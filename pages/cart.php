<?php
require_once '../php/config.php';
requireRole('customer');

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

// Get user address
$user_query = "SELECT address FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - FoodKart</title>
    <link rel="stylesheet" href="../styles/main.css">
    <style>
        .cart-container {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
            padding: 2rem 0;
        }
        
        .cart-items {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: var(--shadow);
        }
        
        .cart-item {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .item-details {
            flex: 1;
        }

        .item-image {
            width: 96px;
            height: 96px;
            object-fit: cover;
            border-radius: 8px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            flex-shrink: 0;
        }

        .item-image-placeholder {
            width: 96px;
            height: 96px;
            border-radius: 8px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            flex-shrink: 0;
        }
        
        .item-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 1rem;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .qty-btn {
            width: 32px;
            height: 32px;
            border: 1px solid var(--primary-color);
            background: white;
            color: var(--primary-color);
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .qty-btn:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .order-summary {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: var(--shadow);
            position: sticky;
            top: 100px;
            height: fit-content;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        
        .summary-total {
            border-top: 2px solid #eee;
            padding-top: 1rem;
            margin-top: 1rem;
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .empty-cart {
            text-align: center;
            padding: 4rem 2rem;
        }
        
        @media (max-width: 968px) {
            .cart-container {
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
                <li><a href="cart.php">🛒 Cart <span id="cartCount" class="badge badge-danger">0</span></a></li>
                <li><a href="user_home.php">Dashboard</a></li>
                <li><a href="../php/auth.php?logout=1" class="btn btn-outline">Logout</a></li>
            </ul>
        </div>
    </nav>
    
    <div class="container cart-container">
        <!-- Cart Items -->
        <div class="cart-items">
            <h2>🛒 Shopping Cart</h2>
            <div id="cartItemsContainer">
                <!-- Cart items will be loaded here by JavaScript -->
            </div>
        </div>
        
        <!-- Order Summary -->
        <div class="order-summary">
            <h3>Order Summary</h3>
            
            <div class="summary-row">
                <span>Subtotal:</span>
                <span id="subtotal">₹0.00</span>
            </div>
            
            <div class="summary-row">
                <span>Delivery Fee:</span>
                <span id="deliveryFee">₹40.00</span>
            </div>
            
            <div class="summary-row">
                <span>GST (5%):</span>
                <span id="gst">₹0.00</span>
            </div>
            
            <div class="summary-row summary-total">
                <span>Total:</span>
                <span id="total">₹0.00</span>
            </div>
            
            <div class="form-group mt-3">
                <label for="deliveryAddress">Delivery Address</label>
                <textarea id="deliveryAddress" class="form-control" rows="3" required><?php echo htmlspecialchars($user_data['address'] ?? ''); ?></textarea>
            </div>
            
            <button class="btn btn-primary mt-3" style="width: 100%;" onclick="proceedToCheckout()">
                Proceed to Checkout
            </button>
            
            <a href="menu.php" class="btn btn-outline mt-2" style="width: 100%; text-align: center;">
                Continue Shopping
            </a>
        </div>
    </div>
    
    <script src="../scripts/cart.js"></script>
    <script>
        function resolveCartItemImage(name, providedImage) {
            return providedImage || '';
        }

        function loadCartItems() {
            const cart = getCart();
            const container = document.getElementById('cartItemsContainer');
            
            if (cart.length === 0) {
                container.innerHTML = `
                    <div class="empty-cart">
                        <h3>Your cart is empty</h3>
                        <p>Add some delicious items to get started!</p>
                        <a href="menu.php" class="btn btn-primary mt-3">Browse Menu</a>
                    </div>
                `;
                updateSummary(0);
                return;
            }
            
            let html = '';
            cart.forEach(item => {
                const resolvedImage = resolveCartItemImage(item.name, item.image || '');
                const hasImage = !!resolvedImage;
                html += `
                    <div class="cart-item">
                        <img src="${resolvedImage}" alt="${item.name}" class="item-image" style="display: ${hasImage ? 'block' : 'none'};" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="item-image-placeholder" style="display: ${hasImage ? 'none' : 'flex'};">🍽️</div>
                        <div class="item-details">
                            <h4>${item.name}</h4>
                            <p style="color: var(--primary-color); font-weight: bold;">₹${item.price.toFixed(2)}</p>
                        </div>
                        <div class="item-actions">
                            <div class="quantity-controls">
                                <button class="qty-btn" onclick="changeQuantity(${item.id}, ${item.quantity - 1})">-</button>
                                <span class="qty-display">${item.quantity}</span>
                                <button class="qty-btn" onclick="changeQuantity(${item.id}, ${item.quantity + 1})">+</button>
                            </div>
                            <p style="font-weight: bold;">₹${(item.price * item.quantity).toFixed(2)}</p>
                            <button class="btn btn-outline" style="padding: 0.4rem 0.8rem; font-size: 0.9rem;" 
                                    onclick="removeItem(${item.id})">Remove</button>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
            updateSummary(getCartTotal());
        }
        
        function changeQuantity(itemId, newQuantity) {
            if (newQuantity <= 0) {
                if (confirm('Remove this item from cart?')) {
                    removeFromCart(itemId);
                    loadCartItems();
                }
            } else {
                updateQuantity(itemId, newQuantity);
                loadCartItems();
            }
        }
        
        function removeItem(itemId) {
            if (confirm('Remove this item from cart?')) {
                removeFromCart(itemId);
                loadCartItems();
            }
        }
        
        function updateSummary(subtotal) {
            const deliveryFee = subtotal > 0 ? 40 : 0;
            const gst = subtotal * 0.05;
            const total = subtotal + deliveryFee + gst;
            
            document.getElementById('subtotal').textContent = '₹' + subtotal.toFixed(2);
            document.getElementById('deliveryFee').textContent = '₹' + deliveryFee.toFixed(2);
            document.getElementById('gst').textContent = '₹' + gst.toFixed(2);
            document.getElementById('total').textContent = '₹' + total.toFixed(2);
        }
        
        function proceedToCheckout() {
            const cart = getCart();
            if (cart.length === 0) {
                alert('Your cart is empty!');
                return;
            }
            
            const address = document.getElementById('deliveryAddress').value.trim();
            if (!address) {
                alert('Please enter delivery address!');
                return;
            }
            
            // Store address in session storage for checkout
            sessionStorage.setItem('delivery_address', address);
            
            // Redirect to checkout
            window.location.href = 'checkout.php';
        }
        
        // Load cart on page load
        loadCartItems();
        updateCartCount();
    </script>
</body>
</html>
<?php $conn->close(); ?>
