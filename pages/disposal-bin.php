<?php
require_once __DIR__ . '/../includes/layout.php';
$user = Auth::getCurrentUser();
if (!$user) { header('Location: /v1/pages/login.php'); exit; }
$lang = I18n::getCurrentLang();
$userId = $user['id'];
$roleLevel = (int)($user['role_level'] ?? $user['level'] ?? 0);
$isAdmin = $roleLevel >= 5;
$isManager = $roleLevel >= 3;

if (!$isManager) { header('Location: /v1/pages/borrow.php'); exit; }

// Load filter options
$buildings = Database::fetchAll("SELECT DISTINCT building_name FROM disposal_bin WHERE building_name IS NOT NULL AND building_name != '' ORDER BY building_name");
$departments = Database::fetchAll("SELECT DISTINCT department FROM disposal_bin WHERE department IS NOT NULL AND department != '' ORDER BY department");
$disposers = Database::fetchAll("
    SELECT DISTINCT db.disposed_by, u.first_name, u.last_name 
    FROM disposal_bin db JOIN users u ON u.id = db.disposed_by 
    ORDER BY u.first_name");

Layout::head($lang==='th'?'ถังจำหน่าย — Monitor & Report':'Disposal Bin — Monitor & Report');
?>
<body>
<?php Layout::sidebar('disposal'); Layout::beginContent(); ?>
<?php Layout::pageHeader(
    $lang==='th'?'ถังจำหน่ายสารเคมี':'Chemical Disposal Bin',
    'fas fa-trash-alt',
    $lang==='th'?'ติดตามสถานะ ตรวจสอบรายการจำหน่าย แยกตามหน่วยงาน บุคคล อาคาร เพื่อจัดทำรายงาน':'Monitor disposal status, review items by department, person, building for reporting'
); ?>

<!-- ===== SUMMARY STATS ===== -->
<div class="ci-stats" id="dispStats" style="margin-bottom:20px">
    <div class="ci-stat"><div class="ci-stat-icon red"><i class="fas fa-inbox"></i></div>
        <div><div class="ci-stat-val" id="dsTotalAll">-</div><div class="ci-stat-lbl"><?php echo $lang==='th'?'ทั้งหมด':'Total'; ?></div></div></div>
    <div class="ci-stat"><div class="ci-stat-icon orange"><i class="fas fa-clock"></i></div>
        <div><div class="ci-stat-val" id="dsPending">-</div><div class="ci-stat-lbl"><?php echo $lang==='th'?'รอดำเนินการ':'Pending'; ?></div></div></div>
    <div class="ci-stat"><div class="ci-stat-icon green"><i class="fas fa-check-circle"></i></div>
        <div><div class="ci-stat-val" id="dsCompleted">-</div><div class="ci-stat-lbl"><?php echo $lang==='th'?'จำหน่ายแล้ว':'Completed'; ?></div></div></div>
    <div class="ci-stat"><div class="ci-stat-icon gray"><i class="fas fa-undo"></i></div>
        <div><div class="ci-stat-val" id="dsCancelled">-</div><div class="ci-stat-lbl"><?php echo $lang==='th'?'คืนกลับ':'Restored'; ?></div></div></div>
</div>

<!-- ===== TABS: Report / List ===== -->
<div class="ci-tabs" style="margin-bottom:0">
    <button onclick="switchDispTab('report')" id="dtab-report" class="ci-tab active"><i class="fas fa-chart-bar" style="font-size:11px"></i> <?php echo $lang==='th'?'สรุปรายงาน':'Report Summary'; ?></button>
    <button onclick="switchDispTab('list')" id="dtab-list" class="ci-tab"><i class="fas fa-list" style="font-size:11px"></i> <?php echo $lang==='th'?'รายการทั้งหมด':'All Items'; ?></button>
</div>

<!-- ===== FILTER BAR ===== -->
<div class="ci-card" style="border-top:none;border-radius:0 0 6px 6px;margin-bottom:16px">
<div class="ci-card-body" style="padding:10px 14px">
<div class="ci-filter-bar" style="flex-direction:row;flex-wrap:wrap;gap:8px">
    <div style="position:relative;flex:2;min-width:160px">
        <i class="fas fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#aaa;font-size:12px"></i>
        <input type="text" id="dFilterSearch" class="ci-input" placeholder="<?php echo $lang==='th'?'ค้นหาชื่อสาร / Barcode...':'Search chemical, barcode...'; ?>" style="padding-left:32px" oninput="debounceDispLoad()">
    </div>
    <select id="dFilterBuilding" class="ci-select" style="flex:1;min-width:110px" onchange="loadDispData()">
        <option value=""><?php echo $lang==='th'?'ทุกอาคาร':'All Buildings'; ?></option>
        <?php foreach($buildings as $b): ?>
        <option value="<?php echo htmlspecialchars($b['building_name']); ?>"><?php echo htmlspecialchars($b['building_name']); ?></option>
        <?php endforeach; ?>
    </select>
    <select id="dFilterDept" class="ci-select" style="flex:1;min-width:110px" onchange="loadDispData()">
        <option value=""><?php echo $lang==='th'?'ทุกหน่วยงาน':'All Departments'; ?></option>
        <?php foreach($departments as $d): ?>
        <option value="<?php echo htmlspecialchars($d['department']); ?>"><?php echo htmlspecialchars($d['department']); ?></option>
        <?php endforeach; ?>
    </select>
    <select id="dFilterPerson" class="ci-select" style="flex:1;min-width:110px" onchange="loadDispData()">
        <option value=""><?php echo $lang==='th'?'ทุกคน':'All People'; ?></option>
        <?php foreach($disposers as $dp): ?>
        <option value="<?php echo $dp['disposed_by']; ?>"><?php echo htmlspecialchars($dp['first_name'].' '.$dp['last_name']); ?></option>
        <?php endforeach; ?>
    </select>
    <select id="dFilterStatus" class="ci-select" style="flex:1;min-width:90px" onchange="loadDispData()">
        <option value=""><?php echo $lang==='th'?'ทุกสถานะ':'All Status'; ?></option>
        <option value="pending"><?php echo $lang==='th'?'รอดำเนินการ':'Pending'; ?></option>
        <option value="approved"><?php echo $lang==='th'?'อนุมัติแล้ว':'Approved'; ?></option>
        <option value="completed"><?php echo $lang==='th'?'จำหน่ายแล้ว':'Completed'; ?></option>
        <option value="rejected"><?php echo $lang==='th'?'คืนกลับ':'Restored'; ?></option>
    </select>
    <div style="display:flex;gap:4px;flex:1;min-width:200px">
        <input type="date" id="dFilterDateFrom" class="ci-input" style="flex:1" onchange="loadDispData()" title="<?php echo $lang==='th'?'ตั้งแต่':'From'; ?>">
        <input type="date" id="dFilterDateTo" class="ci-input" style="flex:1" onchange="loadDispData()" title="<?php echo $lang==='th'?'ถึง':'To'; ?>">
    </div>
</div>
</div>
</div>

<!-- ===== REPORT VIEW ===== -->
<div id="reportView">
    <div class="ci-g2" style="gap:16px;margin-bottom:20px">
        <!-- By Department -->
        <div class="ci-card">
            <div class="ci-card-head"><i class="fas fa-building"></i> <?php echo $lang==='th'?'ตามหน่วยงาน':'By Department'; ?></div>
            <div class="ci-card-body" id="rptDepartment"><div class="ci-loading"><div class="ci-spinner"></div></div></div>
        </div>
        <!-- By Person -->
        <div class="ci-card">
            <div class="ci-card-head"><i class="fas fa-user"></i> <?php echo $lang==='th'?'ตามบุคคล':'By Person'; ?></div>
            <div class="ci-card-body" id="rptPerson"><div class="ci-loading"><div class="ci-spinner"></div></div></div>
        </div>
    </div>
    <div class="ci-g2" style="gap:16px;margin-bottom:20px">
        <!-- By Reason -->
        <div class="ci-card">
            <div class="ci-card-head"><i class="fas fa-question-circle"></i> <?php echo $lang==='th'?'ตามเหตุผล':'By Reason'; ?></div>
            <div class="ci-card-body" id="rptReason"><div class="ci-loading"><div class="ci-spinner"></div></div></div>
        </div>
        <!-- By Building -->
        <div class="ci-card">
            <div class="ci-card-head"><i class="fas fa-map-marker-alt"></i> <?php echo $lang==='th'?'ตามอาคาร':'By Building'; ?></div>
            <div class="ci-card-body" id="rptBuilding"><div class="ci-loading"><div class="ci-spinner"></div></div></div>
        </div>
    </div>
    <!-- By Method -->
    <div class="ci-card" style="margin-bottom:20px">
        <div class="ci-card-head"><i class="fas fa-cogs"></i> <?php echo $lang==='th'?'ตามวิธีจำหน่าย':'By Disposal Method'; ?></div>
        <div class="ci-card-body" id="rptMethod"><div class="ci-loading"><div class="ci-spinner"></div></div></div>
    </div>
    <!-- Recent -->
    <div class="ci-card" style="margin-bottom:20px">
        <div class="ci-card-head"><i class="fas fa-clock"></i> <?php echo $lang==='th'?'รายการล่าสุด (20 รายการ)':'Recent Items (Last 20)'; ?></div>
        <div class="ci-card-body" id="rptRecent" style="padding:0"><div class="ci-loading"><div class="ci-spinner"></div></div></div>
    </div>
</div>

<!-- ===== LIST VIEW ===== -->
<div id="listView" style="display:none">
    <div id="dispList"><div class="ci-loading"><div class="ci-spinner"></div></div></div>
    <div id="dispPagination"></div>
</div>

<!-- ===== EMPTY STATE ===== -->
<div id="dispEmpty" style="display:none" class="ci-empty">
    <i class="fas fa-trash-alt"></i>
    <p style="font-size:15px;font-weight:500;margin-bottom:4px"><?php echo $lang==='th'?'ไม่พบรายการจำหน่าย':'No disposal items found'; ?></p>
    <p><?php echo $lang==='th'?'รายการจำหน่ายจะปรากฏเมื่อมีการส่งสารเคมีเข้าถังจำหน่าย':'Disposal items will appear when chemicals are sent for disposal'; ?></p>
</div>

<?php Layout::endContent(); ?>

<style>
/* Report cards */
.rpt-bar{display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #f5f5f5}
.rpt-bar:last-child{border-bottom:none}
.rpt-bar-label{flex:1;font-size:13px;font-weight:500;color:var(--c1);min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.rpt-bar-count{font-size:13px;font-weight:700;color:var(--accent);min-width:30px;text-align:right}
.rpt-bar-chart{flex:2;height:18px;background:#f5f5f5;border-radius:9px;overflow:hidden;position:relative}
.rpt-bar-fill{height:100%;border-radius:9px;transition:width .4s ease;display:flex;align-items:center;justify-content:flex-end;padding-right:6px;font-size:9px;color:#fff;font-weight:600}
.rpt-bar-fill.red{background:linear-gradient(90deg,#ef5350,#c62828)}
.rpt-bar-fill.orange{background:linear-gradient(90deg,#ff9800,#e65100)}
.rpt-bar-fill.blue{background:linear-gradient(90deg,#42a5f5,#1565c0)}
.rpt-bar-fill.green{background:linear-gradient(90deg,#66bb6a,#2e7d32)}
.rpt-bar-fill.purple{background:linear-gradient(90deg,#ab47bc,#6a1b9a)}
.rpt-bar-fill.teal{background:linear-gradient(90deg,#26a69a,#00695c)}
.rpt-bar-fill.gray{background:linear-gradient(90deg,#9e9e9e,#616161)}
.rpt-bar-meta{font-size:10px;color:var(--c3);margin-top:1px}
.rpt-summary-row{display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px dashed #e8e8e8;font-size:12px}
.rpt-summary-row:last-child{border-bottom:none}

/* Disposal list table */
.disp-tbl{width:100%;border-collapse:collapse;font-size:12px}
.disp-tbl th{background:#f8f9fa;padding:8px 10px;text-align:left;font-weight:600;color:var(--c2);border-bottom:2px solid #e0e0e0;position:sticky;top:0}
.disp-tbl td{padding:8px 10px;border-bottom:1px solid #f0f0f0;color:var(--c1);vertical-align:middle}
.disp-tbl tr:hover td{background:#fafafa}
.disp-tbl .mono{font-family:monospace;font-size:11px;color:var(--c2)}

/* Status chips */
.ds-chip{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:10px;font-size:10px;font-weight:600}
.ds-chip.pending{background:#fff3e0;color:#e65100}
.ds-chip.approved{background:#e3f2fd;color:#1565c0}
.ds-chip.completed{background:#e8f5e9;color:#2e7d32}
.ds-chip.rejected{background:#f3e5f5;color:#7b1fa2}

/* Disposal item card (mobile-friendly) */
.disp-item{background:#fff;border:1px solid var(--border);border-left:3px solid #ef5350;border-radius:0 6px 6px 0;padding:12px 14px;margin-bottom:8px;transition:box-shadow .1s}
.disp-item:hover{box-shadow:0 2px 6px rgba(0,0,0,.06)}
.disp-item.status-completed{border-left-color:#66bb6a;background:#fafff9}
.disp-item.status-rejected{border-left-color:#ab47bc;background:#fdf5ff}

/* Export button */
.export-bar{display:flex;justify-content:flex-end;gap:8px;margin-bottom:12px}

@media(max-width:768px){
    .rpt-bar{flex-direction:column;align-items:stretch;gap:4px}
    .rpt-bar-chart{flex:none}
    .disp-tbl{display:none}
    .disp-mobile{display:block !important}
}
@media(min-width:769px){
    .disp-mobile{display:none}
}
@media(max-width:480px){
    #dispStats{grid-template-columns:1fr 1fr}
}
</style>

<script>
const L = '<?php echo $lang; ?>';
const TH = L==='th';
const IS_ADMIN = <?php echo $isAdmin?'true':'false'; ?>;

const REASON_LABELS = {expired:TH?'หมดอายุ':'Expired', empty:TH?'หมด/ใช้จนหมด':'Empty', contaminated:TH?'ปนเปื้อน':'Contaminated', damaged:TH?'ชำรุด/แตก':'Damaged', obsolete:TH?'ไม่ใช้แล้ว':'Obsolete', other:TH?'อื่นๆ':'Other'};
const METHOD_LABELS = {waste_collection:TH?'ส่งเก็บของเสีย':'Waste Collection', neutralization:TH?'ทำให้เป็นกลาง':'Neutralization', incineration:TH?'เผาทำลาย':'Incineration', return_to_vendor:TH?'คืนผู้ขาย':'Return to Vendor', other:TH?'อื่นๆ':'Other'};
const BAR_COLORS = ['red','blue','orange','green','purple','teal','gray'];
const STATUS_LABELS = {pending:TH?'รอดำเนินการ':'Pending', approved:TH?'อนุมัติ':'Approved', completed:TH?'จำหน่ายแล้ว':'Completed', rejected:TH?'คืนกลับ':'Restored'};

let dispTab = 'report', dispPage = 1, dispTimer = null;

// ===== INIT =====
loadDispData();

function switchDispTab(tab) {
    dispTab = tab;
    dispPage = 1;
    document.querySelectorAll('.ci-tab').forEach(el => el.classList.remove('active'));
    document.getElementById('dtab-'+tab).classList.add('active');
    document.getElementById('reportView').style.display = tab==='report'?'':'none';
    document.getElementById('listView').style.display = tab==='list'?'':'none';
    loadDispData();
}

function debounceDispLoad() {
    clearTimeout(dispTimer);
    dispTimer = setTimeout(() => { dispPage = 1; loadDispData(); }, 300);
}

function getFilters() {
    const f = {};
    const s = document.getElementById('dFilterSearch').value.trim();
    if (s) f.search = s;
    const b = document.getElementById('dFilterBuilding').value;
    if (b) f.building = b;
    const d = document.getElementById('dFilterDept').value;
    if (d) f.department = d;
    const p = document.getElementById('dFilterPerson').value;
    if (p) f.disposed_by = p;
    const st = document.getElementById('dFilterStatus').value;
    if (st) f.status = st;
    const df = document.getElementById('dFilterDateFrom').value;
    if (df) f.date_from = df;
    const dt = document.getElementById('dFilterDateTo').value;
    if (dt) f.date_to = dt;
    return f;
}

async function loadDispData() {
    if (dispTab === 'report') {
        loadReport();
    } else {
        loadDispList();
    }
}

// ===== REPORT =====
async function loadReport() {
    const filters = getFilters();
    const params = new URLSearchParams({action:'disposal_report', ...filters});

    try {
        const d = await apiFetch('/v1/api/borrow.php?' + params.toString());
        if (!d.success) throw new Error(d.error);
        const rpt = d.data;

        // Stats
        const st = rpt.stats || {};
        document.getElementById('dsTotalAll').textContent = st.total || 0;
        document.getElementById('dsPending').textContent = st.pending || 0;
        document.getElementById('dsCompleted').textContent = st.completed || 0;
        document.getElementById('dsCancelled').textContent = st.cancelled || 0;

        // By Department
        renderBarChart('rptDepartment', rpt.by_department || [], 'dept_name', 'item_count', 'units');

        // By Person
        const personData = (rpt.by_person || []).map(p => ({
            ...p, display_name: [p.first_name, p.last_name].filter(Boolean).join(' ') || '-',
            sub: p.department || ''
        }));
        renderBarChart('rptPerson', personData, 'display_name', 'item_count', 'units', 'sub');

        // By Reason
        const reasonData = (rpt.by_reason || []).map(r => ({...r, display: REASON_LABELS[r.reason] || r.reason}));
        renderBarChart('rptReason', reasonData, 'display', 'item_count');

        // By Building
        renderBarChart('rptBuilding', rpt.by_building || [], 'bld_name', 'item_count', 'units');

        // By Method
        const methodData = (rpt.by_method || []).map(m => ({...m, display: METHOD_LABELS[m.method] || m.method}));
        renderBarChart('rptMethod', methodData, 'display', 'item_count');

        // Recent
        renderRecentTable('rptRecent', rpt.recent || []);

    } catch(e) {
        ['rptDepartment','rptPerson','rptReason','rptBuilding','rptMethod'].forEach(id => {
            document.getElementById(id).innerHTML = `<div class="ci-alert ci-alert-danger" style="margin:0">${e.message}</div>`;
        });
    }
}

function renderBarChart(containerId, data, labelKey, countKey, unitsKey, subKey) {
    const el = document.getElementById(containerId);
    if (!data.length) {
        el.innerHTML = `<div style="text-align:center;padding:16px;color:var(--c3);font-size:12px"><i class="fas fa-inbox"></i> ${TH?'ไม่มีข้อมูล':'No data'}</div>`;
        return;
    }
    const maxVal = Math.max(...data.map(d => parseInt(d[countKey]) || 0), 1);
    el.innerHTML = data.map((d, i) => {
        const count = parseInt(d[countKey]) || 0;
        const pct = Math.round((count / maxVal) * 100);
        const color = BAR_COLORS[i % BAR_COLORS.length];
        const units = unitsKey && d[unitsKey] ? ` <span style="font-size:10px;color:var(--c3)">(${d[unitsKey]})</span>` : '';
        const sub = subKey && d[subKey] ? `<div class="rpt-bar-meta">${esc(d[subKey])}</div>` : '';
        return `<div class="rpt-bar">
            <div style="flex:1.5;min-width:0">
                <div class="rpt-bar-label">${esc(d[labelKey] || '-')}${units}</div>
                ${sub}
            </div>
            <div class="rpt-bar-chart"><div class="rpt-bar-fill ${color}" style="width:${pct}%">${pct>15?count:''}</div></div>
            <div class="rpt-bar-count">${count}</div>
        </div>`;
    }).join('');
}

function renderRecentTable(containerId, items) {
    const el = document.getElementById(containerId);
    if (!items.length) {
        el.innerHTML = `<div style="text-align:center;padding:20px;color:var(--c3);font-size:12px"><i class="fas fa-inbox"></i> ${TH?'ไม่มีรายการ':'No items'}</div>`;
        return;
    }

    // Desktop table
    let html = `<table class="disp-tbl">
        <thead><tr>
            <th>${TH?'สารเคมี':'Chemical'}</th>
            <th>Barcode</th>
            <th>${TH?'ปริมาณ':'Qty'}</th>
            <th>${TH?'ส่งโดย':'By'}</th>
            <th>${TH?'เหตุผล':'Reason'}</th>
            <th>${TH?'สถานะ':'Status'}</th>
            <th>${TH?'วันที่':'Date'}</th>
            ${IS_ADMIN?`<th>${TH?'จัดการ':'Actions'}</th>`:''}
        </tr></thead><tbody>`;

    html += items.map(b => {
        const name = esc(b.chemical_name || '-');
        const by = [b.disposed_first, b.disposed_last].filter(Boolean).join(' ');
        const reason = REASON_LABELS[b.disposal_reason] || (b.disposal_reason || '-');
        const statusCls = b.status;
        const statusLbl = STATUS_LABELS[b.status] || b.status;

        let actions = '';
        if (IS_ADMIN && (b.status === 'pending' || b.status === 'approved')) {
            actions = `<td>
                <button onclick="completeDisp(${b.id})" class="ci-btn ci-btn-primary ci-btn-sm" style="padding:3px 8px;font-size:10px" title="${TH?'ยืนยันจำหน่าย':'Complete'}"><i class="fas fa-check"></i></button>
                <button onclick="cancelDisp(${b.id})" class="ci-btn ci-btn-secondary ci-btn-sm" style="padding:3px 8px;font-size:10px" title="${TH?'คืนกลับ':'Restore'}"><i class="fas fa-undo"></i></button>
            </td>`;
        } else if (IS_ADMIN) {
            actions = '<td>-</td>';
        }

        return `<tr>
            <td style="font-weight:500;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${name}">${name}</td>
            <td class="mono">${b.barcode || '-'}</td>
            <td>${Number(b.remaining_qty).toLocaleString()} ${b.unit || ''}</td>
            <td>${esc(by)}</td>
            <td>${reason}</td>
            <td><span class="ds-chip ${statusCls}">${statusLbl}</span></td>
            <td style="font-size:11px;color:var(--c3)">${formatDate(b.created_at)}</td>
            ${actions}
        </tr>`;
    }).join('');
    html += '</tbody></table>';

    // Mobile cards
    html += `<div class="disp-mobile" style="display:none;padding:10px">`;
    html += items.map(b => {
        const name = esc(b.chemical_name || '-');
        const by = [b.disposed_first, b.disposed_last].filter(Boolean).join(' ');
        const reason = REASON_LABELS[b.disposal_reason] || (b.disposal_reason || '-');
        const statusCls = b.status;
        const statusLbl = STATUS_LABELS[b.status] || b.status;

        let actionBtns = '';
        if (IS_ADMIN && (b.status === 'pending' || b.status === 'approved')) {
            actionBtns = `<div style="margin-top:8px;display:flex;gap:6px">
                <button onclick="completeDisp(${b.id})" class="ci-btn ci-btn-primary ci-btn-sm" style="font-size:11px"><i class="fas fa-check"></i> ${TH?'ยืนยัน':'Complete'}</button>
                <button onclick="cancelDisp(${b.id})" class="ci-btn ci-btn-secondary ci-btn-sm" style="font-size:11px"><i class="fas fa-undo"></i> ${TH?'คืนกลับ':'Restore'}</button>
            </div>`;
        }

        return `<div class="disp-item status-${statusCls}">
            <div style="display:flex;justify-content:space-between;align-items:start;gap:8px">
                <div style="font-weight:600;font-size:13px;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${name}</div>
                <span class="ds-chip ${statusCls}">${statusLbl}</span>
            </div>
            <div style="display:flex;gap:14px;flex-wrap:wrap;margin-top:4px;font-size:11px;color:var(--c3)">
                <span><i class="fas fa-barcode"></i> ${b.barcode || '-'}</span>
                <span><i class="fas fa-flask"></i> ${Number(b.remaining_qty).toLocaleString()} ${b.unit||''}</span>
                <span><i class="fas fa-user"></i> ${esc(by)}</span>
            </div>
            <div style="margin-top:4px;font-size:11px;color:var(--c3)">
                <span><i class="fas fa-tag"></i> ${reason}</span>
                <span style="margin-left:10px"><i class="fas fa-calendar"></i> ${formatDate(b.created_at)}</span>
            </div>
            ${actionBtns}
        </div>`;
    }).join('');
    html += '</div>';

    el.innerHTML = html;
}

// ===== LIST VIEW =====
async function loadDispList() {
    const list = document.getElementById('dispList');
    const empty = document.getElementById('dispEmpty');
    const pag = document.getElementById('dispPagination');
    list.innerHTML = '<div class="ci-loading"><div class="ci-spinner"></div></div>';
    empty.style.display = 'none';
    pag.innerHTML = '';

    const filters = getFilters();
    const params = new URLSearchParams({action:'disposal_bin', page:dispPage, per_page:30, show_all:1, ...filters});

    try {
        const d = await apiFetch('/v1/api/borrow.php?' + params.toString());
        if (!d.success) throw new Error(d.error);

        const items = d.data.items || d.data || [];
        const pagination = d.data.pagination;

        if (!items.length) {
            list.innerHTML = '';
            empty.style.display = 'block';
            return;
        }

        renderRecentTable('dispList', items);

        // Pagination
        if (pagination && pagination.total_pages > 1) {
            pag.innerHTML = `<div class="ci-pagination">
                <span>${TH?'หน้า':'Page'} ${pagination.page}/${pagination.total_pages} (${pagination.total} ${TH?'รายการ':'items'})</span>
                <div class="ci-pagination-btns">
                    ${pagination.page > 1 ? `<button class="ci-btn ci-btn-sm ci-btn-secondary" onclick="dispPage--;loadDispList()"><i class="fas fa-chevron-left"></i></button>` : ''}
                    ${pagination.page < pagination.total_pages ? `<button class="ci-btn ci-btn-sm ci-btn-secondary" onclick="dispPage++;loadDispList()"><i class="fas fa-chevron-right"></i></button>` : ''}
                </div>
            </div>`;
        }

        // Also update stats
        loadReport(); // refresh stats
    } catch(e) {
        list.innerHTML = `<div class="ci-alert ci-alert-danger">${e.message}</div>`;
    }
}

// ===== ADMIN ACTIONS =====
async function completeDisp(binId) {
    if (!confirm(TH?'ยืนยันว่าจำหน่ายสำเร็จแล้ว? (ไม่สามารถย้อนกลับ)':'Confirm disposal completed? (Cannot undo)')) return;
    try {
        const d = await apiFetch('/v1/api/borrow.php?action=disposal_complete', {method:'POST', body:JSON.stringify({bin_id:binId})});
        if (!d.success) throw new Error(d.error);
        showToast(TH?'จำหน่ายสำเร็จ':'Disposal completed', 'success');
        loadDispData();
    } catch(e) { alert(e.message); }
}

async function cancelDisp(binId) {
    if (!confirm(TH?'คืนสารนี้กลับเข้าระบบ?':'Restore this item back to the system?')) return;
    try {
        const d = await apiFetch('/v1/api/borrow.php?action=disposal_cancel', {method:'POST', body:JSON.stringify({bin_id:binId})});
        if (!d.success) throw new Error(d.error);
        showToast(TH?'คืนกลับเข้าระบบแล้ว':'Restored to system', 'success');
        loadDispData();
    } catch(e) { alert(e.message); }
}

// ===== HELPERS =====
function esc(s) { if (!s) return ''; const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

function showToast(msg, type='info') {
    const t = document.createElement('div');
    t.className = 'ci-alert ci-alert-' + (type==='success'?'success':'info');
    t.style.cssText = 'position:fixed;top:60px;right:20px;z-index:9999;max-width:360px;box-shadow:0 4px 12px rgba(0,0,0,.15);animation:fadeIn .2s';
    t.innerHTML = `<i class="fas ${type==='success'?'fa-check-circle':'fa-info-circle'}"></i> ${msg}`;
    document.body.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity .3s'; setTimeout(() => t.remove(), 300); }, 3000);
}
</script>
</body></html>
