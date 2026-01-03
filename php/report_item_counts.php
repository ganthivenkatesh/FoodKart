<?php
require_once __DIR__ . '/config.php';

$conn = getDBConnection();

$include_unavailable = isset($_GET['all']) && $_GET['all'] === '1';

$sql = "SELECT r.id, r.name, r.cuisine, COUNT(m.id) AS item_count
        FROM restaurants r
        LEFT JOIN menu_items m
          ON m.restaurant_id = r.id " . ($include_unavailable ? "" : "AND m.is_available = 1") . "
        GROUP BY r.id, r.name, r.cuisine
        ORDER BY item_count DESC, r.name ASC";
$result = $conn->query($sql);

$rows = [];
if ($result) {
    while ($row = $result->fetch_assoc()) { $rows[] = $row; }
}

// CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=item_counts.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Restaurant ID', 'Restaurant Name', 'Cuisine', 'Item Count' . ($include_unavailable ? ' (all)' : ' (available)')]);
    foreach ($rows as $r) {
        fputcsv($out, [$r['id'], $r['name'], $r['cuisine'], $r['item_count']]);
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
<title>Restaurant Item Counts</title>
<link rel="stylesheet" href="../styles/main.css">
<style>
.table { width: 100%; border-collapse: collapse; }
.table th, .table td { padding: 0.75rem; border-bottom: 1px solid #eee; text-align: left; }
.table th { background: #f8f9fa; }
.toolbar { display: flex; gap: 0.75rem; align-items: center; margin: 1rem 0; }
.badge { display: inline-block; padding: 0.2rem 0.5rem; border-radius: 999px; background: #eef; color: #334; font-size: 0.85rem; }
</style>
</head>
<body>
<div class="container" style="padding:2rem 0; max-width: 1000px;">
    <h1>Restaurant Item Counts</h1>
    <div class="toolbar">
        <a class="btn btn-outline" href="?<?= $include_unavailable ? '' : 'all=1' ?>"><?= $include_unavailable ? 'Show Available Only' : 'Include Unavailable' ?></a>
        <a class="btn btn-primary" href="?<?= $include_unavailable ? 'all=1&' : '' ?>export=csv">Export CSV</a>
        <span class="badge">Total Restaurants: <?= count($rows) ?></span>
    </div>
    <div class="card" style="padding: 1rem;">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Cuisine</th>
                    <th>Item Count<?= $include_unavailable ? ' (all)' : ' (available)' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= (int)$r['id'] ?></td>
                        <td><?= htmlspecialchars($r['name']) ?></td>
                        <td><?= htmlspecialchars($r['cuisine']) ?></td>
                        <td><?= (int)$r['item_count'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
