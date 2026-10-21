<div class="admin-logs-page" style="margin: 20px auto 60px auto;">

    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 28px;">
        <div>
            <a href="<?= url('/admin/dashboard') ?>" style="color: var(--text-muted); font-size: 0.88rem; font-weight: 600;">← Back to Command Center</a>
            <h1 style="font-size: 2.2rem; margin: 6px 0 0 0;">Administrator Audit Logs</h1>
            <p style="color: var(--text-muted); margin-top: 4px; font-size: 0.95rem;">Tamper-evident record of all administrative actions, bans, and adjustments</p>
        </div>
    </div>

    <div class="glass-card" style="padding: 24px; border-radius: var(--radius-xl); overflow-x: auto;">
        <?php if (!empty($logs)): ?>
            <table style="width: 100%; border-collapse: collapse; font-size: 0.88rem;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-glass); color: var(--text-muted); text-transform: uppercase; font-size: 0.78rem;">
                        <th style="padding: 12px 14px;">Timestamp</th>
                        <th style="padding: 12px 14px;">Administrator</th>
                        <th style="padding: 12px 14px;">Action</th>
                        <th style="padding: 12px 14px;">Target</th>
                        <th style="padding: 12px 14px;">Details & Justification</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr style="border-bottom: 1px solid var(--border-glass);">
                            <td style="padding: 12px 14px; color: var(--text-muted); font-size: 0.82rem; white-space: nowrap;">
                                <?= e($log['created_at']) ?>
                            </td>

                            <td style="padding: 12px 14px;">
                                <div style="font-weight: 700;"><?= e($log['admin_name']) ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);"><?= e($log['admin_email']) ?></div>
                            </td>

                            <td style="padding: 12px 14px;">
                                <span class="badge badge-normal"><?= e($log['action']) ?></span>
                            </td>

                            <td style="padding: 12px 14px; font-size: 0.82rem; color: var(--text-muted);">
                                <?= e($log['target_type'] ?? 'system') ?> #<?= e($log['target_id'] ?? '-') ?>
                            </td>

                            <td style="padding: 12px 14px; color: var(--text-main);">
                                <?= e($log['details'] ?? 'N/A') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                <p>No administrative activity recorded yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
