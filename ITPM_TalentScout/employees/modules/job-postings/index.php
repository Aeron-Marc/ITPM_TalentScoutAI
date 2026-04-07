<?php
// Database Connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "itpm_talentscoutai";

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");

// Fetch ALL job postings from database
$sql = "SELECT jp.* FROM job_post jp ORDER BY jp.job_post_id DESC";
$result = $conn->query($sql);
$jobs = array();
$dbError = '';

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $jobs[] = $row;
    }
} else if (!$result) {
    $dbError = "Query error: " . $conn->error;
}

// Encode jobs as JSON for JavaScript
$jobsJson = json_encode($jobs);
$hasError = !empty($dbError);
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Job Postings — TalentScout AI</title>
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
      .search-input-rel {
        position: relative;
        flex: 2;
      }
      .search-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-light);
        font-size: 0.9rem;
      }
      .search-input-rel .input {
        padding-left: 2.5rem;
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
        padding: 0.75rem;
        margin-bottom: 0.75rem;
      }
      .sidebar-title {
        font-weight: 700;
        font-size: 0.88rem;
        color: var(--text-dark);
        margin-bottom: 0.6rem;
        padding-bottom: 0.4rem;
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
        padding: 0.25rem 0;
        cursor: pointer;
        user-select: none;
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

      /* Salary range */
      .salary-range-container {
        margin: 0.75rem 0;
      }
      .salary-inputs {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
        font-size: 0.8rem;
      }
      .salary-inputs input {
        flex: 1;
        padding: 0.4rem;
        border: 1px solid var(--border);
        border-radius: 4px;
        font-size: 0.8rem;
      }
      .range-bar {
        position: relative;
        height: 5px;
        background: var(--border);
        border-radius: 5px;
        margin: 0.75rem 0;
      }
      .range-track {
        position: absolute;
        height: 100%;
        background: var(--primary-dark);
        border-radius: 5px;
      }
      .range-input {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 100%;
        height: 5px;
        pointer-events: none;
        -webkit-appearance: none;
        appearance: none;
        background: transparent;
      }
      .range-input::-webkit-slider-thumb {
        pointer-events: auto;
        -webkit-appearance: none;
        appearance: none;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: var(--primary-dark);
        cursor: pointer;
        border: 2px solid white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
      }
      .range-input::-moz-range-thumb {
        pointer-events: auto;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: var(--primary-dark);
        cursor: pointer;
        border: 2px solid white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
      }
      .range-labels {
        display: flex;
        justify-content: space-between;
        font-size: 0.75rem;
        color: var(--text-light);
      }

      /* Jobs column */
      .jobs-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.25rem;
      }
      .jobs-count {
        font-size: 0.9rem;
        color: var(--text-light);
      }
      .jobs-count strong {
        color: var(--text-dark);
        font-weight: 700;
      }

      .job-card {
        background: white;
        border: 1.5px solid var(--border);
        border-radius: var(--radius);
        padding: 1.5rem;
        margin-bottom: 1rem;
        transition: all 0.2s;
        cursor: pointer;
        animation: fadeInSlide 0.4s ease-out;
      }
      .job-card:hover {
        border-color: var(--primary-dark);
        box-shadow: var(--shadow);
      }
      .job-top {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 0.75rem;
      }
      .job-logo {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1rem;
        flex-shrink: 0;
      }
      .job-info {
        flex: 1;
      }
      .job-title {
        font-weight: 700;
        font-size: 1.05rem;
        color: var(--text-dark);
        margin-bottom: 0.2rem;
      }
      .job-company {
        font-size: 0.87rem;
        color: var(--text-mid);
      }
      .job-meta {
        display: flex;
        gap: 1.2rem;
        flex-wrap: wrap;
        font-size: 0.83rem;
        color: var(--text-light);
        margin: 0.6rem 0;
      }
      .job-meta span {
        display: flex;
        align-items: center;
        gap: 0.3rem;
      }
      .job-skills {
        display: flex;
        gap: 0.4rem;
        flex-wrap: wrap;
      }
      .chip {
        background: var(--bg-light);
        color: var(--text-mid);
        padding: 0.3rem 0.6rem;
        border-radius: 4px;
        font-size: 0.8rem;
        display: inline-block;
      }
      .badge {
        display: inline-block;
        padding: 0.4rem 0.8rem;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
      }
      .badge-blue {
        background: #e3f2fd;
        color: #1565c0;
      }
      .badge-gray {
        background: #f5f5f5;
        color: #616161;
      }
      .badge-purple {
        background: #f3e5f5;
        color: #6a1b9a;
      }
      .job-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 1rem;
        padding-top: 0.75rem;
        border-top: 1px solid var(--border);
      }
      .job-salary {
        font-weight: 700;
        font-size: 0.95rem;
        color: var(--primary-darker);
      }
      .job-date {
        font-size: 0.8rem;
        color: var(--text-light);
      }
      .job-footer-actions {
        display: flex;
        gap: 0.5rem;
      }

      /* Loading state */
      .loading-state {
        text-align: center;
        padding: 3rem 2rem;
        color: var(--text-light);
      }
      .loading-spinner {
        width: 40px;
        height: 40px;
        border: 3px solid var(--border);
        border-top-color: var(--primary-dark);
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        margin: 0 auto 1rem;
      }
      @keyframes spin { to { transform: rotate(360deg); } }
      @keyframes fadeInSlide {
        from {
          opacity: 0;
          transform: translateY(10px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }

      /* Pagination */
      .pagination {
        display: flex;
        justify-content: center;
        gap: 0.4rem;
        margin-top: 3rem;
        padding-top: 2rem;
        border-top: 1px solid var(--border);
      }
      .pg-btn {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--radius-sm);
        font-size: 0.87rem;
        font-weight: 500;
        border: 1.5px solid var(--border);
        background: white;
        color: var(--text-mid);
        cursor: pointer;
      }
      .pg-btn.active {
        background: var(--primary-dark);
        border-color: var(--primary-dark);
        color: white;
      }
      
      .no-results {
        text-align: center;
        padding: 3rem 2rem;
        color: var(--text-light);
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
        <li><a href="./index.php" class="active">Browse Jobs</a></li>
        <li><a href="../ai-matching/index.php">AI Matching</a></li>
        <li><a href="../resume-builder/index.php">Resume Builder</a></li>
        <li><a href="../skill-gap-analysis/index.php">Skills</a></li>
        <li><a href="../applicant-tracking/index.php">Applications</a></li>
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

    <!-- PAGE HEADER -->
    <div class="page-header">
      <div class="page-header-inner">
        <div class="breadcrumb">
          <a href="../../index.php">Home</a> / Job Postings
        </div>
        <h1>📋 Job Postings</h1>
        <p>
          Explore available jobs across all barangays in Nasugbu, Batangas.
          Centralized, digitized, and updated daily.
        </p>
      </div>
    </div>

    <!-- SEARCH BAR -->
    <div class="search-bar-wrap">
      <div class="search-bar">
        <div class="search-input-rel">
          <span class="search-icon">🔍</span>
          <input
            id="searchInput"
            type="text"
            class="input"
            placeholder="Search job title, skills, or company..."
          />
        </div>
        <select id="barangayFilter" class="input select" style="width: 200px">
          <option disabled selected>All Barangays</option>
          <option>Barangay Kaylaway</option>
          <option>Barangay Natipuan</option>
          <option>Barangay Bucana</option>
          <option>Barangay Maugat</option>
          <option>Barangay Reparo</option>
          <option>Barangay Calayo</option>
          <option>Barangay Pantalan</option>
          <option>Barangay Wawa</option>
          <option>Barangay Bolo</option>
          <option>Barangay Lumbangan</option>
          <option>Barangay Aga</option>
          <option>Barangay Bilaran</option>
          <option>Barangay Utod</option>
        </select>
        <select id="jobTypeFilter" class="input select" style="width: 160px">
          <option disabled selected>All Job Types</option>
          <option>Full-time</option>
          <option>Part-time</option>
          <option>Remote</option>
          <option>Hybrid</option>
          <option>Contract</option>
        </select>
        <select id="categoryFilter" class="input select" style="width: 210px">
          <option disabled selected>All Job Category</option>
          <option>Technology</option>
          <option>Healthcare</option>
          <option>Finance &amp; Accounting</option>
          <option>Education</option>
          <option>Construction</option>
          <option>Hospitality</option>
          <option>Customer Service</option>
          <option>Agriculture</option>
        </select>
        <button
          id="searchBtn"
          class="btn btn-primary"
          style="white-space: nowrap; padding: 0.65rem 1.5rem"
        >Search Jobs</button>
        <button
          id="resetBtn"
          class="btn btn-secondary"
          style="white-space: nowrap; padding: 0.65rem 1.5rem; background: #e0e0e0; color: #333; border-color: #d0d0d0; margin-left: 0.75rem"
        >Reset Filters</button>
      </div>
    </div>

    <!-- MAIN LAYOUT -->
    <div class="main-layout">
      <!-- SIDEBAR FILTERS -->
      <aside>
        <div class="sidebar-card">
          <div class="sidebar-title">Work Setup</div>
          <div class="filter-item" data-group="workSetup" data-value="On-site">
            <div class="filter-left">
              <div class="fcheck"></div>
              <span>On-site</span>
            </div>
            <span class="fcount">—</span>
          </div>
          <div class="filter-item" data-group="workSetup" data-value="Remote">
            <div class="filter-left">
              <div class="fcheck"></div>
              <span>Remote</span>
            </div>
            <span class="fcount">—</span>
          </div>
          <div class="filter-item" data-group="workSetup" data-value="Hybrid">
            <div class="filter-left">
              <div class="fcheck"></div>
              <span>Hybrid</span>
            </div>
            <span class="fcount">—</span>
          </div>
        </div>

        <div class="sidebar-card">
          <div class="sidebar-title">Salary Range</div>
          <div class="salary-range-container">
            <div class="salary-inputs">
              <input type="number" id="minSalary" placeholder="Min" value="0" min="0" max="60000" />
              <input type="number" id="maxSalary" placeholder="Max" value="60000" min="0" max="60000" />
            </div>
            <div class="range-bar">
              <div class="range-track" id="rangeTrack"></div>
              <input type="range" id="minRange" class="range-input" min="0" max="60000" value="0" />
              <input type="range" id="maxRange" class="range-input" min="0" max="60000" value="60000" />
            </div>
            <div class="range-labels">
              <span>₱<span id="minLabel">0</span></span>
              <span>₱<span id="maxLabel">60,000</span></span>
            </div>
          </div>
        </div>

        <div class="sidebar-card">
          <div class="sidebar-title">Experience Level</div>
          <div class="filter-item" data-group="experienceLevel" data-value="Entry Level">
            <div class="filter-left">
              <div class="fcheck"></div>
              <span>Entry Level</span>
            </div>
            <span class="fcount">—</span>
          </div>
          <div class="filter-item" data-group="experienceLevel" data-value="Mid Level">
            <div class="filter-left">
              <div class="fcheck"></div>
              <span>Mid Level</span>
            </div>
            <span class="fcount">—</span>
          </div>
          <div class="filter-item" data-group="experienceLevel" data-value="Senior Level">
            <div class="filter-left">
              <div class="fcheck"></div>
              <span>Senior Level</span>
            </div>
            <span class="fcount">—</span>
          </div>
        </div>

        <div class="sidebar-card">
          <div class="sidebar-title">Posted Within</div>
          <div class="filter-item" data-group="postedWithin" data-value="Last 7 days">
            <div class="filter-left">
              <div class="fcheck"></div>
              <span>Last 7 days</span>
            </div>
            <span class="fcount">—</span>
          </div>
          <div class="filter-item" data-group="postedWithin" data-value="Last 30 days">
            <div class="filter-left">
              <div class="fcheck"></div>
              <span>Last 30 days</span>
            </div>
            <span class="fcount">—</span>
          </div>
          <div class="filter-item" data-group="postedWithin" data-value="All time">
            <div class="filter-left">
              <div class="fcheck"></div>
              <span>All time</span>
            </div>
            <span class="fcount">—</span>
          </div>
        </div>
      </aside>

      <!-- JOB LISTINGS -->
      <main id="jobsMain">
        <div class="jobs-header">
          <div class="jobs-count" id="jobsCount">
            Loading jobs...
          </div>
          <select id="sortFilter" class="input select" style="width: 200px">
            <option>Sort: Most Recent</option>
            <option>Sort: Salary (High–Low)</option>
            <option>Sort: Salary (Low–High)</option>
          </select>
        </div>

        <!-- Loading spinner shown on initial load -->
        <div class="loading-state" id="loadingState">
          <div class="loading-spinner"></div>
          <p>Loading job postings...</p>
        </div>
      </main>
    </div>

    <!-- Data passed from PHP to JavaScript -->
    <script>
      window.dbJobs = <?php echo $jobsJson; ?>;
      window.dbError = <?php echo json_encode($dbError); ?>;
    </script>

    <script src="../../employee-auth.js"></script>
    <script>
      // ─── All jobs loaded from DB ───────────────────────────────────────────
      let allJobs = [];
      let loadErrorMessage = '';
      let salaryMaxValue = 60000;
      let currentPage = 1;

      // ─── Filter state ──────────────────────────────────────────────────────
      const filterState = {
        searchQuery: '',
        barangay: 'All Barangays',
        jobType: 'All Job Types',
        category: 'All Job Category',
        workSetup: [],
        salaryRange: { min: 0, max: 60000 },
        experienceLevel: [],
        postedWithin: [],
        sortBy: 'Sort: Most Recent'
      };

      // ─── Helpers ───────────────────────────────────────────────────────────
      function splitSkills(val) {
        if (!val) return [];
        return String(val).split(',').map(s => s.trim()).filter(Boolean);
      }

      function parseSalaryRange(val) {
        const text = String(val || '').trim();
        const nums = (text.match(/\d[\d,]*/g) || [])
          .map(n => Number(n.replace(/,/g, '')))
          .filter(Number.isFinite);
        if (!nums.length) return { min: 0, max: 0, text: text || 'Not specified' };
        const min = nums[0];
        const max = nums.length > 1 ? nums[nums.length - 1] : nums[0];
        return {
          min,
          max,
          text: min === max
            ? `₱${min.toLocaleString()} / mo`
            : `₱${min.toLocaleString()} – ₱${max.toLocaleString()} / mo`
        };
      }

      function formatDeadline(val) {
        if (!val) return 'Not set';
        const d = new Date(val);
        if (isNaN(d.getTime())) return String(val);
        return d.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
      }

      function formatPostedDate(val) {
        if (!val) return 'Posted recently';
        const d = new Date(val);
        if (isNaN(d.getTime())) return 'Posted recently';
        
        const now = new Date();
        const diffMs = now.getTime() - d.getTime();
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMins / 60);
        const diffDays = Math.floor(diffHours / 24);
        const diffWeeks = Math.floor(diffDays / 7);
        const diffMonths = Math.floor(diffDays / 30);
        
        if (diffMins < 1) return 'Posted just now';
        if (diffMins < 60) return `Posted ${diffMins} minute${diffMins !== 1 ? 's' : ''} ago`;
        if (diffHours < 24) return `Posted ${diffHours} hour${diffHours !== 1 ? 's' : ''} ago`;
        if (diffDays < 7) return `Posted ${diffDays} day${diffDays !== 1 ? 's' : ''} ago`;
        if (diffWeeks < 4) return `Posted ${diffWeeks} week${diffWeeks !== 1 ? 's' : ''} ago`;
        return `Posted ${diffMonths} month${diffMonths !== 1 ? 's' : ''} ago`;
      }

      function normalizeWorkType(val) {
        const v = String(val || '').trim().toLowerCase();
        if (v.includes('remote'))   return 'Remote';
        if (v.includes('hybrid'))   return 'Hybrid';
        if (v.includes('contract')) return 'Contract';
        if (v.includes('part'))     return 'Part-time';
        return 'Full-time';
      }

      function inferCategory(title, desc, skills) {
        const t = `${title} ${desc} ${skills.join(' ')}`.toLowerCase();
        if (/nurse|doctor|medical|clinic|hospital|health/.test(t))        return 'Healthcare';
        if (/account|finance|bookkeep|payroll|audit|tax/.test(t))         return 'Finance & Accounting';
        if (/teacher|tutor|school|education|curriculum/.test(t))          return 'Education';
        if (/construction|electrician|plumber|mason|foreman|site/.test(t))return 'Construction';
        if (/chef|cook|kitchen|hotel|restaurant|hospitality|barista/.test(t)) return 'Hospitality';
        if (/customer service|call center|support|csr|helpdesk/.test(t))  return 'Customer Service';
        if (/farm|agri|agriculture|crop|livestock|harvest/.test(t))        return 'Agriculture';
        return 'Technology';
      }

      function inferLevel(title, desc) {
        const t = `${title} ${desc}`.toLowerCase();
        if (/senior|lead|principal|manager|head/.test(t)) return 'Senior Level';
        if (/mid|intermediate/.test(t))                   return 'Mid Level';
        return 'Entry Level';
      }

      function normalizeLevel(val, title, desc) {
        const v = String(val || '').trim().toLowerCase();
        if (v.includes('senior'))                                       return 'Senior Level';
        if (v.includes('mid') || v.includes('intermediate'))           return 'Mid Level';
        if (v.includes('entry') || v.includes('junior') || v.includes('fresh')) return 'Entry Level';
        if (!v) return inferLevel(title, desc);
        return String(val).trim();
      }

      function normalizeRow(row) {
        const salary   = parseSalaryRange(row.salary);
        const workType = normalizeWorkType(row.work_type);
        const skills   = splitSkills(row.skills);
        const title    = String(row.title || '').trim();
        const desc     = String(row.description || '').trim();
        const setup    = workType === 'Remote' ? 'Remote' : workType === 'Hybrid' ? 'Hybrid' : 'On-site';

        // Compute postedDays from application_deadline (fallback: 0)
        let postedDays = 0;
        if (row.created_at) {
          const created = new Date(row.created_at);
          if (!isNaN(created.getTime())) {
            postedDays = Math.floor((Date.now() - created.getTime()) / 86400000);
          }
        }

        return {
          id: Number(row.job_post_id) || 0,
          title,
          description: desc,
          location: String(row.location || '').trim() || 'Not specified',
          type: workType,
          setup,
          salary,
          salaryText: salary.text,
          applicationDeadline: row.application_deadline || '',
          skills,
          level: normalizeLevel(row.experience_level, title, desc),
          category: inferCategory(title, desc, skills),
          postedDays,
          created_at: row.created_at || ''
        };
      }

      // ─── Load from DB ──────────────────────────────────────────────────────
      function loadJobsFromDatabase() {
        try {
          // Use data passed from PHP
          const dbJobs = window.dbJobs || [];
          const hasError = window.dbError || false;
          
          if (hasError) {
            throw new Error(window.dbError);
          }
          
          if (!Array.isArray(dbJobs)) {
            throw new Error('Invalid jobs data format');
          }

          allJobs = dbJobs.map(normalizeRow);
          console.log(`📦 Loaded ${allJobs.length} jobs from database`);
          loadErrorMessage = '';

          // Keep salary filters wide enough for actual DB data
          const highest = allJobs.reduce((mx, job) => Math.max(mx, Number(job.salary.max || 0)), 0);
          salaryMaxValue = Math.max(60000, highest);
          applySalaryBounds();
        } catch (err) {
          allJobs = [];
          loadErrorMessage = (err && err.message) ? err.message : 'Failed to load jobs from database.';
          console.error('❌ Error loading jobs:', err);
        }
        updateSidebarCounts();
        applyFilters();
      }

      function applySalaryBounds() {
        const minRange  = document.getElementById('minRange');
        const maxRange  = document.getElementById('maxRange');
        const minInput  = document.getElementById('minSalary');
        const maxInput  = document.getElementById('maxSalary');
        const minLabel  = document.getElementById('minLabel');
        const maxLabel  = document.getElementById('maxLabel');
        const track     = document.getElementById('rangeTrack');

        if (!minRange || !maxRange || !minInput || !maxInput || !minLabel || !maxLabel || !track) return;

        const maxStr = String(salaryMaxValue);
        minRange.max = maxStr;
        maxRange.max = maxStr;
        minInput.max = maxStr;
        maxInput.max = maxStr;

        minRange.value = '0';
        maxRange.value = maxStr;
        minInput.value = '0';
        maxInput.value = maxStr;

        minLabel.textContent = '0';
        maxLabel.textContent = salaryMaxValue.toLocaleString();
        track.style.left = '0%';
        track.style.right = '0%';

        filterState.salaryRange.min = 0;
        filterState.salaryRange.max = salaryMaxValue;
      }

      // ─── Sidebar counts (updated based on current dropdown filters) ──────
      function updateSidebarCounts() {
        // First, apply dropdown filters to get filtered jobs
        let filtered = allJobs.filter(job => {
          // Barangay filter
          if (filterState.barangay !== 'All Barangays' && job.location !== filterState.barangay) return false;

          // Job Type filter
          if (filterState.jobType !== 'All Job Types' && job.type !== filterState.jobType) return false;

          // Category filter
          if (filterState.category !== 'All Job Category' && job.category !== filterState.category) return false;

          // Salary Range filter
          if (job.salary.max < filterState.salaryRange.min || job.salary.min > filterState.salaryRange.max) return false;

          return true;
        });

        // Now count checkbox options based on filtered results
        const counts = {
          'On-site':      filtered.filter(j => j.setup === 'On-site').length,
          'Remote':       filtered.filter(j => j.setup === 'Remote').length,
          'Hybrid':       filtered.filter(j => j.setup === 'Hybrid').length,
          'Entry Level':  filtered.filter(j => j.level === 'Entry Level').length,
          'Mid Level':    filtered.filter(j => j.level === 'Mid Level').length,
          'Senior Level': filtered.filter(j => j.level === 'Senior Level').length,
          'Last 7 days':  filtered.filter(j => j.postedDays <= 7).length,
          'Last 30 days': filtered.filter(j => j.postedDays <= 30).length,
          'All time':     filtered.length,
        };

        document.querySelectorAll('.filter-item').forEach(item => {
          const val   = item.dataset.value;
          const fcount = item.querySelector('.fcount');
          if (fcount && val && counts[val] !== undefined) {
            fcount.textContent = counts[val];
          }
        });
      }

      // ─── Apply all filters & render ───────────────────────────────────────
      function applyFilters() {
        let filtered = allJobs.filter(job => {
          // Search
          if (filterState.searchQuery) {
            const q = filterState.searchQuery;
            const inTitle = job.title.toLowerCase().includes(q);
            const inDesc  = job.description.toLowerCase().includes(q);
            const inSkill = job.skills.some(s => s.toLowerCase().includes(q));
            if (!inTitle && !inDesc && !inSkill) return false;
          }

          // Barangay (loose match: location contains selected barangay name)
          if (filterState.barangay !== 'All Barangays') {
            const barName = filterState.barangay.replace('Barangay ', '').toLowerCase();
            if (!job.location.toLowerCase().includes(barName)) return false;
          }

          // Job Type
          if (filterState.jobType !== 'All Job Types' && job.type !== filterState.jobType) return false;

          // Category
          if (filterState.category !== 'All Job Category' && job.category !== filterState.category) return false;

          // Work Setup
          if (filterState.workSetup.length && !filterState.workSetup.includes(job.setup)) return false;

          // Salary Range — include job if ranges overlap
          if (job.salary.max < filterState.salaryRange.min || job.salary.min > filterState.salaryRange.max) return false;

          // Experience Level
          if (filterState.experienceLevel.length && !filterState.experienceLevel.includes(job.level)) return false;

          // Posted Within
          if (filterState.postedWithin.length) {
            const ok = filterState.postedWithin.some(period => {
              if (period === 'Last 7 days')  return job.postedDays <= 7;
              if (period === 'Last 30 days') return job.postedDays <= 30;
              if (period === 'All time')     return true;
              return false;
            });
            if (!ok) return false;
          }

          return true;
        });

        // Sort
        if (filterState.sortBy === 'Sort: Salary (High–Low)') {
          filtered.sort((a, b) => b.salary.max - a.salary.max);
        } else if (filterState.sortBy === 'Sort: Salary (Low–High)') {
          filtered.sort((a, b) => a.salary.min - b.salary.min);
        } else {
          // Most Recent - sort by postedDays (smaller = more recent)
          filtered.sort((a, b) => a.postedDays - b.postedDays);
        }

        document.getElementById('jobsCount').innerHTML =
          `Showing <strong>${filtered.length} jobs</strong> in Nasugbu, Batangas`;

        displayJobs(filtered);
      }

      // ─── Render job cards ──────────────────────────────────────────────────
      function displayJobs(jobs) {
        const main = document.getElementById('jobsMain');

        // Remove loading spinner if still visible
        const loading = document.getElementById('loadingState');
        if (loading) loading.remove();

        // Fade out old cards smoothly
        const oldCards = main.querySelectorAll('.job-card');
        oldCards.forEach(card => {
          card.style.opacity = '0';
          card.style.transform = 'translateY(10px)';
          card.style.transition = 'all 0.3s ease-out';
          setTimeout(() => card.remove(), 300);
        });

        // Remove old pagination and no-results after fade
        setTimeout(() => {
          main.querySelectorAll('.pagination, .no-results').forEach(el => el.remove());

          if (!jobs.length) {
            const empty = document.createElement('div');
            empty.className = 'no-results';
            empty.style.cssText = 'text-align:center;padding:3rem 2rem;color:var(--text-light);';
            empty.innerHTML = `<p>${loadErrorMessage || 'No jobs found matching your filters. Try adjusting your search criteria.'}</p>`;
            main.appendChild(empty);
            return;
          }

          // Reset to page 1 when filters change
          currentPage = 1;
          
          // Show only 5 items per page
          const itemsPerPage = 5;
          const startIdx = (currentPage - 1) * itemsPerPage;
          const endIdx = startIdx + itemsPerPage;
          const paginatedJobs = jobs.slice(startIdx, endIdx);

          paginatedJobs.forEach(job => main.appendChild(createJobCard(job)));
          addPagination(jobs, currentPage);
        }, 300);
      }

      // ─── Build a single job card ───────────────────────────────────────────
      function createJobCard(job) {
        const bgColors   = ['#e8f5e9','#e3f2fd','#f3e5f5','#fff8e1','#ffe0b2','#fce4ec'];
        const textColors = ['#2e7d32','#1565c0','#6a1b9a','#e65100','#e65100','#c2185b'];
        const idx        = job.id % 6;

        const setupBadgeClass = job.setup === 'Remote'
          ? 'badge-blue' : job.setup === 'Hybrid'
          ? 'badge-purple' : 'badge-gray';

        const logoText = job.title
          .split(' ').filter(Boolean)
          .map(w => w[0]).join('')
          .substring(0, 2).toUpperCase() || 'JP';

        const deadline = formatDeadline(job.applicationDeadline);
        const createdDate = formatPostedDate(job.created_at);
        const skillsHtml = job.skills.length
          ? job.skills.map(s => `<span class="chip">${s}</span>`).join('')
          : '<span class="chip">No skills listed</span>';

        const card = document.createElement('div');
        card.className = 'job-card';
        card.innerHTML = `
          <div class="job-top">
            <div class="job-logo" style="background:${bgColors[idx]};color:${textColors[idx]}">
              ${logoText}
            </div>
            <div class="job-info">
              <div class="job-title">${job.title}</div>
              <div class="job-company">${job.description || 'No description provided.'}</div>
            </div>
            <span class="badge ${setupBadgeClass}">${job.setup}</span>
          </div>
          <div class="job-meta">
            <span>📍 ${job.location}</span>
            <span>💼 ${job.type}</span>
            <span>📊 ${job.level || 'Not specified'}</span>
            <span>🏢 ${job.category || 'Not specified'}</span>
            <span>📅 ${createdDate}</span>
          </div>
          <div class="job-skills">${skillsHtml}</div>
          <div class="job-footer">
            <div class="job-salary">${job.salaryText}</div>
            <div style="display:flex;align-items:center;gap:1rem">
              <span class="job-date">Application deadline: ${deadline}</span>
              <a href="../../login.php" class="btn btn-primary">Apply Now</a>
            </div>
          </div>
        `;
        return card;
      }

      // ─── Pagination ────────────────────────────────────────────────────────
      function addPagination(allFilteredJobs, activePage) {
        const main  = document.getElementById('jobsMain');
        const itemsPerPage = 5;
        const totalPages = Math.max(1, Math.ceil(allFilteredJobs.length / itemsPerPage));
        const pag   = document.createElement('div');
        pag.className = 'pagination';

        // Previous button
        const prevBtn = document.createElement('button');
        prevBtn.className = 'pg-btn';
        prevBtn.textContent = '‹';
        prevBtn.onclick = () => {
          if (activePage > 1) {
            currentPage = activePage - 1;
            renderPage(allFilteredJobs);
          }
        };
        pag.appendChild(prevBtn);

        // Page number buttons
        for (let i = 1; i <= Math.min(totalPages, 5); i++) {
          const btn = document.createElement('button');
          btn.className = 'pg-btn' + (i === activePage ? ' active' : '');
          btn.textContent = i;
          btn.onclick = () => {
            currentPage = i;
            renderPage(allFilteredJobs);
          };
          pag.appendChild(btn);
        }

        // Ellipsis and last page
        if (totalPages > 5) {
          const ellipsis = document.createElement('button');
          ellipsis.className = 'pg-btn';
          ellipsis.textContent = '…';
          ellipsis.style.pointerEvents = 'none';
          pag.appendChild(ellipsis);

          const last = document.createElement('button');
          last.className = 'pg-btn';
          last.textContent = totalPages;
          last.onclick = () => {
            currentPage = totalPages;
            renderPage(allFilteredJobs);
          };
          pag.appendChild(last);
        }

        // Next button
        const nextBtn = document.createElement('button');
        nextBtn.className = 'pg-btn';
        nextBtn.textContent = '›';
        nextBtn.onclick = () => {
          if (activePage < totalPages) {
            currentPage = activePage + 1;
            renderPage(allFilteredJobs);
          }
        };
        pag.appendChild(nextBtn);

        main.appendChild(pag);
      }

      // ─── Render a specific page ────────────────────────────────────────────
      function renderPage(allFilteredJobs) {
        const main = document.getElementById('jobsMain');
        const itemsPerPage = 5;
        const startIdx = (currentPage - 1) * itemsPerPage;
        const endIdx = startIdx + itemsPerPage;
        const paginatedJobs = allFilteredJobs.slice(startIdx, endIdx);

        // Fade out and remove old cards smoothly
        const oldCards = main.querySelectorAll('.job-card');
        oldCards.forEach(card => {
          card.style.opacity = '0';
          card.style.transform = 'translateY(10px)';
          card.style.transition = 'all 0.3s ease-out';
          setTimeout(() => card.remove(), 300);
        });

        // Add and fade in new cards after old ones fade out
        setTimeout(() => {
          // Remove pagination before adding new cards
          const oldPagination = main.querySelector('.pagination');
          if (oldPagination) oldPagination.remove();

          // Display the jobs for current page
          paginatedJobs.forEach(job => main.appendChild(createJobCard(job)));

          // Re-add pagination
          addPagination(allFilteredJobs, currentPage);
        }, 300);
      }

      // ─── Reset Filters Function ───────────────────────────────────────────────
      function resetFilters() {
        // Reset filter state
        filterState.searchQuery = '';
        filterState.barangay = 'All Barangays';
        filterState.jobType = 'All Job Types';
        filterState.category = 'All Job Category';
        filterState.workSetup = [];
        filterState.salaryRange = { min: 0, max: 60000 };
        filterState.experienceLevel = [];
        filterState.postedWithin = [];
        filterState.sortBy = 'Sort: Most Recent';

        // Reset search input
        document.getElementById('searchInput').value = '';

        // Reset dropdowns
        document.getElementById('barangayFilter').value = 'All Barangays';
        document.getElementById('jobTypeFilter').value = 'All Job Types';
        document.getElementById('categoryFilter').value = 'All Job Category';
        document.getElementById('sortFilter').value = 'Sort: Most Recent';

        // Reset checkboxes
        document.querySelectorAll('.filter-item').forEach(item => {
          item.classList.remove('active');
          item.querySelector('.fcheck').classList.remove('on');
        });

        // Reset salary range
        const minRange = document.getElementById('minRange');
        const maxRange = document.getElementById('maxRange');
        const minInput = document.getElementById('minSalary');
        const maxInput = document.getElementById('maxSalary');
        const minLabel = document.getElementById('minLabel');
        const maxLabel = document.getElementById('maxLabel');
        const track = document.getElementById('rangeTrack');

        minRange.value = 0;
        maxRange.value = 60000;
        minInput.value = 0;
        maxInput.value = 60000;
        minLabel.textContent = '0';
        maxLabel.textContent = '60,000';
        track.style.left = '0%';
        track.style.right = '0%';

        // Re-apply filters to show all jobs
        updateSidebarCounts();
        applyFilters();
      }

      // ─── Salary range slider logic ────────────────────────────────────────────
      function initSalaryRange() {
        const minRange  = document.getElementById('minRange');
        const maxRange  = document.getElementById('maxRange');
        const minInput  = document.getElementById('minSalary');
        const maxInput  = document.getElementById('maxSalary');
        const minLabel  = document.getElementById('minLabel');
        const maxLabel  = document.getElementById('maxLabel');
        const track     = document.getElementById('rangeTrack');

        function sync() {
          let minVal = Math.min(parseInt(minRange.value), parseInt(maxRange.value));
          let maxVal = Math.max(parseInt(minRange.value), parseInt(maxRange.value));

          minRange.value = minVal;
          maxRange.value = maxVal;
          minInput.value = minVal;
          maxInput.value = maxVal;
          minLabel.textContent = minVal.toLocaleString();
          maxLabel.textContent = maxVal.toLocaleString();

          track.style.left  = (minVal / salaryMaxValue * 100) + '%';
          track.style.right = (100 - maxVal / salaryMaxValue * 100) + '%';

          filterState.salaryRange.min = minVal;
          filterState.salaryRange.max = maxVal;
          updateSidebarCounts();
          applyFilters();
        }

        minRange.addEventListener('input', sync);
        maxRange.addEventListener('input', sync);
        minInput.addEventListener('change', () => { minRange.value = minInput.value; sync(); });
        maxInput.addEventListener('change', () => { maxRange.value = maxInput.value; sync(); });

        // Init track with current bounds
        applySalaryBounds();
      }

      // ─── Checkbox toggles ──────────────────────────────────────────────────
      function initCheckboxes() {
        document.querySelectorAll('.filter-item[data-group]').forEach(item => {
          item.addEventListener('click', () => {
            const group = item.dataset.group;
            const value = item.dataset.value;
            const box   = item.querySelector('.fcheck');

            box.classList.toggle('on');
            box.textContent = box.classList.contains('on') ? '✓' : '';

            if (box.classList.contains('on')) {
              if (!filterState[group].includes(value)) filterState[group].push(value);
            } else {
              filterState[group] = filterState[group].filter(v => v !== value);
            }

            applyFilters();
          });
        });
      }

      // ─── Top-bar dropdowns & search ───────────────────────────────────────
      function initTopBar() {
        document.getElementById('searchInput').addEventListener('input', e => {
          filterState.searchQuery = e.target.value.toLowerCase().trim();
          applyFilters();
        });

        document.getElementById('barangayFilter').addEventListener('change', e => {
          filterState.barangay = e.target.value;
          updateSidebarCounts();
          applyFilters();
        });

        document.getElementById('jobTypeFilter').addEventListener('change', e => {
          filterState.jobType = e.target.value;
          updateSidebarCounts();
          applyFilters();
        });

        document.getElementById('categoryFilter').addEventListener('change', e => {
          filterState.category = e.target.value;
          updateSidebarCounts();
          applyFilters();
        });

        document.getElementById('sortFilter').addEventListener('change', e => {
          filterState.sortBy = e.target.value;
          applyFilters();
        });

        document.getElementById('searchBtn').addEventListener('click', e => {
          e.preventDefault();
          filterState.searchQuery = document.getElementById('searchInput').value.toLowerCase().trim();
          applyFilters();
        });

        document.getElementById('resetBtn').addEventListener('click', e => {
          e.preventDefault();
          resetFilters();
        });
      }

      // ─── Bootstrap ────────────────────────────────────────────────────────
      document.addEventListener('DOMContentLoaded', () => {
        initSalaryRange();
        initCheckboxes();
        initTopBar();
        loadJobsFromDatabase(); // fetches DB → normalizes → calls applyFilters() → renders cards
      });
    </script>
  </body>
</html>
