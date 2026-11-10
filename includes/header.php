<?php
/**
 * Header Partial - ShopEase
 * HTML5 document head, meta tags, fonts, styles, and model-viewer loader.
 */
if (!defined('SHOPEASE_INIT')) {
    require_once __DIR__ . '/../config/config.php';
}

$pageTitle = $pageTitle ?? APP_NAME . ' - ' . APP_TAGLINE;
$currentUser = Auth::user();
$userTheme = $currentUser['theme'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= e($userTheme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(Csrf::getToken()) ?>">
    <title><?= e($pageTitle) ?></title>

    <!-- Instant Theme Loader (Prevents white flash on dark theme) -->
    <script>
        (function() {
            const saved = localStorage.getItem('shopease_theme');
            if (saved) {
                document.documentElement.setAttribute('data-theme', saved);
            }
        })();
    </script>

    <!-- Google Fonts: Space Grotesk & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 Grid and Utilities Only -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap-grid.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap-utilities.min.css">

    <!-- Bootstrap Icons (Fallback icon set only) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Locked UI Design System (theme.css) -->
    <link rel="stylesheet" href="<?= asset('css/theme.css') ?>">

    <!-- Google <model-viewer> for 3D Product & AR Rendering (Lazy loaded) -->
    <script type="module" src="https://ajax.googleapis.com/ajax/libs/model-viewer/3.5.0/model-viewer.min.js"></script>
</head>
<body>
<?php if (Auth::check()): ?>
<div class="app-shell">
    <?php require_once __DIR__ . '/sidebar.php'; ?>
    <div class="main-wrapper">
        <?php require_once __DIR__ . '/topbar.php'; ?>
        <main class="content-body">
<?php endif; ?>
