<?php
session_start();
include '../includes/db.php';
include '../templates/adminheader.php';

// Kiểm tra nếu chưa đăng nhập hoặc không phải Admin thì chuyển hướng
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

//Xây dựng mảng chứa các tham số cần giữ lại từ URL GET
$redirectParams = [];
if (isset($_GET['search']))    $redirectParams['search']   = $_GET['search'];
if (isset($_GET['date_from'])) $redirectParams['date_from'] = $_GET['date_from'];
if (isset($_GET['date_to']))   $redirectParams['date_to']   = $_GET['date_to'];
if (isset($_GET['filter']))    $redirectParams['filter']    = $_GET['filter'];

$redirect = "manage_orders.php";
if (!empty($redirectParams)) {
    $redirect .= "?" . http_build_query($redirectParams);
}

//Xử lý cập nhật tình trạng đơn hàng (Status Update)
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['update_status'])) {
    $madonhang = $_POST['madonhang'];
    $tinhtrang = $_POST['tinhtrang'];

    $update_query = $conn->prepare("UPDATE tbDonHang SET tinhtrang = ? WHERE madonhang = ?");
    $update_query->bind_param("ss", $tinhtrang, $madonhang);
    $update_query->execute();

    $_SESSION['success_message'] = "Cập nhật đơn hàng thành công.";
    header("Location: " . $redirect);
    exit;
}

//Xử lý xoá đơn hàng (Delete Order) – chỉ cho phép xoá khi đơn hàng có trạng thái "Đã hủy"
if (isset($_GET['delete'])) {
    $madonhang = $_GET['delete'];

    // Kiểm tra đơn hàng có tồn tại và lấy tình trạng đơn hàng
    $check_sql = "SELECT tinhtrang FROM tbDonHang WHERE madonhang = ?";
    $stmt_check = $conn->prepare($check_sql);
    $stmt_check->bind_param("s", $madonhang);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows == 0) {
        $_SESSION['error_message'] = "Đơn hàng không tồn tại.";
        header("Location: " . $redirect);
        exit;
    }

    $order = $result_check->fetch_assoc();
    if ($order['tinhtrang'] !== "Đã hủy") {
        $_SESSION['error_message'] = "Chỉ cho phép xóa đơn hàng ở trạng thái 'Đã hủy'.";
        header("Location: " . $redirect);
        exit;
    }

    // Xoá các chi tiết đơn hàng trước
    $delete_details_sql = "DELETE FROM tbChiTietDonHang WHERE madonhang = ?";
    $stmt_details = $conn->prepare($delete_details_sql);
    $stmt_details->bind_param("s", $madonhang);
    $stmt_details->execute();

    // Xoá đơn hàng
    $delete_order_sql = "DELETE FROM tbDonHang WHERE madonhang = ?";
    $stmt_delete = $conn->prepare($delete_order_sql);
    $stmt_delete->bind_param("s", $madonhang);

    try {
        $stmt_delete->execute();
        $_SESSION['success_message'] = "Xóa đơn hàng thành công.";
    } catch (mysqli_sql_exception $e) {
        $_SESSION['error_message'] = "Xóa đơn hàng thất bại do lỗi hệ thống. Vui lòng thử lại sau.";
    }

    header("Location: " . $redirect);
    exit;
}

//Xử lý các tham số tìm kiếm & lọc từ URL (GET)
$search    = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to   = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$filter    = isset($_GET['filter']) ? $_GET['filter'] : '';

// Xây dựng mệnh đề WHERE cho SQL
$where = "WHERE 1=1";
$params = [];
$types  = "";

if ($search !== '') {
    $where .= " AND (tbDonHang.madonhang LIKE ? OR tbkhachhang.tenkhach LIKE ? OR tbkhachhang.sodienthoai LIKE ? OR tbkhachhang.diachi LIKE ?)";
    $search_param = "%{$search}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $types .= "ssss";
}

