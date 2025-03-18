<?php
if (session_status() == PHP_SESSION_NONE) {
  session_start();
}
$cart_count = 0;
if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
  $cart_count = count($_SESSION['cart']);
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Website bán hàng</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <script src="../assets/js/jquery.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    .main-header {
      background: #ffffff;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      padding: 5px 0;
    }

    .header-container {
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0 10px;
    }
    .logo-area {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .logo-area a {
      display: flex;
      align-items: center;
      text-decoration: none;
      animation: fadeInSlide 0.8s ease forwards;
    }

    .logo-area img {
      width: 50px;
      height: 50px;
      transition: transform 0.3s ease;
    }

    .shop-name {
      font-size: 20px;
      font-weight: bold;
      color: #333;
      transition: transform 0.3s ease, color 0.3s ease, text-shadow 0.3s ease;
    }
    .logo-area a:hover img {
      transform: scale(1.1);
    }

    .logo-area a:hover .shop-name {
      transform: scale(1.05);
      color: #007bff;
      text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
    }
    @keyframes fadeInSlide {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .main-nav ul {
      list-style: none;
      display: flex;
      gap: 20px;
      margin: 0;
      padding: 0;
      align-items: center;
    }

    .main-nav a {
      text-decoration: none;
      color: #333;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 5px;
      transition: color 0.3s;
    }

    .main-nav a:hover {
      color: #007bff;
    }

    .search-box {
      width: 500px;
    }

    .search-box form {
      display: flex;
      border: 1px solid #ddd;
      border-radius: 15px;
      overflow: hidden;
    }

    .search-box input[type="text"] {
      flex: 1;
      border: none;
      padding: 8px 15px;
      outline: none;
      background-color: #fff;
    }

    .search-box button {
      background: transparent;
      border: none;
      padding: 8px 15px;
      cursor: pointer;
      color: #000;
    }

    .header-right {
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .cart-box {
      text-align: center;
      position: relative;
    }

    .cart-box .cart-link {
      text-decoration: none;
      color: #333;
      display: flex;
      flex-direction: column;
      align-items: center;
      position: relative;
    }

    .cart-box .cart-link i {
      font-size: 24px;
    }

    .cart-box .cart-count {
      position: absolute;
      top: -5px;
      right: -5px;
      background: #d9534f;
      color: #fff;
      border-radius: 50%;
      padding: 2px 6px;
      font-size: 12px;
    }

    .cart-box .cart-text {
      font-size: 14px;
      margin-top: 2px;
    }

    .account-box {
      position: relative;
      text-align: center;
    }

    .account-box .account-link {
      text-decoration: none;
      color: #333;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .account-box .account-link i {
      font-size: 24px;
    }

    .account-box .account-name {
      font-size: 14px;
      margin-top: 2px;
    }

    .account-dropdown {
      position: absolute;
      top: 110%;
      right: 0;
      display: none;
      background: #fff;
      border: 1px solid #ddd;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      border-radius: 5px;
      z-index: 1000;
      min-width: 150px;
    }

    .account-dropdown ul {
      list-style: none;
      margin: 0;
      padding: 5px 0;
    }

    .account-dropdown li {
      padding: 8px 15px;
    }

    .account-dropdown li a {
      text-decoration: none;
      color: #333;
      display: block;
    }

    .account-dropdown li:hover {
      background: #f5f5f5;
    }

    /* Responsive */
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
  </style>
</head>

<body>
  <header class="main-header">
    <div class="header-container">
      <!-- Logo và tên shop -->
      <div class="logo-area">
        <a href="../pages/index.php">
          <img src="../assets/images/logo.png" alt="Logo">
          <span class="shop-name">SEVENTH SENSE WATCH STORE</span>
        </a>
      </div>
      <!-- Navigation -->
      <nav class="main-nav">
        <ul>
        
        </ul>
      </nav>
      <div class="header-right">
        <div class="search-box">
          <form action="../pages/search.php" method="GET">
            <input type="text" name="q" placeholder="Tìm kiếm sản phẩm..." required>
            <button type="submit"><i class="fas fa-search"></i></button>
          </form>
        </div>
        <div class="cart-box">
          <a href="../pages/cart.php" class="cart-link">
            <i class="fas fa-shopping-cart"></i>
            <?php if ($cart_count > 0): ?>
              <span class="cart-count"><?= $cart_count ?></span>
            <?php endif; ?> 
            <div class="cart-text">Giỏ hàng</div>
          </a>
        </div>
        <div class="account-box">
          <?php if (isset($_SESSION['username'])): ?>
            <a href="../pages/account.php" class="account-link">
              <i class="fas fa-user"></i>
              <div class="account-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
            </a>
            <div class="account-dropdown">
              <ul>
                <li><a href="../pages/account.php">Tài khoản</a></li>
                <li><a href="account.php?tab=orders">Đơn hàng</a></li>
                <li><a href="../pages/logout.php">Đăng xuất</a></li>
              </ul>
            </div>
          <?php else: ?>
            <a href="../pages/login.php" class="account-link">
              <i class="fas fa-user"></i>
              <div class="account-name">Đăng nhập</div>
            </a>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </header>
  <hr style="box-shadow: 0px 0px 1px 1px gray; margin-bottom: 20px;">
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      const accountBox = document.querySelector('.account-box');
      const accountDropdown = document.querySelector('.account-dropdown');
      let hideTimeout;

      // Hiển thị dropdown khi di chuột vào accountBox
      accountBox.addEventListener('mouseenter', function() {
        clearTimeout(hideTimeout);
        accountDropdown.style.display = 'block';
      });

      // Ẩn dropdown khi di chuột ra khỏi accountBox
      accountBox.addEventListener('mouseleave', function() {
        hideTimeout = setTimeout(function() {
          accountDropdown.style.display = 'none';
        }, 300);
      });

      // Nếu chuột di vào dropdown thì hủy bỏ việc ẩn
      accountDropdown.addEventListener('mouseenter', function() {
        clearTimeout(hideTimeout);
      });

      // Khi chuột rời khỏi dropdown cũng thực hiện ẩn với delay
      accountDropdown.addEventListener('mouseleave', function() {
        hideTimeout = setTimeout(function() {
          accountDropdown.style.display = 'none';
        }, 300);
      });
    });
  </script>
</body>

</html>