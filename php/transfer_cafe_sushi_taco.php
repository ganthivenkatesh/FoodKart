<?php
require_once __DIR__ . '/config.php';

$conn = getDBConnection();

header('Content-Type: text/html; charset=utf-8');

function find_restaurant($conn, $ref) {
    if (ctype_digit((string)$ref)) {
        $id = (int)$ref;
        $stmt = $conn->prepare("SELECT id, name FROM restaurants WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }
    $sql = "SELECT id, name FROM restaurants WHERE LOWER(name) = LOWER(?) LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $ref);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function get_items($conn, $rid) {
    $stmt = $conn->prepare("SELECT id, name, category, price, discount, description, image, is_available FROM menu_items WHERE restaurant_id = ?");
    $stmt->bind_param('i', $rid);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) { $rows[] = $r; }
    $stmt->close();
    return $rows;
}

function clone_items_unique($conn, $src_id, $dst_id) {
    // Load destination names to avoid duplicates
    $names = [];
    $rs = $conn->prepare("SELECT name FROM menu_items WHERE restaurant_id = ?");
    $rs->bind_param('i', $dst_id);
    $rs->execute();
    $res = $rs->get_result();
    while ($row = $res->fetch_assoc()) { $names[strtolower(trim($row['name']))] = true; }
    $rs->close();

    $src_items = get_items($conn, $src_id);
    $ins = $conn->prepare("INSERT INTO menu_items (restaurant_id, name, category, price, discount, description, image, is_available) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $ok = 0; $skip = 0;
    foreach ($src_items as $it) {
        $key = strtolower(trim($it['name']));
        if (isset($names[$key])) { $skip++; continue; }
        $ins->bind_param('issddssi', $dst_id, $it['name'], $it['category'], $it['price'], $it['discount'], $it['description'], $it['image'], $it['is_available']);
        if ($ins->execute()) { $ok++; $names[$key] = true; } else { $skip++; }
    }
    $ins->close();
    return [$ok, $skip];
}

function delete_items($conn, $rid) {
    $stmt = $conn->prepare('DELETE FROM menu_items WHERE restaurant_id = ?');
    $stmt->bind_param('i', $rid);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    return $affected;
}

// Names as per request
$cafe = find_restaurant($conn, 'Cafe Delight');
$sushi = find_restaurant($conn, 'Sushi Bay');
$taco = find_restaurant($conn, 'Taco Fiesta');

$missing = [];
if (!$cafe) $missing[] = 'Cafe Delight';
if (!$sushi) $missing[] = 'Sushi Bay';
if (!$taco) $missing[] = 'Taco Fiesta';

$confirm = isset($_GET['confirm']) && $_GET['confirm'] === '1';
$replace_mode = isset($_GET['replace']) ? (int)$_GET['replace'] : 1; // 1=clear before cloning, 0=append unique

// Generic transfer mode via params: src, dst (name or id)
$param_src = isset($_GET['src']) ? trim($_GET['src']) : '';
$param_dst = isset($_GET['dst']) ? trim($_GET['dst']) : '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Transfer Menus: Cafe -> Sushi, Sushi -> Taco</title>
<link rel="stylesheet" href="../styles/main.css">
</head>
<body>
<div class="container" style="padding:2rem 0; max-width: 900px;">
    <h1>Transfer Menus</h1>
    <div class="card" style="padding:1rem;">
        <h3>Plan</h3>
        <ol>
            <li>Move Sushi Bay's current items to Taco Fiesta (clone unique into Taco).</li>
            <li><?php echo $replace_mode? 'Replace' : 'Append to'; ?> Sushi Bay items with Cafe Delight's items.</li>
        </ol>
        <?php if (!empty($missing)): ?>
            <p style="color:#b00;">Missing restaurants: <?php echo htmlspecialchars(implode(', ', $missing)); ?>. Please create or correct names.</p>
        <?php else: ?>
            <p>
                Cafe Delight (#<?php echo (int)$cafe['id']; ?>),
                Sushi Bay (#<?php echo (int)$sushi['id']; ?>),
                Taco Fiesta (#<?php echo (int)$taco['id']; ?>)
            </p>
            <form method="get" onsubmit="return confirm('Apply the transfer now?');" style="display:flex; gap:0.75rem; align-items:center;">
                <input type="hidden" name="confirm" value="1">
                <label><input type="checkbox" name="replace" value="1" <?php echo $replace_mode? 'checked' : ''; ?>> Replace Sushi Bay items before cloning Cafe</label>
                <button class="btn btn-primary" type="submit">Apply</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($confirm && empty($missing)): ?>
    <div class="card" style="padding:1rem; margin-top:1rem;">
        <h3>Execution Log</h3>
        <pre style="white-space: pre-wrap;">
<?php
// Step 1: Move Sushi -> Taco (clone unique)
[$ok1, $skip1] = clone_items_unique($conn, (int)$sushi['id'], (int)$taco['id']);
echo "Cloned from Sushi Bay -> Taco Fiesta | Inserted: $ok1 | Skipped (duplicate names): $skip1\n";

// Step 2: Replace or append Cafe -> Sushi
$deleted = 0;
if ($replace_mode) {
    $deleted = delete_items($conn, (int)$sushi['id']);
    echo "Cleared Sushi Bay items: $deleted deleted\n";
}
[$ok2, $skip2] = clone_items_unique($conn, (int)$cafe['id'], (int)$sushi['id']);
echo "Cloned from Cafe Delight -> Sushi Bay | Inserted: $ok2 | Skipped (duplicate names): $skip2\n";
?>
        </pre>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
