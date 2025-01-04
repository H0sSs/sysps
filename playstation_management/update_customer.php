<?php
// update_customer.php
require 'includes/config.php';
require 'includes/functions.php';

// التحقق من تسجيل الدخول
check_login();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_id = intval($_POST['customer_id']);
    $name = trim($_POST['name']);
    $drink = trim($_POST['drink']);
    $price = floatval($_POST['price']);

    if (empty($name) || empty($drink) || $price <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required and price must be positive.']);
        exit();
    }

    // تحديث الزبون
    if (updateCustomer($pdo, $customer_id, $name, $drink, $price)) {
        echo json_encode(['status' => 'success', 'message' => 'Customer updated successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update customer.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
}
?>
