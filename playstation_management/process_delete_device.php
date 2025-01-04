<?php
// process_delete_device.php
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

    if (empty($device_id)) {
        echo json_encode(['status' => 'error', 'message' => 'معرف الجهاز غير صالح.']);
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

    // حذف الجهاز وجميع الملخصات والتايمرات المرتبطة به
    $pdo->beginTransaction();
    try {
        // حذف الملخصات
        $stmt = $pdo->prepare("DELETE FROM summaries WHERE device_id = ?");
        $stmt->execute([$device_id]);

        // حذف التايمرات
        $stmt = $pdo->prepare("DELETE FROM timers WHERE device_id = ?");
        $stmt->execute([$device_id]);

        // حذف المشروبات المرتبطة بالجهاز
        $stmt = $pdo->prepare("DELETE FROM device_drinks WHERE device_id = ?");
        $stmt->execute([$device_id]);

        // حذف الجهاز
        $stmt = $pdo->prepare("DELETE FROM devices WHERE id = ?");
        $stmt->execute([$device_id]);

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'تم حذف الجهاز بنجاح.']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'حدث خطأ أثناء حذف الجهاز.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'طلب غير صالح.']);
}
?>
