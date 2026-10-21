<div class="admin-categories-page" style="margin: 20px auto 60px auto;">

    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 28px;">
        <div>
            <a href="<?= url('/admin/dashboard') ?>" style="color: var(--text-muted); font-size: 0.88rem; font-weight: 600;">← Back to Command Center</a>
            <h1 style="font-size: 2.2rem; margin: 6px 0 0 0;">Item Categories Directory</h1>
            <p style="color: var(--text-muted); margin-top: 4px; font-size: 0.95rem;">Organize equipment and emergency supplies for UTU students</p>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px;" class="admin-categories-grid">
        
        <!-- Add New Category Form -->
        <div class="glass-card" style="padding: 24px; border-radius: var(--radius-xl); height: fit-content;">
            <h3 style="font-size: 1.15rem; margin-bottom: 16px;">➕ Add Category</h3>
            
            <form action="<?= url('/admin/categories/create') ?>" method="POST">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label class="form-label" for="name">Category Name</label>
                    <input type="text" class="form-control" id="name" name="name" required placeholder="e.g. Soldering Kits">
                </div>

                <div class="form-group">
                    <label class="form-label" for="icon">Emoji Icon</label>
                    <input type="text" class="form-control" id="icon" name="icon" required placeholder="e.g. 🔌" value="📦">
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Short Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3" placeholder="Explain what items fit here..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Add to Directory</button>
            </form>
        </div>

        <!-- Categories Table -->
        <div class="glass-card" style="padding: 24px; border-radius: var(--radius-xl); overflow-x: auto;">
            <h3 style="font-size: 1.15rem; margin-bottom: 16px;">Active Categories (<?= count($categories) ?>)</h3>

            <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-glass); color: var(--text-muted); text-transform: uppercase; font-size: 0.78rem;">
                        <th style="padding: 12px 14px;">Icon & Name</th>
                        <th style="padding: 12px 14px;">Slug</th>
                        <th style="padding: 12px 14px; text-align: center;">Catalog Items</th>
                        <th style="padding: 12px 14px; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <tr style="border-bottom: 1px solid var(--border-glass);">
                            <td style="padding: 14px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span style="font-size: 1.4rem;"><?= e($cat['icon']) ?></span>
                                    <div>
                                        <div style="font-weight: 700;"><?= e($cat['name']) ?></div>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?= e($cat['description'] ?? '') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 14px; color: var(--text-muted); font-family: monospace; font-size: 0.82rem;">
                                <?= e($cat['slug']) ?>
                            </td>
                            <td style="padding: 14px; text-align: center; font-weight: 700;">
                                <?= (int)($cat['item_count'] ?? 0) ?>
                            </td>
                            <td style="padding: 14px; text-align: right;">
                                <form action="<?= url('/admin/categories/delete') ?>" method="POST" style="display: inline-block;" onsubmit="return confirm('Delete category <?= e($cat['name']) ?>?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
                                    <button type="submit" class="btn btn-outline btn-sm" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.4); padding: 4px 8px; font-size: 0.75rem;">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
