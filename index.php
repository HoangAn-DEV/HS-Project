<?php
/**
 * ============================================================
 * index.php — Trang chủ SKIBIDI TOLET Homestay
 * ============================================================
 *
 * FILE NÀY LÀM GÌ?
 * - Hiển thị banner giới thiệu (hero section)
 * - Lấy danh sách phòng từ database → hiển thị dạng card
 * - Mỗi phòng có 1 modal (popup) để khách đặt phòng
 * - Đọc slot đã đặt từ DB để hiển thị "Đã đặt" trên bảng giờ
 *
 * CÁCH HOẠT ĐỘNG:
 * 1. require_once 'includes/auth.php' → kết nối DB + session
 * 2. Query bảng `rooms` → lấy phòng đang hoạt động
 * 3. Query view `v_booked_slots` → lấy slot đã có người đặt
 * 4. Dùng vòng lặp foreach để render từng phòng ra HTML
 * ============================================================
 */

// Bước 1: Nạp file auth.php (bao gồm kết nối DB, session, hàm tiện ích)
require_once __DIR__ . '/includes/auth.php';

// ------------------------------------------------------------------
// Bước 2: Lấy danh sách phòng đang hoạt động từ bảng `rooms`
// - is_active = 1 nghĩa là phòng đang hiển thị
// - ORDER BY sort_order ASC: sắp xếp theo thứ tự admin đã cài
// ------------------------------------------------------------------
$rooms = [];
$res = db()->query("SELECT * FROM rooms WHERE is_active = 1 ORDER BY sort_order ASC");

// fetch_assoc() trả về 1 hàng dưới dạng mảng key => value
// Vòng while chạy cho đến khi hết hàng
while ($row = $res->fetch_assoc()) {
    // amenities trong DB lưu dạng JSON string: '["Máy chiếu","Wifi"]'
    // json_decode() chuyển JSON thành mảng PHP để dùng foreach
    $row['amenities_arr'] = json_decode($row['amenities'] ?? '[]', true) ?: [];
    $rooms[] = $row; // Thêm phòng vào mảng
}

// ------------------------------------------------------------------
// Bước 3: Lấy danh sách slot đã có người đặt
// View v_booked_slots chỉ trả về slot của đơn CHƯA BỊ HỦY
// Mục đích: hiển thị ô "Đã đặt" (không cho click) trên bảng giờ
// ------------------------------------------------------------------
$BOOKED = [];
foreach ($rooms as $r) {
    // Khởi tạo mảng rỗng cho từng phòng, từng ca
    $BOOKED[$r['slug']] = ['sang' => [], 'chieu' => [], 'dem' => []];
}

$res2 = db()->query("SELECT phong_id, loai_ca, col_ngay FROM v_booked_slots");
if ($res2) {
    while ($s = $res2->fetch_assoc()) {
        $pid = $s['phong_id'];  // VD: 'xine'
        $ca  = $s['loai_ca'];   // VD: 'sang'
        // col_ngay trong DB: 2=Thứ 2, 3=Thứ 3... 8=CN
        // genRow() dùng index 1-7, nên cần offset -1
        if (isset($BOOKED[$pid][$ca])) {
            $BOOKED[$pid][$ca][] = (int)$s['col_ngay'] - 1;
        }
    }
}

/**
 * Hàm genRow() — Tạo 7 ô (Thứ 2 → CN) cho 1 hàng ca
 *
 * @param string $px     Prefix phòng (vd: 'xn' cho Xi nê)
 * @param string $tc     Ký tự ca: 'm'=sáng, 'a'=chiều, 'o'=đêm
 * @param string $name   Tên input: 'ca_sang[]', 'ca_chieu[]', 'ca_dem[]'
 * @param array  $booked Mảng các cột đã đặt (index 1-7)
 * @return string        HTML cho 7 ô <td>
 */
function genRow(string $px, string $tc, string $name, array $booked): string
{
    $out = '';
    for ($col = 1; $col <= 7; $col++) {
        $s   = $col + 1; // col_ngay thực tế trong DB
        $cid = "{$px}-{$tc}{$s}"; // ID duy nhất, vd: 'xn-m3'

        if (in_array($col, $booked)) {
            // Slot này đã có người đặt → hiển thị ô khóa
            $out .= '<td><div class="cell-booked">Đã đặt</div></td>';
        } else {
            // Slot trống → hiển thị checkbox để khách chọn
            $out .= "<td class=\"cell-wrap\">
                <input type=\"checkbox\" name=\"{$name}[]\" value=\"{$cid}\" id=\"{$cid}\" class=\"gio-cb\">
                <label for=\"{$cid}\">Trống</label>
            </td>";
        }
    }
    return $out;
}

