<?php
/**
 * ============================================================
 * includes/header.php — Phần đầu trang (dùng chung)
 * ============================================================
 * File này được include ở ĐẦU mỗi trang public.
 * Nó tạo ra: <!DOCTYPE html>, <head>, <header> (thanh nav)
 * 
 * Trước khi include, bạn có thể đặt biến:
 *   $page_title = 'Trang chủ';  → hiện trên tab trình duyệt
 *   $extra_css  = ['admin.css']; → thêm file CSS phụ
 */

// Nạp auth.php nếu chưa có (tránh lỗi gọi is_logged_in() mà chưa define)
// function_exists() = kiểm tra hàm đã được khai báo chưa
if (!function_exists('is_logged_in')) {
    require_once __DIR__ . '/auth.php';
}

// ?? = null coalescing operator (nếu biến chưa đặt thì dùng giá trị mặc định)
$page_title = $page_title ?? 'SKIBIDI TOLET HOMESTAY'; // Tiêu đề trang
$extra_css  = $extra_css  ?? [];                         // Mảng CSS bổ sung
$base_url   = defined('BASE_URL') ? BASE_URL : '/homestay/'; // Đường dẫn gốc

?>
<!DOCTYPE html>
<!-- lang="vi" = trang web bằng tiếng Việt (giúp trình duyệt + SEO) -->
<html lang="vi">
<head>
    <!-- charset UTF-8 = hỗ trợ tiếng Việt -->
    <meta charset="UTF-8">
    <!-- viewport = đảm bảo trang hiển thị đúng trên điện thoại -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Dùng cú pháp ngắn để in giá trị ra HTML -->
    <title><?= e($page_title) ?> — SKIBIDI TOLET</title>

    <!-- Google Fonts: Playfair Display (tiêu đề) + Outfit (nội dung) -->
    <!-- preconnect = kết nối sớm đến server font để tải nhanh hơn -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- CSS chính — $base_url đảm bảo đường dẫn đúng dù ở subfolder -->
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/main.css">

    <!-- CSS bổ sung (nếu trang cần thêm, vd: admin.css) -->
    <?php foreach ($extra_css as $css): ?>
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/<?= $css ?>">
    <?php endforeach; // Kết thúc vòng lặp ?>
</head>
<body>

<!-- ===== THANH ĐIỀU HƯỚNG (NAVIGATION BAR) ===== -->
<!-- class="site-header" → CSS trong main.css sẽ style thanh nav -->
<header class="site-header">
    <!-- Logo — bấm vào sẽ về trang chủ (href=$base_url = '/homestay/') -->
    <a href="<?= $base_url ?>" class="logo">SKIBIDI <span>TOLET</span></a>

    <!-- Nút hamburger ☰ — chỉ hiện trên mobile (CSS ẩn trên desktop) -->
    <!-- id="menuToggle" → JS sẽ tìm nút này bằng getElementById() -->
    <button class="menu-toggle" id="menuToggle" aria-label="Mở menu">
        <span></span><span></span><span></span> <!-- 3 gạch ngang ☰ -->
    </button>

    <!-- Menu điều hướng — id="navMenu" để JS toggle class 'active' -->
    <nav id="navMenu">
        <!-- Mỗi <a> là 1 link trong menu -->
        <a href="<?= $base_url ?>">Trang chủ</a>
        <!-- Bấm → đến trang tra cứu booking bằng số điện thoại -->
        <a href="<?= $base_url ?>pages/user/search_booking.php">Tra cứu booking</a>
        <!-- href="#lien-he" = cuộn xuống phần footer có id="lien-he" -->
        <a href="#lien-he">Liên hệ</a>

        <?php if (is_logged_in()): // Nếu đã đăng nhập → hiện link "Xin chào, [Tên]" dẫn đến trang tài khoản ?>
            <!-- Bấm vào → chuyển sang trang thông tin tài khoản (account.php) -->
            <a href="<?= $base_url ?>pages/user/account.php">Xin chào, <?= e(current_user()['name']) ?></a>

        <?php else: // Nếu chưa đăng nhập → hiện link đăng nhập ?>
            <a href="<?= $base_url ?>pages/auth/login.php">Đăng nhập</a>
        <?php endif; // Kết thúc kiểm tra đăng nhập ?>
    </nav>
</header>
