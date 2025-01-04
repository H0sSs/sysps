<?php
// index.php
require 'includes/config.php';
require 'includes/functions.php';

// بدء الجلسة إذا لم تكن قد بدأت بالفعل
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// التحقق مما إذا كان المستخدم مسجلاً بالفعل
if (isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit();
}

$message = '';

// توليد رمز CSRF جديد
$csrf_token = generateCsrfToken();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['Email']);
    $password = trim($_POST['Password']);
    $submitted_csrf_token = $_POST['csrf_token'] ?? '';

    // التحقق من صحة رمز CSRF
    if (!validateCsrfToken($submitted_csrf_token)) {
        $message = "<div class='alert alert-danger'>رمز CSRF غير صالح.</div>";
    } else {
        // التحقق من صحة البيانات المدخلة
        if (empty($email) || empty($password)) {
            $message = "<div class='alert alert-danger'>الرجاء ملء جميع الحقول.</div>";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "<div class='alert alert-danger'>الرجاء إدخال بريد إلكتروني صالح.</div>";
        } else {
            // استعلام للتحقق من المستخدم
            $stmt = $pdo->prepare("SELECT id, password, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // تسجيل الدخول بنجاح
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                session_regenerate_id(true); // لمنع هجمات جلسة التزييف
                header('Location: home.php');
                exit();
            } else {
                $message = "<div class='alert alert-danger'>البريد الإلكتروني أو كلمة المرور غير صحيحة.</div>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول</title>
    <meta name="description" content="صفحة تسجيل الدخول لنظام إدارة بلايستيشن">
    <link rel="shortcut icon" href="assets/images/controller.png" type="image/x-icon">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/all.min.css">
    <link rel="stylesheet" href="css/login.css">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #343a40;
        }
        .login {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }
        .shape img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 1;
            opacity: 0.2;
        }
        .login-text {
            background-color: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            z-index: 2;
            position: relative;
        }
        .btn-submit {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
        }
        .btn-submit:hover {
            background-color: #0056b3;
        }
        .logo {
            width: 150px;
            margin-bottom: 20px;
        }
        footer {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <section class="login py-4 py-lg-0 d-flex justify-content-center align-items-center flex-column position-relative">
        <div class="shape">
            <img src="assets/images/shape.png" alt="Background Shape">
        </div>
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <div class="login-text position-relative z-2">
                        <img src="assets/images/logojpg.png" alt="Logo" class="logo">
                        <h1>تسجيل الدخول</h1>
                        <p class="text-muted"><span class="text-dark">ملاحظة:</span> أدخل بريدك الإلكتروني وكلمة المرور</p>
                        <?php if ($message): ?>
                            <?php echo $message; ?>
                        <?php endif; ?>
                        <form method="POST" action="">
                            <!-- تضمين رمز CSRF في النموذج -->
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                            <div class="input-group mb-3">
                                <input type="email" class="form-control" id="Email" name="Email" placeholder="Example@example.com" autocomplete="on" required>
                            </div>
                            <div class="input-group mb-3">
                                <input type="password" class="form-control" id="Password" name="Password" placeholder="كلمة المرور" required>
                            </div>
                            <button class="btn-submit" type="submit">دخول</button>
                        </form>
                        <p class="mt-3">ليس لديك حساب؟ <a href="register.php">سجل الآن</a></p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div>
                        <img src="assets/images/controller.png" class="w-100 position-relative z-2" alt="Controller Image">
                    </div>
                </div>
            </div>
            <footer class="d-flex justify-content-center gap-3 align-items-center position-relative z-2">
                <img src="assets/images/ps4.png" alt="PS4 Logo">
                <span class="text-muted m-0"> <i class="fa-regular fa-copyright"></i> جميع الحقوق محفوظة 2025</span>
            </footer>
        </div>
    </section>
    <!-- إضافة Bootstrap JS و Popper.js لتحسين التفاعلية -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
