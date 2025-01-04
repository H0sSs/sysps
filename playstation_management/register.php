<?php
// register.php
require 'includes/config.php';
require 'includes/functions.php';

// بدء الجلسة إذا لم تكن قد بدأت بالفعل
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// إذا كان المستخدم مسجلاً بالفعل، قم بإعادة توجيهه إلى الصفحة الرئيسية
if (isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit();
}

// توليد رمز CSRF جديد
$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل جديد</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Cairo', sans-serif;
        }
        .register-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 30px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .register-container h2 {
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>تسجيل جديد</h2>
        <form id="registerForm" method="POST" action="process_register.php">
            <div class="mb-3">
                <label for="email" class="form-label">البريد الإلكتروني</label>
                <input type="email" class="form-control" id="email" name="email" required placeholder="أدخل بريدك الإلكتروني">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">كلمة المرور</label>
                <input type="password" class="form-control" id="password" name="password" required placeholder="أدخل كلمة المرور">
            </div>
            <!-- تضمين رمز CSRF في النموذج -->
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <button type="submit" class="btn btn-primary w-100">تسجيل</button>
        </form>
        <p class="mt-3 text-center">هل لديك حساب؟ <a href="login.php">تسجيل الدخول هنا</a></p>
        <div id="registerMessage" class="mt-3"></div>
    </div>

    <!-- إضافة ملفات JavaScript -->
    <script src="js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault(); // منع الإرسال الافتراضي للنموذج

            const form = e.target;
            const formData = new FormData(form);

            fetch('process_register.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const messageDiv = document.getElementById('registerMessage');
                if (data.status === 'success') {
                    messageDiv.innerHTML = <div class="alert alert-success">${data.message}</div>;
                    form.reset();
                } else {
                    messageDiv.innerHTML = <div class="alert alert-danger">${data.message}</div>;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const messageDiv = document.getElementById('registerMessage');
                messageDiv.innerHTML = <div class="alert alert-danger">حدث خطأ أثناء التسجيل. يرجى المحاولة مرة أخرى.</div>;
            });
        });
    </script>
</body>
</html>