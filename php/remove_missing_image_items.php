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

$targetName = isset($_GET['restaurant']) ? trim($_GET['restaurant']) : 'Taco Fiesta';
$targetId = getRestaurantIdByNameCI($conn, $targetName);

$response = [
	'ok' => false,
	'restaurant' => $targetName,
	'restaurant_id' => $targetId,
	'deleted' => 0,
	'candidate_count' => 0,
	'error' => null,
	'deleted_ids' => [],
];

if ($targetId === null) {
	$names = [];
	$rs = $conn->query("SELECT name FROM restaurants ORDER BY name ASC");
	if ($rs) { while ($r = $rs->fetch_assoc()) { $names[] = $r['name']; } }
	$response['error'] = 'Restaurant not found';
	$response['available_restaurants'] = $names;
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($response, JSON_PRETTY_PRINT);
	exit;
}

// Load items for the restaurant
$stmt = $conn->prepare("SELECT id, image FROM menu_items WHERE restaurant_id = ?");
$stmt->bind_param('i', $targetId);
$stmt->execute();
$res = $stmt->get_result();

$projectRoot = dirname(__DIR__);
$imagesDir = $projectRoot . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;

$toDelete = [];
while ($row = $res->fetch_assoc()) {
	$img = $row['image'];
	$hasName = isset($img) && trim($img) !== '';
	$exists = false;
	if ($hasName) {
		$exists = is_file($imagesDir . $img);
	}
	if (!$hasName || !$exists) {
		$toDelete[] = (int)$row['id'];
	}
}
$stmt->close();

$response['candidate_count'] = count($toDelete);

if (count($toDelete) > 0) {
	$placeholders = implode(',', array_fill(0, count($toDelete), '?'));
	$types = str_repeat('i', count($toDelete));
	$sql = "DELETE FROM menu_items WHERE id IN ($placeholders)";
	$del = $conn->prepare($sql);
	$del->bind_param($types, ...$toDelete);
	$ok = $del->execute();
	if (!$ok) {
		$response['error'] = $del->error;
	} else {
		$response['deleted'] = $del->affected_rows;
		$response['deleted_ids'] = $toDelete;
	}
	$del->close();
}

$response['ok'] = true;

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response, JSON_PRETTY_PRINT);

?>


