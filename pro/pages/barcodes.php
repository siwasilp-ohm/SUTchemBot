<?php
require_once __DIR__ . '/../includes/db.php';
pro_require_permission('barcodes.view');
pro_header('Barcode Labels', $user);
pro_nav('barcodes');
$rows = pro_db()->query('SELECT c.container_code, ch.name AS chemical_name, c.owner_name, c.qty_remaining, c.unit FROM pro_containers c JOIN pro_chemicals ch ON ch.id = c.chemical_id ORDER BY c.id DESC LIMIT 30')->fetchAll();
?>
<main class="container">
    <?php pro_render_flash(); ?>
    <section class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
            <h2>Barcode Labels for Packaging & Transactions</h2>
            <button class="btn" onclick="window.print()">Print Labels</button>
        </div>
        <div class="grid">
            <?php foreach ($rows as $r): ?>
                <article class="card label-card" style="background:#fff;color:#111">
                    <strong><?= pro_h($r['chemical_name']) ?></strong><br>
                    Owner: <?= pro_h($r['owner_name']) ?><br>
                    Remaining: <?= pro_h((string) $r['qty_remaining']) . ' ' . pro_h($r['unit']) ?><br>
                    <svg data-barcode="<?= pro_h($r['container_code']) ?>"></svg>
                    <div><small>Ref: <?= pro_h($r['container_code']) ?></small></div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>
<?php pro_footer(); ?>
