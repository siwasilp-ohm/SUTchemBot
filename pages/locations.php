<?php
require_once __DIR__ . '/../includes/layout.php';
$user = Auth::getCurrentUser();
if (!$user) { header('Location: /v1/pages/login.php'); exit; }
$lang = I18n::getCurrentLang();
$isAdmin = ($user['role_level'] ?? $user['level'] ?? 0) >= 5;
$isManager = ($user['role_level'] ?? $user['level'] ?? 0) >= 3;
Layout::head('จัดการสถานที่จัดเก็บ');
?>
<body>
<?php Layout::sidebar('locations'); Layout::beginContent(); ?>
<?php Layout::pageHeader('จัดการสถานที่จัดเก็บ', 'fas fa-map-marker-alt', 'อาคาร → ชั้น → ห้อง → ตู้ → ชั้นวาง → ช่อง'); ?>

<!-- ═══════ Stats Cards ═══════ -->
<div id="statsRow" class="ci-auto-grid" style="margin-bottom:20px">
    <div class="ci-card ci-card-body loc-stat-card">
        <div class="loc-stat-icon" style="background:var(--accent-light)"><i class="fas fa-building" style="color:var(--accent)"></i></div>
        <div><p class="loc-stat-num" id="statBuildings">—</p><p class="loc-stat-label">อาคาร</p></div>
    </div>
    <div class="ci-card ci-card-body loc-stat-card">
        <div class="loc-stat-icon" style="background:#fff3e0"><i class="fas fa-door-open" style="color:#ef6c00"></i></div>
        <div><p class="loc-stat-num" id="statRooms">—</p><p class="loc-stat-label">ห้อง</p></div>
    </div>
    <div class="ci-card ci-card-body loc-stat-card">
        <div class="loc-stat-icon" style="background:#e8f5e9"><i class="fas fa-archive" style="color:#2e7d32"></i></div>
        <div><p class="loc-stat-num" id="statCabinets">—</p><p class="loc-stat-label">ตู้เก็บ</p></div>
    </div>
    <div class="ci-card ci-card-body loc-stat-card">
        <div class="loc-stat-icon" style="background:#fce4ec"><i class="fas fa-flask" style="color:#c62828"></i></div>
        <div><p class="loc-stat-num" id="statContainers">—</p><p class="loc-stat-label">ภาชนะ</p></div>
    </div>
</div>

<!-- ═══════ Toolbar ═══════ -->
<div class="ci-card" style="margin-bottom:16px">
    <div class="ci-card-body" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center">
        <!-- Search -->
        <div style="flex:1;min-width:200px;position:relative">
            <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#aaa;font-size:13px"></i>
            <input type="text" id="searchInput" placeholder="ค้นหาอาคาร, ห้อง, รหัส..." class="ci-input" style="padding-left:36px;width:100%">
        </div>
        <!-- View toggle -->
        <div class="loc-view-toggle">
            <button onclick="setView('tree')" id="btnTree" class="ci-btn ci-btn-sm active" title="มุมมองต้นไม้"><i class="fas fa-sitemap"></i></button>
            <button onclick="setView('grid')" id="btnGrid" class="ci-btn ci-btn-sm" title="มุมมองการ์ด"><i class="fas fa-th-large"></i></button>
            <button onclick="setView('table')" id="btnTable" class="ci-btn ci-btn-sm" title="มุมมองตาราง"><i class="fas fa-table"></i></button>
        </div>
        <?php if($isManager): ?>
        <button onclick="showAddModal()" class="ci-btn ci-btn-primary ci-btn-sm"><i class="fas fa-plus"></i> เพิ่ม</button>
        <?php endif; ?>
    </div>
</div>

<!-- ═══════ Breadcrumb ═══════ -->
<div id="breadcrumbBar" class="ci-card" style="margin-bottom:16px;display:none">
    <div class="ci-card-body" style="padding:10px 16px">
        <div id="breadcrumbContent" style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;font-size:13px"></div>
    </div>
</div>

<!-- ═══════ Search Results ═══════ -->
<div id="searchResults" style="display:none;margin-bottom:16px"></div>

<!-- ═══════ Main Content Area ═══════ -->
<div id="mainContent"></div>

<!-- ═══════ Add/Edit Modal ═══════ -->
<div id="addModal" class="ci-modal-bg">
    <div class="ci-modal" style="max-width:540px">
        <div class="ci-modal-hdr">
            <h3 id="modalTitle"><i class="fas fa-plus-circle" style="margin-right:8px;opacity:.7"></i>เพิ่มรายการ</h3>
            <button class="ci-modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="ci-modal-body" id="modalBody" style="padding:0"></div>
    </div>
</div>

