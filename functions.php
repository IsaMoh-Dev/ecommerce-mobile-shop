<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/database/DBController.php';
require_once __DIR__ . '/database/product.php';
require_once __DIR__ . '/database/Cart.php';
require_once __DIR__ . '/database/auth.php';
require_once __DIR__ . '/database/Order.php';

$db              = new DBController();
$product         = new product($db);
$product_shuffle = $product->getData();
$Cart            = new Cart($db);
$auth            = new Auth($db);
$Order           = new Order($db);
$current_user_id = $auth->getUserId();

// ---- Settings helper ----
function getSetting($key) {
    global $db;
    $key    = mysqli_real_escape_string($db->con, $key);
    $result = $db->con->query("SELECT setting_value FROM settings WHERE setting_key='{$key}' LIMIT 1");
    if ($result && $result->num_rows > 0) return $result->fetch_assoc()['setting_value'];
    return '';
}

// ---- Fill array to minimum count by repeating items ----
// Used so carousels/grids always look full even with few products.
// $items   = original array
// $min     = minimum number of items to show (default 8)
// $max     = never exceed this count (default 16)
function fillToMin(array $items, int $min = 8, int $max = 16): array {
    if (empty($items)) return $items;
    $count = count($items);
    if ($count >= $min) return array_slice($items, 0, $max);
    // Repeat items until we reach $min
    $filled = $items;
    while (count($filled) < $min) {
        foreach ($items as $item) {
            $filled[] = $item;
            if (count($filled) >= $min) break;
        }
    }
    return array_slice($filled, 0, $max);
}

// ---- Currency helper ----
function formatPrice($usd_price) {
    $currency = $_SESSION['currency'] ?? getSetting('currency') ?? 'USD';
    $rate     = (float)(getSetting('usd_to_birr') ?: 57);
    if ($currency === 'ETB') {
        return number_format($usd_price * $rate, 2) . ' Birr';
    }
    return '$' . number_format($usd_price, 2);
}

function getCurrencySymbol() {
    $currency = $_SESSION['currency'] ?? getSetting('currency') ?? 'USD';
    return $currency === 'ETB' ? 'Birr' : '$';
}

// Handle currency switch
if (isset($_GET['set_currency'])) {
    $c = $_GET['set_currency'] === 'ETB' ? 'ETB' : 'USD';
    $_SESSION['currency'] = $c;
    $url = strtok($_SERVER['REQUEST_URI'], '?');
    header("Location: {$url}");
    exit;
}

// Maintenance mode — block customers but allow admins
if (getSetting('maintenance_mode') === '1') {
    if (!isset($auth) || !$auth->canAccessAdmin()) {
        $maintenance_msg = getSetting('maintenance_message') ?: "We're performing maintenance. We'll be back shortly!";
        http_response_code(503);
        die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Maintenance</title>
        <style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#003859;color:#fff;}
        .box{text-align:center;padding:40px;max-width:500px;}
        .box h1{font-size:28px;margin-bottom:12px;}
        .box p{font-size:16px;opacity:0.8;line-height:1.6;}
        .box .icon{font-size:60px;margin-bottom:20px;}
        </style></head><body><div class="box">
        <div class="icon">🔧</div>
        <h1>Under Maintenance</h1>
        <p>' . htmlspecialchars($maintenance_msg) . '</p>
        <p style="font-size:13px;margin-top:20px;opacity:0.6;">Isa &amp; Dagi Mobile Shop · Merkato, Addis Ababa</p>
        </div></body></html>');
    }
}
