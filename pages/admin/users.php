<?php
/**
 * pages/admin/users.php — Trang quản lý người dùng (Admin)
 *
 * Chức năng:
 * - Hiển thị danh sách tất cả tài khoản trong hệ thống
 * - Cho phép admin cấp quyền / hạ quyền / xóa tài khoản
 * - Không cho phép admin thao tác lên chính tài khoản mình
 */

// Kiểm tra đăng nhập admin & lấy kết nối database
require_once __DIR__ . '/../../includes/auth.php';
$conn = db();

// ============================================================
// XỬ LÝ HÀNH ĐỘNG (khi URL có ?action=...&id=...)
// VD: users.php?action=make_admin&id=5
// ============================================================
if (isset($_GET['action'], $_GET['id'])) {

    // Lấy ID user cần thao tác, ép kiểu int để chống injection
    $uid = (int)$_GET['id'];

    // Không cho phép admin tự thay đổi chính mình (tránh tự xóa/hạ quyền)
    if ($uid === (int)$_SESSION['user_id']) {
        $_SESSION['error'] = 'Không thể thay đổi chính mình.';
    } else {
        // Dùng match() (PHP 8+) để xử lý theo từng loại action
        match($_GET['action']) {
            // Cấp quyền admin: đổi role từ 'user' → 'admin'
            'make_admin' => (function() use ($conn, $uid) { $s=$conn->prepare("UPDATE users SET role='admin' WHERE id=?"); $s->bind_param('i',$uid); $s->execute(); $s->close(); $_SESSION['success']='Đã cấp quyền Admin.'; })(),
            // Hạ quyền: đổi role từ 'admin' → 'user'
            'make_user'  => (function() use ($conn, $uid) { $s=$conn->prepare("UPDATE users SET role='user' WHERE id=?"); $s->bind_param('i',$uid); $s->execute(); $s->close(); $_SESSION['success']='Đã hạ quyền User.'; })(),
            // Xóa tài khoản khỏi database
            'delete'     => (function() use ($conn, $uid) { $s=$conn->prepare("DELETE FROM users WHERE id=?"); $s->bind_param('i',$uid); $s->execute(); $s->close(); $_SESSION['success']='Đã xóa người dùng.'; })(),
            // Action không hợp lệ → báo lỗi
            default => $_SESSION['error'] = 'Hành động không hợp lệ.',
        };
    }

    // chuyển hướng về trang users (PRG pattern - tránh thực thi lại action khi refresh)
    header('Location: users.php'); exit;
}

// ============================================================
// LẤY DỮ LIỆU TỪ DATABASE
// ============================================================

// Lấy danh sách tất cả user, sắp xếp theo ID tăng dần
$users = $conn->query("SELECT * FROM users ORDER BY id ASC");

// Đếm tổng số tài khoản và số admin để hiển thị thống kê
$total_users  = $conn->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'];
$total_admins = $conn->query("SELECT COUNT(*) c FROM users WHERE role='admin'")->fetch_assoc()['c'];

// Đặt tiêu đề trang và menu active cho sidebar admin
$page_title = 'Quản lý người dùng'; $active_page = 'users';

// Include header admin (sidebar, navbar, CSS, JS chung)
include __DIR__ . '/../../includes/admin_header.php';
?>

<!-- ============================================================ -->
<!-- PHẦN THỐNG KÊ: 3 ô hiển thị tổng tài khoản / admin / user  -->
<!-- ============================================================ -->
<div class="stat-grid" style="margin-bottom:24px;">
    <!-- Ô 1: Tổng số tài khoản -->
    <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-label">Tổng tài khoản</div>
        <div class="stat-value"><?= $total_users ?></div>
    </div>
    <!-- Ô 2: Số quản trị viên (admin) -->
    <div class="stat-card">
        <div class="stat-icon green-icon">🛡️</div>
        <div class="stat-label">Quản trị viên</div>
        <div class="stat-value"><?= $total_admins ?></div>
    </div>
    <!-- Ô 3: Số người dùng thường = tổng - admin -->
    <div class="stat-card">
        <div class="stat-icon blue-icon">👤</div>
        <div class="stat-label">Người dùng</div>
        <div class="stat-value"><?= $total_users - $total_admins ?></div>
    </div>
