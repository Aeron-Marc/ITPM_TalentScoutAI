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
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../../styles/global.css">
  <link rel="stylesheet" href="../../../styles/page-layout.css">
  <style>
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

    .navbar.scrolled .nav-logo { color: var(--charcoal); }

    .nav-logo-icon {
      width: 36px; height: 36px;
      background: linear-gradient(135deg, var(--sage), var(--sage-dark));
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 0.7rem; font-weight: 700; color: #fff; letter-spacing: 0.05em;
      box-shadow: 0 4px 12px rgba(90,138,104,0.35);
    }

    .nav-logo-text { display: inline; }
    .nav-logo-text span { color: var(--mint-deep); transition: color 0.4s; }
    .navbar.scrolled .nav-logo-text span { color: var(--sage); }

    .nav-links { display: flex; list-style: none; gap: 0.2rem; margin: 0; padding: 0; }

    .nav-links a {
      padding: 0.2rem 0.75rem;
      font-size: 0.84rem; font-weight: 500; color: rgba(255,255,255,0.8);
      transition: color 0.2s, border-bottom 0.2s;
      position: relative;
      padding-bottom: 0.4rem;
    }

    .navbar.scrolled .nav-links a { color: rgba(255,255,255,0.8); }

    .nav-links a:hover {
      color: #fff;
      font-weight: 600;
    }

    .nav-links a.active {
      color: #fff;
      font-weight: 600;
      border-bottom: 2.5px solid #fff;
    }

    .navbar.scrolled .nav-links a:hover {
      color: #fff;
    }

    .navbar.scrolled .nav-links a.active {
      color: #fff;
      border-bottom-color: #fff;
    }

    .nav-actions { display: flex; align-items: center; gap: 0.65rem; }

    .nav-user { font-size: 0.82rem; color: rgba(255,255,255,0.75); transition: color 0.4s; }
    .navbar.scrolled .nav-user { color: var(--text-soft); }

    .btn-outline {
      padding: 0.42rem 1.1rem; border-radius: 999px;
      border: 1.5px solid rgba(255,255,255,0.4); color: #fff;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 0.83rem; font-weight: 500; background: transparent;
      cursor: pointer; transition: all 0.2s; text-decoration: none;
    }

    .btn-outline:hover {
      background: rgba(255,255,255,0.15);
      border-color: #fff;
    }

    .navbar.scrolled .btn-outline {
      border-color: rgba(90,138,104,0.3);
      color: var(--text-mid);
    }

    .navbar.scrolled .btn-outline:hover {
      background: var(--mint);
      border-color: var(--sage);
      color: var(--sage-dark);
    }

    .btn-primary {
      padding: 0.42rem 1.3rem; border-radius: 999px;
      background: linear-gradient(135deg, var(--mint-deep), var(--mint));
      color: var(--charcoal); font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 0.83rem; font-weight: 700; border: none;
      cursor: pointer; transition: all 0.2s; text-decoration: none;
      box-shadow: 0 4px 14px rgba(90,138,104,0.32);
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 22px rgba(90,138,104,0.4);
    }

    @media (max-width: 1024px) {
      .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
      }
    }

    @media (max-width: 768px) {
      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }
      text-decoration: none;
    }

    .btn-outline {
      border: 1.5px solid var(--mint-deep); color: var(--text-mid);
      background: transparent;
    }

    .btn-outline:hover { background: var(--mint); border-color: var(--sage); color: var(--sage-dark); }

    .btn-primary {
      background: linear-gradient(135deg, var(--sage), var(--sage-dark));
      color: #fff; font-weight: 700;
      box-shadow: 0 4px 14px rgba(90,138,104,0.32);
    }

    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 22px rgba(90,138,104,0.4); }

    /* ===== LAYOUT ===== */
    html, body {
      height: 100%;
      margin: 0;
      padding: 0;
    }

    body {
      display: flex;
      flex-direction: column;
    }

    .page-container, main {
      flex: 1 0 auto;
    }

    .footer {
      flex-shrink: 0;
    }

    /* Push content below fixed navbar */
    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 2rem 2.5rem 3rem;
      padding-top: 0;
    }

    /* ══ PAGE HERO ══ */
    .page-hero {
      background: linear-gradient(135deg, #f0fff8 0%, #e8f8f0 60%, #f5fdf8 100%);
      border-bottom: 1px solid rgba(90,138,104,0.12);
      padding: 2.5rem 2.5rem 2rem;
      margin-top: 66px;
      margin-bottom: 2.5rem;
    }
    .page-hero-inner { max-width: 1200px; margin: 0 auto; }
    .page-hero h1 { font-family: 'Lora', serif; font-size: 1.9rem; font-weight: 700; color: var(--text-dark); margin: 0 0 0.3rem 0; }
    .page-hero p { font-size: 0.92rem; color: var(--text-light); margin: 0; }

    /* Stats Block */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      gap: 1.25rem;
      margin-bottom: 3.5rem;
    }

    .stat-card {
      background: white;
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.4rem 1.5rem;
      transition: box-shadow 0.2s, transform 0.2s;
    }

    .stat-card:hover {
      box-shadow: 0 6px 20px rgba(90,138,104,0.12);
      transform: translateY(-2px);
    }

    .stat-value {
      font-size: 2.2rem;
      font-weight: 800;
      color: var(--primary-dark);
      margin-bottom: 0.35rem;
      line-height: 1;
    }

    .stat-label {
      font-size: 0.8rem;
      color: var(--text-light);
      text-transform: uppercase;
      letter-spacing: 0.7px;
      font-weight: 600;
    }

    /* Table */
    .table-wrapper {
      background: white;
      border: 1px solid var(--border);
      border-radius: var(--radius);
      overflow: hidden;
      box-shadow: 0 2px 12px rgba(0,0,0,0.04);
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    thead {
      background: #f7f7f7;
      border-bottom: 1px solid var(--border);
    }

    th {
      padding: 0.9rem 1.25rem;
      text-align: left;
      font-size: 0.78rem;
      font-weight: 700;
      color: var(--text-dark);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    tbody tr {
      border-bottom: 1px solid var(--border);
      transition: background 0.15s;
    }

    tbody tr:hover {
      background-color: #fafdf9;
    }

    tbody tr:last-child {
      border-bottom: none;
    }

    td {
      padding: 1rem 1.25rem;
      font-size: 0.92rem;
      color: var(--text-dark);
      vertical-align: middle;
    }

    .candidate-col { font-weight: 600; }

    .position-col {
      color: var(--text-light);
      font-size: 0.88rem;
    }

    .status-badge {
      font-weight: 600;
      padding: 0.3rem 0.7rem;
      border-radius: 999px;
      font-size: 0.78rem;
      display: inline-block;
      text-transform: uppercase;
      letter-spacing: 0.4px;
      white-space: nowrap;
    }

    .status-applied   { background: #efefef; color: #555; }
    .status-interview { background: #d1ecf1; color: #0c5460; }
    .status-offer     { background: #d4edda; color: #155724; }
    .status-rejected  { background: #f8d7da; color: #721c24; }
    .status-hired     { background: #c8e6c9; color: #1b5e20; }

    .match-score { font-weight: 700; }

    .date-col {
      color: var(--text-light);
      font-size: 0.88rem;
      white-space: nowrap;
    }

    .action-col { text-align: center; }

    /* Eye icon button */
    .btn-eye {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 36px;
      height: 36px;
      border-radius: 8px;
      border: 1px solid var(--border);
      background: white;
      cursor: pointer;
      transition: all 0.2s ease;
      color: var(--text-mid);
    }

    .btn-eye:hover {
      background: var(--primary-dark);
      border-color: var(--primary-dark);
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(30,158,134,0.2);
    }

    .btn-eye svg {
      width: 16px;
      height: 16px;
      flex-shrink: 0;
    }

    .footer {
      background: #1a1a1a;
      color: rgba(255,255,255,0.6);
      padding: 1.75rem 2rem;
      text-align: center;
      font-size: 0.85rem;
    }

    /* Modal Styles */
    .modal {
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      z-index: 1000;
      display: flex;
      align-items: center;
      justify-content: center;
      animation: fadeIn 0.2s ease;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to   { opacity: 1; }
    }

    .modal-overlay {
      position: absolute;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0,0,0,0.45);
      cursor: pointer;
    }

    .modal-content {
      position: relative;
      background: white;
      border-radius: var(--radius);
      box-shadow: 0 24px 64px rgba(0,0,0,0.22);
      max-width: 600px;
      width: 90%;
      max-height: 88vh;
      overflow-y: auto;
      animation: slideUp 0.25s ease;
    }

    @keyframes slideUp {
      from { transform: translateY(24px); opacity: 0; }
      to   { transform: translateY(0);    opacity: 1; }
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1.25rem 1.5rem;
      border-bottom: 1px solid var(--border);
      position: sticky;
      top: 0;
      background: white;
      z-index: 1;
    }

    .modal-header h2 {
      margin: 0;
      font-size: 1.15rem;
    }

    .modal-close {
      background: none;
      border: none;
      font-size: 1.5rem;
      cursor: pointer;
      color: var(--text-light);
      padding: 0;
      width: 2.2rem; height: 2.2rem;
      display: flex; align-items: center; justify-content: center;
      border-radius: 6px;
      transition: all 0.2s ease;
      line-height: 1;
    }

    .modal-close:hover {
      background: #f0f0f0;
      color: var(--text-dark);
    }

    .modal-body { padding: 1.75rem; }

    .modal-footer {
      padding: 1.25rem 1.5rem;
      border-top: 1px solid var(--border);
      display: flex;
      gap: 0.75rem;
      justify-content: flex-end;
    }

    .candidate-info {
      margin-bottom: 1.75rem;
      padding: 1.25rem;
      background: #f9f9f9;
      border-radius: var(--radius);
    }

    .candidate-info h3 {
      margin: 0 0 1rem;
      font-size: 1.05rem;
    }

    .info-row {
      display: flex;
      justify-content: space-between;
      padding: 0.45rem 0;
      border-bottom: 1px solid #ebebeb;
    }

    .info-row:last-child { border-bottom: none; }

    .info-label {
      font-weight: 600;
      color: var(--text-dark);
      font-size: 0.88rem;
      min-width: 110px;
    }

    .info-value {
      color: var(--text-light);
      text-align: right;
      font-size: 0.88rem;
    }

    .status-update-section {
      margin-top: 1.75rem;
      padding: 1.25rem;
      background: #f9f9f9;
      border-radius: var(--radius);
    }

    .status-update-section h3 {
      margin: 0 0 1rem;
      font-size: 0.95rem;
    }

    .status-buttons {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 0.65rem;
    }

    .status-btn {
      padding: 0.55rem 0.75rem;
      border: 1px solid var(--border);
      border-radius: 6px;
      background: white;
      cursor: pointer;
      font-size: 0.83rem;
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
      background: #fff0f0;
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

    .modal-input {
      box-sizing: border-box;
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

  <div class="page-hero">
    <div class="page-hero-inner">
      <h1>Job Application Tracker</h1>
      <p>Manage your entire hiring pipeline from application to hire</p>
    </div>
  </div>

  <div class="container">

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

    <!-- Applications Table -->
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th style="width: 22%;">Candidate</th>
            <th style="width: 18%;">Position</th>
            <th style="width: 14%;">Status</th>
            <th style="width: 12%;">Match Score</th>
            <th style="width: 18%;">Applied Date</th>
            <th style="width: 16%; text-align: center;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($applications) > 0): ?>
            <?php foreach ($applications as $app): 
              $displayStatus = $app['display_status'];
              $hireStatus = $app['hire_status'] ?? 'none';
              
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
              <td><span class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($displayStatus); ?></span></td>
              <td class="match-score" style="color: <?php 
                  if ($match_score >= 80) echo '#28a745';
                  elseif ($match_score >= 50) echo '#e6a817';
                  else echo '#dc3545';
                ?>;"><?php echo $match_score; ?>%</td>
              <td class="date-col"><?php echo date('M j, Y', strtotime($app['application_date'])); ?></td>
              <td class="action-col">
                <button 
                  class="btn-eye" 
                  onclick="openApplicationModal(<?php echo $app['application_id']; ?>)"
                  title="View application details"
                >
                  <!-- Eye icon -->
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                  </svg>
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" style="text-align: center; color: var(--text-light); padding: 3rem 2rem;">
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
        <p style="font-size: 1rem; margin-bottom: 1rem;">Are you sure you want to change status to:</p>
        <div id="newStatusDisplay" style="font-weight: 700; font-size: 1.2rem; color: var(--primary-dark); margin-bottom: 1.25rem;"></div>
        <p style="font-size: 0.88rem; color: var(--text-light);">This action cannot be undone.</p>
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
    <div class="modal-content" style="max-width: 380px;">
      <div class="modal-header">
        <h2 id="notificationTitle"></h2>
        <button class="modal-close" onclick="closeNotificationModal()">×</button>
      </div>
      <div class="modal-body" style="text-align: center;">
        <div id="notificationIcon" style="font-size: 3rem; margin-bottom: 1rem;"></div>
        <p id="notificationMessage" style="font-size: 1rem;"></p>
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
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.88rem;">Date</label>
            <input type="date" id="scheduleDate" class="modal-input" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-size: 0.9rem;" required min="<?php echo date('Y-m-d'); ?>">
          </div>
          <div>
            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.88rem;">Time</label>
            <input type="time" id="scheduleTime" class="modal-input" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid #ddd; border-radius: 6px; font-size: 0.9rem;" required>
          </div>
        </div>
        <div style="margin-bottom: 1rem;">
          <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.88rem;">Confirmation Message (optional)</label>
          <textarea id="scheduleMessage" class="modal-input" rows="3" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid #ddd; border-radius: 6px; resize: vertical; font-size: 0.9rem;" placeholder="Please confirm your availability for this interview."></textarea>
        </div>
        <div style="display: flex; gap: 0.75rem;">
          <button type="button" class="btn btn-outline" onclick="hideScheduleForm()" style="flex: 1;">Cancel</button>
          <button type="button" class="btn btn-primary" onclick="submitScheduleInterview()" style="flex: 1;">Schedule</button>
        </div>
      </div>
      <!-- Send Offer Inline Form -->
      <div id="offerForm" style="display: none; padding: 1.5rem; border-top: 1px solid #eee;">
        <h3 style="margin-top: 0;">Send Job Offer</h3>
        <div style="margin-bottom: 1rem;">
          <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.88rem;">Offer Message</label>
          <textarea id="offerMessage" class="modal-input" rows="4" style="width: 100%; padding: 0.65rem 0.75rem; border: 1px solid #ddd; border-radius: 6px; resize: vertical; font-size: 0.9rem;" placeholder="Congratulations! We are pleased to offer you the position. Please respond to this offer."></textarea>
        </div>
        <p style="font-size: 0.83rem; color: #888; margin-bottom: 1rem;">This will notify the candidate about the job offer.</p>
        <div style="display: flex; gap: 0.75rem;">
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
    function openApplicationModal(applicationId) {
      var modal = document.getElementById('applicationModal');
      var modalBody = document.getElementById('modalBody');
      
      modal.style.display = 'flex';
      modalBody.style.display = 'block';
      document.getElementById('scheduleForm').style.display = 'none';
      document.getElementById('offerForm').style.display = 'none';
      modalBody.innerHTML = '<div style="text-align: center; padding: 2rem; color: var(--text-light);">Loading...</div>';

      fetch('get-application.php?application_id=' + applicationId)
        .then(function(response) { return response.json(); })
        .then(function(data) {
          if (data.success) {
            var app = data.application;
            
            var displayStatus = app.status;
            var isHired = app.hire_status === 'accepted';
            if (isHired) displayStatus = 'Hired';
            else if (app.hire_status === 'offered') displayStatus = 'Send Offer';
            else if (app.hire_status === 'rejected') displayStatus = 'Offer Declined';
            else if (app.status === 'Pending') displayStatus = 'Applied';
            else if (app.status === 'Interview Scheduled') displayStatus = 'Schedule Interview';
            else if (app.status === 'Rejected') displayStatus = 'Rejected';
            
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
            
            var messageBtn = '';
            if (!isHired) {
              messageBtn = '<div style="margin-top: 1.25rem;"><button class="btn btn-outline" style="width: 100%;" onclick="messageCandidate(' + app.application_id + ', \'' + app.first_name + ' ' + app.last_name + '\')">Message Candidate</button></div>';
            }
            
            var msgHistory = '';
            if (app.message_history && app.message_history.length > 0) {
              msgHistory = '<div style="margin-top: 1.75rem; padding: 1.25rem; background: #f9f9f9; border-radius: var(--radius);"><h3 style="margin: 0 0 1rem; font-size: 0.95rem;">Message History</h3>';
              for (var j = 0; j < app.message_history.length; j++) {
                var msg = app.message_history[j];
                var sender = msg.sender_type === 'employer' ? 'You' : 'Candidate';
                msgHistory += '<div style="margin-bottom: 0.65rem; padding: 0.65rem; background: white; border-radius: 6px; border: 1px solid #eee;"><div style="font-size: 0.78rem; color: #999; margin-bottom: 0.3rem;">' + sender + ' · ' + new Date(msg.timestamp).toLocaleDateString() + '</div><div style="font-size: 0.9rem;">' + msg.message + '</div></div>';
              }
              msgHistory += '</div>';
            }
            
            var html = '<div class="candidate-info">' +
              '<h3>' + app.first_name + ' ' + app.last_name + '</h3>' +
              '<div class="info-row"><span class="info-label">Position</span><span class="info-value">' + app.job_title + '</span></div>' +
              '<div class="info-row"><span class="info-label">Email</span><span class="info-value"><a href="mailto:' + app.email + '" style="color: var(--primary-dark);">' + app.email + '</a></span></div>' +
              '<div class="info-row"><span class="info-label">Location</span><span class="info-value">' + (app.address || 'N/A') + '</span></div>' +
              '<div class="info-row"><span class="info-label">Applied</span><span class="info-value">' + new Date(app.application_date).toLocaleDateString() + '</span></div>' +
              '<div class="info-row"><span class="info-label">Status</span><span class="info-value" style="font-weight: 600;">' + displayStatus + '</span></div>' +
              '</div>' +
              (statusButtons ? '<div class="status-update-section"><h3>Update Status</h3><div class="status-buttons">' + statusButtons + '</div></div>' : '') +
              messageBtn +
              msgHistory +
              '<div style="margin-top: 1.75rem; padding: 1.25rem; background: #f9f9f9; border-radius: var(--radius);"><h3 style="margin: 0 0 0.75rem; font-size: 0.95rem;">Job Description</h3><p style="color: #777; font-size: 0.88rem; margin: 0; line-height: 1.6;">' + (app.job_description || 'No description available.') + '</p></div>';
            
            modalBody.innerHTML = html;
          } else {
            modalBody.innerHTML = '<div style="color: #dc3545; text-align: center; padding: 2rem;">Error: ' + data.message + '</div>';
          }
        })
        .catch(function(error) {
          console.error(error);
          modalBody.innerHTML = '<div style="color: #dc3545; text-align: center; padding: 2rem;">Error loading details. Please try again.</div>';
        });
    }

    function closeApplicationModal() {
      document.getElementById('applicationModal').style.display = 'none';
      document.getElementById('scheduleForm').style.display = 'none';
      document.getElementById('offerForm').style.display = 'none';
      document.getElementById('modalBody').style.display = 'block';
    }

    function updateApplicationStatus(applicationId, newStatus) {
      if (newStatus === 'Schedule Interview') {
        window.pendingStatusUpdate = { applicationId: applicationId, newStatus: newStatus };
        closeApplicationModal();
        showScheduleForm(applicationId);
        return;
      }
      if (newStatus === 'Send Offer') {
        window.pendingStatusUpdate = { applicationId: applicationId, newStatus: newStatus };
        closeApplicationModal();
        showOfferForm(applicationId);
        return;
      }
      document.getElementById('newStatusDisplay').textContent = newStatus;
      document.getElementById('statusModal').style.display = 'flex';
      window.pendingStatusUpdate = { applicationId: applicationId, newStatus: newStatus };
    }

    function showScheduleForm(applicationId) {
      var modal = document.getElementById('applicationModal');
      modal.style.display = 'flex';
      document.getElementById('scheduleForm').style.display = 'block';
      document.getElementById('offerForm').style.display = 'none';
      document.getElementById('modalBody').style.display = 'none';
      var today = new Date().toISOString().split('T')[0];
      document.getElementById('scheduleDate').min = today;
    }

    function showOfferForm(applicationId) {
      var modal = document.getElementById('applicationModal');
      modal.style.display = 'flex';
      document.getElementById('offerForm').style.display = 'block';
      document.getElementById('scheduleForm').style.display = 'none';
      document.getElementById('modalBody').style.display = 'none';
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
        for (var key in formData) { data[key] = formData[key]; }
      }
      var formDataObj = new FormData();
      for (var key in data) { formDataObj.append(key, data[key]); }
      
      fetch('update-application.php', { method: 'POST', body: formDataObj })
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
        .catch(function() {
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
      updateStatusViaAction('offer_hire_action', data.applicationId, {
        hire_message: offerMessage
      }, 'Job offer sent successfully');
    }

    function confirmStatusUpdate() {
      var data = window.pendingStatusUpdate;
      if (!data) return;
      closeStatusModal();
      var formData = new FormData();
      formData.append('application_id', data.applicationId);
      formData.append('status', data.newStatus);
      fetch('update-application.php', { method: 'POST', body: formData })
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
        .catch(function() {
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

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeApplicationModal();
        closeNotificationModal();
        closeStatusModal();
      }
    });

    document.getElementById('confirmStatusBtn').addEventListener('click', confirmStatusUpdate);

    /* Navbar scroll detection */
    const navbar = document.querySelector('.navbar');
    window.addEventListener('scroll', () => {
      if (window.scrollY > 20) {
        navbar.classList.add('scrolled');
      } else {
        navbar.classList.remove('scrolled');
      }
    });
  </script>

</body>
</html>