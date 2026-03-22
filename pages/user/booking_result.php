<?php
/**
 * ============================================================
 * pages/user/booking_result.php — Kết quả đặt phòng
 * ============================================================
 * Sau khi api/process_booking.php xử lý xong:
 *   - Thành công → redirect đến đây với ?status=success
 *     + dữ liệu lưu trong $_SESSION['booking_result']
 *   - Lỗi → redirect đến đây với ?status=error
 *     + lỗi lưu trong $_SESSION['booking_errors']
 * 
 * Trang này đọc session → hiển thị kết quả → xóa session
 */

require_once __DIR__ . '/../../includes/auth.php';

// Lấy trạng thái từ URL: 'success' hoặc 'error'
$status   = $_GET['status'] ?? '';
$base_url = BASE_URL;

// Tên thứ trong tuần: dùng để hiển thị "Thứ 2", "Thứ 3"... thay vì số
$TEN_THU = [
    '2' => 'Thứ 2', '3' => 'Thứ 3', '4' => 'Thứ 4',
    '5' => 'Thứ 5', '6' => 'Thứ 6', '7' => 'Thứ 7', '8' => 'CN',
];

/**
 * Chuyển mảng slot code → tên thứ
 * VD: ['xn-m3', 'xn-m5'] → ['Thứ 4', 'Thứ 6']
 * Ký tự cuối của code = số thứ (3=Thứ 4, 5=Thứ 6...)
 */
function layThu(array $ds, array $tenThu): array {
    $thu = [];
    foreach ($ds as $val) {
        $so = substr(trim($val), -1);         // Lấy ký tự cuối: '3'
        $thu[] = $tenThu[$so] ?? 'Ngày ' . $so; // Tra bảng tên thứ
    }
    return $thu;
}

$page_title = $status === 'success' ? 'Xác nhận đặt phòng' : 'Lỗi đặt phòng';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> — SKIBIDI TOLET</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/main.css">
</head>
<body>

<!-- Header đơn giản (chỉ logo + 2 link) -->
<header class="site-header">
    <a href="<?= $base_url ?>" class="logo">SKIBIDI <span>TOLET</span></a>
    <nav>
        <a href="<?= $base_url ?>">Trang chủ</a>
        <a href="<?= $base_url ?>pages/user/search_booking.php">Tra cứu</a>
    </nav>
</header>

<div class="result-page">

<?php if ($status === 'error'): ?>
    <?php
    // Lấy mảng lỗi từ session rồi XÓA (chỉ hiện 1 lần)
    $errors = $_SESSION['booking_errors'] ?? ['Có lỗi không xác định.'];
    unset($_SESSION['booking_errors']);
    ?>
    <!-- ===== TRANG LỖI ===== -->
    <div class="card card-error">
        <div class="result-header">
            <span class="icon">❌</span>
            <h1>Đặt phòng chưa thành công</h1>
            <p>Vui lòng kiểm tra và thử lại.</p>
        </div>
        <!-- Liệt kê từng lỗi -->
        <ul class="error-list">
            <?php foreach ($errors as $msg): ?>
                <li><?= e($msg) ?></li>
            <?php endforeach; ?>
        </ul>
        <div class="btn-group">
            <!-- history.back() = quay lại trang trước đó (modal đặt phòng) -->
            <a href="javascript:history.back()" class="btn btn-primary">← Quay lại sửa</a>
            <a href="<?= $base_url ?>" class="btn btn-outline">🏠 Về trang chủ</a>
        </div>
    </div>

