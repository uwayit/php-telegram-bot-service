# php-telegram-bot-service
Compatible php 8.1

A comprehensive set of libraries, classes, and functions essential for building a functional procedural Telegram bot that does not rely on any external dependencies

Повний набір бібліотек, класів та функцій, необхідних для створення функціонального процедурного Telegram-бота, який не залежить від жодних зовнішніх залежностей

```php
// Connection base class
include_once $_SERVER['DOCUMENT_ROOT'] . "/lib/tgBot.php";
// Get the bot object
// Specify the token from BotFather and the bot name
$bot = new tgBot($token, $name);

// Send message
$bot->for($chat);
// $bot->forCallBack($cqid);

// Ban, for example, until 2048 year (unix)
$bot->tempban($user, '2473401362');

```

Якщо Ви хочете використовувати не тільки клас tgBot, а одразу мати функціональний бот, тоді Вам треба:
1. В файлі MysqlConnection.php вказати дані для доступу до бази данних
2. Скористайтесь методом setWebhook API Telegram для встановлення webhook

**Example using the browser address bar**
Pass your bot's API key and url hook file
Pass also the additional ID ?bot={$botname} if you want to create one single hook file for several bots
Передайте API ключ вашого бота та url hook файлу
Передайте також додатковий ідентифікатор ?bot={$botname} якщо хочете використовувати один спільний файл hook для декількох ботів

https://api.telegram.org/bot{$token}/setWebhook?url=https://{$host}/bots/hook.php?bot={$botname}

