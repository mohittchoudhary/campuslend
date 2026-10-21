<div style="max-width: 760px; margin: 20px auto 50px auto;">
    
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h1 style="font-size: 2rem; margin-bottom: 4px;">Notifications</h1>
            <p style="color: var(--text-muted); font-size: 0.92rem;">
                Updates on your emergency requests, offers, handovers, and reviews
            </p>
        </div>

        <?php if (!empty($notifications)): ?>
            <form method="POST" action="<?= url('/notifications/read-all') ?>" style="margin: 0;">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline btn-sm">
                    Mark All as Read
                </button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Notifications List -->
    <?php if (!empty($notifications)): ?>
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php foreach ($notifications as $n): ?>
                <?php
                    $isUnread = (int)$n['is_read'] === 0;
                    $icon = match($n['type']) {
                        'offer_received' => '🤝',
                        'offer_accepted' => '🎉',
                        'handover' => '📦',
                        'return_due' => '⏰',
                        'rating_received' => '⭐',
                        'badge_earned' => '🏆',
                        'chat_message' => '💬',
                        default => '🔔',
                    };
                ?>
                <div class="glass-card" style="padding: 18px 22px; border-radius: var(--radius-md); display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; border-left: 4px solid <?= $isUnread ? 'var(--primary)' : 'transparent' ?>; background: <?= $isUnread ? 'var(--bg-card-hover)' : 'var(--bg-card)' ?>;">
                    <div style="display: flex; gap: 14px; align-items: flex-start;">
                        <div style="font-size: 1.6rem; line-height: 1; margin-top: 2px;">
                            <?= $icon ?>
                        </div>
                        <div>
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                <h3 style="font-size: 1.05rem; font-weight: <?= $isUnread ? '700' : '600' ?>; color: var(--text-main); margin: 0;">
                                    <?= e($n['title']) ?>
                                </h3>
                                <?php if ($isUnread): ?>
                                    <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--primary); display: inline-block;"></span>
                                <?php endif; ?>
                            </div>
                            <p style="font-size: 0.9rem; color: var(--text-muted); line-height: 1.5; margin: 0 0 8px 0;">
                                <?= e($n['message']) ?>
                            </p>
                            <div style="font-size: 0.78rem; color: var(--text-light);">
                                <?= format_time_ago($n['created_at']) ?>
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 10px; flex-shrink: 0;">
                        <?php if (!empty($n['link'])): ?>
                            <a href="<?= url($n['link']) ?>" class="btn btn-outline btn-sm" style="font-size: 0.8rem; padding: 6px 12px;">
                                View →
                            </a>
                        <?php endif; ?>

                        <?php if ($isUnread): ?>
                            <form method="POST" action="<?= url('/notifications/read') ?>" style="margin: 0;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $n['id'] ?>">
                                <button type="submit" class="btn btn-sm" style="background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 0.8rem;" title="Mark as read">
                                    ✓
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="glass-card" style="padding: 60px 20px; text-align: center; border-radius: var(--radius-xl);">
            <div style="font-size: 3rem; margin-bottom: 12px;">📭</div>
            <h2 style="font-size: 1.3rem; margin-bottom: 6px;">No Notifications Yet</h2>
            <p style="color: var(--text-muted); font-size: 0.9rem;">
                When you post requests or offer to help peers, updates will appear here in real-time.
            </p>
        </div>
    <?php endif; ?>
</div>
