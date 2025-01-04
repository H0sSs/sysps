<?php
// process_save_drink_quantities.php
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
    $drinks = $data['drinks'] ?? [];

    if (empty($device_id) || !is_array($drinks)) {
        echo json_encode(['status' => 'error', 'message' => 'بيانات غير مكتملة.']);
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

    // تحديث أو إضافة كميات المشروبات
    foreach ($drinks as $drink) {
        $drink_id = $drink['drink_id'];
        $quantity = $drink['quantity'];

        if (!is_numeric($quantity) || $quantity < 0) {
            continue; // تخطي الكميات غير الصالحة
        }

        // التحقق مما إذا كان المشروب مرتبطًا بهذا الجهاز
        $stmt = $pdo->prepare("SELECT id FROM device_drinks WHERE device_id = ? AND drink_id = ?");
        $stmt->execute([$device_id, $drink_id]);
        $device_drink = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($device_drink) {
            if ($quantity > 0) {
                // تحديث الكمية
                $stmt = $pdo->prepare("UPDATE device_drinks SET quantity = ? WHERE id = ?");
                $stmt->execute([$quantity, $device_drink['id']]);
            } else {
                // حذف السطر إذا كانت الكمية صفر
                $stmt = $pdo->prepare("DELETE FROM device_drinks WHERE id = ?");
                $stmt->execute([$device_drink['id']]);
            }
        } else {
            if ($quantity > 0) {
                // إضافة السطر
                $stmt = $pdo->prepare("INSERT INTO device_drinks (device_id, drink_id, quantity) VALUES (?, ?, ?)");
                $stmt->execute([$device_id, $drink_id, $quantity]);
            }
        }
    }

    echo json_encode(['status' => 'success', 'message' => 'تم حفظ كميات المشروبات بنجاح.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'طلب غير صالح.']);
}
?>
