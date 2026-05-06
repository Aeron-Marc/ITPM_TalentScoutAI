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

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$employee_id = $_SESSION['employee_id'];

try {
    $conn = getConnection();

    // Fetch all applications for the logged-in employee with job details
    $query = "SELECT 
      a.application_id,
      a.job_post_id,
      a.employee_id,
      a.application_date,
      a.status,
      a.hire_status,
      j.title as job_title,
      e.company_name,
      e.employer_id
    FROM application a
    JOIN job_post j ON a.job_post_id = j.job_post_id
    JOIN employer e ON j.employer_id = e.employer_id
    WHERE a.employee_id = ?
    ORDER BY a.application_date DESC";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $employee_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $applications = [];

    while ($row = $result->fetch_assoc()) {
        // Calculate display status based on hire_status priority
        $displayStatus = $row['status'];
        $hireStatus = $row['hire_status'] ?? 'none';
        
        // Override display status based on hire_status
        if ($hireStatus === 'accepted') {
            $displayStatus = 'Hired';
        } elseif ($hireStatus === 'rejected') {
            $displayStatus = 'Offer Declined';
        } elseif ($hireStatus === 'offered') {
            $displayStatus = 'Offer Received';
        }
        
        $row['display_status'] = $displayStatus;
        $applications[] = $row;
    }

    $stmt->close();
    closeConnection($conn);

    // Calculate statistics
    $totalApplications = count($applications);
    $hiredCount = count(array_filter($applications, function($app) {
        return ($app['hire_status'] ?? 'none') === 'accepted';
    }));
    $jobOffers = count(array_filter($applications, function($app) {
        return ($app['hire_status'] ?? 'none') === 'offered';
    }));
    $interviewsScheduled = count(array_filter($applications, function($app) {
        // Count interview only if not hired or offered
        $hireStatus = $app['hire_status'] ?? 'none';
        return stripos($app['status'], 'interview') !== false && !in_array($hireStatus, ['offered', 'accepted']);
    }));
    $underReview = count(array_filter($applications, function($app) {
        $status = strtolower($app['status']);
        $hireStatus = $app['hire_status'] ?? 'none';
        // Under review if: not hired, not offered, not interview, not rejected
        return !in_array($hireStatus, ['offered', 'accepted', 'rejected']) && 
               $status !== 'rejected' && 
               stripos($status, 'interview') === false &&
               stripos($status, 'offer') === false;
    }));

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'applications' => $applications,
        'stats' => [
            'totalApplications' => $totalApplications,
            'hired' => $hiredCount,
            'jobOffers' => $jobOffers,
            'interviewsScheduled' => $interviewsScheduled,
            'underReview' => $underReview
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    exit;
}
?>
