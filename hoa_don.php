<?php
require_once 'config.php';
requireLogin();
$pageTitle = 'Quản lý Hóa đơn';
$db = getDB();

$allowedTrangThai = ['chua_thanh_toan','da_thanh_toan','qua_han'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $phong_id   = (int)$_POST['phong_id'];
        $thang      = (int)$_POST['thang'];
        $nam        = (int)$_POST['nam'];
        $d_cu       = (float)$_POST['chi_so_dien_cu'];
        $d_moi      = (float)$_POST['chi_so_dien_moi'];
        $gd_dien    = (int)$_POST['don_gia_dien'];
        $n_cu       = (float)$_POST['chi_so_nuoc_cu'];
        $n_moi      = (float)$_POST['chi_so_nuoc_moi'];
        $gd_nuoc    = (int)$_POST['don_gia_nuoc'];
        $dv         = (int)$_POST['tien_dich_vu'];
        $tp         = (int)$_POST['tien_phong'];
        $tt         = $_POST['trang_thai'] ?? 'chua_thanh_toan';
        $ngay_tt    = $_POST['ngay_thanh_toan'] ?: null;
        $ghichu     = trim($_POST['ghi_chu'] ?? '');

        $id = (int)($_POST['id'] ?? 0);
        $tt = in_array($tt, $allowedTrangThai, true) ? $tt : 'chua_thanh_toan';
        if ($tt === 'da_thanh_toan' && empty($ngay_tt)) {
            $ngay_tt = date('Y-m-d');
        }
        if ($tt !== 'da_thanh_toan') {
            $ngay_tt = null;
        }

        $errors = [];
        if ($action === 'edit' && $id <= 0) { $errors[] = 'Thiếu ID hóa đơn để cập nhật.'; }
        if ($phong_id <= 0) { $errors[] = 'Vui lòng chọn phòng hợp lệ.'; }
        if ($thang < 1 || $thang > 12) { $errors[] = 'Tháng không hợp lệ.'; }
        if ($nam < 2000 || $nam > 2100) { $errors[] = 'Năm không hợp lệ.'; }
        if ($d_moi < $d_cu) { $errors[] = 'Chỉ số điện mới phải >= chỉ số cũ.'; }
        if ($n_moi < $n_cu) { $errors[] = 'Chỉ số nước mới phải >= chỉ số cũ.'; }
        if ($gd_dien < 0 || $gd_nuoc < 0 || $dv < 0 || $tp < 0) { $errors[] = 'Số tiền không được âm.'; }

        if ($phong_id > 0) {
            $exists = $db->prepare("SELECT COUNT(*) FROM phong WHERE id=?");
            $exists->execute([$phong_id]);
            if ((int)$exists->fetchColumn() === 0) {
                $errors[] = 'Phòng không tồn tại.';
            }
        }

        if ($errors) {
            setFlash('error', implode(' ', $errors));
            $back = ($action === 'edit' && $id > 0) ? ('hoa_don.php?edit=' . $id) : 'hoa_don.php';
            redirect($back);
        }

        $tien_dien  = ($d_moi - $d_cu) * $gd_dien;
        $tien_nuoc  = ($n_moi - $n_cu) * $gd_nuoc;
        $tong       = $tien_dien + $tien_nuoc + $dv + $tp;

        // Lấy số phòng để tạo mã
        $pInfo = $db->prepare("SELECT p.so_phong FROM phong p WHERE p.id=?");
        $pInfo->execute([$phong_id]);
        $pRow = $pInfo->fetch();
        $ma = generateMaHoaDon($pRow['so_phong']??'P'.$phong_id, $thang, $nam);

        if ($action === 'add') {
            try {
                $db->prepare("INSERT INTO hoa_don (ma_hoa_don,phong_id,thang,nam,chi_so_dien_cu,chi_so_dien_moi,don_gia_dien,chi_so_nuoc_cu,chi_so_nuoc_moi,don_gia_nuoc,tien_dich_vu,tien_phong,tong_tien,trang_thai,ngay_thanh_toan,ghi_chu) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
                   ->execute([$ma,$phong_id,$thang,$nam,$d_cu,$d_moi,$gd_dien,$n_cu,$n_moi,$gd_nuoc,$dv,$tp,$tong,$tt,$ngay_tt,$ghichu]);
                setFlash('success', "Tạo hóa đơn $ma thành công! Tổng tiền: ".formatMoney($tong));
            } catch (PDOException $e) {
                setFlash('error', 'Hóa đơn tháng này cho phòng đã tồn tại!');
            }
        } else {
            try {
                $db->prepare("UPDATE hoa_don SET ma_hoa_don=?,phong_id=?,thang=?,nam=?,chi_so_dien_cu=?,chi_so_dien_moi=?,don_gia_dien=?,chi_so_nuoc_cu=?,chi_so_nuoc_moi=?,don_gia_nuoc=?,tien_dich_vu=?,tien_phong=?,tong_tien=?,trang_thai=?,ngay_thanh_toan=?,ghi_chu=? WHERE id=?")
                   ->execute([$ma,$phong_id,$thang,$nam,$d_cu,$d_moi,$gd_dien,$n_cu,$n_moi,$gd_nuoc,$dv,$tp,$tong,$tt,$ngay_tt,$ghichu,$id]);
                setFlash('success', 'Cập nhật hóa đơn thành công!');
            } catch (PDOException $e) {
                setFlash('error', 'Không thể cập nhật do trùng mã hóa đơn.');
            }
        }
        redirect('hoa_don.php');
    }

    if ($action === 'thanh_toan') {
        $id = (int)$_POST['id'];
        $db->prepare("UPDATE hoa_don SET trang_thai='da_thanh_toan', ngay_thanh_toan=CURDATE() WHERE id=?")->execute([$id]);
        setFlash('success', 'Đã xác nhận thanh toán!');
        redirect('hoa_don.php');
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $db->prepare("DELETE FROM hoa_don WHERE id=?")->execute([$id]);
        setFlash('success', 'Đã xóa hóa đơn!');
        redirect('hoa_don.php');
    }
}

