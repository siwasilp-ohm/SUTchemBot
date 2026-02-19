<?php
/**
 * CAS ↔ Packaging ↔ 3D Model Map — PRO Edition
 * 0-9 digit filter, multi-view (table / card / compact),
 * inline 3D model embed add, professional analytics dashboard
 */
require_once __DIR__ . '/../includes/layout.php';
$user = Auth::getCurrentUser();
if (!$user) { header('Location: /v1/pages/login.php'); exit; }
$lang = I18n::getCurrentLang();
$roleLevel = (int)($user['role_level'] ?? $user['level'] ?? 0);
$isAdmin = $roleLevel >= 5;
$isManager = $roleLevel >= 3;
if (!$isManager) { header('Location: /v1/'); exit; }
Layout::head('CAS Map — แผนที่สารเคมี ↔ บรรจุภัณฑ์ ↔ โมเดล 3D');
?>
<body>
<?php Layout::sidebar('cas-map'); Layout::beginContent(); ?>
<?php Layout::pageHeader('CAS ↔ Packaging ↔ 3D Model Map', 'fas fa-project-diagram', 'แผนที่ความสัมพันธ์ระหว่าง CAS Number, บรรจุภัณฑ์ และโมเดล 3D — กรอง ค้นหา จัดการ ได้ครบจากที่นี่'); ?>

