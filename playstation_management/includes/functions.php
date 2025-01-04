<?php
// includes/functions.php

// بدء الجلسة إذا لم تكن قد بدأت بالفعل
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// توليد رمز CSRF
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// التحقق من رمز CSRF
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// التحقق من تسجيل الدخول
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php');
        exit();
    }
}

// التحقق مما إذا كان المستخدم مسؤولاً
function is_admin($pdo) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        return true;
    }
    return false;
}

// جلب جميع الأجهزة المرتبطة بالمستخدم
function getDevices($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM devices WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

// جلب عدد الأجهزة للمستخدم
function getDeviceCount($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM devices WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    return $result['count'] ?? 0;
}

// جلب ملخصات جهاز معين
function getDeviceSummaries($pdo, $device_id) {
    $stmt = $pdo->prepare("SELECT * FROM summaries WHERE device_id = ? ORDER BY id DESC");
    $stmt->execute([$device_id]);
    return $stmt->fetchAll();
}

// إضافة جهاز جديد
function addDevice($pdo, $room_number, $user_id) {
    // التحقق مما إذا كان رقم الغرفة موجوداً بالفعل
    $stmt = $pdo->prepare("SELECT id FROM devices WHERE room_number = ?");
    $stmt->execute([$room_number]);
    if ($stmt->fetch()) {
        return ['status' => 'error', 'message' => "<span class='text-danger fw-bolder'>الغرفة $room_number</span> موجودة بالفعل <i class='fa-solid fa-triangle-exclamation'></i>"];
    }

    // إضافة الجهاز
    $stmt = $pdo->prepare("INSERT INTO devices (room_number, user_id, is_running, rate) VALUES (?, ?, 0, 10)");
    if ($stmt->execute([$room_number, $user_id])) {
        return ['status' => 'success', 'message' => "تم إضافة الجهاز في الغرفة $room_number بنجاح."];
    } else {
        return ['status' => 'error', 'message' => "فشل في إضافة الجهاز."];
    }
}

// حذف جهاز
function deleteDevice($pdo, $device_id) {
    // حذف الجهاز
    $stmt = $pdo->prepare("DELETE FROM devices WHERE id = ?");
    if ($stmt->execute([$device_id])) {
        // حذف الملخصات المرتبطة
        $stmt = $pdo->prepare("DELETE FROM summaries WHERE device_id = ?");
        $stmt->execute([$device_id]);
        return ['status' => 'success', 'message' => "تم حذف الجهاز بنجاح."];
    } else {
        return ['status' => 'error', 'message' => "فشل في حذف الجهاز."];
    }
}

// إعادة تعيين جهاز (إعادة تعيين التايمر)
function resetDevice($pdo, $device_id) {
    // تحديث حالة التايمر وإعادة تعيين الوقت
    $stmt = $pdo->prepare("UPDATE devices SET is_running = 0, rate = 10, elapsed_time = '00:00:00' WHERE id = ?");
    if ($stmt->execute([$device_id])) {
        return ['status' => 'success', 'message' => "تم إعادة تعيين الجهاز بنجاح."];
    } else {
        return ['status' => 'error', 'message' => "فشل في إعادة تعيين الجهاز."];
    }
}

// بدء التايمر
function startTimer($pdo, $device_id) {
    // تحقق مما إذا كان التايمر غير نشط
    $stmt = $pdo->prepare("SELECT is_running FROM devices WHERE id = ?");
    $stmt->execute([$device_id]);
    $device = $stmt->fetch();
    if ($device && !$device['is_running']) {
        $stmt = $pdo->prepare("UPDATE devices SET is_running = 1, start_time = NOW() WHERE id = ?");
        if ($stmt->execute([$device_id])) {
            return ['status' => 'success', 'message' => "تم بدء التايمر."];
        }
    }
    return ['status' => 'error', 'message' => "لا يمكن بدء التايمر."];
}
// إيقاف التايمر
function stopTimer($pdo, $device_id) {
    // تحقق مما إذا كان التايمر نشط
    $stmt = $pdo->prepare("SELECT is_running, start_time, pause_time, elapsed_time FROM devices WHERE id = ?");
    $stmt->execute([$device_id]);
    $device = $stmt->fetch();
    if ($device && $device['is_running']) {
        // حساب الوقت المستغرق
        $start_time = new DateTime($device['start_time']);
        $end_time = new DateTime();
        $interval = $start_time->diff($end_time);
        $elapsed = $interval->format('%H:%I:%S');

        // حساب التكلفة بناءً على المعدل
        $rate = $device['rate'];
        list($hours, $minutes, $seconds) = explode(':', $elapsed);
        $total_seconds = ($hours * 3600) + ($minutes * 60) + $seconds;
        $total_hours = $total_seconds / 3600;
        $cost = $rate * $total_hours;

        // تحديث حالة التايمر
        $stmt = $pdo->prepare("UPDATE devices SET is_running = 0 WHERE id = ?");
        if ($stmt->execute([$device_id])) {
            // إضافة ملخص
            $stmt = $pdo->prepare("INSERT INTO summaries (device_id, date, start_time, end_time, elapsed_time, cost) VALUES (?, CURDATE(), ?, NOW(), ?, ?)");
            if ($stmt->execute([$device_id, $device['start_time'], $elapsed, $cost])) {
                return ['status' => 'success', 'message' => "تم إيقاف التايمر وحساب التكلفة."];
            }
        }
    }
    return ['status' => 'error', 'message' => "لا يمكن إيقاف التايمر."];
}

// إيقاف مؤقت التايمر
function pauseTimer($pdo, $device_id) {
    // تحقق مما إذا كان التايمر نشط
    $stmt = $pdo->prepare("SELECT is_running, pause_time, elapsed_time FROM devices WHERE id = ?");
    $stmt->execute([$device_id]);
    $device = $stmt->fetch();
    if ($device && $device['is_running']) {
        // حساب الوقت المستغرق حتى الإيقاف المؤقت
        $start_time = new DateTime($device['start_time']);
        $pause_time = new DateTime();
        $interval = $start_time->diff($pause_time);
        $elapsed = $interval->format('%H:%I:%S');

        // تحديث وقت الإيقاف المؤقت وزيادة الوقت المستغرق
        $stmt = $pdo->prepare("UPDATE devices SET is_running = 0, elapsed_time = ? WHERE id = ?");
        if ($stmt->execute([$elapsed, $device_id])) {
            return ['status' => 'success', 'message' => "تم إيقاف التايمر مؤقتًا."];
        }
    }
    return ['status' => 'error', 'message' => "لا يمكن إيقاف التايمر مؤقتًا."];
}

// استئناف التايمر
function resumeTimer($pdo, $device_id) {
    // تحقق مما إذا كان التايمر غير نشط
    $stmt = $pdo->prepare("SELECT is_running, elapsed_time FROM devices WHERE id = ?");
    $stmt->execute([$device_id]);
    $device = $stmt->fetch();
    if ($device && !$device['is_running']) {
        // تحديث حالة التايمر واستئناف الوقت
        $stmt = $pdo->prepare("UPDATE devices SET is_running = 1, start_time = NOW() WHERE id = ?");
        if ($stmt->execute([$device_id])) {
            return ['status' => 'success', 'message' => "تم استئناف التايمر."];
        }
    }
    return ['status' => 'error', 'message' => "لا يمكن استئناف التايمر."];
}

// حفظ اختيار المعدل
function saveRateSelection($pdo, $device_id, $rate) {
    $allowed_rates = [10, 15, 20, 25, 30, 35, 40];
    if (!in_array($rate, $allowed_rates)) {
        return ['status' => 'error', 'message' => "المعدل غير صالح."];
    }

    $stmt = $pdo->prepare("UPDATE devices SET rate = ? WHERE id = ?");
    if ($stmt->execute([$rate, $device_id])) {
        return ['status' => 'success', 'message' => "تم تحديث المعدل بنجاح."];
    } else {
        return ['status' => 'error', 'message' => "فشل في تحديث المعدل."];
    }
}
// حفظ كميات المشروبات
function saveDrinkQuantities($pdo, $device_id, $drink_quantities) {
    foreach ($drink_quantities as $drink_id => $quantity) {
        if ($quantity > 0) {
            // تحقق مما إذا كانت هناك كمية مسبقة
            $stmt = $pdo->prepare("SELECT id FROM device_drinks WHERE device_id = ? AND drink_id = ?");
            $stmt->execute([$device_id, $drink_id]);
            if ($stmt->fetch()) {
                // تحديث الكمية
                $stmt = $pdo->prepare("UPDATE device_drinks SET quantity = ? WHERE device_id = ? AND drink_id = ?");
                $stmt->execute([$quantity, $device_id, $drink_id]);
            } else {
                // إضافة كمية جديدة
                $stmt = $pdo->prepare("INSERT INTO device_drinks (device_id, drink_id, quantity) VALUES (?, ?, ?)");
                $stmt->execute([$device_id, $drink_id, $quantity]);
            }
        } else {
            // إذا كانت الكمية صفرية، حذف السجل
            $stmt = $pdo->prepare("DELETE FROM device_drinks WHERE device_id = ? AND drink_id = ?");
            $stmt->execute([$device_id, $drink_id]);
        }
    }
    return ['status' => 'success', 'message' => "تم حفظ كميات المشروبات بنجاح."];
}

// حساب تكلفة القائمة
function calculateMenuCost($pdo, $device_id) {
    // جلب كميات المشروبات وأسعارها
    $stmt = $pdo->prepare("
        SELECT d.price, dd.quantity
        FROM device_drinks dd
        JOIN drinks d ON dd.drink_id = d.id
        WHERE dd.device_id = ?
    ");
    $stmt->execute([$device_id]);
    $drinks = $stmt->fetchAll();

    $menu_cost = 0;
    foreach ($drinks as $drink) {
        $menu_cost += $drink['price'] * $drink['quantity'];
    }

    // جلب معدل الجهاز لحساب التكلفة الإجمالية
    $stmt = $pdo->prepare("SELECT rate FROM devices WHERE id = ?");
    $stmt->execute([$device_id]);
    $device = $stmt->fetch();
    $rate = $device['rate'] ?? 10;

    // جلب الوقت المستغرق
    $stmt = $pdo->prepare("SELECT elapsed_time FROM devices WHERE id = ?");
    $stmt->execute([$device_id]);
    $elapsed_time_str = $stmt->fetch()['elapsed_time'] ?? '00:00:00';
    list($hours, $minutes, $seconds) = explode(':', $elapsed_time_str);
    $total_seconds = ($hours * 3600) + ($minutes * 60) + $seconds;
    $total_hours = $total_seconds / 3600;

    $total_cost = $rate * $total_hours + $menu_cost;

    return ['status' => 'success', 'menu_cost' => number_format($menu_cost, 2), 'total_cost' => number_format($total_cost, 2)];
}

// تطبيق الخصم
function applyDiscount($pdo, $device_id, $discount) {
    // جلب التكلفة الحالية
    $stmt = $pdo->prepare("SELECT cost FROM summaries WHERE device_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$device_id]);
    $summary = $stmt->fetch();
    if (!$summary) {
        return ['status' => 'error', 'message' => "لا توجد تكلفة لحساب الخصم."];
    }

    $current_cost = $summary['cost'];
    if ($discount > $current_cost) {
        return ['status' => 'error', 'message' => "قيمة الخصم لا يمكن أن تتجاوز التكلفة الحالية."];
    }

    $new_cost = $current_cost - $discount;

    // تحديث التكلفة في الملخص الأخير
    $stmt = $pdo->prepare("UPDATE summaries SET cost = ? WHERE device_id = ? ORDER BY id DESC LIMIT 1");
    if ($stmt->execute([$new_cost, $device_id])) {
        return ['status' => 'success', 'discount_applied' => number_format($discount, 2), 'after_discount' => number_format($new_cost, 2)];
    } else {
        return ['status' => 'error', 'message' => "فشل في تطبيق الخصم."];
    }
}

// تقديم ملخص الجهاز
function submitDeviceSummary($pdo, $device_id) {
    // هنا يمكن تنفيذ أي إجراءات إضافية عند تقديم الملخص، مثل إرسال بريد إلكتروني، تخزين الملخص في مكان آخر، إلخ.
    // سأفترض أنه لا يوجد شيء إضافي مطلوب حاليًا.
    return ['status' => 'success', 'message' => "تم تقديم الملخص بنجاح."];
}

// جلب المستخدمين (للوحة الإدارة)
function getUsers($pdo) {
    $stmt = $pdo->prepare("SELECT id, email, role FROM users");
    $stmt->execute();
    return $stmt->fetchAll();
}
// حذف مستخدم
function deleteUser($pdo, $user_id) {
    // منع حذف المستخدم الحالي
    if ($user_id == $_SESSION['user_id']) {
        return ['status' => 'error', 'message' => "لا يمكن حذف الحساب الحالي."];
    }

    // حذف المستخدم
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    if ($stmt->execute([$user_id])) {
        return ['status' => 'success', 'message' => "تم حذف المستخدم بنجاح."];
    } else {
        return ['status' => 'error', 'message' => "فشل في حذف المستخدم."];
    }
}

// جلب المشروبات (لصفحة home.php وعرض في النماذج)
function getDrinks($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM drinks");
    $stmt->execute();
    return $stmt->fetchAll();
}

// حذف مشروب
function deleteDrink($pdo, $drink_id) {
    $stmt = $pdo->prepare("DELETE FROM drinks WHERE id = ?");
    if ($stmt->execute([$drink_id])) {
        return ['status' => 'success', 'message' => "تم حذف المشروب بنجاح."];
    } else {
        return ['status' => 'error', 'message' => "فشل في حذف المشروب."];
    }
}

// إضافة مشروب (للوحة الإدارة)
function addDrink($pdo, $name, $price) {
    $stmt = $pdo->prepare("INSERT INTO drinks (name, price) VALUES (?, ?)");
    if ($stmt->execute([$name, $price])) {
        return ['status' => 'success', 'message' => "تم إضافة المشروب بنجاح."];
    } else {
        return ['status' => 'error', 'message' => "فشل في إضافة المشروب."];
    }
}

// تحديث مشروب (للوحة الإدارة)
function updateDrink($pdo, $drink_id, $name, $price) {
    $stmt = $pdo->prepare("UPDATE drinks SET name = ?, price = ? WHERE id = ?");
    if ($stmt->execute([$name, $price, $drink_id])) {
        return ['status' => 'success', 'message' => "تم تحديث المشروب بنجاح."];
    } else {
        return ['status' => 'error', 'message' => "فشل في تحديث المشروب."];
    }
}

// مسح جميع البيانات (للوحة الإدارة)
function clearAllData($pdo) {
    // حذف جميع الأجهزة والملخصات والعملاء والإحصائيات وغيرها
    try {
        $pdo->beginTransaction();
        $pdo->exec("DELETE FROM summaries");
        $pdo->exec("DELETE FROM device_drinks");
        $pdo->exec("DELETE FROM devices");
        $pdo->exec("DELETE FROM customers");
        $pdo->exec("DELETE FROM statistics");
        $pdo->commit();
        return ['status' => 'success', 'message' => "تم مسح جميع البيانات بنجاح."];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['status' => 'error', 'message' => "فشل في مسح البيانات."];
    }
}

// جلب الزبائن (لصفحة home.php وعرضهم)
function getCustomers($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE device_id IN (SELECT id FROM devices WHERE user_id = ?) ORDER BY id DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

// إضافة زبون (لصفحة home.php)
function addCustomer($pdo, $device_id, $name, $drink, $price) {
    $stmt = $pdo->prepare("INSERT INTO customers (device_id, name, drink, price) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$device_id, $name, $drink, $price])) {
        return ['status' => 'success', 'message' => "تم إضافة الزبون بنجاح."];
    } else {
        return ['status' => 'error', 'message' => "فشل في إضافة الزبون."];
    }
}

// تحديث زبون (لصفحة home.php)
function updateCustomer($pdo, $customer_id, $name, $drink, $price) {
    $stmt = $pdo->prepare("UPDATE customers SET name = ?, drink = ?, price = ? WHERE id = ?");
    if ($stmt->execute([$name, $drink, $price, $customer_id])) {
        return ['status' => 'success', 'message' => "تم تحديث الزبون بنجاح."];
    } else {
        return ['status' => 'error', 'message' => "فشل في تحديث الزبون."];
    }
}

// حذف زبون (لصفحة home.php)
function deleteCustomer($pdo, $customer_id) {
    $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
    if ($stmt->execute([$customer_id])) {
        return ['status' => 'success', 'message' => "تم حذف الزبون بنجاح."];
    } else {
        return ['status' => 'error', 'message' => "فشل في حذف الزبون."];
    }
}
?>