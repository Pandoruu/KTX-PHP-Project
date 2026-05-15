<?php
require_once 'config.php';
requireLogin();
$pageTitle = 'Quản lý Vi phạm';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add' || $action === 'edit') {
        $sv_id  = (int)$_POST['sinh_vien_id'];
        $loai   = trim($_POST['loai_vi_pham']??'');
        $mo     = trim($_POST['mo_ta']??'');
        $muc    = $_POST['muc_do']??'nhe';
        $xu     = trim($_POST['xu_phat']??'');
        $tien   = (int)($_POST['so_tien_phat']??0);
        $ngay   = $_POST['ngay_vi_pham'];
        $tt     = $_POST['trang_thai']??'cho_xu_ly';

        if ($action === 'add') {
            $db->prepare("INSERT INTO vi_pham (sinh_vien_id,loai_vi_pham,mo_ta,muc_do,xu_phat,so_tien_phat,ngay_vi_pham,trang_thai) VALUES (?,?,?,?,?,?,?,?)")
               ->execute([$sv_id,$loai,$mo,$muc,$xu,$tien,$ngay,$tt]);
            setFlash('success', 'Ghi nhận vi phạm thành công!');
        } else {
            $id = (int)$_POST['id'];
            $db->prepare("UPDATE vi_pham SET sinh_vien_id=?,loai_vi_pham=?,mo_ta=?,muc_do=?,xu_phat=?,so_tien_phat=?,ngay_vi_pham=?,trang_thai=? WHERE id=?")
               ->execute([$sv_id,$loai,$mo,$muc,$xu,$tien,$ngay,$tt,$id]);
            setFlash('success', 'Cập nhật vi phạm thành công!');
        }
        redirect('vi_pham.php');
    }
    if ($action === 'delete') {
        $db->prepare("DELETE FROM vi_pham WHERE id=?")->execute([(int)$_POST['id']]);
        setFlash('success', 'Đã xóa vi phạm!');
        redirect('vi_pham.php');
    }
}

// Filter
$where = []; $params = [];
$fTT = $_GET['trang_thai']??''; $fMuc = $_GET['muc_do']??''; $fQ = trim($_GET['q']??'');
if ($fTT)  { $where[] = 'vp.trang_thai=?'; $params[] = $fTT; }
if ($fMuc) { $where[] = 'vp.muc_do=?';     $params[] = $fMuc; }
if ($fQ)   { $where[] = '(sv.ho_ten LIKE ? OR sv.ma_sv LIKE ? OR vp.loai_vi_pham LIKE ?)'; $params=array_merge($params,["%$fQ%","%$fQ%","%$fQ%"]); }
$whereStr = $where ? 'WHERE '.implode(' AND ',$where) : '';

$perPage = 15; $page = max(1,(int)($_GET['page']??1));
$total = $db->prepare("SELECT COUNT(*) FROM vi_pham vp JOIN sinh_vien sv ON vp.sinh_vien_id=sv.id $whereStr");
$total->execute($params); $total = $total->fetchColumn();
$totalPages = ceil($total/$perPage); $offset = ($page-1)*$perPage;

$list = $db->prepare("SELECT vp.*,sv.ho_ten,sv.ma_sv FROM vi_pham vp JOIN sinh_vien sv ON vp.sinh_vien_id=sv.id $whereStr ORDER BY vp.ngay_vi_pham DESC LIMIT $perPage OFFSET $offset");
$list->execute($params); $list = $list->fetchAll();

$svList = $db->query("SELECT id,ma_sv,ho_ten FROM sinh_vien WHERE trang_thai='dang_o' ORDER BY ho_ten")->fetchAll();
$editData = null;
if (isset($_GET['edit'])) { $s=$db->prepare("SELECT * FROM vi_pham WHERE id=?"); $s->execute([(int)$_GET['edit']]); $editData=$s->fetch(); }

