<div style="max-width: 800px; margin: 20px auto 50px auto;">
    
    <div style="margin-bottom: 20px;">
        <a href="<?= url('/items') ?>" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600; color: var(--text-muted); font-size: 0.9rem;">
            ← Back to Lend Catalog
        </a>
    </div>

    <div class="glass-card" style="padding: 36px; border-radius: var(--radius-xl); overflow: hidden;">
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 36px;" class="item-view-grid">
            
            <!-- Photo Column -->
            <div>
                <div style="height: 300px; border-radius: var(--radius-lg); background: linear-gradient(135deg, rgba(79, 70, 229, 0.08) 0%, rgba(6, 182, 212, 0.08) 100%); display: flex; align-items: center; justify-content: center; overflow: hidden; border: 1px solid var(--border-glass);">
                    <?php if (!empty($item['photo']) && file_exists(__DIR__ . '/../../../../public/uploads/items/' . $item['photo'])): ?>
                        <img src="<?= asset('uploads/items/' . $item['photo']) ?>" alt="<?= e($item['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <div style="font-size: 5rem; opacity: 0.8;">
                            <i data-lucide="<?= e($item['category_icon']) ?>" style="width: 80px; height: 80px; color: var(--primary);"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="margin-top: 16px; display: flex; justify-content: space-between; align-items: center;">
                    <span class="badge" style="background: rgba(79, 70, 229, 0.1); color: var(--primary);">
                        Condition: <?= e($item['item_condition']) ?>
                    </span>
                    <span style="font-size: 0.82rem; color: var(--text-muted); font-weight: 600;">
                        ⏱️ Max: <?= (int)$item['max_duration_hours'] ?> Hours
                    </span>
                </div>
            </div>

            <!-- Details Column -->
            <div style="display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="font-size: 0.8rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 6px;">
                        <?= e($item['category_name']) ?>
                    </div>

                    <h1 style="font-size: 1.85rem; margin-bottom: 12px; line-height: 1.25;"><?= e($item['title']) ?></h1>

                    <div style="background: var(--bg-surface); border: 1px solid var(--border-glass); border-radius: var(--radius-md); padding: 14px; margin-bottom: 16px;">
                        <div style="font-size: 0.78rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 4px;">Pickup Location</div>
                        <div style="font-weight: 700; font-size: 0.95rem;">📍 <?= e($item['pickup_location']) ?></div>
                    </div>

                    <?php if (!empty($item['description'])): ?>
                        <div style="margin-bottom: 20px;">
                            <div style="font-size: 0.8rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 4px;">Description</div>
                            <p style="font-size: 0.92rem; color: var(--text-main); line-height: 1.6;">
                                <?= nl2br(e($item['description'])) ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Owner Profile & Actions -->
                <div style="border-top: 1px solid var(--border-glass); padding-top: 20px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.95rem;">
                                <?= strtoupper(substr($item['owner_name'], 0, 1)) ?>
                            </div>
                            <div>
                                <div style="font-weight: 700; font-size: 0.95rem;"><?= e($item['owner_name']) ?></div>
                                <div style="font-size: 0.78rem; color: var(--text-muted);"><?= e($item['department']) ?></div>
                            </div>
                        </div>

                        <div style="text-align: right;">
                            <div style="font-size: 1.1rem; font-weight: 800; color: #f59e0b;">⭐ <?= number_format($item['trust_score'], 1) ?></div>
                            <div style="font-size: 0.72rem; color: var(--text-muted);">Trust Score</div>
                        </div>
                    </div>

                    <?php if ($isOwner): ?>
                        <form method="POST" action="<?= url('/items/toggle-pause') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                            <button type="submit" class="btn btn-outline" style="width: 100%;">
                                <?= (int)$item['is_paused'] === 1 ? '▶️ Resume Lending Listing' : '⏸️ Pause Listing (Temporarily Hide)' ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <a href="<?= url('/requests/create?category=' . $item['category_id'] . '&item=' . urlencode($item['title'])) ?>" class="btn btn-primary" style="width: 100%; padding: 12px;">
                            <span>📢 Request to Borrow This Item</span> →
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
