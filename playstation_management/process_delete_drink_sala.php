<?php
// process_delete_drink_sala.php
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
    // الحصول على رمز CSRF من رؤوس الطلب
    $headers = getallheaders();
    $csrf_token = $headers['CSRF-Token'] ?? '';

    // التحقق من رمز CSRF
    if (!validateCsrfToken($csrf_token)) {
        echo json_encode(['status' => 'error', 'message' => 'رمز CSRF غير صالح.']);
        exit();
    }

    // الحصول على البيانات المرسلة
    $data = json_decode(file_get_contents('php://input'), true);
    $drink_id = $data['drink_id'] ?? '';

    if (empty($drink_id)) {
        echo json_encode(['status' => 'error', 'message' => 'معرف المشروب غير صالح.']);
        exit();
    }

    // التحقق من أن المشروب موجود
    $stmt = $pdo->prepare("SELECT id FROM drinks WHERE id = ?");
    $stmt->execute([$drink_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'المشروب غير موجود.']);
        exit();
    }

    // حذف المشروب
    $stmt = $pdo->prepare("DELETE FROM drinks WHERE id = ?");
    if ($stmt->execute([$drink_id])) {
        echo json_encode(['status' => 'success', 'message' => 'تم حذف المشروب بنجاح.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'حدث خطأ أثناء حذف المشروب.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'طلب غير صالح.']);
}
?>
