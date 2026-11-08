<?php
/**
 * Signup Page - ShopEase
 * Public cashier account registration with validation and CSRF protection.
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/error_handler.php';

if (Auth::check()) {
    redirect(Auth::isAdmin() ? 'pages/dashboard.php' : 'pages/pos.php');
}

$error = null;
$nameValue = '';
$emailValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::verify();

    $nameValue = trim($_POST['name'] ?? '');
    $emailValue = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $result = Auth::signup($nameValue, $emailValue, $password, $confirmPassword);

    if ($result['success']) {
        redirect('pages/pos.php');
    } else {
        $error = $result['message'];
    }
}

$pageTitle = 'Cashier Signup - ShopEase';
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
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(147, 51, 234, 0.16) 0%, rgba(109, 91, 240, 0.04) 70%, transparent 100%);
            filter: blur(50px);
            pointer-events: none;
            z-index: 1;
        }
        .auth-card {
            width: 100%;
            max-width: 440px;
            position: relative;
            z-index: 10;
            padding: 36px 32px;
        }
    </style>
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-blob" style="top: -120px; right: -80px;"></div>
    <div class="auth-blob" style="bottom: -150px; left: -100px;"></div>

    <div class="se-card se-card-lg auth-card">
        <div class="text-center mb-4">
            <div class="brand-icon-box mx-auto mb-2" style="width: 44px; height: 44px; border-radius: 12px; font-size: 20px;">S</div>
            <h1 class="h4 mb-1">Create <span class="grad-text">Staff Account</span></h1>
            <p class="text-muted small mb-0">Join ShopEase as a Sales Cashier</p>
        </div>

        <?php if ($error): ?>
            <div class="status-pill danger w-100 mb-3 justify-content-center py-2" role="alert">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="signup.php" novalidate>
            <?= Csrf::field() ?>

            <div class="mb-3">
                <label for="name" class="form-label small fw-semibold text-muted mb-1">Full Name</label>
                <input type="text" name="name" id="name" class="se-input" value="<?= e($nameValue) ?>" placeholder="e.g. John Doe" required autofocus>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label small fw-semibold text-muted mb-1">Email Address</label>
                <input type="email" name="email" id="email" class="se-input" value="<?= e($emailValue) ?>" placeholder="name@domain.com" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label small fw-semibold text-muted mb-1">Password (min 8 characters)</label>
                <input type="password" name="password" id="password" class="se-input" placeholder="••••••••" required>
            </div>

            <div class="mb-4">
                <label for="confirm_password" class="form-label small fw-semibold text-muted mb-1">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" class="se-input" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-primary-grad w-100 py-2 mb-3">
                <span>Create Cashier Account</span>
                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </button>
        </form>

        <div class="text-center pt-2 border-top">
            <span class="small text-muted">Already have an account? </span>
            <a href="login.php" class="small fw-semibold text-decoration-none" style="color: var(--primary);">Sign in here</a>
        </div>
    </div>
</div>

</body>
</html>
