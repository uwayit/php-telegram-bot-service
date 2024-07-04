# php telegram api bot easy FULL

### PHP Compatibility  
This code has been tested and verified to work with PHP 8.1.  
It may also be compatible with PHP 5.6, though this is not guaranteed.  

[README українською](../docs/README.ua.md)  

THE BOT IS IN THE STAGE OF FINAL DOCUMENTATION  
IT IS NOT READY FOR USE NOW  
FIRST WORK COMMITMENT SCHEDULED FOR 07/06/2024  

A complete set of libraries, classes, and functions required to create a functional procedural Telegram bot that is free from any external dependencies.  

**Key advantages:**  
1. Low entry barrier (junior friendly) due to simple and well-commented code  
2. Functionality and flexibility  

The bot you take out of the box after [installation](../docs/README.en.md#full-bot)  

**Attention!**  
Due to visual testing, you will NOT be able to grasp all the bot's capabilities.

**It can do MORE than just:**  

- Work with messages and commands;  
- Manage user and service provider (seller) profiles;  
- Flexibly manage service provision and product sale cities;  
- Verify phone numbers;  
- Work with buttons.

**Additionally, the bot:**
- Is protected from injections;  
- Has anti-spam protection and blacklist (mute and block);  
- Can remain silent in specific chats (even in private);  
- Can prevent sending certain messages too frequently;  
- Can maintain context;  
- Has a test mode that switches to SQL tables with the prefix *_test;  
- Selects the response language based on the bot's or user's country;  
- Can be part of a website (e.g., verify phone numbers for the site);  
- Can temporarily hide a city if it is under occupation or in a disaster zone;  
- Automatically redirects messages to a new chat ID and updates the chat ID in the database if a group becomes a - supergroup (thus changing the group's chat ID);  
- Edits messages in channels (e.g., adding buttons);  
- Allows creating user roles in chats (admin, moderator, etc.);  
- Sends error and exception notifications to Slack or Telegram to the developer;  
- Logs all incoming data and responses from the TG API for easier debugging;  
- Can be used for dozens of different functional bots with a single hook;  
- in public chats, the bot automatically displays intrusive notifications about those who want to join the chat or have seen someone;  

**Additionally, it can easily moderate public chats:**
- Automatically deletes intrusive messages about users joining or leaving the chat;  
- Instantly removes spam messages (with links);  
- Instantly deletes messages containing, for example, obscene language (from your list);  
- The bot can notify users privately about rule violations and keep track of the number and frequency of these violations. Persistent offenders can be blocked by the bot at both the Telegram API level and the system level (for all chats managed by the bot).  

A sample starting and fully functional **hook** file can be found in  
**bots/base_hook.php**  
The hook is thoroughly commented, explaining each line.  

**If you ONLY need a class** that works with the Telegram API and you will develop the bot's functionality yourself, all you need is one file:  
**/lib/tgBot.php**

**Examples of using the basic class tgBot:**  
```php
// Connection base class
include_once $_SERVER['DOCUMENT_ROOT'] . "/lib/tgBot.php";
// Get the bot object
// Вкажіть токен від botfather та ім’я бота
$bot = new tgBot($token, $name);

// Якщо вам ЛИШЕ потрібен клас, то потрібно при кожному отриманні об'єкта активувати
$bot->liteModeOn();

// АБО ЩОБ ЦЬОГО НЕ РОБИТИ КОЖЕН РАЗ, ТО В САМОМУ КЛАСІ (!) tgBot:
// public $FullMode = false;

// Бан користувача, наприклад, до 2048 року (unix)
$bot->tempban($user, '2473401362');

// chat_id|from_id отримувача повідомлення
$bot->for($chat_id);

// Видаляємо підготовлену клавіатуру та всі попередні keyboard кнопки що відображаються користувачу
$bot->deleteKeyboard();
// Визначаємо тип нової клавіатури
$bot->setupTypeKeyboard('inline'); // or keyboard or remove_keyboard
// Додаємо кнопку
$bot->insertButton([['text' => "підтвердити", 'callback_data' => 'make_ok']]);

/* 
Раніше додана клавіатура видаляється автоматично після відправки повідомлення,
тож для нового повідомлення її потрібно вказувати знову або якщо потрібно зберегти клавіатуру, 
то ДО відправки КОЖНОГО повідомлення можна вказати: 
*/
$bot->clearKeyboardAfterSend = false;
// Send message
// Після відправки ми зможемо розібрати масив $response з відповідю
$response = $bot->reply("Hello World");
```

More documentation regarding the class's capabilities can be found in the comments directly in lib/tgBot  

## Full bot
**The bot can become infinitely functional if connected to MySQL and slightly configured**  


**To install and obtain the full bot, you need to:**
1. Download the entire repository.
2. Specify the database access details in the root file MysqlConnection.php.
3. Change the constants to your preference in load.php (EMAIL, SERVICE_KEY, SERVICE_KEY_SOLD, SECURITY, SLACK, $SlackSet, tgdevId).
4. Execute install.php and follow its instructions.
5. Set webhook **bots/base_hook.php** [faq](../docs/setWebhook.en.md)  
6. Pay attention to the restrictions and rules in the root and other .htaccess files.

**Attention!**  
Up-to-date and more detailed instructions are available in [Ukrainian](../docs/README.ua.md)  