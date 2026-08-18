<?php
// Start PHP session to manage user authentication state
// This allows storing user information after successful login
session_start();

// Include database connection file to access PDO object for user authentication
require_once 'config/database.php';

// Check if the form was submitted using POST method
// This ensures the script only processes actual login form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Sanitize and retrieve login credentials from POST request
    // trim() removes whitespace from beginning and end of username/email
    $username = trim($_POST['username']); // Can be username or email
    $password = $_POST['password']; // Password as entered by user

    // Basic validation to ensure both fields are provided
    // empty() returns true for null, empty string, 0, false, etc.
    if (empty($username) || empty($password)) {
        // Redirect back to login form with error message if fields are empty
        header('Location: login.php?error=Please enter both username and password');
        exit(); // Stop script execution after redirect
    }

    try {
        // Prepare SQL statement to find user by username OR email
        // This allows users to login with either their username or email address
        // Uses prepared statements to prevent SQL injection attacks
        // Also fetch status to check if account is active
        $stmt = $pdo->prepare("SELECT id, username, password, role, status FROM users WHERE username = ? OR email = ?");
        
        // Execute the query with the same username parameter for both conditions
        // This searches for a user where either username OR email matches the input
        $stmt->execute([$username, $username]);
        
        // Fetch the user record if found
        // PDO::FETCH_ASSOC returns an associative array with column names as keys
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify the user exists and the password is correct
        // password_verify() securely compares the plain text password with the hashed password
        // This uses PHP's built-in password hashing functions for security
        if ($user && password_verify($password, $user['password'])) {
            
            // Check if user account is active
            // Inactive users cannot login even with correct credentials
            if ($user['status'] !== 'active') {
                header('Location: login.php?error=Your account has been deactivated. Please contact the administrator.');
                exit();
            }
            
            // Login successful - Create user session
            // Store essential user information in session variables for future requests
            $_SESSION['user_id'] = $user['id']; // Store user's database ID
            $_SESSION['username'] = $user['username']; // Store username for display
            $_SESSION['role'] = $user['role']; // Store user role (admin, buyer, supplier) for permissions

            // Check if there's a redirect parameter (for guests who tried to purchase)
            $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
            
            // Sanitize redirect to prevent open redirect vulnerabilities
            // Only allow redirects to local pages
            $allowed_redirects = ['product-list.php', 'cart.php', 'checkout.php', 'index.php'];
            if (!in_array($redirect, $allowed_redirects)) {
                $redirect = 'index.php';
            }
            
            // Redirect to appropriate page after successful login
            header('Location: ' . $redirect);
            exit(); // Stop script execution after redirect
            
        } else {
            // Login failed - either user doesn't exist or password is incorrect
            // Don't specify which part failed for security reasons (prevents username enumeration)
            header('Location: login.php?error=Invalid username or password');
            exit();
        }

    } catch (PDOException $e) {
        // Handle database connection or query errors gracefully
        // Don't expose technical error details to users for security
        // Log the actual error for debugging purposes
        error_log("Login error: " . $e->getMessage());
        
        // Show generic error message to user
        header('Location: login.php?error=Login failed. Please try again.');
        exit();
    }
    
} else {
    // Handle non-POST requests (direct access to this file)
    // Redirect back to login form if accessed incorrectly (e.g., via GET request)
    header('Location: login.php');
    exit();
}
?>
