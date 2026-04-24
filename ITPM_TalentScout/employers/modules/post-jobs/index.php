<?php
session_start();
require_once('../../../database/db.php');

// Get database connection
$conn = getConnection();
$employer_id = isset($_SESSION['employer_id']) ? $_SESSION['employer_id'] : 1; // Default to employer 1 for testing

// Handle form submission (POST new job)
$success_message = '';
$error_message = '';

if (isset($_GET['job_created']) && $_GET['job_created'] === '1') {
  $success_message = "Job posting created successfully!";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_job') {
  $title = trim($_POST['job_title'] ?? '');
  $category = trim($_POST['job_category'] ?? '');
  $description = trim($_POST['job_description'] ?? '');
  $salary = trim($_POST['salary_range'] ?? '');
  $location = trim($_POST['location'] ?? '');
  $location_lat = trim($_POST['location_lat'] ?? '');
  $location_lng = trim($_POST['location_lng'] ?? '');
  $work_type = trim($_POST['job_type'] ?? '');
  $skills = trim($_POST['required_skills'] ?? '');
  $deadline = trim($_POST['application_deadline'] ?? '');

  if ($title && $category && $description && $salary && $location && $work_type && $skills && $location_lat !== '' && $location_lng !== '') {
    $stmt = $conn->prepare("INSERT INTO job_post (employer_id, title, description, salary, location, work_type, application_deadline, skills, experience_level, job_category, job_post_created) VALUES (?, ?, ?, ?, ?, ?, ?, ?, '', ?, NOW())");
    $stmt->bind_param("issssssss", $employer_id, $title, $description, $salary, $location, $work_type, $deadline, $skills, $category);

    if ($stmt->execute()) {
      $stmt->close();
      header("Location: " . strtok($_SERVER['REQUEST_URI'], '?') . "?job_created=1");
      exit;
    } else {
      $error_message = "Error creating job posting: " . $conn->error;
    }
  } else {
    $error_message = ($location_lat === '' || $location_lng === '') ? "Please pin a location on the map before submitting." : "Please fill in all required fields.";
  }
}

// Fetch jobs for current employer
$jobs = [];
$stmt = $conn->prepare("SELECT job_post_id, title, salary, location, work_type, job_post_created FROM job_post WHERE employer_id = ? ORDER BY job_post_created DESC");
$stmt->bind_param("i", $employer_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $jobs[] = $row;
}
$stmt->close();

