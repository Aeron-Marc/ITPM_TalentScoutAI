<?php
session_start();
require_once '../database/db.php';

$conn = getConnection();

$stats = [
  'total_applicants' => 0,
  'active_jobs' => 0,
  'successful_hires' => 0,
  'employers' => 0
];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM employee");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['total_applicants'] = $row['count'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM job_post WHERE application_deadline >= CURDATE()");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['active_jobs'] = $row['count'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM application WHERE status = 'Hired' OR status = 'Accepted'");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['successful_hires'] = $row['count'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM employer");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['employers'] = $row['count'];
$stmt->close();

$status_dist = ['Pending' => 0, 'Applied' => 0, 'Interview Scheduled' => 0, 'Matched' => 0, 'Offer Received' => 0, 'Offer Sent' => 0, 'Offer Declined' => 0, 'Accepted' => 0, 'Rejected' => 0, 'Hired' => 0];
$stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM application GROUP BY status");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $status_dist[$row['status']] = $row['count'];
}
$stmt->close();

$recent_apps = [];
$stmt = $conn->prepare("SELECT e.first_name, e.last_name, e.address, jp.title, a.status, a.application_date FROM application a JOIN employee e ON a.employee_id = e.employee_id JOIN job_post jp ON a.job_post_id = jp.job_post_id ORDER BY a.application_date DESC LIMIT 20");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $recent_apps[] = $row;
}
$stmt->close();

$top_positions = [];
$stmt = $conn->prepare("SELECT jp.title, COUNT(*) as count FROM application a JOIN job_post jp ON a.job_post_id = jp.job_post_id GROUP BY jp.title ORDER BY count DESC LIMIT 5");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $top_positions[] = $row;
}
$stmt->close();

$top_employers = [];
$stmt = $conn->prepare("SELECT em.company_name, COUNT(*) as count FROM application a JOIN job_post jp ON a.job_post_id = jp.job_post_id JOIN employer em ON jp.employer_id = em.employer_id GROUP BY em.company_name ORDER BY count DESC LIMIT 5");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $top_employers[] = $row;
}
$stmt->close();

