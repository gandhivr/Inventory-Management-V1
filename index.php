<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management System - Professional Business Solution</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/cyberpunk-header.css?v=2">
    <meta name="description" content="Professional inventory management system with advanced features for admins, suppliers, and buyers. Streamline your business operations today.">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <h1>📦 Inventory Management System</h1>
                <nav>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo ucfirst($_SESSION['role']); ?>)</span>
                        <a href="logout.php">Logout</a>
                    <?php else: ?>
                        <a href="login.php">Login</a>
                        <a href="register.php">Register</a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>
    
    <main>
        <?php if (isset($_SESSION['user_id'])): ?>
            <!-- Dashboard Section for Logged-in Users -->
            <section class="dashboard-section">
                <div class="container">
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <div class="dashboard-header fade-in-up">
                            <h2>👑 Admin Command Center</h2>
                            <p>Complete system oversight with advanced analytics, user management, and comprehensive business intelligence at your fingertips.</p>
                        </div>
                        <div class="dashboard-actions">
                            <a href="admin-dashboard.php" class="action-card primary floating fade-in-up">
                                <div class="action-icon">🚀</div>
                                <div class="action-content">
                                    <h3>Admin Dashboard</h3>
                                    <p>Comprehensive analytics, system metrics, and business intelligence dashboard with real-time insights.</p>
                                </div>
                            </a>
                            <a href="manage-users.php" class="action-card fade-in-up">
                                <div class="action-icon">👥</div>
                                <div class="action-content">
                                    <h3>User Management</h3>
                                    <p>Advanced user administration with role-based access control and account management tools.</p>
                                </div>
                            </a>
                            <a href="manage-products.php" class="action-card fade-in-up">
                                <div class="action-icon">📦</div>
                                <div class="action-content">
                                    <h3>Product Oversight</h3>
                                    <p>Complete product catalog management with inventory tracking and supplier coordination.</p>
                                </div>
                            </a>
                            <a href="admin-orders.php" class="action-card fade-in-up">
                                <div class="action-icon">📊</div>
                                <div class="action-content">
                                    <h3>Order Analytics</h3>
                                    <p>Advanced order management with detailed reporting and performance analytics.</p>
                                </div>
                            </a>
                        </div>
                    <?php elseif ($_SESSION['role'] === 'supplier'): ?>
                        <div class="dashboard-header fade-in-up">
                            <h2>🏭 Supplier Business Hub</h2>
                            <p>Powerful tools for product management, sales analytics, and business growth with comprehensive supplier features.</p>
                        </div>
                        <div class="dashboard-actions">
                            <a href="supplier-dashboard.php" class="action-card primary floating fade-in-up">
                                <div class="action-icon">🚀</div>
                                <div class="action-content">
                                    <h3>Supplier Dashboard</h3>
                                    <p>Performance metrics, sales analytics, and business insights tailored for suppliers.</p>
                                </div>
                            </a>
                            <a href="add-product.php" class="action-card fade-in-up">
                                <div class="action-icon">➕</div>
                                <div class="action-content">
                                    <h3>Add New Product</h3>
                                    <p>Professional product listing with advanced features and inventory management.</p>
                                </div>
                            </a>
                            <a href="product-list.php" class="action-card fade-in-up">
                                <div class="action-icon">📦</div>
                                <div class="action-content">
                                    <h3>Product Portfolio</h3>
                                    <p>Comprehensive product management with stock tracking and performance analytics.</p>
                                </div>
                            </a>
                            <a href="supplier-orders.php" class="action-card fade-in-up">
                                <div class="action-icon">📈</div>
                                <div class="action-content">
                                    <h3>Sales & Orders</h3>
                                    <p>Advanced order tracking with detailed sales reports and customer insights.</p>
                                </div>
                            </a>
                        </div>
                    <?php elseif ($_SESSION['role'] === 'buyer'): ?>
                        <!-- Buyer Welcome Section -->
                        <div class="buyer-welcome fade-in-up">
                            <div class="welcome-content">
                                <h2>🛒 Welcome Back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
                                <p>Discover amazing products, track your orders, and enjoy a premium shopping experience tailored just for you.</p>
                            </div>
                            <div class="quick-stats">
                                <?php
                                require_once 'config/database.php';
                                try {
                                    // Get buyer stats
                                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
                                    $stmt->execute([$_SESSION['user_id']]);
                                    $cart_count = $stmt->fetchColumn();
                                    
                                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
                                    $stmt->execute([$_SESSION['user_id']]);
                                    $order_count = $stmt->fetchColumn();
                                    
                                    $stmt = $pdo->prepare("SELECT SUM(total_amount) FROM orders WHERE user_id = ? AND status = 'completed'");
                                    $stmt->execute([$_SESSION['user_id']]);
                                    $total_spent = $stmt->fetchColumn() ?: 0;
                                } catch (PDOException $e) {
                                    $cart_count = 0;
                                    $order_count = 0;
                                    $total_spent = 0;
                                }
                                ?>
                                <div class="stat-item">
                                    <div class="stat-icon">🛒</div>
                                    <div class="stat-info">
                                        <div class="stat-value"><?php echo $cart_count; ?></div>
                                        <div class="stat-label">Items in Cart</div>
                                    </div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-icon">📦</div>
                                    <div class="stat-info">
                                        <div class="stat-value"><?php echo $order_count; ?></div>
                                        <div class="stat-label">Total Orders</div>
                                    </div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-icon">💰</div>
                                    <div class="stat-info">
                                        <div class="stat-value">₹<?php echo number_format($total_spent, 0); ?></div>
                                        <div class="stat-label">Total Spent</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Featured Products Section -->
                        <div class="featured-section fade-in-up">
                            <h3>🌟 Featured Products</h3>
                            <div class="featured-products">
                                <?php
                                try {
                                    $stmt = $pdo->query("
                                        SELECT p.*, u.username as supplier_name 
                                        FROM products p 
                                        LEFT JOIN users u ON p.supplier_id = u.id 
                                        WHERE p.stock_quantity > 0
                                        ORDER BY p.created_at DESC 
                                        LIMIT 4
                                    ");
                                    $featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    foreach ($featured_products as $product):
                                ?>
                                    <div class="featured-product-card">
                                        <?php if ($product['image']): ?>
                                            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                        <?php else: ?>
                                            <div class="product-placeholder">📦</div>
                                        <?php endif; ?>
                                        <div class="product-info">
                                            <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                            <div class="product-price">₹<?php echo number_format($product['price'], 2); ?></div>
                                            <div class="product-supplier">by <?php echo htmlspecialchars($product['supplier_name']); ?></div>
                                            <form action="add-to-cart.php" method="POST" class="quick-add-form">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <input type="hidden" name="quantity" value="1">
                                                <button type="submit" class="btn-quick-add">Add to Cart</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php 
                                    endforeach;
                                } catch (PDOException $e) {
                                    echo '<p class="no-products">No products available at the moment.</p>';
                                }
                                ?>
                            </div>
                            <div class="view-all-products">
                                <a href="product-list.php" class="btn btn-view-all">View All Products →</a>
                            </div>
                        </div>

                        <!-- Quick Actions for Buyers -->
                        <div class="dashboard-actions">
                            <a href="buyer-dashboard.php" class="action-card primary floating fade-in-up">
                                <div class="action-icon">📊</div>
                                <div class="action-content">
                                    <h3>My Dashboard</h3>
                                    <p>View your shopping insights, order history, and personalized recommendations.</p>
                                </div>
                            </a>
                            <a href="product-list.php" class="action-card fade-in-up">
                                <div class="action-icon">🛍️</div>
                                <div class="action-content">
                                    <h3>Browse Products</h3>
                                    <p>Discover amazing products with advanced search and smart filtering options.</p>
                                </div>
                            </a>
                            <a href="cart.php" class="action-card fade-in-up">
                                <div class="action-icon">🛒</div>
                                <div class="action-content">
                                    <h3>My Cart (<?php echo $cart_count; ?>)</h3>
                                    <p>Review your selected items and proceed to secure checkout.</p>
                                </div>
                            </a>
                            <a href="buyer-orders.php" class="action-card fade-in-up">
                                <div class="action-icon">📋</div>
                                <div class="action-content">
                                    <h3>Order History</h3>
                                    <p>Track your orders, view delivery status, and manage your purchases.</p>
                                </div>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php else: ?>
            <!-- Hero Section for Non-logged-in Users -->
            <section class="hero">
                <div class="container">
                    <div class="hero-content fade-in-up">
                        <h1 class="floating">Next-Generation Inventory Management</h1>
                        <p class="hero-subtitle">Transform your business operations with our enterprise-grade inventory management platform. Designed for modern businesses that demand excellence, efficiency, and scalability.</p>
                        <div class="hero-actions">
                            <a href="login.php" class="btn btn-hero-primary">🔐 Access Your Account</a>
                            <a href="register.php" class="btn btn-hero-secondary">🚀 Start Free Trial</a>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Browse Products Section for Guests -->
            <section class="browse-section" style="padding: 3rem 0; background: rgba(30, 41, 59, 0.5);">
                <div class="container">
                    <div class="text-center fade-in-up">
                        <h2 style="color: #f8fafc; margin-bottom: 1rem;">🛍️ Browse Our Products</h2>
                        <p style="color: #cbd5e1; font-size: 1.1rem; margin-bottom: 2rem;">Explore our product catalog without signing up. Login only when you're ready to purchase!</p>
                        <a href="product-list.php" class="btn btn-hero-primary" style="font-size: 1.2rem; padding: 1rem 2.5rem;">
                            🔍 View All Products
                        </a>
                    </div>
                </div>
            </section>

            <!-- Features Section -->
            <section class="features">
                <div class="container">
                    <div class="features-header fade-in-up">
                        <h2>Three Powerful Business Solutions</h2>
                        <p>Choose the perfect role for your business needs. Our platform adapts to your requirements with specialized features and interfaces designed for maximum productivity.</p>
                    </div>
                    <div class="features-grid">
                        <div class="feature-card fade-in-up">
                            <div class="feature-icon floating">👑</div>
                            <h3>Enterprise Admin</h3>
                            <p>Complete system control with advanced analytics, comprehensive user management, and enterprise-grade security features.</p>
                            <ul class="feature-list">
                                <li>Advanced Analytics Dashboard</li>
                                <li>User & Role Management</li>
                                <li>System-wide Oversight</li>
                                <li>Business Intelligence</li>
                                <li>Security & Compliance</li>
                                <li>Performance Monitoring</li>
                            </ul>
                        </div>
                        <div class="feature-card fade-in-up">
                            <div class="feature-icon floating">🏭</div>
                            <h3>Professional Supplier</h3>
                            <p>Sophisticated supplier tools with inventory management, sales analytics, and business growth features designed for success.</p>
                            <ul class="feature-list">
                                <li>Product Portfolio Management</li>
                                <li>Inventory Optimization</li>
                                <li>Sales Performance Analytics</li>
                                <li>Order Processing System</li>
                                <li>Customer Insights</li>
                                <li>Revenue Tracking</li>
                            </ul>
                        </div>
                        <div class="feature-card fade-in-up">
                            <div class="feature-icon floating">🛒</div>
                            <h3>Premium Buyer</h3>
                            <p>Enhanced shopping experience with personalized recommendations, advanced search, and comprehensive order management.</p>
                            <ul class="feature-list">
                                <li>Smart Product Discovery</li>
                                <li>Personalized Recommendations</li>
                                <li>Advanced Cart Management</li>
                                <li>Order Tracking & History</li>
                                <li>Wishlist & Favorites</li>
                                <li>Purchase Analytics</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CTA Section -->
            <section class="cta">
                <div class="container">
                    <div class="cta-content fade-in-up">
                        <h2 class="floating">Ready to Transform Your Business?</h2>
                        <p>Join thousands of successful businesses who trust our platform for their inventory management needs. Experience the difference that professional-grade tools can make.</p>
                        <div class="cta-actions">
                            <a href="register.php" class="btn btn-hero-primary">🚀 Start Your Journey</a>
                            <a href="login.php" class="btn btn-hero-secondary">🔐 Sign In Now</a>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>