<?php
require_once __DIR__ . '/config.php';

$conn = getDBConnection();

// Params
$strategy = isset($_GET['strategy']) ? $_GET['strategy'] : 'suffix_number'; // suffix_number | suffix_id
$confirm  = isset($_GET['confirm']) && $_GET['confirm'] === '1';
$case_insensitive = true; // treat names case-insensitively

// Load all restaurants
$sql = "SELECT id, name, cuisine, location FROM restaurants ORDER BY name, id";
$res = $conn->query($sql);
$rows = [];
while ($row = $res->fetch_assoc()) { $rows[] = $row; }

// Group by normalized name
$groups = [];
foreach ($rows as $r) {
    $key = $case_insensitive ? strtolower(trim($r['name'])) : trim($r['name']);
    if (!isset($groups[$key])) $groups[$key] = [];
    $groups[$key][] = $r;
}

$renames = [];
foreach ($groups as $key => $list) {
    if (count($list) <= 1) continue; // unique already
    // Sort stable by id
    usort($list, function($a,$b){ return $a['id'] <=> $b['id']; });
    // Keep the first as-is; rename others
    $seen = 1;
    foreach ($list as $idx => $r) {
        if ($idx === 0) continue;
        if ($strategy === 'suffix_id') {
            $new_name = $r['name'] . ' #' . $r['id'];
        } else {
            $seen++;
            $new_name = $r['name'] . ' ' . $seen;
        }
        $renames[] = [
            'id' => (int)$r['id'],
            'old' => $r['name'],
            'new' => $new_name,
            'cuisine' => $r['cuisine'],
            'location' => $r['location'],
        ];
    }
}

$affected = 0; $errors = 0; $log = [];
if ($confirm && !empty($renames)) {
    $stmt = $conn->prepare('UPDATE restaurants SET name = ? WHERE id = ?');
    foreach ($renames as $chg) {
        $stmt->bind_param('si', $chg['new'], $chg['id']);
        if ($stmt->execute()) { $affected++; $log[] = "UPDATED #{$chg['id']}: '{$chg['old']}' -> '{$chg['new']}'"; }
        else { $errors++; $log[] = "ERROR #{$chg['id']}: '{$chg['old']}'"; }
    }
    $stmt->close();
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Deduplicate Restaurant Names</title>
<link rel="stylesheet" href="../styles/main.css">
<style>
.table { width:100%; border-collapse: collapse; }
.table th, .table td { padding: 0.75rem; border-bottom: 1px solid #eee; text-align: left; }
.table th { background: #f8f9fa; }
.toolbar { display:flex; gap:0.75rem; align-items:end; margin:1rem 0; flex-wrap:wrap; }
.badge { display:inline-block; padding:0.2rem 0.5rem; border-radius:999px; background:#eef; color:#334; font-size:0.85rem; }
</style>
</head>
<body>
<div class="container" style="padding:2rem 0; max-width: 1000px;">
    <h1>Deduplicate Restaurant Names</h1>
    <form method="get" class="toolbar">
        <div>
            <label>Strategy</label>
            <select class="form-control" name="strategy">
                <option value="suffix_number" <?= $strategy==='suffix_number'?'selected':'' ?>>Add numeric suffix (Name 2, Name 3)</option>
                <option value="suffix_id" <?= $strategy==='suffix_id'?'selected':'' ?>>Append ID (Name #123)</option>
            </select>
        </div>
        <div>
            <label>&nbsp;</label>
            <button class="btn btn-outline" type="submit">Preview</button>
        </div>
    </form>

    <?php
    // Show preview table of duplicates with proposed new names
    $dupes = array_filter($groups, function($g){ return count($g) > 1; });
    ?>

    <div class="card" style="padding:1rem;">
        <h3>Duplicates Found: <span class="badge"><?= count($dupes) ?></span></h3>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Old Name</th>
                    <th>Proposed New Name</th>
                    <th>Cuisine</th>
                    <th>Location</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($renames)): ?>
                    <tr><td colspan="5">No duplicates detected. All names are unique.</td></tr>
                <?php else: ?>
                    <?php foreach ($renames as $chg): ?>
                        <tr>
                            <td><?= (int)$chg['id'] ?></td>
                            <td><?= htmlspecialchars($chg['old']) ?></td>
                            <td><?= htmlspecialchars($chg['new']) ?></td>
                            <td><?= htmlspecialchars($chg['cuisine']) ?></td>
                            <td><?= htmlspecialchars($chg['location']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if (!empty($renames)): ?>
            <form method="get" onsubmit="return confirm('Proceed to rename <?= count($renames) ?> restaurants?');" style="margin-top:1rem;">
                <input type="hidden" name="strategy" value="<?= htmlspecialchars($strategy) ?>">
                <input type="hidden" name="confirm" value="1">
                <button class="btn btn-primary" type="submit">Apply Renames</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($confirm): ?>
        <div class="card" style="padding:1rem; margin-top:1rem;">
            <p><strong>Affected:</strong> <?= (int)$affected ?> | <strong>Errors:</strong> <?= (int)$errors ?></p>
            <?php if (!empty($log)): ?>
                <pre style="white-space: pre-wrap;"><?= htmlspecialchars(implode("\n", $log)) ?></pre>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
