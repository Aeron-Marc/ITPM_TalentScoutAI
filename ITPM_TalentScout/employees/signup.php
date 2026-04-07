<?php
require_once __DIR__ . '/../database/db.php';

$errorMessage = '';
$successMessage = '';
$firstNameValue = '';
$lastNameValue = '';
$emailValue = '';
$barangayValue = '';
$showSuccessPopup = isset($_GET['signed_up']) && $_GET['signed_up'] === '1';

if ($showSuccessPopup) {
  $successMessage = 'Account created successfully.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $firstNameValue = trim($_POST['firstName'] ?? '');
  $lastNameValue = trim($_POST['lastName'] ?? '');
  $emailValue = trim($_POST['email'] ?? '');
  $barangayValue = trim($_POST['barangay'] ?? '');
  $password = $_POST['password'] ?? '';
  $confirmPassword = $_POST['confirmPassword'] ?? '';
  $acceptedTerms = isset($_POST['terms']);

  if ($firstNameValue === '' || $lastNameValue === '' || $emailValue === '' || $barangayValue === '' || $password === '' || $confirmPassword === '') {
    $errorMessage = 'Please complete all required fields.';
  } elseif (!filter_var($emailValue, FILTER_VALIDATE_EMAIL)) {
    $errorMessage = 'Please enter a valid email address.';
  } elseif ($password !== $confirmPassword) {
    $errorMessage = 'Password and confirm password do not match.';
  } elseif (strlen($password) < 6) {
    $errorMessage = 'Password must be at least 6 characters long.';
  } elseif (!$acceptedTerms) {
    $errorMessage = 'You must accept the terms to create an account.';
  } else {
    $conn = getConnection();

    $checkStmt = $conn->prepare('SELECT employee_id FROM employee WHERE email = ? LIMIT 1');
    if (!$checkStmt) {
      $errorMessage = 'Unable to process sign up right now. Please try again.';
    } else {
      $checkStmt->bind_param('s', $emailValue);
      $checkStmt->execute();
      $existing = $checkStmt->get_result();

      if ($existing && $existing->num_rows > 0) {
        $errorMessage = 'That email is already registered. Please log in instead.';
      } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $insertStmt = $conn->prepare('INSERT INTO employee (first_name, last_name, email, password, address, is_active) VALUES (?, ?, ?, ?, ?, 1)');

        if (!$insertStmt) {
          $errorMessage = 'Unable to create your account right now. Please try again.';
        } else {
          $insertStmt->bind_param('sssss', $firstNameValue, $lastNameValue, $emailValue, $hashedPassword, $barangayValue);

          if ($insertStmt->execute()) {
            closeConnection($conn);
            header('Location: ./signup.php?signed_up=1');
            exit;
          }

          $errorMessage = 'Unable to create your account right now. Please try again.';
          $insertStmt->close();
        }
      }

      $checkStmt->close();
    }

    closeConnection($conn);
  }
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Employee Sign Up | TalentScout AI</title>
  <link rel="stylesheet" href="../styles/global.css" />
  <link rel="stylesheet" href="../styles/page-layout.css" />
  <style>
    .auth-wrap {
      min-height: calc(100vh - var(--nav-height));
      padding: 3rem 1.25rem;
      background: linear-gradient(160deg,
          var(--bg-light) 0%,
          white 45%,
          var(--bg-lighter) 100%);
      display: grid;
      place-items: center;
    }

    .auth-grid {
      width: 100%;
      max-width: 1050px;
      display: grid;
      grid-template-columns: 1fr 1.1fr;
      background: white;
      border: 1.5px solid var(--border);
      border-radius: var(--radius);
      overflow: hidden;
      box-shadow: var(--shadow);
    }

    .auth-card {
      padding: 2.5rem;
      background: white;
    }

    .auth-title {
      font-size: 1.6rem;
      margin-bottom: 0.35rem;
      color: var(--text-dark);
    }

    .auth-subtitle {
      color: var(--text-light);
      margin-bottom: 1.5rem;
      font-size: 0.9rem;
    }

    .auth-form {
      display: grid;
      gap: 1rem;
    }

    .two-col {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.8rem;
    }

    .field label {
      display: block;
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--text-mid);
      margin-bottom: 0.35rem;
    }

    .field input,
    .field select {
      width: 100%;
      border: 1.5px solid var(--border);
      border-radius: var(--radius-sm);
      padding: 0.75rem 0.85rem;
      font-size: 0.92rem;
      outline: none;
      transition:
        border-color 0.2s,
        box-shadow 0.2s;
      background: white;
    }

    .field input:focus,
    .field select:focus {
      border-color: var(--primary-dark);
      box-shadow: 0 0 0 3px rgba(30, 158, 134, 0.12);
    }

    .terms {
      display: inline-flex;
      align-items: flex-start;
      gap: 0.5rem;
      color: var(--text-light);
      font-size: 0.83rem;
    }

    .terms a {
      color: var(--primary-dark);
      font-weight: 700;
    }

    .auth-submit {
      width: 100%;
      justify-content: center;
      padding: 0.8rem 1.1rem;
      font-size: 0.9rem;
      margin-top: 0.2rem;
    }

    .auth-alt {
      margin-top: 1rem;
      font-size: 0.85rem;
      color: var(--text-light);
      text-align: center;
    }

    .auth-alt a {
      color: var(--primary-dark);
      font-weight: 700;
    }

    .auth-message {
      margin-top: 0.9rem;
      padding: 0.75rem 0.9rem;
      border-radius: var(--radius-sm);
      font-size: 0.86rem;
    }

    .auth-message.error {
      background: #fef2f2;
      color: #b91c1c;
      border: 1px solid #fecaca;
    }

    .auth-message.success {
      background: #ecfdf3;
      color: #166534;
      border: 1px solid #bbf7d0;
    }

    .auth-info {
      padding: 2.5rem;
      background: linear-gradient(145deg, #13917a 0%, #10614f 100%);
      color: white;
    }

    .auth-kicker {
      display: inline-block;
      font-size: 0.78rem;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      font-weight: 700;
      padding: 0.35rem 0.65rem;
      background: rgba(255, 255, 255, 0.15);
      border-radius: 999px;
      margin-bottom: 1rem;
    }

    .auth-info h2 {
      font-size: 2rem;
      line-height: 1.2;
      margin-bottom: 0.85rem;
    }

    .auth-info p {
      color: rgba(255, 255, 255, 0.9);
      margin-bottom: 1.25rem;
      font-size: 0.95rem;
      line-height: 1.7;
    }

    .auth-points {
      list-style: none;
      display: grid;
      gap: 0.7rem;
      font-size: 0.9rem;
    }

    .auth-points li {
      display: flex;
      align-items: center;
      gap: 0.55rem;
    }

    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, 0.45);
      display: none;
      align-items: center;
      justify-content: center;
      padding: 1rem;
      z-index: 9999;
    }

    .modal-overlay.show {
      display: flex;
    }

    .modal-card {
      width: min(420px, 100%);
      background: #ffffff;
      border-radius: 14px;
      border: 1px solid #e5e7eb;
      box-shadow: 0 20px 35px rgba(15, 23, 42, 0.2);
      padding: 1.25rem 1.25rem 1rem;
      text-align: center;
    }

    .modal-icon {
      width: 56px;
      height: 56px;
      margin: 0 auto 0.75rem;
      border-radius: 999px;
      display: grid;
      place-items: center;
      background: #ecfdf3;
      color: #15803d;
      font-size: 1.5rem;
      font-weight: 800;
    }

    .modal-title {
      font-size: 1.2rem;
      color: var(--text-dark);
      margin-bottom: 0.4rem;
    }

    .modal-text {
      font-size: 0.92rem;
      color: var(--text-light);
      margin-bottom: 1rem;
    }

    .modal-actions {
      display: flex;
      justify-content: center;
    }

    .modal-btn {
      min-width: 130px;
    }

    @media (max-width: 900px) {
      .auth-grid {
        grid-template-columns: 1fr;
      }

      .two-col {
        grid-template-columns: 1fr;
      }

      .auth-card,
      .auth-info {
        padding: 2rem 1.25rem;
      }
    }
  </style>
