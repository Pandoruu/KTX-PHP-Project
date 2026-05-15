<?php
require_once 'config.php';
requireLogin();
$pageTitle = 'Quản lý Tòa nhà';
$db = getDB();

// XỬ LÝ FORM
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $ten  = trim($_POST['ten_toa'] ?? '');
        $mo   = trim($_POST['mo_ta'] ?? '');
        $tang = (int)($_POST['so_tang'] ?? 1);
        $tt   = $_POST['trang_thai'] ?? 'hoat_dong';

        if (!$ten) { setFlash('error', 'Tên tòa nhà không được để trống!'); }
        else {
            if ($action === 'add') {
                $stmt = $db->prepare("INSERT INTO toa_nha (ten_toa,mo_ta,so_tang,trang_thai) VALUES (?,?,?,?)");
                $stmt->execute([$ten,$mo,$tang,$tt]);
                setFlash('success', 'Thêm tòa nhà thành công!');
            } else {
                $id = (int)$_POST['id'];
                $stmt = $db->prepare("UPDATE toa_nha SET ten_toa=?,mo_ta=?,so_tang=?,trang_thai=? WHERE id=?");
                $stmt->execute([$ten,$mo,$tang,$tt,$id]);
                setFlash('success', 'Cập nhật tòa nhà thành công!');
            }
        }
        redirect('toa_nha.php');
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $cnt = $db->prepare("SELECT COUNT(*) FROM phong WHERE toa_nha_id=?");
        $cnt->execute([$id]);
        if ($cnt->fetchColumn() > 0) {
            setFlash('error', 'Không thể xóa tòa nhà đang có phòng!');
        } else {
            $db->prepare("DELETE FROM toa_nha WHERE id=?")->execute([$id]);
            setFlash('success', 'Đã xóa tòa nhà!');
        }
        redirect('toa_nha.php');
    }
}

// LẤY DỮ LIỆU
$list = $db->query("
    SELECT tn.*,
        COUNT(p.id) as so_phong,
        SUM(p.trang_thai='trong') as phong_trong,
        SUM(p.trang_thai='day') as phong_day,
        SUM(p.trang_thai='bao_tri') as phong_baotr
    FROM toa_nha tn
    LEFT JOIN phong p ON tn.id = p.toa_nha_id
    GROUP BY tn.id
    ORDER BY tn.ten_toa
")->fetchAll();

// Edit
$editData = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM toa_nha WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $editData = $stmt->fetch();
}

include 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h2>🏗️ Quản lý Tòa nhà</h2>
        <p>Quản lý thông tin các tòa nhà trong khu ký túc xá</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modal-add')">➕ Thêm tòa nhà</button>
</div>

<!-- DANH SÁCH -->
<div class="card">
    <div class="card-header">
        <div class="card-title">📋 Danh sách tòa nhà (<?= count($list) ?>)</div>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tên tòa</th>
                    <th>Số tầng</th>
                    <th>Tổng phòng</th>
                    <th>Còn trống</th>
                    <th>Đã đầy</th>
                    <th>Bảo trì</th>
                    <th>Trạng thái</th>
                    <th>Mô tả</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($list)): ?>
            <tr><td colspan="10" class="text-center text-muted" style="padding:30px">Chưa có tòa nhà nào</td></tr>
            <?php else: ?>
            <?php foreach ($list as $i => $tn): ?>
            <tr>
                <td class="text-muted"><?= $i+1 ?></td>
                <td>
                    <div class="flex-center" style="gap:8px">
                        <div style="width:36px;height:36px;background:#e8f0fe;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:16px">🏗️</div>
                        <span style="font-weight:700"><?= sanitize($tn['ten_toa']) ?></span>
                    </div>
                </td>
                <td><?= $tn['so_tang'] ?> tầng</td>
                <td><span style="font-weight:700"><?= $tn['so_phong'] ?></span></td>
                <td><span class="badge badge-success"><?= $tn['phong_trong'] ?></span></td>
                <td><span class="badge badge-danger"><?= $tn['phong_day'] ?></span></td>
                <td><span class="badge badge-warning"><?= $tn['phong_baotr'] ?></span></td>
                <td><?= getStatusBadge($tn['trang_thai'], 'toa_nha') ?></td>
                <td style="max-width:200px;color:#64748b;font-size:12.5px"><?= sanitize(mb_substr($tn['mo_ta']??'',0,60)) ?><?= mb_strlen($tn['mo_ta']??'')>60?'...':'' ?></td>
                <td>
                    <div class="btn-group">
                        <a href="phong.php?toa=<?= $tn['id'] ?>" class="btn btn-sm btn-secondary">🚪 Phòng</a>
                        <a href="toa_nha.php?edit=<?= $tn['id'] ?>" class="btn btn-sm btn-outline">✏️</a>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Xóa tòa nhà này?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $tn['id'] ?>">
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
</div>

<!-- MODAL THÊM -->
<div class="modal-overlay" id="modal-add">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">➕ Thêm tòa nhà mới</div>
            <button class="modal-close" onclick="closeModal('modal-add')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add">
            <div class="modal-body">
                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <label>Tên tòa nhà *</label>
                        <input type="text" name="ten_toa" placeholder="VD: Tòa A" required>
                    </div>
                    <div class="form-group">
                        <label>Số tầng *</label>
                        <input type="number" name="so_tang" value="5" min="1" max="30" required>
                    </div>
                    <div class="form-group">
                        <label>Trạng thái</label>
                        <select name="trang_thai">
                            <option value="hoat_dong">✅ Hoạt động</option>
                            <option value="bao_tri">🔧 Bảo trì</option>
                            <option value="dong_cua">🚪 Đóng cửa</option>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label>Mô tả</label>
                        <textarea name="mo_ta" placeholder="Mô tả về tòa nhà..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-add')">Hủy</button>
                <button type="submit" class="btn btn-primary">💾 Lưu</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL SỬA -->
<?php if ($editData): ?>
<script>document.addEventListener('DOMContentLoaded', () => openModal('modal-edit'));</script>
<div class="modal-overlay show" id="modal-edit">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">✏️ Sửa tòa nhà</div>
            <a href="toa_nha.php" class="modal-close">✕</a>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="<?= $editData['id'] ?>">
            <div class="modal-body">
                <div class="form-grid form-grid-2">
                    <div class="form-group">
                        <label>Tên tòa nhà *</label>
                        <input type="text" name="ten_toa" value="<?= sanitize($editData['ten_toa']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Số tầng *</label>
                        <input type="number" name="so_tang" value="<?= $editData['so_tang'] ?>" min="1" max="30" required>
                    </div>
                    <div class="form-group">
                        <label>Trạng thái</label>
                        <select name="trang_thai">
                            <?php foreach (['hoat_dong'=>'✅ Hoạt động','bao_tri'=>'🔧 Bảo trì','dong_cua'=>'🚪 Đóng cửa'] as $v=>$l): ?>
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
                <a href="toa_nha.php" class="btn btn-secondary">Hủy</a>
                <button type="submit" class="btn btn-primary">💾 Cập nhật</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
