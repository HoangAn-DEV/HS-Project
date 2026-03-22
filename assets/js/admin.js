/**
 * admin.js — JS cho khu vực quản trị SKIBIDI TOLET
 * Features: Sidebar toggle, Confirm modal, Live clock, Stagger animations
 */
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('adminSidebar');
    const toggle  = document.getElementById('sidebarToggle');
    const overlay = document.getElementById('sidebarOverlay');

    // === Sidebar Toggle ===
    function openSidebar()  { sidebar?.classList.add('open');  overlay?.classList.add('show'); document.body.style.overflow = 'hidden'; }
    function closeSidebar() { sidebar?.classList.remove('open'); overlay?.classList.remove('show'); document.body.style.overflow = ''; }

    toggle?.addEventListener('click', () => sidebar?.classList.contains('open') ? closeSidebar() : openSidebar());
    overlay?.addEventListener('click', closeSidebar);

    // Close on Escape
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeSidebar();
    });

    // === Confirm Modal ===
    const modal      = document.getElementById('confirmModal');
    const modalText  = document.getElementById('confirmText');
    const modalOk    = document.getElementById('confirmOk');
    const modalCancel= document.getElementById('confirmCancel');

    function showModal(text, href) {
        if (!modal) return;
        modalText.textContent = text;
        modalOk.href = href;
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    function hideModal() {
        modal?.classList.remove('show');
        document.body.style.overflow = '';
    }

    modalCancel?.addEventListener('click', hideModal);
    modal?.addEventListener('click', e => { if (e.target === modal) hideModal(); });

    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', e => {
            e.preventDefault();
            showModal(el.dataset.confirm || 'Bạn chắc chắn?', el.href || '#');
        });
    });

    // === Live Clock ===
    const clockEl = document.getElementById('liveClock');
    function updateClock() {
        if (!clockEl) return;
        const now = new Date();
        const days = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
        const day = days[now.getDay()];
        const dd = String(now.getDate()).padStart(2, '0');
        const mm = String(now.getMonth() + 1).padStart(2, '0');
        const hh = String(now.getHours()).padStart(2, '0');
        const mi = String(now.getMinutes()).padStart(2, '0');
        clockEl.textContent = `${day}, ${dd}/${mm} — ${hh}:${mi}`;
    }
    updateClock();
    setInterval(updateClock, 30000);

    // === Stagger Animation for stat cards & table rows ===
    const staggerItems = document.querySelectorAll('.stat-card, .admin-table tbody tr');
    staggerItems.forEach((el, i) => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(12px)';
        el.style.transition = `opacity 0.4s ease ${i * 0.05}s, transform 0.4s ease ${i * 0.05}s`;
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            });
        });
    });

    // === Auto-dismiss alerts ===
    document.querySelectorAll('.alert').forEach(el => {
        setTimeout(() => {
            el.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            el.style.opacity = '0';
            el.style.transform = 'translateY(-8px)';
            setTimeout(() => el.remove(), 400);
        }, 5000);
    });

    // === Active nav item subtle animation ===
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
