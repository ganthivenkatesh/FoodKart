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
$order_query = "SELECT o.*, r.name as restaurant_name, p.transaction_id, p.method as payment_method 
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
$items_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - FoodKart</title>
    <link rel="stylesheet" href="../styles/main.css">
    <style>
        .confirmation-container {
            max-width: 700px;
            margin: 3rem auto;
            padding: 0 20px;
        }
        
        .success-icon {
            text-align: center;
            font-size: 5rem;
            margin-bottom: 1rem;
            animation: scaleIn 0.5s ease-out;
        }
        
        @keyframes scaleIn {
            from {
                transform: scale(0);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        .confirmation-card {
            background: white;
            border-radius: 15px;
            padding: 2.5rem;
            box-shadow: var(--shadow);
            text-align: center;
        }
        
        .order-id {
            font-size: 1.5rem;
            color: var(--primary-color);
            font-weight: bold;
            margin: 1rem 0;
        }
        
        .order-details {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 2rem 0;
            text-align: left;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.8rem 0;
            border-bottom: 1px solid #ddd;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .items-list {
            margin: 1rem 0;
        }
        
        .item-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
        }
        .item-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .item-thumb {
            width: 56px;
            height: 56px;
            object-fit: cover;
            border-radius: 6px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .action-buttons a {
            flex: 1;
            text-align: center;
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
    
    <div class="confirmation-container">
        <div class="confirmation-card">
            <div class="success-icon">✅</div>
            <h1>Order Placed Successfully!</h1>
            <p style="color: var(--light-text); margin: 1rem 0;">Thank you for your order. Your food is being prepared.</p>
            
            <div class="order-id">Order #<?php echo $order_id; ?></div>
            
            <div class="order-details">
                <h3 style="margin-bottom: 1rem;">Order Details</h3>
                
                <div class="detail-row">
                    <strong>Restaurant:</strong>
                    <span><?php echo htmlspecialchars($order['restaurant_name']); ?></span>
                </div>
                
                <div class="detail-row">
                    <strong>Order Status:</strong>
                    <span class="badge badge-warning"><?php echo ucfirst(str_replace('_', ' ', $order['order_status'])); ?></span>
                </div>
                
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
                
                <div class="detail-row">
                    <strong>Delivery Address:</strong>
                    <span><?php echo htmlspecialchars($order['delivery_address']); ?></span>
                </div>
                
                <div style="margin-top: 1.5rem;">
                    <h4>Items Ordered:</h4>
                    <div class="items-list">
                        <?php while ($item = $items_result->fetch_assoc()): 
                            $image_path = !empty($item['item_image']) ? '../assets/images/' . $item['item_image'] : '';
                            $has_valid_image = $image_path && file_exists($image_path) && filesize($image_path) > 0;
                            $fallback_image = ($item['category'] ?? '') === 'veg'
                                ? '../assets/images/veg-biryani.jpg'
                                : ((($item['category'] ?? '') === 'non-veg')
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
                            $line_total = $item['price'] * $item['quantity'];
                        ?>
                            <div class="item-row">
                                <div class="item-left">
                                    <img src="<?php echo $resolved_image; ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>" class="item-thumb" onerror="this.src='<?php echo $fallback_image; ?>'">
                                    <span><?php echo htmlspecialchars($item['item_name']); ?> x <?php echo $item['quantity']; ?></span>
                                </div>
                                <span>₹<?php echo number_format($line_total, 2); ?></span>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                
                <div class="detail-row" style="margin-top: 1rem; font-size: 1.2rem; font-weight: bold;">
                    <strong>Total Amount:</strong>
                    <span>₹<?php echo number_format($order['total_price'], 2); ?></span>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="order_tracking.php?order_id=<?php echo $order_id; ?>" class="btn btn-primary">
                    Track Order
                </a>
                <a href="menu.php" class="btn btn-outline">
                    Order More
                </a>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
