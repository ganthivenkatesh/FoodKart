<?php
require_once 'php/config.php';

// Redirect delivery agents to their dashboard
if (isLoggedIn() && $_SESSION['role'] === 'delivery_agent') {
    header('Location: ' . BASE_URL . 'dashboards/delivery_dashboard.php');
    exit();
}

// Redirect restaurant owners to their dashboard
if (isLoggedIn() && $_SESSION['role'] === 'restaurant_owner') {
    header('Location: ' . BASE_URL . 'dashboards/restaurant_dashboard.php');
    exit();
}

// Redirect admins to their dashboard
if (isLoggedIn() && $_SESSION['role'] === 'admin') {
    header('Location: ' . BASE_URL . 'dashboards/admin_dashboard.php');
    exit();
}

$conn = getDBConnection();

// Get logged-in user info
$user_name = '';
if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $user_query = "SELECT name FROM users WHERE id = ?";
    $stmt = $conn->prepare($user_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_name = $user['name'];
    }
}

// Fetch featured restaurants
$featured_query = "SELECT r.*, u.name as owner_name 
                   FROM restaurants r 
                   JOIN users u ON r.owner_id = u.id 
                   WHERE r.status = 'approved' 
                   ORDER BY r.rating DESC 
                   LIMIT 6";
$featured_restaurants = $conn->query($featured_query);

// Fetch active offers
$offers_query = "SELECT o.*, r.name as restaurant_name 
                 FROM offers o 
                 JOIN restaurants r ON o.restaurant_id = r.id 
                 WHERE o.is_active = TRUE 
                 AND o.valid_until >= CURDATE() 
                 LIMIT 4";
