<?php
require_once __DIR__ . '/../database/db.php';

$errorMessage = '';
$successMessage = '';
$companyNameValue = '';
$emailValue = '';
$addressValue = '';
$showSuccessPopup = isset($_GET['signed_up']) && $_GET['signed_up'] === '1';

if ($showSuccessPopup) {
  $successMessage = 'Company account created successfully.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $companyNameValue = trim($_POST['companyName'] ?? '');
  $emailValue = trim($_POST['email'] ?? '');
  $addressValue = trim($_POST['address'] ?? '');
  $password = $_POST['password'] ?? '';
  $confirmPassword = $_POST['confirmPassword'] ?? '';
  $acceptedTerms = isset($_POST['terms']);

  if ($companyNameValue === '' || $emailValue === '' || $addressValue === '' || $password === '' || $confirmPassword === '') {
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

    $checkStmt = $conn->prepare('SELECT employer_id FROM employer WHERE email = ? LIMIT 1');
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
        $status = 'active';
        $insertStmt = $conn->prepare('INSERT INTO employer (company_name, email, password, address, status) VALUES (?, ?, ?, ?, ?)');

        if (!$insertStmt) {
          $errorMessage = 'Unable to create your account right now. Please try again.';
        } else {
          $insertStmt->bind_param('sssss', $companyNameValue, $emailValue, $hashedPassword, $addressValue, $status);

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
  <title>Employer Sign Up | TalentScout AI</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900;1,700&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --sage:        #3d6b50;
      --sage-light:  #5a8a68;
      --sage-pale:   #d4e6d6;
      --sage-deep:   #1e3a2e;
      --sand:        #f5f0e8;
      --sand-dark:   #ece5d5;
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

    /* ────────── NAVBAR ────────── */
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

    .nav-right {
      display: flex; align-items: center; gap: 0.7rem;
    }

    .btn-nav-ghost {
      padding: 0.4rem 1rem;
      border-radius: var(--radius-pill);
      border: 1px solid var(--warm-light);
      color: var(--warm-mid);
      font-family: 'DM Sans', sans-serif;
      font-size: 0.83rem;
      font-weight: 500;
      background: transparent;
      cursor: pointer;
      transition: border-color 0.2s, background 0.2s;
    }

    .btn-nav-ghost:hover { background: var(--sand); border-color: var(--warm-mid); }

    /* ────────── AUTH PAGE ────────── */
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
      top: -100px; right: -100px;
      width: 400px; height: 400px;
      border-radius: 50%;
      background: radial-gradient(circle, var(--sage-pale) 0%, transparent 70%);
      pointer-events: none;
    }

    .auth-wrap::after {
      content: '';
      position: absolute;
      bottom: -80px; left: -80px;
      width: 300px; height: 300px;
      border-radius: 50%;
      background: radial-gradient(circle, var(--gold-pale) 0%, transparent 70%);
      pointer-events: none;
    }

    .auth-grid {
      width: 100%;
      max-width: 980px;
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
      top: -40px; right: -40px;
      width: 200px; height: 200px;
      border-radius: 50%;
      background: rgba(255,255,255,0.06);
      pointer-events: none;
    }

    .auth-info::after {
      content: '';
      position: absolute;
      bottom: -60px; left: -40px;
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

    .auth-info h1 {
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

    .auth-card {
      padding: 2.5rem;
      background: #fff;
      display: flex;
      flex-direction: column;
      justify-content: center;
      max-height: 100vh;
      overflow-y: auto;
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

    .field label {
      display: block;
      font-size: 0.8rem;
      font-weight: 600;
      color: var(--warm-mid);
      margin-bottom: 0.4rem;
      letter-spacing: 0.01em;
    }

    .field input {
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

    .field input:focus {
      border-color: var(--sage-light);
      box-shadow: 0 0 0 3px var(--sage-pale);
      background: #fff;
    }

    .field input::placeholder { color: var(--warm-light); }

    .terms {
      display: flex;
      align-items: flex-start;
      gap: 0.4rem;
      font-size: 0.82rem;
      color: var(--warm-light);
      line-height: 1.5;
    }

    .terms input[type="checkbox"] {
      width: 16px; height: 16px;
      margin-top: 2px;
      accent-color: var(--sage-deep);
      cursor: pointer;
      flex-shrink: 0;
    }

    .terms a {
      color: var(--sage-deep);
      font-weight: 600;
      transition: color 0.15s;
    }

    .terms a:hover { color: var(--sage); }

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
      box-shadow: 0 4px 14px rgba(30, 62, 46, 0.28);
      margin-top: 0.25rem;
      display: flex;
      align-items: center;
    }

    .btn-auth:hover {
      background: var(--sage);
      transform: translateY(-1px);
      box-shadow: 0 6px 20px rgba(30, 62, 46, 0.35);
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

    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(26, 46, 34, 0.5);
      display: none;
      align-items: center;
      justify-content: center;
      padding: 1rem;
      z-index: 9999;
      backdrop-filter: blur(2px);
      opacity: 0;
      transition: opacity 0.3s ease;
    }

    .modal-overlay.show {
      display: flex;
      opacity: 1;
    }

    .modal-card {
      width: min(420px, 100%);
      background: #ffffff;
      border-radius: var(--radius-lg);
      box-shadow: 0 20px 35px rgba(42, 42, 34, 0.2);
      padding: 2rem 1.5rem;
      text-align: center;
      transform: scale(0.9);
      transition: transform 0.3s var(--ease-out);
    }

    .modal-overlay.show .modal-card {
      transform: scale(1);
    }

    .modal-icon {
      width: 56px;
      height: 56px;
      margin: 0 auto 1rem;
      border-radius: 50%;
      display: grid;
      place-items: center;
      background: var(--sage-pale);
      color: var(--sage-deep);
      font-size: 1.8rem;
      font-weight: 800;
    }

    .modal-title {
      font-family: 'Playfair Display', serif;
      font-size: 1.3rem;
      font-weight: 900;
      color: var(--charcoal);
      margin-bottom: 0.5rem;
      letter-spacing: -0.02em;
    }

    .modal-text {
      font-size: 0.92rem;
      color: var(--warm-light);
      margin-bottom: 1.5rem;
      line-height: 1.6;
    }

    .modal-actions {
      display: flex;
      justify-content: center;
    }

    .modal-btn {
      padding: 0.8rem 2rem;
      border-radius: var(--radius-lg);
      background: var(--sage-deep);
      color: #fff;
      font-family: 'DM Sans', sans-serif;
      font-size: 0.9rem;
      font-weight: 700;
      border: none;
      cursor: pointer;
      transition: background 0.2s, transform 0.15s;
    }

    .modal-btn:hover {
      background: var(--sage);
      transform: translateY(-1px);
    }

    @media (max-width: 900px) {
      .navbar { padding: 0 1.5rem; }
      
      .auth-grid {
        grid-template-columns: 1fr;
        max-width: 520px;
      }

      .auth-info { padding: 2rem 1.75rem; }
      .auth-card { padding: 2rem 1.75rem; max-height: none; }

      .auth-wrap {
        padding: calc(64px + 2rem) 1rem 2rem;
      }
    }

    @media (max-width: 600px) {
      .navbar { padding: 0 1.2rem; }
      .auth-info h1 { font-size: 1.5rem; }
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
    <div class="nav-right">
      <a href="./login.php" class="btn-nav-ghost">Login</a>
    </div>
  </nav>

  <main class="auth-wrap">
    <section class="auth-grid">
      <aside class="auth-info">
        <span class="auth-kicker">🚀 Get Started</span>
        <h1>Find your perfect candidate</h1>
        <p>
          Post jobs, reach pre-vetted local talent, screen candidates with blind hiring, and manage your entire hiring pipeline with TalentScout AI.
        </p>
        <ul class="auth-points">
          <li>
            <span class="check-icon">✓</span>
            <span>Post jobs and access local talent pool</span>
          </li>
          <li>
            <span class="check-icon">✓</span>
            <span>Use blind hiring for fair assessment</span>
          </li>
          <li>
            <span class="check-icon">✓</span>
            <span>Manage hiring pipeline from start to finish</span>
          </li>
        </ul>
      </aside>

      <section class="auth-card">
        <div class="auth-card-logo">
          <div class="auth-card-logo-icon">TS</div>
          <span>TalentScout AI</span>
        </div>
        <h2 class="auth-title">Create Account</h2>
        <p class="auth-subtitle">Start recruiting with TalentScout AI.</p>
        
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
        
        <form class="auth-form" action="" method="post">
          <div class="field">
            <label for="companyName">Company Name</label>
            <input 
              id="companyName" 
              name="companyName" 
              type="text" 
              placeholder="Your Company Name"
              value="<?php echo htmlspecialchars($companyNameValue, ENT_QUOTES, 'UTF-8'); ?>" 
              required />
          </div>

          <div class="field">
            <label for="email">Email Address</label>
            <input
              id="email"
              name="email"
              type="email"
              placeholder="company@example.com"
              value="<?php echo htmlspecialchars($emailValue, ENT_QUOTES, 'UTF-8'); ?>"
              required />
          </div>

          <div class="field">
            <label for="address">Company Address</label>
            <input
              id="address"
              name="address"
              type="text"
              placeholder="Street address, city, province"
              value="<?php echo htmlspecialchars($addressValue, ENT_QUOTES, 'UTF-8'); ?>"
              required />
          </div>

          <div class="field">
            <label for="password">Password</label>
            <input 
              id="password" 
              name="password" 
              type="password" 
              placeholder="Minimum 6 characters"
              required />
          </div>

          <div class="field">
            <label for="confirmPassword">Confirm Password</label>
            <input
              id="confirmPassword"
              name="confirmPassword"
              type="password"
              placeholder="Re-enter your password"
              required />
          </div>

          <label class="terms" for="terms">
            <input id="terms" name="terms" type="checkbox" required />
            <span>I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.</span>
          </label>

          <button type="submit" class="btn-auth">Create Account</button>
        </form>

        <p class="auth-alt">
          Already have an account? <a href="./login.php">Sign in</a>
        </p>
      </section>
    </section>
  </main>

  <?php if ($showSuccessPopup): ?>
    <div id="successModal" class="modal-overlay show" role="dialog" aria-modal="true" aria-labelledby="successModalTitle">
      <div class="modal-card">
        <div class="modal-icon">✓</div>
        <h2 id="successModalTitle" class="modal-title">Signup Successful</h2>
        <p class="modal-text">Your employer account has been created successfully. You can now log in.</p>
        <div class="modal-actions">
          <button id="modalContinueBtn" class="modal-btn" type="button">Continue to Login</button>
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
</body>

</html>
