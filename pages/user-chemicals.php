<?php
/**
 * ข้อมูลสารรายบุคคล — User Chemical Inventory
 * Admin/CEO: view all users' chemical holdings
 * Lab Manager: view own lab's users
 */
require_once __DIR__ . '/../includes/layout.php';
$user = Auth::getCurrentUser();
if (!$user) { header('Location: /v1/pages/login.php'); exit; }
if (!in_array($user['role_name'], ['admin', 'ceo', 'lab_manager'])) { header('Location: /v1/'); exit; }
$lang = I18n::getCurrentLang();
Layout::head($lang === 'th' ? 'ข้อมูลสารรายบุคคล' : 'User Chemical Inventory');
?>
<body>
<?php Layout::sidebar('user-chemicals'); Layout::beginContent(); ?>

<!-- Page Header -->
<div class="ci-pg-hdr">
    <div>
        <div class="ci-pg-title">
            <i class="fas fa-users-cog" style="color:var(--accent);margin-right:8px"></i>
            <?php echo $lang === 'th' ? 'ข้อมูลสารรายบุคคล' : 'User Chemical Inventory'; ?>
        </div>
        <div style="font-size:12px;color:var(--c3);margin-top:2px">
            <?php echo $lang === 'th' ? 'สรุปภาพรวมสารเคมีของแต่ละบุคคล' : 'Overview of chemicals per individual user'; ?>
        </div>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
        <button class="ci-btn ci-btn-outline" onclick="window.print()">
            <i class="fas fa-print"></i> <?php echo __('reports_export'); ?>
        </button>
    </div>
</div>

<!-- Summary Stats -->
<div class="ci-stats" style="margin-bottom:20px" id="statsRow">
    <div class="ci-stat">
        <div class="ci-stat-icon purple"><i class="fas fa-users"></i></div>
        <div><div class="ci-stat-val" id="sTotalUsers">-</div>
        <div class="ci-stat-lbl"><?php echo $lang === 'th' ? 'ผู้ใช้ทั้งหมด' : 'Total Users'; ?></div></div>
    </div>
    <div class="ci-stat">
        <div class="ci-stat-icon green"><i class="fas fa-user-check"></i></div>
        <div><div class="ci-stat-val" id="sWithChem">-</div>
        <div class="ci-stat-lbl"><?php echo $lang === 'th' ? 'มีสารเคมี' : 'With Chemicals'; ?></div></div>
    </div>
    <div class="ci-stat">
        <div class="ci-stat-icon blue"><i class="fas fa-box"></i></div>
        <div><div class="ci-stat-val" id="sTotalCont">-</div>
        <div class="ci-stat-lbl"><?php echo $lang === 'th' ? 'ภาชนะทั้งหมด' : 'Total Containers'; ?></div></div>
    </div>
    <div class="ci-stat">
        <div class="ci-stat-icon orange"><i class="fas fa-battery-quarter"></i></div>
        <div><div class="ci-stat-val" id="sLowStock">-</div>
        <div class="ci-stat-lbl"><?php echo $lang === 'th' ? 'สต็อกต่ำ' : 'Low Stock'; ?></div></div>
    </div>
    <div class="ci-stat">
        <div class="ci-stat-icon red"><i class="fas fa-clock"></i></div>
        <div><div class="ci-stat-val" id="sExpiring">-</div>
        <div class="ci-stat-lbl"><?php echo $lang === 'th' ? 'ใกล้หมดอายุ' : 'Expiring'; ?></div></div>
    </div>
</div>

