<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Tools — For Employers | TalentScout AI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../styles/global.css">
  <link rel="stylesheet" href="../../styles/page-layout.css">
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

    .nav-links { display: flex; list-style: none; gap: 0.2rem; }

    .nav-links a {
      padding: 0.2rem 0.75rem;
      font-size: 0.84rem; font-weight: 500; color: rgba(255,255,255,0.8);
      transition: color 0.2s, border-bottom 0.2s;
      position: relative;
      padding-bottom: 0.4rem;
    }

    .nav-links a:hover {
      color: #fff;
      font-weight: 600;
    }

    .nav-links a.active {
      color: #fff;
      font-weight: 600;
      border-bottom: 2.5px solid #fff;
    }

    .nav-actions { display: flex; align-items: center; gap: 0.65rem; }
    .nav-user { font-size: 0.82rem; color: rgba(255,255,255,0.75); transition: color 0.4s; }

    .btn-ghost {
      padding: 0.42rem 1.1rem; border-radius: var(--radius-pill);
      border: 1.5px solid rgba(255,255,255,0.3); color: #fff;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 0.83rem; font-weight: 500; background: transparent;
      cursor: pointer; transition: all 0.2s; display: inline-block;
    }

    .btn-ghost:hover { background: rgba(255,255,255,0.15); color: #fff; }

    .btn-solid {
      padding: 0.46rem 1.25rem; border-radius: var(--radius-pill);
      background: rgba(255,255,255,0.2);
      color: #fff; font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 0.83rem; font-weight: 700; border: 1.5px solid rgba(255,255,255,0.4);
      cursor: pointer; display: inline-flex; align-items: center; gap: 0.3rem;
      transition: all 0.25s var(--ease);
    }

    .btn-solid:hover { background: rgba(255,255,255,0.3); border-color: rgba(255,255,255,0.5); }

    .modules-section { padding: 3rem 2.5rem; background: white; margin-top: 66px; }
    .modules-inner { max-width: 1200px; margin: 0 auto; }
    .modules-header { margin-bottom: 2.5rem; }
    .section-label {
      font-size: 0.85rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase;
      color: var(--sage-dark); margin-bottom: 0.75rem;
    }
    .section-title {
      font-family: 'Lora', serif; font-size: 2rem; font-weight: 700; color: var(--charcoal);
      margin-bottom: 0.75rem; line-height: 1.2;
    }
    .section-subtitle {
      font-size: 1rem; color: var(--text-soft); line-height: 1.7; max-width: 600px;
    }
    .modules-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1.5rem;
    }
    .module-card {
      background: var(--cream);
      border: 1.5px solid var(--cream-warm);
      border-radius: var(--radius-lg);
      padding: 2rem;
      text-decoration: none;
      color: inherit;
      transition: all 0.25s;
      display: block;
    }
    .module-card:hover {
      border-color: var(--sage);
      box-shadow: var(--shadow-soft);
      transform: translateY(-4px);
      background: white;
    }
    .module-icon {
      font-size: 2.5rem;
      margin-bottom: 1rem;
      display: block;
    }
    .module-title {
      font-family: 'Lora', serif;
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--charcoal);
      margin-bottom: 0.5rem;
    }
    .module-desc {
      font-size: 0.87rem;
      color: var(--text-soft);
      line-height: 1.6;
      margin-bottom: 1rem;
    }
    .module-link {
      display: inline-flex;
      align-items: center;
      gap: 0.3rem;
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--sage);
      text-decoration: none;
      transition: color 0.15s;
    }
    .module-link:hover { color: var(--sage-dark); }
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <a href="../index.php" class="nav-logo">
    <div class="nav-logo-mark">TS</div>
    <span>Talent<em>Scout</em> AI</span>
  </a>
  <ul class="nav-links">
    <li><a href="../index.php">Home</a></li>
    <li><a href="./post-jobs/" class="active">Post Jobs</a></li>
    <li><a href="./employee-finder/">Find Talent</a></li>
    <li><a href="./applicant-tracking/">Hiring Pipeline</a></li>
    <li><a href="./chat-sms/">Messages</a></li>
  </ul>
  <div class="nav-right">
    <?php if (isset($_SESSION['employer_id'])): ?>
      <span class="nav-user">Welcome, <?php echo htmlspecialchars($_SESSION['employer_name'] ?? 'Employer'); ?></span>
      <a href="../logout.php" class="btn-ghost">Logout</a>
    <?php else: ?>
      <a href="../login.php" class="btn-ghost">Login</a>
      <a href="../signup.php" class="btn-solid">Get Started</a>
    <?php endif; ?>
  </div>
