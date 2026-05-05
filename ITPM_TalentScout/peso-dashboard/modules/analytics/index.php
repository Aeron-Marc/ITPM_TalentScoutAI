<?php
session_start();
require_once __DIR__ . '/../../auth.php';
peso_require_admin('../../login.php');
require_once('../../../database/db.php');

// Get database connection
$conn = getConnection();

// Fetch statistics
$stats = [
  'total_applicants' => 0,
  'active_jobs' => 0,
  'employers' => 0,
  'hires' => 0
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

// Employers
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM employer WHERE status = 'active'");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['employers'] = $row['count'];
$stmt->close();

// Successful hires
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM application WHERE status = 'Hired' OR status = 'Accepted'");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['hires'] = $row['count'];
$stmt->close();

// Fetch top hiring categories (by job posts)
$top_categories = [];
$stmt = $conn->prepare("SELECT title, COUNT(*) as count FROM job_post GROUP BY title ORDER BY count DESC LIMIT 5");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $top_categories[] = $row;
}
$stmt->close();

// Fetch recent applications
$recent_applications = [];
$stmt = $conn->prepare("SELECT 
  e.first_name as firstName, 
  e.last_name as lastName,
  e.address as location,
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
  $recent_applications[] = $row;
}
$stmt->close();

// Fetch barangay distribution
$barangay_data = [];
$stmt = $conn->prepare("SELECT address, COUNT(*) as count FROM employee GROUP BY address ORDER BY count DESC LIMIT 5");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $barangay_data[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Analytics & Reports | PESO Admin - TalentScout AI</title>
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
      max-height: 280px;
      overflow-y: auto;
      border: 1px solid var(--border);
      border-radius: 8px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.87rem;
    }

    thead {
      position: sticky;
      top: 0;
      background: #F8FAFB;
      z-index: 1;
    }

    th {
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

    /* Bar chart */
    .brgy-bar {
      margin-bottom: 1rem;
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
      <li><a href="./" class="active">Analytics</a></li>
      <li><a href="../employer-management/">Employers</a></li>
      <li><a href="../employee-management/">Employees</a></li>
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
          <div class="admin-page-title">Analytics & Reports</div>
          <div class="admin-page-sub">Platform insights and statistics • Updated just now</div>
        </div>
      </div>

      <!-- CATEGORIES CARD -->
      <div class="card">
        <div class="card-title">Top Job Categories</div>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Job Title</th>
                <th>Open Positions</th>
                <th>% of Total</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $total_jobs = $stats['active_jobs'] > 0 ? $stats['active_jobs'] : 1;
              foreach ($top_categories as $cat):
              ?>
                <tr>
                  <td><?php echo htmlspecialchars($cat['title']); ?></td>
                  <td><?php echo $cat['count']; ?></td>
                  <td><?php echo round(($cat['count'] / $total_jobs) * 100, 1); ?>%</td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- RECENT APPLICATIONS TABLE -->
      <div class="card" style="margin-top: 1.5rem;">
        <div class="card-title" style="margin-bottom:1rem;">Recent Applications</div>
        <div class="table-wrapper">
          <table class="table">
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
              <?php if (count($recent_applications) > 0): ?>
                <?php foreach ($recent_applications as $app):
                  $status_class = 'badge-gray';
                  if (strtolower($app['status']) == 'matched') $status_class = 'badge-blue';
                  elseif (strtolower($app['status']) == 'interview scheduled') $status_class = 'badge-yellow';
                  elseif (strtolower($app['status']) == 'offer received') $status_class = 'badge-green';
                  elseif (strtolower($app['status']) == 'rejected') $status_class = 'badge-red';
                ?>
                  <tr>
                    <td><strong><?php echo htmlspecialchars($app['firstName'] . ' ' . $app['lastName']); ?></strong></td>
                    <td><?php echo htmlspecialchars($app['position']); ?></td>
                    <td><?php echo htmlspecialchars($app['location'] ?? '-'); ?></td>
                    <td><span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars(ucfirst($app['status'])); ?></span></td>
                    <td><?php echo date('M d, Y', strtotime($app['application_date'])); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="5" style="text-align:center;color:var(--text-light);">No recent applications</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- BARANGAY DISTRIBUTION -->
      <div class="card">
        <div class="card-title">Applicant Distribution by Location</div>
        <?php
        $total_applicants = $stats['total_applicants'] > 0 ? $stats['total_applicants'] : 1;
        foreach ($barangay_data as $barangay):
        ?>
          <div class="brgy-bar">
            <div class="brgy-bar-header">
              <span class="brgy-bar-name"><?php echo htmlspecialchars($barangay['address']); ?></span>
              <span class="brgy-bar-count"><?php echo round(($barangay['count'] / $total_applicants) * 100, 1); ?>%</span>
            </div>
            <div class="brgy-fill" style="width: <?php echo min(($barangay['count'] / $total_applicants) * 100, 100); ?>%;"></div>
          </div>
        <?php endforeach; ?>
      </div>

    </main>

  </div>

</body>

</html>