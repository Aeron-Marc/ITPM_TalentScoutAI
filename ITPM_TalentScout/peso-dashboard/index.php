<?php
session_start();
require_once __DIR__ . '/auth.php';
peso_require_admin('login.php');
require_once __DIR__ . '/dashboard-data.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PESO Admin Dashboard – TalentScout AI</title>
  <link rel="stylesheet" href="../styles/global.css">
  <style>
    body {
      background: #EEFFF9;
    }

    /* Admin Layout */
    .admin-wrapper {
      display: flex;
      min-height: calc(100vh - var(--nav-height));
    }

    /* Sidebar */
    .admin-sidebar {
      width: 240px;
      background: var(--primary-darker);
      min-height: 100%;
      padding: 1.5rem 0;
      flex-shrink: 0;
      position: sticky;
      top: var(--nav-height);
      height: calc(100vh - var(--nav-height));
      overflow-y: auto;
    }

    .sidebar-menu-label {
      font-size: 0.72rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1.2px;
      color: rgba(255, 255, 255, 0.45);
      padding: 0 1.25rem;
      margin-bottom: 0.5rem;
      margin-top: 1.25rem;
    }

    .sidebar-menu-label:first-child {
      margin-top: 0;
    }

    .sidebar-link {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.7rem 1.25rem;
      font-size: 0.88rem;
      font-weight: 500;
      color: rgba(255, 255, 255, 0.72);
      text-decoration: none;
      transition: all 0.2s;
    }

    .sidebar-link:hover {
      background: rgba(255, 255, 255, 0.08);
      color: white;
    }

    .sidebar-link.active {
      background: rgba(152, 251, 203, 0.15);
      color: #98FBCB;
      font-weight: 600;
      border-right: 3px solid #98FBCB;
    }

    .sidebar-link .icon {
      font-size: 1rem;
    }

    .sidebar-divider {
      border: none;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      margin: 0.75rem 1.25rem;
    }

    /* Main Content */
    .admin-content {
      flex: 1;
      padding: 2rem;
      overflow-x: hidden;
    }

    /* Admin page header */
    .admin-page-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 1.75rem;
    }

    .admin-page-title {
      font-size: 1.5rem;
      font-weight: 800;
      color: var(--text-dark);
    }

    .admin-page-sub {
      font-size: 0.88rem;
      color: var(--text-light);
      margin-top: 0.2rem;
    }

    .admin-actions {
      display: flex;
      gap: 0.75rem;
    }

    /* Stats Grid */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1.25rem;
      margin-bottom: 1.75rem;
    }

    @media (max-width: 900px) {
      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
      }

      .mid-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 600px) {
      .stats-grid {
        grid-template-columns: 1fr;
      }
    }

    .kpi-card {
      background: white;
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.5rem;
      box-shadow: var(--shadow-sm);
      position: relative;
      overflow: hidden;
    }

    .kpi-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
    }

    .kpi-green::before {
      background: var(--primary-dark);
    }

    .kpi-blue::before {
      background: #63E6B3;
    }

    .kpi-yellow::before {
      background: #63E6B3;
    }

    .kpi-purple::before {
      background: #1E9E86;
    }

    .kpi-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 1rem;
    }

    .kpi-icon {
      font-size: 1.5rem;
    }

    .kpi-trend {
      font-size: 0.78rem;
      font-weight: 600;
      padding: 0.2rem 0.55rem;
      border-radius: 100px;
    }

    .trend-pos {
      background: #D4EDDA;
      color: #155724;
    }

    .trend-neg {
      background: #F8D7DA;
      color: #721c24;
    }

    .kpi-value {
      font-size: 2.2rem;
      font-weight: 800;
      color: var(--text-dark);
      line-height: 1;
      margin-bottom: 0.3rem;
    }

    .kpi-label {
      font-size: 0.85rem;
      color: var(--text-light);
    }

    .kpi-sub {
      font-size: 0.78rem;
      color: var(--text-light);
      margin-top: 0.5rem;
    }

    /* Two-column mid section */
    .mid-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.5rem;
      margin-bottom: 1.75rem;
    }

    /* Chart */
    .chart-card {
      background: white;
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 1.5rem;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
      transition: all 0.2s ease;
    }

    .chart-card:hover {
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
    }

    .chart-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.25rem;
      padding-bottom: 0.75rem;
      border-bottom: 1px solid var(--border);
    }

    .chart-title {
      font-size: 1rem;
      font-weight: 700;
      color: var(--text-dark);
    }

    .chart-area {
      display: flex;
      align-items: flex-end;
      gap: 0.6rem;
      height: 180px;
      padding-bottom: 0.5rem;
      border-bottom: 2px solid var(--border);
    }

    .bar-group {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.3rem;
      flex: 1;
    }

    .bar-wrap {
      display: flex;
      gap: 3px;
      align-items: flex-end;
      height: 100%;
    }

    .bar {
      border-radius: 4px 4px 0 0;
      width: 14px;
      transition: opacity 0.2s;
    }

    .bar:hover {
      opacity: 0.8;
    }

    .bar-a {
      background: var(--primary-dark);
    }

    .bar-b {
      background: #98FBCB;
    }

    .bar-lbl {
      font-size: 0.72rem;
      color: var(--text-light);
    }

    /* Vertical Bar Chart */
    .vbars-area {
      display: flex;
      align-items: flex-end;
      justify-content: space-around;
      height: 200px;
      padding: 1rem 0.5rem 0.5rem;
      border-bottom: 2px solid var(--border);
      background: linear-gradient(180deg, rgba(30, 158, 134, 0.03) 0%, rgba(30, 158, 134, 0.08) 100%);
      border-radius: 8px 8px 0 0;
    }

    .vbar-group {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.4rem;
      flex: 1;
    }

    .vbar-wrap {
      display: flex;
      align-items: flex-end;
      height: 160px;
      width: 100%;
      justify-content: center;
    }

    .vbar {
      width: 32px;
      border-radius: 6px 6px 0 0;
      background: linear-gradient(180deg, var(--primary-dark) 0%, var(--primary-mid) 100%);
      transition: all 0.3s ease;
      box-shadow: 0 -2px 8px rgba(30, 158, 134, 0.25);
    }

    .vbar:hover {
      background: linear-gradient(180deg, var(--primary-darker) 0%, var(--primary-dark) 100%);
      transform: scaleY(1.02);
    }

    .vbar-label {
      font-size: 0.7rem;
      color: var(--text-mid);
      text-align: center;
      max-width: 60px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .vbar-count {
      font-size: 0.75rem;
      font-weight: 700;
      color: var(--primary-darker);
    }

    /* Employer List */
    .employer-list {
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
    }

    .employer-item {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 0.75rem 1rem;
      background: var(--bg-light);
      border-radius: 8px;
      transition: all 0.2s ease;
    }

    .employer-item:hover {
      background: #E8F5E9;
      transform: translateX(4px);
    }

    .employer-rank {
      width: 28px;
      height: 28px;
      border-radius: 50%;
      background: var(--primary-dark);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 0.8rem;
      flex-shrink: 0;
    }

    .employer-info {
      flex: 1;
    }

    .employer-name {
      font-weight: 600;
      color: var(--text-dark);
      font-size: 0.9rem;
    }

    .employer-count {
      font-weight: 800;
      font-size: 1.1rem;
      color: var(--primary-darker);
    }

    .chart-legend {
      display: flex;
      gap: 1rem;
      margin-top: 0.75rem;
      font-size: 0.8rem;
      color: var(--text-light);
    }

    .legend-dot {
      width: 10px;
      height: 10px;
      border-radius: 2px;
      margin-right: 0.3rem;
    }

    /* Donut Chart */
    .donut-container {
      display: flex;
      align-items: center;
      gap: 2rem;
      flex-wrap: wrap;
      justify-content: center;
    }

    .donut {
      width: 140px;
      height: 140px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
      border: 4px solid white;
    }

    .donut-inner {
      width: 90px;
      height: 90px;
      background: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.85rem;
      font-weight: 800;
      color: var(--primary-darker);
      text-align: center;
      line-height: 1.2;
    }

    .donut-legend {
      flex: 1;
      display: flex;
      flex-wrap: wrap;
      gap: 0.6rem;
      justify-content: center;
    }

    .dl-item {
      display: flex;
      align-items: center;
      gap: 0.4rem;
      font-size: 0.75rem;
      min-width: 90px;
      padding: 0.3rem 0.5rem;
      background: var(--bg-light);
      border-radius: 4px;
    }

    .dl-dot {
      width: 8px;
      height: 8px;
      border-radius: 2px;
      flex-shrink: 0;
    }

    .dl-label {
      color: var(--text-mid);
    }

    .dl-val {
      font-weight: 700;
      color: var(--text-dark);
    }

    /* Activity Feed */
    .activity-card {
      background: white;
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.25rem;
    }

    .activity-feed {
      display: flex;
      flex-direction: column;
      gap: 0;
    }

    .feed-item {
      display: flex;
      align-items: flex-start;
      gap: 0.75rem;
      padding: 0.85rem 0;
      border-bottom: 1px solid var(--border);
    }

    .feed-item:last-child {
      border-bottom: none;
    }

    .feed-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      flex-shrink: 0;
      margin-top: 0.4rem;
    }

    .feed-text {
      font-size: 0.87rem;
      color: var(--text-dark);
      flex: 1;
    }

    .feed-time {
      font-size: 0.78rem;
      color: var(--text-light);
      white-space: nowrap;
    }

    /* Table Section */
    .table-section {
      margin-bottom: 1.75rem;
    }

    .table-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem;
    }

    .table-title {
      font-size: 1rem;
      font-weight: 700;
      color: var(--text-dark);
    }

    .avatar-name {
      display: flex;
      align-items: center;
      gap: 0.6rem;
    }

    .small-avatar {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.72rem;
      font-weight: 700;
      flex-shrink: 0;
    }

    /* Table Styles */
    .table-wrap {
      background: white;
      border: 1px solid var(--border);
      border-radius: var(--radius);
      overflow: hidden;
    }

    .table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.9rem;
    }

    .table thead {
      background: var(--bg-light);
      border-bottom: 2px solid var(--border);
    }

    .table th {
      padding: 1rem 1.2rem;
      text-align: left;
      font-weight: 700;
      color: var(--text-dark);
      white-space: nowrap;
    }

    .table tbody tr {
      border-bottom: 1px solid var(--border);
      transition: background-color 0.2s;
    }

    .table tbody tr:hover {
      background-color: var(--bg-lighter);
    }

    .table td {
      padding: 0.9rem 1.2rem;
      color: var(--text-mid);
    }

    .table .badge {
      display: inline-block;
      padding: 0.35rem 0.7rem;
      border-radius: 100px;
      font-size: 0.75rem;
      font-weight: 600;
      white-space: nowrap;
    }

    .badge-gray {
      background: #E8EAED;
      color: #5F6368;
    }

    .badge-blue {
      background: #E3F2FD;
      color: #1976D2;
    }

    .badge-yellow {
      background: #FFF3E0;
      color: #F57C00;
    }

    .badge-green {
      background: #E8F5E9;
      color: #388E3C;
    }

    /* Progress bar for barangays */
    .progress-bar {
      width: 100%;
      height: 8px;
      background: var(--bg-light);
      border-radius: 100px;
      overflow: hidden;
    }

    /* Barangay bar chart */
    .brgy-bar {
      margin-bottom: 0.75rem;
    }

    .brgy-bar-header {
      display: flex;
      justify-content: space-between;
      font-size: 0.83rem;
      margin-bottom: 0.3rem;
    }

    .brgy-bar-name {
      color: var(--text-mid);
      font-weight: 500;
    }

    .brgy-bar-count {
      font-weight: 700;
      color: var(--primary-darker);
    }

    .brgy-fill {
      height: 8px;
      background: linear-gradient(90deg, var(--primary-dark), var(--primary-mid));
      border-radius: 100px;
    }

    /* Bottom grid */
    .bottom-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 1.5rem;
    }
  </style>
