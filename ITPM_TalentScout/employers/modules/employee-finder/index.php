<?php 
session_start();
require_once('../../../database/db.php');

// Get database connection
$conn = getConnection();
$employer_id = isset($_SESSION['employer_id']) ? $_SESSION['employer_id'] : 1; // Default to employer 1 for testing

// Fetch all employees with their resumes and skills
$employees = [];
$stmt = $conn->prepare("SELECT DISTINCT
  e.employee_id,
  e.first_name,
  e.last_name,
  e.address,
  IFNULL(r.summary, 'No summary provided') as summary,
  IFNULL(r.resume_id, 0) as resume_id
FROM employee e
LEFT JOIN resumes r ON e.employee_id = r.employee_id
WHERE e.is_active = 1
ORDER BY e.first_name ASC");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $employees[] = $row;
}
$stmt->close();

// Fetch skills for each employee
$employee_skills = [];
foreach ($employees as &$emp) {
  if ($emp['resume_id'] > 0) {
    $stmt = $conn->prepare("SELECT skill_name FROM resume_skills WHERE resume_id = ?");
    $stmt->bind_param("i", $emp['resume_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $skills = [];
    while ($row = $result->fetch_assoc()) {
      $skills[] = $row['skill_name'];
    }
    $stmt->close();
    $emp['skills'] = $skills;
  } else {
    $emp['skills'] = [];
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Employee Finder — TalentScout AI</title>
  <link rel="stylesheet" href="../../../styles/global.css">
  <link rel="stylesheet" href="../../../styles/page-layout.css">
  <style>
    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      animation: fadeIn 0.2s ease;
    }

    .modal.active {
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .modal-content {
      background: white;
      border-radius: var(--radius);
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
      width: 90%;
      max-width: 600px;
      max-height: 85vh;
      overflow-y: auto;
      position: relative;
      animation: slideIn 0.3s ease;
    }

    .modal-header {
      padding: 1.75rem;
      border-bottom: 1px solid var(--border);
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: linear-gradient(135deg, #f8fffc 0%, #f0fffb 100%);
    }

    .modal-header h2 {
      font-size: 1.35rem;
      font-weight: 700;
      color: var(--text-dark);
      margin: 0;
    }

    .modal-close {
      background: none;
      border: none;
      font-size: 1.75rem;
      cursor: pointer;
      color: var(--text-light);
      transition: color 0.2s;
      padding: 0;
      width: 32px;
      height: 32px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .modal-close:hover {
      color: var(--text-dark);
    }

    .modal-body {
      padding: 1.75rem;
    }

    .modal-footer {
      padding: 1.5rem 1.75rem;
      border-top: 1px solid var(--border);
      display: flex;
      gap: 1rem;
      justify-content: flex-end;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    @keyframes slideIn {
      from {
        transform: translateY(-30px);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    /* Improved Candidate Card */
    .candidate-card {
      background: white;
      border: 1.5px solid var(--border);
      border-radius: var(--radius);
      padding: 1.5rem;
      margin-bottom: 1.25rem;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
    }

    .candidate-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: linear-gradient(90deg, var(--primary-dark), var(--primary-light));
      opacity: 0;
      transition: opacity 0.3s ease;
    }

    .candidate-card:hover {
      border-color: var(--primary-dark);
      box-shadow: 0 8px 24px rgba(30, 158, 134, 0.12);
      transform: translateY(-4px);
    }

    .candidate-card:hover::before {
      opacity: 1;
    }

    .candidate-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 1rem;
    }

    .candidate-info h3 {
      font-size: 1.05rem;
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 0.25rem;
    }

    .candidate-role {
      font-size: 0.88rem;
      color: var(--primary-dark);
      font-weight: 600;
    }

    .match-badge {
      background: linear-gradient(135deg, var(--primary-light), #f0fffb);
      color: var(--primary-darker);
      padding: 0.4rem 0.95rem;
      border-radius: 20px;
      font-size: 0.82rem;
      font-weight: 700;
      border: 1px solid var(--primary-dark);
      display: flex;
      align-items: center;
      gap: 0.4rem;
      white-space: nowrap;
    }

    .candidate-meta {
      display: flex;
      gap: 1.5rem;
      font-size: 0.85rem;
      color: var(--text-light);
      margin-bottom: 1rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid var(--border);
    }

    .candidate-meta span {
      display: flex;
      align-items: center;
      gap: 0.3rem;
    }

    .skills-list {
      display: flex;
      flex-wrap: wrap;
      gap: 0.6rem;
      margin-bottom: 1.25rem;
    }

    .skill-tag {
      background: linear-gradient(135deg, #f0fffb, #e8fff5);
      color: var(--primary-dark);
      padding: 0.35rem 0.85rem;
      border-radius: 20px;
      font-size: 0.82rem;
      font-weight: 600;
      border: 1px solid var(--primary-light);
      transition: all 0.2s ease;
    }

    .skill-tag:hover {
      background: var(--primary-light);
      transform: translateY(-2px);
    }

    .action-buttons {
      display: flex;
      gap: 0.75rem;
    }

    .btn-small {
      padding: 0.5rem 1.1rem;
      border-radius: var(--radius-sm);
      font-size: 0.85rem;
      border: 1px solid var(--border);
      cursor: pointer;
      transition: all 0.25s ease;
      font-weight: 600;
      background: white;
      color: var(--text-dark);
      flex: 1;
      text-align: center;
    }

    .btn-primary-small {
      background: var(--primary-dark);
      color: white;
      border-color: var(--primary-dark);
    }

    .btn-primary-small:hover {
      background: var(--primary-darker);
      box-shadow: 0 4px 12px rgba(30, 158, 134, 0.15);
      transform: translateY(-2px);
    }

    .btn-outline-small:hover {
      border-color: var(--primary-dark);
      color: var(--primary-dark);
      box-shadow: 0 2px 8px rgba(30, 158, 134, 0.08);
    }

    /* Secondary Navigation / Tabs */
    .sub-navbar {
      background: white;
      border-bottom: 1px solid var(--border);
      padding: 0 2.5rem;
    }
    .sub-navbar-inner {
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      gap: 2rem;
    }
    .sub-nav-link {
      padding: 1rem 0;
      font-size: 0.95rem;
      font-weight: 600;
      color: var(--text-light);
      text-decoration: none;
      border-bottom: 2px solid transparent;
      transition: all 0.2s;
      cursor: pointer;
    }
    .sub-nav-link.active {
      color: var(--text-dark);
      border-bottom-color: var(--primary-dark);
    }
    .sub-nav-link:hover {
      color: var(--text-dark);
    }

    /* Tab Content */
    .tab-content {
      display: none;
    }
    .tab-content.active {
      display: block;
    }

    .search-bar-wrap {
      background: white;
      border-bottom: 1px solid var(--border);
      padding: 1.25rem 2.5rem;
    }
    .search-bar {
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      gap: 0.75rem;
      align-items: center;
    }
    .search-icon {
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-light);
      font-size: 0.9rem;
      pointer-events: none;
      z-index: 1;
    }
    .search-input-rel {
      position: relative;
      flex: 2;
    }
    .search-input-rel .input {
      padding-left: 2.5rem;
      width: 100%;
    }

    .main-layout {
      max-width: 1200px;
      margin: 0 auto;
      padding: 2rem 2.5rem;
      display: grid;
      grid-template-columns: 260px 1fr;
      gap: 2rem;
    }

    /* Sidebar */
    .sidebar-card {
      background: white;
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.25rem;
      margin-bottom: 1.25rem;
    }
    .sidebar-title {
      font-weight: 700;
      font-size: 0.88rem;
      color: var(--text-dark);
      margin-bottom: 1rem;
      padding-bottom: 0.6rem;
      border-bottom: 1px solid var(--border);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .filter-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      font-size: 0.87rem;
      color: var(--text-mid);
      padding: 0.38rem 0;
    }
    .filter-left {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .fcheck {
      width: 16px;
      height: 16px;
      border: 1.5px solid var(--border);
      border-radius: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.65rem;
      flex-shrink: 0;
    }
    .fcheck.on {
      background: var(--primary-dark);
      border-color: var(--primary-dark);
      color: white;
    }
    .fcount {
      background: var(--bg-light);
      color: var(--text-light);
      padding: 0.1rem 0.5rem;
      border-radius: 100px;
      font-size: 0.75rem;
    }

    /* Results */
    .results-header {
      margin-bottom: 1.5rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .results-title {
      font-size: 1.1rem;
      font-weight: 600;
      color: var(--text-dark);
    }
    .results-count {
      font-size: 0.9rem;
      color: var(--text-light);
    }

    .candidate-card {
      background: white;
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.5rem;
      margin-bottom: 1rem;
      transition: all 0.2s;
    }
    .candidate-card:hover {
      border-color: var(--primary-dark);
      box-shadow: var(--shadow);
    }

    .candidate-header {
      display: flex;
      justify-content: space-between;
      align-items: start;
      margin-bottom: 1rem;
    }
    .candidate-info h3 {
      font-size: 1rem;
      font-weight: 600;
      color: var(--text-dark);
      margin-bottom: 0.25rem;
    }
    .candidate-role {
      font-size: 0.87rem;
      color: var(--text-light);
    }
    .match-badge {
      background: var(--primary-light);
      color: var(--primary-darker);
      padding: 0.35rem 0.75rem;
      border-radius: 4px;
      font-size: 0.8rem;
      font-weight: 600;
    }

    .candidate-meta {
      display: flex;
      gap: 1.5rem;
      font-size: 0.85rem;
      color: var(--text-light);
      margin-bottom: 1rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid var(--border);
    }
    .candidate-meta span {
      display: flex;
      align-items: center;
      gap: 0.3rem;
    }

    .skills-list {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
      margin-bottom: 1rem;
    }
    .skill-tag {
      background: var(--bg-light);
      color: var(--text-mid);
      padding: 0.35rem 0.75rem;
      border-radius: 4px;
      font-size: 0.8rem;
    }

    .action-buttons {
      display: flex;
      gap: 0.75rem;
    }
    .btn-small {
      padding: 0.5rem 1rem;
      border-radius: var(--radius-sm);
      font-size: 0.85rem;
      border: none;
      cursor: pointer;
      transition: all 0.2s;
    }
    .btn-primary-small {
      background: var(--primary-dark);
      color: white;
    }
    .btn-primary-small:hover {
      background: var(--primary-darker);
    }
    .btn-outline-small {
      background: white;
      color: var(--primary-dark);
      border: 1px solid var(--border);
    }
    .btn-outline-small:hover {
      border-color: var(--primary-dark);
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
    <li><a href="../../index.php">Home</a></li>
    <li><a href="../post-jobs/">Post Jobs</a></li>
    <li><a href="./" class="active">Find Talent</a></li>
    <li><a href="../applicant-tracking/">Hiring Pipeline</a></li>
    <li><a href="../chat-sms/">Messages</a></li>
  </ul>
  <div class="nav-actions">
    <a href="#" class="btn btn-outline">Login</a>
    <a href="#" class="btn btn-primary">Get Started</a>
  </div>
</nav>

<!-- SECONDARY NAVBAR -->
<div class="sub-navbar">
  <div class="sub-navbar-inner">
    <a class="sub-nav-link active" onclick="switchTab('normal')">
      Normal Hiring
    </a>
    <a class="sub-nav-link" onclick="switchTab('blind')">
      Blind Hiring
    </a>
  </div>
</div>

<!-- TAB 1: NORMAL HIRING -->
<div id="normal-tab" class="tab-content active">
  <!-- SEARCH BAR -->
  <div class="search-bar-wrap">
    <div class="search-bar">
      <div class="search-input-rel">
        <input type="text" class="input" placeholder="Search by skills, job title, experience...">
      </div>
      <button class="btn btn-primary">Search</button>
    </div>
  </div>

  <!-- CONTENT -->
  <div class="main-layout">
    <!-- SIDEBAR FILTERS -->
    <aside>
      <div class="sidebar-card">
        <div class="sidebar-title">Experience Level</div>
        <div class="filter-item">
          <div class="filter-left">
            <div class="fcheck">-</div>
            <span>Entry Level</span>
          </div>
          <span class="fcount">12</span>
        </div>
        <div class="filter-item">
          <div class="filter-left">
            <div class="fcheck on">✓</div>
            <span>Mid Level</span>
          </div>
          <span class="fcount">24</span>
        </div>
        <div class="filter-item">
          <div class="filter-left">
            <div class="fcheck">-</div>
            <span>Senior</span>
          </div>
          <span class="fcount">18</span>
        </div>
      </div>

      <div class="sidebar-card">
        <div class="sidebar-title">Skills</div>
        <div class="filter-item">
          <div class="filter-left">
            <div class="fcheck on">✓</div>
            <span>React</span>
          </div>
          <span class="fcount">16</span>
        </div>
        <div class="filter-item">
          <div class="filter-left">
            <div class="fcheck on">✓</div>
            <span>Python</span>
          </div>
          <span class="fcount">12</span>
        </div>
        <div class="filter-item">
          <div class="filter-left">
            <div class="fcheck">-</div>
            <span>JavaScript</span>
          </div>
          <span class="fcount">18</span>
        </div>
      </div>

      <div class="sidebar-card">
        <div class="sidebar-title">Location</div>
        <div class="filter-item">
          <div class="filter-left">
            <div class="fcheck on">✓</div>
            <span>Nasugbu</span>
          </div>
          <span class="fcount">42</span>
        </div>
        <div class="filter-item">
          <div class="filter-left">
            <div class="fcheck">-</div>
            <span>Remote</span>
          </div>
          <span class="fcount">8</span>
        </div>
      </div>
    </aside>

    <!-- RESULTS -->
    <div>
      <div class="results-header">
        <div class="results-title">Top Talent Matches</div>
        <div class="results-count">Showing <?php echo count(array_slice($employees, 0, 6)); ?> of <?php echo count($employees); ?> candidates</div>
      </div>

      <?php 
        if (count($employees) > 0) {
          foreach (array_slice($employees, 0, 6) as $emp) {
            // Generate match score based on skills count
            $match_score = min(98, 70 + (count($emp['skills']) * 4));
      ?>
      <!-- Candidate Card -->
      <div class="candidate-card">
        <div class="candidate-header">
          <div class="candidate-info">
            <h3><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></h3>
            <div class="candidate-role"><?php echo htmlspecialchars(count($emp['skills']) > 0 ? implode(' / ', array_slice($emp['skills'], 0, 2)) : 'Professional'); ?></div>
          </div>
          <div class="match-badge"><?php echo $match_score; ?>% Match</div>
        </div>
        <div class="candidate-meta">
          <span><?php echo htmlspecialchars($emp['address']); ?></span>
        </div>
        <div class="skills-list">
          <?php foreach (array_slice($emp['skills'], 0, 4) as $skill): ?>
            <span class="skill-tag"><?php echo htmlspecialchars($skill); ?></span>
          <?php endforeach; ?>
          <?php if (count($emp['skills']) > 4): ?>
            <span class="skill-tag">+<?php echo count($emp['skills']) - 4; ?> more</span>
          <?php endif; ?>
        </div>
        <div class="action-buttons">
          <button class="btn-small btn-primary-small" onclick="viewProfile(<?php echo $emp['employee_id']; ?>)">View Profile</button>
          <button class="btn-small btn-outline-small">Message</button>
        </div>
      </div>
      <?php } 
        } else { 
      ?>
      <div style="text-align: center; color: var(--text-light); padding: 2rem;">
        <p>No candidates available at this time.</p>
      </div>
      <?php } ?>
    </div>
  </div>
</div>

<!-- TAB 2: BLIND HIRING -->
<div id="blind-tab" class="tab-content">
  <!-- SEARCH BAR -->
  <div class="search-bar-wrap">
    <div class="search-bar">
      <div class="search-input-rel">
        <input type="text" class="input" placeholder="Search by skills or experience level...">
      </div>
      <button class="btn btn-primary">Search</button>
    </div>
  </div>

  <!-- CONTENT -->
  <div class="main-layout">
    <!-- SIDEBAR FILTERS -->
    <aside>
      <div class="sidebar-card">
        <div class="sidebar-title">Experience Level</div>
        <div class="filter-item">
          <div class="filter-left">
            <div class="fcheck">-</div>
            <span>Entry Level</span>
          </div>
          <span class="fcount">12</span>
        </div>
        <div class="filter-item">
          <div class="filter-left">
            <div class="fcheck on">✓</div>
            <span>Mid Level</span>
          </div>
          <span class="fcount">24</span>
        </div>
        <div class="filter-item">
          <div class="filter-left">
            <div class="fcheck">-</div>
            <span>Senior</span>
          </div>
          <span class="fcount">18</span>
        </div>
      </div>

      <div class="sidebar-card">
        <div class="sidebar-title">Skills</div>
        <div class="filter-item">
          <div class="filter-left">
            <div class="fcheck on">✓</div>
            <span>React</span>
          </div>
          <span class="fcount">16</span>
        </div>
        <div class="filter-item">
          <div class="filter-left">
            <div class="fcheck on">✓</div>
            <span>Python</span>
          </div>
          <span class="fcount">12</span>
        </div>
        <div class="filter-item">
          <div class="filter-left">
            <div class="fcheck">-</div>
            <span>JavaScript</span>
          </div>
          <span class="fcount">18</span>
        </div>
      </div>
    </aside>

    <!-- RESULTS -->
    <div>
      <div class="results-header">
        <div class="results-title">Anonymous Candidate Screening</div>
        <div class="results-count">Showing <?php echo count(array_slice($employees, 0, 6)); ?> of <?php echo count($employees); ?> candidates</div>
      </div>

      <?php 
        if (count($employees) > 0) {
          foreach (array_slice($employees, 0, 6) as $index => $emp) {
            // Generate match score based on skills count
            $match_score = min(98, 70 + (count($emp['skills']) * 4));
            $candidate_id = chr(65 + ($index % 26)) . rand(10, 99); // Generate ID like A42, B71, etc
      ?>
      <!-- Blind Candidate Card -->
      <div class="candidate-card">
        <div class="candidate-header">
          <div class="candidate-info">
            <h3>Candidate #<?php echo $candidate_id; ?></h3>
            <div class="candidate-role"><?php echo htmlspecialchars(count($emp['skills']) > 0 ? implode(' / ', array_slice($emp['skills'], 0, 2)) : 'Professional'); ?> Role</div>
          </div>
          <div class="match-badge"><?php echo $match_score; ?>% Match</div>
        </div>
        <div class="candidate-meta">
          <span><?php echo count($emp['skills']); ?> competencies</span>
        </div>
        <div class="skills-list">
          <?php foreach (array_slice($emp['skills'], 0, 4) as $skill): ?>
            <span class="skill-tag"><?php echo htmlspecialchars($skill); ?></span>
          <?php endforeach; ?>
          <?php if (count($emp['skills']) > 4): ?>
            <span class="skill-tag">+<?php echo count($emp['skills']) - 4; ?> more</span>
          <?php endif; ?>
        </div>
        <div class="action-buttons">
          <button class="btn-small btn-primary-small" onclick="viewProfile(<?php echo $emp['employee_id']; ?>)">View Anonymous Profile</button>
          <button class="btn-small btn-outline-small">Schedule Interview</button>
        </div>
      </div>
      <?php } 
        } else { 
      ?>
      <div style="text-align: center; color: var(--text-light); padding: 2rem;">
        <p>No candidates available for blind hiring screening at this time.</p>
      </div>
      <?php } ?>
    </div>
  </div>
</div>
  </div>
</div>

<script>
  function switchTab(tabName) {
    // Hide all tabs
    document.getElementById('normal-tab').classList.remove('active');
    document.getElementById('blind-tab').classList.remove('active');
    
    // Remove active class from all links
    document.querySelectorAll('.sub-nav-link').forEach(link => {
      link.classList.remove('active');
    });
    
    // Show selected tab with smooth animation
    if (tabName === 'normal') {
      document.getElementById('normal-tab').classList.add('active');
      document.querySelectorAll('.sub-nav-link')[0].classList.add('active');
      window.scrollTo({ top: 0, behavior: 'smooth' });
    } else if (tabName === 'blind') {
      document.getElementById('blind-tab').classList.add('active');
      document.querySelectorAll('.sub-nav-link')[1].classList.add('active');
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  }

  // Enhance candidate card interactions
  document.querySelectorAll('.candidate-card').forEach((card, index) => {
    card.style.animationDelay = (index * 0.1) + 's';
    card.addEventListener('mouseenter', function() {
      this.style.boxShadow = '0 8px 24px rgba(30, 158, 134, 0.16)';
    });
    card.addEventListener('mouseleave', function() {
      this.style.boxShadow = '';
    });
  });

  // Add smooth transitions to filter checkboxes
  document.querySelectorAll('.fcheck').forEach(checkbox => {
    checkbox.addEventListener('click', function(e) {
      e.preventDefault();
      this.classList.toggle('on');
      this.style.transform = 'scale(1.15)';
      setTimeout(() => {
        this.style.transform = 'scale(1)';
      }, 150);
    });
  });

  // Enhance button interactions
  document.querySelectorAll('.btn-small').forEach(btn => {
    btn.addEventListener('mouseenter', function() {
      this.style.transform = 'translateY(-2px)';
      this.style.boxShadow = '0 4px 12px rgba(30, 158, 134, 0.15)';
    });
    btn.addEventListener('mouseleave', function() {
      this.style.transform = 'translateY(0)';
      this.style.boxShadow = '';
    });
  });

  // Add observer for lazy animation of cards
  const observer = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.style.opacity = '1';
        entry.target.style.transform = 'translateY(0)';
      }
    });
  }, { threshold: 0.1 });

  document.querySelectorAll('.candidate-card').forEach(card => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(10px)';
    observer.observe(card);
  });

  // Modal Functions
  function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.add('active');
      document.body.style.overflow = 'hidden';
    }
  }

  function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.remove('active');
      document.body.style.overflow = 'auto';
    }
  }

  // View Profile Handler
  function viewProfile(employeeId) {
    // In a real app, this would fetch candidate data from backend
    // For now, we'll open the modal with the employee ID
    openModal('profileModal');
    // You could send AJAX request here to fetch candidate details
  }

  // Modal Event Listeners
  const modals = document.querySelectorAll('.modal');
  modals.forEach(modal => {
    // Close on close button
    const closeBtn = modal.querySelector('.modal-close');
    if (closeBtn) {
      closeBtn.addEventListener('click', () => {
        closeModal(modal.id);
      });
    }

    // Close on ESC key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && modal.classList.contains('active')) {
        closeModal(modal.id);
      }
    });

    // Close on modal overlay click
    modal.addEventListener('click', (e) => {
      if (e.target === modal) {
        closeModal(modal.id);
      }
    });
  });
