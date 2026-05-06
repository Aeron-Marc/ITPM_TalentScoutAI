<?php
session_start();
require_once('../../../database/db.php');

if (!isset($_SESSION['employer_id'])) {
  header('Location: ../../login.php');
  exit;
}

$conn = getConnection();
$employer_id = (int)$_SESSION['employer_id'];

$success_message = '';
$error_message = '';

if (isset($_GET['job_created']) && $_GET['job_created'] === '1') {
  $success_message = "Job posting created successfully!";
}

if (isset($_GET['job_drafted']) && $_GET['job_drafted'] === '1') {
  $success_message = "Job saved as draft.";
}

if (isset($_GET['job_updated']) && $_GET['job_updated'] === '1') {
  $success_message = "Job posting updated successfully!";
}

if (isset($_GET['job_closed']) && $_GET['job_closed'] === '1') {
  $success_message = "Job posting has been closed.";
}

if (isset($_GET['job_deleted']) && $_GET['job_deleted'] === '1') {
  $success_message = "Job posting has been deleted.";
}

if (isset($_GET['job_published']) && $_GET['job_published'] === '1') {
  $success_message = "Draft published successfully!";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

  /* ── CREATE JOB (active or draft) ── */
  if ($_POST['action'] === 'create_job') {
    $is_draft   = isset($_POST['save_as_draft']) && $_POST['save_as_draft'] === '1';
    $title      = trim($_POST['job_title']              ?? '');
    $category   = trim($_POST['job_category']           ?? '');
    $description= trim($_POST['job_description']        ?? '');
    $salary     = trim($_POST['salary_range']           ?? '');
    $location   = trim($_POST['location']               ?? '');
    $location_lat = trim($_POST['location_lat']         ?? '');
    $location_lng = trim($_POST['location_lng']         ?? '');
    $work_type  = trim($_POST['job_type']               ?? '');
    $skills     = trim($_POST['required_skills']        ?? '');
    $deadline   = trim($_POST['application_deadline']   ?? '');
    $job_status = $is_draft ? 'draft' : 'active';

    // Drafts only need a title; active posts need everything
    $valid = $is_draft
      ? (bool)$title
      : ($title && $category && $description && $salary && $location && $work_type && $skills && $location_lat !== '' && $location_lng !== '');

    if ($valid) {
      $stmt = $conn->prepare(
        "INSERT INTO job_post (employer_id, title, description, salary, location, work_type, application_deadline, skills, experience_level, job_category, job_status, job_post_created)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, '', ?, ?, NOW())"
      );
      $stmt->bind_param("isssssssss",
        $employer_id, $title, $description, $salary, $location,
        $work_type, $deadline, $skills, $category, $job_status
      );
      if ($stmt->execute()) {
        $stmt->close();
        $redirect_param = $is_draft ? 'job_drafted=1' : 'job_created=1';
        header("Location: " . strtok($_SERVER['REQUEST_URI'], '?') . "?{$redirect_param}");
        exit;
      } else {
        $error_message = "Database error. Please try again.";
      }
    } else {
      $error_message = $is_draft
        ? "Please enter at least a job title to save as draft."
        : (($location_lat === '' || $location_lng === '')
            ? "Please pin a location on the map before submitting."
            : "Please fill in all required fields.");
    }
  }

  /* ── PUBLISH DRAFT ── */
  if ($_POST['action'] === 'publish_draft') {
    $job_id = intval($_POST['job_id'] ?? 0);
    if ($job_id > 0) {
      // Fetch the draft to check if all required fields are complete
      $stmt = $conn->prepare("SELECT title, job_category, description, salary, location, work_type, skills FROM job_post WHERE job_post_id = ? AND employer_id = ? AND job_status = 'draft'");
      $stmt->bind_param("ii", $job_id, $employer_id);
      $stmt->execute();
      $result = $stmt->get_result();
      $draft = $result->fetch_assoc();
      $stmt->close();

      if ($draft) {
        // Check if all required fields are complete
        $title = trim($draft['title'] ?? '');
        $category = trim($draft['job_category'] ?? '');
        $description = trim($draft['description'] ?? '');
        $salary = trim($draft['salary'] ?? '');
        $location = trim($draft['location'] ?? '');
        $work_type = trim($draft['work_type'] ?? '');
        $skills = trim($draft['skills'] ?? '');

        if ($title && $category && $description && $salary && $location && $work_type && $skills) {
          // All fields are complete - publish the draft
          $stmt = $conn->prepare("UPDATE job_post SET job_status = 'active' WHERE job_post_id = ? AND employer_id = ? AND job_status = 'draft'");
          $stmt->bind_param("ii", $job_id, $employer_id);
          $stmt->execute();
          $stmt->close();
          header("Location: " . strtok($_SERVER['REQUEST_URI'], '?') . "?job_published=1");
          exit;
        } else {
          // Missing required fields
          $error_message = "Cannot publish draft. Please complete all required fields: Title, Category, Description, Salary, Location, Job Type, and Required Skills.";
        }
      }
    }
  }

  /* ── CLOSE JOB ── */
  if ($_POST['action'] === 'close_job') {
    $job_id = intval($_POST['job_id'] ?? 0);
    if ($job_id > 0) {
      $stmt = $conn->prepare("UPDATE job_post SET job_status = 'closed' WHERE job_post_id = ? AND employer_id = ?");
      $stmt->bind_param("ii", $job_id, $employer_id);
      $stmt->execute();
      $stmt->close();
      header("Location: " . strtok($_SERVER['REQUEST_URI'], '?') . "?job_closed=1");
      exit;
    }
  }

  /* ── DELETE JOB ── */
  if ($_POST['action'] === 'delete_job') {
    $job_id = intval($_POST['job_id'] ?? 0);
    if ($job_id > 0) {
      $stmt = $conn->prepare("DELETE FROM job_post WHERE job_post_id = ? AND employer_id = ?");
      $stmt->bind_param("ii", $job_id, $employer_id);
      $stmt->execute();
      $stmt->close();
      header("Location: " . strtok($_SERVER['REQUEST_URI'], '?') . "?job_deleted=1");
      exit;
    }
  }

  /* ── UPDATE JOB ── */
  if ($_POST['action'] === 'update_job') {
    $job_id      = intval($_POST['job_id']                   ?? 0);
    $title       = trim($_POST['edit_job_title']             ?? '');
    $category    = trim($_POST['edit_job_category']          ?? '');
    $description = trim($_POST['edit_job_description']       ?? '');
    $salary      = trim($_POST['edit_salary_range']          ?? '');
    $location    = trim($_POST['edit_location']              ?? '');
    $work_type   = trim($_POST['edit_job_type']              ?? '');
    $skills      = trim($_POST['edit_required_skills']       ?? '');
    $deadline    = trim($_POST['edit_application_deadline']  ?? '');
    // If save_as_draft flag is set, keep/set draft; else keep current status (don't change)
    $force_draft = isset($_POST['edit_save_as_draft']) && $_POST['edit_save_as_draft'] === '1';

    if ($job_id > 0 && $title) {
      if ($force_draft) {
        $stmt = $conn->prepare(
          "UPDATE job_post SET title=?, description=?, salary=?, location=?, work_type=?, application_deadline=?, skills=?, job_category=?, job_status='draft'
           WHERE job_post_id=? AND employer_id=?"
        );
      } else {
        $stmt = $conn->prepare(
          "UPDATE job_post SET title=?, description=?, salary=?, location=?, work_type=?, application_deadline=?, skills=?, job_category=?
           WHERE job_post_id=? AND employer_id=?"
        );
      }
      $stmt->bind_param("ssssssssii", $title, $description, $salary, $location, $work_type, $deadline, $skills, $category, $job_id, $employer_id);
      $stmt->execute();
      $stmt->close();
      $rp = $force_draft ? 'job_drafted=1' : 'job_updated=1';
      header("Location: " . strtok($_SERVER['REQUEST_URI'], '?') . "?{$rp}");
      exit;
    } else {
      $error_message = "Please fill in at least the job title.";
    }
  }
}

