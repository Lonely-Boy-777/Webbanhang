<?php  
session_start();
include '../includes/db.php';
include '../templates/header.php';

// Tính số lượng sản phẩm duy nhất trong giỏ hàng từ session
$cart_count = 0;
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
  $cart_count = count($_SESSION['cart']);
}

// Kiểm tra mã hàng trong URL
if (isset($_GET['mahang'])) {
    $mahang = trim($_GET['mahang']);

    // Lấy thông tin sản phẩm từ tbmathang
    $sql_product = "SELECT * FROM tbmathang WHERE mahang = ?";
    $stmt_product = $conn->prepare($sql_product);
    $stmt_product->bind_param("s", $mahang);
    $stmt_product->execute();
    $result_product = $stmt_product->get_result();
    $product = $result_product->fetch_assoc();

    // Kiểm tra xem sản phẩm có tồn tại không
    if (!$product) {
        echo "<p style='text-align: center; color: red;'>Không tìm thấy sản phẩm với mã hàng: " . htmlspecialchars($mahang) . "</p>";
        include '../templates/footer.php';
        exit;
    }

    // Lấy ảnh chi tiết từ tbhinhanhchitiet
    $sql_images = "SELECT hinhanh_chitiet FROM tbhinhanhchitiet WHERE mahang = ?";
    $stmt_images = $conn->prepare($sql_images);
    $stmt_images->bind_param("s", $mahang);
    $stmt_images->execute();
    $db_images = $stmt_images->get_result()->fetch_all(MYSQLI_ASSOC);

    // Tạo mảng danh sách ảnh từ DB
    $images = [];
    foreach ($db_images as $row) {
        $images[] = $row['hinhanh_chitiet'];
    }

    // Nếu có file JSON lưu thứ tự ảnh, sắp xếp lại theo thứ tự đó
    $orderFile = "../assets/image_orders/order_{$mahang}.json";
    if (file_exists($orderFile)) {
        $jsonOrder = json_decode(file_get_contents($orderFile), true);
        if (is_array($jsonOrder)) {
            $orderedImages = [];
            foreach ($jsonOrder as $fname) {
                if (in_array($fname, $images)) {
                    $orderedImages[] = $fname;
                }
            }
            // Thêm những ảnh chưa có trong file JSON vào cuối danh sách
            foreach ($images as $img) {
                if (!in_array($img, $orderedImages)) {
                    $orderedImages[] = $img;
                }
            }
            $images = $orderedImages;
        }
    }
} else {
    echo "<p style='text-align: center; color: red;'>Vui lòng cung cấp mã hàng!</p>";
    include '../templates/footer.php';
    exit;
}
?>

<div class="product-detail-container">
    <div class="product-header">
        <h1 class="product-title"><?php echo htmlspecialchars($product['tenhang']); ?></h1>
    </div>
    
    <div class="product-content">
        <!-- Cột bên trái: Ảnh sản phẩm -->
        <div class="product-images">
            <?php if (!empty($images)) { ?>
                <div class="slideshow-container">
                    <?php foreach ($images as $index => $image) { ?>
                        <div class="mySlides fade">
                            <div class="numbertext"><?php echo $index + 1; ?> / <?php echo count($images); ?></div>
                            <img src="../assets/images/<?php echo htmlspecialchars($image); ?>" alt="Ảnh chi tiết">
                        </div>
                    <?php } ?>
                    <a class="prev" onclick="plusSlides(-1)">❮</a>
                    <a class="next" onclick="plusSlides(1)">❯</a>
                </div>
                <div class="thumbnail-container">
                    <?php foreach ($images as $index => $image) { ?>
                        <img class="thumbnail" src="../assets/images/<?php echo htmlspecialchars($image); ?>" onclick="currentSlide(<?php echo $index + 1; ?>)">
                    <?php } ?>
                </div>
            <?php } else { ?>
                <div class="no-images">Không có ảnh chi tiết nào.</div>
            <?php } ?>
        </div>

        <!-- Cột bên phải: Thông tin sản phẩm, mô tả, tình trạng & nút mua -->
        <div class="product-details">
            <p class="price"><?php echo number_format($product['dongia'], 0); ?> VND</p>
            
            <!-- Mô tả sản phẩm -->
            <div class="product-description">
                <div class="description-content">
                    <?php echo nl2br(htmlspecialchars($product['mota'])); ?>
                </div>
            </div>
            
            <!-- Container cho tình trạng và nút Thêm vào giỏ hàng trên cùng 1 dòng -->
            <div class="status-add-container">
                <div class="product-info">
                    <ul class="info-list">
                        <li><strong>Tình trạng:</strong> <?php echo htmlspecialchars($product['conhang']); ?></li>
                    </ul>
                </div>
            
                <div class="action-buttons">
                    <button class="add-to-cart-btn" data-id="<?php echo htmlspecialchars($product['mahang']); ?>" <?php echo (strtolower(trim($product['conhang'])) === 'hết hàng') ? 'disabled' : ''; ?>>
                        Thêm vào giỏ hàng
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div class="toast" id="toast">
    <i class="fas fa-check-circle"></i> Sản phẩm đã được thêm vào giỏ hàng!
</div>

<!-- JavaScript xử lý slideshow và Thêm vào giỏ hàng -->
<script>
let slideIndex = 1;
showSlides(slideIndex);

