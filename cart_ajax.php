<?php
ob_start();
require_once 'functions.php';
ob_end_clean(); // discard any output from includes

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    case 'update_qty':
        if (!$auth->isLoggedIn()) { echo json_encode(['success'=>false]); exit; }
        $item_id = (int)($_POST['item_id'] ?? 0);
        $qty     = (int)($_POST['qty']     ?? 1);
        $Cart->updateQty($current_user_id, $item_id, $qty);
        echo json_encode(['success' => true, 'count' => $Cart->countCart($current_user_id)]);
        break;

    case 'get_count':
        $count = $auth->isLoggedIn() ? $Cart->countCart($current_user_id) : 0;
        echo json_encode(['count' => $count]);
        break;

    case 'add':
        if (!$auth->isLoggedIn()) { echo json_encode(['success'=>false,'message'=>'Login required']); exit; }
        $item_id = (int)($_POST['item_id'] ?? 0);
        $uid     = (int)$current_user_id;
        $db->con->query(
            "INSERT INTO cart (user_id, item_id, qty) VALUES ({$uid},{$item_id},1)
             ON DUPLICATE KEY UPDATE qty = qty + 1"
        );
        echo json_encode(['success'=>true,'count'=>$Cart->countCart($current_user_id)]);
        break;

    default:
        echo json_encode(['success'=>false,'message'=>'Invalid action']);
}
