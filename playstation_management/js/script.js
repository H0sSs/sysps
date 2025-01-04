// script.js

// الحصول على رمز CSRF من meta tag
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// وظائف إظهار وإخفاء الأقسام
function showDevices(event) {
    event.preventDefault();
    toggleSection('.device-blocks');
}

function showSummaryTable(event) {
    event.preventDefault();
    toggleSection('.summary-table');
}

function showSala(event) {
    event.preventDefault();
    toggleSection('.main-elsala');
}

function showAdminPanel(event) {
    event.preventDefault();
    toggleSection('.admin-panel');
}

function toggleSection(sectionClass) {
    // إخفاء جميع الأقسام
    document.querySelectorAll('.device-blocks, .summary-table, .main-elsala, .admin-panel').forEach(section => {
        section.classList.add('d-none');
    });
    // إظهار القسم المطلوب
    document.querySelector(sectionClass).classList.remove('d-none');
}

// وظائف لإدارة الأجهزة

// إظهار نموذج إضافة جهاز
document.getElementById('showAddBtn').addEventListener('click', function() {
    document.querySelector('.add-device-layer').classList.remove('d-none');
});

// إخفاء نموذج إضافة جهاز عند النقر خارج النموذج
document.querySelector('.add-device-layer').addEventListener('click', function(event) {
    if (event.target === this) {
        this.classList.add('d-none');
    }
});

// تمكين زر إضافة الجهاز فقط عند إدخال رقم الغرفة
document.getElementById('roomNumber').addEventListener('input', function() {
    const formBtn = document.getElementById('formBtn');
    if (this.value.trim() !== '') {
        formBtn.disabled = false;
    } else {
        formBtn.disabled = true;
    }
});

// معالجة إضافة جهاز جديد
document.getElementById('addDeviceForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('csrf_token', csrfToken);

    fetch('process_add_device.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const existingRoomDiv = document.getElementById('existingRoom');
        if (data.status === 'success') {
            existingRoomDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
            // تحديث عدد الأجهزة
            document.getElementById('numOfDevices').innerText = parseInt(document.getElementById('numOfDevices').innerText) + 1;
            // إخفاء النموذج بعد قليل
            setTimeout(() => {
                document.querySelector('.add-device-layer').classList.add('d-none');
                existingRoomDiv.innerHTML = '';
                // إعادة تحميل قائمة الأجهزة
                location.reload();
            }, 1500);
        } else {
            existingRoomDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        const existingRoomDiv = document.getElementById('existingRoom');
        existingRoomDiv.innerHTML = `<div class="alert alert-danger">حدث خطأ أثناء إضافة الجهاز. يرجى المحاولة مرة أخرى.</div>`;
    });
});

// وظائف لإدارة التايمر

function startTimer(deviceId) {
    showLoading();
    fetch('process_start_timer.php', {
        method: 'POST',
        headers: {
            'CSRF-Token': csrfToken,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ device_id: deviceId })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.status === 'success') {
            // تحديث واجهة المستخدم حسب الحاجة
            alert(data.message);
            // تفعيل/تعطيل الأزرار حسب الحالة
            document.getElementById(`pauseTimer${deviceId}`).disabled = false;
            document.getElementById(`continueTimer${deviceId}`).disabled = true;
            document.querySelector(`#device${deviceId} .starting-time`).disabled = true;
            document.querySelector(`#device${deviceId} .stop-time`).disabled = false;
            // تحديث وقت البدء
            document.getElementById(`startTime${deviceId}`).innerText = formatTime(new Date(data.start_time));
            document.getElementById(`startTime${deviceId}`).classList.remove('d-none');
            // تحديث حالة الغرفة إلى "يعمل"
            document.querySelector(`#device${deviceId} .running-device`).classList.remove('d-none');
            document.querySelector(`#device${deviceId} .not-running-device`).classList.add('d-none');
            // بدء العد التنازلي
            startElapsedTimer(deviceId, new Date(data.start_time));
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('حدث خطأ أثناء بدء التايمر. يرجى المحاولة مرة أخرى.');
    });
}

function pauseTimer(deviceId) {
    showLoading();
    fetch('process_pause_timer.php', {
        method: 'POST',
        headers: {
            'CSRF-Token': csrfToken,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ device_id: deviceId })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.status === 'success') {
            alert(data.message);
            // تعطيل زر الإيقاف المؤقت وتفعيل زر الاستئناف
            document.getElementById(`pauseTimer${deviceId}`).disabled = true;
            document.getElementById(`continueTimer${deviceId}`).classList.remove('d-none');
            document.getElementById(`continueTimer${deviceId}`).disabled = false;
            // تحديث وقت الإيقاف المؤقت
            document.getElementById(`pauseTime${deviceId}`).innerText = formatTime(new Date(data.pause_time));
            document.getElementById(`pauseTime${deviceId}`).classList.remove('d-none');
            // إيقاف العد التنازلي
            stopElapsedTimer(deviceId);
            // حساب التكلفة الجزئية
            if (data.partial_cost !== undefined) {
                document.getElementById(`cost${deviceId}`).innerText = data.partial_cost.toFixed(2);
            }
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('حدث خطأ أثناء إيقاف التايمر. يرجى المحاولة مرة أخرى.');
    });
}

