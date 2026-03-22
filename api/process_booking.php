<?php
/**
 * ============================================================
 * api/process_booking.php — Xử lý đặt phòng
 * ============================================================
 * LUỒNG HOẠT ĐỘNG:
 * 1. Khách chọn giờ + điền thông tin trên modal (index.php)
 * 2. Bấm "Xác nhận" → form gửi POST đến file này
 * 3. File này: validate → tính tiền → lưu DB → redirect kết quả
 * 
 * DỮ LIỆU NHẬN TỪ FORM (qua $_POST):
 * - room_id, room_slug, room_name (ẩn)
 * - ho_ten, so_dt, email, so_khach (user nhập)
 * - ca_sang[], ca_chieu[], ca_dem[] (checkbox đã tick)
 * - xac_nhan_tuoi (checkbox xác nhận)
 * - cccd_truoc, cccd_sau (file ảnh CCCD — qua $_FILES)
 */

// Nạp auth.php → DB, session, hàm tiện ích, BASE_URL
require_once __DIR__ . '/../includes/auth.php';

// Chỉ chấp nhận phương thức POST (form submit)
// Nếu ai đó gõ URL trực tiếp (GET) → đá về trang chủ
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL); // Về trang chủ
    exit;
}

// ---- THƯ MỤC LƯU ẢNH CCCD ----
$cccd_upload_dir = __DIR__ . '/../uploads/cccd/';

/**
 * Upload ảnh CCCD (mặt trước hoặc mặt sau)
 * - Kiểm tra loại file (chỉ ảnh), dung lượng (tối đa 5MB)
 * - Đặt tên file: cccd-{booking_id}-{truoc|sau}.{ext}
 *
 * @param string $field_name — Tên input file ('cccd_truoc' hoặc 'cccd_sau')
 * @param int    $booking_id — ID đơn đặt phòng (dùng đặt tên file)
 * @param string $side       — 'truoc' hoặc 'sau'
 * @param string $upload_dir — Đường dẫn thư mục lưu file
 * @return string|null — Đường dẫn tương đối hoặc null nếu không có file
 */
function handleCCCDUpload($field_name, $booking_id, $side, $upload_dir) {
    // Không có file upload → bỏ qua (CCCD không bắt buộc)
    if (!isset($_FILES[$field_name]) || $_FILES[$field_name]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $file = $_FILES[$field_name];

    // Kiểm tra loại file — chỉ chấp nhận ảnh
    // mime_content_type() đọc header file thật (không dựa vào extension giả)
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = mime_content_type($file['tmp_name']);
    if (!in_array($file_type, $allowed_types)) {
        return null; // File không phải ảnh → bỏ qua
    }

    // Giới hạn dung lượng: 5MB
    if ($file['size'] > 5 * 1024 * 1024) {
        return null; // Quá lớn → bỏ qua
    }

    // Lấy extension từ MIME type
    $ext_map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    $ext = $ext_map[$file_type] ?? 'jpg';

    // Đặt tên file: cccd-15-truoc.jpg, cccd-15-sau.png
    $filename = 'cccd-' . $booking_id . '-' . $side . '.' . $ext;
    $dest = $upload_dir . $filename;

    // Di chuyển file từ thư mục tạm sang đích
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return null; // Lỗi lưu file → bỏ qua
    }

    // Trả về đường dẫn tương đối (lưu vào DB)
    return 'uploads/cccd/' . $filename;
}

// ---- CẤU HÌNH PHỤ THU ----
$PHU_THU_MOI_KHACH = 50000; // 50.000đ mỗi khách vượt ngưỡng
$NGUONG_PHU_THU    = 2;     // Từ khách thứ 3 trở lên mới phụ thu

// ---- LẤY & LÀM SẠCH DỮ LIỆU TỪ FORM ----
$room_id   = (int)($_POST['room_id']   ?? 0);  // (int) ép kiểu về số nguyên
$room_slug = trim($_POST['room_slug']     ?? '');
$room_name = trim($_POST['room_name']     ?? '');
$ho_ten    = trim($_POST['ho_ten']        ?? '');
$so_dt     = trim($_POST['so_dt']         ?? '');
$email     = trim($_POST['email']         ?? '');
// max(1, min(4, ...)) = giới hạn giá trị từ 1 đến 4
$so_khach  = max(1, min(4, (int)($_POST['so_khach'] ?? 2)));

// ---- LỌC SLOT CODE ----
// Mỗi checkbox khi tick sẽ gửi giá trị vd: 'xn-m3' (phòng xine, sáng, thứ 4)
// Hàm ẩn danh (fn) kiểm tra: phải là string + chỉ chứa a-z, 0-9, dấu -
$valid_slot = fn($v) => is_string($v) && preg_match('/^[a-z0-9\-]+$/', $v);

