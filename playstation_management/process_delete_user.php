<?php
// process_delete_user.php
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
    $user_id = $data['user_id'] ?? '';

    if (empty($user_id)) {
        echo json_encode(['status' => 'error', 'message' => 'معرف المستخدم غير صالح.']);
        exit();
    }

    // التأكد من أن المستخدم موجود ولديه دور مناسب
    $stmt = $pdo->prepare("SELECT id, role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'المستخدم غير موجود.']);
        exit();
    }

    if ($user['role'] === 'admin') {
        echo json_encode(['status' => 'error', 'message' => 'لا يمكنك حذف مستخدم مسؤول.']);
        exit();
    }

    // حذف جميع الأجهزة والملخصات والتايمرات للمستخدم
    $pdo->beginTransaction();
    try {
        // جلب أجهزة المستخدم
        $stmt = $pdo->prepare("SELECT id FROM devices WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($devices as $device) {
            $device_id = $device['id'];
            // حذف الملخصات
            $stmt_summary = $pdo->prepare("DELETE FROM summaries WHERE device_id = ?");
            $stmt_summary->execute([$device_id]);

            // حذف التايمرات
            $stmt_timer = $pdo->prepare("DELETE FROM timers WHERE device_id = ?");
            $stmt_timer->execute([$device_id]);

            // حذف المشروبات المرتبطة بالجهاز
            $stmt_device_drinks = $pdo->prepare("DELETE FROM device_drinks WHERE device_id = ?");
            $stmt_device_drinks->execute([$device_id]);

            // حذف الجهاز
            $stmt_device = $pdo->prepare("DELETE FROM devices WHERE id = ?");
            $stmt_device->execute([$device_id]);
        }

        // حذف المستخدم
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'تم حذف المستخدم بنجاح.']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'حدث خطأ أثناء حذف المستخدم.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'طلب غير صالح.']);
}
?>
