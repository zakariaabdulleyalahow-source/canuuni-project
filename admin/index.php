<?php
// admin/index.php
require_once '../config/db.php';

// Authentication Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['branch_id'])) {
    header('Location: login');
    exit;
}

$branch_id = $_SESSION['branch_id'];

// 1. Total Products in this Branch
$stmtProd = $pdo->prepare("
    SELECT COUNT(*) FROM products p 
    JOIN users u ON p.uid = u.id 
    WHERE u.branch_id = ?
");
$stmtProd->execute([$branch_id]);
$totalProducts = $stmtProd->fetchColumn();

// 2. Total Pre-orders for this Branch
$stmtPre = $pdo->prepare("
    SELECT COUNT(*) FROM preorder po 
    JOIN products p ON po.product_id = p.id 
    JOIN users u ON p.uid = u.id 
    WHERE u.branch_id = ?
");
$stmtPre->execute([$branch_id]);
$totalPreorders = $stmtPre->fetchColumn();

// 3. Low Stock Alert (< 5 units)
$stmtLow = $pdo->prepare("
    SELECT p.* FROM products p 
    JOIN users u ON p.uid = u.id 
    WHERE u.branch_id = ? AND p.quantity < 5
");
$stmtLow->execute([$branch_id]);
$lowStockItems = $stmtLow->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Branch Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="d-flex">
    <!-- Sidebar -->
    <div class="bg-dark text-white p-3 min-vh-100" style="width: 250px;">
        <h4 class="text-warning border-bottom pb-2">Branch Admin</h4>
        <ul class="nav nav-pills flex-column mb-auto mt-3">
            <li class="nav-item"><a href="index" class="nav-link active"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
            <li><a href="products" class="nav-link text-white"><i class="fas fa-box me-2"></i> Products</a></li>
            <li><a href="orders" class="nav-link text-white"><i class="fas fa-shopping-bag me-2"></i> Preorders</a></li>
            <li class="mt-4 border-top border-secondary pt-2"><a href="logout" class="nav-link text-white"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="flex-grow-1 p-4">
        <h2 class="mb-4">Branch Overview</h2>

        <div class="row g-3 mb-4">
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 bg-primary text-white shadow-sm">
                    <div class="card-body">
                        <h5>Branch Products</h5>
                        <h2 class="fw-bold mb-0"><?= (int)$totalProducts; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 bg-warning text-dark shadow-sm">
                    <div class="card-body">
                        <h5>Pending Preorders</h5>
                        <h2 class="fw-bold mb-0"><?= (int)$totalPreorders; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-bold">Low Stock Warning</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Category ID</th>
                            <th>Stock Left</th>
                            <th>Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($lowStockItems)): ?>
                            <tr><td colspan="4" class="text-center py-3">All stocks are healthy!</td></tr>
                        <?php else: ?>
                            <?php foreach ($lowStockItems as $item): ?>
                                <tr>
                                    <td><?= e($item['product_name'] ?? $item['productname']); ?></td>
                                    <td><?= $item['category_id']; ?></td>
                                    <td><span class="badge bg-danger"><?= $item['quantity']; ?></span></td>
                                    <td>$<?= number_format($item['price'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>