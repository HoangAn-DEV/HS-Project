<?php
/**
 * pages/admin/dashboard.php — Trang tổng quan admin (Dashboard = Bảng điều khiển)
 *
 * Chức năng:
 * - Hiển thị banner chào mừng + ngày hiện tại
 * - Hiển thị các thẻ thống kê (stat cards): tổng đơn, chờ duyệt, doanh thu, phòng, thành viên
 * - Quick actions (hành động nhanh): duyệt đơn chờ, thêm phòng, cài đặt
 * - Bảng 10 đơn đặt phòng mới nhất
 */

// require_once = nhúng file 1 lần duy nhất (tránh nhúng trùng lặp)
// auth.php = file xác thực (authentication = xác thực đăng nhập), kiểm tra admin đã đăng nhập chưa
require_once __DIR__ . '/../../includes/auth.php';

// db() = hàm trả về kết nối database (database connection = kết nối cơ sở dữ liệu)
$conn = db();

// ============================================================
// QUERY THỐNG KÊ (query = truy vấn dữ liệu từ database)
// ============================================================
$s = []; // Mảng (array = mảng) lưu các số liệu thống kê

// COUNT(*) = đếm tổng số dòng (row = dòng/bản ghi) trong bảng bookings
$s['bookings'] = $conn->query("SELECT COUNT(*) c FROM bookings")->fetch_assoc()['c'];

// Đếm số đơn có trạng thái 'cho_xac_nhan' (WHERE = điều kiện lọc)
$s['pending']  = $conn->query("SELECT COUNT(*) c FROM bookings WHERE trang_thai='cho_xac_nhan'")->fetch_assoc()['c'];

// COALESCE = nếu kết quả NULL thì trả về 0
// SUM(tong_cuoi) = tính tổng (sum = tổng) cột tong_cuoi của các đơn đã thanh toán
$s['revenue']  = $conn->query("SELECT COALESCE(SUM(tong_cuoi),0) s FROM bookings WHERE trang_thai='da_thanh_toan'")->fetch_assoc()['s'];

// Đếm phòng đang hoạt động (is_active=1 nghĩa là đang mở)
$s['rooms']    = $conn->query("SELECT COUNT(*) c FROM rooms WHERE is_active=1")->fetch_assoc()['c'];

// Đếm tổng số tài khoản người dùng
$s['users']    = $conn->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'];

// ORDER BY id DESC = sắp xếp giảm dần theo ID (đơn mới nhất lên đầu)
// LIMIT 10 = chỉ lấy 10 dòng đầu tiên
$recent = $conn->query("SELECT * FROM bookings ORDER BY id DESC LIMIT 10");

// Đặt tiêu đề trang (page title) và trang đang active trên sidebar menu
$page_title = 'Bảng điều khiển'; $active_page = 'dashboard';

// include = nhúng file header admin (chứa sidebar, navbar, CSS, JS chung)
include __DIR__ . '/../../includes/admin_header.php';

// ============================================================
// XỬ LÝ NGÀY TIẾNG VIỆT
// ============================================================
// date('w') trả về 0-6 (0=Chủ nhật, 1=Thứ hai,...6=Thứ bảy)
$days_vi = ['Chủ nhật','Thứ hai','Thứ ba','Thứ tư','Thứ năm','Thứ sáu','Thứ bảy'];

// Mảng tên tháng tiếng Việt, phần tử [0] để trống vì tháng bắt đầu từ 1
$months_vi = ['','Tháng 1','Tháng 2','Tháng 3','Tháng 4','Tháng 5','Tháng 6','Tháng 7','Tháng 8','Tháng 9','Tháng 10','Tháng 11','Tháng 12'];

$today_day = $days_vi[date('w')];       // Tên thứ trong tuần (VD: "Thứ hai")
$today_date = date('d');                  // Ngày trong tháng (VD: "19")
$today_month = $months_vi[(int)date('m')]; // Tên tháng (VD: "Tháng 3")
$today_year = date('Y');                  // Năm (VD: "2026")
?>

