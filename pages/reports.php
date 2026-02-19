<?php
require_once __DIR__ . '/../includes/layout.php';
$user = Auth::getCurrentUser();
if (!$user) { header('Location: /v1/pages/login.php'); exit; }
if (!in_array($user['role_name'], ['admin', 'lab_manager', 'ceo'])) { header('Location: /v1/'); exit; }
$lang = I18n::getCurrentLang();
Layout::head(__('reports_title'), [], ['https://cdn.jsdelivr.net/npm/chart.js']);
?>
<body>
<?php Layout::sidebar('reports'); Layout::beginContent(); ?>

<!-- Page Header with Print Button -->
<div class="ci-pg-hdr">
    <div>
        <div class="ci-pg-title"><i class="fas fa-chart-bar" style="color:var(--accent);margin-right:8px"></i> <?php echo __('reports_title'); ?></div>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
        <button class="ci-btn ci-btn-outline" onclick="window.print()" title="<?php echo __('reports_export'); ?>">
            <i class="fas fa-print"></i> <?php echo __('reports_export'); ?>
        </button>
    </div>
</div>

<!-- Summary Stats (6 cards) -->
<div class="ci-stats" style="margin-bottom:20px">
    <div class="ci-stat">
        <div class="ci-stat-icon blue"><i class="fas fa-flask"></i></div>
        <div><div class="ci-stat-val" id="sChemicals">-</div><div class="ci-stat-lbl"><?php echo __('stat_total_chemicals'); ?></div></div>
    </div>
    <div class="ci-stat">
        <div class="ci-stat-icon green"><i class="fas fa-box"></i></div>
        <div><div class="ci-stat-val" id="sContainers">-</div><div class="ci-stat-lbl"><?php echo __('stat_active_containers'); ?></div></div>
    </div>
    <div class="ci-stat">
        <div class="ci-stat-icon red"><i class="fas fa-times-circle"></i></div>
        <div><div class="ci-stat-val" id="sExpired">-</div><div class="ci-stat-lbl"><?php echo __('stat_expired_containers'); ?></div></div>
    </div>
    <div class="ci-stat">
        <div class="ci-stat-icon orange"><i class="fas fa-clock"></i></div>
        <div><div class="ci-stat-val" id="sExpiring">-</div><div class="ci-stat-lbl"><?php echo __('stat_expiring_soon'); ?></div></div>
    </div>
    <div class="ci-stat">
        <div class="ci-stat-icon purple"><i class="fas fa-users"></i></div>
        <div><div class="ci-stat-val" id="sUsers">-</div><div class="ci-stat-lbl"><?php echo __('stat_total_users'); ?></div></div>
    </div>
    <div class="ci-stat">
        <div class="ci-stat-icon teal"><i class="fas fa-building"></i></div>
        <div><div class="ci-stat-val" id="sLabs">-</div><div class="ci-stat-lbl"><?php echo __('stat_total_labs'); ?></div></div>
    </div>
</div>

<!-- Tabs -->
<div class="ci-tabs" id="reportTabs">
    <button class="ci-tab active" data-tab="overview"><i class="fas fa-chart-pie"></i> <?php echo __('reports_tab_overview'); ?></button>
    <button class="ci-tab" data-tab="labs"><i class="fas fa-building"></i> <?php echo __('reports_tab_labs'); ?></button>
    <button class="ci-tab" data-tab="expiring"><i class="fas fa-clock"></i> <?php echo __('reports_tab_expiring'); ?></button>
    <button class="ci-tab" data-tab="lowstock"><i class="fas fa-exclamation-triangle"></i> <?php echo __('reports_tab_low_stock'); ?></button>
</div>

<!-- Tab: Overview Charts -->
<div class="tab-panel active" id="panel-overview">
    <div class="ci-g2" style="margin-bottom:16px">
        <div class="ci-card">
            <div class="ci-card-head">
                <span><i class="fas fa-building" style="color:#7b1fa2"></i> <?php echo __('reports_stock_by_lab'); ?></span>
            </div>
            <div class="ci-card-body">
                <div style="height:320px;position:relative"><canvas id="labChart"></canvas></div>
            </div>
        </div>
        <div class="ci-card">
            <div class="ci-card-head">
                <span><i class="fas fa-exchange-alt" style="color:var(--warn)"></i> <?php echo __('reports_borrow_activity'); ?></span>
            </div>
            <div class="ci-card-body">
                <div style="height:320px;position:relative"><canvas id="borrowChart"></canvas></div>
            </div>
        </div>
    </div>
    <div class="ci-g2">
        <div class="ci-card">
            <div class="ci-card-head">
                <span><i class="fas fa-shield-alt" style="color:var(--accent)"></i> <?php echo __('reports_compliance'); ?></span>
            </div>
            <div class="ci-card-body">
                <div id="complianceContent" style="min-height:200px">
                    <div class="ci-loading"><div class="ci-spinner"></div></div>
                </div>
            </div>
        </div>
        <div class="ci-card">
            <div class="ci-card-head">
                <span><i class="fas fa-chart-area" style="color:#1976d2"></i> <?php echo __('reports_usage_trend'); ?></span>
            </div>
            <div class="ci-card-body">
                <div style="height:260px;position:relative"><canvas id="trendChart"></canvas></div>
            </div>
        </div>
    </div>
</div>

