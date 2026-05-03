<?php
session_start();
require_once __DIR__ . '/../../../database/db.php';
require_once __DIR__ . '/match-engine.php';

// Check if user is logged in
if (!isset($_SESSION['employee_id'])) {
    header('Location: ../../login.php');
    exit;
}
  
// Initialize database connection
$conn = getConnection();

// Initialize match engine
$engine = new MatchEngine($conn, $_SESSION['employee_id']);

// Get employee profile and matches
$employee_profile = $engine->getEmployeeProfile();
$matched_jobs = $engine->getMatchedJobs();
$employee_skills = $engine->getEmployeeSkills();

// Calculate profile score (50% skills, 50% experience)
$skills_score = min(100, count($employee_skills) * 10);
$experience_score = min(100, ($employee_profile['experience_count'] ?? 0) * 20);
$profile_score = round(($skills_score * 0.5) + ($experience_score * 0.5));
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AI Job Matching — TalentScout AI</title>
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
        --nav-height:  64px;
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
        padding-top: calc(var(--nav-height) + 3rem);
        padding-bottom: 2rem;
        background: var(--sand);
        position: relative;
        overflow: hidden;
      }

      .page-header::before {
        content: '';
        position: absolute;
        top: -60px; right: -60px;
        width: 240px; height: 240px;
        border-radius: 50%;
        background: radial-gradient(circle, var(--sage-pale) 0%, transparent 70%);
        pointer-events: none;
      }

      .page-header-inner {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 2.5rem;
        position: relative;
        z-index: 1;
      }

      .breadcrumb {
        font-size: 0.78rem;
        color: var(--warm-light);
        margin-bottom: 1rem;
      }

      .breadcrumb a {
        color: var(--sage);
        transition: color 0.15s;
      }

      .breadcrumb a:hover { color: var(--sage-deep); }

      .page-header h1 {
        font-family: 'Playfair Display', serif;
        font-size: clamp(1.8rem, 3vw, 2.4rem);
        font-weight: 900;
        color: var(--charcoal);
        letter-spacing: -0.025em;
        margin-bottom: 0.6rem;
      }

      .page-header p {
        font-size: 0.92rem;
        color: var(--warm-mid);
        line-height: 1.7;
        max-width: 600px;
      }

      /* ── MAIN LAYOUT ── */
      .match-layout {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2.5rem;
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 2rem;
        align-items: start;
      }

      /* ── PROFILE PANEL ── */
      .profile-panel {
        position: sticky;
        top: calc(var(--nav-height) + 1rem);
      }

      .profile-card {
        background: #fff;
        border: 1px solid var(--sand-dark);
        border-radius: var(--radius-xl);
        overflow: hidden;
        box-shadow: 0 4px 24px rgba(42,42,34,0.06);
        transition: box-shadow 0.3s var(--ease-out);
      }

      .profile-card:hover {
        box-shadow: 0 12px 40px rgba(42,42,34,0.1);
      }

      .profile-header {
        background: linear-gradient(135deg, var(--sage-deep), var(--sage));
        padding: 1.75rem;
        text-align: center;
        color: #fff;
        position: relative;
      }

      .profile-header::after {
        content: '';
        position: absolute;
        bottom: 0; left: 0; right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--gold-pale), var(--gold), var(--gold-pale));
      }

      .profile-avatar {
        width: 72px; height: 72px;
        background: rgba(255,255,255,0.18);
        border: 3px solid rgba(255,255,255,0.35);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        font-weight: 700;
        margin: 0 auto 0.75rem;
        color: #fff;
        font-family: 'Playfair Display', serif;
      }

      .profile-name {
        font-family: 'Playfair Display', serif;
        font-size: 1.15rem;
        font-weight: 700;
      }

      .profile-loc {
        font-size: 0.85rem;
        opacity: 0.82;
        margin-top: 0.2rem;
      }

      .profile-body {
        padding: 1.4rem;
      }

      .profile-section {
        margin-bottom: 1.25rem;
      }

      .profile-section-title {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: var(--warm-light);
        margin-bottom: 0.75rem;
      }

      .skills-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
      }

      .profile-info-row {
        display: flex;
        justify-content: space-between;
        font-size: 0.87rem;
        padding: 0.5rem 0;
        border-bottom: 1px solid var(--sand-dark);
      }

      .profile-info-row:last-child {
        border-bottom: none;
      }

      .profile-info-label {
        color: var(--warm-light);
      }

      .profile-info-val {
        font-weight: 600;
        color: var(--charcoal);
      }

      .match-score-ring {
        width: 90px; height: 90px;
        border-radius: 50%;
        background: conic-gradient(var(--sage-deep) 0% 87%, var(--sand-dark) 87% 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 0.5rem;
        position: relative;
      }

      .match-score-inner {
        width: 68px; height: 68px;
        background: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--sage-deep);
        font-family: 'Playfair Display', serif;
      }

      /* ── BUTTONS ── */
      .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        padding: 0.65rem 1.4rem;
        border-radius: var(--radius-pill);
        font-family: 'DM Sans', sans-serif;
        font-size: 0.84rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s var(--ease-out);
        border: none;
        text-decoration: none;
        line-height: 1;
      }

      .btn-sage {
        background: var(--sage-deep);
        color: #fff;
        box-shadow: 0 4px 14px rgba(74,107,80,0.28);
      }

      .btn-sage:hover {
        background: var(--sage);
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(74,107,80,0.35);
      }

      .btn-outline {
        background: transparent;
        color: var(--warm-mid);
        border: 1.5px solid var(--stone-light);
      }

      .btn-outline:hover {
        background: var(--sand);
        border-color: var(--stone);
        transform: translateY(-1px);
      }

      .btn-light {
        background: var(--sand);
        color: var(--sage-deep);
        border: 1px solid var(--sand-dark);
      }

      .btn-light:hover {
        background: var(--sage-pale);
        border-color: var(--sage-light);
        transform: translateY(-1px);
      }

      /* ── INPUTS ── */
      .input {
        font-family: 'DM Sans', sans-serif;
        font-size: 0.86rem;
        padding: 0.55rem 0.85rem;
        border-radius: var(--radius-pill);
        border: 1.5px solid var(--sand-dark);
        background: #fff;
        color: var(--charcoal);
        transition: border-color 0.2s, box-shadow 0.2s;
        outline: none;
      }

      .input:focus {
        border-color: var(--sage-light);
        box-shadow: 0 0 0 3px var(--sage-pale);
      }

      .input::placeholder { color: var(--warm-light); }

      .select {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg width='12' height='8' viewBox='0 0 12 8' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1.5L6 6.5L11 1.5' stroke='%238a8070' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        padding-right: 2.2rem;
      }

      /* ── MATCHES COLUMN ── */
      .matches-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        gap: 0.8rem;
      }

      .matches-header > div:first-child {
        font-size: 1rem;
        font-weight: 700;
        color: var(--charcoal);
      }

      .matches-header > div:first-child + div {
        font-size: 0.85rem;
        color: var(--warm-light);
        margin-top: 0.2rem;
      }

      .matches-tabs {
        display: flex;
        gap: 0.2rem;
        background: var(--sand);
        border-radius: var(--radius-pill);
        padding: 0.25rem;
        border: 1px solid var(--sand-dark);
      }

      .tab-btn {
        padding: 0.45rem 1rem;
        border-radius: var(--radius-pill);
        font-size: 0.82rem;
        font-weight: 500;
        color: var(--warm-mid);
        cursor: pointer;
        background: transparent;
        border: none;
        font-family: 'DM Sans', sans-serif;
        transition: all 0.2s;
      }

      .tab-btn.active {
        background: #fff;
        color: var(--sage-deep);
        font-weight: 700;
        box-shadow: 0 2px 8px rgba(42,42,34,0.08);
      }

      /* ── MATCH CARDS ── */
      .match-card {
        background: #fff;
        border: 1.5px solid var(--sand-dark);
        border-radius: var(--radius-xl);
        padding: 1.5rem;
        margin-bottom: 1rem;
        transition: all 0.3s var(--ease-out);
        position: relative;
        overflow: hidden;
      }

      .match-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--sage-pale), var(--sage));
        transform: scaleX(0);
        transform-origin: left;
        transition: transform 0.3s var(--ease-out);
      }

      .match-card:hover {
        border-color: var(--sage-pale);
        box-shadow: 0 12px 40px rgba(42,42,34,0.1);
        transform: translateY(-3px);
      }

      .match-card:hover::before {
        transform: scaleX(1);
      }

      .match-card-top {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1rem;
      }

      .match-logo {
        width: 48px; height: 48px;
        border-radius: var(--radius-md);
        font-weight: 700;
        font-size: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-family: 'Playfair Display', serif;
      }

      .match-info {
        flex: 1;
      }

      .match-title {
        font-family: 'Playfair Display', serif;
        font-size: 1.05rem;
        font-weight: 700;
        margin-bottom: 0.2rem;
        color: var(--charcoal);
      }

      .match-company {
        font-size: 0.87rem;
        color: var(--warm-mid);
      }

      .match-pct {
        background: var(--sage-pale);
        color: var(--sage-deep);
        padding: 0.35rem 0.85rem;
        border-radius: var(--radius-pill);
        font-weight: 700;
        font-size: 0.85rem;
        white-space: nowrap;
        flex-shrink: 0;
      }

      .match-pct.high {
        background: var(--sage-pale);
        color: var(--sage-deep);
      }

      .match-pct.mid {
        background: var(--gold-pale);
        color: #8a6d2b;
      }

      .match-pct.low {
        background: var(--sand);
        color: var(--warm-mid);
      }

      .match-meta {
        display: flex;
        gap: 1.2rem;
        font-size: 0.83rem;
        color: var(--warm-light);
        margin-bottom: 0.75rem;
        flex-wrap: wrap;
      }

      .match-bars {
        background: var(--sand);
        border-radius: var(--radius-lg);
        padding: 1rem;
        margin-top: 0.75rem;
      }

      .match-bar-row {
        margin-bottom: 0.6rem;
      }

      .match-bar-row:last-child {
        margin-bottom: 0;
      }

      .match-bar-label {
        display: flex;
        justify-content: space-between;
        font-size: 0.8rem;
        font-weight: 500;
        color: var(--warm-mid);
        margin-bottom: 0.3rem;
      }

      .job-skills {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.75rem;
      }

      /* ── CHIPS ── */
      .chip {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        background: var(--sage-pale);
        color: var(--sage-deep);
        padding: 0.38rem 0.75rem;
        border-radius: var(--radius-pill);
        font-size: 0.78rem;
        font-weight: 500;
        white-space: nowrap;
      }

      .chip-remove {
        background: none;
        border: none;
        color: inherit;
        cursor: pointer;
        font-size: 1rem;
        line-height: 1;
        opacity: 0.6;
        transition: opacity 0.15s;
        padding: 0;
      }

      .chip-remove:hover { opacity: 1; }

      .chip-outline {
        display: inline-block;
        background: transparent;
        color: var(--warm-light);
        padding: 0.38rem 0.75rem;
        border: 1px dashed var(--stone-light);
        border-radius: var(--radius-pill);
        font-size: 0.78rem;
        font-weight: 500;
        white-space: nowrap;
      }

      /* ── PROGRESS BAR ── */
      .progress-bar {
        width: 100%;
        height: 8px;
        background: var(--sand-dark);
        border-radius: 4px;
        overflow: hidden;
      }

      .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--sage-deep), var(--sage));
        border-radius: 4px;
        transition: width 0.4s var(--ease-out);
      }

      /* ── HOW IT WORKS ── */
      .how-section {
        background: var(--sand);
        padding: 4rem 2rem 4.5rem;
        margin-top: 2rem;
        position: relative;
        overflow: hidden;
      }

      .how-section::before {
        content: '';
        position: absolute;
        bottom: -80px; left: -80px;
        width: 260px; height: 260px;
        border-radius: 50%;
        background: radial-gradient(circle, var(--gold-pale) 0%, transparent 70%);
        pointer-events: none;
      }

      .how-inner {
        max-width: 1080px;
        margin: 0 auto;
        position: relative;
        z-index: 1;
      }

      .section-head {
        text-align: center;
        margin-bottom: 3rem;
      }

      .section-head .eyebrow {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: var(--sage);
        margin-bottom: 0.7rem;
      }

      .section-head h2 {
        font-family: 'Playfair Display', serif;
        font-size: clamp(1.6rem, 3vw, 2.2rem);
        font-weight: 900;
        color: var(--charcoal);
        letter-spacing: -0.025em;
        margin-bottom: 0.5rem;
      }

      .section-head p {
        font-size: 0.88rem;
        color: var(--warm-mid);
        max-width: 480px;
        margin: 0 auto;
        line-height: 1.7;
      }

      .how-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.4rem;
      }

      .how-card {
        background: var(--cream);
        border: 1px solid rgba(139,128,112,0.12);
        border-radius: var(--radius-xl);
        padding: 2rem 1.5rem;
        text-align: center;
        transition: box-shadow 0.25s var(--ease-out), transform 0.25s var(--ease-out), border-color 0.25s;
        position: relative;
        overflow: hidden;
      }

      .how-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--sage-pale), var(--sage));
        transform: scaleX(0);
        transform-origin: left;
        transition: transform 0.3s var(--ease-out);
      }

      .how-card:hover {
        box-shadow: 0 12px 40px rgba(42,42,34,0.1);
        transform: translateY(-4px);
        border-color: var(--sage-pale);
      }

      .how-card:hover::before {
        transform: scaleX(1);
      }

      .how-icon {
        width: 52px; height: 52px;
        background: var(--sage-pale);
        border-radius: var(--radius-md);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem;
        margin: 0 auto 1rem;
      }

      .how-card h3 {
        font-family: 'Playfair Display', serif;
        font-size: 1rem;
        font-weight: 700;
        color: var(--charcoal);
        margin-bottom: 0.5rem;
        letter-spacing: -0.01em;
      }

      .how-card p {
        font-size: 0.83rem;
        color: var(--warm-mid);
        line-height: 1.68;
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
      .reveal-delay-5 { transition-delay: 0.5s; }

      /* ── RESPONSIVE ── */
      @media (max-width: 1024px) {
        .match-layout {
          grid-template-columns: 1fr;
          padding: 1.5rem;
        }

        .profile-panel {
          position: static;
        }

        .matches-header {
          flex-direction: column;
          align-items: flex-start;
          gap: 0.8rem;
        }

        .how-grid {
          grid-template-columns: repeat(2, 1fr);
        }
      }

      @media (max-width: 768px) {
        .navbar { padding: 0 1.2rem; }
        .nav-links { display: none; }

        .match-layout {
          padding: 1rem;
        }

        .match-card-top {
          flex-wrap: wrap;
        }

        .match-pct {
          order: 3;
        }

        .profile-header,
        .profile-body,
        .match-card,
        .how-card {
          padding: 1rem;
        }

        .match-meta {
          gap: 0.6rem;
        }

        .matches-header > div:last-child {
          width: 100%;
          flex-wrap: wrap;
        }

        .matches-header .select {
          width: 100% !important;
        }

        .matches-tabs {
          width: 100%;
          justify-content: space-between;
        }

        .tab-btn {
          flex: 1;
          text-align: center;
          padding: 0.5rem 0.4rem;
        }

        .how-grid {
          grid-template-columns: 1fr;
        }

        .footer-top {
          grid-template-columns: 1fr 1fr;
        }

        .footer-bottom {
          flex-direction: column;
          text-align: center;
        }
      }

      @media (max-width: 600px) {
        .page-header-inner { padding: 0 1.2rem; }
        .footer-top { grid-template-columns: 1fr; }
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
        <li><a href="./index.php" class="active">AI Matching</a></li>
        <li><a href="../resume-builder/index.php">Resume Builder</a></li>
        <li><a href="../skill-gap-analysis/index.php">Skills</a></li>
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
      <div class="page-header-inner">
        <div class="breadcrumb">
          <a href="../../index.php">Home</a> / AI Job Matching
        </div>
        <h1>AI Job Matching</h1>
        <p>
          Our AI engine analyzes your skills and barangay location to surface
          the most relevant job opportunities — ranked by match percentage.
        </p>
      </div>
    </div>

    <!-- MAIN -->
    <div class="match-layout">
      <!-- PROFILE PANEL -->
      <aside class="profile-panel">
        <div class="profile-card reveal">
          <div class="profile-header">
            <div class="profile-avatar"><?php 
              echo strtoupper(substr($employee_profile['first_name'], 0, 1) . substr($employee_profile['last_name'], 0, 1));
            ?></div>
            <div class="profile-name"><?php echo htmlspecialchars($employee_profile['first_name'] . ' ' . $employee_profile['last_name']); ?></div>
            <div class="profile-loc">📍 <?php echo htmlspecialchars($employee_profile['address']); ?></div>
          </div>
          <div class="profile-body">
            <div class="profile-section">
              <div style="text-align: center; margin-bottom: 0.6rem">
                <div class="match-score-ring" style="background: conic-gradient(var(--sage-deep) 0% <?php echo $profile_score; ?>%, var(--sand-dark) <?php echo $profile_score; ?>% 100%)">
                  <div class="match-score-inner"><?php echo $profile_score; ?>%</div>
                </div>
                <div style="font-size: 0.82rem; color: var(--warm-light)">
                  Overall Profile Score
                </div>
              </div>
            </div>

            <div class="profile-section">
              <div class="profile-section-title">Skills (<?php echo count($employee_skills); ?>)</div>
              <div class="skills-grid" id="skillsList">
                <?php 
                if (!empty($employee_skills)) {
                    foreach (array_slice($employee_skills, 0, 6) as $skill) {
                        echo '<span class="chip" data-skill="' . htmlspecialchars($skill) . '">' . htmlspecialchars($skill) . ' <button type="button" class="chip-remove" onclick="removeSkill(this)">×</button></span>';
                    }
                    if (count($employee_skills) > 6) {
                        echo '<span class="chip-outline">+' . (count($employee_skills) - 6) . ' more</span>';
                    }
                } else {
                    echo '<span class="chip-outline">No skills added yet</span>';
                }
                ?>
              </div>
              <div style="margin-top: 0.75rem; position: relative;">
                <input 
                  type="text" 
                  id="skillInput" 
                  class="input" 
                  placeholder="Add a skill..." 
                  style="width: 100%;" 
                />
                <div id="skillSuggestions" style="position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid var(--sand-dark); border-top: none; border-radius: 0 0 var(--radius-lg) var(--radius-lg); max-height: 200px; overflow-y: auto; display: none; z-index: 10;"></div>
              </div>
            </div>

            <div class="profile-section">
              <div class="profile-section-title">Profile Info</div>
              <div class="profile-info-row">
                <span class="profile-info-label">Skills Count</span>
                <span class="profile-info-val"><?php echo $employee_profile['skill_count']; ?></span>
              </div>
              <div class="profile-info-row">
                <span class="profile-info-label">Experience</span>
                <span class="profile-info-val"><?php echo $employee_profile['experience_count']; ?> Job<?php echo $employee_profile['experience_count'] != 1 ? 's' : ''; ?></span>
              </div>
              <div class="profile-info-row">
                <span class="profile-info-label">Email</span>
                <span class="profile-info-val" style="font-size: 0.75rem; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($employee_profile['email']); ?></span>
              </div>
            </div>

            <a
              href="../skill-gap-analysis/"
              class="btn btn-light"
              style="width: 100%; justify-content: center"
              >📊 View Skill Gap Analysis</a
            >
          </div>
        </div>
      </aside>

      <!-- MATCHES -->
      <main>
        <div class="matches-header">
          <div>
            <div>Your AI-Generated Matches</div>
            <!-- DEBUG: Employee ID = <?php echo $_SESSION['employee_id'] ?? 'NOT SET'; ?>, Jobs count = <?php echo count($matched_jobs); ?> -->
            <div style="font-size: 0.85rem; color: var(--warm-light); margin-top: 0.2rem;">
              <?php echo count($matched_jobs); ?> job<?php echo count($matched_jobs) != 1 ? 's' : ''; ?> matched to your profile
            </div>
          </div>
          <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
            <select class="input select" style="width: 175px" id="locationFilter">
              <option value="">All Locations</option>
              <option value="remote">Remote</option>
              <option value="hybrid">Hybrid</option>
              <option value="on-site">On-site</option>
            </select>
            <div class="matches-tabs">
              <button class="tab-btn active" data-filter="all">All</button>
              <button class="tab-btn" data-filter="90">90%+</button>
              <button class="tab-btn" data-filter="75">75%+</button>
            </div>
          </div>
        </div>

        <?php 
        if (empty($matched_jobs)): 
        ?>
          <div class="match-card reveal" style="text-align: center; padding: 2.5rem;">
            <div style="font-size: 2.5rem; margin-bottom: 0.75rem;">🔍</div>
            <div style="font-family: 'Playfair Display', serif; font-weight: 700; font-size: 1.1rem; margin-bottom: 0.5rem;">No Matches Found</div>
            <p style="color: var(--warm-light); font-size: 0.9rem; line-height: 1.6;">
              Add more skills to your profile to see job matches.
              <a href="../resume-builder/" style="color: var(--sage-deep); font-weight: 600;">Update your resume →</a>
            </p>
          </div>
        <?php else: 
          foreach ($matched_jobs as $job):
            $match_class = $job['overall_match'] >= 85 ? 'high' : ($job['overall_match'] >= 70 ? 'mid' : 'low');
            $employer_name = $engine->getEmployerName($job['employer_id']);
            $initials = strtoupper(substr($employer_name, 0, 1) . (strpos($employer_name, ' ') !== false ? substr($employer_name, strpos($employer_name, ' ') + 1, 1) : ''));
            
            $colors = [
              ['bg' => 'var(--sage-pale)', 'text' => 'var(--sage-deep)'],
              ['bg' => '#dce8f5', 'text' => '#2b5797'],
              ['bg' => '#ede4f5', 'text' => '#5b2d8e'],
              ['bg' => 'var(--gold-pale)', 'text' => '#8a6d2b'],
              ['bg' => '#f5dde4', 'text' => '#8e2d5b'],
              ['bg' => '#dce8e4', 'text' => '#2d5b4f'],
            ];
            $color = $colors[crc32($job['employer_id']) % count($colors)];
        ?>
        <!-- Match Card -->
        <div class="match-card reveal" data-match-score="<?php echo $job['overall_match']; ?>" data-work-type="<?php echo strtolower($job['work_type']); ?>">
          <div class="match-card-top">
            <div class="match-logo" style="background: <?php echo $color['bg']; ?>; color: <?php echo $color['text']; ?>">
              <?php echo htmlspecialchars($initials); ?>
            </div>
            <div class="match-info">
              <div class="match-title"><?php echo htmlspecialchars($job['title']); ?></div>
              <div class="match-company">
                <?php echo htmlspecialchars($employer_name); ?> &bull; <?php echo htmlspecialchars($job['location']); ?>
              </div>
            </div>
            <span class="match-pct <?php echo $match_class; ?>"><?php echo $job['overall_match']; ?>% Match</span>
          </div>
          <div class="match-meta">
            <span>📍 <?php echo $job['work_type']; ?></span>
            <span>💼 <?php echo htmlspecialchars($job['job_category']); ?></span>
            <span>₱<?php echo htmlspecialchars($job['salary']); ?></span>
          </div>
          
          <?php if (!empty($job['matched_skills']) || !empty($job['missing_skills'])): ?>
          <div class="job-skills">
            <?php 
            foreach (array_slice($job['matched_skills'], 0, 3) as $skill) {
                echo '<span class="chip">' . htmlspecialchars($skill) . '</span>';
            }
            foreach (array_slice($job['missing_skills'], 0, 2) as $skill) {
                echo '<span class="chip-outline">' . htmlspecialchars($skill) . ' ← Missing</span>';
            }
            ?>
          </div>
          <?php endif; ?>
          
          <div class="match-bars">
            <div style="font-size: 0.78rem; font-weight: 700; color: var(--warm-mid); margin-bottom: 0.7rem; text-transform: uppercase; letter-spacing: 0.08em;">
              Match Breakdown
            </div>
            <div class="match-bar-row">
              <div class="match-bar-label">
                <span>Skills Match</span><span><?php echo $job['skill_match']; ?>%</span>
              </div>
              <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo $job['skill_match']; ?>%"></div>
              </div>
            </div>
            <div class="match-bar-row">
              <div class="match-bar-label">
                <span>Location Match</span><span><?php echo $job['location_match']; ?>%</span>
              </div>
              <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo $job['location_match']; ?>%"></div>
              </div>
            </div>
            <div class="match-bar-row">
              <div class="match-bar-label">
                <span>Experience Match</span><span><?php echo $job['experience_match']; ?>%</span>
              </div>
              <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo $job['experience_match']; ?>%"></div>
              </div>
            </div>
          </div>
          <div style="margin-top: 1.1rem; display: flex; gap: 0.75rem; flex-wrap: wrap;">
            <a href="../job-postings/index.php?id=<?php echo $job['job_post_id']; ?>" class="btn btn-outline">
              View Full Posting
            </a>
            <button type="button" class="btn btn-sage" onclick="applyToJob({title: '<?php echo htmlspecialchars($job['title']); ?>', company: '<?php echo htmlspecialchars($employer_name); ?>', jobPostId: <?php echo $job['job_post_id']; ?>, location: '<?php echo htmlspecialchars($job['location'] ?? 'Not specified'); ?>', jobType: '<?php echo htmlspecialchars($job['work_type'] ?? 'Not specified'); ?>', salary: '<?php echo htmlspecialchars($job['salary'] ?? 'Not specified'); ?>', industry: '<?php echo htmlspecialchars($job['job_category'] ?? 'Not specified'); ?>', companySize: 'Not specified'})">
              Apply Now →
            </button>
          </div>
        </div>
        <?php endforeach; 
        endif; 
        ?>
      </main>
    </div>

    <!-- HOW IT WORKS -->
    <section class="how-section">
      <div class="how-inner">
        <div class="section-head reveal">
          <div class="eyebrow">Behind the AI</div>
          <h2>How AI Matching Works</h2>
          <p>Three pillars power our intelligent job matching engine.</p>
        </div>
        <div class="how-grid">
          <div class="how-card reveal reveal-delay-1">
            <div class="how-icon">🧠</div>
            <h3>Skills Analysis</h3>
            <p>
              The AI maps your listed skills against job requirements, identifying
              exact, partial, and missing skill matches with weighted scoring.
            </p>
          </div>
          <div class="how-card reveal reveal-delay-2">
            <div class="how-icon">📍</div>
            <h3>Barangay Targeting</h3>
            <p>
              Location matching uses barangay-level data within Nasugbu,
              prioritizing nearby jobs while still surfacing high-quality remote
              options.
            </p>
          </div>
          <div class="how-card reveal reveal-delay-3">
            <div class="how-icon">⚖️</div>
            <h3>Weighted Scoring</h3>
            <p>
              Skills (50%), Location (30%), and Experience (20%) are weighted and
              combined to produce a single reliable match percentage score.
            </p>
          </div>
        </div>
      </div>
    </section>

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
              <li><a href="./">AI Matching</a></li>
              <li><a href="../skill-gap-analysis/">Skill Gap Analysis</a></li>
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

    <!-- Confirmation Modal -->
    <div id="applicationConfirmModal" class="job-modal-backdrop" aria-hidden="true">
      <div class="job-modal" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
        <div class="job-modal-header">
          <h3 class="job-modal-title" id="confirmTitle">Confirm Application</h3>
          <button type="button" class="job-modal-close" id="closeConfirmModal" aria-label="Close confirmation">&times;</button>
        </div>
        <div class="job-modal-body">
          <p style="margin-bottom: 1.5rem; color: var(--text-mid);">Please review the job details before submitting your application.</p>
          <div class="job-modal-section">
            <h4 id="confirmJobTitle" style="color: var(--primary);">—</h4>
            <p id="confirmCompanyName" style="color: var(--text-mid); margin-bottom: 0;">—</p>
          </div>
          <p style="margin-top: 1.5rem; padding: 1rem; background: var(--bg-light); border-radius: var(--radius); border-left: 4px solid var(--primary); color: var(--text-mid); font-size: 0.9rem;">
            Once submitted, your application will be sent to the employer. You can track the status in your Applications page.
          </p>
        </div>
        <div class="job-modal-footer">
          <button type="button" class="btn btn-secondary" id="cancelConfirmBtn">Cancel</button>
          <button type="button" class="btn btn-primary" id="confirmApplyBtn">Confirm Application</button>
        </div>
      </div>
    </div>

    <!-- Success Modal (same as browse jobs) -->
    <div id="successModal" class="success-modal-backdrop" aria-hidden="true">
      <div class="success-modal" role="dialog" aria-modal="true">
        <button type="button" id="closeSuccessXBtn" class="job-modal-close" aria-label="Close success dialog" style="position: absolute; top: 16px; right: 16px; z-index: 20;">×</button>
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
            <span class="success-detail-value" id="successJobTitle">—</span>
          </div>
          <div class="success-detail-row">
            <span class="success-detail-label">Company</span>
            <span class="success-detail-value" id="successCompany">—</span>
          </div>
          <div class="success-detail-row">
            <span class="success-detail-label">Applied Date</span>
            <span class="success-detail-value" id="successDate">—</span>
          </div>
          <div class="success-detail-row">
            <span class="success-detail-label">Status</span>
            <span class="success-detail-value" id="successStatus">Pending</span>
          </div>
        </div>
        <div class="success-actions">
          <button type="button" class="btn btn-secondary" id="viewApplicationsBtn">View Applications</button>
          <button type="button" class="btn btn-primary" id="closeSuccessBtn">Browse More Jobs</button>
        </div>
      </div>
    </div>

    <script src="../../employee-auth.js"></script>
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

      // Match filtering functionality
      const matchCards = document.querySelectorAll('.match-card[data-match-score]');
      const tabButtons = document.querySelectorAll('.tab-btn[data-filter]');
      const locationFilter = document.getElementById('locationFilter');
      
      function applyFilters() {
        const activeTab = document.querySelector('.tab-btn.active');
        const minScore = activeTab.dataset.filter === 'all' ? 0 : parseInt(activeTab.dataset.filter);
        const location = locationFilter.value.toLowerCase();
        
        matchCards.forEach(card => {
          const score = parseInt(card.dataset.matchScore);
          const workType = card.dataset.workType;
          
          let scoreMatch = score >= minScore;
          let locationMatch = !location || 
                              location === 'remote' && workType.includes('remote') ||
                              location === 'hybrid' && workType.includes('hybrid') ||
                              location === 'on-site' && workType.includes('on-site');
          
          card.style.display = (scoreMatch && locationMatch) ? 'block' : 'none';
        });
        
        // Show/hide "no matches" message
        const visibleCards = Array.from(matchCards).filter(card => card.style.display !== 'none');
        const noMatchesMsg = document.querySelector('.match-card:last-child');
      }
      
      tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
          tabButtons.forEach(b => b.classList.remove('active'));
          btn.classList.add('active');
          applyFilters();
        });
      });
      
      locationFilter.addEventListener('change', applyFilters);

      // ===== SKILL MANAGEMENT =====
      const skillInput = document.getElementById('skillInput');
      const skillSuggestions = document.getElementById('skillSuggestions');
      let suggestionsTimeout;

      // Show suggestions on input
      skillInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        clearTimeout(suggestionsTimeout);
        
        if (query.length < 2) {
          skillSuggestions.style.display = 'none';
          return;
        }
        
        // Debounce the suggestion fetch
        suggestionsTimeout = setTimeout(() => {
          fetch(`./skills-api.php?action=suggest&q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
              if (data.suggestions && data.suggestions.length > 0) {
                skillSuggestions.innerHTML = data.suggestions
                  .map(skill => `<div class="suggestion-item" onclick="selectSkill('${skill}')" style="padding: 0.6rem 0.75rem; cursor: pointer; border-bottom: 1px solid var(--sand-dark); font-size: 0.9rem; color: var(--charcoal); transition: background 0.15s;" onmouseover="this.style.background='var(--sand)'" onmouseout="this.style.background='transparent'">${skill}</div>`)
                  .join('');
                skillSuggestions.style.display = 'block';
              } else {
                skillSuggestions.style.display = 'none';
              }
            })
            .catch(err => console.error('Suggestion error:', err));
        }, 300);
      });

      // Add skill on Enter or button click
      skillInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          addSkill();
        }
      });

      // Hide suggestions when clicking outside
      document.addEventListener('click', function(e) {
        if (e.target !== skillInput && !skillSuggestions.contains(e.target)) {
          skillSuggestions.style.display = 'none';
        }
      });

      function selectSkill(skill) {
        skillInput.value = skill;
        skillSuggestions.style.display = 'none';
        addSkill();
      }

      function addSkill() {
        const skill = skillInput.value.trim();
        
        if (!skill) {
          alert('Please enter a skill');
          return;
        }
        
        if (skill.length < 2) {
          alert('Skill must be at least 2 characters');
          return;
        }

        fetch('./skills-api.php?action=add', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ skill: skill })
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            // Clear input
            skillInput.value = '';
            skillSuggestions.style.display = 'none';
            
            // Add to skills list
            addSkillChip(data.skill);
            
            // Show success message
            showNotification('Skill added!', 'success');
            
            // Reload page to update matches
            setTimeout(() => location.reload(), 1500);
          } else {
            showNotification(data.message || 'Failed to add skill', 'error');
          }
        })
        .catch(err => {
          console.error('Add skill error:', err);
          showNotification('Error adding skill', 'error');
        });
      }

      function addSkillChip(skill) {
        const skillsList = document.getElementById('skillsList');
        
        // Remove "No skills added yet" message
        const noSkillsMsg = skillsList.querySelector('.chip-outline');
        if (noSkillsMsg && noSkillsMsg.textContent.includes('No skills')) {
          noSkillsMsg.remove();
        }
        
        // Create new chip
        const chip = document.createElement('span');
        chip.className = 'chip';
        chip.setAttribute('data-skill', skill);
        chip.innerHTML = skill + ' <button type="button" class="chip-remove" onclick="removeSkill(this)">×</button>';
        
        skillsList.appendChild(chip);
      }

      function removeSkill(btn) {
        const chip = btn.closest('.chip');
        const skill = chip.getAttribute('data-skill');
        
        if (!confirm(`Remove "${skill}"?`)) {
          return;
        }

        fetch('./skills-api.php?action=remove', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ skill: skill })
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            chip.remove();
            showNotification('Skill removed', 'success');
            
            // Reload page to update matches
            setTimeout(() => location.reload(), 1500);
          } else {
            showNotification('Failed to remove skill', 'error');
          }
        })
        .catch(err => {
          console.error('Remove skill error:', err);
          showNotification('Error removing skill', 'error');
        });
      }

      function showNotification(message, type = 'info') {
        const colors = {
          success: { bg: 'var(--sage-pale)', text: 'var(--sage-deep)' },
          error: { bg: '#f8e4e4', text: '#8b2020' },
          info: { bg: 'var(--gold-pale)', text: '#8a6d2b' }
        };
        const c = colors[type] || colors.info;
        
        const notification = document.createElement('div');
        notification.style.cssText = `
          position: fixed;
          top: 1rem;
          right: 1rem;
          padding: 0.9rem 1.4rem;
          background: ${c.bg};
          color: ${c.text};
          border-radius: var(--radius-pill);
          box-shadow: 0 8px 28px rgba(42,42,34,0.12);
          font-weight: 600;
          font-size: 0.88rem;
          z-index: 1000;
          animation: slideIn 0.3s ease-out;
        `;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
          notification.style.animation = 'slideOut 0.3s ease-out';
          setTimeout(() => notification.remove(), 300);
        }, 3000);
      }

      // Handle application form submissions via AJAX
      document.addEventListener('submit', function(e) {
        if (e.target.action.includes('submit-application.php')) {
          e.preventDefault();
          
          const formData = new FormData(e.target);
          const jobPostId = formData.get('job_post_id');
          
          fetch('../job-postings/submit-application.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              showNotification('✓ Application submitted successfully! Job: ' + data.application.job_title);
              setTimeout(() => {
                window.location.href = '../applicant-tracking/index.php';
              }, 1500);
            } else {
              showNotification('✗ Error: ' + data.message);
            }
          })
          .catch(error => {
            console.error('Error:', error);
            showNotification('✗ An error occurred while submitting your application');
          });
        }
      });
    </script>
    <style>
      @keyframes slideIn {
        from {
          transform: translateX(400px);
          opacity: 0;
        }
        to {
          transform: translateX(0);
          opacity: 1;
        }
      }
      @keyframes slideOut {
        from {
          transform: translateX(0);
          opacity: 1;
        }
        to {
          transform: translateX(400px);
          opacity: 0;
        }
      }

      /* Success Modal Styles */
      .success-modal-backdrop {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        align-items: center;
        justify-content: center;
      }
      .success-modal-backdrop.active {
        display: flex;
        animation: fadeIn 0.3s ease-out;
      }
      .success-modal {
        background: white;
        border-radius: 12px;
        padding: 2.5rem;
        max-width: 420px;
        width: 90vw;
        max-height: 85vh;
        overflow-y: auto;
        position: relative;
        animation: popIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
      }
      .success-checkmark {
        width: 80px;
        height: 80px;
        margin: 0 auto 1.5rem;
      }
      .success-checkmark svg {
        width: 100%;
        height: 100%;
      }
      .success-checkmark-circle {
        stroke: var(--primary);
        stroke-width: 2;
        fill: none;
        animation: scaleIn 0.4s ease-out;
      }
      .success-checkmark-line {
        stroke: var(--primary);
        stroke-width: 3;
        stroke-linecap: round;
        fill: none;
        animation: drawLine 0.5s ease-out 0.2s both;
      }
      @keyframes scaleIn {
        from { transform: scale(0); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
      }
      @keyframes drawLine {
        from { stroke-dashoffset: 8; }
        to { stroke-dashoffset: 0; }
      }
      .success-checkmark-line {
        stroke-dasharray: 8;
      }
      .success-title {
        font-size: 1.5rem;
        font-weight: 700;
        text-align: center;
        color: var(--text-dark);
        margin-bottom: 0.75rem;
      }
      .success-message {
        text-align: center;
        color: var(--text-mid);
        margin-bottom: 2rem;
        font-size: 0.95rem;
        line-height: 1.5;
      }
      .success-details {
        background: var(--bg-light);
        border-radius: 8px;
        padding: 1.25rem;
        margin-bottom: 1.75rem;
        border-left: 4px solid var(--primary);
      }
      .success-detail-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
      }
      .success-detail-row:last-child {
        border-bottom: none;
      }
      .success-detail-label {
        color: var(--text-mid);
        font-size: 0.9rem;
        font-weight: 500;
      }
      .success-detail-value {
        color: var(--text-dark);
        font-weight: 600;
      }
      .success-actions {
        display: flex;
        gap: 1rem;
      }
      .success-actions button {
        flex: 1;
      }
      @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
      }
      @keyframes popIn {
        from {
          transform: scale(0.9);
          opacity: 0;
        }
        to {
          transform: scale(1);
          opacity: 1;
        }
      }
    </style>
  <?php include_once __DIR__ . '/../common/application-modals.php'; ?>
  <script>
    // Apply to job from AI Matching
    function applyToJob(jobData) {
      <?php if (!isset($_SESSION['employee_id'])) { ?>
        window.location.href = '../../login.php';
        return;
      <?php } ?>
      if (jobData && jobData.title) {
        showConfirmModal(jobData);
      } else {
        alert('Error: Could not load job details.');
      }
    }
  </script>
  </body>
</html>
