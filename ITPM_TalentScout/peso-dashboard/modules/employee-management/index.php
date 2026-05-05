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
  <title>Employee Management | PESO Admin - TalentScout AI</title>
  <link rel="stylesheet" href="../../../styles/global.css">
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

    /* Stats Grid */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1.25rem;
      margin-bottom: 1.75rem;
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
      background: var(--primary-dark);
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

    /* Content Cards */
    .card {
      background: white;
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.5rem;
      margin-bottom: 1.75rem;
    }

    .card-title {
      font-size: 1rem;
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 1.25rem;
    }

    /* Tables */
    .table-wrapper {
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.87rem;
    }

    th {
      background: #F8FAFB;
      padding: 0.9rem 1rem;
      text-align: left;
      font-weight: 700;
      color: var(--text-dark);
      border-bottom: 1px solid var(--border);
    }

    td {
      padding: 0.9rem 1rem;
      border-bottom: 1px solid var(--border);
    }

    tr:hover {
      background: #FAFAFA;
    }

    .stat-badge {
      display: inline-block;
      padding: 0.3rem 0.7rem;
      border-radius: 3px;
      font-size: 0.75rem;
      background: #F0FDFB;
      color: var(--primary-dark);
      font-weight: 600;
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
      <li><a href="../../index.php">Dashboard</a></li>
      <li><a href="../analytics/">Analytics</a></li>
      <li><a href="../employer-management/">Employers</a></li>
      <li><a href="./" class="active">Employees</a></li>
      <li><a href="../application-tracking/">Applications</a></li>
      <li><a href="../../logout.php">Logout</a></li>
    </ul>
  </nav>

  <!-- ADMIN WRAPPER -->
  <div class="admin-wrapper" style="display:block;">

    <!-- MAIN CONTENT -->
    <main class="admin-content" style="padding:2rem;">

      <!-- PAGE HEADER -->
      <div class="admin-page-header">
        <div>
          <div class="admin-page-title">Employee Management</div>
          <div class="admin-page-sub">Manage job seeker accounts and profiles • Updated just now</div>
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
          <input type="text" id="searchInput" class="input" placeholder="Search name, email, or location..." style="width:300px;font-size:0.85rem;padding:0.5rem 0.85rem;">
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

  </div>

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