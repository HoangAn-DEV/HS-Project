<?php
/**
 * ============================================================
 * pages/admin/bookings.php — Trang quản lý đơn đặt phòng (Admin)
 * ============================================================
 *
 * Chức năng:
 * - Hiển thị danh sách tất cả đơn đặt phòng
 * - Lọc (filter) theo trạng thái: Chờ duyệt / Đã thanh toán / Đã hủy
 * - Cho phép admin: Duyệt (approve) / Hủy (cancel) / Xóa (delete) đơn
 * - Hiện thông tin chi tiết: khách, phòng, ca đặt, tổng tiền
 */

// Nạp auth.php → kiểm tra đăng nhập admin + kết nối DB + hàm tiện ích
require_once __DIR__ . '/../../includes/auth.php';
$conn = db(); // db() = lấy kết nối database (connection)

// ============================================================
// LỌC THEO TRẠNG THÁI (FILTER)
// URL dạng: bookings.php?status=cho_xac_nhan
// ============================================================
$filter = $_GET['status'] ?? ''; // Lấy giá trị filter từ URL, mặc định rỗng (= tất cả)

// Khởi tạo biến cho câu WHERE trong SQL
$where = '';       // Chuỗi WHERE (rỗng = không lọc)
$params = [];      // Mảng tham số cho prepared statement
$types = '';       // Kiểu tham số ('s' = string)

// in_array() = kiểm tra giá trị có nằm trong mảng hợp lệ không
if (in_array($filter, ['cho_xac_nhan','da_thanh_toan','da_huy'])) {
    $where = "WHERE b.trang_thai = ?"; // Thêm điều kiện lọc
    $params = [$filter];               // Giá trị cần bind
    $types = 's';                      // 's' = string
}

// ============================================================
// QUERY LẤY DANH SÁCH ĐƠN ĐẶT PHÒNG
// ============================================================
// LEFT JOIN = nối bảng bookings với booking_slots (lấy thông tin ca đã đặt)
// GROUP_CONCAT() = gộp nhiều slot thành 1 chuỗi, VD: "sang T3, chieu T5, dem T7"
// DISTINCT = loại bỏ trùng lặp
// GROUP BY b.id = gộp kết quả theo từng đơn (1 đơn có thể có nhiều slot)
// ORDER BY b.id DESC = đơn mới nhất lên đầu (DESC = descending = giảm dần)
$sql = "SELECT b.*,
    GROUP_CONCAT(DISTINCT CONCAT(s.loai_ca,' T',s.col_ngay) SEPARATOR ', ') AS slot_info
    FROM bookings b LEFT JOIN booking_slots s ON s.booking_id = b.id
    $where GROUP BY b.id ORDER BY b.id DESC";

// Nếu có filter → dùng prepared statement (an toàn hơn)
// Nếu không có filter → dùng query thường (đơn giản hơn)
if ($params) {
    $stmt = $conn->prepare($sql);
    // ...$params = spread operator (trải mảng thành các tham số riêng lẻ)
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result(); // get_result() = lấy kết quả query
} else {
    $result = $conn->query($sql); // query() = chạy SQL trực tiếp
}

// ============================================================
// ĐẾM SỐ ĐƠN THEO TỪNG TRẠNG THÁI (hiện trên filter bar)
// ============================================================
$count_all     = $conn->query("SELECT COUNT(*) c FROM bookings")->fetch_assoc()['c'];
$count_pending = $conn->query("SELECT COUNT(*) c FROM bookings WHERE trang_thai='cho_xac_nhan'")->fetch_assoc()['c'];
$count_paid    = $conn->query("SELECT COUNT(*) c FROM bookings WHERE trang_thai='da_thanh_toan'")->fetch_assoc()['c'];
$count_cancel  = $conn->query("SELECT COUNT(*) c FROM bookings WHERE trang_thai='da_huy'")->fetch_assoc()['c'];

// Đặt tiêu đề trang + menu active trên sidebar
$page_title = 'Quản lý đặt phòng'; $active_page = 'bookings';

// Include layout admin (sidebar + topbar + flash message)
include __DIR__ . '/../../includes/admin_header.php';
?>

<!-- ============================================================ -->
<!-- FILTER BAR (thanh lọc trạng thái)                            -->
<!-- Bấm vào nút → thêm ?status=xxx vào URL → PHP lọc theo đó    -->
<!-- ============================================================ -->
<div class="filter-bar">
    <!-- Nút "Tất cả" — active khi không có filter -->
    <a href="bookings.php" class="filter-btn <?= !$filter ? 'active' : '' ?>">
        Tất cả <span style="opacity:0.7;margin-left:4px"><?= $count_all ?></span>
    </a>
    <!-- Nút "Chờ duyệt" — active-warn = highlight màu cam -->
    <a href="bookings.php?status=cho_xac_nhan" class="filter-btn <?= $filter==='cho_xac_nhan' ? 'active-warn' : '' ?>">
        ⏳ Chờ duyệt <span style="opacity:0.7;margin-left:4px"><?= $count_pending ?></span>
    </a>
    <!-- Nút "Đã thanh toán" — active-ok = highlight màu xanh -->
    <a href="bookings.php?status=da_thanh_toan" class="filter-btn <?= $filter==='da_thanh_toan' ? 'active-ok' : '' ?>">
        ✓ Đã thanh toán <span style="opacity:0.7;margin-left:4px"><?= $count_paid ?></span>
    </a>
    <!-- Nút "Đã hủy" — active-err = highlight màu đỏ -->
    <a href="bookings.php?status=da_huy" class="filter-btn <?= $filter==='da_huy' ? 'active-err' : '' ?>">
        ✕ Đã hủy <span style="opacity:0.7;margin-left:4px"><?= $count_cancel ?></span>
    </a>
