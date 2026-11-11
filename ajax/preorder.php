<?php
// ajax/preorder.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['status' => 'unauthorized', 'message' => 'Please log in to submit a pre-order.']);
    exit;
}

$customerId = $_SESSION['customer_id'];
$productId  = (int)($_POST['product_id'] ?? 0);
$comment    = trim($_POST['comment'] ?? 'Pre-order request');
$uid        = $_SESSION['user_id'] ?? 1;

if ($productId > 0) {
    $stmt = $pdo->prepare("
        INSERT INTO preorder (customer_id, product_id, date, status, comment, uid) 
        VALUES (?, ?, NOW(), 'Pending', ?, ?)
    ");
    
    if ($stmt->execute([$customerId, $productId, $comment, $uid])) {
        echo json_encode(['status' => 'success', 'message' => 'Your pre-order request has been placed!']);
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Unable to complete pre-order.']);
?>