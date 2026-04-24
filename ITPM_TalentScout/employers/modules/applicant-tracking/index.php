<?php 
session_start();
require_once('../../../database/db.php');

// Get database connection
$conn = getConnection();
$employer_id = isset($_SESSION['employer_id']) ? $_SESSION['employer_id'] : 1; // Default to employer 1 for testing

// Fetch all applications for this employer's jobs
$applications = [];
$stmt = $conn->prepare("SELECT 
  a.application_id, 
  a.job_post_id,
  a.employee_id,
  a.status,
  a.application_date,
  e.first_name,
  e.last_name,
  jp.title as job_title
FROM application a
JOIN job_post jp ON a.job_post_id = jp.job_post_id
JOIN employee e ON a.employee_id = e.employee_id
WHERE jp.employer_id = ?
ORDER BY a.application_date DESC");
$stmt->bind_param("i", $employer_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $applications[] = $row;
}
$stmt->close();

// Calculate stats
$stats = [
  'total' => count($applications),
  'applied' => 0,
  'review' => 0,
  'interview' => 0,
  'offer' => 0,
  'hired' => 0
];

foreach ($applications as $app) {
  switch (strtolower($app['status'])) {
    case 'applied':
      $stats['applied']++;
      break;
    case 'in review':
    case 'review':
      $stats['review']++;
      break;
    case 'interview':
      $stats['interview']++;
      break;
    case 'offer sent':
    case 'offer':
      $stats['offer']++;
      break;
    case 'hired':
      $stats['hired']++;
      break;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Application Tracker — TalentScout AI</title>
  <link rel="stylesheet" href="../../../styles/global.css">
  <link rel="stylesheet" href="../../../styles/page-layout.css">
  <style>
    /* ===== STICKY FOOTER LAYOUT ===== */
    html,
    body {
      height: 100%;
      margin: 0;
      padding: 0;
    }

    body {
      display: flex;
      flex-direction: column;
    }

    /* Main content area expands to fill available space */
    .page-container,
    main {
      flex: 1 0 auto;
    }

    /* Footer stays at the bottom */
    .footer {
      flex-shrink: 0;
    }

    .container { max-width: 1400px; margin: 0 auto; padding: 2.5rem; }
    
    .page-header {
      margin-bottom: 2.5rem;
    }
    .page-header h1 {
      font-size: 1.75rem;
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 0.5rem;
    }
    .page-header p {
      color: var(--text-light);
      font-size: 0.95rem;
    }

    /* Stats Block */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1.5rem;
      margin-bottom: 3rem;
    }
    .stat-card {
      background: white;
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.5rem;
    }
    .stat-value {
      font-size: 2.2rem;
      font-weight: 800;
      color: var(--primary-dark);
      margin-bottom: 0.5rem;
    }
    .stat-label {
      font-size: 0.85rem;
      color: var(--text-light);
      text-transform: uppercase;
      letter-spacing: 0.6px;
      font-weight: 600;
    }

    /* Table */
    .table-wrapper {
      background: white;
      border: 1px solid var(--border);
      border-radius: var(--radius);
      overflow: hidden;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    thead {
      background: #f5f5f5;
      border-bottom: 1px solid var(--border);
    }

    th {
      padding: 1rem 1.25rem;
      text-align: left;
      font-size: 0.85rem;
      font-weight: 700;
      color: var(--text-dark);
      text-transform: uppercase;
      letter-spacing: 0.4px;
    }

    tbody tr {
      border-bottom: 1px solid var(--border);
      transition: all 0.2s ease;
    }

    tbody tr:hover {
      background-color: #fafafa;
      box-shadow: inset 0 0 12px rgba(30, 158, 134, 0.08);
    }

    tbody tr:last-child {
      border-bottom: none;
    }

    td {
      padding: 1.25rem;
      font-size: 0.95rem;
      color: var(--text-dark);
    }

    .candidate-col {
      font-weight: 600;
    }

    .position-col {
      color: var(--text-light);
      font-size: 0.9rem;
    }

    .status-col {
      font-weight: 600;
      padding: 0.5rem 0.75rem;
      border-radius: 4px;
      font-size: 0.85rem;
      display: inline-block;
      text-transform: uppercase;
      letter-spacing: 0.3px;
    }

    .status-applied { background: #e8e8e8; color: #666; }
    .status-review { background: #fff3cd; color: #865c00; }
    .status-interview { background: #d1ecf1; color: #0c5460; }
    .status-offer { background: #d4edda; color: #155724; }
    .status-hired { background: #98FBCB; color: #1a1a1a; font-weight: 700; }

    .match-score {
      font-weight: 700;
      color: var(--primary-dark);
    }

    .date-col {
      color: var(--text-light);
      font-size: 0.9rem;
    }

    .action-col {
      text-align: center;
    }

    .btn-small {
      background: white;
      border: 1px solid var(--border);
      padding: 0.4rem 0.8rem;
      border-radius: 4px;
      font-size: 0.8rem;
      cursor: pointer;
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      font-weight: 600;
      color: var(--text-dark);
    }

    .btn-small:hover {
      background: var(--primary-dark);
      color: white;
      border-color: var(--primary-dark);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(30, 158, 134, 0.15);
    }

    .btn-small:active {
      transform: translateY(0);
    }

    .footer { background: #1a1a1a; color: white; padding: 2rem; margin-top: 3rem; text-align: center; }
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
      <li><a href="../post-jobs/">Post Jobs</a></li>
      <li><a href="../employee-finder/">Find Talent</a></li>
      <li><a href="./" class="active">Hiring Pipeline</a></li>
      <li><a href="../chat-sms/">Messages</a></li>
    </ul>
    <div class="nav-actions">
      <?php if (isset($_SESSION['employer_id'])): ?>
        <span class="nav-user">Welcome, <?php echo htmlspecialchars($_SESSION['employer_name'] ?? 'Employer'); ?></span>
        <a href="../../logout.php" class="btn btn-outline">Logout</a>
      <?php else: ?>
        <a href="../../login.php" class="btn btn-outline">Login</a>
        <a href="../../signup.php" class="btn btn-primary">Get Started</a>
      <?php endif; ?>
    </div>
  </nav>

  <div class="container">
    <div class="page-header">
      <h1>Job Application Tracker</h1>
      <p>Manage your entire hiring pipeline from application to hire</p>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-value"><?php echo $stats['total']; ?></div>
        <div class="stat-label">Total Applications</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?php echo ($stats['applied'] + $stats['review']); ?></div>
        <div class="stat-label">Ready to Review</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?php echo $stats['interview']; ?></div>
        <div class="stat-label">Interviews Scheduled</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?php echo ($stats['offer'] + $stats['hired']); ?></div>
        <div class="stat-label">Offers Sent / Hired</div>
      </div>
    </div>

    <!-- Kanban Board -->
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th style="width: 20%;">Candidate Name</th>
            <th style="width: 15%;">Position</th>
            <th style="width: 15%;">Status</th>
            <th style="width: 12%;">Match Score</th>
            <th style="width: 18%;">Applied Date</th>
            <th style="width: 20%;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($applications) > 0): ?>
            <?php foreach ($applications as $app): 
              // Normalize status for display
              $status = strtolower($app['status']);
              $status_display = ucfirst($status);
              if ($status === 'in review') $status_display = 'In Review';
              if ($status === 'offer sent') $status_display = 'Offer Sent';
              
              // Determine status class
              $status_class = 'status-applied';
              if (in_array($status, ['in review', 'review'])) $status_class = 'status-review';
              if ($status === 'interview') $status_class = 'status-interview';
              if (in_array($status, ['offer sent', 'offer'])) $status_class = 'status-offer';
              if ($status === 'hired') $status_class = 'status-hired';
              
              // Generate match score (random for now, could be calculated from skills later)
              $match_score = rand(75, 98);
            ?>
            <tr>
              <td class="candidate-col"><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></td>
              <td class="position-col"><?php echo htmlspecialchars($app['job_title']); ?></td>
              <td><span class="status-col <?php echo $status_class; ?>"><?php echo $status_display; ?></span></td>
              <td class="match-score"><?php echo $match_score; ?>%</td>
              <td class="date-col"><?php echo date('M j, Y', strtotime($app['application_date'])); ?></td>
              <td class="action-col"><button class="btn-small">View</button></td>
            </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" style="text-align: center; color: var(--text-light); padding: 2rem;">
                No applications yet. Share your job postings with candidates to start receiving applications.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <footer class="footer">
    <p>&copy; 2026 TalentScout AI — PESO Nasugbu, Batangas. Streamlined hiring process.</p>
  </footer>

  <script>
    // Animate table rows on load
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach((row, index) => {
      row.style.opacity = '0';
      row.style.transform = 'translateY(10px)';
      row.style.transition = `opacity 0.4s ease ${index * 0.05}s, transform 0.4s ease ${index * 0.05}s`;
      setTimeout(() => {
        row.style.opacity = '1';
        row.style.transform = 'translateY(0)';
      }, 10);
    });

    // Enhance button interactions
    document.querySelectorAll('.btn-small').forEach(btn => {
      btn.addEventListener('click', function(e) {
        // Prevent default action for demo
        e.preventDefault();
        
        // Add click feedback
        this.style.transform = 'scale(0.95)';
        setTimeout(() => {
          this.style.transform = '';
        }, 100);
      });
    });

    // Add hover effect to rows
    document.querySelectorAll('tbody tr').forEach(row => {
      row.addEventListener('mouseenter', function() {
        this.style.transform = 'scale(1.01)';
        this.style.transformOrigin = 'center';
      });
      row.addEventListener('mouseleave', function() {
        this.style.transform = 'scale(1)';
      });
    });
  </script>

</body>
</html>
