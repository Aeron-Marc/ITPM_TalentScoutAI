<?php 
session_start();
require_once('../../../database/db.php');

if (!isset($_SESSION['employer_id'])) {
  header('Location: ../../login.php');
  exit;
}

$conn = getConnection();
$employer_id = (int)$_SESSION['employer_id'];

$search_query = trim($_GET['search'] ?? '');
$filter_skill = $_GET['skill'] ?? '';
$filter_experience = $_GET['experience'] ?? '';

$where_conditions = ["e.is_active = 1"];
$params = [];
$types = "";

if (!empty($search_query)) {
  $where_conditions[] = "(e.first_name LIKE ? OR e.last_name LIKE ? OR r.summary LIKE ? OR EXISTS (SELECT 1 FROM resume_skills rs JOIN resumes r2 ON rs.resume_id = r2.resume_id WHERE r2.employee_id = e.employee_id AND rs.skill_name LIKE ?))";
  $search_param = "%{$search_query}%";
  $params[] = $search_param;
  $params[] = $search_param;
  $params[] = $search_param;
  $params[] = $search_param;
  $types .= "ssss";
}

if (!empty($filter_skill)) {
  $where_conditions[] = "EXISTS (SELECT 1 FROM resume_skills rs JOIN resumes r2 ON rs.resume_id = r2.resume_id WHERE r2.employee_id = e.employee_id AND rs.skill_name = ?)";
  $params[] = $filter_skill;
  $types .= "s";
}

$where_clause = implode(" AND ", $where_conditions);

$employees = [];
$sql = "SELECT DISTINCT
  e.employee_id,
  e.first_name,
  e.last_name,
  e.address,
  IFNULL(r.summary, 'No summary provided') as summary,
  IFNULL(r.resume_id, 0) as resume_id
