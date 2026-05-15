<?php
require_once 'config.php';
requireLogin();
$pageTitle = 'Quản lý Phòng ở';
$db = getDB();

// XỬ LÝ FORM
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $toa    = (int)$_POST['toa_nha_id'];
        $so     = trim($_POST['so_phong'] ?? '');
        $tang   = (int)$_POST['tang'];
        $loai   = $_POST['loai_phong'] ?? 'doi';
        $gia    = (int)$_POST['gia_thue'];
        $giuong = (int)$_POST['so_giuong'];
        $dt     = $_POST['dien_tich'] ? (float)$_POST['dien_tich'] : 0;
        $tt     = $_POST['trang_thai'] ?? 'trong';
        $mo     = trim($_POST['mo_ta'] ?? '');

        if (!$toa || !$so) { setFlash('error', 'Vui lòng điền đầy đủ thông tin!'); redirect('phong.php'); }

        if ($action === 'add') {
            try {
                $stmt = $db->prepare("INSERT INTO phong (toa_nha_id,so_phong,tang,loai_phong,gia_thue,so_giuong,dien_tich,trang_thai,mo_ta) VALUES (?,?,?,?,?,?,?,?,?)");
                $stmt->execute([$toa,$so,$tang,$loai,$gia,$giuong,$dt,$tt,$mo]);
                setFlash('success', "Thêm phòng $so thành công!");
            } catch (PDOException $e) {
                setFlash('error', 'Số phòng đã tồn tại trong tòa nhà này!');
            }
        } else {
            $id = (int)$_POST['id'];
            $stmt = $db->prepare("UPDATE phong SET toa_nha_id=?,so_phong=?,tang=?,loai_phong=?,gia_thue=?,so_giuong=?,dien_tich=?,trang_thai=?,mo_ta=? WHERE id=?");
            $stmt->execute([$toa,$so,$tang,$loai,$gia,$giuong,$dt,$tt,$mo,$id]);
            setFlash('success', 'Cập nhật phòng thành công!');
        }
        redirect('phong.php');
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $cnt = $db->prepare("SELECT COUNT(*) FROM hop_dong WHERE phong_id=? AND trang_thai='hieu_luc'");
        $cnt->execute([$id]);
        if ($cnt->fetchColumn() > 0) {
            setFlash('error', 'Không thể xóa phòng đang có sinh viên ở!');
        } else {
            $db->prepare("DELETE FROM phong WHERE id=?")->execute([$id]);
            setFlash('success', 'Đã xóa phòng!');
        }
        redirect('phong.php');
    }
}

// LẤY DỮ LIỆU
$toa_nhas = $db->query("SELECT * FROM toa_nha ORDER BY ten_toa")->fetchAll();

// Filter
$where = []; $params = [];
$fToa  = $_GET['toa'] ?? '';
$fTT   = $_GET['trang_thai'] ?? '';
$fLoai = $_GET['loai'] ?? '';
$fQ    = trim($_GET['q'] ?? '');

if ($fToa)  { $where[] = 'p.toa_nha_id = ?'; $params[] = $fToa; }
if ($fTT)   { $where[] = 'p.trang_thai = ?'; $params[] = $fTT; }
if ($fLoai) { $where[] = 'p.loai_phong = ?'; $params[] = $fLoai; }
if ($fQ)    { $where[] = '(p.so_phong LIKE ? OR tn.ten_toa LIKE ?)'; $params[] = "%$fQ%"; $params[] = "%$fQ%"; }

$whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Pagination
$perPage = 15;
$page = max(1, (int)($_GET['page'] ?? 1));
$total = $db->prepare("SELECT COUNT(*) FROM phong p JOIN toa_nha tn ON p.toa_nha_id=tn.id $whereStr");
$total->execute($params);
$totalRows = $total->fetchColumn();
$totalPages = ceil($totalRows / $perPage);
$offset = ($page - 1) * $perPage;

