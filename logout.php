<?php
/**
 * Logout - ShopEase
 * Destroys user session and redirects to login.
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/error_handler.php';

Auth::logout();
redirect('login.php');
