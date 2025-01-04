<?php
// process_resume_timer.php
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

    // العثور على التايمر الموقف
    $stmt = $pdo->prepare("SELECT * FROM timers WHERE device_id = ? AND status = 'paused' ORDER BY id DESC LIMIT 1");
    $stmt->execute([$device_id]);
    $timer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$timer) {
        echo json_encode(['status' => 'error', 'message' => 'لا يوجد تايمر موقوف لهذا الجهاز.']);
        exit();
    }

    // حساب التكلفة الجزئية حتى الإيقاف المؤقت
    $start_time = new DateTime($timer['start_time']);
    $pause_time = new DateTime($timer['pause_time']);
    $interval = $start_time->diff($pause_time);
    $elapsed_seconds = ($interval->days * 24 * 60 * 60) + ($interval->h * 60 * 60) + ($interval->i * 60) + $interval->s;
    $elapsed_hours = $elapsed_seconds / 3600;

    // الحصول على المعدل
    $stmt = $pdo->prepare("SELECT rate FROM devices WHERE id = ?");
    $stmt->execute([$device_id]);
    $device_info = $stmt->fetch(PDO::FETCH_ASSOC);
    $rate = $device_info['rate'] ?? 10; // Default rate 10 EGP/hour

    // حساب التكلفة الجزئية
    $partial_cost = $elapsed_hours * $rate;
    $partial_cost = round($partial_cost, 2);

    // إعادة بدء التايمر
    $resume_time = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("UPDATE timers SET start_time = ?, pause_time = NULL, status = 'running' WHERE id = ?");
    if ($stmt->execute([$resume_time, $timer['id']])) {
        echo json_encode(['status' => 'success', 'message' => 'تم استئناف التايمر بنجاح.', 'start_time' => $resume_time, 'partial_cost' => $partial_cost]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'حدث خطأ أثناء استئناف التايمر.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'طلب غير صالح.']);
}
?>
