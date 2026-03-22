<?php
/**
 * ============================================================
 * includes/admin_header.php — Layout đầu trang admin
 * ============================================================
 * Design: Glass-morphism sidebar (thanh bên mờ kính) + Editorial content area
 *
 * Chức năng:
 * - Kiểm tra quyền admin (require_admin) trước khi hiển thị
 * - Tạo sidebar menu điều hướng (navigation sidebar)
 * - Tạo topbar (thanh trên cùng) với tiêu đề + đồng hồ + tên user
 * - Hiển thị flash message (thông báo 1 lần) nếu có
 *
 * Biến cần đặt trước khi include:
 *   $page_title  = 'Bảng điều khiển'; → tiêu đề hiện trên topbar
 *   $active_page = 'dashboard';       → menu nào được highlight
 */

// Nạp auth.php nếu chưa có (chứa hàm require_admin, is_admin, v.v.)
if (!function_exists('require_admin')) require_once __DIR__ . '/auth.php';

// require_admin() = bắt buộc phải đăng nhập VÀ có quyền admin
// Nếu không → tự động redirect (chuyển hướng) về trang chủ
require_admin();

// ?? = null coalescing operator (toán tử kiểm tra null)
// Nếu biến chưa được đặt → dùng giá trị mặc định
$page_title  = $page_title  ?? 'Quản trị';
$active_page = $active_page ?? 'dashboard';
$base_url    = defined('BASE_URL') ? BASE_URL : '/homestay/';

// Query đếm số đơn chờ duyệt → hiển thị badge (huy hiệu số) trên menu "Đặt phòng"
$_pending_count = 0;
$_pq = db()->query("SELECT COUNT(*) AS c FROM bookings WHERE trang_thai='cho_xac_nhan'");
if ($_pq) $_pending_count = (int)$_pq->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <!-- charset UTF-8 = hỗ trợ tiếng Việt và emoji -->
    <meta charset="UTF-8">
    <!-- viewport = responsive (tự co giãn theo kích thước màn hình) -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> — Admin SKIBIDI TOLET</title>
    <!-- Google Fonts: Outfit (body text) + Playfair Display (tiêu đề) — giống trang chủ -->
    <!-- preconnect = kết nối trước đến server font → tải nhanh hơn -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- CSS riêng cho trang admin -->
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/admin.css">
</head>
<body class="admin-body">

