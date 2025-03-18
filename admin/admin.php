<?php
session_start();
include '../includes/db.php';
include '../templates/adminheader.php';

// Kiểm tra nếu chưa đăng nhập hoặc không phải admin thì chuyển hướng
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

$sql_orders = "SELECT COUNT(*) as total_orders FROM tbdonhang";
$result_orders = $conn->query($sql_orders);
$row_orders = $result_orders->fetch_assoc();
$total_orders = $row_orders['total_orders'];


$sql_customers = "SELECT COUNT(*) as total_customers FROM tbkhachhang";
$result_customers = $conn->query($sql_customers);
$row_customers = $result_customers->fetch_assoc();
$total_customers = $row_customers['total_customers'];

$filter    = isset($_GET['filter']) ? $_GET['filter'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to   = isset($_GET['date_to']) ? $_GET['date_to'] : '';

$where = "WHERE tinhtrang = 'Đã giao'";
$params = [];
$types  = "";
if ($filter !== '') {
    if ($filter == 'today') {
        $where .= " AND DATE(ngaymua) = CURDATE()";
    } elseif ($filter == 'yesterday') {
        $where .= " AND DATE(ngaymua) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
    } elseif ($filter == 'this_week') {
        $where .= " AND YEARWEEK(ngaymua, 1) = YEARWEEK(CURDATE(), 1)";
    } elseif ($filter == 'this_month') {
        $where .= " AND MONTH(ngaymua) = MONTH(CURDATE()) AND YEAR(ngaymua) = YEAR(CURDATE())";
    }
}
if ($date_from !== '' && $date_to !== '') {
    $where .= " AND DATE(ngaymua) BETWEEN ? AND ?";
    $params[] = $date_from;
    $params[] = $date_to;
    $types .= "ss";
}

$sql_revenue = "SELECT SUM(ct.soluong * ct.dongia) as total_revenue 
                FROM tbdonhang 
                INNER JOIN tbchitietdonhang as ct ON tbdonhang.madonhang = ct.madonhang 
                $where";
$stmt = $conn->prepare($sql_revenue);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result_revenue = $stmt->get_result();
$row_revenue = $result_revenue->fetch_assoc();
$total_revenue = $row_revenue['total_revenue'];
if (!$total_revenue) {
    $total_revenue = 0;
}
?>

<style>
    h2 {
        margin-bottom: 20px;
        color: #2c3e50;
        font-weight: 600;
        border-bottom: 2px solid #e1e8ed;
        padding-bottom: 10px;
    }

    .custom-card {
        border: none;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        border-radius: 10px;
        transition: transform 0.2s ease;
        margin-bottom: 30px;
    }

    .custom-card:hover {
        transform: translateY(-5px);
    }

    .icon-style {
        font-size: 2rem;
        color: #007bff;
        margin-right: 10px;
    }

    .card-title {
        font-weight: bold;
    }

    .card-link {
        text-decoration: none;
        color: inherit;
        display: block;
    }

    .filter-form {
        max-width: 500px;
        margin: 0 auto 30px;
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .filter-form .form-group {
        width: 100%;
        display: flex;
        flex-direction: column;
    }

    .filter-form .form-group label {
        margin-bottom: 5px;
        font-weight: 500;
    }

    .filter-form select,
    .filter-form input[type="date"] {
        height: calc(1.5em + .75rem + 2px);
        padding: 0.375rem 0.75rem;
        border: 1px solid #ced4da;
        border-radius: 5px;
    }

    .filter-form button.btn-primary,
    .filter-form a.btn-secondary {
        width: 120px;
        height: calc(1.5em + .75rem + 2px);
        padding: 0;
        line-height: 1;
        border: 1px solid #ced4da;
        border-radius: 5px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        margin: 0 auto;

    }
</style>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

<!-- Phần Tổng quan -->
<h2 class="text-center my-4">Tổng quan</h2>
<div class="container mt-5">
    <div class="row">
        <!-- Tổng số đơn hàng -->
        <div class="col-md-6">
            <a href="manage_orders.php" class="card-link">
                <div class="card text-center custom-card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-shopping-cart icon-style"></i> Tổng số đơn hàng
                        </h5>
                        <p class="card-text display-4"><?php echo $total_orders; ?></p>
                    </div>
                </div>
            </a>
        </div>
        <!-- Tổng số khách hàng -->
        <div class="col-md-6">
            <a href="manage_customers.php" class="card-link">
                <div class="card text-center custom-card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-users icon-style"></i> Tổng số khách hàng
                        </h5>
                        <p class="card-text display-4"><?php echo $total_customers; ?></p>
                    </div>
                </div>
            </a>
        </div>
    </div>
    <!-- Tổng doanh thu (không lọc) -->
    <div class="row">
        <div class="col-md-12">
            <div class="card text-center custom-card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-dollar-sign icon-style"></i> Tổng doanh thu
                    </h5>
                    <p class="card-text display-4"><?php echo number_format($total_revenue, 0, ',', '.'); ?> VND</p>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="container mt-4">
    <form method="GET" class="filter-form">
      
        <div class="form-group w-100">
            <select name="filter" class="form-control">
                <option value="">-- Lọc nhanh doanh thu--</option>
                <option value="today" <?= ($filter == 'today') ? 'selected' : '' ?>>Hôm nay</option>
                <option value="yesterday" <?= ($filter == 'yesterday') ? 'selected' : '' ?>>Hôm qua</option>
                <option value="this_week" <?= ($filter == 'this_week') ? 'selected' : '' ?>>Tuần này</option>
                <option value="this_month" <?= ($filter == 'this_month') ? 'selected' : '' ?>>Tháng này</option>
            </select>
        </div>
  
        <div class="form-group w-100">
            <label for="date_from" class="mr-2">Từ:</label>
            <input type="date" name="date_from" id="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
        </div>
    
        <div class="form-group w-100">
            <label for="date_to" class="mr-2">Đến:</label>
            <input type="date" name="date_to" id="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
        </div>
  
        <div class="form-group w-100">
            <button type="submit" class="btn btn-primary">Lọc</button>
        </div>
  
        <div class="form-group w-100">
            <a href="revenue.php" class="btn btn-secondary">Reset</a>
        </div>
    </form>
</div>