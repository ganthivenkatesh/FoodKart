<?php
require_once '../php/config.php';
requireRole('customer');

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Verify order belongs to user and is delivered
$order_query = "SELECT o.*, r.name as restaurant_name, r.id as restaurant_id 
                FROM orders o 
                JOIN restaurants r ON o.restaurant_id = r.id 
                WHERE o.id = ? AND o.user_id = ? AND o.order_status = 'delivered'";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: user_home.php');
    exit();
}

$order = $result->fetch_assoc();

// Check if feedback already exists
$check_query = "SELECT id FROM feedback WHERE order_id = ? AND user_id = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$existing_feedback = $stmt->get_result();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = intval($_POST['rating']);
    $message = sanitize($_POST['message']);
    
    if ($rating >= 1 && $rating <= 5) {
        if ($existing_feedback->num_rows > 0) {
            // Update existing feedback
            $update_query = "UPDATE feedback SET rating = ?, message = ? WHERE order_id = ? AND user_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("isii", $rating, $message, $order_id, $user_id);
        } else {
            // Insert new feedback
            $insert_query = "INSERT INTO feedback (user_id, restaurant_id, order_id, rating, message) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("iiiis", $user_id, $order['restaurant_id'], $order_id, $rating, $message);
        }
        
        if ($stmt->execute()) {
            // Update restaurant rating
            $rating_query = "UPDATE restaurants SET rating = (
                SELECT AVG(rating) FROM feedback WHERE restaurant_id = ?
            ) WHERE id = ?";
            $stmt = $conn->prepare($rating_query);
            $stmt->bind_param("ii", $order['restaurant_id'], $order['restaurant_id']);
            $stmt->execute();
            
            $_SESSION['success'] = "Thank you for your feedback!";
            header('Location: user_home.php');
            exit();
        } else {
            $_SESSION['error'] = "Failed to submit feedback. Please try again.";
        }
    }
}

$has_feedback = $existing_feedback->num_rows > 0;
if ($has_feedback) {
    $feedback_data = $existing_feedback->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Order - FoodKart</title>
    <link rel="stylesheet" href="../styles/main.css">
    <style>
        .feedback-container {
            max-width: 600px;
            margin: 3rem auto;
            padding: 0 20px;
        }
        
        .feedback-card {
            background: white;
            border-radius: 15px;
            padding: 2.5rem;
            box-shadow: var(--shadow);
        }
        
        .order-info {
            background-color: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        
        .rating-input {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin: 2rem 0;
        }
        
        .star {
            font-size: 3rem;
            cursor: pointer;
            transition: all 0.3s;
            filter: grayscale(100%);
        }
        
        .star.active {
            filter: grayscale(0%);
            transform: scale(1.2);
        }
        
        .star:hover {
            transform: scale(1.3);
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
                <li><a href="menu.php">Menu</a></li>
                <li><a href="user_home.php">Dashboard</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="feedback-container">
        <div class="feedback-card">
            <h2 style="text-align: center; margin-bottom: 1rem;">
                ⭐ Rate Your Experience
            </h2>
            <p style="text-align: center; color: var(--light-text); margin-bottom: 2rem;">
                Help us improve by sharing your feedback
            </p>
            
            <div class="order-info">
                <h3>Order #<?php echo $order_id; ?></h3>
                <p style="margin: 0.5rem 0; color: var(--light-text);">
                    <strong>Restaurant:</strong> <?php echo htmlspecialchars($order['restaurant_name']); ?>
                </p>
                <p style="margin: 0; color: var(--light-text);">
                    <strong>Order Date:</strong> <?php echo date('d M Y', strtotime($order['created_at'])); ?>
                </p>
            </div>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?php 
                    echo $_SESSION['error']; 
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label style="text-align: center; display: block; margin-bottom: 1rem;">
                        How would you rate your order?
                    </label>
                    <div class="rating-input">
                        <span class="star" data-rating="1" onclick="setRating(1)">⭐</span>
                        <span class="star" data-rating="2" onclick="setRating(2)">⭐</span>
                        <span class="star" data-rating="3" onclick="setRating(3)">⭐</span>
                        <span class="star" data-rating="4" onclick="setRating(4)">⭐</span>
                        <span class="star" data-rating="5" onclick="setRating(5)">⭐</span>
                    </div>
                    <input type="hidden" name="rating" id="ratingValue" value="5" required>
                </div>
                
                <div class="form-group">
                    <label for="message">Your Feedback (Optional)</label>
                    <textarea id="message" name="message" class="form-control" rows="5" 
                              placeholder="Tell us about your experience..."><?php echo $has_feedback ? htmlspecialchars($feedback_data['message'] ?? '') : ''; ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <?php echo $has_feedback ? 'Update Feedback' : 'Submit Feedback'; ?>
                </button>
                
                <a href="user_home.php" class="btn btn-outline mt-2" style="width: 100%; text-align: center;">
                    Skip for Now
                </a>
            </form>
        </div>
    </div>
    
    <script>
        function setRating(rating) {
            document.getElementById('ratingValue').value = rating;
            
            const stars = document.querySelectorAll('.star');
            stars.forEach((star, index) => {
                if (index < rating) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
        }
        
        // Set default rating to 5
        setRating(5);
    </script>
</body>
</html>
<?php $conn->close(); ?>
