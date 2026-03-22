<?php
/**
 * ============================================================
 * pages/auth/process_login.php — Xử lý đăng nhập
 * ============================================================
 * File này KHÔNG hiển thị giao diện — chỉ xử lý logic:
 * 1. Nhận email + mật khẩu từ form login.php (qua $_POST)
 * 2. Tìm user trong database bằng email
 * 3. So sánh mật khẩu (dùng password_verify)
 * 4. Nếu đúng → lưu thông tin vào session → chuyển hướng
 *    Nếu sai → lưu lỗi vào session → quay lại trang login
 */

// Nạp auth.php → có hàm db(), flash(), hằng BASE_URL, session
require_once __DIR__ . '/../../includes/auth.php';

// trim() = bỏ khoảng trắng đầu cuối (tránh user vô tình gõ thừa)
// $_POST['email'] = giá trị user nhập vào ô email trong form
$email    = trim($_POST['email']    ?? ''); // ?? '' = nếu không có thì dùng ''
$password = trim($_POST['password'] ?? '');

// Kiểm tra nếu email hoặc mật khẩu rỗng
if (empty($email) || empty($password)) { // empty() = kiểm tra chuỗi rỗng
    $_SESSION['error'] = 'Vui lòng nhập đầy đủ email và mật khẩu.';
    header('Location: login.php'); // Quay lại trang login (cùng thư mục)
    exit; // Dừng script
}

// ---- TÌM USER TRONG DATABASE ----
// prepare() = tạo câu SQL có chỗ trống (?) để điền sau
// Lý do dùng prepare: chống SQL Injection (hacker chèn mã SQL qua input)
// VD: Nếu dùng "WHERE email='$email'" → hacker nhập ' OR 1=1 -- → hack được
//     Dùng prepare() + bind_param() → an toàn, không hack được
$stmt = db()->prepare("SELECT id, name, password, role FROM users WHERE email = ? LIMIT 1");
// bind_param('s', $email) = điền giá trị $email vào dấu ? ('s' = kiểu string)
$stmt->bind_param('s', $email);
$stmt->execute();         // Chạy câu SQL
$user = $stmt->get_result()->fetch_assoc(); // Lấy 1 hàng kết quả dạng mảng
$stmt->close();           // Đóng prepared statement (giải phóng bộ nhớ)

// Nếu không tìm thấy user nào có email này
if (!$user) { // !$user = $user là null/false (không có kết quả)
    $_SESSION['error'] = 'Email không tồn tại trong hệ thống.';
    header('Location: login.php');
    exit;
}

// ---- SO SÁNH MẬT KHẨU ----
// password_verify() so sánh mật khẩu gốc với hash đã lưu trong DB
// Trong DB, mật khẩu KHÔNG lưu nguyên gốc mà lưu dạng hash (mã hóa 1 chiều)
// VD: 'admin123' → '$2y$10$ABC...' (không thể giải mã ngược)
if (!password_verify($password, $user['password'])) {
    $_SESSION['error'] = 'Mật khẩu không chính xác.';
    header('Location: login.php');
    exit;
}

// ---- ĐĂNG NHẬP THÀNH CÔNG ----
// session_regenerate_id() = tạo session ID mới (bảo mật, chống session fixation)
session_regenerate_id(true);

// Lưu thông tin user vào session → các trang khác dùng is_logged_in() kiểm tra
$_SESSION['user_id']   = $user['id'];    // ID user (số nguyên)
$_SESSION['user_name'] = $user['name'];  // Tên hiển thị
$_SESSION['role']      = $user['role'];  // Quyền: 'admin' hoặc 'user'

// Admin → vào trang quản trị, User → về trang chủ
if ($user['role'] === 'admin') {
    header('Location: ' . BASE_URL . 'pages/admin/dashboard.php');
} else {
    header('Location: ' . BASE_URL); // BASE_URL = '/homestay/' = trang chủ
}
exit;
