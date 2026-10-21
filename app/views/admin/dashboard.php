<div class="admin-dashboard-page" style="margin: 20px auto 60px auto;">

    <!-- Top Admin Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 28px;">
        <div>
            <div style="display: inline-flex; align-items: center; gap: 6px; background: rgba(79, 70, 229, 0.1); color: var(--primary); padding: 4px 12px; border-radius: var(--radius-full); font-weight: 700; font-size: 0.8rem; margin-bottom: 8px;">
                ⚙️ Command Center
            </div>
            <h1 style="font-size: 2.2rem; margin: 0;">Campus Administration</h1>
            <p style="color: var(--text-muted); margin-top: 4px; font-size: 0.95rem;">System metrics, campus activity, and student moderation</p>
        </div>

        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="<?= url('/admin/users') ?>" class="btn btn-outline btn-sm">👥 Students</a>
            <a href="<?= url('/admin/categories') ?>" class="btn btn-outline btn-sm">📁 Categories</a>
            <a href="<?= url('/admin/reports') ?>" class="btn btn-outline btn-sm">🚨 Reports</a>
            <a href="<?= url('/admin/logs') ?>" class="btn btn-outline btn-sm">📜 Audit Logs</a>
        </div>
    </div>

    <!-- Quick Metrics Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div class="glass-card" style="padding: 22px; border-radius: var(--radius-lg); border-left: 4px solid #4f46e5;">
            <div style="color: var(--text-muted); font-size: 0.82rem; font-weight: 700; text-transform: uppercase;">Total Students</div>
            <div style="font-size: 2.2rem; font-weight: 900; margin: 8px 0; font-family: var(--font-heading); color: var(--primary);">
                <?= number_format($totalUsers) ?>
            </div>
            <div style="font-size: 0.8rem; color: #10b981;">✓ Verified @utu.ac.in accounts</div>
        </div>

        <div class="glass-card" style="padding: 22px; border-radius: var(--radius-lg); border-left: 4px solid #ef4444;">
            <div style="color: var(--text-muted); font-size: 0.82rem; font-weight: 700; text-transform: uppercase;">Borrow Requests</div>
            <div style="font-size: 2.2rem; font-weight: 900; margin: 8px 0; font-family: var(--font-heading); color: #ef4444;">
                <?= number_format($totalRequests) ?>
            </div>
            <div style="font-size: 0.8rem; color: var(--text-muted);"><?= count($criticalRequests) ?> Critical open now</div>
        </div>

        <div class="glass-card" style="padding: 22px; border-radius: var(--radius-lg); border-left: 4px solid #06b6d4;">
            <div style="color: var(--text-muted); font-size: 0.82rem; font-weight: 700; text-transform: uppercase;">Lending Catalog</div>
            <div style="font-size: 2.2rem; font-weight: 900; margin: 8px 0; font-family: var(--font-heading); color: #06b6d4;">
                <?= number_format($totalItems) ?>
            </div>
            <div style="font-size: 0.8rem; color: var(--text-muted);">Active equipment listings</div>
        </div>

        <div class="glass-card" style="padding: 22px; border-radius: var(--radius-lg); border-left: 4px solid #10b981;">
            <div style="color: var(--text-muted); font-size: 0.82rem; font-weight: 700; text-transform: uppercase;">Success Rate</div>
            <div style="font-size: 2.2rem; font-weight: 900; margin: 8px 0; font-family: var(--font-heading); color: #10b981;">
                <?= number_format($successRate, 1) ?>%
            </div>
            <div style="font-size: 0.8rem; color: #10b981;">Items returned safely</div>
        </div>
    </div>

    <!-- Chart.js Visualization Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 24px; margin-bottom: 30px;">
        <!-- Requests Per Day Chart -->
        <div class="glass-card" style="padding: 24px; border-radius: var(--radius-xl);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 style="font-size: 1.15rem; margin: 0;">📈 Requests Per Day</h3>
                <span style="font-size: 0.8rem; color: var(--text-muted);">Recent 7 days</span>
            </div>
            <div style="height: 240px; position: relative;">
                <canvas id="requestsChart"></canvas>
            </div>
        </div>

        <!-- Top Categories Chart -->
        <div class="glass-card" style="padding: 24px; border-radius: var(--radius-xl);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 style="font-size: 1.15rem; margin: 0;">📊 High-Demand Categories</h3>
                <span style="font-size: 0.8rem; color: var(--text-muted);">By request volume</span>
            </div>
            <div style="height: 240px; position: relative;">
                <canvas id="categoriesChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Exam Season Control & Critical Requests -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 30px;" class="admin-subgrid">
        
        <!-- Exam Mode Banner Configuration -->
        <div class="glass-card" style="padding: 24px; border-radius: var(--radius-xl);">
            <h3 style="font-size: 1.15rem; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                📢 Exam Season Alert Banner
            </h3>
            <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 18px;">
                Toggle the persistent campus-wide banner displayed across the top of all pages during mid-term and final exam weeks.
            </p>

            <form action="<?= url('/admin/settings/exam-mode') ?>" method="POST">
                <?= csrf_field() ?>
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 16px;">
                    <input type="checkbox" id="exam_mode" name="exam_mode" value="1" <?= $examMode === '1' ? 'checked' : '' ?> style="width: 20px; height: 20px; accent-color: var(--primary); cursor: pointer;">
                    <label for="exam_mode" style="font-weight: 700; cursor: pointer; font-size: 0.95rem;">Enable Exam Season Banner</label>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" for="exam_message">Custom Banner Message</label>
                    <textarea class="form-control" id="exam_message" name="exam_message" rows="3" style="font-size: 0.88rem;"><?= e($examMessage) ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-sm">Save Banner Settings</button>
            </form>
        </div>

        <!-- Active Critical Emergency Requests -->
        <div class="glass-card" style="padding: 24px; border-radius: var(--radius-xl);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <h3 style="font-size: 1.15rem; margin: 0; color: #ef4444; display: flex; align-items: center; gap: 8px;">
                    ⚡ Active Critical Requests
                </h3>
                <span class="badge badge-critical"><?= count($criticalRequests) ?> Active</span>
            </div>

            <?php if (!empty($criticalRequests)): ?>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php foreach ($criticalRequests as $cr): ?>
                        <div style="background: var(--bg-surface); padding: 12px 14px; border-radius: var(--radius-md); border: 1px solid var(--border-glass); display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-weight: 700; font-size: 0.92rem;"><?= e($cr['title']) ?></div>
                                <div style="font-size: 0.78rem; color: var(--text-muted);">
                                    By <?= e($cr['student_name']) ?> • 📍 <?= e($cr['building_block']) ?>
                                </div>
                            </div>
                            <a href="<?= url('/requests/view?id=' . $cr['id']) ?>" class="btn btn-outline btn-sm" style="font-size: 0.8rem; padding: 4px 10px;">
                                View
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 30px; color: var(--text-muted);">
                    <div style="font-size: 2rem; margin-bottom: 8px;">✅</div>
                    <p>No critical emergency requests are currently unresolved.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Audit Logs -->
    <div class="glass-card" style="padding: 24px; border-radius: var(--radius-xl);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="font-size: 1.15rem; margin: 0;">📜 Recent Admin Activity</h3>
            <a href="<?= url('/admin/logs') ?>" style="font-size: 0.85rem; color: var(--primary); font-weight: 600;">View Full Log →</a>
        </div>

        <?php if (!empty($recentLogs)): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.88rem;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--border-glass); color: var(--text-muted); text-align: left;">
                            <th style="padding: 10px 12px;">Time</th>
                            <th style="padding: 10px 12px;">Administrator</th>
                            <th style="padding: 10px 12px;">Action</th>
                            <th style="padding: 10px 12px;">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentLogs as $log): ?>
                            <tr style="border-bottom: 1px solid var(--border-glass);">
                                <td style="padding: 10px 12px; color: var(--text-muted); font-size: 0.8rem;"><?= e($log['created_at']) ?></td>
                                <td style="padding: 10px 12px; font-weight: 600;"><?= e($log['admin_name']) ?></td>
                                <td style="padding: 10px 12px;"><span class="badge badge-normal"><?= e($log['action']) ?></span></td>
                                <td style="padding: 10px 12px; color: var(--text-muted);"><?= e($log['details'] ?? 'N/A') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="color: var(--text-muted); font-size: 0.9rem;">No audit logs recorded yet.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Chart.js Script via CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Requests Per Day Chart
    const dayData = <?= json_encode($requestsPerDay) ?>;
    const dayLabels = dayData.map(d => d.day);
    const dayCounts = dayData.map(d => parseInt(d.count, 10));

    const ctxDays = document.getElementById('requestsChart');
    if (ctxDays && dayLabels.length > 0) {
        new Chart(ctxDays, {
            type: 'line',
            data: {
                labels: dayLabels,
                datasets: [{
                    label: 'Requests',
                    data: dayCounts,
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2,
                    pointBackgroundColor: '#4f46e5',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    }

    // 2. High Demand Categories Chart
    const catData = <?= json_encode($topCategories) ?>;
    const catLabels = catData.map(c => c.name);
    const catCounts = catData.map(c => parseInt(c.req_count, 10));

    const ctxCats = document.getElementById('categoriesChart');
    if (ctxCats && catLabels.length > 0) {
        new Chart(ctxCats, {
            type: 'bar',
            data: {
                labels: catLabels,
                datasets: [{
                    label: 'Emergency Requests',
                    data: catCounts,
                    backgroundColor: ['#4f46e5', '#06b6d4', '#10b981', '#f59e0b', '#ec4899'],
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    }
});
</script>
