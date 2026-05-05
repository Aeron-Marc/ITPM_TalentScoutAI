<?php
session_start();
require_once __DIR__ . '/../../../database/db.php';

// Initialize database connection
$conn = getConnection();

// Check if user is logged in
// if (!isset($_SESSION['employee_id'])) {
//     header('Location: ../../login.php');
//     exit;
// }

// Get employee data
// Use test employee ID 1 for now, or session ID if logged in
$employee_id = isset($_SESSION['employee_id']) ? $_SESSION['employee_id'] : 1;
$employee_query = "SELECT e.first_name, e.last_name FROM employee e WHERE e.employee_id = ?";
$employee_stmt = $conn->prepare($employee_query);
$employee_stmt->bind_param("i", $employee_id);
$employee_stmt->execute();
$employee_result = $employee_stmt->get_result();
$employee_data = $employee_result->fetch_assoc();
$employee_name = isset($employee_data) ? $employee_data['first_name'] . ' ' . $employee_data['last_name'] : 'User';

// Get employee's skills from employee_skill table (more reliable than resume)
$employee_skills_query = "SELECT DISTINCT skill_name FROM employee_skill WHERE employee_id = ?";
$employee_skills_stmt = $conn->prepare($employee_skills_query);
$employee_skills_stmt->bind_param("i", $employee_id);
$employee_skills_stmt->execute();
$employee_skills_result = $employee_skills_stmt->get_result();

// Build employee skills array
$employee_skills = array();
while ($skill = $employee_skills_result->fetch_assoc()) {
    $employee_skills[] = strtolower(trim($skill['skill_name']));
}

// Get all job postings to analyze market demand for skills
$jobs_query = "SELECT skills FROM job_post WHERE skills IS NOT NULL AND skills != ''";
$jobs_result = $conn->query($jobs_query);

// Parse job posting skills and count frequency
$market_skills = array();
$total_jobs = 0;

while ($job = $jobs_result->fetch_assoc()) {
    $total_jobs++;
    $job_skills = explode(',', $job['skills']);
    foreach ($job_skills as $skill) {
        $skill_clean = strtolower(trim($skill));
        if (!empty($skill_clean)) {
            if (!isset($market_skills[$skill_clean])) {
                $market_skills[$skill_clean] = 0;
            }
            $market_skills[$skill_clean]++;
        }
    }
}

// Sort market skills by frequency (demand)
arsort($market_skills);

// Calculate skill gaps and current skills
$current_skills = array();
$skill_gaps = array();

foreach ($market_skills as $skill => $frequency) {
    if (in_array($skill, $employee_skills)) {
        $current_skills[$skill] = array(
            'name' => ucwords($skill),
            'proficiency' => 85 // Default proficiency for verified skills
        );
    } else {
        $skill_gaps[$skill] = array(
            'name' => ucwords($skill),
            'demand_count' => $frequency,
            'demand_percent' => round(($frequency / $total_jobs) * 100, 1)
        );
    }
}

// Calculate employability score
$total_market_skills = count($market_skills);
$matched_skills = count($current_skills);
$employability_score = $total_market_skills > 0 ? round(($matched_skills / $total_market_skills) * 100, 0) : 0;

// Take top 7 current skills and top 4 gaps for display
$current_skills_display = array_slice($current_skills, 0, 7);
$skill_gaps_display = array_slice($skill_gaps, 0, 4);
$critical_gaps_count = count($skill_gaps_display);

