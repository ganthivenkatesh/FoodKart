<?php
require_once '../php/config.php';
requireRole('customer');

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id <= 0) {
    header('Location: user_home.php');
    exit();
}

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get order details
$order_query = "SELECT o.*, r.name as restaurant_name, r.location, p.transaction_id, p.method as payment_method 
                FROM orders o 
                JOIN restaurants r ON o.restaurant_id = r.id 
                LEFT JOIN payments p ON o.id = p.order_id 
                WHERE o.id = ? AND o.user_id = ?";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: user_home.php');
    exit();
}

$order = $result->fetch_assoc();

// Get order items
$items_query = "SELECT oi.*, m.name as item_name, m.category, m.image as item_image 
                FROM order_items oi 
                JOIN menu_items m ON oi.item_id = m.id 
                WHERE oi.order_id = ?";
$stmt = $conn->prepare($items_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - FoodKart</title>
    <link rel="stylesheet" href="../styles/main.css">
    <style>
        .details-container {
            max-width: 800px;
            margin: 3rem auto;
            padding: 0 20px;
        }
        
        .details-card {
            background: white;
            border-radius: 15px;
            padding: 2.5rem;
            box-shadow: var(--shadow);
        }
        
        .detail-section {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #eee;
        }
        
        .detail-section:last-child {
            border-bottom: none;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.8rem 0;
        }
        
        .items-table {
            width: 100%;
            margin: 1rem 0;
        }
        
        .items-table th,
        .items-table td {
            padding: 0.8rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .items-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .item-thumb {
            width: 64px;
            height: 64px;
            object-fit: cover;
            border-radius: 6px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin-right: 10px;
            vertical-align: middle;
        }
        .item-cell {
            display: flex;
            align-items: center;
            gap: 10px;
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
                <li><a href="menu.php">Menu</a></li>
                <li><a href="user_home.php">Dashboard</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="details-container">
        <div class="details-card">
            <h1 style="margin-bottom: 2rem;">📋 Order Details</h1>
            
            <!-- Order Info -->
            <div class="detail-section">
                <h3>Order Information</h3>
                <div class="detail-row">
                    <strong>Order ID:</strong>
                    <span>#<?php echo $order['id']; ?></span>
                </div>
                <div class="detail-row">
                    <strong>Order Date:</strong>
                    <span><?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></span>
                </div>
                <div class="detail-row">
                    <strong>Order Status:</strong>
                    <span class="badge <?php 
                        echo match($order['order_status']) {
                            'delivered' => 'badge-success',
                            'cancelled' => 'badge-danger',
                            'out_for_delivery' => 'badge-veg',
                            default => 'badge-warning'
                        };
                    ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $order['order_status'])); ?>
                    </span>
                </div>
            </div>
            
            <!-- Restaurant Info -->
            <div class="detail-section">
                <h3>Restaurant Details</h3>
                <div class="detail-row">
                    <strong>Restaurant:</strong>
                    <span><?php echo htmlspecialchars($order['restaurant_name']); ?></span>
                </div>
                <div class="detail-row">
                    <strong>Location:</strong>
                    <span><?php echo htmlspecialchars($order['location']); ?></span>
                </div>
            </div>
            
            <!-- Order Items -->
            <div class="detail-section">
                <h3>Items Ordered</h3>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $subtotal = 0;
                        while ($item = $items->fetch_assoc()): 
                            $item_total = $item['price'] * $item['quantity'];
                            $subtotal += $item_total;
                            $image_path = !empty($item['item_image']) ? '../assets/images/' . $item['item_image'] : '';
                            $has_valid_image = $image_path && file_exists($image_path) && filesize($image_path) > 0;
                            $fallback_image = $item['category'] === 'veg' 
                                ? '../assets/images/veg-biryani.jpg' 
                                : ($item['category'] === 'non-veg' 
                                    ? '../assets/images/mutton-biryani.jpg' 
                                    : '../assets/images/party-combo.jpg');
                            $name_lc = strtolower($item['item_name']);
                            $keyword_image = '';
                            if (strpos($name_lc, 'margherita') !== false) {
                                $keyword_image = '../assets/images/margherita-pizza.jpg';
                            } elseif (strpos($name_lc, 'pepperoni') !== false) {
                                $keyword_image = '../assets/images/pepperoni-delight.jpg';
                            } elseif (strpos($name_lc, 'bbq') !== false || strpos($name_lc, 'barbeque') !== false) {
                                $keyword_image = '../assets/images/chicken-bbq-pizza.jpg';
                            } elseif (strpos($name_lc, 'veggie') !== false || strpos($name_lc, 'vegetable') !== false) {
                                $keyword_image = '../assets/images/veggie-supreme.jpg';
                            } elseif (strpos($name_lc, 'paneer') !== false) {
                                $keyword_image = '../assets/images/paneer-tikka.jpg';
                            } elseif (strpos($name_lc, 'tikka') !== false || strpos($name_lc, 'masala') !== false) {
                                $keyword_image = '../assets/images/chicken-tikka-masala.jpg';
                            } elseif (strpos($name_lc, 'veg biryani') !== false || (strpos($name_lc, 'biryani') !== false && strpos($name_lc, 'veg') !== false)) {
                                $keyword_image = '../assets/images/veg-biryani.jpg';
                            } elseif (strpos($name_lc, 'biryani') !== false) {
                                $keyword_image = '../assets/images/mutton-biryani.jpg';
                            } elseif (strpos($name_lc, 'family') !== false) {
                                $keyword_image = '../assets/images/family-combo.jpg';
                            } elseif (strpos($name_lc, 'combo') !== false || strpos($name_lc, 'party') !== false) {
                                $keyword_image = '../assets/images/party-combo.jpg';
                            }
                            $resolved_image = $has_valid_image ? $image_path : ($keyword_image ?: $fallback_image);
                        ?>
                            <tr>
                                <td class="item-cell">
                                    <img src="<?php echo $resolved_image; ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>" class="item-thumb" onerror="this.src='<?php echo $fallback_image; ?>'">
                                    <span><?php echo htmlspecialchars($item['item_name']); ?></span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $item['category']; ?>">
                                        <?php echo ucfirst($item['category']); ?>
                                    </span>
                                </td>
                                <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>₹<?php echo number_format($item_total, 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Payment Info -->
            <div class="detail-section">
                <h3>Payment Details</h3>
                <div class="detail-row">
                    <strong>Payment Method:</strong>
                    <span><?php echo strtoupper($order['payment_method']); ?></span>
                </div>
                <div class="detail-row">
                    <strong>Payment Status:</strong>
                    <span class="badge <?php echo $order['payment_status'] === 'completed' ? 'badge-success' : 'badge-warning'; ?>">
                        <?php echo ucfirst($order['payment_status']); ?>
                    </span>
                </div>
                <?php if ($order['transaction_id']): ?>
                <div class="detail-row">
                    <strong>Transaction ID:</strong>
                    <span><?php echo htmlspecialchars($order['transaction_id']); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="detail-row" style="margin-top: 1rem; padding-top: 1rem; border-top: 2px solid #eee;">
                    <strong style="font-size: 1.2rem;">Total Amount:</strong>
                    <strong style="font-size: 1.2rem; color: var(--primary-color);">
                        ₹<?php echo number_format($order['total_price'], 2); ?>
                    </strong>
                </div>
            </div>
            
            <!-- Delivery Address -->
            <div class="detail-section">
                <h3>Delivery Address</h3>
                <p style="margin: 0.5rem 0;"><?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?></p>
            </div>
            
            <!-- Actions -->
            <div style="display: flex; gap: 1rem;">
                <a href="user_home.php" class="btn btn-outline" style="flex: 1; text-align: center;">
                    Back to Orders
                </a>
                <?php if ($order['order_status'] !== 'delivered' && $order['order_status'] !== 'cancelled'): ?>
                    <a href="order_tracking.php?order_id=<?php echo $order_id; ?>" class="btn btn-primary" style="flex: 1; text-align: center;">
                        Track Order
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
