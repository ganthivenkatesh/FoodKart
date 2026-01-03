<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'delivery_agent') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$latitude = floatval($data['latitude']);
$longitude = floatval($data['longitude']);
$agent_id = $_SESSION['user_id'];

$conn = getDBConnection();

// Update agent's current location
$stmt = $conn->prepare("UPDATE delivery_agents SET current_latitude = ?, current_longitude = ?, updated_at = NOW() WHERE user_id = ?");
$stmt->bind_param("ddi", $latitude, $longitude, $agent_id);

if ($stmt->execute()) {
    // Get active order for this agent
    $stmt = $conn->prepare("SELECT id FROM orders WHERE delivery_agent_id = ? AND order_status IN ('assigned_to_agent', 'picked_up', 'out_for_delivery') LIMIT 1");
    $stmt->bind_param("i", $agent_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $order = $result->fetch_assoc();
        
        // Add location to tracking history
        $stmt = $conn->prepare("INSERT INTO order_tracking (order_id, delivery_agent_id, latitude, longitude, status, notes) VALUES (?, ?, ?, ?, 'location_update', 'Location updated')");
        $stmt->bind_param("iidd", $order['id'], $agent_id, $latitude, $longitude);
        $stmt->execute();
    }
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update location']);
}

$stmt->close();
$conn->close();
?>
