<?php
require_once '../functions.php';

if (!$auth->canAccessAdmin()) {
    header('Location: login.php');
    exit;
}

// Prevent browser from caching the dashboard so status counts are always fresh
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$total_products    = $product->countProducts();
$total_orders      = $Order->countOrders();
$total_revenue     = $Order->totalRevenue();
$total_users       = $db->con->query("SELECT COUNT(*) as c FROM user")->fetch_assoc()['c'];
$total_accessories = $db->con->query("SELECT COUNT(*) as c FROM accessories")->fetch_assoc()['c'];
$recent_orders     = array_slice($Order->getAllOrders(), 0, 5);

// Low stock alerts (products with stock <= 5)
$low_stock_products = [];
$ls = $db->con->query("SELECT item_id, item_name, item_brand, item_stock FROM product WHERE item_stock <= 5 ORDER BY item_stock ASC");
while ($row = $ls->fetch_assoc()) $low_stock_products[] = $row;

$low_stock_acc = [];
$la = $db->con->query("SELECT acc_id, acc_name, acc_category, acc_stock FROM accessories WHERE acc_stock <= 5 ORDER BY acc_stock ASC");
while ($row = $la->fetch_assoc()) $low_stock_acc[] = $row;

// Pending orders count
$pending_count = $db->con->query("SELECT COUNT(*) as c FROM orders WHERE status='pending'")->fetch_assoc()['c'];

// Sales chart data — last 7 days
$chart_labels = [];
$chart_revenue = [];
$chart_orders  = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $label = date('M d', strtotime("-{$i} days"));
    $chart_labels[] = $label;
    $r = $db->con->query("SELECT COALESCE(SUM(total_amount),0) as rev, COUNT(*) as cnt FROM orders WHERE DATE(order_date)='{$date}' AND status != 'cancelled'")->fetch_assoc();
    $chart_revenue[] = round((float)$r['rev'], 2);
    $chart_orders[]  = (int)$r['cnt'];
}

