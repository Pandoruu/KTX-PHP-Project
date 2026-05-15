<?php
require_once 'config.php';
requireLogin();
$pageTitle = 'Quản lý Bảo trì';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add' || $action === 'edit') {
        $phong_id = (int)$_POST['phong_id'];
        $sv_id    = $_POST['sinh_vien_id'] ? (int)$_POST['sinh_vien_id'] : null;
        $tieu_de  = trim($_POST['tieu_de']??'');
        $mo       = trim($_POST['mo_ta']??'');
        $loai     = $_POST['loai']??'khac';
        $uu_tien  = $_POST['muc_do_uu_tien']??'trung_binh';
        $tt       = $_POST['trang_thai']??'cho_xu_ly';
        $ngay_yc  = $_POST['ngay_yeu_cau'];
        $ngay_ht  = $_POST['ngay_hoan_thanh'] ?: null;
        $cp       = (int)($_POST['chi_phi']??0);
        $ghichu   = trim($_POST['ghi_chu']??'');

        if ($action === 'add') {
            $db->prepare("INSERT INTO bao_tri (phong_id,sinh_vien_id,tieu_de,mo_ta,loai,muc_do_uu_tien,trang_thai,ngay_yeu_cau,ngay_hoan_thanh,chi_phi,ghi_chu) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
               ->execute([$phong_id,$sv_id,$tieu_de,$mo,$loai,$uu_tien,$tt,$ngay_yc,$ngay_ht,$cp,$ghichu]);
            setFlash('success', 'Tạo yêu cầu bảo trì thành công!');
        } else {
            $id = (int)$_POST['id'];
            $db->prepare("UPDATE bao_tri SET phong_id=?,sinh_vien_id=?,tieu_de=?,mo_ta=?,loai=?,muc_do_uu_tien=?,trang_thai=?,ngay_yeu_cau=?,ngay_hoan_thanh=?,chi_phi=?,ghi_chu=? WHERE id=?")
               ->execute([$phong_id,$sv_id,$tieu_de,$mo,$loai,$uu_tien,$tt,$ngay_yc,$ngay_ht,$cp,$ghichu,$id]);
            if ($tt === 'hoan_thanh') {
                $db->prepare("UPDATE bao_tri SET ngay_hoan_thanh=CURDATE() WHERE id=? AND ngay_hoan_thanh IS NULL")->execute([$id]);
            }
            setFlash('success', 'Cập nhật bảo trì thành công!');
        }
        redirect('bao_tri.php');
    }
    if ($action === 'delete') {
        $db->prepare("DELETE FROM bao_tri WHERE id=?")->execute([(int)$_POST['id']]);
        setFlash('success', 'Đã xóa yêu cầu!');
        redirect('bao_tri.php');
    }
}

// Filter
$where=[]; $params=[];
$fTT = $_GET['trang_thai']??''; $fLoai = $_GET['loai']??''; $fQ = trim($_GET['q']??'');
if ($fTT)   { $where[]='bt.trang_thai=?'; $params[]=$fTT; }
if ($fLoai) { $where[]='bt.loai=?';       $params[]=$fLoai; }
if ($fQ)    { $where[]='(p.so_phong LIKE ? OR tn.ten_toa LIKE ? OR bt.tieu_de LIKE ?)'; $params=array_merge($params,["%$fQ%","%$fQ%","%$fQ%"]); }
$whereStr = $where ? 'WHERE '.implode(' AND ',$where) : '';

$perPage=15; $page=max(1,(int)($_GET['page']??1));
$total=$db->prepare("SELECT COUNT(*) FROM bao_tri bt JOIN phong p ON bt.phong_id=p.id JOIN toa_nha tn ON p.toa_nha_id=tn.id $whereStr");
$total->execute($params); $total=$total->fetchColumn();
$totalPages=ceil($total/$perPage); $offset=($page-1)*$perPage;

$list=$db->prepare("SELECT bt.*,p.so_phong,tn.ten_toa,sv.ho_ten as sv_ho_ten FROM bao_tri bt JOIN phong p ON bt.phong_id=p.id JOIN toa_nha tn ON p.toa_nha_id=tn.id LEFT JOIN sinh_vien sv ON bt.sinh_vien_id=sv.id $whereStr ORDER BY FIELD(bt.muc_do_uu_tien,'khan_cap','cao','trung_binh','thap'), bt.created_at DESC LIMIT $perPage OFFSET $offset");
$list->execute($params); $list=$list->fetchAll();

$phongList=$db->query("SELECT p.*,tn.ten_toa FROM phong p JOIN toa_nha tn ON p.toa_nha_id=tn.id ORDER BY tn.ten_toa,p.so_phong")->fetchAll();
$svList=$db->query("SELECT id,ma_sv,ho_ten FROM sinh_vien WHERE trang_thai='dang_o' ORDER BY ho_ten")->fetchAll();

