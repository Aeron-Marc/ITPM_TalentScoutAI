<?php
session_start();
require_once __DIR__ . '/../database/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter username and password.';
    } else {
        try {
            $conn = getConnection();
            $stmt = $conn->prepare('SELECT admin_id, username, password FROM admin WHERE username = ? LIMIT 1');
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            if ($row) {
                $stored = $row['password'];
                $ok = false;
                // Prefer password_verify but fall back to plain-text comparison
                if (password_verify($password, $stored)) $ok = true;
                if (!$ok && $password === $stored) $ok = true;

                if ($ok) {
                    $_SESSION['peso_admin_logged_in'] = true;
                    $_SESSION['peso_admin_id'] = $row['admin_id'];
                    $_SESSION['peso_admin_username'] = $row['username'];
                    header('Location: index.php');
                    exit;
                }
            }
            $error = 'Invalid username or password.';
        } catch (Exception $e) {
            $error = 'Login error. Check configuration.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>PESO Admin Login</title>
    <link rel="stylesheet" href="../styles/global.css">
    <style>
        :root {
            --hero-bg: radial-gradient(circle at top, rgba(152, 251, 203, 0.48), transparent 42%), linear-gradient(180deg, #f4fffb 0%, #e8fff7 100%);
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: inherit;
            background: var(--hero-bg);
            display: grid;
            place-items: center;
            color: var(--text-dark);
        }

        .login-shell {
            width: min(1100px, calc(100vw - 2rem));
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            gap: 1.5rem;
            align-items: stretch;
        }

        .login-hero,
        .login-card {
            border-radius: 24px;
            box-shadow: 0 18px 50px rgba(15, 23, 42, 0.10);
            overflow: hidden;
        }

        .login-hero {
            position: relative;
            padding: 2.25rem;
            background: linear-gradient(140deg, #0f766e 0%, #1e9e86 50%, #2dd4bf 100%);
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 560px;
        }

        .login-hero::before,
        .login-hero::after {
            content: '';
            position: absolute;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.14);
            pointer-events: none;
        }

        .login-hero::before {
            width: 220px;
            height: 220px;
            top: -70px;
            right: -40px;
        }

        .login-hero::after {
            width: 160px;
            height: 160px;
            bottom: -50px;
            left: -30px;
        }

        .hero-brand {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .hero-badge {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.16);
            display: grid;
            place-items: center;
            font-size: 1.1rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            backdrop-filter: blur(10px);
        }

        .hero-title {
            position: relative;
            z-index: 1;
            max-width: 420px;
            margin-top: 1.5rem;
        }

        .hero-title h1 {
            margin: 0;
            font-size: clamp(2rem, 4vw, 3.35rem);
            line-height: 0.98;
            letter-spacing: -0.03em;
        }

        .hero-title p {
            margin: 1rem 0 0;
            font-size: 1rem;
            line-height: 1.65;
            color: rgba(255, 255, 255, 0.92);
        }

        .hero-points {
            position: relative;
            z-index: 1;
            display: grid;
            gap: 0.75rem;
            margin-top: 2rem;
        }

        .hero-point {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.85rem 1rem;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.14);
            backdrop-filter: blur(10px);
        }

        .hero-point strong {
            display: block;
        }

        .hero-point span {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .login-card {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(18px);
            padding: 2.25rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-card h2 {
            margin: 0 0 0.5rem;
            font-size: 1.8rem;
            letter-spacing: -0.02em;
        }

        .login-card p {
            margin: 0 0 1.5rem;
            color: var(--text-mid);
            line-height: 1.6;
        }

        .field {
            margin-bottom: 1rem;
        }

        .field label {
            display: block;
            margin-bottom: 0.45rem;
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-dark);
        }

        .input {
            width: 100%;
            padding: 0.9rem 1rem;
            border: 1px solid #cfe7e2;
            border-radius: 14px;
            background: white;
            font-size: 0.95rem;
            transition: border-color .2s, box-shadow .2s, transform .2s;
            box-sizing: border-box;
        }

        .input:focus {
            outline: none;
            border-color: var(--primary-dark);
            box-shadow: 0 0 0 4px rgba(30, 158, 134, 0.12);
        }

        .error {
            margin: 0 0 1rem;
            padding: 0.8rem 0.95rem;
            border-radius: 12px;
            background: #fff1f2;
            color: #b91c1c;
            font-size: 0.92rem;
            font-weight: 600;
        }

        .actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.9rem 1.15rem;
            border-radius: 14px;
            border: none;
            background: linear-gradient(135deg, var(--primary-dark), #0f766e);
            color: white;
            font-weight: 800;
            letter-spacing: 0.01em;
            text-decoration: none;
            box-shadow: 0 10px 24px rgba(30, 158, 134, 0.28);
            cursor: pointer;
        }

        .back-link {
            color: var(--text-mid);
            font-size: 0.92rem;
            text-decoration: none;
        }

        .back-link:hover {
            color: var(--text-dark);
        }

        @media (max-width: 900px) {
            .login-shell {
                grid-template-columns: 1fr;
            }

            .login-hero {
                min-height: 320px;
            }
        }
    </style>
</head>

<body>
    <div class="login-shell">
        <section class="login-hero">
            <div class="hero-brand">
                <div class="hero-badge">TS</div>
                <div>
                    <div style="font-weight:800;font-size:1.05rem;line-height:1;">TalentScout AI</div>
                    <div style="font-size:0.85rem;opacity:0.88;">PESO Admin Portal</div>
                </div>
            </div>
            <div class="hero-title">
                <h1>Manage hiring operations from one secure dashboard.</h1>
                <p>Access employer activity, applications, analytics, and reports with a clean PESO admin workspace built for quick review.</p>
            </div>
            <div class="hero-points">
                <div class="hero-point">
                    <div class="hero-badge" style="width:40px;height:40px;border-radius:12px;">✓</div>
                    <div><strong>Centralized control</strong><span>Track applications, employers, and reports in one place.</span></div>
                </div>
                <div class="hero-point">
                    <div class="hero-badge" style="width:40px;height:40px;border-radius:12px;">🔒</div>
                    <div><strong>Protected access</strong><span>Only authenticated PESO admins can enter the dashboard.</span></div>
                </div>
            </div>
        </section>

        <section class="login-card">
            <h2>Sign in</h2>
            <p>Use your PESO admin credentials to continue.</p>
            <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <form method="post" action="">
                <div class="field">
                    <label for="username">Username</label>
                    <input id="username" name="username" class="input" placeholder="Enter your username" required>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" class="input" placeholder="Enter your password" required>
                </div>
                <div class="actions">
                    <button class="btn" type="submit">Login to dashboard</button>
                </div>
            </form>
        </section>
    </div>
</body>

</html>