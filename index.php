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
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passport Điện Tử - Ngày Hội Trường Học</title>
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
            <a href="register.php" class="btn btn-primary"><i class="fa-solid fa-id-card"></i> Đăng ký ngay</a>
            <a href="admin_login.php" class="btn btn-secondary"><i class="fa-solid fa-user-shield"></i> Admin</a>
        </nav>
    </header>

    <!-- Main Content -->
    <main>
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-badge">
                <i class="fa-solid fa-sparkles"></i> Sự Kiện Đặc Biệt Trường Học 2026
            </div>
            <h1 class="hero-title">
                Sở Hữu <span class="text-gradient-student">Passport Điện Tử</span> <br>
                Tham Gia Ngày Hội Lớn
            </h1>
            <p class="hero-subtitle">
                Giải pháp thông minh, hiện đại dành cho Học sinh & Phụ huynh. <br>
                Đăng ký nhanh chóng để nhận Thư mời điện tử sang trọng và Lộ trình tham quan cá nhân hóa cho ngày hội.
            </p>
            <div class="hero-actions">
                <a href="register.php" class="btn btn-primary btn-accent-student" style="padding: 1rem 2rem; font-size: 1.1rem;">
                    <i class="fa-solid fa-pen-to-square"></i> Đăng Ký Thư Mời & Lộ Trình
                </a>
                <a href="#lookup" class="btn btn-secondary" style="padding: 1rem 2rem; font-size: 1.1rem;">
                    <i class="fa-solid fa-magnifying-glass"></i> Tra Cứu Thư Mời Cũ
                </a>
            </div>
        </section>

        <!-- Search Section -->
        <section id="lookup" class="glass-panel search-container" style="margin-bottom: 4rem; scroll-margin-top: 100px;">
            <h3 style="text-align: center; margin-bottom: 1.5rem; font-size: 1.5rem;">
                <i class="fa-solid fa-search text-gradient-student"></i> Tra Cứu Thư Mời Của Bạn
            </h3>
            
            <?php if (!empty($search_error)): ?>
                <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #f87171; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; font-size: 0.95rem; display: flex; align-items: center; gap: 0.8rem;">
                    <i class="fa-solid fa-circle-exclamation" style="font-size: 1.2rem;"></i>
                    <span><?php echo htmlspecialchars($search_error); ?></span>
                </div>
            <?php endif; ?>

            <form action="index.php#lookup" method="POST">
                <div style="position: relative;">
                    <input type="text" name="search_query" class="search-box" placeholder="Nhập số điện thoại hoặc mã Passport (ví dụ: PP-HS-12345)..." required>
                    <button type="submit" class="btn btn-primary search-btn">
                        <i class="fa-solid fa-search"></i> Tìm kiếm
                    </button>
                </div>
            </form>
            <p style="text-align: center; color: var(--text-muted); font-size: 0.85rem; margin-top: 1rem;">
                * Nhập đúng số điện thoại đã sử dụng khi đăng ký để mở lại thư mời của bạn.
            </p>
        </section>

        <!-- Features/How it works Section -->
        <section style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
            <div class="glass-panel glass-card-hover" style="text-align: center; padding: 2.5rem 2rem;">
                <div style="width: 60px; height: 60px; background: rgba(0, 240, 255, 0.1); color: var(--student-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 1.5rem auto; box-shadow: 0 0 15px rgba(0,240,255,0.15);">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <h3 style="margin-bottom: 1rem;">1. Đăng ký thông tin</h3>
                <p style="color: var(--text-muted); font-size: 0.95rem;">
                    Điền thông tin cá nhân cơ bản cực kỳ nhanh chóng. Hệ thống sẽ tự động phân loại Học sinh và Phụ huynh để áp dụng lộ trình tham quan riêng biệt.
                </p>
            </div>

            <div class="glass-panel glass-card-hover" style="text-align: center; padding: 2.5rem 2rem;">
                <div style="width: 60px; height: 60px; background: rgba(255, 170, 0, 0.1); color: var(--parent-primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 1.5rem auto; box-shadow: 0 0 15px rgba(255,170,0,0.15);">
                    <i class="fa-solid fa-envelope-open-text"></i>
                </div>
                <h3 style="margin-bottom: 1rem;">2. Nhận Thư Mời</h3>
                <p style="color: var(--text-muted); font-size: 0.95rem;">
                    Nhận ngay Thư mời điện tử độc quyền được thiết kế sang trọng với tên của bạn. Thư mời được định dạng màu sắc chuẩn, phông chữ tinh tế và hài hòa.
                </p>
            </div>

            <div class="glass-panel glass-card-hover" style="text-align: center; padding: 2.5rem 2rem;">
                <div style="width: 60px; height: 60px; background: rgba(16, 185, 129, 0.1); color: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 1.5rem auto; box-shadow: 0 0 15px rgba(16,185,129,0.15);">
                    <i class="fa-solid fa-map-location-dot"></i>
                </div>
                <h3 style="margin-bottom: 1rem;">3. Xem Lộ Trình & Tải Về</h3>
                <p style="color: var(--text-muted); font-size: 0.95rem;">
                    Xem lộ trình trải nghiệm chi tiết được thiết kế riêng. Tải Thư mời chất lượng cao (PNG, PDF) về thiết bị để lưu trữ hoặc in ấn dễ dàng.
                </p>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2026 Hệ thống Passport Điện Tử Học Đường. Phát triển bởi Antigravity AI.</p>
        <p style="font-size: 0.8rem; margin-top: 0.5rem; color: rgba(255,255,255,0.2);">Ứng dụng tối ưu hóa cho mọi thiết bị di động & nhà mạng viễn thông.</p>
    </footer>

    <!-- JS chính -->
    <script src="assets/js/main.js"></script>
</body>
</html>
