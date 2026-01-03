<?php
require_once 'php/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: dashboards/admin_dashboard.php');
            break;
        case 'restaurant_owner':
            header('Location: dashboards/restaurant_dashboard.php');
            break;
        case 'delivery_agent':
            header('Location: dashboards/delivery_dashboard.php');
            break;
        case 'customer':
            header('Location: pages/user_home.php');
            break;
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login / Signup - FoodKart</title>
    <link rel="stylesheet" href="styles/main.css">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        
        .auth-box {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 450px;
            width: 100%;
            overflow: hidden;
        }
        
        .auth-tabs {
            display: flex;
            background-color: #f8f9fa;
        }
        
        .auth-tab {
            flex: 1;
            padding: 1.2rem;
            text-align: center;
            cursor: pointer;
            font-weight: 600;
            color: #7f8c8d;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
        }
        
        .auth-tab.active {
            color: var(--primary-color);
            background-color: white;
            border-bottom-color: var(--primary-color);
        }
        
        .auth-content {
            padding: 2rem;
        }
        
        .auth-form {
            display: none;
        }
        
        .auth-form.active {
            display: block;
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .auth-header h2 {
            color: var(--dark-text);
            margin-bottom: 0.5rem;
        }
        
        .auth-header p {
            color: var(--light-text);
            font-size: 0.95rem;
        }
        
        .role-selector {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.8rem;
            margin-bottom: 1.5rem;
        }
        
        .role-option {
            position: relative;
        }
        
        .role-option input[type="radio"] {
            display: none;
        }
        
        .role-label {
            display: block;
            padding: 0.8rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .role-option input[type="radio"]:checked + .role-label {
            border-color: var(--primary-color);
            background-color: #ffe5e5;
            color: var(--primary-color);
        }
        
        .role-label:hover {
            border-color: var(--primary-color);
        }
        
        .form-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }
        
        .form-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .logo-header {
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .logo-header h1 {
            color: var(--primary-color);
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="logo-header">
                <h1>🍴 FoodKart</h1>
            </div>
            
            <div class="auth-tabs">
                <div class="auth-tab active" onclick="switchTab('login')">Login</div>
                <div class="auth-tab" onclick="switchTab('signup')">Sign Up</div>
            </div>
            
            <div class="auth-content">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error">
                        <?php 
                        echo $_SESSION['error']; 
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php 
                        echo $_SESSION['success']; 
                        unset($_SESSION['success']);
                        ?>
                    </div>
                <?php endif; ?>
                
                <!-- Login Form -->
                <form id="loginForm" class="auth-form active" action="php/auth.php" method="POST">
                    <div class="auth-header">
                        <h2>Welcome Back!</h2>
                        <p>Login to continue to FoodKart</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="login-email">Email Address</label>
                        <input type="email" id="login-email" name="email" class="form-control" placeholder="Enter your email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="login-password">Password</label>
                        <input type="password" id="login-password" name="password" class="form-control" placeholder="Enter your password" required>
                    </div>
                    
                    <button type="submit" name="login" class="btn btn-primary" style="width: 100%;">Login</button>
                    
                    <div class="form-footer">
                        <p>Don't have an account? <a href="#" onclick="switchTab('signup'); return false;">Sign Up</a></p>
                    </div>
                </form>
                
                <!-- Signup Form -->
                <form id="signupForm" class="auth-form" action="php/auth.php" method="POST">
                    <div class="auth-header">
                        <h2>Create Account</h2>
                        <p>Join FoodKart today!</p>
                    </div>
                    
                    <div class="form-group">
                        <label>Select Role</label>
                        <div class="role-selector">
                            <div class="role-option">
                                <input type="radio" id="role-customer" name="role" value="customer" checked>
                                <label for="role-customer" class="role-label">👤 Customer</label>
                            </div>
                            <div class="role-option">
                                <input type="radio" id="role-owner" name="role" value="restaurant_owner">
                                <label for="role-owner" class="role-label">🍴 Owner</label>
                            </div>
                            <div class="role-option">
                                <input type="radio" id="role-delivery" name="role" value="delivery_agent">
                                <label for="role-delivery" class="role-label">🏍️ Delivery</label>
                            </div>
                            <div class="role-option">
                                <input type="radio" id="role-admin" name="role" value="admin">
                                <label for="role-admin" class="role-label">👨‍💼 Admin</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="signup-name">Full Name</label>
                        <input type="text" id="signup-name" name="name" class="form-control" placeholder="Enter your full name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="signup-email">Email Address</label>
                        <input type="email" id="signup-email" name="email" class="form-control" placeholder="Enter your email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="signup-phone">Phone Number</label>
                        <input type="tel" id="signup-phone" name="phone" class="form-control" placeholder="Enter your phone number" required>
                    </div>
                    
                    <div class="form-group" id="address-group">
                        <label for="signup-address">Address (Optional for Customer)</label>
                        <textarea id="signup-address" name="address" class="form-control" placeholder="Enter your address" rows="2"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="signup-password">Password</label>
                        <input type="password" id="signup-password" name="password" class="form-control" placeholder="Create a password (min 6 characters)" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="signup-confirm-password">Confirm Password</label>
                        <input type="password" id="signup-confirm-password" name="confirm_password" class="form-control" placeholder="Confirm your password" required>
                    </div>
                    
                    <button type="submit" name="signup" class="btn btn-primary" style="width: 100%;">Sign Up</button>
                    
                    <div class="form-footer">
                        <p>Already have an account? <a href="#" onclick="switchTab('login'); return false;">Login</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function switchTab(tab) {
            // Update tabs
            const tabs = document.querySelectorAll('.auth-tab');
            tabs.forEach(t => t.classList.remove('active'));
            
            // Update forms
            const forms = document.querySelectorAll('.auth-form');
            forms.forEach(f => f.classList.remove('active'));
            
            if (tab === 'login') {
                tabs[0].classList.add('active');
                document.getElementById('loginForm').classList.add('active');
            } else {
                tabs[1].classList.add('active');
                document.getElementById('signupForm').classList.add('active');
            }
        }
    </script>
</body>
</html>