$barangays = [];
$stmt = $conn->prepare("SELECT address, COUNT(*) as count FROM employee WHERE address IS NOT NULL AND address != '' GROUP BY address ORDER BY count DESC LIMIT 8");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $barangays[] = $row;
}
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>PESO Admin Report - TalentScout AI</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; padding: 2rem; color: #1a1a1a; line-height: 1.5; }
    .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 2px solid #1E9E86; }
    .logo { display: flex; align-items: center; gap: 0.75rem; }
    .logo-icon { width: 40px; height: 40px; background: #1E9E86; color: white; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1rem; }
    .logo-text { font-size: 1.25rem; font-weight: 700; }
    .logo-text span { color: #1E9E86; }
    .report-title { font-size: 1.5rem; font-weight: 700; }
    .report-date { color: #666; font-size: 0.875rem; }
    .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2rem; }
    .stat-card { background: #f8f9fa; padding: 1.25rem; border-radius: 8px; text-align: center; }
    .stat-value { font-size: 2rem; font-weight: 800; color: #1E9E86; }
    .stat-label { font-size: 0.8rem; color: #666; text-transform: uppercase; letter-spacing: 0.5px; }
    .section { margin-bottom: 2rem; }
    .section-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid #ddd; }
    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
    table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
    th { background: #f8f9fa; padding: 0.75rem; text-align: left; font-weight: 600; border-bottom: 1px solid #ddd; }
    td { padding: 0.75rem; border-bottom: 1px solid #eee; }
    .status-badge { display: inline-block; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
    .status-pending { background: #e5e7eb; color: #374151; }
    .status-applied { background: #dbeafe; color: #1e40af; }
    .status-interview { background: #fef3c7; color: #92400e; }
    .status-matched { background: #e9d5ff; color: #6b21a8; }
    .status-offer { background: #d1fae5; color: #065f46; }
    .status-accepted { background: #d1fae5; color: #047857; }
    .status-rejected { background: #fee2e2; color: #991b1b; }
    .status-hired { background: #d1fae5; color: #065f46; font-weight: 700; }
    .bar-list { display: flex; flex-direction: column; gap: 0.5rem; }
    .bar-item { display: flex; align-items: center; gap: 1rem; }
    .bar-name { flex: 1; font-weight: 500; }
    .bar-count { font-weight: 700; color: #1E9E86; }
    .bar-bar { flex: 2; height: 8px; background: #eee; border-radius: 4px; overflow: hidden; }
    .bar-fill { height: 100%; background: #1E9E86; border-radius: 4px; }
    .footer { margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #ddd; text-align: center; font-size: 0.8rem; color: #999; }
    @media print {
      body { padding: 1rem; }
      .stats-row { grid-template-columns: repeat(2, 1fr); }
      .two-col { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
  <div class="header">
    <div class="logo">
      <div class="logo-icon">TS</div>
      <div class="logo-text">Talent<span>Scout</span> AI</div>
    </div>
    <div style="text-align: right;">
      <div class="report-title">PESO Admin Report</div>
      <div class="report-date">Generated on <?php echo date('F j, Y \a\t g:i A'); ?></div>
    </div>
  </div>

  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-value"><?php echo $stats['total_applicants']; ?></div>
      <div class="stat-label">Total Applicants</div>
    </div>
    <div class="stat-card">
      <div class="stat-value"><?php echo $stats['active_jobs']; ?></div>
      <div class="stat-label">Active Jobs</div>
    </div>
    <div class="stat-card">
      <div class="stat-value"><?php echo $stats['successful_hires']; ?></div>
      <div class="stat-label">Successful Hires</div>
    </div>
    <div class="stat-card">
      <div class="stat-value"><?php echo $stats['employers']; ?></div>
      <div class="stat-label">Employers</div>
    </div>
  </div>

  <div class="two-col">
    <div class="section">
      <div class="section-title">Top Positions by Applications</div>
      <div class="bar-list">
        <?php 
        $max_pos = !empty($top_positions) ? $top_positions[0]['count'] : 1;
        foreach ($top_positions as $pos): 
          $pct = ($pos['count'] / $max_pos) * 100;
        ?>
        <div class="bar-item">
          <div class="bar-name"><?php echo htmlspecialchars($pos['title']); ?></div>
          <div class="bar-bar"><div class="bar-fill" style="width: <?php echo $pct; ?>%;"></div></div>
          <div class="bar-count"><?php echo $pos['count']; ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="section">
      <div class="section-title">Top Employers by Applications</div>
      <div class="bar-list">
        <?php 
        $max_emp = !empty($top_employers) ? $top_employers[0]['count'] : 1;
        foreach ($top_employers as $emp): 
          $pct = ($emp['count'] / $max_emp) * 100;
        ?>
        <div class="bar-item">
          <div class="bar-name"><?php echo htmlspecialchars($emp['company_name']); ?></div>
          <div class="bar-bar"><div class="bar-fill" style="width: <?php echo $pct; ?>%;"></div></div>
          <div class="bar-count"><?php echo $emp['count']; ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="section">
    <div class="section-title">Application Status Distribution</div>
    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
      <?php foreach ($status_dist as $status => $count): if ($count > 0): ?>
      <div style="display: flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.5rem; background: #f8f9fa; border-radius: 4px; font-size: 0.8rem;">
        <strong><?php echo $count; ?></strong> <span style="color: #666;"><?php echo $status; ?></span>
      </div>
      <?php endif; endforeach; ?>
    </div>
  </div>

  <div class="section">
    <div class="section-title">Recent Applications</div>
    <table>
      <thead>
        <tr>
          <th>Applicant</th>
          <th>Position</th>
          <th>Location</th>
          <th>Status</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recent_apps as $app): ?>
        <tr>
          <td><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></td>
          <td><?php echo htmlspecialchars($app['title']); ?></td>
          <td><?php echo htmlspecialchars($app['address'] ?? '-'); ?></td>
          <td><span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $app['status'])); ?>"><?php echo $app['status']; ?></span></td>
          <td><?php echo date('M d, Y', strtotime($app['application_date'])); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="section">
    <div class="section-title">Applicant Distribution by Location</div>
    <div class="bar-list">
      <?php 
      $max_brgy = !empty($barangays) ? $barangays[0]['count'] : 1;
      foreach ($barangays as $brgy): 
        $pct = ($brgy['count'] / $max_brgy) * 100;
      ?>
      <div class="bar-item">
        <div class="bar-name"><?php echo htmlspecialchars($brgy['address']); ?></div>
        <div class="bar-bar"><div class="bar-fill" style="width: <?php echo $pct; ?>%;"></div></div>
        <div class="bar-count"><?php echo $brgy['count']; ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="footer">
    Report generated from TalentScout AI • PESO Admin Dashboard • <?php echo date('F j, Y'); ?>
  </div>

  <script>
    window.onload = function() {
      setTimeout(function() {
        window.print();
      }, 500);
    };
  </script>
</body>
</html>