<!-- Tab: Lab Performance -->
<div class="tab-panel" id="panel-labs">
    <div class="ci-card">
        <div class="ci-card-head">
            <span><i class="fas fa-building" style="color:#7b1fa2"></i> <?php echo __('reports_lab_performance'); ?></span>
            <span class="ci-badge ci-badge-default" id="labCount">0</span>
        </div>
        <div class="ci-card-body" style="padding:0">
            <div id="labTableWrap">
                <div class="ci-loading" style="padding:40px"><div class="ci-spinner"></div></div>
            </div>
        </div>
    </div>
</div>

<!-- Tab: Expiring / Expired -->
<div class="tab-panel" id="panel-expiring">
    <div class="ci-card">
        <div class="ci-card-head">
            <span><i class="fas fa-clock" style="color:var(--warn)"></i> <?php echo __('reports_expiring_items'); ?></span>
            <span class="ci-badge ci-badge-warning" id="expiringCount">0</span>
        </div>
        <div class="ci-card-body" style="padding:0">
            <div id="expiringTableWrap">
                <div class="ci-loading" style="padding:40px"><div class="ci-spinner"></div></div>
            </div>
        </div>
    </div>
</div>

<!-- Tab: Low Stock -->
<div class="tab-panel" id="panel-lowstock">
    <div class="ci-card">
        <div class="ci-card-head">
            <span><i class="fas fa-exclamation-triangle" style="color:var(--danger)"></i> <?php echo __('reports_low_stock_items'); ?></span>
            <span class="ci-badge ci-badge-danger" id="lowStockCount">0</span>
        </div>
        <div class="ci-card-body" style="padding:0">
            <div id="lowStockTableWrap">
                <div class="ci-loading" style="padding:40px"><div class="ci-spinner"></div></div>
            </div>
        </div>
    </div>
</div>

<!-- Expiring Item Detail Modal -->
<div class="rpt-modal-overlay" id="expDetailOverlay" onclick="closeExpDetail(event)">
    <div class="rpt-modal" onclick="event.stopPropagation()">
        <div class="rpt-modal-hdr">
            <div class="rpt-modal-hdr-left">
                <i class="fas fa-clock" style="color:var(--warn)"></i>
                <span id="expModalTitle"><?php echo __('details'); ?></span>
            </div>
            <button class="rpt-modal-close" onclick="closeExpDetail(event)" title="Close">&times;</button>
        </div>
        <div class="rpt-modal-body" id="expModalBody">
            <div class="ci-loading"><div class="ci-spinner"></div></div>
        </div>
    </div>
</div>

<!-- Low Stock Item Detail Modal -->
<div class="rpt-modal-overlay" id="lsDetailOverlay" onclick="closeLsDetail(event)">
    <div class="rpt-modal" onclick="event.stopPropagation()">
        <div class="rpt-modal-hdr" style="background:linear-gradient(135deg,#fee2e2 0%,#fecaca 100%)">
            <div class="rpt-modal-hdr-left" style="color:#991b1b">
                <i class="fas fa-exclamation-triangle"></i>
                <span id="lsModalTitle"><?php echo __('details'); ?></span>
            </div>
            <button class="rpt-modal-close" onclick="closeLsDetail(event)" title="Close" style="color:#991b1b">&times;</button>
        </div>
        <div class="rpt-modal-body" id="lsModalBody">
            <div class="ci-loading"><div class="ci-spinner"></div></div>
        </div>
    </div>
</div>

<?php Layout::endContent(); ?>

