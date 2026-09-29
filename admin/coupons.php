<?php
require_once '../functions.php';
if (!$auth->isAdmin()) { header('Location: login.php'); exit; }

// Ensure coupons table exists
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

$success = '';
$error   = '';

// Send coupon by email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_coupon_email'])) {
    $cid        = (int)($_POST['coupon_id'] ?? 0);
    $send_email = trim($_POST['send_email'] ?? '');
    $send_name  = trim($_POST['send_name']  ?? 'Valued Customer');

    if (!filter_var($send_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $cpn_res = $db->con->query("SELECT * FROM coupons WHERE coupon_id={$cid} LIMIT 1");
        if ($cpn_res && $cpn_res->num_rows > 0) {
            $cpn = $cpn_res->fetch_assoc();
            $discount_text = $cpn['type'] === 'percent'
                ? $cpn['value'] . '% OFF'
                : '$' . number_format($cpn['value'], 2) . ' OFF';
            $min_text = $cpn['min_order'] > 0
                ? "\nMinimum order: $" . number_format($cpn['min_order'], 2)
                : '';
            $exp_text = !empty($cpn['expires_at'])
                ? "\nExpires: " . date('M d, Y', strtotime($cpn['expires_at']))
                : '';

            $email_body =
                "Hi {$send_name},\n\n" .
                "Here is your exclusive discount coupon from Isa & Dagi Mobile Shop!\n\n" .
                "━━━━━━━━━━━━━━━━━━━━━━\n" .
                "  Coupon Code:  " . $cpn['code'] . "\n" .
                "  Discount:     " . $discount_text . "\n" .
                $min_text . $exp_text . "\n" .
                "━━━━━━━━━━━━━━━━━━━━━━\n\n" .
                "How to use:\n" .
                "1. Add items to your cart\n" .
                "2. Go to Checkout\n" .
                "3. Enter the code above in the coupon field\n" .
                "4. Your discount will be applied automatically!\n\n" .
                "Shop now: http://" . $_SERVER['HTTP_HOST'] . "/mymobileshop/\n\n" .
                "-- Isa & Dagi Mobile Shop\n" .
                "Phone: +251 911 000 000\n" .
                "Telegram: @isadagishop";

            require_once '../mailer.php';
            sendMail($send_email, 'Your Exclusive Coupon: ' . $cpn['code'] . ' — Isa & Dagi', $email_body);
            $success = "Coupon <strong>{$cpn['code']}</strong> sent to <strong>{$send_email}</strong> successfully!";
        } else {
            $error = 'Coupon not found.';
        }
    }
}

// Add coupon
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_coupon'])) {
    $code      = strtoupper(trim($_POST['code'] ?? ''));
    $type      = in_array($_POST['type'] ?? '', ['percent','fixed']) ? $_POST['type'] : 'percent';
    $value     = (float)($_POST['value'] ?? 10);
    $min_order = (float)($_POST['min_order'] ?? 0);
    $max_uses  = (int)($_POST['max_uses'] ?? 100);
    $expires   = !empty($_POST['expires_at']) ? "'" . mysqli_real_escape_string($db->con, $_POST['expires_at']) . "'" : 'NULL';

    if (empty($code)) {
        $error = 'Coupon code is required.';
    } elseif ($value <= 0) {
        $error = 'Discount value must be greater than 0.';
    } elseif ($type === 'percent' && $value > 100) {
        $error = 'Percentage discount cannot exceed 100%.';
    } else {
        $code_esc = mysqli_real_escape_string($db->con, $code);
        $res = $db->con->query("INSERT INTO coupons (code, type, value, min_order, max_uses, expires_at)
            VALUES ('{$code_esc}', '{$type}', {$value}, {$min_order}, {$max_uses}, {$expires})");
        if ($res) {
            $success = "Coupon <strong>{$code}</strong> created successfully!";
        } else {
            $error = $db->con->errno === 1062 ? "Coupon code '{$code}' already exists." : 'Failed to create coupon.';
        }
    }
}

// Toggle active
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_coupon'])) {
    $cid = (int)$_POST['coupon_id'];
    $db->con->query("UPDATE coupons SET is_active = 1 - is_active WHERE coupon_id = {$cid}");
    $success = 'Coupon status updated.';
}

// Delete coupon
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_coupon'])) {
    $cid = (int)$_POST['coupon_id'];
    $db->con->query("DELETE FROM coupons WHERE coupon_id = {$cid}");
    $success = 'Coupon deleted.';
}

