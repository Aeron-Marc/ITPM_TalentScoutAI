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
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)'/%3E%3C/svg%3E");
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
        display: flex; align-items: center; gap: 0.6rem;
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
      }

      .nav-links a:hover, .nav-links a.active {
        background: var(--sage-pale);
        color: var(--sage-deep);
      }

      .nav-right {
        display: flex; align-items: center; gap: 0.7rem;
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

      /* AUTH PAGE */
      .auth-wrap {
        min-height: 100vh;
        padding-top: 64px;
        display: grid;
        place-items: center;
        background: linear-gradient(180deg, var(--sand) 0%, var(--cream) 100%);
        position: relative;
        overflow: hidden;
      }

      .auth-wrap::before {
        content: '';
        position: absolute;
        top: -100px; left: -100px;
        width: 400px; height: 400px;
        border-radius: 50%;
        background: radial-gradient(circle, var(--gold-pale) 0%, transparent 70%);
        pointer-events: none;
      }

      .auth-wrap::after {
        content: '';
        position: absolute;
        bottom: -80px; right: -80px;
        width: 300px; height: 300px;
        border-radius: 50%;
        background: radial-gradient(circle, var(--sage-pale) 0%, transparent 70%);
        pointer-events: none;
      }

      .auth-grid {
        width: 100%;
        max-width: 1020px;
        display: grid;
        grid-template-columns: 1.05fr 1fr;
        border-radius: var(--radius-xl);
        overflow: hidden;
        box-shadow: 0 12px 48px rgba(42,42,34,0.1), 0 2px 8px rgba(42,42,34,0.06);
        position: relative;
        z-index: 1;
        animation: fadeUp 0.7s var(--ease-out) both;
      }

      @keyframes fadeUp {
        from { opacity: 0; transform: translateY(24px); }
        to   { opacity: 1; transform: translateY(0); }
      }

      .auth-card {
        padding: 2.5rem;
        background: #fff;
        display: flex;
        flex-direction: column;
        justify-content: center;
      }

      .auth-card-logo {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-family: 'Playfair Display', serif;
        font-weight: 700;
        font-size: 1rem;
        color: var(--charcoal);
        margin-bottom: 1.8rem;
      }

      .auth-card-logo-icon {
        width: 30px; height: 30px;
        background: var(--sage-pale);
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.65rem;
        font-weight: 700;
        color: var(--sage-deep);
      }

      .auth-title {
        font-family: 'Playfair Display', serif;
        font-size: 1.5rem;
        font-weight: 900;
        color: var(--charcoal);
        margin-bottom: 0.35rem;
        letter-spacing: -0.02em;
      }

      .auth-subtitle {
        color: var(--warm-light);
        margin-bottom: 1.75rem;
        font-size: 0.88rem;
        line-height: 1.5;
      }

      .auth-form {
        display: flex;
        flex-direction: column;
        gap: 1rem;
      }

      .form-row-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.85rem;
      }

      .field label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--warm-mid);
        margin-bottom: 0.4rem;
        letter-spacing: 0.01em;
      }

      .field input, .field select {
        width: 100%;
        border: 1.5px solid var(--sand-dark);
        border-radius: var(--radius-lg);
        padding: 0.7rem 0.9rem;
        font-size: 0.88rem;
        font-family: 'DM Sans', sans-serif;
        color: var(--charcoal);
        outline: none;
        transition: border-color 0.2s, box-shadow 0.2s;
        background: var(--cream);
      }

      .field input:focus, .field select:focus {
        border-color: var(--sage-light);
        box-shadow: 0 0 0 3px var(--sage-pale);
        background: #fff;
      }

      .field input::placeholder { color: var(--warm-light); }

      .select-wrap {
        position: relative;
      }

      .select-wrap::after {
        content: '';
        position: absolute;
        right: 0.9rem;
        top: 50%;
        transform: translateY(-50%);
        width: 12px;
        height: 8px;
        background-image: url("data:image/svg+xml,%3Csvg width='12' height='8' viewBox='0 0 12 8' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1.5L6 6.5L11 1.5' stroke='%238a8070' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: center;
        pointer-events: none;
      }

      .field select {
        appearance: none;
        padding-right: 2.5rem;
        cursor: pointer;
      }

      .terms {
        display: inline-flex;
        align-items: flex-start;
        gap: 0.5rem;
        color: var(--warm-light);
        font-size: 0.82rem;
        cursor: pointer;
        line-height: 1.4;
      }

      .terms input[type="checkbox"] {
        width: 16px; height: 16px;
        accent-color: var(--sage-deep);
        cursor: pointer;
        margin-top: 2px;
        flex-shrink: 0;
      }

      .terms a {
        color: var(--sage);
        font-weight: 600;
        transition: color 0.15s;
      }

      .terms a:hover { color: var(--sage-deep); }

      .btn-auth {
        width: 100%;
        justify-content: center;
        padding: 0.8rem 1.2rem;
        font-size: 0.9rem;
        border-radius: var(--radius-lg);
        background: var(--sage-deep);
        color: #fff;
        font-family: 'DM Sans', sans-serif;
        font-weight: 700;
        border: none;
        cursor: pointer;
        transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
        box-shadow: 0 4px 14px rgba(74,107,80,0.28);
        margin-top: 0.25rem;
      }

      .btn-auth:hover {
        background: var(--sage);
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(74,107,80,0.35);
      }

      .auth-alt {
        margin-top: 1.25rem;
        font-size: 0.85rem;
        color: var(--warm-light);
        text-align: center;
      }

      .auth-alt a {
        color: var(--sage-deep);
        font-weight: 700;
        transition: color 0.15s;
      }

      .auth-alt a:hover { color: var(--sage); }

      .auth-message {
        margin-top: 0.9rem;
        padding: 0.75rem 0.9rem;
        border-radius: var(--radius-md);
        font-size: 0.84rem;
        font-weight: 500;
      }

      .auth-message.error {
        background: #fef2f2;
        color: #b91c1c;
        border: 1px solid #fecaca;
      }

      .auth-message.success {
        background: var(--sage-pale);
        color: var(--sage-deep);
        border: 1px solid var(--sage-light);
      }

      .auth-info {
        padding: 3rem 2.5rem;
        background: linear-gradient(135deg, var(--sage-deep), var(--sage));
        color: #fff;
        display: flex;
        flex-direction: column;
        justify-content: center;
        position: relative;
        overflow: hidden;
      }

      .auth-info::before {
        content: '';
        position: absolute;
        top: -40px; left: -40px;
        width: 200px; height: 200px;
        border-radius: 50%;
        background: rgba(255,255,255,0.06);
        pointer-events: none;
      }

      .auth-info::after {
        content: '';
        position: absolute;
        bottom: -60px; right: -40px;
        width: 180px; height: 180px;
        border-radius: 50%;
        background: rgba(200,169,110,0.1);
        pointer-events: none;
      }

      .auth-kicker {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        background: rgba(255,255,255,0.15);
        border-radius: var(--radius-pill);
        padding: 0.3rem 0.75rem;
        margin-bottom: 1.25rem;
        width: fit-content;
      }

      .auth-info h2 {
        font-family: 'Playfair Display', serif;
        font-size: 1.9rem;
        font-weight: 900;
        line-height: 1.2;
        margin-bottom: 0.75rem;
        letter-spacing: -0.02em;
      }

      .auth-info p {
        font-size: 0.9rem;
        line-height: 1.7;
        opacity: 0.85;
        margin-bottom: 1.5rem;
      }

      .auth-points {
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: 0.7rem;
        font-size: 0.88rem;
        position: relative;
        z-index: 1;
      }

      .auth-points li {
        display: flex;
        align-items: center;
        gap: 0.6rem;
      }

      .auth-points li .check-icon {
        width: 22px; height: 22px;
        background: rgba(255,255,255,0.18);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
        flex-shrink: 0;
      }

      /* MODAL */
      .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(42,42,34,0.5);
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        z-index: 10000;
      }

      .modal-overlay.show {
        display: flex;
      }

      .modal-card {
        width: min(420px, 100%);
        background: #fff;
        border-radius: var(--radius-xl);
        border: 1px solid var(--sand-dark);
        box-shadow: 0 20px 35px rgba(42,42,34,0.2);
        padding: 2.5rem 2rem;
        text-align: center;
        animation: popIn 0.4s var(--ease-out);
      }

      @keyframes popIn {
        from { transform: scale(0.9); opacity: 0; }
        to   { transform: scale(1); opacity: 1; }
      }

      .modal-icon {
        width: 64px;
        height: 64px;
        margin: 0 auto 1rem;
        border-radius: 50%;
        display: grid;
        place-items: center;
        background: var(--sage-pale);
        color: var(--sage-deep);
        font-size: 1.5rem;
        font-weight: 800;
      }

      .modal-title {
        font-family: 'Playfair Display', serif;
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--charcoal);
        margin-bottom: 0.5rem;
      }

      .modal-text {
        font-size: 0.9rem;
        color: var(--warm-mid);
        margin-bottom: 1.5rem;
        line-height: 1.5;
      }

      .modal-btn {
        padding: 0.7rem 2rem;
        border-radius: var(--radius-pill);
        background: var(--sage-deep);
        color: #fff;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.88rem;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: background 0.2s, transform 0.15s;
      }

      .modal-btn:hover {
        background: var(--sage);
        transform: translateY(-1px);
      }

      @media (max-width: 900px) {
        .auth-grid {
          grid-template-columns: 1fr;
          max-width: 520px;
        }

        .auth-card, .auth-info {
          padding: 2rem 1.75rem;
        }

        .auth-wrap {
          padding: calc(64px + 2rem) 1rem 2rem;
        }

        .form-row-2 {
          grid-template-columns: 1fr;
        }
      }

      @media (max-width: 600px) {
        .navbar { padding: 0 1.2rem; }
        .nav-links { display: none; }
        .auth-info h2 { font-size: 1.5rem; }
        .auth-card { padding: 1.5rem; }
      }
    </style>
  </head>

  <body>
    <nav class="navbar">
      <a href="./index.php" class="nav-logo">
        <div class="nav-logo-mark">TS</div>
        <span>Talent<em>Scout</em> AI</span>
      </a>
      <ul class="nav-links">
        <li><a href="./index.php">Home</a></li>
        <li><a href="./modules/job-postings/index.php">Browse Jobs</a></li>
        <li><a href="./modules/ai-matching/index.php">AI Matching</a></li>
        <li><a href="./modules/resume-builder/index.php">Resume Builder</a></li>
        <li><a href="./modules/skill-gap-analysis/index.php">Skills</a></li>
        <li><a href="./modules/applicant-tracking/index.php">Applications</a></li>
        <li><a href="./modules/messages/index.php">Messages</a></li>
      </ul>
      <div class="nav-right">
        <a href="./login.php" class="btn-nav-ghost">Login</a>
        <a href="./signup.php" class="btn-nav-solid">Get Started →</a>
      </div>
    </nav>

    <main class="auth-wrap">
      <section class="auth-grid">
        <div class="auth-card">
          <div class="auth-card-logo">
            <div class="auth-card-logo-icon">TS</div>
            <span>Talent<em style="font-style:italic;color:var(--sage)">Scout</em> AI</span>
          </div>

          <h1 class="auth-title">Create Employee Account</h1>
          <p class="auth-subtitle">
            Start your job journey with TalentScout AI.
          </p>

          <form class="auth-form" action="" method="post">
            <div class="form-row-2">
              <div class="field">
                <label for="firstName">First Name</label>
                <input id="firstName" name="firstName" type="text" placeholder="Juan" value="<?php echo htmlspecialchars($firstNameValue, ENT_QUOTES, 'UTF-8'); ?>" required />
              </div>
              <div class="field">
                <label for="lastName">Last Name</label>
                <input id="lastName" name="lastName" type="text" placeholder="Dela Cruz" value="<?php echo htmlspecialchars($lastNameValue, ENT_QUOTES, 'UTF-8'); ?>" required />
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
              <div class="select-wrap">
                <select id="barangay" name="barangay" required>
                  <option value="">Select your barangay</option>
                  <option <?php echo $barangayValue === 'Kaylaway' ? 'selected' : ''; ?>>Kaylaway</option>
                  <option <?php echo $barangayValue === 'Wawa' ? 'selected' : ''; ?>>Wawa</option>
                  <option <?php echo $barangayValue === 'Bucana' ? 'selected' : ''; ?>>Bucana</option>
                  <option <?php echo $barangayValue === 'Poblacion' ? 'selected' : ''; ?>>Poblacion</option>
                  <option <?php echo $barangayValue === 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
              </div>
            </div>

            <div class="form-row-2">
              <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" placeholder="Min 6 characters" required />
              </div>
              <div class="field">
                <label for="confirmPassword">Confirm Password</label>
                <input
                  id="confirmPassword"
                  name="confirmPassword"
                  type="password"
                  placeholder="Re-enter password"
                  required />
              </div>
            </div>

            <label class="terms" for="terms">
              <input id="terms" name="terms" type="checkbox" required />
              <span>I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.</span>
            </label>

            <button type="submit" class="btn-auth">
              Create Account →
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
          <span class="auth-kicker">🚀 Get Started Fast</span>
          <h2>Build your profile and get matched</h2>
          <p>
            Your account gives you instant access to local job opportunities, AI matching, and application tracking in one place.
          </p>
          <ul class="auth-points">
            <li>
              <span class="check-icon">✓</span>
              <span>Create your profile in under 5 minutes</span>
            </li>
            <li>
              <span class="check-icon">✓</span>
              <span>Receive skill-based recommendations</span>
            </li>
            <li>
              <span class="check-icon">✓</span>
              <span>Apply and monitor status in real time</span>
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
          <p class="modal-text">Your account was created successfully. You can now log in to access your personalized dashboard.</p>
          <button id="modalContinueBtn" class="modal-btn" type="button">Go to Login</button>
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

    <script src="./employee-auth.js"></script>
  </body>
</html>
