<?php
// process_register.php
require 'includes/config.php';
require 'includes/functions.php';

// بدء الجلسة إذا لم تكن قد بدأت بالفعل
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // جلب بيانات POST
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    // التحقق من صحة رمز CSRF
    if (!validateCsrfToken($csrf_token)) {
        echo json_encode(['status' => 'error', 'message' => "رمز CSRF غير صالح."]);
        exit();
    }

    // التحقق من صحة البيانات المدخلة
    if (empty($email) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => "الرجاء إدخال البريد الإلكتروني وكلمة المرور."]);
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => "البريد الإلكتروني غير صالح."]);
        exit();
    }

    // التحقق مما إذا كان البريد الإلكتروني موجودًا بالفعل
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => "البريد الإلكتروني موجود بالفعل."]);
        exit();
    }

    // تشفير كلمة المرور
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // إضافة المستخدم كـ 'user' فقط
    $stmt = $pdo->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, 'user')");
    if ($stmt->execute([$email, $hashed_password])) {
        echo json_encode(['status' => 'success', 'message' => "تم التسجيل بنجاح."]);
    } else {
        echo json_encode(['status' => 'error', 'message' => "فشل في التسجيل. يرجى المحاولة مرة أخرى."]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => "طلب غير صالح."]);
}
?>