<?php
require_once __DIR__ . '/../includes/layout.php';
$user = Auth::getCurrentUser();
if (!$user) { header('Location: /v1/pages/login.php'); exit; }
$lang = I18n::getCurrentLang();
$userId = $user['id'];
$roleLevel = (int)($user['role_level'] ?? $user['level'] ?? 0);
$isAdmin = $roleLevel >= 5;
$isManager = $roleLevel >= 3;

// Load buildings & departments for filters
$buildings = Database::fetchAll("SELECT id, name FROM buildings ORDER BY name");
$departments = Database::fetchAll("SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != '' ORDER BY department");

Layout::head($lang==='th'?'ใช้ / ยืม / โอน / คืน สารเคมี':'Use / Borrow / Transfer / Return');
?>
<body>
<?php Layout::sidebar('borrow'); Layout::beginContent(); ?>
<?php Layout::pageHeader(
    $lang==='th'?'ใช้ / ยืม / โอน / คืน สารเคมี':'Chemical Transactions',
    'fas fa-exchange-alt',
    $lang==='th'?'บริหารจัดการยืม คืน โอน และจำหน่ายสารเคมี พร้อมติดตาม lifecycle ตาม barcode':'Manage borrow, return, transfer & disposal with barcode lifecycle tracking'
); ?>

<!-- ===== PRO DASHBOARD ===== -->
<div id="txnDashboard" class="txn-dash-grid">
    <div class="txn-dash-loading"><div class="ci-spinner"></div></div>
</div>

<!-- ===== ACTION BUTTONS ===== -->
<div class="txn-action-bar">
    <button onclick="openNewTxn('borrow')" class="ci-btn ci-btn-primary"><i class="fas fa-hand-holding-medical"></i> <?php echo $lang==='th'?'ยืมสาร':'Borrow'; ?></button>
    <button onclick="openNewTxn('use')" class="ci-btn" style="background:#7c3aed;color:#fff;border-color:#7c3aed"><i class="fas fa-eye-dropper"></i> <?php echo $lang==='th'?'ใช้สาร':'Use'; ?></button>
    <button onclick="openNewTxn('transfer')" class="ci-btn ci-btn-outline"><i class="fas fa-people-arrows"></i> <?php echo $lang==='th'?'โอนสาร':'Transfer'; ?></button>
    <button onclick="openScanModal()" class="ci-btn scan-btn-glow"><i class="fas fa-qrcode"></i> <?php echo $lang==='th'?'แสกน Barcode':'Scan Barcode'; ?></button>
    <button onclick="openTimelineModal()" class="ci-btn ci-btn-secondary"><i class="fas fa-history"></i> <?php echo $lang==='th'?'ดู Timeline':'Timeline'; ?></button>
    <?php if ($isManager): ?>
    <button onclick="openNewTxn('dispose')" class="ci-btn ci-btn-danger"><i class="fas fa-trash-alt"></i> <?php echo $lang==='th'?'จำหน่ายออก':'Dispose'; ?></button>
    <?php endif; ?>
</div>

<!-- ===== TABS ===== -->
<div class="ci-tabs" style="margin-bottom:0">
    <button onclick="switchTab('all')" id="tab-all" class="ci-tab active"><?php echo $lang==='th'?'ทั้งหมด':'All'; ?></button>
    <button onclick="switchTab('pending')" id="tab-pending" class="ci-tab"><?php echo $lang==='th'?'รออนุมัติ':'Pending'; ?></button>
    <button onclick="switchTab('active')" id="tab-active" class="ci-tab"><?php echo $lang==='th'?'กำลังยืม':'Active Borrows'; ?></button>
    <button onclick="switchTab('overdue')" id="tab-overdue" class="ci-tab"><?php echo $lang==='th'?'เกินกำหนด':'Overdue'; ?></button>
    <?php if ($isManager): ?>
    <button onclick="switchTab('disposal')" id="tab-disposal" class="ci-tab"><i class="fas fa-trash-alt" style="font-size:11px"></i> <?php echo $lang==='th'?'จำหน่าย':'Disposal'; ?></button>
    <?php endif; ?>
</div>

<!-- ===== FILTER BAR ===== -->
<div class="ci-card" style="border-top:none;border-radius:0 0 6px 6px;margin-bottom:16px;overflow:visible">
<div class="ci-card-body" style="padding:10px 14px;overflow:visible">
<div class="ci-filter-bar" style="flex-direction:row;flex-wrap:wrap;gap:8px">
    <div style="position:relative;flex:2;min-width:180px">
        <i class="fas fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#aaa;font-size:12px"></i>
        <input type="text" id="filterSearch" class="ci-input" placeholder="<?php echo $lang==='th'?'ค้นหาชื่อสาร / Barcode / หมายเลข...':'Search chemical, barcode, txn#...'; ?>" style="padding-left:32px" oninput="debounceLoad()">
    </div>
    <select id="filterBuilding" class="ci-select" style="flex:1;min-width:120px" onchange="loadList()">
        <option value=""><?php echo $lang==='th'?'ทุกอาคาร':'All Buildings'; ?></option>
        <?php foreach($buildings as $b): ?>
        <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['name']); ?></option>
        <?php endforeach; ?>
    </select>
    <select id="filterDept" class="ci-select" style="flex:1;min-width:120px" onchange="loadList()">
        <option value=""><?php echo $lang==='th'?'ทุกหน่วยงาน':'All Departments'; ?></option>
        <?php foreach($departments as $d): ?>
        <option value="<?php echo htmlspecialchars($d['department']); ?>"><?php echo htmlspecialchars($d['department']); ?></option>
        <?php endforeach; ?>
    </select>
    <select id="filterType" class="ci-select" style="flex:1;min-width:100px" onchange="loadList()">
        <option value=""><?php echo $lang==='th'?'ทุกประเภท':'All Types'; ?></option>
        <option value="borrow"><?php echo $lang==='th'?'ยืม':'Borrow'; ?></option>
        <option value="use"><?php echo $lang==='th'?'ใช้':'Use'; ?></option>
        <option value="return"><?php echo $lang==='th'?'คืน':'Return'; ?></option>
        <option value="transfer"><?php echo $lang==='th'?'โอน':'Transfer'; ?></option>
        <option value="dispose"><?php echo $lang==='th'?'จำหน่าย':'Dispose'; ?></option>
    </select>
</div>
</div>
</div>

<!-- ===== LIST ===== -->
<div id="txnList" style="overflow:visible"><div class="ci-loading"><div class="ci-spinner"></div></div></div>
<div id="txnPagination"></div>

<!-- ===== EMPTY STATE ===== -->
<div id="emptyState" style="display:none" class="ci-empty">
    <i class="fas fa-exchange-alt"></i>
    <p style="font-size:15px;font-weight:500;margin-bottom:4px" id="emptyTitle"></p>
    <p><?php echo $lang==='th'?'รายการธุรกรรมจะปรากฏที่นี่':'Transactions will appear here'; ?></p>
</div>

<!-- ===== NEW TRANSACTION MODAL ===== -->
<div class="ci-modal-bg" id="txnModal">
<div class="ci-modal txn-modal-pro">
    <!-- Pro Header -->
    <div class="txn-modal-hdr" id="txnHdrGradient">
        <div class="txn-hdr-content">
            <div class="txn-hdr-icon" id="txnHdrIcon"><i class="fas fa-hand-holding-medical"></i></div>
            <div>
                <h3 id="txnModalTitle"><?php echo $lang==='th'?'ยืมสารเคมี':'Borrow Chemical'; ?></h3>
                <p class="txn-hdr-sub" id="txnModalSub"><?php echo $lang==='th'?'ค้นหาและเลือกสารเคมีที่ต้องการยืม':'Search and select the chemical you want to borrow'; ?></p>
            </div>
        </div>
        <button class="txn-modal-close" onclick="closeTxnModal()">&times;</button>
    </div>

    <!-- Stepper -->
    <div class="txn-stepper">
        <div class="txn-step active" id="step-ind-1">
            <div class="txn-step-dot">1</div>
            <span><?php echo $lang==='th'?'เลือกสาร':'Select Item'; ?></span>
        </div>
        <div class="txn-step-line" id="step-line-1"></div>
        <div class="txn-step" id="step-ind-2">
            <div class="txn-step-dot">2</div>
            <span><?php echo $lang==='th'?'ระบุรายละเอียด':'Details'; ?></span>
        </div>
        <div class="txn-step-line" id="step-line-2"></div>
        <div class="txn-step" id="step-ind-3">
            <div class="txn-step-dot">3</div>
            <span><?php echo $lang==='th'?'ยืนยัน':'Confirm'; ?></span>
        </div>
    </div>

    <div class="ci-modal-body" style="padding:20px 24px 24px">
        <!-- ===== STEP 1: Search & Pick Item ===== -->
        <div id="txnStep1" style="min-height:260px">
            <div style="position:relative">
                <div class="txn-search-box">
                    <i class="fas fa-search txn-search-icon"></i>
                    <input type="text" id="itemSearch" class="txn-search-input" placeholder="<?php echo $lang==='th'?'พิมพ์ชื่อสาร, Barcode หรือ CAS No. เพื่อค้นหา...':'Type chemical name, barcode or CAS No. to search...'; ?>" oninput="debounceItemSearch()" autocomplete="off">
                    <button type="button" class="txn-search-scan-btn" onclick="openInModalScan()" title="<?php echo $lang==='th'?'แสกน Barcode':'Scan Barcode'; ?>"><i class="fas fa-qrcode"></i></button>
                    <div class="txn-search-shortcut" id="searchHint"><kbd>↵</kbd></div>
                </div>
                <div id="itemResults" class="txn-search-results" style="display:none"></div>
            </div>

            <!-- Empty state when no search -->
            <div id="searchGuide" class="txn-search-guide">
                <div class="txn-guide-icon"><i class="fas fa-flask"></i></div>
                <p><?php echo $lang==='th'?'ค้นหาสารเคมีด้วยชื่อ, Barcode หรือ CAS Number':'Search chemicals by name, barcode or CAS number'; ?></p>
                <div class="txn-guide-tips">
                    <span><i class="fas fa-barcode"></i> <?php echo $lang==='th'?'รหัสขวด':'Bottle code'; ?></span>
                    <span><i class="fas fa-flask"></i> <?php echo $lang==='th'?'ชื่อสารเคมี':'Chemical name'; ?></span>
                    <span><i class="fas fa-hashtag"></i> CAS Number</span>
                </div>
            </div>

            <!-- Selected Item Card (enhanced) -->
            <div id="selectedItem" style="display:none"></div>
        </div>

        <!-- ===== STEP 2: Details ===== -->
        <div id="txnStep2" style="display:none">
            <!-- Selected item summary mini-card -->
            <div class="txn-mini-card" id="txnMiniCard"></div>

            <!-- Quantity -->
            <div class="txn-form-section">
                <label class="txn-form-label"><i class="fas fa-vial"></i> <?php echo $lang==='th'?'ปริมาณที่ต้องการ':'Desired Quantity'; ?> <span class="text-danger">*</span></label>
                <div class="txn-qty-row">
                    <div class="txn-qty-input-wrap">
                        <input type="number" id="txnQty" class="txn-qty-input" step="0.01" min="0.01" placeholder="0.00" oninput="updateQtyBar()">
                        <div class="txn-qty-unit" id="txnUnitLabel">mL</div>
                    </div>
                    <input type="hidden" id="txnUnit">
                </div>
                <div class="txn-qty-bar-wrap" id="qtyBarWrap" style="display:none">
                    <div class="txn-qty-bar"><div class="txn-qty-bar-fill" id="qtyBarFill"></div></div>
                    <div class="txn-qty-info">
                        <span id="txnQtyHint" class="txn-qty-hint"></span>
                        <span class="txn-qty-max" id="qtyMaxLabel"></span>
                    </div>
                </div>
            </div>

            <!-- Recipient (for transfer) -->
            <div id="recipientSection" style="display:none" class="txn-form-section">
                <label class="txn-form-label"><i class="fas fa-user-plus"></i> <?php echo $lang==='th'?'โอนให้บุคคล':'Transfer to Person'; ?> <span class="text-danger">*</span></label>
                <div style="position:relative">
                    <div class="txn-search-box" style="margin-bottom:0">
                        <i class="fas fa-user txn-search-icon" style="color:#7c3aed"></i>
                        <input type="text" id="userSearch" class="txn-search-input" placeholder="<?php echo $lang==='th'?'ค้นหาชื่อ / username...':'Search name / username...'; ?>" oninput="debounceUserSearch()" autocomplete="off">
                    </div>
                    <div id="userResults" class="txn-search-results" style="display:none"></div>
                </div>
                <div id="selectedUser" style="display:none;margin-top:8px"></div>
            </div>

            <!-- Use mode: info banner -->
            <div id="useInfoSection" style="display:none">
                <div class="use-info-box">
                    <div class="use-info-icon"><i class="fas fa-eye-dropper"></i></div>
                    <div>
                        <div style="font-weight:700;font-size:13px;color:#5b21b6"><?php echo $lang==='th'?'เบิกใช้สารเคมีของคุณ':'Use Your Own Chemical'; ?></div>
                        <div style="font-size:11px;color:#6d28d9;margin-top:2px"><?php echo $lang==='th'?'ปริมาณจะถูกหักจาก stock ทันที ไม่ต้องรออนุมัติ':'Quantity will be deducted immediately — no approval needed'; ?></div>
                    </div>
                </div>
            </div>

            <!-- Use mode: quick quantity presets -->
            <div id="useQuickQty" style="display:none" class="txn-form-section">
                <label class="txn-form-label" style="margin-bottom:6px"><i class="fas fa-bolt"></i> <?php echo $lang==='th'?'เลือกปริมาณด่วน':'Quick Select'; ?></label>
                <div class="use-quick-grid">
                    <button type="button" class="use-quick-btn" onclick="setQuickQty(0.25)"><span class="use-quick-pct">25%</span><span class="use-quick-val" id="quickVal25"></span></button>
                    <button type="button" class="use-quick-btn" onclick="setQuickQty(0.50)"><span class="use-quick-pct">50%</span><span class="use-quick-val" id="quickVal50"></span></button>
                    <button type="button" class="use-quick-btn" onclick="setQuickQty(0.75)"><span class="use-quick-pct">75%</span><span class="use-quick-val" id="quickVal75"></span></button>
                    <button type="button" class="use-quick-btn use-quick-all" onclick="setQuickQty(1.0)"><span class="use-quick-pct"><?php echo $lang==='th'?'ทั้งหมด':'All'; ?></span><span class="use-quick-val" id="quickVal100"></span></button>
                </div>
            </div>

            <!-- Purpose -->
            <div class="txn-form-section">
                <label class="txn-form-label"><i class="fas fa-comment-alt"></i> <?php echo $lang==='th'?'วัตถุประสงค์ / หมายเหตุ':'Purpose / Notes'; ?></label>
                <input type="text" id="txnPurpose" class="ci-input" placeholder="<?php echo $lang==='th'?'ระบุวัตถุประสงค์ เช่น งานวิจัย, ทดสอบ (ไม่จำเป็น)':'e.g. research, testing (optional)'; ?>" oninput="if(txnMode==='use')updateUsePreview()">
            </div>

            <!-- Use mode: preview -->
            <div id="usePreview" style="display:none" class="use-preview">
                <div style="font-size:12px;font-weight:600;color:#5b21b6;margin-bottom:8px"><i class="fas fa-clipboard-check"></i> <?php echo $lang==='th'?'สรุปการเบิกใช้':'Usage Summary'; ?></div>
                <div id="usePreviewContent"></div>
            </div>

            <!-- Return Date (for borrow) -->
            <div id="returnDateSection" class="txn-form-section">
                <label class="txn-form-label"><i class="fas fa-calendar-alt"></i> <?php echo $lang==='th'?'กำหนดคืน':'Expected Return Date'; ?></label>
                <input type="date" id="txnReturnDate" class="ci-input">
                <div class="ci-hint"><?php echo $lang==='th'?'ไม่บังคับ — ระบุเพื่อช่วยติดตามการคืน':'Optional — set to help track returns'; ?></div>
            </div>

            <!-- Disposal fields (enhanced) -->
            <div id="disposeSection" style="display:none">
                <div class="dispose-warning-box">
                    <div class="dispose-warning-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <div>
                        <div style="font-weight:700;font-size:13px;color:#c62828"><?php echo $lang==='th'?'⚠️ จำหน่ายออกจากระบบ':'⚠️ Permanent Disposal'; ?></div>
                        <div style="font-size:11px;color:#b71c1c;margin-top:2px"><?php echo $lang==='th'?'สารเคมีนี้จะถูกย้ายเข้าถังจำหน่าย และสถานะจะเปลี่ยนเป็น "disposed" ทันที ผู้ดูแลระบบจะตรวจสอบและยืนยันการจำหน่ายอีกครั้ง':'This chemical will be moved to the disposal bin. An admin will review and confirm the final disposal.'; ?></div>
                    </div>
                </div>

                <div class="txn-form-section">
                    <label class="txn-form-label"><i class="fas fa-tag"></i> <?php echo $lang==='th'?'เหตุผลจำหน่าย':'Disposal Reason'; ?> <span class="text-danger">*</span></label>
                    <div class="dispose-reason-grid">
                        <label class="dispose-reason-opt" data-val="expired">
                            <input type="radio" name="disposeReasonR" value="expired" checked>
                            <div class="dispose-reason-card">
                                <i class="fas fa-calendar-times"></i>
                                <span><?php echo $lang==='th'?'หมดอายุ':'Expired'; ?></span>
                            </div>
                        </label>
                        <label class="dispose-reason-opt" data-val="empty">
                            <input type="radio" name="disposeReasonR" value="empty">
                            <div class="dispose-reason-card">
                                <i class="fas fa-wine-bottle"></i>
                                <span><?php echo $lang==='th'?'หมด/ใช้จนหมด':'Empty'; ?></span>
                            </div>
                        </label>
                        <label class="dispose-reason-opt" data-val="contaminated">
                            <input type="radio" name="disposeReasonR" value="contaminated">
                            <div class="dispose-reason-card">
                                <i class="fas fa-biohazard"></i>
                                <span><?php echo $lang==='th'?'ปนเปื้อน':'Contaminated'; ?></span>
                            </div>
                        </label>
                        <label class="dispose-reason-opt" data-val="damaged">
                            <input type="radio" name="disposeReasonR" value="damaged">
                            <div class="dispose-reason-card">
                                <i class="fas fa-heart-broken"></i>
                                <span><?php echo $lang==='th'?'ชำรุด/แตก':'Damaged'; ?></span>
                            </div>
                        </label>
                        <label class="dispose-reason-opt" data-val="obsolete">
                            <input type="radio" name="disposeReasonR" value="obsolete">
                            <div class="dispose-reason-card">
                                <i class="fas fa-archive"></i>
                                <span><?php echo $lang==='th'?'ไม่ใช้แล้ว':'Obsolete'; ?></span>
                            </div>
                        </label>
                        <label class="dispose-reason-opt" data-val="other">
                            <input type="radio" name="disposeReasonR" value="other">
                            <div class="dispose-reason-card">
                                <i class="fas fa-ellipsis-h"></i>
                                <span><?php echo $lang==='th'?'อื่นๆ':'Other'; ?></span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="txn-form-section">
                    <label class="txn-form-label"><i class="fas fa-cogs"></i> <?php echo $lang==='th'?'วิธีจำหน่าย':'Disposal Method'; ?></label>
                    <select id="disposeMethod" class="ci-select">
                        <option value="waste_collection"><?php echo $lang==='th'?'🗑️ ส่งเก็บของเสีย':'🗑️ Waste Collection'; ?></option>
                        <option value="neutralization"><?php echo $lang==='th'?'⚗️ ทำให้เป็นกลาง':'⚗️ Neutralization'; ?></option>
                        <option value="incineration"><?php echo $lang==='th'?'🔥 เผาทำลาย':'🔥 Incineration'; ?></option>
                        <option value="return_to_vendor"><?php echo $lang==='th'?'📦 คืนผู้ขาย':'📦 Return to Vendor'; ?></option>
                        <option value="other"><?php echo $lang==='th'?'อื่นๆ':'Other'; ?></option>
                    </select>
                </div>

                <!-- Disposal confirmation preview -->
                <div id="disposePreview" class="dispose-preview" style="display:none">
                    <div style="font-size:12px;font-weight:600;color:var(--c1);margin-bottom:8px"><i class="fas fa-clipboard-check"></i> <?php echo $lang==='th'?'ตรวจสอบก่อนจำหน่าย':'Review before disposal'; ?></div>
                    <div id="disposePreviewContent"></div>
                </div>
            </div>

            <!-- Submit -->
            <div class="txn-submit-section">
                <button onclick="goBackStep1()" class="ci-btn ci-btn-secondary" id="txnBackBtn">
                    <i class="fas fa-arrow-left"></i> <?php echo $lang==='th'?'เปลี่ยนสาร':'Change Item'; ?>
                </button>
                <button onclick="submitTxn()" class="txn-submit-btn" id="txnSubmitBtn">
                    <i class="fas fa-check-circle"></i> <span id="txnSubmitLabel"><?php echo $lang==='th'?'ยืนยันยืมสาร':'Confirm Borrow'; ?></span>
                </button>
            </div>
        </div>
    </div>
