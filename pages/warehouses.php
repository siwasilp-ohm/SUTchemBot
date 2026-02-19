<?php
/**
 * Chemical Warehouses — จัดการคลังสารเคมี
 * Admin: Full management with filters, detail, edit
 * CEO: Organization overview dashboard with charts
 */
require_once __DIR__ . '/../includes/layout.php';
$user = Auth::getCurrentUser();
if (!$user) { header('Location: /v1/pages/login.php'); exit; }
$role = $user['role_name'] ?? 'user';
if (!in_array($role, ['admin', 'ceo', 'lab_manager'])) { header('Location: /v1/'); exit; }
$isAdmin = $role === 'admin';
$isCeo   = $role === 'ceo';
$lang = I18n::getCurrentLang();
$pageTitle = $lang === 'th' ? 'คลังสารเคมี' : 'Chemical Warehouses';
Layout::head($pageTitle);
?>
<body>
<?php Layout::sidebar('warehouses'); Layout::beginContent(); ?>

<!-- Page Header -->
<div class="ci-pg-hdr">
    <div>
        <div class="ci-pg-title"><i class="fas fa-warehouse"></i> <?php echo $pageTitle; ?></div>
        <div class="ci-pg-sub"><?php echo $lang === 'th' ? 'ภาพรวมคลังสารเคมีทั้งหมดในองค์กร' : 'Organization-wide chemical warehouse overview'; ?></div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <div class="wh-view-toggle">
            <button class="wh-vt-btn active" data-view="overview" onclick="switchView('overview')"><i class="fas fa-chart-pie"></i> <span>Overview</span></button>
            <button class="wh-vt-btn" data-view="list" onclick="switchView('list')"><i class="fas fa-list"></i> <span>รายการ</span></button>
            <button class="wh-vt-btn" data-view="map" onclick="switchView('map')"><i class="fas fa-building"></i> <span>อาคาร</span></button>
        </div>
    </div>
</div>

<!-- ═══ Overview View (CEO Dashboard) ═══ -->
<div id="viewOverview">
    <!-- Grand Total Stats -->
    <div class="wh-grand-stats">
        <div class="wh-grand-card wh-gc-primary">
            <div class="wh-gc-icon"><i class="fas fa-warehouse"></i></div>
            <div class="wh-gc-body">
                <div class="wh-gc-val" id="gTotal">-</div>
                <div class="wh-gc-lbl">คลังสารเคมีทั้งหมด</div>
            </div>
        </div>
        <div class="wh-grand-card wh-gc-green">
            <div class="wh-gc-icon"><i class="fas fa-box-open"></i></div>
            <div class="wh-gc-body">
                <div class="wh-gc-val" id="gActive">-</div>
                <div class="wh-gc-lbl">คลังที่มีสารเคมี</div>
            </div>
        </div>
        <div class="wh-grand-card wh-gc-blue">
            <div class="wh-gc-icon"><i class="fas fa-wine-bottle"></i></div>
            <div class="wh-gc-body">
                <div class="wh-gc-val" id="gBottles">-</div>
                <div class="wh-gc-lbl">ขวดสารเคมี</div>
            </div>
        </div>
        <div class="wh-grand-card wh-gc-amber">
            <div class="wh-gc-icon"><i class="fas fa-flask"></i></div>
            <div class="wh-gc-body">
                <div class="wh-gc-val" id="gChemicals">-</div>
                <div class="wh-gc-lbl">ชนิดสารเคมี</div>
            </div>
        </div>
        <div class="wh-grand-card wh-gc-red">
            <div class="wh-gc-icon"><i class="fas fa-weight-hanging"></i></div>
            <div class="wh-gc-body">
                <div class="wh-gc-val" id="gWeight">-</div>
                <div class="wh-gc-lbl">ปริมาณรวม (kg)</div>
            </div>
        </div>
    </div>

    <!-- Division Breakdown + Top Warehouses -->
    <div class="wh-grid-2">
        <!-- Division breakdown -->
        <div class="ci-card">
            <div class="ci-card-hdr">
                <h3><i class="fas fa-sitemap" style="color:var(--accent);margin-right:6px"></i> สัดส่วนตามฝ่าย</h3>
            </div>
            <div class="ci-card-body" id="divisionChart" style="padding:0">
                <div class="ci-loading"><div class="ci-spinner"></div></div>
            </div>
        </div>

        <!-- Top warehouses by weight -->
        <div class="ci-card">
            <div class="ci-card-hdr">
                <h3><i class="fas fa-trophy" style="color:#d97706;margin-right:6px"></i> Top 10 คลัง (ปริมาณ kg)</h3>
            </div>
            <div class="ci-card-body" id="topWeightList" style="padding:8px 0">
                <div class="ci-loading"><div class="ci-spinner"></div></div>
            </div>
        </div>
    </div>

    <!-- Building map overview -->
    <div class="ci-card" style="margin-top:16px">
        <div class="ci-card-hdr">
            <h3><i class="fas fa-building" style="color:#6C5CE7;margin-right:6px"></i> สารเคมีตามอาคาร</h3>
        </div>
        <div class="ci-card-body" id="buildingGrid" style="padding:12px">
            <div class="ci-loading"><div class="ci-spinner"></div></div>
        </div>
    </div>
</div>

<!-- ═══ List View (Admin Management) ═══ -->
<div id="viewList" style="display:none">
    <!-- Filters -->
    <div class="ci-card" style="margin-bottom:16px">
        <div class="ci-card-body" style="padding:12px 16px">
            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
                <div style="position:relative;flex:1;min-width:200px">
                    <i class="fas fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#bbb;font-size:13px"></i>
                    <input type="text" id="whSearch" class="ci-input" style="padding-left:32px" placeholder="ค้นหาคลังสารเคมี..." oninput="debounceSearch()">
                </div>
                <select id="whDivFilter" class="ci-select" style="width:auto;min-width:180px" onchange="loadWarehouses()">
                    <option value="">ทุกฝ่าย</option>
                </select>
                <select id="whStockFilter" class="ci-select" style="width:auto;min-width:140px" onchange="loadWarehouses()">
                    <option value="">ทั้งหมด</option>
                    <option value="1">มีสารเคมี</option>
                    <option value="0">ไม่มีสารเคมี</option>
                </select>
                <select id="whSortBy" class="ci-select" style="width:auto;min-width:140px" onchange="loadWarehouses()">
                    <option value="weight">น้ำหนัก (kg)</option>
                    <option value="bottles">จำนวนขวด</option>
                    <option value="chemicals">ชนิดสารเคมี</option>
                    <option value="name">ชื่อ A-Z</option>
                    <option value="division">ตามฝ่าย</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Warehouses Grid -->
    <div id="whListGrid" class="wh-list-grid">
        <div class="ci-loading" style="padding:40px"><div class="ci-spinner"></div></div>
    </div>
</div>

<!-- ═══ Map View (Building-based) ═══ -->
<div id="viewMap" style="display:none">
    <div id="buildingMapDetail">
        <div class="ci-loading" style="padding:40px"><div class="ci-spinner"></div></div>
    </div>
</div>

<!-- Detail Modal -->
<div class="ci-modal-bg" id="whDetailModal">
    <div class="ci-modal" style="max-width:960px;max-height:92vh;display:flex;flex-direction:column">
        <div class="ci-modal-hdr">
            <h3><i class="fas fa-warehouse" style="color:var(--accent);margin-right:6px"></i> <span id="whDetailTitle">รายละเอียดคลัง</span></h3>
            <button class="ci-modal-close" onclick="closeWhDetail()">&times;</button>
        </div>
        <div class="ci-modal-body" id="whDetailContent" style="overflow-y:auto;flex:1">
        </div>
    </div>
</div>

<?php Layout::endContent(); ?>

