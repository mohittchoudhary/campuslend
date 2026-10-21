<div class="admin-reports-page" style="margin: 20px auto 60px auto;">

    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 28px;">
        <div>
            <a href="<?= url('/admin/dashboard') ?>" style="color: var(--text-muted); font-size: 0.88rem; font-weight: 600;">← Back to Command Center</a>
            <h1 style="font-size: 2.2rem; margin: 6px 0 0 0;">Safety & Abuse Reports</h1>
            <p style="color: var(--text-muted); margin-top: 4px; font-size: 0.95rem;">Review reports submitted by students for item damage, no-shows, or harassment</p>
        </div>
    </div>

    <div class="glass-card" style="padding: 24px; border-radius: var(--radius-xl); overflow-x: auto;">
        <?php if (!empty($reports)): ?>
            <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-glass); color: var(--text-muted); text-transform: uppercase; font-size: 0.78rem;">
                        <th style="padding: 12px 14px;">Date</th>
                        <th style="padding: 12px 14px;">Reported Student</th>
                        <th style="padding: 12px 14px;">Reporter</th>
                        <th style="padding: 12px 14px;">Reason & Details</th>
                        <th style="padding: 12px 14px; text-align: center;">Status</th>
                        <th style="padding: 12px 14px; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $r): ?>
                        <tr style="border-bottom: 1px solid var(--border-glass);">
                            <td style="padding: 14px; color: var(--text-muted); font-size: 0.82rem;">
                                <?= e($r['created_at']) ?>
                            </td>

                            <td style="padding: 14px;">
                                <div style="font-weight: 700; color: #ef4444;"><?= e($r['target_name']) ?></div>
                                <div style="font-size: 0.78rem; color: var(--text-muted);"><?= e($r['target_email']) ?></div>
                                <?php if (!empty($r['target_banned'])): ?>
                                    <span style="font-size: 0.7rem; background: rgba(239, 68, 68, 0.15); color: #ef4444; padding: 2px 6px; border-radius: 4px; font-weight: 700;">Currently Banned</span>
                                <?php endif; ?>
                            </td>

                            <td style="padding: 14px;">
                                <div style="font-weight: 600;"><?= e($r['reporter_name']) ?></div>
                                <div style="font-size: 0.78rem; color: var(--text-muted);"><?= e($r['reporter_email']) ?></div>
                            </td>

                            <td style="padding: 14px; max-width: 320px;">
                                <div style="font-weight: 700; color: var(--text-main);"><?= e($r['reason']) ?></div>
                                <?php if (!empty($r['details'])): ?>
                                    <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 4px;"><?= e($r['details']) ?></div>
                                <?php endif; ?>
                            </td>

                            <td style="padding: 14px; text-align: center;">
                                <?php if ($r['status'] === 'pending'): ?>
                                    <span class="badge badge-high">Pending</span>
                                <?php elseif ($r['status'] === 'action_taken'): ?>
                                    <span class="badge badge-critical">Action Taken</span>
                                <?php else: ?>
                                    <span class="badge badge-normal"><?= ucfirst(e($r['status'])) ?></span>
                                <?php endif; ?>
                            </td>

                            <td style="padding: 14px; text-align: right;">
                                <form action="<?= url('/admin/reports/update') ?>" method="POST" style="display: flex; gap: 6px; justify-content: flex-end;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="report_id" value="<?= (int)$r['id'] ?>">
                                    <select name="status" class="form-control" style="font-size: 0.78rem; padding: 4px 8px; width: auto;">
                                        <option value="pending" <?= $r['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="reviewed" <?= $r['status'] === 'reviewed' ? 'selected' : '' ?>>Reviewed</option>
                                        <option value="action_taken" <?= $r['status'] === 'action_taken' ? 'selected' : '' ?>>Action Taken</option>
                                        <option value="dismissed" <?= $r['status'] === 'dismissed' ? 'selected' : '' ?>>Dismissed</option>
                                    </select>
                                    <button type="submit" class="btn btn-outline btn-sm" style="font-size: 0.78rem; padding: 4px 8px;">Save</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                <div style="font-size: 2.5rem; margin-bottom: 10px;">🛡️</div>
                <h3>Zero Abuse Reports</h3>
                <p>No policy violations or misconduct reports have been filed.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
