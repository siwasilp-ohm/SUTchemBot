<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

if (($_GET['action'] ?? '') === 'logout') {
    pro_logout();
    header('Location: index.php?page=login');
    exit;
}

$page = $_GET['page'] ?? 'dashboard';

if ($page === 'login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!pro_verify_csrf($_POST['csrf_token'] ?? null)) {
            $error = 'Security token ไม่ถูกต้อง กรุณาลองใหม่';
        } else {
            $ok = pro_login($_POST['username'] ?? '', $_POST['password'] ?? '');
            if ($ok) {
                pro_flash('เข้าสู่ระบบสำเร็จ', 'success');
                header('Location: index.php?page=dashboard');
                exit;
            }
            $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
        }
    }
    include __DIR__ . '/pages/login.php';
    exit;
}

$user = pro_require_auth();
$allowed = ['dashboard', 'chemicals', 'barcodes', 'ar-viewer'];
if (!in_array($page, $allowed, true)) {
    $page = 'dashboard';
}

include __DIR__ . '/pages/' . $page . '.php';
