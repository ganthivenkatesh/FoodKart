<?php
require_once '../php/config.php';

$conn = getDBConnection();

// Get filter parameters
$category = isset($_GET['category']) ? $_GET['category'] : 'all';
$cuisine = isset($_GET['cuisine']) ? $_GET['cuisine'] : 'all';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$restaurant_id = isset($_GET['restaurant']) ? intval($_GET['restaurant']) : 0;

// Build query
$query = "SELECT m.*, r.name as restaurant_name, r.location 
          FROM menu_items m 
          JOIN restaurants r ON m.restaurant_id = r.id 
          WHERE m.is_available = TRUE AND r.status = 'approved'";

if ($category !== 'all') {
    $query .= " AND m.category = '" . $conn->real_escape_string($category) . "'";
}

if ($cuisine !== 'all') {
    $query .= " AND r.cuisine = '" . $conn->real_escape_string($cuisine) . "'";
}

if ($search) {
    $query .= " AND (m.name LIKE '%" . $conn->real_escape_string($search) . "%' 
                OR m.description LIKE '%" . $conn->real_escape_string($search) . "%'
                OR r.name LIKE '%" . $conn->real_escape_string($search) . "%')";
}

if ($restaurant_id > 0) {
    $query .= " AND m.restaurant_id = " . $restaurant_id;
}

$query .= " ORDER BY m.created_at DESC";

$menu_items = $conn->query($query);

// Get all restaurants for filter
$restaurants_query = "SELECT id, name FROM restaurants WHERE status = 'approved' ORDER BY name";
$restaurants = $conn->query($restaurants_query);

