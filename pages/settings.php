<?php
require_once __DIR__ . '/../includes/layout.php';
$user = Auth::getCurrentUser();
if (!$user) { header('Location: /v1/pages/login.php'); exit; }
if ($user['role_name'] !== 'admin') { header('Location: /v1/'); exit; }
$lang = I18n::getCurrentLang();
Layout::head($lang==='th'?'ตั้งค่าระบบ':'System Settings');
?>
<style>
.settings-wrap{max-width:800px}
.setting-section{margin-bottom:24px}
.setting-section-title{font-size:15px;font-weight:700;color:var(--c1);margin-bottom:4px;display:flex;align-items:center;gap:8px}
.setting-section-desc{font-size:12px;color:var(--c3);margin-bottom:12px}
.setting-row{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:14px 0;border-bottom:1px solid var(--border)}
.setting-row:last-child{border-bottom:none}
.setting-info{flex:1;min-width:0}
.setting-label{font-size:13px;font-weight:600;color:var(--c1)}
.setting-desc{font-size:11px;color:var(--c3);margin-top:2px}
.setting-control{flex-shrink:0;display:flex;align-items:center;gap:8px}
/* Toggle switch */
.toggle{position:relative;width:44px;height:24px;flex-shrink:0}
.toggle input{opacity:0;width:0;height:0}
.toggle-slider{position:absolute;cursor:pointer;inset:0;background:#ccc;border-radius:24px;transition:.2s}
.toggle-slider::before{content:'';position:absolute;height:18px;width:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.2s;box-shadow:0 1px 3px rgba(0,0,0,.15)}
.toggle input:checked+.toggle-slider{background:var(--accent)}
.toggle input:checked+.toggle-slider::before{transform:translateX(20px)}
.toggle input:disabled+.toggle-slider{opacity:.4;cursor:not-allowed}
.num-input{width:90px;text-align:center;font-weight:600}
.lockout-sub{padding-left:20px;border-left:3px solid var(--accent-l);margin-left:4px;transition:opacity .2s}
.lockout-sub.disabled{opacity:.45;pointer-events:none}
.toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(100px);background:#1a1a2e;color:#fff;padding:12px 24px;border-radius:8px;font-size:13px;font-weight:500;display:flex;align-items:center;gap:8px;z-index:9999;opacity:0;transition:all .3s ease}
.toast.show{transform:translateX(-50%) translateY(0);opacity:1}
.toast.success{background:#0d6832}
.toast.error{background:#c62828}
@media(max-width:768px){
    .setting-row{flex-direction:column;align-items:flex-start;gap:8px}
    .setting-control{align-self:flex-end}
}
/* ═══ Section Save Footer ═══ */
.section-save{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:var(--bg);border-top:1px solid var(--border);border-radius:0 0 12px 12px}
.section-save .save-hint{font-size:11px;color:#f59e0b;display:flex;align-items:center;gap:5px;opacity:0;transition:opacity .25s}
.section-save .save-hint.show{opacity:1}
.section-save .save-hint i{font-size:12px}
.section-save-btn{padding:7px 18px;border:none;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;transition:all .15s;font-family:inherit;background:var(--accent);color:#fff}
.section-save-btn:hover{filter:brightness(1.08);box-shadow:0 2px 8px rgba(26,138,92,.25)}
.section-save-btn:disabled{opacity:.5;cursor:not-allowed;filter:none;box-shadow:none}
/* ═══ Confirm Modal ═══ */
.confirm-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);backdrop-filter:blur(2px);z-index:10000;display:flex;align-items:center;justify-content:center;opacity:0;visibility:hidden;transition:all .2s}
.confirm-overlay.show{opacity:1;visibility:visible}
.confirm-box{background:#fff;border-radius:16px;width:92%;max-width:440px;box-shadow:0 20px 60px rgba(0,0,0,.2);transform:scale(.92) translateY(10px);transition:transform .25s cubic-bezier(.34,1.56,.64,1);overflow:hidden}
.confirm-overlay.show .confirm-box{transform:scale(1) translateY(0)}
.confirm-header{padding:24px 24px 0;display:flex;align-items:center;gap:12px}
.confirm-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.confirm-icon.warn{background:#fef3c7;color:#d97706}
.confirm-icon.danger{background:#fee2e2;color:#dc2626}
.confirm-icon.info{background:#dbeafe;color:#2563eb}
.confirm-title{font-size:16px;font-weight:700;color:#0f172a}
.confirm-body{padding:14px 24px 20px;font-size:13px;color:#64748b;line-height:1.6}
.confirm-changes{margin-top:10px;padding:10px 12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;max-height:180px;overflow-y:auto}
.confirm-change-item{display:flex;align-items:center;gap:8px;padding:5px 0;font-size:12px;border-bottom:1px solid #f1f5f9}
.confirm-change-item:last-child{border-bottom:none}
.confirm-change-item .ck-icon{width:18px;height:18px;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:9px;flex-shrink:0;background:#f1f5f9;color:#64748b}
.confirm-change-item .ck-label{font-weight:600;color:#334155;flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.confirm-change-item .ck-old{color:#ef4444;text-decoration:line-through;font-size:11px;max-width:90px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.confirm-change-item .ck-arrow{color:#94a3b8;font-size:9px;flex-shrink:0}
.confirm-change-item .ck-new{color:#22c55e;font-weight:700;font-size:11px;max-width:90px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.confirm-footer{padding:0 24px 24px;display:flex;justify-content:flex-end;gap:8px}
.confirm-btn{padding:9px 22px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;border:none;font-family:inherit;transition:all .12s}
.confirm-btn.cancel{background:#f1f5f9;color:#64748b}
.confirm-btn.cancel:hover{background:#e2e8f0}
.confirm-btn.ok{background:var(--accent);color:#fff}
.confirm-btn.ok:hover{filter:brightness(1.1)}
.confirm-btn.danger{background:#dc2626;color:#fff}
.confirm-btn.danger:hover{background:#b91c1c}
/* ═══ Iframe Config Section ═══ */
.iframe-config-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
@media(max-width:640px){.iframe-config-grid{grid-template-columns:1fr}}
.iframe-config-field label{font-size:12px;font-weight:600;color:var(--c1);display:block;margin-bottom:4px}
.iframe-config-field select,.iframe-config-field input,.iframe-config-field textarea{width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:8px;font-size:13px;background:var(--bg);color:var(--c1)}
.iframe-config-field select:focus,.iframe-config-field input:focus,.iframe-config-field textarea:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(108,92,231,.1)}
.iframe-config-field .hint{font-size:10px;color:var(--c3);margin-top:3px}
.iframe-tool{margin-top:16px;padding:16px;background:#fffbeb;border:1.5px solid #fde68a;border-radius:12px}
.iframe-tool h4{font-size:13px;font-weight:700;color:#92400e;margin-bottom:4px;display:flex;align-items:center;gap:8px}
.iframe-tool h4 i{font-size:13px}
.iframe-tool .hint{font-size:11px;color:#92400e;margin-bottom:12px}
.iframe-tool textarea{width:100%;min-height:70px;font-family:'Courier New',monospace;font-size:12px;padding:10px;border:1px solid var(--border);border-radius:8px;resize:vertical;background:#fff;color:var(--c1)}
.iframe-tool textarea:focus{outline:none;border-color:#d97706;box-shadow:0 0 0 3px rgba(217,119,6,.1)}
.iframe-tool .result-area textarea{background:#f0fdf4;border-color:#86efac}
</style>
<body>
<?php Layout::sidebar('settings'); Layout::beginContent(); ?>
<?php Layout::pageHeader(
    $lang==='th'?'ตั้งค่าระบบ':'System Settings',
    'fas fa-sliders-h',
    $lang==='th'?'จัดการการตั้งค่าความปลอดภัยและระบบ':'Manage security & system settings'
); ?>

<!-- ═══ Confirm Dialog ═══ -->
<div class="confirm-overlay" id="confirmOverlay" onclick="if(event.target===this)closeConfirm()">
    <div class="confirm-box">
        <div class="confirm-header">
            <div class="confirm-icon" id="confirmIcon"><i id="confirmIconI" class="fas fa-question-circle"></i></div>
            <div class="confirm-title" id="confirmTitle"></div>
        </div>
        <div class="confirm-body" id="confirmBody"></div>
        <div class="confirm-footer">
            <button class="confirm-btn cancel" onclick="closeConfirm()"><?php echo $lang==='th'?'ยกเลิก':'Cancel'; ?></button>
            <button class="confirm-btn ok" id="confirmOkBtn" onclick="doConfirm()"><?php echo $lang==='th'?'ยืนยัน':'Confirm'; ?></button>
        </div>
    </div>
</div>

<div class="settings-wrap" id="settingsWrap">

    <!-- ═══════════════════════════════════════ -->
    <!-- SECURITY SECTION                       -->
    <!-- ═══════════════════════════════════════ -->
    <div class="setting-section">
        <div class="ci-card">
            <div class="ci-card-head">
                <span><i class="fas fa-shield-alt" style="color:#c62828"></i> <?php echo $lang==='th'?'ความปลอดภัย':'Security'; ?></span>
            </div>
            <div class="ci-card-body">
                <div class="setting-row">
                    <div class="setting-info">
                        <div class="setting-label"><i class="fas fa-lock" style="color:#e65100;margin-right:4px"></i> <?php echo $lang==='th'?'การล็อคบัญชีอัตโนมัติ':'Account Auto-Lock'; ?></div>
                        <div class="setting-desc"><?php echo $lang==='th'?'ล็อคบัญชีเมื่อกรอกรหัสผ่านผิดเกินจำนวนครั้งที่กำหนด':'Lock account after too many failed login attempts'; ?></div>
                    </div>
                    <div class="setting-control">
                        <label class="toggle"><input type="checkbox" id="account_lockout_enabled" data-key="account_lockout_enabled" data-section="security" data-label="<?php echo $lang==='th'?'ล็อคบัญชีอัตโนมัติ':'Auto-Lock'; ?>"><span class="toggle-slider"></span></label>
                    </div>
                </div>
                <div class="lockout-sub" id="lockoutSub">
                    <div class="setting-row">
                        <div class="setting-info">
                            <div class="setting-label"><?php echo $lang==='th'?'จำนวนครั้งสูงสุดที่กรอกผิดได้':'Max Failed Attempts'; ?></div>
                            <div class="setting-desc"><?php echo $lang==='th'?'ล็อคบัญชีเมื่อกรอกรหัสผ่านผิดเกินจำนวนนี้':'Lock account after this many wrong passwords'; ?></div>
                        </div>
                        <div class="setting-control">
                            <input type="number" id="account_lockout_max_attempts" data-key="account_lockout_max_attempts" data-section="security" data-label="<?php echo $lang==='th'?'จำนวนครั้งสูงสุด':'Max Attempts'; ?>" class="ci-input num-input" min="1" max="20" value="5">
                            <span style="font-size:12px;color:var(--c3)"><?php echo $lang==='th'?'ครั้ง':'times'; ?></span>
                        </div>
                    </div>
                    <div class="setting-row">
                        <div class="setting-info">
                            <div class="setting-label"><?php echo $lang==='th'?'ระยะเวลาล็อค':'Lock Duration'; ?></div>
                            <div class="setting-desc"><?php echo $lang==='th'?'ระยะเวลาที่ล็อคบัญชีหลังจากกรอกผิดเกิน':'How long the account stays locked'; ?></div>
                        </div>
                        <div class="setting-control">
                            <input type="number" id="account_lockout_duration" data-key="account_lockout_duration" data-section="security" data-label="<?php echo $lang==='th'?'ระยะเวลาล็อค':'Lock Duration'; ?>" class="ci-input num-input" min="1" max="1440" value="30">
                            <span style="font-size:12px;color:var(--c3)"><?php echo $lang==='th'?'นาที':'min'; ?></span>
                        </div>
                    </div>
                </div>
                <div style="height:6px"></div>
                <div class="setting-row">
                    <div class="setting-info">
                        <div class="setting-label"><i class="fas fa-user-plus" style="color:#1565c0;margin-right:4px"></i> <?php echo $lang==='th'?'เปิดให้ลงทะเบียนเอง':'Allow Self-Registration'; ?></div>
                        <div class="setting-desc"><?php echo $lang==='th'?'ผู้ใช้สามารถสมัครบัญชีเองผ่านหน้า Register':'Users can register accounts via the registration page'; ?></div>
                    </div>
                    <div class="setting-control">
                        <label class="toggle"><input type="checkbox" id="allow_registration" data-key="allow_registration" data-section="security" data-label="<?php echo $lang==='th'?'ลงทะเบียนเอง':'Self-Register'; ?>"><span class="toggle-slider"></span></label>
                    </div>
                </div>
                <div class="setting-row">
                    <div class="setting-info">
                        <div class="setting-label"><i class="fas fa-flask-vial" style="color:#0d9488;margin-right:4px"></i> <?php echo $lang==='th'?'บัญชีทดลองใช้งาน':'Demo Accounts'; ?></div>
                        <div class="setting-desc"><?php echo $lang==='th'?'แสดงรายชื่อบัญชีทดลองใช้งานในหน้า Login เพื่อให้เลือกเข้าสู่ระบบได้ง่าย':'Show demo account selector on the login page for easy access'; ?></div>
                    </div>
                    <div class="setting-control">
                        <span class="demo-status-label" id="demoStatusLabel" style="font-size:11px;font-weight:600;margin-right:6px"></span>
                        <label class="toggle"><input type="checkbox" id="demo_accounts_enabled" data-key="demo_accounts_enabled" data-section="security" data-label="<?php echo $lang==='th'?'บัญชีทดลอง':'Demo Accounts'; ?>"><span class="toggle-slider"></span></label>
                    </div>
                </div>
                <div class="setting-row">
                    <div class="setting-info">
                        <div class="setting-label"><i class="fas fa-clock" style="color:#6a1b9a;margin-right:4px"></i> Session Timeout</div>
                        <div class="setting-desc"><?php echo $lang==='th'?'ระยะเวลา session หมดอายุ (นาที)':'Session expiry time in minutes'; ?></div>
                    </div>
                    <div class="setting-control">
                        <input type="number" id="session_timeout" data-key="session_timeout" data-section="security" data-label="Session Timeout" class="ci-input num-input" min="5" max="43200" value="1440">
                        <span style="font-size:12px;color:var(--c3)"><?php echo $lang==='th'?'นาที':'min'; ?></span>
                    </div>
                </div>
            </div>
            <div class="section-save">
                <div class="save-hint" id="hintSecurity"><i class="fas fa-exclamation-circle"></i> <?php echo $lang==='th'?'มีการเปลี่ยนแปลงที่ยังไม่ได้บันทึก':'Unsaved changes'; ?></div>
                <button class="section-save-btn" onclick="confirmSave('security')" id="btnSecurity"><i class="fas fa-save"></i> <?php echo $lang==='th'?'บันทึกความปลอดภัย':'Save Security'; ?></button>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════ -->
    <!-- GENERAL SECTION                        -->
    <!-- ═══════════════════════════════════════ -->
    <div class="setting-section">
        <div class="ci-card">
            <div class="ci-card-head">
                <span><i class="fas fa-cog" style="color:var(--accent)"></i> <?php echo $lang==='th'?'ทั่วไป':'General'; ?></span>
            </div>
            <div class="ci-card-body">
                <div class="setting-row">
                    <div class="setting-info"><div class="setting-label"><?php echo $lang==='th'?'ชื่อระบบ (ไทย)':'System Name (Thai)'; ?></div></div>
                    <div class="setting-control"><input type="text" id="app_name_th" data-key="app_name_th" data-section="general" data-label="<?php echo $lang==='th'?'ชื่อระบบ (TH)':'Name TH'; ?>" class="ci-input" style="width:220px" value=""></div>
                </div>
                <div class="setting-row">
                    <div class="setting-info"><div class="setting-label"><?php echo $lang==='th'?'ชื่อระบบ (EN)':'System Name (EN)'; ?></div></div>
                    <div class="setting-control"><input type="text" id="app_name_en" data-key="app_name_en" data-section="general" data-label="<?php echo $lang==='th'?'ชื่อระบบ (EN)':'Name EN'; ?>" class="ci-input" style="width:220px" value=""></div>
                </div>
                <div class="setting-row">
                    <div class="setting-info"><div class="setting-label"><?php echo $lang==='th'?'ชื่อหน่วยงาน':'Organization Name'; ?></div></div>
                    <div class="setting-control"><input type="text" id="org_name" data-key="org_name" data-section="general" data-label="<?php echo $lang==='th'?'หน่วยงาน':'Organization'; ?>" class="ci-input" style="width:320px" value=""></div>
                </div>
            </div>
            <div class="section-save">
                <div class="save-hint" id="hintGeneral"><i class="fas fa-exclamation-circle"></i> <?php echo $lang==='th'?'มีการเปลี่ยนแปลงที่ยังไม่ได้บันทึก':'Unsaved changes'; ?></div>
                <button class="section-save-btn" onclick="confirmSave('general')" id="btnGeneral"><i class="fas fa-save"></i> <?php echo $lang==='th'?'บันทึกทั่วไป':'Save General'; ?></button>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════ -->
    <!-- 3D IFRAME SECTION                      -->
    <!-- ═══════════════════════════════════════ -->
    <div class="setting-section">
        <div class="ci-card">
            <div class="ci-card-head">
                <span><i class="fas fa-cube" style="color:#6C5CE7"></i> Iframe / 3D Embed Config</span>
            </div>
            <div class="ci-card-body">
                <div class="setting-section-desc"><?php echo $lang==='th'?'กำหนดค่าพารามิเตอร์สำหรับ iframe จาก Kiri Engine และ embed อื่น ๆ':'Configure iframe parameters for Kiri Engine and other embeds'; ?></div>
                <div style="margin-bottom:16px">
                    <div style="font-size:13px;font-weight:600;color:var(--c1);margin-bottom:10px"><i class="fas fa-cog" style="color:#6C5CE7;margin-right:4px"></i> Kiri Engine Parameters</div>
                    <div class="iframe-config-grid">
                        <div class="iframe-config-field">
                            <label>Background Theme</label>
                            <select id="iframe_kiri_bg_theme" data-key="iframe_kiri_bg_theme" data-section="3d_iframe" data-label="BG Theme">
                                <option value="transparent">transparent</option><option value="dark">dark</option><option value="light">light</option><option value="gradient">gradient</option>
                            </select>
                        </div>
                        <div class="iframe-config-field">
                            <label>Auto Spin Model</label>
                            <select id="iframe_kiri_auto_spin" data-key="iframe_kiri_auto_spin" data-section="3d_iframe" data-label="Auto Spin">
                                <option value="1"><?php echo $lang==='th'?'เปิด':'On'; ?></option><option value="0"><?php echo $lang==='th'?'ปิด':'Off'; ?></option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="iframe-config-field" style="margin-bottom:16px">
                    <label><?php echo $lang==='th'?'พารามิเตอร์เพิ่มเติม':'Additional Parameters'; ?> (key=value, &amp;)</label>
                    <input type="text" id="iframe_default_params" data-key="iframe_default_params" data-section="3d_iframe" data-label="Extra Params" style="font-family:monospace;font-size:12px" placeholder="bg_theme=transparent&auto_spin_model=1">
                    <div class="hint"><?php echo $lang==='th'?'เช่น':'e.g.'; ?> userId=1665127&amp;bg_theme=transparent&amp;auto_spin_model=1</div>
                </div>
                <div style="margin-bottom:16px">
                    <div style="font-size:13px;font-weight:600;color:var(--c1);margin-bottom:10px"><i class="fas fa-code" style="color:#6C5CE7;margin-right:4px"></i> Iframe Attributes</div>
                    <div class="iframe-config-field" style="margin-bottom:12px">
                        <label>Default Attributes</label>
                        <textarea id="iframe_default_attrs" data-key="iframe_default_attrs" data-section="3d_iframe" data-label="Attributes" rows="2" style="font-family:monospace;font-size:12px" placeholder='frameborder="0" allowfullscreen ...'></textarea>
                    </div>
                    <div class="iframe-config-grid">
                        <div class="iframe-config-field">
                            <label>Width (px)</label>
                            <input type="number" id="iframe_width" data-key="iframe_width" data-section="3d_iframe" data-label="Width" class="ci-input" min="100" max="1920">
                        </div>
                        <div class="iframe-config-field">
                            <label>Height (px)</label>
                            <input type="number" id="iframe_height" data-key="iframe_height" data-section="3d_iframe" data-label="Height" class="ci-input" min="100" max="1080">
                        </div>
                    </div>
                </div>
                <!-- Parse & Transform Tool -->
                <div class="iframe-tool">
                    <h4><i class="fas fa-magic"></i> <?php echo $lang==='th'?'ตัดแต่ง Iframe Code':'Parse & Transform Iframe Code'; ?></h4>
                    <div class="hint"><?php echo $lang==='th'?'วาง iframe code ดิบจาก Kiri Engine — ระบบจะตัด parameter เก่าออก แล้วต่อด้วยพารามิเตอร์ที่ตั้งไว้ด้านบน':'Paste raw iframe code from Kiri Engine — old parameters will be stripped and replaced with the settings above'; ?></div>
                    <div class="iframe-config-field" style="margin-bottom:10px">
                        <label><?php echo $lang==='th'?'วาง Iframe Code ดิบ':'Paste Raw Iframe Code'; ?></label>
                        <textarea id="cfgRawIframe" rows="4" placeholder='<?php echo $lang==='th'?'วาง <iframe> code ที่นี่...':'Paste <iframe> code here...'; ?>'></textarea>
                    </div>
                    <button onclick="parseAndTransformIframe()" class="ci-btn ci-btn-sm" style="background:#d97706;color:#fff;margin-bottom:10px">
                        <i class="fas fa-magic"></i> <?php echo $lang==='th'?'ตัดแต่ง & สร้างใหม่':'Parse & Transform'; ?>
                    </button>
                    <div id="cfgParsedResult" style="display:none">
                        <div class="iframe-config-field result-area" style="margin-bottom:8px">
                            <label><?php echo $lang==='th'?'ผลลัพธ์':'Result'; ?></label>
                            <textarea id="cfgParsedOutput" rows="3" readonly></textarea>
                        </div>
                        <button onclick="navigator.clipboard.writeText(document.getElementById('cfgParsedOutput').value);showToast('<?php echo $lang==='th'?'คัดลอกแล้ว!':'Copied!'; ?>','success')" class="ci-btn ci-btn-sm ci-btn-secondary" style="font-size:11px">
                            <i class="fas fa-copy"></i> <?php echo $lang==='th'?'คัดลอก':'Copy'; ?>
                        </button>
                    </div>
                </div>
            </div>
            <div class="section-save">
                <div class="save-hint" id="hint3d_iframe"><i class="fas fa-exclamation-circle"></i> <?php echo $lang==='th'?'มีการเปลี่ยนแปลงที่ยังไม่ได้บันทึก':'Unsaved changes'; ?></div>
                <button class="section-save-btn" onclick="confirmSave('3d_iframe')" id="btn3d_iframe"><i class="fas fa-save"></i> <?php echo $lang==='th'?'บันทึก Iframe Config':'Save Iframe Config'; ?></button>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════ -->
    <!-- LOCKED USERS SECTION                   -->
    <!-- ═══════════════════════════════════════ -->
    <div class="setting-section">
        <div class="ci-card">
            <div class="ci-card-head">
                <span><i class="fas fa-user-lock" style="color:#c62828"></i> <?php echo $lang==='th'?'บัญชีที่ถูกล็อค':'Locked Accounts'; ?></span>
                <button class="ci-btn ci-btn-sm ci-btn-secondary" onclick="loadLockedUsers()" style="font-size:11px"><i class="fas fa-sync-alt"></i></button>
            </div>
            <div class="ci-card-body" id="lockedUsersArea">
                <div style="text-align:center;color:var(--c3);font-size:13px;padding:12px"><i class="fas fa-spinner fa-spin"></i> <?php echo $lang==='th'?'กำลังโหลด...':'Loading...'; ?></div>
            </div>
        </div>
    </div>
</div>

<div class="toast" id="toast"></div>
<?php Layout::endContent(); ?>

<script>
const LANG='<?php echo $lang; ?>';
let originalValues={};
let sectionDirty={security:false,general:false,'3d_iframe':false};
let _confirmCb=null;

const SEC_LABELS={
    security: LANG==='th'?'ความปลอดภัย':'Security',
    general:  LANG==='th'?'ทั่วไป':'General',
    '3d_iframe': 'Iframe / 3D Config'
};
const SEC_BTN_LABELS={
    security: LANG==='th'?'บันทึกความปลอดภัย':'Save Security',
    general:  LANG==='th'?'บันทึกทั่วไป':'Save General',
    '3d_iframe': LANG==='th'?'บันทึก Iframe Config':'Save Iframe Config'
};

// ═══════════════════════════════════════════════
// CONFIRM DIALOG
// ═══════════════════════════════════════════════
function showConfirm(opt){
    document.getElementById('confirmTitle').textContent=opt.title||'';
    document.getElementById('confirmBody').innerHTML=opt.body||'';
    const ic=document.getElementById('confirmIcon');
    const ii=document.getElementById('confirmIconI');
    ic.className='confirm-icon '+(opt.icon||'warn');
    ii.className='fas '+(opt.icon==='danger'?'fa-exclamation-triangle':opt.icon==='info'?'fa-info-circle':'fa-exclamation-triangle');
    const ok=document.getElementById('confirmOkBtn');
    ok.textContent=opt.okText||(LANG==='th'?'ยืนยัน':'Confirm');
    ok.className='confirm-btn '+(opt.okClass||'ok');
    _confirmCb=opt.onOk||null;
    document.getElementById('confirmOverlay').classList.add('show');
}
function doConfirm(){
    document.getElementById('confirmOverlay').classList.remove('show');
    if(_confirmCb) _confirmCb();
    _confirmCb=null;
}
function closeConfirm(){
    document.getElementById('confirmOverlay').classList.remove('show');
    _confirmCb=null;
}
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeConfirm()});

// ═══════════════════════════════════════════════
// LOAD SETTINGS
// ═══════════════════════════════════════════════
async function loadSettings(){
    try{
        const d=await apiFetch('/v1/api/settings.php');
        if(!d.success) return;
        const flat={};
        Object.values(d.data).forEach(cat=>{
            if(Array.isArray(cat)) cat.forEach(s=>{flat[s.key]=s.value});
        });
        Object.entries(flat).forEach(([k,v])=>{
            const el=document.getElementById(k);
            if(!el) return;
            if(el.type==='checkbox') el.checked=!!v; else el.value=v;
        });
        originalValues={...flat};
        Object.keys(sectionDirty).forEach(s=>{sectionDirty[s]=false;showHint(s)});
        updateLockoutSub();
        updateDemoLabel();
    }catch(e){console.error(e)}
}

// ═══════════════════════════════════════════════
// SECTION DIRTY TRACKING
// ═══════════════════════════════════════════════
function showHint(sec){
    const h=document.getElementById('hint'+sec.charAt(0).toUpperCase()+sec.slice(1))||document.getElementById('hint'+sec);
    if(h) h.classList.toggle('show',!!sectionDirty[sec]);
}

document.querySelectorAll('[data-key][data-section]').forEach(el=>{
    const ev=el.type==='checkbox'?'change':(el.tagName==='SELECT'?'change':'input');
    el.addEventListener(ev,()=>{
        sectionDirty[el.dataset.section]=true;
        showHint(el.dataset.section);
        if(el.id==='account_lockout_enabled') updateLockoutSub();
        if(el.id==='demo_accounts_enabled') updateDemoLabel();
    });
});

function updateLockoutSub(){
    const on=document.getElementById('account_lockout_enabled').checked;
    document.getElementById('lockoutSub').classList.toggle('disabled',!on);
    document.getElementById('lockoutSub').querySelectorAll('input').forEach(i=>{if(i.type==='number')i.disabled=!on});
}
function updateDemoLabel(){
    const el=document.getElementById('demo_accounts_enabled');
    const lbl=document.getElementById('demoStatusLabel');
    if(el&&lbl){
        if(el.checked){lbl.textContent=LANG==='th'?'เปิดใช้งาน':'Enabled';lbl.style.color='#0d9488'}
        else{lbl.textContent=LANG==='th'?'ปิดใช้งาน':'Disabled';lbl.style.color='#94a3b8'}
    }
}

// ═══════════════════════════════════════════════
// DETECT CHANGES PER SECTION
// ═══════════════════════════════════════════════
function getChanges(sec){
    const list=[];
    document.querySelectorAll('[data-key][data-section="'+sec+'"]').forEach(el=>{
        const key=el.dataset.key, label=el.dataset.label||key;
        let nv,ov=originalValues[key];
        if(el.type==='checkbox'){
            nv=el.checked; if(typeof ov==='undefined')ov=false;
            if(nv!==!!ov) list.push({label, o:ov?(LANG==='th'?'เปิด':'ON'):(LANG==='th'?'ปิด':'OFF'), n:nv?(LANG==='th'?'เปิด':'ON'):(LANG==='th'?'ปิด':'OFF')});
        }else{
            nv=el.type==='number'?(parseInt(el.value)||0):el.value;
            const os=ov!=null?String(ov):'',ns=String(nv);
            if(os!==ns) list.push({label, o:os||'—', n:ns||'—'});
        }
    });
    return list;
}

function changesHtml(changes){
    if(!changes.length) return '<div style="text-align:center;color:#94a3b8;padding:10px;font-size:12px"><i class="fas fa-check-circle" style="margin-right:4px"></i>'+(LANG==='th'?'ไม่มีการเปลี่ยนแปลง':'No changes')+'</div>';
    return '<div class="confirm-changes">'+changes.map(c=>`<div class="confirm-change-item">
        <div class="ck-icon"><i class="fas fa-pen"></i></div>
        <span class="ck-label" title="${esc(c.label)}">${esc(c.label)}</span>
        <span class="ck-old" title="${esc(c.o)}">${esc(c.o)}</span>
        <span class="ck-arrow"><i class="fas fa-long-arrow-alt-right"></i></span>
        <span class="ck-new" title="${esc(c.n)}">${esc(c.n)}</span>
    </div>`).join('')+'</div>';
}

// ═══════════════════════════════════════════════
// SAVE WITH CONFIRM
// ═══════════════════════════════════════════════
function confirmSave(sec){
    const changes=getChanges(sec);
    const sName=SEC_LABELS[sec]||sec;
    showConfirm({
        title: LANG==='th'?'ยืนยันบันทึก — '+sName:'Confirm Save — '+sName,
        body: (LANG==='th'
            ?'<div style="margin-bottom:6px">คุณแน่ใจหรือไม่ว่าต้องการบันทึกการตั้งค่า <b>'+sName+'</b>?</div>'
            :'<div style="margin-bottom:6px">Are you sure you want to save <b>'+sName+'</b> settings?</div>')
            + changesHtml(changes),
        icon: changes.length?'warn':'info',
        okText: LANG==='th'?'บันทึก':'Save',
        okClass:'ok',
        onOk:()=>doSave(sec)
    });
}

async function doSave(sec){
    const btn=document.getElementById('btn'+sec.charAt(0).toUpperCase()+sec.slice(1))||document.getElementById('btn'+sec);
    if(btn){btn.disabled=true;btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> '+(LANG==='th'?'กำลังบันทึก...':'Saving...')}
    const payload={};
    document.querySelectorAll('[data-key][data-section="'+sec+'"]').forEach(el=>{
        const k=el.dataset.key;
        if(el.type==='checkbox') payload[k]=el.checked;
        else if(el.type==='number') payload[k]=parseInt(el.value)||0;
        else payload[k]=el.value;
    });
    try{
        const d=await apiFetch('/v1/api/settings.php',{method:'POST',body:JSON.stringify(payload)});
        if(d.success){
            showToast('✅ '+(LANG==='th'?'บันทึก '+SEC_LABELS[sec]+' เรียบร้อย':SEC_LABELS[sec]+' saved'),'success');
            Object.entries(payload).forEach(([k,v])=>{originalValues[k]=v});
            sectionDirty[sec]=false;showHint(sec);
        }else throw new Error(d.error);
    }catch(e){showToast('❌ '+e.message,'error')}
    finally{if(btn){btn.disabled=false;btn.innerHTML='<i class="fas fa-save"></i> '+(SEC_BTN_LABELS[sec]||'Save')}}
}

// ═══════════════════════════════════════════════
// LOCKED USERS
// ═══════════════════════════════════════════════
async function loadLockedUsers(){
    const area=document.getElementById('lockedUsersArea');
    area.innerHTML='<div style="text-align:center;padding:12px;color:var(--c3)"><i class="fas fa-spinner fa-spin"></i></div>';
    try{
        const d=await apiFetch('/v1/api/auth.php?action=locked_users');
        if(!d.success) throw new Error(d.error);
        const users=d.data||[];
        if(!users.length){
            area.innerHTML='<div style="text-align:center;padding:16px;color:var(--c3);font-size:13px"><i class="fas fa-check-circle" style="color:var(--accent);margin-right:4px"></i> '+(LANG==='th'?'ไม่มีบัญชีที่ถูกล็อค':'No locked accounts')+'</div>';
            return;
        }
        area.innerHTML=users.map(u=>`<div class="setting-row" style="padding:10px 0">
            <div class="setting-info">
                <div class="setting-label"><i class="fas fa-user-lock" style="color:#c62828;margin-right:4px"></i> ${esc(u.username)} <span style="font-weight:400;color:var(--c3)">— ${esc(u.full_name_th||((u.first_name||'')+' '+(u.last_name||'')))}</span></div>
                <div class="setting-desc">${LANG==='th'?'ลองผิด':'Attempts'}: ${u.login_attempts} | ${LANG==='th'?'ล็อคถึง':'Locked until'}: ${esc(u.locked_until||'-')}</div>
            </div>
            <button class="ci-btn ci-btn-sm ci-btn-outline" onclick="confirmUnlock(${u.id},'${esc(u.username)}',this)" style="color:#c62828;border-color:#c62828"><i class="fas fa-unlock"></i> ${LANG==='th'?'ปลดล็อค':'Unlock'}</button>
        </div>`).join('');
    }catch(e){area.innerHTML='<div style="padding:12px;color:var(--danger)">'+e.message+'</div>'}
}

function confirmUnlock(id,uname,btn){
    showConfirm({
        title: LANG==='th'?'ยืนยันปลดล็อค':'Confirm Unlock',
        body: LANG==='th'
            ?'คุณแน่ใจหรือไม่ว่าต้องการปลดล็อคบัญชี <b>'+uname+'</b>?<br><span style="font-size:11px;color:#94a3b8">ผู้ใช้จะสามารถเข้าสู่ระบบได้อีกครั้ง</span>'
            :'Are you sure you want to unlock <b>'+uname+'</b>?<br><span style="font-size:11px;color:#94a3b8">The user will be able to log in again</span>',
        icon:'danger',
        okText: LANG==='th'?'ปลดล็อค':'Unlock',
        okClass:'danger',
        onOk:()=>doUnlock(id,btn)
    });
}

async function doUnlock(id,btn){
    if(btn){btn.disabled=true;btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>'}
    try{
        const d=await apiFetch('/v1/api/auth.php?action=unlock_user',{method:'POST',body:JSON.stringify({user_id:id})});
        if(d.success){showToast(LANG==='th'?'✅ ปลดล็อคสำเร็จ':'✅ Unlocked','success');loadLockedUsers()}
        else throw new Error(d.error);
    }catch(e){showToast('❌ '+e.message,'error');if(btn){btn.disabled=false;btn.innerHTML='<i class="fas fa-unlock"></i> '+(LANG==='th'?'ปลดล็อค':'Unlock')}}
}

// ═══════════════════════════════════════════════
// HELPERS
// ═══════════════════════════════════════════════
function showToast(msg,type){
    const t=document.getElementById('toast');
    t.textContent=msg;t.className='toast '+type+' show';
    setTimeout(()=>t.classList.remove('show'),3000);
}
function esc(s){if(!s)return '';const d=document.createElement('div');d.textContent=s;return d.innerHTML}

// ═══ Parse & Transform Iframe Tool ═══
function parseAndTransformIframe(){
    var raw=document.getElementById('cfgRawIframe').value.trim();
    if(!raw){showToast(LANG==='th'?'กรุณาวาง iframe code':'Please paste iframe code','error');return;}
    var iframes=raw.match(/<iframe[\s\S]*?<\/iframe>/gi);
    if(!iframes||!iframes.length){showToast(LANG==='th'?'ไม่พบ <iframe> tag ใน code':'No <iframe> tag found','error');return;}
    var results=[];
    iframes.forEach(function(tag){
        var srcMatch=tag.match(/src\s*=\s*["']([^"']+)["']/i);
        if(!srcMatch) return;
        var originalUrl=srcMatch[1];
        var base=originalUrl.split('?')[0];
        base=base.replace(/\/sharemodel(\/|$)/gi,'/embed$1');
        var params={};
        var bgEl=document.getElementById('iframe_kiri_bg_theme');
        var spinEl=document.getElementById('iframe_kiri_auto_spin');
        params['bg_theme']=bgEl?bgEl.value:'transparent';
        params['auto_spin_model']=spinEl?spinEl.value:'1';
        var extraEl=document.getElementById('iframe_default_params');
        var extra=extraEl?extraEl.value.trim():'';
        if(extra){extra.split('&').forEach(function(p){var kv=p.split('=');if(kv[0])params[kv[0]]=kv[1]||''})}
        var origParams=originalUrl.split('?')[1]||'';
        if(origParams){origParams.split('&').forEach(function(p){var kv=p.split('=');if(kv[0]==='userId'&&kv[1])params['userId']=kv[1]})}
        var newUrl=base+'?'+Object.keys(params).map(function(k){return k+'='+params[k]}).join('&');
        var attrsEl=document.getElementById('iframe_default_attrs');
        var attrs=attrsEl?attrsEl.value.trim():'allowfullscreen';
        if(!attrs) attrs='allowfullscreen';
        var wEl=document.getElementById('iframe_width'),hEl=document.getElementById('iframe_height');
        var w=wEl?wEl.value:'640',h=hEl?hEl.value:'480';
        if(!w) w='640';if(!h) h='480';
        results.push('<iframe src="'+newUrl+'" width="'+w+'" height="'+h+'" '+attrs+'><\/iframe>');
    });
    if(results.length){
        document.getElementById('cfgParsedOutput').value=results.join('\n\n');
        document.getElementById('cfgParsedResult').style.display='block';
        showToast((LANG==='th'?'ตัดแต่ง ':'Transformed ')+results.length+(LANG==='th'?' iframe สำเร็จ!':' iframe(s) successfully!'),'success');
    }else{showToast(LANG==='th'?'ไม่สามารถแปลง iframe ได้':'Could not transform iframe','error')}
}

// ═══ INIT ═══
loadSettings();
loadLockedUsers();
</script>
</body></html>
