<?php
session_start();
require_once __DIR__ . '/../../../database/db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['employee_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$employee_id = $_SESSION['employee_id'];
$job_post_id = $_POST['job_post_id'] ?? null;

// Validate input
if (!$job_post_id || !is_numeric($job_post_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid job post ID']);
    exit;
}

$job_post_id = (int)$job_post_id;

try {
    $conn = getConnection();

    // Check if job exists
    $checkJobQuery = "SELECT jp.title, jp.job_post_id, e.company_name FROM job_post jp 
                      JOIN employer e ON jp.employer_id = e.employer_id 
                      WHERE jp.job_post_id = ?";
    $checkStmt = $conn->prepare($checkJobQuery);
    if (!$checkStmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $checkStmt->bind_param("i", $job_post_id);
    if (!$checkStmt->execute()) {
        throw new Exception("Execute failed: " . $checkStmt->error);
    }

    $jobResult = $checkStmt->get_result();
    if ($jobResult->num_rows === 0) {
        $checkStmt->close();
        closeConnection($conn);
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Job posting not found']);
        exit;
    }

    $jobData = $jobResult->fetch_assoc();
    $checkStmt->close();

    // Check if user has already applied for this job
    $checkAppliedQuery = "SELECT application_id, status, application_date FROM application WHERE employee_id = ? AND job_post_id = ?";
    $checkAppliedStmt = $conn->prepare($checkAppliedQuery);
    if (!$checkAppliedStmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $checkAppliedStmt->bind_param("ii", $employee_id, $job_post_id);
    if (!$checkAppliedStmt->execute()) {
        throw new Exception("Execute failed: " . $checkAppliedStmt->error);
    }

    $appliedResult = $checkAppliedStmt->get_result();
    if ($appliedResult->num_rows > 0) {
        $existingApp = $appliedResult->fetch_assoc();
        $checkAppliedStmt->close();
        closeConnection($conn);
        http_response_code(409);
        echo json_encode([
            'success' => false, 
            'message' => 'You have already applied for this job',
            'existing_application' => [
                'application_id' => $existingApp['application_id'],
                'status' => $existingApp['status'],
                'application_date' => $existingApp['application_date']
            ]
        ]);
        exit;
    }

    $checkAppliedStmt->close();

    // Insert application record
    $applicationDate = date('Y-m-d');
    $status = 'Pending';
    $is_anonymous = isset($_POST['is_anonymous']) && $_POST['is_anonymous'] == '1' ? 1 : 0;

    $insertQuery = "INSERT INTO application (job_post_id, employee_id, status, application_date, is_anonymous) 
                    VALUES (?, ?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertQuery);
    if (!$insertStmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $insertStmt->bind_param("iissi", $job_post_id, $employee_id, $status, $applicationDate, $is_anonymous);
    if (!$insertStmt->execute()) {
        throw new Exception("Execute failed: " . $insertStmt->error);
    }

    $applicationId = $insertStmt->insert_id;
    $insertStmt->close();
    closeConnection($conn);

    // Send success response with job details
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Application submitted successfully!',
        'application' => [
            'application_id' => $applicationId,
            'job_post_id' => $job_post_id,
            'job_title' => $jobData['title'],
            'company_name' => $jobData['company_name'],
            'application_date' => $applicationDate,
            'status' => $status
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    exit;
}
?>
