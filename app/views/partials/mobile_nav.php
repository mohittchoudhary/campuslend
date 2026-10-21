<nav class="mobile-bottom-nav">
    <a href="<?= url('/feed') ?>" class="mobile-nav-item <?= ($_SERVER['REQUEST_URI'] ?? '') === '/feed' ? 'active' : '' ?>">
        <i data-lucide="radio"></i>
        <span>Live Feed</span>
    </a>
    
    <a href="<?= url('/items') ?>" class="mobile-nav-item <?= str_contains(($_SERVER['REQUEST_URI'] ?? ''), '/items') ? 'active' : '' ?>">
        <i data-lucide="package-search"></i>
        <span>Catalog</span>
    </a>

    <a href="<?= is_logged_in() ? url('/requests/create') : url('/login') ?>" class="mobile-nav-item" style="color: #ef4444; font-weight: 700;">
        <div style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: #fff; width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(239, 68, 68, 0.4); margin-top: -18px;">
            <i data-lucide="plus" style="width: 24px; height: 24px;"></i>
        </div>
        <span style="margin-top: 2px;">Request</span>
    </a>

    <a href="<?= is_logged_in() ? url('/my-activity') : url('/login') ?>" class="mobile-nav-item">
        <i data-lucide="repeat"></i>
        <span>Activity</span>
    </a>

    <a href="<?= is_logged_in() ? url('/profile') : url('/login') ?>" class="mobile-nav-item">
        <i data-lucide="user"></i>
        <span>Profile</span>
    </a>
</nav>