// array_filter() lọc bỏ giá trị không hợp lệ
// array_values() đánh lại index từ 0 (sau filter, index có thể bị hỏng)
$ca_sang_chon  = array_values(array_filter((array)($_POST['ca_sang']  ?? []), $valid_slot));
$ca_chieu_chon = array_values(array_filter((array)($_POST['ca_chieu'] ?? []), $valid_slot));
$ca_dem_chon   = array_values(array_filter((array)($_POST['ca_dem']   ?? []), $valid_slot));

// count() = đếm số phần tử trong mảng
$so_ca_sang  = count($ca_sang_chon);  // Bao nhiêu ca sáng đã chọn
$so_ca_chieu = count($ca_chieu_chon);
$so_ca_dem   = count($ca_dem_chon);
$tong_ca     = $so_ca_sang + $so_ca_chieu + $so_ca_dem; // Tổng tất cả ca

// ---- VALIDATE (KIỂM TRA) ----
$loi = []; // Mảng chứa các lỗi

if (empty($ho_ten)) $loi[] = 'Vui lòng nhập họ và tên.';

if (empty($so_dt)) $loi[] = 'Vui lòng nhập số điện thoại.';
// preg_replace() bỏ hết ký tự không phải số, rồi kiểm tra 9-11 chữ số
elseif (!preg_match('/^[0-9]{9,11}$/', preg_replace('/\D/', '', $so_dt)))
    $loi[] = 'Số điện thoại không hợp lệ (cần 9–11 chữ số).';

// Nếu có nhập email → kiểm tra định dạng
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL))
    $loi[] = 'Địa chỉ email không đúng định dạng.';

// Phải chọn ít nhất 1 ca
if ($tong_ca === 0 || $tong_ca > 21)
    $loi[] = 'Bạn chưa chọn khung giờ nào.';

// Phải tick checkbox xác nhận tuổi
if (!isset($_POST['xac_nhan_tuoi']))
    $loi[] = 'Bạn cần xác nhận điều khoản về độ tuổi.';

// ---- KIỂM TRA PHÒNG TỒN TẠI TRONG DB ----
$stmt_room = db()->prepare("SELECT * FROM rooms WHERE id = ? AND is_active = 1 LIMIT 1");
$stmt_room->bind_param('i', $room_id); // 'i' = integer (số nguyên)
$stmt_room->execute();
$room = $stmt_room->get_result()->fetch_assoc(); // Lấy thông tin phòng
$stmt_room->close();

if (!$room) $loi[] = 'Phòng được chọn không hợp lệ.';

// ---- NẾU CÓ LỖI → REDIRECT VỀ TRANG LỖI ----
if (!empty($loi)) { // !empty() = mảng có phần tử
    $_SESSION['booking_errors'] = $loi; // Lưu danh sách lỗi vào session
    header('Location: ' . BASE_URL . 'pages/user/booking_result.php?status=error');
    exit;
}

// ---- TÍNH TIỀN ----
$tien_sang  = $so_ca_sang  * $room['price_morning'];   // Số ca × đơn giá
$tien_chieu = $so_ca_chieu * $room['price_afternoon'];
$tien_dem   = $so_ca_dem   * $room['price_overnight'];
$tong_phong = $tien_sang + $tien_chieu + $tien_dem;     // Tổng tiền phòng

// Phụ thu: nếu quá 2 khách → mỗi khách thêm 50k
// VD: 3 khách → (3-2) × 50000 = 50000
$phu_thu = $so_khach > $NGUONG_PHU_THU
           ? ($so_khach - $NGUONG_PHU_THU) * $PHU_THU_MOI_KHACH
           : 0; // Không phụ thu nếu ≤ 2 khách

$tong_cuoi = $tong_phong + $phu_thu; // Tổng cuối cùng

// ---- LƯU VÀO DATABASE (dùng Transaction) ----
// Transaction = nhóm nhiều thao tác DB thành 1 khối
// Nếu 1 thao tác lỗi → rollback (hủy tất cả) → không có dữ liệu rác
$conn = db();
$conn->begin_transaction(); // Bắt đầu transaction

