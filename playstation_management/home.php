<?php
// home.php
require 'includes/config.php';
require 'includes/functions.php';

// بدء الجلسة إذا لم تكن قد بدأت بالفعل
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// التحقق من حالة تسجيل الدخول
check_login();

// جلب بيانات المستخدم
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// جلب جميع الأجهزة المرتبطة بالمستخدم
$devices = getDevices($pdo, $user_id);

// جلب إحصائيات الدخل الشهري (للمسؤولين)
$statistics = [];
if ($role === 'admin') {
    $stmt = $pdo->prepare("SELECT month_year, total_income FROM statistics ORDER BY id DESC LIMIT 12");
    $stmt->execute();
    $statistics = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// توليد رمز CSRF جديد
$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <!-- نفس محتويات head في home.html مع إضافة رمز CSRF -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="نظام إدارة بلايستيشن">
    <meta name="author" content="Mohamed Khalel">
    <meta name="keywords" content="Playstation, Gaming, Cafe">
    <title>إدارة بلايستيشن</title>
    <link rel="shortcut icon" href="assets/images/logojpg1.png" type="image/x-icon">
    <link rel="stylesheet" href="css/all.min.css">
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/main.css">
    <!-- إضافة رمز CSRF كـ meta tag -->
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrf_token); ?>">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f1f1f1;
        }
        .navbar-brand {
            font-weight: bold;
        }
        .card {
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: relative;
        }
        .add-device-layer {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .add-device-layer .card {
            width: 400px;
        }
        .discount-menu {
            background-color: #fff3cd;
            padding: 10px;
            border-radius: 5px;
        }
        .drinks-menu, .others-menu {
            padding-left: 10px;
        }
        /* إضافات لتحسين التصميم */
        .cursor-pointer {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="main">
        <!-- النافبار -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-3 w-75 m-auto rounded-5">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                <a class="navbar-brand" href="#">
                    <div class="title text-center">
                        <img width="60" src="assets/images/logojpg.png" alt="Logo">
                    </div>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="تبديل التنقل">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0 main-nav text-center d-flex list-unstyled">
                        <li class="nav-item">
                            <a href="#" class="nav-link active" onclick="showDevices(event)"><span class="nav-text">الأجهزة</span> <i class="fa fa-house nav-icon fa-xl"></i></a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" onclick="showSummaryTable(event)"><span class="nav-text">الملخصات</span> <i class="fa fa-table nav-icon fa-xl"></i></a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link" onclick="showSala(event)"><span class="nav-text">الصالة</span> <i class="fa fa-mug-hot nav-icon fa-xl"></i></a>
                        </li>
                        <?php if ($role === 'admin'): ?>
                            <li class="nav-item">
                                <a href="#" class="nav-link" onclick="showAdminPanel(event)"><span class="nav-text">لوحة الإدارة</span> <i class="fa fa-cogs nav-icon fa-xl"></i></a>
                            </li>
                        <?php endif; ?>
                    </ul>
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link text-white" href="logout.php"><span class="nav-text">تسجيل الخروج</span> <i class="fa-solid fa-right-from-bracket fa-xl"></i></a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        
        <!-- Device blocks -->
        <div class="device-blocks container py-5">
            <div class="d-flex justify-content-between align-items-center logic-buttons flex-wrap">
                <button type="button" class="btn btn-success add-btn" id="showAddBtn">إضافة جهاز <i class="fa-solid fa-plus fa-xl"></i></button>
                <span class="device-nums shadow rounded-5 gap-3">
                    <i class="fa fa-xl fa-laptop text-success"></i> 
                    <span id="numOfDevices" class="text-bg-success px-5 p-2 rounded-4 fw-bolder"><?php echo getDeviceCount($pdo, $user_id); ?></span>
                </span>
                <?php if ($role === 'admin'): ?>
                    <button type="button" class="btn btn-danger clearbtn" onclick="clearAllData()">مسح جميع البيانات</button>
                <?php endif; ?>
            </div>
            <div id="deviceContainer" class="row justify-content-center g-4 mt-1">
                <?php if ($devices): ?>
                    <?php foreach ($devices as $device): ?>
                        <div class="col-lg-4 col-md-6">
                            <div id="device<?php echo htmlspecialchars($device['id']); ?>" class="card position-relative" data-device-id="<?php echo htmlspecialchars($device['id']); ?>">
                                <div id="layer<?php echo htmlspecialchars($device['id']); ?>" class="layer-succes position-absolute top-0 start-0 end-0 bottom-0 bg-success z-3 d-flex justify-content-center align-items-center d-none">
                                    <i class="fa-regular fa-circle-check fa-4x text-white"></i>
                                    <h3 class="text-white">تم الإنجاز</h3>
                                </div>
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span class="running-device d-none d-flex justify-content-between align-items-center gap-1 text-success text-uppercase">
                                        <i class="fa-solid fa-circle ms-1 fa-beat"></i> يعمل
                                    </span>
                                    <span class="not-running-device d-flex justify-content-between align-items-center gap-1 text-danger">
                                        <i class="fa-solid fa-circle ms-1"></i> متوقف
                                    </span>
                                    <h5 class="fw-bolder text-uppercase device-num">غرفة <?php echo htmlspecialchars($device['room_number']); ?></h5>
                                    <div class="d-flex justify-content-center gap-3 p-2 align-items-center position-relative">
                                        <!-- استخدام معرف الجهاز بدلاً من رقم الغرفة -->
                                        <i title="إعادة تعيين" onclick="resetDevice(<?php echo htmlspecialchars($device['id']); ?>)" class="fa-solid fa-arrows-rotate fa-xl cursor-pointer"></i>
                                        <i title="حذف" onclick="deleteDevice(<?php echo htmlspecialchars($device['id']); ?>)" class="fa-solid fa-trash-can fa-xl cursor-pointer"></i>
                                    </div>
                                </div>
                                <div class="card-body d-flex justify-content-between align-items-center gap-3">
                                    <!-- استخدام معرف الجهاز بدلاً من رقم الغرفة -->
                                    <button class="btn btn-primary w-100 starting-time" onclick="startTimer(<?php echo htmlspecialchars($device['id']); ?>)">بدء</button>
                                    <button class="btn btn-warning pausing-time" title="إيقاف مؤقت" onclick="pauseTimer(<?php echo htmlspecialchars($device['id']); ?>)" id="pauseTimer<?php echo htmlspecialchars($device['id']); ?>" disabled><i class="fa fa-pause fa-xl"></i></button>
                                    <button title="استئناف" type="button" class="btn btn-info d-none continue-time" onclick="resumeTimer(<?php echo htmlspecialchars($device['id']); ?>)" id="continueTimer<?php echo htmlspecialchars($device['id']); ?>" disabled><i class="fa fa-play fa-xl"></i></button>
                                </div>
                                <button class="btn btn-danger stop-time w-100" onclick="stopTimer(<?php echo htmlspecialchars($device['id']); ?>)" disabled>إيقاف</button>

                                <div class="info-time">
                                    <span>وقت البدء: <span class="start-time d-none" id="startTime<?php echo htmlspecialchars($device['id']); ?>"></span></span>
                                    <span>وقت الإيقاف المؤقت: <span class="pause-time d-none" id="pauseTime<?php echo htmlspecialchars($device['id']); ?>"></span></span>
                                </div>
                                <div class="elapsed-time"><span>الوقت المستغرق: </span><span class="my-elapse" id="elapsedTime<?php echo htmlspecialchars($device['id']); ?>">00:00:00</span></div>
                                <select title="المعدل" id="rateSelector<?php echo htmlspecialchars($device['id']); ?>" onchange="saveRateSelection(<?php echo htmlspecialchars($device['id']); ?>)" class="form-select mb-3">
                                    <option value="10">10 EGP/ساعة</option>
                                    <option value="15">15 EGP/ساعة</option>
                                    <option value="20">20 EGP/ساعة</option>
                                    <option value="25">25 EGP/ساعة</option>
                                    <option value="30">30 EGP/ساعة</option>
                                    <option value="35">35 EGP/ساعة</option>
                                    <option value="40">40 EGP/ساعة</option>
                                </select>
                                <!-- Start Menu -->
                                <div class="menu">
                                    <button onclick="toggleMenu(this, '.drinks<?php echo htmlspecialchars($device['id']); ?>')" class="btn btn-secondary mb-2">إظهار القائمة <i class="fa-solid fa-caret-down fa-xl"></i></button>
                                    <div class="drinks<?php echo htmlspecialchars($device['id']); ?> d-none">
                                        <div class="p-3 all-menu rounded-5 shadow">
                                            <div class="p-3 rounded-4 all-drinks shadow">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <h4 class="h5 pt-2">المشروبات:</h4>
                                                    <i onclick="toggleDrinks(this, '<?php echo htmlspecialchars($device['id']); ?>')" class="fa-solid fa-caret-down fa-xl show-drinks-bar cursor-pointer"></i>
                                                    <i onclick="toggleDrinks(this, '<?php echo htmlspecialchars($device['id']); ?>')" class="fa-solid fa-caret-up fa-xl d-none hide-drinks-bar cursor-pointer"></i>
                                                </div>
                                                <div class="d-none drinks-menu">
                                                    <?php
                                                    // جلب المشروبات من قاعدة البيانات
                                                    $stmt_drinks = $pdo->prepare("SELECT * FROM drinks");
                                                    $stmt_drinks->execute();
                                                    $all_drinks = $stmt_drinks->fetchAll(PDO::FETCH_ASSOC);

                                                    foreach ($all_drinks as $drink_item):
                                                    ?>
                                                        <div class="my-2 menu-item d-flex justify-content-between align-items-center">
                                                            <label for="drink<?php echo htmlspecialchars($device['id'] . '_' . $drink_item['id']); ?>"><?php echo htmlspecialchars($drink_item['name']); ?> (<?php echo htmlspecialchars($drink_item['price']); ?> EGP)</label>
                                                            <input min="0" title="مشروب" class="form-control drink-input" data-device-id="<?php echo htmlspecialchars($device['id']); ?>" id="drink<?php echo htmlspecialchars($device['id'] . '_' . $drink_item['id']); ?>" type="number" value="0" onchange="saveDrinkQuantities(<?php echo htmlspecialchars($device['id']); ?>)">
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            <div class="p-3 rounded-4 mt-2 all-others shadow">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <h4 class="h5 pt-2">أخرى :</h4>
                                                    <i onclick="toggleOthers(this, '<?php echo htmlspecialchars($device['id']); ?>')" class="fa-solid fa-caret-down fa-xl show-others-bar cursor-pointer"></i>
                                                    <i onclick="toggleOthers(this, '<?php echo htmlspecialchars($device['id']); ?>')" class="fa-solid fa-caret-up fa-xl d-none hide-others-bar cursor-pointer"></i>
                                                </div>
                                                <div class="d-none others-menu">
                                                    <div class="my-2 menu-item d-flex justify-content-between align-items-center">
                                                        <label for="otherDrink<?php echo htmlspecialchars($device['id']); ?>">مشروب آخر (15 EGP)</label>
                                                        <input min="0" title="مشروب آخر" class="form-control drink-input" data-device-id="<?php echo htmlspecialchars($device['id']); ?>" id="otherDrink<?php echo htmlspecialchars($device['id']); ?>" type="number" value="0" onchange="saveDrinkQuantities(<?php echo htmlspecialchars($device['id']); ?>)">
                                                    </div>
                                                </div>
                                            </div>
                                            <button onclick="calculateMenuCost(<?php echo htmlspecialchars($device['id']); ?>)" class="btn btn-success w-100 m-auto mt-2 calc-menu">حساب التكلفة <i class="fa-solid fa-calculator"></i></button>
                                            <div class="mt-2">تكلفة المشروبات: <span id="costm<?php echo htmlspecialchars($device['id']); ?>">0</span> EGP</div>
                                        </div>
                                    </div>
                                </div>
                                <!-- End Menu -->
                                <div id="totalDiscount<?php echo htmlspecialchars($device['id']); ?>" class="mt-2 d-none">قيمة الخصم: <span id="discountCost<?php echo htmlspecialchars($device['id']); ?>">0</span> EGP</div>
                                <div class="time-cost"><span>التكلفة الإجمالية:</span> <span class="my-cost" id="cost<?php echo htmlspecialchars($device['id']); ?>">0</span> EGP</div>
                                <h4 id="discountRequest<?php echo htmlspecialchars($device['id']); ?>" onclick="discountRequest(event, <?php echo htmlspecialchars($device['id']); ?>)" class="h5 d-flex justify-content-between align-items-center d-none cursor-pointer">
                                    <span>إضافة خصم؟</span> <i class="fa fa-plus-circle fa-xl"></i>
                                </h4>
                                <div id="discountMenu<?php echo htmlspecialchars($device['id']); ?>" class="discount-menu position-relative d-none">
                                    <div class="d-flex justify-content-between align-items-center discount-input">
                                        <form onsubmit="calcDiscountCost(event, <?php echo htmlspecialchars($device['id']); ?>)" class="w-100">
                                            <div class="input-group mb-3">
                                                <input min="0" id="discount<?php echo htmlspecialchars($device['id']); ?>" type="number" class="form-control" placeholder="أدخل قيمة الخصم" required>
                                                <button class="btn btn-success" type="submit">حساب الخصم</button>
                                            </div>
                                            <!-- تضمين رمز CSRF في النموذج -->
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                        </form>
                                    </div>
                                    <div id="discountLayer<?php echo htmlspecialchars($device['id']); ?>" class="d-none discount-layer position-absolute d-flex justify-content-center align-items-center bg-success rounded-2 top-0 bottom-0 start-0 end-0">
                                        <h2>تم إضافة الخصم</h2>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <button class="btn btn-primary submit w-100" onclick="submitDevice(<?php echo htmlspecialchars($device['id']); ?>)" disabled>تقديم الملخص</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center">لا توجد أجهزة متاحة. اضغط على "إضافة جهاز" لإضافة جهاز جديد.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- قسم إضافة جهاز جديد -->
        <div class="add-device-layer d-none">
            <div class="card">
                <div class="card-header">
                    <h5>إضافة جهاز جديد</h5>
                </div>
                <div class="card-body">
                    <form id="addDeviceForm">
                        <div class="mb-3">
                            <label for="roomNumber" class="form-label">رقم الغرفة</label>
                            <input type="number" class="form-control" id="roomNumber" name="room_number" required>
                        </div>
                        <button type="submit" id="formBtn" class="btn btn-success" disabled>إضافة</button>
                        <div id="existingRoom" class="mt-2"></div>
                        <!-- تضمين رمز CSRF في النموذج -->
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    </form>
                </div>
            </div>
        </div>

        <!-- قسم الملخصات -->
        <div class="container py-5 summary-table d-none">
            <h2 class="mb-4">الملخصات</h2>
            <?php if ($devices): ?>
                <table class="table table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>رقم الغرفة</th>
                            <th>التاريخ</th>
                            <th>وقت البدء</th>
                            <th>وقت النهاية</th>
                            <th>الوقت المستغرق</th>
                            <th>التكلفة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($devices as $device): ?>
                            <?php
                                $summaries = getDeviceSummaries($pdo, $device['id']);
                                if ($summaries):
                                    foreach ($summaries as $summary):
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($device['room_number']); ?></td>
                                    <td><?php echo htmlspecialchars($summary['date']); ?></td>
                                    <td><?php echo htmlspecialchars($summary['start_time']); ?></td>
                                    <td><?php echo htmlspecialchars($summary['end_time']); ?></td>
                                    <td><?php echo htmlspecialchars($summary['elapsed_time']); ?></td>
                                    <td><?php echo htmlspecialchars(number_format($summary['cost'], 2)); ?> EGP</td>
                                </tr>
                            <?php
                                    endforeach;
                                endif;
                            ?>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="5">إجمالي التكلفة</th>
                            <th id="totalCost">
                                <?php
                                    $stmt_total = $pdo->prepare("SELECT SUM(cost) as total_cost FROM summaries WHERE device_id IN (SELECT id FROM devices WHERE user_id = ?)");
                                    $stmt_total->execute([$user_id]);
                                    $total_cost = $stmt_total->fetch(PDO::FETCH_ASSOC);
                                    echo htmlspecialchars(number_format($total_cost['total_cost'] ?? 0, 2));
                                ?>
                                EGP
                            </th>
                        </tr>
                    </tfoot>
                </table>
                <button class="btn btn-secondary btn-sm" onclick="downloadPDF()">تحميل الملخصات كـ PDF</button>
            <?php else: ?>
                <p class="text-center">لا توجد ملخصات متاحة.</p>
            <?php endif; ?>
        </div>

        <!-- قسم الصالة -->
        <div class="container py-5 main-elsala d-none">
            <h2 class="mb-4">الصالة</h2>
            <!-- محتوى الصالة مع الحفاظ على الصور -->
            <div class="text-center">
                <img src="assets/images/psConsole.png" alt="PS Console" class="img-fluid">
            </div>
            <!-- نموذج إدارة المشروبات في الصالة -->
            <div class="mt-4">
                <h4>إدارة المشروبات في الصالة</h4>
                <form id="addDrinkFormSala">
                    <div class="mb-3">
                        <label for="drinkNameSala" class="form-label">اسم المشروب</label>
                        <input type="text" class="form-control" id="drinkNameSala" name="drinkNameSala" required>
                    </div>
                    <div class="mb-3">
                        <label for="drinkPriceSala" class="form-label">سعر المشروب (EGP)</label>
                        <input type="number" class="form-control" id="drinkPriceSala" name="drinkPriceSala" min="0" step="0.01" required>
                    </div>
                    <button type="submit" class="btn btn-primary">إضافة مشروب</button>
                </form>
                <!-- قائمة المشروبات الحالية -->
                <div class="mt-4">
                    <h5>المشروبات الحالية:</h5>
                    <ul class="list-group">
                        <?php
                        // جلب المشروبات من قاعدة البيانات
                        $stmt_current_drinks = $pdo->prepare("SELECT * FROM drinks");
                        $stmt_current_drinks->execute();
                        $current_drinks = $stmt_current_drinks->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($current_drinks as $current_drink):
                        ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?php echo htmlspecialchars($current_drink['name']); ?> - <?php echo htmlspecialchars($current_drink['price']); ?> EGP
                                <div>
                                    <button class="btn btn-sm btn-warning me-2" onclick="updateDrinkSala(<?php echo htmlspecialchars($current_drink['id']); ?>)">تعديل</button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteDrinkSala(<?php echo htmlspecialchars($current_drink['id']); ?>)">حذف</button>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- قسم لوحة الإدارة (للمسؤولين فقط) -->
        <?php if ($role === 'admin'): ?>
            <div class="container py-5 admin-panel d-none">
                <h2 class="mb-4">لوحة الإدارة</h2>
                <!-- إدارة المستخدمين -->
                <div class="mb-4">
                    <h3>إدارة المستخدمين</h3>
                    <?php
                    $users = getUsers($pdo);
                    ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>البريد الإلكتروني</th>
                                <th>الدور</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user_item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user_item['id']); ?></td>
                                    <td><?php echo htmlspecialchars($user_item['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user_item['role']); ?></td>
                                    <td>
                                        <button onclick="deleteUser(<?php echo htmlspecialchars($user_item['id']); ?>)" class="btn btn-danger btn-sm">حذف</button>
                                        <!-- يمكن إضافة زر لتغيير الدور هنا -->
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- إدارة المشروبات -->
                <div class="mb-4">
                    <h3>إدارة المشروبات</h3>
                    <?php
                    $admin_drinks = getDrinks($pdo);
                    ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>الاسم</th>
                                <th>السعر</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admin_drinks as $drink_item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($drink_item['id']); ?></td>
                                    <td><?php echo htmlspecialchars($drink_item['name']); ?></td>
                                    <td><?php echo htmlspecialchars($drink_item['price']); ?> EGP</td>
                                    <td>
                                        <button onclick="deleteDrink(<?php echo htmlspecialchars($drink_item['id']); ?>)" class="btn btn-danger btn-sm">حذف</button>
                                        <!-- يمكن إضافة زر لتعديل المشروب هنا -->
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- عرض الإحصائيات -->
                <div>
                    <h3>إحصائيات الدخل الشهري</h3>
                    <canvas id="incomeChart" width="400" height="200"></canvas>
                </div>
            </div>
        <?php endif; ?>

        <!-- Loading Spinner -->
        <div class="loading d-none position-fixed d-flex justify-content-center align-items-center top-0 bottom-0 start-0 end-0">
            <div class="spinner position-relative d-flex flex-column justify-content-center align-items-center">
                <img src="assets/images/logojpg.png" alt="Logo">
                <img class="position-absolute loader" src="assets/images/psConsole.png" alt="Loading">
            </div>
        </div>

        <!-- إضافة ملفات JavaScript -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.3.1/jspdf.umd.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="js/bootstrap.bundle.min.js"></script>
        <script src="js/elsala.js"></script>
        <script src="js/script.js"></script>

        <?php if ($role === 'admin'): ?>
            <script>
                // جلب إحصائيات الدخل الشهري من قاعدة البيانات وعرضها باستخدام Chart.js
                const ctx = document.getElementById('incomeChart').getContext('2d');
                const incomeData = <?php echo json_encode($statistics); ?>;
                const labels = incomeData.map(stat => stat.month_year);
                const data = incomeData.map(stat => stat.total_income);

                const incomeChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'إجمالي الدخل',
                            data: data,
                            backgroundColor: [
                                'rgba(255, 99, 132, 0.2)',
                                'rgba(54, 162, 235, 0.2)',
                                'rgba(255, 206, 86, 0.2)',
                                'rgba(75, 192, 192, 0.2)',
                                'rgba(153, 102, 255, 0.2)',
                                'rgba(255, 159, 64, 0.2)',
                                'rgba(255, 99, 132, 0.2)',
                                'rgba(54, 162, 235, 0.2)',
                                'rgba(255, 206, 86, 0.2)',
                                'rgba(75, 192, 192, 0.2)',
                                'rgba(153, 102, 255, 0.2)',
                                'rgba(255, 159, 64, 0.2)'
                            ],
                            borderColor: [
                                'rgba(255,99,132,1)',
                                'rgba(54, 162, 235, 1)',
                                'rgba(255, 206, 86, 1)',
                                'rgba(75, 192, 192, 1)',
                                'rgba(153, 102, 255, 1)',
                                'rgba(255, 159, 64, 1)',
                                'rgba(255,99,132,1)',
                                'rgba(54, 162, 235, 1)',
                                'rgba(255, 206, 86, 1)',
                                'rgba(75, 192, 192, 1)',
                                'rgba(153, 102, 255, 1)',
                                'rgba(255, 159, 64, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            title: {
                                display: true,
                                text: 'إحصائيات الدخل الشهري'
                            }
                        }
                    },
                });
            </script>
        <?php endif; ?>
    </div>
</body>
</html>
