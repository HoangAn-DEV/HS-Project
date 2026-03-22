<?php
/**
 * ============================================================
 * pages/auth/logout.php — Đăng xuất
 * ============================================================
 * Khi user bấm "Đăng xuất" trên nav → chạy file này.
 * 
 * Cách hoạt động:
 * 1. session_start() → bắt đầu session (cần để hủy)
 * 2. require config.php → có hằng BASE_URL
 * 3. session_destroy() → xóa toàn bộ dữ liệu session
 *    (user_id, user_name, role... tất cả biến mất)
 * 4. Chuyển hướng về trang đăng nhập
 */

session_start(); // Phải start trước mới destroy được

// Nạp config.php để có hằng BASE_URL (dùng cho redirect)
require_once __DIR__ . '/../../includes/config.php';

session_destroy(); // Hủy session → user coi như chưa đăng nhập

// Chuyển về trang đăng nhập
header('Location: ' . BASE_URL . 'pages/auth/login.php');
exit;
