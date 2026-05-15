<?php
require_once 'config.php';
requireLogin();
$pageTitle = 'Báo cáo & Thống kê';
$db = getDB();

$nam = (int)($_GET['nam'] ?? date('Y'));

// Doanh thu theo tháng
$dtThang = $db->prepare("
    SELECT thang,
        SUM(CASE WHEN trang_thai='da_thanh_toan' THEN tong_tien ELSE 0 END) as da_thu,
        SUM(CASE WHEN trang_thai!='da_thanh_toan' THEN tong_tien ELSE 0 END) as chua_thu,
        SUM(tong_tien) as tong
    FROM hoa_don WHERE nam=?
    GROUP BY thang ORDER BY thang
");
$dtThang->execute([$nam]);
$dtThang = $dtThang->fetchAll(PDO::FETCH_ASSOC);
$dtMap = [];
foreach ($dtThang as $r) $dtMap[$r['thang']] = $r;

// Thống kê tổng
$tongDaThu = array_sum(array_column($dtThang, 'da_thu'));
$tongChuaThu = array_sum(array_column($dtThang, 'chua_thu'));

// Sinh viên theo trường
$svTruong = $db->query("SELECT truong, COUNT(*) as so_luong FROM sinh_vien WHERE trang_thai='dang_o' AND truong IS NOT NULL GROUP BY truong ORDER BY so_luong DESC LIMIT 8")->fetchAll();

// Phòng tỷ lệ sử dụng
$phongStats = $db->query("
    SELECT trang_thai, COUNT(*) as cnt
    FROM phong GROUP BY trang_thai
")->fetchAll();
$phongMap = [];
foreach ($phongStats as $r) $phongMap[$r['trang_thai']] = $r['cnt'];
$totalPhong = array_sum(array_column($phongStats,'cnt'));

// Vi phạm theo tháng (năm hiện tại)
$vpThang = $db->prepare("SELECT MONTH(ngay_vi_pham) as thang, COUNT(*) as cnt FROM vi_pham WHERE YEAR(ngay_vi_pham)=? GROUP BY MONTH(ngay_vi_pham)")->execute([$nam]);
$vpThang = $db->prepare("SELECT MONTH(ngay_vi_pham) as thang, COUNT(*) as cnt, muc_do FROM vi_pham WHERE YEAR(ngay_vi_pham)=? GROUP BY MONTH(ngay_vi_pham), muc_do");
$vpThang->execute([$nam]); $vpThang = $vpThang->fetchAll();

// Top phòng doanh thu cao
$topPhong = $db->prepare("
    SELECT p.so_phong, tn.ten_toa, SUM(hd.tong_tien) as tong
    FROM hoa_don hd
    JOIN phong p ON hd.phong_id=p.id
    JOIN toa_nha tn ON p.toa_nha_id=tn.id
    WHERE hd.nam=? AND hd.trang_thai='da_thanh_toan'
    GROUP BY p.id ORDER BY tong DESC LIMIT 5
");
$topPhong->execute([$nam]); $topPhong = $topPhong->fetchAll();

include 'includes/header.php';
?>

<div class="page-header">
    <div><h2>📈 Báo cáo & Thống kê</h2><p>Tổng quan hoạt động ký túc xá</p></div>
    <form method="GET" style="display:flex;gap:8px;align-items:center">
        <label style="text-transform:none;font-size:13px;font-weight:600">Năm:</label>
        <select name="nam" style="min-width:100px" onchange="this.form.submit()">
            <?php for($y=date('Y');$y>=date('Y')-3;$y--): ?>
            <option value="<?= $y ?>" <?= $nam==$y?'selected':'' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </form>
</div>

<!-- TỔNG QUAN -->
<div class="stats-grid" style="margin-bottom:20px">
    <div class="stat-card green">
        <div class="stat-icon">💵</div>
        <div class="stat-value" style="font-size:18px"><?= formatMoney($tongDaThu) ?></div>
        <div class="stat-label">Tổng đã thu <?= $nam ?></div>
    </div>
    <div class="stat-card red">
        <div class="stat-icon">⏳</div>
        <div class="stat-value" style="font-size:18px"><?= formatMoney($tongChuaThu) ?></div>
        <div class="stat-label">Tổng chưa thu <?= $nam ?></div>
    </div>
    <div class="stat-card blue">
        <div class="stat-icon">🚪</div>
        <div class="stat-value"><?= $phongMap['day']??0 ?>/<?= $totalPhong ?></div>
        <div class="stat-label">Phòng đang có SV</div>
    </div>
    <div class="stat-card yellow">
        <div class="stat-icon">👥</div>
        <div class="stat-value"><?= $db->query("SELECT COUNT(*) FROM sinh_vien WHERE trang_thai='dang_o'")->fetchColumn() ?></div>
        <div class="stat-label">SV đang cư trú</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:20px">
    <!-- Doanh thu theo tháng -->
    <div class="card">
        <div class="card-header"><div class="card-title">📊 Doanh thu theo tháng năm <?= $nam ?></div></div>
        <div class="card-body">
            <?php
            $maxVal = max(array_map(fn($r)=>$r['tong'], $dtThang) ?: [1]);
            for ($m=1;$m<=12;$m++):
                $r = $dtMap[$m] ?? ['da_thu'=>0,'chua_thu'=>0,'tong'=>0];
                $pct = $maxVal > 0 ? round($r['tong']/$maxVal*100) : 0;
                $pctDa = $r['tong'] > 0 ? round($r['da_thu']/$r['tong']*100) : 0;
            ?>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
                <div style="width:50px;font-size:12px;font-weight:700;color:#64748b;flex-shrink:0">T<?= $m ?></div>
                <div style="flex:1;height:22px;background:#f1f5f9;border-radius:6px;overflow:hidden;position:relative">
                    <?php if ($r['tong']>0): ?>
                    <div style="height:100%;width:<?= $pct ?>%;background:#e8f0fe;border-radius:6px;position:relative">
                        <div style="height:100%;width:<?= $pctDa ?>%;background:linear-gradient(90deg,#059669,#10b981);border-radius:6px"></div>
                    </div>
                    <?php endif; ?>
                </div>
                <div style="width:130px;text-align:right;font-size:12px;flex-shrink:0">
                    <?php if ($r['tong']>0): ?>
                    <span style="font-weight:700;color:#059669"><?= formatMoney($r['da_thu']) ?></span>
                    <?php if ($r['chua_thu']>0): ?>
                    <span style="color:#dc2626"> / <?= formatMoney($r['chua_thu']) ?></span>
                    <?php endif; ?>
                    <?php else: ?><span style="color:#94a3b8">—</span><?php endif; ?>
                </div>
            </div>
            <?php endfor; ?>
            <div style="display:flex;gap:16px;margin-top:12px;font-size:12px">
                <span style="display:flex;align-items:center;gap:4px"><span style="width:12px;height:12px;background:linear-gradient(90deg,#059669,#10b981);border-radius:3px;display:inline-block"></span>Đã thu</span>
                <span style="display:flex;align-items:center;gap:4px"><span style="width:12px;height:12px;background:#e8f0fe;border:1px solid #1a56db;border-radius:3px;display:inline-block"></span>Chưa thu</span>
            </div>
        </div>
    </div>

    <!-- Tỷ lệ phòng -->
    <div class="card">
        <div class="card-header"><div class="card-title">🚪 Tỷ lệ sử dụng phòng</div></div>
        <div class="card-body">
            <?php
            $roomData = [
                'day'     => ['label'=>'Đã đầy',    'color'=>'#dc2626','bg'=>'#fee2e2'],
                'trong'   => ['label'=>'Còn trống', 'color'=>'#059669','bg'=>'#d1fae5'],
                'bao_tri' => ['label'=>'Bảo trì',   'color'=>'#d97706','bg'=>'#fef3c7'],
            ];
            foreach ($roomData as $k=>$info):
                $cnt = $phongMap[$k]??0;
                $pct = $totalPhong>0 ? round($cnt/$totalPhong*100) : 0;
            ?>
            <div style="margin-bottom:16px">
                <div class="flex-between" style="margin-bottom:5px">
                    <span style="font-size:13px;font-weight:600"><?= $info['label'] ?></span>
                    <span style="font-size:12px;color:#64748b"><?= $cnt ?> / <?= $totalPhong ?> (<?= $pct ?>%)</span>
                </div>
                <div style="height:10px;background:#f1f5f9;border-radius:99px;overflow:hidden">
                    <div style="height:100%;width:<?= $pct ?>%;background:<?= $info['color'] ?>;border-radius:99px"></div>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="divider"></div>
            <div style="font-size:12.5px;font-weight:700;margin-bottom:8px;color:#64748b">🏆 Top phòng doanh thu</div>
            <?php foreach ($topPhong as $tp): ?>
            <div class="flex-between" style="margin-bottom:6px;font-size:12.5px">
                <code><?= sanitize($tp['ten_toa']) ?>-<?= sanitize($tp['so_phong']) ?></code>
                <span style="font-weight:700;color:#1a56db"><?= formatMoney($tp['tong']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Sinh viên theo trường -->
<div class="card">
    <div class="card-header"><div class="card-title">🏫 Phân bổ sinh viên theo trường</div></div>
    <div class="card-body">
        <?php
        $maxSv = $svTruong ? max(array_column($svTruong,'so_luong')) : 1;
        $colors = ['#1a56db','#0891b2','#059669','#d97706','#7c3aed','#db2777','#dc2626','#4f46e5'];
        foreach ($svTruong as $i => $tr):
            $pct = round($tr['so_luong']/$maxSv*100);
            $color = $colors[$i%count($colors)];
        ?>
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px">
            <div style="width:220px;font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;flex-shrink:0"><?= sanitize($tr['truong']) ?></div>
            <div style="flex:1;height:18px;background:#f1f5f9;border-radius:6px;overflow:hidden">
                <div style="height:100%;width:<?= $pct ?>%;background:<?= $color ?>;border-radius:6px;transition:.6s"></div>
            </div>
            <div style="width:40px;text-align:right;font-size:13px;font-weight:700;color:<?= $color ?>"><?= $tr['so_luong'] ?></div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($svTruong)): ?><div class="empty-state"><p>Chưa có dữ liệu</p></div><?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
