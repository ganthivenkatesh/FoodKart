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

// Allow override via ?target=...
$targetName = isset($_GET['target']) ? trim($_GET['target']) : 'Kebab Corner';
$targetId = getRestaurantIdByNameCI($conn, $targetName);

$response = [
	'ok' => false,
	'target' => $targetName,
	'inserted' => 0,
	'matched' => 0,
	'error' => null,
	'inserted_names' => [],
];

if ($targetId === null) {
	// Provide available names to help
	$names = [];
	$rs = $conn->query("SELECT name FROM restaurants ORDER BY name ASC");
	if ($rs) {
		while ($r = $rs->fetch_assoc()) { $names[] = $r['name']; }
	}
	$response['error'] = 'Target restaurant not found';
	$response['available_restaurants'] = $names;
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($response, JSON_PRETTY_PRINT);
	exit;
}

// Collect matching items across all restaurants
$matchSql = "SELECT id, name, category, price, discount, description, image, is_available
			  FROM menu_items
			  WHERE LOWER(name) LIKE '%kebab%' OR LOWER(name) LIKE '%kabab%'";
$matches = $conn->query($matchSql);

$response['ok'] = true;

if (!$matches) {
	$response['error'] = $conn->error;
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($response, JSON_PRETTY_PRINT);
	exit;
}

$toInsert = [];
while ($row = $matches->fetch_assoc()) {
	$toInsert[] = $row;
}
$response['matched'] = count($toInsert);

if (count($toInsert) === 0) {
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode($response, JSON_PRETTY_PRINT);
	exit;
}

// Insert all, skipping existing names in target
$sql = "INSERT INTO menu_items (restaurant_id, name, category, price, discount, description, image, is_available)
		SELECT ?, s.name, s.category, s.price, s.discount, s.description, s.image, s.is_available
		FROM (SELECT ? AS rid) x
		JOIN (SELECT ? AS rid2) y ON 1=1
		JOIN menu_items s ON 1=1
		LEFT JOIN menu_items t ON t.restaurant_id = ? AND t.name = s.name
		WHERE (LOWER(s.name) LIKE '%kebab%' OR LOWER(s.name) LIKE '%kabab%')
		  AND t.id IS NULL";

$stmt = $conn->prepare($sql);
$stmt->bind_param('iiii', $targetId, $targetId, $targetId, $targetId);
$ok = $stmt->execute();
$affected = $stmt->affected_rows;
$err = $stmt->error;
$stmt->close();

$response['inserted'] = $affected >= 0 ? $affected : 0;
if (!$ok) {
	$response['error'] = $err;
}

// Also return names actually in target now (optional small list)
$namesRes = $conn->prepare("SELECT name FROM menu_items WHERE restaurant_id = ? AND (LOWER(name) LIKE '%kebab%' OR LOWER(name) LIKE '%kabab%') ORDER BY name");
$namesRes->bind_param('i', $targetId);
$namesRes->execute();
$list = $namesRes->get_result();
while ($r = $list->fetch_assoc()) { $response['inserted_names'][] = $r['name']; }
$namesRes->close();

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response, JSON_PRETTY_PRINT);

?>