?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Skill Gap Analysis — TalentScout AI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900;1,700&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
      *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

      :root {
        --sand:        #f5f0e8;
        --sand-dark:   #ece5d5;
        --sage:        #6b8f71;
        --sage-light:  #9ab89f;
        --sage-pale:   #d4e6d6;
        --sage-deep:   #4a6b50;
        --stone:       #8a8070;
        --stone-light: #c4b9a8;
        --cream:       #faf8f3;
        --charcoal:    #2a2a22;
        --warm-mid:    #5a5448;
        --warm-light:  #9a9288;
        --gold:        #c8a96e;
        --gold-pale:   #f0e4c8;
        --white-t:     rgba(255,255,255,0.92);
        --radius-xl:   24px;
        --radius-lg:   16px;
        --radius-md:   10px;
        --radius-pill: 999px;
        --ease-out:    cubic-bezier(0.22, 1, 0.36, 1);
      }

      html { scroll-behavior: smooth; }

      body {
        font-family: 'DM Sans', sans-serif;
        background: var(--cream);
        color: var(--charcoal);
        min-height: 100vh;
        overflow-x: hidden;
        line-height: 1.6;
      }

      a { text-decoration: none; color: inherit; }

      /* ── GRAIN OVERLAY ── */
      body::before {
        content: '';
        position: fixed;
        inset: 0;
        pointer-events: none;
        z-index: 9999;
        opacity: 0.03;
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)'/%3E%3C/svg%3E");
      }

      /* ── NAVBAR ── */
      .navbar {
        position: fixed;
        top: 0; left: 0; right: 0;
        z-index: 100;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 3rem;
        height: 64px;
        background: rgba(250, 248, 243, 0.88);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-bottom: 1px solid rgba(139, 128, 112, 0.12);
        animation: slideDown 0.6s var(--ease-out) both;
      }

      @keyframes slideDown {
        from { transform: translateY(-100%); opacity: 0; }
        to   { transform: translateY(0); opacity: 1; }
      }

      .nav-logo {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        font-family: 'Playfair Display', serif;
        font-weight: 700;
        font-size: 1.15rem;
        color: var(--charcoal);
        letter-spacing: -0.01em;
      }

      .nav-logo-mark {
        width: 34px; height: 34px;
        background: var(--sage-deep);
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.72rem;
        font-weight: 600;
        color: #fff;
        letter-spacing: 0.04em;
      }

      .nav-logo em { font-style: italic; color: var(--sage); }

      .nav-links {
        display: flex;
        list-style: none;
        gap: 0.15rem;
      }

      .nav-links a {
        padding: 0.38rem 0.85rem;
        border-radius: var(--radius-pill);
        font-size: 0.84rem;
        font-weight: 400;
        color: var(--warm-mid);
        transition: background 0.2s, color 0.2s;
        letter-spacing: 0.01em;
      }

      .nav-links a:hover, .nav-links a.active {
        background: var(--sage-pale);
        color: var(--sage-deep);
      }

      .nav-right {
        display: flex; align-items: center; gap: 0.7rem;
      }

      .nav-user {
        font-size: 0.83rem;
        color: var(--warm-mid);
      }

      .btn-nav-ghost {
        padding: 0.4rem 1rem;
        border-radius: var(--radius-pill);
        border: 1px solid var(--stone-light);
        color: var(--warm-mid);
        font-family: 'DM Sans', sans-serif;
        font-size: 0.83rem;
        font-weight: 500;
        background: transparent;
        cursor: pointer;
        transition: border-color 0.2s, background 0.2s;
      }

      .btn-nav-ghost:hover { background: var(--sand); border-color: var(--stone); }

      .btn-nav-solid {
        padding: 0.44rem 1.2rem;
        border-radius: var(--radius-pill);
        background: var(--sage-deep);
        color: #fff;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.83rem;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: background 0.2s, transform 0.15s;
        display: flex; align-items: center; gap: 0.35rem;
      }

      .btn-nav-solid:hover { background: var(--sage); transform: translateY(-1px); }

      /* ── PAGE HEADER ── */
      .page-header {
        padding: 7.5rem 2.5rem 2rem;
        max-width: 1200px;
        margin: 0 auto;
        text-align: center;
      }

      .breadcrumb {
        font-size: 0.78rem;
        color: var(--warm-light);
        margin-bottom: 1rem;
      }

      .breadcrumb a {
        color: var(--sage);
        font-weight: 500;
        transition: color 0.2s;
      }

      .breadcrumb a:hover { color: var(--sage-deep); }

      .page-header h1 {
        font-family: 'Playfair Display', serif;
        font-size: clamp(1.8rem, 3.5vw, 2.6rem);
        font-weight: 900;
        color: var(--charcoal);
        letter-spacing: -0.025em;
        line-height: 1.2;
        margin-bottom: 0.7rem;
      }

      .page-header h1 em {
        font-style: italic;
        color: var(--sage);
      }

      .page-header p {
        font-size: 0.95rem;
        color: var(--warm-mid);
        max-width: 600px;
        margin: 0 auto;
        line-height: 1.7;
      }

      /* ── MAIN LAYOUT ── */
      .gap-layout {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 2.5rem 4rem;
        display: grid;
        grid-template-columns: 1fr 340px;
        gap: 2rem;
        align-items: start;
      }

      /* ── SUMMARY BANNER ── */
      .summary-banner {
        background: var(--sand);
        border: 1px solid rgba(139,128,112,0.12);
        border-radius: var(--radius-xl);
        padding: 2rem;
        display: flex;
        gap: 2rem;
        align-items: center;
        margin-bottom: 2rem;
        box-shadow: 0 4px 24px rgba(42,42,34,0.06);
      }

      .summary-score {
        text-align: center;
        flex-shrink: 0;
      }

      .score-ring {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 0.5rem;
      }

      .score-inner {
        width: 76px;
        height: 76px;
        background: var(--cream);
        border-radius: 50%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
      }

      .score-val {
        font-size: 1.3rem;
        font-weight: 800;
        color: var(--sage-deep);
        line-height: 1;
      }

      .score-lbl {
        font-size: 0.68rem;
        color: var(--warm-light);
      }

      .summary-info h3 {
        font-family: 'Playfair Display', serif;
        font-size: 1.2rem;
        font-weight: 700;
        margin-bottom: 0.3rem;
        color: var(--charcoal);
      }

      .summary-info p {
        font-size: 0.9rem;
        color: var(--warm-mid);
        line-height: 1.6;
      }

      .summary-pills {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        margin-top: 0.75rem;
      }

      .summary-pill {
        padding: 0.3rem 0.8rem;
        border-radius: var(--radius-pill);
        font-size: 0.8rem;
        font-weight: 600;
      }

      .pill-green {
        background: var(--sage-pale);
        color: var(--sage-deep);
      }

      .pill-red {
        background: #f8d7da;
        color: #721c24;
      }

      .pill-yellow {
        background: var(--gold-pale);
        color: #856404;
      }

      /* ── BADGES ── */
      .badge {
        display: inline-flex;
        align-items: center;
        padding: 0.28rem 0.8rem;
        border-radius: var(--radius-pill);
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.02em;
      }

      .badge-green {
        background: var(--sage-pale);
        color: var(--sage-deep);
      }

      .badge-red {
        background: #f8d7da;
        color: #721c24;
      }

      /* ── SKILL SECTIONS ── */
      .skill-section {
        background: #fff;
        border: 1px solid rgba(139,128,112,0.12);
        border-radius: var(--radius-xl);
        padding: 1.25rem;
        margin-bottom: 0.75rem;
        box-shadow: 0 4px 24px rgba(42,42,34,0.05);
        transition: box-shadow 0.25s;
      }

      .skill-section:hover {
        box-shadow: 0 8px 32px rgba(42,42,34,0.1);
      }

      .skill-section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
      }

      .skill-section-title {
        font-family: 'Playfair Display', serif;
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--charcoal);
      }

      .skill-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.25rem;
      }

      .skill-item {
        display: flex;
        flex-direction: column;
      }

      .skill-label-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.4rem;
      }

      .skill-name {
        font-size: 0.88rem;
        font-weight: 500;
        color: var(--charcoal);
      }

      .skill-pct {
        font-size: 0.82rem;
        font-weight: 700;
      }

      .skill-pct.good {
        color: var(--sage-deep);
      }

      .skill-pct.warn {
        color: #856404;
      }

      .skill-pct.bad {
        color: #721c24;
      }

      /* ── PROGRESS BARS ── */
      .progress-bar {
        width: 100%;
        height: 8px;
        background: var(--sand);
        border-radius: 999px;
        overflow: hidden;
      }

      .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--sage-light), var(--sage));
        border-radius: 999px;
        transition: width 0.8s var(--ease-out);
      }

      .progress-fill.gap {
        background: linear-gradient(90deg, #f1aeb5, #f8d7da);
      }

      .progress-wrap {
        margin-bottom: 0.6rem;
      }

      .progress-wrap:last-child {
        margin-bottom: 0;
      }

      .progress-label {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.82rem;
        font-weight: 500;
        color: var(--charcoal);
        margin-bottom: 0.35rem;
      }

      .progress-label span:last-child {
        color: var(--warm-mid);
        font-weight: 600;
      }

      /* ── GAP TAGS ── */
      .gap-tag {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.4rem 0.9rem;
        border-radius: var(--radius-pill);
        font-size: 0.85rem;
        font-weight: 500;
        margin: 0.3rem;
      }

      .gap-tag.missing {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f1aeb5;
      }

      .gap-tag.partial {
        background: var(--gold-pale);
        color: #856404;
        border: 1px solid #ffd77a;
      }

      /* ── SECTION LABELS ── */
      .section-label {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: var(--sage);
        margin-bottom: 0.5rem;
      }

      .section-title {
        font-family: 'Playfair Display', serif;
        font-size: 1.5rem;
        font-weight: 900;
        color: var(--charcoal);
        letter-spacing: -0.02em;
        margin-bottom: 0.3rem;
      }

      /* ── CHART CARDS ── */
      .chart-card {
        background: #fff;
        border: 1px solid rgba(139,128,112,0.12);
        border-radius: var(--radius-xl);
        padding: 1.25rem;
        margin-bottom: 0.75rem;
        box-shadow: 0 4px 24px rgba(42,42,34,0.05);
        transition: box-shadow 0.25s;
      }

      .chart-card:hover {
        box-shadow: 0 8px 32px rgba(42,42,34,0.1);
      }

      .chart-card h3 {
        font-family: 'Playfair Display', serif;
        margin-bottom: 0.75rem;
        font-size: 1rem;
        font-weight: 700;
        color: var(--charcoal);
      }

      /* ── TABLE ── */
      .stats-table-wrap {
        background: #fff;
        border: 1px solid rgba(139,128,112,0.12);
        border-radius: var(--radius-xl);
        padding: 1.25rem;
        margin-bottom: 1.25rem;
        box-shadow: 0 4px 24px rgba(42,42,34,0.05);
        width: calc(100% + 370px);
      }

      .stats-table-wrap h3 {
        font-family: 'Playfair Display', serif;
        margin-bottom: 0.75rem;
        font-size: 1rem;
        font-weight: 700;
        color: var(--charcoal);
      }

      .rank-badge {
        background: var(--sage-pale);
        color: var(--sage-deep);
        padding: 0.25rem 0.5rem;
        border-radius: var(--radius-md);
        margin-right: 0.5rem;
        font-weight: 700;
        font-size: 0.8rem;
      }

      .table-header-row {
        background: var(--sand);
        border-bottom: 2px solid var(--sand-dark);
      }

      .table-header-row th {
        padding: 0.75rem;
        text-align: left;
        font-weight: 700;
        color: var(--charcoal);
        font-size: 0.82rem;
      }

      .table-header-row th:nth-child(2),
      .table-header-row th:nth-child(3),
      .table-header-row th:nth-child(4) {
        text-align: center;
      }

      .table-row {
        border-bottom: 1px solid var(--sand-dark);
      }

      .table-row td {
        padding: 0.75rem;
        font-size: 0.88rem;
      }

      .table-row td:nth-child(2),
      .table-row td:nth-child(3),
      .table-row td:nth-child(4) {
        text-align: center;
      }

      .status-have {
        color: var(--sage-deep);
        font-weight: 700;
      }

      .status-need {
        color: #721c24;
        font-weight: 700;
      }

      /* ── SIDEBAR ── */
      .side-card {
        background: #fff;
        border: 1px solid rgba(139,128,112,0.12);
        border-radius: var(--radius-xl);
        padding: 1.25rem;
        margin-bottom: 0.75rem;
        box-shadow: 0 4px 24px rgba(42,42,34,0.05);
        transition: box-shadow 0.25s;
      }

      .side-card:hover {
        box-shadow: 0 8px 32px rgba(42,42,34,0.1);
      }

      .side-card-title {
        font-family: 'Playfair Display', serif;
        font-size: 0.92rem;
        font-weight: 700;
        color: var(--charcoal);
        margin-bottom: 0.75rem;
        letter-spacing: -0.01em;
      }

      /* ── MICRO STATS ── */
      .micro-stat {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.6rem 0;
        border-bottom: 1px solid var(--sand-dark);
        font-size: 0.87rem;
      }

      .micro-stat:last-child {
        border-bottom: none;
      }

      .micro-val {
        font-weight: 700;
        color: var(--sage-deep);
      }

      /* ── BUTTONS ── */
      .btn-sage {
        padding: 0.68rem 1.5rem;
        background: var(--sage-deep);
        color: #fff;
        border-radius: var(--radius-pill);
        font-family: 'DM Sans', sans-serif;
        font-size: 0.86rem;
        font-weight: 700;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        transition: background 0.2s, transform 0.15s;
        box-shadow: 0 4px 14px rgba(74,107,80,0.28);
        text-align: center;
      }

      .btn-sage:hover { background: var(--sage); transform: translateY(-1px); }

      .btn-outline {
        padding: 0.68rem 1.4rem;
        background: transparent;
        color: var(--warm-mid);
        border-radius: var(--radius-pill);
        font-family: 'DM Sans', sans-serif;
        font-size: 0.86rem;
        font-weight: 500;
        border: 1.5px solid var(--stone-light);
        cursor: pointer;
        transition: background 0.2s, border-color 0.2s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        text-align: center;
      }

      .btn-outline:hover { background: var(--sand); border-color: var(--stone); }

      /* ── FOOTER ── */
      .footer {
        background: #1e1e18;
        color: rgba(255,255,255,0.5);
        padding: 4rem 2rem 2rem;
        margin-top: 2rem;
      }

      .footer-inner { max-width: 1200px; margin: 0 auto; }

      .footer-top {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr;
        gap: 2.5rem;
        padding-bottom: 2.5rem;
        border-bottom: 1px solid rgba(255,255,255,0.07);
        margin-bottom: 1.8rem;
      }

      .footer-brand h3 {
        font-family: 'Playfair Display', serif;
        font-size: 1.1rem;
        font-weight: 700;
        color: #fff;
        margin-bottom: 0.7rem;
      }

      .footer-brand p {
        font-size: 0.81rem;
        line-height: 1.68;
        color: rgba(255,255,255,0.42);
      }

      .footer-col h4 {
        font-size: 0.72rem;
        font-weight: 700;
        color: rgba(255,255,255,0.7);
        text-transform: uppercase;
        letter-spacing: 0.1em;
        margin-bottom: 1rem;
      }

      .footer-col ul { list-style: none; display: flex; flex-direction: column; gap: 0.5rem; }

      .footer-col ul a {
        font-size: 0.82rem;
        color: rgba(255,255,255,0.4);
        transition: color 0.15s;
      }

      .footer-col ul a:hover { color: var(--sage-light); }

      .footer-bottom {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.77rem;
        flex-wrap: wrap;
        gap: 0.5rem;
      }

      /* ── SCROLL ANIMATIONS ── */
      .reveal {
        opacity: 0;
        transform: translateY(28px);
        transition: opacity 0.7s var(--ease-out), transform 0.7s var(--ease-out);
      }

      .reveal.visible {
        opacity: 1;
        transform: translateY(0);
      }

      .reveal-delay-1 { transition-delay: 0.1s; }
      .reveal-delay-2 { transition-delay: 0.2s; }
      .reveal-delay-3 { transition-delay: 0.3s; }
      .reveal-delay-4 { transition-delay: 0.4s; }
      .reveal-delay-5 { transition-delay: 0.5s; }

      /* ── RESPONSIVE ── */
      @media (max-width: 960px) {
        .gap-layout {
          grid-template-columns: 1fr;
          padding: 0 1.5rem 3rem;
        }

        .skill-grid {
          grid-template-columns: 1fr;
        }

        .summary-banner {
          flex-direction: column;
          text-align: center;
        }

        .summary-pills {
          justify-content: center;
        }

        .footer-top {
          grid-template-columns: 1fr 1fr;
        }

        .nav-links { display: none; }

        .navbar { padding: 0 1.2rem; }
      }

      @media (max-width: 600px) {
        .page-header {
          padding: 7rem 1.5rem 1.5rem;
        }

        .gap-layout {
          padding: 0 1rem 2rem;
        }

        .footer-top {
          grid-template-columns: 1fr;
        }

        .footer-bottom {
          flex-direction: column;
          text-align: center;
        }
      }
    </style>
  </head>
  <body>
    <!-- NAVBAR -->
    <nav class="navbar">
      <a href="../../index.php" class="nav-logo">
        <div class="nav-logo-mark">TS</div>
        <span>Talent<em>Scout</em> AI</span>
      </a>
      <ul class="nav-links">
        <li><a href="../../index.php">Home</a></li>
        <li><a href="../job-postings/index.php">Browse Jobs</a></li>
        <li><a href="../ai-matching/index.php">AI Matching</a></li>
        <li><a href="../resume-builder/index.php">Resume Builder</a></li>
        <li><a href="./index.php" class="active">Skills</a></li>
        <li><a href="../applicant-tracking/index.php">Applications</a></li>
        <li><a href="../messages/index.php">Messages</a></li>
      </ul>
      <div class="nav-right">
        <?php if (isset($_SESSION['employee_id'])): ?>
          <span class="nav-user">Welcome, <?php echo htmlspecialchars($_SESSION['employee_name'] ?? 'User'); ?></span>
          <a href="../../logout.php" class="btn-nav-ghost">Logout</a>
        <?php else: ?>
          <a href="../../login.php" class="btn-nav-ghost">Login</a>
          <a href="../../signup.php" class="btn-nav-solid">Get Started →</a>
        <?php endif; ?>
      </div>
    </nav>

    <!-- PAGE HEADER -->
    <div class="page-header">
      <div class="breadcrumb">
        <a href="../../index.php">Home</a> / <em>Skill Gap Analysis</em>
      </div>
      <h1>Skill Gap <em>Analysis</em></h1>
      <p>
        Discover which skills you already have, identify gaps, and get
        personalized upskilling recommendations to boost your employability.
      </p>
    </div>

    <!-- MAIN LAYOUT -->
    <div class="gap-layout">
      <div>
        <!-- SUMMARY BANNER -->
        <div class="summary-banner reveal">
          <div class="summary-score">
            <div class="score-ring" style="background: conic-gradient(var(--sage-deep) 0% <?php echo $employability_score; ?>%, var(--sand-dark) <?php echo $employability_score; ?>% 100%);">
              <div class="score-inner">
                <div class="score-val"><?php echo $employability_score; ?>%</div>
                <div class="score-lbl">Readiness</div>
              </div>
            </div>
            <div style="font-size: 0.78rem; color: var(--warm-light)">
              <?php echo htmlspecialchars($employee_name); ?>
            </div>
          </div>
          <div class="summary-info">
            <h3>Your Employability Score: <?php echo $employability_score; ?> / 100</h3>
            <p>
              You have <?php echo count($current_skills); ?> verified skills that match market demand.
              <?php if ($critical_gaps_count > 0): ?>
                Closing <?php echo $critical_gaps_count; ?> skill gaps could raise your score to <strong><?php echo min(100, $employability_score + 20); ?>%</strong>.
              <?php else: ?>
                You're well-aligned with market demands!
              <?php endif; ?>
            </p>
            <div class="summary-pills">
              <span class="summary-pill pill-green">✓ <?php echo count($current_skills); ?> Verified Skills</span>
              <span class="summary-pill pill-red">✗ <?php echo $critical_gaps_count; ?> Critical Gaps</span>
            </div>
          </div>
        </div>

        <!-- CURRENT SKILLS -->
        <div class="skill-section reveal reveal-delay-1">
          <div class="skill-section-header">
            <div class="skill-section-title">Current Skills</div>
            <span class="badge badge-green"><?php echo count($current_skills); ?> Verified</span>
          </div>
          <div class="skill-grid">
            <?php 
            $proficiency_levels = [85, 90, 88, 82, 87, 78, 83];
            $index = 0;
            foreach ($current_skills_display as $skill => $data): 
              $proficiency = isset($proficiency_levels[$index]) ? $proficiency_levels[$index] : 80;
              $class = $proficiency >= 70 ? 'good' : 'warn';
            ?>
              <div class="skill-item">
                <div class="skill-label-row">
                  <span class="skill-name"><?php echo htmlspecialchars($data['name']); ?></span
                  ><span class="skill-pct <?php echo $class; ?>"><?php echo $proficiency; ?>%</span>
                </div>
                <div class="progress-bar">
                  <div class="progress-fill" style="width: <?php echo $proficiency; ?>%"></div>
                </div>
              </div>
            <?php 
              $index++;
            endforeach; 
            ?>
          </div>
        </div>

        <!-- SKILL STATS TABLE -->
        <div class="stats-table-wrap reveal reveal-delay-5">
          <h3>Market Demand & Your Skills</h3>
          
          <!-- Market Demand Progress Bars -->
          <div style="margin-bottom: 2rem;">
            <div style="font-size: 0.82rem; color: var(--warm-mid); margin-bottom: 1rem;">
              <strong>Top Market Demand:</strong> Most requested skills in job postings right now:
            </div>
            <?php
              $skill_count = 0;
              foreach ($market_skills as $skill => $frequency) {
                if ($skill_count >= 5) break;
                $percentage = $total_jobs > 0 ? round(($frequency / $total_jobs) * 100, 0) : 0;
            ?>
            <div class="progress-wrap">
              <div class="progress-label">
                <span><?php echo htmlspecialchars(ucwords($skill)); ?></span><span><?php echo $percentage; ?>%</span>
              </div>
              <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
              </div>
            </div>
            <?php
                $skill_count++;
              }
            ?>
          </div>

          <!-- Detailed Analysis Table -->
          <div style="margin-bottom: 1rem;">
            <div style="font-size: 0.82rem; color: var(--warm-mid); margin-bottom: 1rem;">
              <strong>Detailed Analysis:</strong> Top 10 skills ranked by market demand:
            </div>
          </div>
          <table style="width: 100%; border-collapse: collapse;">
            <thead>
              <tr class="table-header-row">
                <th>Skill</th>
                <th>Jobs Requiring</th>
                <th>Demand %</th>
                <th>Your Status</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              $rank = 1;
              foreach (array_slice($market_skills, 0, 10) as $skill => $frequency):
                $demand_pct = round(($frequency / $total_jobs) * 100, 1);
                $has_skill = in_array($skill, $employee_skills) ? true : false;
                $status_class = $has_skill ? 'class="status-have"' : 'class="status-need"';
                $status_text = $has_skill ? '✓ You have it' : '✗ Need to learn';
              ?>
                <tr class="table-row">
                  <td>
                    <span class="rank-badge"><?php echo $rank; ?></span>
                    <?php echo htmlspecialchars(ucwords($skill)); ?>
                  </td>
                  <td><?php echo $frequency; ?></td>
                  <td><?php echo $demand_pct; ?>%</td>
                  <td <?php echo $status_class; ?>><?php echo $status_text; ?></td>
                </tr>
              <?php 
                $rank++;
              endforeach; 
              ?>
            </tbody>
          </table>
        </div>

      </div>

      <!-- SIDEBAR -->
      <aside>
        <div class="side-card reveal">
          <div class="side-card-title">Your Progress</div>
          <div class="micro-stat">
            <span>Skills Have</span><span class="micro-val"><?php echo count($current_skills); ?></span>
          </div>
          <div class="micro-stat">
            <span>Skills Missing</span
            ><span class="micro-val" style="color: #721c24"><?php echo count($skill_gaps); ?></span>
          </div>
          <div class="micro-stat">
            <span>Total Market Skills</span
            ><span class="micro-val" style="color: #856404"><?php echo $total_market_skills; ?></span>
          </div>
          <div class="micro-stat">
            <span>Job Postings</span><span class="micro-val"><?php echo $total_jobs; ?></span>
          </div>
          <div class="micro-stat">
            <span>Employability Score</span><span class="micro-val"><?php echo $employability_score; ?>%</span>
          </div>
          <div class="micro-stat">
            <span>Potential Score</span
            ><span class="micro-val" style="color: var(--sage)"><?php echo min(100, $employability_score + 20); ?>%</span>
          </div>
        </div>
      </aside>
    </div>

    <!-- FOOTER -->
    <footer class="footer">
      <div class="footer-inner">
        <div class="footer-top">
          <div class="footer-brand">
            <h3>🌿 TalentScout AI</h3>
            <p>Smart AI-powered recruitment platform for PESO Nasugbu, Batangas. Connecting local talent with local opportunities through fair, intelligent hiring.</p>
          </div>
          <div class="footer-col">
            <h4>Features</h4>
            <ul>
              <li><a href="../job-postings/">Job Postings</a></li>
              <li><a href="../ai-matching/">AI Matching</a></li>
              <li><a href="./">Skill Gap Analysis</a></li>
            </ul>
          </div>
          <div class="footer-col">
            <h4>Job Seeker</h4>
            <ul>
              <li><a href="../applicant-tracking/">Applicant Tracking</a></li>
              <li><a href="../">All Employee Tools</a></li>
            </ul>
          </div>
          <div class="footer-col">
            <h4>PESO Nasugbu</h4>
            <ul>
              <li><a href="#">Nasugbu, Batangas</a></li>
              <li><a href="#">About PESO</a></li>
              <li><a href="#">Contact Us</a></li>
            </ul>
          </div>
        </div>
        <div class="footer-bottom">
          <span>&copy; 2026 TalentScout AI — PESO Nasugbu, Batangas</span>
          <span>Built for Local Employment &amp; Community Growth</span>
        </div>
      </div>
    </footer>

    <script>
      // ── Scroll reveal
      const reveals = document.querySelectorAll('.reveal');
      const io = new IntersectionObserver((entries) => {
        entries.forEach(e => {
          if (e.isIntersecting) {
            e.target.classList.add('visible');
            io.unobserve(e.target);
          }
        });
      }, { threshold: 0.12 });
      reveals.forEach(el => io.observe(el));
    </script>

    <script src="../../employee-auth.js"></script>
  </body>
</html>
