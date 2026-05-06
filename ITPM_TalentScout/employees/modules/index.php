<?php
session_start();
require_once __DIR__ . '/../../database/db.php';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Tools — For Job Seekers | TalentScout AI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900;1,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
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
      body::before {
        content: '';
        position: fixed;
        inset: 0;
        pointer-events: none;
        z-index: 9999;
        opacity: 0.03;
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3Cfilter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)'/%3E%3C/svg%3E");
      }
      a { text-decoration: none; color: inherit; }

      /* NAVBAR */
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
        display: flex; align-items: center; gap: 0.35rem;
      }
      .btn-nav-solid:hover { background: var(--sage); transform: translateY(-1px); }

      /* MODULES SECTION */
      .modules-section {
        padding: 8rem 2rem 4rem;
        background: var(--cream);
        min-height: calc(100vh - 64px);
      }
      .modules-inner {
        max-width: 1080px;
        margin: 0 auto;
      }
      .modules-header {
        text-align: center;
        margin-bottom: 3.5rem;
      }
      .modules-header .eyebrow {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: var(--sage);
        margin-bottom: 0.7rem;
      }
      .modules-header h1 {
        font-family: 'Playfair Display', serif;
        font-size: clamp(1.8rem, 3vw, 2.4rem);
        font-weight: 900;
        color: var(--charcoal);
        letter-spacing: -0.025em;
        margin-bottom: 0.7rem;
      }
      .modules-header p {
        font-size: 0.88rem;
        color: var(--warm-mid);
        max-width: 520px;
        margin: 0 auto;
        line-height: 1.7;
      }

      /* MODULES GRID */
      .modules-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.4rem;
      }
      .module-card {
        background: var(--cream);
        border-radius: var(--radius-xl);
        padding: 0;
        overflow: hidden;
        border: 1px solid rgba(139,128,112,0.12);
        box-shadow: 0 4px 24px rgba(42,42,34,0.07);
        transition: transform 0.3s var(--ease-out), box-shadow 0.3s;
        display: flex; flex-direction: column;
        cursor: pointer;
        text-decoration: none;
        color: inherit;
      }
      .module-card:hover {
        transform: translateY(-7px);
        box-shadow: 0 18px 48px rgba(42,42,34,0.14);
      }
      .module-thumb {
        width: 100%;
        height: 160px;
        background: var(--sage-pale);
        display: flex; align-items: center; justify-content: center;
        font-size: 2.8rem;
        overflow: hidden;
        position: relative;
      }
      .module-thumb .overlay-tag {
        position: absolute;
        top: 0.75rem; left: 0.75rem;
        background: rgba(250,248,243,0.9);
        backdrop-filter: blur(8px);
        border-radius: var(--radius-pill);
        padding: 0.22rem 0.7rem;
        font-size: 0.7rem;
        font-weight: 600;
        color: var(--sage-deep);
        letter-spacing: 0.05em;
      }
      .module-body {
        padding: 1.5rem 1.4rem 1.4rem;
        flex: 1;
        display: flex; flex-direction: column;
      }
      .module-body h3 {
        font-family: 'Playfair Display', serif;
        font-size: 1.02rem;
        font-weight: 700;
        color: var(--charcoal);
        margin-bottom: 0.5rem;
        letter-spacing: -0.01em;
      }
      .module-body p {
        font-size: 0.81rem;
        color: var(--warm-mid);
        line-height: 1.66;
        flex: 1;
        margin-bottom: 1.1rem;
      }
      .module-link {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--sage-deep);
        transition: gap 0.2s, color 0.2s;
      }
      .module-link:hover { gap: 0.55rem; color: var(--sage); }

      /* SCROLL REVEAL */
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

      /* FOOTER */
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

      /* RESPONSIVE */
      @media (max-width: 960px) {
        .modules-grid { grid-template-columns: repeat(2, 1fr); }
        .footer-top { grid-template-columns: 1fr 1fr; }
        .nav-links { display: none; }
      }
      @media (max-width: 600px) {
        .navbar { padding: 0 1.2rem; }
        .modules-grid { grid-template-columns: 1fr; }
        .footer-top { grid-template-columns: 1fr; }
        .footer-bottom { flex-direction: column; text-align: center; }
        .modules-section { padding: 7rem 1.5rem 3rem; }
      }
    </style>
  </head>
  <body>
    <!-- NAVBAR -->
    <nav class="navbar">
      <a href="../index.php" class="nav-logo">
        <div class="nav-logo-mark">TS</div>
        <span>Talent<em>Scout</em> AI</span>
      </a>
      <ul class="nav-links">
        <li><a href="../index.php">Home</a></li>
        <li><a href="./job-postings/index.php">Browse Jobs</a></li>
        <li><a href="./ai-matching/index.php">AI Matching</a></li>
        <li><a href="./resume-builder/index.php">Resume Builder</a></li>
        <li><a href="./skill-gap-analysis/index.php">Skills</a></li>
        <li><a href="./applicant-tracking/index.php">Applications</a></li>
        <li><a href="./messages/index.php">Messages</a></li>
      </ul>
      <div class="nav-right">
        <?php if (isset($_SESSION['employee_id'])): ?>
          <span class="nav-user">Welcome, <?php echo htmlspecialchars($_SESSION['employee_name'] ?? 'User'); ?></span>
          <a href="../logout.php" class="btn-nav-ghost">Logout</a>
        <?php else: ?>
          <a href="../login.php" class="btn-nav-ghost">Login</a>
          <a href="../signup.php" class="btn-nav-solid">Get Started →</a>
        <?php endif; ?>
      </div>
    </nav>

    <!-- MODULES SECTION -->
    <div class="modules-section">
      <div class="modules-inner">
        <div class="modules-header reveal">
          <div class="eyebrow">Your Toolkit</div>
          <h1>Job Seeker Modules</h1>
          <p>Access all the tools you need to find opportunities, build your profile, and advance your career.</p>
        </div>

        <div class="modules-grid">
          <!-- Browse Jobs -->
          <a href="./job-postings/index.php" class="module-card reveal">
            <div class="module-thumb">
              <span style="font-size:2.8rem;">💼</span>
              <span class="overlay-tag">Open Roles</span>
            </div>
            <div class="module-body">
              <h3>Job Postings</h3>
              <p>Browse hundreds of job opportunities across all barangays in Nasugbu. Filter by skills, location, and salary.</p>
              <span class="module-link">View Jobs <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></span>
            </div>
          </a>

          <!-- AI Matching -->
          <a href="./ai-matching/index.php" class="module-card reveal reveal-delay-1">
            <div class="module-thumb" style="background: linear-gradient(135deg, #d4e6d6, #f0e4c8);">
              <span style="font-size:2.8rem;">🤖</span>
              <span class="overlay-tag">AI-Powered</span>
            </div>
            <div class="module-body">
              <h3>AI Job Matching</h3>
              <p>Get personalized job recommendations based on your skills. AI-powered matching finds your perfect fit.</p>
              <span class="module-link">See Matches <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></span>
            </div>
          </a>

          <!-- Skill Gap Analysis -->
          <a href="./skill-gap-analysis/index.php" class="module-card reveal reveal-delay-2">
            <div class="module-thumb">
              <span style="font-size:2.8rem;">📊</span>
              <span class="overlay-tag">Analysis</span>
            </div>
            <div class="module-body">
              <h3>Skill Gap Analysis</h3>
              <p>Discover what skills you need to unlock more opportunities. Get personalized course recommendations.</p>
              <span class="module-link">Analyze Skills <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></span>
            </div>
          </a>

          <!-- Application Tracker -->
          <a href="./applicant-tracking/index.php" class="module-card reveal reveal-delay-1">
            <div class="module-thumb" style="background: linear-gradient(135deg, #f0e4c8, #d4e6d6);">
              <span style="font-size:2.8rem;">📑</span>
              <span class="overlay-tag">Live Status</span>
            </div>
            <div class="module-body">
              <h3>Application Tracker</h3>
              <p>Monitor your applications in real-time. Track progress from pending applications through job offers.</p>
              <span class="module-link">Track Applications <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></span>
            </div>
          </a>

          <!-- Resume Builder -->
          <a href="./resume-builder/index.php" class="module-card reveal reveal-delay-2">
            <div class="module-thumb">
              <span style="font-size:2.8rem;">📝</span>
              <span class="overlay-tag">Templates</span>
            </div>
            <div class="module-body">
              <h3>Resume Builder</h3>
              <p>Create a professional resume in minutes. Simple step-by-step process with real-time preview.</p>
              <span class="module-link">Build Resume <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></span>
            </div>
          </a>

          <!-- Messages -->
          <a href="./messages/index.php" class="module-card reveal reveal-delay-1">
            <div class="module-thumb" style="background: linear-gradient(135deg, #d4e6d6, #f0e4c8);">
              <span style="font-size:2.8rem;">💬</span>
              <span class="overlay-tag">Chat</span>
            </div>
            <div class="module-body">
              <h3>Messages</h3>
              <p>Chat directly with employers about your applications. Get real-time updates and ask questions.</p>
              <span class="module-link">View Messages <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></span>
            </div>
          </a>
        </div>
      </div>
    </div>

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
              <li><a href="./job-postings/index.php">Browse Jobs</a></li>
              <li><a href="./ai-matching/index.php">AI Matching</a></li>
              <li><a href="./skill-gap-analysis/index.php">Skill Analysis</a></li>
              <li><a href="./applicant-tracking/index.php">Track Applications</a></li>
              <li><a href="./resume-builder/index.php">Resume Builder</a></li>
              <li><a href="./messages/index.php">Messages</a></li>
            </ul>
          </div>
          <div class="footer-col">
            <h4>For Employers</h4>
            <ul>
              <li><a href="../../employers/modules/post-jobs/index.php">Post Jobs</a></li>
              <li><a href="../../employers/modules/blind-hiring/index.php">Blind Hiring</a></li>
              <li><a href="../../employers/modules/employee-finder/index.php">Find Talent</a></li>
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
          <span>Built for Local Employment & Community Growth</span>
        </div>
      </div>
    </footer>

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
    <script src="../../employee-auth.js"></script>
  </body>
</html>
