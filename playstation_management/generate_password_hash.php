<?php
// generate_password_hash.php
$password = '123456'; // استبدل 'YourPasswordHere' بكلمة المرور التي تريد استخدامها
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
echo $hashed_password;
?>
