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
    GROUP BY DATE(`created_at`) 
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
    <!-- CSS chính -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <!-- Header -->
    <header class="no-print">
        <a href="admin_dashboard.php" class="logo-container">
            <div class="logo-icon">A</div>
            <div>
                <div class="logo-text">ADMIN PANEL</div>
                <div class="logo-sub">Quản lý Passport</div>
            </div>
        </a>
        <nav>
            <a href="index.php" class="btn btn-secondary" target="_blank"><i class="fa-solid fa-globe"></i> Xem Trang Chủ</a>
            <a href="admin_dashboard.php?action=logout" class="btn btn-secondary" style="background: rgba(239, 68, 68, 0.1); color: #f87171; border-color: rgba(239, 68, 68, 0.2);"><i class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a>
        </nav>
    </header>

    <!-- Main Content -->
    <main style="max-width: 1300px;">
        
        <!-- TIÊU ĐỀ DASHBOARD -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 2.2rem;">Hệ Thống Thống Kê & Quản Trị</h1>
                <p style="color: var(--text-muted);">Chào mừng trở lại, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>. Xem báo cáo và chỉnh sửa thông tin tại đây.</p>
            </div>
            <div style="display: flex; gap: 0.8rem;">
                <a href="admin_dashboard.php?action=export" class="btn btn-secondary" style="background: rgba(16, 185, 129, 0.1); color: #34d399; border-color: rgba(16, 185, 129, 0.2);">
                    <i class="fa-solid fa-file-excel"></i> Xuất danh sách Excel
                </a>
                <button onclick="openAddModal()" class="btn btn-primary">
                    <i class="fa-solid fa-user-plus"></i> Thêm thành viên mới
                </button>
            </div>
        </div>

        <!-- THẺ THỐNG KÊ NHANH (STAT CARDS) -->
        <section class="dashboard-grid">
            <!-- Tổng số lượng -->
            <div class="glass-panel stat-card">
                <div class="stat-info">
                    <h4>Tổng Đăng Ký</h4>
                    <div class="value" id="stat-total"><?php echo number_format($total_count); ?></div>
                </div>
                <div class="stat-icon stat-icon-total">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>

            <!-- Học sinh -->
            <div class="glass-panel stat-card" style="border-color: rgba(0, 240, 255, 0.2);">
                <div class="stat-info">
                    <h4>Học Sinh</h4>
                    <div class="value" id="stat-student"><?php echo number_format($student_count); ?></div>
                </div>
                <div class="stat-icon stat-icon-student">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
            </div>

            <!-- Phụ huynh -->
            <div class="glass-panel stat-card" style="border-color: rgba(255, 170, 0, 0.2);">
                <div class="stat-info">
                    <h4>Phụ Huynh</h4>
                    <div class="value" id="stat-parent"><?php echo number_format($parent_count); ?></div>
                </div>
                <div class="stat-icon stat-icon-parent">
                    <i class="fa-solid fa-user-group"></i>
                </div>
            </div>
        </section>

        <!-- BIỂU ĐỒ BÁO CÁO (CHARTS SECTION) -->
        <section class="charts-grid">
            <!-- Biểu đồ tròn: Tỷ lệ -->
            <div class="glass-panel chart-card">
                <h3 style="font-size: 1.1rem; margin-bottom: 1.5rem;"><i class="fa-solid fa-chart-pie"></i> Tỷ Lệ Nhóm Đối Tượng</h3>
                <div class="chart-container">
                    <canvas id="ratioChart"></canvas>
                </div>
            </div>

            <!-- Biểu đồ đường: Tiến trình đăng ký -->
            <div class="glass-panel chart-card">
                <h3 style="font-size: 1.1rem; margin-bottom: 1.5rem;"><i class="fa-solid fa-chart-line"></i> Tốc Độ Đăng Ký Theo Thời Gian</h3>
                <div class="chart-container">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </section>

        <!-- BỘ LỌC TÌM KIẾM DANH SÁCH (FILTERS & TABLE) -->
        <section class="glass-panel">
            <h3 style="margin-bottom: 1.5rem; font-size: 1.3rem;"><i class="fa-solid fa-list-check text-gradient-student"></i> Danh Sách Đăng Ký Chi Tiết</h3>
            
            <!-- Panel bộ lọc -->
            <form action="admin_dashboard.php" method="GET" class="filters-panel">
                <!-- Ô tìm kiếm từ khóa -->
                <input type="text" name="search" class="filter-control" style="flex-grow: 1; min-width: 250px;" placeholder="Tìm tên, số điện thoại, mã Passport..." value="<?php echo htmlspecialchars($search); ?>">
                
                <!-- Lọc theo đối tượng -->
                <select name="role" class="filter-control" style="min-width: 150px;">
                    <option value="">-- Chọn đối tượng --</option>
                    <option value="student" <?php echo $filter_role === 'student' ? 'selected' : ''; ?>>Học sinh</option>
                    <option value="parent" <?php echo $filter_role === 'parent' ? 'selected' : ''; ?>>Phụ huynh</option>
                </select>

                <!-- Lọc theo lớp -->
                <select name="class" class="filter-control" style="min-width: 130px;">
                    <option value="">-- Chọn lớp --</option>
                    <?php foreach ($classes_list as $cls): ?>
                        <option value="<?php echo htmlspecialchars($cls); ?>" <?php echo $filter_class === $cls ? 'selected' : ''; ?>>
                            Lớp <?php echo htmlspecialchars($cls); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Nút áp dụng bộ lọc -->
                <button type="submit" class="btn btn-secondary"><i class="fa-solid fa-filter"></i> Lọc</button>
                
                <!-- Nút Reset bộ lọc -->
                <?php if (!empty($search) || !empty($filter_role) || !empty($filter_class)): ?>
                    <a href="admin_dashboard.php" class="btn btn-secondary" style="background: rgba(255, 255, 255, 0.02);"><i class="fa-solid fa-rotate-left"></i> Reset</a>
                <?php endif; ?>
            </form>

            <!-- Bảng dữ liệu (Data Table) -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Ảnh</th>
                            <th>Mã Passport</th>
                            <th>Họ và Tên</th>
                            <th>Đối Tượng</th>
                            <th>Lớp</th>
                            <th>Thông Tin Con (PH)</th>
                            <th>Số Điện Thoại</th>
                            <th>Ngày Đăng Ký</th>
                            <th style="text-align: center;">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody id="table-body">
                        <?php if (empty($passports_list)): ?>
                            <tr>
                                <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 3rem;">
                                    <i class="fa-solid fa-folder-open" style="font-size: 2.5rem; margin-bottom: 1rem; display: block;"></i>
                                    Không tìm thấy dữ liệu đăng ký nào phù hợp với bộ lọc!
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($passports_list as $row): ?>
                                <tr id="row-<?php echo $row['id']; ?>">
                                    <td>
                                        <img class="table-avatar" src="<?php echo htmlspecialchars($row['avatar']); ?>" alt="Avatar">
                                    </td>
                                    <td style="font-family: monospace; font-weight: 700; letter-spacing: 0.5px;">
                                        <?php echo htmlspecialchars($row['passport_code']); ?>
                                    </td>
                                    <td style="font-weight: 600;">
                                        <?php echo htmlspecialchars($row['fullname']); ?>
                                    </td>
                                    <td>
                                        <span class="badge-role badge-role-<?php echo $row['role']; ?>">
                                            <?php echo $row['role'] === 'student' ? 'Học sinh' : 'Phụ huynh'; ?>
                                        </span>
                                    </td>
                                    <td><strong>Lớp <?php echo htmlspecialchars($row['student_class']); ?></strong></td>
                                    <td style="color: #cbd5e1; font-size: 0.85rem;">
                                        <?php echo $row['role'] === 'parent' ? htmlspecialchars($row['student_name']) : '-'; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                    <td style="color: var(--text-muted); font-size: 0.85rem;">
                                        <?php echo date('d/m H:i', strtotime($row['created_at'])); ?>
                                    </td>
                                    <td>
                                        <div class="action-btns" style="justify-content: center;">
                                            <!-- Xem Passport -->
                                            <a href="passport.php?code=<?php echo $row['passport_code']; ?>" class="btn-icon btn-view" title="Xem Passport" target="_blank">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                            <!-- Chỉnh sửa -->
                                            <button onclick="openEditModal(<?php echo $row['id']; ?>)" class="btn-icon btn-edit" title="Chỉnh sửa thông tin">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <!-- Xóa -->
                                            <button onclick="deleteMember(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['fullname']); ?>')" class="btn-icon btn-delete" title="Xóa Passport">
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
        <div class="modal-content">
            <button class="modal-close" onclick="closeEditModal()">&times;</button>
            <div class="modal-header">
                <h3><i class="fa-solid fa-user-pen text-gradient-student"></i> Chỉnh Sửa Thông Tin Passport</h3>
                <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.2rem;">Mã Passport: <strong id="edit-passport-code" style="color: #fff;"></strong></p>
            </div>
            
            <form id="editForm" onsubmit="submitEditForm(event)">
                <input type="hidden" name="id" id="edit-id">
                
                <!-- Họ tên -->
                <div class="form-group">
                    <label class="form-label">Họ và Tên</label>
                    <input type="text" name="fullname" id="edit-fullname" class="form-control" required>
                </div>

                <!-- Vai trò -->
                <div class="form-group">
                    <label class="form-label">Đối Tượng</label>
                    <select name="role" id="edit-role" class="form-control" onchange="toggleEditModalFields(this.value)" required>
                        <option value="student">Học sinh</option>
                        <option value="parent">Phụ huynh</option>
                    </select>
                </div>

                <!-- Lớp học -->
                <div class="form-group">
                    <label class="form-label" id="edit-label-class">Lớp Học</label>
                    <input type="text" name="student_class" id="edit-class" class="form-control" required>
                </div>

                <!-- Tên con (Chỉ hiện khi là Phụ huynh) -->
                <div class="form-group" id="edit-group-student-name" style="display: none;">
                    <label class="form-label">Họ và Tên Con</label>
                    <input type="text" name="student_name" id="edit-student-name" class="form-control">
                </div>

                <!-- Số điện thoại -->
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="form-label">Số Điện Thoại</label>
                    <input type="tel" name="phone" id="edit-phone" class="form-control" required>
                </div>

                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================== -->
    <!-- 2. MODAL THÊM MỚI THỦ CÔNG (ADD MODAL) -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeAddModal()">&times;</button>
            <div class="modal-header">
                <h3><i class="fa-solid fa-user-plus text-gradient-student"></i> Thêm Passport Thủ Công</h3>
                <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.2rem;">Tạo nhanh Passport cho khách hoặc trường hợp lỗi</p>
            </div>
            
            <form id="addForm" onsubmit="submitAddForm(event)">
                <!-- Họ tên -->
                <div class="form-group">
                    <label class="form-label">Họ và Tên</label>
                    <input type="text" name="fullname" class="form-control" placeholder="Nhập họ tên đầy đủ..." required>
                </div>

                <!-- Vai trò -->
                <div class="form-group">
                    <label class="form-label">Đối Tượng</label>
                    <select name="role" class="form-control" onchange="toggleAddModalFields(this.value)" required>
                        <option value="student" selected>Học sinh</option>
                        <option value="parent">Phụ huynh</option>
                    </select>
                </div>

                <!-- Lớp học -->
                <div class="form-group">
                    <label class="form-label" id="add-label-class">Lớp Học</label>
                    <input type="text" name="student_class" class="form-control" placeholder="Ví dụ: 11A2..." required>
                </div>

                <!-- Tên con (Chỉ hiện khi là Phụ huynh) -->
                <div class="form-group" id="add-group-student-name" style="display: none;">
                    <label class="form-label">Họ và Tên Con</label>
                    <input type="text" name="student_name" class="form-control" placeholder="Nhập họ tên của con...">
                </div>

                <!-- Số điện thoại -->
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="form-label">Số Điện Thoại</label>
                    <input type="tel" name="phone" class="form-control" placeholder="Nhập số điện thoại..." required>
                </div>

                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Hủy</button>
                    <button type="submit" class="btn btn-primary">Thêm Mới</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="no-print">
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
                    backgroundColor: ['#00f0ff', '#ffaa00'],
                    borderColor: '#0f172a',
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
                            color: '#8a99ad',
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
                    borderColor: '#00f0ff',
                    backgroundColor: 'rgba(0, 240, 255, 0.05)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#00f0ff',
                    pointBorderColor: '#0f172a',
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
                        grid: { color: 'rgba(255, 255, 255, 0.03)' },
                        ticks: { color: '#8a99ad', font: { family: 'Outfit' } }
                    },
                    y: {
                        grid: { color: 'rgba(255, 255, 255, 0.03)' },
                        ticks: { 
                            color: '#8a99ad', 
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
                        document.getElementById('edit-class').value = data.student_class;
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
                            if (roleBadge.classList.contains('badge-role-student')) {
                                let student = parseInt(studentEl.textContent.replace(/,/g, '')) - 1;
                                studentEl.textContent = student.toLocaleString();
                            } else {
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
