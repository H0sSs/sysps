<?php
// process_calculate_menu_cost.php
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
    $stmt = $pdo->prepare("SELECT rate FROM devices WHERE id = ? AND user_id = ?");
    $stmt->execute([$device_id, $user_id]);
    $device = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$device) {
        echo json_encode(['status' => 'error', 'message' => 'الجهاز غير موجود أو لا ينتمي إليك.']);
        exit();
    }

    $rate = $device['rate'];

    // جلب كميات المشروبات المرتبطة بالجهاز
    $stmt = $pdo->prepare("SELECT d.name, d.price, dd.quantity FROM device_drinks dd JOIN drinks d ON dd.drink_id = d.id WHERE dd.device_id = ?");
    $stmt->execute([$device_id]);
    $drinks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$drinks) {
        echo json_encode(['status' => 'error', 'message' => 'لا توجد مشروبات مرتبطة بهذا الجهاز.']);
        exit();
    }

    // حساب تكلفة المشروبات
    $total_drink_cost = 0;
    foreach ($drinks as $drink) {
        $total_drink_cost += $drink['price'] * $drink['quantity'];
    }

    // تحديث تكلفة المشروبات في الجهاز
    $stmt = $pdo->prepare("UPDATE devices SET drink_cost = ? WHERE id = ?");
    if ($stmt->execute([$total_drink_cost, $device_id])) {
        echo json_encode(['status' => 'success', 'message' => 'تم حساب تكلفة المشروبات بنجاح.', 'menu_cost' => number_format($total_drink_cost, 2)]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'حدث خطأ أثناء حساب تكلفة المشروبات.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'طلب غير صالح.']);
}
?>
