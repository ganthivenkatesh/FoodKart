<?php
require_once '../php/config.php';
requireRole('admin');

$conn = getDBConnection();

// Handle status update
if (isset($_GET['mark_resolved'])) {
    $contact_id = intval($_GET['mark_resolved']);
    
    $update_query = "UPDATE contact SET status = 'resolved' WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("i", $contact_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Contact message marked as resolved!';
    } else {
        $_SESSION['error'] = 'Failed to update status.';
    }
    
    header('Location: manage_contacts.php');
    exit();
}

// Handle delete
if (isset($_GET['delete'])) {
    $contact_id = intval($_GET['delete']);
    
    $delete_query = "DELETE FROM contact WHERE id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $contact_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Contact message deleted successfully!';
    } else {
        $_SESSION['error'] = 'Failed to delete message.';
    }
    
    header('Location: manage_contacts.php');
    exit();
}

// Get filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build query
$query = "SELECT * FROM contact WHERE 1=1";
if ($status_filter !== 'all') {
    $query .= " AND status = '" . $conn->real_escape_string($status_filter) . "'";
}
$query .= " ORDER BY created_at DESC";

$contacts = $conn->query($query);

// Get statistics
$total_contacts = $conn->query("SELECT COUNT(*) as count FROM contact")->fetch_assoc()['count'];
$pending_contacts = $conn->query("SELECT COUNT(*) as count FROM contact WHERE status = 'pending'")->fetch_assoc()['count'];
$resolved_contacts = $conn->query("SELECT COUNT(*) as count FROM contact WHERE status = 'resolved'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Contact Messages - FoodKart</title>
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
        
        .contact-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
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
                <li><a href="manage_feedback.php">💬 Feedback</a></li>
                <li><a href="manage_contacts.php" class="active">📧 Contact Messages</a></li>
                <li><a href="../php/auth.php?logout=1">🚪 Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <h1 style="margin-bottom: 2rem;">📧 Manage Contact Messages</h1>
            
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
                <a href="manage_contacts.php?status=all" class="stat-card">
                    <div class="stat-icon">📧</div>
                    <div class="stat-value"><?php echo $total_contacts; ?></div>
                    <div class="stat-label">Total Messages</div>
                </a>
                
                <a href="manage_contacts.php?status=pending" class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-value"><?php echo $pending_contacts; ?></div>
                    <div class="stat-label">Pending</div>
                </a>
                
                <a href="manage_contacts.php?status=resolved" class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-value"><?php echo $resolved_contacts; ?></div>
                    <div class="stat-label">Resolved</div>
                </a>
            </div>
            
            <!-- Filters -->
            <div class="filters">
                <strong>Filter by Status:</strong>
                <select class="form-control" onchange="applyFilter()" id="statusFilter">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                </select>
            </div>
            
            <!-- Contact Messages -->
            <div class="section-card">
                <h2 style="margin-bottom: 1.5rem;">All Contact Messages</h2>
                
                <?php if ($contacts->num_rows > 0): ?>
                    <?php while ($contact = $contacts->fetch_assoc()): ?>
                        <div class="contact-card">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                                <div>
                                    <h3 style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($contact['name']); ?></h3>
                                    <p style="color: var(--light-text); font-size: 0.9rem; margin: 0;">
                                        📧 <?php echo htmlspecialchars($contact['email']); ?>
                                    </p>
                                    <p style="color: var(--light-text); font-size: 0.9rem; margin: 0.25rem 0;">
                                        📅 <?php echo date('d M Y, H:i', strtotime($contact['created_at'])); ?>
                                    </p>
                                </div>
                                <div>
                                    <span class="badge <?php echo $contact['status'] === 'resolved' ? 'badge-success' : 'badge-warning'; ?>">
                                        <?php echo ucfirst($contact['status']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <p style="background: #f8f9fa; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                                <?php echo nl2br(htmlspecialchars($contact['message'])); ?>
                            </p>
                            
                            <div style="display: flex; gap: 0.5rem;">
                                <?php if ($contact['status'] === 'pending'): ?>
                                    <button onclick="markResolved(<?php echo $contact['id']; ?>)" 
                                            class="btn btn-success" 
                                            style="padding: 0.4rem 0.8rem; font-size: 0.9rem;">
                                        Mark as Resolved
                                    </button>
                                <?php endif; ?>
                                <button onclick="deleteMessage(<?php echo $contact['id']; ?>)" 
                                        class="btn btn-outline" 
                                        style="padding: 0.4rem 0.8rem; font-size: 0.9rem; color: var(--danger-color);">
                                    Delete
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 4rem;">
                        <h3>No contact messages</h3>
                        <p>Contact messages will appear here.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function applyFilter() {
            const status = document.getElementById('statusFilter').value;
            window.location.href = `manage_contacts.php?status=${status}`;
        }
        
        function markResolved(contactId) {
            if (confirm('Mark this message as resolved?')) {
                window.location.href = 'manage_contacts.php?mark_resolved=' + contactId;
            }
        }
        
        function deleteMessage(contactId) {
            if (confirm('Are you sure you want to delete this message?')) {
                window.location.href = 'manage_contacts.php?delete=' + contactId;
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
