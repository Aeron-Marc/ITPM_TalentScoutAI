<?php
session_start();
require_once __DIR__ . '/../../../database/db.php';

// Check if user is logged in
$employee_id = $_SESSION['employee_id'] ?? null;
if (!$employee_id) {
  header('Location: ../../login.php');
  exit;
}

// Get database connection
$conn = getConnection();

// Fetch all applications for the logged-in employee with job details
$applications = [];
$query = "SELECT 
  a.application_id,
  a.job_post_id,
  a.employee_id,
  a.application_date,
  a.status,
  j.title as job_title,
  e.company_name
FROM application a
JOIN job_post j ON a.job_post_id = j.job_post_id
JOIN employer e ON j.employer_id = e.employer_id
WHERE a.employee_id = ?
ORDER BY a.application_date DESC";

$stmt = $conn->prepare($query);
if (!$stmt) {
  die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $employee_id);
if (!$stmt->execute()) {
  die("Execute failed: " . $stmt->error);
}

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
  $applications[] = $row;
}

$stmt->close();

// Calculate statistics from database
$totalApplications = count($applications);
$interviewsScheduled = count(array_filter($applications, function($app) {
  return stripos($app['status'], 'interview') !== false;
}));
$jobOffers = count(array_filter($applications, function($app) {
  return stripos($app['status'], 'offer') !== false;
}));
$underReview = count(array_filter($applications, function($app) {
  $status = strtolower($app['status']);
  return $status === 'under review' || $status === 'pending' || 
         (!in_array($status, ['interview scheduled', 'offer received', 'rejected', 'not selected']));
}));