<!-- Filters -->
<div class="ci-card" style="margin-bottom:16px">
    <div class="ci-card-body" style="padding:12px 16px">
        <div class="uc-filters">
            <div class="uc-filter-group">
                <div class="uc-search-wrap">
                    <i class="fas fa-search"></i>
                    <input type="text" id="fSearch" class="uc-search" 
                        placeholder="<?php echo $lang === 'th' ? 'ค้นหาชื่อผู้ใช้...' : 'Search user name...'; ?>"
                        oninput="debounceLoad()">
                </div>
            </div>
            <div class="uc-filter-group">
                <select id="fStore" class="uc-select" onchange="loadData()">
                    <option value=""><?php echo $lang === 'th' ? '— ทุกคลัง —' : '— All Stores —'; ?></option>
                </select>
                <select id="fDivision" class="uc-select" onchange="loadData()">
                    <option value=""><?php echo $lang === 'th' ? '— ทุกฝ่าย —' : '— All Divisions —'; ?></option>
                </select>
                <select id="fSort" class="uc-select" onchange="loadData()">
                    <option value="containers_desc"><?php echo $lang === 'th' ? 'สารเคมีมาก → น้อย' : 'Most Chemicals'; ?></option>
                    <option value="containers_asc"><?php echo $lang === 'th' ? 'สารเคมีน้อย → มาก' : 'Least Chemicals'; ?></option>
                    <option value="name_asc"><?php echo $lang === 'th' ? 'ชื่อ ก-ฮ' : 'Name A-Z'; ?></option>
                    <option value="name_desc"><?php echo $lang === 'th' ? 'ชื่อ ฮ-ก' : 'Name Z-A'; ?></option>
                    <option value="store"><?php echo $lang === 'th' ? 'ตามคลัง' : 'By Store'; ?></option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- User List -->
<div id="userListWrap">
    <div class="ci-loading" style="padding:60px"><div class="ci-spinner"></div></div>
</div>

<!-- Container Detail Modal -->
<div class="uc-modal-overlay" id="ucModalOverlay" onclick="closeUcModal(event)">
    <div class="uc-modal" onclick="event.stopPropagation()">
        <div class="uc-modal-hdr">
            <div class="uc-modal-hdr-left">
                <div class="uc-modal-avatar" id="modalAvatar"><i class="fas fa-user"></i></div>
                <div>
                    <div class="uc-modal-name" id="modalName">-</div>
                    <div class="uc-modal-sub" id="modalSub">-</div>
                </div>
            </div>
            <button class="rpt-modal-close" onclick="closeUcModal(event)">&times;</button>
        </div>
        <div class="uc-modal-body" id="modalBody">
            <div class="ci-loading" style="padding:40px"><div class="ci-spinner"></div></div>
        </div>
    </div>
</div>

<?php Layout::endContent(); ?>

