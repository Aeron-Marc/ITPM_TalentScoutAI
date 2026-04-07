<?php 
session_start();
require_once('../database/db.php');

// Get database connection
$conn = getConnection();

// Fetch statistics from database
$stats = [
  'total_applicants' => 0,
  'active_jobs' => 0,
  'ai_matches' => 0,
  'successful_hires' => 0,
  'employers' => 0
];

// Total applicants
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM employee");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['total_applicants'] = $row['count'];
$stmt->close();

// Active jobs
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM job_post WHERE application_deadline >= CURDATE()");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['active_jobs'] = $row['count'];
$stmt->close();

// Successful hires (applications with 'hired' or positive status)
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM application WHERE status IN ('Interview Scheduled', 'Offer Received')");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['successful_hires'] = $row['count'];
$stmt->close();

// AI Matches (approximate as 70% of applications)
$stats['ai_matches'] = intval($stats['successful_hires'] * 1.5);

// Total employers
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM employer");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['employers'] = $row['count'];
$stmt->close();

// Fetch application status distribution for donut
$status_dist = ['Pending' => 0, 'Interview Scheduled' => 0, 'Matched' => 0, 'Offer Received' => 0];
$stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM application GROUP BY status");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  if (isset($status_dist[$row['status']])) {
    $status_dist[$row['status']] = $row['count'];
  }
}
$stmt->close();

// Fetch recent applications
$applications = [];
$stmt = $conn->prepare("SELECT 
  e.first_name as firstName, 
  e.last_name as lastName,
  e.address,
  jp.title as position,
  a.status,
  a.application_date
FROM application a
JOIN employee e ON a.employee_id = e.employee_id
JOIN job_post jp ON a.job_post_id = jp.job_post_id
ORDER BY a.application_date DESC
LIMIT 15");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $applications[] = $row;
}
$stmt->close();

// Fetch barangay distribution
$barangays = [];
$stmt = $conn->prepare("SELECT address, COUNT(*) as count FROM employee WHERE address IS NOT NULL AND address != '' GROUP BY address ORDER BY count DESC LIMIT 8");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $barangays[] = $row;
}
$stmt->close();

