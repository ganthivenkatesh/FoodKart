<?php
require_once '../php/config.php';

// Check if user is logged in and is a delivery agent
if (!isLoggedIn() || $_SESSION['role'] !== 'delivery_agent') {
    header('Location: ' . BASE_URL . 'login_signup.php');
    exit();
}

$conn = getDBConnection();
$agent_id = $_SESSION['user_id'];

// Get delivery agent details
$table_check = $conn->query("SHOW TABLES LIKE 'delivery_agents'");
if ($table_check->num_rows > 0) {
    $stmt = $conn->prepare("
        SELECT u.*, da.license_number, da.total_deliveries, da.rating, da.is_online, da.current_latitude, da.current_longitude
        FROM users u
        LEFT JOIN delivery_agents da ON u.id = da.user_id
        WHERE u.id = ?
    ");
    $stmt->bind_param("i", $agent_id);
    $stmt->execute();
    $agent = $stmt->get_result()->fetch_assoc();
} else {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $agent_id);
    $stmt->execute();
    $agent = $stmt->get_result()->fetch_assoc();
    $agent['license_number'] = 'N/A';
    $agent['total_deliveries'] = 0;
    $agent['rating'] = 0;
    $agent['is_online'] = 1;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - FoodKart</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>styles/main.css">
    <style>
        .profile-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 2rem;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #eee;
        }

        .profile-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .profile-item {
            display: flex;
            flex-direction: column;
        }

        .profile-label {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .profile-value {
            font-size: 1rem;
            color: #333;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .btn-edit {
            background: #667eea;
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-edit:hover {
            background: #5568d3;
        }

        .btn-back {
            background: #6c757d;
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }

        .btn-back:hover {
            background: #5a6268;
        }

        .edit-form input, .edit-form textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .edit-form textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .btn-save {
            background: #28a745;
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-save:hover {
            background: #218838;
        }

        .btn-cancel {
            background: #dc3545;
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-cancel:hover {
            background: #c82333;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="profile-container">
        <div class="profile-header">
            <h1>👤 My Profile</h1>
            <p style="color: #666;">Manage your delivery agent profile</p>
        </div>

        <!-- Profile View Mode -->
        <div id="profile-view">
            <div class="profile-card">
                <div class="profile-grid">
                    <div class="profile-item">
                        <span class="profile-label">👤 Full Name</span>
                        <span class="profile-value"><?php echo htmlspecialchars($agent['name']); ?></span>
                    </div>
                    <div class="profile-item">
                        <span class="profile-label">📧 Email</span>
                        <span class="profile-value"><?php echo htmlspecialchars($agent['email']); ?></span>
                    </div>
                    <div class="profile-item">
                        <span class="profile-label">📱 Phone</span>
                        <span class="profile-value"><?php echo htmlspecialchars($agent['phone'] ?? 'Not provided'); ?></span>
                    </div>
                    <div class="profile-item">
                        <span class="profile-label">📍 Address</span>
                        <span class="profile-value"><?php echo htmlspecialchars($agent['address'] ?? 'Not provided'); ?></span>
                    </div>
                    <div class="profile-item">
                        <span class="profile-label">🎯 Total Deliveries</span>
                        <span class="profile-value"><?php echo $agent['total_deliveries']; ?></span>
                    </div>
                    <div class="profile-item">
                        <span class="profile-label">⭐ Rating</span>
                        <span class="profile-value"><?php echo number_format($agent['rating'], 1); ?> / 5.0</span>
                    </div>
                    <?php if (isset($agent['license_number']) && $agent['license_number'] && $agent['license_number'] !== 'N/A'): ?>
                    <div class="profile-item">
                        <span class="profile-label">🪪 License Number</span>
                        <span class="profile-value"><?php echo htmlspecialchars($agent['license_number']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div style="text-align: center;">
                    <a href="<?php echo BASE_URL; ?>dashboards/delivery_dashboard.php" class="btn-back">← Back to Dashboard</a>
                    <button class="btn-edit" onclick="showEditForm()">✏️ Edit Profile</button>
                </div>
            </div>
        </div>

        <!-- Profile Edit Mode -->
        <div id="profile-edit" style="display: none;">
            <div class="profile-card">
                <form class="edit-form" onsubmit="updateProfile(event)">
                    <div class="profile-grid">
                        <div class="profile-item">
                            <label class="profile-label">👤 Full Name</label>
                            <input type="text" id="edit_name" value="<?php echo htmlspecialchars($agent['name']); ?>" required>
                        </div>
                        
                        <div class="profile-item">
                            <label class="profile-label">📧 Email</label>
                            <input type="email" id="edit_email" value="<?php echo htmlspecialchars($agent['email']); ?>" required>
                        </div>
                        
                        <div class="profile-item">
                            <label class="profile-label">📱 Phone</label>
                            <input type="tel" id="edit_phone" value="<?php echo htmlspecialchars($agent['phone'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="profile-item">
                            <label class="profile-label">📍 Address</label>
                            <textarea id="edit_address" required><?php echo htmlspecialchars($agent['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="profile-item" style="grid-column: 1 / -1;">
                            <label class="profile-label">🔒 New Password (leave blank to keep current)</label>
                            <input type="password" id="edit_password" placeholder="Enter new password (optional)">
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-save">💾 Save Changes</button>
                        <button type="button" class="btn-cancel" onclick="cancelEdit()">❌ Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showEditForm() {
            document.getElementById('profile-view').style.display = 'none';
            document.getElementById('profile-edit').style.display = 'block';
        }

        function cancelEdit() {
            document.getElementById('profile-view').style.display = 'block';
            document.getElementById('profile-edit').style.display = 'none';
        }

        function updateProfile(event) {
            event.preventDefault();
            
            const name = document.getElementById('edit_name').value;
            const email = document.getElementById('edit_email').value;
            const phone = document.getElementById('edit_phone').value;
            const address = document.getElementById('edit_address').value;
            const password = document.getElementById('edit_password').value;

            fetch('<?php echo BASE_URL; ?>php/update_profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    name: name,
                    email: email,
                    phone: phone,
                    address: address,
                    password: password
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Profile updated successfully!');
                    location.reload();
                } else {
                    alert(data.message || 'Failed to update profile');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating profile');
            });
        }
    </script>
</body>
</html>
