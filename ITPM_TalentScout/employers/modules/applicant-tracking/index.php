<?php 
session_start();
require_once('../../../database/db.php');

// Check if employer is logged in
if (!isset($_SESSION['employer_id'])) {
  header('Location: ../../login.php');
  exit;
}

// Get database connection
$conn = getConnection();
$employer_id = (int)$_SESSION['employer_id'];

// Fetch all applications for this employer's jobs
$applications = [];
$stmt = $conn->prepare("SELECT 
  a.application_id, 
  a.job_post_id,
  a.employee_id,
  a.status,
  a.hire_status,
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
  // Calculate display status based on hire_status
  $hireStatus = $row['hire_status'] ?? 'none';
  if ($hireStatus === 'accepted') {
    $row['display_status'] = 'Hired';
  } elseif ($hireStatus === 'rejected') {
    $row['display_status'] = 'Offer Declined';
  } elseif ($hireStatus === 'offered') {
    $row['display_status'] = 'Offer Sent';
  } else {
    $row['display_status'] = $row['status'];
  }
  $applications[] = $row;
}
$stmt->close();

// Calculate stats
$stats = [
  'total' => count($applications),
  'applied' => 0,
  'interview' => 0,
  'offer' => 0,
  'hired' => 0,
  'rejected' => 0
];

