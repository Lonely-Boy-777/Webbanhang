<?php
ob_start();
session_start();
include '../includes/db.php';
include '../templates/adminheader.php';

// Kiểm tra quyền Admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit;
}

// Kiểm tra tham số sản phẩm (sử dụng "id" trong GET)
if (!isset($_GET['id'])) {
    echo "Không có mã sản phẩm được chọn.";
    exit;
}

$mahang = trim($_GET['id']);

// Đường dẫn file JSON chứa thứ tự ảnh chi tiết
$orderFile = "../assets/image_orders/order_{$mahang}.json";
if (!is_dir("../assets/image_orders")) {
    mkdir("../assets/image_orders", 0755, true);
}

// Lấy thông tin sản phẩm từ bảng tbmathang
$sql = "SELECT * FROM tbmathang WHERE mahang = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $mahang);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    echo "Sản phẩm không tồn tại.";
    exit;
}

// Xử lý xoá ảnh chi tiết (Delete image)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_image'])) {
    $imgToDelete = $_POST['delete_image']; // tên file ảnh cần xoá

    // Xoá khỏi bảng tbhinhanhchitiet
    $sql_delete_img = "DELETE FROM tbhinhanhchitiet WHERE mahang = ? AND hinhanh_chitiet = ?";
    $stmt_del = $conn->prepare($sql_delete_img);
    $stmt_del->bind_param("ss", $mahang, $imgToDelete);
    $stmt_del->execute();

    // Xoá file ảnh khỏi thư mục ../assets/images/
    $filePath = "../assets/images/" . $imgToDelete;
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    // Cập nhật file JSON chứa thứ tự ảnh
    if (file_exists($orderFile)) {
        $jsonOrder = json_decode(file_get_contents($orderFile), true);
        if (is_array($jsonOrder)) {
            if (($key = array_search($imgToDelete, $jsonOrder)) !== false) {
                unset($jsonOrder[$key]);
                $jsonOrder = array_values($jsonOrder);
                file_put_contents($orderFile, json_encode($jsonOrder));
            }
        }
    }
    $_SESSION['success_message'] = "Xoá ảnh chi tiết thành công!";
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// Xử lý cập nhật khi form gửi về
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    //Cập nhật thông tin sản phẩm và ảnh chính
    if (isset($_POST['update_product'])) {
        $tenhang  = trim($_POST['tenhang']);
        $mota     = trim($_POST['mota']);
        $dongia   = floatval($_POST['dongia']);
        $nguongoc = trim($_POST['nguongoc']);
        $thuonghieu = trim($_POST['thuonghieu']);
        $conhang  = trim($_POST['conhang']);

        // Nếu có file ảnh chính mới, xử lý upload
        $hinhanh = $product['hinhanh']; 
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

        $sql_update = "UPDATE tbmathang SET tenhang = ?, mota = ?, dongia = ?, nguongoc = ?, thuonghieu = ?, hinhanh = ?, conhang = ? WHERE mahang = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ssdsssss", $tenhang, $mota, $dongia, $nguongoc, $thuonghieu, $hinhanh, $conhang, $mahang);

        if ($stmt_update->execute()) {
            echo "<p style='color: green; text-align: center;'>Cập nhật thông tin sản phẩm thành công!</p>";
        } else {
            echo "<p style='color: red; text-align: center;'>Lỗi cập nhật sản phẩm: " . $conn->error . "</p>";
        }
    }

    //Cập nhật thứ tự ảnh chi tiết và upload ảnh mới
    if (isset($_POST['update_images'])) {
        // Cập nhật thứ tự cho ảnh hiện có:
        if (isset($_POST['file_names']) && isset($_POST['order'])) {
            $file_names = $_POST['file_names']; 
            $ordersInput = $_POST['order'];      
            $assoc = array();
            foreach ($file_names as $index => $fname) {
                $assoc[$fname] = intval($ordersInput[$index]);
            }
            asort($assoc);
            $newOrder = array();
            foreach ($assoc as $fname => $val) {
                $newOrder[] = $fname;
            }
            // Lưu mảng thứ tự mới vào file JSON
            file_put_contents($orderFile, json_encode($newOrder));
        }
        // Xử lý upload ảnh chi tiết mới (nếu có)
        if (isset($_FILES['new_images']) && !empty($_FILES['new_images']['name'][0])) {
            $currentOrder = file_exists($orderFile) ? json_decode(file_get_contents($orderFile), true) : array();
            if (!is_array($currentOrder)) {
                $currentOrder = array();
            }
            $files = $_FILES['new_images'];
            $file_count = count($files['name']);
            for ($i = 0; $i < $file_count; $i++) {
                $file_name = basename($files['name'][$i]);
                $target_file = "../assets/images/" . $file_name;
                if (move_uploaded_file($files['tmp_name'][$i], $target_file)) {
                    // Thêm ảnh mới vào bảng tbhinhanhchitiet
                    $sql_insert = "INSERT INTO tbhinhanhchitiet (mahang, hinhanh_chitiet) VALUES (?, ?)";
                    $stmt_insert = $conn->prepare($sql_insert);
                    $stmt_insert->bind_param("ss", $mahang, $file_name);
                    $stmt_insert->execute();
                    // Thêm ảnh mới vào mảng thứ tự (append)
                    $currentOrder[] = $file_name;
                }
            }
            // Lưu lại mảng thứ tự mới
            file_put_contents($orderFile, json_encode($currentOrder));
        }
        echo "<p style='color: green; text-align: center;'>Cập nhật ảnh chi tiết thành công!</p>";
    }
}

