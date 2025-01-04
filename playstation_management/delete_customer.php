<?php
// delete_customer.php
require 'includes/config.php';
require 'includes/functions.php';

// التحقق من تسجيل الدخول
check_login();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_id = intval($_POST['customer_id']);

    // حذف الزبون
    if (deleteCustomer($pdo, $customer_id)) {
        echo json_encode(['status' => 'success', 'message' => 'Customer deleted successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete customer.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
}
?>
