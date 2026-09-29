<?php
// =====================================================
// Mid-page Ad Banners — fully dynamic from DB
// Admin manages these at: admin/banners.php
// =====================================================

$ad_banners = [];

$ad_defaults = [
    'ad_banner_1' => 'assets/banner1-cr-500x150.jpg',
    'ad_banner_2' => 'assets/banner2-cr-500x150.jpg',
];

foreach ($ad_defaults as $key => $default_path) {
    $res = $db->con->query("SELECT setting_value FROM settings WHERE setting_key='{$key}' LIMIT 1");

    if ($res && $res->num_rows > 0) {
        // Key exists in DB
        $val = $res->fetch_assoc()['setting_value'];
        if ($val !== '') {
            // Non-empty = active ad banner
            $ad_banners[] = $val;
        }
        // Empty string = admin removed it, skip
    } else {
        // Key not in DB yet (fresh install) — use default
        if (file_exists($default_path)) {
            $ad_banners[] = $default_path;
        }
    }
}
?>
<!-- Banner Ads -->
<?php if (!empty($ad_banners)): ?>
<section id="banner_adds">
    <div class="container py-5 text-center">
        <?php foreach ($ad_banners as $ad): ?>
        <img src="<?php echo htmlspecialchars($ad); ?>" alt="Ad Banner" class="img-fluid mx-2">
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
<!-- !Banner Ads -->
