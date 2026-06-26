<?php
/**
 * QRvaPassPort - AJAX API Handler (Admin CRUD)
 * Antigravity - Premium AI Developer
 */
require_once 'config.php';
session_start();

// Thiết lập phản hồi JSON
header('Content-Type: application/json; charset=utf-8');

// Kiểm tra quyền Admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện hành động này!']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    
    // 1. LẤY THÔNG TIN CHI TIẾT ĐỂ SỬA (GET)
    case 'get':
        $id = intval($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID không hợp lệ!']);
            exit;
        }
        
        $stmt = $pdo->prepare("SELECT * FROM `passports` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$id]);
        $data = $stmt->fetch();
        
        if ($data) {
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin thành viên!']);
        }
        break;

    // 2. CẬP NHẬT THÔNG TIN (UPDATE)
    case 'update':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Phương thức yêu cầu không hợp lệ!']);
            exit;
        }

        $id = intval($_POST['id'] ?? 0);
        $fullname = trim($_POST['fullname'] ?? '');
        $role = trim($_POST['role'] ?? 'student');
        $phone = trim($_POST['phone'] ?? '');
        $student_class = trim($_POST['student_class'] ?? '');
        $student_name = trim($_POST['student_name'] ?? '');

        if ($id <= 0 || empty($fullname) || empty($phone)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ các thông tin bắt buộc!']);
            exit;
        }

        if ($role === 'student') {
            $student_name = null;
        }

        try {
            // Kiểm tra trùng số điện thoại với người khác
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM `passports` WHERE `phone` = ? AND `id` != ?");
            $stmt->execute([$phone, $id]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'Số điện thoại này đã được sử dụng bởi thành viên khác!']);
                exit;
            }

            // Thực hiện cập nhật
            $stmt = $pdo->prepare("
                UPDATE `passports` 
                SET `fullname` = ?, `role` = ?, `student_name` = ?, `student_class` = ?, `phone` = ? 
                WHERE `id` = ?
            ");
            $stmt->execute([$fullname, $role, $student_name, $student_class, $phone, $id]);

            echo json_encode(['success' => true, 'message' => 'Cập nhật thông tin thành công!']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi database: ' . $e->getMessage()]);
        }
        break;

    // 3. XÓA BẢN GHI (DELETE)
    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Phương thức yêu cầu không hợp lệ!']);
            exit;
        }

        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID không hợp lệ!']);
            exit;
        }

        try {
            // Lấy đường dẫn avatar của bản ghi trước khi xóa để dọn dẹp file
            $stmt = $pdo->prepare("SELECT `avatar` FROM `passports` WHERE `id` = ? LIMIT 1");
            $stmt->execute([$id]);
            $avatar = $stmt->fetchColumn();

            // Thực hiện xóa bản ghi
            $stmt = $pdo->prepare("DELETE FROM `passports` WHERE `id` = ?");
            $stmt->execute([$id]);

            // Nếu xóa thành công và file avatar không phải mặc định, thực hiện xóa file trên ổ đĩa
            if ($avatar && $avatar !== 'uploads/default.png' && file_exists($avatar)) {
                @unlink($avatar);
            }

            echo json_encode(['success' => true, 'message' => 'Đã xóa Passport thành công!']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi database: ' . $e->getMessage()]);
        }
        break;

    // 4. THÊM MỚI THỦ CÔNG TỪ ADMIN (ADD)
    case 'add':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Phương thức yêu cầu không hợp lệ!']);
            exit;
        }

        $fullname = trim($_POST['fullname'] ?? '');
        $role = trim($_POST['role'] ?? 'student');
        $phone = trim($_POST['phone'] ?? '');
        $student_class = trim($_POST['student_class'] ?? '');
        $student_name = trim($_POST['student_name'] ?? '');

        if (empty($fullname) || empty($phone)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập họ tên và số điện thoại!']);
            exit;
        }

        if ($role === 'student') {
            $student_name = null;
        }

        try {
            // Kiểm tra trùng số điện thoại
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM `passports` WHERE `phone` = ?");
            $stmt->execute([$phone]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'Số điện thoại này đã được đăng ký!']);
                exit;
            }

            // Sinh mã Passport duy nhất
            $passport_code = generatePassportCode($pdo, $role);
            $avatar_path = 'uploads/default.png'; // Thêm thủ công mặc định dùng avatar mặc định

            // Thực hiện thêm mới
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

            echo json_encode(['success' => true, 'message' => 'Thêm mới thành viên thành công!', 'code' => $passport_code]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi database: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ!']);
        break;
}
?>
