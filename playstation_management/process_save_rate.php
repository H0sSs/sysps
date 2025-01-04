<?php
// process_save_rate.php
require 'includes/config.php';
require 'includes/functions.php';

// بدء الجلسة إذا لم تكن قد بدأت بالفعل
if (session_status() == PHP_SESSION_NONE) {
    session_start();
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
    $device_id = $data['device_id'] ?? '';
    $rate = $data['rate'] ?? '';

    if (empty($device_id) || empty($rate)) {
        echo json_encode(['status' => 'error', 'message' => 'بيانات غير مكتملة.']);
        exit();
    }

    if (!is_numeric($rate) || $rate <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'المعدل غير صالح.']);
        exit();
    }

    // التأكد من أن الجهاز ينتمي إلى المستخدم الحالي
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT id FROM devices WHERE id = ? AND user_id = ?");
    $stmt->execute([$device_id, $user_id]);
    $device = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$device) {
        echo json_encode(['status' => 'error', 'message' => 'الجهاز غير موجود أو لا ينتمي إليك.']);
        exit();
    }

    // تحديث المعدل
    $stmt = $pdo->prepare("UPDATE devices SET rate = ? WHERE id = ?");
    if ($stmt->execute([$rate, $device_id])) {
        echo json_encode(['status' => 'success', 'message' => 'تم حفظ المعدل بنجاح.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'حدث خطأ أثناء حفظ المعدل.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'طلب غير صالح.']);
}
?>
