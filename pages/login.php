<?php
/**
 * Login Page - SUT chemBot
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';

if (Auth::getCurrentUser()) { header('Location: /v1/'); exit; }
$lang = I18n::getCurrentLang();

// Check if demo accounts are enabled
$demoSetting = Database::fetch("SELECT setting_value FROM system_settings WHERE setting_key = :k", [':k' => 'demo_accounts_enabled']);
$demoEnabled = $demoSetting && ($demoSetting['setting_value'] === '1' || $demoSetting['setting_value'] === 'true');

// Fetch all users grouped by role for demo accounts dropdown (only if enabled)
$allUsers = [];
if ($demoEnabled) {
    $allUsers = Database::fetchAll("
        SELECT u.id, u.username, u.full_name_th, u.first_name, u.last_name,
               r.name as role_name, r.display_name as role_display,
               d.name as dept_name
        FROM users u
        LEFT JOIN roles r ON u.role_id = r.id
        LEFT JOIN departments d ON u.department_id = d.id
        ORDER BY u.role_id ASC, u.username ASC
    ");
}

// Group users by role
$usersByRole = [];
foreach ($allUsers as $u) {
    $role = $u['role_name'] ?? 'user';
    if (!isset($usersByRole[$role])) $usersByRole[$role] = [];
    $usersByRole[$role][] = $u;
}

// Role config: icon, color, label_th, label_en
$roleConfig = [
    'admin'       => ['icon' => 'fa-user-shield',  'color' => '#dc2626', 'bg' => '#fef2f2', 'border' => '#fecaca', 'th' => 'ผู้ดูแลระบบ',      'en' => 'Administrator'],
    'ceo'         => ['icon' => 'fa-crown',         'color' => '#d97706', 'bg' => '#fffbeb', 'border' => '#fde68a', 'th' => 'ผู้บริหาร',         'en' => 'CEO / Director'],
    'lab_manager' => ['icon' => 'fa-microscope',    'color' => '#7c3aed', 'bg' => '#f5f3ff', 'border' => '#ddd6fe', 'th' => 'ผู้จัดการห้องปฏิบัติการ', 'en' => 'Lab Manager'],
    'user'        => ['icon' => 'fa-user',          'color' => '#2563eb', 'bg' => '#eff6ff', 'border' => '#bfdbfe', 'th' => 'ผู้ใช้งาน',          'en' => 'Lab User'],
    'visitor'     => ['icon' => 'fa-eye',           'color' => '#6b7280', 'bg' => '#f9fafb', 'border' => '#e5e7eb', 'th' => 'ผู้เยี่ยมชม',        'en' => 'Visitor'],
];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('login_title'); ?> - <?php echo __('app_name'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
    :root{--accent:#f97316;--accent-h:#ea580c;--accent-glow:rgba(249,115,22,.35);--sb-bg:#1e1e1e}
    *{margin:0;padding:0;box-sizing:border-box;font-family:'Inter','Noto Sans Thai',sans-serif}
    body{min-height:100vh;display:flex;background:#f5f5f5}
    .login-left{width:50%;background:var(--sb-bg);display:flex;flex-direction:column;justify-content:center;align-items:center;padding:60px;color:#fff;position:relative;overflow:hidden}
    .login-left::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse at 30% 20%,rgba(249,115,22,.12) 0%,transparent 60%),radial-gradient(ellipse at 70% 80%,rgba(251,146,60,.08) 0%,transparent 50%)}
    .login-right{width:50%;display:flex;align-items:center;justify-content:center;padding:40px}
    @media(max-width:768px){body{flex-direction:column}.login-left{width:100%;padding:30px 20px;min-height:auto}.login-right{width:100%;padding:20px}.feature-list{display:none}.stats-row{margin-top:20px}.login-left h1{font-size:22px}.login-left .tagline{margin-bottom:14px}}
    @media(max-width:480px){.login-right{padding:16px 12px}.demo-grid{grid-template-columns:1fr}.login-card h2{font-size:18px}.stats-row{gap:8px}.stats-row .st{padding:8px 12px}.stats-row .st-val{font-size:18px}}
    .logo-big{width:80px;height:80px;background:linear-gradient(135deg,#f97316 0%,#fb923c 50%,#fdba74 100%);border-radius:22px;display:flex;align-items:center;justify-content:center;font-size:32px;margin-bottom:24px;position:relative;z-index:1;box-shadow:0 8px 32px rgba(249,115,22,.4),0 2px 8px rgba(249,115,22,.2);color:#fff;animation:logoFloat 3s ease-in-out infinite}
    .logo-big::before{content:'';position:absolute;inset:-3px;border-radius:25px;background:linear-gradient(135deg,#f97316,#fbbf24,#f97316);z-index:-1;opacity:.5;filter:blur(1px)}
    .logo-big::after{content:'';position:absolute;inset:0;border-radius:22px;background:linear-gradient(135deg,rgba(255,255,255,.25) 0%,transparent 50%);pointer-events:none}
    @keyframes logoFloat{0%,100%{transform:translateY(0);box-shadow:0 8px 32px rgba(249,115,22,.4),0 2px 8px rgba(249,115,22,.2)}50%{transform:translateY(-4px);box-shadow:0 14px 40px rgba(249,115,22,.45),0 4px 12px rgba(249,115,22,.25)}}
    .login-left h1{font-size:28px;font-weight:700;margin-bottom:6px;position:relative;z-index:1}
    .login-left .tagline{font-size:14px;color:rgba(255,255,255,.6);margin-bottom:40px;position:relative;z-index:1}
    .feature-list{list-style:none;display:flex;flex-direction:column;gap:18px;position:relative;z-index:1;max-width:360px}
    .feature-list li{display:flex;align-items:center;gap:14px;font-size:14px;color:rgba(255,255,255,.85)}
    .feature-list li .fi{width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:rgba(255,255,255,.7)}
    .stats-row{display:flex;gap:16px;margin-top:40px;position:relative;z-index:1}
    .stats-row .st{text-align:center;padding:12px 20px;background:rgba(255,255,255,.06);border-radius:10px;border:1px solid rgba(255,255,255,.08)}
    .stats-row .st-val{font-size:22px;font-weight:700;color:var(--accent)}
    .stats-row .st-lbl{font-size:11px;color:rgba(255,255,255,.5)}
    .login-card{width:100%;max-width:420px}
    .login-card h2{font-size:22px;font-weight:700;color:#333;margin-bottom:4px}
    .login-card .sub{font-size:13px;color:#999;margin-bottom:24px}
    .fg{margin-bottom:16px}
    .fg label{display:block;font-size:13px;font-weight:600;color:#555;margin-bottom:4px}
    .fg .input-wrap{position:relative}
    .fg .input-wrap i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#bbb;font-size:13px}
    .fg input[type=text],.fg input[type=password]{width:100%;padding:10px 12px 10px 36px;border:1px solid #ddd;border-radius:6px;font-size:13px;transition:border .15s,box-shadow .15s;font-family:inherit}
    .fg input:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 2px rgba(249,115,22,.15)}
    .fg input::placeholder{color:#bbb;font-style:italic}
    .login-opts{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;font-size:13px}
    .login-opts label{display:flex;align-items:center;gap:6px;color:#777;cursor:pointer}
    .login-opts a{color:var(--accent);font-weight:500}
    .login-btn{width:100%;padding:11px;background:var(--accent);color:#fff;border:none;border-radius:6px;font-size:14px;font-weight:600;cursor:pointer;transition:background .15s;display:flex;align-items:center;justify-content:center;gap:6px;font-family:inherit}
    .login-btn:hover{background:var(--accent-h)}
    .login-btn:disabled{opacity:.6;cursor:not-allowed}
    .demo-box{background:#fffbf5;border:1px solid #fed7aa;border-radius:10px;padding:14px;margin-bottom:20px}
    .demo-box .demo-title{font-size:12px;font-weight:700;color:#334155;margin-bottom:10px;display:flex;align-items:center;gap:6px}
    .demo-box .demo-title i{color:var(--accent)}
    .demo-role-group{margin-bottom:6px;position:relative}
    .demo-role-btn{width:100%;display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:8px;border:1px solid #e2e8f0;background:#fff;cursor:pointer;font-size:12px;font-family:inherit;transition:all .15s;position:relative}
    .demo-role-btn:hover{border-color:var(--accent);background:#fff7ed}
    .demo-role-btn .role-icon{width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0}
    .demo-role-btn .role-info{flex:1;text-align:left;min-width:0}
    .demo-role-btn .role-name{font-weight:700;color:#334155;font-size:12px}
    .demo-role-btn .role-count{font-size:10px;color:#94a3b8;font-weight:500}
    .demo-role-btn .role-arrow{font-size:10px;color:#94a3b8;transition:transform .2s}
    .demo-role-btn.open .role-arrow{transform:rotate(180deg)}
    .demo-dropdown{display:none;position:absolute;left:0;right:0;top:calc(100% + 2px);background:#fff;border:1px solid #e2e8f0;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.12);z-index:100;max-height:220px;overflow-y:auto;padding:4px}
    .demo-dropdown.show{display:block}
    .demo-user-item{display:flex;align-items:center;gap:8px;padding:7px 10px;border-radius:6px;cursor:pointer;transition:background .1s;font-size:12px}
    .demo-user-item:hover{background:#fff7ed}
    .demo-user-item .user-avatar{width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;flex-shrink:0}
    .demo-user-item .user-info{flex:1;min-width:0}
    .demo-user-item .user-name{font-weight:600;color:#334155;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-size:11px}
    .demo-user-item .user-dept{font-size:9px;color:#94a3b8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .demo-user-item .user-id{font-size:10px;font-weight:700;color:var(--accent);flex-shrink:0;font-family:monospace}
    .err-msg{padding:10px 14px;background:#fef2f2;border:1px solid #fecaca;border-radius:6px;color:#b91c1c;font-size:13px;margin-top:12px;display:none;align-items:center;gap:6px}
    .err-msg.show{display:flex}
    .login-footer{text-align:center;font-size:13px;color:#999;margin-top:20px}
    .login-footer a{color:var(--accent);font-weight:500}
    .lang-switch{position:absolute;top:16px;right:16px;display:flex;gap:4px;z-index:2}
    .lang-switch a{padding:4px 10px;border-radius:4px;font-size:11px;font-weight:500;color:rgba(255,255,255,.5);text-decoration:none;transition:all .12s}
    .lang-switch a:hover{color:#fff;background:rgba(255,255,255,.1)}
    .lang-switch a.active{color:#fff;background:var(--accent)}
    </style>
</head>
<body>
    <div class="login-left">
        <div class="lang-switch">
            <a href="?lang=th" class="<?php echo $lang==='th'?'active':''; ?>">🇹🇭 TH</a>
            <a href="?lang=en" class="<?php echo $lang==='en'?'active':''; ?>">🇬🇧 EN</a>
        </div>
        <div class="logo-big"><i class="fas fa-flask-vial"></i></div>
        <h1 style="font-size:30px;letter-spacing:-.5px"><span style="color:#f97316;font-weight:800">SUT</span> <span style="color:#fff;font-weight:600">chemBot</span></h1>
        <p class="tagline"><?php echo $lang==='th'?'ศูนย์เครื่องมือวิทยาศาสตร์และเทคโนโลยี มทส.':__('app_tagline'); ?></p>
        <ul class="feature-list">
            <?php $features=[['fa-qrcode',$lang==='th'?'ติดตามด้วย QR Code':'QR Code Tracking'],['fa-vr-cardboard',$lang==='th'?'ภาพเสมือนจริง AR':'AR Visualization'],['fa-robot',$lang==='th'?'ผู้ช่วย AI อัจฉริยะ':'AI Smart Assistant'],['fa-shield-alt',$lang==='th'?'มาตรฐาน GHS':'GHS Compliance']];
            foreach($features as $f): ?>
            <li><div class="fi"><i class="fas <?php echo $f[0]; ?>"></i></div><?php echo $f[1]; ?></li>
            <?php endforeach; ?>
        </ul>
        <div class="stats-row">
            <div class="st"><div class="st-val">7,300+</div><div class="st-lbl"><?php echo $lang==='th'?'สารเคมี':'Chemicals'; ?></div></div>
            <div class="st"><div class="st-val">5,400+</div><div class="st-lbl"><?php echo $lang==='th'?'ขวดสาร':'Bottles'; ?></div></div>
            <div class="st"><div class="st-val">15</div><div class="st-lbl"><?php echo $lang==='th'?'อาคาร':'Buildings'; ?></div></div>
        </div>
    </div>
    <div class="login-right">
        <div class="login-card">
            <h2><?php echo __('login_title'); ?></h2>
            <p class="sub"><?php echo __('login_subtitle'); ?></p>
            <?php if ($demoEnabled && !empty($allUsers)): ?>
            <div class="demo-box">
                <div class="demo-title"><i class="fas fa-flask-vial"></i> <?php echo $lang==='th' ? 'บัญชีทดลองใช้งาน' : 'Demo Accounts'; ?> <span style="font-weight:400;color:#94a3b8;font-size:10px;margin-left:auto"><?php echo count($allUsers); ?> <?php echo $lang==='th' ? 'บัญชี' : 'accounts'; ?></span></div>
                <?php foreach ($roleConfig as $rKey => $rc):
                    $usersInRole = $usersByRole[$rKey] ?? [];
                    if (empty($usersInRole)) continue;
                    $roleLabel = $lang === 'th' ? $rc['th'] : $rc['en'];
                ?>
                <div class="demo-role-group">
                    <button type="button" class="demo-role-btn" onclick="toggleRoleDropdown(this)">
                        <div class="role-icon" style="background:<?php echo $rc['bg']; ?>;color:<?php echo $rc['color']; ?>;border:1px solid <?php echo $rc['border']; ?>"><i class="fas <?php echo $rc['icon']; ?>"></i></div>
                        <div class="role-info">
                            <div class="role-name"><?php echo $roleLabel; ?></div>
                            <div class="role-count"><?php echo count($usersInRole); ?> <?php echo $lang==='th' ? 'บัญชี' : 'accounts'; ?></div>
                        </div>
                        <i class="fas fa-chevron-down role-arrow"></i>
                    </button>
                    <div class="demo-dropdown">
                        <?php foreach ($usersInRole as $du):
                            $initial = mb_substr($du['first_name'] ?: $du['username'], 0, 1, 'UTF-8');
                            $deptShort = str_replace(['งานกลุ่ม','งาน','ห้องปฏิบัติการ'], ['','','ปฏิบัติการ'], $du['dept_name'] ?? '');
                        ?>
                        <div class="demo-user-item" onclick="fillDemo('<?php echo htmlspecialchars($du['username']); ?>')">
                            <div class="user-avatar" style="background:<?php echo $rc['color']; ?>"><?php echo $initial; ?></div>
                            <div class="user-info">
                                <div class="user-name"><?php echo htmlspecialchars($du['full_name_th'] ?: $du['first_name'].' '.$du['last_name']); ?></div>
                                <div class="user-dept"><?php echo htmlspecialchars($deptShort); ?></div>
                            </div>
                            <div class="user-id"><?php echo htmlspecialchars($du['username']); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <form id="loginForm">
                <div class="fg">
                    <label><?php echo __('login_username'); ?></label>
                    <div class="input-wrap"><i class="fas fa-user"></i><input type="text" id="username" required autocomplete="username" placeholder="<?php echo $lang==='th'?'ชื่อผู้ใช้หรืออีเมล':'Username or email'; ?>"></div>
                </div>
                <div class="fg">
                    <label><?php echo __('login_password'); ?></label>
                    <div class="input-wrap"><i class="fas fa-lock"></i><input type="password" id="password" required autocomplete="current-password" placeholder="<?php echo $lang==='th'?'รหัสผ่าน':'Password'; ?>"></div>
                </div>
                <div class="login-opts">
                    <label><input type="checkbox" id="remember"> <?php echo __('login_remember'); ?></label>
                    <a href="#"><?php echo __('login_forgot'); ?></a>
                </div>
                <button type="submit" id="loginBtn" class="login-btn">
                    <span id="loginText"><i class="fas fa-sign-in-alt"></i> <?php echo __('login'); ?></span>
                    <i id="loginSpinner" class="fas fa-spinner fa-spin" style="display:none"></i>
                </button>
            </form>
            <div id="errorMsg" class="err-msg"><i class="fas fa-exclamation-circle"></i><span id="errorText"></span></div>
            <div class="login-footer">
                <?php echo __('login_no_account'); ?> <a href="/v1/pages/register.php"><?php echo __('login_register'); ?></a>
            </div>
            <p style="text-align:center;font-size:11px;color:#bbb;margin-top:16px"><span style="color:#f97316;font-weight:600">SUT</span> chemBot v2.0 © <?php echo date('Y'); ?></p>
        </div>
    </div>
<script>
function fillDemo(u){
    document.getElementById('username').value=u;
    document.getElementById('password').value='123';
    document.getElementById('username').focus();
    // Close all dropdowns
    document.querySelectorAll('.demo-dropdown.show').forEach(d=>d.classList.remove('show'));
    document.querySelectorAll('.demo-role-btn.open').forEach(b=>b.classList.remove('open'));
}

function toggleRoleDropdown(btn){
    const dd = btn.nextElementSibling;
    const isOpen = dd.classList.contains('show');
    // Close all others first
    document.querySelectorAll('.demo-dropdown.show').forEach(d=>d.classList.remove('show'));
    document.querySelectorAll('.demo-role-btn.open').forEach(b=>b.classList.remove('open'));
    if(!isOpen){
        dd.classList.add('show');
        btn.classList.add('open');
    }
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(e){
    if(!e.target.closest('.demo-role-group')){
        document.querySelectorAll('.demo-dropdown.show').forEach(d=>d.classList.remove('show'));
        document.querySelectorAll('.demo-role-btn.open').forEach(b=>b.classList.remove('open'));
    }
});

document.getElementById('loginForm').addEventListener('submit',async(e)=>{
    e.preventDefault();
    const btn=document.getElementById('loginBtn'),text=document.getElementById('loginText'),spin=document.getElementById('loginSpinner'),err=document.getElementById('errorMsg'),errT=document.getElementById('errorText');
    btn.disabled=true;text.style.display='none';spin.style.display='inline-block';err.classList.remove('show');
    try{
        const r=await fetch('/v1/api/auth.php?action=login',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({username:document.getElementById('username').value,password:document.getElementById('password').value,remember:document.getElementById('remember').checked})});
        const d=await r.json();
        if(d.success){window.location.href='/v1/'}else{throw new Error(d.error||'<?php echo __("error_invalid_credentials"); ?>')}
    }catch(er){errT.textContent=er.message;err.classList.add('show')}
    finally{btn.disabled=false;text.style.display='';spin.style.display='none'}
});
</script>
</body>
</html>
