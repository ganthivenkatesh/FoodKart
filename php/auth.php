<?php
require_once 'config.php';

// Handle Login
if (isset($_POST['login'])) {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id, name, email, password, role, status FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if ($user['status'] === 'inactive') {
            $_SESSION['error'] = "Your account has been deactivated. Please contact admin.";
            header('Location: ' . BASE_URL . 'login_signup.php');
            exit();
        }
        
        if (verifyPassword($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            // Redirect based on role
            switch ($user['role']) {
                case 'admin':
                    header('Location: ' . BASE_URL . 'dashboards/admin_dashboard.php');
                    break;
                case 'restaurant_owner':
                    header('Location: ' . BASE_URL . 'dashboards/restaurant_dashboard.php');
                    break;
                case 'delivery_agent':
                    header('Location: ' . BASE_URL . 'dashboards/delivery_dashboard.php');
                    break;
                case 'customer':
                    header('Location: ' . BASE_URL . 'index.php');
                    break;
                default:
                    header('Location: ' . BASE_URL . 'index.php');
            }
            exit();
        } else {
            $_SESSION['error'] = "Invalid email or password!";
            header('Location: ' . BASE_URL . 'login_signup.php');
            exit();
        }
    } else {
        $_SESSION['error'] = "Invalid email or password!";
        header('Location: ' . BASE_URL . 'login_signup.php');
        exit();
    }
    
    $stmt->close();
    $conn->close();
}

// Handle Signup
if (isset($_POST['signup'])) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = sanitize($_POST['role']);
    $phone = sanitize($_POST['phone']);
    $address = isset($_POST['address']) ? sanitize($_POST['address']) : '';
    
    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $_SESSION['error'] = "All fields are required!";
        header('Location: ' . BASE_URL . 'login_signup.php');
        exit();
    }
    
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match!";
        header('Location: ' . BASE_URL . 'login_signup.php');
        exit();
    }
    
    if (strlen($password) < 6) {
        $_SESSION['error'] = "Password must be at least 6 characters!";
        header('Location: ' . BASE_URL . 'login_signup.php');
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format!";
        header('Location: ' . BASE_URL . 'login_signup.php');
        exit();
    }
    
    $conn = getDBConnection();
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['error'] = "Email already registered!";
        header('Location: ' . BASE_URL . 'login_signup.php');
        exit();
    }
    
    // Hash password and insert user
    $hashed_password = hashPassword($password);
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, phone, address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $name, $email, $hashed_password, $role, $phone, $address);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Registration successful! Please login.";
        header('Location: ' . BASE_URL . 'login_signup.php');
        exit();
    } else {
        $_SESSION['error'] = "Registration failed! Please try again.";
        header('Location: ' . BASE_URL . 'login_signup.php');
        exit();
    }
    
    $stmt->close();
    $conn->close();
}

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}
?>