<!-- ============================================================ -->
<!-- WELCOME BANNER (banner = biểu ngữ chào mừng)                -->
<!-- ============================================================ -->
<div class="welcome-banner">
    <!-- current_user() = lấy thông tin user đang đăng nhập -->
    <!-- e() = escape HTML (mã hóa ký tự đặc biệt để chống XSS = tấn công chèn mã độc) -->
    <h2>Xin chào, <?= e(current_user()['name']) ?></h2>
    <p>Đây là tổng quan hoạt động homestay của bạn hôm nay.</p>
    <div class="welcome-date">
        <div class="date-day"><?= $today_date ?></div>
        <div class="date-info"><?= $today_day ?>, <?= $today_month ?> <?= $today_year ?></div>
    </div>
</div>

<!-- ============================================================ -->
<!-- STAT CARDS (stat = thống kê, card = thẻ hiển thị)            -->
<!-- 5 thẻ: Tổng đơn | Chờ duyệt | Doanh thu | Phòng | Thành viên -->
<!-- ============================================================ -->
<div class="stat-grid">
    <!-- Thẻ 1: Tổng số đơn đặt phòng -->
    <div class="stat-card">
        <div class="stat-icon">📊</div>
        <div class="stat-label">Tổng đơn đặt</div>
        <div class="stat-value"><?= $s['bookings'] ?></div>
        <div class="stat-sub">Tất cả đơn trong hệ thống</div>
    </div>
    <!-- Thẻ 2: Số đơn đang chờ duyệt (pending = đang chờ xử lý) -->
    <div class="stat-card">
        <div class="stat-icon orange-icon">⏳</div>
        <div class="stat-label">Chờ duyệt</div>
        <div class="stat-value orange"><?= $s['pending'] ?></div>
        <div class="stat-sub">Cần xử lý ngay</div>
    </div>
    <!-- Thẻ 3: Tổng doanh thu (revenue = doanh thu) từ các đơn đã thanh toán -->
    <!-- vnd() = hàm format số tiền theo định dạng VNĐ (VD: 1.500.000đ) -->
    <div class="stat-card">
        <div class="stat-icon green-icon">💰</div>
        <div class="stat-label">Doanh thu</div>
        <div class="stat-value green"><?= vnd($s['revenue']) ?></div>
        <div class="stat-sub">Đã thanh toán</div>
    </div>
    <!-- Thẻ 4: Số phòng đang hoạt động (active = đang hoạt động) -->
    <div class="stat-card">
        <div class="stat-icon blue-icon">🏠</div>
        <div class="stat-label">Phòng hoạt động</div>
        <div class="stat-value"><?= $s['rooms'] ?></div>
        <div class="stat-sub">Đang mở đặt</div>
    </div>
    <!-- Thẻ 5: Tổng số thành viên đã đăng ký -->
    <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-label">Thành viên</div>
        <div class="stat-value"><?= $s['users'] ?></div>
        <div class="stat-sub">Tài khoản đã đăng ký</div>
    </div>
</div>

<!-- ============================================================ -->
<!-- QUICK ACTIONS (hành động nhanh - các nút tắt cho admin)      -->
<!-- ============================================================ -->
<div class="quick-actions">
    <!-- Chỉ hiện nút "Duyệt đơn chờ" nếu có đơn pending (chờ xử lý) -->
    <?php if ($s['pending'] > 0): ?>
    <a href="<?= BASE_URL ?>pages/admin/bookings.php?status=cho_xac_nhan" class="quick-action-btn">
        <span class="qa-icon amber">⚡</span>
        Duyệt <?= $s['pending'] ?> đơn chờ
    </a>
    <?php endif; ?>
    <!-- Nút tắt: đi đến trang quản lý phòng -->
    <a href="<?= BASE_URL ?>pages/admin/rooms.php" class="quick-action-btn">
        <span class="qa-icon emerald">+</span>
        Thêm phòng mới
    </a>
    <!-- Nút tắt: đi đến trang cài đặt hệ thống -->
    <a href="<?= BASE_URL ?>pages/admin/settings.php" class="quick-action-btn">
        <span class="qa-icon sky">⚙</span>
        Cài đặt hệ thống
    </a>
