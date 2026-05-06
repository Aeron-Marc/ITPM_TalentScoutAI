<?php 
session_start();
require_once('../../../database/db.php');

if (!isset($_SESSION['employer_id'])) {
  header('Location: ../../login.php');
  exit;
}

$employer_status = $_SESSION['employer_status'] ?? 'pending';
$isVerified = $employer_status === 'active';

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
$is_new_conversation = false;

// If employee_id is provided but not in conversations, it's a new conversation
if ($selected_employee_id > 0 && count($conversations) > 0) {
  foreach ($conversations as $conv) {
    if ($conv['employee_id'] == $selected_employee_id) {
      $selected_employee_name = $conv['display_name'];
      break;
    }
  }
}

// If still no name found, get employee directly from database
if ($selected_employee_id > 0 && empty($selected_employee_name)) {
  $empStmt = $conn->prepare("SELECT first_name, last_name FROM employee WHERE employee_id = ?");
  $empStmt->bind_param("i", $selected_employee_id);
  $empStmt->execute();
  $empResult = $empStmt->get_result();
  if ($empRow = $empResult->fetch_assoc()) {
    $selected_employee_name = $empRow['first_name'] . ' ' . $empRow['last_name'];
    $is_new_conversation = true;
  }
  $empStmt->close();
}

// If new conversation, add to conversations list
if ($is_new_conversation && $selected_employee_id > 0) {
  $conversations[] = [
    'employee_id' => $selected_employee_id,
    'display_name' => $selected_employee_name
  ];
}

