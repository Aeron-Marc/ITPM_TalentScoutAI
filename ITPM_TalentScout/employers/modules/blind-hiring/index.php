<?php 
session_start();
require_once('../../../database/db.php');

// Check if employer is logged in
if (!isset($_SESSION['employer_id'])) {
  header('Location: ../../login.php');
  exit;
}

// Handle contact candidate action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'contact_candidate') {
  $candidate_id = intval($_POST['candidate_id'] ?? 0);
  
  if ($candidate_id > 0) {
    $conn = getConnection();
    $employer_id = (int)$_SESSION['employer_id'];
    
    // Pre-filled job offer message for blind hiring
    $job_offer_message = "Hello! We're interested in your profile based on your skills. We'd like to offer you an opportunity to learn more about our open positions. Would you be available for a brief chat?";
    
    // Insert the initial message as an anonymous employer
    $stmt = $conn->prepare("INSERT INTO message (sender_id, sender_type, receiver_id, receiver_type, message, timestamp) VALUES (?, 'employer', ?, 'employee', ?, NOW())");
    $stmt->bind_param("iis", $employer_id, $candidate_id, $job_offer_message);
    
    if ($stmt->execute()) {
      $stmt->close();
      header('Location: ../chat-sms/index.php?employee_id=' . $candidate_id);
      exit;
    }
    $stmt->close();
  }
}

// Get database connection
$conn = getConnection();
$employer_id = (int)$_SESSION['employer_id'];

// Fetch all employees with their resumes and skills (for blind hiring)
$blind_candidates = [];
$stmt = $conn->prepare("SELECT DISTINCT
  e.employee_id,
  CONCAT('Candidate ', e.employee_id) as candidate_name,
  r.summary,
  IFNULL(r.resume_id, 0) as resume_id
FROM employee e
LEFT JOIN resumes r ON e.employee_id = r.employee_id
WHERE e.is_active = 1
ORDER BY RAND()");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $blind_candidates[] = $row;
}
$stmt->close();

