<?php
/**
 * ============================================================
 * pages/user/search_booking.php — Tra cứu đặt phòng bằng SĐT
 * ============================================================
 * Khách nhập SĐT → bấm "Tra cứu" → form gửi GET (?sdt=0901234567)
 * → File này query DB tìm đơn theo SĐT → hiển thị bảng kết quả
 * 
 * Ai cũng dùng được (không cần đăng nhập)
 */

require_once __DIR__ . '/../../includes/auth.php';

$base_url = BASE_URL;
$results  = null;  // null = chưa tìm kiếm (chưa nhập SĐT)
$sdt      = '';    // Số điện thoại user nhập

// ---- NẾU CÓ SĐT TRÊN URL → TÌM KIẾM ----
// !empty() = chuỗi không rỗng
if (!empty($_GET['sdt'])) {
    $sdt = trim($_GET['sdt']); // Lấy SĐT từ URL, bỏ khoảng trắng

    // Query tìm đơn theo SĐT
    // LEFT JOIN booking_slots → lấy thêm thông tin ca đã đặt
    // GROUP_CONCAT() → gộp nhiều slot thành 1 chuỗi
    // GROUP BY b.id → gộp theo từng đơn (1 đơn có nhiều slot)
    $stmt = db()->prepare(
        "SELECT b.*, GROUP_CONCAT(
            CONCAT(s.loai_ca, '-', s.col_ngay) SEPARATOR ','
        ) AS slots
        FROM bookings b
        LEFT JOIN booking_slots s ON s.booking_id = b.id
        WHERE b.so_dt = ?
        GROUP BY b.id
        ORDER BY b.id DESC"
    );
    $stmt->bind_param('s', $sdt); // Bind SĐT vào dấu ?
    $stmt->execute();
    $results = $stmt->get_result(); // Lưu kết quả để dùng bên dưới
}

// Render header (nav bar)
$page_title = 'Tra cứu booking';
include __DIR__ . '/../../includes/header.php';
?>

<!-- ===== FORM TRA CỨU ===== -->
<div class="search-page">
    <div class="search-card">
        <h2>🔎 Tra cứu đặt phòng</h2>
        <!-- method="GET" → SĐT hiện trên URL (?sdt=xxx) để có thể chia sẻ link -->
        <!-- action="" (rỗng) = gửi về chính trang này -->
        <form action="" method="GET">
            <!-- value=sdt giữ lại SĐT đã nhập sau khi search -->
            <input type="text" name="sdt" placeholder="Nhập số điện thoại"
                   value="<?= e($sdt) ?>" required>
            <button type="submit" class="btn btn-primary" style="width:100%">Tra cứu</button>
        </form>
    </div>
</div>

<?php
// Chỉ hiện kết quả nếu đã tìm kiếm ($results !== null)
if ($results !== null):
?>
<div class="container" style="margin-top:20px; margin-bottom:60px;">
    <?php if ($results->num_rows > 0): // Tìm thấy đơn ?>
        <h3 class="section-title" style="margin-bottom:16px;">
            Tìm thấy <?= $results->num_rows ?> đơn đặt phòng
        </h3>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Mã</th><th>Phòng</th><th>Tên khách</th>
                        <th>Số khách</th><th>Tổng tiền</th>
                        <th>Trạng thái</th><th>Ngày đặt</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $results->fetch_assoc()): ?>
                    <tr>
                        <td>#<?= $row['id'] ?></td>
                        <td><?= e($row['room_name']) ?></td>
                        <td><?= e($row['ho_ten']) ?></td>
                        <td><?= $row['so_khach'] ?></td>
                        <td style="font-weight:700;color:var(--accent)">
                            <?= vnd($row['tong_cuoi']) ?>
                        </td>
                        <td>
                            <?php
                            // Hiển thị badge tùy trạng thái
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
    <?php else: // Không tìm thấy ?>
        <div class="card" style="text-align:center; max-width:500px; margin:0 auto;">
            <div class="result-header" style="border:none; padding:0; margin:0;">
                <span class="icon">🔍</span>
                <h1 style="font-size:20px;">Không tìm thấy booking</h1>
                <p>SĐT "<?= e($sdt) ?>" chưa có đơn đặt phòng nào.</p>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
