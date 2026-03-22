<?php
/**
 * ============================================================
 * includes/footer.php — Phần cuối trang (dùng chung)
 * ============================================================
 * Include ở CUỐI mỗi trang public.
 * Tạo ra: footer, link JS, đóng </body></html>
 */
$base_url = defined('BASE_URL') ? BASE_URL : '/homestay/';
?>

<!-- ===== FOOTER — id="lien-he" để link #lien-he cuộn xuống đây ===== -->
<footer class="footer" id="lien-he">
    <div class="container">
        <!-- Phần kêu gọi feedback -->
        <div class="footer-top">
            <h2>Trải nghiệm của bạn tại <span>SKIBIDI TOLET</span> rất quan trọng</h2>
            <p>Chia sẻ suy nghĩ để chúng tôi cải thiện dịch vụ tốt hơn.</p>
            <!-- target="_blank" = mở link trong tab mới -->
            <a href="https://zalo.me/0399190522" target="_blank" class="btn-feedback">
                Chia sẻ suy nghĩ của bạn
            </a>
        </div>

        <hr class="footer-divider"> <!-- Đường kẻ ngang phân cách -->

        <!-- 3 cột thông tin — CSS grid tự chia đều -->
        <div class="footer-main">
            <!-- Cột 1: Chính sách -->
            <div class="footer-col">
                <h3>CHÍNH SÁCH</h3>
                <ul> <!-- ul = danh sách không đánh số -->
                    <li><a href="#">Chính sách bảo mật</a></li>   <!-- # = link chưa có trang -->
                    <li><a href="#">Nội quy và quy định</a></li>
                    <li><a href="#">Hình thức thanh toán</a></li>
                    <li><a href="#">Câu hỏi thường gặp</a></li>
                </ul>
            </div>
            <!-- Cột 2: Mạng xã hội -->
            <div class="footer-col">
                <h3>THEO DÕI CHÚNG TÔI</h3>
                <div class="social">
                    <!-- Mỗi link mở trang mạng xã hội trong tab mới -->
                    <a href="https://www.facebook.com/luccan139" target="_blank">📘 Facebook</a>
                    <a href="https://www.tiktok.com/@phd170306" target="_blank">🎵 TikTok</a>
                    <a href="https://www.instagram.com/d.hoangan_" target="_blank">📸 Instagram</a>
                </div>
            </div>
            <!-- Cột 3: Thông tin liên hệ -->
            <div class="footer-col">
                <h3>LIÊN HỆ</h3>
                <!-- Link bấm vào mở Google Maps -->
                <p>📍 <a href="https://maps.app.goo.gl/TJbmMtLRtuktuQU69" target="_blank">
                    256 Đ. Nguyễn Văn Cừ, An Hoà, Ninh Kiều, Cần Thơ
                </a></p>
                <p>📞 Zalo: <a href="https://zalo.me/0399190522">0399190522</a></p>
            </div>
        </div>

        <hr class="footer-divider">

        <!-- Tags SEO — giúp Google hiểu nội dung trang -->
        <div class="footer-tags">
            <span class="tag">Home local Cần Thơ</span>
            <span class="tag">Homestay máy chiếu</span>
            <span class="tag">Tự check-in</span>
        </div>

        <!-- Copyright — date('Y') = năm hiện tại (vd: 2026) -->
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> SKIBIDI TOLET. All rights reserved.</p>
        </div>
    </div>
</footer>

<!-- Nạp file JavaScript chính -->
<!-- defer = tải JS sau khi HTML load xong (trang hiện nhanh hơn) -->
<script src="<?= $base_url ?>assets/js/main.js" defer></script>
</body>
</html>
