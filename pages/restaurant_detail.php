<?php
require_once '../php/config.php';

$restaurant_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($restaurant_id <= 0) {
    header('Location: restaurants.php');
    exit();
}

$conn = getDBConnection();

// Get restaurant details
$rest_query = "SELECT r.*, u.name as owner_name 
               FROM restaurants r 
               JOIN users u ON r.owner_id = u.id 
               WHERE r.id = ? AND r.status = 'approved'";
$stmt = $conn->prepare($rest_query);
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: restaurants.php');
    exit();
}

$restaurant = $result->fetch_assoc();

// Get menu items
$menu_query = "SELECT * FROM menu_items WHERE restaurant_id = ? AND is_available = TRUE";
$stmt = $conn->prepare($menu_query);
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$menu_items = $stmt->get_result();

// Load items into array for custom sorting by cuisine relevance
$all_items = [];
while ($row = $menu_items->fetch_assoc()) { $all_items[] = $row; }

// Build cuisine keywords from restaurant cuisine/name (legacy; not used for filtering now)
$cuisine_text = strtolower(($restaurant['cuisine'] ?? '') . ' ' . ($restaurant['name'] ?? ''));
$keywords = [];
if (strpos($cuisine_text, 'ital') !== false || strpos($cuisine_text, 'pizza') !== false) {
    $keywords = array_merge($keywords, ['pizza','pasta','margherita','pepperoni','lasagna','risotto','bruschetta','alfredo']);
}
if (strpos($cuisine_text, 'south') !== false || strpos($cuisine_text, 'udupi') !== false) {
    $keywords = array_merge($keywords, ['idli','dosa','sambar','sambhar','vada','curd rice','pongal','uttapam']);
}
if (strpos($cuisine_text, 'north') !== false || strpos($cuisine_text, 'punjab') !== false || strpos($cuisine_text, 'mughl') !== false) {
    $keywords = array_merge($keywords, ['paneer','tikka','butter chicken','dal makhani','naan','biryani','kebab']);
}
if (strpos($cuisine_text, 'chinese') !== false || strpos($cuisine_text, 'asia') !== false) {
    $keywords = array_merge($keywords, ['noodles','fried rice','manchurian','schezwan','spring roll','kung pao','dumpling']);
}
if (strpos($cuisine_text, 'american') !== false || strpos($cuisine_text, 'burger') !== false) {
    $keywords = array_merge($keywords, ['burger','fries','hot dog','bbq','barbeque','wings']);
}
if (strpos($cuisine_text, 'mexic') !== false) {
    $keywords = array_merge($keywords, ['taco','burrito','quesadilla','nacho','salsa','enchilada']);
}
if (strpos($cuisine_text, 'japan') !== false || strpos($cuisine_text, 'sushi') !== false) {
    $keywords = array_merge($keywords, ['sushi','ramen','tempura','teriyaki']);
}

// Deduplicate keywords
$keywords = array_values(array_unique($keywords));

// Compute cuisine match score and build filtered items list
$score = function($it) use ($keywords) {
    $text = strtolower(($it['name'] ?? '') . ' ' . ($it['description'] ?? ''));
    $s = 0;
    foreach ($keywords as $kw) {
        if ($kw !== '' && strpos($text, $kw) !== false) { $s++; }
    }
    return $s;
};

// Use all items as before (no cuisine filtering)
$use_items = $all_items;

// Sort chosen items by score desc, then name asc
usort($use_items, function($a, $b) use ($score) {
    $sa = $score($a); $sb = $score($b);
    if ($sa === $sb) { return strcasecmp($a['name'] ?? '', $b['name'] ?? ''); }
    return $sb <=> $sa;
});

// Get reviews
$reviews_query = "SELECT f.*, u.name as customer_name 
                  FROM feedback f 
                  JOIN users u ON f.user_id = u.id 
                  WHERE f.restaurant_id = ? 
                  ORDER BY f.created_at DESC 
                  LIMIT 5";
