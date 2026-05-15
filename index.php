<?php
require_once 'config.php';
requireLogin();
$pageTitle = 'Dashboard';

$db = getDB();

// Thống kê tổng quan
$stats = [];
$stats['toa_nha']     = $db->query("SELECT COUNT(*) FROM toa_nha WHERE trang_thai='hoat_dong'")->fetchColumn();
$stats['phong_total'] = $db->query("SELECT COUNT(*) FROM phong")->fetchColumn();
$stats['phong_trong'] = $db->query("SELECT COUNT(*) FROM phong WHERE trang_thai='trong'")->fetchColumn();
$stats['phong_day']   = $db->query("SELECT COUNT(*) FROM phong WHERE trang_thai='day'")->fetchColumn();
$stats['sv_total']    = $db->query("SELECT COUNT(*) FROM sinh_vien WHERE trang_thai='dang_o'")->fetchColumn();
$stats['hd_hieuluc']  = $db->query("SELECT COUNT(*) FROM hop_dong WHERE trang_thai='hieu_luc'")->fetchColumn();
$stats['hd_chuatt']   = $db->query("SELECT COUNT(*) FROM hoa_don WHERE trang_thai='chua_thanh_toan'")->fetchColumn();
$stats['hd_quahan']   = $db->query("SELECT COUNT(*) FROM hoa_don WHERE trang_thai='qua_han'")->fetchColumn();
$stats['vp_choxl']    = $db->query("SELECT COUNT(*) FROM vi_pham WHERE trang_thai='cho_xu_ly'")->fetchColumn();
$stats['bt_choxl']    = $db->query("SELECT COUNT(*) FROM bao_tri WHERE trang_thai IN ('cho_xu_ly','dang_xu_ly')")->fetchColumn();

// Doanh thu tháng này
$thang = date('m'); $nam = date('Y');
$dt = $db->prepare("SELECT COALESCE(SUM(tong_tien),0) FROM hoa_don WHERE thang=? AND nam=? AND trang_thai='da_thanh_toan'");
$dt->execute([$thang, $nam]);
$stats['doanh_thu'] = $dt->fetchColumn();

// Hóa đơn chưa TT tháng này
$dt2 = $db->prepare("SELECT COALESCE(SUM(tong_tien),0) FROM hoa_don WHERE thang=? AND nam=? AND trang_thai IN ('chua_thanh_toan','qua_han')");
$dt2->execute([$thang, $nam]);
$stats['chua_thu'] = $dt2->fetchColumn();

