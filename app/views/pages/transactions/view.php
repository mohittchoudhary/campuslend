<?php
$isOverdue = $tx['status'] === 'overdue' || ($tx['status'] === 'handed_over' && strtotime($tx['due_time']) < time());
$isReturned = $tx['status'] === 'returned';
$isHandedOver = in_array($tx['status'], ['handed_over', 'overdue']);
$isAccepted = $tx['status'] === 'accepted';
$targetSec = strtotime($tx['due_time']);
?>

<div style="max-width: 860px; margin: 20px auto 50px auto;">
    
    <div style="margin-bottom: 20px;">
        <a href="<?= url('/my-activity') ?>" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600; color: var(--text-muted); font-size: 0.9rem;">
            ← Back to My Campus Activity
        </a>
    </div>

    <!-- Main Transaction Card -->
    <div class="glass-card" style="padding: 36px; border-radius: var(--radius-xl); margin-bottom: 30px;">
        
        <!-- Header -->
        <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 28px;">
            <div>
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                    <span class="badge" style="background: var(--primary-light); color: var(--primary); font-size: 0.75rem;">
                        Transaction #<?= $tx['id'] ?>
                    </span>
                    <span class="badge badge-<?= $isReturned ? 'normal' : ($isOverdue ? 'critical' : 'high') ?>">
                        <?= $isReturned ? '✅ Returned (Completed)' : ($isOverdue ? '⚠️ Overdue' : ($isHandedOver ? '📦 Handed Over' : '🤝 Accepted')) ?>
                    </span>
                </div>
                <h1 style="font-size: 2rem; margin-bottom: 4px;"><?= e($tx['item_name']) ?></h1>
                <p style="color: var(--text-muted); font-size: 0.9rem;">
                    Category: <strong><?= e($tx['category_name']) ?></strong> • Campus Location: <strong><?= e($tx['campus_block']) ?></strong>
                </p>
            </div>

            <!-- Due Timer Badge -->
            <?php if (!$isReturned): ?>
                <div style="text-align: right; background: var(--bg-surface); border: 1px solid var(--border-glass); padding: 12px 20px; border-radius: var(--radius-md);">
                    <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 2px;">
                        <?= $isOverdue ? 'Overdue Since' : 'Return Due Time' ?>
                    </div>
                    <div class="countdown-badge" data-countdown="<?= $targetSec ?>" style="font-size: 1.15rem; font-weight: 800; color: <?= $isOverdue ? '#ef4444' : '#d97706' ?>;">
                        <?= date('M d, h:i A', strtotime($tx['due_time'])) ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Overdue Alert Banner if applicable -->
        <?php if ($isOverdue && !$isReturned): ?>
            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: var(--radius-md); padding: 16px; margin-bottom: 28px; display: flex; align-items: center; gap: 14px; color: #b91c1c;">
                <div style="font-size: 2rem;">⚠️</div>
                <div>
                    <div style="font-weight: 800; font-size: 1rem;">Overdue Item Return Alert!</div>
                    <div style="font-size: 0.88rem;">The agreed loan period for this item has elapsed. Please meet up immediately to complete the return and protect your campus trust score.</div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Visual 4-Step Progress State Machine Bar -->
        <div style="margin-bottom: 36px; padding: 20px 10px; background: var(--bg-surface); border: 1px solid var(--border-glass); border-radius: var(--radius-lg);">
            <div style="display: flex; justify-content: space-between; position: relative;">
                
                <!-- Step 1: Requested -->
                <div style="text-align: center; flex: 1; position: relative; z-index: 2;">
                    <div style="width: 38px; height: 38px; border-radius: 50%; background: #10b981; color: #fff; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px auto; font-weight: 700;">✓</div>
                    <div style="font-size: 0.82rem; font-weight: 700;">Requested</div>
                </div>

                <!-- Step 2: Offered -->
                <div style="text-align: center; flex: 1; position: relative; z-index: 2;">
                    <div style="width: 38px; height: 38px; border-radius: 50%; background: #10b981; color: #fff; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px auto; font-weight: 700;">✓</div>
                    <div style="font-size: 0.82rem; font-weight: 700;">Offered</div>
                </div>

                <!-- Step 3: Accepted -->
                <div style="text-align: center; flex: 1; position: relative; z-index: 2;">
                    <div style="width: 38px; height: 38px; border-radius: 50%; background: #10b981; color: #fff; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px auto; font-weight: 700;">✓</div>
                    <div style="font-size: 0.82rem; font-weight: 700;">Accepted</div>
                </div>

                <!-- Step 4: Handed Over -->
                <div style="text-align: center; flex: 1; position: relative; z-index: 2;">
                    <div style="width: 38px; height: 38px; border-radius: 50%; background: <?= ($isHandedOver || $isReturned) ? '#10b981' : 'var(--border-glass)' ?>; color: <?= ($isHandedOver || $isReturned) ? '#fff' : 'var(--text-muted)' ?>; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px auto; font-weight: 700;">
                        <?= ($isHandedOver || $isReturned) ? '✓' : '4' ?>
                    </div>
                    <div style="font-size: 0.82rem; font-weight: 700; color: <?= ($isHandedOver || $isReturned) ? 'var(--text-main)' : 'var(--text-muted)' ?>;">Handed Over</div>
                </div>

                <!-- Step 5: Returned -->
                <div style="text-align: center; flex: 1; position: relative; z-index: 2;">
                    <div style="width: 38px; height: 38px; border-radius: 50%; background: <?= $isReturned ? '#10b981' : 'var(--border-glass)' ?>; color: <?= $isReturned ? '#fff' : 'var(--text-muted)' ?>; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px auto; font-weight: 700;">
                        <?= $isReturned ? '✓' : '5' ?>
                    </div>
                    <div style="font-size: 0.82rem; font-weight: 700; color: <?= $isReturned ? 'var(--text-main)' : 'var(--text-muted)' ?>;">Returned</div>
                </div>
            </div>
        </div>

        <!-- Two Parties Details Grid -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
            <!-- Borrower Card -->
            <div style="background: var(--bg-surface); border: 1px solid var(--border-glass); padding: 20px; border-radius: var(--radius-md);">
                <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 800; margin-bottom: 8px;">
                    🎒 Borrower <?= $isBorrower ? '(You)' : '' ?>
                </div>
                <div style="font-weight: 700; font-size: 1.1rem; margin-bottom: 2px;"><?= e($tx['borrower_name']) ?></div>
                <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 10px;"><?= e($tx['borrower_dept']) ?></div>
                <div style="font-size: 0.85rem;">📞 Phone: <strong><?= e($tx['borrower_phone']) ?></strong></div>
            </div>

            <!-- Lender Card -->
            <div style="background: var(--bg-surface); border: 1px solid var(--border-glass); padding: 20px; border-radius: var(--radius-md);">
                <div style="font-size: 0.75rem; text-transform: uppercase; color: var(--text-muted); font-weight: 800; margin-bottom: 8px;">
                    🤝 Lender <?= $isLender ? '(You)' : '' ?>
                </div>
                <div style="font-weight: 700; font-size: 1.1rem; margin-bottom: 2px;"><?= e($tx['lender_name']) ?></div>
                <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 10px;"><?= e($tx['lender_dept']) ?></div>
                <div style="font-size: 0.85rem;">📞 Phone: <strong><?= e($tx['lender_phone']) ?></strong></div>
            </div>
        </div>

        <!-- ACTION CONTROLS FOR STATE TRANSITIONS -->
        <div style="border-top: 1px solid var(--border-glass); padding-top: 24px;">
            
            <?php if ($isAccepted): ?>
                <!-- STEP 1: Handover Confirmation Form -->
                <div style="background: rgba(79, 70, 229, 0.05); border: 1px solid rgba(79, 70, 229, 0.2); padding: 24px; border-radius: var(--radius-lg); text-align: center;">
                    <h3 style="font-size: 1.25rem; margin-bottom: 6px;">Step 1: Confirm Physical Handover</h3>
                    <p style="color: var(--text-muted); font-size: 0.92rem; max-width: 500px; margin: 0 auto 20px auto;">
                        Meet up in <strong><?= e($tx['campus_block']) ?></strong>. Once the item is handed over to <?= e($tx['borrower_name']) ?>, click confirm below.
                    </p>

                    <form method="POST" action="<?= url('/transactions/handover') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="transaction_id" value="<?= $tx['id'] ?>">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <span>📦 Confirm Item Handover</span> →
                        </button>
                    </form>
                </div>

            <?php elseif ($isHandedOver && !$isReturned): ?>
                <!-- STEP 2: Return Confirmation Form -->
                <div style="background: rgba(16, 185, 129, 0.06); border: 1px solid rgba(16, 185, 129, 0.25); padding: 24px; border-radius: var(--radius-lg); text-align: center;">
                    <h3 style="font-size: 1.25rem; margin-bottom: 6px;">Step 2: Confirm Safe Return</h3>
                    <p style="color: var(--text-muted); font-size: 0.92rem; max-width: 500px; margin: 0 auto 20px auto;">
                        Once the item has been returned back to <?= e($tx['lender_name']) ?>, click confirm below to complete the transaction.
                    </p>

                    <form method="POST" action="<?= url('/transactions/return') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="transaction_id" value="<?= $tx['id'] ?>">
                        <button type="submit" class="btn btn-primary btn-lg" style="background: #10b981;">
                            <span>✅ Confirm Item Returned</span> →
                        </button>
                    </form>
                </div>

            <?php elseif ($isReturned): ?>
                <?php
                // Check if viewer has already rated this transaction
                $sqlCheckRating = "SELECT * FROM ratings WHERE transaction_id = :tx_id AND reviewer_id = :rev_id LIMIT 1";
                $myRating = App\Models\Rating::fetch($sqlCheckRating, [':tx_id' => $tx['id'], ':rev_id' => $userId]);
                ?>

                <!-- COMPLETED & RATING CARD -->
                <div style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.25); padding: 26px; border-radius: var(--radius-lg); margin-bottom: 24px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; margin-bottom: 16px;">
                        <div>
                            <h3 style="font-size: 1.3rem; color: #065f46; margin-bottom: 4px;">🎉 Item Returned & Completed</h3>
                            <p style="font-size: 0.9rem; color: var(--text-muted); margin: 0;">Safe return completed on <?= date('M d, Y h:i A', strtotime($tx['returned_time'])) ?></p>
                        </div>
                        <?php if ($myRating): ?>
                            <div style="background: var(--bg-surface); border: 1px solid var(--border-glass); padding: 8px 16px; border-radius: var(--radius-full); font-weight: 700; color: #f59e0b; font-size: 0.9rem;">
                                Your Review: <?= str_repeat('⭐', $myRating['stars']) ?> (<?= $myRating['stars'] ?>/5)
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!$myRating): ?>
                        <!-- Rating Submission Form -->
                        <div style="background: var(--bg-surface); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-glass);">
                            <h4 style="font-size: 1.05rem; margin-bottom: 6px;">Rate your experience with <?= e($isBorrower ? $tx['lender_name'] : $tx['borrower_name']) ?></h4>
                            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 16px;">Your honest review helps calculate their campus trust score and award badges.</p>

                            <form method="POST" action="<?= url('/ratings/submit') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="transaction_id" value="<?= $tx['id'] ?>">

                                <div class="form-group" style="margin-bottom: 14px;">
                                    <label class="form-label">Star Rating *</label>
                                    <div style="display: flex; gap: 14px; font-size: 1.1rem; color: #f59e0b;">
                                        <label style="cursor: pointer;"><input type="radio" name="stars" value="5" checked> ⭐⭐⭐⭐⭐ (5 - Excellent)</label>
                                        <label style="cursor: pointer;"><input type="radio" name="stars" value="4"> ⭐⭐⭐⭐ (4 - Good)</label>
                                        <label style="cursor: pointer;"><input type="radio" name="stars" value="3"> ⭐⭐⭐ (3 - Average)</label>
                                        <label style="cursor: pointer;"><input type="radio" name="stars" value="2"> ⭐⭐ (2 - Poor)</label>
                                        <label style="cursor: pointer;"><input type="radio" name="stars" value="1"> ⭐ (1 - Terrible)</label>
                                    </div>
                                </div>

                                <div class="form-group" style="margin-bottom: 16px;">
                                    <label for="comment" class="form-label">Feedback / Review</label>
                                    <textarea id="comment" name="comment" class="form-control" rows="2" placeholder="e.g. Prompt handover, returned clean and on time. Excellent peer!"></textarea>
                                </div>

                                <button type="submit" class="btn btn-primary btn-sm">
                                    <span>⭐ Submit Campus Rating</span>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- PER-TRANSACTION CHAT BOX (Phase 3) -->
    <div class="glass-card" style="padding: 28px; border-radius: var(--radius-xl); margin-bottom: 30px;" id="chat">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid var(--border-glass); padding-bottom: 14px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="font-size: 1.5rem;">💬</div>
                <div>
                    <h2 style="font-size: 1.25rem; margin-bottom: 2px;">Direct Transaction Chat</h2>
                    <div style="font-size: 0.8rem; color: var(--text-muted);">Coordinate meeting spot & handover in real-time</div>
                </div>
            </div>

            <!-- Report & Block Controls -->
            <div style="display: flex; gap: 8px;">
                <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('report-modal').style.display='flex'" style="color: #ef4444; border-color: rgba(239, 68, 68, 0.3);">
                    🚩 Report Peer
                </button>
            </div>
        </div>

        <!-- Chat Stream Window -->
        <div id="chat-messages" style="height: 320px; overflow-y: auto; padding: 14px; background: var(--bg-surface); border: 1px solid var(--border-glass); border-radius: var(--radius-md); margin-bottom: 16px; display: flex; flex-direction: column; gap: 12px;">
            <div style="text-align: center; color: var(--text-muted); font-size: 0.85rem; padding: 20px;">
                Loading conversation messages...
            </div>
        </div>

        <!-- Message Input Form -->
        <form id="chat-form" style="display: flex; gap: 10px;">
            <input 
                type="text" 
                id="chat-input" 
                class="form-control" 
                placeholder="Type your message (e.g. I am standing near the Block B staircase)..." 
                autocomplete="off" 
                required 
                style="flex: 1;"
            >
            <button type="submit" class="btn btn-primary" id="chat-send-btn" style="padding: 10px 22px;">
                <span>Send</span> ✈️
            </button>
        </form>
    </div>