function resumeTimer(deviceId) {
    showLoading();
    fetch('process_resume_timer.php', {
        method: 'POST',
        headers: {
            'CSRF-Token': csrfToken,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ device_id: deviceId })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.status === 'success') {
            alert(data.message);
            // تفعيل زر الإيقاف المؤقت وتعطيل زر الاستئناف
            document.getElementById(`pauseTimer${deviceId}`).disabled = false;
            document.getElementById(`continueTimer${deviceId}`).classList.add('d-none');
            document.getElementById(`continueTimer${deviceId}`).disabled = true;
            // إعادة بدء العد التنازلي
            startElapsedTimer(deviceId, new Date(data.start_time));
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('حدث خطأ أثناء استئناف التايمر. يرجى المحاولة مرة أخرى.');
    });
}

function stopTimer(deviceId) {
    showLoading();
    fetch('process_stop_timer.php', {
        method: 'POST',
        headers: {
            'CSRF-Token': csrfToken,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ device_id: deviceId })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.status === 'success') {
            alert(data.message);
            // تحديث وقت الإيقاف النهائي
            document.getElementById(`pauseTime${deviceId}`).innerText = formatTime(new Date(data.end_time));
            document.getElementById(`pauseTime${deviceId}`).classList.remove('d-none');
            // تحديث التكلفة الإجمالية
            document.getElementById(`cost${deviceId}`).innerText = data.cost.toFixed(2);
            // إعادة تعيين التايمر
            document.getElementById(`elapsedTime${deviceId}`).innerText = '00:00:00';
            // تعطيل الأزرار
            document.querySelector(`#device${deviceId} .starting-time`).disabled = false;
            document.getElementById(`pauseTimer${deviceId}`).disabled = true;
            document.getElementById(`continueTimer${deviceId}`).disabled = true;
            document.querySelector(`#device${deviceId} .stop-time`).disabled = true;
            // إخفاء وقت البدء ووقت الإيقاف المؤقت
            document.getElementById(`startTime${deviceId}`).classList.add('d-none');
            // تحديث حالة الغرفة إلى "متوقف"
            document.querySelector(`#device${deviceId} .running-device`).classList.add('d-none');
            document.querySelector(`#device${deviceId} .not-running-device`).classList.remove('d-none');
            // تعطيل عرض إضافة خصم
            document.getElementById(`discountRequest${deviceId}`).classList.remove('d-none');
            // إيقاف العد التنازلي
            stopElapsedTimer(deviceId);
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('حدث خطأ أثناء إيقاف التايمر. يرجى المحاولة مرة أخرى.');
    });
}

// وظائف لإدارة العد التنازلي

const timers = {};

function startElapsedTimer(deviceId, startTime) {
    const elapsedTimeSpan = document.getElementById(`elapsedTime${deviceId}`);
    if (timers[deviceId]) {
        clearInterval(timers[deviceId]);
    }
    timers[deviceId] = setInterval(() => {
        const now = new Date();
        const start = new Date(startTime);
        const elapsed = now - start;
        if (elapsed < 0) {
            elapsedTimeSpan.innerText = '00:00:00';
            return;
        }
        const hours = String(Math.floor(elapsed / (1000 * 60 * 60))).padStart(2, '0');
        const minutes = String(Math.floor((elapsed % (1000 * 60 * 60)) / (1000 * 60))).padStart(2, '0');
        const seconds = String(Math.floor((elapsed % (1000 * 60)) / 1000)).padStart(2, '0');
        elapsedTimeSpan.innerText = `${hours}:${minutes}:${seconds}`;
    }, 1000);
}

function stopElapsedTimer(deviceId) {
    if (timers[deviceId]) {
        clearInterval(timers[deviceId]);
        delete timers[deviceId];
    }
}

// تنسيق الوقت إلى صيغة 24 ساعة
function formatTime(date) {
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    const seconds = String(date.getSeconds()).padStart(2, '0');
    return `${hours}:${minutes}:${seconds}`;
}

// وظائف لإدارة القوائم

function toggleMenu(button, menuSelector) {
    event.preventDefault();
    const menu = document.querySelector(menuSelector);
    menu.classList.toggle('d-none');
}