$editData=null;
if (isset($_GET['edit'])) { $s=$db->prepare("SELECT * FROM bao_tri WHERE id=?"); $s->execute([(int)$_GET['edit']]); $editData=$s->fetch(); }

$uuTienMap=['thap'=>['label'=>'Thấp','class'=>'badge-secondary'],'trung_binh'=>['label'=>'Trung bình','class'=>'badge-info'],'cao'=>['label'=>'Cao','class'=>'badge-warning'],'khan_cap'=>['label'=>'🚨 Khẩn cấp','class'=>'badge-danger']];
$loaiMap=['dien'=>'⚡ Điện','nuoc'=>'💧 Nước','co_so_vat_chat'=>'🏗️ CSVC','khac'=>'🔧 Khác'];
include 'includes/header.php';
?>
<div class="page-header">
    <div><h2>🔧 Quản lý Bảo trì</h2><p>Theo dõi và xử lý yêu cầu bảo trì</p></div>
    <button class="btn btn-primary" onclick="openModal('modal-add')">➕ Tạo yêu cầu</button>
</div>
<div class="card" style="margin-bottom:16px"><div class="card-body" style="padding:14px 20px">
    <form method="GET" class="search-bar">
        <div class="search-input"><span class="search-icon">🔍</span><input type="text" name="q" placeholder="Tìm phòng, tiêu đề..." value="<?= sanitize($fQ) ?>"></div>
        <select name="trang_thai" style="min-width:160px"><option value="">Tất cả TT</option><option value="cho_xu_ly" <?= $fTT=='cho_xu_ly'?'selected':'' ?>>📋 Chờ xử lý</option><option value="dang_xu_ly" <?= $fTT=='dang_xu_ly'?'selected':'' ?>>🔧 Đang xử lý</option><option value="hoan_thanh" <?= $fTT=='hoan_thanh'?'selected':'' ?>>✅ Hoàn thành</option></select>
        <select name="loai" style="min-width:130px"><option value="">Tất cả loại</option><option value="dien">⚡ Điện</option><option value="nuoc">💧 Nước</option><option value="co_so_vat_chat">🏗️ CSVC</option><option value="khac">🔧 Khác</option></select>
        <button type="submit" class="btn btn-primary">🔍 Lọc</button>
        <a href="bao_tri.php" class="btn btn-secondary">↩ Reset</a>
    </form>
</div></div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead><tr><th>#</th><th>Tiêu đề</th><th>Phòng</th><th>Loại</th><th>Ưu tiên</th><th>Ngày yêu cầu</th><th>Chi phí</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
            <tbody>
            <?php if (empty($list)): ?>
            <tr><td colspan="9"><div class="empty-state"><div class="empty-icon">🔧</div><p>Không có yêu cầu bảo trì</p></div></td></tr>
            <?php else: ?>
            <?php foreach ($list as $i => $bt): ?>
            <tr>
                <td class="text-muted"><?= ($page-1)*$perPage+$i+1 ?></td>
                <td>
                    <div style="font-weight:600"><?= sanitize($bt['tieu_de']) ?></div>
                    <?php if ($bt['sv_ho_ten']): ?><div style="font-size:11.5px;color:#64748b">👤 <?= sanitize($bt['sv_ho_ten']) ?></div><?php endif; ?>
                    <?php if ($bt['mo_ta']): ?><div style="font-size:11.5px;color:#94a3b8;margin-top:2px"><?= sanitize(mb_substr($bt['mo_ta'],0,60)) ?>...</div><?php endif; ?>
                </td>
                <td><code><?= sanitize($bt['ten_toa']) ?> - <?= sanitize($bt['so_phong']) ?></code></td>
                <td><?= $loaiMap[$bt['loai']]??$bt['loai'] ?></td>
                <td><span class="badge <?= $uuTienMap[$bt['muc_do_uu_tien']]['class'] ?>"><?= $uuTienMap[$bt['muc_do_uu_tien']]['label'] ?></span></td>
                <td><?= formatDate($bt['ngay_yeu_cau']) ?><?php if ($bt['ngay_hoan_thanh']): ?><div style="font-size:11px;color:#059669">✅ <?= formatDate($bt['ngay_hoan_thanh']) ?></div><?php endif; ?></td>
                <td style="font-weight:700;color:<?= $bt['chi_phi']>0?'#dc2626':'#64748b' ?>"><?= $bt['chi_phi']>0?formatMoney($bt['chi_phi']):'—' ?></td>
                <td><?= getStatusBadge($bt['trang_thai'],'bao_tri') ?></td>
                <td><div class="btn-group"><a href="bao_tri.php?edit=<?= $bt['id'] ?>" class="btn btn-sm btn-outline">✏️</a><form method="POST" style="display:inline" onsubmit="return confirm('Xóa?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $bt['id'] ?>"><button type="submit" class="btn btn-sm btn-danger">🗑️</button></form></div></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php foreach (['modal-add'=>false,'modal-edit'=>$editData] as $mid=>$fd):