/* ══ FETCH JOBS ══ */
$filter_status = $_GET['status'] ?? 'all';

$jobs = [];
if ($filter_status === 'all') {
  $stmt = $conn->prepare(
    "SELECT job_post_id, title, salary, location, work_type, job_post_created, job_status, job_category, description, skills, application_deadline
     FROM job_post WHERE employer_id = ?
     ORDER BY job_post_created DESC"
  );
  $stmt->bind_param("i", $employer_id);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) $jobs[] = $row;
  $stmt->close();
} elseif ($filter_status === 'active') {
  $stmt = $conn->prepare(
    "SELECT job_post_id, title, salary, location, work_type, job_post_created, job_status, job_category, description, skills, application_deadline
     FROM job_post WHERE employer_id = ? AND (job_status IS NULL OR job_status = 'active' OR job_status = '')
     ORDER BY job_post_created DESC"
  );
  $stmt->bind_param("i", $employer_id);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) $jobs[] = $row;
  $stmt->close();
} elseif ($filter_status === 'closed') {
  $stmt = $conn->prepare(
    "SELECT job_post_id, title, salary, location, work_type, job_post_created, job_status, job_category, description, skills, application_deadline
     FROM job_post WHERE employer_id = ? AND job_status = 'closed'
     ORDER BY job_post_created DESC"
  );
  $stmt->bind_param("i", $employer_id);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) $jobs[] = $row;
  $stmt->close();
} elseif ($filter_status === 'drafts') {
  $stmt = $conn->prepare(
    "SELECT job_post_id, title, salary, location, work_type, job_post_created, job_status, job_category, description, skills, application_deadline
     FROM job_post WHERE employer_id = ? AND job_status = 'draft'
     ORDER BY job_post_created DESC"
  );
  $stmt->bind_param("i", $employer_id);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) $jobs[] = $row;
  $stmt->close();
}

/* ── Application counts ── */
$job_counts = [];
$stmt = $conn->prepare(
  "SELECT job_post_id, COUNT(*) as count FROM application
   WHERE job_post_id IN (SELECT job_post_id FROM job_post WHERE employer_id = ?)
   GROUP BY job_post_id"
);
$stmt->bind_param("i", $employer_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) $job_counts[$row['job_post_id']] = $row['count'];
$stmt->close();

