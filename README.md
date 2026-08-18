# Inventory Management System

A comprehensive web-based inventory management system built with PHP and MySQL, featuring three distinct user roles: Admin, Supplier, and Buyer.

## Features

### User Authentication
- User registration and login system
- Role-based access control (Admin, Supplier, Buyer)
- Secure password hashing
- Session management

### Admin Features
- Complete system dashboard with statistics
- User management (add, edit, delete users)
- Product management (view, edit, delete all products)
- Order monitoring and revenue tracking
- Low stock alerts

### Supplier Features
- Add new products with images
- Update product details and inventory
- Delete own products
- View personal product listings

### Buyer Features
- Browse all available products
- Add products to shopping cart
- Update cart quantities
- Complete checkout process
- Order confirmation and history

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **Server**: Apache/Nginx with PHP support

## Installation

### Prerequisites
- Web server (Apache/Nginx)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- PDO PHP extension

### Setup Instructions

1. **Clone or download the project files**
   ```bash
   git clone <repository-url>
   cd inventory-management-system
   ```

2. **Database Setup**
   - Create a MySQL database named `inventory_management`
   - Import the database schema:
   ```bash
   mysql -u root -p inventory_management < database/schema.sql
   ```

3. **Configure Database Connection**
   - Edit `config/database.php`
   - Update the database credentials:
   ```php
   $host = 'localhost';
   $dbname = 'inventory_management';
   $username = 'your_username';
   $password = 'your_password';
   ```

4. **Set Directory Permissions**
   ```bash
   chmod 755 uploads/
   chmod 755 uploads/products/
   ```

5. **Web Server Configuration**
   - Point your web server document root to the project directory
   - Ensure PHP is properly configured
   - Enable URL rewriting if needed

## Default Login Credentials

### Admin Account
- **Username**: admin
- **Password**: admin123

### Creating Other Accounts
- Supplier and Buyer accounts can be created through the registration page
- Admin can also create accounts through the user management interface

## File Structure

```
inventory-management-system/
├── config/
│   └── database.php          # Database configuration
├── css/
│   └── style.css            # Main stylesheet
├── database/
│   └── schema.sql           # Database schema
├── js/
│   └── script.js            # JavaScript functionality
├── uploads/
│   └── products/            # Product images directory
├── index.php                # Homepage
├── login.php               # Login page
├── register.php            # Registration page
├── dashboard.php           # Admin dashboard
├── product-list.php        # Product listing
├── add-product.php         # Add product (Supplier)
├── cart.php               # Shopping cart (Buyer)
├── checkout.php           # Checkout process
└── README.md              # This file
```

## Security Features

- Password hashing using PHP's `password_hash()`
- SQL injection prevention with prepared statements
- Input validation and sanitization
- Session-based authentication
- Role-based access control
- File upload validation

## Usage

### For Suppliers
1. Register as a Supplier
2. Login to access supplier dashboard
3. Add products with details and images
4. Manage inventory and update product information

### For Buyers
1. Register as a Buyer
2. Browse available products
3. Add items to cart
4. Complete checkout process

### For Admins
1. Login with admin credentials
2. Access admin dashboard for system overview
3. Manage users and products
4. Monitor orders and system activity

## Database Schema

### Users Table
- `id` (Primary Key)
- `username` (Unique)
- `email` (Unique)
- `password` (Hashed)
- `role` (admin/supplier/buyer)
- `created_at`

### Products Table
- `id` (Primary Key)
- `name`
- `description`
- `price`
- `image`
- `supplier_id` (Foreign Key)
- `stock_quantity`
- `created_at`

### Cart Table
- `id` (Primary Key)
- `user_id` (Foreign Key)
- `product_id` (Foreign Key)
- `quantity`
- `created_at`

### Orders Table
- `id` (Primary Key)
- `user_id` (Foreign Key)
- `total_amount`
- `status`
- `created_at`

### Order Items Table
- `id` (Primary Key)
- `order_id` (Foreign Key)
- `product_id` (Foreign Key)
- `quantity`
- `price`

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `config/database.php`
   - Ensure MySQL service is running
   - Verify database exists

2. **Image Upload Issues**
   - Check `uploads/products/` directory permissions
   - Ensure directory exists and is writable
   - Verify PHP file upload settings

3. **Session Issues**
   - Check PHP session configuration
   - Ensure session directory is writable
   - Clear browser cookies if needed

### Error Logs
- Check PHP error logs for detailed error information
- Enable error reporting in development environment

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open source and available under the [MIT License](LICENSE).

## Support

For support and questions, please create an issue in the repository or contact the development team.