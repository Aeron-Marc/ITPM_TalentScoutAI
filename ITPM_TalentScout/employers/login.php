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
  <link rel="stylesheet" href="../styles/global.css" />
  <link rel="stylesheet" href="../styles/page-layout.css" />
  <style>
    .auth-wrap {
      min-height: calc(100vh - var(--nav-height));
      padding: 3rem 1.25rem;
      background: linear-gradient(160deg,
          var(--bg-lighter) 0%,
          white 45%,
          var(--bg-light) 100%);
      display: grid;
      place-items: center;
    }

    .auth-grid {
      width: 100%;
      max-width: 1050px;
      display: grid;
      grid-template-columns: 1.1fr 1fr;
      background: white;
      border: 1.5px solid var(--border);
      border-radius: var(--radius);
      overflow: hidden;
      box-shadow: var(--shadow);
    }

    .auth-info {
      padding: 2.5rem;
      background: linear-gradient(145deg, #1e9e86 0%, #167865 100%);
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

    .auth-info h1 {
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

    .field label {
      display: block;
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--text-mid);
      margin-bottom: 0.35rem;
    }

    .field input {
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

    .field input:focus {
      border-color: var(--primary-dark);
      box-shadow: 0 0 0 3px rgba(30, 158, 134, 0.12);
    }

    .form-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 0.8rem;
      flex-wrap: wrap;
      font-size: 0.83rem;
      color: var(--text-light);
    }

    .check {
      display: inline-flex;
      align-items: center;
      gap: 0.45rem;
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

    @media (max-width: 900px) {
      .auth-grid {
        grid-template-columns: 1fr;
      }

      .auth-info,
      .auth-card {
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
      <li><a href="./modules/post-jobs/">Post Jobs</a></li>
      <li><a href="./modules/employee-finder/">Find Talent</a></li>
      <li><a href="./modules/applicant-tracking/">Hiring Pipeline</a></li>
      <li><a href="./modules/chat-sms/">Messages</a></li>
    </ul>
    <div class="nav-actions">
      <a href="./login.php" class="btn btn-outline">Login</a>
      <a href="./signup.php" class="btn btn-primary">Get Started</a>
    </div>
  </nav>

  <main class="auth-wrap">
    <section class="auth-grid">
      <aside class="auth-info">
        <span class="auth-kicker">Welcome Back</span>
        <h1>Sign in to start hiring</h1>
        <p>
          Access your hiring dashboard, manage job postings, screen candidates, and collaborate with your team using TalentScout AI.
        </p>
        <ul class="auth-points">
          <li>
            <span>✓</span><span>Post jobs and reach pre-vetted talent</span>
          </li>
          <li>
            <span>✓</span><span>Manage applications in one dashboard</span>
          </li>
          <li>
            <span>✓</span><span>Use blind hiring for fair assessment</span>
          </li>
        </ul>
      </aside>

      <div class="auth-card">
        <h1 class="auth-title">Employer Login</h1>
        <p class="auth-subtitle">
          Enter your credentials to access your hiring dashboard.
        </p>

        <form class="auth-form" action="" method="post">
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

          <button type="submit" class="btn btn-primary auth-submit">
            Login to Dashboard
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
          No account yet? <a href="./signup.php">Create one here</a>
        </p>
      </div>
    </section>
  </main>
</body>

</html>
