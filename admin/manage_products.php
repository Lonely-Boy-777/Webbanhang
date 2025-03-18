<?php
session_start();
include '../includes/db.php';
include '../templates/adminheader.php';

// Kiểm tra nếu chưa đăng nhập hoặc không phải Admin thì chuyển hướng
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

// Xử lý xóa sản phẩm
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    // Truy vấn bảng tbchitietdonhang để kiểm tra sản phẩm có đang xuất hiện ở đơn hàng nào không
    $order_sql = "SELECT madonhang FROM tbchitietdonhang WHERE mahang = ?";
    $order_stmt = $conn->prepare($order_sql);
    $order_stmt->bind_param("s", $id);
    $order_stmt->execute();
    $order_result = $order_stmt->get_result();

    // Nếu sản phẩm có trong đơn hàng, thu thập thông tin các đơn hàng
    if ($order_result->num_rows > 0) {
        $orders = array();
        while ($row = $order_result->fetch_assoc()) {
            // Giả sử trường madonhang chứa mã đơn hàng
            $orders[] = $row['madonhang'];
        }
        $orders_list = implode(", ", $orders);
        $_SESSION['error_message'] = "Không thể xóa sản phẩm vì nó đang có trong các đơn hàng: " . $orders_list . ". Vui lòng kiểm tra lại.";
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $search = isset($_GET['search']) ? urlencode($_GET['search']) : '';
        header("Location: manage_products.php?page=$page&search=$search");

        exit;
    }

    // Nếu sản phẩm không có trong đơn hàng, tiến hành xoá ảnh chi tiết ở bảng tbhinhanhchitiet
    $sql_images = "DELETE FROM tbhinhanhchitiet WHERE mahang = ?";
    $stmt_images = $conn->prepare($sql_images);
    $stmt_images->bind_param("s", $id);
    $stmt_images->execute();

    // Tiến hành xoá sản phẩm trong bảng tbmathang
    $sql = "DELETE FROM tbmathang WHERE mahang = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id);

    try {
        $stmt->execute();
        $_SESSION['success_message'] = "Xóa sản phẩm thành công.";
    } catch (mysqli_sql_exception $e) {
        $_SESSION['error_message'] = "Xóa sản phẩm thất bại do lỗi hệ thống. Vui lòng thử lại sau.";
    }
    header("Location: manage_products.php");
    exit;
}

// Lấy từ khóa tìm kiếm
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

// Phân trang
$limit = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

if ($search !== "") {
    $likeSearch = "%" . $search . "%";

    // Tính tổng số sản phẩm theo từ khóa
    $stmtCount = $conn->prepare("SELECT COUNT(*) as total FROM tbmathang WHERE tenhang LIKE ?");
    $stmtCount->bind_param("s", $likeSearch);
    $stmtCount->execute();
    $resultCount = $stmtCount->get_result();
    $total = $resultCount->fetch_assoc()['total'];

    // Truy vấn sản phẩm có tìm kiếm với phân trang
    $stmt = $conn->prepare("SELECT * FROM tbmathang WHERE tenhang LIKE ? LIMIT ? OFFSET ?");
    $stmt->bind_param("sii", $likeSearch, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Tính tổng số sản phẩm
    $resultCount = $conn->query("SELECT COUNT(*) as total FROM tbmathang");
    $total = $resultCount->fetch_assoc()['total'];

    // Truy vấn sản phẩm với phân trang
    $result = $conn->query("SELECT * FROM tbmathang LIMIT $limit OFFSET $offset");
}

$total_pages = ceil($total / $limit);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Quản lý sản phẩm</title>
    <!-- Sử dụng Bootstrap để đồng nhất giao diện -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f7f9fc;
            color: #333;
        }

        h2 {
            margin-bottom: 20px;
            color: #2c3e50;
            font-weight: 600;
            border-bottom: 2px solid #e1e8ed;
            padding-bottom: 10px;
        }

        .container-custom {
            max-width: 1200px;
            margin: 30px auto;
            background: #fff;
            padding: 20px 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }

        .search-form .input-group {
            align-items: center;
        }

        .search-form .input-group input.form-control {
            border-radius: 30px 0 0 30px;
            border-right: none;
        }

        .search-form .input-group-append button,
        .search-form .input-group-append a.btn {
            border-radius: 0 30px 30px 0;
        }

        .input-group .input-group-append {
            display: flex;
        }

        .input-group .input-group-append button,
        .input-group .input-group-append a.btn {
            height: calc(2.25rem + 2px);
            margin: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th,
        table td {
            padding: 12px;
            border: 1px solid #dee2e6;
            text-align: center;
        }

        table th {
            background-color: #2c3e50;
            color: #fff;
        }

        table th:first-child,
        table td:first-child {
            width: 15%;
        }

        .add-product-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .pagination .page-link {
            color: #2c3e50;
        }

        .pagination .page-item.active .page-link {
            background-color: #2c3e50;
            border-color: #2c3e50;
        }
    </style>
</head>

<body>
    <div class="container-custom">
        <h2>Quản lý sản phẩm</h2>

        <!-- Hiển thị thông báo lỗi hoặc thành công -->
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

        <!-- Nút Thêm Sản Phẩm -->
        <div class="add-product-container">
            <a href="../admin/add_product.php" class="btn btn-success">Thêm sản phẩm</a>
        </div>

        <!-- Thanh tìm kiếm -->
        <form class="search-form mb-4" action="" method="GET">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Tìm kiếm sản phẩm..." value="<?= htmlspecialchars($search) ?>">
                <div class="input-group-append">
                    <button class="btn btn-primary" type="submit">Tìm kiếm</button>
                    <a href="manage_products.php" class="btn btn-secondary">Reset</a>
                </div>
            </div>
        </form>

        <!-- Bảng dữ liệu sản phẩm -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Mã hàng</th>
                    <th>Tên hàng</th>
                    <th>Đơn giá</th>
                    <th>Thay đổi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0) : ?>
                    <?php while ($row = $result->fetch_assoc()) : ?>
                        <tr>
                            <td><?= htmlspecialchars($row['mahang']) ?></td>
                            <td><?= htmlspecialchars($row['tenhang']) ?></td>
                            <td><?= number_format($row['dongia'], 0) ?> VND</td>
                            <td>
                                <a href="../admin/edit_product.php?id=<?= urlencode($row['mahang']) ?>" class="btn btn-warning btn-sm">Sửa</a>
                                <a href="?delete=<?= urlencode($row['mahang']) ?>&page=<?= $page ?>&search=<?= urlencode($search) ?>"
                                    class="btn btn-danger btn-sm" onclick="return confirm('Xác nhận xóa?')">Xóa</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="4" class="text-center">Không có dữ liệu sản phẩm.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Phân trang -->
        <nav>
            <ul class="pagination justify-content-center">
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