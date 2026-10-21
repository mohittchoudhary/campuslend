<div class="landing-page">
    <!-- Hero Section -->
    <section style="position: relative; padding: 40px 0 70px 0; overflow: hidden;">
        <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 40px; align-items: center;" class="hero-grid">
            
            <!-- Hero Text Content -->
            <div>
                <div style="display: inline-flex; align-items: center; gap: 8px; background: var(--primary-light); color: var(--primary); padding: 6px 14px; border-radius: var(--radius-full); font-weight: 700; font-size: 0.85rem; margin-bottom: 20px;">
                    <span>🎓</span> <?= e($campusName) ?> Student Hub
                </div>

                <h1 style="font-size: clamp(2.3rem, 4vw, 3.4rem); font-weight: 900; letter-spacing: -1px; margin-bottom: 20px; line-height: 1.15;">
                    Need a calculator or drafter <span style="background: linear-gradient(135deg, #ef4444 0%, #f59e0b 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">right now</span> for your exam?
                </h1>

                <p style="font-size: 1.15rem; color: var(--text-muted); line-height: 1.6; margin-bottom: 32px; max-width: 580px;">
                    Don't panic! Connect with peers in your block or floor instantly. Borrow scientific calculators, lab coats, fast chargers, drafters, or notes in minutes.
                </p>

                <!-- Strong CTAs -->
                <div style="display: flex; flex-wrap: wrap; gap: 14px; margin-bottom: 40px;">
                    <a href="<?= is_logged_in() ? url('/requests/create') : url('/register') ?>" class="btn btn-urgent btn-lg">
                        <span>🚨 Post Emergency Request</span>
                    </a>
                    <a href="<?= is_logged_in() ? url('/items/create') : url('/register') ?>" class="btn btn-outline btn-lg">
                        <span>📦 Lend an Item</span>
                    </a>
                </div>

                <!-- Verified Campus Trust Badge -->
                <div style="display: flex; align-items: center; gap: 16px; font-size: 0.88rem; color: var(--text-muted);">
                    <div style="display: flex; -space-x: 10px;">
                        <span style="display: inline-block; width: 32px; height: 32px; border-radius: 50%; background: #6366f1; color: #fff; text-align: center; line-height: 32px; font-weight: 700; border: 2px solid var(--bg-page);">A</span>
                        <span style="display: inline-block; width: 32px; height: 32px; border-radius: 50%; background: #ec4899; color: #fff; text-align: center; line-height: 32px; font-weight: 700; border: 2px solid var(--bg-page); margin-left: -8px;">D</span>
                        <span style="display: inline-block; width: 32px; height: 32px; border-radius: 50%; background: #10b981; color: #fff; text-align: center; line-height: 32px; font-weight: 700; border: 2px solid var(--bg-page); margin-left: -8px;">R</span>
                    </div>
                    <span><strong>1,200+ students</strong> active across Engineering, Pharmacy & Management blocks.</span>
                </div>
            </div>

            <!-- Floating Hero Illustration -->
            <div style="position: relative; display: flex; justify-content: center; align-items: center;" class="hero-illustration">
                <!-- Glowing background blob -->
                <div style="position: absolute; width: 320px; height: 320px; border-radius: 50%; background: radial-gradient(circle, rgba(79, 70, 229, 0.25) 0%, rgba(239, 68, 68, 0.15) 70%, transparent 100%); filter: blur(40px); z-index: 0;"></div>

                <!-- Center Campus Hub Card -->
                <div class="glass-card" style="position: relative; z-index: 2; padding: 28px; width: 100%; max-width: 380px; text-align: center; border-radius: var(--radius-xl);">
                    <div style="font-size: 3rem; margin-bottom: 12px;">🤝⚡</div>
                    <h3 style="font-size: 1.3rem; margin-bottom: 6px;">CampusLend Radar</h3>
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 20px;">Live student peer-lending active</p>
                    
                    <div style="background: var(--bg-surface); padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--border-glass); text-align: left; margin-bottom: 12px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <span class="badge badge-critical">Critical Urgency</span>
                            <span style="font-size: 0.75rem; color: #ef4444; font-weight: 700;">⏱️ 25m left</span>
                        </div>
                        <div style="font-weight: 700; font-size: 0.95rem;">Casio FX-991EX Calculator</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">📍 Block B • Exam Hall 101</div>
                    </div>

                    <div style="background: var(--bg-surface); padding: 12px; border-radius: var(--radius-md); border: 1px solid var(--border-glass); text-align: left;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <span class="badge badge-high">High Urgency</span>
                            <span style="font-size: 0.75rem; color: #d97706; font-weight: 700;">⏱️ 40m left</span>
                        </div>
                        <div style="font-weight: 700; font-size: 0.95rem;">White Lab Coat (Size L)</div>
                        <div style="font-size: 0.8rem; color: var(--text-muted);">📍 Pharmacy Block • Chem Lab 1</div>
                    </div>
                </div>

                <!-- Floating Drifting Items -->
                <div class="floating-item" style="position: absolute; top: -15px; left: 10px; z-index: 3; background: var(--bg-surface); border: 1px solid var(--border-glass); padding: 10px 16px; border-radius: var(--radius-full); box-shadow: var(--shadow-lg); display: flex; align-items: center; gap: 8px; font-size: 0.85rem; font-weight: 700;">
                    <span>📐</span> Mini Drafter
                </div>

                <div class="floating-item-delayed" style="position: absolute; bottom: 20px; right: -10px; z-index: 3; background: var(--bg-surface); border: 1px solid var(--border-glass); padding: 10px 16px; border-radius: var(--radius-full); box-shadow: var(--shadow-lg); display: flex; align-items: center; gap: 8px; font-size: 0.85rem; font-weight: 700;">
                    <span>⚡</span> 65W Laptop Charger
                </div>

                <div class="floating-item" style="position: absolute; top: 180px; right: -25px; z-index: 3; background: var(--bg-surface); border: 1px solid var(--border-glass); padding: 8px 14px; border-radius: var(--radius-full); box-shadow: var(--shadow-lg); display: flex; align-items: center; gap: 6px; font-size: 0.8rem; font-weight: 700;">
                    <span>🥼</span> Lab Coat
                </div>
            </div>
        </div>
    </section>

    <!-- Animated Platform Counters -->
    <section style="margin-bottom: 60px;">
        <div class="glass-card" style="padding: 30px; border-radius: var(--radius-xl);">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 24px; text-align: center;">
                <div>
                    <div style="font-size: 2.5rem; font-weight: 900; color: var(--primary); font-family: var(--font-heading);">
                        <?= number_format($stats['students_count'] ?? 20) ?>+
                    </div>
                    <div style="color: var(--text-muted); font-weight: 600; font-size: 0.95rem;">Verified UTU Students</div>
                </div>

                <div>
                    <div style="font-size: 2.5rem; font-weight: 900; color: #10b981; font-family: var(--font-heading);">
                        <?= number_format($stats['fulfilled_count'] ?? 150) ?>+
                    </div>
                    <div style="color: var(--text-muted); font-weight: 600; font-size: 0.95rem;">Emergency Asists Fulfilled</div>
                </div>

                <div>
                    <div style="font-size: 2.5rem; font-weight: 900; color: #06b6d4; font-family: var(--font-heading);">
                        <?= number_format($stats['items_count'] ?? 25) ?>+
                    </div>
                    <div style="color: var(--text-muted); font-weight: 600; font-size: 0.95rem;">Catalog Items Available</div>
                </div>

                <div>
                    <div style="font-size: 2.5rem; font-weight: 900; color: #ef4444; font-family: var(--font-heading);">
                        < 4 min
                    </div>
                    <div style="color: var(--text-muted); font-weight: 600; font-size: 0.95rem;">Average Response Time</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Category Quick Chips -->
    <section style="margin-bottom: 60px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px;">
            <div>
                <h2 style="font-size: 1.8rem; margin-bottom: 6px;">Browse by Campus Need</h2>
                <p style="color: var(--text-muted); font-size: 0.95rem;">Common exam, practical, and daily tools available on campus</p>
            </div>
            <a href="<?= url('/items') ?>" class="btn btn-outline btn-sm">View All Catalog →</a>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 16px;">
            <?php foreach ($categories as $cat): ?>
                <a href="<?= url('/feed?category=' . urlencode($cat['slug'])) ?>" class="glass-card" style="padding: 18px; text-align: center; text-decoration: none; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <div style="width: 48px; height: 48px; border-radius: var(--radius-md); background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                        <i data-lucide="<?= e($cat['icon']) ?>" style="width: 24px; height: 24px;"></i>
                    </div>
                    <span style="font-weight: 700; font-size: 0.9rem; color: var(--text-main);"><?= e($cat['name']) ?></span>
                    <span style="font-size: 0.75rem; color: var(--text-muted);"><?= (int)$cat['item_count'] ?> items available</span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Live Urgent Emergency Feed Preview -->
    <section style="margin-bottom: 60px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px;">
            <div>
                <div style="display: inline-flex; align-items: center; gap: 6px; color: #ef4444; font-weight: 800; font-size: 0.85rem; text-transform: uppercase; margin-bottom: 4px;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #ef4444; animation: pulse-critical 1.5s infinite;"></span>
                    Live Campus Radar
                </div>
                <h2 style="font-size: 1.8rem;">Active Emergency Requests</h2>
            </div>
            <a href="<?= url('/feed') ?>" class="btn btn-primary btn-sm">Full Live Feed →</a>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 20px;">
            <?php if (!empty($urgentRequests)): ?>
                <?php foreach ($urgentRequests as $req): ?>
                    <?php 
                        $remaining = format_time_remaining($req['needed_by']);
                        $targetSec = strtotime($req['needed_by']);
                    ?>
                    <div class="glass-card" style="padding: 22px; position: relative; border-left: 4px solid <?= $req['urgency'] === 'Critical' ? '#ef4444' : '#f59e0b' ?>;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <span class="badge badge-<?= strtolower($req['urgency']) ?>">
                                <?= $req['urgency'] === 'Critical' ? '🚨 Critical' : '⚡ ' . e($req['urgency']) ?>
                            </span>
                            <span class="countdown-badge" data-countdown="<?= $targetSec ?>" style="font-size: 0.82rem; font-weight: 700; color: <?= $req['urgency'] === 'Critical' ? '#ef4444' : '#d97706' ?>;">
                                <?= e($remaining['text']) ?>
                            </span>
                        </div>

                        <h3 style="font-size: 1.15rem; margin-bottom: 8px;">
                            <a href="<?= url('/requests/view?id=' . $req['id']) ?>" style="color: var(--text-main);">
                                <?= e($req['item_name']) ?>
                            </a>
                        </h3>

                        <div style="display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 12px;">
                            <span>📍 <strong><?= e($req['campus_block']) ?></strong> (<?= e($req['room_location']) ?>)</span>
                        </div>

                        <?php if (!empty($req['note'])): ?>
                            <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 16px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                <?= e($req['note']) ?>
                            </p>
                        <?php endif; ?>

                        <div style="border-top: 1px solid var(--border-glass); padding-top: 14px; display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="width: 28px; height: 28px; border-radius: 50%; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700;">
                                    <?= strtoupper(substr($req['student_name'], 0, 1)) ?>
                                </div>
                                <span style="font-size: 0.85rem; font-weight: 600;"><?= e(explode(' ', $req['student_name'])[0]) ?></span>
                                <span style="font-size: 0.8rem; color: #f59e0b;">⭐ <?= number_format($req['trust_score'], 1) ?></span>
                            </div>

                            <a href="<?= is_logged_in() ? url('/requests/view?id=' . $req['id']) : url('/login') ?>" class="btn btn-primary btn-sm">
                                I Can Lend This
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="glass-card" style="padding: 40px; text-align: center; grid-column: 1 / -1;">
                    <div style="font-size: 2.5rem; margin-bottom: 10px;">🎉</div>
                    <h3>No Urgent Requests Open Right Now!</h3>
                    <p style="color: var(--text-muted); margin-bottom: 20px;">Everyone currently has the tools they need. Got spare items? List them now to help others.</p>
                    <a href="<?= url('/items/create') ?>" class="btn btn-outline">List an Item for Lending</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Trust & Safety Section -->
    <section style="margin-bottom: 40px;">
        <div class="glass-card" style="padding: 40px; border-radius: var(--radius-xl); background: linear-gradient(135deg, rgba(79, 70, 229, 0.05) 0%, rgba(6, 182, 212, 0.05) 100%);">
            <div style="text-align: center; max-width: 600px; margin: 0 auto 36px auto;">
                <h2 style="font-size: 2rem; margin-bottom: 10px;">Built on Student Trust</h2>
                <p style="color: var(--text-muted);">How CampusLend guarantees zero theft, on-time returns, and friendly campus accountability.</p>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 24px;">
                <div style="background: var(--bg-surface); padding: 24px; border-radius: var(--radius-lg); border: 1px solid var(--border-glass);">
                    <div style="font-size: 1.8rem; margin-bottom: 12px;">🛡️</div>
                    <h3 style="font-size: 1.15rem; margin-bottom: 8px;">Campus Domain Lock</h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.5;">
                        Strict registration restricted exclusively to <strong>@<?= e(config('app.email_domain', 'utu.ac.in')) ?></strong>. Every student is verified with an enrollment number and department.
                    </p>
                </div>

                <div style="background: var(--bg-surface); padding: 24px; border-radius: var(--radius-lg); border: 1px solid var(--border-glass);">
                    <div style="font-size: 1.8rem; margin-bottom: 12px;">🤝</div>
                    <h3 style="font-size: 1.15rem; margin-bottom: 8px;">2-Way Handover Confirmation</h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.5;">
                        Both borrower and lender independently confirm item collection and return inside the app. No disputes about who has what.
                    </p>
                </div>

                <div style="background: var(--bg-surface); padding: 24px; border-radius: var(--radius-lg); border: 1px solid var(--border-glass);">
                    <div style="font-size: 1.8rem; margin-bottom: 12px;">⭐</div>
                    <h3 style="font-size: 1.15rem; margin-bottom: 8px;">Peer Trust Scores & Badges</h3>
                    <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.5;">
                        Earn badges like <strong>"Exam Saviour"</strong> and <strong>"Campus Hero"</strong>. Students with late returns receive automated alerts and trust score penalties.
                    </p>
                </div>
            </div>
        </div>
    </section>
</div>
