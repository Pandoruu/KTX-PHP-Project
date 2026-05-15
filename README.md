# 🏢 Hệ Thống Quản Lý Ký Túc Xá

## Cài đặt

### Yêu cầu
- PHP 7.4+ (khuyến nghị PHP 8.0+)
- MySQL 5.7+ hoặc MariaDB 10.3+
- Web server: Apache/Nginx (XAMPP, WAMP, MAMP, Laragon)

### Các bước cài đặt

1. **Sao chép project** vào thư mục web:
   - XAMPP: `C:/xampp/htdocs/ktx/`
   - WAMP: `C:/wamp64/www/ktx/`

2. **Tạo database**: Import file `database.sql` vào phpMyAdmin hoặc MySQL CLI:
   ```
   mysql -u root -p < database.sql
   ```

3. **Cấu hình kết nối** trong `config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');      // mật khẩu MySQL của bạn
   define('DB_NAME', 'ktx_db');
   ```

4. **Truy cập**: `http://localhost/ktx/`

### Tài khoản mặc định
| Tên đăng nhập | Mật khẩu | Quyền |
|---|---|---|
| admin | password | Quản trị viên |
| nhanvien1 | password | Nhân viên |

## Tính năng

| Module | Mô tả |
|---|---|
| 📊 Dashboard | Tổng quan, thống kê, cảnh báo |
| 🏗️ Tòa nhà | CRUD tòa nhà |
| 🚪 Phòng ở | Quản lý phòng, lọc theo trạng thái |
| 👤 Sinh viên | Hồ sơ sinh viên, tìm kiếm, phân trang |
| 📋 Hợp đồng | Tạo/gia hạn/hủy hợp đồng |
| 💰 Hóa đơn | Điện, nước, tính toán tự động |
| ⚠️ Vi phạm | Ghi nhận kỷ luật sinh viên |
| 🔧 Bảo trì | Quản lý yêu cầu sửa chữa |
| 📈 Báo cáo | Doanh thu theo tháng, thống kê |
| ⚙️ Quản trị | Tài khoản, công cụ hệ thống |

## Cấu trúc thư mục
```
ktx/
├── config.php          # Cấu hình DB & hàm tiện ích
├── login.php           # Đăng nhập
├── logout.php          # Đăng xuất
├── index.php           # Dashboard
├── toa_nha.php         # Tòa nhà
├── phong.php           # Phòng ở
├── sinh_vien.php       # Sinh viên
├── hop_dong.php        # Hợp đồng
├── hoa_don.php         # Hóa đơn
├── vi_pham.php         # Vi phạm
├── bao_tri.php         # Bảo trì
├── bao_cao.php         # Báo cáo
├── admin.php           # Quản trị hệ thống
├── database.sql        # Script tạo CSDL + dữ liệu mẫu
└── includes/
    ├── header.php      # Layout header + sidebar + CSS
    └── footer.php      # Footer + JS
```
