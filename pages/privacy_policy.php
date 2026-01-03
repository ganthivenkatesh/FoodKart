<?php
require_once '../php/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - FoodKart</title>
    <link rel="stylesheet" href="../styles/main.css">
    <style>
        .policy-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: var(--shadow);
        }
        
        .policy-container h1 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-size: 2.5rem;
        }
        
        .policy-container h2 {
            color: var(--secondary-color);
            margin-top: 2rem;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }
        
        .policy-container p {
            margin-bottom: 1rem;
            line-height: 1.8;
            color: var(--dark-text);
        }
        
        .policy-container ul {
            margin-left: 2rem;
            margin-bottom: 1rem;
        }
        
        .policy-container li {
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
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="policy-container">
        <a href="../index.php" class="back-link">← Back to Home</a>
        
        <h1>Privacy Policy</h1>
        <p class="last-updated">Last Updated: October 25, 2025</p>
        
        <p>Welcome to FoodKart. We respect your privacy and are committed to protecting your personal data. This privacy policy will inform you about how we look after your personal data when you visit our website and tell you about your privacy rights.</p>
        
        <h2>1. Information We Collect</h2>
        <p>We collect and process the following types of personal information:</p>
        <ul>
            <li><strong>Account Information:</strong> Name, email address, phone number, and password when you create an account</li>
            <li><strong>Order Information:</strong> Delivery address, payment details, and order history</li>
            <li><strong>Location Data:</strong> Delivery address and location for order fulfillment</li>
            <li><strong>Usage Data:</strong> Information about how you use our website, including pages visited and features used</li>
            <li><strong>Device Information:</strong> Browser type, IP address, and device identifiers</li>
        </ul>
        
        <h2>2. How We Use Your Information</h2>
        <p>We use your personal information for the following purposes:</p>
        <ul>
            <li>To process and deliver your food orders</li>
            <li>To manage your account and provide customer support</li>
            <li>To send you order confirmations and delivery updates</li>
            <li>To improve our services and website functionality</li>
            <li>To send promotional offers and marketing communications (with your consent)</li>
            <li>To prevent fraud and ensure platform security</li>
            <li>To comply with legal obligations</li>
        </ul>
        
        <h2>3. Information Sharing</h2>
        <p>We may share your information with:</p>
        <ul>
            <li><strong>Restaurant Partners:</strong> To fulfill your orders</li>
            <li><strong>Delivery Agents:</strong> To deliver your orders to your location</li>
            <li><strong>Payment Processors:</strong> To process your payments securely</li>
            <li><strong>Service Providers:</strong> Who help us operate our platform</li>
            <li><strong>Legal Authorities:</strong> When required by law or to protect our rights</li>
        </ul>
        <p>We do not sell your personal information to third parties.</p>
        
        <h2>4. Data Security</h2>
        <p>We implement appropriate security measures to protect your personal information:</p>
        <ul>
            <li>Password encryption using industry-standard hashing algorithms</li>
            <li>Secure HTTPS connections for data transmission</li>
            <li>Regular security audits and updates</li>
            <li>Access controls and authentication mechanisms</li>
            <li>Secure payment processing through trusted payment gateways</li>
        </ul>
        
        <h2>5. Your Rights</h2>
        <p>You have the following rights regarding your personal data:</p>
        <ul>
            <li><strong>Access:</strong> Request a copy of your personal data</li>
            <li><strong>Correction:</strong> Update or correct inaccurate information</li>
            <li><strong>Deletion:</strong> Request deletion of your account and data</li>
            <li><strong>Objection:</strong> Object to processing of your personal data</li>
            <li><strong>Portability:</strong> Request transfer of your data to another service</li>
            <li><strong>Withdraw Consent:</strong> Opt-out of marketing communications</li>
        </ul>
        
        <h2>6. Cookies and Tracking</h2>
        <p>We use cookies and similar technologies to:</p>
        <ul>
            <li>Remember your preferences and login status</li>
            <li>Store your shopping cart items</li>
            <li>Analyze website traffic and usage patterns</li>
            <li>Provide personalized content and recommendations</li>
        </ul>
        <p>You can control cookie settings through your browser preferences.</p>
        
        <h2>7. Data Retention</h2>
        <p>We retain your personal information for as long as necessary to:</p>
        <ul>
            <li>Provide our services to you</li>
            <li>Comply with legal obligations</li>
            <li>Resolve disputes and enforce our agreements</li>
        </ul>
        <p>Order history is retained for 7 years for accounting and legal purposes.</p>
        
        <h2>8. Children's Privacy</h2>
        <p>Our services are not intended for children under 18 years of age. We do not knowingly collect personal information from children. If you believe we have collected information from a child, please contact us immediately.</p>
        
        <h2>9. Third-Party Links</h2>
        <p>Our website may contain links to third-party websites. We are not responsible for the privacy practices of these external sites. Please review their privacy policies before providing any personal information.</p>
        
        <h2>10. Changes to This Policy</h2>
        <p>We may update this privacy policy from time to time. We will notify you of any significant changes by posting the new policy on this page and updating the "Last Updated" date.</p>
        
        <h2>11. Contact Us</h2>
        <p>If you have any questions about this privacy policy or our data practices, please contact us:</p>
        <ul>
            <li><strong>Email:</strong> support@foodkart.com / info@foodkart.com</li>
            <li><strong>Phone:</strong> +91 63030-92763</li>
            <li><strong>Address:</strong> Mohan Babu University, Sri Sainath Nagar, Tirupathi, Andhra Pradesh 517102</li>
            <li><strong>Contact Form:</strong> <a href="contact.php">Visit our Contact Page</a></li>
        </ul>
        
        <p style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #ddd;">
            By using FoodKart, you acknowledge that you have read and understood this Privacy Policy and agree to its terms.
        </p>
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
