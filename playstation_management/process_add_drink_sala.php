<?php
// process_add_drink_sala.php
require 'includes/config.php';
require 'includes/functions.php';

// بدء الجلسة إذا لم تكن قد بدأت بالفعل
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// التحقق من أن المستخدم هو مسؤول
if ($_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'ليس لديك صلاحية لإجراء هذا الإجراء.']);
    exit();
}

header('Content-Type: application/json');

// التحقق من طريقة الطلب
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // جلب رمز CSRF من النموذج
    $csrf_token = $_POST['csrf_token'] ?? '';

    // التحقق من رمز CSRF
    if (!validateCsrfToken($csrf_token)) {
        echo json_encode(['status' => 'error', 'message' => 'رمز CSRF غير صالح.']);
        exit();
    }

    // جلب بيانات POST
    $name = trim($_POST['drinkNameSala'] ?? '');
    $price = trim($_POST['drinkPriceSala'] ?? '');

    // التحقق من صحة البيانات المدخلة
    if (empty($name) || empty($price)) {
        echo json_encode(['status' => 'error', 'message' => 'يرجى إدخال اسم المشروب وسعره.']);
        exit();
    }

    if (!is_numeric($price) || $price < 0) {
        echo json_encode(['status' => 'error', 'message' => 'السعر غير صالح.']);
        exit();
    }

    // التحقق مما إذا كان المشروب موجود بالفعل
    $stmt = $pdo->prepare("SELECT id FROM drinks WHERE name = ?");
    $stmt->execute([$name]);
    if ($stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'المشروب موجود بالفعل.']);
        exit();
    }

    // إضافة المشروب الجديد
    $stmt = $pdo->prepare("INSERT INTO drinks (name, price) VALUES (?, ?)");
    if ($stmt->execute([$name, $price])) {
        echo json_encode(['status' => 'success', 'message' => 'تم إضافة المشروب بنجاح.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'حدث خطأ أثناء إضافة المشروب.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'طلب غير صالح.']);
}
?>
