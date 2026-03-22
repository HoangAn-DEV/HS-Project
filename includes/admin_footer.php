<?php
/**
 * ============================================================
 * includes/admin_footer.php — Phần cuối trang admin
 * ============================================================
 * Đóng các thẻ HTML đã mở trong admin_header.php:
 *   </div>  ← đóng .admin-content
 *   </main> ← đóng .admin-main
 * Thêm: Modal xác nhận + file JavaScript admin
 */
$base_url = defined('BASE_URL') ? BASE_URL : '/homestay/';
?>
    </div><!-- /.admin-content — đóng vùng nội dung chính -->
</main><!-- /.admin-main — đóng khung chính bên phải sidebar -->

<!-- ============================================================ -->
<!-- CONFIRM MODAL (hộp thoại xác nhận)                           -->
<!-- Hiện khi admin bấm nút có data-confirm="..."                 -->
<!-- JS trong admin.js sẽ bắt sự kiện click → hiện modal này      -->
<!-- ============================================================ -->
<div class="modal-overlay" id="confirmModal">
    <div class="modal-box">
        <div class="modal-title">Xác nhận thao tác</div>
        <!-- Nội dung câu hỏi xác nhận — JS sẽ thay đổi nội dung này -->
        <div class="modal-text" id="confirmText">Bạn có chắc chắn muốn thực hiện?</div>
        <div class="modal-actions">
            <!-- Nút "Hủy bỏ" → đóng modal, không làm gì -->
            <button class="act-btn" id="confirmCancel">Hủy bỏ</button>
            <!-- Nút "Xác nhận" → JS sẽ set href rồi chuyển hướng đến action URL -->
            <a href="#" class="act-btn red" id="confirmOk">Xác nhận</a>
        </div>
    </div>
</div>

<!-- Nạp file JavaScript admin -->
<!-- defer = tải JS sau khi HTML đã load xong (trang hiện nhanh hơn) -->
<script src="<?= $base_url ?>assets/js/admin.js" defer></script>
</body>
</html>
