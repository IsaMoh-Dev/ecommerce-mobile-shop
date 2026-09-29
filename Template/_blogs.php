<?php
// Fetch latest 3 blog posts from DB
$home_blogs = [];
$res = $db->con->query("SELECT * FROM blog ORDER BY published_at DESC LIMIT 3");
if ($res) while ($row = $res->fetch_assoc()) $home_blogs[] = $row;

// Fallback to static if DB empty
if (empty($home_blogs)) {
    $home_blogs = [
        ['blog_id'=>0,'title'=>'Upcoming Mobiles 2026',       'image'=>'assets/blog/blog1.jpg','published_at'=>'2026-04-20','content'=>'Explore the next generation of smartphones launching this season. From foldable screens to ultra-fast 5G connectivity, see which flagship devices are worth the wait.'],
        ['blog_id'=>0,'title'=>'How to Maintain Your Phone',  'image'=>'assets/blog/blog2.jpg','published_at'=>'2026-04-15','content'=>'Keep your smartphone running like new with our professional maintenance tips. Learn how to optimize battery health and manage background apps.'],
        ['blog_id'=>0,'title'=>'Top 5 Budget Phones in 2026', 'image'=>'assets/blog/blog3.jpg','published_at'=>'2026-04-10','content'=>'Looking for great value? We review the top 5 budget smartphones that deliver premium features without breaking the bank.'],
    ];
}
?>
<!-- Blogs -->
<section id="blogs" style="background:#fafaf8;">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="section-heading mb-0">Latest Blog Posts</h4>
            <a href="blog.php" style="font-size:13px;color:var(--secondary);font-weight:600;">
                View All <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="owl-carousel owl-theme">
            <?php foreach ($home_blogs as $b): ?>
            <div class="item">
                <div class="card border-0 shadow-sm me-3" style="border-radius:12px;overflow:hidden;">
                    <div class="blog-card-img-wrap">
                        <img src="<?php echo htmlspecialchars($b['image']); ?>"
                             alt="<?php echo htmlspecialchars($b['title']); ?>">
                    </div>
                    <div class="card-body p-3">
                        <p class="text-muted mb-1" style="font-size:11px;">
                            <i class="fas fa-calendar me-1"></i>
                            <?php echo date('M d, Y', strtotime($b['published_at'])); ?>
                        </p>
                        <h6 style="font-family:'Rubik',sans-serif;font-weight:600;font-size:14px;color:#003859;margin-bottom:8px;">
                            <?php echo htmlspecialchars($b['title']); ?>
                        </h6>
                        <p style="font-size:12px;color:#666;line-height:1.6;margin-bottom:10px;">
                            <?php echo htmlspecialchars(mb_substr($b['content'], 0, 90)) . '...'; ?>
                        </p>
                        <a href="blog.php" style="font-size:12px;font-weight:600;color:var(--secondary);text-decoration:none;">
                            Read More <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<!-- !Blogs -->
