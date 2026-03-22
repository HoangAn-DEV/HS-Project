/**
 * ============================================================
 * admin.js — JavaScript cho khu vực quản trị SKIBIDI TOLET
 * ============================================================
 * Chức năng:
 * 1. Bật/tắt sidebar (thanh menu bên trái) trên mobile
 * 2. Modal xác nhận (hỏi trước khi xóa/thực hiện hành động)
 * 3. Đồng hồ thời gian thực trên topbar
 * 4. Hiệu ứng xuất hiện tuần tự cho thẻ thống kê & dòng bảng
 * 5. Tự ẩn thông báo (alert) sau 5 giây
 * 6. Hiệu ứng menu đang chọn (active nav item)
 */
document.addEventListener('DOMContentLoaded', () => {

    // ===== 1. BẬT/TẮT SIDEBAR =====
    const sidebar = document.getElementById('adminSidebar');  // Thanh menu trái
    const toggle  = document.getElementById('sidebarToggle'); // Nút hamburger ☰
    const overlay = document.getElementById('sidebarOverlay');// Lớp phủ mờ

    // Mở sidebar → thêm class 'open' + hiện overlay + khóa cuộn trang
    function openSidebar()  { sidebar?.classList.add('open');  overlay?.classList.add('show'); document.body.style.overflow = 'hidden'; }
    // Đóng sidebar → bỏ class 'open' + ẩn overlay + mở khóa cuộn
    function closeSidebar() { sidebar?.classList.remove('open'); overlay?.classList.remove('show'); document.body.style.overflow = ''; }

    // Bấm nút ☰ → đóng nếu đang mở, mở nếu đang đóng
    toggle?.addEventListener('click', () => sidebar?.classList.contains('open') ? closeSidebar() : openSidebar());
    // Bấm vào overlay (vùng mờ) → đóng sidebar
    overlay?.addEventListener('click', closeSidebar);

    // Nhấn phím Escape → đóng sidebar
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeSidebar();
    });

    // ===== 2. MODAL XÁC NHẬN =====
    // Hiện hộp thoại hỏi "Bạn chắc chắn?" trước khi thực hiện hành động (xóa, v.v.)
    const modal      = document.getElementById('confirmModal');    // Hộp thoại
    const modalText  = document.getElementById('confirmText');     // Nội dung câu hỏi
    const modalOk    = document.getElementById('confirmOk');       // Nút "Đồng ý"
    const modalCancel= document.getElementById('confirmCancel');   // Nút "Hủy"

    // Hiện modal với nội dung text, bấm OK sẽ chuyển đến href
    function showModal(text, href) {
        if (!modal) return;
        modalText.textContent = text;
        modalOk.href = href;
        modal.classList.add('show');
        document.body.style.overflow = 'hidden'; // Khóa cuộn trang
    }
    // Ẩn modal + mở khóa cuộn
    function hideModal() {
        modal?.classList.remove('show');
        document.body.style.overflow = '';
    }

    // Bấm "Hủy" hoặc bấm ra ngoài modal → đóng
    modalCancel?.addEventListener('click', hideModal);
    modal?.addEventListener('click', e => { if (e.target === modal) hideModal(); });

    // Tìm tất cả phần tử có data-confirm → bấm sẽ hiện modal xác nhận
    // VD: <a href="delete.php" data-confirm="Bạn muốn xóa?">Xóa</a>
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', e => {
            e.preventDefault(); // Ngăn chuyển trang ngay
            showModal(el.dataset.confirm || 'Bạn chắc chắn?', el.href || '#');
        });
    });

    // ===== 3. ĐỒNG HỒ THỜI GIAN THỰC =====
    // Hiện trên topbar, cập nhật mỗi 30 giây
    const clockEl = document.getElementById('liveClock');
    function updateClock() {
        if (!clockEl) return;
        const now = new Date();
        const days = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7']; // Tên thứ viết tắt
        const day = days[now.getDay()];
        const dd = String(now.getDate()).padStart(2, '0');   // Ngày 2 chữ số
        const mm = String(now.getMonth() + 1).padStart(2, '0'); // Tháng 2 chữ số
        const hh = String(now.getHours()).padStart(2, '0');  // Giờ 2 chữ số
        const mi = String(now.getMinutes()).padStart(2, '0'); // Phút 2 chữ số
        clockEl.textContent = `${day}, ${dd}/${mm} — ${hh}:${mi}`;
    }
    updateClock();                    // Chạy ngay lần đầu
    setInterval(updateClock, 30000);  // Lặp lại mỗi 30 giây

    // ===== 4. HIỆU ỨNG XUẤT HIỆN TUẦN TỰ =====
    // Thẻ thống kê + dòng bảng lần lượt trượt lên + mờ dần vào
    // Mỗi phần tử cách nhau 0.05s → tạo hiệu ứng "sóng" (stagger)
    const staggerItems = document.querySelectorAll('.stat-card, .admin-table tbody tr');
    staggerItems.forEach((el, i) => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(12px)';
        el.style.transition = `opacity 0.4s ease ${i * 0.05}s, transform 0.4s ease ${i * 0.05}s`;
        // requestAnimationFrame x2 = đợi 2 frame → đảm bảo trình duyệt đã render
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            });
        });
    });

    // ===== 5. TỰ ẨN THÔNG BÁO (ALERT) =====
    // Sau 5 giây → mờ dần + trượt lên → xóa khỏi DOM
    document.querySelectorAll('.alert').forEach(el => {
        setTimeout(() => {
            el.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            el.style.opacity = '0';
            el.style.transform = 'translateY(-8px)';
            setTimeout(() => el.remove(), 400); // Xóa phần tử sau khi hiệu ứng xong
        }, 5000);
    });

    // ===== 6. HIỆU ỨNG MENU ĐANG CHỌN =====
    // Menu sidebar đang active → trượt từ trái vào với hiệu ứng mờ dần
    const activeNav = document.querySelector('.nav-item.active');
    if (activeNav) {
        activeNav.style.transition = 'none';
        activeNav.style.opacity = '0';
        activeNav.style.transform = 'translateX(-8px)';
        requestAnimationFrame(() => {
            activeNav.style.transition = 'opacity 0.3s ease 0.1s, transform 0.3s ease 0.1s';
            activeNav.style.opacity = '1';
            activeNav.style.transform = 'translateX(0)';
        });
    }
});
