<?php
// manage_drinks.php
require 'includes/config.php';
require 'includes/functions.php';

// التحقق من صلاحيات المسؤول
check_login();
if (!is_admin($pdo)) {
    echo "Access denied.";
    exit();
}

// جلب جميع المشروبات
$drinks = getDrinks($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Drinks</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<body>
    <div class="container py-5">
        <h2>Manage Drinks</h2>
        <?php if ($drinks): ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Price (EGP)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($drinks as $drink_row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($drink_row['id']); ?></td>
                            <td><?php echo htmlspecialchars($drink_row['name']); ?></td>
                            <td><?php echo htmlspecialchars($drink_row['price']); ?></td>
                            <td>
                                <button onclick="deleteDrink(<?php echo htmlspecialchars($drink_row['id']); ?>)" class="btn btn-danger btn-sm">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No drinks found.</p>
        <?php endif; ?>
    </div>

    <!-- JavaScript لمعالجة حذف المشروبات -->
    <script>
        function deleteDrink(drinkId) {
            if (confirm('Are you sure you want to delete this drink?')) {
                fetch('delete_drink.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `drink_id=${drinkId}`
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
