<?php
/**
 * ============================================================
 * includes/auth.php — Xác thực & phân quyền người dùng
 * ============================================================
 * File này chứa các hàm kiểm tra đăng nhập, quyền admin, v.v.
 * Hầu hết các trang đều include file này ở đầu.
 * 
 * Khi include auth.php, nó tự động:
 * 1. Nạp config.php (tạo BASE_URL)
 * 2. Nạp db.php (hàm kết nối database)
 * 3. Khởi tạo session (để lưu trạng thái đăng nhập)
 */

// require_once = nạp file 1 lần duy nhất (nếu đã nạp thì bỏ qua)
// __DIR__ = thư mục chứa file hiện tại (includes/)
require_once __DIR__ . '/config.php'; // → Tạo hằng BASE_URL
require_once __DIR__ . '/db.php';     // → Tạo hàm db()

// Session = cách PHP nhớ thông tin user giữa các trang
// VD: user đăng nhập ở trang A, sang trang B vẫn biết đã đăng nhập
// session_status() kiểm tra session đã bắt đầu chưa
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Bắt đầu session (phải gọi TRƯỚC khi dùng $_SESSION)
}

/**
 * Kiểm tra user đã đăng nhập chưa
 * 
 * Khi user đăng nhập thành công, process_login.php lưu:
 *   $_SESSION['user_id'] = 1 (ID user trong DB)
 * Hàm này kiểm tra biến đó có tồn tại và > 0 không
 * 
 * @return bool  true = đã đăng nhập, false = chưa
 */
function is_logged_in(): bool
{
    // isset() = kiểm tra biến có tồn tại không
    // && = AND (cả 2 điều kiện đều phải đúng)
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

/**
 * Kiểm tra user hiện tại có phải admin không
 * 
 * Khi đăng nhập, process_login.php cũng lưu:
 *   $_SESSION['role'] = 'admin' hoặc 'user'
 * 
 * @return bool  true = là admin, false = không phải
 */
function is_admin(): bool
{
    // Phải đăng nhập VÀ role phải là 'admin'
    // ?? '' = nếu $_SESSION['role'] không tồn tại thì dùng '' (chuỗi rỗng)
    return is_logged_in() && ($_SESSION['role'] ?? '') === 'admin';
}

/**
 * Lấy thông tin user đang đăng nhập
 * 
 * @return array|null  Mảng thông tin user, hoặc null nếu chưa đăng nhập
 * Ví dụ trả về: ['id' => 1, 'name' => 'Admin', 'role' => 'admin']
 */
function current_user(): ?array // ?array = có thể trả về array HOẶC null
{
    if (!is_logged_in()) return null; // ! = NOT (đảo ngược)
    return [
        'id'   => $_SESSION['user_id'],       // ID user trong database
        'name' => $_SESSION['user_name'] ?? '', // Tên hiển thị
        'role' => $_SESSION['role'] ?? 'user',  // Quyền: 'admin' hoặc 'user'
    ];
}

/**
 * Bắt buộc đăng nhập — gọi ở đầu trang cần bảo vệ
 * Nếu chưa đăng nhập → chuyển về trang login
 * 
 * VD dùng: require_login(); (đặt ở đầu my_bookings.php)
 */
function require_login(): void // void = hàm không trả về gì
{
    if (!is_logged_in()) {
        // Lưu thông báo lỗi vào session → trang login sẽ hiển thị
        $_SESSION['error'] = 'Vui lòng đăng nhập để tiếp tục.';
        // header('Location: ...') = chuyển hướng trình duyệt đến URL khác
        // BASE_URL = '/homestay/' → link đầy đủ: '/homestay/pages/auth/login.php'
        header('Location: ' . BASE_URL . 'pages/auth/login.php');
        exit; // Dừng script ngay (quan trọng! không có exit thì code tiếp tục chạy)
    }
}

/**
 * Bắt buộc quyền admin — gọi ở đầu các trang admin
 * Nếu không phải admin → chuyển về trang chủ
 * 
 * VD dùng: require_admin(); (đặt ở đầu dashboard.php)
 */
function require_admin(): void
{
    require_login(); // Trước hết phải đăng nhập đã
    if (!is_admin()) {
        $_SESSION['error'] = 'Bạn không có quyền truy cập trang này.';
        header('Location: ' . BASE_URL . 'index.php'); // Về trang chủ
        exit;
    }
}

/**
 * Escape HTML — chống tấn công XSS
 * 
 * XSS = hacker nhập mã HTML/JS vào form → hiện trên trang web
 * VD: user nhập tên "<script>alert('hack')</script>"
 * Không escape → trình duyệt chạy code JS đó!
 * Có escape → hiện nguyên văn text, không chạy code
 * 
 * @param string $str  Chuỗi cần làm sạch
 * @return string      Chuỗi đã an toàn
 */
function e(string $str): string
{
    // htmlspecialchars() chuyển < thành &lt;, > thành &gt;, v.v.
    // trim() bỏ khoảng trắng đầu/cuối
    // ENT_QUOTES = escape cả dấu ' và "
    // 'UTF-8' = bộ mã ký tự tiếng Việt
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

/**
 * Định dạng số tiền VNĐ
 * VD: vnd(179000) → "179.000đ"
 * 
 * @param int $amount  Số tiền
 * @return string      Chuỗi đã format
 */
function vnd(int $amount): string
{
    // number_format(số, số_thập_phân, dấu_thập_phân, dấu_phân_cách_hàng_nghìn)
    return number_format($amount, 0, ',', '.') . 'đ';
}

/**
 * Flash message — lấy thông báo 1 lần rồi xóa
 * 
 * Cách dùng:
 *   Trang A: $_SESSION['error'] = 'Có lỗi!';
 *   Trang B: $msg = flash('error'); // Lấy thông báo + tự xóa khỏi session
 * 
 * @param string $key   Tên key: 'error' hoặc 'success'
 * @return string|null  Nội dung thông báo, hoặc null nếu không có
 */
function flash(string $key): ?string
{
    if (isset($_SESSION[$key])) {      // Kiểm tra có thông báo không
        $msg = $_SESSION[$key];        // Lấy nội dung
        unset($_SESSION[$key]);        // Xóa khỏi session (chỉ hiện 1 lần)
        return $msg;                   // Trả về nội dung
    }
    return null; // Không có thông báo
}
