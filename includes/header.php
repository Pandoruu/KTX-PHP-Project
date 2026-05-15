<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' — ' : '' ?><?= SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary:         #1f3b2c;
            --primary-dark:    #162b21;
            --primary-light:   #e7efe9;
            --accent:          #2f5d3f;
            --success:         #2d6a4f;
            --success-light:   #e2f1e7;
            --danger:          #b42318;
            --danger-light:    #fde8e8;
            --warning:         #b45309;
            --warning-light:   #fef3c7;
            --info:            #0f766e;
            --info-light:      #e0f2f1;
            --secondary:       #6b7280;
            --secondary-light: #f3f4f6;

            --bg:              #f5f1e8;
            --surface:         #fffaf2;
            --surface2:        #f7f3ea;
            --border:          #e3ded3;
            --text:            #1f2937;
            --text-muted:      #6b7280;
            --text-light:      #9ca3af;

            --header-h:        84px;
            --radius:          12px;
            --radius-lg:       18px;
            --shadow:          0 2px 6px rgba(31,41,55,.08), 0 16px 30px rgba(31,41,55,.08);
            --shadow-lg:       0 24px 60px rgba(31,41,55,.16);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Be Vietnam Pro', sans-serif;
            background: radial-gradient(1200px 520px at 10% -10%, #f8f2e7 0%, transparent 60%),
                        radial-gradient(1000px 520px at 90% -10%, #e8efe8 0%, transparent 55%),
                        var(--bg);
            color: var(--text);
            min-height: 100vh;
            font-size: 14px;
            line-height: 1.6;
        }

        /* ===== HEADER LAYOUT ===== */
        .app-header {
            position: sticky; top: 0; z-index: 100;
            height: var(--header-h);
            background: rgba(255,250,242,.92);
            border-bottom: 1px solid var(--border);
            backdrop-filter: blur(10px);
        }

        .app-header-inner {
            max-width: 1200px;
            margin: 0 auto;
            height: 100%;
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 0 20px;
        }

        .brand {
            display: flex; align-items: center; gap: 12px;
            padding-right: 10px;
            border-right: 1px solid var(--border);
        }

        .brand-icon {
            width: 44px; height: 44px;
            border-radius: 12px;
            display: inline-flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: #fff; font-size: 20px;
            box-shadow: 0 10px 20px rgba(31,59,44,.2);
        }

        .brand-title {
            font-size: 14px; font-weight: 800; letter-spacing: .2px;
        }

        .brand-sub {
            font-size: 11px; color: var(--text-muted); font-family: 'JetBrains Mono', monospace;
        }

        .top-nav {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0;
            overflow: visible;
        }

        .nav-group {
            position: relative;
            display: flex;
            align-items: center;
            padding-right: 0;
            border-right: none;
        }

        .nav-section {
            font-size: 12px;
            font-weight: 700;
            color: var(--text);
            letter-spacing: .2px;
            text-transform: none;
        }

        .nav-links { display: none; }

        .dropdown {
            position: relative;
        }

        .dropdown-toggle {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 8px 14px;
            border-radius: 999px;
            color: var(--text);
            text-decoration: none;
            font-size: 13px; font-weight: 700;
            background: var(--surface2);
            border: 1px solid var(--border);
            transition: all .15s;
            white-space: nowrap;
        }

        .dropdown-toggle:hover {
            background: var(--primary-light);
            border-color: rgba(31,59,44,.2);
            color: var(--primary);
        }

        .dropdown-toggle::after {
            content: '▾';
            font-size: 12px;
            color: var(--text-muted);
            margin-left: 2px;
        }

        .dropdown-menu {
            position: absolute;
            top: calc(100% + 10px);
            left: 0;
            min-width: 220px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: var(--shadow-lg);
            padding: 8px;
            display: none;
            z-index: 1000;
        }

        .dropdown-menu::before {
            content: '';
            position: absolute;
            top: -8px;
            left: 16px;
            width: 14px; height: 14px;
            background: var(--surface);
            border-left: 1px solid var(--border);
            border-top: 1px solid var(--border);
            transform: rotate(45deg);
        }

        .dropdown:hover .dropdown-menu {
            display: none;
        }

        .dropdown.open .dropdown-menu {
            display: block;
        }

        .dropdown-item {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 10px;
            border-radius: 10px;
            color: var(--text);
            text-decoration: none;
            font-size: 13px; font-weight: 600;
            transition: all .12s;
        }

        .dropdown-item:hover {
            background: var(--surface2);
            color: var(--primary);
        }

        .dropdown-item.active {
            background: var(--primary-light);
            color: var(--primary);
        }

        .dropdown-item .nav-icon { font-size: 15px; width: 18px; text-align: center; }

        .nav-badge {
            margin-left: 6px;
            background: var(--danger);
            color: #fff;
            font-size: 10px; font-weight: 700;
            padding: 1px 6px;
            border-radius: 20px;
        }

        .user-box {
            display: flex; align-items: center; gap: 10px;
            padding-left: 14px;
            border-left: 1px solid var(--border);
        }

        .user-avatar {
            width: 36px; height: 36px;
            background: var(--primary);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; font-weight: 700; color: #fff;
            flex-shrink: 0;
        }

        .user-meta { display: flex; flex-direction: column; }
        .user-name { font-size: 13px; font-weight: 700; color: var(--text); }
        .user-role { font-size: 11px; color: var(--text-muted); }

        .btn-logout {
            padding: 6px 10px;
            background: var(--surface2);
            color: var(--primary);
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 12px; font-weight: 700;
            text-decoration: none;
            transition: all .15s;
        }
        .btn-logout:hover { background: var(--primary-light); }

        .content-shell {
            max-width: 1200px;
            margin: 0 auto;
            padding: 22px 20px 36px;
        }

        .main-content { padding: 0; }

        /* ===== CARDS ===== */
        .card {
            background: var(--surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
            gap: 12px;
        }

        .card-title {
            font-size: 15px; font-weight: 700;
            display: flex; align-items: center; gap: 8px;
        }

        .card-body { padding: 20px; }

        /* ===== STAT CARDS ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: linear-gradient(180deg, #ffffff 0%, #fbfcff 100%);
            border-radius: var(--radius-lg);
            padding: 20px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            position: relative; overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute; top: 0; left: 0; right: 0;
            height: 3px;
        }
        .stat-card.blue::before   { background: var(--primary); }
        .stat-card.green::before  { background: var(--success); }
        .stat-card.red::before    { background: var(--danger); }
        .stat-card.yellow::before { background: var(--warning); }
        .stat-card.cyan::before   { background: var(--accent); }

        .stat-icon {
            width: 44px; height: 44px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; margin-bottom: 12px;
        }
        .stat-card.blue   .stat-icon { background: var(--primary-light); }
        .stat-card.green  .stat-icon { background: var(--success-light); }
        .stat-card.red    .stat-icon { background: var(--danger-light); }
        .stat-card.yellow .stat-icon { background: var(--warning-light); }
        .stat-card.cyan   .stat-icon { background: var(--info-light); }

        .stat-value { font-size: 28px; font-weight: 800; line-height: 1; margin-bottom: 4px; }
        .stat-label { font-size: 12px; color: var(--text-muted); font-weight: 500; }
        .stat-change { font-size: 11px; margin-top: 8px; color: var(--success); }

        /* ===== TABLE ===== */
        .table-wrapper { overflow-x: auto; }

        table {
            width: 100%; border-collapse: collapse;
            font-size: 13.5px;
        }

        thead th {
            padding: 11px 14px;
            background: linear-gradient(180deg, #fbf8f1 0%, #f3ede2 100%);
            font-weight: 700; font-size: 12px;
            color: var(--text-muted);
            text-transform: uppercase; letter-spacing: .5px;
            text-align: left;
            border-bottom: 2px solid var(--border);
            white-space: nowrap;
        }

        tbody tr { transition: background .1s; }
        tbody tr:hover { background: var(--surface2); }

        tbody td {
            padding: 11px 14px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        tbody tr:last-child td { border-bottom: none; }

        /* ===== BADGES ===== */
        .badge {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11.5px; font-weight: 600;
            white-space: nowrap;
        }
        .badge-success   { background: var(--success-light); color: var(--success); }
        .badge-danger    { background: var(--danger-light);  color: var(--danger); }
        .badge-warning   { background: var(--warning-light); color: var(--warning); }
        .badge-info      { background: var(--info-light);    color: var(--info); }
        .badge-secondary { background: var(--secondary-light);color: var(--secondary); }
        .badge-primary   { background: var(--primary-light); color: var(--primary); }

        /* ===== BUTTONS ===== */
        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 9px 16px;
            border-radius: 10px;
            font-size: 13.5px; font-weight: 600;
            cursor: pointer; border: none;
            text-decoration: none; transition: all .15s;
            white-space: nowrap;
            box-shadow: 0 6px 14px rgba(15,23,42,.08);
        }
        .btn-sm { padding: 5px 10px; font-size: 12px; border-radius: 6px; }
        .btn-lg { padding: 11px 22px; font-size: 15px; }

        .btn-primary { background: linear-gradient(135deg, var(--primary), var(--accent)); color: #fff; }
        .btn-primary:hover { background: linear-gradient(135deg, var(--primary-dark), var(--primary)); }
        .btn-success { background: var(--success); color: #fff; }
        .btn-success:hover { background: #047857; }
        .btn-danger  { background: var(--danger);  color: #fff; }
        .btn-danger:hover  { background: #b91c1c; }
        .btn-warning { background: var(--warning); color: #fff; }
        .btn-warning:hover { background: #b45309; }
        .btn-secondary { background: #f8fafc; color: var(--text); border: 1px solid var(--border); }
        .btn-secondary:hover { background: #eef2ff; }
        .btn-outline  { background: #fff; border: 1.5px solid var(--primary); color: var(--primary); }
        .btn-outline:hover { background: var(--primary); color: #fff; }

        .btn-group { display: flex; gap: 6px; flex-wrap: wrap; }

        /* ===== FORMS ===== */
        .form-grid { display: grid; gap: 16px; }
        .form-grid-2 { grid-template-columns: 1fr 1fr; }
        .form-grid-3 { grid-template-columns: 1fr 1fr 1fr; }
        .form-grid-4 { grid-template-columns: 1fr 1fr 1fr 1fr; }

        .form-group { display: flex; flex-direction: column; gap: 5px; }
        .form-group.full { grid-column: 1 / -1; }

        label {
            font-size: 12.5px; font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase; letter-spacing: .4px;
        }

        input, select, textarea {
            padding: 10px 12px;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            font-size: 13.5px;
            font-family: inherit;
            color: var(--text);
            background: var(--surface);
            transition: border-color .15s, box-shadow .15s;
            width: 100%;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(31,59,44,.16);
        }

        textarea { resize: vertical; min-height: 80px; }

        .form-hint { font-size: 11.5px; color: var(--text-light); }
        .form-error { font-size: 11.5px; color: var(--danger); }

        /* ===== ALERTS ===== */
        .alert {
            padding: 12px 16px;
            border-radius: var(--radius);
            margin-bottom: 16px;
            font-size: 13.5px; font-weight: 500;
            display: flex; align-items: center; gap: 8px;
            box-shadow: 0 8px 20px rgba(15,23,42,.08);
        }
        .alert-success { background: var(--success-light); color: var(--success); border: 1px solid #a7f3d0; }
        .alert-error   { background: var(--danger-light);  color: var(--danger);  border: 1px solid #fca5a5; }
        .alert-warning { background: var(--warning-light); color: var(--warning); border: 1px solid #fcd34d; }
        .alert-info    { background: var(--info-light);    color: var(--info);    border: 1px solid #7dd3fc; }

        /* ===== PAGINATION ===== */
        .pagination {
            display: flex; align-items: center; gap: 4px;
            padding: 12px 0;
        }
        .page-btn {
            display: inline-flex; align-items: center; justify-content: center;
            width: 34px; height: 34px;
            border-radius: 8px;
            font-size: 13px; font-weight: 600;
            text-decoration: none; color: var(--text-muted);
            border: 1px solid var(--border);
            transition: all .15s;
        }
        .page-btn:hover { background: var(--surface2); color: var(--text); }
        .page-btn.active { background: var(--primary); color: #fff; border-color: var(--primary); }
        .page-btn.disabled { opacity: .4; pointer-events: none; }

        /* ===== SEARCH BAR ===== */
        .search-bar {
            display: flex; gap: 10px; align-items: center;
            flex-wrap: wrap;
        }
        .search-input { position: relative; }
        .search-input input { padding-left: 36px; min-width: 220px; }
        .search-input .search-icon {
            position: absolute; left: 11px; top: 50%;
            transform: translateY(-50%);
            font-size: 14px; color: var(--text-muted);
        }

        /* ===== MODAL ===== */
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,.5); backdrop-filter: blur(4px);
            z-index: 1000; align-items: center; justify-content: center;
        }
        .modal-overlay.show { display: flex; }

        .modal {
            background: var(--surface);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            width: 90%; max-width: 640px;
            max-height: 90vh; overflow-y: auto;
        }
        .modal-header {
            padding: 20px 24px 16px;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .modal-title { font-size: 16px; font-weight: 700; }
        .modal-close {
            background: none; border: none;
            font-size: 18px; cursor: pointer;
            color: var(--text-muted); padding: 4px;
            border-radius: 4px;
        }
        .modal-close:hover { background: var(--surface2); }
        .modal-body { padding: 20px 24px; }
        .modal-footer {
            padding: 16px 24px;
            border-top: 1px solid var(--border);
            display: flex; justify-content: flex-end; gap: 10px;
        }

        /* ===== MISC ===== */
        .page-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 20px; flex-wrap: wrap; gap: 12px;
            padding: 14px 18px;
            border-radius: var(--radius-lg);
            background: linear-gradient(135deg, #fffaf2 0%, #f7f3ea 100%);
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
        }
        .page-header h2 { font-size: 20px; font-weight: 800; }
        .page-header p { color: var(--text-muted); font-size: 13px; margin-top: 2px; }

        .empty-state {
            text-align: center; padding: 40px 20px;
            color: var(--text-muted);
        }
        .empty-state .empty-icon { font-size: 48px; margin-bottom: 12px; }
        .empty-state p { font-size: 14px; }

        .text-center { text-align: center; }
        .text-right  { text-align: right; }
        .text-muted  { color: var(--text-muted); }
        .text-danger { color: var(--danger); }
        .text-success{ color: var(--success); }
        .fw-bold { font-weight: 700; }
        .mt-1 { margin-top: 8px; }
        .mt-2 { margin-top: 16px; }
        .mb-1 { margin-bottom: 8px; }
        .mb-2 { margin-bottom: 16px; }
        .gap-2 { gap: 16px; }

        .flex { display: flex; }
        .flex-center { display: flex; align-items: center; }
        .flex-between { display: flex; align-items: center; justify-content: space-between; }

        .divider { height: 1px; background: var(--border); margin: 16px 0; }

        .avatar-sm {
            width: 32px; height: 32px;
            background: var(--primary-light);
            border-radius: 8px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 700;
            color: var(--primary); flex-shrink: 0;
        }

        code {
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            background: var(--surface2);
            padding: 1px 6px; border-radius: 4px;
            color: var(--primary);
        }

        @media (max-width: 1024px) {
            .app-header { height: auto; }
            .app-header-inner { flex-wrap: wrap; padding: 12px 16px; }
            .brand { border-right: none; width: 100%; }
            .top-nav { width: 100%; order: 3; flex-wrap: wrap; }
            .user-box { width: 100%; justify-content: space-between; border-left: none; padding-left: 0; }
            .nav-group { border-right: none; }
        }

        @media (max-width: 768px) {
            .top-nav { gap: 8px; }
            .dropdown-menu { position: static; box-shadow: none; border: 1px dashed var(--border); margin-top: 6px; }
            .dropdown-menu::before { display: none; }
        }
    </style>
</head>
<body>

<!-- HEADER -->
<header class="app-header">
    <div class="app-header-inner">
        <div class="brand">
            <div class="brand-icon">🏢</div>
            <div>
                <div class="brand-title"><?= SITE_NAME ?></div>
                <div class="brand-sub">v<?= SITE_VERSION ?> — <?= date('Y') ?></div>
            </div>
        </div>

        <nav class="top-nav">
            <div class="nav-group dropdown">
                <a class="dropdown-toggle" href="#">Tổng quan</a>
                <div class="dropdown-menu">
                    <a href="index.php" class="dropdown-item <?= basename($_SERVER['PHP_SELF'])=='index.php'?'active':'' ?>">
                        <span class="nav-icon">📊</span> Dashboard
                    </a>
                </div>
            </div>

            <div class="nav-group dropdown">
                <a class="dropdown-toggle" href="#">Quản lý</a>
                <div class="dropdown-menu">
                    <a href="toa_nha.php" class="dropdown-item <?= basename($_SERVER['PHP_SELF'])=='toa_nha.php'?'active':'' ?>">
                        <span class="nav-icon">🏗️</span> Tòa nhà
                    </a>
                    <a href="phong.php" class="dropdown-item <?= basename($_SERVER['PHP_SELF'])=='phong.php'?'active':'' ?>">
                        <span class="nav-icon">🚪</span> Phòng ở
                    </a>
                    <a href="sinh_vien.php" class="dropdown-item <?= basename($_SERVER['PHP_SELF'])=='sinh_vien.php'?'active':'' ?>">
                        <span class="nav-icon">👤</span> Sinh viên
                    </a>
                    <a href="hop_dong.php" class="dropdown-item <?= basename($_SERVER['PHP_SELF'])=='hop_dong.php'?'active':'' ?>">
                        <span class="nav-icon">📋</span> Hợp đồng
                    </a>
                </div>
            </div>

            <div class="nav-group dropdown">
                <a class="dropdown-toggle" href="#">Tài chính</a>
                <div class="dropdown-menu">
                    <a href="hoa_don.php" class="dropdown-item <?= basename($_SERVER['PHP_SELF'])=='hoa_don.php'?'active':'' ?>">
                        <span class="nav-icon">💰</span> Hóa đơn
                        <?php
                        try {
                            $db = getDB();
                            $cnt = $db->query("SELECT COUNT(*) FROM hoa_don WHERE trang_thai IN ('chua_thanh_toan','qua_han')")->fetchColumn();
                            if ($cnt > 0) echo '<span class="nav-badge">' . $cnt . '</span>';
                        } catch(Exception $e) {}
                        ?>
                    </a>
                </div>
            </div>

            <div class="nav-group dropdown">
                <a class="dropdown-toggle" href="#">Tiện ích</a>
                <div class="dropdown-menu">
                    <a href="vi_pham.php" class="dropdown-item <?= basename($_SERVER['PHP_SELF'])=='vi_pham.php'?'active':'' ?>">
                        <span class="nav-icon">⚠️</span> Vi phạm
                        <?php
                        try {
                            $db = getDB();
                            $cnt2 = $db->query("SELECT COUNT(*) FROM vi_pham WHERE trang_thai='cho_xu_ly'")->fetchColumn();
                            if ($cnt2 > 0) echo '<span class="nav-badge">' . $cnt2 . '</span>';
                        } catch(Exception $e) {}
                        ?>
                    </a>
                    <a href="bao_tri.php" class="dropdown-item <?= basename($_SERVER['PHP_SELF'])=='bao_tri.php'?'active':'' ?>">
                        <span class="nav-icon">🔧</span> Bảo trì
                        <?php
                        try {
                            $db = getDB();
                            $cnt3 = $db->query("SELECT COUNT(*) FROM bao_tri WHERE trang_thai='cho_xu_ly'")->fetchColumn();
                            if ($cnt3 > 0) echo '<span class="nav-badge">' . $cnt3 . '</span>';
                        } catch(Exception $e) {}
                        ?>
                    </a>
                    <a href="bao_cao.php" class="dropdown-item <?= basename($_SERVER['PHP_SELF'])=='bao_cao.php'?'active':'' ?>">
                        <span class="nav-icon">📈</span> Báo cáo
                    </a>
                </div>
            </div>

            <?php if (isAdmin()): ?>
            <div class="nav-group dropdown">
                <a class="dropdown-toggle" href="#">Hệ thống</a>
                <div class="dropdown-menu">
                    <a href="admin.php" class="dropdown-item <?= basename($_SERVER['PHP_SELF'])=='admin.php'?'active':'' ?>">
                        <span class="nav-icon">⚙️</span> Quản trị
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </nav>

        <div class="user-box">
            <div class="user-avatar"><?= strtoupper(substr($_SESSION['ho_ten'] ?? 'A', 0, 1)) ?></div>
            <div class="user-meta">
                <div class="user-name"><?= sanitize($_SESSION['ho_ten'] ?? 'Admin') ?></div>
                <div class="user-role"><?= $_SESSION['vai_tro'] === 'quan_tri' ? '👑 Quản trị' : '👷 Nhân viên' ?></div>
            </div>
            <a href="logout.php" class="btn-logout">Đăng xuất</a>
        </div>
    </div>
</header>

<div class="content-shell">
    <main class="main-content">
        <?php showFlash(); ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dropdowns = document.querySelectorAll('.dropdown');

    dropdowns.forEach(dd => {
        const toggle = dd.querySelector('.dropdown-toggle');
        if (!toggle) return;
        toggle.addEventListener('click', function (e) {
            e.preventDefault();
            const isOpen = dd.classList.contains('open');
            dropdowns.forEach(d => d.classList.remove('open'));
            if (!isOpen) dd.classList.add('open');
        });
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.dropdown')) {
            dropdowns.forEach(d => d.classList.remove('open'));
        }
    });
});
</script>

</main>
</div>

</body>
</html>
