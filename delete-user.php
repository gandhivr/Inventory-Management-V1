<?php
// Start session to manage user authentication and store messages
session_start();
// Include database connection for user operations
require_once 'config/database.php';

// Check if user is logged in and has admin privileges
// Only admin users should be able to delete other users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Redirect unauthorized users to login page
    header("Location: login.php");
    exit();
}

// Handle Soft Delete User (deactivate account)
if (isset($_GET['soft_delete'])) {
    $user_id = $_GET['soft_delete'];
    
    // Prevent admin from deleting themselves
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['message'] = "You cannot delete your own account!";
        $_SESSION['msg_type'] = "danger";
        header("Location: manage-users.php");
        exit();
    }
    
    try {
        // Soft delete by updating status to 'inactive' instead of removing record
        // This preserves user data and associated records (orders, etc.)
        $stmt = $pdo->prepare("UPDATE users SET status = 'inactive', deleted_at = NOW() WHERE id = ? AND status = 'active'");
        $stmt->execute([$user_id]);
        
        if ($stmt->rowCount() > 0) {
            // Verify the update by checking the user's current status
            $checkStmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
            $checkStmt->execute([$user_id]);
            $currentStatus = $checkStmt->fetchColumn();
            
            $_SESSION['message'] = "User account deactivated successfully! Status is now: " . $currentStatus;
            $_SESSION['msg_type'] = "warning";
        } else {
            $_SESSION['message'] = "User not found or already inactive.";
            $_SESSION['msg_type'] = "danger";
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = "Error deactivating user: " . $e->getMessage();
        $_SESSION['msg_type'] = "danger";
    }
    
    header("Location: manage-users.php");
    exit();
}

// Handle Hard Delete User (permanently remove from database)
if (isset($_GET['hard_delete'])) {
    $user_id = $_GET['hard_delete'];
    
    // Prevent admin from deleting themselves
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['message'] = "You cannot delete your own account!";
        $_SESSION['msg_type'] = "danger";
        header("Location: manage-users.php");
        exit();
    }
    
    try {
        // Start transaction to ensure data integrity
        // Need to handle related records in buyer_profile and supplier_profile tables
        $pdo->beginTransaction();
        
        // Get user information before deletion
        $stmt = $pdo->prepare("SELECT username, role FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new Exception("User not found");
        }
        
        // Delete related profile data based on user role
        if ($user['role'] === 'buyer') {
            // Delete buyer profile data
            $stmt = $pdo->prepare("DELETE FROM buyer_profile WHERE user_id = ?");
            $stmt->execute([$user_id]);
        } elseif ($user['role'] === 'supplier') {
            // Delete supplier profile data
            $stmt = $pdo->prepare("DELETE FROM supplier_profile WHERE user_id = ?");
            $stmt->execute([$user_id]);
        }
        
        // Delete the main user record
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        
        // Commit the transaction
        $pdo->commit();
        
        $_SESSION['message'] = "User '{$user['username']}' permanently deleted successfully!";
        $_SESSION['msg_type'] = "success";
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollback();
        $_SESSION['message'] = "Error permanently deleting user: " . $e->getMessage();
        $_SESSION['msg_type'] = "danger";
    }
    
    header("Location: manage-users.php");
    exit();
}

// Handle Restore User (reactivate deactivated account)
if (isset($_GET['restore'])) {
    $user_id = $_GET['restore'];
    
    try {
        // Reactivate user by setting status back to 'active'
        $stmt = $pdo->prepare("UPDATE users SET status = 'active', deleted_at = NULL WHERE id = ? AND status = 'inactive'");
        $stmt->execute([$user_id]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['message'] = "User account reactivated successfully!";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['message'] = "User not found in inactive accounts.";
            $_SESSION['msg_type'] = "danger";
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = "Error reactivating user: " . $e->getMessage();
        $_SESSION['msg_type'] = "danger";
    }
    
    header("Location: manage-users.php");
    exit();
}

// If accessed directly without action parameters, redirect to user management
header("Location: manage-users.php");
exit();
?>
