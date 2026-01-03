<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'delivery_agent') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$order_id = intval($data['order_id']);
$agent_id = $_SESSION['user_id'];

$conn = getDBConnection();

// Check if order is still available
$stmt = $conn->prepare("SELECT id, order_status FROM orders WHERE id = ? AND delivery_agent_id IS NULL AND order_status = 'ready_for_pickup'");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Order is no longer available']);
    $stmt->close();
    $conn->close();
    exit();
}

// Assign order to delivery agent
$stmt = $conn->prepare("UPDATE orders SET delivery_agent_id = ?, order_status = 'assigned_to_agent', assigned_at = NOW() WHERE id = ?");
$stmt->bind_param("ii", $agent_id, $order_id);

if ($stmt->execute()) {
    // Add tracking entry
    $stmt = $conn->prepare("INSERT INTO order_tracking (order_id, delivery_agent_id, latitude, longitude, status, notes) VALUES (?, ?, 0, 0, 'assigned', 'Order assigned to delivery agent')");
    $stmt->bind_param("ii", $order_id, $agent_id);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Order accepted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to assign order']);
}

$stmt->close();
$conn->close();
?>
