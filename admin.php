<?php
require_once 'config.php';
requireLogin();
if (!isAdmin()) { setFlash('error', 'Bạn không có quyền truy cập trang này!'); redirect('index.php'); }
$pageTitle = 'Quản trị Hệ thống';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_admin' || $action === 'edit_admin') {
        $ten  = trim($_POST['ten_dang_nhap']??'');
        $ht   = trim($_POST['ho_ten']??'');
        $email= trim($_POST['email']??'');
        $vt   = $_POST['vai_tro']??'nhan_vien';
        $tt   = $_POST['trang_thai']??'hoat_dong';
        $pass = trim($_POST['mat_khau']??'');

        if ($action === 'add_admin') {
            if (!$ten || !$pass) { setFlash('error','Tên đăng nhập và mật khẩu bắt buộc!'); redirect('admin.php'); }
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            try {
                $db->prepare("INSERT INTO admin (ten_dang_nhap,mat_khau,ho_ten,email,vai_tro,trang_thai) VALUES (?,?,?,?,?,?)")->execute([$ten,$hash,$ht,$email,$vt,$tt]);
                setFlash('success','Thêm tài khoản thành công!');
            } catch (PDOException $e) {
                setFlash('error','Tên đăng nhập đã tồn tại!');
            }
        } else {
            $id = (int)$_POST['id'];
            if ($pass) {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $db->prepare("UPDATE admin SET ho_ten=?,email=?,vai_tro=?,trang_thai=?,mat_khau=? WHERE id=?")->execute([$ht,$email,$vt,$tt,$hash,$id]);
            } else {
                $db->prepare("UPDATE admin SET ho_ten=?,email=?,vai_tro=?,trang_thai=? WHERE id=?")->execute([$ht,$email,$vt,$tt,$id]);
            }
            setFlash('success','Cập nhật tài khoản thành công!');
        }
        redirect('admin.php');
    }

    if ($action === 'delete_admin') {
        $id = (int)$_POST['id'];
        if ($id === (int)$_SESSION['admin_id']) { setFlash('error','Không thể xóa tài khoản đang đăng nhập!'); }
        else { $db->prepare("DELETE FROM admin WHERE id=?")->execute([$id]); setFlash('success','Đã xóa tài khoản!'); }
        redirect('admin.php');
    }

    if ($action === 'mark_quahan') {
        $db->query("UPDATE hoa_don SET trang_thai='qua_han' WHERE trang_thai='chua_thanh_toan' AND CONCAT(nam,'-',LPAD(thang,2,'0'),'-01') < DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
        setFlash('success','Đã cập nhật hóa đơn quá hạn!');
        redirect('admin.php');
    }
}

$admins = $db->query("SELECT * FROM admin ORDER BY vai_tro, ho_ten")->fetchAll();
$editData = null;
if (isset($_GET['edit'])) { $s=$db->prepare("SELECT * FROM admin WHERE id=?"); $s->execute([(int)$_GET['edit']]); $editData=$s->fetch(); }

// System stats
$sysStats = [
    'tong_sv'   => $db->query("SELECT COUNT(*) FROM sinh_vien")->fetchColumn(),
    'tong_phong'=> $db->query("SELECT COUNT(*) FROM phong")->fetchColumn(),
    'tong_hd'   => $db->query("SELECT COUNT(*) FROM hop_dong")->fetchColumn(),
    'tong_hoadon'=> $db->query("SELECT COUNT(*) FROM hoa_don")->fetchColumn(),
    'tong_vp'   => $db->query("SELECT COUNT(*) FROM vi_pham")->fetchColumn(),
    'tong_bt'   => $db->query("SELECT COUNT(*) FROM bao_tri")->fetchColumn(),
];

include 'includes/header.php';
?>

<div class="page-header">
    <div><h2>⚙️ Quản trị Hệ thống</h2><p>Quản lý tài khoản và cấu hình hệ thống</p></div>
</div>

