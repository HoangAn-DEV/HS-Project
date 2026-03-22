<?php
/**
 * ============================================================
 * includes/config.php — Cấu hình đường dẫn gốc (Base URL)
 * ============================================================
 * VẤN ĐỀ: Project nằm ở localhost/homestay/ nhưng link CSS kiểu
 * "/assets/css/..." sẽ trỏ nhầm về localhost/assets/...
 * GIẢI PHÁP: Tự phát hiện thư mục → tạo hằng BASE_URL = '/homestay/'
 */

// define() tạo hằng số — giá trị không thể thay đổi sau khi tạo
// PROJECT_FOLDER = tên thư mục project trong htdocs
define('PROJECT_FOLDER', 'homestay'); // Đổi nếu bạn đặt tên thư mục khác

/**
 * Tự phát hiện đường dẫn gốc của project
 * VD: Project ở C:/xampp/htdocs/homestay → trả về '/homestay/'
 */
function detect_base_url(): string // ': string' = hàm này trả về chuỗi
{
    // $_SERVER['DOCUMENT_ROOT'] = thư mục gốc web (vd: C:/xampp/htdocs)
    // realpath() = chuyển đường dẫn thành tuyệt đối, bỏ ../ và ./
    $doc_root    = realpath($_SERVER['DOCUMENT_ROOT'] ?? ''); // ?? '' = nếu null thì dùng ''
    $current_dir = realpath(__DIR__ . '/..'); // __DIR__ = thư mục chứa file này (includes/)
    //                                          /.. = lùi 1 cấp → thư mục homestay/

    // Kiểm tra thư mục project có nằm trong thư mục gốc web không
    if ($doc_root && $current_dir && str_starts_with($current_dir, $doc_root)) {
        // Cắt bỏ phần doc_root, còn lại '/homestay'
        $relative = str_replace('\\', '/', substr($current_dir, strlen($doc_root)));
        return rtrim($relative, '/') . '/'; // Thêm / cuối → '/homestay/'
    }

    return '/' . PROJECT_FOLDER . '/'; // Fallback nếu không phát hiện được
}

// Chỉ define 1 lần (tránh lỗi nếu file được include nhiều lần)
if (!defined('BASE_URL')) {
    define('BASE_URL', detect_base_url());
    // Từ giờ, viết BASE_URL ở bất kỳ đâu sẽ ra '/homestay/'
    // VD: BASE_URL . 'assets/css/main.css' → '/homestay/assets/css/main.css'
}
