<?php
require_once '../php/config.php';
requireRole('admin');

$conn = getDBConnection();

// Get filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build query
$query = "SELECT r.*, u.name as owner_name, u.email as owner_email 
          FROM restaurants r 
          JOIN users u ON r.owner_id = u.id 
          WHERE 1=1";
if ($status_filter !== 'all') {
    $query .= " AND r.status = '" . $conn->real_escape_string($status_filter) . "'";
}
$query .= " ORDER BY r.created_at DESC";

$restaurants = $conn->query($query);

// Get statistics
$total_restaurants = $conn->query("SELECT COUNT(*) as count FROM restaurants")->fetch_assoc()['count'];
$approved_restaurants = $conn->query("SELECT COUNT(*) as count FROM restaurants WHERE status = 'approved'")->fetch_assoc()['count'];
$pending_restaurants = $conn->query("SELECT COUNT(*) as count FROM restaurants WHERE status = 'pending'")->fetch_assoc()['count'];
$rejected_restaurants = $conn->query("SELECT COUNT(*) as count FROM restaurants WHERE status = 'rejected'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Restaurants - FoodKart</title>
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
        
        .filters {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .section-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: var(--shadow);
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
                <li><a href="manage_restaurants_admin.php" class="active">🍽️ Restaurants</a></li>
                <li><a href="manage_all_orders.php">📦 All Orders</a></li>
                <li><a href="manage_feedback.php">💬 Feedback</a></li>
                <li><a href="manage_contacts.php">📧 Contact Messages</a></li>
                <li><a href="../php/auth.php?logout=1">🚪 Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <h1 style="margin-bottom: 2rem;">🍽️ Manage Restaurants</h1>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <a href="manage_restaurants_admin.php?status=all" class="stat-card">
                    <div class="stat-icon">🍽️</div>
                    <div class="stat-value"><?php echo $total_restaurants; ?></div>
                    <div class="stat-label">Total Restaurants</div>
                </a>
                
                <a href="manage_restaurants_admin.php?status=approved" class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-value"><?php echo $approved_restaurants; ?></div>
                    <div class="stat-label">Approved</div>
                </a>
                
                <a href="manage_restaurants_admin.php?status=pending" class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-value"><?php echo $pending_restaurants; ?></div>
                    <div class="stat-label">Pending</div>
                </a>
                
                <a href="manage_restaurants_admin.php?status=rejected" class="stat-card">
                    <div class="stat-icon">❌</div>
                    <div class="stat-value"><?php echo $rejected_restaurants; ?></div>
                    <div class="stat-label">Rejected</div>
                </a>
            </div>
            
            <!-- Filters -->
            <div class="filters">
                <strong>Filter by Status:</strong>
                <select class="form-control" onchange="applyFilter()" id="statusFilter">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            
            <!-- Restaurants Table -->
            <div class="section-card">
                <h2 style="margin-bottom: 1.5rem;">All Restaurants</h2>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Owner</th>
                            <th>Cuisine</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($restaurants->num_rows > 0): ?>
                            <?php while ($restaurant = $restaurants->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $restaurant['id']; ?></td>
                                    <td><?php echo htmlspecialchars($restaurant['name']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($restaurant['owner_name']); ?><br>
                                        <small style="color: var(--light-text);"><?php echo htmlspecialchars($restaurant['owner_email']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($restaurant['cuisine']); ?></td>
                                    <td><?php echo htmlspecialchars($restaurant['location']); ?></td>
                                    <td>
                                        <span class="badge <?php 
                                            echo match($restaurant['status']) {
                                                'approved' => 'badge-success',
                                                'rejected' => 'badge-danger',
                                                'pending' => 'badge-warning',
                                                default => 'badge-secondary'
                                            };
                                        ?>">
                                            <?php echo ucfirst($restaurant['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($restaurant['created_at'])); ?></td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                            <?php if ($restaurant['status'] === 'pending'): ?>
                                                <button onclick="updateStatus(<?php echo $restaurant['id']; ?>, 'approved')" 
                                                        class="btn btn-success" 
                                                        style="padding: 0.4rem 0.8rem; font-size: 0.9rem;">
                                                    Approve
                                                </button>
                                                <button onclick="updateStatus(<?php echo $restaurant['id']; ?>, 'rejected')" 
                                                        class="btn btn-outline" 
                                                        style="padding: 0.4rem 0.8rem; font-size: 0.9rem;">
                                                    Reject
                                                </button>
                                            <?php elseif ($restaurant['status'] === 'approved'): ?>
                                                <button onclick="updateStatus(<?php echo $restaurant['id']; ?>, 'rejected')" 
                                                        class="btn btn-outline" 
                                                        style="padding: 0.4rem 0.8rem; font-size: 0.9rem;">
                                                    Reject
                                                </button>
                                            <?php else: ?>
                                                <button onclick="updateStatus(<?php echo $restaurant['id']; ?>, 'approved')" 
                                                        class="btn btn-success" 
                                                        style="padding: 0.4rem 0.8rem; font-size: 0.9rem;">
                                                    Approve
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 2rem;">
                                    <p>No restaurants found matching the filter.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        function applyFilter() {
            const status = document.getElementById('statusFilter').value;
            window.location.href = `manage_restaurants_admin.php?status=${status}`;
        }
        
        async function updateStatus(restaurantId, status) {
            if (!confirm(`Are you sure you want to ${status} this restaurant?`)) {
                return;
            }
            
            try {
                const response = await fetch('../php/update_restaurant_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ restaurant_id: restaurantId, status: status })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    location.reload();
                } else {
                    alert('Failed to update status: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to update restaurant status');
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
