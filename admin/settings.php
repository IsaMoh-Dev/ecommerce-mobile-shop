<?php
require_once '../functions.php';
if (!$auth->isAdmin()) { header('Location: login.php'); exit; }

// ── DATABASE EXPORT ──────────────────────────────────────────────────────────
if (isset($_GET['export_db'])) {
    $tables = ['user','brand','product','accessories','cart','wishlist',
               'orders','order_items','reviews','blog','password_resets',
               'newsletter','contact_messages','settings'];

    $output  = "-- =====================================================\n";
    $output .= "-- Isa & Dagi Mobile Shop — Full Database Export\n";
    $output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $output .= "-- Import this file into a new 'shopee' database\n";
    $output .= "-- =====================================================\n\n";
    $output .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $output .= "SET AUTOCOMMIT = 0;\nSTART TRANSACTION;\n";
    $output .= "SET time_zone = \"+00:00\";\n\n";

    foreach ($tables as $table) {
        // Check table exists
        $check = $db->con->query("SHOW TABLES LIKE '{$table}'");
        if (!$check || $check->num_rows === 0) continue;

        $output .= "-- --------------------------------------------------------\n";
        $output .= "-- Table: `{$table}`\n";
        $output .= "-- --------------------------------------------------------\n\n";

        // DROP + CREATE
        $output .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $create = $db->con->query("SHOW CREATE TABLE `{$table}`");
        $row    = $create->fetch_assoc();
        $output .= $row['Create Table'] . ";\n\n";

        // INSERT data
        $rows = $db->con->query("SELECT * FROM `{$table}`");
        if ($rows && $rows->num_rows > 0) {
            $output .= "INSERT INTO `{$table}` VALUES\n";
            $inserts = [];
            while ($r = $rows->fetch_row()) {
                $vals = array_map(function($v) use ($db) {
                    if ($v === null) return 'NULL';
                    return "'" . mysqli_real_escape_string($db->con, $v) . "'";
                }, $r);
                $inserts[] = '(' . implode(', ', $vals) . ')';
            }
            $output .= implode(",\n", $inserts) . ";\n\n";
        }
    }

    $output .= "COMMIT;\n";

    $filename = 'shopee_backup_' . date('Y-m-d_H-i-s') . '.sql';
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($output));
    echo $output;
    exit;
}

$success = '';
$error   = '';

