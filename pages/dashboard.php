<?php
/**
 * Dashboard – Premium Bento Grid v3
 * Modern asymmetric grid, glass panels, premium feel
 */
require_once __DIR__ . '/../includes/layout.php';
$user = Auth::getCurrentUser();
if (!$user) { header('Location: /v1/pages/login.php'); exit; }
$lang = I18n::getCurrentLang();
$role = $user['role_name'] ?? 'user';
$roleLevel = (int)($user['role_level'] ?? $user['level'] ?? 0);
$displayName = !empty($user['full_name_th']) ? $user['full_name_th'] : htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
$deptName = htmlspecialchars($user['department_name'] ?? $user['department'] ?? '');
$isAdm = ($role==='admin'||$role==='ceo');
$isMgr = ($role==='lab_manager');
Layout::head(__('nav_dashboard'));
?>
<style>
/* ============================================
   PREMIUM BENTO GRID DASHBOARD v3
   ============================================ */

/* ── Animations ── */
@keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.5}}
@keyframes glowPulse{0%,100%{box-shadow:0 0 0 0 rgba(239,68,68,.3)}50%{box-shadow:0 0 0 6px rgba(239,68,68,0)}}
@keyframes slideR{from{opacity:0;transform:translateX(-12px)}to{opacity:1;transform:translateX(0)}}
@keyframes popIn{from{opacity:0;transform:scale(.92)}to{opacity:1;transform:scale(1)}}
.bn-anim{animation:fadeUp .5s cubic-bezier(.4,0,.2,1) both}
.bn-d1{animation-delay:.04s}.bn-d2{animation-delay:.08s}.bn-d3{animation-delay:.12s}
.bn-d4{animation-delay:.16s}.bn-d5{animation-delay:.2s}.bn-d6{animation-delay:.24s}

