<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is either an admin or a supplier
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'supplier'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];

    // Validate status, including the new 'shipped' option
    if (!in_array($status, ['pending', 'completed', 'cancelled', 'shipped'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit();
    }

    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $order_id]);

        echo json_encode(['success' => true, 'message' => 'Order status updated successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>