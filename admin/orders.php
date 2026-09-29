<?php
require_once '../functions.php';
if (!$auth->canAccessAdmin()) { header('Location: login.php'); exit; }

// Handle status update
$success = '';
$error   = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $allowed = ['pending','processing','shipped','delivered','cancelled'];
    $new_status = $_POST['status'] ?? '';
    $oid = (int)$_POST['order_id'];
    if (in_array($new_status, $allowed)) {
        // If admin is cancelling, restore stock
        if ($new_status === 'cancelled') {
            $prev = $db->con->query("SELECT status FROM orders WHERE order_id={$oid} LIMIT 1")->fetch_assoc();
            if ($prev && $prev['status'] !== 'cancelled') {
                $items_to_restore = $db->con->query("SELECT item_id, quantity FROM order_items WHERE order_id={$oid}");
                if ($items_to_restore) {
                    while ($ir = $items_to_restore->fetch_assoc()) {
                        $db->con->query("UPDATE product SET item_stock = item_stock + {$ir['quantity']} WHERE item_id = {$ir['item_id']}");
                    }
                }
            }
        }
        $Order->updateStatus($oid, $new_status);
        $success = 'Order #' . $oid . ' status updated to <strong>' . ucfirst($new_status) . '</strong>.';

        // Send email notification to customer
        $stmt = $db->con->prepare(
            "SELECT o.email, o.full_name, o.order_id FROM orders o WHERE o.order_id=? LIMIT 1"
        );
        $stmt->bind_param("i", $oid);
        $stmt->execute();
        $orow = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($orow) {
            $status_messages = [
                'processing' => 'Your order is now being processed and prepared for shipment.',
                'shipped'    => 'Great news! Your order has been shipped and is on its way to you.',
                'delivered'  => 'Your order has been delivered. We hope you enjoy your purchase!',
                'cancelled'  => 'Your order has been cancelled. If you have questions, please contact us.',
                'pending'    => 'Your order status has been updated to pending.',
            ];
            $status_msg = $status_messages[$new_status] ?? 'Your order status has been updated.';
            $email_body =
                "Hi " . $orow['full_name'] . ",\n\n" .
                "Your order #" . $oid . " status has been updated.\n\n" .
                "New Status: " . strtoupper($new_status) . "\n\n" .
                $status_msg . "\n\n" .
                "You can track your order at: http://" . $_SERVER['HTTP_HOST'] .
                str_replace('admin/orders.php', 'orders.php', $_SERVER['SCRIPT_NAME']) . "\n\n" .
                "-- Isa & Dagi Mobile Shop\n" .
                "Phone: +251 911 000 000\n" .
                "Telegram: @isadagishop";
            require_once '../mailer.php';
            sendMail($orow['email'], 'Order #' . $oid . ' Update: ' . ucfirst($new_status) . ' - Isa & Dagi', $email_body);
        }
    } else {
        $error = 'Invalid status.';
    }
}

// Filters
$filter_status = $_GET['status'] ?? '';
$filter_search = trim($_GET['q'] ?? '');

$all_orders = $Order->getAllOrders();

// Apply filters in PHP
if ($filter_status !== '') {
    $all_orders = array_filter($all_orders, fn($o) => $o['status'] === $filter_status);
}
if ($filter_search !== '') {
    $q = strtolower($filter_search);
    $all_orders = array_filter($all_orders, function($o) use ($q) {
        return str_contains(strtolower($o['first_name'] . ' ' . $o['last_name']), $q)
            || str_contains($o['phone'], $q)
            || str_contains((string)$o['order_id'], $q)
            || str_contains(strtolower($o['email']), $q);
    });
}

// Status config
$statuses = [
    'pending'    => ['label' => 'Pending',    'color' => 'warning',  'icon' => 'fa-clock',          'bg' => '#fff3cd', 'text' => '#856404'],
    'processing' => ['label' => 'Processing', 'color' => 'info',     'icon' => 'fa-cog',            'bg' => '#d1ecf1', 'text' => '#0c5460'],
    'shipped'    => ['label' => 'Shipped',    'color' => 'primary',  'icon' => 'fa-truck',          'bg' => '#cce5ff', 'text' => '#004085'],
    'delivered'  => ['label' => 'Delivered',  'color' => 'success',  'icon' => 'fa-check-circle',   'bg' => '#d4edda', 'text' => '#155724'],
    'cancelled'  => ['label' => 'Cancelled',  'color' => 'danger',   'icon' => 'fa-times-circle',   'bg' => '#f8d7da', 'text' => '#721c24'],
];

