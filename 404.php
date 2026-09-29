<?php
http_response_code(404);
$page_title = 'Page Not Found';
ob_start();
include 'header.php';
?>

<section class="py-5" style="background:#f8f9fa; min-height:80vh;">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 text-center py-5">
            <div style="font-size:100px;font-weight:900;color:#003859;font-family:'Rubik',sans-serif;line-height:1;">404</div>
            <div style="width:60px;height:4px;background:linear-gradient(90deg,#003859,#00A5C4);border-radius:2px;margin:16px auto;"></div>
            <h4 style="font-family:'Rubik',sans-serif;font-weight:700;color:#333;">Page Not Found</h4>
            <p class="text-muted mb-4">The page you're looking for doesn't exist or has been moved.</p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="index.php" class="btn btn-primary-custom px-4">
                    <i class="fas fa-home me-2"></i>Back to Home
                </a>
                <a href="search.php" class="btn btn-outline-secondary px-4">
                    <i class="fas fa-search me-2"></i>Browse Products
                </a>
            </div>
        </div>
    </div>
</div>
</section>

<?php include 'footer.php'; ?>
