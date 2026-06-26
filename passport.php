<?php
/**
 * QRvaPassPort - Personal Passport, Invitation & Route View
 * Antigravity - Premium AI Developer
 */
require_once 'config.php';

// Lấy mã Passport từ URL
$code = $_GET['code'] ?? '';

if (empty($code)) {
    header("Location: index.php");
    exit;
}

// Truy vấn thông tin Passport từ database
$stmt = $pdo->prepare("SELECT * FROM `passports` WHERE `passport_code` = ? LIMIT 1");
$stmt->execute([$code]);
$passport = $stmt->fetch();

if (!$passport) {
    // Không tìm thấy Passport
    $error_title = "Không Tìm Thấy Passport";
    $error_msg = "Mã Passport <strong>" . htmlspecialchars($code) . "</strong> không tồn tại trên hệ thống. Vui lòng kiểm tra lại!";
} else {
    // Tìm thấy Passport
    $fullname = $passport['fullname'];
    $role = $passport['role'];
    $student_class = $passport['student_class'];
    $student_name = $passport['student_name'];
    $phone = $passport['phone'];
    $avatar = $passport['avatar'];
    $created_at = date('d/m/Y H:i', strtotime($passport['created_at']));
    
    // Phân loại nhãn vai trò và màu sắc
    $role_label = ($role === 'student') ? 'Học Sinh' : 'Phụ Huynh';
    $theme_class = ($role === 'student') ? 'student' : 'parent';
    
    // Xác định mẫu lộ trình tương ứng
    $route_image = ($role === 'student') ? 'anh/lotrinhsinhvien-final.png' : 'anh/lotrinhphuhuynh-final.png';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passport & Vé Sự Kiện - <?php echo htmlspecialchars($fullname ?? 'Lỗi'); ?></title>
    <!-- CSS chính -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <!-- Header -->
    <header class="no-print">
        <a href="index.php" class="logo-container">
            <div class="logo-icon">P</div>
            <div>
                <div class="logo-text">PASSPORT</div>
                <div class="logo-sub">Điện Tử Học Đường</div>
            </div>
        </a>
        <nav>
            <a href="index.php" class="btn btn-secondary"><i class="fa-solid fa-house"></i> Trang chủ</a>
            <a href="register.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Đăng ký mới</a>
        </nav>
    </header>

    <!-- Main Content -->
    <main>
        <?php if (isset($error_msg)): ?>
            <!-- Màn hình lỗi không tìm thấy -->
            <div class="glass-panel" style="max-width: 500px; margin: 4rem auto; text-align: center; padding: 3rem 2rem;">
                <div style="width: 70px; height: 70px; background: rgba(239, 68, 68, 0.1); color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.2rem; margin: 0 auto 1.5rem auto;">
                    <i class="fa-solid fa-circle-exclamation"></i>
                </div>
                <h2><?php echo $error_title; ?></h2>
                <p style="color: var(--text-muted); margin: 1rem 0 2rem 0;"><?php echo $error_msg; ?></p>
                <a href="index.php" class="btn btn-primary"><i class="fa-solid fa-house"></i> Về Trang Chủ</a>
            </div>
        <?php else: ?>
            
            <!-- Màn hình hiển thị Passport & Vé mời thành công -->
            <div class="passport-layout">
                
                <!-- BÊN TRÁI: KHU VỰC HIỂN THỊ THƯ MỜI & VÉ LỘ TRÌNH -->
                <div class="passport-viewer">
                    
                    <!-- KHUNG CHỨA THẺ PASSPORT -->
                    <div class="passport-card-wrapper" id="alignable-wrapper">
                        
                        <!-- TẤM VÉ 1: THƯ MỜI THAM DỰ (Tương ứng file final-thumoi.png) -->
                        <div id="theme-thumoi" class="ticket-card-container ticket-card-container-<?php echo $theme_class; ?>">
                            <!-- Ảnh mẫu gốc từ thư mục anh -->
                            <img class="ticket-template-img" src="anh/final-thumoi.png" alt="Vé Thư Mời" onerror="this.src='uploads/default.png'; alert('Không tìm thấy tệp anh/final-thumoi.png! Hệ thống sẽ dùng ảnh mặc định thay thế.');">
                            
                            <!-- Họ tên đè lên (Overlay) - Mặc định màu trắng, tự căn giữa đẹp mắt -->
                            <div class="overlay-element overlay-name-thumoi" id="overlay-tm-name" style="color: #ffffff; top: 52%; left: 50%; font-size: 24px;">
                                <?php echo htmlspecialchars($fullname); ?>
                            </div>
                        </div>

                        <!-- Dòng chữ phân tách: Vé của bạn là: -->
                        <div class="ticket-separator no-print" style="text-align: center; margin: 2rem 0; font-size: 1.2rem; font-weight: bold; color: var(--text-white); display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                            <i class="fa-solid fa-ticket" style="color: var(--<?php echo $theme_class; ?>-primary); font-size: 1.3rem;"></i> 
                            <span>Vé của bạn là:</span>
                        </div>

                        <!-- TẤM VÉ 2: LỘ TRÌNH THAM QUAN (Lọc theo vai trò HS/PH) - KHÔNG hiện tên, KHÔNG cho chỉnh -->
                        <div id="theme-lotrinh" class="ticket-card-container ticket-card-container-<?php echo $theme_class; ?>">
                            <!-- Lộ trình tương ứng cho học sinh hoặc phụ huynh -->
                            <img class="ticket-template-img" src="<?php echo htmlspecialchars($route_image); ?>" alt="Lộ Trình Tham Quan" onerror="this.src='uploads/default.png'; alert('Không tìm thấy tệp lộ trình tương ứng trong thư mục anh!');">
                        </div>

                    </div>
                </div>

                </div>

                    <!-- BÊN PHẢI: THÔNG TIN CHI TIẾT VÀ NÚT TẢI VỀ -->
                <div class="action-card glass-panel">
                    
                    <!-- Hiển thị thông báo nếu vừa đăng ký xong -->
                    <?php if (isset($_GET['new']) && $_GET['new'] == 1): ?>
                        <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); color: #34d399; padding: 1.2rem; border-radius: 16px; text-align: center; margin-bottom: 1.5rem;">
                            <div style="font-size: 2rem; margin-bottom: 0.5rem;"><i class="fa-solid fa-circle-check"></i></div>
                            <h4 style="font-size: 1.1rem; margin-bottom: 0.2rem;">Đăng Ký Thành Công!</h4>
                            <p style="font-size: 0.8rem; color: var(--text-muted);">Passport và thư mời của bạn đã được khởi tạo và lưu trữ.</p>
                        </div>
                    <?php endif; ?>
 
                    <h3><i class="fa-solid fa-circle-info text-gradient-<?php echo $theme_class; ?>"></i> Thông Tin Passport</h3>
                    
                    <ul class="meta-info-list" style="margin-bottom: 1.5rem;">
                        <li class="meta-info-item">
                            <span>Mã Passport:</span>
                            <strong style="font-family: monospace; letter-spacing: 1.5px; color: var(--<?php echo $theme_class; ?>-primary);"><?php echo htmlspecialchars($passport['passport_code']); ?></strong>
                        </li>
                        <li class="meta-info-item">
                            <span>Họ và Tên:</span>
                            <strong><?php echo htmlspecialchars($fullname); ?></strong>
                        </li>
                        <li class="meta-info-item">
                            <span>Đối tượng:</span>
                            <strong><?php echo $role_label; ?></strong>
                        </li>
                        <?php if ($role === 'parent'): ?>
                            <li class="meta-info-item">
                                <span>Họ tên con:</span>
                                <strong><?php echo htmlspecialchars($student_name); ?></strong>
                            </li>
                        <?php endif; ?>
                        <li class="meta-info-item">
                            <span>Số điện thoại:</span>
                            <strong><?php echo htmlspecialchars($phone); ?></strong>
                        </li>
                    </ul>
 
                    <!-- PHẦN ĐỀ XUẤT VÉ THAM QUAN CHO PHÙ HỢP (PERSONALIZED RECOMMENDATION) -->
                    <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid var(--border-color); padding: 1.2rem; border-radius: 16px; margin-bottom: 1.5rem;">
                        <h4 style="font-size: 0.95rem; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem; color: var(--<?php echo $theme_class; ?>-primary);">
                            <?php if ($role === 'student'): ?>
                                <i class="fa-solid fa-graduation-cap"></i> 🗺️ Lộ Trình Học Sinh Khuyên Dùng
                            <?php else: ?>
                                <i class="fa-solid fa-user-group"></i> 🗺️ Lộ Trình Phụ Huynh Khuyên Dùng
                            <?php endif; ?>
                        </h4>
                        <p style="font-size: 0.85rem; line-height: 1.5; color: #cbd5e1;">
                            <?php if ($role === 'student'): ?>
                                Chào em học sinh! Hãy hoàn thành các hoạt động tuyệt vời tại: <br>
                                <strong>Khu gian hàng STEM 🔬 &rarr; Thử thách Thể thao vận động ⚽ &rarr; Sân khấu Lễ hội 🎤 &rarr; Nhận quà lưu niệm tại quầy Check-in chính.</strong>
                            <?php else: ?>
                                Chào quý phụ huynh! Hệ thống đề xuất lộ trình trải nghiệm bổ ích và thư giãn: <br>
                                <strong>Triển lãm Sáng tạo của học sinh 🎨 &rarr; Hội thảo Định hướng & Chia sẻ 💼 &rarr; Trà đạo giáo viên thư giãn 🍵 &rarr; Đồng hành cùng con tại khu Trò chơi gia đình.</strong>
                            <?php endif; ?>
                        </p>
                    </div>
 
                    <!-- Nút thao tác -->
                    <div style="display: flex; flex-direction: column; gap: 0.8rem;" class="no-print">
                        <button onclick="downloadPNG()" class="btn btn-primary btn-accent-<?php echo $theme_class; ?>" id="btn-download-png">
                            <i class="fa-solid fa-file-image"></i> Tải ảnh Thư Mời (PNG)
                        </button>
                        <button onclick="downloadPDF()" class="btn btn-secondary">
                            <i class="fa-solid fa-file-pdf"></i> Tải file PDF Thư Mời
                        </button>
                        <button onclick="window.print()" class="btn btn-secondary">
                            <i class="fa-solid fa-print"></i> In trực tiếp qua trình duyệt
                        </button>
                    </div>
                </div>

            </div>
            
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="no-print">
        <p>&copy; 2026 Hệ thống Passport Điện Tử Học Đường. Phát triển bởi Antigravity AI.</p>
    </footer>

    <!-- Tải các thư viện JS Offline đã tải về -->
    <script src="assets/js/qrcode.min.js"></script>
    <script src="assets/js/html2canvas.min.js"></script>
    <script src="assets/js/jspdf.umd.min.js"></script>

    <?php if (!isset($error_msg)): ?>
    <!-- Script xử lý logic tại trang Passport -->
    <script>
        // TẢI ẢNH PNG (Chỉ tải ảnh Thư Mời theo yêu cầu)
        function downloadPNG() {
            const invitationCard = document.getElementById('theme-thumoi');
            const btn = document.getElementById('btn-download-png');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang xuất ảnh...';
            btn.disabled = true;

            html2canvas(invitationCard, {
                scale: 3, // Xuất độ nét siêu cao để in ấn không bị vỡ chữ
                useCORS: true,
                backgroundColor: null,
                logging: false
            }).then(canvas => {
                const link = document.createElement('a');
                link.download = 'ThuMoi_' + '<?php echo $passport["passport_code"]; ?>' + '.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
                
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            }).catch(err => {
                alert('Có lỗi xảy ra khi xuất ảnh! Vui lòng thử lại.');
                btn.innerHTML = originalHTML;
                btn.disabled = false;
                console.error(err);
            });
        }

        // TẢI FILE PDF (Chỉ tải Thư Mời)
        function downloadPDF() {
            const invitationCard = document.getElementById('theme-thumoi');
            const btn = document.querySelector('button[onclick="downloadPDF()"]');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang xuất PDF...';
            btn.disabled = true;

            html2canvas(invitationCard, {
                scale: 2.5,
                useCORS: true,
                logging: false
            }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const { jsPDF } = window.jspdf;
                
                // Đo kích thước thẻ thực tế
                const width = invitationCard.offsetWidth;
                const height = invitationCard.offsetHeight;
                
                // Căn hướng trang
                const orientation = width > height ? 'l' : 'p';
                
                // Khởi tạo PDF có kích thước vừa khít
                const pdf = new jsPDF(orientation, 'px', [width, height]);
                pdf.addImage(imgData, 'PNG', 0, 0, width, height);
                pdf.save('ThuMoi_' + '<?php echo $passport["passport_code"]; ?>' + '.pdf');
                
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            }).catch(err => {
                alert('Có lỗi xảy ra khi xuất file PDF! Vui lòng thử lại.');
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            });
        }
    </script>
    <?php endif; ?>
</body>
</html>
