<?php
session_start();
require_once __DIR__ . '/../database/db.php';

$errorMessage = '';
$successMessage = '';
$emailValue = '';

if (isset($_GET['registered']) && $_GET['registered'] === '1') {
  $successMessage = 'Company account created successfully. You can now log in.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $emailValue = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($emailValue === '' || $password === '') {
    $errorMessage = 'Please enter both email and password.';
  } else {
    $conn = getConnection();
    $stmt = $conn->prepare('SELECT employer_id, company_name, email, password, status FROM employer WHERE email = ? LIMIT 1');

    if (!$stmt) {
      $errorMessage = 'Unable to process login right now. Please try again.';
    } else {
      $stmt->bind_param('s', $emailValue);
      $stmt->execute();
      $result = $stmt->get_result();
      $employer = $result ? $result->fetch_assoc() : null;

      if (!$employer) {
        $errorMessage = 'Invalid email or password.';
      } else {
        $storedPassword = (string)($employer['password'] ?? '');
        $isValidPassword = password_verify($password, $storedPassword) || hash_equals($storedPassword, $password);
        $isActive = strtolower($employer['status'] ?? 'active') === 'active';

        if (!$isValidPassword) {
          $errorMessage = 'Invalid email or password.';
        } elseif (!$isActive) {
          $errorMessage = 'Your account is inactive. Please contact support.';
        } else {
          $_SESSION['employer_id'] = (int)$employer['employer_id'];
          $_SESSION['employer_name'] = $employer['company_name'] ?? '';
          $_SESSION['employer_email'] = $employer['email'] ?? '';

          // Prepare data to pass to client after redirect
          $loginData = [
            'isLoggedIn' => true,
            'employerName' => $_SESSION['employer_name'],
            'employerId' => $_SESSION['employer_id']
          ];
          $loginDataJson = json_encode($loginData);
          
          $successMessage = 'Login successful. Redirecting...';
          ?>
          <!DOCTYPE html>
          <html>
          <head>
            <title>Redirecting...</title>
          </head>
          <body>
            <script>
              try {
                localStorage.setItem('employer_auth_state', <?php echo $loginDataJson; ?>);
              } catch (e) {
                // Ignore if localStorage is unavailable
              }
            </script>
          </body>
          </html>
          <?php
          header('Location: ./index.php');
          exit;
        }
      }

      $stmt->close();
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
  <title>Employer Login | TalentScout AI</title>
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
      grid-template-columns: 1fr 1.05fr;
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
      gap: 1.1rem;
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

    .form-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 0.8rem;
      flex-wrap: wrap;
      font-size: 0.82rem;
      color: var(--warm-light);
    }

    .check {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      cursor: pointer;
    }

    .check input[type="checkbox"] {
      width: 16px; height: 16px;
      accent-color: var(--sage-deep);
      cursor: pointer;
    }

    .form-row a {
      color: var(--sage);
      font-weight: 600;
      transition: color 0.15s;
    }

    .form-row a:hover { color: var(--sage-deep); }

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

    @media (max-width: 900px) {
      .navbar { padding: 0 1.5rem; }
      
      .auth-grid {
        grid-template-columns: 1fr;
        max-width: 520px;
      }

      .auth-info { padding: 2rem 1.75rem; }
      .auth-card { padding: 2rem 1.75rem; }

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
      <a href="./signup.php" class="btn-nav-ghost">Sign Up</a>
    </div>
  </nav>

  <main class="auth-wrap">
    <section class="auth-grid">
      <aside class="auth-info">
        <span class="auth-kicker">🚀 Welcome Back</span>
        <h1>Sign in to your hiring dashboard</h1>
        <p>
          Access your job postings, screen candidates, manage applications, and find top talent with AI-powered matching on TalentScout AI.
        </p>
        <ul class="auth-points">
          <li>
            <span class="check-icon">✓</span>
            <span>Post jobs and reach qualified candidates</span>
          </li>
          <li>
            <span class="check-icon">✓</span>
            <span>Screen candidates and manage applications</span>
          </li>
          <li>
            <span class="check-icon">✓</span>
            <span>Use blind hiring for fair assessments</span>
          </li>
        </ul>
      </aside>

      <section class="auth-card">
        <div class="auth-card-logo">
          <div class="auth-card-logo-icon">TS</div>
          <span>TalentScout AI</span>
        </div>
        <h2 class="auth-title">Employer Login</h2>
        <p class="auth-subtitle">Enter your credentials to access your hiring dashboard.</p>
        
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
        
        <form method="post" action="" class="auth-form">
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
            <label for="password">Password</label>
            <input 
              id="password" 
              name="password" 
              type="password" 
              placeholder="Enter your password" 
              required />
          </div>

          <div class="form-row">
            <label class="check" for="remember">
              <input id="remember" name="remember" type="checkbox" />
              <span>Remember me</span>
            </label>
            <a href="#">Forgot password?</a>
          </div>

          <button type="submit" class="btn-auth">Login to Dashboard</button>
        </form>

        <p class="auth-alt">
          No account yet? <a href="./signup.php">Create one here</a>
        </p>
      </section>
    </section>
  </main>
</body>

</html>
