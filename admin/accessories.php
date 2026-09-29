<?php
require_once '../functions.php';
if (!$auth->canAccessAdmin()) { header('Location: login.php'); exit; }

$success = '';
$error   = '';

$categories = ['Cases', 'Chargers', 'Earphones', 'Powerbanks', 'Other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ADD
    if (isset($_POST['add_acc'])) {
        $image_path = './assets/products/1.png';
        if (!empty($_FILES['acc_image']['name'])) {
            $allowed = ['jpg','jpeg','png','webp','gif'];
            $ext     = strtolower(pathinfo($_FILES['acc_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $filename   = 'acc_' . time() . '.' . $ext;
                $upload_dir = '../assets/products/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                if (move_uploaded_file($_FILES['acc_image']['tmp_name'], $upload_dir . $filename))
                    $image_path = './assets/products/' . $filename;
            }
        }
        $name      = mysqli_real_escape_string($db->con, trim($_POST['acc_name']));
        $cat       = mysqli_real_escape_string($db->con, trim($_POST['acc_category']));
        $price     = (float)$_POST['acc_price'];
        $old_price = !empty($_POST['acc_old_price']) ? (float)$_POST['acc_old_price'] : 'NULL';
        $desc      = mysqli_real_escape_string($db->con, trim($_POST['acc_description']));
        $stock     = (int)$_POST['acc_stock'];
        $featured  = isset($_POST['acc_featured']) ? 1 : 0;
        $onsale    = isset($_POST['acc_onsale'])   ? 1 : 0;
        $img       = mysqli_real_escape_string($db->con, $image_path);
        $old_val   = is_numeric($old_price) ? $old_price : 'NULL';

        $sql = "INSERT INTO accessories (acc_name,acc_category,acc_price,acc_old_price,acc_image,acc_description,acc_stock,acc_featured,acc_onsale)
                VALUES ('{$name}','{$cat}',{$price},{$old_val},'{$img}','{$desc}',{$stock},{$featured},{$onsale})";
        if ($db->con->query($sql)) $success = 'Accessory added!';
        else $error = 'Failed: ' . $db->con->error;
    }

    // DELETE
    if (isset($_POST['delete_acc'])) {
        $id = (int)$_POST['acc_id'];
        $db->con->query("DELETE FROM accessories WHERE acc_id={$id}");
        $success = 'Accessory deleted.';
    }

    // UPDATE
    if (isset($_POST['update_acc'])) {
        $id        = (int)$_POST['acc_id'];
        $name      = mysqli_real_escape_string($db->con, trim($_POST['acc_name']));
        $cat       = mysqli_real_escape_string($db->con, trim($_POST['acc_category']));
        $price     = (float)$_POST['acc_price'];
        $old_price = !empty($_POST['acc_old_price']) ? (float)$_POST['acc_old_price'] : 'NULL';
        $desc      = mysqli_real_escape_string($db->con, trim($_POST['acc_description']));
        $stock     = (int)$_POST['acc_stock'];
        $featured  = isset($_POST['acc_featured']) ? 1 : 0;
        $onsale    = isset($_POST['acc_onsale'])   ? 1 : 0;
        $img       = mysqli_real_escape_string($db->con, $_POST['acc_image_current']);
        $old_val   = is_numeric($old_price) ? $old_price : 'NULL';

        if (!empty($_FILES['acc_image_edit']['name'])) {
            $allowed = ['jpg','jpeg','png','webp','gif'];
            $ext     = strtolower(pathinfo($_FILES['acc_image_edit']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $filename = 'acc_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['acc_image_edit']['tmp_name'], '../assets/products/' . $filename))
                    $img = mysqli_real_escape_string($db->con, './assets/products/' . $filename);
            }
        }

        $sql = "UPDATE accessories SET
                    acc_name='{$name}',acc_category='{$cat}',acc_price={$price},
                    acc_old_price={$old_val},acc_image='{$img}',acc_description='{$desc}',
                    acc_stock={$stock},acc_featured={$featured},acc_onsale={$onsale}
                WHERE acc_id={$id}";
        if ($db->con->query($sql)) $success = 'Accessory updated!';
        else $error = 'Update failed: ' . $db->con->error;
    }
}

// Fetch all
$all = [];
$res = $db->con->query("SELECT * FROM accessories ORDER BY acc_category, acc_name");
while ($row = $res->fetch_assoc()) $all[] = $row;

// Stats
$total    = count($all);
$onsale   = count(array_filter($all, fn($a) => $a['acc_onsale']));
$featured = count(array_filter($all, fn($a) => $a['acc_featured']));
$low      = count(array_filter($all, fn($a) => $a['acc_stock'] <= 5));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accessories — Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>

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
        <a class="nav-link active" href="accessories.php"><i class="fas fa-headphones"></i>Accessories</a>
        <a class="nav-link" href="blog.php"><i class="fas fa-newspaper"></i>Blog</a>
        <a class="nav-link" href="banners.php"><i class="fas fa-images"></i>Banners</a>
        <a class="nav-link" href="orders.php"><i class="fas fa-shopping-bag"></i>Orders</a>
        <a class="nav-link" href="users.php"><i class="fas fa-users"></i>Users</a>
        <hr style="border-color:rgba(255,255,255,0.1);margin:10px 20px;">
        <a class="nav-link" href="settings.php"><i class="fas fa-cog"></i>Settings</a>
        <a class="nav-link" href="../index.php" target="_blank"><i class="fas fa-store"></i>View Shop</a>
        <a class="nav-link text-danger" href="../logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a>
    </nav>
</div>

<div class="admin-content">
    <div class="admin-topbar">
        <h5 class="mb-0" style="font-family:'Rubik',sans-serif;font-weight:600;">
            <i class="fas fa-headphones me-2"></i>Manage Accessories
        </h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fas fa-plus me-1"></i>Add Accessory
        </button>
    </div>

    <?php if ($success): ?><div class="alert alert-success alert-dismissible"><button type="button" class="btn-close" data-bs-dismiss="alert"></button><?php echo $success; ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-danger  alert-dismissible"><button type="button" class="btn-close" data-bs-dismiss="alert"></button><?php echo $error; ?></div><?php endif; ?>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-label">Total</div>
                <div class="stat-value"><?php echo $total; ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card" style="border-left-color:#dc3545;">
                <div class="stat-label">On Sale</div>
                <div class="stat-value"><?php echo $onsale; ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card" style="border-left-color:#ffc107;">
                <div class="stat-label">Featured</div>
                <div class="stat-value"><?php echo $featured; ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card" style="border-left-color:#fd7e14;">
                <div class="stat-label">Low Stock</div>
                <div class="stat-value"><?php echo $low; ?></div>
            </div>
        </div>
    </div>

    <!-- Filter tabs by category -->
    <div class="bg-white rounded-3 shadow-sm mb-3 px-3 pt-3 pb-0">
        <ul class="nav nav-tabs border-0" id="catTabs">
            <li class="nav-item">
                <a class="nav-link active" href="#" data-cat="all">All</a>
            </li>
            <?php foreach ($categories as $cat): ?>
            <li class="nav-item">
                <a class="nav-link" href="#" data-cat="<?php echo $cat; ?>"><?php echo $cat; ?></a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Table -->
    <div class="admin-table">
        <div class="p-3 border-bottom">
            <input type="text" id="accSearch" class="form-control form-control-sm"
                   placeholder="Search accessories..." style="max-width:300px;">
        </div>
        <div style="overflow-x:auto;">
        <table class="table mb-0" id="accTable">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Sale</th>
                    <th>Featured</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all as $a): ?>
                <tr data-cat="<?php echo htmlspecialchars($a['acc_category']); ?>">
                    <td>
                        <img src="../<?php echo ltrim(htmlspecialchars($a['acc_image']),'./'); ?>"
                             style="width:48px;height:48px;object-fit:contain;background:#f8f9fa;border-radius:8px;padding:3px;">
                    </td>
                    <td style="font-weight:600;font-size:13px;max-width:180px;">
                        <?php echo htmlspecialchars($a['acc_name']); ?>
                        <?php if (!empty($a['acc_description'])): ?>
                        <div style="font-size:11px;color:#888;font-weight:400;margin-top:2px;">
                            <?php echo htmlspecialchars(mb_substr($a['acc_description'], 0, 50)) . '...'; ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $cat_colors = ['Cases'=>'primary','Chargers'=>'warning','Earphones'=>'info','Powerbanks'=>'success','Other'=>'secondary'];
                        $cc = $cat_colors[$a['acc_category']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?php echo $cc; ?>"><?php echo htmlspecialchars($a['acc_category']); ?></span>
                    </td>
                    <td>
                        <span style="font-weight:700;color:#dc3545;">$<?php echo number_format($a['acc_price'],2); ?></span>
                        <?php if ($a['acc_old_price']): ?>
                        <div style="font-size:11px;color:#aaa;text-decoration:line-through;">$<?php echo number_format($a['acc_old_price'],2); ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?php echo $a['acc_stock'] > 5 ? 'bg-success' : 'bg-danger'; ?>">
                            <?php echo $a['acc_stock']; ?>
                        </span>
                    </td>
                    <td><?php echo $a['acc_onsale']   ? '<span class="badge bg-danger">Yes</span>'           : '<span class="badge bg-light text-dark">No</span>'; ?></td>
                    <td><?php echo $a['acc_featured'] ? '<span class="badge bg-warning text-dark">Yes</span>' : '<span class="badge bg-light text-dark">No</span>'; ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary me-1"
                                data-bs-toggle="modal" data-bs-target="#editModal"
                                onclick="fillEdit(<?php echo htmlspecialchars(json_encode($a)); ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form method="post" class="d-inline" onsubmit="return confirm('Delete this accessory?')">
                            <input type="hidden" name="acc_id" value="<?php echo $a['acc_id']; ?>">
                            <button type="submit" name="delete_acc" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($all)): ?>
                <tr><td colspan="8" class="text-center text-muted py-5">No accessories yet. Add your first one!</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- ===== ADD MODAL ===== -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:#003859;color:#fff;">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add Accessory</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Name *</label>
                            <input type="text" name="acc_name" class="form-control" placeholder="e.g. USB-C Fast Charger 33W" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Category *</label>
                            <select name="acc_category" class="form-select" required>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Price (USD) *</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="acc_price" class="form-control" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Old Price</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="acc_old_price" class="form-control" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Stock *</label>
                            <input type="number" name="acc_stock" class="form-control" value="10" min="0" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Image</label>
                            <input type="file" name="acc_image" class="form-control" accept="image/*">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Description</label>
                            <textarea name="acc_description" class="form-control" rows="2" placeholder="Short description..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="acc_featured" id="addFeatured">
                                <label class="form-check-label" for="addFeatured"><i class="fas fa-star text-warning me-1"></i>Featured</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="acc_onsale" id="addOnsale">
                                <label class="form-check-label" for="addOnsale"><i class="fas fa-tag text-danger me-1"></i>On Sale</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_acc" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Add Accessory</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===== EDIT MODAL ===== -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:#00A5C4;color:#fff;">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Accessory</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="acc_id" id="edit_id">
                    <input type="hidden" name="acc_image_current" id="edit_img_current">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Name *</label>
                            <input type="text" name="acc_name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Category *</label>
                            <select name="acc_category" id="edit_cat" class="form-select" required>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Price (USD) *</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="acc_price" id="edit_price" class="form-control" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Old Price</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="acc_old_price" id="edit_old_price" class="form-control" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Stock *</label>
                            <input type="number" name="acc_stock" id="edit_stock" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">New Image</label>
                            <input type="file" name="acc_image_edit" class="form-control" accept="image/*">
                            <div class="mt-1">
                                <img id="edit_img_preview" src="" style="height:44px;object-fit:contain;background:#f8f9fa;border-radius:6px;padding:3px;">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Description</label>
                            <textarea name="acc_description" id="edit_desc" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="acc_featured" id="edit_featured">
                                <label class="form-check-label" for="edit_featured"><i class="fas fa-star text-warning me-1"></i>Featured</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="acc_onsale" id="edit_onsale">
                                <label class="form-check-label" for="edit_onsale"><i class="fas fa-tag text-danger me-1"></i>On Sale</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_acc" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.modal-backdrop').forEach(function(el){ el.remove(); });
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
});
function fillEdit(a) {
    document.getElementById('edit_id').value          = a.acc_id;
    document.getElementById('edit_name').value        = a.acc_name;
    document.getElementById('edit_price').value       = a.acc_price;
    document.getElementById('edit_old_price').value   = a.acc_old_price || '';
    document.getElementById('edit_stock').value       = a.acc_stock;
    document.getElementById('edit_desc').value        = a.acc_description || '';
    document.getElementById('edit_featured').checked  = a.acc_featured == 1;
    document.getElementById('edit_onsale').checked    = a.acc_onsale   == 1;
    document.getElementById('edit_img_current').value = a.acc_image;
    document.getElementById('edit_img_preview').src   = '../' + a.acc_image.replace('./', '');
    var sel = document.getElementById('edit_cat');
    for (var i = 0; i < sel.options.length; i++) {
        if (sel.options[i].value === a.acc_category) { sel.selectedIndex = i; break; }
    }
}

// Live search
document.getElementById('accSearch').addEventListener('input', function () {
    var q = this.value.toLowerCase();
    document.querySelectorAll('#accTable tbody tr').forEach(function (row) {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});

// Category filter tabs
document.querySelectorAll('#catTabs .nav-link').forEach(function (tab) {
    tab.addEventListener('click', function (e) {
        e.preventDefault();
        document.querySelectorAll('#catTabs .nav-link').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        var cat = this.dataset.cat;
        document.querySelectorAll('#accTable tbody tr').forEach(function (row) {
            row.style.display = (cat === 'all' || row.dataset.cat === cat) ? '' : 'none';
        });
        document.getElementById('accSearch').value = '';
    });
});
</script>
</body>
</html>

