<div style="max-width: 480px; margin: 30px auto 40px auto;">
    <div class="glass-card" style="padding: 36px; border-radius: var(--radius-xl);">
        
        <!-- Header -->
        <div style="text-align: center; margin-bottom: 28px;">
            <div style="width: 54px; height: 54px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.6rem; margin: 0 auto 14px auto;">
                🔐
            </div>
            <h1 style="font-size: 1.75rem; margin-bottom: 6px;">Sign In to CampusLend</h1>
            <p style="color: var(--text-muted); font-size: 0.92rem;">
                Enter your <strong>@<?= e($campusDomain) ?></strong> student credentials
            </p>
        </div>

        <!-- Quick Demo Accounts Helper -->
        <div style="background: rgba(79, 70, 229, 0.06); border: 1px solid rgba(79, 70, 229, 0.2); border-radius: var(--radius-md); padding: 14px; margin-bottom: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <span style="font-size: 0.8rem; font-weight: 700; color: var(--primary); text-transform: uppercase;">⚡ Quick Demo Logins</span>
                <span style="font-size: 0.75rem; color: var(--text-muted);">Click to autofill</span>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                <button type="button" class="btn btn-outline btn-sm" style="font-size: 0.78rem; padding: 4px 10px;" onclick="autofill('admin@utu.ac.in', 'Admin@123')">
                    👑 Admin: admin@utu.ac.in
                </button>
                <button type="button" class="btn btn-outline btn-sm" style="font-size: 0.78rem; padding: 4px 10px;" onclick="autofill('aarav.patel@utu.ac.in', 'Student@123')">
                    🎒 Student: aarav.patel@utu.ac.in
                </button>
            </div>
        </div>

        <!-- Login Form -->
        <form method="POST" action="<?= url('/login') ?>" id="login-form">
            <?= csrf_field() ?>

            <!-- Email -->
            <div class="form-group">
                <label for="email" class="form-label">Campus Email</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="yourname@<?= e($campusDomain) ?>" required autofocus>
            </div>

            <!-- Password -->
            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <label for="password" class="form-label" style="margin-bottom: 0;">Password</label>
                    <a href="<?= url('/forgot-password') ?>" style="font-size: 0.82rem; font-weight: 600;">Forgot Password?</a>
                </div>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <!-- Remember Me -->
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 24px;">
                <input type="checkbox" id="remember" name="remember" value="1" style="width: 16px; height: 16px; accent-color: var(--primary); cursor: pointer;">
                <label for="remember" style="font-size: 0.88rem; color: var(--text-muted); cursor: pointer;">Remember me on this campus device (30 days)</label>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; font-size: 1rem;">
                <span>Sign In to Platform</span> →
            </button>
        </form>

        <!-- Footer -->
        <div style="text-align: center; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border-glass); font-size: 0.92rem; color: var(--text-muted);">
            New student on campus? <a href="<?= url('/register') ?>" style="font-weight: 700;">Join CampusLend</a>
        </div>
    </div>
</div>

<script>
function autofill(email, password) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = password;
}
</script>
