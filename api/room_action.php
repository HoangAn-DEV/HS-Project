<?php
/**
 * ============================================================
 * api/room_action.php — Admin thêm / sửa / xóa phòng
 * ============================================================
 * Nhận dữ liệu từ form trong pages/admin/rooms.php
 * 
 * 3 ACTION:
 *   POST ?action=create  → Thêm phòng mới
 *   POST ?action=update  → Cập nhật phòng (cần id trong POST)
 *   GET  ?action=delete&id=X → Xóa phòng
 * 
 * Sau xử lý → redirect về pages/admin/rooms.php
 */

require_once __DIR__ . '/../includes/auth.php';
require_admin(); // Chỉ admin được thao tác

$conn   = db();
// $_REQUEST = gộp cả $_GET và $_POST (lấy action từ URL hoặc form)
$action = $_REQUEST['action'] ?? '';

switch ($action) {

    // ===== THÊM PHÒNG MỚI =====
    case 'create':
        // Chỉ chấp nhận POST (form submit), không cho GET
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') break;

        // Lấy dữ liệu từ form, e() escape HTML chống XSS
        $slug   = trim($_POST['slug'] ?? '');
        $prefix = trim($_POST['prefix'] ?? '');
        $name   = trim($_POST['name'] ?? '');
        $desc   = trim($_POST['description'] ?? '');
        $pM     = (int)($_POST['price_morning'] ?? 0);
        $pA     = (int)($_POST['price_afternoon'] ?? 0);
        $pO     = (int)($_POST['price_overnight'] ?? 0);
        $maxG   = (int)($_POST['max_guests'] ?? 4);
        $amenities = trim($_POST['amenities'] ?? '[]');
        $img    = trim($_POST['image_url'] ?? '');
        $order  = (int)($_POST['sort_order'] ?? 0);

        // Kiểm tra bắt buộc
        if (empty($slug) || empty($name)) {
            $_SESSION['error'] = 'Slug và tên phòng không được để trống.';
            break; // Thoát switch → redirect ở cuối file
        }

        // Chèn phòng mới vào bảng rooms
        $stmt = $conn->prepare(
            "INSERT INTO rooms (slug, prefix, name, description, price_morning, price_afternoon,
             price_overnight, max_guests, amenities, image_url, sort_order)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)"
        );
        // 'ssssiiiissi' = 4 string, 4 integer, 2 string, 1 integer
        $stmt->bind_param('ssssiiiissi',
            $slug, $prefix, $name, $desc, $pM, $pA, $pO, $maxG, $amenities, $img, $order
        );

        if ($stmt->execute()) { // Thành công
            $_SESSION['success'] = "Đã thêm phòng \"$name\" thành công.";
        } else { // Lỗi (vd: slug bị trùng do UNIQUE constraint)
            $_SESSION['error'] = 'Lỗi: ' . $conn->error;
        }
        $stmt->close();
        break;

    // ===== CẬP NHẬT PHÒNG =====
    case 'update':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') break;

        $id     = (int)($_POST['id'] ?? 0);
        $slug   = trim($_POST['slug'] ?? '');
        $prefix = trim($_POST['prefix'] ?? '');
        $name   = trim($_POST['name'] ?? '');
        $desc   = trim($_POST['description'] ?? '');
        $pM     = (int)($_POST['price_morning'] ?? 0);
        $pA     = (int)($_POST['price_afternoon'] ?? 0);
        $pO     = (int)($_POST['price_overnight'] ?? 0);
        $maxG   = (int)($_POST['max_guests'] ?? 4);
        $amenities = trim($_POST['amenities'] ?? '[]');
        $img    = trim($_POST['image_url'] ?? '');
        $order  = (int)($_POST['sort_order'] ?? 0);
        // isset() = checkbox có được tick không (tick → gửi value, không tick → không gửi)
        $active = isset($_POST['is_active']) ? 1 : 0; // 1 = hiện, 0 = ẩn

        // UPDATE = cập nhật hàng có id khớp
        $stmt = $conn->prepare(
            "UPDATE rooms SET slug=?, prefix=?, name=?, description=?,
             price_morning=?, price_afternoon=?, price_overnight=?,
             max_guests=?, amenities=?, image_url=?, sort_order=?, is_active=?
             WHERE id=?"
        );
        $stmt->bind_param('ssssiiiissiii',
            $slug, $prefix, $name, $desc, $pM, $pA, $pO,
            $maxG, $amenities, $img, $order, $active, $id
        );

        if ($stmt->execute()) {
            $_SESSION['success'] = "Đã cập nhật phòng \"$name\".";
        } else {
            $_SESSION['error'] = 'Lỗi: ' . $conn->error;
        }
        $stmt->close();
        break;

    // ===== XÓA PHÒNG =====
    case 'delete':
        $id = (int)($_GET['id'] ?? 0); // Lấy id từ URL (?id=5)
        if ($id > 0) {
            // DELETE = xóa hàng khỏi bảng
            // Bookings + slots liên quan cũng bị xóa nhờ ON DELETE CASCADE
            $stmt = $conn->prepare("DELETE FROM rooms WHERE id=?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['success'] = 'Đã xóa phòng.';
        }
        break;

    default: // Nếu action không khớp case nào
        $_SESSION['error'] = 'Hành động không hợp lệ.';
}

// Quay lại trang quản lý phòng
header('Location: ' . BASE_URL . 'pages/admin/rooms.php');
exit;
