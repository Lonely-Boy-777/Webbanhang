<?php
session_start();
include '../includes/db.php';

// Xử lý đăng nhập
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Truy vấn lấy thông tin user từ tbUser
    $sql = "SELECT * FROM tbUser WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        // Lưu username vào session
        $_SESSION['username'] = $username;
        
        // Lấy role từ bảng tbUserInRole
        $role_sql = "SELECT role FROM tbUserInRole WHERE username = ?";
        $stmt_role = $conn->prepare($role_sql);
        $stmt_role->bind_param("s", $username);
        $stmt_role->execute();
        $role_result = $stmt_role->get_result();
        $role_row = $role_result->fetch_assoc();
        
        // Nếu tìm thấy role, lưu vào session, ngược lại mặc định là Member
        $_SESSION['role'] = $role_row ? $role_row['role'] : 'Member';

        // Chuyển hướng tới trang quản trị nếu là Admin, ngược lại quay lại trang chủ
        if ($_SESSION['role'] === 'Admin') {
            header("Location: ../admin/admin.php");

        } else {
            header("Location: index.php");
        }
        exit;
    } else {
        $error_message = "Sai tài khoản hoặc mật khẩu!";
    }
}
include '../templates/header.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập</title>
    <!-- Bootstrap CSS và Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            max-width: 400px;
            width: 100%;
            padding: 2rem;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            border-radius: 10px;
            background: white;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header img {
            width: 80px;
            margin-bottom: 1rem;
        }
        .form-floating {
            margin-bottom: 1rem;
        }
        .btn-login {
            width: 100%;
            padding: 0.8rem;
        }
        .error-message {
            color: #dc3545;
            text-align: center;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="../assets/images/logo.png" alt="Logo">
                <h2>Đăng nhập</h2>
                <p class="text-muted">Vui lòng đăng nhập để tiếp tục</p>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="username" name="username" placeholder="Tên đăng nhập" required>
                    <label for="username"><i class="fas fa-user me-2"></i>Tên đăng nhập</label>
                </div>

                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Mật khẩu" required>
                    <label for="password"><i class="fas fa-lock me-2"></i>Mật khẩu</label>
                </div>

                <!-- Checkbox hiển thị mật khẩu -->
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="show_password">
                    <label class="form-check-label" for="show_password">
                        Hiển thị mật khẩu
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>Đăng nhập
                </button>

                <div class="text-center mt-3">
                    <p>Chưa có tài khoản?</p>
                    <a href="register.php" class="text-decoration-none">Đăng ký tài khoản mới</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- JavaScript xử lý hiển thị mật khẩu -->
    <script>
        document.getElementById('show_password').addEventListener('change', function() {
            var passwordInput = document.getElementById('password');
            if (this.checked) {
                passwordInput.type = 'text';
            } else {
                passwordInput.type = 'password';
            }
        });
    </script>
</body>
</html>

<?php include '../templates/footer.php'; ?>
