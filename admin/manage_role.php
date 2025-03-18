<?php
session_start();
include '../includes/db.php';

// Kiểm tra nếu chưa đăng nhập hoặc không phải Admin thì chuyển hướng
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

include '../templates/adminheader.php';

// Xử lý xoá tài khoản 
if (isset($_GET['delete'])) {
    $usernameToDelete = $_GET['delete'];

    // Kiểm tra tài khoản có tồn tại và lấy thông tin quyền từ bảng tbuserinrole
    $stmtCheck = $conn->prepare("SELECT role FROM tbuserinrole WHERE username = ?");
    $stmtCheck->bind_param("s", $usernameToDelete);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();

    if ($resultCheck->num_rows == 0) {
        $_SESSION['error_message'] = "Tài khoản không tồn tại.";
        header("Location: manage_role.php");
        exit;
    }

    $rowCheck = $resultCheck->fetch_assoc();
    $role = $rowCheck['role'];

    // Không cho phép xoá tài khoản Admin
    if ($role === "Admin") {
        $_SESSION['error_message'] = "Không thể xoá tài khoản Admin.";
        header("Location: manage_role.php");
        exit;
    }

    // Kiểm tra nếu tài khoản có giao dịch liên quan (giả sử thông qua bảng tbDonHang)
  
    $stmtTrans = $conn->prepare("SELECT COUNT(*) as count 
                                 FROM tbDonHang d 
                                 JOIN tbkhachhang kh ON d.makhach = kh.makhach 
                                 WHERE kh.username = ?");
    $stmtTrans->bind_param("s", $usernameToDelete);
    $stmtTrans->execute();
    $resultTrans = $stmtTrans->get_result();
    $transCount = $resultTrans->fetch_assoc()['count'];

    if ($transCount > 0) {
        $_SESSION['error_message'] = "Không thể xoá tài khoản này vì có giao dịch liên quan.";
        header("Location: manage_role.php");
        exit;
    }

    // Nếu các kiểm tra đều thỏa mãn, tiến hành xoá tài khoản
    try {
        // Xoá dữ liệu liên quan từ bảng tbuserinrole
        $stmtDel1 = $conn->prepare("DELETE FROM tbuserinrole WHERE username = ?");
        $stmtDel1->bind_param("s", $usernameToDelete);
        $stmtDel1->execute();

        // Xoá dữ liệu từ bảng tbkhachhang (nếu có)
        $stmtDel2 = $conn->prepare("DELETE FROM tbkhachhang WHERE username = ?");
        $stmtDel2->bind_param("s", $usernameToDelete);
        $stmtDel2->execute();

        // Xoá tài khoản từ bảng tbUser
        $stmtDel3 = $conn->prepare("DELETE FROM tbUser WHERE username = ?");
        $stmtDel3->bind_param("s", $usernameToDelete);
        $stmtDel3->execute();

        $_SESSION['success_message'] = "Xoá tài khoản thành công.";
    } catch (mysqli_sql_exception $e) {
        $_SESSION['error_message'] = "Xoá tài khoản thất bại do lỗi hệ thống. Vui lòng thử lại sau.";
    }
    header("Location: manage_role.php");
    exit;
}

// Xử lý cập nhật quyền nếu có yêu cầu
if (isset($_POST['update_role'])) {
    $username = $_POST['username'];
    $new_role = $_POST['role'];

    // Cập nhật quyền trong bảng tbuserinrole
    $role_query = $conn->prepare("UPDATE tbuserinrole SET role = ? WHERE username = ?");
    $role_query->bind_param("ss", $new_role, $username);
    $role_query->execute();

    $_SESSION['success_message'] = "Cập nhật quyền thành công.";
    header("Location: manage_role.php");
    exit;
}

// Lấy từ khóa tìm kiếm nếu có (theo username hoặc tên khách)
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

// Thiết lập phân trang
$limit = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Xây dựng mệnh đề WHERE cho tìm kiếm
$whereClause = "";
$params = [];
$types = "";
if ($search !== "") {
    $whereClause = "WHERE (u.username LIKE ? OR kh.tenkhach LIKE ?)";
    $search_param = "%" . $search . "%";
    $params = [$search_param, $search_param];
    $types = "ss";
}

// Truy vấn tổng số tài khoản
$count_sql = "SELECT COUNT(*) as total 
              FROM tbUser u 
              JOIN tbuserinrole ur ON u.username = ur.username 
              LEFT JOIN tbkhachhang kh ON u.username = kh.username 
              $whereClause";
$stmt = $conn->prepare($count_sql);
if ($types !== "") {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$count_result = $stmt->get_result();
$total_row = $count_result->fetch_assoc();
$total_users = $total_row['total'];
$total_pages = ceil($total_users / $limit);

// Truy vấn lấy thông tin người dùng, quyền và tên khách (nếu có) với phân trang
$sql = "SELECT u.username, ur.role AS role_name, kh.tenkhach 
        FROM tbUser u 
        JOIN tbuserinrole ur ON u.username = ur.username 
        LEFT JOIN tbkhachhang kh ON u.username = kh.username 
        $whereClause
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if ($types !== "") {
    $types_with_limit = $types . "ii";
    $params_with_limit = array_merge($params, [$limit, $offset]);
    $stmt->bind_param($types_with_limit, ...$params_with_limit);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý tài khoản</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        
        .container-custom {
            max-width: 900px;
            margin: 30px auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        h2 {
            margin-bottom: 20px;
            color: #2c3e50;
            font-weight: 600;
            border-bottom: 2px solid #e1e8ed;
            padding-bottom: 10px;
        }


        .input-group .input-group-append {
            display: flex;
        }
        .input-group input.form-control {
            border-radius: 30px 0 0 30px;
            border-right: none;
        }
        .input-group .input-group-append button,
        .input-group .input-group-append a.btn {
            border-radius: 0 30px 30px 0;
            height: calc(2.25rem + 2px);
            margin: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table thead {
            background-color: #2c3e50 !important;
            color: #fff;
        }

        table thead th {
            padding: 12px 15px;
            background-color: #2c3e50 !important;
            color: #fff;
        }

        table th,
        table td {
            padding: 12px 15px;
            text-align: center;
            border: 1px solid #dee2e6;
        }

        table tbody tr:nth-child(even) {
            background-color: #f7f9fc;
        }

        table tbody tr:hover {
            background-color: #eef1f5;
        }

        form.inline-form {
            margin: 0;
        }

        form.inline-form select {
            padding: 5px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            margin-right: 5px;
        }

        form.inline-form button {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            background-color: #ffc107;
            color: black;
            transition: background-color 0.3s ease;
        }

        form.inline-form button:hover {
            background-color: #d39e00;
        }

        .pagination {
            justify-content: center;
        }

        .pagination .page-item.active .page-link {
            background-color: #2c3e50;
            border-color: #2c3e50;
        }

        .pagination .page-link {
            color: #2c3e50;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <div class="container-custom">
        <h2>Quản lý tài khoản</h2>

        <!-- Hiển thị thông báo nếu có -->
        <?php
        if (isset($_SESSION['error_message'])) {
            echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
            unset($_SESSION['error_message']);
        }
        if (isset($_SESSION['success_message'])) {
            echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
            unset($_SESSION['success_message']);
        }
        ?>

        </form>
        <form method="GET" class="mb-4">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Tìm kiếm theo tên, username..." value="<?= htmlspecialchars($search) ?>">
                <div class="input-group-append">
                    <button class="btn btn-primary" type="submit">Tìm kiếm</button>
                    <a href="manage_role.php" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>

        <!-- Bảng dữ liệu -->
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Tên</th>
                    <th>Quyền</th>
                    <th>Thay đổi</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) : ?>
                    <tr>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td><?= htmlspecialchars($row['tenkhach'] ?? 'Chưa cập nhật') ?></td>
                        <td><?= htmlspecialchars($row['role_name']) ?></td>
                        <td>
                            <form method="post" class="inline-form">
                                <input type="hidden" name="username" value="<?= htmlspecialchars($row['username']) ?>">
                                <select name="role">
                                    <option value="Member" <?= ($row['role_name'] == 'Member') ? 'selected' : '' ?>>Member</option>
                                    <option value="Admin" <?= ($row['role_name'] == 'Admin') ? 'selected' : '' ?>>Admin</option>
                                </select>
                                <button type="submit" name="update_role">Cập nhật</button>
                            </form>
                        </td>
                        <td>
                            <a href="?delete=<?= urlencode($row['username']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xoá tài khoản này?')">Xóa</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Phân trang -->
        <nav>
            <ul class="pagination">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">Trước</a>
                    </li>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Sau</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</body>

</html>