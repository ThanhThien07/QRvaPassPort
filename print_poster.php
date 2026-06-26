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
    <!-- CSS chính -->
    <link rel="stylesheet" href="assets/css/style.css">
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

        /* Khi hiển thị trên màn hình, giả lập trang giấy A4 */
        .poster-preview-wrapper {
            background: #ffffff;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 2rem;
        }

        /* Thanh điều khiển nổi trên màn hình */
        .print-control-bar {
            background: rgba(15, 23, 42, 0.95);
            border: 1px solid rgba(255,255,255,0.1);
            padding: 1rem 2rem;
            border-radius: 50px;
            display: flex;
            gap: 1.5rem;
            align-items: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            position: fixed;
            bottom: 2rem;
            z-index: 100;
            backdrop-filter: blur(10px);
        }

        .print-info-text {
            color: #94a3b8;
            font-size: 0.9rem;
        }
        .print-info-text strong {
            color: #38bdf8;
            font-family: monospace;
        }

        /* Ẩn thanh điều khiển và tối ưu khổ giấy khi in thực tế */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .print-control-bar {
                display: none !important;
            }
            .poster-preview-wrapper {
                box-shadow: none !important;
                border-radius: 0 !important;
                margin: 0 !important;
            }
            .poster-container {
                border: 10px double #000000 !important;
                width: 100% !important;
                height: 100vh !important;
                page-break-after: avoid;
                page-break-before: avoid;
            }
        }
    </style>
</head>
<body>

    <!-- Thanh điều khiển nổi trên màn hình (Ẩn khi in) -->
    <div class="print-control-bar no-print">
        <span class="print-info-text">
            <i class="fa-solid fa-link"></i> URL QR đăng ký: <strong><?php echo htmlspecialchars($register_url); ?></strong>
        </span>
        <button onclick="window.print()" class="btn btn-primary" style="padding: 0.6rem 1.5rem; font-size: 0.95rem;">
            <i class="fa-solid fa-print"></i> Tiến hành In Poster (A4)
        </button>
        <a href="admin_dashboard.php" class="btn btn-secondary" style="padding: 0.6rem 1.5rem; font-size: 0.95rem;">
            <i class="fa-solid fa-arrow-left"></i> Quay lại Dashboard
        </a>
    </div>

    <!-- Khung Poster A4 -->
    <div class="poster-preview-wrapper">
        <div class="poster-container">
            <!-- Header -->
            <div class="poster-header">
                <div class="poster-logo">
                    <i class="fa-solid fa-id-card" style="color: #1e3a8a;"></i> PASSPORT ĐIỆN TỬ
                </div>
                <div class="poster-subtitle">HỆ THỐNG QUẢN LÝ THÔNG TIN SỰ KIỆN HỌC ĐƯỜNG</div>
            </div>

            <!-- Title chính -->
            <div style="text-align: center;">
                <h1 class="poster-title">QUÉT MÃ ĐỂ ĐĂNG KÝ</h1>
                <p class="poster-subtitle" style="margin-top: 0.5rem; color: #1e3a8a; font-weight: 700;">Nhận ngay Passport điện tử & Vé mời tham gia sự kiện</p>
            </div>

            <!-- Khung chứa mã QR khổng lồ -->
            <div class="poster-qr-wrapper">
                <div id="poster-qr-code"></div>
            </div>

            <!-- Hướng dẫn chi tiết -->
            <div class="poster-instructions">
                <h3><i class="fa-solid fa-mobile-screen-button" style="color: #1e3a8a; margin-right: 0.5rem;"></i> Hướng dẫn thực hiện:</h3>
                <p>1. Sử dụng ứng dụng <strong>Camera điện thoại</strong>, <strong>Zalo</strong>, hoặc trình quét QR bất kỳ.</p>
                <p style="margin-top: 0.3rem;">2. Quét mã QR ở trên để truy cập biểu mẫu đăng ký trực tuyến.</p>
                <p style="margin-top: 0.3rem;">3. Điền đầy đủ thông tin và nhận ngay thẻ Passport & Vé mời của bạn!</p>
            </div>

            <!-- Footer -->
            <div class="poster-footer">
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
