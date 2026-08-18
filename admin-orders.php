<?php
// Start session to access $_SESSION superglobal
session_start();
// Include file to access $pdo database object
require_once 'config/database.php';

// Check session variables with isset() and logical operators
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Send HTTP redirect header
    header('Location: login.php');
    // Stop script execution
    exit();
}

// Try-catch block for exception handling
try {
    // PDO query() method executes SQL with multiple JOINs
    $stmt = $pdo->query("
        SELECT 
            o.id,
            o.total_amount,          -- Total monetary value of the order
            o.status,                -- Order status (pending, completed, cancelled, etc.)
            o.created_at,            -- Timestamp when order was placed
            u.username as buyer_name, -- Customer's username for display
            
            -- GROUP_CONCAT: Combines multiple order items into a single readable string
            -- Format: 'Product Name (Quantity × Price)' separated by newlines
            -- This creates a preview of all items in the order for quick viewing
            GROUP_CONCAT(
                CONCAT(p.name, ' (', oi.quantity, ' × $', oi.price, ')')
                SEPARATOR '\n'
            ) as items,
            
            -- COUNT: Total number of individual items in this order
            -- Useful for displaying item count badges and order complexity
            COUNT(oi.id) as item_count
            
        FROM orders o 
        
        -- INNER JOIN: Get customer information (required - every order must have a user)
        JOIN users u ON o.user_id = u.id 
        
        -- LEFT JOIN: Get order items (optional - handles orders with no items gracefully)
        -- LEFT JOIN ensures orders appear even if they have no associated items
        LEFT JOIN order_items oi ON o.id = oi.order_id 
        
        -- LEFT JOIN: Get product details for each order item
        -- LEFT JOIN handles cases where products might be deleted but orders remain
        LEFT JOIN products p ON oi.product_id = p.id 
        GROUP BY o.id 
        ORDER BY o.created_at DESC
    ");
    
    // fetchAll() method returns array of associative arrays
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Second PDO query with SQL aggregate functions
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_orders,
            -- SQL CASE statements for conditional counting
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
            -- SQL SUM() and AVG() aggregate functions
            SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END) as total_revenue,
            AVG(CASE WHEN status = 'completed' THEN total_amount ELSE NULL END) as avg_order_value
        FROM orders
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Catch PDOException class for database errors
} catch (PDOException $e) {
    // Assign empty array to variable
    $orders = [];
    
    // Assign associative array with default values
    $stats = [
        'total_orders' => 0,
        'completed_orders' => 0,
        'pending_orders' => 0,
        'cancelled_orders' => 0,
        'total_revenue' => 0,
        'avg_order_value' => 0
    ];
    
    // Optional: error_log() function writes to server error log
    // error_log("Admin Orders Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Inventory Management System</title>
    <!-- Bootstrap 5 CSS framework for responsive design and UI components -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome icons library for UI icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Unified Dashboard CSS -->
    <link rel="stylesheet" href="css/professional-admin.css?v=<?php echo time(); ?>">
    <style>
        /* Professional Admin Orders CSS */
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%) !important;
            background-attachment: fixed !important;
            color: #f8fafc !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
            min-height: 100vh !important;
        }
        
        /* Professional Grid Background */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(59, 130, 246, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(59, 130, 246, 0.03) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
            z-index: 0;
        }
        
        .container-fluid {
            position: relative;
            z-index: 1;
        }
        
        /* Navigation Styling */
        .navbar-custom {
            background: rgba(15, 23, 42, 0.9) !important;
            backdrop-filter: blur(20px) !important;
            border-bottom: 1px solid rgba(59, 130, 246, 0.2) !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
        }
        
        .navbar-brand {
            color: #f8fafc !important;
            font-weight: 800 !important;
            font-size: 1.5rem !important;
        }
        
        .navbar-brand i {
            color: #f59e0b !important;
            filter: drop-shadow(0 0 8px rgba(245, 158, 11, 0.4)) !important;
        }
        
        /* Page Header */
        .page-header {
            background: rgba(30, 41, 59, 0.95) !important;
            backdrop-filter: blur(20px) !important;
            border-radius: 1rem !important;
            padding: 2rem !important;
            margin-bottom: 2rem !important;
            border: 1px solid rgba(245, 158, 11, 0.2) !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
        }
        
        .page-header h1 {
            color: #f8fafc !important;
            font-size: 2.25rem !important;
            font-weight: 800 !important;
            margin-bottom: 0.5rem !important;
        }
        
        .page-header h1 i {
            color: #f59e0b !important;
            filter: drop-shadow(0 0 8px rgba(245, 158, 11, 0.4)) !important;
        }
        
        /* Enhanced Statistics Cards */
        .stats-grid {
            display: grid !important;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)) !important;
            gap: 3rem !important;
            margin-bottom: 4rem !important;
            padding: 0 2rem !important;
        }
        
        .stat-card {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.95) 0%, rgba(51, 65, 85, 0.95) 100%) !important;
            backdrop-filter: blur(20px) !important;
            border-radius: 1.5rem !important;
            padding: 2.5rem 2rem !important;
            border: 1px solid rgba(245, 158, 11, 0.3) !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
            text-align: center !important;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
            position: relative !important;
            overflow: hidden !important;
        }
        
        .stat-card::before {
            content: '' !important;
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            height: 6px !important;
            background: linear-gradient(90deg, #f59e0b 0%, #d97706 50%, #b45309 100%) !important;
            border-radius: 1.5rem 1.5rem 0 0 !important;
        }
        
        .stat-card::after {
            content: '' !important;
            position: absolute !important;
            top: -50% !important;
            right: -50% !important;
            width: 100% !important;
            height: 100% !important;
            background: radial-gradient(circle, rgba(245, 158, 11, 0.05) 0%, transparent 70%) !important;
            opacity: 0 !important;
            transition: opacity 0.4s ease !important;
            z-index: 1 !important;
            pointer-events: none !important;
        }
        
        .stat-card:hover {
            transform: translateY(-8px) scale(1.02) !important;
            box-shadow: 0 25px 50px -12px rgba(245, 158, 11, 0.25), 0 0 30px rgba(245, 158, 11, 0.3) !important;
            border-color: rgba(245, 158, 11, 0.6) !important;
        }
        
        .stat-card:hover::after {
            opacity: 0.3 !important;
        }
        
        .stat-card h3 {
            color: #cbd5e1 !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
            font-size: 0.875rem !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.1em !important;
            margin-bottom: 1.5rem !important;
            position: relative !important;
            z-index: 2 !important;
        }
        
        .stat-value {
            color: #f8fafc !important;
            font-size: 3rem !important;
            font-weight: 800 !important;
            line-height: 1 !important;
            margin-bottom: 0.75rem !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
            position: relative !important;
            z-index: 10 !important;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3) !important;
        }
        
        .stat-card small {
            color: #94a3b8 !important;
            font-size: 0.8rem !important;
            font-weight: 500 !important;
            position: relative !important;
            z-index: 10 !important;
            display: block !important;
            margin-top: 0.5rem !important;
            margin: 0 !important;
        }
        
        /* Card Type Specific Styling */
        .stat-card.total {
            border-color: rgba(59, 130, 246, 0.3) !important;
        }
        
        .stat-card.total::before {
            background: linear-gradient(90deg, #3b82f6 0%, #1d4ed8 50%, #1e40af 100%) !important;
        }
        
        .stat-card.total .stat-value {
            color: #60a5fa !important;
        }
        
        .stat-card.total:hover {
            box-shadow: 0 25px 50px -12px rgba(59, 130, 246, 0.25), 0 0 30px rgba(59, 130, 246, 0.3) !important;
            border-color: rgba(59, 130, 246, 0.6) !important;
        }
        
        .stat-card.completed {
            border-color: rgba(16, 185, 129, 0.3) !important;
        }
        
        .stat-card.completed::before {
            background: linear-gradient(90deg, #10b981 0%, #059669 50%, #047857 100%) !important;
        }
        
        .stat-card.completed .stat-value {
            color: #34d399 !important;
        }
        
        .stat-card.completed:hover {
            box-shadow: 0 25px 50px -12px rgba(16, 185, 129, 0.25), 0 0 30px rgba(16, 185, 129, 0.3) !important;
            border-color: rgba(16, 185, 129, 0.6) !important;
        }
        
        .stat-card.pending {
            border-color: rgba(245, 158, 11, 0.3) !important;
        }
        
        .stat-card.pending::before {
            background: linear-gradient(90deg, #f59e0b 0%, #d97706 50%, #b45309 100%) !important;
        }
        
        .stat-card.pending .stat-value {
            color: #fbbf24 !important;
        }
        
        .stat-card.pending:hover {
            box-shadow: 0 25px 50px -12px rgba(245, 158, 11, 0.25), 0 0 30px rgba(245, 158, 11, 0.3) !important;
            border-color: rgba(245, 158, 11, 0.6) !important;
        }
        
        .stat-card.cancelled {
            border-color: rgba(239, 68, 68, 0.3) !important;
        }
        
        .stat-card.cancelled::before {
            background: linear-gradient(90deg, #ef4444 0%, #dc2626 50%, #b91c1c 100%) !important;
        }
        
        .stat-card.cancelled .stat-value {
            color: #f87171 !important;
        }
        
        .stat-card.cancelled:hover {
            box-shadow: 0 25px 50px -12px rgba(239, 68, 68, 0.25), 0 0 30px rgba(239, 68, 68, 0.3) !important;
            border-color: rgba(239, 68, 68, 0.6) !important;
        }
        
        .stat-card.revenue {
            border-color: rgba(16, 185, 129, 0.3) !important;
        }
        
        .stat-card.revenue::before {
            background: linear-gradient(90deg, #10b981 0%, #059669 50%, #047857 100%) !important;
        }
        
        .stat-card.revenue .stat-value {
            color: #34d399 !important;
        }
        
        .stat-card.revenue:hover {
            box-shadow: 0 25px 50px -12px rgba(16, 185, 129, 0.25), 0 0 30px rgba(16, 185, 129, 0.3) !important;
            border-color: rgba(16, 185, 129, 0.6) !important;
        }
        
        .stat-card.average {
            border-color: rgba(99, 102, 241, 0.3) !important;
        }
        
        .stat-card.average::before {
            background: linear-gradient(90deg, #6366f1 0%, #4f46e5 50%, #4338ca 100%) !important;
        }
        
        .stat-card.average .stat-value {
            color: #818cf8 !important;
        }
        
        .stat-card.average:hover {
            box-shadow: 0 25px 50px -12px rgba(99, 102, 241, 0.25), 0 0 30px rgba(99, 102, 241, 0.3) !important;
            border-color: rgba(99, 102, 241, 0.6) !important;
        }
        
        /* Table Container */
        .table-container {
            background: rgba(30, 41, 59, 0.95) !important;
            backdrop-filter: blur(20px) !important;
            border-radius: 1rem !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
            overflow: hidden !important;
            margin-bottom: 2rem !important;
        }
        
        /* Table Styling */
        .table {
            color: #f8fafc !important;
            background: transparent !important;
            margin-bottom: 0 !important;
        }
        
        .table th {
            color: #f8fafc !important;
            background: rgba(245, 158, 11, 0.1) !important;
            border-color: rgba(245, 158, 11, 0.2) !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
            font-size: 0.875rem !important;
            padding: 1rem 0.75rem !important;
        }
        
        .table th i {
            color: #f59e0b !important;
            margin-right: 0.5rem !important;
        }
        
        .table td {
            color: #f8fafc !important;
            border-color: rgba(255, 255, 255, 0.05) !important;
            padding: 1rem 0.75rem !important;
            vertical-align: middle !important;
        }
        
        .table-hover tbody tr:hover {
            background: rgba(245, 158, 11, 0.08) !important;
            color: #f8fafc !important;
            transform: translateX(2px) !important;
            transition: all 0.3s ease !important;
        }
        
        .table-hover tbody tr:hover td {
            color: #f8fafc !important;
        }
        
        /* Order Details */
        .table td strong {
            color: #f8fafc !important;
            font-weight: 700 !important;
            font-size: 1rem !important;
        }
        
        .table td .text-muted {
            color: #cbd5e1 !important;
            font-size: 0.875rem !important;
        }
        
        /* Amount Styling */
        .amount {
            color: #10b981 !important;
            font-weight: 700 !important;
            font-size: 1.1rem !important;
        }
        
        /* CRITICAL TEXT VISIBILITY FIXES */
        
        /* Force ALL text to be visible */
        *, *::before, *::after {
            color: #f8fafc !important;
        }
        
        /* Force table text visibility */
        .table, .table *, .table td, .table th {
            color: #f8fafc !important;
            background: transparent !important;
        }
        
        /* Force table body text visibility */
        .table tbody td {
            color: #f8fafc !important;
            font-size: 0.95rem !important;
        }
        
        /* Force order ID visibility */
        .table td:first-child {
            color: #f8fafc !important;
            font-weight: 700 !important;
        }
        
        /* Force customer name visibility */
        .table .customer-info strong {
            color: #f8fafc !important;
            font-weight: 700 !important;
        }
        
        .table .customer-info small {
            color: #cbd5e1 !important;
        }
        
        /* Force order items visibility */
        .table .order-items {
            color: #f8fafc !important;
        }
        
        .table .order-items strong {
            color: #f8fafc !important;
        }
        
        .table .order-items small {
            color: #cbd5e1 !important;
        }
        
        /* Force card text visibility */
        .card, .card-body, .card-header {
            background: rgba(30, 41, 59, 0.95) !important;
            color: #f8fafc !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
        }
        
        .card-header h5 {
            color: #f8fafc !important;
        }
        
        /* Force alert text visibility */
        .alert {
            color: #1f2937 !important;
        }
        
        /* Override Bootstrap text utilities */
        .text-success { color: #10b981 !important; }
        .text-warning { color: #f59e0b !important; }
        .text-danger { color: #ef4444 !important; }
        .text-primary { color: #3b82f6 !important; }
        .text-secondary { color: #6b7280 !important; }
        .text-muted { color: #cbd5e1 !important; }
        .text-dark { color: #f8fafc !important; }
        .text-light { color: #f8fafc !important; }
        
        /* Force visibility for specific elements */
        .fw-bold, strong, b {
            color: #f8fafc !important;
            font-weight: 700 !important;
        }
        
        small {
            color: #cbd5e1 !important;
        }
        
        /* Empty state styling */
        .text-center h4 {
            color: #f8fafc !important;
        }
        
        .text-center p {
            color: #cbd5e1 !important;
        }
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            
            /* Order Status Colors */
            --status-pending: #f59e0b;
            --status-completed: #10b981;
            --status-cancelled: #ef4444;
            --status-processing: #3b82f6;
            --status-shipped: #8b5cf6;
            --status-refunded: #6b7280;
            
            /* Neutral Colors */
            --white: #ffffff;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            
            /* Gradients */
            --primary-gradient: linear-gradient(135deg, #2c8cfb 0%, #5cacfa 100%);
            --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
            --admin-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --orders-gradient: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
            
            /* Spacing */
            --space-xs: 0.25rem;
            --space-sm: 0.5rem;
            --space-md: 1rem;
            --space-lg: 1.5rem;
            --space-xl: 2rem;
            --space-2xl: 3rem;
            --space-3xl: 4rem;
            
            /* Border Radius */
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            --radius-2xl: 1.5rem;
            
            /* Shadows */
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px rgba(0, 0, 0, 0.1);
            --admin-shadow: 0 10px 25px rgba(44, 140, 251, 0.15);
            
            /* Transitions */
            --transition-fast: 0.15s ease-in-out;
            --transition-normal: 0.3s ease-in-out;
            --transition-slow: 0.5s ease-in-out;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', sans-serif;
            background: var(--gray-50);
            color: var(--gray-800);
            line-height: 1.6;
            font-size: 14px;
            -webkit-font-smoothing: antialiased;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 var(--space-lg);
        }

        /* Header - Admin Theme Fixed */
        header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%) !important;
            padding: 1.5rem 0 !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
            position: sticky !important;
            top: 0 !important;
            z-index: 1000 !important;
            border-bottom: 3px solid #f59e0b !important;
        }

        header .container {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            max-width: 1200px !important;
            margin: 0 auto !important;
            padding: 0 1rem !important;
        }

        header h1 {
            color: white !important;
            font-size: 1.75rem !important;
            font-weight: 700 !important;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3) !important;
            display: flex !important;
            align-items: center !important;
            letter-spacing: -0.025em !important;
            margin: 0 !important;
        }

        header h1::before {
            content: "🏭" !important;
            margin-right: 0.75rem !important;
            font-size: 1.5rem !important;
        }

        /* Navigation - Fixed Styling */
        nav {
            display: flex !important;
            gap: 1rem !important;
            align-items: center !important;
            flex-wrap: wrap !important;
        }

        nav a {
            color: rgba(255, 255, 255, 0.9) !important;
            text-decoration: none !important;
            padding: 0.75rem 1rem !important;
            border-radius: 0.5rem !important;
            font-weight: 500 !important;
            font-size: 0.875rem !important;
            transition: all 0.3s ease !important;
            position: relative !important;
            overflow: hidden !important;
            background: rgba(255, 255, 255, 0.1) !important;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
            backdrop-filter: blur(10px) !important;
        }

        nav a::before {
            content: '' !important;
            position: absolute !important;
            top: 0 !important;
            left: -100% !important;
            width: 100% !important;
            height: 100% !important;
            background: rgba(245, 158, 11, 0.3) !important;
            transition: left 0.3s ease !important;
            z-index: -1 !important;
        }

        nav a:hover {
            color: white !important;
            background: rgba(245, 158, 11, 0.2) !important;
            transform: translateY(-1px) !important;
            border-color: rgba(245, 158, 11, 0.4) !important;
            box-shadow: 0 4px 6px -1px rgba(245, 158, 11, 0.2) !important;
        }

        nav a:hover::before {
            left: 0 !important;
        }

        nav span {
            background: rgba(245, 158, 11, 0.2) !important;
            color: white !important;
            padding: 0.75rem 1rem !important;
            border-radius: 0.5rem !important;
            font-weight: 600 !important;
            border: 1px solid rgba(245, 158, 11, 0.4) !important;
            backdrop-filter: blur(10px) !important;
            font-size: 0.875rem !important;
        }

        /* Main Content */
        main {
            padding: var(--space-2xl) 0;
            min-height: calc(100vh - 120px);
        }

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--space-2xl);
            background: var(--white);
            padding: var(--space-xl);
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-md);
            border-left: 6px solid var(--primary-color);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
        }

        .page-header h2 {
            font-size: 2.25rem;
            font-weight: 700;
            color: var(--gray-900);
            display: flex;
            align-items: center;
            gap: var(--space-md);
        }

        .page-header h2::before {
            content: "📋";
            font-size: 2rem;
        }

        /* Statistics Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--space-lg);
            margin-bottom: var(--space-2xl);
        }

        .stat-card {
            background: var(--white);
            padding: var(--space-xl);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            text-align: center;
            transition: all var(--transition-normal);
            position: relative;
            overflow: hidden;
            border-top: 4px solid var(--primary-color);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-gradient);
            transform: scaleY(0);
            transform-origin: bottom;
            transition: transform var(--transition-normal);
        }

        .stat-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-xl);
        }

        .stat-card:hover::before {
            transform: scaleY(1);
        }

        .stat-card h3 {
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: var(--space-md);
            color: var(--gray-700);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: var(--space-sm);
            color: var(--primary-color);
            line-height: 1;
        }

        .stat-card small {
            color: var(--gray-500);
            font-size: 0.75rem;
            font-weight: 500;
        }

        /* Card Variants */
        .stat-card.total {
            border-top-color: var(--info-color);
        }

        .stat-card.total .stat-value {
            color: var(--info-color);
        }

        .stat-card.total::after {
            content: "📊";
            position: absolute;
            top: var(--space-md);
            right: var(--space-md);
            font-size: 2rem;
            opacity: 0.15;
        }

        .stat-card.completed {
            border-top-color: var(--success-color);
        }

        .stat-card.completed .stat-value {
            color: var(--success-color);
        }

        .stat-card.completed::after {
            content: "✅";
            position: absolute;
            top: var(--space-md);
            right: var(--space-md);
            font-size: 2rem;
            opacity: 0.15;
        }

        .stat-card.pending {
            border-top-color: var(--warning-color);
        }

        .stat-card.pending .stat-value {
            color: var(--warning-color);
        }

        .stat-card.pending::after {
            content: "⏳";
            position: absolute;
            top: var(--space-md);
            right: var(--space-md);
            font-size: 2rem;
            opacity: 0.15;
        }

        .stat-card.cancelled {
            border-top-color: var(--danger-color);
        }

        .stat-card.cancelled .stat-value {
            color: var(--danger-color);
        }

        .stat-card.cancelled::after {
            content: "❌";
            position: absolute;
            top: var(--space-md);
            right: var(--space-md);
            font-size: 2rem;
            opacity: 0.15;
        }

        .stat-card.revenue {
            border-top-color: var(--success-color);
        }

        .stat-card.revenue .stat-value {
            color: var(--success-color);
        }

        .stat-card.revenue::after {
            content: "💰";
            position: absolute;
            top: var(--space-md);
            right: var(--space-md);
            font-size: 2rem;
            opacity: 0.15;
        }

        .stat-card.average {
            border-top-color: var(--info-color);
        }

        .stat-card.average .stat-value {
            color: var(--info-color);
        }

        .stat-card.average::after {
            content: "📈";
            position: absolute;
            top: var(--space-md);
            right: var(--space-md);
            font-size: 2rem;
            opacity: 0.15;
        }

        /* Orders Table Card */
        .orders-table-card {
            background: var(--white);
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            border-left: 6px solid var(--success-color);
            position: relative;
        }

        .orders-table-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--success-gradient);
        }

        .orders-table-header {
            background: var(--orders-gradient);
            padding: var(--space-xl);
            border-bottom: 1px solid var(--gray-200);
        }

        .orders-table-header h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-900);
            display: flex;
            align-items: center;
            gap: var(--space-md);
        }

        .orders-table-header h3::before {
            content: "📦";
            font-size: 1.5rem;
        }

        /* Orders Table */
        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }

        .orders-table th,
        .orders-table td {
            padding: var(--space-lg);
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }

        .orders-table th {
            background: var(--gray-100);
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .orders-table tbody tr {
            transition: all var(--transition-fast);
            position: relative;
        }

        .orders-table tbody tr:hover {
            background: var(--gray-50);
            transform: translateX(2px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .orders-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Order ID Styling */
        .order-id {
            font-weight: 700;
            color: var(--primary-color);
            font-family: 'Monaco', 'Consolas', monospace;
        }

        /* Status Badges - Dark Theme Compatible */
        .status-badge {
            display: inline-flex !important;
            align-items: center !important;
            padding: 0.4rem 0.8rem !important;
            border-radius: 0.375rem !important;
            font-size: 0.75rem !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
            gap: 0.375rem !important;
        }

        .status-badge.pending {
            background: rgba(245, 158, 11, 0.1) !important;
            color: #f59e0b !important;
            border: 1px solid rgba(245, 158, 11, 0.3) !important;
        }

        .status-badge.pending::before {
            content: "⏳";
            margin-right: 0.25rem;
        }

        .status-badge.completed {
            background: rgba(16, 185, 129, 0.1) !important;
            color: #10b981 !important;
            border: 1px solid rgba(16, 185, 129, 0.3) !important;
        }

        .status-badge.completed::before {
            content: "✅";
            margin-right: 0.25rem;
        }

        .status-badge.cancelled {
            background: rgba(239, 68, 68, 0.1) !important;
            color: #ef4444 !important;
            border: 1px solid rgba(239, 68, 68, 0.3) !important;
        }

        .status-badge.cancelled::before {
            content: "❌";
            margin-right: 0.25rem;
        }

        .status-badge.processing {
            background: rgba(59, 130, 246, 0.1) !important;
            color: #3b82f6 !important;
            border: 1px solid rgba(59, 130, 246, 0.3) !important;
        }

        .status-badge.processing::before {
            content: "⚡";
            margin-right: 0.25rem;
        }

        .status-badge.shipped {
            background: rgba(139, 92, 246, 0.2) !important;
            color: #a78bfa !important;
            border: 1px solid rgba(139, 92, 246, 0.5) !important;
        }

        .status-badge.shipped::before {
            content: "🚚";
            margin-right: 0.25rem;
        }

        /* Amount Display */
        .amount {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--success-color);
        }

        /* Date Display */
        .order-date {
            color: var(--gray-600);
            font-size: 0.875rem;
        }

        /* Customer Display */
        .customer-name {
            font-weight: 600;
            color: var(--gray-900);
        }

        /* Items Preview */
        .items-preview {
            font-size: 0.8rem;
            color: var(--gray-600);
            line-height: 1.4;
            max-width: 200px;
            white-space: pre-line;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }

        .item-count {
            background: var(--primary-color);
            color: var(--white);
            padding: var(--space-xs) var(--space-sm);
            border-radius: var(--radius-lg);
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: var(--space-sm);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: var(--space-sm);
            align-items: center;
        }

        /* Button Styling - Dark Theme Compatible */
        .btn {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 0.375rem !important;
            padding: 0.5rem 1rem !important;
            text-decoration: none !important;
            border-radius: 0.375rem !important;
            font-weight: 600 !important;
            font-size: 0.875rem !important;
            transition: all 0.3s ease !important;
            border: none !important;
            cursor: pointer !important;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06) !important;
            white-space: nowrap !important;
            color: white !important;
        }

        .btn:hover {
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
        }

        .btn-view, .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%) !important;
            color: white !important;
        }

        .btn-view:hover, .btn-primary:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%) !important;
            color: white !important;
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.4) !important;
        }

        .btn-edit, .btn-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
            color: white !important;
        }

        .btn-edit:hover, .btn-warning:hover {
            background: linear-gradient(135deg, #d97706 0%, #b45309 100%) !important;
            color: white !important;
            box-shadow: 0 4px 6px -1px rgba(245, 158, 11, 0.4) !important;
        }

        .btn-delete, .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
            color: white !important;
        }

        .btn-delete:hover, .btn-danger:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%) !important;
            color: white !important;
            box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.4) !important;
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            color: white !important;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%) !important;
            color: white !important;
            box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.4) !important;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%) !important;
            color: white !important;
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #4b5563 0%, #374151 100%) !important;
            color: white !important;
            box-shadow: 0 4px 6px -1px rgba(107, 114, 128, 0.4) !important;
        }

        .btn-sm {
            padding: 0.375rem 0.75rem !important;
            font-size: 0.8rem !important;
        }

        /* No Orders State */
        .no-orders {
            text-align: center;
            padding: var(--space-3xl);
            color: var(--gray-500);
            background: var(--white);
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-md);
            border: 2px dashed var(--gray-300);
        }

        .no-orders::before {
            content: "📦";
            font-size: 4rem;
            display: block;
            margin-bottom: var(--space-lg);
            opacity: 0.3;
        }

        .no-orders h3 {
            font-size: 1.5rem;
            margin-bottom: var(--space-md);
            color: var(--gray-600);
        }

        .no-orders p {
            font-size: 1rem;
            line-height: 1.6;
        }

        /* Table Responsive Wrapper */
        .table-wrapper {
            overflow-x: auto;
            background: var(--white);
            border-radius: 0 0 var(--radius-2xl) var(--radius-2xl);
        }

        .table-wrapper::-webkit-scrollbar {
            height: 8px;
        }

        .table-wrapper::-webkit-scrollbar-track {
            background: var(--gray-200);
            border-radius: var(--radius-sm);
        }

        .table-wrapper::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: var(--radius-sm);
        }

        /* Animation Classes */
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .slide-in-up {
            animation: slideInUp 0.5s ease-out;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            }
            
            .orders-table th,
            .orders-table td {
                padding: var(--space-md);
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 var(--space-md);
            }
            
            header .container {
                flex-direction: column;
                gap: var(--space-md);
                text-align: center;
            }
            
            nav {
                justify-content: center;
                flex-wrap: wrap;
            }
            
            .page-header {
                flex-direction: column;
                gap: var(--space-md);
                text-align: center;
            }
            
            .page-header h2 {
                font-size: 1.75rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: var(--space-md);
            }
            
            .orders-table {
                font-size: 0.75rem;
            }
            
            .orders-table th,
            .orders-table td {
                padding: var(--space-sm);
            }
            
            .items-preview {
                max-width: 150px;
                -webkit-line-clamp: 2;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: var(--space-xs);
            }
            
            .btn {
                width: 100%;
                padding: var(--space-xs) var(--space-sm);
                font-size: 0.7rem;
            }
        }

        @media (max-width: 480px) {
            main {
                padding: var(--space-lg) 0;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .stat-card {
                padding: var(--space-lg);
            }
            
            .stat-value {
                font-size: 2rem;
            }
            
            .orders-table-header {
                padding: var(--space-lg);
            }
            
            header h1 {
                font-size: 1.5rem;
            }
        }

        /* Print Styles */
        @media print {
            header nav,
            .btn,
            .action-buttons {
                display: none !important;
            }
            
            body {
                background: white !important;
                color: black !important;
            }
            
            .container {
                max-width: none !important;
                padding: 0 !important;
            }
            
            .stat-card,
            .orders-table-card {
                box-shadow: none !important;
                border: 1px solid #ccc !important;
                break-inside: avoid;
            }
            
            .stats-grid {
                display: block;
            }
            
            .stat-card {
                margin-bottom: 1rem;
                display: inline-block;
                width: 30%;
                margin-right: 3%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- ===================================================================
             HEADER SECTION: Main navigation and branding
             ===================================================================
             Contains system branding, main navigation menu, and user information
             Provides consistent navigation across all admin pages -->
        <header>
            <!-- SYSTEM TITLE: Main branding and identification -->
            <h1>Inventory Management System</h1>
            
            <!-- MAIN NAVIGATION: Links to all major admin sections -->
            <nav>
                <!-- Dashboard link: Return to main admin overview -->
                <a href="admin-dashboard.php">Dashboard</a>
                
                <!-- User Management: Admin tools for managing user accounts -->
                <a href="manage-users.php">Manage Users</a>
                
                <!-- Product Management: Admin tools for managing inventory -->
                <a href="manage-products.php">Manage Products</a>
                
                <!-- Reports: Analytics and business intelligence -->
                <a href="reports.php">Reports</a>
                
                <!-- Echo statement with htmlspecialchars() function -->
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (Admin)</span>
                
                <!-- LOGOUT LINK: Secure session termination -->
                <!-- Redirects to logout.php which destroys session and redirects to login -->
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main class="fade-in">
            <!-- Page Header -->
            <div class="page-header">
                <h2>Manage Orders</h2>
            </div>

            <!-- ===================================================================
                 STATISTICS DASHBOARD: Key Business Metrics Display
                 ===================================================================
                 This section displays critical order statistics in an attractive grid layout
                 Each card shows a different metric with visual styling and animations -->
            <div class="stats-grid">
                
                <!-- TOTAL ORDERS CARD: Shows complete order count across all statuses -->
                <div class="stat-card total slide-in-up">
                    <h3>Total Orders</h3>
                    <!-- Array access with square brackets -->
                    <div class="stat-value"><?php echo $stats['total_orders']; ?></div>
                    <small>All time orders</small>
                </div>

                <div class="stat-card completed slide-in-up" style="animation-delay: 0.1s;">
                    <h3>Completed</h3>
                    <div class="stat-value"><?php echo $stats['completed_orders']; ?></div>
                    <small>Successfully fulfilled</small>
                </div>

                <div class="stat-card pending slide-in-up" style="animation-delay: 0.2s;">
                    <h3>Pending</h3>
                    <div class="stat-value"><?php echo $stats['pending_orders']; ?></div>
                    <small>Awaiting processing</small>
                </div>

                <!-- CANCELLED ORDERS CARD: Shows failed/rejected orders -->
                <div class="stat-card cancelled slide-in-up" style="animation-delay: 0.3s;">
                    <h3>Cancelled</h3>
                    <!-- Display cancelled orders count -->
                    <!-- Helps track order failure rate and customer satisfaction -->
                    <div class="stat-value"><?php echo $stats['cancelled_orders']; ?></div>
                    <small>Cancelled orders</small>
                </div>

                <!-- REVENUE CARD: Shows total monetary value from completed orders -->
                <div class="stat-card revenue slide-in-up" style="animation-delay: 0.4s;">
                    <h3>Total Revenue</h3>
                    <!-- Display formatted revenue amount -->
                    <!-- number_format() adds thousands separators for readability -->
                    <!-- Only includes revenue from completed orders (actual money received) -->
                    <div class="stat-value">₹<?php echo number_format($stats['total_revenue'], 0); ?></div>
                    <small>Completed orders only</small>
                </div>

                <!-- AVERAGE ORDER VALUE CARD: Shows mean order value -->
                <div class="stat-card average slide-in-up" style="animation-delay: 0.5s;">
                    <h3>Avg Order</h3>
                    <!-- Display formatted average order value -->
                    <!-- Helps understand customer spending patterns -->
                    <!-- Calculated from completed orders only for accuracy -->
                    <div class="stat-value">₹<?php echo number_format($stats['avg_order_value'], 0); ?></div>
                    <small>Average order value</small>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="orders-table-card slide-in-up" style="animation-delay: 0.6s;">
                <div class="orders-table-header">
                    <h3>All Orders</h3>
                </div>

                <?php 
                // ===================================================================
                // CONDITIONAL DISPLAY: Handle Empty Orders State
                // ===================================================================
                // Check if orders array is empty and display appropriate content
                if (empty($orders)): ?>
                    <!-- EMPTY STATE: Display when no orders exist -->
                    <div class="no-orders">
                        <h3>No Orders Found</h3>
                        <p>No orders have been placed yet.</p>
                    </div>
                <?php else: ?>
                    <!-- ===================================================================
                         ORDERS TABLE: Display all orders in tabular format
                         ===================================================================
                         This section creates a comprehensive table showing all order details
                         with proper formatting, security measures, and action buttons -->
                    <div class="table-wrapper">
                        <table class="orders-table">
                            <!-- TABLE HEADER: Define column structure -->
                            <thead>
                                <tr>
                                    <th>Order ID</th>      <!-- Unique order identifier -->
                                    <th>Customer</th>      <!-- Customer who placed the order -->
                                    <th>Items</th>         <!-- Products in the order -->
                                    <th>Amount</th>        <!-- Total monetary value -->
                                    <th>Status</th>        <!-- Current order status -->
                                    <th>Date</th>          <!-- When order was placed -->
                                    <th>Actions</th>       <!-- Admin action buttons -->
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // foreach loop iterates through array
                                foreach ($orders as $order): ?>
                                    <tr>
                                        <td>
                                            <!-- String concatenation with . operator -->
                                            <span class="order-id">#<?php echo $order['id']; ?></span>
                                        </td>
                                        
                                        <td>
                                            <!-- htmlspecialchars() prevents XSS attacks -->
                                            <!-- Essential security measure for any user-generated content -->
                                            <span class="customer-name"><?php echo htmlspecialchars($order['buyer_name']); ?></span>
                                        </td>
                                        
                                        <!-- ITEMS COLUMN: Display order items preview -->
                                        <td>
                                            <!-- Display items string from GROUP_CONCAT query -->
                                            <!-- Null coalescing operator (??) provides fallback if items is null -->
                                            <div class="items-preview"><?php echo htmlspecialchars($order['items'] ?? 'No items'); ?></div>
                                            <!-- Display total item count with visual badge -->
                                            <span class="item-count"><?php echo $order['item_count']; ?> items</span>
                                        </td>
                                        
                                        <!-- AMOUNT COLUMN: Display formatted monetary value -->
                                        <td>
                                            <!-- number_format() function with 2 decimal places -->
                                            <span class="amount">₹<?php echo number_format($order['total_amount'], 2); ?></span>
                                        </td>
                                        
                                        <td>
                                            <!-- ucfirst() capitalizes first letter -->
                                            <span class="status-badge <?php echo $order['status']; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        
                                        <td>
                                            <!-- strtotime() converts string to timestamp, date() formats output -->
                                            <div class="order-date"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></div>
                                            <div class="order-date"><?php echo date('g:i A', strtotime($order['created_at'])); ?></div>
                                        </td>
                                        
                                        <!-- ACTIONS COLUMN: Admin action buttons -->
                                        <td>
                                            <div class="action-buttons">
                                                <!-- VIEW BUTTON: Link to detailed order view -->
                                                <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-view">
                                                    👁️ View
                                                </a>
                                                
                                                <!-- EDIT BUTTON: Link to order editing interface -->
                                                <a href="edit-order.php?id=<?php echo $order['id']; ?>" class="btn btn-edit">
                                                    ✏️ Edit
                                                </a>
                                                
                                                <!-- DELETE BUTTON: Link to order deletion with confirmation -->
                                                <a href="delete-order.php?id=<?php echo $order['id']; ?>" 
                                                   class="btn btn-delete" 
                                                   onclick="return confirm('Are you sure you want to delete this order?')">
                                                    🗑️ Delete
                                                </a>
                                                <!-- onclick confirmation prevents accidental deletions -->
                                                <!-- JavaScript confirm() shows browser confirmation dialog -->
                                                <!-- return false cancels the link if user clicks "Cancel" -->
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>