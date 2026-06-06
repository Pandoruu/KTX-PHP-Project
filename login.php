<?php
require_once 'config.php';

if (isLoggedIn()) redirect('index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = trim($_POST['password'] ?? '');

    if ($user && $pass) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM admin WHERE ten_dang_nhap = ? AND trang_thai = 'hoat_dong'");
        $stmt->execute([$user]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($pass, $admin['mat_khau'])) {
            $_SESSION['admin_id']  = $admin['id'];
            $_SESSION['ho_ten']    = $admin['ho_ten'];
            $_SESSION['vai_tro']   = $admin['vai_tro'];
            $_SESSION['username']  = $admin['ten_dang_nhap'];

            $db->prepare("UPDATE admin SET lan_dang_nhap_cuoi = NOW() WHERE id = ?")->execute([$admin['id']]);
            redirect('index.php');
        } else {
            $error = 'Tên đăng nhập hoặc mật khẩu không đúng!';
        }
    } else {
        $error = 'Vui lòng nhập đầy đủ thông tin!';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập — <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Be Vietnam Pro', sans-serif;
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            background: #0f172a;
            position: relative; overflow: hidden;
            padding: 24px;
        }
        .bg-grid {
            position: fixed; inset: 0;
            background-image: linear-gradient(rgba(26,86,219,.15) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(26,86,219,.15) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
        }
        .bg-glow {
            position: fixed;
            width: 600px; height: 600px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(26,86,219,.25) 0%, transparent 70%);
            top: -200px; left: -100px;
            pointer-events: none;
        }
        .bg-glow2 {
            position: fixed;
            width: 400px; height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(14,165,233,.2) 0%, transparent 70%);
            bottom: -100px; right: -50px;
            pointer-events: none;
        }
        .login-card {
            position: relative; z-index: 10;
            background: rgba(255,255,255,.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 20px;
            padding: 44px 40px;
            width: 100%; max-width: 420px;
            box-shadow: 0 25px 60px rgba(0,0,0,.4);
            margin: 0 auto;
        }
        .brand {
            text-align: center; margin-bottom: 32px;
        }
        .brand-icon {
            width: 64px; height: 64px;
            background: linear-gradient(135deg, #1a56db, #0ea5e9);
            border-radius: 16px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 28px; margin-bottom: 16px;
            box-shadow: 0 8px 24px rgba(26,86,219,.4);
        }
        .brand h1 { font-size: 22px; font-weight: 800; color: #fff; }
        .brand p  { font-size: 13px; color: #64748b; margin-top: 4px; }
        .form-group { margin-bottom: 18px; }
        label {
            display: block; font-size: 12px; font-weight: 700;
            color: #94a3b8; text-transform: uppercase; letter-spacing: .5px;
            margin-bottom: 6px;
        }
        .input-wrap { position: relative; }
        .input-icon {
            position: absolute; left: 12px; top: 50%;
            transform: translateY(-50%); font-size: 16px;
            color: #475569;
        }
        input {
            width: 100%; padding: 11px 14px 11px 40px;
            background: rgba(255,255,255,.07);
            border: 1.5px solid rgba(255,255,255,.12);
            border-radius: 10px;
            font-size: 14px; font-family: inherit; color: #fff;
            transition: all .2s;
        }
        input::placeholder { color: #475569; }
        input:focus {
            outline: none;
            border-color: #1a56db;
            background: rgba(26,86,219,.1);
            box-shadow: 0 0 0 3px rgba(26,86,219,.2);
        }
        .error-msg {
            background: rgba(220,38,38,.15);
            border: 1px solid rgba(220,38,38,.3);
            border-radius: 8px; padding: 10px 14px;
            color: #f87171; font-size: 13px;
            margin-bottom: 16px;
            display: flex; align-items: center; gap: 6px;
        }
        .btn-login {
            width: 100%; padding: 13px;
            background: linear-gradient(135deg, #1a56db, #0ea5e9);
            color: #fff; border: none; border-radius: 10px;
            font-size: 15px; font-weight: 700;
            cursor: pointer; transition: all .2s;
            font-family: inherit;
            box-shadow: 0 4px 15px rgba(26,86,219,.4);
        }
        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(26,86,219,.5);
        }
        .btn-login:active { transform: translateY(0); }
        .hint {
            margin-top: 24px; padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,.08);
            text-align: center;
        }
        .hint p { font-size: 12px; color: #475569; margin-bottom: 6px; }
        .hint code {
            background: rgba(255,255,255,.07);
            padding: 3px 8px; border-radius: 5px;
            font-size: 12px; color: #94a3b8;
            font-family: monospace;
        }
    </style>
</head>
<body>
<div class="bg-grid"></div>
<div class="bg-glow"></div>
<div class="bg-glow2"></div>

<div class="login-card">
    <div class="brand">
        <div class="brand-icon">🏢</div>
        <h1><?= SITE_NAME ?></h1>
        <p>Hệ thống quản lý ký túc xá</p>
    </div>

    <?php if ($error): ?>
    <div class="error-msg">❌ <?= sanitize($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Tên đăng nhập</label>
            <div class="input-wrap">
                <span class="input-icon">👤</span>
                <input type="text" name="username" placeholder="Nhập tên đăng nhập..."
                       value="<?= sanitize($_POST['username'] ?? '') ?>" required autofocus>
            </div>
        </div>
        <div class="form-group">
            <label>Mật khẩu</label>
            <div class="input-wrap">
                <span class="input-icon">🔑</span>
                <input type="password" name="password" placeholder="Nhập mật khẩu..." required>
            </div>
        </div>
        <button type="submit" class="btn-login">🚀 Đăng nhập</button>
    </form>

    <div class="hint">
        <p>Tài khoản mặc định:</p>
        <code>admin / password</code>
    </div>
</div>
</body>
</html>
