<?php
// calc_discount.php
require 'includes/config.php';
require 'includes/functions.php';

// التحقق من تسجيل الدخول
check_login();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $device_id = intval($_POST['device_id']);
    $discount = floatval($_POST['discount']);
    $user_id = $_SESSION['user_id'];

    if ($discount <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Discount must be a positive value.']);
        exit();
    }

    // التحقق مما إذا كان الجهاز ينتمي للمستخدم
    $stmt = $pdo->prepare("SELECT rate FROM devices WHERE id = ? AND user_id = ?");
    $stmt->execute([$device_id, $user_id]);
    $device = $stmt->fetch();

    if (!$device) {
        echo json_encode(['status' => 'error', 'message' => 'Device not found or unauthorized.']);
        exit();
    }

    // جلب معلومات التايمر
    $stmt = $pdo->prepare("SELECT * FROM device_timers WHERE device_id = ?");
    $stmt->execute([$device_id]);
    $timer = $stmt->fetch();

    if (!$timer || !$timer['is_running']) {
        echo json_encode(['status' => 'error', 'message' => 'Timer is not running for this device.']);
        exit();
    }

    $start_time = new DateTime($timer['start_time']);
    $current_time = new DateTime();
    $elapsed = $start_time->diff($current_time);
    $elapsed_seconds = ($elapsed->h * 3600) + ($elapsed->i * 60) + $elapsed->s;
    $time_cost = ($elapsed_seconds / 3600) * floatval($device['rate']);

    // جلب كميات المشروبات
    $stmt = $pdo->prepare("SELECT dd.quantity, d.price FROM device_drinks dd JOIN drinks d ON dd.drink_id = d.id WHERE dd.device_id = ?");
    $stmt->execute([$device_id]);
    $drink_quantities = $stmt->fetchAll();

    $menu_cost = 0;
    foreach ($drink_quantities as $dq) {
        $menu_cost += $dq['quantity'] * $dq['price'];
    }

    $total_cost = $menu_cost + $time_cost;
    $after_discount = $total_cost - $discount;

    if ($after_discount < 0) {
        echo json_encode(['status' => 'error', 'message' => 'Discount cannot exceed total cost.']);
        exit();
    }

    echo json_encode([
        'status' => 'success',
        'after_discount' => number_format($after_discount, 2),
        'discount_applied' => number_format($discount, 2)
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
}
?>