FROM employee e
LEFT JOIN resumes r ON e.employee_id = r.employee_id
WHERE $where_clause
ORDER BY e.first_name ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $employees[] = $row;
}
$stmt->close();

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
    
    $stmt = $conn->prepare("SELECT job_title, company_name FROM employee_experience WHERE resume_id = ? ORDER BY start_date DESC LIMIT 3");
    $stmt->bind_param("i", $emp['resume_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $experiences = [];
    while ($row = $result->fetch_assoc()) {
      if (!empty($row['job_title'])) {
        $experiences[] = $row;
      }
    }
    $stmt->close();
    $emp['experiences'] = $experiences;
    
    $stmt = $conn->prepare("SELECT degree, school FROM employee_education WHERE resume_id = ? ORDER BY start_date DESC LIMIT 2");
    $stmt->bind_param("i", $emp['resume_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $educations = [];
    while ($row = $result->fetch_assoc()) {
      if (!empty($row['degree'])) {
        $educations[] = $row;
      }
    }
    $stmt->close();
    $emp['educations'] = $educations;
  } else {
    $emp['skills'] = [];
    $emp['experiences'] = [];
    $emp['educations'] = [];
  }
}

$all_skills = [];
$stmt = $conn->query("SELECT DISTINCT skill_name FROM resume_skills ORDER BY skill_name LIMIT 50");
while ($row = $stmt->fetch_assoc()) {
  $all_skills[] = $row['skill_name'];
}
$stmt->close();

$total_count = 0;
$stmt = $conn->query("SELECT COUNT(*) as cnt FROM employee WHERE is_active = 1");
if ($row = $stmt->fetch_assoc()) {
  $total_count = $row['cnt'];
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Employee Finder — TalentScout AI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --mint:        #e8f5ee;
      --mint-mid:    #c8e6d4;
      --mint-deep:   #a8d4b8;
      --sage:        #5a8a68;
      --sage-dark:   #3d6b50;
      --sage-deeper: #2d5040;
      --gold:        #c8a46a;
      --gold-pale:   #f5ead8;
      --gold-light:  #f0ddb8;
      --cream:       #fdfaf5;
      --cream-mid:   #f7f2ea;
      --cream-warm:  #f0ead8;
      --warm-tan:    #e8dfc8;
      --charcoal:    #2c3028;
      --text-mid:    #4a5244;
      --text-soft:   #7a8270;
      --text-pale:   #a8b0a0;
      --white:       #ffffff;
      --shadow-soft: 0 4px 24px rgba(60,80,50,0.08);
      --shadow-med:  0 8px 40px rgba(60,80,50,0.12);
      --shadow-lift: 0 20px 60px rgba(60,80,50,0.16);
      --radius-xl:   28px;
      --radius-lg:   18px;
      --radius-md:   12px;
      --radius-sm:   8px;
      --radius-pill: 999px;
      --ease:        cubic-bezier(0.22, 1, 0.36, 1);
    }

    html { scroll-behavior: smooth; }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: var(--cream);
      color: var(--charcoal);
      min-height: 100vh;
      overflow-x: hidden;
    }

    a { text-decoration: none; color: inherit; }

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

    .navbar.scrolled .nav-logo { color: #fff; }

    .nav-logo-mark {
      width: 36px; height: 36px;
      background: rgba(255,255,255,0.25);
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 0.7rem; font-weight: 700; color: #fff; letter-spacing: 0.05em;
    }

    .nav-logo em { font-style: italic; color: rgba(255,255,255,0.8); transition: color 0.4s; }
    .navbar.scrolled .nav-logo em { color: rgba(255,255,255,0.8); }

    .nav-links { display: flex; list-style: none; gap: 0.2rem; margin: 0; padding: 0; }

    .nav-links a {
      padding: 0.2rem 0.75rem;
      font-size: 0.84rem; font-weight: 500; color: rgba(255,255,255,0.8);
      transition: color 0.2s, border-bottom 0.2s;
      position: relative;
      padding-bottom: 0.4rem;
    }

    .navbar.scrolled .nav-links a { color: rgba(255,255,255,0.8); }

    .nav-links a:hover { color: #fff; font-weight: 600; }

    .nav-links a.active { color: #fff; font-weight: 600; border-bottom: 2.5px solid #fff; }

    .navbar.scrolled .nav-links a:hover { color: #fff; }
    .navbar.scrolled .nav-links a.active { color: #fff; border-bottom-color: #fff; }

    .nav-right { display: flex; align-items: center; gap: 0.65rem; }

    .nav-user { font-size: 0.82rem; color: rgba(255,255,255,0.75); transition: color 0.4s; }
    .navbar.scrolled .nav-user { color: rgba(255,255,255,0.75); }

    .btn-ghost {
      padding: 0.42rem 1.1rem; border-radius: var(--radius-pill);
      border: 1.5px solid rgba(255,255,255,0.3); color: #fff;
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 0.83rem; font-weight: 500; background: transparent;
      cursor: pointer; transition: all 0.2s; display: inline-block;
    }

    .navbar.scrolled .btn-ghost { border-color: rgba(255,255,255,0.3); color: #fff; }
    .btn-ghost:hover { background: rgba(255,255,255,0.15); color: #fff; }
    .navbar.scrolled .btn-ghost:hover { background: rgba(255,255,255,0.2); color: #fff; }

    .btn-solid {
      padding: 0.46rem 1.25rem; border-radius: var(--radius-pill);
      background: rgba(255,255,255,0.2);
      color: #fff; font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 0.83rem; font-weight: 700; border: 1.5px solid rgba(255,255,255,0.4);
      cursor: pointer; display: inline-flex; align-items: center; gap: 0.3rem;
      transition: all 0.25s var(--ease);
    }

    .btn-solid:hover { background: rgba(255,255,255,0.3); border-color: rgba(255,255,255,0.5); }

    .hamburger {
      display: none; flex-direction: column; gap: 5px;
      cursor: pointer; padding: 6px; background: none; border: none;
    }

    .hamburger span {
      display: block; width: 22px; height: 2px;
      background: #fff; border-radius: 2px;
      transition: all 0.3s var(--ease);
    }

    .hamburger.open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
    .hamburger.open span:nth-child(2) { opacity: 0; transform: scaleX(0); }
    .hamburger.open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

    .mobile-nav {
      position: fixed; top: 66px; left: 0; right: 0;
      background: rgba(253,250,245,0.97);
      backdrop-filter: blur(24px);
      border-bottom: 1px solid rgba(90,138,104,0.1);
      padding: 1.5rem 2rem; z-index: 190;
      display: flex; flex-direction: column; gap: 0.3rem;
      transform: translateY(-130%); opacity: 0;
      transition: transform 0.4s var(--ease), opacity 0.3s;
    }

    .mobile-nav.open { transform: translateY(0); opacity: 1; }

    .mobile-nav a {
      padding: 0.75rem 1rem; border-radius: var(--radius-md);
      font-size: 0.95rem; font-weight: 500; color: var(--text-mid);
      transition: background 0.2s, color 0.2s;
    }

    .mobile-nav a:hover { background: var(--mint); color: var(--sage-dark); }

    .mobile-nav-actions {
      display: flex; gap: 0.6rem; margin-top: 0.8rem;
      padding-top: 1rem; border-top: 1px solid rgba(90,138,104,0.1);
    }

    /* ══ PAGE WRAPPER ══ */
    .page-wrapper { flex: 1 0 auto; padding-top: 66px; }

    /* ══ HERO ══ */
    .page-hero {
      background: linear-gradient(135deg, #f0fff8 0%, #e8f8f0 60%, #f5fdf8 100%);
      border-bottom: 1px solid rgba(90,138,104,0.12);
      padding: 2.5rem 2.5rem 2rem;
    }

    .page-hero-inner { max-width: 1200px; margin: 0 auto; }

    .page-hero h1 {
      font-family: 'Lora', serif;
      font-size: 1.9rem; font-weight: 700;
      color: var(--charcoal);
      margin: 0 0 0.3rem 0;
    }

    .page-hero p { font-size: 0.92rem; color: var(--text-soft); margin: 0; }

    /* ══ MAIN LAYOUT ══ */
    .main-layout {
      max-width: 1200px; margin: 0 auto;
      padding: 2rem 2.5rem 3rem;
      display: grid; grid-template-columns: 260px 1fr;
      gap: 2rem; align-items: start;
    }

    /* ══ SIDEBAR ══ */
    .sidebar { position: sticky; top: 82px; }

    .sidebar-card {
      background: white; border: 1px solid rgba(90,138,104,0.13);
      border-radius: 14px; overflow: hidden; margin-bottom: 1rem;
      box-shadow: 0 2px 10px rgba(0,0,0,0.04);
    }

    .sidebar-title {
      font-weight: 700; font-size: 0.72rem;
      color: var(--text-soft); padding: 0.85rem 1rem 0.6rem;
      text-transform: uppercase; letter-spacing: 0.08em;
      border-bottom: 1px solid rgba(90,138,104,0.1);
      background: #fafcfb;
    }

    .filter-item {
      display: flex; align-items: center; justify-content: space-between;
      padding: 0.62rem 1rem; font-size: 0.85rem; color: var(--text-mid);
      text-decoration: none; transition: background 0.15s;
      border-bottom: 1px solid rgba(0,0,0,0.03);
    }

    .filter-item:last-child { border-bottom: none; }
    .filter-item:hover { background: rgba(90,138,104,0.05); }

    .filter-item.active-filter { background: rgba(90,138,104,0.08); color: var(--sage-dark); font-weight: 600; }

    .filter-left { display: flex; align-items: center; gap: 0.55rem; }

    .fcheck {
      width: 17px; height: 17px;
      border: 1.5px solid rgba(90,138,104,0.3);
      border-radius: 5px; display: flex; align-items: center; justify-content: center;
      font-size: 0.6rem; flex-shrink: 0; color: transparent;
    }

    .fcheck.on { background: var(--sage-dark); border-color: var(--sage-dark); color: white; }

    .fcount {
      background: rgba(90,138,104,0.1); color: var(--sage-dark);
      padding: 0.08rem 0.5rem; border-radius: 100px;
      font-size: 0.72rem; font-weight: 600;
    }

    /* ══ CONTENT COL ══ */
    .content-col { display: flex; flex-direction: column; gap: 1.25rem; min-width: 0; }

    .toolbar {
      display: flex; align-items: center; justify-content: space-between;
      gap: 1rem; flex-wrap: wrap;
    }

    .toolbar-label { font-size: 0.9rem; color: var(--text-soft); }
    .toolbar-label strong { color: var(--charcoal); }

    /* ══ SEARCH BAR ══ */
    .search-bar-wrap {
      background: white; border-bottom: 1px solid rgba(90,138,104,0.1);
      padding: 1.25rem 2.5rem;
    }

    .search-bar {
      max-width: 1200px; margin: 0 auto;
      display: flex; gap: 0.75rem; align-items: center;
    }

    .search-icon {
      position: absolute; left: 1rem; top: 50%;
      transform: translateY(-50%); color: var(--text-soft);
      font-size: 0.9rem; pointer-events: none; z-index: 1;
    }

    .search-input-rel { position: relative; flex: 2; }

    .search-input-rel .input {
      padding-left: 2.5rem; width: 100%;
      padding: 0.6rem 0.85rem; padding-left: 2.5rem;
      border: 1.5px solid rgba(90,138,104,0.2); border-radius: 9px;
      font-size: 0.875rem; font-family: inherit;
      color: var(--charcoal); background: white;
      transition: border-color 0.2s, box-shadow 0.2s;
    }

    .search-input-rel .input:focus {
      outline: none; border-color: var(--sage);
      box-shadow: 0 0 0 3px rgba(90,138,104,0.1);
    }

    .btn-search {
      padding: 0.6rem 1.3rem; border-radius: 9px;
      background: linear-gradient(135deg, var(--sage), var(--sage-dark));
      color: #fff; font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 0.875rem; font-weight: 700; border: none;
      cursor: pointer; transition: all 0.2s;
      box-shadow: 0 3px 10px rgba(90,138,104,0.3);
    }

    .btn-search:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(90,138,104,0.38); }

    /* ══ CANDIDATE CARDS ══ */
    .candidate-card {
      background: white; border: 1px solid rgba(90,138,104,0.13);
      border-radius: 14px; padding: 1.3rem 1.5rem;
      display: flex; align-items: center; gap: 1.25rem;
      transition: box-shadow 0.2s, transform 0.2s, border-color 0.2s;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }

    .candidate-card:hover {
      box-shadow: 0 6px 24px rgba(90,138,104,0.12);
      transform: translateY(-2px);
      border-color: rgba(90,138,104,0.25);
    }

    .candidate-icon {
      width: 46px; height: 46px; border-radius: 12px;
      background: linear-gradient(135deg, rgba(90,138,104,0.12), rgba(90,138,104,0.2));
      display: flex; align-items: center; justify-content: center;
      font-size: 1.3rem; flex-shrink: 0;
    }

    .candidate-info { flex: 1; min-width: 0; }

    .candidate-info h3 {
      font-size: 0.97rem; font-weight: 700;
      color: var(--charcoal); margin-bottom: 0.3rem;
    }

    .candidate-meta {
      display: flex; flex-wrap: wrap; gap: 0.6rem;
      font-size: 0.78rem; color: var(--text-soft);
    }

    .candidate-meta-item { display: flex; align-items: center; gap: 0.28rem; }

    .skills-list {
      display: flex; flex-wrap: wrap; gap: 0.5rem;
      margin-top: 0.75rem;
    }

    .skill-tag {
      background: rgba(90,138,104,0.08); color: var(--sage-dark);
      padding: 0.35rem 0.75rem; border-radius: 6px;
      font-size: 0.8rem; font-weight: 600;
      border: 1px solid rgba(90,138,104,0.15);
    }

    .action-buttons { display: flex; gap: 0.5rem; flex-shrink: 0; }

    .btn-small {
      padding: 0.5rem 1rem; border-radius: var(--radius-sm);
      font-size: 0.85rem; border: 1px solid rgba(90,138,104,0.2);
      cursor: pointer; transition: all 0.2s;
      background: white; color: var(--text-mid);
    }

    .btn-view {
      background: linear-gradient(135deg, var(--sage), var(--sage-dark));
      color: #fff; border-color: transparent;
      font-weight: 600;
    }

    .btn-view:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(90,138,104,0.3); }

    .btn-message {
      border-color: rgba(90,138,104,0.3); color: var(--sage-dark);
      text-decoration: none; display: inline-block;
    }

    .btn-message:hover { background: var(--mint); border-color: var(--sage); }

    /* ══ RESULTS HEADER ══ */
    .results-header {
      margin-bottom: 1.5rem; display: flex;
      justify-content: space-between; align-items: center;
    }

    .results-title {
      font-size: 1.1rem; font-weight: 600;
      color: var(--charcoal);
    }

    .results-count { font-size: 0.9rem; color: var(--text-soft); }

    /* ══ MODAL ══ */
    .modal {
      position: fixed; z-index: 1000;
      left: 0; top: 0; width: 100%; height: 100%;
      background: rgba(0,0,0,0.45);
      backdrop-filter: blur(4px);
      animation: fadeIn 0.2s ease;
      display: none !important;
      align-items: center; justify-content: center;
    }

    .modal.active { display: flex !important; }

    .modal-content {
      background: white; border-radius: 18px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.18);
      width: 90%; max-width: 600px;
      max-height: 90vh; overflow-y: auto; position: relative;
      animation: slideUp 0.3s cubic-bezier(0.22, 1, 0.36, 1);
    }

    .modal-header {
      padding: 1.5rem 1.75rem 1.25rem;
      border-bottom: 1px solid rgba(90,138,104,0.12);
      display: flex; justify-content: space-between; align-items: center;
      background: linear-gradient(135deg, #f8fffc, #f0fff8);
      border-radius: 18px 18px 0 0;
    }

    .modal-header h2 {
      font-family: 'Lora', serif;
      font-size: 1.2rem; font-weight: 700;
      color: var(--charcoal); margin: 0;
    }

    .modal-close {
      background: none; border: none;
      width: 32px; height: 32px;
      display: flex; align-items: center; justify-content: center;
      border-radius: 8px; cursor: pointer;
      color: var(--text-soft); transition: all 0.15s;
      font-size: 1.4rem; padding: 0;
    }

    .modal-close:hover { background: rgba(0,0,0,0.06); color: var(--charcoal); }

    .modal-body { padding: 1.5rem 1.75rem; }

    .modal-footer {
      padding: 1.25rem 1.75rem;
      border-top: 1px solid rgba(90,138,104,0.1);
      display: flex; gap: 0.75rem;
      justify-content: flex-end; align-items: center; flex-wrap: wrap;
    }

    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes slideUp { from { transform: translateY(24px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

    /* ══ FORMS ══ */
    .form-group { margin-bottom: 1.1rem; }

    .form-label {
      display: block; font-size: 0.83rem; font-weight: 600;
      color: var(--charcoal); margin-bottom: 0.4rem;
    }

    .input, .select, .textarea {
      width: 100%; padding: 0.6rem 0.85rem;
      border: 1.5px solid rgba(90,138,104,0.2);
      border-radius: 9px; font-size: 0.875rem;
      font-family: inherit; color: var(--charcoal);
      background: white; transition: border-color 0.2s, box-shadow 0.2s;
    }

    .input:focus, .select:focus, .textarea:focus {
      outline: none; border-color: var(--sage);
      box-shadow: 0 0 0 3px rgba(90,138,104,0.1);
    }

    .textarea { resize: vertical; min-height: 90px; }

    .btn-modal-cancel {
      background: #f0f0f0; color: var(--text-mid);
      border: none; padding: 0.6rem 1.3rem;
      border-radius: 9px; font-size: 0.875rem;
      font-weight: 600; cursor: pointer;
      font-family: inherit; transition: background 0.15s;
    }

    .btn-modal-cancel:hover { background: #e2e2e2; }

    .btn-modal-submit {
      padding: 0.46rem 1.25rem; border-radius: var(--radius-pill);
      background: rgba(255,255,255,0.2);
      color: #fff; font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 0.83rem; font-weight: 700;
      border: 1.5px solid rgba(255,255,255,0.4);
      cursor: pointer; display: inline-flex; align-items: center; gap: 0.3rem;
      transition: all 0.25s var(--ease);
    }

    .btn-modal-submit:hover { background: rgba(255,255,255,0.3); border-color: rgba(255,255,255,0.5); }

    /* ══ EMPTY STATE ══ */
    .empty-state {
      background: white; border: 1.5px dashed rgba(90,138,104,0.25);
      border-radius: 14px; padding: 3rem 2rem; text-align: center;
    }

    .empty-state-icon { font-size: 2.5rem; margin-bottom: 1rem; }

    .empty-state h3 { font-size: 1rem; font-weight: 700; color: var(--charcoal); margin: 0 0 0.4rem 0; }

    .empty-state p { font-size: 0.85rem; color: var(--text-soft); margin: 0; }

    /* ══ FOOTER ══ */
    .footer { background: var(--charcoal); color: rgba(255,255,255,0.5); padding: 4.5rem 2.5rem 2rem; }

    .footer-inner { max-width: 1120px; margin: 0 auto; }

    .footer-top {
      display: grid; grid-template-columns: 2fr 1fr 1fr 1fr;
      gap: 3rem; padding-bottom: 3rem;
      border-bottom: 1px solid rgba(255,255,255,0.07); margin-bottom: 2rem;
    }

    .footer-brand h3 { font-family: 'Lora', serif; font-size: 1.1rem; font-weight: 700; color: #fff; margin-bottom: 0.75rem; }
    .footer-brand p { font-size: 0.82rem; line-height: 1.72; color: rgba(255,255,255,0.4); }

    .footer-col h4 {
      font-size: 0.7rem; font-weight: 700; color: rgba(255,255,255,0.7);
      text-transform: uppercase; letter-spacing: 0.12em; margin-bottom: 1.1rem;
    }

    .footer-col ul { list-style: none; display: flex; flex-direction: column; gap: 0.55rem; }
    .footer-col ul a { font-size: 0.83rem; color: rgba(255,255,255,0.38); transition: color 0.2s; }
    .footer-col ul a:hover { color: var(--mint-deep); }

    .footer-bottom {
      display: flex; justify-content: space-between; align-items: center;
      font-size: 0.77rem; flex-wrap: wrap; gap: 0.5rem;
    }

    /* ══ RESPONSIVE ══ */
    @media (max-width: 1024px) {
      .main-layout { grid-template-columns: 220px 1fr; }
    }

    @media (max-width: 860px) {
      .footer-top { grid-template-columns: 1fr 1fr; }
      .nav-links { display: none; }
      .hamburger { display: flex; }
    }

    @media (max-width: 600px) {
      .navbar { padding: 0 1.3rem; }
      .page-hero { padding: 2rem 1.3rem 1.5rem; }
      .main-layout { grid-template-columns: 1fr; padding: 1.5rem 1.3rem; }
      .sidebar { position: static; }
      .footer-top { grid-template-columns: 1fr; gap: 2rem; }
      .footer-bottom { flex-direction: column; text-align: center; }
      .candidate-card { flex-direction: column; align-items: flex-start; }
      .action-buttons { width: 100%; }
    }
  </style>
</head>
<body>

  <!-- ══ NAVBAR ══ -->
  <nav class="navbar" id="navbar">
    <a href="../../index.php" class="nav-logo">
      <div class="nav-logo-mark">TS</div>
      <span>Talent<em>Scout</em> AI</span>
    </a>
    <ul class="nav-links">
      <li><a href="../../index.php">Home</a></li>
      <li><a href="../post-jobs/">Post Jobs</a></li>
      <li><a href="./" class="active">Find Talent</a></li>
      <li><a href="../applicant-tracking/">Hiring Pipeline</a></li>
      <li><a href="../chat-sms/">Messages</a></li>
    </ul>
    <div class="nav-right">
      <?php if (isset($_SESSION['employer_id'])): ?>
        <span class="nav-user">Welcome, <?php echo htmlspecialchars($_SESSION['employer_name'] ?? 'Employer'); ?></span>
        <a href="../../logout.php" class="btn-ghost">Logout</a>
      <?php else: ?>
        <a href="../../login.php" class="btn-ghost">Login</a>
        <a href="../../signup.php" class="btn-solid">Get Started →</a>
      <?php endif; ?>
      <button class="hamburger" id="hamburger" aria-label="Menu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </nav>

  <!-- Mobile Nav -->
  <div class="mobile-nav" id="mobileNav">
    <a href="../../index.php">🏠 Home</a>
    <a href="../post-jobs/">📋 Post Jobs</a>
    <a href="./">🔍 Find Talent</a>
    <a href="../applicant-tracking/">📊 Hiring Pipeline</a>
    <a href="../chat-sms/">💬 Messages</a>
    <div class="mobile-nav-actions">
      <?php if (isset($_SESSION['employer_id'])): ?>
        <a href="../../logout.php" class="btn-ghost">Logout</a>
      <?php else: ?>
        <a href="../../login.php" class="btn-ghost">Login</a>
        <a href="../../signup.php" class="btn-solid">Get Started →</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="page-wrapper">

    <!-- ══ PAGE HERO ══ -->
    <div class="page-hero">
      <div class="page-hero-inner">
        <h1>Find Talent</h1>
        <p>Search and filter candidates by skills, experience, and location</p>
      </div>
    </div>

    <!-- ══ SEARCH BAR ══ -->
    <div class="search-bar-wrap">
      <form method="GET" class="search-bar">
        <div class="search-input-rel">
          <span class="search-icon">🔍</span>
          <input type="text" name="search" class="input" placeholder="Search by skills, name, or summary..." value="<?php echo htmlspecialchars($search_query); ?>">
        </div>
        <button type="submit" class="btn-search">Search</button>
        <?php if (!empty($search_query) || !empty($filter_skill)): ?>
          <a href="?" class="btn-ghost" style="margin-left: 0.5rem;">Clear</a>
        <?php endif; ?>
      </form>
    </div>

    <!-- ══ MAIN LAYOUT ══ -->
    <div class="main-layout">

      <!-- SIDEBAR FILTERS -->
      <aside class="sidebar">
        <div class="sidebar-card">
          <div class="sidebar-title">Filter by Skill</div>
          <form method="GET">
            <?php if (!empty($search_query)): ?>
              <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
            <?php endif; ?>
            <select name="skill" class="select" style="width: 100%; margin-bottom: 1rem; padding: 0.6rem; border-radius: 6px; border: 1px solid rgba(90,138,104,0.2);" onchange="this.form.submit()">
              <option value="">All Skills</option>
              <?php foreach ($all_skills as $skill): ?>
                <option value="<?php echo htmlspecialchars($skill); ?>" <?php echo ($filter_skill === $skill) ? 'selected' : ''; ?>><?php echo htmlspecialchars($skill); ?></option>
              <?php endforeach; ?>
            </select>
          </form>
          <a href="?" class="filter-item" style="text-decoration: none; color: inherit; display: block; padding: 0.5rem; background: rgba(90,138,104,0.05); border-radius: 4px; text-align: center;">
            Clear All Filters
          </a>
        </div>

        <div class="sidebar-card">
          <div class="sidebar-title">Total Candidates</div>
          <div style="text-align: center; padding: 1rem;">
            <div style="font-size: 2rem; font-weight: 700; color: var(--sage-dark);"><?php echo $total_count; ?></div>
            <div style="font-size: 0.85rem; color: var(--text-soft);">Available in database</div>
          </div>
        </div>

        <div class="sidebar-card">
          <div class="sidebar-title">Quick Links</div>
          <a href="?search=JavaScript" class="filter-item" style="text-decoration: none; color: var(--text-mid); display: block; padding: 0.3rem 0;">
            <span>JavaScript</span>
          </a>
          <a href="?search=Python" class="filter-item" style="text-decoration: none; color: var(--text-mid); display: block; padding: 0.3rem 0;">
            <span>Python</span>
          </a>
          <a href="?search=React" class="filter-item" style="text-decoration: none; color: var(--text-mid); display: block; padding: 0.3rem 0;">
            <span>React</span>
          </a>
          <a href="?search=Management" class="filter-item" style="text-decoration: none; color: var(--text-mid); display: block; padding: 0.3rem 0;">
            <span>Management</span>
          </a>
        </div>
      </aside>

      <!-- RESULTS -->
      <div class="content-col">
        <div class="results-header">
          <div class="results-title">Talent Matches</div>
          <div class="results-count">
            <?php if (!empty($search_query) || !empty($filter_skill)): ?>
              Found <?php echo count($employees); ?> candidate(s)
            <?php else: ?>
              Showing <?php echo count($employees); ?> of <?php echo $total_count; ?> candidates
            <?php endif; ?>
          </div>
        </div>

        <?php 
          if (count($employees) > 0) {
            foreach ($employees as $emp) {
              $match_score = min(98, 70 + (count($emp['skills']) * 4));
        ?>
        <!-- Candidate Card -->
        <div class="candidate-card">
          <div class="candidate-icon">👤</div>
          <div class="candidate-info">
            <h3><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></h3>
            <div class="candidate-meta">
              <span class="candidate-meta-item">📍 <?php echo htmlspecialchars($emp['address']); ?></span>
            </div>
            <div class="skills-list">
              <?php foreach (array_slice($emp['skills'], 0, 4) as $skill): ?>
                <span class="skill-tag"><?php echo htmlspecialchars($skill); ?></span>
              <?php endforeach; ?>
              <?php if (count($emp['skills']) > 4): ?>
                <span class="skill-tag">+<?php echo count($emp['skills']) - 4; ?> more</span>
              <?php endif; ?>
            </div>
          </div>
          <div class="action-buttons">
            <button class="btn-small btn-view" onclick="viewProfile(<?php echo $emp['employee_id']; ?>)">View Profile</button>
            <a href="../chat-sms/?employee_id=<?php echo $emp['employee_id']; ?>" class="btn-small btn-message">Message</a>
          </div>
        </div>
        <?php } 
          } else { 
        ?>
        <div class="empty-state">
          <div class="empty-state-icon">📭</div>
          <h3>No candidates found</h3>
          <p>Try adjusting your search or filter criteria</p>
        </div>
        <?php } ?>
      </div>
    </div>
  </div>

  <!-- Candidate Profile Modal -->
  <div id="profileModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 id="profileModalTitle">Candidate Profile</h2>
        <button class="modal-close" onclick="closeModal('profileModal')">×</button>
      </div>
      <div class="modal-body" id="profileModalBody">
        Loading...
      </div>
      <div class="modal-footer">
        <button class="btn-modal-cancel" onclick="closeModal('profileModal')">Close</button>
        <button class="btn-modal-submit" id="messageBtn" onclick="">Message Candidate</button>
      </div>
    </div>
  </div>

  <!-- ══ FOOTER ══ -->
  <footer class="footer">
    <div class="footer-inner">
      <div class="footer-top">
        <div class="footer-brand">
          <h3>🌿 TalentScout AI</h3>
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

  <script>
    // Hamburger menu
    const ham = document.getElementById('hamburger');
    const mNav = document.getElementById('mobileNav');

    if (ham) {
      ham.addEventListener('click', () => {
        ham.classList.toggle('open');
        mNav.classList.toggle('open');
      });

      document.addEventListener('click', (e) => {
        if (!ham.contains(e.target) && !mNav.contains(e.target)) {
          ham.classList.remove('open');
          mNav.classList.remove('open');
        }
      });
    }

    // Navbar scroll
    const navbar = document.getElementById('navbar');
    window.addEventListener('scroll', () => {
      if (window.scrollY > 60) {
        navbar.classList.add('scrolled');
        navbar.style.boxShadow = '0 4px 24px rgba(60,80,50,0.1)';
      } else {
        navbar.classList.remove('scrolled');
        navbar.style.boxShadow = 'none';
      }
    }, { passive: true });

    // Modal functions
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

    // Candidate profile data
    var employeeData = {};

    <?php foreach ($employees as $emp): ?>
      employeeData[<?php echo $emp['employee_id']; ?>] = {
        employee_id: <?php echo $emp['employee_id']; ?>,
        first_name: '<?php echo addslashes($emp['first_name']); ?>',
        last_name: '<?php echo addslashes($emp['last_name']); ?>',
        summary: '<?php echo addslashes($emp['summary'] ?? 'No summary available.'); ?>',
        address: '<?php echo addslashes($emp['address'] ?? ''); ?>',
        skills: <?php echo json_encode($emp['skills'] ?? []); ?>,
        experiences: <?php echo json_encode($emp['experiences'] ?? []); ?>,
        educations: <?php echo json_encode($emp['educations'] ?? []); ?>
      };
    <?php endforeach; ?>

    function viewProfile(employeeId) {
      var emp = employeeData[employeeId];
      var modal = document.getElementById('profileModal');
      var modalBody = document.getElementById('profileModalBody');
      var messageBtn = document.getElementById('messageBtn');
      
      if (!emp) {
        modalBody.innerHTML = '<div style="text-align: center; padding: 2rem; color: red;">Employee not found</div>';
        messageBtn.style.display = 'none';
        openModal('profileModal');
        return;
      }
      
      openModal('profileModal');
      messageBtn.style.display = 'inline-flex';
      messageBtn.onclick = function() {
        window.location.href = '../chat-sms/?employee_id=' + emp.employee_id;
      };

      // Build skills HTML
      var skillsHtml = '';
      if (emp.skills && emp.skills.length > 0) {
        for (var i = 0; i < emp.skills.length; i++) {
          skillsHtml += '<span class="skill-tag" style="margin: 0.25rem;">' + emp.skills[i] + '</span>';
        }
      } else {
        skillsHtml = '<span style="color: #888;">No skills listed</span>';
      }

      // Build experience HTML
      var expHtml = '';
      if (emp.experiences && emp.experiences.length > 0) {
        expHtml = '<div style="margin-top: 1.5rem;"><h3 style="font-size: 1.1rem; color: var(--charcoal); margin-bottom: 0.75rem;">Experience</h3>';
        for (var j = 0; j < emp.experiences.length; j++) {
          var exp = emp.experiences[j];
          expHtml += '<div style="padding-left: 1rem; border-left: 2px solid var(--sage); margin-bottom: 1rem;">' +
            '<p style="margin: 0.5rem 0; font-weight: 600; color: var(--charcoal);">' + (exp.job_title || 'Position') + '</p>' +
            '<p style="margin: 0; font-size: 0.9rem; color: var(--text-soft);">' + (exp.company_name || 'Company') + '</p>' +
            '</div>';
        }
        expHtml += '</div>';
      }

      // Build education HTML
      var eduHtml = '';
      if (emp.educations && emp.educations.length > 0) {
        eduHtml = '<div style="margin-top: 1.5rem;"><h3 style="font-size: 1.1rem; color: var(--charcoal); margin-bottom: 0.75rem;">Education</h3>';
        for (var k = 0; k < emp.educations.length; k++) {
          var edu = emp.educations[k];
          eduHtml += '<div style="padding-left: 1rem; border-left: 2px solid var(--mint-deep); margin-bottom: 0.5rem;">' +
            '<p style="margin: 0.5rem 0; font-weight: 600; color: var(--charcoal);">' + (edu.degree || 'Degree') + '</p>' +
            '<p style="margin: 0; font-size: 0.9rem; color: var(--text-soft);">' + (edu.school || 'School') + '</p>' +
            '</div>';
        }
        eduHtml += '</div>';
      }

      modalBody.innerHTML = '<div style="margin-bottom: 1.5rem;">' +
        '<h3 style="font-size: 1.2rem; color: var(--charcoal); margin-bottom: 0.5rem;">' + emp.first_name + ' ' + emp.last_name + '</h3>' +
        '<p style="color: var(--text-soft); line-height: 1.6;">' + emp.summary + '</p>' +
        '<p style="color: var(--text-soft); font-size: 0.9rem; margin-top: 0.5rem;">📍 ' + emp.address + '</p>' +
        '</div>' +
        '<div style="margin-bottom: 1.5rem;">' +
        '<h3 style="font-size: 1.1rem; color: var(--charcoal); margin-bottom: 0.75rem;">Key Skills</h3>' +
        '<div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">' + skillsHtml + '</div>' +
        '</div>' +
        expHtml + eduHtml;
    }

    // Close modal on ESC
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        closeModal('profileModal');
      }
    });

    // Close modal on overlay click
    document.querySelectorAll('.modal').forEach(modal => {
      modal.addEventListener('click', (e) => {
        if (e.target === modal) {
          closeModal(modal.id);
        }
      });
    });
  </script>

</body>
</html>