</div>
</div>

<!-- ===== BARCODE SCAN MODAL ===== -->
<div class="ci-modal-bg" id="scanModal">
<div class="ci-modal scan-modal-pro">
    <div class="scan-modal-hdr">
        <div class="scan-hdr-content">
            <div class="scan-hdr-icon"><i class="fas fa-qrcode"></i></div>
            <div>
                <h3><?php echo $lang==='th'?'แสกน Barcode':'Scan Barcode'; ?></h3>
                <p class="scan-hdr-sub"><?php echo $lang==='th'?'สแกนรหัสขวดเพื่อใช้/ยืม/คืนสารเคมีอัตโนมัติ':'Scan bottle code to auto use/borrow/return chemicals'; ?></p>
            </div>
        </div>
        <button class="txn-modal-close" onclick="closeScanModal()">&times;</button>
    </div>
    <div class="ci-modal-body" style="padding:20px 24px 24px">
        <!-- Camera viewer -->
        <div id="scanCameraWrap" class="scan-camera-wrap">
            <div id="scanReader"></div>
            <div id="scanCameraOverlay" class="scan-camera-overlay">
                <div class="scan-corner tl"></div><div class="scan-corner tr"></div>
                <div class="scan-corner bl"></div><div class="scan-corner br"></div>
                <div class="scan-line"></div>
            </div>
        </div>
        <div id="scanCameraError" style="display:none" class="scan-camera-error">
            <i class="fas fa-video-slash"></i>
            <p><?php echo $lang==='th'?'ไม่สามารถเปิดกล้องได้':'Could not access camera'; ?></p>
        </div>
        <div class="scan-camera-actions">
            <button onclick="toggleScanCamera()" class="ci-btn ci-btn-sm" id="scanCamToggle">
                <i class="fas fa-camera"></i> <span id="scanCamToggleLabel"><?php echo $lang==='th'?'เปิดกล้อง':'Open Camera'; ?></span>
            </button>
        </div>

        <!-- Manual input -->
        <div class="scan-manual-section">
            <label class="txn-form-label"><i class="fas fa-keyboard"></i> <?php echo $lang==='th'?'หรือพิมพ์รหัส Barcode':'Or enter barcode manually'; ?></label>
            <div class="scan-manual-row">
                <input type="text" id="scanBarcodeInput" class="ci-input scan-barcode-input" placeholder="<?php echo $lang==='th'?'พิมพ์รหัสขวด เช่น 320F6600000001':'Enter bottle code e.g. 320F6600000001'; ?>" autocomplete="off">
                <button onclick="processScanBarcode()" class="ci-btn ci-btn-primary scan-go-btn" id="scanGoBtn">
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- Processing state -->
        <div id="scanProcessing" style="display:none" class="scan-processing">
            <div class="ci-spinner"></div>
            <p><?php echo $lang==='th'?'กำลังค้นหา...':'Looking up...'; ?></p>
        </div>

        <!-- Result state -->
        <div id="scanResult" style="display:none"></div>
    </div>
</div>
</div>

<!-- ===== SMART MODE CHOOSER MODAL ===== -->
<div class="ci-modal-bg" id="modeChooserModal">
<div class="ci-modal" style="max-width:440px;border-radius:16px;overflow:hidden">
    <div class="mode-chooser-hdr">
        <h3 id="modeChooserTitle"><?php echo $lang==='th'?'เลือกการดำเนินการ':'Choose Action'; ?></h3>
        <button class="txn-modal-close" onclick="closeModeChooser()">&times;</button>
    </div>
    <div class="ci-modal-body" style="padding:16px 20px 20px">
        <div id="modeChooserInfo" class="mode-chooser-info"></div>
        <div id="modeChooserGrid" class="mode-chooser-grid"></div>
    </div>
</div>
</div>

<!-- ===== DETAIL MODAL ===== -->
<div class="ci-modal-bg" id="detailModal">
<div class="ci-modal" style="max-width:600px">
    <div class="ci-modal-hdr">
        <h3><i class="fas fa-receipt"></i> <?php echo $lang==='th'?'รายละเอียดธุรกรรม':'Transaction Detail'; ?></h3>
        <button class="ci-modal-close" onclick="closeDetailModal()">&times;</button>
    </div>
    <div class="ci-modal-body" id="detailContent"></div>
</div>
</div>

<!-- ===== TIMELINE MODAL ===== -->
<div class="ci-modal-bg" id="timelineModal">
<div class="ci-modal" style="max-width:600px">
    <div class="ci-modal-hdr">
        <h3><i class="fas fa-history"></i> <?php echo $lang==='th'?'Lifecycle Timeline':'Lifecycle Timeline'; ?></h3>
        <button class="ci-modal-close" onclick="closeTimelineModal()">&times;</button>
    </div>
    <div class="ci-modal-body">
        <div class="ci-fg">
            <label class="ci-label"><?php echo $lang==='th'?'ใส่ Barcode / รหัสขวด':'Enter Barcode / Bottle Code'; ?></label>
            <div style="display:flex;gap:8px">
                <input type="text" id="timelineBarcode" class="ci-input" placeholder="e.g. 320F6600000001">
                <button onclick="loadTimeline()" class="ci-btn ci-btn-primary"><i class="fas fa-search"></i></button>
            </div>
        </div>
        <div id="timelineContent"></div>
    </div>
</div>
</div>

<!-- ===== RETURN MODAL (quick) ===== -->
<div class="ci-modal-bg" id="returnModal">
<div class="ci-modal" style="max-width:480px">
    <div class="ci-modal-hdr">
        <h3><i class="fas fa-undo"></i> <?php echo $lang==='th'?'คืนสารเคมี':'Return Chemical'; ?></h3>
        <button class="ci-modal-close" onclick="closeReturnModal()">&times;</button>
    </div>
    <div class="ci-modal-body">
        <div id="returnInfo" style="margin-bottom:12px"></div>
        <div class="ci-fg">
            <label class="ci-label"><?php echo $lang==='th'?'ปริมาณที่คืน':'Return Quantity'; ?></label>
            <input type="number" id="returnQty" class="ci-input" step="0.01" min="0.01">
        </div>
        <div class="ci-fg">
            <label class="ci-label"><?php echo $lang==='th'?'สภาพ':'Condition'; ?></label>
            <select id="returnCondition" class="ci-select">
                <option value="good"><?php echo $lang==='th'?'ดี':'Good'; ?></option>
                <option value="partially_used"><?php echo $lang==='th'?'ใช้ไปบางส่วน':'Partially Used'; ?></option>
                <option value="contaminated"><?php echo $lang==='th'?'ปนเปื้อน':'Contaminated'; ?></option>
                <option value="damaged"><?php echo $lang==='th'?'ชำรุด':'Damaged'; ?></option>
            </select>
        </div>
        <div class="ci-fg">
            <label class="ci-label"><?php echo $lang==='th'?'หมายเหตุ':'Notes'; ?></label>
            <input type="text" id="returnNotes" class="ci-input" placeholder="<?php echo $lang==='th'?'หมายเหตุ (ถ้ามี)':'Notes (optional)'; ?>">
        </div>
        <input type="hidden" id="returnTxnId">
        <button onclick="submitReturn()" class="ci-btn ci-btn-primary ci-btn-block" style="margin-top:8px">
            <i class="fas fa-undo"></i> <?php echo $lang==='th'?'ยืนยันคืน':'Confirm Return'; ?>
        </button>
    </div>
</div>
</div>

<?php Layout::endContent(); ?>

<style>
/* ==================== PRO DASHBOARD ==================== */
.txn-dash-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px}
.txn-dash-loading{grid-column:1/-1;text-align:center;padding:40px 0}