// Save any settings section
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // General settings
    if (isset($_POST['save_general'])) {
        $fields = ['shop_name','shop_phone','shop_email','shop_address'];
        foreach ($fields as $f) {
            $val = mysqli_real_escape_string($db->con, trim($_POST[$f] ?? ''));
            $db->con->query("INSERT INTO settings (setting_key,setting_value) VALUES ('{$f}','{$val}')
                             ON DUPLICATE KEY UPDATE setting_value='{$val}'");
        }
        // Save telegram separately
        if (isset($_POST['telegram_username'])) {
            $tg = mysqli_real_escape_string($db->con, trim($_POST['telegram_username']));
            $db->con->query("INSERT INTO settings (setting_key,setting_value) VALUES ('telegram','{$tg}')
                             ON DUPLICATE KEY UPDATE setting_value='{$tg}'");
        }
        $success = 'General settings saved!';
    }

    // Social media settings
    if (isset($_POST['save_social'])) {
        $socials = ['facebook','instagram','youtube','tiktok','twitter'];
        foreach ($socials as $s) {
            $val = mysqli_real_escape_string($db->con, trim($_POST[$s] ?? ''));
            $db->con->query("INSERT INTO settings (setting_key,setting_value) VALUES ('{$s}','{$val}')
                             ON DUPLICATE KEY UPDATE setting_value='{$val}'");
        }
        $tg = mysqli_real_escape_string($db->con, trim($_POST['telegram_username'] ?? ''));
        $db->con->query("INSERT INTO settings (setting_key,setting_value) VALUES ('telegram','{$tg}')
                         ON DUPLICATE KEY UPDATE setting_value='{$tg}'");
        $success = 'Social media links saved!';
    }

    // SEO settings
    if (isset($_POST['save_seo'])) {
        $fields = ['meta_description','meta_keywords'];
        foreach ($fields as $f) {
            $val = mysqli_real_escape_string($db->con, trim($_POST[$f] ?? ''));
            $db->con->query("INSERT INTO settings (setting_key,setting_value) VALUES ('{$f}','{$val}')
                             ON DUPLICATE KEY UPDATE setting_value='{$val}'");
        }
        $success = 'SEO settings saved!';
    }

    // Delivery settings
    if (isset($_POST['save_delivery'])) {
        $fields = ['delivery_fee','free_delivery_above','delivery_areas','delivery_note'];
        foreach ($fields as $f) {
            $val = mysqli_real_escape_string($db->con, trim($_POST[$f] ?? ''));
            $db->con->query("INSERT INTO settings (setting_key,setting_value) VALUES ('{$f}','{$val}')
                             ON DUPLICATE KEY UPDATE setting_value='{$val}'");
        }
        $success = 'Delivery settings saved!';
    }

    // Order settings
    if (isset($_POST['save_orders'])) {
        $fields = ['min_order_amount','order_notify_email','order_auto_confirm'];
        foreach ($fields as $f) {
            $val = mysqli_real_escape_string($db->con, trim($_POST[$f] ?? ''));
            $db->con->query("INSERT INTO settings (setting_key,setting_value) VALUES ('{$f}','{$val}')
                             ON DUPLICATE KEY UPDATE setting_value='{$val}'");
        }
        $success = 'Order settings saved!';
    }

    // Maintenance mode
    if (isset($_POST['save_maintenance'])) {
        $mode = isset($_POST['maintenance_mode']) ? '1' : '0';
        $msg  = mysqli_real_escape_string($db->con, trim($_POST['maintenance_message'] ?? ''));
        $db->con->query("INSERT INTO settings (setting_key,setting_value) VALUES ('maintenance_mode','{$mode}')
                         ON DUPLICATE KEY UPDATE setting_value='{$mode}'");
        $db->con->query("INSERT INTO settings (setting_key,setting_value) VALUES ('maintenance_message','{$msg}')
                         ON DUPLICATE KEY UPDATE setting_value='{$msg}'");
        $success = 'Maintenance settings saved!';
    }

    // Delete newsletter subscriber
    if (isset($_POST['delete_subscriber'])) {
        $sid = (int)$_POST['sub_id'];
        $db->con->query("DELETE FROM newsletter WHERE id={$sid}");
        $success = 'Subscriber removed.';
    }
    // Currency settings
    if (isset($_POST['save_currency'])) {
        $rate     = (float)$_POST['usd_to_birr'];
        $currency = in_array($_POST['currency'], ['USD','ETB']) ? $_POST['currency'] : 'USD';
        $db->con->query("INSERT INTO settings (setting_key,setting_value) VALUES ('usd_to_birr','{$rate}')
                         ON DUPLICATE KEY UPDATE setting_value='{$rate}'");
        $db->con->query("INSERT INTO settings (setting_key,setting_value) VALUES ('currency','{$currency}')
                         ON DUPLICATE KEY UPDATE setting_value='{$currency}'");
        $success = 'Currency settings saved!';
    }

    // Upload shop logo
    if (isset($_POST['save_logo'])) {
        if (!empty($_FILES['shop_logo']['name'])) {
            $allowed = ['jpg','jpeg','png','webp','svg','gif'];
            $ext     = strtolower(pathinfo($_FILES['shop_logo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $fname = 'logo_' . time() . '.' . $ext;
                $dest  = '../assets/' . $fname;
                if (move_uploaded_file($_FILES['shop_logo']['tmp_name'], $dest)) {
                    $val = mysqli_real_escape_string($db->con, 'assets/' . $fname);
                    $db->con->query("INSERT INTO settings (setting_key,setting_value) VALUES ('shop_logo','{$val}')
                                     ON DUPLICATE KEY UPDATE setting_value='{$val}'");
                    $success = 'Logo updated!';
                } else { $error = 'Logo upload failed.'; }
            } else { $error = 'Invalid file type. Use JPG, PNG, WebP, SVG.'; }
        } else { $error = 'Please choose a logo file.'; }
    }

    // Change admin password
    if (isset($_POST['change_admin_password'])) {
        $cur  = $_POST['current_password'] ?? '';
        $new  = $_POST['new_password']     ?? '';
        $conf = $_POST['confirm_password'] ?? '';

        $stmt = $db->con->prepare("SELECT password FROM user WHERE user_id=? LIMIT 1");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!password_verify($cur, $row['password'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($new !== $conf) {
            $error = 'New passwords do not match.';
        } else {
            $hash = password_hash($new, PASSWORD_BCRYPT);
            $uid  = (int)$_SESSION['user_id'];
            $db->con->query("UPDATE user SET password='{$hash}' WHERE user_id={$uid}");
            $success = 'Admin password changed successfully!';
        }
    }

}

// Load all settings
$settings = [];
$res = $db->con->query("SELECT * FROM settings");
if ($res) while ($row = $res->fetch_assoc()) $settings[$row['setting_key']] = $row['setting_value'];

// Load contact messages
$messages = [];
$res = $db->con->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
if ($res) while ($row = $res->fetch_assoc()) $messages[] = $row;
$unread_count = count(array_filter($messages, fn($m) => !$m['is_read']));

// Load newsletter subscribers
$subscribers = [];
$res = $db->con->query("SELECT * FROM newsletter ORDER BY subscribed_at DESC");
if ($res) while ($row = $res->fetch_assoc()) $subscribers[] = $row;

// Export newsletter as CSV
if (isset($_GET['export_newsletter'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="newsletter_subscribers_' . date('Y-m-d') . '.csv"');
    echo "Email,Subscribed Date\n";
    foreach ($subscribers as $sub) {
        echo '"' . $sub['email'] . '","' . $sub['subscribed_at'] . '"' . "\n";
    }
    exit;
}

// Mark message as read
if (isset($_GET['read_msg'])) {
    $mid = (int)$_GET['read_msg'];
    $db->con->query("UPDATE contact_messages SET is_read=1 WHERE msg_id={$mid}");
    header('Location: settings.php#messages');
    exit;
}
// Mark all messages as read
if (isset($_GET['read_all'])) {
    $db->con->query("UPDATE contact_messages SET is_read=1");
    header('Location: settings.php#messages');
    exit;
}
// Delete message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_msg'])) {
    $mid = (int)$_POST['msg_id'];
    $db->con->query("DELETE FROM contact_messages WHERE msg_id={$mid}");
    $success = 'Message deleted.';
}?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings — Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .settings-card {
            background:#fff; border-radius:14px;
            box-shadow:0 2px 12px rgba(0,0,0,0.07);
            padding:28px 32px; margin-bottom:24px;
        }
        .settings-card h5 {
            font-family:'Rubik',sans-serif; font-weight:700;
            color:#003859; margin-bottom:20px;
            padding-bottom:12px; border-bottom:2px solid #f0f0f0;
            display:flex; align-items:center; gap:10px;
        }
        .settings-card h5 i { color:#00A5C4; }
        .settings-label { font-size:13px; font-weight:600; color:#444; margin-bottom:5px; }
        .settings-input {
            border:1.5px solid #ddd; border-radius:8px;
            padding:10px 14px; font-size:14px;
            transition:border-color 0.2s;
        }
        .settings-input:focus { border-color:#00A5C4; box-shadow:0 0 0 3px rgba(0,165,196,0.1); outline:none; }
    </style>
</head>
<body>

<?php $active_page = 'settings'; include '_sidebar.php'; ?>

<div class="admin-content">
    <div class="admin-topbar">
        <h5 class="mb-0" style="font-family:'Rubik',sans-serif;font-weight:600;">
            <i class="fas fa-cog me-2"></i>Settings
        </h5>
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
    <div class="col-lg-8">

        <!-- 1. General Settings -->
        <div class="settings-card">
            <h5><i class="fas fa-store"></i>Shop Information</h5>
            <form method="post">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="settings-label">Shop Name</label>
                        <input type="text" name="shop_name" class="form-control settings-input"
                               value="<?php echo htmlspecialchars($settings['shop_name'] ?? 'Isa & Dagi Mobile Shop'); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="settings-label">Phone Number</label>
                        <input type="text" name="shop_phone" class="form-control settings-input"
                               value="<?php echo htmlspecialchars($settings['shop_phone'] ?? ''); ?>"
                               placeholder="+251 911 000 000">
                    </div>
                    <div class="col-md-6">
                        <label class="settings-label">Email Address</label>
                        <input type="email" name="shop_email" class="form-control settings-input"
                               value="<?php echo htmlspecialchars($settings['shop_email'] ?? ''); ?>"
                               placeholder="info@isadagi.com">
                    </div>
                    <div class="col-12">
                        <label class="settings-label">Shop Address</label>
                        <input type="text" name="shop_address" class="form-control settings-input"
                               value="<?php echo htmlspecialchars($settings['shop_address'] ?? ''); ?>"
                               placeholder="Merkato, Samson Building, Addis Ababa">
                    </div>
                    <div class="col-12">
                        <button type="submit" name="save_general" class="btn btn-primary px-4">
                            <i class="fas fa-save me-2"></i>Save Shop Info
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- 2. Currency Settings -->
        <div class="settings-card">
            <h5><i class="fas fa-exchange-alt"></i>Currency Settings</h5>
            <form method="post">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="settings-label">Default Currency</label>
                        <select name="currency" class="form-select settings-input">
                            <option value="USD" <?php echo ($settings['currency']??'USD')==='USD'?'selected':''; ?>>USD — US Dollar</option>
                            <option value="ETB" <?php echo ($settings['currency']??'USD')==='ETB'?'selected':''; ?>>ETB — Ethiopian Birr</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="settings-label">1 USD = ? ETB</label>
                        <div class="input-group">
                            <span class="input-group-text">ETB</span>
                            <input type="number" name="usd_to_birr" class="form-control settings-input"
                                   value="<?php echo htmlspecialchars($settings['usd_to_birr'] ?? '57'); ?>"
                                   step="0.01" min="1">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" name="save_currency" class="btn btn-primary w-100">
                            <i class="fas fa-save me-2"></i>Save Currency
                        </button>
                    </div>
                </div>
                <p class="text-muted mt-2 mb-0" style="font-size:12px;">
                    <i class="fas fa-info-circle me-1"></i>
                    Current rate: 1 USD = <strong><?php echo $settings['usd_to_birr'] ?? '57'; ?> ETB</strong>
                </p>
            </form>
        </div>

        <!-- 3. Shop Logo -->
        <div class="settings-card">
            <h5><i class="fas fa-image"></i>Shop Logo</h5>
            <form method="post" enctype="multipart/form-data">
                <div class="row g-3 align-items-center">
                    <div class="col-md-3 text-center">
                        <?php
                        $cur_logo = $settings['shop_logo'] ?? '';
                        $logo_src = ($cur_logo && file_exists('../' . ltrim($cur_logo, './')))
                            ? '../' . ltrim($cur_logo, './')
                            : '../assets/logo.svg';
                        ?>
                        <div style="background:#003859;border-radius:10px;padding:12px;display:inline-block;">
                            <img src="<?php echo htmlspecialchars($logo_src); ?>" id="logoPreview"
                                 style="height:50px;width:auto;object-fit:contain;filter:brightness(0) invert(1);"
                                 alt="Current Logo">
                        </div>
                        <div style="font-size:11px;color:#888;margin-top:6px;">Current logo</div>
                    </div>
                    <div class="col-md-6">
                        <label class="settings-label">Upload New Logo</label>
                        <input type="file" name="shop_logo" id="logoInput" class="form-control settings-input"
                               accept="image/*" onchange="previewLogo(this)">
                        <small class="text-muted" style="font-size:11px;">PNG, SVG, WebP recommended. Will appear in admin sidebar &amp; shop header.</small>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" name="save_logo" class="btn btn-primary w-100">
                            <i class="fas fa-upload me-2"></i>Upload Logo
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- 4. Social Media -->
        <div class="settings-card">
            <h5><i class="fas fa-share-alt"></i>Social Media Links</h5>
            <form method="post">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="settings-label"><i class="fab fa-telegram-plane me-1" style="color:#0088cc;"></i>Telegram</label>
                        <div class="input-group">
                            <span class="input-group-text" style="font-size:12px;">t.me/</span>
                            <input type="text" name="telegram_username" class="form-control settings-input"
                                   value="<?php echo htmlspecialchars(str_replace('https://t.me/','',$settings['telegram'] ?? 'isadagishop')); ?>"
                                   placeholder="isadagishop">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="settings-label"><i class="fab fa-facebook me-1" style="color:#1877F2;"></i>Facebook</label>
                        <div class="input-group">
                            <span class="input-group-text" style="font-size:12px;">facebook.com/</span>
                            <input type="text" name="facebook" class="form-control settings-input"
                                   value="<?php echo htmlspecialchars(str_replace('https://facebook.com/','',$settings['facebook'] ?? '')); ?>"
                                   placeholder="isadagishop">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="settings-label"><i class="fab fa-instagram me-1" style="color:#E1306C;"></i>Instagram</label>
                        <div class="input-group">
                            <span class="input-group-text" style="font-size:12px;">instagram.com/</span>
                            <input type="text" name="instagram" class="form-control settings-input"
                                   value="<?php echo htmlspecialchars(str_replace('https://instagram.com/','',$settings['instagram'] ?? '')); ?>"
                                   placeholder="isadagishop">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="settings-label"><i class="fab fa-youtube me-1" style="color:#FF0000;"></i>YouTube</label>
                        <div class="input-group">
                            <span class="input-group-text" style="font-size:12px;">youtube.com/@</span>
                            <input type="text" name="youtube" class="form-control settings-input"
                                   value="<?php echo htmlspecialchars(str_replace(['https://youtube.com/@','https://youtube.com/'],'',$settings['youtube'] ?? '')); ?>"
                                   placeholder="isadagishop">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="settings-label"><i class="fab fa-tiktok me-1"></i>TikTok</label>
                        <div class="input-group">
                            <span class="input-group-text" style="font-size:12px;">tiktok.com/@</span>
                            <input type="text" name="tiktok" class="form-control settings-input"
                                   value="<?php echo htmlspecialchars(str_replace('https://tiktok.com/@','',$settings['tiktok'] ?? '')); ?>"
                                   placeholder="isadagishop">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="settings-label"><i class="fab fa-x-twitter me-1"></i>X (Twitter)</label>
                        <div class="input-group">
                            <span class="input-group-text" style="font-size:12px;">x.com/</span>
                            <input type="text" name="twitter" class="form-control settings-input"
                                   value="<?php echo htmlspecialchars(str_replace(['https://x.com/','https://twitter.com/'],'',$settings['twitter'] ?? '')); ?>"
                                   placeholder="isadagishop">
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" name="save_social" class="btn btn-primary px-4">
                            <i class="fas fa-save me-2"></i>Save Social Links
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- 5. Delivery Settings -->
        <div class="settings-card">
            <h5><i class="fas fa-truck"></i>Delivery Settings</h5>
            <form method="post">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="settings-label">Delivery Fee (USD)</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" name="delivery_fee" class="form-control settings-input"
                                   value="<?php echo htmlspecialchars($settings['delivery_fee'] ?? '0'); ?>"
                                   step="0.01" min="0" placeholder="0 = free">
                        </div>
                        <small class="text-muted" style="font-size:11px;">Set 0 for free delivery</small>
                    </div>
                    <div class="col-md-4">
                        <label class="settings-label">Free Delivery Above (USD)</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" name="free_delivery_above" class="form-control settings-input"
                                   value="<?php echo htmlspecialchars($settings['free_delivery_above'] ?? '0'); ?>"
                                   step="0.01" min="0" placeholder="0 = always free">
                        </div>
                        <small class="text-muted" style="font-size:11px;">0 = always apply delivery fee</small>
                    </div>
                    <div class="col-md-4">
                        <label class="settings-label">Delivery Areas</label>
                        <input type="text" name="delivery_areas" class="form-control settings-input"
                               value="<?php echo htmlspecialchars($settings['delivery_areas'] ?? 'Addis Ababa'); ?>"
                               placeholder="e.g. Addis Ababa, Adama">
                    </div>
                    <div class="col-12">
                        <label class="settings-label">Delivery Note (shown at checkout)</label>
                        <input type="text" name="delivery_note" class="form-control settings-input"
                               value="<?php echo htmlspecialchars($settings['delivery_note'] ?? 'Same-day delivery within Addis Ababa for orders before 2 PM.'); ?>"
                               placeholder="Delivery note for customers">
                    </div>
                    <div class="col-12">
                        <button type="submit" name="save_delivery" class="btn btn-primary px-4">
                            <i class="fas fa-save me-2"></i>Save Delivery Settings
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- 6. Order Settings -->
        <div class="settings-card">
            <h5><i class="fas fa-shopping-bag"></i>Order Settings</h5>
            <form method="post">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="settings-label">Minimum Order Amount (USD)</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" name="min_order_amount" class="form-control settings-input"
                                   value="<?php echo htmlspecialchars($settings['min_order_amount'] ?? '0'); ?>"
                                   step="0.01" min="0" placeholder="0 = no minimum">
                        </div>
                    </div>
                    <div class="col-md-5">
                        <label class="settings-label">Order Notification Email</label>
                        <input type="email" name="order_notify_email" class="form-control settings-input"
                               value="<?php echo htmlspecialchars($settings['order_notify_email'] ?? ($settings['shop_email'] ?? '')); ?>"
                               placeholder="Receive new order alerts here">
                        <small class="text-muted" style="font-size:11px;">Leave blank to use shop email</small>
                    </div>
                    <div class="col-md-3">
                        <label class="settings-label">Auto-confirm Orders</label>
                        <select name="order_auto_confirm" class="form-select settings-input">
                            <option value="0" <?php echo ($settings['order_auto_confirm']??'0')==='0'?'selected':''; ?>>Manual (recommended)</option>
                            <option value="1" <?php echo ($settings['order_auto_confirm']??'0')==='1'?'selected':''; ?>>Auto-confirm</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" name="save_orders" class="btn btn-primary px-4">
                            <i class="fas fa-save me-2"></i>Save Order Settings
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- 7. SEO Settings -->
        <div class="settings-card">
            <h5><i class="fas fa-search"></i>SEO &amp; Meta Tags</h5>
            <form method="post">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="settings-label">Meta Description <small class="text-muted fw-normal">(shown in Google search results)</small></label>
                        <textarea name="meta_description" class="form-control settings-input" rows="2"
                                  maxlength="160" placeholder="Short description of your shop (max 160 characters)"><?php echo htmlspecialchars($settings['meta_description'] ?? 'Isa & Dagi Mobile Shop — Buy the latest smartphones and accessories in Addis Ababa, Ethiopia. Samsung, Apple, Redmi, Tecno and more.'); ?></textarea>
                        <small class="text-muted" style="font-size:11px;">Recommended: 120–160 characters</small>
                    </div>
                    <div class="col-12">
                        <label class="settings-label">Meta Keywords <small class="text-muted fw-normal">(comma separated)</small></label>
                        <input type="text" name="meta_keywords" class="form-control settings-input"
                               value="<?php echo htmlspecialchars($settings['meta_keywords'] ?? 'mobile shop, smartphones, Addis Ababa, Samsung, Apple, Redmi, Ethiopia'); ?>"
                               placeholder="mobile shop, smartphones, Addis Ababa, Samsung, Apple">
                    </div>
                    <div class="col-12">
                        <button type="submit" name="save_seo" class="btn btn-primary px-4">
                            <i class="fas fa-save me-2"></i>Save SEO Settings
                        </button>
                    </div>
                </div>
            </form>
        </div>

    </div>

    <div class="col-lg-4">
        <div class="settings-card">
            <h5><i class="fas fa-key"></i>Change Password</h5>
            <form method="post">
                <div class="mb-3">
                    <label class="settings-label">Current Password</label>
                    <input type="password" name="current_password" class="form-control settings-input" required>
                </div>
                <div class="mb-3">
                    <label class="settings-label">New Password</label>
                    <input type="password" name="new_password" class="form-control settings-input"
                           placeholder="Min 6 characters" required>
                </div>
                <div class="mb-3">
                    <label class="settings-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control settings-input" required>
                </div>
                <button type="submit" name="change_admin_password" class="btn btn-warning w-100 fw-bold">
                    <i class="fas fa-lock me-2"></i>Update Password
                </button>
            </form>
        </div>

        <!-- 5. Quick Info -->
        <div class="settings-card">
            <h5><i class="fas fa-info-circle"></i>System Info</h5>
            <div style="font-size:13px;">
                <?php
                $nl_count = count($subscribers);
                ?>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Newsletter Subscribers</span>
                    <strong class="text-success"><?php echo $nl_count; ?></strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">PHP Version</span>
                    <strong><?php echo phpversion(); ?></strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">MySQL</span>
                    <strong><?php echo $db->con->server_info; ?></strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Database</span>
                    <strong>shopee</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Logged in as</span>
                    <strong><?php echo htmlspecialchars($_SESSION['first_name']); ?></strong>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-muted">Server Time</span>
                    <strong><?php echo date('M d, Y H:i'); ?></strong>
                </div>
            </div>

            <!-- Database Export -->
            <div class="mt-4 p-3 rounded-3" style="background:#f0f9ff;border:1px solid #bee3f8;">
                <h6 class="fw-bold mb-1" style="font-size:13px;color:#003859;">
                    <i class="fas fa-database me-2"></i>Database Backup
                </h6>
                <p class="text-muted mb-3" style="font-size:12px;">
                    Export your full database including all products, orders, users, and settings.
                    Use this to move your project to another computer.
                </p>
                <a href="settings.php?export_db=1" class="btn btn-primary w-100">
                    <i class="fas fa-download me-2"></i>Download Full Database Backup (.sql)
                </a>
                <p class="text-muted mt-2 mb-0" style="font-size:11px;">
                    <i class="fas fa-info-circle me-1"></i>
                    On the new computer: open phpMyAdmin → create database "shopee" → Import → choose this file → Go
                </p>
            </div>
        </div>

        <!-- Maintenance Mode -->
        <div class="settings-card">
            <h5><i class="fas fa-tools"></i>Maintenance Mode</h5>
            <form method="post">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="maintenance_mode" id="maintenanceToggle"
                           <?php echo ($settings['maintenance_mode'] ?? '0') === '1' ? 'checked' : ''; ?>
                           style="width:44px;height:22px;cursor:pointer;">
                    <label class="form-check-label ms-2 fw-bold" for="maintenanceToggle" style="font-size:13px;">
                        <?php echo ($settings['maintenance_mode']??'0')==='1'
                            ? '<span style="color:#dc3545;">🔴 Shop is OFFLINE</span>'
                            : '<span style="color:#28a745;">🟢 Shop is ONLINE</span>'; ?>
                    </label>
                </div>
                <div class="mb-3">
                    <label class="settings-label">Maintenance Message</label>
                    <textarea name="maintenance_message" class="form-control settings-input" rows="2"
                              placeholder="We'll be back shortly..."><?php echo htmlspecialchars($settings['maintenance_message'] ?? "We are currently performing maintenance. We'll be back shortly!"); ?></textarea>
                </div>
                <button type="submit" name="save_maintenance" class="btn btn-warning w-100 fw-bold">
                    <i class="fas fa-save me-2"></i>Save Maintenance Settings
                </button>
            </form>
            <p class="text-muted mt-2 mb-0" style="font-size:11px;">
                <i class="fas fa-info-circle me-1"></i>When ON, customers see the maintenance message. Admins can still browse the shop.
            </p>
        </div>

    </div>
    </div>

    <!-- ===== NEWSLETTER SUBSCRIBERS ===== -->
    <div class="settings-card mt-2" id="newsletter">
        <h5>
            <i class="fas fa-envelope-open-text"></i>Newsletter Subscribers
            <span class="badge bg-success ms-2" style="font-size:12px;"><?php echo count($subscribers); ?> total</span>
        </h5>
        <?php if (empty($subscribers)): ?>
        <p class="text-muted" style="font-size:13px;"><i class="fas fa-inbox me-1"></i>No subscribers yet.</p>
        <?php else: ?>
        <div class="mb-2 d-flex justify-content-between align-items-center">
            <span class="text-muted" style="font-size:12px;">Manage your newsletter subscribers below.</span>
            <a href="?export_newsletter=1" class="btn btn-sm btn-outline-success">
                <i class="fas fa-download me-1"></i>Export CSV
            </a>
        </div>
        <div style="overflow-x:auto;max-height:300px;overflow-y:auto;">
        <table class="table table-sm mb-0">
            <thead style="background:#f8f9fa;position:sticky;top:0;">
                <tr>
                    <th style="font-size:12px;">#</th>
                    <th style="font-size:12px;">Email</th>
                    <th style="font-size:12px;">Subscribed</th>
                    <th style="font-size:12px;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subscribers as $i => $sub): ?>
                <tr>
                    <td style="font-size:12px;color:#888;"><?php echo $i+1; ?></td>
                    <td style="font-size:13px;"><?php echo htmlspecialchars($sub['email']); ?></td>
                    <td style="font-size:12px;color:#888;"><?php echo date('M d, Y', strtotime($sub['subscribed_at'])); ?></td>
                    <td>
                        <form method="post" class="d-inline" onsubmit="return confirm('Remove this subscriber?')">
                            <input type="hidden" name="sub_id" value="<?php echo $sub['id']; ?>">
                            <button type="submit" name="delete_subscriber" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- ===== CONTACT MESSAGES ===== -->
    <div class="settings-card mt-2" id="messages">
        <h5>
            <i class="fas fa-envelope"></i>Contact Messages
            <?php if ($unread_count > 0): ?>
            <span class="badge bg-danger ms-2" style="font-size:12px;"><?php echo $unread_count; ?> new</span>
            <?php endif; ?>
        </h5>
        <?php if ($unread_count > 0): ?>
        <div class="mb-3">
            <a href="settings.php?read_all=1#messages" class="btn btn-sm btn-outline-success">
                <i class="fas fa-check-double me-1"></i>Mark All as Read
            </a>
        </div>
        <?php endif; ?>

        <?php if (empty($messages)): ?>
        <p class="text-muted" style="font-size:13px;"><i class="fas fa-inbox me-1"></i>No messages yet.</p>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table class="table table-sm mb-0">
            <thead style="background:#f8f9fa;">
                <tr>
                    <th style="font-size:12px;">Name</th>
                    <th style="font-size:12px;">Email</th>
                    <th style="font-size:12px;">Subject</th>
                    <th style="font-size:12px;">Message</th>
                    <th style="font-size:12px;">Date</th>
                    <th style="font-size:12px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($messages as $msg): ?>
                <tr style="<?php echo !$msg['is_read'] ? 'background:#fffbf0;' : ''; ?>">
                    <td style="font-size:13px;font-weight:<?php echo !$msg['is_read']?'700':'400'; ?>;">
                        <?php if (!$msg['is_read']): ?>
                        <span class="badge bg-warning text-dark me-1" style="font-size:9px;">NEW</span>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($msg['name']); ?>
                    </td>
                    <td style="font-size:12px;">
                        <a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>" style="color:#00A5C4;">
                            <?php echo htmlspecialchars($msg['email']); ?>
                        </a>
                    </td>
                    <td style="font-size:12px;">
                        <span class="badge bg-secondary"><?php echo htmlspecialchars($msg['subject']); ?></span>
                    </td>
                    <td style="font-size:12px;max-width:260px;">
                        <span title="<?php echo htmlspecialchars($msg['message']); ?>">
                            <?php echo htmlspecialchars(mb_substr($msg['message'], 0, 60)) . (mb_strlen($msg['message']) > 60 ? '...' : ''); ?>
                        </span>
                    </td>
                    <td style="font-size:11px;color:#888;white-space:nowrap;">
                        <?php echo date('M d, Y H:i', strtotime($msg['created_at'])); ?>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <?php if (!$msg['is_read']): ?>
                            <a href="settings.php?read_msg=<?php echo $msg['msg_id']; ?>#messages"
                               class="btn btn-sm btn-outline-success" title="Mark as read">
                                <i class="fas fa-check"></i>
                            </a>
                            <?php endif; ?>
                            <a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>?subject=Re: <?php echo urlencode($msg['subject']); ?>"
                               class="btn btn-sm btn-outline-primary" title="Reply by email">
                                <i class="fas fa-reply"></i>
                            </a>
                            <form method="post" class="d-inline" onsubmit="return confirm('Delete this message?')">
                                <input type="hidden" name="msg_id" value="<?php echo $msg['msg_id']; ?>">
                                <button type="submit" name="delete_msg" class="btn btn-sm btn-outline-danger" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

</div><!-- end admin-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.modal-backdrop').forEach(function(el){ el.remove(); });
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
});

function previewLogo(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('logoPreview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
</body>
</html>

