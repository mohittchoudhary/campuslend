<?php
$old = $_SESSION['old'] ?? [];
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['old'], $_SESSION['errors']);
?>

<div style="max-width: 680px; margin: 20px auto 40px auto;">
    <div class="glass-card" style="padding: 36px; border-radius: var(--radius-xl); border-top: 4px solid <?= $isSos ? 'var(--urgent-red)' : 'var(--primary)' ?>;">
        
        <!-- Header -->
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
            <div>
                <div style="display: inline-flex; align-items: center; gap: 6px; color: <?= $isSos ? 'var(--urgent-red)' : 'var(--primary)' ?>; font-weight: 800; font-size: 0.85rem; text-transform: uppercase; margin-bottom: 4px;">
                    <?= $isSos ? '🚨 Immediate SOS Broadcast' : '📢 Emergency Request' ?>
                </div>
                <h1 style="font-size: 1.85rem; margin-bottom: 4px;">
                    <?= $isSos ? 'One-Click SOS Emergency Request' : 'What do you need to borrow?' ?>
                </h1>
                <p style="color: var(--text-muted); font-size: 0.92rem;">
                    Nearby peers in your campus block will be alerted to help you quickly.
                </p>
            </div>
            <div style="font-size: 2.4rem;">
                <?= $isSos ? '🚨' : '🆘' ?>
            </div>
        </div>

        <?php if ($isSos): ?>
            <div style="background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.25); border-radius: var(--radius-md); padding: 12px 16px; margin-bottom: 24px; font-size: 0.88rem; color: #b91c1c;">
                <strong>⚡ SOS Priority Active:</strong> Your request will feature a pulsing critical badge and priority broadcast on the campus radar.
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" action="<?= url('/requests/create') ?>" id="request-form">
            <?= csrf_field() ?>
            <?php if ($isSos): ?>
                <input type="hidden" name="is_sos" value="1">
                <input type="hidden" name="urgency" value="Critical">
            <?php endif; ?>

            <!-- Item Name -->
            <div class="form-group">
                <label for="item_name" class="form-label">Item Needed *</label>
                <input 
                    type="text" 
                    id="item_name" 
                    name="item_name" 
                    class="form-control <?= isset($errors['item_name']) ? 'is-invalid' : '' ?>" 
                    placeholder="e.g. Casio FX-991EX Calculator, Mini Drafter, Lab Coat..." 
                    value="<?= e($old['item_name'] ?? '') ?>" 
                    required 
                    autofocus
                >
                <?php if (isset($errors['item_name'])): ?>
                    <div class="invalid-feedback"><?= e($errors['item_name']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Category & Urgency Grid -->
            <div style="display: grid; grid-template-columns: 1fr <?= $isSos ? '' : '1fr' ?>; gap: 16px;">
                <div class="form-group">
                    <label for="category_id" class="form-label">Item Category *</label>
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

                <?php if (!$isSos): ?>
                    <div class="form-group">
                        <label for="urgency" class="form-label">Urgency Level *</label>
                        <select id="urgency" name="urgency" class="form-control" required>
                            <option value="Critical" <?= ($old['urgency'] ?? '') === 'Critical' ? 'selected' : '' ?>>🚨 Critical (Exam / Lab in progress)</option>
                            <option value="High" <?= ($old['urgency'] ?? '') === 'High' ? 'selected' : '' ?>>⚡ High (Needed within 1–2 hours)</option>
                            <option value="Normal" <?= ($old['urgency'] ?? 'Normal') === 'Normal' ? 'selected' : '' ?>>Normal (Needed later today)</option>
                        </select>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Campus Block & Room Location Grid -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label for="campus_block" class="form-label">Campus Block *</label>
                    <select id="campus_block" name="campus_block" class="form-control <?= isset($errors['campus_block']) ? 'is-invalid' : '' ?>" required>
                        <option value="">Select Campus Location</option>
                        <?php foreach ($campusBlocks as $blk): ?>
                            <option value="<?= e($blk) ?>" <?= ($old['campus_block'] ?? '') === $blk ? 'selected' : '' ?>>
                                <?= e($blk) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['campus_block'])): ?>
                        <div class="invalid-feedback"><?= e($errors['campus_block']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="room_location" class="form-label">Room / Floor / Spot *</label>
                    <input 
                        type="text" 
                        id="room_location" 
                        name="room_location" 
                        class="form-control <?= isset($errors['room_location']) ? 'is-invalid' : '' ?>" 
                        placeholder="e.g. Lab 201, Exam Hall 3, Ground Floor Lobby" 
                        value="<?= e($old['room_location'] ?? '') ?>" 
                        required
                    >
                    <?php if (isset($errors['room_location'])): ?>
                        <div class="invalid-feedback"><?= e($errors['room_location']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Needed In (Timeframe) -->
            <div class="form-group">
                <label class="form-label">When do you need this by? *</label>
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 8px;">
                    <label style="cursor: pointer; text-align: center; border: 1px solid var(--border-glass); padding: 10px 4px; border-radius: var(--radius-md); background: var(--bg-surface); font-size: 0.85rem; font-weight: 600;">
                        <input type="radio" name="needed_in_minutes" value="15" <?= $isSos ? 'checked' : '' ?>> In 15 mins
                    </label>
                    <label style="cursor: pointer; text-align: center; border: 1px solid var(--border-glass); padding: 10px 4px; border-radius: var(--radius-md); background: var(--bg-surface); font-size: 0.85rem; font-weight: 600;">
                        <input type="radio" name="needed_in_minutes" value="30"> In 30 mins
                    </label>
                    <label style="cursor: pointer; text-align: center; border: 1px solid var(--border-glass); padding: 10px 4px; border-radius: var(--radius-md); background: var(--bg-surface); font-size: 0.85rem; font-weight: 600;">
                        <input type="radio" name="needed_in_minutes" value="60" <?= !$isSos ? 'checked' : '' ?>> In 1 hour
                    </label>
                    <label style="cursor: pointer; text-align: center; border: 1px solid var(--border-glass); padding: 10px 4px; border-radius: var(--radius-md); background: var(--bg-surface); font-size: 0.85rem; font-weight: 600;">
                        <input type="radio" name="needed_in_minutes" value="180"> In 3 hours
                    </label>
                </div>
                <div class="form-hint">Requests auto-expire if not fulfilled before this time.</div>
            </div>

            <!-- Note / Extra Context -->
            <div class="form-group" style="margin-bottom: 24px;">
                <label for="note" class="form-label">Note for Lenders (Optional)</label>
                <textarea 
                    id="note" 
                    name="note" 
                    class="form-control" 
                    rows="3" 
                    placeholder="e.g. In exam right now, can collect at classroom door. Will return immediately afterwards."
                ><?= e($old['note'] ?? '') ?></textarea>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn <?= $isSos ? 'btn-urgent' : 'btn-primary' ?>" style="width: 100%; padding: 15px; font-size: 1.05rem;">
                <span><?= $isSos ? '🚨 Broadcast SOS Request Now' : '📢 Post Emergency Request' ?></span> →
            </button>
        </form>
    </div>
</div>
