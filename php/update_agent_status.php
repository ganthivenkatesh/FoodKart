<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SESSION['role'] !== 'delivery_agent') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$is_online = $data['is_online'] ? 1 : 0;
$agent_id = $_SESSION['user_id'];

$conn = getDBConnection();

// Check if delivery_agents table exists
$table_check = $conn->query("SHOW TABLES LIKE 'delivery_agents'");

if ($table_check->num_rows > 0) {
    // Update online status in delivery_agents table
    $check_agent = $conn->query("SELECT id FROM delivery_agents WHERE user_id = $agent_id");
    
    if ($check_agent->num_rows > 0) {
        // Agent exists, update status
        $stmt = $conn->prepare("UPDATE delivery_agents SET is_online = ? WHERE user_id = ?");
        $stmt->bind_param("ii", $is_online, $agent_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Status updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update status']);
        }
        $stmt->close();
    } else {
        // Agent doesn't exist in delivery_agents table, create entry
        $stmt = $conn->prepare("INSERT INTO delivery_agents (user_id, is_online, total_deliveries, rating) VALUES (?, ?, 0, 0)");
        $stmt->bind_param("ii", $agent_id, $is_online);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Status created and updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create agent entry']);
        }
        $stmt->close();
    }
} else {
    // Table doesn't exist, just return success (status stored in session)
    $_SESSION['agent_online_status'] = $is_online;
    echo json_encode(['success' => true, 'message' => 'Status updated in session']);
}

$conn->close();
?>
