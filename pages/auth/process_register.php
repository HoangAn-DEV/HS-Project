<?php
/**
 * ============================================================
 * pages/auth/process_register.php — Xử lý đăng ký
 * ============================================================
 * Nhận dữ liệu từ form register.php → validate → lưu vào bảng users
 * Nếu thành công → chuyển về login.php
 * Nếu lỗi → quay lại register.php kèm thông báo
 */

require_once __DIR__ . '/../../includes/auth.php';

// Lấy dữ liệu từ form (POST) và loại bỏ khoảng trắng thừa
$name       = trim($_POST['name']       ?? ''); // Họ tên
$email      = trim($_POST['email']      ?? ''); // Email
$password   = trim($_POST['password']   ?? ''); // Mật khẩu
$repassword = trim($_POST['repassword'] ?? ''); // Nhập lại mật khẩu
$phone      = trim($_POST['phone']      ?? ''); // Số điện thoại
$address    = trim($_POST['address']    ?? ''); // Địa chỉ

// ---- VALIDATE (KIỂM TRA DỮ LIỆU) ----

// Kiểm tra các trường bắt buộc có rỗng không
if (empty($name) || empty($email) || empty($password)) {
    $_SESSION['error'] = 'Vui lòng nhập đầy đủ họ tên, email và mật khẩu.';
    header('Location: register.php'); // Quay lại trang đăng ký
    exit;
}

// Mật khẩu nhập 2 lần phải khớp nhau
if ($password !== $repassword) { // !== = khác (so sánh cả kiểu dữ liệu)
    $_SESSION['error'] = 'Mật khẩu nhập lại không khớp.';
    header('Location: register.php');
    exit;
}

// Mật khẩu tối thiểu 6 ký tự
if (strlen($password) < 6) { // strlen() = đếm số ký tự
    $_SESSION['error'] = 'Mật khẩu phải có ít nhất 6 ký tự.';
    header('Location: register.php');
    exit;
}

// Kiểm tra email đúng định dạng (có @ và .com/.vn/...)
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Email không hợp lệ.';
    header('Location: register.php');
    exit;
}

// ---- KIỂM TRA EMAIL ĐÃ TỒN TẠI CHƯA ----
$conn = db(); // Lấy kết nối database

// Tìm xem có user nào đã dùng email này chưa
$chk = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$chk->bind_param('s', $email); // 's' = string
$chk->execute();
$chk->store_result(); // Lưu kết quả để đếm số hàng
if ($chk->num_rows > 0) { // num_rows > 0 = đã có user dùng email này
    $chk->close();
    $_SESSION['error'] = 'Email này đã được đăng ký.';
    header('Location: register.php');
    exit;
}
$chk->close();

// ---- LƯU USER MỚI VÀO DATABASE ----

// password_hash() = mã hóa mật khẩu 1 chiều (không giải mã ngược được)
// QUAN TRỌNG: KHÔNG BAO GIỜ lưu mật khẩu gốc vào database!
// VD: 'admin123' → '$2y$10$ABC...' (chuỗi hash dài ~60 ký tự)
$hash = password_hash($password, PASSWORD_DEFAULT);

// Mặc định user mới có role = 'user' (không phải admin)
$role = 'user';

// Chèn user mới vào bảng users
// Có 6 dấu ? tương ứng 6 giá trị cần điền
$ins = $conn->prepare(
    "INSERT INTO users (name, email, password, phone, address, role) VALUES (?,?,?,?,?,?)"
);
// bind_param('ssssss', ...) = 6 tham số kiểu string
$ins->bind_param('ssssss', $name, $email, $hash, $phone, $address, $role);

// execute() trả về true nếu thành công, false nếu lỗi
if ($ins->execute()) {
    $ins->close();
    // Đặt thông báo thành công → trang login sẽ hiện
    $_SESSION['success'] = 'Đăng ký thành công! Vui lòng đăng nhập.';
    header('Location: login.php'); // Chuyển đến trang login
} else {
    $ins->close();
    $_SESSION['error'] = 'Đăng ký thất bại. Vui lòng thử lại.';
    header('Location: register.php');
}
exit;