/* ── Stats ── */
$stats = ['active' => 0, 'closed' => 0, 'drafts' => 0];
$stmt = $conn->prepare("SELECT job_status, COUNT(*) as count FROM job_post WHERE employer_id = ? GROUP BY job_status");
$stmt->bind_param("i", $employer_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $status = $row['job_status'] ?? 'active';
  if ($status === 'closed')      $stats['closed']  = (int)$row['count'];
  elseif ($status === 'draft')   $stats['drafts']  = (int)$row['count'];
  else                           $stats['active'] += (int)$row['count'];
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Post Job Listings — TalentScout AI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../../styles/global.css">
  <link rel="stylesheet" href="../../../styles/page-layout.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <style>
    *, *::before, *::after { box-sizing: border-box; }
    html, body { height: 100%; margin: 0; padding: 0; }
    body { display: flex; flex-direction: column; background: #f7f9f8; font-family: 'Plus Jakarta Sans', sans-serif; }

    /* ══ NAVBAR ══ */
    .navbar {
      position: fixed; top: 0; left: 0; right: 0; z-index: 200;
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 2.5rem; height: 66px;
      background: var(--sage);
      border-bottom: 1px solid rgba(0,0,0,0.1);
      transition: all 0.4s;
      animation: navSlide 0.7s var(--ease) both;
    }

    .navbar.scrolled {
      background: var(--sage);
      border-bottom-color: rgba(0,0,0,0.15);
    }

    @keyframes navSlide {
      from { transform: translateY(-100%); opacity: 0; }
      to   { transform: translateY(0);     opacity: 1; }
    }

    .nav-logo {
      display: flex; align-items: center; gap: 0.6rem;
      font-family: 'Lora', serif; font-weight: 700; font-size: 1.12rem;
      color: #fff;
      transition: color 0.4s;
    }

    .navbar.scrolled .nav-logo { color: var(--charcoal); }

    .nav-logo-mark {
      width: 36px; height: 36px;
      background: linear-gradient(135deg, var(--sage), var(--sage-dark));
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 0.7rem; font-weight: 700; color: #fff; letter-spacing: 0.05em;
      box-shadow: 0 4px 12px rgba(90,138,104,0.35);
    }

    .nav-logo-text { display: inline; }
    .nav-logo-text span { color: var(--mint-deep); transition: color 0.4s; }
    .navbar.scrolled .nav-logo-text span { color: var(--sage); }

    .nav-links { display: flex; list-style: none; gap: 0.2rem; margin: 0; padding: 0; }

    .nav-links a {
      padding: 0.2rem 0.75rem;
      font-size: 0.84rem; font-weight: 500; color: rgba(255,255,255,0.8);
      transition: color 0.2s, border-bottom 0.2s;
      position: relative;
      padding-bottom: 0.4rem;
    }

    .navbar.scrolled .nav-links a { color: rgba(255,255,255,0.8); }

    .nav-links a:hover {
      color: #fff;
      font-weight: 600;
    }

    .nav-links a.active {
      color: #fff;
      font-weight: 600;
      border-bottom: 2.5px solid #fff;
    }

    .navbar.scrolled .nav-links a:hover {
      color: #fff;
    }

    .navbar.scrolled .nav-links a.active {
      color: #fff;
      border-bottom-color: #fff;
    }

    .nav-right { display: flex; align-items: center; gap: 0.65rem; }

    .nav-user { font-size: 0.82rem; color: rgba(255,255,255,0.75); transition: color 0.4s; }
    .navbar.scrolled .nav-user { color: var(--text-soft); }

    .btn-ghost {
      padding: 0.42rem 1.1rem; border-radius: var(--radius-pill);
      border: 1.5px solid rgba(255,255,255,0.4); color: #fff;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 0.83rem; font-weight: 500; background: transparent;
      cursor: pointer; transition: all 0.2s; text-decoration: none;
    }

    .btn-ghost:hover {
      background: rgba(255,255,255,0.15);
      border-color: #fff;
    }

    .navbar.scrolled .btn-ghost {
      border-color: rgba(90,138,104,0.3);
      color: var(--text-mid);
    }

    .navbar.scrolled .btn-ghost:hover {
      background: var(--mint);
      border-color: var(--sage);
      color: var(--sage-dark);
    }

    .btn-solid {
      padding: 0.42rem 1.3rem; border-radius: var(--radius-pill);
      background: linear-gradient(135deg, var(--mint-deep), var(--mint));
      color: var(--charcoal); font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 0.83rem; font-weight: 700; border: none;
      cursor: pointer; transition: all 0.2s; text-decoration: none;
      box-shadow: 0 4px 14px rgba(90,138,104,0.32);
    }

    .btn-solid:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 22px rgba(90,138,104,0.4);
    }

    /* ══ PAGE WRAPPER ══ */
    .page-wrapper { flex: 1 0 auto; padding-top: 66px; }

    /* ══ PAGE HERO ══ */
    .page-hero {
      background: linear-gradient(135deg, #f0fff8 0%, #e8f8f0 60%, #f5fdf8 100%);
      border-bottom: 1px solid rgba(90,138,104,0.12);
      padding: 2.5rem 2.5rem 2rem;
    }
    .page-hero-inner { max-width: 1200px; margin: 0 auto; }
    .page-hero h1 { font-family: 'Lora', serif; font-size: 1.9rem; font-weight: 700; color: var(--text-dark); margin: 0 0 0.3rem 0; }
    .page-hero p { font-size: 0.92rem; color: var(--text-light); margin: 0; }

    /* ══ MAIN LAYOUT ══ */
    .main-layout { max-width: 1200px; margin: 0 auto; padding: 2rem 2.5rem 3rem; display: grid; grid-template-columns: 220px 1fr; gap: 2rem; align-items: start; }

    /* ══ SIDEBAR ══ */
    .sidebar { position: sticky; top: 82px; }
    .sidebar-card { background: white; border: 1px solid rgba(90,138,104,0.13); border-radius: 14px; overflow: hidden; margin-bottom: 1rem; box-shadow: 0 2px 10px rgba(0,0,0,0.04); }
    .sidebar-title { font-weight: 700; font-size: 0.72rem; color: var(--text-light); padding: 0.85rem 1rem 0.6rem; text-transform: uppercase; letter-spacing: 0.08em; border-bottom: 1px solid rgba(90,138,104,0.1); background: #fafcfb; }
    .filter-item { display: flex; align-items: center; justify-content: space-between; padding: 0.62rem 1rem; font-size: 0.85rem; color: var(--text-mid); text-decoration: none; transition: background 0.15s; border-bottom: 1px solid rgba(0,0,0,0.03); }
    .filter-item:last-child { border-bottom: none; }
    .filter-item:hover { background: rgba(90,138,104,0.05); }
    .filter-item.active-filter { background: rgba(90,138,104,0.08); color: var(--sage-dark, #3d7a55); font-weight: 600; }
    .filter-left { display: flex; align-items: center; gap: 0.55rem; }
    .fcheck { width: 17px; height: 17px; border: 1.5px solid rgba(90,138,104,0.3); border-radius: 5px; display: flex; align-items: center; justify-content: center; font-size: 0.6rem; flex-shrink: 0; color: transparent; }
    .fcheck.on { background: var(--sage-dark, #3d7a55); border-color: var(--sage-dark, #3d7a55); color: white; }
    .fcount { background: rgba(90,138,104,0.1); color: var(--sage-dark, #3d7a55); padding: 0.08rem 0.5rem; border-radius: 100px; font-size: 0.72rem; font-weight: 600; }

    /* Draft count chip — amber */
    .fcount.draft-count { background: rgba(210,140,30,0.12); color: #a07010; }

    /* ══ CONTENT COL ══ */
    .content-col { display: flex; flex-direction: column; gap: 1.25rem; min-width: 0; }
    .toolbar { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
    .toolbar-label { font-size: 0.9rem; color: var(--text-light); }
    .toolbar-label strong { color: var(--text-dark); }
    .btn-create { display: inline-flex; align-items: center; gap: 0.5rem; background: linear-gradient(135deg, var(--sage, #5a8a68), var(--sage-dark, #3d7a55)); color: #fff; padding: 0.6rem 1.3rem; border: none; border-radius: 10px; font-size: 0.88rem; font-weight: 700; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 14px rgba(90,138,104,0.3); font-family: 'Plus Jakarta Sans', sans-serif; }
    .btn-create:hover { transform: translateY(-2px); box-shadow: 0 8px 22px rgba(90,138,104,0.38); }
    .btn-create svg { flex-shrink: 0; }

    /* ══ JOB CARDS ══ */
    .jobs-list { display: flex; flex-direction: column; gap: 0.85rem; }
    .job-card { background: white; border: 1px solid rgba(90,138,104,0.13); border-radius: 14px; padding: 1.3rem 1.5rem; display: flex; align-items: center; gap: 1.25rem; transition: box-shadow 0.2s, transform 0.2s, border-color 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
    .job-card:hover { box-shadow: 0 6px 24px rgba(90,138,104,0.12); transform: translateY(-2px); border-color: rgba(90,138,104,0.25); }

    /* Draft card — amber left border */
    .job-card.draft-card { border-left: 3px solid #d4a017; background: #fffdf5; }

    .job-icon { width: 46px; height: 46px; border-radius: 12px; background: linear-gradient(135deg, rgba(90,138,104,0.12), rgba(90,138,104,0.2)); display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; }
    .job-info { flex: 1; min-width: 0; }
    .job-title { font-size: 0.97rem; font-weight: 700; color: var(--text-dark); margin-bottom: 0.3rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .job-meta { display: flex; flex-wrap: wrap; gap: 0.6rem; font-size: 0.78rem; color: var(--text-light); }
    .job-meta-item { display: flex; align-items: center; gap: 0.28rem; }

    .app-count { display: flex; flex-direction: column; align-items: center; justify-content: center; min-width: 56px; padding: 0.5rem 0.75rem; background: rgba(90,138,104,0.06); border-radius: 10px; flex-shrink: 0; }
    .app-count-num { font-size: 1.25rem; font-weight: 700; color: var(--sage-dark, #3d7a55); line-height: 1; font-family: 'Lora', serif; }
    .app-count-label { font-size: 0.65rem; color: var(--text-light); font-weight: 500; text-align: center; }

    .status-badge { padding: 0.3rem 0.7rem; border-radius: 6px; font-size: 0.72rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; flex-shrink: 0; }
    .status-badge.active { background: #dcf5e9; color: #1a7a46; }
    .status-badge.closed { background: #fde8e8; color: #9b2335; }
    .status-badge.draft  { background: #fff3cd; color: #856404; }

    .job-actions { display: flex; align-items: center; gap: 0.4rem; flex-shrink: 0; }
    .icon-btn { width: 34px; height: 34px; border-radius: 8px; border: 1px solid rgba(90,138,104,0.2); background: white; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.18s; color: var(--text-mid); }
    .icon-btn:hover { background: rgba(90,138,104,0.08); border-color: rgba(90,138,104,0.35); color: var(--sage-dark, #3d7a55); transform: translateY(-1px); }
    .icon-btn.danger:hover { background: #fde8e8; border-color: #f5c6cb; color: #9b2335; }
    .icon-btn.amber:hover { background: #fff3cd; border-color: #ffc107; color: #856404; }
    .icon-btn svg { pointer-events: none; }
    .icon-btn-wrap { position: relative; }
    .icon-btn-wrap:hover .icon-tooltip { opacity: 1; transform: translateY(0); }
    .icon-tooltip { position: absolute; bottom: calc(100% + 6px); left: 50%; transform: translateX(-50%) translateY(4px); background: rgba(30,30,30,0.88); color: white; font-size: 0.7rem; font-weight: 600; padding: 0.25rem 0.55rem; border-radius: 5px; white-space: nowrap; opacity: 0; transition: opacity 0.15s, transform 0.15s; pointer-events: none; z-index: 10; }

    /* Empty state */
    .empty-state { background: white; border: 1.5px dashed rgba(90,138,104,0.25); border-radius: 14px; padding: 3rem 2rem; text-align: center; }
    .empty-state-icon { font-size: 2.5rem; margin-bottom: 1rem; }
    .empty-state h3 { font-size: 1rem; font-weight: 700; color: var(--text-dark); margin: 0 0 0.4rem 0; }
    .empty-state p { font-size: 0.85rem; color: var(--text-light); margin: 0; }

    /* ══ MODALS ══ */
    .modal { position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.45); backdrop-filter: blur(4px); animation: fadeIn 0.2s ease; display: none !important; align-items: center; justify-content: center; }
    .modal.active { display: flex !important; }
    .modal-content { background: white; border-radius: 18px; box-shadow: 0 20px 60px rgba(0,0,0,0.18); width: 90%; max-width: 660px; max-height: 90vh; overflow-y: auto; position: relative; animation: slideUp 0.3s cubic-bezier(0.22, 1, 0.36, 1); }
    .modal-content.narrow { max-width: 460px; }
    .modal-header { padding: 1.5rem 1.75rem 1.25rem; border-bottom: 1px solid rgba(90,138,104,0.12); display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #f8fffc, #f0fff8); border-radius: 18px 18px 0 0; }
    .modal-header h2 { font-family: 'Lora', serif; font-size: 1.2rem; font-weight: 700; color: var(--text-dark); margin: 0; }
    .modal-close { background: none; border: none; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 8px; cursor: pointer; color: var(--text-light); transition: all 0.15s; font-size: 1.4rem; padding: 0; }
    .modal-close:hover { background: rgba(0,0,0,0.06); color: var(--text-dark); }
    .modal-body { padding: 1.5rem 1.75rem; }
    .modal-footer { padding: 1.25rem 1.75rem; border-top: 1px solid rgba(90,138,104,0.1); display: flex; gap: 0.75rem; justify-content: flex-end; align-items: center; flex-wrap: wrap; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes slideUp { from { transform: translateY(24px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

    /* ══ FORMS ══ */
    .form-group { margin-bottom: 1.1rem; }
    .form-label { display: block; font-size: 0.83rem; font-weight: 600; color: var(--text-dark); margin-bottom: 0.4rem; }
    .input, .select, .textarea { width: 100%; padding: 0.6rem 0.85rem; border: 1.5px solid rgba(90,138,104,0.2); border-radius: 9px; font-size: 0.875rem; font-family: inherit; color: var(--text-dark); background: white; transition: border-color 0.2s, box-shadow 0.2s; }
    .input:focus, .select:focus, .textarea:focus { outline: none; border-color: var(--sage, #5a8a68); box-shadow: 0 0 0 3px rgba(90,138,104,0.1); }
    .textarea { resize: vertical; min-height: 90px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem; }
    .form-row .form-group { margin-bottom: 0; }

    /* Draft helper note */
    .draft-note { background: #fffbea; border: 1px solid rgba(210,148,0,0.2); border-radius: 8px; padding: 0.65rem 0.9rem; font-size: 0.8rem; color: #7a5a00; margin-bottom: 1.1rem; display: flex; align-items: center; gap: 0.5rem; }

    /* Map */
    .location-map { width: 100%; height: 220px; margin-top: 0.6rem; border: 1.5px solid rgba(90,138,104,0.2); border-radius: 9px; overflow: hidden; background: #eef5f2; cursor: crosshair; }
    .location-hint { font-size: 0.78rem; color: var(--text-light); margin-top: 0.35rem; display: flex; align-items: center; gap: 0.4rem; }
    .location-confirm-popup { text-align: center; min-width: 190px; }
    .location-confirm-popup h4 { margin: 0 0 0.4rem 0; font-size: 0.9rem; color: var(--text-dark); }
    .location-confirm-popup .popup-address { font-size: 0.76rem; color: var(--text-mid); margin-bottom: 0.65rem; max-height: 55px; overflow-y: auto; line-height: 1.4; }
    .location-confirm-popup .popup-actions { display: flex; gap: 0.45rem; justify-content: center; }
    .location-confirm-popup .popup-btn { padding: 0.35rem 0.9rem; border: none; border-radius: 6px; font-size: 0.78rem; font-weight: 600; cursor: pointer; transition: opacity 0.15s; }
    .popup-btn-cancel { background: #e9ecef; color: var(--text-mid); }
    .popup-btn-save   { background: var(--sage, #5a8a68); color: white; }
    .popup-btn:hover  { opacity: 0.82; }

    /* Modal footer buttons */
    .btn-modal-cancel { background: #f0f0f0; color: var(--text-mid); border: none; padding: 0.6rem 1.3rem; border-radius: 9px; font-size: 0.875rem; font-weight: 600; cursor: pointer; font-family: inherit; transition: background 0.15s; }
    .btn-modal-cancel:hover { background: #e2e2e2; }
    .btn-modal-submit { background: linear-gradient(135deg, var(--sage, #5a8a68), var(--sage-dark, #3d7a55)); color: white; border: none; padding: 0.6rem 1.5rem; border-radius: 9px; font-size: 0.875rem; font-weight: 700; cursor: pointer; font-family: inherit; transition: all 0.2s; box-shadow: 0 3px 10px rgba(90,138,104,0.3); }
    .btn-modal-submit:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(90,138,104,0.38); }
    .btn-modal-draft { background: #fff3cd; color: #856404; border: 1.5px solid #ffc107; padding: 0.6rem 1.2rem; border-radius: 9px; font-size: 0.875rem; font-weight: 700; cursor: pointer; font-family: inherit; transition: all 0.2s; }
    .btn-modal-draft:hover { background: #ffeaa0; transform: translateY(-1px); }
    .btn-modal-danger { background: #dc3545; color: white; border: none; padding: 0.6rem 1.5rem; border-radius: 9px; font-size: 0.875rem; font-weight: 700; cursor: pointer; font-family: inherit; transition: all 0.2s; }
    .btn-modal-danger:hover { background: #c82333; transform: translateY(-1px); }

    /* Publish button (green outlined) */
    .btn-modal-publish { background: white; color: var(--sage-dark, #3d7a55); border: 1.5px solid var(--sage, #5a8a68); padding: 0.6rem 1.2rem; border-radius: 9px; font-size: 0.875rem; font-weight: 700; cursor: pointer; font-family: inherit; transition: all 0.2s; }
    .btn-modal-publish:hover { background: rgba(90,138,104,0.08); transform: translateY(-1px); }

    /* ══ ALERTS ══ */
    .alert { padding: 0.85rem 1.1rem; border-radius: 10px; font-size: 0.875rem; font-weight: 500; display: flex; align-items: center; gap: 0.6rem; transition: opacity 0.4s, transform 0.4s; }
    .alert-success { background: #dcf5e9; color: #1a7a46; border: 1px solid rgba(26,122,70,0.2); }
    .alert-error   { background: #fde8e8; color: #9b2335; border: 1px solid rgba(155,35,53,0.2); }
    .warning-text  { background: #fff8e8; border: 1px solid rgba(210,148,0,0.2); border-radius: 8px; padding: 0.75rem 1rem; font-size: 0.83rem; color: #7a5a00; margin-bottom: 0.75rem; font-weight: 600; }

    /* ══ FOOTER ══ */
    .footer { flex-shrink: 0; }

    /* Leaflet popup tweaks */
    .leaflet-popup-content-wrapper { border-radius: 10px !important; box-shadow: 0 6px 24px rgba(0,0,0,0.14) !important; padding: 0 !important; }
    .leaflet-popup-content { margin: 0 !important; line-height: 1.4 !important; }
    .location-popup-wrapper .location-confirm-popup { padding: 0.7rem; }
  </style>
</head>

<body>

  <!-- ══ NAVBAR ══ -->
  <nav class="navbar">
    <a href="../../index.php" class="nav-logo">
      <div class="nav-logo-mark">TS</div>
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

  <div class="page-wrapper">

    <!-- ══ PAGE HERO (no stats chips) ══ -->
    <div class="page-hero">
      <div class="page-hero-inner">
        <h1>Job Postings</h1>
        <p>Create and manage all your active job listings in one place.</p>
      </div>
    </div>

    <!-- ══ MAIN LAYOUT ══ -->
    <div class="main-layout">

      <!-- SIDEBAR -->
      <aside class="sidebar">
        <div class="sidebar-card">
          <div class="sidebar-title">Filter by Status</div>
          <?php
            $filters = [
              ['label' => 'All Jobs', 'key' => 'all',    'count' => $stats['active'] + $stats['closed'] + $stats['drafts'], 'draft' => false],
              ['label' => 'Active',   'key' => 'active',  'count' => $stats['active'],  'draft' => false],
              ['label' => 'Closed',   'key' => 'closed',  'count' => $stats['closed'],  'draft' => false],
              ['label' => 'Drafts',   'key' => 'drafts',  'count' => $stats['drafts'],  'draft' => true],
            ];
            foreach ($filters as $f):
              $isActive = ($filter_status === $f['key']) || ($f['key'] === 'all' && ($filter_status === '' || $filter_status === 'all'));
          ?>
          <a href="?status=<?php echo $f['key']; ?>" class="filter-item <?php echo $isActive ? 'active-filter' : ''; ?>">
            <div class="filter-left">
              <div class="fcheck <?php echo $isActive ? 'on' : ''; ?>"><?php echo $isActive ? '✓' : ''; ?></div>
              <span><?php echo $f['label']; ?></span>
            </div>
            <span class="fcount <?php echo $f['draft'] ? 'draft-count' : ''; ?>"><?php echo $f['count']; ?></span>
          </a>
          <?php endforeach; ?>
        </div>

        <div class="sidebar-card">
          <div class="sidebar-title">Posted Date</div>
          <div class="filter-item">
            <div class="filter-left"><div class="fcheck">-</div><span>Last 7 Days</span></div>
            <span class="fcount">—</span>
          </div>
          <div class="filter-item">
            <div class="filter-left"><div class="fcheck">-</div><span>Last 30 Days</span></div>
            <span class="fcount">—</span>
          </div>
          <div class="filter-item">
            <div class="filter-left"><div class="fcheck">-</div><span>Earlier</span></div>
            <span class="fcount">—</span>
          </div>
        </div>
      </aside>

      <!-- CONTENT -->
      <div class="content-col">

        <!-- Alerts -->
        <?php if (!empty($success_message)): ?>
          <div id="success-alert" class="alert alert-success">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="7.5" stroke="currentColor"/><path d="M5 8l2 2 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
            <?php echo htmlspecialchars($success_message); ?>
          </div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
          <div class="alert alert-error">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="7.5" stroke="currentColor"/><path d="M8 5v3M8 10.5v.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
            <?php echo htmlspecialchars($error_message); ?>
          </div>
        <?php endif; ?>

        <!-- Toolbar -->
        <div class="toolbar">
          <span class="toolbar-label">
            Showing <strong><?php echo count($jobs); ?></strong> job<?php echo count($jobs) !== 1 ? 's' : ''; ?>
            <?php if ($filter_status !== 'all'): ?>
              — <strong><?php echo ucfirst($filter_status); ?></strong>
            <?php endif; ?>
          </span>
          <button class="btn-create" onclick="openModal('jobModal')">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M8 3v10M3 8h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            Create New Posting
          </button>
        </div>

        <!-- Job Cards -->
        <div class="jobs-list">
          <?php if (count($jobs) > 0): ?>
            <?php
              $category_icons = [
                'IT' => '💻', 'Healthcare' => '🏥', 'Education' => '📚',
                'Finance' => '💰', 'Retail' => '🛒', 'Manufacturing' => '🏭',
                'Construction' => '🏗️', 'Food' => '🍽️', 'Transport' => '🚚',
                'Admin' => '📋', 'Other' => '💼',
              ];
            ?>
            <?php foreach ($jobs as $job):
              $job_status = $job['job_status'] ?? 'active';
              $is_closed  = ($job_status === 'closed');
              $is_draft   = ($job_status === 'draft');
              $icon       = $category_icons[$job['job_category'] ?? ''] ?? '💼';
              $apps       = $job_counts[$job['job_post_id']] ?? 0;
            ?>
            <div class="job-card <?php echo $is_draft ? 'draft-card' : ''; ?>">

              <div class="job-icon"><?php echo $icon; ?></div>

              <div class="job-info">
                <div class="job-title"><?php echo htmlspecialchars($job['title']); ?></div>
                <div class="job-meta">
                  <?php if ($job['location']): ?>
                  <span class="job-meta-item">
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M6 1C4.067 1 2.5 2.567 2.5 4.5c0 2.625 3.5 6.5 3.5 6.5s3.5-3.875 3.5-6.5C9.5 2.567 7.933 1 6 1zm0 4.75a1.25 1.25 0 110-2.5 1.25 1.25 0 010 2.5z" fill="currentColor" opacity=".6"/></svg>
                    <?php echo htmlspecialchars($job['location']); ?>
                  </span>
                  <?php endif; ?>
                  <?php if ($job['work_type']): ?>
                  <span class="job-meta-item">
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><rect x="1.5" y="2.5" width="9" height="7" rx="1" stroke="currentColor" opacity=".6"/><path d="M1.5 5h9" stroke="currentColor" opacity=".6"/><path d="M4 2.5V1.5M8 2.5V1.5" stroke="currentColor" opacity=".6" stroke-linecap="round"/></svg>
                    <?php echo htmlspecialchars($job['work_type']); ?>
                  </span>
                  <?php endif; ?>
                  <span class="job-meta-item">
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><circle cx="6" cy="6" r="4.5" stroke="currentColor" opacity=".6"/><path d="M6 3.5v2.75l1.5 1.5" stroke="currentColor" opacity=".6" stroke-linecap="round"/></svg>
                    <?php echo date('M j, Y', strtotime($job['job_post_created'])); ?>
                  </span>
                  <?php if ($job['salary']): ?>
                  <span class="job-meta-item">₱ <?php echo htmlspecialchars($job['salary']); ?></span>
                  <?php endif; ?>
                  <?php if ($is_draft): ?>
                  <span class="job-meta-item" style="color:#a07010;font-weight:600;">✏️ Incomplete draft</span>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Applications (hide for drafts) -->
              <?php if (!$is_draft): ?>
              <div class="app-count">
                <div class="app-count-num"><?php echo $apps; ?></div>
                <div class="app-count-label">Applicants</div>
              </div>
              <?php endif; ?>

              <!-- Status badge -->
              <span class="status-badge <?php echo $is_draft ? 'draft' : ($is_closed ? 'closed' : 'active'); ?>">
                <?php echo $is_draft ? 'Draft' : ($is_closed ? 'Closed' : 'Active'); ?>
              </span>

              <!-- Actions -->
              <div class="job-actions">

                <!-- Edit -->
                <div class="icon-btn-wrap">
                  <button class="icon-btn" onclick="openEditModal(
                    <?php echo $job['job_post_id']; ?>,
                    '<?php echo htmlspecialchars(addslashes($job['title'])); ?>',
                    '<?php echo htmlspecialchars(addslashes($job['job_category'] ?? '')); ?>',
                    '<?php echo htmlspecialchars(addslashes($job['description'])); ?>',
                    '<?php echo htmlspecialchars(addslashes($job['salary'])); ?>',
                    '<?php echo htmlspecialchars(addslashes($job['location'])); ?>',
                    '<?php echo htmlspecialchars(addslashes($job['work_type'])); ?>',
                    '<?php echo htmlspecialchars(addslashes($job['skills'])); ?>',
                    '<?php echo $job['application_deadline'] ?? ''; ?>',
                    '<?php echo $job_status; ?>')">
                    <svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M10.586 1.586a2 2 0 012.828 2.828L4.5 13.328l-3 .672.672-3 8.414-9.414z" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/></svg>
                  </button>
                  <span class="icon-tooltip">Edit</span>
                </div>

                <?php if ($is_draft): ?>
                  <!-- Publish draft -->
                  <form id="publishForm_<?php echo $job['job_post_id']; ?>" method="POST" style="display:none;">
                    <input type="hidden" name="action" value="publish_draft">
                    <input type="hidden" name="job_id" value="<?php echo $job['job_post_id']; ?>">
                  </form>
                  <div class="icon-btn-wrap">
                    <button class="icon-btn amber" onclick="openPublishConfirm(<?php echo $job['job_post_id']; ?>)">
                      <!-- Send/publish icon -->
                      <svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M13.5 7.5L1.5 2l2.5 5.5-2.5 5.5 12-5.5z" stroke="currentColor" stroke-width="1.35" stroke-linejoin="round"/></svg>
                    </button>
                    <span class="icon-tooltip">Publish</span>
                  </div>
                  <!-- Delete draft -->
                  <form id="deleteForm_<?php echo $job['job_post_id']; ?>" method="POST" style="display:none;">
                    <input type="hidden" name="action" value="delete_job">
                    <input type="hidden" name="job_id" value="<?php echo $job['job_post_id']; ?>">
                  </form>
                  <div class="icon-btn-wrap">
                    <button class="icon-btn danger" onclick="openDeleteConfirm(<?php echo $job['job_post_id']; ?>)">
                      <svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M2.5 4h10M5 4V2.5h5V4M6 7v4M9 7v4M3.5 4l.5 8.5h7L12 4" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <span class="icon-tooltip">Delete</span>
                  </div>

                <?php elseif (!$is_closed): ?>
                  <!-- Close active job -->
                  <form id="closeForm_<?php echo $job['job_post_id']; ?>" method="POST" style="display:none;">
                    <input type="hidden" name="action" value="close_job">
                    <input type="hidden" name="job_id" value="<?php echo $job['job_post_id']; ?>">
                  </form>
                  <div class="icon-btn-wrap">
                    <button class="icon-btn" onclick="openCloseConfirm(<?php echo $job['job_post_id']; ?>)">
                      <svg width="15" height="15" viewBox="0 0 15 15" fill="none"><rect x="2.5" y="6.5" width="10" height="7" rx="1.5" stroke="currentColor" stroke-width="1.35"/><path d="M5 6.5V4.5a2.5 2.5 0 015 0v2" stroke="currentColor" stroke-width="1.35" stroke-linecap="round"/><circle cx="7.5" cy="10" r="1" fill="currentColor"/></svg>
                    </button>
                    <span class="icon-tooltip">Close</span>
                  </div>

                <?php else: ?>
                  <!-- Delete closed job -->
                  <form id="deleteForm_<?php echo $job['job_post_id']; ?>" method="POST" style="display:none;">
                    <input type="hidden" name="action" value="delete_job">
                    <input type="hidden" name="job_id" value="<?php echo $job['job_post_id']; ?>">
                  </form>
                  <div class="icon-btn-wrap">
                    <button class="icon-btn danger" onclick="openDeleteConfirm(<?php echo $job['job_post_id']; ?>)">
                      <svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M2.5 4h10M5 4V2.5h5V4M6 7v4M9 7v4M3.5 4l.5 8.5h7L12 4" stroke="currentColor" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <span class="icon-tooltip">Delete</span>
                  </div>
                <?php endif; ?>

              </div>
            </div>
            <?php endforeach; ?>

          <?php else: ?>
            <div class="empty-state">
              <div class="empty-state-icon">
                <?php echo $filter_status === 'drafts' ? '✏️' : '📋'; ?>
              </div>
              <h3><?php echo $filter_status === 'drafts' ? 'No drafts saved yet' : 'No job postings yet'; ?></h3>
              <p><?php echo $filter_status === 'drafts'
                  ? 'Click "Create New Posting" and choose "Save as Draft" to save an incomplete posting.'
                  : 'Click "Create New Posting" above to publish your first job listing.'; ?></p>
            </div>
          <?php endif; ?>
        </div>

      </div><!-- /content-col -->
    </div><!-- /main-layout -->

  </div><!-- /page-wrapper -->

  <!-- ══ CREATE JOB MODAL ══ -->
  <div id="jobModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Create New Job Posting</h2>
        <button class="modal-close" onclick="closeModal('jobModal')">&times;</button>
      </div>
      <form method="POST" action="" id="createJobForm">
        <input type="hidden" name="action" value="create_job">
        <input type="hidden" name="save_as_draft" id="save_as_draft_flag" value="0">
        <div class="modal-body">
          <div class="draft-note">
            💡 <span>You can <strong>Save as Draft</strong> anytime — only a title is required. Come back to finish and publish later.</span>
          </div>
          <div class="form-group">
            <label class="form-label">Job Title *</label>
            <input type="text" name="job_title" class="input" required placeholder="e.g. Senior Software Engineer">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Category</label>
              <select name="job_category" class="select">
                <option value="">Select Category</option>
                <option value="IT">Information Technology</option>
                <option value="Healthcare">Healthcare</option>
                <option value="Education">Education</option>
                <option value="Finance">Finance & Banking</option>
                <option value="Retail">Retail & Sales</option>
                <option value="Manufacturing">Manufacturing</option>
                <option value="Construction">Construction</option>
                <option value="Food">Food & Hospitality</option>
                <option value="Transport">Transportation</option>
                <option value="Admin">Administration</option>
                <option value="Other">Other</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Work Type</label>
              <select name="job_type" class="select">
                <option value="">Select Type</option>
                <option value="Full-time">Full-time</option>
                <option value="Part-time">Part-time</option>
                <option value="Contract">Contract</option>
                <option value="Internship">Internship</option>
                <option value="Freelance">Freelance</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Job Description</label>
            <textarea name="job_description" class="textarea" placeholder="Describe responsibilities, requirements, and benefits…"></textarea>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Salary Range</label>
              <input type="text" name="salary_range" class="input" placeholder="e.g. ₱25,000 – ₱35,000">
            </div>
            <div class="form-group">
              <label class="form-label">Application Deadline</label>
              <input type="date" name="application_deadline" class="input" min="<?php echo date('Y-m-d'); ?>">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Location <span style="font-weight:400;color:var(--text-light);">(click map to pin)</span></label>
            <input type="text" name="location" class="input" id="jobLocationInput" placeholder="Enter address or click the map">
            <input type="hidden" name="location_lat" id="location_lat">
            <input type="hidden" name="location_lng" id="location_lng">
            <div id="locationMap" class="location-map"></div>
            <div class="location-hint">📍 <span id="locationHintText">Click anywhere on the map to pin a location</span></div>
          </div>
          <div class="form-group">
            <label class="form-label">Required Skills</label>
            <input type="text" name="required_skills" class="input" placeholder="e.g. HTML, CSS, JavaScript, PHP">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-modal-cancel" onclick="closeModal('jobModal')">Cancel</button>
          <button type="button" class="btn-modal-draft" onclick="submitAsDraft()">Save as Draft</button>
          <button type="button" class="btn-modal-submit" onclick="submitAsActive()">Post Job</button>
        </div>
      </form>
    </div>
  </div>

  <!-- ══ EDIT JOB MODAL ══ -->
  <div id="editJobModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Edit Job Posting</h2>
        <button class="modal-close" onclick="closeModal('editJobModal')">&times;</button>
      </div>
      <form method="POST" action="" id="editJobForm">
        <input type="hidden" name="action" value="update_job">
        <input type="hidden" name="job_id" id="edit_job_id">
        <input type="hidden" name="edit_save_as_draft" id="edit_save_as_draft_flag" value="0">
        <div class="modal-body">
          <div id="edit_draft_note" class="draft-note" style="display:none;">
            ✏️ <span>This is a <strong>draft</strong>. Fill in all fields and click <strong>Publish</strong> to make it live, or save progress as a draft.</span>
          </div>
          <div class="form-group">
            <label class="form-label">Job Title *</label>
            <input type="text" name="edit_job_title" id="edit_job_title" class="input" required>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Category</label>
              <select name="edit_job_category" id="edit_job_category" class="select">
                <option value="">Select Category</option>
                <option value="IT">Information Technology</option>
                <option value="Healthcare">Healthcare</option>
                <option value="Education">Education</option>
                <option value="Finance">Finance & Banking</option>
                <option value="Retail">Retail & Sales</option>
                <option value="Manufacturing">Manufacturing</option>
                <option value="Construction">Construction</option>
                <option value="Food">Food & Hospitality</option>
                <option value="Transport">Transportation</option>
                <option value="Admin">Administration</option>
                <option value="Other">Other</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Work Type</label>
              <select name="edit_job_type" id="edit_job_type" class="select">
                <option value="">Select Type</option>
                <option value="Full-time">Full-time</option>
                <option value="Part-time">Part-time</option>
                <option value="Contract">Contract</option>
                <option value="Internship">Internship</option>
                <option value="Freelance">Freelance</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Job Description</label>
            <textarea name="edit_job_description" id="edit_job_description" class="textarea"></textarea>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Salary Range</label>
              <input type="text" name="edit_salary_range" id="edit_salary_range" class="input">
            </div>
            <div class="form-group">
              <label class="form-label">Application Deadline</label>
              <input type="date" name="edit_application_deadline" id="edit_application_deadline" class="input" min="<?php echo date('Y-m-d'); ?>">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Location</label>
            <input type="text" name="edit_location" id="edit_location" class="input">
          </div>
          <div class="form-group">
            <label class="form-label">Required Skills</label>
            <input type="text" name="edit_required_skills" id="edit_required_skills" class="input">
          </div>
        </div>
        <div class="modal-footer" id="edit_modal_footer">
          <button type="button" class="btn-modal-cancel" onclick="closeModal('editJobModal')">Cancel</button>
          <!-- Buttons injected by JS based on draft status -->
        </div>
      </form>
    </div>
  </div>

  <!-- ══ CONFIRM PUBLISH MODAL ══ -->
  <div id="confirmPublishModal" class="modal">
    <div class="modal-content narrow">
      <div class="modal-header">
        <h2>Publish Draft?</h2>
        <button class="modal-close" onclick="closeModal('confirmPublishModal')">&times;</button>
      </div>
      <div class="modal-body">
        <p style="color:var(--text-mid);margin:0;font-size:0.9rem;">This will make the job posting live and visible to job seekers.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-modal-cancel" onclick="closeModal('confirmPublishModal')">Cancel</button>
        <button type="button" class="btn-modal-submit" onclick="confirmPublishJob()">Publish Now</button>
      </div>
    </div>
  </div>

  <!-- ══ CONFIRM CLOSE MODAL ══ -->
  <div id="confirmCloseModal" class="modal">
    <div class="modal-content narrow">
      <div class="modal-header">
        <h2>Close Job Posting?</h2>
        <button class="modal-close" onclick="closeModal('confirmCloseModal')">&times;</button>
      </div>
      <div class="modal-body">
        <p style="color:var(--text-mid);margin:0;font-size:0.9rem;">Are you sure you want to close this job posting? Applicants will no longer be able to apply.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-modal-cancel" onclick="closeModal('confirmCloseModal')">Cancel</button>
        <button type="button" class="btn-modal-submit" onclick="confirmCloseJob()">Close Posting</button>
      </div>
    </div>
  </div>

  <!-- ══ CONFIRM DELETE MODAL ══ -->
  <div id="confirmDeleteModal" class="modal">
    <div class="modal-content narrow">
      <div class="modal-header">
        <h2>Delete Job Posting?</h2>
        <button class="modal-close" onclick="closeModal('confirmDeleteModal')">&times;</button>
      </div>
      <div class="modal-body">
        <div class="warning-text">⚠️ This action cannot be undone.</div>
        <p style="color:var(--text-mid);margin:0;font-size:0.9rem;">The job posting and all related data will be permanently removed.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-modal-cancel" onclick="closeModal('confirmDeleteModal')">Cancel</button>
        <button type="button" class="btn-modal-danger" onclick="confirmDeleteJob()">Delete Permanently</button>
      </div>
    </div>
  </div>

  <!-- ══ SCRIPTS ══ -->
  <script>
    /* ─── Location map ─── */
    let jobLocationMap = null, jobLocationMarker = null;
    let jobLocationDebounceTimer = null, jobLocationRequestId = 0;
    let pendingLocationCoords = null;

    function getJobLocationElements() {
      return {
        input: document.getElementById('jobLocationInput'),
        lat:   document.getElementById('location_lat'),
        lng:   document.getElementById('location_lng'),
        map:   document.getElementById('locationMap'),
        hint:  document.getElementById('locationHintText')
      };
    }

    function setJobLocationCoordinates(lat, lng) {
      const el = getJobLocationElements();
      if (el.lat) el.lat.value = (lat !== null && lat !== undefined) ? String(lat) : '';
      if (el.lng) el.lng.value = (lng !== null && lng !== undefined) ? String(lng) : '';
    }

    function ensureJobLocationMap() {
      if (!window.L || jobLocationMap) return;
      const el = getJobLocationElements();
      if (!el.map) return;
      jobLocationMap = L.map('locationMap', { zoomControl: true, scrollWheelZoom: false }).setView([14.0728, 120.6339], 13);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors' }).addTo(jobLocationMap);
      jobLocationMap.on('click', handleJobLocationMapClick);
    }

    async function handleJobLocationMapClick(e) {
      const { lat, lng } = e.latlng;
      pendingLocationCoords = { lat, lng };
      if (!jobLocationMarker) {
        jobLocationMarker = L.marker([lat, lng]).addTo(jobLocationMap);
      } else {
        jobLocationMarker.setLatLng([lat, lng]);
      }
      jobLocationMarker.bindPopup('<div class="location-confirm-popup"><h4>Fetching address…</h4></div>', { closeButton: false, closeOnClick: false, autoClose: false, className: 'location-popup-wrapper' }).openPopup();
      try {
        const res = await fetch('./geocode-location.php?lat=' + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng));
        const data = res.ok ? await res.json() : null;
        showLocationConfirmPopup(lat, lng, (data && data.success && data.data && data.data.display_name) ? data.data.display_name : 'Address not found');
      } catch (err) {
        showLocationConfirmPopup(lat, lng, 'Could not retrieve address');
      }
    }

    function showLocationConfirmPopup(lat, lng, address) {
      const html = `<div class="location-confirm-popup">
        <h4>Save This Location?</h4>
        <div class="popup-address">${address}</div>
        <div class="popup-actions">
          <button class="popup-btn popup-btn-cancel" onclick="cancelLocationSelection()">Cancel</button>
          <button class="popup-btn popup-btn-save" onclick="confirmLocationSelection()">Save</button>
        </div>
      </div>`;
      if (jobLocationMarker) jobLocationMarker.setPopupContent(html);
    }

    function confirmLocationSelection() {
      if (!pendingLocationCoords) return;
      const el = getJobLocationElements();
      const addrEl = document.querySelector('.location-confirm-popup .popup-address');
      setJobLocationCoordinates(pendingLocationCoords.lat, pendingLocationCoords.lng);
      if (el.input && addrEl) el.input.value = addrEl.textContent;
      if (el.hint) { el.hint.textContent = 'Location selected ✓'; el.hint.style.color = '#1a7a46'; }
      cancelLocationSelection();
    }

    function cancelLocationSelection() {
      if (jobLocationMarker) jobLocationMarker.closePopup();
      pendingLocationCoords = null;
    }

    async function scheduleJobLocationGeocode() {
      clearTimeout(jobLocationDebounceTimer);
      jobLocationDebounceTimer = setTimeout(runJobLocationGeocode, 500);
    }

    async function runJobLocationGeocode() {
      const el = getJobLocationElements();
      if (!el.input) return;
      const query = el.input.value.trim();
      if (!query) { setJobLocationCoordinates('', ''); return; }
      const rid = ++jobLocationRequestId;
      try {
        const res = await fetch('./geocode-location.php?location=' + encodeURIComponent(query));
        if (!res.ok) return;
        const data = await res.json();
        if (rid !== jobLocationRequestId || !data.success || !data.data) return;
        const lat = data.data.lat, lng = data.data.lng;
        if (isNaN(lat) || isNaN(lng)) return;
        ensureJobLocationMap();
        if (!jobLocationMarker) {
          jobLocationMarker = L.marker([lat, lng]).addTo(jobLocationMap);
        } else {
          jobLocationMarker.setLatLng([lat, lng]);
        }
        jobLocationMap.setView([lat, lng], 16, { animate: true });
        setJobLocationCoordinates(lat, lng);
      } catch (e) {}
    }

    /* ─── Create form submission ─── */
    function submitAsDraft() {
      document.getElementById('save_as_draft_flag').value = '1';
      document.getElementById('createJobForm').submit();
    }

    function submitAsActive() {
      document.getElementById('save_as_draft_flag').value = '0';
      const el = getJobLocationElements();
      const lat = el.lat ? el.lat.value.trim() : '';
      const lng = el.lng ? el.lng.value.trim() : '';
      if (!lat || !lng) {
        alert('Please pin a location on the map before posting the job.');
        return;
      }
      document.getElementById('createJobForm').submit();
    }

    /* ─── Edit form submission ─── */
    function submitEditAsDraft() {
      document.getElementById('edit_save_as_draft_flag').value = '1';
      document.getElementById('editJobForm').submit();
    }

    function submitEditAsActive() {
      document.getElementById('edit_save_as_draft_flag').value = '0';
      document.getElementById('editJobForm').submit();
    }

    /* ─── Modal logic ─── */
    function openModal(id) {
      const m = document.getElementById(id);
      if (!m) return;
      m.classList.add('active');
      document.body.style.overflow = 'hidden';
      if (id === 'jobModal') {
        ensureJobLocationMap();
        setTimeout(() => { if (jobLocationMap) jobLocationMap.invalidateSize(); }, 250);
      }
    }

    function closeModal(id) {
      const m = document.getElementById(id);
      if (!m) return;
      m.classList.remove('active');
      document.body.style.overflow = '';
    }

    function openEditModal(jobId, title, category, description, salary, location, workType, skills, deadline, status) {
      document.getElementById('edit_job_id').value               = jobId;
      document.getElementById('edit_job_title').value            = title;
      document.getElementById('edit_job_category').value         = category;
      document.getElementById('edit_job_description').value      = description;
      document.getElementById('edit_salary_range').value         = salary;
      document.getElementById('edit_location').value             = location;
      document.getElementById('edit_job_type').value             = workType;
      document.getElementById('edit_required_skills').value      = skills;
      document.getElementById('edit_application_deadline').value = deadline;

      const isDraft = (status === 'draft');
      document.getElementById('edit_draft_note').style.display = isDraft ? '' : 'none';

      // Rebuild footer buttons
      const footer = document.getElementById('edit_modal_footer');
      // Remove all but Cancel
      Array.from(footer.querySelectorAll('button:not(.btn-modal-cancel)')).forEach(b => b.remove());

      if (isDraft) {
        // Save draft progress + Publish buttons
        const draftBtn = document.createElement('button');
        draftBtn.type = 'button';
        draftBtn.className = 'btn-modal-draft';
        draftBtn.textContent = 'Save Draft';
        draftBtn.onclick = submitEditAsDraft;
        footer.appendChild(draftBtn);

        const publishBtn = document.createElement('button');
        publishBtn.type = 'button';
        publishBtn.className = 'btn-modal-submit';
        publishBtn.textContent = 'Publish Now';
        publishBtn.onclick = submitEditAsActive;
        footer.appendChild(publishBtn);
      } else {
        const updateBtn = document.createElement('button');
        updateBtn.type = 'button';
        updateBtn.className = 'btn-modal-submit';
        updateBtn.textContent = 'Update Job';
        updateBtn.onclick = submitEditAsActive;
        footer.appendChild(updateBtn);
      }

      openModal('editJobModal');
    }

    /* ─── Confirm actions ─── */
    let pendingJobId = null;

    function openCloseConfirm(id)   { pendingJobId = id; openModal('confirmCloseModal'); }
    function openDeleteConfirm(id)  { pendingJobId = id; openModal('confirmDeleteModal'); }
    function openPublishConfirm(id) { pendingJobId = id; openModal('confirmPublishModal'); }

    function confirmCloseJob()   { if (pendingJobId) document.getElementById('closeForm_'   + pendingJobId)?.submit(); }
    function confirmDeleteJob()  { if (pendingJobId) document.getElementById('deleteForm_'  + pendingJobId)?.submit(); }
    function confirmPublishJob() { if (pendingJobId) document.getElementById('publishForm_' + pendingJobId)?.submit(); }

    /* ─── Init ─── */
    document.addEventListener('DOMContentLoaded', function () {
      ensureJobLocationMap();

      const el = getJobLocationElements();
      if (el.input) {
        el.input.addEventListener('input', function () {
          setJobLocationCoordinates('', '');
          scheduleJobLocationGeocode();
        });
      }

      document.querySelectorAll('.modal').forEach(m => {
        m.addEventListener('click', function (e) {
          if (e.target === this) closeModal(this.id);
        });
      });

      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') document.querySelectorAll('.modal.active').forEach(m => closeModal(m.id));
      });

      const alert = document.getElementById('success-alert');
      if (alert) {
        setTimeout(() => {
          alert.style.opacity = '0';
          alert.style.transform = 'translateY(-6px)';
          setTimeout(() => alert.remove(), 400);
        }, 3000);
      }
    });

    /* ─── Navbar scroll detection ─── */
    const navbar = document.querySelector('.navbar');
    window.addEventListener('scroll', () => {
      if (window.scrollY > 20) {
        navbar.classList.add('scrolled');
      } else {
        navbar.classList.remove('scrolled');
      }
    });
  </script>

  <!-- ══ FOOTER ══ -->
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