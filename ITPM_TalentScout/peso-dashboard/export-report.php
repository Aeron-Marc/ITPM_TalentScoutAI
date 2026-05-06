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
  <title>PESO Admin Report - TalentScout AI</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    :root {
      --ink: #000000;
      --muted: #333333;
      --line: #cccccc;
      --panel: #ffffff;
    }

    body {
      font-family: Arial, sans-serif;
      background: #ffffff;
      padding: 10px;
      color: var(--ink);
      line-height: 1.4;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
      font-size: 12px;
    }

    @page {
      margin: 10mm;
    }

    .report-shell {
      max-width: 1100px;
      margin: 0 auto;
      background: #ffffff;
    }

    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 1rem;
      margin-bottom: 1rem;
      padding: 0.8rem 1rem;
      background: #1E9E86;
      border: 1px solid #1E9E86;
      color: white;
    }

    .logo {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .logo-icon {
      width: 32px;
      height: 32px;
      background: white;
      color: #1E9E86;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 0.9rem;
    }

    .logo-text {
      font-size: 1rem;
      font-weight: 700;
      color: white;
    }

    .logo-text span {
      color: white;
    }

    .report-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: white;
    }

    .report-date {
      color: rgba(255, 255, 255, 0.9);
      font-size: 0.8rem;
    }

    .stats-row {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 0;
      margin-bottom: 1rem;
      border: 1px solid var(--line);
    }

    .stat-card {
      background: var(--panel);
      padding: 0.6rem 0.8rem;
      border-right: 1px solid var(--line);
      text-align: center;
    }

    .stat-card:last-child {
      border-right: none;
    }

    .stat-value {
      font-size: 1.8rem;
      font-weight: 700;
      color: #000 !important;
      margin: 0;
    }

    .stat-label {
      font-size: 0.7rem;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-top: 0.1rem;
    }

    .summary-row {
      display: grid;
      grid-template-columns: 1fr;
      gap: 0;
      margin-bottom: 1rem;
      border: 1px solid var(--line);
    }

    .summary-card,
    .section-card {
      background: var(--panel);
      border-bottom: 1px solid var(--line);
      overflow: hidden;
    }

    .summary-card:last-child {
      border-bottom: none;
    }

    .summary-card {
      padding: 0.8rem 1rem;
    }

    .summary-eyebrow {
      font-size: 0.7rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: #666;
      font-weight: 700;
      margin-bottom: 0.15rem;
    }

    .summary-title {
      font-size: 1.3rem;
      font-weight: 700;
      margin-bottom: 0.25rem;
      color: #000;
    }

    .summary-copy {
      color: #555;
      font-size: 0.85rem;
      line-height: 1.3;
    }

    .summary-meta {
      padding: 0.8rem 1rem;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
      background: #ffffff;
      border-top: 1px solid var(--line);
    }

    .summary-meta-label {
      font-size: 0.7rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: #999;
      margin-bottom: 0.15rem;
      font-weight: 700;
    }

    .summary-meta-value {
      font-size: 0.95rem;
      font-weight: 700;
      color: #000;
      margin: 0;
    }

    .section {
      margin-bottom: 0.8rem;
      page-break-inside: avoid;
      break-inside: avoid;
    }

    .section-card {
      padding: 0.8rem 1rem;
      page-break-inside: avoid;
      break-inside: avoid;
      border: 1px solid var(--line);
    }

    .section-title {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      font-size: 0.95rem;
      font-weight: 700;
      margin-bottom: 0.6rem;
      padding-bottom: 0.5rem;
      border-bottom: 2px solid #000;
      color: #000;
    }

    .section-title span:last-child {
      color: var(--muted);
      font-size: 0.75rem;
      font-weight: 500;
    }

    .two-col {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.5rem;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.8rem;
      border: 1px solid var(--line);
    }

    th {
      background: #eeeeee;
      padding: 0.5rem 0.6rem;
      text-align: left;
      font-weight: 700;
      color: #000;
      border-bottom: 1px solid var(--line);
    }

    td {
      padding: 0.5rem 0.6rem;
      border-bottom: 1px solid var(--line);
      vertical-align: top;
    }

    .status-badge {
      display: inline-block;
      padding: 0.2rem 0.4rem;
      border-radius: 0;
      font-size: 0.7rem;
      font-weight: 600;
      border: 1px solid #999;
      background: #fff;
      color: #111 !important;
    }

    .status-pending,
    .status-applied,
    .status-interview,
    .status-matched,
    .status-offer,
    .status-accepted,
    .status-rejected,
    .status-hired {
      background: #fff;
      color: #111 !important;
      border-color: #999;
      font-weight: 600;
    }

    .bar-list {
      display: flex;
      flex-direction: column;
      gap: 0.4rem;
    }

    .bar-item {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.3rem 0;
    }

    .bar-name {
      flex: 1;
      font-weight: 600;
      color: #000 !important;
      font-size: 0.8rem;
    }

    .bar-count {
      font-weight: 600;
      color: #000 !important;
      min-width: 1.5rem;
      text-align: right;
      font-size: 0.8rem;
    }

    .bar-bar {
      flex: 1.5;
      height: 6px;
      background: #ddd;
      border: 1px solid #999;
      overflow: hidden;
    }

    .bar-fill {
      height: 100%;
      background: #333;
    }

    .footer {
      margin-top: 1rem;
      padding-top: 0.5rem;
      border-top: 1px solid var(--line);
      text-align: center;
      font-size: 0.7rem;
      color: var(--muted);
    }

    .status-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 0.4rem 0.5rem;
    }

    .status-pill {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.4rem;
      padding: 0.35rem 0.5rem;
      border: 1px solid var(--line);
      background: #fff;
      font-size: 0.75rem;
    }

    .status-pill strong {
      font-size: 0.8rem;
      font-weight: 700;
    }

    .dot {
      width: 6px;
      height: 6px;
      flex-shrink: 0;
      background: #222;
    }

    .locations-table td:first-child,
    .recent-table td:first-child {
      font-weight: 600;
      color: var(--ink);
    }

    .table-wrap {
      border: 1px solid var(--line);
      border-radius: 6px;
      overflow: hidden;
    }

    @media print {
      body {
        background: #fff;
        padding: 0;
      }

      .report-shell {
        background: transparent;
      }

      .stats-row {
        grid-template-columns: repeat(2, 1fr);
      }

      .two-col {
        grid-template-columns: 1fr;
      }

      .summary-row {
        grid-template-columns: 1fr;
      }

      .section-card,
      .stat-card,
      .header,
      .summary-card,
      .summary-meta,
      .table-wrap {
        break-inside: avoid;
        page-break-inside: avoid;
      }
    }
  </style>
