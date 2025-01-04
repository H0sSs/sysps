<?php
// includes/config.php
session_start();

$host = 'localhost';
$db   = 'playstation_management';
$user = 'root'; // استبدل باسم المستخدم الخاص بقاعدة البيانات
$pass = '';     // استبدل بكلمة المرور الخاصة بقاعدة البيانات
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // التعامل مع الأخطاء بطريقة استثنائية
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // وضع جلب البيانات كصفوف مصفوفة مترابطة
    PDO::ATTR_EMULATE_PREPARES   => false,                  // تعطيل المحاكاة لتحضير الاستعلامات
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>