<style>
/* ═══ CSS Variables ═══ */
:root{--cm-primary:#6C5CE7;--cm-primary-light:#ede9fe;--cm-success:#059669;--cm-success-bg:#d1fae5;--cm-warning:#d97706;--cm-warning-bg:#fef3c7;--cm-danger:#dc2626;--cm-danger-bg:#fee2e2;--cm-muted:#9ca3af;--cm-muted-bg:#f3f4f6;--cm-radius:12px;--cm-shadow:0 4px 16px rgba(0,0,0,.06)}

/* ═══ Stats Bar ═══ */
.cm-stats{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:20px}
.cm-stat{padding:14px 16px;border-radius:var(--cm-radius);text-align:center;cursor:pointer;transition:all .2s;border:2px solid transparent;position:relative;overflow:hidden}
.cm-stat::before{content:'';position:absolute;inset:0;opacity:.04;transition:opacity .2s}
.cm-stat:hover{transform:translateY(-2px);box-shadow:var(--cm-shadow)}
.cm-stat:hover::before{opacity:.08}
.cm-stat.active{border-color:currentColor;box-shadow:0 4px 20px rgba(0,0,0,.1)}
.cm-stat .num{font-size:26px;font-weight:800;line-height:1;letter-spacing:-.5px}
.cm-stat .lbl{font-size:10px;font-weight:700;margin-top:4px;text-transform:uppercase;letter-spacing:.5px;opacity:.8}
.cm-stat .pct{font-size:9px;font-weight:600;margin-top:2px;opacity:.6}

/* ═══ Control Bar ═══ */
.cm-controls{display:flex;gap:10px;align-items:center;margin-bottom:6px;flex-wrap:wrap;padding:14px 16px;background:#fff;border:1.5px solid #e5e7eb;border-radius:var(--cm-radius);box-shadow:var(--cm-shadow)}
.cm-search{flex:1;min-width:200px;position:relative}
.cm-search i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#bbb;font-size:13px}
.cm-search input{width:100%;padding:10px 14px 10px 36px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:13px;transition:all .2s;background:#f9fafb}
.cm-search input:focus{outline:none;border-color:var(--cm-primary);box-shadow:0 0 0 3px rgba(108,92,231,.1);background:#fff}
.cm-search .clear-btn{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:#ccc;cursor:pointer;font-size:14px;display:none;padding:2px}
.cm-search .clear-btn.show{display:block}
.cm-search .clear-btn:hover{color:#999}
.cm-select{padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:13px;background:#f9fafb;cursor:pointer;transition:all .2s;min-width:130px}
.cm-select:focus{outline:none;border-color:var(--cm-primary)}
.cm-view-toggle{display:flex;border:1.5px solid #e5e7eb;border-radius:10px;overflow:hidden;background:#f9fafb}
.cm-view-btn{padding:9px 14px;font-size:12px;cursor:pointer;background:transparent;border:none;color:#999;transition:all .15s;display:flex;align-items:center;gap:5px}
.cm-view-btn:hover{color:#333;background:#f0f0ff}
.cm-view-btn.active{background:var(--cm-primary);color:#fff}

/* ═══ Alphabet Filter ═══ */
.cm-alpha{display:flex;gap:3px;margin-bottom:16px;flex-wrap:wrap;padding:8px 0}
.cm-alpha-btn{width:32px;height:30px;display:flex;align-items:center;justify-content:center;border-radius:8px;border:1.5px solid #e5e7eb;background:#fff;font-size:11px;font-weight:700;color:#666;cursor:pointer;transition:all .15s;font-family:'Courier New',monospace}
.cm-alpha-btn:hover{border-color:var(--cm-primary);color:var(--cm-primary);background:#faf5ff}
.cm-alpha-btn.active{background:var(--cm-primary);color:#fff;border-color:var(--cm-primary);box-shadow:0 2px 8px rgba(108,92,231,.25)}
.cm-alpha-btn.has-items{border-color:#c4b5fd}
.cm-alpha-btn.no-items{opacity:.35;cursor:default}
.cm-alpha-btn.all{width:44px;font-family:inherit}

/* ═══ Per-page selector ═══ */
.cm-perpage{display:flex;align-items:center;gap:6px;font-size:12px;color:#888}
.cm-perpage select{padding:6px 10px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:12px;background:#f9fafb;cursor:pointer;font-weight:600;color:#555}
.cm-perpage select:focus{outline:none;border-color:var(--cm-primary)}

/* ═══ Results Info ═══ */
.cm-info-bar{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;font-size:12px;color:#999}
.cm-info-bar .count{font-weight:600}
.cm-info-bar .sort-link{color:var(--cm-primary);cursor:pointer;font-weight:600;display:flex;align-items:center;gap:4px}
.cm-info-bar .sort-link:hover{text-decoration:underline}

/* ═══ Pagination ═══ */
.cm-pagination{display:flex;justify-content:space-between;align-items:center;margin-top:16px;padding:12px 16px;background:#fff;border:1.5px solid #e5e7eb;border-radius:var(--cm-radius);box-shadow:var(--cm-shadow);flex-wrap:wrap;gap:10px}
.cm-pagination .pg-info{font-size:12px;color:#888}
.cm-pagination .pg-info strong{color:#333}
.cm-pagination .pg-btns{display:flex;gap:4px;align-items:center}
.cm-pagination .pg-btn{min-width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:8px;border:1.5px solid #e5e7eb;background:#fff;font-size:12px;font-weight:600;color:#666;cursor:pointer;transition:all .15s;padding:0 8px}
.cm-pagination .pg-btn:hover:not([disabled]):not(.active){background:#f0f0ff;border-color:var(--cm-primary);color:var(--cm-primary)}
.cm-pagination .pg-btn.active{background:var(--cm-primary);color:#fff;border-color:var(--cm-primary)}
.cm-pagination .pg-btn[disabled]{opacity:.35;cursor:default}
.cm-pagination .pg-dots{font-size:12px;color:#ccc;padding:0 4px;user-select:none}

/* ═══ TABLE VIEW ═══ */
.cm-table-wrap{overflow-x:auto;border-radius:var(--cm-radius);box-shadow:var(--cm-shadow)}
.cm-table{width:100%;border-collapse:separate;border-spacing:0;border:1.5px solid #e5e7eb;border-radius:var(--cm-radius);overflow:hidden;font-size:13px}
.cm-table thead{background:linear-gradient(135deg,#f8f9fa 0%,#f0f0ff 100%)}
.cm-table th{padding:12px 14px;font-size:10px;font-weight:800;color:var(--cm-primary);text-transform:uppercase;letter-spacing:.5px;text-align:left;border-bottom:2px solid #e5e7eb;white-space:nowrap;position:sticky;top:0;z-index:2;cursor:pointer;user-select:none;transition:background .15s}
.cm-table th:hover{background:#e8e0ff}
.cm-table th .sort-icon{margin-left:3px;font-size:8px;opacity:.4}
.cm-table th.sorted .sort-icon{opacity:1;color:var(--cm-primary)}
.cm-table td{padding:10px 14px;border-bottom:1px solid #f3f4f6;vertical-align:middle;transition:background .1s}
.cm-table tbody tr{transition:all .15s}
.cm-table tbody tr:hover td{background:#faf5ff}
.cm-table tbody tr:last-child td{border-bottom:none}
.cm-table tbody tr.expanded td{background:#f8f5ff;border-bottom-color:#ede9fe}
.cm-table .cas-cell{font-family:'Courier New',monospace;font-weight:700;color:var(--cm-primary);font-size:12px;white-space:nowrap;letter-spacing:.3px}
.cm-table .chem-cell{max-width:220px}
.cm-table .chem-name{font-weight:600;color:#333;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:200px}
.cm-table .chem-formula{font-size:11px;color:#999;font-style:italic;margin-top:1px}
.cm-table .signal-word{font-size:9px;padding:1px 6px;border-radius:4px;font-weight:700;display:inline-block;margin-top:2px}
.cm-table .signal-danger{background:#fee2e2;color:#dc2626}
.cm-table .signal-warning{background:#fef3c7;color:#d97706}

/* Pills */
.cm-pills{display:flex;flex-wrap:wrap;gap:4px}
.cm-pill{display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:6px;font-size:10px;font-weight:600;border:1px solid #e5e7eb;background:#fff;transition:all .15s;white-space:nowrap;cursor:pointer}
.cm-pill:hover{border-color:var(--cm-primary);background:#faf5ff;transform:translateY(-1px)}
.cm-pill i{font-size:9px}
.cm-pill.has-model{border-color:#86efac;background:#f0fdf4;color:var(--cm-success)}
.cm-pill.has-model i{color:var(--cm-success)}
.cm-pill.no-model{border-color:#fca5a5;background:#fef2f2;color:var(--cm-danger)}
.cm-pill.no-model i{color:var(--cm-danger)}
.cm-model-tag{display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:6px;font-size:10px;font-weight:600;background:var(--cm-primary-light);color:var(--cm-primary);margin:2px;white-space:nowrap}
.cm-model-tag.embed{background:#dbeafe;color:#2563eb}

/* Status */
.cm-badge{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:8px;font-size:10px;font-weight:700;white-space:nowrap}
.cm-badge-complete{background:var(--cm-success-bg);color:var(--cm-success)}
.cm-badge-partial{background:var(--cm-warning-bg);color:var(--cm-warning)}
.cm-badge-missing{background:var(--cm-danger-bg);color:var(--cm-danger)}
.cm-badge-none{background:var(--cm-muted-bg);color:var(--cm-muted)}

/* Actions */
.cm-actions{display:flex;gap:4px}
.cm-act{width:28px;height:28px;display:flex;align-items:center;justify-content:center;border-radius:8px;font-size:11px;border:1.5px solid #e5e7eb;background:#fff;color:#888;cursor:pointer;transition:all .15s}
.cm-act:hover{background:var(--cm-primary);color:#fff;border-color:var(--cm-primary);transform:scale(1.05)}
.cm-act.act-add:hover{background:#059669;border-color:#059669}
.cm-act.act-view:hover{background:#2563eb;border-color:#2563eb}
.cm-act.act-expand{transition:all .25s cubic-bezier(.4,0,.2,1)}
.cm-act.act-expand:hover{background:#7c3aed;border-color:#7c3aed}
.cm-act.act-expand i{transition:transform .35s cubic-bezier(.4,0,.2,1)}
.cm-act.act-expand.expanded{background:#7c3aed;color:#fff;border-color:#7c3aed;box-shadow:0 2px 8px rgba(124,58,237,.3)}
.cm-act.act-expand.expanded i{transform:rotate(180deg)}

/* ═══ Expand Row — PRO Detail Panel ═══ */
.cm-detail-row td{padding:0!important;background:transparent!important;border:none!important}
.cm-detail-row{border:none!important}
.cm-detail-wrap{overflow:hidden;max-height:0;opacity:0;transition:max-height .45s cubic-bezier(.4,0,.2,1),opacity .35s ease;will-change:max-height,opacity}
.cm-detail-wrap.open{max-height:2000px;opacity:1}
.cm-detail-inner{padding:0;margin:8px 12px 12px}

/* Hero Banner */
.cm-hero{position:relative;border-radius:14px;overflow:hidden;background:linear-gradient(135deg,#1a1033 0%,#2d1b69 40%,#4c1d95 100%);box-shadow:0 8px 32px rgba(108,92,231,.18);margin-bottom:0}
.cm-hero-grid{display:grid;grid-template-columns:1fr 360px;min-height:320px}
.cm-hero-viewer{position:relative;border-right:1px solid rgba(255,255,255,.06)}
.cm-hero-viewer iframe{width:100%;height:100%;border:none;display:block}
.cm-hero-viewer .empty{display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;min-height:320px;gap:12px}
.cm-hero-viewer .empty i{font-size:56px;color:rgba(255,255,255,.08)}
.cm-hero-viewer .empty span{font-size:12px;color:rgba(255,255,255,.3);font-weight:500}
.cm-hero-viewer .empty .empty-sub{font-size:10px;color:rgba(255,255,255,.15)}

/* Viewer overlay actions */
.cm-hero-actions{position:absolute;top:12px;right:12px;display:flex;gap:6px;z-index:10}
.cm-hero-act{width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:10px;border:none;background:rgba(255,255,255,.12);backdrop-filter:blur(12px);color:rgba(255,255,255,.8);cursor:pointer;font-size:12px;transition:all .2s}
.cm-hero-act:hover{background:rgba(108,92,231,.8);color:#fff;transform:scale(1.08)}

/* Model selector pills (top-left of viewer) */
.cm-hero-pills{position:absolute;bottom:12px;left:12px;display:flex;gap:5px;z-index:10;flex-wrap:wrap;max-width:60%}
.cm-hero-pill{padding:5px 12px;border-radius:20px;font-size:10px;font-weight:700;border:none;background:rgba(255,255,255,.1);backdrop-filter:blur(12px);color:rgba(255,255,255,.7);cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:4px;white-space:nowrap}
.cm-hero-pill:hover{background:rgba(255,255,255,.2);color:#fff}
.cm-hero-pill.active{background:rgba(108,92,231,.85);color:#fff;box-shadow:0 2px 12px rgba(108,92,231,.4)}
.cm-hero-pill i{font-size:8px}

/* Provider badge (top-left) */
.cm-hero-provider{position:absolute;top:12px;left:12px;z-index:10;padding:4px 12px;border-radius:20px;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;backdrop-filter:blur(12px)}
.cm-hero-provider.kiri{background:rgba(59,130,246,.2);color:#93c5fd}
.cm-hero-provider.sketchfab{background:rgba(217,119,6,.2);color:#fcd34d}
.cm-hero-provider.upload{background:rgba(5,150,105,.2);color:#6ee7b7}
.cm-hero-provider.generic{background:rgba(255,255,255,.1);color:rgba(255,255,255,.6)}

/* Right Info Panel */
.cm-hero-info{padding:24px;display:flex;flex-direction:column;gap:0;color:#fff;overflow-y:auto;max-height:420px}
.cm-hero-cas{font-family:'Courier New',monospace;font-size:24px;font-weight:900;letter-spacing:1px;background:linear-gradient(135deg,#a78bfa,#c4b5fd);-webkit-background-clip:text;-webkit-text-fill-color:transparent;line-height:1.1}
.cm-hero-name{font-size:14px;font-weight:600;color:rgba(255,255,255,.85);margin-top:4px;line-height:1.3}
.cm-hero-formula{font-size:12px;color:rgba(255,255,255,.4);font-style:italic;margin-top:2px}
.cm-hero-divider{height:1px;background:linear-gradient(90deg,rgba(167,139,250,.3),transparent);margin:14px 0}

/* Info items */
.cm-info-list{display:flex;flex-direction:column;gap:8px;flex:1}
.cm-info-item{display:flex;align-items:flex-start;gap:10px;padding:8px 10px;border-radius:10px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.06);transition:all .15s}
.cm-info-item:hover{background:rgba(255,255,255,.07);border-color:rgba(167,139,250,.2)}
.cm-info-item .ii-icon{width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:11px;flex-shrink:0}
.cm-info-item .ii-icon.purple{background:rgba(167,139,250,.15);color:#c4b5fd}
.cm-info-item .ii-icon.green{background:rgba(52,211,153,.15);color:#6ee7b7}
.cm-info-item .ii-icon.amber{background:rgba(251,191,36,.15);color:#fcd34d}
.cm-info-item .ii-icon.red{background:rgba(248,113,113,.15);color:#fca5a5}
.cm-info-item .ii-icon.blue{background:rgba(96,165,250,.15);color:#93c5fd}
.cm-info-item .ii-body{flex:1;min-width:0}
.cm-info-item .ii-label{font-size:9px;font-weight:700;color:rgba(255,255,255,.35);text-transform:uppercase;letter-spacing:.5px}
.cm-info-item .ii-value{font-size:12px;font-weight:600;color:rgba(255,255,255,.9);margin-top:1px;word-break:break-word}

/* Signal word highlight */
.cm-signal-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:6px;font-size:10px;font-weight:800;letter-spacing:.5px}
.cm-signal-badge.danger{background:rgba(239,68,68,.2);color:#fca5a5}
.cm-signal-badge.warning{background:rgba(245,158,11,.2);color:#fcd34d}

/* Hazard pictograms */
.cm-hazard-row{display:flex;gap:6px;flex-wrap:wrap;margin-top:4px}
.cm-hazard-pic{width:30px;height:30px;border-radius:6px;object-fit:contain;background:rgba(255,255,255,.06);padding:2px;border:1px solid rgba(255,255,255,.08)}

/* Stats bar at bottom of info panel */
.cm-hero-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:6px;margin-top:auto;padding-top:14px}
.cm-hero-stat-box{text-align:center;padding:8px 4px;border-radius:10px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.06)}
.cm-hero-stat-box .hs-num{font-size:18px;font-weight:800;line-height:1}
.cm-hero-stat-box .hs-num .hs-of{font-size:11px;font-weight:600;opacity:.45}
.cm-hero-stat-box .hs-label{font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;opacity:.5;margin-top:3px}
.cm-hero-stat-box.s-pkg{color:#c4b5fd}
.cm-hero-stat-box.s-model{color:#6ee7b7}
.cm-hero-stat-box.s-linked{color:#93c5fd}
.cm-hero-stat-box.s-status .hs-num{font-size:13px}

/* ═══ Packaging Strip ═══ */
.cm-pkg-strip{display:flex;gap:8px;padding:14px 0 0;flex-wrap:wrap}
.cm-pkg-card{flex:1;min-width:200px;max-width:320px;border-radius:12px;border:1.5px solid #e5e7eb;background:#fff;overflow:hidden;transition:all .2s;cursor:pointer}
.cm-pkg-card:hover{border-color:var(--cm-primary);box-shadow:0 4px 16px rgba(108,92,231,.08);transform:translateY(-2px)}
.cm-pkg-card.active{border-color:var(--cm-primary);box-shadow:0 4px 20px rgba(108,92,231,.15);background:#faf5ff}
.cm-pkg-card.active .cm-pkg-card-head{background:linear-gradient(135deg,#ede9fe,#faf5ff)}
.cm-pkg-card-head{display:flex;align-items:center;gap:8px;padding:10px 14px;background:linear-gradient(135deg,#faf5ff,#fff);border-bottom:1px solid #f0f0f0;transition:background .2s}
.cm-pkg-card-head .pkg-icon{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0}
.cm-pkg-card-head .pkg-icon.has{background:#d1fae5;color:#059669}
.cm-pkg-card-head .pkg-icon.miss{background:#fee2e2;color:#dc2626}
.cm-pkg-card-head .pkg-title{flex:1;font-size:12px;font-weight:700;color:#333;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.cm-pkg-card-head .pkg-sub{font-size:10px;color:#999}
.cm-pkg-card-body{padding:10px 14px;font-size:11px;color:#666}
.cm-pkg-card-body .model-link{display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:6px;font-size:10px;font-weight:600;background:var(--cm-primary-light);color:var(--cm-primary);margin-top:4px;cursor:pointer;transition:all .15s}
.cm-pkg-card-body .model-link:hover{background:var(--cm-primary);color:#fff}
.cm-pkg-card-body .model-link.embed{background:#dbeafe;color:#2563eb}
.cm-pkg-card-body .model-link.embed:hover{background:#2563eb;color:#fff}
.cm-pkg-add-btn{display:flex;align-items:center;gap:6px;padding:6px 12px;border-radius:8px;font-size:11px;font-weight:600;border:1.5px dashed #c4b5fd;background:#faf5ff;color:var(--cm-primary);cursor:pointer;transition:all .15s;width:100%;justify-content:center}
.cm-pkg-add-btn:hover{background:var(--cm-primary);color:#fff;border-style:solid}

/* ═══ Viewer Loading Overlay ═══ */
.cm-viewer-loading{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(15,15,26,.8);z-index:5;transition:opacity .3s}
.cm-viewer-loading.hidden{opacity:0;pointer-events:none}
.cm-viewer-loading .ld-spinner{width:36px;height:36px;border:3px solid rgba(167,139,250,.2);border-top-color:#a78bfa;border-radius:50%;animation:cmSpin .8s linear infinite}
@keyframes cmSpin{to{transform:rotate(360deg)}}

/* ═══ Card Inline 3D Viewer ═══ */
.cm-card-viewer-wrap{width:100%;border-radius:10px;overflow:hidden;background:#0f0f1a;border:1.5px solid #ede9fe;margin-top:10px;position:relative;transition:all .3s}
.cm-card-viewer-wrap.open{height:220px}
.cm-card-viewer-wrap.closed{height:0;border:none;margin:0;overflow:hidden}
.cm-card-viewer-wrap iframe{width:100%;height:100%;border:none;display:block}
.cm-card-viewer-bar{display:flex;align-items:center;gap:6px;padding:6px 10px;background:linear-gradient(135deg,#1a1033,#2d1b69);border-bottom:1px solid rgba(255,255,255,.06)}
.cm-card-viewer-bar .cvb-label{font-size:10px;font-weight:600;color:rgba(255,255,255,.7);flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.cm-card-viewer-bar .cvb-badge{padding:2px 8px;border-radius:10px;font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
.cm-card-viewer-bar .cvb-badge.kiri{background:rgba(59,130,246,.2);color:#93c5fd}
.cm-card-viewer-bar .cvb-badge.sketchfab{background:rgba(217,119,6,.2);color:#fcd34d}
.cm-card-viewer-bar .cvb-badge.upload{background:rgba(5,150,105,.2);color:#6ee7b7}
.cm-card-viewer-bar .cvb-badge.generic{background:rgba(255,255,255,.1);color:rgba(255,255,255,.5)}
.cm-card-viewer-close{width:22px;height:22px;border-radius:6px;border:none;background:rgba(255,255,255,.1);color:rgba(255,255,255,.6);cursor:pointer;font-size:10px;display:flex;align-items:center;justify-content:center;transition:all .15s}
.cm-card-viewer-close:hover{background:rgba(239,68,68,.3);color:#fca5a5}

/* Card pills clickable for 3D */
.cm-card .cm-pill.has-model{cursor:pointer;position:relative}
.cm-card .cm-pill.has-model::after{content:'\f1b2';font-family:'Font Awesome 5 Free';font-weight:900;font-size:7px;margin-left:2px;opacity:.5}
.cm-card .cm-pill.has-model:hover{transform:translateY(-2px);box-shadow:0 2px 8px rgba(5,150,105,.2)}

/* Responsive */
@media(max-width:900px){.cm-hero-grid{grid-template-columns:1fr;min-height:auto}.cm-hero-viewer{min-height:260px}.cm-hero-info{max-height:none}.cm-hero-stats{grid-template-columns:repeat(4,1fr)}}
@media(max-width:600px){.cm-pkg-strip{flex-direction:column}.cm-pkg-card{max-width:100%}.cm-hero-info{padding:16px}}

/* ═══ CARD VIEW ═══ */
.cm-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:14px}
.cm-card{border:1.5px solid #e5e7eb;border-radius:var(--cm-radius);background:#fff;overflow:hidden;transition:all .2s;position:relative}
.cm-card:hover{border-color:var(--cm-primary);box-shadow:0 8px 24px rgba(108,92,231,.1);transform:translateY(-2px)}
.cm-card-header{display:flex;align-items:center;gap:10px;padding:14px 16px;border-bottom:1px solid #f0f0f0;background:linear-gradient(135deg,#faf5ff 0%,#fff 100%)}
.cm-card-cas{font-family:'Courier New',monospace;font-weight:800;font-size:13px;color:var(--cm-primary);background:var(--cm-primary-light);padding:4px 10px;border-radius:8px;letter-spacing:.5px}
.cm-card-name{flex:1;font-size:13px;font-weight:600;color:#333;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.cm-card-badge{position:absolute;top:12px;right:12px}
.cm-card-body{padding:14px 16px}
.cm-card-row{display:flex;align-items:flex-start;gap:8px;margin-bottom:8px;font-size:12px}
.cm-card-row i{color:#bbb;width:14px;text-align:center;margin-top:2px;font-size:10px}
.cm-card-row .label{color:#999;min-width:50px;font-weight:600}
.cm-card-row .value{color:#333;flex:1}
.cm-card-pkgs{display:flex;flex-wrap:wrap;gap:4px;margin-top:8px}
.cm-card-footer{display:flex;gap:6px;padding:10px 16px;border-top:1px solid #f0f0f0;background:#fafafa}
.cm-card-btn{flex:1;padding:7px 12px;border-radius:8px;font-size:11px;font-weight:600;border:1.5px solid #e5e7eb;background:#fff;color:#888;cursor:pointer;transition:all .15s;display:flex;align-items:center;justify-content:center;gap:5px}
.cm-card-btn:hover{color:#fff;border-color:var(--cm-primary);background:var(--cm-primary)}
.cm-card-btn.btn-add:hover{background:#059669;border-color:#059669}

/* ═══ COMPACT VIEW ═══ */
.cm-compact{display:flex;flex-direction:column;gap:4px}
.cm-compact-row{display:flex;align-items:center;gap:12px;padding:8px 14px;border-radius:10px;border:1px solid #f0f0f0;background:#fff;transition:all .15s;cursor:pointer}
.cm-compact-row:hover{border-color:var(--cm-primary);background:#faf5ff;box-shadow:0 2px 8px rgba(108,92,231,.06)}
.cm-compact-row .idx{font-size:10px;color:#ccc;font-weight:700;min-width:24px}
.cm-compact-row .cas{font-family:'Courier New',monospace;font-weight:700;color:var(--cm-primary);font-size:12px;min-width:100px}
.cm-compact-row .name{flex:1;font-size:12px;font-weight:600;color:#333;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.cm-compact-row .formula{font-size:11px;color:#999;font-style:italic;min-width:80px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.cm-compact-row .counts{display:flex;gap:8px;font-size:10px;font-weight:600}
.cm-compact-row .cnt{padding:2px 8px;border-radius:6px}
.cm-compact-row .cnt-pkg{background:#ede9fe;color:var(--cm-primary)}
.cm-compact-row .cnt-model{background:var(--cm-success-bg);color:var(--cm-success)}
.cm-compact-row .cnt-miss{background:var(--cm-danger-bg);color:var(--cm-danger)}

/* ═══ Add Model Modal — PRO Layout ═══ */
.cm-modal-overlay{position:fixed;inset:0;z-index:1050;background:rgba(10,10,26,.65);display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity .3s ease;backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px)}
.cm-modal-overlay.show{opacity:1;pointer-events:all}
.cm-modal{width:94vw;max-width:820px;max-height:92vh;background:#fff;border-radius:20px;overflow:hidden;box-shadow:0 32px 100px rgba(0,0,0,.28),0 0 0 1px rgba(255,255,255,.05);display:flex;flex-direction:column;transform:translateY(24px) scale(.97);transition:transform .35s cubic-bezier(.19,1,.22,1),opacity .3s}
.cm-modal-overlay.show .cm-modal{transform:translateY(0) scale(1)}

/* Modal Header — Refined */
.cm-modal-head{display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid rgba(108,92,231,.08);background:linear-gradient(135deg,#f8f5ff 0%,#faf8ff 50%,#fff 100%);position:relative}
.cm-modal-head::after{content:'';position:absolute;bottom:-1px;left:24px;right:24px;height:1px;background:linear-gradient(90deg,transparent 0%,rgba(108,92,231,.15) 50%,transparent 100%)}
.cm-modal-head h3{font-size:16px;font-weight:800;color:#1a1a2e;display:flex;align-items:center;gap:10px;letter-spacing:-.2px}
.cm-modal-head h3 i{color:var(--cm-primary);font-size:18px;width:36px;height:36px;background:linear-gradient(135deg,#ede9fe,#f5f3ff);border-radius:10px;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(108,92,231,.12)}
.cm-modal-close{width:36px;height:36px;border-radius:10px;border:1.5px solid #e5e7eb;background:#fff;cursor:pointer;font-size:14px;color:#aaa;display:flex;align-items:center;justify-content:center;transition:all .2s}
.cm-modal-close:hover{background:#fee2e2;color:#dc2626;border-color:#fecaca;transform:rotate(90deg)}

/* Modal Body */
.cm-modal-body{padding:24px;overflow-y:auto;flex:1;background:#fff}
.cm-modal-body::-webkit-scrollbar{width:5px}
.cm-modal-body::-webkit-scrollbar-track{background:transparent}
.cm-modal-body::-webkit-scrollbar-thumb{background:#e5e7eb;border-radius:10px}
.cm-modal-body::-webkit-scrollbar-thumb:hover{background:#d1d5db}

/* Modal Footer — Elevated */
.cm-modal-foot{display:flex;justify-content:flex-end;gap:8px;padding:16px 24px;border-top:1px solid #f0f0f0;background:linear-gradient(to top,#f8f9fb,#fff)}

/* ═══ Step Indicator ═══ */
.cm-step-header{display:flex;align-items:center;gap:8px;margin-bottom:18px;padding-bottom:14px;border-bottom:1.5px solid #f3f4f6}
.cm-step-num{width:24px;height:24px;border-radius:50%;background:var(--cm-primary);color:#fff;font-size:11px;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 2px 8px rgba(108,92,231,.25)}
.cm-step-title{font-size:13px;font-weight:700;color:#333;letter-spacing:-.1px}
.cm-step-desc{font-size:10px;color:#aaa;margin-left:auto;font-weight:500}

/* ═══ Chemical Info Card ═══ */
.cm-chem-card{padding:14px 16px;background:linear-gradient(135deg,#f8f5ff 0%,#faf8ff 100%);border-radius:12px;margin-bottom:20px;display:flex;align-items:center;gap:12px;border:1.5px solid #ede9fe;position:relative;overflow:hidden}
.cm-chem-card::before{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:linear-gradient(to bottom,var(--cm-primary),#a78bfa);border-radius:0 4px 4px 0}
.cm-chem-card-icon{width:40px;height:40px;border-radius:10px;background:linear-gradient(135deg,#ede9fe,#ddd6fe);display:flex;align-items:center;justify-content:center;color:var(--cm-primary);font-size:16px;flex-shrink:0}
.cm-chem-card-body{flex:1;min-width:0}
.cm-chem-card-label{font-size:10px;color:#a78bfa;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:1px}
.cm-chem-card-value{font-size:13px;font-weight:700;color:#333;line-height:1.3}

/* ═══ Section Divider ═══ */
.cm-section-divider{border:none;height:0;border-top:1.5px solid #f3f4f6;margin:22px 0;position:relative}
.cm-section-divider::after{content:'';position:absolute;left:50%;top:-1px;transform:translateX(-50%);width:40px;height:2px;background:linear-gradient(90deg,transparent,rgba(108,92,231,.2),transparent)}

/* ═══ Modal Tabs — Pill Style ═══ */
.cm-modal-tabs{display:flex;gap:4px;margin-bottom:18px;padding:4px;border:1.5px solid #e5e7eb;border-radius:14px;background:#f9fafb}
.cm-modal-tab{flex:1;padding:10px 6px;font-size:12px;font-weight:700;border:none;background:transparent;color:#999;cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:6px;border-radius:10px;letter-spacing:-.1px}
.cm-modal-tab:hover{color:#555;background:rgba(108,92,231,.05)}
.cm-modal-tab.active{background:var(--cm-primary);color:#fff;box-shadow:0 4px 16px rgba(108,92,231,.3)}
.cm-modal-tab.active i{animation:cmTabPop .3s ease}
@keyframes cmTabPop{0%{transform:scale(1)}50%{transform:scale(1.2)}100%{transform:scale(1)}}

/* ═══ Form Fields — Refined ═══ */
.cm-field{margin-bottom:16px}
.cm-field label{display:flex;align-items:center;gap:4px;font-size:11px;font-weight:700;color:#777;margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px}
.cm-field .req{color:var(--cm-danger);font-size:9px}
.cm-field input,.cm-field select,.cm-field textarea{width:100%;padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:13px;color:#333;background:#fff;transition:all .25s}
.cm-field input:focus,.cm-field select:focus,.cm-field textarea:focus{outline:none;border-color:var(--cm-primary);box-shadow:0 0 0 4px rgba(108,92,231,.08);background:#fefeff}
.cm-field input::placeholder,.cm-field textarea::placeholder{color:#ccc}
.cm-field-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}

/* ═══ Embed Preview — Polished ═══ */
.cm-embed-preview{width:100%;height:220px;border-radius:14px;overflow:hidden;background:radial-gradient(ellipse at center,#1a1a3a 0%,#0f0f1a 70%);border:1.5px solid #e5e7eb;position:relative;margin-top:10px;box-shadow:0 4px 20px rgba(0,0,0,.06)}
.cm-embed-preview iframe{width:100%;height:100%;border:none}
.cm-embed-preview .empty{display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;color:#555;gap:10px}
.cm-embed-preview .empty i{font-size:36px;opacity:.15}
.cm-embed-preview .empty span{font-size:11px;color:#666}

/* Provider detect pill */
.cm-provider{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:10px;font-weight:700;margin-top:6px;letter-spacing:.2px}
.cm-provider.kiri{background:#dbeafe;color:#2563eb}
.cm-provider.sketchfab{background:#fef3c7;color:#d97706}
.cm-provider.generic{background:#f3f4f6;color:#888}

/* ═══ Upload Drop Zone — Refined ═══ */
.cm-upload-drop{border:2.5px dashed #d4d4d4;border-radius:16px;padding:36px 20px;text-align:center;cursor:pointer;transition:all .3s;background:linear-gradient(135deg,#fafafa 0%,#f9fafb 100%);position:relative;overflow:hidden}
.cm-upload-drop::before{content:'';position:absolute;inset:0;background:radial-gradient(circle at center,rgba(108,92,231,.03) 0%,transparent 70%);opacity:0;transition:opacity .3s}
.cm-upload-drop:hover,.cm-upload-drop.dragover{border-color:var(--cm-primary);background:linear-gradient(135deg,#faf5ff 0%,#f5f3ff 100%)}
.cm-upload-drop:hover::before,.cm-upload-drop.dragover::before{opacity:1}
.cm-upload-drop i{font-size:36px;color:#ddd;margin-bottom:10px;display:block;transition:all .3s}
.cm-upload-drop:hover i{color:var(--cm-primary);transform:translateY(-4px)}
.cm-upload-drop h4{font-size:14px;font-weight:700;color:#333;margin-bottom:4px}
.cm-upload-drop p{font-size:11px;color:#999}
.cm-upload-info{margin-top:10px;padding:10px 14px;background:#f0fdf4;border-radius:8px;border:1px solid #86efac;font-size:12px;color:#059669;display:none;align-items:center;gap:8px}
.cm-upload-info.show{display:flex}
.cm-upload-info .fname{font-weight:600;flex:1}

/* ═══ Buttons — Polished ═══ */
.cm-btn{padding:10px 20px;border-radius:12px;font-size:13px;font-weight:700;border:none;cursor:pointer;transition:all .2s;display:inline-flex;align-items:center;gap:7px;letter-spacing:-.1px}
.cm-btn:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,.12)}
.cm-btn:active{transform:translateY(0);box-shadow:0 2px 8px rgba(0,0,0,.1)}
.cm-btn-primary{background:linear-gradient(135deg,var(--cm-primary),#7c6fe0);color:#fff}
.cm-btn-primary:hover{box-shadow:0 6px 20px rgba(108,92,231,.3)}
.cm-btn-ghost{background:#fff;color:#666;border:1.5px solid #e5e7eb}
.cm-btn-ghost:hover{background:#f0f0ff;color:var(--cm-primary);border-color:#c4b5fd}
.cm-btn-success{background:linear-gradient(135deg,#059669,#10b981);color:#fff}
.cm-btn-success:hover{box-shadow:0 6px 20px rgba(5,150,105,.3)}
.cm-btn[disabled]{opacity:.45;cursor:not-allowed;transform:none!important;box-shadow:none!important;filter:grayscale(.3)}

/* ═══ Empty / Loading ═══ */
.cm-empty{text-align:center;padding:48px 20px;color:#999}
.cm-empty i{font-size:48px;opacity:.12;display:block;margin-bottom:14px}
.cm-empty p{font-size:14px;margin-bottom:6px}
.cm-empty .sub{font-size:12px;color:#bbb}

/* ═══ Iframe Config Panel — Refined ═══ */
.cm-iframe-config{background:linear-gradient(135deg,#f8f5ff,#faf8ff);border:1.5px solid #ede9fe;border-radius:14px;padding:16px 18px;margin-top:14px;display:none}
.cm-iframe-config.show{display:block;animation:cmFadeIn .3s ease}
.cm-iframe-config-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;padding-bottom:8px;border-bottom:1px solid rgba(108,92,231,.08)}
.cm-iframe-config-head h5{font-size:12px;font-weight:700;color:var(--cm-primary);display:flex;align-items:center;gap:6px;margin:0}
.cm-iframe-config-head h5 i{font-size:11px}
.cm-iframe-config-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px}
.cm-iframe-config-field{display:flex;flex-direction:column;gap:3px}
.cm-iframe-config-field label{font-size:10px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.3px}
.cm-iframe-config-field select,.cm-iframe-config-field input{padding:8px 11px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:12px;background:#fff;transition:all .2s}
.cm-iframe-config-field select:focus,.cm-iframe-config-field input:focus{outline:none;border-color:var(--cm-primary);box-shadow:0 0 0 3px rgba(108,92,231,.1)}
.cm-iframe-config-field textarea{padding:8px 11px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:11px;font-family:monospace;background:#fff;resize:vertical;transition:all .2s}
.cm-iframe-config-field textarea:focus{outline:none;border-color:var(--cm-primary);box-shadow:0 0 0 3px rgba(108,92,231,.1)}
.cm-iframe-config-field .hint{font-size:9px;color:#bbb;font-style:italic}
.cm-auto-toggle{display:flex;align-items:center;gap:8px;padding:10px 14px;background:#fff;border:1.5px solid #e5e7eb;border-radius:12px;margin-bottom:12px;cursor:pointer;transition:all .2s;user-select:none}
.cm-auto-toggle:hover{border-color:var(--cm-primary);background:#faf5ff}
.cm-auto-toggle.active{border-color:var(--cm-primary);background:var(--cm-primary-light)}
.cm-auto-toggle input[type=checkbox]{accent-color:var(--cm-primary);width:16px;height:16px;cursor:pointer}
.cm-auto-toggle .toggle-label{font-size:12px;font-weight:600;color:#333;flex:1}
.cm-auto-toggle .toggle-hint{font-size:10px;color:#999}
.cm-config-link{font-size:10px;color:var(--cm-primary);text-decoration:none;font-weight:600;display:inline-flex;align-items:center;gap:4px;opacity:.7;transition:opacity .2s}
.cm-config-link:hover{opacity:1;text-decoration:underline}

/* ═══ Upload 3D Preview — Pro ═══ */
.cm-upload-preview{width:100%;height:240px;border-radius:14px;overflow:hidden;background:radial-gradient(ellipse at center,#1a1a3a 0%,#0f0f1a 70%);border:1.5px solid rgba(108,92,231,.15);position:relative;margin-top:12px;display:none;animation:cmFadeIn .3s ease;box-shadow:0 4px 20px rgba(0,0,0,.08)}
.cm-upload-preview.show{display:block}
.cm-upload-preview model-viewer{width:100%;height:100%;display:block;--poster-color:transparent;--progress-bar-color:#6C5CE7;--progress-bar-height:3px}
.cm-upload-preview model-viewer::part(default-ar-button){display:none}
.cm-upload-preview-head{position:absolute;top:0;left:0;right:0;z-index:5;display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:linear-gradient(to bottom,rgba(0,0,0,.75) 0%,transparent 100%);pointer-events:none}
.cm-upload-preview-head>*{pointer-events:auto}
.cm-upload-preview-badge{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:20px;font-size:9px;font-weight:700;background:rgba(5,150,105,.2);color:#6ee7b7;backdrop-filter:blur(8px);border:1px solid rgba(5,150,105,.15)}
.cm-upload-preview-badge i{font-size:8px}
.cm-upload-preview-acts{display:flex;gap:5px}
.cm-upload-preview-act{width:30px;height:30px;border-radius:9px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.08);backdrop-filter:blur(8px);color:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:11px;transition:all .2s}
.cm-upload-preview-act:hover{background:rgba(108,92,231,.35);border-color:rgba(108,92,231,.4);transform:scale(1.05)}
.cm-upload-preview-loading{position:absolute;inset:0;z-index:3;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;background:radial-gradient(ellipse at center,#1a1a3a 0%,#0f0f1a 70%);transition:opacity .5s}
.cm-upload-preview-loading.hidden{opacity:0;pointer-events:none}
.cm-upload-preview-loading p{font-size:11px;color:#888;font-weight:500}
.cm-upload-preview-stats{position:absolute;bottom:0;left:0;right:0;z-index:5;display:flex;align-items:center;justify-content:center;gap:16px;padding:8px 14px;background:linear-gradient(to top,rgba(0,0,0,.75) 0%,transparent 100%)}
.cm-upload-preview-stats span{font-size:9px;color:rgba(255,255,255,.55);display:flex;align-items:center;gap:4px}
.cm-upload-preview-stats span i{font-size:7px}

/* ═══ Upload File Info — Pro Card ═══ */
.cm-upload-info-pro{margin-top:12px;padding:14px 16px;background:linear-gradient(135deg,#f0fdf4 0%,#ecfdf5 100%);border-radius:12px;border:1.5px solid #86efac;font-size:12px;color:#059669;display:none;flex-direction:column;gap:8px;animation:cmFadeIn .3s ease;box-shadow:0 2px 12px rgba(5,150,105,.06)}
.cm-upload-info-pro.show{display:flex}
.cm-upload-info-row{display:flex;align-items:center;gap:10px}
.cm-upload-info-icon{width:40px;height:40px;border-radius:12px;background:linear-gradient(135deg,rgba(5,150,105,.12),rgba(16,185,129,.08));display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:17px;color:#059669}
.cm-upload-info-det{flex:1;min-width:0}
.cm-upload-info-name{font-weight:700;font-size:12px;color:#333;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.cm-upload-info-meta{font-size:10px;color:#999;display:flex;gap:10px;margin-top:2px}
.cm-upload-info-meta span{display:flex;align-items:center;gap:3px}
.cm-upload-info-acts{display:flex;gap:5px;flex-shrink:0}
.cm-upload-info-btn{width:32px;height:32px;border-radius:10px;border:none;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .2s;font-size:13px}

/* ═══ Library Panel ═══ */
.cm-lib-search{position:relative;margin-bottom:12px}
.cm-lib-search i.fa-search{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#bbb;font-size:12px}
.cm-lib-search input{width:100%;padding:10px 14px 10px 34px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:12px;background:#f9fafb;transition:all .2s}
.cm-lib-search input:focus{outline:none;border-color:var(--cm-primary);box-shadow:0 0 0 3px rgba(108,92,231,.1);background:#fff}
.cm-lib-filters{display:flex;gap:6px;margin-bottom:12px;flex-wrap:wrap}
.cm-lib-filters select{padding:6px 10px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:11px;font-weight:600;background:#f9fafb;cursor:pointer;color:#555}
.cm-lib-filters select:focus{outline:none;border-color:var(--cm-primary)}
.cm-lib-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;max-height:340px;overflow-y:auto;padding:2px}
.cm-lib-grid::-webkit-scrollbar{width:6px}
.cm-lib-grid::-webkit-scrollbar-thumb{background:#ddd;border-radius:3px}
.cm-lib-card{background:#fff;border:2px solid #e5e7eb;border-radius:12px;overflow:hidden;cursor:pointer;transition:all .2s;position:relative}
.cm-lib-card:hover{border-color:var(--cm-primary);box-shadow:0 4px 16px rgba(108,92,231,.12);transform:translateY(-2px)}
.cm-lib-card.selected{border-color:#059669;box-shadow:0 4px 20px rgba(5,150,105,.22);background:#ecfdf5}
.cm-lib-card.selected::after{content:'\f00c';font-family:'Font Awesome 6 Free';font-weight:900;position:absolute;top:6px;right:6px;z-index:3;width:24px;height:24px;border-radius:50%;background:#059669;color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;box-shadow:0 2px 8px rgba(5,150,105,.45)}
.cm-lib-card-preview{height:100px;background:linear-gradient(135deg,#0f0f1a,#1a1a2e);position:relative;overflow:hidden}
.cm-lib-card-preview iframe{width:100%;height:100%;border:none;pointer-events:none}
.cm-lib-card-preview .no-pv{display:flex;align-items:center;justify-content:center;height:100%;color:#444;font-size:24px}
.cm-lib-card-preview .src-badge{position:absolute;top:6px;left:6px;font-size:7px;padding:2px 6px;border-radius:4px;font-weight:700;z-index:2}
.cm-lib-card-preview .src-badge.embed{background:rgba(37,99,235,.2);color:#60a5fa;border:1px solid rgba(37,99,235,.25)}
.cm-lib-card-preview .src-badge.glb{background:rgba(5,150,105,.2);color:#6ee7b7;border:1px solid rgba(5,150,105,.25)}
.cm-lib-card-body{padding:8px 10px}
.cm-lib-card-name{font-size:11px;font-weight:700;color:#333;line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:3px}
.cm-lib-card-meta{font-size:9px;color:#999;display:flex;flex-wrap:wrap;gap:4px}
.cm-lib-card-meta span{background:#f3f4f6;padding:1px 5px;border-radius:4px;white-space:nowrap}
.cm-lib-card-meta span.type{background:#ede9fe;color:var(--cm-primary)}
.cm-lib-empty{text-align:center;padding:40px 20px;color:#ccc}
.cm-lib-empty i{font-size:36px;opacity:.15;display:block;margin-bottom:10px}
.cm-lib-empty p{font-size:12px;color:#999}
.cm-lib-loading{text-align:center;padding:40px;color:#999}
.cm-lib-loading i{font-size:20px;color:var(--cm-primary);margin-bottom:8px;display:block}
.cm-lib-selected-info{margin-top:12px;padding:12px 14px;background:linear-gradient(135deg,#ede9fe,#faf5ff);border:1.5px solid #c4b5fd;border-radius:12px;display:none;align-items:center;gap:10px;animation:cmFadeIn .3s ease}
.cm-lib-selected-info.show{display:flex}
.cm-lib-selected-info .sel-icon{width:36px;height:36px;border-radius:10px;background:var(--cm-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0}
.cm-lib-selected-info .sel-det{flex:1;min-width:0}
.cm-lib-selected-info .sel-name{font-size:12px;font-weight:700;color:#333;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.cm-lib-selected-info .sel-meta{font-size:10px;color:#888;margin-top:1px}
.cm-lib-selected-info .sel-clear{width:28px;height:28px;border-radius:8px;border:none;background:rgba(220,38,38,.08);color:#dc2626;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:11px;transition:all .15s;flex-shrink:0}
.cm-lib-selected-info .sel-clear:hover{background:rgba(220,38,38,.15)}
.cm-upload-info-btn:hover{transform:scale(1.08)}
.cm-upload-info-btn.btn-clear{background:#fee2e2;color:#dc2626}
.cm-upload-info-btn.btn-clear:hover{background:#fca5a5;color:#991b1b}
.cm-upload-info-btn.btn-preview{background:#ede9fe;color:#6C5CE7}
.cm-upload-info-btn.btn-preview:hover{background:#ddd6fe;color:#5b45d3}

/* ═══ Delete Model Confirmation ═══ */
.cm-delete-bar{margin-top:12px;padding:12px 16px;background:linear-gradient(135deg,#fef2f2,#fff5f5);border:1.5px solid #fecaca;border-radius:12px;display:none;align-items:center;gap:12px;animation:cmFadeIn .3s ease;box-shadow:0 2px 12px rgba(220,38,38,.06)}
.cm-delete-bar.show{display:flex}
.cm-delete-bar-icon{width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,rgba(220,38,38,.12),rgba(220,38,38,.06));display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#dc2626;font-size:15px}
.cm-delete-bar-text{flex:1;font-size:12px;color:#991b1b;font-weight:500;line-height:1.4}
.cm-delete-bar-text strong{font-weight:700;color:#dc2626}
.cm-delete-bar-acts{display:flex;gap:6px;flex-shrink:0}
.cm-delete-confirm{padding:7px 16px;border-radius:9px;border:none;font-size:11px;font-weight:700;cursor:pointer;transition:all .2s}
.cm-delete-confirm:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(0,0,0,.12)}
.cm-delete-confirm.btn-yes{background:linear-gradient(135deg,#dc2626,#ef4444);color:#fff}
.cm-delete-confirm.btn-yes:hover{background:linear-gradient(135deg,#b91c1c,#dc2626)}
.cm-delete-confirm.btn-no{background:#fff;color:#666;border:1.5px solid #e5e7eb}
.cm-delete-confirm.btn-no:hover{background:#f9fafb;border-color:#d1d5db}

/* ═══ Modal Responsive ═══ */
@media(max-width:640px){
.cm-modal{max-width:100%;width:100%;max-height:100vh;border-radius:20px 20px 0 0;margin-top:auto}
.cm-modal-head{padding:14px 16px}
.cm-modal-body{padding:16px}
.cm-modal-foot{padding:12px 16px}
.cm-field-row{grid-template-columns:1fr}
}

/* ═══ Animations ═══ */
@keyframes cmFadeIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
@keyframes cmPulse{0%,100%{opacity:1}50%{opacity:.5}}

/* ═══ Responsive ═══ */
@media(max-width:900px){
    .cm-stats{grid-template-columns:repeat(3,1fr)}
    .cm-detail-grid{grid-template-columns:1fr}
    .cm-cards{grid-template-columns:repeat(auto-fill,minmax(280px,1fr))}
}
@media(max-width:640px){
    .cm-stats{grid-template-columns:repeat(2,1fr)}
    .cm-controls{flex-direction:column}
    .cm-search{min-width:100%}
    .cm-alpha-btn{width:26px;height:26px;font-size:10px}
    .cm-table th,.cm-table td{padding:8px 10px}
    .cm-field-row{grid-template-columns:1fr}
}
</style>

<!-- ═══════ Stats Dashboard ═══════ -->
<div class="cm-stats" id="cmStats">
    <div class="cm-stat active" onclick="filterStatus('')" id="cmStatAll" style="background:#f0f0ff;color:var(--cm-primary)">
        <div class="num" id="cmAll">—</div>
        <div class="lbl">ทั้งหมด</div>
        <div class="pct" id="cmAllPct">&nbsp;</div>
    </div>
    <div class="cm-stat" onclick="filterStatus('complete')" id="cmStatComplete" style="background:#f0fdf4;color:var(--cm-success)">
        <div class="num" id="cmComplete">—</div>
        <div class="lbl">✅ ครบถ้วน</div>
        <div class="pct" id="cmCompletePct">&nbsp;</div>
    </div>
    <div class="cm-stat" onclick="filterStatus('partial')" id="cmStatPartial" style="background:#fffbeb;color:var(--cm-warning)">
        <div class="num" id="cmPartial">—</div>
        <div class="lbl">⚠️ บางส่วน</div>
        <div class="pct" id="cmPartialPct">&nbsp;</div>
    </div>
    <div class="cm-stat" onclick="filterStatus('missing')" id="cmStatMissing" style="background:#fef2f2;color:var(--cm-danger)">
        <div class="num" id="cmMissing">—</div>
        <div class="lbl">❌ ขาดโมเดล</div>
        <div class="pct" id="cmMissingPct">&nbsp;</div>
    </div>
    <div class="cm-stat" onclick="filterStatus('none')" id="cmStatNone" style="background:#f9fafb;color:var(--cm-muted)">
        <div class="num" id="cmNone">—</div>
        <div class="lbl">⬜ ไม่มี Pkg</div>
        <div class="pct" id="cmNonePct">&nbsp;</div>
    </div>
</div>

<!-- ═══════ Control Bar ═══════ -->
<div class="cm-controls">
    <div class="cm-search">
        <i class="fas fa-search"></i>
        <input type="text" id="cmSearch" placeholder="ค้นหา CAS Number, ชื่อสาร, สูตรโมเลกุล..." oninput="debounceLoad()">
        <button class="clear-btn" id="cmClearSearch" onclick="clearSearch()"><i class="fas fa-times"></i></button>
    </div>
    <select class="cm-select" id="cmStatusFilter" onchange="filterStatus(this.value)">
        <option value="">ทุกสถานะ</option>
        <option value="complete">✅ ครบถ้วน</option>
        <option value="partial">⚠️ บางส่วน</option>
        <option value="missing">❌ ขาดโมเดล</option>
        <option value="none">⬜ ไม่มี Packaging</option>
    </select>
    <div class="cm-perpage">
        <span>แสดง</span>
        <select id="cmPerPage" onchange="setPerPage(this.value)">
            <option value="10">10</option>
            <option value="50" selected>50</option>
            <option value="100">100</option>
            <option value="500">500</option>
            <option value="1000">1000</option>
            <option value="0">ทั้งหมด</option>
        </select>
        <span>รายการ</span>
    </div>
    <div class="cm-view-toggle">
        <button class="cm-view-btn active" onclick="setView('table')" id="viewTable" title="ตาราง"><i class="fas fa-table"></i><span class="ci-hide-mobile">ตาราง</span></button>
        <button class="cm-view-btn" onclick="setView('card')" id="viewCard" title="การ์ด"><i class="fas fa-th-large"></i><span class="ci-hide-mobile">การ์ด</span></button>
        <button class="cm-view-btn" onclick="setView('compact')" id="viewCompact" title="กะทัดรัด"><i class="fas fa-list"></i><span class="ci-hide-mobile">กะทัดรัด</span></button>
    </div>
</div>

<!-- ═══════ A-Z 0-9 Filter ═══════ -->
<div class="cm-alpha" id="cmAlpha"></div>

<!-- ═══════ Info Bar ═══════ -->
<div class="cm-info-bar">
    <span id="cmCount" class="count"></span>
    <span class="sort-link" onclick="toggleSort()" id="cmSortBtn"><i class="fas fa-sort-alpha-down"></i> CAS ↑</span>
</div>

<!-- ═══════ Content Container ═══════ -->
<div id="cmContent">
    <div class="cm-empty"><i class="fas fa-spinner fa-spin" style="animation:cmPulse 1s infinite"></i><p>กำลังโหลดข้อมูล...</p></div>
</div>

<!-- ═══════ Pagination ═══════ -->
<div class="cm-pagination" id="cmPagination" style="display:none">
    <div class="pg-info" id="cmPgInfo"></div>
    <div class="pg-btns" id="cmPgBtns"></div>
</div>

<!-- ═══════ Add Model Modal — PRO ═══════ -->
<div class="cm-modal-overlay" id="addModelModal" onclick="if(event.target===this)closeAddModal()">
    <div class="cm-modal">
        <div class="cm-modal-head">
            <h3><i class="fas fa-cube"></i> <span id="modalTitle">เพิ่มโมเดล 3D</span></h3>
            <button class="cm-modal-close" onclick="closeAddModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="cm-modal-body">

            <!-- ═══ STEP 1: Chemical & Packaging ═══ -->
            <div class="cm-step-header">
                <span class="cm-step-num">1</span>
                <span class="cm-step-title">สารเคมีและบรรจุภัณฑ์</span>
                <span class="cm-step-desc">Chemical & Packaging</span>
            </div>

            <!-- Chemical Info Card -->
            <div class="cm-chem-card">
                <div class="cm-chem-card-icon"><i class="fas fa-flask"></i></div>
                <div class="cm-chem-card-body">
                    <div class="cm-chem-card-label">สารเคมี</div>
                    <div class="cm-chem-card-value" id="modalChemInfo">—</div>
                </div>
            </div>

            <!-- Packaging selector -->
            <div class="cm-field" id="modalPkgField">
                <label><i class="fas fa-box" style="font-size:9px;opacity:.5"></i> เลือกบรรจุภัณฑ์ที่ต้องการเพิ่มโมเดล</label>
                <select id="modalPkgSelect" onchange="onPkgSelect()">
                    <option value="">— เลือกบรรจุภัณฑ์ —</option>
                </select>
            </div>

            <!-- ═══ STEP 2: Source ═══ -->
            <div class="cm-section-divider"></div>
            <div class="cm-step-header">
                <span class="cm-step-num">2</span>
                <span class="cm-step-title">แหล่งข้อมูลโมเดล</span>
                <span class="cm-step-desc">Model Source</span>
            </div>

            <!-- Method tabs -->
            <div class="cm-modal-tabs">
                <button class="cm-modal-tab active" onclick="setModalTab('embed')" id="mtEmbed"><i class="fas fa-code"></i> Embed</button>
                <button class="cm-modal-tab" onclick="setModalTab('upload')" id="mtUpload"><i class="fas fa-cloud-upload-alt"></i> อัปโหลด</button>
                <button class="cm-modal-tab" onclick="setModalTab('library')" id="mtLibrary"><i class="fas fa-database"></i> เลือกจากระบบ</button>
            </div>

            <!-- Embed Panel -->
            <div id="panelEmbed">
                <!-- Auto Config Toggle -->
                <label class="cm-auto-toggle" id="autoConfigToggle" onclick="toggleAutoConfig()">
                    <input type="checkbox" id="modalAutoConfig" checked>
                    <span class="toggle-label"><i class="fas fa-magic" style="color:var(--cm-primary);margin-right:4px"></i> Kiri Auto Config</span>
                    <span class="toggle-hint">ใช้ค่าพารามิเตอร์จาก Iframe Config</span>
                </label>

                <div class="cm-field">
                    <label>Embed URL หรือ iFrame Code <span class="req">*</span></label>
                    <textarea id="modalEmbedUrl" rows="3" placeholder="วาง Embed URL เช่น https://kiri.app/embed/... &#10;หรือ iFrame code จาก Sketchfab, Kiri Engine ฯลฯ" oninput="onEmbedInput()" onpaste="onEmbedPaste()" style="font-family:'Courier New',monospace;font-size:12px"></textarea>
                    <div class="cm-provider" id="modalProvider" style="display:none"></div>
                </div>
                <div class="cm-embed-preview" id="modalEmbedPreview">
                    <div class="empty"><i class="fas fa-cube"></i><span>ตัวอย่าง Preview จะปรากฏที่นี่เมื่อวาง URL</span></div>
                </div>

                <!-- Iframe Config Panel (collapsible) -->
                <div class="cm-iframe-config" id="iframeConfigPanel">
                    <div class="cm-iframe-config-head">
                        <h5><i class="fas fa-cog"></i> Iframe Parameters Config</h5>
                        <a href="/v1/pages/settings.php" target="_blank" class="cm-config-link"><i class="fas fa-external-link-alt"></i> ตั้งค่าหลัก</a>
                    </div>
                    <div class="cm-iframe-config-grid">
                        <div class="cm-iframe-config-field">
                            <label>Background Theme</label>
                            <select id="cfgBgTheme" onchange="onLocalConfigChange()">
                                <option value="transparent">transparent</option>
                                <option value="dark">dark</option>
                                <option value="light">light</option>
                                <option value="gradient">gradient</option>
                            </select>
                        </div>
                        <div class="cm-iframe-config-field">
                            <label>Auto Spin Model</label>
                            <select id="cfgAutoSpin" onchange="onLocalConfigChange()">
                                <option value="1">เปิด (On)</option>
                                <option value="0">ปิด (Off)</option>
                            </select>
                        </div>
                    </div>
                    <div class="cm-iframe-config-field" style="margin-bottom:10px">
                        <label>Additional Parameters</label>
                        <input type="text" id="cfgExtraParams" style="font-family:monospace;font-size:11px" placeholder="key=value&key2=value2" onchange="onLocalConfigChange()">
                        <div class="hint">เช่น userId=1665127&amp;bg_theme=transparent</div>
                    </div>
                    <div class="cm-iframe-config-grid">
                        <div class="cm-iframe-config-field">
                            <label>Default Attributes</label>
                            <textarea id="cfgAttrs" rows="2" style="font-size:10px" placeholder='frameborder="0" allowfullscreen ...' onchange="onLocalConfigChange()"></textarea>
                        </div>
                        <div class="cm-iframe-config-field">
                            <label>Size (W × H)</label>
                            <div style="display:flex;gap:6px;align-items:center">
                                <input type="number" id="cfgWidth" min="100" max="1920" style="width:50%" placeholder="640" onchange="onLocalConfigChange()">
                                <span style="color:#ccc;font-size:12px">×</span>
                                <input type="number" id="cfgHeight" min="100" max="1080" style="width:50%" placeholder="480" onchange="onLocalConfigChange()">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upload Panel -->
            <div id="panelUpload" style="display:none">
                <div class="cm-upload-drop" id="modalDropZone" onclick="document.getElementById('modalFileInput').click()">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <h4>ลากไฟล์มาวาง หรือคลิกเพื่อเลือกไฟล์</h4>
                    <p>รองรับ .glb .gltf (สูงสุด 100MB) — จะแสดง 3D Preview ทันที</p>
                </div>
                <input type="file" id="modalFileInput" accept=".glb,.gltf,.obj,.fbx,.stl" style="display:none" onchange="onFileSelect(this)">

                <!-- PRO File Info -->
                <div class="cm-upload-info-pro" id="modalFileInfo">
                    <div class="cm-upload-info-row">
                        <div class="cm-upload-info-icon"><i class="fas fa-cube"></i></div>
                        <div class="cm-upload-info-det">
                            <div class="cm-upload-info-name" id="modalFileName"></div>
                            <div class="cm-upload-info-meta">
                                <span><i class="fas fa-weight-hanging"></i> <span id="modalFileSize">—</span></span>
                                <span><i class="fas fa-file-code"></i> <span id="modalFileExt">—</span></span>
                            </div>
                        </div>
                        <div class="cm-upload-info-acts">
                            <button class="cm-upload-info-btn btn-preview" onclick="toggleUploadPreview()" title="ดู/ซ่อน Preview" id="btnTogglePreview" style="display:none"><i class="fas fa-eye"></i></button>
                            <button class="cm-upload-info-btn btn-clear" onclick="clearFile()" title="ลบไฟล์"><i class="fas fa-trash-alt"></i></button>
                        </div>
                    </div>
                </div>

                <!-- 3D Preview for GLB/GLTF uploads -->
                <div class="cm-upload-preview" id="uploadPreview">
                    <div class="cm-upload-preview-head">
                        <span class="cm-upload-preview-badge"><i class="fas fa-check-circle"></i> Live Preview</span>
                        <div class="cm-upload-preview-acts">
                            <button class="cm-upload-preview-act" onclick="resetUploadPreviewCamera()" title="รีเซ็ตมุมมอง"><i class="fas fa-crosshairs"></i></button>
                            <button class="cm-upload-preview-act" onclick="toggleUploadPreviewRotate()" title="หมุนอัตโนมัติ" id="btnPreviewRotate"><i class="fas fa-sync-alt"></i></button>
                        </div>
                    </div>
                    <div class="cm-upload-preview-loading" id="uploadPreviewLoading">
                        <div class="ar-load-ring"><div></div><div></div><div></div></div>
                        <p>กำลังโหลด 3D Preview...</p>
                    </div>
                    <div class="cm-upload-preview-stats" id="uploadPreviewStats" style="display:none">
                        <span><i class="fas fa-cube"></i> <span id="upStatType">GLB</span></span>
                        <span><i class="fas fa-weight-hanging"></i> <span id="upStatSize">—</span></span>
                        <span><i class="fas fa-check-circle" style="color:#6ee7b7"></i> พร้อมบันทึก</span>
                    </div>
                </div>

                <!-- Delete existing model bar -->
                <div class="cm-delete-bar" id="deleteModelBar">
                    <div class="cm-delete-bar-icon"><i class="fas fa-trash-alt"></i></div>
                    <div class="cm-delete-bar-text">ลบโมเดล <strong id="deleteModelName">—</strong> ออกจากระบบ?</div>
                    <div class="cm-delete-bar-acts">
                        <button class="cm-delete-confirm btn-no" onclick="cancelDeleteModel()">ยกเลิก</button>
                        <button class="cm-delete-confirm btn-yes" id="btnDeleteConfirm" onclick="confirmDeleteModel()"><i class="fas fa-trash-alt"></i> ลบ</button>
                    </div>
                </div>
            </div>

            <!-- Library Panel -->
            <div id="panelLibrary" style="display:none">
                <div class="cm-lib-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="libSearchInput" placeholder="ค้นหาโมเดล ชื่อ, ประเภท, CAS..." oninput="onLibSearch()">
                </div>
                <div class="cm-lib-filters">
                    <select id="libTypeFilter" onchange="loadLibraryModels()">
                        <option value="">ทุกประเภท</option>
                        <option value="bottle">ขวด (Bottle)</option>
                        <option value="vial">Vial</option>
                        <option value="flask">Flask</option>
                        <option value="jar">Jar</option>
                        <option value="can">Can</option>
                        <option value="drum">Drum</option>
                        <option value="bag">Bag</option>
                        <option value="box">Box</option>
                        <option value="ampoule">Ampoule</option>
                        <option value="cylinder">Cylinder</option>
                    </select>
                    <select id="libSourceFilter" onchange="loadLibraryModels()">
                        <option value="">ทุกแหล่ง</option>
                        <option value="embed">Embed</option>
                        <option value="glb">GLB/Upload</option>
                    </select>
                </div>
                <div id="libModelGrid" class="cm-lib-grid">
                    <div class="cm-lib-loading"><i class="fas fa-spinner fa-spin"></i><p>กำลังโหลด...</p></div>
                </div>
                <div class="cm-lib-selected-info" id="libSelectedInfo">
                    <div class="sel-icon"><i class="fas fa-cube"></i></div>
                    <div class="sel-det">
                        <div class="sel-name" id="libSelName">—</div>
                        <div class="sel-meta" id="libSelMeta">—</div>
                    </div>
                    <button class="sel-clear" onclick="clearLibrarySelection()" title="ยกเลิกการเลือก"><i class="fas fa-times"></i></button>
                </div>
            </div>

            <!-- ═══ STEP 3: Details ═══ -->
            <div class="cm-section-divider"></div>
            <div class="cm-step-header">
                <span class="cm-step-num">3</span>
                <span class="cm-step-title">รายละเอียดโมเดล</span>
                <span class="cm-step-desc">Model Details</span>
            </div>

            <div class="cm-field">
                <label><i class="fas fa-tag" style="font-size:9px;opacity:.5"></i> ชื่อโมเดล <span class="req">*</span></label>
                <input type="text" id="modalLabel" placeholder="เช่น ขวดแก้ว Pyrex 2.5L">
            </div>
            <div class="cm-field-row">
                <div class="cm-field">
                    <label><i class="fas fa-box-open" style="font-size:9px;opacity:.5"></i> ประเภทภาชนะ <span class="req">*</span></label>
                    <select id="modalType">
                        <option value="bottle">ขวด (Bottle)</option>
                        <option value="vial">ขวดเล็ก (Vial)</option>
                        <option value="flask">ขวดทดลอง (Flask)</option>
                        <option value="jar">โหล (Jar)</option>
                        <option value="can">กระป๋อง (Can)</option>
                        <option value="drum">ถัง (Drum)</option>
                        <option value="bag">ถุง (Bag)</option>
                        <option value="box">กล่อง (Box)</option>
                        <option value="ampoule">แอมพูล (Ampoule)</option>
                        <option value="cylinder">ถังแก๊ส (Cylinder)</option>
                    </select>
                </div>
                <div class="cm-field">
                    <label><i class="fas fa-layer-group" style="font-size:9px;opacity:.5"></i> วัสดุ</label>
                    <select id="modalMaterial">
                        <option value="">— ไม่ระบุ —</option>
                        <option value="glass">แก้ว (Glass)</option>
                        <option value="plastic">พลาสติก (Plastic)</option>
                        <option value="metal">โลหะ (Metal)</option>
                        <option value="hdpe">HDPE</option>
                        <option value="pp">PP</option>
                        <option value="pet">PET</option>
                        <option value="amber_glass">แก้วสีชา (Amber)</option>
                    </select>
                </div>
            </div>
            <div class="cm-field">
                <label><i class="fas fa-align-left" style="font-size:9px;opacity:.5"></i> คำอธิบาย</label>
                <textarea id="modalDesc" rows="2" placeholder="รายละเอียดเพิ่มเติม (ไม่บังคับ)"></textarea>
            </div>
        </div>
        <div class="cm-modal-foot">
            <button class="cm-btn cm-btn-ghost" onclick="closeAddModal()"><i class="fas fa-times" style="font-size:11px"></i> ยกเลิก</button>
            <button class="cm-btn" id="modalDeleteBtn" onclick="showDeleteModel()" style="display:none;background:linear-gradient(135deg,#fee2e2,#fef2f2);color:#dc2626;border:1.5px solid #fecaca;margin-right:auto"><i class="fas fa-trash-alt"></i> ลบโมเดลนี้</button>
            <button class="cm-btn cm-btn-success" id="modalSaveBtn" onclick="saveModel()"><i class="fas fa-save"></i> บันทึกโมเดล</button>
        </div>
    </div>
</div>

<!-- Google model-viewer for upload preview -->
<script type="module" src="https://ajax.googleapis.com/ajax/libs/model-viewer/3.4.0/model-viewer.min.js"></script>

<script>
// ═══ Config ═══
const API = '/v1/api/models3d.php';
const TYPES = {bottle:'ขวด',vial:'ขวดเล็ก',flask:'ขวดทดลอง',jar:'โหล',can:'กระป๋อง',drum:'ถัง',bag:'ถุง',box:'กล่อง',ampoule:'แอมพูล',cylinder:'ถังแก๊ส'};
const MATS = {glass:'แก้ว',plastic:'พลาสติก',metal:'โลหะ',hdpe:'HDPE',pp:'PP',pet:'PET',amber_glass:'แก้วสีชา'};

// ═══ State ═══
let allData = [];
let filteredData = [];
let currentView = 'table';
let currentStatus = '';
let currentAlpha = '';
let sortAsc = true;
let expandedRows = new Set();
let debounceTimer = null;
let currentPage = 1;
let perPage = 50;
let embedPreviewTimer = null;
let uploadedFile = null;
let modalChemId = null;
let modalPkgId = null;
let iframeConfig = {};
let iframeConfigLoaded = false;

// ═══ Helpers ═══
function esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
function escAttr(s) { return (s || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '&quot;'); }

async function apiFetch(url, opts) {
    const token = localStorage.getItem('auth_token') || '';
    const headers = opts?.headers || {};
    if (token) headers['Authorization'] = 'Bearer ' + token;
    if (!(opts?.body instanceof FormData) && !headers['Content-Type']) headers['Content-Type'] = 'application/json';
    const r = await fetch(url, { ...opts, headers });
    return r.json();
}

function showToast(msg, type = 'success') {
    const t = document.createElement('div');
    t.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;padding:12px 20px;border-radius:10px;font-size:13px;font-weight:600;animation:cmFadeIn .2s;max-width:400px;box-shadow:0 4px 16px rgba(0,0,0,.15)';
    t.style.background = type === 'error' ? '#fee2e2' : type === 'warning' ? '#fef3c7' : '#d1fae5';
    t.style.color = type === 'error' ? '#dc2626' : type === 'warning' ? '#d97706' : '#059669';
    t.innerHTML = '<i class="fas fa-' + (type === 'error' ? 'times-circle' : type === 'warning' ? 'exclamation-triangle' : 'check-circle') + '"></i> ' + esc(msg);
    document.body.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity .3s'; setTimeout(() => t.remove(), 300); }, 3000);
}

function pct(n, total) { return total ? Math.round(n / total * 100) + '%' : '0%'; }

// ═══ Alphabet Filter ═══
function buildAlphaBar(data) {
    const chars = new Set();
    data.forEach(r => { const c = (r.cas_number || '')[0]; if (c) chars.add(c.toUpperCase()); });
    const bar = document.getElementById('cmAlpha');
    let html = '<button class="cm-alpha-btn all' + (currentAlpha === '' ? ' active' : '') + '" onclick="setAlpha(\'\')">ทั้งหมด</button>';
    for (let i = 0; i <= 9; i++) {
        const c = String(i);
        const has = chars.has(c);
        html += '<button class="cm-alpha-btn' + (currentAlpha === c ? ' active' : '') + (has ? ' has-items' : ' no-items') + '" onclick="' + (has ? "setAlpha('" + c + "')" : '') + '">' + c + '</button>';
    }
    bar.innerHTML = html;
}

function setAlpha(c) {
    currentAlpha = c;
    applyFilters();
    buildAlphaBar(allData);
}

// ═══ Controls ═══
function debounceLoad() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(loadData, 300);
    const v = document.getElementById('cmSearch').value;
    document.getElementById('cmClearSearch').classList.toggle('show', v.length > 0);
}

function clearSearch() {
    document.getElementById('cmSearch').value = '';
    document.getElementById('cmClearSearch').classList.remove('show');
    loadData();
}

function filterStatus(st) {
    currentStatus = st;
    document.getElementById('cmStatusFilter').value = st;
    document.querySelectorAll('.cm-stat').forEach(e => e.classList.remove('active'));
    const id = st ? 'cmStat' + st.charAt(0).toUpperCase() + st.slice(1) : 'cmStatAll';
    document.getElementById(id)?.classList.add('active');
    applyFilters();
}

function setView(v) {
    currentView = v;
    document.querySelectorAll('.cm-view-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('view' + v.charAt(0).toUpperCase() + v.slice(1)).classList.add('active');
    expandedRows.clear();
    renderData();
}

function toggleSort() {
    sortAsc = !sortAsc;
    document.getElementById('cmSortBtn').innerHTML = '<i class="fas fa-sort-alpha-' + (sortAsc ? 'down' : 'up') + '"></i> CAS ' + (sortAsc ? '↑' : '↓');
    applyFilters();
}

// ═══ Data Loading ═══
async function loadData() {
    const search = document.getElementById('cmSearch').value.trim();
    let url = API + '?action=cas_packaging_map';
    if (search) url += '&search=' + encodeURIComponent(search);

    try {
        const d = await apiFetch(url);
        if (!d.success) throw new Error(d.error);
        // Normalize all IDs to integers for consistent === comparison
        allData = (d.data || []).map(r => {
            r.chemical_id = parseInt(r.chemical_id) || 0;
            r.pkg_count = parseInt(r.pkg_count) || 0;
            r.model_count = parseInt(r.model_count) || 0;
            r.pkg_with_model = parseInt(r.pkg_with_model) || 0;
            if (r.packaging) r.packaging = r.packaging.map(p => {
                p.id = parseInt(p.id) || 0;
                p.chemical_id = parseInt(p.chemical_id) || 0;
                if (p.model_id) p.model_id = parseInt(p.model_id);
                if (p.model_3d_id) p.model_3d_id = parseInt(p.model_3d_id);
                return p;
            });
            if (r.direct_models) r.direct_models = r.direct_models.map(m => {
                m.id = parseInt(m.id) || 0;
                if (m.chemical_id) m.chemical_id = parseInt(m.chemical_id);
                return m;
            });
            return r;
        });
        updateStats(d.stats || {});
        buildAlphaBar(allData);
        applyFilters();
    } catch (e) {
        document.getElementById('cmContent').innerHTML = '<div class="cm-empty"><i class="fas fa-exclamation-triangle"></i><p>' + esc(e.message) + '</p></div>';
    }
}

function updateStats(stats) {
    const t = stats.total || 0;
    document.getElementById('cmAll').textContent = t;
    document.getElementById('cmComplete').textContent = stats.complete || 0;
    document.getElementById('cmPartial').textContent = stats.partial || 0;
    document.getElementById('cmMissing').textContent = stats.missing || 0;
    document.getElementById('cmNone').textContent = stats.none || 0;
    document.getElementById('cmAllPct').textContent = '100%';
    document.getElementById('cmCompletePct').textContent = pct(stats.complete || 0, t);
    document.getElementById('cmPartialPct').textContent = pct(stats.partial || 0, t);
    document.getElementById('cmMissingPct').textContent = pct(stats.missing || 0, t);
    document.getElementById('cmNonePct').textContent = pct(stats.none || 0, t);
}

function applyFilters(keepPage) {
    let data = [...allData];
    // Status filter
    if (currentStatus) data = data.filter(r => r.status === currentStatus);
    // Alpha filter
    if (currentAlpha) data = data.filter(r => (r.cas_number || '')[0]?.toUpperCase() === currentAlpha);
    // Sort
    data.sort((a, b) => {
        const cmp = (a.cas_number || '').localeCompare(b.cas_number || '', undefined, { numeric: true });
        return sortAsc ? cmp : -cmp;
    });
    filteredData = data;
    if (!keepPage) currentPage = 1;
    renderData();
}

// ═══ Pagination helpers ═══
function getPageData() {
    if (perPage === 0) return { data: filteredData, start: 0, end: filteredData.length, totalPages: 1 };
    const totalPages = Math.max(1, Math.ceil(filteredData.length / perPage));
    if (currentPage > totalPages) currentPage = totalPages;
    const start = (currentPage - 1) * perPage;
    const end = Math.min(start + perPage, filteredData.length);
    return { data: filteredData.slice(start, end), start, end, totalPages };
}

function setPerPage(v) {
    perPage = parseInt(v) || 0;
    currentPage = 1;
    renderData();
}

function goPage(p) {
    currentPage = p;
    renderData();
    document.getElementById('cmContent').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function renderPagination(total, totalPages, start, end) {
    const wrap = document.getElementById('cmPagination');
    // Update info bar
    if (perPage === 0) {
        document.getElementById('cmCount').innerHTML = '<i class="fas fa-database" style="margin-right:4px"></i> แสดง <strong>ทั้งหมด ' + total + '</strong> จาก ' + allData.length + ' รายการ';
    } else {
        document.getElementById('cmCount').innerHTML = '<i class="fas fa-database" style="margin-right:4px"></i> แสดง <strong>' + (start + 1) + '–' + end + '</strong> จาก ' + total + ' รายการ (กรองจาก ' + allData.length + ')';
    }
    if (totalPages <= 1) { wrap.style.display = 'none'; return; }
    wrap.style.display = 'flex';
    document.getElementById('cmPgInfo').innerHTML = 'หน้า <strong>' + currentPage + '</strong> / ' + totalPages + ' &nbsp;·&nbsp; รวม <strong>' + total + '</strong> รายการ';
    let btns = '<button class="pg-btn" onclick="goPage(1)" ' + (currentPage <= 1 ? 'disabled' : '') + '><i class="fas fa-angle-double-left"></i></button>';
    btns += '<button class="pg-btn" onclick="goPage(' + (currentPage - 1) + ')" ' + (currentPage <= 1 ? 'disabled' : '') + '><i class="fas fa-angle-left"></i></button>';
    const range = 2;
    let pStart = Math.max(1, currentPage - range);
    let pEnd = Math.min(totalPages, currentPage + range);
    if (pStart > 1) { btns += '<button class="pg-btn" onclick="goPage(1)">1</button>'; if (pStart > 2) btns += '<span class="pg-dots">…</span>'; }
    for (let p = pStart; p <= pEnd; p++) {
        btns += '<button class="pg-btn' + (p === currentPage ? ' active' : '') + '" onclick="goPage(' + p + ')">' + p + '</button>';
    }
    if (pEnd < totalPages) { if (pEnd < totalPages - 1) btns += '<span class="pg-dots">…</span>'; btns += '<button class="pg-btn" onclick="goPage(' + totalPages + ')">' + totalPages + '</button>'; }
    btns += '<button class="pg-btn" onclick="goPage(' + (currentPage + 1) + ')" ' + (currentPage >= totalPages ? 'disabled' : '') + '><i class="fas fa-angle-right"></i></button>';
    btns += '<button class="pg-btn" onclick="goPage(' + totalPages + ')" ' + (currentPage >= totalPages ? 'disabled' : '') + '><i class="fas fa-angle-double-right"></i></button>';
    document.getElementById('cmPgBtns').innerHTML = btns;
}

// ═══ Render Engine ═══
function renderData() {
    const el = document.getElementById('cmContent');
    if (!filteredData.length) {
        el.innerHTML = '<div class="cm-empty"><i class="fas fa-inbox"></i><p>ไม่พบข้อมูลที่ตรงกับเงื่อนไข</p><div class="sub">ลองปรับตัวกรองหรือล้างการค้นหา</div></div>';
        document.getElementById('cmPagination').style.display = 'none';
        document.getElementById('cmCount').innerHTML = '<i class="fas fa-database" style="margin-right:4px"></i> แสดง <strong>0</strong> จาก ' + allData.length + ' รายการ';
        return;
    }
    const pg = getPageData();
    switch (currentView) {
        case 'table': renderTable(el, pg); break;
        case 'card': renderCards(el, pg); break;
        case 'compact': renderCompact(el, pg); break;
    }
    renderPagination(filteredData.length, pg.totalPages, pg.start, pg.end);
}

function statusBadge(st) {
    const map = { complete: ['check-circle', 'ครบ', 'cm-badge-complete'], partial: ['exclamation-circle', 'บางส่วน', 'cm-badge-partial'], missing: ['times-circle', 'ขาด', 'cm-badge-missing'], none: ['minus-circle', 'ไม่มี', 'cm-badge-none'] };
    const m = map[st] || map.none;
    return '<span class="cm-badge ' + m[2] + '"><i class="fas fa-' + m[0] + '"></i> ' + m[1] + '</span>';
}

function renderPkgPills(pkgs) {
    if (!pkgs || !pkgs.length) return '<span style="font-size:11px;color:#ccc">— ยังไม่มี —</span>';
    let h = '<div class="cm-pills">';
    pkgs.forEach(p => {
        const has = !!p.model_id;
        const lbl = (TYPES[p.container_type] || p.container_type) + (p.capacity ? ' ' + p.capacity + (p.capacity_unit || '') : '');
        h += '<span class="cm-pill ' + (has ? 'has-model' : 'no-model') + '" title="' + esc(p.label || lbl) + (has ? ' — มีโมเดล' : ' — ยังไม่มีโมเดล') + '">';
        h += '<i class="fas fa-' + (has ? 'check-circle' : 'times-circle') + '"></i> ' + esc(lbl);
        h += '</span>';
    });
    return h + '</div>';
}

function renderModelTags(r) {
    let models = [];
    if (r.packaging) r.packaging.forEach(p => { if (p.model_id) models.push({ label: p.model_label, src: p.model_source_type }); });
    if (r.direct_models) r.direct_models.forEach(m => models.push({ label: m.label, src: m.source_type }));
    if (!models.length) return '<span style="font-size:11px;color:#ccc">—</span>';
    return models.map(m => '<span class="cm-model-tag' + (m.src === 'embed' ? ' embed' : '') + '"><i class="fas fa-' + (m.src === 'embed' ? 'code' : 'cube') + '" style="font-size:8px"></i> ' + esc((m.label || '').substring(0, 18)) + (m.label && m.label.length > 18 ? '…' : '') + '</span>').join('');
}

function signalBadge(sw) {
    if (!sw) return '';
    if (sw.toLowerCase() === 'danger') return '<span class="signal-word signal-danger">DANGER</span>';
    if (sw.toLowerCase() === 'warning') return '<span class="signal-word signal-warning">WARNING</span>';
    return '';
}

// Helper to safely encode packaging data for onclick attribute
function encodePkgs(pkgs) {
    return encodeURIComponent(JSON.stringify(pkgs || []));
}

// Helper: get first available model for a chemical row
function getFirstModel(r) {
    if (r.packaging) {
        for (const p of r.packaging) {
            if (p.model_id) return { id: p.model_id, label: p.model_label, src: p.model_source_type, url: p.model_embed_url || p.model_file_url, provider: p.model_provider };
        }
    }
    if (r.direct_models && r.direct_models.length) {
        const m = r.direct_models[0];
        return { id: m.id, label: m.label, src: m.source_type, url: m.embed_url || m.file_url, provider: m.embed_provider };
    }
    return null;
}

// ─── TABLE VIEW ───
function renderTable(el, pg) {
    let h = '<div class="cm-table-wrap"><table class="cm-table"><thead><tr>';
    h += '<th style="width:36px">#</th>';
    h += '<th>CAS Number</th>';
    h += '<th>สารเคมี</th>';
    h += '<th>บรรจุภัณฑ์</th>';
    h += '<th>โมเดล 3D</th>';
    h += '<th>สถานะ</th>';
    h += '<th style="width:100px">จัดการ</th>';
    h += '</tr></thead><tbody>';
    pg.data.forEach((r, i) => {
        const isExp = expandedRows.has(r.chemical_id);
        h += '<tr class="' + (isExp ? 'expanded' : '') + '">';
        h += '<td style="color:#ccc;font-size:10px;font-weight:700">' + (pg.start + i + 1) + '</td>';
        h += '<td class="cas-cell">' + esc(r.cas_number) + '</td>';
        h += '<td class="chem-cell"><div class="chem-name" title="' + esc(r.chemical_name || '') + '">' + esc(r.chemical_name || '—') + '</div>';
        if (r.formula) h += '<div class="chem-formula">' + esc(r.formula) + '</div>';
        h += signalBadge(r.signal_word);
        h += '</td>';
        h += '<td>' + renderPkgPills(r.packaging) + '</td>';
        h += '<td>' + renderModelTags(r) + '</td>';
        h += '<td>' + statusBadge(r.status) + '</td>';
        h += '<td class="cm-actions">';
        h += '<button class="cm-act act-add" title="เพิ่มโมเดล" onclick="openAddModal(' + r.chemical_id + ',\'' + escAttr(r.cas_number) + '\',\'' + escAttr(r.chemical_name || '') + '\',\'' + encodePkgs(r.packaging) + '\')"><i class="fas fa-plus"></i></button>';
        h += '<button class="cm-act act-expand' + (isExp ? ' expanded' : '') + '" title="รายละเอียด" onclick="toggleExpand(' + r.chemical_id + ')"><i class="fas fa-chevron-down"></i></button>';
        h += '<button class="cm-act act-view" title="ดูโมเดล" onclick="goViewModels(\'' + escAttr(r.cas_number) + '\')"><i class="fas fa-eye"></i></button>';
        h += '</td></tr>';
        // Expandable detail row — always render, use CSS for slide
        h += '<tr class="cm-detail-row"><td colspan="7"><div class="cm-detail-wrap' + (isExp ? ' open' : '') + '" id="detail_' + r.chemical_id + '">';
        if (isExp) h += renderDetailInner(r);
        h += '</div></td></tr>';
    });
    h += '</tbody></table></div>';
    el.innerHTML = h;
    // Trigger slide open animation for newly expanded rows
    requestAnimationFrame(() => {
        expandedRows.forEach(id => {
            const wrap = document.getElementById('detail_' + id);
            const row = allData.find(d => d.chemical_id === id);
            if (wrap && row && !wrap.classList.contains('open')) {
                wrap.innerHTML = renderDetailInner(row);
                wrap.classList.add('open');
            }
        });
    });
}

// Build indexed model list from a chemical row — each entry knows its pkgIndex
function collectModels(r) {
    let models = [];
    if (r.packaging) r.packaging.forEach((p, pi) => {
        if (p.model_id) models.push({ id: p.model_id, label: p.model_label, src: p.model_source_type, url: p.model_embed_url || p.model_file_url, provider: p.model_provider, pkgLabel: p.label || (TYPES[p.container_type]||p.container_type), pkgIdx: pi, pkgId: p.id });
    });
    if (r.direct_models) r.direct_models.forEach(m => models.push({ id: m.id, label: m.label, src: m.source_type, url: m.embed_url || m.file_url, provider: m.embed_provider, pkgLabel: null, pkgIdx: -1, pkgId: null }));
    return models;
}

function renderDetailInner(r) {
    const models = collectModels(r);
    const viewerId = 'viewer_' + r.chemical_id;
    const curModel = models[0] || null;
    let h = '<div class="cm-detail-inner">';

    // ══ HERO BANNER ══
    h += '<div class="cm-hero" data-chem-id="' + r.chemical_id + '">';
    h += '<div class="cm-hero-grid">';

    // ── Left: 3D Viewer ──
    h += '<div class="cm-hero-viewer" id="' + viewerId + '">';
    if (curModel && curModel.url) {
        h += buildViewerOverlay(curModel, models, r.chemical_id, viewerId, 0);
        h += '<div class="cm-viewer-loading" id="ld_' + viewerId + '"><div class="ld-spinner"></div></div>';
        h += renderModelFrame(curModel, viewerId);
    } else {
        h += '<div class="empty"><i class="fas fa-cube"></i><span>ยังไม่มีโมเดล 3D สำหรับ CAS นี้</span><span class="empty-sub">กดบรรจุภัณฑ์ด้านล่าง หรือปุ่ม + เพื่อเพิ่มโมเดล</span></div>';
    }
    h += '</div>';

    // ── Right: Info Panel ──
    h += '<div class="cm-hero-info" id="info_' + r.chemical_id + '">';
    h += '<div class="cm-hero-cas">' + esc(r.cas_number) + '</div>';
    h += '<div class="cm-hero-name">' + esc(r.chemical_name || '—') + '</div>';
    if (r.formula) h += '<div class="cm-hero-formula"><i class="fas fa-atom" style="margin-right:4px;font-size:10px"></i>' + esc(r.formula) + '</div>';
    h += '<div class="cm-hero-divider"></div>';
    h += '<div class="cm-info-list">';
    if (r.signal_word) {
        const swCls = r.signal_word.toLowerCase() === 'danger' ? 'danger' : r.signal_word.toLowerCase() === 'warning' ? 'warning' : '';
        h += '<div class="cm-info-item"><div class="ii-icon ' + (swCls === 'danger' ? 'red' : 'amber') + '"><i class="fas fa-exclamation-triangle"></i></div><div class="ii-body"><div class="ii-label">Signal Word</div><div class="ii-value"><span class="cm-signal-badge ' + swCls + '">' + esc(r.signal_word) + '</span></div></div></div>';
    }
    if (r.hazard_pictograms) {
        h += '<div class="cm-info-item"><div class="ii-icon red"><i class="fas fa-radiation"></i></div><div class="ii-body"><div class="ii-label">Hazard Pictograms</div><div class="cm-hazard-row">';
        const pics = (typeof r.hazard_pictograms === 'string') ? r.hazard_pictograms.split(',') : (r.hazard_pictograms || []);
        pics.forEach(p => { const c = p.trim(); if (c) h += '<img class="cm-hazard-pic" src="/v1/assets/pictograms/' + esc(c) + '.png" alt="' + esc(c) + '" title="' + esc(c) + '" onerror="this.style.display=\'none\'">';
        });
        h += '</div></div></div>';
    }
    // Dynamic model info (will be updated by switchDetailModel)
    h += '<div id="info_model_' + r.chemical_id + '">';
    if (curModel) {
        h += renderInfoModelBlock(curModel);
    } else {
        h += '<div class="cm-info-item"><div class="ii-icon purple"><i class="fas fa-cube"></i></div><div class="ii-body"><div class="ii-label">โมเดล 3D</div><div class="ii-value" style="opacity:.4">ยังไม่มีโมเดล</div></div></div>';
    }
    h += '</div>';
    h += '</div>'; // end info-list
    // Stats
    h += '<div class="cm-hero-stats">';
    h += '<div class="cm-hero-stat-box s-pkg"><div class="hs-num">' + (r.pkg_count || 0) + '</div><div class="hs-label">Packaging</div></div>';
    h += '<div class="cm-hero-stat-box s-model"><div class="hs-num">' + (r.model_count || 0) + '</div><div class="hs-label">3D Models</div></div>';
    h += '<div class="cm-hero-stat-box s-linked"><div class="hs-num">' + (r.pkg_with_model || 0) + '<span class="hs-of">/' + (r.pkg_count || 0) + '</span></div><div class="hs-label">Linked</div></div>';
    h += '<div class="cm-hero-stat-box s-status"><div class="hs-num">' + statusBadge(r.status) + '</div><div class="hs-label">Status</div></div>';
    h += '</div>';
    h += '</div>'; // end hero-info
    h += '</div>'; // end hero-grid
    h += '</div>'; // end hero

    // ══ PACKAGING STRIP — clickable per-package ══
    if (r.packaging && r.packaging.length) {
        h += '<div class="cm-pkg-strip" id="pkgstrip_' + r.chemical_id + '">';
        r.packaging.forEach((p, pi) => {
            const has = !!p.model_id;
            const lbl = p.label || ((TYPES[p.container_type] || p.container_type) + (p.capacity ? ' ' + p.capacity + (p.capacity_unit || '') : ''));
            // Find model index for this pkg
            const modelIdx = has ? models.findIndex(m => m.pkgId === p.id) : -1;
            const isFirst = (pi === 0 && curModel && curModel.pkgIdx === 0);
            h += '<div class="cm-pkg-card' + (isFirst ? ' active' : '') + '" data-pkg-id="' + p.id + '"';
            if (has && modelIdx >= 0) {
                h += ' onclick="switchDetailModel(' + r.chemical_id + ',' + modelIdx + ')"';
            } else {
                h += ' onclick="openAddModalForPkg(' + r.chemical_id + ',\'' + escAttr(r.cas_number) + '\',\'' + escAttr(r.chemical_name || '') + '\',' + p.id + ',\'' + escAttr(p.container_type || '') + '\',\'' + escAttr(p.container_material || '') + '\')"';
            }
            h += '>';
            h += '<div class="cm-pkg-card-head">';
            h += '<div class="pkg-icon ' + (has ? 'has' : 'miss') + '"><i class="fas fa-' + (has ? 'check-circle' : 'times-circle') + '"></i></div>';
            h += '<div style="flex:1;min-width:0"><div class="pkg-title">' + esc(lbl) + '</div>';
            h += '<div class="pkg-sub">' + (TYPES[p.container_type] || p.container_type) + (p.container_material ? ' · ' + (MATS[p.container_material] || p.container_material) : '') + '</div></div>';
            if (has) h += '<i class="fas fa-eye" style="color:var(--cm-primary);opacity:.3;font-size:10px" title="คลิกเพื่อดูโมเดล 3D"></i>';
            h += '</div>';
            h += '<div class="cm-pkg-card-body">';
            if (has) {
                h += '<span class="model-link' + (p.model_source_type === 'embed' ? ' embed' : '') + '"><i class="fas fa-' + (p.model_source_type === 'embed' ? 'code' : 'cube') + '" style="font-size:8px"></i> ' + esc((p.model_label || '').substring(0, 22)) + '</span>';
            } else {
                h += '<span style="font-size:10px;color:#ccc"><i class="fas fa-plus-circle" style="margin-right:3px"></i> คลิกเพื่อเพิ่มโมเดล</span>';
            }
            h += '</div></div>';
        });
        // Add-new card
        h += '<div class="cm-pkg-card" style="border-style:dashed;border-color:#c4b5fd;background:#faf5ff;display:flex;align-items:center;justify-content:center;min-height:80px" onclick="event.stopPropagation();openAddModal(' + r.chemical_id + ',\'' + escAttr(r.cas_number) + '\',\'' + escAttr(r.chemical_name || '') + '\',\'' + encodePkgs(r.packaging) + '\')">';
        h += '<div style="text-align:center;color:var(--cm-primary)"><i class="fas fa-plus-circle" style="font-size:20px;opacity:.5;display:block;margin-bottom:4px"></i><span style="font-size:10px;font-weight:600">เพิ่มโมเดลใหม่</span></div>';
        h += '</div></div>';
    } else {
        h += '<div style="text-align:center;padding:16px"><button class="cm-pkg-add-btn" style="max-width:320px;margin:0 auto" onclick="openAddModal(' + r.chemical_id + ',\'' + escAttr(r.cas_number) + '\',\'' + escAttr(r.chemical_name || '') + '\',\'' + encodePkgs(r.packaging) + '\')">';
        h += '<i class="fas fa-plus"></i> เพิ่มโมเดล 3D ตัวแรก</button></div>';
    }

    h += '</div>';
    return h;
}

// Render dynamic model info block in the info panel
function renderInfoModelBlock(m) {
    let h = '';
    h += '<div class="cm-info-item"><div class="ii-icon purple"><i class="fas fa-cube"></i></div><div class="ii-body"><div class="ii-label">โมเดลที่แสดง</div><div class="ii-value">' + esc(m.label || 'ไม่มีชื่อ') + '</div></div></div>';
    if (m.pkgLabel) {
        h += '<div class="cm-info-item"><div class="ii-icon blue"><i class="fas fa-box"></i></div><div class="ii-body"><div class="ii-label">บรรจุภัณฑ์</div><div class="ii-value">' + esc(m.pkgLabel) + '</div></div></div>';
    }
    const prov = getProviderInfo(m);
    h += '<div class="cm-info-item"><div class="ii-icon green"><i class="fas fa-' + (m.src === 'embed' ? 'code' : 'cloud-upload-alt') + '"></i></div><div class="ii-body"><div class="ii-label">ประเภท / Provider</div><div class="ii-value">' + esc(m.src === 'embed' ? 'Embed' : 'Upload') + ' · ' + esc(prov.name) + '</div></div></div>';
    return h;
}

// Build overlay elements for the viewer (provider badge, actions, pills)
function buildViewerOverlay(curModel, models, chemId, viewerId, activeIdx) {
    let h = '';
    const prov = getProviderInfo(curModel);
    h += '<span class="cm-hero-provider ' + prov.cls + '">' + prov.icon + ' ' + esc(prov.name) + '</span>';
    h += '<div class="cm-hero-actions">';
    if (curModel.src === 'embed' && curModel.url) h += '<button class="cm-hero-act" onclick="event.stopPropagation();window.open(\'' + escAttr(curModel.url) + '\',\'_blank\')" title="เปิดในแท็บใหม่"><i class="fas fa-external-link-alt"></i></button>';
    h += '<button class="cm-hero-act" onclick="event.stopPropagation();toggleDetailFullscreen(\'' + viewerId + '\')" title="ขยาย / ย่อ"><i class="fas fa-expand"></i></button>';
    h += '</div>';
    if (models.length > 1) {
        h += '<div class="cm-hero-pills">';
        models.forEach((m, i) => {
            h += '<button class="cm-hero-pill' + (i === activeIdx ? ' active' : '') + '" onclick="event.stopPropagation();switchDetailModel(' + chemId + ',' + i + ')" data-idx="' + i + '">';
            h += '<i class="fas fa-' + (m.src === 'embed' ? 'code' : 'cube') + '"></i>';
            h += esc((m.label || 'โมเดล ' + (i + 1)).substring(0, 16));
            h += '</button>';
        });
        h += '</div>';
    }
    return h;
}

// Provider display info helper
function getProviderInfo(m) {
    if (!m) return { name: 'Unknown', cls: 'generic', icon: '<i class="fas fa-globe" style="font-size:8px"></i>' };
    const url = m.url || '';
    if (url.includes('kiriengine') || url.includes('kiri.app')) return { name: 'Kiri Engine', cls: 'kiri', icon: '<i class="fas fa-camera" style="font-size:8px"></i>' };
    if (url.includes('sketchfab')) return { name: 'Sketchfab', cls: 'sketchfab', icon: '<i class="fas fa-eye" style="font-size:8px"></i>' };
    if (m.src === 'upload') return { name: 'Upload', cls: 'upload', icon: '<i class="fas fa-cloud-upload-alt" style="font-size:8px"></i>' };
    if (m.provider) return { name: m.provider, cls: 'generic', icon: '<i class="fas fa-globe" style="font-size:8px"></i>' };
    return { name: 'Embed', cls: 'generic', icon: '<i class="fas fa-code" style="font-size:8px"></i>' };
}

function getProviderCls(m) {
    if (!m || !m.url) return 'generic';
    if (m.url.includes('kiriengine') || m.url.includes('kiri.app')) return 'kiri';
    if (m.url.includes('sketchfab')) return 'sketchfab';
    if (m.src === 'upload') return 'upload';
    return 'generic';
}

// Render a 3D model iframe with onload to hide loading spinner
function renderModelFrame(m, viewerId) {
    if (!m || !m.url) return '<div class="empty"><i class="fas fa-cube"></i><span>ไม่มี URL</span></div>';
    const onload = viewerId ? ' onload="var ld=document.getElementById(\'ld_' + viewerId + '\');if(ld)ld.classList.add(\'hidden\')"' : '';
    if (m.src === 'embed') {
        return '<iframe src="' + esc(m.url) + '" allowfullscreen allow="autoplay; fullscreen" style="width:100%;height:100%;border:none;display:block"' + onload + '></iframe>';
    } else {
        return '<iframe src="/v1/pages/viewer3d.php?url=' + encodeURIComponent(m.url) + '" style="width:100%;height:100%;border:none;display:block" allowfullscreen' + onload + '></iframe>';
    }
}

// Switch model in hero viewer — smooth with loading spinner + info sync + pkg card highlight
function switchDetailModel(chemId, idx) {
    const r = allData.find(d => d.chemical_id === chemId) || filteredData.find(d => d.chemical_id === chemId);
    if (!r) return;
    const models = collectModels(r);
    if (!models[idx]) return;
    const m = models[idx];
    const viewerId = 'viewer_' + chemId;
    const viewer = document.getElementById(viewerId);
    if (!viewer) return;

    // Rebuild viewer content with loading spinner
    let inner = buildViewerOverlay(m, models, chemId, viewerId, idx);
    inner += '<div class="cm-viewer-loading" id="ld_' + viewerId + '"><div class="ld-spinner"></div></div>';
    inner += renderModelFrame(m, viewerId);
    viewer.innerHTML = inner;

    // Update info panel model block
    const infoBlock = document.getElementById('info_model_' + chemId);
    if (infoBlock) infoBlock.innerHTML = renderInfoModelBlock(m);

    // Highlight active pkg card
    const strip = document.getElementById('pkgstrip_' + chemId);
    if (strip) {
        strip.querySelectorAll('.cm-pkg-card').forEach(c => c.classList.remove('active'));
        if (m.pkgId) {
            const activeCard = strip.querySelector('[data-pkg-id="' + m.pkgId + '"]');
            if (activeCard) activeCard.classList.add('active');
        }
    }
}

// Toggle fullscreen for detail viewer
function toggleDetailFullscreen(viewerId) {
    const el = document.getElementById(viewerId);
    if (!el) return;
    if (el.style.position === 'fixed') {
        el.style.cssText = '';
        el.querySelector('.fa-compress')?.classList.replace('fa-compress', 'fa-expand');
    } else {
        el.style.cssText = 'position:fixed;inset:0;z-index:9999;width:100vw;height:100vh;max-height:none;border-radius:0;border:none;background:#000';
        const expandBtn = el.querySelector('.fa-expand');
        if (expandBtn) expandBtn.classList.replace('fa-expand', 'fa-compress');
    }
}

function toggleExpand(chemId) {
    const wrap = document.getElementById('detail_' + chemId);
    if (expandedRows.has(chemId)) {
        // Collapse with animation
        if (wrap) {
            wrap.classList.remove('open');
            // Remove iframe to stop loading after animation
            setTimeout(() => {
                wrap.innerHTML = '';
                expandedRows.delete(chemId);
                renderData();
            }, 450);
        } else {
            expandedRows.delete(chemId);
            renderData();
        }
    } else {
        expandedRows.add(chemId);
        renderData();
    }
}

// ─── CARD VIEW — PRO with per-package 3D preview ───
function renderCards(el, pg) {
    let h = '<div class="cm-cards">';
    pg.data.forEach(r => {
        const models = collectModels(r);
        const cardViewerId = 'cardviewer_' + r.chemical_id;
        h += '<div class="cm-card" id="card_' + r.chemical_id + '">';
        h += '<div class="cm-card-badge">' + statusBadge(r.status) + '</div>';
        h += '<div class="cm-card-header">';
        h += '<span class="cm-card-cas">' + esc(r.cas_number) + '</span>';
        h += '<span class="cm-card-name" title="' + esc(r.chemical_name || '') + '">' + esc(r.chemical_name || '—') + '</span>';
        h += '</div>';
        h += '<div class="cm-card-body">';
        if (r.formula) h += '<div class="cm-card-row"><i class="fas fa-atom"></i><span class="label">สูตร</span><span class="value" style="font-style:italic;color:#999">' + esc(r.formula) + '</span></div>';
        h += '<div class="cm-card-row"><i class="fas fa-box"></i><span class="label">Pkg</span><span class="value"><strong>' + (r.pkg_count || 0) + '</strong> รายการ (มีโมเดล <strong>' + (r.pkg_with_model || 0) + '</strong>)</span></div>';
        h += '<div class="cm-card-row"><i class="fas fa-cube"></i><span class="label">3D</span><span class="value"><strong>' + (r.model_count || 0) + '</strong> โมเดล</span></div>';

        // Packaging pills — clickable to show 3D in card
        if (r.packaging && r.packaging.length) {
            h += '<div class="cm-card-pkgs">';
            r.packaging.forEach((p, pi) => {
                const has = !!p.model_id;
                const lbl = (TYPES[p.container_type] || p.container_type) + (p.capacity ? ' ' + p.capacity + (p.capacity_unit || '') : '');
                if (has) {
                    const mIdx = models.findIndex(m => m.pkgId === p.id);
                    h += '<span class="cm-pill has-model" title="คลิกเพื่อดูโมเดล 3D: ' + esc(p.model_label || '') + '" onclick="event.stopPropagation();showCardViewer(' + r.chemical_id + ',' + mIdx + ')">';
                    h += '<i class="fas fa-check-circle"></i> ' + esc(lbl);
                    h += '</span>';
                } else {
                    h += '<span class="cm-pill no-model" title="ยังไม่มีโมเดล — คลิกเพื่อเพิ่ม" onclick="event.stopPropagation();openAddModalForPkg(' + r.chemical_id + ',\'' + escAttr(r.cas_number) + '\',\'' + escAttr(r.chemical_name || '') + '\',' + p.id + ',\'' + escAttr(p.container_type || '') + '\',\'' + escAttr(p.container_material || '') + '\')">';
                    h += '<i class="fas fa-times-circle"></i> ' + esc(lbl);
                    h += '</span>';
                }
            });
            h += '</div>';
        }

        // Inline 3D viewer container (hidden by default, opens when pill is clicked)
        h += '<div class="cm-card-viewer-wrap closed" id="' + cardViewerId + '"></div>';

        h += '</div>'; // end card-body
        h += '<div class="cm-card-footer">';
        h += '<button class="cm-card-btn btn-add" onclick="openAddModal(' + r.chemical_id + ',\'' + escAttr(r.cas_number) + '\',\'' + escAttr(r.chemical_name || '') + '\',\'' + encodePkgs(r.packaging) + '\')"><i class="fas fa-plus"></i> เพิ่มโมเดล</button>';
        h += '<button class="cm-card-btn" onclick="setView(\'table\');expandedRows.add(' + r.chemical_id + ');renderData()"><i class="fas fa-info-circle"></i> รายละเอียด</button>';
        h += '</div></div>';
    });
    h += '</div>';
    el.innerHTML = h;
}

// Show 3D model viewer inline in a card when a packaging pill is clicked
function showCardViewer(chemId, modelIdx) {
    const r = allData.find(d => d.chemical_id === chemId) || filteredData.find(d => d.chemical_id === chemId);
    if (!r) return;
    const models = collectModels(r);
    if (!models[modelIdx]) return;
    const m = models[modelIdx];
    const wrap = document.getElementById('cardviewer_' + chemId);
    if (!wrap) return;

    // If already open with same model, close it
    if (wrap.classList.contains('open') && wrap.dataset.activeIdx === String(modelIdx)) {
        wrap.classList.remove('open');
        wrap.classList.add('closed');
        setTimeout(() => { wrap.innerHTML = ''; }, 300);
        // Remove active from pills
        const card = document.getElementById('card_' + chemId);
        if (card) card.querySelectorAll('.cm-pill').forEach(p => p.style.outline = '');
        return;
    }

    wrap.dataset.activeIdx = String(modelIdx);
    const prov = getProviderInfo(m);
    let ih = '<div class="cm-card-viewer-bar">';
    ih += '<span class="cvb-badge ' + getProviderCls(m) + '">' + esc(prov.name) + '</span>';
    ih += '<span class="cvb-label">' + esc(m.label || 'โมเดล 3D') + (m.pkgLabel ? ' — ' + esc(m.pkgLabel) : '') + '</span>';
    ih += '<button class="cm-card-viewer-close" onclick="event.stopPropagation();closeCardViewer(' + chemId + ')" title="ปิด"><i class="fas fa-times"></i></button>';
    ih += '</div>';
    if (m.src === 'embed') {
        ih += '<iframe src="' + esc(m.url) + '" allowfullscreen allow="autoplay; fullscreen" style="width:100%;height:calc(100% - 32px);border:none;display:block"></iframe>';
    } else {
        ih += '<iframe src="/v1/pages/viewer3d.php?url=' + encodeURIComponent(m.url) + '" style="width:100%;height:calc(100% - 32px);border:none;display:block" allowfullscreen></iframe>';
    }

    wrap.innerHTML = ih;
    wrap.classList.remove('closed');
    wrap.classList.add('open');

    // Highlight the active pill
    const card = document.getElementById('card_' + chemId);
    if (card) {
        card.querySelectorAll('.cm-pill').forEach(p => p.style.outline = '');
        const pills = card.querySelectorAll('.cm-pill.has-model');
        // Find which pill corresponds to this model
        let pillCount = 0;
        if (r.packaging) r.packaging.forEach((p, pi) => {
            if (p.model_id) {
                const mIdx = models.findIndex(mm => mm.pkgId === p.id);
                if (mIdx === modelIdx && pills[pillCount]) {
                    pills[pillCount].style.outline = '2px solid var(--cm-primary)';
                    pills[pillCount].style.outlineOffset = '1px';
                }
                pillCount++;
            }
        });
    }
}

function closeCardViewer(chemId) {
    const wrap = document.getElementById('cardviewer_' + chemId);
    if (!wrap) return;
    wrap.classList.remove('open');
    wrap.classList.add('closed');
    setTimeout(() => { wrap.innerHTML = ''; }, 300);
    const card = document.getElementById('card_' + chemId);
    if (card) card.querySelectorAll('.cm-pill').forEach(p => p.style.outline = '');
}

// ─── COMPACT VIEW ───
function renderCompact(el, pg) {
    let h = '<div class="cm-compact">';
    pg.data.forEach((r, i) => {
        const miss = (r.packaging || []).filter(p => !p.model_id).length;
        h += '<div class="cm-compact-row" onclick="setView(\'table\');expandedRows.add(' + r.chemical_id + ');renderData()">';
        h += '<span class="idx">' + (pg.start + i + 1) + '</span>';
        h += '<span class="cas">' + esc(r.cas_number) + '</span>';
        h += '<span class="name">' + esc(r.chemical_name || '—') + '</span>';
        h += '<span class="formula">' + esc(r.formula || '') + '</span>';
        h += '<span class="counts">';
        if (r.pkg_count) h += '<span class="cnt cnt-pkg"><i class="fas fa-box" style="font-size:8px;margin-right:2px"></i>' + r.pkg_count + '</span>';
        if (r.model_count) h += '<span class="cnt cnt-model"><i class="fas fa-cube" style="font-size:8px;margin-right:2px"></i>' + r.model_count + '</span>';
        if (miss > 0) h += '<span class="cnt cnt-miss"><i class="fas fa-exclamation" style="font-size:8px;margin-right:2px"></i>' + miss + '</span>';
        h += '</span>';
        h += statusBadge(r.status);
        h += '</div>';
    });
    h += '</div>';
    el.innerHTML = h;
}

// ═══ Add Model Modal ═══
function openAddModal(chemId, cas, name, pkgsEncoded) {
    modalChemId = chemId;
    modalPkgId = null;
    uploadedFile = null;
    document.getElementById('modalChemInfo').innerHTML = '<span style="font-family:Courier New;color:var(--cm-primary);font-weight:700">' + esc(cas) + '</span> — ' + esc(name);
    document.getElementById('modalTitle').textContent = 'เพิ่มโมเดล 3D — ' + cas;

    // Decode packaging data
    let pkgs = [];
    try { pkgs = JSON.parse(decodeURIComponent(pkgsEncoded || '[]')); } catch (e) { pkgs = []; }

    // Populate packaging selector
    const sel = document.getElementById('modalPkgSelect');
    sel.innerHTML = '<option value="">— ไม่ระบุบรรจุภัณฑ์ (generic) —</option>';
    if (pkgs && pkgs.length) {
        pkgs.forEach(p => {
            const has = !!p.model_id;
            const lbl = (TYPES[p.container_type] || p.container_type) + (p.capacity ? ' ' + p.capacity + (p.capacity_unit || '') : '');
            sel.innerHTML += '<option value="' + p.id + '" data-type="' + (p.container_type || '') + '" data-mat="' + (p.container_material || '') + '">' + (has ? '✅' : '❌') + ' ' + esc(p.label || lbl) + '</option>';
        });
        document.getElementById('modalPkgField').style.display = 'block';
    } else {
        document.getElementById('modalPkgField').style.display = 'none';
    }

    // Reset form
    document.getElementById('modalEmbedUrl').value = '';
    document.getElementById('modalLabel').value = '';
    document.getElementById('modalDesc').value = '';
    document.getElementById('modalType').value = 'bottle';
    document.getElementById('modalMaterial').value = '';
    document.getElementById('modalEmbedPreview').innerHTML = '<div class="empty"><i class="fas fa-cube"></i><span>ตัวอย่างจะปรากฏที่นี่</span></div>';
    document.getElementById('modalProvider').style.display = 'none';
    document.getElementById('modalFileInfo').classList.remove('show');
    // Reset upload preview & delete UI
    hideUploadPreview();
    uploadPreviewRotating = true;
    cancelDeleteModel();
    document.getElementById('modalDeleteBtn').style.display = 'none';
    document.getElementById('btnTogglePreview').style.display = 'none';

    // Reset auto-config toggle (default on)
    document.getElementById('modalAutoConfig').checked = true;
    document.getElementById('autoConfigToggle').classList.add('active');
    document.getElementById('iframeConfigPanel').classList.add('show');

    // Reset library selection
    selectedLibModel = null;
    document.getElementById('libSelectedInfo').classList.remove('show');
    document.getElementById('libSearchInput').value = '';
    document.getElementById('libTypeFilter').value = '';
    document.getElementById('libSourceFilter').value = '';
    libModelsLoaded = false;

    setModalTab('embed');
    loadIframeConfigSilent();

    document.getElementById('addModelModal').classList.add('show');
}

function openAddModalForPkg(chemId, cas, name, pkgId, ptype, pmat) {
    openAddModal(chemId, cas, name, '[]');
    document.getElementById('modalPkgField').style.display = 'none';
    modalPkgId = pkgId;
    if (ptype) document.getElementById('modalType').value = ptype;
    if (pmat) document.getElementById('modalMaterial').value = pmat;
    document.getElementById('modalTitle').textContent = 'เพิ่มโมเดลให้บรรจุภัณฑ์ #' + pkgId;
    // Show delete button if this pkg already has a model
    const r = allData.find(d => d.chemical_id === chemId);
    if (r && r.packaging) {
        const pkg = r.packaging.find(p => p.id == pkgId);
        if (pkg && pkg.model_id) {
            document.getElementById('modalDeleteBtn').style.display = '';
        }
    }
}

function closeAddModal() {
    document.getElementById('addModelModal').classList.remove('show');
    // Cleanup upload preview to free memory
    hideUploadPreview();
    cancelDeleteModel();
}

function onPkgSelect() {
    const sel = document.getElementById('modalPkgSelect');
    modalPkgId = sel.value ? parseInt(sel.value) : null;
    const opt = sel.selectedOptions[0];
    if (opt?.dataset.type) document.getElementById('modalType').value = opt.dataset.type;
    if (opt?.dataset.mat) document.getElementById('modalMaterial').value = opt.dataset.mat;
    // Show/hide delete button based on whether this pkg has a model
    const delBtn = document.getElementById('modalDeleteBtn');
    cancelDeleteModel();
    if (modalPkgId && modalChemId) {
        const r = allData.find(d => d.chemical_id === modalChemId);
        if (r && r.packaging) {
            const pkg = r.packaging.find(p => p.id == modalPkgId);
            delBtn.style.display = (pkg && pkg.model_id) ? '' : 'none';
        } else { delBtn.style.display = 'none'; }
    } else { delBtn.style.display = 'none'; }
}

function setModalTab(tab) {
    document.getElementById('mtEmbed').classList.toggle('active', tab === 'embed');
    document.getElementById('mtUpload').classList.toggle('active', tab === 'upload');
    document.getElementById('mtLibrary').classList.toggle('active', tab === 'library');
    document.getElementById('panelEmbed').style.display = tab === 'embed' ? 'block' : 'none';
    document.getElementById('panelUpload').style.display = tab === 'upload' ? 'block' : 'none';
    document.getElementById('panelLibrary').style.display = tab === 'library' ? 'block' : 'none';
    if (tab === 'embed') loadIframeConfigSilent();
    if (tab === 'library') loadLibraryModels();
}

// ─── Iframe Config ───
async function loadIframeConfigSilent() {
    if (iframeConfigLoaded) return;
    try {
        const d = await apiFetch(API + '?action=iframe_config');
        if (d.success) {
            iframeConfig = d.data;
            iframeConfigLoaded = true;
            applyConfigToUI();
        }
    } catch (e) {}
}

function applyConfigToUI() {
    if (!iframeConfig) return;
    if (iframeConfig.kiri_bg_theme) document.getElementById('cfgBgTheme').value = iframeConfig.kiri_bg_theme;
    if (iframeConfig.kiri_auto_spin !== undefined) document.getElementById('cfgAutoSpin').value = iframeConfig.kiri_auto_spin;
    if (iframeConfig.default_params) document.getElementById('cfgExtraParams').value = iframeConfig.default_params;
    if (iframeConfig.default_attrs) document.getElementById('cfgAttrs').value = iframeConfig.default_attrs;
    if (iframeConfig.width) document.getElementById('cfgWidth').value = iframeConfig.width;
    if (iframeConfig.height) document.getElementById('cfgHeight').value = iframeConfig.height;
}

function readConfigFromUI() {
    return {
        kiri_bg_theme: document.getElementById('cfgBgTheme').value,
        kiri_auto_spin: document.getElementById('cfgAutoSpin').value,
        default_params: document.getElementById('cfgExtraParams').value.trim(),
        default_attrs: document.getElementById('cfgAttrs').value.trim(),
        width: parseInt(document.getElementById('cfgWidth').value) || 640,
        height: parseInt(document.getElementById('cfgHeight').value) || 480
    };
}

function onLocalConfigChange() {
    // Re-apply auto-config to current URL if auto-config is on
    const isAuto = document.getElementById('modalAutoConfig').checked;
    if (isAuto) {
        reapplyAutoConfig();
    }
}

function toggleAutoConfig() {
    setTimeout(() => {
        const isAuto = document.getElementById('modalAutoConfig').checked;
        const panel = document.getElementById('iframeConfigPanel');
        const toggle = document.getElementById('autoConfigToggle');
        panel.classList.toggle('show', isAuto);
        toggle.classList.toggle('active', isAuto);
        if (isAuto) {
            loadIframeConfigSilent();
            reapplyAutoConfig();
        }
    }, 10);
}

function reapplyAutoConfig() {
    const raw = document.getElementById('modalEmbedUrl').value.trim();
    if (!raw) return;
    // Check if it contains kiriengine URL
    const isIframe = raw.match(/<iframe[\s\S]*<\/iframe>/i);
    if (isIframe) {
        if (raw.indexOf('kiriengine') >= 0) {
            document.getElementById('modalEmbedUrl').value = autoConfigCode(raw);
        }
    } else {
        if (raw.indexOf('kiriengine') >= 0) {
            document.getElementById('modalEmbedUrl').value = autoConfigUrl(raw);
        }
    }
    previewEmbed();
}

function detectProvider(url) {
    if (!url) return '';
    const map = [
        ['kiriengine.app', 'Kiri Engine'],
        ['kiri.app', 'Kiri Engine'],
        ['sketchfab.com', 'Sketchfab'],
        ['youtube.com', 'YouTube'], ['youtu.be', 'YouTube'],
        ['matterport.com', 'Matterport'],
        ['google.com/maps', 'Google Maps'],
        ['vimeo.com', 'Vimeo'],
        ['poly.cam', 'Polycam'],
        ['lumalabs.ai', 'Luma AI'],
        ['p3d.in', 'P3D.in'],
    ];
    for (let i = 0; i < map.length; i++) {
        if (url.indexOf(map[i][0]) >= 0) return map[i][1];
    }
    try { return new URL(url).hostname; } catch (e) { return ''; }
}

function autoConfigUrl(url) {
    if (!url) return url;
    const cfg = readConfigFromUI();
    // Replace sharemodel → embed (Kiri Engine specific)
    url = url.replace(/\/sharemodel(\/|$)/gi, '/embed$1');
    const parts = url.split('?');
    const base = parts[0];
    const origQuery = parts[1] || '';
    // Parse original params
    const paramMap = {};
    if (origQuery) {
        origQuery.split('&').forEach(p => {
            const kv = p.split('=');
            if (kv[0]) paramMap[kv[0]] = kv[1] || '';
        });
    }
    // Override with config
    paramMap['bg_theme'] = cfg.kiri_bg_theme || 'transparent';
    paramMap['auto_spin_model'] = cfg.kiri_auto_spin || '1';
    if (cfg.default_params) {
        cfg.default_params.split('&').forEach(p => {
            const kv = p.split('=');
            if (kv[0]) paramMap[kv[0]] = kv[1] || '';
        });
    }
    const newParams = Object.keys(paramMap).map(k => k + '=' + paramMap[k]).join('&');
    return base + '?' + newParams;
}

function autoConfigCode(code) {
    if (!code) return code;
    return code.replace(/src\s*=\s*["']([^"']+)["']/gi, (match, url) => {
        return 'src="' + autoConfigUrl(url) + '"';
    });
}

// ─── Embed Input handlers ───
function onEmbedInput() {
    clearTimeout(embedPreviewTimer);
    const isAuto = document.getElementById('modalAutoConfig').checked;
    if (isAuto) {
        embedPreviewTimer = setTimeout(() => {
            reapplyAutoConfig();
        }, 600);
    } else {
        embedPreviewTimer = setTimeout(() => {
            previewEmbed();
        }, 500);
    }
}

function onEmbedPaste() {
    setTimeout(() => {
        const raw = document.getElementById('modalEmbedUrl').value.trim();
        if (!raw) return;
        const isAuto = document.getElementById('modalAutoConfig').checked;
        if (isAuto && raw.indexOf('kiriengine') >= 0) {
            const isIframe = raw.match(/<iframe[\s\S]*<\/iframe>/i);
            if (isIframe) {
                document.getElementById('modalEmbedUrl').value = autoConfigCode(raw);
            } else {
                document.getElementById('modalEmbedUrl').value = autoConfigUrl(raw);
            }
        }
        previewEmbed();
    }, 60);
}

// ─── Embed Preview ───
function previewEmbed() {
    clearTimeout(embedPreviewTimer);
    embedPreviewTimer = setTimeout(() => {
        let raw = document.getElementById('modalEmbedUrl').value.trim();
        if (!raw) {
            document.getElementById('modalEmbedPreview').innerHTML = '<div class="empty"><i class="fas fa-cube"></i><span>ตัวอย่างจะปรากฏที่นี่</span></div>';
            document.getElementById('modalProvider').style.display = 'none';
            return;
        }
        let url = raw;
        // Extract URL from iframe tag
        const iframeMatch = raw.match(/src=["']([^"']+)["']/i);
        if (iframeMatch) url = iframeMatch[1];
        // Detect provider
        const providerName = detectProvider(url);
        let providerClass = 'generic';
        if (providerName === 'Kiri Engine') providerClass = 'kiri';
        else if (providerName === 'Sketchfab') providerClass = 'sketchfab';

        document.getElementById('modalProvider').className = 'cm-provider ' + providerClass;
        document.getElementById('modalProvider').innerHTML = '<i class="fas fa-check-circle"></i> ' + esc(providerName || 'Embed');
        document.getElementById('modalProvider').style.display = 'inline-flex';

        // Build preview iframe - use auto-config attrs if available
        const isAuto = document.getElementById('modalAutoConfig').checked;
        const cfg = readConfigFromUI();
        if (raw.match(/<iframe[\s\S]*<\/iframe>/i)) {
            // Use pasted iframe code, fix size
            let fixed = raw.replace(/style\s*=\s*["'][^"']*["']/gi, '')
                          .replace(/<iframe/i, '<iframe style="width:100%;height:100%;border:none;"');
            document.getElementById('modalEmbedPreview').innerHTML = fixed;
        } else {
            let attrs = 'allowfullscreen allow="autoplay; fullscreen"';
            if (isAuto && cfg.default_attrs) attrs = cfg.default_attrs;
            document.getElementById('modalEmbedPreview').innerHTML = '<iframe src="' + esc(url) + '" style="width:100%;height:100%;border:none;" ' + attrs + '></iframe>';
        }
    }, 200);
}

// ─── File Upload ───
let uploadPreviewUrl = null;
let uploadPreviewRotating = true;

function onFileSelect(input) {
    if (!input.files.length) return;
    const f = input.files[0];
    const ext = f.name.split('.').pop().toLowerCase();
    if (!['glb', 'gltf', 'obj', 'fbx', 'stl'].includes(ext)) { showToast('ไฟล์ไม่รองรับ — รองรับเฉพาะ .glb .gltf .obj .fbx .stl', 'error'); return; }
    if (f.size > 100 * 1024 * 1024) { showToast('ไฟล์ใหญ่เกิน 100MB', 'error'); return; }
    uploadedFile = f;

    // File info
    document.getElementById('modalFileName').textContent = f.name;
    const sizeStr = f.size < 1048576 ? (f.size / 1024).toFixed(1) + ' KB' : (f.size / 1048576).toFixed(1) + ' MB';
    document.getElementById('modalFileSize').textContent = sizeStr;
    document.getElementById('modalFileExt').textContent = ext.toUpperCase();
    document.getElementById('modalFileInfo').classList.add('show');

    // Auto-suggest model label from filename
    const labelInput = document.getElementById('modalLabel');
    if (!labelInput.value.trim()) {
        labelInput.value = f.name.replace(/\.[^.]+$/, '').replace(/[_-]/g, ' ');
    }

    // 3D Preview for GLB/GLTF
    const canPreview = ['glb', 'gltf'].includes(ext);
    document.getElementById('btnTogglePreview').style.display = canPreview ? '' : 'none';
    if (canPreview) {
        showUploadPreview(f, sizeStr, ext);
    } else {
        hideUploadPreview();
    }
}

function showUploadPreview(file, sizeStr, ext) {
    // Revoke old URL
    if (uploadPreviewUrl) { URL.revokeObjectURL(uploadPreviewUrl); uploadPreviewUrl = null; }
    uploadPreviewUrl = URL.createObjectURL(file);

    const container = document.getElementById('uploadPreview');
    const loading = document.getElementById('uploadPreviewLoading');
    const stats = document.getElementById('uploadPreviewStats');
    loading.classList.remove('hidden');
    stats.style.display = 'none';
    container.classList.add('show');

    // Remove old model-viewer
    const old = container.querySelector('model-viewer');
    if (old) old.remove();

    // Create model-viewer
    const createViewer = () => {
        const mv = document.createElement('model-viewer');
        mv.id = 'uploadPreviewViewer';
        mv.setAttribute('src', uploadPreviewUrl);
        mv.setAttribute('alt', file.name);
        mv.setAttribute('camera-controls', '');
        mv.setAttribute('auto-rotate', '');
        mv.setAttribute('shadow-intensity', '1');
        mv.setAttribute('shadow-softness', '0.8');
        mv.setAttribute('environment-image', 'neutral');
        mv.setAttribute('tone-mapping', 'commerce');
        mv.setAttribute('exposure', '1.1');
        mv.setAttribute('interaction-prompt', 'auto');
        mv.setAttribute('touch-action', 'pan-y');
        mv.style.cssText = 'width:100%;height:100%;display:block';

        let loaded = false;
        const onLoad = () => {
            if (loaded) return;
            loaded = true;
            loading.classList.add('hidden');
            document.getElementById('upStatType').textContent = ext.toUpperCase();
            document.getElementById('upStatSize').textContent = sizeStr;
            stats.style.display = '';
        };
        mv.addEventListener('load', onLoad);
        mv.addEventListener('error', () => {
            loading.innerHTML = '<i class="fas fa-exclamation-triangle" style="font-size:24px;color:#e17055;margin-bottom:6px"></i><p style="color:#e17055">โหลด Preview ไม่สำเร็จ</p>';
        });
        setTimeout(() => { if (!loaded) onLoad(); }, 8000);
        container.appendChild(mv);
    };

    // Wait for model-viewer custom element
    if (customElements.get('model-viewer')) {
        createViewer();
    } else {
        customElements.whenDefined('model-viewer').then(createViewer).catch(() => {
            loading.innerHTML = '<p style="color:#888;font-size:11px">model-viewer ไม่พร้อมใช้งาน</p>';
        });
        setTimeout(() => {
            if (!customElements.get('model-viewer') && !container.querySelector('model-viewer')) {
                loading.innerHTML = '<p style="color:#888;font-size:11px">กำลังโหลด viewer...</p>';
            }
        }, 5000);
    }
}

function hideUploadPreview() {
    const container = document.getElementById('uploadPreview');
    container.classList.remove('show');
    const mv = container.querySelector('model-viewer');
    if (mv) { mv.setAttribute('src', ''); setTimeout(() => mv.remove(), 50); }
    if (uploadPreviewUrl) { URL.revokeObjectURL(uploadPreviewUrl); uploadPreviewUrl = null; }
}

function toggleUploadPreview() {
    const container = document.getElementById('uploadPreview');
    const btn = document.getElementById('btnTogglePreview');
    if (container.classList.contains('show')) {
        container.classList.remove('show');
        btn.innerHTML = '<i class="fas fa-eye"></i>';
    } else {
        container.classList.add('show');
        btn.innerHTML = '<i class="fas fa-eye-slash"></i>';
        // Re-create if needed
        if (!container.querySelector('model-viewer') && uploadedFile) {
            const ext = uploadedFile.name.split('.').pop().toLowerCase();
            const sizeStr = uploadedFile.size < 1048576 ? (uploadedFile.size / 1024).toFixed(1) + ' KB' : (uploadedFile.size / 1048576).toFixed(1) + ' MB';
            showUploadPreview(uploadedFile, sizeStr, ext);
        }
    }
}

function resetUploadPreviewCamera() {
    const mv = document.getElementById('uploadPreviewViewer');
    if (!mv) return;
    mv.cameraOrbit = 'auto auto auto';
    mv.cameraTarget = 'auto auto auto';
    mv.fieldOfView = 'auto';
    if (typeof mv.jumpCameraToGoal === 'function') mv.jumpCameraToGoal();
}

function toggleUploadPreviewRotate() {
    const mv = document.getElementById('uploadPreviewViewer');
    if (!mv) return;
    uploadPreviewRotating = !uploadPreviewRotating;
    if (uploadPreviewRotating) mv.setAttribute('auto-rotate', '');
    else mv.removeAttribute('auto-rotate');
    const btn = document.getElementById('btnPreviewRotate');
    btn.style.background = uploadPreviewRotating ? 'rgba(108,92,231,.3)' : '';
}

function clearFile() {
    uploadedFile = null;
    document.getElementById('modalFileInfo').classList.remove('show');
    document.getElementById('modalFileInput').value = '';
    hideUploadPreview();
    uploadPreviewRotating = true;
}

// ─── Delete Model ───
let deleteModelId = null;
let deleteModelLabel = '';

function showDeleteModel() {
    // Find the model linked to the currently selected packaging
    if (!modalPkgId && !modalChemId) { showToast('ไม่พบโมเดลที่จะลบ', 'warning'); return; }
    const r = allData.find(d => d.chemical_id === modalChemId);
    if (!r) return;
    // Find model for this pkg
    let model = null;
    if (modalPkgId && r.packaging) {
        const pkg = r.packaging.find(p => p.id == modalPkgId);
        if (pkg && pkg.model_id) {
            model = { id: pkg.model_id, label: pkg.model_label || 'โมเดล #' + pkg.model_id };
        }
    }
    if (!model) {
        // Try from collectModels
        const models = collectModels(r);
        if (models.length) model = { id: models[0].id, label: models[0].label };
    }
    if (!model) { showToast('ไม่พบโมเดลสำหรับบรรจุภัณฑ์นี้', 'warning'); return; }
    deleteModelId = model.id;
    deleteModelLabel = model.label;
    document.getElementById('deleteModelName').textContent = model.label;
    document.getElementById('deleteModelBar').classList.add('show');
}

function cancelDeleteModel() {
    deleteModelId = null;
    document.getElementById('deleteModelBar').classList.remove('show');
}

async function confirmDeleteModel() {
    if (!deleteModelId) return;
    const btn = document.getElementById('btnDeleteConfirm');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังลบ...';
    try {
        const d = await apiFetch(API + '?action=delete&id=' + deleteModelId, { method: 'DELETE' });
        if (!d.success) throw new Error(d.error || 'ลบไม่สำเร็จ');
        showToast('ลบโมเดล "' + deleteModelLabel + '" สำเร็จ');
        closeAddModal();
        loadData();
    } catch (e) {
        showToast(e.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-trash-alt"></i> ลบ';
        deleteModelId = null;
        document.getElementById('deleteModelBar').classList.remove('show');
    }
}

// Drag & drop
document.addEventListener('DOMContentLoaded', () => {
    const dz = document.getElementById('modalDropZone');
    if (!dz) return;
    dz.addEventListener('dragover', e => { e.preventDefault(); dz.classList.add('dragover'); });
    dz.addEventListener('dragleave', () => dz.classList.remove('dragover'));
    dz.addEventListener('drop', e => {
        e.preventDefault(); dz.classList.remove('dragover');
        const f = e.dataTransfer.files[0];
        if (f) { const inp = document.getElementById('modalFileInput'); const dt = new DataTransfer(); dt.items.add(f); inp.files = dt.files; onFileSelect(inp); }
    });
});

// ─── Save Model ───
// ═══ Library Model Browser ═══
let selectedLibModel = null;
let libModelsLoaded = false;
let libSearchTimer = null;
let allLibModels = [];

function onLibSearch() {
    clearTimeout(libSearchTimer);
    libSearchTimer = setTimeout(() => loadLibraryModels(), 300);
}

async function loadLibraryModels() {
    const grid = document.getElementById('libModelGrid');
    grid.innerHTML = '<div class="cm-lib-loading"><i class="fas fa-spinner fa-spin"></i><p>กำลังโหลด...</p></div>';
    try {
        const params = new URLSearchParams({ action: 'list', limit: 50 });
        const search = document.getElementById('libSearchInput').value.trim();
        const typeF = document.getElementById('libTypeFilter').value;
        const srcF = document.getElementById('libSourceFilter').value;
        if (search) params.set('search', search);
        if (typeF) params.set('container_type', typeF);
        const d = await apiFetch(API + '?' + params);
        if (!d.success) throw new Error(d.error);
        let models = d.data || [];
        // Filter by source type client-side
        if (srcF === 'embed') models = models.filter(m => m.source_type === 'embed');
        else if (srcF === 'glb') models = models.filter(m => m.source_type !== 'embed');
        allLibModels = models;
        renderLibraryGrid(models);
        libModelsLoaded = true;
    } catch (e) {
        grid.innerHTML = '<div class="cm-lib-empty"><i class="fas fa-exclamation-circle"></i><p>' + esc(e.message) + '</p></div>';
    }
}

function renderLibraryGrid(models) {
    const grid = document.getElementById('libModelGrid');
    if (!models.length) {
        grid.innerHTML = '<div class="cm-lib-empty"><i class="fas fa-cube"></i><p>ไม่พบโมเดล 3D</p></div>';
        return;
    }
    const TYPES = {bottle:'ขวด',vial:'Vial',flask:'Flask',jar:'Jar',can:'Can',drum:'Drum',bag:'Bag',box:'Box',ampoule:'Ampoule',cylinder:'Cylinder'};
    grid.innerHTML = models.map(m => {
        const isSelected = selectedLibModel && selectedLibModel.id === m.id;
        const isEmbed = m.source_type === 'embed';
        const previewUrl = isEmbed
            ? (m.embed_url || '')
            : (m.file_url ? '/v1/pages/viewer3d.php?src=' + encodeURIComponent(m.file_url) + '&embed=1&transparent=0' : '');
        const typeLbl = TYPES[m.container_type] || m.container_type || '';
        const matLbl = m.container_material ? m.container_material.charAt(0).toUpperCase() + m.container_material.slice(1) : '';
        const casLbl = m.cas_number || '';
        return `<div class="cm-lib-card${isSelected ? ' selected' : ''}" onclick="selectLibraryModel(${m.id})" data-id="${m.id}">
            <div class="cm-lib-card-preview">
                ${previewUrl
                    ? `<iframe src="${esc(previewUrl)}" loading="lazy" allow="autoplay" allowfullscreen></iframe>`
                    : `<div class="no-pv"><i class="fas fa-cube"></i></div>`}
                <span class="src-badge ${isEmbed ? 'embed' : 'glb'}">${isEmbed ? 'EMBED' : 'GLB'}</span>
            </div>
            <div class="cm-lib-card-body">
                <div class="cm-lib-card-name">${esc(m.label || 'Untitled')}</div>
                <div class="cm-lib-card-meta">
                    ${typeLbl ? `<span class="type">${esc(typeLbl)}</span>` : ''}
                    ${matLbl ? `<span>${esc(matLbl)}</span>` : ''}
                    ${casLbl ? `<span>CAS:${esc(casLbl)}</span>` : ''}
                </div>
            </div>
        </div>`;
    }).join('');
}

function selectLibraryModel(id) {
    const m = allLibModels.find(x => x.id === id);
    if (!m) return;
    // Toggle selection
    if (selectedLibModel && selectedLibModel.id === id) {
        clearLibrarySelection();
        return;
    }
    selectedLibModel = m;
    // Highlight card
    document.querySelectorAll('.cm-lib-card').forEach(c => c.classList.toggle('selected', parseInt(c.dataset.id) === id));
    // Show selected info bar
    const info = document.getElementById('libSelectedInfo');
    const TYPES = {bottle:'ขวด',vial:'Vial',flask:'Flask',jar:'Jar',can:'Can',drum:'Drum',bag:'Bag',box:'Box',ampoule:'Ampoule',cylinder:'Cylinder'};
    document.getElementById('libSelName').textContent = m.label || 'Untitled';
    document.getElementById('libSelMeta').textContent = (TYPES[m.container_type] || m.container_type || '') + (m.container_material ? ' · ' + m.container_material : '') + ' · ' + (m.source_type === 'embed' ? 'Embed' : 'GLB');
    info.classList.add('show');
    // Auto-fill step 3 fields from the selected model
    if (m.label && !document.getElementById('modalLabel').value) document.getElementById('modalLabel').value = m.label;
    if (m.container_type) document.getElementById('modalType').value = m.container_type;
    if (m.container_material) document.getElementById('modalMaterial').value = m.container_material;
    if (m.description && !document.getElementById('modalDesc').value) document.getElementById('modalDesc').value = m.description || '';
}

function clearLibrarySelection() {
    selectedLibModel = null;
    document.querySelectorAll('.cm-lib-card').forEach(c => c.classList.remove('selected'));
    document.getElementById('libSelectedInfo').classList.remove('show');
}

async function saveModel() {
    const label = document.getElementById('modalLabel').value.trim();
    const type = document.getElementById('modalType').value;
    if (!label) { showToast('กรุณาระบุชื่อโมเดล', 'error'); return; }

    const btn = document.getElementById('modalSaveBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังบันทึก...';

    try {
        const isLibrary = document.getElementById('panelLibrary').style.display !== 'none';
        const isEmbed = !isLibrary && document.getElementById('panelEmbed').style.display !== 'none';

        if (isLibrary) {
            // Library mode — link existing model to this packaging
            if (!selectedLibModel) { showToast('กรุณาเลือกโมเดลจากระบบ', 'error'); btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> บันทึกโมเดล'; return; }
            const m = selectedLibModel;
            const d = await apiFetch(API + '?action=save', {
                method: 'POST',
                body: JSON.stringify({
                    label: label || m.label,
                    container_type: type,
                    container_material: document.getElementById('modalMaterial').value || m.container_material || null,
                    description: document.getElementById('modalDesc').value || m.description || null,
                    chemical_id: modalChemId,
                    packaging_id: modalPkgId || null,
                    source_type: m.source_type || 'embed',
                    embed_url: m.embed_url || null,
                    embed_code: m.embed_code || null,
                    embed_provider: m.embed_provider || null,
                    file_path: m.file_path || null,
                    file_url: m.file_url || null,
                    original_name: m.original_name || null,
                    linked_model_id: m.id
                })
            });
            if (!d.success) throw new Error(d.error);
            showToast('เชื่อมโยงโมเดลจากระบบสำเร็จ');
        } else if (isEmbed) {
            // Embed mode
            let raw = document.getElementById('modalEmbedUrl').value.trim();
            if (!raw) { showToast('กรุณาระบุ Embed URL', 'error'); btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> บันทึกโมเดล'; return; }

            const isAuto = document.getElementById('modalAutoConfig').checked;
            const cfg = readConfigFromUI();
            let url = raw;
            const iframeMatch = raw.match(/src=["']([^"']+)["']/i);
            if (iframeMatch) url = iframeMatch[1];

            // Final auto-config before save
            if (isAuto && url.indexOf('kiriengine') >= 0) {
                url = autoConfigUrl(url);
            }

            const provider = detectProvider(url) || 'generic';
            let providerKey = 'generic';
            if (provider === 'Kiri Engine') providerKey = 'kiri';
            else if (provider === 'Sketchfab') providerKey = 'sketchfab';

            // Generate proper embed code with config
            let embedCode = raw;
            if (isAuto && raw.indexOf('kiriengine') >= 0) {
                if (raw.match(/<iframe[\s\S]*<\/iframe>/i)) {
                    embedCode = autoConfigCode(raw);
                } else {
                    // Generate iframe code from URL
                    const attrs = cfg.default_attrs
                        ? cfg.default_attrs + ' width="' + (cfg.width || 640) + '" height="' + (cfg.height || 480) + '"'
                        : 'allowfullscreen style="width:100%;height:100%;border:none;"';
                    embedCode = '<iframe src="' + url + '" ' + attrs + '></iframe>';
                }
            }

            const d = await apiFetch(API + '?action=save', {
                method: 'POST',
                body: JSON.stringify({
                    label: label,
                    container_type: type,
                    container_material: document.getElementById('modalMaterial').value || null,
                    description: document.getElementById('modalDesc').value || null,
                    chemical_id: modalChemId,
                    packaging_id: modalPkgId || null,
                    source_type: 'embed',
                    embed_url: url,
                    embed_code: embedCode,
                    embed_provider: providerKey
                })
            });
            if (!d.success) throw new Error(d.error);
            showToast('บันทึกโมเดล Embed สำเร็จ');
        } else {
            // Upload mode
            if (!uploadedFile) { showToast('กรุณาเลือกไฟล์', 'error'); btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> บันทึกโมเดล'; return; }
            // Step 1: Upload file via XHR (handles multipart/form-data correctly)
            const upData = await new Promise((resolve, reject) => {
                const fd = new FormData();
                fd.append('model_file', uploadedFile);
                const xhr = new XMLHttpRequest();
                xhr.open('POST', API + '?action=upload');
                const tkn = localStorage.getItem('auth_token') || '';
                if (tkn) xhr.setRequestHeader('Authorization', 'Bearer ' + tkn);
                xhr.onload = function() {
                    try {
                        const d = JSON.parse(xhr.responseText);
                        if (d.success) resolve(d.data);
                        else reject(new Error(d.error || 'Upload failed'));
                    } catch(e) { reject(new Error('Invalid server response')); }
                };
                xhr.onerror = function() { reject(new Error('Network error during upload')); };
                xhr.send(fd);
            });
            // Step 2: Save record
            const d = await apiFetch(API + '?action=save', {
                method: 'POST',
                body: JSON.stringify({
                    label: label,
                    container_type: type,
                    container_material: document.getElementById('modalMaterial').value || null,
                    description: document.getElementById('modalDesc').value || null,
                    chemical_id: modalChemId,
                    packaging_id: modalPkgId || null,
                    source_type: 'upload',
                    file_path: upData.file_path,
                    file_url: upData.file_url,
                    original_name: upData.original_name,
                    mime_type: upData.mime_type,
                    extension: upData.extension,
                    file_size: upData.file_size
                })
            });
            if (!d.success) throw new Error(d.error);
            showToast('อัปโหลดและบันทึกโมเดลสำเร็จ');
        }
        closeAddModal();
        loadData();
    } catch (e) {
        showToast(e.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> บันทึกโมเดล';
    }
}

// ═══ Navigation ═══
function goViewModels(cas) { window.location.href = '/v1/pages/models3d.php?view=cas&search=' + encodeURIComponent(cas); }

// ═══ Keyboard ═══
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        // Close fullscreen viewers first
        const fullscreenViewer = document.querySelector('.cm-hero-viewer[style*="position:fixed"]');
        if (fullscreenViewer) { toggleDetailFullscreen(fullscreenViewer.id); return; }
        closeAddModal();
    }
});

// ═══ Initialize ═══
loadData();
</script>

<?php Layout::endContent(); ?>
</body>
</html>
