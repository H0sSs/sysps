<?php
// submit_device.php
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

    // تقديم ملخص الجهاز باستخدام الدالة في functions.php
    $result = submitDeviceSummary($pdo, $device_id);

    echo json_encode($result);
} else {
    echo json_encode(['status' => 'error', 'message' => 'طلب غير صالح.']);
}
?>
