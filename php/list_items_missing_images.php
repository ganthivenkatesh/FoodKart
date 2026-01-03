<?php
require_once __DIR__ . '/config.php';

$conn = getDBConnection();
$conn->set_charset('utf8mb4');

// Fetch all items where image is NULL/empty or the file is missing on disk
$sql = "SELECT id, restaurant_id, name, image FROM menu_items";
$result = $conn->query($sql);

$projectRoot = dirname(__DIR__); // one level up from /php
$imagesDir = $projectRoot . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;

$missing = [];

if ($result) {
	while ($row = $result->fetch_assoc()) {
		$image = $row['image'];
		$hasName = isset($image) && trim($image) !== '';
		$fileExists = false;
		if ($hasName) {
			$filePath = $imagesDir . $image;
			$fileExists = is_file($filePath);
		}

		if (!$hasName || !$fileExists) {
			$missing[] = [
				'id' => (int)$row['id'],
				'restaurant_id' => (int)$row['restaurant_id'],
				'name' => $row['name'],
				'image' => $image,
				'reason' => !$hasName ? 'no_image_name' : 'file_not_found',
			];
		}
	}
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
	'ok' => true,
	'count' => count($missing),
	'items' => $missing,
], JSON_PRETTY_PRINT);

?>


