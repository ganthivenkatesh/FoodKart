<?php
require_once '../php/config.php';
requireLogin(); // All logged-in users can access

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id <= 0) {
    header('Location: ../index.php');
    exit();
}

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Build query based on user role
if ($user_role === 'customer') {
    // Customer can only see their own orders
    $order_query = "SELECT o.*, r.name as restaurant_name, r.location, r.phone,
                    u.name as customer_name, u.phone as customer_phone
                    FROM orders o 
                    JOIN restaurants r ON o.restaurant_id = r.id 
                    JOIN users u ON o.user_id = u.id
                    WHERE o.id = ? AND o.user_id = ?";
    $stmt = $conn->prepare($order_query);
    $stmt->bind_param("ii", $order_id, $user_id);
} elseif ($user_role === 'restaurant_owner') {
    // Restaurant owner can see orders for their restaurant
    $order_query = "SELECT o.*, r.name as restaurant_name, r.location, r.phone,
                    u.name as customer_name, u.phone as customer_phone
                    FROM orders o 
                    JOIN restaurants r ON o.restaurant_id = r.id 
                    JOIN users u ON o.user_id = u.id
                    WHERE o.id = ? AND r.owner_id = ?";
    $stmt = $conn->prepare($order_query);
    $stmt->bind_param("ii", $order_id, $user_id);
} else {
    // Admin can see all orders
    $order_query = "SELECT o.*, r.name as restaurant_name, r.location, r.phone,
                    u.name as customer_name, u.phone as customer_phone
                    FROM orders o 
                    JOIN restaurants r ON o.restaurant_id = r.id 
                    JOIN users u ON o.user_id = u.id
                    WHERE o.id = ?";
    $stmt = $conn->prepare($order_query);
    $stmt->bind_param("i", $order_id);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: ../index.php');
    exit();
}

$order = $result->fetch_assoc();

// Get order items
$items_query = "SELECT oi.*, mi.name as item_name 
                FROM order_items oi 
                JOIN menu_items mi ON oi.item_id = mi.id 
                WHERE oi.order_id = ?";
