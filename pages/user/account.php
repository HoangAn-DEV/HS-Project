<?php
/**
 * ============================================================
 * pages/user/account.php — Trang thông tin tài khoản cá nhân
 * ============================================================
 * Hiển thị thông tin chi tiết của user đang đăng nhập:
 * - Họ tên, email, số điện thoại, địa chỉ
 * - Vai trò (Quản trị viên / Khách hàng)
 * - Ngày tạo tài khoản
 *
 * Yêu cầu: phải đăng nhập (require_login)
 */

require_once __DIR__ . '/../../includes/auth.php';

// Bắt buộc đăng nhập — nếu chưa login → chuyển về trang đăng nhập
require_login();

// Lấy ID user từ session
$user_id = (int) current_user()['id'];

// Query thông tin đầy đủ từ bảng users
$stmt = db()->prepare("SELECT name, email, phone, address, role, created_at FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Nếu không tìm thấy user → về trang chủ
if (!$user) {
    header('Location: ' . (defined('BASE_URL') ? BASE_URL : '/homestay/'));
    exit;
}

// Render header + nav
$page_title = 'Thông tin tài khoản';
require_once __DIR__ . '/../../includes/header.php';
?>

<!-- ===== NỘI DUNG TRANG THÔNG TIN TÀI KHOẢN ===== -->
<section class="account-page">
    <div class="account-container">

        <!-- Phần đầu: avatar + tên + vai trò -->
        <div class="account-header">
            <!-- Avatar tròn — hiện chữ cái đầu tên (mb_substr hỗ trợ tiếng Việt) -->
            <div class="account-avatar">
                <?= mb_substr($user['name'], 0, 1, 'UTF-8') ?>
            </div>
            <h1 class="account-name"><?= e($user['name']) ?></h1>
            <!-- Hiện vai trò: admin → "Quản trị viên", user → "Khách hàng" -->
            <span class="account-role"><?= $user['role'] === 'admin' ? 'Quản trị viên' : 'Khách hàng' ?></span>
        </div>

        <!-- Phần thân: các dòng thông tin chi tiết -->
        <div class="account-body">

            <!-- Dòng email -->
            <div class="account-row">
                <div class="account-label">
                    <!-- SVG icon thư -->
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    Email
                </div>
                <div class="account-value"><?= e($user['email'] ?? 'Chưa cập nhật') ?></div>
            </div>

            <!-- Dòng số điện thoại -->
            <div class="account-row">
                <div class="account-label">
                    <!-- SVG icon điện thoại -->
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg>
                    Số điện thoại
                </div>
                <div class="account-value"><?= e($user['phone'] ?? 'Chưa cập nhật') ?></div>
            </div>

            <!-- Dòng địa chỉ -->
            <div class="account-row">
                <div class="account-label">
                    <!-- SVG icon vị trí -->
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    Địa chỉ
                </div>
                <div class="account-value"><?= e($user['address'] ?? 'Chưa cập nhật') ?></div>
            </div>

            <!-- Dòng ngày tạo tài khoản -->
            <div class="account-row">
                <div class="account-label">
                    <!-- SVG icon lịch -->
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Ngày tham gia
                </div>
                <!-- date() format: d/m/Y = ngày/tháng/năm, strtotime chuyển text→timestamp -->
                <div class="account-value"><?= date('d/m/Y', strtotime($user['created_at'])) ?></div>
            </div>
        </div>

        <!-- Phần cuối: các nút hành động -->
        <div class="account-actions">
            <a href="<?= $base_url ?>pages/user/my_bookings.php" class="account-btn">
                <!-- SVG icon clipboard -->
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/></svg>
                Lịch sử đặt phòng
            </a>
            <?php if (is_admin()): // Nếu là admin → thêm nút vào trang quản trị ?>
            <a href="<?= $base_url ?>pages/admin/dashboard.php" class="account-btn">
                <!-- SVG icon bánh răng -->
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 01-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
                Quản trị
            </a>
            <?php endif; ?>
            <a href="<?= $base_url ?>pages/auth/logout.php" class="account-btn account-btn-logout">
                <!-- SVG icon đăng xuất -->
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Đăng xuất
            </a>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
