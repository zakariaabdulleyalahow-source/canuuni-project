<?php
// ajax/search.php
require_once '../config/db.php';

header('Content-Type: application/json');

$query = trim($_GET['q'] ?? '');
$branch_id = $_SESSION['branch_id'];

if (empty($query)) {
    echo json_encode([]);
    exit;
}

$searchTerm = '%' . $query . '%';

$stmt = $pdo->prepare("
    SELECT p.id, p.product_name, p.price, p.productimage, c.category_name 
    FROM products p
    JOIN categories c ON p.category_id = c.id
    JOIN users u ON p.uid = u.id
    WHERE u.branch_id = ? 
    AND (p.product_name LIKE ? OR p.barcode LIKE ? OR c.category_name LIKE ?)
    LIMIT 8
");

$stmt->execute([$branch_id, $searchTerm, $searchTerm, $searchTerm]);
$results = $stmt->fetchAll();

echo json_encode($results);
?>