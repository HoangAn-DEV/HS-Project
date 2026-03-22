-- ============================================================
-- Thêm cột lưu đường dẫn ảnh CCCD vào bảng bookings
-- Chạy 1 lần để cập nhật DB hiện tại
-- ============================================================

ALTER TABLE bookings
    ADD COLUMN cccd_truoc VARCHAR(255) DEFAULT NULL AFTER tong_cuoi,
    ADD COLUMN cccd_sau   VARCHAR(255) DEFAULT NULL AFTER cccd_truoc;
