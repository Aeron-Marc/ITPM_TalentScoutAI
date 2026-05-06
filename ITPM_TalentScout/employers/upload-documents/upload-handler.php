<?php
session_start();
require_once __DIR__ . '/../../database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['employer_id'])) {
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit;
}

$employer_id = (int)$_SESSION['employer_id'];

function uploadDocument($file, $employerId, $docType) {
  if ($file['error'] === UPLOAD_ERR_NO_FILE) {
    return null;
  }
  
  if ($file['error'] !== UPLOAD_ERR_OK) {
    return false;
  }
  
  $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
  $maxSize = 5 * 1024 * 1024;
  
  if (!in_array($file['type'], $allowedTypes)) {
    return false;
  }
  
  if ($file['size'] > $maxSize) {
    return false;
  }
  
  $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
  $filename = 'employer_' . $employerId . '_' . $docType . '_' . time() . '.' . $extension;
  $uploadDir = __DIR__ . '/../../uploads/employer_documents/';
  
  if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
  }
  
  $targetPath = $uploadDir . $filename;
  
  if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    return 'uploads/employer_documents/' . $filename;
  }
  
  return false;
}

try {
  $conn = getConnection();
  
  $businessRegCert = uploadDocument($_FILES['business_reg_cert'] ?? null, $employer_id, 'business_reg_cert');
  $mayorPermit = uploadDocument($_FILES['mayor_permit'] ?? null, $employer_id, 'mayor_permit');
  $birRegistration = uploadDocument($_FILES['bir_registration'] ?? null, $employer_id, 'bir_registration');
  $doleRegistration = uploadDocument($_FILES['dole_registration'] ?? null, $employer_id, 'dole_registration');
  
  $docColumns = [];
  $docValues = [];
  $docTypes = '';
  
  if ($businessRegCert !== false && $businessRegCert !== null) {
    $docColumns[] = 'business_reg_cert = ?';
    $docValues[] = $businessRegCert;
    $docTypes .= 's';
  }
  if ($mayorPermit !== false && $mayorPermit !== null) {
    $docColumns[] = 'mayor_permit = ?';
    $docValues[] = $mayorPermit;
    $docTypes .= 's';
  }
  if ($birRegistration !== false && $birRegistration !== null) {
    $docColumns[] = 'bir_registration = ?';
    $docValues[] = $birRegistration;
    $docTypes .= 's';
  }
  if ($doleRegistration !== false && $doleRegistration !== null) {
    $docColumns[] = 'dole_registration = ?';
    $docValues[] = $doleRegistration;
    $docTypes .= 's';
  }
  
  if (count($docColumns) > 0) {
    $docValues[] = $employer_id;
    $docTypes .= 'i';
    $updateDocStmt = $conn->prepare('UPDATE employer SET ' . implode(', ', $docColumns) . ' WHERE employer_id = ?');
    $updateDocStmt->bind_param($docTypes, ...$docValues);
    $updateDocStmt->execute();
    $updateDocStmt->close();
    
    echo json_encode(['success' => true, 'message' => 'Documents uploaded successfully']);
  } else {
    echo json_encode(['success' => false, 'message' => 'No files selected']);
  }
  
  $conn->close();
} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => 'Database error']);
}