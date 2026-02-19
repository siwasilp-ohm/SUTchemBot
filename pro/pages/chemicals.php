<?php
require_once __DIR__ . '/../includes/db.php';
pro_require_permission('chemicals.view');
pro_header('Chemicals & Transactions', $user);
pro_nav('chemicals');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!pro_verify_csrf($_POST['csrf_token'] ?? null)) {
        pro_flash('Security token ไม่ถูกต้อง', 'error');
        header('Location: index.php?page=chemicals');
        exit;
    }

    if (!pro_can('chemicals.transact')) {
        pro_flash('บทบาทนี้ไม่สามารถทำธุรกรรมยืม/คืนได้', 'error');
        header('Location: index.php?page=chemicals');
        exit;
    }

    $containerId = (int) ($_POST['container_id'] ?? 0);
    $qty = (float) ($_POST['quantity'] ?? 0);
    $action = $_POST['action_type'] ?? 'borrow';

    if ($containerId <= 0 || $qty <= 0 || !in_array($action, ['borrow', 'return'], true)) {
        pro_flash('ข้อมูลธุรกรรมไม่ถูกต้อง', 'error');
        header('Location: index.php?page=chemicals');
        exit;
    }

    $pdo = pro_db();
    $pdo->beginTransaction();

    try {
        $container = $pdo->prepare('SELECT id, qty_remaining, unit FROM pro_containers WHERE id = :id FOR UPDATE');
        $container->execute([':id' => $containerId]);
        $row = $container->fetch();

        if (!$row) {
            throw new RuntimeException('ไม่พบ container ที่เลือก');
        }

        $currentQty = (float) $row['qty_remaining'];
        if ($action === 'borrow' && $qty > $currentQty) {
            throw new RuntimeException('จำนวนที่ยืมมากกว่าคงเหลือในคลัง');
        }

        $newQty = $action === 'borrow' ? $currentQty - $qty : $currentQty + $qty;

        $pdo->prepare('UPDATE pro_containers SET qty_remaining = :qty WHERE id = :id')
            ->execute([':qty' => $newQty, ':id' => $containerId]);

        $pdo->prepare('INSERT INTO pro_transactions (container_id, user_id, action_type, quantity, status, note) VALUES (:container_id, :user_id, :action_type, :quantity, :status, :note)')
            ->execute([
                ':container_id' => $containerId,
                ':user_id' => $user['id'],
                ':action_type' => $action,
                ':quantity' => $qty,
                ':status' => $action === 'borrow' ? 'open' : 'closed',
                ':note' => strtoupper($action) . ' by ' . $user['username'],
            ]);

        $pdo->commit();
        pro_flash('บันทึกธุรกรรมเรียบร้อย', 'success');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        pro_flash($e->getMessage(), 'error');
    }

    header('Location: index.php?page=chemicals');
    exit;
}

$rows = pro_db()->query('SELECT c.id, c.container_code, c.owner_name, c.qty_remaining, c.unit, c.reorder_point, ch.name AS chemical_name
FROM pro_containers c JOIN pro_chemicals ch ON ch.id = c.chemical_id ORDER BY c.id DESC')->fetchAll();
$txRows = pro_db()->query('SELECT t.created_at, t.action_type, t.quantity, t.status, t.note, c.container_code, u.username
FROM pro_transactions t
JOIN pro_containers c ON c.id = t.container_id
JOIN pro_users u ON u.id = t.user_id
ORDER BY t.id DESC LIMIT 12')->fetchAll();
?>
<main class="container">
    <?php pro_render_flash(); ?>
    <section class="card">
        <h2>Chemical Containers</h2>
        <table class="table">
            <thead><tr><th>Code</th><th>Chemical</th><th>Owner</th><th>Remaining</th><th>Status</th><th>Transaction</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= pro_h($r['container_code']) ?></td>
                    <td><?= pro_h($r['chemical_name']) ?></td>
                    <td><?= pro_h($r['owner_name']) ?></td>
                    <td><?= pro_h((string) $r['qty_remaining']) . ' ' . pro_h($r['unit']) ?></td>
                    <td><?= $r['qty_remaining'] <= $r['reorder_point'] ? '<span class="badge">Low stock</span>' : 'Normal' ?></td>
                    <td>
                        <?php if (pro_can('chemicals.transact')): ?>
                        <form method="post" class="form-row">
                            <input type="hidden" name="csrf_token" value="<?= pro_h(pro_csrf_token()) ?>">
                            <input type="hidden" name="container_id" value="<?= (int) $r['id'] ?>">
                            <select name="action_type"><option value="borrow">Borrow</option><option value="return">Return</option></select>
                            <input name="quantity" type="number" step="0.01" min="0.01" placeholder="Qty" required>
                            <button class="btn ok" type="submit">Submit</button>
                        </form>
                        <?php else: ?>
                            <span class="muted">View only</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section class="card" style="margin-top:14px">
        <h3>Recent Transactions</h3>
        <table class="table">
            <thead><tr><th>Time</th><th>Container</th><th>Action</th><th>Qty</th><th>User</th><th>Status</th><th>Note</th></tr></thead>
            <tbody>
            <?php foreach ($txRows as $tx): ?>
                <tr>
                    <td><?= pro_h($tx['created_at']) ?></td>
                    <td><?= pro_h($tx['container_code']) ?></td>
                    <td><?= pro_h(strtoupper($tx['action_type'])) ?></td>
                    <td><?= pro_h((string) $tx['quantity']) ?></td>
                    <td><?= pro_h($tx['username']) ?></td>
                    <td><?= pro_h($tx['status']) ?></td>
                    <td><?= pro_h((string) $tx['note']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>
<?php pro_footer(); ?>
