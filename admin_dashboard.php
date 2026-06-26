<?php
/**
 * QRvaPassPort - Admin Dashboard (Analytics, CRUD & Excel Export)
 * Antigravity - Premium AI Developer
 */
require_once 'config.php';
session_start();

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit;
}

// 2. Xử lý Đăng xuất
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['admin_logged_in']);
    unset($_SESSION['admin_username']);
    session_destroy();
    header("Location: admin_login.php");
    exit;
}

// 3. XỬ LÝ XUẤT EXCEL/CSV (Chuẩn UTF-8 BOM chống lỗi font tiếng Việt)
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    ob_clean();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Danh_Sach_Passport_' . date('Ymd_His') . '.csv');
    
    $output = fopen('php://output', 'w');
    // Ghi UTF-8 BOM để Excel hiển thị tiếng Việt chuẩn
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Tiêu đề cột
    fputcsv($output, ['STT', 'Mã Passport', 'Họ và Tên', 'Vai trò', 'Lớp học', 'Họ tên con (nếu có)', 'Số điện thoại', 'Ngày đăng ký']);
    
    $stmt = $pdo->query("SELECT * FROM `passports` ORDER BY `id` DESC");
    $index = 1;
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $index++,
            $row['passport_code'],
            $row['fullname'],
            $row['role'] === 'student' ? 'Học sinh' : 'Phụ huynh',
            $row['student_class'],
            $row['student_name'] ?? '',
            $row['phone'],
            $row['created_at']
        ]);
    }
    fclose($output);
    exit;
}

// 4. LẤY SỐ LIỆU THỐNG KÊ (Tổng quan)
$total_count = $pdo->query("SELECT COUNT(*) FROM `passports`")->fetchColumn();
$student_count = $pdo->query("SELECT COUNT(*) FROM `passports` WHERE `role` = 'student'")->fetchColumn();
$parent_count = $pdo->query("SELECT COUNT(*) FROM `passports` WHERE `role` = 'parent'")->fetchColumn();

// 5. LẤY DỮ LIỆU VẼ BIỂU ĐỒ
// Biểu đồ tròn: Tỷ lệ vai trò
$ratio_data = [
    'student' => intval($student_count),
    'parent' => intval($parent_count)
];

