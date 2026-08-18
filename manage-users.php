<?php
// Start session to access user authentication and role information
// This creates or resumes a session to maintain user state across HTTP requests
// Essential for checking if current user has administrative privileges
session_start();

// Include database connection file to access PDO object
// PDO (PHP Data Objects) provides secure database access with prepared statements
// This file typically contains database credentials and connection configuration
require_once 'config/database.php';

// Check if user is logged in and has admin privileges
// Only admin users should be able to manage other users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Redirect unauthorized users to login page
    // header() sends HTTP redirect response, exit() stops further execution
    header("Location: login.php");
    exit();
}

// Handle Add New User
if (isset($_POST['add_user'])) {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    $errors = [];
    
    // Validation
    if (empty($name) || empty($username) || empty($email) || empty($password)) {
        $errors[] = 'All fields are required';
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
    
    if (!in_array($role, ['admin', 'buyer', 'supplier'])) {
        $errors[] = 'Invalid role selected';
    }
    
    // Check if username or email already exists
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->rowCount() > 0) {
                $errors[] = 'Username or email already exists';
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error occurred';
        }
    }
    
    // Create user if no errors
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, username, email, password, role, status) VALUES (?, ?, ?, ?, ?, 'active')");
            $stmt->execute([$name, $username, $email, $hashed_password, $role]);
            
            $_SESSION['message'] = "User created successfully!";
            $_SESSION['msg_type'] = "success";
        } catch (PDOException $e) {
            $_SESSION['message'] = "Error creating user: " . $e->getMessage();
            $_SESSION['msg_type'] = "danger";
        }
    } else {
        $_SESSION['message'] = implode('. ', $errors);
        $_SESSION['msg_type'] = "danger";
    }
    
    header("Location: manage-users.php");
    exit();
}

// Handle Update User
// This section processes form submissions from the edit user modal
// Validates input data and updates user information in the database
if (isset($_POST['update_user'])) {
    // Extract form data from POST request with sanitization
    $user_id = $_POST['user_id']; // Hidden field containing user ID to update
    $name = trim($_POST['name']); // Remove whitespace from name
    $username = trim($_POST['username']); // Remove whitespace from username
    $email = trim($_POST['email']); // Remove whitespace from email
    $role = $_POST['role']; // User role (admin, buyer, supplier)
    $status = $_POST['status']; // User status (active, inactive)
    
    // Validation
    // Initialize errors array to collect validation messages
    $errors = [];
    
    // Check for empty required fields
    // empty() returns true for null, empty string, 0, false, etc.
    if (empty($name) || empty($username) || empty($email)) {
        $errors[] = 'All fields are required';
    }
    
    // Validate email format using PHP's built-in filter
    // FILTER_VALIDATE_EMAIL checks for proper email structure
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    // Validate role against allowed values to prevent injection
    // in_array() checks if value exists in predefined array
    if (!in_array($role, ['admin', 'buyer', 'supplier'])) {
        $errors[] = 'Invalid role selected';
    }
    
    // Validate status against allowed values
    if (!in_array($status, ['active', 'inactive'])) {
        $errors[] = 'Invalid status selected';
    }
    
    // Check if username or email already exists (excluding current user)
    // This prevents duplicate usernames/emails while allowing user to keep their own
    if (empty($errors)) {
        try {
            // Query to find other users with same username or email
            // Exclude current user (id != ?) to allow keeping existing values
            $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $stmt->execute([$username, $email, $user_id]);
            
            // If any rows returned, duplicate exists
            if ($stmt->rowCount() > 0) {
                $errors[] = 'Username or email already exists';
            }
        } catch (PDOException $e) {
            // Handle database errors gracefully
            $errors[] = 'Database error occurred';
        }
    }
    // Update user if no errors
    // Only proceed with database update if all validation passes
    if (empty($errors)) {
        try {
            // Update user record with new information
            // Prepared statement prevents SQL injection attacks
            // If status is being changed to inactive, set deleted_at timestamp
            if ($status === 'inactive') {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, username = ?, email = ?, role = ?, status = ?, deleted_at = NOW() WHERE id = ?");
                $stmt->execute([$name, $username, $email, $role, $status, $user_id]);
            } else {
                // If status is active, clear deleted_at timestamp
                $stmt = $pdo->prepare("UPDATE users SET name = ?, username = ?, email = ?, role = ?, status = ?, deleted_at = NULL WHERE id = ?");
                $stmt->execute([$name, $username, $email, $role, $status, $user_id]);
            }
            
            // Set success message in session for display after redirect
            $_SESSION['message'] = "User updated successfully!";
            $_SESSION['msg_type'] = "success"; // Bootstrap alert class
        } catch (PDOException $e) {
            // Handle database update errors
            $_SESSION['message'] = "Error updating user: " . $e->getMessage();
            $_SESSION['msg_type'] = "danger";
        }
    } else {
        // Combine all validation errors into single message
        // implode() joins array elements with specified separator
        //The implode() function converts an array into a string by 
        // joining all array elements with a specified separator. In this case:
        $_SESSION['message'] = implode('. ', $errors);
        $_SESSION['msg_type'] = "danger";
    }
    
    // Redirect back to user management page to show updated list
    header("Location: manage-users.php");
    exit();
}

