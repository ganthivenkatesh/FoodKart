<?php
require_once '../php/config.php';
requireRole('restaurant_owner');

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Check if restaurant already exists
$check_query = "SELECT id FROM restaurants WHERE owner_id = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();

if ($existing) {
    header('Location: restaurant_dashboard.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $cuisine = sanitize($_POST['cuisine']);
    $location = sanitize($_POST['location']);
    $phone = sanitize($_POST['phone']);
    $description = sanitize($_POST['description']);
    
    // Handle image upload
    $image = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/images/';
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $new_filename = 'restaurant_' . $user_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image = 'assets/images/' . $new_filename;
            }
        }
    }
    
    // Insert restaurant
    $insert_query = "INSERT INTO restaurants (owner_id, name, cuisine, location, phone, description, image, status) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("issssss", $user_id, $name, $cuisine, $location, $phone, $description, $image);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Restaurant registration submitted! Waiting for admin approval.';
        header('Location: restaurant_dashboard.php');
        exit();
    } else {
        $_SESSION['error'] = 'Failed to register restaurant. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Your Restaurant - FoodKart</title>
    <link rel="stylesheet" href="../styles/main.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .registration-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            padding: 3rem;
        }
        
        .registration-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .registration-header h1 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .registration-header p {
            color: var(--light-text);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark-text);
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }
        
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }
        
        .file-input-wrapper input[type=file] {
            position: absolute;
            left: -9999px;
        }
        
        .file-input-label {
            display: block;
            padding: 0.75rem;
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .file-input-label:hover {
            background: #e9ecef;
            border-color: var(--primary-color);
        }
        
        .file-name {
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: var(--light-text);
        }
        
        .submit-btn {
            width: 100%;
            padding: 1rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .submit-btn:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.3);
        }
        
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 1rem;
            margin-bottom: 2rem;
            border-radius: 4px;
        }
        
        .info-box p {
            margin: 0;
            color: #1976d2;
            font-size: 0.9rem;
        }
        
        .logout-link {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .logout-link a {
            color: var(--light-text);
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .logout-link a:hover {
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="registration-container">
        <div class="registration-header">
            <h1>🍴 Register Your Restaurant</h1>
            <p>Join FoodKart and start receiving orders!</p>
        </div>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <div class="info-box">
            <p>📋 Your restaurant registration will be reviewed by our admin team. You'll be notified once approved!</p>
        </div>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Restaurant Name *</label>
                <input type="text" id="name" name="name" class="form-control" required 
                       placeholder="e.g., Spice Garden">
            </div>
            
            <div class="form-group">
                <label for="cuisine">Cuisine Type *</label>
                <input type="text" id="cuisine" name="cuisine" class="form-control" required 
                       placeholder="e.g., Indian, Chinese, Italian">
            </div>
            
            <div class="form-group">
                <label for="location">Location *</label>
                <input type="text" id="location" name="location" class="form-control" required 
                       placeholder="e.g., MG Road, Bangalore">
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number *</label>
                <input type="tel" id="phone" name="phone" class="form-control" required 
                       placeholder="e.g., +91 9876543210">
            </div>
            
            <div class="form-group">
                <label for="description">Description *</label>
                <textarea id="description" name="description" class="form-control" required 
                          placeholder="Tell customers about your restaurant..."></textarea>
            </div>
            
            <div class="form-group">
                <label for="image">Restaurant Image (Optional)</label>
                <div class="file-input-wrapper">
                    <input type="file" id="image" name="image" accept="image/*" onchange="updateFileName(this)">
                    <label for="image" class="file-input-label">
                        📷 Click to upload restaurant image
                    </label>
                    <div class="file-name" id="fileName"></div>
                </div>
            </div>
            
            <button type="submit" class="submit-btn">
                Submit for Approval
            </button>
        </form>
        
        <div class="logout-link">
            <a href="../php/auth.php?logout=1">← Logout</a>
        </div>
    </div>
    
    <script>
        function updateFileName(input) {
            const fileName = document.getElementById('fileName');
            if (input.files && input.files[0]) {
                fileName.textContent = '✓ ' + input.files[0].name;
            } else {
                fileName.textContent = '';
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