<!-- SYSTEM STATS -->
<div class="stats-grid" style="grid-template-columns:repeat(6,1fr);margin-bottom:20px">
    <?php foreach ([
        ['Sinh viên','👥',$sysStats['tong_sv'],'blue'],
        ['Phòng ở','🚪',$sysStats['tong_phong'],'cyan'],
        ['Hợp đồng','📋',$sysStats['tong_hd'],'green'],
        ['Hóa đơn','💰',$sysStats['tong_hoadon'],'yellow'],
        ['Vi phạm','⚠️',$sysStats['tong_vp'],'red'],
        ['Bảo trì','🔧',$sysStats['tong_bt'],'yellow'],
    ] as [$lb,$ic,$v,$cl]): ?>
    <div class="stat-card <?= $cl ?>">
        <div class="stat-icon"><?= $ic ?></div>
        <div class="stat-value"><?= $v ?></div>
        <div class="stat-label"><?= $lb ?></div>
    </div>
    <?php endforeach; ?>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px">
    <!-- Tài khoản admin -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">👑 Tài khoản quản trị</div>
            <button class="btn btn-sm btn-primary" onclick="openModal('modal-add')">➕ Thêm tài khoản</button>
        </div>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>#</th><th>Tên đăng nhập</th><th>Họ tên</th><th>Email</th><th>Vai trò</th><th>Trạng thái</th><th>Đăng nhập cuối</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($admins as $i=>$a): ?>
                <tr>
                    <td class="text-muted"><?= $i+1 ?></td>
                    <td><code><?= sanitize($a['ten_dang_nhap']) ?></code><?= $a['id']==$_SESSION['admin_id']?' <span class="badge badge-primary">Bạn</span>':'' ?></td>
                    <td style="font-weight:600"><?= sanitize($a['ho_ten']??'—') ?></td>
                    <td style="font-size:12.5px;color:#64748b"><?= sanitize($a['email']??'—') ?></td>
                    <td><?= $a['vai_tro']==='quan_tri'?'<span class="badge badge-warning">👑 Quản trị</span>':'<span class="badge badge-secondary">👷 Nhân viên</span>' ?></td>
                    <td><?= $a['trang_thai']==='hoat_dong'?'<span class="badge badge-success">✅ Hoạt động</span>':'<span class="badge badge-danger">🔒 Khóa</span>' ?></td>
                    <td style="font-size:12px;color:#64748b"><?= $a['lan_dang_nhap_cuoi']?formatDateTime($a['lan_dang_nhap_cuoi']):'Chưa đăng nhập' ?></td>
                    <td>
                        <div class="btn-group">
                            <a href="admin.php?edit=<?= $a['id'] ?>" class="btn btn-sm btn-outline">✏️</a>
                            <?php if ($a['id'] != $_SESSION['admin_id']): ?>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Xóa tài khoản <?= sanitize($a['ten_dang_nhap']) ?>?')">
                                <input type="hidden" name="action" value="delete_admin">
                                <input type="hidden" name="id" value="<?= $a['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">🗑️</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Công cụ hệ thống -->
    <div class="card">
        <div class="card-header"><div class="card-title">🛠️ Công cụ hệ thống</div></div>
        <div class="card-body">
            <div style="margin-bottom:16px">
                <div style="font-size:13.5px;font-weight:600;margin-bottom:6px">📅 Cập nhật hóa đơn quá hạn</div>
                <p style="font-size:12.5px;color:#64748b;margin-bottom:10px">Đánh dấu tất cả hóa đơn chưa TT quá 30 ngày là "Quá hạn"</p>
                <form method="POST" onsubmit="return confirm('Thực hiện cập nhật?')">
                    <input type="hidden" name="action" value="mark_quahan">
                    <button type="submit" class="btn btn-warning" style="width:100%">⚡ Cập nhật ngay</button>
                </form>
            </div>
            <div class="divider"></div>
            <div style="margin-bottom:16px">
                <div style="font-size:13.5px;font-weight:700;margin-bottom:8px">📋 Hướng dẫn cài đặt</div>
                <div style="background:var(--surface2);border-radius:8px;padding:12px;font-size:12px;font-family:monospace;line-height:1.8">
                    1. Import <b>database.sql</b><br>
                    2. Sửa <b>config.php</b><br>
                    &nbsp;&nbsp;DB_HOST, DB_USER<br>
                    &nbsp;&nbsp;DB_PASS, DB_NAME<br>
                    3. Đặt vào thư mục htdocs<br>
                    4. Truy cập qua localhost<br>
                    5. Đăng nhập: <b>admin/password</b>
                </div>
            </div>
            <div class="divider"></div>
            <div>
                <div style="font-size:13.5px;font-weight:700;margin-bottom:8px">ℹ️ Thông tin hệ thống</div>
                <div style="font-size:12.5px;color:#64748b;line-height:2">
                    PHP: <?= phpversion() ?><br>
                    Server: <?= $_SERVER['SERVER_SOFTWARE']??'N/A' ?><br>
                    Phiên bản: <?= SITE_VERSION ?><br>
                    Ngày: <?= date('d/m/Y H:i') ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL THÊM/SỬA TK -->
