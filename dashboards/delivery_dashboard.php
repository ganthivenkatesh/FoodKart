<?php
require_once '../php/config.php';

// Check if user is logged in and is a delivery agent
if (!isLoggedIn() || $_SESSION['role'] !== 'delivery_agent') {
    header('Location: ' . BASE_URL . 'login_signup.php');
    exit();
}

$conn = getDBConnection();
$agent_id = $_SESSION['user_id'];

// Get delivery agent details
// Check if delivery_agents table exists
$table_check = $conn->query("SHOW TABLES LIKE 'delivery_agents'");
if ($table_check->num_rows > 0) {
    $stmt = $conn->prepare("
        SELECT u.*, da.license_number, da.total_deliveries, da.rating, da.is_online, da.current_latitude, da.current_longitude
        FROM users u
        LEFT JOIN delivery_agents da ON u.id = da.user_id
        WHERE u.id = ?
    ");
    $stmt->bind_param("i", $agent_id);
    $stmt->execute();
    $agent = $stmt->get_result()->fetch_assoc();
} else {
    // Fallback if delivery_agents table doesn't exist
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $agent_id);
    $stmt->execute();
    $agent = $stmt->get_result()->fetch_assoc();
    // Set default values
    $agent['license_number'] = 'N/A';
    $agent['total_deliveries'] = 0;
    $agent['rating'] = 0;
    $agent['is_online'] = 1;
    $agent['current_latitude'] = 0;
    $agent['current_longitude'] = 0;
}

