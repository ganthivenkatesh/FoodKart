# 🍴 FoodKart - Food Delivery System

A comprehensive web-based food delivery platform similar to Zomato, built with PHP, MySQL, JavaScript, HTML, and CSS.

## 🎯 Features

### For Customers
- Browse restaurants and menus with Veg/Non-Veg/Combo filters
- Add items to cart and place orders
- Secure payment integration (Razorpay/PayPal/COD)
- Real-time order tracking
- Rate and review restaurants
- View order history

### For Restaurant Owners
- Manage restaurant profile
- Add/Edit/Delete menu items
- Update order status (Preparing → Out for Delivery → Delivered)
- View orders and revenue statistics
- Create special offers and discounts

### For Admins
- Approve/Reject restaurant registrations
- Manage users and restaurants
- Monitor all orders and payments
- View feedback and contact messages
- Platform-wide statistics

## 🛠️ Tech Stack

- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Backend**: PHP 7.4+
- **Database**: MySQL
- **Server**: XAMPP (Apache + phpMyAdmin)

## 📋 Prerequisites

- XAMPP installed on your system
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Modern web browser

## 🚀 Installation

1. **Clone or Download** the project to your XAMPP htdocs folder:
   ```
   C:\xampp\htdocs\WT-Project\FoodKart\
   ```

2. **Start XAMPP**:
   - Open XAMPP Control Panel
   - Start Apache and MySQL services

3. **Create Database**:
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Import the database file: `database/foodkart.sql`
   - Or run the SQL file directly in phpMyAdmin

4. **Configure Database** (if needed):
   - Open `php/config.php`
   - Update database credentials if different:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     define('DB_NAME', 'foodkart');
     ```

5. **Access the Application**:
   ```
   http://localhost/WT-Project/FoodKart/
   ```

## 👤 Default Login Credentials

### Admin
- **Email**: admin@foodkart.com
- **Password**: admin123

### Restaurant Owner
- **Email**: john@restaurant.com
- **Password**: password123

### Customer
- **Email**: alice@customer.com
- **Password**: password123

## 📁 Project Structure

```
FoodKart/
├── assets/              # Images and static files
├── database/            # SQL database schema
│   └── foodkart.sql
├── dashboards/          # Admin and Restaurant dashboards
│   ├── admin_dashboard.php
│   ├── restaurant_dashboard.php
│   └── manage_menu.php
├── pages/               # Customer pages
│   ├── menu.php
│   ├── cart.php
│   ├── checkout.php
│   ├── order_tracking.php
│   ├── user_home.php
│   ├── restaurants.php
│   ├── contact.php
│   └── feedback.php
├── php/                 # Backend PHP files
│   ├── config.php
│   ├── auth.php
│   ├── process_order.php
│   └── update_order_status.php
├── scripts/             # JavaScript files
│   └── cart.js
├── styles/              # CSS files
│   └── main.css
├── index.php            # Home page
├── login_signup.php     # Authentication page
└── README.md
```

## 🔐 Security Features

- Password hashing using PHP's `password_hash()`
- SQL injection prevention with prepared statements
- Input sanitization and validation
- Session-based authentication
- Role-based access control
- CSRF protection for payments

## 💳 Payment Integration

The system supports three payment methods:

1. **Razorpay** - Credit/Debit cards, UPI, Net Banking
2. **PayPal** - PayPal account payments
3. **Cash on Delivery (COD)** - Pay when you receive

*Note: Payment gateways are in sandbox/demo mode. Configure API keys for production use.*

## 📊 Database Schema

### Main Tables
- `users` - User accounts (Admin, Restaurant Owner, Customer)
- `restaurants` - Restaurant information
- `menu_items` - Food items with categories
- `orders` - Order details
- `order_items` - Items in each order
- `payments` - Payment transactions
- `feedback` - Customer reviews
- `offers` - Restaurant offers and discounts
- `contact` - Contact form submissions

## 🎨 Features Highlights

### Menu Filtering
- Filter by Veg/Non-Veg/Combo
- Search by dish or restaurant name
- Restaurant-wise filtering

### Order Tracking
- Real-time status updates
- Visual timeline progress
- Auto-refresh every 30 seconds

### Cart System
- LocalStorage-based cart
- Quantity management
- Price calculation with GST and delivery fee

### Responsive Design
- Mobile-friendly interface
- Modern UI with smooth animations
- Intuitive navigation

## 🔧 Customization

### Change Colors
Edit `styles/main.css`:
```css
:root {
    --primary-color: #e74c3c;
    --secondary-color: #2c3e50;
    --success-color: #27ae60;
}
```

### Add Payment Gateway
1. Get API keys from payment provider
2. Update `pages/checkout.php`
3. Implement payment callback in `php/process_order.php`

## 🐛 Troubleshooting

### Database Connection Error
- Verify MySQL is running in XAMPP
- Check database credentials in `php/config.php`
- Ensure database `foodkart` exists

### Login Not Working
- Clear browser cache and cookies
- Check if session is started in `php/config.php`
- Verify user exists in database

### Cart Not Saving
- Enable JavaScript in browser
- Check browser console for errors
- Verify LocalStorage is enabled

## 📝 License

This project is created for educational purposes.

## 👨‍💻 Developer Notes

- All passwords are hashed using bcrypt
- Sample data is included in the SQL file
- Images use placeholder URLs (update with actual images)
- Payment integration is in demo mode

## 🚀 Future Enhancements

- Email notifications
- SMS alerts for order updates
- AI-based food recommendations
- Advanced analytics dashboard
- Mobile app integration
- Multi-language support

## 📞 Support

For issues or questions:
- Check the troubleshooting section
- Review the code comments
- Verify XAMPP services are running

---

**Developed with ❤️ for FoodKart**
