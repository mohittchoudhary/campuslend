<div class="profile-page" style="max-width: 960px; margin: 20px auto 50px auto;">
    
    <!-- Profile Header Card -->
    <div class="glass-card" style="padding: 36px; border-radius: var(--radius-xl); margin-bottom: 30px;">
        <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-start; gap: 24px;">
            
            <div style="display: flex; align-items: center; gap: 24px; flex-wrap: wrap;">
                <!-- Avatar with Upload Indicator -->
                <div style="position: relative;">
                    <div style="width: 90px; height: 90px; border-radius: 50%; overflow: hidden; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 2.5rem; font-weight: 800; border: 3px solid var(--primary); box-shadow: var(--shadow-md);">
                        <?php if (!empty($user['avatar']) && file_exists(__DIR__ . '/../../../../public/uploads/avatars/' . $user['avatar'])): ?>
                            <img src="<?= asset('uploads/avatars/' . $user['avatar']) ?>" alt="<?= e($user['full_name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>

                    <?php if ($isOwnProfile): ?>
                        <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('avatar-input').click()" style="position: absolute; bottom: -4px; right: -4px; border-radius: 50%; width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center;" title="Upload Avatar">
                            📷
                        </button>
                        <form id="avatar-form" method="POST" action="<?= url('/profile/avatar') ?>" enctype="multipart/form-data" style="display: none;">
                            <?= csrf_field() ?>
                            <input type="file" id="avatar-input" name="avatar" accept=".jpg,.jpeg,.png,.webp" onchange="document.getElementById('avatar-form').submit()">
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Info -->
                <div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
                        <h1 style="font-size: 1.85rem; margin: 0;"><?= e($user['full_name']) ?></h1>
                        <?php if ((int)$user['is_verified'] === 1): ?>
                            <span class="badge badge-verified" title="Campus Verified Student">✓ Verified</span>
                        <?php endif; ?>
                    </div>

                    <div style="font-size: 0.95rem; color: var(--text-muted); margin-bottom: 8px;">
                        <?= e($user['department']) ?> • <?= e($user['year_of_study']) ?>
                    </div>

                    <div style="display: flex; align-items: center; gap: 16px; font-size: 0.85rem;">
                        <span>🎓 Enrollment: <strong><?= e($user['enrollment_no']) ?></strong></span>
                        <span>✉️ <?= e($user['email']) ?></span>
                    </div>
                </div>
            </div>

            <!-- Trust Score & Peer Action Buttons -->
            <div style="text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 12px;">
                <div style="background: var(--bg-surface); border: 1px solid var(--border-glass); padding: 14px 24px; border-radius: var(--radius-md); text-align: center;">
                    <div style="font-size: 2rem; font-weight: 900; color: #f59e0b; line-height: 1;">
                        ⭐ <?= number_format($user['trust_score'], 1) ?>
                    </div>
                    <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: var(--text-muted); margin-top: 4px;">
                        Trust Score (<?= (int)$user['total_ratings'] ?> reviews)
                    </div>
                </div>

                <?php if (!$isOwnProfile): ?>
                    <div style="display: flex; gap: 8px;">
                        <?php if ($isBlocked): ?>
                            <form method="POST" action="<?= url('/users/unblock') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="target_id" value="<?= $user['id'] ?>">
                                <button type="submit" class="btn btn-outline btn-sm">Unblock User</button>
                            </form>
                        <?php else: ?>
                            <form method="POST" action="<?= url('/users/block') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="target_id" value="<?= $user['id'] ?>">
                                <button type="submit" class="btn btn-outline btn-sm" onclick="return confirm('Block this student from contacting you?')">🚫 Block</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Badges Earned Ribbon -->
        <div style="border-top: 1px solid var(--border-glass); margin-top: 24px; padding-top: 20px;">
            <div style="font-size: 0.8rem; text-transform: uppercase; font-weight: 800; color: var(--text-muted); margin-bottom: 12px;">
                Earned Campus Badges (<?= count($badges) ?>)
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                <?php if (!empty($badges)): ?>
                    <?php foreach ($badges as $b): ?>
                        <span class="badge" style="background: linear-gradient(135deg, rgba(79, 70, 229, 0.15) 0%, rgba(245, 158, 11, 0.15) 100%); color: var(--primary); padding: 8px 16px; font-size: 0.85rem; border: 1px solid var(--border-glass);">
                            🏆 <?= e($b['badge_name']) ?>
                        </span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <span style="font-size: 0.85rem; color: var(--text-muted);">Complete loan transactions to unlock your first badge!</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Stats & Activity Charts -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 30px;" class="profile-stats-grid">
        <!-- Quick Numbers -->
        <div class="glass-card" style="padding: 24px; border-radius: var(--radius-lg); display: flex; flex-direction: column; justify-content: space-around;">
            <h3 style="font-size: 1.15rem; margin-bottom: 16px;">Campus Activity Overview</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; text-align: center;">
                <div style="background: var(--bg-surface); padding: 18px; border-radius: var(--radius-md); border: 1px solid var(--border-glass);">
                    <div style="font-size: 2rem; font-weight: 900; color: #10b981;"><?= $stats['lent_count'] ?></div>
                    <div style="font-size: 0.82rem; color: var(--text-muted); font-weight: 600;">Items Lent & Returned</div>
                </div>
                <div style="background: var(--bg-surface); padding: 18px; border-radius: var(--radius-md); border: 1px solid var(--border-glass);">
                    <div style="font-size: 2rem; font-weight: 900; color: var(--primary);"><?= $stats['borrowed_count'] ?></div>
                    <div style="font-size: 0.82rem; color: var(--text-muted); font-weight: 600;">Items Borrowed Safely</div>
                </div>
            </div>
        </div>

        <!-- Chart.js Activity Visualization -->
        <div class="glass-card" style="padding: 24px; border-radius: var(--radius-lg);">
            <h3 style="font-size: 1.15rem; margin-bottom: 12px;">Exchange Ratio</h3>
            <div style="position: relative; height: 180px;">
                <canvas id="profile-chart"></canvas>
            </div>
        </div>
    </div>

    <!-- Reviews Received Section -->
    <div class="glass-card" style="padding: 30px; border-radius: var(--radius-xl); margin-bottom: 30px;">
        <h2 style="font-size: 1.35rem; margin-bottom: 16px;">Peer Reviews & Feedback (<?= count($reviews) ?>)</h2>
        <?php if (!empty($reviews)): ?>
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <?php foreach ($reviews as $rev): ?>
                    <div style="padding: 16px; background: var(--bg-surface); border: 1px solid var(--border-glass); border-radius: var(--radius-md);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <span style="font-weight: 700; font-size: 0.95rem;"><?= e($rev['reviewer_name']) ?></span>
                            <span style="color: #f59e0b; font-size: 0.9rem;"><?= str_repeat('⭐', $rev['stars']) ?></span>
                        </div>
                        <?php if (!empty($rev['comment'])): ?>
                            <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.5; margin: 0 0 6px 0;">
                                "<?= e($rev['comment']) ?>"
                            </p>
                        <?php endif; ?>
                        <div style="font-size: 0.75rem; color: var(--text-light);">
                            For <strong><?= e($rev['item_name']) ?></strong> • <?= format_time_ago($rev['created_at']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; color: var(--text-muted); font-size: 0.9rem; padding: 20px;">
                No reviews recorded yet. Complete an item exchange to receive ratings.
            </div>
        <?php endif; ?>
    </div>

    <!-- Edit Profile & Settings (Only shown for own profile) -->
    <?php if ($isOwnProfile): ?>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;" class="profile-settings-grid">
            
            <!-- Edit Profile Form -->
            <div class="glass-card" style="padding: 28px; border-radius: var(--radius-lg);">
                <h3 style="font-size: 1.2rem; margin-bottom: 16px;">Edit Campus Profile</h3>
                <form method="POST" action="<?= url('/profile/update') ?>">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" value="<?= e($user['full_name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="department" class="form-label">Department</label>
                        <input type="text" id="department" name="department" class="form-control" value="<?= e($user['department']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone" class="form-label">Phone / WhatsApp</label>
                        <input type="tel" id="phone" name="phone" class="form-control" value="<?= e($user['phone']) ?>" required>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm" style="width: 100%;">Save Changes</button>
                </form>
            </div>

            <!-- Change Password Form -->
            <div class="glass-card" style="padding: 28px; border-radius: var(--radius-lg);">
                <h3 style="font-size: 1.2rem; margin-bottom: 16px;">Change Password</h3>
                <form method="POST" action="<?= url('/profile/password') ?>">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" id="current_password" name="current_password" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" minlength="6" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" minlength="6" required>
                    </div>

                    <button type="submit" class="btn btn-outline btn-sm" style="width: 100%;">Update Password</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('profile-chart');
    if (ctx) {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Items Lent', 'Items Borrowed'],
                datasets: [{
                    data: [<?= max(1, $stats['lent_count']) ?>, <?= max(1, $stats['borrowed_count']) ?>],
                    backgroundColor: ['#10b981', '#4f46e5'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }
});
</script>
