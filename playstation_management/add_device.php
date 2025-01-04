<?php
// add_device.php
require 'includes/config.php';
require 'includes/functions.php';

// بدء الجلسة إذا لم تكن قد بدأت بالفعل
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// التحقق من تسجيل الدخول
check_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // جلب بيانات POST
    $room_number = intval($_POST['room_number'] ?? 0);
    $csrf_token = $_POST['csrf_token'] ?? '';
    $user_id = $_SESSION['user_id'];

    // التحقق من صحة رمز CSRF
    if (!validateCsrfToken($csrf_token)) {
        echo json_encode(['status' => 'error', 'message' => "رمز CSRF غير صالح."]);
        exit();
    }

    // التحقق من صحة رقم الغرفة
    if ($room_number <= 0) {
        echo json_encode(['status' => 'error', 'message' => "الرجاء إدخال رقم غرفة صالح."]);
        exit();
    }

    // إضافة الجهاز باستخدام الدالة في functions.php
    $result = addDevice($pdo, $room_number, $user_id);

    if ($result['status'] === 'success') {
        echo json_encode(['status' => 'success', 'message' => 'تم إضافة الجهاز بنجاح.']);
    } else {
        // إذا كانت الرسالة تحتوي على HTML، يجب التأكد من معالجتها بشكل صحيح في JavaScript
        echo json_encode(['status' => 'error', 'message' => $result['message']]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'طلب غير صالح.']);
}
?>
