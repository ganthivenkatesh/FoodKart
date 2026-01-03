<?php
require_once '../php/config.php';
requireRole('admin');

$conn = getDBConnection();

// Handle status update
if (isset($_GET['toggle_status'])) {
    $user_id = intval($_GET['toggle_status']);
    $new_status = $_GET['status'] === 'active' ? 'inactive' : 'active';
    
    $update_query = "UPDATE users SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $new_status, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'User status updated successfully!';
    } else {
        $_SESSION['error'] = 'Failed to update user status.';
    }
    
    header('Location: manage_users.php');
    exit();
}

// Handle delete user
if (isset($_GET['delete'])) {
    $user_id = intval($_GET['delete']);
    
    // Don't allow deleting admin users
    $check_query = "SELECT role FROM users WHERE id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if ($user && $user['role'] !== 'admin') {
        $delete_query = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'User deleted successfully!';
        } else {
            $_SESSION['error'] = 'Failed to delete user.';
        }
    } else {
        $_SESSION['error'] = 'Cannot delete admin users.';
    }
    
    header('Location: manage_users.php');
    exit();
}

// Get filter
$role_filter = isset($_GET['role']) ? $_GET['role'] : 'all';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build query
$query = "SELECT * FROM users WHERE 1=1";
if ($role_filter !== 'all') {
    $query .= " AND role = '" . $conn->real_escape_string($role_filter) . "'";
}
if ($status_filter !== 'all') {
    $query .= " AND status = '" . $conn->real_escape_string($status_filter) . "'";
}
$query .= " ORDER BY created_at DESC";

$users = $conn->query($query);

// Get statistics
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$total_customers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'customer'")->fetch_assoc()['count'];
$total_owners = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'restaurant_owner'")->fetch_assoc()['count'];
$active_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - FoodKart</title>
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
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
                <li><a href="manage_users.php" class="active">👥 Users</a></li>
                <li><a href="manage_restaurants_admin.php">🍽️ Restaurants</a></li>
                <li><a href="manage_all_orders.php">📦 All Orders</a></li>
                <li><a href="manage_feedback.php">💬 Feedback</a></li>
                <li><a href="manage_contacts.php">📧 Contact Messages</a></li>
                <li><a href="../php/auth.php?logout=1">🚪 Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <h1 style="margin-bottom: 2rem;">👥 Manage Users</h1>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <a href="manage_users.php?role=all&status=all" class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-value"><?php echo $total_users; ?></div>
                    <div class="stat-label">Total Users</div>
                </a>
                
                <a href="manage_users.php?role=customer&status=all" class="stat-card">
                    <div class="stat-icon">🛍️</div>
                    <div class="stat-value"><?php echo $total_customers; ?></div>
                    <div class="stat-label">Customers</div>
                </a>
                
                <a href="manage_users.php?role=restaurant_owner&status=all" class="stat-card">
                    <div class="stat-icon">🍽️</div>
                    <div class="stat-value"><?php echo $total_owners; ?></div>
                    <div class="stat-label">Restaurant Owners</div>
                </a>
                
                <a href="manage_users.php?role=all&status=active" class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-value"><?php echo $active_users; ?></div>
                    <div class="stat-label">Active Users</div>
                </a>
            </div>
            
            <!-- Filters -->
            <div class="filters">
                <strong>Filters:</strong>
                <div class="filter-group">
                    <label>Role:</label>
                    <select class="form-control" onchange="applyFilters()" id="roleFilter">
                        <option value="all" <?php echo $role_filter === 'all' ? 'selected' : ''; ?>>All Roles</option>
                        <option value="customer" <?php echo $role_filter === 'customer' ? 'selected' : ''; ?>>Customers</option>
                        <option value="restaurant_owner" <?php echo $role_filter === 'restaurant_owner' ? 'selected' : ''; ?>>Restaurant Owners</option>
                        <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admins</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Status:</label>
                    <select class="form-control" onchange="applyFilters()" id="statusFilter">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
            </div>
            
            <!-- Users Table -->
            <div class="section-card">
                <h2 style="margin-bottom: 1.5rem;">All Users</h2>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users->num_rows > 0): ?>
                            <?php while ($user = $users->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge <?php 
                                            echo match($user['role']) {
                                                'admin' => 'badge-danger',
                                                'restaurant_owner' => 'badge-warning',
                                                'customer' => 'badge-info',
                                                default => 'badge-secondary'
                                            };
                                        ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $user['status'] === 'active' ? 'badge-success' : 'badge-secondary'; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <?php if ($user['role'] !== 'admin'): ?>
                                                <a href="?toggle_status=<?php echo $user['id']; ?>&status=<?php echo $user['status']; ?>" 
                                                   class="btn btn-outline" 
                                                   style="padding: 0.4rem 0.8rem; font-size: 0.9rem;">
                                                    <?php echo $user['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                                </a>
                                                <button onclick="deleteUser(<?php echo $user['id']; ?>)" 
                                                        class="btn btn-outline" 
                                                        style="padding: 0.4rem 0.8rem; font-size: 0.9rem; color: var(--danger-color);">
                                                    Delete
                                                </button>
                                            <?php else: ?>
                                                <span style="color: var(--light-text); font-size: 0.9rem;">Protected</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 2rem;">
                                    <p>No users found matching the filters.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        function applyFilters() {
            const role = document.getElementById('roleFilter').value;
            const status = document.getElementById('statusFilter').value;
            window.location.href = `manage_users.php?role=${role}&status=${status}`;
        }
        
        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                window.location.href = 'manage_users.php?delete=' + userId;
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