$mucDoMap = ['nhe'=>['label'=>'Nhẹ','class'=>'badge-info'],'trung_binh'=>['label'=>'Trung bình','class'=>'badge-warning'],'nghiem_trong'=>['label'=>'Nghiêm trọng','class'=>'badge-danger']];
include 'includes/header.php';
?>
<div class="page-header">
    <div><h2>⚠️ Quản lý Vi phạm</h2><p>Theo dõi kỷ luật sinh viên</p></div>
    <button class="btn btn-primary" onclick="openModal('modal-add')">➕ Ghi nhận vi phạm</button>
</div>
<div class="card" style="margin-bottom:16px">
    <div class="card-body" style="padding:14px 20px">
        <form method="GET" class="search-bar">
            <div class="search-input"><span class="search-icon">🔍</span><input type="text" name="q" placeholder="Tìm sinh viên, loại vi phạm..." value="<?= sanitize($fQ) ?>"></div>
            <select name="trang_thai" style="min-width:150px">
                <option value="">Tất cả TT</option>
                <option value="cho_xu_ly" <?= $fTT=='cho_xu_ly'?'selected':'' ?>>⚠️ Chờ xử lý</option>
                <option value="da_xu_ly" <?= $fTT=='da_xu_ly'?'selected':'' ?>>✅ Đã xử lý</option>
            </select>
            <select name="muc_do" style="min-width:140px">
                <option value="">Tất cả mức độ</option>
                <option value="nhe" <?= $fMuc=='nhe'?'selected':'' ?>>🟢 Nhẹ</option>
                <option value="trung_binh" <?= $fMuc=='trung_binh'?'selected':'' ?>>🟡 Trung bình</option>
                <option value="nghiem_trong" <?= $fMuc=='nghiem_trong'?'selected':'' ?>>🔴 Nghiêm trọng</option>
            </select>
            <button type="submit" class="btn btn-primary">🔍 Lọc</button>
            <a href="vi_pham.php" class="btn btn-secondary">↩ Reset</a>
        </form>
    </div>
</div>
<div class="card">
    <div class="table-wrapper">
        <table>
            <thead><tr><th>#</th><th>Sinh viên</th><th>Loại vi phạm</th><th>Mô tả</th><th>Mức độ</th><th>Xử phạt</th><th>Tiền phạt</th><th>Ngày VP</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
            <tbody>
            <?php if (empty($list)): ?>
            <tr><td colspan="10"><div class="empty-state"><div class="empty-icon">✅</div><p>Không có vi phạm nào</p></div></td></tr>
            <?php else: ?>
            <?php foreach ($list as $i => $vp): ?>
            <tr>
                <td class="text-muted"><?= ($page-1)*$perPage+$i+1 ?></td>
                <td><div class="flex-center" style="gap:8px"><div class="avatar-sm"><?= strtoupper(mb_substr($vp['ho_ten'],0,1)) ?></div><div><div style="font-weight:600"><?= sanitize($vp['ho_ten']) ?></div><div style="font-size:11px;color:#64748b"><?= sanitize($vp['ma_sv']) ?></div></div></div></td>
                <td style="font-weight:600"><?= sanitize($vp['loai_vi_pham']) ?></td>
                <td style="max-width:180px;font-size:12.5px;color:#64748b"><?= sanitize(mb_substr($vp['mo_ta']??'',0,80)) ?></td>
                <td><span class="badge <?= $mucDoMap[$vp['muc_do']]['class'] ?>"><?= $mucDoMap[$vp['muc_do']]['label'] ?></span></td>
                <td style="font-size:12.5px"><?= sanitize($vp['xu_phat']??'—') ?></td>
                <td style="font-weight:700;color:<?= $vp['so_tien_phat']>0?'#dc2626':'#64748b' ?>"><?= $vp['so_tien_phat']>0?formatMoney($vp['so_tien_phat']):'—' ?></td>
                <td><?= formatDate($vp['ngay_vi_pham']) ?></td>
                <td><?= getStatusBadge($vp['trang_thai'],'vi_pham') ?></td>
                <td><div class="btn-group"><a href="vi_pham.php?edit=<?= $vp['id'] ?>" class="btn btn-sm btn-outline">✏️</a><form method="POST" style="display:inline" onsubmit="return confirm('Xóa?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $vp['id'] ?>"><button type="submit" class="btn btn-sm btn-danger">🗑️</button></form></div></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php foreach (['modal-add'=>false,'modal-edit'=>$editData] as $mid=>$fd):
