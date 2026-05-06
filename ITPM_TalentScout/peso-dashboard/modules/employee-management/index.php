<?php
session_start();
require_once __DIR__ . '/../../auth.php';
peso_require_admin('../../login.php');
require_once '../../../database/db.php';

$stats = array(
  'total' => 0,
  'with_applications' => 0,
  'completed_resume' => 0,
  'active' => 0
);

$employees = array();

try {
  $conn = getConnection();

  // Get employee statistics
  $sql = "SELECT COUNT(*) as total FROM employee";
  $result = $conn->query($sql);
  if ($result && $row = $result->fetch_assoc()) {
    $stats['total'] = $row['total'];
  }

  // Employees with applications
  $sql = "SELECT COUNT(DISTINCT employee_id) as count FROM application";
  $result = $conn->query($sql);
  if ($result && $row = $result->fetch_assoc()) {
    $stats['with_applications'] = $row['count'];
  }

  // Employees with completed resume
  $sql = "SELECT COUNT(DISTINCT e.employee_id) as count FROM employee e";
  $result = $conn->query($sql);
  if ($result && $row = $result->fetch_assoc()) {
    $stats['completed_resume'] = $row['count'];
  }

  // Active employees
  $sql = "SELECT COUNT(*) as count FROM employee WHERE is_active = 1";
  $result = $conn->query($sql);
  if ($result && $row = $result->fetch_assoc()) {
    $stats['active'] = $row['count'];
  }

  // Get all employees with their info
  $sql = "SELECT 
    e.employee_id,
    e.first_name as firstName,
    e.last_name as lastName,
    e.email,
    e.address as location,
    CURDATE() as created_at,
    e.is_active as status,
    COUNT(DISTINCT a.application_id) as app_count,
    COUNT(DISTINCT es.employee_skill_id) as skill_count
  FROM employee e
  LEFT JOIN application a ON e.employee_id = a.employee_id
  LEFT JOIN employee_skill es ON e.employee_id = es.employee_id
  GROUP BY e.employee_id
  ORDER BY e.employee_id DESC
  LIMIT 100";

  $result = $conn->query($sql);

  if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $employees[] = $row;
    }
  }

  $conn->close();
} catch (Exception $e) {
  error_log("Employee Management Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Employee Management – TalentScout AI</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --green:       #3d6b50;
      --green-dark:  #2d5040;
      --green-deeper:#1e3a2e;
      --green-light: #5a8a68;
      --mint:        #e8f5ee;
      --mint-mid:    #c8e6d4;
      --mint-deep:   #a8d4b8;
      --gold:        #c8a46a;
      --gold-light:  #fef3d0;
      --gold-text:   #8a6030;
      --blue:        #3a7cbf;
      --blue-light:  #dce8f8;
      --blue-text:   #185fa5;
      --teal:        #1a8a6e;
      --teal-light:  #d4f0e6;
      --red:         #c0392b;
      --red-light:   #fde8e8;
      --bg:          #f0faf4;
      --bg-card:     #ffffff;
      --border:      #d4eddf;
      --text-main:   #1a2e22;
      --text-mid:    #3d5445;
      --text-soft:   #5a8a68;
      --text-muted:  #7a9a82;
      --shadow-sm:   0 2px 8px rgba(45,80,64,0.07);
      --shadow-md:   0 6px 24px rgba(45,80,64,0.10);
      --shadow-lg:   0 12px 40px rgba(45,80,64,0.14);
      --radius-sm:   8px;
      --radius-md:   12px;
      --radius-lg:   16px;
      --radius-xl:   20px;
    }

    html { scroll-behavior: smooth; }
    body {
      font-family: 'Poppins', sans-serif;
      background: var(--bg);
      color: var(--text-main);
      min-height: 100vh;
    }
    a { text-decoration: none; color: inherit; }

    /* ── SIDEBAR ── */
    .sidebar {
      position: fixed; top: 0; left: 0; bottom: 0;
      width: 240px; background: var(--green-deeper);
      display: flex; flex-direction: column;
      z-index: 200; transition: transform 0.35s cubic-bezier(.22,1,.36,1);
    }

    .sidebar-logo {
      padding: 22px 20px 18px;
      border-bottom: 1px solid rgba(255,255,255,0.08);
      display: flex; align-items: center; gap: 10px;
    }

    .logo-mark {
      width: 36px; height: 36px;
      background: linear-gradient(135deg, var(--green-light), var(--green));
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-size: 11px; font-weight: 700; color: #fff; letter-spacing: 0.05em;
      flex-shrink: 0;
    }

    .logo-text {
      font-size: 14px; font-weight: 700; color: #fff; line-height: 1.2;
    }
    .logo-text span { color: var(--mint-deep); }
    .logo-sub { font-size: 9px; color: rgba(255,255,255,0.4); letter-spacing: 0.06em; }

    .sidebar-nav { flex: 1; padding: 16px 12px; overflow-y: auto; }

    .nav-section-label {
      font-size: 9px; font-weight: 700; letter-spacing: 0.15em;
      text-transform: uppercase; color: rgba(255,255,255,0.3);
      padding: 14px 10px 6px;
    }

    .nav-item {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 12px; border-radius: var(--radius-md);
      font-size: 13px; font-weight: 500; color: rgba(255,255,255,0.65);
      cursor: pointer; transition: all 0.2s; margin-bottom: 2px;
      text-decoration: none;
    }

    .nav-item i { width: 18px; text-align: center; font-size: 14px; }

    .nav-item:hover { background: rgba(255,255,255,0.08); color: #fff; }
    .nav-item.active { background: rgba(168,212,184,0.18); color: #fff; font-weight: 600; }
    .nav-item.active i { color: var(--mint-deep); }

    .nav-badge {
      margin-left: auto; background: #c0392b;
      color: #fff; font-size: 9px; font-weight: 700;
      padding: 2px 7px; border-radius: 20px; min-width: 18px; text-align: center;
    }

    .nav-badge.gold { background: var(--gold); color: var(--green-deeper); }

    .sidebar-footer {
      padding: 14px 12px;
      border-top: 1px solid rgba(255,255,255,0.08);
    }

    .sidebar-user {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 12px; border-radius: var(--radius-md);
      background: rgba(255,255,255,0.06);
    }

    .sidebar-avatar {
      width: 34px; height: 34px; border-radius: 50%;
      background: linear-gradient(135deg, var(--green-light), var(--teal));
      display: flex; align-items: center; justify-content: center;
      font-size: 12px; font-weight: 700; color: #fff; flex-shrink: 0;
    }

    .sidebar-user-info { flex: 1; overflow: hidden; }
    .sidebar-user-name { font-size: 12px; font-weight: 600; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .sidebar-user-role { font-size: 10px; color: rgba(255,255,255,0.4); }

    /* ── MAIN CONTENT ── */
    .content {
      margin-left: 240px;
      min-height: 100vh;
      padding: 24px;
      max-width: 1400px;
    }

    /* ── PAGE HEADER ── */
    .page-header {
      display: flex; align-items: flex-start;
      justify-content: space-between; flex-wrap: wrap; gap: 12px;
      margin-bottom: 22px;
    }

    .page-header h1 { font-size: 20px; font-weight: 700; color: var(--text-main); }
    .page-header p  { font-size: 12px; color: var(--text-muted); margin-top: 3px; }

    /* Card styles */
    .card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 20px;
      margin-bottom: 18px;
      box-shadow: var(--shadow-sm);
    }

    .card-title {
      font-size: 14px;
      font-weight: 700;
      color: var(--text-main);
      margin-bottom: 16px;
    }

    /* Stats Grid */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 14px; margin-bottom: 18px;
    }

    .kpi-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 18px 20px;
      display: flex; flex-direction: column; gap: 6px;
      transition: transform 0.25s, box-shadow 0.25s;
      cursor: pointer; position: relative; overflow: hidden;
      box-shadow: var(--shadow-sm);
    }

    .kpi-card::after {
      content: '';
      position: absolute; top: 0; left: 0; right: 0; height: 4px;
      border-radius: var(--radius-lg) var(--radius-lg) 0 0;
      background: var(--green);
    }

    .kpi-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }

    .kpi-value { font-size: 28px; font-weight: 700; color: var(--text-main); }
    .kpi-label { font-size: 12px; font-weight: 500; color: var(--text-muted); }

    /* Table styles */
    .table-wrapper {
      overflow-x: auto;
      max-height: 500px;
      overflow-y: auto;
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
    }

    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 13px;
    }

    thead {
      position: sticky;
      top: 0;
      background: #F8FAFB;
      z-index: 1;
    }

    th {
      padding: 12px;
      text-align: left;
      font-weight: 700;
      color: var(--text-mid);
      border-bottom: 1px solid var(--border);
      font-size: 12px;
    }

    td {
      padding: 12px;
      border-bottom: 1px solid var(--border);
    }

    tr:hover {
      background: #FAFAFA;
    }

    .stat-badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 12px;
      font-size: 11px;
      font-weight: 600;
      background: var(--teal-light);
      color: var(--teal);
    }

    .search-input {
      padding: 8px 12px;
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      font-family: 'Poppins', sans-serif;
      font-size: 13px;
      color: var(--text-main);
      transition: border-color 0.2s;
    }

    .search-input:focus {
      outline: none;
      border-color: var(--green);
      box-shadow: 0 0 0 3px rgba(61,107,80,0.1);
    }

    @media (max-width: 900px) {
      .sidebar { transform: translateX(-100%); }
      .content { margin-left: 0; }
      .stats-grid { grid-template-columns: repeat(2, 1fr); }
      .table-wrapper { max-height: 300px; }
    }

    @media (max-width: 600px) {
      .stats-grid { grid-template-columns: 1fr; }
      .page-header { flex-direction: column; }
    }
  </style>
