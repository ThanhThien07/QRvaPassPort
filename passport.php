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
    
    // Đọc ảnh thư mời gốc (Đã được nén tối ưu xuống ~430KB) và chuyển thành Base64 để chống trượt nền trên mobile
    $thumoi_path = 'anh/final-thumoi.png';
    $thumoi_base64 = '';
    if (file_exists($thumoi_path)) {
        $thumoi_data = file_get_contents($thumoi_path);
        $thumoi_base64 = 'data:image/png;base64,' . base64_encode($thumoi_data);
    }
    
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
<body class="min-h-screen flex flex-col antialiased text-slate-800 print:bg-white print:m-0 print:p-0 print:h-auto">

    <!-- Header -->
    <header class="sticky top-0 z-50 flex justify-between items-center px-6 py-4 bg-white/75 backdrop-blur-md border-b border-slate-200/80 print:hidden">
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
    <main class="flex-1 max-w-6xl w-full mx-auto px-6 py-12 flex items-center print:p-0 print:m-0 print:max-w-full print:w-full print:block">
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
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 w-full print:block">
                
                <!-- BÊN TRÁI: KHU VỰC HIỂN THỊ THƯ MỜI -->
                <div class="lg:col-span-7 flex flex-col items-center justify-center print:w-full print:flex print:justify-center print:items-center print:p-0 print:m-0">
                    
                    <!-- KHUNG CHỨA THẺ PASSPORT -->
                    <div class="w-full max-w-[500px]">
                        
                        <!-- TẤM VÉ 1: THƯ MỜI THAM DỰ (Tương ứng file final-thumoi.png) -->
                        <div id="theme-thumoi" class="w-full max-w-[500px] relative mx-auto overflow-hidden rounded-3xl bg-white border-[3px] <?php echo ($role === 'student') ? 'border-sky-500 shadow-sky-500/15' : 'border-amber-500 shadow-amber-500/15'; ?> print:shadow-none print:border-none print:rounded-none print:max-w-full print:w-[170mm] print:mx-auto print:my-[20mm] print:break-inside-avoid">
                            <!-- Ảnh mẫu gốc dạng inline Base64 để tránh lỗi bảo mật (CORS/Sandbox) và chống trượt nền trên mobile -->
                            <img class="w-full h-auto block select-none pointer-events-none" src="<?php echo $thumoi_base64 ?: 'anh/default.png'; ?>" alt="Vé Thư Mời">
                            
                            <!-- Họ tên đè lên (Overlay) - Căn chỉnh font-size và vị trí chính xác theo ảnh gốc -->
                            <div class="absolute top-[46.5%] left-[39%] w-[48%] text-left leading-none whitespace-nowrap overflow-hidden text-[#401308] font-serif italic font-extrabold tracking-tight pointer-events-none z-10" id="overlay-tm-name">
                                <?php echo htmlspecialchars($fullname); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BÊN PHẢI: THÔNG TIN CHI TIẾT VÀ NÚT TẢI VỀ -->
                <div class="lg:col-span-5 flex flex-col justify-center print:hidden">
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
                        <div class="print:hidden">
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
    <footer class="text-center py-8 border-t border-slate-200/60 mt-auto text-slate-400 text-sm print:hidden">
        <p>&copy; 2026 Hệ thống Passport Điện Tử Học Đường. Phát triển bởi Antigravity AI.</p>
    </footer>

    <!-- Tải các thư viện JS Offline đã tải về -->
    <script src="assets/js/qrcode.min.js"></script>
    <script src="assets/js/dom-to-image-more.min.js"></script>

    <?php if (!isset($error_msg)): ?>
    <!-- Script xử lý logic tại trang Passport -->
    <script>
        // Tự động điều chỉnh cỡ chữ vừa vặn trong khung chấm (3.25% chiều rộng card)
        function adjustNameFontSize() {
            const container = document.getElementById('theme-thumoi');
            const nameOverlay = document.getElementById('overlay-tm-name');
            if (container && nameOverlay) {
                const containerWidth = container.offsetWidth;
                if (containerWidth > 0) {
                    nameOverlay.style.fontSize = Math.round(containerWidth * 0.0325) + 'px';
                }
            }
        }

        document.addEventListener('DOMContentLoaded', adjustNameFontSize);
        window.addEventListener('load', adjustNameFontSize);
        window.addEventListener('resize', adjustNameFontSize);

        // TẢI ẢNH PNG dùng dom-to-image-more (Áp dụng Double Render & Base64 để chống mất nền trên mobile)
        async function downloadPNG() {
            const invitationCard = document.getElementById('theme-thumoi');
            const btn = document.getElementById('btn-download-png');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1.5"></i> Đang xuất ảnh...';
            btn.disabled = true;

            // Đảm bảo phần tử hiển thị 100% trong Viewport trước khi render để tránh lỗi vẽ thiếu tài nguyên của WebKit di động
            invitationCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Chờ tất cả font chữ load xong
            if (document.fonts && document.fonts.ready) {
                await document.fonts.ready;
            }

            // Delay nhỏ để cuộn ổn định và CSS render xong
            await new Promise(r => setTimeout(r, 600));

            // Tạm thời remove các link stylesheet ngoài để tránh SecurityError từ dom-to-image
            const corsLinks = Array.from(document.querySelectorAll('link[rel="stylesheet"]')).filter(link => {
                const href = link.getAttribute('href');
                return href && (href.startsWith('http://') || href.startsWith('https://')) && !href.includes(window.location.host);
            });

            const savedLinks = corsLinks.map(link => ({
                parent: link.parentNode,
                nextSibling: link.nextSibling,
                link: link
            }));

            corsLinks.forEach(link => link.remove());

            const scale = 3; // Xuất ảnh nét gấp 3x
            const imgElement = invitationCard.querySelector('img');
            const width  = imgElement ? imgElement.clientWidth : invitationCard.clientWidth;
            const height = imgElement ? imgElement.clientHeight : invitationCard.clientHeight;

            // Cấu hình vẽ: Tắt các CSS nặng (boxShadow, border, borderRadius) bên ngoài để chống lỗi WebKit di động
            const renderOptions = {
                quality: 1,
                width:  width  * scale,
                height: height * scale,
                style: {
                    transform: 'scale(' + scale + ')',
                    transformOrigin: 'top left',
                    width:  width  + 'px',
                    height: height + 'px',
                    boxShadow: 'none',
                    border: 'none',
                    borderRadius: '0',
                    margin: '0',
                    padding: '0'
                }
            };

            // Mẹo Render 2 lần (Double Render Hack) - Giúp WebKit/Safari di động cache đầy đủ tài nguyên ảnh nền trước khi lấy kết quả thật
            domtoimage.toPng(invitationCard, renderOptions)
                .then(() => {
                    // Lần render thứ 2 để lấy kết quả chính thức đã cache
                    return domtoimage.toPng(invitationCard, renderOptions);
                })
                .then(dataUrl => {
                    // Khôi phục lại các stylesheets
                    savedLinks.forEach(item => {
                        item.parent.insertBefore(item.link, item.nextSibling);
                    });

                    btn.innerHTML = originalHTML;
                    btn.disabled = false;

                    if (isMobileDevice()) {
                        // Nếu là thiết bị di động, hiển thị Modal kèm hướng dẫn nhấn giữ để lưu
                        const modal = document.getElementById('mobile-download-modal');
                        const modalImg = document.getElementById('mobile-rendered-img');
                        if (modal && modalImg) {
                            modalImg.src = dataUrl;
                            modal.classList.remove('hidden');
                            modal.classList.add('flex');
                        }
                    } else {
                        // Nếu là máy tính, tự động tải xuống
                        const link = document.createElement('a');
                        link.download = 'ThuMoi_<?php echo htmlspecialchars($passport["passport_code"] ?? time()); ?>.png';
                        link.href = dataUrl;
                        link.click();
                    }
                })
                .catch(err => {
                    // Khôi phục lại các stylesheets nếu lỗi
                    savedLinks.forEach(item => {
                        item.parent.insertBefore(item.link, item.nextSibling);
                    });

                    alert('Có lỗi xảy ra khi xuất ảnh! Vui lòng thử lại.');
                    btn.innerHTML = originalHTML;
                    btn.disabled = false;
                    console.error(err);
                });
        }

        function isMobileDevice() {
            return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || window.innerWidth < 768;
        }

        function closeMobileModal() {
            const modal = document.getElementById('mobile-download-modal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }
    </script>
    <?php endif; ?>

    <!-- Modal xem trước ảnh để lưu trên thiết bị di động -->
    <div id="mobile-download-modal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/80 p-4 backdrop-blur-sm transition-opacity duration-300">
        <div class="relative w-full max-w-lg bg-white rounded-3xl p-6 shadow-2xl flex flex-col items-center animate-[scaleIn_0.3s_ease-out]">
            <!-- Nút đóng -->
            <button onclick="closeMobileModal()" class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-700 transition-colors">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
            
            <div class="text-center mb-4">
                <h3 class="text-lg font-extrabold text-slate-800 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-circle-down text-emerald-500"></i> Lưu Thư Mời Của Bạn
                </h3>
                <p class="text-xs text-slate-500 mt-1">Vui lòng <strong class="text-slate-800">nhấn giữ (chạm và giữ im)</strong> vào bức ảnh bên dưới để chọn <strong class="text-slate-800">"Lưu hình ảnh"</strong> hoặc <strong class="text-slate-800">"Tải về hình ảnh"</strong>.</p>
            </div>
            
            <!-- Nơi hiển thị ảnh đã xuất -->
            <div class="w-full border border-slate-100 rounded-2xl overflow-hidden shadow-inner bg-slate-50">
                <img id="mobile-rendered-img" class="w-full h-auto block select-all cursor-pointer" src="" alt="Thư mời đã xuất">
            </div>
            
            <!-- Nút đóng nhanh bên dưới -->
            <button onclick="closeMobileModal()" class="mt-5 px-5 py-2.5 rounded-xl text-sm font-semibold bg-slate-100 text-slate-600 hover:bg-slate-200 active:scale-95 transition-all">
                Đóng lại
            </button>
        </div>
    </div>
</body>
</html>
