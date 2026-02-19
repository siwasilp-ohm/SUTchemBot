<?php
require_once __DIR__ . '/../includes/layout.php';
$user = Auth::getCurrentUser();
if (!$user) { header('Location: /v1/pages/login.php'); exit; }
if (!in_array($user['role_name'], ['admin', 'lab_manager'])) { header('Location: /v1/'); exit; }
$isAdmin = $user['role_name'] === 'admin';
$lang = I18n::getCurrentLang();
Layout::head(__('users_title'));
?>
<body>
<?php Layout::sidebar('users'); Layout::beginContent(); ?>

<!-- Page Header -->
<div class="ci-pg-hdr">
    <div>
        <div class="ci-pg-title"><i class="fas fa-users-cog"></i> <?php echo __('users_title'); ?></div>
        <div class="ci-pg-sub"><?php echo __('users_subtitle'); ?></div>
    </div>
    <?php if ($isAdmin): ?>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <div class="ci-btn-group" style="position:relative">
            <button class="ci-btn ci-btn-outline ci-btn-sm" onclick="toggleImportExport()" id="btnImportExport" title="Import / Export">
                <i class="fas fa-exchange-alt"></i> Import / Export
            </button>
            <div class="ie-dropdown" id="ieDropdown">
                <div class="ie-dropdown-head">
                    <i class="fas fa-file-csv" style="color:var(--accent)"></i> จัดการข้อมูลผู้ใช้
                </div>
                <button class="ie-dropdown-item" onclick="exportUsersCSV()">
                    <i class="fas fa-download" style="color:#059669"></i>
                    <div>
                        <div class="ie-item-title">Export CSV</div>
                        <div class="ie-item-desc">ดาวน์โหลดข้อมูลผู้ใช้ทั้งหมด</div>
                    </div>
                </button>
                <button class="ie-dropdown-item" onclick="exportUsersJSON()">
                    <i class="fas fa-code" style="color:#6C5CE7"></i>
                    <div>
                        <div class="ie-item-title">Export JSON</div>
                        <div class="ie-item-desc">สำรองข้อมูลแบบ JSON</div>
                    </div>
                </button>
                <div class="ie-dropdown-divider"></div>
                <button class="ie-dropdown-item" onclick="downloadTemplate()">
                    <i class="fas fa-file-alt" style="color:#d97706"></i>
                    <div>
                        <div class="ie-item-title">ดาวน์โหลด Template</div>
                        <div class="ie-item-desc">แบบฟอร์ม CSV สำหรับ import</div>
                    </div>
                </button>
                <button class="ie-dropdown-item" onclick="openImportModal()">
                    <i class="fas fa-upload" style="color:#2563eb"></i>
                    <div>
                        <div class="ie-item-title">Import จาก CSV</div>
                        <div class="ie-item-desc">นำเข้าผู้ใช้จากไฟล์ CSV</div>
                    </div>
                </button>
            </div>
        </div>
        <button class="ci-btn ci-btn-primary" onclick="openAddModal()">
            <i class="fas fa-user-plus"></i> <?php echo __('users_add'); ?>
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- Stats -->
<div class="ci-stats" style="margin-bottom:20px">
    <div class="ci-stat">
        <div class="ci-stat-icon blue"><i class="fas fa-users"></i></div>
        <div><div class="ci-stat-val" id="sTotalUsers">-</div><div class="ci-stat-lbl"><?php echo __('users_total'); ?></div></div>
    </div>
    <div class="ci-stat">
        <div class="ci-stat-icon green"><i class="fas fa-check-circle"></i></div>
        <div><div class="ci-stat-val" id="sActiveUsers">-</div><div class="ci-stat-lbl"><?php echo __('users_active'); ?></div></div>
    </div>
    <div class="ci-stat">
        <div class="ci-stat-icon orange"><i class="fas fa-user-shield"></i></div>
        <div><div class="ci-stat-val" id="sManagers">-</div><div class="ci-stat-lbl"><?php echo __('users_lab_managers'); ?></div></div>
    </div>
    <div class="ci-stat">
        <div class="ci-stat-icon purple"><i class="fas fa-crown"></i></div>
        <div><div class="ci-stat-val" id="sAdmins">-</div><div class="ci-stat-lbl"><?php echo __('users_admins'); ?></div></div>
    </div>
</div>

<!-- Filter Tabs + Search -->
<div class="ci-card" style="margin-bottom:16px">
    <div class="ci-card-body" style="padding:12px 16px">
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
            <div class="ci-tabs" id="roleFilter" style="border-bottom:none;margin-bottom:0;flex:1;min-width:0;overflow-x:auto">
                <button class="ci-tab active" data-role="all"><?php echo __('all'); ?> <span class="ci-badge ci-badge-default" id="cAll" style="margin-left:4px">0</span></button>
                <button class="ci-tab" data-role="admin"><i class="fas fa-crown ci-hide-mobile" style="color:#c62828;margin-right:3px"></i> Admin</button>
                <button class="ci-tab" data-role="lab_manager"><i class="fas fa-user-shield ci-hide-mobile" style="color:#e65100;margin-right:3px"></i> Manager</button>
                <button class="ci-tab" data-role="user"><i class="fas fa-user ci-hide-mobile" style="color:var(--accent);margin-right:3px"></i> User</button>
                <button class="ci-tab" data-role="inactive"><i class="fas fa-ban ci-hide-mobile" style="color:var(--c3);margin-right:3px"></i> <?php echo __('users_inactive'); ?></button>
            </div>
            <div style="position:relative;width:260px" class="ci-user-search">
                <i class="fas fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#bbb;font-size:13px"></i>
                <input type="text" id="userSearch" class="ci-input" style="padding-left:32px" placeholder="<?php echo __('users_search'); ?>" oninput="applyFilters()">
            </div>
        </div>
    </div>
</div>

<!-- Users Table -->
<div class="ci-card">
    <div class="ci-card-body" style="padding:0">
        <div class="ci-table-wrap">
            <table class="ci-table" id="usersTable">
                <thead>
                    <tr>
                        <th style="width:30%"><?php echo __('users_name'); ?></th>
                        <th><?php echo __('users_role'); ?></th>
                        <th><?php echo $lang === 'th' ? 'ฝ่าย' : 'Division'; ?></th>
                        <th><?php echo $lang === 'th' ? 'งาน' : 'Section'; ?></th>
                        <th><?php echo __('users_status'); ?></th>
                        <th><?php echo __('users_last_login'); ?></th>
                        <th style="text-align:center;width:100px"><?php echo __('actions'); ?></th>
                    </tr>
                </thead>
                <tbody id="userTableBody">
                    <tr><td colspan="7"><div class="ci-loading"><div class="ci-spinner"></div></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit User Modal -->
