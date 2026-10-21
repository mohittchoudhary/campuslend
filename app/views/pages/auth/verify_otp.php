<div style="max-width: 480px; margin: 30px auto 40px auto;">
    <div class="glass-card" style="padding: 36px; border-radius: var(--radius-xl); text-align: center;">
        
        <!-- Header -->
        <div style="width: 58px; height: 58px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 16px auto;">
            ✉️
        </div>

        <h1 style="font-size: 1.6rem; margin-bottom: 8px;">Check Your Campus Email</h1>
        <p style="color: var(--text-muted); font-size: 0.92rem; line-height: 1.5; margin-bottom: 20px;">
            We have sent a 6-digit verification code to:<br>
            <strong style="color: var(--text-main);"><?= e($user['email']) ?></strong>
        </p>

        <!-- Development Mode Simulated OTP Badge -->
        <?php if (!empty($simulatedOtp)): ?>
            <div style="background: rgba(79, 70, 229, 0.08); border: 2px dashed #6366f1; border-radius: var(--radius-md); padding: 12px; margin-bottom: 24px; text-align: center;">
                <div style="font-size: 0.78rem; font-weight: 700; color: var(--primary); text-transform: uppercase; margin-bottom: 4px;">
                    ⚡ Local Test Mode Active
                </div>
                <div style="font-size: 0.85rem; color: var(--text-muted);">
                    Your verification code is: <strong style="font-size: 1.2rem; color: var(--primary); font-family: monospace; letter-spacing: 2px;"><?= e($simulatedOtp) ?></strong>
                </div>
            </div>
        <?php endif; ?>

        <!-- OTP Form -->
        <form method="POST" action="<?= url('/verify-otp') ?>" id="otp-form">
            <?= csrf_field() ?>

            <div class="form-group" style="margin-bottom: 24px;">
                <label for="otp" class="form-label" style="text-align: center;">Enter 6-Digit Verification Code</label>
                <input 
                    type="text" 
                    id="otp" 
                    name="otp" 
                    class="form-control" 
                    placeholder="• • • • • •" 
                    maxlength="6" 
                    pattern="[0-9]{6}" 
                    inputmode="numeric" 
                    autocomplete="one-time-code"
                    style="text-align: center; font-size: 1.8rem; letter-spacing: 12px; font-weight: 800; font-family: monospace; padding: 14px;"
                    value="<?= e($simulatedOtp ?? '') ?>"
                    required 
                    autofocus
                >
                <div class="form-hint" style="text-align: center;">Code expires in 10 minutes • Maximum 5 attempts allowed</div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; font-size: 1rem;">
                <span>Verify & Activate Account</span> →
            </button>
        </form>

        <!-- Resend OTP Form -->
        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border-glass); display: flex; justify-content: space-between; align-items: center; font-size: 0.88rem;">
            <span style="color: var(--text-muted);">Didn't receive the email?</span>
            <form method="POST" action="<?= url('/resend-otp') ?>" style="display: inline; margin: 0;">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline btn-sm">Resend Code</button>
            </form>
        </div>
    </div>
</div>
