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
      --ink: #14302d;
      --muted: #5f6f6b;
      --line: #d7e7e2;
      --panel: #ffffff;
      --bg: #eefaf7;
      --accent: #1E9E86;
      --accent-2: #0f766e;
      --accent-3: #2dd4bf;
      --shadow: 0 10px 30px rgba(20, 48, 45, 0.08);
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: linear-gradient(180deg, #f4fffb 0%, var(--bg) 100%);
      padding: 24px;
      color: var(--ink);
      line-height: 1.5;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }

    @page {
      margin: 14mm;
    }

    .report-shell {
      max-width: 1120px;
      margin: 0 auto;
      background: rgba(255, 255, 255, 0.76);
      backdrop-filter: blur(10px);
    }

    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 1rem;
      margin-bottom: 1.5rem;
      padding: 1.35rem 1.5rem;
      background: linear-gradient(135deg, rgba(30, 158, 134, 0.10), rgba(45, 212, 191, 0.06));
      border: 1px solid var(--line);
      border-radius: 18px;
      box-shadow: var(--shadow);
    }

    .logo {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .logo-icon {
      width: 40px;
      height: 40px;
      background: linear-gradient(135deg, var(--accent-2), var(--accent));
      color: white;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      font-size: 1rem;
    }

    .logo-text {
      font-size: 1.2rem;
      font-weight: 700;
    }

    .logo-text span {
      color: var(--accent);
    }

    .report-title {
      font-size: 1.8rem;
      font-weight: 800;
      letter-spacing: -0.03em;
      color: var(--ink);
    }

    .report-date {
      color: var(--muted);
      font-size: 0.875rem;
    }

    .stats-row {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1rem;
      margin-bottom: 1.5rem;
    }

    .stat-card {
      background: var(--panel);
      padding: 1.15rem 1.25rem;
      border-radius: 16px;
      border: 1px solid var(--line);
      box-shadow: var(--shadow);
      text-align: center;
      position: relative;
      overflow: hidden;
    }

    .stat-card::before {
      content: '';
      position: absolute;
      inset: 0 auto auto 0;
      width: 100%;
      height: 4px;
      background: linear-gradient(90deg, var(--accent-2), var(--accent-3));
    }

    .stat-value {
      margin-top: 0.1rem;
      font-size: 2.2rem;
      font-weight: 900;
      color: var(--accent);
      letter-spacing: -0.03em;
    }

    .stat-label {
      font-size: 0.76rem;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.08em;
      margin-top: 0.15rem;
    }

    .summary-row {
      display: grid;
      grid-template-columns: 1.3fr 0.7fr;
      gap: 1rem;
      margin-bottom: 1.5rem;
    }

    .summary-card,
    .section-card {
      background: var(--panel);
      border: 1px solid var(--line);
      border-radius: 18px;
      box-shadow: var(--shadow);
      overflow: hidden;
    }

    .summary-card {
      padding: 1.25rem 1.35rem;
    }

    .summary-eyebrow {
      font-size: 0.74rem;
      text-transform: uppercase;
      letter-spacing: 0.12em;
      color: var(--accent);
      font-weight: 800;
      margin-bottom: 0.35rem;
    }

    .summary-title {
      font-size: 1.5rem;
      font-weight: 800;
      letter-spacing: -0.02em;
      margin-bottom: 0.4rem;
    }

    .summary-copy {
      color: var(--muted);
      max-width: 70ch;
      font-size: 0.95rem;
    }

    .summary-meta {
      padding: 1.25rem 1.35rem;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      background: linear-gradient(180deg, rgba(30, 158, 134, 0.10), rgba(30, 158, 134, 0.03));
    }

    .summary-meta-label {
      font-size: 0.78rem;
      text-transform: uppercase;
      letter-spacing: 0.11em;
      color: var(--muted);
      margin-bottom: 0.35rem;
    }

    .summary-meta-value {
      font-size: 1rem;
      font-weight: 700;
      color: var(--ink);
    }

    .section {
      margin-bottom: 1.5rem;
    }

    .section-card {
      padding: 1.1rem 1.25rem 1.25rem;
      page-break-inside: avoid;
      break-inside: avoid;
    }

    .section-title {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      font-size: 1rem;
      font-weight: 800;
      margin-bottom: 1rem;
      padding-bottom: 0.7rem;
      border-bottom: 1px solid var(--line);
    }

    .section-title span:last-child {
      color: var(--muted);
      font-size: 0.8rem;
      font-weight: 600;
    }

    .two-col {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.85rem;
    }

    th {
      background: #edf8f5;
      padding: 0.8rem 0.75rem;
      text-align: left;
      font-weight: 600;
      color: var(--ink);
      border-bottom: 1px solid var(--line);
    }

    td {
      padding: 0.75rem;
      border-bottom: 1px solid #eef3f1;
      vertical-align: top;
    }

    .status-badge {
      display: inline-block;
      padding: 0.3rem 0.55rem;
      border-radius: 999px;
      font-size: 0.75rem;
      font-weight: 600;
      border: 1px solid transparent;
    }

    .status-pending {
      background: #f3f4f6;
      color: #4b5563;
      border-color: #d1d5db;
    }

    .status-applied {
      background: #e0f2fe;
      color: #0369a1;
      border-color: #bae6fd;
    }

    .status-interview {
      background: #fef3c7;
      color: #92400e;
      border-color: #fde68a;
    }

    .status-matched {
      background: #ede9fe;
      color: #6d28d9;
      border-color: #ddd6fe;
    }

    .status-offer {
      background: #d1fae5;
      color: #065f46;
      border-color: #a7f3d0;
    }

    .status-accepted {
      background: #dcfce7;
      color: #15803d;
      border-color: #bbf7d0;
    }

    .status-rejected {
      background: #fee2e2;
      color: #991b1b;
      border-color: #fecaca;
    }

    .status-hired {
      background: linear-gradient(135deg, rgba(30, 158, 134, 0.15), rgba(45, 212, 191, 0.15));
      color: var(--accent-2);
      font-weight: 700;
      border-color: rgba(30, 158, 134, 0.18);
    }

    .bar-list {
      display: flex;
      flex-direction: column;
      gap: 0.7rem;
    }

    .bar-item {
      display: flex;
      align-items: center;
      gap: 0.85rem;
    }

    .bar-name {
      flex: 1;
      font-weight: 600;
      color: var(--ink);
    }

    .bar-count {
      font-weight: 700;
      color: var(--accent);
      min-width: 1.6rem;
      text-align: right;
    }

    .bar-bar {
      flex: 2.3;
      height: 10px;
      background: #e8f1ee;
      border-radius: 999px;
      overflow: hidden;
    }

    .bar-fill {
      height: 100%;
      background: linear-gradient(90deg, var(--accent-2), var(--accent-3));
      border-radius: 999px;
    }

    .footer {
      margin-top: 1.5rem;
      padding-top: 0.85rem;
      border-top: 1px solid var(--line);
      text-align: center;
      font-size: 0.8rem;
      color: var(--muted);
    }

    .status-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 0.5rem 0.75rem;
    }

    .status-pill {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.65rem;
      padding: 0.55rem 0.7rem;
      border: 1px solid var(--line);
      border-radius: 12px;
      background: #fbfefc;
      font-size: 0.8rem;
    }

    .status-pill strong {
      font-size: 0.9rem;
    }

    .dot {
      width: 9px;
      height: 9px;
      border-radius: 999px;
      flex-shrink: 0;
    }

    .locations-table td:first-child,
    .recent-table td:first-child {
      font-weight: 600;
      color: var(--ink);
    }

    .table-wrap {
      border: 1px solid var(--line);
      border-radius: 14px;
      overflow: hidden;
    }

    @media print {
      body {
        background: #fff;
        padding: 0;
      }

      .report-shell {
        background: transparent;
        box-shadow: none;
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

      .header,
      .summary-card,
      .summary-meta,
      .stat-card,
      .section-card {
        box-shadow: none;
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
              <div class="bar-name"><?php echo htmlspecialchars($pos['title']); ?></div>
              <div class="bar-bar">
                <div class="bar-fill" style="width: <?php echo $pct; ?>%;"></div>
              </div>
              <div class="bar-count"><?php echo $pos['count']; ?></div>
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
              <div class="bar-name"><?php echo htmlspecialchars($emp['company_name']); ?></div>
              <div class="bar-bar">
                <div class="bar-fill" style="width: <?php echo $pct; ?>%;"></div>
              </div>
              <div class="bar-count"><?php echo $emp['count']; ?></div>
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
                                                      echo match ($status) {
                                                        'Pending' => '#6c757d',
                                                        'Applied' => '#17a2b8',
                                                        'Interview Scheduled' => '#ffc107',
                                                        'Matched' => '#6610f2',
                                                        'Offer Received' => '#20c997',
                                                        'Offer Sent' => '#007bff',
                                                        'Offer Declined' => '#fd7e14',
                                                        'Accepted' => '#28a745',
                                                        'Rejected' => '#dc3545',
                                                        'Hired' => '#1E9E86',
                                                        default => '#1E9E86',
                                                      };
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
            <div class="bar-name"><?php echo htmlspecialchars($brgy['address']); ?></div>
            <div class="bar-bar">
              <div class="bar-fill" style="width: <?php echo $pct; ?>%;"></div>
            </div>
            <div class="bar-count"><?php echo $brgy['count']; ?></div>
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