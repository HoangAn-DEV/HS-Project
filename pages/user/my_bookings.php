<?php
/**
 * ============================================================
 * pages/user/my_bookings.php — Lịch sử đặt phòng cá nhân
 * ============================================================
 * Trang này yêu cầu ĐĂNG NHẬP (require_login()).
 * Hiển thị tất cả đơn đặt phòng của user đang đăng nhập.
 * 
 * Khác với search_booking.php (ai cũng tra cứu được bằng SĐT),
 * trang này chỉ hiện đơn của CHÍNH user đã login.
 */

require_once __DIR__ . '/../../includes/auth.php';

// BẮT BUỘC đăng nhập → nếu chưa login → bị đá về trang login
require_login();

// Lấy ID user từ session (đã lưu khi đăng nhập)
$user_id = $_SESSION['user_id'];

// Query: lấy tất cả đơn của user này, mới nhất lên đầu
$stmt = db()->prepare(
    "SELECT * FROM bookings WHERE user_id = ? ORDER BY id DESC"
);
$stmt->bind_param('i', $user_id); // 'i' = integer
$stmt->execute();
$results = $stmt->get_result();

// Render header + nav
$page_title = 'Lịch sử đặt phòng';
include __DIR__ . '/../../includes/header.php';
?>

<div class="container" style="margin-top:40px; margin-bottom:60px;">
    <h2 class="section-title">Lịch sử đặt phòng của bạn</h2>

    <?php if ($results->num_rows > 0): // Có đơn ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Mã</th><th>Phòng</th><th>Số khách</th>
                        <th>Tổng tiền</th><th>Trạng thái</th><th>Ngày đặt</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $results->fetch_assoc()): ?>
                    <tr>
                        <td>#<?= $row['id'] ?></td>
                        <td><?= e($row['room_name']) ?></td>
                        <td><?= $row['so_khach'] ?></td>
                        <td style="font-weight:700;color:var(--accent)">
                            <?= vnd($row['tong_cuoi']) ?>
                        </td>
                        <td>
                            <?php
                            switch ($row['trang_thai']) {
                                case 'cho_xac_nhan':  echo '<span class="badge badge-pending">⏳ Chờ xác nhận</span>'; break;
                                case 'da_thanh_toan': echo '<span class="badge badge-paid">✅ Đã thanh toán</span>'; break;
                                case 'da_huy':        echo '<span class="badge badge-canceled">❌ Đã hủy</span>'; break;
                            }
                            ?>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: // Chưa có đơn nào ?>
        <div class="card" style="text-align:center; max-width:500px; margin:0 auto;">
            <div class="result-header" style="border:none;padding:0;margin:0;">
                <span class="icon">📋</span>
                <h1 style="font-size:20px;">Chưa có đơn đặt phòng</h1>
                <p>Hãy khám phá các phòng tuyệt vời của chúng tôi!</p>
            </div>
            <div class="btn-group" style="justify-content:center;margin-top:20px;">
                <!-- Bấm → về trang chủ để đặt phòng -->
                <a href="<?= BASE_URL ?>" class="btn btn-primary">Đặt phòng ngay</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