if ($mid==='modal-edit' && !$fd) continue;
$fd=$fd?:['phong_id'=>'','sinh_vien_id'=>'','tieu_de'=>'','mo_ta'=>'','loai'=>'khac','muc_do_uu_tien'=>'trung_binh','trang_thai'=>'cho_xu_ly','ngay_yeu_cau'=>date('Y-m-d'),'ngay_hoan_thanh'=>'','chi_phi'=>0,'ghi_chu'=>''];
$isE=$mid==='modal-edit';
?>
<div class="modal-overlay <?= $isE?'show':'' ?>" id="<?= $mid ?>">
    <div class="modal" style="max-width:650px">
        <div class="modal-header">
            <div class="modal-title"><?= $isE?'✏️ Sửa yêu cầu':'➕ Tạo yêu cầu bảo trì' ?></div>
            <?php if ($isE): ?><a href="bao_tri.php" class="modal-close">✕</a><?php else: ?><button class="modal-close" onclick="closeModal('modal-add')">✕</button><?php endif; ?>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="<?= $isE?'edit':'add' ?>">
            <?php if ($isE): ?><input type="hidden" name="id" value="<?= $fd['id'] ?>"><?php endif; ?>
            <div class="modal-body">
                <div class="form-grid form-grid-2">
                    <div class="form-group full"><label>Tiêu đề *</label><input type="text" name="tieu_de" value="<?= sanitize($fd['tieu_de']) ?>" placeholder="Mô tả ngắn vấn đề..." required></div>
                    <div class="form-group"><label>Phòng *</label><select name="phong_id" required><?php foreach ($phongList as $p): ?><option value="<?= $p['id'] ?>" <?= $fd['phong_id']==$p['id']?'selected':'' ?>><?= sanitize($p['ten_toa']) ?> - <?= sanitize($p['so_phong']) ?></option><?php endforeach; ?></select></div>
                    <div class="form-group"><label>Sinh viên yêu cầu</label><select name="sinh_vien_id"><option value="">— Không xác định —</option><?php foreach ($svList as $sv): ?><option value="<?= $sv['id'] ?>" <?= $fd['sinh_vien_id']==$sv['id']?'selected':'' ?>><?= sanitize($sv['ho_ten']) ?></option><?php endforeach; ?></select></div>
                    <div class="form-group"><label>Loại</label><select name="loai"><?php foreach (['dien'=>'⚡ Điện','nuoc'=>'💧 Nước','co_so_vat_chat'=>'🏗️ CSVC','khac'=>'🔧 Khác'] as $v=>$l): ?><option value="<?= $v ?>" <?= $fd['loai']==$v?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
                    <div class="form-group"><label>Mức độ ưu tiên</label><select name="muc_do_uu_tien"><?php foreach (['thap'=>'🟢 Thấp','trung_binh'=>'🔵 Trung bình','cao'=>'🟡 Cao','khan_cap'=>'🚨 Khẩn cấp'] as $v=>$l): ?><option value="<?= $v ?>" <?= $fd['muc_do_uu_tien']==$v?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
                    <div class="form-group"><label>Trạng thái</label><select name="trang_thai"><?php foreach (['cho_xu_ly'=>'📋 Chờ xử lý','dang_xu_ly'=>'🔧 Đang xử lý','hoan_thanh'=>'✅ Hoàn thành','huy'=>'❌ Hủy'] as $v=>$l): ?><option value="<?= $v ?>" <?= $fd['trang_thai']==$v?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
                    <div class="form-group"><label>Ngày yêu cầu *</label><input type="date" name="ngay_yeu_cau" value="<?= $fd['ngay_yeu_cau'] ?>" required></div>
                    <div class="form-group"><label>Ngày hoàn thành</label><input type="date" name="ngay_hoan_thanh" value="<?= $fd['ngay_hoan_thanh']??'' ?>"></div>
                    <div class="form-group"><label>Chi phí (đ)</label><input type="number" name="chi_phi" value="<?= $fd['chi_phi']??0 ?>" min="0"></div>
                    <div class="form-group full"><label>Mô tả chi tiết</label><textarea name="mo_ta"><?= sanitize($fd['mo_ta']??'') ?></textarea></div>
                    <div class="form-group full"><label>Ghi chú</label><textarea name="ghi_chu"><?= sanitize($fd['ghi_chu']??'') ?></textarea></div>
                </div>
            </div>
            <div class="modal-footer">
                <?php if ($isE): ?><a href="bao_tri.php" class="btn btn-secondary">Hủy</a><?php else: ?><button type="button" class="btn btn-secondary" onclick="closeModal('modal-add')">Hủy</button><?php endif; ?>
                <button type="submit" class="btn btn-primary">💾 <?= $isE?'Cập nhật':'Tạo yêu cầu' ?></button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>
<?php include 'includes/footer.php'; ?>
