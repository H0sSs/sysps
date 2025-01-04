<?php
// get_customers.php
require 'includes/config.php';
require 'includes/functions.php';

// التحقق من تسجيل الدخول
check_login();

// جلب بيانات المستخدم
$user_id = $_SESSION['user_id'];

// جلب جميع الزبائن المرتبطين بالمستخدم
$customers = getCustomers($pdo, $user_id);

if ($customers !== false) {
    echo json_encode(['status' => 'success', 'customers' => $customers]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to fetch customers.']);
}
?>