// Filter
$where = []; $params = [];
$fTT   = $_GET['filter'] ?? ($_GET['trang_thai'] ?? '');
$fQ    = trim($_GET['q'] ?? '');
$fThang= $_GET['thang'] ?? '';
$fNam  = $_GET['nam'] ?? date('Y');

if ($fTT)    { $where[] = 'hd.trang_thai=?'; $params[] = $fTT; }
if ($fQ)     { $where[] = '(p.so_phong LIKE ? OR tn.ten_toa LIKE ? OR hd.ma_hoa_don LIKE ?)'; $params = array_merge($params,["%$fQ%","%$fQ%","%$fQ%"]); }
if ($fThang) { $where[] = 'hd.thang=?'; $params[] = $fThang; }
if ($fNam)   { $where[] = 'hd.nam=?';   $params[] = $fNam; }
$whereStr = $where ? 'WHERE '.implode(' AND ',$where) : '';

$perPage = 15;
$page = max(1,(int)($_GET['page']??1));
$total = $db->prepare("SELECT COUNT(*) FROM hoa_don hd JOIN phong p ON hd.phong_id=p.id JOIN toa_nha tn ON p.toa_nha_id=tn.id $whereStr");
$total->execute($params); $total = $total->fetchColumn();
$totalPages = ceil($total/$perPage);
$offset = ($page-1)*$perPage;