// ------------------------------------------------------------------
// Bước 4: Render giao diện
// include header.php → hiển thị <html>, <head>, thanh nav
// ------------------------------------------------------------------
$page_title = 'Trang chủ';
include __DIR__ . '/includes/header.php';
?>

<!-- ===== HERO BANNER ===== -->
<section class="hero">
    <h1>Khám phá <em>SKIBIDI TOLET</em></h1>
    <p>Không gian tự check-in, riêng tư &amp; chill tại Cần Thơ</p>
    <a href="#rooms" class="hero-cta">Xem phòng ↓</a>
</section>

<!-- ===== DANH SÁCH PHÒNG ===== -->
<div class="container" id="rooms">
    <h2 class="section-title">Các phòng của chúng tôi</h2>
    <p class="section-subtitle">Mỗi phòng mang một phong cách riêng biệt — chọn không gian phù hợp với bạn</p>

    <div class="rooms-grid">
    <?php
    // Vòng lặp qua từng phòng để render card
    foreach ($rooms as $r):
    ?>
        <div class="room-card">
            <!-- Ảnh phòng -->
            <div class="room-card-img" style="background-image:url('<?= e($r['image_url'] ?? '') ?>')">
                <span class="room-card-price-badge">
                    Từ <?= number_format($r['price_morning'],0,',','.') ?>đ
                </span>
            </div>

            <!-- Nội dung -->
            <div class="room-card-body">
                <h3><?= e($r['name']) ?></h3>
                <p><?= e($r['description'] ?? '') ?></p>

                <!-- Tiện ích: lặp qua mảng amenities_arr -->
                <div class="amenities">
                    <?php foreach ($r['amenities_arr'] as $t): ?>
                        <span class="amenity-tag"><?= e($t) ?></span>
                    <?php endforeach; ?>
                </div>

                <!-- Giá + nút đặt -->
                <div class="room-card-footer">
                    <div class="price-info">
                        <strong><?= number_format($r['price_overnight'],0,',','.') ?>đ</strong> / đêm
                    </div>
                    <!-- href="#modal-xxx" sẽ kích hoạt CSS :target để hiện modal -->
                    <a href="#modal-<?= e($r['slug']) ?>" class="book-btn">Đặt phòng →</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
</div>

<!-- ===== CÁC MODAL ĐẶT PHÒNG (1 modal / phòng) ===== -->
<?php
foreach ($rooms as $r):
    // Lấy thông tin cần thiết
    $slug  = $r['slug'];
    $px    = $r['prefix'];
    $sang  = $r['price_morning'];
    $chieu = $r['price_afternoon'];
    $dem   = $r['price_overnight'];