/* Hero card — spans 2 cols for admin/manager */
.txn-dash-hero{grid-column:span 2;border-radius:14px;padding:20px 24px;position:relative;overflow:hidden;color:#fff;min-height:120px;display:flex;flex-direction:column;justify-content:space-between}
.txn-dash-hero::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(255,255,255,.08),transparent);pointer-events:none}
.txn-dash-hero.admin{background:linear-gradient(135deg,#1e293b 0%,#334155 100%)}
.txn-dash-hero.manager{background:linear-gradient(135deg,#1565c0 0%,#42a5f5 100%)}
.txn-dash-hero.user{background:linear-gradient(135deg,#059669 0%,#34d399 100%);grid-column:span 2}
.txn-dash-hero-top{display:flex;align-items:center;gap:14px}
.txn-dash-hero-icon{width:48px;height:48px;border-radius:12px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-size:20px;backdrop-filter:blur(4px);flex-shrink:0}
.txn-dash-hero-info h4{margin:0;font-size:16px;font-weight:700;letter-spacing:-.2px}
.txn-dash-hero-info p{margin:3px 0 0;font-size:12px;opacity:.8}
.txn-dash-hero-stats{display:flex;gap:20px;margin-top:14px}
.txn-dash-hero-stat{text-align:center}
.txn-dash-hero-stat .hval{font-size:22px;font-weight:800;line-height:1}
.txn-dash-hero-stat .hlbl{font-size:10px;opacity:.75;margin-top:2px;text-transform:uppercase;letter-spacing:.5px}
.txn-dash-hero-badge{position:absolute;top:14px;right:16px;font-size:9px;padding:3px 10px;border-radius:20px;background:rgba(255,255,255,.2);backdrop-filter:blur(4px);font-weight:600;letter-spacing:.3px}

/* Stat mini cards */
.txn-dash-card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:16px;display:flex;align-items:center;gap:14px;transition:all .2s;cursor:default;position:relative;overflow:hidden}
.txn-dash-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.06);transform:translateY(-1px)}
.txn-dash-card-icon{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0}
.txn-dash-card-icon.orange{background:linear-gradient(135deg,#fff7ed,#ffedd5);color:#ea580c}
.txn-dash-card-icon.purple{background:linear-gradient(135deg,#faf5ff,#f3e8ff);color:#7c3aed}
.txn-dash-card-icon.blue{background:linear-gradient(135deg,#eff6ff,#dbeafe);color:#2563eb}
.txn-dash-card-icon.green{background:linear-gradient(135deg,#f0fdf4,#dcfce7);color:#16a34a}
.txn-dash-card-icon.red{background:linear-gradient(135deg,#fef2f2,#fecaca);color:#dc2626}
.txn-dash-card-icon.amber{background:linear-gradient(135deg,#fffbeb,#fef3c7);color:#d97706}
.txn-dash-card-icon.teal{background:linear-gradient(135deg,#f0fdfa,#ccfbf1);color:#0d9488}
.txn-dash-card-icon.slate{background:linear-gradient(135deg,#f8fafc,#e2e8f0);color:#475569}
.txn-dash-card-info{flex:1;min-width:0}
.txn-dash-card-val{font-size:22px;font-weight:800;color:var(--c1);line-height:1}
.txn-dash-card-lbl{font-size:11px;color:var(--c3);margin-top:3px;font-weight:500}
.txn-dash-card-trend{position:absolute;right:14px;bottom:10px;font-size:10px;color:var(--c3);display:flex;align-items:center;gap:3px}
.txn-dash-card-trend i{font-size:8px}
.txn-dash-card-trend.up{color:#16a34a}
.txn-dash-card-trend.warn{color:#dc2626}

/* Alert badge on card */
.txn-dash-card .dash-alert{position:absolute;top:10px;right:12px;width:8px;height:8px;border-radius:50%;background:#ef4444;animation:dashPulse 2s infinite}
@keyframes dashPulse{0%,100%{opacity:1}50%{opacity:.4}}

/* Clickable cards */
.txn-dash-card.clickable{cursor:pointer}
.txn-dash-card.clickable:hover{border-color:var(--accent)}

/* ================== RESPONSIVE ================== */

/* ── XL → LG (≤1200px) ── */
@media(max-width:1200px){
    .txn-dash-grid{grid-template-columns:repeat(2,1fr);gap:10px}
    .txn-dash-hero{grid-column:span 2}
    .txn-dash-hero.user{grid-column:span 2}
}

/* ── LG → MD (≤900px) ── */
@media(max-width:900px){
    .txn-dash-grid{grid-template-columns:repeat(2,1fr);gap:10px}
    .txn-dash-hero{grid-column:span 2}
    .txn-dash-hero-stats{gap:14px;flex-wrap:wrap}
    .txn-dash-hero-stat .hval{font-size:20px}
    .txn-dash-card-val{font-size:20px}
    .txn-dash-card-trend{display:none}
}

/* ── MD → SM (≤768px) ── */
@media(max-width:768px){
    /* Dashboard */
    .txn-dash-grid{grid-template-columns:1fr 1fr;gap:8px}
    .txn-dash-hero{grid-column:span 2;padding:16px 18px;min-height:auto}
    .txn-dash-hero-stats{gap:12px}
    .txn-dash-hero-stat .hval{font-size:18px}
    .txn-dash-hero-stat .hlbl{font-size:9px}
    .txn-dash-card{padding:12px;gap:10px}
    .txn-dash-card-icon{width:38px;height:38px;font-size:15px}
    .txn-dash-card-val{font-size:20px}

    /* Tabs — horizontal scroll */
    .ci-tabs{display:flex;overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:none;gap:0;flex-wrap:nowrap!important;padding-bottom:2px}
    .ci-tabs::-webkit-scrollbar{display:none}
    .ci-tab{flex-shrink:0;white-space:nowrap;font-size:12px;padding:8px 14px}

    /* Filter bar — stack */
    .ci-filter-bar{flex-direction:column!important;gap:8px!important}
    .ci-filter-bar>div,.ci-filter-bar>select{flex:unset!important;min-width:unset!important;width:100%}

    /* Transaction cards */
    .txn-card-body{flex-direction:column;gap:6px;padding:12px 14px 12px 18px}
    .txn-card-left{overflow:visible;flex-direction:row;gap:10px;width:100%}
    .txn-card-left>div:last-child{flex:1;min-width:0}
    .txn-card-right{display:flex;align-items:center;justify-content:space-between;text-align:left;min-width:0;width:100%;padding-top:6px;border-top:1px solid #f3f4f6;gap:8px}
    .txn-card-chem{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%}
    .txn-card-txnno{font-size:11px}
    .txn-card-meta{gap:6px}
    .txn-card-meta span{font-size:10px}
    .txn-card-actions{padding:8px 12px;gap:6px}
    .txn-card-actions .ci-btn{font-size:11px;padding:6px 10px;flex:1;justify-content:center;text-align:center}
    .txn-type-icon{width:36px;height:36px;min-width:36px;font-size:14px}
    .txn-status-check{width:16px;height:16px;font-size:7px}
    .txn-pro-badge{font-size:9px;padding:3px 8px}
    .txn-lifecycle{margin-top:4px}

    /* Modals */
    .txn-modal-pro{border-radius:16px 16px 0 0;max-width:100%;max-height:92vh;min-height:auto;margin-top:auto}
    .scan-modal-pro{border-radius:16px 16px 0 0;max-width:100%}
    .txn-modal-hdr{padding:16px 18px 12px}
    .txn-modal-hdr h3{font-size:16px}
    .txn-hdr-icon{width:38px;height:38px;font-size:17px}
    .ci-modal-body{padding:16px!important}
    .txn-submit-section{flex-direction:column}
    .txn-submit-section .ci-btn-secondary{width:100%}
    .mode-chooser-grid{grid-template-columns:repeat(2,1fr)}

    /* Disposal cards */
    .disp-card>div:first-child{flex-direction:column!important;gap:6px}
    .disp-card>div:first-child>div:last-child{align-self:flex-start}
}

/* ── SM → XS (≤480px) ── */
@media(max-width:480px){
    .txn-dash-grid{grid-template-columns:1fr 1fr;gap:6px}
    .txn-dash-hero{padding:14px;grid-column:span 2}
    .txn-dash-hero-icon{width:36px;height:36px;font-size:15px;border-radius:10px}
    .txn-dash-hero-info h4{font-size:13px}
    .txn-dash-hero-info p{font-size:10px}
    .txn-dash-hero-badge{font-size:8px;padding:2px 8px;top:10px;right:10px}
    .txn-dash-hero-stats{gap:10px;margin-top:10px}
    .txn-dash-hero-stat .hval{font-size:16px}
    .txn-dash-hero-stat .hlbl{font-size:8px;letter-spacing:.3px}
    .txn-dash-card{padding:10px;gap:8px;border-radius:10px}
    .txn-dash-card-icon{width:34px;height:34px;font-size:13px;border-radius:8px}
    .txn-dash-card-val{font-size:17px}
    .txn-dash-card-lbl{font-size:10px}
    .txn-dash-card .dash-alert{width:6px;height:6px;top:6px;right:8px}

    /* Type icon in txn card */
    .txn-type-icon{width:32px;height:32px;min-width:32px;font-size:13px;border-radius:7px}
    .txn-status-check{width:14px;height:14px;font-size:6px;bottom:-2px;right:-2px}
    .txn-pro-badge{font-size:8px;padding:2px 6px;gap:3px}
    .txn-pro-badge i{font-size:7px}
    .txn-card-chem{font-size:12px}
    .txn-card-txnno{font-size:10px}
    .txn-card-meta{gap:4px}
    .txn-card-meta span{font-size:9px}
    .txn-card-meta span i{font-size:8px;width:10px}
    .txn-lifecycle{display:none}

    /* Stepper */
    .txn-stepper{padding:10px 14px 0}
    .txn-step span{display:none}
    .txn-step-dot{width:22px;height:22px;font-size:10px}
    .txn-step-line{width:20px;margin:0 4px}

    /* Modal forms */
    .dispose-reason-grid{grid-template-columns:repeat(2,1fr)}
    .use-quick-grid{grid-template-columns:repeat(2,1fr)}
    .txn-own-notice{padding:14px}
    .txn-own-notice-icon{width:44px;height:44px;font-size:18px}
    .txn-own-notice-title{font-size:14px}
    .txn-own-notice-desc{font-size:11px}
    .txn-search-input{font-size:13px;padding:10px 0}
    .txn-search-input::placeholder{font-size:12px}
    .txn-qty-input{font-size:14px;padding:8px 12px}
    .txn-qty-unit{padding:8px 12px;font-size:12px}

    /* Selected item pro card */
    .txn-selected-pro{padding:12px}
    .txn-sel-name{font-size:13px}
    .txn-sel-chips{gap:4px}
    .txn-sel-chip{font-size:10px;padding:3px 8px}

    /* Scan modal camera */
    .scan-camera-wrap{min-height:160px}
    .scan-manual-row{flex-direction:column;gap:6px}
    .scan-go-btn{width:100%;height:40px;border-radius:8px!important}
    .scan-result-actions{gap:6px}
    .scan-action-sec{flex-direction:column}
    .scan-action-sec .scan-action-btn{flex:unset;width:100%}
    .scan-result-chips{gap:4px}
    .scan-result-chip{font-size:10px;padding:3px 8px}

    /* Mode chooser */
    .mode-chooser-grid{grid-template-columns:1fr 1fr;gap:6px}
    .mode-opt-card{padding:14px 8px}
    .mode-opt-card .mode-opt-icon{width:38px;height:38px;font-size:16px}
    .mode-opt-card .mode-opt-label{font-size:12px}
    .mode-opt-card .mode-opt-desc{font-size:9px}
}

/* ==================== ACTION BAR ==================== */
.txn-action-bar{display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap}
@media(max-width:768px){
    .txn-action-bar{gap:6px}
    .txn-action-bar .ci-btn{font-size:12px;padding:8px 12px;flex:1 1 auto;min-width:calc(50% - 6px);justify-content:center;text-align:center;display:inline-flex;align-items:center;gap:5px}
}
@media(max-width:480px){
    .txn-action-bar .ci-btn{font-size:11px;padding:7px 8px;min-width:calc(50% - 4px)}
    .txn-action-bar .ci-btn i{font-size:11px}
}

/* ==================== PRO MODAL ==================== */
.txn-modal-pro{max-width:600px;border-radius:16px;overflow:hidden;box-shadow:0 25px 60px rgba(0,0,0,.18);display:flex;flex-direction:column;min-height:420px}
.txn-modal-pro>.ci-modal-body{flex:1;min-height:0;overflow-y:auto}

/* Gradient header */
.txn-modal-hdr{padding:20px 24px 16px;position:relative;display:flex;justify-content:space-between;align-items:flex-start}
.txn-modal-hdr.mode-borrow{background:linear-gradient(135deg,#e65100 0%,#ff8f00 100%)}
.txn-modal-hdr.mode-transfer{background:linear-gradient(135deg,#1565c0 0%,#42a5f5 100%)}
.txn-modal-hdr.mode-use{background:linear-gradient(135deg,#6d28d9 0%,#a78bfa 100%)}
.txn-modal-hdr.mode-dispose{background:linear-gradient(135deg,#b71c1c 0%,#e53935 100%)}
.txn-hdr-content{display:flex;gap:14px;align-items:center}
.txn-hdr-icon{width:44px;height:44px;background:rgba(255,255,255,.2);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;color:#fff;backdrop-filter:blur(4px)}
.txn-modal-hdr h3{margin:0;font-size:18px;font-weight:700;color:#fff;letter-spacing:-.2px}
.txn-hdr-sub{margin:3px 0 0;font-size:12px;color:rgba(255,255,255,.8);font-weight:400}
.txn-modal-close{background:rgba(255,255,255,.15);border:none;color:#fff;width:32px;height:32px;border-radius:8px;cursor:pointer;font-size:18px;display:flex;align-items:center;justify-content:center;transition:background .15s;flex-shrink:0}
.txn-modal-close:hover{background:rgba(255,255,255,.3)}

/* Stepper */
.txn-stepper{display:flex;align-items:center;justify-content:center;padding:16px 24px 0;gap:0}
.txn-step{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--c3);font-weight:500;transition:color .25s}
.txn-step.active{color:var(--accent)}
.txn-step.active .txn-step-dot{background:var(--accent);color:#fff;box-shadow:0 2px 8px rgba(76,175,80,.3)}
.txn-step.done{color:var(--accent)}
.txn-step.done .txn-step-dot{background:var(--accent);color:#fff}
.txn-step-dot{width:26px;height:26px;border-radius:50%;border:2px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:var(--c3);transition:all .25s;flex-shrink:0;background:var(--card)}
.txn-step-line{width:40px;height:2px;background:var(--border);margin:0 6px;border-radius:1px;transition:background .25s}
.txn-step-line.done{background:var(--accent)}

/* Search box pro */
.txn-search-box{position:relative;display:flex;align-items:center;background:var(--input-bg);border:2px solid var(--border);border-radius:12px;padding:0 14px;transition:border-color .2s,box-shadow .2s;margin-bottom:12px}
.txn-search-box:focus-within{border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-l)}
.txn-search-icon{color:var(--c3);font-size:14px;flex-shrink:0;margin-right:10px}
.txn-search-input{border:none;background:transparent;padding:12px 0;font-size:14px;color:var(--c1);width:100%;outline:none}
.txn-search-input::placeholder{color:var(--c3);font-size:13px}
.txn-search-shortcut{flex-shrink:0;margin-left:8px}
.txn-search-shortcut kbd{font-size:10px;padding:2px 6px;background:var(--border);border-radius:4px;color:var(--c3);font-family:inherit}

/* Search guide / empty state */
.txn-search-guide{text-align:center;padding:32px 16px;color:var(--c3)}
.txn-guide-icon{width:56px;height:56px;margin:0 auto 12px;background:linear-gradient(135deg,var(--accent-l),#e8f5e9);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:22px;color:var(--accent)}
.txn-search-guide p{font-size:13px;margin:0 0 14px}
.txn-guide-tips{display:flex;justify-content:center;gap:16px;flex-wrap:wrap}
.txn-guide-tips span{font-size:11px;display:flex;align-items:center;gap:4px;color:var(--c3);background:var(--input-bg);padding:4px 10px;border-radius:20px;border:1px solid var(--border)}
.txn-guide-tips span i{font-size:10px;color:var(--accent)}

/* Search dropdown */
.txn-search-results{position:absolute;top:calc(100% + 4px);left:0;right:0;background:#fff;border:1px solid var(--border);border-radius:12px;max-height:280px;overflow-y:auto;z-index:10;box-shadow:0 12px 32px rgba(0,0,0,.12)}
.txn-sr-item{padding:12px 16px;cursor:pointer;border-bottom:1px solid #f5f5f5;transition:all .12s;display:flex;gap:12px;align-items:center}
.txn-sr-item:first-child{border-radius:12px 12px 0 0}
.txn-sr-item:last-child{border-bottom:none;border-radius:0 0 12px 12px}
.txn-sr-item:hover{background:var(--accent-l)}
.txn-sr-icon{width:34px;height:34px;border-radius:8px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;color:var(--accent);font-size:13px;flex-shrink:0}
.txn-sr-info{flex:1;min-width:0}
.txn-sr-name{font-size:13px;font-weight:600;color:var(--c1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.txn-sr-meta{font-size:11px;color:var(--c3);margin-top:2px;display:flex;gap:10px;flex-wrap:wrap}
.txn-sr-meta span{display:flex;align-items:center;gap:3px}
.txn-sr-qty{font-size:12px;font-weight:600;color:var(--accent);white-space:nowrap;flex-shrink:0}

/* Owned-item indicator in search results */
.txn-sr-item.is-own{background:linear-gradient(135deg,#faf5ff,#f5f3ff);border-left:3px solid #7c3aed}
.txn-sr-item.is-own:hover{background:linear-gradient(135deg,#f3e8ff,#ede9fe)}
.txn-sr-item.is-own .txn-sr-icon{background:#ede9fe;color:#7c3aed}
.txn-sr-own-badge{display:inline-flex;align-items:center;gap:3px;font-size:9px;font-weight:700;color:#7c3aed;background:#ede9fe;padding:1px 7px;border-radius:10px;white-space:nowrap;letter-spacing:.3px;line-height:1.6}
.txn-sr-own-badge i{font-size:8px}

/* Others-item indicator in search results (manager/admin transfer) */
.txn-sr-item.is-others-transfer{background:linear-gradient(135deg,#fffbeb,#fef3c7);border-left:3px solid #f59e0b}
.txn-sr-item.is-others-transfer:hover{background:linear-gradient(135deg,#fef3c7,#fde68a)}
.txn-sr-item.is-others-transfer .txn-sr-icon{background:#fef3c7;color:#b45309}
.txn-sr-others-badge{display:inline-flex;align-items:center;gap:3px;font-size:9px;font-weight:700;color:#b45309;background:#fef3c7;padding:1px 7px;border-radius:10px;white-space:nowrap;letter-spacing:.3px;line-height:1.6}
.txn-sr-others-badge i{font-size:8px}

/* Ownership notice card (in borrow mode when selecting own item) */
.txn-own-notice{background:linear-gradient(135deg,#faf5ff,#f5f3ff);border:2px solid #c4b5fd;border-radius:12px;padding:18px;position:relative;animation:slideUp .25s ease;text-align:center}
.txn-own-notice-icon{width:52px;height:52px;margin:0 auto 12px;background:linear-gradient(135deg,#ede9fe,#ddd6fe);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:22px;color:#7c3aed}
.txn-own-notice-title{font-size:15px;font-weight:700;color:#5b21b6;margin-bottom:6px}
.txn-own-notice-desc{font-size:12px;color:#6b7280;line-height:1.5;margin-bottom:16px}
.txn-own-notice-chem{display:inline-flex;align-items:center;gap:6px;background:#fff;border:1px solid #ddd6fe;border-radius:8px;padding:6px 12px;font-size:12px;font-weight:600;color:var(--c1);margin-bottom:16px}
.txn-own-notice-chem i{color:#7c3aed;font-size:11px}
.txn-own-notice-actions{display:flex;flex-direction:column;gap:8px}
.txn-own-notice-btn{display:flex;align-items:center;justify-content:center;gap:8px;padding:11px 18px;border:none;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;transition:all .2s}
.txn-own-notice-btn.primary{background:linear-gradient(135deg,#6d28d9,#a78bfa);color:#fff;box-shadow:0 4px 14px rgba(109,40,217,.25)}
.txn-own-notice-btn.primary:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(109,40,217,.35)}
.txn-own-notice-btn.secondary{background:#fff;color:#6b7280;border:1px solid var(--border)}
.txn-own-notice-btn.secondary:hover{background:#f9fafb;color:var(--c1)}

/* Transfer on-behalf notice */
.txn-own-notice.transfer-notice{background:linear-gradient(135deg,#fffbeb,#fef3c7);border-color:#fbbf24}
.txn-own-notice-icon.transfer-icon{background:linear-gradient(135deg,#fef3c7,#fde68a);color:#b45309}
.txn-own-notice-btn.transfer-primary{background:linear-gradient(135deg,#d97706,#f59e0b);color:#fff;box-shadow:0 4px 14px rgba(217,119,6,.25)}
.txn-own-notice-btn.transfer-primary:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(217,119,6,.35)}

/* Selected item — pro card */
.txn-selected-pro{background:linear-gradient(135deg,#f0fdf4,#ecfdf5);border:2px solid var(--accent);border-radius:12px;padding:16px;position:relative;animation:slideUp .25s ease}
@keyframes slideUp{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
.txn-sel-hdr{display:flex;justify-content:space-between;align-items:flex-start;gap:8px}
.txn-sel-badge{font-size:10px;padding:2px 8px;border-radius:10px;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.txn-sel-badge.stock{background:#dbeafe;color:#1e40af}
.txn-sel-badge.container{background:#f3e8ff;color:#6b21a8}
.txn-sel-name{font-size:15px;font-weight:700;color:var(--c1);margin:8px 0 4px;line-height:1.35}
.txn-sel-cas{font-size:12px;color:var(--c3);font-family:'Courier New',monospace}
.txn-sel-chips{display:flex;flex-wrap:wrap;gap:6px;margin-top:10px}
.txn-sel-chip{font-size:11px;padding:4px 10px;border-radius:20px;background:#fff;border:1px solid var(--border);color:var(--c2);display:flex;align-items:center;gap:4px}
.txn-sel-chip i{font-size:10px;color:var(--c3)}
.txn-sel-remove-btn{position:absolute;top:10px;right:10px;background:#fff;border:1px solid var(--border);color:var(--c3);cursor:pointer;font-size:11px;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;transition:all .15s}
.txn-sel-remove-btn:hover{background:#fef2f2;border-color:#fca5a5;color:#dc2626}
.txn-sel-action{display:flex;align-items:center;gap:6px;margin-top:12px;padding-top:10px;border-top:1px dashed var(--accent);color:var(--accent);font-size:12px;font-weight:600}
.txn-sel-action i{font-size:10px}

/* Mini card (step 2 summary) */
.txn-mini-card{display:flex;align-items:center;gap:10px;background:var(--input-bg);border:1px solid var(--border);border-radius:10px;padding:10px 14px;margin-bottom:18px;cursor:pointer;transition:background .15s}
.txn-mini-card:hover{background:#f0fdf4}
.txn-mini-card-icon{width:32px;height:32px;border-radius:8px;background:var(--accent-l);display:flex;align-items:center;justify-content:center;color:var(--accent);font-size:13px;flex-shrink:0}
.txn-mini-card-info{flex:1;min-width:0}
.txn-mini-card-name{font-size:13px;font-weight:600;color:var(--c1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.txn-mini-card-meta{font-size:11px;color:var(--c3)}
.txn-mini-card-change{font-size:10px;color:var(--accent);font-weight:500}

/* Form sections */
.txn-form-section{margin-bottom:18px}
.txn-form-label{display:flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:var(--c1);margin-bottom:8px}
.txn-form-label i{font-size:12px;color:var(--accent);width:16px;text-align:center}

/* Quantity row pro */
.txn-qty-row{display:flex;gap:0}
.txn-qty-input-wrap{display:flex;flex:1;border:2px solid var(--border);border-radius:10px;overflow:hidden;transition:border-color .2s}
.txn-qty-input-wrap:focus-within{border-color:var(--accent)}
.txn-qty-input{border:none;padding:10px 14px;font-size:16px;font-weight:600;color:var(--c1);width:100%;outline:none;background:var(--input-bg)}
.txn-qty-input::placeholder{font-weight:400;color:var(--c3)}
.txn-qty-unit{padding:10px 16px;background:#f8fafc;border-left:1px solid var(--border);font-size:13px;font-weight:600;color:var(--c2);display:flex;align-items:center;white-space:nowrap}

/* Quantity progress bar */
.txn-qty-bar-wrap{margin-top:8px}
.txn-qty-bar{height:6px;background:#e5e7eb;border-radius:3px;overflow:hidden}
.txn-qty-bar-fill{height:100%;border-radius:3px;background:linear-gradient(90deg,var(--accent),#66bb6a);transition:width .3s ease;width:0}
.txn-qty-bar-fill.warn{background:linear-gradient(90deg,#f59e0b,#ef4444)}
.txn-qty-bar-fill.danger{background:#ef4444}
.txn-qty-info{display:flex;justify-content:space-between;margin-top:4px}
.txn-qty-hint{font-size:11px;color:var(--c3)}
.txn-qty-max{font-size:11px;color:var(--c3);font-weight:500}

/* Submit section */
.txn-submit-section{display:flex;gap:10px;margin-top:20px;padding-top:16px;border-top:1px solid var(--border)}
.txn-submit-section .ci-btn-secondary{flex-shrink:0}
.txn-submit-btn{flex:1;display:flex;align-items:center;justify-content:center;gap:8px;padding:12px 20px;border:none;border-radius:10px;font-size:14px;font-weight:600;color:#fff;cursor:pointer;transition:all .2s;position:relative;overflow:hidden}
.txn-submit-btn::before{content:'';position:absolute;inset:0;background:linear-gradient(rgba(255,255,255,.1),transparent);pointer-events:none}
.txn-submit-btn.mode-borrow{background:linear-gradient(135deg,#e65100,#ff8f00)}
.txn-submit-btn.mode-use{background:linear-gradient(135deg,#6d28d9,#a78bfa)}
.txn-submit-btn.mode-transfer{background:linear-gradient(135deg,#1565c0,#42a5f5)}
.txn-submit-btn.mode-dispose{background:linear-gradient(135deg,#b71c1c,#e53935)}
.txn-submit-btn:hover{transform:translateY(-1px);box-shadow:0 4px 14px rgba(0,0,0,.2)}
.txn-submit-btn:active{transform:translateY(0)}

/* ═══ Transaction Card List — Pro Status Design ═══ */
.txn-card{background:var(--card);border:1px solid var(--border);border-radius:12px;margin-bottom:10px;transition:all .18s;cursor:pointer;position:relative;overflow:hidden}
.txn-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.07);transform:translateY(-1px)}

/* ── Left accent stripe by status ── */
.txn-card::before{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;border-radius:12px 0 0 12px;transition:width .15s}
.txn-card.st-completed::before{background:linear-gradient(180deg,#059669,#34d399)}
.txn-card.st-pending::before{background:linear-gradient(180deg,#f59e0b,#fbbf24)}
.txn-card.st-approved::before{background:linear-gradient(180deg,#2563eb,#60a5fa)}
.txn-card.st-rejected::before{background:linear-gradient(180deg,#dc2626,#f87171)}
.txn-card.st-cancelled::before{background:linear-gradient(180deg,#9ca3af,#d1d5db)}
.txn-card.st-overdue::before{background:linear-gradient(180deg,#dc2626,#f59e0b);width:5px}

/* Subtle completed card tint */
.txn-card.st-completed{background:linear-gradient(135deg,#f0fdf4 0%,var(--card) 40%)}
.txn-card.st-rejected{background:linear-gradient(135deg,#fef2f2 0%,var(--card) 40%)}
.txn-card.st-cancelled{background:linear-gradient(135deg,#f9fafb 0%,var(--card) 40%);opacity:.75}
.txn-card.st-cancelled:hover{opacity:1}

.txn-card-body{padding:14px 16px 14px 20px;display:flex;justify-content:space-between;gap:12px;align-items:flex-start}
.txn-card-left{display:flex;gap:12px;align-items:flex-start;flex:1;min-width:0;overflow:hidden}
.txn-card-left>div:last-child{flex:1;min-width:0}
.txn-card-right{flex-shrink:0;text-align:right;min-width:90px;display:flex;flex-direction:column;align-items:flex-end;gap:6px}
.txn-card-chem{font-weight:600;font-size:13px;color:var(--c1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%}
.txn-card-txnno{font-size:12px;color:var(--c2);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.txn-card-meta{display:flex;gap:10px;flex-wrap:wrap;margin-top:5px;font-size:11px;color:var(--c3)}
.txn-card-meta span{display:inline-flex;align-items:center;gap:3px;white-space:nowrap}
.txn-card-meta span i{font-size:10px;width:12px;text-align:center;flex-shrink:0}
.txn-card-actions{padding:8px 16px;border-top:1px solid #f0f0f0;display:flex;gap:8px;flex-wrap:wrap}

/* ── Type icon with status ring ── */
.txn-type-icon-wrap{position:relative;flex-shrink:0}
.txn-type-icon{width:42px;height:42px;min-width:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;position:relative;z-index:1;transition:all .15s}
.txn-type-icon.borrow{background:#fff3e0;color:#e65100}
.txn-type-icon.use{background:#f3e8ff;color:#7c3aed}
.txn-type-icon.return{background:#e8f5e9;color:#2e7d32}
.txn-type-icon.transfer{background:#e3f2fd;color:#1565c0}
.txn-type-icon.dispose{background:#fce4ec;color:#c62828}

/* Status check overlay on icon */
.txn-status-check{position:absolute;bottom:-3px;right:-3px;width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:8px;font-weight:900;border:2px solid var(--card);z-index:2;transition:transform .15s}
.txn-card:hover .txn-status-check{transform:scale(1.15)}
.txn-status-check.done{background:linear-gradient(135deg,#059669,#10b981);color:#fff}
.txn-status-check.wait{background:linear-gradient(135deg,#f59e0b,#fbbf24);color:#fff;animation:txnPulse 1.8s ease-in-out infinite}
.txn-status-check.fail{background:linear-gradient(135deg,#dc2626,#ef4444);color:#fff}
.txn-status-check.draft{background:linear-gradient(135deg,#6b7280,#9ca3af);color:#fff}
.txn-status-check.info{background:linear-gradient(135deg,#2563eb,#3b82f6);color:#fff}
.txn-status-check.warn{background:linear-gradient(135deg,#dc2626,#f59e0b);color:#fff;animation:txnPulse 1.2s ease-in-out infinite}

@keyframes txnPulse{0%,100%{box-shadow:0 0 0 0 rgba(245,158,11,.4)}50%{box-shadow:0 0 0 4px rgba(245,158,11,.0)}}

/* ── Pro Status Badge ── */
.txn-pro-badge{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:8px;font-size:10px;font-weight:700;letter-spacing:.02em;white-space:nowrap}
.txn-pro-badge i{font-size:9px}
.txn-pro-badge.s-completed{background:linear-gradient(135deg,#dcfce7,#bbf7d0);color:#15803d;border:1px solid #86efac}
.txn-pro-badge.s-pending{background:linear-gradient(135deg,#fef9c3,#fef08a);color:#a16207;border:1px solid #fde047}
.txn-pro-badge.s-approved{background:linear-gradient(135deg,#dbeafe,#bfdbfe);color:#1d4ed8;border:1px solid #93c5fd}
.txn-pro-badge.s-rejected{background:linear-gradient(135deg,#fee2e2,#fecaca);color:#b91c1c;border:1px solid #fca5a5}
.txn-pro-badge.s-cancelled{background:linear-gradient(135deg,#f3f4f6,#e5e7eb);color:#6b7280;border:1px solid #d1d5db;text-decoration:line-through}
.txn-pro-badge.s-overdue{background:linear-gradient(135deg,#fee2e2,#fef3c7);color:#b91c1c;border:1px solid #fca5a5;animation:txnPulse 1.2s ease-in-out infinite}

/* ── Lifecycle progress bar (borrow cards) ── */
.txn-lifecycle{display:flex;align-items:center;gap:3px;margin-top:6px}
.txn-lc-step{height:3px;border-radius:2px;flex:1;background:#e5e7eb;position:relative;transition:background .2s}
.txn-lc-step.active{background:var(--accent)}
.txn-lc-step.warn{background:#f59e0b}
.txn-lc-step.danger{background:#ef4444}
.txn-lc-label{font-size:9px;color:var(--c3);font-weight:500;white-space:nowrap;margin-left:4px}

/* Timeline */
.tl-item{position:relative;padding-left:28px;margin-bottom:16px}
.tl-item::before{content:'';position:absolute;left:8px;top:22px;bottom:-16px;width:2px;background:#e0e0e0}
.tl-item:last-child::before{display:none}
.tl-dot{position:absolute;left:0;top:4px;width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:8px;color:#fff}
.tl-dot.borrow{background:#e65100}.tl-dot.use{background:#7c3aed}.tl-dot.return{background:#2e7d32}.tl-dot.transfer{background:#1565c0}
.tl-dot.dispose{background:#c62828}.tl-dot.receive{background:#6a1b9a}.tl-dot.adjust{background:#795548}

/* ========== USE MODE STYLES ========== */
.use-info-box{display:flex;gap:12px;align-items:start;background:linear-gradient(135deg,#f5f3ff,#ede9fe);border:1px solid #c4b5fd;border-radius:10px;padding:14px;margin-bottom:16px}
.use-info-icon{width:36px;height:36px;border-radius:50%;background:#ddd6fe;display:flex;align-items:center;justify-content:center;color:#7c3aed;font-size:16px;flex-shrink:0}
.use-quick-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:6px}
.use-quick-btn{display:flex;flex-direction:column;align-items:center;gap:2px;padding:10px 6px;border:2px solid var(--border);border-radius:10px;background:var(--card);cursor:pointer;transition:all .15s;text-align:center}
.use-quick-btn:hover{border-color:#a78bfa;background:#faf5ff}
.use-quick-btn.active{border-color:#7c3aed;background:#f5f3ff;box-shadow:0 0 0 3px rgba(124,58,237,.1)}
.use-quick-pct{font-size:14px;font-weight:700;color:var(--c1)}
.use-quick-val{font-size:10px;color:var(--c3);font-weight:500}
.use-quick-all{border-color:#e9d5ff}
.use-quick-all .use-quick-pct{color:#7c3aed}
.use-preview{background:linear-gradient(135deg,#faf5ff,#f5f3ff);border:1px solid #ddd6fe;border-radius:10px;padding:14px;margin-bottom:4px}
.use-preview-row{display:flex;justify-content:space-between;font-size:12px;padding:4px 0;border-bottom:1px dashed #e9d5ff}
.use-preview-row:last-child{border-bottom:none}
.use-preview-row .up-label{color:var(--c3)}
.use-preview-row .up-val{font-weight:600;color:var(--c1)}
.use-preview-row .up-val.purple{color:#7c3aed}

/* Use mode themed search box */
.txn-search-box.use-theme:focus-within{border-color:#7c3aed;box-shadow:0 0 0 3px rgba(124,58,237,.12)}
.txn-search-box.use-theme .txn-search-icon{color:#7c3aed}

/* Use mode themed selected card */
.txn-selected-pro.use-theme{background:linear-gradient(135deg,#faf5ff,#f5f3ff);border-color:#7c3aed}
.txn-selected-pro.use-theme .txn-sel-action{border-top-color:#c4b5fd;color:#7c3aed}

/* Use mode themed stepper */
.txn-stepper.use-theme .txn-step.active{color:#7c3aed}
.txn-stepper.use-theme .txn-step.active .txn-step-dot{background:#7c3aed;box-shadow:0 2px 8px rgba(124,58,237,.3)}
.txn-stepper.use-theme .txn-step.done{color:#7c3aed}
.txn-stepper.use-theme .txn-step.done .txn-step-dot{background:#7c3aed}
.txn-stepper.use-theme .txn-step-line.done{background:#7c3aed}

/* Use mode themed mini card */
.txn-mini-card.use-theme:hover{background:#faf5ff}
.txn-mini-card.use-theme .txn-mini-card-icon{background:#ede9fe;color:#7c3aed}
.txn-mini-card.use-theme .txn-mini-card-change{color:#7c3aed}

/* Use mode themed qty bar */
.txn-qty-bar-fill.use-fill{background:linear-gradient(90deg,#7c3aed,#a78bfa)}
.txn-qty-bar-fill.use-fill.warn{background:linear-gradient(90deg,#f59e0b,#ef4444)}
.txn-qty-bar-fill.use-fill.danger{background:#ef4444}

/* Use mode themed qty input */
.txn-qty-input-wrap.use-theme:focus-within{border-color:#7c3aed}

/* Search guide use theme */
.txn-search-guide.use-theme .txn-guide-icon{background:linear-gradient(135deg,#ede9fe,#f5f3ff);color:#7c3aed}
.txn-search-guide.use-theme .txn-guide-tips span i{color:#7c3aed}

/* Search result use icon */
.txn-sr-icon.use-icon{background:#ede9fe;color:#7c3aed}

/* ========== SCAN BARCODE ========== */
.scan-btn-glow{background:linear-gradient(135deg,#059669,#10b981)!important;color:#fff!important;border:none!important;position:relative;overflow:hidden}
.scan-btn-glow::after{content:'';position:absolute;inset:0;background:linear-gradient(90deg,transparent,rgba(255,255,255,.2),transparent);animation:scanGlow 2.5s infinite}
@keyframes scanGlow{0%{transform:translateX(-100%)}100%{transform:translateX(100%)}}
.scan-btn-glow:hover{box-shadow:0 4px 16px rgba(5,150,105,.35)}

/* Scan modal */
.scan-modal-pro{max-width:480px;border-radius:16px;overflow:hidden;box-shadow:0 25px 60px rgba(0,0,0,.18)}
.scan-modal-hdr{padding:20px 24px 16px;background:linear-gradient(135deg,#059669,#34d399);display:flex;justify-content:space-between;align-items:flex-start}
.scan-hdr-content{display:flex;gap:14px;align-items:center}
.scan-hdr-icon{width:44px;height:44px;background:rgba(255,255,255,.2);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;color:#fff;backdrop-filter:blur(4px)}
.scan-modal-hdr h3{margin:0;font-size:18px;font-weight:700;color:#fff}
.scan-hdr-sub{margin:3px 0 0;font-size:12px;color:rgba(255,255,255,.8);font-weight:400}

/* Camera area */
.scan-camera-wrap{position:relative;border-radius:12px;overflow:hidden;background:#111;min-height:200px;margin-bottom:12px}
.scan-camera-wrap video{width:100%;display:block;border-radius:12px}
.scan-camera-overlay{position:absolute;inset:0;pointer-events:none;display:flex;align-items:center;justify-content:center}
.scan-corner{position:absolute;width:28px;height:28px;border:3px solid #10b981}
.scan-corner.tl{top:20px;left:20px;border-right:none;border-bottom:none;border-radius:6px 0 0 0}
.scan-corner.tr{top:20px;right:20px;border-left:none;border-bottom:none;border-radius:0 6px 0 0}
.scan-corner.bl{bottom:20px;left:20px;border-right:none;border-top:none;border-radius:0 0 0 6px}
.scan-corner.br{bottom:20px;right:20px;border-left:none;border-top:none;border-radius:0 0 6px 0}
.scan-line{position:absolute;width:calc(100% - 56px);height:2px;background:linear-gradient(90deg,transparent,#10b981,transparent);animation:scanLine 2s infinite ease-in-out}
@keyframes scanLine{0%,100%{top:30px;opacity:0}50%{top:calc(100% - 30px);opacity:1}}
.scan-camera-error{text-align:center;padding:40px 20px;color:#aaa}
.scan-camera-error i{font-size:36px;margin-bottom:10px;display:block;color:#666}
.scan-camera-error p{font-size:13px;margin:0}
.scan-camera-actions{display:flex;gap:8px;justify-content:center;margin-bottom:16px}

/* Manual input */
.scan-manual-section{margin-bottom:16px}
.scan-manual-row{display:flex;gap:8px}
.scan-barcode-input{flex:1;font-family:'Courier New',monospace;font-size:15px;font-weight:600;letter-spacing:.5px}
.scan-go-btn{width:44px;height:44px;border-radius:10px!important;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}

/* Processing state */
.scan-processing{text-align:center;padding:24px}
.scan-processing p{font-size:13px;color:var(--c3);margin-top:8px}

/* Scan result card */
.scan-result-card{background:var(--card);border:1px solid var(--border);border-radius:12px;overflow:hidden;animation:slideUp .25s ease}
.scan-result-hdr{display:flex;align-items:center;gap:12px;padding:14px 16px;border-bottom:1px solid #f0f0f0}
.scan-result-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
.scan-result-icon.owner{background:#ede9fe;color:#7c3aed}
.scan-result-icon.other{background:#fff7ed;color:#ea580c}
.scan-result-icon.returnable{background:#ecfdf5;color:#059669}
.scan-result-body{padding:14px 16px}
.scan-result-name{font-size:15px;font-weight:700;color:var(--c1);margin-bottom:2px}
.scan-result-cas{font-size:12px;color:var(--c3);font-family:'Courier New',monospace}
.scan-result-chips{display:flex;flex-wrap:wrap;gap:6px;margin-top:10px}
.scan-result-chip{font-size:11px;padding:4px 10px;border-radius:20px;background:#f8fafc;border:1px solid var(--border);color:var(--c2);display:flex;align-items:center;gap:4px}
.scan-result-chip i{font-size:10px;color:var(--c3)}
.scan-result-badge{display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;padding:6px 14px;border-radius:20px;margin-top:12px}
.scan-result-badge.owner-badge{background:#f5f3ff;color:#6d28d9;border:1px solid #ddd6fe}
.scan-result-badge.other-badge{background:#fff7ed;color:#c2410c;border:1px solid #fed7aa}
.scan-result-badge.return-badge{background:#ecfdf5;color:#047857;border:1px solid #a7f3d0}
.scan-result-actions{padding:14px 16px;display:grid;gap:8px}
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

/* Mode chooser */
.mode-chooser-hdr{padding:18px 20px;background:linear-gradient(135deg,#1e293b,#334155);display:flex;justify-content:space-between;align-items:center}
.mode-chooser-hdr h3{margin:0;font-size:16px;font-weight:700;color:#fff}
.mode-chooser-info{display:flex;gap:10px;align-items:center;background:#f8fafc;border:1px solid var(--border);border-radius:10px;padding:10px 14px;margin-bottom:14px}
.mode-chooser-info-icon{width:36px;height:36px;border-radius:8px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;color:var(--accent);font-size:15px;flex-shrink:0}
.mode-chooser-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:8px}
.mode-opt-card{display:flex;flex-direction:column;align-items:center;gap:6px;padding:18px 10px;border:2px solid var(--border);border-radius:12px;cursor:pointer;transition:all .15s;background:var(--card);text-align:center}
.mode-opt-card:hover{border-color:var(--accent);transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,.08)}
.mode-opt-card .mode-opt-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:18px}
.mode-opt-card .mode-opt-label{font-size:13px;font-weight:600;color:var(--c1)}
.mode-opt-card .mode-opt-desc{font-size:10px;color:var(--c3);line-height:1.3}
.mode-opt-card.opt-use .mode-opt-icon{background:#f3e8ff;color:#7c3aed}
.mode-opt-card.opt-use:hover{border-color:#7c3aed}
.mode-opt-card.opt-borrow .mode-opt-icon{background:#fff3e0;color:#e65100}
.mode-opt-card.opt-borrow:hover{border-color:#e65100}
.mode-opt-card.opt-transfer .mode-opt-icon{background:#e3f2fd;color:#1565c0}
.mode-opt-card.opt-transfer:hover{border-color:#1565c0}
.mode-opt-card.opt-dispose .mode-opt-icon{background:#fce4ec;color:#c62828}
.mode-opt-card.opt-dispose:hover{border-color:#c62828}
.mode-opt-card.opt-return .mode-opt-icon{background:#ecfdf5;color:#059669}
.mode-opt-card.opt-return:hover{border-color:#059669}

/* In-modal scan button */
.txn-search-scan-btn{background:none;border:none;color:#059669;font-size:15px;cursor:pointer;padding:4px 8px;border-radius:6px;transition:all .15s;flex-shrink:0;display:flex;align-items:center}
.txn-search-scan-btn:hover{background:#ecfdf5;color:#047857}

/* Disposal bin card */
.disp-card{background:#fff5f5;border:1px solid #fecaca;border-radius:6px;padding:14px 16px;margin-bottom:10px}

/* Dispose modal enhanced */
.dispose-warning-box{display:flex;gap:12px;align-items:start;background:linear-gradient(135deg,#fff5f5,#fef2f2);border:1px solid #fecaca;border-radius:8px;padding:14px;margin-bottom:16px}
.dispose-warning-icon{width:36px;height:36px;border-radius:50%;background:#fecaca;display:flex;align-items:center;justify-content:center;color:#c62828;font-size:16px;flex-shrink:0}
.dispose-reason-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:6px;margin-top:6px}
.dispose-reason-opt input{display:none}
.dispose-reason-card{display:flex;flex-direction:column;align-items:center;gap:4px;padding:10px 6px;border:2px solid var(--border);border-radius:8px;cursor:pointer;transition:all .15s;text-align:center;font-size:11px;color:var(--c2)}
.dispose-reason-card i{font-size:18px;color:var(--c3);transition:color .15s}
.dispose-reason-opt input:checked + .dispose-reason-card{border-color:#c62828;background:#fff5f5;color:#c62828}
.dispose-reason-opt input:checked + .dispose-reason-card i{color:#c62828}
.dispose-reason-card:hover{border-color:#ef9a9a;background:#fafafa}
.dispose-preview{background:#f8f9fa;border:1px solid var(--border);border-radius:6px;padding:12px 14px;margin-top:12px}
.dispose-preview-row{display:flex;justify-content:space-between;font-size:12px;padding:3px 0;border-bottom:1px dashed #e0e0e0}
.dispose-preview-row:last-child{border-bottom:none}
.dispose-preview-row .dp-label{color:var(--c3)}
.dispose-preview-row .dp-val{font-weight:600;color:var(--c1)}

/* (responsive rules moved to unified responsive section above) */
</style>

<script>
const L = '<?php echo $lang; ?>';
const UID = <?php echo $userId; ?>;
const IS_ADMIN = <?php echo $isAdmin?'true':'false'; ?>;
const IS_MANAGER = <?php echo $isManager?'true':'false'; ?>;
const TH = L==='th';

const TXN_LABELS = {borrow:TH?'ยืม':'Borrow', use:TH?'ใช้':'Use', return:TH?'คืน':'Return', transfer:TH?'โอน':'Transfer', dispose:TH?'จำหน่าย':'Dispose', adjust:TH?'ปรับ':'Adjust', receive:TH?'รับเข้า':'Receive'};
const TXN_ICONS  = {borrow:'fa-hand-holding-medical', use:'fa-eye-dropper', return:'fa-undo', transfer:'fa-people-arrows', dispose:'fa-trash-alt', adjust:'fa-sliders-h', receive:'fa-box-open'};
const STATUS_MAP = {pending:['ci-badge-warning',TH?'รออนุมัติ':'Pending'], completed:['ci-badge-success',TH?'เสร็จสิ้น':'Completed'], rejected:['ci-badge-danger',TH?'ปฏิเสธ':'Rejected'], cancelled:['ci-badge-default',TH?'ยกเลิก':'Cancelled'], approved:['ci-badge-info',TH?'อนุมัติแล้ว':'Approved']};

let currentTab = 'all', currentPage = 1, searchTimer = null;
let txnMode = 'borrow'; // borrow, use, transfer, dispose
let selectedSource = null, selectedRecipient = null;

// ========== INIT ==========
loadDashboard();
loadList();

// Handle scan_action from QR Scanner page
(function handleScanAction() {
    const params = new URLSearchParams(window.location.search);
    const action = params.get('scan_action');
    if (!action) return;
    // Clean URL
    history.replaceState(null, '', window.location.pathname);
    try {
        const raw = sessionStorage.getItem('scanAction');
        if (!raw) return;
        const sd = JSON.parse(raw);
        sessionStorage.removeItem('scanAction');
        // Check freshness (within 30 seconds)
        if (Date.now() - sd.timestamp > 30000) return;
        if (sd.mode && sd.item) {
            setTimeout(() => {
                openNewTxn(sd.mode);
                setTimeout(() => autoSelectScannedItem(sd.item), 200);
            }, 400);
        }
    } catch(e) { console.error('scanAction error', e); }
})();

// ========== DASHBOARD ==========
async function loadDashboard() {
    const dash = document.getElementById('txnDashboard');
    try {
        const d = await apiFetch('/v1/api/borrow.php?action=dashboard');
        if (!d.success) { dash.innerHTML = ''; return; }
        const s = d.data;
        const rl = s.role_level || 0;
        const isAdm = rl >= 5;
        const isMgr = rl >= 3;
        let html = '';

        // ── Hero Card ──
        if (isAdm) {
            html += `
            <div class="txn-dash-hero admin">
                <div class="txn-dash-hero-badge"><i class="fas fa-shield-alt"></i> Admin</div>
                <div class="txn-dash-hero-top">
                    <div class="txn-dash-hero-icon"><i class="fas fa-shield-alt"></i></div>
                    <div class="txn-dash-hero-info">
                        <h4>${TH?'ภาพรวมระบบ':'System Overview'}</h4>
                        <p>${TH?'จัดการธุรกรรมสารเคมีทั้งหมดในระบบ':'Manage all chemical transactions in the system'}</p>
                    </div>
                </div>
                <div class="txn-dash-hero-stats">
                    <div class="txn-dash-hero-stat"><div class="hval">${num(s.total_transactions)}</div><div class="hlbl">${TH?'ธุรกรรมทั้งหมด':'Total Txns'}</div></div>
                    <div class="txn-dash-hero-stat"><div class="hval">${num(s.total_active_borrows)}</div><div class="hlbl">${TH?'กำลังยืม (ทั้งระบบ)':'Active Borrows'}</div></div>
                    <div class="txn-dash-hero-stat"><div class="hval">${num(s.total_users)}</div><div class="hlbl">${TH?'ผู้ใช้งาน':'Users'}</div></div>
                    <div class="txn-dash-hero-stat"><div class="hval">${num(s.total_chemicals)}</div><div class="hlbl">${TH?'สารเคมี':'Chemicals'}</div></div>
                </div>
            </div>`;
        } else if (isMgr) {
            html += `
            <div class="txn-dash-hero manager">
                <div class="txn-dash-hero-badge"><i class="fas fa-user-tie"></i> ${TH?'ผู้จัดการ':'Manager'}</div>
                <div class="txn-dash-hero-top">
                    <div class="txn-dash-hero-icon"><i class="fas fa-user-tie"></i></div>
                    <div class="txn-dash-hero-info">
                        <h4>${TH?'ภาพรวมห้องปฏิบัติการ':'Lab Overview'}</h4>
                        <p>${TH?'ดูแลจัดการธุรกรรมสารเคมีในห้องปฏิบัติการ':'Supervise chemical transactions in the lab'}</p>
                    </div>
                </div>
                <div class="txn-dash-hero-stats">
                    <div class="txn-dash-hero-stat"><div class="hval">${num(s.total_active_borrows)}</div><div class="hlbl">${TH?'กำลังยืม (ทั้งหมด)':'Active Borrows'}</div></div>
                    <div class="txn-dash-hero-stat"><div class="hval">${num(s.total_users)}</div><div class="hlbl">${TH?'ผู้ใช้งาน':'Users'}</div></div>
                    <div class="txn-dash-hero-stat"><div class="hval">${num(s.recent_7d)}</div><div class="hlbl">${TH?'7 วันล่าสุด':'Last 7 Days'}</div></div>
                </div>
            </div>`;
        } else {
            html += `
            <div class="txn-dash-hero user">
                <div class="txn-dash-hero-top">
                    <div class="txn-dash-hero-icon"><i class="fas fa-flask"></i></div>
                    <div class="txn-dash-hero-info">
                        <h4>${TH?'สารเคมีของฉัน':'My Chemicals'}</h4>
                        <p>${TH?'ภาพรวมธุรกรรมสารเคมีของคุณ':'Your chemical transaction overview'}</p>
                    </div>
                </div>
                <div class="txn-dash-hero-stats">
                    <div class="txn-dash-hero-stat"><div class="hval">${num(s.my_stock)}</div><div class="hlbl">${TH?'รายการสาร':'My Stock'}</div></div>
                    <div class="txn-dash-hero-stat"><div class="hval">${num(s.my_uses)}</div><div class="hlbl">${TH?'เบิกใช้':'Used'}</div></div>
                    <div class="txn-dash-hero-stat"><div class="hval">${num(s.my_transfers)}</div><div class="hlbl">${TH?'โอน':'Transfers'}</div></div>
                </div>
            </div>`;
        }

        // ── Stat Cards — row 1: key action stats ──
        // Pending
        html += `
        <div class="txn-dash-card clickable" onclick="switchTab('pending')">
            <div class="txn-dash-card-icon amber"><i class="fas fa-clock"></i></div>
            <div class="txn-dash-card-info">
                <div class="txn-dash-card-val">${num(s.pending_approvals)}</div>
                <div class="txn-dash-card-lbl">${TH?'รออนุมัติ':'Pending'}</div>
            </div>
            ${s.pending_approvals > 0 ? '<div class="dash-alert"></div>' : ''}
        </div>`;

        // Overdue
        html += `
        <div class="txn-dash-card clickable" onclick="switchTab('overdue')">
            <div class="txn-dash-card-icon red"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="txn-dash-card-info">
                <div class="txn-dash-card-val">${num(s.overdue)}</div>
                <div class="txn-dash-card-lbl">${TH?'เกินกำหนดคืน':'Overdue'}</div>
            </div>
            ${s.overdue > 0 ? '<div class="dash-alert"></div>' : ''}
            ${s.overdue > 0 ? `<div class="txn-dash-card-trend warn"><i class="fas fa-arrow-up"></i> ${TH?'ต้องติดตาม':'Needs attention'}</div>` : ''}
        </div>`;

        // ── Row 2: personal / role-specific ──
        // Active borrows (me)
        html += `
        <div class="txn-dash-card clickable" onclick="switchTab('active')">
            <div class="txn-dash-card-icon orange"><i class="fas fa-hand-holding-medical"></i></div>
            <div class="txn-dash-card-info">
                <div class="txn-dash-card-val">${num(s.my_borrows)}</div>
                <div class="txn-dash-card-lbl">${TH?'กำลังยืมอยู่':'My Borrows'}</div>
            </div>
        </div>`;

        // My Uses
        html += `
        <div class="txn-dash-card">
            <div class="txn-dash-card-icon purple"><i class="fas fa-eye-dropper"></i></div>
            <div class="txn-dash-card-info">
                <div class="txn-dash-card-val">${num(s.my_uses)}</div>
                <div class="txn-dash-card-lbl">${TH?'เบิกใช้แล้ว':'My Uses'}</div>
            </div>
        </div>`;

        // Lent Out
        html += `
        <div class="txn-dash-card">
            <div class="txn-dash-card-icon teal"><i class="fas fa-share-alt"></i></div>
            <div class="txn-dash-card-info">
                <div class="txn-dash-card-val">${num(s.my_lent_out)}</div>
                <div class="txn-dash-card-lbl">${TH?'ให้ยืมอยู่':'Lent Out'}</div>
            </div>
        </div>`;

        // Disposal (manager/admin) or My Stock (user)
        if (isMgr || isAdm) {
            html += `
            <div class="txn-dash-card clickable" onclick="switchTab('disposal')">
                <div class="txn-dash-card-icon slate"><i class="fas fa-trash-alt"></i></div>
                <div class="txn-dash-card-info">
                    <div class="txn-dash-card-val">${num(s.disposal_bin)}</div>
                    <div class="txn-dash-card-lbl">${TH?'รอจำหน่าย':'Disposal Bin'}</div>
                </div>
                ${s.disposal_bin > 0 ? '<div class="dash-alert"></div>' : ''}
            </div>`;
        } else {
            html += `
            <div class="txn-dash-card">
                <div class="txn-dash-card-icon green"><i class="fas fa-boxes"></i></div>
                <div class="txn-dash-card-info">
                    <div class="txn-dash-card-val">${num(s.my_stock)}</div>
                    <div class="txn-dash-card-lbl">${TH?'สาร/ขวดของฉัน':'My Stock'}</div>
                </div>
            </div>`;
        }

        dash.innerHTML = html;
    } catch(e) {
        dash.innerHTML = `<div class="ci-alert ci-alert-danger" style="grid-column:1/-1">${e.message}</div>`;
    }
}

function num(v) { return Number(v||0).toLocaleString(); }

// ========== TABS ==========
function switchTab(tab) {
    currentTab = tab;
    currentPage = 1;
    document.querySelectorAll('.ci-tab').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-'+tab).classList.add('active');
    loadList();
}

// ========== LIST ==========
function debounceLoad() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => { currentPage = 1; loadList(); }, 300);
}

async function loadList() {
    const list = document.getElementById('txnList');
    const empty = document.getElementById('emptyState');
    const pag = document.getElementById('txnPagination');
    list.innerHTML = '<div class="ci-loading"><div class="ci-spinner"></div></div>';
    empty.style.display = 'none';
    pag.innerHTML = '';

    const params = new URLSearchParams({action:'list', page:currentPage, per_page:20});

    // Tab filter
    if (currentTab === 'disposal') {
        // Show disposal bin instead
        return loadDisposalBin();
    }
    if (currentTab !== 'all') params.set('tab', currentTab);

    // Filters
    const search = document.getElementById('filterSearch').value.trim();
    if (search) params.set('barcode', search);
    const building = document.getElementById('filterBuilding').value;
    if (building) params.set('building_id', building);
    const dept = document.getElementById('filterDept').value;
    if (dept) params.set('department', dept);
    const type = document.getElementById('filterType').value;
    if (type) params.set('txn_type', type);

    try {
        const d = await apiFetch('/v1/api/borrow.php?' + params.toString());
        if (!d.success) throw new Error(d.error);

        const items = d.data.items || [];
        if (!items.length) {
            list.innerHTML = '';
            empty.style.display = 'block';
            document.getElementById('emptyTitle').textContent = TH?'ไม่พบรายการ':'No transactions found';
            return;
        }

        list.innerHTML = items.map(renderTxnCard).join('');

        // Pagination
        const pg = d.data.pagination;
        if (pg.total_pages > 1) {
            pag.innerHTML = `<div class="ci-pagination">
                <span>${TH?'หน้า':'Page'} ${pg.page}/${pg.total_pages} (${pg.total} ${TH?'รายการ':'items'})</span>
                <div class="ci-pagination-btns">
                    ${pg.page > 1 ? `<button class="ci-btn ci-btn-sm ci-btn-secondary" onclick="currentPage--;loadList()"><i class="fas fa-chevron-left"></i></button>` : ''}
                    ${pg.page < pg.total_pages ? `<button class="ci-btn ci-btn-sm ci-btn-secondary" onclick="currentPage++;loadList()"><i class="fas fa-chevron-right"></i></button>` : ''}
                </div>
            </div>`;
        }
    } catch(e) {
        list.innerHTML = '';
        empty.style.display = 'block';
        document.getElementById('emptyTitle').textContent = e.message || (TH?'เกิดข้อผิดพลาด':'Error loading');
    }
}

function renderTxnCard(t) {
    const type = t.txn_type || 'unknown';
    const icon = TXN_ICONS[type] || 'fa-exchange-alt';
    const label = TXN_LABELS[type] || type;
    const fromName = [t.from_first, t.from_last].filter(Boolean).join(' ') || '-';
    const toName = [t.to_first, t.to_last].filter(Boolean).join(' ') || '-';
    const chemName = t.chemical_name || '-';
    const isOverdue = type === 'borrow' && t.status === 'completed' && t.expected_return_date && new Date(t.expected_return_date) < new Date();

    // ── Status classification ──
    const isClosed = (t.status === 'completed' && type !== 'borrow') || t.status === 'rejected' || t.status === 'cancelled';
    const isReturned = type === 'return' && t.status === 'completed';
    const isTransferred = type === 'transfer' && t.status === 'completed';
    const isUsed = type === 'use' && t.status === 'completed';
    const isDisposed = type === 'dispose' && t.status === 'completed';
    const isBorrowActive = type === 'borrow' && t.status === 'completed' && !isOverdue;
    const isPending = t.status === 'pending';
    const isApproved = t.status === 'approved';
    const isRejected = t.status === 'rejected';
    const isCancelled = t.status === 'cancelled';

    // Card status class
    let stCls = isOverdue ? 'st-overdue' : `st-${t.status}`;

    // ── Status check icon on type icon ──
    let checkIcon = '';
    if (isClosed || isReturned || isTransferred || isUsed || isDisposed) {
        checkIcon = '<div class="txn-status-check done"><i class="fas fa-check"></i></div>';
    } else if (isOverdue) {
        checkIcon = '<div class="txn-status-check warn"><i class="fas fa-exclamation"></i></div>';
    } else if (isPending) {
        checkIcon = '<div class="txn-status-check wait"><i class="fas fa-clock"></i></div>';
    } else if (isApproved) {
        checkIcon = '<div class="txn-status-check info"><i class="fas fa-thumbs-up"></i></div>';
    } else if (isBorrowActive) {
        checkIcon = '<div class="txn-status-check info"><i class="fas fa-arrow-right"></i></div>';
    }

    // ── Pro status badge ──
    let badge = '';
    if (isOverdue) {
        badge = `<span class="txn-pro-badge s-overdue"><i class="fas fa-exclamation-triangle"></i> ${TH?'เกินกำหนด':'Overdue'}</span>`;
    } else if (isClosed || isReturned || isTransferred || isUsed || isDisposed) {
        const doneLbl = isReturned ? (TH?'คืนแล้ว':'Returned') : isTransferred ? (TH?'โอนแล้ว':'Transferred') : isUsed ? (TH?'ใช้แล้ว':'Used') : isDisposed ? (TH?'จำหน่ายแล้ว':'Disposed') : (TH?'เสร็จสิ้น':'Completed');
        badge = `<span class="txn-pro-badge s-completed"><i class="fas fa-check-circle"></i> ${doneLbl}</span>`;
    } else if (isPending) {
        badge = `<span class="txn-pro-badge s-pending"><i class="fas fa-clock"></i> ${TH?'รออนุมัติ':'Pending'}</span>`;
    } else if (isApproved) {
        badge = `<span class="txn-pro-badge s-approved"><i class="fas fa-thumbs-up"></i> ${TH?'อนุมัติแล้ว':'Approved'}</span>`;
    } else if (isBorrowActive) {
        badge = `<span class="txn-pro-badge s-approved"><i class="fas fa-hand-holding-medical"></i> ${TH?'กำลังยืม':'Active'}</span>`;
    } else if (isRejected) {
        badge = `<span class="txn-pro-badge s-rejected"><i class="fas fa-times-circle"></i> ${TH?'ปฏิเสธ':'Rejected'}</span>`;
    } else if (isCancelled) {
        badge = `<span class="txn-pro-badge s-cancelled"><i class="fas fa-ban"></i> ${TH?'ยกเลิก':'Cancelled'}</span>`;
    } else {
        badge = `<span class="txn-pro-badge s-completed"><i class="fas fa-check-circle"></i> ${t.status}</span>`;
    }

    // ── Lifecycle progress (borrow type) ──
    let lifecycle = '';
    if (type === 'borrow') {
        const steps = 3; // borrow → active → returned
        let filled = 0, stepCls = 'active';
        if (isPending) { filled = 0; stepCls = 'warn'; }
        else if (isOverdue) { filled = 2; stepCls = 'danger'; }
        else if (isBorrowActive) { filled = 2; }
        else if (t.status === 'completed') { filled = 2; } // borrowed, awaiting return
        let lcLabel = '';
        if (isPending) lcLabel = TH?'รออนุมัติ':'Awaiting approval';
        else if (isOverdue) lcLabel = TH?'เกินกำหนดคืน!':'Return overdue!';
        else if (isBorrowActive) lcLabel = TH?'กำลังใช้งาน → รอคืน':'In use → Return pending';

        if (lcLabel) {
            lifecycle = `<div class="txn-lifecycle">`;
            for (let i = 0; i < steps; i++) lifecycle += `<div class="txn-lc-step ${i < filled ? stepCls : ''}"></div>`;
            lifecycle += `<span class="txn-lc-label">${lcLabel}</span></div>`;
        }
    }

    // ── Action buttons ──
    let actions = '';
    if (isPending && (IS_ADMIN || t.from_user_id == UID)) {
        actions = `<div class="txn-card-actions">
            <button onclick="event.stopPropagation();approveTxn(${t.id})" class="ci-btn ci-btn-primary ci-btn-sm"><i class="fas fa-check"></i> ${TH?'อนุมัติ':'Approve'}</button>
            <button onclick="event.stopPropagation();rejectTxn(${t.id})" class="ci-btn ci-btn-danger ci-btn-sm"><i class="fas fa-times"></i> ${TH?'ปฏิเสธ':'Reject'}</button>
        </div>`;
    }
    if (type === 'borrow' && t.status === 'completed' && t.to_user_id == UID) {
        actions = `<div class="txn-card-actions">
            <button onclick="event.stopPropagation();openReturnModal(${t.id}, '${esc(chemName)}', ${t.quantity}, '${t.unit}')" class="ci-btn ci-btn-outline ci-btn-sm"><i class="fas fa-undo"></i> ${TH?'คืน':'Return'}</button>
        </div>`;
    }

    return `<div class="txn-card ${stCls} ci-fade" onclick="openDetail(${t.id})">
        <div class="txn-card-body">
            <div class="txn-card-left">
                <div class="txn-type-icon-wrap">
                    <div class="txn-type-icon ${type}"><i class="fas ${icon}"></i></div>
                    ${checkIcon}
                </div>
                <div>
                    <div class="txn-card-chem" title="${esc(chemName)}">${esc(chemName)}</div>
                    <div class="txn-card-txnno">
                        ${t.txn_number || '#'+t.id}
                        <span style="margin-left:8px;color:var(--accent);font-weight:500">${label}</span>
                    </div>
                    <div class="txn-card-meta">
                        <span><i class="fas fa-flask"></i> ${Number(t.quantity).toLocaleString()} ${t.unit}</span>
                        ${type !== 'dispose' ? `<span><i class="fas fa-user"></i> ${esc(fromName)} → ${esc(toName)}</span>` : `<span><i class="fas fa-user"></i> ${esc(fromName)}</span>`}
                        ${t.barcode ? `<span><i class="fas fa-barcode"></i> ${t.barcode}</span>` : ''}
                    </div>
                    ${lifecycle}
                </div>
            </div>
            <div class="txn-card-right">
                ${badge}
                <div style="font-size:11px;color:var(--c3);white-space:nowrap">${formatDate(t.created_at)}</div>
            </div>
        </div>
        ${actions}
    </div>`;
}

// ========== DISPOSAL BIN TAB ==========
async function loadDisposalBin() {
    const list = document.getElementById('txnList');
    const empty = document.getElementById('emptyState');
    document.getElementById('txnPagination').innerHTML = '';

    try {
        const d = await apiFetch('/v1/api/borrow.php?action=disposal_bin');
        if (!d.success) throw new Error(d.error);

        const items = d.data || [];
        if (!items.length) {
            list.innerHTML = '';
            empty.style.display = 'block';
            document.getElementById('emptyTitle').textContent = TH?'ไม่มีรายการรอจำหน่าย':'Disposal bin is empty';
            return;
        }

        list.innerHTML = `<div style="margin-bottom:8px;font-size:13px;color:var(--c2)"><i class="fas fa-trash-alt"></i> ${TH?'สารเคมีที่เตรียมจำหน่ายออกจากระบบ — รอดำเนินการ':'Chemicals pending disposal — awaiting action'}</div>` +
            items.map(b => {
                const disposedBy = [b.disposed_first, b.disposed_last].filter(Boolean).join(' ');
                return `<div class="disp-card ci-fade">
                    <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px">
                        <div>
                            <div style="font-weight:600;font-size:13px">${esc(b.chemical_name)}</div>
                            <div style="font-size:12px;color:var(--c3);margin-top:2px">
                                <i class="fas fa-barcode"></i> ${b.barcode || '-'}
                                <span style="margin-left:10px"><i class="fas fa-flask"></i> ${Number(b.remaining_qty).toLocaleString()} ${b.unit}</span>
                            </div>
                            <div style="font-size:11px;color:var(--c3);margin-top:4px">
                                ${TH?'ส่งโดย':'By'}: ${disposedBy} | ${TH?'เหตุผล':'Reason'}: ${b.disposal_reason || '-'} | ${b.department || ''}
                            </div>
                        </div>
                        <div style="display:flex;gap:6px;align-items:start">
                            <span class="ci-badge ${b.status==='pending'?'ci-badge-warning':'ci-badge-info'}">${b.status}</span>
                        </div>
                    </div>
                    ${IS_ADMIN ? `<div style="margin-top:10px;display:flex;gap:6px">
                        <button onclick="completeDisposal(${b.id})" class="ci-btn ci-btn-primary ci-btn-sm"><i class="fas fa-check"></i> ${TH?'ยืนยันจำหน่ายแล้ว':'Mark Complete'}</button>
                        <button onclick="cancelDisposal(${b.id})" class="ci-btn ci-btn-secondary ci-btn-sm"><i class="fas fa-undo"></i> ${TH?'คืนกลับระบบ':'Restore'}</button>
                    </div>` : ''}
                </div>`;
            }).join('');
    } catch(e) {
        list.innerHTML = `<div class="ci-alert ci-alert-danger">${e.message}</div>`;
    }
}

// ========== NEW TRANSACTION MODAL ==========
function openNewTxn(mode) {
    txnMode = mode;
    selectedSource = null;
    selectedRecipient = null;

    // Reset form
    document.getElementById('itemSearch').value = '';
    document.getElementById('itemResults').style.display = 'none';
    document.getElementById('selectedItem').style.display = 'none';
    document.getElementById('searchGuide').style.display = '';
    document.getElementById('txnStep1').style.display = '';
    document.getElementById('txnStep2').style.display = 'none';
    document.getElementById('txnQty').value = '';
    document.getElementById('txnPurpose').value = '';
    document.getElementById('txnReturnDate').value = '';
    document.getElementById('txnMiniCard').innerHTML = '';
    document.getElementById('qtyBarWrap').style.display = 'none';
    if (document.getElementById('userSearch')) document.getElementById('userSearch').value = '';
    document.getElementById('selectedUser').style.display = 'none';

    // Header gradient + icon
    const hdr = document.getElementById('txnHdrGradient');
    hdr.className = 'txn-modal-hdr mode-' + mode;
    const titles = {borrow: TH?'ยืมสารเคมี':'Borrow Chemical', use: TH?'เบิกใช้สารเคมี':'Use Chemical', transfer: TH?'โอนสารเคมี':'Transfer Chemical', dispose: TH?'จำหน่ายออก':'Dispose Chemical'};
    const subs = {borrow: TH?'ค้นหาและเลือกสารเคมีที่ต้องการยืม':'Search and select the chemical to borrow', use: TH?'เบิกใช้สารเคมีของตัวเอง — หักปริมาณจาก stock ทันที':'Use your own chemical — quantity will be deducted immediately', transfer: TH?'ค้นหาสารเคมีแล้วเลือกผู้รับโอน':'Search chemical and select transfer recipient', dispose: TH?'ค้นหาสารเคมีที่ต้องการจำหน่ายออก':'Search and select the chemical to dispose'};
    const icons = {borrow:'fa-hand-holding-medical', use:'fa-eye-dropper', transfer:'fa-people-arrows', dispose:'fa-trash-alt'};
    document.getElementById('txnModalTitle').textContent = titles[mode] || mode;
    document.getElementById('txnModalSub').textContent = subs[mode] || '';
    document.getElementById('txnHdrIcon').innerHTML = '<i class="fas ' + (icons[mode]||'fa-exchange-alt') + '"></i>';

    // Stepper reset
    setStepperStep(1);

    // Show/hide sections
    document.getElementById('recipientSection').style.display = mode === 'transfer' ? '' : 'none';
    document.getElementById('returnDateSection').style.display = mode === 'borrow' ? '' : 'none';
    document.getElementById('disposeSection').style.display = mode === 'dispose' ? '' : 'none';
    document.getElementById('useInfoSection').style.display = mode === 'use' ? '' : 'none';
    document.getElementById('useQuickQty').style.display = mode === 'use' ? '' : 'none';
    document.getElementById('usePreview').style.display = 'none';
    if (mode === 'dispose') {
        document.getElementById('disposePreview').style.display = 'none';
    }

    // Apply use-mode theme classes
    const isUse = mode === 'use';
    document.querySelector('.txn-stepper').classList.toggle('use-theme', isUse);
    document.querySelector('#txnStep1 .txn-search-box').classList.toggle('use-theme', isUse);
    document.getElementById('searchGuide').classList.toggle('use-theme', isUse);

    // Submit button style + label
    const submitBtn = document.getElementById('txnSubmitBtn');
    submitBtn.className = 'txn-submit-btn mode-' + mode;
    const submitLabels = {borrow: TH?'ยืนยันยืมสาร':'Confirm Borrow', use: TH?'ยืนยันเบิกใช้':'Confirm Use', transfer: TH?'ยืนยันโอน':'Confirm Transfer', dispose: TH?'ยืนยันจำหน่าย':'Confirm Disposal'};
    document.getElementById('txnSubmitLabel').textContent = submitLabels[mode] || (TH?'ยืนยัน':'Confirm');
    const submitIcons = {borrow:'fa-hand-holding-medical', use:'fa-eye-dropper', transfer:'fa-people-arrows', dispose:'fa-skull-crossbones'};
    submitBtn.querySelector('i').className = 'fas ' + (submitIcons[mode]||'fa-check-circle');

    // Update search guide text for 'use' mode
    const guideP = document.querySelector('#searchGuide p');
    if (guideP) {
        if (mode === 'use') {
            guideP.textContent = TH?'ค้นหาเฉพาะสารเคมีที่คุณเป็นเจ้าของ':'Search only chemicals you own';
        } else if (mode === 'transfer' && !IS_ADMIN && !IS_MANAGER) {
            guideP.textContent = TH?'ค้นหาเฉพาะสารเคมีที่คุณเป็นเจ้าของเพื่อโอน':'Search your own chemicals to transfer';
        } else if (mode === 'transfer') {
            guideP.textContent = TH?'ค้นหาสารเคมีที่ต้องการโอน (สามารถโอนแทนเจ้าของได้)':'Search chemicals to transfer (can act on behalf of owner)';
        } else {
            guideP.textContent = TH?'ค้นหาสารเคมีด้วยชื่อ, Barcode หรือ CAS Number':'Search chemicals by name, barcode or CAS number';
        }
    }
    const guideIcon = document.querySelector('#searchGuide .txn-guide-icon i');
    if (guideIcon) guideIcon.className = 'fas ' + (mode === 'use' ? 'fa-eye-dropper' : 'fa-flask');

    document.getElementById('txnModal').classList.add('show');
    setTimeout(() => document.getElementById('itemSearch').focus(), 200);
}

function setStepperStep(step) {
    for (let i = 1; i <= 3; i++) {
        const el = document.getElementById('step-ind-' + i);
        el.classList.remove('active','done');
        if (i < step) el.classList.add('done');
        if (i === step) el.classList.add('active');
    }
    for (let i = 1; i <= 2; i++) {
        const line = document.getElementById('step-line-' + i);
        line.classList.toggle('done', i < step);
    }
}

function goBackStep1() {
    document.getElementById('txnStep1').style.display = '';
    document.getElementById('txnStep2').style.display = 'none';
    setStepperStep(1);
    // Keep selected source but allow re-search
    document.getElementById('searchGuide').style.display = selectedSource ? 'none' : '';
}

function closeTxnModal() {
    document.getElementById('txnModal').classList.remove('show');
    closeInModalScan(); // Stop any active in-modal camera
}

// ========== ITEM SEARCH ==========
let itemSearchTimer = null;
function debounceItemSearch() {
    clearTimeout(itemSearchTimer);
    itemSearchTimer = setTimeout(searchItemsAPI, 300);
}

async function searchItemsAPI() {
    const q = document.getElementById('itemSearch').value.trim();
    const res = document.getElementById('itemResults');
    const guide = document.getElementById('searchGuide');
    if (q.length < 1) { res.style.display = 'none'; guide.style.display = selectedSource ? 'none' : ''; return; }
    guide.style.display = 'none';

    try {
        // For use mode: owner only. For transfer mode: regular users see only their own items
        const needOwnerOnly = (txnMode==='use') || (txnMode==='transfer' && !IS_ADMIN && !IS_MANAGER);
        const d = await apiFetch('/v1/api/borrow.php?action=search_items&q=' + encodeURIComponent(q) + (needOwnerOnly?'&owner_only=1':''));
        if (!d.success || !d.data.length) {
            res.innerHTML = `<div class="txn-sr-item" style="color:var(--c3);cursor:default;justify-content:center">
                <i class="fas fa-search" style="margin-right:6px;opacity:.5"></i> ${TH?'ไม่พบรายการ "'+esc(q)+'"':'No items found for "'+esc(q)+'"'}
            </div>`;
            res.style.display = 'block';
            return;
        }
        const srIcon = txnMode === 'use' ? 'fa-eye-dropper' : 'fa-flask';
        const srIconClass = txnMode === 'use' ? ' use-icon' : '';
        res.innerHTML = d.data.map((it, i) => {
            const isOwn = (parseInt(it.owner_id) === UID);
            const ownClass = (isOwn && txnMode !== 'use') ? ' is-own' : '';
            // For non-owned items in transfer mode (manager/admin), show "others" warning badge
            const isOthersTransfer = (!isOwn && txnMode === 'transfer' && (IS_ADMIN || IS_MANAGER));
            const othersClass = isOthersTransfer ? ' is-others-transfer' : '';
            let badge = '';
            if (isOwn && txnMode !== 'use') {
                badge = `<span class="txn-sr-own-badge"><i class="fas fa-crown"></i> ${TH?'ของคุณ':'Yours'}</span>`;
            } else if (isOthersTransfer) {
                badge = `<span class="txn-sr-others-badge"><i class="fas fa-user-shield"></i> ${TH?'ของผู้อื่น':'Others'}</span>`;
            }
            const iconForItem = isOwn && txnMode !== 'use' ? 'fa-crown' : (isOthersTransfer ? 'fa-user-shield' : srIcon);
            const iconCls = (isOwn && txnMode !== 'use') ? '' : (isOthersTransfer ? '' : srIconClass);
            return `<div class="txn-sr-item${ownClass}${othersClass}" onclick="selectItem(${i})">
                <div class="txn-sr-icon${iconCls}"><i class="fas ${iconForItem}"></i></div>
                <div class="txn-sr-info">
                    <div class="txn-sr-name">${esc(it.chemical_name)} ${badge}</div>
                    <div class="txn-sr-meta">
                        <span><i class="fas fa-barcode"></i> ${it.barcode || '-'}</span>
                        <span><i class="fas fa-user"></i> ${esc(it.owner_name || '-')}</span>
                        <span class="ci-badge ${it.source_type==='container'?'ci-badge-info':'ci-badge-default'}" style="font-size:9px;padding:1px 6px">${it.source_type}</span>
                    </div>
                </div>
                <div class="txn-sr-qty">${Number(it.remaining_qty).toLocaleString()} ${it.unit}</div>
            </div>`;
        }).join('');
        res.style.display = 'block';
        // Store data for selection
        res._data = d.data;
    } catch(e) {
        res.innerHTML = `<div class="txn-sr-item" style="color:var(--danger)">${e.message}</div>`;
        res.style.display = 'block';
    }
}

function selectItem(idx) {
    const res = document.getElementById('itemResults');
    const item = res._data[idx];
    if (!item) return;

    selectedSource = item;
    res.style.display = 'none';
    document.getElementById('itemSearch').value = '';
    document.getElementById('searchGuide').style.display = 'none';

    // Check if user is selecting their own item in borrow mode
    const isOwnInBorrow = (txnMode === 'borrow' && parseInt(item.owner_id) === UID);

    if (isOwnInBorrow) {
        // Show ownership notice instead of normal card
        document.getElementById('selectedItem').innerHTML = `
            <div class="txn-own-notice">
                <div class="txn-own-notice-icon"><i class="fas fa-crown"></i></div>
                <div class="txn-own-notice-title">${TH?'นี่เป็นสารเคมีของคุณ':'This is your chemical'}</div>
                <div class="txn-own-notice-chem"><i class="fas fa-flask"></i> ${esc(item.chemical_name)}</div>
                <div class="txn-own-notice-desc">
                    ${TH
                        ?'สารเคมีนี้เป็นของคุณเอง — แนะนำใช้ <strong>"เบิกใช้สาร"</strong> แทนการยืม<br>เบิกใช้จะหักปริมาณจาก stock ทันทีโดยไม่ต้องรออนุมัติ'
                        :'This chemical belongs to you — we recommend using <strong>"Use Chemical"</strong> instead of borrowing.<br>Usage will deduct from your stock immediately without waiting for approval.'}
                </div>
                <div class="txn-own-notice-actions">
                    <button onclick="switchToUseMode()" class="txn-own-notice-btn primary">
                        <i class="fas fa-eye-dropper"></i> ${TH?'เปลี่ยนเป็นเบิกใช้สาร':'Switch to Use Mode'}
                    </button>
                    <button onclick="proceedBorrowOwn()" class="txn-own-notice-btn secondary">
                        <i class="fas fa-hand-holding-medical"></i> ${TH?'ยืมต่อไปอยู่ดี':'Borrow Anyway'}
                    </button>
                </div>
            </div>`;
        document.getElementById('selectedItem').style.display = 'block';
        return;
    }

    // Check if manager/admin is transferring someone else's item
    const isOthersInTransfer = (txnMode === 'transfer' && parseInt(item.owner_id) !== UID && (IS_ADMIN || IS_MANAGER));

    if (isOthersInTransfer) {
        const ownerDisplay = esc(item.owner_name || (TH?'ไม่ระบุ':'Unknown'));
        document.getElementById('selectedItem').innerHTML = `
            <div class="txn-own-notice transfer-notice">
                <div class="txn-own-notice-icon transfer-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="txn-own-notice-title" style="color:#b45309">${TH?'ดำเนินการแทนเจ้าของสาร':'Acting on Behalf of Owner'}</div>
                <div class="txn-own-notice-chem"><i class="fas fa-flask"></i> ${esc(item.chemical_name)}</div>
                <div class="txn-own-notice-desc">
                    ${TH
                        ?'สารเคมีนี้เป็นของ <strong>"'+ownerDisplay+'"</strong> ไม่ใช่ของคุณ<br>คุณกำลังจะทำการโอนสารแทนเจ้าของ ในฐานะ '+(IS_ADMIN?'ผู้ดูแลระบบ':'หัวหน้าห้องปฏิบัติการ')+'<br>รายการนี้จะ<strong>ต้องรอการอนุมัติ</strong>จากเจ้าของสาร'
                        :'This chemical belongs to <strong>"'+ownerDisplay+'"</strong>, not you.<br>You are about to transfer on behalf of the owner as '+(IS_ADMIN?'Administrator':'Lab Manager')+'.<br>This transaction will <strong>require approval</strong> from the owner.'}
                </div>
                <div class="txn-own-notice-actions">
                    <button onclick="proceedTransferOnBehalf()" class="txn-own-notice-btn transfer-primary">
                        <i class="fas fa-people-arrows"></i> ${TH?'ยืนยัน — ดำเนินการโอนแทน':'Confirm — Transfer on Behalf'}
                    </button>
                    <button onclick="clearSelectedItem()" class="txn-own-notice-btn secondary">
                        <i class="fas fa-times"></i> ${TH?'ยกเลิก':'Cancel'}
                    </button>
                </div>
            </div>`;
        document.getElementById('selectedItem').style.display = 'block';
        return;
    }

    // Regular users selecting non-owned item in transfer mode — blocked at search level (owner_only)
    // But just in case, show error
    if (txnMode === 'transfer' && parseInt(item.owner_id) !== UID && !IS_ADMIN && !IS_MANAGER) {
        alert(TH?'คุณสามารถโอนได้เฉพาะสารเคมีที่คุณเป็นเจ้าของเท่านั้น':'You can only transfer chemicals that you own');
        selectedSource = null;
        document.getElementById('searchGuide').style.display = '';
        return;
    }

    renderSelectedCard(item);
}

// Switch from borrow to use mode keeping the selected item
function switchToUseMode() {
    const item = selectedSource;
    if (!item) return;
    openNewTxn('use');
    setTimeout(() => {
        const mockResults = document.getElementById('itemResults');
        mockResults._data = [item];
        selectItem(0);
    }, 100);
}

// User chose to borrow their own item anyway
function proceedBorrowOwn() {
    if (!selectedSource) return;
    renderSelectedCard(selectedSource);
}

// Manager/Admin confirmed transfer on behalf of owner
function proceedTransferOnBehalf() {
    if (!selectedSource) return;
    renderSelectedCard(selectedSource);
}

function renderSelectedCard(item) {
    const srcBadge = item.source_type === 'container'
        ? '<span class="txn-sel-badge container">Container</span>'
        : '<span class="txn-sel-badge stock">Stock</span>';

    // Pro selected card
    const useTheme = txnMode === 'use' ? ' use-theme' : '';
    document.getElementById('selectedItem').innerHTML = `
        <div class="txn-selected-pro${useTheme}">
            <button class="txn-sel-remove-btn" onclick="clearSelectedItem()" title="${TH?'ยกเลิกเลือก':'Deselect'}"><i class="fas fa-times"></i></button>
            <div class="txn-sel-hdr">
                ${srcBadge}
            </div>
            <div class="txn-sel-name">${esc(item.chemical_name)}</div>
            ${item.cas_number ? `<div class="txn-sel-cas">CAS: ${item.cas_number}</div>` : ''}
            <div class="txn-sel-chips">
                <span class="txn-sel-chip"><i class="fas fa-barcode"></i> ${item.barcode || '-'}</span>
                <span class="txn-sel-chip"><i class="fas fa-flask"></i> ${Number(item.remaining_qty).toLocaleString()} ${item.unit}</span>
                <span class="txn-sel-chip"><i class="fas fa-user"></i> ${esc(item.owner_name || '-')}</span>
                ${item.department ? `<span class="txn-sel-chip"><i class="fas fa-building"></i> ${esc(item.department)}</span>` : ''}
            </div>
            <div class="txn-sel-action">
                <i class="fas fa-arrow-right"></i>
                ${TH?'คลิก "ดำเนินการต่อ" เพื่อไปขั้นตอนถัดไป':'Click "Continue" to proceed to the next step'}
            </div>
        </div>
        <button onclick="proceedToStep2()" class="ci-btn ci-btn-block" style="margin-top:12px;border-radius:10px;padding:10px 20px;${txnMode==='use'?'background:#7c3aed;color:#fff;border-color:#7c3aed':'background:var(--accent);color:#fff;border-color:var(--accent)'}">
            <i class="fas fa-arrow-right"></i> ${TH?'ดำเนินการต่อ':'Continue'}
        </button>`;
    document.getElementById('selectedItem').style.display = 'block';
}

function proceedToStep2() {
    if (!selectedSource) return;

    // Hide step 1, show step 2
    document.getElementById('txnStep1').style.display = 'none';
    document.getElementById('txnStep2').style.display = '';
    setStepperStep(2);

    // Mini card — themed for use mode
    const isUseMode = txnMode === 'use';
    document.getElementById('txnMiniCard').className = 'txn-mini-card' + (isUseMode ? ' use-theme' : '');
    document.getElementById('txnMiniCard').innerHTML = `
        <div class="txn-mini-card-icon"><i class="fas ${isUseMode?'fa-eye-dropper':'fa-flask'}"></i></div>
        <div class="txn-mini-card-info">
            <div class="txn-mini-card-name">${esc(selectedSource.chemical_name)}</div>
            <div class="txn-mini-card-meta"><i class="fas fa-barcode"></i> ${selectedSource.barcode || '-'} · ${Number(selectedSource.remaining_qty).toLocaleString()} ${selectedSource.unit}</div>
        </div>
        <div class="txn-mini-card-change"><i class="fas fa-exchange-alt"></i> ${TH?'เปลี่ยน':'Change'}</div>`;
    document.getElementById('txnMiniCard').onclick = goBackStep1;

    // Use mode: theme qty input + populate quick values
    document.querySelector('.txn-qty-input-wrap').classList.toggle('use-theme', isUseMode);
    if (isUseMode) {
        const max = parseFloat(selectedSource.remaining_qty) || 0;
        const u = selectedSource.unit || 'mL';
        document.getElementById('quickVal25').textContent = (max*0.25).toFixed(1)+' '+u;
        document.getElementById('quickVal50').textContent = (max*0.50).toFixed(1)+' '+u;
        document.getElementById('quickVal75').textContent = (max*0.75).toFixed(1)+' '+u;
        document.getElementById('quickVal100').textContent = max.toFixed(1)+' '+u;
    }

    // Setup quantity
    document.getElementById('txnUnit').value = selectedSource.unit;
    document.getElementById('txnUnitLabel').textContent = selectedSource.unit || 'mL';
    document.getElementById('txnQty').max = selectedSource.remaining_qty;
    document.getElementById('txnQtyHint').textContent = `${TH?'คงเหลือ':'Available'}: ${Number(selectedSource.remaining_qty).toLocaleString()} ${selectedSource.unit}`;
    document.getElementById('qtyMaxLabel').textContent = `${TH?'สูงสุด':'Max'}: ${Number(selectedSource.remaining_qty).toLocaleString()}`;
    document.getElementById('qtyBarWrap').style.display = '';

    if (txnMode === 'dispose') {
        document.getElementById('txnQty').value = selectedSource.remaining_qty;
        updateQtyBar();
        updateDisposePreview();
    } else {
        document.getElementById('txnQty').value = '';
        updateQtyBar();
    }

    setTimeout(() => document.getElementById('txnQty').focus(), 150);
}

function updateQtyBar() {
    if (!selectedSource) return;
    const qty = parseFloat(document.getElementById('txnQty').value) || 0;
    const max = parseFloat(selectedSource.remaining_qty) || 1;
    const pct = Math.min((qty / max) * 100, 100);
    const fill = document.getElementById('qtyBarFill');
    fill.style.width = pct + '%';
    const useClass = txnMode === 'use' ? ' use-fill' : '';
    fill.className = 'txn-qty-bar-fill' + useClass + (pct > 90 ? ' danger' : pct > 70 ? ' warn' : '');

    // Update stepper to step 3 if qty is valid
    if (qty > 0 && qty <= max) {
        setStepperStep(3);
    } else {
        setStepperStep(2);
    }

    // Update use preview
    if (txnMode === 'use') updateUsePreview();

    // Highlight active quick btn
    document.querySelectorAll('.use-quick-btn').forEach(b => b.classList.remove('active'));
}

function setQuickQty(pct) {
    if (!selectedSource) return;
    const max = parseFloat(selectedSource.remaining_qty) || 0;
    const val = (max * pct).toFixed(2);
    document.getElementById('txnQty').value = val;
    updateQtyBar();
    // Mark active button
    document.querySelectorAll('.use-quick-btn').forEach(b => b.classList.remove('active'));
    const labels = {0.25:'25%', 0.50:'50%', 0.75:'75%', 1.0: TH?'ทั้งหมด':'All'};
    const targetLabel = labels[pct];
    document.querySelectorAll('.use-quick-btn').forEach(b => {
        if (b.querySelector('.use-quick-pct').textContent === targetLabel) b.classList.add('active');
    });
}

function updateUsePreview() {
    if (!selectedSource || txnMode !== 'use') return;
    const qty = parseFloat(document.getElementById('txnQty').value) || 0;
    const max = parseFloat(selectedSource.remaining_qty) || 0;
    const remaining = Math.max(0, max - qty);
    const purpose = document.getElementById('txnPurpose').value || (TH?'ไม่ระบุ':'Not specified');
    const preview = document.getElementById('usePreview');
    if (qty <= 0) { preview.style.display = 'none'; return; }
    document.getElementById('usePreviewContent').innerHTML = `
        <div class="use-preview-row"><span class="up-label">${TH?'สารเคมี':'Chemical'}</span><span class="up-val">${esc(selectedSource.chemical_name)}</span></div>
        <div class="use-preview-row"><span class="up-label">Barcode</span><span class="up-val" style="font-family:monospace">${selectedSource.barcode||'-'}</span></div>
        <div class="use-preview-row"><span class="up-label">${TH?'ปริมาณเบิกใช้':'Use Qty'}</span><span class="up-val purple">${Number(qty).toLocaleString()} ${selectedSource.unit}</span></div>
        <div class="use-preview-row"><span class="up-label">${TH?'คงเหลือหลังใช้':'Remaining'}</span><span class="up-val">${Number(remaining).toLocaleString()} ${selectedSource.unit}</span></div>
        <div class="use-preview-row"><span class="up-label">${TH?'วัตถุประสงค์':'Purpose'}</span><span class="up-val">${esc(purpose)}</span></div>
    `;
    preview.style.display = '';
}

function clearSelectedItem() {
    selectedSource = null;
    document.getElementById('selectedItem').style.display = 'none';
    document.getElementById('searchGuide').style.display = '';
    document.getElementById('txnStep2').style.display = 'none';
    setStepperStep(1);
}

// ========== USER SEARCH (for transfer) ==========
let userSearchTimer = null;
function debounceUserSearch() {
    clearTimeout(userSearchTimer);
    userSearchTimer = setTimeout(searchUsersAPI, 300);
}

async function searchUsersAPI() {
    const q = document.getElementById('userSearch').value.trim();
    const res = document.getElementById('userResults');
    if (q.length < 1) { res.style.display = 'none'; return; }

    try {
        const d = await apiFetch('/v1/api/borrow.php?action=search_users&q=' + encodeURIComponent(q));
        if (!d.success || !d.data.length) {
            res.innerHTML = `<div class="txn-sr-item" style="color:var(--c3);cursor:default">${TH?'ไม่พบผู้ใช้':'No users found'}</div>`;
            res.style.display = 'block';
            return;
        }
        res.innerHTML = d.data.map((u, i) => `
            <div class="txn-sr-item" onclick="selectUser(${i})">
                <div class="txn-sr-icon" style="background:#f5f3ff;color:#7c3aed"><i class="fas fa-user"></i></div>
                <div class="txn-sr-info">
                    <div class="txn-sr-name">${esc(u.full_name)}</div>
                    <div class="txn-sr-meta"><span><i class="fas fa-building"></i> ${esc(u.department || '-')}</span><span><i class="fas fa-at"></i> ${u.username}</span></div>
                </div>
            </div>
        `).join('');
        res.style.display = 'block';
        res._data = d.data;
    } catch(e) {}
}

function selectUser(idx) {
    const res = document.getElementById('userResults');
    const u = res._data[idx];
    if (!u) return;
    selectedRecipient = u;
    res.style.display = 'none';
    document.getElementById('userSearch').value = '';
    document.getElementById('selectedUser').innerHTML = `
        <div style="display:flex;align-items:center;gap:10px;background:#f5f3ff;border:2px solid #7c3aed;border-radius:10px;padding:10px 14px;position:relative;animation:slideUp .25s ease">
            <div style="width:32px;height:32px;border-radius:50%;background:#ede9fe;display:flex;align-items:center;justify-content:center;color:#7c3aed;font-size:13px;flex-shrink:0"><i class="fas fa-user"></i></div>
            <div style="flex:1;min-width:0">
                <div style="font-weight:600;font-size:13px;color:var(--c1)">${esc(u.full_name)}</div>
                <div style="font-size:11px;color:var(--c3)">${esc(u.department || '')} · @${u.username}</div>
            </div>
            <button onclick="clearSelectedUser()" style="background:#fff;border:1px solid var(--border);color:var(--c3);cursor:pointer;font-size:11px;width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center" title="${TH?'ยกเลิก':'Remove'}"><i class="fas fa-times"></i></button>
        </div>`;
    document.getElementById('selectedUser').style.display = 'block';
}

function clearSelectedUser() {
    selectedRecipient = null;
    document.getElementById('selectedUser').style.display = 'none';
}

// ========== SUBMIT TRANSACTION ==========
async function submitTxn() {
    if (!selectedSource) return alert(TH?'กรุณาเลือกสารเคมี':'Please select a chemical');

    const qty = parseFloat(document.getElementById('txnQty').value);
    if (!qty || qty <= 0) return alert(TH?'กรุณาระบุปริมาณ':'Please enter quantity');
    if (qty > parseFloat(selectedSource.remaining_qty)) return alert(TH?'ปริมาณเกินจำนวนคงเหลือ':'Quantity exceeds available amount');

    if (txnMode === 'transfer' && !selectedRecipient) return alert(TH?'กรุณาเลือกผู้รับ':'Please select recipient');

    const btn = document.getElementById('txnSubmitBtn');
    const savedLabel = document.getElementById('txnSubmitLabel').textContent;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + (TH?'กำลังดำเนินการ...':'Processing...');

    try {
        const body = {
            source_type: selectedSource.source_type,
            source_id: selectedSource.source_id,
            quantity: qty,
            unit: selectedSource.unit,
            purpose: document.getElementById('txnPurpose').value
        };

        let action = txnMode;
        if (txnMode === 'borrow') {
            body.expected_return_date = document.getElementById('txnReturnDate').value || null;
        } else if (txnMode === 'transfer') {
            body.to_user_id = selectedRecipient.id;
        } else if (txnMode === 'dispose') {
            const checkedR = document.querySelector('input[name="disposeReasonR"]:checked');
            body.disposal_reason = checkedR ? checkedR.value : 'other';
            body.disposal_method = document.getElementById('disposeMethod').value;
        }

        const d = await apiFetch('/v1/api/borrow.php?action=' + action, {
            method: 'POST',
            body: JSON.stringify(body)
        });

        if (!d.success) throw new Error(d.error);

        closeTxnModal();
        loadDashboard();
        loadList();

        let statusMsg = d.data.status === 'pending' ? (TH?'สร้างคำขอแล้ว — รออนุมัติ':'Request created — pending approval') : (TH?'ดำเนินการสำเร็จ':'Transaction completed');
        if (d.data.acting_on_behalf) statusMsg = TH?'สร้างคำขอโอนแทนเจ้าของแล้ว — รออนุมัติจากเจ้าของสาร':'Transfer request created on behalf — awaiting owner approval';
        showToast(statusMsg, 'success');
    } catch(e) {
        alert(e.message || (TH?'เกิดข้อผิดพลาด':'Error'));
    } finally {
        btn.disabled = false;
        const submitIcons = {borrow:'fa-hand-holding-medical', use:'fa-eye-dropper', transfer:'fa-people-arrows', dispose:'fa-skull-crossbones'};
        btn.innerHTML = `<i class="fas ${submitIcons[txnMode]||'fa-check-circle'}"></i> <span id="txnSubmitLabel">${savedLabel}</span>`;
    }
}

// ========== APPROVE / REJECT ==========
async function approveTxn(id) {
    if (!confirm(TH?'อนุมัติรายการนี้?':'Approve this transaction?')) return;
    try {
        const d = await apiFetch('/v1/api/borrow.php?action=approve', {method:'POST', body:JSON.stringify({txn_id:id})});
        if (!d.success) throw new Error(d.error);
        loadDashboard(); loadList();
        showToast(TH?'อนุมัติแล้ว':'Approved', 'success');
    } catch(e) { alert(e.message); }
}

async function rejectTxn(id) {
    const reason = prompt(TH?'เหตุผลที่ปฏิเสธ:':'Rejection reason:');
    if (reason === null) return;
    try {
        const d = await apiFetch('/v1/api/borrow.php?action=reject', {method:'POST', body:JSON.stringify({txn_id:id, reason})});
        if (!d.success) throw new Error(d.error);
        loadDashboard(); loadList();
        showToast(TH?'ปฏิเสธแล้ว':'Rejected', 'info');
    } catch(e) { alert(e.message); }
}

// ========== RETURN MODAL ==========
function openReturnModal(txnId, chemName, qty, unit) {
    document.getElementById('returnTxnId').value = txnId;
    document.getElementById('returnQty').value = qty;
    document.getElementById('returnQty').max = qty;
    document.getElementById('returnNotes').value = '';
    document.getElementById('returnCondition').value = 'good';
    document.getElementById('returnInfo').innerHTML = `
        <div style="font-weight:600;font-size:14px">${chemName}</div>
        <div style="font-size:12px;color:var(--c3);margin-top:4px">${TH?'ยืมไป':'Borrowed'}: ${Number(qty).toLocaleString()} ${unit}</div>`;
    document.getElementById('returnModal').classList.add('show');
}

function closeReturnModal() {
    document.getElementById('returnModal').classList.remove('show');
}

async function submitReturn() {
    const txnId = parseInt(document.getElementById('returnTxnId').value);
    const qty = parseFloat(document.getElementById('returnQty').value);
    const condition = document.getElementById('returnCondition').value;
    const notes = document.getElementById('returnNotes').value;

    try {
        const d = await apiFetch('/v1/api/borrow.php?action=return', {
            method:'POST',
            body: JSON.stringify({borrow_txn_id: txnId, quantity: qty, return_condition: condition, notes})
        });
        if (!d.success) throw new Error(d.error);
        closeReturnModal();
        loadDashboard(); loadList();
        showToast(TH?'คืนสารเคมีเรียบร้อย':'Chemical returned successfully', 'success');
    } catch(e) { alert(e.message); }
}

// ========== DETAIL MODAL ==========
async function openDetail(id) {
    document.getElementById('detailContent').innerHTML = '<div class="ci-loading"><div class="ci-spinner"></div></div>';
    document.getElementById('detailModal').classList.add('show');

    try {
        const d = await apiFetch('/v1/api/borrow.php?action=detail&id=' + id);
        if (!d.success) throw new Error(d.error);
        const t = d.data;
        const type = t.txn_type || 'borrow';
        const [badgeCls, badgeLbl] = STATUS_MAP[t.status] || ['ci-badge-default', t.status];

        document.getElementById('detailContent').innerHTML = `
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
                <div class="txn-type-icon ${type}" style="width:48px;height:48px;font-size:20px"><i class="fas ${TXN_ICONS[type]}"></i></div>
                <div>
                    <div style="font-weight:700;font-size:16px">${TXN_LABELS[type]}</div>
                    <div style="font-size:12px;color:var(--c3)">${t.txn_number}</div>
                </div>
                <span class="ci-badge ${badgeCls}" style="margin-left:auto">${badgeLbl}</span>
            </div>

            <div class="ci-g2" style="gap:12px;margin-bottom:16px">
                <div><div style="font-size:11px;color:var(--c3)">${TH?'สารเคมี':'Chemical'}</div><div style="font-weight:600">${esc(t.chemical_name||'-')}</div></div>
                <div><div style="font-size:11px;color:var(--c3)">Barcode</div><div style="font-weight:600;font-family:monospace">${t.barcode||'-'}</div></div>
                <div><div style="font-size:11px;color:var(--c3)">${TH?'ปริมาณ':'Quantity'}</div><div style="font-weight:600">${Number(t.quantity).toLocaleString()} ${t.unit}</div></div>
                <div><div style="font-size:11px;color:var(--c3)">${TH?'คงเหลือหลังทำรายการ':'Balance After'}</div><div style="font-weight:600">${t.balance_after != null ? Number(t.balance_after).toLocaleString() + ' ' + t.unit : '-'}</div></div>
            </div>

            <div class="ci-g2" style="gap:12px;margin-bottom:16px">
                <div><div style="font-size:11px;color:var(--c3)">${TH?'จาก':'From'}</div><div>${[t.from_first,t.from_last].filter(Boolean).join(' ')||'-'}</div><div style="font-size:10px;color:var(--c3)">${t.from_dept||''}</div></div>
                <div><div style="font-size:11px;color:var(--c3)">${TH?'ถึง':'To'}</div><div>${[t.to_first,t.to_last].filter(Boolean).join(' ')||'-'}</div><div style="font-size:10px;color:var(--c3)">${t.to_dept||''}</div></div>
            </div>

            ${t.purpose ? `<div style="margin-bottom:12px"><div style="font-size:11px;color:var(--c3)">${TH?'วัตถุประสงค์':'Purpose'}</div><div>${esc(t.purpose)}</div></div>` : ''}
            ${t.expected_return_date ? `<div style="margin-bottom:12px"><div style="font-size:11px;color:var(--c3)">${TH?'กำหนดคืน':'Due Date'}</div><div>${formatDate(t.expected_return_date)}</div></div>` : ''}
            ${t.actual_return_date ? `<div style="margin-bottom:12px"><div style="font-size:11px;color:var(--c3)">${TH?'คืนจริง':'Actual Return'}</div><div>${formatDate(t.actual_return_date)} — ${t.return_condition||''}</div></div>` : ''}
            ${t.approval_notes ? `<div style="margin-bottom:12px"><div style="font-size:11px;color:var(--c3)">${TH?'หมายเหตุอนุมัติ':'Approval Notes'}</div><div>${esc(t.approval_notes)}</div></div>` : ''}

            <div style="margin-top:16px;padding-top:12px;border-top:1px solid var(--border);font-size:11px;color:var(--c3);display:flex;gap:16px;flex-wrap:wrap">
                <span>${TH?'สร้างโดย':'By'}: ${[t.init_first,t.init_last].filter(Boolean).join(' ')}</span>
                <span>${formatDate(t.created_at)}</span>
                ${t.approved_by ? `<span>${TH?'อนุมัติโดย':'Approved by'}: ${[t.approver_first,t.approver_last].filter(Boolean).join(' ')}</span>` : ''}
            </div>

            ${t.barcode ? `<button onclick="closeDetailModal();document.getElementById('timelineBarcode').value='${t.barcode}';openTimelineModal();loadTimeline()" class="ci-btn ci-btn-outline ci-btn-sm" style="margin-top:12px"><i class="fas fa-history"></i> ${TH?'ดู Lifecycle Timeline':'View Lifecycle'}</button>` : ''}
        `;
    } catch(e) {
        document.getElementById('detailContent').innerHTML = `<div class="ci-alert ci-alert-danger">${e.message}</div>`;
    }
}

function closeDetailModal() {
    document.getElementById('detailModal').classList.remove('show');
}

// ========== TIMELINE MODAL ==========
function openTimelineModal() {
    document.getElementById('timelineModal').classList.add('show');
}

function closeTimelineModal() {
    document.getElementById('timelineModal').classList.remove('show');
}

async function loadTimeline() {
    const barcode = document.getElementById('timelineBarcode').value.trim();
    if (!barcode) return alert(TH?'กรุณาใส่ Barcode':'Please enter barcode');

    const cont = document.getElementById('timelineContent');
    cont.innerHTML = '<div class="ci-loading"><div class="ci-spinner"></div></div>';

    try {
        const d = await apiFetch('/v1/api/borrow.php?action=timeline&barcode=' + encodeURIComponent(barcode));
        if (!d.success) throw new Error(d.error);

        const items = d.data || [];
        if (!items.length) {
            cont.innerHTML = `<div class="ci-empty" style="padding:20px"><i class="fas fa-history"></i><p>${TH?'ไม่พบประวัติสำหรับ Barcode นี้':'No history found for this barcode'}</p></div>`;
            return;
        }

        const chemName = items[0].chemical_name || barcode;
        cont.innerHTML = `
            <div style="font-weight:600;font-size:14px;margin-bottom:12px">${esc(chemName)}</div>
            <div style="font-size:12px;color:var(--c3);margin-bottom:16px"><i class="fas fa-barcode"></i> ${barcode} — ${items.length} ${TH?'รายการ':'events'}</div>
            ${items.map(t => {
                const type = t.txn_type;
                const fromName = [t.from_first, t.from_last].filter(Boolean).join(' ');
                const toName = [t.to_first, t.to_last].filter(Boolean).join(' ');
                return `<div class="tl-item">
                    <div class="tl-dot ${type}"><i class="fas ${TXN_ICONS[type]||'fa-circle'}" style="font-size:8px"></i></div>
                    <div>
                        <div style="font-weight:600;font-size:13px">${TXN_LABELS[type]||type}</div>
                        <div style="font-size:12px;color:var(--c2);margin-top:2px">
                            ${Number(t.quantity).toLocaleString()} ${t.unit}
                            ${type !== 'dispose' ? ` — ${fromName} → ${toName}` : ` — ${fromName}`}
                        </div>
                        ${t.purpose ? `<div style="font-size:11px;color:var(--c3);margin-top:2px">${esc(t.purpose)}</div>` : ''}
                        <div style="font-size:10px;color:var(--c3);margin-top:4px">${formatDate(t.created_at)}</div>
                    </div>
                </div>`;
            }).join('')}
        `;
    } catch(e) {
        cont.innerHTML = `<div class="ci-alert ci-alert-danger">${e.message}</div>`;
    }
}

// ========== DISPOSAL ACTIONS ==========
async function completeDisposal(binId) {
    if (!confirm(TH?'ยืนยันว่าจำหน่ายสำเร็จแล้ว? (ไม่สามารถย้อนกลับ)':'Confirm disposal completed? (Cannot undo)')) return;
    try {
        const d = await apiFetch('/v1/api/borrow.php?action=disposal_complete', {method:'POST', body:JSON.stringify({bin_id:binId})});
        if (!d.success) throw new Error(d.error);
        loadDashboard(); loadDisposalBin();
        showToast(TH?'จำหน่ายสำเร็จ':'Disposal completed', 'success');
    } catch(e) { alert(e.message); }
}

async function cancelDisposal(binId) {
    if (!confirm(TH?'คืนสารนี้กลับเข้าระบบ?':'Restore this item back to the system?')) return;
    try {
        const d = await apiFetch('/v1/api/borrow.php?action=disposal_cancel', {method:'POST', body:JSON.stringify({bin_id:binId})});
        if (!d.success) throw new Error(d.error);
        loadDashboard(); loadDisposalBin();
        showToast(TH?'คืนกลับเข้าระบบแล้ว':'Restored to system', 'success');
    } catch(e) { alert(e.message); }
}

// ========== HELPERS ==========
function esc(s) { if (!s) return ''; const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

function showToast(msg, type='info') {
    const t = document.createElement('div');
    t.className = 'ci-alert ci-alert-' + (type==='success'?'success':'info');
    t.style.cssText = 'position:fixed;top:60px;right:20px;z-index:9999;max-width:360px;box-shadow:0 4px 12px rgba(0,0,0,.15);animation:fadeIn .2s';
    t.innerHTML = `<i class="fas ${type==='success'?'fa-check-circle':'fa-info-circle'}"></i> ${msg}`;
    document.body.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity .3s'; setTimeout(() => t.remove(), 300); }, 3000);
}

// Close modals on backdrop click
document.querySelectorAll('.ci-modal-bg').forEach(bg => {
    bg.addEventListener('click', e => { if (e.target === bg) bg.classList.remove('show'); });
});

// Close search dropdowns on outside click
document.addEventListener('click', e => {
    if (!e.target.closest('#itemSearch') && !e.target.closest('#itemResults')) {
        document.getElementById('itemResults').style.display = 'none';
    }
    if (!e.target.closest('#userSearch') && !e.target.closest('#userResults')) {
        const ur = document.getElementById('userResults');
        if (ur) ur.style.display = 'none';
    }
});

// ========== DISPOSE PREVIEW ==========
const REASON_LABELS = {expired:TH?'หมดอายุ':'Expired', empty:TH?'หมด/ใช้จนหมด':'Empty', contaminated:TH?'ปนเปื้อน':'Contaminated', damaged:TH?'ชำรุด/แตก':'Damaged', obsolete:TH?'ไม่ใช้แล้ว':'Obsolete', other:TH?'อื่นๆ':'Other'};
const METHOD_LABELS = {waste_collection:TH?'ส่งเก็บของเสีย':'Waste Collection', neutralization:TH?'ทำให้เป็นกลาง':'Neutralization', incineration:TH?'เผาทำลาย':'Incineration', return_to_vendor:TH?'คืนผู้ขาย':'Return to Vendor', other:TH?'อื่นๆ':'Other'};

function updateDisposePreview() {
    if (!selectedSource || txnMode !== 'dispose') return;
    const preview = document.getElementById('disposePreview');
    const checkedR = document.querySelector('input[name="disposeReasonR"]:checked');
    const reason = checkedR ? checkedR.value : 'expired';
    const method = document.getElementById('disposeMethod').value;
    const qty = document.getElementById('txnQty').value || selectedSource.remaining_qty;

    document.getElementById('disposePreviewContent').innerHTML = `
        <div class="dispose-preview-row"><span class="dp-label">${TH?'สารเคมี':'Chemical'}</span><span class="dp-val">${esc(selectedSource.chemical_name)}</span></div>
        <div class="dispose-preview-row"><span class="dp-label">Barcode</span><span class="dp-val" style="font-family:monospace">${selectedSource.barcode||'-'}</span></div>
        <div class="dispose-preview-row"><span class="dp-label">${TH?'ปริมาณจำหน่าย':'Dispose Qty'}</span><span class="dp-val" style="color:#c62828">${Number(qty).toLocaleString()} ${selectedSource.unit}</span></div>
        <div class="dispose-preview-row"><span class="dp-label">${TH?'เจ้าของ':'Owner'}</span><span class="dp-val">${esc(selectedSource.owner_name||'-')}</span></div>
        <div class="dispose-preview-row"><span class="dp-label">${TH?'เหตุผล':'Reason'}</span><span class="dp-val">${REASON_LABELS[reason]||reason}</span></div>
        <div class="dispose-preview-row"><span class="dp-label">${TH?'วิธีจำหน่าย':'Method'}</span><span class="dp-val">${METHOD_LABELS[method]||method}</span></div>
    `;
    preview.style.display = 'block';
}

// Listen for reason radio changes & method select
document.querySelectorAll('input[name="disposeReasonR"]').forEach(r => r.addEventListener('change', updateDisposePreview));
document.getElementById('disposeMethod').addEventListener('change', updateDisposePreview);

// ========== BARCODE SCANNER ==========
let scannerInstance = null;
let scanCameraActive = false;
let scannedItemData = null; // stores the full scan result

function openScanModal() {
    document.getElementById('scanModal').classList.add('show');
    document.getElementById('scanBarcodeInput').value = '';
    document.getElementById('scanResult').style.display = 'none';
    document.getElementById('scanProcessing').style.display = 'none';
    document.getElementById('scanCameraWrap').style.display = '';
    document.getElementById('scanCameraError').style.display = 'none';
    setTimeout(() => document.getElementById('scanBarcodeInput').focus(), 200);
}

function closeScanModal() {
    document.getElementById('scanModal').classList.remove('show');
    stopScanCamera();
}

function toggleScanCamera() {
    if (scanCameraActive) {
        stopScanCamera();
    } else {
        startScanCamera();
    }
}

async function startScanCamera() {
    if (typeof Html5Qrcode === 'undefined') {
        // Load the library dynamically
        try {
            await new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js';
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });
        } catch(e) {
            document.getElementById('scanCameraError').style.display = '';
            return;
        }
    }

    try {
        scannerInstance = new Html5Qrcode('scanReader');
        await scannerInstance.start(
            { facingMode: 'environment' },
            { fps: 10, qrbox: { width: 250, height: 100 }, formatsToSupport: [
                Html5QrcodeSupportedFormats.QR_CODE,
                Html5QrcodeSupportedFormats.CODE_128,
                Html5QrcodeSupportedFormats.CODE_39,
                Html5QrcodeSupportedFormats.EAN_13,
                Html5QrcodeSupportedFormats.EAN_8,
                Html5QrcodeSupportedFormats.CODE_93
            ]},
            (decodedText) => {
                // Success — barcode found
                document.getElementById('scanBarcodeInput').value = decodedText;
                stopScanCamera();
                processScanBarcode();
            },
            () => {} // ignore errors
        );
        scanCameraActive = true;
        document.getElementById('scanCamToggleLabel').textContent = TH?'ปิดกล้อง':'Close Camera';
        document.getElementById('scanCamToggle').classList.add('ci-btn-danger');
        document.getElementById('scanCamToggle').classList.remove('ci-btn-sm');
        document.getElementById('scanCameraOverlay').style.display = '';
    } catch(e) {
        document.getElementById('scanCameraError').style.display = '';
        document.getElementById('scanCameraWrap').style.display = 'none';
    }
}

function stopScanCamera() {
    if (scannerInstance && scanCameraActive) {
        try { scannerInstance.stop().catch(()=>{}); } catch(e) {}
    }
    scanCameraActive = false;
    document.getElementById('scanCamToggleLabel').textContent = TH?'เปิดกล้อง':'Open Camera';
    const btn = document.getElementById('scanCamToggle');
    btn.classList.remove('ci-btn-danger');
    btn.classList.add('ci-btn-sm');
}

// Handle Enter key on barcode input
document.getElementById('scanBarcodeInput').addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); processScanBarcode(); }
});

async function processScanBarcode() {
    const barcode = document.getElementById('scanBarcodeInput').value.trim();
    if (!barcode) return;

    // Show processing
    document.getElementById('scanProcessing').style.display = '';
    document.getElementById('scanResult').style.display = 'none';
    document.getElementById('scanGoBtn').disabled = true;

    try {
        const d = await apiFetch('/v1/api/borrow.php?action=scan_barcode&barcode=' + encodeURIComponent(barcode));
        if (!d.success) throw new Error(d.error);

        scannedItemData = d.data;
        document.getElementById('scanProcessing').style.display = 'none';
        renderScanResult(d.data);
    } catch(e) {
        document.getElementById('scanProcessing').style.display = 'none';
        document.getElementById('scanResult').innerHTML = `
            <div class="scan-result-card">
                <div class="scan-result-hdr">
                    <div class="scan-result-icon" style="background:#fef2f2;color:#dc2626"><i class="fas fa-times-circle"></i></div>
                    <div>
                        <div class="scan-result-name">${TH?'ไม่พบรายการ':'Not Found'}</div>
                        <div class="scan-result-cas">${esc(barcode)}</div>
                    </div>
                </div>
                <div class="scan-result-body">
                    <p style="font-size:13px;color:var(--c3);margin:0">${TH?'ไม่พบสารเคมีที่ตรงกับ Barcode นี้ในระบบ':'No chemical found matching this barcode in the system'}</p>
                </div>
            </div>`;
        document.getElementById('scanResult').style.display = '';
    } finally {
        document.getElementById('scanGoBtn').disabled = false;
    }
}

function renderScanResult(data) {
    const item = data.item;
    const relation = data.relation; // 'owner', 'borrower', 'other'
    const activeBorrow = data.active_borrow; // active borrow txn if borrower
    const isOwner = relation === 'owner';
    const isBorrower = relation === 'borrower';

    // Badge based on relation
    let badgeHtml = '';
    let iconClass = 'other';
    if (isOwner) {
        badgeHtml = `<div class="scan-result-badge owner-badge"><i class="fas fa-crown"></i> ${TH?'คุณเป็นเจ้าของสารนี้':'You own this chemical'}</div>`;
        iconClass = 'owner';
    } else if (isBorrower) {
        badgeHtml = `<div class="scan-result-badge return-badge"><i class="fas fa-undo"></i> ${TH?'คุณกำลังยืมสารนี้อยู่':'You are currently borrowing this'}</div>`;
        iconClass = 'returnable';
    } else {
        const ownerName = esc(item.owner_name || (TH?'ไม่ระบุ':'Unknown'));
        badgeHtml = `<div class="scan-result-badge other-badge"><i class="fas fa-user"></i> ${TH?'เจ้าของ: ':'Owner: '}${ownerName}</div>`;
        iconClass = 'other';
    }

    // Action buttons based on relation + role
    let actionsHtml = '';
    if (isBorrower && activeBorrow) {
        // Primary: Return
        actionsHtml = `
            <button onclick="scanActionReturn()" class="scan-action-btn act-return"><i class="fas fa-undo"></i> ${TH?'คืนสารเคมี':'Return Chemical'}</button>`;
    } else if (isOwner) {
        // Primary: Use (owner consumes own stock)
        actionsHtml = `
            <button onclick="scanActionUse()" class="scan-action-btn act-use"><i class="fas fa-eye-dropper"></i> ${TH?'เบิกใช้สารเคมี':'Use Chemical'}</button>`;
        if (IS_MANAGER) {
            actionsHtml += `<div class="scan-action-sec">
                <button onclick="scanActionTransfer()" class="scan-action-btn act-transfer"><i class="fas fa-people-arrows"></i> ${TH?'โอน':'Transfer'}</button>
                <button onclick="scanActionDispose()" class="scan-action-btn act-dispose"><i class="fas fa-trash-alt"></i> ${TH?'จำหน่าย':'Dispose'}</button>
            </div>`;
        }
    } else {
        // Primary: Borrow (from someone else)
        actionsHtml = `
            <button onclick="scanActionBorrow()" class="scan-action-btn act-borrow"><i class="fas fa-hand-holding-medical"></i> ${TH?'ยืมสารเคมี':'Borrow Chemical'}</button>`;
        if (IS_MANAGER) {
            actionsHtml += `<div class="scan-action-sec">
                <button onclick="scanActionTransfer()" class="scan-action-btn act-transfer"><i class="fas fa-people-arrows"></i> ${TH?'โอน':'Transfer'}</button>
                <button onclick="scanActionDispose()" class="scan-action-btn act-dispose"><i class="fas fa-trash-alt"></i> ${TH?'จำหน่าย':'Dispose'}</button>
            </div>`;
        }
    }

    document.getElementById('scanResult').innerHTML = `
        <div class="scan-result-card">
            <div class="scan-result-hdr">
                <div class="scan-result-icon ${iconClass}"><i class="fas ${isOwner?'fa-crown':isBorrower?'fa-undo':'fa-flask'}"></i></div>
                <div style="flex:1;min-width:0">
                    <div class="scan-result-name">${esc(item.chemical_name)}</div>
                    ${item.cas_number ? `<div class="scan-result-cas">CAS: ${item.cas_number}</div>` : ''}
                </div>
                <span class="ci-badge ${item.source_type==='container'?'ci-badge-info':'ci-badge-default'}" style="font-size:9px">${item.source_type}</span>
            </div>
            <div class="scan-result-body">
                <div class="scan-result-chips">
                    <span class="scan-result-chip"><i class="fas fa-barcode"></i> ${item.barcode || '-'}</span>
                    <span class="scan-result-chip"><i class="fas fa-flask"></i> ${Number(item.remaining_qty).toLocaleString()} ${item.unit}</span>
                    <span class="scan-result-chip"><i class="fas fa-user"></i> ${esc(item.owner_name || '-')}</span>
                    ${item.department ? `<span class="scan-result-chip"><i class="fas fa-building"></i> ${esc(item.department)}</span>` : ''}
                </div>
                ${badgeHtml}
                ${isBorrower && activeBorrow ? `<div style="font-size:11px;color:var(--c3);margin-top:6px"><i class="fas fa-calendar"></i> ${TH?'ยืมเมื่อ':'Borrowed on'}: ${formatDate(activeBorrow.created_at)} · ${Number(activeBorrow.quantity).toLocaleString()} ${activeBorrow.unit}</div>` : ''}
            </div>
            <div class="scan-result-actions">${actionsHtml}</div>
        </div>`;
    document.getElementById('scanResult').style.display = '';
}

// ========== SCAN ACTION HANDLERS ==========
function scanActionUse() {
    if (!scannedItemData) return;
    closeScanModal();
    openNewTxn('use');
    setTimeout(() => autoSelectScannedItem(scannedItemData.item), 150);
}

function scanActionBorrow() {
    if (!scannedItemData) return;
    closeScanModal();
    openNewTxn('borrow');
    setTimeout(() => autoSelectScannedItem(scannedItemData.item), 150);
}

function scanActionTransfer() {
    if (!scannedItemData) return;
    closeScanModal();
    openNewTxn('transfer');
    setTimeout(() => autoSelectScannedItem(scannedItemData.item), 150);
}

function scanActionDispose() {
    if (!scannedItemData) return;
    closeScanModal();
    openNewTxn('dispose');
    setTimeout(() => autoSelectScannedItem(scannedItemData.item), 150);
}

function scanActionReturn() {
    if (!scannedItemData || !scannedItemData.active_borrow) return;
    const ab = scannedItemData.active_borrow;
    closeScanModal();
    openReturnModal(ab.id, scannedItemData.item.chemical_name, ab.quantity, ab.unit);
}

// Auto-select a scanned item in the txn modal (skip step 1 search)
function autoSelectScannedItem(item) {
    // Simulate having search result data
    const mockResults = document.getElementById('itemResults');
    mockResults._data = [item];
    // For scan actions, bypass ownership notices (scan already showed owner info & user chose the action)
    const isBorrowOwnBypass = (txnMode === 'borrow' && parseInt(item.owner_id) === UID);
    const isTransferOnBehalfBypass = (txnMode === 'transfer' && parseInt(item.owner_id) !== UID && (IS_ADMIN || IS_MANAGER));
    if (isBorrowOwnBypass || isTransferOnBehalfBypass) {
        selectedSource = item;
        document.getElementById('itemSearch').value = '';
        document.getElementById('searchGuide').style.display = 'none';
        renderSelectedCard(item);
    } else {
        selectItem(0);
    }
    // Auto-proceed to step 2
    setTimeout(() => proceedToStep2(), 200);
}

// ========== IN-MODAL SCAN ==========
let inModalScannerInstance = null;
let inModalScanActive = false;

function openInModalScan() {
    // Create a mini scan overlay inside the modal
    const searchBox = document.querySelector('#txnStep1 .txn-search-box');
    const existing = document.getElementById('inModalScanArea');
    if (existing) { existing.remove(); inModalScanActive = false; return; }

    const scanArea = document.createElement('div');
    scanArea.id = 'inModalScanArea';
    scanArea.style.cssText = 'margin-bottom:12px;border-radius:10px;overflow:hidden;position:relative;background:#111';
    scanArea.innerHTML = `
        <div id="inModalReader" style="min-height:160px"></div>
        <div style="position:absolute;inset:0;pointer-events:none;display:flex;align-items:center;justify-content:center">
            <div class="scan-corner tl" style="width:20px;height:20px;top:12px;left:12px"></div>
            <div class="scan-corner tr" style="width:20px;height:20px;top:12px;right:12px"></div>
            <div class="scan-corner bl" style="width:20px;height:20px;bottom:12px;left:12px"></div>
            <div class="scan-corner br" style="width:20px;height:20px;bottom:12px;right:12px"></div>
        </div>
        <button onclick="closeInModalScan()" style="position:absolute;top:6px;right:6px;background:rgba(0,0,0,.5);border:none;color:#fff;width:28px;height:28px;border-radius:50%;cursor:pointer;font-size:13px;z-index:5"><i class="fas fa-times"></i></button>
    `;
    searchBox.parentElement.insertBefore(scanArea, searchBox);

    startInModalCamera();
}

async function startInModalCamera() {
    if (typeof Html5Qrcode === 'undefined') {
        try {
            await new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js';
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            });
        } catch(e) { return; }
    }

    try {
        inModalScannerInstance = new Html5Qrcode('inModalReader');
        await inModalScannerInstance.start(
            { facingMode: 'environment' },
            { fps: 10, qrbox: { width: 220, height: 80 }, formatsToSupport: [
                Html5QrcodeSupportedFormats.QR_CODE,
                Html5QrcodeSupportedFormats.CODE_128,
                Html5QrcodeSupportedFormats.CODE_39,
                Html5QrcodeSupportedFormats.EAN_13
            ]},
            async (decodedText) => {
                closeInModalScan();
                // Use the barcode to search
                document.getElementById('itemSearch').value = decodedText;
                searchItemsAPI();
            },
            () => {}
        );
        inModalScanActive = true;
    } catch(e) {
        closeInModalScan();
    }
}

function closeInModalScan() {
    if (inModalScannerInstance && inModalScanActive) {
        try { inModalScannerInstance.stop().catch(()=>{}); } catch(e) {}
    }
    inModalScanActive = false;
    const el = document.getElementById('inModalScanArea');
    if (el) el.remove();
}
</script>
</body></html>