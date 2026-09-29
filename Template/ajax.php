<?php
require_once '../database/DBController.php';
require_once '../database/product.php';

$db      = new DBController();
$product = new product($db);

// Single product by ID
if (isset($_POST['itemid'])) {
    $result = $product->getProduct((int)$_POST['itemid']);
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

// Search suggestions
if (isset($_POST['search'])) {
    $results = $product->searchProducts(trim($_POST['search']));
    header('Content-Type: application/json');
    echo json_encode(array_slice($results, 0, 6));
    exit;
}
