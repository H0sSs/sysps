<?php
// suggest_drinks.php
require 'includes/config.php';
require 'includes/functions.php';

// التحقق من تسجيل الدخول
check_login();

if (isset($_GET['query'])) {
    $query = trim($_GET['query']);
    $stmt = $pdo->prepare("SELECT name FROM drinks WHERE name LIKE ? LIMIT 5");
    $stmt->execute(['%' . $query . '%']);
    $suggestions = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode(['status' => 'success', 'suggestions' => $suggestions]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'No query provided.']);
}
?>
