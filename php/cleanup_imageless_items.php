<?php
require_once __DIR__ . '/config.php';

$conn = getDBConnection();

function is_remote_url($s) {
    return $s && (stripos($s, 'http://') === 0 || stripos($s, 'https://') === 0);
}

$assetsDir = realpath(__DIR__ . '/../assets/images');
if ($assetsDir === false) $assetsDir = __DIR__ . '/../assets/images';

$dry_run = !isset($_GET['confirm']) || $_GET['confirm'] !== '1';
$action  = isset($_GET['action']) && in_array($_GET['action'], ['delete','disable']) ? $_GET['action'] : 'disable';
$restaurant_id = isset($_GET['restaurant_id']) ? intval($_GET['restaurant_id']) : 0; // optional scope

$where = $restaurant_id > 0 ? 'WHERE restaurant_id = ?' : '';
$sql = "SELECT id, restaurant_id, name, category, image FROM menu_items $where ORDER BY restaurant_id, name";
$stmt = $restaurant_id > 0 ? $conn->prepare($sql) : null;
if ($stmt) { $stmt->bind_param('i', $restaurant_id); $stmt->execute(); $res = $stmt->get_result(); }
else { $res = $conn->query($sql); }

$bad = [];
while ($row = $res->fetch_assoc()) {
    $img = trim($row['image'] ?? '');
    if ($img === '') { $bad[] = $row; continue; }
    if (is_remote_url($img)) {
        // Accept remote URL as valid (do not fetch)
        continue;
    }
    $path = $assetsDir . DIRECTORY_SEPARATOR . $img;
    if (!(is_file($path) && filesize($path) > 0)) {
        $bad[] = $row;
    }
}
if ($stmt) $stmt->close();

$affected = 0; $errors = 0; $log = [];
if (!$dry_run && count($bad) > 0) {
    if ($action === 'delete') {
        $del = $conn->prepare('DELETE FROM menu_items WHERE id = ? LIMIT 1');
        foreach ($bad as $it) {
            $id = (int)$it['id'];
            $del->bind_param('i', $id);
            if ($del->execute()) { $affected++; $log[] = "DELETED #$id {$it['name']}"; }
            else { $errors++; $log[] = "ERROR delete #$id {$it['name']}"; }
        }
        $del->close();
    } else {
        $upd = $conn->prepare('UPDATE menu_items SET is_available = 0 WHERE id = ?');
        foreach ($bad as $it) {
            $id = (int)$it['id'];
            $upd->bind_param('i', $id);
            if ($upd->execute()) { $affected++; $log[] = "DISABLED #$id {$it['name']}"; }
            else { $errors++; $log[] = "ERROR disable #$id {$it['name']}"; }
        }
        $upd->close();
    }
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Cleanup Imageless Items</title>
<link rel="stylesheet" href="../styles/main.css">
</head>
<body>
<div class="container" style="padding: 2rem 0; max-width: 1000px;">
    <h1>Cleanup Menu Items Without Valid Images</h1>
    <form method="GET" style="margin: 1rem 0; display: flex; gap: 1rem; align-items: end; flex-wrap: wrap;">
        <div>
            <label>Restaurant ID (optional)</label>
            <input type="number" name="restaurant_id" value="<?= htmlspecialchars($restaurant_id) ?>" class="form-control" placeholder="0 = all">
        </div>
        <div>
            <label>Action</label>
            <select name="action" class="form-control">
                <option value="disable" <?= $action==='disable'?'selected':'' ?>>Disable (is_available=0)</option>
                <option value="delete" <?= $action==='delete'?'selected':'' ?>>Delete Items</option>
            </select>
        </div>
        <div>
            <label>&nbsp;</label>
            <button class="btn btn-outline" type="submit">Preview (Dry Run)</button>
        </div>
    </form>

    <div class="card" style="padding: 1rem;">
        <h3>Preview</h3>
        <p>Total imageless items found: <strong><?= count($bad) ?></strong></p>
        <?php if ($dry_run): ?>
            <p style="color:#555;">This is a dry run. No changes have been made.</p>
            <?php if (count($bad) > 0): ?>
                <form method="GET" onsubmit="return confirm('Are you sure you want to <?= $action ?> these ' + <?= count($bad) ?> + ' items?');">
                    <input type="hidden" name="restaurant_id" value="<?= htmlspecialchars($restaurant_id) ?>">
                    <input type="hidden" name="action" value="<?= htmlspecialchars($action) ?>">
                    <input type="hidden" name="confirm" value="1">
                    <button class="btn btn-danger" type="submit"><?php echo $action==='delete'?'Delete Items':'Disable Items'; ?></button>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <p><strong>Executed:</strong> <?= htmlspecialchars($action) ?> | Affected: <strong><?= $affected ?></strong> | Errors: <strong><?= $errors ?></strong></p>
        <?php endif; ?>
    </div>

    <div class="card" style="padding: 1rem; margin-top: 1rem;">
        <h3>Items</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Restaurant</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Image</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($bad as $it): ?>
                <tr>
                    <td><?= (int)$it['id'] ?></td>
                    <td><?= (int)$it['restaurant_id'] ?></td>
                    <td><?= htmlspecialchars($it['name']) ?></td>
                    <td><?= htmlspecialchars($it['category']) ?></td>
                    <td><?= htmlspecialchars($it['image']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (!$dry_run && !empty($log)): ?>
            <h4>Log</h4>
            <pre style="white-space: pre-wrap;"><?= htmlspecialchars(implode("\n", $log)) ?></pre>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
