<?php
require_once 'config.php';
requireRole('customer');

header('Content-Type: application/json');

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit();
}

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

try {
    // Start transaction
    $conn->begin_transaction();
    
    $items = $data['items'];
    $delivery_address = sanitize($data['delivery_address']);
    $payment_method = sanitize($data['payment_method']);
    $total = floatval($data['total']);
    
    if (empty($items) || empty($delivery_address)) {
        throw new Exception('Missing required fields');
    }
    
    // Get restaurant_id from first item
    $first_item_id = $items[0]['id'];
    $rest_query = "SELECT restaurant_id FROM menu_items WHERE id = ?";
    $stmt = $conn->prepare($rest_query);
    $stmt->bind_param("i", $first_item_id);
    $stmt->execute();
    $rest_result = $stmt->get_result();
    $rest_data = $rest_result->fetch_assoc();
    $restaurant_id = $rest_data['restaurant_id'];
    
    // Insert order
    $order_query = "INSERT INTO orders (user_id, restaurant_id, total_price, payment_status, order_status, delivery_address) 
                    VALUES (?, ?, ?, 'pending', 'placed', ?)";
    $stmt = $conn->prepare($order_query);
    $stmt->bind_param("iids", $user_id, $restaurant_id, $total, $delivery_address);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to create order');
    }
    
    $order_id = $conn->insert_id;
    
    // Insert order items
    $item_query = "INSERT INTO order_items (order_id, item_id, quantity, price) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($item_query);
    
    foreach ($items as $item) {
        $item_id = intval($item['id']);
        $quantity = intval($item['quantity']);
        $price = floatval($item['price']);
        
        $stmt->bind_param("iiid", $order_id, $item_id, $quantity, $price);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to add order items');
        }
    }
    
    // Insert payment record
    $payment_status = ($payment_method === 'cod') ? 'pending' : 'success';
    $transaction_id = 'TXN' . time() . rand(1000, 9999);
    
    $payment_query = "INSERT INTO payments (order_id, user_id, method, amount, transaction_id, status) 
                      VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($payment_query);
    $stmt->bind_param("iisdss", $order_id, $user_id, $payment_method, $total, $transaction_id, $payment_status);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to process payment');
    }
    
    // Update order payment status
    if ($payment_method !== 'cod') {
        $update_query = "UPDATE orders SET payment_status = 'completed' WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'transaction_id' => $transaction_id,
        'message' => 'Order placed successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
