<?php
require_once 'config.php';
requireLogin();
$pageTitle = 'Quản lý Sinh viên';
$db = getDB();

// XỬ LÝ FORM
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $data = [
            'ma_sv'          => trim($_POST['ma_sv'] ?? ''),
            'ho_ten'         => trim($_POST['ho_ten'] ?? ''),
            'ngay_sinh'      => $_POST['ngay_sinh'] ?: null,
            'gioi_tinh'      => $_POST['gioi_tinh'] ?? 'nam',
            'cccd'           => trim($_POST['cccd'] ?? '') ?: null,
            'email'          => trim($_POST['email'] ?? '') ?: null,
            'so_dien_thoai'  => trim($_POST['so_dien_thoai'] ?? '') ?: null,
            'dia_chi'        => trim($_POST['dia_chi'] ?? '') ?: null,
            'truong'         => trim($_POST['truong'] ?? '') ?: null,
            'khoa'           => trim($_POST['khoa'] ?? '') ?: null,
            'nganh'          => trim($_POST['nganh'] ?? '') ?: null,
            'nam_hoc'        => $_POST['nam_hoc'] ? (int)$_POST['nam_hoc'] : null,
            'trang_thai'     => $_POST['trang_thai'] ?? 'dang_o',
            'ghi_chu'        => trim($_POST['ghi_chu'] ?? '') ?: null,
        ];

        if (!$data['ma_sv'] || !$data['ho_ten']) {
            setFlash('error', 'Mã SV và Họ tên không được để trống!');
            redirect('sinh_vien.php');
        }

        if ($action === 'add') {
            try {
                $cols = implode(',', array_keys($data));
                $phs  = implode(',', array_fill(0, count($data), '?'));
                $db->prepare("INSERT INTO sinh_vien ($cols) VALUES ($phs)")->execute(array_values($data));
                setFlash('success', "Thêm sinh viên {$data['ho_ten']} thành công!");
            } catch (PDOException $e) {
                setFlash('error', 'Mã SV hoặc CCCD đã tồn tại!');
            }
        } else {
            $id = (int)$_POST['id'];
            $sets = implode('=?,', array_keys($data)) . '=?';
            $db->prepare("UPDATE sinh_vien SET $sets WHERE id=?")->execute([...array_values($data), $id]);
            setFlash('success', 'Cập nhật sinh viên thành công!');
        }
        redirect('sinh_vien.php');
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $cnt = $db->prepare("SELECT COUNT(*) FROM hop_dong WHERE sinh_vien_id=? AND trang_thai='hieu_luc'");
        $cnt->execute([$id]);
        if ($cnt->fetchColumn() > 0) {
            setFlash('error', 'Không thể xóa sinh viên đang có hợp đồng hiệu lực!');
        } else {
            $db->prepare("DELETE FROM sinh_vien WHERE id=?")->execute([$id]);
            setFlash('success', 'Đã xóa sinh viên!');
        }
        redirect('sinh_vien.php');
    }
}

// FILTER & SEARCH
$where = []; $params = [];
$fTT = $_GET['trang_thai'] ?? '';
$fQ  = trim($_GET['q'] ?? '');
if ($fTT) { $where[] = 'sv.trang_thai=?'; $params[] = $fTT; }
if ($fQ)  { $where[] = '(sv.ho_ten LIKE ? OR sv.ma_sv LIKE ? OR sv.so_dien_thoai LIKE ? OR sv.email LIKE ?)'; $params = array_merge($params, ["%$fQ%","%$fQ%","%$fQ%","%$fQ%"]); }
$whereStr = $where ? 'WHERE '.implode(' AND ',$where) : '';

// Pagination
$perPage = 15;
$page = max(1,(int)($_GET['page']??1));
$totalStmt = $db->prepare("SELECT COUNT(*) FROM sinh_vien sv $whereStr");
$totalStmt->execute($params);
$total = $totalStmt->fetchColumn();
$totalPages = ceil($total/$perPage);
$offset = ($page-1)*$perPage;