</div>

<!-- Report Peer Modal Dialog -->
<div id="report-modal" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center; padding: 20px;">
    <div class="glass-card" style="max-width: 480px; width: 100%; padding: 30px; border-radius: var(--radius-xl);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="font-size: 1.3rem; margin: 0;">Report User Violation</h3>
            <button type="button" onclick="document.getElementById('report-modal').style.display='none'" style="background:none; border:none; font-size: 1.3rem; cursor: pointer; color: var(--text-muted);">✕</button>
        </div>
        <form method="POST" action="<?= url('/users/report') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="target_id" value="<?= $isBorrower ? $tx['lender_id'] : $tx['borrower_id'] ?>">

            <div class="form-group">
                <label for="reason" class="form-label">Violation Reason *</label>
                <select name="reason" id="reason" class="form-control" required>
                    <option value="Did not return item on time">Did not return item on time</option>
                    <option value="Damaged the borrowed item">Damaged the borrowed item</option>
                    <option value="Inappropriate or abusive chat behavior">Inappropriate or abusive chat behavior</option>
                    <option value="Did not show up for scheduled handover">Did not show up for scheduled handover</option>
                    <option value="Other campus violation">Other campus violation</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label for="details" class="form-label">Details / Explanation</label>
                <textarea name="details" id="details" class="form-control" rows="3" placeholder="Provide context for campus moderators..."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('report-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-urgent">Submit Report</button>
            </div>
        </form>
    </div>
</div>

<script>
// Chat polling & real-time messaging script
(function() {
    const txId = <?= (int)$tx['id'] ?>;
    const currentUserId = <?= (int)$userId ?>;
    const chatContainer = document.getElementById('chat-messages');
    const chatForm = document.getElementById('chat-form');
    const chatInput = document.getElementById('chat-input');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    let isSubmitting = false;

    async function fetchMessages() {
        try {
            const res = await fetch(`<?= url('/api/chat/messages') ?>?transaction_id=${txId}`, {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) return;

            const data = await res.json();
            if (!data.messages) return;

            const wasNearBottom = chatContainer.scrollHeight - chatContainer.scrollTop - chatContainer.clientHeight < 60;

            if (data.messages.length === 0) {
                chatContainer.innerHTML = '<div style="text-align: center; color: var(--text-muted); font-size: 0.85rem; padding: 20px;">No messages in this transaction yet. Say hello! 👋</div>';
                return;
            }

            let html = '';
            data.messages.forEach(msg => {
                const isMe = parseInt(msg.sender_id, 10) === currentUserId;
                const timeStr = new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

                html += `
                    <div style="display: flex; flex-direction: column; align-items: ${isMe ? 'flex-end' : 'flex-start'};">
                        <div style="font-size: 0.72rem; color: var(--text-muted); margin-bottom: 2px;">
                            ${isMe ? 'You' : escapeHtml(msg.sender_name)} • ${timeStr}
                        </div>
                        <div style="max-width: 75%; padding: 10px 16px; border-radius: 16px; font-size: 0.92rem; line-height: 1.4; word-break: break-word; background: ${isMe ? 'linear-gradient(135deg, #4f46e5 0%, #4338ca 100%)' : 'var(--bg-card)'}; color: ${isMe ? '#ffffff' : 'var(--text-main)'}; border: 1px solid ${isMe ? 'transparent' : 'var(--border-glass)'};">
                            ${escapeHtml(msg.message)}
                        </div>
                    </div>
                `;
            });

            chatContainer.innerHTML = html;

            if (wasNearBottom) {
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }
        } catch (e) {
            // Silently retry
        }
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Send Message
    chatForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const text = chatInput.value.trim();
        if (!text || isSubmitting) return;

        isSubmitting = true;
        chatInput.value = '';

        try {
            const formData = new FormData();
            formData.append('csrf_token', csrfToken);
            formData.append('transaction_id', txId);
            formData.append('message', text);

            const res = await fetch(`<?= url('/api/chat/send') ?>`, {
                method: 'POST',
                body: formData,
            });

            if (res.ok) {
                await fetchMessages();
                chatContainer.scrollTop = chatContainer.scrollHeight;
            } else {
                alert('Failed to send message.');
            }
        } catch (err) {
            alert('Connection error while sending message.');
        } finally {
            isSubmitting = false;
        }
    });

    // Start polling every 5 seconds
    fetchMessages();
    setInterval(fetchMessages, 5000);
})();
</script>