if ($filter !== '') {
    if ($filter == 'today') {
        $where .= " AND DATE(tbDonHang.ngaymua) = CURDATE()";
    } elseif ($filter == 'yesterday') {
        $where .= " AND DATE(tbDonHang.ngaymua) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
    } elseif ($filter == 'this_week') {
        $where .= " AND YEARWEEK(tbDonHang.ngaymua, 1) = YEARWEEK(CURDATE(), 1)";
    } elseif ($filter == 'this_month') {
        $where .= " AND MONTH(tbDonHang.ngaymua) = MONTH(CURDATE()) AND YEAR(tbDonHang.ngaymua) = YEAR(CURDATE())";
    }
}

if ($date_from !== '' && $date_to !== '') {
    $where .= " AND DATE(tbDonHang.ngaymua) BETWEEN ? AND ?";
    $params[] = $date_from;
    $params[] = $date_to;
    $types .= "ss";
}


$limit  = 10;
$page   = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Đếm tổng số đơn hàng
$count_sql = "SELECT COUNT(*) as total FROM tbDonHang 
              JOIN tbkhachhang ON tbDonHang.makhach = tbkhachhang.makhach 
              $where";
$stmt = $conn->prepare($count_sql);
if ($types !== "") {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result_count = $stmt->get_result();
$total_row = $result_count->fetch_assoc();
$total = $total_row['total'];
$total_pages = ceil($total / $limit);

//Lấy dữ liệu đơn hàng cùng thông tin khách hàng và tổng giá trị
$sql = "SELECT tbDonHang.*, tbkhachhang.tenkhach, tbkhachhang.sodienthoai, tbkhachhang.diachi, tbDonHang.makhach,
        (SELECT SUM(soluong * dongia) FROM tbChiTietDonHang WHERE madonhang = tbDonHang.madonhang) AS total_value
        FROM tbDonHang 
        JOIN tbkhachhang ON tbDonHang.makhach = tbkhachhang.makhach 
        $where 
        ORDER BY tbDonHang.ngaymua DESC 
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if ($types !== "") {
    $types_with_limit = $types . "ii";
    $params_with_limit = $params;
    $params_with_limit[] = $limit;
    $params_with_limit[] = $offset;
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
    <title>Quản lý đơn hàng</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f7f9fc;
            color: #333;
        }

        h2 {
            text-align: center;
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

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .filter-form .form-group {
            display: flex;
            align-items: center;
            margin-bottom: 0;
        }

        .filter-form .form-group label {
            margin-right: 5px;
        }

        .filter-form input[name="search"] {
            width: 500px;
            border-radius: 30px;
        }

        .filter-form input[name="date_from"] {
            width: 400px;

        }

        .filter-form input[name="date_to"] {
            width: 400px;

        }

        .filter-form .form-group input,
        .filter-form .form-group select {
            height: calc(1.5em + .75rem + 2px);
        }

        .filter-form button.btn-primary,
        .filter-form a.btn-secondary {
            width: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            height: calc(1.5em + .75rem + 2px);
            padding: 0;
            line-height: 1;
            vertical-align: middle;
            margin: 0;
        }

        .filter-form .form-group a.btn-secondary {
            margin-left: 10px;
        }

    
        .table th:nth-child(1) {
            width: 100px;
        }

       
        .table th:nth-child(2) {
            width: 120px;
        }

    
        .table th:nth-child(3) {
            width: 18%;
        }

      
        .table th:nth-child(4) {
            width: 12%;
        }

       
        .table th:nth-child(5) {
            width: 20%;
        }

     
        .table th:nth-child(6) {
            width: 13%;
        }

      
        .table th:nth-child(7) {
            width: 15%;
        }

      
        .table th:nth-child(8) {
            width: 10%;
        }

        .table th:nth-child(9) {
            width: 150px;
        }

       
        .table th,
        .table td {
            text-align: center;
            vertical-align: middle;
        }

        .table thead th {
            background-color: #2c3e50 !important;
            color: #fff;
            font-weight: bold;
        }

        .pagination .page-link {
            color: #2c3e50;
        }

        .pagination .page-item.active .page-link {
            background-color: #2c3e50;
            border-color: #2c3e50;
        }

        .btn.btn-sm.btn-primary {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #000;
        }

        .btn.btn-sm.btn-primary:hover {
            background-color: #e0a800;
            border-color: #d39e00;
            color: #000;
        }

   
        .action-btn {
            margin-bottom: 5px;
            width: 100%;
        }
    </style>
</head>

<body>
    <div class="container-custom">
        <h2>Quản lý đơn hàng</h2>
     
        <?php
        if (isset($_SESSION['error_message'])) {
            echo '<div class="alert alert-danger text-center">' . $_SESSION['error_message'] . '</div>';
            unset($_SESSION['error_message']);
        }
        if (isset($_SESSION['success_message'])) {
            echo '<div class="alert alert-success text-center">' . $_SESSION['success_message'] . '</div>';
            unset($_SESSION['success_message']);
        }
        ?>
        <!-- Phần tìm kiếm và lọc -->
        <form method="GET" class="filter-form">
          
            <div class="form-group">
                <label for="date_from">Từ:</label>
                <input type="date" name="date_from" id="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
            </div>
            <div class="form-group">
                <label for="date_to">Đến:</label>
                <input type="date" name="date_to" id="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
            </div>
            
            <div class="form-group">
                <input type="text" name="search" class="form-control" placeholder="Tìm kiếm: Mã đơn, tên khách, SĐT, địa chỉ..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">Tìm kiếm</button>
                <a href="manage_orders.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>
        <!-- Bảng đơn hàng -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Mã đơn</th>
                    <th>Mã khách hàng</th>
                    <th>Khách hàng</th>
                    <th>SĐT</th>
                    <th>Địa chỉ</th>
                    <th>Ngày mua</th>
                    <th>Tổng giá trị</th>
                    <th>Tình trạng</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['madonhang']) ?></td>
                            <td><?= htmlspecialchars($row['makhach']) ?></td>
                            <td><?= htmlspecialchars($row['tenkhach']) ?></td>
                            <td><?= htmlspecialchars($row['sodienthoai']) ?></td>
                            <td><?= htmlspecialchars($row['diachi']) ?></td>
                            <td><?= htmlspecialchars($row['ngaymua']) ?></td>
                            <td><?= number_format($row['total_value'], 0) ?> VND</td>
                            <td>
                                <form method="post" style="margin: 0;">
                                    <input type="hidden" name="madonhang" value="<?= htmlspecialchars($row['madonhang']) ?>">
                                    <select name="tinhtrang" class="form-control form-control-sm d-inline-block" style="width: auto;">
                                        <option value="Đang xử lý" <?= ($row['tinhtrang'] == "Đang xử lý") ? 'selected' : '' ?>>Đang xử lý</option>
                                        <option value="Đã giao" <?= ($row['tinhtrang'] == "Đã giao") ? 'selected' : '' ?>>Đã giao</option>
                                        <option value="Đang giao hàng" <?= ($row['tinhtrang'] == "Đang giao hàng") ? 'selected' : '' ?>>Đang giao hàng</option>
                                        <option value="Đã hủy" <?= ($row['tinhtrang'] == "Đã hủy") ? 'selected' : '' ?>>Đã hủy</option>
                                    </select>
                                    <button type="submit" name="update_status" class="btn btn-sm btn-primary">Cập nhật</button>
                                </form>
                            </td>
                            <td>
                                <!-- Cột Hành động: Gộp nút "Xoá" và "Xem chi tiết" -->
                                <?php if ($row['tinhtrang'] === "Đã hủy"): ?>
                                    <a href="?delete=<?= urlencode($row['madonhang']) ?>&<?= http_build_query($redirectParams) ?>"
                                        class="btn btn-danger btn-sm action-btn"
                                        onclick="return confirm('Bạn có chắc chắn muốn xóa đơn hàng này?')">Xoá</a>
                                <?php else: ?>
                                    <button class="btn btn-danger btn-sm action-btn" disabled>Xoá</button>
                                <?php endif; ?>
                                <button type="button" class="btn btn-info btn-sm action-btn"
                                    onclick="toggleDetails('details-<?= htmlspecialchars($row['madonhang']) ?>')">Xem chi tiết</button>
                            </td>
                        </tr>
                        <!-- Hàng chi tiết đơn hàng ẩn (hiển thị dưới dạng table-row) -->
                        <tr id="details-<?= htmlspecialchars($row['madonhang']) ?>" style="display: none;">
                            <td colspan="9">
                                <?php
                                // Truy vấn các sản phẩm trong đơn hàng (bao gồm tên, ảnh)
                                $detail_sql = "SELECT c.*, m.tenhang, m.hinhanh FROM tbChiTietDonHang c JOIN tbmathang m ON c.mahang = m.mahang WHERE c.madonhang = ?";
                                $stmt_detail = $conn->prepare($detail_sql);
                                $stmt_detail->bind_param("s", $row['madonhang']);
                                $stmt_detail->execute();
                                $detail_result = $stmt_detail->get_result();
                                ?>
                                <table class="table table-sm table-bordered" style="margin-bottom: 0;">
                                    <thead>
                                        <tr>
                                            <th style="padding:5px;">STT</th>
                                            <th style="padding:5px;">Ảnh sản phẩm</th>
                                            <th style="padding:5px;">Tên hàng</th>
                                            <th style="padding:5px;">Số lượng</th>
                                            <th style="padding:5px;">Đơn giá</th>
                                            <th style="padding:5px;">Thành tiền</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $i = 1;
                                        while ($detail = $detail_result->fetch_assoc()) {
                                            $thanhtien = $detail['soluong'] * $detail['dongia'];
                                            $hinhanh = !empty($detail['hinhanh']) ? "../assets/images/" . $detail['hinhanh'] : "../assets/images/default.jpg";
                                        ?>
                                            <tr>
                                                <td style="padding:5px; text-align:center;"><?= $i ?></td>
                                                <td style="padding:5px; text-align:center;">
                                                    <a href="../pages/product_detail.php?mahang=<?= urlencode($detail['mahang']) ?>">
                                                        <img src="<?= $hinhanh ?>" alt="<?= htmlspecialchars($detail['tenhang']) ?>" style="max-width:60px; max-height:60px;">
                                                    </a>
                                                </td>
                                                <td style="padding:5px;">
                                                    <a href="../pages/product_detail.php?mahang=<?= urlencode($detail['mahang']) ?>" style="text-decoration:none; color:#333;">
                                                        <?= htmlspecialchars($detail['tenhang']) ?>
                                                    </a>
                                                </td>
                                                <td style="padding:5px; text-align:center;"><?= $detail['soluong'] ?></td>
                                                <td style="padding:5px; text-align:right;"><?= number_format($detail['dongia'], 0) ?> VND</td>
                                                <td style="padding:5px; text-align:right;"><?= number_format($thanhtien, 0) ?> VND</td>
                                            </tr>
                                        <?php
                                            $i++;
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center">Không có dữ liệu đơn hàng.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <!-- Phân trang -->
        <nav>
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page - 1 ?>&<?= http_build_query($redirectParams) ?>">Trước</a>
                    </li>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query($redirectParams) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page + 1 ?>&<?= http_build_query($redirectParams) ?>">Sau</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</body>

</html>
<script>
    // Hàm toggle hiển thị/ẩn chi tiết đơn hàng (hiển thị dưới dạng table-row)
    function toggleDetails(divId) {
        var detailsDiv = document.getElementById(divId);
        if (detailsDiv.style.display === "none" || detailsDiv.style.display === "") {
            detailsDiv.style.display = "table-row";
        } else {
            detailsDiv.style.display = "none";
        }
    }
</script>