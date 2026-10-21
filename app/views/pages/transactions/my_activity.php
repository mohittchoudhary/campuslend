<div class="my-activity-page">
    
    <div style="margin-bottom: 28px;">
        <h1 style="font-size: 2.2rem; margin-bottom: 6px;">My Campus Activity</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem;">
            Track your emergency requests, active borrow loans, and listed equipment
        </p>
    </div>

    <!-- 1. Active Transactions Section -->
    <div style="margin-bottom: 40px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h2 style="font-size: 1.4rem;">Borrow & Lend Transactions (<?= count($transactions) ?>)</h2>
        </div>

        <?php if (!empty($transactions)): ?>
            <div style="display: flex; flex-direction: column; gap: 14px;">
                <?php foreach ($transactions as $t): ?>
                    <div class="glass-card" style="padding: 20px; border-radius: var(--radius-lg); display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px;">
                        <div style="display: flex; align-items: center; gap: 14px;">
                            <div style="width: 44px; height: 44px; border-radius: var(--radius-md); background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.3rem;">
                                <i data-lucide="<?= e($t['category_icon']) ?>" style="width: 24px; height: 24px;"></i>
                            </div>
                            <div>
                                <div style="font-weight: 700; font-size: 1.1rem;"><?= e($t['item_name']) ?></div>
                                <div style="font-size: 0.82rem; color: var(--text-muted);">
                                    Borrower: <strong><?= e($t['borrower_name']) ?></strong> • Lender: <strong><?= e($t['lender_name']) ?></strong>
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; gap: 14px;">
                            <span class="badge" style="background: <?= $t['status'] === 'returned' ? 'rgba(16, 185, 129, 0.15)' : ($t['status'] === 'overdue' ? 'rgba(239, 68, 68, 0.15)' : 'rgba(79, 70, 229, 0.1)') ?>; color: <?= $t['status'] === 'returned' ? '#059669' : ($t['status'] === 'overdue' ? '#dc2626' : '#4f46e5') ?>;">
                                <?= ucfirst(str_replace('_', ' ', $t['status'])) ?>
                            </span>

                            <a href="<?= url('/transactions?id=' . $t['id']) ?>" class="btn btn-outline btn-sm">
                                View Details & Status →
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="glass-card" style="padding: 30px; text-align: center; border-radius: var(--radius-lg);">
                <div style="color: var(--text-muted);">You have no active or past borrow transactions yet.</div>
            </div>
        <?php endif; ?>
    </div>

    <!-- 2. My Emergency Requests Section -->
    <div style="margin-bottom: 40px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h2 style="font-size: 1.4rem;">My Emergency Requests (<?= count($requests) ?>)</h2>
            <a href="<?= url('/requests/create') ?>" class="btn btn-urgent btn-sm">➕ Post New Request</a>
        </div>

        <?php if (!empty($requests)): ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px;">
                <?php foreach ($requests as $r): ?>
                    <div class="glass-card" style="padding: 20px; border-radius: var(--radius-md);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <span class="badge badge-<?= strtolower($r['urgency']) ?>"><?= e($r['urgency']) ?></span>
                            <span style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: var(--text-muted);"><?= e($r['status']) ?></span>
                        </div>
                        <h3 style="font-size: 1.1rem; margin-bottom: 6px;">
                            <a href="<?= url('/requests/view?id=' . $r['id']) ?>" style="color: var(--text-main);"><?= e($r['item_name']) ?></a>
                        </h3>
                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 12px;">
                            📍 <?= e($r['campus_block']) ?> • <?= (int)$r['offer_count'] ?> Offers Received
                        </div>
                        <a href="<?= url('/requests/view?id=' . $r['id']) ?>" class="btn btn-outline btn-sm" style="width: 100%;">
                            Manage & View Offers
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="glass-card" style="padding: 30px; text-align: center; border-radius: var(--radius-lg);">
                <div style="color: var(--text-muted);">You haven't posted any emergency requests.</div>
            </div>
        <?php endif; ?>
    </div>

    <!-- 3. My Listed Items for Lending -->
    <div style="margin-bottom: 40px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h2 style="font-size: 1.4rem;">My Listed Items (<?= count($items) ?>)</h2>
            <a href="<?= url('/items/create') ?>" class="btn btn-primary btn-sm">➕ List Another Item</a>
        </div>

        <?php if (!empty($items)): ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px;">
                <?php foreach ($items as $it): ?>
                    <div class="glass-card" style="padding: 20px; border-radius: var(--radius-md);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <span class="badge" style="background: rgba(79, 70, 229, 0.1); color: var(--primary);"><?= e($it['item_condition']) ?></span>
                            <span style="font-size: 0.75rem; font-weight: 700; color: <?= (int)$it['is_paused'] === 1 ? '#ef4444' : '#10b981' ?>;">
                                <?= (int)$it['is_paused'] === 1 ? '⏸️ Paused' : '✅ Active' ?>
                            </span>
                        </div>
                        <h3 style="font-size: 1.1rem; margin-bottom: 6px;">
                            <a href="<?= url('/items/view?id=' . $it['id']) ?>" style="color: var(--text-main);"><?= e($it['title']) ?></a>
                        </h3>
                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 12px;">
                            📍 <?= e($it['pickup_location']) ?>
                        </div>
                        <a href="<?= url('/items/view?id=' . $it['id']) ?>" class="btn btn-outline btn-sm" style="width: 100%;">
                            View / Manage Listing
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="glass-card" style="padding: 30px; text-align: center; border-radius: var(--radius-lg);">
                <div style="color: var(--text-muted);">You have not listed any items to lend yet.</div>
            </div>
        <?php endif; ?>
    </div>
</div>
