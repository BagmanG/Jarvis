<!doctype html>
<html lang="ru">

<head>
    <meta charset="utf-8" />
    <title></title>
    <link rel="stylesheet" href="style.css" />
    <script src="https://telegram.org/js/telegram-web-app.js"></script>

    <style>
    body {
        color: var(--tg-theme-text-color);
        background: var(--tg-theme-bg-color);
        display: flex;
        flex-direction: column;
        align-items: center;
        font-size: 18px;
    }

    .hint {
        color: var(--tg-theme-hint-color);
    }

    .link {
        color: var(--tg-theme-link-color);
    }

    .button {
        background: var(--tg-theme-button-color);
        color: var(--tg-theme-button-text-color);
        border: none;
        font-size: 18px;
    }

    .button:not(:last-child) {
        margin-bottom: 20px
    }

    #usercard {
        text-align: center;
    }
    </style>
</head>

<body>
    <div id="usercard">
        <!--Карта профиля, человека, который к нам обратился-->
    </div>

</body>
<script>
let tg = window.Telegram.WebApp;
tg.expand(); //расширяем на все окно  

let usercard = document.getElementById("usercard"); //получаем блок usercard 

let profName = document.createElement('p'); //создаем параграф
profName.innerText = `${tg.initDataUnsafe.user.first_name}
${tg.initDataUnsafe.user.last_name}
${tg.initDataUnsafe.user.username} (${tg.initDataUnsafe.user.language_code})`;
//выдем имя, "фамилию", через тире username и код языка
usercard.appendChild(profName); //добавляем 

let userid = document.createElement('p'); //создаем еще параграф 
userid.innerText = `${tg.initDataUnsafe.user.id}`; //показываем user_id
usercard.appendChild(userid); //добавляем
</script>

</html>