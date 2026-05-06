<?php
session_start();
require_once __DIR__ . '/../../auth.php';
peso_require_admin('../../login.php');
require_once __DIR__ . '/../../../database/db.php';

// Get PDO connection
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=itpm_talentscoutai;charset=utf8mb4',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}

function db_val($pdo, $sql, $params = [], $default = 0) {
    try {
        $s = $pdo->prepare($sql);
        $s->execute($params);
        return $s->fetchColumn() ?? $default;
    } catch (Exception $e) { return $default; }
}
function db_all($pdo, $sql, $params = []) {
    try {
        $s = $pdo->prepare($sql);
        $s->execute($params);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { return []; }
}

// Get database connection for backwards compat
$conn = getConnection();

// Fetch statistics
$stats = [
  'total_applicants' => db_val($pdo, "SELECT COUNT(*) FROM employee"),
  'active_jobs' => db_val($pdo, "SELECT COUNT(*) FROM job_post WHERE application_deadline >= CURDATE()"),
  'employers' => db_val($pdo, "SELECT COUNT(*) FROM employer WHERE status = 'active'"),
  'hires' => db_val($pdo, "SELECT COUNT(*) FROM application WHERE status = 'Hired' OR status = 'Accepted'")
];

// Fetch top hiring categories (by job posts)
$top_categories = db_all($pdo, "SELECT title, COUNT(*) as count FROM job_post GROUP BY title ORDER BY count DESC LIMIT 5");

// Fetch recent applications
$recent_applications = db_all($pdo, "SELECT 
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

// Fetch barangay distribution
$barangay_data = db_all($pdo, "SELECT address, COUNT(*) as count FROM employee GROUP BY address ORDER BY count DESC LIMIT 5");

// Get sidebar data
$pending_approvals = 0;
$unverified_users = 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Analytics & Reports – TalentScout AI</title>
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

    /* Table styles */
    .table-wrapper {
      overflow-x: auto;
      max-height: 380px;
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

    /* Bar chart */
    .brgy-bar {
      margin-bottom: 16px;
    }

    .brgy-bar-header {
      display: flex;
      justify-content: space-between;
      font-size: 12px;
      margin-bottom: 6px;
    }

    .brgy-bar-name {
      color: var(--text-mid);
      font-weight: 500;
    }

    .brgy-bar-count {
      font-weight: 700;
      color: var(--green);
    }

    .brgy-fill {
      height: 8px;
      background: linear-gradient(90deg, var(--green), var(--teal));
      border-radius: 100px;
    }

    .badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 12px;
      font-size: 11px;
      font-weight: 600;
    }

    .badge-gray { background: #e0e0e0; color: #333; }
    .badge-blue { background: var(--blue-light); color: var(--blue-text); }
    .badge-yellow { background: #fff3cd; color: #856404; }
    .badge-green { background: var(--teal-light); color: var(--teal); }
    .badge-red { background: var(--red-light); color: var(--red); }

    @media (max-width: 900px) {
      .sidebar { transform: translateX(-100%); }
      .content { margin-left: 0; }
      .table-wrapper { max-height: 300px; }
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
    <a href="../employee-management/" class="nav-item">
      <i class="fa-solid fa-users"></i> Job Seekers
      <?php if ($unverified_users > 0): ?>
        <span class="nav-badge gold"><?= $unverified_users ?></span>
      <?php endif; ?>
    </a>
    <a href="../application-tracking/" class="nav-item">
      <i class="fa-solid fa-clipboard-list"></i> Applications
    </a>

    <div class="nav-section-label">Insights</div>
    <a href="./" class="nav-item active">
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
      <h1>Analytics & Reports</h1>
      <p>Platform insights and statistics</p>
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

</body>

</html>