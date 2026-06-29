<?php
/**
 * QRvaPassPort - Integrated Registration Form
 * Antigravity - Premium AI Developer
 */
require_once 'config.php';

$errors = [];
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    if (!empty($fullname)) {
        $fullname = mb_convert_case($fullname, MB_CASE_TITLE, "UTF-8");
    }
    $role = trim($_POST['role'] ?? 'student');
    $phone = trim($_POST['phone'] ?? '');
    $student_class = ''; // Lớp học đã được lược bỏ theo yêu cầu
    $student_name = ''; // Tên con đã được lược bỏ theo yêu cầu để tránh rườm rà

    // 1. Kiểm tra hợp lệ dữ liệu đầu vào
    if (empty($fullname)) $errors[] = 'Họ và tên không được để trống!';
    if (empty($phone)) $errors[] = 'Số điện thoại không được để trống!';
    if (!in_array($role, ['student', 'parent'])) $role = 'student';

    // 2. Kiểm tra xem số điện thoại đã đăng ký chưa
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT `passport_code`, `fullname` FROM `passports` WHERE `phone` = ? LIMIT 1");
        $stmt->execute([$phone]);
        $existing_user = $stmt->fetch();
        
        if ($existing_user) {
            $errors[] = 'Số điện thoại này đã được đăng ký bởi <strong>' . htmlspecialchars($existing_user['fullname']) . '</strong>. <a href="passport.php?code=' . $existing_user['passport_code'] . '" class="text-sky-600 font-bold underline hover:text-sky-700">Bấm vào đây để xem Passport</a>';
        }
    }

    // 3. Xử lý ảnh đại diện (Mặc định theo yêu cầu, không cần tải lên)
    $avatar_path = 'anh/default.png';

    // 4. Lưu vào database và chuyển hướng
    if (empty($errors)) {
        try {
            // Sinh mã Passport duy nhất
            $passport_code = generatePassportCode($pdo, $role);
            
            $stmt = $pdo->prepare("
                INSERT INTO `passports` (`passport_code`, `fullname`, `role`, `student_name`, `student_class`, `phone`, `avatar`) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $passport_code,
                $fullname,
                $role,
                $student_name,
                $student_class,
                $phone,
                $avatar_path
            ]);
            
            // Đồng bộ ra file database.csv
            syncDatabaseToCsv($pdo);
            
            // Đăng ký thành công, chuyển hướng đến trang hiển thị Passport cá nhân
            header("Location: passport.php?code=" . $passport_code . "&new=1");
            exit;
            
        } catch (PDOException $e) {
            $errors[] = 'Lỗi cơ sở dữ liệu: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký Passport Điện Tử</title>
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
        <nav>
            <a href="index.php" class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl font-semibold text-sm bg-slate-100 border border-slate-200 text-slate-700 hover:bg-slate-200/80 hover:border-slate-300 active:bg-slate-100 transition-all duration-300"><i class="fa-solid fa-arrow-left"></i> Quay lại</a>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="flex-1 flex items-center justify-center px-6 py-12">
        <div class="max-w-xl w-full">
            <div class="p-8 md:p-10 bg-white border border-slate-100 rounded-3xl shadow-xl relative overflow-hidden">
                <!-- Decor Glow -->
                <div id="form-glow" class="absolute -top-12 -right-12 w-36 h-36 rounded-full bg-sky-500/10 filter blur-2xl pointer-events-none transition-all duration-500"></div>

                <div class="text-center mb-8">
                    <h2 class="text-2xl md:text-3xl font-extrabold text-slate-800" id="form-title">Đăng Ký <span class="bg-gradient-to-r from-sky-500 to-blue-600 bg-clip-text text-transparent">Passport Học Sinh</span></h2>
                    <p class="text-slate-400 text-sm mt-2">Vui lòng điền thông tin chính xác để hệ thống tạo Passport điện tử.</p>
                </div>

                <!-- Hiển thị lỗi nếu có -->
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-50 border border-red-200 text-red-600 p-4 rounded-2xl mb-6 text-sm">
                        <ul class="list-disc list-inside flex flex-col gap-1.5">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Form -->
                <form action="register.php" method="POST" class="space-y-5" id="register-form">
                    
                    <!-- Lựa chọn đối tượng (Role) -->
                    <div>
                        <label class="block font-semibold text-slate-700 text-sm mb-3">Bạn là ai?</label>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="border-2 border-sky-500 bg-sky-50/40 shadow-md shadow-sky-500/5 rounded-2xl p-4 text-center cursor-pointer relative flex flex-col items-center justify-center transition-all duration-300" id="option-student">
                                <input type="radio" name="role" value="student" class="absolute opacity-0 cursor-pointer" checked onclick="switchRole('student')">
                                <span class="icon text-2xl mb-2 text-sky-500"><i class="fa-solid fa-graduation-cap"></i></span>
                                <span class="title font-bold text-sky-600 text-sm">Học Sinh</span>
                                <span class="text-[10px] text-slate-400 mt-0.5">Màu Thư mời Xanh Neon</span>
                            </label>
                            <label class="border border-slate-200/80 rounded-2xl p-4 text-center cursor-pointer bg-slate-50/50 hover:bg-slate-50 relative flex flex-col items-center justify-center transition-all duration-300" id="option-parent">
                                <input type="radio" name="role" value="parent" class="absolute opacity-0 cursor-pointer" onclick="switchRole('parent')">
                                <span class="icon text-2xl mb-2 text-slate-400"><i class="fa-solid fa-user-group"></i></span>
                                <span class="title font-bold text-slate-700 text-sm">Phụ Huynh</span>
                                <span class="text-[10px] text-slate-400 mt-0.5">Màu Thư mời Hổ phách</span>
                            </label>
                        </div>
                    </div>

                    <!-- Họ và tên -->
                    <div>
                        <label class="block font-semibold text-slate-700 text-sm mb-2" id="label-fullname"><i class="fa-solid fa-user"></i> Họ và Tên Học Sinh</label>
                        <input type="text" name="fullname" class="w-full bg-slate-50 border border-slate-200/80 rounded-2xl py-3.5 px-4 text-slate-900 placeholder-slate-400 focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-500/10 focus:bg-white transition-all duration-300" placeholder="Nhập họ và tên đầy đủ..." required value="<?php echo htmlspecialchars($_POST['fullname'] ?? ''); ?>">
                    </div>

                    <!-- Số điện thoại -->
                    <div>
                        <label class="block font-semibold text-slate-700 text-sm mb-2"><i class="fa-solid fa-phone"></i> Số Điện Thoại Liên Hệ</label>
                        <input type="tel" name="phone" class="w-full bg-slate-50 border border-slate-200/80 rounded-2xl py-3.5 px-4 text-slate-900 placeholder-slate-400 focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-500/10 focus:bg-white transition-all duration-300" placeholder="Nhập số điện thoại để tra cứu lại sau..." required value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>

                    <!-- Nút Submit -->
                    <button type="submit" class="w-full py-4 px-6 rounded-2xl font-bold text-base bg-gradient-to-r from-sky-500 to-blue-600 text-white shadow-lg shadow-sky-500/25 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-sky-500/30 hover:brightness-105 active:translate-y-0 active:shadow-lg transition-all duration-300" id="btn-submit">
                        <i class="fa-solid fa-qrcode mr-1.5"></i> Tạo Passport Của Tôi
                    </button>
                </form>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="text-center py-6 border-t border-slate-200/60 mt-auto text-slate-400 text-sm no-print">
        <p>&copy; 2026 Hệ thống Passport Điện Tử Học Đường. Phát triển bởi Antigravity AI.</p>
    </footer>

    <!-- Script tương tác động -->
    <script>
        // Hàm chuyển đổi vai trò động
        function switchRole(role) {
            const optStudent = document.getElementById('option-student');
            const optParent = document.getElementById('option-parent');
            const formGlow = document.getElementById('form-glow');
            const formTitle = document.getElementById('form-title');
            const labelFullname = document.getElementById('label-fullname');
            const btnSubmit = document.getElementById('btn-submit');

            if (role === 'student') {
                // Active Học sinh
                optStudent.className = 'border-2 border-sky-500 bg-sky-50/40 shadow-md shadow-sky-500/5 rounded-2xl p-4 text-center cursor-pointer relative flex flex-col items-center justify-center transition-all duration-300';
                optStudent.querySelector('.icon').className = 'icon text-2xl mb-2 text-sky-500';
                optStudent.querySelector('.title').className = 'title font-bold text-sky-600 text-sm';
                
                // Deactive Phụ huynh
                optParent.className = 'border border-slate-200/80 rounded-2xl p-4 text-center cursor-pointer bg-slate-50/50 hover:bg-slate-50 relative flex flex-col items-center justify-center transition-all duration-300';
                optParent.querySelector('.icon').className = 'icon text-2xl mb-2 text-slate-400';
                optParent.querySelector('.title').className = 'title font-bold text-slate-700 text-sm';
                
                // Đổi màu Glow và Title
                formGlow.className = 'absolute -top-12 -right-12 w-36 h-36 rounded-full bg-sky-500/10 filter blur-2xl pointer-events-none transition-all duration-500';
                formTitle.innerHTML = 'Đăng Ký <span class="bg-gradient-to-r from-sky-500 to-blue-600 bg-clip-text text-transparent">Passport Học Sinh</span>';
                
                // Thay đổi Label
                labelFullname.innerHTML = '<i class="fa-solid fa-user"></i> Họ và Tên Học Sinh';
                
                // Thay đổi nút submit
                btnSubmit.className = 'w-full py-4 px-6 rounded-2xl font-bold text-base bg-gradient-to-r from-sky-500 to-blue-600 text-white shadow-lg shadow-sky-500/25 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-sky-500/30 hover:brightness-105 active:translate-y-0 active:shadow-lg transition-all duration-300';
            } else {
                // Active Phụ huynh
                optStudent.className = 'border border-slate-200/80 rounded-2xl p-4 text-center cursor-pointer bg-slate-50/50 hover:bg-slate-50 relative flex flex-col items-center justify-center transition-all duration-300';
                optStudent.querySelector('.icon').className = 'icon text-2xl mb-2 text-slate-400';
                optStudent.querySelector('.title').className = 'title font-bold text-slate-700 text-sm';

                optParent.className = 'border-2 border-amber-500 bg-amber-50/40 shadow-md shadow-amber-500/5 rounded-2xl p-4 text-center cursor-pointer relative flex flex-col items-center justify-center transition-all duration-300';
                optParent.querySelector('.icon').className = 'icon text-2xl mb-2 text-amber-600';
                optParent.querySelector('.title').className = 'title font-bold text-amber-600 text-sm';
                
                // Đổi màu Glow và Title
                formGlow.className = 'absolute -top-12 -right-12 w-36 h-36 rounded-full bg-amber-500/10 filter blur-2xl pointer-events-none transition-all duration-500';
                formTitle.innerHTML = 'Đăng Ký <span class="bg-gradient-to-r from-amber-500 to-orange-600 bg-clip-text text-transparent">Passport Phụ Huynh</span>';
                
                // Thay đổi Label
                labelFullname.innerHTML = '<i class="fa-solid fa-user"></i> Họ và Tên Phụ Huynh';
                
                // Thay đổi nút submit
                btnSubmit.className = 'w-full py-4 px-6 rounded-2xl font-bold text-base bg-gradient-to-r from-amber-500 to-orange-600 text-white shadow-lg shadow-amber-500/25 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-amber-500/30 hover:brightness-105 active:translate-y-0 active:shadow-lg transition-all duration-300';
            }
        }

        // Xử lý submit form - tránh lỗi nút bị lock vĩnh viễn trên Android WebView
        const registerForm = document.getElementById('register-form');
        if (registerForm) {
            registerForm.addEventListener('submit', function() {
                const btn = document.getElementById('btn-submit');
                const originalHTML = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1.5"></i> Đang tạo...';
                // Tự động mở khóa nút sau 15 giây nếu mạng chậm hoặc WebView cancel request
                setTimeout(function() {
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                }, 15000);
            });
        }

        // Tự động chạy khi khởi chạy để đồng bộ hóa nếu ấn nút F5 có lưu dữ liệu POST cũ
        window.addEventListener('DOMContentLoaded', () => {
            const selectedRole = document.querySelector('input[name="role"]:checked').value;
            switchRole(selectedRole);
        });
    </script>
</body>
</html>