// Determine which users to show based on filter
// This creates toggle functionality between active and inactive users
// URL parameter 'show_inactive=1' switches to inactive view
$show_inactive = isset($_GET['show_inactive']) && $_GET['show_inactive'] == '1';

// Fetch users based on current view (active or inactive)
// Complex query joins user data with profile information from related tables
try {
    if ($show_inactive) {
        // Show only inactive users
        // LEFT JOIN gets profile data even if profile doesn't exist
        // ORDER BY deleted_at DESC shows most recently deactivated first
        $stmt = $pdo->query("
            SELECT u.id, u.name, u.username, u.email, u.role, u.status, u.created_at, u.deleted_at,
                   bp.address as buyer_address, bp.phone as buyer_phone,
                   sp.company_name, sp.address as supplier_address, sp.phone as supplier_phone
            FROM users u
            LEFT JOIN buyer_profile bp ON u.id = bp.user_id
            LEFT JOIN supplier_profile sp ON u.id = sp.user_id
            WHERE u.status = 'inactive'
            ORDER BY u.deleted_at DESC
        ");
    } else {
        // Show only active users
        // Similar query structure but filters for active users only
        // ORDER BY id DESC shows newest users first
        $stmt = $pdo->query("
            SELECT u.id, u.name, u.username, u.email, u.role, u.status, u.created_at,
                   bp.address as buyer_address, bp.phone as buyer_phone,
                   sp.company_name, sp.address as supplier_address, sp.phone as supplier_phone
            FROM users u
            LEFT JOIN buyer_profile bp ON u.id = bp.user_id
            LEFT JOIN supplier_profile sp ON u.id = sp.user_id
            WHERE u.status = 'active'
            ORDER BY u.id DESC
        ");
    }
    // Fetch all matching users as associative array
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle database errors gracefully - set empty array as fallback
    $users = [];
    $_SESSION['message'] = "Error fetching users: " . $e->getMessage();
    $_SESSION['msg_type'] = "danger";
}

// Get counts for both active and inactive users
// These counts are displayed in navigation buttons to show available items
try {
    // Count active users for "View Active Users" button
    $active_count = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
    // Count inactive users for "View Inactive Users" button
    $inactive_count = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'inactive'")->fetchColumn();
} catch (PDOException $e) {
    // Set counts to zero if database error occurs
    $active_count = 0;
    $inactive_count = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <!-- Bootstrap 5 CSS framework for responsive design and UI components -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome icons library for UI icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Unified Dashboard CSS -->
    <link rel="stylesheet" href="css/professional-admin.css?v=<?php echo time(); ?>">
    <style>
        /* Professional Manage Users CSS */
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
                linear-gradient(rgba(99, 102, 241, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(99, 102, 241, 0.03) 1px, transparent 1px);
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
            border-bottom: 1px solid rgba(99, 102, 241, 0.2) !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
        }
        
        .navbar-brand {
            color: #f8fafc !important;
            font-weight: 800 !important;
            font-size: 1.5rem !important;
        }
        
        .navbar-brand i {
            color: #6366f1 !important;
            filter: drop-shadow(0 0 8px rgba(99, 102, 241, 0.4)) !important;
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
            background: rgba(99, 102, 241, 0.1) !important;
            border-color: rgba(99, 102, 241, 0.2) !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.05em !important;
            font-size: 0.875rem !important;
            padding: 1rem 0.75rem !important;
        }
        
        .table th i {
            color: #6366f1 !important;
            margin-right: 0.5rem !important;
        }
        
        .table td {
            color: #f8fafc !important;
            border-color: rgba(255, 255, 255, 0.05) !important;
            padding: 1rem 0.75rem !important;
            vertical-align: middle !important;
        }
        
        .table-hover tbody tr:hover {
            background: rgba(99, 102, 241, 0.08) !important;
            color: #f8fafc !important;
            transform: translateX(2px) !important;
            transition: all 0.3s ease !important;
        }
        
        .table-hover tbody tr:hover td {
            color: #f8fafc !important;
        }
        /* CRITICAL TEXT VISIBILITY FIXES */
        
        /* Force text to be visible except in form inputs */
        *:not(input):not(select):not(textarea):not(option), 
        *:not(input):not(select):not(textarea):not(option)::before, 
        *:not(input):not(select):not(textarea):not(option)::after {
            color: #f8fafc !important;
        }
        
        /* Form field styling */
        .modal input.form-control,
        .modal select.form-control,
        .modal textarea.form-control,
        #addUserModal input.form-control,
        #addUserModal select.form-control,
        #editUserModal input.form-control,
        #editUserModal select.form-control {
            background: #ffffff !important;
            color: #1f2937 !important;
            border: 1px solid #d1d5db !important;
        }
        
        .modal label.form-label,
        #addUserModal label.form-label,
        #editUserModal label.form-label {
            color: #1f2937 !important;
            font-weight: 600 !important;
        }
        
        .modal-content {
            background: #ffffff !important;
            color: #1f2937 !important;
        }
        
        .modal-header {
            background: #f3f4f6 !important;
            border-bottom: 1px solid #e5e7eb !important;
        }
        
        .modal-title {
            color: #1f2937 !important;
        }
        
        .modal-body {
            background: #ffffff !important;
        }
        
        .modal-body .alert {
            color: #1f2937 !important;
        }
        
        .modal-footer {
            background: #f3f4f6 !important;
            border-top: 1px solid #e5e7eb !important;
        }
        
        /* Ensure small text in modals is visible */
        .modal small.text-muted {
            color: #6b7280 !important;
        }
        
        /* Force table text visibility */
        .table, .table *, .table td, .table th {
            color: #f8fafc !important;
            background: transparent !important;
        }
        
        /* Force table header visibility */
        .table thead th {
            color: #f8fafc !important;
            background: rgba(99, 102, 241, 0.1) !important;
            font-weight: 700 !important;
        }
        
        /* Force table body text visibility */
        .table tbody td {
            color: #f8fafc !important;
            font-size: 0.95rem !important;
        }
        
        /* Force user name visibility */
        .table .fw-bold, .table strong {
            color: #f8fafc !important;
            font-weight: 700 !important;
        }
        
        /* Force small text visibility */
        .table small, .table .text-muted {
            color: #cbd5e1 !important;
        }
        
        /* Force badge text visibility */
        .badge {
            color: white !important;
            font-weight: 600 !important;
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
        
        /* Force button text visibility */
        .btn {
            color: white !important;
            font-weight: 600 !important;
        }
        
        /* Custom CSS for user avatar circles */
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(45deg, #6366f1, #4f46e5);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white !important;
            font-weight: bold;
            margin-right: 12px;
        }
        
        /* User info styling */
        .user-info .fw-bold {
            color: #f8fafc !important;
            font-weight: 700 !important;
        }
        
        .user-info .text-muted {
            color: #cbd5e1 !important;
        }
        
        /* Styling for inactive user rows */
        .inactive-row {
            background-color: rgba(251, 191, 36, 0.1) !important;
            opacity: 0.8;
        }
        
        .inactive-row td {
            color: #cbd5e1 !important;
        }
        
        /* Role badge styling */
        .role-badge {
            font-size: 0.75rem;
            color: white !important;
            font-weight: 600 !important;
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
        .d-flex .fw-bold {
            color: #f8fafc !important;
        }
        
        .d-flex small {
            color: #cbd5e1 !important;
        }
        
        /* Empty state styling */
        .text-center h4 {
            color: #f8fafc !important;
        }
        
        .text-center p {
            color: #cbd5e1 !important;
        }
        
        /* Force all text in table cells to be visible */
        .table td div {
            color: inherit !important;
        }
        
        .table td div .fw-bold {
            color: #f8fafc !important;
        }
        
        .table td div small {
            color: #cbd5e1 !important;
        }
        
        /* Profile info styling */
        .table td small i {
            color: #6366f1 !important;
            margin-right: 0.25rem !important;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <!-- Header -->
        <!-- Page title with icon for visual identification -->
        <div class="row mb-4">
            <div class="col-md-12">
                <h2><i class="fas fa-users"></i> Manage Users</h2>
                <hr>
            </div>
        </div>

        <!-- Navigation -->
        <!-- Navigation buttons for main actions and view switching -->
        <div class="row mb-3">
            <div class="col-md-4">
                <!-- Back to admin dashboard link -->
                <a href="admin-dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            <div class="col-md-4 text-center">
                <!-- Add New User Button -->
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-user-plus"></i> Add New User
                </button>
            </div>
            <div class="col-md-4 text-end">
                <!-- Toggle between active and inactive users -->
                <?php if (!$show_inactive): ?>
                    <a href="manage-users.php?show_inactive=1" class="btn btn-warning">
                        <i class="fas fa-user-slash"></i> View Inactive Users (<?php echo $inactive_count; ?>)
                    </a>
                <?php else: ?>
                    <a href="manage-users.php" class="btn btn-success">
                        <i class="fas fa-users"></i> View Active Users (<?php echo $active_count; ?>)
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Alert Messages -->
        <!-- Display success/error messages stored in session -->
        <?php if (isset($_SESSION['message'])): ?>
            <!-- Bootstrap alert component with dynamic styling based on message type -->
            <div class="alert alert-<?php echo $_SESSION['msg_type']; ?> alert-dismissible fade show" role="alert">
                <?php 
                // Display message and immediately clear from session to prevent re-display
                echo $_SESSION['message']; 
                unset($_SESSION['message']); // Clear message
                unset($_SESSION['msg_type']); // Clear message type
                ?>
                <!-- Dismissible alert close button -->
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Users Table -->
        <!-- Main content area displaying user list in table format -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <!-- Card header with dynamic title based on current view -->
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>
                            <?php if ($show_inactive): ?>
                                <!-- Title for inactive users view -->
                                <i class="fas fa-user-slash text-warning"></i> Inactive Users (<?php echo count($users); ?> total)
                            <?php else: ?>
                                <!-- Title for active users view -->
                                <i class="fas fa-users text-success"></i> Active Users (<?php echo count($users); ?> total)
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Handle empty user list with appropriate messaging -->
                        <?php if (empty($users)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-users fa-4x text-muted"></i>
                                <h4 class="mt-3">No Users Found</h4>
                                <p>No users in the system.</p>
                            </div>
                        <?php else: ?>
                            <!-- User table when users exist -->
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <!-- Table header with column titles -->
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th> <!-- User database ID -->
                                            <th>User</th> <!-- Name, username, email with avatar -->
                                            <th>Role</th> <!-- User role badge -->
                                            <th>Status</th> <!-- Active/inactive status -->
                                            <th>Profile Info</th> <!-- Role-specific profile data -->
                                            <th>Created</th> <!-- Account creation date -->
                                            <!-- Conditional column for deactivation timestamp -->
                                            <?php if ($show_inactive): ?>
                                                <th>Deactivated</th>
                                            <?php endif; ?>
                                            <th>Actions</th> <!-- Edit/delete buttons -->
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Loop through users array to display each user -->
                                        <?php foreach ($users as $user): ?>
                                            <!-- Apply special styling for inactive users -->
                                            <tr <?php echo $show_inactive ? 'class="inactive-row"' : ''; ?>>
                                                <!-- User ID column -->
                                                <td><strong><?php echo $user['id']; ?></strong></td>
                                                <!-- User information column with avatar and details -->
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <!-- Circular avatar with user initials -->
                                                        <div class="user-avatar me-3">
                                                            <!-- Extract first 2 characters of name for avatar -->
                                                            <?php echo strtoupper(substr($user['name'], 0, 2)); ?>
                                                        </div>
                                                        <div>
                                                            <!-- User's full name with XSS protection -->
                                                            <div class="fw-bold"><?php echo htmlspecialchars($user['name']); ?></div>
                                                            <!-- Username with XSS protection -->
                                                            <small class="text-muted"><?php echo htmlspecialchars($user['username']); ?></small><br>
                                                            <!-- Email address with XSS protection -->
                                                            <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <!-- Role badge column with dynamic styling -->
                                                <td>
                                                    <?php
                                                    // Determine badge styling and icon based on user role
                                                    $role_class = '';
                                                    $role_icon = '';
                                                    switch($user['role']) {
                                                        case 'admin':
                                                            $role_class = 'bg-danger'; // Red for admin
                                                            $role_icon = 'fas fa-crown'; // Crown icon
                                                            break;
                                                        case 'supplier':
                                                            $role_class = 'bg-primary'; // Blue for supplier
                                                            $role_icon = 'fas fa-industry'; // Factory icon
                                                            break;
                                                        case 'buyer':
                                                            $role_class = 'bg-success'; // Green for buyer
                                                            $role_icon = 'fas fa-shopping-cart'; // Cart icon
                                                            break;
                                                    }
                                                    ?>
                                                    <!-- Role badge with icon and text -->
                                                    <span class="badge <?php echo $role_class; ?> role-badge">
                                                        <i class="<?php echo $role_icon; ?>"></i> 
                                                        <?php echo ucfirst($user['role']); ?>
                                                    </span>
                                                </td>
                                                <!-- Status badge column -->
                                                <td>
                                                    <?php if ($user['status'] == 'active'): ?>
                                                        <span class="badge bg-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <!-- Profile information column - role-specific data -->
                                                <td>
                                                    <?php if ($user['role'] == 'buyer'): ?>
                                                        <!-- Display buyer profile information -->
                                                        <small class="text-muted">
                                                            <?php if ($user['buyer_address']): ?>
                                                                <!-- Truncated address with home icon -->
                                                                <i class="fas fa-home"></i> <?php echo htmlspecialchars(substr($user['buyer_address'], 0, 30)) . '...'; ?><br>
                                                            <?php endif; ?>
                                                            <?php if ($user['buyer_phone']): ?>
                                                                <!-- Phone number with phone icon -->
                                                                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['buyer_phone']); ?>
                                                            <?php endif; ?>
                                                        </small>
                                                    <?php elseif ($user['role'] == 'supplier'): ?>
                                                        <!-- Display supplier profile information -->
                                                        <small class="text-muted">
                                                            <?php if ($user['company_name']): ?>
                                                                <!-- Company name with building icon -->
                                                                <i class="fas fa-building"></i> <?php echo htmlspecialchars($user['company_name']); ?><br>
                                                            <?php endif; ?>
                                                            <?php if ($user['supplier_phone']): ?>
                                                                <!-- Phone number with phone icon -->
                                                                <i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['supplier_phone']); ?>
                                                            <?php endif; ?>
                                                        </small>
                                                    <?php else: ?>
                                                        <!-- Admin accounts don't have extended profile -->
                                                        <small class="text-muted">Admin Account</small>
                                                    <?php endif; ?>
                                                </td>
                                                <!-- Account creation date column -->
                                                <td>
                                                    <small class="text-muted">
                                                        <!-- Format creation date for display -->
                                                        <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                                    </small>
                                                </td>
                                                <!-- Conditional deactivation date column -->
                                                <?php if ($show_inactive): ?>
                                                    <td>
                                                        <small class="text-muted">
                                                            <!-- Show deactivation date or N/A -->
                                                            <?php echo $user['deleted_at'] ? date('M j, Y', strtotime($user['deleted_at'])) : 'N/A'; ?>
                                                        </small>
                                                    </td>
                                                <?php endif; ?>
                                                <!-- Action buttons column with conditional display -->
                                                <td>
                                                    <!-- Prevent admin from editing/deleting themselves -->
                                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                        <?php if ($show_inactive): ?>
                                                            <!-- Actions for inactive users -->
                                                            <!-- Reactivate user button -->
                                                            <a href="delete-user.php?restore=<?php echo $user['id']; ?>" 
                                                               class="btn btn-sm btn-success" 
                                                               onclick="return confirm('Are you sure you want to reactivate this user?')"
                                                               title="Reactivate User">
                                                                <i class="fas fa-user-check"></i>
                                                            </a>
                                                            <!-- Permanently delete user button -->
                                                            <a href="delete-user.php?hard_delete=<?php echo $user['id']; ?>" 
                                                               class="btn btn-sm btn-danger" 
                                                               onclick="return confirm('Are you sure you want to PERMANENTLY delete this user? This action cannot be undone and will remove all associated data!')"
                                                               title="Permanently Delete">
                                                                <i class="fas fa-times"></i>
                                                            </a>
                                                        <?php else: ?>
                                                            <!-- Actions for active users -->
                                                            <!-- Edit user button (triggers JavaScript modal) -->
                                                            <button class="btn btn-sm btn-primary" 
                                                                    onclick="editUser(
                                                                        <?php echo $user['id']; ?>, 
                                                                        '<?php echo addslashes($user['name']); ?>', 
                                                                        '<?php echo addslashes($user['username']); ?>', 
                                                                        '<?php echo addslashes($user['email']); ?>', 
                                                                        '<?php echo $user['role']; ?>', 
                                                                        '<?php echo $user['status']; ?>'
                                                                    )"
                                                                    title="Edit User">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <!-- Deactivate user button -->
                                                            <a href="delete-user.php?soft_delete=<?php echo $user['id']; ?>" 
                                                               class="btn btn-sm btn-warning" 
                                                               onclick="return confirm('Are you sure you want to deactivate this user?')"
                                                               title="Deactivate User">
                                                                <i class="fas fa-ban"></i>
                                                            </a>
                                                            <!-- Delete user button -->
                                                            <a href="delete-user.php?hard_delete=<?php echo $user['id']; ?>" 
                                                               class="btn btn-sm btn-danger" 
                                                               onclick="return confirm('Are you sure you want to permanently delete this user? This action cannot be undone!')"
                                                               title="Delete User">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <!-- Current user indicator - prevents self-modification -->
                                                        <span class="badge bg-info">Current User</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus"></i> Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="add_name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" name="name" id="add_name" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="add_username" class="form-label">Username</label>
                                    <input type="text" class="form-control" name="username" id="add_username" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="add_email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" name="email" id="add_email" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="add_password" class="form-label">Password</label>
                                    <input type="password" class="form-control" name="password" id="add_password" required minlength="6">
                                    <small class="text-muted">Minimum 6 characters</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="add_role" class="form-label">Role</label>
                                    <select class="form-control" name="role" id="add_role" required>
                                        <option value="">Select Role</option>
                                        <option value="buyer">Buyer</option>
                                        <option value="supplier">Supplier</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>
                                
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Note:</strong> User will be created with active status and can login immediately.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" name="add_user" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Create User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <!-- Bootstrap modal for editing user information -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <!-- Modal header with title and close button -->
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-edit"></i> Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <!-- Form for user editing -->
                <form method="POST">
                    <div class="modal-body">
                        <!-- Hidden field for user ID -->
                        <input type="hidden" name="user_id" id="edit_user_id">
                        
                        <div class="row">
                            <!-- Left column - basic user information -->
                            <div class="col-md-6">
                                <!-- Full name input -->
                                <div class="mb-3">
                                    <label for="edit_name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" name="name" id="edit_name" required>
                                </div>
                                
                                <!-- Username input -->
                                <div class="mb-3">
                                    <label for="edit_username" class="form-label">Username</label>
                                    <input type="text" class="form-control" name="username" id="edit_username" required>
                                </div>
                                
                                <!-- Email address input -->
                                <div class="mb-3">
                                    <label for="edit_email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" name="email" id="edit_email" required>
                                </div>
                            </div>
                            
                            <!-- Right column - role and status selection -->
                            <div class="col-md-6">
                                <!-- Role selection dropdown -->
                                <div class="mb-3">
                                    <label for="edit_role" class="form-label">Role</label>
                                    <select class="form-control" name="role" id="edit_role" required>
                                        <option value="buyer">Buyer</option>
                                        <option value="supplier">Supplier</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>
                                
                                <!-- Status selection dropdown -->
                                <div class="mb-3">
                                    <label for="edit_status" class="form-label">Status</label>
                                    <select class="form-control" name="status" id="edit_status" required>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                                
                                <!-- Warning notice about role changes -->
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Note:</strong> Changing user role may affect their access to features and data.
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Modal footer with action buttons -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" name="update_user" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JavaScript bundle for modal and other interactive components -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Check if we should open the add user modal
        if (localStorage.getItem('openAddUserModal') === 'true') {
            localStorage.removeItem('openAddUserModal');
            setTimeout(function() {
                var addModal = new bootstrap.Modal(document.getElementById('addUserModal'));
                addModal.show();
            }, 500);
        }
        
        // Function to populate edit user modal with current user data
        // Called when edit button is clicked on any user row
        function editUser(id, name, username, email, role, status) {
            // Fill form fields with current user data
            document.getElementById('edit_user_id').value = id; // Hidden user ID field
            document.getElementById('edit_name').value = name; // User's full name
            document.getElementById('edit_username').value = username; // Username
            document.getElementById('edit_email').value = email; // Email address
            document.getElementById('edit_role').value = role; // User role dropdown
            document.getElementById('edit_status').value = status; // Status dropdown
            
            // Show the modal using Bootstrap's JavaScript API
            var modal = new bootstrap.Modal(document.getElementById('editUserModal'));
            modal.show();
        }
    </script>
</body>
</html>
