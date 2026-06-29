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
    $fullname = mb_convert_case($passport['fullname'], MB_CASE_TITLE, "UTF-8");
    $role = $passport['role'];
    $phone = $passport['phone'];
    $avatar = $passport['avatar'];
    $created_at = date('d/m/Y H:i', strtotime($passport['created_at']));
    
    // Phân loại nhãn vai trò và màu sắc
    $role_label = ($role === 'student') ? 'Học Sinh' : 'Phụ Huynh';
    $theme_class = ($role === 'student') ? 'student' : 'parent';
    
    // Tạo biến class Tailwind động
    $accent_text = ($role === 'student') ? 'text-sky-600' : 'text-amber-600';
    $accent_gradient = ($role === 'student') ? 'from-sky-500 to-blue-600' : 'from-amber-500 to-orange-600';
    $accent_shadow_glow = ($role === 'student') ? 'shadow-sky-500/25 hover:shadow-sky-500/30' : 'shadow-amber-500/25 hover:shadow-amber-500/30';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passport & Vé Sự Kiện - <?php echo htmlspecialchars($fullname ?? 'Lỗi'); ?></title>
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
    <link rel="stylesheet" href="assets/css/style.css?v=1.1.0">
    <!-- Meta để tránh WebView cache quá cũ -->
    <meta http-equiv="Cache-Control" content="no-cache, must-revalidate">
    <meta name="format-detection" content="telephone=no">
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
            <a href="index.php" class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl font-semibold text-sm bg-slate-100 border border-slate-200 text-slate-700 hover:bg-slate-200/80 hover:border-slate-300 active:bg-slate-100 transition-all duration-300"><i class="fa-solid fa-house"></i> Trang chủ</a>
            <a href="register.php" class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl font-semibold text-sm bg-gradient-to-r from-sky-500 to-blue-600 text-white shadow-md shadow-sky-500/15 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-sky-500/20 active:translate-y-0 active:shadow-md transition-all duration-300"><i class="fa-solid fa-plus"></i> Đăng ký mới</a>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-6xl w-full mx-auto px-6 py-12 flex items-center">
        <?php if (isset($error_msg)): ?>
            <!-- Màn hình lỗi không tìm thấy -->
            <div class="max-w-md w-full mx-auto p-8 bg-white/85 backdrop-blur-md border border-slate-200/60 rounded-3xl shadow-xl text-center">
                <div class="w-16 h-16 bg-red-50 text-red-500 border border-red-100 rounded-full flex items-center justify-center text-3xl mx-auto mb-6 shadow-sm">
                    <i class="fa-solid fa-circle-exclamation"></i>
                </div>
                <h2 class="text-xl font-extrabold text-slate-800 mb-3"><?php echo $error_title; ?></h2>
                <p class="text-slate-500 text-sm leading-relaxed mb-6"><?php echo $error_msg; ?></p>
                <a href="index.php" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-semibold text-sm bg-gradient-to-r from-sky-500 to-blue-600 text-white shadow-md hover:-translate-y-0.5 transition-all"><i class="fa-solid fa-house"></i> Về Trang Chủ</a>
            </div>
        <?php else: ?>
            
            <!-- Màn hình hiển thị Passport & Vé mời thành công -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 w-full">
                
                <!-- BÊN TRÁI: KHU VỰC HIỂN THỊ THƯ MỜI -->
                <div class="lg:col-span-7 flex flex-col items-center justify-center">
                    
                    <!-- KHUNG CHỨA THẺ PASSPORT -->
                    <div class="w-full max-w-[500px]">
                        
                        <!-- TẤM VÉ 1: THƯ MỜI THAM DỰ (Tương ứng file final-thumoi.png) -->
                        <div id="theme-thumoi" class="ticket-card-container ticket-card-container-<?php echo $theme_class; ?>">
                            <!-- Ảnh mẫu gốc từ thư mục anh -->
                            <img class="ticket-template-img" src="anh/final-thumoi.png" alt="Vé Thư Mời" crossorigin="anonymous" onerror="this.removeAttribute('crossorigin'); this.src='uploads/default.png';">
                            
                            <!-- Họ tên đè lên (Overlay) - Căn giữa trong phạm vi dấu 3 chấm, ngang với "Kính mời" -->
                            <div class="overlay-element overlay-name-thumoi" id="overlay-tm-name">
                                <?php echo htmlspecialchars($fullname); ?>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- BÊN PHẢI: THÔNG TIN CHI TIẾT VÀ NÚT TẢI VỀ -->
                <div class="lg:col-span-5 flex flex-col justify-center no-print">
                    <div class="p-8 md:p-10 bg-white border border-slate-100 rounded-3xl shadow-xl shadow-slate-100">
                        
                        <!-- Hiển thị thông báo nếu vừa đăng ký xong -->
                        <?php if (isset($_GET['new']) && $_GET['new'] == 1): ?>
                            <div class="bg-emerald-50 border border-emerald-200/60 text-emerald-700 p-5 rounded-2xl mb-6 text-center shadow-sm animate-pulse">
                                <div class="text-3xl text-emerald-500 mb-2"><i class="fa-solid fa-circle-check"></i></div>
                                <h4 class="font-bold text-base mb-1">Đăng Ký Thành Công!</h4>
                                <p class="text-xs text-slate-400">Thư mời của bạn đã được khởi tạo và lưu trữ.</p>
                            </div>
                        <?php endif; ?>

                        <h3 class="text-xl font-extrabold text-slate-800 flex items-center gap-2.5 mb-6">
                            <i class="fa-solid fa-circle-info <?php echo $accent_text; ?>"></i> Thông Tin Passport
                        </h3>
                        
                        <ul class="divide-y divide-slate-100 mb-8">
                            <li class="flex justify-between items-center py-3.5 text-sm text-slate-500">
                                <span>Mã Passport:</span>
                                <strong class="font-mono tracking-wider text-base <?php echo $accent_text; ?>"><?php echo htmlspecialchars($passport['passport_code']); ?></strong>
                            </li>
                            <li class="flex justify-between items-center py-3.5 text-sm text-slate-500">
                                <span>Họ và Tên:</span>
                                <strong class="text-slate-900 font-bold"><?php echo htmlspecialchars($fullname); ?></strong>
                            </li>
                            <li class="flex justify-between items-center py-3.5 text-sm text-slate-500">
                                <span>Đối tượng:</span>
                                <strong class="text-slate-900 font-bold"><?php echo $role_label; ?></strong>
                            </li>
                            <li class="flex justify-between items-center py-3.5 text-sm text-slate-500">
                                <span>Số điện thoại:</span>
                                <strong class="text-slate-900 font-bold"><?php echo htmlspecialchars($phone); ?></strong>
                            </li>
                        </ul>

                        <!-- Nút thao tác -->
                        <div class="no-print">
                            <button onclick="downloadPNG()" class="w-full inline-flex items-center justify-center gap-2 py-4 px-6 rounded-2xl font-bold text-base text-white bg-gradient-to-r <?php echo $accent_gradient; ?> shadow-lg <?php echo $accent_shadow_glow; ?> hover:-translate-y-0.5 active:translate-y-0 transition-all duration-300" id="btn-download-png">
                                <i class="fa-solid fa-file-image"></i> Tải ảnh Thư Mời (PNG)
                            </button>
                        </div>
                    </div>
                </div>

            </div>
            
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="text-center py-8 border-t border-slate-200/60 mt-auto text-slate-400 text-sm no-print">
        <p>&copy; 2026 Hệ thống Passport Điện Tử Học Đường. Phát triển bởi Antigravity AI.</p>
    </footer>

    <!-- Tải các thư viện JS Offline đã tải về -->
    <script src="assets/js/qrcode.min.js"></script>
    <script src="assets/js/html2canvas.min.js"></script>

    <?php if (!isset($error_msg)): ?>
    <!-- Script xử lý logic tại trang Passport -->
    <script>
        // TỰ ĐỘNG ĐIỀU CHỈNH CỠ CHỮ TRÊN TRÌNH DUYỆT
        // Font mặc định = 1.11cqw, chỉ thu nhỏ nếu tên quá dài
        function adjustNameFontSize() {
            const invitationCard = document.getElementById('theme-thumoi');
            const nameOverlay = document.getElementById('overlay-tm-name');
            if (invitationCard && nameOverlay) {
                const actualWidth = invitationCard.offsetWidth;
                
                // Cỡ chữ cơ sở = 1.6% chiều rộng card (khớp với 1.6cqw trong CSS)
                let targetFontSize = actualWidth * 0.016;
                nameOverlay.style.fontSize = targetFontSize + 'px';
                
                // Thu nhỏ dần cỡ chữ nếu tên quá dài làm tràn khung chứa
                let maxIterations = 30;
                while (nameOverlay.scrollWidth > nameOverlay.clientWidth + 2 && targetFontSize > 4 && maxIterations > 0) {
                    targetFontSize -= 0.2;
                    nameOverlay.style.fontSize = targetFontSize + 'px';
                    maxIterations--;
                }
            }
        }

        // Kích hoạt tự động điều chỉnh cỡ chữ khi tải trang và thay đổi kích thước màn hình
        document.addEventListener('DOMContentLoaded', adjustNameFontSize);
        window.addEventListener('load', adjustNameFontSize);
        window.addEventListener('resize', adjustNameFontSize);

        // TẢI ẢNH PNG
        // Fix lỗi trên Android/Mobile: đợi ảnh template load xong, dùng allowTaint + imageTimeout cao hơn
        function downloadPNG() {
            const invitationCard = document.getElementById('theme-thumoi');
            const nameOverlay = document.getElementById('overlay-tm-name');
            const btn = document.getElementById('btn-download-png');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1.5"></i> Đang xuất ảnh...';
            btn.disabled = true;

            const templateImg = invitationCard.querySelector('.ticket-template-img');

            function doRender() {
                const currentFontSize = nameOverlay.style.fontSize;

                html2canvas(invitationCard, {
                    scale: 3,
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#ffffff',
                    logging: false,
                    imageTimeout: 15000,
                    onclone: (clonedDoc) => {
                        const clonedName = clonedDoc.getElementById('overlay-tm-name');
                        if (clonedName) {
                            // Sao chép toàn bộ style inline từ overlay gốc sang bản clone
                            clonedName.style.cssText = nameOverlay.style.cssText;
                            clonedName.style.fontSize = currentFontSize;
                            // Đảm bảo display flex & căn giữa được giữ nguyên
                            clonedName.style.display = 'flex';
                            clonedName.style.alignItems = 'center';
                            clonedName.style.justifyContent = 'center';
                            clonedName.style.textAlign = 'center';
                            // GIỮ transform translateY(-50%) để tên ngang hàng đúng với "Kính mời"
                            clonedName.style.transform = 'translateY(-50%)';
                        }
                    }
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

            // Nếu ảnh template chưa load xong, chờ load xong rồi mới render
            if (templateImg && !templateImg.complete) {
                templateImg.onload = doRender;
                templateImg.onerror = doRender; // Vẫn cố gắng render dù ảnh lỗi
            } else {
                doRender();
            }
        }
    </script>
    <?php endif; ?>
</body>
</html>
