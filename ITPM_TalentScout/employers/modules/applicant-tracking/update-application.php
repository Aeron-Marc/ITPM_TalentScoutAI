<?php
session_start();
require_once('../../../database/db.php');

header('Content-Type: application/json');

// Check if employer is logged in
if (!isset($_SESSION['employer_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$application_id = $_POST['application_id'] ?? null;
$new_status = $_POST['status'] ?? null;
$employer_id = (int)$_SESSION['employer_id'];

// Validate input
if (!$application_id || !is_numeric($application_id) || !$new_status) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Validate status value - map to database enum values
$status_map = [
    'Applied' => 'Pending',
    'In Review' => 'Pending',
    'Interview' => 'Interview Scheduled',
    'Offer Sent' => 'Offer Received',
    'Hired' => 'Offer Received',
    'Rejected' => 'Rejected'
];

if (!isset($status_map[$new_status])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

$db_status = $status_map[$new_status];

try {
    $conn = getConnection();

    // Verify that this application belongs to this employer's job
    $verifyQuery = "SELECT a.application_id FROM application a
                    JOIN job_post jp ON a.job_post_id = jp.job_post_id
                    WHERE a.application_id = ? AND jp.employer_id = ?";
    $verifyStmt = $conn->prepare($verifyQuery);
    if (!$verifyStmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $verifyStmt->bind_param("ii", $application_id, $employer_id);
    if (!$verifyStmt->execute()) {
        throw new Exception("Execute failed: " . $verifyStmt->error);
    }

    $verifyResult = $verifyStmt->get_result();
    if ($verifyResult->num_rows === 0) {
        $verifyStmt->close();
        closeConnection($conn);
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Application not found or unauthorized']);
        exit;
    }

    $verifyStmt->close();

    // Update the application status
    $updateQuery = "UPDATE application SET status = ?, updated_at = NOW() WHERE application_id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    if (!$updateStmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $updateStmt->bind_param("si", $db_status, $application_id);
    if (!$updateStmt->execute()) {
        throw new Exception("Execute failed: " . $updateStmt->error);
    }

    $updateStmt->close();
    closeConnection($conn);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Application status updated successfully',
        'new_status' => $new_status,
        'db_status' => $db_status
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    exit;
}
?>
