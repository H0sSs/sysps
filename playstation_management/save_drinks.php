<?php
// save_drinks.php
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
    // جلب بيانات POST كـ JSON
    $data = json_decode(file_get_contents('php://input'), true);
    $device_id = intval($data['device_id'] ?? 0);
    $drinks = $data['drinks'] ?? [];
    $csrf_token = $data['csrf_token'] ?? '';

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

    // حفظ كميات المشروبات باستخدام الدالة في functions.php
    $result = saveDrinkQuantities($pdo, $device_id, $drinks);

    echo json_encode($result);
} else {
    echo json_encode(['status' => 'error', 'message' => 'طلب غير صالح.']);
}
?>
