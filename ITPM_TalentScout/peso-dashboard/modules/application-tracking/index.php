<?php 
session_start();
require_once '../../../database/db.php';

$stats = array(
  'pending' => 0,
  'applied' => 0,
  'interview scheduled' => 0,
  'matched' => 0,
  'offer received' => 0,
  'offer sent' => 0,
  'offer declined' => 0,
  'accepted' => 0,
  'rejected' => 0,
  'hired' => 0
);

$applications = array();

try {
  $conn = getConnection();
  
  // Get application status counts
  $sql = "SELECT status, COUNT(*) as count FROM application GROUP BY status";
  $result = $conn->query($sql);
  
  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $status = strtolower($row['status']);
      if (isset($stats[$status])) {
        $stats[$status] = $row['count'];
      } else {
        $stats[$status] = $row['count'];
      }
    }
  }
  
  // Get all applications with details
  $sql = "SELECT 
    a.application_id,
    a.status,
    a.application_date,
    e.first_name as firstName,
    e.last_name as lastName,
    e.email,
    j.title as job_title,
    em.company_name
  FROM application a
  JOIN employee e ON a.employee_id = e.employee_id
  JOIN job_post j ON a.job_post_id = j.job_post_id
  JOIN employer em ON j.employer_id = em.employer_id
  ORDER BY a.application_date DESC
  LIMIT 50";
  
  $result = $conn->query($sql);
  
  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $applications[] = $row;
    }
  }
  
  $conn->close();
} catch (Exception $e) {
  error_log("Application Tracking Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Application Tracking | PESO Admin - TalentScout AI</title>
  <link rel="stylesheet" href="../../../styles/global.css">
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
    .kpi-card.pending::before { background: #F97316; }
    .kpi-card.interview::before { background: #FBBF24; }
    .kpi-card.offer::before { background: #3B82F6; }
    .kpi-card.rejected::before { background: #EF4444; }
    .kpi-value { font-size: 2.2rem; font-weight: 800; color: var(--text-dark); line-height: 1; margin-bottom: 0.3rem; }
    .kpi-label { font-size: 0.85rem; color: var(--text-light); }

    /* Content Cards */
    .card { background: white; border: 1px solid var(--border); border-radius: var(--radius); padding: 1.5rem; margin-bottom: 1.75rem; }
    .card-title { font-size: 1rem; font-weight: 700; color: var(--text-dark); margin-bottom: 1.25rem; }

    /* Tables */
    .table-wrapper { overflow-x: auto; }
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
    tr:hover { background: #FAFAFA; }

    .status-badge {
      display: inline-block;
      padding: 0.4rem 0.8rem;
      border-radius: 4px;
      font-size: 0.85rem;
      font-weight: 600;
      text-transform: capitalize;
    }
    
    .status-pending {
      background: #E5E7EB;
      color: #374151;
    }
    
    .status-applied {
      background: #DBEAFE;
      color: #1E40AF;
    }
    
    .status-interview {
      background: #FEF3C7;
      color: #92400E;
    }
    
    .status-matched {
      background: #E9D5FF;
      color: #6B21A8;
    }
    
    .status-offer {
      background: #D1FAE5;
      color: #065F46;
    }
    
    .status-sent {
      background: #C7D2FE;
      color: #3730A3;
    }
    
    .status-declined {
      background: #FED7AA;
      color: #9A3412;
    }
    
    .status-accepted {
      background: #D1FAE5;
      color: #047857;
    }
    
    .status-rejected {
      background: #FEE2E2;
      color: #991B1B;
    }
    
    .status-hired {
      background: #D1FAE5;
      color: #065F46;
      font-weight: 700;
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
    <li><a href="../employee-management/">Employees</a></li>
    <li><a href="./" class="active">Applications</a></li>
  </ul>
</nav>

<!-- ADMIN WRAPPER -->
<div class="admin-wrapper" style="display:block;">

  <!-- MAIN CONTENT -->
  <main class="admin-content" style="padding:2rem;">

    <!-- PAGE HEADER -->
    <div class="admin-page-header">
      <div>
        <div class="admin-page-title">Application Tracking</div>
        <div class="admin-page-sub">Monitor and manage all job applications • Updated just now</div>
      </div>
    </div>

    <!-- STATS GRID -->
    <div class="stats-grid">
      <div class="kpi-card pending">
        <div class="kpi-value"><?php echo $stats['pending']; ?></div>
        <div class="kpi-label">Pending</div>
      </div>
      <div class="kpi-card interview">
        <div class="kpi-value"><?php echo $stats['interview scheduled']; ?></div>
        <div class="kpi-label">Interview</div>
      </div>
      <div class="kpi-card offer">
        <div class="kpi-value"><?php echo $stats['offer received']; ?></div>
        <div class="kpi-label">Offer</div>
      </div>
      <div class="kpi-card rejected">
        <div class="kpi-value"><?php echo $stats['rejected']; ?></div>
        <div class="kpi-label">Rejected</div>
      </div>
    </div>

    <!-- APPLICATIONS TABLE -->
    <div class="card">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
        <div class="card-title" style="margin:0;">Recent Applications</div>
        <input type="text" id="searchInput" class="input" placeholder="Search applicant, position, or company..." style="width:300px;font-size:0.85rem;padding:0.5rem 0.85rem;">
      </div>
      <?php if (!empty($applications)): ?>
      <div class="table-wrapper">
        <table id="applicationsTable">
          <thead>
            <tr>
              <th>Applicant</th>
              <th>Position</th>
              <th>Company</th>
              <th>Status</th>
              <th>Applied Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($applications as $app): ?>
            <tr class="app-row" data-search="<?php echo strtolower($app['firstName'] . ' ' . $app['lastName'] . ' ' . $app['job_title'] . ' ' . $app['company_name']); ?>">
              <td>
                <strong><?php echo htmlspecialchars($app['firstName'] . ' ' . $app['lastName']); ?></strong><br/>
                <small style="color: #999;"><?php echo htmlspecialchars($app['email']); ?></small>
              </td>
              <td><?php echo htmlspecialchars($app['job_title']); ?></td>
              <td><?php echo htmlspecialchars($app['company_name']); ?></td>
              <td>
                <span class="status-badge status-<?php echo strtolower($app['status']); ?>">
                  <?php echo htmlspecialchars($app['status']); ?>
                </span>
              </td>
              <td><?php echo date('M d, Y', strtotime($app['application_date'])); ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <p style="color: #999; text-align: center; padding: 2rem;">No applications found</p>
      <?php endif; ?>
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