</head>

<body>
  <div class="report-shell">
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

    <div class="summary-row">
      <div class="summary-card">
        <div class="summary-eyebrow">Dashboard Summary</div>
        <div class="summary-title"><?php echo $application_total; ?> total applications reviewed</div>
        <div class="summary-copy">This report summarizes platform activity across applicants, active jobs, employers, hiring outcomes, and geographic distribution. The sections below are arranged for quick scanning and print cleanly to PDF.</div>
      </div>
      <div class="summary-meta">
        <div>
          <div class="summary-meta-label">Prepared for</div>
          <div class="summary-meta-value">PESO Admin Operations</div>
        </div>
        <div style="margin-top:1rem;">
          <div class="summary-meta-label">Report scope</div>
          <div class="summary-meta-value">Analytics, status, and recent activity</div>
        </div>
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
      <div class="section section-card">
        <div class="section-title"><span>Top Positions by Applications</span><span>Ranked by demand</span></div>
        <div class="bar-list">
          <?php
          $max_pos = !empty($top_positions) ? $top_positions[0]['count'] : 1;
          foreach ($top_positions as $pos):
            $pct = ($pos['count'] / $max_pos) * 100;
          ?>
            <div class="bar-item">
              <div class="bar-name" style="color: #000;"><?php echo htmlspecialchars(strip_tags($pos['title'])); ?></div>
              <div class="bar-bar">
                <div class="bar-fill" style="width: <?php echo $pct; ?>%;"></div>
              </div>
              <div class="bar-count" style="color: #000;"><?php echo $pos['count']; ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="section section-card">
        <div class="section-title"><span>Top Employers by Applications</span><span>Hiring volume</span></div>
        <div class="bar-list">
          <?php
          $max_emp = !empty($top_employers) ? $top_employers[0]['count'] : 1;
          foreach ($top_employers as $emp):
            $pct = ($emp['count'] / $max_emp) * 100;
          ?>
            <div class="bar-item">
              <div class="bar-name" style="color: #000;"><?php echo htmlspecialchars(strip_tags($emp['company_name'])); ?></div>
              <div class="bar-bar">
                <div class="bar-fill" style="width: <?php echo $pct; ?>%;"></div>
              </div>
              <div class="bar-count" style="color: #000;"><?php echo $emp['count']; ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="section section-card">
      <div class="section-title"><span>Application Status Distribution</span><span>Current pipeline mix</span></div>
      <div class="status-grid">
        <?php foreach ($status_dist as $status => $count): if ($count > 0): ?>
            <div class="status-pill">
              <div style="display:flex;align-items:center;gap:0.5rem;min-width:0;">
                <span class="dot" style="background: <?php
                                                      echo '#111111';
                                                      ?>"></span>
                <span style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo $status; ?></span>
              </div>
              <strong><?php echo $count; ?></strong>
            </div>
        <?php endif;
        endforeach; ?>
      </div>
    </div>

    <div class="section section-card">
      <div class="section-title"><span>Recent Applications</span><span>Latest 20 entries</span></div>
      <div class="table-wrap">
        <table class="recent-table">
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
                <td style="color: #000;"><?php echo htmlspecialchars(strip_tags($app['first_name'] . ' ' . $app['last_name'])); ?></td>
                <td style="color: #000;"><?php echo htmlspecialchars(strip_tags($app['title'])); ?></td>
                <td style="color: #000;"><?php echo htmlspecialchars(strip_tags($app['address'] ?? '-')); ?></td>
                <td><span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $app['status'])); ?>" style="color: #000;"><?php echo htmlspecialchars(strip_tags($app['status'])); ?></span></td>
                <td style="color: #000;"><?php echo date('M d, Y', strtotime($app['application_date'])); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="section section-card">
      <div class="section-title"><span>Applicant Distribution by Location</span><span>Top barangays</span></div>
      <div class="bar-list">
        <?php
        $max_brgy = !empty($barangays) ? $barangays[0]['count'] : 1;
        foreach ($barangays as $brgy):
          $pct = ($brgy['count'] / $max_brgy) * 100;
        ?>
          <div class="bar-item">
            <div class="bar-name" style="color: #000;"><?php echo htmlspecialchars(strip_tags($brgy['address'])); ?></div>
            <div class="bar-bar">
              <div class="bar-fill" style="width: <?php echo $pct; ?>%;"></div>
            </div>
            <div class="bar-count" style="color: #000;"><?php echo $brgy['count']; ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="footer">
      Report generated from TalentScout AI • PESO Admin Dashboard • <?php echo date('F j, Y'); ?>
    </div>
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