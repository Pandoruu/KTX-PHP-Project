# Tom tat du an Quan Ly Ky Tuc Xa

Tai lieu nay tom tat cau truc tung file, cac doan code chinh, workflow nghiep vu va run flow de trinh bay du an.

## 1) Tong quan nhanh
- Cong nghe: PHP (server-side), MySQL/MariaDB, HTML/CSS/JS inline, chay tren Apache (XAMPP/WAMP/MAMP).
- Kieu kien truc: PHP thuong (moi trang la mot file PHP, tu xu ly POST/GET va render HTML).
- Bao mat co ban: dang nhap session, ham requireLogin, phan quyen admin/nhan vien.

## 2) Cau truc thu muc (goc: ktx/)
```
ktx/
├── admin.php
├── bao_cao.php
├── bao_tri.php
├── config.php
├── database.sql
├── hoa_don.php
├── hop_dong.php
├── includes/
│   ├── footer.php
│   └── header.php
├── index.php
├── login.php
├── logout.php
├── phong.php
├── README.md
├── sinh_vien.php
├── toa_nha.php
├── vi_pham.php
├── FEATURES_WORKFLOW.md
└── docs/
    └── PROJECT_SUMMARY.md
```

## 3) Mo ta chi tiet tung file va doan code chinh

### 3.1 `config.php`
- Muc dich: cau hinh DB, khoi dong session, ham tien ich dung chung.
- Doan code chinh:
  - Hang 8-14: khai bao DB_HOST/DB_USER/DB_PASS/DB_NAME/DB_CHARSET.
  - Ham `getDB()`: tao ket noi PDO, set error mode, fetch mode; hien thong bao neu ket noi loi.
  - Ham session + auth: `isLoggedIn()`, `requireLogin()`, `isAdmin()`.
  - Ham tien ich hien thi: `formatMoney`, `formatDate`, `formatDateTime`, `sanitize`.
  - Ham trang thai UI: `getStatusBadge()` tra ve HTML badge theo loai.
  - Ham tao ma: `generateMaHoaDon()`, `generateMaHopDong()`.
  - Flash message: `setFlash`, `getFlash`, `showFlash`.

### 3.2 `README.md`
- Muc dich: huong dan cai dat nhanh.
- Noi dung chinh: yeu cau he thong, huong dan import `database.sql`, cau hinh `config.php`, tai khoan mac dinh.

### 3.3 `includes/header.php`
- Muc dich: layout chung, CSS theme, top header va dropdown menu.
- Doan code chinh:
  - CSS bien mau Be + xanh la tham + xam, component (card/button/table/modal/badge).
  - Header layout: logo + dropdown menu theo nhom + user box.
  - Dropdown menu: mo bang click (JS) thay vi hover.
  - Luu y: file hien tai da co the chua dong the `</main></div></body></html>` (can dong bo voi `footer.php` neu chinh sua sau).

### 3.4 `includes/footer.php`
- Muc dich: dong layout va cac ham JS dung chung.
- Doan code chinh:
  - Dong the `</main>` va wrapper.
  - JS: dong ho thoi gian, helper modal open/close, confirm delete, tu dong tat alert.

### 3.5 `login.php`
- Muc dich: dang nhap he thong.
- Doan code chinh:
  - Neu da dang nhap thi redirect ve `index.php`.
  - Kiem tra POST, lay user/pass, truy van bang `admin`, verify password hash.
  - Luu session: `admin_id`, `ho_ten`, `vai_tro`, `username`.
  - Giao dien login rieng (dark theme, inline CSS).

### 3.6 `logout.php`
- Muc dich: dang xuat.
- Doan code chinh: `session_destroy()` va `redirect('login.php')`.

### 3.7 `index.php` (Dashboard)
- Muc dich: tong quan thong ke.
- Doan code chinh:
  - Thong ke tong quan: toa_nha, phong, sinh_vien, hop_dong, hoa_don, vi_pham, bao_tri.
  - Doanh thu thang hien tai va so tien chua thu.
  - Danh sach: hop dong sap het han, hoa don qua han, sinh vien moi, phong theo toa.
  - Render cac card + table tong hop.

### 3.8 `toa_nha.php`
- Muc dich: CRUD toa nha.
- Doan code chinh:
  - POST add/edit/delete (kiem tra ten toa, kiem tra phong ton tai khi xoa).
  - Query list toa nha + thong ke phong theo trang thai.
  - Modal form add/edit.

### 3.9 `phong.php`
- Muc dich: CRUD phong o.
- Doan code chinh:
  - POST add/edit/delete (kiem tra phong co hop dong hieu luc khi xoa).
  - Filter theo toa, trang thai, loai phong, tim kiem.
  - Pagination va thong ke so sinh vien trong phong.
  - Modal form add/edit + JS tu dong thay doi so giuong.

### 3.10 `sinh_vien.php`
- Muc dich: CRUD sinh vien + xem chi tiet.
- Doan code chinh:
  - POST add/edit/delete (chan xoa neu co hop dong hieu luc).
  - Filter theo trang thai, tim kiem theo ho ten/ma sv/email/sdt.
  - Query ket hop voi hop_dong/phong/toa_nha de hien phong hien tai.
  - Modal add/edit + modal view chi tiet.