</div>

<!-- ============================================================ -->
<!-- BẢNG DANH SÁCH TÀI KHOẢN                                    -->
<!-- ============================================================ -->
<div class="admin-table-wrap">
    <div class="admin-table-header">
        <h3>Danh sách tài khoản</h3>
    </div>
    <div class="table-scroll">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Họ tên</th>
                    <th>Email</th>
                    <th>Số điện thoại</th>
                    <th>Vai trò</th>
                    <th>Ngày tạo</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <!-- Lặp qua từng user trong kết quả query -->
            <?php while ($u = $users->fetch_assoc()): ?>
                <tr>
                    <td class="td-bold">#<?= $u['id'] ?></td>
                    <td>
                        <!-- Avatar: lấy chữ cái đầu tên, admin=vàng gold, user=xám -->
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:32px;height:32px;border-radius:8px;background:<?= $u['role']==='admin' ? 'linear-gradient(135deg,#c8a96e,#a88a4e)' : 'linear-gradient(135deg,#e5e7eb,#d1d5db)' ?>;display:flex;align-items:center;justify-content:center;color:<?= $u['role']==='admin' ? '#fff' : '#6b7280' ?>;font-size:0.75rem;font-weight:700;flex-shrink:0;">
                                <?= mb_substr($u['name'], 0, 1, 'UTF-8') ?>
                            </div>
                            <!-- Tên user, dùng e() để escape HTML chống XSS -->
                            <span class="td-bold"><?= e($u['name']) ?></span>
                        </div>
                    </td>
                    <td class="td-muted"><?= e($u['email']) ?></td>
                    <!-- Nếu không có SĐT thì hiển thị dấu gạch ngang -->
                    <td class="td-muted"><?= e($u['phone'] ?? '—') ?></td>
                    <td>
                        <!-- Badge vai trò: Admin (xanh) hoặc User (vàng) -->
                        <?= $u['role']==='admin'
                            ? '<span class="badge badge-paid">🛡️ Admin</span>'
                            : '<span class="badge badge-pending">👤 User</span>' ?>
                    </td>
                    <!-- Định dạng ngày tạo: dd/mm/yyyy -->
                    <td class="td-muted"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <!-- Chỉ hiện nút thao tác nếu KHÔNG phải tài khoản mình -->
                        <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                        <div class="act-group">
                            <?php if ($u['role']==='user'): ?>
                                <!-- User thường → hiện nút "Lên Admin" -->
                                <a href="users.php?action=make_admin&id=<?= $u['id'] ?>" class="act-btn green" data-confirm="Cấp quyền Admin cho '<?= e($u['name']) ?>'?">↑ Lên Admin</a>
                            <?php else: ?>
                                <!-- Admin → hiện nút "Hạ User" -->
                                <a href="users.php?action=make_user&id=<?= $u['id'] ?>" class="act-btn orange" data-confirm="Hạ quyền '<?= e($u['name']) ?>' xuống User?">↓ Hạ User</a>
                            <?php endif; ?>
                            <!-- Nút xóa tài khoản (có confirm trước khi xóa) -->
                            <a href="users.php?action=delete&id=<?= $u['id'] ?>" class="act-btn red" data-confirm="Xóa tài khoản '<?= e($u['name']) ?>'? Hành động này không thể hoàn tác.">🗑 Xóa</a>
                        </div>
                        <?php else: ?>
                            <!-- Nếu là tài khoản mình → chỉ hiện badge, không cho thao tác -->
                            <span class="badge" style="background:var(--accent-soft);color:var(--accent-text);">Tài khoản của bạn</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Include footer admin (đóng layout, JS chung)
include __DIR__ . '/../../includes/admin_footer.php';
?>
