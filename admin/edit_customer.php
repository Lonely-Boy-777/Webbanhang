<?php
session_start();
include '../includes/db.php';
include '../templates/adminheader.php';

// Kiểm tra quyền Admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Không tìm thấy khách hàng";
    header("Location: manage_customers.php");
    exit;
}


$id = $conn->real_escape_string($_GET['id']);

$customer = $conn->query("SELECT * FROM tbkhachhang WHERE makhach = '$id'");
if ($customer->num_rows === 0) {
    $_SESSION['error'] = "Khách hàng không tồn tại";
    header("Location: manage_customers.php");
    exit;
}
$customer = $customer->fetch_assoc();

// Xử lý cập nhật
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenkhach    = $conn->real_escape_string($_POST['tenkhach']);
    $ngaysinh    = $conn->real_escape_string($_POST['ngaysinh']);
    $sodienthoai = $conn->real_escape_string($_POST['sodienthoai']);
    $diachi      = $conn->real_escape_string($_POST['diachi']);
    $gioitinh    = $conn->real_escape_string($_POST['gioitinh']);

    $sql = "UPDATE tbkhachhang SET 
                tenkhach    = '$tenkhach',
                ngaysinh    = '$ngaysinh',
                sodienthoai = '$sodienthoai',
                diachi      = '$diachi',
                gioitinh    = '$gioitinh'
            WHERE makhach = '$id'";

    if ($conn->query($sql)) {
        $_SESSION['message'] = "Cập nhật thông tin thành công!";
        header("Location: manage_customers.php");
        exit;
    } else {
        $_SESSION['error'] = "Lỗi cập nhật: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sửa thông tin khách hàng</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
 
        .container {
            max-width: 600px;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        

        button.btn.btn-primary {
            width: 50%;
            font-size: 1rem;
            text-align: center;
            padding: 0.5rem 1.25rem;
            display: block;
            margin: 0 auto;
        }
        
        a.btn.btn-secondary {
            padding: 0.5rem 1.25rem;
            font-size: 1rem;
            background-color: #f44336;
            border-color: #f44336;
            color: #fff;
            display: block;
            width: 50%;
            margin: 20px auto 0;
            text-align: center;
            border-radius: 0.25rem;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }
        
        a.btn.btn-secondary:hover {
            background-color: #d32f2f;
            border-color: #d32f2f;
            text-decoration: none;
        }
        
        .form-group label {
            font-weight: 500;
            color: #333;
        }
        
        .alert {
            text-align: center;
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <h2>Sửa thông tin khách hàng</h2>
    
    <?php if (isset($_SESSION['error'])) : ?>
        <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Tên khách hàng:</label>
            <input type="text" name="tenkhach" class="form-control" 
                   value="<?= htmlspecialchars($customer['tenkhach']) ?>" required>
        </div>
        
        <div class="form-group">
            <label>Ngày sinh:</label>
            <input type="date" name="ngaysinh" class="form-control" 
                   value="<?= htmlspecialchars($customer['ngaysinh']) ?>">
        </div>
        
        <div class="form-group">
            <label>Số điện thoại:</label>
            <input type="tel" name="sodienthoai" class="form-control" 
                   value="<?= htmlspecialchars($customer['sodienthoai']) ?>" required>
        </div>
        
        <div class="form-group">
            <label>Địa chỉ:</label>
            <textarea name="diachi" class="form-control"><?= htmlspecialchars($customer['diachi']) ?></textarea>
        </div>
        
        <div class="form-group">
            <label>Giới tính:</label>
            <select name="gioitinh" class="form-control">
                <option value="Nam" <?= $customer['gioitinh'] == 'Nam' ? 'selected' : '' ?>>Nam</option>
                <option value="Nữ" <?= $customer['gioitinh'] == 'Nữ' ? 'selected' : '' ?>>Nữ</option>
                <option value="Khác" <?= $customer['gioitinh'] == 'Khác' ? 'selected' : '' ?>>Khác</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary">Cập nhật</button>
        <a href="manage_customers.php" class="btn btn-secondary">Hủy</a>
    </form>
</div>
</body>
</html>