closeConnection($conn);
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Application Tracker | Job Seekers - TalentScout AI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../styles/global.css" />
    <link rel="stylesheet" href="../../../styles/page-layout.css" />
    <link rel="stylesheet" href="../../navbar.css" />
    <style>
      * {
        font-family: 'Poppins', sans-serif;
      }
    </style>
  </head>
  <body>
    <nav class="navbar">
      <a href="../../index.php" class="nav-logo">
        <div class="nav-logo-icon">TS</div>
        <span class="nav-logo-text">Talent<span>Scout</span> AI</span>
      </a>
      <ul class="nav-links">
        <li><a href="../../index.php">Home</a></li>
        <li><a href="../job-postings/index.php">Browse Jobs</a></li>
        <li><a href="../ai-matching/index.php">AI Matching</a></li>
        <li><a href="../resume-builder/index.php">Resume Builder</a></li>
        <li><a href="../skill-gap-analysis/index.php">Skills</a></li>
        <li><a href="./index.php" class="active">Applications</a></li>
      </ul>
      <div class="nav-actions">
        <?php if (isset($_SESSION['employee_id'])): ?>
          <span class="nav-user">Welcome, <?php echo htmlspecialchars($_SESSION['employee_name'] ?? 'User'); ?></span>
          <a href="../../logout.php" class="btn btn-outline">Logout</a>
        <?php else: ?>
          <a href="../../login.php" class="btn btn-outline">Login</a>
          <a href="../../signup.php" class="btn btn-primary">Get Started</a>
        <?php endif; ?>
      </div>
    </nav>

    <div class="page-header">
      <div class="page-header-inner">
        <div class="breadcrumb">
          <a href="../../index.php">Home</a> / Application Tracker
        </div>
        <h1>Application Tracker</h1>
        <p>
          Monitor your job applications from first submission to interview and
          final hiring decision.
        </p>
      </div>
    </div>

    <main class="employee-module-shell">
      <div class="tracker-stats">
        <div class="tracker-stat-card">
          <div class="tracker-stat-number" id="totalAppCount"><?php echo $totalApplications; ?></div>
          <div class="tracker-stat-label">Total Applications</div>
        </div>
        <div class="tracker-stat-card">
          <div class="tracker-stat-number" id="interviewCount"><?php echo $interviewsScheduled; ?></div>
          <div class="tracker-stat-label">Interviews Scheduled</div>
        </div>
        <div class="tracker-stat-card">
          <div class="tracker-stat-number" id="offersCount"><?php echo $jobOffers; ?></div>
          <div class="tracker-stat-label">Job Offer</div>
        </div>
        <div class="tracker-stat-card">
          <div class="tracker-stat-number" id="reviewCount"><?php echo $underReview; ?></div>
          <div class="tracker-stat-label">Under Review</div>
        </div>
      </div>

      <div class="tracker-table-wrap">
        <table class="tracker-table">
          <thead>
            <tr>
              <th>Job Title</th>
              <th>Company</th>
              <th>Applied Date</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="applicationsTableBody">
            <?php if (empty($applications)): ?>
              <tr id="emptyStateRow">
                <td colspan="5" style="text-align: center; padding: 2rem; color: #5a716a;">
                  No applications yet. <a href="../job-postings/" style="color: #1e9e86; text-decoration: none; font-weight: 600;">Browse jobs</a> and start applying!
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($applications as $app): ?>
                <?php
                  $status = $app['status'];
                  $statusClass = 'status-pending';
                  
                  if (stripos($status, 'offer') !== false) {
                    $statusClass = 'status-approved';
                  } elseif (stripos($status, 'interview') !== false) {
                    $statusClass = 'status-interview';
                  } elseif (stripos($status, 'review') !== false) {
                    $statusClass = 'status-reviewed';
                  } elseif (stripos($status, 'rejected') !== false || stripos($status, 'not selected') !== false) {
                    $statusClass = 'status-rejected';
                  }
                  
                  $appliedDate = date('Y-m-d', strtotime($app['application_date']));
                ?>
                <tr>
                  <td class="tracker-job-title"><?php echo htmlspecialchars($app['job_title']); ?></td>
                  <td class="tracker-company"><?php echo htmlspecialchars($app['company_name']); ?></td>
                  <td><?php echo htmlspecialchars($appliedDate); ?></td>
                  <td>
                    <span class="status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($status); ?></span>
                  </td>
                  <td><button class="action-btn">View Details</button></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </main>

    <footer class="footer">
      <div class="footer-inner">
        <div class="footer-top">
          <div class="footer-brand">
            <h3>TalentScout AI</h3>
            <p>
              Smart AI-powered recruitment platform for PESO Nasugbu, Batangas.
              Follow your application journey with a clear, unified dashboard.
            </p>
          </div>
          <div class="footer-col">
            <h4>Employee Tools</h4>
            <ul>
              <li><a href="../job-postings/">Job Postings</a></li>
              <li><a href="../ai-matching/">AI Matching</a></li>
              <li><a href="../skill-gap-analysis/">Skill Gap Analysis</a></li>
              <li><a href="./">Application Tracker</a></li>
            </ul>
          </div>
          <div class="footer-col">
            <h4>Account</h4>
            <ul>
              <li><a href="../../login.php">Login</a></li>
              <li><a href="../../signup.php">Sign Up</a></li>
            </ul>
          </div>
          <div class="footer-col">
            <h4>PESO Nasugbu</h4>
            <ul>
              <li><a href="#">Nasugbu, Batangas</a></li>
              <li><a href="#">Contact Us</a></li>
            </ul>
          </div>
        </div>
        <div class="footer-bottom">
          <span>© 2026 TalentScout AI - PESO Nasugbu, Batangas</span>
          <span>Track your path from application to hiring</span>
        </div>
      </div>
    </footer>
    <div id="application-details-modal" class="app-details-modal" aria-hidden="true">
      <div class="app-details-card" role="dialog" aria-modal="true" aria-labelledby="app-details-title">
        <div class="app-details-head">
          <div>
            <h3 id="app-details-title">Application Details</h3>
            <p id="app-details-subtext">Comprehensive snapshot of your selected job application.</p>
          </div>
          <button type="button" id="app-details-close-btn" aria-label="Close details dialog">×</button>
        </div>
        <div id="app-details-body" class="app-details-body"></div>
        <div class="app-details-footer">
          <button type="button" id="app-details-ok-btn">Close</button>
        </div>
      </div>
    </div>
    <style>
      html,
      body {
        height: 100%;
        margin: 0;
        padding: 0;
      }

      body {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
      }

      .navbar {
        flex-shrink: 0;
      }

      .page-header {
        flex-shrink: 0;
      }

      .employee-module-shell {
        flex: 1;
        padding-top: 2.25rem;
      }

      .footer {
        flex-shrink: 0;
        margin-top: auto;
      }

      .tracker-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1.05rem;
        margin-bottom: 1.65rem;
      }

      .tracker-stat-card {
        position: relative;
        overflow: hidden;
        background: linear-gradient(160deg, #ffffff 0%, #f6fcfa 100%);
        border: 1px solid #d6e5df;
        border-radius: 14px;
        box-shadow: 0 8px 22px rgba(16, 48, 41, 0.07);
        padding: 1.28rem 1.2rem 1.18rem;
        transition: transform 180ms ease, box-shadow 180ms ease;
      }

      .tracker-stat-card::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background: linear-gradient(90deg, #1e9e86 0%, #5bc9ae 100%);
      }

      .tracker-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 26px rgba(16, 48, 41, 0.1);
      }

      .tracker-stat-number {
        font-size: 2rem;
        font-weight: 800;
        line-height: 1.05;
        color: #0f6f5d;
        letter-spacing: -0.02em;
      }

      .tracker-stat-label {
        margin-top: 0.4rem;
        color: #5a716a;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.42px;
        text-transform: uppercase;
      }

      .tracker-table-wrap {
        border: 1px solid #d6e5df;
        border-radius: 14px;
        box-shadow: 0 14px 28px rgba(12, 43, 37, 0.06);
      }

      .tracker-table th {
        font-size: 0.75rem;
        font-weight: 800;
        color: #4c635c;
        text-transform: uppercase;
        letter-spacing: 0.45px;
        background: linear-gradient(180deg, #f5fcfa 0%, #eef8f4 100%);
        border-bottom: 1px solid #dbe8e3;
      }

      .tracker-table td {
        border-bottom: 1px solid #e6efec;
      }

      .tracker-table tbody tr:nth-child(even) {
        background: #fcfefd;
      }

      .tracker-table tbody tr:hover {
        background: #f4fbf8;
      }

      .action-btn {
        border: 1px solid #bfd9d0;
        background: #f6fcfa;
        color: #135f50;
        border-radius: 9px;
        padding: 0.42rem 0.74rem;
        font-size: 0.79rem;
        font-weight: 700;
        letter-spacing: 0.15px;
        box-shadow: 0 3px 10px rgba(15, 75, 63, 0.08);
        transition: all 0.18s ease;
      }

      .action-btn:hover {
        background: #ffffff;
        border-color: #1e9e86;
        color: #0e5345;
        transform: translateY(-1px);
      }

      .app-details-modal {
        position: fixed;
        inset: 0;
        background:
          radial-gradient(circle at 15% 20%, rgba(79, 191, 164, 0.18) 0%, rgba(79, 191, 164, 0) 45%),
          rgba(5, 18, 17, 0.56);
        backdrop-filter: blur(3px);
        display: none;
        align-items: center;
        justify-content: center;
        padding: 16px;
        z-index: 1200;
      }

      .app-details-modal.is-open {
        display: flex;
      }

      .app-details-card {
        width: 100%;
        max-width: 700px;
        background: #ffffff;
        border-radius: 20px;
        border: 1px solid #dbe8e3;
        box-shadow: 0 30px 70px rgba(16, 24, 40, 0.3);
        padding: 0;
        text-align: left;
        overflow: hidden;
        animation: appDetailsPop 180ms ease-out;
      }

      @keyframes appDetailsPop {
        from {
          opacity: 0;
          transform: translateY(8px) scale(0.985);
        }
        to {
          opacity: 1;
          transform: translateY(0) scale(1);
        }
      }

      .app-details-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        padding: 22px 24px 18px;
        background:
          linear-gradient(120deg, rgba(30, 158, 134, 0.13) 0%, rgba(30, 158, 134, 0.03) 46%, #ffffff 100%),
          #ffffff;
        border-bottom: 1px solid #e2eee9;
      }

      .app-details-head h3 {
        margin: 0;
        font-size: 1.22rem;
        color: #163b34;
      }

      .app-details-head p {
        margin: 7px 0 0;
        font-size: 0.9rem;
        color: #4f6b63;
        max-width: 46ch;
      }

      #app-details-close-btn {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        border: 1px solid #cde0d9;
        background: #ffffff;
        color: #37554f;
        font-size: 1.4rem;
        line-height: 1;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(23, 83, 72, 0.12);
        transition: all 140ms ease;
      }

      #app-details-close-btn:hover {
        transform: translateY(-1px);
        border-color: #aac7be;
      }

      .app-details-body {
        padding: 18px 24px 8px;
      }

      .app-details-summary {
        border: 1px solid #d9e8e2;
        border-radius: 16px;
        padding: 16px;
        background: linear-gradient(145deg, #f7fffc 0%, #ffffff 100%);
        margin-bottom: 8px;
      }

      .app-details-summary-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 10px;
      }

      .app-details-role-block {
        min-width: 0;
      }

      .app-details-summary h4 {
        margin: 0;
        font-size: 1.08rem;
        color: #113630;
      }

      .app-details-company {
        display: inline-block;
        margin-top: 4px;
        font-size: 0.9rem;
        color: #5a766d;
      }

      .app-details-status-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        font-size: 0.76rem;
        font-weight: 800;
        letter-spacing: 0.2px;
        text-transform: uppercase;
        white-space: nowrap;
        padding: 7px 11px;
        border: 1px solid transparent;
      }

      .app-details-status-pill.tone-offer {
        background: #e7fbf1;
        color: #116f54;
        border-color: #a9e3c9;
      }

      .app-details-status-pill.tone-interview {
        background: #edf7ff;
        color: #145ea6;
        border-color: #c7ddf5;
      }

      .app-details-status-pill.tone-review {
        background: #fff7e7;
        color: #8a5a02;
        border-color: #f2d9a8;
      }

      .app-details-status-pill.tone-pending {
        background: #f4f7fb;
        color: #4b6075;
        border-color: #d8e0eb;
      }

      .app-details-status-pill.tone-rejected {
        background: #fdeeee;
        color: #9a2d2d;
        border-color: #f3c4c4;
      }

      .app-details-progress {
        margin: 8px 0 12px;
      }

      .app-details-progress-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 6px;
        font-size: 0.79rem;
        font-weight: 700;
        color: #4f6961;
      }

      .app-details-progress-track {
        width: 100%;
        height: 8px;
        border-radius: 999px;
        background: #eaf2ef;
        overflow: hidden;
      }

      .app-details-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #1e9e86 0%, #45bea2 100%);
        border-radius: 999px;
      }

      .app-details-meta {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
      }

      .app-details-row {
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 11px 12px;
        border: none;
        border-radius: 10px;
        background: #f2f7f5;
        min-height: 88px;
      }

      .app-details-label {
        display: block;
        color: #5b746c;
        font-weight: 700;
        font-size: 0.73rem;
        letter-spacing: 0.3px;
        text-transform: uppercase;
        margin-bottom: 4px;
      }

      .app-details-value {
        color: #1c2f29;
        font-weight: 700;
        font-size: 0.91rem;
      }

      .app-details-notes {
        margin-top: 12px;
        border: 1px dashed #c6ddd4;
        border-radius: 12px;
        background: #fcfffd;
        padding: 11px 12px;
      }

      .app-details-notes-label {
        font-size: 0.73rem;
        letter-spacing: 0.25px;
        text-transform: uppercase;
        font-weight: 800;
        color: #55766c;
        margin-bottom: 4px;
      }

      .app-details-notes p {
        margin: 0;
        font-size: 0.87rem;
        line-height: 1.5;
        color: #34544b;
      }

      .app-details-footer {
        padding: 2px 24px 10px;
        display: flex;
        justify-content: flex-end;
      }

      #app-details-ok-btn {
        margin: 2px 0 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #1e9e86;
        border-radius: 10px;
        color: #ffffff;
        padding: 11px 24px;
        font-weight: 700;
        cursor: pointer;
        box-shadow: 0 8px 20px rgba(21, 122, 104, 0.22);
        transition: all 160ms ease;
      }

      #app-details-ok-btn:hover {
        filter: brightness(0.96);
        transform: translateY(-1px);
      }

      @media (max-width: 560px) {
        .tracker-stats {
          grid-template-columns: 1fr;
        }

        .app-details-head {
          padding: 16px 16px 14px;
        }

        .app-details-body {
          padding: 14px 16px 4px;
        }

        .app-details-footer {
          padding: 2px 16px 10px;
        }

        .app-details-summary-top {
          flex-direction: column;
          align-items: flex-start;
        }

        .app-details-meta {
          grid-template-columns: 1fr;
        }
      }
    </style>
    <script>
      const detailsModal = document.getElementById('application-details-modal');
      const detailsBody = document.getElementById('app-details-body');
      const detailsOkBtn = document.getElementById('app-details-ok-btn');
      const detailsCloseBtn = document.getElementById('app-details-close-btn');
      const detailsSubtext = document.getElementById('app-details-subtext');

      if (detailsOkBtn) {
        const rootStyle = window.getComputedStyle(document.documentElement);
        const primaryDark = rootStyle.getPropertyValue('--primary-dark').trim() || '#1e9e86';
        detailsOkBtn.style.backgroundColor = primaryDark;
      }

      function escapeHtml(value) {
        return value
          .replaceAll('&', '&amp;')
          .replaceAll('<', '&lt;')
          .replaceAll('>', '&gt;')
          .replaceAll('"', '&quot;')
          .replaceAll("'", '&#39;');
      }

      function formatAppliedDate(value) {
        const parsed = new Date(value);
        if (Number.isNaN(parsed.getTime())) {
          return value;
        }

        return parsed.toLocaleDateString('en-PH', {
          year: 'numeric',
          month: 'short',
          day: '2-digit'
        });
      }

      function closeDetailsModal() {
        detailsModal.classList.remove('is-open');
        detailsModal.setAttribute('aria-hidden', 'true');
      }

      function getStatusMetadata(status) {
        const normalized = status.toLowerCase();

        if (normalized.includes('offer')) {
          return {
            toneClass: 'tone-offer',
            progress: 96,
            stage: 'Final Stage',
            nextStep: 'Review offer package and confirm availability',
            priority: 'High',
            note: 'Strong momentum. Keep communication fast and professional to secure the role.'
          };
        }

        if (normalized.includes('interview')) {
          return {
            toneClass: 'tone-interview',
            progress: 72,
            stage: 'Interview Stage',
            nextStep: 'Prepare portfolio highlights and interview talking points',
            priority: 'High',
            note: 'Focus on role-specific achievements and measurable impact during interviews.'
          };
        }

        if (normalized.includes('review')) {
          return {
            toneClass: 'tone-review',
            progress: 48,
            stage: 'Evaluation Stage',
            nextStep: 'Follow up with a concise and polite status inquiry',
            priority: 'Medium',
            note: 'Application is active. A timely follow-up can improve visibility with recruiters.'
          };
        }

        if (normalized.includes('not selected') || normalized.includes('reject')) {
          return {
            toneClass: 'tone-rejected',
            progress: 100,
            stage: 'Closed',
            nextStep: 'Request feedback and apply learnings to upcoming opportunities',
            priority: 'Low',
            note: 'Treat this as a learning checkpoint. Update your resume and target-fit strategy.'
          };
        }

        return {
          toneClass: 'tone-pending',
          progress: 28,
          stage: 'Submitted',
          nextStep: 'Continue monitoring and keep profile documents ready',
          priority: 'Medium',
          note: 'No employer action yet. Keep your profile active and continue applying strategically.'
        };
      }

      document.querySelectorAll('.action-btn').forEach((button) => {
        button.addEventListener('click', () => {
          const row = button.closest('tr');
          if (!row) return;

          const jobTitle = row.querySelector('.tracker-job-title')?.textContent?.trim() || 'N/A';
          const company = row.querySelector('.tracker-company')?.textContent?.trim() || 'N/A';
          const appliedDate = row.cells[2]?.textContent?.trim() || 'N/A';
          const status = row.querySelector('.status-badge')?.textContent?.trim() || 'N/A';
          const formattedDate = formatAppliedDate(appliedDate);
          const statusMeta = getStatusMetadata(status);

          if (detailsSubtext) {
            detailsSubtext.textContent = 'Comprehensive snapshot for ' + jobTitle + ' at ' + company + '.';
          }

          detailsBody.innerHTML =
            '<div class="app-details-summary">' +
            '<div class="app-details-summary-top">' +
            '<div class="app-details-role-block"><h4>' +
            escapeHtml(jobTitle) +
            '</h4><span class="app-details-company">' +
            escapeHtml(company) +
            '</span></div>' +
            '<span class="app-details-status-pill ' +
            escapeHtml(statusMeta.toneClass) +
            '">' +
            escapeHtml(status) +
            '</span></div>' +
            '<div class="app-details-progress">' +
            '<div class="app-details-progress-head"><span>Stage Progress</span><span>' +
            String(statusMeta.progress) +
            '%</span></div>' +
            '<div class="app-details-progress-track"><div class="app-details-progress-fill" style="width: ' +
            String(statusMeta.progress) +
            '%;"></div></div>' +
            '</div>' +
            '<div class="app-details-meta">' +
            '<div class="app-details-row"><span class="app-details-label">Applied Date</span><span class="app-details-value">' +
            escapeHtml(formattedDate) +
            '</span></div>' +
            '<div class="app-details-row"><span class="app-details-label">Current Status</span><span class="app-details-value">' +
            escapeHtml(status) +
            '</span></div>' +
            '<div class="app-details-row"><span class="app-details-label">Application Stage</span><span class="app-details-value">' +
            escapeHtml(statusMeta.stage) +
            '</span></div>' +
            '<div class="app-details-row"><span class="app-details-label">Next Recommended Step</span><span class="app-details-value">' +
            escapeHtml(statusMeta.nextStep) +
            '</span></div>' +
            '<div class="app-details-row"><span class="app-details-label">Response Priority</span><span class="app-details-value">' +
            escapeHtml(statusMeta.priority) +
            '</span></div>' +
            '</div>' +
            '<div class="app-details-notes"><div class="app-details-notes-label">Hiring Insight</div><p>' +
            escapeHtml(statusMeta.note) +
            '</p></div></div>';
          detailsModal.classList.add('is-open');
          detailsModal.setAttribute('aria-hidden', 'false');
          detailsOkBtn?.focus();
        });
      });

      detailsOkBtn?.addEventListener('click', () => {
        closeDetailsModal();
      });

      detailsCloseBtn?.addEventListener('click', () => {
        closeDetailsModal();
      });

      detailsModal?.addEventListener('click', (event) => {
        if (event.target === detailsModal) {
          closeDetailsModal();
        }
      });

      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && detailsModal?.classList.contains('is-open')) {
          closeDetailsModal();
        }
      });
    </script>

    <script>
      // ─── Dynamic Application Loading ──────────────────────────────────
      
      async function loadApplications() {
        try {
          const response = await fetch('./get-applications.php');
          const data = await response.json();

          if (!data.success) {
            console.error('Failed to load applications:', data.message);
            return;
          }

          const applications = data.applications;
          const stats = data.stats;

          // Update statistics
          document.getElementById('totalAppCount').textContent = stats.totalApplications;
          document.getElementById('interviewCount').textContent = stats.interviewsScheduled;
          document.getElementById('offersCount').textContent = stats.jobOffers;
          document.getElementById('reviewCount').textContent = stats.underReview;

          // Update table
          const tableBody = document.getElementById('applicationsTableBody');
          const emptyState = document.getElementById('emptyStateRow');

          if (applications.length === 0) {
            // Show empty state
            tableBody.innerHTML = '<tr id="emptyStateRow"><td colspan="5" style="text-align: center; padding: 2rem; color: #5a716a;">No applications yet. <a href="../job-postings/" style="color: #1e9e86; text-decoration: none; font-weight: 600;">Browse jobs</a> and start applying!</td></tr>';
          } else {
            // Hide empty state and populate table
            tableBody.innerHTML = applications.map(app => {
              const status = app.status;
              let statusClass = 'status-pending';

              if (status.toLowerCase().includes('offer')) {
                statusClass = 'status-approved';
              } else if (status.toLowerCase().includes('interview')) {
                statusClass = 'status-interview';
              } else if (status.toLowerCase().includes('review')) {
                statusClass = 'status-reviewed';
              } else if (status.toLowerCase().includes('rejected') || status.toLowerCase().includes('not selected')) {
                statusClass = 'status-rejected';
              }

              const appliedDate = new Date(app.application_date).toLocaleDateString('en-CA');

              return `
                <tr>
                  <td class="tracker-job-title">${escapeHtml(app.job_title)}</td>
                  <td class="tracker-company">${escapeHtml(app.company_name)}</td>
                  <td>${escapeHtml(appliedDate)}</td>
                  <td>
                    <span class="status-badge ${statusClass}">${escapeHtml(status)}</span>
                  </td>
                  <td><button class="action-btn">View Details</button></td>
                </tr>
              `;
            }).join('');
          }
        } catch (error) {
          console.error('Error loading applications:', error);
        }
      }

      function escapeHtml(text) {
        const map = {
          '&': '&amp;',
          '<': '&lt;',
          '>': '&gt;',
          '"': '&quot;',
          "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
      }

      // Load applications on page load
      document.addEventListener('DOMContentLoaded', () => {
        loadApplications();

        // Reload applications periodically (every 30 seconds)
        setInterval(loadApplications, 30000);

        // Also reload when the page comes into focus (user comes back from job listings)
        document.addEventListener('visibilitychange', () => {
          if (!document.hidden) {
            loadApplications();
          }
        });

        // Listen for custom event from job-postings (if they trigger it)
        window.addEventListener('applicationSubmitted', () => {
          loadApplications();
        });
      });
    </script>
    <script src="../../employee-auth.js"></script>
  </body>
</html>