<style>
.tab-panel { display: none; }
.tab-panel.active { display: block; animation: fadeIn .2s ease-out; }
@media print {
    .ci-sidebar, .ci-tab, .ci-pg-hdr .ci-btn, .ci-tabs { display: none !important; }
    .ci-main { margin-left: 0 !important; padding: 10px !important; }
    .tab-panel { display: block !important; page-break-inside: avoid; margin-bottom: 20px; }
    .ci-g2 { grid-template-columns: 1fr 1fr !important; }
}
.rpt-progress-wrap { display: flex; align-items: center; gap: 8px; }
.rpt-progress-wrap .ci-progress { flex: 1; height: 8px; }
.rpt-progress-wrap span { font-size: 12px; font-weight: 600; min-width: 38px; text-align: right; }
.compliance-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
.compliance-item { text-align: center; padding: 20px 12px; border-radius: 6px; }
.compliance-item .val { font-size: 32px; font-weight: 700; }
.compliance-item .lbl { font-size: 12px; color: var(--c2); margin-top: 4px; }
.comp-pass { background: #e8f5ef; }
.comp-pass .val { color: var(--ok); }
.comp-warn { background: #fff8e1; }
.comp-warn .val { color: var(--warn); }
.comp-fail { background: #ffebee; }
.comp-fail .val { color: var(--danger); }
@media(max-width:768px){
    .compliance-grid{grid-template-columns:1fr 1fr!important;gap:10px}
    .compliance-item .val{font-size:24px}
}
@media(max-width:480px){
    .compliance-grid{grid-template-columns:1fr!important}
}

/* Expiring items clickable rows */
.exp-row-click { cursor: pointer; transition: background .15s; }
.exp-row-click:hover { background: rgba(26,138,92,0.06) !important; }
.exp-row-click td:first-child { position: relative; padding-left: 20px; }
.exp-row-click td:first-child::before {
    content: '';
    position: absolute; left: 0; top: 0; bottom: 0; width: 3px;
    background: var(--warn); border-radius: 0 3px 3px 0;
    opacity: 0; transition: opacity .15s;
}
.exp-row-click:hover td:first-child::before { opacity: 1; }
.exp-row-click .row-arrow { opacity: 0; transition: opacity .15s; color: var(--c3); font-size: 11px; margin-left: 6px; }
.exp-row-click:hover .row-arrow { opacity: 1; }

/* Low stock clickable rows */
.ls-row-click { cursor: pointer; transition: background .15s; }
.ls-row-click:hover { background: rgba(217,83,79,0.05) !important; }
.ls-row-click td:first-child { position: relative; padding-left: 20px; }
.ls-row-click td:first-child::before {
    content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px;
    background: var(--danger); border-radius: 0 3px 3px 0;
    opacity: 0; transition: opacity .15s;
}
.ls-row-click:hover td:first-child::before { opacity: 1; }
.ls-row-click .row-arrow { opacity: 0; transition: opacity .15s; color: var(--c3); font-size: 11px; margin-left: 6px; }
.ls-row-click:hover .row-arrow { opacity: 1; }

/* Low stock status hero */
.ls-status-hero {
    display: flex; align-items: center; gap: 16px;
    padding: 16px; margin-bottom: 16px; border-radius: 8px;
}
.ls-status-hero.critical { background: #fef2f2; border: 1px solid #fecaca; }
.ls-status-hero.critical .ls-status-icon { background: #fee2e2; color: var(--danger); }
.ls-status-hero.low { background: #fffbeb; border: 1px solid #fde68a; }
.ls-status-hero.low .ls-status-icon { background: #fef3c7; color: #d97706; }
.ls-status-icon {
    width: 48px; height: 48px; border-radius: 50%; display: flex;
    align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;
}
.ls-status-text h4 { margin: 0 0 2px; font-size: 15px; font-weight: 700; }
.ls-status-text p { margin: 0; font-size: 12px; color: var(--c2,#64748b); }

/* Gauge mini */
.ls-gauge {
    width: 80px; height: 80px; border-radius: 50%; position: relative;
    display: flex; align-items: center; justify-content: center; margin: 0 auto;
}
.ls-gauge-val { font-size: 18px; font-weight: 800; }

/* Expiring detail modal */
.rpt-modal-overlay {
    display: none; position: fixed; inset: 0; z-index: 9999;
    background: rgba(0,0,0,0.45); backdrop-filter: blur(3px);
    justify-content: center; align-items: center; padding: 16px;
}
.rpt-modal-overlay.show { display: flex; animation: fadeIn .15s ease-out; }
.rpt-modal {
    background: var(--card-bg,#fff); border-radius: 12px;
    width: 100%; max-width: 540px; max-height: 90vh;
    box-shadow: 0 20px 60px rgba(0,0,0,0.25); overflow: hidden;
    animation: slideUp .2s ease-out;
}
@keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
.rpt-modal-hdr {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 20px; border-bottom: 1px solid var(--border,#e5e7eb);
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
}
.rpt-modal-hdr-left { display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 15px; color: #92400e; }
.rpt-modal-close {
    border: none; background: rgba(0,0,0,0.08); width: 32px; height: 32px;
    border-radius: 50%; font-size: 20px; cursor: pointer; display: flex;
    align-items: center; justify-content: center; color: #92400e; transition: background .15s;
}
.rpt-modal-close:hover { background: rgba(0,0,0,0.15); }
.rpt-modal-body { padding: 20px; overflow-y: auto; max-height: calc(90vh - 60px); }

/* Detail grid */
.exp-detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
.exp-detail-item {
    padding: 14px 16px; border-bottom: 1px solid var(--border,#f0f0f0);
}
.exp-detail-item:nth-child(odd) { border-right: 1px solid var(--border,#f0f0f0); }
.exp-detail-item.full { grid-column: 1 / -1; border-right: none; }
.exp-detail-lbl { font-size: 11px; color: var(--c3,#94a3b8); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; font-weight: 600; }
.exp-detail-val { font-size: 14px; color: var(--c1,#1e293b); font-weight: 500; word-break: break-all; }
.exp-detail-val .ci-badge { font-size: 11px; }

/* Status hero area */
.exp-status-hero {
    display: flex; align-items: center; gap: 16px;
    padding: 16px; margin-bottom: 16px; border-radius: 8px;
}
.exp-status-hero.danger { background: #fef2f2; border: 1px solid #fecaca; }
.exp-status-hero.warning { background: #fffbeb; border: 1px solid #fde68a; }
.exp-status-hero.info { background: #eff6ff; border: 1px solid #bfdbfe; }
.exp-status-icon {
    width: 48px; height: 48px; border-radius: 50%; display: flex;
    align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;
}
.exp-status-hero.danger .exp-status-icon { background: #fee2e2; color: var(--danger); }
.exp-status-hero.warning .exp-status-icon { background: #fef3c7; color: #d97706; }
.exp-status-hero.info .exp-status-icon { background: #dbeafe; color: #2563eb; }
.exp-status-text h4 { margin: 0 0 2px; font-size: 15px; font-weight: 700; }
.exp-status-text p { margin: 0; font-size: 12px; color: var(--c2,#64748b); }

/* Barcode display */
.exp-barcode {
    display: inline-flex; align-items: center; gap: 8px;
    background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 6px;
    padding: 6px 12px; font-family: 'Courier New', monospace; font-size: 13px;
    font-weight: 600; color: #334155; letter-spacing: 1px;
}
.exp-barcode i { color: var(--c3); }

@media(max-width:600px){
    .exp-detail-grid { grid-template-columns: 1fr; }
    .exp-detail-item:nth-child(odd) { border-right: none; }
    .rpt-modal { max-width: 100%; margin: 8px; border-radius: 10px; }
}
@media print { .rpt-modal-overlay { display: none !important; } }
</style>

<script>
const gridColor = 'rgba(0,0,0,0.06)', txtColor = '#64748b';
Chart.defaults.color = txtColor;
Chart.defaults.borderColor = gridColor;
Chart.defaults.plugins.legend.labels.usePointStyle = true;
Chart.defaults.plugins.legend.labels.pointStyle = 'circle';

// Tab switching
document.querySelectorAll('#reportTabs .ci-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('#reportTabs .ci-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById('panel-' + tab.dataset.tab).classList.add('active');
    });
});

let reportData = null;

async function loadReport() {
    try {
        const d = await apiFetch('/v1/api/dashboard.php');
        if (d.success && d.data) {
            reportData = d.data;
            const s = d.data.summary || d.data.stats || d.data;

            // Populate stat cards
            setText('sChemicals', s.total_chemicals);
            setText('sContainers', s.active_containers);
            setText('sExpired', s.expired_containers || '0');
            setText('sExpiring', (d.data.expiring_soon || []).length);
            setText('sUsers', s.total_users || '-');
            setText('sLabs', s.total_labs || '-');

            renderCharts(d.data);
            renderLabTable(d.data.lab_performance || []);
            renderExpiringTable(d.data.expiring_soon || []);
            renderLowStockTable(d.data.low_stock || []);
            renderCompliance(d.data.compliance_status || {});
        }
    } catch (e) {
        console.error('Report load error:', e);
        renderCharts({});
    }
}

function setText(id, val) {
    const el = document.getElementById(id);
    if (el && val !== undefined) el.textContent = val;
}

function renderCharts(d) {
    // Lab stock bar chart
    const labs = d.lab_performance || [];
    const labLabels = labs.length ? labs.map(l => (l.name || '').replace('ห้องปฏิบัติการ', '').trim().substring(0, 20)) : ['<?php echo __("no_data"); ?>'];
    const labData = labs.length ? labs.map(l => parseInt(l.container_count) || 0) : [0];

    new Chart(document.getElementById('labChart'), {
        type: 'bar',
        data: {
            labels: labLabels,
            datasets: [
                {
                    label: '<?php echo __("nav_containers"); ?>',
                    data: labData,
                    backgroundColor: 'rgba(26,138,92,0.75)',
                    borderRadius: 4,
                    borderSkipped: false,
                    barPercentage: 0.6
                },
                {
                    label: '<?php echo __("stat_team_members"); ?>',
                    data: labs.length ? labs.map(l => parseInt(l.user_count) || 0) : [0],
                    backgroundColor: 'rgba(25,118,210,0.65)',
                    borderRadius: 4,
                    borderSkipped: false,
                    barPercentage: 0.6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top', labels: { padding: 12, font: { size: 12 } } } },
            scales: {
                y: { beginAtZero: true, grid: { color: gridColor }, ticks: { stepSize: 1 } },
                x: { grid: { display: false } }
            }
        }
    });

    // Borrow activity per lab (horizontal bar)
    const borrowLabels = labs.length ? labs.map(l => (l.name || '').replace('ห้องปฏิบัติการ', '').trim().substring(0, 20)) : [];
    const borrowReqs = labs.length ? labs.map(l => parseInt(l.borrow_requests) || 0) : [];
    const overdueReqs = labs.length ? labs.map(l => parseInt(l.overdue_borrows) || 0) : [];

    new Chart(document.getElementById('borrowChart'), {
        type: 'bar',
        data: {
            labels: borrowLabels,
            datasets: [
                {
                    label: '<?php echo __("reports_borrow_requests"); ?>',
                    data: borrowReqs,
                    backgroundColor: 'rgba(139,92,246,0.7)',
                    borderRadius: 4,
                    borderSkipped: false,
                    barPercentage: 0.5
                },
                {
                    label: '<?php echo __("reports_overdue"); ?>',
                    data: overdueReqs,
                    backgroundColor: 'rgba(239,68,68,0.7)',
                    borderRadius: 4,
                    borderSkipped: false,
                    barPercentage: 0.5
                }
            ]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top', labels: { padding: 12, font: { size: 12 } } } },
            scales: {
                x: { beginAtZero: true, grid: { color: gridColor }, ticks: { stepSize: 1 } },
                y: { grid: { display: false } }
            }
        }
    });

    // Usage trend line chart
    const trend = d.usage_trend || [];
    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: trend.length ? trend.map(t => t.month) : ['<?php echo __("no_data"); ?>'],
            datasets: [{
                label: '<?php echo __("reports_transactions"); ?>',
                data: trend.length ? trend.map(t => parseInt(t.transactions) || 0) : [0],
                borderColor: '#1976d2',
                backgroundColor: 'rgba(25,118,210,0.08)',
                tension: 0.4,
                fill: true,
                pointRadius: 4,
                pointBackgroundColor: '#1976d2'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: gridColor } },
                x: { grid: { display: false } }
            }
        }
    });
}

function renderCompliance(c) {
    const passed = parseInt(c.passed) || 0;
    const warnings = parseInt(c.warnings) || 0;
    const failed = parseInt(c.failed) || 0;
    const total = passed + warnings + failed;

    let html = '<div class="compliance-grid">';
    html += `<div class="compliance-item comp-pass"><div class="val">${passed}</div><div class="lbl"><?php echo __("reports_comp_passed"); ?></div></div>`;
    html += `<div class="compliance-item comp-warn"><div class="val">${warnings}</div><div class="lbl"><?php echo __("reports_comp_warnings"); ?></div></div>`;
    html += `<div class="compliance-item comp-fail"><div class="val">${failed}</div><div class="lbl"><?php echo __("reports_comp_failed"); ?></div></div>`;
    html += '</div>';

    if (total === 0) {
        html += '<div style="text-align:center;padding:12px 0;color:var(--c3);font-size:13px"><i class="fas fa-check-circle" style="color:var(--ok);margin-right:4px"></i> <?php echo __("reports_no_compliance_issues"); ?></div>';
    }
    document.getElementById('complianceContent').innerHTML = html;
}

function renderLabTable(labs) {
    document.getElementById('labCount').textContent = labs.length;
    if (!labs.length) {
        document.getElementById('labTableWrap').innerHTML = '<div class="ci-empty"><i class="fas fa-building"></i><div><?php echo __("no_data"); ?></div></div>';
        return;
    }
    const maxContainers = Math.max(...labs.map(l => parseInt(l.container_count) || 0), 1);
    let html = '<div class="ci-table-wrap"><table class="ci-table"><thead><tr>';
    html += '<th><?php echo __("stat_total_labs"); ?></th>';
    html += '<th style="text-align:center"><?php echo __("nav_containers"); ?></th>';
    html += '<th style="text-align:center"><?php echo __("stat_team_members"); ?></th>';
    html += '<th style="text-align:center"><?php echo __("reports_borrow_requests"); ?></th>';
    html += '<th style="text-align:center"><?php echo __("reports_overdue"); ?></th>';
    html += '<th style="min-width:140px"><?php echo __("reports_capacity"); ?></th>';
    html += '</tr></thead><tbody>';

    labs.forEach(lab => {
        const cnt = parseInt(lab.container_count) || 0;
        const pct = Math.round((cnt / maxContainers) * 100);
        const pctClass = pct > 70 ? 'ci-progress-green' : pct > 30 ? 'ci-progress-orange' : 'ci-progress-red';
        const overdue = parseInt(lab.overdue_borrows) || 0;
        html += '<tr>';
        html += `<td><strong>${lab.name}</strong></td>`;
        html += `<td style="text-align:center"><span class="ci-badge ci-badge-primary">${cnt}</span></td>`;
        html += `<td style="text-align:center">${lab.user_count || 0}</td>`;
        html += `<td style="text-align:center">${lab.borrow_requests || 0}</td>`;
        html += `<td style="text-align:center">${overdue > 0 ? '<span class="ci-badge ci-badge-danger">' + overdue + '</span>' : '<span class="text-muted">0</span>'}</td>`;
        html += `<td><div class="rpt-progress-wrap"><div class="ci-progress"><div class="ci-progress-bar ${pctClass}" style="width:${pct}%"></div></div><span>${pct}%</span></div></td>`;
        html += '</tr>';
    });
    html += '</tbody></table></div>';
    document.getElementById('labTableWrap').innerHTML = html;
}

let expiringItems = [];

function renderExpiringTable(items) {
    expiringItems = items;
    document.getElementById('expiringCount').textContent = items.length;
    if (!items.length) {
        document.getElementById('expiringTableWrap').innerHTML = '<div class="ci-empty"><i class="fas fa-check-circle" style="color:var(--ok)"></i><div><?php echo __("reports_no_expiring"); ?></div></div>';
        return;
    }
    let html = '<div class="ci-table-wrap"><table class="ci-table"><thead><tr>';
    html += '<th><?php echo __("chemicals_name"); ?></th>';
    html += '<th>Barcode</th>';
    html += '<th><?php echo __("reports_owner"); ?></th>';
    html += '<th><?php echo __("containers_location"); ?></th>';
    html += '<th style="text-align:right"><?php echo __("containers_quantity"); ?></th>';
    html += '<th><?php echo __("containers_expiry"); ?></th>';
    html += '<th style="text-align:center"><?php echo __("reports_days_left"); ?></th>';
    html += '<th><?php echo __("containers_status"); ?></th>';
    html += '</tr></thead><tbody>';

    items.forEach((item, idx) => {
        const days = parseInt(item.days_until_expiry) || 0;
        let badge, statusText;
        if (days < 0) {
            badge = 'ci-badge-danger';
            statusText = '<?php echo __("status_expired"); ?>';
        } else if (days <= 7) {
            badge = 'ci-badge-danger';
            statusText = '<?php echo __("reports_critical"); ?>';
        } else if (days <= 30) {
            badge = 'ci-badge-warning';
            statusText = '<?php echo __("reports_warning_status"); ?>';
        } else {
            badge = 'ci-badge-info';
            statusText = '<?php echo __("reports_ok_status"); ?>';
        }

        const qty = parseFloat(item.current_quantity) || 0;
        const unit = item.quantity_unit || 'mL';
        const qtyStr = qty >= 1000 && unit === 'mL' ? (qty / 1000).toFixed(1) + ' L' : qty.toFixed(0) + ' ' + unit;
        const ownerName = [item.owner_first, item.owner_last].filter(Boolean).join(' ') || '-';
        const barcode = item.bottle_code || item.qr_code || '-';
        const shortBarcode = barcode.length > 16 ? barcode.substring(0, 16) + '…' : barcode;

        html += `<tr class="exp-row-click" onclick="showExpDetail(${idx})" title="<?php echo __('view_details'); ?>">`;
        html += `<td><strong>${item.name}</strong><i class="fas fa-chevron-right row-arrow"></i></td>`;
        html += `<td><code style="font-size:11px;color:#475569;background:#f1f5f9;padding:2px 6px;border-radius:3px">${shortBarcode}</code></td>`;
        html += `<td style="font-size:12px">${ownerName}</td>`;
        html += `<td style="color:var(--c2);font-size:12px">${item.lab_name || '-'}</td>`;
        html += `<td style="text-align:right">${qtyStr}</td>`;
        html += `<td>${formatDate(item.expiry_date)}</td>`;
        html += `<td style="text-align:center;font-weight:600;color:${days < 0 ? 'var(--danger)' : days <= 7 ? 'var(--danger)' : 'var(--c1)'}">${days}d</td>`;
        html += `<td><span class="ci-badge ${badge}">${statusText}</span></td>`;
        html += '</tr>';
    });
    html += '</tbody></table></div>';
    document.getElementById('expiringTableWrap').innerHTML = html;
}

function showExpDetail(idx) {
    const item = expiringItems[idx];
    if (!item) return;

    const days = parseInt(item.days_until_expiry) || 0;
    let heroClass, heroIcon, heroTitle, heroDesc;
    if (days < 0) {
        heroClass = 'danger'; heroIcon = 'fas fa-exclamation-circle';
        heroTitle = '<?php echo __("status_expired"); ?>' + ' (' + Math.abs(days) + ' <?php echo $lang==="th"?"วันที่แล้ว":"days ago"; ?>)';
        heroDesc = '<?php echo $lang==="th"?"ภาชนะนี้หมดอายุแล้ว ควรดำเนินการจัดการ":"This container has expired and needs attention"; ?>';
    } else if (days <= 7) {
        heroClass = 'danger'; heroIcon = 'fas fa-exclamation-triangle';
        heroTitle = '<?php echo __("reports_critical"); ?>' + ' — ' + days + ' <?php echo $lang==="th"?"วันเหลือ":"days left"; ?>';
        heroDesc = '<?php echo $lang==="th"?"ใกล้หมดอายุมาก ควรใช้งานโดยเร็ว":"Critically close to expiry, use soon"; ?>';
    } else if (days <= 30) {
        heroClass = 'warning'; heroIcon = 'fas fa-clock';
        heroTitle = '<?php echo __("reports_warning_status"); ?>' + ' — ' + days + ' <?php echo $lang==="th"?"วันเหลือ":"days left"; ?>';
        heroDesc = '<?php echo $lang==="th"?"จะหมดอายุเร็ว ๆ นี้":"Will expire soon"; ?>';
    } else {
        heroClass = 'info'; heroIcon = 'fas fa-info-circle';
        heroTitle = days + ' <?php echo $lang==="th"?"วันเหลือ":"days remaining"; ?>';
        heroDesc = '<?php echo $lang==="th"?"ยังมีเวลาก่อนหมดอายุ":"Still has time before expiry"; ?>';
    }

    const qty = parseFloat(item.current_quantity) || 0;
    const unit = item.quantity_unit || 'mL';
    const qtyStr = qty >= 1000 && unit === 'mL' ? (qty / 1000).toFixed(1) + ' L' : qty.toFixed(0) + ' ' + unit;
    const pct = parseFloat(item.remaining_percentage) || 0;
    const ownerName = [item.owner_first, item.owner_last].filter(Boolean).join(' ') || '-';
    const barcode = item.bottle_code || item.qr_code || '-';
    const cas = item.cas_number || '-';
    const loc = item.location_path || item.lab_name || '-';
    const ctype = item.container_type || '-';
    const grade = item.grade || '-';

    document.getElementById('expModalTitle').textContent = item.name;

    let html = '';
    // Status hero
    html += `<div class="exp-status-hero ${heroClass}">`;
    html += `<div class="exp-status-icon"><i class="${heroIcon}"></i></div>`;
    html += `<div class="exp-status-text"><h4>${heroTitle}</h4><p>${heroDesc}</p></div>`;
    html += '</div>';

    // Barcode display
    if (barcode !== '-') {
        html += '<div style="text-align:center;margin-bottom:16px">';
        html += `<div class="exp-barcode"><i class="fas fa-barcode"></i> ${barcode}</div>`;
        html += '</div>';
    }

    // Detail grid
    html += '<div class="exp-detail-grid">';
    html += detailItem('<?php echo __("chemicals_name"); ?>', item.name);
    html += detailItem('CAS No.', cas);
    html += detailItem('<?php echo __("reports_owner"); ?>', `<i class="fas fa-user" style="color:var(--c3);margin-right:4px;font-size:11px"></i>${ownerName}`);
    html += detailItem('<?php echo __("containers_location"); ?>', `<i class="fas fa-map-marker-alt" style="color:var(--danger);margin-right:4px;font-size:11px"></i>${loc}`);
    html += detailItem('<?php echo __("containers_quantity"); ?>', qtyStr);
    html += detailItem('<?php echo __("reports_remaining"); ?>', `<div class="rpt-progress-wrap" style="min-width:100px"><div class="ci-progress" style="flex:1;height:6px"><div class="ci-progress-bar ${pct<=15?'ci-progress-red':pct<=40?'ci-progress-orange':'ci-progress-green'}" style="width:${pct}%"></div></div><span style="font-size:11px">${pct.toFixed(1)}%</span></div>`);
    html += detailItem('<?php echo __("containers_expiry"); ?>', formatDate(item.expiry_date));
    html += detailItem('<?php echo $lang==="th"?"ประเภทภาชนะ":"Container Type"; ?>', ctype);
    if (grade !== '-') html += detailItem('Grade', grade);
    html += detailItem('<?php echo $lang==="th"?"ห้องปฏิบัติการ":"Lab"; ?>', item.lab_name || '-');
    html += '</div>';

    // Container ID reference
    html += `<div style="text-align:center;margin-top:16px;padding-top:12px;border-top:1px solid var(--border,#e5e7eb)">`;
    html += `<span style="font-size:11px;color:var(--c3)">Container ID: #${item.container_id || '-'}</span>`;
    html += '</div>';

    document.getElementById('expModalBody').innerHTML = html;
    document.getElementById('expDetailOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function detailItem(label, value) {
    return `<div class="exp-detail-item"><div class="exp-detail-lbl">${label}</div><div class="exp-detail-val">${value}</div></div>`;
}

function closeExpDetail(e) {
    const overlay = document.getElementById('expDetailOverlay');
    // Close if: clicking overlay background, clicking close button, or called directly
    if (e && e.target) {
        const isOverlay = e.target === overlay;
        const isCloseBtn = e.target.closest('.rpt-modal-close');
        if (!isOverlay && !isCloseBtn) return;
    }
    overlay.classList.remove('show');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        closeExpDetail({target: document.getElementById('expDetailOverlay')});
        closeLsDetail({target: document.getElementById('lsDetailOverlay')});
    }
});

let lowStockItems = [];

function renderLowStockTable(items) {
    lowStockItems = items;
    document.getElementById('lowStockCount').textContent = items.length;
    if (!items.length) {
        document.getElementById('lowStockTableWrap').innerHTML = '<div class="ci-empty"><i class="fas fa-check-circle" style="color:var(--ok)"></i><div><?php echo __("reports_no_low_stock"); ?></div></div>';
        return;
    }
    let html = '<div class="ci-table-wrap"><table class="ci-table"><thead><tr>';
    html += '<th><?php echo __("chemicals_name"); ?></th>';
    html += '<th>Barcode</th>';
    html += '<th><?php echo __("chemicals_cas"); ?></th>';
    html += '<th><?php echo __("reports_owner"); ?></th>';
    html += '<th><?php echo __("containers_location"); ?></th>';
    html += '<th style="text-align:right"><?php echo __("containers_quantity"); ?></th>';
    html += '<th><?php echo __("reports_remaining"); ?></th>';
    html += '</tr></thead><tbody>';

    items.forEach((item, idx) => {
        const pct = parseFloat(item.remaining_percentage) || 0;
        const pctClass = pct <= 5 ? 'ci-progress-red' : pct <= 15 ? 'ci-progress-orange' : 'ci-progress-green';
        const qty = parseFloat(item.current_quantity) || 0;
        const qtyStr = qty + ' ' + (item.quantity_unit || 'mL');
        const ownerName = [item.first_name, item.last_name].filter(Boolean).join(' ') || '-';
        const barcode = item.bottle_code || item.qr_code || '-';
        const shortBarcode = barcode.length > 16 ? barcode.substring(0, 16) + '\u2026' : barcode;

        html += `<tr class="ls-row-click" onclick="showLsDetail(${idx})" title="<?php echo __('view_details'); ?>">`;
        html += `<td><strong>${item.name}</strong><i class="fas fa-chevron-right row-arrow"></i></td>`;
        html += `<td><code style="font-size:11px;color:#475569;background:#f1f5f9;padding:2px 6px;border-radius:3px">${shortBarcode}</code></td>`;
        html += `<td style="color:var(--c3);font-size:12px">${item.cas_number || '-'}</td>`;
        html += `<td style="font-size:12px">${ownerName}</td>`;
        html += `<td style="color:var(--c2);font-size:12px">${item.lab_name || '-'}</td>`;
        html += `<td style="text-align:right">${qtyStr}</td>`;
        html += `<td><div class="rpt-progress-wrap"><div class="ci-progress"><div class="ci-progress-bar ${pctClass}" style="width:${pct}%"></div></div><span>${pct.toFixed(1)}%</span></div></td>`;
        html += '</tr>';
    });
    html += '</tbody></table></div>';
    document.getElementById('lowStockTableWrap').innerHTML = html;
}

function showLsDetail(idx) {
    const item = lowStockItems[idx];
    if (!item) return;

    const pct = parseFloat(item.remaining_percentage) || 0;
    const isCritical = pct <= 5;
    const heroClass = isCritical ? 'critical' : 'low';
    const heroIcon = isCritical ? 'fas fa-times-circle' : 'fas fa-exclamation-triangle';
    const heroTitle = isCritical
        ? '<?php echo $lang==="th"?"วิกฤต — เหลือ":"Critical — Remaining"; ?> ' + pct.toFixed(1) + '%'
        : '<?php echo $lang==="th"?"สต็อกต่ำ — เหลือ":"Low Stock — Remaining"; ?> ' + pct.toFixed(1) + '%';
    const heroDesc = isCritical
        ? '<?php echo $lang==="th"?"ปริมาณเหลือน้อยมาก ควรสั่งซื้อเพิ่มโดยเร็ว":"Very low quantity, reorder urgently"; ?>'
        : '<?php echo $lang==="th"?"ปริมาณเหลือน้อย ควรวางแผนสั่งซื้อเพิ่ม":"Quantity is low, plan to reorder"; ?>';

    const qty = parseFloat(item.current_quantity) || 0;
    const initQty = parseFloat(item.initial_quantity) || 0;
    const unit = item.quantity_unit || 'mL';
    const qtyStr = qty + ' ' + unit;
    const initStr = initQty > 0 ? initQty + ' ' + unit : '-';
    const ownerName = [item.first_name, item.last_name].filter(Boolean).join(' ') || '-';
    const barcode = item.bottle_code || item.qr_code || '-';
    const cas = item.cas_number || '-';
    const loc = item.location_path || item.lab_name || '-';
    const ctype = item.container_type || '-';
    const grade = item.grade || '-';
    const pctClass = pct <= 5 ? '#ef4444' : pct <= 15 ? '#f59e0b' : '#22c55e';

    document.getElementById('lsModalTitle').textContent = item.name;

    let html = '';
    // Status hero
    html += `<div class="ls-status-hero ${heroClass}">`;
    html += `<div class="ls-status-icon"><i class="${heroIcon}"></i></div>`;
    html += `<div class="ls-status-text"><h4>${heroTitle}</h4><p>${heroDesc}</p></div>`;
    html += '</div>';

    // Gauge + Barcode row
    html += '<div style="display:flex;align-items:center;justify-content:center;gap:24px;margin-bottom:16px;flex-wrap:wrap">';
    // Circular gauge
    html += '<div style="text-align:center">';
    html += `<div class="ls-gauge" style="background:conic-gradient(${pctClass} 0% ${pct}%, #f1f5f9 ${pct}% 100%)">`;
    html += `<div style="width:60px;height:60px;border-radius:50%;background:var(--card-bg,#fff);display:flex;align-items:center;justify-content:center">`;
    html += `<span class="ls-gauge-val" style="color:${pctClass}">${pct.toFixed(0)}%</span>`;
    html += '</div></div>';
    html += `<div style="font-size:11px;color:var(--c3);margin-top:6px"><?php echo $lang==="th"?"คงเหลือ":"Remaining"; ?></div>`;
    html += '</div>';
    // Barcode
    if (barcode !== '-') {
        html += '<div style="text-align:center">';
        html += `<div class="exp-barcode"><i class="fas fa-barcode"></i> ${barcode}</div>`;
        html += `<div style="font-size:11px;color:var(--c3);margin-top:6px">Barcode</div>`;
        html += '</div>';
    }
    html += '</div>';

    // Detail grid
    html += '<div class="exp-detail-grid">';
    html += detailItem('<?php echo __("chemicals_name"); ?>', item.name);
    html += detailItem('CAS No.', cas);
    html += detailItem('<?php echo __("reports_owner"); ?>', `<i class="fas fa-user" style="color:var(--c3);margin-right:4px;font-size:11px"></i>${ownerName}`);
    html += detailItem('<?php echo __("containers_location"); ?>', `<i class="fas fa-map-marker-alt" style="color:var(--danger);margin-right:4px;font-size:11px"></i>${loc}`);
    html += detailItem('<?php echo $lang==="th"?"ปริมาณคงเหลือ":"Current Qty"; ?>', qtyStr);
    html += detailItem('<?php echo $lang==="th"?"ปริมาณเริ่มต้น":"Initial Qty"; ?>', initStr);
    html += detailItem('<?php echo __("reports_remaining"); ?>', `<div class="rpt-progress-wrap" style="min-width:100px"><div class="ci-progress" style="flex:1;height:6px"><div class="ci-progress-bar ${pct<=5?'ci-progress-red':pct<=15?'ci-progress-orange':'ci-progress-green'}" style="width:${pct}%"></div></div><span style="font-size:11px">${pct.toFixed(1)}%</span></div>`);
    html += detailItem('<?php echo __("containers_expiry"); ?>', item.expiry_date ? formatDate(item.expiry_date) : '-');
    html += detailItem('<?php echo $lang==="th"?"ประเภทภาชนะ":"Container Type"; ?>', ctype);
    if (grade !== '-') html += detailItem('Grade', grade);
    html += detailItem('<?php echo $lang==="th"?"ห้องปฏิบัติการ":"Lab"; ?>', item.lab_name || '-');
    html += '</div>';

    // Container ID reference
    html += `<div style="text-align:center;margin-top:16px;padding-top:12px;border-top:1px solid var(--border,#e5e7eb)">`;
    html += `<span style="font-size:11px;color:var(--c3)">Container ID: #${item.container_id || '-'}</span>`;
    html += '</div>';

    document.getElementById('lsModalBody').innerHTML = html;
    document.getElementById('lsDetailOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeLsDetail(e) {
    const overlay = document.getElementById('lsDetailOverlay');
    if (e && e.target) {
        const isOverlay = e.target === overlay;
        const isCloseBtn = e.target.closest('.rpt-modal-close');
        if (!isOverlay && !isCloseBtn) return;
    }
    overlay.classList.remove('show');
    document.body.style.overflow = '';
}

loadReport();
</script>
</body></html>