// Count by status for summary cards
$counts = ['all' => count($Order->getAllOrders())];
foreach ($statuses as $key => $s) {
    $counts[$key] = count(array_filter($Order->getAllOrders(), fn($o) => $o['status'] === $key));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders — Isa &amp; Dagi Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        /* ---- Status badge pills ---- */
        .status-pill {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 12px; border-radius: 20px;
            font-size: 12px; font-weight: 600;
        }

        /* ---- Summary cards ---- */
        .order-stat {
            background: #fff; border-radius: 12px; padding: 16px 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border-left: 4px solid transparent;
            cursor: pointer; transition: transform 0.15s, box-shadow 0.15s;
            text-decoration: none; display: block;
        }
        .order-stat:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0,0,0,0.1); }
        .order-stat.active-filter { box-shadow: 0 0 0 2px var(--secondary); }
        .order-stat-num { font-family:'Rubik',sans-serif; font-size:26px; font-weight:700; color:var(--primary); }
        .order-stat-label { font-size:12px; color:#888; margin-top:2px; }

        /* ---- Table ---- */
        .orders-table th { font-size:12px; text-transform:uppercase; letter-spacing:0.5px; }
        .orders-table td { vertical-align: middle; }

        /* ---- Status select styled ---- */
        .status-select {
            border-radius: 8px; font-size:13px; padding: 5px 10px;
            border: 1.5px solid #ddd; cursor: pointer;
            transition: border-color 0.2s;
        }
        .status-select:focus { border-color: var(--secondary); outline: none; box-shadow: none; }

        /* ---- Order detail modal ---- */
        .order-modal-header {
            background: linear-gradient(135deg, var(--primary), #005580);
            color: #fff; border-radius: 12px 12px 0 0; padding: 20px 24px;
        }
        .order-timeline { list-style: none; padding: 0; margin: 0; position: relative; }
        .order-timeline::before {
            content: ''; position: absolute; left: 15px; top: 0; bottom: 0;
            width: 2px; background: #e0e0e0;
        }
        .order-timeline li {
            display: flex; align-items: flex-start; gap: 14px;
            padding: 10px 0; position: relative;
        }
        .order-timeline .tl-dot {
            width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; z-index: 1;
        }
        .order-timeline .tl-dot.done  { background: #28a745; color: #fff; }
        .order-timeline .tl-dot.active { background: var(--secondary); color: #fff; }
        .order-timeline .tl-dot.idle  { background: #e9ecef; color: #aaa; }
        .order-timeline li > div strong { color: #212529 !important; }
        .order-timeline li > div small  { color: #6c757d !important; }

        /* ---- Search bar ---- */
        .admin-search-bar {
            border: 1.5px solid #ddd; border-radius: 8px;
            padding: 8px 14px; font-size: 13px; width: 260px;
            transition: border-color 0.2s;
        }
        .admin-search-bar:focus { border-color: var(--secondary); outline: none; box-shadow: none; }

        /* ---- Modal body text fix (dark admin theme override) ---- */
        #modalOrderBody,
        #modalOrderBody p,
        #modalOrderBody div,
        #modalOrderBody td,
        #modalOrderBody th,
        #modalOrderBody li,
        #modalOrderBody small,
        #modalOrderBody span:not(.badge) {
            color: #212529;
        }
        #modalOrderBody .text-danger,
        #modalOrderBody strong.text-danger { color: #dc3545 !important; }
        #modalOrderBody .text-muted        { color: #6c757d !important; }
        #modalOrderBody h6                 { color: var(--primary) !important; }
        #modalOrderBody .tl-dot            { color: inherit; }
        #modalOrderBody .tl-dot.done       { color: #fff !important; }
        #modalOrderBody .tl-dot.active     { color: #fff !important; }
        #modalOrderBody .tl-dot.idle       { color: #aaa !important; }
        #modalOrderBody .order-timeline::before { background: #dee2e6; }
    </style>
</head>
<body>

<!-- Sidebar -->
<?php $active_page = 'orders'; include '_sidebar.php'; ?>

<!-- Main Content -->
<div class="admin-content">

    <!-- Top bar -->
    <div class="admin-topbar">
        <h5 class="mb-0" style="font-family:'Rubik',sans-serif; font-weight:600;">
            <i class="fas fa-shopping-bag me-2 text-primary"></i>Manage Orders
        </h5>
        <span style="font-size:13px; color:#888;">
            Total: <strong><?php echo $counts['all']; ?></strong> orders
        </span>
    </div>

    <!-- Alerts -->
    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" role="alert" style="border-radius:10px;">
        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius:10px;">
        <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-2">
            <a href="orders.php" class="order-stat <?php echo $filter_status==='' && $filter_search==='' ? 'active-filter' : ''; ?>" style="border-left-color:#6c757d;">
                <div class="order-stat-num"><?php echo $counts['all']; ?></div>
                <div class="order-stat-label"><i class="fas fa-list me-1"></i>All Orders</div>
            </a>
        </div>
        <?php foreach ($statuses as $key => $s): ?>
        <div class="col-6 col-md-2">
            <a href="orders.php?status=<?php echo $key; ?>" class="order-stat <?php echo $filter_status===$key ? 'active-filter' : ''; ?>" style="border-left-color:<?php echo ['pending'=>'#ffc107','processing'=>'#17a2b8','shipped'=>'#007bff','delivered'=>'#28a745','cancelled'=>'#dc3545'][$key]; ?>;">
                <div class="order-stat-num"><?php echo $counts[$key]; ?></div>
                <div class="order-stat-label">
                    <i class="fas <?php echo $s['icon']; ?> me-1"></i><?php echo $s['label']; ?>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Search + Filter bar -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <form method="get" class="d-flex gap-2 align-items-center">
            <?php if ($filter_status): ?>
            <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter_status); ?>">
            <?php endif; ?>
            <div class="position-relative">
                <i class="fas fa-search position-absolute" style="left:10px;top:50%;transform:translateY(-50%);color:#aaa;font-size:12px;"></i>
                <input type="text" name="q" class="admin-search-bar" style="padding-left:32px;"
                       placeholder="Search name, phone, order #..."
                       value="<?php echo htmlspecialchars($filter_search); ?>">
            </div>
            <button type="submit" class="btn btn-sm btn-primary px-3">Search</button>
            <?php if ($filter_search || $filter_status): ?>
            <a href="orders.php" class="btn btn-sm btn-outline-secondary">Clear</a>
            <?php endif; ?>
        </form>
        <span class="text-muted" style="font-size:13px;">
            Showing <strong><?php echo count($all_orders); ?></strong> order(s)
            <?php if ($filter_status): ?>
            — filtered by <span class="badge bg-<?php echo $statuses[$filter_status]['color']; ?>"><?php echo $statuses[$filter_status]['label']; ?></span>
            <?php endif; ?>
        </span>
    </div>

    <!-- Orders Table -->
    <div class="admin-table">
        <table class="table mb-0 orders-table">
            <thead>
                <tr>
                    <th style="padding:14px 16px;">Order #</th>
                    <th style="padding:14px 16px;">Customer</th>
                    <th style="padding:14px 16px;">Items</th>
                    <th style="padding:14px 16px;">Total</th>
                    <th style="padding:14px 16px;">Payment</th>
                    <th style="padding:14px 16px;">Date</th>
                    <th style="padding:14px 16px;">Status</th>
                    <th style="padding:14px 16px;">Update Status</th>
                    <th style="padding:14px 16px;">Details</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($all_orders)): ?>
                <tr>
                    <td colspan="9" class="text-center py-5 text-muted">
                        <i class="fas fa-box-open fa-2x mb-2 d-block"></i>
                        No orders found.
                        <?php if ($filter_status || $filter_search): ?>
                        <a href="orders.php" class="d-block mt-2 text-primary" style="font-size:13px;">Clear filters</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($all_orders as $o):
                    $s   = $statuses[$o['status']] ?? $statuses['pending'];
                    $items = $Order->getOrderItems($o['order_id']);
                ?>
                <tr id="row-<?php echo $o['order_id']; ?>">
                    <td style="padding:14px 16px;">
                        <span style="font-family:'Rubik',sans-serif; font-weight:700; color:var(--primary);">
                            #<?php echo $o['order_id']; ?>
                        </span>
                    </td>
                    <td style="padding:14px 16px;">
                        <div style="font-weight:600; font-size:13px;"><?php echo htmlspecialchars($o['first_name'] . ' ' . $o['last_name']); ?></div>
                        <div style="font-size:11px; color:#888;"><?php echo htmlspecialchars($o['phone']); ?></div>
                        <div style="font-size:11px; color:#aaa;"><?php echo htmlspecialchars($o['email']); ?></div>
                    </td>
                    <td style="padding:14px 16px; font-size:12px; color:#555;">
                        <?php foreach ($items as $it): ?>
                        <div><?php echo htmlspecialchars($it['item_name']); ?> <span class="text-muted">×<?php echo $it['quantity']; ?></span></div>
                        <?php endforeach; ?>
                    </td>
                    <td style="padding:14px 16px;">
                        <span style="font-weight:700; color:#dc3545; font-size:15px;">
                            <?php echo formatPrice($o['total_amount']); ?>
                        </span>
                    </td>
                    <td style="padding:14px 16px;">
                        <span class="badge bg-secondary text-uppercase" style="font-size:10px; letter-spacing:0.5px;">
                            <?php echo htmlspecialchars($o['payment_method']); ?>
                        </span>
                    </td>
                    <td style="padding:14px 16px; font-size:12px; color:#666;">
                        <?php echo date('M d, Y', strtotime($o['order_date'])); ?><br>
                        <span style="color:#aaa;"><?php echo date('h:i A', strtotime($o['order_date'])); ?></span>
                    </td>
                    <td style="padding:14px 16px; color:unset !important;">
                        <span class="status-pill" id="status-badge-<?php echo $o['order_id']; ?>"
                              style="background:<?php echo $s['bg']; ?>; color:<?php echo $s['text']; ?> !important;">
                            <i class="fas <?php echo $s['icon']; ?>" style="color:<?php echo $s['text']; ?> !important;"></i>
                            <?php echo $s['label']; ?>
                        </span>
                    </td>
                    <td style="padding:14px 16px;">
                        <form method="post" class="d-flex gap-2 align-items-center status-form"
                              data-order="<?php echo $o['order_id']; ?>">
                            <input type="hidden" name="order_id" value="<?php echo $o['order_id']; ?>">
                            <select name="status" class="status-select"
                                    id="select-<?php echo $o['order_id']; ?>">
                                <?php foreach ($statuses as $key => $sv): ?>
                                <option value="<?php echo $key; ?>" <?php echo $o['status']===$key ? 'selected' : ''; ?>>
                                    <?php echo $sv['label']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="update_status"
                                    class="btn btn-sm btn-primary px-3"
                                    title="Save status">
                                <i class="fas fa-save"></i>
                            </button>
                        </form>
                    </td>
                    <td style="padding:14px 16px;">
                        <button class="btn btn-sm btn-outline-primary"
                                onclick="openOrderModal(<?php echo $o['order_id']; ?>)"
                                title="View full order details">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div><!-- /admin-content -->

<!-- ===================== ORDER DETAIL MODAL ===================== -->
<div class="modal fade" id="orderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0" style="border-radius:14px; overflow:hidden;">
            <div class="order-modal-header d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="mb-1" style="font-family:'Rubik',sans-serif; font-weight:700;" id="modalOrderTitle">Order Details</h5>
                    <p class="mb-0" style="font-size:13px; opacity:0.75;" id="modalOrderDate"></p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="modalOrderBody">
                <div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Store all order data as JSON for the modal -->
<script>
const ORDER_DATA = <?php
    $modal_data = [];
    foreach ($Order->getAllOrders() as $o) {
        $o['items'] = $Order->getOrderItems($o['order_id']);
        $modal_data[$o['order_id']] = $o;
    }
    echo json_encode($modal_data);
?>;

const STATUSES = <?php echo json_encode($statuses); ?>;

function openOrderModal(id) {
    const o = ORDER_DATA[id];
    if (!o) return;

    document.getElementById('modalOrderTitle').textContent = 'Order #' + id;
    document.getElementById('modalOrderDate').textContent  = 'Placed on ' + o.order_date;

    const steps = ['pending','processing','shipped','delivered'];
    const isCancelled = o.status === 'cancelled';
    const currentIdx  = steps.indexOf(o.status);

    // Build timeline
    let timeline = '';
    if (isCancelled) {
        timeline = `<li>
            <div class="tl-dot" style="background:#dc3545;color:#fff;"><i class="fas fa-times"></i></div>
            <div><strong style="color:#dc3545;">Order Cancelled</strong><br><small class="text-muted">This order was cancelled.</small></div>
        </li>`;
    } else {
        steps.forEach((step, i) => {
            const s = STATUSES[step];
            let dotClass = i < currentIdx ? 'done' : (i === currentIdx ? 'active' : 'idle');
            timeline += `<li>
                <div class="tl-dot ${dotClass}"><i class="fas ${s.icon}"></i></div>
                <div>
                    <strong style="font-size:13px; color:#212529;">${s.label}</strong>
                    ${i === currentIdx ? '<span class="badge bg-secondary ms-2" style="font-size:10px;">Current</span>' : ''}
                    <br><small style="font-size:11px; color:#6c757d;">${i <= currentIdx ? '✓ Completed' : 'Pending'}</small>
                </div>
            </li>`;
        });
    }

    // Build items table
    let itemsHtml = o.items.map(it => `
        <tr>
            <td style="font-size:13px;">${it.item_name}</td>
            <td class="text-center" style="font-size:13px;">${it.quantity}</td>
            <td class="text-end text-danger fw-bold" style="font-size:13px;">$${parseFloat(it.item_price).toFixed(2)}</td>
            <td class="text-end fw-bold" style="font-size:13px;">$${(it.item_price * it.quantity).toFixed(2)}</td>
        </tr>`).join('');

    const s = STATUSES[o.status] || STATUSES['pending'];

    document.getElementById('modalOrderBody').innerHTML = `
        <div class="row g-4">

            <!-- Left: Customer + Delivery -->
            <div class="col-md-6">
                <h6 style="font-family:'Rubik',sans-serif;font-weight:600;color:var(--primary);margin-bottom:12px;">
                    <i class="fas fa-user me-2"></i>Customer Info
                </h6>
                <div class="bg-light rounded-3 p-3" style="font-size:13px;">
                    <div class="mb-1"><strong style="color:#212529;">${o.full_name}</strong></div>
                    <div style="color:#555; margin-bottom:4px;"><i class="fas fa-envelope me-1"></i>${o.email}</div>
                    <div style="color:#555; margin-bottom:4px;"><i class="fas fa-phone me-1"></i>${o.phone}</div>
                    <div style="color:#555;"><i class="fas fa-map-marker-alt me-1"></i>${o.address}, ${o.city}</div>
                </div>

                <h6 style="font-family:'Rubik',sans-serif;font-weight:600;color:var(--primary);margin:16px 0 12px;">
                    <i class="fas fa-credit-card me-2"></i>Payment
                </h6>
                <div class="bg-light rounded-3 p-3" style="font-size:13px;">
                    <span class="badge bg-secondary text-uppercase" style="font-size:11px;letter-spacing:0.5px;">${o.payment_method}</span>
                    <span class="ms-2" style="font-size:13px; color:#212529;">Total: <strong class="text-danger">$${parseFloat(o.total_amount).toFixed(2)}</strong></span>
                </div>
            </div>

            <!-- Right: Timeline -->
            <div class="col-md-6">
                <h6 style="font-family:'Rubik',sans-serif;font-weight:600;color:var(--primary);margin-bottom:12px;">
                    <i class="fas fa-route me-2"></i>Order Progress
                </h6>
                <div class="bg-light rounded-3 p-3">
                    <ul class="order-timeline">${timeline}</ul>
                </div>
                <div class="mt-3 p-3 rounded-3" style="background:${s.bg}; color:${s.text}; font-size:13px; font-weight:600;">
                    <i class="fas ${s.icon} me-2"></i>Current Status: ${s.label}
                </div>
            </div>

            <!-- Items -->
            <div class="col-12">
                <h6 style="font-family:'Rubik',sans-serif;font-weight:600;color:var(--primary);margin-bottom:12px;">
                    <i class="fas fa-box me-2"></i>Order Items
                </h6>
                <table class="table table-sm table-bordered mb-0" style="font-size:13px;">
                    <thead style="background:#f8f9fa;">
                        <tr>
                            <th>Product</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>${itemsHtml}</tbody>
                    <tfoot style="background:#f8f9fa;">
                        <tr>
                            <td colspan="3" class="text-end fw-bold">Total</td>
                            <td class="text-end fw-bold text-danger">$${parseFloat(o.total_amount).toFixed(2)}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

        </div>`;

    new bootstrap.Modal(document.getElementById('orderModal')).show();
}

// Live status badge color update on select change
document.querySelectorAll('.status-select').forEach(sel => {
    sel.addEventListener('change', function() {
        const orderId = this.closest('form').dataset.order;
        const badge   = document.getElementById('status-badge-' + orderId);
        const s       = STATUSES[this.value];
        if (badge && s) {
            badge.style.background = s.bg;
            badge.style.color      = s.text;
            badge.style.setProperty('color', s.text, 'important');
            badge.innerHTML = `<i class="fas ${s.icon}" style="color:${s.text} !important;"></i> ${s.label}`;
        }
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.modal-backdrop').forEach(function(el){ el.remove(); });
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
});
</script>
</body>
</html>