// Get all cuisines dynamically for filter
$cuisines_query = "SELECT DISTINCT cuisine FROM restaurants WHERE status = 'approved' AND cuisine IS NOT NULL AND TRIM(cuisine) <> '' ORDER BY cuisine";
$cuisines = $conn->query($cuisines_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - FoodKart</title>
    <link rel="stylesheet" href="../styles/main.css">
    <style>
        .menu-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            text-align: center;
        }
        
        .menu-layout {
            display: block;
            position: relative;
        }
        
        .sidebar-filters {
            position: fixed !important;
            left: -100% !important;
        }
        
        .sidebar-filters.show {
            left: 0 !important;
        }
        
        
        .filter-toggle-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: white;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .filter-toggle-btn:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .filter-toggle-btn.active {
            background: var(--primary-color);
            color: white;
        }
        
        .filter-icon {
            font-size: 1.2rem;
        }
        
        .filter-section {
            margin-bottom: 2rem;
        }
        
        .filter-section:last-child {
            margin-bottom: 0;
        }
        
        .filter-section h3 {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--light-text);
            margin-bottom: 1rem;
            font-weight: 600;
        }
        
        .filter-options {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .filter-option {
            padding: 0.75rem 1rem;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
            text-decoration: none;
            color: var(--dark-text);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: white;
        }
        
        .filter-option.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .filter-option:hover:not(.active) {
            border-color: var(--primary-color);
            background-color: #f8f9fa;
        }
        
        .search-box {
            margin-bottom: 1.5rem;
        }
        
        .search-box input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .filter-divider {
            height: 1px;
            background: #e0e0e0;
            margin: 1.5rem 0;
        }
        
        .menu-content {
            min-width: 0;
        }
        
        .menu-content .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            align-items: stretch;
        }
        
        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .results-count {
            font-size: 0.9rem;
            color: var(--light-text);
        }
        
        .clear-filters {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .clear-filters:hover {
            text-decoration: underline;
        }
        
        .filter-tabs {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 1rem;
        }
        
        .filter-tab {
            padding: 0.7rem 1.5rem;
            border: 2px solid #ddd;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
            text-decoration: none;
            color: var(--dark-text);
        }
        
        .filter-tab.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .filter-tab:hover {
            border-color: var(--primary-color);
        }
        
        .search-filter {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .menu-item-card {
            display: flex;
            flex-direction: column;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            height: 100%;
            width: 100%;
        }
        
        .menu-item-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        
        .menu-item-image {
            width: 100%;
            height: 280px;
            object-fit: cover;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .menu-item-image-placeholder {
            width: 100%;
            height: 280px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 5rem;
        }
        
        .menu-item-image-container {
            width: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .menu-item-add-to-cart {
            position: absolute;
            bottom: 10px;
            right: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10;
        }
        
        .menu-item-add-to-cart .btn {
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 200px;
        }
        
        .menu-item-content {
            flex: 1;
            padding: 1.75rem;
            display: flex;
            flex-direction: column;
            gap: 0.9rem;
            justify-content: space-between;
        }
        
        .menu-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.4rem;
            gap: 0.75rem;
        }
        
        .menu-item-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex: 1;
            min-width: 0;
        }
        
        .menu-item-title h3 {
            margin: 0;
            font-size: 1.35rem;
            color: var(--dark-text);
            line-height: 1.4;
            word-wrap: break-word;
            overflow-wrap: break-word;
            font-weight: 600;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .veg-icon {
            width: 22px;
            height: 22px;
            border: 2px solid #27ae60;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .veg-icon::after {
            content: '';
            width: 11px;
            height: 11px;
            background-color: #27ae60;
            border-radius: 50%;
        }
        
        .non-veg-icon {
            width: 22px;
            height: 22px;
            border: 2px solid #e74c3c;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .non-veg-icon::after {
            content: '';
            width: 11px;
            height: 11px;
            background-color: #e74c3c;
            border-radius: 50%;
        }
        
        .combo-icon {
            width: 22px;
            height: 22px;
            border: 2px solid #f39c12;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .combo-icon::after {
            content: '';
            width: 11px;
            height: 11px;
            background-color: #f39c12;
            border-radius: 50%;
        }
        
        .price-section {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 0.25rem;
        }
        
        .original-price {
            text-decoration: line-through;
            color: var(--light-text);
            font-size: 1.1rem;
        }
        
        .discounted-price {
            color: var(--primary-color);
            font-weight: bold;
            font-size: 1.6rem;
        }
        
        .discount-badge {
            background-color: #27ae60;
            color: white;
            padding: 0.35rem 0.7rem;
            border-radius: 5px;
            font-size: 0.85rem;
            white-space: nowrap;
            flex-shrink: 0;
        }
        
        .restaurant-info-small {
            color: var(--light-text);
            font-size: 1rem;
            margin: 0;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .menu-item-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            padding-top: 0.5rem;
        }
        
        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .qty-btn {
            width: 30px;
            height: 30px;
            border: 1px solid var(--primary-color);
            background: white;
            color: var(--primary-color);
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .qty-btn:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .qty-display {
            min-width: 30px;
            text-align: center;
            font-weight: bold;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }
        
        .empty-state img {
            max-width: 300px;
            margin-bottom: 2rem;
        }
        
        .menu-item-description {
            color: var(--light-text);
            margin: 0;
            line-height: 1.5;
            font-size: 1rem;
            word-wrap: break-word;
            overflow-wrap: break-word;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        /* Sidebar slide-in behavior */
        .sidebar-filters {
            background-color: white;
            padding: 1.5rem;
            position: fixed;
            top: 0;
            left: -100%;
            width: 320px;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            border-radius: 0;
            transition: left 0.3s ease;
        }
        
        .sidebar-filters.show {
            left: 0;
        }
        
        .filter-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        
        .filter-overlay.show {
            display: block;
        }
        
        .filter-close-btn {
            display: flex !important;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 1rem;
            margin-bottom: 1rem;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .filter-close-btn h3 {
            margin: 0;
            font-size: 1.1rem;
            color: var(--dark-text);
        }
        
        .close-icon {
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--light-text);
            transition: color 0.3s;
        }
        
        .close-icon:hover {
            color: var(--primary-color);
        }
        
        @media (min-width: 1600px) {
            .menu-content .grid-3 {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (max-width: 900px) {
            .menu-content .grid-3 {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .menu-content .grid-3 {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 968px) {
            .menu-layout {
                grid-template-columns: 1fr;
            }
            
            .filter-options {
                flex-direction: row;
                flex-wrap: wrap;
            }
        }
        
        @media (max-width: 768px) {
            .menu-content .grid-3 {
                grid-template-columns: 1fr;
            }
            
            .menu-item-image,
            .menu-item-image-placeholder {
                height: 200px;
            }
            
            .menu-item-content {
                padding: 1rem;
            }
        }
        
        @media (max-width: 768px) {
            .search-filter {
                flex-direction: column;
            }
            
            .menu-item-add-to-cart .btn {
                font-size: 0.9rem;
                padding: 0.5rem 1rem;
            }
            
            .filter-options {
                flex-direction: column;
            }
            
            .menu-content .grid-3 {
                grid-template-columns: 1fr;
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
    <div class="menu-header">
        <div class="container">
            <h1>🍽️ Our Menu</h1>
            <p>Explore delicious food from top restaurants</p>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="container" style="padding: 2rem 20px;">
        <!-- Filter Toggle Button (Mobile) -->
        <button class="filter-toggle-btn" id="filterToggleBtn" onclick="toggleFilters()">
            <span class="filter-icon">🎛️</span>
            <span>Filters</span>
            <span id="activeFilterCount" style="display: none; background: var(--primary-color); color: white; padding: 0.2rem 0.5rem; border-radius: 12px; font-size: 0.8rem;"></span>
        </button>
        
        <!-- Filter Overlay (Mobile) -->
        <div class="filter-overlay" id="filterOverlay" onclick="toggleFilters()"></div>
        
        <div class="menu-layout">
            <!-- Sidebar Filters -->
            <aside class="sidebar-filters" id="sidebarFilters">
                <!-- Close Button (Mobile) -->
                <div class="filter-close-btn" id="filterCloseBtn">
                    <h3>🎛️ Filters</h3>
                    <span class="close-icon" onclick="toggleFilters()">✕</span>
                </div>
                
                <!-- Search -->
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="🔍 Search dishes..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <!-- Cuisine Filter -->
                <div class="filter-section">
                    <h3>Cuisine</h3>
                    <div class="filter-options">
                        <a href="?cuisine=all&category=<?php echo $category; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                           class="filter-option <?php echo $cuisine === 'all' ? 'active' : ''; ?>">
                            <span>🌍</span> All Cuisines
                        </a>
                        <?php if ($cuisines && $cuisines->num_rows > 0): ?>
                            <?php while ($c = $cuisines->fetch_assoc()): 
                                $cname = trim($c['cuisine']);
                                if ($cname === '') continue;
                            ?>
                                <a href="?cuisine=<?php echo urlencode($cname); ?>&category=<?php echo $category; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="filter-option <?php echo $cuisine === $cname ? 'active' : ''; ?>">
                                    <span>🍽️</span> <?php echo htmlspecialchars($cname); ?>
                                </a>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="filter-divider"></div>
                
                <!-- Category Filter -->
                <div class="filter-section">
                    <h3>Category</h3>
                    <div class="filter-options">
                        <a href="?cuisine=<?php echo $cuisine; ?>&category=all<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                           class="filter-option <?php echo $category === 'all' ? 'active' : ''; ?>">
                            <span>🍴</span> All Items
                        </a>
                        <a href="?cuisine=<?php echo $cuisine; ?>&category=veg<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                           class="filter-option <?php echo $category === 'veg' ? 'active' : ''; ?>">
                            <span>🥗</span> Vegetarian
                        </a>
                        <a href="?cuisine=<?php echo $cuisine; ?>&category=non-veg<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                           class="filter-option <?php echo $category === 'non-veg' ? 'active' : ''; ?>">
                            <span>🍗</span> Non-Veg
                        </a>
                        <a href="?cuisine=<?php echo $cuisine; ?>&category=combo<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                           class="filter-option <?php echo $category === 'combo' ? 'active' : ''; ?>">
                            <span>🍱</span> Combos
                        </a>
                    </div>
                </div>
                
                <div class="filter-divider"></div>
                
                <!-- Restaurant Filter -->
                <div class="filter-section">
                    <h3>Restaurant</h3>
                    <select id="restaurantFilter" class="form-control" style="width: 100%;" onchange="applyFilters()">
                        <option value="0">All Restaurants</option>
                        <?php 
                        $restaurants->data_seek(0); // Reset pointer
                        while ($rest = $restaurants->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $rest['id']; ?>" <?php echo $restaurant_id == $rest['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($rest['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <?php if ($cuisine !== 'all' || $category !== 'all' || $search || $restaurant_id > 0): ?>
                    <div class="filter-divider"></div>
                    <a href="menu.php" class="clear-filters" style="display: block; text-align: center;">✕ Clear All Filters</a>
                <?php endif; ?>
            </aside>
            
            <!-- Menu Content -->
            <div class="menu-content">
                <!-- Results Header -->
                <div class="results-header">
                    <div class="results-count">
                        <strong><?php echo $menu_items->num_rows; ?></strong> items found
                    </div>
                </div>
                
                <!-- Menu Items Grid -->
                <div class="grid grid-3">
            <?php if ($menu_items->num_rows > 0): ?>
                <?php while ($item = $menu_items->fetch_assoc()): 
                    $final_price = $item['price'] - ($item['price'] * $item['discount'] / 100);
                    $img_raw = trim($item['image'] ?? '');
                    $is_remote = $img_raw && (stripos($img_raw, 'http://') === 0 || stripos($img_raw, 'https://') === 0);
                    $image_path = $img_raw ? ($is_remote ? $img_raw : ('../assets/images/' . $img_raw)) : '';
                    $has_valid_image = $image_path && ($is_remote || (file_exists($image_path) && filesize($image_path) > 0));
                    $category_emoji = $item['category'] === 'veg' ? '🥗' : ($item['category'] === 'non-veg' ? '🍗' : '🍱');
                ?>
                    <div class="card menu-item-card">
                        <div class="menu-item-image-container">
                            <?php if ($has_valid_image): ?>
                                <img src="<?php echo $image_path; ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                     class="menu-item-image"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="menu-item-image-placeholder" style="display: none;"><?php echo $category_emoji; ?></div>
                            <?php else: ?>
                                <div class="menu-item-image-placeholder"><?php echo $category_emoji; ?></div>
                            <?php endif; ?>

                            <div class="menu-item-add-to-cart">
                                <?php if (isLoggedIn() && hasRole('customer')): ?>
                                    <button class="btn btn-primary" onclick="addToCart(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>', <?php echo $final_price; ?>, '<?php echo $has_valid_image ? $image_path : '' ; ?>')">
                                        Add to Cart
                                    </button>
                                <?php else: ?>
                                    <a href="../login_signup.php" class="btn btn-primary">Login to Order</a>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="menu-item-content">
                            <div>
                                <div class="menu-item-header">
                                    <div class="menu-item-title">
                                        <div class="<?php echo $item['category']; ?>-icon"></div>
                                        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                    </div>
                                </div>
                                
                                <p class="restaurant-info-small">
                                    📍 <?php echo htmlspecialchars($item['restaurant_name']); ?> • <?php echo htmlspecialchars($item['location']); ?>
                                </p>
                                
                                <p class="menu-item-description">
                                    <?php echo htmlspecialchars($item['description']); ?>
                                </p>
                            </div>
                            
                            <div class="menu-item-footer">
                                <div class="price-section">
                                    <?php if ($item['discount'] > 0): ?>
                                        <div>
                                            <span class="discount-badge"><?php echo $item['discount']; ?>% OFF</span>
                                        </div>
                                        <div>
                                            <span class="discounted-price">₹<?php echo number_format($final_price, 2); ?></span>
                                        </div>
                                        <div>
                                            <span class="original-price">₹<?php echo number_format($item['price'], 2); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <span class="discounted-price">₹<?php echo number_format($item['price'], 2); ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="buy-now-btn">
                                    <?php if (isLoggedIn() && hasRole('customer')): ?>
                                        <button class="btn btn-primary" onclick="buyNow(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>', <?php echo $final_price; ?>, '<?php echo $has_valid_image ? $image_path : '' ; ?>')" style="font-size: 0.9rem; padding: 0.6rem 1.2rem;">
                                            Buy Now
                                        </button>
                                    <?php else: ?>
                                        <a href="../login_signup.php" class="btn btn-primary" style="font-size: 0.9rem; padding: 0.6rem 1.2rem;">Login to Buy</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state" style="grid-column: 1 / -1;">
                    <h2>😕 No items found</h2>
                    <p>Try adjusting your filters or search terms</p>
                    <a href="menu.php" class="btn btn-primary mt-3">View All Items</a>
                </div>
            <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../scripts/cart.js"></script>
    <script>
        function applyFilters() {
            const search = document.getElementById('searchInput').value;
            const restaurant = document.getElementById('restaurantFilter').value;
            const category = '<?php echo $category; ?>';
            const cuisine = '<?php echo $cuisine; ?>';
            
            let url = '?cuisine=' + cuisine + '&category=' + category;
            if (search) url += '&search=' + encodeURIComponent(search);
            if (restaurant != '0') url += '&restaurant=' + restaurant;
            
            window.location.href = url;
        }
        
        // Auto-search on typing (with debounce)
        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                applyFilters();
            }, 500);
        });
        
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                clearTimeout(searchTimeout);
                applyFilters();
            }
        });
        
        // Toggle filters sidebar
        function toggleFilters() {
            const sidebar = document.getElementById('sidebarFilters');
            const overlay = document.getElementById('filterOverlay');
            const toggleBtn = document.getElementById('filterToggleBtn');
            const closeBtn = document.getElementById('filterCloseBtn');
            
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
            toggleBtn.classList.toggle('active');
            
            // Prevent body scroll when filters are open
            if (sidebar.classList.contains('show')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        }
        
        // Update active filter count badge
        function updateFilterCount() {
            const cuisine = '<?php echo $cuisine; ?>';
            const category = '<?php echo $category; ?>';
            const search = '<?php echo $search; ?>';
            const restaurant = '<?php echo $restaurant_id; ?>';
            
            let count = 0;
            if (cuisine !== 'all') count++;
            if (category !== 'all') count++;
            if (search) count++;
            if (restaurant > 0) count++;
            
            const badge = document.getElementById('activeFilterCount');
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        }
        
        // Buy Now function - adds to cart and redirects to checkout
        function buyNow(itemId, itemName, price, imageUrl = '') {
            // Add to cart first
            addToCart(itemId, itemName, price, imageUrl);
            
            // Small delay to ensure cart is updated, then redirect to cart/checkout
            setTimeout(function() {
                window.location.href = '../pages/cart.php';
            }, 300);
        }
        
        // Initialize
        updateFilterCount();
        
        // Update cart count on page load
        updateCartCount();
    </script>
</body>
</html>
<?php $conn->close(); ?>
