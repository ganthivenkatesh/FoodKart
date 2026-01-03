<?php
require_once '../php/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - FoodKart</title>
    <link rel="stylesheet" href="../styles/main.css">
    <style>
        .terms-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: var(--shadow);
        }
        
        .terms-container h1 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-size: 2.5rem;
        }
        
        .terms-container h2 {
            color: var(--secondary-color);
            margin-top: 2rem;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }
        
        .terms-container p {
            margin-bottom: 1rem;
            line-height: 1.8;
            color: var(--dark-text);
        }
        
        .terms-container ul {
            margin-left: 2rem;
            margin-bottom: 1rem;
        }
        
        .terms-container li {
            margin-bottom: 0.5rem;
            line-height: 1.8;
        }
        
        .last-updated {
            color: var(--light-text);
            font-style: italic;
            margin-bottom: 2rem;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 1rem;
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .important-note {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 1rem;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="terms-container">
        <a href="../index.php" class="back-link">← Back to Home</a>
        
        <h1>Terms of Service</h1>
        <p class="last-updated">Last Updated: October 25, 2025</p>
        
        <p>Welcome to FoodKart! These Terms of Service ("Terms") govern your use of our food delivery platform. By accessing or using FoodKart, you agree to be bound by these Terms.</p>
        
        <h2>1. Acceptance of Terms</h2>
        <p>By creating an account, placing an order, or using any of our services, you acknowledge that you have read, understood, and agree to be bound by these Terms and our Privacy Policy. If you do not agree, please do not use our services.</p>
        
        <h2>2. Eligibility</h2>
        <p>To use FoodKart, you must:</p>
        <ul>
            <li>Be at least 18 years of age</li>
            <li>Have the legal capacity to enter into binding contracts</li>
            <li>Provide accurate and complete registration information</li>
            <li>Maintain the security of your account credentials</li>
            <li>Not be prohibited from using our services under applicable laws</li>
        </ul>
        
        <h2>3. User Accounts</h2>
        <p><strong>Account Registration:</strong></p>
        <ul>
            <li>You must create an account to place orders</li>
            <li>Provide accurate, current, and complete information</li>
            <li>Keep your password secure and confidential</li>
            <li>Notify us immediately of any unauthorized access</li>
            <li>You are responsible for all activities under your account</li>
        </ul>
        
        <p><strong>Account Types:</strong></p>
        <ul>
            <li><strong>Customer:</strong> Browse restaurants, place orders, track deliveries</li>
            <li><strong>Restaurant Owner:</strong> Manage restaurant profile, menu, and orders</li>
            <li><strong>Delivery Agent:</strong> Accept and deliver orders</li>
        </ul>
        
        <h2>4. Orders and Payments</h2>
        <p><strong>Placing Orders:</strong></p>
        <ul>
            <li>All orders are subject to acceptance by the restaurant</li>
            <li>Prices are displayed in Indian Rupees (INR)</li>
            <li>Prices include applicable taxes and delivery fees</li>
            <li>We reserve the right to refuse or cancel orders</li>
        </ul>
        
        <p><strong>Payment Methods:</strong></p>
        <ul>
            <li>Razorpay (Credit/Debit Cards, UPI, Net Banking)</li>
            <li>PayPal</li>
            <li>Cash on Delivery (COD)</li>
        </ul>
        
        <p><strong>Payment Terms:</strong></p>
        <ul>
            <li>Payment is due at the time of order placement</li>
            <li>For COD orders, payment is due upon delivery</li>
            <li>All payments are processed securely through third-party gateways</li>
            <li>You authorize us to charge your payment method for all orders</li>
        </ul>
        
        <h2>5. Delivery</h2>
        <p><strong>Delivery Terms:</strong></p>
        <ul>
            <li>Estimated delivery times are approximate and not guaranteed</li>
            <li>Delivery is available within our service areas only</li>
            <li>You must provide accurate delivery address and contact information</li>
            <li>Delivery agents may contact you for directions or access</li>
            <li>Failed deliveries due to incorrect information are non-refundable</li>
        </ul>
        
        <div class="important-note">
            <strong>Important:</strong> Please ensure someone is available to receive the order. If no one is available after multiple attempts, the order may be cancelled without refund.
        </div>
        
        <h2>6. Cancellations and Refunds</h2>
        <p><strong>Cancellation Policy:</strong></p>
        <ul>
            <li>Orders can be cancelled before restaurant acceptance</li>
            <li>Once preparation begins, cancellation may not be possible</li>
            <li>Cancellation fees may apply based on order status</li>
            <li>Restaurant partners have the right to cancel orders</li>
        </ul>
        
        <p><strong>Refund Policy:</strong></p>
        <ul>
            <li>Refunds are processed for cancelled orders (before preparation)</li>
            <li>Refunds for quality issues are evaluated case-by-case</li>
            <li>Refunds are processed within 5-7 business days</li>
            <li>Refunds are issued to the original payment method</li>
            <li>COD orders are not eligible for refunds</li>
        </ul>
        
        <h2>7. Restaurant Partner Responsibilities</h2>
        <p>Restaurant owners agree to:</p>
        <ul>
            <li>Provide accurate menu information and pricing</li>
            <li>Maintain food quality and safety standards</li>
            <li>Prepare orders within estimated time frames</li>
            <li>Comply with all applicable food safety regulations</li>
            <li>Handle customer complaints professionally</li>
            <li>Pay applicable commission fees to FoodKart</li>
        </ul>
        
        <h2>8. Delivery Agent Responsibilities</h2>
        <p>Delivery agents agree to:</p>
        <ul>
            <li>Accept orders in a timely manner</li>
            <li>Deliver orders safely and promptly</li>
            <li>Maintain professional conduct with customers</li>
            <li>Follow traffic laws and safety regulations</li>
            <li>Handle food items with care</li>
            <li>Update order status accurately</li>
        </ul>
        
        <h2>9. User Conduct</h2>
        <p>You agree NOT to:</p>
        <ul>
            <li>Violate any laws or regulations</li>
            <li>Provide false or misleading information</li>
            <li>Impersonate any person or entity</li>
            <li>Interfere with the platform's operation</li>
            <li>Use automated systems to access the platform</li>
            <li>Harass, abuse, or harm other users</li>
            <li>Post inappropriate content or reviews</li>
            <li>Attempt to gain unauthorized access to our systems</li>
        </ul>
        
        <h2>10. Intellectual Property</h2>
        <p>All content on FoodKart, including:</p>
        <ul>
            <li>Logo, trademarks, and brand elements</li>
            <li>Website design and layout</li>
            <li>Text, images, and graphics</li>
            <li>Software and source code</li>
        </ul>
        <p>...are owned by FoodKart or our licensors and protected by intellectual property laws. You may not copy, modify, distribute, or reproduce any content without permission.</p>
        
        <h2>11. Disclaimers</h2>
        <p><strong>Service Availability:</strong></p>
        <ul>
            <li>We do not guarantee uninterrupted service availability</li>
            <li>Services may be temporarily unavailable for maintenance</li>
            <li>We are not responsible for third-party service failures</li>
        </ul>
        
        <p><strong>Food Quality:</strong></p>
        <ul>
            <li>Food is prepared by independent restaurant partners</li>
            <li>We are not responsible for food quality or safety</li>
            <li>Customers should report issues directly to restaurants</li>
        </ul>
        
        <h2>12. Limitation of Liability</h2>
        <p>To the maximum extent permitted by law:</p>
        <ul>
            <li>FoodKart is not liable for indirect, incidental, or consequential damages</li>
            <li>Our total liability is limited to the amount paid for the specific order</li>
            <li>We are not liable for delays, cancellations, or quality issues</li>
            <li>We are not responsible for third-party actions or failures</li>
        </ul>
        
        <h2>13. Indemnification</h2>
        <p>You agree to indemnify and hold FoodKart harmless from any claims, damages, or expenses arising from:</p>
        <ul>
            <li>Your violation of these Terms</li>
            <li>Your use of our services</li>
            <li>Your violation of any third-party rights</li>
            <li>Any content you submit to the platform</li>
        </ul>
        
        <h2>14. Dispute Resolution</h2>
        <p><strong>Customer Support:</strong> Contact us first to resolve any issues at support@foodkart.com</p>
        <p><strong>Governing Law:</strong> These Terms are governed by the laws of India</p>
        <p><strong>Jurisdiction:</strong> Disputes will be resolved in the courts of Mumbai, India</p>
        
        <h2>15. Modifications to Terms</h2>
        <p>We reserve the right to modify these Terms at any time. Changes will be effective immediately upon posting. Continued use of our services constitutes acceptance of the modified Terms.</p>
        
        <h2>16. Termination</h2>
        <p>We may terminate or suspend your account:</p>
        <ul>
            <li>For violation of these Terms</li>
            <li>For fraudulent or illegal activity</li>
            <li>At our sole discretion with or without notice</li>
        </ul>
        <p>You may terminate your account by contacting customer support.</p>
        
        <h2>17. Contact Information</h2>
        <p>For questions about these Terms, please contact us:</p>
        <ul>
            <li><strong>Email:</strong> support@foodkart.com / info@foodkart.com</li>
            <li><strong>Phone:</strong> +91 63030-92763</li>
            <li><strong>Address:</strong> Mohan Babu University, Sri Sainath Nagar, Tirupathi, Andhra Pradesh 517102</li>
            <li><strong>Contact Form:</strong> <a href="contact.php">Visit our Contact Page</a></li>
        </ul>
        
        <h2>18. Severability</h2>
        <p>If any provision of these Terms is found to be unenforceable, the remaining provisions will continue in full force and effect.</p>
        
        <h2>19. Entire Agreement</h2>
        <p>These Terms, together with our Privacy Policy, constitute the entire agreement between you and FoodKart regarding the use of our services.</p>
        
        <div class="important-note" style="margin-top: 2rem;">
            <strong>By using FoodKart, you acknowledge that you have read, understood, and agree to be bound by these Terms of Service.</strong>
        </div>
    </div>
    
    <footer style="background-color: var(--secondary-color); color: white; text-align: center; padding: 2rem; margin-top: 3rem;">
        <div class="container">
            <p>&copy; 2025 FoodKart. All rights reserved.</p>
            <p style="margin-top: 0.5rem;">
                <a href="contact.php" style="color: white; margin: 0 1rem;">Contact Us</a>
                <a href="privacy_policy.php" style="color: white; margin: 0 1rem;">Privacy Policy</a>
                <a href="terms_of_service.php" style="color: white; margin: 0 1rem;">Terms of Service</a>
            </p>
        </div>
    </footer>
</body>
</html>
