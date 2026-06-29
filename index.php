<?php
/**
 * QRvaPassPort - Landing Page & Passport Lookup
 * Antigravity - Premium AI Developer
 */
require_once 'config.php';

$search_error = '';

// Xử lý tra cứu Passport
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_query'])) {
    $search_query = trim($_POST['search_query']);
    
    if (empty($search_query)) {
        $search_error = 'Vui lòng nhập số điện thoại hoặc mã Passport!';
    } else {
        // Tìm kiếm trong database theo passport_code hoặc phone
        $stmt = $pdo->prepare("SELECT `passport_code` FROM `passports` WHERE `passport_code` = ? OR `phone` = ? LIMIT 1");
        $stmt->execute([$search_query, $search_query]);
        $passport = $stmt->fetch();
        
        if ($passport) {
            // Nếu tìm thấy, chuyển hướng sang trang hiển thị Passport
            header("Location: passport.php?code=" . $passport['passport_code']);
            exit;
        } else {
            $search_error = 'Không tìm thấy Passport nào khớp với thông tin bạn cung cấp. Vui lòng kiểm tra lại!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passport Điện Tử - Ngày Hội Trường Học</title>
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
    <link rel="stylesheet" href="assets/css/style.css?v=1.0.4">
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
        <nav class="flex gap-3">
            <a href="register.php" class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl font-semibold text-sm bg-gradient-to-r from-sky-500 to-blue-600 text-white shadow-md shadow-sky-500/15 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-sky-500/20 active:translate-y-0 active:shadow-md transition-all duration-300"><i class="fa-solid fa-id-card"></i> Đăng ký ngay</a>
            <a href="admin_login.php" class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl font-semibold text-sm bg-slate-100 border border-slate-200 text-slate-700 hover:bg-slate-200/80 hover:border-slate-300 active:bg-slate-100 transition-all duration-300"><i class="fa-solid fa-user-shield"></i> Admin</a>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-6xl w-full mx-auto px-6 py-12">
        <!-- Hero Section -->
        <section class="text-center max-w-3xl mx-auto mb-16 px-4">
            <div class="inline-flex items-center gap-2 bg-sky-50 border border-sky-100/80 px-4 py-1.5 rounded-full text-xs text-sky-600 font-semibold mb-6">
                <i class="fa-solid fa-sparkles"></i> Sự Kiện Đặc Biệt Trường Học 2026
            </div>
            <h1 class="text-4xl md:text-5xl font-extrabold text-slate-900 tracking-tight leading-[1.15] mb-6">
                Sở Hữu <span class="bg-gradient-to-r from-sky-500 to-blue-600 bg-clip-text text-transparent">Passport Điện Tử</span> <br>
                Tham Gia Ngày Hội Lớn
            </h1>
            <p class="text-base md:text-lg text-slate-500 max-w-xl mx-auto mb-8 leading-relaxed">
                Giải pháp thông minh, hiện đại dành cho Học sinh & Phụ huynh. <br>
                Đăng ký nhanh chóng để nhận Thư mời điện tử sang trọng được in tên cá nhân của riêng bạn.
            </p>
            <div class="flex justify-center gap-4 flex-wrap">
                <a href="register.php" class="inline-flex items-center justify-center gap-2 px-8 py-3.5 rounded-xl font-semibold text-base bg-gradient-to-r from-sky-500 to-blue-600 text-white shadow-lg shadow-sky-500/20 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-sky-500/25 hover:brightness-105 active:translate-y-0 transition-all duration-300">
                    <i class="fa-solid fa-pen-to-square"></i> Đăng Ký Nhận Thư Mời
                </a>
                <a href="#lookup" class="inline-flex items-center justify-center gap-2 px-8 py-3.5 rounded-xl font-semibold text-base bg-slate-100 border border-slate-200 text-slate-700 hover:bg-slate-200/80 hover:border-slate-300 active:bg-slate-100 transition-all duration-300">
                    <i class="fa-solid fa-magnifying-glass"></i> Tra Cứu Thư Mời Cũ
                </a>
            </div>
        </section>

        <!-- Search Section -->
        <section id="lookup" class="max-w-xl mx-auto mb-16 p-8 bg-white border border-slate-100 rounded-3xl shadow-xl shadow-slate-100 scroll-mt-24">
            <h3 class="text-center mb-6 text-xl font-extrabold text-slate-800 flex items-center justify-center gap-2">
                <i class="fa-solid fa-search bg-gradient-to-r from-sky-500 to-blue-600 bg-clip-text text-transparent"></i> Tra Cứu Thư Mời Của Bạn
            </h3>
            
            <?php if (!empty($search_error)): ?>
                <div class="bg-red-50 border border-red-200 text-red-600 p-4 rounded-2xl mb-6 text-sm flex items-center gap-3">
                    <i class="fa-solid fa-circle-exclamation text-lg"></i>
                    <span><?php echo htmlspecialchars($search_error); ?></span>
                </div>
            <?php endif; ?>

            <form action="index.php#lookup" method="POST">
                <div class="relative">
                    <input type="text" name="search_query" class="w-full bg-slate-50 border border-slate-200/80 rounded-2xl py-4 pl-5 pr-32 text-slate-900 placeholder-slate-400 focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-500/10 focus:bg-white transition-all duration-300" placeholder="Nhập SĐT hoặc mã Passport..." required>
                    <button type="submit" class="absolute right-2 top-2 bottom-2 px-5 bg-gradient-to-r from-sky-500 to-blue-600 text-white font-semibold text-sm rounded-xl flex items-center gap-2 shadow-md hover:brightness-105 active:scale-[0.98] transition-all">
                        <i class="fa-solid fa-search"></i> Tìm kiếm
                    </button>
                </div>
            </form>
            <p class="text-center text-slate-400 text-xs mt-4">
                * Nhập đúng số điện thoại đã sử dụng khi đăng ký để mở lại thư mời của bạn.
            </p>
        </section>

        <!-- Features/How it works Section -->
        <section class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-5xl mx-auto mb-12">
            <div class="p-8 bg-white border border-slate-100 rounded-3xl shadow-md hover:shadow-xl hover:-translate-y-1 transition-all duration-300 text-center">
                <div class="w-14 h-14 bg-sky-50 text-sky-500 rounded-2xl flex items-center justify-center text-2xl mx-auto mb-6 shadow-md shadow-sky-500/5">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800 mb-3">1. Đăng ký thông tin</h3>
                <p class="text-slate-500 text-sm leading-relaxed">
                    Điền thông tin cá nhân cơ bản cực kỳ nhanh chóng. Hệ thống sẽ tự động phân loại Học sinh và Phụ huynh để áp dụng màu sắc giao diện thư mời phù hợp.
                </p>
            </div>

            <div class="p-8 bg-white border border-slate-100 rounded-3xl shadow-md hover:shadow-xl hover:-translate-y-1 transition-all duration-300 text-center">
                <div class="w-14 h-14 bg-amber-50 text-amber-500 rounded-2xl flex items-center justify-center text-2xl mx-auto mb-6 shadow-md shadow-amber-500/5">
                    <i class="fa-solid fa-envelope-open-text"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800 mb-3">2. Nhận Thư Mời</h3>
                <p class="text-slate-500 text-sm leading-relaxed">
                    Nhận ngay Thư mời điện tử độc quyền được thiết kế sang trọng với tên của bạn. Thư mời được định dạng màu sắc chuẩn, phông chữ tinh tế và hài hòa.
                </p>
            </div>

            <div class="p-8 bg-white border border-slate-100 rounded-3xl shadow-md hover:shadow-xl hover:-translate-y-1 transition-all duration-300 text-center">
                <div class="w-14 h-14 bg-emerald-50 text-emerald-500 rounded-2xl flex items-center justify-center text-2xl mx-auto mb-6 shadow-md shadow-emerald-500/5">
                    <i class="fa-solid fa-download"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800 mb-3">3. Tải Thư Mời</h3>
                <p class="text-slate-500 text-sm leading-relaxed">
                    Tải Thư mời chất lượng cao (PNG) về thiết bị của bạn để lưu trữ, chia sẻ hoặc in ấn một cách dễ dàng chỉ với một cú click chuột.
                </p>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="text-center py-8 border-t border-slate-200/60 mt-auto text-slate-400 text-sm no-print">
        <p>&copy; 2026 Hệ thống Passport Điện Tử Học Đường. Phát triển bởi Antigravity AI.</p>
        <p class="text-[11px] mt-1 text-slate-300">Ứng dụng tối ưu hóa cho mọi thiết bị di động & nhà mạng viễn thông.</p>
    </footer>

    <!-- JS chính -->

</body>
</html>