// Calculate max barangay count for percentage
$max_barangay = !empty($barangays) ? $barangays[0]['count'] : 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PESO Admin Dashboard – TalentScout AI</title>
  <link rel="stylesheet" href="../styles/global.css">
  <style>
    body { background: #EEFFF9; }

    /* Admin Layout */
    .admin-wrapper { display: flex; min-height: calc(100vh - var(--nav-height)); }

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
      color: rgba(255,255,255,0.45);
      padding: 0 1.25rem;
      margin-bottom: 0.5rem;
      margin-top: 1.25rem;
    }
    .sidebar-menu-label:first-child { margin-top: 0; }
    .sidebar-link {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.7rem 1.25rem;
      font-size: 0.88rem;
      font-weight: 500;
      color: rgba(255,255,255,0.72);
      text-decoration: none;
      transition: all 0.2s;
    }
    .sidebar-link:hover { background: rgba(255,255,255,0.08); color: white; }
    .sidebar-link.active { background: rgba(152,251,203,0.15); color: #98FBCB; font-weight: 600; border-right: 3px solid #98FBCB; }
    .sidebar-link .icon { font-size: 1rem; }
    .sidebar-divider { border: none; border-top: 1px solid rgba(255,255,255,0.1); margin: 0.75rem 1.25rem; }

    /* Main Content */
    .admin-content { flex: 1; padding: 2rem; overflow-x: hidden; }

    /* Admin page header */
    .admin-page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.75rem; }
    .admin-page-title { font-size: 1.5rem; font-weight: 800; color: var(--text-dark); }
    .admin-page-sub { font-size: 0.88rem; color: var(--text-light); margin-top: 0.2rem; }
    .admin-actions { display: flex; gap: 0.75rem; }

    /* Stats Grid */
    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.25rem; margin-bottom: 1.75rem; }
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
      top: 0; left: 0; right: 0;
      height: 4px;
    }
    .kpi-green::before  { background: var(--primary-dark); }
    .kpi-blue::before   { background: #63E6B3; }
    .kpi-yellow::before { background: #63E6B3; }
    .kpi-purple::before { background: #1E9E86; }
    .kpi-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; }
    .kpi-icon { font-size: 1.5rem; }
    .kpi-trend {
      font-size: 0.78rem;
      font-weight: 600;
      padding: 0.2rem 0.55rem;
      border-radius: 100px;
    }
    .trend-pos { background: #D4EDDA; color: #155724; }
    .trend-neg { background: #F8D7DA; color: #721c24; }
    .kpi-value { font-size: 2.2rem; font-weight: 800; color: var(--text-dark); line-height: 1; margin-bottom: 0.3rem; }
    .kpi-label { font-size: 0.85rem; color: var(--text-light); }
    .kpi-sub { font-size: 0.78rem; color: var(--text-light); margin-top: 0.5rem; }

    /* Two-column mid section */
    .mid-grid { display: grid; grid-template-columns: 1fr 340px; gap: 1.5rem; margin-bottom: 1.75rem; }

    /* Chart */
    .chart-card {
      background: white;
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.5rem;
    }
    .chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
    .chart-title { font-size: 1rem; font-weight: 700; color: var(--text-dark); }
    .chart-area { display: flex; align-items: flex-end; gap: 0.6rem; height: 180px; padding-bottom: 0.5rem; border-bottom: 2px solid var(--border); }
    .bar-group { display: flex; flex-direction: column; align-items: center; gap: 0.3rem; flex: 1; }
    .bar-wrap { display: flex; gap: 3px; align-items: flex-end; height: 100%; }
    .bar {
      border-radius: 4px 4px 0 0;
      width: 14px;
      transition: opacity 0.2s;
    }
    .bar:hover { opacity: 0.8; }
    .bar-a { background: var(--primary-dark); }
    .bar-b { background: #98FBCB; }
    .bar-lbl { font-size: 0.72rem; color: var(--text-light); }
    .chart-legend { display: flex; gap: 1rem; margin-top: 0.75rem; font-size: 0.8rem; color: var(--text-light); }
    .legend-dot { width: 10px; height: 10px; border-radius: 2px; margin-right: 0.3rem; }

    /* Donut Chart (CSS only) */
    .donut-container { display: flex; align-items: center; gap: 1.5rem; }
    .donut {
      width: 120px; height: 120px;
      border-radius: 50%;
      background: conic-gradient(
        var(--primary-dark) 0% 35%,
        #63E6B3 35% 62%,
        #63E6B3 62% 80%,
        #1E9E86 80% 100%
      );
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
    }
    .donut-inner {
      width: 80px; height: 80px;
      background: white;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 0.75rem;
      font-weight: 700;
      color: var(--text-mid);
      text-align: center;
    }
    .donut-legend { flex: 1; }
    .dl-item { display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; margin-bottom: 0.5rem; }
    .dl-dot { width: 10px; height: 10px; border-radius: 2px; flex-shrink: 0; }
    .dl-label { color: var(--text-mid); flex: 1; }
    .dl-val { font-weight: 700; color: var(--text-dark); }

    /* Activity Feed */
    .activity-card {
      background: white;
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.25rem;
    }
    .activity-feed { display: flex; flex-direction: column; gap: 0; }
    .feed-item {
      display: flex;
      align-items: flex-start;
      gap: 0.75rem;
      padding: 0.85rem 0;
      border-bottom: 1px solid var(--border);
    }
    .feed-item:last-child { border-bottom: none; }
    .feed-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; margin-top: 0.4rem; }
    .feed-text { font-size: 0.87rem; color: var(--text-dark); flex: 1; }
    .feed-time { font-size: 0.78rem; color: var(--text-light); white-space: nowrap; }

    /* Table Section */
    .table-section { margin-bottom: 1.75rem; }
    .table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
    .table-title { font-size: 1rem; font-weight: 700; color: var(--text-dark); }
    .avatar-name { display: flex; align-items: center; gap: 0.6rem; }
    .small-avatar {
      width: 32px; height: 32px;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 0.72rem;
      font-weight: 700;
      flex-shrink: 0;
    }

    /* Barangay bar chart */
    .brgy-bar { margin-bottom: 0.75rem; }
    .brgy-bar-header { display: flex; justify-content: space-between; font-size: 0.83rem; margin-bottom: 0.3rem; }
    .brgy-bar-name { color: var(--text-mid); font-weight: 500; }
    .brgy-bar-count { font-weight: 700; color: var(--primary-darker); }
    .brgy-fill { height: 8px; background: linear-gradient(90deg, var(--primary-dark), var(--primary-mid)); border-radius: 100px; }

    /* Quick actions */
    .quick-actions { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem; }
    .quick-action-btn {
      background: var(--bg-light);
      border: 1.5px solid var(--border);
      border-radius: var(--radius-sm);
      padding: 1rem;
      text-align: center;
      font-size: 0.87rem;
      font-weight: 600;
      color: var(--text-dark);
      cursor: pointer;
      text-decoration: none;
      display: block;
      transition: all 0.2s;
    }
    .quick-action-btn:hover { border-color: var(--primary-dark); background: white; }
    .quick-action-btn .qa-icon { font-size: 1.4rem; margin-bottom: 0.35rem; display: block; }

    /* Bottom grid */
    .bottom-grid { display: grid; grid-template-columns: 1fr 320px; gap: 1.5rem; }
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
  </ul>
</nav>

<!-- ADMIN WRAPPER -->
<div class="admin-wrapper">

  <!-- SIDEBAR -->
  <aside class="admin-sidebar">
    <div class="sidebar-menu-label">Overview</div>
    <a href="#" class="sidebar-link active"><span class="icon">📊</span> Dashboard</a>
    <a href="./modules/analytics/" class="sidebar-link"><span class="icon">📊</span> Analytics</a>

    <div class="sidebar-menu-label">Management</div>
    <a href="./modules/employer-management/" class="sidebar-link"><span class="icon">🏢</span> Employer Management</a>
    <a href="./modules/employee-management/" class="sidebar-link"><span class="icon">👥</span> Employee Management</a>
    <a href="./modules/application-tracking/" class="sidebar-link"><span class="icon">📋</span> Application Tracking</a>
  </aside>

  <!-- MAIN CONTENT -->
  <main class="admin-content">

    <!-- PAGE HEADER -->
    <div class="admin-page-header">
      <div>
        <div class="admin-page-title">PESO Admin Dashboard</div>
        <div class="admin-page-sub">Nasugbu, Batangas • <?php echo date('F j, Y'); ?> • Updated just now</div>
      </div>
      <div class="admin-actions">
        <a href="#" class="btn btn-outline">🔤 Export Report</a>
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
      <div class="kpi-card kpi-yellow">
        <div class="kpi-header">
          <span class="kpi-icon">🤖</span>
          <span class="kpi-trend trend-pos">87%</span>
        </div>
        <div class="kpi-value"><?php echo $stats['ai_matches']; ?></div>
        <div class="kpi-label">AI Matches Made</div>
        <div class="kpi-sub">This month</div>
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
          <div class="chart-title">Monthly Applications vs. Hires</div>
          <select class="input select" style="width:140px;font-size:0.82rem;padding:0.4rem 0.8rem;">
            <option>Last 7 months</option>
            <option>Last 12 months</option>
          </select>
        </div>
        <div class="chart-area">
          <div class="bar-group">
            <div class="bar-wrap">
              <div class="bar bar-a" style="height:80px;"></div>
              <div class="bar bar-b" style="height:35px;"></div>
            </div>
            <span class="bar-lbl">Aug</span>
          </div>
          <div class="bar-group">
            <div class="bar-wrap">
              <div class="bar bar-a" style="height:100px;"></div>
              <div class="bar bar-b" style="height:50px;"></div>
            </div>
            <span class="bar-lbl">Sep</span>
          </div>
          <div class="bar-group">
            <div class="bar-wrap">
              <div class="bar bar-a" style="height:90px;"></div>
              <div class="bar bar-b" style="height:55px;"></div>
            </div>
            <span class="bar-lbl">Oct</span>
          </div>
          <div class="bar-group">
            <div class="bar-wrap">
              <div class="bar bar-a" style="height:120px;"></div>
              <div class="bar bar-b" style="height:65px;"></div>
            </div>
            <span class="bar-lbl">Nov</span>
          </div>
          <div class="bar-group">
            <div class="bar-wrap">
              <div class="bar bar-a" style="height:140px;"></div>
              <div class="bar bar-b" style="height:80px;"></div>
            </div>
            <span class="bar-lbl">Dec</span>
          </div>
          <div class="bar-group">
            <div class="bar-wrap">
              <div class="bar bar-a" style="height:110px;"></div>
              <div class="bar bar-b" style="height:70px;"></div>
            </div>
            <span class="bar-lbl">Jan</span>
          </div>
          <div class="bar-group">
            <div class="bar-wrap">
              <div class="bar bar-a" style="height:155px;"></div>
              <div class="bar bar-b" style="height:100px;"></div>
            </div>
            <span class="bar-lbl">Mar</span>
          </div>
        </div>
        <div class="chart-legend">
          <span style="display:flex;align-items:center;"><span class="legend-dot" style="background:var(--primary-dark);"></span> Applications</span>
          <span style="display:flex;align-items:center;"><span class="legend-dot" style="background:#98FBCB;"></span> Hires</span>
        </div>
      </div>

      <div class="chart-card">
        <div class="chart-header">
          <div class="chart-title">Applicants by Status</div>
        </div>
        <div class="donut-container">
          <div class="donut">
            <div class="donut-inner"><?php echo $stats['total_applicants']; ?><br>Total</div>
          </div>
          <div class="donut-legend">
            <div class="dl-item"><span class="dl-dot" style="background:var(--primary-dark);"></span><span class="dl-label">Pending</span><span class="dl-val"><?php echo $status_dist['Pending']; ?></span></div>
            <div class="dl-item"><span class="dl-dot" style="background:#63E6B3;"></span><span class="dl-label">Interview</span><span class="dl-val"><?php echo $status_dist['Interview Scheduled']; ?></span></div>
            <div class="dl-item"><span class="dl-dot" style="background:#63E6B3;"></span><span class="dl-label">Matched</span><span class="dl-val"><?php echo $status_dist['Matched']; ?></span></div>
            <div class="dl-item"><span class="dl-dot" style="background:#1E9E86;"></span><span class="dl-label">Offer</span><span class="dl-val"><?php echo $status_dist['Offer Received']; ?></span></div>
          </div>
        </div>

        <hr class="divider">

        <div style="font-size:0.85rem;font-weight:700;color:var(--text-mid);margin-bottom:0.75rem;">Quick Actions</div>
        <div class="quick-actions">
          <a href="../employers/modules/post-jobs/" class="quick-action-btn"><span class="qa-icon">📋</span>Post Job</a>
          <a href="./modules/application-tracking/" class="quick-action-btn"><span class="qa-icon">📋</span>Track Apps</a>
          <a href="./modules/analytics/" class="quick-action-btn"><span class="qa-icon">📊</span>Analytics</a>
          <a href="#" class="quick-action-btn"><span class="qa-icon">👥</span>Users</a>
        </div>
      </div>
    </div>

    <!-- RECENT APPLICATIONS TABLE + ACTIVITY -->
    <div class="bottom-grid">
      <div class="table-section">
        <div class="table-header">
          <div class="table-title">Recent Applications</div>
          <div style="display:flex;gap:0.5rem;">
            <input type="text" id="searchInput" class="input" placeholder="Search applicant or position..." style="width:250px;font-size:0.85rem;padding:0.5rem 0.85rem;">
            <a href="./modules/application-tracking/" class="btn btn-outline" style="padding:0.5rem 1rem;font-size:0.85rem;">View All →</a>
          </div>
        </div>
        <div class="table-wrap">
          <table class="table" id="applicationsTable">
            <thead>
              <tr>
                <th>Applicant</th>
                <th>Position</th>
                <th>Location</th>
                <th>Status</th>
                <th>Applied</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($applications) > 0): ?>
                <?php foreach ($applications as $app): 
                  $initials = strtoupper(substr($app['firstName'], 0, 1) . substr($app['lastName'], 0, 1));
                  $status_class = 'badge-gray';
                  if (strtolower($app['status']) == 'matched') $status_class = 'badge-blue';
                  elseif (strtolower($app['status']) == 'interview scheduled') $status_class = 'badge-yellow';
                  elseif (strtolower($app['status']) == 'offer received') $status_class = 'badge-green';
                ?>
                <tr class="app-row" data-search="<?php echo strtolower($app['first_name'] . ' ' . $app['last_name'] . ' ' . $app['position']); ?>">
                  <td>
                    <div class="avatar-name">
                      <div class="small-avatar" style="background:#E4FBF3;color:#0F6E5E;"><?php echo $initials; ?></div>
                      <span><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></span>
                    </div>
                  </td>
                  <td><?php echo htmlspecialchars($app['position']); ?></td>
                  <td><?php echo htmlspecialchars($app['location'] ?? '-'); ?></td>
                  <td><span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars(ucfirst($app['status'])); ?></span></td>
                  <td><?php echo date('M j, Y', strtotime($app['application_date'])); ?></td>
                </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="5" style="text-align:center;color:var(--text-light);padding:2rem;">No recent applications</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- BARANGAYS -->
      <div>
        <div class="chart-card">
          <div class="chart-title" style="margin-bottom:1rem;">Top Barangays by Applicants</div>
          <?php foreach ($barangays as $brgy): 
            $percentage = ($brgy['count'] / $max_barangay) * 100;
          ?>
          <div class="brgy-bar">
            <div class="brgy-bar-header">
              <span class="brgy-bar-name"><?php echo htmlspecialchars($brgy['address']); ?></span>
              <span class="brgy-bar-count"><?php echo $brgy['count']; ?></span>
            </div>
            <div class="progress-bar"><div class="brgy-fill" style="width:<?php echo $percentage; ?>%;"></div></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

  </main>
</div>

<script>
  // Search functionality
  document.getElementById('searchInput').addEventListener('keyup', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.app-row');
    
    rows.forEach(row => {
      const searchData = row.getAttribute('data-search');
      if (searchData.includes(searchTerm)) {
        row.style.display = '';
      } else {
        row.style.display = 'none';
      }
    });
  });
</script>

</body>
</html>
