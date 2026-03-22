<?php
/**
 * ============================================================
 * pages/auth/register.php — Trang đăng ký tài khoản
 * ============================================================
 * Hiển thị form đăng ký: họ tên, email, mật khẩu, SĐT, địa chỉ
 * Khi bấm "Đăng ký" → gửi POST đến process_register.php
 */

// Nạp auth.php → session, BASE_URL, hàm tiện ích
require_once __DIR__ . '/../../includes/auth.php';

// Nếu đã đăng nhập → không cần đăng ký, về trang chủ
if (is_logged_in()) {
    header('Location: ' . BASE_URL);
    exit;
}

// Lấy thông báo lỗi/thành công (nếu process_register.php đã đặt)
$error   = flash('error');
$success = flash('success');
$base_url = BASE_URL;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký — SKIBIDI TOLET</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/main.css">
</head>
<body>

<div class="auth-page"> <!-- Nền tối, căn giữa card -->
    <div class="auth-card"> <!-- Card trắng chứa form -->
        <h2>Đăng ký tài khoản</h2>

        <!-- Hiện lỗi/thành công nếu có -->
        <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <!-- FORM ĐĂNG KÝ — gửi đến process_register.php -->
        <!-- id="registerForm" → JS validate trước khi gửi -->
        <form id="registerForm" action="process_register.php" method="POST">
            <div class="form-group">
                <!-- Mỗi input có id (JS dùng) và name (PHP dùng) -->
                <input type="text" id="name" name="name" placeholder="Họ tên *">
                <span class="field-error" id="nameError"></span> <!-- JS hiện lỗi ở đây -->
            </div>
            <div class="form-group">
                <input type="text" id="email" name="email" placeholder="Email *">
                <span class="field-error" id="emailError"></span>
            </div>
            <div class="form-group">
                <input type="password" id="password" name="password" placeholder="Mật khẩu * (tối thiểu 6 ký tự)">
                <span class="field-error" id="passError"></span>
            </div>
            <div class="form-group">
                <input type="password" id="repassword" name="repassword" placeholder="Nhập lại mật khẩu *">
                <span class="field-error" id="repassError"></span>
            </div>
            <div class="form-group">
                <input type="text" id="phone" name="phone" placeholder="Số điện thoại">
                <span class="field-error" id="phoneError"></span>
            </div>
            <div class="form-group">
                <input type="text" name="address" placeholder="Địa chỉ">
                <!-- Không có validate cho địa chỉ (không bắt buộc) -->
            </div>
            <button type="submit" class="btn btn-primary">Đăng ký</button>
        </form>

        <!-- Link sang trang đăng nhập -->
        <p class="auth-link">Đã có tài khoản? <a href="login.php">Đăng nhập</a></p>
        <p class="auth-link"><a href="<?= $base_url ?>">← Về trang chủ</a></p>
    </div>
</div>

<script src="<?= $base_url ?>assets/js/main.js" defer></script>
</body>
</html>
