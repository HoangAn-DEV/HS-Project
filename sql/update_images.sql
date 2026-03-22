-- ============================================================
-- update_images.sql — Cập nhật đường dẫn ảnh phòng
-- Chạy file này trong phpMyAdmin nếu bạn ĐÃ có database
-- (Không cần xóa database cũ)
-- ============================================================

UPDATE rooms SET image_url = 'assets/img/room-xine.jpg'      WHERE slug = 'xine';
UPDATE rooms SET image_url = 'assets/img/room-signature.jpg'  WHERE slug = 'signature';
UPDATE rooms SET image_url = 'assets/img/room-basic.jpg'      WHERE slug = 'basic';
UPDATE rooms SET image_url = 'assets/img/room-bliss.jpg'      WHERE slug = 'bliss';
UPDATE rooms SET image_url = 'assets/img/room-relax.jpg'      WHERE slug = 'relax';
UPDATE rooms SET image_url = 'assets/img/room-reup.jpg'       WHERE slug = 'reup';
UPDATE rooms SET image_url = 'assets/img/room-lit.jpg'        WHERE slug = 'lit';
