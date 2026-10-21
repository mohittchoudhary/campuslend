<div style="max-width: 800px; margin: 20px auto 50px auto;">
    <div class="glass-card" style="padding: 40px; border-radius: var(--radius-xl);">
        <div style="text-align: center; margin-bottom: 36px;">
            <div style="display: inline-flex; align-items: center; gap: 8px; background: var(--primary-light); color: var(--primary); padding: 6px 14px; border-radius: var(--radius-full); font-weight: 700; font-size: 0.85rem; margin-bottom: 12px;">
                📖 Campus Guide
            </div>
            <h1 style="font-size: 2.2rem; margin-bottom: 10px;">How CampusLend Works</h1>
            <p style="color: var(--text-muted); font-size: 1rem;">
                A student-to-student emergency borrowing community at <strong><?= e($campusName) ?></strong>
            </p>
        </div>

        <div style="display: flex; flex-direction: column; gap: 30px;">
            <!-- Step 1 -->
            <div style="display: flex; gap: 20px; align-items: flex-start;">
                <div style="width: 48px; height: 48px; border-radius: 50%; background: #4f46e5; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.2rem; flex-shrink: 0;">
                    1
                </div>
                <div>
                    <h3 style="font-size: 1.2rem; margin-bottom: 6px;">Sign Up with your @<?= e(config('app.email_domain', 'utu.ac.in')) ?> Campus ID</h3>
                    <p style="color: var(--text-muted); line-height: 1.6;">
                        Only verified university students can participate. You will verify your account using a 6-digit email OTP. Unverified guests cannot see contact details or post requests.
                    </p>
                </div>
            </div>

            <!-- Step 2 -->
            <div style="display: flex; gap: 20px; align-items: flex-start;">
                <div style="width: 48px; height: 48px; border-radius: 50%; background: #ef4444; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.2rem; flex-shrink: 0;">
                    2
                </div>
                <div>
                    <h3 style="font-size: 1.2rem; margin-bottom: 6px;">Post an Urgent Need or List Your Spare Items</h3>
                    <p style="color: var(--text-muted); line-height: 1.6;">
                        In an emergency (e.g. lab coat needed in 20 minutes in Chemistry Lab), post an urgent request. Our smart matching algorithm notifies students in the same building or department who have listed matching items.
                    </p>
                </div>
            </div>

            <!-- Step 3 -->
            <div style="display: flex; gap: 20px; align-items: flex-start;">
                <div style="width: 48px; height: 48px; border-radius: 50%; background: #06b6d4; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.2rem; flex-shrink: 0;">
                    3
                </div>
                <div>
                    <h3 style="font-size: 1.2rem; margin-bottom: 6px;">Accept Offer & Chat Live</h3>
                    <p style="color: var(--text-muted); line-height: 1.6;">
                        A peer clicks "I can lend this". You receive an instant in-app notification. Coordinate exact meeting spot (e.g. "near Block B water cooler") via built-in direct chat.
                    </p>
                </div>
            </div>

            <!-- Step 4 -->
            <div style="display: flex; gap: 20px; align-items: flex-start;">
                <div style="width: 48px; height: 48px; border-radius: 50%; background: #10b981; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.2rem; flex-shrink: 0;">
                    4
                </div>
                <div>
                    <h3 style="font-size: 1.2rem; margin-bottom: 6px;">Two-Way Confirmation & Return</h3>
                    <p style="color: var(--text-muted); line-height: 1.6;">
                        When you receive the item, tap <strong>"Confirm Handover"</strong>. When returned, both tap <strong>"Confirm Return"</strong>. Both parties rate each other to build a high campus trust score!
                    </p>
                </div>
            </div>
        </div>

        <div style="text-align: center; margin-top: 40px; padding-top: 30px; border-top: 1px solid var(--border-glass);">
            <a href="<?= url('/register') ?>" class="btn btn-primary btn-lg">Join CampusLend Community</a>
        </div>
    </div>
</div>
