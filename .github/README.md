# php telegram api bot easy FULL

### PHP Compatibility  
This code has been tested and verified to work with PHP 8.1.  
It may also be compatible with PHP 5.6, though this is not guaranteed.  

БОТ НА ЕТАПІ ФІНАЛЬНОГО ДОКУМЕНТУВАННЯ  
ЗАРАЗ ВІН НЕ ГОТОВИЙ ДЛЯ ВИКОРИСТАННЯ  
ПЕРШИЙ РОБОЧИЙ КОМІТ ЗАПЛАНОВАНО НА 25.06.2024  

THE BOT IS IN THE STAGE OF FINAL DOCUMENTATION  
IT IS NOT READY FOR USE NOW  
FIRST WORK COMMITMENT SCHEDULED FOR 06/25/2024  

A comprehensive set of libraries, classes, and functions essential for building a functional procedural Telegram bot that does not rely on any external dependencies

Повний набір бібліотек, класів та функцій, необхідних для створення функціонального процедурного Telegram-бота, який вільний від будь яких зовнішніх залежностей

```php
// Connection base class
include_once $_SERVER['DOCUMENT_ROOT'] . "/lib/tgBot.php";
// Get the bot object
// Specify the token from BotFather and the bot name
$bot = new tgBot($token, $name);
$bot->liteModeOn();
```

Скористайтесь методом setWebhook API Telegram для встановлення webhook:  
**If you using the browser address bar for setWebhook**  
https://api.telegram.org/bot{token}/setWebhook?url=https://{example.com}/bots/hook.php?bot={botname}  
Specify the API key of your bot and the url path to your hook file  
Also specify the additional parameter ?bot={botname}. This will allow using one shared hook file for multiple bots  
Вкажіть API ключ вашого бота та url шлях до Вашого hook файлу  
Вкажіть також додатковий параметр ?bot={botname}. Це дозволить використовувати один спільний файл hook для декількох ботів  

**Example**  
https://api.telegram.org/bot1234567890:BBIJKL80DFMMOPQy-Yfj8Zq6Lm_78Vb2x3W/setWebhook?url=https://example.com/bots/hook.php?bot=botnameBot  

Приклади базового використання:
```php

// Ban, for example, until 2048 year (unix)
$bot->tempban($user, '2473401362');


// Send message
$bot->for($chat);
$bot->reply("Hello World");


```


Більше документації з приводу можливостей можна знайти в коментарях в lib/tgBot  
Приклад стартового, але функціонального та повністю робочого файлу **hook** можна знайти в 
**bots/hook.php**  
Цей стартовий файл **hook** гарно прокоментований і має багато готових рішень  

З коробки він:  
1. Вітається з користувачем
2. Зберігає надіслані йому файли
3. ...
4. ...


**Наприклад бот може**: 
```php
// Зберігати файли які хтось відправив боту
// Save files that someone sent to the bot
$bot->saveFile($data['message']['photo'][1], $pathtosave);

// ...

```


По замовчуванню кожен запит для зручного дебагінгу логується в текстові файли в папку bots/nameBot/  
Для відключення логування **вхідних** закоментуйте в bots/test.php рядок $bot->botLog(...);  
Для відключення логування **вихідних** закоментуйте в lib/tgBot.php у функції request() рядок $bot->botLog(...);  

Дуже рекомендую обов'язково відключити логування після дебагінгу  


**Бот може стати більш функціональним якщо Ви підключити його до MySQL бази данних та інсталюєте**  
Тоді бот зможе:
1. Зберігати унікальні для кожного боту налаштування (країну, адміністраторів, модераторів, контакти, сайт, ключовий tg канал тощо)
2. Пам'ятати користувачів та їх дані (контакти, піб, баланс, статус тощо)
3. Надавати користувачам різні ролі та відповідні можливості (адміністратор, модератор)
3. Надавати послуги або продавати товари
4. Пам'ятати останній важливий контекст діалогу (наприклад коли бот запитав номер телефону, він продовжує його чекати не дивлячись на деякі незначні метання та помилки користувача)
5. Автоматично додавати до постів на каналах кнопки чи посилання
6. Впроваджувати обмежений доступ


**Що саме встановлює бот в базу данних?**  
Перелік країн (table countries) з якими він може функціонально працювати. Наразі додано 11 країн (Україна,Great Britain,Грузія)  
Перелік областей та всіх міст вищезазначених країн (в яких проживає більше 3-7 тисяч). Це дозволяє з коробки повноцінно надавати послуги або продавати товари в цих містах.  


**Для цього треба:**  
1. В файлі MysqlConnection.php вказати дані для доступу до бази данних
2. Виконати install.php
3. Змінити constants в add.php (EMAIL,SERVICE_KEY,SERVICE_KEY_SOLD,SECURITY)


**Poadmap**  
1. Доступ до чату по підписці
2. Додавання послуг, підписок чи товарів
3. Сплата послуги, підписки чи товару по номеру картки чи на BTC використовуючи унікальний холодний автоматичний процесінг платежів (нода не потрібна)!
