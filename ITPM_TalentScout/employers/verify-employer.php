<?php
session_start();
require_once __DIR__ . '/../database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['peso_admin_logged_in']) || $_SESSION['peso_admin_logged_in'] !== true) {
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit;
}

$employer_id = (int)($_POST['employer_id'] ?? 0);

if ($employer_id <= 0) {
  echo json_encode(['success' => false, 'message' => 'Invalid employer ID']);
  exit;
}

try {
  $conn = getConnection();
  $stmt = $conn->prepare('UPDATE employer SET status = ? WHERE employer_id = ?');
  $stmt->bind_param('si', $status, $employer_id);
  $status = 'active';
  
  if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Employer verified successfully']);
  } else {
    echo json_encode(['success' => false, 'message' => 'Failed to verify employer']);
  }
  
  $stmt->close();
  $conn->close();
} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => 'Database error']);
}