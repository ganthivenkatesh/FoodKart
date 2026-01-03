<?php
require_once 'config.php';
requireRole('restaurant_owner');

header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['order_id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];
$order_id = intval($data['order_id']);
$status = sanitize($data['status']);

// Verify order belongs to restaurant owner
$verify_query = "SELECT o.id FROM orders o 
                 JOIN restaurants r ON o.restaurant_id = r.id 
                 WHERE o.id = ? AND r.owner_id = ?";
$stmt = $conn->prepare($verify_query);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Update order status
$allowed_statuses = ['preparing', 'out_for_delivery', 'delivered'];
if (!in_array($status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

$update_query = "UPDATE orders SET order_status = ? WHERE id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("si", $status, $order_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update status']);
}

$conn->close();
?>
