<?php
/**
 * ============================================================
 * pages/admin/rooms.php — Trang quản lý phòng (Admin)
 * ============================================================
 *
 * Chức năng:
 * - CRUD = Create (thêm) / Read (xem) / Update (sửa) / Delete (xóa) phòng
 * - Form thêm/sửa phòng ở trên, bảng danh sách phòng ở dưới
 * - Khi bấm "Sửa" → URL thêm ?edit=5 → form chuyển sang chế độ sửa
 * - Form submit gửi đến api/room_action.php để xử lý
 */

// Nạp auth.php → kiểm tra đăng nhập admin + kết nối DB + hàm tiện ích
require_once __DIR__ . '/../../includes/auth.php';
$conn = db(); // db() = lấy kết nối database

// ============================================================
// LẤY DANH SÁCH PHÒNG
// ============================================================
// ORDER BY sort_order ASC = sắp xếp theo thứ tự admin đã đặt (ASC = tăng dần)
// Nếu sort_order bằng nhau → sắp theo id
$rooms = $conn->query("SELECT * FROM rooms ORDER BY sort_order ASC, id ASC");

// ============================================================
// CHẾ ĐỘ SỬA PHÒNG (khi URL có ?edit=5)
// ============================================================
$editing = null; // null = đang ở chế độ THÊM MỚI (không phải sửa)

// isset() = kiểm tra tham số 'edit' có trên URL không
if (isset($_GET['edit'])) {
    // Lấy thông tin phòng cần sửa từ DB
    $stmt = $conn->prepare("SELECT * FROM rooms WHERE id=?");
    $eid = (int)$_GET['edit']; // Ép kiểu int để chống injection
    $stmt->bind_param('i', $eid); // 'i' = integer
    $stmt->execute();
    $editing = $stmt->get_result()->fetch_assoc(); // Lấy 1 hàng kết quả
    $stmt->close();
    // Nếu $editing có dữ liệu → form sẽ hiện chế độ "Chỉnh sửa"
    // Nếu $editing = null (không tìm thấy) → vẫn hiện form "Thêm mới"
}

// Đặt tiêu đề trang + menu active trên sidebar
$page_title = 'Quản lý phòng'; $active_page = 'rooms';

// Include layout admin (sidebar + topbar + flash message)
include __DIR__ . '/../../includes/admin_header.php';
?>

