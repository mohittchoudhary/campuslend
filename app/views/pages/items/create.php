<?php
$old = $_SESSION['old'] ?? [];
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['old'], $_SESSION['errors']);
?>

<div style="max-width: 640px; margin: 20px auto 40px auto;">
    <div class="glass-card" style="padding: 36px; border-radius: var(--radius-xl);">
        
        <!-- Header -->
        <div style="margin-bottom: 24px;">
            <div style="display: inline-flex; align-items: center; gap: 6px; color: var(--primary); font-weight: 800; font-size: 0.85rem; text-transform: uppercase; margin-bottom: 4px;">
                📦 Peer Lending Catalog
            </div>
            <h1 style="font-size: 1.85rem; margin-bottom: 4px;">List an Item for Lending</h1>
            <p style="color: var(--text-muted); font-size: 0.92rem;">
                Help fellow students during exams and labs by sharing your spare tools.
            </p>
        </div>

        <form method="POST" action="<?= url('/items/create') ?>" enctype="multipart/form-data" id="item-form">
            <?= csrf_field() ?>

            <!-- Title -->
            <div class="form-group">
                <label for="title" class="form-label">Item Title *</label>
                <input 
                    type="text" 
                    id="title" 
                    name="title" 
                    class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>" 
                    placeholder="e.g. Casio FX-991EX ClassWiz, Omega Mini Drafter..." 
                    value="<?= e($old['title'] ?? '') ?>" 
                    required 
                    autofocus
                >
                <?php if (isset($errors['title'])): ?>
                    <div class="invalid-feedback"><?= e($errors['title']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Category & Condition Grid -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label for="category_id" class="form-label">Category *</label>
                    <select id="category_id" name="category_id" class="form-control <?= isset($errors['category_id']) ? 'is-invalid' : '' ?>" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($old['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['category_id'])): ?>
                        <div class="invalid-feedback"><?= e($errors['category_id']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="item_condition" class="form-label">Condition *</label>
                    <select id="item_condition" name="item_condition" class="form-control" required>
                        <?php foreach ($conditions as $cond): ?>
                            <option value="<?= e($cond) ?>" <?= ($old['item_condition'] ?? 'Good') === $cond ? 'selected' : '' ?>>
                                <?= e($cond) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Pickup Location & Max Duration Grid -->
            <div style="display: grid; grid-template-columns: 1.3fr 0.7fr; gap: 16px;">
                <div class="form-group">
                    <label for="pickup_location" class="form-label">Pickup Location *</label>
                    <input 
                        type="text" 
                        id="pickup_location" 
                        name="pickup_location" 
                        class="form-control <?= isset($errors['pickup_location']) ? 'is-invalid' : '' ?>" 
                        placeholder="e.g. Block B, Lab 201 (CS Dept)" 
                        value="<?= e($old['pickup_location'] ?? '') ?>" 
                        required
                    >
                    <?php if (isset($errors['pickup_location'])): ?>
                        <div class="invalid-feedback"><?= e($errors['pickup_location']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="max_duration_hours" class="form-label">Max Duration</label>
                    <select id="max_duration_hours" name="max_duration_hours" class="form-control">
                        <option value="6" <?= ($old['max_duration_hours'] ?? '') == 6 ? 'selected' : '' ?>>6 Hours</option>
                        <option value="12" <?= ($old['max_duration_hours'] ?? '') == 12 ? 'selected' : '' ?>>12 Hours</option>
                        <option value="24" <?= ($old['max_duration_hours'] ?? 24) == 24 ? 'selected' : '' ?>>24 Hours (1 Day)</option>
                        <option value="48" <?= ($old['max_duration_hours'] ?? '') == 48 ? 'selected' : '' ?>>48 Hours (2 Days)</option>
                        <option value="72" <?= ($old['max_duration_hours'] ?? '') == 72 ? 'selected' : '' ?>>72 Hours (3 Days)</option>
                    </select>
                </div>
            </div>

            <!-- Photo Upload (Secure: 2MB, JPG/PNG/WebP) -->
            <div class="form-group">
                <label for="photo" class="form-label">Item Photo (Optional)</label>
                <input 
                    type="file" 
                    id="photo" 
                    name="photo" 
                    class="form-control <?= isset($errors['photo']) ? 'is-invalid' : '' ?>" 
                    accept=".jpg,.jpeg,.png,.webp"
                >
                <div class="form-hint">Accepted formats: JPG, PNG, WebP • Maximum file size: 2 MB</div>
                <?php if (isset($errors['photo'])): ?>
                    <div class="invalid-feedback"><?= e($errors['photo']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Description -->
            <div class="form-group" style="margin-bottom: 24px;">
                <label for="description" class="form-label">Description / Instructions</label>
                <textarea 
                    id="description" 
                    name="description" 
                    class="form-control" 
                    rows="3" 
                    placeholder="e.g. Battery fresh, please handle with care. Return to Block B after your exam."
                ><?= e($old['description'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; font-size: 1rem;">
                <span>Publish Item to Catalog</span> →
            </button>
        </form>
    </div>
</div>
