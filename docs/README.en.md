# php telegram api bot easy FULL

### PHP Compatibility  
This code has been tested and verified to work with PHP 8.1.  
It may also be compatible with PHP 5.6, though this is not guaranteed.  

[README українською](../docs/README.ua.md)  

THE BOT IS IN THE STAGE OF FINAL DOCUMENTATION  
IT IS NOT READY FOR USE NOW  
FIRST WORK COMMITMENT SCHEDULED FOR 07/06/2024  

A comprehensive set of libraries, classes, and functions essential for building a functional procedural Telegram bot that does not rely on any external dependencies

```php
// Connection base class
include_once $_SERVER['DOCUMENT_ROOT'] . "/lib/tgBot.php";
// Get the bot object
// Specify the token from BotFather and the bot name
$bot = new tgBot($token, $name);
```

**If you ONLY need a class**  that works with Telegram bot api, and you will develop the functionality of the bot yourself, then all you need is one file:  

/lib/tgBot.php  

```php
// If you ONLY need the class, then you need to activate it every time you get the object
$bot->liteModeOn();

// OR TO NOT DO THIS EACH TIME, YOU CAN CHANGE IN THE CLASS ITSELF  
public $FullMode = false;
```

**Examples of basic usage:**  
```php

// Ban, for example, until 2048 year (unix)
$bot->tempban($user, '2473401362');

// 


// chat_id|from_id of the recipient of the message
$bot->for($chat_id);
// Previously added keyboard is removed automatically after sending a message
// so for a new message it must be specified again or if you want to save the keyboard
// BEFORE sending each message you can specify
$bot->clearKeyboardAfterSend = false;
// Send message
// After sending, we can parse the array with the response
$response = $bot->reply("Hello World");

```


By default, each request is logged to text files in the bots/nameBot/ folder for convenient debugging
To disable **outgoing** logging, comment the $bot->botLog(...) line in the request() function in lib/tgBot.php;
To disable **incoming** logging, comment the $bot->botLog(...) line in the bots/test.php hook file;

I highly recommend that you disable logging after debugging


More documentation on the class' capabilities can be found in the comments in lib/tgBot


An example of a starter, but functional and fully working **hook** file can be found in
**bots/hook.php**  
This starter file **hook** is well commented and has many ready-made solutions  

**Наприклад бот може**: 
```php
// Save files that someone sent to a bot or public chat
$bot->saveFile($data['message']['photo'][1], $pathtosave);

// ...

```
