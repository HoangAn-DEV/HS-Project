-- ============================================================
-- SKIBIDI TOLET HOMESTAY — Database Schema
-- Chạy file này trong phpMyAdmin hoặc MySQL CLI để tạo DB
-- ============================================================

-- Tạo database
CREATE DATABASE IF NOT EXISTS skibidi_tolet
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE skibidi_tolet;

-- ============================================================
-- 1. BẢNG USERS — Quản lý tài khoản đăng nhập
-- role: 'admin' hoặc 'user'
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,           -- Lưu hash bcrypt
    phone       VARCHAR(20)  DEFAULT NULL,
    address     VARCHAR(255) DEFAULT NULL,
    role        ENUM('admin','user') NOT NULL DEFAULT 'user',
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tạo tài khoản admin mặc định (mật khẩu: admin123)
INSERT INTO users (name, email, password, role) VALUES
('Admin', 'admin@skibidi.com', '$2b$12$A17gcVsDFNQS7NlnZIBelO6Nuf6s98evDdUBh1aAvWwNoiVfiywlS', 'admin');

-- ============================================================
-- 2. BẢNG ROOMS — Thông tin phòng (admin có thể thêm/sửa/xóa)
-- ============================================================
CREATE TABLE IF NOT EXISTS rooms (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    slug        VARCHAR(50)  NOT NULL UNIQUE,    -- VD: 'xine', 'signature'
    prefix      VARCHAR(5)   NOT NULL,           -- VD: 'xn', 'sg' (dùng cho slot code)
    name        VARCHAR(100) NOT NULL,           -- Tên hiển thị
    description TEXT         DEFAULT NULL,       -- Mô tả phòng
    price_morning   INT NOT NULL DEFAULT 0,      -- Giá ca sáng (VNĐ)
    price_afternoon INT NOT NULL DEFAULT 0,      -- Giá ca chiều
    price_overnight INT NOT NULL DEFAULT 0,      -- Giá ca đêm
    max_guests  INT NOT NULL DEFAULT 4,          -- Số khách tối đa
    amenities   JSON         DEFAULT NULL,       -- Tiện ích ["Máy chiếu","Wifi",...]
    image_url   VARCHAR(255) DEFAULT NULL,       -- Ảnh đại diện phòng
    is_active   TINYINT(1)   NOT NULL DEFAULT 1, -- 1=hiển thị, 0=ẩn
    sort_order  INT NOT NULL DEFAULT 0,          -- Thứ tự sắp xếp
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Dữ liệu 7 phòng mặc định
INSERT INTO rooms (slug, prefix, name, description, price_morning, price_afternoon, price_overnight, amenities, image_url, sort_order) VALUES
('xine',      'xn', 'Xi nê',         'Trải nghiệm điện ảnh đỉnh cao với hình ảnh sắc nét tiêu chuẩn rạp phim, hoàn hảo cho một tối thư giãn.',   179000, 179000, 319000, '["Máy chiếu","Netflix chính chủ","Giường lớn","Wifi tốc độ cao"]', 'assets/img/room-xine.jpg', 1),
('signature', 'sg', 'Signature',      'Đẳng cấp khác biệt với phong cách Bắc Âu Scandinavia với ban công thoáng đãng và bồn tắm riêng biệt.',     199000, 199000, 359000, '["Bồn tắm","Máy chiếu","Ban công","Ghế lười"]',                   'assets/img/room-signature.jpg', 2),
('basic',     'bs', 'Basic',          'Phong cách tối giản, tinh tế với đầy đủ tiện nghi, đáp ứng mọi nhu cầu thư giãn nhẹ nhàng của bạn.',        169000, 169000, 299000, '["Boardgame","Netflix chính chủ","Bàn ăn"]',                      'assets/img/room-basic.jpg', 3),
('bliss',     'bl', 'Bliss',          'Một không gian ấm áp, thanh bình giúp bạn tận hưởng những phút giây thư thái tuyệt đối.',                   189000, 189000, 329000, '["Ghế đọc sách","Cửa sổ lớn","Máy chiếu","Tủ lạnh"]',            'assets/img/room-bliss.jpg', 4),
('relax',     'rx', 'Relax',          'Không gian thư giãn tối ưu, nơi mọi căng thẳng tan biến trong sự tĩnh lặng và dịu êm.',                     199000, 199000, 339000, '["Bồn tắm","Ban công","Ghế lười","Máy chiếu"]',                   'assets/img/room-relax.jpg', 5),
('reup',      'ru', 'Re-up – Gaming', 'Nạp lại năng lượng cho bản thân trong một không gian tinh tế và tràn đầy cảm hứng mới mẻ.',                 199000, 199000, 359000, '["Máy chơi game","Ghế lười","Máy chiếu","Boardgame"]',            'assets/img/room-reup.jpg', 6),
('lit',       'lt', 'Lit',            'Không gian sống động, trẻ trung và cá tính, nơi mọi khoảnh khắc đều đáng nhớ.',                              189000, 189000, 339000, '["Đèn neon","Loa bluetooth","Máy chiếu","Mini bar"]',             'assets/img/room-lit.jpg', 7);

-- ============================================================
-- 3. BẢNG BOOKINGS — Đơn đặt phòng
-- ============================================================
CREATE TABLE IF NOT EXISTS bookings (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT          DEFAULT NULL,       -- NULL nếu khách vãng lai
    room_id     INT          NOT NULL,           -- FK → rooms.id
    room_slug   VARCHAR(50)  NOT NULL,           -- Cache slug phòng
    room_name   VARCHAR(100) NOT NULL,           -- Cache tên phòng
    ho_ten      VARCHAR(100) NOT NULL,
    so_dt       VARCHAR(20)  NOT NULL,
    email       VARCHAR(150) DEFAULT NULL,
    so_khach    INT          NOT NULL DEFAULT 2,
    tien_sang   INT          NOT NULL DEFAULT 0,
    tien_chieu  INT          NOT NULL DEFAULT 0,
    tien_dem    INT          NOT NULL DEFAULT 0,
    phu_thu     INT          NOT NULL DEFAULT 0,
    tong_cuoi   INT          NOT NULL DEFAULT 0,
    trang_thai  ENUM('cho_xac_nhan','da_thanh_toan','da_huy')
                NOT NULL DEFAULT 'cho_xac_nhan',
    cccd_truoc  VARCHAR(255) DEFAULT NULL,       -- Đường dẫn ảnh CCCD mặt trước
    cccd_sau    VARCHAR(255) DEFAULT NULL,       -- Đường dẫn ảnh CCCD mặt sau
    ghi_chu     TEXT         DEFAULT NULL,       -- Admin ghi chú thêm
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_user    (user_id),
    INDEX idx_room    (room_id),
    INDEX idx_phone   (so_dt),
    INDEX idx_status  (trang_thai),
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 4. BẢNG BOOKING_SLOTS — Chi tiết ca đã đặt
-- ============================================================
CREATE TABLE IF NOT EXISTS booking_slots (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    booking_id  INT          NOT NULL,           -- FK → bookings.id
    room_id     INT          NOT NULL,           -- FK → rooms.id
    room_slug   VARCHAR(50)  NOT NULL,
    loai_ca     ENUM('sang','chieu','dem') NOT NULL,
    col_ngay    INT          NOT NULL,           -- 2=Thứ 2 ... 8=CN
    slot_code   VARCHAR(20)  NOT NULL,           -- VD: 'xn-m3'
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_booking (booking_id),
    INDEX idx_lookup  (room_slug, loai_ca, col_ngay),
    UNIQUE KEY uq_slot (room_slug, loai_ca, col_ngay), -- Không trùng lịch
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id)    REFERENCES rooms(id)    ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 5. VIEW — Lấy danh sách slot đã đặt (dùng cho trang chủ)
-- Chỉ hiển thị slot thuộc đơn chưa bị hủy
-- ============================================================
CREATE OR REPLACE VIEW v_booked_slots AS
SELECT
    bs.room_slug  AS phong_id,
    bs.loai_ca,
    bs.col_ngay
FROM booking_slots bs
JOIN bookings b ON b.id = bs.booking_id
WHERE b.trang_thai != 'da_huy';

-- ============================================================
-- 6. BẢNG SETTINGS — Cấu hình hệ thống (mở rộng tương lai)
-- ============================================================
CREATE TABLE IF NOT EXISTS settings (
    setting_key   VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NOT NULL,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Cấu hình mặc định
INSERT INTO settings (setting_key, setting_value) VALUES
('site_name',       'SKIBIDI TOLET'),
('surcharge_per_guest', '50000'),
('surcharge_threshold', '2'),
('bank_name',       'MB Bank'),
('bank_account',    '0965544925'),
('bank_holder',     'DUONG HOANG AN'),
('contact_zalo',    '0399190522'),
('contact_address', '256 Đ. Nguyễn Văn Cừ, An Hoà, Ninh Kiều, Cần Thơ');
