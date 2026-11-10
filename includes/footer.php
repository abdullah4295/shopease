<?php
/**
 * Footer Partial - ShopEase
 * Closes page wrapper, injects toast container, notification poller (every 60s), theme sync & utility helpers.
 */
?>
<?php if (Auth::check()): ?>
        </main>
    </div>
</div>

<!-- Notification Dropdown / Drawer Modal -->
<div id="notifModal" style="display:none; position:fixed; inset:0; background:rgba(20,19,31,0.4); backdrop-filter:blur(3px); z-index:1080; align-items:flex-start; justify-content:flex-end; padding:68px 24px;">
    <div class="se-card se-card-lg p-3" style="width: 100%; max-width: 360px; background: var(--surface); max-height: 480px; display: flex; flex-direction: column;">
        <div class="d-flex justify-content-between align-items-center pb-2 mb-2 border-bottom">
            <h3 class="h6 mb-0">System Alerts</h3>
            <div class="d-flex gap-2">
                <button type="button" class="btn-ghost" style="padding: 2px 8px; font-size: 11px;" onclick="markAllNotifsRead()">Clear All</button>
                <button type="button" class="btn-ghost" style="padding: 2px 8px; font-size: 11px;" onclick="closeNotifModal()">✕</button>
            </div>
        </div>
        <div id="notifModalList" style="overflow-y: auto; flex: 1;" class="d-flex flex-column gap-2"></div>
    </div>
</div>
<?php endif; ?>

<!-- Toast Notification Container -->
<div id="toastContainer" style="position: fixed; bottom: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 10px; max-width: 360px;"></div>

<!-- Core Vanilla JavaScript Utility Layer -->
<script>
document.getElementById('mobileMenuToggle')?.addEventListener('click', () => {
    document.querySelector('.sidebar')?.classList.toggle('show');
});

// Theme Switcher Sync & Persistence
(function() {
    const themeBtn = document.getElementById('themeToggleBtn');
    if (!themeBtn) return;

    const sunIcon = document.getElementById('themeIconSun');
    const moonIcon = document.getElementById('themeIconMoon');

    function syncIcons(theme) {
        if (theme === 'dark') {
            if (sunIcon) sunIcon.style.display = 'block';
            if (moonIcon) moonIcon.style.display = 'none';
        } else {
            if (sunIcon) sunIcon.style.display = 'none';
            if (moonIcon) moonIcon.style.display = 'block';
        }
    }

    const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
    syncIcons(currentTheme);

    themeBtn.addEventListener('click', async () => {
        const active = document.documentElement.getAttribute('data-theme') || 'light';
        const next = active === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('shopease_theme', next);
        syncIcons(next);

        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
            await fetch('<?= url('api/theme.php') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-Token': csrf
                },
                body: 'theme=' + encodeURIComponent(next) + '&csrf_token=' + encodeURIComponent(csrf)
            });
        } catch (e) {}
    });
})();

window.showToast = function(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = 'se-card';
    toast.style.cssText = `
        padding: 12px 18px;
        font-size: 13.5px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 8px 24px rgba(20, 19, 31, 0.12);
        animation: toastIn 200ms ease;
    `;

    const colors = {
        success: 'var(--success)',
        warning: 'var(--warning)',
        danger: 'var(--danger)',
        info: 'var(--primary)'
    };

    const dot = document.createElement('span');
    dot.style.cssText = `
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: ${colors[type] || colors.info};
        flex-shrink: 0;
    `;

    const text = document.createElement('span');
    text.textContent = message;

    toast.appendChild(dot);
    toast.appendChild(text);
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(8px)';
        toast.style.transition = 'all 200ms ease';
        setTimeout(() => toast.remove(), 200);
    }, 3800);
};

// Global Fetch helper with CSRF injection
window.fetchWithCsrf = async function(url, options = {}) {
    options.headers = options.headers || {};
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (csrfToken) {
        options.headers['X-CSRF-Token'] = csrfToken;
    }
    return fetch(url, options);
};

// Polling for system notifications every 60s
(function() {
    let latestNotifs = [];

    async function pollNotifications() {
        try {
            const res = await fetch('<?= url('api/notifications.php?action=poll') ?>');
            const data = await res.json();
            if (data.success && data.data) {
                latestNotifs = data.data.notifications || [];
                const unread = data.data.unread_count || 0;
                const bell = document.getElementById('notifBellBtn');
                if (!bell) return;

                let badge = bell.querySelector('.badge-counter');
                if (unread > 0) {
                    if (!badge) {
                        badge = document.createElement('span');
                        badge.className = 'badge-counter';
                        bell.appendChild(badge);
                    }
                    badge.textContent = unread;
                } else if (badge) {
                    badge.remove();
                }
            }
        } catch (e) {}
    }

    document.getElementById('notifBellBtn')?.addEventListener('click', () => {
        const modal = document.getElementById('notifModal');
        const list = document.getElementById('notifModalList');
        if (!modal || !list) return;

        list.innerHTML = '';
        if (!latestNotifs.length) {
            list.innerHTML = '<div class="text-center text-muted small py-4">No unread alerts.</div>';
        } else {
            latestNotifs.forEach(n => {
                const item = document.createElement('div');
                item.className = 'p-2 rounded border-bottom';
                item.innerHTML = `
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="status-pill ${n.type}" style="padding: 1px 6px; font-size: 10px;">${n.type}</span>
                        <small class="text-muted" style="font-size: 10px;">${n.created_at}</small>
                    </div>
                    <div class="small mt-1 text-truncate">${n.message}</div>
                `;
                list.appendChild(item);
            });
        }
        modal.style.display = 'flex';
    });

    window.closeNotifModal = function() {
        const modal = document.getElementById('notifModal');
        if (modal) modal.style.display = 'none';
    };

    window.markAllNotifsRead = async function() {
        try {
            await fetchWithCsrf('<?= url('api/notifications.php') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=mark_all'
            });
            latestNotifs = [];
            const badge = document.querySelector('#notifBellBtn .badge-counter');
            if (badge) badge.remove();
            closeNotifModal();
            showToast('All notifications cleared.', 'info');
        } catch (e) {}
    };

    // Initial poll + 60s recurrence
    pollNotifications();
    setInterval(pollNotifications, 60000);
})();
</script>

<style>
@keyframes toastIn {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
</body>
</html>
