<?php
// ajax/cart.php
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action    = $_POST['action'] ?? $_GET['action'] ?? '';
$productId = (int)($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
$quantity  = (int)($_POST['quantity'] ?? 1);

function calculateCartTotals() {
    $subtotal = 0;
    $itemCount = 0;

    foreach ($_SESSION['cart'] as $item) {
        $subtotal += (float)($item['price'] ?? 0) * (int)($item['quantity'] ?? 1);
        $itemCount += (int)($item['quantity'] ?? 1);
    }

    return [
        'subtotal'  => number_format($subtotal, 2, '.', ''),
        'itemCount' => $itemCount,
        'cartCount' => count($_SESSION['cart'])
    ];
}

switch ($action) {

    case 'add':
        if ($productId <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid product ID.']);
            exit;
        }

        // Using LEFT JOIN so missing user/branch relationship doesn't hide products
        $stmt = $pdo->prepare("
            SELECT p.id, p.product_name, p.unitprice, p.advantage, p.quantity AS stock, p.productimage
            FROM products p
            LEFT JOIN users u ON p.uid = u.id
            WHERE p.id = ?
        ");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product) {
            echo json_encode(['status' => 'error', 'message' => 'Product not found.']);
            exit;
        }

        $stock = (int)($product['stock'] ?? 0);
        if ($stock < 1) {
            echo json_encode(['status' => 'error', 'message' => 'Product is currently out of stock.']);
            exit;
        }

        $sellingPrice = (float)($product['unitprice'] ?? 0) + (float)($product['advantage'] ?? 0);
        $key = (string)$productId;

        if (isset($_SESSION['cart'][$key])) {
            $newQuantity = $_SESSION['cart'][$key]['quantity'] + $quantity;
            if ($newQuantity > $stock) {
                echo json_encode([
                    'status' => 'error', 
                    'message' => 'Cannot add more. Reached maximum available stock (' . $stock . ').'
                ]);
                exit;
            }
            $_SESSION['cart'][$key]['quantity'] = $newQuantity;
        } else {
            $_SESSION['cart'][$key] = [
                'id'       => $product['id'],
                'name'     => $product['product_name'],
                'price'    => $sellingPrice,
                'image'    => $product['productimage'],
                'quantity' => min($quantity, $stock)
            ];
        }

        $totals = calculateCartTotals();

        echo json_encode([
            'status'    => 'success',
            'message'   => $product['product_name'] . ' added to cart!',
            'cart'      => $_SESSION['cart'],
            'cartCount' => $totals['itemCount'],
            'subtotal'  => $totals['subtotal']
        ]);
        exit;

    case 'update':
        $key = (string)$productId;
        if ($productId > 0 && isset($_SESSION['cart'][$key])) {
            if ($quantity > 0) {
                $_SESSION['cart'][$key]['quantity'] = $quantity;
            } else {
                unset($_SESSION['cart'][$key]);
            }

            $totals = calculateCartTotals();
            echo json_encode([
                'status'    => 'success',
                'message'   => 'Cart updated successfully.',
                'cart'      => $_SESSION['cart'],
                'cartCount' => $totals['itemCount'],
                'subtotal'  => $totals['subtotal']
            ]);
            exit;
        }
        echo json_encode(['status' => 'error', 'message' => 'Item not found in cart.']);
        exit;

    case 'remove':
        $key = (string)$productId;
        if ($productId > 0 && isset($_SESSION['cart'][$key])) {
            unset($_SESSION['cart'][$key]);
            $totals = calculateCartTotals();
            echo json_encode([
                'status'    => 'success',
                'message'   => 'Item removed from cart.',
                'cart'      => $_SESSION['cart'],
                'cartCount' => $totals['itemCount'],
                'subtotal'  => $totals['subtotal']
            ]);
            exit;
        }
        echo json_encode(['status' => 'error', 'message' => 'Item not found in cart.']);
        exit;

    case 'get':
        $totals = calculateCartTotals();
        echo json_encode([
            'status'    => 'success',
            'cart'      => $_SESSION['cart'],
            'cartCount' => $totals['itemCount'],
            'subtotal'  => $totals['subtotal']
        ]);
        exit;

    case 'clear':
        $_SESSION['cart'] = [];
        echo json_encode([
            'status'    => 'success',
            'message'   => 'Cart cleared.',
            'cart'      => [],
            'cartCount' => 0,
            'subtotal'  => '0.00'
        ]);
        exit;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action parameter.']);
        exit;
}