<?php
// apply_discount.php
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
    $discount = floatval($_POST['discount'] ?? 0);
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

    // التحقق من صحة قيمة الخصم
    if ($discount <= 0) {
        echo json_encode(['status' => 'error', 'message' => "قيمة الخصم غير صالحة."]);
        exit();
    }

    // تطبيق الخصم باستخدام الدالة في functions.php
    $result = applyDiscount($pdo, $device_id, $discount);

    echo json_encode($result);
} else {
    echo json_encode(['status' => 'error', 'message' => 'طلب غير صالح.']);
}
?>