### 3.11 `hop_dong.php`
- Muc dich: CRUD hop dong.
- Doan code chinh:
  - POST add/edit/delete. Khi add hop dong hieu luc, cap nhat trang thai phong (day/trong).
  - Filter theo trang thai, phong, tim kiem theo ma hop dong/ten sv/ma sv.
  - Query list co so ngay con lai (DATEDIFF) de canh bao het han.
  - Modal form add/edit.

### 3.12 `hoa_don.php`
- Muc dich: CRUD hoa don + tinh toan dien/nuoc.
- Doan code chinh:
  - Validate du lieu (thang/nam, chi so, so tien, phong ton tai).
  - Tu tinh: tien dien, tien nuoc, tong tien.
  - POST add/edit/delete + action thanh_toan.
  - Filter theo trang thai, thang/nam, tu khoa.
  - Tong hop thu/chu thu; modal add/edit, JS tinh tong tien realtime.

### 3.13 `vi_pham.php`
- Muc dich: CRUD vi pham.
- Doan code chinh:
  - POST add/edit/delete.
  - Filter theo trang thai, muc do, tim kiem.
  - Query join sinh_vien, hien thi muc do va xu phat.

### 3.14 `bao_tri.php`
- Muc dich: CRUD yeu cau bao tri.
- Doan code chinh:
  - POST add/edit/delete, cap nhat ngay hoan thanh neu trang thai hoan_thanh.
  - Filter theo trang thai, loai, tim kiem.
  - Query join phong/toa_nha va optional sinh_vien.

### 3.15 `bao_cao.php`
- Muc dich: thong ke bao cao.
- Doan code chinh:
  - Doanh thu theo thang (da thu, chua thu).
  - Thong ke phong theo trang thai, top phong doanh thu.
  - Phan bo sinh vien theo truong.

### 3.16 `admin.php`
- Muc dich: quan tri tai khoan + cong cu he thong.
- Doan code chinh:
  - Chi cho admin truy cap (`isAdmin()`).
  - CRUD tai khoan admin/nhan vien (bcrypt, update password).
  - Cong cu: danh dau hoa don qua han.
  - System stats va thong tin he thong.

### 3.17 `database.sql`
- Muc dich: tao CSDL va du lieu mau.
- Bang chinh: `toa_nha`, `phong`, `sinh_vien`, `hop_dong`, `hoa_don`, `vi_pham`, `bao_tri`, `admin`.
- Rang buoc: khoa ngoai, unique (ma_sv, ma_hop_dong, ma_hoa_don, (toa_nha_id, so_phong)).

### 3.18 `FEATURES_WORKFLOW.md`
- Tai lieu tom tat tinh nang va quy trinh nghiep vu (da co san).

## 4) Mo hinh du lieu (CSDL)
- Quan he chinh:
  - `toa_nha` 1-n `phong`.
  - `phong` 1-n `hop_dong` va 1-n `hoa_don`.
  - `sinh_vien` 1-n `hop_dong`, 1-n `vi_pham`.
  - `bao_tri` gan voi `phong` va co the gan `sinh_vien`.
- Trang thai quan trong:
  - `phong`: trong/day/bao_tri
  - `hop_dong`: hieu_luc/het_han/da_huy
  - `hoa_don`: chua_thanh_toan/da_thanh_toan/qua_han
  - `vi_pham`: cho_xu_ly/da_xu_ly
  - `bao_tri`: cho_xu_ly/dang_xu_ly/hoan_thanh/huy

## 5) Workflow nghiep vu (chinh)
1) Tiep nhan sinh vien
- Tao sinh vien -> Tao hop dong -> Cap nhat phong (day/trong) -> Sinh vien dang o.

2) Quan ly hop dong
- Tao moi/gia han -> Theo doi ngay bat dau/ket thuc -> Het han/huy -> cap nhat phong va sinh vien.

3) Lap hoa don thang
- Chon phong + thang/nam -> nhap chi so -> he thong tinh tong -> xac nhan thanh toan.

4) Vi pham
- Ghi nhan -> xu ly -> cap nhat ket qua + phat tien (neu co).

5) Bao tri
- Tao yeu cau -> cap nhat trang thai -> hoan thanh va luu chi phi.

## 6) Run flow (luong chay he thong)
1) Cai dat
- Import `database.sql` tao DB + du lieu mau.
- Sua `config.php` cho dung DB_HOST/DB_USER/DB_PASS/DB_NAME.
- Dat project vao `htdocs` va truy cap `http://localhost/ktx/`.

2) Dang nhap
- Nguoi dung vao `login.php` -> kiem tra tai khoan bang `admin` -> tao session.

3) Truy cap cac trang
- Moi trang PHP goi `requireLogin()` de bao ve.
- `config.php` cung cap ket noi DB va ham chung.
- Trang lay du lieu -> render HTML + modal -> submit POST -> xu ly -> redirect -> show flash.

4) Render UI
- `includes/header.php` load CSS + menu dropdown + user box.
- `includes/footer.php` xu ly JS ho tro (modal, clock, confirm, auto-dismiss).

## 7) Diem nhan UI/UX
- Theme mau Be + xanh la tham + xam.
- Menu topbar theo nhom dropdown.
- Su dung card, badge, modal, pagination dong bo.
- Login page co giao dien rieng.

---
Neu can mo rong tai lieu (vi du: them so do Mermaid cho workflow), hay cho minh biet.

