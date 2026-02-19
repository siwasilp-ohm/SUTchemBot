<?php
/**
 * Chemical Stock Management Page — Pro Edition (ขวดสารเคมีในคลัง)
 * 
 * Now powered by Containers API — same data source as containers.php
 * 
 * Role-based views:
 *   admin       → Full CRUD, import/export, see ALL
 *   ceo         → Read-only, see ALL, export
 *   lab_manager → See own + team, manage own
 *   user        → See/manage own only
 *
 * Views: Table / Grid / Compact / Analytics
 */
require_once __DIR__ . '/../includes/layout.php';
$user = Auth::getCurrentUser();
if (!$user) { header('Location: /v1/pages/login.php'); exit; }
$lang    = I18n::getCurrentLang();
$role    = $user['role_name'];
$uid     = (int)$user['id'];
$isAdmin = $role === 'admin';
$isCeo   = $role === 'ceo';
$isLab   = $role === 'lab_manager';
$canEdit = in_array($role, ['admin','lab_manager','user']);
$canSeeAll = $isAdmin || $isCeo;
$userDisplayName = $user['full_name_th'] ?? trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
$userInitial = mb_substr(preg_replace('/^(นาย|นางสาว|นาง|ดร\.)\s*/u', '', $userDisplayName), 0, 1, 'UTF-8');
Layout::head($lang === 'th' ? 'คลังสารเคมี — ขวดสาร' : 'Chemical Stock');
?>
<style>
:root{--stk-r:14px;--stk-rs:10px;--stk-sh:0 1px 6px rgba(0,0,0,.06);--stk-shm:0 4px 20px rgba(0,0,0,.08);--sg:#16a34a;--sy:#d97706;--sr:#dc2626;--sb:#2563eb;--sp:#7c3aed;--st:#0d9488}

/* ── Hero Banner ── */
.stk-src{display:inline-flex;align-items:center;gap:3px;font-size:8px;font-weight:700;padding:1px 6px;border-radius:4px;letter-spacing:.3px;text-transform:uppercase;vertical-align:middle}
.stk-src-container{background:#dbeafe;color:#2563eb}
.stk-src-stock{background:#fef3c7;color:#92400e}

.stk-hero{background:linear-gradient(135deg,#065f46 0%,#0d9488 50%,#14b8a6 100%);border-radius:var(--stk-r);padding:24px 28px;color:#fff;display:flex;align-items:center;gap:20px;margin-bottom:20px;position:relative;overflow:hidden}
.stk-hero::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat}
.stk-hero-ic{width:56px;height:56px;border-radius:16px;background:rgba(255,255,255,.18);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0}
.stk-hero-info h2{font-size:20px;font-weight:800;margin:0 0 3px;position:relative}
.stk-hero-info p{font-size:12px;opacity:.85;margin:0;position:relative}
.stk-hero-meta{margin-left:auto;display:flex;gap:20px;flex-shrink:0}
.stk-hero-c{text-align:center;position:relative}
.stk-hero-c .v{font-size:26px;font-weight:900;line-height:1}
.stk-hero-c .lb{font-size:10px;opacity:.7;margin-top:2px;text-transform:uppercase;letter-spacing:.5px}

/* ── Stats Row ── */
.stk-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:10px;margin-bottom:18px}
.stk-stat{background:#fff;border-radius:var(--stk-rs);padding:14px 16px;display:flex;align-items:center;gap:12px;box-shadow:var(--stk-sh);border:1px solid var(--border);transition:all .15s;cursor:pointer}
.stk-stat:hover{transform:translateY(-2px);box-shadow:var(--stk-shm)}
.stk-stat.af{border-color:var(--accent);background:#f0fdf4}
.stk-si{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0}
.stk-sv{font-size:20px;font-weight:800;color:var(--c1);line-height:1}
.stk-sl{font-size:10px;color:var(--c3);margin-top:2px;text-transform:uppercase;letter-spacing:.3px}

/* ── Tabs ── */
.stk-tabs{display:inline-flex;background:#f1f5f9;border-radius:var(--stk-rs);padding:3px}
.stk-tab{padding:8px 20px;font-size:12px;font-weight:600;color:var(--c3);border-radius:8px;cursor:pointer;border:none;background:none;font-family:inherit;transition:all .15s;display:flex;align-items:center;gap:6px}
.stk-tab:hover{color:var(--c1)}
.stk-tab.active{background:#fff;color:var(--accent);box-shadow:0 1px 4px rgba(0,0,0,.08)}
.stk-tab .bg{font-size:9px;padding:2px 7px;border-radius:10px;font-weight:700;background:#e2e8f0;color:var(--c3)}
.stk-tab.active .bg{background:var(--accent);color:#fff}

/* ── My Banner ── */
.stk-my{background:linear-gradient(135deg,#1e40af,#3b82f6);border-radius:var(--stk-r);padding:16px 20px;color:#fff;display:flex;align-items:center;gap:14px;margin-bottom:16px}
.stk-my-av{width:42px;height:42px;border-radius:12px;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:18px}
.stk-my h3{font-size:14px;font-weight:700;margin:0}
.stk-my p{font-size:11px;opacity:.8;margin:2px 0 0}

/* ── Toolbar ── */
.stk-toolbar{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:14px}
.stk-search{flex:1;min-width:220px;position:relative}
.stk-search input{width:100%;padding:9px 14px 9px 38px;border:1.5px solid var(--border);border-radius:var(--stk-rs);font-size:13px;background:#fff;color:var(--c1);transition:border .15s}
.stk-search input:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(26,138,92,.1)}
.stk-search i{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--c3);font-size:13px}
.stk-btn{padding:8px 16px;border:none;border-radius:var(--stk-rs);font-size:12px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;font-family:inherit;transition:all .12s;white-space:nowrap;text-decoration:none}
.stk-btn-p{background:var(--accent);color:#fff}.stk-btn-p:hover{filter:brightness(1.08)}
.stk-btn-o{background:#fff;color:var(--accent);border:1.5px solid var(--accent)}.stk-btn-o:hover{background:var(--accent);color:#fff}
.stk-btn-d{background:#dc2626;color:#fff}.stk-btn-d:hover{background:#b91c1c}
.stk-btn-g{background:transparent;color:var(--c3);border:1.5px solid var(--border)}.stk-btn-g:hover{border-color:var(--accent);color:var(--accent)}
.stk-btn-s{padding:5px 10px;font-size:11px}

/* ── View Switcher ── */
.stk-vw{display:flex;border:1.5px solid var(--border);border-radius:var(--stk-rs);overflow:hidden}
.stk-vw button{padding:7px 11px;border:none;background:#fff;color:var(--c3);cursor:pointer;font-size:12px;transition:all .12s;display:flex;align-items:center;gap:4px}
.stk-vw button+button{border-left:1px solid var(--border)}
.stk-vw button.active{background:var(--accent);color:#fff}
.stk-vw button:hover:not(.active){background:#f8fafc}

/* ── Filter Panel ── */
.stk-fp{max-height:0;overflow:hidden;transition:max-height .25s ease,margin .25s ease,padding .25s ease;background:#fff;border:1.5px solid transparent;border-radius:var(--stk-r);margin-bottom:0}
.stk-fp.show{max-height:300px;border-color:var(--border);padding:16px;margin-bottom:14px;box-shadow:var(--stk-sh)}
.stk-fg2{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px}
.stk-fl label{font-size:10px;font-weight:700;color:var(--c3);display:block;margin-bottom:3px;text-transform:uppercase;letter-spacing:.5px}
.stk-fl select{width:100%;padding:7px 10px;border:1.5px solid var(--border);border-radius:8px;font-size:12px;background:#fff;color:var(--c1)}
.stk-fl select:focus{outline:none;border-color:var(--accent)}
.stk-fa{display:flex;gap:8px;margin-top:10px;justify-content:flex-end}

/* ── Table View ── */
.stk-tw{overflow-x:auto;border-radius:var(--stk-r);border:1px solid var(--border);background:#fff;box-shadow:var(--stk-sh)}
.stk-t{width:100%;border-collapse:collapse;font-size:12px}
.stk-t th{background:#f8fafc;padding:10px 12px;text-align:left;font-weight:700;color:var(--c3);font-size:10px;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid var(--border);white-space:nowrap;cursor:pointer;user-select:none;transition:color .12s;position:sticky;top:0;z-index:1}
.stk-t th:hover{color:var(--accent)}
.stk-t td{padding:10px 12px;border-bottom:1px solid #f1f5f9;color:var(--c1);vertical-align:middle}
.stk-t tbody tr{transition:background .1s;cursor:pointer}
.stk-t tbody tr:hover{background:#f0fdf4}
.stk-t tbody tr.me{background:#eff6ff}
.stk-t tbody tr.me:hover{background:#dbeafe}

/* ── Grid/Card View ── */
.stk-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(290px,1fr));gap:12px}
.stk-card{background:#fff;border:1.5px solid var(--border);border-radius:var(--stk-r);overflow:hidden;transition:all .18s;cursor:pointer;position:relative}
.stk-card:hover{border-color:var(--accent);box-shadow:var(--stk-shm);transform:translateY(-2px)}
.stk-card.me{border-left:3px solid var(--sb)}
.stk-card-hd{display:flex;align-items:flex-start;gap:10px;padding:16px 16px 0}
.stk-card-ic{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
.stk-card-nm{font-size:13px;font-weight:700;color:var(--c1);line-height:1.3;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.stk-card-cd{font-size:10px;color:var(--c2);font-family:'Courier New',monospace;margin-top:2px;background:#f1f5f9;padding:1px 6px;border-radius:3px;display:inline-block;letter-spacing:0.3px}
.stk-card-bd{padding:10px 16px 16px}
.stk-card-tg{display:flex;flex-wrap:wrap;gap:4px;margin-bottom:8px}
.stk-card-tag{font-size:9px;padding:2px 7px;border-radius:6px;font-weight:600}
.stk-card-bar{height:5px;border-radius:3px;background:#e2e8f0;overflow:hidden;margin-bottom:6px}
.stk-card-bf{height:100%;border-radius:3px;transition:width .3s}
.stk-card-ft{display:flex;justify-content:space-between;align-items:center;font-size:10px;color:var(--c3)}
.stk-card-row{display:flex;align-items:center;gap:6px;font-size:11px;color:var(--c2);margin-top:4px}
.stk-card-row i{width:14px;text-align:center;color:var(--c3);font-size:10px}
.stk-av{width:20px;height:20px;border-radius:50%;background:var(--accent);color:#fff;display:inline-flex;align-items:center;justify-content:center;font-size:8px;font-weight:700;margin-right:4px;vertical-align:middle}
.stk-3d-badge{position:absolute;top:10px;right:10px;background:linear-gradient(135deg,#6C5CE7,#a855f7);color:#fff;font-size:9px;padding:3px 8px;border-radius:6px;font-weight:700;display:flex;align-items:center;gap:4px}

/* ── Compact View ── */
.stk-compact{display:flex;flex-direction:column;gap:4px}
.stk-cr{display:flex;align-items:center;gap:10px;padding:8px 14px;background:#fff;border-radius:8px;border:1px solid var(--border);cursor:pointer;transition:all .1s;font-size:12px}
.stk-cr:hover{background:#f0fdf4;border-color:var(--accent)}
.stk-cr.me{border-left:3px solid var(--sb)}
.stk-cn{flex:1;font-weight:600;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.stk-cc{color:var(--c2);font-size:10px;width:100px;flex-shrink:0;font-family:'Courier New',monospace;letter-spacing:0.3px}
.stk-cb{width:50px;height:4px;border-radius:2px;background:#e2e8f0;overflow:hidden;flex-shrink:0}
.stk-cb div{height:100%;border-radius:2px}
.stk-cp{font-weight:700;font-size:11px;width:35px;text-align:right;flex-shrink:0}
.stk-co{font-size:10px;color:var(--c3);width:80px;flex-shrink:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}

/* ── Analytics View ── */
.stk-an{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:14px}
.stk-ac{background:#fff;border-radius:var(--stk-r);border:1px solid var(--border);padding:18px;box-shadow:var(--stk-sh)}
.stk-at{font-size:12px;font-weight:700;color:var(--c1);margin-bottom:12px;display:flex;align-items:center;gap:6px}
.stk-at i{color:var(--accent)}
.stk-bc{display:flex;flex-direction:column;gap:6px}
.stk-br{display:flex;align-items:center;gap:8px;font-size:11px}
.stk-bl{width:100px;text-align:right;color:var(--c3);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex-shrink:0}
.stk-bt{flex:1;height:18px;background:#f1f5f9;border-radius:4px;overflow:hidden;position:relative}
.stk-bf{height:100%;border-radius:4px;display:flex;align-items:center;padding-left:6px;font-size:9px;font-weight:700;color:#fff;transition:width .4s}
.stk-bv{font-weight:700;color:var(--c1);width:40px;flex-shrink:0}
.stk-dn{display:flex;align-items:center;gap:20px;justify-content:center}
.stk-dl2{display:flex;flex-direction:column;gap:6px}
.stk-di{display:flex;align-items:center;gap:6px;font-size:11px}
.stk-dd{width:10px;height:10px;border-radius:3px;flex-shrink:0}

/* ── Badges ── */
.stk-badge{font-size:9px;padding:3px 8px;border-radius:6px;font-weight:700;text-transform:uppercase;letter-spacing:.3px;display:inline-block}
.stk-badge-active{background:#dcfce7;color:#15803d}
.stk-badge-low{background:#fef9c3;color:#a16207}
.stk-badge-empty{background:#fee2e2;color:#dc2626}
.stk-badge-expired{background:#fce7f3;color:#be185d}
.stk-badge-quarantined{background:#fef3c7;color:#d97706}
.stk-badge-disposed{background:#f1f5f9;color:#64748b}
.bar-ok{background:linear-gradient(90deg,#22c55e,#16a34a)}
.bar-mid{background:linear-gradient(90deg,#eab308,#f59e0b)}
.bar-low{background:linear-gradient(90deg,#ef4444,#dc2626)}

/* ── Type Icons ── */
.type-icon{width:28px;height:28px;border-radius:7px;display:inline-flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0}
.type-bottle{background:#dbeafe;color:#2563eb}
.type-vial{background:#ede9fe;color:#7c3aed}
.type-flask{background:#d1fae5;color:#059669}
.type-canister{background:#fed7aa;color:#ea580c}
.type-cylinder{background:#fecdd3;color:#e11d48}
.type-ampoule{background:#e0e7ff;color:#4338ca}
.type-bag{background:#f5f5f4;color:#78716c}
.type-other{background:#f1f5f9;color:#64748b}

/* ── GHS Pictograms ── */
.ghs-row{display:flex;gap:6px;flex-wrap:wrap;margin-top:8px}
.ghs-diamond{width:36px;height:36px;position:relative;cursor:pointer;transition:transform .15s}
.ghs-diamond:hover{transform:scale(1.15)}
.ghs-diamond-inner{position:absolute;inset:3px;transform:rotate(45deg);border:2px solid #dc2626;border-radius:3px;display:flex;align-items:center;justify-content:center;font-size:13px}
.ghs-diamond-inner i{transform:rotate(-45deg)}
.ghs-compressed_gas .ghs-diamond-inner{background:#fff3cd;border-color:#d97706;color:#92400e}
.ghs-flammable .ghs-diamond-inner{background:#fee2e2;border-color:#dc2626;color:#dc2626}
.ghs-oxidizing .ghs-diamond-inner{background:#fef3c7;border-color:#d97706;color:#92400e}
.ghs-toxic .ghs-diamond-inner{background:#fee2e2;border-color:#dc2626;color:#991b1b}
.ghs-corrosive .ghs-diamond-inner{background:#f3e8ff;border-color:#7c3aed;color:#6d28d9}
.ghs-irritant .ghs-diamond-inner{background:#fef3c7;border-color:#f59e0b;color:#92400e}
.ghs-environmental .ghs-diamond-inner{background:#dcfce7;border-color:#16a34a;color:#15803d}
.ghs-health_hazard .ghs-diamond-inner{background:#fee2e2;border-color:#dc2626;color:#991b1b}
.ghs-explosive .ghs-diamond-inner{background:#fef3c7;border-color:#ea580c;color:#c2410c}
.ghs-tooltip{position:absolute;bottom:calc(100% + 6px);left:50%;transform:translateX(-50%);background:#1a1a2e;color:#fff;padding:4px 8px;border-radius:5px;font-size:9px;white-space:nowrap;pointer-events:none;opacity:0;transition:opacity .15s;z-index:10}
.ghs-diamond:hover .ghs-tooltip{opacity:1}
.ghs-tooltip::after{content:'';position:absolute;top:100%;left:50%;transform:translateX(-50%);border:4px solid transparent;border-top-color:#1a1a2e}

/* ── Signal Word ── */
.signal-danger{background:linear-gradient(135deg,#dc2626,#b91c1c);color:#fff;padding:3px 10px;border-radius:6px;font-size:10px;font-weight:800;letter-spacing:.5px;text-transform:uppercase;display:inline-flex;align-items:center;gap:4px;animation:signal-pulse 2s infinite}
.signal-warning{background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;padding:3px 10px;border-radius:6px;font-size:10px;font-weight:800;letter-spacing:.5px;text-transform:uppercase;display:inline-flex;align-items:center;gap:4px}
@keyframes signal-pulse{0%,100%{box-shadow:0 0 0 0 rgba(220,38,38,.3)}50%{box-shadow:0 0 0 6px rgba(220,38,38,0)}}

/* ── Fluid Level ── */
.stk-fluid{width:44px;height:70px;border:2px solid var(--accent);border-radius:8px;position:relative;overflow:hidden;background:#f0fdf4;flex-shrink:0}
.stk-fluid-fill{position:absolute;bottom:0;left:0;right:0;transition:height .5s;border-radius:0 0 5px 5px}
.stk-fluid-pct{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;color:#065f46;text-shadow:0 0 4px rgba(255,255,255,.8)}

/* ── Chemical Info Card ── */
.stk-chem-card{background:linear-gradient(135deg,#f8faf8,#f0fdf4);border:1.5px solid #bbf7d0;border-radius:14px;padding:18px;margin-bottom:16px}
.stk-chem-header{display:flex;gap:14px;align-items:flex-start}
.stk-chem-body{flex:1;min-width:0}
.stk-chem-name{font-size:18px;font-weight:800;color:var(--c1);margin-bottom:2px;line-height:1.25}
.stk-chem-sub{font-size:12px;color:var(--c3);margin-bottom:2px;display:flex;align-items:center;gap:6px}
.stk-chem-sub b{color:var(--c1);font-weight:600}
.stk-chem-tags{display:flex;gap:4px;flex-wrap:wrap;margin-top:8px}
.stk-chem-props{display:grid;grid-template-columns:repeat(auto-fit,minmax(100px,1fr));gap:8px;margin-top:12px;padding-top:12px;border-top:1px solid #e2e8f0}
.stk-chem-prop{text-align:center;padding:6px 4px;background:#fff;border-radius:8px;border:1px solid #e2e8f0}
.stk-chem-prop .prop-v{font-size:14px;font-weight:800;color:var(--c1)}
.stk-chem-prop .prop-l{font-size:9px;color:var(--c3);text-transform:uppercase;letter-spacing:.3px;margin-top:1px}

/* ── 3D Viewer ── */
.stk-3d-viewer{background:linear-gradient(135deg,#0c0c1d 0%,#1a1a3e 100%);border-radius:14px;overflow:hidden;height:280px;margin-bottom:16px;position:relative;border:1px solid rgba(108,92,231,.25)}
.stk-3d-viewer iframe{width:100%;height:100%;border:none}
.stk-3d-viewer .no-model{display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;color:#555;gap:8px}
.stk-3d-viewer .no-model i{font-size:40px;opacity:.3;color:#6C5CE7}
.stk-3d-viewer .no-model p{font-size:12px;color:#888}
.stk-3d-actions{position:absolute;bottom:12px;left:12px;right:12px;display:flex;gap:6px;justify-content:flex-end}
.stk-3d-actions button,.stk-3d-actions a{padding:7px 14px;border:none;border-radius:8px;background:rgba(108,92,231,.85);color:#fff;font-size:11px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:5px;transition:all .18s;text-decoration:none;backdrop-filter:blur(4px)}
.stk-3d-actions button:hover,.stk-3d-actions a:hover{background:#6C5CE7;transform:translateY(-1px)}
.stk-3d-actions .ar-btn{background:linear-gradient(135deg,#0d9488,#14b8a6);box-shadow:0 2px 10px rgba(13,148,136,.4)}
.stk-3d-actions .ar-btn:hover{background:linear-gradient(135deg,#0f766e,#0d9488)}
.stk-3d-label{position:absolute;top:12px;left:12px;display:flex;gap:6px;align-items:center}
.stk-3d-label span{background:rgba(0,0,0,.5);backdrop-filter:blur(4px);color:#fff;font-size:10px;padding:4px 10px;border-radius:6px;font-weight:600;display:flex;align-items:center;gap:4px}

/* ── History Timeline ── */
.stk-tl{position:relative;padding-left:20px}
.stk-tl::before{content:'';position:absolute;left:6px;top:4px;bottom:4px;width:2px;background:#e2e8f0;border-radius:2px}
.stk-tl-item{position:relative;margin-bottom:14px;padding-left:12px}
.stk-tl-item::before{content:'';position:absolute;left:-18px;top:5px;width:10px;height:10px;border-radius:50%;border:2px solid var(--accent);background:#fff}
.stk-tl-item.created::before{background:#22c55e;border-color:#22c55e}
.stk-tl-item.used::before{background:#eab308;border-color:#eab308}
.stk-tl-item.moved::before{background:#3b82f6;border-color:#3b82f6}
.stk-tl-item.disposed::before{background:#ef4444;border-color:#ef4444}
.stk-tl-act{font-size:12px;font-weight:600;color:var(--c1);text-transform:capitalize}
.stk-tl-det{font-size:11px;color:var(--c3);margin-top:2px}
.stk-tl-time{font-size:10px;color:var(--c3);margin-top:1px}

/* ── Pagination ── */
.stk-pager{display:flex;align-items:center;justify-content:center;gap:3px;margin-top:14px;flex-wrap:wrap}
.stk-pager button{width:32px;height:32px;border:1px solid var(--border);border-radius:8px;background:#fff;color:var(--c1);cursor:pointer;font-size:11px;font-weight:600;transition:all .12s;display:flex;align-items:center;justify-content:center}
.stk-pager button:hover:not(:disabled){border-color:var(--accent);color:var(--accent)}
.stk-pager button.active{background:var(--accent);color:#fff;border-color:var(--accent)}
.stk-pager button:disabled{opacity:.3;cursor:default}
.stk-pager-info{font-size:11px;color:var(--c3);margin:0 8px}

/* ── Modal ── */
.stk-ov{position:fixed;inset:0;background:rgba(0,0,0,.45);backdrop-filter:blur(3px);z-index:9999;display:flex;align-items:center;justify-content:center;opacity:0;visibility:hidden;transition:all .2s}
.stk-ov.show{opacity:1;visibility:visible}
.stk-md{background:#fff;border-radius:18px;width:96%;max-width:820px;max-height:92vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.2);transform:scale(.92) translateY(10px);transition:transform .25s cubic-bezier(.34,1.56,.64,1)}
.stk-ov.show .stk-md{transform:scale(1) translateY(0)}
.stk-md-sm{max-width:420px}
.stk-mh{padding:20px 24px 0;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:#fff;z-index:2;border-bottom:1px solid transparent}
.stk-mh h3{font-size:16px;font-weight:700;color:var(--c1);display:flex;align-items:center;gap:8px}
.stk-mx{width:32px;height:32px;border-radius:8px;border:none;background:#f1f5f9;cursor:pointer;font-size:14px;color:var(--c3);display:flex;align-items:center;justify-content:center;transition:all .12s}
.stk-mx:hover{background:#fee2e2;color:#dc2626}
.stk-mb{padding:16px 24px 24px}
.stk-dg{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.stk-dlb{font-size:10px;font-weight:700;color:var(--c3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px}
.stk-dvl{font-size:13px;color:var(--c1);font-weight:500}
.stk-df{grid-column:1/-1}
.stk-da{display:flex;gap:8px;margin-top:16px;flex-wrap:wrap}

/* ── Toast ── */
.stk-toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(100px);background:#1a1a2e;color:#fff;padding:12px 24px;border-radius:var(--stk-rs);font-size:13px;font-weight:500;display:flex;align-items:center;gap:8px;z-index:99999;opacity:0;transition:all .3s}
.stk-toast.show{transform:translateX(-50%) translateY(0);opacity:1}
.stk-toast.ok{background:#0d6832}.stk-toast.err{background:#c62828}

/* ── Empty ── */
.stk-empty{text-align:center;padding:48px 24px;color:var(--c3)}
.stk-empty i{font-size:48px;margin-bottom:12px;opacity:.25}
.stk-empty p{font-size:14px}

/* ── Barcode Label Preview ── */
.stk-label{background:#fff;border:2px solid #333;border-radius:6px;padding:16px;width:380px;max-width:100%;margin:0 auto;font-family:'Courier New',monospace;position:relative}
.stk-label-header{display:flex;align-items:center;gap:8px;border-bottom:2px solid #333;padding-bottom:8px;margin-bottom:10px}
.stk-label-header .lab-logo{width:32px;height:32px;background:linear-gradient(135deg,#065f46,#0d9488);border-radius:6px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:14px;font-weight:900;flex-shrink:0}
.stk-label-header .lab-title{font-size:10px;font-weight:700;line-height:1.2;color:#333}.stk-label-header .lab-sub{font-size:8px;color:#666}
.stk-label-chem{font-size:14px;font-weight:900;color:#000;margin-bottom:2px;line-height:1.2;word-break:break-word}
.stk-label-cas{font-size:10px;color:#444;margin-bottom:6px}
.stk-label-row{display:flex;justify-content:space-between;font-size:9px;color:#444;margin-bottom:2px}
.stk-label-row b{color:#000;font-weight:700}
.stk-label-ghsstrip{display:flex;gap:4px;margin:6px 0;flex-wrap:wrap}
.stk-label-ghs{width:24px;height:24px;position:relative}
.stk-label-ghs-inner{position:absolute;inset:2px;transform:rotate(45deg);border:1.5px solid #dc2626;border-radius:2px;display:flex;align-items:center;justify-content:center;font-size:9px;background:#fff}
.stk-label-ghs-inner i{transform:rotate(-45deg)}
.stk-label-signal{font-size:9px;font-weight:900;padding:2px 6px;border-radius:3px;text-transform:uppercase;letter-spacing:.5px;display:inline-block;margin-bottom:6px}
.stk-label-signal.danger{background:#dc2626;color:#fff}
.stk-label-signal.warning{background:#f59e0b;color:#000}
.stk-label-codes{display:flex;gap:10px;align-items:flex-end;border-top:2px solid #333;padding-top:8px;margin-top:8px}
.stk-label-qr{width:80px;height:80px;border:1px solid #ddd;border-radius:4px;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:#fff;position:relative;overflow:hidden}
.stk-label-qr img{max-width:100%;max-height:100%}
.stk-label-qr canvas{max-width:100%;max-height:100%}
.stk-label-barcode{flex:1;text-align:center;overflow:hidden}
.stk-label-barcode svg,.stk-label-barcode canvas{max-width:100%;height:40px!important}
.stk-label-barcode-text{font-size:8px;color:#333;margin-top:2px;letter-spacing:1px;font-weight:700}
.stk-label-footer{border-top:1px dashed #999;padding-top:4px;margin-top:6px;font-size:7px;color:#999;display:flex;justify-content:space-between}
.stk-label-ar{position:absolute;top:8px;right:8px;background:linear-gradient(135deg,#6C5CE7,#a855f7);color:#fff;font-size:7px;padding:2px 6px;border-radius:4px;font-weight:800;display:flex;align-items:center;gap:3px}

/* ── QR Modal Display ── */
.stk-qr-display{text-align:center;padding:20px}
.stk-qr-display .qr-big{width:200px;height:200px;margin:0 auto 12px;border:3px solid var(--border);border-radius:12px;padding:8px;background:#fff;display:flex;align-items:center;justify-content:center}
.stk-qr-display .qr-big img,.stk-qr-display .qr-big canvas{max-width:100%;max-height:100%}
.stk-qr-display .qr-val{font-family:'Courier New',monospace;font-size:13px;font-weight:700;color:var(--c1);background:#f1f5f9;padding:6px 14px;border-radius:8px;display:inline-block;letter-spacing:.5px;margin-bottom:8px}
.stk-qr-display .qr-hint{font-size:11px;color:var(--c3);display:flex;align-items:center;gap:6px;justify-content:center}

/* ── Report Export Dropdown ── */
.stk-export-dd{position:relative;display:inline-block}
.stk-export-menu{position:absolute;top:calc(100% + 4px);right:0;background:#fff;border:1.5px solid var(--border);border-radius:var(--stk-rs);box-shadow:var(--stk-shm);min-width:200px;z-index:100;display:none;overflow:hidden}
.stk-export-menu.show{display:block;animation:ddSlide .15s ease-out}
@keyframes ddSlide{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:translateY(0)}}
.stk-export-item{display:flex;align-items:center;gap:10px;padding:10px 14px;font-size:12px;color:var(--c1);cursor:pointer;transition:background .1s;border:none;background:none;width:100%;text-align:left;font-family:inherit}
.stk-export-item:hover{background:#f0fdf4}
.stk-export-item i{width:16px;text-align:center;font-size:13px}
.stk-export-item .ext{font-size:9px;padding:1px 5px;border-radius:3px;font-weight:700;margin-left:auto}
.stk-export-sep{height:1px;background:var(--border);margin:2px 0}

/* ── Batch Actions Bar ── */
.stk-batch{background:linear-gradient(135deg,#065f46,#0d9488);border-radius:var(--stk-rs);padding:10px 16px;display:flex;align-items:center;gap:10px;margin-bottom:12px;color:#fff;font-size:12px;font-weight:600;animation:fadeIn .2s ease-out;position:fixed;bottom:16px;left:50%;transform:translateX(-50%);z-index:1000;box-shadow:0 8px 32px rgba(6,95,70,.35);min-width:320px;max-width:90vw;border-radius:14px}
@keyframes fadeIn{from{opacity:0;transform:translateY(-4px)}to{opacity:1;transform:translateY(0)}}
.stk-batch .sel-count{background:rgba(255,255,255,.2);padding:3px 10px;border-radius:6px;font-weight:800}
.stk-batch button{padding:6px 12px;border:1px solid rgba(255,255,255,.3);border-radius:6px;background:rgba(255,255,255,.1);color:#fff;font-size:11px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:5px;transition:all .12s;font-family:inherit}
.stk-batch button:hover{background:rgba(255,255,255,.25)}

/* ── Selection ── */
.stk-selected{outline:2px solid var(--accent)!important;outline-offset:-2px;background:linear-gradient(135deg,rgba(13,148,136,.03),rgba(20,184,166,.06))!important}
.stk-card .stk-chk,.stk-cr .stk-chk{accent-color:var(--accent)}
tr .stk-chk{accent-color:var(--accent)}

/* ── QR Display (modal) ── */
.stk-qr-display{text-align:center;padding:20px}
.stk-qr-display .qr-big{width:180px;height:180px;margin:0 auto 12px;background:#fff;border-radius:12px;border:2px solid var(--border);padding:8px;display:flex;align-items:center;justify-content:center}
.stk-qr-display .qr-val{font-family:'Courier New',monospace;font-size:12px;color:var(--c2);letter-spacing:1px;margin-bottom:8px;font-weight:600}
.stk-qr-display .qr-hint{font-size:11px;color:var(--c3);line-height:1.5;padding:8px 16px;background:#f0fdf4;border-radius:8px;border:1px solid #bbf7d0;display:inline-block}

/* ── Print Styles ── */
@media print{
    body *{visibility:hidden}
    .stk-print-area,.stk-print-area *{visibility:visible}
    .stk-print-area{position:absolute;left:0;top:0;width:100%}
    .stk-label{border:2px solid #000!important;break-inside:avoid;margin-bottom:12px}
    .stk-print-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
}

/* ── Responsive ── */
@media(max-width:768px){
    .stk-hero{flex-direction:column;text-align:center;gap:12px;padding:20px}
    .stk-hero-meta{margin-left:0}
    .stk-stats{grid-template-columns:repeat(2,1fr)}
    .stk-toolbar{flex-direction:column;align-items:stretch}
    .stk-search{min-width:100%}
    .stk-grid{grid-template-columns:1fr}
    .stk-dg{grid-template-columns:1fr}
    .stk-an{grid-template-columns:1fr}
    .stk-tabs{overflow-x:auto}
    .stk-cc,.stk-co{display:none}
    .stk-dn{flex-direction:column}
    .stk-md{width:100%;max-width:100%;border-radius:14px 14px 0 0;max-height:95vh;margin-top:auto}
}
</style>
<body>
<?php Layout::sidebar('stock'); Layout::beginContent(); ?>

<!-- ═══ Hero Banner ═══ -->
<div class="stk-hero">
    <div class="stk-hero-ic"><i class="fas fa-flask"></i></div>
    <div class="stk-hero-info">
        <h2><?php echo $lang==='th'?'คลังขวดสารเคมี':'Chemical Bottle Stock'; ?></h2>
        <p><?php echo $lang==='th'
            ? ($canSeeAll?'ภาพรวมข้อมูลขวดสารเคมีทั้งหมดในระบบ':($isLab?'จัดการสารเคมีของทีมคุณ':'จัดการขวดสารเคมีของคุณ'))
            : ($canSeeAll?'Overview of all chemical bottles in the system':($isLab?'Manage your team\'s chemicals':'Manage your chemical bottles'));
        ?></p>
    </div>
    <div class="stk-hero-meta">
        <div class="stk-hero-c"><div class="v" id="heroTotal">—</div><div class="lb"><?php echo $lang==='th'?'ขวดทั้งหมด':'Total'; ?></div></div>
        <div class="stk-hero-c"><div class="v" id="heroMy">—</div><div class="lb"><?php echo $lang==='th'?'ของฉัน':'My Stock'; ?></div></div>
        <div class="stk-hero-c"><div class="v" id="hero3D">—</div><div class="lb">3D</div></div>
    </div>
</div>

<!-- ═══ Stats Row ═══ -->
<div class="stk-stats" id="statsRow"></div>

<!-- ═══ Tabs + View ═══ -->
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:14px">
    <div class="stk-tabs" id="mainTabs">
        <button class="stk-tab active" data-tab="all" onclick="switchTab('all')">
            <i class="fas fa-globe"></i> <?php echo $lang==='th'?($canSeeAll?'ทั้งหมด':'ที่เข้าถึงได้'):($canSeeAll?'All':'Accessible'); ?>
            <span class="bg" id="badgeAll">0</span>
        </button>
        <button class="stk-tab" data-tab="my" onclick="switchTab('my')">
            <i class="fas fa-user"></i> <?php echo $lang==='th'?'ของฉัน':'My Stock'; ?>
            <span class="bg" id="badgeMy">0</span>
        </button>
    </div>
    <div style="display:flex;gap:6px;align-items:center">
        <div class="stk-vw" id="viewSw">
            <button class="active" data-view="table" onclick="setView('table')" title="Table"><i class="fas fa-th-list"></i></button>
            <button data-view="grid" onclick="setView('grid')" title="Grid"><i class="fas fa-th-large"></i></button>
            <button data-view="compact" onclick="setView('compact')" title="Compact"><i class="fas fa-bars"></i></button>
            <button data-view="analytics" onclick="setView('analytics')" title="Analytics"><i class="fas fa-chart-pie"></i></button>
        </div>
        <?php if ($canEdit): ?>
        <a href="/v1/pages/containers.php?action=add" class="stk-btn stk-btn-p"><i class="fas fa-plus"></i> <?php echo $lang==='th'?'เพิ่มขวด':'Add Bottle'; ?></a>
        <?php endif; ?>
    </div>
</div>

<!-- ═══ My Banner ═══ -->
<div class="stk-my" id="myBanner" style="display:none">
    <div class="stk-my-av"><?php echo htmlspecialchars($userInitial); ?></div>
    <div style="flex:1">
        <h3><?php echo $lang==='th'?'สารเคมีของฉัน':'My Chemical Stock'; ?> — <?php echo htmlspecialchars($userDisplayName); ?></h3>
        <p><?php echo $lang==='th'?'แสดงเฉพาะขวดที่คุณเป็นผู้เพิ่มหรือรับผิดชอบ':'Showing bottles you added or are responsible for'; ?></p>
    </div>
    <div style="display:flex;gap:14px">
        <div class="stk-hero-c"><div class="v" id="myStatTotal">—</div><div class="lb"><?php echo $lang==='th'?'ขวด':'Bottles'; ?></div></div>
        <div class="stk-hero-c"><div class="v" id="myStatActive" style="color:#4ade80">—</div><div class="lb"><?php echo $lang==='th'?'ปกติ':'Active'; ?></div></div>
    </div>
</div>

<!-- ═══ Toolbar ═══ -->
<div class="stk-toolbar" id="toolbar">
    <div class="stk-search">
        <i class="fas fa-search"></i>
        <input type="text" id="searchInput" placeholder="<?php echo $lang==='th'?'ค้นหา: รหัสขวด, ชื่อสาร, CAS, ผู้เพิ่ม, ผู้ผลิต...':'Search: bottle code, chemical, CAS, owner, manufacturer...'; ?>">
    </div>
    <button class="stk-btn stk-btn-g" id="filterToggle" onclick="toggleFilter()">
        <i class="fas fa-sliders-h"></i> <?php echo $lang==='th'?'ตัวกรอง':'Filters'; ?>
    </button>
    <select id="sortSelect" style="padding:7px 10px;border:1.5px solid var(--border);border-radius:8px;font-size:12px;background:#fff">
        <option value="newest"><?php echo $lang==='th'?'ใหม่สุด':'Newest'; ?></option>
        <option value="oldest"><?php echo $lang==='th'?'เก่าสุด':'Oldest'; ?></option>
        <option value="name_asc">A → Z</option>
        <option value="name_desc">Z → A</option>
        <option value="pct_asc"><?php echo $lang==='th'?'เหลือน้อย':'Low first'; ?></option>
        <option value="pct_desc"><?php echo $lang==='th'?'เหลือมาก':'Full first'; ?></option>
        <option value="bottle_code"><?php echo $lang==='th'?'รหัสขวด':'Bottle Code'; ?></option>
    </select>
    <div class="stk-export-dd">
        <button class="stk-btn stk-btn-g" onclick="toggleExportMenu(event)"><i class="fas fa-file-export"></i> <?php echo $lang==='th'?'ส่งออก / รายงาน':'Export / Report'; ?> <i class="fas fa-chevron-down" style="font-size:9px;margin-left:2px"></i></button>
        <div class="stk-export-menu" id="exportMenu">
            <button class="stk-export-item" onclick="doExport('csv')">
                <i class="fas fa-file-csv" style="color:#16a34a"></i> <?php echo $lang==='th'?'ส่งออก CSV':'Export CSV'; ?>
                <span class="ext" style="background:#dcfce7;color:#16a34a">.csv</span>
            </button>
            <button class="stk-export-item" onclick="doExport('pdf_report')">
                <i class="fas fa-file-pdf" style="color:#dc2626"></i> <?php echo $lang==='th'?'รายงาน PDF (สรุป)':'Summary Report PDF'; ?>
                <span class="ext" style="background:#fee2e2;color:#dc2626">.pdf</span>
            </button>
            <div class="stk-export-sep"></div>
            <button class="stk-export-item" onclick="doPrintLabels('selected')">
                <i class="fas fa-print" style="color:#7c3aed"></i> <?php echo $lang==='th'?'พิมพ์ฉลากขวด (ที่เลือก)':'Print Labels (Selected)'; ?>
            </button>
            <button class="stk-export-item" onclick="doPrintLabels('all')">
                <i class="fas fa-tags" style="color:#0d9488"></i> <?php echo $lang==='th'?'พิมพ์ฉลากทั้งหมด (หน้านี้)':'Print All Labels (This Page)'; ?>
            </button>
            <div class="stk-export-sep"></div>
            <button class="stk-export-item" onclick="doPrintLabels('qr_sheet')">
                <i class="fas fa-qrcode" style="color:#2563eb"></i> <?php echo $lang==='th'?'แผ่น QR Code (A4)':'QR Code Sheet (A4)'; ?>
            </button>
        </div>
    </div>
</div>

<!-- ═══ Filter Panel ═══ -->
<div class="stk-fp" id="filterPanel">
    <div class="stk-fg2">
        <div class="stk-fl">
            <label><?php echo $lang==='th'?'สถานะ':'Status'; ?></label>
            <select id="fStatus">
                <option value=""><?php echo $lang==='th'?'ทั้งหมด':'All'; ?></option>
                <option value="active"><?php echo $lang==='th'?'ปกติ':'Active'; ?></option>
                <option value="empty"><?php echo $lang==='th'?'หมด':'Empty'; ?></option>
                <option value="expired"><?php echo $lang==='th'?'หมดอายุ':'Expired'; ?></option>
                <option value="quarantined"><?php echo $lang==='th'?'กักกัน':'Quarantined'; ?></option>
            </select>
        </div>
        <div class="stk-fl">
            <label><?php echo $lang==='th'?'ประเภท':'Type'; ?></label>
            <select id="fType">
                <option value=""><?php echo $lang==='th'?'ทั้งหมด':'All'; ?></option>
                <option value="bottle">Bottle</option>
                <option value="vial">Vial</option>
                <option value="flask">Flask</option>
                <option value="canister">Canister</option>
                <option value="cylinder">Cylinder</option>
                <option value="ampoule">Ampoule</option>
            </select>
        </div>
        <div class="stk-fl">
            <label><?php echo $lang==='th'?'อาคาร':'Building'; ?></label>
            <select id="fBuilding">
                <option value=""><?php echo $lang==='th'?'ทั้งหมด':'All'; ?></option>
            </select>
        </div>
        <div class="stk-fl">
            <label><?php echo $lang==='th'?'แหล่งข้อมูล':'Source'; ?></label>
            <select id="fSource">
                <option value=""><?php echo $lang==='th'?'ทั้งหมด':'All'; ?></option>
                <option value="container"><?php echo $lang==='th'?'ระบบใหม่ (Container)':'System (Container)'; ?></option>
                <option value="stock"><?php echo $lang==='th'?'คลังเดิม (CSV)':'Legacy (CSV)'; ?></option>
            </select>
        </div>
    </div>
    <div class="stk-fa">
        <button class="stk-btn stk-btn-g stk-btn-s" onclick="clearFilters()"><i class="fas fa-undo"></i> <?php echo $lang==='th'?'ล้าง':'Clear'; ?></button>
    </div>
</div>

<!-- ═══ Data Area ═══ -->
<div id="dataArea"></div>
<div class="stk-pager" id="pagerArea"></div>

<!-- ═══ Batch Actions Bar ═══ -->
<div class="stk-batch" id="batchBar" style="display:none">
    <span class="sel-count" id="selCount">0</span> <?php echo $lang==='th'?'รายการที่เลือก':'selected'; ?>
    <button onclick="doPrintLabels('selected')"><i class="fas fa-print"></i> <?php echo $lang==='th'?'พิมพ์ฉลาก':'Print Labels'; ?></button>
    <button onclick="doPrintLabels('qr_selected')"><i class="fas fa-qrcode"></i> QR Sheet</button>
    <button onclick="clearSelection()" style="margin-left:auto;border-color:rgba(255,255,255,.5)"><i class="fas fa-times"></i> <?php echo $lang==='th'?'ยกเลิก':'Cancel'; ?></button>
</div>

<!-- ═══ Detail Modal ═══ -->
<div class="stk-ov" id="detailOv" onclick="if(event.target===this)closeDetail()">
    <div class="stk-md" id="detailModal"></div>
</div>

<!-- ═══ Label Preview Modal ═══ -->
<div class="stk-ov" id="labelOv" onclick="if(event.target===this)closeLabelModal()">
    <div class="stk-md" style="max-width:900px" id="labelModal">
        <div class="stk-mh">
            <h3><i class="fas fa-tag" style="color:var(--accent)"></i> <?php echo $lang==='th'?'ฉลากขวดสารเคมี':'Chemical Bottle Labels'; ?></h3>
            <button class="stk-mx" onclick="closeLabelModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="stk-mb" id="labelContent">
            <div style="text-align:center;padding:40px;color:var(--c3)"><i class="fas fa-spinner fa-spin fa-2x"></i></div>
        </div>
    </div>
</div>

<!-- ═══ QR Code Enlarge Modal ═══ -->
<div class="stk-ov" id="qrOv" onclick="if(event.target===this)closeQrModal()">
    <div class="stk-md stk-md-sm" id="qrModal">
        <div class="stk-mh">
            <h3><i class="fas fa-qrcode" style="color:var(--accent)"></i> QR Code</h3>
            <button class="stk-mx" onclick="closeQrModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="stk-mb" id="qrContent"></div>
    </div>
</div>

<!-- ═══ Hidden print area ═══ -->
<div class="stk-print-area" id="printArea" style="display:none"></div>

<!-- ═══ Toast ═══ -->
<div class="stk-toast" id="stkToast"></div>
<?php Layout::endContent(); ?>

<script>
/* ═════════════════════════════════════════
   CONFIG & STATE
   ═════════════════════════════════════════ */
const L='<?php echo $lang; ?>';
const ROLE='<?php echo $role; ?>';
const UID=<?php echo (int)$uid; ?>;
const IS_ADMIN=<?php echo $isAdmin?'true':'false'; ?>;
const CAN_EDIT=<?php echo $canEdit?'true':'false'; ?>;
const CAN_SEE_ALL=<?php echo $canSeeAll?'true':'false'; ?>;
const USER_NAME='<?php echo addslashes($userDisplayName); ?>';

let VIEW='table',TAB='all',PAGE=1;
let DATA=[],STATS=null,SELECTED=new Set();
const T=(th,en)=>L==='th'?th:en;

const typeIcons={bottle:'fa-wine-bottle',vial:'fa-vial',flask:'fa-flask',canister:'fa-gas-pump',cylinder:'fa-fire-extinguisher',ampoule:'fa-syringe',bag:'fa-bag-shopping',other:'fa-box'};
const typeLabels={bottle:'Bottle',vial:'Vial',flask:'Flask',canister:'Canister',cylinder:'Cylinder',ampoule:'Ampoule',bag:'Bag',other:'Other'};

const ghsTinyIcons={compressed_gas:'fa-wind',flammable:'fa-fire-flame-curved',oxidizing:'fa-circle-radiation',toxic:'fa-skull-crossbones',corrosive:'fa-flask-vial',irritant:'fa-exclamation-triangle',environmental:'fa-leaf',health_hazard:'fa-heart-crack',explosive:'fa-explosion'};
const ghsTinyColors={compressed_gas:'#d97706',flammable:'#dc2626',oxidizing:'#d97706',toxic:'#991b1b',corrosive:'#7c3aed',irritant:'#f59e0b',environmental:'#16a34a',health_hazard:'#dc2626',explosive:'#ea580c'};
const ghsLabelsMap={compressed_gas:T('ก๊าซอัด','Compressed Gas'),flammable:T('ไวไฟ','Flammable'),oxidizing:T('วัตถุออกซิไดซ์','Oxidizing'),toxic:T('พิษเฉียบพลัน','Toxic'),corrosive:T('กัดกร่อน','Corrosive'),irritant:T('ระคายเคือง','Irritant'),environmental:T('อันตรายต่อสิ่งแวดล้อม','Environmental Hazard'),health_hazard:T('อันตรายต่อสุขภาพ','Health Hazard'),explosive:T('วัตถุระเบิด','Explosive')};

/* ═════════════════════════════════════════
   INIT
   ═════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded',()=>{
    loadStats();loadData(1);setupSearch();loadBuildingFilter();
});
function setupSearch(){let t;document.getElementById('searchInput').addEventListener('input',()=>{clearTimeout(t);t=setTimeout(()=>loadData(1),300)})}

/* ═════════════════════════════════════════
   STATS
   ═════════════════════════════════════════ */
async function loadStats(){
    try{
        const d=await apiFetch('/v1/api/containers.php?action=stats');
        if(!d.success)return;
        STATS=d.data;const s=d.data;
        document.getElementById('heroTotal').textContent=num(s.total);
        document.getElementById('heroMy').textContent=num(s.my_total||0);
        document.getElementById('hero3D').textContent=num(s.models_3d||0);
        document.getElementById('badgeAll').textContent=num(s.total);
        document.getElementById('badgeMy').textContent=num(s.my_total||0);
        // Source breakdown tooltip
        const sb=s.source_breakdown||{};
        const heroEl=document.getElementById('heroTotal');
        if(heroEl)heroEl.title=`Container: ${num(sb.containers||0)} | CSV Stock: ${num(sb.stock||0)}`;
        const mst=document.getElementById('myStatTotal');if(mst)mst.textContent=num(s.my_total||0);
        const msa=document.getElementById('myStatActive');if(msa)msa.textContent=num(s.my_active||0);
        const cards=[
            {icon:'fa-check-circle',bg:'#dcfce7',fg:'#15803d',v:s.active,l:T('ปกติ','Active'),k:'active'},
            {icon:'fa-flask-vial',bg:'#dbeafe',fg:'#2563eb',v:s.chemicals,l:T('สารเคมี','Chemicals'),k:''},
            {icon:'fa-battery-quarter',bg:'#fef3c7',fg:'#d97706',v:s.low,l:T('เหลือน้อย','Low'),k:''},
            {icon:'fa-clock',bg:'#fee2e2',fg:'#dc2626',v:s.expiring_soon,l:T('ใกล้หมดอายุ','Expiring'),k:''},
            {icon:'fa-box-archive',bg:'#f1f5f9',fg:'#64748b',v:s.empty,l:T('หมดแล้ว','Empty'),k:'empty'},
            {icon:'fa-cube',bg:'#ede9fe',fg:'#7c3aed',v:s.models_3d,l:'3D Models',k:''},
        ];
        document.getElementById('statsRow').innerHTML=cards.map(c=>`
            <div class="stk-stat" ${c.k?`onclick="quickFilter('${c.k}')"`:''}> 
                <div class="stk-si" style="background:${c.bg};color:${c.fg}"><i class="fas ${c.icon}"></i></div>
                <div><div class="stk-sv">${num(c.v)}</div><div class="stk-sl">${c.l}</div></div>
            </div>`).join('');
    }catch(e){console.error(e)}
}
function quickFilter(st){
    const sel=document.getElementById('fStatus');
    if(sel.value===st){sel.value=''}else{sel.value=st}
    loadData(1);
}

/* ═════════════════════════════════════════
   LOAD DATA
   ═════════════════════════════════════════ */
async function loadData(page){
    PAGE=page||1;
    const p=new URLSearchParams({page:PAGE,limit:25,tab:TAB,sort:document.getElementById('sortSelect').value});
    const search=document.getElementById('searchInput').value.trim();
    if(search)p.set('search',search);
    const status=document.getElementById('fStatus').value;if(status)p.set('status',status);
    const type=document.getElementById('fType')?.value;if(type)p.set('type',type);
    const building=document.getElementById('fBuilding')?.value;if(building)p.set('building_id',building);
    const source=document.getElementById('fSource')?.value;if(source)p.set('source',source);

    const area=document.getElementById('dataArea');
    area.innerHTML='<div style="text-align:center;padding:40px;color:var(--c3)"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    try{
        const d=await apiFetch('/v1/api/containers.php?'+p);
        if(!d.success)throw new Error(d.error);
        DATA=d.data.data||[];
        renderView();
        renderPager(d.data.pagination);
    }catch(e){area.innerHTML='<div class="stk-empty"><i class="fas fa-exclamation-circle"></i><p>'+esc(e.message)+'</p></div>'}
}

/* ═════════════════════════════════════════
   RENDER DISPATCHER
   ═════════════════════════════════════════ */
function renderView(){
    const area=document.getElementById('dataArea');
    if(!DATA||!DATA.length){
        area.innerHTML=`<div class="stk-empty"><i class="fas fa-flask"></i><p>${T('ไม่พบข้อมูลขวดสารเคมี','No chemical bottles found')}</p>${CAN_EDIT?`<a href="/v1/pages/containers.php?action=add" class="stk-btn stk-btn-p" style="margin-top:12px"><i class="fas fa-plus"></i> ${T('เพิ่มขวดสาร','Add Bottle')}</a>`:''}</div>`;
        document.getElementById('pagerArea').innerHTML='';return;
    }
    switch(VIEW){
        case 'table':renderTable(area);break;
        case 'grid':renderGrid(area);break;
        case 'compact':renderCompact(area);break;
        case 'analytics':renderAnalytics(area);break;
        default:renderTable(area);
    }
}

/* ═════════════════════════════════════════
   TABLE VIEW
   ═════════════════════════════════════════ */
function renderTable(area){
    let h='<div class="stk-tw"><table class="stk-t"><thead><tr>';
    h+='<th style="width:30px"><input type="checkbox" id="chkAll" onclick="toggleSelectAll(event)" style="cursor:pointer"></th>';
    h+='<th>#</th>';
    h+=`<th>${T('สารเคมี','Chemical')}</th>`;
    h+=`<th>${T('รหัสขวด','Code')}</th>`;
    h+=`<th>${T('ปริมาณ','Quantity')}</th>`;
    h+='<th>%</th>';
    h+=`<th>${T('อันตราย','Hazard')}</th>`;
    h+=`<th>${T('สถานะ','Status')}</th>`;
    h+=`<th>${T('เจ้าของ','Owner')}</th>`;
    h+=`<th>${T('ตำแหน่ง','Location')}</th>`;
    h+=`<th style="text-align:center">${T('QR/ฉลาก','QR/Label')}</th>`;
    h+='</tr></thead><tbody>';
    DATA.forEach((r,i)=>{
        const p=parseFloat(r.remaining_percentage)||0;
        const mine=r.is_mine;
        const idx=(PAGE-1)*25+i+1;
        const haz=(r.hazard_pictograms||[]);
        const hazTiny=haz.length?haz.slice(0,4).map(hp=>`<i class="fas ${ghsTinyIcons[hp]||'fa-exclamation'}" style="font-size:10px;color:${ghsTinyColors[hp]||'#dc2626'}" title="${hp}"></i>`).join(' ')+(haz.length>4?` <span style="font-size:9px;color:var(--c3)">+${haz.length-4}</span>`:''):`<span style="color:#ccc;font-size:10px">—</span>`;
        h+=`<tr class="${mine?'me':''}" onclick="openDetail(${r.id})">
            <td onclick="event.stopPropagation()"><input type="checkbox" class="stk-chk" data-id="${r.id}" ${SELECTED.has(r.id)?'checked':''} onchange="toggleSelect(${r.id},event)" style="cursor:pointer"></td>
            <td>${idx}</td>
            <td><div style="display:flex;align-items:center;gap:8px">
                <div class="type-icon type-${r.container_type||'other'}"><i class="fas ${typeIcons[r.container_type]||'fa-box'}"></i></div>
                <div><div style="font-weight:600;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(r.chemical_name||'-')}</div>
                ${r.cas_number?`<div style="font-size:10px;color:var(--c3)">${r.cas_number} ${srcBadge(r.source)}</div>`:srcBadge(r.source)}</div>
            </div></td>
            <td><code style="font-size:10px;background:#f1f5f9;padding:2px 6px;border-radius:4px;font-family:'Courier New',monospace;letter-spacing:0.3px">${esc(r.bottle_code||'')}</code></td>
            <td style="white-space:nowrap;font-size:11px">${r.current_quantity||0} / ${r.initial_quantity||0} ${esc(r.quantity_unit||'')}</td>
            <td><div style="display:flex;align-items:center;gap:6px">
                <div style="width:40px;height:4px;border-radius:2px;background:#e2e8f0;overflow:hidden"><div class="${barCls(p)}" style="height:100%;width:${p}%;border-radius:2px"></div></div>
                <span style="font-weight:700;font-size:11px;color:${pctColor(p)}">${p.toFixed(0)}%</span>
            </div></td>
            <td style="white-space:nowrap">${hazTiny}</td>
            <td>${badgeHtml(r.status||'active')}</td>
            <td style="font-size:11px">${r.is_mine?'<i class="fas fa-star" style="color:#d97706;font-size:10px"></i> ':''}<span>${esc(r.owner_name||'-')}</span></td>
            <td style="font-size:11px;color:var(--c2)">${esc(r.location_text||'-')}</td>
            <td onclick="event.stopPropagation()" style="text-align:center">
                <div style="display:flex;gap:3px;justify-content:center">
                    <button class="stk-btn stk-btn-s stk-btn-g" onclick="showQRModal({id:${r.id},qr_code:'${esc(r.qr_code||'')}',bottle_code:'${esc(r.bottle_code||'')}',chemical_name:'${esc(r.chemical_name||'')}'})" title="QR Code" style="padding:4px 6px"><i class="fas fa-qrcode" style="font-size:11px"></i></button>
                    <button class="stk-btn stk-btn-s stk-btn-g" onclick="doPrintSingleLabel(${r.id})" title="${T('พิมพ์ฉลาก','Print Label')}" style="padding:4px 6px"><i class="fas fa-tag" style="font-size:11px"></i></button>
                    ${r.has_3d?`<a href="/v1/ar/view_ar.php?id=${r.id}" target="_blank" class="stk-btn stk-btn-s" style="padding:4px 6px;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;text-decoration:none" title="AR View"><i class="fas fa-vr-cardboard" style="font-size:11px"></i></a>`:''}
                </div>
            </td>
        </tr>`;
    });
    h+='</tbody></table></div>';
    area.innerHTML=h;
}

/* ═════════════════════════════════════════
   GRID VIEW
   ═════════════════════════════════════════ */
function renderGrid(area){
    let h='<div class="stk-grid">';
    DATA.forEach(r=>{
        const p=parseFloat(r.remaining_percentage)||0;
        const mine=r.is_mine;
        const isExp=r.expiry_date&&new Date(r.expiry_date)<new Date();
        const haz=(r.hazard_pictograms||[]);
        const hazMini=haz.length?`<div style="display:flex;gap:3px;flex-wrap:wrap;margin-top:6px">${haz.slice(0,5).map(hp=>`<span style="width:20px;height:20px;border-radius:4px;display:inline-flex;align-items:center;justify-content:center;font-size:9px;background:#fef2f2;color:${ghsTinyColors[hp]||'#dc2626'};border:1px solid ${ghsTinyColors[hp]||'#fecaca'}30" title="${hp}"><i class="fas ${ghsTinyIcons[hp]||'fa-exclamation'}"></i></span>`).join('')}${haz.length>5?`<span style="font-size:9px;color:var(--c3)">+${haz.length-5}</span>`:''}</div>`:'';
        h+=`<div class="stk-card${mine?' me':''}${SELECTED.has(r.id)?' stk-selected':''}" onclick="openDetail(${r.id})">
            ${r.has_3d?'<div class="stk-3d-badge"><i class="fas fa-cube"></i> 3D</div>':''}
            <div onclick="event.stopPropagation()" style="position:absolute;top:8px;left:8px;z-index:2">
                <input type="checkbox" class="stk-chk" data-id="${r.id}" ${SELECTED.has(r.id)?'checked':''} onchange="toggleSelect(${r.id},event)" style="cursor:pointer;width:16px;height:16px">
            </div>
            <div class="stk-card-hd">
                <div class="stk-card-ic type-${r.container_type||'other'}"><i class="fas ${typeIcons[r.container_type]||'fa-box'}"></i></div>
                <div style="min-width:0;flex:1">
                    <div class="stk-card-nm">${esc(r.chemical_name||'-')}</div>
                    <div class="stk-card-cd">${esc(r.bottle_code||'')} ${srcBadge(r.source)}</div>
                </div>
                ${badgeHtml(r.status||'active')}
            </div>
            <div class="stk-card-bd">
                <div class="stk-card-tg">
                    <span class="stk-card-tag" style="background:#f0fdf4;color:#059669">${typeLabels[r.container_type]||r.container_type||'-'}</span>
                    ${r.grade?`<span class="stk-card-tag" style="background:#ede9fe;color:#6d28d9">${esc(r.grade)}</span>`:''}
                    ${r.manufacturer_name?`<span class="stk-card-tag" style="background:#fef3c7;color:#d97706">${esc(r.manufacturer_name)}</span>`:''}
                </div>
                <div class="stk-card-ft" style="margin-bottom:6px">
                    <span>${r.current_quantity||0} / ${r.initial_quantity||0} ${esc(r.quantity_unit||'')}</span>
                    <span style="font-weight:700;color:${pctColor(p)}">${p.toFixed(0)}%</span>
                </div>
                <div class="stk-card-bar"><div class="stk-card-bf ${barCls(p)}" style="width:${Math.min(p,100)}%"></div></div>
                ${r.location_text&&r.location_text!=='-'?`<div class="stk-card-row"><i class="fas fa-map-marker-alt"></i> ${esc(r.location_text)}</div>`:''}
                <div class="stk-card-row"><i class="fas fa-user"></i> ${r.is_mine?'<i class="fas fa-star" style="color:#d97706;font-size:9px"></i> ':''} ${esc(r.owner_name||'-')}</div>
                ${r.expiry_date?`<div class="stk-card-row" style="${isExp?'color:#dc2626;font-weight:600':''}"><i class="fas fa-calendar"></i> ${fmtDate(r.expiry_date)}${isExp?' ⚠️':''}</div>`:''}
                ${hazMini}
                <div onclick="event.stopPropagation()" style="display:flex;gap:4px;margin-top:8px;padding-top:8px;border-top:1px solid #f1f5f9">
                    <button class="stk-btn stk-btn-s stk-btn-g" onclick="showQRModal({id:${r.id},qr_code:'${esc(r.qr_code||'')}',bottle_code:'${esc(r.bottle_code||'')}',chemical_name:'${esc(r.chemical_name||'')}'})" title="QR" style="padding:3px 8px;font-size:10px"><i class="fas fa-qrcode"></i></button>
                    <button class="stk-btn stk-btn-s stk-btn-g" onclick="doPrintSingleLabel(${r.id})" title="${T('ฉลาก','Label')}" style="padding:3px 8px;font-size:10px"><i class="fas fa-tag"></i></button>
                    ${r.has_3d?`<a href="/v1/ar/view_ar.php?id=${r.id}" target="_blank" class="stk-btn stk-btn-s" style="padding:3px 8px;font-size:10px;background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff;text-decoration:none" title="AR"><i class="fas fa-vr-cardboard"></i></a>`:''}
                </div>
            </div>
        </div>`;
    });
    h+='</div>';
    area.innerHTML=h;
}

/* ═════════════════════════════════════════
   COMPACT VIEW
   ═════════════════════════════════════════ */
function renderCompact(area){
    let h='<div class="stk-compact">';
    DATA.forEach(r=>{
        const p=parseFloat(r.remaining_percentage)||0;
        const mine=r.is_mine;
        h+=`<div class="stk-cr${mine?' me':''}${SELECTED.has(r.id)?' stk-selected':''}" onclick="openDetail(${r.id})">
            <div onclick="event.stopPropagation()" style="display:flex;align-items:center"><input type="checkbox" class="stk-chk" data-id="${r.id}" ${SELECTED.has(r.id)?'checked':''} onchange="toggleSelect(${r.id},event)" style="cursor:pointer;width:14px;height:14px;margin-right:6px"></div>
            <div class="type-icon type-${r.container_type||'other'}" style="width:24px;height:24px;border-radius:6px;font-size:10px"><i class="fas ${typeIcons[r.container_type]||'fa-box'}"></i></div>
            <div class="stk-cn" title="${esc(r.chemical_name)}">${esc(r.chemical_name||'-')}</div>
            <span class="stk-cc">${esc(r.bottle_code||'')} ${srcBadge(r.source)}</span>
            ${badgeHtml(r.status||'active')}
            <div class="stk-cb"><div class="${barCls(p)}" style="width:${Math.min(p,100)}%"></div></div>
            <span class="stk-cp" style="color:${pctColor(p)}">${p.toFixed(0)}%</span>
            <span class="stk-co">${esc(r.owner_name||'-')}</span>
            <div onclick="event.stopPropagation()" style="display:flex;gap:2px;margin-left:4px">
                <button class="stk-btn stk-btn-s stk-btn-g" onclick="doPrintSingleLabel(${r.id})" title="${T('ฉลาก','Label')}" style="padding:2px 5px;font-size:9px"><i class="fas fa-tag"></i></button>
                ${r.has_3d?`<a href="/v1/ar/view_ar.php?id=${r.id}" target="_blank" style="padding:2px 5px;font-size:9px;color:#0d9488;text-decoration:none" title="AR"><i class="fas fa-vr-cardboard"></i></a>`:''}
            </div>
        </div>`;
    });
    h+='</div>';
    area.innerHTML=h;
}

/* ═════════════════════════════════════════
   ANALYTICS VIEW
   ═════════════════════════════════════════ */
function renderAnalytics(area){
    if(!STATS){area.innerHTML='<div class="stk-empty"><p>Loading…</p></div>';return}
    const s=STATS;
    const total=s.total||1;
    const colors=['#22c55e','#3b82f6','#8b5cf6','#f59e0b','#ec4899','#0891b2','#ea580c','#64748b'];

    // Donut for statuses
    const sd=(s.statuses||[]);
    let svg='',off=0;
    const sc2={active:'#22c55e',empty:'#ef4444',expired:'#ec4899',quarantined:'#f59e0b',disposed:'#94a3b8'};
    sd.forEach(d=>{const pc=(d.cnt/total)*100;const ds=2*Math.PI*40;svg+=`<circle cx="50" cy="50" r="40" fill="none" stroke="${sc2[d.status]||'#999'}" stroke-width="14" stroke-dasharray="${ds*pc/100} ${ds*(1-pc/100)}" stroke-dashoffset="${-ds*off/100}" transform="rotate(-90 50 50)"/>`;off+=pc});

    const maxType=Math.max(...(s.types||[]).map(t=>t.cnt),1);
    const maxChem=Math.max(...(s.top_chemicals||[]).map(t=>t.cnt),1);
    const maxOwn=Math.max(...(s.top_owners||[]).map(t=>t.cnt),1);

    let h=`<div class="stk-an">
        <!-- Status Donut -->
        <div class="stk-ac"><div class="stk-at"><i class="fas fa-chart-pie"></i> ${T('สถานะขวดสาร','Bottle Status')}</div>
            <div class="stk-dn">
                <svg width="120" height="120" viewBox="0 0 100 100">${svg}<text x="50" y="48" text-anchor="middle" font-size="18" font-weight="800" fill="var(--c1)">${num(s.total)}</text><text x="50" y="60" text-anchor="middle" font-size="7" fill="var(--c3)">${T('ขวดทั้งหมด','TOTAL')}</text></svg>
                <div class="stk-dl2">${sd.map(d=>`<div class="stk-di"><div class="stk-dd" style="background:${sc2[d.status]||'#999'}"></div><span style="text-transform:capitalize">${d.status}: <b>${num(d.cnt)}</b> (${Math.round(d.cnt/total*100)}%)</span></div>`).join('')}</div>
            </div>
        </div>

        <!-- Container Types -->
        <div class="stk-ac"><div class="stk-at"><i class="fas fa-box-open"></i> ${T('ประเภทบรรจุภัณฑ์','Container Types')}</div>
            <div class="stk-bc">${(s.types||[]).map((t,i)=>`<div class="stk-br">
                <span class="stk-bl">${typeLabels[t.container_type]||t.container_type||'N/A'}</span>
                <div class="stk-bt"><div class="stk-bf" style="width:${(t.cnt/maxType*100).toFixed(1)}%;background:${colors[i%8]}">${t.cnt}</div></div>
                <span class="stk-bv">${(t.cnt/total*100).toFixed(0)}%</span>
            </div>`).join('')}</div>
        </div>

        <!-- Top Chemicals -->
        <div class="stk-ac"><div class="stk-at"><i class="fas fa-flask"></i> ${T('สารเคมีที่มีขวดมากสุด','Top Chemicals')}</div>
            <div class="stk-bc">${(s.top_chemicals||[]).map((c,i)=>`<div class="stk-br">
                <span class="stk-bl" title="${esc(c.chemical_name)}">${esc(c.chemical_name.length>14?c.chemical_name.substring(0,12)+'…':c.chemical_name)}</span>
                <div class="stk-bt"><div class="stk-bf" style="width:${(c.cnt/maxChem*100).toFixed(1)}%;background:${colors[i%8]}">${c.cnt}</div></div>
                <span class="stk-bv">${num(c.cnt)}</span>
            </div>`).join('')}${!(s.top_chemicals||[]).length?`<p style="text-align:center;color:var(--c3);font-size:12px">${T('ไม่มีข้อมูล','No data')}</p>`:''}</div>
        </div>

        <!-- Top Owners -->
        <div class="stk-ac"><div class="stk-at"><i class="fas fa-users"></i> ${T('เจ้าของขวดมากสุด','Top Owners')}</div>
            <div class="stk-bc">${(s.top_owners||[]).map((t,i)=>`<div class="stk-br">
                <span class="stk-bl" title="${esc(t.owner_name)}">${esc(t.owner_name)}</span>
                <div class="stk-bt"><div class="stk-bf" style="width:${(t.cnt/maxOwn*100).toFixed(1)}%;background:${colors[i%8]}">${t.cnt}</div></div>
                <span class="stk-bv">${num(t.cnt)}</span>
            </div>`).join('')}</div>
        </div>

        <!-- Summary -->
        <div class="stk-ac"><div class="stk-at"><i class="fas fa-info-circle"></i> ${T('สรุปภาพรวม','Summary')}</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div style="text-align:center;padding:14px;background:#f0fdf4;border-radius:10px"><div style="font-size:24px;font-weight:800;color:#16a34a">${num(s.total)}</div><div style="font-size:10px;color:var(--c3);text-transform:uppercase">${T('ขวดทั้งหมด','Total')}</div></div>
                <div style="text-align:center;padding:14px;background:#eff6ff;border-radius:10px"><div style="font-size:24px;font-weight:800;color:#2563eb">${num(s.chemicals)}</div><div style="font-size:10px;color:var(--c3);text-transform:uppercase">${T('ชนิดสาร','Chemicals')}</div></div>
                <div style="text-align:center;padding:14px;background:#fef9c3;border-radius:10px"><div style="font-size:24px;font-weight:800;color:#a16207">${num(s.low)}</div><div style="font-size:10px;color:var(--c3);text-transform:uppercase">${T('เหลือน้อย','Low Stock')}</div></div>
                <div style="text-align:center;padding:14px;background:#ede9fe;border-radius:10px"><div style="font-size:24px;font-weight:800;color:#7c3aed">${num(s.models_3d||0)}</div><div style="font-size:10px;color:var(--c3);text-transform:uppercase">3D Models</div></div>
            </div>
        </div>
    </div>`;
    area.innerHTML=h;
    document.getElementById('pagerArea').innerHTML='';
}

/* ═════════════════════════════════════════
   PAGINATION
   ═════════════════════════════════════════ */
function renderPager(pg){
    if(!pg||pg.pages<=1){document.getElementById('pagerArea').innerHTML='';return}
    let h='';
    h+=`<button ${pg.page<=1?'disabled':''} onclick="loadData(${pg.page-1})"><i class="fas fa-chevron-left"></i></button>`;
    const mx=5;let st=Math.max(1,pg.page-Math.floor(mx/2)),en=Math.min(pg.pages,st+mx-1);
    if(en-st<mx-1)st=Math.max(1,en-mx+1);
    if(st>1){h+=`<button onclick="loadData(1)">1</button>`;if(st>2)h+=`<span class="stk-pager-info">…</span>`}
    for(let i=st;i<=en;i++)h+=`<button class="${i===pg.page?'active':''}" onclick="loadData(${i})">${i}</button>`;
    if(en<pg.pages){if(en<pg.pages-1)h+=`<span class="stk-pager-info">…</span>`;h+=`<button onclick="loadData(${pg.pages})">${pg.pages}</button>`}
    h+=`<button ${pg.page>=pg.pages?'disabled':''} onclick="loadData(${pg.page+1})"><i class="fas fa-chevron-right"></i></button>`;
    h+=`<span class="stk-pager-info">${T('หน้า '+pg.page+'/'+pg.pages+' • '+num(pg.total)+' รายการ','Page '+pg.page+'/'+pg.pages+' • '+num(pg.total)+' items')}</span>`;
    document.getElementById('pagerArea').innerHTML=h;
}

/* ═════════════════════════════════════════
   DETAIL MODAL
   ═════════════════════════════════════════ */
async function openDetail(id){
    const ov=document.getElementById('detailOv');
    const md=document.getElementById('detailModal');
    md.innerHTML='<div style="padding:60px;text-align:center;color:var(--c3)"><i class="fas fa-spinner fa-spin" style="font-size:24px"></i></div>';
    ov.classList.add('show');
    try{
        const d=await apiFetch('/v1/api/containers.php?action=detail&id='+id);
        if(!d.success)throw new Error(d.error);
        const c=d.data;
        const pct=parseFloat(c.remaining_percentage)||0;
        const ar=c.ar_data||{};
        const isExp=c.expiry_date&&new Date(c.expiry_date)<new Date();
        const hazards=(c.hazard_pictograms||[]);
        const history=c.history||[];

        // GHS diamonds
        const ghsHtml=hazards.length?`<div class="ghs-row">${hazards.map(hp=>
            `<div class="ghs-diamond ghs-${hp}" title="${ghsLabelsMap[hp]||hp}">
                <div class="ghs-diamond-inner"><i class="fas ${ghsTinyIcons[hp]||'fa-exclamation'}"></i></div>
                <div class="ghs-tooltip">${ghsLabelsMap[hp]||hp}</div>
            </div>`
        ).join('')}</div>`:'';

        // Signal word
        const signalHtml=c.signal_word?
            (c.signal_word==='Danger'
                ?`<span class="signal-danger"><i class="fas fa-radiation"></i> ${T('อันตราย','DANGER')}</span>`
                :`<span class="signal-warning"><i class="fas fa-exclamation-triangle"></i> ${T('ระวัง','WARNING')}</span>`)
            :'';

        // GHS classifications
        const ghsClassHtml=(c.ghs_classifications||[]).length?
            `<div style="display:flex;gap:4px;flex-wrap:wrap;margin-top:6px">${c.ghs_classifications.map(g=>
                `<span style="font-size:9px;padding:2px 7px;border-radius:5px;background:#fef2f2;color:#991b1b;font-weight:600;border:1px solid #fecaca">${esc(g)}</span>`
            ).join('')}</div>`:'';

        // 3D Viewer
        let viewer3d='';
        if(ar.has_model){
            const arBtn=`<a href="/v1/ar/view_ar.php?id=${c.id}" target="_blank" class="ar-btn" onclick="event.stopPropagation()"><i class="fas fa-vr-cardboard"></i> ${T('ดู AR','View AR')}</a>`;
            if(ar.model_type==='embed'){
                viewer3d=`<div class="stk-3d-viewer">
                    <div class="stk-3d-label"><span><i class="fas fa-cube"></i> 3D Preview</span>${signalHtml?'<span>'+signalHtml+'</span>':''}</div>
                    <iframe src="${ar.model_url}" allow="autoplay; fullscreen" allowfullscreen></iframe>
                    <div class="stk-3d-actions"><button onclick="window.open('${ar.model_url}','_blank')"><i class="fas fa-expand"></i> ${T('เต็มจอ','Fullscreen')}</button>${arBtn}</div>
                </div>`;
            }else{
                viewer3d=`<div class="stk-3d-viewer">
                    <div class="stk-3d-label"><span><i class="fas fa-cube"></i> 3D Preview</span>${signalHtml?'<span>'+signalHtml+'</span>':''}</div>
                    <iframe src="/v1/pages/viewer3d.php?src=${encodeURIComponent(ar.model_url)}&embed=1&transparent=0&title=${encodeURIComponent(c.chemical_name||'')}" style="width:100%;height:100%;border:none"></iframe>
                    <div class="stk-3d-actions"><button onclick="window.open('/v1/pages/viewer3d.php?src=${encodeURIComponent(ar.model_url)}&title=${encodeURIComponent(c.chemical_name||'')}','_blank')"><i class="fas fa-expand"></i> ${T('เต็มจอ','Fullscreen')}</button>${arBtn}</div>
                </div>`;
            }
        }else{
            viewer3d=`<div class="stk-3d-viewer">
                <div class="stk-3d-label"><span><i class="fas fa-cube"></i> 3D Preview</span></div>
                <div class="no-model">
                    <i class="fas fa-cube"></i>
                    <p>${T('ยังไม่มีโมเดล 3D สำหรับบรรจุภัณฑ์นี้','No 3D model available for this container type')}</p>
                    <a href="/v1/ar/view_ar.php?id=${c.id}" target="_blank" style="margin-top:4px;font-size:11px;color:#0d9488;text-decoration:none;display:flex;align-items:center;gap:4px"><i class="fas fa-vr-cardboard"></i> ${T('ลองดูใน AR','Try AR View')}</a>
                </div>
            </div>`;
        }

        // Fluid level color
        const fluidColor=pct>50?'linear-gradient(to top,#0d9488,#14b8a6)':pct>20?'linear-gradient(to top,#eab308,#fbbf24)':'linear-gradient(to top,#ef4444,#f87171)';

        // Chemical properties
        const propsData=[];
        if(c.molecular_formula)propsData.push({v:c.molecular_formula,l:T('สูตร','Formula')});
        if(c.molecular_weight)propsData.push({v:parseFloat(c.molecular_weight).toFixed(2),l:'MW (g/mol)'});
        if(c.physical_state){
            const stateMap={solid:T('ของแข็ง','Solid'),liquid:T('ของเหลว','Liquid'),gas:T('ก๊าซ','Gas'),powder:T('ผง','Powder'),solution:T('สารละลาย','Solution')};
            propsData.push({v:stateMap[c.physical_state]||c.physical_state,l:T('สถานะ','State')});
        }
        propsData.push({v:`${pct.toFixed(0)}%`,l:T('คงเหลือ','Remaining')});
        propsData.push({v:`${c.current_quantity||0} ${esc(c.quantity_unit||'')}`,l:T('ปริมาณ','Qty')});

        const propsHtml=propsData.length?`<div class="stk-chem-props">${propsData.map(pp=>
            `<div class="stk-chem-prop"><div class="prop-v">${pp.v}</div><div class="prop-l">${pp.l}</div></div>`
        ).join('')}</div>`:'';

        md.innerHTML=`
        <div class="stk-mh">
            <h3><i class="fas fa-flask" style="color:var(--accent)"></i> ${esc(c.chemical_name||'Container')} ${badgeHtml(c.status||'active')} ${srcBadge(c.source)}</h3>
            <button class="stk-mx" onclick="closeDetail()"><i class="fas fa-times"></i></button>
        </div>
        <div class="stk-mb">
            ${viewer3d}

            <div class="stk-chem-card">
                <div class="stk-chem-header">
                    <div style="flex-shrink:0">
                        <div class="stk-fluid" style="width:48px;height:76px">
                            <div class="stk-fluid-fill" style="height:${pct}%;background:${fluidColor}"></div>
                            <div class="stk-fluid-pct">${pct.toFixed(0)}%</div>
                        </div>
                    </div>
                    <div class="stk-chem-body">
                        <div class="stk-chem-name">${esc(c.chemical_name||'-')}</div>
                        ${c.cas_number?`<div class="stk-chem-sub">CAS: <b>${c.cas_number}</b></div>`:''}
                        <div class="stk-chem-tags">
                            <span class="stk-card-tag" style="background:#f0fdf4;color:#059669"><i class="fas ${typeIcons[c.container_type]||'fa-box'}" style="font-size:9px"></i> ${typeLabels[c.container_type]||c.container_type||'-'}</span>
                            ${c.container_material?`<span class="stk-card-tag" style="background:#f5f5f4;color:#78716c">${c.container_material}</span>`:''}
                            ${c.grade?`<span class="stk-card-tag" style="background:#ede9fe;color:#6d28d9">${esc(c.grade)}</span>`:''}
                            ${signalHtml}
                        </div>
                        ${ghsHtml}
                        ${ghsClassHtml}
                    </div>
                </div>
                ${propsHtml}
            </div>

            <div class="stk-dg" style="margin-bottom:16px">
                <div><div class="stk-dlb">${T('รหัสขวด','Bottle Code')}</div><div class="stk-dvl"><code style="font-size:12px;background:#f1f5f9;padding:2px 8px;border-radius:4px;font-family:'Courier New',monospace;letter-spacing:0.5px">${esc(c.bottle_code||'-')}</code></div></div>
                <div><div class="stk-dlb">QR Code</div><div class="stk-dvl" style="font-family:monospace;font-size:11px">${esc(c.qr_code||'-')}</div></div>
                <div><div class="stk-dlb">${T('เจ้าของ','Owner')}</div><div class="stk-dvl"><i class="fas fa-user" style="color:var(--accent);font-size:11px;margin-right:4px"></i>${esc(c.owner_name||'-')}</div></div>
                <div><div class="stk-dlb">${T('ตำแหน่ง','Location')}</div><div class="stk-dvl"><i class="fas fa-map-marker-alt" style="color:#dc2626;font-size:11px;margin-right:4px"></i>${esc(c.location_text||'-')}</div></div>
                <div><div class="stk-dlb">${T('ห้อง/แลป','Lab')}</div><div class="stk-dvl">${esc(c.lab_name||'-')}</div></div>
                ${c.manufacturer_name?`<div><div class="stk-dlb">${T('ผู้ผลิต','Manufacturer')}</div><div class="stk-dvl">${esc(c.manufacturer_name)}</div></div>`:''}
                ${c.cost?`<div><div class="stk-dlb">${T('ราคา','Cost')}</div><div class="stk-dvl" style="color:#16a34a;font-weight:700">${parseFloat(c.cost).toLocaleString()} ฿</div></div>`:''}
                ${c.batch_number?`<div><div class="stk-dlb">Batch No.</div><div class="stk-dvl" style="font-family:monospace">${esc(c.batch_number)}</div></div>`:''}
                ${c.lot_number?`<div><div class="stk-dlb">Lot No.</div><div class="stk-dvl" style="font-family:monospace">${esc(c.lot_number)}</div></div>`:''}
                ${c.expiry_date?`<div><div class="stk-dlb">${T('วันหมดอายุ','Expiry')}</div><div class="stk-dvl" style="${isExp?'color:#dc2626;font-weight:700':''}"><i class="fas fa-calendar-alt" style="font-size:10px;margin-right:4px"></i>${fmtDate(c.expiry_date)}${isExp?' <span style="font-size:10px">⚠️ '+(T('หมดอายุแล้ว','Expired'))+'</span>':''}</div></div>`:''}
                ${c.received_date?`<div><div class="stk-dlb">${T('วันที่รับ','Received')}</div><div class="stk-dvl">${fmtDate(c.received_date)}</div></div>`:''}
                ${c.invoice_number?`<div><div class="stk-dlb">${T('เลขที่ใบแจ้งหนี้','Invoice')}</div><div class="stk-dvl">${esc(c.invoice_number)}</div></div>`:''}
                ${c.notes?`<div class="stk-df"><div class="stk-dlb">${T('หมายเหตุ','Notes')}</div><div class="stk-dvl">${esc(c.notes)}</div></div>`:''}
            </div>

            ${history.length?`
            <div style="padding-top:16px;border-top:1px solid var(--border)">
                <h4 style="font-size:12px;font-weight:700;color:var(--c1);margin-bottom:12px;display:flex;align-items:center;gap:6px"><i class="fas fa-history" style="color:var(--accent)"></i> ${T('ประวัติ','History')} <span style="font-size:10px;color:var(--c3);font-weight:400">(${history.length})</span></h4>
                <div class="stk-tl">${history.map(hi=>`
                    <div class="stk-tl-item ${hi.action_type||''}">
                        <div class="stk-tl-act">${hi.action_type||'-'}</div>
                        <div class="stk-tl-det">${esc(hi.notes||'')} ${hi.quantity_change?'(<span style=\"font-weight:700;color:'+(parseFloat(hi.quantity_change)<0?'#dc2626':'#16a34a')+'\">'+(parseFloat(hi.quantity_change)>0?'+':'')+hi.quantity_change+'</span>)':''} — ${esc(hi.user_name||'')}</div>
                        <div class="stk-tl-time"><i class="fas fa-clock" style="font-size:9px;margin-right:3px"></i>${fmtDate(hi.created_at)}</div>
                    </div>
                `).join('')}</div>
            </div>`:''}

            <div class="stk-da" style="padding-top:14px;border-top:1px solid var(--border)">
                <button onclick="showQRModal({id:${c.id},qr_code:'${esc(c.qr_code||'')}',bottle_code:'${esc(c.bottle_code||'')}',chemical_name:'${esc(c.chemical_name||'')}'})" class="stk-btn stk-btn-o stk-btn-s"><i class="fas fa-qrcode"></i> QR Code</button>
                <button onclick="doPrintSingleLabel(${c.id})" class="stk-btn stk-btn-s stk-btn-g"><i class="fas fa-tag"></i> ${T('พิมพ์ฉลาก','Print Label')}</button>
                ${c.sds_url?`<a href="${c.sds_url}" target="_blank" class="stk-btn stk-btn-g stk-btn-s"><i class="fas fa-file-pdf"></i> SDS</a>`:''}
                ${ar.has_model?`<button onclick="window.open('/v1/pages/viewer3d.php?src=${encodeURIComponent(ar.model_url||'')}&title=${encodeURIComponent(c.chemical_name||'')}','_blank')" class="stk-btn stk-btn-s" style="background:#6C5CE7;color:#fff"><i class="fas fa-cube"></i> ${T('ดู 3D','3D View')}</button>`:''}
                <a href="/v1/ar/view_ar.php?id=${c.id}" target="_blank" class="stk-btn stk-btn-s" style="background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff"><i class="fas fa-vr-cardboard"></i> ${T('ดู AR','AR View')}</a>
            </div>
        </div>`;
    }catch(e){
        md.innerHTML=`<div style="padding:40px;text-align:center;color:#dc2626"><i class="fas fa-exclamation-triangle" style="font-size:24px;margin-bottom:8px"></i><p>${e.message}</p><button class="stk-btn stk-btn-g" onclick="closeDetail()" style="margin-top:12px">Close</button></div>`;
    }
}
function closeDetail(){document.getElementById('detailOv').classList.remove('show')}
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeDetail()});

/* ═════════════════════════════════════════
   TABS / VIEWS / FILTER
   ═════════════════════════════════════════ */
function switchTab(tab){
    TAB=tab;
    document.querySelectorAll('.stk-tab').forEach(b=>b.classList.toggle('active',b.dataset.tab===tab));
    document.getElementById('myBanner').style.display=tab==='my'?'flex':'none';
    if(tab==='my'&&STATS){
        document.getElementById('myStatTotal').textContent=num(STATS.my_total||0);
        document.getElementById('myStatActive').textContent=num(STATS.my_active||0);
    }
    loadData(1);
}
function setView(v){
    VIEW=v;
    document.querySelectorAll('#viewSw button').forEach(b=>b.classList.toggle('active',b.dataset.view===v));
    document.getElementById('toolbar').style.display=v==='analytics'?'none':'flex';
    document.getElementById('filterPanel').classList.remove('show');
    renderView();
    if(v==='analytics')document.getElementById('pagerArea').innerHTML='';
}
function toggleFilter(){const p=document.getElementById('filterPanel');p.classList.toggle('show');document.getElementById('filterToggle').classList.toggle('active',p.classList.contains('show'))}
function clearFilters(){document.getElementById('fStatus').value='';document.getElementById('fType').value='';document.getElementById('fBuilding').value='';document.getElementById('fSource').value='';document.getElementById('sortSelect').value='newest';document.getElementById('searchInput').value='';loadData(1)}

/* ═════════════════════════════════════════
   BUILDING FILTER
   ═════════════════════════════════════════ */
async function loadBuildingFilter(){
    try{
        const d=await apiFetch('/v1/api/locations.php?type=buildings');
        if(d.success){
            const sel=document.getElementById('fBuilding');
            d.data.forEach(b=>{const o=document.createElement('option');o.value=b.id;o.textContent=b.shortname||b.name;sel.appendChild(o)});
        }
    }catch(e){}
}

/* ═════════════════════════════════════════
   EVENT LISTENERS
   ═════════════════════════════════════════ */
document.getElementById('sortSelect').addEventListener('change',()=>loadData(1));
document.getElementById('fStatus').addEventListener('change',()=>loadData(1));
document.getElementById('fType').addEventListener('change',()=>loadData(1));
document.getElementById('fBuilding').addEventListener('change',()=>loadData(1));
document.getElementById('fSource').addEventListener('change',()=>loadData(1));

/* ═════════════════════════════════════════
   HELPERS
   ═════════════════════════════════════════ */
function esc(s){if(!s)return '';const d=document.createElement('div');d.textContent=String(s);return d.innerHTML}
function num(n){return(n||0).toLocaleString()}
function barCls(p){return p>50?'bar-ok':p>15?'bar-mid':'bar-low'}
function pctColor(p){return p>50?'#16a34a':p>15?'#a16207':'#dc2626'}
function badgeHtml(s){
    const m={active:['stk-badge-active',T('ปกติ','Active')],empty:['stk-badge-empty',T('หมด','Empty')],expired:['stk-badge-expired',T('หมดอายุ','Expired')],quarantined:['stk-badge-quarantined',T('กักกัน','Quarantined')],disposed:['stk-badge-disposed',T('กำจัดแล้ว','Disposed')],low:['stk-badge-low',T('เหลือน้อย','Low')]};
    const [cls,lbl]=m[s]||m.active;
    return `<span class="stk-badge ${cls}">${lbl}</span>`;
}
function fmtDate(d){if(!d)return '—';try{return new Date(d).toLocaleDateString(L==='th'?'th-TH':'en-US',{day:'numeric',month:'short',year:'numeric'})}catch(e){return d}}
function srcBadge(s){return s==='stock'?'<span class="stk-src stk-src-stock">CSV</span>':'<span class="stk-src stk-src-container">SYS</span>'}
function toast(msg,type){const t=document.getElementById('stkToast');t.textContent=msg;t.className='stk-toast '+(type||'')+' show';setTimeout(()=>t.classList.remove('show'),3000)}

/* ═════════════════════════════════════════
   SELECTION / BATCH
   ═════════════════════════════════════════ */
function toggleSelect(id,e){
    e.stopPropagation();
    if(SELECTED.has(id))SELECTED.delete(id);else SELECTED.add(id);
    updateSelectionUI();
}
function toggleSelectAll(e){
    e.stopPropagation();
    if(SELECTED.size===DATA.length){SELECTED.clear()}else{DATA.forEach(r=>SELECTED.add(r.id))}
    updateSelectionUI();renderView();
}
function clearSelection(){SELECTED.clear();updateSelectionUI();renderView()}

/* ═════════════════════════════════════════
   SINGLE LABEL PRINT
   ═════════════════════════════════════════ */
async function doPrintSingleLabel(id){
    try{
        const d=await apiFetch('/v1/api/containers.php?action=detail&id='+id);
        if(!d.success)throw new Error(d.error);
        const r=d.data;
        r.has_3d=!!(r.ar_data&&r.ar_data.has_model);
        r.hazard_pictograms=r.hazard_pictograms||[];
        
        const labelOv=document.getElementById('labelOv');
        const labelContent=document.getElementById('labelContent');
        labelOv.classList.add('show');
        
        let h=`<div style="display:flex;gap:8px;margin-bottom:16px;align-items:center">
            <span style="font-size:13px;font-weight:700;color:var(--c1)"><i class="fas fa-tag" style="color:var(--accent)"></i> ${T('ฉลากสำหรับ','Label for')} ${esc(r.chemical_name||'')}</span>
            <button class="stk-btn stk-btn-p" onclick="printLabels()"><i class="fas fa-print"></i> ${T('พิมพ์','Print')}</button>
            <button class="stk-btn stk-btn-g" onclick="closeLabelModal()"><i class="fas fa-times"></i> ${T('ปิด','Close')}</button>
        </div>`;
        h+='<div id="labelGrid" style="max-width:440px;margin:0 auto">';
        h+=generateLabelHtml(r);
        h+='</div>';
        labelContent.innerHTML=h;
        
        await loadExternalLibs();
        setTimeout(()=>{
            renderQRCode('qrLabel_'+r.id, window.location.origin+'/v1/ar/view_ar.php?id='+r.id, 70);
            renderBarcode('barcode_'+r.id, r.bottle_code||('ID'+r.id));
        },200);
    }catch(e){
        toast(T('❌ ไม่สามารถสร้างฉลากได้','❌ Cannot generate label'),'err');
    }
}

function updateSelectionUI(){
    const bar=document.getElementById('batchBar');
    const cnt=document.getElementById('selCount');
    if(SELECTED.size>0){bar.style.display='flex';cnt.textContent=SELECTED.size}else{bar.style.display='none'}
    // Update checkboxes
    document.querySelectorAll('.stk-chk').forEach(el=>{el.checked=SELECTED.has(parseInt(el.dataset.id))});
    const allCb=document.getElementById('chkAll');
    if(allCb)allCb.checked=SELECTED.size===DATA.length&&DATA.length>0;
}

/* ═════════════════════════════════════════
   EXPORT DROPDOWN
   ═════════════════════════════════════════ */
function toggleExportMenu(e){
    e.stopPropagation();
    const m=document.getElementById('exportMenu');m.classList.toggle('show');
    const close=()=>{m.classList.remove('show');document.removeEventListener('click',close)};
    if(m.classList.contains('show'))setTimeout(()=>document.addEventListener('click',close),10);
}

/* ═════════════════════════════════════════
   EXPORT FUNCTIONS
   ═════════════════════════════════════════ */
function doExport(format){
    document.getElementById('exportMenu').classList.remove('show');
    if(format==='csv'){
        const s=document.getElementById('searchInput').value.trim();
        const a=document.createElement('a');
        a.href='/v1/api/containers.php?action=export&search='+encodeURIComponent(s);
        a.target='_blank';document.body.appendChild(a);a.click();a.remove();
        toast(T('📥 กำลังดาวน์โหลด CSV...','📥 Downloading CSV...'),'ok');
    }else if(format==='pdf_report'){
        generatePDFReport();
    }
}

function generatePDFReport(){
    const items=DATA;
    if(!items.length){toast(T('ไม่มีข้อมูลสำหรับรายงาน','No data for report'),'err');return}
    
    const now=new Date().toLocaleString(L==='th'?'th-TH':'en-US');
    const title=T('รายงานสรุปคลังสารเคมี','Chemical Stock Summary Report');
    
    let html=`<html><head><meta charset="utf-8"><title>${title}</title>
    <style>
    *{margin:0;padding:0;box-sizing:border-box}body{font-family:'Sarabun',sans-serif;padding:20px;font-size:11px;color:#333}
    .rpt-hdr{text-align:center;border-bottom:3px solid #065f46;padding-bottom:12px;margin-bottom:16px}
    .rpt-hdr h1{font-size:18px;color:#065f46;font-weight:800}.rpt-hdr p{font-size:10px;color:#666;margin-top:4px}
    .rpt-stats{display:flex;gap:12px;justify-content:center;margin-bottom:16px}
    .rpt-stat{text-align:center;padding:8px 16px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px}
    .rpt-stat .v{font-size:20px;font-weight:800;color:#065f46}.rpt-stat .l{font-size:9px;color:#666;text-transform:uppercase}
    table{width:100%;border-collapse:collapse;font-size:10px;margin-top:10px}
    th{background:#065f46;color:#fff;padding:6px 8px;text-align:left;font-weight:700;font-size:9px;text-transform:uppercase;letter-spacing:.3px}
    td{padding:5px 8px;border-bottom:1px solid #e2e8f0}tr:nth-child(even){background:#f8fafc}
    .badge{padding:2px 6px;border-radius:4px;font-size:8px;font-weight:700;text-transform:uppercase}
    .b-active{background:#dcfce7;color:#15803d}.b-empty{background:#fee2e2;color:#dc2626}.b-expired{background:#fce7f3;color:#be185d}
    .bar{width:50px;height:5px;background:#e2e8f0;border-radius:3px;display:inline-block;vertical-align:middle;overflow:hidden}
    .bar div{height:100%;border-radius:3px}
    .hz{font-size:8px;color:#dc2626}.footer{text-align:center;margin-top:20px;font-size:8px;color:#999;border-top:1px solid #e2e8f0;padding-top:8px}
    @media print{@page{size:A4 landscape;margin:10mm}}
    </style></head><body>
    <div class="rpt-hdr">
        <h1>🧪 ${title}</h1>
        <p>${T('วันที่พิมพ์','Printed')}: ${now} | ${T('จำนวน','Count')}: ${items.length} ${T('รายการ','items')} | ${T('ผู้จัดทำ','By')}: ${USER_NAME}</p>
    </div>`;
    
    if(STATS){
        html+=`<div class="rpt-stats">
            <div class="rpt-stat"><div class="v">${num(STATS.total)}</div><div class="l">${T('ทั้งหมด','Total')}</div></div>
            <div class="rpt-stat"><div class="v">${num(STATS.active)}</div><div class="l">${T('ปกติ','Active')}</div></div>
            <div class="rpt-stat"><div class="v">${num(STATS.low)}</div><div class="l">${T('เหลือน้อย','Low')}</div></div>
            <div class="rpt-stat"><div class="v">${num(STATS.expiring_soon)}</div><div class="l">${T('ใกล้หมดอายุ','Expiring')}</div></div>
        </div>`;
    }
    
    html+=`<table><thead><tr>
        <th>#</th><th>${T('รหัสขวด','Code')}</th><th>${T('สารเคมี','Chemical')}</th><th>CAS</th>
        <th>${T('ประเภท','Type')}</th><th>${T('ปริมาณ','Qty')}</th><th>%</th>
        <th>${T('สถานะ','Status')}</th><th>${T('อันตราย','Hazard')}</th>
        <th>${T('เจ้าของ','Owner')}</th><th>${T('ตำแหน่ง','Location')}</th>
    </tr></thead><tbody>`;
    
    items.forEach((r,i)=>{
        const p=parseFloat(r.remaining_percentage)||0;
        const bc=p>50?'#22c55e':p>15?'#eab308':'#ef4444';
        const haz=(r.hazard_pictograms||[]).join(', ');
        const sc={active:'b-active',empty:'b-empty',expired:'b-expired'}[r.status]||'b-active';
        html+=`<tr>
            <td>${i+1}</td>
            <td style="font-family:monospace;font-size:9px">${esc(r.bottle_code||'')}</td>
            <td style="font-weight:600">${esc(r.chemical_name||'-')}</td>
            <td>${esc(r.cas_number||'')}</td>
            <td>${r.container_type||'-'}</td>
            <td>${r.current_quantity||0}/${r.initial_quantity||0} ${esc(r.quantity_unit||'')}</td>
            <td><div class="bar"><div style="width:${p}%;background:${bc}"></div></div> ${p.toFixed(0)}%</td>
            <td><span class="badge ${sc}">${r.status||'active'}</span></td>
            <td class="hz">${haz||'—'}</td>
            <td>${esc(r.owner_name||'-')}</td>
            <td>${esc(r.location_text||'-')}</td>
        </tr>`;
    });
    
    html+=`</tbody></table>
    <div class="footer">SUT chemBot — ${T('ระบบจัดการคลังสารเคมี','Chemical Inventory Management System')} | ${now}</div>
    </body></html>`;
    
    const w=window.open('','_blank','width=1100,height=800');
    w.document.write(html);w.document.close();
    setTimeout(()=>w.print(),500);
    toast(T('📊 กำลังสร้างรายงาน...','📊 Generating report...'),'ok');
}

/* ═════════════════════════════════════════
   LABEL GENERATION
   ═════════════════════════════════════════ */
function generateLabelHtml(r){
    const p=parseFloat(r.remaining_percentage)||0;
    const haz=(r.hazard_pictograms||[]);
    const arUrl=window.location.origin+'/v1/ar/view_ar.php?id='+r.id;
    const qrVal=r.qr_code||('CHEM-'+r.id);
    const now=new Date().toLocaleDateString(L==='th'?'th-TH':'en-US',{day:'numeric',month:'short',year:'numeric'});
    
    let ghsHtml='';
    if(haz.length){
        ghsHtml='<div class="stk-label-ghsstrip">'+haz.slice(0,6).map(hp=>
            `<div class="stk-label-ghs"><div class="stk-label-ghs-inner" style="border-color:${ghsTinyColors[hp]||'#dc2626'};color:${ghsTinyColors[hp]||'#dc2626'}"><i class="fas ${ghsTinyIcons[hp]||'fa-exclamation'}"></i></div></div>`
        ).join('')+'</div>';
    }
    
    const signalHtml=r.signal_word?
        (r.signal_word==='Danger'?'<div class="stk-label-signal danger">⚠ DANGER</div>':
        '<div class="stk-label-signal warning">⚠ WARNING</div>'):'';

    return `<div class="stk-label" data-id="${r.id}">
        ${r.has_3d?'<div class="stk-label-ar"><i class="fas fa-cube"></i> AR/3D</div>':''}
        <div class="stk-label-header">
            <div class="lab-logo"><i class="fas fa-flask"></i></div>
            <div>
                <div class="lab-title">SUT chemBot — ${T('คลังสารเคมี','Chemical Stock')}</div>
                <div class="lab-sub">${esc(r.location_text||'-')} ${r.lab_name?' | '+esc(r.lab_name):''}</div>
            </div>
        </div>
        <div class="stk-label-chem">${esc(r.chemical_name||'-')}</div>
        ${r.cas_number?'<div class="stk-label-cas">CAS: '+r.cas_number+'</div>':''}
        ${signalHtml}
        ${ghsHtml}
        <div class="stk-label-row"><span>${T('ประเภท','Type')}: <b>${r.container_type||'-'}</b></span><span>${T('เกรด','Grade')}: <b>${esc(r.grade||'-')}</b></span></div>
        <div class="stk-label-row"><span>${T('ปริมาณ','Qty')}: <b>${r.current_quantity||0} / ${r.initial_quantity||0} ${esc(r.quantity_unit||'')}</b></span><span>${T('คงเหลือ','Rem')}: <b style="color:${pctColor(p)}">${p.toFixed(0)}%</b></span></div>
        ${r.expiry_date?'<div class="stk-label-row"><span>'+T('หมดอายุ','Exp')+': <b>'+fmtDate(r.expiry_date)+'</b></span><span>'+T('เจ้าของ','Owner')+': <b>'+esc(r.owner_name||'-')+'</b></span></div>':''}
        <div class="stk-label-codes">
            <div class="stk-label-qr" id="qrLabel_${r.id}"></div>
            <div class="stk-label-barcode">
                <svg id="barcode_${r.id}"></svg>
                <div class="stk-label-barcode-text">${esc(r.bottle_code||'')}</div>
            </div>
        </div>
        <div class="stk-label-footer">
            <span>${T('พิมพ์','Printed')}: ${now}</span>
            <span>ID: ${r.id} | ${esc(r.bottle_code||'')}</span>
            <span>${T('แสกน QR เพื่อดู AR','Scan QR for AR')}</span>
        </div>
    </div>`;
}

async function doPrintLabels(mode){
    document.getElementById('exportMenu').classList.remove('show');
    let items=[];
    
    if(mode==='selected'||mode==='qr_selected'){
        if(SELECTED.size===0){toast(T('กรุณาเลือกรายการก่อน','Please select items first'),'err');return}
        items=DATA.filter(r=>SELECTED.has(r.id));
    }else if(mode==='all'){
        items=[...DATA];
    }else if(mode==='qr_sheet'){
        items=[...DATA];
    }
    
    if(!items.length){toast(T('ไม่มีข้อมูลสำหรับพิมพ์','No items to print'),'err');return}
    
    if(mode==='qr_sheet'||mode==='qr_selected'){
        await generateQRSheet(items);return;
    }
    
    // Full label mode — fetch detail for each item
    const labelOv=document.getElementById('labelOv');
    const labelContent=document.getElementById('labelContent');
    labelOv.classList.add('show');
    labelContent.innerHTML='<div style="text-align:center;padding:40px;color:var(--c3)"><i class="fas fa-spinner fa-spin fa-2x"></i><p style="margin-top:8px">'+T('กำลังสร้างฉลาก...','Generating labels...')+'</p></div>';
    
    try{
        // Fetch full details for each item to get signal_word etc
        const detailedItems=[];
        for(const item of items){
            try{
                const d=await apiFetch('/v1/api/containers.php?action=detail&id='+item.id);
                if(d.success){
                    const det=d.data;
                    det.has_3d=!!(det.ar_data&&det.ar_data.has_model);
                    det.hazard_pictograms=det.hazard_pictograms||[];
                    det.location_text=det.location_text||item.location_text;
                    det.is_mine=item.is_mine;
                    detailedItems.push(det);
                }else{
                    detailedItems.push(item);
                }
            }catch(e){detailedItems.push(item)}
        }
        
        let h=`<div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;align-items:center">
            <span style="font-size:13px;font-weight:700;color:var(--c1)"><i class="fas fa-tag" style="color:var(--accent)"></i> ${detailedItems.length} ${T('ฉลาก','labels')}</span>
            <button class="stk-btn stk-btn-p" onclick="printLabels()"><i class="fas fa-print"></i> ${T('พิมพ์ทั้งหมด','Print All')}</button>
            <button class="stk-btn stk-btn-g" onclick="closeLabelModal()"><i class="fas fa-times"></i> ${T('ปิด','Close')}</button>
        </div>`;
        h+='<div id="labelGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(380px,1fr));gap:16px">';
        detailedItems.forEach(r=>{h+=generateLabelHtml(r)});
        h+='</div>';
        labelContent.innerHTML=h;
        
        // Render QR codes and barcodes after DOM update
        await loadExternalLibs();
        setTimeout(()=>{
            detailedItems.forEach(r=>{
                renderQRCode('qrLabel_'+r.id, window.location.origin+'/v1/ar/view_ar.php?id='+r.id, 70);
                renderBarcode('barcode_'+r.id, r.bottle_code||('ID'+r.id));
            });
        },200);
    }catch(e){
        labelContent.innerHTML=`<div class="stk-empty"><i class="fas fa-exclamation-triangle"></i><p>${e.message}</p></div>`;
    }
}

async function generateQRSheet(items){
    const labelOv=document.getElementById('labelOv');
    const labelContent=document.getElementById('labelContent');
    labelOv.classList.add('show');
    labelContent.innerHTML='<div style="text-align:center;padding:40px;color:var(--c3)"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
    
    await loadExternalLibs();
    
    let h=`<div style="display:flex;gap:8px;margin-bottom:16px;align-items:center">
        <span style="font-size:13px;font-weight:700"><i class="fas fa-qrcode" style="color:var(--accent)"></i> ${items.length} QR Codes</span>
        <button class="stk-btn stk-btn-p" onclick="printLabels()"><i class="fas fa-print"></i> ${T('พิมพ์','Print')}</button>
        <button class="stk-btn stk-btn-g" onclick="closeLabelModal()"><i class="fas fa-times"></i> ${T('ปิด','Close')}</button>
    </div>`;
    h+='<div id="labelGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:12px">';
    items.forEach(r=>{
        h+=`<div style="background:#fff;border:1.5px solid var(--border);border-radius:10px;padding:12px;text-align:center">
            <div id="qrSheet_${r.id}" style="width:100px;height:100px;margin:0 auto 6px"></div>
            <div style="font-size:9px;font-weight:700;color:var(--c1);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-bottom:2px">${esc(r.chemical_name||'-')}</div>
            <div style="font-family:'Courier New',monospace;font-size:8px;color:var(--c3);letter-spacing:0.3px">${esc(r.bottle_code||'')}</div>
            <div style="margin-top:4px"><svg id="qrBar_${r.id}"></svg></div>
        </div>`;
    });
    h+='</div>';
    labelContent.innerHTML=h;
    
    setTimeout(()=>{
        items.forEach(r=>{
            renderQRCode('qrSheet_'+r.id, window.location.origin+'/v1/ar/view_ar.php?id='+r.id, 90);
            renderBarcode('qrBar_'+r.id, r.bottle_code||('ID'+r.id), {height:25,fontSize:0,width:1});
        });
    },200);
}

function printLabels(){
    const grid=document.getElementById('labelGrid');
    if(!grid)return;
    const w=window.open('','_blank','width=900,height=700');
    w.document.write(`<html><head><meta charset="utf-8"><title>${T('ฉลากสารเคมี','Chemical Labels')}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
    *{margin:0;padding:0;box-sizing:border-box}body{padding:10px;font-family:'Courier New',monospace;font-size:11px}
    .grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px}
    .stk-label{border:2px solid #333;border-radius:6px;padding:14px;break-inside:avoid;position:relative;page-break-inside:avoid}
    .stk-label-header{display:flex;align-items:center;gap:8px;border-bottom:2px solid #333;padding-bottom:6px;margin-bottom:8px}
    .lab-logo{width:28px;height:28px;background:#065f46;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:12px}
    .lab-title{font-size:9px;font-weight:700}.lab-sub{font-size:7px;color:#666}
    .stk-label-chem{font-size:13px;font-weight:900;margin-bottom:2px}
    .stk-label-cas{font-size:9px;color:#444;margin-bottom:4px}
    .stk-label-row{display:flex;justify-content:space-between;font-size:8px;color:#444;margin-bottom:1px}
    .stk-label-row b{color:#000}
    .stk-label-ghsstrip{display:flex;gap:3px;margin:4px 0}
    .stk-label-ghs{width:18px;height:18px;position:relative}
    .stk-label-ghs-inner{position:absolute;inset:1px;transform:rotate(45deg);border:1px solid #dc2626;border-radius:1px;display:flex;align-items:center;justify-content:center;font-size:7px}
    .stk-label-ghs-inner i{transform:rotate(-45deg)}
    .stk-label-signal{font-size:8px;font-weight:900;padding:1px 4px;border-radius:2px;text-transform:uppercase;display:inline-block;margin-bottom:4px}
    .stk-label-signal.danger{background:#dc2626;color:#fff}
    .stk-label-signal.warning{background:#f59e0b;color:#000}
    .stk-label-codes{display:flex;gap:8px;align-items:flex-end;border-top:2px solid #333;padding-top:6px;margin-top:6px}
    .stk-label-qr{width:70px;height:70px;flex-shrink:0}
    .stk-label-qr canvas,.stk-label-qr img{max-width:100%;max-height:100%}
    .stk-label-barcode{flex:1;text-align:center;overflow:hidden}
    .stk-label-barcode svg{max-width:100%;height:35px!important}
    .stk-label-barcode-text{font-size:7px;margin-top:1px;letter-spacing:.8px;font-weight:700}
    .stk-label-footer{border-top:1px dashed #999;padding-top:3px;margin-top:4px;font-size:6px;color:#999;display:flex;justify-content:space-between}
    .stk-label-ar{position:absolute;top:6px;right:6px;background:#6C5CE7;color:#fff;font-size:6px;padding:1px 4px;border-radius:3px;font-weight:800}
    @page{size:A4;margin:10mm}
    </style></head><body><div class="grid">`+grid.innerHTML+`</div></body></html>`);
    w.document.close();
    setTimeout(()=>w.print(),800);
}

function closeLabelModal(){document.getElementById('labelOv').classList.remove('show')}
function closeQrModal(){document.getElementById('qrOv').classList.remove('show')}

/* ═════════════════════════════════════════
   QR CODE & BARCODE RENDERING
   ═════════════════════════════════════════ */
let libsLoaded=false;
function loadExternalLibs(){
    if(libsLoaded)return Promise.resolve();
    return new Promise((res)=>{
        let loaded=0;const total=2;
        const done=()=>{loaded++;if(loaded>=total){libsLoaded=true;res()}};
        
        // JsBarcode
        if(typeof JsBarcode!=='undefined'){done()}else{
            const s1=document.createElement('script');
            s1.src='https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js';
            s1.onload=done;s1.onerror=done;document.head.appendChild(s1);
        }
        // QRCode.js
        if(typeof QRCode!=='undefined'){done()}else{
            const s2=document.createElement('script');
            s2.src='https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js';
            s2.onload=done;s2.onerror=done;document.head.appendChild(s2);
        }
    });
}

function renderQRCode(elId,data,size){
    const el=document.getElementById(elId);if(!el)return;
    el.innerHTML='';
    try{
        if(typeof QRCode!=='undefined'){
            new QRCode(el,{text:data,width:size||80,height:size||80,colorDark:'#000000',colorLight:'#ffffff',correctLevel:QRCode.CorrectLevel.M});
        }else{
            el.innerHTML=`<div style="width:${size||80}px;height:${size||80}px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;border-radius:6px;font-size:20px;color:var(--c3)"><i class="fas fa-qrcode"></i></div>`;
        }
    }catch(e){
        el.innerHTML='<i class="fas fa-qrcode" style="font-size:24px;color:#ccc"></i>';
    }
}

function renderBarcode(elId,data,opts){
    const el=document.getElementById(elId);if(!el)return;
    try{
        if(typeof JsBarcode!=='undefined'){
            JsBarcode('#'+elId,data,Object.assign({format:'CODE128',height:opts?.height||40,displayValue:false,margin:0,width:opts?.width||1.5,lineColor:'#333'},opts||{}));
        }
    }catch(e){
        el.outerHTML='<div style="font-family:monospace;font-size:8px;color:#666;text-align:center">'+esc(data)+'</div>';
    }
}

/* ═════════════════════════════════════════
   SHOW QR CODE MODAL (from detail)
   ═════════════════════════════════════════ */
async function showQRModal(container){
    const ov=document.getElementById('qrOv');
    const content=document.getElementById('qrContent');
    ov.classList.add('show');
    
    const arUrl=window.location.origin+'/v1/ar/view_ar.php?id='+container.id;
    const qrVal=container.qr_code||('CHEM-'+container.id);
    
    content.innerHTML=`
    <div class="stk-qr-display">
        <div class="qr-big" id="qrModalBig"></div>
        <div class="qr-val">${esc(qrVal)}</div>
        <div style="margin:8px 0"><svg id="qrModalBarcode"></svg></div>
        <div style="font-family:'Courier New',monospace;font-size:11px;color:var(--c2);margin-bottom:12px;letter-spacing:0.5px">${esc(container.bottle_code||'')}</div>
        <div class="qr-hint"><i class="fas fa-mobile-alt" style="color:var(--accent)"></i> ${T('สแกน QR Code เพื่อเปิดดู AR Model ของขวดสารเคมีนี้','Scan this QR Code to view the AR Model of this chemical bottle')}</div>
        <div style="margin-top:14px;display:flex;gap:8px;justify-content:center;flex-wrap:wrap">
            <a href="${arUrl}" target="_blank" class="stk-btn stk-btn-s" style="background:linear-gradient(135deg,#0d9488,#14b8a6);color:#fff"><i class="fas fa-vr-cardboard"></i> ${T('เปิด AR View','Open AR View')}</a>
            <button class="stk-btn stk-btn-s stk-btn-o" onclick="printSingleQR(${container.id},'${esc(container.bottle_code||'')}','${esc(container.chemical_name||'')}')"><i class="fas fa-print"></i> ${T('พิมพ์ QR','Print QR')}</button>
            <button class="stk-btn stk-btn-s stk-btn-g" onclick="downloadQR(${container.id})"><i class="fas fa-download"></i> ${T('ดาวน์โหลด','Download')}</button>
        </div>
    </div>`;
    
    await loadExternalLibs();
    setTimeout(()=>{
        renderQRCode('qrModalBig',arUrl,180);
        renderBarcode('qrModalBarcode',container.bottle_code||('ID'+container.id),{height:50,width:2});
    },200);
}

function printSingleQR(id,code,name){
    const qrEl=document.getElementById('qrModalBig');
    const canvas=qrEl?.querySelector('canvas');
    const img=canvas?canvas.toDataURL():'';
    const barcodeEl=document.getElementById('qrModalBarcode');
    const barcodeHtml=barcodeEl?barcodeEl.outerHTML:'';
    
    const w=window.open('','_blank','width=400,height=500');
    w.document.write(`<html><head><meta charset="utf-8"><title>QR - ${esc(code)}</title>
    <style>body{text-align:center;padding:20px;font-family:'Courier New',monospace}
    img{width:200px;height:200px;margin:10px auto;display:block}
    h3{font-size:14px;margin:4px 0}p{font-size:10px;color:#666}
    svg{max-width:250px;height:50px!important;margin:8px auto;display:block}
    .code{font-size:12px;letter-spacing:1px;font-weight:700;margin:4px 0}
    .hint{font-size:9px;color:#999;margin-top:10px;border-top:1px dashed #ddd;padding-top:8px}
    @page{size:60mm 80mm;margin:5mm}
    </style></head><body>
    <h3>🧪 ${esc(name)}</h3>
    ${img?'<img src="'+img+'">':'<p>QR Code</p>'}
    ${barcodeHtml}
    <div class="code">${esc(code)}</div>
    <p>${T('สแกน QR เพื่อดู AR Model','Scan QR for AR Model')}</p>
    <div class="hint">SUT chemBot | ID: ${id}</div>
    </body></html>`);
    w.document.close();
    setTimeout(()=>w.print(),500);
}

function downloadQR(id){
    const canvas=document.querySelector('#qrModalBig canvas');
    if(!canvas){toast(T('ไม่พบ QR Code','QR Code not found'),'err');return}
    const a=document.createElement('a');
    a.href=canvas.toDataURL('image/png');
    a.download='qrcode_'+id+'.png';
    a.click();
    toast(T('📥 ดาวน์โหลด QR Code แล้ว','📥 QR Code downloaded'),'ok');
}
</script>
</body></html>
