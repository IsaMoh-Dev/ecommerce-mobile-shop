<?php
require_once '../functions.php';
if (!$auth->isAdmin()) { header('Location: login.php'); exit; }

$success = '';

// Delete a review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review'])) {
    $rid = (int)$_POST['review_id'];
    $db->con->query("DELETE FROM reviews WHERE review_id={$rid}");
    $success = 'Review deleted.';
}

// Toggle featured
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feature_review'])) {
    $rid = (int)$_POST['review_id'];
    // Add featured column if not exists
    $db->con->query("ALTER TABLE reviews ADD COLUMN IF NOT EXISTS is_featured TINYINT(1) DEFAULT 0");
    $db->con->query("UPDATE reviews SET is_featured = 1 - COALESCE(is_featured,0) WHERE review_id={$rid}");
    $success = 'Review updated.';
}

// Fetch all reviews with product name and user name
$reviews = [];
$res = $db->con->query(
    "SELECT r.*, u.first_name, u.last_name, p.item_name
     FROM reviews r
     JOIN user u ON r.user_id = u.user_id
     JOIN product p ON r.item_id = p.item_id
     ORDER BY r.created_at DESC"
);
if ($res) while ($row = $res->fetch_assoc()) $reviews[] = $row;

$total    = count($reviews);
$avg      = $total ? round(array_sum(array_column($reviews, 'rating')) / $total, 1) : 0;
$five     = count(array_filter($reviews, fn($r) => $r['rating'] == 5));
$four     = count(array_filter($reviews, fn($r) => $r['rating'] == 4));
$three    = count(array_filter($reviews, fn($r) => $r['rating'] == 3));
$two      = count(array_filter($reviews, fn($r) => $r['rating'] == 2));
$one      = count(array_filter($reviews, fn($r) => $r['rating'] == 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews — Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>

<?php $active_page = 'reviews'; include '_sidebar.php'; ?>

<div class="admin-content">
    <div class="admin-topbar">
        <h5 class="mb-0" style="font-family:'Rubik',sans-serif;font-weight:600;">
            <i class="fas fa-star me-2 text-warning"></i>Customer Reviews
        </h5>
        <span class="text-muted" style="font-size:13px;">
            <?php echo $total; ?> review<?php echo $total != 1 ? 's' : ''; ?>
            <?php if ($total > 0): ?>
            &nbsp;·&nbsp; Avg: <strong class="text-warning"><?php echo $avg; ?> / 5</strong>
            <?php endif; ?>
        </span>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible d-flex align-items-center gap-2" style="border-radius:10px;">
        <i class="fas fa-check-circle"></i><?php echo $success; ?>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($total > 0): ?>
    <!-- Rating Summary -->
    <div class="bg-white rounded-3 shadow-sm p-4 mb-4">
        <div class="row align-items-center g-4">
            <div class="col-md-2 text-center">
                <div style="font-size:52px;font-weight:800;color:#003859;font-family:'Rubik',sans-serif;line-height:1;"><?php echo $avg; ?></div>
                <div class="text-warning my-1">
                    <?php for ($i=1;$i<=5;$i++) echo $i<=$avg ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                </div>
                <div class="text-muted" style="font-size:12px;"><?php echo $total; ?> reviews</div>
            </div>
            <div class="col-md-10">
                <?php
                $bars = [5=>$five, 4=>$four, 3=>$three, 2=>$two, 1=>$one];
                foreach ($bars as $stars => $count):
                    $pct = $total > 0 ? round($count / $total * 100) : 0;
                ?>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span style="font-size:12px;font-weight:600;width:40px;"><?php echo $stars; ?> <i class="fas fa-star text-warning" style="font-size:10px;"></i></span>
                    <div class="flex-grow-1 bg-light rounded" style="height:10px;overflow:hidden;">
                        <div style="width:<?php echo $pct; ?>%;height:100%;background:<?php echo $stars>=4?'#ffc107':($stars==3?'#fd7e14':'#dc3545'); ?>;border-radius:4px;transition:width 0.3s;"></div>
                    </div>
                    <span class="text-muted" style="font-size:12px;width:30px;"><?php echo $count; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Reviews Table -->
    <div class="admin-table">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Product</th>
                    <th>Rating</th>
                    <th>Comment</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reviews)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-5">
                        <i class="fas fa-star fa-2x mb-2 d-block text-muted"></i>
                        No reviews yet.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($reviews as $r): ?>
                <tr>
                    <td style="font-size:12px;color:#888;"><?php echo $r['review_id']; ?></td>
                    <td>
                        <div style="font-weight:600;font-size:13px;"><?php echo htmlspecialchars($r['first_name'] . ' ' . $r['last_name']); ?></div>
                    </td>
                    <td>
                        <a href="../product.php?item_id=<?php echo $r['item_id']; ?>" target="_blank"
                           style="font-size:13px;color:#00A5C4;text-decoration:none;">
                            <?php echo htmlspecialchars($r['item_name']); ?>
                            <i class="fas fa-external-link-alt ms-1" style="font-size:10px;"></i>
                        </a>
                    </td>
                    <td>
                        <div class="text-warning" style="font-size:13px;">
                            <?php for ($i=1;$i<=5;$i++) echo $i<=$r['rating'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                        </div>
                        <div style="font-size:11px;color:#888;"><?php echo $r['rating']; ?>/5</div>
                    </td>
                    <td style="max-width:300px;font-size:13px;color:#444;">
                        <?php echo htmlspecialchars(mb_substr($r['comment'], 0, 100)) . (mb_strlen($r['comment']) > 100 ? '...' : ''); ?>
                    </td>
                    <td style="font-size:12px;color:#888;white-space:nowrap;">
                        <?php echo date('M d, Y', strtotime($r['created_at'])); ?>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <form method="post" class="d-inline">
                                <input type="hidden" name="review_id" value="<?php echo $r['review_id']; ?>">
                                <button type="submit" name="feature_review"
                                        class="btn btn-sm <?php echo !empty($r['is_featured']) ? 'btn-warning' : 'btn-outline-warning'; ?>"
                                        title="<?php echo !empty($r['is_featured']) ? 'Unfeature' : 'Feature'; ?>">
                                    <i class="fas fa-star"></i>
                                </button>
                            </form>
                            <form method="post" class="d-inline" onsubmit="return confirm('Delete this review?')">
                                <input type="hidden" name="review_id" value="<?php echo $r['review_id']; ?>">
                                <button type="submit" name="delete_review" class="btn btn-sm btn-outline-danger" title="Delete">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