$stmt = $db->prepare("
    SELECT sv.*,
        hd.id as hd_id, p.so_phong, tn.ten_toa
    FROM sinh_vien sv
    LEFT JOIN hop_dong hd ON sv.id=hd.sinh_vien_id AND hd.trang_thai='hieu_luc'
    LEFT JOIN phong p ON hd.phong_id=p.id
    LEFT JOIN toa_nha tn ON p.toa_nha_id=tn.id
    $whereStr
    ORDER BY sv.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$stmt->execute($params);
$list = $stmt->fetchAll();

// Edit data
$editData = null;
if (isset($_GET['edit'])) {
    $s = $db->prepare("SELECT * FROM sinh_vien WHERE id=?");
    $s->execute([(int)$_GET['edit']]);
    $editData = $s->fetch();
}

// View chi tiết
$viewData = null;
if (isset($_GET['view'])) {
    $s = $db->prepare("SELECT sv.*, p.so_phong, tn.ten_toa, hd.ma_hop_dong, hd.ngay_bat_dau, hd.ngay_ket_thuc, hd.gia_thue as hd_gia FROM sinh_vien sv LEFT JOIN hop_dong hd ON sv.id=hd.sinh_vien_id AND hd.trang_thai='hieu_luc' LEFT JOIN phong p ON hd.phong_id=p.id LEFT JOIN toa_nha tn ON p.toa_nha_id=tn.id WHERE sv.id=?");
    $s->execute([(int)$_GET['view']]);
    $viewData = $s->fetch();
}

include 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h2>👤 Quản lý Sinh viên</h2>
        <p>Tổng <?= $total ?> sinh viên</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modal-add')">➕ Thêm sinh viên</button>
</div>

<!-- FILTER -->
<div class="card" style="margin-bottom:16px">
    <div class="card-body" style="padding:14px 20px">
        <form method="GET" class="search-bar">
            <div class="search-input">
                <span class="search-icon">🔍</span>
                <input type="text" name="q" placeholder="Tìm theo tên, mã SV, SĐT..." value="<?= sanitize($fQ) ?>">
            </div>
            <select name="trang_thai" style="min-width:150px">
                <option value="">Tất cả trạng thái</option>
                <option value="dang_o" <?= $fTT=='dang_o'?'selected':'' ?>>🏠 Đang ở</option>
                <option value="da_roi" <?= $fTT=='da_roi'?'selected':'' ?>>🚪 Đã rời</option>
                <option value="tam_vang" <?= $fTT=='tam_vang'?'selected':'' ?>>✈️ Tạm vắng</option>
            </select>
            <button type="submit" class="btn btn-primary">🔍 Tìm</button>
            <a href="sinh_vien.php" class="btn btn-secondary">↩ Reset</a>
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
                    <th>Sinh viên</th>
                    <th>Giới tính</th>
                    <th>Liên hệ</th>
                    <th>Trường / Khoa</th>
                    <th>Phòng hiện tại</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($list)): ?>
            <tr><td colspan="8"><div class="empty-state"><div class="empty-icon">👤</div><p>Không tìm thấy sinh viên nào</p></div></td></tr>
            <?php else: ?>
            <?php foreach ($list as $i => $sv): ?>
            <tr>
                <td class="text-muted"><?= ($page-1)*$perPage+$i+1 ?></td>
                <td>
                    <div class="flex-center" style="gap:10px">
                        <div style="width:38px;height:38px;border-radius:50%;background:<?= $sv['gioi_tinh']=='nam'?'#dbeafe':'#fce7f3' ?>;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;color:<?= $sv['gioi_tinh']=='nam'?'#1d4ed8':'#be185d' ?>;flex-shrink:0">
                            <?= strtoupper(mb_substr($sv['ho_ten'],0,1)) ?>
                        </div>
                        <div>
                            <div style="font-weight:700"><?= sanitize($sv['ho_ten']) ?></div>
                            <div style="font-size:11.5px;color:#64748b">
                                <code><?= sanitize($sv['ma_sv']) ?></code>
                                <?php if ($sv['ngay_sinh']): ?> · <?= date('d/m/Y',strtotime($sv['ngay_sinh'])) ?><?php endif; ?>
                            </div>
                        </div>
                    </div>
                </td>
                <td><?= $sv['gioi_tinh']=='nam'?'👦 Nam':($sv['gioi_tinh']=='nu'?'👧 Nữ':'⚧ Khác') ?></td>
                <td>
                    <?php if ($sv['so_dien_thoai']): ?><div style="font-size:13px">📞 <?= sanitize($sv['so_dien_thoai']) ?></div><?php endif; ?>
                    <?php if ($sv['email']): ?><div style="font-size:12px;color:#64748b">✉️ <?= sanitize($sv['email']) ?></div><?php endif; ?>
                </td>
                <td>
                    <?php if ($sv['truong']): ?><div style="font-size:13px;font-weight:500"><?= sanitize($sv['truong']) ?></div><?php endif; ?>
                    <?php if ($sv['nganh']): ?><div style="font-size:12px;color:#64748b"><?= sanitize($sv['nganh']) ?></div><?php endif; ?>
                </td>
                <td>
                    <?php if ($sv['so_phong']): ?>
                    <a href="phong.php?edit=<?= '' ?>" style="text-decoration:none">
                        <code><?= sanitize($sv['ten_toa']) ?> - <?= sanitize($sv['so_phong']) ?></code>
                    </a>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td><?= getStatusBadge($sv['trang_thai'],'sinh_vien') ?></td>
                <td>
                    <div class="btn-group">
                        <a href="sinh_vien.php?view=<?= $sv['id'] ?>" class="btn btn-sm btn-secondary" title="Chi tiết">👁️</a>
                        <a href="sinh_vien.php?edit=<?= $sv['id'] ?>" class="btn btn-sm btn-outline" title="Sửa">✏️</a>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Xóa sinh viên <?= sanitize($sv['ho_ten']) ?>?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $sv['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger" title="Xóa">🗑️</button>
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

<!-- FORM THÊM/SỬA (dùng chung template) -->
<?php
$formData = $editData ?? ['ma_sv'=>'','ho_ten'=>'','ngay_sinh'=>'','gioi_tinh'=>'nam','cccd'=>'','email'=>'','so_dien_thoai'=>'','dia_chi'=>'','truong'=>'','khoa'=>'','nganh'=>'','nam_hoc'=>'','trang_thai'=>'dang_o','ghi_chu'=>''];
$isEdit = !!$editData;
$modalId = $isEdit ? 'modal-edit' : 'modal-add';
?>

<?php foreach (['modal-add' => false, 'modal-edit' => $editData] as $mid => $fd):
if ($mid === 'modal-edit' && !$fd) continue;
$fd = $fd ?: ['ma_sv'=>'','ho_ten'=>'','ngay_sinh'=>'','gioi_tinh'=>'nam','cccd'=>'','email'=>'','so_dien_thoai'=>'','dia_chi'=>'','truong'=>'','khoa'=>'','nganh'=>'','nam_hoc'=>'','trang_thai'=>'dang_o','ghi_chu'=>''];
$isE = $mid === 'modal-edit';
?>
<div class="modal-overlay <?= $isE?'show':'' ?>" id="<?= $mid ?>">
    <div class="modal" style="max-width:750px">
        <div class="modal-header">
            <div class="modal-title"><?= $isE?'✏️ Sửa sinh viên':'➕ Thêm sinh viên mới' ?></div>
            <?php if ($isE): ?><a href="sinh_vien.php" class="modal-close">✕</a>
            <?php else: ?><button class="modal-close" onclick="closeModal('modal-add')">✕</button><?php endif; ?>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="<?= $isE?'edit':'add' ?>">
            <?php if ($isE): ?><input type="hidden" name="id" value="<?= $fd['id'] ?>"><?php endif; ?>
            <div class="modal-body">
                <div class="form-grid form-grid-3">
                    <div class="form-group">
                        <label>Mã SV *</label>
                        <input type="text" name="ma_sv" value="<?= sanitize($fd['ma_sv']) ?>" placeholder="VD: SV001" required <?= $isE?'readonly':'' ?>>
                    </div>
                    <div class="form-group" style="grid-column:span 2">
                        <label>Họ và tên *</label>
                        <input type="text" name="ho_ten" value="<?= sanitize($fd['ho_ten']) ?>" placeholder="Nguyễn Văn A" required>
                    </div>
                    <div class="form-group">
                        <label>Ngày sinh</label>
                        <input type="date" name="ngay_sinh" value="<?= $fd['ngay_sinh'] ?>">
                    </div>
                    <div class="form-group">
                        <label>Giới tính</label>
                        <select name="gioi_tinh">
                            <option value="nam" <?= $fd['gioi_tinh']=='nam'?'selected':'' ?>>👦 Nam</option>
                            <option value="nu" <?= $fd['gioi_tinh']=='nu'?'selected':'' ?>>👧 Nữ</option>
                            <option value="khac" <?= $fd['gioi_tinh']=='khac'?'selected':'' ?>>⚧ Khác</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>CCCD</label>
                        <input type="text" name="cccd" value="<?= sanitize($fd['cccd']??'') ?>" placeholder="012345678901">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?= sanitize($fd['email']??'') ?>" placeholder="email@example.com">
                    </div>
                    <div class="form-group">
                        <label>Số điện thoại</label>
                        <input type="text" name="so_dien_thoai" value="<?= sanitize($fd['so_dien_thoai']??'') ?>" placeholder="0901234567">
                    </div>
                    <div class="form-group">
                        <label>Trường</label>
                        <input type="text" name="truong" value="<?= sanitize($fd['truong']??'') ?>" placeholder="ĐH Bách Khoa">
                    </div>
                    <div class="form-group">
                        <label>Khoa</label>
                        <input type="text" name="khoa" value="<?= sanitize($fd['khoa']??'') ?>" placeholder="CNTT">
                    </div>
                    <div class="form-group">
                        <label>Ngành học</label>
                        <input type="text" name="nganh" value="<?= sanitize($fd['nganh']??'') ?>" placeholder="Kỹ thuật phần mềm">
                    </div>
                    <div class="form-group">
                        <label>Năm học</label>
                        <select name="nam_hoc">
                            <option value="">— Chọn —</option>
                            <?php for($y=1;$y<=6;$y++): ?>
                            <option value="<?= $y ?>" <?= $fd['nam_hoc']==$y?'selected':'' ?>>Năm <?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Trạng thái</label>
                        <select name="trang_thai">
                            <option value="dang_o" <?= $fd['trang_thai']=='dang_o'?'selected':'' ?>>🏠 Đang ở</option>
                            <option value="da_roi" <?= $fd['trang_thai']=='da_roi'?'selected':'' ?>>🚪 Đã rời</option>
                            <option value="tam_vang" <?= $fd['trang_thai']=='tam_vang'?'selected':'' ?>>✈️ Tạm vắng</option>
                        </select>
                    </div>
                    <div class="form-group full">
                        <label>Địa chỉ</label>
                        <input type="text" name="dia_chi" value="<?= sanitize($fd['dia_chi']??'') ?>" placeholder="Số nhà, đường, quận/huyện, tỉnh/thành...">
                    </div>
                    <div class="form-group full">
                        <label>Ghi chú</label>
                        <textarea name="ghi_chu"><?= sanitize($fd['ghi_chu']??'') ?></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <?php if ($isE): ?>
                <a href="sinh_vien.php" class="btn btn-secondary">Hủy</a>
                <?php else: ?>
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-add')">Hủy</button>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary">💾 <?= $isE?'Cập nhật':'Thêm sinh viên' ?></button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<!-- MODAL CHI TIẾT -->
<?php if ($viewData): ?>
<div class="modal-overlay show" id="modal-view">
    <div class="modal" style="max-width:620px">
        <div class="modal-header">
            <div class="modal-title">👁️ Chi tiết sinh viên</div>
            <a href="sinh_vien.php" class="modal-close">✕</a>
        </div>
        <div class="modal-body">
            <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px;padding:16px;background:var(--surface2);border-radius:10px">
                <div style="width:60px;height:60px;border-radius:50%;background:<?= $viewData['gioi_tinh']=='nam'?'#dbeafe':'#fce7f3' ?>;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:800;color:<?= $viewData['gioi_tinh']=='nam'?'#1d4ed8':'#be185d' ?>">
                    <?= strtoupper(mb_substr($viewData['ho_ten'],0,1)) ?>
                </div>
                <div>
                    <div style="font-size:18px;font-weight:800"><?= sanitize($viewData['ho_ten']) ?></div>
                    <div style="font-size:13px;color:#64748b"><code><?= sanitize($viewData['ma_sv']) ?></code> · <?= getStatusBadge($viewData['trang_thai'],'sinh_vien') ?></div>
                </div>
            </div>
            <?php
            $rows = [
                ['📅 Ngày sinh', formatDate($viewData['ngay_sinh'])],
                ['⚧ Giới tính', $viewData['gioi_tinh']=='nam'?'Nam':($viewData['gioi_tinh']=='nu'?'Nữ':'Khác')],
                ['🪪 CCCD', $viewData['cccd']??'—'],
                ['📞 Điện thoại', $viewData['so_dien_thoai']??'—'],
                ['✉️ Email', $viewData['email']??'—'],
                ['📍 Địa chỉ', $viewData['dia_chi']??'—'],
                ['🏫 Trường', $viewData['truong']??'—'],
                ['🏢 Khoa / Ngành', ($viewData['khoa']??'—').' / '.($viewData['nganh']??'—')],
                ['📚 Năm học', $viewData['nam_hoc']?'Năm '.$viewData['nam_hoc']:'—'],
                ['🚪 Phòng', $viewData['so_phong']?sanitize($viewData['ten_toa']).' - '.sanitize($viewData['so_phong']):'Chưa có phòng'],
                ['📋 Hợp đồng', $viewData['ma_hop_dong']??'—'],
            ];
            ?>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
            <?php foreach ($rows as [$label,$val]): ?>
            <div style="padding:10px 14px;background:var(--surface2);border-radius:8px">
                <div style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px"><?= $label ?></div>
                <div style="font-size:13.5px;font-weight:500"><?= sanitize((string)$val) ?></div>
            </div>
            <?php endforeach; ?>
            </div>
            <?php if ($viewData['ghi_chu']): ?>
            <div style="margin-top:12px;padding:12px 14px;background:#fef9c3;border-radius:8px;font-size:13px">
                <b>📝 Ghi chú:</b> <?= sanitize($viewData['ghi_chu']) ?>
            </div>
            <?php endif; ?>
        </div>
        <div class="modal-footer">
            <a href="sinh_vien.php" class="btn btn-secondary">Đóng</a>
            <a href="sinh_vien.php?edit=<?= $viewData['id'] ?>" class="btn btn-primary">✏️ Chỉnh sửa</a>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
