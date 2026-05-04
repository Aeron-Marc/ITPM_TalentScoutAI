<?php 
session_start();
require_once('../../../database/db.php');

// Check if employer is logged in
if (!isset($_SESSION['employer_id'])) {
  header('Location: ../../login.php');
  exit;
}

// Get database connection
$conn = getConnection();
$employer_id = (int)$_SESSION['employer_id'];

// Handle new message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
  $receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;
  $message = trim($_POST['message'] ?? '');
  $application_id = isset($_POST['application_id']) ? intval($_POST['application_id']) : 0;

  if ($receiver_id > 0 && !empty($message)) {
    $stmt = $conn->prepare("INSERT INTO message (sender_id, sender_type, receiver_id, receiver_type, message, application_id, timestamp) VALUES (?, 'employer', ?, 'employee', ?, ?, NOW())");
    $stmt->bind_param("iisi", $employer_id, $receiver_id, $message, $application_id);
    if ($stmt->execute()) {
      $stmt->close();
      header('Location: ' . $_SERVER['REQUEST_URI']);
      exit;
    }
    $stmt->close();
  }
}

// Fetch all conversations (unique employee IDs who have messages with this employer)
$conversations = [];
$stmt = $conn->prepare("SELECT DISTINCT e.employee_id, 
  COALESCE(
    (SELECT 'Anonymous Applicant' FROM application a 
     WHERE a.employee_id = e.employee_id AND a.is_anonymous = 1 
     LIMIT 1),
    CONCAT(e.first_name, ' ', e.last_name)
  ) as display_name
FROM message m
JOIN employee e ON (m.sender_id = e.employee_id OR m.receiver_id = e.employee_id)
WHERE (m.sender_id = ? OR m.receiver_id = ?)
AND (m.sender_type = 'employer' OR m.receiver_type = 'employer')
ORDER BY m.timestamp DESC");
$stmt->bind_param("ii", $employer_id, $employer_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $conversations[] = $row;
}
$stmt->close();

// Get selected conversation (first one or from request)
$selected_employee_id = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : (count($conversations) > 0 ? $conversations[0]['employee_id'] : 0);
$selected_employee_name = '';
if ($selected_employee_id > 0) {
  foreach ($conversations as $conv) {
    if ($conv['employee_id'] === $selected_employee_id) {
      $selected_employee_name = $conv['display_name'];
      break;
    }
  }
}

// Fetch messages for selected conversation
$messages = [];
if ($selected_employee_id > 0) {
  $stmt = $conn->prepare("SELECT m.*, e.first_name, e.last_name, a.job_post_id, j.title as job_title
  FROM message m
  LEFT JOIN employee e ON m.sender_id = e.employee_id
  LEFT JOIN application a ON m.application_id = a.application_id
  LEFT JOIN job_post j ON a.job_post_id = j.job_post_id
  WHERE (m.sender_id = ? AND m.receiver_id = ? AND m.sender_type = 'employer') 
  OR (m.sender_id = ? AND m.receiver_id = ? AND m.sender_type = 'employee')
  ORDER BY m.timestamp ASC");
  $stmt->bind_param("iiii", $employer_id, $selected_employee_id, $selected_employee_id, $employer_id);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
  }
  $stmt->close();
}

// Fetch applications between this employer and selected employee (if available)
$applications_with_candidate = [];
$selected_application_id = isset($_GET['application_id']) ? intval($_GET['application_id']) : 0;
if ($selected_employee_id > 0) {
  $stmt = $conn->prepare("SELECT a.application_id, a.status, a.hire_status, j.title as job_title, j.job_post_id
  FROM application a
  JOIN job_post j ON a.job_post_id = j.job_post_id
  WHERE a.employee_id = ? AND j.employer_id = ?
  ORDER BY a.application_date DESC");
  $stmt->bind_param("ii", $selected_employee_id, $employer_id);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $applications_with_candidate[] = $row;
  }
  $stmt->close();
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Schedule Interview
  if (isset($_POST['action']) && $_POST['action'] === 'schedule_interview') {
    $application_id = intval($_POST['application_id'] ?? 0);
    $scheduled_date = $_POST['scheduled_date'] ?? '';
    $scheduled_time = $_POST['scheduled_time'] ?? '';
    $confirmation_message = trim($_POST['confirmation_message'] ?? '');
    
    if ($application_id > 0 && !empty($scheduled_date) && !empty($scheduled_time)) {
      $scheduled_datetime = $scheduled_date . ' ' . $scheduled_time . ':00';
      $stmt = $conn->prepare("INSERT INTO interviews (application_id, employer_id, employee_id, scheduled_datetime, confirmation_message, status) VALUES (?, ?, ?, ?, ?, 'scheduled')");
      $stmt->bind_param("iiiss", $application_id, $employer_id, $selected_employee_id, $scheduled_datetime, $confirmation_message);
      $stmt->execute();
      $stmt->close();
      
      $msg = "Interview scheduled for " . date('M d, Y g:i A', strtotime($scheduled_datetime)) . ". " . ($confirmation_message ? $confirmation_message : "Please confirm your availability.");
      $stmt = $conn->prepare("INSERT INTO message (sender_id, sender_type, receiver_id, receiver_type, message, application_id, timestamp) VALUES (?, 'employer', ?, 'employee', ?, ?, NOW())");
      $stmt->bind_param("iisi", $employer_id, $selected_employee_id, $msg, $application_id);
      $stmt->execute();
      $stmt->close();
      
      header('Location: ' . $_SERVER['REQUEST_URI']);
      exit;
    }
  }
  
  // Offer Hire
  if (isset($_POST['action']) && $_POST['action'] === 'offer_hire') {
    $application_id = intval($_POST['application_id'] ?? 0);
    $hire_message = trim($_POST['hire_message'] ?? '');
    
    if ($application_id > 0) {
      $stmt = $conn->prepare("UPDATE application SET hire_status = 'offered', hire_offer_message = ?, hire_offer_date = NOW() WHERE application_id = ?");
      $stmt->bind_param("si", $hire_message, $application_id);
      $stmt->execute();
      $stmt->close();
      
      $msg = "🎉 JOB OFFER! Congratulations! " . ($hire_message ? "\n\n" . $hire_message : "We are pleased to offer you the position. Please respond to this offer.");
      $stmt = $conn->prepare("INSERT INTO message (sender_id, sender_type, receiver_id, receiver_type, message, application_id, timestamp) VALUES (?, 'employer', ?, 'employee', ?, ?, NOW())");
      $stmt->bind_param("iisi", $employer_id, $selected_employee_id, $msg, $application_id);
      $stmt->execute();
      $stmt->close();
      
      header('Location: ' . $_SERVER['REQUEST_URI']);
      exit;
    }
  }
  
  // Reject Application
  if (isset($_POST['action']) && $_POST['action'] === 'reject_application') {
    $application_id = intval($_POST['application_id'] ?? 0);
    $reject_message = trim($_POST['reject_message'] ?? '');
    
    if ($application_id > 0) {
      $stmt = $conn->prepare("UPDATE application SET status = 'rejected' WHERE application_id = ?");
      $stmt->bind_param("i", $application_id);
      $stmt->execute();
      $stmt->close();
      
      $msg = "Application Update: " . ($reject_message ? $reject_message : "Thank you for your interest. We have decided to move forward with other candidates.");
      $stmt = $conn->prepare("INSERT INTO message (sender_id, sender_type, receiver_id, receiver_type, message, application_id, timestamp) VALUES (?, 'employer', ?, 'employee', ?, ?, NOW())");
      $stmt->bind_param("iisi", $employer_id, $selected_employee_id, $msg, $application_id);
      $stmt->execute();
      $stmt->close();
      
      header('Location: ' . $_SERVER['REQUEST_URI']);
      exit;
    }
  }
  
  // Cancel Interview
  if (isset($_POST['action']) && $_POST['action'] === 'cancel_interview') {
    $interview_id = intval($_POST['interview_id'] ?? 0);
    
    if ($interview_id > 0) {
      $stmt = $conn->prepare("UPDATE interviews SET status = 'cancelled' WHERE interview_id = ? AND employer_id = ?");
      $stmt->bind_param("ii", $interview_id, $employer_id);
      $stmt->execute();
      $stmt->close();
      
      header('Location: ' . $_SERVER['REQUEST_URI']);
      exit;
    }
  }
}

// Fetch current interview status for selected application
$current_interview = null;
$application_hire_status = null;
if ($selected_application_id > 0) {
  $stmt = $conn->prepare("SELECT * FROM interviews WHERE application_id = ? AND employer_id = ? ORDER BY scheduled_datetime DESC LIMIT 1");
  $stmt->bind_param("ii", $selected_application_id, $employer_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($row = $result->fetch_assoc()) {
    $current_interview = $row;
  }
  $stmt->close();
  
  $stmt = $conn->prepare("SELECT hire_status, hire_offer_message FROM application WHERE application_id = ?");
  $stmt->bind_param("i", $selected_application_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($row = $result->fetch_assoc()) {
    $application_hire_status = $row;
  }
  $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Chat & SMS — TalentScout AI</title>
  <link rel="stylesheet" href="../../../styles/global.css">
  <link rel="stylesheet" href="../../../styles/page-layout.css">
  <style>
    /* ===== STICKY FOOTER LAYOUT ===== */
    html,
    body {
      height: 100%;
      margin: 0;
      padding: 0;
    }

    body {
      display: flex;
      flex-direction: column;
    }

    /* Main content area expands to fill available space */
    .page-container,
    main {
      flex: 1 0 auto;
    }

    /* Footer stays at the bottom */
    .footer {
      flex-shrink: 0;
    }

    .page-container {
      max-width: 100%;
      width: 100%;
      margin: 0 auto;
      padding: 2.5rem;
      display: grid;
      grid-template-columns: 350px 1fr;
      gap: 2rem;
      min-height: calc(100vh - 120px);
      height: 100%;
    }

    .page-header {
      grid-column: 1 / -1;
      margin-bottom: 1.5rem;
    }

    .page-header h1 {
      font-size: 1.75rem;
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 0.5rem;
    }

    .page-header p {
      color: var(--text-light);
      font-size: 0.95rem;
    }

    /* Sidebar */
    .conversations-sidebar {
      background: white;
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.5rem;
      height: fit-content;
      position: sticky;
      top: 90px;
      box-shadow: var(--shadow-sm);
    }

    .conversations-title {
      font-weight: 700;
      font-size: 1rem;
      color: var(--text-dark);
      margin-bottom: 1.25rem;
      padding-bottom: 1rem;
      border-bottom: 2px solid var(--border);
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .conversation-list {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }

    .conversation-item {
      padding: 1.25rem;
      border-radius: var(--radius-sm);
      cursor: pointer;
      transition: all 0.25s ease;
      border-left: 4px solid transparent;
      background: transparent;
    }

    .conversation-item:hover {
      background: var(--bg-light);
    }

    .conversation-item.active {
      background: linear-gradient(135deg, #e8fff5 0%, #f4fffb 100%);
      border-left-color: var(--primary-dark);
    }

    .conversation-name {
      font-weight: 600;
      font-size: 1rem;
      color: var(--text-dark);
      margin-bottom: 0.35rem;
    }

    .conversation-preview {
      color: var(--text-light);
      font-size: 0.9rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      margin-bottom: 0.4rem;
    }

    .conversation-time {
      color: var(--text-muted);
      font-size: 0.85rem;
    }

    /* Chat Window */
    .chat-window {
      background: white;
      border: 1px solid var(--border);
      border-radius: var(--radius);
      display: flex;
      flex-direction: column;
      overflow: hidden;
      box-shadow: var(--shadow-sm);
      width: 100%;
      height: 100%;
    }

    .chat-header {
      border-bottom: 1px solid var(--border);
      padding: 1.5rem 2rem;
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      align-items: center;
      gap: 1rem;
      background: linear-gradient(135deg, #f8fffc 0%, #f0fffb 100%);
    }

    .chat-info {
      display: flex;
      flex-direction: column;
    }

    .chat-title {
      font-weight: 700;
      font-size: 1.2rem;
      color: var(--text-dark);
    }

    .chat-status {
      font-size: 0.9rem;
      color: var(--text-light);
      margin-top: 0.3rem;
    }

    .chat-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
    }

    .action-btn {
      background: white;
      border: 1px solid var(--border);
      padding: 0.5rem 1rem;
      border-radius: var(--radius-sm);
      cursor: pointer;
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--text-dark);
      transition: all 0.2s ease;
    }

    .action-btn:hover {
      background: var(--bg-light);
      border-color: var(--primary-dark);
      color: var(--primary-dark);
    }

    .action-btn-interview {
      background: #e3f2fd;
      border-color: #1976d2;
      color: #1976d2;
    }
    .action-btn-interview:hover {
      background: #bbdefb;
    }

    .action-btn-hire {
      background: #e8f5e9;
      border-color: #388e3c;
      color: #388e3c;
    }
    .action-btn-hire:hover {
      background: #c8e6c9;
    }

    .action-btn-reject {
      background: #ffebee;
      border-color: #d32f2f;
      color: #d32f2f;
    }
    .action-btn-reject:hover {
      background: #ffcdd2;
    }

    .interview-badge, .hire-badge {
      padding: 0.4rem 0.8rem;
      border-radius: var(--radius-sm);
      font-size: 0.8rem;
      font-weight: 600;
    }
    .interview-badge.scheduled, .hire-badge.offered {
      background: #fff3e0;
      color: #e65100;
    }
    .interview-badge.accepted, .hire-badge.accepted {
      background: #e8f5e9;
      color: #2e7d32;
    }
    .interview-badge.rejected, .hire-badge.rejected {
      background: #ffebee;
      color: #c62828;
    }

    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0,0,0,0.5);
      z-index: 1000;
      justify-content: center;
      align-items: center;
    }
    .modal.active {
      display: flex;
    }
    .modal-content {
      background: white;
      border-radius: var(--radius);
      width: 90%;
      max-width: 450px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    }
    .modal-header {
      padding: 1.25rem 1.5rem;
      border-bottom: 1px solid var(--border);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .modal-header h3 {
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--text-dark);
      margin: 0;
    }
    .modal-close {
      font-size: 1.5rem;
      color: var(--text-light);
      cursor: pointer;
    }
    .modal-body {
      padding: 1.5rem;
    }
    .form-group {
      margin-bottom: 1rem;
    }
    .form-group label {
      display: block;
      font-size: 0.9rem;
      font-weight: 600;
      color: var(--text-dark);
      margin-bottom: 0.5rem;
    }
    .modal-input {
      width: 100%;
      padding: 0.75rem;
      border: 1.5px solid var(--border);
      border-radius: var(--radius-sm);
      font-size: 0.95rem;
      font-family: inherit;
      transition: all 0.2s ease;
    }
    .modal-input:focus {
      outline: none;
      border-color: var(--primary-dark);
      box-shadow: 0 0 0 3px rgba(30,158,134,0.1);
    }
    .modal-note {
      font-size: 0.85rem;
      color: var(--text-light);
      margin-top: 0.5rem;
    }
    .modal-warning {
      color: #d32f2f;
    }
    .modal-footer {
      padding: 1rem 1.5rem;
      border-top: 1px solid var(--border);
      display: flex;
      justify-content: flex-end;
      gap: 0.75rem;
    }
    .btn-cancel {
      padding: 0.6rem 1.2rem;
      border: 1px solid var(--border);
      background: white;
      border-radius: var(--radius-sm);
      font-size: 0.9rem;
      font-weight: 600;
      color: var(--text-dark);
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .btn-cancel:hover {
      background: var(--bg-light);
    }
    .btn-submit {
      padding: 0.6rem 1.2rem;
      border: none;
      background: var(--primary-dark);
      color: white;
      border-radius: var(--radius-sm);
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .btn-submit:hover {
      background: #157a68;
    }
    .btn-hire {
      background: #388e3c;
    }
    .btn-hire:hover {
      background: #2e7d32;
    }
    .btn-reject {
      background: #d32f2f;
    }
    .btn-reject:hover {
      background: #c62828;
    }

    /* Messages Area */
    .messages-area {
      flex: 1 1 auto;
      padding: 2rem;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      gap: 1.25rem;
      background: white;
      width: 100%;
      min-width: 0;
    }

    .message-group {
      display: flex;
      gap: 0.75rem;
      animation: slideInUp 0.3s ease-out;
      flex-wrap: nowrap;
      align-items: flex-end;
      width: 100%;
      margin-bottom: 0.5rem;
    }

    .message-group.own {
      justify-content: flex-end;
    }

    .message-group.other {
      justify-content: flex-start;
    }

    .message-bubble {
      padding: 1rem 1.35rem;
      border-radius: 12px;
      overflow-wrap: break-word;
      word-break: break-word;
      white-space: normal;
      line-height: 1.6;
      font-size: 1rem;
      transition: all 0.2s ease;
      display: inline-block;
      min-width: 60px;
    }

    .message-group.other .message-bubble {
      background: var(--bg-light);
      color: var(--text-dark);
      border: 1px solid var(--border);
    }

    .message-group.own .message-bubble {
      background: var(--primary-dark);
      color: white;
      box-shadow: 0 2px 8px rgba(30, 158, 134, 0.15);
    }

    .message-time {
      font-size: 0.85rem;
      color: var(--text-light);
      margin-top: 0.4rem;
    }

    .message-content {
      display: flex;
      flex-direction: column;
      max-width: 75%;
    }

    /* Input Area */
    .input-area {
      border-top: 1px solid var(--border);
      padding: 2rem;
      background: white;
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .message-input {
      flex: 1;
      padding: 1rem 1.25rem;
      border: 1.5px solid var(--border);
      border-radius: var(--radius-sm);
      font-family: inherit;
      font-size: 1rem;
      color: var(--text-dark);
      transition: all 0.2s ease;
      background: white;
      min-height: 50px;
      resize: vertical;
    }

    .message-input:focus {
      outline: none;
      border-color: var(--primary-dark);
      box-shadow: 0 0 0 3px rgba(30, 158, 134, 0.1);
    }

    .message-input::placeholder {
      color: var(--text-light);
    }

    .send-btn {
      background: var(--primary-dark);
      color: white;
      border: none;
      padding: 1rem 2rem;
      border-radius: var(--radius-sm);
      font-weight: 600;
      cursor: pointer;
      font-size: 1rem;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      min-height: 45px;
    }

    .send-btn:hover {
      background: #157a68;
      box-shadow: 0 4px 12px rgba(30, 158, 134, 0.15);
      transform: translateY(-1px);
    }

    .send-btn:active {
      transform: translateY(0);
    }

    /* Empty State */
    .empty-state {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      height: 100%;
      color: var(--text-light);
      text-align: center;
    }

    .empty-icon {
      font-size: 3rem;
      margin-bottom: 1rem;
      opacity: 0.5;
    }

    .empty-text {
      font-size: 1rem;
      margin-bottom: 0.5rem;
    }

    .empty-subtext {
      font-size: 0.85rem;
      opacity: 0.7;
    }

    @keyframes slideInUp {
      from {
        opacity: 0;
        transform: translateY(10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .footer {
      background: #0f2b26;
      color: #d7efea;
      padding: 2.75rem 2.5rem 1.5rem;
      margin-top: 3rem;
    }

    .footer-inner {
      max-width: 1200px;
      margin: 0 auto;
    }

    .footer-top {
      display: grid;
      grid-template-columns: 1.6fr 1fr 1fr 1fr;
      gap: 1.4rem;
      margin-bottom: 1.5rem;
    }

    .footer-brand h3,
    .footer-col h4 {
      color: #f1fffc;
      margin-bottom: 0.6rem;
    }

    .footer-brand p,
    .footer-col a {
      color: #b8d9d2;
      font-size: 0.86rem;
      line-height: 1.65;
    }

    .footer-col ul {
      list-style: none;
      display: grid;
      gap: 0.35rem;
    }

    .footer-col a:hover {
      color: white;
    }

    .footer-bottom {
      border-top: 1px solid rgba(215, 239, 234, 0.2);
      padding-top: 0.9rem;
      display: flex;
      justify-content: space-between;
      gap: 0.75rem;
      font-size: 0.8rem;
      color: #add2ca;
      flex-wrap: wrap;
    }

    @media (max-width: 1024px) {
      .page-container {
        grid-template-columns: 260px 1fr;
        gap: 1.5rem;
        padding: 1.5rem;
      }

      .message-content {
        max-width: 80%;
      }
    }

    @media (max-width: 768px) {
      .page-container {
        grid-template-columns: 1fr;
        gap: 1rem;
        padding: 1rem;
      }

      .conversations-sidebar {
        position: relative;
        top: 0;
      }

      .message-content {
        max-width: 90%;
      }

      .footer-top {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <a href="../../index.php" class="nav-logo">
    <div class="nav-logo-icon">TS</div>
    <span class="nav-logo-text">Talent<span>Scout</span> AI</span>
  </a>
  <ul class="nav-links">
    <li><a href="../../index.php">Home</a></li>
    <li><a href="../post-jobs/">Post Jobs</a></li>
    <li><a href="../employee-finder/">Find Talent</a></li>
    <li><a href="../applicant-tracking/">Hiring Pipeline</a></li>
    <li><a href="./" class="active">Messages</a></li>
  </ul>
  <div class="nav-actions">
    <?php if (isset($_SESSION['employer_id'])): ?>
      <span class="nav-user">Welcome, <?php echo htmlspecialchars($_SESSION['employer_name'] ?? 'Employer'); ?></span>
      <a href="../../logout.php" class="btn btn-outline">Logout</a>
    <?php else: ?>
      <a href="../../login.php" class="btn btn-outline">Login</a>
      <a href="../../signup.php" class="btn btn-primary">Get Started</a>
    <?php endif; ?>
  </div>
</nav>

<!-- CONTENT -->
<div class="page-container">
  <!-- Header -->
  <div class="page-header">
    <h1>Messages & Communications</h1>
    <p>Connect with candidates via chat and SMS</p>
  </div>

  <!-- Conversations Sidebar -->
  <div class="conversations-sidebar">
    <div class="conversations-title">Conversations (<?php echo count($conversations); ?>)</div>
    
    <div class="conversation-list">
      <?php if (count($conversations) > 0): ?>
        <?php foreach ($conversations as $conv): ?>
          <div class="conversation-item <?php echo ($conv['employee_id'] === $selected_employee_id) ? 'active' : ''; ?>" onclick="selectConversation(<?php echo $conv['employee_id']; ?>)">
            <div class="conversation-name"><?php echo htmlspecialchars($conv['display_name']); ?></div>
            <div class="conversation-preview">Click to view messages...</div>
            <div class="conversation-time">Recent</div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div style="color: var(--text-light); text-align: center; padding: 1rem; font-size: 0.85rem;">
          No conversations yet
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Chat Window -->
  <div class="chat-window">
    <div class="chat-header">
      <div class="chat-info">
        <div class="chat-title"><?php echo htmlspecialchars($selected_employee_name); ?></div>
        <div class="chat-status">Last message</div>
      </div>
      <div class="chat-actions">
        <?php if (!empty($applications_with_candidate) && $selected_application_id > 0): ?>
          <?php if (!$current_interview || $current_interview['status'] === 'cancelled'): ?>
            <button type="button" class="action-btn action-btn-interview" onclick="openModal('scheduleModal')" title="Schedule Interview">📅 Schedule Interview</button>
          <?php else: ?>
            <?php if ($current_interview['status'] === 'scheduled'): ?>
              <span class="interview-badge scheduled">Interview Scheduled</span>
            <?php elseif ($current_interview['status'] === 'accepted'): ?>
              <span class="interview-badge accepted">Interview Accepted</span>
            <?php elseif ($current_interview['status'] === 'rejected'): ?>
              <span class="interview-badge rejected">Interview Declined</span>
            <?php endif; ?>
          <?php endif; ?>
          
          <?php if ($application_hire_status && $application_hire_status['hire_status'] === 'none'): ?>
            <button type="button" class="action-btn action-btn-hire" onclick="openModal('hireModal')" title="Offer Hire">🎯 Offer Hire</button>
          <?php elseif ($application_hire_status && $application_hire_status['hire_status'] === 'offered'): ?>
            <span class="hire-badge offered">Hire Offer Sent</span>
          <?php elseif ($application_hire_status && $application_hire_status['hire_status'] === 'accepted'): ?>
            <span class="hire-badge accepted">Hired! 🎉</span>
          <?php elseif ($application_hire_status && $application_hire_status['hire_status'] === 'rejected'): ?>
            <span class="hire-badge rejected">Offer Declined</span>
          <?php endif; ?>
          
          <?php if ($application_hire_status && $application_hire_status['hire_status'] === 'none'): ?>
            <button type="button" class="action-btn action-btn-reject" onclick="openModal('rejectModal')" title="Reject Application">✕ Reject</button>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="messages-area">
      <?php if (count($messages) > 0): ?>
        <?php foreach ($messages as $msg): ?>
          <div class="message-group <?php echo ($msg['sender_type'] === 'employer') ? 'own' : 'other'; ?>">
            <div class="message-content">
              <div class="message-bubble">
                <?php echo htmlspecialchars($msg['message']); ?>
                <?php if ($msg['application_id'] > 0 && !empty($msg['job_title'])): ?>
                  <div style="background: rgba(0,0,0,0.08); padding: 0.75rem; margin-top: 0.75rem; border-radius: 6px; font-size: 0.95rem; margin-left: -1.35rem; margin-right: -1.35rem; margin-bottom: -1rem; border-left: 3px solid rgba(0,0,0,0.2);">
                    <strong style="color: rgba(0,0,0,0.7);">📋 About:</strong> <span style="color: rgba(0,0,0,0.75);"><?php echo htmlspecialchars($msg['job_title']); ?></span>
                  </div>
                <?php endif; ?>
              </div>
              <div class="message-time"><?php echo date('g:i A', strtotime($msg['timestamp'])); ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php elseif ($selected_employee_id > 0): ?>
        <div class="empty-state">
          <div class="empty-icon">💬</div>
          <div class="empty-text">No messages yet</div>
          <div class="empty-subtext">Start a conversation with this candidate</div>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <div class="empty-icon">📬</div>
          <div class="empty-text">No Conversations</div>
          <div class="empty-subtext">Select or start a conversation to get started</div>
        </div>
      <?php endif; ?>
    </div>

    <div class="input-area">
      <form method="POST" style="display: flex; flex-direction: column; gap: 1rem; width: 100%;">
        <input type="hidden" name="action" value="send_message">
        <input type="hidden" name="receiver_id" value="<?php echo $selected_employee_id; ?>">
        
        <?php if (!empty($applications_with_candidate)): ?>
          <select name="application_id" id="appSelect" onchange="changeApplication(this.value)" style="padding: 1rem; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-size: 1rem; background: white; cursor: pointer; transition: all 0.2s ease;">
            <option value="0">-- Relate to specific application --</option>
            <?php foreach ($applications_with_candidate as $app): ?>
              <option value="<?php echo $app['application_id']; ?>" <?php echo ($selected_application_id === $app['application_id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($app['job_title']); ?> (<?php echo $app['status']; ?>)
              </option>
            <?php endforeach; ?>
          </select>
        <?php endif; ?>
        
        <div style="display: flex; gap: 1rem; align-items: flex-end;">
          <input type="text" name="message" class="message-input" placeholder="Type your message..." id="messageInput" required style="flex: 1; margin: 0;">
          <button type="submit" class="send-btn" <?php echo ($selected_employee_id > 0) ? '' : 'disabled'; ?>>Send</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Schedule Interview Modal -->
  <div id="scheduleModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3>📅 Schedule Interview</h3>
        <span class="modal-close" onclick="closeModal('scheduleModal')">&times;</span>
      </div>
      <form method="POST">
        <input type="hidden" name="action" value="schedule_interview">
        <input type="hidden" name="application_id" value="<?php echo $selected_application_id; ?>">
        <div class="modal-body">
          <div class="form-group">
            <label>Select Date</label>
            <input type="date" name="scheduled_date" class="modal-input" required min="<?php echo date('Y-m-d'); ?>">
          </div>
          <div class="form-group">
            <label>Select Time</label>
            <input type="time" name="scheduled_time" class="modal-input" required>
          </div>
          <div class="form-group">
            <label>Confirmation Message (optional)</label>
            <textarea name="confirmation_message" class="modal-input" rows="3" placeholder="Please confirm your availability for this scheduled interview."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-cancel" onclick="closeModal('scheduleModal')">Cancel</button>
          <button type="submit" class="btn-submit">Schedule Interview</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Offer Hire Modal -->
  <div id="hireModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3>🎯 Offer Hire</h3>
        <span class="modal-close" onclick="closeModal('hireModal')">&times;</span>
      </div>
      <form method="POST">
        <input type="hidden" name="action" value="offer_hire">
        <input type="hidden" name="application_id" value="<?php echo $selected_application_id; ?>">
        <div class="modal-body">
          <div class="form-group">
            <label>Offer Message</label>
            <textarea name="hire_message" class="modal-input" rows="4" placeholder="Congratulations! We are pleased to offer you the position. Please respond to this offer."></textarea>
          </div>
          <p class="modal-note">This will notify the candidate about the job offer.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-cancel" onclick="closeModal('hireModal')">Cancel</button>
          <button type="submit" class="btn-submit btn-hire">Send Offer</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Reject Application Modal -->
  <div id="rejectModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3>✕ Reject Application</h3>
        <span class="modal-close" onclick="closeModal('rejectModal')">&times;</span>
      </div>
      <form method="POST">
        <input type="hidden" name="action" value="reject_application">
        <input type="hidden" name="application_id" value="<?php echo $selected_application_id; ?>">
        <div class="modal-body">
          <div class="form-group">
            <label>Rejection Message</label>
            <textarea name="reject_message" class="modal-input" rows="3" placeholder="Thank you for your interest. We have decided to move forward with other candidates."></textarea>
          </div>
          <p class="modal-note modal-warning">This action will reject the candidate's application.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-cancel" onclick="closeModal('rejectModal')">Cancel</button>
          <button type="submit" class="btn-submit btn-reject">Reject Application</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- FOOTER -->
<footer class="footer">
  <div class="footer-inner">
    <div class="footer-top">
      <div class="footer-brand">
        <h3>TalentScout AI</h3>
        <p>Smart AI-powered recruitment platform for PESO Nasugbu, Batangas. Connecting employers with qualified local talent.</p>
      </div>
      <div class="footer-col">
        <h4>For Job Seekers</h4>
        <ul>
          <li><a href="../../employees/">Browse Jobs</a></li>
          <li><a href="../../employees/modules/ai-matching/">AI Matching</a></li>
          <li><a href="../../employees/modules/skill-gap-analysis/">Skill Gap Analysis</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>For Employers</h4>
        <ul>
          <li><a href="../../index.php">Home</a></li>
          <li><a href="../post-jobs/">Post Jobs</a></li>
          <li><a href="../employee-finder/">Find Talent</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>PESO Nasugbu</h4>
        <ul>
          <li><a href="#">Contact Us</a></li>
          <li><a href="#">Privacy Policy</a></li>
          <li><a href="#">Terms of Service</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© 2026 TalentScout AI – PESO Nasugbu, Batangas</span>
      <span>Stay Connected with Your Candidates</span>
    </div>
  </div>
</footer>

<script>
  function selectConversation(element) {
    // Remove active class from all conversations
    document.querySelectorAll('.conversation-item').forEach(item => {
      item.classList.remove('active');
    });
    
    // Add active class to selected conversation
    element.classList.add('active');
    
    // Get the name and update chat header
    const name = element.querySelector('.conversation-name').textContent;
    document.querySelector('.chat-title').textContent = name;
    
    // Animate message clear and load
    const messagesArea = document.querySelector('.messages-area');
    messagesArea.style.opacity = '0.5';
    
    setTimeout(() => {
      messagesArea.style.opacity = '1';
    }, 200);
  }

  function sendMessage() {
    const input = document.getElementById('messageInput');
    const messageText = input.value.trim();
    
    if (messageText === '') return;
    
    // Create new message bubble
    const messagesArea = document.querySelector('.messages-area');
    const newMessage = document.createElement('div');
    newMessage.className = 'message-group own';
    
    const now = new Date();
    const timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    
    newMessage.innerHTML = `
      <div class="message-content">
        <div class="message-bubble">${messageText}</div>
        <div class="message-time">${timeStr}</div>
      </div>
    `;
    
    messagesArea.appendChild(newMessage);
    
    // Scroll to bottom
    messagesArea.scrollTop = messagesArea.scrollHeight;
    
    // Clear input
    input.value = '';
    input.focus();
  }

  // Allow Enter key to send message
  document.getElementById('messageInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
      sendMessage();
    }
  });

  // Enhance button feedback
  document.querySelectorAll('.action-btn, .send-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      this.style.transform = 'scale(0.95)';
      setTimeout(() => {
        this.style.transform = '';
      }, 100);
    });
  });

  // Enhance conversation hover effects
  document.querySelectorAll('.conversation-item').forEach(item => {
    item.addEventListener('mouseenter', function() {
      this.style.transform = 'translateX(4px)';
    });
    item.addEventListener('mouseleave', function() {
      this.style.transform = '';
    });
  });

  // Function to select a conversation
  function selectConversation(employeeId) {
    window.location.href = '?employee_id=' + employeeId;
  }

  // Modal functions
  function openModal(modalId) {
    document.getElementById(modalId).classList.add('active');
  }

  function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
  }

  // Close modal when clicking outside
  window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
      event.target.classList.remove('active');
    }
  }

  // Change application
  function changeApplication(appId) {
    const url = new URL(window.location.href);
    if (appId > 0) {
      url.searchParams.set('application_id', appId);
    } else {
      url.searchParams.delete('application_id');
    }
    window.location.href = url.toString();
  }
</script>

</body>
</html>
