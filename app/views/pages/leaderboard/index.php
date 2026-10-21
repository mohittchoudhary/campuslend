<div class="leaderboard-page" style="max-width: 900px; margin: 20px auto 50px auto;">
    
    <!-- Header -->
    <div style="text-align: center; margin-bottom: 36px;">
        <div style="display: inline-flex; align-items: center; gap: 6px; background: rgba(245, 158, 11, 0.12); color: #d97706; padding: 6px 14px; border-radius: var(--radius-full); font-weight: 800; font-size: 0.82rem; text-transform: uppercase; margin-bottom: 12px;">
            🏆 Campus Honour Roll
        </div>
        <h1 style="font-size: 2.3rem; margin-bottom: 8px;">Top Helper Hall of Fame</h1>
        <p style="color: var(--text-muted); font-size: 1rem; max-width: 600px; margin: 0 auto;">
            Celebrating generous students at <strong><?= e($campusName) ?></strong> who step up during exams and lab emergencies.
        </p>
    </div>

    <!-- Leaderboard Table Card -->
    <div class="glass-card" style="padding: 24px; border-radius: var(--radius-xl); overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 2px solid var(--border-glass); font-size: 0.8rem; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px;">
                    <th style="padding: 14px 16px;">Rank</th>
                    <th style="padding: 14px 16px;">Student Helper</th>
                    <th style="padding: 14px 16px;">Department</th>
                    <th style="padding: 14px 16px; text-align: center;">Items Lent</th>
                    <th style="padding: 14px 16px; text-align: center;">Trust Score</th>
                    <th style="padding: 14px 16px; text-align: center;">Badges</th>
                    <th style="padding: 14px 16px; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($helpers)): ?>
                    <?php $rank = 1; ?>
                    <?php foreach ($helpers as $h): ?>
                        <tr style="border-bottom: 1px solid var(--border-glass); transition: background-color 0.15s ease;">
                            <!-- Rank -->
                            <td style="padding: 16px; font-weight: 800; font-size: 1.1rem;">
                                <?php if ($rank === 1): ?>
                                    🥇 <span style="color: #f59e0b;">#1</span>
                                <?php elseif ($rank === 2): ?>
                                    🥈 <span style="color: #94a3b8;">#2</span>
                                <?php elseif ($rank === 3): ?>
                                    🥉 <span style="color: #d97706;">#3</span>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 0.95rem;">#<?= $rank ?></span>
                                <?php endif; ?>
                            </td>

                            <!-- Student Info -->
                            <td style="padding: 16px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 38px; height: 38px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.9rem;">
                                        <?= strtoupper(substr($h['full_name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 700; font-size: 0.95rem;">
                                            <a href="<?= url('/profile?id=' . $h['id']) ?>" style="color: var(--text-main);">
                                                <?= e($h['full_name']) ?>
                                            </a>
                                        </div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?= e($h['year_of_study']) ?></div>
                                    </div>
                                </div>
                            </td>

                            <!-- Department -->
                            <td style="padding: 16px; font-size: 0.88rem; color: var(--text-muted);">
                                <?= e($h['department']) ?>
                            </td>

                            <!-- Items Lent -->
                            <td style="padding: 16px; text-align: center; font-weight: 800; font-size: 1.1rem; color: #10b981;">
                                <?= (int)$h['items_lent_count'] ?>
                            </td>

                            <!-- Trust Score -->
                            <td style="padding: 16px; text-align: center;">
                                <span style="font-weight: 800; color: #f59e0b; font-size: 0.95rem;">⭐ <?= number_format($h['trust_score'], 1) ?></span>
                                <div style="font-size: 0.7rem; color: var(--text-muted);">(<?= (int)$h['total_ratings'] ?>)</div>
                            </td>

                            <!-- Badges Count -->
                            <td style="padding: 16px; text-align: center;">
                                <span class="badge" style="background: rgba(79, 70, 229, 0.1); color: var(--primary);">
                                    🏆 <?= (int)$h['badge_count'] ?>
                                </span>
                            </td>

                            <!-- Action -->
                            <td style="padding: 16px; text-align: right;">
                                <a href="<?= url('/profile?id=' . $h['id']) ?>" class="btn btn-outline btn-sm" style="font-size: 0.78rem;">
                                    View Profile
                                </a>
                            </td>
                        </tr>
                        <?php $rank++; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="padding: 40px; text-align: center; color: var(--text-muted);">
                            No leaderboard records yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