function toggleDrinks(icon, deviceId) {
    const drinksMenu = document.querySelector(`.drinks-menu`);
    if (drinksMenu.classList.contains('d-none')) {
        drinksMenu.classList.remove('d-none');
        icon.classList.add('d-none');
        icon.nextElementSibling.classList.remove('d-none');
    } else {
        drinksMenu.classList.add('d-none');
        icon.classList.add('d-none');
        icon.previousElementSibling.classList.remove('d-none');
    }
}

function toggleOthers(icon, deviceId) {
    const othersMenu = document.querySelector(`.others-menu`);
    if (othersMenu.classList.contains('d-none')) {
        othersMenu.classList.remove('d-none');
        icon.classList.add('d-none');
        icon.nextElementSibling.classList.remove('d-none');
    } else {
        othersMenu.classList.add('d-none');
        icon.classList.add('d-none');
        icon.previousElementSibling.classList.remove('d-none');
    }
}

// حفظ اختيار المعدل
function saveRateSelection(deviceId) {
    const rate = document.getElementById(`rateSelector${deviceId}`).value;
    showLoading();
    fetch('process_save_rate.php', {
        method: 'POST',
        headers: {
            'CSRF-Token': csrfToken,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ device_id: deviceId, rate: rate })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.status === 'success') {
            alert(data.message);
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('حدث خطأ أثناء حفظ المعدل. يرجى المحاولة مرة أخرى.');
    });
}

// حفظ كميات المشروبات
function saveDrinkQuantities(deviceId) {
    const drinkInputs = document.querySelectorAll(`.drink-input[data-device-id="${deviceId}"]`);
    const drinks = [];
    drinkInputs.forEach(input => {
        const parts = input.id.split('_');
        if (parts.length < 2) return;
        const drinkId = parts[1];
        const quantity = input.value;
        if (quantity > 0) {
            drinks.push({ drink_id: drinkId, quantity: quantity });
        }
    });
    showLoading();
    fetch('process_save_drink_quantities.php', {
        method: 'POST',
        headers: {
            'CSRF-Token': csrfToken,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ device_id: deviceId, drinks: drinks })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.status === 'success') {
            alert(data.message);
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('حدث خطأ أثناء حفظ كميات المشروبات. يرجى المحاولة مرة أخرى.');
    });
}

// حساب تكلفة القائمة
function calculateMenuCost(deviceId) {
    showLoading();
    fetch('process_calculate_menu_cost.php', {
        method: 'POST',
        headers: {
            'CSRF-Token': csrfToken,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ device_id: deviceId })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.status === 'success') {
            document.getElementById(`costm${deviceId}`).innerText = data.menu_cost;
            alert('تم حساب تكلفة القائمة بنجاح.');
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('حدث خطأ أثناء حساب تكلفة القائمة. يرجى المحاولة مرة أخرى.');
    });
}

// تقديم الملخص
function submitDevice(deviceId) {
    showLoading();
    fetch('process_submit_device_summary.php', {
        method: 'POST',
        headers: {
            'CSRF-Token': csrfToken,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ device_id: deviceId })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.status === 'success') {
            alert(data.message);
            // تحديث واجهة المستخدم حسب الحاجة
            // مثل إعادة تعيين التايمر
            document.getElementById(`elapsedTime${deviceId}`).innerText = '00:00:00';
            // تعطيل الأزرار
            document.querySelector(`#device${deviceId} .starting-time`).disabled = false;
            document.getElementById(`pauseTimer${deviceId}`).disabled = true;
            document.getElementById(`continueTimer${deviceId}`).disabled = true;
            document.querySelector(`#device${deviceId} .stop-time`).disabled = true;
            // إخفاء وقت البدء ووقت الإيقاف المؤقت
            document.getElementById(`startTime${deviceId}`).classList.add('d-none');
            document.getElementById(`pauseTime${deviceId}`).classList.add('d-none');
            // تحديث حالة الغرفة إلى "متوقف"
            document.querySelector(`#device${deviceId} .running-device`).classList.add('d-none');
            document.querySelector(`#device${deviceId} .not-running-device`).classList.remove('d-none');
            // تعطيل عرض إضافة خصم
            document.getElementById(`discountRequest${deviceId}`).classList.remove('d-none');
            // إيقاف العد التنازلي
            stopElapsedTimer(deviceId);
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('حدث خطأ أثناء تقديم الملخص. يرجى المحاولة مرة أخرى.');
    });
}

// طلب إضافة خصم
function discountRequest(event, deviceId) {
    event.preventDefault();
    const discountMenu = document.getElementById(`discountMenu${deviceId}`);
    discountMenu.classList.toggle('d-none');
}

