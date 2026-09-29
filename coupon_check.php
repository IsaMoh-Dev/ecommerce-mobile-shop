<?php
// AJAX endpoint — validate a coupon code
require_once 'functions.php';
header('Content-Type: application/json');

$code  = strtoupper(trim($_POST['code'] ?? ''));
$total = (float)($_POST['total'] ?? 0);

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a coupon code.']);
    exit;
}

// Ensure table exists
$db->con->query("CREATE TABLE IF NOT EXISTS `coupons` (
    `coupon_id`  INT(11)      NOT NULL AUTO_INCREMENT,
    `code`       VARCHAR(50)  NOT NULL,
    `type`       ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
    `value`      DOUBLE(10,2) NOT NULL DEFAULT 10,
    `min_order`  DOUBLE(10,2) NOT NULL DEFAULT 0,
    `max_uses`   INT(11)      NOT NULL DEFAULT 100,
    `used_count` INT(11)      NOT NULL DEFAULT 0,
    `expires_at` DATE         DEFAULT NULL,
    `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at` DATETIME     DEFAULT NOW(),
    PRIMARY KEY (`coupon_id`),
    UNIQUE KEY `unique_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$code_esc = mysqli_real_escape_string($db->con, $code);
$res = $db->con->query("SELECT * FROM coupons WHERE code='{$code_esc}' LIMIT 1");

if (!$res || $res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid coupon code.']);
    exit;
}

$coupon = $res->fetch_assoc();

if (!$coupon['is_active']) {
    echo json_encode(['success' => false, 'message' => 'This coupon is no longer active.']);
    exit;
}

if (!empty($coupon['expires_at']) && strtotime($coupon['expires_at']) < time()) {
    echo json_encode(['success' => false, 'message' => 'This coupon has expired.']);
    exit;
}

if ($coupon['used_count'] >= $coupon['max_uses']) {
    echo json_encode(['success' => false, 'message' => 'This coupon has reached its usage limit.']);
    exit;
}

if ($coupon['min_order'] > 0 && $total < $coupon['min_order']) {
    echo json_encode([
        'success' => false,
        'message' => 'Minimum order of $' . number_format($coupon['min_order'], 2) . ' required for this coupon.'
    ]);
    exit;
}

// Calculate discount
$discount = 0;
if ($coupon['type'] === 'percent') {
    $discount = round($total * ($coupon['value'] / 100), 2);
} else {
    $discount = min($coupon['value'], $total);
}

echo json_encode([
    'success'     => true,
    'message'     => 'Coupon applied! You save $' . number_format($discount, 2),
    'discount'    => $discount,
    'type'        => $coupon['type'],
    'value'       => $coupon['value'],
    'coupon_id'   => $coupon['coupon_id'],
    'code'        => $coupon['code'],
]);