</head>

<body>
  <nav class="navbar">
    <a href="./index.php" class="nav-logo">
      <div class="nav-logo-icon">TS</div>
      <span class="nav-logo-text">Talent<span>Scout</span> AI</span>
    </a>
    <ul class="nav-links">
      <li><a href="./index.php">Home</a></li>
      <li><a href="./modules/job-postings/index.php">Browse Jobs</a></li>
      <li><a href="./modules/ai-matching/index.php">AI Matching</a></li>
      <li><a href="./modules/resume-builder/index.php">Resume Builder</a></li>
      <li><a href="./modules/skill-gap-analysis/index.php">Skills</a></li>
      <li><a href="./modules/applicant-tracking/index.php">Applications</a></li>
    </ul>
    <div class="nav-actions">
      <a href="./login.php" class="btn btn-outline">Login</a>
      <a href="./signup.php" class="btn btn-primary">Get Started</a>
    </div>
  </nav>

  <main class="auth-wrap">
    <section class="auth-grid">
      <div class="auth-card">
        <h1 class="auth-title">Create Employee Account</h1>
        <p class="auth-subtitle">
          Start your job journey with TalentScout AI.
        </p>

        <form class="auth-form" action="" method="post">
          <div class="two-col">
            <div class="field">
              <label for="firstName">First Name</label>
              <input id="firstName" name="firstName" type="text" value="<?php echo htmlspecialchars($firstNameValue, ENT_QUOTES, 'UTF-8'); ?>" required />
            </div>
            <div class="field">
              <label for="lastName">Last Name</label>
              <input id="lastName" name="lastName" type="text" value="<?php echo htmlspecialchars($lastNameValue, ENT_QUOTES, 'UTF-8'); ?>" required />
            </div>
          </div>

          <div class="field">
            <label for="email">Email Address</label>
            <input
              id="email"
              name="email"
              type="email"
              placeholder="you@example.com"
              value="<?php echo htmlspecialchars($emailValue, ENT_QUOTES, 'UTF-8'); ?>"
              required />
          </div>

          <div class="field">
            <label for="barangay">Barangay</label>
            <select id="barangay" name="barangay" required>
              <option value="">Select your barangay</option>
              <option <?php echo $barangayValue === 'Kaylaway' ? 'selected' : ''; ?>>Kaylaway</option>
              <option <?php echo $barangayValue === 'Wawa' ? 'selected' : ''; ?>>Wawa</option>
              <option <?php echo $barangayValue === 'Bucana' ? 'selected' : ''; ?>>Bucana</option>
              <option <?php echo $barangayValue === 'Poblacion' ? 'selected' : ''; ?>>Poblacion</option>
              <option <?php echo $barangayValue === 'Other' ? 'selected' : ''; ?>>Other</option>
            </select>
          </div>

          <div class="two-col">
            <div class="field">
              <label for="password">Password</label>
              <input id="password" name="password" type="password" required />
            </div>
            <div class="field">
              <label for="confirmPassword">Confirm Password</label>
              <input
                id="confirmPassword"
                name="confirmPassword"
                type="password"
                required />
            </div>
          </div>

          <label class="terms" for="terms">
            <input id="terms" name="terms" type="checkbox" required />
            <span>I agree to the <a href="#">Terms of Service</a> and
              <a href="#">Privacy Policy</a>.</span>
          </label>

          <button type="submit" class="btn btn-primary auth-submit">
            Create Account
          </button>
        </form>

        <?php if ($errorMessage !== ''): ?>
          <div class="auth-message error" aria-live="polite">
            <?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
          </div>
        <?php endif; ?>

        <?php if ($successMessage !== ''): ?>
          <div class="auth-message success" aria-live="polite">
            <?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?>
          </div>
        <?php endif; ?>

        <p class="auth-alt">
          Already have an account? <a href="./login.php">Sign in</a>
        </p>
      </div>

      <aside class="auth-info">
        <span class="auth-kicker">Get Started Fast</span>
        <h2>Build your profile and get matched</h2>
        <p>
          Your account gives you instant access to local job opportunities, AI
          matching, and application tracking in one place.
        </p>
        <ul class="auth-points">
          <li>
            <span>✓</span><span>Create your profile in under 5 minutes</span>
          </li>
          <li>
            <span>✓</span><span>Receive skill-based recommendations</span>
          </li>
          <li>
            <span>✓</span><span>Apply and monitor status in real time</span>
          </li>
        </ul>
      </aside>
    </section>
  </main>
  <?php if ($showSuccessPopup): ?>
    <div id="successModal" class="modal-overlay show" role="dialog" aria-modal="true" aria-labelledby="successModalTitle">
      <div class="modal-card">
        <div class="modal-icon">✓</div>
        <h2 id="successModalTitle" class="modal-title">Signup Successful</h2>
        <p class="modal-text">Your account was created successfully.</p>
        <div class="modal-actions">
          <button id="modalContinueBtn" class="btn btn-primary modal-btn" type="button">Continue</button>
        </div>
      </div>
    </div>
    <script>
      (function() {
        var continueBtn = document.getElementById('modalContinueBtn');
        if (continueBtn) {
          continueBtn.addEventListener('click', function() {
            window.location.href = './login.php?registered=1';
          });
          continueBtn.focus();
        }
      })();
    </script>
  <?php endif; ?>
</body>

</html>