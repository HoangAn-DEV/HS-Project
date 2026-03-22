/**
 * ============================================================
 * main.js — JavaScript chính cho trang public
 * ============================================================
 * File này xử lý:
 * 1. Menu mobile (hamburger ☰)
 * 2. Modal đặt phòng (tóm tắt giá real-time)
 * 3. Validate form đăng nhập / đăng ký
 */

// ===== 1. MENU MOBILE =====
// DOMContentLoaded = chạy code khi HTML đã load xong (chưa cần ảnh)
document.addEventListener('DOMContentLoaded', () => {

    // getElementById() = tìm phần tử HTML có id="menuToggle"
    const toggle = document.getElementById('menuToggle'); // Nút ☰
    const nav    = document.getElementById('navMenu');    // Menu nav

    // Kiểm tra cả 2 phần tử tồn tại (tránh lỗi ở trang không có nav)
    if (toggle && nav) {
        // addEventListener('click', ...) = khi bấm vào toggle, chạy hàm
        toggle.addEventListener('click', () => {
            // classList.toggle() = thêm class nếu chưa có, bỏ nếu đã có
            // 'active' → CSS sẽ hiện/ẩn menu dựa trên class này
            toggle.classList.toggle('active'); // Đổi icon ☰ ↔ ✕
            nav.classList.toggle('active');    // Hiện/ẩn menu
        });

        // Khi bấm vào link trong menu → đóng menu lại
        // querySelectorAll('a') = lấy tất cả thẻ <a> trong nav
        nav.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                toggle.classList.remove('active'); // Bỏ class 'active'
                nav.classList.remove('active');
            });
        });
    }

});


// ===== 2. MODAL ĐẶT PHÒNG =====

// ROOM_PRICES = object chứa giá phòng, đọc từ data attribute trên HTML
// VD: <div class="modal-overlay" data-phong="xine" data-gia-sang="179000" ...>
const ROOM_PRICES = {};

// Tìm tất cả modal có attribute data-phong → lấy giá
document.querySelectorAll('.modal-overlay[data-phong]').forEach(modal => {
    const slug = modal.dataset.phong;     // dataset = đọc data-xxx attribute
    const sang  = parseInt(modal.dataset.giaSang  || 0); // parseInt = chuyển text→số
    const chieu = parseInt(modal.dataset.giaChieu || 0);
    const dem   = parseInt(modal.dataset.giaDem   || 0);
    if (slug && sang) {
        ROOM_PRICES[slug] = { sang, chieu, dem }; // Lưu giá vào object
    }
});

/**
 * Định dạng số thành tiền VNĐ
 * VD: vnd(179000) → "179.000đ"
 */
function vnd(amount) {
    // toLocaleString('vi-VN') = format theo locale Việt Nam (dấu . ngăn hàng nghìn)
    return amount.toLocaleString('vi-VN') + 'đ';
}

/**
 * Reset modal về trạng thái ban đầu (bỏ tick, xóa form, reset giá)
 * Gọi khi: đóng modal, bấm nút ✕, bấm ESC
 */
function resetModal(modal) {
    // Bỏ tick tất cả checkbox giờ + đổi label về "Trống"
    modal.querySelectorAll('input.gio-cb').forEach(cb => {
        cb.checked = false; // Bỏ tick
        const label = cb.nextElementSibling; // Thẻ <label> ngay sau <input>
        if (label) label.textContent = 'Trống'; // Đổi text
    });

    // reset() = xóa hết giá trị trong form (họ tên, SĐT, email...)
    const form = modal.querySelector('form');
    if (form) form.reset();

    // Reset bảng tóm tắt về 0
    const summary = modal.querySelector('.price-summary');
    if (!summary) return;
    // Các class: cnt-sang, tien-sang, cnt-chieu, ... (đặt trong HTML)
    ['cnt-sang','tien-sang','cnt-chieu','tien-chieu',
     'cnt-dem','tien-dem','cnt-total','tien-total'].forEach(cls => {
        const el = summary.querySelector('.' + cls);
        // class bắt đầu bằng 'cnt' = số lượng (hiện 0), còn lại = tiền (hiện 0đ)
        if (el) el.textContent = cls.startsWith('cnt') ? '0' : '0đ';
    });
}

/**
 * Đóng modal: reset form + xóa hash URL
 * URL hash (#modal-xine) kích hoạt CSS :target để hiện modal
 * Xóa hash → CSS :target không match → modal ẩn
 */
function closeModal(modal) {
    resetModal(modal);
    location.hash = ''; // Xóa #xxx khỏi URL
}

/**
 * Cập nhật bảng tóm tắt giá real-time
 * Gọi mỗi khi user tick/bỏ tick checkbox hoặc đổi số khách
 */
