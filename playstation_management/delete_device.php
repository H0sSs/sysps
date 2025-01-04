<?php
// delete_device.php
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
    $device_id = intval($_POST['device_id'] ?? 0);
    $csrf_token = $_POST['csrf_token'] ?? '';

    // التحقق من صحة رمز CSRF
    if (!validateCsrfToken($csrf_token)) {
        echo json_encode(['status' => 'error', 'message' => "رمز CSRF غير صالح."]);
        exit();
    }

    // التحقق من صحة معرف الجهاز
    if ($device_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => "معرف الجهاز غير صالح."]);
        exit();
    }

    // حذف الجهاز باستخدام الدالة في functions.php
    $result = deleteDevice($pdo, $device_id);

    if ($result['status'] === 'success') {
        echo json_encode(['status' => 'success', 'message' => 'تم حذف الجهاز بنجاح.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $result['message']]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'طلب غير صالح.']);
}
?>
