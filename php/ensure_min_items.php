<?php
require_once __DIR__ . '/config.php';

$conn = getDBConnection();

function is_remote_url($s){ return $s && (stripos($s,'http://')===0 || stripos($s,'https://')===0); }

$min = isset($_GET['min']) ? max(1, intval($_GET['min'])) : 20;
$restaurant_id = isset($_GET['restaurant_id']) ? intval($_GET['restaurant_id']) : 0; // 0 = all
$images_only = isset($_GET['images_only']) ? (intval($_GET['images_only']) === 1) : 1; // default true
$confirm = isset($_GET['confirm']) && $_GET['confirm'] === '1';

$assetsDir = realpath(__DIR__ . '/../assets/images');
if ($assetsDir === false) $assetsDir = __DIR__ . '/../assets/images';

// Load restaurants
if ($restaurant_id > 0) {
    $stmt = $conn->prepare("SELECT id, name, cuisine FROM restaurants WHERE id = ?");
    $stmt->bind_param('i', $restaurant_id);
    $stmt->execute();
    $restaurants = $stmt->get_result();
    $stmt->close();
} else {
    $restaurants = $conn->query("SELECT id, name, cuisine FROM restaurants");
}

$report = [];
$total_created = 0; $errors = 0;

while ($rest = $restaurants->fetch_assoc()) {
    $rid = (int)$rest['id'];
    // Fetch current items
    $items = $conn->prepare("SELECT id, name, category, price, discount, description, image, is_available FROM menu_items WHERE restaurant_id = ? ORDER BY id ASC");
    $items->bind_param('i', $rid);
    $items->execute();
    $res = $items->get_result();
    $rows = [];
    while ($row = $res->fetch_assoc()) { $rows[] = $row; }
    $items->close();

    $count = count($rows);
    if ($count >= $min) {
        $report[] = "[OK] {$rest['name']} (#$rid) has $count items (>= $min)";
        continue;
    }

    // Filter by images when requested
    $candidates = $rows;
    if ($images_only) {
        $filtered = [];
        foreach ($rows as $r) {
            $img = trim($r['image'] ?? '');
            if ($img === '') continue;
            if (is_remote_url($img)) { $filtered[] = $r; continue; }
            $path = $assetsDir . DIRECTORY_SEPARATOR . $img;
            if (is_file($path) && filesize($path) > 0) $filtered[] = $r;
        }
        $candidates = $filtered;
    }

    if (empty($candidates)) {
        $report[] = "[SKIP] {$rest['name']} (#$rid): no suitable items to clone (images_only=".($images_only?"1":"0").")";
        continue;
    }

    $needed = $min - $count;
    $created_here = 0;

    if ($confirm) {
        $ins = $conn->prepare("INSERT INTO menu_items (restaurant_id, name, category, price, discount, description, image, is_available) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach (range(1, $needed) as $i) {
            $src = $candidates[($i-1) % count($candidates)];
            // Make a unique variant name
            $baseName = $src['name'];
            $variant = " ".(ceil(($count + $created_here + 1)/count($candidates)));
            $newName = $baseName.$variant;
            $cat = $src['category'];
            $price = (float)$src['price'];
            $discount = (float)$src['discount'];
            $desc = $src['description'];
            $img = $src['image'];
            $avail = 1; // make new items available
            $ins->bind_param('issddssi', $rid, $newName, $cat, $price, $discount, $desc, $img, $avail);
            if ($ins->execute()) { $created_here++; $total_created++; }
            else { $errors++; }
        }
        $ins->close();
        $report[] = "[ADD] {$rest['name']} (#$rid): created $created_here items to reach $min";
    } else {
        $report[] = "[DRY] {$rest['name']} (#$rid): would create $needed items (images_only=".($images_only?"1":"0").")";
    }
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Ensure Minimum Items</title>
<link rel="stylesheet" href="../styles/main.css">
</head>
<body>
<div class="container" style="padding:2rem 0; max-width: 900px;">
    <h1>Ensure Minimum Items per Restaurant</h1>
    <form method="GET" style="display:flex; gap:1rem; flex-wrap:wrap; align-items:end; margin-bottom:1rem;">
        <div>
            <label>Restaurant ID (0 = all)</label>
            <input type="number" name="restaurant_id" class="form-control" value="<?= htmlspecialchars($restaurant_id) ?>">
        </div>
        <div>
            <label>Minimum Items</label>
            <input type="number" name="min" class="form-control" min="1" value="<?= htmlspecialchars($min) ?>">
        </div>
        <div>
            <label>Images Only</label>
            <select name="images_only" class="form-control">
                <option value="1" <?= $images_only?'selected':'' ?>>Yes</option>
                <option value="0" <?= !$images_only?'selected':'' ?>>No</option>
            </select>
        </div>
        <div>
            <label>&nbsp;</label>
            <button class="btn btn-outline" type="submit">Preview (Dry Run)</button>
        </div>
    </form>

    <div class="card" style="padding:1rem;">
        <h3>Report</h3>
        <pre style="white-space:pre-wrap;">
<?= htmlspecialchars(implode("\n", $report)) ?>
        </pre>
        <?php if (!$confirm): ?>
            <form method="GET" onsubmit="return confirm('Proceed to create items?');">
                <input type="hidden" name="restaurant_id" value="<?= htmlspecialchars($restaurant_id) ?>">
                <input type="hidden" name="min" value="<?= htmlspecialchars($min) ?>">
                <input type="hidden" name="images_only" value="<?= $images_only?1:0 ?>">
                <input type="hidden" name="confirm" value="1">
                <button class="btn btn-primary" type="submit">Create Missing Items</button>
            </form>
        <?php else: ?>
            <p><strong>Created:</strong> <?= (int)$total_created ?> | <strong>Errors:</strong> <?= (int)$errors ?></p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
