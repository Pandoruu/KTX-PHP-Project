<?php
require_once 'config.php';
requireLogin();
$pageTitle = 'Quản lý Hợp đồng';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $sv_id    = (int)$_POST['sinh_vien_id'];
        $phong_id = (int)$_POST['phong_id'];
        $ngay_bd  = $_POST['ngay_bat_dau'];
        $ngay_kt  = $_POST['ngay_ket_thuc'];
        $gia      = (int)$_POST['gia_thue'];
        $coc      = (int)($_POST['tien_coc'] ?? 0);
        $tt       = $_POST['trang_thai'] ?? 'hieu_luc';
        $ghichu   = trim($_POST['ghi_chu'] ?? '');

        if (!$sv_id || !$phong_id || !$ngay_bd || !$ngay_kt) {
            setFlash('error', 'Vui lòng điền đầy đủ thông tin!');
            redirect('hop_dong.php');
        }

        if ($action === 'add') {
            $ma = 'HD' . date('Y') . str_pad($db->query("SELECT COALESCE(MAX(id),0)+1 FROM hop_dong")->fetchColumn(), 4, '0', STR_PAD_LEFT);
            $db->prepare("INSERT INTO hop_dong (ma_hop_dong,sinh_vien_id,phong_id,ngay_bat_dau,ngay_ket_thuc,gia_thue,tien_coc,trang_thai,ghi_chu) VALUES (?,?,?,?,?,?,?,?,?)")
               ->execute([$ma,$sv_id,$phong_id,$ngay_bd,$ngay_kt,$gia,$coc,$tt,$ghichu]);
            // Cập nhật trạng thái phòng
            if ($tt === 'hieu_luc') {
                $sv_cnt = $db->prepare("SELECT COUNT(*) FROM hop_dong WHERE phong_id=? AND trang_thai='hieu_luc'");
                $sv_cnt->execute([$phong_id]);
                $phong_info = $db->prepare("SELECT so_giuong FROM phong WHERE id=?");
                $phong_info->execute([$phong_id]);
                $pg = $phong_info->fetch();
                $new_tt = ($sv_cnt->fetchColumn() >= $pg['so_giuong']) ? 'day' : 'trong';
                $db->prepare("UPDATE phong SET trang_thai=? WHERE id=?")->execute([$new_tt, $phong_id]);
            }
            setFlash('success', "Tạo hợp đồng $ma thành công!");
        } else {
            $id = (int)$_POST['id'];
            $db->prepare("UPDATE hop_dong SET sinh_vien_id=?,phong_id=?,ngay_bat_dau=?,ngay_ket_thuc=?,gia_thue=?,tien_coc=?,trang_thai=?,ghi_chu=? WHERE id=?")
               ->execute([$sv_id,$phong_id,$ngay_bd,$ngay_kt,$gia,$coc,$tt,$ghichu,$id]);
            setFlash('success', 'Cập nhật hợp đồng thành công!');
        }
        redirect('hop_dong.php');
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $db->prepare("DELETE FROM hop_dong WHERE id=?")->execute([$id]);
        setFlash('success', 'Đã xóa hợp đồng!');
        redirect('hop_dong.php');
    }
}

// Filter
$where = []; $params = [];
$fTT   = $_GET['trang_thai'] ?? '';
$fQ    = trim($_GET['q'] ?? '');
$fPhong= $_GET['phong'] ?? '';

if ($fTT)    { $where[] = 'hd.trang_thai=?'; $params[] = $fTT; }
if ($fPhong) { $where[] = 'hd.phong_id=?';   $params[] = $fPhong; }
if ($fQ)     { $where[] = '(hd.ma_hop_dong LIKE ? OR sv.ho_ten LIKE ? OR sv.ma_sv LIKE ?)'; $params = array_merge($params,["%$fQ%","%$fQ%","%$fQ%"]); }
$whereStr = $where ? 'WHERE '.implode(' AND ',$where) : '';

$perPage = 15;
$page = max(1,(int)($_GET['page']??1));
$total = $db->prepare("SELECT COUNT(*) FROM hop_dong hd JOIN sinh_vien sv ON hd.sinh_vien_id=sv.id $whereStr");
$total->execute($params); $total = $total->fetchColumn();
$totalPages = ceil($total/$perPage);
$offset = ($page-1)*$perPage;