<?php foreach(['modal-add'=>false,'modal-edit'=>$editData] as $mid=>$fd):
if ($mid==='modal-edit'&&!$fd) continue;
$fd=$fd?:['ten_dang_nhap'=>'','ho_ten'=>'','email'=>'','vai_tro'=>'nhan_vien','trang_thai'=>'hoat_dong'];
$isE=$mid==='modal-edit';
?>
<div class="modal-overlay <?= $isE?'show':'' ?>" id="<?= $mid ?>">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><?= $isE?'✏️ Sửa tài khoản':'➕ Thêm tài khoản' ?></div>
            <?php if($isE): ?><a href="admin.php" class="modal-close">✕</a><?php else: ?><button class="modal-close" onclick="closeModal('modal-add')">✕</button><?php endif; ?>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="<?= $isE?'edit_admin':'add_admin' ?>">
            <?php if($isE): ?><input type="hidden" name="id" value="<?= $fd['id'] ?>"><?php endif; ?>
            <div class="modal-body">
                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <label>Tên đăng nhập *</label>
                        <input type="text" name="ten_dang_nhap" value="<?= sanitize($fd['ten_dang_nhap']) ?>" <?= $isE?'readonly':'' ?> required placeholder="username">
                    </div>
                    <div class="form-group">
                        <label>Mật khẩu <?= $isE?'(để trống = giữ cũ)':'' ?> *</label>
                        <input type="password" name="mat_khau" placeholder="<?= $isE?'Nhập mật khẩu mới...':'Mật khẩu...' ?>" <?= $isE?'':'required' ?>>
                    </div>
                    <div class="form-group"><label>Họ tên</label><input type="text" name="ho_ten" value="<?= sanitize($fd['ho_ten']??'') ?>" placeholder="Nguyễn Văn A"></div>
                    <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= sanitize($fd['email']??'') ?>" placeholder="email@ktx.edu.vn"></div>
                    <div class="form-group"><label>Vai trò</label><select name="vai_tro"><option value="quan_tri" <?= ($fd['vai_tro']??'')==='quan_tri'?'selected':'' ?>>👑 Quản trị viên</option><option value="nhan_vien" <?= ($fd['vai_tro']??'')==='nhan_vien'?'selected':'' ?>>👷 Nhân viên</option></select></div>
                    <div class="form-group"><label>Trạng thái</label><select name="trang_thai"><option value="hoat_dong" <?= ($fd['trang_thai']??'')==='hoat_dong'?'selected':'' ?>>✅ Hoạt động</option><option value="khoa" <?= ($fd['trang_thai']??'')==='khoa'?'selected':'' ?>>🔒 Khóa</option></select></div>
                </div>
            </div>
            <div class="modal-footer">
                <?php if($isE): ?><a href="admin.php" class="btn btn-secondary">Hủy</a><?php else: ?><button type="button" class="btn btn-secondary" onclick="closeModal('modal-add')">Hủy</button><?php endif; ?>
                <button type="submit" class="btn btn-primary">💾 <?= $isE?'Cập nhật':'Thêm' ?></button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>
<?php include 'includes/footer.php'; ?>