?>
<div id="modal-<?= $slug ?>" class="modal-overlay"
     data-phong="<?= $slug ?>"
     data-gia-sang="<?= $sang ?>"
     data-gia-chieu="<?= $chieu ?>"
     data-gia-dem="<?= $dem ?>">
    <div class="modal-content">
        <a href="#" class="close-btn">&times;</a>

        <!-- Form gửi về api/process_booking.php bằng phương thức POST -->
        <form method="POST" action="<?= $base_url ?>api/process_booking.php" enctype="multipart/form-data">
            <!-- Hidden input: gửi kèm ID phòng để server biết đặt phòng nào -->
            <input type="hidden" name="room_id"   value="<?= $r['id'] ?>">
            <input type="hidden" name="room_slug" value="<?= $slug ?>">
            <input type="hidden" name="room_name" value="<?= e($r['name']) ?>">

            <h2 class="modal-room-name">Đặt phòng: <?= e($r['name']) ?></h2>
            <p class="modal-subtitle">Chọn khung giờ bạn muốn (có thể chọn nhiều ngày)</p>

            <div class="booking-container">
                <!-- === BÊN TRÁI: Bảng chọn giờ === -->
                <div class="booking-left">
                    <div class="table-responsive">
                        <table class="booking-table">
                            <thead>
                                <tr>
                                    <th>Khung giờ</th>
                                    <th>T2</th><th>T3</th><th>T4</th>
                                    <th>T5</th><th>T6</th><th>T7</th><th>CN</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Hàng ca sáng -->
                                <tr class="row-morning">
                                    <td class="time-label">
                                        <strong>Sáng</strong>
                                        09:00–12:00<br>
                                        <small><?= $sang/1000 ?>k</small>
                                    </td>
                                    <?= genRow($px, 'm', 'ca_sang', $BOOKED[$slug]['sang']) ?>
                                </tr>
                                <!-- Hàng ca chiều -->
                                <tr class="row-afternoon">
                                    <td class="time-label">
                                        <strong>Chiều</strong>
                                        13:00–16:00<br>
                                        <small><?= $chieu/1000 ?>k</small>
                                    </td>
                                    <?= genRow($px, 'a', 'ca_chieu', $BOOKED[$slug]['chieu']) ?>
                                </tr>
                                <!-- Hàng ca đêm -->
                                <tr class="row-overnight">
                                    <td class="time-label">
                                        <strong>Qua đêm</strong>
                                        20:00–08:00<br>
                                        <small><?= $dem/1000 ?>k</small>
                                    </td>
                                    <?= genRow($px, 'o', 'ca_dem', $BOOKED[$slug]['dem']) ?>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Tóm tắt giá (JS cập nhật real-time khi tick checkbox) -->
                    <div class="price-summary">
                        <div class="summary-row">
                            <span>Ca Sáng:</span>
                            <span><b class="cnt-sang">0</b> ca × <?= number_format($sang,0,',','.') ?>đ = <strong class="tien-sang">0đ</strong></span>
                        </div>
                        <div class="summary-row">
                            <span>Ca Chiều:</span>
                            <span><b class="cnt-chieu">0</b> ca × <?= number_format($chieu,0,',','.') ?>đ = <strong class="tien-chieu">0đ</strong></span>
                        </div>
                        <div class="summary-row">
                            <span>Ca Qua đêm:</span>
                            <span><b class="cnt-dem">0</b> ca × <?= number_format($dem,0,',','.') ?>đ = <strong class="tien-dem">0đ</strong></span>
                        </div>
                        <div class="price-note">
                            Tổng: <b class="cnt-total">0</b> ca · Tạm tính: <strong class="tien-total">0đ</strong> · Phụ thu >2 khách: +50k/người
                        </div>
                    </div>
                </div>

                <!-- === BÊN PHẢI: Form thông tin khách === -->
                <div class="booking-right">
                    <h3>Thông tin của bạn</h3>

                    <div class="form-group">
                        <input type="text" name="ho_ten" placeholder="Họ và tên *" required>
                    </div>
                    <div class="form-group">
                        <input type="tel" name="so_dt" placeholder="Số điện thoại *" required>
                    </div>
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Email (không bắt buộc)">
                    </div>

                    <div class="form-group">
                        <label>Số lượng khách</label>
                        <select name="so_khach" class="so-khach-select">
                            <option value="1">1 người</option>
                            <option value="2" selected>2 người</option>
                            <option value="3">3 người</option>
                            <option value="4">4 người</option>
                        </select>
                    </div>
                    <ul class="form-notes">
                        <li>Quá 2 khách sẽ phụ thu 50.000đ/người</li>
                        <li>Ca qua đêm tối đa 2 khách</li>
                    </ul>

                    <div class="form-group">
                        <label>Căn cước / Hộ chiếu</label>
                        <div class="cccd-upload">
                            <!-- Ô upload mặt trước: bấm vào → mở chọn file -->
                            <div class="upload-box" onclick="this.querySelector('input[type=file]').click()">
                                <img class="cccd-preview" src="" alt="" style="display:none">
                                <span class="upload-text">📷 Mặt trước</span>
                                <button type="button" class="cccd-remove" style="display:none" onclick="event.stopPropagation(); removeCCCD(this)">✕</button>
                                <input type="file" name="cccd_truoc" accept="image/*" onchange="previewCCCD(this)" style="display:none">
                            </div>
                            <div class="upload-box" onclick="this.querySelector('input[type=file]').click()">
                                <img class="cccd-preview" src="" alt="" style="display:none">
                                <span class="upload-text">📷 Mặt sau</span>
                                <button type="button" class="cccd-remove" style="display:none" onclick="event.stopPropagation(); removeCCCD(this)">✕</button>
                                <input type="file" name="cccd_sau" accept="image/*" onchange="previewCCCD(this)" style="display:none">
                            </div>
                        </div>
                    </div>
                    <ul class="form-notes"><li>Dùng để khai báo lưu trú, sẽ xóa sau check-out</li></ul>

                    <div class="form-group checkbox-group">
                        <input type="checkbox" name="xac_nhan_tuoi" id="<?= $px ?>-age" required>
                        <label for="<?= $px ?>-age">
                            Tôi xác nhận tất cả khách đủ tuổi vị thành niên hoặc có người giám hộ đi kèm.
                        </label>
                    </div>
                </div>
            </div>

            <button type="submit" class="confirm-btn">Xác nhận đặt phòng →</button>
        </form>
    </div>
</div>
<?php endforeach; ?>

<?php
// include footer.php → hiển thị footer + đóng </body></html>
include __DIR__ . '/includes/footer.php';
?>