</nav>

<!-- MODULES SECTION -->
<div class="modules-section">
  <div class="modules-inner">
    <div class="modules-header">
      <div class="section-label">Your Tools</div>
      <h2 class="section-title">Employer Modules</h2>
      <p class="section-subtitle">Everything you need to post jobs, find talent, screen candidates, and manage your hiring pipeline.</p>
    </div>

    <div class="modules-grid">
      <!-- Post Jobs -->
      <a href="./post-jobs/" class="module-card">
        <span class="module-icon">📝</span>
        <div class="module-title">Post Job Listings</div>
        <div class="module-desc">Create and publish job postings. Reach pre-vetted candidates across all Nasugbu barangays instantly.</div>
        <span class="module-link">Post Jobs →</span>
      </a>

      <!-- Employee Finder / Talent Search -->
      <a href="./employee-finder/" class="module-card">
        <span class="module-icon">🔍</span>
        <div class="module-title">Employee Finder</div>
        <div class="module-desc">Search our talent pool by skills, experience, and location. Find candidates that match your job requirements.</div>
        <span class="module-link">Search Talent →</span>
      </a>

      <!-- Blind Hiring -->
      <a href="./blind-hiring/" class="module-card">
        <span class="module-icon">🫥</span>
        <div class="module-title">Blind Hiring</div>
        <div class="module-desc">Screen candidates anonymously by skills alone. Reduce bias and ensure fair, merit-based candidate evaluation.</div>
        <span class="module-link">Learn More →</span>
      </a>

      <!-- Application Tracker -->
      <a href="./applicant-tracking/" class="module-card">
        <span class="module-icon">📊</span>
        <div class="module-title">Job Application Tracker</div>
        <div class="module-desc">Manage your entire hiring pipeline. Track candidates from application through interview to successful hire.</div>
        <span class="module-link">Manage Applicants →</span>
      </a>

      <!-- Chat & SMS -->
      <a href="./chat-sms/" class="module-card">
        <span class="module-icon">💬</span>
        <div class="module-title">Chat & SMS</div>
        <div class="module-desc">Communicate directly with candidates. Schedule interviews, send updates, and close hires faster.</div>
        <span class="module-link">Start Messaging →</span>
      </a>
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
          <li><a href="../../employees/modules/job-postings/">Browse Jobs</a></li>
          <li><a href="../../employees/modules/ai-matching/">AI Matching</a></li>
          <li><a href="../../employees/modules/skill-gap-analysis/">Skill Analysis</a></li>
          <li><a href="../../employees/modules/applicant-tracking/">Track Applications</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>For Employers</h4>
        <ul>
          <li><a href="../index.php">Post Jobs</a></li>
          <li><a href="./blind-hiring/">Blind Hiring</a></li>
          <li><a href="./employee-finder/">Find Talent</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Resources</h4>
        <ul>
          <li><a href="../../index.php">Home</a></li>
          <li><a href="#">About</a></li>
          <li><a href="#">Contact</a></li>
          <li><a href="#">Privacy Policy</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© 2026 TalentScout AI — PESO Nasugbu, Batangas</span>
      <span>Connecting Employers with Local Talent</span>
    </div>
  </div>
</footer>

</body>
</html>
