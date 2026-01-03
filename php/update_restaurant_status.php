<?php
require_once 'config.php';
requireRole('admin');

header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['restaurant_id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$conn = getDBConnection();
$restaurant_id = intval($data['restaurant_id']);
$status = sanitize($data['status']);

$allowed_statuses = ['approved', 'rejected', 'pending'];
if (!in_array($status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

$update_query = "UPDATE restaurants SET status = ? WHERE id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("si", $status, $restaurant_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Restaurant status updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update status']);
}

$conn->close();
?>
