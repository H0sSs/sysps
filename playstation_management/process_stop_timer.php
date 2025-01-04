<?php
// process_stop_timer.php
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

    // العثور على التايمر القائم (running or paused)
    $stmt = $pdo->prepare("SELECT * FROM timers WHERE device_id = ? AND status IN ('running', 'paused') ORDER BY id DESC LIMIT 1");
    $stmt->execute([$device_id]);
    $timer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$timer) {
        echo json_encode(['status' => 'error', 'message' => 'لا يوجد تايمر قيد التشغيل أو موقوف لهذا الجهاز.']);
        exit();
    }

    // إيقاف التايمر
    $end_time = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("UPDATE timers SET end_time = ?, status = 'stopped' WHERE id = ?");
    if ($stmt->execute([$end_time, $timer['id']])) {
        // حساب الوقت المستغرق والتكلفة
        $start_time = new DateTime($timer['start_time']);
        $end_time_dt = new DateTime($end_time);
        $interval = $start_time->diff($end_time_dt);
        $elapsed_seconds = ($interval->days * 24 * 60 * 60) + ($interval->h * 60 * 60) + ($interval->i * 60) + $interval->s;
        $elapsed_time = $interval->format('%H:%I:%S');
        
        // الحصول على المعدل
        $stmt = $pdo->prepare("SELECT rate FROM devices WHERE id = ?");
        $stmt->execute([$device_id]);
        $device_info = $stmt->fetch(PDO::FETCH_ASSOC);
        $rate = $device_info['rate'] ?? 10; // Default rate 10 EGP/hour
        
        // حساب التكلفة (ساعات)
        $elapsed_hours = $elapsed_seconds / 3600;
        $cost = $elapsed_hours * $rate;
        $cost = round($cost, 2);
        
        echo json_encode(['status' => 'success', 'message' => 'تم إيقاف التايمر بنجاح.', 'elapsed_time' => $elapsed_time, 'cost' => $cost, 'end_time' => $end_time]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'حدث خطأ أثناء إيقاف التايمر.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'طلب غير صالح.']);
}
?>
