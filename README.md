
Tài khoản admin mặc định:

| Trường   | Giá trị              |
|----------|----------------------|
| Email    | `admin@skibidi.com`  |
| Mật khẩu | `admin123`          |

### Dành cho khách

- Xem danh sách phòng với mô tả, tiện nghi, giá
- Đặt phòng theo khung giờ: **Sáng** / **Chiều** / **Tối**
- Tự động tính giá (phụ thu 50.000đ/người khi > 2 khách)
- Tra cứu đơn đặt phòng bằng số điện thoại
- Đăng ký tài khoản để xem lịch sử đặt phòng

### Dành cho Admin

- **Dashboard:** Thống kê doanh thu, đơn mới, số phòng, số thành viên
- **Quản lý đặt phòng:** Duyệt / Hủy / Xóa đơn
- **Quản lý phòng:** Thêm / Sửa / Xóa phòng, upload ảnh
- **Quản lý người dùng:** Phân quyền admin / user
- **Cài đặt hệ thống:** Tên site, thông tin ngân hàng, Zalo, phụ thu, địa chỉ

---

## Cấu trúc thư mục

```
homestay/
├── index.php                      # Trang chủ (danh sách phòng + modal đặt)
│
├── sql/
│   └── schema.sql                 # File tạo database — CHẠY ĐẦU TIÊN
│
├── includes/                      # Components dùng chung
│   ├── db.php                     # Kết nối MySQL (singleton pattern)
│   ├── config.php                 # Tự phát hiện BASE_URL
│   ├── auth.php                   # Hàm xác thực & phân quyền
│   ├── header.php                 # Header + nav cho trang public
│   ├── footer.php                 # Footer cho trang public
│   ├── admin_header.php           # Layout admin (sidebar + topbar)
│   └── admin_footer.php           # Đóng layout admin
│
├── assets/                        # Tài nguyên tĩnh
│   ├── css/
│   │   ├── main.css               # CSS chính toàn site
│   │   └── admin.css              # CSS riêng khu vực admin
│   ├── js/
│   │   ├── main.js                # JS chung (modal, validation, tính giá)
│   │   └── admin.js               # JS admin (sidebar toggle, confirm xóa)
│   └── img/                       # Ảnh phòng (tự thêm vào)
│
├── pages/
│   ├── auth/                      # Đăng nhập / Đăng ký
│   │   ├── login.php              # Form đăng nhập
│   │   ├── process_login.php      # Xử lý đăng nhập (bcrypt verify)
│   │   ├── register.php           # Form đăng ký
│   │   ├── process_register.php   # Xử lý đăng ký (bcrypt hash)
│   │   └── logout.php             # Đăng xuất (hủy session)
│   │
│   ├── user/                      # Trang dành cho khách / user
│   │   ├── booking_result.php     # Kết quả sau khi đặt phòng
│   │   ├── search_booking.php     # Tra cứu booking bằng SĐT
│   │   └── my_bookings.php        # Lịch sử đặt phòng (cần đăng nhập)
│   │
│   └── admin/                     # Trang quản trị (cần quyền admin)
│       ├── dashboard.php          # Tổng quan: thống kê doanh thu, đơn mới
│       ├── bookings.php           # Quản lý đơn đặt (duyệt/hủy/xóa)
│       ├── rooms.php              # Quản lý phòng (thêm/sửa/xóa)
│       ├── users.php              # Quản lý tài khoản (phân quyền)
│       └── settings.php           # Cài đặt hệ thống (ngân hàng, phụ thu)
│
└── api/                           # Xử lý logic backend
    ├── process_booking.php        # Nhận form đặt phòng → validate → lưu DB
    ├── booking_action.php         # Admin: duyệt/hủy/xóa booking
    └── room_action.php            # Admin: CRUD phòng
```

Nếu MySQL của bạn có mật khẩu khác, sửa trong file `includes/db.php`.

### Đặt phòng (khách)

1. Vào trang chủ → chọn phòng muốn đặt
2. Trong modal, chọn **ngày** và **khung giờ** (Sáng / Chiều / Tối)
3. Nhập **số khách**, **họ tên**, **số điện thoại**
4. Hệ thống tự tính tổng tiền (bao gồm phụ thu nếu > 2 khách)
5. Nhấn **Đặt phòng** → chuyển sang trang kết quả với thông tin chuyển khoản

### Tra cứu đơn đặt (khách)

1. Vào **Tra cứu đặt phòng** trên thanh menu
2. Nhập số điện thoại đã dùng khi đặt
3. Xem trạng thái đơn: *Chờ duyệt* / *Đã thanh toán* / *Đã hủy*

### Quản lý (admin)

1. Đăng nhập bằng tài khoản admin
2. Truy cập **Dashboard** để xem tổng quan
3. Vào **Quản lý đặt phòng** để duyệt/hủy đơn
4. Vào **Quản lý phòng** để thêm/sửa/xóa phòng
5. Vào **Cài đặt** để cập nhật thông tin ngân hàng, Zalo, phụ thu


## Xử lý sự cố

| Vấn đề | Giải pháp |
|--------|-----------|
| Trang trắng / lỗi kết nối DB | Kiểm tra MySQL đã bật trong XAMPP chưa |
| "Database not found" | Chạy lại file `sql/schema.sql` trong phpMyAdmin |
| Không đăng nhập được admin | Đảm bảo đã import schema.sql (chứa tài khoản admin mặc định) |
| Ảnh phòng không hiển thị | Thêm ảnh vào thư mục `assets/img/` với đúng tên file |
| Lỗi tiếng Việt / ký tự lạ | Đảm bảo charset `utf8mb4` trong phpMyAdmin |