// Fetch messages for selected conversation (or empty if new conversation)
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
      
      $msg = "📅 Interview scheduled for " . date('M d, Y g:i A', strtotime($scheduled_datetime)) . ". " . ($confirmation_message ? $confirmation_message : "Please confirm your availability.");
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
      $stmt = $conn->prepare("UPDATE application SET status = 'Rejected' WHERE application_id = ?");
      $stmt->bind_param("i", $application_id);
      $stmt->execute();
      $stmt->close();
      
      $msg = "❌ Application Status Update: " . ($reject_message ? $reject_message : "Thank you for your interest. We have decided to move forward with other candidates.");
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
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --mint:        #e8f5ee;
      --mint-mid:    #c8e6d4;
      --mint-deep:   #a8d4b8;
      --sage:        #5a8a68;
      --sage-dark:   #3d6b50;
      --sage-deeper: #2d5040;
      --gold:        #c8a46a;
      --gold-pale:   #f5ead8;
      --gold-light:  #f0ddb8;
      --cream:       #fdfaf5;
      --cream-mid:   #f7f2ea;
      --cream-warm:  #f0ead8;
      --warm-tan:    #e8dfc8;
      --charcoal:    #2c3028;
      --text-mid:    #4a5244;
      --text-soft:   #7a8270;
      --text-pale:   #a8b0a0;
      --white:       #ffffff;
      --shadow-soft: 0 4px 24px rgba(60,80,50,0.08);
      --shadow-med:  0 8px 40px rgba(60,80,50,0.12);
      --shadow-lift: 0 20px 60px rgba(60,80,50,0.16);
      --radius-xl:   28px;
      --radius-lg:   18px;
      --radius-md:   12px;
      --radius-sm:   8px;
      --radius-pill: 999px;
      --ease:        cubic-bezier(0.22, 1, 0.36, 1);
    }

    html { scroll-behavior: smooth; }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: var(--cream);
      color: var(--charcoal);
      min-height: 100vh;
      overflow-x: hidden;
    }

    a { text-decoration: none; color: inherit; }

    /* ══ NAVBAR ══ */
    .navbar {
      position: fixed; top: 0; left: 0; right: 0; z-index: 200;
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 2.5rem; height: 66px;
      background: var(--sage);
      border-bottom: 1px solid rgba(0,0,0,0.1);
      transition: all 0.4s;
      animation: navSlide 0.7s var(--ease) both;
    }

    .navbar.scrolled {
      background: var(--sage);
      border-bottom-color: rgba(0,0,0,0.15);
    }

    @keyframes navSlide {
      from { transform: translateY(-100%); opacity: 0; }
      to   { transform: translateY(0);     opacity: 1; }
    }

    .nav-logo {
      display: flex; align-items: center; gap: 0.6rem;
      font-family: 'Lora', serif; font-weight: 700; font-size: 1.12rem;
      color: #fff;
      transition: color 0.4s;
    }

    .navbar.scrolled .nav-logo { color: #fff; }

    .nav-logo-mark {
      width: 36px; height: 36px;
      background: rgba(255,255,255,0.25);
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 0.7rem; font-weight: 700; color: #fff; letter-spacing: 0.05em;
    }

    .nav-logo em { font-style: italic; color: rgba(255,255,255,0.8); transition: color 0.4s; }
    .navbar.scrolled .nav-logo em { color: rgba(255,255,255,0.8); }

    .nav-links { display: flex; list-style: none; gap: 0.2rem; margin: 0; padding: 0; }

    .nav-links a {
      padding: 0.2rem 0.75rem;
      font-size: 0.84rem; font-weight: 500; color: rgba(255,255,255,0.8);
      transition: color 0.2s, border-bottom 0.2s;
      position: relative;
      padding-bottom: 0.4rem;
    }

    .navbar.scrolled .nav-links a { color: rgba(255,255,255,0.8); }

    .nav-links a:hover { color: #fff; font-weight: 600; }

    .nav-links a.active { color: #fff; font-weight: 600; border-bottom: 2.5px solid #fff; }

    .nav-links a.nav-muted { opacity: 0.5; cursor: not-allowed; }

    .navbar.scrolled .nav-links a:hover { color: #fff; }
    .navbar.scrolled .nav-links a.active { color: #fff; border-bottom-color: #fff; }

    .nav-right { display: flex; align-items: center; gap: 0.65rem; }

    .nav-user { font-size: 0.82rem; color: rgba(255,255,255,0.75); transition: color 0.4s; }
    .navbar.scrolled .nav-user { color: rgba(255,255,255,0.75); }

    .btn-ghost {
      padding: 0.42rem 1.1rem; border-radius: var(--radius-pill);
      border: 1.5px solid rgba(255,255,255,0.3); color: #fff;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 0.83rem; font-weight: 500; background: transparent;
      cursor: pointer; transition: all 0.2s; display: inline-block;
    }

    .navbar.scrolled .btn-ghost { border-color: rgba(255,255,255,0.3); color: #fff; }
    .btn-ghost:hover { background: rgba(255,255,255,0.15); color: #fff; }
    .navbar.scrolled .btn-ghost:hover { background: rgba(255,255,255,0.2); color: #fff; }

    .btn-solid {
      padding: 0.46rem 1.25rem; border-radius: var(--radius-pill);
      background: rgba(255,255,255,0.2);
      color: #fff; font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 0.83rem; font-weight: 700; border: 1.5px solid rgba(255,255,255,0.4);
      cursor: pointer; display: inline-flex; align-items: center; gap: 0.3rem;
      transition: all 0.25s var(--ease);
    }

    .btn-solid:hover { background: rgba(255,255,255,0.3); border-color: rgba(255,255,255,0.5); }

    .hamburger {
      display: none; flex-direction: column; gap: 5px;
      cursor: pointer; padding: 6px; background: none; border: none;
    }

    .hamburger span {
      display: block; width: 22px; height: 2px;
      background: #fff; border-radius: 2px;
      transition: all 0.3s var(--ease);
    }

    .hamburger.open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
    .hamburger.open span:nth-child(2) { opacity: 0; transform: scaleX(0); }
    .hamburger.open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

    .mobile-nav {
      position: fixed; top: 66px; left: 0; right: 0;
      background: rgba(253,250,245,0.97);
      backdrop-filter: blur(24px);
      border-bottom: 1px solid rgba(90,138,104,0.1);
      padding: 1.5rem 2rem; z-index: 190;
      display: flex; flex-direction: column; gap: 0.3rem;
      transform: translateY(-130%); opacity: 0;
      transition: transform 0.4s var(--ease), opacity 0.3s;
    }

    .mobile-nav.open { transform: translateY(0); opacity: 1; }

    .mobile-nav a {
      padding: 0.75rem 1rem; border-radius: var(--radius-md);
      font-size: 0.95rem; font-weight: 500; color: var(--text-mid);
      transition: background 0.2s, color 0.2s;
    }

    .mobile-nav a:hover { background: var(--mint); color: var(--sage-dark); }

    .mobile-nav-actions {
      display: flex; gap: 0.6rem; margin-top: 0.8rem;
      padding-top: 1rem; border-top: 1px solid rgba(90,138,104,0.1);
    }

    /* ══ PAGE CONTAINER ══ */
    .page-container {
      max-width: 100%; width: 100%;
      margin: 0 auto;
      padding: 2.5rem;
      display: grid; grid-template-columns: 350px 1fr;
      gap: 2rem; min-height: calc(100vh - 120px);
      height: 100%; padding-top: calc(66px + 2.5rem);
    }

    /* ══ SIDEBAR ══ */
    .conversations-sidebar {
      background: white; border: 1px solid rgba(90,138,104,0.13);
      border-radius: var(--radius-lg); padding: 1.5rem;
      height: calc(100vh - 180px); display: flex;
      flex-direction: column; box-shadow: var(--shadow-soft);
    }

    .conversations-title {
      font-weight: 700; font-size: 1rem; color: var(--text-dark);
      margin-bottom: 1.25rem; padding-bottom: 1rem;
      border-bottom: 2px solid rgba(90,138,104,0.1);
      text-transform: uppercase; letter-spacing: 1px; flex-shrink: 0;
    }

    .conversation-list {
      display: flex; flex-direction: column; gap: 0.5rem;
      overflow-y: auto; flex: 1; padding-right: 0.5rem;
    }

    .conversation-list::-webkit-scrollbar { width: 6px; }
    .conversation-list::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 3px; }
    .conversation-list::-webkit-scrollbar-thumb { background: #ccc; border-radius: 3px; }
    .conversation-list::-webkit-scrollbar-thumb:hover { background: #aaa; }

    .conversation-item {
      padding: 1.25rem; border-radius: var(--radius-sm);
      cursor: pointer; transition: all 0.25s ease;
      border-left: 4px solid transparent; background: transparent;
      flex-shrink: 0;
    }

    .conversation-item:hover { background: var(--mint); }
    .conversation-item.active {
      background: linear-gradient(135deg, #e8fff5 0%, #f4fffb 100%);
      border-left-color: var(--sage-dark);
    }

    .conversation-name {
      font-weight: 600; font-size: 1rem;
      color: var(--charcoal); margin-bottom: 0.35rem;
    }

    .conversation-preview {
      color: var(--text-light); font-size: 0.9rem;
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
      margin-bottom: 0.4rem;
    }

    .conversation-time { color: var(--text-pale); font-size: 0.85rem; }

    /* ══ CHAT WINDOW ══ */
    .chat-window {
      background: white; border: 1px solid rgba(90,138,104,0.13);
      border-radius: var(--radius-lg);
      display: flex; flex-direction: column;
      overflow: hidden; box-shadow: var(--shadow-soft);
      width: 100%; height: 100%;
    }

    .chat-header {
      border-bottom: 1px solid rgba(90,138,104,0.1);
      padding: 1.5rem 2rem;
      display: flex; flex-wrap: wrap; justify-content: space-between;
      align-items: center; gap: 1rem;
      background: linear-gradient(135deg, #f8fffc 0%, #f0fffb 100%);
    }

    .chat-info { display: flex; flex-direction: column; }

    .chat-title {
      font-weight: 700; font-size: 1.2rem;
      color: var(--charcoal); font-family: 'Lora', serif;
    }

    .chat-status { font-size: 0.9rem; color: var(--text-soft); margin-top: 0.3rem; }

    .chat-actions { display: flex; flex-wrap: wrap; gap: 0.5rem; }

    .action-btn {
      background: white; border: 1px solid rgba(90,138,104,0.2);
      padding: 0.5rem 1rem; border-radius: var(--radius-sm);
      cursor: pointer; font-size: 0.85rem; font-weight: 600;
      color: var(--text-dark); transition: all 0.2s ease;
    }

    .action-btn:hover { background: var(--mint); border-color: var(--sage-dark); color: var(--sage-dark); }

    .action-btn-interview { background: #e3f2fd; border-color: #1976d2; color: #1976d2; }
    .action-btn-interview:hover { background: #bbdefb; }

    .action-btn-hire { background: #e8f5e9; border-color: #388e3c; color: #388e3c; }
    .action-btn-hire:hover { background: #c8e6c9; }

    .action-btn-reject { background: #ffebee; border-color: #d32f2f; color: #d32f2f; }
    .action-btn-reject:hover { background: #ffcdd2; }

    .interview-badge, .hire-badge {
      padding: 0.4rem 0.8rem; border-radius: var(--radius-sm);
      font-size: 0.8rem; font-weight: 600;
    }
    .interview-badge.scheduled, .hire-badge.offered {
      background: #fff3e0; color: #e65100;
    }
    .interview-badge.accepted, .hire-badge.accepted {
      background: #e8f5e9; color: #2e7d32;
    }
    .interview-badge.rejected, .hire-badge.rejected {
      background: #ffebee; color: #c62828;
    }

    /* ══ MESSAGES AREA ══ */
    .messages-area {
      flex: 1 1 auto; padding: 2rem; overflow-y: auto;
      display: flex; flex-direction: column; gap: 1.25rem;
      background: white; width: 100%; min-width: 0;
    }

    .message-group {
      display: flex; gap: 0.75rem;
      animation: slideInUp 0.3s ease-out;
      flex-wrap: nowrap; align-items: flex-end;
      width: 100%; margin-bottom: 0.5rem;
    }

    .message-group.own { justify-content: flex-end; }
    .message-group.other { justify-content: flex-start; }

    .message-bubble {
      padding: 1rem 1.35rem; border-radius: 12px;
      overflow-wrap: break-word; word-break: break-word;
      white-space: normal; line-height: 1.6; font-size: 1rem;
      transition: all 0.2s ease; display: inline-block; min-width: 60px;
    }

    .message-group.other .message-bubble {
      background: var(--cream-mid); color: var(--charcoal);
      border: 1px solid rgba(90,138,104,0.1);
    }

    .message-group.own .message-bubble {
      background: var(--sage); color: white;
      box-shadow: 0 2px 8px rgba(90,138,104,0.15);
    }

    .message-time { font-size: 0.85rem; color: var(--text-pale); margin-top: 0.4rem; }

    .message-content { display: flex; flex-direction: column; max-width: 75%; }

    /* ══ INPUT AREA ══ */
    .input-area {
      border-top: 1px solid rgba(90,138,104,0.1);
      padding: 2rem; background: white;
      display: flex; flex-direction: column; gap: 1rem;
    }

    .message-input {
      flex: 1; padding: 1rem 1.25rem;
      border: 1.5px solid rgba(90,138,104,0.2);
      border-radius: var(--radius-sm);
      font-family: inherit; font-size: 1rem;
      color: var(--charcoal); transition: all 0.2s ease;
      background: white; min-height: 50px; resize: vertical;
    }

    .message-input:focus {
      outline: none; border-color: var(--sage);
      box-shadow: 0 0 0 3px rgba(90,138,104,0.1);
    }

    .message-input::placeholder { color: var(--text-soft); }

    .send-btn {
      background: var(--sage); color: white; border: none;
      padding: 1rem 2rem; border-radius: var(--radius-sm);
      font-weight: 600; cursor: pointer; font-size: 1rem;
      transition: all 0.2s ease;
      display: flex; align-items: center; justify-content: center;
      gap: 0.5rem; min-height: 45px;
    }

    .send-btn:hover { background: var(--sage-dark); box-shadow: 0 4px 12px rgba(90,138,104,0.15); transform: translateY(-1px); }
    .send-btn:active { transform: translateY(0); }

    /* ══ EMPTY STATE ══ */
    .empty-state {
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      height: 100%; color: var(--text-soft);
      text-align: center;
    }

    .empty-icon { font-size: 3rem; margin-bottom: 1rem; opacity: 0.5; }
    .empty-text { font-size: 1rem; margin-bottom: 0.5rem; }
    .empty-subtext { font-size: 0.85rem; opacity: 0.7; }

    /* ══ MODAL ══ */
    .modal {
      display: none; position: fixed;
      top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(0,0,0,0.5); z-index: 1000;
      justify-content: center; align-items: center;
    }
    .modal.active { display: flex; }

    .modal-content {
      background: white; border-radius: var(--radius-lg);
      width: 90%; max-width: 450px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    }

    .modal-header {
      padding: 1.25rem 1.5rem; border-bottom: 1px solid rgba(90,138,104,0.1);
      display: flex; justify-content: space-between; align-items: center;
    }

    .modal-header h3 {
      font-size: 1.1rem; font-weight: 700;
      color: var(--charcoal); margin: 0; font-family: 'Lora', serif;
    }

    .modal-close {
      font-size: 1.5rem; color: var(--text-soft);
      cursor: pointer; background: none; border: none;
      width: 2.2rem; height: 2.2rem;
      display: flex; align-items: center; justify-content: center;
      border-radius: 6px; transition: all 0.2s ease;
    }

    .modal-close:hover { background: #f0f0f0; color: var(--charcoal); }

    .modal-body { padding: 1.5rem; }

    .form-group { margin-bottom: 1rem; }
    .form-group label {
      display: block; font-size: 0.9rem; font-weight: 600;
      color: var(--charcoal); margin-bottom: 0.5rem;
    }

    .modal-input {
      width: 100%; padding: 0.75rem;
      border: 1.5px solid rgba(90,138,104,0.2);
      border-radius: var(--radius-sm); font-size: 0.95rem;
      font-family: inherit; transition: all 0.2s ease;
    }

    .modal-input:focus {
      outline: none; border-color: var(--sage);
      box-shadow: 0 0 0 3px rgba(90,138,104,0.1);
    }

    .modal-note { font-size: 0.85rem; color: var(--text-soft); margin-top: 0.5rem; }
    .modal-warning { color: #d32f2f; }

    .modal-footer {
      padding: 1rem 1.5rem; border-top: 1px solid rgba(90,138,104,0.1);
      display: flex; justify-content: flex-end; gap: 0.75rem;
    }

    .btn-cancel {
      padding: 0.6rem 1.2rem; border: 1px solid rgba(90,138,104,0.2);
      background: white; border-radius: var(--radius-sm);
      font-size: 0.9rem; font-weight: 600;
      color: var(--text-dark); cursor: pointer;
      transition: all 0.2s ease;
    }

    .btn-cancel:hover { background: var(--cream-mid); }

    .btn-submit {
      padding: 0.6rem 1.2rem; border: none;
      background: var(--sage); color: white;
      border-radius: var(--radius-sm); font-size: 0.9rem;
      font-weight: 600; cursor: pointer;
      transition: all 0.2s ease;
    }

    .btn-submit:hover { background: var(--sage-dark); }
    .btn-hire { background: #388e3c; }
    .btn-hire:hover { background: #2e7d32; }
    .btn-reject { background: #d32f2f; }
    .btn-reject:hover { background: #c62828; }

    @keyframes slideInUp {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* ══ FOOTER ══ */
    .footer { background: var(--charcoal); color: rgba(255,255,255,0.5); padding: 4.5rem 2.5rem 2rem; }

    .footer-inner { max-width: 1120px; margin: 0 auto; }

    .footer-top {
      display: grid; grid-template-columns: 2fr 1fr 1fr 1fr;
      gap: 3rem; padding-bottom: 3rem;
      border-bottom: 1px solid rgba(255,255,255,0.07); margin-bottom: 2rem;
    }

    .footer-brand h3 { font-family: 'Lora', serif; font-size: 1.1rem; font-weight: 700; color: #fff; margin-bottom: 0.75rem; }
    .footer-brand p { font-size: 0.82rem; line-height: 1.72; color: rgba(255,255,255,0.4); }

    .footer-col h4 {
      font-size: 0.7rem; font-weight: 700; color: rgba(255,255,255,0.7);
      text-transform: uppercase; letter-spacing: 0.12em; margin-bottom: 1.1rem;
    }

    .footer-col ul { list-style: none; display: flex; flex-direction: column; gap: 0.55rem; }
    .footer-col ul a { font-size: 0.83rem; color: rgba(255,255,255,0.38); transition: color 0.2s; }
    .footer-col ul a:hover { color: var(--mint-deep); }

    .footer-bottom {
      display: flex; justify-content: space-between; align-items: center;
      font-size: 0.77rem; flex-wrap: wrap; gap: 0.5rem;
    }

    /* ══ RESPONSIVE ══ */
    @media (max-width: 1024px) {
      .page-container { grid-template-columns: 260px 1fr; gap: 1.5rem; padding: 1.5rem; }
      .message-content { max-width: 80%; }
    }

    @media (max-width: 860px) {
      .footer-top { grid-template-columns: 1fr 1fr; }
      .nav-links { display: none; }
      .hamburger { display: flex; }
    }

    @media (max-width: 768px) {
      .page-container { grid-template-columns: 1fr; gap: 1rem; padding: 1rem; }
      .conversations-sidebar { position: relative; top: 0; }
      .message-content { max-width: 90%; }
      .footer-top { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

  <!-- ══ NAVBAR ══ -->
  <nav class="navbar" id="navbar">
    <a href="../../index.php" class="nav-logo">
      <div class="nav-logo-mark">TS</div>
      <span>Talent<em>Scout</em> AI</span>
    </a>
    <ul class="nav-links">
      <li><a href="../../index.php">Home</a></li>
      <?php if ($isVerified): ?>
        <li><a href="../post-jobs/">Post Jobs</a></li>
      <?php else: ?>
        <li><a href="#" class="nav-muted" onclick="window.location.href='../../index.php'; return false;">Post Jobs</a></li>
      <?php endif; ?>
      <li><a href="../employee-finder/">Find Talent</a></li>
      <?php if ($isVerified): ?>
        <li><a href="../applicant-tracking/">Hiring Pipeline</a></li>
      <?php else: ?>
        <li><a href="#" class="nav-muted" onclick="window.location.href='../../index.php'; return false;">Hiring Pipeline</a></li>
      <?php endif; ?>
      <li><a href="./" class="active">Messages</a></li>
    </ul>
    <div class="nav-right">
      <?php if (isset($_SESSION['employer_id'])): ?>
        <span class="nav-user">Welcome, <?php echo htmlspecialchars($_SESSION['employer_name'] ?? 'Employer'); ?></span>
        <a href="../../logout.php" class="btn-ghost">Logout</a>
      <?php else: ?>
        <a href="../../login.php" class="btn-ghost">Login</a>
        <a href="../../signup.php" class="btn-solid">Get Started →</a>
      <?php endif; ?>
      <button class="hamburger" id="hamburger" aria-label="Menu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </nav>

  <!-- Mobile Nav -->
  <div class="mobile-nav" id="mobileNav">
    <a href="../../index.php">🏠 Home</a>
    <a href="../post-jobs/">📋 Post Jobs</a>
    <a href="../employee-finder/">🔍 Find Talent</a>
    <a href="../applicant-tracking/">📊 Hiring Pipeline</a>
    <a href="./">💬 Messages</a>
    <div class="mobile-nav-actions">
      <?php if (isset($_SESSION['employer_id'])): ?>
        <a href="../../logout.php" class="btn-ghost">Logout</a>
      <?php else: ?>
        <a href="../../login.php" class="btn-ghost">Login</a>
        <a href="../../signup.php" class="btn-solid">Get Started →</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="page-container">

    <!-- Conversations Sidebar -->
    <div class="conversations-sidebar">
      <div class="conversations-title">Conversations (<?php echo count($conversations); ?>)</div>
      <div class="conversation-list">
        <?php if (count($conversations) > 0): ?>
          <?php foreach ($conversations as $conv): ?>
            <div class="conversation-item <?php echo ($conv['employee_id'] == $selected_employee_id) ? 'active' : ''; ?>" onclick="selectConversation(<?php echo $conv['employee_id']; ?>)">
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
              <button type="button" class="action-btn action-btn-hire" onclick="openModal('hireModal')" title="Offer Hire">🎉 Offer Hire</button>
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
            <select name="application_id" id="appSelect" onchange="changeApplication(this.value)" style="padding: 1rem; border: 1.5px solid rgba(90,138,104,0.2); border-radius: var(--radius-sm); font-size: 1rem; background: white; cursor: pointer; transition: all 0.2s ease;">
              <option value="0">-- Related to specific application --</option>
              <?php foreach ($applications_with_candidate as $app): ?>
                <option value="<?php echo $app['application_id']; ?>" <?php echo ($selected_application_id == $app['application_id']) ? 'selected' : ''; ?>>
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
        <h3>🎉 Offer Hire</h3>
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

  <!-- ══ FOOTER ══ -->
  <footer class="footer">
    <div class="footer-inner">
      <div class="footer-top">
        <div class="footer-brand">
          <h3>🌿 TalentScout AI</h3>
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
    // Hamburger menu
    const ham = document.getElementById('hamburger');
    const mNav = document.getElementById('mobileNav');

    if (ham) {
      ham.addEventListener('click', () => {
        ham.classList.toggle('open');
        mNav.classList.toggle('open');
      });

      document.addEventListener('click', (e) => {
        if (!ham.contains(e.target) && !mNav.contains(e.target)) {
          ham.classList.remove('open');
          mNav.classList.remove('open');
        }
      });
    }

    // Navbar scroll
    const navbar = document.getElementById('navbar');
    window.addEventListener('scroll', () => {
      if (window.scrollY > 60) {
        navbar.classList.add('scrolled');
        navbar.style.boxShadow = '0 4px 24px rgba(60,80,50,0.1)';
      } else {
        navbar.classList.remove('scrolled');
        navbar.style.boxShadow = 'none';
      }
    }, { passive: true });

    // Select conversation
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

    // Send message with Enter key
    const messageInput = document.getElementById('messageInput');
    if (messageInput) {
      messageInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
          e.preventDefault();
          this.closest('form').submit();
        }
      });
    }

    // Scroll messages to bottom
    const messagesArea = document.querySelector('.messages-area');
    if (messagesArea) {
      messagesArea.scrollTop = messagesArea.scrollHeight;
    }
  </script>

</body>
</html>
