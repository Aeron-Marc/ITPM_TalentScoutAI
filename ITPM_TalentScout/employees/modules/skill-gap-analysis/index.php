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

// Get employee's most recent resume and skills
$resume_query = "SELECT rs.skill_name FROM resumes r 
                 JOIN resume_skills rs ON r.resume_id = rs.resume_id 
                 WHERE r.employee_id = ? 
                 ORDER BY r.updated_at DESC LIMIT 50";
$resume_stmt = $conn->prepare($resume_query);
$resume_stmt->bind_param("i", $employee_id);
$resume_stmt->execute();
$resume_result = $resume_stmt->get_result();

// Build employee skills array
$employee_skills = array();
while ($skill = $resume_result->fetch_assoc()) {
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../styles/global.css" />
    <link rel="stylesheet" href="../../../styles/page-layout.css" />
    <link rel="stylesheet" href="../../navbar.css" />
    <style>
      * {
        font-family: 'Poppins', sans-serif;
      }
      .gap-layout {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2.5rem;
        display: grid;
        grid-template-columns: 1fr 340px;
        gap: 2rem;
        align-items: start;
      }

      /* Summary banner */
      .summary-banner {
        background: linear-gradient(135deg, #e8f5e9, #f2fcf3);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.75rem;
        display: flex;
        gap: 2rem;
        align-items: center;
        margin-bottom: 2rem;
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
        background: white;
        border-radius: 50%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
      }
      .score-val {
        font-size: 1.3rem;
        font-weight: 800;
        color: var(--primary-darker);
        line-height: 1;
      }
      .score-lbl {
        font-size: 0.68rem;
        color: var(--text-light);
      }
      .summary-info h3 {
        font-size: 1.2rem;
        font-weight: 700;
        margin-bottom: 0.3rem;
      }
      .summary-info p {
        font-size: 0.9rem;
        color: var(--text-light);
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
        border-radius: 100px;
        font-size: 0.8rem;
        font-weight: 600;
      }
      .pill-green {
        background: #d4edda;
        color: #155724;
      }
      .pill-red {
        background: #f8d7da;
        color: #721c24;
      }
      .pill-yellow {
        background: #fff3cd;
        color: #856404;
      }

      /* Skill Sections */
      .skill-section {
        background: white;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
      }
      .skill-section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.25rem;
      }
      .skill-section-title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--text-dark);
      }
      .skill-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.25rem;
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
        color: var(--text-dark);
      }
      .skill-pct {
        font-size: 0.82rem;
        font-weight: 700;
      }
      .skill-pct.good {
        color: var(--primary-darker);
      }
      .skill-pct.warn {
        color: #856404;
      }
      .skill-pct.bad {
        color: #721c24;
      }

      /* Gap Tags */
      .gap-tag {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.4rem 0.9rem;
        border-radius: 8px;
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
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffd77a;
      }

      /* Courses */
      .course-card {
        background: white;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        overflow: hidden;
        margin-bottom: 1rem;
        transition: all 0.2s;
      }
      .course-card:hover {
        box-shadow: var(--shadow);
        transform: translateY(-2px);
      }
      .course-banner {
        height: 8px;
      }
      .course-body {
        padding: 1.25rem;
      }
      .course-provider {
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--text-light);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.35rem;
      }
      .course-title {
        font-size: 0.97rem;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 0.5rem;
      }
      .course-meta {
        display: flex;
        gap: 0.75rem;
        font-size: 0.8rem;
        color: var(--text-light);
        margin-bottom: 0.75rem;
      }
      .course-skills {
        display: flex;
        gap: 0.35rem;
        flex-wrap: wrap;
        margin-bottom: 0.9rem;
      }
      .course-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
      }
      .course-price {
        font-weight: 700;
        color: var(--primary-darker);
        font-size: 0.92rem;
      }

      /* Sidebar */
      .side-card {
        background: white;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.25rem;
        margin-bottom: 1.25rem;
      }
      .side-card-title {
        font-size: 0.88rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--text-light);
        margin-bottom: 1rem;
      }
      .career-path {
        display: flex;
        flex-direction: column;
        gap: 0;
      }
      .path-step {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding-bottom: 1.25rem;
        position: relative;
      }
      .path-step:last-child {
        padding-bottom: 0;
      }
      .path-step::before {
        content: "";
        position: absolute;
        left: 14px;
        top: 28px;
        bottom: 0;
        width: 2px;
        background: var(--border);
      }
      .path-step:last-child::before {
        display: none;
      }
      .path-dot {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.72rem;
        font-weight: 700;
        flex-shrink: 0;
        position: relative;
        z-index: 1;
      }
      .path-dot.done {
        background: var(--primary-dark);
        color: white;
      }
      .path-dot.current {
        background: var(--primary);
        color: var(--primary-darker);
        border: 2px solid var(--primary-dark);
      }
      .path-dot.next {
        background: var(--border);
        color: var(--text-light);
      }
      .path-info {
        padding-top: 0.15rem;
      }
      .path-title {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--text-dark);
      }
      .path-sub {
        font-size: 0.78rem;
        color: var(--text-light);
        margin-top: 0.15rem;
      }

      /* Progress summary */
      .micro-stat {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.6rem 0;
        border-bottom: 1px solid var(--border);
        font-size: 0.87rem;
      }
      .micro-stat:last-child {
        border-bottom: none;
      }
      .micro-val {
        font-weight: 700;
        color: var(--primary-darker);
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
        <li><a href="../ai-matching/index.php">AI Matching</a></li>
        <li><a href="../resume-builder/index.php">Resume Builder</a></li>
        <li><a href="./index.php" class="active">Skills</a></li>
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
          <a href="../../index.php">Home</a> / Skill Gap Analysis
        </div>
        <h1>📊 Skill Gap Analysis</h1>
        <p>
          Discover which skills you already have, identify gaps, and get
          personalized upskilling recommendations to boost your employability.
        </p>
      </div>
    </div>

    <!-- MAIN LAYOUT -->
    <div class="gap-layout">
      <div>
        <!-- SUMMARY BANNER -->
        <div class="summary-banner">
          <div class="summary-score">
            <div class="score-ring" style="background: conic-gradient(var(--primary-dark) 0% <?php echo $employability_score; ?>%, var(--border) <?php echo $employability_score; ?>% 100%);">
              <div class="score-inner">
                <div class="score-val"><?php echo $employability_score; ?>%</div>
                <div class="score-lbl">Readiness</div>
              </div>
            </div>
            <div style="font-size: 0.78rem; color: var(--text-light)">
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
              <span class="summary-pill pill-yellow">⚠ 2 Partial Skills</span>
            </div>
          </div>
        </div>

        <!-- CURRENT SKILLS -->
        <div class="skill-section">
          <div class="skill-section-header">
            <div class="skill-section-title">✅ Current Skills</div>
            <span class="badge badge-green"><?php echo count($current_skills); ?> Verified</span>
          </div>
          <div class="skill-grid">
            <?php 
            $proficiency_levels = [85, 90, 88, 82, 87, 78, 83]; // Varied proficiency levels
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

        <!-- SKILL GAPS -->
        <div class="skill-section">
          <div class="skill-section-header">
            <div class="skill-section-title">❌ Identified Skill Gaps</div>
            <span class="badge badge-red"><?php echo $critical_gaps_count; ?> Critical</span>
          </div>
          <p
            style="
              font-size: 0.87rem;
              color: var(--text-light);
              margin-bottom: 1.25rem;
            "
          >
            These skills are in demand across <?php echo $total_jobs; ?>+ job postings but
            are missing from your profile.
          </p>
          <div class="skill-grid">
            <?php foreach ($skill_gaps_display as $skill => $data): ?>
              <div class="skill-item">
                <div class="skill-label-row">
                  <span class="skill-name"><?php echo htmlspecialchars($data['name']); ?></span
                  ><span class="skill-pct bad">0%</span>
                </div>
                <div class="progress-bar">
                  <div class="progress-fill gap" style="width: 5%"></div>
                </div>
                <div
                  style="font-size: 0.78rem; color: #856404; margin-top: 0.3rem"
                >
                  Required by <?php echo $data['demand_count']; ?> jobs (<?php echo $data['demand_percent']; ?>%)
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <div
            style="
              margin-top: 1.25rem;
              padding-top: 1.25rem;
              border-top: 1px solid var(--border);
            "
          >
            <div
              style="
                font-size: 0.87rem;
                font-weight: 600;
                color: var(--text-dark);
                margin-bottom: 0.5rem;
              "
            >
              All Gap Tags
            </div>
            <div>
              <?php 
              $gap_count = 0;
              foreach ($skill_gaps as $skill => $data):
                if ($gap_count >= 5) break;
              ?>
                <span class="gap-tag missing">✗ <?php echo htmlspecialchars($data['name']); ?></span>
              <?php 
                $gap_count++;
              endforeach; 
              ?>
            </div>
          </div>
        </div>

        <!-- SKILL TRENDS VISUALIZATION -->
        <div style="margin-bottom: 1.5rem">
          <div class="section-label">Market Insights</div>
          <h2 class="section-title" style="font-size: 1.5rem">
            Current Skill Trends
          </h2>
          <p
            style="
              color: var(--text-light);
              font-size: 0.9rem;
              margin-top: 0.3rem;
            "
          >
            See which skills are most in-demand across job postings in your market.
          </p>
        </div>

        <!-- CHARTS CONTAINER -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
          <!-- BAR CHART: Top Skills Demand -->
          <div style="background: white; border: 1px solid var(--border); border-radius: var(--radius); padding: 1.5rem;">
            <h3 style="margin-bottom: 1rem; font-size: 1rem; font-weight: 700; color: var(--text-dark);">Top Demanded Skills</h3>
            <canvas id="skillDemandChart" height="250"></canvas>
          </div>

          <!-- PIE CHART: Skills Distribution -->
          <div style="background: white; border: 1px solid var(--border); border-radius: var(--radius); padding: 1.5rem;">
            <h3 style="margin-bottom: 1rem; font-size: 1rem; font-weight: 700; color: var(--text-dark);">Skill Distribution</h3>
            <canvas id="skillDistributionChart" height="250"></canvas>
          </div>
        </div>

        <!-- SKILL STATS TABLE -->
        <div style="background: white; border: 1px solid var(--border); border-radius: var(--radius); padding: 1.5rem; margin-bottom: 2rem;">
          <h3 style="margin-bottom: 1rem; font-size: 1rem; font-weight: 700; color: var(--text-dark);">Skill Market Analysis</h3>
          <table style="width: 100%; border-collapse: collapse;">
            <thead>
              <tr style="background: #f5f5f5; border-bottom: 2px solid var(--border);">
                <th style="padding: 0.75rem; text-align: left; font-weight: 700; color: var(--text-dark);">Skill</th>
                <th style="padding: 0.75rem; text-align: center; font-weight: 700; color: var(--text-dark);">Jobs Requiring</th>
                <th style="padding: 0.75rem; text-align: center; font-weight: 700; color: var(--text-dark);">Demand %</th>
                <th style="padding: 0.75rem; text-align: center; font-weight: 700; color: var(--text-dark);">Your Status</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              $rank = 1;
              foreach (array_slice($market_skills, 0, 10) as $skill => $frequency):
                $demand_pct = round(($frequency / $total_jobs) * 100, 1);
                $has_skill = in_array($skill, $employee_skills) ? true : false;
                $status_class = $has_skill ? 'style="color: #155724; font-weight: 700;"' : 'style="color: #721c24; font-weight: 700;"';
                $status_text = $has_skill ? '✓ You have it' : '✗ Need to learn';
              ?>
                <tr style="border-bottom: 1px solid var(--border);">
                  <td style="padding: 0.75rem; color: var(--text-dark); font-weight: 600;">
                    <span style="background: var(--primary-light); color: var(--primary-dark); padding: 0.25rem 0.5rem; border-radius: 4px; margin-right: 0.5rem;"><?php echo $rank; ?></span>
                    <?php echo htmlspecialchars(ucwords($skill)); ?>
                  </td>
                  <td style="padding: 0.75rem; text-align: center; color: var(--text-dark);"><?php echo $frequency; ?></td>
                  <td style="padding: 0.75rem; text-align: center; color: var(--text-dark);"><?php echo $demand_pct; ?>%</td>
                  <td style="padding: 0.75rem; text-align: center;" <?php echo $status_class; ?>><?php echo $status_text; ?></td>
                </tr>
              <?php 
                $rank++;
              endforeach; 
              ?>
            </tbody>
          </table>
        </div>

        <!-- Chart.js Library -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
          // Prepare data for charts
          const marketSkills = <?php echo json_encode(array_slice($market_skills, 0, 8)); ?>;
          const employeeSkills = <?php echo json_encode($employee_skills); ?>;
          const totalJobs = <?php echo $total_jobs; ?>;

          // Extract labels and data
          let skillLabels = [];
          let skillDemand = [];
          let skillColors = [];
          
          for (const [skill, count] of Object.entries(marketSkills)) {
            skillLabels.push(skill.charAt(0).toUpperCase() + skill.slice(1));
            skillDemand.push(count);
            // Color based on if user has skill
            if (employeeSkills.includes(skill.toLowerCase())) {
              skillColors.push('rgba(34, 197, 94, 0.7)'); // Green for have
            } else {
              skillColors.push('rgba(220, 38, 38, 0.7)'); // Red for need
            }
          }

          // Bar Chart: Top Skills Demand
          const barCtx = document.getElementById('skillDemandChart').getContext('2d');
          new Chart(barCtx, {
            type: 'bar',
            data: {
              labels: skillLabels,
              datasets: [{
                label: 'Job Postings Requiring Skill',
                data: skillDemand,
                backgroundColor: skillColors,
                borderColor: skillColors.map(c => c.replace('0.7', '1')),
                borderWidth: 2,
                borderRadius: 5
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: true,
              indexAxis: 'y',
              plugins: {
                legend: {
                  display: false
                },
                tooltip: {
                  callbacks: {
                    label: function(context) {
                      const pct = ((context.parsed.x / totalJobs) * 100).toFixed(1);
                      return context.parsed.x + ' jobs (' + pct + '%)';
                    }
                  }
                }
              },
              scales: {
                x: {
                  beginAtZero: true,
                  ticks: {
                    font: { size: 11 }
                  }
                },
                y: {
                  ticks: {
                    font: { size: 12 }
                  }
                }
              }
            }
          });

          // Pie Chart: Skills Distribution (Have vs Need)
          const haveCount = skillDemand.reduce((sum, demand, idx) => 
            sum + (employeeSkills.includes(skillLabels[idx].toLowerCase()) ? 1 : 0), 0
          );
          const needCount = skillDemand.length - haveCount;

          const pieCtx = document.getElementById('skillDistributionChart').getContext('2d');
          new Chart(pieCtx, {
            type: 'doughnut',
            data: {
              labels: ['Skills You Have', 'Skills to Learn'],
              datasets: [{
                data: [haveCount, needCount],
                backgroundColor: [
                  'rgba(34, 197, 94, 0.8)',  // Green
                  'rgba(220, 38, 38, 0.8)'   // Red
                ],
                borderColor: [
                  'rgba(34, 197, 94, 1)',
                  'rgba(220, 38, 38, 1)'
                ],
                borderWidth: 2
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: true,
              plugins: {
                legend: {
                  position: 'bottom',
                  labels: {
                    font: { size: 12 },
                    padding: 15
                  }
                },
                tooltip: {
                  callbacks: {
                    label: function(context) {
                      const total = context.dataset.data.reduce((a, b) => a + b, 0);
                      const pct = ((context.parsed / total) * 100).toFixed(1);
                      return context.label + ': ' + context.parsed + ' (' + pct + '%)';
                    }
                  }
                }
              }
            }
          });
        </script>
      </div>

      <!-- SIDEBAR -->
      <aside>
        <div class="side-card">
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
            ><span class="micro-val" style="color: var(--primary-dark)"><?php echo min(100, $employability_score + 20); ?>%</span>
          </div>
        </div>

        <div class="side-card">
          <div class="side-card-title">Career Path</div>
          <div class="career-path">
            <div class="path-step">
              <div class="path-dot done">✓</div>
              <div class="path-info">
                <div class="path-title">Junior Developer</div>
                <div class="path-sub">Current level • Achieved</div>
              </div>
            </div>
            <div class="path-step">
              <div class="path-dot current">→</div>
              <div class="path-info">
                <div class="path-title">Mid-Level Developer</div>
                <div class="path-sub">In progress • +3 skills needed</div>
              </div>
            </div>
            <div class="path-step">
              <div class="path-dot next">3</div>
              <div class="path-info">
                <div class="path-title">Senior Developer</div>
                <div class="path-sub">Future goal • 2–3 years</div>
              </div>
            </div>
            <div class="path-step">
              <div class="path-dot next">4</div>
              <div class="path-info">
                <div class="path-title">Tech Lead / Architect</div>
                <div class="path-sub">Long-term target</div>
              </div>
            </div>
          </div>
        </div>

        <div class="side-card">
          <div class="side-card-title">Job Market Demand</div>
          <div
            style="
              font-size: 0.82rem;
              color: var(--text-light);
              margin-bottom: 1rem;
            "
          >
            Most requested skills in Nasugbu tech jobs right now:
          </div>
          <div class="progress-wrap">
            <div class="progress-label">
              <span>JavaScript</span><span>94%</span>
            </div>
            <div class="progress-bar">
              <div class="progress-fill" style="width: 94%"></div>
            </div>
          </div>
          <div class="progress-wrap">
            <div class="progress-label">
              <span>React / Vue</span><span>78%</span>
            </div>
            <div class="progress-bar">
              <div class="progress-fill" style="width: 78%"></div>
            </div>
          </div>
          <div class="progress-wrap">
            <div class="progress-label">
              <span>TypeScript</span><span>72%</span>
            </div>
            <div class="progress-bar">
              <div class="progress-fill" style="width: 72%"></div>
            </div>
          </div>
          <div class="progress-wrap">
            <div class="progress-label"><span>SQL</span><span>65%</span></div>
            <div class="progress-bar">
              <div class="progress-fill" style="width: 65%"></div>
            </div>
          </div>
          <div class="progress-wrap">
            <div class="progress-label">
              <span>Python</span><span>58%</span>
            </div>
            <div class="progress-bar">
              <div class="progress-fill" style="width: 58%"></div>
            </div>
          </div>
        </div>

        <div class="side-card">
          <div class="side-card-title">Quick Actions</div>
          <div style="display: flex; flex-direction: column; gap: 0.6rem">
            <a
              href="../ai-matching/"
              class="btn btn-primary"
              style="justify-content: center"
              >🤖 View Job Matches</a
            >
            <a
              href="../job-postings/"
              class="btn btn-light"
              style="justify-content: center"
              >📋 Browse Jobs</a
            >

          </div>
        </div>
      </aside>
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
          <span>© 2026 TalentScout AI — PESO Nasugbu, Batangas</span>
          <span>Built for Local Employment</span>
        </div>
      </div>
    </footer>

    <script src="../../employee-auth.js"></script>
  </body>
</html>
