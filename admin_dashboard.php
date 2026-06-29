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
    fputcsv($output, ['STT', 'Mã Passport', 'Họ và Tên', 'Vai trò', 'Số điện thoại', 'Ngày đăng ký']);
    
    $stmt = $pdo->query("SELECT * FROM `passports` ORDER BY `id` DESC");
    $index = 1;
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $index++,
            $row['passport_code'],
            $row['fullname'],
            $row['role'] === 'student' ? 'Học sinh' : 'Phụ huynh',
            "\t" . $row['phone'],
            "\t" . $row['created_at']
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

$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

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
    <link rel="stylesheet" href="assets/css/style.css?v=1.0.4">
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
            <div class="p-6 bg-white border border-slate-100 rounded-2xl shadow-md flex justify-between items-center">
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">Tổng Đăng Ký</h4>
                    <div class="text-3xl font-extrabold text-slate-800 mt-2" id="stat-total"><?php echo number_format($total_count); ?></div>
                </div>
                <div class="w-12 h-12 bg-slate-50 text-slate-500 rounded-xl flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>

            <!-- Học sinh -->
            <div class="p-6 bg-white border border-sky-100 rounded-2xl shadow-md flex justify-between items-center">
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-sky-500">Học Sinh</h4>
                    <div class="text-3xl font-extrabold text-slate-800 mt-2" id="stat-student"><?php echo number_format($student_count); ?></div>
                </div>
                <div class="w-12 h-12 bg-sky-50 text-sky-500 rounded-xl flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
            </div>

            <!-- Phụ huynh -->
            <div class="p-6 bg-white border border-amber-100 rounded-2xl shadow-md flex justify-between items-center">
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
            <div class="p-6 bg-white border border-slate-100 rounded-2xl shadow-md">
                <h3 class="text-base font-bold text-slate-800 flex items-center gap-2 mb-6">
                    <i class="fa-solid fa-chart-pie text-slate-400"></i> Tỷ Lệ Nhóm Đối Tượng
                </h3>
                <div class="relative h-64 w-full">
                    <canvas id="ratioChart"></canvas>
                </div>
            </div>

            <!-- Biểu đồ đường: Tiến trình đăng ký -->
            <div class="p-6 bg-white border border-slate-100 rounded-2xl shadow-md">
                <h3 class="text-base font-bold text-slate-800 flex items-center gap-2 mb-6">
                    <i class="fa-solid fa-chart-line text-slate-400"></i> Tốc Độ Đăng Ký Theo Thời Gian
                </h3>
                <div class="relative h-64 w-full">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </section>

        <!-- BỘ LỌC TÌM KIẾM DANH SÁCH (FILTERS & TABLE) -->
        <section class="p-6 bg-white border border-slate-100 rounded-3xl shadow-xl shadow-slate-100/40">
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

                <!-- Nút áp dụng bộ lọc -->
                <button type="submit" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl font-bold text-sm bg-slate-100 border border-slate-200 text-slate-700 hover:bg-slate-200/80 transition-all"><i class="fa-solid fa-filter"></i> Lọc</button>
                
                <!-- Nút Reset bộ lọc -->
                <?php if (!empty($search) || !empty($filter_role)): ?>
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
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 tracking-wider uppercase">Số Điện Thoại</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 tracking-wider uppercase">Ngày Đăng Ký</th>
                            <th class="px-6 py-4 text-xs font-bold text-slate-400 tracking-wider uppercase text-center">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white/50" id="table-body">
                        <?php if (empty($passports_list)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-slate-400 py-12">
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
                                            <span class="badge-role px-2.5 py-1 rounded-full text-xs font-bold bg-sky-50 border border-sky-100 text-sky-600">Học sinh</span>
                                        <?php else: ?>
                                            <span class="badge-role px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 border border-amber-100 text-amber-600">Phụ huynh</span>
                                        <?php endif; ?>
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
                    <select name="role" id="edit-role" class="w-full bg-slate-50 border border-slate-200/80 rounded-2xl py-3 px-4 text-slate-900 focus:outline-none focus:border-sky-500 focus:bg-white transition-all duration-300 text-sm" required>
                        <option value="student">Học sinh</option>
                        <option value="parent">Phụ huynh</option>
                    </select>
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
                    <select name="role" class="w-full bg-slate-50 border border-slate-200/80 rounded-2xl py-3 px-4 text-slate-900 focus:outline-none focus:border-sky-500 focus:bg-white transition-all duration-300 text-sm" required>
                        <option value="student" selected>Học sinh</option>
                        <option value="parent">Phụ huynh</option>
                    </select>
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

    <!-- ============================================== -->
    <!-- 3. MODAL XÁC NHẬN XÓA (DELETE CONFIRM MODAL) -->
    <div id="deleteConfirmModal" class="modal">
        <div class="bg-white border border-slate-200 rounded-3xl p-8 max-w-md w-full mx-4 shadow-2xl relative animate-[scaleIn_0.3s_ease]">
            <button class="absolute top-5 right-5 text-slate-400 hover:text-slate-600 text-2xl font-bold outline-none" onclick="closeDeleteConfirmModal()">&times;</button>
            <div class="text-center">
                <div class="w-16 h-16 bg-rose-50 text-rose-500 rounded-full flex items-center justify-center text-3xl mx-auto mb-4 shadow-inner">
                    <i class="fa-solid fa-triangle-exclamation animate-pulse"></i>
                </div>
                <h3 class="text-xl font-extrabold text-slate-800 mb-2">Xác nhận xóa Passport</h3>
                <p class="text-slate-500 text-sm mb-6 leading-relaxed">
                    Bạn có chắc chắn muốn xóa Passport của <strong id="delete-fullname-text" class="text-slate-800"></strong>?<br>
                    Hành động này không thể hoàn tác và sẽ dọn dẹp file ảnh trên máy chủ.
                </p>
                
                <div class="flex flex-col gap-2.5">
                    <div class="flex gap-3">
                        <button onclick="confirmDeleteAction()" id="delete-btn-confirm" class="flex-1 px-5 py-3 rounded-2xl font-bold text-sm bg-rose-500 text-white shadow-md shadow-rose-500/10 hover:bg-rose-600 active:scale-[0.98] transition-all">
                            Có (<span id="delete-timer">10</span>s)
                        </button>
                        <button onclick="resetDeleteTimer()" id="delete-btn-reset" class="px-5 py-3 rounded-2xl font-bold text-sm bg-slate-100 border border-slate-200 text-slate-700 hover:bg-slate-200 active:scale-[0.98] transition-all" title="Reset đếm ngược 10 giây">
                            <i class="fa-solid fa-rotate-left mr-1"></i> Chọn lại
                        </button>
                    </div>
                    <button type="button" class="w-full py-3 rounded-2xl font-bold text-sm bg-slate-100 text-slate-500 hover:bg-slate-200/80 hover:text-slate-700 active:scale-[0.98] transition-all" onclick="closeDeleteConfirmModal()">Không xóa (Hủy)</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- 4. MODAL XÓA THÀNH CÔNG (DELETE SUCCESS MODAL) -->
    <div id="deleteSuccessModal" class="modal">
        <div class="bg-white border border-slate-200 rounded-3xl p-8 max-w-sm w-full mx-4 shadow-2xl relative animate-[scaleIn_0.3s_ease]">
            <div class="text-center">
                <div class="w-16 h-16 bg-emerald-50 text-emerald-500 rounded-full flex items-center justify-center text-3xl mx-auto mb-4 shadow-inner">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <h3 class="text-xl font-extrabold text-slate-800 mb-1">Đã xóa thành công!</h3>
                <p class="text-slate-500 text-sm" id="delete-success-text"></p>
            </div>
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
                        document.getElementById('edit-phone').value = data.phone;
                        document.getElementById('edit-passport-code').textContent = data.passport_code;
                        
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
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1.5"></i> Đang lưu...';

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
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            })
            .catch(err => {
                console.error(err);
                alert('Có lỗi xảy ra khi cập nhật thông tin!');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        }

        // --- 2. MODAL THÊM MỚI (ADD) ---
        function openAddModal() {
            document.getElementById('addForm').reset();
            document.getElementById('addModal').classList.add('show');
        }

        // Đóng các Modal bằng click bên ngoài
        window.onclick = function(event) {
            const editModal = document.getElementById('editModal');
            const addModal = document.getElementById('addModal');
            const deleteConfirmModal = document.getElementById('deleteConfirmModal');
            if (event.target == editModal) {
                closeEditModal();
            }
            if (event.target == addModal) {
                closeAddModal();
            }
            if (event.target == deleteConfirmModal) {
                closeDeleteConfirmModal();
            }
        }

        function closeAddModal() {
            document.getElementById('addModal').classList.remove('show');
        }

        function submitAddForm(event) {
            event.preventDefault();
            const form = document.getElementById('addForm');
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1.5"></i> Đang thêm...';

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
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            })
            .catch(err => {
                console.error(err);
                alert('Có lỗi xảy ra khi thêm mới thành viên!');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        }

        // --- 3. XÓA BẢN GHI (DELETE) ---
        let deleteId = null;
        let deleteName = "";
        let deleteTimerSeconds = 10;
        let deleteTimerInterval = null;
        let deleteSuccessTimeout = null;

        function deleteMember(id, name) {
            deleteId = id;
            deleteName = name;
            deleteTimerSeconds = 10;

            document.getElementById('delete-fullname-text').textContent = name;
            document.getElementById('delete-timer').textContent = deleteTimerSeconds;

            const deleteModal = document.getElementById('deleteConfirmModal');
            deleteModal.classList.add('show');

            if (deleteTimerInterval) {
                clearInterval(deleteTimerInterval);
            }

            deleteTimerInterval = setInterval(() => {
                deleteTimerSeconds--;
                document.getElementById('delete-timer').textContent = deleteTimerSeconds;

                if (deleteTimerSeconds <= 0) {
                    clearInterval(deleteTimerInterval);
                    deleteTimerInterval = null;
                    confirmDeleteAction();
                }
            }, 1000);
        }

        function resetDeleteTimer() {
            deleteTimerSeconds = 10;
            document.getElementById('delete-timer').textContent = deleteTimerSeconds;
            if (!deleteTimerInterval) {
                deleteTimerInterval = setInterval(() => {
                    deleteTimerSeconds--;
                    document.getElementById('delete-timer').textContent = deleteTimerSeconds;

                    if (deleteTimerSeconds <= 0) {
                        clearInterval(deleteTimerInterval);
                        deleteTimerInterval = null;
                        confirmDeleteAction();
                    }
                }, 1000);
            }
        }

        function closeDeleteConfirmModal() {
            const deleteModal = document.getElementById('deleteConfirmModal');
            deleteModal.classList.remove('show');
            if (deleteTimerInterval) {
                clearInterval(deleteTimerInterval);
                deleteTimerInterval = null;
            }
        }

        function confirmDeleteAction() {
            closeDeleteConfirmModal();

            const formData = new FormData();
            formData.append('id', deleteId);

            fetch('ajax_handler.php?action=delete', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(response => {
                if (response.success) {
                    const row = document.getElementById('row-' + deleteId);
                    if (row) {
                        row.style.transition = 'all 0.5s ease';
                        row.style.opacity = '0';
                        row.style.transform = 'translateX(50px)';
                        
                        setTimeout(() => {
                            row.remove();
                            const totalEl = document.getElementById('stat-total');
                            const studentEl = document.getElementById('stat-student');
                            const parentEl = document.getElementById('stat-parent');
                            
                            let total = parseInt(totalEl.textContent.replace(/,/g, '')) - 1;
                            totalEl.textContent = total.toLocaleString();
                            
                            const roleBadge = row.querySelector('.badge-role');
                            if (roleBadge && roleBadge.textContent.trim().toLowerCase().includes('học sinh')) {
                                let student = parseInt(studentEl.textContent.replace(/,/g, '')) - 1;
                                studentEl.textContent = student.toLocaleString();
                            } else if (parentEl) {
                                let parent = parseInt(parentEl.textContent.replace(/,/g, '')) - 1;
                                parentEl.textContent = parent.toLocaleString();
                            }
                        }, 500);
                    }

                    const successModal = document.getElementById('deleteSuccessModal');
                    document.getElementById('delete-success-text').textContent = 'Passport của "' + deleteName + '" đã được xóa khỏi hệ thống.';
                    successModal.classList.add('show');

                    if (deleteSuccessTimeout) {
                        clearTimeout(deleteSuccessTimeout);
                    }
                    deleteSuccessTimeout = setTimeout(() => {
                        successModal.classList.remove('show');
                    }, 3000);

                } else {
                    alert(response.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert('Có lỗi xảy ra khi thực hiện yêu cầu xóa!');
            });
        }

        // ====================================================
        // III. REAL-TIME AUTO UPDATES (POLLING EVERY 5 SECONDS)
        // Lưu ID lớn nhất hiện tại làm mốc theo dõi
        let lastId = <?php echo !empty($passports_list) ? intval(max(array_column($passports_list, 'id'))) : 0; ?>;
        let isPolling = false;

        function pollNewRegistrations() {
            if (isPolling) return;
            // Chỉ thực hiện poll tự động nếu admin không đang thực hiện tìm kiếm hoặc lọc để tránh gây nhiễu dữ liệu đang xem
            const urlParams = new URLSearchParams(window.location.search);
            const isSearching = urlParams.has('search') && urlParams.get('search').trim() !== '';
            const isFiltering = urlParams.has('role') && urlParams.get('role').trim() !== '';
            
            if (isSearching || isFiltering) {
                return; 
            }

            isPolling = true;
            fetch('ajax_handler.php?action=poll_updates&last_id=' + lastId)
                .then(res => res.json())
                .then(response => {
                    if (response.success && response.new_records && response.new_records.length > 0) {
                        const tableBody = document.getElementById('table-body');
                        
                        // Nếu bảng đang trống (đang hiển thị dòng "Không tìm thấy dữ liệu")
                        if (tableBody.querySelector('td[colspan="7"]')) {
                            tableBody.innerHTML = '';
                        }

                        // Lặp qua các bản ghi mới (mới đăng ký) để chèn lên đầu bảng
                        response.new_records.forEach(row => {
                            // Tránh hiển thị lặp lại cùng một dòng trên giao diện
                            if (document.getElementById('row-' + row.id)) {
                                return;
                            }

                            const tr = document.createElement('tr');
                            tr.className = 'hover:bg-slate-50/40 transition-colors opacity-0 -translate-y-4';
                            tr.id = 'row-' + row.id;
                            tr.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                            
                            const roleBadge = row.role === 'student' 
                                ? '<span class="badge-role px-2.5 py-1 rounded-full text-xs font-bold bg-sky-50 border border-sky-100 text-sky-600">Học sinh</span>'
                                : '<span class="badge-role px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 border border-amber-100 text-amber-600">Phụ huynh</span>';

                            tr.innerHTML = `
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <img class="w-9 h-9 rounded-full object-cover border border-slate-200" src="${escapeHtml(row.avatar)}" alt="Avatar">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-mono font-bold tracking-wide text-slate-800">
                                    ${escapeHtml(row.passport_code)}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap font-bold text-slate-800">
                                    ${escapeHtml(row.fullname)}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    ${roleBadge}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-slate-700 font-semibold">${escapeHtml(row.phone)}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-slate-400 text-xs">
                                    ${escapeHtml(row.formatted_date)}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex gap-2 justify-center">
                                        <!-- Xem Passport -->
                                        <a href="passport.php?code=${row.passport_code}" class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center hover:bg-sky-100 transition-colors" title="Xem Passport" target="_blank">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <!-- Chỉnh sửa -->
                                        <button onclick="openEditModal(${row.id})" class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center hover:bg-emerald-100 transition-colors" title="Chỉnh sửa thông tin">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <!-- Xóa -->
                                        <button onclick="deleteMember(${row.id}, '${escapeJs(row.fullname)}')" class="w-8 h-8 rounded-lg bg-red-50 text-red-600 flex items-center justify-center hover:bg-red-100 transition-colors" title="Xóa Passport">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                </td>
                            `;

                            // Chèn dòng mới lên đầu bảng
                            tableBody.insertBefore(tr, tableBody.firstChild);

                            // Kích hoạt hiệu ứng fade-in & slide-down mượt mà sau 50ms
                            setTimeout(() => {
                                tr.classList.remove('opacity-0', '-translate-y-4');
                            }, 50);

                            // Cập nhật mốc ID lớn nhất
                            if (parseInt(row.id) > lastId) {
                                lastId = parseInt(row.id);
                            }
                        });

                        // 1. Cập nhật thẻ thống kê bằng hiệu ứng tăng số chạy mượt mà (Smooth Animation Counter)
                        animateValue('stat-total', response.stats.total);
                        animateValue('stat-student', response.stats.student);
                        animateValue('stat-parent', response.stats.parent);

                        // 2. Cập nhật Biểu đồ tròn (Doughnut Chart) ngay lập tức
                        if (window.ratioChart) {
                            ratioChart.data.datasets[0].data = [response.stats.student, response.stats.parent];
                            ratioChart.update();
                        }
                    }
                })
                .catch(err => console.error('Lỗi tự động cập nhật Dashboard:', err))
                .finally(() => {
                    isPolling = false;
                });
        }

        // Thiết lập vòng lặp chạy tự động mỗi 5 giây
        setInterval(pollNewRegistrations, 5000);

        // --- HÀM HELPER HỖ TRỢ ---
        // Xử lý chống mã độc XSS khi render dữ liệu từ JSON ra HTML
        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }
        
        // Xử lý escape ký tự trong Javascript (để truyền vào hàm xóa)
        function escapeJs(str) {
            if (!str) return '';
            return str.replace(/'/g, "\\'").replace(/"/g, '\\"');
        }

        // Hàm chạy số mượt mà (Smooth Value Animation)
        function animateValue(id, endVal) {
            const obj = document.getElementById(id);
            if (!obj) return;
            const startVal = parseInt(obj.textContent.replace(/,/g, '')) || 0;
            if (startVal === endVal) return;
            
            const duration = 800; // Thời gian chạy (ms)
            const startTime = performance.now();
            
            function updateNumber(now) {
                const elapsed = now - startTime;
                const progress = Math.min(elapsed / duration, 1);
                // Ease out quad
                const easeProgress = progress * (2 - progress);
                const currentVal = Math.floor(startVal + (endVal - startVal) * easeProgress);
                obj.textContent = currentVal.toLocaleString();
                
                if (progress < 1) {
                    requestAnimationFrame(updateNumber);
                }
            }
            requestAnimationFrame(updateNumber);
        }
    </script>
</body>
</html>
