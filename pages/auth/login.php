<?php
/**
 * ============================================================
 * pages/auth/login.php — Trang đăng nhập
 * ============================================================
 * Hiển thị form nhập email + mật khẩu.
 * Khi bấm "Đăng nhập" → form gửi POST đến process_login.php
 * 
 * Nếu user đã đăng nhập → tự chuyển về trang chủ (không cần login lại)
 */

// Nạp auth.php → có hàm is_logged_in(), flash(), e(), hằng BASE_URL
require_once __DIR__ . '/../../includes/auth.php';

// Kiểm tra nếu ĐÃ đăng nhập → không cần ở trang login nữa
if (is_logged_in()) {
    header('Location: ' . BASE_URL); // Chuyển về trang chủ
    exit; // Dừng script (không render HTML bên dưới)
}

// flash() lấy thông báo từ session rồi XÓA (chỉ hiện 1 lần)
// VD: process_login.php đặt $_SESSION['error'] = 'Sai mật khẩu'
//     → flash('error') trả về 'Sai mật khẩu' rồi xóa khỏi session
$error   = flash('error');   // Thông báo lỗi (nếu có)
$success = flash('success'); // Thông báo thành công (vd: đăng ký xong)
$base_url = BASE_URL;        // '/homestay/' — dùng cho link CSS, JS
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập — SKIBIDI TOLET</title>
    <!-- Nạp font + CSS giống các trang khác -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/main.css">
</head>
<body>

<!-- class="auth-page" → CSS tạo nền tối + căn giữa card login -->
<div class="auth-page">
    <div class="auth-card">
        <h2>Đăng nhập</h2>

        <!-- Hiện thông báo lỗi nếu có (vd: sai mật khẩu, email không tồn tại) -->
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <!-- Hiện thông báo thành công nếu có (vd: đăng ký xong, mời đăng nhập) -->
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <!-- FORM ĐĂNG NHẬP -->
        <!-- method="POST" = gửi dữ liệu ẩn (không hiện trên URL) -->
        <!-- action="process_login.php" = gửi đến file xử lý (cùng thư mục) -->
        <form id="loginForm" action="process_login.php" method="POST">
            <div class="form-group">
                <!-- name="email" → PHP nhận bằng $_POST['email'] -->
                <input type="text" id="loginEmail" name="email" placeholder="Email">
                <!-- span hiện lỗi validate JS (vd: "Nhập email") -->
                <span class="field-error" id="loginEmailError"></span>
            </div>
            <div class="form-group">
                <!-- type="password" → trình duyệt ẩn ký tự bằng •••• -->
                <input type="password" id="loginPass" name="password" placeholder="Mật khẩu">
                <span class="field-error" id="loginPassError"></span>
            </div>
            <!-- type="submit" → bấm nút này sẽ gửi form -->
            <button type="submit" class="btn btn-primary">Đăng nhập</button>
        </form>

        <!-- Link đến trang đăng ký (cùng thư mục auth/) -->
        <p class="auth-link">Chưa có tài khoản? <a href="register.php">Đăng ký</a></p>
        <!-- Link về trang chủ -->
        <p class="auth-link"><a href="<?= $base_url ?>">← Về trang chủ</a></p>
    </div>
</div>

<!-- Nạp JS để validate form trước khi gửi -->
<script src="<?= $base_url ?>assets/js/main.js" defer></script>
</body>
</html>