// Get assigned orders with items
// Check if delivery_agent_id column exists
$column_check = $conn->query("SHOW COLUMNS FROM orders LIKE 'delivery_agent_id'");
if ($column_check->num_rows > 0) {
    $stmt = $conn->prepare("
        SELECT o.*, r.name as restaurant_name, r.location as restaurant_location, r.phone as restaurant_phone,
               u.name as customer_name, u.phone as customer_phone, u.address as customer_address
        FROM orders o
        JOIN restaurants r ON o.restaurant_id = r.id
        JOIN users u ON o.user_id = u.id
        WHERE o.delivery_agent_id = ? AND o.order_status IN ('assigned_to_agent', 'picked_up', 'out_for_delivery')
        ORDER BY o.created_at DESC
    ");
    $stmt->bind_param("i", $agent_id);
    $stmt->execute();
    $assigned_orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $assigned_orders = [];
}

// Get order items for assigned orders
foreach ($assigned_orders as &$order) {
    $stmt = $conn->prepare("
        SELECT oi.*, mi.name as item_name, mi.category
        FROM order_items oi
        JOIN menu_items mi ON oi.item_id = mi.id
        WHERE oi.order_id = ?
    ");
    $stmt->bind_param("i", $order['id']);
    $stmt->execute();
    $order['items'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
unset($order);

// Get available orders (ready for pickup)
if ($column_check->num_rows > 0) {
    $available_orders_result = $conn->query("
        SELECT o.*, r.name as restaurant_name, r.location as restaurant_location, r.phone as restaurant_phone,
               u.name as customer_name, u.phone as customer_phone, u.address as customer_address
        FROM orders o
        JOIN restaurants r ON o.restaurant_id = r.id
        JOIN users u ON o.user_id = u.id
        WHERE o.delivery_agent_id IS NULL AND o.order_status = 'ready_for_pickup'
        ORDER BY o.created_at ASC
        LIMIT 10
    ");
    $available_orders = $available_orders_result->fetch_all(MYSQLI_ASSOC);
} else {
    $available_orders = [];
}

// Get order items for available orders
foreach ($available_orders as &$order) {
    $stmt = $conn->prepare("
        SELECT oi.*, mi.name as item_name, mi.category
        FROM order_items oi
        JOIN menu_items mi ON oi.item_id = mi.id
        WHERE oi.order_id = ?
    ");
    $stmt->bind_param("i", $order['id']);
    $stmt->execute();
    $order['items'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
unset($order);

// Get delivery history (delivered orders)
if ($column_check->num_rows > 0) {
    $earnings_table_check = $conn->query("SHOW TABLES LIKE 'delivery_earnings'");
    if ($earnings_table_check->num_rows > 0) {
        $stmt = $conn->prepare("
            SELECT o.*, r.name as restaurant_name, r.location as restaurant_location,
                   u.name as customer_name, de.total_amount as earnings
            FROM orders o
            JOIN restaurants r ON o.restaurant_id = r.id
            JOIN users u ON o.user_id = u.id
            LEFT JOIN delivery_earnings de ON o.id = de.order_id
            WHERE o.delivery_agent_id = ? AND o.order_status = 'delivered'
            ORDER BY o.delivered_at DESC
            LIMIT 20
        ");
    } else {
        $stmt = $conn->prepare("
            SELECT o.*, r.name as restaurant_name, r.location as restaurant_location,
                   u.name as customer_name, 0 as earnings
            FROM orders o
            JOIN restaurants r ON o.restaurant_id = r.id
            JOIN users u ON o.user_id = u.id
            WHERE o.delivery_agent_id = ? AND o.order_status = 'delivered'
            ORDER BY o.created_at DESC
            LIMIT 20
        ");
    }
    $stmt->bind_param("i", $agent_id);
    $stmt->execute();
    $delivery_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $delivery_history = [];
}

// Get order items for delivery history
foreach ($delivery_history as &$order) {
    $stmt = $conn->prepare("
        SELECT oi.*, mi.name as item_name, mi.category
        FROM order_items oi
        JOIN menu_items mi ON oi.item_id = mi.id
        WHERE oi.order_id = ?
    ");
    $stmt->bind_param("i", $order['id']);
    $stmt->execute();
    $order['items'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
unset($order);

// Get statistics
$total_assigned = count($assigned_orders);
$total_delivered = count($delivery_history);
$total_available = count($available_orders);

// Calculate today's earnings
$today_earnings = 0;
$total_earnings = 0;

if (isset($earnings_table_check) && $earnings_table_check->num_rows > 0) {
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(total_amount), 0) as today_earnings
        FROM delivery_earnings
        WHERE delivery_agent_id = ? AND DATE(created_at) = CURDATE()
    ");
    $stmt->bind_param("i", $agent_id);
    $stmt->execute();
    $today_earnings = $stmt->get_result()->fetch_assoc()['today_earnings'];

    // Calculate total earnings
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(total_amount), 0) as total_earnings
        FROM delivery_earnings
        WHERE delivery_agent_id = ?
    ");
    $stmt->bind_param("i", $agent_id);
    $stmt->execute();
    $total_earnings = $stmt->get_result()->fetch_assoc()['total_earnings'];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Agent Dashboard - FoodKart</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>styles/main.css">
    <style>
        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #eee;
        }

        .agent-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .online-toggle {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .toggle-switch {
            position: relative;
            width: 60px;
            height: 30px;
            background-color: #ccc;
            border-radius: 15px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .toggle-switch.active {
            background-color: #4CAF50;
        }

        .toggle-slider {
            position: absolute;
            top: 3px;
            left: 3px;
            width: 24px;
            height: 24px;
            background-color: white;
            border-radius: 50%;
            transition: transform 0.3s;
        }

        .toggle-switch.active .toggle-slider {
            transform: translateX(30px);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }

        .stat-card h3 {
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            opacity: 0.9;
        }

        .stat-card .value {
            font-size: 2rem;
            font-weight: bold;
        }
        
        .stat-card small {
            display: block;
            margin-top: 0.5rem;
            opacity: 0.8;
        }

        .orders-section {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .order-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: box-shadow 0.3s;
        }

        .order-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .order-id {
            font-weight: bold;
            color: var(--primary-color);
        }

        .order-status {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-assigned { background: #fff3cd; color: #856404; }
        .status-picked { background: #cfe2ff; color: #084298; }
        .status-delivery { background: #d1e7dd; color: #0f5132; }
        .status-delivered { background: #d1ecf1; color: #0c5460; }
        .status-ready { background: #f8d7da; color: #842029; }

        .order-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 0.25rem;
        }

        .detail-value {
            font-weight: 500;
        }

        .order-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        .btn-accept {
            background-color: #28a745;
            color: white;
        }

        .btn-pickup {
            background-color: #007bff;
            color: white;
        }

        .btn-deliver {
            background-color: #17a2b8;
            color: white;
        }

        .btn-complete {
            background-color: #28a745;
            color: white;
        }

        .btn-navigate {
            background-color: #6c757d;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #999;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #eee;
        }

        .tab {
            padding: 0.75rem 1.5rem;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            font-weight: 500;
        }

        .tab.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .order-items-section {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 1rem;
            margin: 1rem 0;
        }

        .order-items-header {
            font-weight: 600;
            margin-bottom: 0.75rem;
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .item-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem;
            background: white;
            border-radius: 4px;
            border-left: 3px solid var(--primary-color);
        }

        .item-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex: 1;
        }

        .item-category-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .item-category-veg {
            background: #d4edda;
            color: #155724;
        }

        .item-category-non-veg {
            background: #f8d7da;
            color: #721c24;
        }

        .item-category-combo {
            background: #fff3cd;
            color: #856404;
        }

        .item-name {
            font-weight: 500;
            color: #333;
        }

        .item-quantity {
            color: #666;
            font-size: 0.9rem;
        }

        .item-price {
            font-weight: 600;
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <div class="agent-info">
                <h1>🏍️ Delivery Dashboard</h1>
                <span style="color: #666;">Welcome, <?php echo htmlspecialchars($agent['name']); ?>!</span>
            </div>
            <div class="online-toggle">
                <span>Status:</span>
                <div class="toggle-switch <?php echo $agent['is_online'] ? 'active' : ''; ?>" id="onlineToggle" onclick="toggleOnlineStatus()">
                    <div class="toggle-slider"></div>
                </div>
                <span id="statusText"><?php echo $agent['is_online'] ? 'Online' : 'Offline'; ?></span>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card" onclick="switchTab('assigned')" style="cursor: pointer;">
                <h3>📦 Active Deliveries</h3>
                <div class="value"><?php echo $total_assigned; ?></div>
                <small style="color: #666;">Orders in progress</small>
            </div>
            <div class="stat-card" onclick="switchTab('available')" style="cursor: pointer;">
                <h3>🔔 Available Orders</h3>
                <div class="value"><?php echo $total_available; ?></div>
                <small style="color: #666;">Ready to accept</small>
            </div>
            <div class="stat-card" onclick="switchTab('history')" style="cursor: pointer;">
                <h3>✅ Delivered</h3>
                <div class="value"><?php echo $total_delivered; ?></div>
                <small style="color: #666;">Total completed</small>
            </div>
            <div class="stat-card">
                <h3>💰 Today's Earnings</h3>
                <div class="value">₹<?php echo number_format($today_earnings, 2); ?></div>
                <small style="color: #666;">Total: ₹<?php echo number_format($total_earnings, 2); ?></small>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <div class="tab active" onclick="switchTab('assigned')">✅ Accepted Orders (<?php echo count($assigned_orders); ?>)</div>
            <div class="tab" onclick="switchTab('available')">⏳ Pending Orders (<?php echo count($available_orders); ?>)</div>
            <div class="tab" onclick="switchTab('history')">📦 Delivered Orders</div>
        </div>

        <!-- Assigned Orders Tab -->
        <div id="assigned-tab" class="tab-content active">
            <div class="orders-section">
                <div class="section-header">
                    <h2>✅ Accepted Orders - Active Deliveries</h2>
                </div>
                <?php if (empty($assigned_orders)): ?>
                    <div class="empty-state">
                        <div style="font-size: 3rem;">📦</div>
                        <p>No active deliveries</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($assigned_orders as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div>
                                    <div class="order-id">Order #<?php echo $order['id']; ?></div>
                                    <div style="color: #666; font-size: 0.9rem;">
                                        <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?>
                                    </div>
                                </div>
                                <span class="order-status status-<?php echo str_replace('_', '-', $order['order_status']); ?>">
                                    <?php echo ucwords(str_replace('_', ' ', $order['order_status'])); ?>
                                </span>
                            </div>

                            <div class="order-details">
                                <div class="detail-item">
                                    <span class="detail-label">🏪 Restaurant</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($order['restaurant_name']); ?></span>
                                    <span style="font-size: 0.85rem; color: #666;"><?php echo htmlspecialchars($order['restaurant_location']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">👤 Customer</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($order['customer_name']); ?></span>
                                    <span style="font-size: 0.85rem; color: #666;"><?php echo htmlspecialchars($order['customer_phone']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">📍 Delivery Address</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($order['delivery_address']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">💵 Order Amount</span>
                                    <span class="detail-value">₹<?php echo number_format($order['total_price'], 2); ?></span>
                                </div>
                            </div>

                            <!-- Order Items Section -->
                            <?php if (!empty($order['items'])): ?>
                            <div class="order-items-section">
                                <div class="order-items-header">
                                    🍽️ Order Items (<?php echo count($order['items']); ?> items)
                                </div>
                                <div class="item-list">
                                    <?php foreach ($order['items'] as $item): ?>
                                        <div class="item-row">
                                            <div class="item-info">
                                                <span class="item-category-badge item-category-<?php echo $item['category']; ?>">
                                                    <?php echo strtoupper($item['category']); ?>
                                                </span>
                                                <span class="item-name"><?php echo htmlspecialchars($item['item_name']); ?></span>
                                                <span class="item-quantity">x<?php echo $item['quantity']; ?></span>
                                            </div>
                                            <span class="item-price">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="order-actions">
                                <?php if ($order['order_status'] === 'assigned_to_agent'): ?>
                                    <button class="btn btn-pickup btn-small" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'picked_up')">
                                        Mark as Picked Up
                                    </button>
                                <?php elseif ($order['order_status'] === 'picked_up'): ?>
                                    <button class="btn btn-deliver btn-small" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'out_for_delivery')">
                                        Start Delivery
                                    </button>
                                <?php elseif ($order['order_status'] === 'out_for_delivery'): ?>
                                    <button class="btn btn-complete btn-small" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'delivered')">
                                        Mark as Delivered
                                    </button>
                                <?php endif; ?>
                                <button class="btn btn-navigate btn-small" onclick="navigateToAddress('<?php echo urlencode($order['delivery_address']); ?>')">
                                    Navigate
                                </button>
                                <button class="btn btn-secondary btn-small" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                                    View Details
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Available Orders Tab -->
        <div id="available-tab" class="tab-content">
            <div class="orders-section">
                <div class="section-header">
                    <h2>⏳ Pending Orders - Ready for Pickup</h2>
                </div>
                <?php if (empty($available_orders)): ?>
                    <div class="empty-state">
                        <div style="font-size: 3rem;">✅</div>
                        <p>No orders available at the moment</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($available_orders as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div>
                                    <div class="order-id">Order #<?php echo $order['id']; ?></div>
                                    <div style="color: #666; font-size: 0.9rem;">
                                        <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?>
                                    </div>
                                </div>
                                <span class="order-status status-ready">Ready for Pickup</span>
                            </div>

                            <div class="order-details">
                                <div class="detail-item">
                                    <span class="detail-label">🏪 Restaurant</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($order['restaurant_name']); ?></span>
                                    <span style="font-size: 0.85rem; color: #666;"><?php echo htmlspecialchars($order['restaurant_location']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">📍 Delivery Address</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($order['delivery_address']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">💵 Order Amount</span>
                                    <span class="detail-value">₹<?php echo number_format($order['total_price'], 2); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">💰 Estimated Earnings</span>
                                    <span class="detail-value" style="color: #28a745;">₹<?php echo number_format($order['total_price'] * 0.1, 2); ?></span>
                                </div>
                            </div>

                            <!-- Order Items Section -->
                            <?php if (!empty($order['items'])): ?>
                            <div class="order-items-section">
                                <div class="order-items-header">
                                    🍽️ Order Items (<?php echo count($order['items']); ?> items)
                                </div>
                                <div class="item-list">
                                    <?php foreach ($order['items'] as $item): ?>
                                        <div class="item-row">
                                            <div class="item-info">
                                                <span class="item-category-badge item-category-<?php echo $item['category']; ?>">
                                                    <?php echo strtoupper($item['category']); ?>
                                                </span>
                                                <span class="item-name"><?php echo htmlspecialchars($item['item_name']); ?></span>
                                                <span class="item-quantity">x<?php echo $item['quantity']; ?></span>
                                            </div>
                                            <span class="item-price">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="order-actions">
                                <button class="btn btn-accept btn-small" onclick="acceptOrder(<?php echo $order['id']; ?>)">
                                    Accept Order
                                </button>
                                <button class="btn btn-secondary btn-small" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                                    View Details
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- History Tab -->
        <div id="history-tab" class="tab-content">
            <div class="orders-section">
                <div class="section-header">
                    <h2>📦 Delivered Orders - History</h2>
                </div>
                <?php if (empty($delivery_history)): ?>
                    <div class="empty-state">
                        <div style="font-size: 3rem;">📋</div>
                        <p>No delivery history yet</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($delivery_history as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div>
                                    <div class="order-id">Order #<?php echo $order['id']; ?></div>
                                    <div style="color: #666; font-size: 0.9rem;">
                                        Delivered: <?php echo date('M d, Y h:i A', strtotime($order['delivered_at'])); ?>
                                    </div>
                                </div>
                                <span class="order-status status-delivered">Delivered</span>
                            </div>

                            <div class="order-details">
                                <div class="detail-item">
                                    <span class="detail-label">🏪 Restaurant</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($order['restaurant_name']); ?></span>
                                    <span style="font-size: 0.85rem; color: #666;"><?php echo htmlspecialchars($order['restaurant_location']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">👤 Customer</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($order['customer_name']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">📍 Delivery Address</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($order['delivery_address']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">💵 Order Amount</span>
                                    <span class="detail-value">₹<?php echo number_format($order['total_price'], 2); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">💰 Your Earnings</span>
                                    <span class="detail-value" style="color: #28a745;">
                                        ₹<?php echo number_format($order['earnings'] ?? 0, 2); ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Order Items Section -->
                            <?php if (!empty($order['items'])): ?>
                            <div class="order-items-section">
                                <div class="order-items-header">
                                    🍽️ Order Items (<?php echo count($order['items']); ?> items)
                                </div>
                                <div class="item-list">
                                    <?php foreach ($order['items'] as $item): ?>
                                        <div class="item-row">
                                            <div class="item-info">
                                                <span class="item-category-badge item-category-<?php echo $item['category']; ?>">
                                                    <?php echo strtoupper($item['category']); ?>
                                                </span>
                                                <span class="item-name"><?php echo htmlspecialchars($item['item_name']); ?></span>
                                                <span class="item-quantity">x<?php echo $item['quantity']; ?></span>
                                            </div>
                                            <span class="item-price">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Profile Tab -->
        <div id="profile-tab" class="tab-content">
            <div class="orders-section">
                <div class="section-header">
                    <h2>👤 My Profile</h2>
                </div>
                
                <div style="max-width: 800px; margin: 0 auto;">
                    <!-- Profile View Mode -->
                    <div id="profile-view">
                        <div style="background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;">
                                <div class="detail-item">
                                    <span class="detail-label">👤 Full Name</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($agent['name']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">📧 Email</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($agent['email']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">📱 Phone</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($agent['phone'] ?? 'Not provided'); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">📍 Address</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($agent['address'] ?? 'Not provided'); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">🎯 Total Deliveries</span>
                                    <span class="detail-value"><?php echo $agent['total_deliveries']; ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">⭐ Rating</span>
                                    <span class="detail-value"><?php echo number_format($agent['rating'], 1); ?> / 5.0</span>
                                </div>
                                <?php if (isset($agent['license_number']) && $agent['license_number']): ?>
                                <div class="detail-item">
                                    <span class="detail-label">🪪 License Number</span>
                                    <span class="detail-value"><?php echo htmlspecialchars($agent['license_number']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div style="margin-top: 2rem; text-align: center;">
                                <button class="btn btn-primary" onclick="showEditForm()" style="padding: 0.75rem 2rem; font-size: 1rem;">
                                    ✏️ Edit Profile
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Edit Mode -->
                    <div id="profile-edit" style="display: none;">
                        <div style="background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            <form id="profileForm" onsubmit="updateProfile(event)">
                                <div style="display: grid; gap: 1.5rem;">
                                    <div>
                                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">👤 Full Name</label>
                                        <input type="text" id="edit_name" value="<?php echo htmlspecialchars($agent['name']); ?>" 
                                               style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem;" required>
                                    </div>
                                    
                                    <div>
                                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">📧 Email</label>
                                        <input type="email" id="edit_email" value="<?php echo htmlspecialchars($agent['email']); ?>" 
                                               style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem;" required>
                                    </div>
                                    
                                    <div>
                                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">📱 Phone</label>
                                        <input type="tel" id="edit_phone" value="<?php echo htmlspecialchars($agent['phone'] ?? ''); ?>" 
                                               style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem;" required>
                                    </div>
                                    
                                    <div>
                                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">📍 Address</label>
                                        <textarea id="edit_address" rows="3" 
                                                  style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem;" required><?php echo htmlspecialchars($agent['address'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <div>
                                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">🔒 New Password (leave blank to keep current)</label>
                                        <input type="password" id="edit_password" 
                                               style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem;" 
                                               placeholder="Enter new password (optional)">
                                    </div>
                                    
                                    <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 1rem;">
                                        <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem; font-size: 1rem;">
                                            💾 Save Changes
                                        </button>
                                        <button type="button" class="btn btn-secondary" onclick="cancelEdit()" style="padding: 0.75rem 2rem; font-size: 1rem;">
                                            ❌ Cancel
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            // Hide all tabs
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

            // Show selected tab
            document.querySelector(`.tab[onclick*="${tab}"]`).classList.add('active');
            document.getElementById(`${tab}-tab`).classList.add('active');
        }

        function toggleOnlineStatus() {
            const toggle = document.getElementById('onlineToggle');
            const statusText = document.getElementById('statusText');
            const isOnline = toggle.classList.contains('active');

            fetch('<?php echo BASE_URL; ?>php/update_agent_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ is_online: !isOnline })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    toggle.classList.toggle('active');
                    statusText.textContent = !isOnline ? 'Online' : 'Offline';
                } else {
                    alert('Failed to update status');
                }
            });
        }

        function acceptOrder(orderId) {
            if (confirm('Accept this order for delivery?')) {
                fetch('<?php echo BASE_URL; ?>php/assign_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: orderId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Order accepted successfully!');
                        location.reload();
                    } else {
                        alert(data.message || 'Failed to accept order');
                    }
                });
            }
        }

        function updateOrderStatus(orderId, status) {
            const messages = {
                'picked_up': 'Mark this order as picked up?',
                'out_for_delivery': 'Start delivery for this order?',
                'delivered': 'Mark this order as delivered?'
            };

            if (confirm(messages[status])) {
                fetch('<?php echo BASE_URL; ?>php/update_delivery_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: orderId, status: status })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Order status updated successfully!');
                        location.reload();
                    } else {
                        alert(data.message || 'Failed to update status');
                    }
                });
            }
        }

        function navigateToAddress(address) {
            const googleMapsUrl = `https://www.google.com/maps/search/?api=1&query=${address}`;
            window.open(googleMapsUrl, '_blank');
        }

        function viewOrderDetails(orderId) {
            window.location.href = '<?php echo BASE_URL; ?>pages/order_details.php?id=' + orderId;
        }

        // Auto-refresh every 30 seconds
        setInterval(() => {
            location.reload();
        }, 30000);

        // Location tracking functionality
        let locationWatchId = null;

        function startLocationTracking() {
            if ('geolocation' in navigator) {
                locationWatchId = navigator.geolocation.watchPosition(
                    (position) => {
                        updateLocation(position.coords.latitude, position.coords.longitude);
                    },
                    (error) => {
                        console.error('Location error:', error);
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 5000,
                        maximumAge: 0
                    }
                );
            }
        }

        function stopLocationTracking() {
            if (locationWatchId !== null) {
                navigator.geolocation.clearWatch(locationWatchId);
                locationWatchId = null;
            }
        }

        function updateLocation(latitude, longitude) {
            fetch('<?php echo BASE_URL; ?>php/update_location.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ latitude, longitude })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.error('Failed to update location');
                }
            })
            .catch(error => console.error('Location update error:', error));
        }

        // Start tracking when agent is online
        <?php if ($agent['is_online']): ?>
        startLocationTracking();
        <?php endif; ?>

        // Update tracking when online status changes
        const originalToggle = toggleOnlineStatus;
        toggleOnlineStatus = function() {
            originalToggle();
            setTimeout(() => {
                const isOnline = document.getElementById('onlineToggle').classList.contains('active');
                if (isOnline) {
                    startLocationTracking();
                } else {
                    stopLocationTracking();
                }
            }, 500);
        };

        // Profile edit functions
        function showEditForm() {
            document.getElementById('profile-view').style.display = 'none';
            document.getElementById('profile-edit').style.display = 'block';
        }

        function cancelEdit() {
            document.getElementById('profile-view').style.display = 'block';
            document.getElementById('profile-edit').style.display = 'none';
        }

        function updateProfile(event) {
            event.preventDefault();
            
            const name = document.getElementById('edit_name').value;
            const email = document.getElementById('edit_email').value;
            const phone = document.getElementById('edit_phone').value;
            const address = document.getElementById('edit_address').value;
            const password = document.getElementById('edit_password').value;

            fetch('<?php echo BASE_URL; ?>php/update_profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    name: name,
                    email: email,
                    phone: phone,
                    address: address,
                    password: password
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Profile updated successfully!');
                    location.reload();
                } else {
                    alert(data.message || 'Failed to update profile');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating profile');
            });
        }
    </script>
</body>
</html>