// Lấy danh sách ảnh chi tiết từ bảng
$sql_images = "SELECT hinhanh_chitiet FROM tbhinhanhchitiet WHERE mahang = ?";
$stmt_images = $conn->prepare($sql_images);
$stmt_images->bind_param("s", $mahang);
$stmt_images->execute();
$db_images = $stmt_images->get_result()->fetch_all(MYSQLI_ASSOC);

$images = array();
foreach ($db_images as $row) {
    $images[] = $row['hinhanh_chitiet'];
}

// Nếu file JSON tồn tại, sắp xếp lại mảng $images theo thứ tự trong file JSON
$jsonOrder = array();
if (file_exists($orderFile)) {
    $jsonOrder = json_decode(file_get_contents($orderFile), true);
    if (is_array($jsonOrder)) {
        $orderedImages = array();
        foreach ($jsonOrder as $fname) {
            if (in_array($fname, $images)) {
                $orderedImages[] = $fname;
            }
        }
        foreach ($images as $img) {
            if (!in_array($img, $orderedImages)) {
                $orderedImages[] = $img;
            }
        }
        $images = $orderedImages;
    }
}
?>

<!-- PHẦN SỬA THÔNG TIN SẢN PHẨM VÀ ẢNH CHÍNH -->
<h2 style="text-align: center;">Sửa sản phẩm</h2>
<form method="post" enctype="multipart/form-data" style="max-width: 500px; margin: auto;">
    <label>Mã hàng: <input type="text" name="mahang" value="<?= htmlspecialchars($product['mahang']) ?>" disabled></label><br><br>
    <label>Tên hàng: <input type="text" name="tenhang" value="<?= htmlspecialchars($product['tenhang']) ?>" required></label><br><br>
    <label>Mô tả: <textarea name="mota"><?= htmlspecialchars($product['mota']) ?></textarea></label><br><br>
    <label>Đơn giá: <input type="number" step="0.01" name="dongia" value="<?= htmlspecialchars($product['dongia']) ?>" required></label><br><br>
    <label>Nguồn gốc: <input type="text" name="nguongoc" value="<?= htmlspecialchars($product['nguongoc']) ?>" required></label><br><br>
    <label>Thương hiệu: <input type="text" name="thuonghieu" value="<?= htmlspecialchars($product['thuonghieu']) ?>" required></label><br><br>
    <label>Tình trạng:
        <select name="conhang" required>
            <option value="Còn hàng" <?= ($product['conhang'] == 'Còn hàng') ? 'selected' : '' ?>>Còn hàng</option>
            <option value="Hết hàng" <?= ($product['conhang'] == 'Hết hàng') ? 'selected' : '' ?>>Hết hàng</option>
        </select>
    </label><br><br>
    <label>Ảnh chính hiện tại:</label><br>
    <?php if (!empty($product['hinhanh'])): ?>
        <img src="../assets/images/<?= htmlspecialchars($product['hinhanh']) ?>" alt="Ảnh chính" width="200"><br><br>
    <?php else: ?>
        <p>Không có ảnh chính.</p><br>
    <?php endif; ?>
    <label>Chọn ảnh chính mới (nếu muốn thay đổi): <input type="file" name="hinhanh" accept="image/*"></label><br><br>
    <button type="submit" name="update_product" class="btn btn-primary">Cập nhật sản phẩm</button>
</form>

<hr style="max-width: 800px; margin: 40px auto;">

<!-- PHẦN QUẢN LÝ ẢNH CHI TIẾT -->
<h2 style="text-align: center;">Quản lý ảnh chi tiết</h2>
<form method="post" enctype="multipart/form-data" style="max-width: 800px; margin: auto;">
    <table border="1" cellpadding="8" cellspacing="0" width="100%">
        <thead>
            <tr style="background: #f0f0f0;">
                <th>Ảnh chi tiết</th>
                <th>Thứ tự hiển thị</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($images)): ?>
                <?php 
              
                $defaultOrder = array();
                foreach ($images as $index => $img) {
                    $defaultOrder[$img] = $index + 1;
                }
                ?>
                <?php foreach ($images as $img): ?>
                <tr>
                    <td style="text-align: center;">
                        <img src="../assets/images/<?= htmlspecialchars($img) ?>" alt="Ảnh chi tiết" width="120">
                        <br>
                        <small><?= htmlspecialchars($img) ?></small>
                    </td>
                    <td style="text-align: center;">
                        <?php 
                        $orderVal = (isset($jsonOrder) && is_array($jsonOrder) && in_array($img, $jsonOrder))
                            ? array_search($img, $jsonOrder) + 1
                            : $defaultOrder[$img];
                        ?>
                        <input type="number" name="order[]" value="<?= $orderVal ?>" style="width: 60px;">
                        <input type="hidden" name="file_names[]" value="<?= htmlspecialchars($img) ?>">
                    </td>
                    <td style="text-align: center;">
                        <!-- Nút xoá ảnh chi tiết -->
                        <button type="submit" name="delete_image" value="<?= htmlspecialchars($img) ?>" class="btn btn-danger btn-sm">Xoá ảnh</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3" style="text-align: center;">Không có ảnh chi tiết nào.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <br>
    <!-- Phần upload thêm ảnh chi tiết mới -->
    <label>Thêm ảnh chi tiết mới:
        <input type="file" name="new_images[]" accept="image/*" multiple>
    </label>
    <br><br>
    <button type="submit" name="update_images" class="btn btn-primary">Cập nhật ảnh chi tiết</button>
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
<script>
   
    function toggleDetails(divId) {
        var detailsDiv = document.getElementById(divId);
        if (detailsDiv.style.display === "none" || detailsDiv.style.display === "") {
            detailsDiv.style.display = "table-row";
        } else {
            detailsDiv.style.display = "none";
        }
    }
</script>
