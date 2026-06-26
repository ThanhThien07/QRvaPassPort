<?php
/**
 * QRvaPassPort - Admin Login Portal
 * Antigravity - Premium AI Developer
 */
require_once 'config.php';
session_start();

// Nếu đã đăng nhập rồi, chuyển thẳng tới Dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin_dashboard.php");
    exit;
}

$login_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Thông tin đăng nhập mặc định cho Admin sự kiện
    $admin_user = 'admin';
    $admin_pass = 'admin123'; // Có thể đổi mật khẩu tại đây

    if ($username === $admin_user && $password === $admin_pass) {
        // Thiết lập Session đăng nhập
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        
        header("Location: admin_dashboard.php");
        exit;
    } else {
        $login_error = 'Tên đăng nhập hoặc mật khẩu không chính xác!';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập Quản Trị - Passport Điện Tử</title>
    <!-- CSS chính -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <!-- Header -->
    <header>
        <a href="index.php" class="logo-container">
            <div class="logo-icon">P</div>
            <div>
                <div class="logo-text">PASSPORT</div>
                <div class="logo-sub">Điện Tử Học Đường</div>
            </div>
        </a>
        <nav>
            <a href="index.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Quay lại</a>
        </nav>
    </header>

    <!-- Main Content -->
    <main>
        <div class="login-container">
            <div class="glass-panel">
                <div style="text-align: center; margin-bottom: 2rem;">
                    <div style="width: 60px; height: 60px; background: rgba(0, 240, 255, 0.1); color: var(--student-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 1rem auto; box-shadow: 0 0 15px rgba(0,240,255,0.15);">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>
                    <h2 style="font-size: 1.8rem;">Đăng Nhập Admin</h2>
                    <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.3rem;">Khu vực dành riêng cho Ban tổ chức / Quản trị viên</p>
                </div>

                <!-- Hiển thị thông báo lỗi -->
                <?php if (!empty($login_error)): ?>
                    <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #f87171; padding: 0.8rem 1rem; border-radius: 12px; margin-bottom: 1.5rem; font-size: 0.9rem; display: flex; align-items: center; gap: 0.8rem;">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span><?php echo $login_error; ?></span>
                    </div>
                <?php endif; ?>

                <!-- Form đăng nhập -->
                <form action="admin_login.php" method="POST">
                    <!-- Username -->
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-user"></i> Tên đăng nhập</label>
                        <input type="text" name="username" class="form-control" placeholder="Tài khoản mặc định: admin" required autocomplete="off">
                    </div>

                    <!-- Password -->
                    <div class="form-group" style="margin-bottom: 2rem;">
                        <label class="form-label"><i class="fa-solid fa-lock"></i> Mật khẩu</label>
                        <input type="password" name="password" class="form-control" placeholder="Mật khẩu mặc định: admin123" required>
                    </div>

                    <!-- Nút Submit -->
                    <button type="submit" class="btn btn-primary btn-accent-student" style="width: 100%; padding: 0.85rem; font-size: 1rem;">
                        <i class="fa-solid fa-right-to-bracket"></i> Đăng Nhập Hệ Thống
                    </button>
                </form>
                
                <div style="margin-top: 1.5rem; text-align: center; border-top: 1px solid var(--border-color); padding-top: 1rem; font-size: 0.8rem; color: var(--text-muted);">
                    <p><i class="fa-solid fa-info-circle"></i> Tài khoản trải nghiệm:</p>
                    <p style="font-family: monospace; margin-top: 0.2rem; color: #fff;">User: admin / Pass: admin123</p>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2026 Hệ thống Passport Điện Tử Học Đường. Phát triển bởi Antigravity AI.</p>
    </footer>

</body>
</html>