$stmt = $db->prepare("
    SELECT p.*, tn.ten_toa,
        (SELECT COUNT(*) FROM hop_dong WHERE phong_id=p.id AND trang_thai='hieu_luc') as so_sv
    FROM phong p
    JOIN toa_nha tn ON p.toa_nha_id=tn.id
    $whereStr
    ORDER BY tn.ten_toa, p.tang, p.so_phong
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$list = $stmt->fetchAll();

// Edit
$editData = null;
if (isset($_GET['edit'])) {
    $s = $db->prepare("SELECT * FROM phong WHERE id=?");
    $s->execute([(int)$_GET['edit']]);
    $editData = $s->fetch();
}

$loai_phong_map = ['don'=>'1 giường','doi'=>'2 giường','ba'=>'3 giường','bon'=>'4 giường'];

include 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h2>🚪 Quản lý Phòng ở</h2>
        <p>Tổng <?= $totalRows ?> phòng — <?= $db->query("SELECT COUNT(*) FROM phong WHERE trang_thai='trong'")->fetchColumn() ?> còn trống</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modal-add')">➕ Thêm phòng</button>
</div>

<!-- FILTER -->
<div class="card" style="margin-bottom:16px">
    <div class="card-body" style="padding:14px 20px">
        <form method="GET" class="search-bar">
            <div class="search-input">
                <span class="search-icon">🔍</span>
                <input type="text" name="q" placeholder="Tìm phòng..." value="<?= sanitize($fQ) ?>">
            </div>
            <select name="toa" style="min-width:130px">
                <option value="">Tất cả tòa</option>
                <?php foreach ($toa_nhas as $tn): ?>
                <option value="<?= $tn['id'] ?>" <?= $fToa==$tn['id']?'selected':'' ?>><?= sanitize($tn['ten_toa']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="trang_thai" style="min-width:140px">
                <option value="">Tất cả trạng thái</option>
                <option value="trong" <?= $fTT=='trong'?'selected':'' ?>>🟢 Còn trống</option>
                <option value="day" <?= $fTT=='day'?'selected':'' ?>>🔴 Đã đầy</option>
                <option value="bao_tri" <?= $fTT=='bao_tri'?'selected':'' ?>>🟡 Bảo trì</option>
            </select>
            <select name="loai" style="min-width:140px">
                <option value="">Tất cả loại</option>
                <option value="don" <?= $fLoai=='don'?'selected':'' ?>>Phòng đơn</option>
                <option value="doi" <?= $fLoai=='doi'?'selected':'' ?>>Phòng đôi</option>
                <option value="ba" <?= $fLoai=='ba'?'selected':'' ?>>Phòng 3</option>
                <option value="bon" <?= $fLoai=='bon'?'selected':'' ?>>Phòng 4</option>
            </select>
            <button type="submit" class="btn btn-primary">🔍 Lọc</button>
            <a href="phong.php" class="btn btn-secondary">↩ Reset</a>
        </form>
    </div>
</div>

<!-- TABLE -->
<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Phòng</th>
                    <th>Tòa nhà</th>
                    <th>Tầng</th>
                    <th>Loại phòng</th>
                    <th>Giá thuê/tháng</th>
                    <th>Giường</th>
                    <th>SV đang ở</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($list)): ?>
            <tr><td colspan="10"><div class="empty-state"><div class="empty-icon">🚪</div><p>Không tìm thấy phòng nào</p></div></td></tr>
            <?php else: ?>
            <?php foreach ($list as $i => $p): ?>
            <tr>
                <td class="text-muted"><?= ($page-1)*$perPage + $i + 1 ?></td>
                <td>
                    <div class="flex-center" style="gap:8px">
                        <div style="width:36px;height:36px;background:<?= $p['trang_thai']=='trong'?'#d1fae5':($p['trang_thai']=='day'?'#fee2e2':'#fef3c7') ?>;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:16px">🚪</div>
                        <div>
                            <div style="font-weight:700;font-size:14px"><?= sanitize($p['so_phong']) ?></div>
                            <?php if ($p['dien_tich']): ?><div style="font-size:11px;color:#64748b"><?= $p['dien_tich'] ?> m²</div><?php endif; ?>
                        </div>
                    </div>
                </td>
                <td><span class="badge badge-primary"><?= sanitize($p['ten_toa']) ?></span></td>
                <td>Tầng <?= $p['tang'] ?></td>
                <td><?= ucfirst($loai_phong_map[$p['loai_phong']] ?? $p['loai_phong']) ?></td>
                <td style="font-weight:700;color:#1a56db"><?= formatMoney($p['gia_thue']) ?></td>
                <td><?= $p['so_giuong'] ?> giường</td>
                <td>
                    <span style="font-weight:700"><?= $p['so_sv'] ?></span>/<?= $p['so_giuong'] ?>
                    <?php if ($p['so_sv'] == 0 && $p['trang_thai'] != 'bao_tri'): ?>
                    <span style="color:#059669;font-size:11px"> trống</span>
                    <?php endif; ?>
                </td>
                <td><?= getStatusBadge($p['trang_thai'], 'phong') ?></td>
                <td>
                    <div class="btn-group">
                        <a href="hop_dong.php?phong=<?= $p['id'] ?>" class="btn btn-sm btn-secondary" title="Xem hợp đồng">📋</a>
                        <a href="phong.php?edit=<?= $p['id'] ?>" class="btn btn-sm btn-outline">✏️</a>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Xóa phòng <?= sanitize($p['so_phong']) ?>?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
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

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div style="padding:12px 20px;border-top:1px solid var(--border)">
        <div class="flex-between">
            <span class="text-muted" style="font-size:13px">
                Hiển thị <?= ($page-1)*$perPage+1 ?>–<?= min($page*$perPage,$totalRows) ?> / <?= $totalRows ?> phòng
            </span>
            <div class="pagination">
                <?php
                $q = http_build_query(array_merge($_GET, ['page'=>$page-1]));
                echo '<a href="?'.$q.'" class="page-btn '.($page<=1?'disabled':'').'">‹</a>';
                for ($i=1;$i<=$totalPages;$i++):
                    $q2 = http_build_query(array_merge($_GET,['page'=>$i]));
                    echo '<a href="?'.$q2.'" class="page-btn '.($i==$page?'active':'').'">'.$i.'</a>';
                endfor;
                $q3 = http_build_query(array_merge($_GET,['page'=>$page+1]));
                echo '<a href="?'.$q3.'" class="page-btn '.($page>=$totalPages?'disabled':'').'">›</a>';
                ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- MODAL THÊM -->
<div class="modal-overlay" id="modal-add">
    <div class="modal" style="max-width:700px">
        <div class="modal-header">
            <div class="modal-title">➕ Thêm phòng mới</div>
            <button class="modal-close" onclick="closeModal('modal-add')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-grid form-grid-3">
                    <div class="form-group">
                        <label>Tòa nhà *</label>
                        <select name="toa_nha_id" required>
                            <option value="">— Chọn tòa —</option>
                            <?php foreach ($toa_nhas as $tn): ?>
                            <option value="<?= $tn['id'] ?>"><?= sanitize($tn['ten_toa']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Số phòng *</label>
                        <input type="text" name="so_phong" placeholder="VD: A101" required>
                    </div>
                    <div class="form-group">
                        <label>Tầng *</label>
                        <input type="number" name="tang" value="1" min="1" max="30" required>
                    </div>
                    <div class="form-group">
                        <label>Loại phòng</label>
                        <select name="loai_phong" onchange="updateGiuong(this)">
                            <option value="don">Phòng đơn (1 giường)</option>
                            <option value="doi" selected>Phòng đôi (2 giường)</option>
                            <option value="ba">Phòng 3 (3 giường)</option>
                            <option value="bon">Phòng 4 (4 giường)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Giá thuê/tháng (đ) *</label>
                        <input type="number" name="gia_thue" value="800000" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Số giường</label>
                        <input type="number" name="so_giuong" id="so_giuong_add" value="2" min="1" max="10">
                    </div>
                    <div class="form-group">
                        <label>Diện tích (m²)</label>
                        <input type="number" name="dien_tich" value="20" min="0" step="0.1">
                    </div>
                    <div class="form-group">
                        <label>Trạng thái</label>
                        <select name="trang_thai">
                            <option value="trong">🟢 Còn trống</option>
                            <option value="day">🔴 Đã đầy</option>
                            <option value="bao_tri">🟡 Bảo trì</option>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label>Mô tả</label>
                        <textarea name="mo_ta" placeholder="Ghi chú về phòng..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-add')">Hủy</button>
                <button type="submit" class="btn btn-primary">💾 Thêm phòng</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL SỬA -->
<?php if ($editData): ?>
<div class="modal-overlay show" id="modal-edit">
    <div class="modal" style="max-width:700px">
        <div class="modal-header">
            <div class="modal-title">✏️ Sửa phòng <?= sanitize($editData['so_phong']) ?></div>
            <a href="phong.php" class="modal-close">✕</a>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?= $editData['id'] ?>">
            <div class="modal-body">
                <div class="form-grid form-grid-3">
                    <div class="form-group">
                        <label>Tòa nhà *</label>
                        <select name="toa_nha_id" required>
                            <?php foreach ($toa_nhas as $tn): ?>
                            <option value="<?= $tn['id'] ?>" <?= $editData['toa_nha_id']==$tn['id']?'selected':'' ?>><?= sanitize($tn['ten_toa']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Số phòng *</label>
                        <input type="text" name="so_phong" value="<?= sanitize($editData['so_phong']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Tầng *</label>
                        <input type="number" name="tang" value="<?= $editData['tang'] ?>" min="1" required>
                    </div>
                    <div class="form-group">
                        <label>Loại phòng</label>
                        <select name="loai_phong">
                            <?php foreach (['don'=>'Phòng đơn (1 giường)','doi'=>'Phòng đôi (2 giường)','ba'=>'Phòng 3 (3 giường)','bon'=>'Phòng 4 (4 giường)'] as $v=>$l): ?>
                            <option value="<?= $v ?>" <?= $editData['loai_phong']==$v?'selected':'' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Giá thuê/tháng (đ)</label>
                        <input type="number" name="gia_thue" value="<?= $editData['gia_thue'] ?>" min="0">
                    </div>
                    <div class="form-group">
                        <label>Số giường</label>
                        <input type="number" name="so_giuong" value="<?= $editData['so_giuong'] ?>" min="1">
                    </div>
                    <div class="form-group">
                        <label>Diện tích (m²)</label>
                        <input type="number" name="dien_tich" value="<?= $editData['dien_tich'] ?>" min="0" step="0.1">
                    </div>
                    <div class="form-group">
                        <label>Trạng thái</label>
                        <select name="trang_thai">
                            <?php foreach (['trong'=>'🟢 Còn trống','day'=>'🔴 Đã đầy','bao_tri'=>'🟡 Bảo trì'] as $v=>$l): ?>
                            <option value="<?= $v ?>" <?= $editData['trang_thai']==$v?'selected':'' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label>Mô tả</label>
                        <textarea name="mo_ta"><?= sanitize($editData['mo_ta']??'') ?></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="phong.php" class="btn btn-secondary">Hủy</a>
                <button type="submit" class="btn btn-primary">💾 Cập nhật</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function updateGiuong(sel) {
    const map = {don:1, doi:2, ba:3, bon:4};
    document.getElementById('so_giuong_add').value = map[sel.value] || 2;
}
</script>

<?php include 'includes/footer.php'; ?>
