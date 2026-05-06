<?php
session_start();
require_once __DIR__ . '/../auth.php';
peso_require_admin('../login.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Tools - TalentScout AI</title>
  <link rel="stylesheet" href="../../styles/global.css">
  <link rel="stylesheet" href="../../styles/page-layout.css">
  <style>
    body {
      background: #EEFFF9;
    }

    .modules-container {
      max-width: 1200px;
      margin: 3rem auto;
      padding: 0 2rem;
    }

    .modules-header {
      margin-bottom: 3rem;
      text-align: center;
    }

    .modules-header h1 {
      font-size: 2.5rem;
      color: var(--text-dark);
      margin-bottom: 0.5rem;
    }

    .modules-header p {
      font-size: 1.1rem;
      color: var(--text-muted);
    }

    .modules-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 2rem;
      margin-bottom: 3rem;
    }

    .module-card {
      display: flex;
      flex-direction: column;
      padding: 1.5rem;
      background: white;
      border: 1px solid var(--border-color);
      border-radius: var(--radius);
      text-decoration: none;
      color: var(--text-dark);
      transition: all 0.3s ease;
      box-shadow: var(--shadow-sm);
    }

    .module-card:hover {
      transform: translateY(-4px);
      box-shadow: var(--shadow);
      border-color: var(--primary);
    }

    .module-card:hover .module-link {
      color: var(--primary);
    }

    .module-icon {
      font-size: 2.5rem;
      margin-bottom: 1rem;
    }

    .module-title {
      font-size: 1.3rem;
      font-weight: 600;
      color: var(--primary-darker);
      margin-bottom: 0.5rem;
    }

    .module-desc {
      font-size: 0.9rem;
      color: var(--text-muted);
      margin-bottom: auto;
      padding-bottom: 1rem;
      flex-grow: 1;
    }

    .module-link {
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--primary);
      transition: color 0.2s;
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
      <li><a href="../../employees/index.php">Job Seekers</a></li>
      <li><a href="../../employers/index.php">Employers</a></li>
      <li><a href="../../peso-dashboard/index.php" class="active">Admin</a></li>
      <li><a href="../logout.php">Logout</a></li>
    </ul>
  </nav>

  <div class="modules-container">
    <div class="modules-header">
      <h1>Admin Tools & Controls</h1>
      <p>Manage the TalentScout AI platform, users, and monitor system performance</p>
    </div>

    <div class="modules-grid">
      <a href="../index.php" class="module-card">
        <span class="module-icon">📊</span>
        <div class="module-title">Dashboard Overview</div>
        <div class="module-desc">View key metrics, platform statistics, and system health at a glance.</div>
        <span class="module-link">View Dashboard →</span>
      </a>

      <a href="./employer-management/" class="module-card">
        <span class="module-icon">🏢</span>
        <div class="module-title">Employer Management</div>
        <div class="module-desc">Manage employer accounts, approvals, and registration verification.</div>
        <span class="module-link">Manage Employers →</span>
      </a>

      <a href="./employee-management/" class="module-card">
        <span class="module-icon">👥</span>
        <div class="module-title">Job Seeker Management</div>
        <div class="module-desc">Monitor user activities, profiles, and manage job seeker accounts.</div>
        <span class="module-link">Manage Job Seekers →</span>
      </a>

      <a href="./application-tracking/" class="module-card">
        <span class="module-icon">📋</span>
        <div class="module-title">Application Tracking</div>
        <div class="module-desc">Monitor all job applications, track hiring pipeline, and view hiring metrics.</div>
        <span class="module-link">View Applications →</span>
      </a>

      <a href="./analytics/" class="module-card">
        <span class="module-icon">📈</span>
        <div class="module-title">Analytics & Reports</div>
        <div class="module-desc">Detailed insights on platform usage, job market trends, and user engagement.</div>
        <span class="module-link">View Analytics →</span>
      </a>

      <a href="#" class="module-card">
        <span class="module-icon">⚙️</span>
        <div class="module-title">System Settings</div>
        <div class="module-desc">Configure platform settings, manage integrations, and system configuration.</div>
        <span class="module-link">Settings →</span>
      </a>
    </div>
  </div>

  <footer class="footer">
    <div class="footer-inner">
      <div class="footer-brand">
        <h3>TalentScout AI</h3>
        <p>Intelligent talent matching for the modern workforce</p>
      </div>
      <div class="footer-col">
        <h4>For Job Seekers</h4>
        <a href="../../employees/index.php">Find Jobs</a>
        <a href="../../employees/modules/ai-matching/">AI Matching</a>
      </div>
      <div class="footer-col">
        <h4>For Employers</h4>
        <a href="../../employers/index.php">Home</a>
        <a href="../../employers/modules/post-jobs/">Post Jobs</a>
      </div>
      <div class="footer-col">
        <h4>PESO Admin</h4>
        <a href="../index.php">Dashboard</a>
        <a href="#">Settings</a>
      </div>
    </div>
  </footer>
</body>

</html>