<?php
session_start();
require_once('../../../database/db.php');

// Check if employee is logged in
if (!isset($_SESSION['employee_id'])) {
  header('Location: ../../login.php');
  exit;
}

$conn = getConnection();
$employee_id = (int)$_SESSION['employee_id'];

// Handle new message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
  $receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;
  $message = trim($_POST['message'] ?? '');
  $application_id = isset($_POST['application_id']) ? intval($_POST['application_id']) : 0;

  if ($receiver_id > 0 && !empty($message)) {
    $stmt = $conn->prepare("INSERT INTO message (sender_id, sender_type, receiver_id, receiver_type, message, application_id, timestamp) VALUES (?, 'employee', ?, 'employer', ?, ?, NOW())");
    $stmt->bind_param("iisi", $employee_id, $receiver_id, $message, $application_id);
    if ($stmt->execute()) {
      $stmt->close();
      // Redirect to clear form
      header('Location: ' . $_SERVER['REQUEST_URI']);
      exit;
    }
    $stmt->close();
  }
}

// Fetch all applications for this employee
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

// Fetch all conversations (unique employers who have sent/received messages)
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

// Get selected conversation
$selected_employer_id = isset($_GET['employer_id']) ? intval($_GET['employer_id']) : (count($conversations) > 0 ? array_key_first($conversations) : 0);
$selected_employer_name = '';
if ($selected_employer_id > 0 && isset($conversations[$selected_employer_id])) {
  $selected_employer_name = $conversations[$selected_employer_id]['company_name'];
}

