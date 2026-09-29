<?php
require_once '../functions.php';
if (!$auth->isAdmin()) { header('Location: login.php'); exit; }

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Save main carousel banners
    if (isset($_POST['save_banners'])) {
        $upload_dir = '../assets/';
        for ($i = 1; $i <= 6; $i++) {
            $key = 'banner_' . $i;
            if (isset($_POST['delete_banner_' . $i])) {
                $db->con->query("INSERT INTO settings (setting_key,setting_value) VALUES ('{$key}','') ON DUPLICATE KEY UPDATE setting_value=''");
                continue;
            }
            if (!empty($_FILES['banner_file_' . $i]['name'])) {
                $allowed = ['jpg','jpeg','png','webp','gif'];
                $ext = strtolower(pathinfo($_FILES['banner_file_' . $i]['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $allowed)) {
                    $fname = 'banner_' . $i . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES['banner_file_' . $i]['tmp_name'], $upload_dir . $fname)) {
                        $val = mysqli_real_escape_string($db->con, 'assets/' . $fname);
                        $db->con->query("INSERT INTO settings (setting_key,setting_value) VALUES ('{$key}','{$val}') ON DUPLICATE KEY UPDATE setting_value='{$val}'");
                    }
                }
            }
        }
        $success = 'Banners saved!';
    }

    // Save ad banners
    if (isset($_POST['save_ad_banners'])) {
        $upload_dir = '../assets/';
        foreach (['ad_banner_1', 'ad_banner_2'] as $key) {
            $file_key = 'ad_file_' . substr($key, -1);
            if (isset($_POST['delete_' . $key])) {
                $db->con->query("INSERT INTO settings (setting_key,setting_value) VALUES ('{$key}','') ON DUPLICATE KEY UPDATE setting_value=''");
                continue;
            }
            if (!empty($_FILES[$file_key]['name'])) {
                $allowed = ['jpg','jpeg','png','webp','gif'];
                $ext = strtolower(pathinfo($_FILES[$file_key]['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $allowed)) {
                    $fname = $key . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $upload_dir . $fname)) {
                        $val = mysqli_real_escape_string($db->con, 'assets/' . $fname);
                        $db->con->query("INSERT INTO settings (setting_key,setting_value) VALUES ('{$key}','{$val}') ON DUPLICATE KEY UPDATE setting_value='{$val}'");
                    }
                }
            }
        }
        $success = 'Ad banners saved!';
    }

}

