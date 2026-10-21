<header class="navbar" id="main-navbar">
    <div class="container navbar-inner">
        <!-- Logo -->
        <a href="<?= url('/') ?>" class="brand-logo">
            <span style="font-size: 1.6rem;">🤝</span>
            <span>CampusLend</span>
            <span class="brand-badge">UTU</span>
        </a>

        <!-- Desktop Navigation Links -->
        <nav>
            <ul class="nav-links">
                <li><a href="<?= url('/feed') ?>" class="nav-link">Live Feed</a></li>
                <li><a href="<?= url('/items') ?>" class="nav-link">Browse Items</a></li>
                <li><a href="<?= url('/how-it-works') ?>" class="nav-link">How It Works</a></li>
                <li><a href="<?= url('/leaderboard') ?>" class="nav-link">Leaderboard</a></li>
            </ul>
        </nav>

        <!-- User Actions & Controls -->
        <div class="nav-actions">
            <!-- Dark / Light Mode Switcher -->
            <button type="button" class="theme-toggle-btn" id="theme-toggle-btn" title="Toggle Theme" aria-label="Toggle dark and light theme">
                <i data-lucide="moon"></i>
            </button>

            <?php if (is_logged_in()): ?>
                <?php $user = auth_user(); ?>
                
                <!-- Post Request Action -->
                <a href="<?= url('/requests/create') ?>" class="btn btn-urgent btn-sm" style="font-weight: 700;">
                    <span>🚨</span> Need Item
                </a>

                <!-- Lend Item Action -->
                <a href="<?= url('/items/create') ?>" class="btn btn-outline btn-sm">
                    <span>➕</span> Lend Item
                </a>

                <!-- Notification Bell -->
                <a href="<?= url('/notifications') ?>" class="theme-toggle-btn" style="position: relative;" title="Notifications">
                    <i data-lucide="bell"></i>
                    <span id="notification-badge" style="display:none; position: absolute; top: -3px; right: -3px; background: #ef4444; color: #fff; font-size: 0.65rem; font-weight: 800; padding: 2px 6px; border-radius: 999px; line-height: 1;">
                        0
                    </span>
                </a>

                <!-- User Dropdown / Profile Button -->
                <div style="display: flex; align-items: center; gap: 8px;">
                    <a href="<?= url('/profile') ?>" style="display: flex; align-items: center; gap: 8px; font-weight: 600; color: var(--text-main);">
                        <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 800; border: 2px solid var(--primary);">
                            <?= strtoupper(substr($user['full_name'] ?? 'U', 0, 1)) ?>
                        </div>
                        <span style="font-size: 0.9rem; max-width: 110px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            <?= e(explode(' ', $user['full_name'])[0]) ?>
                        </span>
                    </a>

                    <?php if (is_admin()): ?>
                        <a href="<?= url('/admin/dashboard') ?>" class="btn btn-sm btn-outline" style="border-color: #8b5cf6; color: #8b5cf6;" title="Admin Dashboard">
                            Admin
                        </a>
                    <?php endif; ?>

                    <form method="POST" action="<?= url('/logout') ?>" style="display: inline; margin: 0;">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-outline" style="padding: 6px 10px;" title="Sign Out">
                            <i data-lucide="log-out" style="width: 16px; height: 16px;"></i>
                        </button>
                    </form>
                </div>

            <?php else: ?>
                <!-- Guest Actions -->
                <a href="<?= url('/login') ?>" class="btn btn-outline btn-sm">Sign In</a>
                <a href="<?= url('/register') ?>" class="btn btn-primary btn-sm">Join Campus</a>
            <?php endif; ?>
        </div>
    </div>
</header>
