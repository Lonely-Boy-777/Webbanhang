<?php

include '../includes/db.php';
include '../templates/header.php';

// Tính số lượng sản phẩm duy nhất trong giỏ hàng từ session
$cart_count = 0;
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
  $cart_count = count($_SESSION['cart']);
}

// Lấy dữ liệu lọc từ URL
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$brand = isset($_GET['brand']) ? trim($_GET['brand']) : "";
$price_range = isset($_GET['price_range']) ? trim($_GET['price_range']) : "";
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : "";  // Thêm biến sắp xếp

// Lấy seed từ URL hoặc tạo mới nếu chưa có
$seed = isset($_GET['seed']) ? (int)$_GET['seed'] : mt_rand();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cửa Hàng Đồng Hồ Cao Cấp</title>
  <link rel="stylesheet" href="../LIB/fontawesome-free-6.4.2-web/css/all.min.css">
  <!-- Slick Slider CSS từ CDN -->
  <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.css" />
  <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick-theme.css" />
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: 'Arial', sans-serif;
      background-color: #f4f4f4;
    }

    @media (max-width: 768px) {
      .header-container {
        flex-direction: column;
        gap: 15px;
      }

      .search-box {
        width: 100%;
        max-width: none;
        margin: 10px 0;
      }

      .main-nav ul {
        flex-wrap: wrap;
        justify-content: center;
      }
    }

    /* ===== Hero Section với Slideshow ===== */
    .hero {
      position: relative;
      width: 100%;
      overflow: hidden;
      margin-bottom: 20px;
    }

    .slideshow-container {
      max-width: 1200px;
      height: 508px;
      position: relative;
      margin: auto;
    }

    .slideshow-container img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 7px;
    }

    .prev,
    .next {
      cursor: pointer;
      position: absolute;
      top: 50%;
      padding: 16px;
      margin-top: -22px;
      color: white;
      font-weight: bold;
      font-size: 18px;
      transition: 0.6s ease;
      user-select: none;
      border-radius: 0 3px 3px 0;
    }

    .next {
      right: 0;
      border-radius: 3px 0 0 3px;
    }

    .prev:hover,
    .next:hover {
      background-color: rgba(0, 0, 0, 0.8);
    }

    .numbertext {
      color: #b77d09;
      font-size: 12px;
      padding: 8px 12px;
      position: absolute;
      top: 0;
    }

    .dot {
      cursor: pointer;
      height: 15px;
      width: 15px;
      margin: 0 2px;
      background-color: #bbb;
      border-radius: 50%;
      display: inline-block;
      transition: background-color 0.6s ease;
    }

    .active,
    .dot:hover {
      background-color: #fed700;
    }

    .fade {
      animation-name: fade;
      animation-duration: 1.5s;
    }

    @keyframes fade {
      from {
        opacity: 0.4;
      }

      to {
        opacity: 1;
      }
    }

    @media only screen and (max-width: 300px) {

      .prev,
      .next,
      .numbertext {
        font-size: 11px;
      }
    }

    /* ===== Sidebar lọc sản phẩm ===== */
    .sidebar {
      max-width: 1200px;
      margin: 0 auto 20px;
      padding: 15px 0 15px 20px;
      background: #fff;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
      display: flex;
      flex-direction: row;
      align-items: center;
      justify-content: space-between;
      gap: 25px;
    }

    .sidebar h3 {
      margin: 0;
      font-size: 18px;
      color: #333;
    }

    .sidebar form {
      display: flex;
      flex-direction: row;
      flex-grow: 1;
      align-items: center;
      justify-content: space-around;
      gap: 20px;
    }

    .filter-group {
      display: flex;
      flex-direction: row;
      align-items: center;
      gap: 10px;
      min-width: 180px;
      flex-wrap: nowrap;
    }

    .filter-group label {
      white-space: nowrap;

      margin-right: 5px;
    }

    .sidebar label {
      margin-bottom: 5px;
      font-size: 14px;
      color: #555;
    }

    .sidebar select {
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 4px;
      width: 100%;
      max-width: 200px;
      font-size: 14px;
    }

    .sidebar button {
      padding: 10px 20px;
      background: #333;
      color: #fff;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;

    }

    .sidebar button:hover {
      background: #555;
    }

    /* ===== Product Listing ===== */
    .product-listing {
      max-width: 1200px;
      margin: 0 auto;
      padding: 20px;
      background: #fff;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .product-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 20px;
    }

    .product-card {
      background: #fff;
      border: 1px solid #eee;
      border-radius: 8px;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      transition: transform 0.3s, box-shadow 0.3s;
    }

    .product-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .product-card img {
      width: 100%;
      height: 300px;
      object-fit: cover;
    }

    .card-content {
      padding: 15px;
      flex: 1;
      display: flex;
      flex-direction: column;
      text-align: center;
    }

    .card-content h3 {
      margin: 0 0 10px;
      font-size: 18px;
      color: #333;
    }

    .action-group {
      margin-top: auto;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .card-content .price {
      font-weight: bold;
      color: #e74c3c;
      margin-bottom: 8px;
      font-size: 16px;
      white-space: nowrap;
  
    }

    .card-content button.add-to-cart {
      padding: 10px;
      background: #e67e22;
      color: #fff;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 5px;
      font-size: 14px;
    }

    .card-content button.add-to-cart:hover {
      background: #d35400;
    }

    .card-content button.add-to-cart:disabled {
      background: #ccc;
      cursor: not-allowed;
    }

    /* ===== Phân trang ===== */
    .pagination {
      text-align: center;
      margin: 20px 0;
    }

    .pagination a {
      padding: 10px 15px;
      margin: 0 5px;
      border: 1px solid #ccc;
      color: #333;
      text-decoration: none;
      border-radius: 4px;
      transition: background 0.3s;
    }

    .pagination a:hover,
    .pagination a.active {
      background: #333;
      color: #fff;
      border-color: #333;
    }

    .toast {
      display: none;
      position: fixed;
      bottom: 30px;
      right: 30px;
      background: rgba(0, 0, 0, 0.8);
      color: #fff;
      padding: 15px 20px;
      border-radius: 4px;
    }

    /* ===== Carousel: Sản phẩm nổi bật ===== */
    .highest-price-carousel {
      max-width: 1200px;
      margin: 40px auto;
      padding: 30px;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
    }

    .highest-price-carousel h2 {
      text-align: center;
      margin-bottom: 30px;
      font-size: 26px;
      font-weight: 700;
      color: #222;
      letter-spacing: 1px;
    }

    .carousel-container {
      position: relative;
      display: flex;
      gap: 20px;
    }

    .carousel-item {
      background: #fafafa;
      border-radius: 10px;
      padding: 20px;
      transition: transform 0.3s ease, box-shadow 0.3s ease, border 0.3s ease;
      text-align: center;
      border: 1px solid transparent;
    }

    .carousel-item a {
      display: flex;
      flex-direction: column;
      height: 100%;
    }

    .carousel-item:hover {
      transform: translateY(-8px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
      border-color: #e0e0e0;
    }

    .carousel-item a {
      text-decoration: none;
      color: inherit;
    }

    .carousel-item img {
      width: 200px;
      height: 200px;
      object-fit: cover;
      border-radius: 8px;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      margin: 0 auto;
      display: block;
    }

    .carousel-item:hover img {
      transform: scale(1.08);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    .carousel-item h3 {
      font-size: 20px;
      margin: 15px 0 8px;
      color: #333;
      font-weight: 600;
      transition: color 0.3s;
      height: 5em;
      overflow: hidden;
    }

    .carousel-item:hover h3 {
      color: #d35400;
    }

    .carousel-item .price {
      font-size: 18px;
      color: #d35400;
      font-weight: bold;
      margin-top: auto;
      white-space: nowrap;
      transition: color 0.3s;
    }

    .carousel-item:hover .price {
      color: #a04000;
    }

    /* Arrow styles cho Slick Slider */
    .slick-prev,
    .slick-next {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      z-index: 10;
      background: #333;
      border: none;
      border-radius: 50%;
      width: 45px;
      height: 45px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
    }

    .slick-prev:hover,
    .slick-next:hover {
      background: #555;
      transform: translateY(-50%) scale(1.1);
    }

    .slick-prev {
      left: -25px;
    }

    .slick-next {
      right: -25px;
    }

    .slick-prev:focus,
    .slick-next:focus {
      outline: none;
    }

    .slick-prev i,
    .slick-next i {
      font-size: 20px;
      color: #fff;
      pointer-events: none;
    }

    @media (max-width: 1024px) {
      .carousel-item img {
        width: 180px;
        height: 180px;
      }
    }

    @media (max-width: 768px) {
      .carousel-item img {
        width: 160px;
        height: 160px;
      }
    }

    @media (max-width: 480px) {
      .carousel-item img {
        width: 140px;
        height: 140px;
      }
    }

    @media (max-width: 768px) {
      .sidebar {
        flex-direction: column;
        align-items: stretch;
        padding: 20px;
      }

      .sidebar form {
        flex-direction: column;
        gap: 15px;
      }

      .filter-group {
        width: 100%;
      }

      .sidebar select {
        max-width: none;
      }
    }
  </style>
</head>

<body>

  <!-- Hero Section với Slideshow -->
  <div class="hero">
    <div class="slideshow-container">
      <div class="mySlides fade">
        <div class="numbertext">1 / 4</div>
        <img src="../assets/images/rolex_banner2.jpg" alt="Banner 1">
      </div>
      <div class="mySlides fade">
        <div class="numbertext">2 / 4</div>
        <img src="../assets/images/banner4.jpg" alt="Banner 1">
      </div>
      <div class="mySlides fade">
        <div class="numbertext">3 / 4</div>
        <img src="../assets/images/rolex_banner.jpg" alt="Banner 2">
      </div>
      <div class="mySlides fade">
        <div class="numbertext">4 / 4</div>
        <img src="../assets/images/rolex_banner3.jpg" alt="Banner 3">
      </div>
      <a class="prev" onclick="plusSlides(-1)">❮</a>
      <a class="next" onclick="plusSlides(1)">❯</a>
    </div>
    <br>
    <div style="text-align:center">
      <span class="dot" onclick="currentSlide(1)"></span>
      <span class="dot" onclick="currentSlide(2)"></span>
      <span class="dot" onclick="currentSlide(3)"></span>
    </div>
  </div>

<section class="highest-price-carousel">
  <h2>Sản phẩm nổi bật</h2>
  <div class="carousel-container">
    <?php
    $sql_highest = "SELECT * FROM tbmathang ORDER BY dongia DESC LIMIT 12";
    $result_highest = $conn->query($sql_highest);
    $topProducts = [];
    while ($row_high = $result_highest->fetch_assoc()) {
      $topProducts[] = $row_high;
    }
    // Xáo trộn thứ tự của các sản phẩm
    shuffle($topProducts);
    
    foreach ($topProducts as $row_high) {
      $hinhanh = !empty($row_high['hinhanh']) ? "../assets/images/" . $row_high['hinhanh'] : "../assets/images/default.jpg";
      if (!file_exists($hinhanh)) {
        $hinhanh = "../assets/images/default.jpg";
      }
    ?>
      <div class="carousel-item">
        <a href="product_detail.php?mahang=<?php echo urlencode($row_high['mahang']); ?>">
          <img src="<?php echo $hinhanh; ?>" alt="<?php echo htmlspecialchars($row_high['tenhang']); ?>">
          <h3><?php echo htmlspecialchars($row_high['tenhang']); ?></h3>
          <span class="price"><?php echo number_format($row_high['dongia'], 0); ?> VND</span>
        </a>
      </div>
    <?php } ?>
  </div>
</section>


  <!-- Sidebar lọc sản phẩm -->
  <aside class="sidebar">
    <h3>Lọc theo</h3>
    <form action="index.php" method="GET">
      <div class="filter-group">
        <label for="brand">Hãng:</label>
        <select name="brand" id="brand">
          <option value="">Tất cả</option>
          <?php
          $brands_sql = "SELECT DISTINCT thuonghieu FROM tbmathang WHERE thuonghieu IS NOT NULL";
          $brands_result = $conn->query($brands_sql);
          while ($brand_row = $brands_result->fetch_assoc()) { ?>
            <option value="<?php echo htmlspecialchars($brand_row['thuonghieu']); ?>"
              <?php echo $brand === $brand_row['thuonghieu'] ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($brand_row['thuonghieu']); ?>
            </option>
          <?php } ?>
        </select>
      </div>
      <div class="filter-group">
        <label for="price_range">Khoảng giá:</label>
        <select name="price_range" id="price_range">
          <option value="">Tất cả</option>
          <option value="0-50000000" <?php echo $price_range === '0-50000000' ? 'selected' : ''; ?>>
            Dưới 50 triệu
          </option>
          <option value="50000000-200000000" <?php echo $price_range === '50000000-200000000' ? 'selected' : ''; ?>>
            50 triệu - 200 triệu
          </option>
          <option value="200000000-500000000" <?php echo $price_range === '200000000-500000000' ? 'selected' : ''; ?>>
            200 triệu - 500 triệu
          </option>
          <option value="500000000-1000000000" <?php echo $price_range === '500000000-1000000000' ? 'selected' : ''; ?>>
            500 triệu - 1 tỷ
          </option>
          <option value="1000000000+" <?php echo $price_range === '1000000000+' ? 'selected' : ''; ?>>
            Trên 1 tỷ
          </option>
        </select>
      </div>
      <!-- Dropdown sắp xếp theo giá -->
      <div class="filter-group">
        <label for="sort">Sắp xếp:</label>
        <select name="sort" id="sort">
          <option value="" <?php echo $sort === "" ? 'selected' : ''; ?>>Mặc định</option>
          <option value="price_asc" <?php echo $sort === "price_asc" ? 'selected' : ''; ?>>Giá tăng dần</option>
          <option value="price_desc" <?php echo $sort === "price_desc" ? 'selected' : ''; ?>>Giá giảm dần</option>
        </select>
      </div>
      <input type="hidden" name="seed" value="<?php echo $seed; ?>">
      <button type="submit">Lọc sản phẩm</button>
    </form>
  </aside>

  <!-- Danh sách sản phẩm -->
  <section class="product-listing">
    <div class="product-grid">
      <?php
      $limit = 12;
      $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
      $offset = ($page - 1) * $limit;

      $sql = "SELECT * FROM tbmathang WHERE tenhang LIKE ?";
      $params = ["%$search%"];
      $types = "s";
      if (!empty($brand)) {
        $sql .= " AND thuonghieu = ?";
        $params[] = $brand;
        $types .= "s";
      }
      if (!empty($price_range)) {
        switch ($price_range) {
          case '0-50000000':
            $sql .= " AND dongia <= 50000000";
            break;
          case '50000000-200000000':
            $sql .= " AND dongia BETWEEN 50000000 AND 200000000";
            break;
          case '200000000-500000000':
            $sql .= " AND dongia BETWEEN 500000000 AND 200000000";
            break;
          case '500000000-1000000000':
            $sql .= " AND dongia BETWEEN 500000000 AND 1000000000";
            break;
          case '1000000000+':
            $sql .= " AND dongia > 1000000000";
            break;
        }
      }
      // Xử lý sắp xếp: nếu có chọn sort thì dùng ORDER BY theo giá, nếu không thì dùng thứ tự ngẫu nhiên
      if ($sort === "price_asc") {
        $sql .= " ORDER BY dongia ASC";
      } elseif ($sort === "price_desc") {
        $sql .= " ORDER BY dongia DESC";
      } else {
        $sql .= " ORDER BY RAND($seed)";
      }
      $sql .= " LIMIT ?, ?";
      $params[] = $offset;
      $params[] = $limit;
      $types .= "ii";

      $stmt = $conn->prepare($sql);
      $stmt->bind_param($types, ...$params);
      $stmt->execute();
      $result = $stmt->get_result();
      while ($row = $result->fetch_assoc()) {
        $hinhanh = !empty($row['hinhanh']) ? "../assets/images/" . $row['hinhanh'] : "../assets/images/default.jpg";
        if (!file_exists($hinhanh)) {
          $hinhanh = "../assets/images/default.jpg";
        }
        $disabled = (strtolower(trim($row['conhang'])) === 'hết hàng') ? 'disabled' : '';
      ?>
        <div class="product-card">
          <a href="product_detail.php?mahang=<?php echo urlencode($row['mahang']); ?>">
            <img src="<?php echo $hinhanh; ?>" alt="<?php echo htmlspecialchars($row['tenhang']); ?>">
          </a>
          <div class="card-content">
            <h3><?php echo htmlspecialchars($row['tenhang']); ?></h3>
            <div class="action-group">
              <span class="price"><?php echo number_format($row['dongia'], 0); ?> VND</span>
              <button class="add-to-cart" data-id="<?php echo $row['mahang']; ?>" <?php echo $disabled ? 'disabled' : ''; ?>>
                <?php echo $disabled ? "Hết hàng" : '<i class="fas fa-cart-plus"></i> Thêm vào giỏ'; ?>
              </button>
            </div>
          </div>
        </div>
      <?php } ?>
    </div>
  </section>

  <!-- Phân trang -->
  <div class="pagination">
    <?php
    $sql_total = "SELECT COUNT(*) AS count FROM tbmathang WHERE tenhang LIKE ?";
    $total_params = ["%$search%"];
    $total_types = "s";
    if (!empty($brand)) {
      $sql_total .= " AND thuonghieu = ?";
      $total_params[] = $brand;
      $total_types .= "s";
    }
    if (!empty($price_range)) {
      switch ($price_range) {
        case '0-50000000':
          $sql_total .= " AND dongia <= 50000000";
          break;
        case '50000000-200000000':
          $sql_total .= " AND dongia BETWEEN 50000000 AND 200000000";
          break;
        case '200000000-500000000':
          $sql_total .= " AND dongia BETWEEN 500000000 AND 200000000";
          break;
        case '500000000-1000000000':
          $sql_total .= " AND dongia BETWEEN 500000000 AND 1000000000";
          break;
        case '1000000000+':
          $sql_total .= " AND dongia > 1000000000";
          break;
      }
    }
    $stmt_total = $conn->prepare($sql_total);
    $stmt_total->bind_param($total_types, ...$total_params);
    $stmt_total->execute();
    $total_items = $stmt_total->get_result()->fetch_assoc()['count'];
    $total_pages = ceil($total_items / $limit) ?: 1;
    if ($page > 1) {
      echo '<a href="index.php?page=' . ($page - 1) . '&seed=' . $seed . '&search=' . urlencode($search) . '&brand=' . urlencode($brand) . '&price_range=' . urlencode($price_range) . '&sort=' . urlencode($sort) . '">« Trước</a>';
    }
    for ($i = 1; $i <= $total_pages; $i++) {
      $active = ($i == $page) ? 'active' : '';
      echo '<a class="' . $active . '" href="index.php?page=' . $i . '&seed=' . $seed . '&search=' . urlencode($search) . '&brand=' . urlencode($brand) . '&price_range=' . urlencode($price_range) . '&sort=' . urlencode($sort) . '">' . $i . '</a>';
    }
    if ($page < $total_pages) {
      echo '<a href="index.php?page=' . ($page + 1) . '&seed=' . $seed . '&search=' . urlencode($search) . '&brand=' . urlencode($brand) . '&price_range=' . urlencode($price_range) . '&sort=' . urlencode($sort) . '">Sau »</a>';
    }
    ?>
  </div>

  <!-- Toast Notification -->
  <div class="toast" id="toast">
    <i class="fas fa-check-circle"></i> Sản phẩm đã được thêm vào giỏ hàng!
  </div>

  <!-- jQuery & Slick Slider JS -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.js"></script>
  <script>
    $(document).ready(function() {
      // Xử lý nút thêm vào giỏ
      $(".add-to-cart").click(function() {
        if ($(this).is(':disabled')) return;
        var btn = $(this);
        var item_id = btn.data("id");
        $.ajax({
          url: "cart.php",
          type: "GET",
          data: {
            add_to_cart: item_id
          },
          success: function(response) {
            $("#toast").fadeIn(400).delay(1500).fadeOut(400);
            if (!btn.hasClass("added")) {
              btn.addClass("added");
              var badge = $(".cart-box .cart-count");
              if (badge.length === 0) {
                $(".cart-box .cart-link").append('<span class="cart-count">1</span>');
              } else {
                var currentCount = parseInt(badge.text()) || 0;
                badge.text(currentCount + 1);
              }
            }
          },
          error: function() {
            alert("Có lỗi xảy ra, vui lòng thử lại!");
          }
        });
      });

      // Khởi tạo Slick Slider cho carousel sản phẩm
      $('.carousel-container').slick({
        slidesToShow: 4,
        slidesToScroll: 4,
        infinite: true,
        autoplay: true,
        autoplaySpeed: 2000,
        arrows: true,
        prevArrow: '<button type="button" class="slick-prev"></button>',
        nextArrow: '<button type="button" class="slick-next"></button>',
        responsive: [{
            breakpoint: 1024,
            settings: {
              slidesToShow: 3,
              slidesToScroll: 3
            }
          },
          {
            breakpoint: 768,
            settings: {
              slidesToShow: 2,
              slidesToScroll: 2
            }
          },
          {
            breakpoint: 480,
            settings: {
              slidesToShow: 1,
              slidesToScroll: 1
            }
          }
        ]
      });

      // Slideshow code
      let slideIndex = 1;
      showSlides(slideIndex);
      window.plusSlides = function(n) {
        showSlides(slideIndex += n);
      }
      window.currentSlide = function(n) {
        showSlides(slideIndex = n);
      }

      function showSlides(n) {
        let i;
        let slides = document.getElementsByClassName("mySlides");
        let dots = document.getElementsByClassName("dot");
        if (n > slides.length) {
          slideIndex = 1;
        }
        if (n < 1) {
          slideIndex = slides.length;
        }
        for (i = 0; i < slides.length; i++) {
          slides[i].style.display = "none";
        }
        for (i = 0; i < dots.length; i++) {
          dots[i].className = dots[i].className.replace(" active", "");
        }
        slides[slideIndex - 1].style.display = "block";
        dots[slideIndex - 1].className += " active";
      }
    });
  </script>

  <?php include '../templates/footer.php'; ?>
</body>

</html>