if ($mid==='modal-edit' && !$fd) continue;
$fd = $fd ?: ['sinh_vien_id'=>'','loai_vi_pham'=>'','mo_ta'=>'','muc_do'=>'nhe','xu_phat'=>'','so_tien_phat'=>0,'ngay_vi_pham'=>date('Y-m-d'),'trang_thai'=>'cho_xu_ly'];
$isE = $mid==='modal-edit';
?>
<div class="modal-overlay <?= $isE?'show':'' ?>" id="<?= $mid ?>">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><?= $isE?'✏️ Sửa vi phạm':'➕ Ghi nhận vi phạm' ?></div>
            <?php if ($isE): ?><a href="vi_pham.php" class="modal-close">✕</a>
            <?php else: ?><button class="modal-close" onclick="closeModal('modal-add')">✕</button><?php endif; ?>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="<?= $isE?'edit':'add' ?>">
            <?php if ($isE): ?><input type="hidden" name="id" value="<?= $fd['id'] ?>"><?php endif; ?>
            <div class="modal-body">
                <div class="form-grid form-grid-2">
                    <div class="form-group full">
                        <label>Sinh viên *</label>
                        <select name="sinh_vien_id" required>
                            <option value="">— Chọn sinh viên —</option>
                            <?php foreach ($svList as $sv): ?><option value="<?= $sv['id'] ?>" <?= $fd['sinh_vien_id']==$sv['id']?'selected':'' ?>>[<?= sanitize($sv['ma_sv']) ?>] <?= sanitize($sv['ho_ten']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Loại vi phạm</label><input type="text" name="loai_vi_pham" value="<?= sanitize($fd['loai_vi_pham']??'') ?>" placeholder="VD: Về muộn, Gây ồn..."></div>
                    <div class="form-group"><label>Ngày vi phạm *</label><input type="date" name="ngay_vi_pham" value="<?= $fd['ngay_vi_pham'] ?>" required></div>
                    <div class="form-group"><label>Mức độ</label><select name="muc_do"><?php foreach (['nhe'=>'🟢 Nhẹ','trung_binh'=>'🟡 Trung bình','nghiem_trong'=>'🔴 Nghiêm trọng'] as $v=>$l): ?><option value="<?= $v ?>" <?= $fd['muc_do']==$v?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
                    <div class="form-group"><label>Trạng thái</label><select name="trang_thai"><option value="cho_xu_ly" <?= $fd['trang_thai']=='cho_xu_ly'?'selected':'' ?>>⚠️ Chờ xử lý</option><option value="da_xu_ly" <?= $fd['trang_thai']=='da_xu_ly'?'selected':'' ?>>✅ Đã xử lý</option></select></div>
                    <div class="form-group"><label>Tiền phạt (đ)</label><input type="number" name="so_tien_phat" value="<?= $fd['so_tien_phat']??0 ?>" min="0"></div>
                    <div class="form-group full"><label>Hình thức xử phạt</label><input type="text" name="xu_phat" value="<?= sanitize($fd['xu_phat']??'') ?>" placeholder="VD: Cảnh cáo, phạt tiền..."></div>
                    <div class="form-group full"><label>Mô tả chi tiết</label><textarea name="mo_ta"><?= sanitize($fd['mo_ta']??'') ?></textarea></div>
                </div>
            </div>
            <div class="modal-footer">
                <?php if ($isE): ?><a href="vi_pham.php" class="btn btn-secondary">Hủy</a><?php else: ?><button type="button" class="btn btn-secondary" onclick="closeModal('modal-add')">Hủy</button><?php endif; ?>
                <button type="submit" class="btn btn-primary">💾 <?= $isE?'Cập nhật':'Lưu' ?></button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>
<?php include 'includes/footer.php'; ?>
