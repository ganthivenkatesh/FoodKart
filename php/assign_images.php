<?php
require_once __DIR__ . '/config.php';

$conn = getDBConnection();
$conn->set_charset('utf8mb4');

function getRestaurantIdByNameCI(mysqli $conn, string $name): ?int {
	$stmt = $conn->prepare("SELECT id FROM restaurants WHERE LOWER(name) = LOWER(?) LIMIT 1");
	$stmt->bind_param('s', $name);
	$stmt->execute();
	$res = $stmt->get_result();
	$row = $res->fetch_assoc();
	$stmt->close();
	return $row ? (int)$row['id'] : null;
}

function slugify(string $text): string {
	// Lowercase, remove non-letters/digits, replace spaces/underscores with single hyphen
	$text = strtolower($text);
	$text = preg_replace('/[^a-z0-9\s_\-]/', '', $text);
	$text = preg_replace('/[\s_\-]+/', '-', $text);
	return trim($text, '-');
}

$restaurantName = isset($_GET['restaurant']) ? trim($_GET['restaurant']) : 'Thai Orchid';
$commit = isset($_GET['commit']) && ($_GET['commit'] === '1' || strtolower($_GET['commit']) === 'true');

$restaurantId = getRestaurantIdByNameCI($conn, $restaurantName);

$response = [
	'ok' => false,
	'restaurant' => $restaurantName,
	'restaurant_id' => $restaurantId,
	'commit' => $commit,
	'mappings' => [],
	'already_set' => [],
	'unmatched_items' => [],
	'images_available' => [],
	'updated' => 0,
	'error' => null,
];

if ($restaurantId === null) {
	$names = [];
	$rs = $conn->query("SELECT name FROM restaurants ORDER BY name ASC");
	if ($rs) { while ($r = $rs->fetch_assoc()) { $names[] = $r['name']; } }
	$response['error'] = 'Restaurant not found';
	$response['available_restaurants'] = $names;
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($response, JSON_PRETTY_PRINT);
	exit;
}

// Fetch menu items for the restaurant
$itemsStmt = $conn->prepare("SELECT id, name, image FROM menu_items WHERE restaurant_id = ? ORDER BY name");
$itemsStmt->bind_param('i', $restaurantId);
$itemsStmt->execute();
$itemsRes = $itemsStmt->get_result();

$items = [];
while ($row = $itemsRes->fetch_assoc()) {
	$items[] = $row;
}
$itemsStmt->close();

// Scan images directory
$projectRoot = dirname(__DIR__);
$imagesDir = $projectRoot . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;

$files = [];
if (is_dir($imagesDir)) {
	$dh = opendir($imagesDir);
	if ($dh) {
		while (($file = readdir($dh)) !== false) {
			if ($file === '.' || $file === '..') continue;
			$lower = strtolower($file);
			if (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $lower)) {
				$files[] = $file;
			}
		}
		closedir($dh);
	}
}

// Index images by slug (without extension) and also keep full list
$imageIndex = [];
foreach ($files as $f) {
	$noExt = preg_replace('/\.[^.]+$/', '', $f);
	$slug = slugify($noExt);
	if (!isset($imageIndex[$slug])) $imageIndex[$slug] = [];
	$imageIndex[$slug][] = $f; // keep possible multiples with same slug
}
$response['images_available'] = $files;

// Propose mappings
$mappings = [];
$already = [];
$unmatched = [];

foreach ($items as $it) {
	$id = (int)$it['id'];
	$name = $it['name'];
	$current = trim($it['image'] ?? '');
	if ($current !== '') {
		$already[] = ['id' => $id, 'name' => $name, 'image' => $current];
		continue;
	}
	$slug = slugify($name);
	$chosen = null;
	if (isset($imageIndex[$slug])) {
		// Prefer the shortest filename if multiple
		$variants = $imageIndex[$slug];
		usort($variants, function($a, $b) { return strlen($a) <=> strlen($b); });
		$chosen = $variants[0];
	} else {
		// Fallback: find image that contains major words
		$words = array_filter(explode('-', $slug), function($w) { return strlen($w) > 2; });
		$best = null; $bestScore = 0;
		foreach ($files as $f) {
			$fs = slugify(preg_replace('/\.[^.]+$/', '', $f));
			$score = 0;
			foreach ($words as $w) { if (strpos($fs, $w) !== false) $score++; }
			if ($score > $bestScore) { $bestScore = $score; $best = $f; }
		}
		if ($bestScore >= 1) $chosen = $best;
	}

	if ($chosen) {
		$mappings[] = ['id' => $id, 'name' => $name, 'image' => $chosen];
	} else {
		$unmatched[] = ['id' => $id, 'name' => $name];
	}
}

$response['mappings'] = $mappings;
$response['already_set'] = $already;
$response['unmatched_items'] = $unmatched;

// Commit updates if requested
$updated = 0;
if ($commit && count($mappings) > 0) {
	$upd = $conn->prepare("UPDATE menu_items SET image = ? WHERE id = ?");
	foreach ($mappings as $m) {
		$img = $m['image'];
		$id = $m['id'];
		$upd->bind_param('si', $img, $id);
		if ($upd->execute()) { $updated++; }
	}
	$upd->close();
}

$response['updated'] = $updated;
$response['ok'] = true;

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response, JSON_PRETTY_PRINT);

?>


