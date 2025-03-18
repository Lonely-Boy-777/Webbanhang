<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Footer - Đồng Hồ Cao Cấp</title>
    <style>
        footer {
            display: flex;
            justify-content: center;
            background-color: #f8f8f8; 
            padding: 20px 0;
        }

        footer ul {
            list-style: none;
            padding: 0;
        }

        footer .f1 ul, footer .f2 ul, footer .f3 ul, footer .f4 ul {
            color: #1a2b6d;
        }

        footer .f1 ul li:hover, footer .f2 ul li:hover, footer .f3 ul li:hover, footer .f4 ul li:hover {
            color: #d4af37; 
            cursor: pointer;
        }

        footer .f1 ul li, footer .f2 ul li, footer .f3 ul li {
            margin-bottom: 18px;
            font-weight: 400;
            font-size: 14px;
        }

        footer .f1-img {
            display: flex;
            height: 40px;
        }

        footer .f4 ul li {
            margin: 5px 0;
        }

        footer .f4 img {
            height: 30px;
            margin-right: 5px;
        }

        footer .f5 ul {
            display: flex;
            justify-content: center;
            align-items: center;
            color: #1a2b6d;
        }

        footer .f5 ul li:hover {
            color: #d4af37;
            cursor: pointer;
        }

        footer .f5 ul li {
            margin-bottom: 0;
            font-weight: 400;
            font-size: 14px;
        }

        footer h3 {
            color: #1a2b6d;
            margin-bottom: 10px;
            font-size: 16px;
            font-weight: 600;
        }

        footer h4 {
            color: #1a2b6d;
            margin-bottom: 5px;
            font-size: 14px;
            font-weight: 500;
        }

        footer .pnum {
            color: #cb1c22;
            font-size: 22px;
            font-weight: 500;
        }

        footer .f5 a img {
            width: 180px; 
            height: auto;
            border-radius: 5px;
        }

        footer .f5 ul {
            margin-top: 10px;
            margin-bottom: 0;
        }

        footer .f5 ul li img {
            width: 40px;
            height: 40px;
            margin-right: 45px; 
        }
    </style>
</head>
<body>
    <hr style="box-shadow: 0px 0px 1px 1px gray; margin-bottom: 20px;">
    <footer>
        <div style="width: 250px" class="f5">
            <a href="../Code/index.php"><img src="../assets/images/logo.png" alt="Logo Công Ty" /></a>
            <h3>Kết nối với chúng tôi</h3>
            <ul>
                <li>
                    <a href="https://m.facebook.com/"><img src="../assets/images/logo FB.png" alt="Facebook" /></a>
                </li>
                <li>
                    <a href="https://zaloweb.vn/"><img src="../assets/images/Zalo icon.png" alt="Zalo" /></a>
                </li>
                <li>
                    <a href="mailto:info@luxurywatches.vn"><img src="../assets/images/emai logo.png" alt="Email" /></a>
                </li>
            </ul>
        </div>
        <div style="width: 250px" class="f1">
            <ul>
                <li>Giới thiệu thương hiệu</li>
                <li>Chính sách bảo mật</li>
                <li>Điều khoản sử dụng</li>
                <li>Chứng nhận đồng hồ chính hãng</li>
                <li>Tra cứu thông tin bảo hành</li>
                <li>Câu hỏi thường gặp</li>
                <li class="f1-img">
                    <img src="../assets/images/ft-img1.png" alt="Chứng nhận 1" />
                    <img src="../assets/images/ft-img2.png" alt="Chứng nhận 2" />
                </li>
            </ul>
        </div>
        <div style="width: 250px" class="f2">
            <ul>
                <li>Tin tức & Sự kiện</li>
                <li>Khuyến mãi đặc biệt</li>
                <li>Hướng dẫn chọn đồng hồ</li>
                <li>Hướng dẫn mua trả góp</li>
                <li>Chính sách trả góp 0%</li>
                <li>Chính sách vận chuyển toàn cầu</li>
            </ul>
        </div>
        <div style="width: 250px" class="f3">
            <ul>
                <li>Hệ thống showroom</li>
                <li>Chính sách đổi trả cao cấp</li>
                <li>Dịch vụ bảo hành chính hãng</li>
                <li>Giới thiệu đồng hồ đã qua sử dụng</li>
            </ul>
        </div>
        <div style="width: 250px" class="f4">
            <ul>
                <h3>Tư vấn chọn đồng hồ</h3>
                <li class="pnum">0918 216 952</li>
                <h3>Hỗ trợ khách hàng</h3>
                <li class="pnum">0989 573 582</li>
                <h4>Phương thức thanh toán</h4>
                <li class="f4-img">
                    <img src="../assets/images/visa.png" alt="Visa" />
                    <img src="../assets/images/Master card.png" alt="MasterCard" />
                    <img src="../assets/images/Zalo.png" alt="ZaloPay" />
                    <img src="../assets/images/VNpay.png" alt="VNPay" />
                </li>
            </ul>
        </div>
    </footer>
</body>
</html>