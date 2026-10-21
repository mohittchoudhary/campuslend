<footer style="background: var(--bg-surface); border-top: 1px solid var(--border-glass); padding: 50px 0 30px 0; margin-top: auto;">
    <div class="container">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 40px; margin-bottom: 40px;">
            <!-- Brand Column -->
            <div>
                <div class="brand-logo" style="margin-bottom: 12px;">
                    <span style="font-size: 1.6rem;">🤝</span>
                    <span>CampusLend</span>
                </div>
                <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.6; margin-bottom: 16px;">
                    An exclusive emergency borrowing network connecting students of <strong><?= e(config('app.campus_name', 'Uka Tarsadia University')) ?></strong> during exams, practical labs and daily campus life.
                </p>
                <div style="display: flex; gap: 8px;">
                    <span class="badge badge-verified">Campus Verified</span>
                    <span class="badge badge-normal">Zero-Fee Peer Sharing</span>
                </div>
            </div>

            <!-- Quick Links -->
            <div>
                <h4 style="font-size: 1rem; margin-bottom: 16px; color: var(--text-main);">Campus Navigation</h4>
                <ul style="list-style: none; padding: 0; line-height: 2;">
                    <li><a href="<?= url('/feed') ?>" style="color: var(--text-muted); font-size: 0.9rem;">Live Emergency Feed</a></li>
                    <li><a href="<?= url('/items') ?>" style="color: var(--text-muted); font-size: 0.9rem;">Available Lend Catalog</a></li>
                    <li><a href="<?= url('/how-it-works') ?>" style="color: var(--text-muted); font-size: 0.9rem;">How It Works & Safety</a></li>
                    <li><a href="<?= url('/leaderboard') ?>" style="color: var(--text-muted); font-size: 0.9rem;">Top Helper Hall of Fame</a></li>
                </ul>
            </div>

            <!-- Urgent Categories -->
            <div>
                <h4 style="font-size: 1rem; margin-bottom: 16px; color: var(--text-main);">Exam Essentials</h4>
                <ul style="list-style: none; padding: 0; line-height: 2;">
                    <li><a href="<?= url('/feed?category=calculators') ?>" style="color: var(--text-muted); font-size: 0.9rem;">Scientific Calculators</a></li>
                    <li><a href="<?= url('/feed?category=drafting-geometry') ?>" style="color: var(--text-muted); font-size: 0.9rem;">Mini Drafters & Geometry</a></li>
                    <li><a href="<?= url('/feed?category=lab-workshop') ?>" style="color: var(--text-muted); font-size: 0.9rem;">Lab Coats & Goggles</a></li>
                    <li><a href="<?= url('/feed?category=chargers-power') ?>" style="color: var(--text-muted); font-size: 0.9rem;">Fast Chargers & Cables</a></li>
                </ul>
            </div>

            <!-- Trust & Campus Domain -->
            <div>
                <h4 style="font-size: 1rem; margin-bottom: 16px; color: var(--text-main);">Student Safety</h4>
                <p style="color: var(--text-muted); font-size: 0.88rem; line-height: 1.6; margin-bottom: 12px;">
                    Access is strictly restricted to verified students possessing an <strong>@<?= e(config('app.email_domain', 'utu.ac.in')) ?></strong> email address.
                </p>
                <p style="color: var(--text-light); font-size: 0.8rem;">
                    Campus Support: <a href="mailto:campuslend@utu.ac.in" style="color: var(--primary);">campuslend@utu.ac.in</a>
                </p>
            </div>
        </div>

        <div style="border-top: 1px solid var(--border-glass); padding-top: 24px; display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 14px; font-size: 0.85rem; color: var(--text-light);">
            <div>
                © <?= date('Y') ?> CampusLend – <?= e(config('app.campus_name', 'Uka Tarsadia University')) ?>. Built with pride for campus collaboration.
            </div>
            <div style="display: flex; gap: 20px;">
                <span>🔒 Secure Sessions</span>
                <span>⚡ Real-Time Matching</span>
                <span>🌟 Peer Trust Score</span>
            </div>
        </div>
    </div>
</footer>