$list = $db->prepare("
    SELECT hd.*, p.so_phong, tn.ten_toa,
        (hd.chi_so_dien_moi - hd.chi_so_dien_cu) * hd.don_gia_dien as tien_dien,
        (hd.chi_so_nuoc_moi - hd.chi_so_nuoc_cu) * hd.don_gia_nuoc as tien_nuoc
    FROM hoa_don hd
    JOIN phong p ON hd.phong_id=p.id
    JOIN toa_nha tn ON p.toa_nha_id=tn.id
    $whereStr
    ORDER BY hd.nam DESC, hd.thang DESC, tn.ten_toa, p.so_phong
    LIMIT $perPage OFFSET $offset
");
$list->execute($params);
$list = $list->fetchAll();

// Tổng hợp
$tongStats = $db->prepare("SELECT COALESCE(SUM(tong_tien),0) as tong, COALESCE(SUM(CASE WHEN hd.trang_thai='da_thanh_toan' THEN tong_tien ELSE 0 END),0) as da_thu, COALESCE(SUM(CASE WHEN hd.trang_thai!='da_thanh_toan' THEN tong_tien ELSE 0 END),0) as chua_thu FROM hoa_don hd JOIN phong p ON hd.phong_id=p.id JOIN toa_nha tn ON p.toa_nha_id=tn.id $whereStr");
$tongStats->execute($params);
$tongStats = $tongStats->fetch();

$phongList = $db->query("SELECT p.*,tn.ten_toa FROM phong p JOIN toa_nha tn ON p.toa_nha_id=tn.id WHERE p.trang_thai='day' ORDER BY tn.ten_toa,p.so_phong")->fetchAll();

$editData = null;
if (isset($_GET['edit'])) {
    $s = $db->prepare("SELECT * FROM hoa_don WHERE id=?");
    $s->execute([(int)$_GET['edit']]);
    $editData = $s->fetch();
}

include 'includes/header.php';
?>

<div class="page-header">
    <div>
        <h2>💰 Quản lý Hóa đơn</h2>
        <p>Điện, nước và các khoản thu hàng tháng</p>
    </div>
    <button class="btn btn-primary" onclick="openModal('modal-add')">➕ Tạo hóa đơn</button>
</div>

<!-- TỔNG HỢP -->
<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:16px">
    <div class="stat-card green">
        <div class="stat-icon">💵</div>
        <div class="stat-value" style="font-size:20px"><?= formatMoney($tongStats['da_thu']) ?></div>
        <div class="stat-label">Đã thu</div>
    </div>
    <div class="stat-card red">
        <div class="stat-icon">⏳</div>
        <div class="stat-value" style="font-size:20px"><?= formatMoney($tongStats['chua_thu']) ?></div>
        <div class="stat-label">Chưa thu</div>
    </div>
    <div class="stat-card blue">
        <div class="stat-icon">📊</div>
        <div class="stat-value" style="font-size:20px"><?= formatMoney($tongStats['tong']) ?></div>
        <div class="stat-label">Tổng cộng</div>
    </div>
</div>

<!-- FILTER -->
<div class="card" style="margin-bottom:16px">
    <div class="card-body" style="padding:14px 20px">
        <form method="GET" class="search-bar">
            <div class="search-input">
                <span class="search-icon">🔍</span>
                <input type="text" name="q" placeholder="Tìm phòng, mã HĐ..." value="<?= sanitize($fQ) ?>">
            </div>
            <select name="trang_thai" style="min-width:160px">
                <option value="">Tất cả TT</option>
                <option value="chua_thanh_toan" <?= $fTT=='chua_thanh_toan'?'selected':'' ?>>⏳ Chưa thanh toán</option>
                <option value="da_thanh_toan" <?= $fTT=='da_thanh_toan'?'selected':'' ?>>✅ Đã thanh toán</option>
                <option value="qua_han" <?= $fTT=='qua_han'?'selected':'' ?>>🚨 Quá hạn</option>
            </select>
            <select name="thang" style="min-width:110px">
                <option value="">Tất cả tháng</option>
                <?php for($m=1;$m<=12;$m++): ?>
                <option value="<?= $m ?>" <?= $fThang==$m?'selected':'' ?>>Tháng <?= $m ?></option>
                <?php endfor; ?>
            </select>
            <select name="nam" style="min-width:100px">
                <?php for($y=date('Y');$y>=date('Y')-3;$y--): ?>
                <option value="<?= $y ?>" <?= $fNam==$y?'selected':'' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <button type="submit" class="btn btn-primary">🔍 Lọc</button>
            <a href="hoa_don.php" class="btn btn-secondary">↩ Reset</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Mã hóa đơn</th>
                    <th>Phòng</th>
                    <th>Tháng/Năm</th>
                    <th>Tiền điện</th>
                    <th>Tiền nước</th>
                    <th>Dịch vụ</th>
                    <th>Tiền phòng</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($list)): ?>
            <tr><td colspan="11"><div class="empty-state"><div class="empty-icon">💰</div><p>Không có hóa đơn nào</p></div></td></tr>
            <?php else: ?>
            <?php foreach ($list as $i => $hd): ?>
            <tr>
                <td class="text-muted"><?= ($page-1)*$perPage+$i+1 ?></td>
                <td><code style="font-size:11.5px"><?= sanitize($hd['ma_hoa_don']) ?></code></td>
                <td><code><?= sanitize($hd['ten_toa']) ?> - <?= sanitize($hd['so_phong']) ?></code></td>
                <td style="font-weight:600">T<?= $hd['thang'] ?>/<?= $hd['nam'] ?></td>
                <td>
                    <div><?= formatMoney($hd['tien_dien']) ?></div>
                    <div style="font-size:11px;color:#94a3b8"><?= $hd['chi_so_dien_moi']-$hd['chi_so_dien_cu'] ?> kWh</div>
                </td>
                <td>
                    <div><?= formatMoney($hd['tien_nuoc']) ?></div>
                    <div style="font-size:11px;color:#94a3b8"><?= $hd['chi_so_nuoc_moi']-$hd['chi_so_nuoc_cu'] ?> m³</div>
                </td>
                <td><?= formatMoney($hd['tien_dich_vu']) ?></td>
                <td><?= formatMoney($hd['tien_phong']) ?></td>
                <td style="font-weight:800;color:#1a56db;font-size:14px"><?= formatMoney($hd['tong_tien']) ?></td>
                <td>
                    <?= getStatusBadge($hd['trang_thai'],'hoa_don') ?>
                    <?php if ($hd['ngay_thanh_toan']): ?>
                    <div style="font-size:10.5px;color:#64748b;margin-top:2px"><?= formatDate($hd['ngay_thanh_toan']) ?></div>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="btn-group">
                        <?php if ($hd['trang_thai'] !== 'da_thanh_toan'): ?>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Xác nhận thanh toán?')">
                            <input type="hidden" name="action" value="thanh_toan">
                            <input type="hidden" name="id" value="<?= $hd['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-success" title="Đánh dấu đã TT">✅</button>
                        </form>
                        <?php endif; ?>
                        <a href="hoa_don.php?edit=<?= $hd['id'] ?>" class="btn btn-sm btn-outline">✏️</a>
                        <form method="POST" style="display:inline" onsubmit="return confirm('Xóa hóa đơn này?')">
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
$fd = $fd ?: ['phong_id'=>'','thang'=>date('m'),'nam'=>date('Y'),'chi_so_dien_cu'=>0,'chi_so_dien_moi'=>0,'don_gia_dien'=>3500,'chi_so_nuoc_cu'=>0,'chi_so_nuoc_moi'=>0,'don_gia_nuoc'=>15000,'tien_dich_vu'=>50000,'tien_phong'=>0,'trang_thai'=>'chua_thanh_toan','ngay_thanh_toan'=>'','ghi_chu'=>''];
$isE = $mid === 'modal-edit';
?>
<div class="modal-overlay <?= $isE?'show':'' ?>" id="<?= $mid ?>">
    <div class="modal" style="max-width:700px">
        <div class="modal-header">
            <div class="modal-title"><?= $isE?'✏️ Sửa hóa đơn':'➕ Tạo hóa đơn mới' ?></div>
            <?php if ($isE): ?><a href="hoa_don.php" class="modal-close">✕</a>
            <?php else: ?><button class="modal-close" onclick="closeModal('modal-add')">✕</button><?php endif; ?>
        </div>
        <form method="POST" id="form-hoadon-<?= $mid ?>">
            <input type="hidden" name="action" value="<?= $isE?'edit':'add' ?>">
            <?php if ($isE): ?><input type="hidden" name="id" value="<?= $fd['id'] ?>"><?php endif; ?>
            <div class="modal-body">
                <div class="form-grid form-grid-3">
                    <div class="form-group full">
                        <label>Phòng *</label>
                        <select name="phong_id" required onchange="loadGiaPhong(this, '<?= $mid ?>')">
                            <option value="">— Chọn phòng —</option>
                            <?php foreach ($phongList as $p): ?>
                            <option value="<?= $p['id'] ?>" data-gia="<?= $p['gia_thue'] ?>" <?= $fd['phong_id']==$p['id']?'selected':'' ?>><?= sanitize($p['ten_toa']) ?> - <?= sanitize($p['so_phong']) ?> (<?= formatMoney($p['gia_thue']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tháng *</label>
                        <select name="thang" required>
                            <?php for($m=1;$m<=12;$m++): ?>
                            <option value="<?= $m ?>" <?= $fd['thang']==$m?'selected':'' ?>>Tháng <?= $m ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Năm *</label>
                        <input type="number" name="nam" value="<?= $fd['nam'] ?>" min="2020" max="2030" required>
                    </div>
                    <div class="form-group">
                        <label>Trạng thái</label>
                        <select name="trang_thai">
                            <option value="chua_thanh_toan" <?= $fd['trang_thai']=='chua_thanh_toan'?'selected':'' ?>>⏳ Chưa TT</option>
                            <option value="da_thanh_toan" <?= $fd['trang_thai']=='da_thanh_toan'?'selected':'' ?>>✅ Đã TT</option>
                            <option value="qua_han" <?= $fd['trang_thai']=='qua_han'?'selected':'' ?>>🚨 Quá hạn</option>
                        </select>
                    </div>
                </div>
                <div class="divider"></div>
                <div style="font-weight:700;margin-bottom:12px;color:#1a56db">⚡ Điện</div>
                <div class="form-grid form-grid-3">
                    <div class="form-group">
                        <label>Chỉ số cũ (kWh)</label>
                        <input type="number" name="chi_so_dien_cu" id="dien_cu_<?= $mid ?>" value="<?= $fd['chi_so_dien_cu'] ?>" min="0" step="0.01" oninput="tinhToan('<?= $mid ?>')">
                    </div>
                    <div class="form-group">
                        <label>Chỉ số mới (kWh)</label>
                        <input type="number" name="chi_so_dien_moi" id="dien_moi_<?= $mid ?>" value="<?= $fd['chi_so_dien_moi'] ?>" min="0" step="0.01" oninput="tinhToan('<?= $mid ?>')">
                    </div>
                    <div class="form-group">
                        <label>Đơn giá (đ/kWh)</label>
                        <input type="number" name="don_gia_dien" id="gd_dien_<?= $mid ?>" value="<?= $fd['don_gia_dien'] ?>" min="0" oninput="tinhToan('<?= $mid ?>')">
                    </div>
                </div>
                <div style="font-weight:700;margin-bottom:12px;margin-top:12px;color:#0891b2">💧 Nước</div>
                <div class="form-grid form-grid-3">
                    <div class="form-group">
                        <label>Chỉ số cũ (m³)</label>
                        <input type="number" name="chi_so_nuoc_cu" id="nuoc_cu_<?= $mid ?>" value="<?= $fd['chi_so_nuoc_cu'] ?>" min="0" step="0.01" oninput="tinhToan('<?= $mid ?>')">
                    </div>
                    <div class="form-group">
                        <label>Chỉ số mới (m³)</label>
                        <input type="number" name="chi_so_nuoc_moi" id="nuoc_moi_<?= $mid ?>" value="<?= $fd['chi_so_nuoc_moi'] ?>" min="0" step="0.01" oninput="tinhToan('<?= $mid ?>')">
                    </div>
                    <div class="form-group">
                        <label>Đơn giá (đ/m³)</label>
                        <input type="number" name="don_gia_nuoc" id="gd_nuoc_<?= $mid ?>" value="<?= $fd['don_gia_nuoc'] ?>" min="0" oninput="tinhToan('<?= $mid ?>')">
                    </div>
                </div>
                <div class="divider"></div>
                <div class="form-grid form-grid-3">
                    <div class="form-group">
                        <label>Phí dịch vụ (đ)</label>
                        <input type="number" name="tien_dich_vu" id="dv_<?= $mid ?>" value="<?= $fd['tien_dich_vu'] ?>" min="0" oninput="tinhToan('<?= $mid ?>')">
                    </div>
                    <div class="form-group">
                        <label>Tiền phòng (đ)</label>
                        <input type="number" name="tien_phong" id="tp_<?= $mid ?>" value="<?= $fd['tien_phong'] ?>" min="0" oninput="tinhToan('<?= $mid ?>')">
                    </div>
                    <div class="form-group">
                        <label>Ngày thanh toán</label>
                        <input type="date" name="ngay_thanh_toan" value="<?= $fd['ngay_thanh_toan'] ?>">
                    </div>
                </div>
                <div style="background:linear-gradient(135deg,#e8f0fe,#dbeafe);border-radius:10px;padding:14px 18px;margin-top:12px;display:flex;align-items:center;justify-content:space-between">
                    <span style="font-size:14px;font-weight:700;color:#1d4ed8">💰 TỔNG TIỀN</span>
                    <span style="font-size:20px;font-weight:800;color:#1a56db" id="tong_<?= $mid ?>">0 đ</span>
                </div>
                <div class="form-group" style="margin-top:12px">
                    <label>Ghi chú</label>
                    <textarea name="ghi_chu"><?= sanitize($fd['ghi_chu']??'') ?></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <?php if ($isE): ?>
                <a href="hoa_don.php" class="btn btn-secondary">Hủy</a>
                <?php else: ?>
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-add')">Hủy</button>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary">💾 <?= $isE?'Cập nhật':'Tạo hóa đơn' ?></button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<script>
function tinhToan(mid) {
    const v = id => parseFloat(document.getElementById(id+'_'+mid)?.value) || 0;
    const dien  = (v('dien_moi') - v('dien_cu')) * v('gd_dien');
    const nuoc  = (v('nuoc_moi') - v('nuoc_cu')) * v('gd_nuoc');
    const total = dien + nuoc + v('dv') + v('tp');
    const el = document.getElementById('tong_'+mid);
    if (el) el.textContent = new Intl.NumberFormat('vi-VN').format(Math.max(0,total)) + ' đ';
}
function loadGiaPhong(sel, mid) {
    const gia = sel.options[sel.selectedIndex]?.dataset.gia || 0;
    const tp = document.getElementById('tp_'+mid);
    if (tp) tp.value = gia;
    tinhToan(mid);
}
// Init tính toán
['modal-add','modal-edit'].forEach(mid => tinhToan(mid));
</script>

<?php include 'includes/footer.php'; ?>