$list = $db->prepare("
    SELECT hd.*, sv.ho_ten, sv.ma_sv, p.so_phong, tn.ten_toa,
        DATEDIFF(hd.ngay_ket_thuc, CURDATE()) as ngay_con_lai
    FROM hop_dong hd
    JOIN sinh_vien sv ON hd.sinh_vien_id=sv.id
    JOIN phong p ON hd.phong_id=p.id
    JOIN toa_nha tn ON p.toa_nha_id=tn.id
    $whereStr
    ORDER BY hd.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$list->execute($params);
$list = $list->fetchAll();

$svList    = $db->query("SELECT id,ma_sv,ho_ten FROM sinh_vien ORDER BY ho_ten")->fetchAll();
$phongList = $db->query("SELECT p.*,tn.ten_toa FROM phong p JOIN toa_nha tn ON p.toa_nha_id=tn.id ORDER BY tn.ten_toa,p.so_phong")->fetchAll();

$editData = null;
if (isset($_GET['edit'])) {
    $s = $db->prepare("SELECT * FROM hop_dong WHERE id=?");
    $s->execute([(int)$_GET['edit']]);
    $editData = $s->fetch();
}

include 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h2>📋 Quản lý Hợp đồng</h2>
        <p>Tổng <?= $total ?> hợp đồng</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modal-add')">➕ Tạo hợp đồng</button>
</div>

<!-- FILTER -->
<div class="card" style="margin-bottom:16px">
    <div class="card-body" style="padding:14px 20px">
        <form method="GET" class="search-bar">
            <div class="search-input">
                <span class="search-icon">🔍</span>
                <input type="text" name="q" placeholder="Tìm mã HĐ, tên SV..." value="<?= sanitize($fQ) ?>">
            </div>
            <select name="trang_thai" style="min-width:150px">
                <option value="">Tất cả trạng thái</option>
                <option value="hieu_luc" <?= $fTT=='hieu_luc'?'selected':'' ?>>✅ Hiệu lực</option>
                <option value="het_han" <?= $fTT=='het_han'?'selected':'' ?>>⏰ Hết hạn</option>
                <option value="da_huy" <?= $fTT=='da_huy'?'selected':'' ?>>❌ Đã hủy</option>
            </select>
            <button type="submit" class="btn btn-primary">🔍 Lọc</button>
            <a href="hop_dong.php" class="btn btn-secondary">↩ Reset</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Mã HĐ</th>
                    <th>Sinh viên</th>
                    <th>Phòng</th>
                    <th>Thời hạn</th>
                    <th>Giá thuê</th>
                    <th>Tiền cọc</th>
                    <th>Còn lại</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($list)): ?>
            <tr><td colspan="10"><div class="empty-state"><div class="empty-icon">📋</div><p>Không có hợp đồng nào</p></div></td></tr>
            <?php else: ?>
            <?php foreach ($list as $i => $hd): ?>
            <?php
                $days = $hd['ngay_con_lai'];
                $dayColor = $days < 0 ? '#dc2626' : ($days <= 7 ? '#d97706' : ($days <= 30 ? '#ca8a04' : '#059669'));
            ?>
            <tr>
                <td class="text-muted"><?= ($page-1)*$perPage+$i+1 ?></td>
                <td><code><?= sanitize($hd['ma_hop_dong']) ?></code></td>
                <td>
                    <div class="flex-center" style="gap:8px">
                        <div class="avatar-sm"><?= strtoupper(mb_substr($hd['ho_ten'],0,1)) ?></div>
                        <div>
                            <div style="font-weight:600"><?= sanitize($hd['ho_ten']) ?></div>
                            <div style="font-size:11px;color:#64748b"><?= sanitize($hd['ma_sv']) ?></div>
                        </div>
                    </div>
                </td>
                <td><code><?= sanitize($hd['ten_toa']) ?> - <?= sanitize($hd['so_phong']) ?></code></td>
                <td style="font-size:12.5px">
                    <div><?= formatDate($hd['ngay_bat_dau']) ?></div>
                    <div style="color:#64748b">→ <?= formatDate($hd['ngay_ket_thuc']) ?></div>
                </td>
                <td style="font-weight:700;color:#1a56db"><?= formatMoney($hd['gia_thue']) ?></td>
                <td><?= formatMoney($hd['tien_coc']) ?></td>
                <td>
                    <?php if ($hd['trang_thai'] === 'hieu_luc'): ?>
                    <span style="font-weight:700;color:<?= $dayColor ?>"><?= $days >= 0 ? $days.' ngày' : abs($days).' ngày trễ' ?></span>
                    <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                </td>
                <td><?= getStatusBadge($hd['trang_thai'],'hop_dong') ?></td>
                <td>
                    <div class="btn-group">
                        <a href="hop_dong.php?edit=<?= $hd['id'] ?>" class="btn btn-sm btn-outline">✏️</a>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Xóa hợp đồng này?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $hd['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">🗑️</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($totalPages > 1): ?>
    <div style="padding:12px 20px;border-top:1px solid var(--border)">
        <div class="flex-between">
            <span class="text-muted" style="font-size:13px">Hiển thị <?= ($page-1)*$perPage+1 ?>–<?= min($page*$perPage,$total) ?> / <?= $total ?></span>
            <div class="pagination">
                <?php for($i=1;$i<=$totalPages;$i++): $q=http_build_query(array_merge($_GET,['page'=>$i])); ?>
                <a href="?<?= $q ?>" class="page-btn <?= $i==$page?'active':'' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- MODAL FORM -->
<?php foreach (['modal-add'=>false,'modal-edit'=>$editData] as $mid=>$fd):
if ($mid==='modal-edit' && !$fd) continue;
$fd = $fd ?: ['sinh_vien_id'=>'','phong_id'=>'','ngay_bat_dau'=>date('Y-m-d'),'ngay_ket_thuc'=>date('Y-m-d',strtotime('+1 year')),'gia_thue'=>800000,'tien_coc'=>1600000,'trang_thai'=>'hieu_luc','ghi_chu'=>''];
$isE = $mid === 'modal-edit';
?>
<div class="modal-overlay <?= $isE?'show':'' ?>" id="<?= $mid ?>">
    <div class="modal" style="max-width:650px">
        <div class="modal-header">
            <div class="modal-title"><?= $isE?'✏️ Sửa hợp đồng':'➕ Tạo hợp đồng mới' ?></div>
            <?php if ($isE): ?><a href="hop_dong.php" class="modal-close">✕</a>
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
                            <?php foreach ($svList as $sv): ?>
                            <option value="<?= $sv['id'] ?>" <?= $fd['sinh_vien_id']==$sv['id']?'selected':'' ?>>[<?= sanitize($sv['ma_sv']) ?>] <?= sanitize($sv['ho_ten']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label>Phòng *</label>
                        <select name="phong_id" required>
                            <option value="">— Chọn phòng —</option>
                            <?php foreach ($phongList as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= $fd['phong_id']==$p['id']?'selected':'' ?>>
                                <?= sanitize($p['ten_toa']) ?> - <?= sanitize($p['so_phong']) ?> (<?= formatMoney($p['gia_thue']) ?>/tháng · <?= $p['trang_thai']=='trong'?'Còn trống':'Đã đầy' ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Ngày bắt đầu *</label>
                        <input type="date" name="ngay_bat_dau" value="<?= $fd['ngay_bat_dau'] ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Ngày kết thúc *</label>
                        <input type="date" name="ngay_ket_thuc" value="<?= $fd['ngay_ket_thuc'] ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Giá thuê/tháng (đ) *</label>
                        <input type="number" name="gia_thue" value="<?= $fd['gia_thue'] ?>" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Tiền cọc (đ)</label>
                        <input type="number" name="tien_coc" value="<?= $fd['tien_coc'] ?>" min="0">
                    </div>
                    <div class="form-group">
                        <label>Trạng thái</label>
                        <select name="trang_thai">
                            <option value="hieu_luc" <?= $fd['trang_thai']=='hieu_luc'?'selected':'' ?>>✅ Hiệu lực</option>
                            <option value="het_han" <?= $fd['trang_thai']=='het_han'?'selected':'' ?>>⏰ Hết hạn</option>
                            <option value="da_huy" <?= $fd['trang_thai']=='da_huy'?'selected':'' ?>>❌ Đã hủy</option>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label>Ghi chú</label>
                        <textarea name="ghi_chu"><?= sanitize($fd['ghi_chu']??'') ?></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <?php if ($isE): ?>
                <a href="hop_dong.php" class="btn btn-secondary">Hủy</a>
                <?php else: ?>
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-add')">Hủy</button>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary">💾 <?= $isE?'Cập nhật':'Tạo hợp đồng' ?></button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<?php include 'includes/footer.php'; ?>
