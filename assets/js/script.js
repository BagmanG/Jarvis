// Инициализация Telegram Web App
const tg = window.Telegram.WebApp;

// Основная функция инициализации
function initApp() {
    // Расширяем приложение на весь экран
    tg.expand();
    
    // Показываем главную кнопку
    tg.MainButton.setText("Закрыть");
    tg.MainButton.show();
    tg.MainButton.onClick(() => tg.close());
    
    // Получаем данные пользователя
    const user = tg.initDataUnsafe?.user;
    
    if (user && user.id) {
        // Отображаем User ID
        document.getElementById('user-id').textContent = user.id;
        
        // Отображаем аватарку
        const avatarImg = document.getElementById('user-avatar');
        if (user.photo_url) {
            avatarImg.src = user.photo_url;
        } else {
            // Заглушка если аватарки нет
            avatarImg.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iNTAiIGN5PSI1MCIgcj0iNTAiIGZpbGw9IiM2NjdlZWEiLz48dGV4dCB4PSI1MCIgeT0iNTUiIGZvbnQtc2l6ZT0iNDAiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZpbGw9IndoaXRlIiBmb250LXdlaWdodD0iYm9sZCI+VTx0ZXh0Pjwvc3ZnPg==';
        }
        
        // Добавляем дополнительную информацию
        updateUserInfo(user);
        
    } else {
        // Если данные пользователя недоступны (запуск вне Telegram)
        handleNoTelegramData();
    }
}

// Функция для обновления информации о пользователе
function updateUserInfo(user) {
    const userDataDiv = document.querySelector('.user-data');
    
    // Очищаем предыдущие данные
    userDataDiv.innerHTML = `<p><strong>User ID:</strong> <span id="user-id">${user.id}</span></p>`;
    
    // Добавляем имя пользователя, если доступно
    if (user.first_name || user.last_name) {
        const name = `${user.first_name || ''} ${user.last_name || ''}`.trim();
        if (name) {
            userDataDiv.innerHTML += `<p><strong>Имя:</strong> <span>${name}</span></p>`;
        }
    }
    
    // Добавляем username, если доступен
    if (user.username) {
        userDataDiv.innerHTML += `<p><strong>Username:</strong> @${user.username}</p>`;
    }
    
    // Добавляем язык, если доступен
    if (user.language_code) {
        userDataDiv.innerHTML += `<p><strong>Язык:</strong> ${user.language_code}</p>`;
    }
}

// Функция для обработки случая, когда нет данных Telegram
function handleNoTelegramData() {
    document.getElementById('user-id').textContent = 'Недоступно (запустите через Telegram)';
    document.getElementById('user-avatar').src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iNTAiIGN5PSI1MCIgcj0iNTAiIGZpbGw9IiM2NjdlZWEiLz48dGV4dCB4PSI1MCIgeT0iNTUiIGZvbnQtc2l6ZT0iNDAiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZpbGw9IndoaXRlIiBmb250LXdlaWdodD0iYm9sZCI+WDx0ZXh0Pjwvc3ZnPg==';
    
    // Скрываем кнопку, так как она не будет работать вне Telegram
    tg.MainButton.hide();
}

// Функция для отладки - показывает все доступные данные
function debugData() {
    console.log('Telegram WebApp:', tg);
    console.log('Init Data:', tg.initDataUnsafe);
    console.log('Init Data String:', tg.initData);
    
    if (tg.initDataUnsafe?.user) {
        console.log('User Object:', tg.initDataUnsafe.user);
    }
}

// Ждем полной загрузки страницы
document.addEventListener('DOMContentLoaded', function() {
    // Проверяем, запущено ли в Telegram WebApp
    if (typeof window.Telegram !== 'undefined' && window.Telegram.WebApp) {
        initApp();
        debugData(); // Для отладки
    } else {
        // Если запущено вне Telegram
        handleNoTelegramData();
        console.log('Запущено вне Telegram WebApp');
    }
});

// Обработчик для кнопки "Назад" в Telegram
if (tg && tg.BackButton) {
    tg.BackButton.onClick(() => {
        tg.close();
    });
}