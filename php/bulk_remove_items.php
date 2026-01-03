<?php
require_once __DIR__ . '/config.php';

$conn = getDBConnection();

$ids_param = isset($_GET['ids']) ? trim($_GET['ids']) : '';
$action = isset($_GET['action']) && in_array($_GET['action'], ['delete','disable']) ? $_GET['action'] : 'disable';
$confirm = isset($_GET['confirm']) && $_GET['confirm'] === '1';

// Parse IDs
$ids = [];
if ($ids_param !== '') {
    foreach (explode(',', $ids_param) as $tok) {
        $v = intval(trim($tok));
        if ($v > 0) $ids[] = $v;
    }
}
$ids = array_values(array_unique($ids));

$items = [];
if (!empty($ids)) {
    // Build placeholders for IN clause
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $sql = "SELECT m.id, m.name, m.category, m.is_available, r.id AS rid, r.name AS rname, r.cuisine
            FROM menu_items m
            JOIN restaurants r ON m.restaurant_id = r.id
            WHERE m.id IN ($placeholders)
            ORDER BY r.name, m.name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) { $items[] = $row; }
    $stmt->close();
}

$affected = 0; $errors = 0; $log = [];
if ($confirm && !empty($items)) {
    if ($action === 'delete') {
        $stmt = $conn->prepare('DELETE FROM menu_items WHERE id = ? LIMIT 1');
        foreach ($items as $it) {
            $id = (int)$it['id'];
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) { $affected++; $log[] = "DELETED #$id {$it['name']}"; }
            else { $errors++; $log[] = "ERROR delete #$id {$it['name']}"; }
        }
        $stmt->close();
    } else {
        $stmt = $conn->prepare('UPDATE menu_items SET is_available = 0 WHERE id = ?');
        foreach ($items as $it) {
            $id = (int)$it['id'];
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) { $affected++; $log[] = "DISABLED #$id {$it['name']}"; }
            else { $errors++; $log[] = "ERROR disable #$id {$it['name']}"; }
        }
        $stmt->close();
    }
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Bulk Remove Items</title>
<link rel="stylesheet" href="../styles/main.css">
<style>
.table { width:100%; border-collapse: collapse; }
.table th, .table td { padding: 0.75rem; border-bottom: 1px solid #eee; text-align: left; }
.table th { background: #f8f9fa; }
.toolbar { display:flex; gap:0.75rem; align-items: end; margin:1rem 0; flex-wrap: wrap; }
</style>
</head>
<body>
<div class="container" style="padding:2rem 0; max-width: 1000px;">
    <h1>Bulk Remove Items</h1>
    <form method="get" class="toolbar">
        <div style="flex:1; min-width: 380px;">
            <label>Item IDs (comma-separated)</label>
            <input type="text" class="form-control" name="ids" value="<?= htmlspecialchars($ids_param) ?>" placeholder="e.g. 87,49,54">
        </div>
        <div>
            <label>Action</label>
            <select name="action" class="form-control">
                <option value="disable" <?= $action==='disable'?'selected':'' ?>>Disable (set unavailable)</option>
                <option value="delete" <?= $action==='delete'?'selected':'' ?>>Delete</option>
            </select>
        </div>
        <div>
            <label>&nbsp;</label>
            <button class="btn btn-outline" type="submit">Preview</button>
        </div>
    </form>

    <?php if (!empty($ids) && empty($items)): ?>
        <div class="card" style="padding:1rem; margin-bottom:1rem;">No matching items found for the provided IDs.</div>
    <?php endif; ?>

    <?php if (!empty($items)): ?>
    <div class="card" style="padding:1rem;">
        <table class="table">
            <thead>
                <tr>
                    <th>Restaurant</th>
                    <th>Item ID</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Available</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td>[#<?= (int)$it['rid'] ?>] <?= htmlspecialchars($it['rname']) ?> (<?= htmlspecialchars($it['cuisine']) ?>)</td>
                        <td><?= (int)$it['id'] ?></td>
                        <td><?= htmlspecialchars($it['name']) ?></td>
                        <td><?= htmlspecialchars($it['category']) ?></td>
                        <td><?= $it['is_available'] ? 'Yes':'No' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <form method="get" onsubmit="return confirm('Are you sure you want to <?= $action ?> these <?= count($items) ?> items?');" style="margin-top:1rem; display:flex; gap:0.75rem;">
            <input type="hidden" name="ids" value="<?= htmlspecialchars(implode(',', $ids)) ?>">
            <input type="hidden" name="action" value="<?= htmlspecialchars($action) ?>">
            <input type="hidden" name="confirm" value="1">
            <button class="btn btn-danger" type="submit"><?php echo $action==='delete'?'Delete Items':'Disable Items'; ?></button>
        </form>
    </div>
    <?php endif; ?>

    <?php if ($confirm): ?>
        <div class="card" style="padding:1rem; margin-top:1rem;">
            <p><strong>Action:</strong> <?= htmlspecialchars($action) ?> | <strong>Affected:</strong> <?= (int)$affected ?> | <strong>Errors:</strong> <?= (int)$errors ?></p>
            <?php if (!empty($log)): ?>
                <pre style="white-space: pre-wrap;"><?= htmlspecialchars(implode("\n", $log)) ?></pre>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