<style>
/* Location page specific styles */
.loc-stat-card{display:flex;align-items:center;gap:14px;padding:16px!important}
.loc-stat-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}
.loc-stat-num{font-size:24px;font-weight:700;color:#333;line-height:1.1}
.loc-stat-label{font-size:12px;color:#999;margin-top:2px}

.loc-view-toggle{display:flex;gap:2px;background:#f1f1f1;border-radius:8px;padding:3px}
.loc-view-toggle .ci-btn{background:transparent;border:none;color:#888;border-radius:6px;padding:6px 10px}
.loc-view-toggle .ci-btn.active{background:#fff;color:var(--accent);box-shadow:0 1px 3px rgba(0,0,0,.1)}

/* Tree view */
.loc-tree-node{border-left:2px solid #e0e0e0;margin-left:12px;padding-left:16px}
.loc-tree-item{display:flex;align-items:center;gap:10px;padding:8px 12px;border-radius:8px;cursor:pointer;transition:background .15s;margin-bottom:2px}
.loc-tree-item:hover{background:#f5f7fa}
.loc-tree-item.active{background:var(--accent-light)}
.loc-tree-toggle{width:20px;height:20px;display:flex;align-items:center;justify-content:center;color:#aaa;transition:transform .2s;flex-shrink:0}
.loc-tree-toggle.open{transform:rotate(90deg)}
.loc-tree-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0}
.loc-tree-name{font-size:13px;font-weight:500;color:#333;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.loc-tree-badge{font-size:11px;padding:2px 8px;border-radius:10px;background:#f0f0f0;color:#666;white-space:nowrap}
.loc-tree-status{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.loc-tree-status.active{background:#4caf50}
.loc-tree-status.maintenance{background:#ff9800}
.loc-tree-status.closed{background:#f44336}

/* Grid cards */
.loc-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px}
.loc-grid-card{cursor:pointer;transition:transform .15s,box-shadow .15s;border-radius:12px}
.loc-grid-card:hover{transform:translateY(-2px);box-shadow:0 4px 16px rgba(0,0,0,.08)}
.loc-grid-card .card-head{display:flex;align-items:center;gap:12px;margin-bottom:12px}
.loc-grid-card .card-icon{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px}
.loc-grid-card .card-title{font-size:14px;font-weight:600;color:#333;line-height:1.3}
.loc-grid-card .card-sub{font-size:11px;color:#aaa}
.loc-grid-card .card-stats{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:12px}
.loc-grid-card .card-stat{background:#f7f8fa;border-radius:8px;padding:8px;text-align:center}
.loc-grid-card .card-stat-num{font-size:18px;font-weight:700;color:var(--accent)}
.loc-grid-card .card-stat-label{font-size:10px;color:#999;margin-top:2px}

/* Room status badge */
.loc-status{display:inline-flex;align-items:center;gap:4px;font-size:11px;padding:3px 10px;border-radius:12px;font-weight:500}
.loc-status.ready{background:#e8f5e9;color:#2e7d32}
.loc-status.maint{background:#fff3e0;color:#ef6c00}
.loc-status.closed{background:#fce4ec;color:#c62828}

/* Table view */
.loc-table{width:100%;border-collapse:separate;border-spacing:0}
.loc-table th{background:#f7f8fa;padding:10px 14px;text-align:left;font-size:12px;font-weight:600;color:#666;border-bottom:2px solid #eee;position:sticky;top:0;z-index:1}
.loc-table td{padding:10px 14px;border-bottom:1px solid #f0f0f0;font-size:13px;color:#444}
.loc-table tr:hover td{background:#f9fafb}
.loc-table tr{cursor:pointer}

/* ═══════ Pro Modal Styles ═══════ */
.modal-wizard{animation:modalSlideIn .25s ease}
@keyframes modalSlideIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}

/* Type Picker */
.modal-type-picker{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;padding:24px}
.modal-type-card{display:flex;flex-direction:column;align-items:center;gap:10px;padding:20px 12px;border:2px solid #eee;border-radius:14px;cursor:pointer;transition:all .2s;background:#fafbfc}
.modal-type-card:hover{border-color:var(--accent);background:var(--accent-light);transform:translateY(-2px);box-shadow:0 4px 12px rgba(59,130,246,.12)}
.modal-type-card.selected{border-color:var(--accent);background:var(--accent-light);box-shadow:0 0 0 3px rgba(59,130,246,.15)}
.modal-type-card .type-icon{width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:20px}
.modal-type-card .type-label{font-size:13px;font-weight:600;color:#333}
.modal-type-card .type-desc{font-size:11px;color:#999;text-align:center;line-height:1.4}
.modal-type-card.disabled{opacity:.4;pointer-events:none}

/* Form Section */
.modal-form-wrap{padding:24px;animation:modalSlideIn .2s ease}
.modal-form-header{display:flex;align-items:center;gap:14px;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid #f0f0f0}
.modal-form-header .form-icon{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0}
.modal-form-header .form-title{font-size:16px;font-weight:700;color:#333}
.modal-form-header .form-desc{font-size:12px;color:#999;margin-top:2px}

/* Better Form Fields */
.modal-field{margin-bottom:18px;position:relative}
.modal-field label{display:block;font-size:12px;font-weight:600;color:#555;margin-bottom:6px;letter-spacing:.3px}
.modal-field label .req{color:#ef4444;margin-left:2px}
.modal-field input,.modal-field select,.modal-field textarea{width:100%;padding:10px 14px;border:1.5px solid #e0e3e8;border-radius:10px;font-size:14px;color:#333;background:#fafbfc;transition:all .2s;outline:none;box-sizing:border-box}
.modal-field input:focus,.modal-field select:focus,.modal-field textarea:focus{border-color:var(--accent);background:#fff;box-shadow:0 0 0 3px rgba(59,130,246,.1)}
.modal-field input::placeholder{color:#bbb}
.modal-field .field-hint{font-size:11px;color:#aaa;margin-top:4px}
.modal-field .field-icon{position:absolute;right:14px;top:34px;color:#ccc;font-size:13px;pointer-events:none}
.modal-field-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}

/* Select with icons */
.modal-select-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.modal-select-opt{display:flex;align-items:center;gap:8px;padding:10px 12px;border:1.5px solid #e0e3e8;border-radius:10px;cursor:pointer;transition:all .15s;font-size:13px;background:#fafbfc}
.modal-select-opt:hover{border-color:var(--accent);background:var(--accent-light)}
.modal-select-opt.active{border-color:var(--accent);background:var(--accent-light);box-shadow:0 0 0 3px rgba(59,130,246,.1)}
.modal-select-opt .opt-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0}
.modal-select-opt .opt-label{font-weight:500;color:#333}

/* Footer */
.modal-footer{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:16px 24px;border-top:1px solid #f0f0f0;background:#fafbfc;border-radius:0 0 6px 6px}
.modal-footer .back-link{display:flex;align-items:center;gap:6px;font-size:13px;color:#888;cursor:pointer;transition:color .15s;background:none;border:none;padding:0}
.modal-footer .back-link:hover{color:var(--accent)}
.modal-footer .btn-submit{display:flex;align-items:center;gap:8px;padding:10px 28px;border-radius:10px;font-size:14px;font-weight:600;border:none;cursor:pointer;transition:all .2s}
.modal-footer .btn-submit.primary{background:var(--accent);color:#fff;box-shadow:0 2px 8px rgba(59,130,246,.25)}
.modal-footer .btn-submit.primary:hover{background:#2563eb;transform:translateY(-1px);box-shadow:0 4px 12px rgba(59,130,246,.3)}
.modal-footer .btn-submit:disabled{opacity:.5;cursor:not-allowed;transform:none!important}
.modal-footer .btn-cancel{background:none;border:1.5px solid #e0e3e8;color:#666;border-radius:10px;padding:10px 20px;font-size:13px;font-weight:500;cursor:pointer;transition:all .15s}
.modal-footer .btn-cancel:hover{border-color:#ccc;background:#f5f5f5}

/* Success State */
.modal-success{display:flex;flex-direction:column;align-items:center;padding:40px 24px;animation:modalSlideIn .3s ease}
.modal-success .success-icon{width:72px;height:72px;border-radius:50%;background:#e8f5e9;display:flex;align-items:center;justify-content:center;font-size:32px;color:#2e7d32;margin-bottom:16px;animation:successPop .4s ease}
@keyframes successPop{0%{transform:scale(0)}50%{transform:scale(1.15)}100%{transform:scale(1)}}
.modal-success h3{font-size:18px;font-weight:700;color:#333;margin-bottom:6px}
.modal-success p{font-size:13px;color:#999}

/* Responsive */
@media(max-width:768px){
    .loc-stat-card{padding:12px!important}
    .loc-stat-num{font-size:20px}
    .loc-stat-icon{width:40px;height:40px;font-size:16px}
    .loc-grid{grid-template-columns:1fr 1fr}
    .loc-tree-node{margin-left:8px;padding-left:10px}
    .modal-type-picker{grid-template-columns:1fr 1fr;padding:16px}
    .modal-field-row{grid-template-columns:1fr}
    .modal-select-grid{grid-template-columns:1fr}
}
@media(max-width:480px){
    .loc-grid{grid-template-columns:1fr}
    .loc-tree-name{font-size:12px}
    .loc-tree-badge{display:none}
    #statsRow{grid-template-columns:1fr 1fr!important}
    .modal-type-picker{grid-template-columns:1fr 1fr;gap:8px}
    .modal-type-card{padding:14px 8px}
    .modal-type-card .type-desc{display:none}
    .modal-form-wrap{padding:16px}
}
</style>

<?php Layout::endContent(); ?>

<script>
const IS_ADMIN = <?php echo $isAdmin ? 'true' : 'false'; ?>;
const IS_MANAGER = <?php echo $isManager ? 'true' : 'false'; ?>;
let currentView = localStorage.getItem('locView') || 'tree';
let navStack = []; // [{type,id,name}...]
let buildingsData = [];

// ═══════ Init ═══════
async function init() {
    loadStats();
    loadBuildings();
    setView(currentView, true);
    setupSearch();
}

// ═══════ Stats ═══════
async function loadStats() {
    try {
        const d = await apiFetch('/v1/api/locations.php?action=stats');
        if (d.success) {
            document.getElementById('statBuildings').textContent = d.data.buildings;
            document.getElementById('statRooms').textContent = d.data.rooms;
            document.getElementById('statCabinets').textContent = d.data.cabinets;
            document.getElementById('statContainers').textContent = d.data.containers;
        }
    } catch(e) { console.error(e); }
}

// ═══════ View Toggle ═══════
function setView(v, noReload) {
    currentView = v;
    localStorage.setItem('locView', v);
    document.querySelectorAll('.loc-view-toggle .ci-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('btn' + v.charAt(0).toUpperCase() + v.slice(1)).classList.add('active');
    if (!noReload) renderCurrentLevel();
}

// ═══════ Navigation ═══════
function navigateTo(type, id, name, extra) {
    navStack.push({type, id, name, ...extra});
    renderCurrentLevel();
    updateBreadcrumb();
}

function navigateBack(toIndex) {
    navStack = navStack.slice(0, toIndex + 1);
    renderCurrentLevel();
    updateBreadcrumb();
}

function navigateHome() {
    navStack = [];
    renderCurrentLevel();
    updateBreadcrumb();
}

function updateBreadcrumb() {
    const bar = document.getElementById('breadcrumbBar');
    const content = document.getElementById('breadcrumbContent');
    if (navStack.length === 0) {
        bar.style.display = 'none';
        return;
    }
    bar.style.display = 'block';
    let html = `<a href="javascript:navigateHome()" style="color:var(--accent);text-decoration:none;display:flex;align-items:center;gap:4px">
        <i class="fas fa-home"></i> อาคารทั้งหมด</a>`;
    navStack.forEach((item, i) => {
        html += `<i class="fas fa-chevron-right" style="color:#ccc;font-size:10px"></i>`;
        if (i < navStack.length - 1) {
            html += `<a href="javascript:navigateBack(${i})" style="color:var(--accent);text-decoration:none">${esc(item.name)}</a>`;
        } else {
            html += `<span style="color:#333;font-weight:600">${esc(item.name)}</span>`;
        }
    });
    content.innerHTML = html;
}

// ═══════ Load Buildings ═══════
async function loadBuildings() {
    try {
        const d = await apiFetch('/v1/api/locations.php?action=buildings');
        if (d.success) {
            buildingsData = d.data;
            renderCurrentLevel();
        }
    } catch(e) { console.error(e); }
}

// ═══════ Render Current Level ═══════
function renderCurrentLevel() {
    const mc = document.getElementById('mainContent');
    const level = navStack.length > 0 ? navStack[navStack.length - 1] : null;

    if (!level) {
        // Show buildings
        renderBuildings(mc);
    } else if (level.type === 'building') {
        loadAndRenderFloors(mc, level.id);
    } else if (level.type === 'floor') {
        loadAndRenderRooms(mc, level.buildingId, level.floor);
    } else if (level.type === 'room') {
        loadAndRenderCabinets(mc, level.id);
    } else if (level.type === 'cabinet') {
        loadAndRenderShelves(mc, level.id);
    } else if (level.type === 'shelf') {
        loadAndRenderSlots(mc, level.id);
    }
}

// ═══════ Render Buildings ═══════
function renderBuildings(el) {
    const data = buildingsData;
    if (!data.length) { el.innerHTML = emptyState('fas fa-building', 'ยังไม่มีข้อมูลอาคาร'); return; }

    if (currentView === 'tree') {
        el.innerHTML = `<div class="ci-card"><div class="ci-card-body" style="padding:8px">
            ${data.map(b => `
                <div class="loc-tree-item" onclick="navigateTo('building',${b.id},'${esc(b.shortname||b.name)}')">
                    <div class="loc-tree-toggle"><i class="fas fa-chevron-right"></i></div>
                    <div class="loc-tree-icon" style="background:var(--accent-light);color:var(--accent)"><i class="fas fa-building"></i></div>
                    <div class="loc-tree-name">${esc(b.name)}</div>
                    <span class="loc-tree-badge">${b.shortname||''}</span>
                    <span class="loc-tree-badge">${b.room_count} ห้อง</span>
                    <span class="loc-tree-badge">${b.floor_count} ชั้น</span>
                </div>
            `).join('')}
        </div></div>`;
    } else if (currentView === 'grid') {
        el.innerHTML = `<div class="loc-grid">${data.map(b => `
            <div class="ci-card loc-grid-card" onclick="navigateTo('building',${b.id},'${esc(b.shortname||b.name)}')">
                <div class="ci-card-body">
                    <div class="card-head">
                        <div class="card-icon" style="background:var(--accent-light);color:var(--accent)"><i class="fas fa-building"></i></div>
                        <div style="flex:1;min-width:0">
                            <div class="card-title">${esc(b.name)}</div>
                            <div class="card-sub">${esc(b.shortname||'')} ${b.name_en ? '• '+esc(b.name_en) : ''}</div>
                        </div>
                    </div>
                    <div class="card-stats">
                        <div class="card-stat"><div class="card-stat-num">${b.room_count}</div><div class="card-stat-label">ห้อง</div></div>
                        <div class="card-stat"><div class="card-stat-num">${b.floor_count}</div><div class="card-stat-label">ชั้น</div></div>
                    </div>
                </div>
            </div>`).join('')}</div>`;
    } else {
        el.innerHTML = `<div class="ci-card"><div style="overflow-x:auto"><table class="loc-table">
            <thead><tr><th>รหัส</th><th>ชื่ออาคาร</th><th>ชื่อภาษาอังกฤษ</th><th style="text-align:center">ชั้น</th><th style="text-align:center">ห้อง</th><th style="text-align:center">ตู้</th></tr></thead>
            <tbody>${data.map(b => `
                <tr onclick="navigateTo('building',${b.id},'${esc(b.shortname||b.name)}')">
                    <td><span class="ci-badge ci-badge-info">${esc(b.shortname||b.code||'')}</span></td>
                    <td style="font-weight:500">${esc(b.name)}</td>
                    <td style="color:#888">${esc(b.name_en||'—')}</td>
                    <td style="text-align:center">${b.floor_count}</td>
                    <td style="text-align:center"><strong>${b.room_count}</strong></td>
                    <td style="text-align:center">${b.cabinet_count}</td>
                </tr>`).join('')}
            </tbody></table></div></div>`;
    }
}

// ═══════ Render Floors ═══════
async function loadAndRenderFloors(el, buildingId) {
    el.innerHTML = loading();
    try {
        const d = await apiFetch(`/v1/api/locations.php?action=floors&building_id=${buildingId}`);
        if (!d.success || !d.data.length) { el.innerHTML = emptyState('fas fa-layer-group','ไม่พบข้อมูลชั้น'); return; }
        const floors = d.data;
        const bid = buildingId;

        if (currentView === 'tree') {
            el.innerHTML = `<div class="ci-card"><div class="ci-card-body" style="padding:8px">
                ${floors.map(f => `
                    <div class="loc-tree-item" onclick="navigateTo('floor',${f.floor},'ชั้น ${f.floor}',{buildingId:${bid},floor:${f.floor}})">
                        <div class="loc-tree-toggle"><i class="fas fa-chevron-right"></i></div>
                        <div class="loc-tree-icon" style="background:#fff3e0;color:#ef6c00"><i class="fas fa-layer-group"></i></div>
                        <div class="loc-tree-name">ชั้นที่ ${f.floor}</div>
                        <span class="loc-tree-badge">${f.room_count} ห้อง</span>
                        <span class="loc-tree-badge" style="background:#e8f5e9;color:#2e7d32">${f.active_rooms} พร้อม</span>
                        ${f.maintenance_rooms > 0 ? `<span class="loc-tree-badge" style="background:#fff3e0;color:#ef6c00">${f.maintenance_rooms} ปรับปรุง</span>` : ''}
                    </div>
                `).join('')}
            </div></div>`;
        } else if (currentView === 'grid') {
            el.innerHTML = `<div class="loc-grid">${floors.map(f => `
                <div class="ci-card loc-grid-card" onclick="navigateTo('floor',${f.floor},'ชั้น ${f.floor}',{buildingId:${bid},floor:${f.floor}})">
                    <div class="ci-card-body">
                        <div class="card-head">
                            <div class="card-icon" style="background:#fff3e0;color:#ef6c00"><i class="fas fa-layer-group"></i></div>
                            <div><div class="card-title">ชั้นที่ ${f.floor}</div><div class="card-sub">${f.room_count} ห้อง</div></div>
                        </div>
                        <div class="card-stats">
                            <div class="card-stat"><div class="card-stat-num" style="color:#2e7d32">${f.active_rooms}</div><div class="card-stat-label">พร้อมใช้</div></div>
                            <div class="card-stat"><div class="card-stat-num" style="color:#ef6c00">${f.maintenance_rooms||0}</div><div class="card-stat-label">ปรับปรุง</div></div>
                        </div>
                    </div>
                </div>`).join('')}</div>`;
        } else {
            el.innerHTML = `<div class="ci-card"><div style="overflow-x:auto"><table class="loc-table">
                <thead><tr><th>ชั้น</th><th style="text-align:center">ห้องทั้งหมด</th><th style="text-align:center">พร้อมใช้</th><th style="text-align:center">ปรับปรุง</th><th style="text-align:center">ตู้</th></tr></thead>
                <tbody>${floors.map(f => `
                    <tr onclick="navigateTo('floor',${f.floor},'ชั้น ${f.floor}',{buildingId:${bid},floor:${f.floor}})">
                        <td style="font-weight:600"><i class="fas fa-layer-group" style="color:#ef6c00;margin-right:6px"></i> ชั้นที่ ${f.floor}</td>
                        <td style="text-align:center"><strong>${f.room_count}</strong></td>
                        <td style="text-align:center"><span class="loc-status ready">${f.active_rooms}</span></td>
                        <td style="text-align:center">${f.maintenance_rooms > 0 ? `<span class="loc-status maint">${f.maintenance_rooms}</span>` : '—'}</td>
                        <td style="text-align:center">${f.cabinet_count}</td>
                    </tr>`).join('')}
                </tbody></table></div></div>`;
        }
    } catch(e) { el.innerHTML = emptyState('fas fa-exclamation-triangle', e.message); }
}

// ═══════ Render Rooms ═══════
async function loadAndRenderRooms(el, buildingId, floor) {
    el.innerHTML = loading();
    try {
        const d = await apiFetch(`/v1/api/locations.php?action=rooms&building_id=${buildingId}&floor=${floor}`);
        if (!d.success || !d.data.length) { el.innerHTML = emptyState('fas fa-door-open','ไม่พบห้องในชั้นนี้'); return; }
        const rooms = d.data;

        if (currentView === 'tree') {
            el.innerHTML = `<div class="ci-card"><div class="ci-card-body" style="padding:8px">
                ${rooms.map(r => `
                    <div class="loc-tree-item" onclick="navigateTo('room',${r.id},'${esc(r.name)}')">
                        <div class="loc-tree-toggle"><i class="fas fa-chevron-right"></i></div>
                        <div class="loc-tree-icon" style="background:#e3f2fd;color:#1565c0"><i class="fas fa-door-open"></i></div>
                        <div class="loc-tree-name">${esc(r.name)}</div>
                        <span class="loc-tree-badge">${esc(r.code||'')}</span>
                        ${statusDot(r.status_text)}
                        ${r.cabinet_count > 0 ? `<span class="loc-tree-badge">${r.cabinet_count} ตู้</span>` : ''}
                    </div>
                `).join('')}
            </div></div>`;
        } else if (currentView === 'grid') {
            el.innerHTML = `<div class="loc-grid">${rooms.map(r => `
                <div class="ci-card loc-grid-card" onclick="navigateTo('room',${r.id},'${esc(r.name)}')">
                    <div class="ci-card-body">
                        <div class="card-head">
                            <div class="card-icon" style="background:#e3f2fd;color:#1565c0"><i class="fas fa-door-open"></i></div>
                            <div style="flex:1;min-width:0">
                                <div class="card-title" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(r.name)}</div>
                                <div class="card-sub">${esc(r.code||'')} ${r.area_sqm ? '• '+r.area_sqm+' ตร.ม.' : ''}</div>
                            </div>
                            ${statusBadge(r.status_text)}
                        </div>
                        <div class="card-stats">
                            <div class="card-stat"><div class="card-stat-num">${r.cabinet_count}</div><div class="card-stat-label">ตู้เก็บ</div></div>
                            <div class="card-stat"><div class="card-stat-num">${r.capacity_persons||'—'}</div><div class="card-stat-label">ความจุ (คน)</div></div>
                        </div>
                    </div>
                </div>`).join('')}</div>`;
        } else {
            el.innerHTML = `<div class="ci-card"><div style="overflow-x:auto"><table class="loc-table">
                <thead><tr><th>รหัส</th><th>ชื่อห้อง</th><th>สถานะ</th><th style="text-align:center">พื้นที่</th><th style="text-align:center">ความจุ</th><th style="text-align:center">ตู้</th></tr></thead>
                <tbody>${rooms.map(r => `
                    <tr onclick="navigateTo('room',${r.id},'${esc(r.name)}')">
                        <td><span class="ci-badge ci-badge-info">${esc(r.code||'—')}</span></td>
                        <td style="font-weight:500;max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(r.name)}</td>
                        <td>${statusBadge(r.status_text)}</td>
                        <td style="text-align:center">${r.area_sqm ? r.area_sqm+' ตร.ม.' : '—'}</td>
                        <td style="text-align:center">${r.capacity_persons||'—'}</td>
                        <td style="text-align:center"><strong>${r.cabinet_count}</strong></td>
                    </tr>`).join('')}
                </tbody></table></div></div>`;
        }
    } catch(e) { el.innerHTML = emptyState('fas fa-exclamation-triangle', e.message); }
}

// ═══════ Render Cabinets ═══════
async function loadAndRenderCabinets(el, roomId) {
    el.innerHTML = loading();
    try {
        const d = await apiFetch(`/v1/api/locations.php?action=cabinets&room_id=${roomId}`);
        if (!d.success || !d.data.length) {
            el.innerHTML = emptyState('fas fa-archive', 'ยังไม่มีตู้เก็บในห้องนี้') +
                (IS_MANAGER ? `<div style="text-align:center;margin-top:12px"><button onclick="showAddModal('cabinet',{room_id:${roomId}})" class="ci-btn ci-btn-primary ci-btn-sm"><i class="fas fa-plus"></i> เพิ่มตู้เก็บ</button></div>` : '');
            return;
        }
        const cabs = d.data;

        if (currentView === 'tree') {
            el.innerHTML = `<div class="ci-card"><div class="ci-card-body" style="padding:8px">
                ${cabs.map(c => `
                    <div class="loc-tree-item" onclick="navigateTo('cabinet',${c.id},'${esc(c.name)}')">
                        <div class="loc-tree-toggle"><i class="fas fa-chevron-right"></i></div>
                        <div class="loc-tree-icon" style="background:#f3e5f5;color:#7b1fa2"><i class="fas fa-archive"></i></div>
                        <div class="loc-tree-name">${esc(c.name)}</div>
                        <span class="loc-tree-badge">${cabinetTypeLabel(c.type)}</span>
                        <span class="loc-tree-badge">${c.shelf_count} ชั้นวาง</span>
                        ${c.container_count > 0 ? `<span class="loc-tree-badge" style="background:#e8f5e9;color:#2e7d32">${c.container_count} ภาชนะ</span>` : ''}
                    </div>
                `).join('')}
                ${IS_MANAGER ? `<div style="padding:8px"><button onclick="showAddModal('cabinet',{room_id:${roomId}})" class="ci-btn ci-btn-outline ci-btn-sm" style="width:100%"><i class="fas fa-plus"></i> เพิ่มตู้เก็บ</button></div>` : ''}
            </div></div>`;
        } else if (currentView === 'grid') {
            el.innerHTML = `<div class="loc-grid">${cabs.map(c => `
                <div class="ci-card loc-grid-card" onclick="navigateTo('cabinet',${c.id},'${esc(c.name)}')">
                    <div class="ci-card-body">
                        <div class="card-head">
                            <div class="card-icon" style="background:#f3e5f5;color:#7b1fa2"><i class="fas fa-archive"></i></div>
                            <div><div class="card-title">${esc(c.name)}</div><div class="card-sub">${cabinetTypeLabel(c.type)}</div></div>
                        </div>
                        <div class="card-stats">
                            <div class="card-stat"><div class="card-stat-num">${c.shelf_count}</div><div class="card-stat-label">ชั้นวาง</div></div>
                            <div class="card-stat"><div class="card-stat-num">${c.container_count}</div><div class="card-stat-label">ภาชนะ</div></div>
                        </div>
                    </div>
                </div>`).join('')}</div>`;
        } else {
            el.innerHTML = `<div class="ci-card"><div style="overflow-x:auto"><table class="loc-table">
                <thead><tr><th>ชื่อตู้</th><th>ประเภท</th><th style="text-align:center">ชั้นวาง</th><th style="text-align:center">ภาชนะ</th></tr></thead>
                <tbody>${cabs.map(c => `
                    <tr onclick="navigateTo('cabinet',${c.id},'${esc(c.name)}')">
                        <td style="font-weight:500"><i class="fas fa-archive" style="color:#7b1fa2;margin-right:6px"></i>${esc(c.name)}</td>
                        <td>${cabinetTypeLabel(c.type)}</td>
                        <td style="text-align:center">${c.shelf_count}</td>
                        <td style="text-align:center"><strong>${c.container_count}</strong></td>
                    </tr>`).join('')}
                </tbody></table></div></div>`;
        }
    } catch(e) { el.innerHTML = emptyState('fas fa-exclamation-triangle', e.message); }
}

// ═══════ Render Shelves ═══════
async function loadAndRenderShelves(el, cabinetId) {
    el.innerHTML = loading();
    try {
        const d = await apiFetch(`/v1/api/locations.php?action=shelves&cabinet_id=${cabinetId}`);
        if (!d.success || !d.data.length) {
            el.innerHTML = emptyState('fas fa-layer-group', 'ยังไม่มีชั้นวาง') +
                (IS_MANAGER ? `<div style="text-align:center;margin-top:12px"><button onclick="showAddModal('shelf',{cabinet_id:${cabinetId}})" class="ci-btn ci-btn-primary ci-btn-sm"><i class="fas fa-plus"></i> เพิ่มชั้นวาง</button></div>` : '');
            return;
        }
        const shelves = d.data;
        el.innerHTML = `<div class="ci-card"><div class="ci-card-body" style="padding:8px">
            ${shelves.map(s => `
                <div class="loc-tree-item" onclick="navigateTo('shelf',${s.id},'${esc(s.name)}')">
                    <div class="loc-tree-toggle"><i class="fas fa-chevron-right"></i></div>
                    <div class="loc-tree-icon" style="background:#e0f2f1;color:#00695c"><i class="fas fa-layer-group"></i></div>
                    <div class="loc-tree-name">${esc(s.name)} (ระดับ ${s.level})</div>
                    <span class="loc-tree-badge">${s.slot_count} ช่อง</span>
                    ${s.container_count > 0 ? `<span class="loc-tree-badge" style="background:#e8f5e9;color:#2e7d32">${s.container_count} ภาชนะ</span>` : ''}
                </div>
            `).join('')}
            ${IS_MANAGER ? `<div style="padding:8px"><button onclick="showAddModal('shelf',{cabinet_id:${cabinetId}})" class="ci-btn ci-btn-outline ci-btn-sm" style="width:100%"><i class="fas fa-plus"></i> เพิ่มชั้นวาง</button></div>` : ''}
        </div></div>`;
    } catch(e) { el.innerHTML = emptyState('fas fa-exclamation-triangle', e.message); }
}

// ═══════ Render Slots ═══════
async function loadAndRenderSlots(el, shelfId) {
    el.innerHTML = loading();
    try {
        const d = await apiFetch(`/v1/api/locations.php?action=slots&shelf_id=${shelfId}`);
        if (!d.success || !d.data.length) {
            el.innerHTML = emptyState('fas fa-th', 'ยังไม่มีช่องเก็บ') +
                (IS_MANAGER ? `<div style="text-align:center;margin-top:12px"><button onclick="showAddModal('slot',{shelf_id:${shelfId}})" class="ci-btn ci-btn-primary ci-btn-sm"><i class="fas fa-plus"></i> เพิ่มช่อง</button></div>` : '');
            return;
        }
        const slots = d.data;
        el.innerHTML = `<div class="ci-card"><div class="ci-card-body">
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px">
                ${slots.map(s => `
                    <div style="border:2px solid ${s.container_id ? '#4caf50' : '#e0e0e0'};border-radius:10px;padding:14px;text-align:center;background:${s.container_id ? '#f1f8e9' : '#fafafa'}">
                        <div style="font-size:11px;color:#999;margin-bottom:4px">${esc(s.code||s.name)}</div>
                        <div style="font-size:24px;margin-bottom:6px">${s.container_id ? '<i class="fas fa-flask" style="color:#4caf50"></i>' : '<i class="fas fa-square" style="color:#e0e0e0"></i>'}</div>
                        ${s.container_id ?
                            `<div style="font-size:12px;font-weight:600;color:#333">${esc(s.chemical_name||'')}</div>
                             <div style="font-size:10px;color:#888">${esc(s.container_number||'')}</div>` :
                            `<div style="font-size:11px;color:#ccc">ว่าง</div>`}
                    </div>
                `).join('')}
            </div>
            ${IS_MANAGER ? `<div style="margin-top:12px"><button onclick="showAddModal('slot',{shelf_id:${shelfId}})" class="ci-btn ci-btn-outline ci-btn-sm" style="width:100%"><i class="fas fa-plus"></i> เพิ่มช่อง</button></div>` : ''}
        </div></div>`;
    } catch(e) { el.innerHTML = emptyState('fas fa-exclamation-triangle', e.message); }
}

// ═══════ Search ═══════
function setupSearch() {
    let timer;
    document.getElementById('searchInput').addEventListener('input', function() {
        clearTimeout(timer);
        const q = this.value.trim();
        if (q.length < 2) { document.getElementById('searchResults').style.display = 'none'; return; }
        timer = setTimeout(() => doSearch(q), 300);
    });
}

async function doSearch(q) {
    try {
        const d = await apiFetch(`/v1/api/locations.php?action=search&q=${encodeURIComponent(q)}`);
        const sr = document.getElementById('searchResults');
        if (!d.success || !d.data.length) { sr.innerHTML = `<div class="ci-card ci-card-body" style="text-align:center;color:#999">ไม่พบผลลัพธ์</div>`; sr.style.display = 'block'; return; }
        sr.style.display = 'block';
        sr.innerHTML = `<div class="ci-card"><div class="ci-card-body" style="padding:8px">
            <div style="font-size:12px;color:#999;padding:4px 12px;margin-bottom:4px">ผลการค้นหา (${d.data.length})</div>
            ${d.data.map(item => {
                const icon = item.type === 'building' ? 'fa-building' : item.type === 'room' ? 'fa-door-open' : 'fa-archive';
                const color = item.type === 'building' ? 'var(--accent)' : item.type === 'room' ? '#1565c0' : '#7b1fa2';
                return `<div class="loc-tree-item" onclick="searchNavigate('${item.type}',${item.id},'${esc(item.name)}',${JSON.stringify(item).replace(/'/g,"\\'")})" style="padding:10px 12px">
                    <div class="loc-tree-icon" style="background:${color}15;color:${color}"><i class="fas ${icon}"></i></div>
                    <div style="flex:1;min-width:0">
                        <div class="loc-tree-name">${esc(item.name)}</div>
                        <div style="font-size:11px;color:#aaa">${esc(item.building_name||'')} ${item.floor ? '• ชั้น '+item.floor : ''} ${item.code ? '• '+item.code : ''}</div>
                    </div>
                    <span class="loc-tree-badge">${item.type === 'building' ? 'อาคาร' : item.type === 'room' ? 'ห้อง' : 'ตู้'}</span>
                </div>`;
            }).join('')}
        </div></div>`;
    } catch(e) { console.error(e); }
}

function searchNavigate(type, id, name, item) {
    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('searchInput').value = '';
    navStack = [];
    if (type === 'building') {
        navigateTo('building', id, name);
    } else if (type === 'room') {
        // Build breadcrumb path
        if (item.building_name) navigateTo('building', null, item.building_code || item.building_name);
        navigateTo('room', id, name);
    }
}

// ═══════ Add Modal — Pro Wizard ═══════
const LOC_TYPES = {
    building: {label:'อาคาร',icon:'fa-building',color:'#3b82f6',bg:'#eff6ff',desc:'เพิ่มอาคารใหม่ในระบบ'},
    room:     {label:'ห้อง',icon:'fa-door-open',color:'#1565c0',bg:'#e3f2fd',desc:'เพิ่มห้องปฏิบัติการ/ห้องเก็บ'},
    cabinet:  {label:'ตู้เก็บ',icon:'fa-archive',color:'#7b1fa2',bg:'#f3e5f5',desc:'เพิ่มตู้/ตู้ดูดควัน/ตู้เย็น'},
    shelf:    {label:'ชั้นวาง',icon:'fa-layer-group',color:'#00695c',bg:'#e0f2f1',desc:'เพิ่มชั้นวางในตู้เก็บ'},
    slot:     {label:'ช่องเก็บ',icon:'fa-th',color:'#ef6c00',bg:'#fff3e0',desc:'เพิ่มช่องสำหรับวางภาชนะ'}
};
let modalExtra = {};
let modalSelectedType = null;

function showAddModal(type, extra) {
    modalExtra = extra || {};
    modalSelectedType = type || null;

    // Auto-detect context from navStack
    const level = navStack.length > 0 ? navStack[navStack.length - 1] : null;
    if (level && !Object.keys(modalExtra).length) {
        if (level.type === 'building') modalExtra.building_id = level.id;
        if (level.type === 'floor') { modalExtra.building_id = level.buildingId; modalExtra.floor = level.floor; }
        if (level.type === 'room') modalExtra.room_id = level.id;
        if (level.type === 'cabinet') modalExtra.cabinet_id = level.id;
        if (level.type === 'shelf') modalExtra.shelf_id = level.id;
    }

    if (modalSelectedType) {
        showAddForm(modalSelectedType);
    } else {
        showTypePicker(level);
    }

    document.getElementById('addModal').classList.add('show');
}

function showTypePicker(level) {
    const suggested = !level ? 'building' : level.type === 'building' ? 'room' : level.type === 'floor' ? 'room' : level.type === 'room' ? 'cabinet' : level.type === 'cabinet' ? 'shelf' : 'slot';
    // Determine which types are available at current level
    const available = getAvailableTypes(level);

    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle" style="margin-right:8px;opacity:.7"></i>เพิ่มรายการใหม่';

    let html = `<div class="modal-wizard">
        <div style="padding:20px 24px 0;font-size:13px;color:#888">
            <i class="fas fa-info-circle" style="margin-right:4px"></i> เลือกประเภทที่ต้องการเพิ่ม
        </div>
        <div class="modal-type-picker">`;

    for (const [key, cfg] of Object.entries(LOC_TYPES)) {
        const enabled = available.includes(key);
        const isSuggested = key === suggested;
        html += `<div class="modal-type-card${!enabled?' disabled':''}${isSuggested?' selected':''}" onclick="${enabled?"showAddForm('"+key+"')":''}">
            <div class="type-icon" style="background:${cfg.bg};color:${cfg.color}"><i class="fas ${cfg.icon}"></i></div>
            <div class="type-label">${cfg.label}</div>
            <div class="type-desc">${cfg.desc}</div>
            ${isSuggested ? '<div style="font-size:10px;color:var(--accent);font-weight:600;margin-top:2px"><i class="fas fa-star" style="font-size:8px"></i> แนะนำ</div>' : ''}
        </div>`;
    }

    html += `</div>
        <div class="modal-footer">
            <div></div>
            <button class="btn-cancel" onclick="closeModal()">ยกเลิก</button>
        </div>
    </div>`;

    document.getElementById('modalBody').innerHTML = html;
}

function getAvailableTypes(level) {
    if (!level) return IS_ADMIN ? ['building','room','cabinet','shelf','slot'] : ['room','cabinet','shelf','slot'];
    if (level.type === 'building' || level.type === 'floor') return ['room'];
    if (level.type === 'room') return ['cabinet'];
    if (level.type === 'cabinet') return ['shelf'];
    if (level.type === 'shelf') return ['slot'];
    return [];
}

function showAddForm(type) {
    modalSelectedType = type;
    const cfg = LOC_TYPES[type];
    document.getElementById('modalTitle').innerHTML = `<i class="fas ${cfg.icon}" style="margin-right:8px;color:${cfg.color}"></i>เพิ่ม${cfg.label}`;

    let fieldsHtml = '';

    if (type === 'building') {
        fieldsHtml = `
            ${mField('name','ชื่ออาคาร (ภาษาไทย)','text',true,'','เช่น อาคารวิชาการ 1','fa-building')}
            ${mField('name_en','ชื่อภาษาอังกฤษ','text',false,'','e.g. Academic Building 1','fa-font')}
            <div class="modal-field-row">
                ${mField('shortname','ชื่อย่อ','text',false,'','เช่น F1','fa-tag')}
                ${mField('code','รหัสอาคาร','text',false,'','เช่น B001','fa-barcode')}
            </div>`;
    } else if (type === 'room') {
        fieldsHtml = `
            ${mField('name','ชื่อห้อง (ภาษาไทย)','text',true,'','เช่น ห้องปฏิบัติการเคมี 1','fa-door-open')}
            ${mField('name_en','ชื่อภาษาอังกฤษ','text',false,'','e.g. Chemistry Lab 1','fa-font')}
            <div class="modal-field-row">
                ${mField('code','รหัสห้อง','text',false,'','เช่น F01101','fa-barcode')}
                ${mField('floor','ชั้นที่','number',false,modalExtra.floor||1,'','fa-layer-group')}
            </div>
            <div class="modal-field-row">
                ${mField('area_sqm','พื้นที่ (ตร.ม.)','number',false,'','','fa-ruler-combined')}
                ${mField('capacity_persons','ความจุ (คน)','number',false,'','','fa-users')}
            </div>`;
    } else if (type === 'cabinet') {
        fieldsHtml = `
            ${mField('name','ชื่อตู้','text',true,'','เช่น ตู้เก็บสารเคมี A1','fa-archive')}
            ${mField('code','รหัสตู้','text',false,'','เช่น CAB-001','fa-barcode')}
            <div class="modal-field">
                <label>ประเภทตู้ <span class="req">*</span></label>
                <div class="modal-select-grid" id="cabinetTypeGrid">
                    ${cabinetTypeOption('storage','fa-box','ตู้เก็บทั่วไป','#7b1fa2','#f3e5f5',true)}
                    ${cabinetTypeOption('fume_hood','fa-wind','ตู้ดูดควัน','#00695c','#e0f2f1')}
                    ${cabinetTypeOption('refrigerator','fa-temperature-low','ตู้เย็น','#1565c0','#e3f2fd')}
                    ${cabinetTypeOption('freezer','fa-snowflake','ตู้แช่แข็ง','#0277bd','#e1f5fe')}
                    ${cabinetTypeOption('safety_cabinet','fa-shield-alt','ตู้นิรภัย','#c62828','#fce4ec')}
                    ${cabinetTypeOption('other','fa-ellipsis-h','อื่นๆ','#888','#f5f5f5')}
                </div>
                <input type="hidden" name="cabinet_type" id="cabinetTypeVal" value="storage">
            </div>
            ${mField('dimensions','ขนาด กxยxส (ซม.)','text',false,'','เช่น 60x45x180','fa-ruler')}`;
    } else if (type === 'shelf') {
        fieldsHtml = `
            ${mField('name','ชื่อชั้นวาง','text',true,'','เช่น ชั้นที่ 1','fa-layer-group')}
            <div class="modal-field-row">
                ${mField('level','ระดับ (ล่าง→บน)','number',false,1,'ลำดับจากล่างขึ้นบน','fa-sort-amount-up')}
                ${mField('capacity','ความจุ (ช่อง)','number',false,'','จำนวนช่องสูงสุด','fa-th')}
            </div>
            ${mField('max_weight','น้ำหนักสูงสุด (กก.)','number',false,'','น้ำหนักที่รับได้','fa-weight-hanging')}`;
    } else if (type === 'slot') {
        fieldsHtml = `
            ${mField('name','ชื่อช่อง','text',true,'','เช่น ช่อง A1','fa-th')}
            <div class="modal-field-row">
                ${mField('code','รหัสช่อง','text',false,'','เช่น S001','fa-barcode')}
                ${mField('position','ลำดับตำแหน่ง','number',false,1,'จากซ้ายไปขวา','fa-arrows-alt-h')}
            </div>`;
    }

    const html = `<div class="modal-wizard">
        <form id="addForm" onsubmit="submitAdd(event)">
            <div class="modal-form-wrap">
                <div class="modal-form-header">
                    <div class="form-icon" style="background:${cfg.bg};color:${cfg.color}"><i class="fas ${cfg.icon}"></i></div>
                    <div>
                        <div class="form-title">เพิ่ม${cfg.label}ใหม่</div>
                        <div class="form-desc">${cfg.desc} — กรอกข้อมูลด้านล่าง</div>
                    </div>
                </div>
                ${fieldsHtml}
            </div>
            <div class="modal-footer">
                <button type="button" class="back-link" onclick="showTypePicker(navStack.length?navStack[navStack.length-1]:null)">
                    <i class="fas fa-arrow-left"></i> เลือกประเภทอื่น
                </button>
                <div style="display:flex;gap:10px">
                    <button type="button" class="btn-cancel" onclick="closeModal()">ยกเลิก</button>
                    <button type="submit" class="btn-submit primary" id="btnSubmitAdd">
                        <i class="fas fa-check"></i> บันทึก
                    </button>
                </div>
            </div>
        </form>
    </div>`;

    document.getElementById('modalBody').innerHTML = html;
    // Focus first input
    setTimeout(() => {
        const firstInput = document.querySelector('#addForm input[type="text"]');
        if (firstInput) firstInput.focus();
    }, 100);
}

function mField(name, label, type, required, value, hint, icon) {
    type = type || 'text';
    return `<div class="modal-field">
        <label>${label}${required ? '<span class="req"> *</span>' : ''}</label>
        <input type="${type}" name="${name}" ${required?'required':''} ${value!==undefined&&value!==''?'value="'+value+'"':''} ${type==='number'?'step="any"':''} placeholder="${hint||''}">
        ${icon ? '<i class="fas '+icon+' field-icon"></i>' : ''}
    </div>`;
}

function cabinetTypeOption(val, icon, label, color, bg, active) {
    return `<div class="modal-select-opt${active?' active':''}" data-val="${val}" onclick="selectCabinetType(this,'${val}')">
        <div class="opt-icon" style="background:${bg};color:${color}"><i class="fas ${icon}"></i></div>
        <span class="opt-label">${label}</span>
    </div>`;
}

function selectCabinetType(el, val) {
    document.querySelectorAll('#cabinetTypeGrid .modal-select-opt').forEach(o => o.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('cabinetTypeVal').value = val;
}

async function submitAdd(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmitAdd');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังบันทึก...';

    const form = document.getElementById('addForm');
    const data = Object.fromEntries(new FormData(form));
    Object.assign(data, modalExtra, {type: modalSelectedType});

    try {
        const d = await apiFetch('/v1/api/locations.php?action=create', {
            method: 'POST',
            body: JSON.stringify(data)
        });
        if (d.success) {
            showAddSuccess(data.name || data.code || '');
            renderCurrentLevel();
            loadStats();
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i> บันทึก';
            showFieldError(d.error || 'เกิดข้อผิดพลาด กรุณาลองใหม่');
        }
    } catch(err) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check"></i> บันทึก';
        showFieldError(err.message);
    }
}

function showAddSuccess(name) {
    const cfg = LOC_TYPES[modalSelectedType];
    document.getElementById('modalBody').innerHTML = `
        <div class="modal-success">
            <div class="success-icon"><i class="fas fa-check"></i></div>
            <h3>เพิ่ม${cfg.label}สำเร็จ!</h3>
            <p>${esc(name)} ถูกเพิ่มเข้าสู่ระบบเรียบร้อยแล้ว</p>
            <div style="margin-top:24px;display:flex;gap:10px">
                <button onclick="closeModal()" class="btn-cancel" style="border:1.5px solid #e0e3e8;border-radius:10px;padding:10px 20px;font-size:13px;cursor:pointer;background:none">ปิด</button>
                <button onclick="showAddModal()" class="btn-submit primary" style="border:none;border-radius:10px;padding:10px 24px;font-size:13px;font-weight:600;cursor:pointer;background:var(--accent);color:#fff">
                    <i class="fas fa-plus"></i> เพิ่มอีก
                </button>
            </div>
        </div>`;
    // Auto close after 3 seconds
    setTimeout(() => {
        if (document.getElementById('addModal').classList.contains('show')) {
            closeModal();
        }
    }, 4000);
}

function showFieldError(msg) {
    // Remove existing error
    document.querySelectorAll('.modal-error-msg').forEach(e => e.remove());
    const wrap = document.querySelector('.modal-form-wrap');
    if (wrap) {
        const errDiv = document.createElement('div');
        errDiv.className = 'modal-error-msg';
        errDiv.style.cssText = 'background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px 16px;margin-top:4px;display:flex;align-items:center;gap:10px;font-size:13px;color:#991b1b;animation:modalSlideIn .2s ease';
        errDiv.innerHTML = `<i class="fas fa-exclamation-circle" style="color:#ef4444;font-size:16px;flex-shrink:0"></i><span>${esc(msg)}</span>`;
        wrap.appendChild(errDiv);
        setTimeout(() => errDiv.remove(), 5000);
    }
}

function closeModal() {
    document.getElementById('addModal').classList.remove('show');
    modalSelectedType = null;
    modalExtra = {};
}
// Close on backdrop click
document.getElementById('addModal').addEventListener('click', e => {
    if (e.target === e.currentTarget) closeModal();
});

// ═══════ Helpers ═══════
function statusBadge(s) {
    if (!s || s === 'พร้อมใช้งาน') return '<span class="loc-status ready">พร้อม</span>';
    if (s === 'ปิดปรับปรุง') return '<span class="loc-status maint">ปรับปรุง</span>';
    if (s === 'ไม่เปิดให้บริการ') return '<span class="loc-status closed">ปิด</span>';
    return `<span class="loc-status">${esc(s)}</span>`;
}

function statusDot(s) {
    if (!s || s === 'พร้อมใช้งาน') return '<div class="loc-tree-status active" title="พร้อมใช้งาน"></div>';
    if (s === 'ปิดปรับปรุง') return '<div class="loc-tree-status maintenance" title="ปิดปรับปรุง"></div>';
    if (s === 'ไม่เปิดให้บริการ') return '<div class="loc-tree-status closed" title="ไม่เปิดให้บริการ"></div>';
    return '';
}

function cabinetTypeLabel(t) {
    const m = {storage:'ตู้เก็บ',fume_hood:'ตู้ดูดควัน',refrigerator:'ตู้เย็น',freezer:'ตู้แช่แข็ง',safety_cabinet:'ตู้นิรภัย',other:'อื่นๆ'};
    return m[t] || t || 'ตู้เก็บ';
}

function loading() {
    return '<div class="ci-loading" style="padding:40px"><div class="ci-spinner"></div></div>';
}

function emptyState(icon, text) {
    return `<div class="ci-card ci-card-body" style="text-align:center;padding:40px;color:#999">
        <i class="${icon}" style="font-size:36px;margin-bottom:12px;display:block;opacity:.3"></i>
        <p>${text}</p>
    </div>`;
}

function esc(s) {
    if (!s) return '';
    const d = document.createElement('div');
    d.textContent = String(s);
    return d.innerHTML;
}

init();
</script>
</body></html>