// Fetch all coupons
$coupons = [];
$res = $db->con->query("SELECT * FROM coupons ORDER BY created_at DESC");
if ($res) while ($row = $res->fetch_assoc()) $coupons[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coupons — Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>

<?php $active_page = 'coupons'; include '_sidebar.php'; ?>

<div class="admin-content">
    <div class="admin-topbar">
        <h5 class="mb-0" style="font-family:'Rubik',sans-serif;font-weight:600;">
            <i class="fas fa-tag me-2 text-success"></i>Coupon / Discount Codes
        </h5>
        <span class="text-muted" style="font-size:13px;"><?php echo count($coupons); ?> coupon(s)</span>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2" style="border-radius:10px;">
        <i class="fas fa-check-circle"></i><?php echo $success; ?>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2" style="border-radius:10px;">
        <i class="fas fa-exclamation-circle"></i><?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Add Coupon Form -->
        <div class="col-lg-4">
            <div class="bg-white rounded-3 shadow-sm p-4">
                <h6 style="font-family:'Rubik',sans-serif;font-weight:700;color:#003859;margin-bottom:20px;">
                    <i class="fas fa-plus-circle me-2 text-success"></i>Create New Coupon
                </h6>
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label" style="font-size:13px;font-weight:600;">Coupon Code *</label>
                        <input type="text" name="code" class="form-control" placeholder="e.g. SAVE20"
                               style="text-transform:uppercase;font-weight:700;letter-spacing:1px;" required>
                        <small class="text-muted" style="font-size:11px;">Will be auto-uppercased</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:13px;font-weight:600;">Discount Type *</label>
                        <select name="type" class="form-select" id="couponType" onchange="updateValueLabel()">
                            <option value="percent">Percentage (%) off</option>
                            <option value="fixed">Fixed Amount ($) off</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:13px;font-weight:600;" id="valueLabel">Discount Value (%) *</label>
                        <div class="input-group">
                            <span class="input-group-text" id="valuePrefix">%</span>
                            <input type="number" name="value" class="form-control" value="10" min="1" step="0.01" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:13px;font-weight:600;">Minimum Order Amount ($)</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" name="min_order" class="form-control" value="0" min="0" step="0.01">
                        </div>
                        <small class="text-muted" style="font-size:11px;">0 = no minimum</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-size:13px;font-weight:600;">Max Uses</label>
                        <input type="number" name="max_uses" class="form-control" value="100" min="1">
                    </div>
                    <div class="mb-4">
                        <label class="form-label" style="font-size:13px;font-weight:600;">Expiry Date (optional)</label>
                        <input type="date" name="expires_at" class="form-control"
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <button type="submit" name="add_coupon" class="btn btn-success w-100 fw-bold">
                        <i class="fas fa-plus me-2"></i>Create Coupon
                    </button>
                </form>
            </div>
        </div>

        <!-- Coupons Table -->
        <div class="col-lg-8">
            <div class="admin-table">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Discount</th>
                            <th>Min Order</th>
                            <th>Uses</th>
                            <th>Expires</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($coupons)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="fas fa-tag fa-2x mb-2 d-block"></i>No coupons yet. Create one!
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($coupons as $c):
                            $is_expired = !empty($c['expires_at']) && strtotime($c['expires_at']) < time();
                            $is_maxed   = $c['used_count'] >= $c['max_uses'];
                            $effective  = $c['is_active'] && !$is_expired && !$is_maxed;
                        ?>
                        <tr>
                            <td>
                                <span style="font-family:monospace;font-size:15px;font-weight:700;color:#003859;background:#f0f9ff;padding:3px 10px;border-radius:6px;letter-spacing:1px;">
                                    <?php echo htmlspecialchars($c['code']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($c['type'] === 'percent'): ?>
                                <span class="badge bg-success" style="font-size:13px;"><?php echo $c['value']; ?>% OFF</span>
                                <?php else: ?>
                                <span class="badge bg-primary" style="font-size:13px;">$<?php echo number_format($c['value'],2); ?> OFF</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:13px;">
                                <?php echo $c['min_order'] > 0 ? '$' . number_format($c['min_order'],2) : '<span class="text-muted">None</span>'; ?>
                            </td>
                            <td style="font-size:13px;">
                                <span style="color:<?php echo $is_maxed ? '#dc3545' : '#333'; ?>;">
                                    <?php echo $c['used_count']; ?> / <?php echo $c['max_uses']; ?>
                                </span>
                                <div style="height:4px;background:#f0f0f0;border-radius:2px;margin-top:3px;width:80px;">
                                    <div style="width:<?php echo min(100, round($c['used_count']/$c['max_uses']*100)); ?>%;height:100%;background:<?php echo $is_maxed?'#dc3545':'#28a745'; ?>;border-radius:2px;"></div>
                                </div>
                            </td>
                            <td style="font-size:12px;color:<?php echo $is_expired ? '#dc3545' : '#555'; ?>;">
                                <?php echo !empty($c['expires_at']) ? date('M d, Y', strtotime($c['expires_at'])) : '<span class="text-muted">Never</span>'; ?>
                                <?php if ($is_expired): ?><br><span class="badge bg-danger" style="font-size:9px;">EXPIRED</span><?php endif; ?>
                            </td>
                            <td>
                                <?php if ($effective): ?>
                                <span class="badge bg-success">Active</span>
                                <?php elseif (!$c['is_active']): ?>
                                <span class="badge bg-secondary">Disabled</span>
                                <?php elseif ($is_expired): ?>
                                <span class="badge bg-danger">Expired</span>
                                <?php elseif ($is_maxed): ?>
                                <span class="badge bg-warning text-dark">Maxed</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="coupon_id" value="<?php echo $c['coupon_id']; ?>">
                                        <button type="submit" name="toggle_coupon"
                                                class="btn btn-sm <?php echo $c['is_active'] ? 'btn-outline-warning' : 'btn-outline-success'; ?>"
                                                title="<?php echo $c['is_active'] ? 'Disable' : 'Enable'; ?>">
                                            <i class="fas <?php echo $c['is_active'] ? 'fa-pause' : 'fa-play'; ?>"></i>
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-sm btn-outline-info"
                                            title="Send by Email"
                                            onclick="openSendModal(<?php echo $c['coupon_id']; ?>, '<?php echo htmlspecialchars($c['code']); ?>')">
                                        <i class="fas fa-envelope"></i>
                                    </button>
                                    <form method="post" class="d-inline" onsubmit="return confirm('Delete coupon <?php echo htmlspecialchars($c['code']); ?>?')">
                                        <input type="hidden" name="coupon_id" value="<?php echo $c['coupon_id']; ?>">
                                        <button type="submit" name="delete_coupon" class="btn btn-sm btn-outline-danger" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function updateValueLabel() {
    var type = document.getElementById('couponType').value;
    document.getElementById('valueLabel').textContent = type === 'percent' ? 'Discount Value (%) *' : 'Discount Amount ($) *';
    document.getElementById('valuePrefix').textContent = type === 'percent' ? '%' : '$';
}

function openSendModal(couponId, couponCode) {
    document.getElementById('sendCouponId').value = couponId;
    document.getElementById('sendModalCode').textContent = couponCode;
    document.getElementById('sendEmail').value = '';
    document.getElementById('sendName').value  = '';
    new bootstrap.Modal(document.getElementById('sendCouponModal')).show();
}
</script>

<!-- Send Coupon by Email Modal -->
<div class="modal fade" id="sendCouponModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content border-0 rounded-3 shadow">
            <div class="modal-header" style="background:linear-gradient(135deg,#003859,#00A5C4);color:#fff;border-radius:12px 12px 0 0;">
                <h6 class="modal-title mb-0" style="font-family:'Rubik',sans-serif;font-weight:700;">
                    <i class="fas fa-envelope me-2"></i>Send Coupon by Email
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p style="font-size:13px;color:#555;margin-bottom:16px;">
                    Send coupon code <strong id="sendModalCode" style="color:#003859;font-family:monospace;font-size:15px;"></strong> directly to a customer's email.
                </p>
                <form method="post">
                    <input type="hidden" name="coupon_id" id="sendCouponId">
                    <div class="mb-3">
                        <label class="form-label" style="font-size:13px;font-weight:600;">Customer Name</label>
                        <input type="text" name="send_name" id="sendName" class="form-control" placeholder="e.g. John Doe" style="font-size:13px;">
                    </div>
                    <div class="mb-4">
                        <label class="form-label" style="font-size:13px;font-weight:600;">Customer Email *</label>
                        <input type="email" name="send_email" id="sendEmail" class="form-control" placeholder="customer@email.com" required style="font-size:13px;">
                    </div>
                    <button type="submit" name="send_coupon_email" class="btn btn-info w-100 text-white fw-bold">
                        <i class="fas fa-paper-plane me-2"></i>Send Coupon
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
        </div>
    </div>
</div>
</body>
</html>