// 5 hợp đồng sắp hết hạn (trong 30 ngày)
$sap_het = $db->query("
    SELECT hd.*, sv.ho_ten, sv.ma_sv, p.so_phong, tn.ten_toa
    FROM hop_dong hd
    JOIN sinh_vien sv ON hd.sinh_vien_id = sv.id
    JOIN phong p ON hd.phong_id = p.id
    JOIN toa_nha tn ON p.toa_nha_id = tn.id
    WHERE hd.trang_thai='hieu_luc'
      AND hd.ngay_ket_thuc BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ORDER BY hd.ngay_ket_thuc ASC LIMIT 5
")->fetchAll();

// Hóa đơn quá hạn
$hd_quahan = $db->query("
    SELECT hd.*, p.so_phong, tn.ten_toa
    FROM hoa_don hd
    JOIN phong p ON hd.phong_id = p.id
    JOIN toa_nha tn ON p.toa_nha_id = tn.id
    WHERE hd.trang_thai='qua_han'
    ORDER BY hd.nam DESC, hd.thang DESC LIMIT 5
")->fetchAll();

// Hoạt động gần đây (5 sinh viên mới nhất)
$sv_moi = $db->query("
    SELECT sv.*, p.so_phong, tn.ten_toa
    FROM sinh_vien sv
    LEFT JOIN hop_dong hd ON sv.id = hd.sinh_vien_id AND hd.trang_thai='hieu_luc'
    LEFT JOIN phong p ON hd.phong_id = p.id
    LEFT JOIN toa_nha tn ON p.toa_nha_id = tn.id
    ORDER BY sv.created_at DESC LIMIT 5
")->fetchAll();

// Thống kê phòng theo tòa
$phong_theo_toa = $db->query("
    SELECT tn.ten_toa,
        COUNT(p.id) as tong,
        SUM(p.trang_thai='trong') as trong,
        SUM(p.trang_thai='day') as day,
        SUM(p.trang_thai='bao_tri') as bao_tri
    FROM toa_nha tn
    LEFT JOIN phong p ON tn.id = p.toa_nha_id
    GROUP BY tn.id, tn.ten_toa
    ORDER BY tn.ten_toa
")->fetchAll();

include 'includes/header.php';
?>

<!-- STAT CARDS -->
<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-icon">🏗️</div>
        <div class="stat-value"><?= $stats['toa_nha'] ?></div>
        <div class="stat-label">Tòa nhà hoạt động</div>
    </div>
    <div class="stat-card cyan">
        <div class="stat-icon">🚪</div>
        <div class="stat-value"><?= $stats['phong_total'] ?></div>
        <div class="stat-label">Tổng số phòng
            <br><small><?= $stats['phong_trong'] ?> còn trống</small>
        </div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon">👥</div>
        <div class="stat-value"><?= $stats['sv_total'] ?></div>
        <div class="stat-label">Sinh viên đang ở</div>
    </div>
    <div class="stat-card blue">
        <div class="stat-icon">📋</div>
        <div class="stat-value"><?= $stats['hd_hieuluc'] ?></div>
        <div class="stat-label">Hợp đồng hiệu lực</div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon">💵</div>
        <div class="stat-value" style="font-size:18px"><?= formatMoney($stats['doanh_thu']) ?></div>
        <div class="stat-label">Đã thu tháng <?= $thang ?>/<?= $nam ?></div>
    </div>
    <div class="stat-card red">
        <div class="stat-icon">⚠️</div>
        <div class="stat-value"><?= $stats['hd_chuatt'] + $stats['hd_quahan'] ?></div>
        <div class="stat-label">Hóa đơn chưa TT
            <br><small style="color:#dc2626"><?= $stats['hd_quahan'] ?> quá hạn</small>
        </div>
    </div>
    <div class="stat-card yellow">
        <div class="stat-icon">🚨</div>
        <div class="stat-value"><?= $stats['vp_choxl'] ?></div>
        <div class="stat-label">Vi phạm chờ xử lý</div>
    </div>
    <div class="stat-card yellow">
        <div class="stat-icon">🔧</div>
        <div class="stat-value"><?= $stats['bt_choxl'] ?></div>
        <div class="stat-label">Bảo trì đang xử lý</div>
    </div>
</div>

<!-- PHÒNG THEO TÒA + HỢP ĐỒNG SẮP HẾT HẠN -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">

    <!-- Tỷ lệ phòng theo tòa -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">🏗️ Tình trạng phòng theo tòa</div>
            <a href="phong.php" class="btn btn-sm btn-outline">Xem tất cả</a>
        </div>
        <div class="card-body">
            <?php foreach ($phong_theo_toa as $toa): ?>
            <?php
                $pct = $toa['tong'] > 0 ? round($toa['day'] / $toa['tong'] * 100) : 0;
                $pct_trong = $toa['tong'] > 0 ? round($toa['trong'] / $toa['tong'] * 100) : 0;
            ?>
            <div style="margin-bottom:16px">
                <div class="flex-between mb-1">
                    <span style="font-weight:700"><?= sanitize($toa['ten_toa']) ?></span>
                    <span class="text-muted" style="font-size:12px">
                        <?= $toa['day'] ?>/<?= $toa['tong'] ?> phòng — <?= $toa['trong'] ?> trống
                    </span>
                </div>
                <div style="height:8px;background:#e2e8f0;border-radius:99px;overflow:hidden">
                    <div style="height:100%;width:<?= $pct ?>%;background:linear-gradient(90deg,#1a56db,#0ea5e9);border-radius:99px;transition:.5s"></div>
                </div>
                <div style="display:flex;gap:12px;margin-top:6px;font-size:11.5px">
                    <span style="color:#059669">🟢 <?= $toa['trong'] ?> trống</span>
                    <span style="color:#dc2626">🔴 <?= $toa['day'] ?> đầy</span>
                    <span style="color:#d97706">🟡 <?= $toa['bao_tri'] ?> bảo trì</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Hợp đồng sắp hết hạn -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">⏰ Hợp đồng sắp hết hạn (30 ngày)</div>
            <a href="hop_dong.php" class="btn btn-sm btn-outline">Xem tất cả</a>
        </div>
        <?php if (empty($sap_het)): ?>
        <div class="card-body">
            <div class="empty-state">
                <div class="empty-icon">✅</div>
                <p>Không có hợp đồng sắp hết hạn</p>
            </div>
        </div>
        <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr><th>Sinh viên</th><th>Phòng</th><th>Hết hạn</th><th>Còn</th></tr>
                </thead>
                <tbody>
                <?php foreach ($sap_het as $hd):
                    $days_left = (strtotime($hd['ngay_ket_thuc']) - time()) / 86400;
                    $color = $days_left <= 7 ? '#dc2626' : ($days_left <= 14 ? '#d97706' : '#059669');
                ?>
                <tr>
                    <td>
                        <div class="flex-center" style="gap:8px">
                            <div class="avatar-sm"><?= strtoupper(mb_substr($hd['ho_ten'], 0, 1)) ?></div>
                            <div>
                                <div style="font-weight:600;font-size:13px"><?= sanitize($hd['ho_ten']) ?></div>
                                <div style="font-size:11px;color:#64748b"><?= sanitize($hd['ma_sv']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><code><?= sanitize($hd['ten_toa']) ?> - <?= sanitize($hd['so_phong']) ?></code></td>
                    <td><?= formatDate($hd['ngay_ket_thuc']) ?></td>
                    <td><span style="font-weight:700;color:<?= $color ?>"><?= ceil($days_left) ?> ngày</span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- HÀNG 3 -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

    <!-- Sinh viên mới nhất -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">👤 Sinh viên đăng ký gần đây</div>
            <a href="sinh_vien.php" class="btn btn-sm btn-outline">Xem tất cả</a>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr><th>Sinh viên</th><th>Phòng</th><th>Trạng thái</th></tr>
                </thead>
                <tbody>
                <?php foreach ($sv_moi as $sv): ?>
                <tr>
                    <td>
                        <div class="flex-center" style="gap:8px">
                            <div class="avatar-sm"><?= strtoupper(mb_substr($sv['ho_ten'], 0, 1)) ?></div>
                            <div>
                                <div style="font-weight:600;font-size:13px"><?= sanitize($sv['ho_ten']) ?></div>
                                <div style="font-size:11px;color:#64748b"><?= sanitize($sv['ma_sv']) ?> · <?= sanitize($sv['truong'] ?? '') ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php if ($sv['so_phong']): ?>
                        <code><?= sanitize($sv['ten_toa']) ?> - <?= sanitize($sv['so_phong']) ?></code>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= getStatusBadge($sv['trang_thai'], 'sinh_vien') ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Hóa đơn quá hạn -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">🚨 Hóa đơn quá hạn</div>
            <a href="hoa_don.php?filter=qua_han" class="btn btn-sm btn-danger">Xử lý ngay</a>
        </div>
        <?php if (empty($hd_quahan)): ?>
        <div class="card-body">
            <div class="empty-state">
                <div class="empty-icon">🎉</div>
                <p>Không có hóa đơn quá hạn!</p>
            </div>
        </div>
        <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr><th>Phòng</th><th>Tháng/Năm</th><th>Số tiền</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($hd_quahan as $hd): ?>
                <tr>
                    <td><code><?= sanitize($hd['ten_toa']) ?> - <?= sanitize($hd['so_phong']) ?></code></td>
                    <td><?= $hd['thang'] ?>/<?= $hd['nam'] ?></td>
                    <td style="font-weight:700;color:#dc2626"><?= formatMoney($hd['tong_tien']) ?></td>
                    <td><a href="hoa_don.php?edit=<?= $hd['id'] ?>" class="btn btn-sm btn-warning">TT</a></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
