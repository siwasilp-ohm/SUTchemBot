<?php
require_once __DIR__ . '/../includes/db.php';
pro_require_permission('dashboard');
pro_header('Dashboard', $user);
pro_nav('dashboard');

$kpi = [
    'chemicals' => (int) pro_db()->query('SELECT COUNT(*) FROM pro_chemicals')->fetchColumn(),
    'containers' => (int) pro_db()->query('SELECT COUNT(*) FROM pro_containers')->fetchColumn(),
    'borrowed' => (int) pro_db()->query("SELECT COUNT(*) FROM pro_transactions WHERE action_type='borrow' AND status='open'")->fetchColumn(),
    'low_stock' => (int) pro_db()->query('SELECT COUNT(*) FROM pro_containers WHERE qty_remaining <= reorder_point')->fetchColumn(),
];

$roleFocus = [
    'admin' => 'Governance, configuration, permissions, system-level approvals',
    'ceo' => 'Executive KPIs, compliance exposure, inventory cost and risk',
    'lab_manager' => 'Daily stock planning, request approvals, movement orchestration',
    'user' => 'Search, borrow, return, and AR verification for the right container',
];
?>
<main class="container">
    <?php pro_render_flash(); ?>
    <section class="grid">
        <article class="card"><div class="muted">Chemicals</div><div class="kpi"><?= $kpi['chemicals'] ?></div></article>
        <article class="card"><div class="muted">Containers</div><div class="kpi"><?= $kpi['containers'] ?></div></article>
        <article class="card"><div class="muted">Active Borrow</div><div class="kpi"><?= $kpi['borrowed'] ?></div></article>
        <article class="card"><div class="muted">Low Stock Alerts</div><div class="kpi"><?= $kpi['low_stock'] ?></div></article>
    </section>

    <section class="card" style="margin-top:14px">
        <h3>Role-based Focus: <span class="badge"><?= pro_h(strtoupper($user['role'])) ?></span></h3>
        <p><?= pro_h($roleFocus[$user['role']] ?? 'Operational overview') ?></p>
        <div class="grid">
            <div class="card"><h4>Flow 1: Receive & Label</h4><p class="muted">รับสารเข้า, ผูก owner/lot, พิมพ์ barcode เพื่อติดบรรจุภัณฑ์</p></div>
            <div class="card"><h4>Flow 2: Borrow/Return</h4><p class="muted">จอง→อนุมัติ→เบิก→คืน พร้อมบันทึก transaction ทุกครั้ง</p></div>
            <div class="card"><h4>Flow 3: AR Verify</h4><p class="muted">เปิด 3D ด้วย WebXR + overlay ชื่อสาร/เจ้าของ/คงเหลือ</p></div>
        </div>
    </section>
</main>
<?php pro_footer(); ?>
