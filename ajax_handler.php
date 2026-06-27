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
        if (!empty($fullname)) {
            $fullname = mb_convert_case($fullname, MB_CASE_TITLE, "UTF-8");
        }
        $role = trim($_POST['role'] ?? 'student');
        $phone = trim($_POST['phone'] ?? '');
        $student_class = ''; // Lược bỏ theo yêu cầu tối giản
        $student_name = ''; // Lược bỏ theo yêu cầu tối giản

        if ($id <= 0 || empty($fullname) || empty($phone)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ các thông tin bắt buộc!']);
            exit;
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

            // Đồng bộ ra file database.csv
            syncDatabaseToCsv($pdo);

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

            // Đồng bộ ra file database.csv
            syncDatabaseToCsv($pdo);

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
        if (!empty($fullname)) {
            $fullname = mb_convert_case($fullname, MB_CASE_TITLE, "UTF-8");
        }
        $role = trim($_POST['role'] ?? 'student');
        $phone = trim($_POST['phone'] ?? '');
        $student_class = ''; // Lược bỏ theo yêu cầu tối giản
        $student_name = ''; // Lược bỏ theo yêu cầu tối giản

        if (empty($fullname) || empty($phone)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập họ tên và số điện thoại!']);
            exit;
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

            // Đồng bộ ra file database.csv
            syncDatabaseToCsv($pdo);

            echo json_encode(['success' => true, 'message' => 'Thêm mới thành viên thành công!', 'code' => $passport_code]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi database: ' . $e->getMessage()]);
        }
        break;

    // 5. THEO DÕI CẬP NHẬT THỜI GIAN THỰC (REAL-TIME POLLING)
    case 'poll_updates':
        $last_id = intval($_GET['last_id'] ?? 0);
        
        // Lấy tất cả bản ghi mới đăng ký có ID lớn hơn last_id và định dạng sẵn ngày giờ Việt Nam
        $stmt = $pdo->prepare("SELECT *, DATE_FORMAT(`created_at`, '%d/%m %H:%i') as `formatted_date` FROM `passports` WHERE `id` > ? ORDER BY `id` ASC");
        $stmt->execute([$last_id]);
        $new_records = $stmt->fetchAll();
        
        // Cập nhật lại họ tên viết hoa chuẩn cho các bản ghi mới trả về
        foreach ($new_records as &$rec) {
            $rec['fullname'] = mb_convert_case($rec['fullname'], MB_CASE_TITLE, "UTF-8");
        }
        
        // Lấy lại thống kê tổng hợp mới nhất để đồng bộ số liệu
        $total_count = $pdo->query("SELECT COUNT(*) FROM `passports`")->fetchColumn();
        $student_count = $pdo->query("SELECT COUNT(*) FROM `passports` WHERE `role` = 'student'")->fetchColumn();
        $parent_count = $pdo->query("SELECT COUNT(*) FROM `passports` WHERE `role` = 'parent'")->fetchColumn();
        
        echo json_encode([
            'success' => true,
            'new_records' => $new_records,
            'stats' => [
                'total' => intval($total_count),
                'student' => intval($student_count),
                'parent' => intval($parent_count)
            ]
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ!']);
        break;
}
?>