// Fetch messages for selected conversation
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
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Messages — TalentScout AI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../../styles/global.css">
  <link rel="stylesheet" href="../../../styles/page-layout.css">
  <link rel="stylesheet" href="../../navbar.css">
  <style>
    * { font-family: 'Poppins', sans-serif; }
    
    html, body {
      height: 100%;
      margin: 0;
      padding: 0;
    }

    body {
      display: flex;
      flex-direction: column;
    }

    main {
      flex: 1;
      display: flex;
      overflow: hidden;
    }

    .footer {
      flex-shrink: 0;
    }

    .messages-container {
      display: grid;
      grid-template-columns: 300px 1fr;
      gap: 0;
      height: 100%;
      max-width: 1400px;
      margin: 0 auto;
      width: 100%;
    }

    .conversations-panel {
      background: #f8fafb;
      border-right: 1px solid #e0e6ed;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    .conversations-header {
      padding: 1.25rem;
      border-bottom: 1px solid #e0e6ed;
      background: white;
    }

    .conversations-header h2 {
      margin: 0;
      font-size: 1.1rem;
      color: var(--text-dark);
    }

    .conversations-list {
      flex: 1;
      overflow-y: auto;
    }

    .conversation-item {
      padding: 1rem;
      border-bottom: 1px solid #f0f2f5;
      cursor: pointer;
      transition: background 0.2s;
    }

    .conversation-item:hover {
      background: #f0f2f5;
    }

    .conversation-item.active {
      background: white;
      border-left: 4px solid var(--primary-dark);
      padding-left: calc(1rem - 4px);
    }

    .conversation-item-name {
      font-weight: 600;
      color: var(--text-dark);
      margin-bottom: 0.25rem;
    }

    .conversation-item-preview {
      font-size: 0.85rem;
      color: var(--text-light);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .chat-panel {
      flex: 1;
      display: flex;
      flex-direction: column;
      background: white;
    }

    .chat-header {
      padding: 1.25rem;
      border-bottom: 1px solid #e0e6ed;
      background: white;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
    }

    .chat-header-title {
      margin: 0;
      font-size: 1.1rem;
      color: var(--text-dark);
    }

    .chat-messages {
      flex: 1;
      overflow-y: auto;
      padding: 1.5rem;
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .message {
      display: flex;
      margin-bottom: 0.75rem;
    }

    .message.sent {
      justify-content: flex-end;
    }

    .message.received {
      justify-content: flex-start;
    }

    .message-bubble {
      max-width: 60%;
      padding: 0.75rem 1rem;
      border-radius: 12px;
      word-wrap: break-word;
      line-height: 1.5;
    }

    .message.sent .message-bubble {
      background: var(--primary-dark);
      color: white;
      border-radius: 12px 4px 12px 12px;
    }

    .message.received .message-bubble {
      background: #f0f2f5;
      color: var(--text-dark);
      border-radius: 4px 12px 12px 12px;
    }

    .message-application {
      background: #f8fafb;
      border-left: 3px solid var(--primary-light);
      padding: 0.75rem;
      border-radius: 4px;
      font-size: 0.85rem;
      margin-top: 0.5rem;
    }

    .message-time {
      font-size: 0.75rem;
      color: #999;
      margin-top: 0.25rem;
      text-align: right;
    }

    .message.received .message-time {
      text-align: left;
    }

    .message-form {
      padding: 1.25rem;
      border-top: 1px solid #e0e6ed;
      background: #f8fafb;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
      margin-bottom: 1rem;
    }

    .form-group label {
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--text-mid);
    }

    .form-group select,
    .form-group textarea {
      border: 1px solid #dde1e6;
      border-radius: 6px;
      padding: 0.75rem;
      font-family: inherit;
      font-size: 0.9rem;
    }

    .form-group textarea {
      resize: none;
      min-height: 80px;
    }

    .message-actions {
      display: flex;
      gap: 0.75rem;
    }

    .btn {
      flex: 1;
      padding: 0.75rem;
      border: none;
      border-radius: 6px;
      font-weight: 600;
      cursor: pointer;
      font-size: 0.9rem;
    }

    .btn-primary {
      background: var(--primary-dark);
      color: white;
    }

    .btn-primary:hover {
      background: var(--primary-darker);
    }

    .empty-state {
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100%;
      color: var(--text-light);
      text-align: center;
      padding: 2rem;
    }

    @media (max-width: 768px) {
      .messages-container {
        grid-template-columns: 1fr;
      }

      .conversations-panel {
        display: none;
      }

      .message-bubble {
        max-width: 85%;
      }
    }
  </style>
</head>
<body>
  <nav class="navbar">
    <a href="../../index.php" class="nav-logo">
      <div class="nav-logo-icon">TS</div>
      <span class="nav-logo-text">Talent<span>Scout</span> AI</span>
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
    <div class="nav-actions">
      <?php if (isset($_SESSION['employee_id'])): ?>
        <span class="nav-user">Welcome, <?php echo htmlspecialchars($_SESSION['employee_name'] ?? 'User'); ?></span>
        <a href="../../logout.php" class="btn btn-outline">Logout</a>
      <?php else: ?>
        <a href="../../login.php" class="btn btn-outline">Login</a>
        <a href="../../signup.php" class="btn btn-primary">Get Started</a>
      <?php endif; ?>
    </div>
  </nav>

  <main class="messages-container">
    <div class="conversations-panel">
      <div class="conversations-header">
        <h2>Messages</h2>
      </div>
      <div class="conversations-list">
        <?php if (empty($conversations)): ?>
          <div style="padding: 2rem 1rem; text-align: center; color: var(--text-light);">
            <p style="margin: 0; font-size: 0.9rem;">No conversations yet</p>
          </div>
        <?php else: ?>
          <?php foreach ($conversations as $conv): ?>
            <a href="?employer_id=<?php echo $conv['employer_id']; ?>" style="text-decoration: none;">
              <div class="conversation-item <?php echo $selected_employer_id === $conv['employer_id'] ? 'active' : ''; ?>">
                <div class="conversation-item-name"><?php echo htmlspecialchars($conv['company_name']); ?></div>
                <div class="conversation-item-preview">Last message...</div>
              </div>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="chat-panel">
      <?php if ($selected_employer_id > 0): ?>
        <div class="chat-header">
          <h3 class="chat-header-title"><?php echo htmlspecialchars($selected_employer_name); ?></h3>
        </div>

        <div class="chat-messages">
          <?php if (empty($messages)): ?>
            <div class="empty-state">
              <div>
                <p style="margin: 0 0 0.5rem 0;">No messages yet</p>
                <p style="margin: 0; font-size: 0.9rem;">Start a conversation about your application</p>
              </div>
            </div>
          <?php else: ?>
            <?php foreach ($messages as $msg): ?>
              <div class="message <?php echo $msg['sender_type'] === 'employee' ? 'sent' : 'received'; ?>">
                <div>
                  <div class="message-bubble">
                    <?php echo htmlspecialchars($msg['message']); ?>
                    <?php if ($msg['application_id'] > 0 && !empty($msg['job_title'])): ?>
                      <div class="message-application">
                        <strong>About:</strong> <?php echo htmlspecialchars($msg['job_title']); ?>
                      </div>
                    <?php endif; ?>
                  </div>
                  <div class="message-time"><?php echo date('M d, H:i', strtotime($msg['timestamp'])); ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <div class="message-form">
          <form method="POST">
            <input type="hidden" name="action" value="send_message">
            <input type="hidden" name="receiver_id" value="<?php echo $selected_employer_id; ?>">

            <div class="form-group">
              <label for="application_id">Related Application (Optional)</label>
              <select name="application_id" id="application_id">
                <option value="0">-- No specific application --</option>
                <?php foreach ($applications as $app): ?>
                  <option value="<?php echo $app['application_id']; ?>">
                    <?php echo htmlspecialchars($app['job_title']); ?> at <?php echo htmlspecialchars($app['company_name']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label for="message">Message</label>
              <textarea name="message" id="message" placeholder="Type your message..." required></textarea>
            </div>

            <div class="message-actions">
              <button type="submit" class="btn btn-primary">Send Message</button>
            </div>
          </form>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <div>
            <p style="margin: 0 0 0.5rem 0;">No conversations</p>
            <p style="margin: 0; font-size: 0.9rem;">Select a conversation to start messaging</p>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </main>

  <footer class="footer">
    <div class="footer-inner">
      <span>© 2026 TalentScout AI - PESO Nasugbu, Batangas. Connect with employers and manage your applications.</span>
    </div>
  </footer>
</body>
</html>
