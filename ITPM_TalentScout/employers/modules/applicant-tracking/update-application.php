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
$action = $_POST['action'] ?? null;
$scheduled_date = $_POST['scheduled_date'] ?? null;
$scheduled_time = $_POST['scheduled_time'] ?? null;
$confirmation_message = $_POST['confirmation_message'] ?? null;
$hire_message = $_POST['hire_message'] ?? null;
$employer_id = (int)$_SESSION['employer_id'];

// Validate input
if (!$application_id || !is_numeric($application_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    $conn = getConnection();

    // Verify application belongs to this employer and get employee_id
    $verifyQuery = "SELECT a.application_id, a.employee_id FROM application a
                    JOIN job_post jp ON a.job_post_id = jp.job_post_id
                    WHERE a.application_id = ? AND jp.employer_id = ?";
    $verifyStmt = $conn->prepare($verifyQuery);
    if (!$verifyStmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $verifyStmt->bind_param("ii", $application_id, $employer_id);
    $verifyStmt->execute();
    $verifyResult = $verifyStmt->get_result();
    if ($verifyResult->num_rows === 0) {
        $verifyStmt->close();
        closeConnection($conn);
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Application not found or unauthorized']);
        exit;
    }

    $application = $verifyResult->fetch_assoc();
    $employee_id = $application['employee_id'];
    $verifyStmt->close();

    // Handle schedule_interview_action
    if ($action === 'schedule_interview_action') {
        $scheduled_datetime = $scheduled_date . ' ' . $scheduled_time . ':00';
        $conf_msg = $confirmation_message ?: 'Please confirm your availability for the scheduled interview.';
        
        // Insert into interviews table
        $interviewStmt = $conn->prepare("INSERT INTO interviews (application_id, employer_id, employee_id, scheduled_datetime, confirmation_message, status) VALUES (?, ?, ?, ?, ?, 'scheduled')");
        $interviewStmt->bind_param("iiiss", $application_id, $employer_id, $employee_id, $scheduled_datetime, $conf_msg);
        $interviewStmt->execute();
        $interviewStmt->close();
        
        // Update application status
        $updateStmt = $conn->prepare("UPDATE application SET status = 'Interview Scheduled' WHERE application_id = ?");
        $updateStmt->bind_param("i", $application_id);
        $updateStmt->execute();
        $updateStmt->close();
        
        // Send notification message
        $msg = "📅 Interview scheduled for " . date('M d, Y g:i A', strtotime($scheduled_datetime)) . ". " . $conf_msg;
        $msgStmt = $conn->prepare("INSERT INTO message (sender_id, sender_type, receiver_id, receiver_type, message, application_id, timestamp) VALUES (?, 'employer', ?, 'employee', ?, ?, NOW())");
        $msgStmt->bind_param("iisi", $employer_id, $employee_id, $msg, $application_id);
        $msgStmt->execute();
        $msgStmt->close();
        
        closeConnection($conn);
        
        echo json_encode(['success' => true, 'message' => 'Interview scheduled successfully']);
        exit;
    }
    
    // Handle offer_hire_action
    if ($action === 'offer_hire_action') {
        // Update hire_status to offered
        $updateStmt = $conn->prepare("UPDATE application SET hire_status = 'offered', hire_offer_message = ?, hire_offer_date = NOW() WHERE application_id = ?");
        $updateStmt->bind_param("si", $hire_message, $application_id);
        $updateStmt->execute();
        $updateStmt->close();
        
        // Send job offer message
        $msg = "🎉 JOB OFFER! Congratulations! " . ($hire_message ?: "We are pleased to offer you the position. Please respond to this offer.");
        $msgStmt = $conn->prepare("INSERT INTO message (sender_id, sender_type, receiver_id, receiver_type, message, application_id, timestamp) VALUES (?, 'employer', ?, 'employee', ?, ?, NOW())");
        $msgStmt->bind_param("iisi", $employer_id, $employee_id, $msg, $application_id);
        $msgStmt->execute();
        $msgStmt->close();
        
        closeConnection($conn);
        
        echo json_encode(['success' => true, 'message' => 'Job offer sent successfully']);
        exit;
    }

    // Handle regular status updates (Applied, Hired, Rejected)
    // Status mapping
    $status_map = [
        'Applied' => ['status' => 'Pending', 'hire_status' => 'none'],
        'Schedule Interview' => ['status' => 'Interview Scheduled', 'hire_status' => 'none'],
        'Send Offer' => ['status' => 'Offer Received', 'hire_status' => 'offered'],
        'Hired' => ['status' => 'Offer Received', 'hire_status' => 'accepted'],
        'Rejected' => ['status' => 'Rejected', 'hire_status' => 'rejected']
    ];

    if (!isset($status_map[$new_status])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid status: ' . $new_status]);
        exit;
    }

    $status_data = $status_map[$new_status];
    $db_status = $status_data['status'];
    $db_hire_status = $status_data['hire_status'];

    // Update the application status and hire_status
    $updateQuery = "UPDATE application SET status = ?, hire_status = ? WHERE application_id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    if (!$updateStmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $updateStmt->bind_param("ssi", $db_status, $db_hire_status, $application_id);
    if (!$updateStmt->execute()) {
        throw new Exception("Execute failed: " . $updateStmt->error);
    }

    $updateStmt->close();

    // Auto-send notification message to candidate
    $status_messages = [
        'Applied' => 'Your application has been received and is pending review.',
        'Schedule Interview' => '📅 Interview Scheduled! Your application has been moved to interview stage. Please check your messages for details.',
        'Send Offer' => 'Great news! We would like to extend a job offer to you.',
        'Hired' => 'Congratulations! You have been hired! Welcome aboard!',
        'Rejected' => 'Thank you for your interest. We have decided to move forward with other candidates.'
    ];
    
    $notification_msg = $status_messages[$new_status] ?? 'Your application status has been updated.';
    
    // Insert notification message
    $msgStmt = $conn->prepare("INSERT INTO message (sender_id, sender_type, receiver_id, receiver_type, message, application_id, timestamp) VALUES (?, 'employer', ?, 'employee', ?, ?, NOW())");
    $msgStmt->bind_param("iisi", $employer_id, $employee_id, $notification_msg, $application_id);
    $msgStmt->execute();
    $msgStmt->close();

    closeConnection($conn);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Application status updated successfully',
        'new_status' => $new_status,
        'db_status' => $db_status,
        'db_hire_status' => $db_hire_status,
        'notification_sent' => true
    ]);

} catch (Exception $e) {
    error_log("Status update error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    exit;
}
?>
