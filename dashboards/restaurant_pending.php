<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Approval - FoodKart</title>
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
        
        .pending-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            padding: 3rem;
            text-align: center;
        }
        
        .pending-icon {
            font-size: 5rem;
            margin-bottom: 1rem;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .pending-container h1 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .pending-container p {
            color: var(--light-text);
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        
        .restaurant-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: left;
        }
        
        .restaurant-info h3 {
            color: var(--dark-text);
            margin-bottom: 1rem;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--dark-text);
        }
        
        .info-value {
            color: var(--light-text);
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: #fff3cd;
            color: #856404;
            border-radius: 20px;
            font-weight: 600;
            margin-bottom: 2rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .btn-outline {
            background: transparent;
            color: var(--light-text);
            border: 2px solid #dee2e6;
        }
        
        .btn-outline:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .timeline {
            margin: 2rem 0;
            padding: 1.5rem;
            background: #e3f2fd;
            border-radius: 10px;
        }
        
        .timeline h4 {
            color: #1976d2;
            margin-bottom: 1rem;
        }
        
        .timeline-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        
        .timeline-item:last-child {
            margin-bottom: 0;
        }
        
        .timeline-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #4caf50;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 0.9rem;
        }
        
        .timeline-icon.pending {
            background: #ff9800;
        }
        
        .timeline-icon.future {
            background: #9e9e9e;
        }
        
        .timeline-text {
            color: #1976d2;
            font-size: 0.95rem;
        }
    </style>
</head>
<body>
    <div class="pending-container">
        <div class="pending-icon">⏳</div>
        <h1>Registration Under Review</h1>
        <div class="status-badge">⏳ Pending Approval</div>
        <p>Thank you for registering your restaurant with FoodKart! Your application is currently being reviewed by our admin team.</p>
        
        <div class="restaurant-info">
            <h3>📋 Your Restaurant Details</h3>
            <div class="info-row">
                <span class="info-label">Restaurant Name:</span>
                <span class="info-value"><?php echo htmlspecialchars($restaurant['name']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Cuisine:</span>
                <span class="info-value"><?php echo htmlspecialchars($restaurant['cuisine']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Location:</span>
                <span class="info-value"><?php echo htmlspecialchars($restaurant['location']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Phone:</span>
                <span class="info-value"><?php echo htmlspecialchars($restaurant['phone']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Submitted On:</span>
                <span class="info-value"><?php echo date('d M Y, H:i', strtotime($restaurant['created_at'])); ?></span>
            </div>
        </div>
        
        <div class="timeline">
            <h4>📍 Approval Process</h4>
            <div class="timeline-item">
                <div class="timeline-icon">✓</div>
                <div class="timeline-text">Registration Submitted</div>
            </div>
            <div class="timeline-item">
                <div class="timeline-icon pending">⏳</div>
                <div class="timeline-text">Admin Review (Current Stage)</div>
            </div>
            <div class="timeline-item">
                <div class="timeline-icon future">○</div>
                <div class="timeline-text">Approval & Dashboard Access</div>
            </div>
        </div>
        
        <p style="font-size: 0.95rem; color: var(--light-text);">
            💡 <strong>What's Next?</strong><br>
            Our team typically reviews applications within 24-48 hours. You'll receive an email notification once your restaurant is approved. In the meantime, feel free to explore the platform!
        </p>
        
        <div class="action-buttons">
            <a href="../index.php" class="btn btn-primary">Go to Home</a>
            <a href="../php/auth.php?logout=1" class="btn btn-outline">Logout</a>
        </div>
    </div>
</body>
</html>
