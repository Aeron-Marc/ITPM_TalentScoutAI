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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../styles/global.css" />
    <link rel="stylesheet" href="../../../styles/page-layout.css" />
    <link rel="stylesheet" href="../../navbar.css" />
    <style>
      * {
        font-family: 'Poppins', sans-serif;
      }
      .match-layout {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2.5rem;
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 2rem;
        align-items: start;
      }

      /* Profile Panel */
      .profile-panel {
        position: sticky;
        top: calc(var(--nav-height) + 1rem);
      }
      .profile-card {
        background: white;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        overflow: hidden;
      }
      .profile-header {
        background: linear-gradient(
          135deg,
          var(--primary-darker),
          var(--primary-dark)
        );
        padding: 1.75rem;
        text-align: center;
        color: white;
      }
      .profile-avatar {
        width: 72px;
        height: 72px;
        background: rgba(255, 255, 255, 0.2);
        border: 3px solid rgba(255, 255, 255, 0.4);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        font-weight: 800;
        margin: 0 auto 0.75rem;
        color: white;
      }
      .profile-name {
        font-size: 1.1rem;
        font-weight: 700;
      }
      .profile-loc {
        font-size: 0.85rem;
        opacity: 0.78;
        margin-top: 0.2rem;
      }
      .profile-body {
        padding: 1.25rem;
      }
      .profile-section {
        margin-bottom: 1.25rem;
      }
      .profile-section-title {
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.7px;
        color: var(--text-light);
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
        padding: 0.4rem 0;
        border-bottom: 1px solid var(--border);
      }
      .profile-info-row:last-child {
        border-bottom: none;
      }
      .profile-info-label {
        color: var(--text-light);
      }
      .profile-info-val {
        font-weight: 600;
        color: var(--text-dark);
      }
      .match-score-ring {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        background: conic-gradient(
          var(--primary-dark) 0% 87%,
          var(--border) 87% 100%
        );
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 0.5rem;
        position: relative;
      }
      .match-score-inner {
        width: 68px;
        height: 68px;
        background: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--primary-darker);
      }

      /* Matches Column */
      .matches-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
      }
      .matches-tabs {
        display: flex;
        gap: 0.25rem;
        background: var(--bg-light);
        border-radius: var(--radius-sm);
        padding: 0.3rem;
        border: 1px solid var(--border);
      }
      .tab-btn {
        padding: 0.45rem 1rem;
        border-radius: 6px;
        font-size: 0.85rem;
        font-weight: 500;
        color: var(--text-mid);
        cursor: pointer;
        background: transparent;
        border: none;
        font-family: inherit;
      }
      .tab-btn.active {
        background: white;
        color: var(--primary-darker);
        font-weight: 700;
        box-shadow: var(--shadow-sm);
      }

      .match-card {
        background: white;
        border: 1.5px solid var(--border);
        border-radius: var(--radius);
        padding: 1.5rem;
        margin-bottom: 1rem;
        transition: all 0.2s;
      }
      .match-card:hover {
        border-color: var(--primary-dark);
        box-shadow: var(--shadow);
      }
      .match-card-top {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1rem;
      }
      .match-logo {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        font-weight: 800;
        font-size: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
      }
      .match-info {
        flex: 1;
      }
      .match-title {
        font-size: 1.05rem;
        font-weight: 700;
        margin-bottom: 0.2rem;
      }
      .match-company {
        font-size: 0.87rem;
        color: var(--text-mid);
      }
      .match-pct {
        background: var(--primary);
        color: var(--primary-darker);
        padding: 0.35rem 0.8rem;
        border-radius: 100px;
        font-weight: 800;
        font-size: 0.88rem;
        white-space: nowrap;
        flex-shrink: 0;
      }
      .match-pct.high {
        background: #d4edda;
        color: #155724;
      }
      .match-pct.mid {
        background: #fff3cd;
        color: #856404;
      }
      .match-meta {
        display: flex;
        gap: 1.2rem;
        font-size: 0.83rem;
        color: var(--text-light);
        margin-bottom: 0.75rem;
        flex-wrap: wrap;
      }
      .match-bars {
        background: var(--bg-light);
        border-radius: var(--radius-sm);
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
        color: var(--text-mid);
        margin-bottom: 0.3rem;
      }

      /* How it Works */
      .how-section {
        background: white;
        margin: 2rem auto;
        max-width: 1200px;
        padding: 0 2.5rem 3rem;
      }
      .how-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.5rem;
        margin-top: 2rem;
      }
      .how-card {
        background: var(--bg-light);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.5rem;
        text-align: center;
      }
      .how-icon {
        font-size: 2rem;
        margin-bottom: 0.75rem;
      }
      .how-title {
        font-weight: 700;
        margin-bottom: 0.5rem;
      }
      .how-desc {
        font-size: 0.87rem;
        color: var(--text-light);
        line-height: 1.65;
      }

      /* Chips / Tags */
      .chip {
        display: inline-block;
        background: var(--primary-light);
        color: var(--primary-darker);
        padding: 0.4rem 0.8rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
        white-space: nowrap;
      }
      .chip-outline {
        display: inline-block;
        background: transparent;
        color: var(--text-light);
        padding: 0.4rem 0.8rem;
        border: 1px dashed var(--border);
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
        white-space: nowrap;
      }

      /* Job Skills Section */
      .job-skills {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.75rem;
      }

      /* Progress Bar */
      .progress-bar {
        width: 100%;
        height: 8px;
        background: var(--border);
        border-radius: 4px;
        overflow: hidden;
      }
      .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--primary-dark), var(--primary));
        border-radius: 4px;
        transition: width 0.3s ease;
      }

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

        .how-section {
          padding: 0 1.5rem 2rem;
        }

        .how-grid {
          grid-template-columns: 1fr;
        }
      }

      @media (max-width: 768px) {
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

        .matches-header .input.select {
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
        <li><a href="../job-postings/index.php">Browse Jobs</a></li>
        <li><a href="./index.php" class="active">AI Matching</a></li>
        <li><a href="../resume-builder/index.php">Resume Builder</a></li>
        <li><a href="../skill-gap-analysis/index.php">Skills</a></li>
        <li><a href="../applicant-tracking/index.php">Applications</a></li>
      </ul>
      <div class="nav-actions">
        <?php if (isset($_SESSION['employee_id'])): ?>
          <span class="nav-user">Welcome, <?php echo htmlspecialchars($_SESSION['employee_name'] ?? 'User'); ?></span>
          <a href="../../logout.php" class="btn btn-outline">Logout</a>
        <?php else: ?>
          <a href="../../login.php" class="btn btn-outline">Login</a>
          <a href="../../signup.php" class="btn btn-primary">Get Started</a>
        <?php endif; ?>
      </div>
    </nav>

    <!-- PAGE HEADER -->
    <div class="page-header">
      <div class="page-header-inner">
        <div class="breadcrumb">
          <a href="../../index.php">Home</a> / AI Job Matching
        </div>
        <h1>🤖 AI Job Matching</h1>
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
        <div class="profile-card">
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
                <div class="match-score-ring" style="background: conic-gradient(var(--primary-dark) 0% <?php echo $profile_score; ?>%, var(--border) <?php echo $profile_score; ?>% 100%)">
                  <div class="match-score-inner"><?php echo $profile_score; ?>%</div>
                </div>
                <div style="font-size: 0.82rem; color: var(--text-light)">
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
                        echo '<span class="chip" data-skill="' . htmlspecialchars($skill) . '">' . htmlspecialchars($skill) . ' <button type="button" class="chip-remove" onclick="removeSkill(this)" style="background: none; border: none; color: inherit; cursor: pointer; margin-left: 0.3rem; font-weight: bold;">×</button></span>';
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
                  style="width: 100%; padding: 0.5rem;" 
                />
                <div id="skillSuggestions" style="position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid var(--border); border-top: none; border-radius: 0 0 var(--radius-sm) var(--radius-sm); max-height: 200px; overflow-y: auto; display: none; z-index: 10;"></div>
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
            <div
              style="font-size: 1rem; font-weight: 700; color: var(--text-dark)"
            >
              Your AI-Generated Matches
            </div>
            <div
              style="
                font-size: 0.85rem;
                color: var(--text-light);
                margin-top: 0.2rem;
              "
            >
              <?php echo count($matched_jobs); ?> job<?php echo count($matched_jobs) != 1 ? 's' : ''; ?> matched to your profile
            </div>
          </div>
          <div style="display: flex; gap: 0.75rem; align-items: center">
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
          <div class="match-card" style="text-align: center; padding: 2rem;">
            <div style="font-size: 2rem; margin-bottom: 0.5rem">🔍</div>
            <div style="font-weight: 600; margin-bottom: 0.5rem">No Matches Found</div>
            <p style="color: var(--text-light); font-size: 0.9rem">
              Add more skills to your profile to see job matches. 
              <a href="../resume-builder/" style="color: var(--primary-dark); font-weight: 600;">Update your resume →</a>
            </p>
          </div>
        <?php else: 
          foreach ($matched_jobs as $job):
            $match_class = $job['overall_match'] >= 85 ? 'high' : ($job['overall_match'] >= 70 ? 'mid' : 'low');
            $employer_name = $engine->getEmployerName($job['employer_id']);
            $initials = strtoupper(substr($employer_name, 0, 1) . (strpos($employer_name, ' ') !== false ? substr($employer_name, strpos($employer_name, ' ') + 1, 1) : ''));
            
            // Generate random color for employer logo
            $colors = [
              ['bg' => '#e8f5e9', 'text' => '#2e7d32'],
              ['bg' => '#e3f2fd', 'text' => '#1565c0'],
              ['bg' => '#f3e5f5', 'text' => '#6a1b9a'],
              ['bg' => '#fff3e0', 'text' => '#e65100'],
              ['bg' => '#fce4ec', 'text' => '#c2185b'],
              ['bg' => '#e0f2f1', 'text' => '#00796b'],
            ];
            $color = $colors[crc32($job['employer_id']) % count($colors)];
        ?>
        <!-- Match Card -->
        <div class="match-card" data-match-score="<?php echo $job['overall_match']; ?>" data-work-type="<?php echo strtolower($job['work_type']); ?>">
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
            <div
              style="
                font-size: 0.8rem;
                font-weight: 700;
                color: var(--text-mid);
                margin-bottom: 0.6rem;
              "
            >
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
          <div style="margin-top: 1rem; display: flex; gap: 0.75rem">
            <a href="../job-postings/index.php?id=<?php echo $job['job_post_id']; ?>" class="btn btn-outline">
              View Full Posting
            </a>
            <a href="../applicant-tracking/submit-application.php?job_id=<?php echo $job['job_post_id']; ?>" class="btn btn-primary">
              Apply Now
            </a>
          </div>
        </div>
        <?php endforeach; 
        endif; 
        ?>
      </main>
    </div>

    <!-- HOW IT WORKS -->
    <div class="how-section">
      <div class="section-label">Behind the AI</div>
      <h2 class="section-title">How AI Matching Works</h2>
      <div class="how-grid">
        <div class="how-card">
          <div class="how-icon">🧠</div>
          <div class="how-title">Skills Analysis</div>
          <p class="how-desc">
            The AI maps your listed skills against job requirements, identifying
            exact, partial, and missing skill matches with weighted scoring.
          </p>
        </div>
        <div class="how-card">
          <div class="how-icon">📍</div>
          <div class="how-title">Barangay Targeting</div>
          <p class="how-desc">
            Location matching uses barangay-level data within Nasugbu,
            prioritizing nearby jobs while still surfacing high-quality remote
            options.
          </p>
        </div>
        <div class="how-card">
          <div class="how-icon">⚖️</div>
          <div class="how-title">Weighted Scoring</div>
          <p class="how-desc">
            Skills (50%), Location (30%), and Experience (20%) are weighted and
            combined to produce a single reliable match percentage score.
          </p>
        </div>
      </div>
    </div>

    <!-- FOOTER -->
    <footer class="footer">
      <div class="footer-inner">
        <div class="footer-top">
          <div class="footer-brand">
            <h3>TalentScout AI</h3>
            <p>
              Smart AI-powered recruitment platform for PESO Nasugbu, Batangas.
              Connecting local talent with local opportunities through fair,
              intelligent hiring.
            </p>
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
          <span>© 2026 TalentScout AI — PESO Nasugbu, Batangas</span>
          <span>Built for Local Employment</span>
        </div>
      </div>
    </footer>

    <script src="../../employee-auth.js"></script>
    <script>
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
                  .map(skill => `<div class="suggestion-item" onclick="selectSkill('${skill}')" style="padding: 0.6rem 0.75rem; cursor: pointer; border-bottom: 1px solid var(--border); font-size: 0.9rem;">${skill}</div>`)
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
        chip.innerHTML = skill + ' <button type="button" class="chip-remove" onclick="removeSkill(this)" style="background: none; border: none; color: inherit; cursor: pointer; margin-left: 0.3rem; font-weight: bold;">×</button>';
        
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
        const notification = document.createElement('div');
        notification.style.cssText = `
          position: fixed;
          top: 1rem;
          right: 1rem;
          padding: 1rem 1.5rem;
          background: ${type === 'success' ? '#d4edda' : type === 'error' ? '#f8d7da' : '#d1ecf1'};
          color: ${type === 'success' ? '#155724' : type === 'error' ? '#721c24' : '#0c5460'};
          border-radius: var(--radius);
          box-shadow: var(--shadow);
          font-weight: 500;
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
    </style>
  </body>
</html>
