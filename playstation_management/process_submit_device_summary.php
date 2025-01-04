<?php
// process_submit_device_summary.php
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

    // جلب آخر التايمر الموقف
    $stmt = $pdo->prepare("SELECT * FROM timers WHERE device_id = ? AND status = 'stopped' ORDER BY id DESC LIMIT 1");
    $stmt->execute([$device_id]);
    $timer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$timer) {
        echo json_encode(['status' => 'error', 'message' => 'لا يوجد تايمر موقوف لهذا الجهاز.']);
        exit();
    }

    // جلب تكلفة التايمر
    $cost = $timer['cost'] ?? 0;

    // جلب تكلفة المشروبات
    $stmt = $pdo->prepare("SELECT drink_cost FROM devices WHERE id = ?");
    $stmt->execute([$device_id]);
    $device_info = $stmt->fetch(PDO::FETCH_ASSOC);
    $drink_cost = $device_info['drink_cost'] ?? 0;

    // حساب التكلفة الإجمالية
    $total_cost = $cost + $drink_cost;

    // جلب قيمة الخصم إذا كان موجودًا
    $stmt = $pdo->prepare("SELECT discount_cost FROM devices WHERE id = ?");
    $stmt->execute([$device_id]);
    $device_discount = $stmt->fetch(PDO::FETCH_ASSOC);
    $discount_cost = $device_discount['discount_cost'] ?? 0;

    // حساب التكلفة النهائية بعد الخصم
    $final_cost = $total_cost - $discount_cost;

    // إنشاء الملخص
    $stmt = $pdo->prepare("INSERT INTO summaries (device_id, date, start_time, end_time, elapsed_time, cost) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$device_id, date('Y-m-d'), $timer['start_time'], $timer['end_time'], $timer['elapsed_time'], $final_cost])) {
        // إعادة تعيين قيم drink_cost و discount_cost
        $stmt = $pdo->prepare("UPDATE devices SET drink_cost = 0, discount_cost = 0 WHERE id = ?");
        $stmt->execute([$device_id]);
        echo json_encode(['status' => 'success', 'message' => 'تم تقديم الملخص بنجاح.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'حدث خطأ أثناء تقديم الملخص.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'طلب غير صالح.']);
}
?>
