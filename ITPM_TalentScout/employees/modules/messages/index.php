<?php
session_start();
require_once('../../../database/db.php');

if (!isset($_SESSION['employee_id'])) {
  header('Location: ../../login.php');
  exit;
}

$conn = getConnection();
$employee_id = (int)$_SESSION['employee_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
  $receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;
  $message = trim($_POST['message'] ?? '');
  $application_id = isset($_POST['application_id']) ? intval($_POST['application_id']) : 0;

  if ($receiver_id > 0 && !empty($message)) {
    $stmt = $conn->prepare("INSERT INTO message (sender_id, sender_type, receiver_id, receiver_type, message, application_id, timestamp) VALUES (?, 'employee', ?, 'employer', ?, ?, NOW())");
    $stmt->bind_param("iisi", $employee_id, $receiver_id, $message, $application_id);
    if ($stmt->execute()) {
      $stmt->close();
      header('Location: ' . $_SERVER['REQUEST_URI']);
      exit;
    }
    $stmt->close();
  }
}

$applications = [];
$stmt = $conn->prepare("SELECT a.application_id, a.job_post_id, j.title as job_title, e.company_name, a.status
FROM application a
JOIN job_post j ON a.job_post_id = j.job_post_id
JOIN employer e ON j.employer_id = e.employer_id
WHERE a.employee_id = ?
ORDER BY a.application_date DESC");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $applications[] = $row;
}
$stmt->close();

