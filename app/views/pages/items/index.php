<div class="items-page">
    <!-- Header -->
    <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-end; gap: 20px; margin-bottom: 24px;">
        <div>
            <div style="display: inline-flex; align-items: center; gap: 6px; color: var(--primary); font-weight: 800; font-size: 0.85rem; text-transform: uppercase; margin-bottom: 6px;">
                📦 Peer Catalog
            </div>
            <h1 style="font-size: 2.2rem; margin-bottom: 6px;">Available Items for Lending</h1>
            <p style="color: var(--text-muted); font-size: 0.95rem;">
                Browse equipment, stationery, and tools students at <strong><?= e($campusName) ?></strong> are ready to lend
            </p>
        </div>

        <a href="<?= url('/items/create') ?>" class="btn btn-primary">
            <span>➕</span> List Your Item for Lending
        </a>
    </div>

    <!-- Filters & Search Toolbar -->
    <div class="glass-card" style="padding: 20px; border-radius: var(--radius-lg); margin-bottom: 30px;">
        <form method="GET" action="<?= url('/items') ?>" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 14px; align-items: center;" class="filter-grid">
            <!-- Search -->
            <div style="position: relative;">
                <input 
                    type="text" 
                    name="search" 
                    class="form-control" 
                    placeholder="Search by title, location, or notes..." 
                    value="<?= e($filters['search']) ?>"
                    style="padding-left: 38px;"
                >
                <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted);">🔍</span>
            </div>

            <!-- Category -->
            <select name="category" class="form-control" onchange="this.form.submit()">
                <option value="all">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= e($cat['slug']) ?>" <?= $filters['category'] === $cat['slug'] ? 'selected' : '' ?>>
                        <?= e($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Condition -->
            <select name="condition" class="form-control" onchange="this.form.submit()">
                <option value="all">All Conditions</option>
                <option value="Like New" <?= $filters['condition'] === 'Like New' ? 'selected' : '' ?>>Like New</option>
                <option value="Good" <?= $filters['condition'] === 'Good' ? 'selected' : '' ?>>Good</option>
                <option value="Fair" <?= $filters['condition'] === 'Fair' ? 'selected' : '' ?>>Fair</option>
                <option value="Usable" <?= $filters['condition'] === 'Usable' ? 'selected' : '' ?>>Usable</option>
            </select>

            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary btn-sm" style="padding: 10px 16px;">Filter</button>
                <a href="<?= url('/items') ?>" class="btn btn-outline btn-sm" style="padding: 10px 12px;" title="Reset filters">✕</a>
            </div>
        </form>
    </div>

    <!-- Items Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px; margin-bottom: 40px;">
        <?php if (!empty($items)): ?>
            <?php foreach ($items as $item): ?>
                <div class="glass-card" style="display: flex; flex-direction: column; justify-content: space-between; overflow: hidden; border-radius: var(--radius-lg);">
                    
                    <!-- Item Photo / Thumbnail Placeholder -->
                    <div style="height: 160px; background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(6, 182, 212, 0.1) 100%); display: flex; align-items: center; justify-content: center; position: relative; border-bottom: 1px solid var(--border-glass);">
                        <?php if (!empty($item['photo']) && file_exists(__DIR__ . '/../../../../public/uploads/items/' . $item['photo'])): ?>
                            <img src="<?= asset('uploads/items/' . $item['photo']) ?>" alt="<?= e($item['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <div style="font-size: 3.5rem; opacity: 0.8;">
                                <i data-lucide="<?= e($item['category_icon']) ?>" style="width: 50px; height: 50px; color: var(--primary);"></i>
                            </div>
                        <?php endif; ?>

                        <span style="position: absolute; top: 12px; right: 12px; background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(4px); color: #fff; font-size: 0.72rem; font-weight: 700; padding: 4px 8px; border-radius: var(--radius-sm);">
                            <?= e($item['item_condition']) ?>
                        </span>
                    </div>

                    <!-- Item Body -->
                    <div style="padding: 20px; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 4px;">
                                <?= e($item['category_name']) ?>
                            </div>

                            <h2 style="font-size: 1.15rem; margin-bottom: 8px;">
                                <a href="<?= url('/items/view?id=' . $item['id']) ?>" style="color: var(--text-main);">
                                    <?= e($item['title']) ?>
                                </a>
                            </h2>

                            <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                                <span>📍</span>
                                <span><?= e($item['pickup_location']) ?></span>
                            </div>

                            <?php if (!empty($item['description'])): ?>
                                <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 16px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    <?= e($item['description']) ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <!-- Card Footer -->
                        <div style="border-top: 1px solid var(--border-glass); padding-top: 14px; display: flex; justify-content: space-between; align-items: center;">
                            <div style="font-size: 0.82rem;">
                                <div style="font-weight: 700; color: var(--text-main);"><?= e($item['owner_name']) ?></div>
                                <div style="color: #f59e0b; font-size: 0.75rem;">⭐ <?= number_format($item['trust_score'], 1) ?></div>
                            </div>

                            <a href="<?= url('/items/view?id=' . $item['id']) ?>" class="btn btn-outline btn-sm">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="glass-card" style="padding: 60px 20px; text-align: center; grid-column: 1 / -1;">
                <div style="font-size: 3rem; margin-bottom: 12px;">📦</div>
                <h2 style="font-size: 1.4rem; margin-bottom: 8px;">No Items Found in Catalog</h2>
                <p style="color: var(--text-muted); max-width: 450px; margin: 0 auto 20px auto;">
                    No listings match your search criteria. Got spare tools or textbooks? List them to help fellow students!
                </p>
                <a href="<?= url('/items/create') ?>" class="btn btn-primary">List an Item Now</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
        <div style="display: flex; justify-content: center; gap: 8px; margin-bottom: 40px;">
            <?php for ($p = 1; $p <= $pages; $p++): ?>
                <a href="<?= url('/items?page=' . $p . '&category=' . urlencode($filters['category']) . '&condition=' . urlencode($filters['condition']) . '&search=' . urlencode($filters['search'])) ?>" class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-outline' ?>">
                    <?= $p ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>
