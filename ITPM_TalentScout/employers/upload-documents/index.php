<?php
session_start();
require_once __DIR__ . '/../../database/db.php';

if (!isset($_SESSION['employer_id'])) {
  header('Location: ../login.php');
  exit;
}

$employer_id = (int)$_SESSION['employer_id'];
$employer_status = $_SESSION['employer_status'] ?? 'pending';
$isVerified = $employer_status === 'active';

$successMessage = '';
$errorMessage = '';

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
  $uploadDir = __DIR__ . '/../uploads/employer_documents/';
  
  if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
  }
  
  $targetPath = $uploadDir . $filename;
  
  if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    return 'uploads/employer_documents/' . $filename;
  }
  
  return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    $successMessage = 'Documents uploaded successfully! Your documents are now pending verification.';
  } else {
    $errorMessage = 'Please select at least one document to upload.';
  }
  
  $conn->close();
}

$conn = getConnection();
$stmt = $conn->prepare('SELECT business_reg_cert, mayor_permit, bir_registration, dole_registration FROM employer WHERE employer_id = ?');
$stmt->bind_param('i', $employer_id);
$stmt->execute();
$result = $stmt->get_result();
$documents = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Upload Documents | TalentScout AI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --mint: #e8f5ee; --mint-mid: #c8e6d4; --mint-deep: #a8d4b8;
      --sage: #5a8a68; --sage-dark: #3d6b50; --sage-deeper: #2d5040;
      --gold: #c8a46a; --gold-pale: #f5ead8; --gold-light: #f0ddb8;
      --cream: #fdfaf5; --cream-mid: #f7f2ea; --cream-warm: #f0ead8;
      --charcoal: #2c3028; --text-mid: #4a5244; --text-soft: #7a8270;
      --white: #ffffff;
      --radius-xl: 28px; --radius-lg: 18px; --radius-md: 12px;
      --ease: cubic-bezier(0.22, 1, 0.36, 1);
    }
    body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--cream); color: var(--charcoal); min-height: 100vh; }
    a { text-decoration: none; color: inherit; }
    .navbar { position: fixed; top: 0; left: 0; right: 0; z-index: 200; display: flex; align-items: center; justify-content: space-between; padding: 0 2.5rem; height: 66px; background: var(--sage); }
    .nav-logo { display: flex; align-items: center; gap: 0.6rem; font-family: 'Lora', serif; font-weight: 700; font-size: 1.12rem; color: #fff; }
    .nav-logo-mark { width: 36px; height: 36px; background: rgba(255,255,255,0.25); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 700; color: #fff; }
    .nav-logo em { font-style: italic; color: rgba(255,255,255,0.8); }
    .nav-right { display: flex; align-items: center; gap: 0.65rem; }
    .btn-ghost { padding: 0.42rem 1.1rem; border-radius: 999px; border: 1.5px solid rgba(255,255,255,0.3); color: #fff; font-size: 0.83rem; font-weight: 500; background: transparent; cursor: pointer; }
    .btn-ghost:hover { background: rgba(255,255,255,0.15); }
    .btn-solid { padding: 0.46rem 1.25rem; border-radius: 999px; background: rgba(255,255,255,0.2); color: #fff; font-size: 0.83rem; font-weight: 700; border: 1.5px solid rgba(255,255,255,0.4); cursor: pointer; }
    .main-content { padding: calc(66px + 3rem) 2.5rem 3rem; max-width: 800px; margin: 0 auto; }
    .page-header { margin-bottom: 2rem; }
    .page-title { font-family: 'Lora', serif; font-size: 2rem; font-weight: 700; color: var(--charcoal); margin-bottom: 0.5rem; }
    .page-subtitle { font-size: 1rem; color: var(--text-soft); }
    .status-banner { background: var(--gold-light); border: 1px solid var(--gold); border-radius: var(--radius-lg); padding: 1.25rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 1rem; }
    .status-banner-icon { font-size: 1.5rem; }
    .status-banner-text { flex: 1; font-size: 0.95rem; color: #8a6030; }
    .status-badge { display: inline-block; padding: 0.35rem 0.75rem; border-radius: 999px; font-size: 0.8rem; font-weight: 700; }
    .status-pending { background: #fef3d0; color: #8a6030; }
    .status-active { background: var(--mint); color: var(--sage-dark); }
    .card { background: var(--white); border-radius: var(--radius-xl); padding: 2rem; box-shadow: 0 4px 24px rgba(60,80,50,0.08); }
    .card-title { font-family: 'Lora', serif; font-size: 1.25rem; font-weight: 700; margin-bottom: 1.5rem; }
    .doc-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
    .field label { display: block; font-size: 0.85rem; font-weight: 600; color: var(--text-mid); margin-bottom: 0.5rem; }
    .file-input { width: 100%; border: 1.5px solid var(--cream-warm); border-radius: var(--radius-lg); padding: 0.75rem; font-size: 0.9rem; background: var(--cream); cursor: pointer; }
    .file-input:focus { outline: none; border-color: var(--sage); box-shadow: 0 0 0 3px var(--mint); }
    .doc-note { font-size: 0.8rem; color: var(--text-soft); margin-top: 1rem; }
    .current-docs { margin-bottom: 2rem; padding: 1rem; background: var(--cream-mid); border-radius: var(--radius-md); }
    .current-docs-title { font-size: 0.9rem; font-weight: 600; margin-bottom: 0.75rem; color: var(--text-mid); }
    .current-doc-item { display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: var(--text-soft); margin-bottom: 0.5rem; }
    .current-doc-item.uploaded { color: var(--sage-dark); }
    .current-doc-item.missing { color: var(--text-soft); }
    .btn-submit { width: 100%; padding: 1rem; background: var(--sage); color: #fff; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1rem; font-weight: 700; border: none; border-radius: var(--radius-lg); cursor: pointer; margin-top: 1.5rem; transition: all 0.2s; }
    .btn-submit:hover { background: var(--sage-dark); }
    .alert { padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-size: 0.9rem; }
    .alert-success { background: var(--mint); color: var(--sage-dark); border: 1px solid var(--mint-mid); }
    .alert-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
    .back-link { display: inline-flex; align-items: center; gap: 0.5rem; color: var(--sage); font-weight: 600; margin-bottom: 1.5rem; }
    .back-link:hover { color: var(--sage-dark); }
    @media (max-width: 600px) { .doc-grid { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
  <nav class="navbar">
    <a href="../index.php" class="nav-logo">
      <div class="nav-logo-mark">TS</div>
      <span>Talent<em>Scout</em> AI</span>
    </a>
    <div class="nav-right">
      <span style="color:#fff;font-size:0.85rem;"><?php echo htmlspecialchars($_SESSION['employer_name'] ?? 'Employer'); ?></span>
      <a href="../logout.php" class="btn-ghost">Logout</a>
    </div>
  </nav>

  <main class="main-content">
    <a href="../index.php" class="back-link">← Back to Dashboard</a>
    
    <div class="page-header">
      <h1 class="page-title">Submit Business Documents</h1>
      <p class="page-subtitle">Upload your business documents for verification to access all features.</p>
    </div>

    <div class="status-banner">
      <span class="status-banner-icon"><?php echo $isVerified ? '✅' : '⏳'; ?></span>
      <span class="status-banner-text">
        <?php if ($isVerified): ?>
          Your account is verified. You have full access to all features.
        <?php else: ?>
          Your account is pending verification. Submit your documents to get verified.
        <?php endif; ?>
      </span>
      <span class="status-badge <?php echo $isVerified ? 'status-active' : 'status-pending'; ?>">
        <?php echo $isVerified ? 'Verified' : 'Pending'; ?>
      </span>
    </div>

    <?php if ($successMessage): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
    <?php endif; ?>
    
    <?php if ($errorMessage): ?>
      <div class="alert alert-error"><?php echo htmlspecialchars($errorMessage); ?></div>
    <?php endif; ?>

    <div class="card">
      <h2 class="card-title">Upload Documents</h2>
      
      <div class="current-docs">
        <div class="current-docs-title">Current Documents:</div>
        <div class="current-doc-item <?php echo !empty($documents['business_reg_cert']) ? 'uploaded' : 'missing'; ?>">
          <?php echo !empty($documents['business_reg_cert']) ? '✅' : '❌'; ?> Business Registration Certificate
        </div>
        <div class="current-doc-item <?php echo !empty($documents['mayor_permit']) ? 'uploaded' : 'missing'; ?>">
          <?php echo !empty($documents['mayor_permit']) ? '✅' : '❌'; ?> Mayor's Business Permit
        </div>
        <div class="current-doc-item <?php echo !empty($documents['bir_registration']) ? 'uploaded' : 'missing'; ?>">
          <?php echo !empty($documents['bir_registration']) ? '✅' : '❌'; ?> BIR Registration
        </div>
        <div class="current-doc-item <?php echo !empty($documents['dole_registration']) ? 'uploaded' : 'missing'; ?>">
          <?php echo !empty($documents['dole_registration']) ? '✅' : '❌'; ?> DOLE Registration
        </div>
      </div>

      <form method="post" enctype="multipart/form-data">
        <div class="doc-grid">
          <div class="field">
            <label for="business_reg_cert">Business Registration Certificate</label>
            <input type="file" id="business_reg_cert" name="business_reg_cert" accept=".pdf,.jpg,.jpeg,.png" class="file-input">
          </div>
          <div class="field">
            <label for="mayor_permit">Mayor's Business Permit</label>
            <input type="file" id="mayor_permit" name="mayor_permit" accept=".pdf,.jpg,.jpeg,.png" class="file-input">
          </div>
          <div class="field">
            <label for="bir_registration">BIR Registration</label>
            <input type="file" id="bir_registration" name="bir_registration" accept=".pdf,.jpg,.jpeg,.png" class="file-input">
          </div>
          <div class="field">
            <label for="dole_registration">DOLE Registration</label>
            <input type="file" id="dole_registration" name="dole_registration" accept=".pdf,.jpg,.jpeg,.png" class="file-input">
          </div>
        </div>
        <p class="doc-note">Accepted formats: PDF, JPG, PNG (Max 5MB per file)</p>
        <button type="submit" class="btn-submit">Upload Documents</button>
      </form>
    </div>
  </main>
</body>
</html>