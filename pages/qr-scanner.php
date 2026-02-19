<?php
require_once __DIR__ . '/../includes/layout.php';
$user = Auth::getCurrentUser();
if (!$user) { header('Location: /v1/pages/login.php'); exit; }
$userId    = $user['id'];
$roleLevel = (int)($user['role_level'] ?? 2);
$isAdmin   = $roleLevel >= 5;
$isManager = $roleLevel >= 3;
$lang      = I18n::getCurrentLang();
$TH        = $lang === 'th';
Layout::head($TH ? 'เครื่องสแกน Barcode' : 'Barcode Scanner', [], ['https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js']);
?>
<style>
:root{--scan-accent:#10b981;--scan-accent-glow:rgba(16,185,129,.3)}

/* ===== PAGE HERO SCANNER ===== */
.scan-page-hero{display:grid;grid-template-columns:1fr 380px;gap:24px;margin-bottom:24px}
@media(max-width:960px){.scan-page-hero{grid-template-columns:1fr;}}

/* Scanner card */
.scanner-card{background:var(--card);border-radius:16px;border:1px solid var(--border);overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.06)}
.scanner-card-hdr{display:flex;align-items:center;gap:12px;padding:16px 20px;border-bottom:1px solid var(--border);background:linear-gradient(135deg,#0f172a,#1e293b)}
.scanner-card-hdr h3{margin:0;font-size:16px;font-weight:700;color:#fff}
.scanner-card-hdr .scan-hdr-icon{width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#10b981,#34d399);display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px}
.scanner-card-hdr .scan-hdr-sub{margin:2px 0 0;font-size:11px;color:rgba(255,255,255,.6);font-weight:400}
.scanner-card-body{padding:20px}

/* Camera */
.scan-camera-wrap{position:relative;border-radius:12px;overflow:hidden;background:#111;min-height:200px;margin-bottom:16px}
.scan-camera-wrap video{width:100%;display:block;border-radius:12px}
.scan-camera-placeholder{display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:200px;color:#666;gap:8px}
.scan-camera-placeholder i{font-size:40px;color:#444}
.scan-camera-placeholder p{font-size:13px;margin:0}
.scan-camera-overlay{position:absolute;inset:0;pointer-events:none;display:flex;align-items:center;justify-content:center}
.scan-corner{position:absolute;width:28px;height:28px;border:3px solid var(--scan-accent)}
.scan-corner.tl{top:20px;left:20px;border-right:none;border-bottom:none;border-radius:6px 0 0 0}
.scan-corner.tr{top:20px;right:20px;border-left:none;border-bottom:none;border-radius:0 6px 0 0}
.scan-corner.bl{bottom:20px;left:20px;border-right:none;border-top:none;border-radius:0 0 0 6px}
.scan-corner.br{bottom:20px;right:20px;border-left:none;border-top:none;border-radius:0 0 6px 0}
.scan-line{position:absolute;width:calc(100% - 56px);height:2px;background:linear-gradient(90deg,transparent,var(--scan-accent),transparent);animation:scanLine 2s infinite ease-in-out}
@keyframes scanLine{0%,100%{top:30px;opacity:0}50%{top:calc(100% - 30px);opacity:1}}
.scan-camera-error{text-align:center;padding:40px 20px;color:#888}
.scan-camera-error i{font-size:36px;margin-bottom:10px;display:block;color:#555}
.scan-camera-error p{font-size:13px;margin:0}
.scan-camera-actions{display:flex;gap:8px;justify-content:center;margin-bottom:16px}
.scan-cam-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:10px;border:1px solid var(--border);background:var(--card);color:var(--c2);font-size:12px;font-weight:600;cursor:pointer;transition:all .15s}
.scan-cam-btn:hover{border-color:var(--scan-accent);color:var(--scan-accent)}
.scan-cam-btn.active{background:var(--scan-accent);color:#fff;border-color:var(--scan-accent)}
.scan-cam-btn i{font-size:14px}

/* Manual input */
.scan-manual-section{margin-bottom:0}
.scan-manual-label{font-size:12px;font-weight:600;color:var(--c3);margin-bottom:6px;display:flex;align-items:center;gap:6px}
.scan-manual-label i{font-size:11px}
.scan-manual-row{display:flex;gap:8px}
.scan-barcode-input{flex:1;font-family:'Courier New',monospace;font-size:15px;font-weight:600;letter-spacing:.5px;border-radius:10px!important}
.scan-go-btn{width:44px;height:44px;border-radius:10px!important;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}

/* Processing & result area */
.scan-processing{text-align:center;padding:30px}
.scan-processing p{font-size:13px;color:var(--c3);margin-top:8px}

/* ===== RIGHT PANEL: RESULT + ACTIONS ===== */
.result-panel{display:flex;flex-direction:column;gap:16px}

/* Scan result card */
.scan-result-card{background:var(--card);border:1px solid var(--border);border-radius:14px;overflow:hidden;animation:slideUp .3s ease}
@keyframes slideUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:none}}
.scan-result-hdr{display:flex;align-items:center;gap:12px;padding:14px 16px;border-bottom:1px solid #f0f0f0}
.scan-result-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0}
.scan-result-icon.owner{background:#ede9fe;color:#7c3aed}
.scan-result-icon.other{background:#fff7ed;color:#ea580c}
.scan-result-icon.returnable{background:#ecfdf5;color:#059669}
.scan-result-icon.error{background:#fef2f2;color:#dc2626}
.scan-result-body{padding:14px 16px}
.scan-result-name{font-size:16px;font-weight:700;color:var(--c1);margin-bottom:2px;line-height:1.3}
.scan-result-cas{font-size:12px;color:var(--c3);font-family:'Courier New',monospace}
.scan-result-chips{display:flex;flex-wrap:wrap;gap:6px;margin-top:10px}
.scan-result-chip{font-size:11px;padding:5px 10px;border-radius:20px;background:#f8fafc;border:1px solid var(--border);color:var(--c2);display:flex;align-items:center;gap:4px}
.scan-result-chip i{font-size:10px;color:var(--c3)}
.scan-result-badge{display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;padding:6px 14px;border-radius:20px;margin-top:12px}
.scan-result-badge.owner-badge{background:#f5f3ff;color:#6d28d9;border:1px solid #ddd6fe}
.scan-result-badge.other-badge{background:#fff7ed;color:#c2410c;border:1px solid #fed7aa}
.scan-result-badge.return-badge{background:#ecfdf5;color:#047857;border:1px solid #a7f3d0}

/* Action buttons */
.scan-result-actions{padding:14px 16px;display:grid;gap:8px;border-top:1px solid #f0f0f0}
.scan-action-btn{display:flex;align-items:center;justify-content:center;gap:8px;padding:12px 16px;border:none;border-radius:10px;font-size:14px;font-weight:600;color:#fff;cursor:pointer;transition:all .2s;position:relative;overflow:hidden}
.scan-action-btn::before{content:'';position:absolute;inset:0;background:linear-gradient(rgba(255,255,255,.1),transparent);pointer-events:none}
.scan-action-btn:hover{transform:translateY(-1px);box-shadow:0 4px 14px rgba(0,0,0,.2)}
.scan-action-btn.act-use{background:linear-gradient(135deg,#6d28d9,#a78bfa)}
.scan-action-btn.act-borrow{background:linear-gradient(135deg,#e65100,#ff8f00)}
.scan-action-btn.act-return{background:linear-gradient(135deg,#059669,#34d399)}
.scan-action-btn.act-transfer{background:linear-gradient(135deg,#1565c0,#42a5f5)}
.scan-action-btn.act-dispose{background:linear-gradient(135deg,#b71c1c,#e53935)}
.scan-action-sec{display:flex;gap:8px}
.scan-action-sec .scan-action-btn{flex:1;font-size:12px;padding:10px 8px;opacity:.85}
.scan-action-sec .scan-action-btn:hover{opacity:1}

/* Link buttons row */
.scan-result-links{padding:0 16px 14px;display:flex;gap:8px}
.scan-link-btn{flex:1;display:flex;align-items:center;justify-content:center;gap:6px;padding:8px;border-radius:8px;border:1px solid var(--border);background:#f8fafc;color:var(--c2);font-size:12px;font-weight:600;cursor:pointer;text-decoration:none;transition:all .15s}
.scan-link-btn:hover{border-color:var(--accent);color:var(--accent);background:#f0fdf4}
.scan-link-btn i{font-size:12px}

/* Empty result panel */
.result-empty{background:var(--card);border:1px solid var(--border);border-radius:14px;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:40px 20px;text-align:center;min-height:200px}
.result-empty-icon{width:64px;height:64px;border-radius:16px;background:linear-gradient(135deg,#f0fdf4,#dcfce7);display:flex;align-items:center;justify-content:center;font-size:28px;color:var(--scan-accent);margin-bottom:14px}
.result-empty h4{margin:0 0 6px;font-size:15px;font-weight:700;color:var(--c1)}
.result-empty p{margin:0;font-size:12px;color:var(--c3);line-height:1.6;max-width:240px}

/* ===== BOTTOM SECTION ===== */
.scan-bottom-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px}
@media(max-width:768px){.scan-bottom-grid{grid-template-columns:1fr}}

/* My active borrows card */
.active-card{background:var(--card);border-radius:14px;border:1px solid var(--border);overflow:hidden}
.active-card-hdr{display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--border);background:linear-gradient(135deg,#faf5ff,#f3e8ff)}
.active-card-hdr i{color:#7c3aed;font-size:16px}
.active-card-hdr h4{margin:0;font-size:14px;font-weight:700;color:var(--c1);flex:1}
.active-card-hdr .active-count{background:#7c3aed;color:#fff;font-size:11px;font-weight:700;padding:3px 9px;border-radius:20px}
.active-card-body{padding:0;max-height:320px;overflow-y:auto}
.active-item{display:flex;align-items:center;gap:12px;padding:12px 18px;border-bottom:1px solid #f5f5f5;cursor:pointer;transition:background .15s}
.active-item:last-child{border-bottom:none}
.active-item:hover{background:#faf5ff}
.active-item-icon{width:36px;height:36px;border-radius:10px;background:#f3e8ff;color:#7c3aed;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0}
.active-item-info{flex:1;min-width:0}
.active-item-name{font-size:13px;font-weight:600;color:var(--c1);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.active-item-meta{font-size:11px;color:var(--c3);margin-top:1px}
.active-item-qty{text-align:right}
.active-item-qty span{display:block;font-size:13px;font-weight:700;color:#7c3aed}
.active-item-qty small{font-size:10px;color:var(--c3)}
.active-empty{padding:30px 20px;text-align:center;color:var(--c3);font-size:13px}
.active-empty i{font-size:28px;color:#ddd;display:block;margin-bottom:8px}

/* Recent scans card */
.recent-card{background:var(--card);border-radius:14px;border:1px solid var(--border);overflow:hidden}
.recent-card-hdr{display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--border);background:linear-gradient(135deg,#f0f9ff,#e0f2fe)}
.recent-card-hdr i{color:#0284c7;font-size:16px}
.recent-card-hdr h4{margin:0;font-size:14px;font-weight:700;color:var(--c1);flex:1}
.recent-clear-btn{border:none;background:none;color:var(--c3);font-size:11px;cursor:pointer;font-weight:600;padding:2px 6px;border-radius:4px}
.recent-clear-btn:hover{color:#dc2626;background:#fef2f2}
.recent-card-body{padding:0;max-height:320px;overflow-y:auto}
.recent-item{display:flex;align-items:center;gap:10px;padding:10px 18px;border-bottom:1px solid #f8f8f8;cursor:pointer;transition:background .15s}
.recent-item:last-child{border-bottom:none}
.recent-item:hover{background:#f0f9ff}
.recent-item-code{flex:1;font-family:'Courier New',monospace;font-size:13px;font-weight:600;color:var(--c1);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.recent-item-time{font-size:10px;color:var(--c3)}
.recent-item-icon{color:#0284c7;font-size:13px;flex-shrink:0}
.recent-empty{padding:30px 20px;text-align:center;color:var(--c3);font-size:13px}
.recent-empty i{font-size:28px;color:#ddd;display:block;margin-bottom:8px}

/* Quick stats */
.scan-stats-row{display:flex;gap:12px;margin-bottom:20px}
@media(max-width:768px){.scan-stats-row{flex-wrap:wrap}.scan-stats-row .scan-stat{flex:1 1 calc(50% - 6px)}}
.scan-stat{flex:1;display:flex;align-items:center;gap:12px;padding:14px 16px;background:var(--card);border:1px solid var(--border);border-radius:12px}
.scan-stat-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
.scan-stat-info p{margin:0;font-size:11px;color:var(--c3);font-weight:500}
.scan-stat-info h4{margin:0;font-size:18px;font-weight:800;color:var(--c1)}

/* Timeline modal */
.tl-modal-bg{position:fixed;inset:0;z-index:999;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;padding:16px;opacity:0;pointer-events:none;transition:opacity .2s}
.tl-modal-bg.show{opacity:1;pointer-events:auto}
.tl-modal{background:var(--card);border-radius:16px;width:100%;max-width:500px;max-height:85vh;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.2)}
.tl-modal-hdr{display:flex;align-items:center;gap:10px;padding:16px 20px;border-bottom:1px solid var(--border)}
.tl-modal-hdr h3{margin:0;flex:1;font-size:15px;font-weight:700}
.tl-modal-hdr .tl-close{width:28px;height:28px;border-radius:8px;border:none;background:#f1f5f9;color:var(--c3);font-size:14px;cursor:pointer;display:flex;align-items:center;justify-content:center}
.tl-modal-hdr .tl-close:hover{background:#fee2e2;color:#dc2626}
.tl-modal-body{flex:1;overflow-y:auto;padding:20px}
.tl-item{display:flex;gap:14px;position:relative;padding-bottom:18px}
.tl-item:last-child{padding-bottom:0}
.tl-item:not(:last-child)::after{content:'';position:absolute;left:17px;top:36px;bottom:0;width:2px;background:var(--border)}
.tl-dot{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;position:relative;z-index:1}
.tl-dot.borrow{background:#fff7ed;color:#ea580c}
.tl-dot.return{background:#ecfdf5;color:#059669}
.tl-dot.use{background:#f5f3ff;color:#7c3aed}
.tl-dot.transfer{background:#eff6ff;color:#2563eb}
.tl-dot.dispose{background:#fef2f2;color:#dc2626}
.tl-dot.default{background:#f1f5f9;color:#64748b}
.tl-content{flex:1;min-width:0}
.tl-content h5{margin:0 0 2px;font-size:13px;font-weight:700;color:var(--c1);text-transform:capitalize}
.tl-content p{margin:0;font-size:11px;color:var(--c3);line-height:1.5}
.tl-content .tl-date{font-size:10px;color:var(--c3);margin-top:4px}

/* Return modal */
.ret-modal-bg{position:fixed;inset:0;z-index:999;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;padding:16px;opacity:0;pointer-events:none;transition:opacity .2s}
.ret-modal-bg.show{opacity:1;pointer-events:auto}
.ret-modal{background:var(--card);border-radius:16px;width:100%;max-width:440px;box-shadow:0 20px 60px rgba(0,0,0,.2);overflow:hidden}
.ret-modal-hdr{padding:18px 20px;background:linear-gradient(135deg,#059669,#34d399);display:flex;align-items:center;gap:10px}
.ret-modal-hdr h3{margin:0;flex:1;font-size:15px;font-weight:700;color:#fff}
.ret-modal-hdr .ret-close{width:28px;height:28px;border-radius:8px;border:none;background:rgba(255,255,255,.2);color:#fff;font-size:14px;cursor:pointer;display:flex;align-items:center;justify-content:center}
.ret-modal-body{padding:20px}
.ret-info-card{background:#f0fdf4;border:1px solid #a7f3d0;border-radius:10px;padding:12px;margin-bottom:16px;display:flex;align-items:center;gap:10px}
.ret-info-icon{width:36px;height:36px;border-radius:8px;background:#dcfce7;display:flex;align-items:center;justify-content:center;color:#059669;font-size:14px;flex-shrink:0}
.ret-info-text h5{margin:0;font-size:13px;font-weight:700;color:var(--c1)}
.ret-info-text p{margin:2px 0 0;font-size:11px;color:var(--c3)}
.ret-form-group{margin-bottom:14px}
.ret-form-group label{display:block;font-size:12px;font-weight:600;color:var(--c2);margin-bottom:4px}
.ret-form-group input,.ret-form-group textarea{width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:8px;font-size:13px;background:var(--card);color:var(--c1);resize:vertical}
.ret-form-group textarea{min-height:70px}
.ret-form-actions{display:flex;gap:8px;margin-top:16px}
.ret-form-actions .ci-btn{flex:1}

/* Responsive tweaks */
@media(max-width:640px){
    .scan-page-hero{gap:16px}
    .scanner-card-body{padding:14px}
    .scan-result-hdr{flex-wrap:wrap}
    .scan-stats-row{gap:8px}
    .scan-stat{padding:10px 12px}
    .scan-stat-icon{width:34px;height:34px;font-size:14px}
    .scan-stat-info h4{font-size:16px}
}
#qrReader{width:100%!important;border:none!important}
#qrReader video{border-radius:8px}
#qrReader__dashboard_section_swaplink,.scan-region-highlight-svg,.code-outline-highlight{display:none!important}
</style>
<body>
<?php Layout::sidebar('qr-scanner'); Layout::beginContent(); ?>
<?php Layout::pageHeader($TH ? 'เครื่องสแกน Barcode' : 'Barcode Scanner', 'fas fa-qrcode'); ?>

<!-- Quick Stats -->
<div class="scan-stats-row" id="scanStats">
    <div class="scan-stat">
        <div class="scan-stat-icon" style="background:#ecfdf5;color:#059669"><i class="fas fa-flask"></i></div>
        <div class="scan-stat-info"><p><?php echo $TH?'คลังของฉัน':'My Stock'; ?></p><h4 id="statMyStock">–</h4></div>
    </div>
    <div class="scan-stat">
        <div class="scan-stat-icon" style="background:#fff7ed;color:#ea580c"><i class="fas fa-hand-holding-medical"></i></div>
        <div class="scan-stat-info"><p><?php echo $TH?'กำลังยืม':'Borrowing'; ?></p><h4 id="statBorrowing">–</h4></div>
    </div>
    <div class="scan-stat">
        <div class="scan-stat-icon" style="background:#f5f3ff;color:#7c3aed"><i class="fas fa-eye-dropper"></i></div>
        <div class="scan-stat-info"><p><?php echo $TH?'ใช้ไปเดือนนี้':'Used This Month'; ?></p><h4 id="statUsed">–</h4></div>
    </div>
    <div class="scan-stat">
        <div class="scan-stat-icon" style="background:#eff6ff;color:#2563eb"><i class="fas fa-exchange-alt"></i></div>
        <div class="scan-stat-info"><p><?php echo $TH?'ธุรกรรมเดือนนี้':'Txn This Month'; ?></p><h4 id="statTxn">–</h4></div>
    </div>
</div>

<!-- Main scanner + result area -->
<div class="scan-page-hero">
    <!-- LEFT: Scanner -->
    <div class="scanner-card">
        <div class="scanner-card-hdr">
            <div class="scan-hdr-icon"><i class="fas fa-qrcode"></i></div>
            <div>
                <h3><?php echo $TH?'สแกน QR / Barcode':'Scan QR / Barcode'; ?></h3>
                <p class="scan-hdr-sub"><?php echo $TH?'ส่องกล้องหรือพิมพ์รหัสเพื่อค้นหาสารเคมีและทำธุรกรรม':'Point camera or enter code to look up chemicals & transact'; ?></p>
            </div>
        </div>
        <div class="scanner-card-body">
            <!-- Camera -->
            <div class="scan-camera-wrap" id="cameraWrap">
                <div id="qrReader"></div>
                <div id="cameraOverlay" class="scan-camera-overlay" style="display:none">
                    <div class="scan-corner tl"></div><div class="scan-corner tr"></div>
                    <div class="scan-corner bl"></div><div class="scan-corner br"></div>
                    <div class="scan-line"></div>
                </div>
                <div id="cameraPlaceholder" class="scan-camera-placeholder">
                    <i class="fas fa-camera"></i>
                    <p><?php echo $TH?'กดปุ่มเพื่อเปิดกล้อง':'Click to open camera'; ?></p>
                </div>
                <div id="cameraError" style="display:none" class="scan-camera-error">
                    <i class="fas fa-video-slash"></i>
                    <p><?php echo $TH?'ไม่สามารถเปิดกล้องได้':'Could not access camera'; ?></p>
                </div>
            </div>

            <!-- Camera toggle -->
            <div class="scan-camera-actions">
                <button onclick="toggleCamera()" class="scan-cam-btn" id="camToggleBtn">
                    <i class="fas fa-camera"></i> <span id="camToggleLabel"><?php echo $TH?'เปิดกล้อง':'Open Camera'; ?></span>
                </button>
                <button onclick="switchCamera()" class="scan-cam-btn" id="camSwitchBtn" style="display:none" title="<?php echo $TH?'สลับกล้อง':'Switch Camera'; ?>">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>

            <!-- Manual input -->
            <div class="scan-manual-section">
                <div class="scan-manual-label"><i class="fas fa-keyboard"></i> <?php echo $TH?'หรือพิมพ์รหัส Barcode / QR':'Or type barcode / QR code'; ?></div>
                <div class="scan-manual-row">
                    <input type="text" id="barcodeInput" class="ci-input scan-barcode-input"
                           placeholder="<?php echo $TH?'พิมพ์รหัสขวด เช่น 320F6600000001':'Enter bottle code e.g. 320F6600000001'; ?>" autocomplete="off">
                    <button onclick="processBarcode()" class="ci-btn ci-btn-primary scan-go-btn" id="goBtn">
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT: Result panel -->
    <div class="result-panel" id="resultPanel">
        <!-- Processing -->
        <div id="scanProcessing" style="display:none" class="scan-processing">
            <div class="ci-spinner"></div>
            <p><?php echo $TH?'กำลังค้นหา...':'Looking up...'; ?></p>
        </div>

        <!-- Result placeholder -->
        <div id="resultEmpty" class="result-empty">
            <div class="result-empty-icon"><i class="fas fa-qrcode"></i></div>
            <h4><?php echo $TH?'พร้อมสแกน':'Ready to Scan'; ?></h4>
            <p><?php echo $TH?'สแกน QR หรือ Barcode บนขวดสารเคมีเพื่อดูข้อมูลและทำธุรกรรม':'Scan a QR or barcode on a chemical bottle to view info and transact'; ?></p>
        </div>

        <!-- Result card (filled by JS) -->
        <div id="scanResult" style="display:none"></div>
    </div>
</div>

<!-- Bottom: Active borrows + Recent scans -->
<div class="scan-bottom-grid">
    <!-- My active borrows -->
    <div class="active-card">
        <div class="active-card-hdr">
            <i class="fas fa-hand-holding-medical"></i>
            <h4><?php echo $TH?'สารที่ยืมอยู่':'My Active Borrows'; ?></h4>
            <span class="active-count" id="activeCount">0</span>
        </div>
        <div class="active-card-body" id="activeList">
            <div class="active-empty"><i class="fas fa-inbox"></i><?php echo $TH?'ไม่มีรายการยืม':'No active borrows'; ?></div>
        </div>
    </div>

    <!-- Recent scans -->
    <div class="recent-card">
        <div class="recent-card-hdr">
            <i class="fas fa-history"></i>
            <h4><?php echo $TH?'สแกนล่าสุด':'Recent Scans'; ?></h4>
            <button class="recent-clear-btn" onclick="clearRecent()" title="<?php echo $TH?'ล้างประวัติ':'Clear History'; ?>"><i class="fas fa-trash-alt"></i></button>
        </div>
        <div class="recent-card-body" id="recentList">
            <div class="recent-empty"><i class="fas fa-history"></i><?php echo $TH?'ยังไม่มีประวัติสแกน':'No scan history yet'; ?></div>
        </div>
    </div>
</div>

<!-- Timeline modal -->
<div class="tl-modal-bg" id="timelineModal">
    <div class="tl-modal">
        <div class="tl-modal-hdr">
            <i class="fas fa-history" style="color:var(--scan-accent)"></i>
            <h3><?php echo $TH?'Lifecycle Timeline':'Lifecycle Timeline'; ?></h3>
            <button class="tl-close" onclick="closeTimeline()">&times;</button>
        </div>
        <div class="tl-modal-body" id="timelineContent">
            <div class="scan-processing"><div class="ci-spinner"></div><p><?php echo $TH?'กำลังโหลด...':'Loading...'; ?></p></div>
        </div>
    </div>
</div>

<!-- Return modal -->
<div class="ret-modal-bg" id="returnModal">
    <div class="ret-modal">
        <div class="ret-modal-hdr">
            <i class="fas fa-undo" style="font-size:16px"></i>
            <h3><?php echo $TH?'คืนสารเคมี':'Return Chemical'; ?></h3>
            <button class="ret-close" onclick="closeReturnModal()">&times;</button>
        </div>
        <div class="ret-modal-body">
            <div class="ret-info-card">
                <div class="ret-info-icon"><i class="fas fa-flask"></i></div>
                <div class="ret-info-text">
                    <h5 id="retChemName">–</h5>
                    <p id="retBorrowInfo">–</p>
                </div>
            </div>
            <div class="ret-form-group">
                <label><?php echo $TH?'จำนวนที่คืน':'Return Quantity'; ?></label>
                <div style="display:flex;gap:8px;align-items:center">
                    <input type="number" id="retQty" min="0" step="any" style="flex:1">
                    <span id="retUnit" style="font-size:13px;font-weight:600;color:var(--c3)">–</span>
                </div>
            </div>
            <div class="ret-form-group">
                <label><?php echo $TH?'หมายเหตุ (ถ้ามี)':'Notes (optional)'; ?></label>
                <textarea id="retNotes" placeholder="<?php echo $TH?'เช่น คืนครบ, คืนบางส่วน, สภาพ...':'e.g. Full return, partial, condition...'; ?>"></textarea>
            </div>
            <div class="ret-form-actions">
                <button onclick="closeReturnModal()" class="ci-btn ci-btn-secondary"><?php echo $TH?'ยกเลิก':'Cancel'; ?></button>
                <button onclick="submitReturn()" class="ci-btn ci-btn-primary" id="retSubmitBtn"><i class="fas fa-undo"></i> <?php echo $TH?'ยืนยันคืน':'Confirm Return'; ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Report/Inquiry Modal -->
<div class="ret-modal-bg" id="reportModal">
    <div class="ret-modal" style="max-width:420px">
        <div class="ret-modal-hdr">
            <i class="fas fa-exclamation-circle" style="font-size:16px;color:#f59e0b"></i>
            <h3><?php echo $TH?'แจ้งเรื่องสารเคมี':'Report Chemical Item'; ?></h3>
            <button class="ret-close" onclick="closeReportModal()">&times;</button>
        </div>
        <div class="ret-modal-body">
            <div class="ret-form-group">
                <label><?php echo $TH?'ประเภทการแจ้ง':'Report Type'; ?></label>
                <select id="reportType" style="width:100%;padding:8px 12px;border:1px solid var(--border);border-radius:8px;font-size:13px">
                    <option value="inquiry"><?php echo $TH?'สอบถาม':'General Inquiry'; ?></option>
                    <option value="issue"><?php echo $TH?'แจ้งปัญหา':'Report Issue'; ?></option>
                    <option value="request"><?php echo $TH?'ขอให้ยืม/ขอเบิก':'Borrow/Request'; ?></option>
                </select>
            </div>
            <div class="ret-form-group">
                <label><?php echo $TH?'ข้อความ':'Message'; ?> *</label>
                <textarea id="reportMessage" placeholder="<?php echo $TH?'บรรยายปัญหา เช่น สารไม่เพียงพอ, หน้าผสม, ขอยืมสาร...':'Describe your concern, e.g., insufficient stock, damaged label, request to borrow...'; ?>" style="min-height:100px"></textarea>
            </div>
            <input type="hidden" id="reportBarcode">
            <input type="hidden" id="reportSourceId">
            <input type="hidden" id="reportSourceType">
            <div class="ret-form-actions">
                <button onclick="closeReportModal()" class="ci-btn ci-btn-secondary"><?php echo $TH?'ยกเลิก':'Cancel'; ?></button>
                <button onclick="submitReport()" class="ci-btn ci-btn-primary" style="background:#f59e0b"><i class="fas fa-paper-plane"></i> <?php echo $TH?'ส่งแจ้ง':'Send Report'; ?></button>
            </div>
        </div>
    </div>
</div>

<?php Layout::endContent(); ?>
<script>
const TH = <?php echo json_encode($TH); ?>;
const API = '/v1/api/borrow.php';
const IS_ADMIN = <?php echo json_encode($isAdmin); ?>;
const IS_MANAGER = <?php echo json_encode($isManager); ?>;
const UID = <?php echo (int)$userId; ?>;

let scanner = null;
let cameraActive = false;
let currentCameraIdx = 0;
let availableCameras = [];
let scannedItemData = null;
let returnTxnId = null;

// ========== INIT ==========
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('barcodeInput').addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); processBarcode(); }
    });
    loadStats();
    loadActiveBorrows();
    renderRecent();
    // Auto-start camera
    startCamera();
});

// ========== QUICK STATS ==========
async function loadStats() {
    try {
        const [dashData, myData] = await Promise.all([
            apiFetch(API + '?action=dashboard'),
            apiFetch('/v1/api/stock.php?action=stats')
        ]);
        if (dashData.success) {
            const s = dashData.data;
            document.getElementById('statBorrowing').textContent = num(s.my_active_borrows ?? 0);
            document.getElementById('statUsed').textContent = num(s.my_used_this_month ?? 0);
            document.getElementById('statTxn').textContent = num(s.total_this_month ?? 0);
        }
        if (myData.success) {
            document.getElementById('statMyStock').textContent = num(myData.data?.total_items ?? myData.data?.my_items ?? 0);
        }
    } catch(e) { /* silent */ }
}

// ========== ACTIVE BORROWS ==========
async function loadActiveBorrows() {
    try {
        const d = await apiFetch(API + '?action=my_active');
        if (!d.success) return;
        const items = d.data || [];
        document.getElementById('activeCount').textContent = items.length;
        const el = document.getElementById('activeList');
        if (!items.length) {
            el.innerHTML = '<div class="active-empty"><i class="fas fa-inbox"></i>' + (TH?'ไม่มีรายการยืม':'No active borrows') + '</div>';
            return;
        }
        el.innerHTML = items.map(it => {
            const bc = esc(it.barcode || it.bottle_code || '');
            return '<div class="active-item" onclick="lookupBarcode(\'' + bc + '\')">' +
                '<div class="active-item-icon"><i class="fas fa-flask"></i></div>' +
                '<div class="active-item-info">' +
                    '<div class="active-item-name">' + esc(it.chemical_name || it.item_name || '-') + '</div>' +
                    '<div class="active-item-meta"><i class="fas fa-calendar" style="font-size:9px"></i> ' + formatDate(it.created_at || it.borrow_date) + ' · ' + esc(it.txn_number || '') + '</div>' +
                '</div>' +
                '<div class="active-item-qty">' +
                    '<span>' + Number(it.quantity).toLocaleString() + '</span>' +
                    '<small>' + esc(it.unit || '') + '</small>' +
                '</div>' +
            '</div>';
        }).join('');
    } catch(e) { /* silent */ }
}

// ========== CAMERA ==========
async function toggleCamera() {
    if (cameraActive) { stopCamera(); return; }
    startCamera();
}

async function startCamera() {
    var placeholder = document.getElementById('cameraPlaceholder');
    var errorEl = document.getElementById('cameraError');
    var overlay = document.getElementById('cameraOverlay');
    placeholder.style.display = 'none';
    errorEl.style.display = 'none';

    try {
        if (!scanner) scanner = new Html5Qrcode('qrReader');

        if (!availableCameras.length) {
            availableCameras = await Html5Qrcode.getCameras();
        }
        if (!availableCameras.length) throw new Error('No cameras');

        var camId = availableCameras[currentCameraIdx % availableCameras.length].id;
        await scanner.start(
            camId,
            { fps: 10, qrbox: { width: 250, height: 100 },
              formatsToSupport: [
                  Html5QrcodeSupportedFormats.QR_CODE,
                  Html5QrcodeSupportedFormats.CODE_128,
                  Html5QrcodeSupportedFormats.CODE_39,
                  Html5QrcodeSupportedFormats.EAN_13,
                  Html5QrcodeSupportedFormats.EAN_8,
                  Html5QrcodeSupportedFormats.CODE_93
              ]
            },
            onScanSuccess,
            function() {}
        );
        cameraActive = true;
        overlay.style.display = '';
        updateCamBtn(true);
        if (availableCameras.length > 1) document.getElementById('camSwitchBtn').style.display = '';
    } catch(e) {
        placeholder.style.display = 'none';
        errorEl.style.display = '';
        console.error('Camera error', e);
    }
}

function stopCamera() {
    if (scanner && cameraActive) {
        scanner.stop().catch(function(){});
    }
    cameraActive = false;
    document.getElementById('cameraOverlay').style.display = 'none';
    document.getElementById('cameraPlaceholder').style.display = '';
    document.getElementById('camSwitchBtn').style.display = 'none';
    updateCamBtn(false);
}

function updateCamBtn(on) {
    var btn = document.getElementById('camToggleBtn');
    var label = document.getElementById('camToggleLabel');
    if (on) {
        btn.classList.add('active');
        label.textContent = TH ? 'ปิดกล้อง' : 'Close Camera';
    } else {
        btn.classList.remove('active');
        label.textContent = TH ? 'เปิดกล้อง' : 'Open Camera';
    }
}

async function switchCamera() {
    if (!cameraActive || availableCameras.length < 2) return;
    currentCameraIdx = (currentCameraIdx + 1) % availableCameras.length;
    await scanner.stop().catch(function(){});
    cameraActive = false;
    startCamera();
}

function onScanSuccess(decodedText) {
    stopCamera();
    if (navigator.vibrate) navigator.vibrate(100);
    lookupBarcode(decodedText);
}

// ========== BARCODE LOOKUP ==========
function processBarcode() {
    var code = document.getElementById('barcodeInput').value.trim();
    if (!code) return;
    lookupBarcode(code);
}

async function lookupBarcode(barcode) {
    document.getElementById('resultEmpty').style.display = 'none';
    document.getElementById('scanResult').style.display = 'none';
    document.getElementById('scanProcessing').style.display = '';
    document.getElementById('goBtn').disabled = true;
    document.getElementById('barcodeInput').value = barcode;

    addRecentScan(barcode);

    try {
        var d = await apiFetch(API + '?action=scan_barcode&barcode=' + encodeURIComponent(barcode));
        if (!d.success) throw new Error(d.error || 'Not found');

        scannedItemData = d.data;
        document.getElementById('scanProcessing').style.display = 'none';
        renderScanResult(d.data);
    } catch(e) {
        document.getElementById('scanProcessing').style.display = 'none';
        renderNotFound(barcode, e.message);
    } finally {
        document.getElementById('goBtn').disabled = false;
    }
}

// ========== RENDER RESULT ==========
function renderScanResult(data) {
    var item = data.item;
    var relation = data.relation;
    var activeBorrow = data.active_borrow;
    var isOwner = relation === 'owner';
    var isBorrower = relation === 'borrower';

    // Badge
    var badgeHtml = '';
    var iconClass = 'other';
    if (isOwner) {
        badgeHtml = '<div class="scan-result-badge owner-badge"><i class="fas fa-crown"></i> ' + (TH?'คุณเป็นเจ้าของสารนี้':'You own this chemical') + '</div>';
        iconClass = 'owner';
    } else if (isBorrower) {
        badgeHtml = '<div class="scan-result-badge return-badge"><i class="fas fa-undo"></i> ' + (TH?'คุณกำลังยืมสารนี้อยู่':'You are currently borrowing this') + '</div>';
        iconClass = 'returnable';
    } else {
        var ownerName = esc(item.owner_name || (TH?'ไม่ระบุ':'Unknown'));
        badgeHtml = '<div class="scan-result-badge other-badge"><i class="fas fa-user"></i> ' + (TH?'เจ้าของ: ':'Owner: ') + ownerName + '</div>';
        iconClass = 'other';
    }

    // Status badge
    var statusColors = { active:'#059669', borrowed:'#ea580c', disposed:'#dc2626', empty:'#6b7280' };
    var statusBgs = { active:'#ecfdf5', borrowed:'#fff7ed', disposed:'#fef2f2', empty:'#f9fafb' };
    var statusLabels = { active:(TH?'ใช้งาน':'Active'), borrowed:(TH?'ถูกยืม':'Borrowed'), disposed:(TH?'จำหน่าย':'Disposed'), empty:(TH?'หมด':'Empty') };
    var stKey = item.status || 'active';
    var stColor = statusColors[stKey] || '#059669';
    var stBg = statusBgs[stKey] || '#ecfdf5';
    var stLabel = statusLabels[stKey] || stKey;
    var statusBadge = '<span style="display:inline-flex;align-items:center;gap:4px;font-size:10px;font-weight:600;padding:3px 10px;border-radius:20px;background:' + stBg + ';color:' + stColor + ';border:1px solid ' + stColor + '22"><i class="fas fa-circle" style="font-size:5px"></i> ' + stLabel + '</span>';

    // Action buttons - Smart display based on ownership and borrow status
    var actionsHtml = '';
    if (isBorrower && activeBorrow) {
        // User is currently borrowing this item → show RETURN button
        actionsHtml = '<button onclick="openReturnModalFromScan()" class="scan-action-btn act-return"><i class="fas fa-undo"></i> ' + (TH?'คืนสารเคมี':'Return Chemical') + '</button>';
    } else if (isOwner) {
        // User is the owner → show USE button for stock management
        actionsHtml = '<button onclick="goAction(\'use\')" class="scan-action-btn act-use"><i class="fas fa-eye-dropper"></i> ' + (TH?'เบิกใช้สารเคมี':'Use Chemical') + '</button>';
        if (IS_MANAGER) {
            actionsHtml += '<div class="scan-action-sec">' +
                '<button onclick="goAction(\'transfer\')" class="scan-action-btn act-transfer"><i class="fas fa-people-arrows"></i> ' + (TH?'โอน':'Transfer') + '</button>' +
                '<button onclick="goAction(\'dispose\')" class="scan-action-btn act-dispose"><i class="fas fa-trash-alt"></i> ' + (TH?'จำหน่าย':'Dispose') + '</button>' +
            '</div>';
        }
    } else {
        // User is NOT owner → show BORROW button (or REPORT to notify owner)
        actionsHtml = '<button onclick="goAction(\'borrow\')" class="scan-action-btn act-borrow"><i class="fas fa-hand-holding-medical"></i> ' + (TH?'ยืมสารเคมี':'Borrow Chemical') + '</button>';
        // Additional action: Report issue/inquiry about this item
        actionsHtml += '<button onclick="openReportModal(\'' + esc(item.barcode || '') + '\', ' + item.source_id + ', \'' + esc(item.source_type) + '\')" class="scan-action-btn" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);margin-top:8px"><i class="fas fa-exclamation-circle"></i> ' + (TH?'แจ้งเหตุ/สอบถาม':'Report/Inquiry') + '</button>';
        if (IS_MANAGER) {
            actionsHtml += '<div class="scan-action-sec">' +
                '<button onclick="goAction(\'transfer\')" class="scan-action-btn act-transfer"><i class="fas fa-people-arrows"></i> ' + (TH?'โอน':'Transfer') + '</button>' +
                '<button onclick="goAction(\'dispose\')" class="scan-action-btn act-dispose"><i class="fas fa-trash-alt"></i> ' + (TH?'จำหน่าย':'Dispose') + '</button>' +
            '</div>';
        }
    }

    // Borrow detail
    var borrowDetailHtml = '';
    if (isBorrower && activeBorrow) {
        borrowDetailHtml = '<div style="font-size:11px;color:var(--c3);margin-top:8px;display:flex;align-items:center;gap:6px;flex-wrap:wrap">' +
            '<i class="fas fa-calendar"></i> ' + (TH?'ยืมเมื่อ':'Borrowed on') + ': ' + formatDate(activeBorrow.created_at) +
            ' · ' + Number(activeBorrow.quantity).toLocaleString() + ' ' + esc(activeBorrow.unit || '') +
            (activeBorrow.expected_return_date ? (' · ' + (TH?'กำหนดคืน':'Due') + ': ' + formatDate(activeBorrow.expected_return_date)) : '') +
        '</div>';
    }

    // Links
    var linksHtml = '<button class="scan-link-btn" onclick="openTimeline(\'' + esc(item.barcode || '') + '\')">' +
        '<i class="fas fa-history"></i> ' + (TH?'Timeline':'Timeline') + '</button>';
    if (item.source_type === 'container') {
        linksHtml += '<a class="scan-link-btn" href="/v1/pages/containers.php?id=' + item.source_id + '"><i class="fas fa-eye"></i> ' + (TH?'รายละเอียด':'Details') + '</a>' +
            '<a class="scan-link-btn" href="/v1/ar/view_ar.php?id=' + item.source_id + '"><i class="fas fa-cube"></i> AR</a>';
    } else {
        linksHtml += '<a class="scan-link-btn" href="/v1/pages/stock.php?id=' + item.source_id + '"><i class="fas fa-eye"></i> ' + (TH?'รายละเอียด':'Details') + '</a>';
    }

    var html = '<div class="scan-result-card">' +
        '<div class="scan-result-hdr">' +
            '<div class="scan-result-icon ' + iconClass + '">' +
                '<i class="fas ' + (isOwner?'fa-crown':isBorrower?'fa-undo':'fa-flask') + '"></i>' +
            '</div>' +
            '<div style="flex:1;min-width:0">' +
                '<div class="scan-result-name">' + esc(item.chemical_name) + '</div>' +
                (item.cas_number ? '<div class="scan-result-cas">CAS: ' + item.cas_number + '</div>' : '') +
            '</div>' +
            statusBadge +
        '</div>' +
        '<div class="scan-result-body">' +
            '<div class="scan-result-chips">' +
                '<span class="scan-result-chip"><i class="fas fa-barcode"></i> ' + esc(item.barcode || '-') + '</span>' +
                '<span class="scan-result-chip"><i class="fas fa-flask"></i> ' + Number(item.remaining_qty).toLocaleString() + ' ' + esc(item.unit || '') + '</span>' +
                '<span class="scan-result-chip"><i class="fas fa-user"></i> ' + esc(item.owner_name || '-') + '</span>' +
                (item.department ? '<span class="scan-result-chip"><i class="fas fa-building"></i> ' + esc(item.department) + '</span>' : '') +
                '<span class="scan-result-chip"><i class="fas fa-tag"></i> ' + (item.source_type === 'container' ? 'Container' : 'Stock') + '</span>' +
            '</div>' +
            badgeHtml +
            borrowDetailHtml +
        '</div>' +
        '<div class="scan-result-actions">' + actionsHtml + '</div>' +
        '<div class="scan-result-links">' + linksHtml + '</div>' +
    '</div>';

    document.getElementById('scanResult').innerHTML = html;
    document.getElementById('scanResult').style.display = '';
}

function renderNotFound(barcode, msg) {
    document.getElementById('scanResult').innerHTML = '<div class="scan-result-card">' +
        '<div class="scan-result-hdr">' +
            '<div class="scan-result-icon error"><i class="fas fa-times-circle"></i></div>' +
            '<div>' +
                '<div class="scan-result-name">' + (TH?'ไม่พบรายการ':'Not Found') + '</div>' +
                '<div class="scan-result-cas">' + esc(barcode) + '</div>' +
            '</div>' +
        '</div>' +
        '<div class="scan-result-body">' +
            '<p style="font-size:13px;color:var(--c3);margin:0">' + (TH?'ไม่พบสารเคมีที่ตรงกับ Barcode นี้ในระบบ':'No chemical found matching this barcode in the system') + '</p>' +
            '<div style="margin-top:12px">' +
                '<a href="/v1/pages/stock.php" class="scan-link-btn" style="display:inline-flex;text-decoration:none">' +
                    '<i class="fas fa-plus"></i> ' + (TH?'เพิ่มสารเคมีใหม่':'Add New Chemical') +
                '</a>' +
            '</div>' +
        '</div>' +
    '</div>';
    document.getElementById('scanResult').style.display = '';
}

// ========== ACTIONS → Navigate to borrow page with mode ==========
function goAction(mode) {
    if (!scannedItemData) return;
    var item = scannedItemData.item;
    sessionStorage.setItem('scanAction', JSON.stringify({
        mode: mode,
        item: item,
        timestamp: Date.now()
    }));
    window.location.href = '/v1/pages/borrow.php?scan_action=' + mode;
}

// ========== RETURN MODAL (inline) ==========
function openReturnModalFromScan() {
    if (!scannedItemData || !scannedItemData.active_borrow) return;
    var ab = scannedItemData.active_borrow;
    var item = scannedItemData.item;
    returnTxnId = ab.id;

    document.getElementById('retChemName').textContent = item.chemical_name || '-';
    document.getElementById('retBorrowInfo').textContent =
        (TH ? 'ยืม ' : 'Borrowed ') + Number(ab.quantity).toLocaleString() + ' ' + (ab.unit || '') +
        (TH ? ' เมื่อ ' : ' on ') + formatDate(ab.created_at);
    document.getElementById('retQty').value = ab.quantity;
    document.getElementById('retQty').max = ab.quantity;
    document.getElementById('retUnit').textContent = ab.unit || '';
    document.getElementById('retNotes').value = '';

    document.getElementById('returnModal').classList.add('show');
}

function closeReturnModal() {
    document.getElementById('returnModal').classList.remove('show');
    returnTxnId = null;
}

async function submitReturn() {
    if (!returnTxnId) return;
    var qty = parseFloat(document.getElementById('retQty').value);
    var notes = document.getElementById('retNotes').value.trim();
    if (!qty || qty <= 0) {
        showToast(TH ? 'กรุณาระบุจำนวน' : 'Please enter quantity', 'error');
        return;
    }

    var btn = document.getElementById('retSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<div class="ci-spinner" style="width:16px;height:16px"></div>';

    try {
        var fd = new FormData();
        fd.append('txn_id', returnTxnId);
        fd.append('return_quantity', qty);
        if (notes) fd.append('notes', notes);

        var d = await apiFetch(API + '?action=return', { method: 'POST', body: fd });
        if (!d.success) throw new Error(d.error || 'Error');

        showToast(TH ? 'คืนสารเคมีสำเร็จ!' : 'Chemical returned successfully!', 'success');
        closeReturnModal();

        loadActiveBorrows();
        loadStats();

        if (scannedItemData && scannedItemData.item) {
            setTimeout(function(){ lookupBarcode(scannedItemData.item.barcode); }, 500);
        }
    } catch(e) {
        showToast(e.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-undo"></i> ' + (TH?'ยืนยันคืน':'Confirm Return');
    }
}

// ========== TIMELINE MODAL ==========
async function openTimeline(barcode) {
    if (!barcode) return;
    document.getElementById('timelineModal').classList.add('show');
    document.getElementById('timelineContent').innerHTML = '<div class="scan-processing"><div class="ci-spinner"></div><p>' + (TH?'กำลังโหลด...':'Loading...') + '</p></div>';

    try {
        var d = await apiFetch(API + '?action=timeline&barcode=' + encodeURIComponent(barcode));
        if (!d.success) throw new Error(d.error);
        renderTimeline(d.data || []);
    } catch(e) {
        document.getElementById('timelineContent').innerHTML = '<div style="text-align:center;padding:30px;color:var(--c3)">' +
            '<i class="fas fa-exclamation-triangle" style="font-size:24px;display:block;margin-bottom:8px;color:#f59e0b"></i>' +
            '<p>' + (TH?'ไม่สามารถโหลด Timeline ได้':'Could not load timeline') + '</p></div>';
    }
}

function renderTimeline(events) {
    var el = document.getElementById('timelineContent');
    if (!events.length) {
        el.innerHTML = '<div style="text-align:center;padding:30px;color:var(--c3)">' +
            '<i class="fas fa-inbox" style="font-size:24px;display:block;margin-bottom:8px;color:#ccc"></i>' +
            '<p>' + (TH?'ยังไม่มีประวัติธุรกรรม':'No transaction history yet') + '</p></div>';
        return;
    }

    var iconMap = {
        'borrow': { icon: 'fa-hand-holding-medical', cls: 'borrow' },
        'return': { icon: 'fa-undo', cls: 'return' },
        'use':    { icon: 'fa-eye-dropper', cls: 'use' },
        'transfer': { icon: 'fa-people-arrows', cls: 'transfer' },
        'dispose': { icon: 'fa-trash-alt', cls: 'dispose' }
    };

    el.innerHTML = events.map(function(ev) {
        var t = ev.txn_type || ev.type || 'borrow';
        var m = iconMap[t] || { icon: 'fa-circle', cls: 'default' };
        return '<div class="tl-item">' +
            '<div class="tl-dot ' + m.cls + '"><i class="fas ' + m.icon + '"></i></div>' +
            '<div class="tl-content">' +
                '<h5>' + t + '</h5>' +
                '<p>' + (ev.description || '') + ' · ' + Number(ev.quantity || 0).toLocaleString() + ' ' + esc(ev.unit || '') + '</p>' +
                '<p>' + (ev.user_name ? ((TH?'โดย ':'By ') + esc(ev.user_name)) : '') + '</p>' +
                '<div class="tl-date"><i class="fas fa-clock" style="font-size:9px"></i> ' + formatDate(ev.created_at || ev.date) + '</div>' +
            '</div>' +
        '</div>';
    }).join('');
}

function closeTimeline() {
    document.getElementById('timelineModal').classList.remove('show');
}

// Close modals on backdrop click
document.getElementById('timelineModal').addEventListener('click', function(e) { if (e.target === e.currentTarget) closeTimeline(); });
document.getElementById('returnModal').addEventListener('click', function(e) { if (e.target === e.currentTarget) closeReturnModal(); });

// ========== RECENT SCANS ==========
var RECENT_KEY = 'qrScannerRecent';
var recentScans = [];
try { recentScans = JSON.parse(localStorage.getItem(RECENT_KEY) || '[]'); } catch(e) { recentScans = []; }

function addRecentScan(code) {
    var idx = -1;
    for (var i = 0; i < recentScans.length; i++) { if (recentScans[i].code === code) { idx = i; break; } }
    if (idx > -1) recentScans.splice(idx, 1);
    recentScans.unshift({ code: code, time: Date.now() });
    if (recentScans.length > 15) recentScans.pop();
    localStorage.setItem(RECENT_KEY, JSON.stringify(recentScans));
    renderRecent();
}

function renderRecent() {
    var el = document.getElementById('recentList');
    if (!recentScans.length) {
        el.innerHTML = '<div class="recent-empty"><i class="fas fa-history"></i>' + (TH?'ยังไม่มีประวัติสแกน':'No scan history yet') + '</div>';
        return;
    }
    el.innerHTML = recentScans.map(function(r) {
        var ago = timeAgo(r.time);
        return '<div class="recent-item" onclick="lookupBarcode(\'' + esc(r.code) + '\')">' +
            '<i class="recent-item-icon fas fa-qrcode"></i>' +
            '<span class="recent-item-code">' + esc(r.code) + '</span>' +
            '<span class="recent-item-time">' + ago + '</span>' +
        '</div>';
    }).join('');
}

function clearRecent() {
    recentScans = [];
    localStorage.removeItem(RECENT_KEY);
    renderRecent();
    showToast(TH ? 'ล้างประวัติสำเร็จ' : 'History cleared', 'success');
}

function timeAgo(ts) {
    var diff = Math.floor((Date.now() - ts) / 1000);
    if (diff < 60) return (TH ? 'เมื่อกี้' : 'Just now');
    if (diff < 3600) return Math.floor(diff / 60) + (TH ? ' นาที' : 'm');
    if (diff < 86400) return Math.floor(diff / 3600) + (TH ? ' ชม.' : 'h');
    return Math.floor(diff / 86400) + (TH ? ' วัน' : 'd');
}

// ========== REPORT/INQUIRY MODAL ==========
function openReportModal(barcode, sourceId, sourceType) {
    document.getElementById('reportBarcode').value = barcode;
    document.getElementById('reportSourceId').value = sourceId;
    document.getElementById('reportSourceType').value = sourceType;
    document.getElementById('reportType').value = 'inquiry'; // Default
    document.getElementById('reportMessage').value = '';
    document.getElementById('reportModal').classList.add('show');
}

function closeReportModal() {
    document.getElementById('reportModal').classList.remove('show');
}

async function submitReport() {
    const barcode = document.getElementById('reportBarcode').value;
    const sourceId = parseInt(document.getElementById('reportSourceId').value);
    const sourceType = document.getElementById('reportSourceType').value;
    const reportType = document.getElementById('reportType').value; // inquiry, issue, request
    const message = document.getElementById('reportMessage').value.trim();

    if (!message) {
        showToast(TH ? 'กรุณากรอกข้อความ' : 'Please enter a message', 'error');
        return;
    }

    try {
        // Get item owner info first for notifying them
        const itemData = scannedItemData ? scannedItemData.item : {};
        const ownerId = itemData.owner_id ? parseInt(itemData.owner_id) : null;

        // Create alert/notification record - store as custom notification since it's a report
        const d = await apiFetch('/v1/api/borrow.php?action=report_item', {
            method: 'POST',
            body: JSON.stringify({
                source_type: sourceType,
                source_id: sourceId,
                barcode: barcode,
                report_type: reportType,
                message: message,
                owner_id: ownerId
            })
        });

        if (d.success) {
            closeReportModal();
            showToast(TH ? 'แจ้งเหตุสำเร็จ เจ้าของสารจะได้รับการแจ้งเตือน' : 'Report sent successfully. Item owner will be notified.', 'success');
            // Clear input
            document.getElementById('reportMessage').value = '';
        } else {
            showToast(d.error || 'Error', 'error');
        }
    } catch (e) {
        showToast('Error: ' + e.message, 'error');
    }
}

// ========== UTILITY ==========
function statusBadge(s) {
    var m = { active: 'ci-badge-success', borrowed: 'ci-badge-warning', disposed: 'ci-badge-danger', empty: 'ci-badge-default' };
    return '<span class="ci-badge ' + (m[s]||'ci-badge-default') + '">' + s + '</span>';
}
</script>
<?php Layout::footer(); ?>
