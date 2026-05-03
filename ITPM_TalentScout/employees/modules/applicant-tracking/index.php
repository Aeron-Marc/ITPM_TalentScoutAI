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
  a.hire_status,
  j.title as job_title,
  e.company_name,
  e.employer_id
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
  // Calculate display status based on hire_status priority
  $hireStatus = $row['hire_status'] ?? 'none';
  if ($hireStatus === 'accepted') {
    $row['display_status'] = 'Hired';
  } elseif ($hireStatus === 'rejected') {
    $row['display_status'] = 'Offer Declined';
  } elseif ($hireStatus === 'offered') {
    $row['display_status'] = 'Offer Received';
  } else {
    $row['display_status'] = $row['status'];
  }
  $applications[] = $row;
}

$stmt->close();

// Calculate statistics from database
$totalApplications = count($applications);
$hiredCount = count(array_filter($applications, function($app) {
  return ($app['hire_status'] ?? 'none') === 'accepted';
}));
$jobOffers = count(array_filter($applications, function($app) {
  return ($app['hire_status'] ?? 'none') === 'offered';
}));
$interviewsScheduled = count(array_filter($applications, function($app) {
  $hireStatus = $app['hire_status'] ?? 'none';
  return stripos($app['status'], 'interview') !== false && !in_array($hireStatus, ['offered', 'accepted']);
}));
$underReview = count(array_filter($applications, function($app) {
  $status = strtolower($app['status']);
  $hireStatus = $app['hire_status'] ?? 'none';
  return !in_array($hireStatus, ['offered', 'accepted', 'rejected']) && 
         $status !== 'rejected' && 
         stripos($status, 'interview') === false &&
         stripos($status, 'offer') === false;
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
        display: inline-flex; align-items: center; gap: 0.35rem;
      }

      .btn-nav-solid:hover { background: var(--sage); transform: translateY(-1px); }

      /* ── PAGE HEADER ── */
      .page-header {
        padding: 6rem 2rem 2.5rem;
        background: linear-gradient(180deg, var(--sand) 0%, var(--cream) 100%);
        text-align: center;
      }

      .page-header-inner {
        max-width: 720px;
        margin: 0 auto;
      }

      .breadcrumb {
        font-size: 0.78rem;
        color: var(--warm-light);
        margin-bottom: 1rem;
      }

      .breadcrumb a {
        color: var(--sage);
        font-weight: 500;
        transition: color 0.15s;
      }

      .breadcrumb a:hover { color: var(--sage-deep); }

      .page-header h1 {
        font-family: 'Playfair Display', serif;
        font-size: clamp(1.8rem, 3.5vw, 2.6rem);
        font-weight: 900;
        color: var(--charcoal);
        letter-spacing: -0.025em;
        line-height: 1.2;
        margin-bottom: 0.8rem;
      }

      .page-header p {
        font-size: 0.92rem;
        color: var(--warm-mid);
        line-height: 1.7;
        max-width: 520px;
        margin: 0 auto;
      }

      /* ── MAIN SHELL ── */
      .employee-module-shell {
        max-width: 1120px;
        margin: 0 auto;
        padding: 2.25rem 2rem 4rem;
      }

      /* ── STAT CARDS ── */
      .tracker-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1.05rem;
        margin-bottom: 2rem;
      }

      .tracker-stat-card {
        position: relative;
        overflow: hidden;
        background: #fff;
        border: 1px solid var(--sand-dark);
        border-radius: var(--radius-xl);
        box-shadow: 0 4px 24px rgba(42, 42, 34, 0.06);
        padding: 1.4rem 1.3rem 1.2rem;
        transition: transform 0.3s var(--ease-out), box-shadow 0.3s var(--ease-out);
      }

      .tracker-stat-card::before {
        content: "";
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--sage-pale), var(--sage));
        transform: scaleX(0);
        transform-origin: left;
        transition: transform 0.3s var(--ease-out);
      }

      .tracker-stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 40px rgba(42, 42, 34, 0.12);
      }

      .tracker-stat-card:hover::before { transform: scaleX(1); }

      .tracker-stat-number {
        font-family: 'Playfair Display', serif;
        font-size: 2rem;
        font-weight: 700;
        line-height: 1.05;
        color: var(--sage-deep);
        letter-spacing: -0.02em;
      }

      .tracker-stat-label {
        margin-top: 0.4rem;
        color: var(--warm-mid);
        font-size: 0.74rem;
        font-weight: 600;
        letter-spacing: 0.06em;
        text-transform: uppercase;
      }

      /* ── TABLE ── */
      .tracker-table-wrap {
        border: 1px solid var(--sand-dark);
        border-radius: var(--radius-xl);
        box-shadow: 0 4px 24px rgba(42, 42, 34, 0.06);
        overflow: hidden;
      }

      .tracker-table {
        width: 100%;
        border-collapse: collapse;
      }

      .tracker-table th {
        font-size: 0.72rem;
        font-weight: 700;
        color: var(--stone);
        text-transform: uppercase;
        letter-spacing: 0.08em;
        background: var(--sand);
        border-bottom: 1px solid var(--sand-dark);
        padding: 0.9rem 1.2rem;
        text-align: left;
      }

      .tracker-table td {
        border-bottom: 1px solid var(--sand-dark);
        padding: 0.85rem 1.2rem;
        vertical-align: middle;
      }

      .tracker-table tbody tr:last-child td { border-bottom: none; }

      .tracker-table tbody tr:nth-child(even) {
        background: rgba(245, 240, 232, 0.35);
      }

      .tracker-table tbody tr:hover {
        background: var(--sand);
      }

      .tracker-job-title {
        font-weight: 600;
        color: var(--charcoal);
      }

      .tracker-company {
        color: var(--warm-mid);
        font-size: 0.88rem;
      }

      .tracker-table td:nth-child(3) {
        color: var(--warm-light);
        font-size: 0.85rem;
        font-variant-numeric: tabular-nums;
      }

      /* ── STATUS BADGES ── */
      .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.32rem 0.78rem;
        border-radius: var(--radius-pill);
        font-size: 0.74rem;
        font-weight: 600;
        letter-spacing: 0.02em;
        white-space: nowrap;
      }

      .status-approved {
        background: var(--sage-pale);
        color: var(--sage-deep);
      }

      .status-interview {
        background: var(--gold-pale);
        color: #7a6230;
      }

      .status-reviewed {
        background: #f0e4c8;
        color: #8a5a02;
      }

      .status-pending {
        background: var(--sand);
        color: var(--warm-mid);
        border: 1px solid var(--stone-light);
      }

      .status-rejected {
        background: #fdeeee;
        color: #9a2d2d;
        border: 1px solid #f3c4c4;
      }

      .status-hired {
        background: #c8e6c9;
        color: #1b5e20;
        border: 1px solid #81c784;
      }

      /* ── ACTION BUTTON ── */
      .action-btn {
        border: 1px solid var(--sage-light);
        background: var(--sage-pale);
        color: var(--sage-deep);
        border-radius: var(--radius-pill);
        padding: 0.42rem 0.95rem;
        font-size: 0.79rem;
        font-weight: 600;
        font-family: 'DM Sans', sans-serif;
        letter-spacing: 0.01em;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(74, 107, 80, 0.1);
        transition: all 0.2s var(--ease-out);
      }

      .action-btn:hover {
        background: var(--sage-deep);
        color: #fff;
        border-color: var(--sage-deep);
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(74, 107, 80, 0.25);
      }

      /* ── MODAL ── */
      .app-details-modal {
        position: fixed;
        inset: 0;
        background:
          radial-gradient(circle at 20% 25%, rgba(107, 143, 113, 0.12) 0%, rgba(107, 143, 113, 0) 50%),
          rgba(42, 42, 34, 0.5);
        backdrop-filter: blur(4px);
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
        background: #fff;
        border-radius: var(--radius-xl);
        border: 1px solid var(--sand-dark);
        box-shadow: 0 30px 70px rgba(42, 42, 34, 0.22);
        padding: 0;
        text-align: left;
        overflow: hidden;
        animation: appDetailsPop 0.2s var(--ease-out);
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
          linear-gradient(120deg, rgba(107, 143, 113, 0.1) 0%, rgba(107, 143, 113, 0.02) 50%, #fff 100%),
          #fff;
        border-bottom: 1px solid var(--sand-dark);
      }

      .app-details-head h3 {
        margin: 0;
        font-family: 'Playfair Display', serif;
        font-size: 1.22rem;
        font-weight: 700;
        color: var(--charcoal);
      }

      .app-details-head p {
        margin: 7px 0 0;
        font-size: 0.9rem;
        color: var(--warm-mid);
        max-width: 46ch;
      }

      #app-details-close-btn {
        width: 34px;
        height: 34px;
        border-radius: var(--radius-pill);
        border: 1px solid var(--stone-light);
        background: #fff;
        color: var(--warm-mid);
        font-size: 1.4rem;
        line-height: 1;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(42, 42, 34, 0.08);
        transition: all 0.14s var(--ease-out);
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        position: relative;
        z-index: 10;
        pointer-events: auto;
      }

      #app-details-close-btn:hover {
        transform: translateY(-1px);
        border-color: var(--stone);
        background: var(--sand);
      }

      #app-details-close-btn:active {
        transform: translateY(0);
      }

      .app-details-body {
        padding: 18px 24px 8px;
      }

      .app-details-summary {
        border: 1px solid var(--sand-dark);
        border-radius: var(--radius-lg);
        padding: 16px;
        background: linear-gradient(145deg, var(--sand) 0%, #fff 100%);
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
        font-family: 'Playfair Display', serif;
        font-size: 1.08rem;
        font-weight: 700;
        color: var(--charcoal);
      }

      .app-details-company {
        display: inline-block;
        margin-top: 4px;
        font-size: 0.9rem;
        color: var(--warm-mid);
      }

      .app-details-status-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: var(--radius-pill);
        font-size: 0.76rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        white-space: nowrap;
        padding: 7px 11px;
        border: 1px solid transparent;
      }

      .app-details-status-pill.tone-offer {
        background: var(--sage-pale);
        color: var(--sage-deep);
        border-color: var(--sage-light);
      }

      .app-details-status-pill.tone-interview {
        background: var(--gold-pale);
        color: #7a6230;
        border-color: #e0cc96;
      }

      .app-details-status-pill.tone-review {
        background: #f0e4c8;
        color: #8a5a02;
        border-color: #e0cc96;
      }

      .app-details-status-pill.tone-pending {
        background: var(--sand);
        color: var(--warm-mid);
        border-color: var(--stone-light);
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
        font-weight: 600;
        color: var(--warm-mid);
      }

      .app-details-progress-track {
        width: 100%;
        height: 8px;
        border-radius: var(--radius-pill);
        background: var(--sand);
        overflow: hidden;
      }

      .app-details-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--sage) 0%, var(--sage-light) 100%);
        border-radius: var(--radius-pill);
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
        border-radius: var(--radius-md);
        background: var(--sand);
        min-height: 88px;
      }

      .app-details-label {
        display: block;
        color: var(--warm-light);
        font-weight: 600;
        font-size: 0.72rem;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        margin-bottom: 4px;
      }

      .app-details-value {
        color: var(--charcoal);
        font-weight: 600;
        font-size: 0.91rem;
      }

      .app-details-notes {
        margin-top: 12px;
        border: 1px dashed var(--stone-light);
        border-radius: var(--radius-lg);
        background: var(--cream);
        padding: 11px 12px;
      }

      .app-details-notes-label {
        font-size: 0.72rem;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        font-weight: 700;
        color: var(--warm-light);
        margin-bottom: 4px;
      }

      .app-details-notes p {
        margin: 0;
        font-size: 0.87rem;
        line-height: 1.55;
        color: var(--warm-mid);
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
        border: none;
        border-radius: var(--radius-pill);
        background: var(--sage-deep);
        color: #fff;
        padding: 11px 24px;
        font-family: 'DM Sans', sans-serif;
        font-weight: 600;
        font-size: 0.88rem;
        cursor: pointer;
        box-shadow: 0 6px 18px rgba(74, 107, 80, 0.25);
        transition: all 0.16s var(--ease-out);
      }

      #app-details-ok-btn:hover {
        background: var(--sage);
        transform: translateY(-1px);
        box-shadow: 0 8px 24px rgba(74, 107, 80, 0.32);
      }

      .btn-messages {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: none;
        border-radius: var(--radius-pill);
        background: var(--gold);
        color: #5a4a20;
        padding: 11px 20px;
        font-family: 'DM Sans', sans-serif;
        font-weight: 600;
        font-size: 0.88rem;
        cursor: pointer;
        text-decoration: none;
        box-shadow: 0 4px 12px rgba(200, 169, 110, 0.3);
        transition: all 0.16s var(--ease-out);
        margin-right: auto;
      }

      .btn-messages:hover {
        background: #c8a96e;
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(200, 169, 110, 0.4);
      }

      .app-details-footer {
        padding: 2px 24px 10px;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 0.75rem;
      }

      /* ── FOOTER ── */
      .footer {
        background: #1e1e18;
        color: rgba(255,255,255,0.5);
        padding: 4rem 2rem 2rem;
      }

      .footer-inner { max-width: 1080px; margin: 0 auto; }

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

      /* ── SCROLL REVEAL ── */
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

      /* ── RESPONSIVE ── */
      @media (max-width: 960px) {
        .tracker-stats { grid-template-columns: repeat(2, 1fr); }
        .footer-top { grid-template-columns: 1fr 1fr; }
        .nav-links { display: none; }
      }

      @media (max-width: 600px) {
        .navbar { padding: 0 1.2rem; }
        .tracker-stats { grid-template-columns: 1fr; }
        .footer-top { grid-template-columns: 1fr; }
        .footer-bottom { flex-direction: column; text-align: center; }
        .app-details-head { padding: 16px 16px 14px; }
        .app-details-body { padding: 14px 16px 4px; }
        .app-details-footer { padding: 2px 16px 10px; }
        .app-details-summary-top { flex-direction: column; align-items: flex-start; }
        .app-details-meta { grid-template-columns: 1fr; }
        .page-header { padding: 5.5rem 1.2rem 2rem; }
        .employee-module-shell { padding: 1.5rem 1rem 3rem; }
      }
    </style>
  </head>
  <body>
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
        <li><a href="../skill-gap-analysis/index.php">Skills</a></li>
        <li><a href="./index.php" class="active">Applications</a></li>
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

    <div class="page-header">
      <div class="page-header-inner">
        <div class="breadcrumb">
          <a href="../../index.php">Home</a> / Application Tracker
        </div>
        <h1 class="reveal">Application Tracker</h1>
        <p class="reveal reveal-delay-1">
          Monitor your job applications from first submission to interview and
          final hiring decision.
        </p>
      </div>
    </div>

    <main class="employee-module-shell">
      <div class="tracker-stats">
        <div class="tracker-stat-card reveal">
          <div class="tracker-stat-number" id="totalAppCount"><?php echo $totalApplications; ?></div>
          <div class="tracker-stat-label">Total Applications</div>
        </div>
        <div class="tracker-stat-card reveal reveal-delay-1">
          <div class="tracker-stat-number" id="interviewCount"><?php echo $interviewsScheduled; ?></div>
          <div class="tracker-stat-label">Interviews</div>
        </div>
        <div class="tracker-stat-card reveal reveal-delay-2">
          <div class="tracker-stat-number" id="offersCount"><?php echo $jobOffers; ?></div>
          <div class="tracker-stat-label">Job Offers</div>
        </div>
        <div class="tracker-stat-card reveal reveal-delay-3">
          <div class="tracker-stat-number" id="hiredCount"><?php echo $hiredCount; ?></div>
          <div class="tracker-stat-label">Hired</div>
        </div>
      </div>

      <div class="tracker-table-wrap reveal reveal-delay-2">
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
                <td colspan="5" style="text-align: center; padding: 2rem; color: var(--warm-mid);">
                  No applications yet. <a href="../job-postings/" style="color: var(--sage-deep); text-decoration: none; font-weight: 600;">Browse jobs</a> and start applying!
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($applications as $app): ?>
                <?php
                  $displayStatus = $app['display_status'];
                  $hireStatus = $app['hire_status'] ?? 'none';
                  $statusClass = 'status-pending';
                  
                  if ($hireStatus === 'accepted') {
                    $statusClass = 'status-hired';
                  } elseif ($hireStatus === 'rejected') {
                    $statusClass = 'status-rejected';
                  } elseif ($hireStatus === 'offered' || stripos($displayStatus, 'offer') !== false) {
                    $statusClass = 'status-approved';
                  } elseif (stripos($displayStatus, 'interview') !== false) {
                    $statusClass = 'status-interview';
                  } elseif (stripos($displayStatus, 'review') !== false) {
                    $statusClass = 'status-reviewed';
                  } elseif (stripos($displayStatus, 'rejected') !== false || stripos($displayStatus, 'not selected') !== false) {
                    $statusClass = 'status-rejected';
                  }
                  
                  $appliedDate = date('Y-m-d', strtotime($app['application_date']));
                ?>
                <tr data-app-id="<?php echo $app['application_id']; ?>" data-employer-id="<?php echo $app['employer_id']; ?>" data-hire-status="<?php echo $hireStatus; ?>">
                  <td class="tracker-job-title"><?php echo htmlspecialchars($app['job_title']); ?></td>
                  <td class="tracker-company"><?php echo htmlspecialchars($app['company_name']); ?></td>
                  <td><?php echo htmlspecialchars($appliedDate); ?></td>
                  <td>
                    <span class="status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($displayStatus); ?></span>
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
          <a href="#" id="app-details-messages-btn" class="btn-messages" style="display: none;">💬 Go to Messages</a>
          <button type="button" id="app-details-ok-btn">Close</button>
        </div>
      </div>
    </div>

    <!-- Scroll reveal script -->
    <script>
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

    <script>
      const detailsModal = document.getElementById('application-details-modal');
      const detailsBody = document.getElementById('app-details-body');
      const detailsOkBtn = document.getElementById('app-details-ok-btn');
      const detailsCloseBtn = document.getElementById('app-details-close-btn');
      const detailsSubtext = document.getElementById('app-details-subtext');

      if (detailsOkBtn) {
        const rootStyle = window.getComputedStyle(document.documentElement);
        const primaryDark = rootStyle.getPropertyValue('--sage-deep').trim() || '#4a6b50';
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

        if (normalized.includes('hired')) {
          return {
            toneClass: 'tone-offer',
            progress: 100,
            stage: 'Hired',
            nextStep: 'Congratulations! Check your messages for next steps',
            priority: 'High',
            note: 'Congratulations on your new job! The employer will reach out with further details.'
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

          const appId = row.dataset.appId || '';
          const employerId = row.dataset.employerId || '';
          
          const jobTitle = row.querySelector('.tracker-job-title')?.textContent?.trim() || 'N/A';
          const company = row.querySelector('.tracker-company')?.textContent?.trim() || 'N/A';
          const appliedDate = row.cells[2]?.textContent?.trim() || 'N/A';
          const status = row.querySelector('.status-badge')?.textContent?.trim() || 'N/A';
          const formattedDate = formatAppliedDate(appliedDate);
          const statusMeta = getStatusMetadata(status);
          
          // Show/hide messages button based on pending actions
          const messagesBtn = document.getElementById('app-details-messages-btn');
          const statusLower = status.toLowerCase();
          if (messagesBtn) {
            // Show messages button if there's a scheduled interview or offer to respond to
            if (statusLower.includes('interview') || statusLower.includes('offer')) {
              messagesBtn.style.display = 'inline-flex';
              messagesBtn.href = '../messages/index.php?employer_id=' + employerId + '&application_id=' + appId;
            } else {
              messagesBtn.style.display = 'none';
            }
          }

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

      if (detailsCloseBtn) {
        detailsCloseBtn.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          closeDetailsModal();
        });
      }

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
          document.getElementById('hiredCount').textContent = stats.hired;

          // Update table
          const tableBody = document.getElementById('applicationsTableBody');
          const emptyState = document.getElementById('emptyStateRow');

          if (applications.length === 0) {
            // Show empty state
            tableBody.innerHTML = '<tr id="emptyStateRow"><td colspan="5" style="text-align: center; padding: 2rem; color: var(--warm-mid);">No applications yet. <a href="../job-postings/" style="color: var(--sage-deep); text-decoration: none; font-weight: 600;">Browse jobs</a> and start applying!</td></tr>';
          } else {
            // Hide empty state and populate table
            tableBody.innerHTML = applications.map(app => {
              const displayStatus = app.display_status || app.status;
              const hireStatus = app.hire_status || 'none';
              let statusClass = 'status-pending';

              if (hireStatus === 'accepted') {
                statusClass = 'status-hired';
              } else if (hireStatus === 'rejected') {
                statusClass = 'status-rejected';
              } else if (hireStatus === 'offered' || displayStatus.toLowerCase().includes('offer')) {
                statusClass = 'status-approved';
              } else if (displayStatus.toLowerCase().includes('interview')) {
                statusClass = 'status-interview';
              } else if (displayStatus.toLowerCase().includes('review')) {
                statusClass = 'status-reviewed';
              } else if (displayStatus.toLowerCase().includes('rejected') || displayStatus.toLowerCase().includes('not selected')) {
                statusClass = 'status-rejected';
              }

              const appliedDate = new Date(app.application_date).toLocaleDateString('en-CA');

              return `
                <tr data-app-id="${app.application_id}" data-employer-id="${app.employer_id}" data-hire-status="${hireStatus}">
                  <td class="tracker-job-title">${escapeHtml(app.job_title)}</td>
                  <td class="tracker-company">${escapeHtml(app.company_name)}</td>
                  <td>${escapeHtml(appliedDate)}</td>
                  <td>
                    <span class="status-badge ${statusClass}">${escapeHtml(displayStatus)}</span>
                  </td>
                  <td><button class="action-btn">View Details</button></td>
                </tr>
              `;
            }).join('');

            // Re-attach event listeners to dynamically created buttons
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
