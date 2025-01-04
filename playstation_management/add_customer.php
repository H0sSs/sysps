<?php
// add_customer.php
require 'includes/config.php';
require 'includes/functions.php';

// التحقق من تسجيل الدخول
check_login();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $drink = trim($_POST['drink']);
    $price = floatval($_POST['price']);
    $device_id = isset($_POST['device_id']) ? intval($_POST['device_id']) : null; // يمكن تحديد جهاز معين إذا لزم الأمر

    if (empty($name) || empty($drink) || $price <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required and price must be positive.']);
        exit();
    }

    // إضافة الزبون
    if (addCustomer($pdo, $name, $drink, $price, $device_id)) {
        echo json_encode(['status' => 'success', 'message' => 'Customer added successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add customer.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
}
?>
