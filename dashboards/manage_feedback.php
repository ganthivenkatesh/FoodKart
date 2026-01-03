<?php
require_once '../php/config.php';
requireRole('admin');

$conn = getDBConnection();

// Get all feedback
$feedback_query = "SELECT f.*, u.name as user_name, u.email as user_email, r.name as restaurant_name 
                   FROM feedback f 
                   JOIN users u ON f.user_id = u.id 
                   JOIN restaurants r ON f.restaurant_id = r.id 
                   ORDER BY f.created_at DESC";
$feedback = $conn->query($feedback_query);

// Get statistics
$total_feedback = $conn->query("SELECT COUNT(*) as count FROM feedback")->fetch_assoc()['count'];
$avg_rating = $conn->query("SELECT AVG(rating) as avg FROM feedback")->fetch_assoc()['avg'] ?? 0;
$five_star = $conn->query("SELECT COUNT(*) as count FROM feedback WHERE rating = 5")->fetch_assoc()['count'];
$one_star = $conn->query("SELECT COUNT(*) as count FROM feedback WHERE rating = 1")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Feedback - FoodKart</title>
    <link rel="stylesheet" href="../styles/main.css">
    <style>
        .dashboard-layout {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }
        
        .sidebar {
            background-color: var(--secondary-color);
            color: white;
            padding: 2rem 0;
        }
        
        .sidebar-header {
            padding: 0 1.5rem;
            margin-bottom: 2rem;
        }
        
        .sidebar-menu {
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 0.5rem;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 1rem 1.5rem;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background-color: var(--primary-color);
        }
        
        .main-content {
            background-color: var(--light-bg);
            padding: 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: var(--shadow);
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .stat-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .stat-label {
            color: var(--light-text);
            margin-top: 0.5rem;
        }
        
        .section-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: var(--shadow);
        }
        
        .feedback-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .rating-stars {
            color: #ffc107;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>🍴 FoodKart</h2>
                <p style="font-size: 0.9rem; margin-top: 0.5rem;">Admin Panel</p>
            </div>
            
            <ul class="sidebar-menu">
                <li><a href="admin_dashboard.php">📊 Dashboard</a></li>
                <li><a href="manage_users.php">👥 Users</a></li>
                <li><a href="manage_restaurants_admin.php">🍽️ Restaurants</a></li>
                <li><a href="manage_all_orders.php">📦 All Orders</a></li>
                <li><a href="manage_feedback.php" class="active">💬 Feedback</a></li>
                <li><a href="manage_contacts.php">📧 Contact Messages</a></li>
                <li><a href="../php/auth.php?logout=1">🚪 Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <h1 style="margin-bottom: 2rem;">💬 Manage Feedback</h1>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">💬</div>
                    <div class="stat-value"><?php echo $total_feedback; ?></div>
                    <div class="stat-label">Total Feedback</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">⭐</div>
                    <div class="stat-value"><?php echo number_format($avg_rating, 1); ?></div>
                    <div class="stat-label">Average Rating</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🌟</div>
                    <div class="stat-value"><?php echo $five_star; ?></div>
                    <div class="stat-label">5-Star Reviews</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">⭐</div>
                    <div class="stat-value"><?php echo $one_star; ?></div>
                    <div class="stat-label">1-Star Reviews</div>
                </div>
            </div>
            
            <!-- Feedback List -->
            <div class="section-card">
                <h2 style="margin-bottom: 1.5rem;">All Feedback</h2>
                
                <?php if ($feedback->num_rows > 0): ?>
                    <?php while ($fb = $feedback->fetch_assoc()): ?>
                        <div class="feedback-card">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                <div>
                                    <h3 style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($fb['user_name']); ?></h3>
                                    <p style="color: var(--light-text); font-size: 0.9rem; margin: 0;">
                                        <?php echo htmlspecialchars($fb['user_email']); ?>
                                    </p>
                                    <p style="color: var(--light-text); font-size: 0.9rem; margin: 0.25rem 0;">
                                        Restaurant: <strong><?php echo htmlspecialchars($fb['restaurant_name']); ?></strong>
                                    </p>
                                </div>
                                <div style="text-align: right;">
                                    <div class="rating-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php echo $i <= $fb['rating'] ? '★' : '☆'; ?>
                                        <?php endfor; ?>
                                    </div>
                                    <p style="color: var(--light-text); font-size: 0.9rem; margin-top: 0.5rem;">
                                        <?php echo date('d M Y, H:i', strtotime($fb['created_at'])); ?>
                                    </p>
                                </div>
                            </div>
                            
                            <?php if ($fb['message']): ?>
                                <p style="background: #f8f9fa; padding: 1rem; border-radius: 5px; margin: 0;">
                                    "<?php echo htmlspecialchars($fb['message']); ?>"
                                </p>
                            <?php endif; ?>
                            
                            <?php if ($fb['order_id']): ?>
                                <p style="color: var(--light-text); font-size: 0.9rem; margin-top: 0.5rem;">
                                    Order ID: #<?php echo $fb['order_id']; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 4rem;">
                        <h3>No feedback yet</h3>
                        <p>Customer feedback will appear here.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
