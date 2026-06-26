<?php
/**
 * QRvaPassPort - Personal Passport View & Export
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
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passport Cá Nhân - <?php echo htmlspecialchars($fullname ?? 'Lỗi'); ?></title>
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
            
            <!-- Màn hình hiển thị Passport thành công -->
            <div class="passport-layout">
                
                <!-- BÊN TRÁI: KHU VỰC ĐỔI CHỦ ĐỀ VÀ HIỂN THỊ THẺ -->
                <div class="passport-viewer">
                    
                    <!-- Bộ chọn chủ đề (Theme Selector) -->
                    <div class="theme-selector-panel no-print">
                        <button class="theme-tab active-<?php echo $theme_class; ?>" onclick="switchTheme('badge', this)">
                            <i class="fa-solid fa-address-card"></i> Thẻ Đeo Sự Kiện
                        </button>
                        <button class="theme-tab" onclick="switchTheme('boarding', this)">
                            <i class="fa-solid fa-ticket-simple"></i> Vé Máy Bay (Boarding)
                        </button>
                        <button class="theme-tab" onclick="switchTheme('ticket', this)">
                            <i class="fa-solid fa-mask"></i> Vé Sự Kiện (Ticket)
                        </button>
                        <button class="theme-tab" onclick="switchTheme('classic', this)">
                            <i class="fa-solid fa-credit-card"></i> Thẻ VIP (Classic)
                        </button>
                    </div>

                    <!-- KHUNG CHỨA THẺ PASSPORT -->
                    <div class="passport-card-wrapper">
                        
                        <!-- CHỦ ĐỀ 1: THẺ ĐEO SỰ KIỆN (FESTIVAL BADGE) - DỌC -->
                        <div id="theme-badge" class="card-badge card-badge-<?php echo $theme_class; ?> passport-card-active">
                            <div class="badge-header">
                                <div class="badge-logo badge-logo-<?php echo $theme_class; ?>">
                                    <i class="fa-solid fa-award"></i> THPT PHÙNG HƯNG
                                </div>
                                <div class="badge-title">PASSPORT SỰ KIỆN 2026</div>
                            </div>

                            <div class="badge-avatar-container">
                                <img class="badge-avatar" src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar">
                            </div>

                            <div class="badge-info">
                                <div class="badge-name"><?php echo htmlspecialchars($fullname); ?></div>
                                <div class="badge-role-tag"><?php echo $role_label; ?></div>
                                
                                <div class="badge-details">
                                    <div class="badge-detail-item">
                                        <span>Lớp Học</span>
                                        <strong><?php echo htmlspecialchars($student_class); ?></strong>
                                    </div>
                                    <?php if ($role === 'parent'): ?>
                                        <div class="badge-detail-item">
                                            <span>Phụ huynh của</span>
                                            <strong><?php echo htmlspecialchars($student_name); ?></strong>
                                        </div>
                                    <?php endif; ?>
                                    <div class="badge-detail-item">
                                        <span>Liên hệ</span>
                                        <strong><?php echo htmlspecialchars($phone); ?></strong>
                                    </div>
                                </div>
                            </div>

                            <!-- QR Code Slot -->
                            <div class="badge-qr-box" id="qr-badge"></div>
                            
                            <div class="badge-footer-text">
                                Vui lòng xuất trình thẻ khi tham gia sự kiện
                            </div>
                        </div>

                        <!-- CHỦ ĐỀ 2: VÉ MÁY BAY (BOARDING PASS) - NGANG (Ẩn mặc định) -->
                        <div id="theme-boarding" class="card-boarding card-boarding-<?php echo $theme_class; ?>" style="display: none;">
                            <!-- Phần thân chính (Main Pass) -->
                            <div class="boarding-main">
                                <div class="boarding-header">
                                    <div class="boarding-logo"><i class="fa-solid fa-plane"></i> PHUNG HƯNG AIR</div>
                                    <div class="boarding-pass-title">BOARDING PASS</div>
                                </div>
                                
                                <div class="boarding-body">
                                    <img class="boarding-avatar" src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar">
                                    <div class="boarding-info">
                                        <div class="boarding-name"><?php echo htmlspecialchars($fullname); ?></div>
                                        <div class="boarding-fields">
                                            <div class="boarding-field">
                                                <span>Vai Trò / Role</span>
                                                <strong><?php echo $role_label; ?></strong>
                                            </div>
                                            <div class="boarding-field">
                                                <span>Lớp / Class</span>
                                                <strong><?php echo htmlspecialchars($student_class); ?></strong>
                                            </div>
                                            <?php if ($role === 'parent'): ?>
                                                <div class="boarding-field" style="grid-column: span 2;">
                                                    <span>Phụ huynh học sinh / Child's Name</span>
                                                    <strong><?php echo htmlspecialchars($student_name); ?></strong>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="boarding-footer">
                                    <span>DESTINATION: FESTIVAL-2026</span>
                                    <span>GATE: MAIN HALL</span>
                                    <span>BOARDING: 08:00 AM</span>
                                </div>
                            </div>
                            
                            <!-- Cuống vé xé (Stub) -->
                            <div class="boarding-stub">
                                <div class="boarding-stub-title">STUB / CUỐNG VÉ</div>
                                <div class="boarding-stub-qr" id="qr-boarding"></div>
                                <div class="boarding-stub-code"><?php echo htmlspecialchars($passport['passport_code']); ?></div>
                            </div>
                        </div>

                        <!-- CHỦ ĐỀ 3: VÉ SỰ KIỆN NEON (EVENT TICKET) - DỌC (Ẩn mặc định) -->
                        <div id="theme-ticket" class="card-ticket card-ticket-<?php echo $theme_class; ?>" style="display: none;">
                            <div class="ticket-top">
                                <div class="ticket-event-name">NGÀY HỘI VĂN HÓA 2026</div>
                                <div class="ticket-sub">VÉ VÀO CỔNG SỰ KIỆN</div>
                                
                                <div class="ticket-avatar-box">
                                    <img class="ticket-avatar" src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar">
                                </div>
                                
                                <div class="ticket-name"><?php echo htmlspecialchars($fullname); ?></div>
                                <div class="ticket-role"><?php echo $role_label; ?></div>
                                
                                <div class="ticket-grid">
                                    <div class="ticket-item">
                                        <span>Lớp tham gia</span>
                                        <strong><?php echo htmlspecialchars($student_class); ?></strong>
                                    </div>
                                    <div class="ticket-item">
                                        <span>Mã Passport</span>
                                        <strong><?php echo htmlspecialchars($passport['passport_code']); ?></strong>
                                    </div>
                                    <?php if ($role === 'parent'): ?>
                                        <div class="ticket-item" style="grid-column: span 2; text-align: center; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 0.3rem; margin-top: 0.3rem;">
                                            <span>Phụ huynh của học sinh: <strong><?php echo htmlspecialchars($student_name); ?></strong></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Đường cắt răng cưa -->
                            <div class="ticket-divider"></div>
                            
                            <div class="ticket-bottom">
                                <div class="ticket-bottom-info">
                                    <span>TỔ CHỨC BỞI</span>
                                    <strong>ĐOÀN TRƯỜNG THPT</strong>
                                </div>
                                <div class="ticket-qr" id="qr-ticket"></div>
                            </div>
                        </div>

                        <!-- CHỦ ĐỀ 4: THẺ VIP/ATM (CLASSIC CARD) - NGANG (Ẩn mặc định) -->
                        <div id="theme-classic" class="card-classic card-classic-<?php echo $theme_class; ?>" style="display: none;">
                            <div class="classic-header">
                                <div class="classic-logo"><i class="fa-solid fa-credit-card"></i> DIGITAL VIP CARD</div>
                                <div class="classic-chip"></div>
                            </div>
                            
                            <div class="classic-body">
                                <img class="classic-avatar" src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar">
                                <div class="classic-info">
                                    <div class="classic-code"><?php echo htmlspecialchars($passport['passport_code']); ?></div>
                                    <div class="classic-name"><?php echo htmlspecialchars($fullname); ?></div>
                                    <div style="font-size: 0.7rem; color: var(--student-primary); text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">
                                        <?php echo $role_label; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="classic-footer">
                                <div class="classic-details">
                                    <div class="classic-detail-item">
                                        <span>Lớp / Class</span>
                                        <strong><?php echo htmlspecialchars($student_class); ?></strong>
                                    </div>
                                    <div class="classic-detail-item">
                                        <span>Hạn Sử Dụng</span>
                                        <strong>12/2026</strong>
                                    </div>
                                </div>
                                <div class="classic-qr" id="qr-classic"></div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- BÊN PHẢI: THÔNG TIN CHI TIẾT VÀ NÚT TẢI VỀ -->
                <div class="action-card glass-panel">
                    
                    <!-- Hiển thị thông báo nếu vừa đăng ký xong -->
                    <?php if (isset($_GET['new']) && $_GET['new'] == 1): ?>
                        <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); color: #34d399; padding: 1.2rem; border-radius: 16px; text-align: center; margin-bottom: 1rem;">
                            <div style="font-size: 2rem; margin-bottom: 0.5rem;"><i class="fa-solid fa-circle-check"></i></div>
                            <h4 style="font-size: 1.1rem; margin-bottom: 0.2rem;">Đăng Ký Thành Công!</h4>
                            <p style="font-size: 0.8rem; color: var(--text-muted);">Passport của bạn đã được khởi tạo và lưu trữ an toàn.</p>
                        </div>
                    <?php endif; ?>

                    <h3><i class="fa-solid fa-circle-info text-gradient-<?php echo $theme_class; ?>"></i> Thông Tin Passport</h3>
                    <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1.2rem;">
                        Đây là thẻ cá nhân chính thức của bạn cho ngày hội. Vui lòng lưu trữ để quét mã khi tham gia các hoạt động.
                    </p>
                    
                    <ul class="meta-info-list">
                        <li class="meta-info-item">
                            <span>Mã Passport:</span>
                            <strong style="font-family: monospace; letter-spacing: 1px; color: var(--<?php echo $theme_class; ?>-primary);"><?php echo htmlspecialchars($passport['passport_code']); ?></strong>
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
                        <li class="meta-info-item">
                            <span>Ngày đăng ký:</span>
                            <strong><?php echo $created_at; ?></strong>
                        </li>
                    </ul>

                    <div style="display: flex; flex-direction: column; gap: 0.8rem; margin-top: 1rem;" class="no-print">
                        <button onclick="downloadPNG()" class="btn btn-primary btn-accent-<?php echo $theme_class; ?>">
                            <i class="fa-solid fa-file-image"></i> Tải ảnh Passport (PNG)
                        </button>
                        <button onclick="downloadPDF()" class="btn btn-secondary">
                            <i class="fa-solid fa-file-pdf"></i> Tải file PDF để in
                        </button>
                        <button onclick="window.print()" class="btn btn-secondary">
                            <i class="fa-solid fa-print"></i> In trực tiếp từ trình duyệt
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
        // URL này hoạt động hoàn hảo trên mọi thiết bị và IP truy cập
        const qrUrl = "<?php echo getBaseUrl() . 'passport.php?code=' . urlencode($passport['passport_code']); ?>";
        const roleTheme = "<?php echo $theme_class; ?>";

        // Khởi tạo mã QR cho cả 4 chủ đề
        window.addEventListener('load', () => {
            // QR cho Badge (90x90)
            new QRCode(document.getElementById("qr-badge"), {
                text: qrUrl,
                width: 90,
                height: 90,
                colorDark : "#000000",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.H
            });

            // QR cho Boarding Pass (90x90)
            new QRCode(document.getElementById("qr-boarding"), {
                text: qrUrl,
                width: 90,
                height: 90,
                colorDark : "#000000",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.H
            });

            // QR cho Event Ticket (75x75)
            new QRCode(document.getElementById("qr-ticket"), {
                text: qrUrl,
                width: 75,
                height: 75,
                colorDark : "#000000",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.H
            });

            // QR cho Classic Card (55x55)
            new QRCode(document.getElementById("qr-classic"), {
                text: qrUrl,
                width: 55,
                height: 55,
                colorDark : "#000000",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.H
            });
        });

        // Hàm chuyển đổi giao diện Thẻ động
        function switchTheme(themeName, tabElement) {
            // 1. Ẩn toàn bộ thẻ passport
            const cards = ['badge', 'boarding', 'ticket', 'classic'];
            cards.forEach(t => {
                const cardEl = document.getElementById('theme-' + t);
                cardEl.style.display = 'none';
                cardEl.classList.remove('passport-card-active');
            });

            // 2. Hiển thị thẻ được chọn
            const activeCard = document.getElementById('theme-' + themeName);
            // Với thẻ ngang, dùng grid hoặc flex tùy loại
            if (themeName === 'boarding') {
                activeCard.style.display = 'grid';
            } else if (themeName === 'classic') {
                activeCard.style.display = 'flex';
            } else {
                activeCard.style.display = 'flex'; // Dành cho Badge và Ticket
            }
            activeCard.classList.add('passport-card-active');

            // 3. Cập nhật trạng thái Active trên nút chuyển
            const tabs = document.querySelectorAll('.theme-tab');
            tabs.forEach(tab => {
                tab.classList.remove('active-student');
                tab.classList.remove('active-parent');
            });
            
            tabElement.classList.add('active-' + roleTheme);
        }

        // TẢI ẢNH PNG (Chất lượng siêu nét bằng cách nhân tỷ lệ scale: 3)
        function downloadPNG() {
            const activeCard = document.querySelector('.passport-card-active');
            
            // Hiển thị loading nhẹ
            const btn = document.querySelector('.btn-accent-' + roleTheme);
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang xử lý...';
            btn.disabled = true;

            html2canvas(activeCard, {
                scale: 3, // Nhân 3 độ phân giải để in ấn cực nét
                useCORS: true, // Cho phép tải các tài nguyên chứa nguồn khác
                backgroundColor: null, // Tạo nền trong suốt cho thẻ bo góc
                logging: false
            }).then(canvas => {
                const link = document.createElement('a');
                link.download = 'Passport_' + '<?php echo $passport["passport_code"]; ?>' + '.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
                
                // Trở lại ban đầu
                btn.innerHTML = originalHTML;
                btn.disabled = false;
            }).catch(err => {
                alert('Có lỗi xảy ra khi xuất ảnh! Vui lòng thử lại.');
                btn.innerHTML = originalHTML;
                btn.disabled = false;
                console.error(err);
            });
        }

        // TẢI FILE PDF (Tự động căn chỉnh khổ ngang dọc khớp với kích thước của thẻ)
        function downloadPDF() {
            const activeCard = document.querySelector('.passport-card-active');
            
            // Tìm nút PDF
            const btn = document.querySelector('button[onclick="downloadPDF()"]');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang xuất PDF...';
            btn.disabled = true;

            html2canvas(activeCard, {
                scale: 2, // Đủ nét để in PDF nhẹ nhàng
                useCORS: true,
                logging: false
            }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const { jsPDF } = window.jspdf;
                
                // Đo kích thước thẻ thực tế
                const width = activeCard.offsetWidth;
                const height = activeCard.offsetHeight;
                
                // Tự động chọn hướng giấy: 'l' (landscape - ngang) nếu rộng hơn cao, ngược lại 'p' (portrait - dọc)
                const orientation = width > height ? 'l' : 'p';
                
                // Khởi tạo PDF có kích thước chính xác bằng kích thước của thẻ (tính theo pixel)
                const pdf = new jsPDF(orientation, 'px', [width, height]);
                pdf.addImage(imgData, 'PNG', 0, 0, width, height);
                pdf.save('Passport_' + '<?php echo $passport["passport_code"]; ?>' + '.pdf');
                
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
