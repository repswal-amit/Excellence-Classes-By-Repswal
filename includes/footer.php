    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="footer-top">
            <div class="container">
                <div class="row g-4">
                    <!-- Brand Column -->
                    <div class="col-lg-4 col-md-6">
                        <div class="footer-brand">
                            <div class="brand-logo mb-3">
                                <span class="brand-icon"><i class="bi bi-mortarboard-fill"></i></span>
                                <div class="brand-text">
                                    <span class="brand-name">Excellence Classes</span>
                                    <span class="brand-sub">By Repswal</span>
                                </div>
                            </div>
                            <p class="footer-tagline"><?= e(APP_TAGLINE) ?></p>
                            <p class="footer-desc">
                                <?= e(getSetting('about_text', 'Excellence Classes By Repswal is an educational and IT training institute providing quality education, practical training, and internship programs.')) ?>
                            </p>
                            <div class="footer-social">
                                <?php if ($fb = getSetting('facebook_url')): ?>
                                    <a href="<?= e($fb) ?>" target="_blank" rel="noopener" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                                <?php endif; ?>
                                <?php if ($tw = getSetting('twitter_url')): ?>
                                    <a href="<?= e($tw) ?>" target="_blank" rel="noopener" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>
                                <?php endif; ?>
                                <?php if ($ig = getSetting('instagram_url')): ?>
                                    <a href="<?= e($ig) ?>" target="_blank" rel="noopener" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                                <?php endif; ?>
                                <?php if ($li = getSetting('linkedin_url')): ?>
                                    <a href="<?= e($li) ?>" target="_blank" rel="noopener" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
                                <?php endif; ?>
                                <?php if ($yt = getSetting('youtube_url')): ?>
                                    <a href="<?= e($yt) ?>" target="_blank" rel="noopener" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Links -->
                    <div class="col-lg-2 col-md-6">
                        <h5 class="footer-heading">Quick Links</h5>
                        <ul class="footer-links">
                            <li><a href="<?= baseUrl() ?>">Home</a></li>
                            <li><a href="<?= baseUrl('about.php') ?>">About Us</a></li>
                            <li><a href="<?= baseUrl('courses.php') ?>">Courses</a></li>
                            <li><a href="<?= baseUrl('internships.php') ?>">Internships</a></li>
                            <li><a href="<?= baseUrl('contact.php') ?>">Contact Us</a></li>
                        </ul>
                    </div>
                    
                    <!-- Programs -->
                    <div class="col-lg-3 col-md-6">
                        <h5 class="footer-heading">Programs</h5>
                        <ul class="footer-links">
                            <li><a href="<?= baseUrl('internships.php') ?>">Web Development</a></li>
                            <li><a href="<?= baseUrl('internships.php') ?>">Python Programming</a></li>
                            <li><a href="<?= baseUrl('internships.php') ?>">Java Programming</a></li>
                            <li><a href="<?= baseUrl('internships.php') ?>">App Development</a></li>
                            <li><a href="<?= baseUrl('verify-certificate.php') ?>">Verify Certificate</a></li>
                        </ul>
                    </div>
                    
                    <!-- Contact Info -->
                    <div class="col-lg-3 col-md-6">
                        <h5 class="footer-heading">Contact Us</h5>
                        <ul class="footer-contact">
                            <li>
                                <i class="bi bi-geo-alt-fill"></i>
                                <span><?= e(getSetting('site_address', 'Nawalgarh, Jhunjhunu, Rajasthan, India - 333308')) ?></span>
                            </li>
                            <?php if ($phone = getSetting('site_phone')): ?>
                            <li>
                                <i class="bi bi-telephone-fill"></i>
                                <a href="tel:<?= e($phone) ?>"><?= e($phone) ?></a>
                            </li>
                            <?php endif; ?>
                            <li>
                                <i class="bi bi-envelope-fill"></i>
                                <a href="mailto:<?= e(getSetting('site_email', 'info@excellenceclasses.com')) ?>"><?= e(getSetting('site_email', 'info@excellenceclasses.com')) ?></a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-6 text-center text-md-start">
                        <p class="mb-0">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. All rights reserved.</p>
                    </div>
                    <div class="col-md-6 text-center text-md-end">
                        <p class="mb-0">A Sole Proprietorship by <strong>Amit Repswal</strong></p>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Back to Top -->
    <button class="back-to-top" id="backToTop" aria-label="Back to top">
        <i class="bi bi-arrow-up"></i>
    </button>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?= baseUrl('assets/js/main.js') ?>"></script>
    
    <?php if (isset($extraJS)): ?>
        <?php foreach ((array)$extraJS as $js): ?>
            <script src="<?= baseUrl('assets/js/' . $js) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
