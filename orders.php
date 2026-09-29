<?php
$page_title = 'My Orders';
ob_start();
include 'header.php';

if (!$auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$cancel_success = '';
$cancel_error   = '';

// Handle cancel request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $oid    = (int)$_POST['order_id'];
    $result = $Order->cancelOrder($oid, $current_user_id);

    if ($result['success']) {
        $cancel_success = 'Order #' . $oid . ' has been cancelled successfully.';

        // Send cancellation email
        $stmt = $db->con->prepare("SELECT email, full_name FROM orders WHERE order_id=? LIMIT 1");
        $stmt->bind_param("i", $oid);
        $stmt->execute();
        $orow = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($orow) {
            $msg =
                "Hi " . $orow['full_name'] . ",\n\n" .
                "Your order #" . $oid . " has been cancelled as requested.\n\n" .
                "If you did not request this cancellation or need help, please contact us:\n" .
                "Phone: +251 911 000 000\n" .
                "Telegram: @isadagishop\n\n" .
                "-- Isa & Dagi Mobile Shop";
            require_once 'mailer.php';
            sendMail($orow['email'], 'Order #' . $oid . ' Cancelled - Isa & Dagi', $msg);
        }
    } else {
        $cancel_error = $result['message'];
    }
}

$my_orders = $Order->getUserOrders($current_user_id);

$status_config = [
    'pending'    => ['label'=>'Pending',    'color'=>'#856404', 'bg'=>'#fff3cd', 'icon'=>'fa-clock',        'step'=>0],
    'processing' => ['label'=>'Processing', 'color'=>'#0c5460', 'bg'=>'#d1ecf1', 'icon'=>'fa-cog',          'step'=>1],
    'shipped'    => ['label'=>'Shipped',    'color'=>'#004085', 'bg'=>'#cce5ff', 'icon'=>'fa-truck',        'step'=>2],
    'delivered'  => ['label'=>'Delivered',  'color'=>'#155724', 'bg'=>'#d4edda', 'icon'=>'fa-check-circle', 'step'=>3],
    'cancelled'  => ['label'=>'Cancelled',  'color'=>'#721c24', 'bg'=>'#f8d7da', 'icon'=>'fa-times-circle', 'step'=>-1],
];
$steps = ['pending','processing','shipped','delivered'];
?>

<style>
/* ---- Order cards ---- */
.order-card {
    background:#fff; border-radius:14px;
    box-shadow:0 2px 12px rgba(0,0,0,0.07);
    border:1px solid #eee;
    margin-bottom:20px;
    overflow:hidden;
    transition:box-shadow 0.2s;
}
.order-card:hover { box-shadow:0 6px 24px rgba(0,0,0,0.11); }