$offers = $conn->query($offers_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoodKart - Order Food Online</title>
    <link rel="stylesheet" href="styles/main.css">
    <style>
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0;
            text-align: center;
        }
        
        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .hero p {
            font-size: 1.3rem;
            margin-bottom: 2rem;
        }
        
        .search-bar {
            max-width: 600px;
            margin: 0 auto;
            display: flex;
            gap: 1rem;
        }
        
        .search-bar input {
            flex: 1;
            padding: 1rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .section {
            padding: 3rem 0;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .section-header h2 {
            font-size: 2rem;
            color: var(--dark-text);
            margin-bottom: 0.5rem;
        }
        
        .restaurant-card {
            position: relative;
            overflow: hidden;
        }
        
        .restaurant-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px 10px 0 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .restaurant-image-placeholder {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 5rem;
            border-radius: 10px 10px 0 0;
        }
        
        .restaurant-info {
            padding: 1rem;
        }
        
        .restaurant-info h3 {
            margin-bottom: 0.5rem;
            color: var(--dark-text);
        }
        
        .restaurant-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.5rem;
            color: var(--light-text);
            font-size: 0.9rem;
        }
        
        .rating {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            color: #f39c12;
            font-weight: 600;
        }
        
        .offer-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            position: relative;
            overflow: hidden;
        }
        
        .offer-card::before {
            content: '🎉';
            position: absolute;
            right: -10px;
            top: -10px;
            font-size: 5rem;
            opacity: 0.2;
        }
        
        .offer-card h3 {
            margin-bottom: 0.5rem;
        }
        
        .offer-discount {
            font-size: 2rem;
            font-weight: bold;
            margin: 0.5rem 0;
        }
        
        .features {
            background-color: #f8f9fa;
        }
        
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .feature-item {
            text-align: center;
            padding: 2rem;
        }
        
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .feature-item h3 {
            margin-bottom: 0.5rem;
            color: var(--dark-text);
        }
        
        .cta-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0;
            text-align: center;
        }
        
        .cta-section h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo">🍴 FoodKart</a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="pages/restaurants.php">Restaurants</a></li>
                <li><a href="pages/menu.php">Menu</a></li>
                <li><a href="pages/contact.php">Contact</a></li>
                <?php if (isLoggedIn()): ?>
                    <?php if (hasRole('customer')): ?>
                        <li><a href="pages/cart.php">🛒 Cart</a></li>
                    <?php endif; ?>
                    <?php if ($user_name): ?>
                        <li><a href="<?php echo getDashboardUrl(); ?>">👤 <?php echo htmlspecialchars(explode(' ', $user_name)[0]); ?></a></li>
                    <?php else: ?>
                        <li><a href="<?php echo getDashboardUrl(); ?>">Dashboard</a></li>
                    <?php endif; ?>
                    <li><a href="php/auth.php?logout=1" class="btn btn-outline">Logout</a></li>
                <?php else: ?>
                    <li><a href="login_signup.php" class="btn btn-primary">Login / Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <?php if (isLoggedIn() && $user_name): ?>
                <h1>👋 Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h1>
                <p>What would you like to eat today?</p>
            <?php else: ?>
                <h1>🍴 Delicious Food, Delivered Fast</h1>
                <p>Order from your favorite restaurants and get it delivered to your doorstep</p>
            <?php endif; ?>
            <div class="search-bar">
                <input type="text" placeholder="Search for restaurants or dishes..." id="searchInput">
                <button class="btn btn-secondary" onclick="searchFood()">Search</button>
            </div>
        </div>
    </section>
    
    <!-- Features Section -->
    <section class="features section">
        <div class="container">
            <div class="feature-grid">
                <div class="feature-item">
                    <div class="feature-icon">🚀</div>
                    <h3>Fast Delivery</h3>
                    <p>Get your food delivered in 30 minutes or less</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">🍽️</div>
                    <h3>Wide Selection</h3>
                    <p>Choose from hundreds of restaurants and cuisines</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">💳</div>
                    <h3>Secure Payment</h3>
                    <p>Multiple payment options with secure checkout</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">⭐</div>
                    <h3>Quality Food</h3>
                    <p>Only the best restaurants with top ratings</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Featured Restaurants -->
    <section class="section">
        <div class="container">
            <div class="section-header">
                <h2>Featured Restaurants</h2>
                <p>Top-rated restaurants near you</p>
            </div>
            
            <div class="grid grid-3">
                <?php if ($featured_restaurants->num_rows > 0): ?>
                    <?php while ($restaurant = $featured_restaurants->fetch_assoc()): 
                        $rest_image_path = !empty($restaurant['image']) ? 'assets/images/' . $restaurant['image'] : '';
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
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="restaurant-image-placeholder" style="display: none;"><?php echo $rest_emoji; ?></div>
                            <?php else: ?>
                                <div class="restaurant-image-placeholder"><?php echo $rest_emoji; ?></div>
                            <?php endif; ?>
                            <div class="restaurant-info">
                                <h3><?php echo htmlspecialchars($restaurant['name']); ?></h3>
                                <p><?php echo htmlspecialchars($restaurant['cuisine']); ?></p>
                                <div class="restaurant-meta">
                                    <span>📍 <?php echo htmlspecialchars($restaurant['location']); ?></span>
                                    <span class="rating">⭐ <?php echo number_format($restaurant['rating'], 1); ?></span>
                                </div>
                                <a href="pages/restaurant_detail.php?id=<?php echo $restaurant['id']; ?>" 
                                   class="btn btn-primary mt-2" style="width: 100%;">View Menu</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No restaurants available at the moment.</p>
                <?php endif; ?>
            </div>
            
            <div class="text-center mt-4">
                <a href="pages/restaurants.php" class="btn btn-outline">View All Restaurants</a>
            </div>
        </div>
    </section>
    
    <!-- Special Offers -->
    <?php if ($offers->num_rows > 0): ?>
    <section class="section" style="background-color: #f8f9fa;">
        <div class="container">
            <div class="section-header">
                <h2>Special Offers</h2>
                <p>Don't miss out on these amazing deals!</p>
            </div>
            
            <div class="grid grid-4">
                <?php while ($offer = $offers->fetch_assoc()): ?>
                    <div class="offer-card">
                        <h3><?php echo htmlspecialchars($offer['title']); ?></h3>
                        <div class="offer-discount"><?php echo $offer['discount_percent']; ?>% OFF</div>
                        <p><?php echo htmlspecialchars($offer['description']); ?></p>
                        <small>@ <?php echo htmlspecialchars($offer['restaurant_name']); ?></small>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
    
    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <?php if (isLoggedIn() && $user_name): ?>
                <h2>Hey <?php echo htmlspecialchars(explode(' ', $user_name)[0]); ?>, Your Stomach Called!</h2>
                <p style="font-size: 1.2rem; margin-bottom: 2rem;">It said it's time to order something delicious... We won't tell anyone you're drooling! 😋</p>
                <a href="pages/menu.php" class="btn btn-secondary" style="font-size: 1.1rem; padding: 1rem 2rem;">Browse Menu</a>
            <?php else: ?>
                <h2>Ready to Order?</h2>
                <p style="font-size: 1.2rem; margin-bottom: 2rem;">Join thousands of happy customers</p>
                <a href="login_signup.php" class="btn btn-secondary" style="font-size: 1.1rem; padding: 1rem 2rem;">Get Started Now</a>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- Footer -->
    <footer style="background-color: var(--secondary-color); color: white; padding: 2rem 0; text-align: center;">
        <div class="container">
            <p>&copy; 2025 FoodKart. All rights reserved.</p>
            <p style="margin-top: 0.5rem;">
                <a href="pages/contact.php" style="color: white; margin: 0 1rem;">Contact Us</a>
                <a href="pages/privacy_policy.php" style="color: white; margin: 0 1rem;">Privacy Policy</a>
                <a href="pages/terms_of_service.php" style="color: white; margin: 0 1rem;">Terms of Service</a>
            </p>
        </div>
    </footer>
    
    <script>
        function searchFood() {
            const query = document.getElementById('searchInput').value;
            if (query.trim()) {
                window.location.href = 'pages/menu.php?search=' + encodeURIComponent(query);
            }
        }
        
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchFood();
            }
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