// Biểu đồ đường: Đăng ký theo 10 ngày gần đây
$trend_stmt = $pdo->query("
    SELECT DATE_FORMAT(`created_at`, '%d/%m') as `date`, COUNT(*) as `count` 
    FROM `passports` 
    GROUP BY DATE(`created_at`), DATE_FORMAT(`created_at`, '%d/%m') 
    ORDER BY DATE(`created_at`) ASC 
    LIMIT 10
");
$trend_labels = [];
$trend_values = [];
while ($row = $trend_stmt->fetch()) {
    $trend_labels[] = $row['date'];
    $trend_values[] = intval($row['count']);
}

// Nếu chưa có dữ liệu trend, tạo dữ liệu giả lập cho đẹp mắt
if (empty($trend_labels)) {
    $trend_labels = [date('d/m')];
    $trend_values = [0];
}

// 6. XỬ LÝ TÌM KIẾM, LỌC DANH SÁCH
$search = trim($_GET['search'] ?? '');
$filter_role = trim($_GET['role'] ?? '');
$filter_class = trim($_GET['class'] ?? '');

$where_clauses = [];
$params = [];

if (!empty($search)) {
    $where_clauses[] = "(`fullname` LIKE ? OR `phone` LIKE ? OR `passport_code` LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($filter_role)) {
    $where_clauses[] = "`role` = ?";
    $params[] = $filter_role;
}

if (!empty($filter_class)) {
    $where_clauses[] = "`student_class` = ?";
    $params[] = $filter_class;
}

$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

// Lấy danh sách lớp học để làm bộ lọc dropdown
$classes_list = $pdo->query("SELECT DISTINCT `student_class` FROM `passports` ORDER BY `student_class` ASC")->fetchAll(PDO::FETCH_COLUMN);

// Truy vấn danh sách thành viên sau khi lọc
$query_sql = "SELECT * FROM `passports` $where_sql ORDER BY `id` DESC";
$stmt = $pdo->prepare($query_sql);
$stmt->execute($params);
$passports_list = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Quản Trị - Passport Điện Tử</title>
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
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="min-h-screen flex flex-col antialiased text-slate-800">

    <!-- Header -->
    <header class="sticky top-0 z-50 flex justify-between items-center px-6 py-4 bg-white/75 backdrop-blur-md border-b border-slate-200/80 no-print">
        <a href="admin_dashboard.php" class="flex items-center gap-3 no-underline text-slate-900 group">
            <div class="w-10 h-10 bg-gradient-to-br from-sky-500 to-blue-600 rounded-xl flex items-center justify-center font-extrabold text-white text-lg shadow-md group-hover:scale-105 transition-transform">A</div>
            <div>
                <div class="text-lg font-extrabold tracking-tight leading-none">ADMIN PANEL</div>
                <div class="text-[10px] text-slate-500 font-medium tracking-wider uppercase mt-0.5">Quản lý Passport</div>
            </div>
        </a>
        <nav class="flex gap-3">
            <a href="index.php" class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl font-semibold text-sm bg-slate-100 border border-slate-200 text-slate-700 hover:bg-slate-200/80 hover:border-slate-300 active:bg-slate-100 transition-all duration-300" target="_blank"><i class="fa-solid fa-globe"></i> Xem Trang Chủ</a>
            <a href="admin_dashboard.php?action=logout" class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl font-semibold text-sm bg-red-50 border border-red-100 text-red-600 hover:bg-red-100 active:bg-red-50 transition-all duration-300"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-6 py-10">
        
        <!-- TIÊU ĐỀ DASHBOARD -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-8">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-800 tracking-tight">Hệ Thống Thống Kê & Quản Trị</h1>
                <p class="text-slate-400 text-sm mt-1">Chào mừng trở lại, <span class="font-bold text-slate-700"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>. Xem báo cáo và chỉnh sửa thông tin tại đây.</p>
            </div>
            <div class="flex gap-3 flex-wrap">
                <a href="print_poster.php" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm bg-sky-50 border border-sky-100 text-sky-600 shadow-sm hover:-translate-y-0.5 hover:shadow-md hover:bg-sky-100/60 active:translate-y-0 transition-all duration-300" target="_blank">
                    <i class="fa-solid fa-print"></i> In Poster QR
                </a>
                <a href="admin_dashboard.php?action=export" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm bg-emerald-50 border border-emerald-100 text-emerald-600 shadow-sm hover:-translate-y-0.5 hover:shadow-md hover:bg-emerald-100/60 active:translate-y-0 transition-all duration-300">
                    <i class="fa-solid fa-file-excel"></i> Xuất Excel
                </a>
                <button onclick="openAddModal()" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl font-bold text-sm bg-gradient-to-r from-sky-500 to-blue-600 text-white shadow-md shadow-sky-500/15 hover:-translate-y-0.5 hover:shadow-lg active:translate-y-0 transition-all duration-300">
                    <i class="fa-solid fa-user-plus"></i> Thêm thành viên
                </button>
            </div>
        </div>

        <!-- THẺ THỐNG KÊ NHANH (STAT CARDS) -->
        <section class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Tổng số lượng -->
            <div class="p-6 bg-white/85 backdrop-blur-md border border-slate-200/60 rounded-2xl shadow-md flex justify-between items-center">
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Tổng Đăng Ký</h4>
                    <div class="text-3xl font-extrabold text-slate-800 mt-2" id="stat-total"><?php echo number_format($total_count); ?></div>
                </div>
                <div class="w-12 h-12 bg-slate-50 text-slate-500 rounded-xl flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>

            <!-- Học sinh -->
            <div class="p-6 bg-white/85 backdrop-blur-md border border-sky-100/60 rounded-2xl shadow-md flex justify-between items-center">
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-sky-500">Học Sinh</h4>
                    <div class="text-3xl font-extrabold text-slate-800 mt-2" id="stat-student"><?php echo number_format($student_count); ?></div>
                </div>
                <div class="w-12 h-12 bg-sky-50 text-sky-500 rounded-xl flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
            </div>

            <!-- Phụ huynh -->
            <div class="p-6 bg-white/85 backdrop-blur-md border border-amber-100/60 rounded-2xl shadow-md flex justify-between items-center">
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-amber-600">Phụ Huynh</h4>
                    <div class="text-3xl font-extrabold text-slate-800 mt-2" id="stat-parent"><?php echo number_format($parent_count); ?></div>
                </div>
                <div class="w-12 h-12 bg-amber-50 text-amber-500 rounded-xl flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-user-group"></i>
                </div>
            </div>
        </section>

        <!-- BIỂU ĐỒ BÁO CÁO (CHARTS SECTION) -->
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Biểu đồ tròn: Tỷ lệ -->
            <div class="p-6 bg-white/85 backdrop-blur-md border border-slate-200/60 rounded-2xl shadow-md">
                <h3 class="text-base font-bold text-slate-800 flex items-center gap-2 mb-6">
                    <i class="fa-solid fa-chart-pie text-slate-400"></i> Tỷ Lệ Nhóm Đối Tượng
                </h3>
                <div class="relative h-64 w-full">
                    <canvas id="ratioChart"></canvas>
                </div>
            </div>

            <!-- Biểu đồ đường: Tiến trình đăng ký -->
            <div class="p-6 bg-white/85 backdrop-blur-md border border-slate-200/60 rounded-2xl shadow-md">
                <h3 class="text-base font-bold text-slate-800 flex items-center gap-2 mb-6">
                    <i class="fa-solid fa-chart-line text-slate-400"></i> Tốc Độ Đăng Ký Theo Thời Gian
                </h3>
                <div class="relative h-64 w-full">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </section>

        <!-- BỘ LỌC TÌM KIẾM DANH SÁCH (FILTERS & TABLE) -->
        <section class="p-6 bg-white/85 backdrop-blur-md border border-slate-200/60 rounded-3xl shadow-xl">
            <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2 mb-6">
                <i class="fa-solid fa-list-check text-sky-500"></i> Danh Sách Đăng Ký Chi Tiết
            </h3>
            
            <!-- Panel bộ lọc -->
            <form action="admin_dashboard.php" method="GET" class="flex flex-wrap gap-4 items-center mb-6">
                <!-- Ô tìm kiếm từ khóa -->
                <input type="text" name="search" class="flex-1 min-w-[250px] bg-slate-50 border border-slate-200/80 rounded-xl py-2.5 px-4 text-slate-900 placeholder-slate-400 focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-500/10 focus:bg-white transition-all duration-300 text-sm" placeholder="Tìm tên, số điện thoại, mã Passport..." value="<?php echo htmlspecialchars($search); ?>">
                
                <!-- Lọc theo đối tượng -->
                <select name="role" class="min-w-[150px] bg-slate-50 border border-slate-200/80 rounded-xl py-2.5 px-4 text-slate-900 focus:outline-none focus:border-sky-500 focus:bg-white transition-all duration-300 text-sm">
                    <option value="">-- Đối tượng --</option>
                    <option value="student" <?php echo $filter_role === 'student' ? 'selected' : ''; ?>>Học sinh</option>
                    <option value="parent" <?php echo $filter_role === 'parent' ? 'selected' : ''; ?>>Phụ huynh</option>
                </select>

                <!-- Lọc theo lớp -->
                <select name="class" class="min-w-[130px] bg-slate-50 border border-slate-200/80 rounded-xl py-2.5 px-4 text-slate-900 focus:outline-none focus:border-sky-500 focus:bg-white transition-all duration-300 text-sm">
                    <option value="">-- Chọn lớp --</option>
                    <?php foreach ($classes_list as $cls): ?>
                        <option value="<?php echo htmlspecialchars($cls); ?>" <?php echo $filter_class === $cls ? 'selected' : ''; ?>>
                            Lớp <?php echo htmlspecialchars($cls); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Nút áp dụng bộ lọc -->
                <button type="submit" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl font-bold text-sm bg-slate-100 border border-slate-200 text-slate-700 hover:bg-slate-200/80 transition-all"><i class="fa-solid fa-filter"></i> Lọc</button>
                
                <!-- Nút Reset bộ lọc -->
                <?php if (!empty($search) || !empty($filter_role) || !empty($filter_class)): ?>
                    <a href="admin_dashboard.php" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl font-bold text-sm bg-slate-50 border border-slate-200/80 text-slate-600 hover:bg-slate-100 transition-all"><i class="fa-solid fa-rotate-left"></i> Reset</a>
                <?php endif; ?>
            </form>

            <!-- Bảng dữ liệu (Data Table) -->
            <div class="overflow-x-auto border border-slate-100 rounded-2xl">
                <table class="min-w-full divide-y divide-slate-100 text-left">
                    <thead class="bg-slate-50/50">
                        <tr>
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 tracking-wider uppercase">Ảnh</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 tracking-wider uppercase">Mã Passport</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 tracking-wider uppercase">Họ và Tên</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 tracking-wider uppercase">Đối Tượng</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 tracking-wider uppercase">Lớp</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 tracking-wider uppercase">Thông Tin Con (PH)</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 tracking-wider uppercase">Số Điện Thoại</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 tracking-wider uppercase">Ngày Đăng Ký</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 tracking-wider uppercase text-center">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white/50" id="table-body">
                        <?php if (empty($passports_list)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-slate-400 py-12">
                                    <i class="fa-solid fa-folder-open text-4xl mb-3 block"></i>
                                    Không tìm thấy dữ liệu đăng ký nào phù hợp với bộ lọc!
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($passports_list as $row): ?>
                                <tr class="hover:bg-slate-50/40 transition-colors" id="row-<?php echo $row['id']; ?>">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <img class="w-9 h-9 rounded-full object-cover border border-slate-200" src="<?php echo htmlspecialchars($row['avatar']); ?>" alt="Avatar">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap font-mono font-bold tracking-wide text-slate-800">
                                        <?php echo htmlspecialchars($row['passport_code']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap font-bold text-slate-800">
                                        <?php echo htmlspecialchars($row['fullname']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($row['role'] === 'student'): ?>
                                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-sky-50 border border-sky-100 text-sky-600">Học sinh</span>
                                        <?php else: ?>
                                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 border border-amber-100 text-amber-600">Phụ huynh</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-slate-700 font-semibold">
                                        <?php echo !empty($row['student_class']) ? 'Lớp ' . htmlspecialchars($row['student_class']) : '-'; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-slate-500 font-medium">
                                        <?php echo $row['role'] === 'parent' ? htmlspecialchars($row['student_name']) : '-'; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-slate-700 font-semibold"><?php echo htmlspecialchars($row['phone']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-slate-400 text-xs">
                                        <?php echo date('d/m H:i', strtotime($row['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex gap-2 justify-center">
                                            <!-- Xem Passport -->
                                            <a href="passport.php?code=<?php echo $row['passport_code']; ?>" class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center hover:bg-sky-100 transition-colors" title="Xem Passport" target="_blank">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                            <!-- Chỉnh sửa -->
                                            <button onclick="openEditModal(<?php echo $row['id']; ?>)" class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center hover:bg-emerald-100 transition-colors" title="Chỉnh sửa thông tin">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <!-- Xóa -->
                                            <button onclick="deleteMember(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['fullname']); ?>')" class="w-8 h-8 rounded-lg bg-red-50 text-red-600 flex items-center justify-center hover:bg-red-100 transition-colors" title="Xóa Passport">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- ============================================== -->
    <!-- 1. MODAL CHỈNH SỬA THÔNG TIN (EDIT MODAL) -->
    <div id="editModal" class="modal">
        <div class="bg-white border border-slate-200 rounded-3xl p-8 max-w-lg w-full mx-4 shadow-2xl relative animate-[scaleIn_0.3s_ease]">
            <button class="absolute top-5 right-5 text-slate-400 hover:text-slate-600 text-2xl font-bold outline-none" onclick="closeEditModal()">&times;</button>
            <div class="mb-6">
                <h3 class="text-xl font-extrabold text-slate-800"><i class="fa-solid fa-user-pen text-sky-500 mr-1.5"></i> Chỉnh Sửa Passport</h3>
                <p class="text-slate-400 text-xs mt-1">Mã Passport: <strong id="edit-passport-code" class="text-slate-700"></strong></p>
            </div>
            
            <form id="editForm" onsubmit="submitEditForm(event)" class="space-y-4">
                <input type="hidden" name="id" id="edit-id">
                
                <!-- Họ tên -->
                <div>
                    <label class="block font-semibold text-slate-700 text-sm mb-2">Họ và Tên</label>
                    <input type="text" name="fullname" id="edit-fullname" class="w-full bg-slate-50 border border-slate-200/80 rounded-2xl py-3 px-4 text-slate-900 focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-500/10 focus:bg-white transition-all duration-300 text-sm" required>
                </div>

                <!-- Vai trò -->
                <div>
                    <label class="block font-semibold text-slate-700 text-sm mb-2">Đối Tượng</label>
                    <select name="role" id="edit-role" class="w-full bg-slate-50 border border-slate-200/80 rounded-2xl py-3 px-4 text-slate-900 focus:outline-none focus:border-sky-500 focus:bg-white transition-all duration-300 text-sm" onchange="toggleEditModalFields(this.value)" required>
                        <option value="student">Học sinh</option>
                        <option value="parent">Phụ huynh</option>
                    </select>
                </div>

                <!-- Lớp học (Ẩn/Hiện bằng JS nhưng vẫn gửi lên nếu cần, tuy nhiên đã lược bỏ lớp lúc đăng ký, ta có thể để trống hoặc cho sửa lớp) -->
                <div>
                    <label class="block font-semibold text-slate-700 text-sm mb-2" id="edit-label-class">Lớp Học</label>
                    <input type="text" name="student_class" id="edit-class" class="w-full bg-slate-50 border border-slate-200/80 rounded-2xl py-3 px-4 text-slate-900 focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-500/10 focus:bg-white transition-all duration-300 text-sm">
                </div>

                <!-- Tên con (Chỉ hiện khi là Phụ huynh) -->
                <div id="edit-group-student-name" style="display: none;">
                    <label class="block font-semibold text-slate-700 text-sm mb-2">Họ và Tên Con</label>
                    <input type="text" name="student_name" id="edit-student-name" class="w-full bg-slate-50 border border-slate-200/80 rounded-2xl py-3 px-4 text-slate-900 focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-500/10 focus:bg-white transition-all duration-300 text-sm">
                </div>

                <!-- Số điện thoại -->
                <div>
                    <label class="block font-semibold text-slate-700 text-sm mb-2">Số Điện Thoại</label>
                    <input type="tel" name="phone" id="edit-phone" class="w-full bg-slate-50 border border-slate-200/80 rounded-2xl py-3 px-4 text-slate-900 focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-500/10 focus:bg-white transition-all duration-300 text-sm" required>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" class="px-5 py-2.5 rounded-xl font-bold text-sm bg-slate-100 border border-slate-200 text-slate-700 hover:bg-slate-200/80 transition-all" onclick="closeEditModal()">Hủy</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl font-bold text-sm bg-gradient-to-r from-sky-500 to-blue-600 text-white shadow-md hover:brightness-105 active:scale-[0.98] transition-all">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- 2. MODAL THÊM MỚI THỦ CÔNG (ADD MODAL) -->
    <div id="addModal" class="modal">
        <div class="bg-white border border-slate-200 rounded-3xl p-8 max-w-lg w-full mx-4 shadow-2xl relative animate-[scaleIn_0.3s_ease]">
            <button class="absolute top-5 right-5 text-slate-400 hover:text-slate-600 text-2xl font-bold outline-none" onclick="closeAddModal()">&times;</button>
            <div class="mb-6">
                <h3 class="text-xl font-extrabold text-slate-800"><i class="fa-solid fa-user-plus text-sky-500 mr-1.5"></i> Thêm Passport Thủ Công</h3>
                <p class="text-slate-400 text-xs mt-1">Tạo nhanh Passport cho khách hoặc trường hợp lỗi</p>
            </div>
            
            <form id="addForm" onsubmit="submitAddForm(event)" class="space-y-4">
                <!-- Họ tên -->
                <div>
                    <label class="block font-semibold text-slate-700 text-sm mb-2">Họ và Tên</label>
                    <input type="text" name="fullname" class="w-full bg-slate-50 border border-slate-200/80 rounded-2xl py-3 px-4 text-slate-900 focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-500/10 focus:bg-white transition-all duration-300 text-sm" placeholder="Nhập họ tên đầy đủ..." required>
                </div>

                <!-- Vai trò -->
                <div>
                    <label class="block font-semibold text-slate-700 text-sm mb-2">Đối Tượng</label>
                    <select name="role" class="w-full bg-slate-50 border border-slate-200/80 rounded-2xl py-3 px-4 text-slate-900 focus:outline-none focus:border-sky-500 focus:bg-white transition-all duration-300 text-sm" onchange="toggleAddModalFields(this.value)" required>
                        <option value="student" selected>Học sinh</option>
                        <option value="parent">Phụ huynh</option>
                    </select>
                </div>

                <!-- Lớp học -->
                <div>
                    <label class="block font-semibold text-slate-700 text-sm mb-2" id="add-label-class">Lớp Học</label>
                    <input type="text" name="student_class" class="w-full bg-slate-50 border border-slate-200/80 rounded-2xl py-3 px-4 text-slate-900 focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-500/10 focus:bg-white transition-all duration-300 text-sm" placeholder="Ví dụ: 11A2...">
                </div>

                <!-- Tên con (Chỉ hiện khi là Phụ huynh) -->
                <div id="add-group-student-name" style="display: none;">
                    <label class="block font-semibold text-slate-700 text-sm mb-2">Họ và Tên Con</label>
                    <input type="text" name="student_name" class="w-full bg-slate-50 border border-slate-200/80 rounded-2xl py-3 px-4 text-slate-900 focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-500/10 focus:bg-white transition-all duration-300 text-sm" placeholder="Nhập họ tên của con...">
                </div>

                <!-- Số điện thoại -->
                <div>
                    <label class="block font-semibold text-slate-700 text-sm mb-2">Số Điện Thoại</label>
                    <input type="tel" name="phone" class="w-full bg-slate-50 border border-slate-200/80 rounded-2xl py-3 px-4 text-slate-900 focus:outline-none focus:border-sky-500 focus:ring-2 focus:ring-sky-500/10 focus:bg-white transition-all duration-300 text-sm" placeholder="Nhập số điện thoại..." required>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" class="px-5 py-2.5 rounded-xl font-bold text-sm bg-slate-100 border border-slate-200 text-slate-700 hover:bg-slate-200/80 transition-all" onclick="closeAddModal()">Hủy</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl font-bold text-sm bg-gradient-to-r from-sky-500 to-blue-600 text-white shadow-md hover:brightness-105 active:scale-[0.98] transition-all">Thêm Mới</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="text-center py-6 border-t border-slate-200/60 mt-auto text-slate-400 text-sm no-print">
        <p>&copy; 2026 Hệ thống Passport Điện Tử Học Đường. Phát triển bởi Antigravity AI.</p>
    </footer>

    <!-- Thư viện Chart.js Offline đã tải về -->
    <script src="assets/js/chart.min.js"></script>

    <!-- Script xử lý biểu đồ và các Modal điều khiển -->
    <script>
        // ----------------------------------------------------
        // I. KHỞI TẠO BIỂU ĐỒ TRỰC QUAN (CHART.JS)
        // 1. Biểu đồ tròn (Tỷ lệ học sinh/phụ huynh)
        const ratioCtx = document.getElementById('ratioChart').getContext('2d');
        const ratioChart = new Chart(ratioCtx, {
            type: 'doughnut',
            data: {
                labels: ['Học Sinh', 'Phụ Huynh'],
                datasets: [{
                    data: [
                        <?php echo $ratio_data['student']; ?>, 
                        <?php echo $ratio_data['parent']; ?>
                    ],
                    backgroundColor: ['#0284c7', '#d97706'],
                    borderColor: '#ffffff',
                    borderWidth: 3,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#64748b',
                            font: { family: 'Outfit', size: 12, weight: 'bold' }
                        }
                    }
                }
            }
        });

        // 2. Biểu đồ đường (Tốc độ đăng ký theo ngày)
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        const trendChart = new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($trend_labels); ?>,
                datasets: [{
                    label: 'Số lượng đăng ký',
                    data: <?php echo json_encode($trend_values); ?>,
                    borderColor: '#0284c7',
                    backgroundColor: 'rgba(2, 132, 199, 0.05)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#0284c7',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(0, 0, 0, 0.04)' },
                        ticks: { color: '#64748b', font: { family: 'Outfit' } }
                    },
                    y: {
                        grid: { color: 'rgba(0, 0, 0, 0.04)' },
                        ticks: { 
                            color: '#64748b', 
                            font: { family: 'Outfit' },
                            stepSize: 1, // Đảm bảo số nguyên
                            beginAtZero: true 
                        }
                    }
                }
            }
        });

        // ----------------------------------------------------
        // II. ĐIỀU KHIỂN MODALS & THAO TÁC CRUD
        
        // --- 1. MODAL CHỈNH SỬA (EDIT) ---
        function toggleEditModalFields(role) {
            const groupStudentName = document.getElementById('edit-group-student-name');
            const labelClass = document.getElementById('edit-label-class');
            if (role === 'student') {
                groupStudentName.style.display = 'none';
                labelClass.textContent = 'Lớp Học';
            } else {
                groupStudentName.style.display = 'block';
                labelClass.textContent = 'Lớp Học Của Con';
            }
        }

        function openEditModal(id) {
            // Gọi AJAX lấy thông tin chi tiết
            fetch('ajax_handler.php?action=get&id=' + id)
                .then(res => res.json())
                .then(response => {
                    if (response.success) {
                        const data = response.data;
                        document.getElementById('edit-id').value = data.id;
                        document.getElementById('edit-fullname').value = data.fullname;
                        document.getElementById('edit-role').value = data.role;
                        document.getElementById('edit-class').value = data.student_class || '';
                        document.getElementById('edit-student-name').value = data.student_name || '';
                        document.getElementById('edit-phone').value = data.phone;
                        document.getElementById('edit-passport-code').textContent = data.passport_code;
                        
                        // Chỉnh sửa hiển thị các trường tùy theo vai trò
                        toggleEditModalFields(data.role);
                        
                        // Hiển thị modal
                        document.getElementById('editModal').classList.add('show');
                    } else {
                        alert(response.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Không thể kết nối đến máy chủ!');
                });
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('show');
        }

        function submitEditForm(event) {
            event.preventDefault();
            const form = document.getElementById('editForm');
            const formData = new FormData(form);

            fetch('ajax_handler.php?action=update', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(response => {
                if (response.success) {
                    alert(response.message);
                    closeEditModal();
                    location.reload(); // Tải lại trang để cập nhật bảng và biểu đồ
                } else {
                    alert(response.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert('Có lỗi xảy ra khi cập nhật thông tin!');
            });
        }

        // --- 2. MODAL THÊM MỚI (ADD) ---
        function toggleAddModalFields(role) {
            const groupStudentName = document.getElementById('add-group-student-name');
            const labelClass = document.getElementById('add-label-class');
            if (role === 'student') {
                groupStudentName.style.display = 'none';
                labelClass.textContent = 'Lớp Học';
            } else {
                groupStudentName.style.display = 'block';
                labelClass.textContent = 'Lớp Học Của Con';
            }
        }

        function openAddModal() {
            document.getElementById('addForm').reset();
            toggleAddModalFields('student');
            document.getElementById('addModal').classList.add('show');
        }

        // Đóng Add Modal bằng click bên ngoài
        window.onclick = function(event) {
            const editModal = document.getElementById('editModal');
            const addModal = document.getElementById('addModal');
            if (event.target == editModal) {
                closeEditModal();
            }
            if (event.target == addModal) {
                closeAddModal();
            }
        }

        function closeAddModal() {
            document.getElementById('addModal').classList.remove('show');
        }

        function submitAddForm(event) {
            event.preventDefault();
            const form = document.getElementById('addForm');
            const formData = new FormData(form);

            fetch('ajax_handler.php?action=add', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(response => {
                if (response.success) {
                    alert(response.message);
                    closeAddModal();
                    location.reload(); // Tải lại trang
                } else {
                    alert(response.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert('Có lỗi xảy ra khi thêm mới thành viên!');
            });
        }

        // --- 3. XÓA BẢN GHI (DELETE) ---
        function deleteMember(id, name) {
            if (confirm('Bạn có chắc chắn muốn xóa Passport của "' + name + '" không?\nHành động này không thể hoàn tác và sẽ dọn dẹp file ảnh trên máy chủ.')) {
                const formData = new FormData();
                formData.append('id', id);

                fetch('ajax_handler.php?action=delete', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(response => {
                    if (response.success) {
                        // Tạo hiệu ứng biến mất (fade out) mượt mà cho dòng bị xóa
                        const row = document.getElementById('row-' + id);
                        row.style.transition = 'all 0.5s ease';
                        row.style.opacity = '0';
                        row.style.transform = 'translateX(50px)';
                        
                        setTimeout(() => {
                            row.remove();
                            // Cập nhật lại số liệu trên các thẻ thống kê nhanh mà không cần F5
                            const totalEl = document.getElementById('stat-total');
                            const studentEl = document.getElementById('stat-student');
                            const parentEl = document.getElementById('stat-parent');
                            
                            // Lấy số hiện tại trừ đi 1
                            let total = parseInt(totalEl.textContent.replace(/,/g, '')) - 1;
                            totalEl.textContent = total.toLocaleString();
                            
                            // Xác định vai trò để trừ
                            const roleBadge = row.querySelector('.badge-role');
                            if (roleBadge && roleBadge.textContent.trim().toLowerCase().includes('học sinh')) {
                                let student = parseInt(studentEl.textContent.replace(/,/g, '')) - 1;
                                studentEl.textContent = student.toLocaleString();
                            } else if (parentEl) {
                                let parent = parseInt(parentEl.textContent.replace(/,/g, '')) - 1;
                                parentEl.textContent = parent.toLocaleString();
                            }
                            
                            // Báo thành công
                            alert(response.message);
                        }, 500);
                    } else {
                        alert(response.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Có lỗi xảy ra khi thực hiện yêu cầu xóa!');
                });
            }
        }
    </script>
</body>
</html>
