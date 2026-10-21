<div style="max-width: 480px; margin: 30px auto 40px auto;">
    <div class="glass-card" style="padding: 36px; border-radius: var(--radius-xl);">
        
        <!-- Header -->
        <div style="text-align: center; margin-bottom: 28px;">
            <div style="width: 54px; height: 54px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.6rem; margin: 0 auto 14px auto;">
                🔑
            </div>
            <h1 style="font-size: 1.6rem; margin-bottom: 8px;">Reset Password</h1>
            <p style="color: var(--text-muted); font-size: 0.92rem; line-height: 1.5;">
                Enter your campus email and we will send you a secure password reset link.
            </p>
        </div>

        <?php if (!empty($_SESSION['last_reset_token'])): ?>
            <!-- Development Mode Helper -->
            <div style="background: rgba(79, 70, 229, 0.08); border: 2px dashed #6366f1; border-radius: var(--radius-md); padding: 12px; margin-bottom: 24px; text-align: center;">
                <div style="font-size: 0.78rem; font-weight: 700; color: var(--primary); text-transform: uppercase; margin-bottom: 4px;">
                    ⚡ Local Test Mode Active
                </div>
                <div style="font-size: 0.85rem; color: var(--text-muted);">
                    <a href="<?= url('/reset-password?token=' . urlencode($_SESSION['last_reset_token'])) ?>" class="btn btn-outline btn-sm" style="margin-top: 6px;">
                        Click Here to Reset Password Directly →
                    </a>
                </div>
            </div>
            <?php unset($_SESSION['last_reset_token']); ?>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" action="<?= url('/forgot-password') ?>" id="forgot-form">
            <?= csrf_field() ?>

            <div class="form-group" style="margin-bottom: 24px;">
                <label for="email" class="form-label">Registered Campus Email</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="yourname@<?= e($campusDomain) ?>" required autofocus>
                <div class="form-hint">Must end with @<?= e($campusDomain) ?></div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; font-size: 1rem;">
                <span>Send Password Reset Link</span> →
            </button>
        </form>

        <div style="text-align: center; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border-glass); font-size: 0.92rem; color: var(--text-muted);">
            Remembered your credentials? <a href="<?= url('/login') ?>" style="font-weight: 700;">Back to Sign In</a>
        </div>
    </div>
</div>