// حساب تكلفة الخصم
function calcDiscountCost(event, deviceId) {
    event.preventDefault();
    const discountValue = parseFloat(document.getElementById(`discount${deviceId}`).value);
    if (isNaN(discountValue) || discountValue < 0) {
        alert('قيمة الخصم يجب أن تكون رقمية وغير سالبة.');
        return;
    }
    showLoading();
    fetch('process_calculate_discount.php', {
        method: 'POST',
        headers: {
            'CSRF-Token': csrfToken,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ device_id: deviceId, discount: discountValue })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.status === 'success') {
            document.getElementById(`discountCost${deviceId}`).innerText = data.discount_cost;
            document.getElementById(`totalDiscount${deviceId}`).classList.remove('d-none');
            document.getElementById(`discountMenu${deviceId}`).classList.add('d-none');
            document.getElementById(`discountLayer${deviceId}`).classList.remove('d-none');
            setTimeout(() => {
                document.getElementById(`discountLayer${deviceId}`).classList.add('d-none');
            }, 2000);
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('حدث خطأ أثناء حساب الخصم. يرجى المحاولة مرة أخرى.');
    });
}

// حذف جهاز
function deleteDevice(deviceId) {
    if (!confirm('هل أنت متأكد أنك تريد حذف هذا الجهاز؟')) {
        return;
    }
    showLoading();
    fetch('process_delete_device.php', {
        method: 'POST',
        headers: {
            'CSRF-Token': csrfToken,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ device_id: deviceId })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.status === 'success') {
            alert(data.message);
            // تحديث عدد الأجهزة
            document.getElementById('numOfDevices').innerText = parseInt(document.getElementById('numOfDevices').innerText) - 1;
            // إزالة الجهاز من الواجهة
            const deviceElement = document.getElementById(`device${deviceId}`);
            if (deviceElement) {
                deviceElement.parentElement.remove();
            }
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('حدث خطأ أثناء حذف الجهاز. يرجى المحاولة مرة أخرى.');
    });
}

// وظائف لإدارة المشروبات في الصالة
// سيتم التعامل معها في elsala.js

// إدارة المستخدمين والمشروبات كمسؤول
// حذف مستخدم
function deleteUser(userId) {
    if (!confirm('هل أنت متأكد أنك تريد حذف هذا المستخدم؟')) {
        return;
    }
    showLoading();
    fetch('process_delete_user.php', {
        method: 'POST',
        headers: {
            'CSRF-Token': csrfToken,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ user_id: userId })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.status === 'success') {
            alert(data.message);
            // إزالة المستخدم من الجدول
            const userRow = document.querySelector(`tr[data-user-id="${userId}"]`);
            if (userRow) {
                userRow.remove();
            }
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('حدث خطأ أثناء حذف المستخدم. يرجى المحاولة مرة أخرى.');
    });
}

// حذف مشروب كمسؤول
function deleteDrink(drinkId) {
    if (!confirm('هل أنت متأكد أنك تريد حذف هذا المشروب؟')) {
        return;
    }
    showLoading();
    fetch('process_delete_drink.php', {
        method: 'POST',
        headers: {
            'CSRF-Token': csrfToken,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ drink_id: drinkId })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.status === 'success') {
            alert(data.message);
            // إزالة المشروب من الجدول
            const drinkRow = document.querySelector(`tr[data-drink-id="${drinkId}"]`);
            if (drinkRow) {
                drinkRow.remove();
            }
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('حدث خطأ أثناء حذف المشروب. يرجى المحاولة مرة أخرى.');
    });
}

// تحميل الملخصات كـ PDF
function downloadPDF() {
    // يمكن تنفيذ هذا في الخادم الخلفي أو استخدام jsPDF
    // مثال باستخدام jsPDF و AutoTable
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    doc.text("ملخصات الأجهزة", 14, 20);

    // جمع البيانات من الجدول
    const table = document.querySelector('.summary-table table');
    const headers = [];
    table.querySelectorAll('thead th').forEach(th => headers.push(th.innerText));
    
    const rows = [];
    table.querySelectorAll('tbody tr').forEach(tr => {
        const row = [];
        tr.querySelectorAll('td').forEach(td => row.push(td.innerText));
        rows.push(row);
    });

    doc.autoTable({
        head: [headers],
        body: rows,
        startY: 25,
    });

    doc.save('ملخصات.pdf');
}

// وظائف أخرى يمكن إضافتها حسب الحاجة

// وظيفة إظهار الـ Loading Spinner
function showLoading() {
    document.querySelector('.loading').classList.remove('d-none');
}

// وظيفة إخفاء الـ Loading Spinner
function hideLoading() {
    document.querySelector('.loading').classList.add('d-none');
}
