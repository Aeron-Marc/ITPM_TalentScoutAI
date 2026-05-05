<?php
// Shared data loader for PESO dashboard pages
// Does not emit HTML — only prepares variables used by templates.
require_once __DIR__ . '/../database/db.php';

$conn = getConnection();

$stats = [
    'total_applicants' => 0,
    'active_jobs' => 0,
    'successful_hires' => 0,
    'employers' => 0
];

// Safely run queries and populate arrays. If any query fails, leave defaults intact.
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM employee");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['total_applicants'] = (int) ($row['count'] ?? 0);
        $stmt->close();
    }

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM job_post WHERE application_deadline >= CURDATE()");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['active_jobs'] = (int) ($row['count'] ?? 0);
        $stmt->close();
    }

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM application WHERE status = 'Hired' OR status = 'Accepted'");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['successful_hires'] = (int) ($row['count'] ?? 0);
        $stmt->close();
    }

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM employer");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stats['employers'] = (int) ($row['count'] ?? 0);
        $stmt->close();
    }

    // Status distribution
    $status_dist = ['Pending' => 0, 'Applied' => 0, 'Interview Scheduled' => 0, 'Matched' => 0, 'Offer Received' => 0, 'Offer Sent' => 0, 'Offer Declined' => 0, 'Accepted' => 0, 'Rejected' => 0, 'Hired' => 0];
    $stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM application GROUP BY status");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $status_dist[$row['status']] = (int) $row['count'];
        }
        $stmt->close();
    }

    $application_total = array_sum($status_dist);
    $status_colors = [
        'Pending' => '#6c757d',
        'Applied' => '#17a2b8',
        'Interview Scheduled' => '#ffc107',
        'Matched' => '#6610f2',
        'Offer Received' => '#20c997',
        'Offer Sent' => '#007bff',
        'Offer Declined' => '#fd7e14',
        'Accepted' => '#28a745',
        'Rejected' => '#dc3545',
        'Hired' => '#1E9E86'
    ];

    $donut_segments = [];
    if ($application_total > 0) {
        $start = 0;
        foreach ($status_dist as $status => $count) {
            if ($count <= 0) {
                continue;
            }

            $end = $start + (($count / $application_total) * 360);
            $color = $status_colors[$status] ?? '#1E9E86';
            $donut_segments[] = sprintf('%s %.2fdeg %.2fdeg', $color, $start, $end);
            $start = $end;
        }
    }

    // Recent applications
    $recent_apps = [];
    $stmt = $conn->prepare("SELECT e.first_name, e.last_name, e.address, jp.title, a.status, a.application_date FROM application a JOIN employee e ON a.employee_id = e.employee_id JOIN job_post jp ON a.job_post_id = jp.job_post_id ORDER BY a.application_date DESC LIMIT 20");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $recent_apps[] = $row;
        }
        $stmt->close();
    }

    // Top positions
    $top_positions = [];
    $stmt = $conn->prepare("SELECT jp.title, COUNT(*) as count FROM application a JOIN job_post jp ON a.job_post_id = jp.job_post_id GROUP BY jp.title ORDER BY count DESC LIMIT 5");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $top_positions[] = $row;
        }
        $stmt->close();
    }

    // Top employers
    $top_employers = [];
    $stmt = $conn->prepare("SELECT em.company_name, COUNT(*) as count FROM application a JOIN job_post jp ON a.job_post_id = jp.job_post_id JOIN employer em ON jp.employer_id = em.employer_id GROUP BY em.company_name ORDER BY count DESC LIMIT 5");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $top_employers[] = $row;
        }
        $stmt->close();
    }

    // Barangays
    $barangays = [];
    $stmt = $conn->prepare("SELECT address, COUNT(*) as count FROM employee WHERE address IS NOT NULL AND address != '' GROUP BY address ORDER BY count DESC LIMIT 8");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $barangays[] = $row;
        }
        $stmt->close();
    }
} catch (Exception $e) {
    // Silent fallback - templates will use default/empty values
}

$max_barangay = !empty($barangays) ? (int) $barangays[0]['count'] : 1;
$donut_gradient = !empty($donut_segments)
    ? 'conic-gradient(' . implode(', ', $donut_segments) . ')'
    : 'conic-gradient(#d9e2ec 0deg 360deg)';

$conn->close();