// Top 5 selling products
$top_products = [];
$tp = $db->con->query("SELECT oi.item_name, SUM(oi.quantity) as total_qty, SUM(oi.item_price * oi.quantity) as total_rev
    FROM order_items oi JOIN orders o ON oi.order_id = o.order_id
    WHERE o.status != 'cancelled'
    GROUP BY oi.item_name ORDER BY total_qty DESC LIMIT 5");
if ($tp) while ($row = $tp->fetch_assoc()) $top_products[] = $row;

// Order status breakdown
$status_counts = [];
foreach (['pending','processing','shipped','delivered','cancelled'] as $st) {
    $status_counts[$st] = (int)$db->con->query("SELECT COUNT(*) as c FROM orders WHERE status='{$st}'")->fetch_assoc()['c'];
}

// Unread contact messages
$unread_msgs_count = (int)$db->con->query("SELECT COUNT(*) as c FROM contact_messages WHERE is_read=0")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel — Isa & Dagi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>

<!-- Sidebar -->
<?php $active_page = 'dashboard'; include '_sidebar.php'; ?>

<!-- Content -->
<div class="admin-content">
    <div class="admin-topbar">
        <h5 class="mb-0" style="font-family:'Rubik',sans-serif; font-weight:600;">Dashboard</h5>
        <span style="font-size:13px; color:#888;">Welcome back, <strong><?php echo htmlspecialchars($_SESSION['first_name']); ?></strong></span>
    </div>

    <!-- Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-2 col-sm-6">
            <a href="products.php" class="text-decoration-none">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div><div class="stat-label">Products</div><div class="stat-value"><?php echo $total_products; ?></div></div>
                    <i class="fas fa-box stat-icon text-primary"></i>
                </div>
            </div></a>
        </div>
        <div class="col-md-2 col-sm-6">
            <a href="accessories.php" class="text-decoration-none">
            <div class="stat-card" style="border-left-color:#00A5C4;">
                <div class="d-flex justify-content-between align-items-start">
                    <div><div class="stat-label">Accessories</div><div class="stat-value"><?php echo $total_accessories; ?></div></div>
                    <i class="fas fa-headphones stat-icon text-info"></i>
                </div>
            </div></a>
        </div>
        <div class="col-md-2 col-sm-6">
            <a href="orders.php" class="text-decoration-none">
            <div class="stat-card" style="border-left-color:#28a745;">
                <div class="d-flex justify-content-between align-items-start">
                    <div><div class="stat-label">Total Orders</div><div class="stat-value"><?php echo $total_orders; ?></div></div>
                    <i class="fas fa-shopping-bag stat-icon text-success"></i>
                </div>
            </div></a>
        </div>
        <div class="col-md-2 col-sm-6">
            <a href="orders.php?status=pending" class="text-decoration-none">
            <div class="stat-card" style="border-left-color:#fd7e14;">
                <div class="d-flex justify-content-between align-items-start">
                    <div><div class="stat-label">Pending Orders</div><div class="stat-value"><?php echo $pending_count; ?></div></div>
                    <i class="fas fa-clock stat-icon text-warning"></i>
                </div>
            </div></a>
        </div>
        <div class="col-md-2 col-sm-6">
            <div class="stat-card" style="border-left-color:#dc3545;">
                <div class="d-flex justify-content-between align-items-start">
                    <div><div class="stat-label">Revenue</div><div class="stat-value" style="font-size:20px;"><?php echo formatPrice($total_revenue); ?></div></div>
                    <i class="fas fa-dollar-sign stat-icon text-danger"></i>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-6">
            <a href="users.php" class="text-decoration-none">
            <div class="stat-card" style="border-left-color:#ffc107;">
                <div class="d-flex justify-content-between align-items-start">
                    <div><div class="stat-label">Users</div><div class="stat-value"><?php echo $total_users; ?></div></div>
                    <i class="fas fa-users stat-icon text-warning"></i>
                </div>
            </div></a>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">
        <!-- Revenue Chart -->
        <div class="col-lg-8">
            <div class="bg-white rounded-3 shadow-sm p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0" style="font-family:'Rubik',sans-serif;font-weight:700;color:#003859;">
                        <i class="fas fa-chart-line me-2 text-primary"></i>Revenue — Last 7 Days
                    </h6>
                    <span style="font-size:12px;color:#888;">Total: <strong class="text-danger"><?php echo formatPrice(array_sum($chart_revenue)); ?></strong></span>
                </div>
                <canvas id="revenueChart" height="100"></canvas>
            </div>
        </div>
        <!-- Order Status Donut -->
        <div class="col-lg-4">
            <div class="bg-white rounded-3 shadow-sm p-4">
                <h6 class="mb-3" style="font-family:'Rubik',sans-serif;font-weight:700;color:#003859;">
                    <i class="fas fa-chart-pie me-2 text-info"></i>Order Status
                </h6>
                <canvas id="statusChart" height="160"></canvas>
                <div class="mt-3 d-flex flex-wrap gap-2 justify-content-center" style="font-size:11px;">
                    <?php
                    $sc_colors = ['pending'=>'#ffc107','processing'=>'#17a2b8','shipped'=>'#007bff','delivered'=>'#28a745','cancelled'=>'#dc3545'];
                    foreach ($status_counts as $st => $cnt): ?>
                    <span style="display:inline-flex;align-items:center;gap:4px;">
                        <span style="width:10px;height:10px;border-radius:50%;background:<?php echo $sc_colors[$st]; ?>;display:inline-block;"></span>
                        <?php echo ucfirst($st); ?> (<?php echo $cnt; ?>)
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Products + Contact Messages -->
    <div class="row g-4 mb-4">
        <!-- Top Selling Products -->
        <div class="col-lg-6">
            <div class="bg-white rounded-3 shadow-sm overflow-hidden">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0" style="font-family:'Rubik',sans-serif;font-weight:600;">
                        <i class="fas fa-trophy me-2 text-warning"></i>Top Selling Products
                    </h6>
                </div>
                <div class="p-3">
                    <?php if (empty($top_products)): ?>
                    <p class="text-muted text-center py-3" style="font-size:13px;">No sales data yet.</p>
                    <?php else: ?>
                    <?php $max_qty = max(array_column($top_products, 'total_qty')); ?>
                    <?php foreach ($top_products as $i => $tp): ?>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span style="width:22px;height:22px;background:<?php echo ['#FFD700','#C0C0C0','#CD7F32','#003859','#00A5C4'][$i]; ?>;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;"><?php echo $i+1; ?></span>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($tp['item_name']); ?></div>
                            <div style="height:6px;background:#f0f0f0;border-radius:3px;margin-top:4px;overflow:hidden;">
                                <div style="width:<?php echo round($tp['total_qty']/$max_qty*100); ?>%;height:100%;background:linear-gradient(90deg,#003859,#00A5C4);border-radius:3px;"></div>
                            </div>
                        </div>
                        <div class="text-end" style="flex-shrink:0;">
                            <div style="font-size:12px;font-weight:700;color:#003859;"><?php echo $tp['total_qty']; ?> sold</div>
                            <div style="font-size:11px;color:#dc3545;"><?php echo formatPrice($tp['total_rev']); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <!-- Unread Contact Messages -->
        <div class="col-lg-6">
            <div class="bg-white rounded-3 shadow-sm overflow-hidden">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0" style="font-family:'Rubik',sans-serif;font-weight:600;">
                        <i class="fas fa-envelope me-2 text-primary"></i>Contact Messages
                        <?php if ($unread_msgs_count > 0): ?>
                        <span class="badge bg-danger ms-1"><?php echo $unread_msgs_count; ?> new</span>
                        <?php endif; ?>
                    </h6>
                    <a href="settings.php#messages" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <?php
                $recent_msgs = [];
                $rm = $db->con->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 5");
                if ($rm) while ($row = $rm->fetch_assoc()) $recent_msgs[] = $row;
                ?>
                <?php if (empty($recent_msgs)): ?>
                <p class="text-muted text-center py-4" style="font-size:13px;">No messages yet.</p>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($recent_msgs as $msg): ?>
                    <a href="settings.php#messages" class="list-group-item list-group-item-action px-3 py-2 <?php echo !$msg['is_read'] ? 'bg-light' : ''; ?>">
                        <div class="d-flex justify-content-between align-items-start">
                            <div style="min-width:0;flex:1;">
                                <div style="font-size:13px;font-weight:<?php echo !$msg['is_read'] ? '700' : '500'; ?>;color:#212529;">
                                    <?php if (!$msg['is_read']): ?><span class="badge bg-danger me-1" style="font-size:9px;">NEW</span><?php endif; ?>
                                    <?php echo htmlspecialchars($msg['name']); ?>
                                    <span style="font-weight:400;color:#888;font-size:12px;">— <?php echo htmlspecialchars($msg['subject']); ?></span>
                                </div>
                                <div style="font-size:11px;color:#888;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars(mb_substr($msg['message'],0,60)); ?>...</div>
                            </div>
                            <span style="font-size:10px;color:#aaa;flex-shrink:0;margin-left:8px;"><?php echo date('M d', strtotime($msg['created_at'])); ?></span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Low Stock Alerts -->
    <?php if (!empty($low_stock_products) || !empty($low_stock_acc)): ?>
    <div class="bg-white rounded-3 shadow-sm mb-4 overflow-hidden">
        <div class="p-3 border-bottom d-flex align-items-center gap-2" style="background:#fff3cd;">
            <i class="fas fa-exclamation-triangle text-warning"></i>
            <h6 class="mb-0 fw-bold" style="color:#856404;">Low Stock Alert — <?php echo count($low_stock_products) + count($low_stock_acc); ?> item(s) running low</h6>
        </div>
        <div class="p-3">
            <div class="row g-2">
                <?php foreach ($low_stock_products as $ls): ?>
                <div class="col-md-3 col-sm-6">
                    <div class="d-flex align-items-center gap-2 p-2 rounded-2" style="background:#fff8f0;border:1px solid #ffe0b2;">
                        <i class="fas fa-mobile-alt text-warning"></i>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($ls['item_name']); ?></div>
                            <div style="font-size:11px;color:#888;"><?php echo htmlspecialchars($ls['item_brand']); ?></div>
                        </div>
                        <span class="badge <?php echo $ls['item_stock'] == 0 ? 'bg-danger' : 'bg-warning text-dark'; ?>">
                            <?php echo $ls['item_stock'] == 0 ? 'Out' : $ls['item_stock']; ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php foreach ($low_stock_acc as $la): ?>
                <div class="col-md-3 col-sm-6">
                    <div class="d-flex align-items-center gap-2 p-2 rounded-2" style="background:#f0f8ff;border:1px solid #b3d9ff;">
                        <i class="fas fa-headphones text-info"></i>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($la['acc_name']); ?></div>
                            <div style="font-size:11px;color:#888;"><?php echo htmlspecialchars($la['acc_category']); ?></div>
                        </div>
                        <span class="badge <?php echo $la['acc_stock'] == 0 ? 'bg-danger' : 'bg-warning text-dark'; ?>">
                            <?php echo $la['acc_stock'] == 0 ? 'Out' : $la['acc_stock']; ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Orders -->
    <div class="admin-table">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="mb-0" style="font-family:'Rubik',sans-serif; font-weight:600;">Recent Orders</h6>
            <a href="orders.php" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>Order #</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_orders as $o):
                    $colors = ['pending'=>'warning','processing'=>'info','shipped'=>'primary','delivered'=>'success','cancelled'=>'danger'];
                    $c = $colors[$o['status']] ?? 'secondary';
                ?>
                <tr>
                    <td>#<?php echo $o['order_id']; ?></td>
                    <td><?php echo htmlspecialchars($o['first_name'] . ' ' . $o['last_name']); ?></td>
                    <td class="text-danger fw-bold"><?php echo formatPrice($o['total_amount']); ?></td>
                    <td style="text-transform:uppercase; font-size:12px;"><?php echo $o['payment_method']; ?></td>
                    <td><span class="badge bg-<?php echo $c; ?> text-capitalize"><?php echo $o['status']; ?></span></td>
                    <td style="font-size:12px;"><?php echo date('M d, Y', strtotime($o['order_date'])); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($recent_orders)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No orders yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Revenue Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revenueCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($chart_labels); ?>,
        datasets: [{
            label: 'Revenue ($)',
            data: <?php echo json_encode($chart_revenue); ?>,
            backgroundColor: 'rgba(0,165,196,0.2)',
            borderColor: '#00A5C4',
            borderWidth: 2,
            borderRadius: 6,
            tension: 0.4,
            fill: true,
        },{
            label: 'Orders',
            data: <?php echo json_encode($chart_orders); ?>,
            type: 'line',
            borderColor: '#003859',
            backgroundColor: 'rgba(0,56,89,0.08)',
            borderWidth: 2,
            pointBackgroundColor: '#003859',
            pointRadius: 4,
            tension: 0.4,
            yAxisID: 'y2',
        }]
    },
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { position: 'top', labels: { font: { size: 12 } } } },
        scales: {
            y:  { beginAtZero: true, ticks: { callback: v => '$' + v } },
            y2: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, ticks: { stepSize: 1 } }
        }
    }
});

// Status Donut Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: ['Pending','Processing','Shipped','Delivered','Cancelled'],
        datasets: [{
            data: <?php echo json_encode(array_values($status_counts)); ?>,
            backgroundColor: ['#ffc107','#17a2b8','#007bff','#28a745','#dc3545'],
            borderWidth: 2,
            borderColor: '#fff',
        }]
    },
    options: {
        responsive: true,
        cutout: '65%',
        plugins: { legend: { display: false } }
    }
});
</script>
</body>
</html>

