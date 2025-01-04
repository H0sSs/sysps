<?php
// update_drink_sala.php
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
    echo json_encode(['status' => 'error', 'message' => "ليس لديك صلاحية لتعديل المشروبات."]);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // جلب بيانات POST
    $drink_id = intval($_POST['drink_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $csrf_token = $_POST['csrf_token'] ?? '';

    // التحقق من صحة رمز CSRF
    if (!validateCsrfToken($csrf_token)) {
        echo json_encode(['status' => 'error', 'message' => "رمز CSRF غير صالح."]);
        exit();
    }

    // التحقق من صحة البيانات
    if ($drink_id <= 0 || empty($name) || $price <= 0) {
        echo json_encode(['status' => 'error', 'message' => "الرجاء إدخال معرف مشروب صالح، اسم، وسعر."]);
        exit();
    }

    // تحديث المشروب باستخدام الدالة في functions.php
    $result = updateDrink($pdo, $drink_id, $name, $price);

    if ($result['status'] === 'success') {
        echo json_encode(['status' => 'success', 'message' => 'تم تحديث المشروب بنجاح.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $result['message']]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'طلب غير صالح.']);
}
?>
