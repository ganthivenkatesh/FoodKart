<?php
// Navbar for FoodKart
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../php/config.php';
}
?>
<style>
    .navbar {
        background: white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 1rem 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        z-index: 1000;
    }
    
    .navbar-brand {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
        color: #ff6b6b;
        font-size: 1.5rem;
        font-weight: bold;
    }
    
    .navbar-menu {
        display: flex;
        gap: 2rem;
        align-items: center;
        list-style: none;
        margin: 0;
        padding: 0;
    }
    
    .navbar-menu a {
        text-decoration: none;
        color: #333;
        font-weight: 500;
        transition: color 0.3s;
    }
    
    .navbar-menu a:hover {
        color: #ff6b6b;
    }
    
    .navbar-user {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .user-name {
        color: #666;
        font-weight: 500;
    }
    
    .btn-logout {
        background: #ff6b6b;
        color: white;
        border: none;
        padding: 0.5rem 1.5rem;
        border-radius: 5px;
        cursor: pointer;
        text-decoration: none;
        font-weight: 500;
        transition: background 0.3s;
    }
    
    .btn-logout:hover {
        background: #ff5252;
    }
</style>

<nav class="navbar">
    <?php 
    // Determine home link based on role
    $home_link = BASE_URL . 'index.php';
    if (isLoggedIn()) {
        switch ($_SESSION['role']) {
            case 'delivery_agent':
                $home_link = BASE_URL . 'dashboards/delivery_dashboard.php';
                break;
            case 'restaurant_owner':
                $home_link = BASE_URL . 'dashboards/restaurant_dashboard.php';
                break;
            case 'admin':
                $home_link = BASE_URL . 'dashboards/admin_dashboard.php';
                break;
        }
    }
    ?>
    <a href="<?php echo $home_link; ?>" class="navbar-brand">
        🍽️ FoodKart
    </a>
    
    <ul class="navbar-menu">
        <?php if (isLoggedIn()): ?>
            <?php if ($_SESSION['role'] === 'customer'): ?>
                <li><a href="<?php echo BASE_URL; ?>index.php">Home</a></li>
                <li><a href="<?php echo BASE_URL; ?>pages/restaurants.php">Restaurants</a></li>
                <li><a href="<?php echo BASE_URL; ?>pages/my_orders.php">My Orders</a></li>
            <?php elseif ($_SESSION['role'] === 'delivery_agent'): ?>
                <li><a href="<?php echo BASE_URL; ?>dashboards/delivery_dashboard.php">Dashboard</a></li>
            <?php elseif ($_SESSION['role'] === 'restaurant_owner'): ?>
                <li><a href="<?php echo BASE_URL; ?>dashboards/restaurant_dashboard.php">Dashboard</a></li>
            <?php elseif ($_SESSION['role'] === 'admin'): ?>
                <li><a href="<?php echo BASE_URL; ?>dashboards/admin_dashboard.php">Dashboard</a></li>
            <?php endif; ?>
        <?php else: ?>
            <li><a href="<?php echo BASE_URL; ?>index.php">Home</a></li>
            <li><a href="<?php echo BASE_URL; ?>pages/restaurants.php">Restaurants</a></li>
        <?php endif; ?>
    </ul>
    
    <div class="navbar-user">
        <?php if (isLoggedIn()): ?>
            <span class="user-name">👤 <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <?php if ($_SESSION['role'] === 'delivery_agent'): ?>
                <a href="<?php echo BASE_URL; ?>dashboards/delivery_profile.php" class="btn-logout" style="background: #667eea;">My Profile</a>
            <?php endif; ?>
            <a href="<?php echo BASE_URL; ?>php/auth.php?logout=1" class="btn-logout">Logout</a>
        <?php else: ?>
            <a href="<?php echo BASE_URL; ?>login_signup.php" class="btn-logout">Login</a>
        <?php endif; ?>
    </div>
</nav>

<script>
function switchToProfile() {
    // Wait for page load, then switch to profile tab
    setTimeout(() => {
        if (typeof switchTab === 'function') {
            switchTab('profile');
        }
    }, 100);
}

// Check if URL has #profile hash
if (window.location.hash === '#profile' && typeof switchTab === 'function') {
    switchTab('profile');
}
</script>
