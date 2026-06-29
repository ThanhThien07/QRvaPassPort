<?php
/**
 * QRvaPassPort - Printable A4 Registration Poster with Fixed QR Code
 * Antigravity - Premium AI Developer
 */
require_once 'config.php';
session_start();

// Kiểm tra quyền Admin (Chỉ admin mới được truy cập để in)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit;
}

// Lấy URL đăng ký động dựa trên IP hiện tại
$register_url = getBaseUrl() . 'register.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>In Poster Mã QR Đăng Ký Cố Định</title>
    <!-- Tailwind CSS Play CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                        serif: ['Times New Roman', 'serif'],
                    }
                }
            }
        }
    </script>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Tối ưu hóa hiển thị riêng cho trang in này */
        body {
            background: #111827;
            margin: 0;
            padding: 2rem 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        @page {
            size: A4 portrait;
            margin: 0;
        }
        /* Ẩn thanh điều khiển và tối ưu khổ giấy khi in thực tế */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .poster-preview-wrapper {
                box-shadow: none !important;
                border-radius: 0 !important;
                margin: 0 !important;
                width: 210mm !important;
                height: 297mm !important;
            }
            .poster-container {
                width: 210mm !important;
                height: 297mm !important;
                border: 15px double #1e293b !important;
                box-sizing: border-box !important;
                margin: 0 !important;
                padding: 20mm !important;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body class="antialiased">

    <!-- Thanh điều khiển nổi trên màn hình (Ẩn khi in) -->
    <div class="print:hidden flex flex-wrap gap-6 items-center bg-slate-900/95 border border-slate-800 px-8 py-4 rounded-full shadow-2xl fixed bottom-8 z-50 backdrop-blur-md">
        <span class="text-slate-400 text-sm">
            <i class="fa-solid fa-link text-sky-500 mr-1"></i> URL QR đăng ký: <strong class="font-mono text-sky-400"><?php echo htmlspecialchars($register_url); ?></strong>
        </span>
        <button onclick="window.print()" class="inline-flex items-center justify-center gap-2 px-5 py-2 rounded-full font-bold text-sm bg-gradient-to-r from-sky-500 to-blue-600 text-white shadow-lg shadow-sky-500/25 hover:-translate-y-0.5 active:translate-y-0 transition-all duration-300">
            <i class="fa-solid fa-print"></i> Tiến hành In Poster (A4)
        </button>
        <a href="admin_dashboard.php" class="inline-flex items-center justify-center gap-2 px-5 py-2 rounded-full font-bold text-sm bg-slate-800 border border-slate-700 text-slate-300 hover:bg-slate-700 transition-all duration-300">
            <i class="fa-solid fa-arrow-left"></i> Quay lại Dashboard
        </a>
    </div>

    <!-- Khung Poster A4 -->
    <div class="poster-preview-wrapper bg-white shadow-[0_20px_50px_rgba(0,0,0,0.5)] rounded-lg overflow-hidden mb-8 print:shadow-none print:rounded-none print:m-0 print:w-[210mm] print:h-[297mm]">
        <div class="poster-container bg-white text-black w-[210mm] h-[297mm] mx-auto p-[20mm] box-border flex flex-col justify-between items-center border-[15px] border-double border-slate-800 relative print:w-[210mm] print:h-[297mm] print:border-[15px] print:border-double print:border-slate-800 print:box-border print:m-0 print:p-[20mm] print:break-inside-avoid">
            <!-- Header -->
            <div class="text-center w-full">
                <div class="text-[2.8rem] font-black text-slate-900 tracking-[2px]">
                    <i class="fa-solid fa-id-card" style="color: #1e3a8a;"></i> PASSPORT ĐIỆN TỬ
                </div>
                <div class="text-[1.6rem] text-slate-500 mt-[0.8rem] font-semibold">HỆ THỐNG QUẢN LÝ THÔNG TIN SỰ KIỆN HỌC ĐƯỜNG</div>
            </div>

            <!-- Title chính -->
            <div style="text-align: center;">
                <h1 class="text-[3.2rem] font-extrabold text-[#1e3a8a] mt-[1.5rem] leading-[1.25] uppercase">QUÉT MÃ ĐỂ ĐĂNG KÝ</h1>
                <p class="text-[1.6rem] text-slate-500 mt-[0.8rem] font-semibold" style="margin-top: 0.5rem; color: #1e3a8a; font-weight: 700;">Nhận ngay Passport điện tử & Vé mời tham gia sự kiện</p>
            </div>

            <!-- Khung chứa mã QR khổng lồ -->
            <div class="w-[120mm] h-[120mm] border-[6px] border-[#1e3a8a] p-[10mm] rounded-[24px] bg-white flex items-center justify-center shadow-lg shadow-slate-200/50">
                <div id="poster-qr-code" class="w-full h-full [&_canvas]:!w-full [&_canvas]:!h-full [&_img]:!w-full [&_img]:!h-full"></div>
            </div>

            <!-- Footer -->
            <div class="text-center text-[1.2rem] text-slate-400 w-full border-t border-slate-200 pt-4">
                <p>Mã QR cố định sự kiện - Vui lòng đặt tại bàn đón tiếp khách</p>
                <p style="font-size: 0.9rem; margin-top: 0.3rem; color: #9ca3af;">Hệ thống Passport Điện Tử Học Đường &copy; 2026</p>
            </div>
        </div>
    </div>

    <!-- Thư viện QRCode Offline -->
    <script src="assets/js/qrcode.min.js"></script>
    <script>
        // Tạo mã QR động trỏ tới URL đăng ký
        const qrContainer = document.getElementById('poster-qr-code');
        const registerUrl = "<?php echo $register_url; ?>";
        
        new QRCode(qrContainer, {
            text: registerUrl,
            width: 450, // Độ phân giải cực cao cho in ấn
            height: 450,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
    </script>
</body>
</html>
