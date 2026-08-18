<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is a buyer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user information
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get user statistics
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_orders = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT SUM(total_amount) FROM orders WHERE user_id = ? AND status = 'completed'");
    $stmt->execute([$user_id]);
    $total_spent = $stmt->fetchColumn() ?: 0;

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchColumn();

} catch (PDOException $e) {
    $user = null;
    $total_orders = 0;
    $total_spent = 0;
    $cart_items = 0;
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $errors = [];

    // Validate input
    if (empty($username) || empty($email)) {
        $errors[] = 'Username and email are required';
    }

    // Check if username/email already exists (excluding current user)
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $user_id]);
        if ($stmt->rowCount() > 0) {
            $errors[] = 'Username or email already exists';
        }
    } catch (PDOException $e) {
        $errors[] = 'Database error occurred';
    }

    // Handle password change
    $update_password = false;
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $errors[] = 'Current password is required to change password';
        } elseif (!password_verify($current_password, $user['password'])) {
            $errors[] = 'Current password is incorrect';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'New passwords do not match';
        } elseif (strlen($new_password) < 6) {
            $errors[] = 'New password must be at least 6 characters';
        } else {
            $update_password = true;
        }
    }

    // Update profile if no errors
    if (empty($errors)) {
        try {
            if ($update_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
                $stmt->execute([$username, $email, $hashed_password, $user_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                $stmt->execute([$username, $email, $user_id]);
            }

            $_SESSION['username'] = $username;
            $success_message = 'Profile updated successfully';
            
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            $errors[] = 'Failed to update profile';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Inventory Management System</title>
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/unified-dashboard.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Inventory Management System</h1>
            <nav>
                <a href="index.php">Home</a>
                <a href="buyer-dashboard.php">Dashboard</a>
                <a href="product-list.php">Browse Products</a>
                <a href="cart.php">My Cart</a>
                <a href="buyer-orders.php">My Orders</a>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (Buyer)</span>
                <a href="logout.php">Logout</a>
            </nav>
        </header>

        <main>
            <h2>My Profile</h2>

            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
                <!-- Profile Statistics -->
                <div style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h3>Account Statistics</h3>
                    <div style="margin-top: 1rem;">
                        <div style="padding: 1rem 0; border-bottom: 1px solid #eee;">
                            <strong>Total Orders</strong>
                            <span style="float: right; color: #007bff;"><?php echo $total_orders; ?></span>
                        </div>
                        <div style="padding: 1rem 0; border-bottom: 1px solid #eee;">
                            <strong>Total Spent</strong>
                            <span style="float: right; color: #28a745;">₹<?php echo number_format($total_spent, 2); ?></span>
                        </div>
                        <div style="padding: 1rem 0; border-bottom: 1px solid #eee;">
                            <strong>Cart Items</strong>
                            <span style="float: right; color: #ffc107;"><?php echo $cart_items; ?></span>
                        </div>
                        <div style="padding: 1rem 0;">
                            <strong>Member Since</strong>
                            <span style="float: right; color: #666;"><?php echo date('M Y', strtotime($user['created_at'])); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Profile Form -->
                <div style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h3>Update Profile</h3>

                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-error">
                            <?php foreach ($errors as $error): ?>
                                <p><?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" style="margin-top: 1rem;">
                        <div class="form-group">
                            <label for="username">Username:</label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <hr style="margin: 2rem 0;">
                        <h4>Change Password (Optional)</h4>

                        <div class="form-group">
                            <label for="current_password">Current Password:</label>
                            <input type="password" id="current_password" name="current_password">
                        </div>

                        <div class="form-group">
                            <label for="new_password">New Password:</label>
                            <input type="password" id="new_password" name="new_password">
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password:</label>
                            <input type="password" id="confirm_password" name="confirm_password">
                        </div>

                        <div style="margin-top: 2rem;">
                            <button type="submit" class="btn btn-success">Update Profile</button>
                            <a href="buyer-dashboard.php" class="btn" style="background: #6c757d;">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>