/* ── Hero Banner ── */
.bn-hero{border-radius:20px;padding:28px 32px 20px;margin-bottom:20px;position:relative;overflow:hidden;color:#fff;animation:fadeUp .45s ease-out}
.bn-hero::before{content:'';position:absolute;inset:0;opacity:.07;background-image:radial-gradient(circle at 15% 50%,#fff 0%,transparent 50%),radial-gradient(circle at 85% 20%,#fff 0%,transparent 40%);pointer-events:none}
.bn-hero::after{content:'';position:absolute;top:-60%;right:-15%;width:420px;height:420px;border-radius:50%;background:rgba(255,255,255,.03);pointer-events:none}
.bn-hero.admin{background:linear-gradient(135deg,#0c1222 0%,#162544 40%,#1e3a5f 100%)}
.bn-hero.manager{background:linear-gradient(135deg,#0f2167 0%,#1d4ed8 50%,#3b82f6 100%)}
.bn-hero.user{background:linear-gradient(135deg,#052e16 0%,#065f46 50%,#059669 100%)}
.bn-hero-top{display:flex;align-items:center;justify-content:space-between;gap:16px;position:relative;z-index:2;flex-wrap:wrap}
.bn-hero-info{display:flex;align-items:center;gap:16px}
.bn-hero-avatar{width:54px;height:54px;border-radius:16px;background:rgba(255,255,255,.1);backdrop-filter:blur(10px);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;border:1.5px solid rgba(255,255,255,.15)}
.bn-hero h2{margin:0;font-size:19px;font-weight:700;letter-spacing:-.3px}
.bn-hero-sub{margin:4px 0 0;font-size:12px;color:rgba(255,255,255,.55);display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.bn-hero-sub i{font-size:9px}
.bn-hero-role{display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:20px;font-size:10px;font-weight:700;background:rgba(255,255,255,.1);backdrop-filter:blur(6px);border:1px solid rgba(255,255,255,.1);letter-spacing:.5px;text-transform:uppercase}
.bn-hero-kpi{display:flex;gap:28px;margin-top:20px;position:relative;z-index:2;flex-wrap:wrap}
.bn-hero-kpi-item{text-align:center;min-width:60px;position:relative}
.bn-hero-kpi-item::after{content:'';position:absolute;right:-14px;top:2px;bottom:2px;width:1px;background:rgba(255,255,255,.08)}
.bn-hero-kpi-item:last-child::after{display:none}
.bn-hero-kpi-item .kv{font-size:26px;font-weight:800;line-height:1;letter-spacing:-.5px}
.bn-hero-kpi-item .kl{font-size:9px;color:rgba(255,255,255,.45);margin-top:4px;font-weight:500;text-transform:uppercase;letter-spacing:.5px}
.bn-hero-kpi-item .kv.pulse{animation:pulse 2s ease-in-out infinite;color:#fbbf24}
.bn-hero-date{font-size:11px;color:rgba(255,255,255,.3);margin-top:14px;position:relative;z-index:2;display:flex;align-items:center;gap:5px}

/* ── Bento Grid ── */
.bn-grid{display:grid;grid-template-columns:repeat(12,1fr);gap:16px;margin-bottom:20px}

/* ── Bento Card ── */
.bn-card{background:#fff;border:1px solid #eef0f4;border-radius:16px;overflow:hidden;transition:all .25s cubic-bezier(.4,0,.2,1);position:relative}
.bn-card:hover{box-shadow:0 8px 32px rgba(0,0,0,.06);border-color:#dde2ea}
.bn-card-hdr{padding:16px 20px 12px;display:flex;align-items:center;justify-content:space-between;gap:8px}
.bn-card-title{font-size:13px;font-weight:700;color:var(--c1);display:flex;align-items:center;gap:8px}
.bn-card-title i{font-size:14px}
.bn-card-badge{font-size:10px;font-weight:700;padding:3px 10px;border-radius:20px;background:#f1f5f9;color:var(--c2)}
.bn-card-badge.danger{background:#fef2f2;color:#dc2626}
.bn-card-badge.warn{background:#fffbeb;color:#d97706}
.bn-card-badge.info{background:#eff6ff;color:#2563eb}
.bn-card-badge.ok{background:#f0fdf4;color:#059669}
.bn-card-body{padding:0 20px 18px}

/* Colored top accent */
.bn-card.accent-blue{border-top:3px solid #3b82f6}
.bn-card.accent-amber{border-top:3px solid #f59e0b}
.bn-card.accent-red{border-top:3px solid #ef4444}
.bn-card.accent-green{border-top:3px solid #10b981}
.bn-card.accent-purple{border-top:3px solid #8b5cf6}
.bn-card.accent-orange{border-top:3px solid #f97316}

/* ── Quick Actions ── */
.bn-qa{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
.bn-qa-item{display:flex;flex-direction:column;align-items:center;gap:7px;padding:14px 6px;border-radius:12px;border:1px solid #eef0f4;text-decoration:none;transition:all .2s;background:#fff}
.bn-qa-item:hover{border-color:var(--accent);box-shadow:0 4px 16px rgba(0,0,0,.06);transform:translateY(-2px);text-decoration:none}
.bn-qa-icon{width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:15px}
.bn-qa-label{font-size:10px;font-weight:600;color:var(--c2);text-align:center;line-height:1.3}

/* ── Stat mini cards ── */
.bn-stat{background:#fff;border:1px solid #eef0f4;border-radius:14px;padding:16px;display:flex;align-items:center;gap:12px;transition:all .22s;cursor:default;position:relative;overflow:hidden}
.bn-stat:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,.05)}
.bn-stat-icon{width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
.bn-stat-icon.green{background:linear-gradient(135deg,#ecfdf5,#a7f3d0);color:#059669}
.bn-stat-icon.blue{background:linear-gradient(135deg,#eff6ff,#bfdbfe);color:#2563eb}
.bn-stat-icon.red{background:linear-gradient(135deg,#fef2f2,#fecaca);color:#dc2626}
.bn-stat-icon.orange{background:linear-gradient(135deg,#fff7ed,#fed7aa);color:#ea580c}
.bn-stat-icon.purple{background:linear-gradient(135deg,#faf5ff,#e9d5ff);color:#7c3aed}
.bn-stat-icon.amber{background:linear-gradient(135deg,#fffbeb,#fde68a);color:#d97706}
.bn-stat-val{font-size:22px;font-weight:800;color:var(--c1);line-height:1;letter-spacing:-.3px}
.bn-stat-lbl{font-size:10px;color:var(--c3);margin-top:2px;font-weight:500}
.bn-stat .alert-dot{position:absolute;top:8px;right:8px;width:8px;height:8px;border-radius:50%;background:#ef4444;animation:glowPulse 2s infinite}

/* ── Organization / Labs table ── */
.bn-lab-summary{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:14px}
.bn-lab-sum-item{text-align:center;padding:12px 6px;border-radius:12px;position:relative;overflow:hidden}
.bn-lab-sum-item::before{content:'';position:absolute;inset:0;opacity:.04;background:radial-gradient(circle at 30% 30%,currentColor,transparent 70%);pointer-events:none}
.bn-lab-sum-item .sv{font-size:20px;font-weight:900;line-height:1;display:block}
.bn-lab-sum-item .sl{font-size:9px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-top:3px;opacity:.7}

.bn-lab-table{width:100%;border-collapse:separate;border-spacing:0;font-size:12px}
.bn-lab-table thead th{padding:8px 12px;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--c3);border-bottom:2px solid #f1f5f9;text-align:left}
.bn-lab-table thead th:nth-child(2),.bn-lab-table thead th:nth-child(3){text-align:center}
.bn-lab-table thead th:last-child{text-align:right}
.bn-lab-table tbody tr{transition:background .15s}
.bn-lab-table tbody tr:hover{background:#f0f9ff}
.bn-lab-table tbody td{padding:10px 12px;border-bottom:1px solid #f8fafc;vertical-align:middle}
.bn-lab-table tbody td:nth-child(2),.bn-lab-table tbody td:nth-child(3){text-align:center}
.bn-lab-table tbody td:last-child{text-align:right}
.bn-lab-table tbody tr:last-child td{border-bottom:none}
.bn-lab-name{font-weight:700;color:var(--c1);display:block;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.bn-lab-bar{height:3px;border-radius:2px;background:#f1f5f9;margin-top:4px;overflow:hidden}
.bn-lab-bar-fill{height:100%;border-radius:2px;background:linear-gradient(90deg,#3b82f6,#93c5fd);transition:width .6s}
.bn-lab-cnt{font-size:15px;font-weight:800;color:#1d4ed8}
.bn-lab-usr{font-size:13px;font-weight:600;color:var(--c2)}
.bn-pill{display:inline-flex;align-items:center;gap:3px;font-size:9px;font-weight:700;padding:3px 9px;border-radius:6px}
.bn-pill.ok{background:#f0fdf4;color:#059669;border:1px solid #a7f3d0}
.bn-pill.danger{background:#fef2f2;color:#dc2626;border:1px solid #fecaca}
.bn-pill.info{background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe}
.bn-pill i{font-size:7px}

/* ── Top consumed ranked ── */
.bn-rank-list{display:flex;flex-direction:column;gap:0}
.bn-rank-item{display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid #f8fafc}
.bn-rank-item:last-child{border-bottom:none}
.bn-rank-pos{width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;flex-shrink:0}
.bn-rank-pos.gold{background:linear-gradient(135deg,#fef3c7,#fde68a);color:#92400e}
.bn-rank-pos.silver{background:linear-gradient(135deg,#f1f5f9,#e2e8f0);color:#475569}
.bn-rank-pos.bronze{background:linear-gradient(135deg,#ffedd5,#fed7aa);color:#9a3412}
.bn-rank-pos.def{background:#f8fafc;color:var(--c3);font-size:10px}
.bn-rank-info{flex:1;min-width:0}
.bn-rank-name{font-size:12px;font-weight:700;color:var(--c1);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.bn-rank-bar{display:flex;align-items:center;gap:6px;margin-top:4px}
.bn-rank-track{flex:1;height:4px;border-radius:2px;background:#f1f5f9;overflow:hidden}
.bn-rank-fill{height:100%;border-radius:2px;transition:width .6s}
.bn-rank-pct{font-size:9px;font-weight:700;color:var(--c3);flex-shrink:0;min-width:30px;text-align:right}
.bn-rank-val{flex-shrink:0;text-align:right}
.bn-rank-val .rv{font-size:16px;font-weight:900;color:var(--c1);line-height:1}
.bn-rank-val .rl{font-size:8px;color:var(--c3);text-transform:uppercase;font-weight:500;letter-spacing:.3px}

/* ── Expiring ── */
.bn-exp-list{display:flex;flex-direction:column;gap:8px}
.bn-exp-item{display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:12px;border:1px solid #f3f4f6;transition:all .18s;position:relative;overflow:hidden;background:#fff}
.bn-exp-item::before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px}
.bn-exp-item.sev-danger::before{background:#ef4444}
.bn-exp-item.sev-warn::before{background:#f59e0b}
.bn-exp-item.sev-ok::before{background:#10b981}
.bn-exp-item:hover{background:#fafbfc;box-shadow:0 2px 12px rgba(0,0,0,.04);transform:translateX(3px)}
.bn-exp-days{width:48px;height:48px;border-radius:12px;display:flex;flex-direction:column;align-items:center;justify-content:center;flex-shrink:0}
.bn-exp-days.danger{background:linear-gradient(135deg,#fef2f2,#fee2e2)}
.bn-exp-days.warn{background:linear-gradient(135deg,#fffbeb,#fef3c7)}
.bn-exp-days.ok{background:linear-gradient(135deg,#f0fdf4,#dcfce7)}
.bn-exp-days .ed-num{font-size:20px;font-weight:900;line-height:1}
.bn-exp-days.danger .ed-num{color:#dc2626}
.bn-exp-days.warn .ed-num{color:#d97706}
.bn-exp-days.ok .ed-num{color:#059669}
.bn-exp-days .ed-unit{font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;margin-top:1px}
.bn-exp-days.danger .ed-unit{color:#ef4444}
.bn-exp-days.warn .ed-unit{color:#f59e0b}
.bn-exp-days.ok .ed-unit{color:#10b981}
.bn-exp-info{flex:1;min-width:0}
.bn-exp-name{font-size:12px;font-weight:700;color:var(--c1);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.bn-exp-meta{display:flex;gap:6px;margin-top:4px;flex-wrap:wrap}
.bn-exp-tag{display:inline-flex;align-items:center;gap:3px;font-size:9px;padding:2px 7px;border-radius:5px;background:#f8fafc;color:var(--c2);font-weight:500;border:1px solid #f1f5f9}
.bn-exp-tag i{font-size:7px;opacity:.5}
.bn-exp-right{flex-shrink:0;text-align:right}
.bn-exp-date{font-size:11px;font-weight:600;color:var(--c2)}
.bn-exp-urgency{height:3px;border-radius:2px;background:#f1f5f9;margin-top:6px;width:56px;overflow:hidden}
.bn-exp-urgency-fill{height:100%;border-radius:2px}

/* ── Low Stock ── */
.bn-ls-list{display:flex;flex-direction:column;gap:8px}
.bn-ls-item{display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:12px;border:1px solid #f3f4f6;background:#fff;transition:all .18s;position:relative;overflow:hidden}
.bn-ls-item::before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px}
.bn-ls-item.sev-crit::before{background:#ef4444}
.bn-ls-item.sev-warn::before{background:#f59e0b}
.bn-ls-item.sev-low::before{background:#fb923c}
.bn-ls-item:hover{background:#fafbfc;box-shadow:0 2px 12px rgba(0,0,0,.04)}
.bn-ls-gauge{width:46px;height:46px;position:relative;flex-shrink:0}
.bn-ls-gauge svg{width:46px;height:46px;transform:rotate(-90deg)}
.bn-ls-gauge .track{fill:none;stroke:#f1f5f9;stroke-width:4}
.bn-ls-gauge .fill{fill:none;stroke-width:4;stroke-linecap:round;transition:stroke-dashoffset .8s cubic-bezier(.4,0,.2,1)}
.bn-ls-pct{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:900;line-height:1}
.bn-ls-info{flex:1;min-width:0}
.bn-ls-name{font-size:12px;font-weight:700;color:var(--c1);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.bn-ls-tags{display:flex;gap:4px;margin-top:4px;flex-wrap:wrap}
.bn-ls-tag{display:inline-flex;align-items:center;gap:3px;font-size:8px;padding:2px 6px;border-radius:4px;background:#f1f5f9;color:var(--c3);font-weight:500}
.bn-ls-tag i{font-size:6px;opacity:.5}
.bn-ls-right{flex-shrink:0;text-align:right}
.bn-ls-qty{font-size:16px;font-weight:900;color:var(--c1);line-height:1}
.bn-ls-unit{font-size:9px;color:var(--c3);font-weight:500}
.bn-ls-sev{display:inline-flex;align-items:center;gap:3px;font-size:8px;font-weight:700;padding:2px 7px;border-radius:4px;text-transform:uppercase;letter-spacing:.3px;margin-top:3px}
.bn-ls-sev.crit{background:#fef2f2;color:#b91c1c}
.bn-ls-sev.warn{background:#fffbeb;color:#b45309}
.bn-ls-sev.low{background:#fff7ed;color:#c2410c}

/* ── Alerts ── */
.bn-alert-row{display:flex;align-items:flex-start;gap:10px;padding:10px 0;border-bottom:1px solid #f8fafc;transition:all .12s}
.bn-alert-row:last-child{border-bottom:none}
.bn-alert-icon{width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:12px}
.bn-alert-msg{font-size:12px;color:var(--c1);line-height:1.4;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.bn-alert-time{font-size:10px;color:var(--c3);margin-top:1px}

/* ── Team ── */
.bn-team-row{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #f8fafc}
.bn-team-row:last-child{border-bottom:none}
.bn-team-avatar{width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#dbeafe,#bfdbfe);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#1d4ed8;flex-shrink:0}
.bn-team-info{flex:1;min-width:0}
.bn-team-name{font-size:12px;font-weight:600;color:var(--c1);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.bn-team-meta{font-size:10px;color:var(--c3);margin-top:1px}

/* ── Feed / Borrow / Request items ── */
.bn-feed-item{display:flex;gap:10px;padding:8px 0;border-bottom:1px solid #f8fafc}
.bn-feed-item:last-child{border-bottom:none}
.bn-feed-dot{width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0}
.bn-feed-dot.used{background:linear-gradient(135deg,#dbeafe,#bfdbfe);color:#1d4ed8}
.bn-feed-dot.added{background:linear-gradient(135deg,#d1fae5,#a7f3d0);color:#059669}
.bn-feed-dot.removed{background:linear-gradient(135deg,#fecaca,#fca5a5);color:#b91c1c}
.bn-feed-dot.default{background:linear-gradient(135deg,#f3f4f6,#e5e7eb);color:#6b7280}
.bn-mychem{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;background:#f8faf9;border:1px solid #e8f0ed;transition:all .15s}
.bn-mychem:hover{background:#f0fdf4;border-color:#a7f3d0;transform:translateX(2px)}
.bn-mychem+.bn-mychem{margin-top:6px}
.bn-mychem-ic{width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,#d1fae5,#a7f3d0);color:#059669;display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0}
.bn-borrow-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;background:#faf5ff;border:1px solid #f3e8ff;transition:all .12s}
.bn-borrow-item:hover{background:#f3e8ff;border-color:#d8b4fe}
.bn-borrow-item+.bn-borrow-item{margin-top:6px}
.bn-req-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;border:1px solid #fde68a;background:#fffdf5;transition:all .15s}
.bn-req-item:hover{background:#fef9c3;border-color:#f59e0b}
.bn-req-item+.bn-req-item{margin-top:6px}

/* ── Section label ── */
.bn-section{font-size:9px;font-weight:700;color:var(--c3);text-transform:uppercase;letter-spacing:.8px;margin-bottom:8px;display:flex;align-items:center;gap:6px}
.bn-section i{font-size:9px}
.bn-section::after{content:'';flex:1;height:1px;background:linear-gradient(90deg,#eee,transparent)}

/* ── Empty ── */
.bn-empty{text-align:center;padding:28px 16px;color:var(--c3)}
.bn-empty i{font-size:28px;margin-bottom:8px;display:block;opacity:.18}
.bn-empty p{font-size:12px;margin:0}

/* ── Trend & Compliance PRO ── */
.bn-tc-wrap{display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start}
@media(max-width:900px){.bn-tc-wrap{grid-template-columns:1fr}}

/* Fullscreen button */
.bn-fullscreen-btn{width:32px;height:32px;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:var(--c2);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:13px;transition:all .2s}
.bn-fullscreen-btn:hover{background:#f0f7ff;border-color:#93c5fd;color:#2563eb;transform:scale(1.05)}
.bn-fullscreen-btn:active{transform:scale(.95)}

/* Fullscreen panel mode */
.bn-card.bn-fullscreen{position:fixed!important;inset:0!important;z-index:9999!important;border-radius:0!important;grid-column:unset!important;margin:0!important;overflow-y:auto;animation:bn-fs-in .3s ease}
.bn-card.bn-fullscreen .bn-card-body{padding:20px 32px 32px}
.bn-card.bn-fullscreen .bn-tc-wrap{gap:28px}
.bn-card.bn-fullscreen .bn-lc-svg-wrap{height:340px}
.bn-card.bn-fullscreen .bn-lc-tooltip{font-size:12px;padding:10px 14px}
.bn-card.bn-fullscreen .bn-lc-legend{gap:16px;margin-bottom:14px}
.bn-card.bn-fullscreen .bn-lc-legend-item{font-size:12px;padding:6px 14px}
.bn-card.bn-fullscreen .bn-lc-summary{gap:12px;margin-top:16px}
.bn-card.bn-fullscreen .bn-lc-sum-val{font-size:20px}
.bn-fs-overlay{display:none}
.bn-card.bn-fullscreen~.bn-fs-overlay{display:block;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:9998}
@keyframes bn-fs-in{from{opacity:0;transform:scale(.96)}to{opacity:1;transform:scale(1)}}

/* Line Chart */
.bn-chart{position:relative}
.bn-lc-toolbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;flex-wrap:wrap;gap:8px}
.bn-lc-legend{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.bn-lc-legend-item{display:flex;align-items:center;gap:6px;font-size:11px;font-weight:600;color:var(--c2);padding:4px 10px;border-radius:8px;border:1px solid #e2e8f0;background:#fff;cursor:pointer;transition:all .2s;user-select:none}
.bn-lc-legend-item:hover{border-color:#93c5fd;background:#f0f7ff}
.bn-lc-legend-item.inactive{opacity:.35;text-decoration:line-through}
.bn-lc-legend-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0;position:relative}
.bn-lc-legend-dot::after{content:'';position:absolute;inset:-2px;border-radius:50%;border:2px solid currentColor;opacity:.2}

/* SVG chart container */
.bn-lc-svg-wrap{position:relative;height:200px;border-radius:12px;border:1px solid #f1f5f9;background:linear-gradient(180deg,#fafbfc,#fff);overflow:visible;transition:height .3s}
.bn-lc-svg{width:100%;height:100%;overflow:visible}
.bn-lc-grid line{stroke:#f1f5f9;stroke-width:1}
.bn-lc-grid text{fill:var(--c3);font-size:9px;font-weight:500}
.bn-lc-axis-label{fill:var(--c3);font-size:8px;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.bn-lc-line{fill:none;stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round;transition:opacity .3s}
.bn-lc-area{opacity:.08;transition:opacity .3s}
.bn-lc-line.inactive,.bn-lc-area.inactive{opacity:0!important}
.bn-lc-dot{r:4;stroke-width:2.5;stroke:#fff;cursor:pointer;transition:all .2s;filter:drop-shadow(0 1px 3px rgba(0,0,0,.12))}
.bn-lc-dot:hover{r:7;stroke-width:3}
.bn-lc-dot.inactive{opacity:0;pointer-events:none}

/* Hover crosshair */
.bn-lc-crosshair{stroke:#94a3b8;stroke-width:1;stroke-dasharray:4 3;opacity:0;transition:opacity .15s;pointer-events:none}
.bn-lc-svg-wrap:hover .bn-lc-crosshair.active{opacity:1}

/* Tooltip */
.bn-lc-tooltip{position:absolute;pointer-events:none;background:#1e293b;color:#fff;font-size:10px;font-weight:500;padding:8px 12px;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.2);opacity:0;transition:opacity .15s;z-index:20;min-width:140px;line-height:1.6}
.bn-lc-tooltip.show{opacity:1}
.bn-lc-tooltip-title{font-weight:700;font-size:11px;margin-bottom:4px;padding-bottom:4px;border-bottom:1px solid rgba(255,255,255,.15)}
.bn-lc-tooltip-row{display:flex;align-items:center;gap:6px}
.bn-lc-tooltip-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.bn-lc-tooltip-val{margin-left:auto;font-weight:700}
.bn-lc-tooltip-change{font-size:9px;padding:1px 5px;border-radius:4px;margin-left:4px}
.bn-lc-tooltip-change.up{background:rgba(34,197,94,.2);color:#22c55e}
.bn-lc-tooltip-change.down{background:rgba(239,68,68,.2);color:#ef4444}
.bn-lc-tooltip::after{content:'';position:absolute;bottom:-5px;left:50%;transform:translateX(-50%);border:5px solid transparent;border-top-color:#1e293b;border-bottom:0}

/* Summary cards */
.bn-lc-summary{display:flex;gap:8px;margin-top:12px;flex-wrap:wrap}
.bn-lc-sum-card{flex:1;min-width:80px;padding:10px 12px;border-radius:10px;border:1px solid;text-align:center;transition:all .2s}
.bn-lc-sum-card:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(0,0,0,.06)}
.bn-lc-sum-val{font-size:17px;font-weight:900;line-height:1}
.bn-lc-sum-lbl{font-size:9px;font-weight:600;margin-top:3px;opacity:.7;text-transform:uppercase;letter-spacing:.3px}

@media(max-width:768px){
    .bn-lc-svg-wrap{height:160px}
    .bn-lc-legend-item{font-size:10px;padding:3px 8px}
    .bn-lc-sum-card{min-width:60px;padding:8px 6px}
    .bn-lc-sum-val{font-size:14px}
    .bn-fullscreen-btn{width:28px;height:28px;font-size:11px}
    .bn-card.bn-fullscreen .bn-card-body{padding:14px 16px 20px}
    .bn-card.bn-fullscreen .bn-lc-svg-wrap{height:260px}
}

/* Compliance area */
.bn-comp-pro{display:flex;flex-direction:column;gap:12px}
.bn-comp-score{display:flex;align-items:center;gap:16px;padding:16px;border-radius:14px;background:linear-gradient(135deg,#f8fafc,#f1f5f9);border:1px solid #e2e8f0;position:relative;overflow:hidden}
.bn-comp-score::before{content:'';position:absolute;top:-30px;right:-30px;width:100px;height:100px;border-radius:50%;background:radial-gradient(circle,rgba(16,185,129,.06),transparent 70%);pointer-events:none}
.bn-comp-ring{position:relative;width:72px;height:72px;flex-shrink:0}
.bn-comp-ring svg{width:72px;height:72px;transform:rotate(-90deg)}
.bn-comp-ring .ring-track{fill:none;stroke:#e2e8f0;stroke-width:5}
.bn-comp-ring .ring-fill{fill:none;stroke-width:5;stroke-linecap:round;transition:stroke-dashoffset 1.2s cubic-bezier(.4,0,.2,1)}
.bn-comp-ring-val{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center}
.bn-comp-ring-val .rv{font-size:18px;font-weight:900;line-height:1}
.bn-comp-ring-val .rl{font-size:8px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-top:1px}
.bn-comp-score-info{flex:1;min-width:0}
.bn-comp-score-title{font-size:14px;font-weight:800;color:var(--c1);line-height:1.2}
.bn-comp-score-desc{font-size:10px;color:var(--c3);margin-top:3px;line-height:1.4}
.bn-comp-score-shield{font-size:16px;position:absolute;top:12px;right:14px}

.bn-comp-cards{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}
.bn-comp-card{padding:12px 8px;border-radius:12px;text-align:center;position:relative;overflow:hidden;transition:all .2s}
.bn-comp-card:hover{transform:translateY(-2px);box-shadow:0 4px 14px rgba(0,0,0,.06)}
.bn-comp-card::before{content:'';position:absolute;inset:0;opacity:.04;background:radial-gradient(circle at 30% 30%,currentColor,transparent 70%);pointer-events:none}
.bn-comp-card.pass{background:linear-gradient(135deg,#f0fdf4,#dcfce7);color:#166534;border:1px solid #bbf7d0}
.bn-comp-card.warn{background:linear-gradient(135deg,#fffbeb,#fef3c7);color:#854d0e;border:1px solid #fde68a}
.bn-comp-card.fail{background:linear-gradient(135deg,#fef2f2,#fee2e2);color:#991b1b;border:1px solid #fecaca}
.bn-comp-card-ic{font-size:16px;margin-bottom:4px;display:block}
.bn-comp-card .cc-val{font-size:22px;font-weight:900;display:block;line-height:1}
.bn-comp-card .cc-lbl{font-size:9px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-top:3px;opacity:.75}
.bn-comp-card .cc-pct{font-size:9px;font-weight:700;margin-top:4px;display:inline-flex;align-items:center;gap:2px;padding:2px 6px;border-radius:8px;background:rgba(255,255,255,.6)}

/* Compliance legend bar */
.bn-comp-legend{display:flex;height:6px;border-radius:3px;overflow:hidden;margin-top:4px}
.bn-comp-legend .seg-pass{background:linear-gradient(90deg,#22c55e,#4ade80)}
.bn-comp-legend .seg-warn{background:linear-gradient(90deg,#f59e0b,#fbbf24)}
.bn-comp-legend .seg-fail{background:linear-gradient(90deg,#ef4444,#f87171)}

/* ── Responsive ── */
@media(max-width:1200px){
    .bn-grid{grid-template-columns:repeat(6,1fr);gap:14px}
    .bn-grid .bn-stat{grid-column:span 3}
    .bn-grid>[style*="span 7"]{grid-column:span 6!important}
    .bn-grid>[style*="span 5"]{grid-column:span 6!important}
    .bn-grid>[style*="span 12"]{grid-column:span 6!important}
}
@media(max-width:768px){
    .bn-hero{padding:20px 18px 16px;border-radius:14px}
    .bn-hero h2{font-size:16px}
    .bn-hero-avatar{width:42px;height:42px;font-size:18px}
    .bn-hero-kpi{gap:16px}
    .bn-hero-kpi-item .kv{font-size:20px}
    .bn-hero-kpi-item::after{display:none}
    .bn-grid{grid-template-columns:1fr;gap:12px}
    .bn-grid>[style*="span"]{grid-column:span 1!important}
    .bn-qa{grid-template-columns:repeat(2,1fr)}
    .bn-stat{padding:12px}
    .bn-stat-icon{width:36px;height:36px;font-size:14px}
    .bn-stat-val{font-size:18px}
}
@media(max-width:480px){
    .bn-hero{padding:16px 14px 12px}
    .bn-hero h2{font-size:14px}
    .bn-hero-avatar{width:38px;height:38px;font-size:16px}
    .bn-hero-kpi-item .kv{font-size:17px}
    .bn-hero-kpi-item .kl{font-size:8px}
    .bn-hero-role{font-size:9px;padding:3px 8px}
    .bn-qa{gap:6px}
    .bn-qa-icon{width:34px;height:34px;font-size:13px}
    .bn-qa-label{font-size:9px}
    .bn-card{border-radius:12px}
    .bn-card-hdr{padding:12px 14px 10px}
    .bn-card-body{padding:0 14px 14px}
}

/* ── Lab Detail Modal ── */
.ld-overlay{position:fixed;inset:0;z-index:10000;background:rgba(15,23,42,.55);backdrop-filter:blur(4px);display:flex;align-items:flex-start;justify-content:center;padding:24px 16px;overflow-y:auto;opacity:0;pointer-events:none;transition:opacity .25s}
.ld-overlay.show{opacity:1;pointer-events:auto}
.ld-modal{background:#fff;border-radius:20px;width:100%;max-width:780px;margin:auto;box-shadow:0 20px 60px rgba(0,0,0,.18);transform:translateY(16px) scale(.97);transition:transform .3s cubic-bezier(.4,0,.2,1);overflow:hidden}
.ld-overlay.show .ld-modal{transform:translateY(0) scale(1)}
.ld-hdr{display:flex;align-items:center;gap:14px;padding:22px 28px 18px;border-bottom:1px solid #f1f5f9;position:sticky;top:0;background:#fff;z-index:2}
.ld-hdr-icon{width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#dbeafe,#bfdbfe);color:#1d4ed8;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}
.ld-hdr-info{flex:1;min-width:0}
.ld-hdr h3{margin:0;font-size:18px;font-weight:800;color:var(--c1);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ld-hdr-sub{font-size:12px;color:var(--c3);margin-top:2px}
.ld-close{width:36px;height:36px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;color:var(--c2);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:15px;transition:all .15s;flex-shrink:0}
.ld-close:hover{background:#fef2f2;border-color:#fca5a5;color:#dc2626}
.ld-body{padding:20px 28px 28px}

/* KPI row */
.ld-kpi{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:20px}
.ld-kpi-item{text-align:center;padding:14px 8px;border-radius:14px;position:relative;overflow:hidden}
.ld-kpi-item::before{content:'';position:absolute;inset:0;opacity:.05;background:radial-gradient(circle at 30% 30%,currentColor,transparent 70%);pointer-events:none}
.ld-kpi-item .kv{font-size:24px;font-weight:900;line-height:1;display:block}
.ld-kpi-item .kl{font-size:9px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-top:4px;opacity:.7}

/* Section */
.ld-section{font-size:10px;font-weight:700;color:var(--c3);text-transform:uppercase;letter-spacing:.8px;margin:20px 0 10px;display:flex;align-items:center;gap:8px}
.ld-section i{font-size:10px}
.ld-section::after{content:'';flex:1;height:1px;background:linear-gradient(90deg,#e2e8f0,transparent)}

/* Members table */
.ld-members{width:100%;border-collapse:separate;border-spacing:0;font-size:12px}
.ld-members thead th{padding:8px 10px;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--c3);border-bottom:2px solid #f1f5f9;text-align:left}
.ld-members thead th:nth-child(n+3){text-align:center}
.ld-members tbody tr{transition:background .12s}
.ld-members tbody tr:hover{background:#f0f9ff}
.ld-members tbody td{padding:9px 10px;border-bottom:1px solid #f8fafc;vertical-align:middle}
.ld-members tbody td:nth-child(n+3){text-align:center}
.ld-members tbody tr:last-child td{border-bottom:none}
.ld-m-avatar{width:30px;height:30px;border-radius:9px;background:linear-gradient(135deg,#dbeafe,#bfdbfe);color:#1d4ed8;display:inline-flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;margin-right:8px;vertical-align:middle}
.ld-m-name{font-weight:700;color:var(--c1)}
.ld-m-pos{font-size:10px;color:var(--c3);margin-top:1px}

/* Chemicals list */
.ld-chem-row{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;border:1px solid #f1f5f9;background:#fafbfc;transition:all .12s}
.ld-chem-row:hover{background:#f0f7ff;border-color:#bfdbfe;transform:translateX(2px)}
.ld-chem-row+.ld-chem-row{margin-top:6px}
.ld-chem-gauge{width:38px;height:38px;position:relative;flex-shrink:0}
.ld-chem-gauge svg{width:38px;height:38px;transform:rotate(-90deg)}
.ld-chem-gauge .trk{fill:none;stroke:#f1f5f9;stroke-width:4}
.ld-chem-gauge .fl{fill:none;stroke-width:4;stroke-linecap:round}
.ld-chem-pct{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:900}
.ld-chem-info{flex:1;min-width:0}
.ld-chem-name{font-size:12px;font-weight:700;color:var(--c1);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ld-chem-meta{font-size:10px;color:var(--c3);margin-top:2px;display:flex;gap:8px;flex-wrap:wrap}
.ld-chem-right{flex-shrink:0;text-align:right}
.ld-chem-qty{font-size:15px;font-weight:800;color:var(--c1);line-height:1}
.ld-chem-unit{font-size:9px;color:var(--c3)}

/* Transaction timeline */
.ld-tl-item{display:flex;gap:12px;padding:8px 0;position:relative}
.ld-tl-item+.ld-tl-item{border-top:1px solid #f8fafc}
.ld-tl-icon{width:32px;height:32px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0}
.ld-tl-icon.borrow{background:linear-gradient(135deg,#fef3c7,#fde68a);color:#92400e}
.ld-tl-icon.return{background:linear-gradient(135deg,#d1fae5,#a7f3d0);color:#065f46}
.ld-tl-icon.transfer{background:linear-gradient(135deg,#dbeafe,#bfdbfe);color:#1e40af}
.ld-tl-icon.dispose{background:linear-gradient(135deg,#fecaca,#fca5a5);color:#991b1b}
.ld-tl-icon.adjust{background:linear-gradient(135deg,#e9d5ff,#d8b4fe);color:#6b21a8}
.ld-tl-icon.receive{background:linear-gradient(135deg,#a7f3d0,#6ee7b7);color:#047857}
.ld-tl-icon.use{background:linear-gradient(135deg,#fed7aa,#fdba74);color:#9a3412}
.ld-tl-info{flex:1;min-width:0}
.ld-tl-title{font-size:12px;font-weight:600;color:var(--c1)}
.ld-tl-desc{font-size:10px;color:var(--c3);margin-top:2px;line-height:1.4}
.ld-tl-time{font-size:10px;color:var(--c3);flex-shrink:0;white-space:nowrap}

/* Overdue list */
.ld-ov-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;background:#fef2f2;border:1px solid #fecaca;transition:all .12s}
.ld-ov-item+.ld-ov-item{margin-top:6px}
.ld-ov-item:hover{background:#fee2e2}
.ld-ov-days{width:42px;height:42px;border-radius:10px;background:linear-gradient(135deg,#fca5a5,#f87171);color:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;flex-shrink:0}
.ld-ov-days .dv{font-size:16px;font-weight:900;line-height:1}
.ld-ov-days .dl{font-size:7px;font-weight:700;text-transform:uppercase;letter-spacing:.3px}

/* Tabs */
.ld-tabs{display:flex;gap:4px;margin-bottom:16px;border-bottom:2px solid #f1f5f9;padding-bottom:0}
.ld-tab{padding:8px 16px;font-size:11px;font-weight:700;color:var(--c3);cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;transition:all .15s;border-radius:8px 8px 0 0;display:flex;align-items:center;gap:5px}
.ld-tab:hover{color:var(--c1);background:#f8fafc}
.ld-tab.active{color:#1d4ed8;border-bottom-color:#3b82f6;background:#eff6ff}
.ld-tab .ld-tab-cnt{font-size:9px;padding:2px 6px;border-radius:10px;background:#f1f5f9;color:var(--c3);font-weight:700}
.ld-tab.active .ld-tab-cnt{background:#dbeafe;color:#1d4ed8}
.ld-tab-panel{display:none}
.ld-tab-panel.active{display:block}

/* Empty inside modal */
.ld-empty{text-align:center;padding:24px 12px;color:var(--c3)}
.ld-empty i{font-size:24px;display:block;margin-bottom:6px;opacity:.2}
.ld-empty p{font-size:12px;margin:0}

@media(max-width:600px){
    .ld-modal{border-radius:14px}
    .ld-hdr{padding:16px 18px 14px}
    .ld-body{padding:14px 18px 20px}
    .ld-kpi{grid-template-columns:repeat(2,1fr)}
    .ld-tabs{overflow-x:auto}
    .ld-tab{white-space:nowrap;font-size:10px;padding:6px 12px}
}

/* ── Stock Detail Modal ── */
.sd-overlay{position:fixed;inset:0;z-index:10001;background:rgba(15,23,42,.55);backdrop-filter:blur(4px);display:flex;align-items:flex-start;justify-content:center;padding:24px 16px;overflow-y:auto;opacity:0;pointer-events:none;transition:opacity .25s}
.sd-overlay.show{opacity:1;pointer-events:auto}
.sd-modal{background:#fff;border-radius:20px;width:100%;max-width:560px;margin:auto;box-shadow:0 20px 60px rgba(0,0,0,.18);transform:translateY(16px) scale(.97);transition:transform .3s cubic-bezier(.4,0,.2,1);overflow:hidden}
.sd-overlay.show .sd-modal{transform:translateY(0) scale(1)}
.sd-hdr{display:flex;align-items:center;gap:14px;padding:22px 24px 16px;border-bottom:1px solid #f1f5f9}
.sd-hdr-gauge{width:56px;height:56px;position:relative;flex-shrink:0}
.sd-hdr-gauge svg{width:56px;height:56px;transform:rotate(-90deg)}
.sd-hdr-gauge .trk{fill:none;stroke:#f1f5f9;stroke-width:5}
.sd-hdr-gauge .fl{fill:none;stroke-width:5;stroke-linecap:round}
.sd-hdr-pct{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:900}
.sd-hdr-info{flex:1;min-width:0}
.sd-hdr h3{margin:0;font-size:16px;font-weight:800;color:var(--c1);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.sd-hdr-sub{font-size:11px;color:var(--c3);margin-top:3px;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.sd-close{width:36px;height:36px;border-radius:10px;border:1px solid #e2e8f0;background:#fff;color:var(--c2);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:15px;transition:all .15s;flex-shrink:0}
.sd-close:hover{background:#fef2f2;border-color:#fca5a5;color:#dc2626}
.sd-body{padding:18px 24px 24px}

/* Severity banner */
.sd-sev{display:flex;align-items:center;gap:10px;padding:12px 16px;border-radius:12px;margin-bottom:16px;font-size:12px;font-weight:600}
.sd-sev.crit{background:linear-gradient(135deg,#fef2f2,#fee2e2);color:#991b1b;border:1px solid #fecaca}
.sd-sev.warn{background:linear-gradient(135deg,#fffbeb,#fef3c7);color:#854d0e;border:1px solid #fde68a}
.sd-sev.low{background:linear-gradient(135deg,#fff7ed,#ffedd5);color:#9a3412;border:1px solid #fed7aa}
.sd-sev i{font-size:16px}

/* Info grid */
.sd-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:18px}
.sd-field{padding:10px 12px;border-radius:10px;background:#f8fafc;border:1px solid #f1f5f9}
.sd-field-label{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--c3);margin-bottom:3px}
.sd-field-value{font-size:13px;font-weight:600;color:var(--c1);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.sd-field.full{grid-column:span 2}

/* Quantity bar */
.sd-qty-bar{height:8px;border-radius:4px;background:#f1f5f9;overflow:hidden;margin:6px 0 4px}
.sd-qty-fill{height:100%;border-radius:4px;transition:width .6s}
.sd-qty-labels{display:flex;justify-content:space-between;font-size:10px;color:var(--c3)}

/* Action buttons */
.sd-actions{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:18px}
.sd-act{display:flex;flex-direction:column;align-items:center;gap:6px;padding:14px 8px;border-radius:12px;border:1.5px solid #e2e8f0;background:#fff;cursor:pointer;transition:all .2s;text-decoration:none}
.sd-act:hover{transform:translateY(-2px);box-shadow:0 4px 16px rgba(0,0,0,.08)}
.sd-act-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:15px}
.sd-act-label{font-size:10px;font-weight:700;color:var(--c2);text-align:center}
.sd-act.use:hover{border-color:#60a5fa;background:#eff6ff}
.sd-act.use .sd-act-icon{background:linear-gradient(135deg,#dbeafe,#bfdbfe);color:#1d4ed8}
.sd-act.transfer:hover{border-color:#a78bfa;background:#faf5ff}
.sd-act.transfer .sd-act-icon{background:linear-gradient(135deg,#e9d5ff,#d8b4fe);color:#7c3aed}
.sd-act.dispose:hover{border-color:#f87171;background:#fef2f2}
.sd-act.dispose .sd-act-icon{background:linear-gradient(135deg,#fecaca,#fca5a5);color:#dc2626}

/* Action form */
.sd-form{margin-top:16px;padding:16px;border-radius:14px;border:1px solid #e2e8f0;background:#f8fafc;display:none}
.sd-form.show{display:block;animation:popIn .25s ease}
.sd-form h4{margin:0 0 12px;font-size:13px;font-weight:700;color:var(--c1);display:flex;align-items:center;gap:8px}
.sd-form-row{margin-bottom:10px}
.sd-form-row label{display:block;font-size:10px;font-weight:700;color:var(--c3);text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px}
.sd-form-row input,.sd-form-row select,.sd-form-row textarea{width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:13px;color:var(--c1);background:#fff;outline:none;transition:border-color .15s;font-family:inherit;box-sizing:border-box}
.sd-form-row input:focus,.sd-form-row select:focus,.sd-form-row textarea:focus{border-color:#3b82f6}
.sd-form-row textarea{resize:vertical;min-height:60px}
.sd-form-btns{display:flex;gap:8px;margin-top:14px}
.sd-form-btns button{flex:1;padding:10px;border-radius:10px;font-size:12px;font-weight:700;cursor:pointer;border:none;transition:all .15s}
.sd-btn-cancel{background:#f1f5f9;color:var(--c2)}
.sd-btn-cancel:hover{background:#e2e8f0}
.sd-btn-submit{color:#fff}
.sd-btn-submit:hover{filter:brightness(1.1);transform:translateY(-1px)}
.sd-btn-submit.use{background:linear-gradient(135deg,#2563eb,#3b82f6)}
.sd-btn-submit.transfer{background:linear-gradient(135deg,#7c3aed,#8b5cf6)}
.sd-btn-submit.dispose{background:linear-gradient(135deg,#dc2626,#ef4444)}
.sd-btn-submit:disabled{opacity:.5;cursor:not-allowed;transform:none}

/* Toast */
.sd-toast{position:fixed;top:24px;left:50%;transform:translateX(-50%) translateY(-80px);z-index:10010;padding:12px 24px;border-radius:12px;font-size:13px;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,.15);transition:transform .35s cubic-bezier(.4,0,.2,1);display:flex;align-items:center;gap:8px}
.sd-toast.show{transform:translateX(-50%) translateY(0)}
.sd-toast.success{background:#065f46;color:#fff}
.sd-toast.error{background:#991b1b;color:#fff}

@media(max-width:500px){
    .sd-modal{border-radius:14px;max-width:100%}
    .sd-hdr{padding:16px 16px 12px}
    .sd-body{padding:14px 16px 18px}
    .sd-grid{grid-template-columns:1fr}
    .sd-field.full{grid-column:span 1}
    .sd-actions{grid-template-columns:repeat(3,1fr);gap:6px}
    .sd-act{padding:10px 4px}
}
</style>
<body>
<?php Layout::sidebar('dashboard'); Layout::beginContent(); ?>

<!-- ═══ Hero Banner ═══ -->
<div class="bn-hero <?php echo $isAdm?'admin':($isMgr?'manager':'user'); ?>">
    <div class="bn-hero-top">
        <div class="bn-hero-info">
            <div class="bn-hero-avatar">
                <?php if ($isAdm): ?><i class="fas fa-shield-alt"></i>
                <?php elseif ($isMgr): ?><i class="fas fa-microscope"></i>
                <?php else: ?><i class="fas fa-flask"></i><?php endif; ?>
            </div>
            <div>
                <h2><?php echo __('dashboard_welcome'); ?>, <?php echo $displayName; ?> 👋</h2>
                <div class="bn-hero-sub">
                    <?php if($deptName): ?><span><i class="fas fa-building"></i> <?php echo $deptName; ?></span><span style="opacity:.4">•</span><?php endif; ?>
                    <span><?php echo __('dashboard_overview'); ?></span>
                </div>
            </div>
        </div>
        <div class="bn-hero-role">
            <?php
            $roleLabels=['admin'=>['fa-crown',$lang==='th'?'ผู้ดูแลระบบ':'Administrator'],'ceo'=>['fa-crown',$lang==='th'?'ผู้บริหาร':'Executive'],'lab_manager'=>['fa-user-shield',$lang==='th'?'หัวหน้าห้องปฏิบัติการ':'Lab Manager'],'user'=>['fa-user',$lang==='th'?'ผู้ใช้งาน':'User'],'visitor'=>['fa-eye',$lang==='th'?'ผู้เยี่ยมชม':'Visitor']];
            $rl=$roleLabels[$role]??$roleLabels['user'];
            ?>
            <i class="fas <?php echo $rl[0]; ?>"></i> <?php echo $rl[1]; ?>
        </div>
    </div>
    <div class="bn-hero-kpi" id="heroKPI"></div>
    <div class="bn-hero-date"><i class="fas fa-calendar-alt"></i> <?php echo date($lang==='th'?'j F Y':'F j, Y'); ?></div>
</div>

<!-- ═══ Bento Grid ═══ -->
<div class="bn-grid" id="bentoGrid">

    <!-- Stats row (dynamic from JS) -->
    <div id="bnStats" style="display:contents"></div>

    <!-- Quick Actions -->
    <div class="bn-card bn-anim bn-d2" style="grid-column:span 12">
        <div class="bn-card-hdr"><span class="bn-card-title"><i class="fas fa-bolt" style="color:#f59e0b"></i> <?php echo __('dashboard_quick_actions'); ?></span></div>
        <div class="bn-card-body">
            <div class="bn-qa">
                <?php
                if($isAdm){$actions=[['/v1/pages/containers.php?action=add','fa-plus-circle','#fff','linear-gradient(135deg,#059669,#34d399)',$lang==='th'?'เพิ่มขวดสาร':'Add Bottle'],['/v1/pages/users.php','fa-users-cog','#1d4ed8','linear-gradient(135deg,#dbeafe,#bfdbfe)',$lang==='th'?'จัดการผู้ใช้':'Manage Users'],['/v1/pages/activity.php','fa-chart-line','#7c3aed','linear-gradient(135deg,#f3e8ff,#e9d5ff)',$lang==='th'?'ธุรกรรมทั้งหมด':'All Activity'],['/v1/pages/reports.php','fa-file-chart-line','#ea580c','linear-gradient(135deg,#ffedd5,#fed7aa)',$lang==='th'?'รายงาน':'Reports']];}
                elseif($isMgr){$actions=[['/v1/pages/containers.php?action=add','fa-plus-circle','#fff','linear-gradient(135deg,#059669,#34d399)',$lang==='th'?'เพิ่มขวดสาร':'Add Bottle'],['/v1/pages/borrow.php','fa-exchange-alt','#7c3aed','linear-gradient(135deg,#f3e8ff,#e9d5ff)',$lang==='th'?'ธุรกรรม':'Transactions'],['/v1/pages/stock.php','fa-boxes','#ea580c','linear-gradient(135deg,#ffedd5,#fed7aa)',$lang==='th'?'สต็อกสาร':'Stock'],['/v1/pages/qr-scanner.php','fa-qrcode','#1d4ed8','linear-gradient(135deg,#dbeafe,#bfdbfe)',$lang==='th'?'แสกน QR':'Scan QR']];}
                else{$actions=[['/v1/pages/containers.php?action=add','fa-plus-circle','#fff','linear-gradient(135deg,#059669,#34d399)',$lang==='th'?'เพิ่มขวดสาร':'Add Bottle'],['/v1/pages/chemicals.php','fa-search','#1d4ed8','linear-gradient(135deg,#dbeafe,#bfdbfe)',$lang==='th'?'ค้นหาสารเคมี':'Search Chemical'],['/v1/pages/borrow.php','fa-exchange-alt','#7c3aed','linear-gradient(135deg,#f3e8ff,#e9d5ff)',$lang==='th'?'ธุรกรรม':'Transactions'],['/v1/pages/qr-scanner.php','fa-qrcode','#ea580c','linear-gradient(135deg,#ffedd5,#fed7aa)',$lang==='th'?'แสกน QR':'Scan QR']];}
                foreach($actions as [$u,$ic,$c,$bg,$l]):
                ?>
                <a href="<?php echo $u; ?>" class="bn-qa-item">
                    <div class="bn-qa-icon" style="background:<?php echo $bg; ?>;color:<?php echo $c; ?>"><i class="fas <?php echo $ic; ?>"></i></div>
                    <span class="bn-qa-label"><?php echo $l; ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Panel A: Primary (7 cols) -->
    <div class="bn-card accent-blue bn-anim bn-d3" style="grid-column:span 7" id="bnPanelA">
        <div class="bn-card-hdr" id="bnPanelAHdr">
            <span class="bn-card-title"><i class="fas fa-building" style="color:#3b82f6"></i> <span id="bnPanelATitle">&nbsp;</span></span>
            <span class="bn-card-badge info" id="bnPanelABadge" style="display:none">0</span>
        </div>
        <div class="bn-card-body" id="bnPanelABody"><div class="ci-loading"><div class="ci-spinner"></div></div></div>
    </div>

    <!-- Panel B: Alerts (5 cols) -->
    <div class="bn-card accent-red bn-anim bn-d3" style="grid-column:span 5" id="bnPanelB">
        <div class="bn-card-hdr">
            <span class="bn-card-title"><i class="fas fa-bell" style="color:#ef4444"></i> <?php echo __('dashboard_alerts'); ?></span>
            <span class="bn-card-badge danger" id="alertBadge" style="display:none">0</span>
        </div>
        <div class="bn-card-body" id="bnAlerts"><div class="ci-loading"><div class="ci-spinner"></div></div></div>
    </div>

    <!-- Panel C: Expiring (6 cols) -->
    <div class="bn-card accent-amber bn-anim bn-d4" style="grid-column:span 6;display:none" id="bnPanelC">
        <div class="bn-card-hdr">
            <span class="bn-card-title"><i class="fas fa-clock" style="color:#f59e0b"></i> <span id="bnPanelCTitle"></span></span>
            <span class="bn-card-badge warn" id="bnPanelCBadge" style="display:none">0</span>
        </div>
        <div class="bn-card-body" id="bnPanelCBody"></div>
    </div>

    <!-- Panel D: Low Stock (6 cols) -->
    <div class="bn-card accent-orange bn-anim bn-d4" style="grid-column:span 6;display:none" id="bnPanelD">
        <div class="bn-card-hdr">
            <span class="bn-card-title"><i class="fas fa-box-open" style="color:#f97316"></i> <span id="bnPanelDTitle"></span></span>
            <span class="bn-card-badge warn" id="bnPanelDBadge" style="display:none">0</span>
        </div>
        <div class="bn-card-body" id="bnPanelDBody"></div>
    </div>

    <!-- Panel E: Trend (full width) -->
    <div class="bn-card accent-green bn-anim bn-d5" style="grid-column:span 12;display:none" id="bnPanelE">
        <div class="bn-card-hdr">
            <span class="bn-card-title"><i class="fas fa-chart-area" style="color:#10b981"></i> <span id="bnPanelETitle"></span></span>
            <button class="bn-fullscreen-btn" onclick="toggleChartFullscreen()" id="bnFullscreenBtn" title="<?php echo $lang==='th'?'ขยายเต็มจอ':'Fullscreen'; ?>">
                <i class="fas fa-expand"></i>
            </button>
        </div>
        <div class="bn-card-body" id="bnPanelEBody"></div>
    </div>

    <!-- Panel F: Borrowed / Sent Requests (6 cols) -->
    <div class="bn-card accent-purple bn-anim bn-d5" style="grid-column:span 6;display:none" id="bnPanelF">
        <div class="bn-card-hdr" id="bnPanelFHdr">
            <span class="bn-card-title"><i class="fas fa-hand-holding-medical" style="color:#8b5cf6"></i> <span id="bnPanelFTitle"></span></span>
            <span class="bn-card-badge" id="bnPanelFBadge" style="display:none">0</span>
        </div>
        <div class="bn-card-body" id="bnPanelFBody"></div>
    </div>

    <!-- Panel G: Pending Requests (6 cols) -->
    <div class="bn-card accent-amber bn-anim bn-d5" style="grid-column:span 6;display:none" id="bnPanelG">
        <div class="bn-card-hdr">
            <span class="bn-card-title"><i class="fas fa-inbox" style="color:#d97706"></i> <span id="bnPanelGTitle"></span></span>
            <span class="bn-card-badge warn" id="bnPanelGBadge" style="display:none">0</span>
        </div>
        <div class="bn-card-body" id="bnPanelGBody"></div>
    </div>

    <!-- AI Card -->
    <div class="bn-card bn-anim bn-d6" style="grid-column:span 12;border-left:4px solid #8b5cf6;background:linear-gradient(135deg,#faf5ff,#fff)">
        <div class="bn-card-hdr" style="background:transparent"><span class="bn-card-title"><i class="fas fa-sparkles" style="color:#8b5cf6"></i> <?php echo __('dashboard_ai_suggestions'); ?></span></div>
        <div class="bn-card-body" style="display:flex;align-items:center;gap:16px;flex-wrap:wrap">
            <p style="flex:1;font-size:12px;color:#6b7280;margin:0;line-height:1.5;min-width:200px"><?php echo $lang==='th'?'🧠 ระบบ AI วิเคราะห์คลังสารเคมีของคุณและให้คำแนะนำอัจฉริยะ':'🧠 AI analyzes your chemical inventory and provides smart suggestions'; ?></p>
            <a href="/v1/pages/ai-assistant.php" class="ci-btn ci-btn-outline" style="border-color:#8b5cf6;color:#7c3aed;flex-shrink:0"><i class="fas fa-comment-dots"></i> <?php echo $lang==='th'?'สนทนากับ AI':'Chat with AI'; ?></a>
        </div>
    </div>
</div>

<!-- Lab Detail Modal -->
<div class="ld-overlay" id="labDetailOverlay" onclick="if(event.target===this)closeLabDetail()">
    <div class="ld-modal">
        <div class="ld-hdr">
            <div class="ld-hdr-icon"><i class="fas fa-building"></i></div>
            <div class="ld-hdr-info"><h3 id="ldTitle"></h3><div class="ld-hdr-sub" id="ldSubtitle"></div></div>
            <button class="ld-close" onclick="closeLabDetail()" title="Close"><i class="fas fa-times"></i></button>
        </div>
        <div class="ld-body" id="ldBody"><div class="ci-loading"><div class="ci-spinner"></div></div></div>
    </div>
</div>

<!-- Stock Detail Modal -->
<div class="sd-overlay" id="sdOverlay" onclick="if(event.target===this)closeSdModal()">
    <div class="sd-modal">
        <div class="sd-hdr">
            <div class="sd-hdr-gauge" id="sdGauge"></div>
            <div class="sd-hdr-info"><h3 id="sdTitle"></h3><div class="sd-hdr-sub" id="sdSub"></div></div>
            <button class="sd-close" onclick="closeSdModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="sd-body" id="sdBody"><div class="ci-loading"><div class="ci-spinner"></div></div></div>
    </div>
</div>
<div class="sd-toast" id="sdToast"></div>

<?php Layout::endContent(); ?>
<script>
const LANG='<?php echo $lang; ?>',TH=LANG==='th',ROLE='<?php echo $role; ?>';
const isAdmin=ROLE==='admin'||ROLE==='ceo',isMgr=ROLE==='lab_manager';
const t={
    total_chemicals:'<?php echo __("stat_total_chemicals"); ?>',
    active_containers:'<?php echo __("stat_active_containers"); ?>',
    total_users:'<?php echo __("stat_total_users"); ?>',
    total_labs:'<?php echo __("stat_total_labs"); ?>',
    pending:'<?php echo __("stat_pending_requests"); ?>',
    expiring:'<?php echo __("stat_expiring_soon"); ?>',
    low_stock:'<?php echo __("stat_low_stock"); ?>',
    team_members:'<?php echo __("stat_team_members"); ?>',
    my_chemicals:'<?php echo __("stat_my_chemicals"); ?>',
    active_borrows:'<?php echo __("stat_active_borrows"); ?>',
    no_alerts:'<?php echo __("alerts_no_new"); ?>',
    no_data:'<?php echo __("no_data"); ?>'
};
const _=s=>document.getElementById(s);
function num(v){return Number(v||0).toLocaleString()}
function esc(s){if(!s)return'';const d=document.createElement('div');d.textContent=s;return d.innerHTML}
function empty(icon,msg){return`<div class="bn-empty"><i class="fas ${icon}"></i><p>${msg}</p></div>`}

function animateNum(el,target){
    if(!el)return;const n=parseInt(target)||0;
    if(n===0){el.textContent='0';return}
    const dur=600,start=performance.now();
    function tick(now){const p=Math.min((now-start)/dur,1);el.textContent=Math.round(n*(1-Math.pow(1-p,3))).toLocaleString();if(p<1)requestAnimationFrame(tick)}
    requestAnimationFrame(tick);
}

function sc(color,icon,val,lbl,alert){
    return`<div class="bn-stat bn-anim" style="grid-column:span 2"><div class="bn-stat-icon ${color}"><i class="fas ${icon}"></i></div><div><div class="bn-stat-val">${val}</div><div class="bn-stat-lbl">${lbl}</div></div>${alert?'<div class="alert-dot"></div>':''}</div>`;
}

async function loadDashboard(){
    try{
        const data=await apiFetch('/v1/api/dashboard.php');
        if(!data.success)throw new Error(data.error);
        const d=data.data;
        if(isAdmin) renderAdmin(d);
        else if(isMgr) renderManager(d);
        else renderUser(d);
        renderAlerts(d);
    }catch(e){console.error('Dashboard error:',e)}
}

/* ═══════════════════════════════════════
   ADMIN / CEO
   ═══════════════════════════════════════ */
function renderAdmin(d){
    const s=d.summary||{};
    _('heroKPI').innerHTML=`
        <div class="bn-hero-kpi-item"><div class="kv" data-count="${s.total_chemicals||0}">0</div><div class="kl">${TH?'สารเคมี':'Chemicals'}</div></div>
        <div class="bn-hero-kpi-item"><div class="kv" data-count="${s.active_containers||0}">0</div><div class="kl">${TH?'ขวดสาร':'Containers'}</div></div>
        <div class="bn-hero-kpi-item"><div class="kv" data-count="${s.total_users||0}">0</div><div class="kl">${TH?'ผู้ใช้งาน':'Users'}</div></div>
        <div class="bn-hero-kpi-item"><div class="kv" data-count="${s.total_labs||0}">0</div><div class="kl">${TH?'ห้องปฏิบัติการ':'Labs'}</div></div>`;
    document.querySelectorAll('.bn-hero-kpi-item .kv[data-count]').forEach(el=>animateNum(el,el.dataset.count));

    const expLen=(d.expiring_soon||[]).length,lowLen=(d.low_stock||[]).length;
    _('bnStats').innerHTML=[
        sc('green','fa-flask',num(s.total_chemicals),t.total_chemicals),
        sc('blue','fa-box',num(s.active_containers),t.active_containers),
        sc('red','fa-times-circle',num(s.expired_containers||0),TH?'หมดอายุ':'Expired',parseInt(s.expired_containers)>0),
        sc('orange','fa-users',num(s.total_users),t.total_users),
        sc('purple','fa-building',num(s.total_labs),t.total_labs),
        sc('amber','fa-clock',num(expLen),t.expiring,expLen>0),
    ].join('');

    _('bnPanelATitle').textContent=TH?'ภาพรวมองค์กร':'Organization Overview';
    renderOrgOverview(d);
    renderExpiring(d.expiring_soon||[]);
    renderLowStock(d);
    renderTrend(d);
}

function renderOrgOverview(d){
    const labs=d.lab_performance||[],topC=d.top_consumed||[];
    const el=_('bnPanelABody');
    if(!labs.length&&!topC.length){el.innerHTML=empty('fa-chart-bar',t.no_data);return}
    const badge=_('bnPanelABadge');
    if(labs.length){badge.textContent=labs.length;badge.style.display='inline-flex'}

    let html='';
    if(labs.length){
        const totC=labs.reduce((s,l)=>s+parseInt(l.container_count||0),0);
        const totU=labs.reduce((s,l)=>s+parseInt(l.user_count||0),0);
        const totO=labs.reduce((s,l)=>s+parseInt(l.overdue_borrows||0),0);

        html+=`<div class="bn-lab-summary">
            <div class="bn-lab-sum-item" style="background:#eff6ff;color:#1d4ed8"><span class="sv">${num(totC)}</span><span class="sl">${TH?'ขวดรวม':'Containers'}</span></div>
            <div class="bn-lab-sum-item" style="background:#f0fdf4;color:#059669"><span class="sv">${num(totU)}</span><span class="sl">${TH?'ผู้ใช้รวม':'Users'}</span></div>
            <div class="bn-lab-sum-item" style="background:${totO>0?'#fef2f2':'#f0fdf4'};color:${totO>0?'#dc2626':'#059669'}"><span class="sv">${totO>0?num(totO):'✓'}</span><span class="sl">${TH?'เกินกำหนด':'Overdue'}</span></div>
        </div>`;

        const maxC=Math.max(...labs.map(l=>parseInt(l.container_count)||1));
        html+=`<table class="bn-lab-table"><thead><tr><th>${TH?'ชื่อห้อง':'Lab'}</th><th>${TH?'ขวด':'Cont.'}</th><th>${TH?'ผู้ใช้':'Users'}</th><th>${TH?'สถานะ':'Status'}</th></tr></thead><tbody>`;
        html+=labs.slice(0,6).map(l=>{
            const ov=parseInt(l.overdue_borrows)>0,br=parseInt(l.borrow_requests)>0;
            const pct=Math.round((parseInt(l.container_count||0)/maxC)*100);
            const dn=esc(l.name).replace(/'/g,"\\'");
            return`<tr onclick="openLabDetail('${dn}')" style="cursor:pointer;${ov?'background:#fffbeb':''}">
                <td><span class="bn-lab-name">${esc(l.name)}</span><div class="bn-lab-bar"><div class="bn-lab-bar-fill" style="width:${pct}%"></div></div></td>
                <td><span class="bn-lab-cnt">${num(l.container_count)}</span></td>
                <td><span class="bn-lab-usr">${num(l.user_count)}</span></td>
                <td>${ov?`<span class="bn-pill danger"><i class="fas fa-exclamation-circle"></i>${l.overdue_borrows}</span>`:br?`<span class="bn-pill info"><i class="fas fa-exchange-alt"></i>${l.borrow_requests}</span>`:`<span class="bn-pill ok"><i class="fas fa-check"></i>OK</span>`}</td>
            </tr>`}).join('');
        html+=`</tbody></table>`;
    }

    if(topC.length){
        html+=`<div class="bn-section" style="margin-top:18px"><i class="fas fa-fire" style="color:#ea580c"></i> ${TH?'สารที่ใช้มากสุด (30 วัน)':'Top Consumed (30 Days)'}</div>`;
        const maxU=Math.max(...topC.map(c=>Math.abs(c.total_consumed||c.usage_count||1)));
        html+=`<div class="bn-rank-list">`;
        html+=topC.slice(0,5).map((c,i)=>{
            const medals=['🥇','🥈','🥉'];
            const posCls=['gold','silver','bronze'][i]||'def';
            const consumed=Math.abs(c.total_consumed||0);
            const pct=Math.round((consumed/maxU)*100);
            const barCol=['linear-gradient(90deg,#f59e0b,#fbbf24)','linear-gradient(90deg,#94a3b8,#cbd5e1)','linear-gradient(90deg,#ea580c,#fb923c)'][i]||'linear-gradient(90deg,#6b7280,#9ca3af)';
            return`<div class="bn-rank-item">
                <div class="bn-rank-pos ${posCls}">${medals[i]||(i+1)}</div>
                <div class="bn-rank-info">
                    <div class="bn-rank-name">${esc(c.name)}</div>
                    <div class="bn-rank-bar"><div class="bn-rank-track"><div class="bn-rank-fill" style="width:${pct}%;background:${barCol}"></div></div><span class="bn-rank-pct">${num(c.usage_count)}×</span></div>
                </div>
                <div class="bn-rank-val"><div class="rv">${num(consumed)}</div><div class="rl">${TH?'ใช้ไป':'used'}</div></div>
            </div>`}).join('');
        html+=`</div>`;
    }
    el.innerHTML=html;
}

/* ── Fullscreen toggle ── */
function toggleChartFullscreen(){
    const panel=document.getElementById('bnPanelE');
    const btn=document.getElementById('bnFullscreenBtn');
    if(!panel)return;
    panel.classList.toggle('bn-fullscreen');
    const isFs=panel.classList.contains('bn-fullscreen');
    if(btn)btn.innerHTML=isFs?'<i class="fas fa-compress"></i>':'<i class="fas fa-expand"></i>';
    btn.title=isFs?(TH?'ออกจากเต็มจอ':'Exit Fullscreen'):(TH?'ขยายเต็มจอ':'Fullscreen');
    document.body.style.overflow=isFs?'hidden':'';
    // ESC key listener
    if(isFs){
        document.addEventListener('keydown',_fsEscHandler);
    } else {
        document.removeEventListener('keydown',_fsEscHandler);
    }
}
function _fsEscHandler(e){if(e.key==='Escape')toggleChartFullscreen()}

/* ── Line Chart State ── */
let _lcSeriesVisible={txn:true,qty:true};

function toggleSeries(key){
    _lcSeriesVisible[key]=!_lcSeriesVisible[key];
    const act=_lcSeriesVisible[key];
    document.querySelectorAll(`.bn-lc-s-${key}`).forEach(el=>{
        el.classList.toggle('inactive',!act);
    });
    const leg=document.querySelector(`.bn-lc-legend-item[data-series="${key}"]`);
    if(leg)leg.classList.toggle('inactive',!act);
}

function renderTrend(d){
    const trend=d.usage_trend||[],comp=d.compliance_status||{};
    if(!trend.length&&!comp.passed&&!comp.warnings&&!comp.failed)return;
    const panel=_('bnPanelE');panel.style.display='';
    _('bnPanelETitle').textContent=TH?'แนวโน้ม & ความปลอดภัย':'Trends & Compliance';

    const hasTrend=trend.length>0;
    const hasComp=(comp.passed||comp.warnings||comp.failed);
    let html=`<div class="bn-tc-wrap${!hasTrend||!hasComp?' style="grid-template-columns:1fr"':''}">`;

    /* ── Line Chart Side ── */
    if(hasTrend){
        const monthNames=TH?['ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.']:['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        const monthFull=TH?['มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม']:['January','February','March','April','May','June','July','August','September','October','November','December'];

        // Parse data
        const data=trend.map(t=>{
            const ms=(t.month||'').replace(/^\d{4}-/,'').replace(/^0/,'');
            const mi=parseInt(ms)-1;
            return {
                month:t.month,
                label:(mi>=0&&mi<12)?monthNames[mi]:ms,
                fullLabel:(mi>=0&&mi<12)?monthFull[mi]:ms,
                txn:parseInt(t.transactions)||0,
                qty:parseFloat(t.total_quantity)||0
            };
        });

        const maxTxn=Math.max(...data.map(d=>d.txn),1);
        const maxQty=Math.max(...data.map(d=>d.qty),1);
        const totalTxn=data.reduce((s,d)=>s+d.txn,0);
        const totalQty=data.reduce((s,d)=>s+d.qty,0);
        const avgTxn=Math.round(totalTxn/data.length);

        // SVG dimensions
        const svgW=500, svgH=180;
        const pad={top:20,right:20,bottom:32,left:42};
        const cw=svgW-pad.left-pad.right;
        const ch=svgH-pad.top-pad.bottom;

        // Scale functions
        const xOf=(i)=>pad.left+(i/(data.length-1||1))*cw;
        const yTxn=(v)=>pad.top+ch-(v/maxTxn)*ch;
        const yQty=(v)=>pad.top+ch-(v/maxQty)*ch;

        // Build path strings
        const pathTxn=data.map((d,i)=>`${i?'L':'M'}${xOf(i).toFixed(1)},${yTxn(d.txn).toFixed(1)}`).join(' ');
        const pathQty=data.map((d,i)=>`${i?'L':'M'}${xOf(i).toFixed(1)},${yQty(d.qty).toFixed(1)}`).join(' ');
        // Area fill paths
        const areaTxn=pathTxn+` L${xOf(data.length-1).toFixed(1)},${svgH-pad.bottom} L${pad.left},${svgH-pad.bottom} Z`;
        const areaQty=pathQty+` L${xOf(data.length-1).toFixed(1)},${svgH-pad.bottom} L${pad.left},${svgH-pad.bottom} Z`;

        const COL_TXN='#3b82f6', COL_QTY='#10b981';

        html+=`<div class="bn-chart">`;

        // Toolbar: legend
        html+=`<div class="bn-lc-toolbar">`;
        html+=`<div class="bn-lc-legend">`;
        html+=`<div class="bn-lc-legend-item" data-series="txn" onclick="toggleSeries('txn')">`;
        html+=`<span class="bn-lc-legend-dot" style="background:${COL_TXN};color:${COL_TXN}"></span>`;
        html+=`${TH?'จำนวนธุรกรรม':'Transactions'}</div>`;
        html+=`<div class="bn-lc-legend-item" data-series="qty" onclick="toggleSeries('qty')">`;
        html+=`<span class="bn-lc-legend-dot" style="background:${COL_QTY};color:${COL_QTY}"></span>`;
        html+=`${TH?'ปริมาณที่ใช้':'Quantity Used'}</div>`;
        html+=`</div></div>`;

        // SVG
        html+=`<div class="bn-lc-svg-wrap" id="lcSvgWrap">`;
        html+=`<div class="bn-lc-tooltip" id="lcTooltip"></div>`;
        html+=`<svg class="bn-lc-svg" viewBox="0 0 ${svgW} ${svgH}" preserveAspectRatio="none" id="lcSvg">`;

        // Defs: gradients
        html+=`<defs>`;
        html+=`<linearGradient id="gradTxn" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="${COL_TXN}" stop-opacity=".25"/><stop offset="100%" stop-color="${COL_TXN}" stop-opacity="0"/></linearGradient>`;
        html+=`<linearGradient id="gradQty" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="${COL_QTY}" stop-opacity=".25"/><stop offset="100%" stop-color="${COL_QTY}" stop-opacity="0"/></linearGradient>`;
        html+=`</defs>`;

        // Grid lines + Y labels
        html+=`<g class="bn-lc-grid">`;
        for(let i=0;i<=4;i++){
            const gy=pad.top+(ch/4)*i;
            const vTxn=Math.round(maxTxn*(1-i/4));
            html+=`<line x1="${pad.left}" y1="${gy}" x2="${svgW-pad.right}" y2="${gy}"/>`;
            html+=`<text x="${pad.left-4}" y="${gy+3}" text-anchor="end">${num(vTxn)}</text>`;
        }
        // X labels (month)
        data.forEach((d,i)=>{
            const x=xOf(i);
            html+=`<text x="${x}" y="${svgH-pad.bottom+14}" text-anchor="middle" class="bn-lc-axis-label">${d.label}</text>`;
        });
        html+=`</g>`;

        // Y-axis right label for Qty
        if(data.length>1){
            for(let i=0;i<=4;i++){
                const gy=pad.top+(ch/4)*i;
                const vQty=Math.round(maxQty*(1-i/4)*10)/10;
                html+=`<text x="${svgW-pad.right+4}" y="${gy+3}" text-anchor="start" class="bn-lc-grid" style="fill:${COL_QTY};font-size:8px">${vQty>999?num(Math.round(vQty)):vQty}</text>`;
            }
        }

        // Area fills
        html+=`<path d="${areaTxn}" fill="url(#gradTxn)" class="bn-lc-area bn-lc-s-txn"/>`;
        html+=`<path d="${areaQty}" fill="url(#gradQty)" class="bn-lc-area bn-lc-s-qty"/>`;

        // Lines
        html+=`<path d="${pathTxn}" class="bn-lc-line bn-lc-s-txn" stroke="${COL_TXN}"/>`;
        html+=`<path d="${pathQty}" class="bn-lc-line bn-lc-s-qty" stroke="${COL_QTY}"/>`;

        // Crosshair
        html+=`<line class="bn-lc-crosshair" id="lcCross" x1="0" y1="${pad.top}" x2="0" y2="${svgH-pad.bottom}"/>`;

        // Interactive dots
        data.forEach((d,i)=>{
            html+=`<circle cx="${xOf(i).toFixed(1)}" cy="${yTxn(d.txn).toFixed(1)}" fill="${COL_TXN}" class="bn-lc-dot bn-lc-s-txn" data-idx="${i}"/>`;
            html+=`<circle cx="${xOf(i).toFixed(1)}" cy="${yQty(d.qty).toFixed(1)}" fill="${COL_QTY}" class="bn-lc-dot bn-lc-s-qty" data-idx="${i}"/>`;
        });

        // Invisible hit areas for hover
        data.forEach((d,i)=>{
            const hw=cw/(data.length||1);
            html+=`<rect x="${xOf(i)-hw/2}" y="${pad.top}" width="${hw}" height="${ch}" fill="transparent" class="bn-lc-hit" data-idx="${i}" style="cursor:crosshair"/>`;
        });

        html+=`</svg></div>`;

        // Summary cards
        html+=`<div class="bn-lc-summary">`;
        html+=`<div class="bn-lc-sum-card" style="background:linear-gradient(135deg,#eff6ff,#dbeafe);border-color:#bfdbfe;color:#1e40af">`;
        html+=`<div class="bn-lc-sum-val">${num(totalTxn)}</div>`;
        html+=`<div class="bn-lc-sum-lbl">${TH?'ธุรกรรมรวม':'Total Txns'}</div></div>`;
        html+=`<div class="bn-lc-sum-card" style="background:linear-gradient(135deg,#ecfdf5,#d1fae5);border-color:#a7f3d0;color:#065f46">`;
        html+=`<div class="bn-lc-sum-val">${num(Math.round(totalQty))}</div>`;
        html+=`<div class="bn-lc-sum-lbl">${TH?'ปริมาณรวม':'Total Qty'}</div></div>`;
        html+=`<div class="bn-lc-sum-card" style="background:linear-gradient(135deg,#f8fafc,#f1f5f9);border-color:#e2e8f0;color:#334155">`;
        html+=`<div class="bn-lc-sum-val">${num(avgTxn)}</div>`;
        html+=`<div class="bn-lc-sum-lbl">${TH?'เฉลี่ย/เดือน':'Avg/Month'}</div></div>`;
        html+=`<div class="bn-lc-sum-card" style="background:linear-gradient(135deg,#faf5ff,#f3e8ff);border-color:#e9d5ff;color:#6b21a8">`;
        html+=`<div class="bn-lc-sum-val">${data.length}</div>`;
        html+=`<div class="bn-lc-sum-lbl">${TH?'เดือน':'Months'}</div></div>`;
        html+=`</div>`;

        html+=`</div>`; // close bn-chart

        // Store data for tooltip interactions
        window._lcData=data;
        window._lcMaxTxn=maxTxn;
        window._lcMaxQty=maxQty;
        window._lcPad=pad;
        window._lcSvgW=svgW;
        window._lcSvgH=svgH;
        window._lcCW=cw;
        window._lcCH=ch;
        window._lcColTxn=COL_TXN;
        window._lcColQty=COL_QTY;
    }

    /* ── Compliance Side ── */
    if(hasComp){
        const p=parseInt(comp.passed)||0, w=parseInt(comp.warnings)||0, f=parseInt(comp.failed)||0;
        const total=p+w+f||1;
        const scorePct=Math.round((p/total)*100);
        const ringR=30, ringCirc=2*Math.PI*ringR, ringOff=ringCirc-(scorePct/100)*ringCirc;
        const ringCol=scorePct>=80?'#10b981':scorePct>=50?'#f59e0b':'#ef4444';
        const grade=scorePct>=90?'A+':scorePct>=80?'A':scorePct>=70?'B':scorePct>=50?'C':'D';
        const gradeEmoji=scorePct>=80?'🛡️':scorePct>=50?'⚠️':'🚨';
        const statusText=scorePct>=80?(TH?'ระบบปลอดภัยดี':'Systems healthy'):scorePct>=50?(TH?'ต้องปรับปรุง':'Needs improvement'):(TH?'ต้องดำเนินการด่วน':'Action required');
        const pPct=Math.round((p/total)*100), wPct=Math.round((w/total)*100), fPct=Math.round((f/total)*100);

        html+=`<div class="bn-comp-pro">`;
        html+=`<div class="bn-section"><i class="fas fa-shield-alt" style="color:#10b981"></i> ${TH?'สถานะความปลอดภัย':'Safety Compliance'}</div>`;

        // Score ring card
        html+=`<div class="bn-comp-score">
            <div class="bn-comp-ring">
                <svg viewBox="0 0 68 68"><circle cx="34" cy="34" r="${ringR}" class="ring-track"/><circle cx="34" cy="34" r="${ringR}" class="ring-fill" style="stroke:${ringCol};stroke-dasharray:${ringCirc.toFixed(1)};stroke-dashoffset:${ringOff.toFixed(1)}"/></svg>
                <div class="bn-comp-ring-val"><span class="rv" style="color:${ringCol}">${grade}</span><span class="rl" style="color:${ringCol}">${scorePct}%</span></div>
            </div>
            <div class="bn-comp-score-info">
                <div class="bn-comp-score-title">${statusText}</div>
                <div class="bn-comp-score-desc">${TH?'ตรวจสอบทั้งหมด '+num(total)+' รายการ — ผ่าน '+pPct+'%':'Checked '+num(total)+' items — '+pPct+'% passed'}</div>
                <div class="bn-comp-legend" style="margin-top:8px">
                    ${p?`<div class="seg-pass" style="width:${pPct}%"></div>`:''}
                    ${w?`<div class="seg-warn" style="width:${wPct}%"></div>`:''}
                    ${f?`<div class="seg-fail" style="width:${fPct}%"></div>`:''}
                </div>
            </div>
            <div class="bn-comp-score-shield">${gradeEmoji}</div>
        </div>`;

        // 3 cards
        html+=`<div class="bn-comp-cards">
            <div class="bn-comp-card pass"><span class="bn-comp-card-ic">✅</span><span class="cc-val">${num(p)}</span><span class="cc-lbl">${TH?'ผ่าน':'Passed'}</span><span class="cc-pct">${pPct}%</span></div>
            <div class="bn-comp-card warn"><span class="bn-comp-card-ic">⚠️</span><span class="cc-val">${num(w)}</span><span class="cc-lbl">${TH?'เตือน':'Warnings'}</span><span class="cc-pct">${wPct}%</span></div>
            <div class="bn-comp-card fail"><span class="bn-comp-card-ic">❌</span><span class="cc-val">${num(f)}</span><span class="cc-lbl">${TH?'ไม่ผ่าน':'Failed'}</span><span class="cc-pct">${fPct}%</span></div>
        </div>`;

        html+=`</div>`; // close bn-comp-pro
    }

    html+=`</div>`; // close bn-tc-wrap
    _('bnPanelEBody').innerHTML=html;

    // ── Bind line chart interactions ──
    const svgWrap=document.getElementById('lcSvgWrap');
    const tooltip=document.getElementById('lcTooltip');
    const cross=document.getElementById('lcCross');
    if(svgWrap && tooltip && window._lcData){
        const dd=window._lcData;
        const xOf=(i)=>window._lcPad.left+(i/(dd.length-1||1))*window._lcCW;

        // Hit zone hover
        svgWrap.querySelectorAll('.bn-lc-hit').forEach(rect=>{
            rect.addEventListener('mouseenter',function(e){
                const idx=parseInt(this.dataset.idx);
                showLcTooltip(idx,e);
            });
            rect.addEventListener('mousemove',function(e){
                const idx=parseInt(this.dataset.idx);
                positionLcTooltip(idx,e);
            });
            rect.addEventListener('mouseleave',function(){
                hideLcTooltip();
            });
        });

        // Dot hover
        svgWrap.querySelectorAll('.bn-lc-dot').forEach(dot=>{
            dot.addEventListener('mouseenter',function(e){
                const idx=parseInt(this.dataset.idx);
                showLcTooltip(idx,e);
            });
            dot.addEventListener('mouseleave',hideLcTooltip);
        });

        function showLcTooltip(idx,e){
            const d=dd[idx];
            if(!d)return;
            // crosshair
            if(cross){
                cross.setAttribute('x1',xOf(idx));
                cross.setAttribute('x2',xOf(idx));
                cross.classList.add('active');
            }
            // highlight dots
            svgWrap.querySelectorAll('.bn-lc-dot').forEach(dot=>{
                dot.style.opacity=parseInt(dot.dataset.idx)===idx?1:.3;
            });

            // Change from previous
            let txnChange='',qtyChange='';
            if(idx>0){
                const prevTxn=dd[idx-1].txn;
                const diffTxn=d.txn-prevTxn;
                if(prevTxn>0){
                    const pctTxn=Math.round((diffTxn/prevTxn)*100);
                    txnChange=`<span class="bn-lc-tooltip-change ${diffTxn>=0?'up':'down'}">${diffTxn>=0?'▲':'▼'}${Math.abs(pctTxn)}%</span>`;
                }
                const prevQty=dd[idx-1].qty;
                const diffQty=d.qty-prevQty;
                if(prevQty>0){
                    const pctQty=Math.round((diffQty/prevQty)*100);
                    qtyChange=`<span class="bn-lc-tooltip-change ${diffQty>=0?'up':'down'}">${diffQty>=0?'▲':'▼'}${Math.abs(pctQty)}%</span>`;
                }
            }

            tooltip.innerHTML=`
                <div class="bn-lc-tooltip-title">📅 ${d.fullLabel} (${d.month})</div>
                <div class="bn-lc-tooltip-row">
                    <span class="bn-lc-tooltip-dot" style="background:${window._lcColTxn}"></span>
                    ${TH?'ธุรกรรม':'Transactions'}
                    <span class="bn-lc-tooltip-val">${num(d.txn)} ${TH?'ครั้ง':'txns'}</span>
                    ${txnChange}
                </div>
                <div class="bn-lc-tooltip-row">
                    <span class="bn-lc-tooltip-dot" style="background:${window._lcColQty}"></span>
                    ${TH?'ปริมาณ':'Quantity'}
                    <span class="bn-lc-tooltip-val">${num(Math.round(d.qty*100)/100)}</span>
                    ${qtyChange}
                </div>`;
            tooltip.classList.add('show');
            positionLcTooltip(idx,e);
        }

        function positionLcTooltip(idx,e){
            const wrapRect=svgWrap.getBoundingClientRect();
            const tw=tooltip.offsetWidth||160;
            const th=tooltip.offsetHeight||80;
            // position relative to wrapper
            let left=e.clientX-wrapRect.left-tw/2;
            let top=e.clientY-wrapRect.top-th-16;
            // clamp
            left=Math.max(4,Math.min(left,wrapRect.width-tw-4));
            if(top<0)top=e.clientY-wrapRect.top+16;
            tooltip.style.left=left+'px';
            tooltip.style.top=top+'px';
        }

        function hideLcTooltip(){
            tooltip.classList.remove('show');
            if(cross)cross.classList.remove('active');
            svgWrap.querySelectorAll('.bn-lc-dot').forEach(dot=>dot.style.opacity=1);
        }
    }
}

/* ═══════════════════════════════════════
   LAB MANAGER
   ═══════════════════════════════════════ */
function renderManager(d){
    const s=d.summary||{},pend=parseInt(s.pending_requests)||0;
    _('heroKPI').innerHTML=`
        <div class="bn-hero-kpi-item"><div class="kv" data-count="${s.unique_chemicals||s.total_chemicals||0}">0</div><div class="kl">${TH?'สารเคมี':'Chemicals'}</div></div>
        <div class="bn-hero-kpi-item"><div class="kv" data-count="${s.active_containers||0}">0</div><div class="kl">${TH?'ขวดที่ใช้งาน':'Active'}</div></div>
        <div class="bn-hero-kpi-item"><div class="kv" data-count="${s.team_members||0}">0</div><div class="kl">${TH?'สมาชิกทีม':'Team'}</div></div>
        <div class="bn-hero-kpi-item"><div class="kv ${pend>0?'pulse':''}" data-count="${pend}">0</div><div class="kl">${TH?'รออนุมัติ':'Pending'}</div></div>`;
    document.querySelectorAll('.bn-hero-kpi-item .kv[data-count]').forEach(el=>animateNum(el,el.dataset.count));

    const exp=parseInt(s.expiring_soon)||0;
    _('bnStats').innerHTML=[
        sc('green','fa-flask',num(s.unique_chemicals||s.total_chemicals),t.total_chemicals),
        sc('blue','fa-box',num(s.active_containers),t.active_containers),
        sc('red','fa-times-circle',num(s.expired_containers||0),TH?'หมดอายุ':'Expired',parseInt(s.expired_containers)>0),
        sc('orange','fa-users',num(s.team_members),t.team_members),
        sc('amber','fa-clock',num(exp),t.expiring,exp>0),
        sc('purple','fa-hourglass-half',num(pend),t.pending,pend>0),
    ].join('');

    _('bnPanelATitle').textContent=TH?'กิจกรรมทีม':'Team Activity';
    _('bnPanelAHdr').querySelector('i').className='fas fa-users';
    renderTeamActivity(d);
    renderExpiring(d.expiring_chemicals||[]);
    renderLowStock(d);
    renderPendingRequests(d.pending_requests||[]);
    renderBorrowed(d.borrowed_chemicals||[]);
}

function renderTeamActivity(d){
    const team=d.team_activity||[],el=_('bnPanelABody');
    if(!team.length){el.innerHTML=empty('fa-users',TH?'ยังไม่มีข้อมูลทีม':'No team data');return}
    const badge=_('bnPanelABadge');badge.textContent=team.length;badge.style.display='inline-flex';

    let html=`<div class="bn-section"><i class="fas fa-users"></i> ${TH?'สมาชิกในทีม':'Team Members'}</div>`;
    html+=team.map(m=>{
        const ini=((m.first_name||'')[0]+(m.last_name||'')[0]).toUpperCase();
        const ov=parseInt(m.overdue_count)>0;
        return`<div class="bn-team-row">
            <div class="bn-team-avatar" style="${ov?'background:linear-gradient(135deg,#fecaca,#fca5a5);color:#b91c1c':''}">${ini}</div>
            <div class="bn-team-info"><div class="bn-team-name">${esc(m.first_name)} ${esc(m.last_name)}</div><div class="bn-team-meta"><i class="fas fa-box" style="font-size:8px;margin-right:2px"></i>${num(m.owned_containers)} ${TH?'ขวด':'bottles'} · <i class="fas fa-exchange-alt" style="font-size:8px;margin:0 2px"></i>${num(m.borrow_count)} ${TH?'ยืม':'borrows'}</div></div>
            <div>${ov?`<span class="bn-pill danger"><i class="fas fa-exclamation-triangle"></i>${m.overdue_count}</span>`:`<span class="bn-pill ok"><i class="fas fa-check-circle"></i>OK</span>`}</div>
        </div>`}).join('');

    const acts=d.recent_activity||[];
    if(acts.length){
        html+=`<div class="bn-section" style="margin-top:16px"><i class="fas fa-history"></i> ${TH?'กิจกรรมล่าสุด':'Recent Activity'}</div>`;
        html+=acts.slice(0,5).map(a=>feedItem(a)).join('');
    }
    el.innerHTML=html;
}

function renderBorrowed(list){
    if(!list||!list.length)return;
    const p=_('bnPanelF');p.style.display='';
    _('bnPanelFTitle').textContent=TH?'สารที่ถูกยืมอยู่':'Currently Borrowed';
    const b=_('bnPanelFBadge');b.textContent=list.length;b.style.display='inline-flex';b.className='bn-card-badge info';
    _('bnPanelFBody').innerHTML=list.slice(0,5).map(b=>{
        const days=parseInt(b.days_remaining)||0,urgent=days<=3;
        return`<div class="bn-borrow-item">
            <div style="width:34px;height:34px;border-radius:9px;background:${urgent?'linear-gradient(135deg,#fecaca,#fca5a5)':'linear-gradient(135deg,#e9d5ff,#d8b4fe)'};color:${urgent?'#b91c1c':'#7c3aed'};display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0"><i class="fas fa-hand-holding-medical"></i></div>
            <div style="flex:1;min-width:0"><div style="font-size:12px;font-weight:700;color:var(--c1);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(b.name)}</div><div style="font-size:10px;color:var(--c3);margin-top:1px">${esc(b.first_name)} ${esc(b.last_name)} · ${num(b.requested_quantity)} ${b.quantity_unit||''}</div></div>
            <div style="text-align:right;flex-shrink:0"><div style="font-size:15px;font-weight:800;color:${urgent?'#dc2626':'var(--c1)'}">${Math.abs(days)}</div><div style="font-size:9px;color:var(--c3)">${days<0?(TH?'เกินกำหนด':'overdue'):(TH?'วัน':'days')}</div></div>
        </div>`}).join('');
}

/* ═══════════════════════════════════════
   USER
   ═══════════════════════════════════════ */
function renderUser(d){
    const s=d.quick_stats||{},myChem=d.my_chemicals||[],pend=parseInt(s.pending_requests)||0;
    _('heroKPI').innerHTML=`
        <div class="bn-hero-kpi-item"><div class="kv" data-count="${s.owned_count||0}">0</div><div class="kl">${TH?'ขวดสารของฉัน':'My Bottles'}</div></div>
        <div class="bn-hero-kpi-item"><div class="kv" data-count="${s.active_borrows||0}">0</div><div class="kl">${TH?'กำลังยืม':'Borrowing'}</div></div>
        <div class="bn-hero-kpi-item"><div class="kv ${pend>0?'pulse':''}" data-count="${pend}">0</div><div class="kl">${TH?'รออนุมัติ':'Pending'}</div></div>`;
    document.querySelectorAll('.bn-hero-kpi-item .kv[data-count]').forEach(el=>animateNum(el,el.dataset.count));

    const reqForMe=(d.requests_for_me||[]).length;
    _('bnStats').innerHTML=[
        sc('green','fa-box',num(s.owned_count),TH?'ขวดสารของฉัน':'My Bottles'),
        sc('blue','fa-hand-holding-medical',num(s.active_borrows),t.active_borrows),
        sc('amber','fa-clock',num(pend),t.pending,pend>0),
        sc('purple','fa-inbox',num(reqForMe),TH?'คำขอจากผู้อื่น':'Requests for Me',reqForMe>0),
    ].join('');

    _('bnPanelATitle').textContent=TH?'สารเคมีของฉัน':'My Chemicals';
    _('bnPanelAHdr').querySelector('i').className='fas fa-flask';
    _('bnPanelAHdr').querySelector('i').style.color='#059669';
    _('bnPanelA').className='bn-card accent-green bn-anim bn-d3';
    renderMyChemicals(myChem);
    renderBorrowHistory(d.borrow_history||[]);
    renderUserPending(d);
}

function renderMyChemicals(list){
    const el=_('bnPanelABody'),badge=_('bnPanelABadge');
    if(!list.length){
        el.innerHTML=`<div class="bn-empty"><i class="fas fa-flask"></i><p>${TH?'ยังไม่มีสารเคมี':'No chemicals yet'}</p><a href="/v1/pages/containers.php?action=add" class="ci-btn ci-btn-primary ci-btn-sm" style="margin-top:10px"><i class="fas fa-plus"></i> ${TH?'เพิ่มขวดสาร':'Add Bottle'}</a></div>`;
        return;
    }
    badge.textContent=list.length;badge.style.display='inline-flex';
    let html=list.slice(0,8).map(c=>`
        <div class="bn-mychem">
            <div class="bn-mychem-ic"><i class="fas fa-vial"></i></div>
            <div style="flex:1;min-width:0"><div style="font-size:12px;font-weight:700;color:var(--c1);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(c.name)}</div><div style="font-size:10px;color:var(--c3);margin-top:1px">${c.cas_number?'CAS: '+c.cas_number:''} ${c.location_name?'· '+esc(c.location_name):''}</div></div>
            <div style="text-align:right;flex-shrink:0"><div style="font-size:15px;font-weight:800;color:var(--c1)">${num(c.current_quantity)}</div><div style="font-size:9px;color:var(--c3)">${c.quantity_unit||''}</div></div>
        </div>`).join('');
    if(list.length>8) html+=`<div style="text-align:center;margin-top:10px"><a href="/v1/pages/containers.php" class="ci-btn ci-btn-secondary ci-btn-sm"><i class="fas fa-arrow-right"></i> ${TH?'ดูทั้งหมด '+list.length+' รายการ':'View All '+list.length}</a></div>`;
    el.innerHTML=html;
}

function renderUserPending(d){
    const reqForMe=d.requests_for_me||[];
    if(reqForMe.length) renderPendingRequests(reqForMe);
    const myPend=d.my_pending_requests||[];
    if(myPend.length){
        const p=_('bnPanelF');p.style.display='';
        _('bnPanelFTitle').textContent=TH?'คำขอที่ฉันส่ง':'My Sent Requests';
        _('bnPanelFHdr').querySelector('i').className='fas fa-paper-plane';
        _('bnPanelFHdr').querySelector('i').style.color='#2563eb';
        const b=_('bnPanelFBadge');b.textContent=myPend.length;b.style.display='inline-flex';b.className='bn-card-badge info';
        _('bnPanelFBody').innerHTML=myPend.map(r=>`
            <div class="bn-req-item" style="background:#f0f7ff;border-color:#bfdbfe">
                <div style="width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,#bfdbfe,#93c5fd);color:#1d4ed8;display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0"><i class="fas fa-paper-plane"></i></div>
                <div style="flex:1;min-width:0"><div style="font-size:12px;font-weight:700;color:var(--c1);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(r.name)}</div><div style="font-size:10px;color:var(--c3);margin-top:1px">${num(r.requested_quantity)} ${r.quantity_unit||''} · ${statusBadge(r.status)}</div></div>
                <div style="font-size:10px;color:var(--c3);flex-shrink:0">${formatDate(r.created_at)}</div>
            </div>`).join('');
    }
}

function renderBorrowHistory(list){
    const p=_('bnPanelD');p.style.display='';
    _('bnPanelDTitle').textContent=TH?'ประวัติการยืม':'Borrow History';
    p.querySelector('.bn-card-title i').className='fas fa-history';
    p.querySelector('.bn-card-title i').style.color='#7c3aed';
    p.className='bn-card accent-purple bn-anim bn-d4';
    _('bnPanelDBadge').style.display='none';
    if(!list.length){_('bnPanelDBody').innerHTML=empty('fa-history',TH?'ยังไม่มีประวัติการยืม':'No borrow history');return}
    _('bnPanelDBody').innerHTML=list.slice(0,5).map(b=>{
        const cls=b.status==='fulfilled'?'used':b.status==='pending'?'default':'added';
        const ic=b.status==='fulfilled'?'fa-hand-holding-medical':b.status==='pending'?'fa-clock':'fa-undo';
        return`<div class="bn-feed-item">
            <div class="bn-feed-dot ${cls}"><i class="fas ${ic}"></i></div>
            <div style="flex:1;min-width:0"><div style="font-size:12px;font-weight:600;color:var(--c1);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(b.chemical_name)}</div><div style="font-size:10px;color:var(--c3);margin-top:1px">${num(b.requested_quantity)} ${b.quantity_unit||''} · ${statusBadge(b.status)} · ${formatDate(b.created_at)}</div></div>
        </div>`}).join('');
}

/* ═══════════════════════════════════════
   SHARED RENDERERS
   ═══════════════════════════════════════ */
function feedItem(a){
    const cls={'used':'used','added':'added','removed':'removed'}[a.action_type]||'default';
    const ic={'used':'fa-eye-dropper','added':'fa-plus-circle','removed':'fa-minus-circle','transferred':'fa-people-arrows','disposed':'fa-trash'}[a.action_type]||'fa-circle';
    const user=a.first_name?`${esc(a.first_name)} ${esc(a.last_name)} — `:'';
    return`<div class="bn-feed-item"><div class="bn-feed-dot ${cls}"><i class="fas ${ic}"></i></div><div style="flex:1;min-width:0"><div style="font-size:12px;font-weight:500;color:var(--c1);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${user}${esc(a.chemical_name||a.description||'')}</div><div style="font-size:10px;color:var(--c3);margin-top:1px">${formatDate(a.created_at||a.requested_at)}</div></div></div>`;
}

function renderAlerts(d){
    const el=_('bnAlerts'),alerts=d.alerts||[];
    const badge=_('alertBadge');
    if(badge&&alerts.length){badge.textContent=alerts.length;badge.style.display='inline-flex'}
    if(!alerts.length){el.innerHTML=`<div class="bn-empty" style="padding:20px"><i class="fas fa-check-circle" style="color:#059669;opacity:.4"></i><p>${t.no_alerts}</p></div>`;return}
    const iconMap={expiry:'fa-clock',low_stock:'fa-box-open',overdue:'fa-exclamation-triangle',safety:'fa-shield-alt'};
    const bgMap={overdue:'linear-gradient(135deg,#fecaca,#fca5a5)',expiry:'linear-gradient(135deg,#fde68a,#fcd34d)',low_stock:'linear-gradient(135deg,#fed7aa,#fdba74)',safety:'linear-gradient(135deg,#e9d5ff,#d8b4fe)'};
    const clrMap={expiry:'#92400e',low_stock:'#9a3412',overdue:'#991b1b',safety:'#6b21a8'};
    el.innerHTML=alerts.slice(0,5).map(a=>`
        <div class="bn-alert-row">
            <div class="bn-alert-icon" style="background:${bgMap[a.type]||'linear-gradient(135deg,#f3f4f6,#e5e7eb)'};color:${clrMap[a.type]||'#4b5563'}"><i class="fas ${iconMap[a.type]||'fa-bell'}"></i></div>
            <div style="flex:1;min-width:0"><div class="bn-alert-msg">${a.message||a.title||''}</div><div class="bn-alert-time">${formatDate(a.created_at)}</div></div>
        </div>`).join('');
}

function renderExpiring(list){
    if(!list.length)return;
    const p=_('bnPanelC');p.style.display='';
    _('bnPanelCTitle').textContent=TH?'ใกล้หมดอายุ':'Expiring Soon';
    const badge=_('bnPanelCBadge');badge.textContent=list.length;badge.style.display='inline-flex';

    let html='<div class="bn-exp-list">';
    html+=list.slice(0,6).map(e=>{
        const days=parseInt(e.days_until_expiry||e.days_left||0);
        const sev=days<=7?'danger':days<=14?'warn':'ok';
        const urgPct=Math.max(0,Math.min(100,Math.round(((30-Math.max(0,days))/30)*100)));
        const urgCol=days<=7?'#ef4444':days<=14?'#f59e0b':'#10b981';
        const dateStr=e.expiry_date?new Date(e.expiry_date).toLocaleDateString(TH?'th-TH':'en-US',{day:'numeric',month:'short'}):'';
        return`<div class="bn-exp-item sev-${sev}">
            <div class="bn-exp-days ${sev}"><span class="ed-num">${Math.abs(days)}</span><span class="ed-unit">${days<0?(TH?'เลย':'ago'):(TH?'วัน':'days')}</span></div>
            <div class="bn-exp-info">
                <div class="bn-exp-name">${esc(e.name||e.chemical_name||'')}</div>
                <div class="bn-exp-meta">
                    ${e.lab_name?`<span class="bn-exp-tag"><i class="fas fa-building"></i>${esc(e.lab_name)}</span>`:''}
                    ${e.current_quantity?`<span class="bn-exp-tag"><i class="fas fa-vial"></i>${e.current_quantity} ${e.quantity_unit||''}</span>`:''}
                    ${e.first_name?`<span class="bn-exp-tag"><i class="fas fa-user"></i>${esc(e.first_name)}</span>`:''}
                </div>
            </div>
            <div class="bn-exp-right">
                <div class="bn-exp-date">${dateStr}</div>
                <div class="bn-exp-urgency"><div class="bn-exp-urgency-fill" style="width:${urgPct}%;background:${urgCol}"></div></div>
            </div>
        </div>`}).join('');
    html+='</div>';
    _('bnPanelCBody').innerHTML=html;
}

function renderLowStock(d){
    const items=d.low_stock||d.chemicals_to_reorder||[];
    if(!items.length)return;
    const p=_('bnPanelD');p.style.display='';
    _('bnPanelDTitle').textContent=TH?'สต็อกต่ำ':'Low Stock';
    const badge=_('bnPanelDBadge');badge.textContent=items.length;badge.style.display='inline-flex';

    let html='<div class="bn-ls-list">';
    html+=items.slice(0,6).map(i=>{
        const pct=Math.max(0,Math.min(100,Math.round(i.remaining_percentage||i.avg_remaining||0)));
        const sev=pct<=10?'crit':pct<=20?'warn':'low';
        const col=pct<=10?'#ef4444':pct<=20?'#f59e0b':'#fb923c';
        const sevTxt=pct<=10?(TH?'วิกฤต':'CRITICAL'):pct<=20?(TH?'เตือน':'WARNING'):(TH?'ต่ำ':'LOW');
        const r=18,circ=2*Math.PI*r,off=circ-(pct/100)*circ;
        const qty=i.current_quantity!=null?parseFloat(i.current_quantity):i.total_quantity!=null?parseFloat(i.total_quantity):'-';
        const sid=i.container_id||i.id||0;
        return`<div class="bn-ls-item sev-${sev}" onclick="openStockDetail(${sid})" style="cursor:pointer">
            <div class="bn-ls-gauge">
                <svg viewBox="0 0 40 40"><circle cx="20" cy="20" r="${r}" class="track"/><circle cx="20" cy="20" r="${r}" class="fill" style="stroke:${col};stroke-dasharray:${circ.toFixed(1)};stroke-dashoffset:${off.toFixed(1)}"/></svg>
                <div class="bn-ls-pct" style="color:${col}">${pct}%</div>
            </div>
            <div class="bn-ls-info">
                <div class="bn-ls-name">${esc(i.name||'')}</div>
                <div class="bn-ls-tags">${i.cas_number?`<span class="bn-ls-tag"><i class="fas fa-hashtag"></i>${i.cas_number}</span>`:''}${i.lab_name?`<span class="bn-ls-tag"><i class="fas fa-building"></i>${esc(i.lab_name)}</span>`:''}</div>
            </div>
            <div class="bn-ls-right">
                <div class="bn-ls-qty">${typeof qty==='number'?qty.toLocaleString():qty}</div>
                <div class="bn-ls-unit">${i.quantity_unit||''}</div>
                <span class="bn-ls-sev ${sev}">${sevTxt}</span>
            </div>
        </div>`}).join('');
    html+='</div>';
    _('bnPanelDBody').innerHTML=html;
}

function renderPendingRequests(list){
    if(!list||!list.length)return;
    const p=_('bnPanelG');p.style.display='';
    _('bnPanelGTitle').textContent=TH?'คำขอรออนุมัติ':'Pending Requests';
    const badge=_('bnPanelGBadge');badge.textContent=list.length;badge.style.display='inline-flex';
    _('bnPanelGBody').innerHTML=list.slice(0,5).map(r=>`
        <div class="bn-req-item">
            <div style="width:34px;height:34px;border-radius:9px;background:linear-gradient(135deg,#fde68a,#fbbf24);color:#92400e;display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0"><i class="fas fa-hand-holding-medical"></i></div>
            <div style="flex:1;min-width:0"><div style="font-size:12px;font-weight:700;color:var(--c1);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(r.name||r.chemical_name||'')}</div><div style="font-size:10px;color:var(--c3);margin-top:1px">${r.first_name?esc(r.first_name+' '+r.last_name)+' · ':''}${num(r.requested_quantity)} ${r.quantity_unit||''} ${r.purpose?'· '+esc(r.purpose):''}</div></div>
            <a href="/v1/pages/borrow.php" class="ci-btn ci-btn-sm ci-btn-primary" style="padding:4px 10px;font-size:10px;flex-shrink:0"><i class="fas fa-arrow-right"></i></a>
        </div>`).join('');
}

/* ═══════════════════════════════════════
   STOCK DETAIL MODAL
   ═══════════════════════════════════════ */
let _sdCurrent=null; // current stock detail data

async function openStockDetail(stockId){
    if(!stockId)return;
    const ov=_('sdOverlay');
    _('sdTitle').textContent=TH?'กำลังโหลด...':'Loading...';
    _('sdSub').textContent='';
    _('sdGauge').innerHTML='';
    _('sdBody').innerHTML='<div class="ci-loading"><div class="ci-spinner"></div></div>';
    ov.classList.add('show');
    try{
        const r=await apiFetch('/v1/api/stock.php?action=detail&id='+stockId);
        if(!r.success)throw new Error(r.error);
        _sdCurrent=r.data;
        renderStockDetail(r.data);
    }catch(e){
        _('sdBody').innerHTML=`<div class="ld-empty"><i class="fas fa-exclamation-triangle"></i><p>${esc(e.message)}</p></div>`;
    }
}
function closeSdModal(){
    _('sdOverlay').classList.remove('show');
    _sdCurrent=null;
}

function renderStockDetail(d){
    const pct=Math.max(0,Math.min(100,Math.round(parseFloat(d.remaining_pct)||0)));
    const col=pct<=10?'#ef4444':pct<=20?'#f59e0b':pct<=50?'#fb923c':'#10b981';
    const sev=pct<=10?'crit':pct<=20?'warn':'low';
    const sevTxt=pct<=10?(TH?'⚠️ วิกฤต — ต้องดำเนินการด่วน':'⚠️ CRITICAL — Immediate action needed'):pct<=20?(TH?'⚠️ เตือน — สต็อกต่ำ':'⚠️ WARNING — Low stock'):(TH?'📦 สต็อกต่ำ':'📦 Low stock');
    const name=d.linked_chem_name||d.chemical_name||'';

    // Header gauge
    const gr=22,gcirc=2*Math.PI*gr,goff=gcirc-(pct/100)*gcirc;
    _('sdGauge').innerHTML=`<svg viewBox="0 0 50 50"><circle cx="25" cy="25" r="${gr}" class="trk"/><circle cx="25" cy="25" r="${gr}" class="fl" style="stroke:${col};stroke-dasharray:${gcirc.toFixed(1)};stroke-dashoffset:${goff.toFixed(1)}"/></svg><div class="sd-hdr-pct" style="color:${col}">${pct}%</div>`;
    _('sdTitle').textContent=name;
    _('sdSub').innerHTML=[
        d.bottle_code?`<span><i class="fas fa-barcode" style="margin-right:3px"></i>${esc(d.bottle_code)}</span>`:'',
        d.cas_no?`<span>CAS: ${esc(d.cas_no)}</span>`:'',
        d.grade?`<span>${esc(d.grade)}</span>`:''
    ].filter(Boolean).join('<span style="opacity:.3">·</span>');

    let html='';

    // Severity banner
    html+=`<div class="sd-sev ${sev}"><i class="fas ${pct<=10?'fa-exclamation-triangle':pct<=20?'fa-exclamation-circle':'fa-info-circle'}"></i>${sevTxt}</div>`;

    // Info grid
    const remQ=parseFloat(d.remaining_qty)||0;
    const pkgS=parseFloat(d.package_size)||0;
    const owner=d.owner_first?`${esc(d.owner_first)} ${esc(d.owner_last)}`:(d.owner_name||'-');
    html+=`<div class="sd-grid">`;
    html+=sdField(TH?'รหัสขวด':'Bottle Code',d.bottle_code||'-');
    html+=sdField('CAS No.',d.cas_no||d.linked_cas||'-');
    html+=sdField(TH?'เกรด':'Grade',d.grade||'-');
    html+=sdField(TH?'สถานะ':'Status',`<span style="color:${col};font-weight:800">${(d.status||'active').toUpperCase()}</span>`);
    html+=sdField(TH?'เจ้าของ':'Owner',owner);
    html+=sdField(TH?'ฝ่าย':'Department',d.owner_department||'-');
    html+=sdField(TH?'ที่จัดเก็บ':'Storage',d.storage_location||'-');
    html+=sdField(TH?'วันที่เพิ่ม':'Added',formatDate(d.added_at||d.created_at));

    // Quantity bar (full width)
    html+=`<div class="sd-field full">
        <div class="sd-field-label">${TH?'ปริมาณคงเหลือ':'Remaining Quantity'}</div>
        <div style="font-size:22px;font-weight:900;color:${col};line-height:1">${remQ.toLocaleString()} <span style="font-size:12px;font-weight:600;color:var(--c3)">${esc(d.unit||'')}${pkgS>0?' / '+pkgS.toLocaleString()+' '+esc(d.unit||''):''}</span></div>
        <div class="sd-qty-bar"><div class="sd-qty-fill" style="width:${pct}%;background:${col}"></div></div>
        <div class="sd-qty-labels"><span>0</span><span>${pkgS>0?pkgS.toLocaleString():''}</span></div>
    </div>`;

    // Chemical properties (if linked)
    if(d.molecular_formula||d.signal_word||d.physical_state){
        html+=`<div class="sd-field full" style="background:linear-gradient(135deg,#faf5ff,#f3e8ff);border-color:#e9d5ff">
            <div class="sd-field-label" style="color:#7c3aed">${TH?'คุณสมบัติสารเคมี':'Chemical Properties'}</div>
            <div style="font-size:12px;color:var(--c1);line-height:1.6">`;
        if(d.molecular_formula) html+=`<div><strong>${TH?'สูตร':'Formula'}:</strong> ${esc(d.molecular_formula)}</div>`;
        if(d.physical_state) html+=`<div><strong>${TH?'สถานะทางกายภาพ':'State'}:</strong> ${esc(d.physical_state)}</div>`;
        if(d.signal_word) html+=`<div><strong>${TH?'คำสัญญาณ':'Signal'}:</strong> <span style="color:${d.signal_word==='Danger'?'#dc2626':'#d97706'};font-weight:700">${esc(d.signal_word)}</span></div>`;
        if(d.sds_url) html+=`<div><a href="${esc(d.sds_url)}" target="_blank" style="color:#2563eb;text-decoration:underline;font-size:11px"><i class="fas fa-file-pdf" style="margin-right:3px"></i>SDS</a></div>`;
        html+=`</div></div>`;
    }
    html+=`</div>`; // close sd-grid

    // Action buttons
    if(remQ>0){
        html+=`<div class="sd-actions">
            <div class="sd-act use" onclick="showSdForm('use')">
                <div class="sd-act-icon"><i class="fas fa-eye-dropper"></i></div>
                <div class="sd-act-label">${TH?'บันทึกการใช้':'Record Use'}</div>
            </div>
            <div class="sd-act transfer" onclick="showSdForm('transfer')">
                <div class="sd-act-icon"><i class="fas fa-people-arrows"></i></div>
                <div class="sd-act-label">${TH?'โอนสาร':'Transfer'}</div>
            </div>
            <div class="sd-act dispose" onclick="showSdForm('dispose')">
                <div class="sd-act-icon"><i class="fas fa-trash-alt"></i></div>
                <div class="sd-act-label">${TH?'กำจัด':'Dispose'}</div>
            </div>
        </div>`;
    } else {
        html+=`<div class="sd-actions">
            <div class="sd-act dispose" onclick="showSdForm('dispose')" style="grid-column:span 3">
                <div class="sd-act-icon"><i class="fas fa-trash-alt"></i></div>
                <div class="sd-act-label">${TH?'กำจัดขวดเปล่า':'Dispose Empty'}</div>
            </div>
        </div>`;
    }

    // Action forms (hidden by default)
    html+=`<div class="sd-form" id="sdFormUse">
        <h4><i class="fas fa-eye-dropper" style="color:#2563eb"></i> ${TH?'บันทึกการใช้สาร':'Record Usage'}</h4>
        <div class="sd-form-row"><label>${TH?'ปริมาณที่ใช้':'Quantity Used'} (${esc(d.unit||'mL')})</label>
            <input type="number" id="sdUseQty" min="0.01" max="${remQ}" step="0.01" placeholder="${TH?'ระบุปริมาณ':'Enter amount'}"></div>
        <div class="sd-form-row"><label>${TH?'วัตถุประสงค์':'Purpose'} (${TH?'ไม่บังคับ':'optional'})</label>
            <input type="text" id="sdUsePurpose" placeholder="${TH?'เช่น ทดสอบตัวอย่าง':'e.g. Sample testing'}"></div>
        <div class="sd-form-btns">
            <button class="sd-btn-cancel" onclick="hideSdForms()">${TH?'ยกเลิก':'Cancel'}</button>
            <button class="sd-btn-submit use" id="sdUseSend" onclick="submitSdUse()">${TH?'บันทึก':'Submit'}</button>
        </div>
    </div>`;

    html+=`<div class="sd-form" id="sdFormTransfer">
        <h4><i class="fas fa-people-arrows" style="color:#7c3aed"></i> ${TH?'โอนสารเคมี':'Transfer Chemical'}</h4>
        <div class="sd-form-row"><label>${TH?'โอนให้':'Transfer To'}</label>
            <select id="sdTransferTo"><option value="">${TH?'-- เลือกผู้รับ --':'-- Select recipient --'}</option></select></div>
        <div class="sd-form-row"><label>${TH?'ปริมาณ':'Quantity'} (${esc(d.unit||'mL')}) — ${TH?'ว่างเปล่า = ทั้งหมด':'empty = all'}</label>
            <input type="number" id="sdTransferQty" min="0.01" max="${remQ}" step="0.01" placeholder="${TH?'ทั้งหมด':'All'}"></div>
        <div class="sd-form-row"><label>${TH?'เหตุผล':'Reason'} (${TH?'ไม่บังคับ':'optional'})</label>
            <input type="text" id="sdTransferPurpose" placeholder=""></div>
        <div class="sd-form-btns">
            <button class="sd-btn-cancel" onclick="hideSdForms()">${TH?'ยกเลิก':'Cancel'}</button>
            <button class="sd-btn-submit transfer" id="sdTransferSend" onclick="submitSdTransfer()">${TH?'โอน':'Transfer'}</button>
        </div>
    </div>`;

    html+=`<div class="sd-form" id="sdFormDispose">
        <h4><i class="fas fa-trash-alt" style="color:#dc2626"></i> ${TH?'กำจัดสารเคมี':'Dispose Chemical'}</h4>
        <div class="sd-form-row"><label>${TH?'เหตุผลในการกำจัด':'Disposal Reason'}</label>
            <select id="sdDisposeReason">
                <option value="expired">${TH?'หมดอายุ':'Expired'}</option>
                <option value="contaminated">${TH?'ปนเปื้อน':'Contaminated'}</option>
                <option value="no_longer_needed">${TH?'ไม่ต้องการแล้ว':'No longer needed'}</option>
                <option value="damaged">${TH?'เสียหาย':'Damaged'}</option>
                <option value="clear_store">${TH?'Clear Store':'Clear Store'}</option>
                <option value="too_small_to_keep">${TH?'ปริมาณน้อยไม่คุ้มเก็บ':'Quantity too small to keep'}</option>
                <option value="other">${TH?'อื่นๆ':'Other'}</option>
            </select></div>
        <div class="sd-form-row"><label>${TH?'วิธีการกำจัด':'Disposal Method'}</label>
            <select id="sdDisposeMethod">
                <option value="waste_collection">${TH?'ส่งศูนย์รวบรวมของเสีย':'Waste collection center'}</option>
                <option value="neutralization">${TH?'ทำให้เป็นกลาง':'Neutralization'}</option>
                <option value="incineration">${TH?'เผาทำลาย':'Incineration'}</option>
                <option value="other">${TH?'อื่นๆ':'Other'}</option>
            </select></div>
        <div class="sd-form-btns">
            <button class="sd-btn-cancel" onclick="hideSdForms()">${TH?'ยกเลิก':'Cancel'}</button>
            <button class="sd-btn-submit dispose" id="sdDisposeSend" onclick="submitSdDispose()">${TH?'ยืนยันกำจัด':'Confirm Dispose'}</button>
        </div>
    </div>`;

    _('sdBody').innerHTML=html;
}

function sdField(label,val){
    return`<div class="sd-field"><div class="sd-field-label">${label}</div><div class="sd-field-value">${val}</div></div>`;
}

function showSdForm(type){
    hideSdForms();
    const f=_('sdForm'+type.charAt(0).toUpperCase()+type.slice(1));
    if(f)f.classList.add('show');
    if(type==='transfer') loadTransferUsers();
    setTimeout(()=>{if(f)f.scrollIntoView({behavior:'smooth',block:'nearest'})},100);
}
function hideSdForms(){
    document.querySelectorAll('.sd-form').forEach(f=>f.classList.remove('show'));
}

async function loadTransferUsers(){
    const sel=_('sdTransferTo');
    if(sel.options.length>1) return; // already loaded
    try{
        const r=await apiFetch('/v1/api/stock.php?action=owners');
        if(r.success&&r.data){
            const curOwner=_sdCurrent?parseInt(_sdCurrent.owner_user_id):0;
            r.data.forEach(u=>{
                if(parseInt(u.owner_user_id)===curOwner) return;
                const o=document.createElement('option');
                o.value=u.owner_user_id;
                o.textContent=(u.owner_name||u.username||'ID:'+u.owner_user_id)+(u.department?' ('+u.department+')':'');
                sel.appendChild(o);
            });
        }
    }catch(e){console.error('Load users:',e)}
}

async function submitSdUse(){
    if(!_sdCurrent)return;
    const qty=parseFloat(_('sdUseQty').value);
    const purpose=_('sdUsePurpose').value.trim();
    if(!qty||qty<=0){sdToast(TH?'กรุณาระบุปริมาณ':'Enter quantity','error');return}
    const btn=_('sdUseSend');btn.disabled=true;btn.textContent=TH?'กำลังบันทึก...':'Submitting...';
    try{
        const r=await apiFetch('/v1/api/borrow.php?action=use',{method:'POST',body:JSON.stringify({
            source_type:'stock', source_id:_sdCurrent.id, quantity:qty, purpose:purpose, unit:_sdCurrent.unit||'mL'
        })});
        if(!r.success)throw new Error(r.error);
        sdToast(TH?'✅ บันทึกการใช้สำเร็จ — '+r.data.txn_number:'✅ Usage recorded — '+r.data.txn_number,'success');
        closeSdModal();
        loadDashboard(); // refresh data
    }catch(e){
        sdToast(e.message,'error');
        btn.disabled=false;btn.textContent=TH?'บันทึก':'Submit';
    }
}

async function submitSdTransfer(){
    if(!_sdCurrent)return;
    const toUser=parseInt(_('sdTransferTo').value);
    if(!toUser){sdToast(TH?'กรุณาเลือกผู้รับ':'Select recipient','error');return}
    const qty=parseFloat(_('sdTransferQty').value)||0;
    const purpose=_('sdTransferPurpose').value.trim();
    const btn=_('sdTransferSend');btn.disabled=true;btn.textContent=TH?'กำลังโอน...':'Transferring...';
    try{
        const r=await apiFetch('/v1/api/borrow.php?action=transfer',{method:'POST',body:JSON.stringify({
            source_type:'stock', source_id:_sdCurrent.id, to_user_id:toUser,
            quantity:qty||parseFloat(_sdCurrent.remaining_qty), purpose:purpose, unit:_sdCurrent.unit||'mL'
        })});
        if(!r.success)throw new Error(r.error);
        const st=r.data.status==='pending'?(TH?'(รออนุมัติ)':'(pending)'):(TH?'(สำเร็จ)':'(completed)');
        sdToast(`✅ ${TH?'โอนสำเร็จ':'Transferred'} — ${r.data.txn_number} ${st}`,'success');
        closeSdModal();
        loadDashboard();
    }catch(e){
        sdToast(e.message,'error');
        btn.disabled=false;btn.textContent=TH?'โอน':'Transfer';
    }
}

async function submitSdDispose(){
    if(!_sdCurrent)return;
    if(!confirm(TH?'ยืนยันการกำจัดสารเคมีนี้? การดำเนินการนี้ไม่สามารถย้อนกลับได้':'Confirm disposal? This action cannot be undone.'))return;
    const reason=_('sdDisposeReason').value;
    const method=_('sdDisposeMethod').value;
    const btn=_('sdDisposeSend');btn.disabled=true;btn.textContent=TH?'กำลังกำจัด...':'Disposing...';
    try{
        const r=await apiFetch('/v1/api/borrow.php?action=dispose',{method:'POST',body:JSON.stringify({
            source_type:'stock', source_id:_sdCurrent.id, disposal_reason:reason, disposal_method:method
        })});
        if(!r.success)throw new Error(r.error);
        sdToast(`✅ ${TH?'กำจัดสำเร็จ':'Disposed'} — ${r.data.txn_number}`,'success');
        closeSdModal();
        loadDashboard();
    }catch(e){
        sdToast(e.message,'error');
        btn.disabled=false;btn.textContent=TH?'ยืนยันกำจัด':'Confirm Dispose';
    }
}

function sdToast(msg,type){
    const el=_('sdToast');
    el.className='sd-toast '+type;
    el.innerHTML=`<i class="fas ${type==='success'?'fa-check-circle':'fa-exclamation-circle'}"></i>${esc(msg)}`;
    el.classList.add('show');
    setTimeout(()=>el.classList.remove('show'),4000);
}

/* ═══════════════════════════════════════
   LAB DETAIL MODAL
   ═══════════════════════════════════════ */
async function openLabDetail(dept){
    const ov=_('labDetailOverlay');
    _('ldTitle').textContent=dept;
    _('ldSubtitle').textContent=TH?'กำลังโหลด...':'Loading...';
    _('ldBody').innerHTML='<div class="ci-loading"><div class="ci-spinner"></div></div>';
    ov.classList.add('show');
    try{
        const r=await apiFetch('/v1/api/dashboard.php?action=lab_detail&dept='+encodeURIComponent(dept));
        if(!r.success)throw new Error(r.error);
        renderLabDetail(r.data);
    }catch(e){
        _('ldBody').innerHTML=`<div class="ld-empty"><i class="fas fa-exclamation-triangle"></i><p>${esc(e.message)}</p></div>`;
    }
}
function closeLabDetail(){
    _('labDetailOverlay').classList.remove('show');
}
document.addEventListener('keydown',e=>{
    if(e.key==='Escape'){
        if(_('sdOverlay').classList.contains('show')){closeSdModal();e.stopPropagation();return}
        if(_('labDetailOverlay').classList.contains('show')){closeLabDetail();e.stopPropagation()}
    }
});

function renderLabDetail(d){
    const s=d.summary||{},mem=d.members||[],chems=d.chemicals||[],txns=d.transactions||[],ovd=d.overdue||[];
    _('ldTitle').textContent=d.department||'';
    _('ldSubtitle').innerHTML=`<i class="fas fa-users" style="margin-right:4px"></i>${num(s.user_count||0)} ${TH?'สมาชิก':'members'} &middot; <i class="fas fa-box" style="margin-right:4px"></i>${num(s.total_bottles||0)} ${TH?'ขวด':'bottles'}`;

    const avgPct=parseFloat(s.avg_remaining_pct)||0;
    const avgCol=avgPct<=20?'#dc2626':avgPct<=50?'#f59e0b':'#059669';
    let html='';

    // KPI
    html+=`<div class="ld-kpi">
        <div class="ld-kpi-item" style="background:#eff6ff;color:#1d4ed8"><span class="kv">${num(s.active_bottles||0)}</span><span class="kl">${TH?'ขวดใช้งาน':'Active'}</span></div>
        <div class="ld-kpi-item" style="background:#fffbeb;color:#d97706"><span class="kv">${num(s.low_bottles||0)}</span><span class="kl">${TH?'สต็อกต่ำ':'Low Stock'}</span></div>
        <div class="ld-kpi-item" style="background:#fef2f2;color:#dc2626"><span class="kv">${num(s.expired_bottles||0)}</span><span class="kl">${TH?'หมดอายุ':'Expired'}</span></div>
        <div class="ld-kpi-item" style="background:#f0fdf4;color:${avgCol}"><span class="kv">${avgPct}%</span><span class="kl">${TH?'เหลือเฉลี่ย':'Avg Remaining'}</span></div>
    </div>`;

    // Tabs
    const tabs=[
        {id:'ldMembers',icon:'fa-users',label:TH?'สมาชิก':'Members',cnt:mem.length},
        {id:'ldChems',icon:'fa-flask',label:TH?'สารเคมี':'Chemicals',cnt:chems.length},
        {id:'ldTxns',icon:'fa-exchange-alt',label:TH?'ธุรกรรม':'Transactions',cnt:txns.length}
    ];
    if(ovd.length) tabs.push({id:'ldOverdue',icon:'fa-exclamation-triangle',label:TH?'เกินกำหนด':'Overdue',cnt:ovd.length});

    html+=`<div class="ld-tabs">`;
    tabs.forEach((tb,i)=>{
        html+=`<div class="ld-tab${i===0?' active':''}" data-tab="${tb.id}" onclick="switchLdTab('${tb.id}')"><i class="fas ${tb.icon}"></i>${tb.label}<span class="ld-tab-cnt">${tb.cnt}</span></div>`;
    });
    html+=`</div>`;

    // ── Tab: Members ──
    html+=`<div class="ld-tab-panel active" id="ldMembers">`;
    if(mem.length){
        html+=`<table class="ld-members"><thead><tr><th>${TH?'ชื่อ':'Name'}</th><th>${TH?'ตำแหน่ง':'Position'}</th><th>${TH?'ขวด':'Bottles'}</th><th>${TH?'สต็อกต่ำ':'Low'}</th><th>${TH?'เฉลี่ย %':'Avg %'}</th></tr></thead><tbody>`;
        html+=mem.map(m=>{
            const ini=((m.first_name||'')[0]+(m.last_name||'')[0]).toUpperCase();
            const low=parseInt(m.low_count)||0;
            const ap=parseFloat(m.avg_pct);
            const apc=isNaN(ap)?'var(--c3)':ap<=20?'#dc2626':ap<=50?'#d97706':'#059669';
            return`<tr>
                <td><span class="ld-m-avatar">${ini}</span><span class="ld-m-name">${esc(m.first_name)} ${esc(m.last_name)}</span></td>
                <td style="font-size:11px;color:var(--c3)">${esc(m.position||'-')}</td>
                <td><strong style="color:#1d4ed8">${num(m.bottle_count)}</strong></td>
                <td>${low>0?`<span style="color:#dc2626;font-weight:700">${low}</span>`:`<span style="color:#059669">-</span>`}</td>
                <td><span style="color:${apc};font-weight:700">${isNaN(ap)?'-':ap+'%'}</span></td>
            </tr>`}).join('');
        html+=`</tbody></table>`;
    } else { html+=`<div class="ld-empty"><i class="fas fa-users"></i><p>${TH?'ไม่พบสมาชิก':'No members found'}</p></div>` }
    html+=`</div>`;

    // ── Tab: Chemicals ──
    html+=`<div class="ld-tab-panel" id="ldChems">`;
    if(chems.length){
        html+=chems.map(c=>{
            const pct=parseFloat(c.avg_pct)||0;
            const col=pct<=20?'#ef4444':pct<=50?'#f59e0b':'#10b981';
            const r=14,circ=2*Math.PI*r,off=circ-(pct/100)*circ;
            const rem=parseFloat(c.total_remaining)||0;
            const cap=parseFloat(c.total_capacity)||0;
            return`<div class="ld-chem-row">
                <div class="ld-chem-gauge">
                    <svg viewBox="0 0 32 32"><circle cx="16" cy="16" r="${r}" class="trk"/><circle cx="16" cy="16" r="${r}" class="fl" style="stroke:${col};stroke-dasharray:${circ.toFixed(1)};stroke-dashoffset:${off.toFixed(1)}"/></svg>
                    <div class="ld-chem-pct" style="color:${col}">${Math.round(pct)}</div>
                </div>
                <div class="ld-chem-info">
                    <div class="ld-chem-name">${esc(c.name)}</div>
                    <div class="ld-chem-meta">
                        ${c.cas_number?`<span><i class="fas fa-hashtag" style="font-size:8px;opacity:.5"></i> ${esc(c.cas_number)}</span>`:''}
                        ${c.molecular_formula?`<span>${esc(c.molecular_formula)}</span>`:''}
                        <span><i class="fas fa-box" style="font-size:8px;opacity:.5"></i> ${num(c.bottle_count)} ${TH?'ขวด':'bottles'}</span>
                    </div>
                </div>
                <div class="ld-chem-right">
                    <div class="ld-chem-qty">${num(Math.round(rem*100)/100)}</div>
                    <div class="ld-chem-unit">${cap>0?'/ '+num(Math.round(cap))+' ':'' }${esc(c.unit||'')}</div>
                </div>
            </div>`}).join('');
    } else { html+=`<div class="ld-empty"><i class="fas fa-flask"></i><p>${TH?'ไม่พบสารเคมี':'No chemicals found'}</p></div>` }
    html+=`</div>`;

    // ── Tab: Transactions ──
    const TXN_IC={borrow:'fa-hand-holding-medical',return:'fa-undo-alt',transfer:'fa-people-arrows',dispose:'fa-trash-alt',adjust:'fa-sliders-h',receive:'fa-arrow-down',use:'fa-eye-dropper'};
    const TXN_LB={borrow:TH?'ยืม':'Borrow',return:TH?'คืน':'Return',transfer:TH?'โอน':'Transfer',dispose:TH?'กำจัด':'Dispose',adjust:TH?'ปรับ':'Adjust',receive:TH?'รับ':'Receive',use:TH?'ใช้':'Use'};
    html+=`<div class="ld-tab-panel" id="ldTxns">`;
    if(txns.length){
        html+=txns.map(tx=>{
            const tp=tx.txn_type||'use';
            const who=tx.initiated_first?(esc(tx.initiated_first)+' '+esc(tx.initiated_last)):'-';
            let desc=esc(tx.chemical_name||'');
            if(tx.quantity) desc+=` · ${tx.quantity} ${tx.unit||''}`;
            if(tx.from_first&&tx.to_first) desc+=` (${esc(tx.from_first)} → ${esc(tx.to_first)})`;
            if(tx.purpose) desc+=` · ${esc(tx.purpose)}`;
            return`<div class="ld-tl-item">
                <div class="ld-tl-icon ${tp}"><i class="fas ${TXN_IC[tp]||'fa-circle'}"></i></div>
                <div class="ld-tl-info">
                    <div class="ld-tl-title">${TXN_LB[tp]||tp} — ${who}</div>
                    <div class="ld-tl-desc">${desc}</div>
                </div>
                <div class="ld-tl-time">${ldFmtDT(tx.created_at)}</div>
            </div>`}).join('');
    } else { html+=`<div class="ld-empty"><i class="fas fa-exchange-alt"></i><p>${TH?'ยังไม่มีธุรกรรม':'No transactions yet'}</p></div>` }
    html+=`</div>`;

    // ── Tab: Overdue ──
    if(ovd.length){
        html+=`<div class="ld-tab-panel" id="ldOverdue">`;
        html+=ovd.map(o=>{
            const days=parseInt(o.days_overdue)||0;
            return`<div class="ld-ov-item">
                <div class="ld-ov-days"><span class="dv">${days}</span><span class="dl">${TH?'วัน':'days'}</span></div>
                <div style="flex:1;min-width:0">
                    <div style="font-size:12px;font-weight:700;color:#991b1b">${esc(o.chemical_name)}</div>
                    <div style="font-size:10px;color:var(--c3);margin-top:2px">${esc(o.first_name)} ${esc(o.last_name)} · ${num(o.requested_quantity)} ${o.quantity_unit||''}</div>
                </div>
                <div style="text-align:right;flex-shrink:0"><div style="font-size:10px;color:#dc2626;font-weight:600">${TH?'ครบกำหนด':'Due'}: ${ldFmtDT(o.expected_return_date)}</div></div>
            </div>`}).join('');
        html+=`</div>`;
    }

    _('ldBody').innerHTML=html;
}

function switchLdTab(tabId){
    document.querySelectorAll('.ld-tab').forEach(t=>t.classList.toggle('active',t.dataset.tab===tabId));
    document.querySelectorAll('.ld-tab-panel').forEach(p=>p.classList.toggle('active',p.id===tabId));
}

function ldFmtDT(d){
    if(!d)return'-';
    try{const dt=new Date(d);if(isNaN(dt))return esc(d);return dt.toLocaleDateString(TH?'th-TH':'en-US',{day:'numeric',month:'short',year:'numeric'})}catch{return esc(d)}
}

loadDashboard();
</script>
</body></html>
