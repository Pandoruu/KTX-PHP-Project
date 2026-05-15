<?php
// =====================================================
// CẤU HÌNH HỆ THỐNG KÝ TÚC XÁ
// =====================================================

define('DB_HOST', 'localhost:3307');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ktx_db');
define('DB_CHARSET', 'utf8mb4');

define('SITE_NAME', 'Quản Lý Ký Túc Xá');
define('SITE_VERSION', '1.0.0');

// Múi giờ
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Kết nối database
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('<div style="padding:20px;background:#fee;border:2px solid #f00;margin:20px;border-radius:8px;font-family:sans-serif;">
                <h3>❌ Lỗi kết nối cơ sở dữ liệu</h3>
                <p>' . htmlspecialchars($e->getMessage()) . '</p>
                <p>Vui lòng kiểm tra cấu hình trong file <code>config.php</code></p>
            </div>');
        }
    }
    return $pdo;
}

// Khởi động session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Hàm tiện ích
function redirect($url) {
    header("Location: $url");
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function isAdmin() {
    return isset($_SESSION['vai_tro']) && $_SESSION['vai_tro'] === 'quan_tri';
}

function formatMoney($amount) {
    return number_format($amount, 0, ',', '.') . ' đ';
}

function formatDate($date) {
    if (!$date) return 'N/A';
    return date('d/m/Y', strtotime($date));
}

function formatDateTime($dt) {
    if (!$dt) return 'N/A';
    return date('d/m/Y H:i', strtotime($dt));
}

function sanitize($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

function getStatusBadge($status, $type = 'default') {
    $badges = [
        'phong' => [
            'trong'   => ['class' => 'badge-success', 'label' => '🟢 Còn trống'],
            'day'     => ['class' => 'badge-danger',  'label' => '🔴 Đã đầy'],
            'bao_tri' => ['class' => 'badge-warning', 'label' => '🟡 Bảo trì'],
        ],
        'hop_dong' => [
            'hieu_luc' => ['class' => 'badge-success', 'label' => '✅ Hiệu lực'],
            'het_han'  => ['class' => 'badge-secondary','label' => '⏰ Hết hạn'],
            'da_huy'   => ['class' => 'badge-danger',  'label' => '❌ Đã hủy'],
        ],
        'hoa_don' => [
            'chua_thanh_toan' => ['class' => 'badge-warning', 'label' => '⏳ Chưa TT'],
            'da_thanh_toan'   => ['class' => 'badge-success', 'label' => '✅ Đã TT'],
            'qua_han'         => ['class' => 'badge-danger',  'label' => '🚨 Quá hạn'],
        ],
        'sinh_vien' => [
            'dang_o'   => ['class' => 'badge-success', 'label' => '🏠 Đang ở'],
            'da_roi'   => ['class' => 'badge-secondary','label' => '🚪 Đã rời'],
            'tam_vang' => ['class' => 'badge-warning', 'label' => '✈️ Tạm vắng'],
        ],
        'vi_pham' => [
            'cho_xu_ly' => ['class' => 'badge-danger',  'label' => '⚠️ Chờ xử lý'],
            'da_xu_ly'  => ['class' => 'badge-success', 'label' => '✅ Đã xử lý'],
        ],
        'bao_tri' => [
            'cho_xu_ly'  => ['class' => 'badge-warning',   'label' => '📋 Chờ xử lý'],
            'dang_xu_ly' => ['class' => 'badge-info',      'label' => '🔧 Đang xử lý'],
            'hoan_thanh' => ['class' => 'badge-success',   'label' => '✅ Hoàn thành'],
            'huy'        => ['class' => 'badge-secondary', 'label' => '❌ Đã hủy'],
        ],
        'toa_nha' => [
            'hoat_dong'  => ['class' => 'badge-success',   'label' => '✅ Hoạt động'],
            'bao_tri'    => ['class' => 'badge-warning',   'label' => '🔧 Bảo trì'],
            'dong_cua'   => ['class' => 'badge-secondary', 'label' => '🚪 Đóng cửa'],
        ],
    ];
    $b = $badges[$type][$status] ?? ['class' => 'badge-secondary', 'label' => $status];
    return '<span class="badge ' . $b['class'] . '">' . $b['label'] . '</span>';
}

function generateMaHoaDon($phong, $thang, $nam) {
    return 'HD-' . strtoupper(str_replace(' ', '', $phong)) . '-' . $nam . '-' . str_pad($thang, 2, '0', STR_PAD_LEFT);
}

function generateMaHopDong() {
    return 'HD' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

function getFlash($key) {
    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function setFlash($key, $msg) {
    $_SESSION['flash'][$key] = $msg;
}

function showFlash() {
    $types = ['success', 'error', 'warning', 'info'];
    foreach ($types as $t) {
        $msg = getFlash($t);
        if ($msg) {
            $icon = ['success'=>'✅','error'=>'❌','warning'=>'⚠️','info'=>'ℹ️'][$t];
            echo '<div class="alert alert-' . $t . '">' . $icon . ' ' . sanitize($msg) . '</div>';
        }
    }
}
?>
