<?php
// process_add_device.php
require 'includes/config.php';
require 'includes/functions.php';

// بدء الجلسة إذا لم تكن قد بدأت بالفعل
if (session_status() == PHP_SESSION_NONE) {
    session_start();
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
    $room_number = trim($_POST['room_number'] ?? '');

    // التحقق من صحة البيانات المدخلة
    if (empty($room_number)) {
        echo json_encode(['status' => 'error', 'message' => 'يرجى إدخال رقم الغرفة.']);
        exit();
    }

    if (!is_numeric($room_number) || intval($room_number) <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'رقم الغرفة غير صالح.']);
        exit();
    }

    // التحقق مما إذا كان الجهاز موجود بالفعل
    $stmt = $pdo->prepare("SELECT id FROM devices WHERE room_number = ? AND user_id = ?");
    $stmt->execute([$room_number, $_SESSION['user_id']]);
    if ($stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'الجهاز موجود بالفعل في هذه الغرفة.']);
        exit();
    }

    // إضافة الجهاز الجديد
    $stmt = $pdo->prepare("INSERT INTO devices (user_id, room_number, rate) VALUES (?, ?, 10)"); // Default rate 10 EGP/hour
    if ($stmt->execute([$_SESSION['user_id'], $room_number])) {
        echo json_encode(['status' => 'success', 'message' => 'تم إضافة الجهاز بنجاح.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'حدث خطأ أثناء إضافة الجهاز.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'طلب غير صالح.']);
}
?>
