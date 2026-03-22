<?php
/**
 * pages/admin/settings.php — Trang cài đặt hệ thống (Admin)
 *
 * Chức năng:
 * - Cho phép admin chỉnh sửa các cài đặt chung của hệ thống
 * - Bảng `settings` dùng mô hình key-value (setting_key, setting_value)
 * - Các cài đặt bao gồm: tên site, phụ thu khách, ngân hàng, liên hệ
 */

// Kiểm tra xác thực đăng nhập admin & lấy kết nối database
require_once __DIR__ . '/../../includes/auth.php';
$conn = db();

// ============================================================
// XỬ LÝ LƯU CÀI ĐẶT (khi form được submit bằng POST)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Danh sách các key cài đặt được phép cập nhật
    $fields = ['site_name','surcharge_per_guest','surcharge_threshold','bank_name','bank_account','bank_holder','contact_zalo','contact_address'];

    // Chuẩn bị câu lệnh UPDATE dùng prepared statement để chống SQL Injection
    $stmt = $conn->prepare("UPDATE settings SET setting_value=? WHERE setting_key=?");

    // Lặp qua từng key, lấy giá trị từ form POST rồi cập nhật vào DB
    foreach ($fields as $k) {
        $v = trim($_POST[$k] ?? '');   // Lấy giá trị từ form, xóa khoảng trắng thừa
        $stmt->bind_param('ss', $v, $k); // Bind 2 tham số kiểu string (value, key)
        $stmt->execute();                // Thực thi câu UPDATE
    }
    $stmt->close();

    // Đặt thông báo thành công vào session
    $_SESSION['success'] = 'Cài đặt đã được lưu thành công!';

    // Redirect về chính trang này (PRG pattern - tránh resubmit form khi refresh)
    header('Location: settings.php');
    exit;
}

// ============================================================
// ĐỌC TOÀN BỘ CÀI ĐẶT TỪ DATABASE
// ============================================================
$settings = []; // Mảng lưu cài đặt dạng key => value
$res = $conn->query("SELECT * FROM settings");

// Chuyển kết quả từ DB thành mảng PHP, VD: $settings['site_name'] = 'Homestay ABC'
while ($r = $res->fetch_assoc()) {
    $settings[$r['setting_key']] = $r['setting_value'];
}

// Đặt tiêu đề trang và menu active cho sidebar admin
$page_title = 'Cài đặt hệ thống';
$active_page = 'settings';

// Include header admin (chứa sidebar, navbar, CSS, JS chung)
include __DIR__ . '/../../includes/admin_header.php';
?>

<!-- ============================================================ -->
<!-- GIAO DIỆN FORM CÀI ĐẶT -->
<!-- ============================================================ -->
<div class="admin-form">
    <h3>⚙️ Cấu hình hệ thống</h3>
    <form method="POST">

        <!-- ===== PHẦN 1: Thông tin chung ===== -->
        <div class="settings-section">Thông tin chung</div>
        <div class="form-row full">
            <div class="form-group">
                <label>Tên website</label>
                <!-- Hiển thị giá trị hiện tại, dùng hàm e() để escape HTML chống XSS -->
                <input type="text" name="site_name" value="<?= e($settings['site_name'] ?? '') ?>" placeholder="SKIBIDI TOLET">
            </div>
        </div>

        <!-- ===== PHẦN 2: Chính sách phụ thu ===== -->
        <!-- Khi số khách vượt ngưỡng (threshold), mỗi khách thêm sẽ bị tính phụ thu -->
        <div class="settings-section">Chính sách phụ thu</div>
        <div class="form-row">
            <div class="form-group">
                <label>Phụ thu mỗi khách thêm (đ)</label>
                <!-- Số tiền phụ thu cho mỗi khách vượt ngưỡng, mặc định 50.000đ -->
                <input type="number" name="surcharge_per_guest" value="<?= e($settings['surcharge_per_guest'] ?? '50000') ?>">
            </div>
            <div class="form-group">
                <label>Bắt đầu phụ thu từ khách thứ</label>
                <!-- Ngưỡng bắt đầu tính phụ thu, mặc định từ khách thứ 2 -->
                <input type="number" name="surcharge_threshold" value="<?= e($settings['surcharge_threshold'] ?? '2') ?>">
            </div>
        </div>

        <!-- ===== PHẦN 3: Thông tin thanh toán (ngân hàng) ===== -->
        <!-- Dùng để hiển thị cho khách hàng khi chuyển khoản -->
        <div class="settings-section">Thông tin thanh toán</div>
        <div class="form-row">
            <div class="form-group">
                <label>Ngân hàng</label>
                <input type="text" name="bank_name" value="<?= e($settings['bank_name'] ?? '') ?>" placeholder="MB Bank">
            </div>
            <div class="form-group">
                <label>Số tài khoản</label>
                <input type="text" name="bank_account" value="<?= e($settings['bank_account'] ?? '') ?>" placeholder="0123456789">
            </div>
        </div>
        <div class="form-row full">
            <div class="form-group">
                <label>Chủ tài khoản</label>
                <input type="text" name="bank_holder" value="<?= e($settings['bank_holder'] ?? '') ?>" placeholder="NGUYEN VAN A">
            </div>
        </div>

        <!-- ===== PHẦN 4: Thông tin liên hệ ===== -->
        <div class="settings-section">Thông tin liên hệ</div>
        <div class="form-row">
            <div class="form-group">
                <label>Số Zalo liên hệ</label>
                <input type="text" name="contact_zalo" value="<?= e($settings['contact_zalo'] ?? '') ?>" placeholder="0399190522">
            </div>
            <div class="form-group">
                <label>Địa chỉ</label>
                <input type="text" name="contact_address" value="<?= e($settings['contact_address'] ?? '') ?>" placeholder="256 Đ. Nguyễn Văn Cừ, Cần Thơ">
            </div>
        </div>

        <!-- Nút submit form -->
        <div style="padding-top:24px;border-top:1px solid rgba(0,0,0,0.06);margin-top:28px;">
            <button type="submit" class="act-btn primary" style="padding:12px 32px;font-size:0.9rem">
                💾 Lưu cài đặt
            </button>
        </div>
    </form>
</div>

<?php
// Include footer admin (đóng layout, JS chung)
include __DIR__ . '/../../includes/admin_footer.php';
?>