// Load all settings
$settings = [];
$res = $db->con->query("SELECT * FROM settings");
if ($res) while ($row = $res->fetch_assoc()) $settings[$row['setting_key']] = $row['setting_value'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banners — Isa &amp; Dagi Admin</title>
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
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="admin-sidebar">
    <div class="admin-logo">
        <?php
        $logo_path = getSetting('shop_logo');
        if ($logo_path && file_exists('../' . ltrim($logo_path, './'))) {
            echo '<img src="../' . htmlspecialchars(ltrim($logo_path, './')) . '" alt="Shop Logo">';
        } else {
        ?>
        <span class="logo-fallback"><i class="fas fa-mobile-alt"></i>Isa &amp; Dagi Admin</span>
        <?php } ?>
    </div>
    <nav class="nav flex-column mt-2">
        <a class="nav-link" href="index.php"><i class="fas fa-tachometer-alt"></i>Dashboard</a>
        <a class="nav-link" href="products.php"><i class="fas fa-box"></i>Products</a>
        <a class="nav-link" href="accessories.php"><i class="fas fa-headphones"></i>Accessories</a>
        <a class="nav-link" href="blog.php"><i class="fas fa-newspaper"></i>Blog</a>
        <a class="nav-link active" href="banners.php"><i class="fas fa-images"></i>Banners</a>
        <a class="nav-link" href="orders.php"><i class="fas fa-shopping-bag"></i>Orders</a>
        <a class="nav-link" href="users.php"><i class="fas fa-users"></i>Users</a>
        <hr style="border-color:rgba(255,255,255,0.1);margin:10px 20px;">
        <a class="nav-link" href="settings.php"><i class="fas fa-cog"></i>Settings</a>
        <a class="nav-link" href="../index.php" target="_blank"><i class="fas fa-store"></i>View Shop</a>
        <a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
    </nav>
</div>

<!-- Main Content -->
<div class="admin-content">

    <!-- Top bar -->
    <div class="admin-topbar">
        <h5 class="mb-0" style="font-family:'Rubik',sans-serif; font-weight:600;">
            <i class="fas fa-images me-2 text-primary"></i>Manage Banners
        </h5>
        <a href="../index.php" target="_blank" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-eye me-1"></i>Preview Homepage
        </a>
    </div>

    <!-- Stats -->
    <?php
    $active_carousel = 0;
    $active_ads = 0;
    for ($i = 1; $i <= 6; $i++) {
        $v = $settings['banner_' . $i] ?? null;
        if ($v !== null && $v !== '') $active_carousel++;
        elseif ($v === null) {
            // default counts as active
            $defaults_check = [1=>'assets/Banner1.png',2=>'assets/my_banner.png',3=>'assets/my_banner2.png',4=>'assets/my_banner3.png'];
            if (isset($defaults_check[$i]) && file_exists('../' . $defaults_check[$i])) $active_carousel++;
        }
    }
    foreach (['ad_banner_1','ad_banner_2'] as $ak) {
        $v = $settings[$ak] ?? null;
        if ($v !== null && $v !== '') $active_ads++;
        elseif ($v === null) $active_ads++;
    }
    ?>
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-label">Carousel Banners</div>
                <div class="stat-value"><?php echo $active_carousel; ?> / 6</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card" style="border-left-color:#ffc107;">
                <div class="stat-label">Ad Banners</div>
                <div class="stat-value"><?php echo $active_ads; ?> / 2</div>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" role="alert" style="border-radius:10px;">
        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2" role="alert" style="border-radius:10px;">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Carousel Banners -->
    <div class="settings-card">
        <h5><i class="fas fa-images"></i>Homepage Carousel Banners</h5>
        <p class="text-muted mb-3" style="font-size:12px;">
            <i class="fas fa-info-circle me-1"></i>
            These are the large sliding banners at the top of the homepage. Upload up to 5 banners.
        </p>
        <form method="post" enctype="multipart/form-data">
            <div class="row g-3">
            <?php
            $default_banners = [
                1 => 'assets/Banner1.png',
                2 => 'assets/my_banner.png',
                3 => 'assets/my_banner2.png',
                4 => 'assets/my_banner3.png',
            ];
            for ($i = 1; $i <= 6; $i++):
                $bkey = 'banner_' . $i;
                if (array_key_exists($bkey, $settings)) {
                    $bval = $settings[$bkey];
                } else {
                    $bval = $default_banners[$i] ?? '';
                }
                $is_removed = array_key_exists($bkey, $settings) && $settings[$bkey] === '';
                $bsrc = (!$is_removed && $bval && file_exists('../' . ltrim($bval, './')))
                    ? '../' . ltrim($bval, './')
                    : '';
            ?>
            <div class="col-md-6">
                <div style="border:1.5px solid #e0e0e0;border-radius:10px;padding:12px;background:#fafafa;">
                    <div style="font-size:12px;font-weight:700;color:#003859;margin-bottom:8px;">
                        <i class="fas fa-image me-1 text-info"></i>Banner <?php echo $i; ?>
                        <?php if ($bsrc): ?>
                        <span class="badge bg-success ms-1" style="font-size:9px;">Active</span>
                        <?php elseif ($is_removed): ?>
                        <span class="badge bg-danger ms-1" style="font-size:9px;">Removed</span>
                        <?php else: ?>
                        <span class="badge bg-secondary ms-1" style="font-size:9px;">Empty</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($bsrc): ?>
                    <img src="<?php echo htmlspecialchars($bsrc); ?>"
                         style="width:100%;height:80px;object-fit:cover;border-radius:6px;margin-bottom:8px;">
                    <?php else: ?>
                    <div style="width:100%;height:80px;background:#e9ecef;border-radius:6px;margin-bottom:8px;display:flex;align-items:center;justify-content:center;">
                        <span style="color:#aaa;font-size:12px;"><i class="fas fa-image me-1"></i>No image</span>
                    </div>
                    <?php endif; ?>
                    <input type="file" name="banner_file_<?php echo $i; ?>" class="form-control form-control-sm mb-2" accept="image/*">
                    <?php if ($bsrc): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="delete_banner_<?php echo $i; ?>" id="del_b<?php echo $i; ?>">
                        <label class="form-check-label text-danger" for="del_b<?php echo $i; ?>" style="font-size:11px;">Remove this banner</label>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endfor; ?>
            </div>
            <div class="mt-3">
                <button type="submit" name="save_banners" class="btn btn-primary px-4">
                    <i class="fas fa-save me-2"></i>Save Carousel Banners
                </button>
            </div>
        </form>
    </div>

    <!-- Ad Banners -->
    <div class="settings-card">
        <h5><i class="fas fa-ad"></i>Ad Banners (Mid-page)</h5>
        <p class="text-muted mb-3" style="font-size:12px;">
            <i class="fas fa-info-circle me-1"></i>
            Two small promotional banners shown between product sections. Recommended size: 500×150px.
        </p>
        <form method="post" enctype="multipart/form-data">
            <div class="row g-3">
            <?php
            $ad_defaults = [1 => 'assets/banner1-cr-500x150.jpg', 2 => 'assets/banner2-cr-500x150.jpg'];
            foreach ([1, 2] as $ai):
                $akey = 'ad_banner_' . $ai;
                if (array_key_exists($akey, $settings)) {
                    $aval = $settings[$akey];
                } else {
                    $aval = $ad_defaults[$ai] ?? '';
                }
                $ad_removed = array_key_exists($akey, $settings) && $settings[$akey] === '';
                $asrc = (!$ad_removed && $aval && file_exists('../' . ltrim($aval, './')))
                    ? '../' . ltrim($aval, './')
                    : '';
            ?>
            <div class="col-md-6">
                <div style="border:1.5px solid #e0e0e0;border-radius:10px;padding:12px;background:#fafafa;">
                    <div style="font-size:12px;font-weight:700;color:#003859;margin-bottom:8px;">
                        <i class="fas fa-rectangle-ad me-1 text-warning"></i>Ad Banner <?php echo $ai; ?>
                        <?php if ($asrc): ?>
                        <span class="badge bg-success ms-1" style="font-size:9px;">Active</span>
                        <?php elseif ($ad_removed): ?>
                        <span class="badge bg-danger ms-1" style="font-size:9px;">Removed</span>
                        <?php else: ?>
                        <span class="badge bg-secondary ms-1" style="font-size:9px;">Empty</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($asrc): ?>
                    <img src="<?php echo htmlspecialchars($asrc); ?>"
                         style="width:100%;height:60px;object-fit:cover;border-radius:6px;margin-bottom:8px;">
                    <?php else: ?>
                    <div style="width:100%;height:60px;background:#e9ecef;border-radius:6px;margin-bottom:8px;display:flex;align-items:center;justify-content:center;">
                        <span style="color:#aaa;font-size:12px;"><i class="fas fa-image me-1"></i>No image</span>
                    </div>
                    <?php endif; ?>
                    <input type="file" name="ad_file_<?php echo $ai; ?>" class="form-control form-control-sm mb-2" accept="image/*">
                    <?php if ($asrc): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="delete_<?php echo $akey; ?>" id="del_a<?php echo $ai; ?>">
                        <label class="form-check-label text-danger" for="del_a<?php echo $ai; ?>" style="font-size:11px;">Remove this banner</label>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
            <div class="mt-3">
                <button type="submit" name="save_ad_banners" class="btn btn-primary px-4">
                    <i class="fas fa-save me-2"></i>Save Ad Banners
                </button>
            </div>
        </form>
    </div>

</div><!-- /admin-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.modal-backdrop').forEach(function(el){ el.remove(); });
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';

    // Live preview when selecting a new banner image
    document.querySelectorAll('input[type="file"][accept="image/*"]').forEach(function(input) {
        input.addEventListener('change', function() {
            if (!this.files || !this.files[0]) return;
            var reader = new FileReader();
            var previewBox = this.closest('div[style]').querySelector('img, div[style*="background:#e9ecef"]');
            var container = this.closest('div[style]');
            reader.onload = function(e) {
                // Remove placeholder div if present
                var placeholder = container.querySelector('div[style*="background:#e9ecef"]');
                if (placeholder) placeholder.remove();
                // Update or create preview img
                var img = container.querySelector('img:not(.form-control)');
                if (!img) {
                    img = document.createElement('img');
                    img.style.cssText = 'width:100%;object-fit:cover;border-radius:6px;margin-bottom:8px;';
                    container.insertBefore(img, input);
                }
                img.src = e.target.result;
                img.style.height = input.name.startsWith('ad_') ? '60px' : '80px';
            };
            reader.readAsDataURL(this.files[0]);
        });
    });
});
</script>
</body>
</html>