.order-card-header {
    background:linear-gradient(135deg,#003859,#005580);
    color:#fff; padding:14px 20px;
    display:flex; justify-content:space-between; align-items:center;
    flex-wrap:wrap; gap:8px;
}
.order-card-header .order-num {
    font-family:'Rubik',sans-serif; font-weight:700; font-size:16px;
}
.order-card-header .order-date {
    font-size:12px; opacity:0.75;
}

/* ---- Status pill ---- */
.status-pill {
    display:inline-flex; align-items:center; gap:6px;
    padding:5px 14px; border-radius:20px;
    font-size:12px; font-weight:700;
}

/* ---- Progress tracker ---- */
.order-progress { padding:18px 20px 10px; }
.progress-steps {
    display:flex; align-items:flex-start;
    position:relative; margin-bottom:8px;
}
.progress-steps::before {
    content:''; position:absolute;
    top:16px; left:16px; right:16px; height:3px;
    background:#e9ecef; z-index:0;
}
.progress-line {
    position:absolute; top:16px; left:16px;
    height:3px; background:linear-gradient(90deg,#003859,#00A5C4);
    z-index:1; transition:width 0.5s;
}
.step-item {
    flex:1; display:flex; flex-direction:column;
    align-items:center; position:relative; z-index:2;
}
.step-dot {
    width:34px; height:34px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:13px; font-weight:700; border:3px solid #e9ecef;
    background:#fff; transition:all 0.3s;
}
.step-dot.done    { background:#003859; border-color:#003859; color:#fff; }
.step-dot.active  { background:#00A5C4; border-color:#00A5C4; color:#fff; box-shadow:0 0 0 4px rgba(0,165,196,0.2); }
.step-dot.idle    { background:#fff; border-color:#dee2e6; color:#aaa; }
.step-label { font-size:10px; font-weight:600; color:#888; margin-top:6px; text-align:center; }
.step-label.done   { color:#003859; }
.step-label.active { color:#00A5C4; }

/* ---- Items list ---- */
.order-items-list { padding:0 20px 16px; }
.order-item-row {
    display:flex; align-items:center; gap:12px;
    padding:10px 0; border-bottom:1px solid #f5f5f5;
}
.order-item-row:last-child { border-bottom:none; }
.order-item-img {
    width:52px; height:52px; object-fit:contain;
    background:#f8f9fa; border-radius:8px; padding:4px; flex-shrink:0;
}
.order-item-name { font-size:13px; font-weight:600; color:#1a1a1a; }
.order-item-meta { font-size:11px; color:#888; }

/* ---- Footer ---- */
.order-card-footer {
    background:#f8f9fa; padding:12px 20px;
    display:flex; justify-content:space-between; align-items:center;
    flex-wrap:wrap; gap:8px; border-top:1px solid #eee;
}
</style>

<section class="py-4" style="background:#f8f9fa;min-height:80vh;">
<div class="container">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h4 class="section-heading mb-0"><i class="fas fa-box me-2"></i>My Orders</h4>
        <a href="search.php" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-shopping-bag me-1"></i>Continue Shopping
        </a>
    </div>

    <?php if ($cancel_success): ?>
    <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2 mb-3" style="border-radius:10px;">
        <i class="fas fa-check-circle"></i><?php echo htmlspecialchars($cancel_success); ?>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if ($cancel_error): ?>
    <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 mb-3" style="border-radius:10px;">
        <i class="fas fa-exclamation-circle"></i><?php echo htmlspecialchars($cancel_error); ?>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (empty($my_orders)): ?>
    <div class="text-center py-5 bg-white rounded-3 shadow-sm">
        <i class="fas fa-box-open fa-3x text-muted mb-3 d-block"></i>
        <h5 class="text-muted">You haven't placed any orders yet</h5>
        <p class="text-muted" style="font-size:14px;">Browse our phones and accessories to get started.</p>
        <a href="search.php" class="btn btn-primary-custom mt-2 me-2">Shop Phones</a>
        <a href="accessories.php" class="btn btn-outline-primary mt-2">Shop Accessories</a>
    </div>

    <?php else: ?>

    <!-- Summary bar -->
    <div class="row g-3 mb-4">
        <?php
        $counts = ['total'=>count($my_orders),'pending'=>0,'processing'=>0,'shipped'=>0,'delivered'=>0,'cancelled'=>0];
        foreach ($my_orders as $o) { if (isset($counts[$o['status']])) $counts[$o['status']]++; }
        $total_spent = array_sum(array_column(array_filter($my_orders, fn($o)=>$o['status']!=='cancelled'), 'total_amount'));
        ?>
        <div class="col-6 col-md-3">
            <div class="bg-white rounded-3 shadow-sm p-3 text-center">
                <div style="font-size:24px;font-weight:700;font-family:'Rubik',sans-serif;color:#003859;"><?php echo $counts['total']; ?></div>
                <div style="font-size:12px;color:#888;">Total Orders</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="bg-white rounded-3 shadow-sm p-3 text-center">
                <div style="font-size:24px;font-weight:700;font-family:'Rubik',sans-serif;color:#28a745;"><?php echo $counts['delivered']; ?></div>
                <div style="font-size:12px;color:#888;">Delivered</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="bg-white rounded-3 shadow-sm p-3 text-center">
                <div style="font-size:24px;font-weight:700;font-family:'Rubik',sans-serif;color:#00A5C4;"><?php echo $counts['shipped'] + $counts['processing'] + $counts['pending']; ?></div>
                <div style="font-size:12px;color:#888;">In Progress</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="bg-white rounded-3 shadow-sm p-3 text-center">
                <div style="font-size:22px;font-weight:700;font-family:'Rubik',sans-serif;color:#dc3545;"><?php echo formatPrice($total_spent); ?></div>
                <div style="font-size:12px;color:#888;">Total Spent</div>
            </div>
        </div>
    </div>

    <!-- Order cards -->
    <?php foreach ($my_orders as $ord):
        $items  = $Order->getOrderItems($ord['order_id']);
        $sc     = $status_config[$ord['status']] ?? $status_config['pending'];
        $cur_step = $sc['step'];
        $is_cancelled = $ord['status'] === 'cancelled';
        // Progress line width
        $line_pct = $is_cancelled ? '0%' : ($cur_step / (count($steps)-1) * 100) . '%';
    ?>
    <div class="order-card">

        <!-- Header -->
        <div class="order-card-header">
            <div>
                <div class="order-num"><i class="fas fa-receipt me-2"></i>Order #<?php echo $ord['order_id']; ?></div>
                <div class="order-date"><i class="fas fa-calendar me-1"></i><?php echo date('M d, Y — h:i A', strtotime($ord['order_date'])); ?></div>
            </div>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <span class="status-pill" style="background:<?php echo $sc['bg']; ?>;color:<?php echo $sc['color']; ?>;">
                    <i class="fas <?php echo $sc['icon']; ?>"></i><?php echo $sc['label']; ?>
                </span>
                <span style="font-size:18px;font-weight:800;color:#FFD289;">
                    <?php echo formatPrice($ord['total_amount']); ?>
                </span>
            </div>
        </div>

        <!-- Progress tracker (hidden for cancelled) -->
        <?php if (!$is_cancelled): ?>
        <div class="order-progress">
            <div class="progress-steps">
                <div class="progress-line" style="width:<?php echo $line_pct; ?>;"></div>
                <?php foreach ($steps as $i => $step):
                    $dot_class = $i < $cur_step ? 'done' : ($i === $cur_step ? 'active' : 'idle');
                    $lbl_class = $dot_class;
                    $icon = $status_config[$step]['icon'];
                ?>
                <div class="step-item">
                    <div class="step-dot <?php echo $dot_class; ?>">
                        <?php if ($i < $cur_step): ?>
                        <i class="fas fa-check" style="font-size:12px;"></i>
                        <?php else: ?>
                        <i class="fas <?php echo $icon; ?>" style="font-size:12px;"></i>
                        <?php endif; ?>
                    </div>
                    <div class="step-label <?php echo $lbl_class; ?>"><?php echo $status_config[$step]['label']; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if ($ord['status'] === 'shipped'): ?>
            <p class="text-primary mb-0" style="font-size:12px;padding:0 4px;">
                <i class="fas fa-truck me-1"></i>Your order is on the way! Expected delivery soon.
            </p>
            <?php elseif ($ord['status'] === 'processing'): ?>
            <p class="text-info mb-0" style="font-size:12px;padding:0 4px;">
                <i class="fas fa-cog me-1"></i>We are preparing your order.
            </p>
            <?php elseif ($ord['status'] === 'delivered'): ?>
            <p class="text-success mb-0" style="font-size:12px;padding:0 4px;">
                <i class="fas fa-check-circle me-1"></i>Your order has been delivered. Enjoy!
            </p>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="px-4 py-2">
            <span class="text-danger" style="font-size:13px;">
                <i class="fas fa-times-circle me-1"></i>This order was cancelled.
            </span>
        </div>
        <?php endif; ?>

        <!-- Items -->
        <div class="order-items-list">
            <?php foreach ($items as $oi): ?>
            <div class="order-item-row">
                <div class="flex-grow-1">
                    <div class="order-item-name"><?php echo htmlspecialchars($oi['item_name']); ?></div>
                    <div class="order-item-meta">
                        Qty: <?php echo $oi['quantity']; ?> &nbsp;·&nbsp;
                        <?php echo formatPrice($oi['item_price']); ?> each
                    </div>
                </div>
                <div class="text-danger fw-bold" style="font-size:14px;">
                    <?php echo formatPrice($oi['item_price'] * $oi['quantity']); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Footer -->
        <div class="order-card-footer">
            <div style="font-size:12px;color:#888;">
                <i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($ord['address'] . ', ' . $ord['city']); ?>
                &nbsp;·&nbsp;
                <i class="fas fa-credit-card me-1"></i><?php echo strtoupper(htmlspecialchars($ord['payment_method'])); ?>
            </div>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div style="font-size:14px;font-weight:700;">
                    Total: <span class="text-danger"><?php echo formatPrice($ord['total_amount']); ?></span>
                </div>
                <!-- Print Invoice -->
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        style="font-size:12px;border-radius:20px;padding:4px 14px;"
                        onclick="printInvoice(<?php echo $ord['order_id']; ?>)">
                    <i class="fas fa-print me-1"></i>Invoice
                </button>
                <?php if (in_array($ord['status'], ['pending','processing'])): ?>
                <form method="post" onsubmit="return confirm('Are you sure you want to cancel Order #<?php echo $ord['order_id']; ?>? This cannot be undone.');">
                    <input type="hidden" name="order_id" value="<?php echo $ord['order_id']; ?>">
                    <button type="submit" name="cancel_order"
                            class="btn btn-sm btn-outline-danger"
                            style="font-size:12px;border-radius:20px;padding:4px 14px;">
                        <i class="fas fa-times me-1"></i>Cancel Order
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>

    </div>
    <?php endforeach; ?>

    <?php endif; ?>
</div>
</section>

<?php include 'footer.php'; ?>

<script>
// Invoice print data
var ORDER_INVOICE = <?php
    $inv = [];
    foreach ($my_orders as $o) {
        $o['items'] = $Order->getOrderItems($o['order_id']);
        $inv[$o['order_id']] = $o;
    }
    echo json_encode($inv);
?>;

function printInvoice(id) {
    var o = ORDER_INVOICE[id];
    if (!o) return;
    var items = o.items.map(function(it) {
        return '<tr>' +
            '<td style="padding:8px;border:1px solid #ddd;">' + it.item_name + '</td>' +
            '<td style="padding:8px;border:1px solid #ddd;text-align:center;">' + it.quantity + '</td>' +
            '<td style="padding:8px;border:1px solid #ddd;text-align:right;">$' + parseFloat(it.item_price).toFixed(2) + '</td>' +
            '<td style="padding:8px;border:1px solid #ddd;text-align:right;font-weight:700;">$' + (it.item_price * it.quantity).toFixed(2) + '</td>' +
            '</tr>';
    }).join('');

    var html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Invoice #' + id + '</title>' +
        '<style>body{font-family:Arial,sans-serif;padding:30px;color:#222;max-width:700px;margin:0 auto;}' +
        'h1{color:#003859;}table{width:100%;border-collapse:collapse;margin-top:16px;}' +
        'th{background:#003859;color:#fff;padding:10px;text-align:left;}' +
        '.total-row td{font-weight:700;background:#f8f9fa;}' +
        '.header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:30px;}' +
        '.badge{display:inline-block;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;}' +
        '@media print{button{display:none!important;}}' +
        '</style></head><body>' +
        '<div class="header">' +
        '<div><h1>Isa &amp; Dagi Mobile Shop</h1>' +
        '<p style="color:#888;margin:0;">Merkato, Samson Building, Addis Ababa<br>Phone: +251 929 346 248</p></div>' +
        '<div style="text-align:right;"><h2 style="color:#003859;margin:0;">INVOICE</h2>' +
        '<p style="margin:4px 0;font-size:14px;">Order #' + id + '</p>' +
        '<p style="margin:4px 0;font-size:13px;color:#888;">' + o.order_date + '</p></div>' +
        '</div>' +
        '<hr>' +
        '<div style="display:flex;justify-content:space-between;margin-bottom:20px;">' +
        '<div><strong>Bill To:</strong><br>' + o.full_name + '<br>' + o.email + '<br>' + o.phone + '<br>' + o.address + ', ' + o.city + '</div>' +
        '<div style="text-align:right;"><strong>Payment:</strong><br>' + o.payment_method.toUpperCase() + '<br><strong>Status:</strong><br>' + o.status.toUpperCase() + '</div>' +
        '</div>' +
        '<table><thead><tr><th>Product</th><th style="text-align:center;">Qty</th><th style="text-align:right;">Unit Price</th><th style="text-align:right;">Subtotal</th></tr></thead>' +
        '<tbody>' + items + '</tbody>' +
        '<tfoot><tr class="total-row"><td colspan="3" style="padding:10px;border:1px solid #ddd;text-align:right;">TOTAL</td>' +
        '<td style="padding:10px;border:1px solid #ddd;text-align:right;color:#dc3545;">$' + parseFloat(o.total_amount).toFixed(2) + '</td></tr></tfoot>' +
        '</table>' +
        '<p style="margin-top:30px;font-size:12px;color:#888;text-align:center;">Thank you for shopping with Isa &amp; Dagi Mobile Shop!</p>' +
        '<div style="text-align:center;margin-top:10px;"><button onclick="window.print()" style="background:#003859;color:#fff;border:none;padding:10px 30px;border-radius:8px;cursor:pointer;font-size:14px;">🖨 Print Invoice</button></div>' +
        '</body></html>';

    var win = window.open('', '_blank');
    win.document.write(html);
    win.document.close();
}
</script>
