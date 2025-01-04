<?php
// delete_user.php
require 'includes/config.php';
require 'includes/functions.php';

// بدء الجلسة إذا لم تكن قد بدأت بالفعل
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// التحقق من تسجيل الدخول
check_login();

// التحقق من أن المستخدم هو مسؤول
if ($_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => "ليس لديك صلاحية لحذف المستخدمين."]);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // جلب بيانات POST
    $user_id = intval($_POST['user_id'] ?? 0);
    $csrf_token = $_POST['csrf_token'] ?? '';

    // التحقق من صحة رمز CSRF
    if (!validateCsrfToken($csrf_token)) {
        echo json_encode(['status' => 'error', 'message' => "رمز CSRF غير صالح."]);
        exit();
    }

    // التحقق من صحة معرف المستخدم
    if ($user_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => "معرف المستخدم غير صالح."]);
        exit();
    }

    // حذف المستخدم باستخدام الدالة في functions.php
    $result = deleteUser($pdo, $user_id);

    echo json_encode($result);
} else {
    echo json_encode(['status' => 'error', 'message' => 'طلب غير صالح.']);
}
?>