$stmt = $conn->prepare($reviews_query);
$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$reviews = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($restaurant['name']); ?> - FoodKart</title>
    <link rel="stylesheet" href="../styles/main.css">
    <style>
        .restaurant-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
        }
        
        .restaurant-info-header {
            display: flex;
            gap: 2rem;
            align-items: start;
        }
        
        .restaurant-details {
            flex: 1;
        }
        
        .restaurant-stats {
            display: flex;
            gap: 2rem;
            margin-top: 1rem;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .menu-section {
            margin: 3rem 0;
        }
        
        .category-section {
            margin-bottom: 3rem;
        }
        
        .category-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--primary-color);
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .menu-item-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px 8px 0 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .menu-item-image-placeholder {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            border-radius: 8px 8px 0 0;
        }
        
        .reviews-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin: 3rem 0;
        }
        
        .review-card {
            border-bottom: 1px solid #eee;
            padding: 1.5rem 0;
        }
        
        .review-card:last-child {
            border-bottom: none;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .stars {
            color: #f39c12;
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
    
    <!-- Restaurant Banner -->
    <div class="restaurant-banner">
        <div class="container">
            <div class="restaurant-info-header">
                <div class="restaurant-details">
                    <h1><?php echo htmlspecialchars($restaurant['name']); ?></h1>
                    <p style="font-size: 1.1rem; margin: 0.5rem 0;"><?php echo htmlspecialchars($restaurant['cuisine']); ?></p>
                    <p style="margin: 0.5rem 0;"><?php echo htmlspecialchars($restaurant['description']); ?></p>
                    
                    <div class="restaurant-stats">
                        <div class="stat-item">
                            <span>⭐</span>
                            <strong><?php echo number_format($restaurant['rating'], 1); ?></strong>
                        </div>
                        <div class="stat-item">
                            <span>📍</span>
                            <span><?php echo htmlspecialchars($restaurant['location']); ?></span>
                        </div>
                        <?php if (!empty($restaurant['phone'])): ?>
                        <div class="stat-item">
                            <span>📞</span>
                            <span><?php echo htmlspecialchars($restaurant['phone']); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="stat-item">
                            <?php if ($restaurant['is_open']): ?>
                                <span class="badge badge-success">🟢 Open Now</span>
                            <?php else: ?>
                                <span class="badge badge-danger">🔴 Closed</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Menu Section -->
    <div class="container menu-section">
        <h2 style="margin-bottom: 2rem;">🍽️ Menu</h2>
        <?php if (empty($use_items)): ?>
            <div class="card" style="padding: 1.5rem;">
                <p style="margin: 0;">No items available right now.</p>
            </div>
        <?php else: ?>
        <?php
        $categories = ['veg' => '🥗 Vegetarian', 'non-veg' => '🍗 Non-Vegetarian', 'combo' => '🍱 Combo Offers'];
        
        foreach ($categories as $cat_key => $cat_name):
            $has_items = false;
            foreach ($use_items as $probe) {
                if ($probe['category'] === $cat_key) { $has_items = true; break; }
            }
            if (!$has_items) continue;
        ?>
            <div class="category-section">
                <div class="category-header">
                    <h3><?php echo $cat_name; ?></h3>
                </div>
                
                <div class="menu-grid">
                    <?php foreach ($use_items as $item): ?>
                        <?php if ($item['category'] === $cat_key): 
                            $final_price = $item['price'] - ($item['price'] * $item['discount'] / 100);
                            $img_raw = trim($item['image'] ?? '');
                            $is_remote = $img_raw && (stripos($img_raw, 'http://') === 0 || stripos($img_raw, 'https://') === 0);
                            $image_path = $img_raw ? ($is_remote ? $img_raw : ('../assets/images/' . $img_raw)) : '';
                            $category_emoji = $cat_key === 'veg' ? '🥗' : ($cat_key === 'non-veg' ? '🍗' : '🍱');
                            $has_valid_image = $image_path && ($is_remote || (file_exists($image_path) && filesize($image_path) > 0));
                        ?>
                            <div class="card">
                                <?php if ($has_valid_image): ?>
                                    <img src="<?php echo $image_path; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="menu-item-image" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="menu-item-image-placeholder" style="display: none;"><?php echo $category_emoji; ?></div>
                                <?php else: ?>
                                    <div class="menu-item-image-placeholder"><?php echo $category_emoji; ?></div>
                                <?php endif; ?>
                                
                                <div style="padding: 1.5rem;">
                                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                        <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                        <?php if ($item['discount'] > 0): ?>
                                            <span class="badge badge-success"><?php echo $item['discount']; ?>% OFF</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <p style="color: var(--light-text); margin: 0.5rem 0;">
                                        <?php echo htmlspecialchars($item['description']); ?>
                                    </p>
                                    
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem;">
                                        <div>
                                            <?php if ($item['discount'] > 0): ?>
                                                <span style="text-decoration: line-through; color: var(--light-text); font-size: 0.9rem;">
                                                    ₹<?php echo number_format($item['price'], 2); ?>
                                                </span>
                                                <strong style="color: var(--primary-color); font-size: 1.2rem; margin-left: 0.5rem;">
                                                    ₹<?php echo number_format($final_price, 2); ?>
                                                </strong>
                                            <?php else: ?>
                                                <strong style="color: var(--primary-color); font-size: 1.2rem;">
                                                    ₹<?php echo number_format($item['price'], 2); ?>
                                                </strong>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (isLoggedIn() && hasRole('customer')): ?>
                                            <button class="btn btn-primary" onclick="addToCart(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>', <?php echo $final_price; ?>, '<?php echo $has_valid_image ? $image_path : '' ; ?>')">
                                                Add to Cart
                                            </button>
                                        <?php else: ?>
                                            <a href="../login_signup.php" class="btn btn-outline">Login to Order</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Reviews Section -->
    <?php if ($reviews->num_rows > 0): ?>
    <div class="container">
        <div class="reviews-section">
            <h2 style="margin-bottom: 1.5rem;">⭐ Customer Reviews</h2>
            
            <?php while ($review = $reviews->fetch_assoc()): ?>
                <div class="review-card">
                    <div class="review-header">
                        <div>
                            <strong><?php echo htmlspecialchars($review['customer_name']); ?></strong>
                            <div class="stars">
                                <?php for ($i = 0; $i < $review['rating']; $i++): ?>⭐<?php endfor; ?>
                            </div>
                        </div>
                        <small style="color: var(--light-text);">
                            <?php echo date('d M Y', strtotime($review['created_at'])); ?>
                        </small>
                    </div>
                    <?php if ($review['message']): ?>
                        <p style="margin: 0; color: var(--dark-text);">
                            <?php echo htmlspecialchars($review['message']); ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (isLoggedIn()): ?>
    <script src="../scripts/cart.js"></script>
    <script>updateCartCount();</script>
    <?php endif; ?>
</body>
</html>
<?php $conn->close(); ?>