<style>
/* ── Filters ─────────────────── */
.uc-filters { display:flex; gap:12px; align-items:center; flex-wrap:wrap; }
.uc-filter-group { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
.uc-search-wrap {
    position:relative; display:flex; align-items:center;
}
.uc-search-wrap i {
    position:absolute; left:10px; color:var(--c3); font-size:13px; pointer-events:none;
}
.uc-search {
    padding:8px 12px 8px 32px; border:1px solid var(--border,#e0e0e0);
    border-radius:6px; font-size:13px; width:220px; background:var(--card-bg,#fff);
    color:var(--c1); outline:none; transition:border-color .15s;
}
.uc-search:focus { border-color:var(--accent); }
.uc-select {
    padding:8px 28px 8px 10px; border:1px solid var(--border,#e0e0e0);
    border-radius:6px; font-size:13px; background:var(--card-bg,#fff);
    color:var(--c1); cursor:pointer; outline:none; min-width:140px;
    appearance:none; -webkit-appearance:none;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%2394a3b8'/%3E%3C/svg%3E");
    background-repeat:no-repeat; background-position:right 10px center;
}
.uc-select:focus { border-color:var(--accent); }

/* ── User Cards ─────────────── */
.uc-list { display:flex; flex-direction:column; gap:6px; }

.uc-user-card {
    background:var(--card-bg,#fff); border:1px solid var(--border,#e5e7eb);
    border-radius:10px; overflow:hidden; transition:all .2s;
}
.uc-user-card:hover { box-shadow:0 4px 16px rgba(0,0,0,0.08); border-color:#ccc; }
.uc-user-card.expanded { border-color:var(--accent); box-shadow:0 4px 20px rgba(26,138,92,0.12); }

.uc-user-row {
    display:flex; align-items:center; gap:14px; padding:14px 18px;
    cursor:pointer; user-select:none; transition:background .15s;
}
.uc-user-row:hover { background:rgba(26,138,92,0.03); }

.uc-avatar {
    width:44px; height:44px; border-radius:50%; background:linear-gradient(135deg,#e0f2fe,#bfdbfe);
    display:flex; align-items:center; justify-content:center; flex-shrink:0;
    font-weight:700; font-size:16px; color:#1d4ed8; overflow:hidden;
}
.uc-avatar img { width:100%; height:100%; object-fit:cover; }

.uc-user-info { flex:1; min-width:0; }
.uc-user-name { font-weight:700; font-size:14px; color:var(--c1); display:flex; align-items:center; gap:8px; }
.uc-user-name .uc-role-badge {
    font-size:10px; font-weight:600; padding:2px 7px; border-radius:10px;
    text-transform:uppercase; letter-spacing:.3px;
}
.uc-role-admin { background:#dbeafe; color:#1d4ed8; }
.uc-role-lab_manager { background:#fce7f3; color:#be185d; }
.uc-role-ceo { background:#f3e8ff; color:#7c3aed; }
.uc-role-user { background:#e0f2fe; color:#0369a1; }

.uc-user-meta { font-size:12px; color:var(--c3); margin-top:2px; display:flex; gap:12px; flex-wrap:wrap; }
.uc-user-meta i { margin-right:3px; font-size:10px; }

.uc-user-stats { display:flex; gap:6px; flex-shrink:0; align-items:center; }
.uc-stat-pill {
    display:flex; align-items:center; gap:5px; padding:5px 10px;
    border-radius:8px; font-size:12px; font-weight:600; white-space:nowrap;
}
.uc-pill-blue { background:#eff6ff; color:#1d4ed8; }
.uc-pill-orange { background:#fff7ed; color:#c2410c; }
.uc-pill-red { background:#fef2f2; color:#dc2626; }
.uc-pill-green { background:#f0fdf4; color:#16a34a; }
.uc-pill-gray { background:#f8fafc; color:#64748b; }

.uc-expand-arrow {
    width:28px; height:28px; border-radius:50%; display:flex;
    align-items:center; justify-content:center; color:var(--c3);
    transition:all .2s; font-size:12px; flex-shrink:0;
    background:var(--bg,#f8f9fa);
}
.uc-user-card.expanded .uc-expand-arrow { transform:rotate(180deg); color:var(--accent); background:#e8f5ef; }

/* ── Dropdown Detail ────────── */
.uc-detail-wrap {
    max-height:0; overflow:hidden; transition:max-height .35s ease;
    background:var(--bg,#f8f9fa); border-top:1px solid transparent;
}
.uc-user-card.expanded .uc-detail-wrap {
    max-height:2000px; border-top-color:var(--border,#e5e7eb);
}
.uc-detail-inner { padding:0; }

.uc-detail-loading { padding:30px; text-align:center; }

.uc-chem-table { width:100%; border-collapse:collapse; font-size:13px; }
.uc-chem-table thead { background:rgba(0,0,0,0.03); position:sticky; top:0; }
.uc-chem-table th {
    padding:10px 14px; text-align:left; font-weight:600; font-size:11px;
    text-transform:uppercase; letter-spacing:.4px; color:var(--c3);
    border-bottom:1px solid var(--border,#e5e7eb);
}
.uc-chem-table td {
    padding:10px 14px; border-bottom:1px solid var(--border,#f0f0f0);
    vertical-align:middle;
}
.uc-chem-table tr:last-child td { border-bottom:none; }
.uc-chem-table tr:hover td { background:rgba(26,138,92,0.03); }

.uc-pct-bar {
    display:flex; align-items:center; gap:6px;
}
.uc-pct-bar-track {
    flex:1; height:6px; background:#e5e7eb; border-radius:3px; overflow:hidden; min-width:50px;
}
.uc-pct-bar-fill { height:100%; border-radius:3px; transition:width .3s; }
.uc-pct-bar-val { font-size:11px; font-weight:700; min-width:36px; text-align:right; }

.uc-barcode-tag {
    font-family:'Courier New',monospace; font-size:11px; font-weight:600;
    background:#f1f5f9; padding:2px 6px; border-radius:3px; color:#475569;
    letter-spacing:.5px;
}

/* No chemicals placeholder */
.uc-no-chem {
    padding:24px; text-align:center; color:var(--c3); font-size:13px;
}
.uc-no-chem i { font-size:24px; display:block; margin-bottom:6px; opacity:.4; }

/* ── View-all link ─────────── */
.uc-view-all {
    display:flex; justify-content:center; padding:10px;
    border-top:1px solid var(--border,#e5e7eb);
}
.uc-view-all-btn {
    font-size:12px; color:var(--accent); cursor:pointer;
    border:none; background:none; font-weight:600;
    display:flex; align-items:center; gap:4px;
}
.uc-view-all-btn:hover { text-decoration:underline; }

/* ── Modal ──────────────────── */
.uc-modal-overlay {
    display:none; position:fixed; inset:0; z-index:9999;
    background:rgba(0,0,0,0.45); backdrop-filter:blur(3px);
    justify-content:center; align-items:center; padding:16px;
}
.uc-modal-overlay.show { display:flex; animation:fadeIn .15s ease-out; }
.uc-modal {
    background:var(--card-bg,#fff); border-radius:12px;
    width:100%; max-width:700px; max-height:90vh;
    box-shadow:0 20px 60px rgba(0,0,0,0.25); overflow:hidden;
    animation:slideUp .2s ease-out;
}
@keyframes slideUp { from{transform:translateY(20px);opacity:0} to{transform:translateY(0);opacity:1} }
.uc-modal-hdr {
    display:flex; align-items:center; justify-content:space-between;
    padding:16px 20px; border-bottom:1px solid var(--border,#e5e7eb);
    background:linear-gradient(135deg,#ecfdf5 0%,#d1fae5 100%);
}
.uc-modal-hdr-left { display:flex; align-items:center; gap:12px; }
.uc-modal-avatar {
    width:42px; height:42px; border-radius:50%; display:flex;
    align-items:center; justify-content:center; font-size:16px;
    background:linear-gradient(135deg,#a7f3d0,#6ee7b7); color:#065f46;
    font-weight:700; overflow:hidden;
}
.uc-modal-avatar img { width:100%; height:100%; object-fit:cover; }
.uc-modal-name { font-weight:700; font-size:15px; color:#065f46; }
.uc-modal-sub { font-size:12px; color:#047857; }
.rpt-modal-close {
    border:none; background:rgba(0,0,0,0.08); width:32px; height:32px;
    border-radius:50%; font-size:20px; cursor:pointer; display:flex;
    align-items:center; justify-content:center; color:#065f46; transition:background .15s;
}
.rpt-modal-close:hover { background:rgba(0,0,0,0.15); }
.uc-modal-body { overflow-y:auto; max-height:calc(90vh - 70px); }

/* ── Empty state ────────────── */
.uc-empty {
    text-align:center; padding:60px 20px; color:var(--c3);
}
.uc-empty i { font-size:48px; opacity:.3; margin-bottom:12px; display:block; }
.uc-empty div { font-size:14px; }

/* ── Responsive ─────────────── */
@media(max-width:768px){
    .uc-filters { flex-direction:column; }
    .uc-filter-group { width:100%; }
    .uc-search { width:100%; }
    .uc-select { flex:1; min-width:0; }
    .uc-user-row { flex-wrap:wrap; gap:10px; padding:12px 14px; }
    .uc-user-stats { width:100%; justify-content:flex-start; flex-wrap:wrap; }
    .uc-stat-pill { font-size:11px; padding:4px 8px; }
    .uc-chem-table { font-size:12px; }
    .uc-chem-table th, .uc-chem-table td { padding:8px 10px; }
    .uc-modal { max-width:100%; margin:8px; border-radius:10px; }
}
@media(max-width:480px){
    .uc-avatar { width:36px; height:36px; font-size:14px; }
    .uc-user-name { font-size:13px; }
}
@media print {
    .uc-modal-overlay, .ci-sidebar, .ci-pg-hdr .ci-btn, .uc-filters { display:none!important; }
    .ci-main { margin-left:0!important; padding:10px!important; }
    .uc-user-card { break-inside:avoid; }
    .uc-detail-wrap { max-height:none!important; overflow:visible!important; }
}
</style>

<script>
const LANG = '<?php echo $lang; ?>';
let allUsers = [];
let loadedDetails = {};  // cache: userId -> containers[]
let debounceTimer = null;

function debounceLoad() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(loadData, 300);
}

async function loadData() {
    const search = document.getElementById('fSearch').value.trim();
    const store = document.getElementById('fStore').value;
    const division = document.getElementById('fDivision').value;
    const sort = document.getElementById('fSort').value;

    let url = `/v1/api/user_chemicals.php?action=list&sort=${encodeURIComponent(sort)}`;
    if (search) url += `&search=${encodeURIComponent(search)}`;
    if (store) url += `&store=${encodeURIComponent(store)}`;
    if (division) url += `&division=${encodeURIComponent(division)}`;

    document.getElementById('userListWrap').innerHTML =
        '<div class="ci-loading" style="padding:60px"><div class="ci-spinner"></div></div>';

    try {
        const res = await apiFetch(url);
        if (!res.success) throw new Error(res.message);
        const d = res.data;
        allUsers = d.users || [];

        // Populate stats
        const s = d.stats || {};
        setText('sTotalUsers', s.total_users);
        setText('sWithChem', s.users_with_chemicals);
        setText('sTotalCont', s.total_containers);
        setText('sLowStock', s.total_low_stock);
        setText('sExpiring', s.total_expiring);

        // Populate filter dropdowns (only first load)
        populateFilters(d.stores || [], d.divisions || []);

        renderUserList(allUsers);
    } catch (e) {
        console.error(e);
        document.getElementById('userListWrap').innerHTML =
            '<div class="uc-empty"><i class="fas fa-exclamation-triangle"></i><div>' + e.message + '</div></div>';
    }
}

function setText(id, val) {
    const el = document.getElementById(id);
    if (el && val !== undefined) el.textContent = val;
}

let filtersPopulated = false;
function populateFilters(stores, divisions) {
    if (filtersPopulated) return;
    filtersPopulated = true;

    const storeSel = document.getElementById('fStore');
    stores.forEach(s => {
        const o = document.createElement('option');
        o.value = s.store_name; o.textContent = s.store_name;
        storeSel.appendChild(o);
    });

    const divSel = document.getElementById('fDivision');
    divisions.forEach(d => {
        const o = document.createElement('option');
        o.value = d.division_name; o.textContent = d.division_name;
        divSel.appendChild(o);
    });
}

function renderUserList(users) {
    if (!users.length) {
        document.getElementById('userListWrap').innerHTML =
            `<div class="uc-empty"><i class="fas fa-users"></i><div>${LANG==='th'?'ไม่พบผู้ใช้':'No users found'}</div></div>`;
        return;
    }

    let html = '<div class="uc-list">';
    users.forEach((u, idx) => {
        const name = `${u.first_name} ${u.last_name}`;
        const initials = (u.first_name?.[0] || '') + (u.last_name?.[0] || '');
        const cnt = parseInt(u.active_containers) || 0;
        const low = parseInt(u.low_stock_count) || 0;
        const exp = parseInt(u.expiring_count) || 0;
        const avgR = parseFloat(u.avg_remaining) || 0;
        const roleClass = 'uc-role-' + (u.role_name || 'user');
        const roleName = u.role_name || 'user';

        html += `<div class="uc-user-card" id="uc-card-${u.id}">`;
        html += `<div class="uc-user-row" onclick="toggleUser(${u.id})">`;

        // Avatar
        html += '<div class="uc-avatar">';
        if (u.avatar_url) {
            html += `<img src="${u.avatar_url}" alt="${name}">`;
        } else {
            html += initials;
        }
        html += '</div>';

        // Info
        html += '<div class="uc-user-info">';
        html += `<div class="uc-user-name">${name} <span class="uc-role-badge ${roleClass}">${roleName}</span></div>`;
        html += '<div class="uc-user-meta">';
        // Show division (ฝ่าย)
        if (u.division_name) html += `<span><i class="fas fa-sitemap"></i>${u.division_name}</span>`;
        // Show store names (คลัง)
        const storeArr = u.store_names || [];
        if (storeArr.length > 0) {
            const storeText = storeArr.length <= 2 ? storeArr.join(', ') : storeArr.slice(0,2).join(', ') + ` +${storeArr.length - 2}`;
            html += `<span><i class="fas fa-warehouse"></i>${storeText}</span>`;
        }
        html += '</div></div>';

        // Stat pills
        html += '<div class="uc-user-stats">';
        html += `<div class="uc-stat-pill ${cnt > 0 ? 'uc-pill-blue' : 'uc-pill-gray'}"><i class="fas fa-box" style="font-size:10px"></i> ${cnt}</div>`;
        if (low > 0) html += `<div class="uc-stat-pill uc-pill-orange"><i class="fas fa-battery-quarter" style="font-size:10px"></i> ${low}</div>`;
        if (exp > 0) html += `<div class="uc-stat-pill uc-pill-red"><i class="fas fa-clock" style="font-size:10px"></i> ${exp}</div>`;
        if (cnt > 0) {
            const avgColor = avgR <= 20 ? '#dc2626' : avgR <= 50 ? '#d97706' : '#16a34a';
            html += `<div class="uc-stat-pill uc-pill-green" style="color:${avgColor}"><i class="fas fa-tachometer-alt" style="font-size:10px"></i> ${avgR.toFixed(0)}%</div>`;
        }
        html += '</div>';

        // Arrow
        html += '<div class="uc-expand-arrow"><i class="fas fa-chevron-down"></i></div>';

        html += '</div>'; // end row

        // Detail dropdown
        html += `<div class="uc-detail-wrap" id="uc-detail-${u.id}"><div class="uc-detail-inner" id="uc-inner-${u.id}">`;
        html += '<div class="uc-detail-loading"><div class="ci-spinner"></div></div>';
        html += '</div></div>';

        html += '</div>'; // end card
    });
    html += '</div>';

    document.getElementById('userListWrap').innerHTML = html;
}

async function toggleUser(userId) {
    const card = document.getElementById('uc-card-' + userId);
    const isExpanded = card.classList.contains('expanded');

    // Collapse all
    document.querySelectorAll('.uc-user-card.expanded').forEach(c => c.classList.remove('expanded'));

    if (isExpanded) return; // was open, just close

    card.classList.add('expanded');

    // Load detail if not cached
    if (!loadedDetails[userId]) {
        const inner = document.getElementById('uc-inner-' + userId);
        inner.innerHTML = '<div class="uc-detail-loading"><div class="ci-spinner"></div></div>';

        try {
            const res = await apiFetch(`/v1/api/user_chemicals.php?action=detail&user_id=${userId}`);
            if (!res.success) throw new Error(res.message);
            loadedDetails[userId] = res.data;
            renderDetail(userId, res.data);
        } catch (e) {
            inner.innerHTML = `<div class="uc-no-chem"><i class="fas fa-exclamation-circle"></i>${e.message}</div>`;
        }
    }
}

function renderDetail(userId, data) {
    const inner = document.getElementById('uc-inner-' + userId);
    const containers = data.containers || [];

    if (!containers.length) {
        inner.innerHTML = `<div class="uc-no-chem"><i class="fas fa-box-open"></i>${LANG==='th'?'ไม่มีสารเคมีในครอบครอง':'No chemicals owned'}</div>`;
        return;
    }

    // Show max 10 inline, rest in modal
    const showInline = containers.slice(0, 10);
    const hasMore = containers.length > 10;

    let html = '<table class="uc-chem-table"><thead><tr>';
    html += `<th>#</th>`;
    html += `<th>${LANG==='th'?'ชื่อสารเคมี':'Chemical Name'}</th>`;
    html += `<th>Barcode</th>`;
    html += `<th>CAS</th>`;
    html += `<th>${LANG==='th'?'ตำแหน่ง':'Location'}</th>`;
    html += `<th style="text-align:right">${LANG==='th'?'ปริมาณ':'Qty'}</th>`;
    html += `<th style="min-width:100px">${LANG==='th'?'คงเหลือ':'Remaining'}</th>`;
    html += `<th>${LANG==='th'?'หมดอายุ':'Expiry'}</th>`;
    html += '</tr></thead><tbody>';

    showInline.forEach((c, i) => {
        html += buildContainerRow(c, i + 1);
    });

    html += '</tbody></table>';

    if (hasMore) {
        html += '<div class="uc-view-all">';
        html += `<button class="uc-view-all-btn" onclick="openFullModal(${userId})">`;
        html += `<i class="fas fa-external-link-alt"></i> ${LANG==='th'?'ดูทั้งหมด':'View All'} (${containers.length} ${LANG==='th'?'รายการ':'items'})`;
        html += '</button></div>';
    }

    inner.innerHTML = html;
}

function buildContainerRow(c, num) {
    const pct = parseFloat(c.remaining_percentage) || 0;
    const pctColor = pct <= 5 ? '#ef4444' : pct <= 20 ? '#f59e0b' : '#22c55e';
    const qty = parseFloat(c.current_quantity) || 0;
    const unit = c.quantity_unit || 'mL';
    const barcode = c.bottle_code || c.qr_code || '-';
    const shortBarcode = barcode.length > 18 ? barcode.substring(0, 18) + '\u2026' : barcode;
    const loc = c.location_path || '-';
    const expiry = c.expiry_date ? formatDate(c.expiry_date) : '-';
    const isExpired = c.expiry_date && new Date(c.expiry_date) < new Date();
    const expiryStyle = isExpired ? 'color:var(--danger);font-weight:600' : '';

    let html = '<tr>';
    html += `<td style="color:var(--c3);font-size:11px;text-align:center">${num}</td>`;
    html += `<td><strong style="font-size:13px">${c.chemical_name}</strong>`;
    if (c.molecular_formula) html += `<div style="font-size:10px;color:var(--c3)">${c.molecular_formula}</div>`;
    html += '</td>';
    html += `<td><span class="uc-barcode-tag">${shortBarcode}</span></td>`;
    html += `<td style="font-size:12px;color:var(--c3)">${c.cas_number || '-'}</td>`;
    html += `<td style="font-size:12px;color:var(--c2)">${loc}</td>`;
    html += `<td style="text-align:right;white-space:nowrap">${qty} ${unit}</td>`;
    html += '<td>';
    html += '<div class="uc-pct-bar">';
    html += `<div class="uc-pct-bar-track"><div class="uc-pct-bar-fill" style="width:${pct}%;background:${pctColor}"></div></div>`;
    html += `<div class="uc-pct-bar-val" style="color:${pctColor}">${pct.toFixed(0)}%</div>`;
    html += '</div></td>';
    html += `<td style="${expiryStyle};font-size:12px;white-space:nowrap">${expiry}${isExpired?' <i class=\"fas fa-exclamation-circle\" style=\"color:var(--danger);font-size:10px\"></i>':''}</td>`;
    html += '</tr>';
    return html;
}

function openFullModal(userId) {
    const data = loadedDetails[userId];
    if (!data) return;

    const u = data.user;
    const containers = data.containers || [];
    const name = `${u.first_name} ${u.last_name}`;

    // Avatar
    const avatarEl = document.getElementById('modalAvatar');
    if (u.avatar_url) {
        avatarEl.innerHTML = `<img src="${u.avatar_url}" alt="${name}">`;
    } else {
        avatarEl.innerHTML = (u.first_name?.[0] || '') + (u.last_name?.[0] || '');
    }
    document.getElementById('modalName').textContent = name;
    const stArr = (data.user?.store_names || []);
    const subParts = [];
    if (data.user?.division_name) subParts.push(data.user.division_name);
    if (stArr.length > 0) subParts.push(stArr.slice(0,2).join(', '));
    if (u.role_name) subParts.push(u.role_name);
    document.getElementById('modalSub').textContent = subParts.join(' · ');

    // Build full table
    let html = '<table class="uc-chem-table"><thead><tr>';
    html += `<th>#</th>`;
    html += `<th>${LANG==='th'?'ชื่อสารเคมี':'Chemical Name'}</th>`;
    html += `<th>Barcode</th>`;
    html += `<th>CAS</th>`;
    html += `<th>${LANG==='th'?'ตำแหน่ง':'Location'}</th>`;
    html += `<th style="text-align:right">${LANG==='th'?'ปริมาณ':'Qty'}</th>`;
    html += `<th style="min-width:100px">${LANG==='th'?'คงเหลือ':'Remaining'}</th>`;
    html += `<th>${LANG==='th'?'หมดอายุ':'Expiry'}</th>`;
    html += '</tr></thead><tbody>';

    containers.forEach((c, i) => {
        html += buildContainerRow(c, i + 1);
    });

    html += '</tbody></table>';

    // Summary footer
    const totalQty = containers.reduce((s, c) => s + (parseFloat(c.current_quantity) || 0), 0);
    const avgPct = containers.length ? containers.reduce((s, c) => s + (parseFloat(c.remaining_percentage) || 0), 0) / containers.length : 0;
    html += '<div style="padding:12px 16px;border-top:1px solid var(--border,#e5e7eb);display:flex;gap:16px;justify-content:flex-end;font-size:12px;color:var(--c2)">';
    html += `<span><strong>${containers.length}</strong> ${LANG==='th'?'ภาชนะ':'containers'}</span>`;
    html += `<span>${LANG==='th'?'เฉลี่ยคงเหลือ':'Avg remaining'}: <strong>${avgPct.toFixed(1)}%</strong></span>`;
    html += '</div>';

    document.getElementById('modalBody').innerHTML = html;
    document.getElementById('ucModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeUcModal(e) {
    const overlay = document.getElementById('ucModalOverlay');
    if (e && e.target) {
        if (e.target !== overlay && !e.target.closest('.rpt-modal-close')) return;
    }
    overlay.classList.remove('show');
    document.body.style.overflow = '';
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeUcModal({target: document.getElementById('ucModalOverlay')});
});

// Initial load
loadData();
</script>
</body></html>