try { // try-catch: nếu có lỗi trong try → nhảy vào catch

    // BƯỚC 1: Chèn đơn booking vào bảng bookings
    $user_id = $_SESSION['user_id'] ?? null; // null nếu khách vãng lai
    $stmt = $conn->prepare(
        "INSERT INTO bookings
         (user_id, room_id, room_slug, room_name, ho_ten, so_dt, email, so_khach,
          tien_sang, tien_chieu, tien_dem, phu_thu, tong_cuoi)
         VALUES (?,?,?,?,?,?,?,?, ?,?,?,?,?)"
    );
    // 'iisssssiiiiii' = kiểu dữ liệu: i=integer, s=string
    $stmt->bind_param(
        'iisssssiiiiii',
        $user_id, $room_id, $room_slug, $room_name,
        $ho_ten, $so_dt, $email, $so_khach,
        $tien_sang, $tien_chieu, $tien_dem, $phu_thu, $tong_cuoi
    );
    $stmt->execute();
    $booking_id = (int)$conn->insert_id; // Lấy ID vừa tạo (auto increment)
    $stmt->close();

    // BƯỚC 1.5: Upload ảnh CCCD (nếu có) → lưu đường dẫn vào DB
    // Cần booking_id để đặt tên file nên phải upload SAU khi insert
    $cccd_truoc_path = handleCCCDUpload('cccd_truoc', $booking_id, 'truoc', $cccd_upload_dir);
    $cccd_sau_path   = handleCCCDUpload('cccd_sau',   $booking_id, 'sau',   $cccd_upload_dir);

    // Nếu có ít nhất 1 ảnh CCCD → cập nhật vào booking vừa tạo
    if ($cccd_truoc_path || $cccd_sau_path) {
        $stmt_cccd = $conn->prepare(
            "UPDATE bookings SET cccd_truoc=?, cccd_sau=? WHERE id=?"
        );
        $stmt_cccd->bind_param('ssi', $cccd_truoc_path, $cccd_sau_path, $booking_id);
        $stmt_cccd->execute();
        $stmt_cccd->close();
    }

    // BƯỚC 2: Kiểm tra trùng lịch (slot đã có người đặt chưa)
    $stmt_check = $conn->prepare(
        "SELECT id FROM booking_slots
         WHERE room_slug = ? AND loai_ca = ? AND col_ngay = ? LIMIT 1"
    );

    // Lặp qua 3 loại ca: sáng, chiều, đêm
    foreach ([
        'sang'  => $ca_sang_chon,  // 'sang' → mảng slot đã chọn
        'chieu' => $ca_chieu_chon,
        'dem'   => $ca_dem_chon,
    ] as $loai => $slots) {
        foreach ($slots as $slot_code) {
            // Lấy số cuối của code: 'xn-m3' → '3' → col_ngay = 3
            $col_ngay = (int)substr($slot_code, -1); // substr(..., -1) = ký tự cuối
            $stmt_check->bind_param("ssi", $room_slug, $loai, $col_ngay);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                // Đã có người đặt → ném lỗi → nhảy vào catch
                throw new Exception("Ca này đã có người đặt. Vui lòng chọn ca khác.");
            }
        }
    }
    $stmt_check->close();

    // BƯỚC 3: Chèn từng slot vào bảng booking_slots
    $stmt2 = $conn->prepare(
        "INSERT INTO booking_slots (booking_id, room_id, room_slug, loai_ca, col_ngay, slot_code)
         VALUES (?,?,?,?,?,?)"
    );

    foreach ([
        'sang'  => $ca_sang_chon,
        'chieu' => $ca_chieu_chon,
        'dem'   => $ca_dem_chon,
    ] as $loai => $slots) {
        foreach ($slots as $slot_code) {
            $col_ngay = (int)substr($slot_code, -1);
            $stmt2->bind_param('iissis', $booking_id, $room_id, $room_slug, $loai, $col_ngay, $slot_code);
            $stmt2->execute();
        }
    }
    $stmt2->close();

    $conn->commit(); // Xác nhận tất cả thao tác DB → lưu vĩnh viễn

    // Lưu kết quả vào session → trang booking_result.php sẽ hiển thị
    $_SESSION['booking_result'] = [
        'booking_id' => $booking_id,
        'room'       => $room,
        'ho_ten'     => $ho_ten,
        'so_dt'      => $so_dt,
        'email'      => $email,
        'so_khach'   => $so_khach,
        'ca_sang'    => $ca_sang_chon,
        'ca_chieu'   => $ca_chieu_chon,
        'ca_dem'     => $ca_dem_chon,
        'tien_sang'  => $tien_sang,
        'tien_chieu' => $tien_chieu,
        'tien_dem'   => $tien_dem,
        'phu_thu'    => $phu_thu,
        'tong_cuoi'  => $tong_cuoi,
    ];

    // Chuyển đến trang kết quả thành công
    header('Location: ' . BASE_URL . 'pages/user/booking_result.php?status=success');
    exit;

} catch (Exception $e) { // Nếu có lỗi bất kỳ trong try → nhảy vào đây
    $conn->rollback(); // Hủy tất cả thao tác DB (không lưu gì cả)
    error_log('[Booking] Lỗi: ' . $e->getMessage()); // Ghi lỗi vào log
    $_SESSION['booking_errors'] = [$e->getMessage()]; // Lưu lỗi cho user
    header('Location: ' . BASE_URL . 'pages/user/booking_result.php?status=error');
    exit;
}
