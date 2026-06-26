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
                
                <!-- BÊN TRÁI: KHU VỰC CHỌN TẤM VÉ VÀ HIỂN THỊ -->
                <div class="passport-viewer">
                    
                    <!-- Bộ chọn giữa Thư mời và Lộ trình (Tab Selector) -->
                    <div class="theme-selector-panel no-print">
                        <button class="theme-tab active-<?php echo $theme_class; ?>" onclick="switchTheme('thumoi', this)">
                            <i class="fa-solid fa-envelope-open-text"></i> ✉️ Vé Thư Mời
                        </button>
                        <button class="theme-tab" onclick="switchTheme('lotrinh', this)">
                            <i class="fa-solid fa-map-location-dot"></i> 🗺️ Lộ Trình Tham Quan
                        </button>
                    </div>

                    <!-- KHUNG CHỨA THẺ PASSPORT HOẠT ĐỘNG (Dùng biến CSS điều khiển vị trí) -->
                    <div class="passport-card-wrapper" id="alignable-wrapper">
                        
                        <!-- TẤM VÉ 1: THƯ MỜI THAM DỰ (Tương ứng file final-thumoi.png) -->
                        <div id="theme-thumoi" class="ticket-card-container ticket-card-container-<?php echo $theme_class; ?> passport-card-active">
                            <!-- Ảnh mẫu gốc từ thư mục anh -->
                            <img class="ticket-template-img" src="anh/final-thumoi.png" alt="Vé Thư Mời" onerror="this.src='uploads/default.png'; alert('Không tìm thấy tệp anh/final-thumoi.png! Hệ thống sẽ dùng ảnh mặc định thay thế.');">
                            
                            <!-- Họ tên đè lên (Overlay) -->
                            <div class="overlay-element overlay-name-thumoi" id="overlay-tm-name">
                                <?php echo htmlspecialchars($fullname); ?>
                            </div>
                            
                            <!-- Mã QR Code đè lên (Overlay) -->
                            <div class="overlay-element overlay-qr-thumoi" id="overlay-tm-qr">
                                <!-- Chứa mã QR cá nhân sinh động -->
                            </div>
                        </div>

                        <!-- TẤM VÉ 2: LỘ TRÌNH THAM QUAN (Lọc theo vai trò HS/PH) -->
                        <div id="theme-lotrinh" class="ticket-card-container ticket-card-container-<?php echo $theme_class; ?>" style="display: none;">
                            <!-- Lộ trình tương ứng cho học sinh hoặc phụ huynh -->
                            <img class="ticket-template-img" src="<?php echo htmlspecialchars($route_image); ?>" alt="Lộ Trình Tham Quan" onerror="this.src='uploads/default.png'; alert('Không tìm thấy tệp lộ trình tương ứng trong thư mục anh!');">
                            
                            <!-- Họ tên đè lên Lộ trình -->
                            <div class="overlay-element overlay-name-lotrinh" id="overlay-lt-name">
                                <?php echo htmlspecialchars($fullname); ?>
                            </div>
                        </div>

                    </div>

                    <!-- BẢNG ĐIỀU KHIỂN CĂN CHỈNH TỌA ĐỘ (CHỈ HIỂN THỊ TRÊN TRÌNH DUYỆT) -->
                    <div class="alignment-panel no-print">
                        <h4><i class="fa-solid fa-sliders-h" style="color: var(--<?php echo $theme_class; ?>-primary);"></i> Căn Chỉnh Vị Trí Chữ & QR Trên Thẻ (Dành cho BTC)</h4>
                        <p style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 1rem;">
                            Kéo các thanh trượt để di chuyển Họ tên và Mã QR vào đúng ô trống trên ảnh mẫu của bạn. Hệ thống tự động lưu vị trí này cho các lần in sau!
                        </p>

                        <!-- Căn chỉnh cho Vé Thư Mời -->
                        <div class="control-group" id="control-group-thumoi">
                            <div class="control-group-title"><i class="fa-solid fa-envelope"></i> Cài đặt Vé Thư Mời</div>
                            
                            <!-- Họ tên Y -->
                            <div class="slider-row">
                                <label>Họ tên (Dọc):</label>
                                <input type="range" id="slider-tm-name-top" min="0" max="100" value="52" oninput="updateAlignment()">
                                <span id="val-tm-name-top">52%</span>
                            </div>
                            <!-- Họ tên X -->
                            <div class="slider-row">
                                <label>Họ tên (Ngang):</label>
                                <input type="range" id="slider-tm-name-left" min="0" max="100" value="50" oninput="updateAlignment()">
                                <span id="val-tm-name-left">50%</span>
                            </div>
                            <!-- Họ tên Size -->
                            <div class="slider-row">
                                <label>Cỡ chữ:</label>
                                <input type="range" id="slider-tm-name-size" min="10" max="60" value="24" oninput="updateAlignment()">
                                <span id="val-tm-name-size">24px</span>
                            </div>
                            <!-- Họ tên Color -->
                            <div class="color-picker-row">
                                <label><i class="fa-solid fa-palette"></i> Màu sắc Họ Tên:</label>
                                <input type="color" id="color-tm-name" class="color-picker-input" value="#ffaa00" onchange="updateAlignment()">
                            </div>

                            <hr style="border: none; border-top: 1px solid rgba(255,255,255,0.05); margin: 0.8rem 0;">

                            <!-- QR Y -->
                            <div class="slider-row">
                                <label>Mã QR (Dọc):</label>
                                <input type="range" id="slider-tm-qr-top" min="0" max="100" value="75" oninput="updateAlignment()">
                                <span id="val-tm-qr-top">75%</span>
                            </div>
                            <!-- QR X -->
                            <div class="slider-row">
                                <label>Mã QR (Ngang):</label>
                                <input type="range" id="slider-tm-qr-left" min="0" max="100" value="50" oninput="updateAlignment()">
                                <span id="val-tm-qr-left">50%</span>
                            </div>
                            <!-- QR Size -->
                            <div class="slider-row">
                                <label>Cỡ ảnh QR:</label>
                                <input type="range" id="slider-tm-qr-size" min="30" max="200" value="90" oninput="updateAlignment()">
                                <span id="val-tm-qr-size">90px</span>
                            </div>
                        </div>

                        <!-- Căn chỉnh cho Lộ Trình -->
                        <div class="control-group" id="control-group-lotrinh" style="display: none;">
                            <div class="control-group-title"><i class="fa-solid fa-map"></i> Cài đặt Lộ Trình Tham Quan</div>
                            
                            <!-- Họ tên Y -->
                            <div class="slider-row">
                                <label>Họ tên (Dọc):</label>
                                <input type="range" id="slider-lt-name-top" min="0" max="100" value="15" oninput="updateAlignment()">
                                <span id="val-lt-name-top">15%</span>
                            </div>
                            <!-- Họ tên X -->
                            <div class="slider-row">
                                <label>Họ tên (Ngang):</label>
                                <input type="range" id="slider-lt-name-left" min="0" max="100" value="50" oninput="updateAlignment()">
                                <span id="val-lt-name-left">50%</span>
                            </div>
                            <!-- Họ tên Size -->
                            <div class="slider-row">
                                <label>Cỡ chữ:</label>
                                <input type="range" id="slider-lt-name-size" min="10" max="60" value="24" oninput="updateAlignment()">
                                <span id="val-lt-name-size">24px</span>
                            </div>
                            <!-- Họ tên Color -->
                            <div class="color-picker-row">
                                <label><i class="fa-solid fa-palette"></i> Màu sắc Họ Tên:</label>
                                <input type="color" id="color-lt-name" class="color-picker-input" value="#ffffff" onchange="updateAlignment()">
                            </div>
                        </div>

                        <div style="text-align: right; margin-top: 1rem;">
                            <button onclick="resetAlignment()" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.8rem; background: rgba(239, 68, 68, 0.1); color: #f87171; border-color: rgba(239, 68, 68, 0.2);">
                                <i class="fa-solid fa-rotate-left"></i> Khôi phục vị trí mặc định
                            </button>
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
                            <span>Lớp học:</span>
                            <strong><?php echo htmlspecialchars($student_class); ?></strong>
                        </li>
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
                            <i class="fa-solid fa-file-image"></i> Tải ảnh Vé Đang Xem (PNG)
                        </button>
                        <button onclick="downloadPDF()" class="btn btn-secondary">
                            <i class="fa-solid fa-file-pdf"></i> Tải file PDF Vé Đang Xem
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
        // Lấy URL hiện tại để gán vào QR code động
        const qrUrl = "<?php echo getBaseUrl() . 'passport.php?code=' . urlencode($passport['passport_code']); ?>";
        const roleTheme = "<?php echo $theme_class; ?>";

        // Khởi tạo mã QR cho Vé Thư Mời (90x90)
        window.addEventListener('load', () => {
            new QRCode(document.getElementById("overlay-tm-qr"), {
                text: qrUrl,
                width: 90,
                height: 90,
                colorDark : "#000000",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.H
            });
            
            // Tải cấu hình căn chỉnh từ localStorage nếu có
            loadAlignmentSettings();
        });

        // Hàm chuyển đổi giao diện Thẻ động
        function switchTheme(themeName, tabElement) {
            // 1. Ẩn toàn bộ thẻ passport
            const cards = ['thumoi', 'lotrinh'];
            cards.forEach(t => {
                const cardEl = document.getElementById('theme-' + t);
                cardEl.style.display = 'none';
                cardEl.classList.remove('passport-card-active');
            });

            // 2. Hiển thị thẻ được chọn
            const activeCard = document.getElementById('theme-' + themeName);
            activeCard.style.display = 'block';
            activeCard.classList.add('passport-card-active');

            // 3. Cập nhật trạng thái Active trên nút chuyển
            const tabs = document.querySelectorAll('.theme-tab');
            tabs.forEach(tab => {
                tab.classList.remove('active-student');
                tab.classList.remove('active-parent');
            });
            tabElement.classList.add('active-' + roleTheme);

            // 4. Hiển thị bảng điều khiển tương ứng với thẻ đang xem
            if (themeName === 'thumoi') {
                document.getElementById('control-group-thumoi').style.display = 'block';
                document.getElementById('control-group-lotrinh').style.display = 'none';
            } else {
                document.getElementById('control-group-thumoi').style.display = 'none';
                document.getElementById('control-group-lotrinh').style.display = 'block';
            }
        }

        // ----------------------------------------------------
        // LOGIC ĐIỀU KHIỂN CĂN CHỈNH VÀ LƯU TRỮ TỌA ĐỘ
        const defaultSettings = {
            tmNameTop: 52,
            tmNameLeft: 50,
            tmNameSize: 24,
            tmNameColor: '#ffaa00',
            
            tmQrTop: 75,
            tmQrLeft: 50,
            tmQrSize: 90,
            
            ltNameTop: 15,
            ltNameLeft: 50,
            ltNameSize: 24,
            ltNameColor: '#ffffff'
        };

        function updateAlignment() {
            const wrapper = document.getElementById('alignable-wrapper');
            
            // Lấy giá trị từ các thanh trượt
            const tmNameTop = document.getElementById('slider-tm-name-top').value;
            const tmNameLeft = document.getElementById('slider-tm-name-left').value;
            const tmNameSize = document.getElementById('slider-tm-name-size').value;
            const tmNameColor = document.getElementById('color-tm-name').value;

            const tmQrTop = document.getElementById('slider-tm-qr-top').value;
            const tmQrLeft = document.getElementById('slider-tm-qr-left').value;
            const tmQrSize = document.getElementById('slider-tm-qr-size').value;

            const ltNameTop = document.getElementById('slider-lt-name-top').value;
            const ltNameLeft = document.getElementById('slider-lt-name-left').value;
            const ltNameSize = document.getElementById('slider-lt-name-size').value;
            const ltNameColor = document.getElementById('color-lt-name').value;

            // Hiển thị số liệu kế bên slider
            document.getElementById('val-tm-name-top').textContent = tmNameTop + '%';
            document.getElementById('val-tm-name-left').textContent = tmNameLeft + '%';
            document.getElementById('val-tm-name-size').textContent = tmNameSize + 'px';

            document.getElementById('val-tm-qr-top').textContent = tmQrTop + '%';
            document.getElementById('val-tm-qr-left').textContent = tmQrLeft + '%';
            document.getElementById('val-tm-qr-size').textContent = tmQrSize + 'px';

            document.getElementById('val-lt-name-top').textContent = ltNameTop + '%';
            document.getElementById('val-lt-name-left').textContent = ltNameLeft + '%';
            document.getElementById('val-lt-name-size').textContent = ltNameSize + 'px';

            // Áp dụng biến CSS lên wrapper
            wrapper.style.setProperty('--tm-name-top', tmNameTop + '%');
            wrapper.style.setProperty('--tm-name-left', tmNameLeft + '%');
            wrapper.style.setProperty('--tm-name-size', tmNameSize + 'px');
            wrapper.style.setProperty('--tm-name-color', tmNameColor);

            wrapper.style.setProperty('--tm-qr-top', tmQrTop + '%');
            wrapper.style.setProperty('--tm-qr-left', tmQrLeft + '%');
            wrapper.style.setProperty('--tm-qr-size', tmQrSize + 'px');

            wrapper.style.setProperty('--lt-name-top', ltNameTop + '%');
            wrapper.style.setProperty('--lt-name-left', ltNameLeft + '%');
            wrapper.style.setProperty('--lt-name-size', ltNameSize + 'px');
            wrapper.style.setProperty('--lt-name-color', ltNameColor);

            // Lưu vào localStorage
            const settings = {
                tmNameTop, tmNameLeft, tmNameSize, tmNameColor,
                tmQrTop, tmQrLeft, tmQrSize,
                ltNameTop, ltNameLeft, ltNameSize, ltNameColor
            };
            localStorage.setItem('qrvapassport_align_settings', JSON.stringify(settings));
        }

        function loadAlignmentSettings() {
            const saved = localStorage.getItem('qrvapassport_align_settings');
            const settings = saved ? JSON.parse(saved) : defaultSettings;

            // Đổ dữ liệu vào các controls
            document.getElementById('slider-tm-name-top').value = settings.tmNameTop ?? defaultSettings.tmNameTop;
            document.getElementById('slider-tm-name-left').value = settings.tmNameLeft ?? defaultSettings.tmNameLeft;
            document.getElementById('slider-tm-name-size').value = settings.tmNameSize ?? defaultSettings.tmNameSize;
            document.getElementById('color-tm-name').value = settings.tmNameColor ?? defaultSettings.tmNameColor;

            document.getElementById('slider-tm-qr-top').value = settings.tmQrTop ?? defaultSettings.tmQrTop;
            document.getElementById('slider-tm-qr-left').value = settings.tmQrLeft ?? defaultSettings.tmQrLeft;
            document.getElementById('slider-tm-qr-size').value = settings.tmQrSize ?? defaultSettings.tmQrSize;

            document.getElementById('slider-lt-name-top').value = settings.ltNameTop ?? defaultSettings.ltNameTop;
            document.getElementById('slider-lt-name-left').value = settings.ltNameLeft ?? defaultSettings.ltNameLeft;
            document.getElementById('slider-lt-name-size').value = settings.ltNameSize ?? defaultSettings.ltNameSize;
            document.getElementById('color-lt-name').value = settings.ltNameColor ?? defaultSettings.ltNameColor;

            // Kích hoạt hàm cập nhật
            updateAlignment();
        }

        function resetAlignment() {
            if (confirm('Bạn có chắc chắn muốn khôi phục lại vị trí căn chỉnh mặc định ban đầu không?')) {
                localStorage.removeItem('qrvapassport_align_settings');
                loadAlignmentSettings();
            }
        }

        // ----------------------------------------------------
        // XUẤT PHÁT FILE ẢNH VÀ FILE PDF
        
        // TẢI ẢNH PNG
        function downloadPNG() {
            const activeCard = document.querySelector('.passport-card-active');
            const btn = document.getElementById('btn-download-png');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang xuất ảnh...';
            btn.disabled = true;

            html2canvas(activeCard, {
                scale: 3, // Xuất độ nét siêu cao để in ấn không bị vỡ chữ
                useCORS: true,
                backgroundColor: null,
                logging: false
            }).then(canvas => {
                const link = document.createElement('a');
                link.download = 'Ve_' + '<?php echo $passport["passport_code"]; ?>' + '.png';
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

        // TẢI FILE PDF (Chuẩn kích thước vừa vặn cho thẻ in)
        function downloadPDF() {
            const activeCard = document.querySelector('.passport-card-active');
            const btn = document.querySelector('button[onclick="downloadPDF()"]');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang xuất PDF...';
            btn.disabled = true;

            html2canvas(activeCard, {
                scale: 2.5,
                useCORS: true,
                logging: false
            }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const { jsPDF } = window.jspdf;
                
                // Đo kích thước thẻ thực tế
                const width = activeCard.offsetWidth;
                const height = activeCard.offsetHeight;
                
                // Căn hướng trang: 'l' cho ngang, 'p' cho dọc
                const orientation = width > height ? 'l' : 'p';
                
                // Khởi tạo PDF có kích thước vừa khít bằng kích thước của thẻ
                const pdf = new jsPDF(orientation, 'px', [width, height]);
                pdf.addImage(imgData, 'PNG', 0, 0, width, height);
                pdf.save('Ve_' + '<?php echo $passport["passport_code"]; ?>' + '.pdf');
                
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            }).catch(err => {
                alert('Có lỗi xảy ra khi xuất file PDF! Vui lòng thử lại.');
                btn.innerHTML = originalHTML;
                btn.disabled = false;
                console.error(err);
            });
        }
    </script>
    <?php endif; ?>
</body>
</html>
