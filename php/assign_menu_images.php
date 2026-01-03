<?php
require_once __DIR__ . '/config.php';

$conn = getDBConnection();

function slugify($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
    $text = trim($text, '-');
    return $text;
}

function collectImages($dir) {
    $exts = ['jpg','jpeg','png','webp'];
    $images = [];
    if (!is_dir($dir)) return $images;
    $dh = opendir($dir);
    if ($dh === false) return $images;
    while (($file = readdir($dh)) !== false) {
        if ($file === '.' || $file === '..') continue;
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($ext, $exts, true)) {
            $basename = pathinfo($file, PATHINFO_FILENAME);
            $images[] = [
                'file' => $file,
                'basename' => $basename,
                'slug' => slugify($basename),
            ];
        }
    }
    closedir($dh);
    return $images;
}

function bestMatch($nameSlug, $images) {
    // 1) Exact slug match
    foreach ($images as $img) {
        if ($img['slug'] === $nameSlug) return $img['file'];
    }
    // 2) Startswith/contains
    foreach ($images as $img) {
        if (str_starts_with($img['slug'], $nameSlug) || str_contains($img['slug'], $nameSlug)) {
            return $img['file'];
        }
    }
    // 3) Fuzzy: highest similar_text score
    $best = null; $bestScore = 0;
    foreach ($images as $img) {
        similar_text($nameSlug, $img['slug'], $pct);
        if ($pct > $bestScore) { $bestScore = $pct; $best = $img['file']; }
    }
    return $bestScore >= 65 ? $best : null; // threshold
}

$assetsDir = realpath(__DIR__ . '/../assets/images');
if ($assetsDir === false) {
    $assetsDir = __DIR__ . '/../assets/images';
}
$images = collectImages($assetsDir);

$strict = isset($_GET['strict']) && $_GET['strict'] == '1';
$force = isset($_GET['force']) && $_GET['force'] == '1';

header('Content-Type: text/plain');
echo "Auto-assigning images to menu items" . ($force ? " (force overwrite)" : " (missing only)") . ($strict ? " [STRICT]" : "") . "...\n\n";

if (empty($images)) {
    echo "No images found in assets/images. Aborting.\n";
    exit;
}

$sql = $force
    ? "SELECT id, name FROM menu_items"
    : "SELECT id, name FROM menu_items WHERE image IS NULL OR image = ''";
$res = $conn->query($sql);
if (!$res) {
    echo "DB query failed.\n";
    exit;
}

$updated = 0; $skipped = 0; $total = $res->num_rows;
while ($row = $res->fetch_assoc()) {
    $id = (int)$row['id'];
    $name = $row['name'];
    $slug = slugify($name);

    // Try common extension guesses first (exact slug match)
    $guessed = null;
    foreach (['jpg','jpeg','png','webp'] as $ext) {
        $candidate = $assetsDir . DIRECTORY_SEPARATOR . $slug . '.' . $ext;
        if (is_file($candidate) && filesize($candidate) > 0) {
            $guessed = basename($candidate);
            break;
        }
    }

    if ($guessed === null && !$strict) {
        $guessed = bestMatch($slug, $images);
    }

    if ($guessed) {
        $stmt = $conn->prepare("UPDATE menu_items SET image = ? WHERE id = ?");
        $stmt->bind_param('si', $guessed, $id);
        if ($stmt->execute()) {
            $updated++;
            echo "[OK] #$id '$name' -> $guessed\n";
        } else {
            $skipped++;
            echo "[ERR] #$id '$name' -> $guessed (DB update failed)\n";
        }
        $stmt->close();
    } else {
        $skipped++;
        echo "[SKIP] #$id '$name' -> no suitable image found\n";
    }
}

echo "\nDone. Updated: $updated, Skipped: $skipped, Total missing: $total\n";

$conn->close();
