<?php  
ob_start();
session_start();
include '../includes/db.php';
include '../templates/header.php';
?>

<style>
    .checkout-container {
        max-width: 600px;
        margin: 50px auto; 
        display: flex; 
        justify-content: center; 
        align-items: center; 
        min-height: 50vh; 
    }
    .checkout-card {
        border: 1px solid #ddd;
        border-radius: 10px;
        padding: 30px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        background-color: #fff;
        width: 100%; 
        text-align: center; 
    }
    .checkout-card h4 {
        margin-bottom: 30px;
    }
    .order-value {
        font-size: 1.2rem;
        font-weight: bold;
        color: #d9534f; 
        background-color: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 20px;
    }
    .btn-confirm, .btn-continue {
        font-size: 1.2rem;
        padding: 15px 30px;
        display: inline-block;
        width: auto;
        margin: 0 auto; 
    }
    .btn-continue {
        margin-top: 20px;
    }
    .alert {
        margin-bottom: 20px;
    }
</style>

<?php
// Kiểm tra đăng nhập trước khi vào trang thanh toán
if (!isset($_SESSION['username'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];  
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];

// Lấy thông tin khách hàng từ bảng tbkhachhang dựa trên username
$check_customer_sql = "SELECT * FROM tbkhachhang WHERE username = ?";
$stmt = $conn->prepare($check_customer_sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$customer_result = $stmt->get_result();

if ($customer_result->num_rows == 0) {
    echo '<div class="container my-5"><div class="alert alert-warning text-center">Khách hàng không tồn tại trong hệ thống!</div></div>';
    exit;
}

$customer = $customer_result->fetch_assoc();
$makhach = $customer['makhach'];  // Lấy mã khách hàng

// Kiểm tra giỏ hàng có rỗng không
if (empty($_SESSION['cart'])) {
    echo '<div class="container my-5"><div class="alert alert-warning text-center">Giỏ hàng của bạn đang trống!</div></div>';
    exit;
}

// Tính tổng giá trị đơn hàng từ giỏ hàng 
$total_price = 0;
foreach ($_SESSION['cart'] as $item_id => $item) {
    $checked = isset($item['checked']) ? $item['checked'] : true;
    if (!$checked) continue;
    
    $product_sql = "SELECT dongia FROM tbmathang WHERE mahang = ?";
    $stmt = $conn->prepare($product_sql);
    $stmt->bind_param("s", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    if (!$product) continue;
    $quantity = (int)$item['quantity'];
    $unit_price = (float)$product['dongia'];
    $total_price += $unit_price * $quantity;
}

// Tạo mã đơn hàng sử dụng microtime (tính một lần cho đơn hàng)
$micro = str_replace('.', '', microtime(true));
$order_id = 'DH' . $micro . rand(100, 999);

$conn->begin_transaction();

try {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_payment'])) {
        // Lưu đơn hàng vào bảng tbdonhang
        $sql_order = "INSERT INTO tbdonhang (madonhang, makhach, ngaymua, tinhtrang) VALUES (?, ?, NOW(), 'Đang xử lý')";
        $stmt_order = $conn->prepare($sql_order);
        $stmt_order->bind_param("ss", $order_id, $makhach);
        $stmt_order->execute();

        // Lưu chi tiết đơn hàng vào bảng tbchitietdonhang 
        $index = 1;
        foreach ($_SESSION['cart'] as $item_id => $item) {
            $checked = isset($item['checked']) ? $item['checked'] : true;
            if (!$checked) continue;
            
            // Tính microtime mới cho từng sản phẩm để đảm bảo tính duy nhất
            $currentMicro = str_replace('.', '', microtime(true));
            
            $product_code = strtoupper(trim($item_id));
            $product_sql = "SELECT dongia, tenhang FROM tbmathang WHERE mahang = ?";
            $stmt_product = $conn->prepare($product_sql);
            $stmt_product->bind_param("s", $product_code);
            $stmt_product->execute();
            $product_result = $stmt_product->get_result();
            $product = $product_result->fetch_assoc();

            if (!$product) {
                echo '<div class="alert alert-warning text-center">Không tìm thấy sản phẩm với mã: ' . htmlspecialchars($product_code) . '</div>';
                continue;
            }

            $quantity = (int)$item['quantity'];
            $unit_price = (float)$product['dongia'];
            $machitiet = 'CTDH' . $currentMicro . sprintf('%03d', $index);

            $sql_detail = "INSERT INTO tbchitietdonhang (machitiet, madonhang, mahang, soluong, dongia) VALUES (?, ?, ?, ?, ?)";
            $stmt_detail = $conn->prepare($sql_detail);
            $stmt_detail->bind_param("sssii", $machitiet, $order_id, $product_code, $quantity, $unit_price);
            $stmt_detail->execute();

            $index++;
        }

        // Nếu tổng giá trị đơn hàng bằng 0, rollback giao dịch
        if ($total_price == 0) {
            $conn->rollback();
            echo '<div class="container my-5"><div class="alert alert-warning text-center">Không có sản phẩm hợp lệ để thanh toán!</div></div>';
            exit;
        }

        $conn->commit();
        ?>
        <div class="checkout-container">
            <div class="checkout-card">
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> Đơn hàng của bạn đã được xác nhận!<br>
                    Giá trị đơn hàng: <?php echo number_format($total_price, 2); ?> VND
                </div>
                <a href="index.php" class="btn btn-primary btn-continue">
                    <i class="fas fa-shopping-bag"></i> Tiếp tục mua sắm
                </a>
            </div>
        </div>
        <?php
        // Xoá chỉ các sản phẩm đã được tick (đã thanh toán) khỏi giỏ hàng,
        
        foreach ($_SESSION['cart'] as $item_id => $item) {
            if (isset($item['checked']) && $item['checked'] === true) {
                unset($_SESSION['cart'][$item_id]);
            }
        }
    } else {
        ?>
        <div class="checkout-container">
            <div class="checkout-card">
                <p class="order-value">Đơn hàng của bạn có giá trị là: <?php echo number_format($total_price, 2); ?> VND</p>
                <?php if ($total_price > 0): ?>
                    <p>Bạn có muốn xác nhận đặt đơn hàng?</p>
                    <form method="post">
                        <button type="submit" name="confirm_payment" class="btn btn-success btn-confirm">
                            <i class="fas fa-credit-card"></i> Xác nhận đặt hàng
                        </button>
                    </form>
                <?php else: ?>
                    <p class="alert alert-warning">Không có sản phẩm nào được chọn để thanh toán!</p>
                    <button class="btn btn-success btn-confirm" disabled>
                        <i class="fas fa-credit-card"></i> Xác nhận đặt hàng
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
} catch (Exception $e) {
    $conn->rollback();
    echo '<div class="container my-5"><div class="alert alert-danger text-center">
    <i class="fas fa-exclamation-triangle"></i> Lỗi khi tạo đơn hàng: ' . $e->getMessage() . '</div></div>';
}

include '../templates/footer.php';
?>