function updateSummary(modal) {
    const slug    = modal.dataset.phong;      // VD: 'xine'
    const prices  = ROOM_PRICES[slug];        // VD: {sang: 179000, chieu: 179000, dem: 319000}
    const summary = modal.querySelector('.price-summary');
    if (!summary || !prices) return;

    // Đếm số checkbox đã tick trong từng hàng ca
    // 'tr.row-morning input.gio-cb:checked' = checkbox đã tick trong hàng sáng
    const countSang  = modal.querySelectorAll('tr.row-morning   input.gio-cb:checked').length;
    const countChieu = modal.querySelectorAll('tr.row-afternoon input.gio-cb:checked').length;
    const countDem   = modal.querySelectorAll('tr.row-overnight input.gio-cb:checked').length;
    const totalSlots = countSang + countChieu + countDem;

    // Tính tiền từng ca
    const tienSang  = countSang  * prices.sang;
    const tienChieu = countChieu * prices.chieu;
    const tienDem   = countDem   * prices.dem;

    // Lấy số khách từ dropdown <select>
    // ?. = optional chaining (nếu null thì không lỗi, trả undefined)
    const soKhach = parseInt(modal.querySelector('.so-khach-select')?.value || 2);

    // Phụ thu: >2 khách + có chọn ít nhất 1 ca → tính
    const phuThu  = (totalSlots > 0 && soKhach > 2) ? (soKhach - 2) * 50000 : 0;
    const tongTien = tienSang + tienChieu + tienDem + phuThu;

    // Cập nhật DOM (hiển thị lên giao diện)
    summary.querySelector('.cnt-sang').textContent    = countSang;
    summary.querySelector('.tien-sang').textContent   = vnd(tienSang);
    summary.querySelector('.cnt-chieu').textContent   = countChieu;
    summary.querySelector('.tien-chieu').textContent  = vnd(tienChieu);
    summary.querySelector('.cnt-dem').textContent     = countDem;
    summary.querySelector('.tien-dem').textContent    = vnd(tienDem);
    summary.querySelector('.cnt-total').textContent   = totalSlots;
    summary.querySelector('.tien-total').textContent  = vnd(tongTien);

    // Đổi label checkbox: "Trống" ↔ "Đang chọn"
    modal.querySelectorAll('input.gio-cb').forEach(cb => {
        const label = cb.nextElementSibling;
        if (label) label.textContent = cb.checked ? 'Đang chọn' : 'Trống';
    });
}

// ===== KHỞI TẠO SỰ KIỆN CHO MODAL =====
document.addEventListener('DOMContentLoaded', () => {

    // Lặp qua từng modal → gắn sự kiện
    document.querySelectorAll('.modal-overlay').forEach(modal => {

        // Khi tick/bỏ tick checkbox → cập nhật tóm tắt
        modal.querySelectorAll('input.gio-cb').forEach(cb => {
            cb.addEventListener('change', () => updateSummary(modal));
        });

        // Khi đổi số khách → tính lại (có thể thay đổi phụ thu)
        modal.querySelector('.so-khach-select')
            ?.addEventListener('change', () => updateSummary(modal));

        // Nút ✕ đóng modal → reset form
        modal.querySelector('.close-btn')
            ?.addEventListener('click', () => resetModal(modal));
    });

    // Bấm vào vùng tối (overlay) bên ngoài popup → đóng modal
    document.addEventListener('click', e => {
        // e.target = phần tử user bấm vào
        if (e.target.classList.contains('modal-overlay')) {
            closeModal(e.target);
        }
    });

    // Bấm phím ESC → đóng modal đang mở
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            // :target = phần tử có id khớp với hash URL hiện tại
            const active = document.querySelector('.modal-overlay:target');
            if (active) closeModal(active);
        }
    });

    // Khi bấm nút Back trình duyệt → hash bị xóa → reset tất cả modal
    window.addEventListener('hashchange', () => {
        if (!window.location.hash) { // hash rỗng = không có modal nào mở
            document.querySelectorAll('.modal-overlay').forEach(resetModal);
        }
    });
});