<!-- ===== OVERLAY (lớp phủ mờ) — bấm vào để đóng sidebar trên mobile ===== -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ===== SIDEBAR (thanh menu bên trái) ===== -->
<aside class="admin-sidebar" id="adminSidebar">
    <!-- Logo + tên thương hiệu -->
    <div class="sidebar-header">
        <!-- Bấm logo → về trang chủ public -->
        <a href="<?= $base_url ?>" class="sidebar-brand">
            <div class="brand-icon">S</div>
            <div class="brand-text">
                <strong>SKIBIDI TOLET</strong>
                <small>Admin Panel</small>
            </div>
        </a>
    </div>

    <!-- ===== MENU ĐIỀU HƯỚNG (Navigation Menu) ===== -->
    <nav class="sidebar-nav">
        <!-- nav-section = tiêu đề nhóm menu -->
        <div class="nav-section">Tổng quan</div>
        <!-- $active_page === 'dashboard' ? 'active' : '' → thêm class 'active' nếu đang ở trang này -->
        <!-- Ternary operator (toán tử ba ngôi): điều_kiện ? giá_trị_đúng : giá_trị_sai -->
        <a href="<?= $base_url ?>pages/admin/dashboard.php" class="nav-item <?= $active_page === 'dashboard' ? 'active' : '' ?>">
            <span class="nav-icon">
                <!-- SVG = đồ họa vector (hình vẽ bằng code, không bị vỡ khi phóng to) -->
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
            </span>
            Bảng điều khiển
        </a>

        <div class="nav-section">Quản lý</div>
        <!-- Link đến trang quản lý phòng -->
        <a href="<?= $base_url ?>pages/admin/rooms.php" class="nav-item <?= $active_page === 'rooms' ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            </span>
            Phòng
        </a>
        <!-- Link đến trang quản lý đặt phòng -->
        <a href="<?= $base_url ?>pages/admin/bookings.php" class="nav-item <?= $active_page === 'bookings' ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M9 14l2 2 4-4"/></svg>
            </span>
            Đặt phòng
            <!-- Badge (huy hiệu số) hiện số đơn chờ duyệt, chỉ hiện khi > 0 -->
            <?php if ($_pending_count > 0): ?>
                <span class="nav-badge"><?= $_pending_count ?></span>
            <?php endif; ?>
        </a>
        <!-- Link đến trang quản lý người dùng -->
        <a href="<?= $base_url ?>pages/admin/users.php" class="nav-item <?= $active_page === 'users' ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
            </span>
            Người dùng
        </a>

        <div class="nav-section">Hệ thống</div>
        <!-- Link đến trang cài đặt -->
        <a href="<?= $base_url ?>pages/admin/settings.php" class="nav-item <?= $active_page === 'settings' ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 01-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
            </span>
            Cài đặt
        </a>
        <!-- Link quay về trang chủ public (trang khách hàng thấy) -->
        <a href="<?= $base_url ?>" class="nav-item">
            <span class="nav-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>
            </span>
            Xem trang chủ
        </a>
    </nav>

    <!-- ===== USER INFO (thông tin admin đang đăng nhập) — ở cuối sidebar ===== -->
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <!-- Avatar: lấy chữ cái đầu tên (mb_substr hỗ trợ tiếng Việt) -->
            <div class="user-avatar"><?= mb_substr(current_user()['name'], 0, 1, 'UTF-8') ?></div>
            <div class="user-info">
                <strong><?= e(current_user()['name']) ?></strong>
                <small>Quản trị viên</small>
            </div>
        </div>
        <!-- Nút đăng xuất (logout = đăng xuất, hủy session) -->
        <a href="<?= $base_url ?>pages/auth/logout.php" class="sidebar-logout" title="Đăng xuất">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        </a>
    </div>
</aside>

<!-- ===== MAIN CONTENT AREA (vùng nội dung chính bên phải sidebar) ===== -->
<main class="admin-main">
    <!-- TOPBAR (thanh trên cùng) — hiện tiêu đề trang + đồng hồ + tên user -->
    <header class="admin-topbar">
        <!-- Nút hamburger ☰ để toggle (bật/tắt) sidebar trên mobile -->
        <button class="topbar-menu" id="sidebarToggle">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        <div class="topbar-title">
            <!-- Tiêu đề trang hiện tại, VD: "Bảng điều khiển", "Quản lý phòng" -->
            <h1><?= e($page_title) ?></h1>
        </div>
        <div class="topbar-actions">
            <!-- Đồng hồ realtime — JS trong admin.js sẽ cập nhật mỗi giây -->
            <span class="topbar-clock" id="liveClock"></span>
            <span class="topbar-greeting">Xin chào, <strong><?= e(current_user()['name']) ?></strong></span>
        </div>
    </header>

    <!-- ===== VÙNG NỘI DUNG — các trang admin sẽ render (hiển thị) vào đây ===== -->
    <div class="admin-content">
        <?php
        // Flash message = thông báo hiện 1 lần rồi tự xóa
        // flash('error') lấy thông báo lỗi từ session (nếu có) rồi xóa
        // flash('success') lấy thông báo thành công từ session (nếu có) rồi xóa
        $_flash_err = flash('error');
        $_flash_ok  = flash('success');
        // Nếu có thông báo → hiện alert box (hộp thông báo)
        if ($_flash_err): ?><div class="alert alert-error">⚠ <?= e($_flash_err) ?></div><?php endif;
        if ($_flash_ok):  ?><div class="alert alert-success">✓ <?= e($_flash_ok) ?></div><?php endif;
        ?>
