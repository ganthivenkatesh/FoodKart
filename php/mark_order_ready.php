<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'restaurant_owner') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$order_id = intval($data['order_id']);
$owner_id = $_SESSION['user_id'];

$conn = getDBConnection();

// Verify order belongs to this restaurant owner
$stmt = $conn->prepare("
    SELECT o.id 
    FROM orders o
    JOIN restaurants r ON o.restaurant_id = r.id
    WHERE o.id = ? AND r.owner_id = ?
");
$stmt->bind_param("ii", $order_id, $owner_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Order not found or unauthorized']);
    $stmt->close();
    $conn->close();
    exit();
}

// Update order status to ready_for_pickup
$stmt = $conn->prepare("UPDATE orders SET order_status = 'ready_for_pickup', updated_at = NOW() WHERE id = ?");
$stmt->bind_param("i", $order_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Order marked as ready for pickup']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update order status']);
}

$stmt->close();
$conn->close();
?>