<?php elseif ($status === 'success' && isset($_SESSION['booking_result'])): ?>
    <?php
    // Lấy dữ liệu kết quả từ session rồi XÓA
    $data = $_SESSION['booking_result'];
    unset($_SESSION['booking_result']);

    $room      = $data['room'];           // Mảng thông tin phòng
    $thu_sang  = layThu($data['ca_sang'],  $TEN_THU); // Chuyển code → tên thứ
    $thu_chieu = layThu($data['ca_chieu'], $TEN_THU);
    $thu_dem   = layThu($data['ca_dem'],   $TEN_THU);
    $so_ca_sang  = count($data['ca_sang']);
    $so_ca_chieu = count($data['ca_chieu']);
    $so_ca_dem   = count($data['ca_dem']);
    ?>

    <!-- ===== TRANG THÀNH CÔNG ===== -->
    <div class="card">
        <div class="result-header">
            <span class="icon">🎉</span>
            <h1>Đặt phòng thành công!</h1>
            <p>Mã đơn: <strong>#<?= $data['booking_id'] ?></strong></p>
        </div>

        <!-- Thông tin khách -->
        <div class="info-block">
            <div class="info-block-title">Thông tin khách</div>
            <div class="info-grid">
                <div class="info-box">
                    <div class="label">Họ và tên</div>
                    <div class="value"><?= e($data['ho_ten']) ?></div>
                </div>
                <div class="info-box">
                    <div class="label">Số điện thoại</div>
                    <div class="value"><?= e($data['so_dt']) ?></div>
                </div>
                <?php if ($data['email']): ?>
                <div class="info-box">
                    <div class="label">Email</div>
                    <div class="value"><?= e($data['email']) ?></div>
                </div>
                <?php endif; ?>
                <div class="info-box highlight">
                    <div class="label">Số khách</div>
                    <div class="value"><?= $data['so_khach'] ?> người</div>
                </div>
            </div>
        </div>

        <!-- Khung giờ đã đặt -->
        <div class="info-block">
            <div class="info-block-title">Phòng &amp; khung giờ</div>
            <table class="admin-table" style="box-shadow:none;">
                <thead><tr><th>Khung giờ</th><th>Ngày</th><th style="text-align:right">Đơn giá</th></tr></thead>
                <tbody>
                    <?php if ($so_ca_sang > 0): ?>
                    <tr>
                        <td><strong><?= e($room['name']) ?></strong><br><small style="color:var(--muted)">Sáng · 09:00–12:00</small></td>
                        <td><?php foreach ($thu_sang as $t): ?><span class="badge badge-pending" style="margin:2px"><?= $t ?></span><?php endforeach; ?><br><small style="color:var(--muted)"><?= $so_ca_sang ?> ca</small></td>
                        <td style="text-align:right;color:var(--accent);font-weight:700"><?= vnd($room['price_morning']) ?>/ca</td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($so_ca_chieu > 0): ?>
                    <tr>
                        <td><strong><?= e($room['name']) ?></strong><br><small style="color:var(--muted)">Chiều · 13:00–16:00</small></td>
                        <td><?php foreach ($thu_chieu as $t): ?><span class="badge badge-pending" style="margin:2px"><?= $t ?></span><?php endforeach; ?><br><small style="color:var(--muted)"><?= $so_ca_chieu ?> ca</small></td>
                        <td style="text-align:right;color:var(--accent);font-weight:700"><?= vnd($room['price_afternoon']) ?>/ca</td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($so_ca_dem > 0): ?>
                    <tr>
                        <td><strong><?= e($room['name']) ?></strong><br><small style="color:var(--muted)">Qua đêm · 20:00–08:00</small></td>
                        <td><?php foreach ($thu_dem as $t): ?><span class="badge badge-pending" style="margin:2px"><?= $t ?></span><?php endforeach; ?><br><small style="color:var(--muted)"><?= $so_ca_dem ?> ca</small></td>
                        <td style="text-align:right;color:var(--accent);font-weight:700"><?= vnd($room['price_overnight']) ?>/ca</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Tổng tiền -->
        <div class="info-block">
            <div class="info-block-title">Tổng tiền</div>
            <table class="total-table">
                <?php if ($so_ca_sang > 0): ?>
                <tr><td class="label-col">Sáng: <?= $so_ca_sang ?> ca × <?= vnd($room['price_morning']) ?></td><td class="amount-col"><?= vnd($data['tien_sang']) ?></td></tr>
                <?php endif; ?>
                <?php if ($so_ca_chieu > 0): ?>
                <tr><td class="label-col">Chiều: <?= $so_ca_chieu ?> ca × <?= vnd($room['price_afternoon']) ?></td><td class="amount-col"><?= vnd($data['tien_chieu']) ?></td></tr>
                <?php endif; ?>
                <?php if ($so_ca_dem > 0): ?>
                <tr><td class="label-col">Đêm: <?= $so_ca_dem ?> ca × <?= vnd($room['price_overnight']) ?></td><td class="amount-col"><?= vnd($data['tien_dem']) ?></td></tr>
                <?php endif; ?>
                <?php if ($data['phu_thu'] > 0): ?>
                <tr class="surcharge-row"><td class="label-col">Phụ thu <?= $data['so_khach'] - 2 ?> khách</td><td class="amount-col">+<?= vnd($data['phu_thu']) ?></td></tr>
                <?php endif; ?>
                <tr class="total-row"><td class="label-col">TỔNG</td><td class="amount-col"><?= vnd($data['tong_cuoi']) ?></td></tr>
            </table>
        </div>

        <!-- Hướng dẫn thanh toán -->
        <div class="info-block">
            <div class="payment-note">
                💳 <strong>Chuyển khoản:</strong>
                <strong>MB Bank: 0965544925 — DUONG HOANG AN</strong><br>
                Nội dung: <strong><?= mb_strtoupper(preg_replace('/\s+/', '', $data['ho_ten']), 'UTF-8') ?>-<?= $data['so_dt'] ?></strong><br>
                Nhân viên xác nhận trong vòng 30 phút.
            </div>
        </div>

        <div class="btn-group">
            <a href="<?= $base_url ?>" class="btn btn-outline">← Đặt thêm phòng</a>
            <!-- window.print() = mở hộp thoại in trang -->
            <button onclick="window.print()" class="btn btn-primary">🖨 In xác nhận</button>
        </div>
    </div>

<?php else: // Không có dữ liệu (vd: reload trang sau khi đã xem) ?>
    <div class="card" style="text-align:center;">
        <div class="result-header" style="border:none;margin:0;padding:0;">
            <span class="icon">🔍</span>
            <h1 style="font-size:20px;">Không có dữ liệu</h1>
            <p>Vui lòng quay lại trang chủ để đặt phòng.</p>
        </div>
        <div class="btn-group" style="justify-content:center;"><a href="<?= $base_url ?>" class="btn btn-primary">← Về trang chủ</a></div>
    </div>
<?php endif; ?>

</div>
</body>
</html>
