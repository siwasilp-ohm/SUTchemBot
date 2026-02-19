<?php
require_once __DIR__ . '/../includes/layout.php';
$user = Auth::getCurrentUser();
if (!$user) { header('Location: /v1/pages/login.php'); exit; }
$lang = I18n::getCurrentLang();
$userId = $user['id'];
$roleLevel = (int)($user['role_level'] ?? $user['level'] ?? 0);
$isAdmin = $roleLevel >= 5;
$isManager = $roleLevel >= 3;

Layout::head($lang==='th'?'ธุรกรรมทั้งหมด':'All Transactions');
?>
<body>
<?php Layout::sidebar('activity'); Layout::beginContent(); ?>
<?php Layout::pageHeader(
    $lang==='th'?'ธุรกรรมทั้งหมด':'All Transactions',
    'fas fa-chart-bar',
    $lang==='th'?'ดูความเคลื่อนไหวธุรกรรมสารเคมี แยกตามหมวดหมู่ ประเภท และรายเดือน':'View chemical transaction activity by category, type and monthly trends'
); ?>

<style>
/* ==================== ACTIVITY PAGE V2 ==================== */

/* ── Hero summary row ── */
.act-hero{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;margin-bottom:24px}
.act-hero-card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:18px;display:flex;align-items:center;gap:14px;transition:all .2s;position:relative;overflow:hidden}
.act-hero-card:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,.06)}
.act-hero-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px}
.act-hero-card.h-total::before{background:linear-gradient(90deg,#2563eb,#60a5fa)}
.act-hero-card.h-borrow::before{background:linear-gradient(90deg,#e65100,#ff8f00)}
.act-hero-card.h-use::before{background:linear-gradient(90deg,#6d28d9,#a78bfa)}
.act-hero-card.h-transfer::before{background:linear-gradient(90deg,#1565c0,#42a5f5)}
.act-hero-card.h-return::before{background:linear-gradient(90deg,#059669,#34d399)}
.act-hero-icon{width:44px;height:44px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.act-hero-icon.total{background:linear-gradient(135deg,#eff6ff,#dbeafe);color:#2563eb}
.act-hero-icon.borrow{background:#fff3e0;color:#e65100}
.act-hero-icon.use{background:#f3e8ff;color:#7c3aed}
.act-hero-icon.transfer{background:#e3f2fd;color:#1565c0}
.act-hero-icon.return{background:#ecfdf5;color:#059669}
.act-hero-val{font-size:24px;font-weight:800;color:var(--c1);line-height:1}
.act-hero-lbl{font-size:11px;color:var(--c3);font-weight:500;margin-top:2px}

/* ── View Tabs ── */
.act-tabs-bar{display:flex;align-items:center;gap:4px;margin-bottom:20px;background:var(--card);border:1px solid var(--border);border-radius:12px;padding:5px;overflow-x:auto}
.act-tab-btn{display:flex;align-items:center;gap:6px;padding:9px 16px;border:none;border-radius:8px;background:transparent;color:var(--c3);font-size:12px;font-weight:600;cursor:pointer;transition:all .2s;white-space:nowrap}
.act-tab-btn:hover{color:var(--c1);background:rgba(37,99,235,.04)}
.act-tab-btn.active{background:linear-gradient(135deg,#2563eb,#3b82f6);color:#fff;box-shadow:0 2px 8px rgba(37,99,235,.25)}
.act-tab-btn i{font-size:11px}

/* ── Content Panel ── */
.act-panel{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:20px;min-height:200px;animation:actFade .25s ease}
@keyframes actFade{from{opacity:0;transform:translateY(-4px)}to{opacity:1;transform:translateY(0)}}

/* ── Breadcrumb / Drilldown nav ── */
.act-breadcrumb{display:flex;align-items:center;gap:6px;margin-bottom:16px;font-size:12px;flex-wrap:wrap}
.act-bc-item{color:var(--accent);cursor:pointer;font-weight:600;display:flex;align-items:center;gap:4px;transition:color .15s}
.act-bc-item:hover{color:#1d4ed8}
.act-bc-sep{color:var(--c3);font-size:10px}
.act-bc-current{color:var(--c1);font-weight:700;cursor:default}

/* ========== TAB 1: BY TYPE ========== */
.act-type-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px}
.act-type-card{border:1px solid var(--border);border-radius:12px;padding:16px;display:flex;flex-direction:column;gap:10px;transition:all .15s;position:relative;overflow:hidden;background:var(--card);cursor:pointer}
.act-type-card:hover{transform:translateY(-2px);box-shadow:0 6px 16px rgba(0,0,0,.06);border-color:var(--accent)}
.act-type-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px}
.act-type-card.t-borrow::before{background:linear-gradient(90deg,#e65100,#ff8f00)}
.act-type-card.t-use::before{background:linear-gradient(90deg,#6d28d9,#a78bfa)}
.act-type-card.t-return::before{background:linear-gradient(90deg,#059669,#34d399)}
.act-type-card.t-transfer::before{background:linear-gradient(90deg,#1565c0,#42a5f5)}
.act-type-card.t-dispose::before{background:linear-gradient(90deg,#b71c1c,#e53935)}
.act-type-card-top{display:flex;align-items:center;gap:12px}
.act-type-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
.act-type-icon.borrow{background:#fff3e0;color:#e65100}
.act-type-icon.use{background:#f3e8ff;color:#7c3aed}
.act-type-icon.return{background:#ecfdf5;color:#059669}
.act-type-icon.transfer{background:#e3f2fd;color:#1565c0}
.act-type-icon.dispose{background:#fce4ec;color:#c62828}
.act-type-val{font-size:24px;font-weight:800;color:var(--c1);line-height:1}
.act-type-lbl{font-size:12px;color:var(--c3);font-weight:500}
.act-type-statuses{display:flex;gap:4px;flex-wrap:wrap}
.act-type-status{font-size:10px;padding:3px 8px;border-radius:10px;font-weight:600;cursor:pointer;transition:all .15s}
.act-type-status:hover{filter:brightness(.9);transform:scale(1.05)}
.act-type-status.completed{background:#dcfce7;color:#166534}
.act-type-status.pending{background:#fef9c3;color:#854d0e}
.act-type-status.rejected{background:#fee2e2;color:#991b1b}
.act-type-status.cancelled{background:#f1f5f9;color:#64748b}
.act-type-status.approved{background:#dbeafe;color:#1e40af}
.act-type-card-arrow{position:absolute;right:12px;top:50%;transform:translateY(-50%);color:var(--c3);font-size:12px;opacity:0;transition:all .2s}
.act-type-card:hover .act-type-card-arrow{opacity:1;right:10px}

/* Type detail list */
.act-detail-hdr{display:flex;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap}
.act-detail-hdr-icon{width:44px;height:44px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.act-detail-hdr h3{font-size:16px;font-weight:700;color:var(--c1);margin:0}
.act-detail-hdr .act-detail-count{font-size:12px;color:var(--c3);font-weight:500}
.act-detail-filters{display:flex;gap:6px;margin-left:auto;flex-wrap:wrap}
.act-detail-filter-btn{padding:5px 12px;border:1px solid var(--border);border-radius:8px;background:var(--card);font-size:11px;font-weight:600;color:var(--c2);cursor:pointer;transition:all .15s}
.act-detail-filter-btn:hover,.act-detail-filter-btn.active{background:#eff6ff;border-color:#93c5fd;color:#2563eb}

.act-txn-list{display:flex;flex-direction:column;gap:6px}
.act-txn-row{display:flex;align-items:center;gap:14px;padding:12px 14px;border:1px solid #f1f5f9;border-radius:10px;transition:all .15s;background:#fff}
.act-txn-row:hover{background:#f8fafc;border-color:#dbeafe;transform:translateX(2px)}
.act-txn-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.act-txn-dot.borrow{background:#e65100}.act-txn-dot.use{background:#7c3aed}
.act-txn-dot.return{background:#059669}.act-txn-dot.transfer{background:#1565c0}
.act-txn-dot.dispose{background:#c62828}
.act-txn-info{flex:1;min-width:0}
.act-txn-name{font-size:13px;font-weight:600;color:var(--c1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.act-txn-sub{font-size:11px;color:var(--c3);margin-top:2px;display:flex;gap:10px;flex-wrap:wrap}
.act-txn-sub span{display:flex;align-items:center;gap:3px}
.act-txn-sub i{font-size:9px}
.act-txn-badge{font-size:9px;padding:2px 8px;border-radius:8px;font-weight:700;white-space:nowrap}
.act-txn-badge.completed{background:#dcfce7;color:#166534}
.act-txn-badge.pending{background:#fef9c3;color:#854d0e}
.act-txn-badge.rejected{background:#fee2e2;color:#991b1b}
.act-txn-badge.cancelled{background:#f1f5f9;color:#64748b}
.act-txn-badge.approved{background:#dbeafe;color:#1e40af}
.act-txn-qty{font-size:13px;font-weight:700;color:var(--c1);white-space:nowrap}
.act-txn-date{font-size:11px;color:var(--c3);white-space:nowrap}

/* Pagination */
.act-pager{display:flex;align-items:center;justify-content:center;gap:6px;margin-top:16px}
.act-pager-btn{width:32px;height:32px;border-radius:8px;border:1px solid var(--border);background:var(--card);color:var(--c2);display:flex;align-items:center;justify-content:center;font-size:11px;cursor:pointer;transition:all .15s}
.act-pager-btn:hover{background:#eff6ff;border-color:#93c5fd;color:#2563eb}
.act-pager-btn.active{background:#2563eb;color:#fff;border-color:#2563eb}
.act-pager-btn:disabled{opacity:.3;cursor:default}
.act-pager-info{font-size:11px;color:var(--c3);font-weight:500;padding:0 8px}

/* ========== TAB 2: CHEMICAL LIFECYCLE ========== */
.act-chem-list{display:flex;flex-direction:column;gap:6px}
.act-chem-row{display:flex;align-items:center;gap:14px;padding:13px 16px;border:1px solid var(--border);border-radius:12px;transition:all .15s;background:var(--card);cursor:pointer}
.act-chem-row:hover{background:#f8fafc;border-color:var(--accent);transform:translateX(2px)}
.act-chem-rank{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;flex-shrink:0}
.act-chem-rank.r1{background:linear-gradient(135deg,#fbbf24,#f59e0b);color:#fff}
.act-chem-rank.r2{background:linear-gradient(135deg,#9ca3af,#6b7280);color:#fff}
.act-chem-rank.r3{background:linear-gradient(135deg,#d97706,#b45309);color:#fff}
.act-chem-rank.r-{background:#f1f5f9;color:var(--c3)}
.act-chem-info{flex:1;min-width:0}
.act-chem-name{font-size:13px;font-weight:600;color:var(--c1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.act-chem-cas{font-size:10px;color:var(--c3);font-family:'Courier New',monospace}
.act-chem-total{font-size:14px;font-weight:700;color:var(--c1);min-width:30px;text-align:right}
.act-chem-arrow{color:var(--c3);font-size:11px;opacity:0;transition:all .15s}
.act-chem-row:hover .act-chem-arrow{opacity:1}

/* Chemical Lifecycle Timeline */
.act-lifecycle{animation:actFade .25s ease}
.act-lifecycle-hdr{display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap}
.act-lifecycle-hdr h3{font-size:16px;font-weight:700;color:var(--c1);margin:0}
.act-lifecycle-hdr .act-lc-sub{font-size:12px;color:var(--c3)}
.act-lifecycle-hdr .act-lc-formula{font-size:12px;color:var(--accent);font-weight:600;font-family:'Courier New',monospace}

.act-lc-barcode-group{margin-bottom:16px}
.act-lc-barcode-hdr{display:flex;align-items:center;gap:8px;padding:10px 14px;background:#f8fafc;border-radius:10px 10px 0 0;border:1px solid var(--border);border-bottom:none;cursor:pointer;transition:background .15s}
.act-lc-barcode-hdr:hover{background:#f1f5f9}
.act-lc-barcode-hdr i.toggle{transition:transform .2s;font-size:10px;color:var(--c3)}
.act-lc-barcode-hdr i.toggle.open{transform:rotate(90deg)}
.act-lc-barcode-tag{font-size:12px;font-weight:700;color:var(--c1);font-family:'Courier New',monospace;display:flex;align-items:center;gap:6px}
.act-lc-barcode-tag i{color:var(--accent);font-size:11px}
.act-lc-barcode-cnt{font-size:10px;color:var(--c3);margin-left:auto;font-weight:500}

.act-lc-timeline{border:1px solid var(--border);border-top:none;border-radius:0 0 10px 10px;padding:12px 16px 16px;background:#fff}
.act-lc-timeline.collapsed{display:none}

.act-tl-item{display:flex;gap:14px;position:relative;padding-bottom:16px}
.act-tl-item:last-child{padding-bottom:0}
.act-tl-item:last-child .act-tl-line{display:none}

.act-tl-node{display:flex;flex-direction:column;align-items:center;flex-shrink:0;width:24px;position:relative}
.act-tl-dot{width:12px;height:12px;border-radius:50%;border:2.5px solid #fff;box-shadow:0 0 0 2px currentColor;z-index:1;flex-shrink:0}
.act-tl-dot.borrow{color:#e65100;background:#fff7ed}
.act-tl-dot.use{color:#7c3aed;background:#faf5ff}
.act-tl-dot.return{color:#059669;background:#ecfdf5}
.act-tl-dot.transfer{color:#1565c0;background:#eff6ff}
.act-tl-dot.dispose{color:#c62828;background:#fef2f2}
.act-tl-line{position:absolute;top:14px;left:50%;transform:translateX(-50%);width:2px;bottom:-2px;background:#e2e8f0}

.act-tl-body{flex:1;min-width:0}
.act-tl-title{font-size:12px;font-weight:600;color:var(--c1);display:flex;align-items:center;gap:6px;flex-wrap:wrap}
.act-tl-title .tl-type{padding:2px 8px;border-radius:5px;font-size:10px;font-weight:700}
.act-tl-title .tl-type.borrow{background:#fff7ed;color:#ea580c}
.act-tl-title .tl-type.use{background:#faf5ff;color:#7c3aed}
.act-tl-title .tl-type.return{background:#f0fdf4;color:#059669}
.act-tl-title .tl-type.transfer{background:#eff6ff;color:#2563eb}
.act-tl-title .tl-type.dispose{background:#fef2f2;color:#dc2626}
.act-tl-meta{font-size:11px;color:var(--c3);margin-top:3px;display:flex;gap:10px;flex-wrap:wrap}
.act-tl-meta span{display:flex;align-items:center;gap:3px}
.act-tl-meta i{font-size:9px}

/* ========== TAB 3: CHART VIEW ========== */
.act-chart-toolbar{display:flex;align-items:center;gap:8px;margin-bottom:16px;flex-wrap:wrap}
.act-chart-mode{display:flex;border:1px solid var(--border);border-radius:8px;overflow:hidden}
.act-chart-mode-btn{padding:7px 14px;border:none;background:var(--card);color:var(--c2);font-size:11px;font-weight:600;cursor:pointer;transition:all .15s;border-right:1px solid var(--border)}
.act-chart-mode-btn:last-child{border-right:none}
.act-chart-mode-btn:hover{background:#f8fafc}
.act-chart-mode-btn.active{background:#2563eb;color:#fff}
.act-chart-filter{margin-left:auto;display:flex;gap:6px;align-items:center;flex-wrap:wrap}
.act-chart-select{padding:6px 10px;border:1px solid var(--border);border-radius:8px;font-size:11px;background:var(--card);color:var(--c1);cursor:pointer}

/* SVG Chart */
.act-chart-wrap{position:relative;height:260px;border:1px solid #f1f5f9;border-radius:12px;background:linear-gradient(180deg,#fafbfc,#fff);overflow:visible;margin-bottom:12px}
.act-chart-svg{width:100%;height:100%;overflow:visible}
.act-chart-grid line{stroke:#f1f5f9;stroke-width:1}
.act-chart-grid text{fill:var(--c3);font-size:9px;font-weight:500}
.act-chart-bar{cursor:pointer;transition:all .15s}
.act-chart-bar:hover{filter:brightness(1.12);stroke:#fff;stroke-width:1}
.act-chart-bar-label{font-size:8px;fill:var(--c3);font-weight:600;text-anchor:middle}
.act-chart-tooltip{position:absolute;pointer-events:none;background:#1e293b;color:#fff;font-size:10px;font-weight:500;padding:8px 12px;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.2);opacity:0;transition:opacity .15s;z-index:20;min-width:120px;line-height:1.6}
.act-chart-tooltip.show{opacity:1}
.act-chart-tooltip::after{content:'';position:absolute;bottom:-5px;left:50%;transform:translateX(-50%);border:5px solid transparent;border-top-color:#1e293b;border-bottom:0}
.act-chart-legend{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:12px}
.act-chart-legend-item{display:flex;align-items:center;gap:5px;font-size:11px;color:var(--c2);font-weight:600;cursor:pointer;padding:3px 8px;border-radius:6px;border:1px solid transparent;transition:all .15s;user-select:none}
.act-chart-legend-item:hover{border-color:#e2e8f0;background:#f8fafc}
.act-chart-legend-item.inactive{opacity:.3;text-decoration:line-through}
.act-chart-legend-dot{width:10px;height:10px;border-radius:3px;flex-shrink:0}

/* Drill-down list */
.act-drill{margin-top:16px;animation:actFade .2s ease}
.act-drill-hdr{display:flex;align-items:center;gap:8px;margin-bottom:10px}
.act-drill-hdr h4{font-size:13px;font-weight:700;color:var(--c1);margin:0}
.act-drill-close{width:24px;height:24px;border-radius:6px;border:1px solid var(--border);background:var(--card);color:var(--c3);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:10px;margin-left:auto;transition:all .15s}
.act-drill-close:hover{background:#fef2f2;border-color:#fecaca;color:#dc2626}

/* ── Common ── */
.act-empty{text-align:center;padding:40px 20px;color:var(--c3)}
.act-empty i{font-size:30px;margin-bottom:8px;display:block;opacity:.3}
.act-empty p{font-size:12px;margin:4px 0 0}
.act-loading{text-align:center;padding:30px;color:var(--c3)}
.act-loading i{animation:spin .8s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}

/* ── Quick nav ── */
.act-quick-nav{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border:1px solid var(--border);border-radius:8px;background:var(--card);color:var(--c1);font-size:12px;font-weight:600;cursor:pointer;transition:all .15s;text-decoration:none;margin-bottom:20px}
.act-quick-nav:hover{background:#f8fafc;border-color:var(--accent);color:var(--accent);text-decoration:none}
.act-quick-nav i{font-size:11px}

/* ── Responsive ── */
@media(max-width:768px){
    .act-hero{grid-template-columns:repeat(2,1fr)}
    .act-tabs-bar{padding:4px}
    .act-tab-btn{padding:7px 10px;font-size:11px;flex:1;justify-content:center}
    .act-panel{padding:14px}
    .act-type-grid{grid-template-columns:1fr 1fr}
    .act-chart-wrap{height:200px}
    .act-chart-toolbar{flex-direction:column;align-items:stretch}
    .act-chart-filter{margin-left:0;justify-content:flex-start}
    .act-detail-filters{margin-left:0;width:100%}
    .act-txn-sub{display:none}
}
@media(max-width:480px){
    .act-hero{grid-template-columns:1fr}
    .act-type-grid{grid-template-columns:1fr}
    .act-chart-wrap{height:180px}
}
</style>

<!-- Quick nav back -->
<a href="/v1/pages/borrow.php" class="act-quick-nav">
    <i class="fas fa-arrow-left"></i> <?php echo $lang==='th'?'กลับไป ใช้ / ยืม / โอน / คืน':'Back to Transactions'; ?>
</a>

<!-- Hero Summary -->
<div class="act-hero" id="actHero">
    <div class="act-hero-card h-total">
        <div class="act-hero-icon total"><i class="fas fa-chart-bar"></i></div>
        <div><div class="act-hero-val" id="heroTotal">-</div><div class="act-hero-lbl"><?php echo $lang==='th'?'ธุรกรรมทั้งหมด':'Total Transactions'; ?></div></div>
    </div>
</div>

<!-- View Tabs -->
<div class="act-tabs-bar">
    <button class="act-tab-btn active" id="actTab-type" onclick="switchActView('type')">
        <i class="fas fa-layer-group"></i> <?php echo $lang==='th'?'ตามประเภท':'By Type'; ?>
    </button>
    <button class="act-tab-btn" id="actTab-chemical" onclick="switchActView('chemical')">
        <i class="fas fa-flask"></i> <?php echo $lang==='th'?'Lifecycle สารเคมี':'Chemical Lifecycle'; ?>
    </button>
    <button class="act-tab-btn" id="actTab-chart" onclick="switchActView('chart')">
        <i class="fas fa-chart-area"></i> <?php echo $lang==='th'?'มุมมองกราฟ':'Chart View'; ?>
    </button>
</div>

<!-- Content Panel -->
<div class="act-panel" id="actContent">
    <div class="act-loading"><i class="fas fa-circle-notch"></i></div>
</div>

<?php Layout::endContent(); ?>
<script>
const TH = '<?php echo $lang; ?>' === 'th';
const UID = <?php echo (int)$userId; ?>;
const IS_ADMIN = <?php echo $isAdmin?'true':'false'; ?>;
const IS_MANAGER = <?php echo $isManager?'true':'false'; ?>;

let actData = null;
let actView = 'type';

function num(v) { return Number(v||0).toLocaleString(); }
function esc(s) { if (!s) return ''; const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

const TYPE_META = {
    borrow:  {icon:'fa-hand-holding-medical', label:TH?'ยืม':'Borrow',     color:'#e65100', bg:'#fff3e0', cls:'borrow'},
    use:     {icon:'fa-eye-dropper',          label:TH?'เบิกใช้':'Use',      color:'#7c3aed', bg:'#f3e8ff', cls:'use'},
    return:  {icon:'fa-undo',                 label:TH?'คืน':'Return',       color:'#059669', bg:'#ecfdf5', cls:'return'},
    transfer:{icon:'fa-people-arrows',        label:TH?'โอน':'Transfer',     color:'#1565c0', bg:'#e3f2fd', cls:'transfer'},
    dispose: {icon:'fa-trash-alt',            label:TH?'จำหน่าย':'Dispose',  color:'#c62828', bg:'#fce4ec', cls:'dispose'}
};
const STATUS_LBL = {completed:TH?'สำเร็จ':'Done', pending:TH?'รอ':'Pending', rejected:TH?'ปฏิเสธ':'Rejected', cancelled:TH?'ยกเลิก':'Cancel', approved:TH?'อนุมัติ':'Approved'};
const CHART_COLORS = {borrow:'#fb923c', use:'#a78bfa', transfer:'#60a5fa', return:'#34d399', dispose:'#f87171'};

/* ══════════════════════════════════════════
   INIT
   ══════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', loadSummary);

async function loadSummary() {
    const c = document.getElementById('actContent');
    c.innerHTML = '<div class="act-loading"><i class="fas fa-circle-notch"></i></div>';
    try {
        const d = await apiFetch('/v1/api/borrow.php?action=activity_summary');
        if (!d.success) throw new Error(d.error);
        actData = d.data;
        renderHero();
        renderView();
    } catch(e) {
        c.innerHTML = `<div class="ci-alert ci-alert-danger">${e.message}</div>`;
    }
}

function renderHero() {
    const bt = actData.by_type || {};
    const types = ['borrow','use','transfer','return'];
    let total = 0;
    Object.values(bt).forEach(v => total += v.total||0);
    document.getElementById('heroTotal').textContent = num(total);
    const el = document.getElementById('actHero');
    let h = el.children[0].outerHTML;
    types.forEach(t => {
        const m = TYPE_META[t];
        h += `<div class="act-hero-card h-${t}">
            <div class="act-hero-icon ${t}"><i class="fas ${m.icon}"></i></div>
            <div><div class="act-hero-val">${num(bt[t]?.total||0)}</div><div class="act-hero-lbl">${m.label}</div></div>
        </div>`;
    });
    el.innerHTML = h;
}

function switchActView(v) {
    actView = v;
    document.querySelectorAll('.act-tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('actTab-'+v).classList.add('active');
    renderView();
}

function renderView() {
    if (!actData) return;
    const c = document.getElementById('actContent');
    switch(actView) {
        case 'type':     renderTypeCards(c); break;
        case 'chemical': renderChemList(c);  break;
        case 'chart':    loadChartView(c);   break;
    }
}

/* ══════════════════════════════════════════
   TAB 1: BY TYPE → drill into paginated list
   ══════════════════════════════════════════ */
function renderTypeCards(el) {
    const bt = actData.by_type || {};
    const types = ['borrow','use','return','transfer','dispose'];
    let html = '<div class="act-type-grid">';
    types.forEach(t => {
        const m = TYPE_META[t];
        const info = bt[t];
        const total = info?.total || 0;
        const statuses = info?.statuses || {};
        const statusHtml = Object.entries(statuses).map(([s,c]) =>
            `<span class="act-type-status ${s}" onclick="event.stopPropagation();openTypeDetail('${t}','${s}')">${STATUS_LBL[s]||s} ${c}</span>`
        ).join('');
        html += `<div class="act-type-card t-${t}" onclick="openTypeDetail('${t}')">
            <div class="act-type-card-top">
                <div class="act-type-icon ${m.cls}"><i class="fas ${m.icon}"></i></div>
                <div><div class="act-type-val">${num(total)}</div><div class="act-type-lbl">${m.label}</div></div>
            </div>
            ${statusHtml?`<div class="act-type-statuses">${statusHtml}</div>`:''}
            <i class="fas fa-chevron-right act-type-card-arrow"></i>
        </div>`;
    });
    html += '</div>';
    el.innerHTML = html;
}

let _tdPage=1, _tdType='', _tdStatus='';

async function openTypeDetail(type, status) {
    _tdType=type; _tdStatus=status||''; _tdPage=1;
    await loadTypeDetail();
}

async function loadTypeDetail(page) {
    if (page) _tdPage = page;
    const el = document.getElementById('actContent');
    const m = TYPE_META[_tdType]||TYPE_META.borrow;
    el.innerHTML = `
        <div class="act-breadcrumb">
            <span class="act-bc-item" onclick="renderTypeCards(document.getElementById('actContent'))"><i class="fas fa-layer-group"></i> ${TH?'ตามประเภท':'By Type'}</span>
            <span class="act-bc-sep"><i class="fas fa-chevron-right"></i></span>
            <span class="act-bc-current"><i class="fas ${m.icon}"></i> ${m.label}${_tdStatus?' — '+(STATUS_LBL[_tdStatus]||_tdStatus):''}</span>
        </div>
        <div class="act-loading"><i class="fas fa-circle-notch"></i></div>`;
    try {
        let url = `/v1/api/borrow.php?action=activity_type_detail&txn_type=${_tdType}&page=${_tdPage}`;
        if (_tdStatus) url += `&status=${_tdStatus}`;
        const d = await apiFetch(url);
        if (!d.success) throw new Error(d.error);
        renderTypeList(el, d.data, m);
    } catch(e) {
        el.innerHTML += `<div class="ci-alert ci-alert-danger">${e.message}</div>`;
    }
}

function renderTypeList(el, data, meta) {
    const items = data.items||[], total = data.total||0, pages = data.pages||1, page = data.page||1;
    let html = `
        <div class="act-breadcrumb">
            <span class="act-bc-item" onclick="renderTypeCards(document.getElementById('actContent'))"><i class="fas fa-layer-group"></i> ${TH?'ตามประเภท':'By Type'}</span>
            <span class="act-bc-sep"><i class="fas fa-chevron-right"></i></span>
            <span class="act-bc-current"><i class="fas ${meta.icon}"></i> ${meta.label}${_tdStatus?' — '+(STATUS_LBL[_tdStatus]||_tdStatus):''}</span>
        </div>
        <div class="act-detail-hdr">
            <div class="act-detail-hdr-icon" style="background:${meta.bg};color:${meta.color}"><i class="fas ${meta.icon}"></i></div>
            <div><h3>${meta.label}</h3><div class="act-detail-count">${num(total)} ${TH?'รายการ':'items'}</div></div>
            <div class="act-detail-filters">
                <button class="act-detail-filter-btn ${!_tdStatus?'active':''}" onclick="openTypeDetail('${_tdType}')">${TH?'ทั้งหมด':'All'}</button>
                <button class="act-detail-filter-btn ${_tdStatus==='completed'?'active':''}" onclick="openTypeDetail('${_tdType}','completed')">${TH?'สำเร็จ':'Done'}</button>
                <button class="act-detail-filter-btn ${_tdStatus==='pending'?'active':''}" onclick="openTypeDetail('${_tdType}','pending')">${TH?'รอ':'Pending'}</button>
            </div>
        </div>`;

    if (!items.length) {
        html += `<div class="act-empty"><i class="fas fa-inbox"></i><p>${TH?'ไม่มีรายการ':'No items'}</p></div>`;
    } else {
        html += '<div class="act-txn-list">';
        items.forEach(r => {
            const fromTo = r.from_name&&r.to_name?`${esc(r.from_name)} → ${esc(r.to_name)}`:esc(r.from_name||r.to_name||'');
            html += `<div class="act-txn-row">
                <div class="act-txn-dot ${r.txn_type}"></div>
                <div class="act-txn-info">
                    <div class="act-txn-name">${esc(r.chemical_name)}</div>
                    <div class="act-txn-sub">
                        <span><i class="fas fa-hashtag"></i>${r.txn_number||r.id}</span>
                        ${r.barcode?`<span><i class="fas fa-barcode"></i>${r.barcode}</span>`:''}
                        <span><i class="fas fa-user"></i>${fromTo}</span>
                        ${r.notes?`<span><i class="fas fa-sticky-note"></i>${esc(r.notes).substring(0,40)}</span>`:''}
                    </div>
                </div>
                <span class="act-txn-badge ${r.status}">${STATUS_LBL[r.status]||r.status}</span>
                <span class="act-txn-qty">${Number(r.quantity||0).toLocaleString()} ${r.unit||''}</span>
                <span class="act-txn-date">${formatDate(r.created_at)}</span>
            </div>`;
        });
        html += '</div>';
        if (pages > 1) {
            html += '<div class="act-pager">';
            html += `<button class="act-pager-btn" onclick="loadTypeDetail(${page-1})" ${page<=1?'disabled':''}><i class="fas fa-chevron-left"></i></button>`;
            for (let p=Math.max(1,page-2);p<=Math.min(pages,page+2);p++)
                html += `<button class="act-pager-btn ${p===page?'active':''}" onclick="loadTypeDetail(${p})">${p}</button>`;
            html += `<button class="act-pager-btn" onclick="loadTypeDetail(${page+1})" ${page>=pages?'disabled':''}><i class="fas fa-chevron-right"></i></button>`;
            html += `<span class="act-pager-info">${page}/${pages}</span></div>`;
        }
    }
    el.innerHTML = html;
}

/* ══════════════════════════════════════════
   TAB 2: CHEMICAL LIFECYCLE TIMELINE
   ══════════════════════════════════════════ */
function renderChemList(el) {
    const list = actData.by_chemical || [];
    if (!list.length) { el.innerHTML = `<div class="act-empty"><i class="fas fa-flask"></i><p>${TH?'ยังไม่มีธุรกรรมสารเคมี':'No chemical transactions yet'}</p></div>`; return; }
    let html = '<div class="act-chem-list">';
    list.forEach((r,i) => {
        const rank = i<3?`r${i+1}`:'r-';
        html += `<div class="act-chem-row" onclick="openChemLifecycle(${r.chemical_id},'${esc(r.chemical_name).replace(/'/g,"\\'")}')">
            <div class="act-chem-rank ${rank}">${i+1}</div>
            <div class="act-chem-info">
                <div class="act-chem-name">${esc(r.chemical_name)}</div>
                ${r.cas_number?`<div class="act-chem-cas">CAS: ${r.cas_number}</div>`:''}
            </div>
            <div class="act-chem-total">${num(r.txn_count)} ${TH?'ธุรกรรม':'txns'}</div>
            <i class="fas fa-chevron-right act-chem-arrow"></i>
        </div>`;
    });
    html += '</div>';
    el.innerHTML = html;
}

async function openChemLifecycle(chemId, chemName) {
    const el = document.getElementById('actContent');
    el.innerHTML = `
        <div class="act-breadcrumb">
            <span class="act-bc-item" onclick="renderChemList(document.getElementById('actContent'))"><i class="fas fa-flask"></i> ${TH?'Lifecycle สารเคมี':'Chemical Lifecycle'}</span>
            <span class="act-bc-sep"><i class="fas fa-chevron-right"></i></span>
            <span class="act-bc-current">${chemName}</span>
        </div>
        <div class="act-loading"><i class="fas fa-circle-notch"></i></div>`;
    try {
        const d = await apiFetch(`/v1/api/borrow.php?action=activity_chem_lifecycle&chemical_id=${chemId}`);
        if (!d.success) throw new Error(d.error);
        renderLifecycle(el, d.data, chemName);
    } catch(e) {
        el.innerHTML += `<div class="ci-alert ci-alert-danger">${e.message}</div>`;
    }
}

function renderLifecycle(el, data, chemName) {
    const chem = data.chemical||{};
    const byBarcode = data.by_barcode||{};
    const barcodes = Object.keys(byBarcode);

    let html = `
        <div class="act-breadcrumb">
            <span class="act-bc-item" onclick="renderChemList(document.getElementById('actContent'))"><i class="fas fa-flask"></i> ${TH?'Lifecycle สารเคมี':'Chemical Lifecycle'}</span>
            <span class="act-bc-sep"><i class="fas fa-chevron-right"></i></span>
            <span class="act-bc-current">${esc(chemName)}</span>
        </div>
        <div class="act-lifecycle">
            <div class="act-lifecycle-hdr">
                <h3>🧪 ${esc(chem.name||chemName)}</h3>
                ${chem.cas_number?`<span class="act-lc-sub">CAS: ${chem.cas_number}</span>`:''}
                ${chem.formula?`<span class="act-lc-formula">${esc(chem.formula)}</span>`:''}
                <span class="act-lc-sub">${num(data.total_txns)} ${TH?'ธุรกรรม':'transactions'} · ${barcodes.length} ${TH?'ขวด/ล็อต':'bottles/lots'}</span>
            </div>`;

    if (!barcodes.length) {
        html += `<div class="act-empty"><i class="fas fa-history"></i><p>${TH?'ยังไม่มีประวัติธุรกรรม':'No transaction history'}</p></div>`;
    } else {
        barcodes.forEach((bc, bi) => {
            const txns = byBarcode[bc];
            const isOpen = bi < 3;
            html += `<div class="act-lc-barcode-group">
                <div class="act-lc-barcode-hdr" onclick="this.querySelector('.toggle').classList.toggle('open');this.nextElementSibling.classList.toggle('collapsed')">
                    <i class="fas fa-chevron-right toggle ${isOpen?'open':''}"></i>
                    <span class="act-lc-barcode-tag"><i class="fas fa-barcode"></i> ${bc==='no-barcode'?(TH?'ไม่มี Barcode':'No Barcode'):bc}</span>
                    <span class="act-lc-barcode-cnt">${txns.length} ${TH?'รายการ':'events'}</span>
                </div>
                <div class="act-lc-timeline ${isOpen?'':'collapsed'}">`;

            txns.forEach(t => {
                const m = TYPE_META[t.txn_type]||TYPE_META.borrow;
                const fromTo = t.from_name&&t.to_name?`${esc(t.from_name)} → ${esc(t.to_name)}`:esc(t.from_name||t.to_name||'');
                html += `<div class="act-tl-item">
                    <div class="act-tl-node"><div class="act-tl-dot ${t.txn_type}"></div><div class="act-tl-line"></div></div>
                    <div class="act-tl-body">
                        <div class="act-tl-title">
                            <span class="tl-type ${t.txn_type}">${m.label}</span>
                            ${Number(t.quantity||0).toLocaleString()} ${t.unit||''}
                            <span class="act-txn-badge ${t.status}" style="font-size:9px">${STATUS_LBL[t.status]||t.status}</span>
                        </div>
                        <div class="act-tl-meta">
                            <span><i class="fas fa-clock"></i> ${formatDate(t.created_at)}</span>
                            ${fromTo?`<span><i class="fas fa-user"></i> ${fromTo}</span>`:''}
                            ${t.notes?`<span><i class="fas fa-sticky-note"></i> ${esc(t.notes).substring(0,50)}</span>`:''}
                        </div>
                    </div>
                </div>`;
            });
            html += '</div></div>';
        });
    }
    html += '</div>';
    el.innerHTML = html;
}

/* ══════════════════════════════════════════
   TAB 3: CHART VIEW — Day / Month / Year
   with drill-down + chemical filter
   ══════════════════════════════════════════ */
let _chartMode='month', _chartChemId=0, _chartYear='', _chartMonth='', _chartHidden={};

async function loadChartView(el) {
    el.innerHTML = '<div class="act-loading"><i class="fas fa-circle-notch"></i></div>';
    try {
        let url = `/v1/api/borrow.php?action=activity_chart&mode=${_chartMode}`;
        if (_chartChemId) url += `&chemical_id=${_chartChemId}`;
        if (_chartYear)   url += `&year=${_chartYear}`;
        if (_chartMonth)  url += `&month=${_chartMonth}`;
        const d = await apiFetch(url);
        if (!d.success) throw new Error(d.error);
        renderChart(el, d.data);
    } catch(e) {
        el.innerHTML = `<div class="ci-alert ci-alert-danger">${e.message}</div>`;
    }
}

function setChartMode(mode) {
    _chartMode=mode;
    if(mode==='year'){_chartYear='';_chartMonth='';}
    if(mode==='month'){_chartMonth='';}
    loadChartView(document.getElementById('actContent'));
}
function setChartChem(sel) { _chartChemId=parseInt(sel.value)||0; loadChartView(document.getElementById('actContent')); }
function setChartYear(sel) { _chartYear=sel.value; loadChartView(document.getElementById('actContent')); }
function setChartMonth(sel){ _chartMonth=sel.value; loadChartView(document.getElementById('actContent')); }
function toggleChartType(t){ _chartHidden[t]=!_chartHidden[t]; if(window._lastChart) renderChart(document.getElementById('actContent'),window._lastChart); }

function renderChart(el, data) {
    window._lastChart = data;
    const chart=data.chart||[], chemicals=data.chemicals||[], years=data.years||[];
    const types=['borrow','use','transfer','return','dispose'];

    // Aggregate
    const periods={};
    chart.forEach(r=>{
        if(!periods[r.period]) periods[r.period]={period:r.period,borrow:0,use:0,transfer:0,return:0,dispose:0,total:0};
        periods[r.period][r.txn_type]=(periods[r.period][r.txn_type]||0)+parseInt(r.cnt);
        periods[r.period].total+=parseInt(r.cnt);
    });
    const pArr=Object.values(periods).sort((a,b)=>a.period.localeCompare(b.period));

    // ── Toolbar ──
    let html='<div class="act-chart-toolbar">';
    html+='<div class="act-chart-mode">';
    [{k:'day',l:TH?'วัน':'Day'},{k:'month',l:TH?'เดือน':'Month'},{k:'year',l:TH?'ปี':'Year'}].forEach(o=>{
        html+=`<button class="act-chart-mode-btn ${_chartMode===o.k?'active':''}" onclick="setChartMode('${o.k}')">${o.l}</button>`;
    });
    html+='</div><div class="act-chart-filter">';

    // Chemical selector
    html+=`<select class="act-chart-select" onchange="setChartChem(this)"><option value="">${TH?'— ทุกสาร —':'— All —'}</option>`;
    chemicals.forEach(c=>html+=`<option value="${c.id}" ${c.id==_chartChemId?'selected':''}>${esc(c.name)}</option>`);
    html+='</select>';

    // Year selector
    if(_chartMode!=='year'&&years.length){
        html+=`<select class="act-chart-select" onchange="setChartYear(this)"><option value="">${TH?'— ทุกปี —':'— All —'}</option>`;
        years.forEach(y=>html+=`<option value="${y}" ${y==_chartYear?'selected':''}>${y}</option>`);
        html+='</select>';
    }
    // Month selector (day mode)
    if(_chartMode==='day'){
        const mN=TH?['ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.']:['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        const yr=_chartYear||new Date().getFullYear();
        html+=`<select class="act-chart-select" onchange="setChartMonth(this)"><option value="">${TH?'— ทุกเดือน —':'— All —'}</option>`;
        for(let i=1;i<=12;i++){const v=`${yr}-${String(i).padStart(2,'0')}`;html+=`<option value="${v}" ${v===_chartMonth?'selected':''}>${mN[i-1]}</option>`;}
        html+='</select>';
    }
    html+='</div></div>';

    // ── Legend ──
    html+='<div class="act-chart-legend">';
    types.forEach(t=>{const m=TYPE_META[t];html+=`<div class="act-chart-legend-item ${_chartHidden[t]?'inactive':''}" onclick="toggleChartType('${t}')"><div class="act-chart-legend-dot" style="background:${CHART_COLORS[t]}"></div>${m.label}</div>`;});
    html+='</div>';

    // ── SVG stacked bar chart ──
    if(!pArr.length){
        html+=`<div class="act-empty"><i class="fas fa-chart-bar"></i><p>${TH?'ไม่มีข้อมูลในช่วงที่เลือก':'No data for selected range'}</p></div>`;
    } else {
        const svgW=800,svgH=240;
        const pad={top:16,right:20,bottom:36,left:44};
        const cw=svgW-pad.left-pad.right, ch=svgH-pad.top-pad.bottom;
        const visTypes=types.filter(t=>!_chartHidden[t]);
        const maxVal=Math.max(...pArr.map(p=>visTypes.reduce((s,t)=>s+(p[t]||0),0)),1);
        const barW=Math.min(40,Math.max(10,cw/pArr.length-4));
        const gap=(cw-barW*pArr.length)/(pArr.length+1);

        const mNames=TH?['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.']:['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

        html+=`<div class="act-chart-wrap" id="actChartWrap"><div class="act-chart-tooltip" id="actChartTip"></div>`;
        html+=`<svg class="act-chart-svg" viewBox="0 0 ${svgW} ${svgH}" preserveAspectRatio="xMidYMid meet">`;

        // Grid
        html+='<g class="act-chart-grid">';
        for(let i=0;i<=4;i++){
            const gy=pad.top+(ch/4)*i,v=Math.round(maxVal*(1-i/4));
            html+=`<line x1="${pad.left}" y1="${gy}" x2="${svgW-pad.right}" y2="${gy}"/>`;
            html+=`<text x="${pad.left-4}" y="${gy+3}" text-anchor="end">${num(v)}</text>`;
        }
        html+='</g>';

        // Bars
        pArr.forEach((p,pi)=>{
            const bx=pad.left+gap+pi*(barW+gap);
            let stackY=pad.top+ch;

            // X label
            let lbl=p.period;
            if(_chartMode==='month'){const pts=p.period.split('-');lbl=mNames[parseInt(pts[1])]||pts[1];}
            else if(_chartMode==='day'){lbl=p.period.split('-').pop().replace(/^0/,'');}

            html+=`<text x="${bx+barW/2}" y="${svgH-pad.bottom+14}" class="act-chart-bar-label">${lbl}</text>`;

            visTypes.forEach(t=>{
                const v=p[t]||0; if(!v)return;
                const h=(v/maxVal)*ch;
                stackY-=h;
                html+=`<rect class="act-chart-bar" x="${bx}" y="${stackY}" width="${barW}" height="${h}" fill="${CHART_COLORS[t]}" rx="2" ry="2"
                    data-period="${p.period}" data-type="${t}" data-val="${v}"
                    onmouseenter="showChartTip(this,event)" onmouseleave="hideChartTip()" onclick="drillPeriod('${p.period}')"/>`;
            });
        });

        html+='</svg></div>';
    }

    html+='<div id="actDrillArea"></div>';
    el.innerHTML=html;
}

function showChartTip(bar,e){
    const tip=document.getElementById('actChartTip'),wrap=document.getElementById('actChartWrap');
    if(!tip||!wrap)return;
    const period=bar.dataset.period;
    const pData=(window._lastChart.chart||[]).filter(r=>r.period===period);
    const types=['borrow','use','transfer','return','dispose'];
    const mFull=TH?['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม']:['','January','February','March','April','May','June','July','August','September','October','November','December'];

    let periodLabel=period;
    if(_chartMode==='month'){const p=period.split('-');periodLabel=(mFull[parseInt(p[1])]||p[1])+' '+p[0];}

    let rows='';
    types.forEach(t=>{
        const rd=pData.find(r=>r.txn_type===t); if(!rd)return;
        rows+=`<div style="display:flex;align-items:center;gap:6px"><span style="width:8px;height:8px;border-radius:2px;background:${CHART_COLORS[t]};flex-shrink:0"></span>${TYPE_META[t].label}<span style="margin-left:auto;font-weight:700">${num(rd.cnt)}</span></div>`;
    });
    tip.innerHTML=`<div style="font-weight:700;margin-bottom:4px;border-bottom:1px solid rgba(255,255,255,.15);padding-bottom:4px">📅 ${periodLabel}</div>${rows}<div style="margin-top:4px;font-size:9px;color:#94a3b8">${TH?'คลิกเพื่อเจาะรายละเอียด':'Click to drill down'}</div>`;
    tip.classList.add('show');

    const wr=wrap.getBoundingClientRect(),tw=tip.offsetWidth||140;
    let left=e.clientX-wr.left-tw/2,top=e.clientY-wr.top-tip.offsetHeight-12;
    left=Math.max(4,Math.min(left,wr.width-tw-4));
    if(top<0)top=e.clientY-wr.top+12;
    tip.style.left=left+'px';tip.style.top=top+'px';
}
function hideChartTip(){const t=document.getElementById('actChartTip');if(t)t.classList.remove('show');}

/* ── Drill into period ── */
async function drillPeriod(period){
    const area=document.getElementById('actDrillArea');
    if(!area)return;
    area.innerHTML='<div class="act-loading"><i class="fas fa-circle-notch"></i></div>';
    try{
        let url=`/v1/api/borrow.php?action=activity_chart&mode=${_chartMode}&drill=${period}`;
        if(_chartChemId)url+=`&chemical_id=${_chartChemId}`;
        if(_chartYear)url+=`&year=${_chartYear}`;
        if(_chartMonth)url+=`&month=${_chartMonth}`;
        const d=await apiFetch(url);
        if(!d.success)throw new Error(d.error);
        const items=d.data.drill||[];

        // period display label
        const mFull=TH?['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม']:['','January','February','March','April','May','June','July','August','September','October','November','December'];
        let pLabel=period;
        if(_chartMode==='month'){const p=period.split('-');pLabel=(mFull[parseInt(p[1])]||p[1])+' '+p[0];}

        let html=`<div class="act-drill"><div class="act-drill-hdr"><h4>📋 ${TH?'รายละเอียด':'Details'}: ${pLabel}</h4><span style="font-size:11px;color:var(--c3)">${items.length} ${TH?'รายการ':'items'}</span><button class="act-drill-close" onclick="document.getElementById('actDrillArea').innerHTML=''"><i class="fas fa-times"></i></button></div>`;

        if(!items.length){
            html+=`<div class="act-empty"><i class="fas fa-inbox"></i><p>${TH?'ไม่มีรายการในช่วงนี้':'No items in this period'}</p></div>`;
        } else {
            // Deeper drill option
            if(_chartMode==='year'||_chartMode==='month'){
                const nMode=_chartMode==='year'?'month':'day';
                const nLbl=nMode==='month'?(TH?'🔍 เจาะรายเดือน':'🔍 View by Month'):(TH?'🔍 เจาะรายวัน':'🔍 View by Day');
                const onClick=_chartMode==='year'?`_chartMode='month';_chartYear='${period}'`:`_chartMode='day';_chartMonth='${period}'`;
                html+=`<div style="margin-bottom:12px"><button class="act-detail-filter-btn active" onclick="${onClick};loadChartView(document.getElementById('actContent'))">${nLbl}</button></div>`;
            }

            html+='<div class="act-txn-list">';
            items.forEach(r=>{
                const m=TYPE_META[r.txn_type]||TYPE_META.borrow;
                const fromTo=r.from_name&&r.to_name?`${esc(r.from_name)} → ${esc(r.to_name)}`:esc(r.from_name||r.to_name||'');
                html+=`<div class="act-txn-row">
                    <div class="act-txn-dot ${r.txn_type}"></div>
                    <div class="act-txn-info">
                        <div class="act-txn-name"><span class="tl-type ${r.txn_type}" style="font-size:10px;padding:1px 6px;border-radius:4px;margin-right:4px">${m.label}</span> ${esc(r.chemical_name)}</div>
                        <div class="act-txn-sub">
                            ${r.barcode?`<span><i class="fas fa-barcode"></i>${r.barcode}</span>`:''}
                            <span><i class="fas fa-user"></i>${fromTo}</span>
                        </div>
                    </div>
                    <span class="act-txn-badge ${r.status}">${STATUS_LBL[r.status]||r.status}</span>
                    <span class="act-txn-qty">${Number(r.quantity||0).toLocaleString()} ${r.unit||''}</span>
                    <span class="act-txn-date">${formatDate(r.created_at)}</span>
                </div>`;
            });
            html+='</div>';
        }
        html+='</div>';
        area.innerHTML=html;
        area.scrollIntoView({behavior:'smooth',block:'nearest'});
    }catch(e){
        area.innerHTML=`<div class="ci-alert ci-alert-danger">${e.message}</div>`;
    }
}
</script>
</body>
</html>
