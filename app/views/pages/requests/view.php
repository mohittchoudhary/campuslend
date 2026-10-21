<?php
$targetSec = strtotime($request['needed_by']);
$remaining = format_time_remaining($request['needed_by']);
$isCritical = $request['urgency'] === 'Critical';
?>

<div style="max-width: 900px; margin: 20px auto 50px auto;">
    
    <!-- Top Nav / Back -->
    <div style="margin-bottom: 20px;">
        <a href="<?= url('/feed') ?>" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600; color: var(--text-muted); font-size: 0.9rem;">
            ← Back to Live Emergency Feed
        </a>
    </div>

    <!-- Main Request Card -->
    <div class="glass-card" style="padding: 36px; border-radius: var(--radius-xl); margin-bottom: 30px; border-left: 6px solid <?= $isCritical ? 'var(--urgent-red)' : ($request['urgency'] === 'High' ? '#f59e0b' : '#10b981') ?>;">
        
        <!-- Header Info -->
        <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 20px;">
            <div>
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                    <span class="badge badge-<?= strtolower($request['urgency']) ?>">
                        <?= $isCritical ? '🚨 Critical Urgency' : ($request['urgency'] === 'High' ? '⚡ High Urgency' : 'Normal') ?>
                    </span>
                    <span style="background: var(--bg-surface); border: 1px solid var(--border-glass); padding: 4px 10px; border-radius: var(--radius-sm); font-size: 0.8rem; font-weight: 600; text-transform: uppercase;">
                        Status: <?= e($request['status']) ?>
                    </span>
                </div>
                <h1 style="font-size: 2rem; margin-bottom: 6px;"><?= e($request['item_name']) ?></h1>
                <div style="font-size: 0.9rem; color: var(--text-muted);">
                    Category: <strong><?= e($request['category_name']) ?></strong> • Posted <?= format_time_ago($request['created_at']) ?>
                </div>
            </div>

            <!-- Countdown Timer -->
            <div style="text-align: right; background: var(--bg-surface); border: 1px solid var(--border-glass); padding: 12px 20px; border-radius: var(--radius-md);">
                <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 2px;">Needed By</div>
                <div class="countdown-badge" data-countdown="<?= $targetSec ?>" style="font-size: 1.15rem; font-weight: 800; color: <?= $isCritical ? '#ef4444' : '#d97706' ?>;">
                    <?= e($remaining['text']) ?>
                </div>
            </div>
        </div>

        <!-- Campus Location Spot -->
        <div style="background: var(--bg-surface); border: 1px solid var(--border-glass); border-radius: var(--radius-md); padding: 16px; margin-bottom: 24px; display: flex; align-items: center; gap: 14px;">
            <div style="font-size: 1.8rem;">📍</div>
            <div>
                <div style="font-weight: 700; font-size: 1rem;"><?= e($request['campus_block']) ?></div>
                <div style="color: var(--text-muted); font-size: 0.9rem;"><?= e($request['room_location']) ?></div>
            </div>
        </div>

        <!-- Requester Note -->
        <?php if (!empty($request['note'])): ?>
            <div style="margin-bottom: 24px;">
                <h3 style="font-size: 0.95rem; text-transform: uppercase; color: var(--text-muted); margin-bottom: 6px;">Requester Note</h3>
                <div style="background: var(--bg-surface); border: 1px solid var(--border-glass); border-radius: var(--radius-md); padding: 16px; font-size: 0.95rem; line-height: 1.6;">
                    <?= nl2br(e($request['note'])) ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Requester Profile Banner -->
        <div style="border-top: 1px solid var(--border-glass); padding-top: 20px; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="width: 46px; height: 46px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.1rem; border: 2px solid var(--primary);">
                    <?= strtoupper(substr($request['student_name'], 0, 1)) ?>
                </div>
                <div>
                    <div style="font-weight: 700; font-size: 1rem;"><?= e($request['student_name']) ?></div>
                    <div style="font-size: 0.85rem; color: var(--text-muted);">
                        <?= e($request['department']) ?> • <?= e($request['year_of_study']) ?>
                    </div>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 20px;">
                <div style="text-align: right;">
                    <div style="font-size: 1.2rem; font-weight: 800; color: #f59e0b;">
                        ⭐ <?= number_format($request['trust_score'], 1) ?>
                    </div>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">Trust Score (<?= (int)$request['total_ratings'] ?> reviews)</div>
                </div>

                <?php if ($isOwner && in_array($request['status'], ['open', 'offered'])): ?>
                    <form method="POST" action="<?= url('/requests/cancel') ?>" style="display: inline; margin: 0;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                        <button type="submit" class="btn btn-outline btn-sm" onclick="return confirm('Are you sure you want to cancel this request?')">
                            Cancel Request
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- OFFERS SECTION -->
    <div style="margin-bottom: 40px;">
        <h2 style="font-size: 1.5rem; margin-bottom: 16px;">
            Offers Received (<?= count($offers) ?>)
        </h2>

        <?php if (!empty($offers)): ?>
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <?php foreach ($offers as $off): ?>
                    <div class="glass-card" style="padding: 24px; border-radius: var(--radius-lg); display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 20px; border-left: 4px solid <?= $off['status'] === 'accepted' ? '#10b981' : '#6366f1' ?>;">
                        <div style="display: flex; align-items: center; gap: 16px;">
                            <div style="width: 44px; height: 44px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1rem;">
                                <?= strtoupper(substr($off['lender_name'], 0, 1)) ?>
                            </div>
                            <div>
                                <div style="font-weight: 700; font-size: 1.05rem;"><?= e($off['lender_name']) ?></div>
                                <div style="font-size: 0.82rem; color: var(--text-muted);">
                                    <?= e($off['department']) ?> • ⭐ <?= number_format($off['trust_score'], 1) ?>
                                </div>
                                <?php if (!empty($off['message'])): ?>
                                    <div style="margin-top: 6px; font-size: 0.9rem; color: var(--text-main);">
                                        "<?= e($off['message']) ?>"
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($off['item_title'])): ?>
                                    <div style="margin-top: 4px; font-size: 0.82rem; color: var(--primary); font-weight: 600;">
                                        📦 Attached Catalog Item: <?= e($off['item_title']) ?> (<?= e($off['item_condition']) ?>)
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; gap: 12px;">
                            <span class="badge" style="background: <?= $off['status'] === 'accepted' ? 'rgba(16, 185, 129, 0.15)' : 'rgba(79, 70, 229, 0.1)' ?>; color: <?= $off['status'] === 'accepted' ? '#059669' : '#4f46e5' ?>;">
                                <?= ucfirst($off['status']) ?>
                            </span>

                            <?php if ($isOwner && $off['status'] === 'pending' && in_array($request['status'], ['open', 'offered'])): ?>
                                <form method="POST" action="<?= url('/transactions/accept') ?>" style="display: inline; margin: 0;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="offer_id" value="<?= $off['id'] ?>">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        Accept Offer & Connect →
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="glass-card" style="padding: 30px; text-align: center; border-radius: var(--radius-lg);">
                <div style="font-size: 2rem; margin-bottom: 8px;">⏳</div>
                <div style="font-weight: 600; color: var(--text-muted);">No peer offers yet.</div>
                <div style="font-size: 0.85rem; color: var(--text-light); margin-top: 4px;">Nearby students in <?= e($request['campus_block']) ?> have been alerted.</div>
            </div>
        <?php endif; ?>
    </div>

    <!-- SUBMIT OFFER FORM (For peers viewing someone else's request) -->
    <?php if (!$isOwner && in_array($request['status'], ['open', 'offered'])): ?>
        <div class="glass-card" style="padding: 36px; border-radius: var(--radius-xl); margin-bottom: 40px; background: linear-gradient(135deg, rgba(79, 70, 229, 0.04) 0%, rgba(16, 185, 129, 0.04) 100%);">
            <h2 style="font-size: 1.4rem; margin-bottom: 6px;">Can you lend this item to <?= e($request['student_name']) ?>?</h2>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 20px;">
                Submit an offer to help your peer in <strong><?= e($request['campus_block']) ?></strong>.
            </p>

            <?php if (is_logged_in()): ?>
                <form method="POST" action="<?= url('/requests/offer') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="request_id" value="<?= $request['id'] ?>">

                    <?php if (!empty($userItems)): ?>
                        <div class="form-group">
                            <label for="item_id" class="form-label">Attach one of your listed items (Optional)</label>
                            <select id="item_id" name="item_id" class="form-control">
                                <option value="">-- I am carrying this item right now --</option>
                                <?php foreach ($userItems as $uItem): ?>
                                    <option value="<?= $uItem['id'] ?>">
                                        <?= e($uItem['title']) ?> (<?= e($uItem['pickup_location']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="message" class="form-label">Message / Meeting Spot</label>
                        <textarea 
                            id="message" 
                            name="message" 
                            class="form-control" 
                            rows="2" 
                            placeholder="e.g. I am on the 2nd floor of Block B right now with a spare FX-991EX. Meet me outside Room 204!" 
                            required
                        ></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="padding: 12px 24px; font-size: 0.95rem;">
                        <span>🤝 Submit Lending Offer</span> →
                    </button>
                </form>
            <?php else: ?>
                <div style="text-align: center; padding: 20px 0;">
                    <p style="margin-bottom: 14px; font-weight: 600;">Sign in with your verified campus email to offer help.</p>
                    <a href="<?= url('/login') ?>" class="btn btn-primary">Sign In to Offer</a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- SUGGESTED LENDERS SECTION (Smart Match AI / Fallback) -->
    <?php if (!empty($suggestedItems)): ?>
        <div style="margin-top: 30px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 8px;">
                <h2 style="font-size: 1.3rem; margin: 0;">Suggested Lenders in <?= e($request['category_name']) ?></h2>
                <?php if (($matchSource ?? '') === 'python_ai'): ?>
                    <span style="display: inline-flex; align-items: center; gap: 6px; background: rgba(79, 70, 229, 0.12); color: var(--primary); padding: 4px 12px; border-radius: var(--radius-full); font-size: 0.78rem; font-weight: 700;">
                        ✨ Python Smart Match AI Active
                    </span>
                <?php else: ?>
                    <span style="display: inline-flex; align-items: center; gap: 6px; background: rgba(16, 185, 129, 0.12); color: #059669; padding: 4px 12px; border-radius: var(--radius-full); font-size: 0.78rem; font-weight: 700;">
                        ⚡ Proximity & Trust Match (Fallback)
                    </span>
                <?php endif; ?>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px;">
                <?php foreach ($suggestedItems as $sItem): ?>
                    <div class="glass-card" style="padding: 18px; border-radius: var(--radius-md); display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px;">
                                <div style="font-weight: 700; font-size: 0.95rem;">
                                    <a href="<?= url('/items/view?id=' . $sItem['id']) ?>" style="color: var(--text-main);">
                                        <?= e($sItem['title']) ?>
                                    </a>
                                </div>
                                <?php if (!empty($sItem['match_score'])): ?>
                                    <span style="font-size: 0.75rem; background: #6366f1; color: #fff; padding: 2px 6px; border-radius: var(--radius-full); font-weight: 800;">
                                        <?= (int)$sItem['match_score'] ?>%
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 8px;">
                                📍 <?= e($sItem['building_block'] ?? $sItem['preferred_location'] ?? 'Campus') ?>
                            </div>

                            <?php if (!empty($sItem['match_reasons'])): ?>
                                <div style="font-size: 0.75rem; color: #059669; margin-bottom: 10px; line-height: 1.4;">
                                    ✓ <?= e(implode(' • ', array_slice($sItem['match_reasons'], 0, 2))) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-glass); padding-top: 10px; font-size: 0.8rem;">
                            <span>Lender: <strong><?= e(explode(' ', $sItem['full_name'] ?? $sItem['owner_name'] ?? 'Peer')[0]) ?></strong></span>
                            <span style="color: #f59e0b; font-weight: 700;">⭐ <?= number_format($sItem['trust_score'] ?? 5.0, 1) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
