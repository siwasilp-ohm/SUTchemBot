<?php
function pro_h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function pro_header(string $title, ?array $user = null): void {
    ?>
    <!doctype html>
    <html lang="th">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title><?= pro_h($title) ?></title>
        <link rel="stylesheet" href="assets/css/app.css">
        <script type="module" src="https://unpkg.com/@google/model-viewer/dist/model-viewer.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    </head>
    <body>
    <header class="topbar">
        <div>
            <h1><?= pro_h(PRO_APP_NAME) ?></h1>
            <small>Smart Chemical Operations Platform</small>
        </div>
        <?php if ($user): ?>
            <div class="user-chip">
                <?= pro_h($user['full_name']) ?>
                <span class="role-tag"><?= pro_h(strtoupper($user['role'])) ?></span>
                <a href="index.php?action=logout">Logout</a>
            </div>
        <?php endif; ?>
    </header>
    <?php
}

function pro_nav(string $active): void {
    $items = [
        'dashboard' => 'Dashboard',
        'chemicals' => 'Chemicals',
        'barcodes' => 'Barcodes',
        'ar-viewer' => 'AR 3D Viewer',
    ];
    echo '<nav class="nav">';
    foreach ($items as $key => $label) {
        $class = $active === $key ? 'active' : '';
        echo '<a class="' . pro_h($class) . '" href="index.php?page=' . pro_h($key) . '">' . pro_h($label) . '</a>';
    }
    echo '</nav>';
}

function pro_render_flash(): void {
    $flash = pro_flash();
    if (!$flash) {
        return;
    }

    $type = in_array($flash['type'], ['info', 'success', 'error'], true) ? $flash['type'] : 'info';
    echo '<div class="flash ' . pro_h($type) . '">' . pro_h($flash['message']) . '</div>';
}

function pro_footer(): void {
    echo '<script src="assets/js/app.js"></script></body></html>';
}