</div>

<!-- ============================================================ -->
<!-- BẢNG DANH SÁCH ĐƠN ĐẶT PHÒNG                               -->
<!-- ============================================================ -->
<div class="admin-table-wrap">
    <div class="admin-table-header">
        <h3>
            <!-- match() (PHP 8+) = so khớp giá trị, tương tự switch nhưng gọn hơn -->
            <!-- Hiển thị tiêu đề tùy theo filter đang chọn -->
            <?php
            match($filter) {
                'cho_xac_nhan'  => print 'Đơn chờ duyệt',
                'da_thanh_toan' => print 'Đơn đã thanh toán',
                'da_huy'        => print 'Đơn đã hủy',
                default         => print 'Tất cả đơn đặt phòng',
            };
            ?>
            <!-- num_rows = số dòng kết quả (tổng đơn tìm được) -->
            <span style="color:var(--text-muted);font-family:'DM Sans',sans-serif;font-size:0.85rem;font-weight:500;margin-left:8px">(<?= $result->num_rows ?>)</span>
        </h3>
    </div>
    <div class="table-scroll">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Mã đơn</th>
                    <th>Phòng</th>
                    <th>Khách hàng</th>
                    <th>SĐT</th>
                    <th>Số khách</th>
                    <th>Ca đặt</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <!-- Empty state: hiện khi không có đơn nào -->
            <?php if ($result->num_rows === 0): ?>
                <tr><td colspan="10">
                    <div class="empty-state">
                        <div class="empty-icon">📋</div>
                        <h3>Không tìm thấy đơn nào</h3>
                        <p>Thử thay đổi bộ lọc phía trên</p>
                    </div>
                </td></tr>
            <?php endif; ?>

            <!-- Lặp qua từng đơn đặt phòng -->
            <!-- fetch_assoc() = lấy 1 dòng kết quả dạng mảng liên kết (associative array) -->
            <?php while ($r = $result->fetch_assoc()): ?>
                <tr>
                    <td class="td-bold">#<?= $r['id'] ?></td>
                    <td><?= e($r['room_name']) ?></td>
                    <td class="td-bold"><?= e($r['ho_ten']) ?></td>
                    <td class="td-muted"><?= e($r['so_dt']) ?></td>
                    <td><?= $r['so_khach'] ?> người</td>
                    <!-- slot_info = chuỗi ca đã đặt, VD: "sang T3, chieu T5" -->
                    <td class="td-muted"><?= e($r['slot_info'] ?? '—') ?></td>
                    <!-- vnd() = format số tiền VNĐ, VD: 179000 → "179.000đ" -->
                    <td class="td-accent"><?= vnd($r['tong_cuoi']) ?></td>
                    <td>
                        <!-- Badge (nhãn) hiển thị trạng thái đơn bằng màu khác nhau -->
                        <?php match($r['trang_thai']) {
                            'cho_xac_nhan'  => print '<span class="badge badge-pending">⏳ Chờ duyệt</span>',
                            'da_thanh_toan' => print '<span class="badge badge-paid">✓ Đã TT</span>',
                            'da_huy'        => print '<span class="badge badge-canceled">✕ Đã hủy</span>',
                            default         => print '<span class="badge">' . e($r['trang_thai']) . '</span>',
                        }; ?>
                    </td>
                    <!-- date() = format ngày giờ. strtotime() = chuyển chuỗi → timestamp -->
                    <td class="td-muted"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                    <td>
                        <!-- Các nút hành động cho từng đơn -->
                        <div class="act-group">
                            <!-- Nút "Duyệt": chỉ hiện cho đơn đang chờ xác nhận -->
                            <?php if ($r['trang_thai'] === 'cho_xac_nhan'): ?>
                                <a href="<?= BASE_URL ?>api/booking_action.php?action=approve&id=<?= $r['id'] ?>" class="act-btn green">✓ Duyệt</a>
                            <?php endif; ?>
                            <!-- Nút "Hủy": hiện cho tất cả đơn chưa bị hủy -->
                            <?php if ($r['trang_thai'] !== 'da_huy'): ?>
                                <a href="<?= BASE_URL ?>api/booking_action.php?action=cancel&id=<?= $r['id'] ?>" class="act-btn orange">Hủy</a>
                            <?php endif; ?>
                            <!-- Nút "Xóa": xóa vĩnh viễn, có hộp thoại confirm trước khi xóa -->
                            <!-- data-confirm = JS sẽ bắt attribute này để hiện modal xác nhận -->
                            <a href="<?= BASE_URL ?>api/booking_action.php?action=delete&id=<?= $r['id'] ?>" class="act-btn red" data-confirm="Bạn có chắc muốn xóa đơn #<?= $r['id'] ?>? Hành động này không thể hoàn tác.">Xóa</a>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Include footer admin (đóng layout + modal confirm + JS admin)
include __DIR__ . '/../../includes/admin_footer.php';
?>