<!-- ============================================================ -->
<!-- FORM THÊM / SỬA PHÒNG                                       -->
<!-- Nếu $editing có dữ liệu → form sửa (action=update)          -->
<!-- Nếu $editing = null → form thêm mới (action=create)          -->
<!-- ============================================================ -->
<div class="admin-form" style="margin-bottom:32px;">
    <!-- Tiêu đề form thay đổi theo chế độ: thêm hoặc sửa -->
    <!-- Ternary operator (toán tử ba ngôi): điều_kiện ? đúng : sai -->
    <h3><?= $editing ? '✏️ Chỉnh sửa — '.e($editing['name']) : '➕ Thêm phòng mới' ?></h3>

    <!-- method="POST" = gửi dữ liệu ẩn (không hiện trên URL) -->
    <!-- action = URL xử lý form, kèm ?action=update hoặc ?action=create -->
    <!-- action = URL xử lý form, kèm ?action=update hoặc ?action=create -->
    <form method="POST" action="<?= BASE_URL ?>api/room_action.php?action=<?= $editing ? 'update' : 'create' ?>">
        <!-- Hidden input: nếu đang sửa, gửi kèm ID phòng để server biết sửa phòng nào -->
        <!-- type="hidden" = ẩn, user không thấy nhưng form vẫn gửi giá trị -->
        <?php if ($editing): ?><input type="hidden" name="id" value="<?= $editing['id'] ?>"><?php endif; ?>

        <!-- ===== Hàng 1: Tên phòng + Slug ===== -->
        <div class="form-row">
            <div class="form-group">
                <label>Tên phòng *</label>
                <!-- required = bắt buộc nhập, trình duyệt sẽ chặn submit nếu trống -->
                <!-- value = giá trị mặc định (lấy từ DB khi đang sửa) -->
                <input type="text" name="name" required value="<?= e($editing['name'] ?? '') ?>" placeholder="VD: Xi nê">
            </div>
            <div class="form-group">
                <label>Slug (URL) *</label>
                <!-- Slug = tên viết thường không dấu, dùng trong URL -->
                <!-- VD: "Xi nê" → slug = "xine" → URL: /rooms/xine -->
                <input type="text" name="slug" required value="<?= e($editing['slug'] ?? '') ?>" placeholder="VD: xine">
            </div>
        </div>

        <!-- ===== Hàng 2: Prefix + Thứ tự ===== -->
        <div class="form-row">
            <div class="form-group">
                <label>Prefix (mã ngắn)</label>
                <!-- Prefix = mã viết tắt, dùng trong slot code (VD: 'xn' → slot 'xn-m3') -->
                <!-- maxlength="5" = giới hạn tối đa 5 ký tự -->
                <input type="text" name="prefix" maxlength="5" value="<?= e($editing['prefix'] ?? '') ?>" placeholder="VD: xn">
            </div>
            <div class="form-group">
                <label>Thứ tự hiển thị</label>
                <!-- sort_order = thứ tự sắp xếp trên trang chủ. Số nhỏ → hiện trước -->
                <input type="number" name="sort_order" value="<?= $editing['sort_order'] ?? 0 ?>">
            </div>
        </div>

        <!-- ===== Hàng 3: Mô tả phòng ===== -->
        <div class="form-row full">
            <div class="form-group">
                <label>Mô tả phòng</label>
                <!-- textarea = ô nhập nhiều dòng (khác input chỉ 1 dòng) -->
                <textarea name="description" rows="3" placeholder="Mô tả ngắn gọn về phòng..."><?= e($editing['description'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- ===== PHẦN BẢNG GIÁ ===== -->
        <div style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:2px;color:var(--text-muted);margin:24px 0 14px;padding-top:18px;border-top:1px solid rgba(0,0,0,0.06);">Bảng giá</div>

        <div class="form-row">
            <div class="form-group">
                <label>Giá ca Sáng (đ)</label>
                <!-- type="number" = chỉ cho phép nhập số -->
                <input type="number" name="price_morning" value="<?= $editing['price_morning'] ?? 0 ?>">
            </div>
            <div class="form-group">
                <label>Giá ca Chiều (đ)</label>
                <input type="number" name="price_afternoon" value="<?= $editing['price_afternoon'] ?? 0 ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Giá ca Đêm (đ)</label>
                <input type="number" name="price_overnight" value="<?= $editing['price_overnight'] ?? 0 ?>">
            </div>
            <div class="form-group">
                <label>Số khách tối đa</label>
                <!-- min/max = giới hạn giá trị nhập (1-10) -->
                <input type="number" name="max_guests" min="1" max="10" value="<?= $editing['max_guests'] ?? 4 ?>">
            </div>
        </div>

        <!-- ===== PHẦN NÂNG CAO ===== -->
        <div style="font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:2px;color:var(--text-muted);margin:24px 0 14px;padding-top:18px;border-top:1px solid rgba(0,0,0,0.06);">Nâng cao</div>

        <div class="form-row full">
            <div class="form-group">
                <label>Tiện ích (JSON)</label>
                <!-- Tiện ích lưu dạng JSON array, VD: ["Wifi","Máy chiếu","Giường lớn"] -->
                <!-- JSON = JavaScript Object Notation, định dạng trao đổi dữ liệu phổ biến -->
                <input type="text" name="amenities" value='<?= e($editing['amenities'] ?? '[]') ?>' placeholder='["Wifi","Máy chiếu","Giường lớn"]'>
            </div>
        </div>
        <div class="form-row full">
            <div class="form-group">
                <label>Ảnh phòng (URL)</label>
                <!-- Đường dẫn ảnh phòng, VD: uploads/rooms/xine.jpg hoặc URL bên ngoài -->
                <input type="text" name="image_url" value="<?= e($editing['image_url'] ?? '') ?>" placeholder="VD: uploads/rooms/xine.jpg">
            </div>
        </div>

        <!-- Checkbox "Đang hoạt động" — chỉ hiện khi đang SỬA (không hiện khi thêm mới) -->
        <!-- Phòng mới mặc định active, chỉ cần tắt khi muốn ẩn phòng cũ -->
        <?php if ($editing): ?>
            <div class="form-row full">
                <div class="form-group checkbox-group">
                    <!-- checked = tick sẵn nếu phòng đang active -->
                    <input type="checkbox" name="is_active" id="is_active" <?= $editing['is_active'] ? 'checked' : '' ?>>
                    <label for="is_active">Đang hoạt động (hiển thị cho khách)</label>
                </div>
            </div>
        <?php endif; ?>

        <!-- Nút submit -->
        <div style="display:flex;gap:12px;margin-top:24px;padding-top:20px;border-top:1px solid rgba(0,0,0,0.06);">
            <button type="submit" class="act-btn primary" style="padding:11px 28px;font-size:0.875rem">
                <?= $editing ? '💾 Cập nhật phòng' : '➕ Thêm phòng' ?>
            </button>
            <!-- Nút "Hủy chỉnh sửa" — chỉ hiện khi đang sửa, bấm → quay về chế độ thêm mới -->
            <?php if ($editing): ?>
                <a href="rooms.php" class="act-btn" style="padding:11px 28px;font-size:0.875rem">Hủy chỉnh sửa</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- ============================================================ -->
<!-- BẢNG DANH SÁCH PHÒNG                                        -->
<!-- ============================================================ -->
<div class="admin-table-wrap">
    <div class="admin-table-header">
        <!-- num_rows = tổng số phòng trong kết quả query -->
        <h3>Danh sách phòng <span style="color:var(--text-muted);font-family:'DM Sans',sans-serif;font-size:0.85rem;font-weight:500;margin-left:6px">(<?= $rooms->num_rows ?>)</span></h3>
    </div>
    <div class="table-scroll">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên phòng</th>
                    <th>Slug</th>
                    <th>Ca sáng</th>
                    <th>Ca chiều</th>
                    <th>Ca đêm</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <!-- Lặp qua từng phòng để hiển thị 1 hàng trong bảng -->
            <?php while ($r = $rooms->fetch_assoc()): ?>
                <tr>
                    <td class="td-bold">#<?= $r['id'] ?></td>
                    <td class="td-bold"><?= e($r['name']) ?></td>
                    <!-- code tag = hiển thị kiểu code (font mono, nền xám nhạt) -->
                    <td class="td-muted"><code style="background:rgba(0,0,0,0.04);padding:3px 8px;border-radius:4px;font-size:0.78rem"><?= e($r['slug']) ?></code></td>
                    <!-- vnd() = format số tiền VNĐ, VD: 179000 → "179.000đ" -->
                    <td><?= vnd($r['price_morning']) ?></td>
                    <td><?= vnd($r['price_afternoon']) ?></td>
                    <td><?= vnd($r['price_overnight']) ?></td>
                    <td>
                        <!-- Badge trạng thái: xanh = hoạt động, đỏ = ẩn -->
                        <?= $r['is_active']
                            ? '<span class="badge badge-paid">Hoạt động</span>'
                            : '<span class="badge badge-canceled">Ẩn</span>' ?>
                    </td>
                    <td>
                        <div class="act-group">
                            <!-- Nút "Sửa": thêm ?edit=ID vào URL → form ở trên chuyển sang chế độ sửa -->
                            <a href="rooms.php?edit=<?= $r['id'] ?>" class="act-btn blue">✏️ Sửa</a>
                            <!-- Nút "Xóa": gọi api/room_action.php?action=delete&id=... -->
                            <!-- data-confirm = JS hiện modal xác nhận trước khi xóa -->
                            <a href="<?= BASE_URL ?>api/room_action.php?action=delete&id=<?= $r['id'] ?>" class="act-btn red" data-confirm="Bạn có chắc muốn xóa phòng '<?= e($r['name']) ?>'? Dữ liệu đặt phòng liên quan cũng sẽ bị xóa.">🗑 Xóa</a>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Include footer admin (đóng layout + modal confirm + JS admin)
include __DIR__ . '/../../includes/admin_footer.php';
?>