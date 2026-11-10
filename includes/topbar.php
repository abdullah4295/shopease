<?php
/**
 * Topbar Partial - ShopEase
 * Clean white header with pill search, held bills chip, notifications, theme toggle & user status.
 */
$user = Auth::user();
$initial = strtoupper(substr($user['name'] ?? 'U', 0, 1));

// Retrieve counts safely
$heldCount = 0;
$unreadCount = 0;
try {
    if (Auth::isAdmin()) {
        $held = Database::fetch("SELECT COUNT(*) as cnt FROM `held_bills`");
    } else {
        $held = Database::fetch("SELECT COUNT(*) as cnt FROM `held_bills` WHERE `cashier_id` = ?", [Auth::id()]);
    }
    $heldCount = (int)($held['cnt'] ?? 0);

    $notif = Database::fetch("SELECT COUNT(*) as cnt FROM `notifications` WHERE `is_read` = 0");
    $unreadCount = (int)($notif['cnt'] ?? 0);
} catch (Throwable $t) {
    // Graceful fallback during setup
}
?>
<header class="topbar">
    <div class="d-flex align-items-center gap-3">
        <!-- Mobile Sidebar Toggle -->
        <button class="btn-icon-topbar d-lg-none" id="mobileMenuToggle" aria-label="Toggle navigation menu">
            <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
        </button>

        <!-- Pill Search Bar -->
        <div class="topbar-search d-none d-md-block">
            <svg class="search-icon" style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            <input type="text" id="globalSearchInput" placeholder="Search products, invoices, or SKU..." autocomplete="off">
        </div>
    </div>

    <!-- Topbar Actions & Profile -->
    <div class="topbar-actions">
        <!-- Held Bills Shortcut Chip -->
        <a href="<?= url('pages/pos.php?action=held_bills') ?>" class="btn-ghost" style="padding: 6px 14px; font-size: 13px;" title="View Held Bills">
            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span>Held Bills</span>
            <?php if ($heldCount > 0): ?>
                <span class="status-pill warning" style="padding: 2px 7px; font-size: 11px;"><?= $heldCount ?></span>
            <?php endif; ?>
        </a>

        <!-- Dark / Light Theme Toggle -->
        <button class="btn-icon-topbar" id="themeToggleBtn" aria-label="Toggle theme" title="Toggle Light/Dark Theme">
            <svg id="themeIconMoon" style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
            <svg id="themeIconSun" style="width: 18px; height: 18px; display: none;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
        </button>

        <!-- Notification Bell -->
        <button class="btn-icon-topbar" id="notifBellBtn" aria-label="Notifications" title="System Notifications">
            <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
            <?php if ($unreadCount > 0): ?>
                <span class="badge-counter"><?= $unreadCount ?></span>
            <?php endif; ?>
        </button>

        <!-- User Profile Pill -->
        <a href="<?= url('pages/profile.php') ?>" class="text-decoration-none d-flex align-items-center gap-2 ps-2" title="My Profile & Settings" style="color: inherit;">
            <?php if (!empty($user['profile_photo'])): ?>
                <img src="<?= asset(e($user['profile_photo'])) ?>" alt="Profile" class="avatar-circle" style="width: 34px; height: 34px; object-fit: cover;">
            <?php else: ?>
                <div class="avatar-circle" style="width: 34px; height: 34px; font-size: 13px;"><?= e($initial) ?></div>
            <?php endif; ?>
            <div class="d-none d-sm-block text-start">
                <div style="font-size: 13px; font-weight: 600; line-height: 1.2;"><?= e($user['name'] ?? 'User') ?></div>
                <div style="font-size: 11px; color: var(--muted);"><?= e(ucfirst($user['role'] ?? 'cashier')) ?></div>
            </div>
        </a>
    </div>
</header>
