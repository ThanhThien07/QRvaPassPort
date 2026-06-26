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
    $role = trim($_POST['role'] ?? 'student');
    $phone = trim($_POST['phone'] ?? '');
    $student_class = trim($_POST['student_class'] ?? '');
    $student_name = trim($_POST['student_name'] ?? '');

    // 1. Kiểm tra hợp lệ dữ liệu đầu vào
    if (empty($fullname)) $errors[] = 'Họ và tên không được để trống!';
    if (empty($phone)) $errors[] = 'Số điện thoại không được để trống!';
    if (!in_array($role, ['student', 'parent'])) $role = 'student';
    
    if ($role === 'student') {
        if (empty($student_class)) $errors[] = 'Vui lòng nhập lớp học của bạn!';
        $student_name = null; // Học sinh thì không cần tên con
    } else {
        if (empty($student_name)) $errors[] = 'Vui lòng nhập họ tên con của bạn!';
        if (empty($student_class)) $errors[] = 'Vui lòng nhập lớp của con!';
    }

    // 2. Kiểm tra xem số điện thoại đã đăng ký chưa
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT `passport_code`, `fullname` FROM `passports` WHERE `phone` = ? LIMIT 1");
        $stmt->execute([$phone]);
        $existing_user = $stmt->fetch();
        
        if ($existing_user) {
            $errors[] = 'Số điện thoại này đã được đăng ký bởi <strong>' . htmlspecialchars($existing_user['fullname']) . '</strong>. <a href="passport.php?code=' . $existing_user['passport_code'] . '" style="color: var(--student-primary); font-weight: bold; text-decoration: underline;">Bấm vào đây để xem Passport</a>';
        }
    }

    // 3. Xử lý tải lên ảnh đại diện (Avatar)
    $avatar_path = 'uploads/default.png'; // Mặc định
    
    if (empty($errors) && isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['avatar']['tmp_name'];
        $file_name = $_FILES['avatar']['name'];
        $file_size = $_FILES['avatar']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($file_ext, $allowed_exts)) {
            $errors[] = 'Định dạng ảnh không hợp lệ! Chỉ chấp nhận JPG, JPEG, PNG, GIF, WEBP.';
        } elseif ($file_size > 5 * 1024 * 1024) { // Giới hạn 5MB
            $errors[] = 'Kích thước ảnh quá lớn! Vui lòng tải ảnh dưới 5MB.';
        } else {
            // Tạo tên file ngẫu nhiên duy nhất để tránh trùng lặp
            $new_file_name = 'avatar_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
            $upload_dir = 'uploads/';
            
            if (move_uploaded_file($file_tmp, $upload_dir . $new_file_name)) {
                $avatar_path = $upload_dir . $new_file_name;
            } else {
                $errors[] = 'Có lỗi xảy ra khi lưu trữ ảnh đại diện. Vui lòng thử lại!';
            }
        }
    }

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
            <a href="index.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Quay lại</a>
        </nav>
    </header>

    <!-- Main Content -->
    <main>
        <div class="form-container">
            <div class="glass-panel" style="position: relative; overflow: hidden;">
                <!-- Decor Glow -->
                <div id="form-glow" style="position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; border-radius: 50%; background: rgba(0, 240, 255, 0.15); filter: blur(30px); pointer-events: none; transition: var(--transition);"></div>

                <div style="text-align: center; margin-bottom: 2rem;">
                    <h2 style="font-size: 2rem;" id="form-title">Đăng Ký <span class="text-gradient-student">Passport Học Sinh</span></h2>
                    <p style="color: var(--text-muted); margin-top: 0.5rem;">Vui lòng điền thông tin chính xác để hệ thống tạo Passport điện tử.</p>
                </div>

                <!-- Hiển thị lỗi nếu có -->
                <?php if (!empty($errors)): ?>
                    <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #f87171; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; font-size: 0.95rem;">
                        <ul style="list-style-position: inside; display: flex; flex-direction: column; gap: 0.5rem;">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Form -->
                <form action="register.php" method="POST" enctype="multipart/form-data">
                    
                    <!-- Lựa chọn đối tượng (Role) -->
                    <div class="form-group">
                        <label class="form-label">Bạn là ai?</label>
                        <div class="role-selector">
                            <label class="role-option active-student" id="option-student">
                                <input type="radio" name="role" value="student" checked onclick="switchRole('student')">
                                <span class="icon"><i class="fa-solid fa-graduation-cap"></i></span>
                                <span class="title">Học Sinh</span>
                                <span class="desc">Thẻ Xanh Neon năng động</span>
                            </label>
                            <label class="role-option" id="option-parent">
                                <input type="radio" name="role" value="parent" onclick="switchRole('parent')">
                                <span class="icon"><i class="fa-solid fa-user-group"></i></span>
                                <span class="title">Phụ Huynh</span>
                                <span class="desc">Thẻ Vàng Amber ấm áp</span>
                            </label>
                        </div>
                    </div>

                    <!-- Họ và tên -->
                    <div class="form-group">
                        <label class="form-label" id="label-fullname"><i class="fa-solid fa-user"></i> Họ và Tên Học Sinh</label>
                        <input type="text" name="fullname" class="form-control" placeholder="Nhập họ và tên đầy đủ..." required value="<?php echo htmlspecialchars($_POST['fullname'] ?? ''); ?>">
                    </div>

                    <!-- Số điện thoại -->
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-phone"></i> Số Điện Thoại Liên Hệ</label>
                        <input type="tel" name="phone" class="form-control" placeholder="Nhập số điện thoại để tra cứu lại sau..." required value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>

                    <!-- Trường thông tin thay đổi theo đối tượng -->
                    <!-- 1. Lớp học (dành cho Học sinh hoặc lớp của Con phụ huynh) -->
                    <div class="form-group" id="group-class">
                        <label class="form-label" id="label-class"><i class="fa-solid fa-school"></i> Lớp Học</label>
                        <input type="text" name="student_class" class="form-control" placeholder="Ví dụ: 10A1, 12A5..." value="<?php echo htmlspecialchars($_POST['student_class'] ?? ''); ?>">
                    </div>

                    <!-- 2. Tên của con (Chỉ hiện khi là Phụ huynh) -->
                    <div class="form-group" id="group-student-name" style="display: none;">
                        <label class="form-label"><i class="fa-solid fa-child"></i> Họ và Tên Con (Học sinh)</label>
                        <input type="text" name="student_name" class="form-control" placeholder="Nhập họ tên đầy đủ của con..." value="<?php echo htmlspecialchars($_POST['student_name'] ?? ''); ?>">
                    </div>

                    <!-- Upload ảnh chân dung -->
                    <div class="form-group">
                        <label class="form-label"><i class="fa-solid fa-camera"></i> Ảnh Chân Dung (Chụp rõ mặt)</label>
                        <div class="avatar-upload-wrapper">
                            <div class="avatar-preview-box">
                                <img id="avatar-preview" src="uploads/default.png" alt="Ảnh xem trước">
                            </div>
                            <div class="upload-btn-wrapper">
                                <button type="button" class="btn btn-secondary" onclick="document.getElementById('avatar-input').click()" style="margin-bottom: 0.5rem; width: 100%;">
                                    <i class="fa-solid fa-cloud-arrow-up"></i> Chọn ảnh chân dung
                                </button>
                                <input type="file" id="avatar-input" name="avatar" accept="image/*" style="display: none;" onchange="previewImage(this)">
                                <p style="font-size: 0.75rem; color: var(--text-muted);">Hỗ trợ JPG, PNG, WEBP. Dung lượng tối đa 5MB. Không chọn sẽ dùng avatar mặc định.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Nút Submit -->
                    <button type="submit" class="btn btn-primary" id="btn-submit" style="width: 100%; padding: 1rem; font-size: 1.1rem; margin-top: 1.5rem;">
                        <i class="fa-solid fa-qrcode"></i> Tạo Passport Của Tôi
                    </button>
                </form>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer>
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
            const labelClass = document.getElementById('label-class');
            const groupStudentName = document.getElementById('group-student-name');
            const btnSubmit = document.getElementById('btn-submit');

            if (role === 'student') {
                // Active Học sinh
                optStudent.classList.add('active-student');
                optParent.classList.remove('active-parent');
                
                // Đổi màu Glow và Title
                formGlow.style.background = 'rgba(0, 240, 255, 0.15)';
                formTitle.innerHTML = 'Đăng Ký <span class="text-gradient-student">Passport Học Sinh</span>';
                
                // Thay đổi Label và ẩn/hiện trường thông tin
                labelFullname.innerHTML = '<i class="fa-solid fa-user"></i> Họ và Tên Học Sinh';
                labelClass.innerHTML = '<i class="fa-solid fa-school"></i> Lớp Học';
                groupStudentName.style.display = 'none';
                
                // Thay đổi nút submit
                btnSubmit.className = 'btn btn-primary btn-accent-student';
            } else {
                // Active Phụ huynh
                optStudent.classList.remove('active-student');
                optParent.classList.add('active-parent');
                
                // Đổi màu Glow và Title
                formGlow.style.background = 'rgba(255, 170, 0, 0.15)';
                formTitle.innerHTML = 'Đăng Ký <span class="text-gradient-parent">Passport Phụ Huynh</span>';
                
                // Thay đổi Label và ẩn/hiện trường thông tin
                labelFullname.innerHTML = '<i class="fa-solid fa-user"></i> Họ và Tên Phụ Huynh';
                labelClass.innerHTML = '<i class="fa-solid fa-school"></i> Lớp Học Của Con';
                groupStudentName.style.display = 'block';
                
                // Thay đổi nút submit
                btnSubmit.className = 'btn btn-primary btn-accent-parent';
            }
        }

        // Xem trước ảnh chân dung sau khi chọn file
        function previewImage(input) {
            const preview = document.getElementById('avatar-preview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.src = 'uploads/default.png';
            }
        }

        // Tự động chạy khi khởi chạy để đồng bộ hóa nếu ấn nút F5 có lưu dữ liệu POST cũ
        window.addEventListener('DOMContentLoaded', () => {
            const selectedRole = document.querySelector('input[name="role"]:checked').value;
            switchRole(selectedRole);
        });
    </script>
</body>
</html>
