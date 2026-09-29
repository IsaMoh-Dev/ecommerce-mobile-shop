<?php
require_once '../functions.php';
if (!$auth->canAccessAdmin()) { header('Location: login.php'); exit; }

$success = '';
$error   = '';

// ---- helpers ----
function specVal($key) {
    return isset($_POST[$key]) ? mysqli_real_escape_string($GLOBALS['db']->con, trim($_POST[$key])) : '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ADD PRODUCT
    if (isset($_POST['add_product'])) {
        $upload_dir = '../assets/products/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $allowed = ['jpg','jpeg','png','webp','gif'];

        // Helper to upload a single image file
        $uploadImg = function($file_key, $default) use ($upload_dir, $allowed) {
            if (!empty($_FILES[$file_key]['name'])) {
                $ext = strtolower(pathinfo($_FILES[$file_key]['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $allowed)) {
                    $fname = 'product_' . time() . '_' . $file_key . '.' . $ext;
                    if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $upload_dir . $fname))
                        return './assets/products/' . $fname;
                }
            }
            return $default;
        };

        $image_path  = $uploadImg('item_image',  './assets/products/1.png');
        $image_path2 = $uploadImg('item_image2', '');
        $image_path3 = $uploadImg('item_image3', '');

        $brand     = mysqli_real_escape_string($db->con, trim($_POST['item_brand']));
        $name      = mysqli_real_escape_string($db->con, trim($_POST['item_name']));
        $price     = (float)$_POST['item_price'];
        $old_price = !empty($_POST['item_old_price']) ? (float)$_POST['item_old_price'] : 'NULL';
        $desc      = mysqli_real_escape_string($db->con, trim($_POST['item_description']));
        $stock     = (int)$_POST['item_stock'];
        $featured  = isset($_POST['item_featured']) ? 1 : 0;
        $onsale    = isset($_POST['item_onsale'])   ? 1 : 0;
        $img       = mysqli_real_escape_string($db->con, $image_path);
        $img2      = mysqli_real_escape_string($db->con, $image_path2);
        $img3      = mysqli_real_escape_string($db->con, $image_path3);
        $old_val   = is_numeric($old_price) ? $old_price : 'NULL';
        $ram       = specVal('item_ram');
        $storage   = specVal('item_storage');
        $display   = specVal('item_display');
        $camera    = specVal('item_camera');
        $battery   = specVal('item_battery');
        $processor = specVal('item_processor');
        $os        = specVal('item_os');

        $sql = "INSERT INTO product
                    (item_brand,item_name,item_price,item_old_price,item_image,item_image2,item_image3,item_description,
                     item_ram,item_storage,item_display,item_camera,item_battery,item_processor,item_os,
                     item_stock,item_featured,item_onsale)
                VALUES
                    ('{$brand}','{$name}',{$price},{$old_val},'{$img}','{$img2}','{$img3}','{$desc}',
                     '{$ram}','{$storage}','{$display}','{$camera}','{$battery}','{$processor}','{$os}',
                     {$stock},{$featured},{$onsale})";
        if ($db->con->query($sql)) {
            $db->con->query("INSERT IGNORE INTO brand (brand_name) VALUES ('{$brand}')");
            $success = 'Product added successfully!';
        } else {
            $error = 'Failed: ' . $db->con->error;
        }
    }

    // DELETE PRODUCT
    if (isset($_POST['delete_product'])) {
        $id = (int)$_POST['item_id'];
        $db->con->query("DELETE FROM product WHERE item_id={$id}");
        $success = 'Product deleted.';
    }

    // UPDATE PRODUCT
    if (isset($_POST['update_product'])) {
        $id        = (int)$_POST['item_id'];
        $brand     = mysqli_real_escape_string($db->con, trim($_POST['item_brand']));
        $name      = mysqli_real_escape_string($db->con, trim($_POST['item_name']));
        $price     = (float)$_POST['item_price'];
        $old_price = !empty($_POST['item_old_price']) ? (float)$_POST['item_old_price'] : 'NULL';
        $desc      = mysqli_real_escape_string($db->con, trim($_POST['item_description']));
        $stock     = (int)$_POST['item_stock'];
        $featured  = isset($_POST['item_featured']) ? 1 : 0;
        $onsale    = isset($_POST['item_onsale'])   ? 1 : 0;
        $img       = mysqli_real_escape_string($db->con, $_POST['item_image_current']);
        $img2      = mysqli_real_escape_string($db->con, $_POST['item_image2_current'] ?? '');
        $img3      = mysqli_real_escape_string($db->con, $_POST['item_image3_current'] ?? '');
        $old_val   = is_numeric($old_price) ? $old_price : 'NULL';
        $ram       = specVal('item_ram');
        $storage   = specVal('item_storage');
        $display   = specVal('item_display');
        $camera    = specVal('item_camera');
        $battery   = specVal('item_battery');
        $processor = specVal('item_processor');
        $os        = specVal('item_os');
        $allowed   = ['jpg','jpeg','png','webp','gif'];

        // Main image
        if (!empty($_FILES['item_image_edit']['name'])) {
            $ext = strtolower(pathinfo($_FILES['item_image_edit']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $filename = 'product_' . time() . '_1.' . $ext;
                if (move_uploaded_file($_FILES['item_image_edit']['tmp_name'], '../assets/products/' . $filename))
                    $img = mysqli_real_escape_string($db->con, './assets/products/' . $filename);
            }
        }
        // Image 2
        if (!empty($_FILES['item_image2_edit']['name'])) {
            $ext = strtolower(pathinfo($_FILES['item_image2_edit']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $filename = 'product_' . time() . '_2.' . $ext;
                if (move_uploaded_file($_FILES['item_image2_edit']['tmp_name'], '../assets/products/' . $filename))
                    $img2 = mysqli_real_escape_string($db->con, './assets/products/' . $filename);
            }
        }
        // Image 3
        if (!empty($_FILES['item_image3_edit']['name'])) {
            $ext = strtolower(pathinfo($_FILES['item_image3_edit']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $filename = 'product_' . time() . '_3.' . $ext;
                if (move_uploaded_file($_FILES['item_image3_edit']['tmp_name'], '../assets/products/' . $filename))
                    $img3 = mysqli_real_escape_string($db->con, './assets/products/' . $filename);
            }
        }
        // Clear image 2/3 if checkbox checked
        if (isset($_POST['clear_image2'])) $img2 = '';
        if (isset($_POST['clear_image3'])) $img3 = '';

        $sql = "UPDATE product SET
                    item_brand='{$brand}',item_name='{$name}',item_price={$price},
                    item_old_price={$old_val},item_image='{$img}',item_image2='{$img2}',item_image3='{$img3}',
                    item_description='{$desc}',
                    item_ram='{$ram}',item_storage='{$storage}',item_display='{$display}',
                    item_camera='{$camera}',item_battery='{$battery}',item_processor='{$processor}',
                    item_os='{$os}',item_stock={$stock},item_featured={$featured},item_onsale={$onsale}
                WHERE item_id={$id}";
        if ($db->con->query($sql)) {
            $db->con->query("INSERT IGNORE INTO brand (brand_name) VALUES ('{$brand}')");
            $success = 'Product updated!';
        } else {
            $error = 'Update failed: ' . $db->con->error;
        }
    }

    // ADD BRAND
    if (isset($_POST['add_brand'])) {
        $bname = mysqli_real_escape_string($db->con, trim($_POST['brand_name']));
        if ($bname) {
            if ($db->con->query("INSERT IGNORE INTO brand (brand_name) VALUES ('{$bname}')"))
                $success = "Brand '{$bname}' added!";
            else $error = 'Brand add failed.';
        }
    }

    // DELETE BRAND
    if (isset($_POST['delete_brand'])) {
        $bid = (int)$_POST['brand_id'];
        $db->con->query("DELETE FROM brand WHERE brand_id={$bid}");
        $success = 'Brand deleted.';
    }

    // SAVE SETTINGS
    if (isset($_POST['save_settings'])) {
        $rate = (float)$_POST['usd_to_birr'];
        $db->con->query("UPDATE settings SET setting_value='{$rate}' WHERE setting_key='usd_to_birr'");
        $success = 'Settings saved!';
    }
}

$all_products = $product->getData();
$all_brands   = [];
$br = $db->con->query("SELECT * FROM brand ORDER BY brand_name");
while ($row = $br->fetch_assoc()) $all_brands[] = $row;
$current_rate = getSetting('usd_to_birr');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products — Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        .spec-section-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #00A5C4;
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 6px;
            margin-bottom: 12px;
            margin-top: 4px;
        }
        .spec-icon { color: #00A5C4; width: 18px; }
    </style>
</head>
<body>

<?php $active_page = 'products'; include '_sidebar.php'; ?>

<div class="admin-content">
    <div class="admin-topbar">
        <h5 class="mb-0" style="font-family:'Rubik',sans-serif;font-weight:600;">
            <i class="fas fa-box me-2"></i>Manage Products
        </h5>
        <div class="d-flex gap-2">
            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#brandsModal">
                <i class="fas fa-tags me-1"></i>Manage Brands
            </button>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="fas fa-plus me-1"></i>Add New Product
            </button>
        </div>
    </div>

    <?php if ($success): ?><div class="alert alert-success alert-dismissible"><button type="button" class="btn-close" data-bs-dismiss="alert"></button><?php echo $success; ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-danger  alert-dismissible"><button type="button" class="btn-close" data-bs-dismiss="alert"></button><?php echo $error; ?></div><?php endif; ?>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="stat-card"><div class="stat-label">Total Products</div><div class="stat-value"><?php echo count($all_products); ?></div></div></div>
        <div class="col-md-3"><div class="stat-card" style="border-left-color:#dc3545;"><div class="stat-label">On Sale</div><div class="stat-value"><?php echo count(array_filter($all_products,fn($p)=>$p['item_onsale'])); ?></div></div></div>
        <div class="col-md-3"><div class="stat-card" style="border-left-color:#ffc107;"><div class="stat-label">Featured</div><div class="stat-value"><?php echo count(array_filter($all_products,fn($p)=>$p['item_featured'])); ?></div></div></div>
        <div class="col-md-3"><div class="stat-card" style="border-left-color:#28a745;"><div class="stat-label">Brands</div><div class="stat-value"><?php echo count($all_brands); ?></div></div></div>
    </div>

    <!-- Products Table -->
    <div class="admin-table">
        <div class="p-3 border-bottom">
            <input type="text" id="productSearch" class="form-control form-control-sm" placeholder="Search products..." style="max-width:300px;">
        </div>
        <div style="overflow-x:auto;">
        <table class="table mb-0" id="productsTable">
            <thead>
                <tr>
                    <th>Image</th><th>Name</th><th>Brand</th><th>Price</th>
                    <th>RAM</th><th>Storage</th><th>Stock</th><th>Sale</th><th>Featured</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_products as $p_item): ?>
                <tr>
                    <td><img src="../<?php echo ltrim(htmlspecialchars($p_item['item_image']),'./'); ?>" style="width:48px;height:48px;object-fit:contain;background:#f8f9fa;border-radius:8px;padding:3px;"></td>
                    <td style="font-weight:600;font-size:13px;max-width:160px;"><?php echo htmlspecialchars($p_item['item_name']); ?></td>
                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($p_item['item_brand']); ?></span></td>
                    <td style="font-weight:700;color:#dc3545;">$<?php echo number_format($p_item['item_price'],2); ?></td>
                    <td style="font-size:12px;"><?php echo $p_item['item_ram'] ? htmlspecialchars($p_item['item_ram']) : '<span class="text-muted">—</span>'; ?></td>
                    <td style="font-size:12px;"><?php echo $p_item['item_storage'] ? htmlspecialchars($p_item['item_storage']) : '<span class="text-muted">—</span>'; ?></td>
                    <td><span class="badge <?php echo $p_item['item_stock']>5?'bg-success':'bg-danger'; ?>"><?php echo $p_item['item_stock']; ?></span></td>
                    <td><?php echo $p_item['item_onsale']   ? '<span class="badge bg-danger">Yes</span>'           : '<span class="badge bg-light text-dark">No</span>'; ?></td>
                    <td><?php echo $p_item['item_featured'] ? '<span class="badge bg-warning text-dark">Yes</span>' : '<span class="badge bg-light text-dark">No</span>'; ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editModal"
                                onclick="fillEdit(<?php echo htmlspecialchars(json_encode($p_item)); ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form method="post" class="d-inline" onsubmit="return confirm('Delete this product?')">
                            <input type="hidden" name="item_id" value="<?php echo $p_item['item_id']; ?>">
                            <button type="submit" name="delete_product" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Currency Settings -->
    <div class="mt-4 bg-white rounded-3 p-4 shadow-sm">
        <h6 style="font-family:'Rubik',sans-serif;font-weight:600;"><i class="fas fa-exchange-alt me-2"></i>Currency Settings</h6>
        <form method="post" class="d-flex align-items-end gap-3 mt-3">
            <div>
                <label class="form-label fw-bold" style="font-size:13px;">USD → ETB Exchange Rate</label>
                <div class="input-group" style="max-width:220px;">
                    <span class="input-group-text">1 USD =</span>
                    <input type="number" name="usd_to_birr" class="form-control" value="<?php echo $current_rate; ?>" step="0.01" required>
                    <span class="input-group-text">ETB</span>
                </div>
            </div>
            <button type="submit" name="save_settings" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Rate</button>
        </form>
    </div>
</div>

<!-- ===== ADD PRODUCT MODAL ===== -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header" style="background:#003859;color:#fff;">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add New Product</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row g-3">

                        <!-- Basic Info -->
                        <div class="col-12"><div class="spec-section-title"><i class="fas fa-info-circle spec-icon me-1"></i>Basic Information</div></div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Brand *</label>
                            <select name="item_brand" class="form-select" required>
                                <option value="">-- Select Brand --</option>
                                <?php foreach ($all_brands as $b): ?>
                                <option value="<?php echo htmlspecialchars($b['brand_name']); ?>"><?php echo htmlspecialchars($b['brand_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Product Name *</label>
                            <input type="text" name="item_name" class="form-control" placeholder="e.g. Samsung Galaxy S24 Ultra" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Price (USD) *</label>
                            <div class="input-group"><span class="input-group-text">$</span>
                            <input type="number" name="item_price" class="form-control" step="0.01" min="0" required></div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Old Price (USD)</label>
                            <div class="input-group"><span class="input-group-text">$</span>
                            <input type="number" name="item_old_price" class="form-control" step="0.01" min="0"></div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Stock *</label>
                            <input type="number" name="item_stock" class="form-control" value="10" min="0" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Product Image</label>
                            <input type="file" name="item_image" class="form-control" accept="image/*">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Image 2 <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="file" name="item_image2" class="form-control" accept="image/*">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Image 3 <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="file" name="item_image3" class="form-control" accept="image/*">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Description</label>
                            <textarea name="item_description" class="form-control" rows="2" placeholder="Short product description..."></textarea>
                        </div>

                        <!-- Specs -->
                        <div class="col-12 mt-2"><div class="spec-section-title"><i class="fas fa-microchip spec-icon me-1"></i>Technical Specifications</div></div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold"><i class="fas fa-memory spec-icon me-1"></i>RAM</label>
                            <input type="text" name="item_ram" class="form-control" placeholder="e.g. 8GB, 12GB">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold"><i class="fas fa-hdd spec-icon me-1"></i>Storage</label>
                            <input type="text" name="item_storage" class="form-control" placeholder="e.g. 128GB, 256GB">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold"><i class="fas fa-mobile-alt spec-icon me-1"></i>Display</label>
                            <input type="text" name="item_display" class="form-control" placeholder='e.g. 6.7" AMOLED 120Hz'>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold"><i class="fas fa-camera spec-icon me-1"></i>Camera</label>
                            <input type="text" name="item_camera" class="form-control" placeholder="e.g. 108MP + 12MP + 10MP">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold"><i class="fas fa-battery-full spec-icon me-1"></i>Battery</label>
                            <input type="text" name="item_battery" class="form-control" placeholder="e.g. 5000mAh, 45W charging">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold"><i class="fas fa-microchip spec-icon me-1"></i>Processor</label>
                            <input type="text" name="item_processor" class="form-control" placeholder="e.g. Snapdragon 8 Gen 2">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold"><i class="fab fa-android spec-icon me-1"></i>OS</label>
                            <input type="text" name="item_os" class="form-control" placeholder="e.g. Android 14, iOS 17">
                        </div>

                        <!-- Flags -->
                        <div class="col-12 mt-2"><div class="spec-section-title"><i class="fas fa-tag spec-icon me-1"></i>Product Flags</div></div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="item_featured" id="addFeatured">
                                <label class="form-check-label" for="addFeatured"><i class="fas fa-star text-warning me-1"></i>Featured Product</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="item_onsale" id="addOnsale">
                                <label class="form-check-label" for="addOnsale"><i class="fas fa-tag text-danger me-1"></i>On Sale</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_product" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Add Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===== EDIT PRODUCT MODAL ===== -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header" style="background:#00A5C4;color:#fff;">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Product</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="item_id" id="edit_id">
                    <input type="hidden" name="item_image_current" id="edit_image_current">
                    <div class="row g-3">

                        <!-- Basic Info -->
                        <div class="col-12"><div class="spec-section-title"><i class="fas fa-info-circle spec-icon me-1"></i>Basic Information</div></div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Brand *</label>
                            <select name="item_brand" id="edit_brand" class="form-select" required>
                                <?php foreach ($all_brands as $b): ?>
                                <option value="<?php echo htmlspecialchars($b['brand_name']); ?>"><?php echo htmlspecialchars($b['brand_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Product Name *</label>
                            <input type="text" name="item_name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Price (USD) *</label>
                            <div class="input-group"><span class="input-group-text">$</span>
                            <input type="number" name="item_price" id="edit_price" class="form-control" step="0.01" required></div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Old Price (USD)</label>
                            <div class="input-group"><span class="input-group-text">$</span>
                            <input type="number" name="item_old_price" id="edit_old_price" class="form-control" step="0.01"></div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Stock *</label>
                            <input type="number" name="item_stock" id="edit_stock" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">New Image (optional)</label>
                            <input type="file" name="item_image_edit" class="form-control" accept="image/*">
                            <div class="mt-1"><img id="edit_img_preview" src="" style="height:48px;object-fit:contain;background:#f8f9fa;border-radius:6px;padding:3px;"></div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">New Image 2 <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="hidden" name="item_image2_current" id="edit_image2_current">
                            <input type="file" name="item_image2_edit" class="form-control" accept="image/*">
                            <div class="mt-1"><img id="edit_img2_preview" src="" style="height:48px;object-fit:contain;background:#f8f9fa;border-radius:6px;padding:3px;display:none;"></div>
                            <div class="form-check mt-1" id="clear_img2_wrap" style="display:none;">
                                <input class="form-check-input" type="checkbox" name="clear_image2" id="clear_image2">
                                <label class="form-check-label text-danger" for="clear_image2" style="font-size:11px;">Remove Image 2</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">New Image 3 <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="hidden" name="item_image3_current" id="edit_image3_current">
                            <input type="file" name="item_image3_edit" class="form-control" accept="image/*">
                            <div class="mt-1"><img id="edit_img3_preview" src="" style="height:48px;object-fit:contain;background:#f8f9fa;border-radius:6px;padding:3px;display:none;"></div>
                            <div class="form-check mt-1" id="clear_img3_wrap" style="display:none;">
                                <input class="form-check-input" type="checkbox" name="clear_image3" id="clear_image3">
                                <label class="form-check-label text-danger" for="clear_image3" style="font-size:11px;">Remove Image 3</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Description</label>
                            <textarea name="item_description" id="edit_desc" class="form-control" rows="2"></textarea>
                        </div>

                        <!-- Specs -->
                        <div class="col-12 mt-2"><div class="spec-section-title"><i class="fas fa-microchip spec-icon me-1"></i>Technical Specifications</div></div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold"><i class="fas fa-memory spec-icon me-1"></i>RAM</label>
                            <input type="text" name="item_ram" id="edit_ram" class="form-control" placeholder="e.g. 8GB">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold"><i class="fas fa-hdd spec-icon me-1"></i>Storage</label>
                            <input type="text" name="item_storage" id="edit_storage" class="form-control" placeholder="e.g. 128GB">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold"><i class="fas fa-mobile-alt spec-icon me-1"></i>Display</label>
                            <input type="text" name="item_display" id="edit_display" class="form-control" placeholder='e.g. 6.7" AMOLED 120Hz'>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold"><i class="fas fa-camera spec-icon me-1"></i>Camera</label>
                            <input type="text" name="item_camera" id="edit_camera" class="form-control" placeholder="e.g. 108MP + 12MP">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold"><i class="fas fa-battery-full spec-icon me-1"></i>Battery</label>
                            <input type="text" name="item_battery" id="edit_battery" class="form-control" placeholder="e.g. 5000mAh">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold"><i class="fas fa-microchip spec-icon me-1"></i>Processor</label>
                            <input type="text" name="item_processor" id="edit_processor" class="form-control" placeholder="e.g. Snapdragon 8 Gen 2">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold"><i class="fab fa-android spec-icon me-1"></i>OS</label>
                            <input type="text" name="item_os" id="edit_os" class="form-control" placeholder="e.g. Android 14">
                        </div>

                        <!-- Flags -->
                        <div class="col-12 mt-2"><div class="spec-section-title"><i class="fas fa-tag spec-icon me-1"></i>Product Flags</div></div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="item_featured" id="edit_featured">
                                <label class="form-check-label" for="edit_featured"><i class="fas fa-star text-warning me-1"></i>Featured</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="item_onsale" id="edit_onsale">
                                <label class="form-check-label" for="edit_onsale"><i class="fas fa-tag text-danger me-1"></i>On Sale</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_product" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===== BRANDS MODAL ===== -->
<div class="modal fade" id="brandsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:#FFD289;color:#003859;">
                <h5 class="modal-title"><i class="fas fa-tags me-2"></i>Manage Brands</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="post" class="d-flex gap-2 mb-4">
                    <input type="text" name="brand_name" class="form-control" placeholder="New brand name e.g. Huawei" required>
                    <button type="submit" name="add_brand" class="btn btn-primary text-nowrap"><i class="fas fa-plus me-1"></i>Add</button>
                </form>
                <table class="table table-sm">
                    <thead><tr><th>#</th><th>Brand Name</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($all_brands as $b): ?>
                        <tr>
                            <td><?php echo $b['brand_id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($b['brand_name']); ?></strong></td>
                            <td>
                                <form method="post" class="d-inline" onsubmit="return confirm('Delete brand?')">
                                    <input type="hidden" name="brand_id" value="<?php echo $b['brand_id']; ?>">
                                    <button type="submit" name="delete_brand" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Remove any leftover modal backdrop on page load (happens after form POST inside modal)
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.modal-backdrop').forEach(function(el){ el.remove(); });
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
});

function fillEdit(p) {
    document.getElementById('edit_id').value            = p.item_id;
    document.getElementById('edit_name').value          = p.item_name;
    document.getElementById('edit_price').value         = p.item_price;
    document.getElementById('edit_old_price').value     = p.item_old_price || '';
    document.getElementById('edit_stock').value         = p.item_stock;
    document.getElementById('edit_desc').value          = p.item_description || '';
    document.getElementById('edit_featured').checked    = p.item_featured == 1;
    document.getElementById('edit_onsale').checked      = p.item_onsale   == 1;
    document.getElementById('edit_image_current').value = p.item_image;
    document.getElementById('edit_img_preview').src     = '../' + p.item_image.replace('./', '');

    // Image 2
    var img2 = p.item_image2 || '';
    document.getElementById('edit_image2_current').value = img2;
    if (img2) {
        document.getElementById('edit_img2_preview').src     = '../' + img2.replace('./', '');
        document.getElementById('edit_img2_preview').style.display = 'block';
        document.getElementById('clear_img2_wrap').style.display   = 'block';
    } else {
        document.getElementById('edit_img2_preview').style.display = 'none';
        document.getElementById('clear_img2_wrap').style.display   = 'none';
    }

    // Image 3
    var img3 = p.item_image3 || '';
    document.getElementById('edit_image3_current').value = img3;
    if (img3) {
        document.getElementById('edit_img3_preview').src     = '../' + img3.replace('./', '');
        document.getElementById('edit_img3_preview').style.display = 'block';
        document.getElementById('clear_img3_wrap').style.display   = 'block';
    } else {
        document.getElementById('edit_img3_preview').style.display = 'none';
        document.getElementById('clear_img3_wrap').style.display   = 'none';
    }
    // specs
    document.getElementById('edit_ram').value       = p.item_ram       || '';
    document.getElementById('edit_storage').value   = p.item_storage   || '';
    document.getElementById('edit_display').value   = p.item_display   || '';
    document.getElementById('edit_camera').value    = p.item_camera    || '';
    document.getElementById('edit_battery').value   = p.item_battery   || '';
    document.getElementById('edit_processor').value = p.item_processor || '';
    document.getElementById('edit_os').value        = p.item_os        || '';
    // brand dropdown
    var sel = document.getElementById('edit_brand');
    for (var i = 0; i < sel.options.length; i++) {
        if (sel.options[i].value === p.item_brand) { sel.selectedIndex = i; break; }
    }
}
document.getElementById('productSearch').addEventListener('input', function(){
    var q = this.value.toLowerCase();
    document.querySelectorAll('#productsTable tbody tr').forEach(function(row){
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>
</body>
</html>

