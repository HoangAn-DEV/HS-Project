<?php
/**
 * ============================================================
 * api/booking_action.php — Admin xử lý đơn đặt phòng
 * ============================================================
 * File này nhận lệnh từ admin qua URL (GET):
 *   booking_action.php?action=approve&id=5  → Duyệt đơn #5
 *   booking_action.php?action=cancel&id=5   → Hủy đơn #5
 *   booking_action.php?action=delete&id=5   → Xóa đơn #5
 * 
 * Sau khi xử lý xong → redirect về trang quản lý bookings
 */

// Nạp auth.php → có hàm require_admin(), db(), BASE_URL
require_once __DIR__ . '/../includes/auth.php';

// BẮT BUỘC phải là admin mới được dùng file này
// Nếu không phải admin → bị đá về trang chủ
require_admin();

// Lấy action và id từ URL (?action=xxx&id=yyy)
$action = $_GET['action'] ?? '';        // 'approve', 'cancel', hoặc 'delete'
$id     = (int)($_GET['id'] ?? 0);      // ID đơn đặt phòng (ép kiểu int)

// Kiểm tra: id phải > 0 và action phải hợp lệ
// in_array() = kiểm tra giá trị có nằm trong mảng không
if ($id <= 0 || !in_array($action, ['approve', 'cancel', 'delete'])) {
    $_SESSION['error'] = 'Hành động không hợp lệ.';
    header('Location: ' . BASE_URL . 'pages/admin/bookings.php');
    exit;
}

$conn = db(); // Lấy kết nối database

/**
 * Xóa file ảnh CCCD trên ổ đĩa (nếu có) của 1 booking
 * @param mysqli $conn — Kết nối DB
 * @param int    $booking_id — ID đơn cần xóa ảnh
 */
function deleteCCCDFiles($conn, $booking_id) {
    $stmt = $conn->prepare("SELECT cccd_truoc, cccd_sau FROM bookings WHERE id=? LIMIT 1");
    $stmt->bind_param('i', $booking_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) return;

    // __DIR__ = thư mục api/ → lùi 1 cấp về gốc project
    $base = __DIR__ . '/../';
    foreach (['cccd_truoc', 'cccd_sau'] as $col) {
        if (!empty($row[$col]) && file_exists($base . $row[$col])) {
            unlink($base . $row[$col]); // Xóa file vật lý
        }
    }
}

// switch = kiểm tra giá trị $action rồi chạy code tương ứng
switch ($action) {

    // ---- DUYỆT ĐƠN (approve) ----
    // Đổi trạng thái từ 'cho_xac_nhan' thành 'da_thanh_toan'
    case 'approve':
        $stmt = $conn->prepare("UPDATE bookings SET trang_thai='da_thanh_toan' WHERE id=?");
        $stmt->bind_param('i', $id); // 'i' = integer
        $stmt->execute();            // Chạy câu SQL
        $stmt->close();              // Giải phóng bộ nhớ
        $_SESSION['success'] = "Đã duyệt đơn #$id thành công.";
        break; // Thoát khỏi switch




    // ---- HỦY ĐƠN (cancel) ----
    // Đổi trạng thái thành 'da_huy' + XÓA slot để người khác đặt được
    case 'cancel':
        // Xóa file ảnh CCCD trước khi thay đổi DB
        deleteCCCDFiles($conn, $id);

        // Dùng transaction vì có 2 thao tác DB liên quan nhau
        $conn->begin_transaction();
        try {
            // Bước 1: Đổi trạng thái đơn
            $stmt = $conn->prepare("UPDATE bookings SET trang_thai='da_huy' WHERE id=?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();

            // Bước 2: Xóa các slot đã đặt → giải phóng lịch
            // Nếu không xóa → slot vẫn hiện "Đã đặt" trên bảng giờ
            $stmt2 = $conn->prepare("DELETE FROM booking_slots WHERE booking_id=?");
            $stmt2->bind_param('i', $id);
            $stmt2->execute();
            $stmt2->close();

            $conn->commit(); // OK → lưu cả 2 thao tác
            $_SESSION['success'] = "Đã hủy đơn #$id.";
        } catch (Exception $e) {
            $conn->rollback(); // Lỗi → hủy cả 2 thao tác
            $_SESSION['error'] = 'Lỗi khi hủy đơn: ' . $e->getMessage();
        }
        break;
        
    // ---- XÓA ĐƠN (delete) ----
    // Xóa hoàn toàn khỏi database (không khôi phục được)
    // booking_slots cũng tự xóa theo nhờ ON DELETE CASCADE trong schema
    case 'delete':
        // Xóa file ảnh CCCD trước khi xóa record trong DB
        deleteCCCDFiles($conn, $id);

        $stmt = $conn->prepare("DELETE FROM bookings WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['success'] = "Đã xóa đơn #$id.";
        break;
}

// Sau khi xử lý xong → quay lại trang quản lý bookings
header('Location: ' . BASE_URL . 'pages/admin/bookings.php');
exit;
