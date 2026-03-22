<?php
/**
 * ============================================================
 * includes/db.php — Kết nối MySQL
 * ============================================================
 * File này tạo 1 kết nối duy nhất đến database MySQL.
 * Các file khác gọi db() để lấy đối tượng kết nối.
 * 
 * Dùng pattern "Singleton": dù gọi db() bao nhiêu lần,
 * vẫn chỉ có 1 kết nối duy nhất (tiết kiệm tài nguyên).
 */

// define() = tạo hằng số, không thể thay đổi giá trị sau này
define('DB_HOST', 'localhost');  // Máy chủ MySQL (localhost = máy mình)
define('DB_USER', 'root');       // Tên user MySQL (XAMPP mặc định là 'root')
define('DB_PASS', '');           // Mật khẩu MySQL (XAMPP mặc định không có)
define('DB_NAME', 'skibidi_tolet'); // Tên database đã tạo trong phpMyAdmin
define('DB_CHAR', 'utf8mb4');    // Bộ mã ký tự (hỗ trợ tiếng Việt + emoji)

/**
 * Hàm db() — Trả về kết nối MySQL
 * 
 * Cách dùng ở file khác:
 *   $conn = db();                        // Lấy kết nối
 *   $conn->query("SELECT * FROM rooms"); // Chạy SQL
 * 
 * @return mysqli  Đối tượng kết nối MySQL
 */
function db(): mysqli // ': mysqli' = hàm trả về đối tượng kiểu mysqli
{
    // static = biến giữ giá trị giữa các lần gọi hàm
    // Lần đầu: $conn = null → tạo kết nối mới
    // Lần sau: $conn đã có giá trị → trả về luôn, không tạo lại
    static $conn = null;
    if ($conn !== null) return $conn; // !== kiểm tra khác null (cả kiểu + giá trị)

    // new mysqli() = tạo kết nối mới đến MySQL
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Kiểm tra kết nối có lỗi không
    if ($conn->connect_error) {
        // error_log() = ghi lỗi vào file log của PHP (không hiện cho user)
        error_log('[DB] Lỗi kết nối: ' . $conn->connect_error);
        // die() = dừng toàn bộ script và hiện thông báo lỗi
        die('<div style="font-family:sans-serif;padding:40px;color:#c62828">
              ⚠ Không thể kết nối database. Kiểm tra XAMPP đã bật MySQL chưa.
             </div>');
    }

    // set_charset() = đặt bộ mã ký tự cho kết nối (để tiếng Việt không bị lỗi)
    $conn->set_charset(DB_CHAR);
    return $conn; // Trả kết nối về cho nơi gọi hàm
}
