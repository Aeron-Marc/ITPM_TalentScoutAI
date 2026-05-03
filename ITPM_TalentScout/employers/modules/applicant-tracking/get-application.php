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

$application_id = $_GET['application_id'] ?? null;
$employer_id = (int)$_SESSION['employer_id'];

// Validate input
if (!$application_id || !is_numeric($application_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid application ID']);
    exit;
}

try {
    $conn = getConnection();

    // Get application details including candidate info
    $query = "SELECT 
                a.application_id,
                a.job_post_id,
                a.employee_id,
                a.status,
                a.application_date,
                e.first_name,
                e.last_name,
                e.email,
                e.address,
                COALESCE(r.phone, '') as phone,
                jp.title as job_title,
                jp.description as job_description,
                jp.salary,
                jp.skills as job_skills,
                c.company_name
              FROM application a
              JOIN job_post jp ON a.job_post_id = jp.job_post_id
              JOIN employee e ON a.employee_id = e.employee_id
              JOIN employer c ON jp.employer_id = c.employer_id
              LEFT JOIN resumes r ON e.employee_id = r.employee_id
              WHERE a.application_id = ? AND jp.employer_id = ?";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ii", $application_id, $employer_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $stmt->close();
        closeConnection($conn);
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Application not found']);
        exit;
    }

    $application = $result->fetch_assoc();
    $stmt->close();
    closeConnection($conn);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'application' => $application
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    exit;
}
?>