$conversations = [];
$stmt = $conn->prepare("SELECT DISTINCT e.employer_id, e.company_name,
  (SELECT COUNT(*) FROM message m WHERE 
    ((m.sender_id = e.employer_id AND m.sender_type = 'employer' AND m.receiver_id = ?) OR
     (m.sender_id = ? AND m.sender_type = 'employee' AND m.receiver_id = e.employer_id)) AND
    m.timestamp > DATE_SUB(NOW(), INTERVAL 30 DAY)
  ) as unread_count
FROM employer e
JOIN message m ON (
  (m.sender_id = e.employer_id AND m.sender_type = 'employer' AND m.receiver_id = ?) OR
  (m.sender_id = ? AND m.sender_type = 'employee' AND m.receiver_id = e.employer_id)
)
ORDER BY m.timestamp DESC");
$stmt->bind_param("iiii", $employee_id, $employee_id, $employee_id, $employee_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  if (!isset($conversations[$row['employer_id']])) {
    $conversations[$row['employer_id']] = $row;
  }
}
$stmt->close();

$selected_employer_id = isset($_GET['employer_id']) ? intval($_GET['employer_id']) : (count($conversations) > 0 ? array_key_first($conversations) : 0);
$selected_employer_name = '';
if ($selected_employer_id > 0 && isset($conversations[$selected_employer_id])) {
  $selected_employer_name = $conversations[$selected_employer_id]['company_name'];
}

$messages = [];
if ($selected_employer_id > 0) {
  $stmt = $conn->prepare("SELECT m.*, a.job_post_id, j.title as job_title, e.company_name
  FROM message m
  LEFT JOIN application a ON m.application_id = a.application_id
  LEFT JOIN job_post j ON a.job_post_id = j.job_post_id
  LEFT JOIN employer e ON j.employer_id = e.employer_id
  WHERE (
    (m.sender_id = ? AND m.sender_type = 'employee' AND m.receiver_id = ?) OR
    (m.sender_id = ? AND m.sender_type = 'employer' AND m.receiver_id = ?)
  )
  ORDER BY m.timestamp ASC");
  $stmt->bind_param("iiii", $employee_id, $selected_employer_id, $selected_employer_id, $employee_id);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
  }
  $stmt->close();
}

closeConnection($conn);
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Messages — TalentScout AI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900;1,700&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
      *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

      :root {
        --sand:        #f5f0e8;
        --sand-dark:   #ece5d5;
        --sage:        #6b8f71;
        --sage-light:  #9ab89f;
        --sage-pale:   #d4e6d6;
        --sage-deep:   #4a6b50;
        --stone:       #8a8070;
        --stone-light: #c4b9a8;
        --cream:       #faf8f3;
        --charcoal:    #2a2a22;
        --warm-mid:    #5a5448;
        --warm-light:  #9a9288;
        --gold:        #c8a96e;
        --gold-pale:   #f0e4c8;
        --white-t:     rgba(255,255,255,0.92);
        --radius-xl:   24px;
        --radius-lg:   16px;
        --radius-md:   10px;
        --radius-pill: 999px;
        --ease-out:    cubic-bezier(0.22, 1, 0.36, 1);
        --nav-height:  64px;
      }

      html { scroll-behavior: smooth; }

      body {
        font-family: 'DM Sans', sans-serif;
        background: var(--cream);
        color: var(--charcoal);
        min-height: 100vh;
        overflow-x: hidden;
      }

      a { text-decoration: none; color: inherit; }

      body::before {
        content: '';
        position: fixed;
        inset: 0;
        pointer-events: none;
        z-index: 9999;
        opacity: 0.03;
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)'/%3E%3C/svg%3E");
      }

      /* ── NAVBAR ── */
      .navbar {
        position: fixed;
        top: 0; left: 0; right: 0;
        z-index: 100;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 3rem;
        height: 64px;
        background: rgba(250, 248, 243, 0.88);
        backdrop-filter: blur(20px);
        border-bottom: 1px solid rgba(139, 128, 112, 0.12);
        animation: slideDown 0.6s var(--ease-out) both;
      }

      @keyframes slideDown {
        from { transform: translateY(-100%); opacity: 0; }
        to   { transform: translateY(0); opacity: 1; }
      }

      .nav-logo {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        font-family: 'Playfair Display', serif;
        font-weight: 700;
        font-size: 1.15rem;
        color: var(--charcoal);
        letter-spacing: -0.01em;
      }

      .nav-logo-mark {
        width: 34px; height: 34px;
        background: var(--sage-deep);
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.72rem;
        font-weight: 600;
        color: #fff;
        letter-spacing: 0.04em;
      }

      .nav-logo em { font-style: italic; color: var(--sage); }

      .nav-links {
        display: flex;
        list-style: none;
        gap: 0.15rem;
      }

      .nav-links a {
        padding: 0.38rem 0.85rem;
        border-radius: var(--radius-pill);
        font-size: 0.84rem;
        font-weight: 400;
        color: var(--warm-mid);
        transition: background 0.2s, color 0.2s;
        letter-spacing: 0.01em;
      }

      .nav-links a:hover, .nav-links a.active {
        background: var(--sage-pale);
        color: var(--sage-deep);
      }

      .nav-right {
        display: flex; align-items: center; gap: 0.7rem;
      }

      .nav-user {
        font-size: 0.83rem;
        color: var(--warm-mid);
      }

      .btn-nav-ghost {
        padding: 0.4rem 1rem;
        border-radius: var(--radius-pill);
        border: 1px solid var(--stone-light);
        color: var(--warm-mid);
        font-family: 'DM Sans', sans-serif;
        font-size: 0.83rem;
        font-weight: 500;
        background: transparent;
        cursor: pointer;
        transition: border-color 0.2s, background 0.2s;
      }

      .btn-nav-ghost:hover { background: var(--sand); border-color: var(--stone); }

      .btn-nav-solid {
        padding: 0.44rem 1.2rem;
        border-radius: var(--radius-pill);
        background: var(--sage-deep);
        color: #fff;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.83rem;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: background 0.2s, transform 0.15s;
        display: inline-flex; align-items: center; gap: 0.35rem;
      }

      .btn-nav-solid:hover { background: var(--sage); transform: translateY(-1px); }

      /* ── PAGE HEADER ── */
      .page-header {
        padding-top: calc(var(--nav-height) + 3rem);
        padding-bottom: 2rem;
        background: var(--sand);
        position: relative;
        overflow: hidden;
      }

      .page-header::before {
        content: '';
        position: absolute;
        top: -60px; right: -60px;
        width: 240px; height: 240px;
        border-radius: 50%;
        background: radial-gradient(circle, var(--sage-pale) 0%, transparent 70%);
        pointer-events: none;
      }

      .page-header-inner {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 2.5rem;
        position: relative;
        z-index: 1;
      }

      .breadcrumb {
        font-size: 0.78rem;
        color: var(--warm-light);
        margin-bottom: 1rem;
      }

      .breadcrumb a {
        color: var(--sage);
        transition: color 0.15s;
      }

      .breadcrumb a:hover { color: var(--sage-deep); }

      .page-header h1 {
        font-family: 'Playfair Display', serif;
        font-size: clamp(1.8rem, 3vw, 2.4rem);
        font-weight: 900;
        color: var(--charcoal);
        letter-spacing: -0.025em;
        margin-bottom: 0.6rem;
      }

      .page-header p {
        font-size: 0.92rem;
        color: var(--warm-mid);
        line-height: 1.7;
        max-width: 600px;
      }

      /* ── MESSAGES LAYOUT ── */
      .messages-layout {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 2.5rem 4rem;
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 0;
        height: calc(100vh - 200px);
        min-height: 500px;
        border-radius: var(--radius-xl);
        overflow: hidden;
        border: 1px solid var(--sand-dark);
        background: #fff;
        box-shadow: 0 4px 24px rgba(42,42,34,0.06);
      }

      /* ── CONVERSATIONS PANEL ── */
      .conversations-panel {
        background: var(--sand);
        border-right: 1px solid var(--sand-dark);
        display: flex;
        flex-direction: column;
        overflow: hidden;
      }

      .conversations-header {
        padding: 1.25rem 1.4rem;
        border-bottom: 1px solid var(--sand-dark);
        background: var(--sand);
      }

      .conversations-header h2 {
        font-family: 'Playfair Display', serif;
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--charcoal);
        display: flex;
        align-items: center;
        gap: 0.5rem;
      }

      .conversations-header .conv-count {
        font-family: 'DM Sans', sans-serif;
        font-size: 0.75rem;
        font-weight: 600;
        background: var(--sage-pale);
        color: var(--sage-deep);
        padding: 0.15rem 0.55rem;
        border-radius: var(--radius-pill);
      }

      .conversations-list {
        flex: 1;
        overflow-y: auto;
      }

      .conversations-list::-webkit-scrollbar {
        width: 4px;
      }

      .conversations-list::-webkit-scrollbar-track {
        background: transparent;
      }

      .conversations-list::-webkit-scrollbar-thumb {
        background: var(--stone-light);
        border-radius: 4px;
      }

      .conversation-item {
        padding: 1rem 1.4rem;
        border-bottom: 1px solid rgba(139,128,112,0.08);
        cursor: pointer;
        transition: background 0.2s;
        display: block;
      }

      .conversation-item:hover {
        background: var(--cream);
      }

      .conversation-item.active {
        background: #fff;
        border-left: 3px solid var(--sage-deep);
        padding-left: calc(1.4rem - 3px);
      }

      .conv-item-top {
        display: flex;
        align-items: center;
        gap: 0.75rem;
      }

      .conv-avatar {
        width: 40px; height: 40px;
        background: var(--sage-pale);
        color: var(--sage-deep);
        border-radius: var(--radius-md);
        display: flex; align-items: center; justify-content: center;
        font-size: 0.85rem;
        font-weight: 700;
        flex-shrink: 0;
        font-family: 'Playfair Display', serif;
      }

      .conv-item-info {
        flex: 1;
        min-width: 0;
      }

      .conv-item-name {
        font-weight: 600;
        font-size: 0.88rem;
        color: var(--charcoal);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }

      .conv-item-preview {
        font-size: 0.78rem;
        color: var(--warm-light);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-top: 0.15rem;
      }

      /* ── CHAT PANEL ── */
      .chat-panel {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: var(--cream);
      }

      .chat-header {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid var(--sand-dark);
        background: #fff;
        display: flex;
        align-items: center;
        gap: 0.75rem;
      }

      .chat-header-avatar {
        width: 36px; height: 36px;
        background: var(--sage-pale);
        color: var(--sage-deep);
        border-radius: var(--radius-md);
        display: flex; align-items: center; justify-content: center;
        font-size: 0.8rem;
        font-weight: 700;
        font-family: 'Playfair Display', serif;
      }

      .chat-header-info h3 {
        font-family: 'Playfair Display', serif;
        font-size: 1rem;
        font-weight: 700;
        color: var(--charcoal);
      }

      .chat-header-info span {
        font-size: 0.75rem;
        color: var(--warm-light);
      }

      .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
      }

      .chat-messages::-webkit-scrollbar {
        width: 4px;
      }

      .chat-messages::-webkit-scrollbar-track {
        background: transparent;
      }

      .chat-messages::-webkit-scrollbar-thumb {
        background: var(--stone-light);
        border-radius: 4px;
      }

      .message {
        display: flex;
      }

      .message.sent {
        justify-content: flex-end;
      }

      .message.received {
        justify-content: flex-start;
      }

      .message-bubble {
        max-width: 65%;
        padding: 0.75rem 1rem;
        border-radius: var(--radius-lg);
        word-wrap: break-word;
        line-height: 1.5;
        font-size: 0.88rem;
      }

      .message.sent .message-bubble {
        background: var(--sage-deep);
        color: #fff;
        border-radius: var(--radius-lg) 4px var(--radius-lg) var(--radius-lg);
      }

      .message.received .message-bubble {
        background: #fff;
        color: var(--charcoal);
        border: 1px solid var(--sand-dark);
        border-radius: 4px var(--radius-lg) var(--radius-lg) var(--radius-lg);
      }

      .message-application-ref {
        background: var(--sand);
        border-left: 3px solid var(--sage);
        padding: 0.5rem 0.75rem;
        border-radius: 0 var(--radius-md) var(--radius-md) 0;
        font-size: 0.78rem;
        margin-top: 0.5rem;
        color: var(--warm-mid);
      }

      .message.received .message-application-ref {
        background: var(--sand);
      }

      .message.sent .message-application-ref {
        background: rgba(255,255,255,0.15);
        border-left-color: rgba(255,255,255,0.4);
        color: rgba(255,255,255,0.8);
      }

      .message-time {
        font-size: 0.7rem;
        color: var(--warm-light);
        margin-top: 0.25rem;
      }

      .message.sent .message-time {
        text-align: right;
      }

      /* ── MESSAGE FORM ── */
      .message-form {
        padding: 1.25rem 1.5rem;
        border-top: 1px solid var(--sand-dark);
        background: #fff;
      }

      .message-form-top {
        display: flex;
        gap: 0.75rem;
        align-items: flex-end;
      }

      .message-form-main {
        flex: 1;
      }

      .message-form-row {
        display: flex;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
      }

      .msg-input {
        font-family: 'DM Sans', sans-serif;
        font-size: 0.88rem;
        padding: 0.65rem 1rem;
        border-radius: var(--radius-lg);
        border: 1.5px solid var(--sand-dark);
        background: var(--cream);
        color: var(--charcoal);
        transition: border-color 0.2s, box-shadow 0.2s;
        outline: none;
        width: 100%;
        resize: none;
        min-height: 48px;
        max-height: 120px;
        line-height: 1.5;
      }

      .msg-input:focus {
        border-color: var(--sage-light);
        box-shadow: 0 0 0 3px var(--sage-pale);
        background: #fff;
      }

      .msg-input::placeholder { color: var(--warm-light); }

      .msg-select {
        font-family: 'DM Sans', sans-serif;
        font-size: 0.82rem;
        padding: 0.45rem 0.75rem;
        border-radius: var(--radius-pill);
        border: 1.5px solid var(--sand-dark);
        background: var(--cream);
        color: var(--warm-mid);
        outline: none;
        max-width: 280px;
      }

      .msg-select:focus {
        border-color: var(--sage-light);
        box-shadow: 0 0 0 3px var(--sage-pale);
      }

      .btn-send {
        padding: 0.65rem 1.5rem;
        background: var(--sage-deep);
        color: #fff;
        border-radius: var(--radius-pill);
        font-family: 'DM Sans', sans-serif;
        font-size: 0.86rem;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: background 0.2s, transform 0.15s;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        white-space: nowrap;
        box-shadow: 0 4px 14px rgba(74,107,80,0.28);
      }

      .btn-send:hover {
        background: var(--sage);
        transform: translateY(-1px);
      }

      /* ── EMPTY STATES ── */
      .empty-state {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: var(--warm-light);
        text-align: center;
        padding: 2rem;
      }

      .empty-state-icon {
        font-size: 2.5rem;
        margin-bottom: 0.75rem;
      }

      .empty-state h3 {
        font-family: 'Playfair Display', serif;
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--warm-mid);
        margin-bottom: 0.3rem;
      }

      .empty-state p {
        font-size: 0.85rem;
      }

      .no-conversations {
        padding: 2rem 1.4rem;
        text-align: center;
        color: var(--warm-light);
      }

      .no-conversations p {
        font-size: 0.85rem;
      }

      /* ── FOOTER ── */
      .footer {
        background: #1e1e18;
        color: rgba(255,255,255,0.5);
        padding: 4rem 2rem 2rem;
      }

      .footer-inner { max-width: 1080px; margin: 0 auto; }

      .footer-top {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr;
        gap: 2.5rem;
        padding-bottom: 2.5rem;
        border-bottom: 1px solid rgba(255,255,255,0.07);
        margin-bottom: 1.8rem;
      }

      .footer-brand h3 {
        font-family: 'Playfair Display', serif;
        font-size: 1.1rem;
        font-weight: 700;
        color: #fff;
        margin-bottom: 0.7rem;
      }

      .footer-brand p {
        font-size: 0.81rem;
        line-height: 1.68;
        color: rgba(255,255,255,0.42);
      }

      .footer-col h4 {
        font-size: 0.72rem;
        font-weight: 700;
        color: rgba(255,255,255,0.7);
        text-transform: uppercase;
        letter-spacing: 0.1em;
        margin-bottom: 1rem;
      }

      .footer-col ul { list-style: none; display: flex; flex-direction: column; gap: 0.5rem; }

      .footer-col ul a {
        font-size: 0.82rem;
        color: rgba(255,255,255,0.4);
        transition: color 0.15s;
      }

      .footer-col ul a:hover { color: var(--sage-light); }

      .footer-bottom {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.77rem;
        flex-wrap: wrap;
        gap: 0.5rem;
      }

      /* ── SCROLL REVEAL ── */
      .reveal {
        opacity: 0;
        transform: translateY(28px);
        transition: opacity 0.7s var(--ease-out), transform 0.7s var(--ease-out);
      }

      .reveal.visible {
        opacity: 1;
        transform: translateY(0);
      }

      /* ── RESPONSIVE ── */
      @media (max-width: 960px) {
        .messages-layout {
          grid-template-columns: 1fr;
          margin: 0 1rem;
          padding: 0;
          height: calc(100vh - 180px);
        }

        .conversations-panel {
          display: none;
        }

        .conversations-panel.mobile-show {
          display: flex;
          position: absolute;
          inset: 0;
          z-index: 10;
          border-radius: var(--radius-xl);
        }

        .message-bubble {
          max-width: 85%;
        }

        .message-form-row {
          flex-direction: column;
        }

        .msg-select {
          max-width: 100%;
        }
      }

      @media (max-width: 600px) {
        .navbar { padding: 0 1.2rem; }
        .nav-links { display: none; }

        .page-header-inner { padding: 0 1.2rem; }

        .messages-layout {
          margin: 0;
          border-radius: 0;
          border-left: none;
          border-right: none;
        }

        .chat-messages {
          padding: 1rem;
        }

        .message-form {
          padding: 1rem;
        }

        .footer-top {
          grid-template-columns: 1fr;
        }

        .footer-bottom {
          flex-direction: column;
          text-align: center;
        }
      }
    </style>
  </head>
  <body>
    <!-- NAVBAR -->
    <nav class="navbar">
      <a href="../../index.php" class="nav-logo">
        <div class="nav-logo-mark">TS</div>
        <span>Talent<em>Scout</em> AI</span>
      </a>
      <ul class="nav-links">
        <li><a href="../../index.php">Home</a></li>
        <li><a href="../job-postings/">Browse Jobs</a></li>
        <li><a href="../ai-matching/">AI Matching</a></li>
        <li><a href="../resume-builder/">Resume Builder</a></li>
        <li><a href="../skill-gap-analysis/">Skills</a></li>
        <li><a href="../applicant-tracking/">Applications</a></li>
        <li><a href="./" class="active">Messages</a></li>
      </ul>
      <div class="nav-right">
        <?php if (isset($_SESSION['employee_id'])): ?>
          <span class="nav-user">Welcome, <?php echo htmlspecialchars($_SESSION['employee_name'] ?? 'User'); ?></span>
          <a href="../../logout.php" class="btn-nav-ghost">Logout</a>
        <?php else: ?>
          <a href="../../login.php" class="btn-nav-ghost">Login</a>
          <a href="../../signup.php" class="btn-nav-solid">Get Started →</a>
        <?php endif; ?>
      </div>
    </nav>

    <!-- PAGE HEADER -->
    <div class="page-header">
      <div class="page-header-inner">
        <div class="breadcrumb">
          <a href="../../index.php">Home</a> / <a href="../index.php">Tools</a> / Messages
        </div>
        <h1>💬 Messages</h1>
        <p>
          Chat directly with employers about your applications. Stay updated and ask questions in real time.
        </p>
      </div>
    </div>

    <!-- MESSAGES -->
    <div class="messages-layout reveal">
      <!-- CONVERSATIONS PANEL -->
      <aside class="conversations-panel">
        <div class="conversations-header">
          <h2>Conversations <span class="conv-count"><?php echo count($conversations); ?></span></h2>
        </div>
        <div class="conversations-list">
          <?php if (empty($conversations)): ?>
            <div class="no-conversations">
              <p>No conversations yet</p>
            </div>
          <?php else: ?>
            <?php foreach ($conversations as $conv): ?>
              <?php
              $conv_initials = strtoupper(substr($conv['company_name'], 0, 1));
              ?>
              <a href="?employer_id=<?php echo $conv['employer_id']; ?>" class="conversation-item <?php echo $selected_employer_id === $conv['employer_id'] ? 'active' : ''; ?>">
                <div class="conv-item-top">
                  <div class="conv-avatar"><?php echo htmlspecialchars($conv_initials); ?></div>
                  <div class="conv-item-info">
                    <div class="conv-item-name"><?php echo htmlspecialchars($conv['company_name']); ?></div>
                    <div class="conv-item-preview">Tap to view conversation</div>
                  </div>
                </div>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </aside>

      <!-- CHAT PANEL -->
      <div class="chat-panel">
        <?php if ($selected_employer_id > 0): ?>
          <div class="chat-header">
            <div class="chat-header-avatar"><?php echo strtoupper(substr($selected_employer_name, 0, 1)); ?></div>
            <div class="chat-header-info">
              <h3><?php echo htmlspecialchars($selected_employer_name); ?></h3>
              <span>Employer</span>
            </div>
          </div>

          <div class="chat-messages" id="chatMessages">
            <?php if (empty($messages)): ?>
              <div class="empty-state">
                <div>
                  <div class="empty-state-icon">💬</div>
                  <h3>No messages yet</h3>
                  <p>Start a conversation about your application</p>
                </div>
              </div>
            <?php else: ?>
              <?php foreach ($messages as $msg): ?>
                <div class="message <?php echo $msg['sender_type'] === 'employee' ? 'sent' : 'received'; ?>">
                  <div>
                    <div class="message-bubble">
                      <?php echo htmlspecialchars($msg['message']); ?>
                      <?php if ($msg['application_id'] > 0 && !empty($msg['job_title'])): ?>
                        <div class="message-application-ref">
                          📎 <?php echo htmlspecialchars($msg['job_title']); ?>
                        </div>
                      <?php endif; ?>
                    </div>
                    <div class="message-time"><?php echo date('M d, h:i A', strtotime($msg['timestamp'])); ?></div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <div class="message-form">
            <form method="POST" id="messageForm">
              <input type="hidden" name="action" value="send_message">
              <input type="hidden" name="receiver_id" value="<?php echo $selected_employer_id; ?>">

              <div class="message-form-row">
                <select name="application_id" id="application_id" class="msg-select">
                  <option value="0">-- No specific application --</option>
                  <?php foreach ($applications as $app): ?>
                    <option value="<?php echo $app['application_id']; ?>">
                      <?php echo htmlspecialchars($app['job_title']); ?> — <?php echo htmlspecialchars($app['company_name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="message-form-top">
                <div class="message-form-main">
                  <textarea name="message" id="messageInput" class="msg-input" placeholder="Type your message..." required rows="1"></textarea>
                </div>
                <button type="submit" class="btn-send">
                  Send
                  <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 2L11 13"/><path d="M22 2L15 22L11 13L2 9L22 2Z"/></svg>
                </button>
              </div>
            </form>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <div>
              <div class="empty-state-icon">💬</div>
              <h3>Select a conversation</h3>
              <p>Choose an employer from the left to start messaging</p>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- FOOTER -->
    <footer class="footer">
      <div class="footer-inner">
        <div class="footer-top">
          <div class="footer-brand">
            <h3>🌿 TalentScout AI</h3>
            <p>Smart AI-powered recruitment platform for PESO Nasugbu, Batangas. Connecting local talent with local opportunities through fair, intelligent hiring.</p>
          </div>
          <div class="footer-col">
            <h4>Features</h4>
            <ul>
              <li><a href="../job-postings/">Job Postings</a></li>
              <li><a href="../ai-matching/">AI Matching</a></li>
              <li><a href="../skill-gap-analysis/">Skill Gap Analysis</a></li>
            </ul>
          </div>
          <div class="footer-col">
            <h4>Job Seeker</h4>
            <ul>
              <li><a href="../applicant-tracking/">Applicant Tracking</a></li>
              <li><a href="../messages/">Messages</a></li>
              <li><a href="../">All Employee Tools</a></li>
            </ul>
          </div>
          <div class="footer-col">
            <h4>PESO Nasugbu</h4>
            <ul>
              <li><a href="#">Nasugbu, Batangas</a></li>
              <li><a href="#">About PESO</a></li>
              <li><a href="#">Contact Us</a></li>
            </ul>
          </div>
        </div>
        <div class="footer-bottom">
          <span>&copy; 2026 TalentScout AI — PESO Nasugbu, Batangas</span>
          <span>Built for Local Employment &amp; Community Growth</span>
        </div>
      </div>
    </footer>

    <script>
      const reveals = document.querySelectorAll('.reveal');
      const io = new IntersectionObserver((entries) => {
        entries.forEach(e => {
          if (e.isIntersecting) {
            e.target.classList.add('visible');
            io.unobserve(e.target);
          }
        });
      }, { threshold: 0.12 });
      reveals.forEach(el => io.observe(el));

      // Auto-scroll to bottom of chat
      const chatMessages = document.getElementById('chatMessages');
      if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
      }

      // Auto-resize textarea
      const messageInput = document.getElementById('messageInput');
      if (messageInput) {
        messageInput.addEventListener('input', function() {
          this.style.height = 'auto';
          this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        });
      }
    </script>
    <script src="../../employee-auth.js"></script>
  </body>
</html>
