<?php
require_once __DIR__ . '/config.php';

$conn = getDBConnection();

function slugify($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/i', '-', $text);
    $text = trim($text, '-');
    return $text;
}

function download_image($url, $destDir, $baseNameSlug) {
    $ctx = stream_context_create([
        'http' => [ 'timeout' => 15, 'follow_location' => 1 ],
        'https' => [ 'timeout' => 15, 'follow_location' => 1 ],
    ]);
    $data = @file_get_contents($url, false, $ctx);
    if ($data === false || strlen($data) < 128) return [false, null];
    // Detect extension from URL or content-type
    $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp'])) {
        // Try to detect from binary
        $signature = bin2hex(substr($data, 0, 12));
        if (str_starts_with($signature, 'ffd8')) $ext = 'jpg';
        elseif (str_starts_with($signature, '89504e47')) $ext = 'png';
        elseif (strpos($signature, '57454250') !== false) $ext = 'webp';
        else $ext = 'jpg';
    }
    $filename = uniqid('fk_', true) . '-' . $baseNameSlug . '.' . $ext;
    $dest = rtrim($destDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
    if (@file_put_contents($dest, $data) !== false) {
        return [true, $filename];
    }
    return [false, null];
}

$assetsDir = realpath(__DIR__ . '/../assets/images');
if ($assetsDir === false) $assetsDir = __DIR__ . '/../assets/images';

$updated = 0; $skipped = 0; $errors = 0; $log = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv']) && $_FILES['csv']['error'] === UPLOAD_ERR_OK) {
    $tmp = $_FILES['csv']['tmp_name'];
    $f = fopen($tmp, 'r');
    if ($f) {
        // Expect header: dish,restaurant,url
        $header = fgetcsv($f);
        $map = ['dish' => 0, 'restaurant' => null, 'url' => null];
        if ($header) {
            foreach ($header as $i => $col) {
                $col = strtolower(trim($col));
                if ($col === 'dish' || $col === 'item' || $col === 'name') $map['dish'] = $i;
                if ($col === 'restaurant' || $col === 'restaurant_name') $map['restaurant'] = $i;
                if ($col === 'url' || $col === 'image' || $col === 'image_url') $map['url'] = $i;
            }
        }
        if ($map['url'] === null) {
            $log[] = 'CSV must contain a URL column (url/image/image_url).';
        } else {
            while (($row = fgetcsv($f)) !== false) {
                $dish = trim($row[$map['dish']] ?? '');
                $rest = $map['restaurant'] !== null ? trim($row[$map['restaurant']] ?? '') : '';
                $url = trim($row[$map['url']] ?? '');
                if ($dish === '' || $url === '') { $skipped++; continue; }
                if (!(stripos($url, 'http://') === 0 || stripos($url, 'https://') === 0)) { $skipped++; continue; }

                // Find target item (prefer match with restaurant if provided)
                if ($rest !== '') {
                    $stmt = $conn->prepare("SELECT id FROM menu_items m JOIN restaurants r ON m.restaurant_id = r.id WHERE m.name = ? AND r.name = ? LIMIT 1");
                    $stmt->bind_param('ss', $dish, $rest);
                } else {
                    $stmt = $conn->prepare("SELECT id FROM menu_items WHERE name = ? LIMIT 1");
                    $stmt->bind_param('s', $dish);
                }
                $stmt->execute();
                $res = $stmt->get_result();
                $stmt->close();
                if ($res->num_rows === 0) { $skipped++; $log[] = "Not found: $dish"; continue; }
                $item = $res->fetch_assoc();
                $item_id = (int)$item['id'];

                // Download and save
                [$ok, $filename] = download_image($url, $assetsDir, slugify($dish));
                if (!$ok) { $errors++; $log[] = "Download failed: $dish -> $url"; continue; }

                // Update DB
                $stmt = $conn->prepare("UPDATE menu_items SET image = ? WHERE id = ?");
                $stmt->bind_param('si', $filename, $item_id);
                if ($stmt->execute()) { $updated++; $log[] = "OK: $dish -> $filename"; }
                else { $errors++; $log[] = "DB update failed: $dish"; }
                $stmt->close();
            }
        }
        fclose($f);
    } else {
        $log[] = 'Unable to read uploaded file.';
    }
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Import Menu Images</title>
<link rel="stylesheet" href="../styles/main.css">
</head>
<body>
<div class="container" style="max-width: 800px; padding: 2rem 0;">
    <h1>Bulk Import Menu Images</h1>
    <p>Upload a CSV with columns: <strong>dish</strong>, <em>restaurant</em> (optional), <strong>url</strong>.</p>
    <form method="POST" enctype="multipart/form-data" style="margin: 1rem 0;">
        <input type="file" name="csv" accept=".csv" required>
        <button class="btn btn-primary" type="submit">Import</button>
    </form>
    <div class="card" style="padding: 1rem;">
        <h3>Result</h3>
        <p>Updated: <?= $updated ?> | Skipped: <?= $skipped ?> | Errors: <?= $errors ?></p>
        <pre style="white-space: pre-wrap;"><?= htmlspecialchars(implode("\n", $log)) ?></pre>
    </div>
</div>
</body>
</html>
