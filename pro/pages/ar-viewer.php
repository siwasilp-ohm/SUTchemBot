<?php
require_once __DIR__ . '/../includes/db.php';
pro_require_permission('ar.view');
pro_header('AR 3D Container Viewer', $user);
pro_nav('ar-viewer');

$selectedId = (int) ($_GET['container_id'] ?? 0);
$list = pro_db()->query('SELECT c.id, c.container_code, c.owner_name, c.qty_remaining, c.unit, c.model_url, ch.name AS chemical_name
FROM pro_containers c
JOIN pro_chemicals ch ON ch.id = c.chemical_id
WHERE c.model_url IS NOT NULL AND c.model_url <> ""
ORDER BY c.id DESC')->fetchAll();

$row = null;
if ($selectedId > 0) {
    $stmt = pro_db()->prepare('SELECT c.container_code, c.owner_name, c.qty_remaining, c.unit, c.model_url, ch.name AS chemical_name
    FROM pro_containers c JOIN pro_chemicals ch ON ch.id = c.chemical_id
    WHERE c.id = :id AND c.model_url IS NOT NULL AND c.model_url <> "" LIMIT 1');
    $stmt->execute([':id' => $selectedId]);
    $row = $stmt->fetch();
}

if (!$row && !empty($list)) {
    $row = $list[0];
}
?>
<main class="container">
    <?php pro_render_flash(); ?>
    <section class="card">
        <h2>AR + WebXR Spatial Anchoring</h2>
        <p class="muted">รองรับการดู 3D model พร้อม overlay ข้อมูลสำคัญของสารเคมีบนอุปกรณ์ที่รองรับ WebXR</p>

        <?php if (!empty($list)): ?>
            <form method="get" class="form-row" style="margin-bottom:12px">
                <input type="hidden" name="page" value="ar-viewer">
                <select name="container_id">
                    <?php foreach ($list as $item): ?>
                        <option value="<?= (int) $item['id'] ?>" <?= ((int) $item['id'] === $selectedId || (!$selectedId && $item === $list[0])) ? 'selected' : '' ?>>
                            <?= pro_h($item['container_code']) ?> - <?= pro_h($item['chemical_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="btn" type="submit">Load Model</button>
            </form>
        <?php endif; ?>

        <?php if ($row): ?>
        <div class="model-wrap">
            <model-viewer
                src="<?= pro_h($row['model_url']) ?>"
                ar
                ar-modes="webxr scene-viewer quick-look"
                ar-scale="fixed"
                camera-controls
                environment-image="neutral"
                shadow-intensity="1"
                xr-environment>
            </model-viewer>
            <div class="overlay">
                <div><strong><?= pro_h($row['chemical_name']) ?></strong></div>
                <div>Container: <?= pro_h($row['container_code']) ?></div>
                <div>Owner: <?= pro_h($row['owner_name']) ?></div>
                <div>Remaining: <?= pro_h((string) $row['qty_remaining']) . ' ' . pro_h($row['unit']) ?></div>
            </div>
        </div>
        <?php else: ?>
            <p class="muted">ยังไม่มี model_url ใน pro_containers</p>
        <?php endif; ?>
    </section>
</main>
<?php pro_footer(); ?>
