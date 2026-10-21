<div class="admin-users-page" style="margin: 20px auto 60px auto;">

    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 28px;">
        <div>
            <a href="<?= url('/admin/dashboard') ?>" style="color: var(--text-muted); font-size: 0.88rem; font-weight: 600;">← Back to Command Center</a>
            <h1 style="font-size: 2.2rem; margin: 6px 0 0 0;">Student Accounts & Moderation</h1>
            <p style="color: var(--text-muted); margin-top: 4px; font-size: 0.95rem;">Manage campus student access, suspensions, and trust standing</p>
        </div>
    </div>

    <div class="glass-card" style="padding: 24px; border-radius: var(--radius-xl); overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-glass); color: var(--text-muted); text-transform: uppercase; font-size: 0.78rem; letter-spacing: 0.5px;">
                    <th style="padding: 12px 14px;">Student</th>
                    <th style="padding: 12px 14px;">Enrollment / Dept</th>
                    <th style="padding: 12px 14px; text-align: center;">Trust Score</th>
                    <th style="padding: 12px 14px; text-align: center;">Activity</th>
                    <th style="padding: 12px 14px; text-align: center;">Status</th>
                    <th style="padding: 12px 14px; text-align: right;">Moderation Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr style="border-bottom: 1px solid var(--border-glass);">
                        <td style="padding: 14px;">
                            <div style="font-weight: 700; font-size: 0.95rem;"><?= e($u['full_name']) ?></div>
                            <div style="font-size: 0.8rem; color: var(--text-muted);"><?= e($u['email']) ?></div>
                            <?php if (!empty($u['is_admin'])): ?>
                                <span style="font-size: 0.72rem; background: #6366f1; color: #fff; padding: 2px 6px; border-radius: 4px; font-weight: 700;">ADMIN</span>
                            <?php endif; ?>
                        </td>

                        <td style="padding: 14px;">
                            <div style="font-weight: 600;"><?= e($u['enrollment_no'] ?? 'N/A') ?></div>
                            <div style="font-size: 0.8rem; color: var(--text-muted);"><?= e($u['department'] ?? 'UTU Campus') ?></div>
                        </td>

                        <td style="padding: 14px; text-align: center;">
                            <span style="font-weight: 700; color: #f59e0b;">⭐ <?= number_format($u['trust_score'] ?? 5.0, 1) ?></span>
                        </td>

                        <td style="padding: 14px; text-align: center; font-size: 0.82rem; color: var(--text-muted);">
                            <div><strong><?= (int)$u['total_requests'] ?></strong> requests</div>
                            <div><strong><?= (int)$u['successful_lends'] ?></strong> successful lends</div>
                        </td>

                        <td style="padding: 14px; text-align: center;">
                            <?php if (!empty($u['is_banned'])): ?>
                                <span style="background: rgba(239, 68, 68, 0.15); color: #ef4444; padding: 4px 10px; border-radius: var(--radius-full); font-weight: 700; font-size: 0.78rem;">Suspended</span>
                            <?php else: ?>
                                <span style="background: rgba(16, 185, 129, 0.15); color: #10b981; padding: 4px 10px; border-radius: var(--radius-full); font-weight: 700; font-size: 0.78rem;">Active</span>
                            <?php endif; ?>
                        </td>

                        <td style="padding: 14px; text-align: right;">
                            <?php if (empty($u['is_admin'])): ?>
                                <?php if (empty($u['is_banned'])): ?>
                                    <form action="<?= url('/admin/users/ban') ?>" method="POST" style="display: inline-block;" onsubmit="return confirm('Suspend this student account?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                        <input type="hidden" name="reason" value="Violation of campus trust policy">
                                        <button type="submit" class="btn btn-outline btn-sm" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.4); font-size: 0.8rem;">
                                            Ban Account
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form action="<?= url('/admin/users/unban') ?>" method="POST" style="display: inline-block;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                        <button type="submit" class="btn btn-outline btn-sm" style="color: #10b981; border-color: rgba(16, 185, 129, 0.4); font-size: 0.8rem;">
                                            Restore Access
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="font-size: 0.8rem; color: var(--text-muted);">Protected</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
