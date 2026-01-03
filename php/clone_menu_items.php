<?php
require_once __DIR__ . '/config.php';

// Optional: restrict to admin
// requireRole('admin');

function getRestaurantIdByName(mysqli $conn, string $name): ?int {
	// Case-insensitive exact match
	$stmt = $conn->prepare("SELECT id FROM restaurants WHERE LOWER(name) = LOWER(?) LIMIT 1");
	$stmt->bind_param('s', $name);
	$stmt->execute();
	$result = $stmt->get_result();
	$row = $result->fetch_assoc();
	$stmt->close();
	return $row ? (int)$row['id'] : null;
}

function copyMenuItems(mysqli $conn, int $fromRestaurantId, int $toRestaurantId): array {
	// Insert items from source into target, skipping items with the same name already present in target
	$sql = "INSERT INTO menu_items (restaurant_id, name, category, price, discount, description, image, is_available)
			SELECT ?, mi.name, mi.category, mi.price, mi.discount, mi.description, mi.image, mi.is_available
			FROM menu_items mi
			LEFT JOIN menu_items tmi ON tmi.restaurant_id = ? AND tmi.name = mi.name
			WHERE mi.restaurant_id = ? AND tmi.id IS NULL";

	$stmt = $conn->prepare($sql);
	$stmt->bind_param('iii', $toRestaurantId, $toRestaurantId, $fromRestaurantId);
	$ok = $stmt->execute();
	$affected = $stmt->affected_rows;
	$error = $stmt->error;
	$stmt->close();

	return [
		'success' => $ok,
		'inserted' => $affected >= 0 ? $affected : 0,
		'error' => $ok ? null : $error,
	];
}

$conn = getDBConnection();
$conn->set_charset('utf8mb4');

// Allow overriding via query params for quick fixes
$from1 = isset($_GET['from1']) ? trim($_GET['from1']) : 'CAfe restaurant';
$to1 = isset($_GET['to1']) ? trim($_GET['to1']) : 'Sushi Bay';
$from2 = isset($_GET['from2']) ? trim($_GET['from2']) : 'Sushi Bay';
$to2 = isset($_GET['to2']) ? trim($_GET['to2']) : 'Taco Fiesta';

// Define requested copies
$pairs = [
	['from' => $from1, 'to' => $to1],
	['from' => $from2, 'to' => $to2],
];

$results = [];

foreach ($pairs as $pair) {
	$fromName = $pair['from'];
	$toName = $pair['to'];

	$fromId = getRestaurantIdByName($conn, $fromName);
	$toId = getRestaurantIdByName($conn, $toName);

	if ($fromId === null || $toId === null) {
		// Build helper: list available restaurant names to guide the user
		$names = [];
		$rs = $conn->query("SELECT name FROM restaurants ORDER BY name ASC");
		if ($rs) {
			while ($r = $rs->fetch_assoc()) { $names[] = $r['name']; }
		}
		$results[] = [
			'from' => $fromName,
			'to' => $toName,
			'success' => false,
			'error' => 'Restaurant not found: ' . ($fromId === null ? $fromName : $toName),
			'available_restaurants' => $names,
			'inserted' => 0,
		];
		continue;
	}

	$copyResult = copyMenuItems($conn, $fromId, $toId);
	$results[] = [
		'from' => $fromName,
		'to' => $toName,
		'success' => $copyResult['success'],
		'error' => $copyResult['error'],
		'inserted' => $copyResult['inserted'],
	];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
	'ok' => true,
	'results' => $results,
], JSON_PRETTY_PRINT);

?>


