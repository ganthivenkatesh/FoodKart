<?php
require_once '../php/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $message = sanitize($_POST['message']);
    
    if (!empty($name) && !empty($email) && !empty($message)) {
        $conn = getDBConnection();
        
        $stmt = $conn->prepare("INSERT INTO contact (name, email, message) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $message);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Thank you! Your message has been sent successfully.";
        } else {
            $_SESSION['error'] = "Failed to send message. Please try again.";
        }
        
        $stmt->close();
        $conn->close();
        
        header('Location: contact.php');
        exit();
    } else {
        $_SESSION['error'] = "All fields are required!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - FoodKart</title>
    <link rel="stylesheet" href="../styles/main.css">
    <style>
        .contact-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            text-align: center;
        }
        
        .contact-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            padding: 3rem 0;
        }
        
        .contact-info {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: var(--shadow);
        }
        
        .contact-form {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: var(--shadow);
        }
        
        .info-item {
            display: flex;
            align-items: start;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .info-icon {
            font-size: 2rem;
            flex-shrink: 0;
        }
        
        .info-content h3 {
            margin-bottom: 0.5rem;
            color: var(--dark-text);
        }
        
        .info-content p {
            color: var(--light-text);
            margin: 0;
        }
        
        @media (max-width: 968px) {
            .contact-container {
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
    <div class="contact-header">
        <div class="container">
            <h1>📞 Contact Us</h1>
            <p>We'd love to hear from you!</p>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="container contact-container">
        <!-- Contact Information -->
        <div class="contact-info">
            <h2 style="margin-bottom: 2rem;">Get in Touch</h2>
            
            <div class="info-item">
                <div class="info-icon">📍</div>
                <div class="info-content">
                    <h3>Address</h3>
                    <p>Mohan Babu University, Sri Sainath Nagar<br>Tirupathi, Andhra Pradesh 517102</p>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-icon">📧</div>
                <div class="info-content">
                    <h3>Email</h3>
                    <p>support@foodkart.com<br>info@foodkart.com</p>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-icon">📞</div>
                <div class="info-content">
                    <h3>Phone</h3>
                    <p>+91 63030-92763</p>
                </div>
            </div>
            
            <div class="info-item">
                <div class="info-icon">🕒</div>
                <div class="info-content">
                    <h3>Working Hours</h3>
                    <p>Monday - Sunday<br>9:00 AM - 11:00 PM</p>
                </div>
            </div>
        </div>
        
        <!-- Contact Form -->
        <div class="contact-form">
            <h2 style="margin-bottom: 1.5rem;">Send us a Message</h2>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo $_SESSION['success']; 
                    unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>
            
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
                    <label for="name">Your Name</label>
                    <input type="text" id="name" name="name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Your Email</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="message">Your Message</label>
                    <textarea id="message" name="message" class="form-control" rows="6" required></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Send Message
                </button>
            </form>
        </div>
    </div>
    
    <?php if (isLoggedIn()): ?>
    <script src="../scripts/cart.js"></script>
    <script>updateCartCount();</script>
    <?php endif; ?>
</body>
</html>