</head>

<body>

  <!-- NAVBAR -->
  <nav class="navbar">
    <a href="./index.php" class="nav-logo">
      <div class="nav-logo-icon">TS</div>
      <span class="nav-logo-text">Talent<span>Scout</span> AI</span>
    </a>
    <ul class="nav-links">
      <li><a href="./index.php" class="active">Dashboard</a></li>
      <li><a href="./modules/analytics/">Analytics</a></li>
      <li><a href="./modules/employer-management/">Employers</a></li>
      <li><a href="./modules/employee-management/">Employees</a></li>
      <li><a href="./modules/application-tracking/">Applications</a></li>
      <li><a href="./logout.php">Logout</a></li>
    </ul>
  </nav>

  <!-- ADMIN WRAPPER -->
  <div class="admin-wrapper" style="display:block;">

    <!-- MAIN CONTENT -->
    <main class="admin-content" style="padding:2rem;">

      <!-- PAGE HEADER -->
      <div class="admin-page-header">
        <div>
          <div class="admin-page-title">PESO Admin Dashboard</div>
          <div class="admin-page-sub">Nasugbu, Batangas • <?php echo date('F j, Y'); ?> • Updated just now</div>
        </div>
        <div class="admin-actions">
          <a href="./export-report.php" target="_blank" class="btn btn-outline">🔤 Export Report</a>
          <a href="./modules/analytics/" class="btn btn-primary">+ View Analytics</a>
        </div>
      </div>

      <!-- KPI STATS -->
      <div class="stats-grid">
        <div class="kpi-card kpi-green">
          <div class="kpi-header">
            <span class="kpi-icon">👥</span>
            <span class="kpi-trend trend-pos">↑ +12%</span>
          </div>
          <div class="kpi-value"><?php echo $stats['total_applicants']; ?></div>
          <div class="kpi-label">Total Applicants</div>
          <div class="kpi-sub">+48 new this week</div>
        </div>
        <div class="kpi-card kpi-blue">
          <div class="kpi-header">
            <span class="kpi-icon">📋</span>
            <span class="kpi-trend trend-pos">↑ +8%</span>
          </div>
          <div class="kpi-value"><?php echo $stats['active_jobs']; ?></div>
          <div class="kpi-label">Active Job Posts</div>
          <div class="kpi-sub">12 new posted today</div>
        </div>
        <div class="kpi-card kpi-purple">
          <div class="kpi-header">
            <span class="kpi-icon">🏆</span>
            <span class="kpi-trend trend-pos">↑ +5%</span>
          </div>
          <div class="kpi-value"><?php echo $stats['successful_hires']; ?></div>
          <div class="kpi-label">Successful Hires</div>
          <div class="kpi-sub">This month</div>
        </div>
      </div>

      <!-- CHARTS ROW -->
      <div class="mid-grid">
        <div class="chart-card">
          <div class="chart-header">
            <div class="chart-title">Top Positions by Applications</div>
          </div>
          <?php if (!empty($top_positions)): ?>
            <div class="vbars-area">
              <?php
              $max_pos = !empty($top_positions) ? $top_positions[0]['count'] : 1;
              foreach ($top_positions as $pos):
                $height = ($pos['count'] / $max_pos) * 100;
              ?>
                <div class="vbar-group">
                  <div class="vbar-wrap">
                    <div class="vbar" style="height:<?php echo max($height, 5); ?>%;"></div>
                  </div>
                  <div class="vbar-label"><?php echo htmlspecialchars(substr($pos['title'], 0, 15)); ?></div>
                  <div class="vbar-count"><?php echo $pos['count']; ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div style="color:var(--text-light);text-align:center;padding:2rem;">No position data available</div>
          <?php endif; ?>
        </div>

        <div class="chart-card">
          <div class="chart-header">
            <div class="chart-title">Applications by Status</div>
          </div>
          <div class="donut-container">
            <div class="donut" style="background: <?php echo $donut_gradient; ?>;">
              <div class="donut-inner"><span><?php echo $application_total; ?></span><span style="font-size:0.65rem;font-weight:400;">total</span></div>
            </div>
            <div class="donut-legend">
              <div class="dl-item"><span class="dl-dot" style="background:#6c757d;"></span><span class="dl-label">Pending</span><span class="dl-val"><?php echo $status_dist['Pending']; ?></span></div>
              <div class="dl-item"><span class="dl-dot" style="background:#17a2b8;"></span><span class="dl-label">Applied</span><span class="dl-val"><?php echo $status_dist['Applied']; ?></span></div>
              <div class="dl-item"><span class="dl-dot" style="background:#ffc107;"></span><span class="dl-label">Interview</span><span class="dl-val"><?php echo $status_dist['Interview Scheduled']; ?></span></div>
              <div class="dl-item"><span class="dl-dot" style="background:#6610f2;"></span><span class="dl-label">Matched</span><span class="dl-val"><?php echo $status_dist['Matched']; ?></span></div>
              <div class="dl-item"><span class="dl-dot" style="background:#20c997;"></span><span class="dl-label">Offer</span><span class="dl-val"><?php echo $status_dist['Offer Received']; ?></span></div>
              <div class="dl-item"><span class="dl-dot" style="background:#007bff;"></span><span class="dl-label">Sent</span><span class="dl-val"><?php echo $status_dist['Offer Sent']; ?></span></div>
              <div class="dl-item"><span class="dl-dot" style="background:#fd7e14;"></span><span class="dl-label">Declined</span><span class="dl-val"><?php echo $status_dist['Offer Declined']; ?></span></div>
              <div class="dl-item"><span class="dl-dot" style="background:#28a745;"></span><span class="dl-label">Accepted</span><span class="dl-val"><?php echo $status_dist['Accepted']; ?></span></div>
              <div class="dl-item"><span class="dl-dot" style="background:#dc3545;"></span><span class="dl-label">Rejected</span><span class="dl-val"><?php echo $status_dist['Rejected']; ?></span></div>
              <div class="dl-item"><span class="dl-dot" style="background:#1E9E86;"></span><span class="dl-label">Hired</span><span class="dl-val"><?php echo $status_dist['Hired']; ?></span></div>
            </div>
          </div>
        </div>
      </div>

      <!-- SECOND CHARTS ROW -->
      <div class="mid-grid">
        <div class="chart-card">
          <div class="chart-header">
            <div class="chart-title">Top Employers by Applications</div>
          </div>
          <?php if (!empty($top_employers)): ?>
            <div class="employer-list">
              <?php foreach ($top_employers as $i => $emp): ?>
                <div class="employer-item">
                  <div class="employer-rank"><?php echo $i + 1; ?></div>
                  <div class="employer-info">
                    <div class="employer-name"><?php echo htmlspecialchars($emp['company_name']); ?></div>
                  </div>
                  <div class="employer-count"><?php echo $emp['count']; ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div style="color:var(--text-light);text-align:center;padding:2rem;">No employer data available</div>
          <?php endif; ?>
        </div>

        <!-- BARANGAYS -->
        <div class="chart-card">
          <div class="chart-header">
            <div class="chart-title">Top Barangays by Applicants</div>
          </div>
          <?php foreach ($barangays as $brgy):
            $percentage = ($brgy['count'] / $max_barangay) * 100;
          ?>
            <div class="brgy-bar">
              <div class="brgy-bar-header">
                <span class="brgy-bar-name"><?php echo htmlspecialchars($brgy['address']); ?></span>
                <span class="brgy-bar-count"><?php echo $brgy['count']; ?></span>
              </div>
              <div class="progress-bar">
                <div class="brgy-fill" style="width:<?php echo $percentage; ?>%;"></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

    </main>
  </div>

</body>

</html>