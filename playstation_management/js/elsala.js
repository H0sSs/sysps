// elsala.js

// إدارة المشروبات في الصالة

// معالجة إضافة مشروب في الصالة
document.getElementById('addDrinkFormSala').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('csrf_token', csrfToken);

    fetch('process_add_drink_sala.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert(data.message);
            // تحديث قائمة المشروبات
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ أثناء إضافة المشروب. يرجى المحاولة مرة أخرى.');
    });
});

// تحديث مشروب في الصالة
function updateDrinkSala(drinkId) {
    const newName = prompt("أدخل اسم المشروب الجديد:");
    if (newName === null) return; // إلغاء العملية
    const newPrice = prompt("أدخل سعر المشروب الجديد (EGP):");
    if (newPrice === null) return;
    if (isNaN(newPrice) || newPrice < 0) {
        alert('يرجى إدخال سعر صالح.');
        return;
    }

    showLoading();
    fetch('process_update_drink_sala.php', {
        method: 'POST',
        headers: {
            'CSRF-Token': csrfToken,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ drink_id: drinkId, name: newName, price: newPrice })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.status === 'success') {
            alert(data.message);
            // تحديث قائمة المشروبات
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('حدث خطأ أثناء تحديث المشروب. يرجى المحاولة مرة أخرى.');
    });
}

// حذف مشروب في الصالة
function deleteDrinkSala(drinkId) {
    if (!confirm('هل أنت متأكد أنك تريد حذف هذا المشروب؟')) {
        return;
    }

    showLoading();
    fetch('process_delete_drink_sala.php', {
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
            // تحديث قائمة المشروبات
            location.reload();
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
