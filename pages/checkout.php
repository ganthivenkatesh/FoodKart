<?php
require_once '../php/config.php';
requireRole('customer');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - FoodKart</title>
    <link rel="stylesheet" href="../styles/main.css">
    <style>
        .checkout-container {
            max-width: 600px;
            margin: 3rem auto;
            padding: 0 20px;
        }
        
        .checkout-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: var(--shadow);
        }
        
        .payment-methods {
            display: grid;
            gap: 1rem;
            margin: 2rem 0;
        }
        
        .payment-option {
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .payment-option:hover {
            border-color: var(--primary-color);
        }
        
        .payment-option.selected {
            border-color: var(--primary-color);
            background-color: #ffe5e5;
        }
        
        .payment-option input[type="radio"] {
            width: 20px;
            height: 20px;
        }
        
        .payment-icon {
            font-size: 2rem;
        }
        
        .order-summary-box {
            background-color: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin: 1.5rem 0;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.8rem;
        }
        
        .summary-total {
            border-top: 2px solid #ddd;
            padding-top: 1rem;
            margin-top: 1rem;
            font-size: 1.3rem;
            font-weight: bold;
        }
        
        .phonepe-qr-container {
            display: none;
            background: white;
            border: 2px solid #5f259f;
            border-radius: 15px;
            padding: 2rem;
            margin-top: 1rem;
            text-align: center;
        }
        
        .phonepe-qr-container.active {
            display: block;
        }
        
        .phonepe-qr-container img {
            max-width: 300px;
            width: 100%;
            margin: 1rem auto;
            display: block;
        }
        
        .phonepe-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .phonepe-logo {
            width: 50px;
            height: 50px;
            background: #5f259f;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .phonepe-instructions {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin-top: 1rem;
            text-align: left;
        }
        
        .phonepe-instructions ol {
            margin: 0.5rem 0 0 1.2rem;
            padding: 0;
        }
        
        .phonepe-instructions li {
            margin: 0.5rem 0;
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
    
    <div class="checkout-container">
        <div class="checkout-card">
            <h2 style="text-align: center; margin-bottom: 2rem;">💳 Secure Checkout</h2>
            
            <div id="orderSummary" class="order-summary-box">
                <!-- Will be populated by JavaScript -->
            </div>
            
            <h3>Select Payment Method</h3>
            <div class="payment-methods">
                <label class="payment-option" onclick="selectPayment('razorpay')">
                    <input type="radio" name="payment" value="razorpay" checked>
                    <span class="payment-icon">💳</span>
                    <div>
                        <strong>Razorpay</strong>
                        <p style="margin: 0; font-size: 0.9rem; color: var(--light-text);">Credit/Debit Card, UPI, Net Banking</p>
                    </div>
                </label>
                
                <label class="payment-option" onclick="selectPayment('paypal')">
                    <input type="radio" name="payment" value="paypal">
                    <span class="payment-icon">🅿️</span>
                    <div>
                        <strong>PayPal</strong>
                        <p style="margin: 0; font-size: 0.9rem; color: var(--light-text);">Pay with PayPal account</p>
                    </div>
                </label>
                
                <label class="payment-option" onclick="selectPayment('phonepe')">
                    <input type="radio" name="payment" value="phonepe">
                    <span class="payment-icon" style="color: #5f259f;">📱</span>
                    <div>
                        <strong>PhonePe UPI</strong>
                        <p style="margin: 0; font-size: 0.9rem; color: var(--light-text);">Scan QR code and pay instantly</p>
                    </div>
                </label>
                
                <label class="payment-option" onclick="selectPayment('cod')">
                    <input type="radio" name="payment" value="cod">
                    <span class="payment-icon">💵</span>
                    <div>
                        <strong>Cash on Delivery</strong>
                        <p style="margin: 0; font-size: 0.9rem; color: var(--light-text);">Pay when you receive</p>
                    </div>
                </label>
            </div>
            
            <!-- PhonePe QR Code Section -->
            <div id="phonepeQR" class="phonepe-qr-container">
                <div class="phonepe-header">
                    <div class="phonepe-logo">Pe</div>
                    <h3 style="margin: 0; color: #5f259f;">PhonePe Payment</h3>
                </div>
                <p style="color: #5f259f; font-weight: bold; font-size: 1.1rem;">ACCEPTED HERE</p>
                <p style="margin: 0.5rem 0;">Scan & Pay Using PhonePe App</p>
                
                <!-- QR Code Image - You'll need to save your QR code as phonepe-qr.png in images folder -->
                <img src="../images/phonepe-qr.png" alt="PhonePe QR Code" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22300%22 height=%22300%22%3E%3Crect width=%22300%22 height=%22300%22 fill=%22%23f0f0f0%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%23999%22%3EPhonePe QR Code%3C/text%3E%3C/svg%3E'">
                
                <p style="font-weight: bold; margin-top: 1rem;">Guduru Mery</p>
                
                <div class="phonepe-instructions">
                    <strong>How to pay:</strong>
                    <ol>
                        <li>Open PhonePe app on your phone</li>
                        <li>Tap on 'Scan QR Code'</li>
                        <li>Scan the QR code above</li>
                        <li>Enter amount: <span id="qrAmount" style="color: var(--primary-color); font-weight: bold;">₹0.00</span></li>
                        <li>Complete the payment</li>
                        <li>Click 'Place Order' below after payment</li>
                    </ol>
                </div>
                
                <p style="color: #666; font-size: 0.85rem; margin-top: 1rem;">© 2025, All rights reserved, PhonePe Ltd (Formerly known as 'PhonePe Private Ltd')</p>
            </div>
            
            <div class="form-group">
                <label>Delivery Address</label>
                <textarea id="deliveryAddress" class="form-control" rows="3" readonly></textarea>
            </div>
            
            <button class="btn btn-primary mt-3" style="width: 100%;" onclick="placeOrder()">
                Place Order
            </button>
            
            <a href="cart.php" class="btn btn-outline mt-2" style="width: 100%; text-align: center;">
                Back to Cart
            </a>
        </div>
    </div>
    
    <script src="../scripts/cart.js"></script>
    <script>
        let selectedPaymentMethod = 'razorpay';
        
        function loadOrderSummary() {
            const cart = getCart();
            const address = sessionStorage.getItem('delivery_address');
            
            if (cart.length === 0) {
                window.location.href = 'cart.php';
                return;
            }
            
            if (!address) {
                alert('No delivery address found!');
                window.location.href = 'cart.php';
                return;
            }
            
            document.getElementById('deliveryAddress').value = address;
            
            const subtotal = getCartTotal();
            const deliveryFee = 40;
            const gst = subtotal * 0.05;
            const total = subtotal + deliveryFee + gst;
            
            let html = `
                <h4 style="margin-bottom: 1rem;">Order Summary</h4>
                <div class="summary-item">
                    <span>Items (${getCartItemCount()}):</span>
                    <span>₹${subtotal.toFixed(2)}</span>
                </div>
                <div class="summary-item">
                    <span>Delivery Fee:</span>
                    <span>₹${deliveryFee.toFixed(2)}</span>
                </div>
                <div class="summary-item">
                    <span>GST (5%):</span>
                    <span>₹${gst.toFixed(2)}</span>
                </div>
                <div class="summary-item summary-total">
                    <span>Total Amount:</span>
                    <span>₹${total.toFixed(2)}</span>
                </div>
            `;
            
            document.getElementById('orderSummary').innerHTML = html;
        }
        
        function selectPayment(method) {
            selectedPaymentMethod = method;
            document.querySelectorAll('.payment-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
            
            // Show/hide PhonePe QR code
            const phonepeQR = document.getElementById('phonepeQR');
            if (method === 'phonepe') {
                phonepeQR.classList.add('active');
                // Update amount in QR instructions
                const cart = getCart();
                const subtotal = getCartTotal();
                const deliveryFee = 40;
                const gst = subtotal * 0.05;
                const total = subtotal + deliveryFee + gst;
                document.getElementById('qrAmount').textContent = '₹' + total.toFixed(2);
            } else {
                phonepeQR.classList.remove('active');
            }
        }
        
        async function placeOrder() {
            const cart = getCart();
            const address = document.getElementById('deliveryAddress').value;
            
            if (cart.length === 0) {
                alert('Your cart is empty!');
                return;
            }
            
            const subtotal = getCartTotal();
            const deliveryFee = 40;
            const gst = subtotal * 0.05;
            const total = subtotal + deliveryFee + gst;
            
            // Prepare order data
            const orderData = {
                items: cart,
                delivery_address: address,
                payment_method: selectedPaymentMethod,
                subtotal: subtotal,
                delivery_fee: deliveryFee,
                gst: gst,
                total: total
            };
            
            try {
                const response = await fetch('../php/process_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(orderData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Clear cart
                    clearCart();
                    sessionStorage.removeItem('delivery_address');
                    
                    // Redirect to order confirmation
                    window.location.href = 'order_confirmation.php?order_id=' + result.order_id;
                } else {
                    alert('Error placing order: ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to place order. Please try again.');
            }
        }
        
        // Initialize payment options
        document.querySelectorAll('.payment-option')[0].classList.add('selected');
        
        // Load order summary
        loadOrderSummary();
    </script>
</body>
</html>
