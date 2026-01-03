<?php
require_once __DIR__ . '/config.php';

$conn = getDBConnection();

header('Content-Type: text/html; charset=utf-8');

// Helper: fetch restaurant by name (case-insensitive exact)
function find_restaurant_by_name($conn, $name) {
    $sql = "SELECT id, name, cuisine FROM restaurants WHERE LOWER(name) = LOWER(?) LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

// Helper: rename restaurant if exists
function rename_restaurant($conn, $fromName, $toName) {
    $r = find_restaurant_by_name($conn, $fromName);
    if (!$r) return [false, "Restaurant '$fromName' not found."];
    // If destination name already exists, we'll still rename by appending a space if exact conflict? We'll just set; DB should allow duplicates, user asked to change.
    $stmt = $conn->prepare("UPDATE restaurants SET name = ? WHERE id = ?");
    $stmt->bind_param('si', $toName, $r['id']);
    $ok = $stmt->execute();
    $stmt->close();
    if ($ok) return [true, "Renamed #{$r['id']} '$fromName' -> '$toName'"];
    return [false, "Failed to rename '$fromName'"];
}

// Helper: clone items from src restaurant to dst restaurant (skip duplicates by name)
function clone_items($conn, $srcName, $dstName) {
    $src = find_restaurant_by_name($conn, $srcName);
    $dst = find_restaurant_by_name($conn, $dstName);
    if (!$src) return [0, 0, "Source '$srcName' not found."];
    if (!$dst) return [0, 0, "Destination '$dstName' not found."];

    $src_id = (int)$src['id'];
    $dst_id = (int)$dst['id'];

    // Load destination item names for de-duplication
    $map = [];
    $rs = $conn->prepare("SELECT name FROM menu_items WHERE restaurant_id = ?");
    $rs->bind_param('i', $dst_id);
    $rs->execute();
    $res = $rs->get_result();
    while ($row = $res->fetch_assoc()) { $map[strtolower(trim($row['name']))] = true; }
    $rs->close();

    // Load source items
    $q = $conn->prepare("SELECT name, category, price, discount, description, image, is_available FROM menu_items WHERE restaurant_id = ?");
    $q->bind_param('i', $src_id);
    $q->execute();
    $src_items = $q->get_result();

    $ins = $conn->prepare("INSERT INTO menu_items (restaurant_id, name, category, price, discount, description, image, is_available) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $ok = 0; $skip = 0;
    while ($it = $src_items->fetch_assoc()) {
        $key = strtolower(trim($it['name']));
        if (isset($map[$key])) { $skip++; continue; }
        $name = $it['name'];
        $cat = $it['category'];
        $price = (float)$it['price'];
        $discount = (float)$it['discount'];
        $desc = $it['description'];
        $image = $it['image'];
        $avail = (int)$it['is_available'];
        $ins->bind_param('issddssi', $dst_id, $name, $cat, $price, $discount, $desc, $image, $avail);
        if ($ins->execute()) { $ok++; $map[$key] = true; } else { $skip++; }
    }
    $ins->close();
    $q->close();

    return [$ok, $skip, "Cloned from '{$src['name']}'(#$src_id) to '{$dst['name']}'(#$dst_id)"];
}

// Operations per user request
$ops = [
    ['type' => 'rename', 'from' => 'Pizza Palace 2', 'to' => 'Pizza Hub'],
    ['type' => 'rename', 'from' => 'Burger Hub 2', 'to' => 'Burger King'],
    ['type' => 'clone',  'from' => 'Pizza Palace', 'to' => 'Pizza Hub'],
    ['type' => 'clone',  'from' => 'Burger Hub',  'to' => 'Burger King'],
    ['type' => 'clone',  'from' => 'South Spice', 'to' => 'Cafe Delight'],
    // After rename, attempt to clone from the old name first; if not found, clone from new name
    ['type' => 'clone',  'from' => 'Pizza Palace 2', 'to' => 'South Spice'],
];

$confirm = isset($_GET['confirm']) && $_GET['confirm'] === '1';

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Rename and Clone Menus</title>
<link rel="stylesheet" href="../styles/main.css">
</head>
<body>
<div class="container" style="padding:2rem 0; max-width: 900px;">
    <h1>Rename and Clone Menus</h1>
    <div class="card" style="padding:1rem;">
        <h3>Planned Operations</h3>
        <ul>
            <li>Rename "Pizza Palace 2" -> "Pizza Hub"</li>
            <li>Rename "Burger Hub 2" -> "Burger King"</li>
            <li>Clone items: Pizza Palace -> Pizza Hub</li>
            <li>Clone items: Burger Hub -> Burger King</li>
            <li>Clone items: South Spice -> Cafe Delight</li>
            <li>Clone items: Pizza Palace 2 -> South Spice (if not found, try from Pizza Hub)</li>
        </ul>
        <?php if (!$confirm): ?>
            <form method="get" onsubmit="return confirm('Apply these changes now?');">
                <input type="hidden" name="confirm" value="1">
                <button class="btn btn-primary" type="submit">Apply Changes</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($confirm): ?>
        <div class="card" style="padding:1rem; margin-top:1rem;">
            <h3>Execution Log</h3>
            <pre style="white-space: pre-wrap;">
<?php
// Execute
foreach ($ops as $op) {
    if ($op['type'] === 'rename') {
        [$ok, $msg] = rename_restaurant($conn, $op['from'], $op['to']);
        echo ($ok ? '[OK] ' : '[ERR] ') . $msg . "\n";
    } elseif ($op['type'] === 'clone') {
        // Try clone; if source not found and it's 'Pizza Palace 2', retry with 'Pizza Hub'
        [$ok, $skip, $msg] = clone_items($conn, $op['from'], $op['to']);
        if (strpos($msg, 'not found') !== false && strtolower($op['from']) === 'pizza palace 2') {
            [$ok, $skip, $msg] = clone_items($conn, 'Pizza Hub', $op['to']);
        }
        echo "[CLONE] $msg | Inserted: $ok | Skipped: $skip\n";
    }
}
?>
            </pre>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