<style>
/* ═══ View Toggle ═══ */
.wh-view-toggle{display:inline-flex;background:#f1f5f9;border-radius:10px;padding:3px;gap:2px}
.wh-vt-btn{border:none;background:transparent;padding:7px 14px;border-radius:8px;font-size:12px;font-weight:600;color:#64748b;cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:5px}
.wh-vt-btn.active{background:#fff;color:var(--accent);box-shadow:0 1px 3px rgba(0,0,0,.1)}
.wh-vt-btn:hover:not(.active){color:#334155}
.wh-vt-btn span{display:inline}

/* ═══ Grand Stats ═══ */
.wh-grand-stats{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:20px}
.wh-grand-card{display:flex;align-items:center;gap:14px;padding:18px 16px;border-radius:14px;background:#fff;border:1px solid #f0f0f0;box-shadow:0 1px 4px rgba(0,0,0,.04);transition:all .2s}
.wh-grand-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.08);transform:translateY(-2px)}
.wh-gc-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}
.wh-gc-primary .wh-gc-icon{background:#ede9fe;color:#7c3aed}
.wh-gc-green .wh-gc-icon{background:#dcfce7;color:#16a34a}
.wh-gc-blue .wh-gc-icon{background:#dbeafe;color:#2563eb}
.wh-gc-amber .wh-gc-icon{background:#fef3c7;color:#d97706}
.wh-gc-red .wh-gc-icon{background:#fee2e2;color:#dc2626}
.wh-gc-val{font-size:24px;font-weight:800;color:#0f172a;line-height:1}
.wh-gc-lbl{font-size:11px;color:#94a3b8;font-weight:500;margin-top:3px}

/* ═══ Grid Layout ═══ */
.wh-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px}

/* ═══ Division Bar ═══ */
.wh-div-item{display:flex;align-items:center;gap:12px;padding:10px 16px;border-bottom:1px solid #f5f5f5;transition:background .15s;cursor:pointer}
.wh-div-item:last-child{border-bottom:none}
.wh-div-item:hover{background:#f8fafc}
.wh-div-bar-wrap{flex:1;min-width:0}
.wh-div-name{font-size:12px;font-weight:600;color:#334155;margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.wh-div-bar{height:8px;background:#f1f5f9;border-radius:4px;overflow:hidden}
.wh-div-bar-fill{height:100%;border-radius:4px;transition:width .6s ease}
.wh-div-stats{display:flex;gap:12px;flex-shrink:0}
.wh-div-stat{text-align:right}
.wh-div-stat-val{font-size:13px;font-weight:700;color:#0f172a}
.wh-div-stat-lbl{font-size:9px;color:#94a3b8;text-transform:uppercase}

/* ═══ Top List ═══ */
.wh-top-item{display:flex;align-items:center;gap:10px;padding:8px 16px;border-bottom:1px solid #f9f9f9;transition:background .12s}
.wh-top-item:hover{background:#f8fafc}
.wh-top-rank{width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;flex-shrink:0}
.wh-top-rank.gold{background:#fef3c7;color:#92400e}
.wh-top-rank.silver{background:#f1f5f9;color:#475569}
.wh-top-rank.bronze{background:#fed7aa;color:#9a3412}
.wh-top-rank.other{background:#f9fafb;color:#94a3b8}
.wh-top-info{flex:1;min-width:0}
.wh-top-name{font-size:12px;font-weight:600;color:#334155;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.wh-top-sub{font-size:10px;color:#94a3b8;margin-top:1px}
.wh-top-val{font-size:14px;font-weight:800;color:#0f172a;text-align:right}
.wh-top-unit{font-size:9px;color:#94a3b8}

/* ═══ Building Grid ═══ */
.wh-bld-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px}
.wh-bld-card{padding:14px;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;transition:all .2s;cursor:pointer}
.wh-bld-card:hover{border-color:var(--accent);background:#f0f7ff;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.wh-bld-name{font-size:13px;font-weight:700;color:#334155;margin-bottom:6px;display:flex;align-items:center;gap:6px}
.wh-bld-name i{color:var(--accent);font-size:12px}
.wh-bld-stats{display:grid;grid-template-columns:1fr 1fr;gap:6px}
.wh-bld-stat{font-size:11px;color:#64748b}
.wh-bld-stat b{color:#0f172a;font-weight:700}

/* ═══ Warehouse List Grid ═══ */
.wh-list-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:12px}
.wh-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px;transition:all .2s;cursor:pointer;position:relative;overflow:hidden}
.wh-card:hover{border-color:var(--accent);box-shadow:0 4px 16px rgba(0,0,0,.08)}
.wh-card-top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px}
.wh-card-name{font-size:14px;font-weight:700;color:#0f172a;flex:1;margin-right:8px;line-height:1.3}
.wh-card-badge{font-size:9px;font-weight:700;padding:3px 8px;border-radius:6px;white-space:nowrap;flex-shrink:0}
.wh-card-badge.has-stock{background:#dcfce7;color:#16a34a}
.wh-card-badge.no-stock{background:#f3f4f6;color:#9ca3af}
.wh-card-hierarchy{font-size:11px;color:#94a3b8;margin-bottom:10px;line-height:1.4;display:flex;align-items:center;gap:4px;flex-wrap:wrap}
.wh-card-hierarchy i{font-size:8px;color:#cbd5e1}
.wh-card-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:6px;padding-top:10px;border-top:1px solid #f1f5f9}
.wh-card-stat{text-align:center}
.wh-card-stat-val{font-size:16px;font-weight:800;color:#0f172a}
.wh-card-stat-lbl{font-size:9px;color:#94a3b8;text-transform:uppercase;font-weight:600}
.wh-card-stat-val.zero{color:#d4d4d8}
.wh-card-bar{position:absolute;bottom:0;left:0;right:0;height:3px;background:#f1f5f9}
.wh-card-bar-fill{height:100%;border-radius:0 0 0 12px;transition:width .5s}

/* ═══ Detail Modal ═══ */
.wh-detail-hero{text-align:center;padding:20px 16px;background:linear-gradient(135deg,#f0f4ff,#e8f5e9);border-radius:12px;margin-bottom:16px}
.wh-detail-hero h2{font-size:16px;font-weight:700;color:#0f172a;margin-bottom:4px}
.wh-detail-hero .wh-hero-path{font-size:11px;color:#64748b;margin-bottom:12px}
.wh-detail-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:12px}
.wh-detail-stat{padding:10px;background:rgba(255,255,255,.8);border-radius:8px;text-align:center}
.wh-detail-stat .val{font-size:20px;font-weight:800;color:#0f172a}
.wh-detail-stat .lbl{font-size:10px;color:#64748b;margin-top:2px}
.wh-detail-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f5f5f5;font-size:13px}
.wh-detail-row:last-child{border-bottom:none}
.wh-detail-label{color:#64748b;font-weight:500;display:flex;align-items:center;gap:6px}
.wh-detail-label i{width:14px;text-align:center;font-size:11px;color:#94a3b8}
.wh-detail-val{font-weight:600;color:#0f172a;text-align:right}

/* ═══ Building Map Detail ═══ */
.wh-bmap-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:20px;margin-bottom:16px}
.wh-bmap-hdr{display:flex;align-items:center;gap:12px;margin-bottom:14px;cursor:pointer}
.wh-bmap-icon{width:44px;height:44px;border-radius:10px;background:#ede9fe;color:#7c3aed;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.wh-bmap-title{font-size:16px;font-weight:700;color:#0f172a}
.wh-bmap-sub{font-size:11px;color:#94a3b8}
.wh-bmap-summ{display:flex;gap:16px;margin-left:auto}
.wh-bmap-summ-item{text-align:center}
.wh-bmap-summ-item .v{font-size:18px;font-weight:800;color:#0f172a}
.wh-bmap-summ-item .l{font-size:9px;color:#94a3b8}
.wh-bmap-wh-list{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:8px}
.wh-bmap-wh{padding:10px 14px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0;font-size:12px;cursor:pointer;transition:all .15s;display:flex;justify-content:space-between;align-items:center}
.wh-bmap-wh:hover{background:#f0f4ff;border-color:var(--accent)}
.wh-bmap-wh-name{font-weight:600;color:#334155;flex:1;margin-right:8px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.wh-bmap-wh-kg{font-weight:800;color:#0f172a;font-size:13px;white-space:nowrap}

/* ═══ Responsive ═══ */
@media(max-width:1024px){
    .wh-grand-stats{grid-template-columns:repeat(3,1fr)}
    .wh-grid-2{grid-template-columns:1fr}
}
@media(max-width:768px){
    .wh-grand-stats{grid-template-columns:repeat(2,1fr)}
    .wh-list-grid{grid-template-columns:1fr}
    .wh-vt-btn span{display:none}
    .wh-bmap-summ{flex-direction:column;gap:4px;margin-left:0}
    .wh-bld-grid{grid-template-columns:repeat(auto-fill,minmax(160px,1fr))}
}
@media(max-width:480px){
    .wh-grand-stats{grid-template-columns:1fr 1fr}
    .wh-grand-stats .wh-grand-card:last-child{grid-column:1/-1}
    .wh-gc-val{font-size:18px}
    .wh-gc-icon{width:40px;height:40px;font-size:16px}
}

/* ═══════════════════════════════════════════════ */
/* CHEMICAL DETAIL VIEW (Pro Design)              */
/* ═══════════════════════════════════════════════ */
.whd-hero{background:linear-gradient(135deg,#0f172a 0%,#1e3a5f 50%,#1a8a5c 100%);border-radius:14px;padding:20px 24px;color:#fff;margin-bottom:16px;position:relative;overflow:hidden}
.whd-hero::after{content:'';position:absolute;top:-50%;right:-20%;width:50%;height:200%;background:radial-gradient(circle,rgba(255,255,255,.06) 0%,transparent 70%);pointer-events:none}
.whd-hero h2{font-size:17px;font-weight:800;margin:0 0 4px;letter-spacing:-.3px}
.whd-hero-path{font-size:11px;opacity:.7;margin-bottom:14px}
.whd-hero-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
.whd-hero-stat{text-align:center;background:rgba(255,255,255,.1);border-radius:10px;padding:10px 6px;backdrop-filter:blur(4px)}
.whd-hero-stat .v{font-size:20px;font-weight:800;line-height:1.1}
.whd-hero-stat .l{font-size:10px;opacity:.7;margin-top:3px;text-transform:uppercase;letter-spacing:.5px}

.whd-tabs{display:flex;gap:4px;margin-bottom:16px;background:#f1f5f9;border-radius:10px;padding:3px;overflow-x:auto}
.whd-tab{flex:1;padding:8px 12px;border-radius:8px;border:none;background:none;cursor:pointer;font-size:12px;font-weight:600;color:#64748b;transition:.2s;white-space:nowrap;display:flex;align-items:center;gap:5px;justify-content:center}
.whd-tab.active{background:#fff;color:#0f172a;box-shadow:0 1px 3px rgba(0,0,0,.1)}
.whd-tab i{font-size:11px}
.whd-tab .cnt{background:#e2e8f0;color:#64748b;border-radius:10px;padding:1px 7px;font-size:10px;font-weight:700}
.whd-tab.active .cnt{background:var(--accent);color:#fff}

.whd-filter-bar{display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap;align-items:center}
.whd-filter-bar input,.whd-filter-bar select{font-size:12px;padding:6px 10px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;outline:none;transition:.2s}
.whd-filter-bar input:focus,.whd-filter-bar select:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(26,138,92,.12)}
.whd-filter-bar input{flex:1;min-width:180px;padding-left:30px}
.whd-search-wrap{position:relative;flex:1;min-width:180px}
.whd-search-wrap i{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:12px}

.whd-tbl{width:100%;border-collapse:separate;border-spacing:0;font-size:12px}
.whd-tbl thead{position:sticky;top:0;z-index:2}
.whd-tbl th{background:#f8fafc;padding:8px 10px;text-align:left;font-weight:700;color:#64748b;font-size:10px;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid #e2e8f0}
.whd-tbl td{padding:8px 10px;border-bottom:1px solid #f1f5f9;vertical-align:middle;color:#334155}
.whd-tbl tr:hover td{background:#f8fffe}
.whd-tbl tr:last-child td{border-bottom:none}
.whd-chem-name{font-weight:600;color:#0f172a;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.whd-barcode{font-family:'JetBrains Mono','Courier New',monospace;font-size:11px;color:#6366f1;font-weight:600;background:#eef2ff;padding:2px 6px;border-radius:4px;display:inline-block}
.whd-cas{font-family:monospace;font-size:11px;color:#64748b}
.whd-qty{font-weight:700;color:#0f172a}
.whd-owner{display:flex;align-items:center;gap:5px;font-size:11px}
.whd-owner-avatar{width:22px;height:22px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#3b82f6);color:#fff;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:800;flex-shrink:0}
.whd-status{display:inline-flex;align-items:center;gap:3px;padding:2px 8px;border-radius:10px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.3px}
.whd-status.active{background:#dcfce7;color:#15803d}
.whd-status.low{background:#fef3c7;color:#b45309}
.whd-status.empty{background:#fee2e2;color:#dc2626}
.whd-status.expired{background:#fce7f3;color:#be185d}
.whd-status.disposed{background:#f1f5f9;color:#64748b}
.whd-status-dot{width:6px;height:6px;border-radius:50%}
.whd-status.active .whd-status-dot{background:#22c55e}
.whd-status.low .whd-status-dot{background:#f59e0b}
.whd-status.empty .whd-status-dot{background:#ef4444}
.whd-status.expired .whd-status-dot{background:#ec4899}
.whd-status.disposed .whd-status-dot{background:#94a3b8}

.whd-pct-bar{width:48px;height:5px;background:#e2e8f0;border-radius:3px;overflow:hidden;display:inline-block;vertical-align:middle;margin-left:4px}
.whd-pct-fill{height:100%;border-radius:3px;transition:width .3s}

.whd-pagination{display:flex;justify-content:space-between;align-items:center;padding:10px 0;font-size:12px;color:#64748b}
.whd-pagination button{padding:5px 12px;border-radius:6px;border:1px solid #e2e8f0;background:#fff;cursor:pointer;font-size:11px;font-weight:600;color:#334155;transition:.2s}
.whd-pagination button:hover:not(:disabled){border-color:var(--accent);color:var(--accent)}
.whd-pagination button:disabled{opacity:.4;cursor:default}

.whd-info-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:14px}
.whd-info-item{display:flex;align-items:center;gap:8px;padding:8px 12px;background:#f8fafc;border-radius:8px;font-size:12px}
.whd-info-icon{width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0}
.whd-info-item .lbl{color:#64748b;font-size:10px;font-weight:600;text-transform:uppercase}
.whd-info-item .val{color:#0f172a;font-weight:700;font-size:13px}

.whd-mini-chart{display:grid;grid-template-columns:repeat(auto-fill,minmax(100px,1fr));gap:6px;margin-bottom:14px}
.whd-mini-card{text-align:center;padding:8px;border-radius:8px;background:#f8fafc;border:1px solid #f1f5f9}
.whd-mini-card .v{font-size:16px;font-weight:800;color:#0f172a}
.whd-mini-card .l{font-size:9px;color:#94a3b8;text-transform:uppercase;letter-spacing:.3px;margin-top:2px}

.whd-top-list{margin-bottom:14px}
.whd-top-item{display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid #f5f5f5;font-size:12px}
.whd-top-item:last-child{border-bottom:none}
.whd-top-rank{width:20px;height:20px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:800;flex-shrink:0}
.whd-top-rank.r1{background:#fef3c7;color:#b45309}
.whd-top-rank.r2{background:#e2e8f0;color:#475569}
.whd-top-rank.r3{background:#fed7aa;color:#c2410c}
.whd-top-rank.rn{background:#f1f5f9;color:#94a3b8}
.whd-top-name{flex:1;font-weight:600;color:#0f172a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.whd-top-val{font-weight:700;color:var(--accent);white-space:nowrap}

.whd-owner-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:8px;margin-bottom:14px}
.whd-owner-card{display:flex;align-items:center;gap:10px;padding:10px 12px;background:#f8fafc;border-radius:10px;border:1px solid #f1f5f9;transition:.2s}
.whd-owner-card:hover{border-color:var(--accent);background:#f0fdf4}
.whd-owner-card .avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#3b82f6);color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;flex-shrink:0}
.whd-owner-card .info{flex:1;min-width:0}
.whd-owner-card .name{font-weight:700;font-size:12px;color:#0f172a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.whd-owner-card .sub{font-size:10px;color:#94a3b8}

.whd-empty{text-align:center;padding:40px 20px;color:#94a3b8}
.whd-empty i{font-size:32px;margin-bottom:8px;display:block;opacity:.5}
.whd-empty div{font-size:13px}

.whd-siblings{margin-top:12px;padding-top:12px;border-top:1px solid #f0f0f0}
.whd-siblings-hdr{font-size:11px;font-weight:700;color:#64748b;margin-bottom:6px;display:flex;align-items:center;gap:5px}
.whd-sib-item{display:flex;justify-content:space-between;padding:5px 0;font-size:12px;border-bottom:1px solid #f9f9f9;cursor:pointer;transition:.15s}
.whd-sib-item:hover{color:var(--accent)}

@media(max-width:768px){
    .whd-hero-stats{grid-template-columns:repeat(2,1fr)}
    .whd-info-grid{grid-template-columns:1fr}
    .whd-tabs{flex-wrap:nowrap;overflow-x:auto}
    .whd-tbl{display:block;overflow-x:auto}
    .whd-owner-grid{grid-template-columns:1fr}
    .ci-modal{max-width:100%!important;margin:8px!important;max-height:95vh!important}
}
@media(max-width:480px){
    .whd-hero{padding:14px 16px}
    .whd-hero h2{font-size:14px}
    .whd-hero-stat .v{font-size:16px}
}
</style>

<script>
let overviewData = null;
let allWarehouses = [];
let divisionsList = [];
let searchTimer = null;

// ═══════════════════════════════════════════════
// VIEW SWITCHING
// ═══════════════════════════════════════════════
function switchView(v) {
    document.querySelectorAll('.wh-vt-btn').forEach(b => b.classList.toggle('active', b.dataset.view === v));
    document.getElementById('viewOverview').style.display = v === 'overview' ? '' : 'none';
    document.getElementById('viewList').style.display = v === 'list' ? '' : 'none';
    document.getElementById('viewMap').style.display = v === 'map' ? '' : 'none';
    
    if (v === 'list' && allWarehouses.length === 0) loadWarehouses();
    if (v === 'map' && !document.getElementById('buildingMapDetail').dataset.loaded) loadBuildingMap();
}

// ═══════════════════════════════════════════════
// OVERVIEW (CEO DASHBOARD)
// ═══════════════════════════════════════════════
async function loadOverview() {
    try {
        const res = await apiFetch('/v1/api/warehouses.php?action=overview');
        if (!res.success) throw new Error(res.error);
        overviewData = res.data;
        
        const t = overviewData.totals;
        anim('gTotal', t.total_warehouses);
        anim('gActive', t.active_warehouses);
        anim('gBottles', t.total_bottles);
        anim('gChemicals', t.total_chemicals);
        document.getElementById('gWeight').textContent = parseFloat(t.total_weight_kg || 0).toLocaleString('th-TH', {minimumFractionDigits:2});
        
        renderDivisionChart(overviewData.by_division);
        renderTopList(overviewData.top_by_weight);
        renderBuildingOverview(overviewData.by_building);
        
    } catch (e) {
        console.error('Overview error:', e);
    }
}

function anim(id, target) {
    const el = document.getElementById(id);
    target = parseInt(target) || 0;
    if (target === 0) { el.textContent = '0'; return; }
    let cur = 0;
    const step = Math.max(1, Math.ceil(target / 40));
    const interval = setInterval(() => {
        cur += step;
        if (cur >= target) { cur = target; clearInterval(interval); }
        el.textContent = cur.toLocaleString('th-TH');
    }, 25);
}

const divColors = ['#6C5CE7','#0984e3','#00b894','#e17055','#fdcb6e','#a29bfe','#55efc4','#fab1a0','#74b9ff'];

function renderDivisionChart(divs) {
    const el = document.getElementById('divisionChart');
    const maxW = Math.max(...divs.map(d => parseFloat(d.weight_kg) || 0));
    
    el.innerHTML = divs.map((d, i) => {
        const pct = maxW > 0 ? (parseFloat(d.weight_kg) / maxW * 100) : 0;
        const color = divColors[i % divColors.length];
        const name = (d.division_name || '').replace(/^ฝ่าย/, '');
        return `<div class="wh-div-item" onclick="filterByDivision(${d.division_id})">
            <div class="wh-div-bar-wrap">
                <div class="wh-div-name" title="${d.division_name}">${name}</div>
                <div class="wh-div-bar"><div class="wh-div-bar-fill" style="width:${pct}%;background:${color}"></div></div>
            </div>
            <div class="wh-div-stats">
                <div class="wh-div-stat"><div class="wh-div-stat-val">${parseInt(d.warehouse_count)}</div><div class="wh-div-stat-lbl">คลัง</div></div>
                <div class="wh-div-stat"><div class="wh-div-stat-val">${parseInt(d.bottles).toLocaleString()}</div><div class="wh-div-stat-lbl">ขวด</div></div>
                <div class="wh-div-stat"><div class="wh-div-stat-val">${parseFloat(d.weight_kg).toLocaleString('th-TH',{maximumFractionDigits:0})}</div><div class="wh-div-stat-lbl">kg</div></div>
            </div>
        </div>`;
    }).join('');
}

function renderTopList(items) {
    const el = document.getElementById('topWeightList');
    el.innerHTML = items.map((w, i) => {
        const rankCls = i === 0 ? 'gold' : i === 1 ? 'silver' : i === 2 ? 'bronze' : 'other';
        return `<div class="wh-top-item" onclick="showWhDetail(${w.id})">
            <div class="wh-top-rank ${rankCls}">${i + 1}</div>
            <div class="wh-top-info">
                <div class="wh-top-name" title="${esc(w.name)}">${esc(w.name)}</div>
                <div class="wh-top-sub">${esc(w.unit_name || w.division_name || '')} ${w.building ? '· ' + w.building : ''}</div>
            </div>
            <div>
                <div class="wh-top-val">${parseFloat(w.total_weight_kg).toLocaleString('th-TH',{minimumFractionDigits:2})}</div>
                <div class="wh-top-unit">kg · ${parseInt(w.total_bottles)} ขวด</div>
            </div>
        </div>`;
    }).join('');
}

function renderBuildingOverview(buildings) {
    const el = document.getElementById('buildingGrid');
    if (!buildings.length) {
        el.innerHTML = '<p style="text-align:center;color:#999;padding:20px">ไม่มีข้อมูลอาคาร</p>';
        return;
    }
    el.innerHTML = '<div class="wh-bld-grid">' + buildings.map(b => {
        return `<div class="wh-bld-card" onclick="filterByBuilding(${b.building_id})">
            <div class="wh-bld-name"><i class="fas fa-building"></i> ${esc(b.shortname || b.building_name)}</div>
            <div class="wh-bld-stats">
                <div class="wh-bld-stat"><b>${parseInt(b.warehouse_count)}</b> คลัง</div>
                <div class="wh-bld-stat"><b>${parseInt(b.bottles).toLocaleString()}</b> ขวด</div>
                <div class="wh-bld-stat"><b>${parseInt(b.chemicals)}</b> ชนิด</div>
                <div class="wh-bld-stat"><b>${parseFloat(b.weight_kg).toLocaleString('th-TH',{maximumFractionDigits:0})}</b> kg</div>
            </div>
        </div>`;
    }).join('') + '</div>';
}

// ═══════════════════════════════════════════════
// LIST VIEW (Admin)
// ═══════════════════════════════════════════════
async function loadWarehouses() {
    const div = document.getElementById('whDivFilter').value;
    const stock = document.getElementById('whStockFilter').value;
    const sort = document.getElementById('whSortBy').value;
    const search = document.getElementById('whSearch').value.trim();
    
    const params = new URLSearchParams({ action: 'list', sort });
    if (div) params.set('division', div);
    if (stock) params.set('has_stock', stock);
    if (search) params.set('search', search);
    
    try {
        const res = await apiFetch('/v1/api/warehouses.php?' + params);
        if (!res.success) throw new Error(res.error);
        allWarehouses = res.data;
        renderWarehouseGrid(allWarehouses);
    } catch (e) {
        document.getElementById('whListGrid').innerHTML = '<div class="ci-empty"><i class="fas fa-exclamation-triangle"></i><div>'+e.message+'</div></div>';
    }
}

function renderWarehouseGrid(items) {
    const el = document.getElementById('whListGrid');
    if (!items.length) {
        el.innerHTML = '<div class="ci-empty" style="grid-column:1/-1;padding:40px"><i class="fas fa-warehouse" style="font-size:28px;color:#ddd;margin-bottom:8px"></i><div style="color:#999">ไม่พบข้อมูลคลังสารเคมี</div></div>';
        return;
    }
    
    const maxWeight = Math.max(...items.map(w => parseFloat(w.total_weight_kg) || 0));
    
    el.innerHTML = items.map(w => {
        const hasStock = parseInt(w.total_bottles) > 0;
        const pct = maxWeight > 0 ? (parseFloat(w.total_weight_kg) / maxWeight * 100) : 0;
        const barColor = pct > 70 ? '#ef4444' : pct > 40 ? '#f59e0b' : '#22c55e';
        const divName = (w.division_name || w.div_name || '').replace(/^ฝ่าย/, '');
        
        return `<div class="wh-card" onclick="showWhDetail(${w.id})">
            <div class="wh-card-top">
                <div class="wh-card-name">${esc(w.name)}</div>
                <span class="wh-card-badge ${hasStock ? 'has-stock' : 'no-stock'}">${hasStock ? 'มีสารเคมี' : 'ว่าง'}</span>
            </div>
            <div class="wh-card-hierarchy">
                <i class="fas fa-chevron-right"></i> ${esc(divName)}
                <i class="fas fa-chevron-right"></i> ${esc(w.unit_name || w.dept_name || '')}
                ${w.building_short ? '<i class="fas fa-building" style="margin-left:4px"></i> ' + esc(w.building_short) : ''}
            </div>
            <div class="wh-card-stats">
                <div class="wh-card-stat"><div class="wh-card-stat-val ${!hasStock?'zero':''}">${parseInt(w.total_bottles).toLocaleString()}</div><div class="wh-card-stat-lbl">ขวด</div></div>
                <div class="wh-card-stat"><div class="wh-card-stat-val ${!hasStock?'zero':''}">${parseInt(w.total_chemicals).toLocaleString()}</div><div class="wh-card-stat-lbl">ชนิด</div></div>
                <div class="wh-card-stat"><div class="wh-card-stat-val">${parseFloat(w.total_weight_kg).toLocaleString('th-TH',{minimumFractionDigits:2})}</div><div class="wh-card-stat-lbl">kg</div></div>
            </div>
            <div class="wh-card-bar"><div class="wh-card-bar-fill" style="width:${pct}%;background:${barColor}"></div></div>
        </div>`;
    }).join('');
}

function debounceSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(loadWarehouses, 300);
}

// ═══════════════════════════════════════════════
// MAP VIEW (Building-grouped)
// ═══════════════════════════════════════════════
async function loadBuildingMap() {
    const el = document.getElementById('buildingMapDetail');
    el.dataset.loaded = '1';
    
    try {
        const [ovRes, whRes] = await Promise.all([
            apiFetch('/v1/api/warehouses.php?action=overview'),
            apiFetch('/v1/api/warehouses.php?action=list&sort=weight')
        ]);
        
        const buildings = ovRes.data.by_building || [];
        const warehouses = whRes.data || [];
        
        // Group warehouses by building_id
        const byBld = {};
        warehouses.forEach(w => {
            const bid = w.building_id || 'none';
            if (!byBld[bid]) byBld[bid] = [];
            byBld[bid].push(w);
        });
        
        // Also add the "no building" group
        let html = '';
        
        buildings.forEach(b => {
            const whs = byBld[b.building_id] || [];
            html += `<div class="wh-bmap-card">
                <div class="wh-bmap-hdr" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display==='none'?'':'none'">
                    <div class="wh-bmap-icon"><i class="fas fa-building"></i></div>
                    <div>
                        <div class="wh-bmap-title">${esc(b.building_name)}</div>
                        <div class="wh-bmap-sub">${esc(b.shortname || '')} · ${b.warehouse_count} คลัง</div>
                    </div>
                    <div class="wh-bmap-summ">
                        <div class="wh-bmap-summ-item"><div class="v">${parseInt(b.bottles).toLocaleString()}</div><div class="l">ขวด</div></div>
                        <div class="wh-bmap-summ-item"><div class="v">${parseFloat(b.weight_kg).toLocaleString('th-TH',{maximumFractionDigits:0})}</div><div class="l">kg</div></div>
                    </div>
                </div>
                <div class="wh-bmap-wh-list">
                    ${whs.map(w => `<div class="wh-bmap-wh" onclick="showWhDetail(${w.id})">
                        <span class="wh-bmap-wh-name">${esc(w.name)}</span>
                        <span class="wh-bmap-wh-kg">${parseFloat(w.total_weight_kg).toLocaleString('th-TH',{minimumFractionDigits:2})} kg</span>
                    </div>`).join('')}
                </div>
            </div>`;
        });
        
        // No-building group
        const noBld = byBld['none'] || byBld[''] || [];
        if (noBld.length) {
            html += `<div class="wh-bmap-card">
                <div class="wh-bmap-hdr" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display==='none'?'':'none'">
                    <div class="wh-bmap-icon" style="background:#fee2e2;color:#dc2626"><i class="fas fa-map-marker-alt"></i></div>
                    <div>
                        <div class="wh-bmap-title">ไม่ระบุอาคาร</div>
                        <div class="wh-bmap-sub">${noBld.length} คลัง</div>
                    </div>
                </div>
                <div class="wh-bmap-wh-list">
                    ${noBld.map(w => `<div class="wh-bmap-wh" onclick="showWhDetail(${w.id})">
                        <span class="wh-bmap-wh-name">${esc(w.name)}</span>
                        <span class="wh-bmap-wh-kg">${parseFloat(w.total_weight_kg).toLocaleString('th-TH',{minimumFractionDigits:2})} kg</span>
                    </div>`).join('')}
                </div>
            </div>`;
        }
        
        el.innerHTML = html || '<div class="ci-empty" style="padding:40px"><i class="fas fa-building" style="font-size:28px;color:#ddd;margin-bottom:8px"></i><div>ไม่มีข้อมูล</div></div>';
        
    } catch (e) {
        el.innerHTML = '<div class="ci-empty"><div>' + e.message + '</div></div>';
    }
}

// ═══════════════════════════════════════════════
// DETAIL MODAL (Pro Version with Chemical View)
// ═══════════════════════════════════════════════
let whDetailState = { id: 0, page: 1, search: '', status: '', tab: 'chemicals', data: null };

async function showWhDetail(id) {
    whDetailState = { id, page: 1, search: '', status: '', tab: 'info', data: null, ownerId: 0, ownerName: '' };
    
    try {
        const res = await apiFetch('/v1/api/warehouses.php?action=detail&id=' + id);
        if (!res.success) throw new Error(res.error);
        const w = res.data;
        whDetailState.data = w;
        
        document.getElementById('whDetailTitle').textContent = w.name;
        
        // Build the full detail view
        let html = buildHeroSection(w);
        html += buildTabsSection();
        html += '<div id="whd-tab-content"></div>';
        
        // Siblings
        if (res.siblings && res.siblings.length) {
            html += `<div class="whd-siblings">
                <div class="whd-siblings-hdr"><i class="fas fa-th-list"></i> คลังในฝ่ายเดียวกัน (${res.siblings.length})</div>
                ${res.siblings.map(s => `<div class="whd-sib-item" onclick="showWhDetail(${s.id})">
                    <span style="color:#334155">${esc(s.name)}</span>
                    <span style="font-weight:700;color:#0f172a">${parseFloat(s.total_weight_kg).toLocaleString('th-TH',{minimumFractionDigits:2})} kg</span>
                </div>`).join('')}
            </div>`;
        }
        
        document.getElementById('whDetailContent').innerHTML = html;
        document.getElementById('whDetailModal').classList.add('show');
        
        // Load info tab by default
        loadInfoTab();
        
    } catch (e) {
        alert(e.message);
    }
}

function buildHeroSection(w) {
    return `<div class="whd-hero">
        <h2><i class="fas fa-warehouse" style="margin-right:6px;opacity:.8"></i> ${esc(w.name)}</h2>
        <div class="whd-hero-path">
            ${esc(w.ctr_name || w.center_name || '')}
            ${w.div_name || w.division_name ? ' › ' + esc(w.div_name || w.division_name) : ''}
            ${w.dept_name || w.unit_name ? ' › ' + esc(w.dept_name || w.unit_name) : ''}
            ${w.building_name_full ? ' · <i class="fas fa-building"></i> ' + esc(w.building_name_full) + (w.building_short ? ' ('+esc(w.building_short)+')' : '') : ''}
        </div>
        <div class="whd-hero-stats">
            <div class="whd-hero-stat"><div class="v">${parseInt(w.total_bottles).toLocaleString()}</div><div class="l">ขวดสารเคมี</div></div>
            <div class="whd-hero-stat"><div class="v">${parseInt(w.total_chemicals).toLocaleString()}</div><div class="l">ชนิดสารเคมี</div></div>
            <div class="whd-hero-stat"><div class="v">${parseFloat(w.total_weight_kg).toLocaleString('th-TH',{maximumFractionDigits:1})}</div><div class="l">kg</div></div>
            <div class="whd-hero-stat"><div class="v"><span class="ci-badge ${w.status==='active'?'ci-badge-success':'ci-badge-default'}" style="font-size:10px">${w.status==='active'?'ใช้งาน':w.status}</span></div><div class="l">สถานะ</div></div>
        </div>
    </div>`;
}

function buildTabsSection() {
    return `<div class="whd-tabs">
        <button class="whd-tab active" data-tab="info" onclick="switchDetailTab('info',this)"><i class="fas fa-info-circle"></i> ข้อมูลคลัง</button>
        <button class="whd-tab" data-tab="overview" onclick="switchDetailTab('overview',this)"><i class="fas fa-chart-pie"></i> ภาพรวม</button>
        <button class="whd-tab" data-tab="chemicals" onclick="switchDetailTab('chemicals',this)"><i class="fas fa-flask"></i> รายการสารเคมี</button>
        <button class="whd-tab" data-tab="owners" onclick="switchDetailTab('owners',this)"><i class="fas fa-users"></i> ผู้ครอบครอง</button>
    </div>`;
}

function switchDetailTab(tab, btn) {
    whDetailState.tab = tab;
    document.querySelectorAll('.whd-tab').forEach(t => t.classList.remove('active'));
    if (btn) btn.classList.add('active');
    
    switch(tab) {
        case 'chemicals': loadChemicalsTab(); break;
        case 'overview': loadOverviewTab(); break;
        case 'owners': loadOwnersTab(); break;
        case 'info': loadInfoTab(); break;
    }
}

// ── CHEMICALS TAB ──
async function loadChemicalsTab() {
    const el = document.getElementById('whd-tab-content');
    el.innerHTML = '<div style="text-align:center;padding:30px"><div class="ci-spinner"></div></div>';
    
    try {
        const params = new URLSearchParams({
            action: 'store_chemicals',
            id: whDetailState.id,
            page: whDetailState.page,
            search: whDetailState.search,
            status: whDetailState.status
        });
        if (whDetailState.ownerId) params.set('owner_id', whDetailState.ownerId);
        
        const res = await apiFetch('/v1/api/warehouses.php?' + params);
        if (!res.success) throw new Error(res.error);
        
        let html = '';
        
        // Owner filter badge
        if (whDetailState.ownerId && whDetailState.ownerName) {
            html += `<div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;padding:8px 12px;background:linear-gradient(135deg,#eff6ff,#f0fdf4);border:1px solid #bfdbfe;border-radius:10px">
                <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#3b82f6);color:#fff;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800">${getInitials(whDetailState.ownerName)}</div>
                <div style="flex:1;min-width:0">
                    <div style="font-size:12px;font-weight:700;color:#0f172a">${esc(whDetailState.ownerName)}</div>
                    <div style="font-size:10px;color:#64748b">แสดงเฉพาะสารเคมีของผู้ครอบครองนี้</div>
                </div>
                <button onclick="whdClearOwner()" style="border:none;background:#fee2e2;color:#dc2626;width:24px;height:24px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:12px" title="ล้างตัวกรอง"><i class="fas fa-times"></i></button>
            </div>`;
        }
        
        // Filter bar
        html += `<div class="whd-filter-bar">
            <div class="whd-search-wrap">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="ค้นหาชื่อสาร, Barcode, CAS No..." value="${esc(whDetailState.search)}" oninput="whdSearchDebounce(this.value)">
            </div>
            <select onchange="whdFilterStatus(this.value)" style="min-width:100px">
                <option value="">ทุกสถานะ</option>
                <option value="active" ${whDetailState.status==='active'?'selected':''}>Active</option>
                <option value="low" ${whDetailState.status==='low'?'selected':''}>Low</option>
                <option value="empty" ${whDetailState.status==='empty'?'selected':''}>Empty</option>
                <option value="expired" ${whDetailState.status==='expired'?'selected':''}>Expired</option>
                <option value="disposed" ${whDetailState.status==='disposed'?'selected':''}>Disposed</option>
            </select>
            <span style="font-size:11px;color:#94a3b8">${res.summary.total_items.toLocaleString()} รายการ</span>
        </div>`;
        
        // Status pills summary
        if (res.status_breakdown && res.status_breakdown.length > 1) {
            html += '<div style="display:flex;gap:6px;margin-bottom:10px;flex-wrap:wrap">';
            res.status_breakdown.forEach(s => {
                html += `<span class="whd-status ${s.status}" style="cursor:pointer" onclick="whdFilterStatus('${s.status}')">
                    <span class="whd-status-dot"></span> ${s.status} (${s.cnt})
                </span>`;
            });
            html += '</div>';
        }
        
        if (!res.data.length) {
            html += `<div class="whd-empty"><i class="fas fa-flask"></i><div>${whDetailState.search || whDetailState.status ? 'ไม่พบรายการที่ตรงกับเงื่อนไข' : 'ไม่พบสารเคมีในคลังนี้'}</div></div>`;
        } else {
            html += `<div style="overflow-x:auto;border-radius:10px;border:1px solid #e2e8f0">
            <table class="whd-tbl">
                <thead><tr>
                    <th style="width:36px">#</th>
                    <th>Barcode</th>
                    <th>ชื่อสารเคมี</th>
                    <th>CAS No.</th>
                    <th>คงเหลือ</th>
                    <th>%</th>
                    <th>สถานะ</th>
                    <th>ผู้ครอบครอง</th>
                </tr></thead>
                <tbody>`;
            
            const startIdx = (res.pagination.page - 1) * res.pagination.limit;
            res.data.forEach((c, i) => {
                const pct = parseFloat(c.remaining_pct || 0);
                const pctColor = pct > 50 ? '#22c55e' : pct > 20 ? '#f59e0b' : '#ef4444';
                const initials = getInitials(c.owner_name || c.owner_first || '?');
                
                html += `<tr>
                    <td style="color:#94a3b8;font-size:11px">${startIdx + i + 1}</td>
                    <td><span class="whd-barcode">${esc(c.bottle_code)}</span></td>
                    <td><div class="whd-chem-name" title="${esc(c.chemical_name)}">${esc(c.chemical_name)}</div>${c.grade ? '<div style="font-size:10px;color:#94a3b8">'+esc(c.grade)+'</div>' : ''}</td>
                    <td><span class="whd-cas">${esc(c.cas_no || '-')}</span></td>
                    <td class="whd-qty">${c.remaining_qty ? parseFloat(c.remaining_qty).toLocaleString('th-TH',{maximumFractionDigits:2}) : '0'} <span style="font-size:10px;color:#94a3b8;font-weight:400">${esc(c.unit || '')}</span></td>
                    <td><span style="font-size:11px;font-weight:600;color:${pctColor}">${pct.toFixed(0)}%</span><div class="whd-pct-bar"><div class="whd-pct-fill" style="width:${pct}%;background:${pctColor}"></div></div></td>
                    <td><span class="whd-status ${c.status}"><span class="whd-status-dot"></span> ${c.status}</span></td>
                    <td><div class="whd-owner"><div class="whd-owner-avatar">${initials}</div><span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:100px">${esc(c.owner_name || (c.owner_first||'')+' '+(c.owner_last||''))}</span></div></td>
                </tr>`;
            });
            
            html += '</tbody></table></div>';
            
            // Pagination
            if (res.pagination.pages > 1) {
                html += `<div class="whd-pagination">
                    <span>หน้า ${res.pagination.page} / ${res.pagination.pages} (${res.pagination.total.toLocaleString()} รายการ)</span>
                    <div style="display:flex;gap:4px">
                        <button ${res.pagination.page <= 1 ? 'disabled' : ''} onclick="whdChangePage(${res.pagination.page - 1})"><i class="fas fa-chevron-left"></i> ก่อนหน้า</button>
                        <button ${res.pagination.page >= res.pagination.pages ? 'disabled' : ''} onclick="whdChangePage(${res.pagination.page + 1})">ถัดไป <i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>`;
            }
        }
        
        el.innerHTML = html;
        
    } catch (e) {
        el.innerHTML = `<div class="whd-empty"><i class="fas fa-exclamation-triangle" style="color:#ef4444"></i><div>${e.message}</div></div>`;
    }
}

let whdSearchTimer = null;
function whdSearchDebounce(val) {
    whDetailState.search = val;
    whDetailState.page = 1;
    clearTimeout(whdSearchTimer);
    whdSearchTimer = setTimeout(loadChemicalsTab, 350);
}
function whdFilterStatus(val) {
    whDetailState.status = whDetailState.status === val ? '' : val;
    whDetailState.page = 1;
    loadChemicalsTab();
}
function whdChangePage(p) {
    whDetailState.page = p;
    loadChemicalsTab();
}
function whdFilterByOwner(userId, name) {
    whDetailState.ownerId = userId;
    whDetailState.ownerName = name;
    whDetailState.page = 1;
    whDetailState.search = '';
    whDetailState.status = '';
    switchDetailTab('chemicals', document.querySelector('.whd-tab[data-tab="chemicals"]'));
}
function whdClearOwner() {
    whDetailState.ownerId = 0;
    whDetailState.ownerName = '';
    whDetailState.page = 1;
    loadChemicalsTab();
}

// ── OVERVIEW TAB ──
async function loadOverviewTab() {
    const el = document.getElementById('whd-tab-content');
    el.innerHTML = '<div style="text-align:center;padding:30px"><div class="ci-spinner"></div></div>';
    
    try {
        const res = await apiFetch('/v1/api/warehouses.php?action=store_chemicals&id=' + whDetailState.id);
        if (!res.success) throw new Error(res.error);
        
        let html = '';
        
        // Status breakdown cards
        html += '<div style="font-size:12px;font-weight:700;color:#64748b;margin-bottom:6px"><i class="fas fa-chart-bar" style="margin-right:4px"></i> สถานะสารเคมี</div>';
        html += '<div class="whd-mini-chart">';
        const statusColors = {active:'#22c55e',low:'#f59e0b',empty:'#ef4444',expired:'#ec4899',disposed:'#94a3b8'};
        const statusLabels = {active:'ใช้ได้',low:'เหลือน้อย',empty:'หมด',expired:'หมดอายุ',disposed:'ทำลาย'};
        (res.status_breakdown || []).forEach(s => {
            html += `<div class="whd-mini-card" style="border-left:3px solid ${statusColors[s.status]||'#ccc'}">
                <div class="v" style="color:${statusColors[s.status]||'#333'}">${parseInt(s.cnt).toLocaleString()}</div>
                <div class="l">${statusLabels[s.status]||s.status}</div>
            </div>`;
        });
        html += '</div>';
        
        // Top chemicals
        if (res.top_chemicals && res.top_chemicals.length) {
            html += '<div style="font-size:12px;font-weight:700;color:#64748b;margin:14px 0 6px"><i class="fas fa-trophy" style="margin-right:4px;color:#f59e0b"></i> สารเคมียอดนิยม (Top 10)</div>';
            html += '<div class="whd-top-list">';
            res.top_chemicals.forEach((c, i) => {
                const rankClass = i === 0 ? 'r1' : i === 1 ? 'r2' : i === 2 ? 'r3' : 'rn';
                html += `<div class="whd-top-item">
                    <div class="whd-top-rank ${rankClass}">${i+1}</div>
                    <div class="whd-top-name" title="${esc(c.chemical_name)}">${esc(c.chemical_name)}</div>
                    <div class="whd-top-val">${parseInt(c.bottle_count)} ขวด</div>
                </div>`;
            });
            html += '</div>';
        }
        
        // Summary stats
        html += '<div style="font-size:12px;font-weight:700;color:#64748b;margin:14px 0 6px"><i class="fas fa-database" style="margin-right:4px"></i> สรุปข้อมูล</div>';
        html += `<div class="whd-info-grid">
            <div class="whd-info-item">
                <div class="whd-info-icon" style="background:#dbeafe;color:#2563eb"><i class="fas fa-wine-bottle"></i></div>
                <div><div class="lbl">จำนวนขวดทั้งหมด</div><div class="val">${res.summary.total_items.toLocaleString()}</div></div>
            </div>
            <div class="whd-info-item">
                <div class="whd-info-icon" style="background:#ede9fe;color:#7c3aed"><i class="fas fa-atom"></i></div>
                <div><div class="lbl">ชนิดสารเคมี</div><div class="val">${res.summary.unique_chemicals.toLocaleString()}</div></div>
            </div>
            <div class="whd-info-item">
                <div class="whd-info-icon" style="background:#dcfce7;color:#15803d"><i class="fas fa-weight-hanging"></i></div>
                <div><div class="lbl">ปริมาณรวม</div><div class="val">${res.summary.total_weight.toLocaleString('th-TH',{maximumFractionDigits:2})}</div></div>
            </div>
            <div class="whd-info-item">
                <div class="whd-info-icon" style="background:#fef3c7;color:#b45309"><i class="fas fa-user-friends"></i></div>
                <div><div class="lbl">ผู้ครอบครอง</div><div class="val">${res.summary.holder_count}</div></div>
            </div>
        </div>`;
        
        if (!res.summary.total_items) {
            html = `<div class="whd-empty"><i class="fas fa-chart-area"></i><div>ไม่มีข้อมูลสารเคมีในคลังนี้</div></div>`;
        }
        
        el.innerHTML = html;
    } catch (e) {
        el.innerHTML = `<div class="whd-empty"><i class="fas fa-exclamation-triangle" style="color:#ef4444"></i><div>${e.message}</div></div>`;
    }
}

// ── OWNERS TAB ──
async function loadOwnersTab() {
    const el = document.getElementById('whd-tab-content');
    el.innerHTML = '<div style="text-align:center;padding:30px"><div class="ci-spinner"></div></div>';
    
    try {
        const res = await apiFetch('/v1/api/warehouses.php?action=store_chemicals&id=' + whDetailState.id);
        if (!res.success) throw new Error(res.error);
        
        let html = '';
        
        if (!res.owners || !res.owners.length) {
            html = `<div class="whd-empty"><i class="fas fa-users"></i><div>ไม่พบผู้ครอบครองสารเคมีในคลังนี้</div></div>`;
        } else {
            html += '<div style="font-size:12px;font-weight:700;color:#64748b;margin-bottom:10px"><i class="fas fa-users" style="margin-right:4px"></i> ผู้ครอบครองสารเคมี (' + res.owners.length + ' คน)</div>';
            html += '<div class="whd-owner-grid">';
            res.owners.forEach(o => {
                const name = o.owner_name || ((o.first_name || '') + ' ' + (o.last_name || '')).trim() || '?';
                const initials = getInitials(name);
                html += `<div class="whd-owner-card" onclick="whdFilterByOwner(${o.owner_user_id},'${esc(name).replace(/'/g,"\\'")}')"
                    style="cursor:pointer" title="คลิกเพื่อดูสารเคมีของ ${esc(name)}">
                    <div class="avatar">${initials}</div>
                    <div class="info">
                        <div class="name">${esc(name)}</div>
                        <div class="sub">${parseInt(o.bottle_count).toLocaleString()} ขวด · ${parseFloat(o.total_qty || 0).toLocaleString('th-TH',{maximumFractionDigits:1})} หน่วย</div>
                    </div>
                    <i class="fas fa-chevron-right" style="color:#cbd5e1;font-size:11px"></i>
                </div>`;
            });
            html += '</div>';
        }
        
        el.innerHTML = html;
    } catch (e) {
        el.innerHTML = `<div class="whd-empty"><i class="fas fa-exclamation-triangle" style="color:#ef4444"></i><div>${e.message}</div></div>`;
    }
}

// ── INFO TAB ──
function loadInfoTab() {
    const w = whDetailState.data;
    if (!w) return;
    
    let html = '<div style="font-size:12px;font-weight:700;color:#64748b;margin-bottom:10px"><i class="fas fa-info-circle" style="margin-right:4px"></i> ข้อมูลคลังสารเคมี</div>';
    
    html += `<div class="whd-info-grid">
        <div class="whd-info-item"><div class="whd-info-icon" style="background:#dbeafe;color:#2563eb"><i class="fas fa-sitemap"></i></div><div><div class="lbl">ศูนย์</div><div class="val">${esc(w.ctr_name || w.center_name || '-')}</div></div></div>
        <div class="whd-info-item"><div class="whd-info-icon" style="background:#ede9fe;color:#7c3aed"><i class="fas fa-layer-group"></i></div><div><div class="lbl">ฝ่าย</div><div class="val">${esc(w.div_name || w.division_name || '-')}</div></div></div>
        <div class="whd-info-item"><div class="whd-info-icon" style="background:#dcfce7;color:#15803d"><i class="fas fa-users-cog"></i></div><div><div class="lbl">งาน/หน่วย</div><div class="val">${esc(w.dept_name || w.unit_name || '-')}</div></div></div>
        <div class="whd-info-item"><div class="whd-info-icon" style="background:#fef3c7;color:#b45309"><i class="fas fa-building"></i></div><div><div class="lbl">อาคาร</div><div class="val">${esc(w.building_name_full || '-')} ${w.building_short ? '('+esc(w.building_short)+')' : ''}</div></div></div>
        ${w.floor ? `<div class="whd-info-item"><div class="whd-info-icon" style="background:#e0e7ff;color:#4f46e5"><i class="fas fa-stairs"></i></div><div><div class="lbl">ชั้น</div><div class="val">${esc(w.floor)}</div></div></div>` : ''}
        ${w.zone ? `<div class="whd-info-item"><div class="whd-info-icon" style="background:#fce7f3;color:#be185d"><i class="fas fa-map-marker-alt"></i></div><div><div class="lbl">โซน</div><div class="val">${esc(w.zone)}</div></div></div>` : ''}
        <div class="whd-info-item"><div class="whd-info-icon" style="background:#ecfdf5;color:#059669"><i class="fas fa-signal"></i></div><div><div class="lbl">สถานะ</div><div class="val">${w.status === 'active' ? '✅ ใช้งาน' : w.status}</div></div></div>
        ${w.mgr_name ? `<div class="whd-info-item"><div class="whd-info-icon" style="background:#fff7ed;color:#c2410c"><i class="fas fa-user-tie"></i></div><div><div class="lbl">ผู้รับผิดชอบ</div><div class="val">${esc(w.mgr_name)}</div></div></div>` : ''}
    </div>`;
    
    if (w.description) {
        html += `<div style="margin-top:10px;padding:12px;background:#f8fafc;border-radius:8px;font-size:12px;color:#475569;line-height:1.5">${esc(w.description)}</div>`;
    }
    
    document.getElementById('whd-tab-content').innerHTML = html;
}

function getInitials(name) {
    if (!name) return '?';
    const parts = name.trim().split(/\s+/);
    if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
    return name.substring(0,2).toUpperCase();
}

function closeWhDetail() {
    document.getElementById('whDetailModal').classList.remove('show');
}
document.getElementById('whDetailModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeWhDetail(); });

// ═══════════════════════════════════════════════
// NAVIGATION HELPERS
// ═══════════════════════════════════════════════
function filterByDivision(divId) {
    switchView('list');
    document.getElementById('whDivFilter').value = divId;
    loadWarehouses();
}

function filterByBuilding(bldId) {
    switchView('map');
}

// ═══════════════════════════════════════════════
// LOAD DIVISIONS FOR FILTER
// ═══════════════════════════════════════════════
async function loadDivisions() {
    try {
        const res = await apiFetch('/v1/api/warehouses.php?action=divisions');
        if (!res.success) return;
        divisionsList = res.data;
        
        const sel = document.getElementById('whDivFilter');
        sel.innerHTML = '<option value="">ทุกฝ่าย</option>' +
            divisionsList.map(d => `<option value="${d.id}">${d.name.replace(/^ฝ่าย/, '')} (${d.warehouse_count})</option>`).join('');
    } catch (e) {
        console.error(e);
    }
}

function esc(s) {
    if (!s) return '';
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ═══════════════════════════════════════════════
// INIT
// ═══════════════════════════════════════════════
loadOverview();
loadDivisions();
</script>
</body></html>
