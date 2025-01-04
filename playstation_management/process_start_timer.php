<?php
// process_start_timer.php
require 'includes/config.php';
require 'includes/functions.php';

// بدء الجلسة
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

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

    // التحقق مما إذا كان التايمر قيد التشغيل بالفعل
    $stmt = $pdo->prepare("SELECT * FROM timers WHERE device_id = ? AND status = 'running'");
    $stmt->execute([$device_id]);
    $timer = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($timer) {
        echo json_encode(['status' => 'error', 'message' => 'التايمر قيد التشغيل بالفعل لهذا الجهاز.']);
        exit();
    }

    // بدء التايمر
    $start_time = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("INSERT INTO timers (device_id, start_time, status) VALUES (?, ?, 'running')");
    if ($stmt->execute([$device_id, $start_time])) {
        echo json_encode(['status' => 'success', 'message' => 'تم بدء التايمر بنجاح.', 'start_time' => $start_time]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'حدث خطأ أثناء بدء التايمر.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'طلب غير صالح.']);
}
?>
