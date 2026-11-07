<?php
/**
 * ShopEase - Global Error & Exception Handler
 * Intercepts PHP errors and uncaught exceptions gracefully.
 */

set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    // Log deprecations without throwing fatal ErrorException
    if ($severity === E_DEPRECATED || $severity === E_USER_DEPRECATED) {
        error_log("PHP Deprecation: $message in $file:$line");
        return true;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function (Throwable $e) {
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
        || (isset($_SERVER['REQUEST_URI']) && str_contains($_SERVER['REQUEST_URI'], '/api/'));

    error_log(sprintf(
        "ShopEase Exception: %s in %s:%d\nStack trace:\n%s",
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    ));

    http_response_code(500);

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => (defined('APP_ENV') && APP_ENV === 'development') ? $e->getMessage() : 'An internal server error occurred.',
            'debug'   => (defined('APP_ENV') && APP_ENV === 'development') ? [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ] : null
        ]);
        exit;
    }

    if (defined('APP_ENV') && APP_ENV === 'development') {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Application Error - ShopEase</title>
            <style>
                body { font-family: monospace; background: #0F0E17; color: #F0EDFD; padding: 30px; margin: 0; }
                .box { background: #1C1A29; border: 1px solid #322F4A; border-radius: 12px; padding: 24px; max-width: 900px; margin: 40px auto; }
                h1 { color: #E5484D; font-size: 20px; margin-top: 0; }
                pre { background: #12111C; padding: 16px; border-radius: 8px; overflow-x: auto; color: #A5A1C0; }
            </style>
        </head>
        <body>
            <div class="box">
                <h1>Application Error (Development Mode)</h1>
                <p><strong>Message:</strong> <?= htmlspecialchars($e->getMessage()) ?></p>
                <p><strong>Location:</strong> <?= htmlspecialchars($e->getFile()) ?> : <?= $e->getLine() ?></p>
                <pre><?= htmlspecialchars($e->getTraceAsString()) ?></pre>
            </div>
        </body>
        </html>
        <?php
    } else {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Internal Error - ShopEase</title>
            <style>
                body { font-family: system-ui, sans-serif; background: #F6F5FB; color: #14131F; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
                .card { background: #FFF; padding: 40px; border-radius: 16px; border: 1px solid #E7E5F2; text-align: center; max-width: 440px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
                h1 { font-size: 22px; margin-bottom: 8px; }
                p { color: #6B6B7D; font-size: 14px; margin-bottom: 24px; }
                a { display: inline-block; background: #5B4FE8; color: #FFF; padding: 10px 20px; border-radius: 999px; text-decoration: none; font-weight: 600; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class="card">
                <h1>Something went wrong</h1>
                <p>We encountered an unexpected error while processing your request. Please try again or return to the homepage.</p>
                <a href="<?= htmlspecialchars(defined('BASE_URL') ? BASE_URL : '/') ?>">Back to ShopEase</a>
            </div>
        </body>
        </html>
        <?php
    }
    exit;
});
