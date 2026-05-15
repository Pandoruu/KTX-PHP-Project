-- =====================================================
-- DATABASE: Quản Lý Ký Túc Xá
-- =====================================================

CREATE DATABASE IF NOT EXISTS ktx_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ktx_db;

-- Bảng tòa nhà
CREATE TABLE IF NOT EXISTS toa_nha (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten_toa VARCHAR(100) NOT NULL,
    mo_ta TEXT,
    so_tang INT NOT NULL DEFAULT 1,
    trang_thai ENUM('hoat_dong','bao_tri','dong_cua') DEFAULT 'hoat_dong',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bảng phòng
CREATE TABLE IF NOT EXISTS phong (
    id INT AUTO_INCREMENT PRIMARY KEY,
    toa_nha_id INT NOT NULL,
    so_phong VARCHAR(20) NOT NULL,
    tang INT NOT NULL,
    loai_phong ENUM('don','doi','ba','bon') NOT NULL DEFAULT 'doi',
    gia_thue DECIMAL(10,0) NOT NULL DEFAULT 0,
    so_giuong INT NOT NULL DEFAULT 2,
    dien_tich DECIMAL(5,1) DEFAULT 0,
    trang_thai ENUM('trong','day','bao_tri') DEFAULT 'trong',
    mo_ta TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (toa_nha_id) REFERENCES toa_nha(id) ON DELETE CASCADE,
    UNIQUE KEY unique_phong (toa_nha_id, so_phong)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bảng sinh viên
CREATE TABLE IF NOT EXISTS sinh_vien (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ma_sv VARCHAR(20) NOT NULL UNIQUE,
    ho_ten VARCHAR(100) NOT NULL,
    ngay_sinh DATE,
    gioi_tinh ENUM('nam','nu','khac') DEFAULT 'nam',
    cccd VARCHAR(20) UNIQUE,
    email VARCHAR(100),
    so_dien_thoai VARCHAR(15),
    dia_chi TEXT,
    truong VARCHAR(200),
    khoa VARCHAR(200),
    nganh VARCHAR(200),
    nam_hoc INT,
    anh_dai_dien VARCHAR(255),
    trang_thai ENUM('dang_o','da_roi','tam_vang') DEFAULT 'dang_o',
    ghi_chu TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bảng hợp đồng thuê phòng
CREATE TABLE IF NOT EXISTS hop_dong (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ma_hop_dong VARCHAR(30) NOT NULL UNIQUE,
    sinh_vien_id INT NOT NULL,
    phong_id INT NOT NULL,
    ngay_bat_dau DATE NOT NULL,
    ngay_ket_thuc DATE NOT NULL,
    gia_thue DECIMAL(10,0) NOT NULL,
    tien_coc DECIMAL(10,0) DEFAULT 0,
    trang_thai ENUM('hieu_luc','het_han','da_huy') DEFAULT 'hieu_luc',
    ghi_chu TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sinh_vien_id) REFERENCES sinh_vien(id) ON DELETE CASCADE,
    FOREIGN KEY (phong_id) REFERENCES phong(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bảng hóa đơn điện nước
CREATE TABLE IF NOT EXISTS hoa_don (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ma_hoa_don VARCHAR(30) NOT NULL UNIQUE,
    phong_id INT NOT NULL,
    thang INT NOT NULL,
    nam INT NOT NULL,
    chi_so_dien_cu DECIMAL(10,2) DEFAULT 0,
    chi_so_dien_moi DECIMAL(10,2) DEFAULT 0,
    don_gia_dien DECIMAL(10,0) DEFAULT 3500,
    chi_so_nuoc_cu DECIMAL(10,2) DEFAULT 0,
    chi_so_nuoc_moi DECIMAL(10,2) DEFAULT 0,
    don_gia_nuoc DECIMAL(10,0) DEFAULT 15000,
    tien_dich_vu DECIMAL(10,0) DEFAULT 0,
    tien_phong DECIMAL(10,0) DEFAULT 0,
    tong_tien DECIMAL(12,0) DEFAULT 0,
    trang_thai ENUM('chua_thanh_toan','da_thanh_toan','qua_han') DEFAULT 'chua_thanh_toan',
    ngay_thanh_toan DATE,
    ghi_chu TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (phong_id) REFERENCES phong(id) ON DELETE CASCADE,
    UNIQUE KEY unique_hd (phong_id, thang, nam)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bảng vi phạm / kỷ luật
CREATE TABLE IF NOT EXISTS vi_pham (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sinh_vien_id INT NOT NULL,
    loai_vi_pham VARCHAR(100),
    mo_ta TEXT,
    muc_do ENUM('nhe','trung_binh','nghiem_trong') DEFAULT 'nhe',
    xu_phat TEXT,
    so_tien_phat DECIMAL(10,0) DEFAULT 0,
    ngay_vi_pham DATE NOT NULL,
    trang_thai ENUM('cho_xu_ly','da_xu_ly') DEFAULT 'cho_xu_ly',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sinh_vien_id) REFERENCES sinh_vien(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bảng yêu cầu bảo trì
CREATE TABLE IF NOT EXISTS bao_tri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phong_id INT NOT NULL,
    sinh_vien_id INT,
    tieu_de VARCHAR(200) NOT NULL,
    mo_ta TEXT,
    loai ENUM('dien','nuoc','co_so_vat_chat','khac') DEFAULT 'khac',
    muc_do_uu_tien ENUM('thap','trung_binh','cao','khan_cap') DEFAULT 'trung_binh',
    trang_thai ENUM('cho_xu_ly','dang_xu_ly','hoan_thanh','huy') DEFAULT 'cho_xu_ly',
    ngay_yeu_cau DATE NOT NULL,
    ngay_hoan_thanh DATE,
    chi_phi DECIMAL(10,0) DEFAULT 0,
    ghi_chu TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (phong_id) REFERENCES phong(id) ON DELETE CASCADE,
    FOREIGN KEY (sinh_vien_id) REFERENCES sinh_vien(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bảng tài khoản admin
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten_dang_nhap VARCHAR(50) NOT NULL UNIQUE,
    mat_khau VARCHAR(255) NOT NULL,
    ho_ten VARCHAR(100),
    email VARCHAR(100),
    vai_tro ENUM('quan_tri','nhan_vien') DEFAULT 'nhan_vien',
    trang_thai ENUM('hoat_dong','khoa') DEFAULT 'hoat_dong',
    lan_dang_nhap_cuoi TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- DỮ LIỆU MẪU
-- =====================================================

-- Admin mặc định (password: password)
-- Hash bcrypt của chuỗi 'password'
INSERT INTO admin (ten_dang_nhap, mat_khau, ho_ten, email, vai_tro) VALUES
('admin',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Quản Trị Viên', 'admin@ktx.edu.vn', 'quan_tri'),
('nhanvien1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nguyễn Văn An', 'nv1@ktx.edu.vn', 'nhan_vien');

-- Tòa nhà
INSERT INTO toa_nha (ten_toa, mo_ta, so_tang, trang_thai) VALUES
('Tòa A', 'Tòa nhà dành cho nam sinh viên, 5 tầng', 5, 'hoat_dong'),
('Tòa B', 'Tòa nhà dành cho nữ sinh viên, 5 tầng', 5, 'hoat_dong'),
('Tòa C', 'Tòa nhà hỗn hợp, đang bảo trì tầng 3', 4, 'hoat_dong');

-- Phòng Tòa A
INSERT INTO phong (toa_nha_id, so_phong, tang, loai_phong, gia_thue, so_giuong, dien_tich, trang_thai) VALUES
(1, 'A101', 1, 'doi', 800000, 2, 20.0, 'day'),
(1, 'A102', 1, 'bon', 600000, 4, 30.0, 'day'),
(1, 'A103', 1, 'bon', 600000, 4, 30.0, 'trong'),
(1, 'A201', 2, 'doi', 800000, 2, 20.0, 'day'),
(1, 'A202', 2, 'bon', 600000, 4, 30.0, 'day'),
(1, 'A203', 2, 'bon', 600000, 4, 30.0, 'day'),
(1, 'A301', 3, 'don', 1200000, 1, 15.0, 'trong'),
(1, 'A302', 3, 'doi', 800000, 2, 20.0, 'bao_tri'),
-- Phòng Tòa B
(2, 'B101', 1, 'doi', 800000, 2, 20.0, 'day'),
(2, 'B102', 1, 'bon', 600000, 4, 30.0, 'day'),
(2, 'B201', 2, 'doi', 800000, 2, 20.0, 'trong'),
(2, 'B202', 2, 'bon', 600000, 4, 30.0, 'day'),
(2, 'B301', 3, 'don', 1200000, 1, 15.0, 'trong'),
-- Phòng Tòa C
(3, 'C101', 1, 'bon', 550000, 4, 28.0, 'day'),
(3, 'C201', 2, 'bon', 550000, 4, 28.0, 'day'),
(3, 'C301', 3, 'ba', 700000, 3, 25.0, 'trong');

-- Sinh viên mẫu
INSERT INTO sinh_vien (ma_sv, ho_ten, ngay_sinh, gioi_tinh, cccd, email, so_dien_thoai, dia_chi, truong, khoa, nganh, nam_hoc, trang_thai) VALUES
('SV001', 'Nguyễn Văn Hùng', '2003-05-15', 'nam', '001203012345', 'hung.nv@email.com', '0901234567', 'Hà Nội', 'ĐH Bách Khoa', 'CNTT', 'Kỹ thuật phần mềm', 2, 'dang_o'),
('SV002', 'Trần Thị Mai', '2003-08-22', 'nu', '001203012346', 'mai.tt@email.com', '0901234568', 'Nam Định', 'ĐH Bách Khoa', 'CNTT', 'Hệ thống thông tin', 2, 'dang_o'),
('SV003', 'Lê Văn Tùng', '2002-03-10', 'nam', '001202012347', 'tung.lv@email.com', '0901234569', 'Hải Phòng', 'ĐH Kinh Tế', 'Kế toán', 'Kế toán doanh nghiệp', 3, 'dang_o'),
('SV004', 'Phạm Thị Lan', '2003-11-30', 'nu', '001203012348', 'lan.pt@email.com', '0901234570', 'Thái Bình', 'ĐH Sư Phạm', 'Toán', 'Sư phạm Toán', 2, 'dang_o'),
('SV005', 'Hoàng Văn Nam', '2001-07-18', 'nam', '001201012349', 'nam.hv@email.com', '0901234571', 'Nghệ An', 'ĐH Bách Khoa', 'Cơ khí', 'Kỹ thuật cơ khí', 4, 'dang_o'),
('SV006', 'Đỗ Thị Hoa', '2003-02-14', 'nu', '001203012350', 'hoa.dt@email.com', '0901234572', 'Hà Tây', 'ĐH Ngoại Thương', 'Kinh doanh', 'Quản trị kinh doanh', 2, 'dang_o'),
('SV007', 'Bùi Văn Kiên', '2002-09-05', 'nam', '001202012351', 'kien.bv@email.com', '0901234573', 'Bắc Ninh', 'ĐH Bách Khoa', 'Điện tử', 'Điện tử viễn thông', 3, 'dang_o'),
('SV008', 'Ngô Thị Thảo', '2003-04-20', 'nu', '001203012352', 'thao.nt@email.com', '0901234574', 'Hưng Yên', 'ĐH Y', 'Y Đa Khoa', 'Bác sĩ đa khoa', 2, 'dang_o'),
('SV009', 'Vũ Văn Đức', '2001-12-08', 'nam', '001201012353', 'duc.vv@email.com', '0901234575', 'Hà Nam', 'ĐH Luật', 'Luật', 'Luật dân sự', 4, 'da_roi'),
('SV010', 'Lý Thị Ngọc', '2004-01-25', 'nu', '001204012354', 'ngoc.lt@email.com', '0901234576', 'Vĩnh Phúc', 'ĐH Nông Nghiệp', 'Nông học', 'Khoa học cây trồng', 1, 'dang_o');

-- Hợp đồng
INSERT INTO hop_dong (ma_hop_dong, sinh_vien_id, phong_id, ngay_bat_dau, ngay_ket_thuc, gia_thue, tien_coc, trang_thai) VALUES
('HD20240001', 1, 1, '2024-09-01', '2025-08-31', 800000, 1600000, 'hieu_luc'),
('HD20240002', 2, 9, '2024-09-01', '2025-08-31', 800000, 1600000, 'hieu_luc'),
('HD20240003', 3, 2, '2024-09-01', '2025-08-31', 600000, 1200000, 'hieu_luc'),
('HD20240004', 4, 10, '2024-09-01', '2025-08-31', 600000, 1200000, 'hieu_luc'),
('HD20240005', 5, 4, '2024-09-01', '2025-08-31', 800000, 1600000, 'hieu_luc'),
('HD20240006', 6, 10, '2024-09-01', '2025-08-31', 600000, 1200000, 'hieu_luc'),
('HD20240007', 7, 5, '2024-09-01', '2025-08-31', 600000, 1200000, 'hieu_luc'),
('HD20240008', 8, 12, '2024-09-01', '2025-08-31', 600000, 1200000, 'hieu_luc'),
('HD20230001', 9, 1, '2023-09-01', '2024-08-31', 750000, 1500000, 'het_han'),
('HD20240009', 10, 9, '2024-09-01', '2025-08-31', 800000, 1600000, 'hieu_luc');

-- Hóa đơn mẫu
INSERT INTO hoa_don (ma_hoa_don, phong_id, thang, nam, chi_so_dien_cu, chi_so_dien_moi, don_gia_dien, chi_so_nuoc_cu, chi_so_nuoc_moi, don_gia_nuoc, tien_dich_vu, tien_phong, tong_tien, trang_thai) VALUES
('HD-A101-2025-04', 1, 4, 2025, 1200, 1285, 3500, 45, 52, 15000, 50000, 800000, 1252500, 'da_thanh_toan'),
('HD-A102-2025-04', 2, 4, 2025, 2100, 2230, 3500, 80, 92, 15000, 50000, 600000, 1255000, 'da_thanh_toan'),
('HD-A101-2025-05', 1, 5, 2025, 1285, 1360, 3500, 52, 59, 15000, 50000, 800000, 1217500, 'chua_thanh_toan'),
('HD-A102-2025-05', 2, 5, 2025, 2230, 2355, 3500, 92, 105, 15000, 50000, 600000, 1282500, 'chua_thanh_toan'),
('HD-B101-2025-04', 9, 4, 2025, 900, 975, 3500, 35, 42, 15000, 50000, 800000, 1217500, 'da_thanh_toan'),
('HD-B101-2025-05', 9, 5, 2025, 975, 1050, 3500, 42, 49, 15000, 50000, 800000, 1217500, 'qua_han');

-- Vi phạm mẫu
INSERT INTO vi_pham (sinh_vien_id, loai_vi_pham, mo_ta, muc_do, xu_phat, so_tien_phat, ngay_vi_pham, trang_thai) VALUES
(1, 'Về muộn', 'Về ký túc xá sau 23h mà không xin phép', 'nhe', 'Cảnh cáo', 0, '2025-03-15', 'da_xu_ly'),
(3, 'Tiếng ồn', 'Gây tiếng ồn vào ban đêm, ảnh hưởng các phòng bên cạnh', 'trung_binh', 'Phạt tiền + cảnh cáo', 200000, '2025-04-02', 'da_xu_ly'),
(7, 'Hút thuốc', 'Hút thuốc trong phòng, vi phạm nội quy', 'trung_binh', 'Phạt tiền', 300000, '2025-04-20', 'cho_xu_ly');

-- Bảo trì mẫu
INSERT INTO bao_tri (phong_id, sinh_vien_id, tieu_de, mo_ta, loai, muc_do_uu_tien, trang_thai, ngay_yeu_cau, chi_phi) VALUES
(8, NULL, 'Bảo trì điện phòng A302', 'Hệ thống điện bị chập, cần kiểm tra toàn bộ', 'dien', 'khan_cap', 'dang_xu_ly', '2025-04-10', 500000),
(1, 1, 'Vòi nước bị rỉ', 'Vòi nước trong phòng tắm bị rỉ, cần thay mới', 'nuoc', 'trung_binh', 'hoan_thanh', '2025-03-20', 150000),
(14, NULL, 'Cửa phòng C101 bị hỏng khóa', 'Khóa cửa bị hỏng, không khóa được', 'co_so_vat_chat', 'cao', 'cho_xu_ly', '2025-05-01', 0);
