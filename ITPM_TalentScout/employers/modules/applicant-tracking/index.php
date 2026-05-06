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
    switch(strtolower($app['status'])) {
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

    /* ══ PAGE WRAPPER ══ */
    .page-wrapper { flex: 1 0 auto; padding-top: 66px; }

    /* ══ PAGE HERO ══ */
    .page-hero {
      background: linear-gradient(135deg, #f0fff8 0%, #e8f8f0 60%, #f5fdf8 100%);
      border-bottom: 1px solid rgba(90,138,104,0.12);
      padding: 2.5rem 2.5rem 2rem;
    }

    .page-hero-inner { max-width: 1200px; margin: 0 auto; }

    .page-hero h1 {
      font-family: 'Lora', serif;
      font-size: 1.9rem; font-weight: 700;
      color: var(--charcoal); margin: 0 0 0.3rem 0;
    }

    .page-hero p { font-size: 0.92rem; color: var(--text-soft); margin: 0; }

    /* ══ MAIN LAYOUT ══ */
    .container {
      max-width: 1200px; margin: 0 auto;
      padding: 2rem 2.5rem 3rem;
    }

    /* ══ STATS GRID ══ */
    .stats-grid {
      display: grid; grid-template-columns: repeat(5, 1fr);
      gap: 1.25rem; margin-bottom: 3.5rem;
    }

    .stat-card {
      background: white; border: 1px solid rgba(90,138,104,0.13);
      border-radius: var(--radius-lg); padding: 1.4rem 1.5rem;
      transition: box-shadow 0.2s, transform 0.2s;
    }

    .stat-card:hover {
      box-shadow: 0 6px 20px rgba(90,138,104,0.12);
      transform: translateY(-2px);
    }

    .stat-value {
      font-size: 2.2rem; font-weight: 800;
      color: var(--sage-dark); margin-bottom: 0.35rem;
      line-height: 1; font-family: 'Lora', serif;
    }

    .stat-label {
      font-size: 0.8rem; color: var(--text-soft);
      text-transform: uppercase; letter-spacing: 0.7px;
      font-weight: 600;
    }

    /* ══ TABLE ══ */
    .table-wrapper {
      background: white; border: 1px solid rgba(90,138,104,0.13);
      border-radius: var(--radius-lg); overflow: hidden;
      box-shadow: var(--shadow-soft);
    }

    table { width: 100%; border-collapse: collapse; }

    thead {
      background: linear-gradient(135deg, #f8fffc, #f0fffb);
      border-bottom: 1px solid rgba(90,138,104,0.1);
    }

    th {
      padding: 0.9rem 1.25rem; text-align: left;
      font-size: 0.78rem; font-weight: 700;
      color: var(--text-mid); text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    tbody tr {
      border-bottom: 1px solid rgba(90,138,104,0.08);
      transition: background 0.15s;
    }

    tbody tr:hover { background-color: var(--mint); }

    tbody tr:last-child { border-bottom: none; }

    td {
      padding: 1rem 1.25rem; font-size: 0.92rem;
      color: var(--charcoal); vertical-align: middle;
    }

    .candidate-col { font-weight: 600; font-family: 'Lora', serif; }

    .position-col { color: var(--text-soft); font-size: 0.88rem; }

    .status-badge {
      font-weight: 600; padding: 0.3rem 0.7rem;
      border-radius: var(--radius-pill);
      font-size: 0.78rem; display: inline-block;
      text-transform: uppercase; letter-spacing: 0.4px;
      white-space: nowrap;
    }

    .status-applied   { background: rgba(90,138,104,0.1); color: var(--sage-dark); }
    .status-interview { background: #d1ecf1; color: #0c5460; }
    .status-offer     { background: #d4edda; color: #155724; }
    .status-rejected  { background: #f8d7da; color: #721c24; }
    .status-hired     { background: var(--mint-deep); color: var(--sage-deeper); }

    .match-score { font-weight: 700; font-family: 'Lora', serif; }

    .date-col { color: var(--text-soft); font-size: 0.88rem; white-space: nowrap; }

    .action-col { text-align: center; }

    /* Eye icon button */
    .btn-eye {
      display: inline-flex; align-items: center; justify-content: center;
      width: 36px; height: 36px; border-radius: var(--radius-sm);
      border: 1px solid rgba(90,138,104,0.2); background: white;
      cursor: pointer; transition: all 0.2s ease;
      color: var(--text-mid); font-size: 0.9rem;
    }

    .btn-eye:hover {
      background: var(--sage); border-color: var(--sage);
      color: white; transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(90,138,104,0.2);
    }

    /* ══ MODAL ══ */
    .modal {
      position: fixed; z-index: 1000;
      left: 0; top: 0; width: 100%; height: 100%;
      background: rgba(0,0,0,0.45);
      backdrop-filter: blur(4px);
      animation: fadeIn 0.2s ease;
      display: none !important;
      align-items: center; justify-content: center;
    }

    .modal.active { display: flex !important; }

    .modal-content {
      background: white; border-radius: var(--radius-xl);
      box-shadow: 0 20px 60px rgba(0,0,0,0.18);
      width: 90%; max-width: 600px;
      max-height: 88vh; overflow-y: auto; position: relative;
      animation: slideUp 0.3s cubic-bezier(0.22, 1, 0.36, 1);
    }

    .modal-header {
      padding: 1.25rem 1.75rem; border-bottom: 1px solid rgba(90,138,104,0.12);
      display: flex; justify-content: space-between; align-items: center;
      background: linear-gradient(135deg, #f8fffc, #f0fff8);
      border-radius: var(--radius-xl) var(--radius-xl) 0 0;
    }

    .modal-header h2 {
      font-family: 'Lora', serif; font-size: 1.15rem;
      font-weight: 700; color: var(--charcoal); margin: 0;
    }

    .modal-close {
      background: none; border: none;
      width: 32px; height: 32px;
      display: flex; align-items: center; justify-content: center;
      border-radius: var(--radius-sm); cursor: pointer;
      color: var(--text-soft); transition: all 0.15s;
      font-size: 1.4rem; padding: 0;
    }

    .modal-close:hover { background: rgba(0,0,0,0.06); color: var(--charcoal); }

    .modal-body { padding: 1.75rem; }

    .modal-footer {
      padding: 1.25rem 1.75rem;
      border-top: 1px solid rgba(90,138,104,0.1);
      display: flex; gap: 0.75rem;
      justify-content: flex-end; align-items: center; flex-wrap: wrap;
    }

    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes slideUp { from { transform: translateY(24px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

    /* ══ FORMS ══ */
    .form-group { margin-bottom: 1.1rem; }

    .form-label {
      display: block; font-size: 0.83rem; font-weight: 600;
      color: var(--charcoal); margin-bottom: 0.4rem;
    }

    .input, .select, .textarea {
      width: 100%; padding: 0.6rem 0.85rem;
      border: 1.5px solid rgba(90,138,104,0.2);
      border-radius: 9px; font-size: 0.875rem;
      font-family: inherit; color: var(--charcoal);
      background: white; transition: border-color 0.2s, box-shadow 0.2s;
    }

    .input:focus, .select:focus, .textarea:focus {
      outline: none; border-color: var(--sage);
      box-shadow: 0 0 0 3px rgba(90,138,104,0.1);
    }

    .textarea { resize: vertical; min-height: 90px; }

    .btn-modal-cancel {
      background: #f0f0f0; color: var(--text-mid);
      border: none; padding: 0.6rem 1.3rem;
      border-radius: 9px; font-size: 0.875rem;
      font-weight: 600; cursor: pointer;
      font-family: inherit; transition: background 0.15s;
    }

    .btn-modal-cancel:hover { background: #e2e2e2; }

    .btn-modal-submit {
      padding: 0.46rem 1.25rem; border-radius: var(--radius-pill);
      background: rgba(255,255,255,0.2);
      color: #fff; font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 0.83rem; font-weight: 700;
      border: 1.5px solid rgba(255,255,255,0.4);
      cursor: pointer; display: inline-flex; align-items: center; gap: 0.3rem;
      transition: all 0.25s var(--ease);
    }

    .btn-modal-submit:hover { background: rgba(255,255,255,0.3); border-color: rgba(255,255,255,0.5); }

    /* ══ CANDIDATE INFO ══ */
    .candidate-info {
      margin-bottom: 1.75rem; padding: 1.25rem;
      background: var(--cream-mid); border-radius: var(--radius-md);
    }

    .candidate-info h3 {
      margin: 0 0 1rem; font-size: 1.05rem;
      font-family: 'Lora', serif;
    }

    .info-row {
      display: flex; justify-content: space-between;
      padding: 0.45rem 0; border-bottom: 1px solid rgba(90,138,104,0.1);
    }

    .info-row:last-child { border-bottom: none; }

    .info-label { font-weight: 600; color: var(--charcoal); font-size: 0.88rem; min-width: 110px; }

    .info-value { color: var(--text-soft); text-align: right; font-size: 0.88rem; }

    /* ══ STATUS BUTTONS ══ */
    .status-buttons {
      display: grid; grid-template-columns: repeat(2, 1fr);
      gap: 0.65rem; margin-top: 1rem;
    }

    .status-btn {
      padding: 0.55rem 0.75rem; border: 1px solid rgba(90,138,104,0.2);
      border-radius: 6px; background: white; cursor: pointer;
      font-size: 0.83rem; font-weight: 600;
      transition: all 0.2s ease; color: var(--text-dark);
    }

    .status-btn:hover { background: var(--mint); border-color: var(--sage); color: var(--sage-dark); }

    .status-btn.btn-reject {
      grid-column: 1 / -1; background: #fff0f0;
      border-color: #fcc; color: #c33;
    }

    .status-btn.btn-reject:hover { background: #fdd; border-color: #f99; }

    .status-btn-active { background: var(--sage) !important; border-color: var(--sage) !important; color: white !important; }

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
      .stats-grid { grid-template-columns: repeat(3, 1fr); }
    }

    @media (max-width: 860px) {
      .footer-top { grid-template-columns: 1fr 1fr; }
      .nav-links { display: none; }
      .hamburger { display: flex; }
    }

    @media (max-width: 600px) {
      .navbar { padding: 0 1.3rem; }
      .page-hero { padding: 2rem 1.3rem 1.5rem; }
      .container { padding: 1.5rem 1.3rem 3rem; }
      .stats-grid { grid-template-columns: repeat(2, 1fr); }
      .footer-top { grid-template-columns: 1fr; gap: 2rem; }
      .footer-bottom { flex-direction: column; text-align: center; }
      table { font-size: 0.82rem; }
      th, td { padding: 0.7rem 0.5rem; }
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
      <li><a href="../post-jobs/">Post Jobs</a></li>
      <li><a href="../employee-finder/">Find Talent</a></li>
      <li><a href="./" class="active">Hiring Pipeline</a></li>
      <li><a href="../chat-sms/">Messages</a></li>
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
    <a href="./">📊 Hiring Pipeline</a>
    <a href="../chat-sms/">💬 Messages</a>
    <div class="mobile-nav-actions">
      <?php if (isset($_SESSION['employer_id'])): ?>
        <a href="../../logout.php" class="btn-ghost">Logout</a>
      <?php else: ?>
        <a href="../../login.php" class="btn-ghost">Login</a>
        <a href="../../signup.php" class="btn-solid">Get Started →</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="page-wrapper">

    <!-- ══ PAGE HERO ══ -->
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
                } elseif ($hireStatus === 'offered') {
                  $status_class = 'status-offer';
                } elseif ($hireStatus === 'rejected') {
                  $status_class = 'status-rejected';
                } else {
                  $status = strtolower($app['status']);
                  if ($status === 'interview scheduled') {
                    $status_class = 'status-interview';
                  } elseif ($status === 'rejected') {
                    $status_class = 'status-rejected';
                  }
                }
                
                $job_skills = !empty($app['job_skills']) 
                    ? array_map('trim', explode(',', $app['job_skills'])) 
                    : [];
                $candidate_skills = !empty($app['candidate_skills']) 
                    ? array_map('trim', explode(',', $app['candidate_skills'])) 
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
                    if ($match_score >= 80) echo 'var(--sage-dark)';
                    elseif ($match_score >= 50) echo 'var(--gold)';
                    else echo '#dc3545';
                  ?>;"><?php echo $match_score; ?>%</td>
                  <td class="date-col"><?php echo date('M j, Y', strtotime($app['application_date'])); ?></td>
                  <td class="action-col">
                    <button 
                      class="btn-eye" 
                      onclick="openApplicationModal(<?php echo $app['application_id']; ?>)"
                      title="View application details"
                    >
                      👁
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
  </div>

  <!-- Application Details Modal -->
  <div id="applicationModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Application Details</h2>
        <button class="modal-close" onclick="closeApplicationModal()">×</button>
      </div>
      <div id="modalBody" class="modal-body">
        <div style="text-align: center; padding: 2rem;">Loading...</div>
      </div>
      <div class="modal-footer">
        <button class="btn-modal-cancel" onclick="closeApplicationModal()">Close</button>
      </div>
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
        <span>Connecting Employers with Local Talent</span>
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

    // Modal functions
    function openApplicationModal(applicationId) {
      const modal = document.getElementById('applicationModal');
      const modalBody = document.getElementById('modalBody');
      
      modal.classList.add('active');
      document.body.style.overflow = 'hidden';
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
              messageBtn = '<div style="margin-top: 1.25rem;"><button class="btn-modal-submit" onclick="messageCandidate(' + app.application_id + ', \'' + app.first_name + ' ' + app.last_name + '\')">Message Candidate</button></div>';
            }
            
            var msgHistory = '';
            if (app.message_history && app.message_history.length > 0) {
              msgHistory = '<div style="margin-top: 1.75rem; padding: 1.25rem; background: var(--cream-mid); border-radius: var(--radius-md);"><h3 style="margin: 0 0 1rem; font-size: 0.95rem;">Message History</h3>';
              for (var j = 0; j < app.message_history.length; j++) {
                var msg = app.message_history[j];
                var sender = msg.sender_type === 'employer' ? 'You' : 'Candidate';
                msgHistory += '<div style="margin-bottom: 0.65rem; padding: 0.65rem; background: white; border-radius: 6px; border: 1px solid rgba(90,138,104,0.1);"><div style="font-size: 0.78rem; color: #999; margin-bottom: 0.3rem;">' + sender + ' · ' + new Date(msg.timestamp).toLocaleDateString() + '</div><div style="font-size: 0.9rem;">' + msg.message + '</div></div>';
              }
              msgHistory += '</div>';
            }
            
            var html = '<div class="candidate-info">' +
              '<h3>' + app.first_name + ' ' + app.last_name + '</h3>' +
              '<div class="info-row"><span class="info-label">Position</span><span class="info-value">' + app.job_title + '</span></div>' +
              '<div class="info-row"><span class="info-label">Email</span><span class="info-value"><a href="mailto:' + app.email + '" style="color: var(--sage-dark);">' + app.email + '</a></span></div>' +
              '<div class="info-row"><span class="info-label">Location</span><span class="info-value">' + (app.address || 'N/A') + '</span></div>' +
              '<div class="info-row"><span class="info-label">Applied</span><span class="info-value">' + new Date(app.application_date).toLocaleDateString() + '</span></div>' +
              '<div class="info-row"><span class="info-label">Status</span><span class="info-value" style="font-weight: 600;">' + displayStatus + '</span></div>' +
              '</div>' +
              (statusButtons ? '<div style="margin-top: 1.75rem;"><h3 style="margin: 0 0 1rem; font-size: 0.95rem;">Update Status</h3><div class="status-buttons">' + statusButtons + '</div></div>' : '') +
              messageBtn +
              msgHistory +
              '<div style="margin-top: 1.75rem; padding: 1.25rem; background: var(--cream-mid); border-radius: var(--radius-md);"><h3 style="margin: 0 0 0.75rem; font-size: 0.95rem;">Job Description</h3><p style="color: var(--text-soft); font-size: 0.88rem; margin: 0; line-height: 1.6;">' + (app.job_description || 'No description available.') + '</p></div>';
            
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
      document.getElementById('applicationModal').classList.remove('active');
      document.body.style.overflow = 'auto';
    }

    function updateApplicationStatus(applicationId, newStatus) {
      alert('Status update to "' + newStatus + '" - Feature coming soon!');
    }

    function messageCandidate(applicationId, candidateName) {
      window.location.href = '../chat-sms/?application_id=' + applicationId;
    }

    // Close modal on ESC
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        closeApplicationModal();
      }
    });

    // Close modal on overlay click
    document.querySelectorAll('.modal').forEach(modal => {
      modal.addEventListener('click', (e) => {
        if (e.target === modal) {
          modal.classList.remove('active');
          document.body.style.overflow = 'auto';
        }
      });
    });
  </script>

</body>
</html>
