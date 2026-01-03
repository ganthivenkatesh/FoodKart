<?php
require_once '../php/config.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'login_signup.php');
    exit();
}

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

$conn = getDBConnection();

// Get order details with delivery agent info
$stmt = $conn->prepare("
    SELECT o.*, 
           r.name as restaurant_name, r.location as restaurant_location, r.phone as restaurant_phone,
           u.name as customer_name, u.phone as customer_phone,
           da_user.name as agent_name, da_user.phone as agent_phone, da_user.vehicle_type, da_user.vehicle_number,
           da.rating as agent_rating, da.current_latitude, da.current_longitude
    FROM orders o
    JOIN restaurants r ON o.restaurant_id = r.id
    JOIN users u ON o.user_id = u.id
    LEFT JOIN users da_user ON o.delivery_agent_id = da_user.id
    LEFT JOIN delivery_agents da ON da_user.id = da.user_id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: ' . BASE_URL . 'pages/track_order.php');
    exit();
}

$order = $result->fetch_assoc();

// Get tracking history
$stmt = $conn->prepare("
    SELECT ot.*, u.name as agent_name
    FROM order_tracking ot
    JOIN users u ON ot.delivery_agent_id = u.id
    WHERE ot.order_id = ?
    ORDER BY ot.created_at ASC
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$tracking_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$conn->close();

// Determine order progress
$status_steps = [
    'placed' => 1,
    'confirmed' => 2,
    'preparing' => 3,
    'ready_for_pickup' => 4,
    'assigned_to_agent' => 5,
    'picked_up' => 6,
    'out_for_delivery' => 7,
    'delivered' => 8
];

$current_step = $status_steps[$order['order_status']] ?? 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Order Tracking - FoodKart</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>styles/main.css">
    <style>
        .tracking-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .tracking-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
            margin-top: 2rem;
        }

        @media (max-width: 968px) {
            .tracking-grid {
                grid-template-columns: 1fr;
            }
        }

        .tracking-main {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .tracking-sidebar {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #eee;
        }

        .order-id {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .order-status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .status-placed { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #cfe2ff; color: #084298; }
        .status-preparing { background: #e7d4ff; color: #6f42c1; }
        .status-ready { background: #ffd6a5; color: #cc5500; }
        .status-assigned { background: #b3e5fc; color: #01579b; }
        .status-picked { background: #c8e6c9; color: #2e7d32; }
        .status-delivery { background: #81c784; color: #1b5e20; }
        .status-delivered { background: #a5d6a7; color: #1b5e20; }

        .progress-tracker {
            position: relative;
            padding: 2rem 0;
        }

        .progress-step {
            display: flex;
            align-items: flex-start;
            margin-bottom: 2rem;
            position: relative;
        }

        .progress-step:last-child {
            margin-bottom: 0;
        }

        .step-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            background: #e0e0e0;
            color: #999;
            flex-shrink: 0;
            position: relative;
            z-index: 2;
        }

        .step-icon.completed {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .step-icon.active {
            background: var(--primary-color);
            color: white;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .step-content {
            margin-left: 1.5rem;
            flex: 1;
        }

        .step-title {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
        }

        .step-time {
            color: #666;
            font-size: 0.9rem;
        }

        .step-line {
            position: absolute;
            left: 25px;
            top: 50px;
            width: 2px;
            height: calc(100% - 50px);
            background: #e0e0e0;
        }

        .step-line.completed {
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
        }

        .info-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .info-card h3 {
            margin-bottom: 1rem;
            color: var(--dark-text);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .agent-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .agent-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .agent-details {
            flex: 1;
        }

        .agent-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .agent-rating {
            color: #ffa500;
            font-size: 0.9rem;
        }

        .agent-vehicle {
            color: #666;
            font-size: 0.85rem;
        }

        .contact-btn {
            padding: 0.5rem 1rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            width: 100%;
            margin-top: 0.5rem;
        }

        .contact-btn:hover {
            opacity: 0.9;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #eee;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #666;
            font-size: 0.9rem;
        }

        .info-value {
            font-weight: 600;
            text-align: right;
        }

        .map-placeholder {
            width: 100%;
            height: 300px;
            background: #f0f0f0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            margin-bottom: 1rem;
        }

        .eta-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 2rem;
        }

        .eta-time {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .eta-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="tracking-container">
        <div class="order-header">
            <div>
                <div class="order-id">Order #<?php echo $order['id']; ?></div>
                <div style="color: #666; margin-top: 0.5rem;">
                    Placed on <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?>
                </div>
            </div>
            <span class="order-status-badge status-<?php echo str_replace('_', '-', $order['order_status']); ?>">
                <?php echo ucwords(str_replace('_', ' ', $order['order_status'])); ?>
            </span>
        </div>

        <?php if ($order['order_status'] === 'out_for_delivery' && $order['estimated_delivery_time']): ?>
            <div class="eta-banner">
                <div class="eta-time">
                    <?php 
                    $eta = strtotime($order['estimated_delivery_time']) - time();
                    echo ceil($eta / 60) . ' mins';
                    ?>
                </div>
                <div class="eta-label">Estimated Delivery Time</div>
            </div>
        <?php endif; ?>

        <div class="tracking-grid">
            <div class="tracking-main">
                <h2 style="margin-bottom: 2rem;">Order Progress</h2>
                
                <div class="progress-tracker">
                    <div class="progress-step">
                        <div class="step-icon <?php echo $current_step >= 1 ? 'completed' : ''; ?>">
                            📝
                        </div>
                        <div class="step-content">
                            <div class="step-title">Order Placed</div>
                            <div class="step-time"><?php echo date('h:i A', strtotime($order['created_at'])); ?></div>
                        </div>
                        <?php if ($current_step > 1): ?>
                            <div class="step-line completed"></div>
                        <?php endif; ?>
                    </div>

                    <div class="progress-step">
                        <div class="step-icon <?php echo $current_step >= 2 ? 'completed' : ''; ?> <?php echo $current_step == 2 ? 'active' : ''; ?>">
                            ✅
                        </div>
                        <div class="step-content">
                            <div class="step-title">Order Confirmed</div>
                            <div class="step-time">Restaurant confirmed your order</div>
                        </div>
                        <?php if ($current_step > 2): ?>
                            <div class="step-line completed"></div>
                        <?php endif; ?>
                    </div>

                    <div class="progress-step">
                        <div class="step-icon <?php echo $current_step >= 3 ? 'completed' : ''; ?> <?php echo $current_step == 3 ? 'active' : ''; ?>">
                            👨‍🍳
                        </div>
                        <div class="step-content">
                            <div class="step-title">Preparing Food</div>
                            <div class="step-time">Your food is being prepared</div>
                        </div>
                        <?php if ($current_step > 3): ?>
                            <div class="step-line completed"></div>
                        <?php endif; ?>
                    </div>

                    <div class="progress-step">
                        <div class="step-icon <?php echo $current_step >= 4 ? 'completed' : ''; ?> <?php echo $current_step == 4 ? 'active' : ''; ?>">
                            🍱
                        </div>
                        <div class="step-content">
                            <div class="step-title">Ready for Pickup</div>
                            <div class="step-time">Food is ready, waiting for delivery agent</div>
                        </div>
                        <?php if ($current_step > 4): ?>
                            <div class="step-line completed"></div>
                        <?php endif; ?>
                    </div>

                    <div class="progress-step">
                        <div class="step-icon <?php echo $current_step >= 5 ? 'completed' : ''; ?> <?php echo $current_step == 5 ? 'active' : ''; ?>">
                            🏍️
                        </div>
                        <div class="step-content">
                            <div class="step-title">Assigned to Delivery Agent</div>
                            <div class="step-time">
                                <?php echo $order['assigned_at'] ? date('h:i A', strtotime($order['assigned_at'])) : 'Pending'; ?>
                            </div>
                        </div>
                        <?php if ($current_step > 5): ?>
                            <div class="step-line completed"></div>
                        <?php endif; ?>
                    </div>

                    <div class="progress-step">
                        <div class="step-icon <?php echo $current_step >= 6 ? 'completed' : ''; ?> <?php echo $current_step == 6 ? 'active' : ''; ?>">
                            📦
                        </div>
                        <div class="step-content">
                            <div class="step-title">Picked Up</div>
                            <div class="step-time">
                                <?php echo $order['picked_up_at'] ? date('h:i A', strtotime($order['picked_up_at'])) : 'Pending'; ?>
                            </div>
                        </div>
                        <?php if ($current_step > 6): ?>
                            <div class="step-line completed"></div>
                        <?php endif; ?>
                    </div>

                    <div class="progress-step">
                        <div class="step-icon <?php echo $current_step >= 7 ? 'completed' : ''; ?> <?php echo $current_step == 7 ? 'active' : ''; ?>">
                            🚚
                        </div>
                        <div class="step-content">
                            <div class="step-title">Out for Delivery</div>
                            <div class="step-time">Your order is on the way!</div>
                        </div>
                        <?php if ($current_step > 7): ?>
                            <div class="step-line completed"></div>
                        <?php endif; ?>
                    </div>

                    <div class="progress-step">
                        <div class="step-icon <?php echo $current_step >= 8 ? 'completed' : ''; ?> <?php echo $current_step == 8 ? 'active' : ''; ?>">
                            ✨
                        </div>
                        <div class="step-content">
                            <div class="step-title">Delivered</div>
                            <div class="step-time">
                                <?php echo $order['delivered_at'] ? date('h:i A', strtotime($order['delivered_at'])) : 'Pending'; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tracking-sidebar">
                <?php if ($order['delivery_agent_id']): ?>
                    <div class="info-card">
                        <h3>🏍️ Delivery Agent</h3>
                        <div class="agent-info">
                            <div class="agent-avatar">
                                <?php echo strtoupper(substr($order['agent_name'], 0, 1)); ?>
                            </div>
                            <div class="agent-details">
                                <div class="agent-name"><?php echo htmlspecialchars($order['agent_name']); ?></div>
                                <div class="agent-rating">⭐ <?php echo number_format($order['agent_rating'], 1); ?></div>
                                <div class="agent-vehicle">
                                    <?php echo htmlspecialchars($order['vehicle_type']); ?> - 
                                    <?php echo htmlspecialchars($order['vehicle_number']); ?>
                                </div>
                            </div>
                        </div>
                        <button class="contact-btn" onclick="contactAgent('<?php echo $order['agent_phone']; ?>')">
                            📞 Contact Agent
                        </button>
                    </div>
                <?php endif; ?>

                <div class="info-card">
                    <h3>🍴 Restaurant Details</h3>
                    <div class="info-row">
                        <span class="info-label">Name</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['restaurant_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Location</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['restaurant_location']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phone</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['restaurant_phone']); ?></span>
                    </div>
                </div>

                <div class="info-card">
                    <h3>💰 Order Summary</h3>
                    <div class="info-row">
                        <span class="info-label">Order Total</span>
                        <span class="info-value">₹<?php echo number_format($order['total_price'], 2); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Payment Status</span>
                        <span class="info-value"><?php echo ucfirst($order['payment_status']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Delivery Address</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['delivery_address']); ?></span>
                    </div>
                </div>

                <?php if (!empty($tracking_history)): ?>
                    <div class="info-card">
                        <h3>📍 Tracking History</h3>
                        <?php foreach ($tracking_history as $track): ?>
                            <div class="info-row">
                                <span class="info-label"><?php echo htmlspecialchars($track['notes']); ?></span>
                                <span class="info-value" style="font-size: 0.85rem;">
                                    <?php echo date('h:i A', strtotime($track['created_at'])); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function contactAgent(phone) {
            window.location.href = 'tel:' + phone;
        }

        // Auto-refresh every 15 seconds if order is not delivered
        <?php if ($order['order_status'] !== 'delivered' && $order['order_status'] !== 'cancelled'): ?>
        setInterval(() => {
            location.reload();
        }, 15000);
        <?php endif; ?>
    </script>
</body>
</html>
