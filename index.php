<?php
$page_title = 'Home';
ob_start();
include 'header.php';
include 'Template/_banner-area.php';
include 'Template/_top-sale.php';
include 'Template/_special-price.php';
include 'Template/_banner-ads.php';
include 'Template/_new-phones.php';
include 'Template/_blogs.php';
?>
<?php if (isset($_GET['need_login'])): ?>
<script>
$(document).ready(function(){
    var modal = new bootstrap.Modal(document.getElementById('authModal'));
    modal.show();
});
</script>
<?php endif; ?>
<?php include 'footer.php'; ?>
