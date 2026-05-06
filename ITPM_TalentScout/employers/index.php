<?php session_start();
require_once __DIR__ . '/../database/db.php';

// ── Real-time stats from DB ──────────────────────────────────────────────────
$employer_id = $_SESSION['employer_id'] ?? null;

// Top job with most applications
$top_job = null;
$top_skills = [];
try {
  $clause = $employer_id ? "AND j.employer_id = :eid" : "";
  $stmt = $pdo->prepare("
    SELECT j.id, j.title, j.skills_required,
           COUNT(DISTINCT a.id) AS application_count
    FROM jobs j
    LEFT JOIN applications a ON a.job_id = j.id
    WHERE j.status = 'active' $clause
    GROUP BY j.id
    ORDER BY application_count DESC
    LIMIT 1
  ");
  $employer_id ? $stmt->execute([':eid' => $employer_id]) : $stmt->execute();
  $top_job = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($top_job && !empty($top_job['skills_required'])) {
    $top_skills = array_slice(array_map('trim', explode(',', $top_job['skills_required'])), 0, 3);
  }
} catch (Exception $e) { $top_job = null; }

// Active postings count
$active_postings = 0;
try {
  $q = $employer_id
    ? $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE status='active' AND employer_id=:eid")
    : $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE status='active'");
  $employer_id ? $q->execute([':eid' => $employer_id]) : $q->execute();
  $active_postings = (int)$q->fetchColumn();
} catch (Exception $e) {}

// Candidate pool
$candidate_pool = 0;
try {
  $candidate_pool = (int)$pdo->query("SELECT COUNT(*) FROM employees WHERE status='active'")->fetchColumn();
} catch (Exception $e) {}

// Hires this month
$hires_month = 0;
try {
  $q2 = $employer_id
    ? $pdo->prepare("SELECT COUNT(*) FROM applications a JOIN jobs j ON a.job_id=j.id WHERE a.status='hired' AND j.employer_id=:eid AND MONTH(a.updated_at)=MONTH(NOW()) AND YEAR(a.updated_at)=YEAR(NOW())")
    : $pdo->prepare("SELECT COUNT(*) FROM applications WHERE status='hired' AND MONTH(updated_at)=MONTH(NOW()) AND YEAR(updated_at)=YEAR(NOW())");
  $employer_id ? $q2->execute([':eid' => $employer_id]) : $q2->execute();
  $hires_month = (int)$q2->fetchColumn();
} catch (Exception $e) {}

// Avg days to hire
$avg_days = 0;
try {
  $q3 = $employer_id
    ? $pdo->prepare("SELECT AVG(DATEDIFF(a.updated_at, a.created_at)) FROM applications a JOIN jobs j ON a.job_id=j.id WHERE a.status='hired' AND j.employer_id=:eid")
    : $pdo->prepare("SELECT AVG(DATEDIFF(updated_at, created_at)) FROM applications WHERE status='hired'");
  $employer_id ? $q3->execute([':eid' => $employer_id]) : $q3->execute();
  $avg_days = round((float)$q3->fetchColumn());
} catch (Exception $e) {}

$top_app_count = $top_job['application_count'] ?? 0;
$top_job_title = $top_job['title'] ?? 'No Active Jobs Yet';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>For Employers – TalentScout AI</title>

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

    .nav-links { display: flex; list-style: none; gap: 0.2rem; }

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

    .navbar.scrolled .hamburger span { background: var(--charcoal); }

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

    /* ══ HERO — Full BG Image ══ */
    .hero {
      position: relative;
      min-height: 100vh;
      display: flex;
      align-items: center;
      overflow: hidden;
    }

    /* Background image layer */
    .hero-bg {
      position: absolute;
      inset: 0;
      background-image: url('../picturee.jpg');
      background-size: cover;
      background-position: center 30%;
      background-repeat: no-repeat;
      transform: scale(1.06);
      animation: heroBgZoom 18s ease-out forwards;
    }

    @keyframes heroBgZoom {
      from { transform: scale(1.06); }
      to   { transform: scale(1); }
    }

    /* Dark overlay — stronger on left for text readability, lighter on right */
    .hero-overlay {
      position: absolute; inset: 0;
      background: linear-gradient(
        105deg,
        rgba(20, 30, 22, 0.88) 0%,
        rgba(30, 48, 34, 0.78) 30%,
        rgba(20, 30, 22, 0.45) 60%,
        rgba(10, 18, 12, 0.18) 100%
      );
    }

    /* Subtle vignette */
    .hero-vignette {
      position: absolute; inset: 0;
      background: radial-gradient(ellipse 120% 100% at 0% 50%, transparent 40%, rgba(0,0,0,0.35) 100%);
      pointer-events: none;
    }

    /* Animated light ray from right */
    .hero-light-ray {
      position: absolute; inset: 0; pointer-events: none;
      background: radial-gradient(ellipse 60% 80% at 75% 30%,
        rgba(200, 164, 106, 0.18) 0%,
        transparent 65%
      );
      animation: lightRayPulse 8s ease-in-out infinite alternate;
    }

    @keyframes lightRayPulse {
      from { opacity: 0.6; transform: scale(1); }
      to   { opacity: 1;   transform: scale(1.08); }
    }

    /* Grain texture overlay */
    .hero-grain {
      position: absolute; inset: 0; pointer-events: none; opacity: 0.04;
      background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)'/%3E%3C/svg%3E");
      background-size: 200px 200px;
    }

    /* Animated scan-line shimmer */
    .hero-shimmer {
      position: absolute; inset: 0; pointer-events: none;
      background: repeating-linear-gradient(
        0deg,
        transparent,
        transparent 2px,
        rgba(255,255,255,0.013) 2px,
        rgba(255,255,255,0.013) 4px
      );
    }

    /* Content */
    .hero-inner {
      position: relative; z-index: 2;
      max-width: 1180px; margin: 0 auto; width: 100%;
      padding: 0 2.5rem;
      padding-top: 66px;
      min-height: 100vh;
      display: flex;
      align-items: center;
    }

    .hero-content {
      max-width: 600px;
      padding: 2rem 0;
    }

    /* Eyebrow */
    .hero-eyebrow {
      display: inline-flex; align-items: center; gap: 0.5rem;
      font-size: 0.71rem; font-weight: 700;
      letter-spacing: 0.13em; text-transform: uppercase;
      color: var(--mint-deep);
      background: rgba(255,255,255,0.1);
      border: 1.5px solid rgba(168,212,184,0.35);
      backdrop-filter: blur(10px);
      padding: 0.35rem 1rem; border-radius: var(--radius-pill);
      margin-bottom: 1.5rem;
      opacity: 0; transform: translateY(20px);
      animation: slideUp 0.8s 0.5s var(--ease) forwards;
    }

    .eyebrow-dot {
      width: 7px; height: 7px;
      background: var(--mint-deep); border-radius: 50%;
      box-shadow: 0 0 8px var(--mint-deep);
      animation: dotPulse 2.5s ease-in-out infinite;
    }

    @keyframes dotPulse {
      0%, 100% { opacity: 1; transform: scale(1); }
      50%       { opacity: 0.5; transform: scale(1.7); }
    }

    /* Main headline */
    .hero-headline {
      font-family: 'Lora', serif;
      font-size: clamp(2.8rem, 6vw, 5rem);
      font-weight: 700;
      color: #fff;
      line-height: 1.06;
      letter-spacing: -0.03em;
      margin-bottom: 1.5rem;
      opacity: 0; transform: translateY(30px);
      animation: slideUp 0.9s 0.7s var(--ease) forwards;
    }

    .hero-headline .line {
      display: block;
      overflow: hidden;
    }

    .hero-headline .accent {
      font-style: italic;
      color: var(--gold-light);
      position: relative;
      display: inline-block;
    }

    .hero-headline .accent::after {
      content: '';
      position: absolute; bottom: 2px; left: 0; right: 0;
      height: 3px;
      background: linear-gradient(90deg, var(--gold), transparent);
      border-radius: 2px;
      transform: scaleX(0); transform-origin: left;
      animation: underlineGrow 1s 1.6s var(--ease) forwards;
    }

    @keyframes underlineGrow { to { transform: scaleX(1); } }

    /* Description */
    .hero-desc {
      font-size: 1rem; color: rgba(255,255,255,0.72);
      line-height: 1.85; margin-bottom: 2.5rem; max-width: 480px;
      opacity: 0; transform: translateY(20px);
      animation: slideUp 0.9s 0.95s var(--ease) forwards;
    }

    /* CTA buttons */
    .hero-actions {
      display: flex; gap: 1rem; flex-wrap: wrap;
      opacity: 0; transform: translateY(20px);
      animation: slideUp 0.9s 1.15s var(--ease) forwards;
    }

    .btn-hero-primary {
      padding: 1rem 2.4rem;
      background: linear-gradient(135deg, var(--sage) 0%, var(--sage-dark) 100%);
      color: #fff; border-radius: var(--radius-pill);
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 0.95rem; font-weight: 700; border: none; cursor: pointer;
      display: inline-flex; align-items: center; gap: 0.5rem;
      transition: all 0.35s var(--ease);
      box-shadow: 0 6px 28px rgba(90,138,104,0.45);
      position: relative; overflow: hidden;
      letter-spacing: 0.01em;
    }

    .btn-hero-primary::before {
      content: '';
      position: absolute; inset: 0;
      background: linear-gradient(135deg, rgba(255,255,255,0.2), transparent);
      opacity: 0; transition: opacity 0.3s;
    }

    .btn-hero-primary:hover::before { opacity: 1; }
    .btn-hero-primary:hover { transform: translateY(-3px) scale(1.02); box-shadow: 0 14px 40px rgba(90,138,104,0.55); }

    .btn-hero-outline {
      padding: 0.95rem 2rem;
      background: rgba(255,255,255,0.1);
      color: rgba(255,255,255,0.9);
      border-radius: var(--radius-pill);
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 0.9rem; font-weight: 600;
      border: 1.5px solid rgba(255,255,255,0.3); cursor: pointer;
      display: inline-block;
      transition: all 0.25s var(--ease); backdrop-filter: blur(12px);
      letter-spacing: 0.01em;
    }

    .btn-hero-outline:hover {
      background: rgba(255,255,255,0.2);
      border-color: rgba(255,255,255,0.6);
      transform: translateY(-2px);
    }

    /* Trust badges */
    .hero-trust {
      display: flex; align-items: center; gap: 2rem;
      margin-top: 2.5rem;
      opacity: 0; transform: translateY(20px);
      animation: slideUp 0.9s 1.35s var(--ease) forwards;
    }

    .trust-badge {
      display: flex; align-items: center; gap: 0.5rem;
      font-size: 0.8rem; color: rgba(255,255,255,0.6); font-weight: 500;
    }

    .trust-badge-icon {
      width: 32px; height: 32px;
      background: rgba(255,255,255,0.1);
      border: 1px solid rgba(255,255,255,0.2);
      border-radius: 50%; display: flex; align-items: center; justify-content: center;
      font-size: 0.85rem; backdrop-filter: blur(8px);
      flex-shrink: 0;
    }

    /* Scroll indicator */
    .scroll-indicator {
      position: absolute; bottom: 2.5rem; left: 50%; transform: translateX(-50%);
      z-index: 2;
      display: flex; flex-direction: column; align-items: center; gap: 0.5rem;
      opacity: 0; animation: fadeIn 1s 2s ease forwards;
    }

    @keyframes fadeIn { to { opacity: 1; } }

    .scroll-indicator span {
      font-size: 0.65rem; font-weight: 600; letter-spacing: 0.15em;
      text-transform: uppercase; color: rgba(255,255,255,0.4);
    }

    .scroll-arrow {
      width: 24px; height: 24px;
      border-right: 2px solid rgba(255,255,255,0.3);
      border-bottom: 2px solid rgba(255,255,255,0.3);
      transform: rotate(45deg);
      animation: scrollBounce 2.2s ease-in-out infinite;
    }

    @keyframes scrollBounce {
      0%, 100% { transform: rotate(45deg) translateY(0); opacity: 0.4; }
      50%       { transform: rotate(45deg) translateY(6px); opacity: 1; }
    }

    @keyframes slideUp {
      to { opacity: 1; transform: translateY(0); }
    }

    /* Floating decorative element — top right */
    .hero-deco-badge {
      position: absolute; top: 140px; right: 5%;
      z-index: 2;
      background: rgba(255,255,255,0.08);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255,255,255,0.15);
      border-radius: var(--radius-xl);
      padding: 1.2rem 1.5rem;
      opacity: 0;
      animation: floatIn 1s 1.5s var(--ease) forwards;
      transition: transform 0.4s var(--ease);
    }

    .hero-deco-badge:hover { transform: translateY(-6px) scale(1.03); }

    @keyframes floatIn {
      from { opacity: 0; transform: translateY(30px) scale(0.92); }
      to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    .deco-badge-label {
      font-size: 0.62rem; font-weight: 700; letter-spacing: 0.12em;
      text-transform: uppercase; color: var(--mint-deep);
      margin-bottom: 0.5rem;
      display: flex; align-items: center; gap: 0.4rem;
    }

    .deco-badge-label::before {
      content: ''; width: 6px; height: 6px;
      background: #4caf82; border-radius: 50%;
      box-shadow: 0 0 8px #4caf82;
      animation: dotPulse 2s infinite;
    }

    .deco-badge-stat {
      font-family: 'Lora', serif; font-size: 2.2rem; font-weight: 700;
      color: #fff; line-height: 1;
    }

    .deco-badge-sub {
      font-size: 0.73rem; color: rgba(255,255,255,0.5); margin-top: 0.3rem;
    }

    /* Second floating badge */
    .hero-deco-badge-2 {
      position: absolute; bottom: 130px; right: 8%;
      z-index: 2;
      background: rgba(200, 164, 106, 0.12);
      backdrop-filter: blur(20px);
      border: 1px solid rgba(200, 164, 106, 0.25);
      border-radius: var(--radius-lg);
      padding: 1rem 1.4rem;
      opacity: 0;
      animation: floatIn 1s 1.8s var(--ease) forwards;
      transition: transform 0.4s var(--ease);
    }

    .hero-deco-badge-2:hover { transform: translateY(-6px) scale(1.03); }

    .deco-badge-2-label {
      font-size: 0.62rem; font-weight: 700; letter-spacing: 0.12em;
      text-transform: uppercase; color: var(--gold);
      margin-bottom: 0.4rem;
    }

    .deco-badge-2-val {
      font-family: 'Lora', serif; font-size: 1.7rem; font-weight: 700;
      color: #fff; line-height: 1;
    }

    .deco-badge-2-sub {
      font-size: 0.7rem; color: rgba(255,255,255,0.45); margin-top: 0.25rem;
    }

    /* Float animation for badges */
    .float-anim {
      animation: badgeFloat 6s ease-in-out infinite;
    }

    .float-anim-slow {
      animation: badgeFloat 8s ease-in-out infinite reverse;
    }

    @keyframes badgeFloat {
      0%, 100% { transform: translateY(0); }
      50%       { transform: translateY(-10px); }
    }

    /* ══ FEATURES ══ */
    .features-bg {
      background: linear-gradient(180deg, var(--cream) 0%, var(--cream-mid) 50%, var(--cream-warm) 100%);
      padding: 7rem 2.5rem 6rem;
      position: relative; overflow: hidden;
    }

    .features-bg .deco-ring {
      position: absolute; border-radius: 50%; pointer-events: none;
    }

    .features-bg .deco-ring.dr1 {
      width: 400px; height: 400px;
      border: 2px solid rgba(90,138,104,0.08);
      top: -120px; right: -100px;
      animation: spinSlow 40s linear infinite;
    }

    .features-bg .deco-ring.dr2 {
      width: 270px; height: 270px;
      border: 1.5px solid rgba(200,164,106,0.1);
      bottom: -70px; left: -70px;
      animation: spinSlow 55s linear infinite reverse;
    }

    @keyframes spinSlow { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

    .features-header {
      text-align: center; margin-bottom: 4rem; position: relative; z-index: 1;
    }

    .section-eyebrow {
      font-size: 0.7rem; font-weight: 700;
      letter-spacing: 0.15em; text-transform: uppercase;
      color: var(--sage); margin-bottom: 0.8rem;
    }

    .section-title {
      font-family: 'Lora', serif;
      font-size: clamp(1.8rem, 3vw, 2.6rem);
      font-weight: 700; color: var(--charcoal);
      letter-spacing: -0.025em; margin-bottom: 0.8rem; line-height: 1.2;
    }

    .section-title em { font-style: italic; color: var(--sage); }

    .section-sub {
      font-size: 0.9rem; color: var(--text-soft);
      max-width: 480px; margin: 0 auto 1.8rem; line-height: 1.75;
    }

    .btn-primary {
      display: inline-flex; align-items: center; gap: 0.4rem;
      padding: 0.72rem 1.7rem;
      background: linear-gradient(135deg, var(--sage), var(--sage-dark));
      color: #fff; border-radius: var(--radius-pill);
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 0.87rem; font-weight: 700; border: none; cursor: pointer;
      transition: all 0.25s var(--ease);
      box-shadow: 0 4px 18px rgba(90,138,104,0.3);
    }

    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(90,138,104,0.42); }

    .features-grid {
      display: grid; grid-template-columns: repeat(3, 1fr);
      gap: 1.5rem; max-width: 1120px; margin: 0 auto; position: relative; z-index: 1;
    }

    .feature-card {
      background: var(--white); border-radius: var(--radius-xl);
      padding: 2rem 1.8rem;
      border: 1px solid rgba(90,138,104,0.1);
      box-shadow: var(--shadow-soft);
      transition: transform 0.35s var(--ease), box-shadow 0.35s, border-color 0.35s;
      display: flex; flex-direction: column;
      cursor: pointer; text-decoration: none; color: inherit;
      position: relative; overflow: hidden;
    }

    .feature-card::before {
      content: '';
      position: absolute; top: 0; left: 0; right: 0; height: 0;
      background: linear-gradient(180deg, rgba(200,228,212,0.35) 0%, transparent 100%);
      transition: height 0.4s var(--ease);
    }

    .feature-card:hover::before { height: 100%; }
    .feature-card:hover { transform: translateY(-9px); box-shadow: var(--shadow-lift); border-color: rgba(90,138,104,0.22); }

    .feature-icon-wrap {
      width: 56px; height: 56px; background: var(--mint);
      border-radius: var(--radius-lg);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.55rem; margin-bottom: 1.3rem;
      transition: transform 0.35s var(--ease), background 0.3s;
      position: relative; z-index: 1;
    }

    .feature-card:hover .feature-icon-wrap { transform: scale(1.12) rotate(-5deg); background: var(--mint-mid); }

    .feature-title {
      font-family: 'Lora', serif; font-size: 1.03rem; font-weight: 700;
      color: var(--charcoal); margin-bottom: 0.55rem; position: relative; z-index: 1;
    }

    .feature-desc {
      font-size: 0.83rem; color: var(--text-soft); line-height: 1.72;
      flex: 1; margin-bottom: 1.3rem; position: relative; z-index: 1;
    }

    .feature-cta {
      display: inline-flex; align-items: center; gap: 0.35rem;
      font-size: 0.81rem; font-weight: 700; color: var(--sage-dark);
      transition: gap 0.25s, color 0.2s; position: relative; z-index: 1;
    }

    .feature-cta svg { transition: transform 0.25s var(--ease); }
    .feature-card:hover .feature-cta { gap: 0.6rem; color: var(--sage); }
    .feature-card:hover .feature-cta svg { transform: translateX(4px); }

    /* ══ BENEFITS ══ */
    .benefits-section { padding: 7rem 2.5rem; background: var(--white); }
    .benefits-inner { max-width: 1120px; margin: 0 auto; }
    .benefits-header { text-align: center; margin-bottom: 4rem; }

    .benefits-header h2 {
      font-family: 'Lora', serif;
      font-size: clamp(1.8rem, 3vw, 2.5rem); font-weight: 700;
      color: var(--charcoal); letter-spacing: -0.025em; margin-top: 0.5rem;
    }

    .benefits-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.4rem; }

    .benefit-item {
      background: var(--cream-mid); border: 1px solid rgba(90,138,104,0.09);
      border-radius: var(--radius-xl); padding: 2rem 1.7rem;
      transition: all 0.3s var(--ease); position: relative; overflow: hidden;
    }

    .benefit-item::after {
      content: '';
      position: absolute; bottom: -60px; right: -60px;
      width: 120px; height: 120px; border-radius: 50%;
      background: radial-gradient(circle, rgba(90,138,104,0.07), transparent);
      transition: transform 0.5s var(--ease), opacity 0.4s; opacity: 0;
    }

    .benefit-item:hover { background: var(--white); box-shadow: var(--shadow-med); transform: translateY(-5px); border-color: rgba(90,138,104,0.18); }
    .benefit-item:hover::after { transform: scale(2.8); opacity: 1; }

    .benefit-icon-wrap {
      width: 50px; height: 50px; background: var(--mint);
      border-radius: var(--radius-md);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.3rem; margin-bottom: 1.1rem;
      transition: transform 0.3s var(--ease), background 0.3s;
    }

    .benefit-item:hover .benefit-icon-wrap { transform: scale(1.1); background: var(--mint-mid); }

    .benefit-title {
      font-family: 'Lora', serif; font-size: 0.99rem; font-weight: 700;
      color: var(--charcoal); margin-bottom: 0.5rem;
    }

    .benefit-desc { font-size: 0.82rem; color: var(--text-soft); line-height: 1.68; }

    /* ══ CTA ══ */
    .cta-section {
      padding: 7rem 2.5rem;
      background: linear-gradient(140deg, #2d5040 0%, #3d6b50 45%, #4a7a5e 75%, #2d4a38 100%);
      text-align: center; position: relative; overflow: hidden;
    }

    .cta-section::before {
      content: '';
      position: absolute; top: -150px; left: -150px;
      width: 500px; height: 500px; border-radius: 50%;
      background: radial-gradient(circle, rgba(255,255,255,0.06) 0%, transparent 70%);
      pointer-events: none;
    }

    .cta-section::after {
      content: '';
      position: absolute; bottom: -100px; right: -100px;
      width: 400px; height: 400px; border-radius: 50%;
      background: radial-gradient(circle, rgba(200,164,106,0.14) 0%, transparent 70%);
      pointer-events: none;
    }

    .cta-pattern {
      position: absolute; inset: 0;
      background-image: radial-gradient(circle, rgba(255,255,255,0.04) 1px, transparent 1px);
      background-size: 28px 28px; pointer-events: none;
    }

    .cta-inner { position: relative; z-index: 1; }

    .cta-eyebrow {
      font-size: 0.7rem; font-weight: 700;
      letter-spacing: 0.15em; text-transform: uppercase;
      color: var(--mint-deep); margin-bottom: 0.9rem;
    }

    .cta-title {
      font-family: 'Lora', serif;
      font-size: clamp(2rem, 4vw, 3.2rem); font-weight: 700;
      color: #fff; letter-spacing: -0.025em; margin-bottom: 1.1rem; line-height: 1.15;
    }

    .cta-title em { font-style: italic; color: var(--gold-light); }

    .cta-desc {
      font-size: 0.92rem; color: rgba(255,255,255,0.68);
      max-width: 440px; margin: 0 auto 2.5rem; line-height: 1.8;
    }

    .cta-actions { display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap; }

    .btn-cta-white {
      padding: 0.9rem 2.2rem; background: #fff; color: var(--sage-deeper);
      border-radius: var(--radius-pill);
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 0.92rem; font-weight: 700; border: none; cursor: pointer;
      display: inline-block; transition: all 0.25s var(--ease);
      box-shadow: 0 6px 24px rgba(0,0,0,0.15);
    }

    .btn-cta-white:hover { background: var(--cream); transform: translateY(-3px); box-shadow: 0 12px 36px rgba(0,0,0,0.2); }

    .btn-cta-ghost {
      padding: 0.88rem 2rem; background: rgba(255,255,255,0.1);
      color: rgba(255,255,255,0.9); border-radius: var(--radius-pill);
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 0.9rem; font-weight: 600;
      border: 1.5px solid rgba(255,255,255,0.28); cursor: pointer;
      display: inline-block; transition: all 0.25s var(--ease); backdrop-filter: blur(8px);
    }

    .btn-cta-ghost:hover { background: rgba(255,255,255,0.18); border-color: rgba(255,255,255,0.55); transform: translateY(-2px); }

    /* ══ FOOTER ══ */
    .footer { background: var(--charcoal); color: rgba(255,255,255,0.5); padding: 4.5rem 2.5rem 2rem; }
    .footer-inner { max-width: 1120px; margin: 0 auto; }

    .footer-top {
      display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 3rem;
      padding-bottom: 3rem; border-bottom: 1px solid rgba(255,255,255,0.07); margin-bottom: 2rem;
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

    /* ══ SCROLL REVEAL ══ */
    .reveal {
      opacity: 0; transform: translateY(34px);
      transition: opacity 0.75s var(--ease), transform 0.75s var(--ease);
    }

    .reveal.in { opacity: 1; transform: translateY(0); }
    .reveal-delay-1 { transition-delay: 0.08s; }
    .reveal-delay-2 { transition-delay: 0.17s; }
    .reveal-delay-3 { transition-delay: 0.26s; }
    .reveal-delay-4 { transition-delay: 0.35s; }

    /* ══ RESPONSIVE ══ */
    @media (max-width: 1024px) {
      .features-grid { grid-template-columns: repeat(2, 1fr); }
      .benefits-grid { grid-template-columns: repeat(2, 1fr); }
      .hero-deco-badge, .hero-deco-badge-2 { display: none; }
    }

    @media (max-width: 860px) {
      .footer-top { grid-template-columns: 1fr 1fr; }
      .nav-links { display: none; }
      .hamburger { display: flex; }
      .hero-headline { font-size: 2.6rem; }
    }

    @media (max-width: 600px) {
      .navbar { padding: 0 1.3rem; }
      .hero-inner { padding: 0 1.3rem; padding-top: 66px; }
      .features-bg, .benefits-section, .cta-section { padding: 5rem 1.3rem; }
      .features-grid, .benefits-grid { grid-template-columns: 1fr; }
      .footer-top { grid-template-columns: 1fr; gap: 2rem; }
      .footer-bottom { flex-direction: column; text-align: center; }
      .hero-trust { flex-wrap: wrap; gap: 1rem; }
      .cta-actions { flex-direction: column; align-items: center; }
      .hero-headline { font-size: 2.1rem; }
      .hero-actions { flex-direction: column; }
    }
  </style>
</head>
<body>

<!-- ════ NAVBAR ════ -->
<nav class="navbar" id="navbar">
  <a href="./index.php" class="nav-logo">
    <div class="nav-logo-mark">TS</div>
    <span>Talent<em>Scout</em> AI</span>
  </a>
  <ul class="nav-links">
    <li><a href="./index.php" class="active">Home</a></li>
    <li><a href="./modules/post-jobs/">Post Jobs</a></li>
    <li><a href="./modules/employee-finder/">Find Talent</a></li>
    <li><a href="./modules/applicant-tracking/">Hiring Pipeline</a></li>
    <li><a href="./modules/chat-sms/">Messages</a></li>
  </ul>
  <div class="nav-right">
    <?php if (isset($_SESSION['employer_id'])): ?>
      <span class="nav-user">Welcome, <?= htmlspecialchars($_SESSION['employer_name'] ?? 'Employer') ?></span>
      <a href="./logout.php" class="btn-ghost">Logout</a>
    <?php else: ?>
      <a href="./login.php" class="btn-ghost">Login</a>
      <a href="./signup.php" class="btn-solid">Get Started →</a>
    <?php endif; ?>
    <button class="hamburger" id="hamburger" aria-label="Menu">
      <span></span><span></span><span></span>
    </button>
  </div>
</nav>

<!-- Mobile Nav -->
<div class="mobile-nav" id="mobileNav">
  <a href="./index.php">🏠 Home</a>
  <a href="./modules/post-jobs/">📋 Post Jobs</a>
  <a href="./modules/employee-finder/">🔍 Find Talent</a>
  <a href="./modules/applicant-tracking/">📊 Hiring Pipeline</a>
  <a href="./modules/chat-sms/">💬 Messages</a>
  <div class="mobile-nav-actions">
    <?php if (isset($_SESSION['employer_id'])): ?>
      <a href="./logout.php" class="btn-ghost">Logout</a>
    <?php else: ?>
      <a href="./login.php" class="btn-ghost">Login</a>
      <a href="./signup.php" class="btn-solid">Get Started →</a>
    <?php endif; ?>
  </div>
</div>

<!-- ════ HERO ════ -->
<section class="hero">

  <!-- BG layers -->
  <div class="hero-bg"></div>
  <div class="hero-overlay"></div>
  <div class="hero-vignette"></div>
  <div class="hero-light-ray"></div>
  <div class="hero-grain"></div>
  <div class="hero-shimmer"></div>

  <!-- Floating right-side decorative stat badges -->
  <div class="hero-deco-badge float-anim">
    <div class="deco-badge-label">Active Postings</div>
    <div class="deco-badge-stat"><?= $active_postings ?></div>
    <div class="deco-badge-sub">→ Reach <?= number_format($candidate_pool) ?>+ candidates</div>
  </div>

  <div class="hero-deco-badge-2 float-anim-slow">
    <div class="deco-badge-2-label">⚡ Fast Hiring</div>
    <div class="deco-badge-2-val"><?= $avg_days > 0 ? $avg_days.'d' : '8d' ?></div>
    <div class="deco-badge-2-sub">avg. days to hire</div>
  </div>

  <!-- Main content — LEFT aligned -->
  <div class="hero-inner">
    <div class="hero-content">

      <div class="hero-eyebrow">
        <span class="eyebrow-dot"></span>
        PESO Nasugbu, Batangas
      </div>

      <h1 class="hero-headline">
        <span class="line">Find Your</span>
        <span class="line"><em class="accent">Perfect Talent</em></span>
        <span class="line">in Nasugbu</span>
      </h1>

      <p class="hero-desc">
        TalentScout AI connects you with pre-vetted talent matched to your job requirements. Post jobs, screen blind profiles, and hire the best fit for your team — all in one place.
      </p>

      <div class="hero-actions">
        <a href="./modules/post-jobs/" class="btn-hero-primary">
          📋 Post a Job
        </a>
        <a href="./modules/blind-hiring/" class="btn-hero-outline">
          Learn About Blind Hiring →
        </a>
      </div>

      <div class="hero-trust">
        <div class="trust-badge">
          <div class="trust-badge-icon">✅</div>
          <span>Free to post</span>
        </div>
        <div class="trust-badge">
          <div class="trust-badge-icon">⚡</div>
          <span>Hire in days</span>
        </div>
        <div class="trust-badge">
          <div class="trust-badge-icon">🤝</div>
          <span>Bias-free</span>
        </div>
      </div>

    </div>
  </div>

  <!-- Scroll indicator -->
  <div class="scroll-indicator">
    <span>Scroll</span>
    <div class="scroll-arrow"></div>
  </div>

</section>

<!-- ════ FEATURES ════ -->
<div class="features-bg">
  <div class="deco-ring dr1"></div>
  <div class="deco-ring dr2"></div>

  <div class="features-header reveal">
    <div class="section-eyebrow">Employer Solutions</div>
    <h2 class="section-title">Recruitment Made <em>Simple</em></h2>
    <p class="section-sub">Complete hiring tools to find, screen, and hire the right talent. All in one platform.</p>
    <a href="./modules/" class="btn-primary">View All Tools →</a>
  </div>

  <div class="features-grid">
    <a href="./modules/post-jobs/" class="feature-card reveal">
      <div class="feature-icon-wrap">📋</div>
      <div class="feature-title">Post Job Listings</div>
      <p class="feature-desc">Create and publish job posts in minutes. Reach hundreds of qualified candidates across all Nasugbu barangays instantly.</p>
      <span class="feature-cta">Post a Job <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></span>
    </a>
    <a href="./modules/blind-hiring/" class="feature-card reveal reveal-delay-1">
      <div class="feature-icon-wrap">🔒</div>
      <div class="feature-title">Blind Hiring</div>
      <p class="feature-desc">Screen talents anonymously by skills alone. Reduce bias, ensure fair evaluation, and hire based purely on merit and capability.</p>
      <span class="feature-cta">Learn More <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></span>
    </a>
    <a href="./modules/employee-finder/" class="feature-card reveal reveal-delay-2">
      <div class="feature-icon-wrap">🔍</div>
      <div class="feature-title">Employee Finder</div>
      <p class="feature-desc">Search and filter candidates by skills, experience, location, and salary expectations. Find your perfect match effortlessly.</p>
      <span class="feature-cta">Search Talent <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></span>
    </a>
    <a href="./modules/applicant-tracking/" class="feature-card reveal reveal-delay-1">
      <div class="feature-icon-wrap">📊</div>
      <div class="feature-title">Application Tracker</div>
      <p class="feature-desc">Manage the entire hiring pipeline from applications through interviews to successful hires. Never lose track of a candidate.</p>
      <span class="feature-cta">Manage Applicants <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></span>
    </a>
    <a href="./modules/company-registration/" class="feature-card reveal reveal-delay-2">
      <div class="feature-icon-wrap">✅</div>
      <div class="feature-title">Company Registration</div>
      <p class="feature-desc">Verify your company with permit/ID registration. Build credibility and access premium employer features and candidate insights.</p>
      <span class="feature-cta">Register Company <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></span>
    </a>
    <a href="./modules/chat-sms/" class="feature-card reveal reveal-delay-3">
      <div class="feature-icon-wrap">💬</div>
      <div class="feature-title">Chat & SMS</div>
      <p class="feature-desc">Communicate directly with candidates via chat or SMS. Schedule interviews, send updates, and close hires faster.</p>
      <span class="feature-cta">Start Messaging <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></span>
    </a>
  </div>
</div>

<!-- ════ BENEFITS ════ -->
<div class="benefits-section">
  <div class="benefits-inner">
    <div class="benefits-header reveal">
      <div class="section-eyebrow">Why TalentScout For Employers</div>
      <h2>Hire Better, Faster, <em style="font-style:italic;color:var(--sage);">Smarter</em></h2>
    </div>
    <div class="benefits-grid">
      <div class="benefit-item reveal">
        <div class="benefit-icon-wrap">⚡</div>
        <div class="benefit-title">Fast Hiring</div>
        <div class="benefit-desc">Average hiring time of <?= $avg_days > 0 ? $avg_days : 8 ?> days. AI matching shortens your recruitment cycle and gets you the right person quickly.</div>
      </div>
      <div class="benefit-item reveal reveal-delay-1">
        <div class="benefit-icon-wrap">⭐</div>
        <div class="benefit-title">Qualified Candidates</div>
        <div class="benefit-desc">Pre-vetted talent pool with verified skills. No more sifting through hundreds of unqualified applications.</div>
      </div>
      <div class="benefit-item reveal reveal-delay-2">
        <div class="benefit-icon-wrap">🤝</div>
        <div class="benefit-title">Bias-Free Hiring</div>
        <div class="benefit-desc">Blind hiring profiles ensure you evaluate candidates based on merit alone. Build diverse, capable teams.</div>
      </div>
      <div class="benefit-item reveal reveal-delay-1">
        <div class="benefit-icon-wrap">💰</div>
        <div class="benefit-title">Cost Effective</div>
        <div class="benefit-desc">No expensive recruitment agencies. Direct access to local talent at a fraction of traditional hiring costs.</div>
      </div>
      <div class="benefit-item reveal reveal-delay-2">
        <div class="benefit-icon-wrap">🔗</div>
        <div class="benefit-title">Seamless Communication</div>
        <div class="benefit-desc">Chat and SMS integration keeps candidates engaged. Reduce no-shows and improve candidate experience.</div>
      </div>
      <div class="benefit-item reveal reveal-delay-3">
        <div class="benefit-icon-wrap">📈</div>
        <div class="benefit-title">Analytics & Insights</div>
        <div class="benefit-desc">Track hiring metrics, candidate quality, and time-to-hire. Make data-driven hiring decisions.</div>
      </div>
    </div>
  </div>
</div>

<!-- ════ CTA ════ -->
<section class="cta-section">
  <div class="cta-pattern"></div>
  <div class="cta-inner reveal">
    <div class="cta-eyebrow">Ready to Find Top Talent?</div>
    <h2 class="cta-title">Start Hiring <em>Today</em></h2>
    <p class="cta-desc">Post your first job for free and access our pre-vetted talent pool. Begin receiving qualified applications and making hires within days.</p>
    <div class="cta-actions">
      <a href="./modules/post-jobs/" class="btn-cta-white">📋 Post Your First Job</a>
      <a href="./modules/" class="btn-cta-ghost">Learn More →</a>
    </div>
  </div>
</section>

<!-- ════ FOOTER ════ -->
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
          <li><a href="../employees/modules/job-postings/">Browse Jobs</a></li>
          <li><a href="../employees/modules/ai-matching/">AI Matching</a></li>
          <li><a href="../employees/modules/skill-gap-analysis/">Skill Gap Analysis</a></li>
          <li><a href="../employees/modules/applicant-tracking/">Track Applications</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>For Employers</h4>
        <ul>
          <li><a href="./index.php">Post Jobs</a></li>
          <li><a href="./modules/blind-hiring/">Blind Hiring</a></li>
          <li><a href="./modules/employee-finder/">Find Talent</a></li>
          <li><a href="./modules/applicant-tracking/">Hiring Pipeline</a></li>
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
      <span>© 2026 TalentScout AI – PESO Nasugbu, Batangas</span>
      <span>Connecting Employers with Local Talent</span>
    </div>
  </div>
</footer>

<script>
  /* ── Hamburger ── */
  const ham = document.getElementById('hamburger');
  const mNav = document.getElementById('mobileNav');

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

  /* ── Navbar scroll: transparent on hero, solid after ── */
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

  /* ── Scroll reveal ── */
  const revealObs = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) { e.target.classList.add('in'); revealObs.unobserve(e.target); }
    });
  }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

  document.querySelectorAll('.reveal').forEach(el => revealObs.observe(el));

  /* ── Staggered feature card reveal ── */
  document.querySelectorAll('.feature-card').forEach((card, i) => {
    card.style.transitionDelay = `${i * 0.07}s`;
  });

  /* ── Subtle parallax on hero BG on scroll ── */
  const heroBg = document.querySelector('.hero-bg');
  window.addEventListener('scroll', () => {
    const scrolled = window.pageYOffset;
    if (scrolled < window.innerHeight) {
      heroBg.style.transform = `scale(1) translateY(${scrolled * 0.3}px)`;
    }
  }, { passive: true });

  /* ── Mouse parallax on overlay light ray ── */
  const lightRay = document.querySelector('.hero-light-ray');
  let ticking = false;

  document.addEventListener('mousemove', (e) => {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(() => {
      const x = (e.clientX / window.innerWidth) * 100;
      const y = (e.clientY / window.innerHeight) * 100;
      lightRay.style.background = `radial-gradient(ellipse 60% 80% at ${x}% ${y}%, rgba(200,164,106,0.22) 0%, transparent 65%)`;
      ticking = false;
    });
  }, { passive: true });

  /* ── Button ripple effect ── */
  document.querySelectorAll('.btn-hero-primary, .btn-cta-white').forEach(btn => {
    btn.addEventListener('click', function(e) {
      const rect = this.getBoundingClientRect();
      const ripple = document.createElement('span');
      ripple.style.cssText = `
        position:absolute; border-radius:50%; background:rgba(255,255,255,0.3);
        width:10px; height:10px; pointer-events:none;
        left:${e.clientX - rect.left - 5}px;
        top:${e.clientY - rect.top - 5}px;
        animation: rippleOut 0.6s ease-out forwards;
      `;
      this.style.position = 'relative';
      this.style.overflow = 'hidden';
      this.appendChild(ripple);
      setTimeout(() => ripple.remove(), 650);
    });
  });

  const style = document.createElement('style');
  style.textContent = `@keyframes rippleOut { to { transform: scale(35); opacity: 0; } }`;
  document.head.appendChild(style);
</script>

</body>
</html>