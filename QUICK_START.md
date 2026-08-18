# Quick Start Guide - Inventory Management System

## 🚀 Getting Started in 3 Steps

### Step 1: Check Your Environment
1. Make sure **XAMPP/WAMP/MAMP** is running
2. Ensure **Apache** and **MySQL** services are started
3. Place this project in your web server directory (e.g., `htdocs` for XAMPP)

### Step 2: Set Up the Database
**Option A: Automatic Setup (Recommended)**
1. Open your browser and go to: `http://localhost/your-project-folder/setup.php`
2. The script will automatically:
   - Create the database
   - Create all tables
   - Insert the admin user
   - Set up directories

**Option B: Manual Setup**
1. Open phpMyAdmin (`http://localhost/phpmyadmin`)
2. Create a new database named `inventory_management`
3. Import the file `database/schema.sql`

### Step 3: Start Using the System
1. Go to: `http://localhost/your-project-folder/index.php`
2. **Login as Admin:**
   - Username: `admin`
   - Password: `admin123`
3. **Or Register** as a new Supplier or Buyer

## 🔧 Troubleshooting

### If you can't login/register:
1. Run `http://localhost/your-project-folder/test-connection.php` to check database connection
2. Make sure MySQL is running
3. Check that the database `inventory_management` exists

### If you get database errors:
1. Check `config/database.php` - make sure credentials are correct
2. Run the setup script: `setup.php`
3. Ensure PHP has PDO MySQL extension enabled

### If images won't upload:
1. Make sure `uploads/products/` directory exists and is writable
2. Check PHP file upload settings in `php.ini`

## 📋 Default Accounts

### Admin Account
- **Username:** admin
- **Password:** admin123
- **Access:** Full system control

### Create Your Own Accounts
- **Suppliers:** Can add/manage products
- **Buyers:** Can browse and purchase products

## 🎯 User Roles & Features

### 👑 Admin
- Complete system dashboard
- Manage all users
- Manage all products
- View all orders
- System analytics

### 🏪 Supplier
- Add/edit/delete products
- Manage inventory
- View orders for their products
- Sales analytics

### 🛒 Buyer
- Browse products
- Shopping cart
- Place orders
- Order history

## 📁 Project Structure
```
inventory-management-system/
├── config/database.php          # Database configuration
├── css/style.css               # Styling
├── database/schema.sql         # Database structure
├── uploads/products/           # Product images
├── setup.php                   # Automatic setup script
├── test-connection.php         # Database test script
├── index.php                   # Homepage
├── login.php & register.php    # Authentication
├── *-dashboard.php             # Role-specific dashboards
└── README.md                   # Full documentation
```

## 🆘 Need Help?

1. **Database Issues:** Run `test-connection.php`
2. **Setup Issues:** Run `setup.php`
3. **General Issues:** Check the full `README.md`

## ✅ Quick Test Checklist

- [ ] XAMPP/WAMP is running
- [ ] Apache and MySQL services are started
- [ ] Database `inventory_management` exists
- [ ] Can access `http://localhost/your-project-folder/`
- [ ] Can login with admin/admin123
- [ ] Can register new accounts

**Happy coding! 🎉**