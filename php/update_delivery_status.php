<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'delivery_agent') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$order_id = intval($data['order_id']);
$status = sanitize($data['status']);
$agent_id = $_SESSION['user_id'];

$conn = getDBConnection();

// Verify order belongs to this agent
$stmt = $conn->prepare("SELECT id, total_price FROM orders WHERE id = ? AND delivery_agent_id = ?");
$stmt->bind_param("ii", $order_id, $agent_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Order not found or not assigned to you']);
    $stmt->close();
    $conn->close();
    exit();
}

$order = $result->fetch_assoc();

// Update order status
$update_query = "UPDATE orders SET order_status = ?";
$timestamp_field = null;

switch ($status) {
    case 'picked_up':
        $update_query .= ", picked_up_at = NOW()";
        $timestamp_field = 'picked_up';
        break;
    case 'out_for_delivery':
        $update_query .= ", updated_at = NOW()";
        $timestamp_field = 'out_for_delivery';
        break;
    case 'delivered':
        $update_query .= ", delivered_at = NOW()";
        $timestamp_field = 'delivered';
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        $conn->close();
        exit();
}

$update_query .= " WHERE id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("si", $status, $order_id);

if ($stmt->execute()) {
    // Add tracking entry
    $notes = ucwords(str_replace('_', ' ', $status));
    $stmt = $conn->prepare("INSERT INTO order_tracking (order_id, delivery_agent_id, latitude, longitude, status, notes) VALUES (?, ?, 0, 0, ?, ?)");
    $stmt->bind_param("iiss", $order_id, $agent_id, $status, $notes);
    $stmt->execute();
    
    // If delivered, calculate and add earnings
    if ($status === 'delivered') {
        $base_amount = $order['total_price'] * 0.10; // 10% of order value
        $bonus_amount = 0;
        $total_amount = $base_amount + $bonus_amount;
        
        $stmt = $conn->prepare("INSERT INTO delivery_earnings (delivery_agent_id, order_id, base_amount, bonus_amount, total_amount) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiddd", $agent_id, $order_id, $base_amount, $bonus_amount, $total_amount);
        $stmt->execute();
        
        // Update total deliveries count
        $stmt = $conn->prepare("UPDATE delivery_agents SET total_deliveries = total_deliveries + 1 WHERE user_id = ?");
        $stmt->bind_param("i", $agent_id);
        $stmt->execute();
    }
    
    echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update status']);
}

$stmt->close();
$conn->close();
?>
