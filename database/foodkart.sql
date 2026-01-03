-- FoodKart Database Schema
-- Drop database if exists and create new
DROP DATABASE IF EXISTS foodkart;
CREATE DATABASE foodkart;
USE foodkart;

-- Users table (Admin, Restaurant Owner, Customer)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'restaurant_owner', 'customer') NOT NULL DEFAULT 'customer',
    phone VARCHAR(15),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active'
);

-- Restaurants table
CREATE TABLE restaurants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    owner_id INT NOT NULL,
    cuisine VARCHAR(100),
    location VARCHAR(255),
    phone VARCHAR(15),
    image VARCHAR(255),
    description TEXT,
    rating DECIMAL(2,1) DEFAULT 0.0,
    is_open BOOLEAN DEFAULT TRUE,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Menu items table
CREATE TABLE menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    category ENUM('veg', 'non-veg', 'combo') NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    discount DECIMAL(5,2) DEFAULT 0.00,
    description TEXT,
    image VARCHAR(255),
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
);

-- Orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    restaurant_id INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    order_status ENUM('placed', 'preparing', 'out_for_delivery', 'delivered', 'cancelled') DEFAULT 'placed',
    delivery_address TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
);

-- Order items table
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES menu_items(id) ON DELETE CASCADE
);

-- Offers table
CREATE TABLE offers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    restaurant_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    discount_percent DECIMAL(5,2) NOT NULL,
    description TEXT,
    valid_from DATE,
    valid_until DATE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
);

-- Payments table
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    method ENUM('razorpay', 'paypal', 'cod') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    transaction_id VARCHAR(100),
    status ENUM('pending', 'success', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Feedback table
CREATE TABLE feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    restaurant_id INT NOT NULL,
    order_id INT,
    message TEXT,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
);

-- Contact table
CREATE TABLE contact (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('pending', 'resolved') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user (password: admin123)
INSERT INTO users (name, email, password, role) VALUES 
('Admin', 'admin@foodkart.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert sample restaurant owners (password: password123)
INSERT INTO users (name, email, password, role, phone) VALUES 
('John Doe', 'john@restaurant.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'restaurant_owner', '9876543210'),
('Jane Smith', 'jane@restaurant.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'restaurant_owner', '9876543211');

-- Insert sample customers (password: password123)
INSERT INTO users (name, email, password, role, phone, address) VALUES 
('Alice Johnson', 'alice@customer.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', '9876543212', '123 Main St, City'),
('Bob Williams', 'bob@customer.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', '9876543213', '456 Oak Ave, City');

-- Insert sample restaurants
INSERT INTO restaurants (name, owner_id, cuisine, location, phone, image, description, status, rating) VALUES 
('Spice Garden', 2, 'Indian', 'Mohan Babu University', '63030 92763', 'spice-garden-restaurant.jpg', 'Authentic Indian cuisine with a modern twist', 'approved', 4.5),
('Pizza Palace', 3, 'Italian', 'Mohan Babu University', '63030 92763', 'pizza-palace-restaurant.jpg', 'Best pizzas in town with fresh ingredients', 'approved', 4.3);

-- Insert sample menu items
INSERT INTO menu_items (restaurant_id, name, category, price, discount, description, image, is_available) VALUES 
-- Spice Garden items
(1, 'Paneer Tikka', 'veg', 250.00, 10.00, 'Grilled cottage cheese with spices', 'paneer-tikka.jpg', TRUE),
(1, 'Veg Biryani', 'veg', 180.00, 0.00, 'Aromatic rice with mixed vegetables', 'veg-biryani.jpg', TRUE),
(1, 'Chicken Tikka Masala', 'non-veg', 320.00, 15.00, 'Tender chicken in creamy tomato sauce', 'chicken-tikka-masala.jpg', TRUE),
(1, 'Mutton Biryani', 'non-veg', 380.00, 0.00, 'Fragrant rice with tender mutton', 'mutton-biryani.jpg', TRUE),
(1, 'Family Combo', 'combo', 899.00, 20.00, '2 Veg + 2 Non-veg + Rice + Dessert', 'family-combo.jpg', TRUE),
-- Pizza Palace items
(2, 'Margherita Pizza', 'veg', 299.00, 0.00, 'Classic pizza with cheese and basil', 'margherita-pizza.jpg', TRUE),
(2, 'Veggie Supreme', 'veg', 349.00, 10.00, 'Loaded with fresh vegetables', 'veggie-supreme.jpg', TRUE),
(2, 'Chicken BBQ Pizza', 'non-veg', 399.00, 0.00, 'BBQ chicken with special sauce', 'chicken-bbq-pizza.jpg', TRUE),
(2, 'Pepperoni Delight', 'non-veg', 429.00, 15.00, 'Loaded with pepperoni', 'pepperoni-delight.jpg', TRUE),
(2, 'Party Combo', 'combo', 1199.00, 25.00, '2 Large Pizzas + Garlic Bread + Coke', 'party-combo.jpg', TRUE);

-- Insert sample offers
INSERT INTO offers (restaurant_id, title, discount_percent, description, valid_from, valid_until) VALUES 
(1, 'Weekend Special', 20.00, 'Get 20% off on all orders above ₹500', '2025-10-01', '2025-10-31'),
(2, 'Pizza Fest', 25.00, 'Flat 25% off on combo orders', '2025-10-01', '2025-10-31');