function plusSlides(n) {
    showSlides(slideIndex += n);
}

function currentSlide(n) {
    showSlides(slideIndex = n);
}

function showSlides(n) {
    let i;
    let slides = document.getElementsByClassName("mySlides");
    let thumbnails = document.getElementsByClassName("thumbnail");
    if (slides.length === 0) return;
    if (n > slides.length) { slideIndex = 1; }
    if (n < 1) { slideIndex = slides.length; }
    for (i = 0; i < slides.length; i++) {
        slides[i].style.display = "none";
    }
    for (i = 0; i < thumbnails.length; i++) {
        thumbnails[i].className = thumbnails[i].className.replace(" active", "");
    }
    slides[slideIndex - 1].style.display = "block";
    thumbnails[slideIndex - 1].className += " active";
}

// Xử lý nút "Thêm vào giỏ hàng" qua AJAX
document.querySelectorAll('.add-to-cart-btn').forEach(button => {
    button.addEventListener('click', function() {
        if (this.disabled) return;
        const productId = this.getAttribute('data-id');
        fetch(`cart.php?add_to_cart=${encodeURIComponent(productId)}`)
            .then(response => response.text())
            .then(data => {
                // Hiển thị toast notification
                const toast = document.getElementById('toast');
                toast.style.display = 'block';
                setTimeout(() => {
                    toast.style.display = 'none';
                }, 1500);

                // Cập nhật số đếm giỏ hàng trên header
                const badge = document.querySelector('.cart-box .cart-count');
                if (badge) {
                    badge.textContent = parseInt(badge.textContent) + 1;
                } else {
                    const cartLink = document.querySelector('.cart-box .cart-link');
                    const newBadge = document.createElement('span');
                    newBadge.className = 'cart-count';
                    newBadge.textContent = '1';
                    cartLink.appendChild(newBadge);
                }
            })
            .catch(error => {
                alert("Có lỗi xảy ra, vui lòng thử lại!");
                console.error(error);
            });
    });
});
</script>

<style>
.product-detail-container {
    max-width: 1200px;
    margin: 30px auto;
    padding: 20px;
    background: #fff;
    font-family: Arial, sans-serif;
}

.product-header {
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

.product-title {
    font-size: 28px;
    color: #333;
    margin: 0;
}

.product-content {
    display: flex;
    gap: 40px;
    flex-wrap: wrap;
}

.product-images {
    flex: 1;
    min-width: 300px;
}

.slideshow-container {
    position: relative;
    width: 500px;
    height: 500px;
    margin: auto;
    background: #f5f5f5;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
}

.mySlides {
    width: 100%;
    height: 100%;
    display: none;
    align-items: center;
    justify-content: center;
}

.mySlides img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: 8px;
}

.prev, .next {
    cursor: pointer;
    position: absolute;
    top: 50%;
    padding: 12px;
    color: #fff;
    font-size: 20px;
    background: rgba(0, 0, 0, 0.6);
    border-radius: 50%;
    transform: translateY(-50%);
    transition: background 0.3s;
}

.prev { left: 15px; }
.next { right: 15px; }
.prev:hover, .next:hover { background: rgba(0, 0, 0, 0.8); }

.numbertext {
    position: absolute;
    top: 10px;
    left: 10px;
    color: #fff;
    background: rgba(0, 0, 0, 0.5);
    padding: 5px 10px;
    border-radius: 3px;
    font-size: 12px;
}

.thumbnail-container {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 15px;
    flex-wrap: wrap;
}

.thumbnail {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border: 2px solid #ddd;
    border-radius: 5px;
    cursor: pointer;
    transition: border-color 0.3s;
}

.thumbnail.active, .thumbnail:hover {
    border-color: #F39C12;
}

.product-details {
    flex: 1;
    min-width: 300px;
    display: flex;
    flex-direction: column;
    gap: 20px;
    background: #F9F9F9;
    padding: 10px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.price {
    font-size: 30px;
    color: #E74C3C;
    font-weight: bold;
    margin: 0;
}

.product-description {
    background: #fff;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 8px;
}
.description-content {
    font-size: 14px;
    line-height: 1.8;
    color: #444;
}

.product-info {
    background: #fff;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
}
.info-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    gap: 20px;
    justify-content: center;
    text-align: center; 
}
.info-list li {
    font-size: 16px;
    color: #666;
}
.info-list li strong {
    color: #333;
}

.action-buttons {
    text-align: center;
}
.add-to-cart-btn {
    padding: 15px 40px;
    background: #F39C12;
    color: #fff;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.3s, box-shadow 0.3s;
}
.add-to-cart-btn:hover {
    background: #E67E22;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}
.add-to-cart-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.status-add-container {
    display: flex;
    align-items: center;
}

.product-info {
    flex-grow: 1;
}

.action-buttons {
    margin-left: 10px;
}

.toast {
    display: none;
    position: fixed;
    bottom: 30px;
    right: 30px;
    background: rgba(0,0,0,0.8);
    color: #fff;
    padding: 15px 20px;
    border-radius: 4px;
    font-size: 16px;
}

@keyframes fade { from { opacity: 0.4; } to { opacity: 1; } }
</style>

<?php include '../templates/footer.php'; ?>
