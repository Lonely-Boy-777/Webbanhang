<?php
session_start();
include '../includes/db.php';
include '../templates/adminheader.php';

// Kiểm tra nếu chưa đăng nhập hoặc không phải Admin thì chuyển hướng
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

// Xử lý xoá khách hàng nếu có tham số delete
if (isset($_GET['delete'])) {
    $makhachToDelete = $_GET['delete'];

    // Kiểm tra xem khách hàng có giao dịch liên quan (trong bảng tbDonHang) hay không
    $stmtOrders = $conn->prepare("SELECT COUNT(*) as orderCount FROM tbDonHang WHERE makhach = ?");
    $stmtOrders->bind_param("s", $makhachToDelete);
    $stmtOrders->execute();
    $resultOrders = $stmtOrders->get_result();
    $rowOrders = $resultOrders->fetch_assoc();

    if ($rowOrders['orderCount'] > 0) {
        $_SESSION['error'] = "Không thể xoá khách hàng này vì đã có giao dịch liên quan.";
        header("Location: manage_customers.php");
        exit;
    }

    // Nếu không có giao dịch, tiến hành xoá khách hàng
    $stmtDelete = $conn->prepare("DELETE FROM tbkhachhang WHERE makhach = ?");
    $stmtDelete->bind_param("s", $makhachToDelete);
    if ($stmtDelete->execute()) {
        $_SESSION['message'] = "Xoá khách hàng thành công.";
    } else {
        $_SESSION['error'] = "Xoá khách hàng thất bại do lỗi hệ thống. Vui lòng thử lại sau.";
    }
    header("Location: manage_customers.php");
    exit;
}

// Khởi tạo biến $search
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Xử lý tìm kiếm (theo tên, số điện thoại, địa chỉ hoặc mã KH)
$where = "WHERE ur.role = 'Member'";
if (!empty($search)) {
    $searchEscaped = $conn->real_escape_string($search);
    $where .= " AND (kh.tenkhach LIKE '%$searchEscaped%' 
                  OR kh.sodienthoai LIKE '%$searchEscaped%' 
                  OR kh.diachi LIKE '%$searchEscaped%' 
                  OR kh.makhach LIKE '%$searchEscaped%')";
}


$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Truy vấn tổng số khách hàng (chỉ có role Member)
$total_query = $conn->query("SELECT COUNT(*) AS total FROM tbkhachhang kh 
                              JOIN tbuserinrole ur ON kh.username = ur.username 
                              $where");
if (!$total_query) {
    die("Lỗi truy vấn: " . $conn->error);
}
$total_row = $total_query->fetch_assoc();
$total_customers = $total_row['total'];
$total_pages = ceil($total_customers / $limit);

// Truy vấn dữ liệu khách hàng (chỉ hiển thị role Member)
$result = $conn->query("SELECT kh.makhach, kh.tenkhach, kh.ngaysinh, kh.sodienthoai, kh.diachi, kh.gioitinh, kh.username 
                        FROM tbkhachhang kh 
                        JOIN tbuserinrole ur ON kh.username = ur.username 
                        $where
                        LIMIT $limit OFFSET $offset");
if (!$result) {
    die("Lỗi truy vấn: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý khách hàng</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
    
        body, h2, table {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f7f9fc;
            color: #333;
            line-height: 1.6;
        }
    
        .container {
            max-width: 1200px;
            margin: 30px auto;
            background: #fff;
            padding: 20px 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
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
       
        .alert {
            border-radius: 5px;
            margin-bottom: 20px;
            padding: 12px 20px;
        }
     
        table.table {
            margin-bottom: 20px;
            border-collapse: separate;
            border-spacing: 0;
        }
        table.table th,
        table.table td {
            vertical-align: middle;
            text-align: center;
        }
        table.table thead th {
            background-color: #2c3e50;
            color: #fff;
            font-weight: 500;
            padding: 12px;
            border: none;
        }
        table.table tbody td {
            padding: 12px;
            border-top: 1px solid #dee2e6;
        }
        table.table tbody tr:hover {
            background-color: #f1f5f9;
        }
       
        .btn {
            min-width: 70px;
        }
       
        .pagination {
            justify-content: center;
            margin: 0;
        }
        .pagination .page-item.active .page-link {
            background-color: #2c3e50;
            border-color: #2c3e50;
        }
        .pagination .page-link {
            color: #2c3e50;
            border-radius: 5px;
        }
    
        @media (max-width: 768px) {
            .container {
                padding: 15px 20px;
            }
            table.table th,
            table.table td {
                padding: 8px;
            }
        }
        
        table.table thead th:nth-child(6),
        table.table tbody td:nth-child(6) {
            width: 100px;
        }

        table.table thead th:nth-child(7),
        table.table tbody td:nth-child(7) {
            width: 15%;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Quản lý khách hàng</h2>

        <!-- Thanh tìm kiếm -->
        <form method="GET" class="mb-4">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Tìm kiếm theo tên, SĐT, địa chỉ, mã khách hàng..." value="<?= htmlspecialchars($search) ?>">
                <div class="input-group-append">
                    <button class="btn btn-primary" type="submit">Tìm kiếm</button>
                    <a href="manage_customers.php" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>

        <!-- Thông báo -->
        <?php if (isset($_SESSION['message'])) : ?>
            <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])) : ?>
            <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Bảng dữ liệu -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Mã KH</th>
                    <th>Tên khách</th>
                    <th>Ngày sinh</th>
                    <th>SĐT</th>
                    <th>Địa chỉ</th>
                    <th>Giới tính</th>
                    <th>Thay đổi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0) : ?>
                    <?php while ($row = $result->fetch_assoc()) : ?>
                        <tr>
                            <td><?= htmlspecialchars($row['makhach']) ?></td>
                            <td><?= htmlspecialchars($row['tenkhach']) ?></td>
                            <td>
                                <?= !empty($row['ngaysinh']) && $row['ngaysinh'] !== '0000-00-00'
                                    ? date('d/m/Y', strtotime($row['ngaysinh']))
                                    : 'N/A' ?>
                            </td>
                            <td><?= htmlspecialchars($row['sodienthoai']) ?></td>
                            <td><?= htmlspecialchars($row['diachi']) ?></td>
                            <td><?= htmlspecialchars($row['gioitinh']) ?></td>
                            <td>
                                <a href="edit_customer.php?id=<?= $row['makhach'] ?>" class="btn btn-warning btn-sm">Sửa</a>
                                <br>
                                <a href="?delete=<?= urlencode($row['makhach']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xoá khách hàng này?')">Xóa</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="7" class="text-center">Không có dữ liệu khách hàng.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Phân trang -->
        <nav>
            <ul class="pagination">
                <?php if ($page > 1) : ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">Trước</a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages) : ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Sau</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</body>
</html>