</div>

<!-- ============================================================ -->
<!-- BẢNG ĐƠN ĐẶT PHÒNG GẦN ĐÂY (10 đơn mới nhất)              -->
<!-- ============================================================ -->
<div class="admin-table-wrap">
    <div class="admin-table-header">
        <h3>Đơn đặt phòng gần đây</h3>
        <!-- Link "Xem tất cả" dẫn đến trang danh sách đầy đủ -->
        <a href="<?= BASE_URL ?>pages/admin/bookings.php" class="act-btn blue">Xem tất cả →</a>
    </div>
    <div class="table-scroll">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Mã đơn</th>
                    <th>Phòng</th>
                    <th>Khách hàng</th>
                    <th>Liên hệ</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái</th>
                    <th>Thời gian</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <!-- num_rows = số dòng kết quả. Nếu = 0 nghĩa là chưa có đơn nào -->
            <?php if ($recent->num_rows === 0): ?>
                <tr><td colspan="8">
                    <!-- Empty state = trạng thái trống, hiển thị khi không có dữ liệu -->
                    <div class="empty-state">
                        <div class="empty-icon">📋</div>
                        <h3>Chưa có đơn nào</h3>
                        <p>Đơn đặt phòng sẽ xuất hiện tại đây</p>
                    </div>
                </td></tr>
            <?php endif; ?>

            <!-- fetch_assoc() = lấy từng dòng kết quả dưới dạng mảng liên kết (associative array) -->
            <!-- Vòng lặp while: lặp qua từng đơn đặt phòng cho đến khi hết -->
            <?php while ($r = $recent->fetch_assoc()): ?>
                <tr>
                    <td class="td-bold">#<?= $r['id'] ?></td>
                    <td><?= e($r['room_name']) ?></td>
                    <td class="td-bold"><?= e($r['ho_ten']) ?></td>
                    <td class="td-muted"><?= e($r['so_dt']) ?></td>
                    <td class="td-accent"><?= vnd($r['tong_cuoi']) ?></td>
                    <td>
                        <!-- match() (PHP 8+) = so khớp giá trị, tương tự switch nhưng gọn hơn -->
                        <!-- Hiển thị badge (nhãn) theo trạng thái đơn -->
                        <?php match($r['trang_thai']) {
                            'cho_xac_nhan'  => print '<span class="badge badge-pending">⏳ Chờ duyệt</span>',
                            'da_thanh_toan' => print '<span class="badge badge-paid">✓ Đã thanh toán</span>',
                            'da_huy'        => print '<span class="badge badge-canceled">✕ Đã hủy</span>',
                            default         => print '<span class="badge">' . e($r['trang_thai']) . '</span>',
                        }; ?>
                    </td>
                    <!-- date() = định dạng ngày giờ. 'd/m H:i' = ngày/tháng giờ:phút -->
                    <!-- strtotime() = chuyển chuỗi ngày thành timestamp (mốc thời gian dạng số) -->
                    <td class="td-muted"><?= date('d/m H:i', strtotime($r['created_at'])) ?></td>
                    <td>
                        <!-- Các nút hành động cho từng đơn -->
                        <div class="act-group">
                            <!-- Nút "Duyệt": chỉ hiện nếu đơn đang chờ xác nhận -->
                            <?php if ($r['trang_thai'] === 'cho_xac_nhan'): ?>
                                <a href="<?= BASE_URL ?>api/booking_action.php?action=approve&id=<?= $r['id'] ?>" class="act-btn green">✓ Duyệt</a>
                            <?php endif; ?>
                            <!-- Nút "Hủy": hiện cho tất cả đơn chưa bị hủy -->
                            <?php if ($r['trang_thai'] !== 'da_huy'): ?>
                                <a href="<?= BASE_URL ?>api/booking_action.php?action=cancel&id=<?= $r['id'] ?>" class="act-btn orange">Hủy</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Include footer admin (đóng layout, chứa JS chung)
include __DIR__ . '/../../includes/admin_footer.php';
?>
