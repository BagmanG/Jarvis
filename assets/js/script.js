// Инициализация Telegram Web App
const tg = window.Telegram.WebApp;

// Основная функция инициализации
function initApp() {
    // Показываем главную кнопку (опционально)
    tg.MainButton.setText("Закрыть");
    tg.MainButton.show();
    tg.MainButton.onClick(() => tg.close());
    
    // Получаем данные пользователя
    const user = tg.initDataUnsafe.user;
    
    if (user) {
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
        
        // Добавляем имя пользователя, если доступно
        if (user.first_name || user.last_name) {
            const name = `${user.first_name || ''} ${user.last_name || ''}`.trim();
            if (name) {
                const userDataDiv = document.querySelector('.user-data');
                userDataDiv.innerHTML += `<p><strong>Имя:</strong> <span>${name}</span></p>`;
            }
        }
        
        // Добавляем username, если доступен
        if (user.username) {
            const userDataDiv = document.querySelector('.user-data');
            userDataDiv.innerHTML += `<p><strong>Username:</strong> @${user.username}</p>`;
        }
    } else {
        // Если данные пользователя недоступны
        document.getElementById('user-id').textContent = 'Недоступно';
        document.getElementById('user-avatar').src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iNTAiIGN5PSI1MCIgcj0iNTAiIGZpbGw9IiM2NjdlZWEiLz48dGV4dCB4PSI1MCIgeT0iNTUiIGZvbnQtc2l6ZT0iNDAiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZpbGw9IndoaXRlIiBmb250LXdlaWdodD0iYm9sZCI+WDx0ZXh0Pjwvc3ZnPg==';
    }
}

// Ждем полной загрузки страницы
document.addEventListener('DOMContentLoaded', initApp);

// Обработчик для кнопки "Назад" в Telegram
tg.BackButton.onClick(() => {
    tg.close();
});