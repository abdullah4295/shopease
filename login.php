<?php
/**
 * Login Page - ShopEase
 * Secured login with rate-limiting, CSRF token, and violet-glass aesthetic.
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/error_handler.php';

// Redirect if already logged in
if (Auth::check()) {
    redirect(Auth::isAdmin() ? 'pages/dashboard.php' : 'pages/pos.php');
}

$error = null;
$emailValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();

    $emailValue = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $result = Auth::login($emailValue, $password);

    if ($result['success']) {
        redirect(Auth::isAdmin() ? 'pages/dashboard.php' : 'pages/pos.php');
    } else {
        $error = $result['message'];
    }
}

$pageTitle = 'Sign In - ShopEase';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap-grid.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap-utilities.min.css">
    <link rel="stylesheet" href="<?= asset('css/theme.css') ?>">
    <style>
        .auth-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            position: relative;
            overflow: hidden;
            background: var(--bg);
        }
        .auth-blob {
            position: absolute;
            width: 480px;
            height: 480px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(147, 51, 234, 0.18) 0%, rgba(109, 91, 240, 0.05) 70%, transparent 100%);
            filter: blur(50px);
            pointer-events: none;
            z-index: 1;
        }
        .auth-card {
            width: 100%;
            max-width: 420px;
            position: relative;
            z-index: 10;
            padding: 36px 32px;
        }
        .demo-chip {
            cursor: pointer;
            font-size: 11.5px;
            padding: 4px 10px;
            border-radius: var(--radius-pill);
            background: var(--bg);
            border: 1px solid var(--border);
            color: var(--muted);
            transition: all 180ms ease;
        }
        .demo-chip:hover {
            border-color: var(--primary);
            color: var(--primary);
        }
    </style>
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-blob" style="top: -120px; right: -100px;"></div>
    <div class="auth-blob" style="bottom: -150px; left: -100px;"></div>

    <div class="se-card se-card-lg auth-card">
        <!-- Logo Header -->
        <div class="text-center mb-4">
            <div class="brand-icon-box mx-auto mb-2" style="width: 44px; height: 44px; border-radius: 12px; font-size: 20px;">S</div>
            <h1 class="h4 mb-1">Sign in to <span class="grad-text">ShopEase</span></h1>
            <p class="text-muted small mb-0">3D Shop Inventory & Sales Management System</p>
        </div>

        <?php if ($error): ?>
            <div class="status-pill danger w-100 mb-3 justify-content-center py-2" role="alert">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="login.php" novalidate>
            <?= Csrf::field() ?>

            <div class="mb-3">
                <label for="email" class="form-label small fw-semibold text-muted mb-1">Email Address</label>
                <input type="email" name="email" id="email" class="se-input" value="<?= e($emailValue) ?>" placeholder="name@domain.com" required autofocus>
            </div>

            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="password" class="form-label small fw-semibold text-muted mb-0">Password</label>
                    <a href="pages/forgot_password.php" class="small text-decoration-none" style="color: var(--primary); font-size: 12px;">Forgot password?</a>
                </div>
                <input type="password" name="password" id="password" class="se-input" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-primary-grad w-100 py-2 mb-3">
                <span>Sign in to Dashboard</span>
                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </button>

            <div class="text-center mb-3">
                <a href="signup.php" class="small text-decoration-none" style="color: var(--muted); font-size: 12px;">Don't have an account? Sign up as Cashier</a>
            </div>
        </form>

        <!-- Demo Accounts Helper -->
        <div class="mt-3 pt-3 border-top text-center">
            <span class="small text-muted d-block mb-2" style="font-size: 11.5px;">Click to quick-fill demo credentials:</span>
            <div class="d-flex justify-content-center gap-2">
                <button type="button" class="demo-chip" onclick="fillDemo('admin@shopease.local', 'Admin123!')">Admin Demo</button>
                <button type="button" class="demo-chip" onclick="fillDemo('john@shopease.local', 'Cashier123!')">Cashier Demo</button>
            </div>
        </div>
    </div>
</div>

<script>
function fillDemo(email, pass) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = pass;
}
</script>
</body>
</html>