</head>

<body>

<!-- ════ SIDEBAR ════ -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-mark">TS</div>
    <div>
      <div class="logo-text">Talent<span>Scout</span> AI</div>
      <div class="logo-sub">PESO NASUGBU, BATANGAS</div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">Overview</div>
    <a href="../../index.php" class="nav-item">
      <i class="fa-solid fa-chart-pie"></i> Dashboard
    </a>

    <div class="nav-section-label">Management</div>
    <a href="../employer-management/" class="nav-item">
      <i class="fa-solid fa-building"></i> Employers
    </a>
    <a href="./" class="nav-item active">
      <i class="fa-solid fa-users"></i> Job Seekers
    </a>
    <a href="../application-tracking/" class="nav-item">
      <i class="fa-solid fa-clipboard-list"></i> Applications
    </a>

    <div class="nav-section-label">Insights</div>
    <a href="../analytics/" class="nav-item">
      <i class="fa-solid fa-chart-line"></i> Analytics
    </a>

  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="sidebar-avatar">PA</div>
      <div class="sidebar-user-info">
        <div class="sidebar-user-name">PESO Admin</div>
        <div class="sidebar-user-role">Administrator</div>
      </div>
    </div>
  </div>
</aside>

<!-- ════ MAIN CONTENT ════ -->
<main class="content">

  <!-- Page Header -->
  <div class="page-header">
    <div>
      <h1>Employee Management</h1>
      <p>Manage job seeker accounts and profiles</p>
    </div>
  </div>

  <!-- STATS GRID -->
  <div class="stats-grid">
    <div class="kpi-card">
      <div class="kpi-value"><?php echo $stats['total']; ?></div>
      <div class="kpi-label">Total Employees</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-value"><?php echo $stats['with_applications']; ?></div>
      <div class="kpi-label">With Applications</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-value"><?php echo $stats['completed_resume']; ?></div>
      <div class="kpi-label">Resume Completed</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-value"><?php echo $stats['active']; ?></div>
      <div class="kpi-label">Active (30 days)</div>
    </div>
  </div>

  <!-- EMPLOYEES TABLE -->
  <div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
      <div class="card-title" style="margin:0;">All Employees</div>
      <input type="text" id="searchInput" class="search-input" placeholder="Search name, email, or location..." style="width:300px;">
    </div>
        <?php if (!empty($employees)): ?>
          <div class="table-wrapper">
            <table id="employeesTable">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Location</th>
                  <th>Applications</th>
                  <th>Skills</th>
                  <th>Joined</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($employees as $emp): ?>
                  <tr class="emp-row" data-search="<?php echo strtolower($emp['firstName'] . ' ' . $emp['lastName'] . ' ' . $emp['email'] . ' ' . ($emp['location'] ?? '')); ?>">
                    <td><strong><?php echo htmlspecialchars($emp['firstName'] . ' ' . $emp['lastName']); ?></strong></td>
                    <td><?php echo htmlspecialchars($emp['email']); ?></td>
                    <td><?php echo htmlspecialchars($emp['location'] ?? '-'); ?></td>
                    <td><span class="stat-badge"><?php echo $emp['app_count']; ?> app</span></td>
                    <td><span class="stat-badge"><?php echo $emp['skill_count']; ?> skill</span></td>
                    <td><?php echo date('M d, Y', strtotime($emp['created_at'])); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p style="color: #999; text-align: center; padding: 2rem;">No employees found</p>
        <?php endif; ?>
    </div>

</main>

<script>
  // Search functionality
  document.getElementById('searchInput').addEventListener('keyup', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.emp-row');

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

</html>