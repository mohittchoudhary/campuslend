<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <meta name="description" content="CampusLend – Emergency Campus Borrowing Platform for Uka Tarsadia University students. Borrow calculators, drafters, chargers, pens, lab coats in seconds.">
    
    <title><?= e($title ?? 'CampusLend – Emergency Borrowing Platform') ?></title>
    
    <!-- Google Fonts: Outfit & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons via unpkg -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- GSAP for micro-animations -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    
    <!-- Core Application Stylesheet -->
    <link rel="stylesheet" href="<?= asset('assets/css/main.css') ?>">
    <link rel="manifest" href="<?= url('manifest.json') ?>">
    <meta name="theme-color" content="#4f46e5">
</head>
<body>

    <!-- Exam Season Alert Banner -->
    <?php 
    $examEnabled = \App\Models\Setting::get('exam_mode', '1') === '1';
    $examMessage = \App\Models\Setting::get('exam_banner_message', '📢 Mid-Term Exam Alert: Emergency requests for Calculators, Drafters, and Lab Coats are prioritized. Return borrowed items on time!');
    ?>
    <?php if ($examEnabled): ?>
    <div class="exam-banner" id="exam-banner">
        <span><?= e($examMessage) ?></span>
    </div>
    <?php endif; ?>

    <!-- Header Navigation -->
    <?php require __DIR__ . '/../partials/navbar.php'; ?>

    <!-- Main Content Body -->
    <main class="main-content">
        <div class="container">
            <!-- Flash Message Alerts -->
            <?php if (has_flash('success')): ?>
                <div class="alert alert-success" role="alert">
                    <span>✅</span>
                    <div><?= e(flash('success')) ?></div>
                </div>
            <?php endif; ?>

            <?php if (has_flash('error')): ?>
                <div class="alert alert-danger" role="alert">
                    <span>⚠️</span>
                    <div><?= e(flash('error')) ?></div>
                </div>
            <?php endif; ?>

            <?php if (has_flash('warning')): ?>
                <div class="alert alert-warning" role="alert">
                    <span>🔔</span>
                    <div><?= e(flash('warning')) ?></div>
                </div>
            <?php endif; ?>

            <?php if (has_flash('info')): ?>
                <div class="alert alert-info" role="alert" style="background: rgba(79, 70, 229, 0.1); color: #4338ca; border: 1px solid rgba(79, 70, 229, 0.25);">
                    <span>ℹ️</span>
                    <div><?= e(flash('info')) ?></div>
                </div>
            <?php endif; ?>

            <!-- Page Output Injection -->
            <?= $content ?? '' ?>
        </div>
    </main>

    <!-- Floating Emergency SOS Button -->
    <a href="<?= is_logged_in() ? url('/requests/create?urgent=1') : url('/login') ?>" class="floating-sos-btn" id="floating-sos-btn" title="One-Click Emergency Request">
        <span>🚨</span>
        <span>SOS Request</span>
    </a>

    <!-- Footer -->
    <?php require __DIR__ . '/../partials/footer.php'; ?>

    <!-- Mobile Bottom Navigation Bar -->
    <?php require __DIR__ . '/../partials/mobile_nav.php'; ?>

    <!-- Toast Notification Container -->
    <div id="toast-container"></div>

    <!-- Scripts -->
    <script src="<?= asset('assets/js/main.js') ?>"></script>
    <script>
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>