// Get application counts for each job
$job_counts = [];
$stmt = $conn->prepare("SELECT job_post_id, COUNT(*) as count FROM application WHERE job_post_id IN (SELECT job_post_id FROM job_post WHERE employer_id = ?) GROUP BY job_post_id");
$stmt->bind_param("i", $employer_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $job_counts[$row['job_post_id']] = $row['count'];
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Post Job Listings — TalentScout AI</title>
  <link rel="stylesheet" href="../../../styles/global.css">
  <link rel="stylesheet" href="../../../styles/page-layout.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
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
    .tab-content {
      flex: 1 0 auto;
    }

    /* Footer stays at the bottom */
    .footer {
      flex-shrink: 0;
    }

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
      max-width: 700px;
      max-height: 90vh;
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
      from {
        opacity: 0;
      }

      to {
        opacity: 1;
      }
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
      pointer-events: none;
      z-index: 1;
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

    /* Content Area */
    .content-col {
      display: flex;
      flex-direction: column;
      gap: 1.5rem;
    }

    .page-header {
      margin-bottom: 1.5rem;
    }

    .page-header h1 {
      font-size: 1.75rem;
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 0.5rem;
    }

    .page-header p {
      font-size: 0.95rem;
      color: var(--text-light);
    }

    .form-card {
      background: white;
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 1.75rem;
    }

    .form-title {
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 1.5rem;
    }

    .form-group {
      margin-bottom: 1.25rem;
    }

    .form-label {
      display: block;
      font-size: 0.87rem;
      font-weight: 600;
      color: var(--text-dark);
      margin-bottom: 0.5rem;
    }

    .input,
    .select,
    .textarea {
      width: 100%;
      padding: 0.65rem 0.875rem;
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      font-size: 0.9rem;
      font-family: inherit;
      color: var(--text-dark);
      background: white;
      transition: border-color 0.2s;
    }

    .input:focus,
    .select:focus,
    .textarea:focus {
      outline: none;
      border-color: var(--primary-dark);
      box-shadow: 0 0 0 3px rgba(152, 251, 203, 0.1);
    }

    .textarea {
      resize: vertical;
      min-height: 100px;
    }

    .location-map {
      width: 100%;
      height: 250px;
      margin-top: 0.75rem;
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      overflow: hidden;
      background: #eef5f2;
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
    }

    .form-row .form-group {
      margin-bottom: 0;
    }

    .submit-btn {
      background: var(--primary-dark);
      color: white;
      padding: 0.7rem 1.75rem;
      border: none;
      border-radius: var(--radius-sm);
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      transition: background-color 0.2s;
    }

    .submit-btn:hover {
      background: var(--primary-darker);
    }

    .jobs-list {
      background: white;
      border: 1px solid var(--border);
      border-radius: var(--radius);
      overflow: hidden;
    }

    .jobs-list-header {
      padding: 1.5rem 1.75rem;
      border-bottom: 1px solid var(--border);
      font-weight: 600;
      color: var(--text-dark);
      background: linear-gradient(135deg, #f8fffc 0%, #f0fffb 100%);
    }

    .job-card {
      padding: 1.5rem 1.75rem;
      border-bottom: 1px solid var(--border);
      display: grid;
      grid-template-columns: 1fr auto auto auto;
      gap: 1rem;
      align-items: center;
      transition: all 0.25s ease;
      background: white;
    }

    .job-card:hover {
      background: #f9fffd;
      box-shadow: inset 0 0 8px rgba(30, 158, 134, 0.05);
    }

    .job-card:last-child {
      border-bottom: none;
    }

    .job-title {
      font-size: 0.98rem;
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 0.3rem;
      transition: color 0.2s;
    }

    .job-card:hover .job-title {
      color: var(--primary-dark);
    }

    .job-meta {
      font-size: 0.82rem;
      color: var(--text-light);
      line-height: 1.4;
    }

    .job-card-actions {
      display: flex;
      gap: 0.5rem;
    }

    .job-card .btn-small {
      padding: 0.35rem 0.75rem;
      font-size: 0.8rem;
      border-radius: 3px;
      transition: all 0.2s;
    }

    .job-card .btn-small:hover {
      transform: translateY(-2px);
      box-shadow: 0 2px 6px rgba(30, 158, 134, 0.1);
    }

    /* Button Styles */
    .btn-modal-trigger {
      background: var(--primary-dark);
      color: white;
      padding: 0.7rem 1.75rem;
      border: none;
      border-radius: var(--radius-sm);
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }

    .btn-modal-trigger:hover {
      background: var(--primary-darker);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(30, 158, 134, 0.15);
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
      <li><a href="./" class="active">Post Jobs</a></li>
      <li><a href="../employee-finder/">Find Talent</a></li>
      <li><a href="../applicant-tracking/">Hiring Pipeline</a></li>
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

  <!-- TAB 1: MANAGE -->
  <div id="manage-tab" class="tab-content active">
    <div class="main-layout">
      <!-- SIDEBAR -->
      <aside>
        <div class="sidebar-card">
          <div class="sidebar-title">Status</div>
          <div class="filter-item">
            <div class="filter-left">
              <div class="fcheck on">✓</div>
              <span>Active</span>
            </div>
            <span class="fcount">8</span>
          </div>
          <div class="filter-item">
            <div class="filter-left">
              <div class="fcheck">-</div>
              <span>Closed</span>
            </div>
            <span class="fcount">2</span>
          </div>
          <div class="filter-item">
            <div class="filter-left">
              <div class="fcheck">-</div>
              <span>Drafts</span>
            </div>
            <span class="fcount">1</span>
          </div>
        </div>

        <div class="sidebar-card">
          <div class="sidebar-title">Posted Date</div>
          <div class="filter-item">
            <div class="filter-left">
              <div class="fcheck">-</div>
              <span>Last 7 Days</span>
            </div>
            <span class="fcount">3</span>
          </div>
          <div class="filter-item">
            <div class="filter-left">
              <div class="fcheck">-</div>
              <span>Last 30 Days</span>
            </div>
            <span class="fcount">5</span>
          </div>
          <div class="filter-item">
            <div class="filter-left">
              <div class="fcheck">-</div>
              <span>Earlier</span>
            </div>
            <span class="fcount">3</span>
          </div>
        </div>
      </aside>

      <!-- MAIN CONTENT -->
      <div class="content-col">
        <div class="page-header">
          <h1>Job Postings</h1>
          <p>Create and manage all your active job postings</p>
        </div>

        <!-- NEW JOB BUTTON -->
        <div style="margin-bottom: 1.5rem;">
          <button class="btn-modal-trigger" onclick="openModal('jobModal')">
            + Create New Job Posting
          </button>
        </div>

        <!-- Success/Error Messages -->
        <?php if (!empty($success_message)): ?>
          <div id="success-alert" style="background: #d4edda; color: #155724; padding: 1rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem; border-left: 4px solid #155724; transition: opacity 0.4s ease, transform 0.4s ease;">
            ✓ <?php echo htmlspecialchars($success_message); ?>
          </div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
          <div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem; border-left: 4px solid #721c24;">
            ✗ <?php echo htmlspecialchars($error_message); ?>
          </div>
        <?php endif; ?>

        <!-- POSTED JOBS TABLE -->
        <div>
          <div class="jobs-list">
            <div class="jobs-list-header">Your Active Job Postings (<?php echo count($jobs); ?> total)</div>

            <?php if (count($jobs) > 0): ?>
              <?php foreach ($jobs as $job): ?>
                <div class="job-card">
                  <div>
                    <div class="job-title"><?php echo htmlspecialchars($job['title']); ?></div>
                    <div class="job-meta">
                      📍 <?php echo htmlspecialchars($job['location']); ?> •
                      💼 <?php echo htmlspecialchars($job['work_type']); ?> •
                      💰 <?php echo htmlspecialchars($job['salary']); ?>
                    </div>
                  </div>
                  <div style="text-align: center;">
                    <div style="font-size: 1.25rem; font-weight: 700; color: var(--primary-dark);">
                      <?php echo isset($job_counts[$job['job_post_id']]) ? $job_counts[$job['job_post_id']] : 0; ?>
                    </div>
                    <div style="font-size: 0.75rem; color: var(--text-light);">Applications</div>
                  </div>
                  <div style="text-align: center;">
                    <div style="background: #d4edda; color: #155724; padding: 0.4rem 0.8rem; border-radius: 4px; font-size: 0.75rem; font-weight: 700;">ACTIVE</div>
                  </div>
                  <div class="job-card-actions">
                    <button class="btn-small" style="background: white; border: 1px solid var(--border); padding: 0.4rem 0.8rem; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: 600;">Edit</button>
                    <button class="btn-small" style="background: white; border: 1px solid var(--border); padding: 0.4rem 0.8rem; border-radius: 4px; cursor: pointer; font-size: 0.8rem; font-weight: 600;">Close</button>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="job-card" style="text-align: center; color: var(--text-light); padding: 2rem; grid-column: 1 / -1;">
                <p>No job postings yet. Click "Create New Job Posting" to get started.</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    let jobLocationMap = null;
    let jobLocationMarker = null;
    let jobLocationDebounceTimer = null;
    let jobLocationRequestId = 0;

    function getJobLocationElements() {
      return {
        modal: document.getElementById('jobModal'),
        input: document.querySelector('input[name="location"]'),
        lat: document.getElementById('location_lat'),
        lng: document.getElementById('location_lng'),
        map: document.getElementById('locationMap')
      };
    }

    function setJobLocationCoordinates(lat, lng) {
      const elements = getJobLocationElements();
      if (elements.lat) elements.lat.value = lat !== null && lat !== undefined ? String(lat) : '';
      if (elements.lng) elements.lng.value = lng !== null && lng !== undefined ? String(lng) : '';
    }

    function ensureJobLocationMap() {
      if (!window.L || jobLocationMap) {
        return;
      }

      const elements = getJobLocationElements();
      if (!elements.map) {
        return;
      }

      jobLocationMap = L.map('locationMap', {
        zoomControl: true,
        scrollWheelZoom: false
      }).setView([14.0728, 120.6339], 13);

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
      }).addTo(jobLocationMap);
    }

    function setJobLocationMarker(lat, lng, shouldCenterMap) {
      if (!jobLocationMap || !window.L) {
        return;
      }

      const latLng = [Number(lat), Number(lng)];

      if (!jobLocationMarker) {
        jobLocationMarker = L.marker(latLng, {
          draggable: true
        }).addTo(jobLocationMap);
        jobLocationMarker.on('dragend', handleJobLocationMarkerDragEnd);
      } else {
        jobLocationMarker.setLatLng(latLng);
      }

      if (shouldCenterMap) {
        jobLocationMap.setView(latLng, 16, {
          animate: true
        });
      }
    }

    function scheduleJobLocationGeocode() {
      clearTimeout(jobLocationDebounceTimer);
      jobLocationDebounceTimer = setTimeout(runJobLocationGeocode, 500);
    }

    async function runJobLocationGeocode() {
      const elements = getJobLocationElements();
      if (!elements.input) {
        return;
      }

      const query = elements.input.value.trim();
      if (!query) {
        setJobLocationCoordinates('', '');
        return;
      }

      const requestId = ++jobLocationRequestId;

      try {
        const response = await fetch('https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q=' + encodeURIComponent(query), {
          headers: {
            'Accept': 'application/json',
            'User-Agent': 'TalentScoutAI/1.0'
          }
        });

        if (!response.ok) {
          return;
        }

        const results = await response.json();
        if (requestId !== jobLocationRequestId || !Array.isArray(results) || !results.length) {
          return;
        }

        const result = results[0];
        const lat = parseFloat(result.lat);
        const lng = parseFloat(result.lon);

        if (Number.isNaN(lat) || Number.isNaN(lng)) {
          return;
        }

        ensureJobLocationMap();
        setJobLocationMarker(lat, lng, true);
        setJobLocationCoordinates(lat, lng);
      } catch (error) {
        console.error('Location geocoding failed:', error);
      }
    }

    async function handleJobLocationMarkerDragEnd() {
      if (!jobLocationMarker) {
        return;
      }

      const latLng = jobLocationMarker.getLatLng();
      setJobLocationCoordinates(latLng.lat, latLng.lng);

      try {
        const response = await fetch('https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + encodeURIComponent(latLng.lat) + '&lon=' + encodeURIComponent(latLng.lng), {
          headers: {
            'Accept': 'application/json',
            'User-Agent': 'TalentScoutAI/1.0'
          }
        });

        if (!response.ok) {
          return;
        }

        const result = await response.json();
        if (result && result.display_name) {
          const elements = getJobLocationElements();
          if (elements.input) {
            elements.input.value = result.display_name;
          }
        }
      } catch (error) {
        console.error('Location reverse geocoding failed:', error);
      }
    }

    function refreshJobLocationMap() {
      if (!jobLocationMap) {
        return;
      }

      setTimeout(() => {
        jobLocationMap.invalidateSize();
      }, 250);
    }

    function openModal(modalId) {
      const modal = document.getElementById(modalId);
      if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';

        if (modalId === 'jobModal') {
          ensureJobLocationMap();
          refreshJobLocationMap();
        }
      }
    }

    function closeModal(modalId) {
      const modal = document.getElementById(modalId);
      if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
      }
    }

    document.addEventListener('DOMContentLoaded', function() {
      ensureJobLocationMap();

      const elements = getJobLocationElements();
      if (elements.input) {
        elements.input.addEventListener('input', function() {
          setJobLocationCoordinates('', '');
          scheduleJobLocationGeocode();
        });
      }

      const jobForm = document.querySelector('#jobModal form');
      if (jobForm) {
        jobForm.addEventListener('submit', function(event) {
          const latValue = elements.lat ? elements.lat.value.trim() : '';
          const lngValue = elements.lng ? elements.lng.value.trim() : '';

          if (!latValue || !lngValue) {
            event.preventDefault();
            alert('Please pin a location on the map before submitting.');
          }
        });
      }

      document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(event) {
          if (event.target === this) {
            closeModal(this.id);
          }
        });
      });

      document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
          document.querySelectorAll('.modal.active').forEach(modal => {
            closeModal(modal.id);
          });
        }
      });

      document.querySelectorAll('.input, .select, .textarea').forEach(input => {
        input.addEventListener('focus', function() {
          this.style.transform = 'scale(1.01)';
        });
        input.addEventListener('blur', function() {
          this.style.transform = 'scale(1)';
        });
      });

      document.querySelectorAll('.fcheck').forEach(checkbox => {
        checkbox.addEventListener('click', function(e) {
          e.preventDefault();
          this.classList.toggle('on');
        });
      });

      document.querySelectorAll('button').forEach(btn => {
        btn.addEventListener('click', function() {
          this.style.transform = 'scale(0.98)';
          setTimeout(() => {
            this.style.transform = 'scale(1)';
          }, 150);
        });
      });

      document.addEventListener('shown.bs.modal', function(event) {
        if (event.target && event.target.id === 'jobModal') {
          ensureJobLocationMap();
          refreshJobLocationMap();
        }
      });

      const successAlert = document.getElementById('success-alert');
      if (successAlert) {
        setTimeout(() => {
          successAlert.style.opacity = '0';
          successAlert.style.transform = 'translateY(-6px)';
          setTimeout(() => {
            successAlert.remove();
          }, 400);
        }, 3000);
      }
    });
  </script>

  <!-- CREATE JOB MODAL -->
  <div id="jobModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Create New Job Posting</h2>
        <button class="modal-close" onclick="closeModal('jobModal')">&times;</button>
      </div>
      <form method="POST">
        <input type="hidden" name="action" value="create_job">
        <div class="modal-body">
          <div class="form-group">
            <label class="form-label">Job Title *</label>
            <input type="text" name="job_title" class="input" placeholder="e.g., Senior React Developer" required>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Job Category *</label>
              <select name="job_category" class="select" required>
                <option value="">Select category</option>
                <option>Technology</option>
                <option>Finance</option>
                <option>Sales</option>
                <option>Marketing</option>
                <option>Healthcare</option>
                <option>Other</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Job Type *</label>
              <select name="job_type" class="select" required>
                <option value="">Select type</option>
                <option>Full-time</option>
                <option>Part-time</option>
                <option>Contract</option>
                <option>Remote</option>
                <option>Hybrid</option>
                <option>On-site</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Job Description *</label>
            <textarea name="job_description" class="textarea" placeholder="Describe the role, responsibilities, and requirements" required></textarea>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Salary Range *</label>
              <input type="text" name="salary_range" class="input" placeholder="e.g., 40,000 - 60,000" required>
            </div>
            <div class="form-group">
              <label class="form-label">Location *</label>
              <input type="text" name="location" class="input" placeholder="Barangay or area" id="locationInput" required>
              <div id="locationMap" class="location-map"></div>
              <input type="hidden" name="location_lat" id="location_lat">
              <input type="hidden" name="location_lng" id="location_lng">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Required Skills *</label>
            <input type="text" name="required_skills" class="input" placeholder="e.g., React, Node.js, PostgreSQL" required>
          </div>

          <div class="form-group">
            <label class="form-label">Application Deadline</label>
            <input type="date" name="application_deadline" class="input">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="submit-btn" style="background: white; border: 1px solid var(--border); color: var(--text-dark);" onclick="closeModal('jobModal')">Cancel</button>
          <button type="submit" class="submit-btn">Post Job Listing</button>
        </div>
      </form>
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
            <li><a href="./">Post Jobs</a></li>
            <li><a href="../employee-finder/">Find Talent</a></li>
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