foreach ($applications as $app) {
  $hireStatus = $app['hire_status'] ?? 'none';
  
  if ($hireStatus === 'accepted') {
    $stats['hired']++;
  } elseif ($hireStatus === 'offered') {
    $stats['offer']++;
  } elseif ($hireStatus === 'rejected') {
    $stats['rejected']++;
  } else {
    switch (strtolower($app['status'])) {
      case 'pending':
        $stats['applied']++;
        break;
      case 'interview scheduled':
        $stats['interview']++;
        break;
      case 'rejected':
        $stats['rejected']++;
        break;
    }
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
    .status-interview { background: #d1ecf1; color: #0c5460; }
    .status-offer { background: #d4edda; color: #155724; }
    .status-rejected { background: #f8d7da; color: #721c24; }
    .status-hired { background: #c8e6c9; color: #1b5e20; }

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
      text-decoration: none;
      display: inline-block;
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

    /* Modal Styles */
    .modal {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 1000;
      display: flex;
      align-items: center;
      justify-content: center;
      animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .modal-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      cursor: pointer;
    }

    .modal-content {
      position: relative;
      background: white;
      border-radius: var(--radius);
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      max-width: 600px;
      width: 90%;
      max-height: 85vh;
      overflow-y: auto;
      animation: slideUp 0.3s ease;
    }

    @keyframes slideUp {
      from {
        transform: translateY(30px);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1.5rem;
      border-bottom: 1px solid var(--border);
    }

    .modal-header h2 {
      margin: 0;
      font-size: 1.25rem;
    }

    .modal-close {
      background: none;
      border: none;
      font-size: 2rem;
      cursor: pointer;
      color: var(--text-light);
      padding: 0;
      width: 2.5rem;
      height: 2.5rem;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s ease;
    }

    .modal-close:hover {
      color: var(--text-dark);
      transform: rotate(90deg);
    }

    .modal-body {
      padding: 2rem;
    }

    .modal-footer {
      padding: 1.5rem;
      border-top: 1px solid var(--border);
      display: flex;
      gap: 1rem;
      justify-content: flex-end;
    }

    .candidate-info {
      margin-bottom: 2rem;
      padding: 1.5rem;
      background: #f9f9f9;
      border-radius: var(--radius);
    }

    .candidate-info h3 {
      margin-top: 0;
      font-size: 1.1rem;
      margin-bottom: 0.75rem;
    }

    .info-row {
      display: flex;
      justify-content: space-between;
      padding: 0.5rem 0;
      border-bottom: 1px solid #e0e0e0;
    }

    .info-row:last-child {
      border-bottom: none;
    }

    .info-label {
      font-weight: 600;
      color: var(--text-dark);
      min-width: 120px;
    }

    .info-value {
      color: var(--text-light);
      text-align: right;
    }

    .status-update-section {
      margin-top: 2rem;
      padding: 1.5rem;
      background: #f9f9f9;
      border-radius: var(--radius);
    }

    .status-update-section h3 {
      margin-top: 0;
      font-size: 1rem;
      margin-bottom: 1rem;
    }

    .status-buttons {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 0.75rem;
    }

    .status-btn {
      padding: 0.6rem 0.8rem;
      border: 1px solid var(--border);
      border-radius: 4px;
      background: white;
      cursor: pointer;
      font-size: 0.85rem;
      font-weight: 600;
      transition: all 0.2s ease;
      color: var(--text-dark);
    }

    .status-btn:hover {
      background: var(--primary-light);
      border-color: var(--primary-dark);
      color: var(--primary-dark);
    }

    .status-btn.btn-reject {
      grid-column: 1 / -1;
      background: #fee;
      border-color: #fcc;
      color: #c33;
    }

    .status-btn.btn-reject:hover {
      background: #fdd;
      border-color: #f99;
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
        <div class="stat-value"><?php echo ($stats['applied'] + $stats['interview']); ?></div>
        <div class="stat-label">Ready to Review</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?php echo $stats['interview']; ?></div>
        <div class="stat-label">Interviews</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?php echo $stats['offer']; ?></div>
        <div class="stat-label">Offers Sent</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?php echo $stats['hired']; ?></div>
        <div class="stat-label">Hired</div>
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
              // Use display_status from query
              $displayStatus = $app['display_status'];
              $hireStatus = $app['hire_status'] ?? 'none';
              
              // Determine status class based on hire_status first
              $status_class = 'status-applied';
              if ($hireStatus === 'accepted') {
                $status_class = 'status-hired';
              } elseif ($hireStatus === 'rejected') {
                $status_class = 'status-rejected';
              } elseif ($hireStatus === 'offered') {
                $status_class = 'status-offer';
              } else {
                $status = strtolower($app['status']);
                if ($status === 'interview scheduled') $status_class = 'status-interview';
                if ($status === 'rejected') $status_class = 'status-rejected';
              }
              
              // Generate match score (random for now, could be calculated from skills later)
              $match_score = rand(75, 98);
            ?>
            <tr data-application-id="<?php echo $app['application_id']; ?>">
              <td class="candidate-col"><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></td>
              <td class="position-col"><?php echo htmlspecialchars($app['job_title']); ?></td>
              <td><span class="status-col <?php echo $status_class; ?>"><?php echo htmlspecialchars($displayStatus); ?></span></td>
              <td class="match-score"><?php echo $match_score; ?>%</td>
              <td class="date-col"><?php echo date('M j, Y', strtotime($app['application_date'])); ?></td>
              <td class="action-col">
                <button class="btn-small view-details" data-application-id="<?php echo $app['application_id']; ?>">View</button>
              </td>
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

  <!-- Application Details Modal -->
  <div id="applicationModal" class="modal" style="display: none;">
    <div class="modal-overlay" onclick="closeApplicationModal()"></div>
    <div class="modal-content">
      <div class="modal-header">
        <h2>Application Details</h2>
        <button class="modal-close" onclick="closeApplicationModal()">×</button>
      </div>
      <div id="modalBody" class="modal-body">
        <div style="text-align: center; padding: 2rem;">Loading...</div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline" onclick="closeApplicationModal()">Close</button>
      </div>
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

    // View details button functionality
    document.querySelectorAll('.view-details').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        const applicationId = this.getAttribute('data-application-id');
        openApplicationModal(applicationId);
      });
    });

    // Modal functions
    function openApplicationModal(applicationId) {
      const modal = document.getElementById('applicationModal');
      const modalBody = document.getElementById('modalBody');
      
      modal.style.display = 'flex';
      modalBody.innerHTML = '<div style="text-align: center; padding: 2rem;">Loading...</div>';

      // Fetch application details
      fetch(`./get-application.php?application_id=${applicationId}`)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            const app = data.application;
            // Map database status to friendly display
            const status_map = {
              'Pending': 'Applied',
              'Interview Scheduled': 'Interview',
              'Offer Received': 'Offer Sent',
              'Rejected': 'Rejected'
            };
            const friendly_status = status_map[app.status] || app.status;
            const statusOptions = ['Applied', 'Interview', 'Offer Sent', 'Rejected'];
            
            let html = `
              <div class="candidate-info">
                <h3>${app.first_name} ${app.last_name}</h3>
                <div class="info-row">
                  <span class="info-label">Position:</span>
                  <span class="info-value">${app.job_title}</span>
                </div>
                <div class="info-row">
                  <span class="info-label">Email:</span>
                  <span class="info-value"><a href="mailto:${app.email}" style="color: var(--primary-dark); text-decoration: none;">${app.email}</a></span>
                </div>
                <div class="info-row">
                  <span class="info-label">Phone:</span>
                  <span class="info-value">${app.phone || 'N/A'}</span>
                </div>
                <div class="info-row">
                  <span class="info-label">Location:</span>
                  <span class="info-value">${app.address || 'N/A'}</span>
                </div>
                <div class="info-row">
                  <span class="info-label">Applied Date:</span>
                  <span class="info-value">${new Date(app.application_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}</span>
                </div>
                <div class="info-row">
                  <span class="info-label">Current Status:</span>
                  <span class="info-value" style="font-weight: 700;">${friendly_status}</span>
                </div>
              </div>

              <div class="status-update-section">
                <h3>Update Status</h3>
                <div class="status-buttons">
                  ${statusOptions.map(status => 
                    `<button class="status-btn ${status === 'Rejected' ? 'btn-reject' : ''}" data-status="${status}" onclick="updateApplicationStatus(${app.application_id}, '${status}')">
                      ${status}
                    </button>`
                  ).join('')}
                </div>
              </div>

              <div style="margin-top: 2rem; padding: 1.5rem; background: #f9f9f9; border-radius: var(--radius);">
                <h3 style="margin-top: 0; font-size: 1rem;">Job Description</h3>
                <p style="color: var(--text-light); font-size: 0.9rem; line-height: 1.6; margin: 0;">${app.job_description || 'No description available'}</p>
              </div>
            `;
            modalBody.innerHTML = html;
          } else {
            modalBody.innerHTML = `<div style="color: red; text-align: center; padding: 2rem;">Error loading application details</div>`;
          }
        })
        .catch(error => {
          console.error('Error:', error);
          modalBody.innerHTML = `<div style="color: red; text-align: center; padding: 2rem;">Error loading application</div>`;
        });
    }

    function closeApplicationModal() {
      const modal = document.getElementById('applicationModal');
      modal.style.display = 'none';
    }

    function updateApplicationStatus(applicationId, newStatus) {
      const formData = new FormData();
      formData.append('application_id', applicationId);
      formData.append('status', newStatus);

      fetch('./update-application.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert(`Application status updated to "${newStatus}"`);
          closeApplicationModal();
          // Reload the page to see updated status
          location.reload();
        } else {
          alert('Error updating status: ' + data.message);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Error updating application status');
      });
    }

    // Close modal when clicking overlay
    document.addEventListener('click', function(e) {
      const modal = document.getElementById('applicationModal');
      if (e.target === modal.querySelector('.modal-overlay')) {
        closeApplicationModal();
      }
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeApplicationModal();
      }
    });

    // Enhance button interactions
    document.querySelectorAll('.btn-small').forEach(btn => {
      btn.addEventListener('click', function(e) {
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
