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

$employee_id = $_GET['employee_id'] ?? null;

if (!$employee_id || !is_numeric($employee_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid employee ID']);
    exit;
}

try {
    $conn = getConnection();
    
    // Get employee basic info
    $stmt = $conn->prepare("SELECT * FROM employee WHERE employee_id = ?");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        closeConnection($conn);
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        exit;
    }
    
    $employee = $result->fetch_assoc();
    $stmt->close();
    
    // Get skills
    $skillsStmt = $conn->prepare("SELECT skill_name FROM employee_skill WHERE employee_id = ?");
    $skillsStmt->bind_param("i", $employee_id);
    $skillsStmt->execute();
    $skillsResult = $skillsStmt->get_result();
    $skills = [];
    while ($skillRow = $skillsResult->fetch_assoc()) {
        $skills[] = $skillRow['skill_name'];
    }
    $skillsStmt->close();
    $employee['skills'] = $skills;
    
    // Get resume ID first
    $resumeStmt = $conn->prepare("SELECT resume_id FROM resumes WHERE employee_id = ? ORDER BY resume_id DESC LIMIT 1");
    $resumeStmt->bind_param("i", $employee_id);
    $resumeStmt->execute();
    $resumeResult = $resumeStmt->get_result();
    $resume_id = null;
    if ($resumeRow = $resumeResult->fetch_assoc()) {
        $resume_id = $resumeRow['resume_id'];
    }
    $resumeStmt->close();
    
    // Get experiences if resume exists
    $experiences = [];
    if ($resume_id) {
        $expStmt = $conn->prepare("SELECT job_title, company, start_date, end_date FROM employee_experience WHERE resume_id = ? ORDER BY start_date DESC");
        $expStmt->bind_param("i", $resume_id);
        $expStmt->execute();
        $expResult = $expStmt->get_result();
        while ($expRow = $expResult->fetch_assoc()) {
            $experiences[] = $expRow;
        }
        $expStmt->close();
    }
    $employee['experiences'] = $experiences;
    
    // Get education if resume exists
    $educations = [];
    if ($resume_id) {
        $eduStmt = $conn->prepare("SELECT degree, school, start_date, end_date FROM employee_education WHERE resume_id = ? ORDER BY start_date DESC");
        $eduStmt->bind_param("i", $resume_id);
        $eduStmt->execute();
        $eduResult = $eduStmt->get_result();
        while ($eduRow = $eduResult->fetch_assoc()) {
            $educations[] = $eduRow;
        }
        $eduStmt->close();
    }
    $employee['educations'] = $educations;
    
    closeConnection($conn);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'employee' => $employee
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    exit;
}
?>