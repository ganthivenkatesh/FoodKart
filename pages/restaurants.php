<?php
require_once '../php/config.php';

$conn = getDBConnection();

// Get all approved restaurants
$query = "SELECT r.*, u.name as owner_name 
          FROM restaurants r 
          JOIN users u ON r.owner_id = u.id 
          WHERE r.status = 'approved' 
          ORDER BY r.rating DESC";
$restaurants = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurants - FoodKart</title>
    <link rel="stylesheet" href="../styles/main.css">
    <style>
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            text-align: center;
        }
        
        .restaurant-card {
            display: flex;
            gap: 1.5rem;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .restaurant-image {
            width: 250px;
            height: 200px;
            object-fit: cover;
            flex-shrink: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .restaurant-image-placeholder {
            width: 250px;
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            flex-shrink: 0;
        }
        
        .restaurant-content {
            flex: 1;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
        }
        
        .restaurant-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }
        
        .restaurant-meta {
            display: flex;
            gap: 2rem;
            margin: 1rem 0;
            color: var(--light-text);
        }
        
        .restaurant-meta span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.85rem;
        }
        
        .status-open {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-closed {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        @media (max-width: 768px) {
            .restaurant-card {
                flex-direction: column;
            }
            
            .restaurant-image {
                width: 100%;
            }
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
                <li><a href="restaurants.php">Restaurants</a></li>
                <li><a href="menu.php">Menu</a></li>
                <li><a href="contact.php">Contact</a></li>
                <?php if (isLoggedIn()): ?>
                    <?php if (hasRole('customer')): ?>
                        <li><a href="cart.php">🛒 Cart <span id="cartCount" class="badge badge-danger">0</span></a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo getDashboardUrl(); ?>">Dashboard</a></li>
                    <li><a href="../php/auth.php?logout=1" class="btn btn-outline">Logout</a></li>
                <?php else: ?>
                    <li><a href="../login_signup.php" class="btn btn-primary">Login / Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    
    <!-- Header -->
    <div class="page-header">
        <div class="container">
            <h1>🍽️ Our Restaurants</h1>
            <p>Discover amazing restaurants near you</p>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="container" style="padding: 3rem 20px;">
        <div class="grid" style="gap: 2rem;">
            <?php if ($restaurants->num_rows > 0): ?>
                <?php while ($restaurant = $restaurants->fetch_assoc()): 
                    $rest_image_path = !empty($restaurant['image']) ? '../assets/images/' . $restaurant['image'] : '';
                    $has_valid_rest_image = $rest_image_path && file_exists($rest_image_path) && filesize($rest_image_path) > 0;
                    // Set emoji based on restaurant name/cuisine
                    $rest_emoji = '🍴'; // default
                    if (stripos($restaurant['name'], 'pizza') !== false || stripos($restaurant['cuisine'], 'italian') !== false) {
                        $rest_emoji = '🍕'; // Pizza for Italian restaurants
                    } elseif (stripos($restaurant['name'], 'spice') !== false || stripos($restaurant['cuisine'], 'indian') !== false) {
                        $rest_emoji = '🍗'; // Spicy grilled chicken for Indian restaurants
                    }
                ?>
                    <div class="card restaurant-card">
                        <?php if ($has_valid_rest_image): ?>
                            <img src="<?php echo $rest_image_path; ?>" 
                                 alt="<?php echo htmlspecialchars($restaurant['name']); ?>" 
                                 class="restaurant-image"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="restaurant-image-placeholder" style="display: none;"><?php echo $rest_emoji; ?></div>
                        <?php else: ?>
                            <div class="restaurant-image-placeholder"><?php echo $rest_emoji; ?></div>
                        <?php endif; ?>
                        
                        <div class="restaurant-content">
                            <div class="restaurant-header">
                                <div>
                                    <h2><?php echo htmlspecialchars($restaurant['name']); ?></h2>
                                    <p style="color: var(--light-text); margin: 0.3rem 0;">
                                        <?php echo htmlspecialchars($restaurant['cuisine']); ?>
                                    </p>
                                </div>
                                <div style="text-align: right;">
                                    <div class="rating" style="font-size: 1.2rem; color: #f39c12; font-weight: bold;">
                                        ⭐ <?php echo number_format($restaurant['rating'], 1); ?>
                                    </div>
                                    <span class="status-badge <?php echo $restaurant['is_open'] ? 'status-open' : 'status-closed'; ?>">
                                        <?php echo $restaurant['is_open'] ? '🟢 Open' : '🔴 Closed'; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <p style="color: var(--dark-text); margin: 1rem 0;">
                                <?php echo htmlspecialchars($restaurant['description']); ?>
                            </p>
                            
                            <div class="restaurant-meta">
                                <span>📍 <?php echo htmlspecialchars($restaurant['location']); ?></span>
                                <?php if ($restaurant['phone']): ?>
                                    <span>📞 <?php echo htmlspecialchars($restaurant['phone']); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div style="margin-top: auto;">
                                <a href="restaurant_detail.php?id=<?php echo $restaurant['id']; ?>" 
                                   class="btn btn-primary">
                                    View Menu
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state" style="text-align: center; padding: 4rem 2rem;">
                    <h2>😕 No restaurants available</h2>
                    <p>Check back later for new restaurants!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (isLoggedIn()): ?>
    <script src="../scripts/cart.js"></script>
    <script>updateCartCount();</script>
    <?php endif; ?>
</body>
</html>
<?php $conn->close(); ?>