<div class="ci-modal-bg" id="userModal">
    <div class="ci-modal" style="max-width:580px">
        <div class="ci-modal-hdr">
            <h3 id="modalTitle"><i class="fas fa-user-plus" style="color:var(--accent);margin-right:6px"></i> <?php echo __('users_add'); ?></h3>
            <button class="ci-modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="ci-modal-body">
            <form id="userForm" onsubmit="saveUser(event)">
                <input type="hidden" id="fUserId" value="">
                
                <div class="ci-g2" style="margin-bottom:0">
                    <div class="ci-fg">
                        <label class="ci-label"><?php echo __('users_first_name'); ?> *</label>
                        <input type="text" id="fFirstName" class="ci-input" required>
                    </div>
                    <div class="ci-fg">
                        <label class="ci-label"><?php echo __('users_last_name'); ?> *</label>
                        <input type="text" id="fLastName" class="ci-input" required>
                    </div>
                </div>
                
                <div class="ci-g2" style="margin-bottom:0">
                    <div class="ci-fg">
                        <label class="ci-label"><?php echo __('users_username'); ?> *</label>
                        <input type="text" id="fUsername" class="ci-input" required>
                    </div>
                    <div class="ci-fg">
                        <label class="ci-label"><?php echo __('users_email'); ?> *</label>
                        <input type="email" id="fEmail" class="ci-input" required>
                    </div>
                </div>
                
                <div class="ci-g2" style="margin-bottom:0">
                    <div class="ci-fg">
                        <label class="ci-label"><?php echo __('users_phone'); ?></label>
                        <input type="text" id="fPhone" class="ci-input">
                    </div>
                    <div class="ci-fg">
                        <label class="ci-label"><?php echo __('users_password'); ?> <span id="pwHint" style="font-weight:400;color:var(--c3)"></span></label>
                        <input type="password" id="fPassword" class="ci-input" minlength="6">
                    </div>
                </div>
                
                <div class="ci-fg" style="margin-bottom:0">
                    <label class="ci-label"><?php echo __('users_role'); ?> *</label>
                    <select id="fRole" class="ci-select" required>
                        <option value="">-- <?php echo __('users_select_role'); ?> --</option>
                    </select>
                </div>
                
                <!-- ═══ Organization Hierarchy Section ═══ -->
                <div class="org-section">
                    <div class="org-section-hdr">
                        <i class="fas fa-sitemap"></i>
                        <span><?php echo $lang === 'th' ? 'สังกัดองค์กร' : 'Organization'; ?></span>
                    </div>
                    
                    <div class="ci-g2" style="margin-bottom:0">
                        <div class="ci-fg">
                            <label class="ci-label"><i class="fas fa-building" style="margin-right:4px;font-size:11px;color:#6C5CE7"></i> <?php echo $lang === 'th' ? 'ศูนย์' : 'Center'; ?></label>
                            <select id="fCenter" class="ci-select org-cascade" data-level="center" onchange="onOrgChange('center')">
                                <option value="">-- <?php echo $lang === 'th' ? 'เลือกศูนย์' : 'Select Center'; ?> --</option>
                            </select>
                        </div>
                        <div class="ci-fg">
                            <label class="ci-label"><i class="fas fa-sitemap" style="margin-right:4px;font-size:11px;color:#e65100"></i> <?php echo $lang === 'th' ? 'ฝ่าย' : 'Division'; ?></label>
                            <select id="fDivision" class="ci-select org-cascade" data-level="division" onchange="onOrgChange('division')" disabled>
                                <option value="">-- <?php echo $lang === 'th' ? 'เลือกฝ่าย' : 'Select Division'; ?> --</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="ci-g2" style="margin-bottom:0">
                        <div class="ci-fg">
                            <label class="ci-label"><i class="fas fa-layer-group" style="margin-right:4px;font-size:11px;color:#1a8a5c"></i> <?php echo $lang === 'th' ? 'งาน' : 'Section'; ?></label>
                            <select id="fSection" class="ci-select org-cascade" data-level="section" onchange="onOrgChange('section')" disabled>
                                <option value="">-- <?php echo $lang === 'th' ? 'เลือกงาน' : 'Select Section'; ?> --</option>
                            </select>
                        </div>
                        <div class="ci-fg">
                            <label class="ci-label"><i class="fas fa-warehouse" style="margin-right:4px;font-size:11px;color:#2563eb"></i> <?php echo $lang === 'th' ? 'ชื่อคลัง' : 'Store'; ?></label>
                            <select id="fStore" class="ci-select org-cascade" data-level="store" onchange="onOrgChange('store')" disabled>
                                <option value="">-- <?php echo $lang === 'th' ? 'เลือกคลัง' : 'Select Store'; ?> --</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="org-breadcrumb" id="orgBreadcrumb" style="display:none">
                        <i class="fas fa-map-marker-alt"></i>
                        <span id="orgBreadcrumbText"></span>
                    </div>
                </div>
                
                <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px;padding-top:14px;border-top:1px solid var(--border)">
                    <button type="button" class="ci-btn ci-btn-secondary" onclick="closeModal()"><?php echo __('cancel'); ?></button>
                    <button type="submit" class="ci-btn ci-btn-primary" id="saveBtn"><i class="fas fa-save"></i> <?php echo __('save'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- User Detail Side Panel -->
<div class="ci-modal-bg" id="detailModal">
    <div class="ci-modal" style="max-width:480px">
        <div class="ci-modal-hdr">
            <h3><i class="fas fa-user" style="color:var(--accent);margin-right:6px"></i> <?php echo __('users_detail'); ?></h3>
            <button class="ci-modal-close" onclick="closeDetailModal()">&times;</button>
        </div>
        <div class="ci-modal-body" id="detailContent">
        </div>
    </div>
</div>

<?php if ($isAdmin): ?>
<!-- Import CSV Modal -->
<div class="ci-modal-bg" id="importModal">
    <div class="ci-modal" style="max-width:780px">
        <div class="ci-modal-hdr">
            <h3><i class="fas fa-file-import" style="color:#2563eb;margin-right:6px"></i> Import ผู้ใช้จาก CSV</h3>
            <button class="ci-modal-close" onclick="closeImportModal()">&times;</button>
        </div>
        <div class="ci-modal-body">
            <!-- Step 1: Upload -->
            <div id="importStep1">
                <div class="imp-info-box">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>รูปแบบ CSV ที่รองรับ</strong>
                        <p>ไฟล์ต้องมี header: <code>ชื่อ นามสกุล, username, password, email, phone, role, ศูนย์, ฝ่าย, งาน</code></p>
                        <p style="margin-top:4px">หรือใช้ <a href="#" onclick="downloadTemplate();return false">Template สำเร็จรูป</a></p>
                    </div>
                </div>
                
                <div class="imp-upload-zone" id="impDropZone" onclick="document.getElementById('impFileInput').click()">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <h4>ลากไฟล์ CSV มาวาง หรือคลิกเพื่อเลือกไฟล์</h4>
                    <p>รองรับ .csv, .txt — สูงสุด 5MB</p>
                </div>
                <input type="file" id="impFileInput" accept=".csv,.txt" style="display:none" onchange="onImportFileSelect(this)">
                
                <div class="imp-file-info" id="impFileInfo" style="display:none">
                    <i class="fas fa-file-csv" style="color:#059669;font-size:20px"></i>
                    <div style="flex:1">
                        <div id="impFileName" style="font-weight:600"></div>
                        <div id="impFileMeta" style="font-size:11px;color:var(--c3)"></div>
                    </div>
                    <button class="ci-btn ci-btn-sm ci-btn-danger" onclick="clearImportFile()" style="padding:4px 10px"><i class="fas fa-times"></i></button>
                </div>
                
                <div style="margin-top:16px">
                    <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:pointer">
                        <input type="checkbox" id="impUpdateExisting" style="accent-color:var(--accent)">
                        <span>อัปเดตผู้ใช้ที่มีอยู่แล้ว (ถ้า username ซ้ำ)</span>
                    </label>
                </div>
                
                <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px;padding-top:14px;border-top:1px solid var(--border)">
                    <button class="ci-btn ci-btn-secondary" onclick="closeImportModal()">ยกเลิก</button>
                    <button class="ci-btn ci-btn-primary" id="btnPreviewImport" onclick="previewImport()" disabled>
                        <i class="fas fa-search"></i> ตรวจสอบข้อมูล
                    </button>
                </div>
            </div>

            <!-- Step 2: Preview -->
            <div id="importStep2" style="display:none">
                <div class="imp-stats" id="impStats"></div>
                
                <div class="ci-table-wrap" style="max-height:400px;overflow-y:auto;margin-top:12px">
                    <table class="ci-table ci-table-sm" id="impPreviewTable">
                        <thead>
                            <tr>
                                <th style="width:30px">#</th>
                                <th>ชื่อ-นามสกุล</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>งาน</th>
                                <th>สถานะ</th>
                            </tr>
                        </thead>
                        <tbody id="impPreviewBody"></tbody>
                    </table>
                </div>
                
                <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px;padding-top:14px;border-top:1px solid var(--border)">
                    <button class="ci-btn ci-btn-secondary" onclick="backToStep1()">
                        <i class="fas fa-arrow-left"></i> กลับ
                    </button>
                    <button class="ci-btn ci-btn-primary" id="btnDoImport" onclick="executeImport()">
                        <i class="fas fa-file-import"></i> นำเข้าข้อมูล
                    </button>
                </div>
            </div>

            <!-- Step 3: Result -->
            <div id="importStep3" style="display:none">
                <div id="impResult"></div>
                <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px;padding-top:14px;border-top:1px solid var(--border)">
                    <button class="ci-btn ci-btn-primary" onclick="closeImportModal();loadData()">
                        <i class="fas fa-check"></i> เสร็จสิ้น
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php Layout::endContent(); ?>

<style>
.user-detail-row { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #f0f0f0; font-size:13px; }
.user-detail-row:last-child { border-bottom:none; }
.user-detail-label { color:var(--c3); font-weight:500; }
.user-detail-val { font-weight:600; color:var(--c1); text-align:right; }
.user-avatar-lg { width:64px; height:64px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:24px; font-weight:700; color:#fff; margin:0 auto 12px; }
.user-detail-name { text-align:center; font-size:16px; font-weight:700; margin-bottom:2px; }
.user-detail-role { text-align:center; margin-bottom:16px; }
.action-btns { display:flex; gap:4px; justify-content:center; }
.action-btns button { padding:4px 8px; font-size:11px; border:none; border-radius:3px; cursor:pointer; transition:all .12s; display:inline-flex; align-items:center; gap:3px; }
.btn-edit { background:#e3f2fd; color:#1976d2; }
.btn-edit:hover { background:#bbdefb; }
.btn-toggle { background:#fff3e0; color:#e65100; }
.btn-toggle:hover { background:#ffe0b2; }
.btn-view { background:var(--accent-l); color:var(--accent-d); }
.btn-view:hover { background:#d0ead9; }
.btn-delete { background:#fee2e2; color:#dc2626; }
.btn-delete:hover { background:#fecaca; }

/* ═══ Organization Hierarchy Section ═══ */
.org-section{margin-top:4px;padding:16px;background:linear-gradient(135deg,#f8fafc 0%,#f0f4ff 100%);border:1.5px solid #e2e8f0;border-radius:12px;position:relative;overflow:hidden}
.org-section::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#6C5CE7,#e65100,#1a8a5c,#2563eb);border-radius:12px 12px 0 0}
.org-section-hdr{display:flex;align-items:center;gap:8px;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid rgba(0,0,0,.06);font-size:13px;font-weight:700;color:var(--c1)}
.org-section-hdr i{color:#6C5CE7;font-size:14px}
.org-cascade{transition:border-color .2s,box-shadow .2s}
.org-cascade:not(:disabled):focus{border-color:#6C5CE7;box-shadow:0 0 0 3px rgba(108,92,231,.12)}
.org-cascade:disabled{background:#f1f5f9;color:#94a3b8;cursor:not-allowed}
.org-breadcrumb{display:flex;align-items:center;gap:6px;margin-top:10px;padding:8px 12px;background:#fff;border:1px solid #e2e8f0;border-radius:8px;font-size:11px;color:var(--c2);line-height:1.5;animation:orgFadeIn .3s ease}
.org-breadcrumb i{color:#6C5CE7;font-size:12px;flex-shrink:0}
.org-breadcrumb span{word-break:break-word}
@keyframes orgFadeIn{from{opacity:0;transform:translateY(-4px)}to{opacity:1;transform:translateY(0)}}
@media(max-width:640px){
    .org-section .ci-g2{grid-template-columns:1fr}
}
@media(max-width:768px){
    .ci-user-search{width:100%!important}
    #roleFilter{flex-wrap:nowrap;-webkit-overflow-scrolling:touch}
    .ci-table th:nth-child(4),.ci-table td:nth-child(4),
    .ci-table th:nth-child(6),.ci-table td:nth-child(6){display:none}
}
@media(max-width:480px){
    .ci-table th:nth-child(3),.ci-table td:nth-child(3){display:none}
}

/* ═══ Import/Export Dropdown ═══ */
.ci-btn-group{position:relative;display:inline-block}
.ie-dropdown{position:absolute;top:calc(100% + 6px);right:0;width:300px;background:#fff;border-radius:14px;box-shadow:0 12px 40px rgba(0,0,0,.16),0 0 0 1px rgba(0,0,0,.04);z-index:100;display:none;overflow:hidden;animation:ieSlideDown .2s ease}
.ie-dropdown.show{display:block}
@keyframes ieSlideDown{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
.ie-dropdown-head{padding:12px 16px;font-size:13px;font-weight:700;color:var(--c1);display:flex;align-items:center;gap:8px;border-bottom:1px solid #f0f0f0;background:#f9fafb}
.ie-dropdown-item{display:flex;align-items:center;gap:12px;padding:10px 16px;width:100%;border:none;background:transparent;cursor:pointer;transition:all .15s;text-align:left;font-size:13px}
.ie-dropdown-item:hover{background:#f0f4ff}
.ie-dropdown-item i{width:20px;text-align:center;font-size:14px;flex-shrink:0}
.ie-item-title{font-weight:600;color:var(--c1);font-size:13px}
.ie-item-desc{font-size:10px;color:var(--c3);margin-top:1px}
.ie-dropdown-divider{height:1px;background:#f0f0f0;margin:2px 0}

/* ═══ Import Modal Styles ═══ */
.imp-info-box{display:flex;align-items:flex-start;gap:10px;padding:12px 16px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;margin-bottom:16px;font-size:12px;color:#1e40af;line-height:1.5}
.imp-info-box i{font-size:16px;margin-top:2px;flex-shrink:0}
.imp-info-box a{color:#2563eb;font-weight:600}
.imp-info-box code{background:rgba(37,99,235,.08);padding:1px 5px;border-radius:4px;font-size:10px}
.imp-upload-zone{border:2.5px dashed #d4d4d4;border-radius:14px;padding:40px 20px;text-align:center;cursor:pointer;transition:all .3s;background:#fafafa}
.imp-upload-zone:hover,.imp-upload-zone.dragover{border-color:var(--accent);background:#f0f4ff}
.imp-upload-zone i{font-size:40px;color:#ddd;margin-bottom:10px;display:block;transition:all .3s}
.imp-upload-zone:hover i{color:var(--accent);transform:translateY(-4px)}
.imp-upload-zone h4{font-size:14px;font-weight:600;color:#333;margin-bottom:4px}
.imp-upload-zone p{font-size:11px;color:#999}
.imp-file-info{display:flex;align-items:center;gap:12px;padding:10px 14px;background:#f0fdf4;border:1px solid #86efac;border-radius:10px;margin-top:12px}
.imp-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:8px}
.imp-stat{padding:12px;background:#f9fafb;border-radius:10px;text-align:center;border:1px solid #f0f0f0}
.imp-stat-val{font-size:22px;font-weight:800;color:var(--c1)}
.imp-stat-lbl{font-size:10px;color:var(--c3);text-transform:uppercase;font-weight:600;margin-top:2px}
.imp-stat.new .imp-stat-val{color:#059669}
.imp-stat.update .imp-stat-val{color:#2563eb}
.imp-stat.error .imp-stat-val{color:#dc2626}
.imp-badge-new{background:#dcfce7;color:#059669;padding:2px 8px;border-radius:6px;font-size:10px;font-weight:700}
.imp-badge-update{background:#dbeafe;color:#2563eb;padding:2px 8px;border-radius:6px;font-size:10px;font-weight:700}
.imp-badge-error{background:#fee2e2;color:#dc2626;padding:2px 8px;border-radius:6px;font-size:10px;font-weight:700}
.imp-badge-skip{background:#f3f4f6;color:#888;padding:2px 8px;border-radius:6px;font-size:10px;font-weight:700}
.ci-table-sm th,.ci-table-sm td{padding:6px 10px;font-size:12px}
.imp-result-card{padding:24px;text-align:center;border-radius:14px;margin-bottom:12px}
.imp-result-card i{font-size:48px;margin-bottom:12px;display:block}
.imp-result-card h3{font-size:18px;font-weight:700;margin-bottom:4px}
.imp-result-detail{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:16px}
.imp-result-item{padding:10px;background:#f9fafb;border-radius:8px}
.imp-result-item .val{font-size:20px;font-weight:800}
.imp-result-item .lbl{font-size:10px;color:var(--c3);margin-top:2px}
@media(max-width:640px){
    .imp-stats{grid-template-columns:repeat(2,1fr)}
    .imp-result-detail{grid-template-columns:repeat(2,1fr)}
    .ie-dropdown{width:260px}
}
</style>

<script>
const isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;
const currentUserId = <?php echo (int)$user['id']; ?>;
const t = {
    never: '<?php echo addslashes(__("users_never")); ?>',
    no_results: '<?php echo addslashes(__("users_no_results")); ?>',
    confirm_toggle: '<?php echo addslashes(__("users_confirm_toggle")); ?>',
    confirm_deactivate: '<?php echo addslashes(__("users_confirm_deactivate")); ?>',
    confirm_activate: '<?php echo addslashes(__("users_confirm_activate")); ?>',
    active: '<?php echo addslashes(__("users_active")); ?>',
    inactive: '<?php echo addslashes(__("users_inactive")); ?>',
    updated: '<?php echo addslashes(__("users_updated")); ?>',
    created: '<?php echo addslashes(__("users_created")); ?>',
    add_title: '<?php echo addslashes(__("users_add")); ?>',
    edit_title: '<?php echo addslashes(__("users_edit")); ?>',
    pw_required: '(<?php echo addslashes(__("required")); ?>)',
    pw_optional: '(<?php echo addslashes(__("users_pw_optional")); ?>)'
};

let allUsers = [], allRoles = [], allLabs = [], allStores = [];
let currentFilter = 'all';

// Tab filter click
document.querySelectorAll('#roleFilter .ci-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('#roleFilter .ci-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        currentFilter = tab.dataset.role;
        applyFilters();
    });
});

function applyFilters() {
    const q = (document.getElementById('userSearch').value || '').toLowerCase();
    let filtered = allUsers;

    if (currentFilter === 'inactive') {
        filtered = filtered.filter(u => !parseInt(u.is_active));
    } else if (currentFilter !== 'all') {
        filtered = filtered.filter(u => u.role_name === currentFilter && parseInt(u.is_active));
    }

    if (q) {
        filtered = filtered.filter(u =>
            ((u.first_name || '') + ' ' + (u.last_name || '') + ' ' + (u.username || '') + ' ' + (u.email || '') + ' ' + (u.department || '')).toLowerCase().includes(q)
        );
    }

    renderUsers(filtered);
}

function updateStats(users) {
    const active = users.filter(u => parseInt(u.is_active));
    document.getElementById('sTotalUsers').textContent = users.length;
    document.getElementById('sActiveUsers').textContent = active.length;
    document.getElementById('sManagers').textContent = users.filter(u => u.role_name === 'lab_manager').length;
    document.getElementById('sAdmins').textContent = users.filter(u => u.role_name === 'admin').length;
    document.getElementById('cAll').textContent = users.length;
}

const roleColors = {
    admin: 'ci-badge-danger',
    ceo: 'ci-badge-info',
    lab_manager: 'ci-badge-warning',
    user: 'ci-badge-success',
    visitor: 'ci-badge-default'
};
const roleIcons = {
    admin: 'fa-crown',
    ceo: 'fa-briefcase',
    lab_manager: 'fa-user-shield',
    user: 'fa-user',
    visitor: 'fa-eye'
};
const avatarColors = {
    admin: '#c62828',
    ceo: '#1565c0',
    lab_manager: '#e65100',
    user: '#1a8a5c',
    visitor: '#757575'
};

function renderUsers(users) {
    const tbody = document.getElementById('userTableBody');
    if (!users.length) {
        tbody.innerHTML = `<tr><td colspan="7"><div class="ci-empty"><i class="fas fa-users"></i><div>${t.no_results}</div></div></td></tr>`;
        return;
    }
    tbody.innerHTML = users.map(u => {
        const active = parseInt(u.is_active);
        const isSelf = parseInt(u.id) === currentUserId;
        const initials = ((u.first_name || '')[0] || '') + ((u.last_name || '')[0] || '');
        const bgColor = avatarColors[u.role_name] || '#757575';
        return `<tr style="${!active ? 'opacity:0.5' : ''}" onclick="showDetail(${u.id})" title="Click to view details">
            <td>
                <div style="display:flex;align-items:center;gap:10px">
                    <div style="width:36px;height:36px;border-radius:50%;background:${bgColor};display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:13px;flex-shrink:0">${initials}</div>
                    <div>
                        <div style="font-weight:600;font-size:13px">${u.first_name || ''} ${u.last_name || ''}${isSelf ? ' <span class="ci-badge ci-badge-info" style="font-size:9px">You</span>' : ''}</div>
                        <div style="font-size:11px;color:var(--c3)">@${u.username} · ${u.email}</div>
                    </div>
                </div>
            </td>
            <td><span class="ci-badge ${roleColors[u.role_name] || 'ci-badge-default'}"><i class="fas ${roleIcons[u.role_name] || 'fa-user'}" style="margin-right:3px;font-size:10px"></i>${u.role_display || u.role_name}</span></td>
            <td style="font-size:12px;color:var(--c2)">${u.department || '<span class="text-muted">-</span>'}</td>
            <td style="font-size:12px;color:var(--c2)">${u.position || '<span class="text-muted">-</span>'}</td>
            <td>${active ? '<span class="ci-badge ci-badge-success"><i class="fas fa-check" style="margin-right:2px;font-size:9px"></i>' + t.active + '</span>' : '<span class="ci-badge ci-badge-default"><i class="fas fa-ban" style="margin-right:2px;font-size:9px"></i>' + t.inactive + '</span>'}</td>
            <td style="font-size:12px;color:var(--c3)">${u.last_login ? formatDate(u.last_login) : '<span class="text-muted">' + t.never + '</span>'}</td>
            <td onclick="event.stopPropagation()">
                <div class="action-btns">
                    <button class="btn-view" onclick="showDetail(${u.id})" title="View"><i class="fas fa-eye"></i></button>
                    ${isAdmin || !isSelf ? '<button class="btn-edit" onclick="openEditModal(' + u.id + ')" title="Edit"><i class="fas fa-pen"></i></button>' : ''}
                    ${isAdmin && !isSelf ? '<button class="btn-toggle" onclick="toggleUser(' + u.id + ', ' + active + ')" title="Toggle"><i class="fas fa-' + (active ? 'ban' : 'check') + '"></i></button>' : ''}
                    ${isAdmin && !isSelf && !active ? '<button class="btn-delete" onclick="deleteUser(' + u.id + ')" title="ลบผู้ใช้"><i class="fas fa-trash-alt"></i></button>' : ''}
                </div>
            </td>
        </tr>`;
    }).join('');
}

// Load data
async function loadData() {
    try {
        const [usersRes, rolesRes, orgRes] = await Promise.all([
            apiFetch('/v1/api/auth.php?action=users'),
            apiFetch('/v1/api/auth.php?action=roles'),
            apiFetch('/v1/api/auth.php?action=org_hierarchy')
        ]);
        
        if (usersRes.success) { allUsers = usersRes.data; updateStats(allUsers); applyFilters(); }
        if (rolesRes.success) {
            allRoles = rolesRes.data;
            const sel = document.getElementById('fRole');
            sel.innerHTML = '<option value="">-- <?php echo __("users_select_role"); ?> --</option>' +
                allRoles.map(r => `<option value="${r.id}">${r.display_name}</option>`).join('');
        }
        if (orgRes.success) {
            allStores = orgRes.data;
            populateOrgCenters();
        }
    } catch(e) {
        console.error('Failed to load users:', e);
        document.getElementById('userTableBody').innerHTML = '<tr><td colspan="7"><div class="ci-empty"><i class="fas fa-exclamation-triangle"></i><div>Failed to load users</div></div></td></tr>';
    }
}

// ═══ Organization Hierarchy Cascade Logic ═══
function escHtml(s) {
    if (!s) return '';
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function populateOrgCenters() {
    const centers = [...new Set(allStores.map(s => s.center_name))].sort();
    const sel = document.getElementById('fCenter');
    sel.innerHTML = '<option value=""><?php echo $lang === "th" ? "-- เลือกศูนย์ --" : "-- Select Center --"; ?></option>' +
        centers.map(c => `<option value="${escHtml(c)}">${escHtml(c)}</option>`).join('');
}

function onOrgChange(level) {
    const center = document.getElementById('fCenter').value;
    const division = document.getElementById('fDivision').value;
    const section = document.getElementById('fSection').value;
    const store = document.getElementById('fStore').value;
    
    if (level === 'center') {
        // Reset division, section, store
        const divSel = document.getElementById('fDivision');
        const secSel = document.getElementById('fSection');
        const stoSel = document.getElementById('fStore');
        
        if (center) {
            const divisions = [...new Set(allStores.filter(s => s.center_name === center).map(s => s.division_name))].sort();
            divSel.innerHTML = '<option value=""><?php echo $lang === "th" ? "-- เลือกฝ่าย --" : "-- Select Division --"; ?></option>' +
                divisions.map(d => `<option value="${escHtml(d)}">${escHtml(d)}</option>`).join('');
            divSel.disabled = false;
        } else {
            divSel.innerHTML = '<option value=""><?php echo $lang === "th" ? "-- เลือกฝ่าย --" : "-- Select Division --"; ?></option>';
            divSel.disabled = true;
        }
        secSel.innerHTML = '<option value=""><?php echo $lang === "th" ? "-- เลือกงาน --" : "-- Select Section --"; ?></option>';
        secSel.disabled = true;
        stoSel.innerHTML = '<option value=""><?php echo $lang === "th" ? "-- เลือกคลัง --" : "-- Select Store --"; ?></option>';
        stoSel.disabled = true;
    }
    
    if (level === 'division') {
        const secSel = document.getElementById('fSection');
        const stoSel = document.getElementById('fStore');
        
        if (division) {
            const sections = [...new Set(allStores.filter(s => s.center_name === center && s.division_name === division).map(s => s.section_name))].sort();
            secSel.innerHTML = '<option value=""><?php echo $lang === "th" ? "-- เลือกงาน --" : "-- Select Section --"; ?></option>' +
                sections.map(s => `<option value="${escHtml(s)}">${escHtml(s)}</option>`).join('');
            secSel.disabled = false;
        } else {
            secSel.innerHTML = '<option value=""><?php echo $lang === "th" ? "-- เลือกงาน --" : "-- Select Section --"; ?></option>';
            secSel.disabled = true;
        }
        stoSel.innerHTML = '<option value=""><?php echo $lang === "th" ? "-- เลือกคลัง --" : "-- Select Store --"; ?></option>';
        stoSel.disabled = true;
    }
    
    if (level === 'section') {
        const stoSel = document.getElementById('fStore');
        
        if (section) {
            const stores = allStores.filter(s => s.center_name === center && s.division_name === division && s.section_name === section);
            stoSel.innerHTML = '<option value=""><?php echo $lang === "th" ? "-- เลือกคลัง --" : "-- Select Store --"; ?></option>' +
                stores.map(s => `<option value="${s.id}">${escHtml(s.store_name)}</option>`).join('');
            stoSel.disabled = false;
        } else {
            stoSel.innerHTML = '<option value=""><?php echo $lang === "th" ? "-- เลือกคลัง --" : "-- Select Store --"; ?></option>';
            stoSel.disabled = true;
        }
    }
    
    // Update breadcrumb
    updateOrgBreadcrumb();
}

function updateOrgBreadcrumb() {
    const center = document.getElementById('fCenter').value;
    const division = document.getElementById('fDivision').value;
    const section = document.getElementById('fSection').value;
    const storeId = document.getElementById('fStore').value;
    
    const bc = document.getElementById('orgBreadcrumb');
    const parts = [];
    if (center) parts.push(center);
    if (division) parts.push(division);
    if (section) parts.push(section);
    if (storeId) {
        const st = allStores.find(s => String(s.id) === String(storeId));
        if (st) parts.push(st.store_name);
    }
    
    if (parts.length > 0) {
        bc.style.display = 'flex';
        document.getElementById('orgBreadcrumbText').textContent = parts.join(' › ');
    } else {
        bc.style.display = 'none';
    }
}

function setOrgFromUser(u) {
    // Try to set org hierarchy from user data
    let center = u.center_name || '';
    const division = u.department || u.division_name || '';
    const section = u.position || u.section_name || '';
    const storeId = u.store_id || '';
    
    // If no center_name from JOIN (store_id is null), derive from division
    if (!center && division) {
        const match = allStores.find(s => s.division_name === division);
        if (match) center = match.center_name;
    }
    
    // Set center
    if (center) {
        const fCenter = document.getElementById('fCenter');
        fCenter.value = center;
        onOrgChange('center');
    }
    
    // Set division
    if (division) {
        const fDiv = document.getElementById('fDivision');
        fDiv.value = division;
        onOrgChange('division');
    }
    
    // Set section
    if (section) {
        const fSec = document.getElementById('fSection');
        fSec.value = section;
        onOrgChange('section');
    }
    
    // Set store
    if (storeId) {
        document.getElementById('fStore').value = storeId;
        onOrgChange('store');
    }
}

function resetOrgFields() {
    document.getElementById('fCenter').value = '';
    document.getElementById('fDivision').innerHTML = '<option value=""><?php echo $lang === "th" ? "-- เลือกฝ่าย --" : "-- Select Division --"; ?></option>';
    document.getElementById('fDivision').disabled = true;
    document.getElementById('fSection').innerHTML = '<option value=""><?php echo $lang === "th" ? "-- เลือกงาน --" : "-- Select Section --"; ?></option>';
    document.getElementById('fSection').disabled = true;
    document.getElementById('fStore').innerHTML = '<option value=""><?php echo $lang === "th" ? "-- เลือกคลัง --" : "-- Select Store --"; ?></option>';
    document.getElementById('fStore').disabled = true;
    document.getElementById('orgBreadcrumb').style.display = 'none';
}

function openAddModal() {
    document.getElementById('fUserId').value = '';
    document.getElementById('userForm').reset();
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-plus" style="color:var(--accent);margin-right:6px"></i> ' + t.add_title;
    document.getElementById('fUsername').disabled = false;
    document.getElementById('fPassword').required = true;
    document.getElementById('pwHint').textContent = t.pw_required;
    resetOrgFields();
    document.getElementById('userModal').classList.add('show');
}

function openEditModal(userId) {
    const u = allUsers.find(x => parseInt(x.id) === userId);
    if (!u) return;
    
    document.getElementById('fUserId').value = u.id;
    document.getElementById('fFirstName').value = u.first_name || '';
    document.getElementById('fLastName').value = u.last_name || '';
    document.getElementById('fUsername').value = u.username || '';
    document.getElementById('fUsername').disabled = true;
    document.getElementById('fEmail').value = u.email || '';
    document.getElementById('fPhone').value = u.phone || '';
    document.getElementById('fRole').value = u.role_id || '';
    document.getElementById('fPassword').value = '';
    document.getElementById('fPassword').required = false;
    document.getElementById('pwHint').textContent = t.pw_optional;
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-edit" style="color:var(--accent);margin-right:6px"></i> ' + t.edit_title;
    
    // Set organization hierarchy
    resetOrgFields();
    if (allStores.length > 0) {
        setTimeout(() => setOrgFromUser(u), 50);
    }
    
    document.getElementById('userModal').classList.add('show');
}

function closeModal() {
    document.getElementById('userModal').classList.remove('show');
}

async function saveUser(e) {
    e.preventDefault();
    const userId = document.getElementById('fUserId').value;
    const isEdit = !!userId;
    
    const data = {
        first_name: document.getElementById('fFirstName').value.trim(),
        last_name: document.getElementById('fLastName').value.trim(),
        email: document.getElementById('fEmail').value.trim(),
        phone: document.getElementById('fPhone').value.trim(),
        role_id: document.getElementById('fRole').value,
        department: document.getElementById('fDivision').value.trim(),
        position: document.getElementById('fSection').value.trim(),
        store_id: document.getElementById('fStore').value || ''
    };
    
    const pw = document.getElementById('fPassword').value;
    if (pw) data.password = pw;
    
    try {
        let res;
        if (isEdit) {
            data.user_id = parseInt(userId);
            res = await apiFetch('/v1/api/auth.php?action=users_update', {
                method: 'POST', body: JSON.stringify(data)
            });
        } else {
            data.username = document.getElementById('fUsername').value.trim();
            data.password = pw;
            res = await apiFetch('/v1/api/auth.php?action=users', {
                method: 'POST', body: JSON.stringify(data)
            });
        }
        
        if (res.success) {
            closeModal();
            showToast(isEdit ? t.updated : t.created, 'success');
            loadData();
        } else {
            showToast(res.error || 'Error', 'danger');
        }
    } catch(e) {
        showToast(e.message || 'Error', 'danger');
    }
}

async function toggleUser(userId, currentlyActive) {
    const msg = currentlyActive ? t.confirm_deactivate : t.confirm_activate;
    if (!confirm(msg)) return;
    
    try {
        const res = await apiFetch('/v1/api/auth.php?action=users_toggle', {
            method: 'POST', body: JSON.stringify({ user_id: userId })
        });
        if (res.success) {
            showToast(t.updated, 'success');
            loadData();
        } else {
            showToast(res.error || 'Error', 'danger');
        }
    } catch(e) {
        showToast(e.message || 'Error', 'danger');
    }
}

async function deleteUser(userId) {
    const u = allUsers.find(x => parseInt(x.id) === userId);
    if (!u) return;
    const name = (u.first_name || '') + ' ' + (u.last_name || '');
    const msg = `⚠️ ลบผู้ใช้ "${name}" (@${u.username}) อย่างถาวร?\n\nการดำเนินการนี้ไม่สามารถย้อนกลับได้!\nพิมพ์ DELETE เพื่อยืนยัน`;
    const answer = prompt(msg);
    if (answer !== 'DELETE') {
        if (answer !== null) showToast('ยกเลิก: กรุณาพิมพ์ DELETE เพื่อยืนยัน', 'danger');
        return;
    }
    
    try {
        const res = await apiFetch('/v1/api/auth.php?action=users_delete', {
            method: 'POST', body: JSON.stringify({ user_id: userId })
        });
        if (res.success) {
            showToast(res.message || 'ลบผู้ใช้สำเร็จ', 'success');
            loadData();
        } else {
            showToast(res.error || 'Error', 'danger');
        }
    } catch(e) {
        showToast(e.message || 'Error', 'danger');
    }
}

function showDetail(userId) {
    const u = allUsers.find(x => parseInt(x.id) === userId);
    if (!u) return;
    
    const initials = ((u.first_name || '')[0] || '') + ((u.last_name || '')[0] || '');
    const bgColor = avatarColors[u.role_name] || '#757575';
    const active = parseInt(u.is_active);
    
    document.getElementById('detailContent').innerHTML = `
        <div class="user-avatar-lg" style="background:${bgColor}">${initials}</div>
        <div class="user-detail-name">${u.first_name || ''} ${u.last_name || ''}</div>
        <div class="user-detail-role">
            <span class="ci-badge ${roleColors[u.role_name] || 'ci-badge-default'}"><i class="fas ${roleIcons[u.role_name] || 'fa-user'}" style="margin-right:3px;font-size:10px"></i>${u.role_display || u.role_name}</span>
            &nbsp;
            ${active ? '<span class="ci-badge ci-badge-success">' + t.active + '</span>' : '<span class="ci-badge ci-badge-default">' + t.inactive + '</span>'}
        </div>
        <div style="border-top:1px solid var(--border);padding-top:12px">
            <div class="user-detail-row"><span class="user-detail-label"><i class="fas fa-at" style="width:16px;margin-right:6px;color:var(--c3)"></i>Username</span><span class="user-detail-val">@${u.username}</span></div>
            <div class="user-detail-row"><span class="user-detail-label"><i class="fas fa-envelope" style="width:16px;margin-right:6px;color:var(--c3)"></i>Email</span><span class="user-detail-val">${u.email || '-'}</span></div>
            <div class="user-detail-row"><span class="user-detail-label"><i class="fas fa-phone" style="width:16px;margin-right:6px;color:var(--c3)"></i><?php echo __('users_phone'); ?></span><span class="user-detail-val">${u.phone || '-'}</span></div>
            <div class="user-detail-row"><span class="user-detail-label"><i class="fas fa-building" style="width:16px;margin-right:6px;color:#6C5CE7"></i><?php echo $lang === 'th' ? 'ศูนย์' : 'Center'; ?></span><span class="user-detail-val">${u.center_name || '-'}</span></div>
            <div class="user-detail-row"><span class="user-detail-label"><i class="fas fa-sitemap" style="width:16px;margin-right:6px;color:#e65100"></i><?php echo $lang === 'th' ? 'ฝ่าย' : 'Division'; ?></span><span class="user-detail-val">${u.department || '-'}</span></div>
            <div class="user-detail-row"><span class="user-detail-label"><i class="fas fa-layer-group" style="width:16px;margin-right:6px;color:#1a8a5c"></i><?php echo $lang === 'th' ? 'งาน' : 'Section'; ?></span><span class="user-detail-val">${u.position || '-'}</span></div>
            <div class="user-detail-row"><span class="user-detail-label"><i class="fas fa-warehouse" style="width:16px;margin-right:6px;color:#2563eb"></i><?php echo $lang === 'th' ? 'คลัง' : 'Store'; ?></span><span class="user-detail-val">${u.store_name || '-'}</span></div>
            <div class="user-detail-row"><span class="user-detail-label"><i class="fas fa-clock" style="width:16px;margin-right:6px;color:var(--c3)"></i><?php echo __('users_last_login'); ?></span><span class="user-detail-val">${u.last_login ? formatDate(u.last_login) : t.never}</span></div>
            <div class="user-detail-row"><span class="user-detail-label"><i class="fas fa-calendar" style="width:16px;margin-right:6px;color:var(--c3)"></i><?php echo __('users_created_at'); ?></span><span class="user-detail-val">${formatDate(u.created_at)}</span></div>
        </div>
        <div style="display:flex;gap:8px;justify-content:center;margin-top:16px;padding-top:14px;border-top:1px solid var(--border)">
            ${isAdmin || parseInt(u.id) !== currentUserId ? '<button class="ci-btn ci-btn-outline ci-btn-sm" onclick="closeDetailModal();openEditModal(' + u.id + ')"><i class="fas fa-pen"></i> <?php echo __("edit"); ?></button>' : ''}
            ${isAdmin && parseInt(u.id) !== currentUserId ? '<button class="ci-btn ' + (active ? 'ci-btn-danger' : 'ci-btn-primary') + ' ci-btn-sm" onclick="closeDetailModal();toggleUser(' + u.id + ',' + active + ')"><i class="fas fa-' + (active ? 'ban' : 'check') + '"></i> ' + (active ? '<?php echo __("users_deactivate"); ?>' : '<?php echo __("users_activate"); ?>') + '</button>' : ''}
            ${isAdmin && parseInt(u.id) !== currentUserId && !active ? '<button class="ci-btn ci-btn-danger ci-btn-sm" onclick="closeDetailModal();deleteUser(' + u.id + ')" style="background:#dc2626"><i class="fas fa-trash-alt"></i> ลบถาวร</button>' : ''}
        </div>
    `;
    document.getElementById('detailModal').classList.add('show');
}

function closeDetailModal() {
    document.getElementById('detailModal').classList.remove('show');
}

// Toast notification
function showToast(msg, type = 'success') {
    const el = document.createElement('div');
    el.className = 'ci-alert ci-alert-' + type + ' ci-fade';
    el.style.cssText = 'position:fixed;top:60px;right:20px;z-index:999;min-width:280px;max-width:400px;box-shadow:0 4px 16px rgba(0,0,0,.15)';
    el.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i> ' + msg;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 3000);
}

// Close modals on backdrop click
document.getElementById('userModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal(); });
document.getElementById('detailModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeDetailModal(); });

// ═══════════════════════════════════════════════════
// Import / Export Functions
// ═══════════════════════════════════════════════════

// Dropdown toggle
function toggleImportExport() {
    const dd = document.getElementById('ieDropdown');
    dd.classList.toggle('show');
}
// Close dropdown on outside click
document.addEventListener('click', e => {
    const dd = document.getElementById('ieDropdown');
    if (dd && !e.target.closest('.ci-btn-group')) dd.classList.remove('show');
});

// Export users as CSV
function exportUsersCSV() {
    document.getElementById('ieDropdown').classList.remove('show');
    window.location.href = '/v1/api/user_import.php?action=export&format=csv';
}

// Export users as JSON
function exportUsersJSON() {
    document.getElementById('ieDropdown').classList.remove('show');
    window.location.href = '/v1/api/user_import.php?action=export&format=json';
}

// Download template
function downloadTemplate() {
    document.getElementById('ieDropdown').classList.remove('show');
    window.location.href = '/v1/api/user_import.php?action=export_template';
}

// Import modal
let importFile = null;

function openImportModal() {
    document.getElementById('ieDropdown').classList.remove('show');
    importFile = null;
    document.getElementById('impFileInput').value = '';
    document.getElementById('impFileInfo').style.display = 'none';
    document.getElementById('impDropZone').style.display = '';
    document.getElementById('btnPreviewImport').disabled = true;
    document.getElementById('importStep1').style.display = '';
    document.getElementById('importStep2').style.display = 'none';
    document.getElementById('importStep3').style.display = 'none';
    document.getElementById('impUpdateExisting').checked = false;
    document.getElementById('importModal').classList.add('show');
}
function closeImportModal() {
    document.getElementById('importModal').classList.remove('show');
    importFile = null;
}
document.getElementById('importModal')?.addEventListener('click', e => { if (e.target === e.currentTarget) closeImportModal(); });

// Drag & drop
const impDrop = document.getElementById('impDropZone');
if (impDrop) {
    impDrop.addEventListener('dragover', e => { e.preventDefault(); impDrop.classList.add('dragover'); });
    impDrop.addEventListener('dragleave', () => impDrop.classList.remove('dragover'));
    impDrop.addEventListener('drop', e => {
        e.preventDefault();
        impDrop.classList.remove('dragover');
        if (e.dataTransfer.files.length) {
            document.getElementById('impFileInput').files = e.dataTransfer.files;
            onImportFileSelect(document.getElementById('impFileInput'));
        }
    });
}

function onImportFileSelect(input) {
    if (!input.files.length) return;
    const f = input.files[0];
    const ext = f.name.split('.').pop().toLowerCase();
    if (!['csv', 'txt'].includes(ext)) {
        showToast('กรุณาเลือกไฟล์ CSV เท่านั้น', 'danger');
        return;
    }
    if (f.size > 5 * 1024 * 1024) {
        showToast('ไฟล์ใหญ่เกิน 5MB', 'danger');
        return;
    }
    importFile = f;
    document.getElementById('impFileName').textContent = f.name;
    document.getElementById('impFileMeta').textContent = formatFileSize(f.size) + ' · ' + ext.toUpperCase();
    document.getElementById('impFileInfo').style.display = 'flex';
    document.getElementById('impDropZone').style.display = 'none';
    document.getElementById('btnPreviewImport').disabled = false;
}

function clearImportFile() {
    importFile = null;
    document.getElementById('impFileInput').value = '';
    document.getElementById('impFileInfo').style.display = 'none';
    document.getElementById('impDropZone').style.display = '';
    document.getElementById('btnPreviewImport').disabled = true;
}

function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
}

// Preview import
async function previewImport() {
    if (!importFile) return;
    
    const btn = document.getElementById('btnPreviewImport');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังตรวจสอบ...';
    
    try {
        const fd = new FormData();
        fd.append('csv_file', importFile);
        
        const res = await fetch('/v1/api/user_import.php?action=import_preview', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
        });
        const data = await res.json();
        
        if (!data.success) throw new Error(data.error || 'Preview failed');
        
        // Show stats
        const s = data.stats;
        document.getElementById('impStats').innerHTML = `
            <div class="imp-stat"><div class="imp-stat-val">${s.total}</div><div class="imp-stat-lbl">ทั้งหมด</div></div>
            <div class="imp-stat new"><div class="imp-stat-val">${s.new}</div><div class="imp-stat-lbl">เพิ่มใหม่</div></div>
            <div class="imp-stat update"><div class="imp-stat-val">${s.update}</div><div class="imp-stat-lbl">อัปเดต</div></div>
            <div class="imp-stat error"><div class="imp-stat-val">${s.error}</div><div class="imp-stat-lbl">ข้อผิดพลาด</div></div>
        `;
        
        // Render preview table
        const tbody = document.getElementById('impPreviewBody');
        tbody.innerHTML = data.preview.map(p => {
            const badge = p.status === 'new' ? 'imp-badge-new' : p.status === 'update' ? 'imp-badge-update' : 'imp-badge-error';
            const label = p.status === 'new' ? 'เพิ่มใหม่' : p.status === 'update' ? 'อัปเดต' : 'ข้อผิดพลาด';
            const notes = [...(p.errors || []), ...(p.warnings || [])].join('<br>');
            return `<tr title="${notes.replace(/<br>/g, '\n').replace(/<[^>]+>/g, '')}">
                <td>${p.row}</td>
                <td style="font-size:12px;font-weight:500">${escHtml(p.name)}</td>
                <td><code style="font-size:11px">${escHtml(p.username)}</code></td>
                <td style="font-size:11px">${escHtml(p.email)}</td>
                <td><span class="ci-badge ${roleColors[p.role] || 'ci-badge-default'}" style="font-size:10px">${escHtml(p.role)}</span></td>
                <td style="font-size:11px;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${escHtml(p.unit)}</td>
                <td><span class="${badge}">${label}</span></td>
            </tr>`;
        }).join('');
        
        // Show step 2
        document.getElementById('importStep1').style.display = 'none';
        document.getElementById('importStep2').style.display = '';
        
        // Disable import if all errors
        document.getElementById('btnDoImport').disabled = (s.new + s.update === 0);
        
    } catch (e) {
        showToast(e.message || 'Preview error', 'danger');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-search"></i> ตรวจสอบข้อมูล';
    }
}

function backToStep1() {
    document.getElementById('importStep1').style.display = '';
    document.getElementById('importStep2').style.display = 'none';
}

// Execute import
async function executeImport() {
    if (!importFile) return;
    
    const btn = document.getElementById('btnDoImport');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังนำเข้า...';
    
    try {
        const fd = new FormData();
        fd.append('csv_file', importFile);
        fd.append('update_existing', document.getElementById('impUpdateExisting').checked ? '1' : '0');
        fd.append('default_password', '123');
        
        const res = await fetch('/v1/api/user_import.php?action=import', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
        });
        const data = await res.json();
        
        if (!data.success) throw new Error(data.error || 'Import failed');
        
        const r = data.data;
        const hasErrors = (r.errors && r.errors.length > 0);
        
        document.getElementById('impResult').innerHTML = `
            <div class="imp-result-card" style="background:${hasErrors ? '#fff7ed' : '#f0fdf4'};color:${hasErrors ? '#9a3412' : '#065f46'}">
                <i class="fas fa-${hasErrors ? 'exclamation-triangle' : 'check-circle'}"></i>
                <h3>${hasErrors ? 'นำเข้าเสร็จสิ้น (มีข้อผิดพลาดบางส่วน)' : 'นำเข้าสำเร็จ!'}</h3>
                <p>ระบบได้ประมวลผลข้อมูลผู้ใช้เรียบร้อยแล้ว</p>
                <div class="imp-result-detail">
                    <div class="imp-result-item"><div class="val" style="color:#059669">${r.inserted}</div><div class="lbl">เพิ่มใหม่</div></div>
                    <div class="imp-result-item"><div class="val" style="color:#2563eb">${r.updated}</div><div class="lbl">อัปเดต</div></div>
                    <div class="imp-result-item"><div class="val" style="color:#888">${r.skipped}</div><div class="lbl">ข้าม</div></div>
                </div>
            </div>
            ${hasErrors ? '<div style="margin-top:8px;padding:10px 14px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;font-size:12px;color:#991b1b"><strong>ข้อผิดพลาด:</strong><ul style="margin:4px 0 0 16px">' + r.errors.map(e => '<li>' + escHtml(e) + '</li>').join('') + '</ul></div>' : ''}
            <p style="margin-top:12px;font-size:12px;color:var(--c3)"><i class="fas fa-key" style="margin-right:4px"></i> รหัสผ่านเริ่มต้น: <code>123</code> — ผู้ใช้ควรเปลี่ยนรหัสผ่านหลังเข้าสู่ระบบครั้งแรก</p>
        `;
        
        document.getElementById('importStep2').style.display = 'none';
        document.getElementById('importStep3').style.display = '';
        
    } catch (e) {
        showToast(e.message || 'Import error', 'danger');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-file-import"></i> นำเข้าข้อมูล';
    }
}

loadData();
</script>
</body></html>
