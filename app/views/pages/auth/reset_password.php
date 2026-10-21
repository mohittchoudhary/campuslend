<div style="max-width: 480px; margin: 30px auto 40px auto;">
    <div class="glass-card" style="padding: 36px; border-radius: var(--radius-xl);">
        
        <!-- Header -->
        <div style="text-align: center; margin-bottom: 28px;">
            <div style="width: 54px; height: 54px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.6rem; margin: 0 auto 14px auto;">
                🔒
            </div>
            <h1 style="font-size: 1.6rem; margin-bottom: 8px;">Choose New Password</h1>
            <p style="color: var(--text-muted); font-size: 0.92rem;">
                Create a strong new password for your CampusLend account.
            </p>
        </div>

        <form method="POST" action="<?= url('/reset-password') ?>" id="reset-form">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= e($token) ?>">

            <div class="form-group">
                <label for="password" class="form-label">New Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="At least 6 characters" minlength="6" required autofocus>
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
                <label for="password_confirm" class="form-label">Confirm New Password</label>
                <input type="password" id="password_confirm" name="password_confirm" class="form-control" placeholder="Re-enter new password" minlength="6" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; font-size: 1rem;">
                <span>Update Password & Sign In</span> →
            </button>
        </form>
    </div>
</div>