$stmt = $conn->prepare($items_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_items = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order #<?php echo $order_id; ?> - FoodKart</title>
    <link rel="stylesheet" href="../styles/main.css">
    <style>
        .tracking-container {
            max-width: 900px;
            margin: 3rem auto;
            padding: 0 20px;
        }
        
        .tracking-card {
            background: white;
            border-radius: 15px;
            padding: 2.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
        
        .order-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 2px solid #eee;
        }
        
        .tracking-timeline {
            position: relative;
            padding: 2rem 0;
        }
        
        .timeline-item {
            position: relative;
            padding-left: 60px;
            margin-bottom: 2.5rem;
        }
        
        .timeline-item:last-child {
            margin-bottom: 0;
        }
        
        .timeline-icon {
            position: absolute;
            left: 0;
            top: 0;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background-color: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            z-index: 2;
        }
        
        .timeline-item.completed .timeline-icon {
            background-color: var(--success-color);
            color: white;
        }
        
        .timeline-item.active .timeline-icon {
            background-color: var(--primary-color);
            color: white;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .timeline-line {
            position: absolute;
            left: 22px;
            top: 45px;
            width: 2px;
            height: calc(100% - 45px);
            background-color: #ddd;
        }
        
        .timeline-item.completed .timeline-line {
            background-color: var(--success-color);
        }
        
        .timeline-content h3 {
            margin-bottom: 0.5rem;
            color: var(--dark-text);
        }
        
        .timeline-content p {
            color: var(--light-text);
            margin: 0;
        }
        
        .timeline-time {
            color: var(--light-text);
            font-size: 0.9rem;
            margin-top: 0.3rem;
        }
        
        .order-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
            padding: 1.5rem;
            background-color: #f8f9fa;
            border-radius: 10px;
        }
        
        .info-item {
            text-align: center;
        }
        
        .info-item .icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .info-item .label {
            color: var(--light-text);
            font-size: 0.9rem;
        }
        
        .info-item .value {
            font-weight: bold;
            color: var(--dark-text);
            margin-top: 0.3rem;
        }
        
        .refresh-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            transition: all 0.3s;
        }
        
        .refresh-btn:hover {
            transform: scale(1.1);
        }
        
        .order-items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .order-items-table th,
        .order-items-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .order-items-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        .status-update-section {
            background-color: #fff3cd;
            padding: 1.5rem;
            border-radius: 10px;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="container">
            <a href="../index.php" class="logo">🍴 FoodKart</a>
            <ul class="nav-links">
                <li><a href="../index.php">Home</a></li>
                <?php if ($user_role === 'customer'): ?>
                    <li><a href="user_home.php">Dashboard</a></li>
                <?php elseif ($user_role === 'restaurant_owner'): ?>
                    <li><a href="../dashboards/restaurant_dashboard.php">Dashboard</a></li>
                <?php else: ?>
                    <li><a href="../dashboards/admin_dashboard.php">Dashboard</a></li>
                <?php endif; ?>
                <li><a href="../php/auth.php?logout=1" class="btn btn-outline">Logout</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="tracking-container">
        <div class="tracking-card">
            <div class="order-header">
                <h1>🚚 Order Tracking</h1>
                <p style="color: var(--light-text);">Order #<?php echo $order_id; ?></p>
                <h3 style="color: var(--primary-color); margin-top: 0.5rem;">
                    <?php echo htmlspecialchars($order['restaurant_name']); ?>
                </h3>
            </div>
            
            <div class="order-info-grid">
                <div class="info-item">
                    <div class="icon">📦</div>
                    <div class="label">Order Status</div>
                    <div class="value"><?php echo ucfirst(str_replace('_', ' ', $order['order_status'])); ?></div>
                </div>
                <div class="info-item">
                    <div class="icon">💳</div>
                    <div class="label">Payment</div>
                    <div class="value"><?php echo ucfirst($order['payment_status']); ?></div>
                </div>
                <div class="info-item">
                    <div class="icon">💰</div>
                    <div class="label">Total Amount</div>
                    <div class="value">₹<?php echo number_format($order['total_price'], 2); ?></div>
                </div>
                <div class="info-item">
                    <div class="icon">📅</div>
                    <div class="label">Order Date</div>
                    <div class="value"><?php echo date('d M Y', strtotime($order['created_at'])); ?></div>
                </div>
            </div>
            
            <?php if ($user_role !== 'customer'): ?>
            <div style="background-color: #e7f3ff; padding: 1rem; border-radius: 8px; margin: 1rem 0;">
                <strong>👤 Customer Details:</strong><br>
                Name: <?php echo htmlspecialchars($order['customer_name']); ?><br>
                Phone: <?php echo htmlspecialchars($order['customer_phone']); ?><br>
                Address: <?php echo htmlspecialchars($order['delivery_address']); ?>
            </div>
            <?php endif; ?>
            
            <div class="tracking-timeline">
                <?php
                $statuses = [
                    'placed' => ['icon' => '📝', 'title' => 'Order Placed', 'desc' => 'Order has been received'],
                    'preparing' => ['icon' => '👨‍🍳', 'title' => 'Preparing', 'desc' => 'Restaurant is preparing the food'],
                    'out_for_delivery' => ['icon' => '🚚', 'title' => 'Out for Delivery', 'desc' => 'Order is on the way'],
                    'delivered' => ['icon' => '✅', 'title' => 'Delivered', 'desc' => 'Order delivered successfully']
                ];
                
                $current_status = $order['order_status'];
                $status_order = ['placed', 'preparing', 'out_for_delivery', 'delivered'];
                $current_index = array_search($current_status, $status_order);
                
                foreach ($status_order as $index => $status):
                    $status_info = $statuses[$status];
                    $is_completed = $index < $current_index;
                    $is_active = $index === $current_index;
                    $class = $is_completed ? 'completed' : ($is_active ? 'active' : '');
                ?>
                    <div class="timeline-item <?php echo $class; ?>">
                        <?php if ($index < count($status_order) - 1): ?>
                            <div class="timeline-line"></div>
                        <?php endif; ?>
                        <div class="timeline-icon"><?php echo $status_info['icon']; ?></div>
                        <div class="timeline-content">
                            <h3><?php echo $status_info['title']; ?></h3>
                            <p><?php echo $status_info['desc']; ?></p>
                            <?php if ($is_completed || $is_active): ?>
                                <div class="timeline-time">
                                    Updated: <?php echo date('d M Y, h:i A', strtotime($order['updated_at'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($user_role === 'restaurant_owner' && $order['order_status'] !== 'delivered' && $order['order_status'] !== 'cancelled'): ?>
            <div class="status-update-section">
                <h3>⚙️ Update Order Status</h3>
                <p style="margin: 0.5rem 0;">Change the order status to keep customer informed:</p>
                <select id="statusSelect" class="form-control" style="max-width: 300px; margin-top: 1rem;">
                    <option value="">Select Status</option>
                    <option value="preparing" <?php echo $order['order_status'] === 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                    <option value="out_for_delivery" <?php echo $order['order_status'] === 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                    <option value="delivered" <?php echo $order['order_status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                </select>
                <button class="btn btn-primary mt-2" onclick="updateStatus()">Update Status</button>
            </div>
            <?php endif; ?>
            
            <div class="tracking-card" style="margin-top: 2rem; padding: 1.5rem;">
                <h3>📋 Order Items</h3>
                <table class="order-items-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($item = $order_items->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>₹<?php echo number_format($item['price'], 2); ?></td>
                            <td>₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($order['order_status'] !== 'delivered' && $order['order_status'] !== 'cancelled'): ?>
                <div class="alert alert-info mt-4">
                    <strong>ℹ️ Estimated Delivery:</strong> 30-45 minutes
                </div>
            <?php endif; ?>
            
            <div class="d-flex gap-2 mt-4">
                <?php if ($user_role === 'customer'): ?>
                    <a href="user_home.php" class="btn btn-outline" style="flex: 1; text-align: center;">
                        Back to Orders
                    </a>
                    <?php if ($order['order_status'] === 'delivered'): ?>
                        <a href="feedback.php?order_id=<?php echo $order_id; ?>" class="btn btn-primary" style="flex: 1; text-align: center;">
                            Rate Order
                        </a>
                    <?php endif; ?>
                <?php elseif ($user_role === 'restaurant_owner'): ?>
                    <a href="../dashboards/restaurant_dashboard.php" class="btn btn-outline" style="flex: 1; text-align: center;">
                        Back to Dashboard
                    </a>
                <?php else: ?>
                    <a href="../dashboards/admin_dashboard.php" class="btn btn-outline" style="flex: 1; text-align: center;">
                        Back to Dashboard
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <button class="refresh-btn" onclick="location.reload()" title="Refresh Status">
        🔄
    </button>
    
    <script>
        // Auto-refresh every 30 seconds
        setTimeout(() => {
            location.reload();
        }, 30000);
        
        async function updateStatus() {
            const status = document.getElementById('statusSelect').value;
            if (!status) {
                alert('Please select a status');
                return;
            }
            
            try {
                const response = await fetch('../php/update_order_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        order_id: <?php echo $order_id; ?>,
                        status: status
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    alert('Order status updated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                alert('Error updating status');
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
