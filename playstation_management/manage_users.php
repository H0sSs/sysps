<?php
// manage_users.php
require 'includes/config.php';
require 'includes/functions.php';

// التحقق من صلاحيات المسؤول
check_login();
if (!is_admin($pdo)) {
    echo "Access denied.";
    exit();
}

// جلب جميع المستخدمين
$stmt = $pdo->prepare("SELECT id, email, role FROM users");
$stmt->execute();
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<body>
    <div class="container py-5">
        <h2>Manage Users</h2>
        <?php if ($users): ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user_row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user_row['id']); ?></td>
                            <td><?php echo htmlspecialchars($user_row['email']); ?></td>
                            <td><?php echo htmlspecialchars($user_row['role']); ?></td>
                            <td>
                                <?php if ($user_row['id'] != $_SESSION['user_id']): ?>
                                    <button onclick="deleteUser(<?php echo htmlspecialchars($user_row['id']); ?>)" class="btn btn-danger btn-sm">Delete</button>
                                <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No users found.</p>
        <?php endif; ?>
    </div>

    <!-- JavaScript لمعالجة حذف المستخدمين -->
    <script>
        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user?')) {
                fetch('delete_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `user_id=${userId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert(data.message);
                        window.location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        }
    </script>
</body>
</html>
