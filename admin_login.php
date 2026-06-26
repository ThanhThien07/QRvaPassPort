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
    <!-- Tailwind CSS Play CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        student: {
                            primary: '#0284c7',
                            secondary: '#1d4ed8',
                            glow: 'rgba(2, 132, 199, 0.15)',
                        },
                        parent: {
                            primary: '#d97706',
                            secondary: '#c2410c',
                            glow: 'rgba(217, 119, 6, 0.15)',
                        }
                    },
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                        serif: ['Times New Roman', 'serif'],
                    }
                }
            }
        }
    </script>
    <!-- CSS chính (Tối giản chỉ giữ card & animation) -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="min-h-screen flex flex-col antialiased text-slate-800">

    <!-- Header -->
    <header class="sticky top-0 z-50 flex justify-between items-center px-6 py-4 bg-white/75 backdrop-blur-md border-b border-slate-200/80 no-print">
        <a href="index.php" class="flex items-center gap-3 no-underline text-slate-900 group">
            <div class="w-10 h-10 bg-gradient-to-br from-sky-500 to-blue-600 rounded-xl flex items-center justify-center font-extrabold text-white text-lg shadow-md group-hover:scale-105 transition-transform">P</div>
            <div>
                <div class="text-lg font-extrabold tracking-tight leading-none">PASSPORT</div>
                <div class="text-[10px] text-slate-500 font-medium tracking-wider uppercase mt-0.5">Điện Tử Học Đường</div>
            </div>
        </a>
        <nav>
            <a href="index.php" class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl font-semibold text-sm bg-slate-100 border border-slate-200 text-slate-700 hover:bg-slate-200/80 hover:border-slate-300 active:bg-slate-100 transition-all duration-300"><i class="fa-solid fa-arrow-left"></i> Quay lại</a>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="flex-1 flex items-center justify-center px-6 py-12">
        <div class="max-w-md w-full">
            <div class="p-8 md:p-10 bg-white border border-slate-100 rounded-3xl shadow-xl shadow-slate-100">
                <div class="text-center mb-8">
                    <div class="w-14 h-14 bg-sky-50 text-sky-500 rounded-2xl flex items-center justify-center text-2xl mx-auto mb-4 shadow-md shadow-sky-500/5">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>
                    <h2 class="text-2xl font-extrabold text-slate-800">Đăng Nhập Admin</h2>
                    <p class="text-slate-400 text-sm mt-2">Khu vực dành riêng cho Ban tổ chức / Quản trị viên</p>
                </div>

                <!-- Hiển thị thông báo lỗi -->
                <?php if (!empty($login_error)): ?>
                    <div class="bg-red-50 border border-red-200 text-red-600 p-4 rounded-2xl mb-6 text-sm flex items-center gap-3">
                        <i class="fa-solid fa-circle-exclamation text-lg"></i>
                        <span><?php echo $login_error; ?></span>
                    </div>
                <?php endif; ?>

                <!-- Form đăng nhập -->
                <form action="admin_login.php" method="POST" class="space-y-5">
                    <!-- Username -->
                    <div>
                        <label class="block font-semibold text-slate-700 text-sm mb-2"><i class="fa-solid fa-user mr-1.5 text-slate-400"></i> Tên đăng nhập</label>
                        <input type="text" name="username" class="w-full bg-slate-50 border border-slate-200/80 rounded-2xl py-3.5 px-4 text-slate-900 placeholder-slate-400 focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-500/10 focus:bg-white transition-all duration-300" placeholder="Tài khoản mặc định: admin" required autocomplete="off">
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block font-semibold text-slate-700 text-sm mb-2"><i class="fa-solid fa-lock mr-1.5 text-slate-400"></i> Mật khẩu</label>
                        <input type="password" name="password" class="w-full bg-slate-50 border border-slate-200/80 rounded-2xl py-3.5 px-4 text-slate-900 placeholder-slate-400 focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-500/10 focus:bg-white transition-all duration-300" placeholder="Mật khẩu mặc định: admin123" required>
                    </div>

                    <!-- Nút Submit -->
                    <button type="submit" class="w-full py-4 px-6 rounded-2xl font-bold text-base text-white bg-gradient-to-r from-sky-500 to-blue-600 shadow-lg shadow-sky-500/25 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-sky-500/30 hover:brightness-105 active:translate-y-0 active:shadow-lg transition-all duration-300">
                        <i class="fa-solid fa-right-to-bracket mr-1.5"></i> Đăng Nhập Hệ Thống
                    </button>
                </form>
                
                <div class="mt-6 pt-5 border-t border-slate-100 text-center text-slate-400 text-xs">
                    <p class="mb-1"><i class="fa-solid fa-info-circle mr-1"></i> Tài khoản trải nghiệm:</p>
                    <p class="font-mono font-bold text-slate-600">User: <span class="text-sky-600">admin</span> / Pass: <span class="text-sky-600">admin123</span></p>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="text-center py-6 border-t border-slate-200/60 mt-auto text-slate-400 text-sm no-print">
        <p>&copy; 2026 Hệ thống Passport Điện Tử Học Đường. Phát triển bởi Antigravity AI.</p>
    </footer>

</body>
</html>
