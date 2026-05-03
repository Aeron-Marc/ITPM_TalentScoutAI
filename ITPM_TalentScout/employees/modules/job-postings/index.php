<?php
session_start();

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
  while ($row = $result->fetch_assoc()) {
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
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900;1,700&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <style>
    /* ===== WARM EARTHY DESIGN SYSTEM ===== */
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

    /* ===== STICKY FOOTER LAYOUT ===== */
    html, body { height: 100%; margin: 0; padding: 0; }
    body { display: flex; flex-direction: column; }
    .tab-content, main, .page-container { flex: 1 0 auto; }
    .footer { flex-shrink: 0; }

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

    .nav-logo-text { font-style: normal; }
    .nav-logo-text span { font-style: italic; color: var(--sage); }

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

    .nav-actions {
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
      padding: 5.5rem 2rem 2.5rem;
      background: linear-gradient(180deg, var(--sand) 0%, var(--cream) 100%);
      border-bottom: 1px solid var(--sand-dark);
    }

    .page-header-inner {
      max-width: 1200px;
      margin: 0 auto;
    }

    .breadcrumb {
      font-size: 0.78rem;
      color: var(--warm-light);
      margin-bottom: 0.8rem;
    }

    .breadcrumb a {
      color: var(--sage);
      transition: color 0.15s;
    }

    .breadcrumb a:hover { color: var(--sage-deep); }

    .page-header h1 {
      font-family: 'Playfair Display', serif;
      font-size: clamp(1.6rem, 3vw, 2.2rem);
      font-weight: 900;
      color: var(--charcoal);
      letter-spacing: -0.025em;
      line-height: 1.2;
      margin-bottom: 0.6rem;
    }

    .page-header p {
      font-size: 0.9rem;
      color: var(--warm-mid);
      line-height: 1.7;
      max-width: 520px;
    }

    /* ── SEARCH BAR ── */
    .search-bar-wrap {
      background: var(--cream);
      border-bottom: 1px solid var(--sand-dark);
      padding: 1.25rem 2.5rem;
    }

    .search-bar {
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      gap: 0.75rem;
      align-items: center;
      flex-wrap: wrap;
    }

    .search-input-rel {
      position: relative;
      flex: 2;
      min-width: 220px;
    }

    .search-icon {
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      color: var(--warm-light);
      font-size: 0.9rem;
      pointer-events: none;
    }

    .search-input-rel .input {
      padding-left: 2.5rem;
    }

    .input, .select {
      padding: 0.65rem 1rem;
      border: 1.5px solid var(--sand-dark);
      border-radius: var(--radius-pill);
      font-family: 'DM Sans', sans-serif;
      font-size: 0.86rem;
      color: var(--charcoal);
      background: #fff;
      transition: border-color 0.2s, box-shadow 0.2s;
      outline: none;
    }

    .input:focus, .select:focus {
      border-color: var(--sage);
      box-shadow: 0 0 0 3px var(--sage-pale);
    }

    .input::placeholder { color: var(--warm-light); }

    .select { cursor: pointer; appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%238a8070' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 0.85rem center; padding-right: 2.2rem; }

    .btn-primary {
      padding: 0.65rem 1.5rem;
      background: var(--sage-deep);
      color: #fff;
      border-radius: var(--radius-pill);
      font-family: 'DM Sans', sans-serif;
      font-size: 0.86rem;
      font-weight: 600;
      border: none;
      cursor: pointer;
      transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
      box-shadow: 0 4px 14px rgba(74,107,80,0.22);
    }

    .btn-primary:hover { background: var(--sage); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(74,107,80,0.3); }

    .btn-secondary {
      padding: 0.65rem 1.5rem;
      background: transparent;
      color: var(--warm-mid);
      border-radius: var(--radius-pill);
      font-family: 'DM Sans', sans-serif;
      font-size: 0.86rem;
      font-weight: 500;
      border: 1.5px solid var(--stone-light);
      cursor: pointer;
      transition: background 0.2s, border-color 0.2s, transform 0.15s;
    }

    .btn-secondary:hover { background: var(--sand); border-color: var(--stone); transform: translateY(-1px); }

    /* ── MAIN LAYOUT ── */
    .main-layout {
      max-width: 1200px;
      margin: 0 auto;
      padding: 2rem 2.5rem;
      display: grid;
      grid-template-columns: 260px 1fr;
      gap: 2rem;
    }

    /* ── SIDEBAR ── */
    .sidebar-card {
      background: #fff;
      border: 1px solid var(--sand-dark);
      border-radius: var(--radius-xl);
      padding: 0.75rem;
      margin-bottom: 0.75rem;
      box-shadow: 0 2px 12px rgba(42,42,34,0.04);
    }

    .sidebar-title {
      font-family: 'Playfair Display', serif;
      font-weight: 700;
      font-size: 0.88rem;
      color: var(--charcoal);
      margin-bottom: 0.6rem;
      padding-bottom: 0.4rem;
      border-bottom: 1px solid var(--sand-dark);
      letter-spacing: -0.01em;
    }

    .filter-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      font-size: 0.87rem;
      color: var(--warm-mid);
      padding: 0.35rem 0.5rem;
      cursor: pointer;
      user-select: none;
      border-radius: var(--radius-md);
      transition: background 0.15s;
    }

    .filter-item:hover { background: var(--sand); }

    .filter-left {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .fcheck {
      width: 16px;
      height: 16px;
      border: 1.5px solid var(--stone-light);
      border-radius: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.65rem;
      flex-shrink: 0;
      transition: background 0.15s, border-color 0.15s;
    }

    .fcheck.on {
      background: var(--sage);
      border-color: var(--sage);
      color: white;
    }

    .fcount {
      background: var(--sand);
      color: var(--warm-light);
      padding: 0.1rem 0.5rem;
      border-radius: var(--radius-pill);
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
      padding: 0.4rem 0.6rem;
      border: 1.5px solid var(--sand-dark);
      border-radius: var(--radius-md);
      font-size: 0.8rem;
      font-family: 'DM Sans', sans-serif;
      color: var(--charcoal);
      background: var(--cream);
      outline: none;
      transition: border-color 0.2s;
    }

    .salary-inputs input:focus {
      border-color: var(--sage);
    }

    .range-bar {
      position: relative;
      height: 5px;
      background: var(--sand-dark);
      border-radius: 5px;
      margin: 0.75rem 0;
    }

    .range-track {
      position: absolute;
      height: 100%;
      background: var(--sage);
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
      background: var(--sage);
      cursor: pointer;
      border: 2px solid #fff;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .range-input::-moz-range-thumb {
      pointer-events: auto;
      width: 18px;
      height: 18px;
      border-radius: 50%;
      background: var(--sage);
      cursor: pointer;
      border: 2px solid #fff;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .range-labels {
      display: flex;
      justify-content: space-between;
      font-size: 0.75rem;
      color: var(--warm-light);
    }

    /* ── JOBS COLUMN ── */
    .jobs-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.25rem;
    }

    .jobs-map {
      position: relative;
    }

    .jobs-map-section {
      background: #fff;
      border: 1.5px solid var(--sand-dark);
      border-radius: var(--radius-xl);
      padding: 1rem;
      margin-bottom: 1.25rem;
      box-shadow: 0 4px 20px rgba(42,42,34,0.05);
    }

    .jobs-map-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 1rem;
      margin-bottom: 0.75rem;
      flex-wrap: wrap;
    }

    .btn-location {
      padding: 0.5rem 1rem;
      background: var(--sage-deep);
      color: #fff;
      border: none;
      border-radius: var(--radius-pill);
      font-family: 'DM Sans', sans-serif;
      font-size: 0.82rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      box-shadow: 0 4px 14px rgba(74,107,80,0.22);
    }

    .btn-location:hover {
      background: var(--sage);
      transform: translateY(-1px);
      box-shadow: 0 6px 20px rgba(74,107,80,0.3);
    }

    .btn-location.active {
      background: var(--gold);
      color: var(--charcoal);
    }

    .btn-location:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      transform: none;
    }

    .radius-control {
      display: none;
      align-items: center;
      gap: 0.5rem;
      margin-left: auto;
      font-size: 0.82rem;
      color: var(--warm-mid);
    }

    .radius-control.visible {
      display: inline-flex;
    }

    .radius-control input {
      width: 60px;
      padding: 0.3rem 0.5rem;
      border: 1.5px solid var(--sand-dark);
      border-radius: var(--radius-md);
      font-family: 'DM Sans', sans-serif;
      font-size: 0.82rem;
      color: var(--charcoal);
      background: #fff;
      outline: none;
      transition: border-color 0.2s;
    }

    .radius-control input:focus {
      border-color: var(--sage);
    }

    .btn-clear-location {
      padding: 0.35rem 0.75rem;
      background: transparent;
      color: var(--warm-mid);
      border: 1.5px solid var(--stone-light);
      border-radius: var(--radius-pill);
      font-family: 'DM Sans', sans-serif;
      font-size: 0.78rem;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s;
    }

    .btn-clear-location:hover {
      background: var(--sand);
      border-color: var(--stone);
    }

    .map-theme-selector {
      position: absolute;
      top: 10px;
      right: 10px;
      z-index: 1000;
      display: flex;
      gap: 0.4rem;
      align-items: center;
    }

    .map-theme-select {
      padding: 0.4rem 2rem 0.4rem 0.6rem;
      border: none;
      border-radius: var(--radius-md);
      background: #fff;
      font-family: 'DM Sans', sans-serif;
      font-size: 0.78rem;
      font-weight: 500;
      color: var(--charcoal);
      cursor: pointer;
      box-shadow: 0 2px 8px rgba(0,0,0,0.15);
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%232a2a22' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 0.5rem center;
      outline: none;
      transition: box-shadow 0.2s;
    }

    .map-theme-select:hover, .map-theme-select:focus {
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }

    .map-overlay-btn {
      padding: 0.4rem 0.6rem;
      border: none;
      border-radius: var(--radius-md);
      background: #fff;
      font-family: 'DM Sans', sans-serif;
      font-size: 0.75rem;
      font-weight: 500;
      color: var(--charcoal);
      cursor: pointer;
      box-shadow: 0 2px 8px rgba(0,0,0,0.15);
      transition: all 0.2s;
      display: flex;
      align-items: center;
      gap: 0.3rem;
      white-space: nowrap;
    }

    .map-overlay-btn:hover {
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
      transform: translateY(-1px);
    }

    .map-overlay-btn.active {
      background: var(--sage);
      color: #fff;
    }

    .map-overlay-btn.active.traffic-active {
      background: #ea580c;
    }

    .leaflet-control-zoom a {
      background: #fff !important;
      color: var(--charcoal) !important;
      border-color: var(--sand-dark) !important;
    }

    .leaflet-popup-content-wrapper {
      border-radius: var(--radius-lg) !important;
      box-shadow: 0 4px 16px rgba(0,0,0,0.15) !important;
    }

    .jobs-map-section.fullscreen {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      z-index: 5000;
      border-radius: 0;
      border: none;
      padding: 0;
      margin: 0;
      background: #000;
    }

    .jobs-map-section.fullscreen .jobs-map {
      height: 100vh;
      border-radius: 0;
      border: none;
    }

    .jobs-map-section.fullscreen .jobs-map-header {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      z-index: 5001;
      padding: 1rem;
      background: linear-gradient(180deg, rgba(0,0,0,0.6) 0%, transparent 100%);
    }

    .jobs-map-section.fullscreen .jobs-map-title,
    .jobs-map-section.fullscreen .jobs-map-subtitle {
      color: #fff;
    }

    .jobs-map-section.fullscreen .map-theme-select,
    .jobs-map-section.fullscreen .map-overlay-btn {
      box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    }

    .map-close-fullscreen {
      display: none;
      position: absolute;
      top: 12px;
      right: 12px;
      z-index: 5002;
      background: #fff;
      border: none;
      width: 36px;
      height: 36px;
      border-radius: 50%;
      font-size: 1.2rem;
      cursor: pointer;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
      align-items: center;
      justify-content: center;
      transition: all 0.2s;
    }

    .jobs-map-section.fullscreen .map-close-fullscreen {
      display: flex;
    }

    .map-close-fullscreen:hover {
      background: var(--sand);
      transform: scale(1.05);
    }

    .jobs-map-section {
      position: relative;
    }

    .directions-panel {
      display: none;
      position: absolute;
      bottom: 10px;
      left: 10px;
      right: 10px;
      max-width: 380px;
      max-height: 50vh;
      z-index: 4000;
      background: rgba(255, 255, 255, 0.97);
      backdrop-filter: blur(10px);
      border-radius: var(--radius-lg);
      box-shadow: 0 4px 24px rgba(0,0,0,0.25);
      overflow: hidden;
      transition: all 0.3s ease;
    }

    @media (max-width: 768px) {
      .directions-panel {
        left: 5px;
        right: 5px;
        max-width: none;
        bottom: 5px;
      }
    }

    .directions-panel.visible {
      display: block;
    }

    .directions-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.65rem 0.85rem;
      background: var(--sage-pale);
      border-bottom: 1px solid var(--sand-dark);
    }

    .directions-header h4 {
      margin: 0;
      font-family: 'Playfair Display', serif;
      font-size: 0.82rem;
      color: var(--charcoal);
    }

    .directions-close {
      border: none;
      background: transparent;
      font-size: 1.1rem;
      cursor: pointer;
      color: var(--warm-mid);
      padding: 0.2rem;
      transition: color 0.2s;
    }

    .directions-close:hover {
      color: var(--charcoal);
    }

    .directions-body {
      padding: 0.75rem;
      overflow-y: auto;
      max-height: calc(50vh - 45px);
    }

    .direction-route-summary {
      display: flex;
      gap: 1rem;
      padding: 0.55rem 0.65rem;
      background: var(--sand);
      border-radius: var(--radius-md);
      margin-bottom: 0.6rem;
      flex-wrap: wrap;
    }

    .direction-stat {
      display: flex;
      align-items: center;
      gap: 0.3rem;
      font-size: 0.76rem;
      color: var(--charcoal);
    }

    .direction-stat strong {
      color: var(--sage-deep);
    }

    .direction-steps {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .direction-steps li {
      display: flex;
      gap: 0.6rem;
      padding: 0.5rem 0;
      border-bottom: 1px solid var(--sand-dark);
      font-size: 0.76rem;
      color: var(--warm-mid);
    }

    .direction-steps li:last-child {
      border-bottom: none;
    }

    .step-number {
      width: 22px;
      height: 22px;
      border-radius: 50%;
      background: var(--sage-pale);
      color: var(--sage-deep);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.7rem;
      font-weight: 600;
      flex-shrink: 0;
    }

    .directions-loading {
      text-align: center;
      padding: 1.2rem;
      color: var(--warm-light);
    }

    .directions-loading .loading-spinner {
      width: 24px;
      height: 24px;
      margin: 0 auto 0.6rem;
    }

    .directions-error {
      text-align: center;
      padding: 0.85rem;
      color: var(--warm-light);
      font-size: 0.8rem;
    }

    .directions-actions {
      display: flex;
      gap: 0.5rem;
      margin-top: 0.6rem;
    }

    .directions-actions .btn-close-directions {
      padding: 0.4rem 0.85rem;
      border-radius: var(--radius-pill);
      font-size: 0.76rem;
      font-weight: 600;
      background: var(--sand);
      color: var(--warm-mid);
      border: 1.5px solid var(--sand-dark);
      cursor: pointer;
      font-family: 'DM Sans', sans-serif;
      transition: all 0.2s;
    }

    .directions-actions .btn-close-directions:hover {
      background: var(--sand-dark);
    }

    .jobs-map-section.fullscreen .directions-panel {
      max-width: 400px;
      left: 20px;
      bottom: 30px;
      right: auto;
    }

    .direction-steps li {
      cursor: pointer;
      transition: background 0.15s;
    }

    .direction-steps li:hover {
      background: var(--sand);
    }

    .direction-steps li.active-step {
      background: var(--sage-pale);
    }

    .map-step-line {
      stroke-width: 6 !important;
      opacity: 1 !important;
    }

    .jobs-map-title {
      font-family: 'Playfair Display', serif;
      font-size: 1rem;
      font-weight: 700;
      color: var(--charcoal);
    }

    .jobs-map-subtitle {
      font-size: 0.84rem;
      color: var(--warm-light);
    }

    .jobs-map {
      width: 100%;
      height: 360px;
      border-radius: var(--radius-lg);
      overflow: hidden;
      border: 1px solid var(--sand-dark);
      background: var(--sage-pale);
    }

    .jobs-count {
      font-size: 0.9rem;
      color: var(--warm-light);
    }

    .jobs-count strong {
      color: var(--charcoal);
      font-weight: 700;
    }

    .job-card {
      background: #fff;
      border: 1.5px solid var(--sand-dark);
      border-radius: var(--radius-xl);
      padding: 1.5rem;
      margin-bottom: 1rem;
      transition: all 0.3s var(--ease-out);
      cursor: pointer;
      animation: fadeInSlide 0.4s ease-out;
      box-shadow: 0 2px 12px rgba(42,42,34,0.04);
    }

    .job-card .btn,
    .job-card a {
      cursor: pointer;
    }

    .job-card:hover {
      border-color: var(--sage-pale);
      box-shadow: 0 18px 48px rgba(42,42,34,0.12);
      transform: translateY(-7px);
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
      border-radius: var(--radius-md);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 1rem;
      flex-shrink: 0;
    }

    .job-info {
      flex: 1;
    }

    .job-title {
      font-family: 'Playfair Display', serif;
      font-weight: 700;
      font-size: 1.05rem;
      color: var(--charcoal);
      margin-bottom: 0.2rem;
      letter-spacing: -0.01em;
    }

    .job-company {
      font-size: 0.87rem;
      color: var(--warm-mid);
      line-height: 1.5;
    }

    .job-meta {
      display: flex;
      gap: 1.2rem;
      flex-wrap: wrap;
      font-size: 0.83rem;
      color: var(--warm-light);
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
      background: var(--sand);
      color: var(--warm-mid);
      padding: 0.3rem 0.7rem;
      border-radius: var(--radius-pill);
      font-size: 0.8rem;
      font-weight: 500;
      display: inline-block;
      transition: background 0.15s;
    }

    .chip:hover { background: var(--sage-pale); color: var(--sage-deep); }

    .badge {
      display: inline-block;
      padding: 0.4rem 0.8rem;
      border-radius: var(--radius-pill);
      font-size: 0.75rem;
      font-weight: 600;
      white-space: nowrap;
    }

    .badge-blue {
      background: var(--sage-pale);
      color: var(--sage-deep);
    }

    .badge-gray {
      background: var(--sand);
      color: var(--warm-mid);
    }

    .badge-purple {
      background: var(--gold-pale);
      color: var(--stone);
    }

    .job-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 1rem;
      padding-top: 0.75rem;
      border-top: 1px solid var(--sand-dark);
    }

    .job-salary {
      font-weight: 700;
      font-size: 0.95rem;
      color: var(--sage-deep);
    }

    .job-date {
      font-size: 0.8rem;
      color: var(--warm-light);
    }

    .job-footer-actions {
      display: flex;
      gap: 0.5rem;
    }

    /* Loading state */
    .loading-state {
      text-align: center;
      padding: 3rem 2rem;
      color: var(--warm-light);
    }

    .loading-spinner {
      width: 40px;
      height: 40px;
      border: 3px solid var(--sand-dark);
      border-top-color: var(--sage);
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
      margin: 0 auto 1rem;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    @keyframes fadeInSlide {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Pagination */
    .pagination {
      display: flex;
      justify-content: center;
      gap: 0.4rem;
      margin-top: 3rem;
      padding-top: 2rem;
      border-top: 1px solid var(--sand-dark);
    }

    .pg-btn {
      width: 36px;
      height: 36px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: var(--radius-pill);
      font-size: 0.87rem;
      font-weight: 500;
      border: 1.5px solid var(--sand-dark);
      background: #fff;
      color: var(--warm-mid);
      cursor: pointer;
      font-family: 'DM Sans', sans-serif;
      transition: all 0.2s;
    }

    .pg-btn:hover { background: var(--sand); border-color: var(--stone-light); }

    .pg-btn.active {
      background: var(--sage);
      border-color: var(--sage);
      color: white;
    }

    .no-results {
      text-align: center;
      padding: 3rem 2rem;
      color: var(--warm-light);
    }

    /* ── JOB DETAILS MODAL ── */
    .job-modal-backdrop {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(42, 42, 34, 0.55);
      z-index: 2500;
      align-items: center;
      justify-content: center;
      padding: 1.25rem;
      backdrop-filter: blur(4px);
    }

    .job-modal-backdrop.active {
      display: flex;
    }

    .job-modal {
      width: min(760px, 100%);
      background: #fff;
      border-radius: var(--radius-xl);
      box-shadow: 0 24px 60px rgba(42,42,34,0.25);
      overflow: hidden;
    }

    .job-modal-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 1rem;
      padding: 1.25rem 1.4rem;
      border-bottom: 1px solid var(--sand-dark);
      background: linear-gradient(135deg, var(--cream) 0%, var(--sage-pale) 100%);
    }

    .job-modal-title {
      margin: 0;
      font-family: 'Playfair Display', serif;
      font-size: 1.25rem;
      font-weight: 800;
      color: var(--charcoal);
      letter-spacing: -0.01em;
    }

    .job-modal-close {
      border: none;
      background: var(--white-t);
      color: var(--charcoal);
      width: 36px;
      height: 36px;
      border-radius: var(--radius-pill);
      cursor: pointer;
      font-size: 1.2rem;
      line-height: 1;
      padding: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 140ms ease;
      pointer-events: auto;
      position: relative;
      z-index: 10;
    }

    .job-modal-close:hover {
      background: #fff;
      transform: translateY(-1px);
    }

    .job-modal-close:active {
      transform: translateY(0);
    }

    .job-modal-body {
      padding: 1.4rem;
    }

    .job-modal-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
      margin: 0.8rem 0 1rem;
    }

    .job-modal-section {
      margin-top: 1rem;
    }

    .job-modal-section h4 {
      margin: 0 0 0.45rem;
      font-family: 'Playfair Display', serif;
      font-size: 0.95rem;
      color: var(--charcoal);
    }

    .job-modal-section p {
      margin: 0;
      color: var(--warm-mid);
      line-height: 1.6;
    }

    .job-modal-footer {
      display: flex;
      justify-content: flex-end;
      gap: 0.75rem;
      padding: 1rem 1.4rem 1.35rem;
      border-top: 1px solid var(--sand-dark);
    }

    /* ── SUCCESS MODAL ── */
    .success-modal-backdrop {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(42, 42, 34, 0.55);
      z-index: 3000;
      align-items: center;
      justify-content: center;
      padding: 1.25rem;
      backdrop-filter: blur(4px);
    }

    .success-modal-backdrop.active {
      display: flex;
      animation: fadeIn 0.3s ease-out;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .success-modal {
      width: min(480px, 100%);
      background: #fff;
      border-radius: var(--radius-xl);
      box-shadow: 0 24px 60px rgba(42,42,34,0.25);
      overflow: hidden;
      text-align: center;
      padding: 2.5rem;
      animation: slideUp 0.4s ease-out;
    }

    @keyframes slideUp {
      from { transform: translateY(20px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }

    .success-checkmark {
      width: 80px;
      height: 80px;
      margin: 0 auto 1.5rem;
      background: linear-gradient(135deg, var(--sage) 0%, var(--sage-deep) 100%);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      animation: scaleIn 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }

    @keyframes scaleIn {
      from { transform: scale(0); }
      to { transform: scale(1); }
    }

    .success-checkmark svg {
      width: 48px;
      height: 48px;
      stroke: white;
      stroke-width: 2;
      fill: none;
      stroke-linecap: round;
      stroke-linejoin: round;
    }

    .success-checkmark-line {
      stroke-dasharray: 50;
      stroke-dashoffset: 50;
      animation: drawLine 0.6s ease-out forwards;
      animation-delay: 0.3s;
    }

    .success-checkmark-circle {
      stroke-dasharray: 166;
      stroke-dashoffset: 166;
      animation: drawCircle 0.6s ease-out forwards;
      animation-delay: 0.2s;
    }

    @keyframes drawCircle {
      to { stroke-dashoffset: 0; }
    }

    @keyframes drawLine {
      to { stroke-dashoffset: 0; }
    }

    .success-title {
      font-family: 'Playfair Display', serif;
      font-size: 1.5rem;
      font-weight: 800;
      color: var(--charcoal);
      margin-bottom: 0.5rem;
    }

    .success-message {
      font-size: 0.95rem;
      color: var(--warm-mid);
      margin-bottom: 1.5rem;
      line-height: 1.5;
    }

    .success-details {
      background: var(--sand);
      border-radius: var(--radius-lg);
      padding: 1.25rem;
      margin-bottom: 1.5rem;
      text-align: left;
    }

    .success-detail-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.6rem 0;
      border-bottom: 1px solid var(--sand-dark);
    }

    .success-detail-row:last-child {
      border-bottom: none;
    }

    .success-detail-label {
      font-weight: 600;
      color: var(--warm-mid);
      font-size: 0.85rem;
    }

    .success-detail-value {
      font-weight: 700;
      color: var(--charcoal);
      text-align: right;
    }

    .success-actions {
      display: flex;
      gap: 0.75rem;
      justify-content: center;
    }

    /* ── FOOTER ── */
    .footer {
      background: #1e1e18;
      color: rgba(255,255,255,0.5);
      padding: 4rem 2rem 2rem;
      margin-top: 3rem;
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
    .reveal-delay-5 { transition-delay: 0.5s; }

    /* ── RESPONSIVE ── */
    @media (max-width: 960px) {
      .main-layout { grid-template-columns: 1fr; }
      .footer-top { grid-template-columns: 1fr 1fr; }
      .nav-links { display: none; }
    }

    @media (max-width: 600px) {
      .navbar { padding: 0 1.2rem; }
      .search-bar-wrap { padding: 1rem 1.2rem; }
      .main-layout { padding: 1.2rem; }
      .footer-top { grid-template-columns: 1fr; }
      .footer-bottom { flex-direction: column; text-align: center; }
      .search-bar { flex-direction: column; }
      .search-input-rel { width: 100%; }
    }
  </style>
</head>

<body>
  <!-- NAVBAR -->
  <nav class="navbar">
    <a href="../../index.php" class="nav-logo">
      <div class="nav-logo-mark">TS</div>
      <span class="nav-logo-text">Talent<span>Scout</span> AI</span>
    </a>
    <ul class="nav-links">
      <li><a href="../../index.php">Home</a></li>
      <li><a href="./index.php" class="active">Browse Jobs</a></li>
      <li><a href="../ai-matching/index.php">AI Matching</a></li>
      <li><a href="../resume-builder/index.php">Resume Builder</a></li>
      <li><a href="../skill-gap-analysis/index.php">Skills</a></li>
      <li><a href="../applicant-tracking/index.php">Applications</a></li>
      <li><a href="../messages/index.php">Messages</a></li>
    </ul>
    <div class="nav-actions">
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
    <div class="page-header-inner">
      <div class="breadcrumb">
        <a href="../../index.php">Home</a> / Job Postings
      </div>
      <h1>Job Postings</h1>
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
        <span class="search-icon">&#128269;</span>
        <input
          id="searchInput"
          type="text"
          class="input"
          placeholder="Search job title, skills, or company..." />
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
        class="btn-primary">Search Jobs</button>
      <button
        id="resetBtn"
        class="btn-secondary">Reset Filters</button>
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
          <span class="fcount">&#8212;</span>
        </div>
        <div class="filter-item" data-group="workSetup" data-value="Remote">
          <div class="filter-left">
            <div class="fcheck"></div>
            <span>Remote</span>
          </div>
          <span class="fcount">&#8212;</span>
        </div>
        <div class="filter-item" data-group="workSetup" data-value="Hybrid">
          <div class="filter-left">
            <div class="fcheck"></div>
            <span>Hybrid</span>
          </div>
          <span class="fcount">&#8212;</span>
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
            <span>&#8369;<span id="minLabel">0</span></span>
            <span>&#8369;<span id="maxLabel">60,000</span></span>
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
          <span class="fcount">&#8212;</span>
        </div>
        <div class="filter-item" data-group="experienceLevel" data-value="Mid Level">
          <div class="filter-left">
            <div class="fcheck"></div>
            <span>Mid Level</span>
          </div>
          <span class="fcount">&#8212;</span>
        </div>
        <div class="filter-item" data-group="experienceLevel" data-value="Senior Level">
          <div class="filter-left">
            <div class="fcheck"></div>
            <span>Senior Level</span>
          </div>
          <span class="fcount">&#8212;</span>
        </div>
      </div>

      <div class="sidebar-card">
        <div class="sidebar-title">Posted Within</div>
        <div class="filter-item" data-group="postedWithin" data-value="Last 7 days">
          <div class="filter-left">
            <div class="fcheck"></div>
            <span>Last 7 days</span>
          </div>
          <span class="fcount">&#8212;</span>
        </div>
        <div class="filter-item" data-group="postedWithin" data-value="Last 30 days">
          <div class="filter-left">
            <div class="fcheck"></div>
            <span>Last 30 days</span>
          </div>
          <span class="fcount">&#8212;</span>
        </div>
        <div class="filter-item" data-group="postedWithin" data-value="All time">
          <div class="filter-left">
            <div class="fcheck"></div>
            <span>All time</span>
          </div>
          <span class="fcount">&#8212;</span>
        </div>
      </div>
    </aside>

    <!-- JOB LISTINGS -->
    <main id="jobsMain">
      <section class="jobs-map-section">
        <div class="jobs-map-header">
          <div>
            <div class="jobs-map-title">Jobs Map</div>
            <div class="jobs-map-subtitle">Pins are placed from each job's saved location. Click a pin to view details.</div>
          </div>
          <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap">
            <select id="mapThemeDropdown" class="map-theme-select">
              <option value="default">Street View</option>
              <option value="satellite">Satellite</option>
              <option value="terrain">Terrain</option>
              <option value="topo">Topographic</option>
              <option value="dark">Dark Mode</option>
            </select>
            <button type="button" class="map-overlay-btn" id="trafficToggleBtn" title="Show Traffic">
              &#128663; Traffic
            </button>
            <button type="button" class="map-overlay-btn" id="fullscreenMapBtn" title="Full Screen">
              &#128269; Expand
            </button>
            <div class="radius-control" id="radiusControl">
              <label for="radiusInput">Radius:</label>
              <input type="number" id="radiusInput" value="10" min="1" max="50" />
              <span>km</span>
              <button type="button" class="btn-clear-location" id="clearLocationBtn">Clear</button>
            </div>
            <button type="button" class="btn-location" id="pinLocationBtn">
              &#128205; Pin My Location
            </button>
          </div>
        </div>
        <div id="jobsMap" class="jobs-map">
          <div class="directions-panel" id="directionsPanel">
            <div class="directions-header">
              <h4 id="directionsTitle">Directions to Job</h4>
              <button type="button" class="directions-close" id="directionsCloseBtn">&times;</button>
            </div>
            <div class="directions-body" id="directionsBody">
            </div>
          </div>
        </div>
        <button type="button" class="map-close-fullscreen" id="closeFullscreenMapBtn" title="Exit Full Screen">&times;</button>
        <div style="margin-top:0.5rem;font-size:0.8rem;color:var(--warm-light)" id="mapStatus">Loading map pins...</div>
      </section>

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

  <div id="jobDetailsModal" class="job-modal-backdrop" aria-hidden="true">
    <div class="job-modal" role="dialog" aria-modal="true" aria-labelledby="jobDetailsTitle">
      <div class="job-modal-header">
        <div>
          <h3 class="job-modal-title" id="jobDetailsTitle">Job Details</h3>
          <div class="job-company" id="jobDetailsCompany"></div>
        </div>
        <button type="button" class="job-modal-close" id="closeJobDetailsModal" aria-label="Close details">&times;</button>
      </div>
      <div class="job-modal-body">
        <div class="job-modal-meta" id="jobDetailsMeta"></div>
        <div class="job-modal-section">
          <h4>Description</h4>
          <p id="jobDetailsDescription"></p>
        </div>
        <div class="job-modal-section">
          <h4>Required Skills</h4>
          <p id="jobDetailsSkills"></p>
        </div>
        <div class="job-modal-section">
          <h4>Location</h4>
          <p id="jobDetailsLocation"></p>
        </div>
        <div class="job-modal-section">
          <h4>Salary &amp; Deadline</h4>
          <p id="jobDetailsSalary"></p>
        </div>
      </div>
      <div class="job-modal-footer">
        <button type="button" class="btn-secondary" id="jobDetailsDirections" onclick="handleDirectionsFromModal()" style="display:none">&#128663; Directions</button>
        <button type="button" class="btn-primary" id="jobDetailsApply" onclick="handleApplyClick()">Apply Now</button>
      </div>
    </div>
  </div>

  <!-- Success Animation Modal -->
  <div id="successModal" class="success-modal-backdrop" aria-hidden="true">
    <div class="success-modal" role="dialog" aria-modal="true">
      <button type="button" id="closeSuccessXBtn" class="job-modal-close" aria-label="Close success dialog" style="position: absolute; top: 16px; right: 16px; z-index: 20;">&times;</button>
      <div class="success-checkmark">
        <svg viewBox="0 0 24 24">
          <circle class="success-checkmark-circle" cx="12" cy="12" r="10"></circle>
          <polyline class="success-checkmark-line" points="8 12 11 15 16 9"></polyline>
        </svg>
      </div>
      <h3 class="success-title">Application Submitted!</h3>
      <p class="success-message">
        Your application has been successfully submitted. Check your Application Tracker for updates.
      </p>
      <div class="success-details" id="successDetails">
        <div class="success-detail-row">
          <span class="success-detail-label">Job Title</span>
          <span class="success-detail-value" id="successJobTitle">&#8212;</span>
        </div>
        <div class="success-detail-row">
          <span class="success-detail-label">Company</span>
          <span class="success-detail-value" id="successCompany">&#8212;</span>
        </div>
        <div class="success-detail-row">
          <span class="success-detail-label">Applied Date</span>
          <span class="success-detail-value" id="successDate">&#8212;</span>
        </div>
        <div class="success-detail-row">
          <span class="success-detail-label">Status</span>
          <span class="success-detail-value" id="successStatus">Pending</span>
        </div>
      </div>
      <div class="success-actions">
        <button type="button" class="btn-secondary" id="viewApplicationsBtn">View Applications</button>
        <button type="button" class="btn-primary" id="closeSuccessBtn">Browse More Jobs</button>
      </div>
    </div>
  </div>

  <!-- Data passed from PHP to JavaScript -->
  <script>
    window.dbJobs = <?php echo $jobsJson; ?>;
    window.dbError = <?php echo json_encode($dbError); ?>;
    window.isLoggedIn = <?php echo isset($_SESSION['employee_id']) ? 'true' : 'false'; ?>;
    window.employeeName = <?php echo json_encode($_SESSION['employee_name'] ?? ''); ?>;
    window.employeeId = <?php echo isset($_SESSION['employee_id']) ? (int)$_SESSION['employee_id'] : 0; ?>;
  </script>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script src="../../employee-auth.js"></script>
  <script>
    // ─── All jobs loaded from DB ───────────────────────────────────────────
    let allJobs = [];
    let loadErrorMessage = '';
    let salaryMaxValue = 60000;
    let currentPage = 1;
    let jobsMap = null;
    let jobsMarkers = [];
    let jobsGeoCache = new Map();
    let jobsGeoQueue = Promise.resolve();
    let jobsMapRenderToken = 0;
    let activeJobForDetails = null;
    let userLocationMarker = null;
    let userRadiusCircle = null;
    let userLocation = null;
    let filterRadiusKm = 10;
    let currentMapLayer = null;
    const mapLayers = {};
    let trafficLayer = null;
    let isTrafficOn = false;
    let isFullscreen = false;
    let directionRouteLine = null;
    let directionStepLines = [];
    let currentActiveStep = -1;

    function initMapLayers() {
      if (!window.L) return;

      mapLayers.default = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19
      });

      mapLayers.satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: '&copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community',
        maxZoom: 18
      });

      mapLayers.terrain = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenTopoMap (CC-BY-SA)',
        maxZoom: 17
      });

      mapLayers.topo = L.tileLayer('https://{s}.tile.stamen.com/terrain-background/{z}/{x}/{y}.jpg', {
        attribution: '&copy; Stamen Design',
        maxZoom: 18
      });

      mapLayers.dark = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; CARTO',
        maxZoom: 19
      });
    }

    function switchMapLayer(theme) {
      if (!jobsMap || !mapLayers[theme]) return;

      if (currentMapLayer) {
        jobsMap.removeLayer(currentMapLayer);
      }

      currentMapLayer = mapLayers[theme].addTo(jobsMap);
      jobsMap.invalidateSize();

      const dropdown = document.getElementById('mapThemeDropdown');
      if (dropdown) dropdown.value = theme;
    }

    function toggleTraffic() {
      if (!jobsMap) return;

      isTrafficOn = !isTrafficOn;
      const btn = document.getElementById('trafficToggleBtn');

      if (isTrafficOn) {
        if (!trafficLayer) {
          trafficLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; CARTO',
            maxZoom: 19
          });
        }
        trafficLayer.addTo(jobsMap);
        btn.classList.add('active', 'traffic-active');
        btn.innerHTML = '&#128663; Traffic';
      } else {
        if (trafficLayer && jobsMap.hasLayer(trafficLayer)) {
          jobsMap.removeLayer(trafficLayer);
        }
        btn.classList.remove('active', 'traffic-active');
      }
    }

    async function fetchDirections(fromLat, fromLng, toLat, toLng) {
      try {
        const response = await fetch(
          `https://router.project-osrm.org/route/v1/driving/${fromLng},${fromLat};${toLng},${toLat}?overview=full&geometries=geojson&steps=true`
        );
        const data = await response.json();

        if (data.code !== 'Ok' || !data.routes || !data.routes.length) {
          return null;
        }

        return data.routes[0];
      } catch (error) {
        console.error('Directions fetch error:', error);
        return null;
      }
    }

    function displayDirections(route, jobTitle, jobLocation) {
      const panel = document.getElementById('directionsPanel');
      const body = document.getElementById('directionsBody');
      const title = document.getElementById('directionsTitle');

      if (!panel || !body || !title) return;

      title.textContent = `Directions to ${jobTitle}`;
      panel.classList.add('visible');

      if (!route) {
        body.innerHTML = `
          <div class="directions-error">
            Unable to calculate route. Route will be shown on the map when available.
          </div>
          <div class="directions-actions" style="justify-content:center">
            <button type="button" class="btn-close-directions" onclick="hideDirections()">Close</button>
          </div>
        `;
        return;
      }

      const distanceKm = (route.distance / 1000).toFixed(1);
      const durationMin = Math.round(route.duration / 60);
      const steps = route.legs[0].steps;

      // Draw step lines on map for hover/click functionality
      drawStepLines(steps);

      let stepsHtml = '<ol class="direction-steps">';
      steps.forEach((step, i) => {
        const stepDist = step.distance > 1000
          ? `${(step.distance / 1000).toFixed(1)} km`
          : `${Math.round(step.distance)} m`;
        const instruction = step.maneuver.instruction || step.name || 'Continue';
        stepsHtml += `
          <li data-step="${i}" onmouseenter="window.highlightStep(${i})" onmouseleave="window.unhighlightStep(${i})" onclick="window.focusStep(${i})">
            <span class="step-number">${i + 1}</span>
            <div>
              <div>${escapeHtml(instruction)}</div>
              <div style="font-size:0.75rem;color:var(--warm-light);margin-top:0.15rem">${stepDist}</div>
            </div>
          </li>
        `;
      });
      stepsHtml += '</ol>';

      body.innerHTML = `
        <div class="direction-route-summary">
          <div class="direction-stat">
            &#128205; <strong>${distanceKm} km</strong>
          </div>
          <div class="direction-stat">
            &#9201; <strong>${durationMin} min</strong>
          </div>
          <div class="direction-stat">
            &#128663; Driving
          </div>
        </div>
        ${stepsHtml}
        <div class="directions-actions" style="justify-content:center">
          <button type="button" class="btn-close-directions" onclick="hideDirections()">Close</button>
        </div>
      `;
    }

    function drawStepLines(steps) {
      // Clear existing step lines
      directionStepLines.forEach(line => {
        if (jobsMap && jobsMap.hasLayer(line)) {
          jobsMap.removeLayer(line);
        }
      });
      directionStepLines = [];

      if (!jobsMap || !steps.length) return;

      // Draw each step as a separate line with white outline
      steps.forEach((step, i) => {
        if (step.geometry && step.geometry.coordinates) {
          // White outline
          L.geoJSON({
            type: 'Feature',
            geometry: step.geometry
          }, {
            style: {
              color: '#ffffff',
              weight: 8,
              opacity: 0.5
            }
          }).addTo(jobsMap);

          const line = L.geoJSON({
            type: 'Feature',
            geometry: step.geometry
          }, {
            style: {
              color: '#2563eb',
              weight: 5,
              opacity: 0.85,
              dashArray: i === 0 ? null : '6, 4'
            }
          }).addTo(jobsMap);
          line._stepIndex = i;
          directionStepLines.push(line);
        }
      });
    }

    window.highlightStep = function(stepIndex) {
      directionStepLines.forEach(line => {
        if (line._stepIndex === stepIndex) {
          if (jobsMap && jobsMap.hasLayer(line)) {
            line.setStyle({ weight: 8, opacity: 1, color: '#1d4ed8', dashArray: null });
          }
        }
      });
    };

    window.unhighlightStep = function(stepIndex) {
      directionStepLines.forEach(line => {
        if (line._stepIndex === stepIndex) {
          if (jobsMap && jobsMap.hasLayer(line)) {
            line.setStyle({ weight: 5, opacity: 0.7, color: '#2563eb', dashArray: line._stepIndex === 0 ? null : '6, 4' });
          }
        }
      });
      // Reset active step styling
      document.querySelectorAll('.direction-steps li.active-step').forEach(li => {
        li.classList.remove('active-step');
      });
    };

    window.focusStep = function(stepIndex) {
      const line = directionStepLines.find(l => l._stepIndex === stepIndex);
      if (line && jobsMap) {
        jobsMap.fitBounds(line.getBounds(), { padding: [40, 40] });
      }

      // Highlight in list
      document.querySelectorAll('.direction-steps li').forEach((li, i) => {
        li.classList.toggle('active-step', i === stepIndex);
      });

      // Highlight on map
      directionStepLines.forEach(l => {
        if (jobsMap && jobsMap.hasLayer(l)) {
          if (l._stepIndex === stepIndex) {
            l.setStyle({ weight: 8, opacity: 1, color: '#f59e0b', dashArray: null });
          } else {
            l.setStyle({ weight: 3, opacity: 0.3, color: '#2563eb', dashArray: '6, 4' });
          }
        }
      });
    };

    function hideDirections() {
      const panel = document.getElementById('directionsPanel');
      if (panel) {
        panel.classList.remove('visible');
      }
      if (directionRouteLine && jobsMap) {
        jobsMap.removeLayer(directionRouteLine);
        directionRouteLine = null;
      }
      // Clear step lines
      directionStepLines.forEach(line => {
        if (jobsMap && jobsMap.hasLayer(line)) {
          jobsMap.removeLayer(line);
        }
      });
      directionStepLines = [];
      currentActiveStep = -1;
    }

    async function showRouteToJob(job) {
      if (!userLocation || !jobsMap) return;

      hideDirections();

      const geo = jobsGeoCache.get(getCachedGeoKey(job.location));
      if (!geo) {
        alert('Location not available for this job.');
        return;
      }

      const panel = document.getElementById('directionsPanel');
      const body = document.getElementById('directionsBody');
      if (panel) panel.classList.add('visible');
      if (body) {
        body.innerHTML = `
          <div class="directions-loading">
            <div class="loading-spinner"></div>
            <p>Calculating route to ${escapeHtml(job.title)}...</p>
          </div>
        `;
      }

      const route = await fetchDirections(userLocation.lat, userLocation.lng, geo.lat, geo.lng);

      if (directionRouteLine && jobsMap) {
        jobsMap.removeLayer(directionRouteLine);
      }

      if (route && route.geometry) {
        // White outline for visibility on all themes
        L.geoJSON(route.geometry, {
          style: {
            color: '#ffffff',
            weight: 10,
            opacity: 0.6
          }
        }).addTo(jobsMap);

        directionRouteLine = L.geoJSON(route.geometry, {
          style: {
            color: '#2563eb',
            weight: 6,
            opacity: 0.95
          }
        }).addTo(jobsMap);
      }

      displayDirections(route, job.title, job.location);
    }

    function toggleFullscreen() {
      const section = document.querySelector('.jobs-map-section');
      if (!section) return;

      isFullscreen = !isFullscreen;
      const btn = document.getElementById('fullscreenMapBtn');

      if (isFullscreen) {
        section.classList.add('fullscreen');
        btn.innerHTML = '&#10060; Shrink';
      } else {
        section.classList.remove('fullscreen');
        btn.innerHTML = '&#128269; Expand';
      }

      if (jobsMap) {
        setTimeout(() => {
          jobsMap.invalidateSize();
        }, 100);
      }
    }

    // Haversine formula to calculate distance between two points in km
    function calculateDistance(lat1, lon1, lat2, lon2) {
      const R = 6371; // Earth's radius in km
      const dLat = (lat2 - lat1) * Math.PI / 180;
      const dLon = (lon2 - lon1) * Math.PI / 180;
      const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLon / 2) * Math.sin(dLon / 2);
      const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
      return R * c;
    }

    function isJobWithinRadius(job, userLat, userLng, radiusKm) {
      if (!job.location || job.location === 'Not specified') return false;
      const geo = jobsGeoCache.get(getCachedGeoKey(job.location));
      if (!geo) return false;
      const distance = calculateDistance(userLat, userLng, geo.lat, geo.lng);
      job._distanceFromUser = distance;
      return distance <= radiusKm;
    }

    async function pinUserLocation() {
      const btn = document.getElementById('pinLocationBtn');
      const status = document.getElementById('mapStatus');
      const radiusControl = document.getElementById('radiusControl');

      if (!navigator.geolocation) {
        status.textContent = 'Geolocation is not supported by your browser';
        return;
      }

      btn.disabled = true;
      btn.innerHTML = '&#9203; Getting location...';
      status.textContent = 'Requesting your location...';

      navigator.geolocation.getCurrentPosition(
        async (position) => {
          const lat = position.coords.latitude;
          const lng = position.coords.longitude;
          userLocation = { lat, lng };
          filterRadiusKm = parseInt(document.getElementById('radiusInput').value) || 10;

          // Add user marker to map
          if (jobsMap) {
            if (userLocationMarker) {
              jobsMap.removeLayer(userLocationMarker);
            }
            if (userRadiusCircle) {
              jobsMap.removeLayer(userRadiusCircle);
            }

            const redPinIcon = L.divIcon({
              html: `
                <div style="position:relative;width:32px;height:44px">
                  <svg viewBox="0 0 24 36" xmlns="http://www.w3.org/2000/svg" style="width:32px;height:44px;filter:drop-shadow(0 2px 4px rgba(0,0,0,0.3))">
                    <path d="M12 0C5.373 0 0 5.373 0 12c0 9 12 24 12 24s12-15 12-24c0-6.627-5.373-12-12-12zm0 17c-2.761 0-5-2.239-5-5s2.239-5 5-5 5 2.239 5 5-2.239 5-5 5z" fill="#dc2626"/>
                    <circle cx="12" cy="12" r="4" fill="#fff"/>
                  </svg>
                </div>
              `,
              className: '',
              iconSize: [32, 44],
              iconAnchor: [16, 44],
              popupAnchor: [0, -44]
            });

            userLocationMarker = L.marker([lat, lng], { icon: redPinIcon })
              .addTo(jobsMap)
              .bindPopup('<strong>Your Location</strong><br>Jobs filtered within ' + filterRadiusKm + 'km radius');

            userRadiusCircle = L.circle([lat, lng], {
              radius: filterRadiusKm * 1000,
              color: '#c8a96e',
              fillColor: '#c8a96e',
              fillOpacity: 0.1,
              weight: 2,
              dashArray: '5, 10'
            }).addTo(jobsMap);

            jobsMap.setView([lat, lng], 12);
          }

          // Update UI
          btn.classList.add('active');
          btn.innerHTML = '&#9989; Location Pinned';
          btn.disabled = false;
          radiusControl.classList.add('visible');
          status.textContent = `Showing jobs within ${filterRadiusKm}km of your location`;

          // Re-apply filters with location constraint
          applyFilters();
        },
        (error) => {
          let errorMsg = 'Unable to retrieve your location';
          switch (error.code) {
            case error.PERMISSION_DENIED:
              errorMsg = 'Location permission denied. Please enable location access.';
              break;
            case error.POSITION_UNAVAILABLE:
              errorMsg = 'Location information unavailable.';
              break;
            case error.TIMEOUT:
              errorMsg = 'Location request timed out.';
              break;
          }
          status.textContent = errorMsg;
          btn.disabled = false;
          btn.innerHTML = '&#128205; Pin My Location';
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
      );
    }

    function clearLocationFilter() {
      const btn = document.getElementById('pinLocationBtn');
      const status = document.getElementById('mapStatus');
      const radiusControl = document.getElementById('radiusControl');

      userLocation = null;

      if (jobsMap) {
        if (userLocationMarker) {
          jobsMap.removeLayer(userLocationMarker);
          userLocationMarker = null;
        }
        if (userRadiusCircle) {
          jobsMap.removeLayer(userRadiusCircle);
          userRadiusCircle = null;
        }
        if (directionRouteLine) {
          jobsMap.removeLayer(directionRouteLine);
          directionRouteLine = null;
        }
      }

      hideDirections();

      btn.classList.remove('active');
      btn.innerHTML = '&#128205; Pin My Location';
      radiusControl.classList.remove('visible');
      status.textContent = 'Location filter cleared';

      // Remove distance property from jobs
      allJobs.forEach(job => delete job._distanceFromUser);

      applyFilters();
    }

    // ─── Filter state ──────────────────────────────────────────────────────
    const filterState = {
      searchQuery: '',
      barangay: 'All Barangays',
      jobType: 'All Job Types',
      category: 'All Job Category',
      workSetup: [],
      salaryRange: {
        min: 0,
        max: 60000
      },
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
      if (!nums.length) return {
        min: 0,
        max: 0,
        text: text || 'Not specified'
      };
      const min = nums[0];
      const max = nums.length > 1 ? nums[nums.length - 1] : nums[0];
      return {
        min,
        max,
        text: min === max ?
          `&#8369;${min.toLocaleString()} / mo` :
          `&#8369;${min.toLocaleString()} &#8211; &#8369;${max.toLocaleString()} / mo`
      };
    }

    function formatDeadline(val) {
      if (!val) return 'Not set';
      const d = new Date(val);
      if (isNaN(d.getTime())) return String(val);
      return d.toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
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

    function escapeHtml(val) {
      return String(val || '').replace(/[&<>"]+/g, function(chr) {
        return ({
          '&': '&amp;',
          '<': '&lt;',
          '>': '&gt;',
          '"': '&quot;'
        })[chr] || chr;
      });
    }

    function formatJobDetails(job) {
      const skills = job.skills.length ? job.skills.join(', ') : 'Not specified';
      const deadline = formatDeadline(job.applicationDeadline);
      return {
        title: job.title || 'Job Details',
        company: job.category || 'TalentScout AI',
        description: job.description || 'No description provided.',
        skills,
        location: job.location || 'Not specified',
        salary: job.salaryText || 'Not specified',
        deadline,
        setup: job.setup || 'Not specified',
        type: job.type || 'Not specified',
        level: job.level || 'Not specified',
        category: job.category || 'Not specified'
      };
    }

    function openJobDetailsModal(job) {
      const details = formatJobDetails(job);
      activeJobForDetails = job;

      document.getElementById('jobDetailsTitle').textContent = details.title;
      document.getElementById('jobDetailsCompany').textContent = `${details.type} • ${details.level} • ${details.setup}`;
      document.getElementById('jobDetailsDescription').textContent = details.description;
      document.getElementById('jobDetailsSkills').textContent = details.skills;
      document.getElementById('jobDetailsLocation').textContent = details.location;
      document.getElementById('jobDetailsSalary').textContent = `${details.salary} | Application deadline: ${details.deadline}`;
      document.getElementById('jobDetailsApply').href = window.isLoggedIn ? '../applicant-tracking/index.php' : '../../login.php';

      const directionsBtn = document.getElementById('jobDetailsDirections');
      if (directionsBtn) {
        directionsBtn.style.display = userLocation ? 'inline-block' : 'none';
      }

      const meta = document.getElementById('jobDetailsMeta');
      meta.innerHTML = '';
      [details.setup, details.type, details.level, details.category].forEach(value => {
        const badge = document.createElement('span');
        badge.className = 'badge badge-gray';
        badge.textContent = value;
        meta.appendChild(badge);
      });

      document.getElementById('jobDetailsModal').classList.add('active');
      document.getElementById('jobDetailsModal').setAttribute('aria-hidden', 'false');
    }

    window.openJobDetailsById = function(jobId) {
      const job = allJobs.find(item => item.id === Number(jobId));
      if (job) {
        openJobDetailsModal(job);
      }
    };

    window.showRouteToJobById = function(jobId) {
      const job = allJobs.find(item => item.id === Number(jobId));
      if (job) {
        showRouteToJob(job);
      }
    };

    function closeJobDetailsModal() {
      document.getElementById('jobDetailsModal').classList.remove('active');
      document.getElementById('jobDetailsModal').setAttribute('aria-hidden', 'true');
      activeJobForDetails = null;
    }

    function showSuccessAnimation(applicationData) {
      const modal = document.getElementById('successModal');
      
      // Populate success modal with application data
      document.getElementById('successJobTitle').textContent = applicationData.job_title;
      document.getElementById('successCompany').textContent = applicationData.company_name;
      document.getElementById('successDate').textContent = new Date(applicationData.application_date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
      document.getElementById('successStatus').textContent = applicationData.status;

      // Close job details modal
      closeJobDetailsModal();

      // Show success modal with animation
      modal.classList.add('active');
      modal.setAttribute('aria-hidden', 'false');
    }

    function closeSuccessModal() {
      const modal = document.getElementById('successModal');
      modal.classList.remove('active');
      modal.setAttribute('aria-hidden', 'true');
    }

    async function submitApplication(jobPostId) {
      if (!window.isLoggedIn) {
        window.location.href = '../../login.php';
        return;
      }

      try {
        const formData = new FormData();
        formData.append('job_post_id', jobPostId);

        const response = await fetch('./submit-application.php', {
          method: 'POST',
          body: formData
        });

        const data = await response.json();

        if (response.ok && data.success) {
          showSuccessAnimation(data.application);
        } else {
          alert(data.message || 'Failed to submit application. Please try again.');
        }
      } catch (error) {
        console.error('Application submission error:', error);
        alert('An error occurred. Please try again.');
      }
    }

    function handleApplyClick() {
      if (activeJobForDetails) {
        submitApplication(activeJobForDetails.id);
      }
    }

    function handleDirectionsFromModal() {
      if (activeJobForDetails) {
        showRouteToJob(activeJobForDetails);
        closeJobDetailsModal();
      }
    }

    function getCachedGeoKey(locationText) {
      return String(locationText || '').trim().toLowerCase();
    }

    async function geocodeLocation(locationText) {
      const query = String(locationText || '').trim();
      if (!query) return null;

      const cacheKey = getCachedGeoKey(query);
      if (jobsGeoCache.has(cacheKey)) {
        return jobsGeoCache.get(cacheKey);
      }

      try {
        const response = await fetch(`https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q=${encodeURIComponent(query)}`, {
          headers: {
            'Accept': 'application/json'
          }
        });

        if (!response.ok) {
          jobsGeoCache.set(cacheKey, null);
          return null;
        }

        const results = await response.json();
        if (!Array.isArray(results) || !results.length) {
          jobsGeoCache.set(cacheKey, null);
          return null;
        }

        const point = {
          lat: parseFloat(results[0].lat),
          lng: parseFloat(results[0].lon),
          label: results[0].display_name || query
        };

        if (Number.isNaN(point.lat) || Number.isNaN(point.lng)) {
          jobsGeoCache.set(cacheKey, null);
          return null;
        }

        jobsGeoCache.set(cacheKey, point);
        return point;
      } catch (error) {
        console.error('Geocoding failed for location:', query, error);
        jobsGeoCache.set(cacheKey, null);
        return null;
      }
    }

    function initJobsMap() {
      if (!window.L || jobsMap) return;

      initMapLayers();

      jobsMap = L.map('jobsMap', {
        scrollWheelZoom: false,
        zoomControl: true
      }).setView([14.068, 120.633], 12);

      currentMapLayer = mapLayers.default.addTo(jobsMap);
    }

    function clearJobMarkers() {
      if (!jobsMap) return;
      jobsMarkers.forEach(marker => jobsMap.removeLayer(marker));
      jobsMarkers = [];
    }

    function buildMarkerPopup(job) {
      const title = escapeHtml(job.title);
      const location = escapeHtml(job.location);
      const salary = escapeHtml(job.salaryText);
      const type = escapeHtml(job.type);
      const directionsBtn = userLocation ?
        `<button type="button" class="btn-secondary" onclick="window.showRouteToJobById(${job.id})" style="padding:0.35rem 0.65rem;font-size:0.78rem;margin-top:0.35rem;width:100%">&#128663; Get Directions</button>` : '';
      return `
          <div style="min-width:220px;max-width:260px">
            <div style="font-weight:700;font-size:0.95rem;margin-bottom:0.25rem">${title}</div>
            <div style="font-size:0.82rem;color:#64748b;margin-bottom:0.4rem">${location}</div>
            <div style="font-size:0.82rem;margin-bottom:0.35rem">${salary}</div>
            <div style="font-size:0.8rem;color:#64748b;margin-bottom:0.65rem">${type}</div>
            <button type="button" class="btn-primary" onclick="window.openJobDetailsById(${job.id})" style="padding:0.45rem 0.75rem;font-size:0.82rem;width:100%">View Details</button>
            ${directionsBtn}
          </div>
        `;
    }

    async function renderJobsMap(jobs) {
      const token = ++jobsMapRenderToken;
      jobsGeoQueue = jobsGeoQueue.then(() => renderJobsMapInternal(jobs, token));
      return jobsGeoQueue;
    }

    async function renderJobsMapInternal(jobs, token) {
      const status = document.getElementById('mapStatus');
      if (!status) return;
      if (token !== jobsMapRenderToken) return;

      initJobsMap();
      if (!jobsMap) return;

      clearJobMarkers();
      status.textContent = jobs.length ? 'Geocoding job locations...' : 'No jobs to show';

      const geocodedJobs = [];
      for (const job of jobs) {
        if (token !== jobsMapRenderToken) return;
        const geo = await geocodeLocation(job.location);
        if (token !== jobsMapRenderToken) return;
        if (geo) {
          geocodedJobs.push({
            job,
            geo
          });
        }
      }

      if (!geocodedJobs.length) {
        status.textContent = 'No map pins available for the current results';
        return;
      }

      const bounds = [];
      geocodedJobs.forEach(({
        job,
        geo
      }) => {
        const marker = L.marker([geo.lat, geo.lng]).addTo(jobsMap);
        marker._job = job;
        marker.bindPopup(buildMarkerPopup(job));
        marker.on('click', () => openJobDetailsModal(job));
        jobsMarkers.push(marker);
        bounds.push([geo.lat, geo.lng]);
      });

      if (bounds.length === 1) {
        jobsMap.setView(bounds[0], 14);
      } else if (bounds.length > 1) {
        jobsMap.fitBounds(bounds, {
          padding: [30, 30]
        });
      }

      // Re-add user location marker and radius if active
      if (userLocation && userLocationMarker && userRadiusCircle) {
        if (!jobsMap.hasLayer(userLocationMarker)) {
          userLocationMarker.addTo(jobsMap);
        }
        if (!jobsMap.hasLayer(userRadiusCircle)) {
          userRadiusCircle.addTo(jobsMap);
        }
      }

      // Re-add route line if active
      if (directionRouteLine && jobsMap && !jobsMap.hasLayer(directionRouteLine)) {
        directionRouteLine.addTo(jobsMap);
      }

      // Re-draw step lines if they exist
      if (directionStepLines.length > 0) {
        const panel = document.getElementById('directionsPanel');
        if (panel && panel.classList.contains('visible')) {
          directionStepLines.forEach(line => {
            if (jobsMap && !jobsMap.hasLayer(line)) {
              line.addTo(jobsMap);
            }
          });
        }
      }

      // Re-add traffic overlay if active
      if (isTrafficOn && trafficLayer && jobsMap && !jobsMap.hasLayer(trafficLayer)) {
        trafficLayer.addTo(jobsMap);
      }

      status.textContent = `${geocodedJobs.length} map pin${geocodedJobs.length === 1 ? '' : 's'} loaded`;
    }

    function normalizeWorkType(val) {
      const v = String(val || '').trim().toLowerCase();
      if (v.includes('remote')) return 'Remote';
      if (v.includes('hybrid')) return 'Hybrid';
      if (v.includes('contract')) return 'Contract';
      if (v.includes('part')) return 'Part-time';
      return 'Full-time';
    }

    function inferCategory(title, desc, skills) {
      const t = `${title} ${desc} ${skills.join(' ')}`.toLowerCase();
      if (/nurse|doctor|medical|clinic|hospital|health/.test(t)) return 'Healthcare';
      if (/account|finance|bookkeep|payroll|audit|tax/.test(t)) return 'Finance & Accounting';
      if (/teacher|tutor|school|education|curriculum/.test(t)) return 'Education';
      if (/construction|electrician|plumber|mason|foreman|site/.test(t)) return 'Construction';
      if (/chef|cook|kitchen|hotel|restaurant|hospitality|barista/.test(t)) return 'Hospitality';
      if (/customer service|call center|support|csr|helpdesk/.test(t)) return 'Customer Service';
      if (/farm|agri|agriculture|crop|livestock|harvest/.test(t)) return 'Agriculture';
      return 'Technology';
    }

    function inferLevel(title, desc) {
      const t = `${title} ${desc}`.toLowerCase();
      if (/senior|lead|principal|manager|head/.test(t)) return 'Senior Level';
      if (/mid|intermediate/.test(t)) return 'Mid Level';
      return 'Entry Level';
    }

    function normalizeLevel(val, title, desc) {
      const v = String(val || '').trim().toLowerCase();
      if (v.includes('senior')) return 'Senior Level';
      if (v.includes('mid') || v.includes('intermediate')) return 'Mid Level';
      if (v.includes('entry') || v.includes('junior') || v.includes('fresh')) return 'Entry Level';
      if (!v) return inferLevel(title, desc);
      return String(val).trim();
    }

    function normalizeRow(row) {
      const salary = parseSalaryRange(row.salary);
      const workType = normalizeWorkType(row.work_type);
      const skills = splitSkills(row.skills);
      const title = String(row.title || '').trim();
      const desc = String(row.description || '').trim();
      const setup = workType === 'Remote' ? 'Remote' : workType === 'Hybrid' ? 'Hybrid' : 'On-site';

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
        created_at: row.created_at || row.job_post_created || ''
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
        console.log(`Loaded ${allJobs.length} jobs from database`);
        loadErrorMessage = '';

        // Keep salary filters wide enough for actual DB data
        const highest = allJobs.reduce((mx, job) => Math.max(mx, Number(job.salary.max || 0)), 0);
        salaryMaxValue = Math.max(60000, highest);
        applySalaryBounds();
        renderJobsMap(allJobs);
      } catch (err) {
        allJobs = [];
        loadErrorMessage = (err && err.message) ? err.message : 'Failed to load jobs from database.';
        console.error('Error loading jobs:', err);
      }
      updateSidebarCounts();
      applyFilters();
    }

    function applySalaryBounds() {
      const minRange = document.getElementById('minRange');
      const maxRange = document.getElementById('maxRange');
      const minInput = document.getElementById('minSalary');
      const maxInput = document.getElementById('maxSalary');
      const minLabel = document.getElementById('minLabel');
      const maxLabel = document.getElementById('maxLabel');
      const track = document.getElementById('rangeTrack');

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
        'On-site': filtered.filter(j => j.setup === 'On-site').length,
        'Remote': filtered.filter(j => j.setup === 'Remote').length,
        'Hybrid': filtered.filter(j => j.setup === 'Hybrid').length,
        'Entry Level': filtered.filter(j => j.level === 'Entry Level').length,
        'Mid Level': filtered.filter(j => j.level === 'Mid Level').length,
        'Senior Level': filtered.filter(j => j.level === 'Senior Level').length,
        'Last 7 days': filtered.filter(j => j.postedDays <= 7).length,
        'Last 30 days': filtered.filter(j => j.postedDays <= 30).length,
        'All time': filtered.length,
      };

      document.querySelectorAll('.filter-item').forEach(item => {
        const val = item.dataset.value;
        const fcount = item.querySelector('.fcount');
        if (fcount && val && counts[val] !== undefined) {
          fcount.textContent = counts[val];
        }
      });
    }

    // ─── Apply all filters & render ───────────────────────────────────────
    function applyFilters() {
      let filtered = allJobs.filter(job => {
        // Location radius filter
        if (userLocation) {
          const geo = jobsGeoCache.get(getCachedGeoKey(job.location));
          if (!geo) return false;
          const distance = calculateDistance(userLocation.lat, userLocation.lng, geo.lat, geo.lng);
          job._distanceFromUser = distance;
          if (distance > filterRadiusKm) return false;
        }

        // Search
        if (filterState.searchQuery) {
          const q = filterState.searchQuery;
          const inTitle = job.title.toLowerCase().includes(q);
          const inDesc = job.description.toLowerCase().includes(q);
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
            if (period === 'Last 7 days') return job.postedDays <= 7;
            if (period === 'Last 30 days') return job.postedDays <= 30;
            if (period === 'All time') return true;
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

      const locationText = userLocation ? ` within <strong>${filterRadiusKm}km</strong> of your location` : '';
      document.getElementById('jobsCount').innerHTML =
        `Showing <strong>${filtered.length} jobs</strong> in Nasugbu, Batangas${locationText}`;

      displayJobs(filtered);
      renderJobsMap(filtered);
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
          empty.style.cssText = 'text-align:center;padding:3rem 2rem;color:var(--warm-light);';
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
      const bgColors = ['#d4e6d6', '#c8d8e8', '#e8d4e8', '#f0e4c8', '#f0d8c0', '#e8d0d8'];
      const textColors = ['#4a6b50', '#3a5a7a', '#6a3a6a', '#8a7040', '#8a5a3a', '#8a3a4a'];
      const idx = job.id % 6;

      const setupBadgeClass = job.setup === 'Remote' ?
        'badge-blue' : job.setup === 'Hybrid' ?
        'badge-purple' : 'badge-gray';

      const logoText = job.title
        .split(' ').filter(Boolean)
        .map(w => w[0]).join('')
        .substring(0, 2).toUpperCase() || 'JP';

      const deadline = formatDeadline(job.applicationDeadline);
      const createdDate = formatPostedDate(job.created_at);
      const skillsHtml = job.skills.length ?
        job.skills.map(s => `<span class="chip">${s}</span>`).join('') :
        '<span class="chip">No skills listed</span>';

      const distanceHtml = (job._distanceFromUser !== undefined && userLocation) ?
        `<span class="chip" style="background:#f0e4c8;color:#8a7040">&#128205; ${job._distanceFromUser.toFixed(1)} km away</span>` : '';

      const directionsBtnHtml = userLocation ?
        `<button type="button" class="btn-secondary" style="padding:0.65rem 1rem" data-directions="${job.id}">&#128663; Directions</button>` : '';

      const card = document.createElement('div');
      card.className = 'job-card';
      card.addEventListener('click', event => {
        const clickedButton = event.target.closest('a, button');
        if (clickedButton) return;
        openJobDetailsModal(job);
      });
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
            <span>&#128205; ${job.location}</span>
            <span>&#128188; ${job.type}</span>
            <span>&#128202; ${job.level || 'Not specified'}</span>
            <span>&#127970; ${job.category || 'Not specified'}</span>
            <span>&#128197; ${createdDate}</span>
          </div>
          <div class="job-skills">${skillsHtml}</div>
          ${distanceHtml ? `<div class="job-skills" style="margin-top:0.35rem">${distanceHtml}</div>` : ''}
          <div class="job-footer">
            <div class="job-salary">${job.salaryText}</div>
            <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
              <span class="job-date">Application deadline: ${deadline}</span>
              <button type="button" class="btn-secondary" style="padding:0.65rem 1rem" data-view-job="${job.id}">View Details</button>
              ${directionsBtnHtml}
              <button type="button" class="btn-primary" onclick="submitApplication(${job.id})">Apply Now</button>
            </div>
          </div>
        `;

      card.querySelector('[data-view-job]')?.addEventListener('click', event => {
        event.stopPropagation();
        openJobDetailsModal(job);
      });

      card.querySelector('[data-directions]')?.addEventListener('click', event => {
        event.stopPropagation();
        showRouteToJob(job);
      });

      return card;
    }

    // ─── Pagination ────────────────────────────────────────────────────────
    function addPagination(allFilteredJobs, activePage) {
      const main = document.getElementById('jobsMain');
      const itemsPerPage = 5;
      const totalPages = Math.max(1, Math.ceil(allFilteredJobs.length / itemsPerPage));
      const pag = document.createElement('div');
      pag.className = 'pagination';

      // Previous button
      const prevBtn = document.createElement('button');
      prevBtn.className = 'pg-btn';
      prevBtn.textContent = '\u2039';
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
        ellipsis.textContent = '\u2026';
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
      nextBtn.textContent = '\u203A';
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
      filterState.salaryRange = {
        min: 0,
        max: 60000
      };
      filterState.experienceLevel = [];
      filterState.postedWithin = [];
      filterState.sortBy = 'Sort: Most Recent';

      // Clear location filter
      clearLocationFilter();

      // Reset map overlays
      if (isTrafficOn) toggleTraffic();
      if (isFullscreen) toggleFullscreen();

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
      const minRange = document.getElementById('minRange');
      const maxRange = document.getElementById('maxRange');
      const minInput = document.getElementById('minSalary');
      const maxInput = document.getElementById('maxSalary');
      const minLabel = document.getElementById('minLabel');
      const maxLabel = document.getElementById('maxLabel');
      const track = document.getElementById('rangeTrack');

      function sync() {
        let minVal = Math.min(parseInt(minRange.value), parseInt(maxRange.value));
        let maxVal = Math.max(parseInt(minRange.value), parseInt(maxRange.value));

        minRange.value = minVal;
        maxRange.value = maxVal;
        minInput.value = minVal;
        maxInput.value = maxVal;
        minLabel.textContent = minVal.toLocaleString();
        maxLabel.textContent = maxVal.toLocaleString();

        track.style.left = (minVal / salaryMaxValue * 100) + '%';
        track.style.right = (100 - maxVal / salaryMaxValue * 100) + '%';

        filterState.salaryRange.min = minVal;
        filterState.salaryRange.max = maxVal;
        updateSidebarCounts();
        applyFilters();
      }

      minRange.addEventListener('input', sync);
      maxRange.addEventListener('input', sync);
      minInput.addEventListener('change', () => {
        minRange.value = minInput.value;
        sync();
      });
      maxInput.addEventListener('change', () => {
        maxRange.value = maxInput.value;
        sync();
      });

      // Init track with current bounds
      applySalaryBounds();
    }

    // ─── Checkbox toggles ──────────────────────────────────────────────────
    function initCheckboxes() {
      document.querySelectorAll('.filter-item[data-group]').forEach(item => {
        item.addEventListener('click', () => {
          const group = item.dataset.group;
          const value = item.dataset.value;
          const box = item.querySelector('.fcheck');

          box.classList.toggle('on');
          box.textContent = box.classList.contains('on') ? '\u2713' : '';

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

      document.getElementById('pinLocationBtn').addEventListener('click', pinUserLocation);

      document.getElementById('clearLocationBtn').addEventListener('click', clearLocationFilter);

      document.getElementById('mapThemeDropdown').addEventListener('change', e => {
        switchMapLayer(e.target.value);
      });

      document.getElementById('trafficToggleBtn').addEventListener('click', toggleTraffic);

      document.getElementById('fullscreenMapBtn').addEventListener('click', toggleFullscreen);

      document.getElementById('closeFullscreenMapBtn').addEventListener('click', toggleFullscreen);

      document.getElementById('directionsCloseBtn').addEventListener('click', hideDirections);

      document.getElementById('radiusInput').addEventListener('change', e => {
        const val = parseInt(e.target.value);
        if (val >= 1 && val <= 50) {
          filterRadiusKm = val;
          if (userLocation) {
            if (userRadiusCircle) {
              jobsMap.removeLayer(userRadiusCircle);
            }
            userRadiusCircle = L.circle([userLocation.lat, userLocation.lng], {
              radius: filterRadiusKm * 1000,
              color: '#c8a96e',
              fillColor: '#c8a96e',
              fillOpacity: 0.1,
              weight: 2,
              dashArray: '5, 10'
            }).addTo(jobsMap);
            applyFilters();
            const status = document.getElementById('mapStatus');
            if (status) status.textContent = `Showing jobs within ${filterRadiusKm}km of your location`;
          }
        } else {
          e.target.value = filterRadiusKm;
        }
      });

      document.querySelectorAll('.map-theme-btn').forEach(btn => {
        btn.addEventListener('click', () => {
          switchMapLayer(btn.dataset.theme);
        });
      });
    }

    // ─── Bootstrap ────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
      initSalaryRange();
      initCheckboxes();
      initTopBar();
      loadJobsFromDatabase(); // fetches DB → normalizes → calls applyFilters() → renders cards

      const jobDetailsModal = document.getElementById('jobDetailsModal');
      const closeDetailsBtn = document.getElementById('closeJobDetailsModal');

      if (jobDetailsModal) {
        jobDetailsModal.addEventListener('click', event => {
          if (event.target === jobDetailsModal) {
            closeJobDetailsModal();
          }
        });
      }

      if (closeDetailsBtn) {
        closeDetailsBtn.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          closeJobDetailsModal();
        });
      }

      document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
          closeJobDetailsModal();
        }
      });

      // Success Modal Event Listeners
      const successModal = document.getElementById('successModal');
      const closeSuccessBtn = document.getElementById('closeSuccessBtn');
      const closeSuccessXBtn = document.getElementById('closeSuccessXBtn');
      const viewApplicationsBtn = document.getElementById('viewApplicationsBtn');

      if (successModal) {
        successModal.addEventListener('click', event => {
          if (event.target === successModal) {
            closeSuccessModal();
          }
        });
      }

      if (closeSuccessBtn) {
        closeSuccessBtn.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          closeSuccessModal();
        });
      }

      if (closeSuccessXBtn) {
        closeSuccessXBtn.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          closeSuccessModal();
        });
      }

      if (viewApplicationsBtn) {
        viewApplicationsBtn.addEventListener('click', () => {
          window.location.href = '../applicant-tracking/index.php';
        });
      }
    });
  </script>

  <!-- FOOTER -->
  <footer class="footer">
    <div class="footer-inner">
      <div class="footer-top">
        <div class="footer-brand">
          <h3>TalentScout AI</h3>
          <p>Smart AI-powered recruitment platform for PESO Nasugbu, Batangas. Connecting local talent with local opportunities.</p>
        </div>
        <div class="footer-col">
          <h4>For Job Seekers</h4>
          <ul>
            <li><a href="../job-postings/index.php">Browse Jobs</a></li>
            <li><a href="../ai-matching/index.php">AI Matching</a></li>
            <li><a href="../skill-gap-analysis/index.php">Skill Gap Analysis</a></li>
            <li><a href="../applicant-tracking/index.php">Track Applications</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>For Employers</h4>
          <ul>
            <li><a href="../../employers/index.php">Post Jobs</a></li>
            <li><a href="../../employers/modules/blind-hiring/index.php">Blind Hiring</a></li>
            <li><a href="../../employers/index.php">Find Talent</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h4>PESO Nasugbu</h4>
          <ul>
            <li><a href="#">Nasugbu, Batangas</a></li>
            <li><a href="#">About PESO</a></li>
            <li><a href="#">Contact Us</a></li>
            <li><a href="#">Privacy Policy</a></li>
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
    if (reveals.length) {
      const io = new IntersectionObserver((entries) => {
        entries.forEach(e => {
          if (e.isIntersecting) {
            e.target.classList.add('visible');
            io.unobserve(e.target);
          }
        });
      }, { threshold: 0.12 });
      reveals.forEach(el => io.observe(el));
    }
  </script>
</body>

</html>