</script>

<!-- Candidate Profile Modal -->
<div id="profileModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Candidate Profile</h2>
      <button class="modal-close">×</button>
    </div>
    <div class="modal-body">
      <div style="margin-bottom: 1.5rem;">
        <h3 style="font-size: 1.2rem; color: var(--text-dark); margin-bottom: 0.5rem;">Experience Summary</h3>
        <p style="color: var(--text-light); line-height: 1.6;">This candidate brings valuable skills and experience to your team. Strong track record in problem-solving and collaboration.</p>
      </div>

      <div style="margin-bottom: 1.5rem;">
        <h3 style="font-size: 1.2rem; color: var(--text-dark); margin-bottom: 0.75rem;">Key Skills</h3>
        <div class="skills-list">
          <span class="skill-tag">React</span>
          <span class="skill-tag">JavaScript</span>
          <span class="skill-tag">Node.js</span>
          <span class="skill-tag">Python</span>
          <span class="skill-tag">AWS</span>
          <span class="skill-tag">Docker</span>
        </div>
      </div>

      <div style="margin-bottom: 1.5rem;">
        <h3 style="font-size: 1.2rem; color: var(--text-dark); margin-bottom: 0.75rem;">Experience & Education</h3>
        <div style="padding-left: 1rem; border-left: 2px solid var(--primary-light);">
          <p style="margin: 0.5rem 0; font-weight: 600; color: var(--text-dark);">Mid-level Full Stack Developer</p>
          <p style="margin: 0 0 0.5rem 0; font-size: 0.9rem; color: var(--text-light);">3+ years of professional development experience</p>
          <p style="margin: 0.5rem 0; font-weight: 600; color: var(--text-dark);">Bachelor's Degree in Computer Science</p>
          <p style="margin: 0; font-size: 0.9rem; color: var(--text-light);">University of Technology</p>
        </div>
      </div>

      <div style="margin-bottom: 1.5rem;">
        <h3 style="font-size: 1.2rem; color: var(--text-dark); margin-bottom: 0.75rem;">Match Details</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
          <div style="background: var(--bg-light); padding: 1rem; border-radius: var(--radius);">
            <p style="margin: 0 0 0.25rem 0; font-size: 0.85rem; color: var(--text-light);">Overall Match</p>
            <p style="margin: 0; font-size: 1.5rem; font-weight: 700; color: var(--primary-dark);">85%</p>
          </div>
          <div style="background: var(--bg-light); padding: 1rem; border-radius: var(--radius);">
            <p style="margin: 0 0 0.25rem 0; font-size: 0.85rem; color: var(--text-light);">Skill Alignment</p>
            <p style="margin: 0; font-size: 1.5rem; font-weight: 700; color: var(--primary-dark);">92%</p>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-small btn-outline-small" onclick="closeModal('profileModal')">Close</button>
      <button class="btn-small btn-primary-small">Message Candidate</button>
      <button class="btn-small btn-primary-small">Schedule Interview</button>
    </div>
  </div>
</div>

<!-- FOOTER -->
<footer class="footer">
  <div class="footer-inner">
    <div class="footer-top">
      <div class="footer-brand">
        <h3>TalentScout AI</h3>
        <p>Smart AI-powered recruitment platform for PESO Nasugbu, Batangas. Connecting employers with qualified local talent.</p>
      </div>
      <div class="footer-col">
        <h4>For Job Seekers</h4>
        <ul>
          <li><a href="../../employees/">Browse Jobs</a></li>
          <li><a href="../../employees/modules/ai-matching/">AI Matching</a></li>
          <li><a href="../../employees/modules/skill-gap-analysis/">Skill Gap Analysis</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>For Employers</h4>
        <ul>
          <li><a href="../../index.php">Home</a></li>
          <li><a href="../post-jobs/">Post Jobs</a></li>
          <li><a href="./">Find Talent</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>PESO Nasugbu</h4>
        <ul>
          <li><a href="#">Contact Us</a></li>
          <li><a href="#">Privacy Policy</a></li>
          <li><a href="#">Terms of Service</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© 2026 TalentScout AI – PESO Nasugbu, Batangas</span>
      <span>Connecting Employers with Local Talent</span>
    </div>
  </div>
</footer>

</body>
</html>
