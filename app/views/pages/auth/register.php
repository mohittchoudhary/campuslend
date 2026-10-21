<?php
$old = $_SESSION['old'] ?? [];
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['old'], $_SESSION['errors']);
?>

<div style="max-width: 580px; margin: 20px auto 40px auto;">
    <div class="glass-card" style="padding: 36px; border-radius: var(--radius-xl);">
        <!-- Card Header -->
        <div style="text-align: center; margin-bottom: 28px;">
            <div style="width: 54px; height: 54px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.6rem; margin: 0 auto 14px auto;">
                🎓
            </div>
            <h1 style="font-size: 1.75rem; margin-bottom: 6px;">Create Campus Account</h1>
            <p style="color: var(--text-muted); font-size: 0.92rem;">
                Exclusively for students of <strong><?= e($campusName) ?></strong>
            </p>
            <div style="display: inline-block; background: var(--primary-light); color: var(--primary); font-size: 0.78rem; font-weight: 700; padding: 4px 12px; border-radius: var(--radius-full); margin-top: 8px;">
                Domain Required: @<?= e($campusDomain) ?>
            </div>
        </div>

        <!-- Registration Form -->
        <form method="POST" action="<?= url('/register') ?>" id="register-form" novalidate>
            <?= csrf_field() ?>

            <!-- Full Name -->
            <div class="form-group">
                <label for="full_name" class="form-label">Full Name *</label>
                <input type="text" id="full_name" name="full_name" class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>" placeholder="e.g. Aarav Patel" value="<?= e($old['full_name'] ?? '') ?>" required>
                <?php if (isset($errors['full_name'])): ?>
                    <div class="invalid-feedback"><?= e($errors['full_name']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Campus Email -->
            <div class="form-group">
                <label for="email" class="form-label">Campus Email Address *</label>
                <div style="position: relative;">
                    <input type="email" id="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" placeholder="e.g. yourname@<?= e($campusDomain) ?>" value="<?= e($old['email'] ?? '') ?>" required>
                </div>
                <div class="form-hint" id="domain-hint">Must end with @<?= e($campusDomain) ?>. An OTP will be dispatched to this address.</div>
                <?php if (isset($errors['email'])): ?>
                    <div class="invalid-feedback"><?= e($errors['email']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Enrollment Number & Department Grid -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label for="enrollment_no" class="form-label">Enrollment Number *</label>
                    <input type="text" id="enrollment_no" name="enrollment_no" class="form-control <?= isset($errors['enrollment_no']) ? 'is-invalid' : '' ?>" placeholder="e.g. 202303100110001" value="<?= e($old['enrollment_no'] ?? '') ?>" required>
                    <?php if (isset($errors['enrollment_no'])): ?>
                        <div class="invalid-feedback"><?= e($errors['enrollment_no']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="department" class="form-label">Department *</label>
                    <select id="department" name="department" class="form-control <?= isset($errors['department']) ? 'is-invalid' : '' ?>" required>
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= e($dept) ?>" <?= ($old['department'] ?? '') === $dept ? 'selected' : '' ?>>
                                <?= e($dept) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['department'])): ?>
                        <div class="invalid-feedback"><?= e($errors['department']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Year of Study & Phone Grid -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label for="year_of_study" class="form-label">Year of Study *</label>
                    <select id="year_of_study" name="year_of_study" class="form-control <?= isset($errors['year_of_study']) ? 'is-invalid' : '' ?>" required>
                        <?php foreach ($years as $yr): ?>
                            <option value="<?= e($yr) ?>" <?= ($old['year_of_study'] ?? '') === $yr ? 'selected' : '' ?>>
                                <?= e($yr) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['year_of_study'])): ?>
                        <div class="invalid-feedback"><?= e($errors['year_of_study']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="phone" class="form-label">Phone / WhatsApp *</label>
                    <input type="tel" id="phone" name="phone" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" placeholder="e.g. 9825100000" value="<?= e($old['phone'] ?? '') ?>" required>
                    <?php if (isset($errors['phone'])): ?>
                        <div class="invalid-feedback"><?= e($errors['phone']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label for="password" class="form-label">Password *</label>
                <input type="password" id="password" name="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" placeholder="At least 6 characters" required>
                <?php if (isset($errors['password'])): ?>
                    <div class="invalid-feedback"><?= e($errors['password']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; font-size: 1rem; margin-top: 10px;">
                <span>Create Campus Account & Send OTP</span> →
            </button>
        </form>

        <!-- Footer Link -->
        <div style="text-align: center; margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border-glass); font-size: 0.92rem; color: var(--text-muted);">
            Already have a verified account? <a href="<?= url('/login') ?>" style="font-weight: 700;">Sign In</a>
        </div>
    </div>
</div>

<script>
// Client-side domain check helper
document.getElementById('email').addEventListener('input', function() {
    const domain = "<?= e($campusDomain) ?>";
    const val = this.value.trim().toLowerCase();
    const hint = document.getElementById('domain-hint');
    if (val.length > 5 && !val.endsWith('@' + domain) && !val.endsWith('.' + domain)) {
        hint.style.color = '#ef4444';
        hint.textContent = `⚠️ Please ensure your email ends with @${domain}`;
    } else {
        hint.style.color = 'var(--text-muted)';
        hint.textContent = `Must end with @${domain}. An OTP will be dispatched to this address.`;
    }
});
</script>
