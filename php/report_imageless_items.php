<?php
require_once __DIR__ . '/config.php';

$conn = getDBConnection();

function is_remote_url($s){ return $s && (stripos($s,'http://')===0 || stripos($s,'https://')===0); }

$restaurant_id = isset($_GET['restaurant_id']) ? intval($_GET['restaurant_id']) : 0; // 0 = all
$include_unavailable = isset($_GET['all']) && $_GET['all'] === '1';

$assetsDir = realpath(__DIR__ . '/../assets/images');
if ($assetsDir === false) $assetsDir = __DIR__ . '/../assets/images';

// Fetch items
$where = [];
$params = [];
$types = '';
if ($restaurant_id > 0) { $where[] = 'm.restaurant_id = ?'; $types .= 'i'; $params[] = $restaurant_id; }
if (!$include_unavailable) { $where[] = 'm.is_available = 1'; }
$whereSql = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));

$sql = "SELECT m.id, m.name, m.category, m.image, m.is_available, r.id AS rid, r.name AS rname, r.cuisine
        FROM menu_items m
        JOIN restaurants r ON m.restaurant_id = r.id
        $whereSql
        ORDER BY r.name ASC, m.name ASC";

$stmt = $types ? $conn->prepare($sql) : null;
if ($stmt) { $stmt->bind_param($types, ...$params); $stmt->execute(); $res = $stmt->get_result(); }
else { $res = $conn->query($sql); }

$rows = [];
while ($row = $res->fetch_assoc()) { $rows[] = $row; }
if ($stmt) $stmt->close();

// Filter to only imageless (no image or invalid local file)
$imageless = [];
foreach ($rows as $row) {
    $img = trim($row['image'] ?? '');
    if ($img === '') { $imageless[] = $row; continue; }
    if (is_remote_url($img)) { continue; }
    $path = $assetsDir . DIRECTORY_SEPARATOR . $img;
    if (!(is_file($path) && filesize($path) > 0)) { $imageless[] = $row; }
}

// CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=imageless_items.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Restaurant ID','Restaurant Name','Cuisine','Item ID','Item Name','Category','Available','Image']);
    foreach ($imageless as $r) {
        fputcsv($out, [$r['rid'], $r['rname'], $r['cuisine'], $r['id'], $r['name'], $r['category'], $r['is_available'] ? 'Yes':'No', $r['image']]);
    }
    fclose($out);
    exit;
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Items Missing Images</title>
<link rel="stylesheet" href="../styles/main.css">
<style>
.table { width: 100%; border-collapse: collapse; }
.table th, .table td { padding: 0.75rem; border-bottom: 1px solid #eee; text-align: left; }
.table th { background: #f8f9fa; }
.toolbar { display: flex; gap: 0.75rem; align-items: center; margin: 1rem 0; flex-wrap: wrap; }
.badge { display: inline-block; padding: 0.2rem 0.5rem; border-radius: 999px; background: #eef; color: #334; font-size: 0.85rem; }
</style>
</head>
<body>
<div class="container" style="padding:2rem 0; max-width: 1100px;">
    <h1>Items Missing Images</h1>
    <form method="get" class="toolbar">
        <div>
            <label>Restaurant ID</label>
            <input type="number" class="form-control" name="restaurant_id" value="<?= htmlspecialchars($restaurant_id) ?>" placeholder="0 = all">
        </div>
        <div>
            <label>&nbsp;</label>
            <label style="display:block"><input type="checkbox" name="all" value="1" <?= $include_unavailable?'checked':'' ?>> Include Unavailable Items</label>
        </div>
        <div>
            <label>&nbsp;</label>
            <button class="btn btn-outline" type="submit">Filter</button>
        </div>
        <div style="margin-left:auto; display:flex; gap:0.5rem;">
            <a class="btn btn-primary" href="?<?= $restaurant_id>0?('restaurant_id='.intval($restaurant_id).'&'):'' ?><?= $include_unavailable?'all=1&':'' ?>export=csv">Export CSV</a>
            <span class="badge">Imageless: <?= count($imageless) ?></span>
        </div>
    </form>

    <div class="card" style="padding:1rem;">
        <table class="table">
            <thead>
                <tr>
                    <th>Restaurant</th>
                    <th>Cuisine</th>
                    <th>Item ID</th>
                    <th>Item Name</th>
                    <th>Category</th>
                    <th>Available</th>
                    <th>Image</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($imageless as $r): ?>
                    <tr>
                        <td>[#<?= (int)$r['rid'] ?>] <?= htmlspecialchars($r['rname']) ?></td>
                        <td><?= htmlspecialchars($r['cuisine']) ?></td>
                        <td><?= (int)$r['id'] ?></td>
                        <td><?= htmlspecialchars($r['name']) ?></td>
                        <td><?= htmlspecialchars($r['category']) ?></td>
                        <td><?= $r['is_available'] ? 'Yes' : 'No' ?></td>
                        <td><?= htmlspecialchars($r['image']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card" style="padding:1rem; margin-top:1rem;">
        <h3>How to add images</h3>
        <ul>
            <li>Manage Menu: paste Image URL (http/https) or upload a file.</li>
            <li>Strict local mapping: place files in assets/images named as dish slugs (e.g., “chicken-katsu-curry.jpg”), then run <code>assign_menu_images.php?strict=1&force=1</code>.</li>
            <li>Bulk CSV import: use <code>import_menu_images.php</code> with columns <strong>dish</strong>, optional <em>restaurant</em>, and <strong>url</strong>.</li>
        </ul>
    </div>
</div>
</body>
</html>
