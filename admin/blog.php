<?php
require_once '../functions.php';
if (!$auth->isAdmin()) { header('Location: login.php'); exit; }

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ADD BLOG POST
    if (isset($_POST['add_blog'])) {
        $title   = mysqli_real_escape_string($db->con, trim($_POST['blog_title']));
        $content = mysqli_real_escape_string($db->con, trim($_POST['blog_content']));
        $image   = 'assets/blog/blog1.jpg';
        if (!empty($_FILES['blog_image']['name'])) {
            $ext = strtolower(pathinfo($_FILES['blog_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp'])) {
                $fname = 'blog_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['blog_image']['tmp_name'], '../assets/blog/' . $fname))
                    $image = 'assets/blog/' . $fname;
            }
        }
        if ($title && $content) {
            $db->con->query("INSERT INTO blog (title,image,content) VALUES ('{$title}','{$image}','{$content}')");
            $success = 'Blog post published!';
        } else {
            $error = 'Title and content are required.';
        }
    }

    // EDIT BLOG POST
    if (isset($_POST['update_blog'])) {
        $bid     = (int)$_POST['blog_id'];
        $title   = mysqli_real_escape_string($db->con, trim($_POST['blog_title']));
        $content = mysqli_real_escape_string($db->con, trim($_POST['blog_content']));
        $image   = mysqli_real_escape_string($db->con, $_POST['blog_image_current']);

        if (!empty($_FILES['blog_image_edit']['name'])) {
            $ext = strtolower(pathinfo($_FILES['blog_image_edit']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp'])) {
                $fname = 'blog_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['blog_image_edit']['tmp_name'], '../assets/blog/' . $fname))
                    $image = 'assets/blog/' . $fname;
            }
        }

        if ($title && $content) {
            $db->con->query("UPDATE blog SET title='{$title}', image='{$image}', content='{$content}' WHERE blog_id={$bid}");
            $success = 'Blog post updated!';
        } else {
            $error = 'Title and content are required.';
        }
    }

    // DELETE BLOG POST
    if (isset($_POST['delete_blog'])) {
        $bid = (int)$_POST['blog_id'];
        $db->con->query("DELETE FROM blog WHERE blog_id={$bid}");
        $success = 'Blog post deleted.';
    }
}

// Load blog posts
$blogs = [];
$res = $db->con->query("SELECT * FROM blog ORDER BY published_at DESC");
if ($res) while ($row = $res->fetch_assoc()) $blogs[] = $row;

$total     = count($blogs);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog — Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
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
        <a class="nav-link active" href="blog.php"><i class="fas fa-newspaper"></i>Blog</a>
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
            <i class="fas fa-newspaper me-2"></i>Manage Blog
        </h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addBlogModal">
            <i class="fas fa-plus me-1"></i>New Post
        </button>
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

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-label">Total Posts</div>
                <div class="stat-value"><?php echo $total; ?></div>
            </div>
        </div>
    </div>

    <!-- Blog Posts Table -->
    <div class="admin-table">
        <div class="p-3 border-bottom">
            <input type="text" id="blogSearch" class="form-control form-control-sm"
                   placeholder="Search posts..." style="max-width:300px;">
        </div>
        <div style="overflow-x:auto;">
        <table class="table mb-0" id="blogTable">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Title</th>
                    <th>Content Preview</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($blogs)): ?>
                <tr>
                    <td colspan="5" class="text-center text-muted py-5">
                        <i class="fas fa-newspaper fa-2x mb-2 d-block"></i>No blog posts yet. Create your first one!
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($blogs as $b): ?>
                <tr>
                    <td>
                        <img src="../<?php echo ltrim(htmlspecialchars($b['image']), './'); ?>"
                             style="width:60px;height:45px;object-fit:cover;border-radius:8px;background:#f8f9fa;">
                    </td>
                    <td style="font-weight:600;font-size:13px;max-width:200px;">
                        <?php echo htmlspecialchars($b['title']); ?>
                    </td>
                    <td style="font-size:12px;color:#666;max-width:300px;">
                        <?php echo htmlspecialchars(mb_substr($b['content'], 0, 80)) . (mb_strlen($b['content']) > 80 ? '...' : ''); ?>
                    </td>
                    <td style="font-size:12px;color:#888;white-space:nowrap;">
                        <?php echo date('M d, Y', strtotime($b['published_at'])); ?>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary me-1"
                                data-bs-toggle="modal" data-bs-target="#editBlogModal"
                                onclick="fillEdit(<?php echo htmlspecialchars(json_encode($b)); ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form method="post" class="d-inline" onsubmit="return confirm('Delete this post?')">
                            <input type="hidden" name="blog_id" value="<?php echo $b['blog_id']; ?>">
                            <button type="submit" name="delete_blog" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- ===== ADD BLOG MODAL ===== -->
<div class="modal fade" id="addBlogModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:#003859;color:#fff;">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>New Blog Post</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold">Post Title *</label>
                            <input type="text" name="blog_title" class="form-control"
                                   placeholder="e.g. Top 5 Phones of 2026" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Content *</label>
                            <textarea name="blog_content" class="form-control" rows="6"
                                      placeholder="Write your blog post content here..." required></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Cover Image</label>
                            <input type="file" name="blog_image" class="form-control" accept="image/*">
                            <small class="text-muted" style="font-size:11px;">JPG/PNG/WebP. Leave empty for default.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_blog" class="btn btn-success">
                        <i class="fas fa-paper-plane me-1"></i>Publish Post
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===== EDIT BLOG MODAL ===== -->
<div class="modal fade" id="editBlogModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:#00A5C4;color:#fff;">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Blog Post</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="blog_id" id="edit_id">
                    <input type="hidden" name="blog_image_current" id="edit_image_current">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-bold">Post Title *</label>
                            <input type="text" name="blog_title" id="edit_title" class="form-control" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Content *</label>
                            <textarea name="blog_content" id="edit_content" class="form-control" rows="6" required></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">New Cover Image</label>
                            <input type="file" name="blog_image_edit" class="form-control" accept="image/*">
                            <div class="mt-2">
                                <img id="edit_img_preview" src=""
                                     style="width:100%;height:80px;object-fit:cover;border-radius:8px;background:#f8f9fa;">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_blog" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.modal-backdrop').forEach(function (el) { el.remove(); });
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
});

function fillEdit(b) {
    document.getElementById('edit_id').value            = b.blog_id;
    document.getElementById('edit_title').value         = b.title;
    document.getElementById('edit_content').value       = b.content;
    document.getElementById('edit_image_current').value = b.image;
    document.getElementById('edit_img_preview').src     = '../' + b.image.replace('./', '');
}

// Live search
document.getElementById('blogSearch').addEventListener('input', function () {
    var q = this.value.toLowerCase();
    document.querySelectorAll('#blogTable tbody tr').forEach(function (row) {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>
</body>
</html>
