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
    // Using a special message to indicate blind hiring - the chat system will display employer as anonymous
    $stmt = $conn->prepare("INSERT INTO message (sender_id, sender_type, receiver_id, receiver_type, message, timestamp) VALUES (?, 'employer', ?, 'employee', ?, NOW())");
    $stmt->bind_param("iis", $employer_id, $candidate_id, $job_offer_message);
    
    if ($stmt->execute()) {
      $stmt->close();
      // Redirect to chat with this candidate
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
  <link rel="stylesheet" href="../../../styles/global.css">
  <link rel="stylesheet" href="../../../styles/page-layout.css">
  <style>
    /* ===== STICKY FOOTER LAYOUT ===== */
    html,
    body {
      height: 100%;
      margin: 0;
      padding: 0;
    }

    body {
      display: flex;
      flex-direction: column;
    }

    /* Main content area expands to fill available space */
    .page-container,
    main {
      flex: 1 0 auto;
    }

    /* Footer stays at the bottom */
    .footer {
      flex-shrink: 0;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; color: #333; }
    
    .navbar { background: white; border-bottom: 1px solid #e0e0e0; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
    .nav-logo { font-size: 1.25rem; font-weight: 700; color: #1a1a1a; text-decoration: none; }
    .nav-logo span { color: #98FBCB; }
    .nav-links { list-style: none; display: flex; gap: 2rem; }
    .nav-links a { text-decoration: none; color: #666; font-weight: 500; }
    
    .container { max-width: 900px; margin: 0 auto; padding: 2rem; }
    
    .header { text-align: center; margin-bottom: 3rem; }
    .header h1 { font-size: 2.5rem; color: #1a1a1a; margin-bottom: 0.5rem; }
    .header-subtitle { color: #666; font-size: 1.1rem; }
    
    .info-section {
      background: white;
      border-left: 4px solid #98FBCB;
      border-radius: 4px;
      padding: 2rem;
      margin-bottom: 2rem;
    }
    
    .info-section h2 { color: #1a1a1a; margin-bottom: 1rem; }
    .info-section p { color: #666; line-height: 1.6; margin-bottom: 1rem; }
    .info-section ul { margin-left: 1.5rem; color: #666; }
    .info-section li { margin-bottom: 0.5rem; line-height: 1.6; }
    
    .benefits-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1.5rem;
      margin-top: 2rem;
    }
    
    .benefit-card {
      background: white;
      border-radius: 4px;
      padding: 1.5rem;
      border: 1px solid #e0e0e0;
      text-align: center;
    }
    
    .benefit-icon { font-size: 2.5rem; margin-bottom: 1rem; }
    .benefit-title { font-weight: 700; color: #1a1a1a; margin-bottom: 0.5rem; }
    .benefit-desc { color: #666; font-size: 0.9rem; }
    
    .highlight {
      background: #eefff9;
      border-left: 4px solid #98FBCB;
      padding: 1.5rem;
      border-radius: 4px;
      margin: 2rem 0;
    }
    
    .footer { background: #1a1a1a; color: white; padding: 2rem; margin-top: 3rem; text-align: center; }
  </style>
</head>
<body>
  <nav class="navbar">
    <a href="../../index.php" class="nav-logo">
      <div class="nav-logo-icon">TS</div>
      <span class="nav-logo-text">Talent<span>Scout</span> AI</span>
    </a>
    <ul class="nav-links">
      <li><a href="../../index.php">Home</a></li>
      <li><a href="../post-jobs/">Post Jobs</a></li>
      <li><a href="../employee-finder/">Find Talent</a></li>
      <li><a href="../applicant-tracking/">Hiring Pipeline</a></li>
      <li><a href="../chat-sms/">Messages</a></li>
    </ul>
    <div class="nav-actions">
      <?php if (isset($_SESSION['employer_id'])): ?>
        <span class="nav-user">Welcome, <?php echo htmlspecialchars($_SESSION['employer_name'] ?? 'Employer'); ?></span>
        <a href="../../logout.php" class="btn btn-outline">Logout</a>
      <?php else: ?>
        <a href="../../login.php" class="btn btn-outline">Login</a>
        <a href="../../signup.php" class="btn btn-primary">Get Started</a>
      <?php endif; ?>
    </div>
  </nav>

  <div class="container">
    <div class="header">
      <h1>🫥 Blind Hiring</h1>
      <p class="header-subtitle">Fair, merit-based screening that reduces bias</p>
    </div>

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
      <div class="candidates-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; margin-top: 1.5rem;">
        <?php if (empty($blind_candidates)): ?>
          <p style="grid-column: 1/-1; text-align: center; color: #999; padding: 2rem;">No candidates available yet.</p>
        <?php else: ?>
          <?php foreach ($blind_candidates as $candidate): ?>
            <div style="background: white; border: 1px solid #e0e0e0; border-radius: 8px; padding: 1.5rem;">
              <h3 style="margin: 0 0 0.5rem; font-size: 1.1rem; color: #1a1a1a;"><?php echo htmlspecialchars($candidate['candidate_name']); ?></h3>
              <p style="margin: 0.5rem 0; font-size: 0.9rem; color: #666; line-height: 1.6;">
                <?php echo !empty($candidate['summary']) ? htmlspecialchars(substr($candidate['summary'], 0, 120)) . '...' : 'No summary available'; ?>
              </p>
              <div style="margin-top: 1rem;">
                <strong style="font-size: 0.85rem; color: #555; display: block; margin-bottom: 0.5rem;">Key Skills:</strong>
                <?php if (!empty($candidate['skills'])): ?>
                  <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                    <?php foreach ($candidate['skills'] as $skill): ?>
                      <span style="background: #eefff9; color: #1e9e86; padding: 0.35rem 0.75rem; border-radius: 999px; font-size: 0.8rem; font-weight: 600;">
                        <?php echo htmlspecialchars($skill); ?>
                      </span>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <span style="color: #999; font-size: 0.9rem;">No skills listed</span>
                <?php endif; ?>
              </div>
              <form method="POST" style="display: inline; width: 100%;">
                <input type="hidden" name="action" value="contact_candidate">
                <input type="hidden" name="candidate_id" value="<?php echo $candidate['employee_id']; ?>">
                <button type="submit" onclick="return confirm('This will send an anonymous job offer message to this candidate. Continue?')" style="width: 100%; margin-top: 1rem; padding: 0.75rem; background: #1e9e86; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 0.9rem;">
                  💬 Contact Candidate
                </button>
              </form>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <footer class="footer">
    <p>&copy; 2026 TalentScout AI — PESO Nasugbu, Batangas. Hire fairly, hire better.</p>
  </footer>
</body>
</html>
