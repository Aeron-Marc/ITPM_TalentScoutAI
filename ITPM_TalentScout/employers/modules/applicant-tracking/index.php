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

// Fetch all applications for this employer's jobs
$applications = [];
$stmt = $conn->prepare("SELECT 
  a.application_id, 
  a.job_post_id,
  a.employee_id,
  a.status,
  a.hire_status,
  a.application_date,
  e.first_name,
  e.last_name,
  jp.title as job_title,
  jp.skills as job_skills,
  GROUP_CONCAT(DISTINCT es.skill_name ORDER BY es.skill_name SEPARATOR ', ') as candidate_skills
FROM application a
JOIN job_post jp ON a.job_post_id = jp.job_post_id
JOIN employee e ON a.employee_id = e.employee_id
LEFT JOIN employee_skill es ON a.employee_id = es.employee_id
WHERE jp.employer_id = ?
GROUP BY a.application_id, a.job_post_id, a.employee_id, a.status, a.hire_status, a.application_date, e.first_name, e.last_name, jp.title, jp.skills
ORDER BY a.application_date DESC");
$stmt->bind_param("i", $employer_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  // Calculate display status based on hire_status
  $hireStatus = $row['hire_status'] ?? 'none';
  if ($hireStatus === 'accepted') {
    $row['display_status'] = 'Hired';
  } elseif ($hireStatus === 'rejected') {
    $row['display_status'] = 'Offer Declined';
  } elseif ($hireStatus === 'offered') {
    $row['display_status'] = 'Offer Sent';
  } else {
    $row['display_status'] = $row['status'];
  }
  $applications[] = $row;
}
$stmt->close();

// Calculate stats
$stats = [
  'total' => count($applications),
  'applied' => 0,
  'interview' => 0,
  'offer' => 0,
  'hired' => 0,
  'rejected' => 0
];

foreach ($applications as $app) {
  $hireStatus = $app['hire_status'] ?? 'none';
  
  if ($hireStatus === 'accepted') {
    $stats['hired']++;
  } elseif ($hireStatus === 'offered') {
    $stats['offer']++;
  } elseif ($hireStatus === 'rejected') {
    $stats['rejected']++;
  } else {
    switch (strtolower($app['status'])) {
      case 'pending':
        $stats['applied']++;
        break;
      case 'interview scheduled':
        $stats['interview']++;
        break;
      case 'rejected':
        $stats['rejected']++;
        break;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Application Tracker — TalentScout AI</title>
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

    .container { max-width: 1400px; margin: 0 auto; padding: 2.5rem; }
    
    .page-header {
      margin-bottom: 2.5rem;
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

    /* Stats Block */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1.5rem;
      margin-bottom: 3rem;
    }
    .stat-card {
      background: white;
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.5rem;
    }
    .stat-value {
      font-size: 2.2rem;
      font-weight: 800;
      color: var(--primary-dark);
      margin-bottom: 0.5rem;
    }
    .stat-label {
      font-size: 0.85rem;
      color: var(--text-light);
      text-transform: uppercase;
      letter-spacing: 0.6px;
      font-weight: 600;
    }

    /* Table */
    .table-wrapper {
      background: white;
      border: 1px solid var(--border);
      border-radius: var(--radius);
      overflow: hidden;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    thead {
      background: #f5f5f5;
      border-bottom: 1px solid var(--border);
    }

    th {
      padding: 1rem 1.25rem;
      text-align: left;
      font-size: 0.85rem;
      font-weight: 700;
      color: var(--text-dark);
      text-transform: uppercase;
      letter-spacing: 0.4px;
    }

    tbody tr {
      border-bottom: 1px solid var(--border);
      transition: all 0.2s ease;
    }

    tbody tr:hover {
      background-color: #fafafa;
      box-shadow: inset 0 0 12px rgba(30, 158, 134, 0.08);
    }

    tbody tr:last-child {
      border-bottom: none;
    }

    td {
      padding: 1.25rem;
      font-size: 0.95rem;
      color: var(--text-dark);
    }

    .candidate-col {
      font-weight: 600;
    }

    .position-col {
      color: var(--text-light);
      font-size: 0.9rem;
    }

    .status-col {
      font-weight: 600;
      padding: 0.5rem 0.75rem;
      border-radius: 4px;
      font-size: 0.85rem;
      display: inline-block;
      text-transform: uppercase;
      letter-spacing: 0.3px;
    }

    .status-applied { background: #e8e8e8; color: #666; }
    .status-interview { background: #d1ecf1; color: #0c5460; }
    .status-offer { background: #d4edda; color: #155724; }
    .status-rejected { background: #f8d7da; color: #721c24; }
    .status-hired { background: #c8e6c9; color: #1b5e20; }

    .match-score {
      font-weight: 700;
      color: var(--primary-dark);
    }

    .date-col {
      color: var(--text-light);
      font-size: 0.9rem;
    }

    .action-col {
      text-align: center;
    }

    .btn-small {
      background: white;
      border: 1px solid var(--border);
      padding: 0.4rem 0.8rem;
      border-radius: 4px;
      font-size: 0.8rem;
      cursor: pointer;
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      font-weight: 600;
      color: var(--text-dark);
      text-decoration: none;
      display: inline-block;
    }

    .btn-small:hover {
      background: var(--primary-dark);
      color: white;
      border-color: var(--primary-dark);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(30, 158, 134, 0.15);
    }

    .btn-small:active {
      transform: translateY(0);
    }

    .footer { background: #1a1a1a; color: white; padding: 2rem; margin-top: 3rem; text-align: center; }

    /* Modal Styles */
    .modal {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 1000;
      display: flex;
      align-items: center;
      justify-content: center;
      animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .modal-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      cursor: pointer;
    }

    .modal-content {
      position: relative;
      background: white;
      border-radius: var(--radius);
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      max-width: 600px;
      width: 90%;
      max-height: 85vh;
      overflow-y: auto;
      animation: slideUp 0.3s ease;
    }

    @keyframes slideUp {
      from {
        transform: translateY(30px);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1.5rem;
      border-bottom: 1px solid var(--border);
    }

    .modal-header h2 {
      margin: 0;
      font-size: 1.25rem;
    }

    .modal-close {
      background: none;
      border: none;
      font-size: 2rem;
      cursor: pointer;
      color: var(--text-light);
      padding: 0;
      width: 2.5rem;
      height: 2.5rem;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s ease;
    }

    .modal-close:hover {
      color: var(--text-dark);
      transform: rotate(90deg);
    }

    .modal-body {
      padding: 2rem;
    }

    .modal-footer {
      padding: 1.5rem;
      border-top: 1px solid var(--border);
      display: flex;
      gap: 1rem;
      justify-content: flex-end;
    }

    .candidate-info {
      margin-bottom: 2rem;
      padding: 1.5rem;
      background: #f9f9f9;
      border-radius: var(--radius);
    }

    .candidate-info h3 {
      margin-top: 0;
      font-size: 1.1rem;
      margin-bottom: 0.75rem;
    }

    .info-row {
      display: flex;
      justify-content: space-between;
      padding: 0.5rem 0;
      border-bottom: 1px solid #e0e0e0;
    }

    .info-row:last-child {
      border-bottom: none;
    }

    .info-label {
      font-weight: 600;
      color: var(--text-dark);
      min-width: 120px;
    }

    .info-value {
      color: var(--text-light);
      text-align: right;
    }

    .status-update-section {
      margin-top: 2rem;
      padding: 1.5rem;
      background: #f9f9f9;
      border-radius: var(--radius);
    }

    .status-update-section h3 {
      margin-top: 0;
      font-size: 1rem;
      margin-bottom: 1rem;
    }

    .status-buttons {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 0.75rem;
    }

    .status-btn {
      padding: 0.6rem 0.8rem;
      border: 1px solid var(--border);
      border-radius: 4px;
      background: white;
      cursor: pointer;
      font-size: 0.85rem;
      font-weight: 600;
      transition: all 0.2s ease;
      color: var(--text-dark);
    }

    .status-btn:hover {
      background: var(--primary-light);
      border-color: var(--primary-dark);
      color: var(--primary-dark);
    }

    .status-btn.btn-reject {
      grid-column: 1 / -1;
      background: #fee;
      border-color: #fcc;
      color: #c33;
    }

    .status-btn.btn-reject:hover {
      background: #fdd;
      border-color: #f99;
    }

    .status-btn-active {
      background: var(--primary-dark) !important;
      border-color: var(--primary-dark) !important;
      color: white !important;
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
      <li><a href="../post-jobs/">Post Jobs</a></li>
      <li><a href="../employee-finder/">Find Talent</a></li>
      <li><a href="./" class="active">Hiring Pipeline</a></li>
      <li><a href="../chat-sms/">Messages</a></li>
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

  <div class="container">
    <div class="page-header">
      <h1>Job Application Tracker</h1>
      <p>Manage your entire hiring pipeline from application to hire</p>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-value"><?php echo $stats['total']; ?></div>
        <div class="stat-label">Total Applications</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?php echo ($stats['applied'] + $stats['interview']); ?></div>
        <div class="stat-label">Ready to Review</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?php echo $stats['interview']; ?></div>
        <div class="stat-label">Interviews</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?php echo $stats['offer']; ?></div>
        <div class="stat-label">Offers Sent</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?php echo $stats['hired']; ?></div>
        <div class="stat-label">Hired</div>
      </div>
    </div>

    <!-- Kanban Board -->
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th style="width: 20%;">Candidate Name</th>
            <th style="width: 15%;">Position</th>
            <th style="width: 15%;">Status</th>
            <th style="width: 12%;">Match Score</th>
            <th style="width: 18%;">Applied Date</th>
            <th style="width: 20%;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($applications) > 0): ?>
            <?php foreach ($applications as $app): 
              // Use display_status from query
              $displayStatus = $app['display_status'];
              $hireStatus = $app['hire_status'] ?? 'none';
              
              // Determine status class based on hire_status first
              $status_class = 'status-applied';
              if ($hireStatus === 'accepted') {
                $status_class = 'status-hired';
                $displayStatus = 'Hired';
              } elseif ($hireStatus === 'rejected') {
                $status_class = 'status-rejected';
                $displayStatus = 'Offer Declined';
              } elseif ($hireStatus === 'offered') {
                $status_class = 'status-offer';
                $displayStatus = 'Offer Sent';
              } else {
                $status = strtolower($app['status']);
                if ($status === 'interview scheduled') {
                  $status_class = 'status-interview';
                  $displayStatus = 'Interview';
                } elseif ($status === 'rejected') {
                  $status_class = 'status-rejected';
                  $displayStatus = 'Rejected';
                } else {
                  $displayStatus = 'Applied';
                }
              }
              
              // Simple skill-based match score algorithm
              $job_skills = !empty($app['job_skills']) 
                  ? array_map('trim', array_filter(explode(',', $app['job_skills']))) 
                  : [];
              $candidate_skills = !empty($app['candidate_skills']) 
                  ? array_map('trim', array_filter(explode(',', $app['candidate_skills']))) 
                  : [];
              
              $matching_skills = array_intersect($job_skills, $candidate_skills);
              $match_score = count($job_skills) > 0 
                  ? round((count($matching_skills) / count($job_skills)) * 100) 
                  : 0;
            ?>
            <tr data-application-id="<?php echo $app['application_id']; ?>">
              <td class="candidate-col"><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></td>
              <td class="position-col"><?php echo htmlspecialchars($app['job_title']); ?></td>
              <td><span class="status-col <?php echo $status_class; ?>"><?php echo htmlspecialchars($displayStatus); ?></span></td>
              <td class="match-score" style="color: <?php 
                  if ($match_score >= 80) echo '#28a745';
                  elseif ($match_score >= 50) echo '#ffc107';
                  else echo '#dc3545';
                ?>;"><?php echo $match_score; ?>%</td>
              <td class="date-col"><?php echo date('M j, Y', strtotime($app['application_date'])); ?></td>
              <td class="action-col">
                <button class="btn-small view-details" onclick="openApplicationModal(<?php echo $app['application_id']; ?>)">View</button>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" style="text-align: center; color: var(--text-light); padding: 2rem;">
                No applications yet. Share your job postings with candidates to start receiving applications.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Status Update Confirmation Modal -->
  <div id="statusModal" class="modal" style="display: none; z-index: 3000;">
    <div class="modal-overlay" onclick="closeStatusModal()"></div>
    <div class="modal-content" style="max-width: 400px;">
      <div class="modal-header">
        <h2>Confirm Status Change</h2>
        <button class="modal-close" onclick="closeStatusModal()">×</button>
      </div>
      <div class="modal-body" style="text-align: center;">
        <p style="font-size: 1.1rem; margin-bottom: 1rem;">Are you sure you want to change status to:</p>
        <div id="newStatusDisplay" style="font-weight: 700; font-size: 1.3rem; color: var(--primary-dark); margin-bottom: 1.5rem;"></div>
        <p style="font-size: 0.9rem; color: var(--text-light);">This action cannot be undone.</p>
      </div>
      <div class="modal-footer" style="justify-content: center;">
        <button class="btn btn-outline" onclick="closeStatusModal()">Cancel</button>
        <button class="btn btn-primary" id="confirmStatusBtn">Confirm</button>
      </div>
    </div>
  </div>

  <!-- Notification Modal -->
  <div id="notificationModal" class="modal" style="display: none; z-index: 3001;">
    <div class="modal-overlay" onclick="closeNotificationModal()"></div>
    <div class="modal-content" style="max-width: 400px;">
      <div class="modal-header">
        <h2 id="notificationTitle"></h2>
        <button class="modal-close" onclick="closeNotificationModal()">×</button>
      </div>
      <div class="modal-body" style="text-align: center;">
        <div id="notificationIcon" style="font-size: 3rem; margin-bottom: 1rem;"></div>
        <p id="notificationMessage" style="font-size: 1.1rem;"></p>
      </div>
      <div class="modal-footer" style="justify-content: center;">
        <button class="btn btn-primary" onclick="closeNotificationModal()">OK</button>
      </div>
    </div>
  </div>

  <!-- Application Details Modal -->
  <div id="applicationModal" class="modal" style="display: none; z-index: 2000;">
    <div class="modal-overlay" onclick="closeApplicationModal()"></div>
    <div class="modal-content">
      <div class="modal-header">
        <h2>Application Details</h2>
        <button class="modal-close" onclick="closeApplicationModal()">×</button>
      </div>
      <div id="modalBody" class="modal-body">
        <div style="text-align: center; padding: 2rem;">Loading...</div>
      </div>
      <!-- Schedule Interview Inline Form -->
      <div id="scheduleForm" style="display: none; padding: 1.5rem; border-top: 1px solid #eee;">
        <h3 style="margin-top: 0;">Schedule Interview</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
          <div>
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Date</label>
            <input type="date" id="scheduleDate" class="modal-input" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px;" required>
          </div>
          <div>
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Time</label>
            <input type="time" id="scheduleTime" class="modal-input" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px;" required>
          </div>
        </div>
        <div style="margin-bottom: 1rem;">
          <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Confirmation Message (optional)</label>
          <textarea id="scheduleMessage" class="modal-input" rows="3" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; resize: vertical;" placeholder="Please confirm your availability for this interview."></textarea>
        </div>
        <div style="display: flex; gap: 1rem;">
          <button type="button" class="btn btn-outline" onclick="hideScheduleForm()" style="flex: 1;">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="submitScheduleInterview()" style="flex: 1;">Schedule</button>
        </div>
      </div>
      <!-- Send Offer Inline Form -->
      <div id="offerForm" style="display: none; padding: 1.5rem; border-top: 1px solid #eee;">
        <h3 style="margin-top: 0;">Send Job Offer</h3>
        <div style="margin-bottom: 1rem;">
          <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Offer Message</label>
          <textarea id="offerMessage" class="modal-input" rows="4" style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 6px; resize: vertical;" placeholder="Congratulations! We are pleased to offer you the position. Please respond to this offer."></textarea>
        </div>
        <p style="font-size: 0.85rem; color: #666; margin-bottom: 1rem;">This will notify the candidate about the job offer.</p>
        <div style="display: flex; gap: 1rem;">
          <button type="button" class="btn btn-outline" onclick="hideScheduleForm()" style="flex: 1;">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="submitSendOffer()" style="flex: 1;">Send Offer</button>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline" onclick="closeApplicationModal()">Close</button>
      </div>
    </div>
  </div>

  <footer class="footer">
    <p>&copy; 2026 TalentScout AI — PESO Nasugbu, Batangas. Streamlined hiring process.</p>
  </footer>

<script>
    // Open the application details modal
    function openApplicationModal(applicationId) {
      var modal = document.getElementById('applicationModal');
      var modalBody = document.getElementById('modalBody');
      
      modal.style.display = 'flex';
      modalBody.innerHTML = '<div style="text-align: center; padding: 2rem;">Loading...</div>';

      // Use relative path to the API
      var apiPath = 'get-application.php?application_id=' + applicationId;

      // Fetch the application details
      fetch(apiPath)
        .then(function(response) { return response.json(); })
        .then(function(data) {
          if (data.success) {
            var app = data.application;
            
            // Build status display with mapping
            var displayStatus = app.status;
            var isHired = app.hire_status === 'accepted';
            if (isHired) displayStatus = 'Hired';
            else if (app.hire_status === 'offered') displayStatus = 'Send Offer';
            else if (app.hire_status === 'rejected') displayStatus = 'Offer Declined';
            else if (app.status === 'Pending') displayStatus = 'Applied';
            else if (app.status === 'Interview Scheduled') displayStatus = 'Schedule Interview';
            else if (app.status === 'Rejected') displayStatus = 'Rejected';
            
            // Status update buttons - hide if hired
            var statusButtons = '';
            if (!isHired) {
              var statusOpts = ['Applied', 'Schedule Interview', 'Send Offer', 'Hired', 'Rejected'];
              for (var i = 0; i < statusOpts.length; i++) {
                var isActive = statusOpts[i] === displayStatus;
                var btnClass = statusOpts[i] === 'Rejected' ? 'status-btn btn-reject' : 'status-btn';
                if (isActive) btnClass += ' status-btn-active';
                statusButtons += '<button class="' + btnClass + '" onclick="updateApplicationStatus(' + app.application_id + ', \'' + statusOpts[i] + '\')">' + (isActive ? '✓ ' : '') + statusOpts[i] + '</button>';
              }
            }
            
            // Message button - hide if hired
            var messageBtn = '';
            if (!isHired) {
              messageBtn = '<div style="margin-top: 1.5rem;"><button class="btn btn-outline" style="width: 100%;" onclick="messageCandidate(' + app.application_id + ', \'' + app.first_name + ' ' + app.last_name + '\')">Message Candidate</button></div>';
            }
            
            // Message history
            var msgHistory = '';
            if (app.message_history && app.message_history.length > 0) {
              msgHistory = '<div style="margin-top: 2rem; padding: 1.5rem; background: #f9f9f9; border-radius: var(--radius);"><h3>Message History</h3>';
              for (var j = 0; j < app.message_history.length; j++) {
                var msg = app.message_history[j];
                var sender = msg.sender_type === 'employer' ? 'You' : 'Candidate';
                msgHistory += '<div style="margin-bottom: 0.75rem; padding: 0.75rem; background: white; border-radius: 8px;"><div style="font-size: 0.8rem; color: #888;">' + sender + ' • ' + new Date(msg.timestamp).toLocaleDateString() + '</div><div>' + msg.message + '</div></div>';
              }
              msgHistory += '</div>';
            }
            
            var html = '<div class="candidate-info">' +
              '<h3>' + app.first_name + ' ' + app.last_name + '</h3>' +
              '<div class="info-row"><span class="info-label">Position:</span><span class="info-value">' + app.job_title + '</span></div>' +
              '<div class="info-row"><span class="info-label">Email:</span><span class="info-value"><a href="mailto:' + app.email + '">' + app.email + '</a></span></div>' +
              '<div class="info-row"><span class="info-label">Location:</span><span class="info-value">' + (app.address || 'N/A') + '</span></div>' +
              '<div class="info-row"><span class="info-label">Applied:</span><span class="info-value">' + new Date(app.application_date).toLocaleDateString() + '</span></div>' +
              '<div class="info-row"><span class="info-label">Status:</span><span class="info-value" style="font-weight: bold;">' + displayStatus + '</span></div>' +
              '</div>' +
              (statusButtons ? '<div class="status-update-section"><h3>Update Status</h3><div class="status-buttons">' + statusButtons + '</div></div>' : '') +
              messageBtn +
              msgHistory +
              '<div style="margin-top: 2rem; padding: 1.5rem; background: #f9f9f9; border-radius: var(--radius);"><h3>Job Description</h3><p style="color: #888;">' + (app.job_description || 'No description') + '</p></div>';
            
            modalBody.innerHTML = html;
          } else {
            modalBody.innerHTML = '<div style="color: red; text-align: center;">Error: ' + data.message + '</div>';
          }
        })
        .catch(function(error) {
          console.error(error);
          modalBody.innerHTML = '<div style="color: red; text-align: center;">Error loading details</div>';
        });
    }

    function closeApplicationModal() {
      document.getElementById('applicationModal').style.display = 'none';
    }

    function updateApplicationStatus(applicationId, newStatus) {
      // If Schedule Interview, show inline form
      if (newStatus === 'Schedule Interview') {
        window.pendingStatusUpdate = { applicationId: applicationId, newStatus: newStatus };
        closeApplicationModal();
        showScheduleForm(applicationId);
        return;
      }
      
      // If Send Offer, use existing chat-sms function via fetch
      if (newStatus === 'Send Offer') {
        window.pendingStatusUpdate = { applicationId: applicationId, newStatus: newStatus };
        closeApplicationModal();
        showOfferForm(applicationId);
        return;
      }
      
      // For other statuses (Applied, Hired, Rejected), show confirmation modal
      document.getElementById('newStatusDisplay').textContent = newStatus;
      document.getElementById('statusModal').style.display = 'flex';
      window.pendingStatusUpdate = { applicationId: applicationId, newStatus: newStatus };
    }

    function showScheduleForm(applicationId) {
      var modal = document.getElementById('applicationModal');
      var modalBody = document.getElementById('modalBody');
      var scheduleForm = document.getElementById('scheduleForm');
      var offerForm = document.getElementById('offerForm');
      
      modal.style.display = 'flex';
      scheduleForm.style.display = 'block';
      offerForm.style.display = 'none';
      modalBody.style.display = 'none';
      
      // Set minimum date to today
      var today = new Date().toISOString().split('T')[0];
      document.getElementById('scheduleDate').min = today;
    }

    function showOfferForm(applicationId) {
      var modal = document.getElementById('applicationModal');
      var modalBody = document.getElementById('modalBody');
      var scheduleForm = document.getElementById('scheduleForm');
      var offerForm = document.getElementById('offerForm');
      
      modal.style.display = 'flex';
      offerForm.style.display = 'block';
      scheduleForm.style.display = 'none';
      modalBody.style.display = 'none';
    }

    function hideScheduleForm() {
      document.getElementById('applicationModal').style.display = 'none';
      document.getElementById('scheduleForm').style.display = 'none';
      document.getElementById('offerForm').style.display = 'none';
      document.getElementById('modalBody').style.display = 'block';
      window.pendingStatusUpdate = null;
    }

    function updateStatusViaAction(action, applicationId, formData, successMessage) {
      var data = { action: action, application_id: applicationId };
      if (formData) {
        for (var key in formData) {
          data[key] = formData[key];
        }
      }
      
      var formDataObj = new FormData();
      for (var key in data) {
        formDataObj.append(key, data[key]);
      }
      
      fetch('update-application.php', {
        method: 'POST',
        body: formDataObj
      })
      .then(function(response) { return response.json(); })
      .then(function(result) {
        if (result.success) {
          showNotificationModal('Success', successMessage || 'Action completed');
          hideScheduleForm();
          setTimeout(function() { location.reload(); }, 1500);
        } else {
          showNotificationModal('Error', result.message || 'Failed');
        }
      })
      .catch(function(error) {
        showNotificationModal('Error', 'Failed to process request');
      });
    }

    function submitScheduleInterview() {
      var data = window.pendingStatusUpdate;
      if (!data) return;
      
      var scheduleDate = document.getElementById('scheduleDate').value;
      var scheduleTime = document.getElementById('scheduleTime').value;
      var scheduleMessage = document.getElementById('scheduleMessage').value;
      
      if (!scheduleDate || !scheduleTime) {
        showNotificationModal('Error', 'Please select date and time');
        return;
      }
      
      // Use update-application.php to handle scheduling
      updateStatusViaAction('schedule_interview_action', data.applicationId, {
        scheduled_date: scheduleDate,
        scheduled_time: scheduleTime,
        confirmation_message: scheduleMessage
      }, 'Interview scheduled successfully');
    }

    function submitSendOffer() {
      var data = window.pendingStatusUpdate;
      if (!data) return;
      
      var offerMessage = document.getElementById('offerMessage').value;
      
      // Use update-application.php to handle offer
      updateStatusViaAction('offer_hire_action', data.applicationId, {
        hire_message: offerMessage
      }, 'Job offer sent successfully');
    }

    function closeApplicationModal() {
      document.getElementById('applicationModal').style.display = 'none';
      document.getElementById('scheduleForm').style.display = 'none';
      document.getElementById('offerForm').style.display = 'none';
      document.getElementById('modalBody').style.display = 'block';
    }

    function confirmStatusUpdate() {
      var data = window.pendingStatusUpdate;
      if (!data) return;
      
      closeStatusModal();
      
      var formData = new FormData();
      formData.append('application_id', data.applicationId);
      formData.append('status', data.newStatus);

      fetch('update-application.php', {
        method: 'POST',
        body: formData
      })
      .then(function(response) { return response.json(); })
      .then(function(result) {
        if (result.success) {
          showNotificationModal('Success', 'Application status updated to "' + data.newStatus + '"');
          closeApplicationModal();
          setTimeout(function() { location.reload(); }, 1500);
        } else {
          showNotificationModal('Error', result.message || 'Failed to update status');
        }
      })
      .catch(function(error) {
        showNotificationModal('Error', 'Failed to update status');
      });
    }

    function closeStatusModal() {
      document.getElementById('statusModal').style.display = 'none';
      window.pendingStatusUpdate = null;
    }

    function showNotificationModal(title, message) {
      document.getElementById('notificationTitle').textContent = title;
      document.getElementById('notificationMessage').textContent = message;
      document.getElementById('notificationModal').style.display = 'flex';
      var icon = document.getElementById('notificationIcon');
      icon.textContent = title === 'Success' ? '✓' : '✗';
      icon.style.color = title === 'Success' ? '#28a745' : '#dc3545';
    }

    function closeNotificationModal() {
      document.getElementById('notificationModal').style.display = 'none';
    }

    function messageCandidate(applicationId, candidateName) {
      window.location.href = '../chat-sms/?application_id=' + applicationId;
    }

    // Close modal when clicking overlay
    document.addEventListener('click', function(e) {
      if (e.target.classList.contains('modal-overlay')) {
        e.target.parentElement.style.display = 'none';
      }
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeApplicationModal();
        closeNotificationModal();
        closeStatusModal();
      }
    });

    // Set up confirm button
    document.getElementById('confirmStatusBtn').addEventListener('click', confirmStatusUpdate);
  </script>

</body>
</html>
