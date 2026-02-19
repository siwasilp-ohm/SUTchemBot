<?php
require_once __DIR__ . '/../includes/layout.php';
$user = Auth::getCurrentUser();
if ($user) { header('Location: /v1/'); exit; }
$lang = I18n::getCurrentLang();
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('register_title'); ?> - <?php echo __('app_name'); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
    :root{--accent:#1a8a5c;--accent-h:#15704b;--sb-bg:#2d2d2d}
    *{margin:0;padding:0;box-sizing:border-box;font-family:'Inter','Noto Sans Thai',sans-serif}
    body{min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f5f5f5;padding:20px}
    .reg-card{width:100%;max-width:640px;background:#fff;border-radius:8px;border:1px solid #e0e0e0;padding:32px;box-shadow:0 2px 12px rgba(0,0,0,.06)}
    .reg-hdr{text-align:center;margin-bottom:24px}
    .reg-logo{width:52px;height:52px;background:var(--accent);border-radius:14px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;margin:0 auto 12px}
    .reg-hdr h2{font-size:22px;font-weight:700;color:#333}
    .reg-hdr p{font-size:13px;color:#999}
    .fg{margin-bottom:14px}
    .fg label{display:block;font-size:12px;font-weight:600;color:#555;margin-bottom:4px}
    .fg input,.fg select{width:100%;padding:9px 12px;border:1px solid #ddd;border-radius:5px;font-size:13px;font-family:inherit;transition:border .15s,box-shadow .15s}
    .fg input:focus,.fg select:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 2px rgba(26,138,92,.15)}
    .fg input::placeholder{color:#bbb;font-style:italic}
    .fg-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    @media(max-width:480px){.fg-row{grid-template-columns:1fr;gap:8px}.reg-card{padding:20px 16px}}
    .reg-btn{width:100%;padding:11px;background:var(--accent);color:#fff;border:none;border-radius:6px;font-size:14px;font-weight:600;cursor:pointer;font-family:inherit;transition:background .15s;display:flex;align-items:center;justify-content:center;gap:6px}
    .reg-btn:hover{background:var(--accent-h)}
    .reg-btn:disabled{opacity:.6}
    .chk{display:flex;align-items:start;gap:8px;margin-bottom:16px;font-size:12px;color:#777}
    .chk a{color:var(--accent);font-weight:500}
    .msg{padding:10px 14px;border-radius:6px;font-size:13px;margin-top:12px;display:none;align-items:center;gap:6px}
    .msg.show{display:flex}
    .msg-ok{background:#e8f5ef;border:1px solid #c8e6d8;color:#2e7d32}
    .msg-err{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c}
    .reg-foot{text-align:center;font-size:13px;color:#999;margin-top:16px}
    .reg-foot a{color:var(--accent);font-weight:500}
    .lang-sw{position:fixed;top:16px;right:16px;display:flex;gap:4px}
    .lang-sw a{padding:4px 10px;border-radius:4px;font-size:11px;font-weight:500;color:#999;text-decoration:none;transition:all .12s}
    .lang-sw a:hover{color:#333;background:#eee}
    .lang-sw a.active{color:#fff;background:var(--accent)}
    </style>
</head>
<body>
    <div class="lang-sw">
        <a href="?lang=th" class="<?php echo $lang==='th'?'active':''; ?>">🇹🇭 TH</a>
        <a href="?lang=en" class="<?php echo $lang==='en'?'active':''; ?>">🇬🇧 EN</a>
    </div>
    <div class="reg-card">
        <div class="reg-hdr">
            <div class="reg-logo"><i class="fas fa-flask-vial"></i></div>
            <h2><?php echo __('register_title'); ?></h2>
            <p><?php echo __('register_subtitle'); ?></p>
        </div>
        <form id="registerForm">
            <div class="fg-row">
                <div class="fg"><label><?php echo __('register_first_name'); ?></label><input type="text" name="first_name" required></div>
                <div class="fg"><label><?php echo __('register_last_name'); ?></label><input type="text" name="last_name" required></div>
            </div>
            <div class="fg"><label><?php echo __('register_email'); ?></label><input type="email" name="email" required></div>
            <div class="fg"><label><?php echo __('register_username'); ?></label><input type="text" name="username" required></div>
            <div class="fg-row">
                <div class="fg"><label><?php echo __('register_password'); ?></label><input type="password" name="password" required minlength="8"></div>
                <div class="fg"><label><?php echo __('register_password_confirm'); ?></label><input type="password" name="password_confirm" required></div>
            </div>
            <div class="fg"><label><?php echo __('register_phone'); ?></label><input type="tel" name="phone"></div>
            <div class="fg-row">
                <div class="fg"><label><?php echo __('register_lab'); ?></label><select name="lab_id" id="labSelect" required><option value=""><?php echo $lang==='th'?'เลือกห้องปฏิบัติการ...':'Select your lab...'; ?></option></select></div>
                <div class="fg"><label><?php echo __('register_department'); ?></label><input type="text" name="department"></div>
            </div>
            <div class="fg"><label><?php echo __('register_position'); ?></label><input type="text" name="position"></div>
            <div class="chk">
                <input type="checkbox" name="terms" required style="margin-top:2px">
                <span><?php echo __('register_terms'); ?> <a href="#"><?php echo __('register_terms_link'); ?></a> <?php echo $lang==='th'?'และ':'and'; ?> <a href="#"><?php echo __('register_privacy_link'); ?></a></span>
            </div>
            <button type="submit" id="submitBtn" class="reg-btn">
                <span id="submitText"><?php echo __('register_title'); ?></span>
                <i id="submitSpinner" class="fas fa-spinner fa-spin" style="display:none"></i>
            </button>
        </form>
        <div id="successMsg" class="msg msg-ok"><i class="fas fa-check-circle"></i> <?php echo __('register_success'); ?></div>
        <div id="errorMsg" class="msg msg-err"><i class="fas fa-exclamation-circle"></i> <span id="errorText"></span></div>
        <div class="reg-foot"><?php echo __('register_has_account'); ?> <a href="/v1/pages/login.php"><?php echo __('login'); ?></a></div>
    </div>
<script>
(async()=>{try{const r=await fetch('/v1/api/locations.php?type=labs');const d=await r.json();if(d.success){const sel=document.getElementById('labSelect');(d.data||[]).forEach(l=>{const o=document.createElement('option');o.value=l.id;o.textContent=l.name;sel.appendChild(o)})}}catch(e){}})();
document.getElementById('registerForm').addEventListener('submit',async e=>{
    e.preventDefault();const form=e.target;
    if(form.password.value!==form.password_confirm.value){showError('<?php echo $lang==="th"?"รหัสผ่านไม่ตรงกัน":"Passwords do not match"; ?>');return}
    const btn=document.getElementById('submitBtn');btn.disabled=true;
    document.getElementById('submitText').style.display='none';document.getElementById('submitSpinner').style.display='inline-block';
    document.getElementById('errorMsg').classList.remove('show');
    try{
        const fd=new FormData(form);const body=Object.fromEntries(fd.entries());delete body.password_confirm;delete body.terms;
        const r=await fetch('/v1/api/auth.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'register',...body})});
        const d=await r.json();
        if(d.success){form.style.display='none';document.getElementById('successMsg').classList.add('show')}
        else showError(d.error||'Registration failed');
    }catch(e){showError('<?php echo $lang==="th"?"ข้อผิดพลาดของเซิร์ฟเวอร์":"Server error"; ?>')}
    finally{btn.disabled=false;document.getElementById('submitText').style.display='';document.getElementById('submitSpinner').style.display='none'}
});
function showError(msg){document.getElementById('errorText').textContent=msg;document.getElementById('errorMsg').classList.add('show')}
</script>
</body>
</html>
