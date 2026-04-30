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
  
  if ($receiver_id > 0 && !empty($message)) {
    $stmt = $conn->prepare("INSERT INTO message (sender_id, sender_type, receiver_id, receiver_type, message, timestamp) VALUES (?, 'employer', ?, 'employee', ?, NOW())");
    $stmt->bind_param("iis", $employer_id, $receiver_id, $message);
    $stmt->execute();
    $stmt->close();
  }
}

// Fetch all conversations (unique employee IDs who have messages with this employer)
$conversations = [];
$stmt = $conn->prepare("SELECT DISTINCT e.employee_id, e.first_name, e.last_name 
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
$selected_employee_id = count($conversations) > 0 ? $conversations[0]['employee_id'] : 0;
$selected_employee_name = count($conversations) > 0 ? ($conversations[0]['first_name'] . ' ' . $conversations[0]['last_name']) : 'No Conversations';

// Fetch messages for selected conversation
$messages = [];
if ($selected_employee_id > 0) {
  $stmt = $conn->prepare("SELECT m.*, e.first_name, e.last_name 
  FROM message m
  LEFT JOIN employee e ON m.sender_id = e.employee_id
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
      max-width: 1400px;
      margin: 0 auto;
      padding: 2.5rem;
      display: grid;
      grid-template-columns: 320px 1fr;
      gap: 2rem;
      min-height: calc(100vh - 120px);
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
      padding: 1.25rem;
      height: fit-content;
      position: sticky;
      top: 90px;
    }

    .conversations-title {
      font-weight: 700;
      font-size: 0.9rem;
      color: var(--text-dark);
      margin-bottom: 1rem;
      padding-bottom: 0.75rem;
      border-bottom: 1px solid var(--border);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .conversation-list {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }

    .conversation-item {
      padding: 1rem;
      border-radius: var(--radius-sm);
      cursor: pointer;
      transition: all 0.25s ease;
      border-left: 3px solid transparent;
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
      font-size: 0.9rem;
      color: var(--text-dark);
      margin-bottom: 0.25rem;
    }

    .conversation-preview {
      color: var(--text-light);
      font-size: 0.8rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      margin-bottom: 0.3rem;
    }

    .conversation-time {
      color: var(--text-muted);
      font-size: 0.75rem;
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
    }

    .chat-header {
      border-bottom: 1px solid var(--border);
      padding: 1.5rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: linear-gradient(135deg, #f8fffc 0%, #f0fffb 100%);
    }

    .chat-info {
      display: flex;
      flex-direction: column;
    }

    .chat-title {
      font-weight: 700;
      font-size: 1rem;
      color: var(--text-dark);
    }

    .chat-status {
      font-size: 0.8rem;
      color: var(--text-light);
      margin-top: 0.2rem;
    }

    .chat-actions {
      display: flex;
      gap: 0.75rem;
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

    /* Messages Area */
    .messages-area {
      flex: 1;
      padding: 1.5rem;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      gap: 1rem;
      background: white;
    }

    .message-group {
      display: flex;
      gap: 0.75rem;
      animation: slideInUp 0.3s ease-out;
    }

    .message-group.own {
      justify-content: flex-end;
    }

    .message-bubble {
      max-width: 65%;
      padding: 0.85rem 1.1rem;
      border-radius: 12px;
      word-wrap: break-word;
      line-height: 1.4;
      font-size: 0.9rem;
      transition: all 0.2s ease;
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
      font-size: 0.75rem;
      color: var(--text-light);
      margin-top: 0.3rem;
    }

    /* Input Area */
    .input-area {
      border-top: 1px solid var(--border);
      padding: 1.5rem;
      background: white;
      display: flex;
      gap: 0.75rem;
    }

    .message-input {
      flex: 1;
      padding: 0.8rem 1rem;
      border: 1.5px solid var(--border);
      border-radius: var(--radius-sm);
      font-family: inherit;
      font-size: 0.9rem;
      color: var(--text-dark);
      transition: all 0.2s ease;
      background: white;
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
      padding: 0.8rem 1.5rem;
      border-radius: var(--radius-sm);
      font-weight: 600;
      cursor: pointer;
      font-size: 0.9rem;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      gap: 0.5rem;
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

      .message-bubble {
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

      .message-bubble {
        max-width: 95%;
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
            <div class="conversation-name"><?php echo htmlspecialchars($conv['first_name'] . ' ' . $conv['last_name']); ?></div>
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
        <button class="action-btn" title="More options">⋮</button>
      </div>
    </div>

    <div class="messages-area">
      <?php if (count($messages) > 0): ?>
        <?php foreach ($messages as $msg): ?>
          <div class="message-group <?php echo ($msg['sender_type'] === 'employer') ? 'own' : 'other'; ?>">
            <div>
              <div class="message-bubble"><?php echo htmlspecialchars($msg['message']); ?></div>
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
      <form method="POST" style="display: flex; gap: 0.75rem; width: 100%;">
        <input type="hidden" name="action" value="send_message">
        <input type="hidden" name="receiver_id" value="<?php echo $selected_employee_id; ?>">
        <input type="text" name="message" class="message-input" placeholder="Type your message..." id="messageInput" required>
        <button type="submit" class="send-btn" <?php echo ($selected_employee_id > 0) ? '' : 'disabled'; ?>>[SEND]</button>
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
      <div>
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
</script>

</body>
</html>
