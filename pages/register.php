<?php
session_start();
include '../includes/db.php';
include '../templates/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Lấy mật khẩu và xác nhận mật khẩu từ form
    $password_raw = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Kiểm tra xem mật khẩu và xác nhận mật khẩu có khớp nhau không
    if ($password_raw !== $confirm_password) {
        echo "<div style='text-align:center; margin-top:20px; font-size:18px;'>Mật khẩu và xác nhận mật khẩu không khớp!</div>";
        exit;
    }

    // Kiểm tra mật khẩu theo yêu cầu: tối thiểu 8 ký tự, ít nhất 1 chữ hoa, 1 chữ thường, 1 ký tự đặc biệt
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,}$/', $password_raw)) {
        echo "<div style='text-align:center; margin-top:20px; font-size:18px;'>Mật khẩu phải có tối thiểu 8 ký tự, bao gồm ít nhất 1 chữ hoa, 1 chữ thường và 1 ký tự đặc biệt!</div>";
        exit;
    }

    // Lấy các thông tin đăng ký khác
    $username = $_POST['username'];
    $customer_name = $_POST['customer_name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $ngaysinh = $_POST['ngaysinh'];
    $gender = $_POST['gender'];
    $role = 'Member';

    // Kiểm tra username hợp lệ: ít nhất 5 ký tự, chỉ chứa chữ cái, chữ số và dấu gạch dưới
    if (!preg_match('/^[A-Za-z0-9_]{5,}$/', $username)) {
        echo "<div style='text-align:center; margin-top:20px; font-size:18px;'>Tên đăng nhập không hợp lệ! Vui lòng chỉ sử dụng chữ cái (không dấu), chữ số và dấu gạch dưới với ít nhất 5 ký tự.</div>";
        exit;
    }

    // Kiểm tra số điện thoại hợp lệ: 10 chữ số bắt đầu bằng số 0
    if (!preg_match('/^0\d{9}$/', $phone)) {
        echo "<div style='text-align:center; margin-top:20px; font-size:18px;'>Số điện thoại không hợp lệ! Vui lòng nhập số điện thoại 10 số bắt đầu bằng 0.</div>";
        exit;
    }

    // Mã hóa mật khẩu
    $password = password_hash($password_raw, PASSWORD_DEFAULT);

    // Kiểm tra username đã tồn tại chưa
    $check_sql = "SELECT * FROM tbuser WHERE username = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $check_result = $stmt->get_result();

    if ($check_result->num_rows > 0) {
        echo "<div style='text-align:center; margin-top:20px; font-size:18px;'>Tên đăng nhập đã tồn tại!</div>";
    } else {
        // Thêm tài khoản vào tbuser
        $sql = "INSERT INTO tbuser (username, password) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();

        $today = date('Ymd'); 
        $prefix = "KH" . $today;
        
        // Truy vấn CSDL để tìm mã khách hàng cuối cùng có định dạng bắt đầu bằng $prefix
        $sql = "SELECT makhach FROM tbkhachhang WHERE makhach LIKE '$prefix%' ORDER BY makhach DESC LIMIT 1";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            // Lấy 3 ký tự cuối của mã và chuyển về số nguyên
            $lastNumber = (int)substr($row['makhach'], -3);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }
        // Định dạng số thứ tự thành 3 ký tự 
        $customer_id = $prefix . sprintf("%03d", $nextNumber);

        // Thêm thông tin khách hàng vào tbkhachhang (sử dụng ngày sinh thay vì tuổi)
        $sql = "INSERT INTO tbkhachhang (makhach, tenkhach, ngaysinh, gioitinh, sodienthoai, diachi, username) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssss", $customer_id, $customer_name, $ngaysinh, $gender, $phone, $address, $username);
        $stmt->execute();

        // Thêm quyền vào tbuserinrole
        $sql = "INSERT INTO tbuserinrole (username, role) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $role);
        $stmt->execute();

        echo "<div style='text-align:center; margin-top:20px; font-size:18px;'>Đăng ký thành công! Bạn có thể <a href='login.php'>đăng nhập</a> ngay.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 50%;
            margin: 50px auto;
            background: white;
            padding: 30px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        .input-group {
            margin-bottom: 15px;
        }
        .input-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
            color: #555;
        }
        .input-group input,
        .input-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        .input-group input:focus,
        .input-group select:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        }
        .button-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
            align-items: center;
        }
        .button-container button {
            width: 100%;
            padding: 12px;
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            font-size: 16px;
            transition: background 0.3s ease-in-out;
        }
        .button-container button:hover {
            background: #0056b3;
        }
        .show-password {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 3px;
            margin-top: 8px;
            margin-bottom: 15px;
            white-space: nowrap;
        }
        .show-password input[type="checkbox"],
        .show-password label {
            margin: 0;
            padding: 0;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Đăng ký tài khoản</h2>
        <form method="post" id="registerForm">
            <div class="input-group">
                <label for="username">Tên đăng nhập:</label>
                <input type="text" id="username" name="username" placeholder="Tên đăng nhập" required>
            </div>
            <div class="input-group">
                <label for="password">Mật khẩu:</label>
                <input type="password" id="password" name="password" placeholder="Mật khẩu" required>
            </div>
            <div class="input-group">
                <label for="confirm_password">Xác nhận mật khẩu:</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Xác nhận mật khẩu" required>
            </div>
            <div class="show-password">
                <input type="checkbox" id="showPassword">
                <label for="showPassword">Hiển thị mật khẩu</label>
            </div>
            <div class="input-group">
                <label for="customer_name">Tên khách hàng:</label>
                <input type="text" id="customer_name" name="customer_name" placeholder="Tên khách hàng" required>
            </div>
            <div class="input-group">
                <label for="ngaysinh">Ngày sinh:</label>
                <input type="date" id="ngaysinh" name="ngaysinh" required>
            </div>
            <div class="input-group">
                <label for="gender">Giới tính:</label>
                <select id="gender" name="gender" required>
                    <option value="Nam">Nam</option>
                    <option value="Nữ">Nữ</option>
                </select>
            </div>
            <div class="input-group">
                <label for="phone">Số điện thoại:</label>
                <input type="text" id="phone" name="phone" placeholder="Số điện thoại" required>
            </div>
            <div class="input-group">
                <label for="address">Địa chỉ:</label>
                <input type="text" id="address" name="address" placeholder="Địa chỉ" required>
            </div>
            <div class="button-container">
                <button type="submit">Đăng ký</button>
            </div>
        </form>
    </div>

    <script>
        // Xử lý checkbox hiển thị mật khẩu cho cả 2 trường: mật khẩu và xác nhận mật khẩu
        document.getElementById('showPassword').addEventListener('change', function() {
            var pwdInput = document.getElementById('password');
            var cpwdInput = document.getElementById('confirm_password');
            var type = this.checked ? 'text' : 'password';
            pwdInput.type = type;
            cpwdInput.type = type;
        });

        // Kiểm tra các điều kiện hợp lệ phía client trước khi submit form
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            var username = document.getElementById('username').value.trim();
            var phone = document.getElementById('phone').value.trim();
            var password = document.getElementById('password').value;
            var confirmPassword = document.getElementById('confirm_password').value;

            // Kiểm tra username: chỉ chứa chữ cái (không dấu), số và dấu gạch dưới, ít nhất 5 ký tự
            var usernameRegex = /^[A-Za-z0-9_]{5,}$/;
            if (!usernameRegex.test(username)) {
                alert("Tên đăng nhập không hợp lệ! Vui lòng chỉ sử dụng chữ cái (không dấu), chữ số và dấu gạch dưới với ít nhất 5 ký tự.");
                e.preventDefault();
                return;
            }

            // Kiểm tra số điện thoại: phải là 10 chữ số, bắt đầu bằng 0
            var phoneRegex = /^0\d{9}$/;
            if (!phoneRegex.test(phone)) {
                alert("Số điện thoại không hợp lệ! Vui lòng nhập số điện thoại 10 số bắt đầu bằng 0.");
                e.preventDefault();
                return;
            }

            // Kiểm tra mật khẩu theo yêu cầu
            var passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,}$/;
            if (!passwordRegex.test(password)) {
                alert("Mật khẩu phải có tối thiểu 8 ký tự, bao gồm ít nhất 1 chữ hoa, 1 chữ thường và 1 ký tự đặc biệt!");
                e.preventDefault();
                return;
            }
            if (password !== confirmPassword) {
                alert("Mật khẩu và xác nhận mật khẩu không khớp!");
                e.preventDefault();
                return;
            }
        });
    </script>
</body>

</html>

<?php include '../templates/footer.php'; ?>