// ===== 3. VALIDATE FORM ĐĂNG KÝ =====
// Kiểm tra dữ liệu TRƯỚC KHI gửi lên server (nhanh hơn, UX tốt hơn)
const registerForm = document.getElementById('registerForm');
if (registerForm) {
    registerForm.addEventListener('submit', function (e) {
        let valid = true; // Mặc định OK, sai ở đâu thì đổi thành false

        // Lấy giá trị từ các ô input
        const name   = document.getElementById('name')?.value.trim();
        const email  = document.getElementById('email')?.value.trim();
        const pass   = document.getElementById('password')?.value;
        const repass = document.getElementById('repassword')?.value;
        const phone  = document.getElementById('phone')?.value.trim();

        // Helper: hiện/xóa lỗi cho 1 trường
        const showErr = (id, msg) => {
            const el = document.getElementById(id);
            if (el) el.textContent = msg; // Đổi nội dung thẻ <span>
        };

        // Kiểm tra họ tên
        showErr('nameError', name ? '' : 'Vui lòng nhập họ tên');
        if (!name) valid = false;

        // Kiểm tra email bằng regex (biểu thức chính quy)
        // /^...$/  = pattern; test() = kiểm tra chuỗi có khớp pattern không
        const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        showErr('emailError', emailOk ? '' : 'Email không hợp lệ');
        if (!emailOk) valid = false;

        // Mật khẩu >= 6 ký tự
        showErr('passError', pass.length >= 6 ? '' : 'Mật khẩu >= 6 ký tự');
        if (pass.length < 6) valid = false;

        // Nhập lại mật khẩu phải khớp
        showErr('repassError', pass === repass ? '' : 'Mật khẩu không khớp');
        if (pass !== repass) valid = false;

        // SĐT nếu có nhập → phải đúng 10 số
        if (phone && !/^[0-9]{10}$/.test(phone)) {
            showErr('phoneError', 'SĐT phải 10 số');
            valid = false;
        } else {
            showErr('phoneError', '');
        }

        // Nếu có lỗi → NGĂN form gửi đi (e.preventDefault())
        if (!valid) e.preventDefault();
    });
}


// ===== 4. VALIDATE FORM ĐĂNG NHẬP =====
const loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', function (e) {
        let valid = true;

        const email = document.getElementById('loginEmail')?.value.trim();
        const pass  = document.getElementById('loginPass')?.value;

        const showErr = (id, msg) => {
            const el = document.getElementById(id);
            if (el) el.textContent = msg;
        };

        showErr('loginEmailError', email ? '' : 'Nhập email');
        if (!email) valid = false;

        showErr('loginPassError', pass ? '' : 'Nhập mật khẩu');
        if (!pass) valid = false;

        if (!valid) e.preventDefault(); // Ngăn gửi form nếu thiếu thông tin
    });
}


// ===== 5. PREVIEW ẢNH CCCD =====

/**
 * previewCCCD() — Khi user chọn ảnh CCCD → hiện ảnh preview trong ô upload
 *
 * Cách hoạt động:
 * 1. User bấm vào ô upload → mở hộp thoại chọn file
 * 2. Chọn ảnh → trình duyệt gọi onchange="previewCCCD(this)"
 * 3. Hàm này đọc file → tạo URL tạm → gán vào <img> preview
 *
 * @param {HTMLInputElement} input — Thẻ <input type="file"> vừa chọn ảnh
 */
function previewCCCD(input) {
    // input.parentElement = thẻ .upload-box chứa input này
    const box     = input.parentElement;
    // Tìm các phần tử con trong ô upload
    const preview = box.querySelector('.cccd-preview');  // Thẻ <img> preview
    const text    = box.querySelector('.upload-text');    // Text "📷 Mặt trước"
    const removeBtn = box.querySelector('.cccd-remove'); // Nút ✕ xóa ảnh

    // input.files = danh sách file user đã chọn
    // input.files[0] = file đầu tiên (chỉ cho chọn 1)
    if (input.files && input.files[0]) {
        // FileReader = API đọc file trên trình duyệt (không gửi lên server)
        const reader = new FileReader();

        // onload = chạy sau khi đọc file xong
        reader.onload = function(e) {
            // e.target.result = chuỗi data:image/... (ảnh dạng base64)
            preview.src = e.target.result;    // Gán ảnh vào <img>
            preview.style.display = 'block';  // Hiện ảnh
            text.style.display = 'none';      // Ẩn text "📷 Mặt trước"
            removeBtn.style.display = 'flex'; // Hiện nút ✕
            box.classList.add('has-image');    // Thêm class → CSS đổi viền xanh
        };

        // Bắt đầu đọc file dưới dạng Data URL (base64)
        reader.readAsDataURL(input.files[0]);
    }
}

/**
 * removeCCCD() — Xóa ảnh preview, trả ô upload về trạng thái ban đầu
 *
 * @param {HTMLButtonElement} btn — Nút ✕ được bấm
 */
function removeCCCD(btn) {
    const box     = btn.parentElement;
    const preview = box.querySelector('.cccd-preview');
    const text    = box.querySelector('.upload-text');
    const input   = box.querySelector('input[type=file]');

    // Reset tất cả về trạng thái ban đầu
    preview.src = '';                     // Xóa ảnh
    preview.style.display = 'none';      // Ẩn <img>
    text.style.display = '';             // Hiện lại text "📷 Mặt trước"
    btn.style.display = 'none';          // Ẩn nút ✕
    input.value = '';                     // Xóa file đã chọn
    box.classList.remove('has-image');    // Bỏ class → CSS trả viền dashed
}
