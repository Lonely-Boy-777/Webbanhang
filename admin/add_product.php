<?php
session_start();
include '../includes/db.php';
include '../templates/adminheader.php';

// Kiểm tra nếu chưa đăng nhập hoặc không phải Admin thì chuyển hướng
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

// Hàm tự tạo mã hàng
function getNextProductCode($conn) {
    $sql = "SELECT MAX(mahang) AS max_code FROM tbmathang WHERE mahang LIKE 'MH%'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $maxCode = $row['max_code'];
    
    if ($maxCode) {
        $numPart = substr($maxCode, 2);
        $nextNum = intval($numPart) + 1;
        if ($nextNum < 1000) {
            $newCode = 'MH' . sprintf("%03d", $nextNum);
        } else {
            $newCode = 'MH' . $nextNum;
        }
    } else {
        $newCode = 'MH001';
    }
    
    return $newCode;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sử dụng hàm để tự động tạo mã hàng
    $mahang = getNextProductCode($conn);
    $tenhang    = trim($_POST['tenhang']);
    $mota       = trim($_POST['mota']);
    $dongia     = floatval($_POST['dongia']);
    $nguongoc   = trim($_POST['nguongoc']);
    $thuonghieu = trim($_POST['thuonghieu']);
    $conhang    = trim($_POST['conhang']);
    $hinhanh    = '';

    // Xử lý tải lên hình ảnh chính
    if (isset($_FILES['hinhanh']) && $_FILES['hinhanh']['error'] == 0) {
        $target_dir = "../assets/images/";
        $target_file = $target_dir . basename($_FILES["hinhanh"]["name"]);
        $check = getimagesize($_FILES["hinhanh"]["tmp_name"]);
        if ($check !== false) {
            if (move_uploaded_file($_FILES["hinhanh"]["tmp_name"], $target_file)) {
                $hinhanh = basename($_FILES["hinhanh"]["name"]);
            }
        }
    }

    // Thêm dữ liệu vào bảng tbmathang
    $sql = "INSERT INTO tbmathang (mahang, tenhang, mota, dongia, nguongoc, thuonghieu, hinhanh, conhang) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssdssss", $mahang, $tenhang, $mota, $dongia, $nguongoc, $thuonghieu, $hinhanh, $conhang);
    $stmt->execute();

    // Xử lý tải lên ảnh chi tiết
    if (isset($_FILES['hinhanh_chitiet']) && !empty($_FILES['hinhanh_chitiet']['name'][0])) {
        $files = $_FILES['hinhanh_chitiet'];
        $file_count = count($files['name']);

        for ($i = 0; $i < $file_count; $i++) {
            $file_name = basename($files['name'][$i]);
            $target_file = "../assets/images/" . $file_name;

            if (move_uploaded_file($files['tmp_name'][$i], $target_file)) {
                $sql_hinh = "INSERT INTO tbhinhanhchitiet (mahang, hinhanh_chitiet) VALUES (?, ?)";
                $stmt_hinh = $conn->prepare($sql_hinh);
                $stmt_hinh->bind_param("ss", $mahang, $file_name);
                $stmt_hinh->execute();
            }
        }
    }

   
   echo "<p style='color: green; text-align: center; font-weight: bold;'>Thêm mặt hàng thành công!</p>";
   echo "<div style='text-align: center; margin-top: 20px;'>";
   echo "<a href='add_product.php' style='display: inline-block; padding: 10px 20px; background: #007BFF; color: white; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Thêm tiếp</a>";
   echo "<a href='manage_products.php' style='display: inline-block; padding: 10px 20px; background: #28A745; color: white; text-decoration: none; border-radius: 5px;'>Xong</a>";
   echo "</div>";
   exit;
}
?>

<h2 style="text-align: center;">Thêm sản phẩm</h2>
<form method="post" enctype="multipart/form-data" style="max-width: 500px; margin: auto;">

    <p style="text-align:center;">Mã hàng sẽ được tạo tự động: <strong><?= getNextProductCode($conn) ?></strong></p>
    <label>Tên hàng: <input type="text" name="tenhang" required></label><br><br>
    <label>Mô tả chi tiết: <textarea name="mota"></textarea></label><br><br>
    <label>Đơn giá: <input type="number" step="0.01" name="dongia" required></label><br><br>
    <label>Nguồn gốc: <input type="text" name="nguongoc" required></label><br><br>
    <label>Thương hiệu: <input type="text" name="thuonghieu" required></label><br><br>
    <label>Hình ảnh chính: <input type="file" name="hinhanh" accept="image/*"></label><br><br>

    <h3>Chi tiết sản phẩm</h3>
    <label>Ảnh chi tiết: <input type="file" name="hinhanh_chitiet[]" accept="image/*" multiple></label><br><br>
    <label>Tình trạng:
        <select name="conhang" required>
            <option value="Còn hàng">Còn hàng</option>
            <option value="Hết hàng">Hết hàng</option>
        </select>
    </label><br><br>

   
    <button type="submit" class="btn btn-primary">Thêm mặt hàng</button>
 
    <button type="button" onclick="window.location.href='manage_products.php'" class="btn btn-secondary cancel-button">Huỷ</button>
</form>

<style>
   
    button.btn {
        margin-top: 10px;
    }

    .cancel-button {
        background-color: #dc3545;
        color: white;
        border: 1px solid #dc3545;
        border-radius: 5px;
       
        display: block;
        width: 100%; 
        margin-top: 10px;
    }
    .cancel-button:hover {
        background-color: #c82333;
        border-color: #bd2130;
    }
</style>