// Fetch skills for each candidate
foreach ($blind_candidates as &$candidate) {
  if ($candidate['resume_id'] > 0) {
    $stmt = $conn->prepare("SELECT skill_name FROM resume_skills WHERE resume_id = ? LIMIT 10");
    $stmt->bind_param("i", $candidate['resume_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $skills = [];
    while ($row = $result->fetch_assoc()) {
      $skills[] = $row['skill_name'];
    }
    $stmt->close();
    $candidate['skills'] = $skills;
  } else {
    $candidate['skills'] = [];
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Blind Hiring — TalentScout AI</title>
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
      color: var(--charcoal); margin: 0 0 0.3rem 0;
    }

    .page-hero p { font-size: 0.92rem; color: var(--text-soft); margin: 0; }

    /* ══ CONTAINER ══ */
    .container { max-width: 900px; margin: 0 auto; padding: 2rem 2.5rem 3rem; }

    /* ══ INFO SECTIONS ══ */
    .info-section {
      background: white; border: 1px solid rgba(90,138,104,0.13);
      border-radius: var(--radius-lg); padding: 2rem;
      margin-bottom: 2rem; box-shadow: var(--shadow-soft);
    }

    .info-section h2 {
      font-family: 'Lora', serif; font-size: 1.3rem; font-weight: 700;
      color: var(--charcoal); margin-bottom: 1rem;
    }

    .info-section p { color: var(--text-soft); line-height: 1.6; margin-bottom: 1rem; }
    .info-section p:last-child { margin-bottom: 0; }
    .info-section ul { margin-left: 1.5rem; color: var(--text-soft); }
    .info-section li { margin-bottom: 0.5rem; line-height: 1.6; }
    .info-section li:last-child { margin-bottom: 0; }

    .highlight {
      background: var(--gold-pale); border-left: 4px solid var(--gold);
      padding: 1.25rem 1.5rem; border-radius: var(--radius-md);
      margin: 2rem 0; color: var(--charcoal);
    }

    .highlight strong { color: var(--gold); }

    /* ══ BENEFITS GRID ══ */
    .benefits-grid {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1.5rem; margin-top: 2rem;
    }

    .benefit-card {
      background: white; border: 1px solid rgba(90,138,104,0.13);
      border-radius: var(--radius-lg); padding: 1.5rem;
      text-align: center; transition: all 0.3s var(--ease);
      box-shadow: var(--shadow-soft);
    }

    .benefit-card:hover { box-shadow: var(--shadow-med); transform: translateY(-5px); }

    .benefit-icon { font-size: 2.5rem; margin-bottom: 1rem; }

    .benefit-title {
      font-family: 'Lora', serif; font-size: 0.99rem; font-weight: 700;
      color: var(--charcoal); margin-bottom: 0.5rem;
    }

    .benefit-desc { font-size: 0.82rem; color: var(--text-soft); line-height: 1.68; }

    /* ══ CANDIDATES GRID ══ */
    .candidates-grid {
      display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 1.5rem; margin-top: 1.5rem;
    }

    .candidate-card {
      background: white; border: 1px solid rgba(90,138,104,0.13);
      border-radius: var(--radius-lg); padding: 1.5rem;
      transition: all 0.3s var(--ease); box-shadow: var(--shadow-soft);
    }

    .candidate-card:hover { box-shadow: var(--shadow-med); transform: translateY(-3px); }

    .candidate-card h3 {
      margin: 0 0 0.5rem; font-size: 1.1rem;
      color: var(--charcoal); font-family: 'Lora', serif;
    }

    .candidate-card p { margin: 0.5rem 0; font-size: 0.9rem; color: var(--text-soft); line-height: 1.6; }

    .skills-wrap { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 1rem; }

    .skill-tag {
      background: rgba(90,138,104,0.08); color: var(--sage-dark);
      padding: 0.35rem 0.75rem; border-radius: var(--radius-pill);
      font-size: 0.8rem; font-weight: 600; border: 1px solid rgba(90,138,104,0.15);
    }

    .btn-contact {
      width: 100%; margin-top: 1rem; padding: 0.75rem;
      background: linear-gradient(135deg, var(--sage), var(--sage-dark));
      color: white; border: none; border-radius: var(--radius-sm);
      font-weight: 600; cursor: pointer; font-size: 0.9rem;
      transition: all 0.25s var(--ease);
    }

    .btn-contact:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(90,138,104,0.3); }

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
      .benefits-grid { grid-template-columns: repeat(2, 1fr); }
      .candidates-grid { grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); }
    }

    @media (max-width: 860px) {
      .footer-top { grid-template-columns: 1fr 1fr; }
      .nav-links { display: none; }
      .hamburger { display: flex; }
    }

    @media (max-width: 600px) {
      .navbar { padding: 0 1.3rem; }
      .page-hero { padding: 2rem 1.3rem 1.5rem; }
      .container { padding: 1.5rem 1.3rem 3rem; }
      .benefits-grid { grid-template-columns: 1fr; }
      .candidates-grid { grid-template-columns: 1fr; }
      .footer-top { grid-template-columns: 1fr; gap: 2rem; }
      .footer-bottom { flex-direction: column; text-align: center; }
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
      <li><a href="../employee-finder/">Find Talent</a></li>
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
    <a href="../employee-finder/">🔍 Find Talent</a>
    <a href="./">🔒 Blind Hiring</a>
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
        <h1>🔒 Blind Hiring</h1>
        <p>Fair, merit-based screening that reduces bias</p>
      </div>
    </div>

    <div class="container">

      <div class="info-section">
        <h2>What is Blind Hiring?</h2>
        <p>Blind hiring is a recruitment approach where you evaluate candidates based purely on <strong>skills and experience</strong> — without seeing personal information like name, age, photo, or background that might introduce unconscious bias.</p>
        <p>When candidates apply for blind hiring positions, we anonymize their profiles so you focus on what matters most: their ability to do the job.</p>
      </div>

      <div class="highlight">
        <strong>✨ The Result:</strong> Better hiring decisions based on merit. Research shows blind hiring leads to more diverse teams and better overall job performance.
      </div>

      <div class="benefits-grid">
        <div class="benefit-card">
          <div class="benefit-icon">⚖️</div>
          <div class="benefit-title">Unbiased Screening</div>
          <div class="benefit-desc">Evaluate candidates purely on skills, not demographics</div>
        </div>
        <div class="benefit-card">
          <div class="benefit-icon">👥</div>
          <div class="benefit-title">Diverse Talent</div>
          <div class="benefit-desc">Access a wider pool of qualified candidates</div>
        </div>
        <div class="benefit-card">
          <div class="benefit-icon">🎯</div>
          <div class="benefit-title">Better Fit</div>
          <div class="benefit-desc">Hire people who match your actual job requirements</div>
        </div>
        <div class="benefit-card">
          <div class="benefit-icon">📈</div>
          <div class="benefit-title">Higher Performance</div>
          <div class="benefit-desc">Studies show merit-based hires perform better</div>
        </div>
      </div>

      <div class="info-section">
        <h2>How Blind Hiring Works (For Employers)</h2>
        <ol style="margin-left: 1.5rem;">
          <li style="margin-bottom: 1rem;"><strong>You Post a Job with Blind Hiring Enabled:</strong> Include in your job posting that this position uses blind hiring</li>
          <li style="margin-bottom: 1rem;"><strong>Candidates Apply:</strong> Their profiles arrive anonymized — you see skills, experience, and achievements only</li>
          <li style="margin-bottom: 1rem;"><strong>You Screen Based on Merit:</strong> Review applications focusing on actual job requirements</li>
          <li style="margin-bottom: 1rem;"><strong>Interview Top Candidates:</strong> Once you've selected candidates for interview, their real identity is revealed</li>
          <li><strong>Proceed Normally:</strong> Standard hiring process resumes from interview stage onward</li>
        </ol>
      </div>

      <div class="info-section">
        <h2>What You See (Anonymized)</h2>
        <ul>
          <li>Professional skill assessment and verification</li>
          <li>Work experience (without company names that might reveal age)</li>
          <li>Educational background</li>
          <li>Relevant certifications and achievements</li>
          <li>Portfolio or work samples (if applicable)</li>
        </ul>
      </div>

      <div class="info-section">
        <h2>What You DON'T See</h2>
        <ul>
          <li>Candidate name or photo</li>
          <li>Age or date of birth</li>
          <li>Gender identity</li>
          <li>Location (until interview stage)</li>
          <li>Personal background details</li>
        </ul>
      </div>

      <div class="highlight">
        <strong>💡 Employer Benefits:</strong> Blind hiring isn't about sacrificing quality — it's about expanding your talent pool. You get access to skilled candidates you might have overlooked due to unconscious bias, resulting in better hires and more diverse teams.
      </div>

      <div class="info-section">
        <h2>Available Blind Candidates</h2>
        <p>Browse anonymized candidate profiles based purely on skills and experience:</p>
        <div class="candidates-grid">
          <?php if (empty($blind_candidates)): ?>
            <p style="grid-column: 1/-1; text-align: center; color: var(--text-light); padding: 2rem;">No candidates available yet.</p>
          <?php else: ?>
            <?php foreach ($blind_candidates as $candidate): ?>
              <div class="candidate-card">
                <h3><?php echo htmlspecialchars($candidate['candidate_name']); ?></h3>
                <p><?php echo !empty($candidate['summary']) ? htmlspecialchars(substr($candidate['summary'], 0, 120)) . '...' : 'No summary available'; ?></p>
                <div class="skills-wrap">
                  <strong style="font-size: 0.85rem; color: var(--text-mid); display: block; margin-bottom: 0.5rem;">Key Skills:</strong>
                  <?php if (!empty($candidate['skills'])): ?>
                    <?php foreach ($candidate['skills'] as $skill): ?>
                      <span class="skill-tag"><?php echo htmlspecialchars($skill); ?></span>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <span style="color: var(--text-light); font-size: 0.9rem;">No skills listed</span>
                  <?php endif; ?>
                </div>
                <form method="POST" style="display: inline; width: 100%;">
                  <input type="hidden" name="action" value="contact_candidate">
                  <input type="hidden" name="candidate_id" value="<?php echo $candidate['employee_id']; ?>">
                  <button type="submit" onclick="return confirm('This will send an anonymous job offer message to this candidate. Continue?')" class="btn-contact">
                    💬 Contact Candidate
                  </button>
                </form>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
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
  </script>

</body>
</html>
