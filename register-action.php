<?php
// Start PHP session to manage user state across pages
// This allows storing user information after successful registration
session_start();

// Include database connection file to access the PDO connection object
// FIXED: Changed from 'config/database.php' - connects to MySQL database
require_once 'config/database.php'; 

// Check if the form was submitted using POST method
// This ensures the script only processes actual form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Sanitize and retrieve basic form data from POST request
    // trim() removes whitespace from beginning and end of strings
    $username = trim($_POST['username']); // Remove extra spaces from username
    $email = trim($_POST['email']); // Clean email input
    $password = $_POST['password']; // Password as-is (will be hashed later)
    $confirm_password = $_POST['confirm_password']; // For validation matching
    $role = $_POST['role']; // Either 'buyer' or 'supplier'

    // Get role-specific profile data based on selected account type
    // Uses null coalescing operator (??) to provide empty string if field doesn't exist
    if ($role === 'buyer') {
        // Retrieve buyer-specific fields from the form
        $buyer_address = trim($_POST['buyer_address'] ?? ''); // Delivery address
        $buyer_phone = trim($_POST['buyer_phone'] ?? ''); // Contact phone number
    } elseif ($role === 'supplier') {
        // Retrieve supplier-specific fields from the form
        $supplier_company = trim($_POST['supplier_company'] ?? ''); // Business name
        $supplier_phone = trim($_POST['supplier_phone'] ?? ''); // Business phone
        $supplier_address = trim($_POST['supplier_address'] ?? ''); // Business address
    }

    // Enhanced Validation System
    // Initialize array to collect all validation errors
    $errors = [];

    // Check for empty required basic fields
    // empty() returns true for null, empty string, 0, false, etc.
    if (empty($username) || empty($email) || empty($password) || empty($role)) {
        $errors[] = 'All basic fields are required'; // Add error to array
    }

    // Verify password confirmation matches original password
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }

    // Enforce minimum password length for security
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }

    // Validate role selection against allowed values
    // in_array() checks if value exists in given array
    if (!in_array($role, ['buyer', 'supplier'])) {
        $errors[] = 'Invalid role selected';
    }

    // Enforce username length constraints (between 3-50 characters)
    if (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = 'Username must be between 3-50 characters';
    }

    // Validate email format using PHP's built-in filter
    // FILTER_VALIDATE_EMAIL checks for proper email structure
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }

    // Role-specific field validation
    // Ensure required profile fields are filled based on account type
    if ($role === 'buyer') {
        // Buyer accounts require address and phone
        if (empty($buyer_address)) {
            $errors[] = 'Address is required for buyer accounts';
        }
        if (empty($buyer_phone)) {
            $errors[] = 'Phone number is required for buyer accounts';
        }
    } elseif ($role === 'supplier') {
        // Supplier accounts require company name, phone, and business address
        if (empty($supplier_company)) {
            $errors[] = 'Company name is required for supplier accounts';
        }
        if (empty($supplier_phone)) {
            $errors[] = 'Phone number is required for supplier accounts';
        }
        if (empty($supplier_address)) {
            $errors[] = 'Business address is required for supplier accounts';
        }
    }

    // Check database for existing username or email to prevent duplicates
    try {
        // Prepare SQL statement to check for existing users
        // Uses prepared statements to prevent SQL injection attacks
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]); // Execute with parameters
        
        // Check if any matching records were found
        if ($stmt->rowCount() > 0) {
            $errors[] = 'Username or email already exists';
        }
    } catch (PDOException $e) {
        // Handle database connection errors gracefully
        $errors[] = 'Database connection error';
        error_log("Database check error: " . $e->getMessage()); // Log actual error for debugging
    }

    // If validation failed, redirect back to registration form with errors
    if (!empty($errors)) {
        // Combine all error messages into single string
        //implode:Join array elements with a string
        $error_message = implode('. ', $errors); // Join errors with periods
        // Redirect back to form with error message in URL
        header('Location: register.php?error=' . urlencode($error_message));
        exit(); // Stop script execution
    }

    // All validation passed - Create account with profile data
    try {
        // Start database transaction for data consistency
        // This ensures all database operations succeed or none do
        $pdo->beginTransaction();

        // Hash password using PHP's secure password hashing
        // PASSWORD_DEFAULT uses bcrypt algorithm (currently most secure)
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user into users table - matching exact database structure
        // FIXED - include 'name' column as required by database schema
        $stmt = $pdo->prepare("INSERT INTO users (name, username, email, password, role, status, created_at) VALUES (?, ?, ?, ?, ?, 'active', NOW())");
        $stmt->execute([$username, $username, $email, $hashed_password, $role]);

        // Get the ID of the newly created user record
        // lastInsertId() returns the auto-increment ID from the last INSERT
        $user_id = $pdo->lastInsertId();

        // Insert role-specific profile data into appropriate tables
        if ($role === 'buyer') {
            // Create buyer profile record linked to user account
            $stmt_buyer = $pdo->prepare("INSERT INTO buyer_profile (user_id, address, phone) VALUES (?, ?, ?)");
            $stmt_buyer->execute([$user_id, $buyer_address, $buyer_phone]);
            
        } elseif ($role === 'supplier') {
            // Create supplier profile record linked to user account
            // FIXED - exact column names matching database schema
            $stmt_supplier = $pdo->prepare("INSERT INTO supplier_profile (user_id, company_name, address, phone) VALUES (?, ?, ?, ?)");
            $stmt_supplier->execute([$user_id, $supplier_company, $supplier_address, $supplier_phone]);
        }

        // Commit the transaction - all database operations successful
        // This makes all changes permanent in the database
        $pdo->commit();

        // Auto-login the user after successful registration
        // Store user information in session for immediate access
        $_SESSION['user_id'] = $user_id; // Store user ID for future requests
        $_SESSION['username'] = $username; // Store username for display
        $_SESSION['role'] = $role; // Store role for permission checking

        // Redirect to main page with success message
        header('Location: index.php?success=' . urlencode('Registration successful! Welcome to the platform.'));
        exit(); // Stop script execution

    } catch (PDOException $e) {
        // Handle any database errors during registration process
        // Rollback transaction to undo any partial changes
        $pdo->rollback();
        
        // Log the actual error details for debugging purposes
        error_log("Registration transaction error: " . $e->getMessage());
        
        // Show user-friendly error message (don't expose technical details)
        header('Location: register.php?error=' . urlencode('Registration failed. Please try again.'));
        exit();
    }

} else {
    // Handle non-POST requests (direct access to this file)
    // Redirect back to registration form if accessed incorrectly
    header('Location: register.php');
    exit();
}
?>
