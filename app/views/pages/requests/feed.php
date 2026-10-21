<div class="feed-page">
    <!-- Header -->
    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-end; gap: 20px; margin-bottom: 24px;">
        <div>
            <div style="display: inline-flex; align-items: center; gap: 6px; color: #ef4444; font-weight: 800; font-size: 0.85rem; text-transform: uppercase; margin-bottom: 6px;">
                <span style="width: 8px; height: 8px; border-radius: 50%; background: #ef4444; animation: pulse-critical 1.5s infinite;"></span>
                Live Emergency Feed
            </div>
            <h1 style="font-size: 2.2rem; margin-bottom: 6px;">Active Campus Borrow Requests</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem;">
                Students at <strong><?= e($campusName) ?></strong> needing items right now for exams, labs, and classes
            </p>
        </div>

        <div style="display: flex; gap: 12px;">
            <a href="<?= url('/requests/create?urgent=1') ?>" class="btn btn-urgent">
                <span>🚨</span> Post SOS Request
            </a>
            <a href="<?= url('/items/create') ?>" class="btn btn-primary">
                <span>➕</span> List Item
            </a>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="glass-card" style="padding: 20px; border-radius: var(--radius-lg); margin-bottom: 30px;">
        <form method="GET" action="<?= url('/feed') ?>" id="feed-filter-form" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 14px; align-items: center;" class="filter-grid">
            
            <!-- Search Input -->
            <div style="position: relative;">
                <input 
                    type="text" 
                    name="search" 
                    id="search-input"
                    class="form-control" 
                    placeholder="Search calculator, lab coat, charger, drafter..." 
                    value="<?= e($filters['search']) ?>"
                    style="padding-left: 38px;"
                >
                <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted);">🔍</span>
            </div>

            <!-- Category Filter -->
            <select name="category" class="form-control" onchange="this.form.submit()">
                <option value="all">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= e($cat['slug']) ?>" <?= $filters['category'] === $cat['slug'] ? 'selected' : '' ?>>
                        <?= e($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Urgency Filter -->
            <select name="urgency" class="form-control" onchange="this.form.submit()">
                <option value="all">All Urgency</option>
                <option value="Critical" <?= $filters['urgency'] === 'Critical' ? 'selected' : '' ?>>🚨 Critical Only</option>
                <option value="High" <?= $filters['urgency'] === 'High' ? 'selected' : '' ?>>⚡ High Urgency</option>
                <option value="Normal" <?= $filters['urgency'] === 'Normal' ? 'selected' : '' ?>>Normal Urgency</option>
            </select>

            <!-- Block Filter -->
            <select name="block" class="form-control" onchange="this.form.submit()">
                <option value="all">All Campus Blocks</option>
                <?php foreach ($campusBlocks as $blk): ?>
                    <option value="<?= e($blk) ?>" <?= $filters['block'] === $blk ? 'selected' : '' ?>>
                        <?= e($blk) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Clear / Filter Buttons -->
            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary btn-sm" style="padding: 10px 16px;">Filter</button>
                <a href="<?= url('/feed') ?>" class="btn btn-outline btn-sm" style="padding: 10px 12px;" title="Reset filters">✕</a>
            </div>
        </form>
    </div>

    <!-- Requests Cards Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 24px; margin-bottom: 40px;">
        <?php if (!empty($requests)): ?>
            <?php foreach ($requests as $req): ?>
                <?php 
                    $targetSec = strtotime($req['needed_by']);
                    $remaining = format_time_remaining($req['needed_by']);
                    $isCritical = $req['urgency'] === 'Critical';
                ?>
                <div class="glass-card" style="padding: 24px; display: flex; flex-direction: column; justify-content: space-between; position: relative; border-left: 4px solid <?= $isCritical ? 'var(--urgent-red)' : ($req['urgency'] === 'High' ? '#f59e0b' : '#10b981') ?>;">
                    
                    <div>
                        <!-- Urgency & Countdown Header -->
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <span class="badge badge-<?= strtolower($req['urgency']) ?>">
                                <?= $isCritical ? '🚨 Critical Urgency' : ($req['urgency'] === 'High' ? '⚡ High Urgency' : 'Normal') ?>
                            </span>
                            <span class="countdown-badge" data-countdown="<?= $targetSec ?>" style="font-size: 0.82rem; font-weight: 700; color: <?= $isCritical ? '#ef4444' : '#d97706' ?>;">
                                <?= e($remaining['text']) ?>
                            </span>
                        </div>

                        <!-- Item Title -->
                        <h2 style="font-size: 1.25rem; margin-bottom: 8px;">
                            <a href="<?= url('/requests/view?id=' . $req['id']) ?>" style="color: var(--text-main);">
                                <?= e($req['item_name']) ?>
                            </a>
                        </h2>

                        <!-- Location & Category -->
                        <div style="display: flex; flex-wrap: wrap; gap: 8px; font-size: 0.82rem; margin-bottom: 12px;">
                            <span style="background: var(--bg-surface); border: 1px solid var(--border-glass); padding: 4px 10px; border-radius: var(--radius-sm); font-weight: 600;">
                                📍 <?= e($req['campus_block']) ?> (<?= e($req['room_location']) ?>)
                            </span>
                            <span style="background: var(--bg-surface); border: 1px solid var(--border-glass); padding: 4px 10px; border-radius: var(--radius-sm); color: var(--text-muted);">
                                🏷️ <?= e($req['category_name']) ?>
                            </span>
                        </div>

                        <!-- Student Note -->
                        <?php if (!empty($req['note'])): ?>
                            <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 18px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                "<?= e($req['note']) ?>"
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Footer: Student Profile & Action Button -->
                    <div style="border-top: 1px solid var(--border-glass); padding-top: 16px; display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 34px; height: 34px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem; border: 1px solid var(--border-glass);">
                                <?= strtoupper(substr($req['student_name'], 0, 1)) ?>
                            </div>
                            <div>
                                <div style="font-size: 0.88rem; font-weight: 700; line-height: 1.2;"><?= e($req['student_name']) ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">⭐ <?= number_format($req['trust_score'], 1) ?> • <?= e($req['department']) ?></div>
                            </div>
                        </div>

                        <a href="<?= url('/requests/view?id=' . $req['id']) ?>" class="btn btn-primary btn-sm">
                            <span>🤝</span> I Can Lend This
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="glass-card" style="padding: 60px 20px; text-align: center; grid-column: 1 / -1;">
                <div style="font-size: 3rem; margin-bottom: 12px;">🔍</div>
                <h2 style="font-size: 1.4rem; margin-bottom: 8px;">No Emergency Requests Found</h2>
                <p style="color: var(--text-muted); max-width: 450px; margin: 0 auto 20px auto;">
                    There are no open requests matching your active filters. Try resetting the filters or post a request if you need something.
                </p>
                <div style="display: flex; justify-content: center; gap: 12px;">
                    <a href="<?= url('/feed') ?>" class="btn btn-outline">Reset Filters</a>
                    <a href="<?= url('/requests/create') ?>" class="btn btn-urgent">Post Request</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination Controls -->
    <?php if ($pages > 1): ?>
        <div style="display: flex; justify-content: center; gap: 8px; margin-bottom: 40px;">
            <?php for ($p = 1; $p <= $pages; $p++): ?>
                <a href="<?= url('/feed?page=' . $p . '&category=' . urlencode($filters['category']) . '&urgency=' . urlencode($filters['urgency']) . '&block=' . urlencode($filters['block']) . '&search=' . urlencode($filters['search'])) ?>" class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-outline' ?>">
                    <?= $p ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>
