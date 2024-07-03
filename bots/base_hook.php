<?php
// First, you must setWebhook
// https://api.telegram.org/bot{$botinfo['token']}/setWebhook?url=https://{$host}/bots/hook.php?bot={$botinfo['name']}

$dirt = file_get_contents('php://input'); // весь input заганяємо у $data
$dirt = json_decode($dirt, true);         // декодуємо json дані в масив
if (empty($dirt)) {
  exit('empty input data');
  }

// Підключаємо бібліотеки
require_once $_SERVER['DOCUMENT_ROOT'] . "/load.php";
// Виходимо, якщо mysql база недоступна
if (!core::$db) {
  exit('error connect or install');
  }

// Чистимо вхідні дані аби запобігти:
// SQL-ін'єкціям, XSS-атакам та іншим загрозам, пов'язаним з необробленими вхідними даними
$data = core::safetyCleanArray($dirt);
// Трохи далі, система рахує кількість текстових запитів до бота 
// (натискання на кнопки не рахуються)
// Якщо кількість запитів перевищить 50 за добу (налаштувати кількість можна в load::adequacyLimit):
// Тоді користувача буде обмежено в можливості надсилати подальші запити


// Оголошуємо деякі змінні
$text = false;          // Текст повідомлення що поступив (може бути пустим, якщо прийшло не текстове)
$reply_author = false;  // Хто автор повідомлення на яке відповідь (якщо це відповідь)
$reply_message = false; // Текст повідомлення на яке ми отримали відповідь
$callback_data = false; // Інлайн кнопка по якій відбувся клік
$message_id = false;    // ID повідомлення
$username = "";         // Юзернейм може бути пустим, якщо в юзера він пустий
$user = false;          // Якщо дію хтось ініціював, то хто? Кому відповідати на повідомлення чи в особисті
$chat = false;          // Чат в якому все відбувається
$allowbot = false;      // Чи дозволено боту бути в чаті? В приватних чатах не актуально
$client = false;        // Застаріло, потроху треба видалити
$cutt = false;          // Первинна команда-дія що передається кліком по іnline кнопці
$cqid = false;          // ID callback запиту на яке треба дати відповідь (якщо це callback запит)
$user_key = false;      // Допомогає ідентифікувати, авторизувати користувача
$partner = false;       // Повна анкета користувача сайту (якщо бот працює в зв'язці з сайтом)
$bundle = false;        // Базова анкета користувача боту
$isAdmin = false;       // Чи користувач адмін? Адмін має повну владу над ботом
$isUsher = false;       // Чи користувач модератор? Модератор - має обмежену владу яку йому надає адмін
$userbot = false;       // Чи є ініціатор запиту до хуку - ботом?
$chattype = false;      // Це приватний чат чи публічний?
$groupsetup = false;    // Налаштування публічного чату в якому відбувається діалог

if (!empty($_GET['bot'])) {
  $nameBot = $_GET['bot'];
  } else {
  // виходимо
  exit('webhook installed incorrectly<br><br>
  <a href="https://github.com/uwayit/php-telegram-bot-service/blob/main/docs/setWebhook.en.md" target="_blank">need get botname</a>');
  }

// Шукаємо інфу про бота
$botinfo = tg::getBotInfo($nameBot, 'work');
// Виходимо якщо не знайшли
if ($botinfo == false) {
  exit('The row with information about this BOT is missing in the database');
  }
// Вказуємо тип бота
$st = $botinfo["type"];
/*
Якщо $st = bot - згідно з задумом це означає, що цей бот не є частиною якогось сайту і наразі працює відокремлено
Всі боті як встановлено з гітхаб репозиторію по замовчуванню НЕ Є частитою сайту
Коли бот є частиною сайту, то до прикладу він може мати веб інтерфейс або може взаємодіяти з базою користувачів 
ТАКОЖ, в бота можуть бути різні сценарії поведінки які в мене доприкладу залежать від типу сайту, частиною яокого є бот
Тож я використовую зміну $st яка допомогає hook файлу розуміти за яким сценарієм опрацьовувати запити
*/
if ($st == 'bot') {
  // Отримуємо додаткову інформацію (налаштування) по цьому боту
  $botinfodop = tg::getBotInfoDop($botinfo["id"]);
  }

// !!! НАРАЗІ ФУНКЦІОНАЛ $botinfo["type"] == bot
// !!! налаштований на те, щоб працювати лише з однією конкретною країною
// !!! тому $botinfo["country"] має містити лише одну країну
// Змінювати це поки що не планую
// Поки простіше як на мене для кожної країни мати окремий бот
// Країна, мова якої використовується ботом по замовчуванню для неавторизованих користувачів
$aco = $botinfo["country"];
$lg = $botinfo["country"];
// Базово використовуємо локаль бота
// Але нижче, можна буде отримувати локаль клієнта
$locale = core::getLocal($aco);
// Отримуємо деталі по країні
// в першу чергу для отримання переліку проблемних регіонів
$setAco = core::getSetupByCountry($aco);
/*
Формуємо перелік проблемних регіонів 
В яких йдуть бойові дії або відбувається щось інше що заважає доставці туди товару або наданю там послуг
Цей список erReg можна постійно мануально коригувати в базі в таблиці countries включаючи туди регіони (області та міста)
В будь яких сценаріях нижче проблемні регіони/міста (назва має повністю співпадати з цільовим з таблиці regions) 
автоматично виключаються зі списків які пропонуються користувачу
*/
$listProblemRegion = false;
if (!empty($setAco['erReg'])) {
  $listproreg = explode(",", $setAco['erReg']);
  $lpr = array_map('trim', $listproreg);
  $listProblemRegion = $lpr;
  }

// Сайт на якому (від імені якого) працює бот
$host = $botinfo["site"];
// Якщо сайт не вказано чомусь
// Отримуємо сайт на якому знаходимося
// Для мене це прийнятно, але не всім це може бути зручно
// Бо сайт ми отримуємо в тому числі і для того щоб від його цмені відправляти листи
// Тож якщо Ви хостите в себе багато клієнтських ботів, то 
// можливо Ви не хочете, щоб від Вашого хосту відправлялись клієнтські листи
if (empty($host)) {
  $host = core::getCurrentDomainByServerHttpHost();
  }

// Шукаємо сайт в базі. Він обов'язково має бути прописаний в таблиці sites
// Цей массив потрібен як мінімум:
// для визначення режиму роботи та для відправки листів на емаіл
$site = core::getSiteByDomain($host);
if ($site == false) {
  exit('The row with information about this SITE is missing in the database');
  }

// Режим роботи сервісу WORK|TEST
// В lib/load можна для тестового режиму вказати дублікати всіх або окремих таблиць
// Та перемикатись ніж ними в будь який момент встановивши
// load::setMode("TEST");
// Це може бути корисно, якщо треба наприклад
// мануально протестувати як працює наприклад скрипт що розсилає по базі клієнтів повідомлення
load::setMode($site["MODE"]);

// Get the bot object
$bot = new tgBot($botinfo['token'], $botinfo['name'], $host);
if (!core::$db) {
  // Якщо використовується простий бот без підключення до бази данних
  // В такому випадку відключаємо увесь функціонал який прямо залежить від бази данних
  $bot->liteModeOn();
  }

// Отримуємо chat_id адмінів боту
$admins = explode(",", $botinfo['admins']);

// Ми можемо автоматично надсилати повідомлення 
// про всі помилки та виключення hook які відбуваються далі по коду
// Помилки які відбуваються вище також можна налаштувати через load.php 
if (!empty($admins[0])) {
  // Я поєдную в собі функції адміна бота та розробника, тому не розділяю тут ролі
// Але якщо необхідно, ви можете створити окремий стовпець де зберігатимете chat_id розробників
  $devAdmin = $admins[0];
  // Обробник помилок що надсилає інформацію про помилки в телеграм бот адміна
  $errorHandler = new ErrorHandler(core::$db, 'tg', $bot, $devAdmin);
  $errorHandler->register();
  }

// Визначаємо з якого мила надсилатимемо листи на електронну пошту
// Лист потрібен наприклад якщо ми запитуємо в користувача емаіл і треба надіслати код перевірки емаіл
if (empty($botinfo['info_email'])) {
  $infoemail = $site["info_email"];
  } else {
  $infoemail = $botinfo["info_email"];
  }

// Стартове повідомлення яке використовується якщо/коли треба вказати юзеру
// що перш ніж зареєструватись в боті, треба спочатку зареєструватись на сайті
if ($bot->FullMode == true and $st != 'bot') {
  $textNeedRegSite = tg::textNeedRegSite($st, $lg, $host);
  }

// Задаємо базові налаштування автопідміни якорів в повідомленнях
// Це для мене вже рудімент, бо 
// юзав коли тягав тексти для повідомлень з бази, а отже в текстах не було змінних
// Але функціонал лишаю про всяк
// Перед відправкою повідомлення в якому треба зробити автозаміну 
// додаємо рядок $message = str_replace($billet, $repl, $message);
$billet[0] = "{host}";
$repl[0] = $host;

// Для логування всіх вхідних в одному файлі
// !!! треба не забувати вимикати після дебагінгу бо це мусорить сильно в папку bots/$botinfo['name']/
$bot->botLog($botinfo['name'], 'all_in', $data, 'debug');

// ** Починаємо розбирати вхідний масив

// Якщо це повідомлення
if (!empty($data['message'])) {
  // Странные ситуации нужно отлавливать и анализировать логи
  // Якщо чат в якому іде діалог не передано нам 
  if (empty($data['message']['chat']['id'])) {
    // Зараз просто логую 
    $bot->botLog($botinfo['name'], 'error_chat_id', $data, 'uvaga');
    exit('end');
    }
  // Заповнюємо змінні
  $chat = $data['message']['chat']['id'];
  $chattype = $data['message']['chat']['type'];
  $user = $data['message']['from']['id'];
  $userbot = $data['message']['from']['is_bot'];
  $first_name = $data['message']['from']['first_name'];
  $message_id = $data['message']['message_id'];
  if (!empty($data['message']['from']['username'])) {
    $username = $data['message']['from']['username'];
    }
  // получаем ТЕКСТОВОЕ сообщение, что юзер отправил боту и 
  // заполняем переменные для дальнейшего использования
  if (!empty($data['message']['text'])) {
    $text = trim($data['message']['text']);
    }
  if (!empty($data['message']['reply_to_message']['from']['id'])) {
    $reply_author = $data['message']['reply_to_message']['from']['id'];
    }
  if (!empty($data['message']['reply_to_message']['message_id'])) {
    $reply_message = $data['message']['reply_to_message']['message_id'];
    }
  }
// Якщо це клік по кнопці
if (!empty($data['callback_query'])) {
  // Чат де все відбувається
  $chat = $data['callback_query']['message']['chat']['id'];

  $chattype = $data['callback_query']['message']['chat']['type'];
  $first_name = $data['callback_query']['from']['first_name'];
  if (!empty($data['callback_query']['from']['username'])) {
    $username = $data['callback_query']['from']['username'];
    }
  $user = $data['callback_query']['from']['id'];
  $cqid = $data['callback_query']['id'];
  $message_id = $data['callback_query']['message']['message_id'];

  // Текст ообщения на которое пришёл клик по кнопке callback
  // Інколи чомусь буває пустим, поки не розібрався чому
  if (!empty($data['callback_query']['message']['text'])) {
    $text = $data['callback_query']['message']['text'];
    } else {
    $text = 'empty';
    }
  $callback_data = $data['callback_query']['data'];
  $bot->forCallBack($cqid);
  }
// Встановлюємо отримувача відповіді (по замовчуванню)
// В першу чергу вважаємо, то відповідаємо в той же чат в якому було задано повідомлення
// Але якщо це публічний чат то відповідаємо в приватні
if ($chattype != 'private') {
  $groupsetup = tg::getGroup($chat, $chattype);
  $allowbot = core::inar($user, $groupsetup['bots']);
  $bot->for($chat);
  } else {
  $bot->for($user);
  }
// Повідомлення про нового юзера в чаті
if (
  empty($user) and isset($data['my_chat_member']['chat']['id']) and
  $chattype != 'private' and $chattype != 'channel'
) {

  $chat = $data['my_chat_member']['chat']['id'];
  $user = $data['my_chat_member']['chat']['id'];
  // Встановлюємо публічний чат куди будемо писати
  $bot->for($chat);


  // Далі
  // Юзер $data['my_chat_member']['from']['id'] 
  // додав цього бота в ЧАТ
  if (!empty($data['my_chat_member']['new_chat_member']) and $data['my_chat_member']['new_chat_member']['user']['id'] == $botinfo['from_id']) {
    //$bot->botLog($botinfo['name'],$chat,$data,'in_new_chat');

    // Що робимо одразу після того як бот опинився в чаті
    // Бот може привітатись, росказати про свої можливості і нагадати про необхідність видати йому права адміна

    // Або виконати якісь дії одразу після того як отримає права адміна
    if ($data['my_chat_member']['new_chat_member']['status'] == 'administrator') {

      }
    exit('done');
    }
  }


// Якщо користувач є у списку адмінів цього робота
// Видаємо йому адмін права
if (!empty($botinfo['admins'])) {
  $isAdmin = core::inar($user, $botinfo['admins']);
  }
// Аналогічно для помічників, модераторів, білетерів
if (!empty($botinfo['usher'])) {
  $isUsher = core::inar($user, $botinfo['usher']);
  }

// Я передбачив в системі лише одну додаткову роль "нижчу" за адміна
// Якщо вам треба мати більше різноманітних ролей одночасно (наприклад роль розробника), тоді:
// додайте для них додаткові стовбці в таблицю tg_bots
// Отримуйте їх роль аналогічно варіантам вище



// Якщо це пост на каналі
if (!empty($data['channel_post'])) {
  $message_id = $data['channel_post']['message_id'];
  $chat = $data['channel_post']['chat']['id'];
  // Можемо відредагувати пост, наприклад додати кнопки
  // за допомогою $bot->editMessageText(...);

  exit('ok');
  }

// Якщо ми не знайшли $chat та $user, значить ми чогось ще не навчили бота
// Логуємо та/або відправляємо alert в slack та виходимо
if (empty($chat) and empty($user)) {
  $bot->botLog($botinfo['name'], 'emptyUserChat', $data, 'hook');
  exit('ok');
  }


// Перевіряємо чи не спамить користувач
$antiSpamData = tg::TelegramSpamProtect($isAdmin, $user, $host, $lg, 'test');
if ($antiSpamData['count'] >= load::adequacyLimit) {
  $stopspamtext = tg::TelegramSpamProtect($isAdmin, $user, $host, $lg, false, $antiSpamData);
  if (!empty($stopspamtext)) {
    exit('stopspam');
    }
  }



// $botinfo['some'] - це перелік чатів, у відповідь на запити яких саме цей бот просто мовчить як риба
// Інколи це може бути корисно, тож просто додаємо такий чат через кому в $botinfo['some']
// Функція при таких чатах завершує виконання - exit('ok');
tg::silentInChat($botinfo['some'], $chat);


// Шукаємо користувача в базі
// Якщо в базі немає, то трохи нижче будемо одразу додавати його в базу
$bundle = tg::SearchTgBundle($user, $botinfo['name']);

// Непускаємо далі тих, хто в чорному списку, тобто мають $bundle['status'] == 'black'
// Тобто можна будь який аккаунт помічати статусом black (в таблиці tg_bundle) 
// і бот автоматично його заблокує в особистих чатах і усюди де цей бот адмін (це треба врахувати перед видачою black)
if (!empty($bundle) and !empty($bundle['status']) and $bundle['status'] == 'black') {
  $bot->tempban($user); // Блокуємо автоматично до 2048 року
  exit('ok');
  }



// Якщо цей бот вперше зіштовхнувся з данним користувачем
// Вносимо все що знаємо по ньому в базу данних
if (empty($bundle) and !empty($user)) {
  $advanced = false;
  // Формуємо первиний массив з його данними
  $bundle = [
    'from_id' => $user,
    'bot' => $botinfo['name'],
    'username' => $username,
    'phone' => '',
    'email' => '',
    'kod' => '',
    'region' => '',
    'city' => '',
    'mst' => '',
    'role' => '',
    'step' => '',
    'country' => '',
    'dopphone' => '',
    'date_phone' => '0000-00-00'
  ];

  // Записуємо все що маємо в базу данних
  core::$db->query("INSERT INTO `" . load::$tg_bundle . "` SET `from_id`='$user',`username`='$username', `bot`='{$botinfo['name']}', `date_reg` = NOW()" . $advanced);
  $bundle['id'] = mysqli_insert_id(core::$db);

  // В СИТУАЦІЇ КОЛИ СПОЧАТКУ ЗАЯВКА НА САЙТІ ПОТІМ звернення до БОТу
  // Надсилаємо підготовлений вище текст textNeedRegSite
  // та выходимо
  if ($st == 'credit' and empty($partner) and $chattype == 'private') {
    $bot->hello($textNeedRegSite);
    exit('hello');
    }

  }






// Задаємо додаткові змінні
// Задаємо країну (переклад) виходячи з налаштувань користувача
if (!empty($bundle['lg'])) {
  $lg = $bundle['lg'];
  $textNeedRegSite = tg::textNeedRegSite($st, $lg, $host);
  }

// Формуємо популярні зміні
$ua = "ВЕРІФІКУВАТИ НОМЕР ТЕЛЕФОНУ";
$ru = "ВЕРИФИЦИРОВАТЬ НОМЕР ТЕЛЕФОНА";
$en = "VERIFY PHONE NUMBER";
$sharecontact = mova::lg($lg, $ua, $ru, $en);
$ua = "ПОВЕРНУТИСЬ НА ГОЛОВНУ";
$ru = "ВЕРНУТЬСЯ НА ГЛАВНУЮ";
$en = "BACK TO THE MAIN PAGE";
$gotostart = mova::lg($lg, $ua, $ru, $en);


// Якщо клієнт раніше відписався, підписуємо його знову
if (!empty($bundle['mst']) and $bundle['mst'] == 'stop') {
  tg::UpdateTgBundle($user, $bundle['email'], $st, $nameBot);
  }

// Якщо це надавач послуг, отримуємо додаткову інформацію про нього в массив
if (!empty($bundle['role']) and $bundle['role'] == 'service') {
  $service = tg::getService($bundle['id']);
  // В данному випадку треба повідомити АДМІНА, ЩО якийсь надавач послуг НЕ МАЄ service профілю
  // Наразі, всі профілі надавачів послуг вносяться через інтерфейс phpMyAdmin
  if (empty($service)) {
    $bot->reply("Ваш профіль неактивований адміном...\r\n---\r\nЦе випадкова помилка і ми її виправимо!\r\nПовідомте нам про неї на:\r\n" . $botinfo['manager_mail'] . "\r\n---\r\nПриносимо вибачення за незручності!");
    exit('ok');
    }

  }


// Якщо кліент змінив username або його просто немає в нас в базі
// Коригуємо бо імена нам потрібні
tg::UpdateUserName($username, $bundle['id'], $bundle['username']);



// ЯКЩО ПЕРЕД НАМИ КАНАЛ
if ($chattype == 'channel') {
  // Якщо ми хочемо відредагувати повідомлення в канал
  // Наприклад додавати до повідомлення в каналі - кнопки
  // То робимо це тут

  // Після виходимо
  exit('ok');
  }


if ($chattype == 'group' or $chattype == 'supergroup') {
  // Якщо ЦЕ до чату приеднався новий учасник
// або
// Якщо видалився учасник
  if (isset($data['message']['new_chat_members']) or isset($data['message']['left_chat_member'])) {

    // Видаляємо мусорне (ІМХО) повідомлення про факт доєднання або видалення учасника
    $bot->deleteMessage($message_id);

    // Наразі виходимо, хоча можемо ще щось робити 
    // для новенького, писати йому в особисті наприклад
    exit('done');
    }
  // Якщо група стала супергрупою, треба автоматично це виявляти і змінювати ідентифікатор групи в базі
  if (isset($data['message']['migrate_to_chat_id']) and isset($data['message']['migrate_from_chat_id'])) {




    }
  }

// Як бути якщо користувач надіслав боту фото
if (!empty($data['message']['photo'])) {
  // Якщо це повідомлення до каналу або публічного чату, то бот на даному етапі ігнорує файли
  if ($chattype != 'private') {
    exit('ok');
    }

  // Якщо повідомлення в особисті
  // Я цей функцііонал не використовую, тому нічого не розробляв

  $ua = "На даний момент бот не запитував у Вас ніяких фото або скріншотів, а тому цей файл буде проігноровано.";
  $ru = "В данный момент бот НЕ запрашивал у Вас никаких фото или скриншотов, а потому данный файл будет проигнорирован.";
  $en = "At the moment, the bot has NOT requested any photos or screenshots from you, and therefore this file will be ignored.";
  $response = mova::lg($lg, $ua, $ru, $en);
  $bot->reply($response);
  exit('end');

  }

// Якщо юзер натиснув на кнопку
if ($callback_data) {
  // Повідомлення (реакцію на клік) будемо відправляти користувачу в особисті
  $bot->for($user);
  // Текст для кнопки відміни дії
  $ua = "СКАСУВАННЯ";
  $ru = "ОТМЕНА";
  $en = "CANCEL";
  $regtextno = mova::lg($lg, $ua, $ru, $en);


  //  Опрацьовуємо всі кнопки які пов'язані з надавачем послуг
  if (stristr($callback_data, 'rear_') !== false) {
    $rear = explode("_", $callback_data);
    array_shift($rear); // Видаляємо з масива перший елемент - rear
    $bot->answerCallback('');
    $response = false;

    // яКЩО ЦЕ НАДАВАч послуг, але його розширений профіль 
    // (який наразі в sql таблицю tg_service вносить вручну) відсутній в системі
    if ($rear[0] == 'pluscityservice' and $bundle['role'] == 'service') {
      if (empty($service)) {
        $bot->reply("Ваш профіль неактивований адміном...\r\n---\r\nЦе випадкова помилка і ми її виправимо!\r\nПовідомте нам про неї на:\r\n" . $botinfo['manager_mail'] . "\r\n---\r\nПриносимо вибачення за незручності!");
        exit('ok');
        }

      // Шукаємо скільки міст він додав раніше
      if (!empty($service['city_id'])) {
        $howcity = substr_count($service['city_id'], ',') + 1;
        } else {
        $howcity = 0;
        }

      // Додавати можна НЕ більше 7
      if ($howcity < 7) {
        // Виводимо перелік доданих раніше якщо вони є
        if ($howcity >= 1) {
          $query = "SELECT * FROM `service_city` WHERE id IN (" . $service['city_id'] . ")";
          $result = core::$db->query($query);
          $listCityService = [];
          $lcs = "";
          while ($row = $result->fetch_assoc()) {
            $arrCityService[$row['id']] = $row;
            $lcs .= $row['city'] . ", " . $row['region'] . "\r\n";
            }
          $kiko = 7 - $howcity;
          if ($kiko <= 0) {
            $kiko = "0";
            }
          }
        $query = "SELECT `id`,`region` FROM `regions` WHERE `country` = '{$botinfo['country']}'";
        $result = core::$db->query($query);
        $i = 0;
        // В країні завжди точно є якісь області
        while ($row = $result->fetch_assoc()) {
          $i++;
          $regions[$i]['id'] = $row['id'];
          $regions[$i]['name'] = $row['region'];
          }
        $result->close();
        // Якщо єлементів > 7, то клавіатура буде укомпактнена
        if ($i > 7) {
          $utockilk = "натисніть на букви з яких починається назва ОБЛАСТІ";
          } else {
          $utockilk = "оберіть ОБЛАСТЬ";
          }
        $keyboard = tg::makeAbcKeyboard($regions, 'region', false, $locale);
        $bot->insertKeyboard($keyboard);


        if ($howcity >= 1) {
          $response = "*Ви додали наступні міста:*\r\n" . $lcs . "\r\nМожна додати ще: " . $kiko . "\r\n-----\r\nЩоб вказати нове місто," . $utockilk . " в якій ви можете фізично надавати послуги:";
          } else { // Якщо раніше нічого не додано
          $response = "Ви ще не вказали жодного міста!\r\n-----\r\nСистема буде пропонувати Вас клієнтам лише після того як Ви вкажете хоча б одне!\r\n-----\r\nЩоб вказати перше місто," . $utockilk . " в якій ви можете фізично надавати послуги:";
          }
        } else {
        // Якщо додано більше за ліміт
        $response = "Ви додали максимальну кількість міст!";
        $bot->insertButton([['text' => "видалити місто надання послуг", 'callback_data' => 'citydel_list']]);
        }

      $bot->reply($dodano);
      exit('ok');

      }

    if ($rear[0] == 'addserviceok') {
      $bot->deleteMessage($message_id - 1);
      $bot->deleteMessage($message_id);
      // Встановлюємо статус кандидата
      // Поки так фіксую, можливо потім придумаю кращий варіант
      // Можливо цей користувач просто грається з формою, але в нескладних формах це не важливо
      core::$db->query("UPDATE `" . load::$tg_bundle . "` SET `role`= 'candidate' WHERE `id`='{$bundle['id']}'");
      $bundle['role'] = 'candidate';
      if ($bundle['phone'] == '') {
        $ppph = "\r\n---\r\nПісля цього ПІДТВЕРДІТЬ НОМЕР ТЕЛЕФОНА";
        } else {
        $ppph = "";
        }

      $bot->setupTypeKeyboard('keyboard');
      $bot->insertButton([['text' => $sharecontact, 'request_contact' => true]]);
      // Звичайно можна розробити складну послідовну форму в tg
      // Але я поки пропоную використовувати спрощений варіант
      $bot->reply("Щоб доєднатись у якості " . $botinfodop['service'] . ":\r\n---\r\n[ЗАПОВНІТЬ ГУГЛ ФОРМУ](" . $botinfodop['oferservice'] . ")" . $ppph . "\r\n---\r\nУ разі корректно виконаних інструкцій, ми зареєструємо Вас в системі якомога швидше!");
      exit('ok');
      }



    $bot->insertButton([["text" => $gotostart, "callback_data" => "/start"]]); // Повернутись назад



    if ($rear[0] == 'addserviceno') {// В будь якому випадку обнулюємо статус адвоката
      $bot->deleteMessage($message_id - 1);
      $bot->deleteMessage($message_id);
      core::$db->query("UPDATE `" . load::$tg_bundle . "` SET `role`= '' WHERE `id`='{$bundle['id']}'");
      $bot->reply("Ваш статус надавача послуг в нашій системі - видалено!");
      exit('ok');
      }


    // Очищуємо дані про регіон, надаючи завдяки цьомукористувачу можливість його змінити далі по коду
    if ($rear[0] == 'editregion') {
      // Перш ніж змінити регіон, треба перевірити
      // чи немає незакритого ордера замовлення надавача послуг
      // Якщо є незакритий ордер, то треба вимагати спочатку закрити ордер 
      // і лише після ми зможемо надати можливість обрати новий регіон
      $seorder = tg::SearchServiceOrder($user);
      $bundle['region'] = '';
      $bundle['city'] = '';
      $callback_data = "/reg";
      $text = "/reg";
      goto litleskip;
      }


    if ($rear[0] == 'editphone') {
      $bot->clearkb();
      $bot->setupTypeKeyboard('keyboard');
      $bot->insertButton([['text' => $sharecontact, 'request_contact' => true]]);
      $response = "Для зміни телефону натисніть нижче на кнопку " . $sharecontact;
      $bot->reply($response);
      exit('ok');
      }



    // Якщо є відповідь яка ще не описана вище, то видаємо заглушку
    if (empty($response)) {
      $response = "Упс... Бот активно розробляється, але цей етап, на жаль, ще не описано в системі...";
      }
    $bot->reply($response);
    exit('ok');
    }

  litleskip:


  // Якщо це кнопка яка містить команду, то переходимо до обробки команд
  $firstChar = substr($callback_data, 0, 1);
  if ($firstChar == '/' and strlen($callback_data) > 2) {
    $text = $callback_data;

    goto skip;
    }



  // умовно застарілий фукнціонал, але треба просто вдосконалити
  if (stristr($callback_data, 'btn_') !== false) {
    $cutt = mb_substr($callback_data, 4);
    // Для тестов
    //$bot->reply($cutt);exit('ok');

    // Если клиентом запрошен новый код привязки - отправляем его
    if ($cutt == 'newkod') {
      // Ищем не получал ли клиент код проверки ранее
      // Продолжительность жизни кода допустим СУТКИ
      $kp = tg::testKodProverki($bundle);

      if ($kp == false) {

        if ($bundle['trykod'] >= '1') {
          // Обнуляем попытки
          core::$db->query("UPDATE `" . load::$tg_bundle . "` SET `trykod`= '0' WHERE `from_id`='" . $user . "' and `bot`='" . $botinfo['name'] . "'");
          }

        $ua = "Надсилаємо email із кодом...";
        $ru = "Отправляем email с кодом...";
        $en = "Send an email with the code...";
        $nowsendkod = mova::lg($lg, $ua, $ru, $en);
        $bot->answerCallback($nowsendkod);
        // Отправляем письмо с кодом
        tg::SendKod($botinfo, $chat, $lg, $aco, $host, $isAdmin, $site, $infoemail);
        $bot->deleteMessage($message_id - 1);

        exit('ok');
        } else {
        $kogdanew = date('H:i (d.m.Yг.)', strtotime($bundle['kodtime']) + (60 * 60 * 24));
        // Допустимо не раньше {kogdanew}
        $ua = "Допустимо не раніше ніж " . $kogdanew;
        $ru = "Допустимо не раньше " . $kogdanew;
        $en = "Acceptable no earlier than " . $kogdanew;
        $ranowantkod = mova::lg($lg, $ua, $ru, $en);
        $bot->answerCallback($ranowantkod);

        }
      exit('ok');

      }


    if ($cutt == 'editemail') {
      // Нужно позволять менять подтверждённый эмаил не чаще раза в месяц
      if ($bundle['kod'] == 'ok') {
        // А в кредитной схеме вообще нельзя позволять его менять
        if ($st != 'credit') {
          $timepayt = strtotime($bundle['setmail']);
          $timeEnd = time() - (60 * 60 * 24 * 30);
          // Если прошло меньше допустимого, то считаем старый код активным
          if ($timepayt > $timeEnd) {
            $kogdanew = date('d.m.Yг.', strtotime($bundle['setmail']) + (60 * 60 * 24 * 30));
            // Допустимо не раньше {kogdanew}
            $ua = "Допустимо не раніше ніж " . $kogdanew;
            $ru = "Допустимо не раньше " . $kogdanew;
            $en = "Acceptable no earlier than " . $kogdanew;
            $ranowantkod = mova::lg($lg, $ua, $ru, $en);
            $bot->answerCallback($ranowantkod);

            $ua = "З метою безпеки ми дозволяємо змінювати підтверджений email НЕ частіше разу на місяць.\r\n\r\nЗмінити поточний email Ви зможете не раніше " . $kogdanew;
            $ru = "В целях безопасности мы позволяем менять подтверждённый email НЕ чаще раза в месяц.\r\n\r\nИзменить текущий email Вы сможете не раньше " . $kogdanew;
            $en = "For security reasons, we allow you to change your confirmed email NOT more than once a month.\r\n\r\nYou can change your current email no earlier than " . $kogdanew;
            $textsTwo = mova::lg($lg, $ua, $ru, $en);
            $bot->reply($textsTwo);
            exit('ok');
            }
          }
        }

      // Просим ввести новый эмаил
      $ua = "Введіть новий email";
      $ru = "Введите новый email";
      $en = "Enter new email";
      $sendnewmail = mova::lg($lg, $ua, $ru, $en);
      $bot->answerCallback($sendnewmail);
      $bot->reply($sendnewmail);
      exit('ok');
      }

    // 
    if ($cutt == 'oldmail') {
      $ua = "Залишили все як було";
      $ru = "Оставили всё как было";
      $en = "Everything was left as it was";
      $vsekakbilo = mova::lg($lg, $ua, $ru, $en);
      $bot->answerCallback($vsekakbilo);
      $bot->deleteMessage($message_id - 1);
      $bot->deleteMessage($message_id);
      exit('ok');
      }


    }



  // Видалення повідомлення до якого прив'язана ця кнопка
  if (stristr($callback_data, 'delete_') !== false) {
    $essence = mb_substr($callback_data, 7);
    if (!empty($essence)) { // Якщо є доп.параметр
      // Відмічаємо в базі повідомлення як видалене
      tg::delDialog($user, $essence, $message_id);
      }
    // Повідомляємо про успіх
    $bot->answerCallback('ok');
    // Видаляємо
    $bot->deleteMessage($message_id);
    exit('ok');
    }






  // Якщо надавач послуг хоче видалити місто, яке додав раніше
  if (stristr($callback_data, 'citydel_') !== false and $bundle['role'] == 'service') {
    $bot->answerCallback('in developing');
    $bot->insertButton([["text" => "НАЗАД В ПРОФІЛЬ", "callback_data" => "/profile"]]); // Повернутись назад
    $bot->reply("Функціонал в розробці...");
    exit('ok');
    }

  // Якщо це кнопка вибору з багатьох пунктів
  if (stristr($callback_data, 'choose_') !== false) {
    $cutt = mb_substr($callback_data, 7);
    $arrc = explode("_", $cutt);
    // Видаляємо попередне повідомлення
    // під час тестування не видаляю
    $bot->deleteMessage($message_id);
    $bot->answerCallback('');
    $response = '';
    // Кліенти можуть зберегти в базу лише одне місто
    // Якщо місто вказано раніше, чи даємо ми змогу його легко змінювати?
    // Треба давати
    // А ось надавача сервісу треба якось обмежувати. ЯК?
    // Надавач послуг поки що може вказувати 7 міст 
    // щоб можна було розмістити дозволену телеграмом кількість рядів-кнопок
    if ($bundle['role'] == 'service') {
      // Перевіряємо чи може надавач послуг додати ще
      // Бо надавач послуг може їх надавати в кількох населених пунктах
      // Рахуємо кількість доданих раніше
      $secis = explode(",", $service["city_id"]);
      $seci = array_map('trim', $secis);
      $coci = count($seci);
      $cocilimit = 7;

      // Отримання даних про міста доданы раніше цим 
      $listCity = tg::getCities($seci);
      $onlyCity = tg::getOnlyCities($listCity);


      // ПОКИ ЩО ДОЗВОЛЯЄМО ЛИШЕ 7 щоб кількість кнопок була допустима телеграмом
      // Якщо дозволяти додавати більше 7, то треба все ускладнювати...
      // Але (!) код нижче вже вміє працювати з 14 кнопками!
      // Питання в тому
      // чи потрібно давати надавачам послуг можливість працювати з великою кількістю міст?
      // Ліміти все рівно є не те щоб великими і обмежені ~28 можливо край - 35
      // після яких потрібно якось додатково городити складні велосипеди
      // В рамках rear задачі то не потрібно
      if ($coci >= $cocilimit) {
        // Якщо вже додано достатньо, пропонуємо спочатку видалити якийсь один 

        $uspehAdd = "Зверніть увагу!\r\n";
        // Створення кнопок видалення міст
        tg::makeDelCityBtnService($listCity, $bot);
        $bot->reply($uspehAdd . "Ви додали максимально можливу (" . $cocilimit . ") кількість міст!\r\nЩоб додати нове, Вам потрібно ВИДАЛИТИ одне з доданих раніше.\r\n\r\nОберіть МІСТО ЯКЕ ВИДАЛИТИ:");
        exit('ok');
        }


      } // $arrc[0] == 'region' and $bundle['role'] == 'service'
    // Код вище ГОТОВИЙ але не тестований
    // Він не дає надавачу послуг додати ще одне місто якщо він досяг ліміту
    $arr = [];
    $list = $arrc[0];
    $idReg = false;
    array_shift($arrc); // Видаляємо з масива перший елемент $arrc[0] 'region'/'city'

    if (empty($arrc[1]) and $list == 'region') {
      $region = core::getRegionById($arrc[0]);
      $response = "Ви обрали:\r\n*" . $region['region'] . "*\r\n\r\n";
      }
    // Якщо РЕГІОН лише один конкретний або це місто (-а)
    // Шукаємо міста
    if ((empty($arrc[1]) and $list == 'region') or $list == 'city') {
      $idReg = $arrc[0];
      if ($list == 'city') {
        array_shift($arrc); // Видаляємо регіон з переліку міст
        }
      $mista = core::getAllSatellite($idReg, $botinfo['country'], $listProblemRegion, $botinfo['FilterPrblmRegion']);
      if ($bundle['role'] == 'service') {
        $mista = core::arrayСleaning($mista, $onlyCity, $locale, true);
        }

      if (!empty($mista)) {
        $i = 0;
        foreach ($mista as $value) {
          $i++;
          $arr[$i]['id'] = $i;
          $arr[$i]['name'] = $value;

          }
        // Якщо це по замовчуванню був перелік міст
        // Значить користувач намагається обрати конкретне місто
        // то викидаємо із масиву все що відсутне в $arrc
        if ($list === 'city') {
          $arr = array_filter($arr, function ($item) use ($arrc) {
            return in_array($item['id'], $arrc);
            });
          }
        $list = 'city';
        } else if ($bundle['role'] == 'service') {
        // Якщо міст НЕ лишилось І ПРИ ЦЬОМУ
        // це надавач послуг
        // Та водночас в регіоні до цьої фільтрації були міста
        // Значить надавачу послуг в цьому регіоні вже не залишилось міст і він вже вказав всі
        // В цій області (регіони) Ви надаєте послуги в усіх доступних містах


        }



      }


    // Якщо областей кілька
    if (!empty($arrc[1]) and $list == 'region') {
      // Якщо ОБ'ЄКТІВ багато, треба розкласти їх на кнопки і знов запропонувати обрати регіон
      // Формування запиту щоб отримати перелік цільових регіонів
      $ids = implode(',', $arrc);
      $query = "SELECT `id`,`region` FROM `regions` WHERE `id` IN ($ids)";
      $result = core::$db->query($query);
      // Обробка результатів
      if ($result->num_rows > 0) {
        $i = 0;
        while ($row = $result->fetch_assoc()) {
          $i++;
          $arr[$i]['id'] = $row['id'];
          $arr[$i]['name'] = $row['region'];
          }
        $result->close();
        } else {
        // Такого просто не може бути
        $list = 'error';
        }
      }
    // * КОД ВИЩЕ В РАМКАХ choose_ вважається готовим

    //$bot->reply(print_r($idReg,true) . " ".count($arr)." ". $list." ". $arrc[0]);exit('ok');
    //
    // Якщо В ОБЛАСТІ варіантів МІСТ немає (тобто це столиця або маленький регіон)
    // Або обране конкретне місто
    // значить ми зупиняємося і фіксуємо те що є
    if ((empty($arr) and $list == 'region') or (count($arr) == '1' and $list == 'city')) {
      if (empty($region)) {
        $region = core::getRegionById($idReg);
        }
      $editR = false;
      // Зберігаємо в базі якщо регіон змінено
      if ($bundle['role'] != 'service') {

        // Зберігаємо НОВИЙ регіон та видаляємо старе місто в даному випадку обов'язково
        if ($bundle['region'] != $region['region']) {
          $bundle['region'] = $region['region'];
          // Якщо вказується місто, то нічого не коригуємо
          if ($list == 'city') {
            $bundle['city'] = $arr[$arrc[0]]['name'];
            } else {
            // Якщо поки що вказується область, то обнулюємо місто
            $bundle['city'] = "";
            }
          $editR = true;
          } else if ($list == 'city' and $bundle['city'] != $arr[$arrc[0]]['name']) {
          $editR = true;

          }
        if ($editR == true) {
          core::$db->query("UPDATE " . load::$tg_bundle . " SET `region` = '{$region['region']}',`city` = '{$arr[$arrc[0]]['name']}' WHERE `id`='{$bundle['id']}'");
          }
        if (empty($bundle['phone'])) {
          $renewvk = "вказати";
          $prba = "при бажанні";
          $narazi = "";
          } else {
          // Чи треба взагалі досилати це повідомлення про можливість оновити телефон?
          // Не думаю, воно більше заважає, але видаляти  чи переналаштовувати функціонал мені впадло
          // Тому вимикаю змінною. true - якщо треба слати
          $sendReTel = false;
          $renewvk = "оновити";
          $prba = "при необхідності";
          $narazi = "\r\n\r\nНаразі підтверджено " . $bundle['phone'];
          }
        $bot->insertButton([["text" => "ВАШ ПРОФІЛЬ", "callback_data" => "/profile"]]);
        $bot->insertButton([["text" => $gotostart, "callback_data" => "/start"]]); // Повернутись назад
        $bot->reply("Регіон знаходження успішно оновлено на:\r\n\r\n". $region['region']."\r\n". $arr[$arrc[0]]['name']."\r\n\r\n");
        if (!empty($sendReTel)) {
          $bot->setupTypeKeyboard('keyboard');
          $bot->insertButton([["text" => $sharecontact, "request_contact" => true]]);
          $bot->reply("Тепер (" . $prba . ") Ви можете " . $renewvk . " свій номер телефону, що прив'язано до цього Telegram аккаунту" . $narazi . ".");
          }
        exit('ok');

        } else {
        // Якщо перед нами надавач послуг
        // $bundle['role'] == 'service'
        // Шукаємо назву міста
        if ($list == 'city') {
          $city = $arr[$arrc[0]]['name'];
          $cityId = $arr[$arrc[0]]['id'];
          $listCity[$arr[$arrc[0]]['id']] = ['city' => $city, 'region' => $region['region']];
          } else {
          $city = '';
          $cityId = false;
          }
        // Перевіряємо, чи не додавав надавач послуг це місто (ОБЛАСТЬ) раніше
        $testCid = tg::findCityId($listCity, $region['region'], $city);

        $uspehAdd = "Місто успішно додано:\r\n" . $region['region'] . ", " . $city . "\r\n-----\r\n";
        // Додаємо поточний варіант вказаний надавачем сервісу в базу
        $new_id = tg::insertCity($bundle['id'], $city, $region['region'], $aco);
        // Додаємо користувачу в його перелік  $service["city_id"].","
        $update_city_id = core::updateStringIds($service["city_id"], $new_id);
        if ($update_city_id[1] == true) { // Вносимо в базу
          core::$db->query("UPDATE `tg_service` SET `city_id` = '{$update_city_id[0]}'  WHERE `id`='{$service['id']}'");
          }
        // після чого пропонуємо додати ще
        // Перевіряємо чи може надавач послуг додати ще
        // Бо надавач послуг може їх надавати в кількох населених пунктах
        // Повідомляємо про досягнення ліміту
        if ($coci >= $cocilimit - 1) {
          // Створення кнопок видалення міст
          tg::makeDelCityBtnService($listCity, $bot);
          $bot->reply($uspehAdd . "Зверніть увагу!\r\nВи додали максимально можливу (" . $cocilimit . ") кількість міст!\r\nЩоб додати нове, Вам потрібно ВИДАЛИТИ одне з доданих раніше.\r\n\r\nОберіть МІСТО ЯКЕ ВИДАЛИТИ:");
          exit('ok');
          }
        // Якщо ще можна додавати інші міста
        $bot->insertButton([['text' => "додати місто надання послуг", 'callback_data' => 'rear_pluscityservice']]);
        $bot->reply($uspehAdd . "Ви можете додати ще " . $cocilimit - $coci - 1 . " міст:");
        exit('ok');



        }
      }
    // Якщо єлементів > 7, то клавіатура буде укомпактнена
    if (count($arr) > 7) {
      if ($list == 'city') {
        $utockilk = "перші букви НАСЕЛЕНОГО ПУНКТУ";
        } else {
        $utockilk = "натисніть на букви з яких починається назва ОБЛАСТІ";
        }

      } else {
      if ($list == 'city') {
        $utockilk = "НАСЕЛЕНИЙ ПУНКТ";
        } else {
        $utockilk = "ОБЛАСТЬ";
        }

      }


    // Хто обирає з массиву?
    // В залежності від цього змінюється відповідь
    if ($bundle['role'] != 'service') {
      // Звичайний користувач
      $response .= "А тепер оберіть " . $utockilk . " Вашого проживання";
      } else {
      // Надавач послуг
      $response .= "А тепер оберіть " . $utockilk . " де Ви готові надавати послуги фізично:";
      }

    // Якщо є массив, формуємо клавіатуру з вибором
    if (!empty($arr)) {
      $keyboard = tg::makeAbcKeyboard($arr, $list, $idReg, $locale);
      $bot->insertKeyboard($keyboard);
      }
    // Якщо помилка
    if ($list == 'error') {
      $response = "Помилка пошуку...";
      }

    if ($bundle['role'] != 'service') {
      $kuda = '/reg';
      } else {
      $kuda = 'rear_pluscityservice';
      }

    $bot->insertButton([["text" => '🔙 ДО СПИСКУ ОБЛАСТЕЙ', "callback_data" => $kuda]]); // Повернутись назад
    $bot->reply($response);
    exit('ok');

    } // choose_


  // Если клиент нажал на кнопку которая не описана выше
  // Выходим никак не обработав клик
  // ! Можливо про це треба повідомляти адміна
  // ! бо виходить, що клік по якійсь кнопці не було опрацьовано
  $bot->answerCallback('');
  exit('ok');

  } // КОНЕЦ ОБРАБОТКИ нажатий на INLINE кнопки


skip:

// Починаємо опрацьовувати команди 
// і відповідаємо на них в особисті
$textCommand = false;
$firstChar = substr($text, 0, 1);
if ($firstChar == '/' and strlen($text) > 2) {
  $textCommand = true;
  $bot->for($user);
  if (!empty($cqid) and $chattype != 'private') {
    $bot->answerCallback('Bot sent you a private message');
    } else {
    $bot->answerCallback('');
    }
  }



// тут опрацьовуємо команди які в публічному чаті відправляють ботам користувачі чату
if ($textCommand == true and strpos($text, '@') !== false) {
  $command = trim(explode("@", $text));
  $text = $command[0];


  }



// Якщо поступила команда на реєстрацію умовного надавача послуг
if ($textCommand == true and $text === '/regservice') {

  if ($bundle['role'] == 'service') {
    $bot->insertButton([["text" => $gotostart, "callback_data" => "/start"]]); // Повернутись назад
    $bot->reply("Ви вже зареєстровані в системі в якості надавача послуг! Повторно реєструватись немає потреби. У випадку запитань, проблем, ідей чи пропозицій - пишіть на:\r\n" . $botinfo['manager_mail']);
    exit('ok');
    }
  // Перепитуємо, чи справді він хоче подати заявку на реєстрацію надавачем послуг
  // Тут все поки що дуже спрощено
  // Це концепт який кожен може налаштовувати під себе
  $bot->insertButton([['text' => "✅ ТАК, ПІДТВЕРДЖУЮ", 'callback_data' => "rear_addserviceok"]]);
  $bot->insertButton([['text' => "🚫 СКАСУВАТИ", 'callback_data' => "rear_addserviceno"]]);
  $bot->reply("Ви підтверджуєте, що готові надавати послуги " . $botinfodop['service'] . "?\r\n\r\nЯкщо підтверджуєте, то на наступному етапі Вам потрібно буде заповнити профіль та озвучити Ваші ціни");
  exit('ok');
  }



// Якщо це бот який дозволяє реєстрацію 
// та поступила команда на реєстрацію
if ($textCommand == true and ($text === '/reg' or $text == '/registration')) {


  // Перевіряємо чи не є кліент вже зареєстрованим надавачем послуг
  // Бо їх не можна допускати до реєстрації у якості кліента
  if ($bundle['role'] == 'service') {
    $bot->reply("Ви зареєстровані як надавач послуг " . $botinfodop['service'] . ", тому реєстрація у якості отримувача послуг Вам недоступна.");
    exit('ok');
    }


  // перевіряємо, чи не реєструвався кліент раніше
  // Якщо раніше НЕ реєструвався або недозаповнив форму
  // То пропонуємо зареєструватись
  if ($bundle['region'] == '') {
    $query = "SELECT `id`,`region` FROM `regions` WHERE `country` = '{$botinfo['country']}'";
    $result = core::$db->query($query);
    if ($result->num_rows > 0) {
      $i = 0;
      while ($row = $result->fetch_assoc()) {
        $i++;
        $regions[$i]['id'] = $row['id'];
        $regions[$i]['name'] = $row['region'];

        }
      $result->close();

      // Якщо єлементів > 7, то клавіатура буде укомпактнена
      if ($i > 7) {
        $utockilk = "натисніть на букви з яких починається назва ОБЛАСТІ";
        } else {
        $utockilk = "оберіть ОБЛАСТЬ";
        }
      $keyboard = tg::makeAbcKeyboard($regions, 'region', false, $locale);
      $bot->insertKeyboard($keyboard);
      $bot->insertButton([['text' => "✉ ОЗНАЙОМИТИСЬ З УМОВАМИ", 'url' => "https://" . $botinfodop['oferbase']]]);
      // Якщо це редагування попереднього
      if ((!empty($rear[0]) and $rear[0] == 'editregion') or $bundle['region'] != '') {
        $edornot = "Вкажіть";
        } else {
        // Якщо це встановлення
        $edornot = "Ми зможемо надати Вам МІСЦЕВОГО " . $botinfodop['service'] . " лише після того як Ви вкажите";
        }
      $bot->reply($edornot . " область та місто поточного знаходження.\r\n---\r\nПеред цим просимо ознайомитись з [умовами](https://" . $botinfodop['oferbase'] . ")\r\n---\r\nЯкщо Ви приймаєте умови, тоді " . $utockilk . " ВАШОГО ЗНАХОДЖЕННЯ:");
      exit('ok');
      }


    }

  // Якщо місто не вказано, можливо так треба
  // Знаходимо регіон і перевіряємо, чи є в ньому непроблемні міста 
  if ($bundle['city'] == '' and $bundle['region'] != '') {
    $mista = core::getAllSatellite($bundle['region'], $botinfo['country'], $listProblemRegion, $botinfo['FilterPrblmRegion']);
    // Якщо міста є в переліку, значить кліент не вказав місто і потрібно щоб спочатку він його вказав
    if (!empty($mista)) {
      $list = 'city';
      $i = 0;
      foreach ($mista as $value) {
        $i++;
        $arr[$i]['id'] = $i;
        $arr[$i]['name'] = $value;

        }
      // Якщо єлементів > 7, то клавіатура буде укомпактнена
      if (count($arr) > 7) {
        if ($list == 'city') {
          $utockilk = "перші букви НАСЕЛЕНОГО ПУНКТУ";
          } else {
          $utockilk = "натисніть на букви з яких починається назва ОБЛАСТІ";
          }

        } else {
        if ($list == 'city') {
          $utockilk = "НАСЕЛЕНИЙ ПУНКТ";
          } else {
          $utockilk = "ОБЛАСТЬ";
          }

        }


      // Хто обирає з массиву?
// В залежності від цього змінюється відповідь
      if ($bundle['role'] != 'service') {
        // Звичайний користувач
        $response .= "А тепер оберіть " . $utockilk . " Вашого проживання";
        } else {
        // Надавач послуг
        $response .= "А тепер оберіть " . $utockilk . " де Ви готові надавати послуги фізично:";
        }

      // Якщо є массив, формуємо клавіатуру з вибором
      if (!empty($arr)) {
        $keyboard = tg::makeAbcKeyboard($arr, $list, $idReg, $locale);
        $bot->insertKeyboard($keyboard);
        }
      // Якщо помилка
      if ($list == 'error') {
        $response = "Помилка пошуку...";
        }

      if ($bundle['role'] != 'service') {
        $kuda = '/reg';
        } else {
        $kuda = 'rear_pluscityservice';
        }

      $bot->insertButton([["text" => '🔙 ДО СПИСКУ ОБЛАСТЕЙ', "callback_data" => $kuda]]); // Повернутись назад
      $bot->reply($response);
      exit('ok');
      }
    // Якщо міст немає, значить все окей, йдемо далі
    }

  // Якщо він ще не вказав раніше номер телефона, пропонуємо поділитись номером
  if ($sos != true) {

    if (empty($bundle['phone'])) {
      $renewvk = "При бажанні Ви можете веріфікувати номер телефону";
      $narazi = "";
      } else {
      $renewvk = "Якщо Ви змінили номер телефону, то перед замовленням " . $botinfodop['service'] . ", його потрібно оновити";
      $narazi = "Наразі Ви веріфікували:\r\n" . $bundle['phone'] . "\r\n-----\r\n";
      }
    $bot->insertButton([['text' => "змінити регіон знаходження", 'callback_data' => 'rear_editregion']]);
    $bot->insertButton([['text' => "змінити номер телефону", 'callback_data' => 'rear_editphone']]);
    $bot->reply("Ви вказали регіон знаходження:\r\n" . $bundle['region'] . "\r\n" . $bundle['city'] . "\r\n-----\r\nВ разі зміни регіону, Ви маєте оновити дані, перш ніж замовляти послугу МІСЦЕВОГО " . $botinfodop['service'] . ".\r\n\r\n" . $narazi . $renewvk . ".");
    exit('ok');

    }



  // Якщо кліент раніше вказував регіон


  // Перевіряємо чи є незакритий поточний ордер замовлення послуги
  // Якщо поточний ордер є, то видаємо його
  $seorder = tg::SearchServiceOrder($user);

  // Перевіряємо, чи співпадає регіон та місто у ордера та у клієнта зараз
  // Якщо не співпадає, вимагаємо спочатку дати фідбек на попередній ордер

  // Перш ніж надати нового надавача послуг 
  // треба перевірити на абюз системи
  // скільки ордерів за сутки створив цей користувач?
  // Скільки ордерів загалом у нього cancel


  // надаємо йому надавача послуг
  // Якщо маємо таку можливість
  $bot->insertButton([["text" => $gotostart, "callback_data" => "/start"]]); // Повернутись назад
  $bot->reply("Вибачте, база надавачів послуг ще формується!\r\n-----\r\nСлідкуйте за новинами в нашому [каналі](" . $botinfo['channel'] . ")");

  exit('ok');
  }




// Виводимо профайл користувача
if ($textCommand == true and $text === '/profile') {
  // Створення екземпляра класу tgProfile
  $profileHandler = new tgProfile($bot, $bundle);
  // Передача додаткових даних необхідних для виводу профайлу
  $profileHandler->makeProfile($botinfodop, $service);
  exit('ok');
  }




// Тільки після того як ми 
// пошукали клієнта, внесли його в базу, або забанили
// Тільки після ми починаємо працювати з діплінками та командами
// Працюємо С ДІПЛІНКАМИ
if ($textCommand == true and strpos($text, ' ') !== false) {
  $command = explode(" ", $text);
  }

// Якщо це первісна команда з параметрами 
// (використвується в схемах де спочатку сайт потім бот (event))
// Тобто користувач вже в базі, ми ідентифікуємо його по діплінку
//  і зараз він ділиться своїм tg
if ($textCommand == true and $st != 'bot' and $command[0] === '/start' and !empty($command[1]) and strlen($command[1]) >= '35' and strlen($command[1]) <= '44') {

  // Ми можемо розповсюджувати декілька видів діплінків

  // Перший для експрес реєстрації за посиланням з листа
  // наприклад адмін може в своєму tg чаті з ботом ввести sendkey gmail@gmail.com
  // це може бути корисно, коли ви на якихось зустрічах клубу хочете додати користувача в чат клубу 
  // тобто якщо клієнт перейшов за посиланням з такого листа, то він підтвердив емаїл і chat_ia одночасно
  // key a
  // https://t.me/{$botinfo['name']}?start={$user_key}a{$country}

  // Другий для білетера
  // Для використання в QR кодах квитків для входу на івенти
  // Поки розробку закинув
  // https://t.me/{$botinfo['name']}?start={$user_key}b{$ivent}
  // tg://resolve?domain={$botinfo['name']}?start={$user_key}c{$lg}

  // Третій key "c" для зв'язки зареєстрованного раніше веб аккаунта з телеграм аккаунтом

  // * Третій key "c" - ЕДИНИЙ НАРАЗІ ПОТРІБНИЙ ФУНКЦІОНАЛ НАД ЯКИМ ПРАЦЮЮ
  // Інші реалізовані базово і їх треба ще розвивати

  $user_key = mb_substr($command[1], 0, 32); // Перші 32 символи
  $typelink = mb_substr($command[1], 32, 1); // 33-й символ
  $co = mb_substr($command[1], 33); // Всі інші символи після 33-го

  if (!empty($co) and $typelink != 'b') {
    // Для початку, треба перевірити чи працює сайт з цією країною
    // І тільки переконавшись, що працює - використовуємо країну
    // в розробці

    $lg = $co;
    }

    $mdkey = md5($user_key);

  // Адмін НЕ може використати (погасити) діплінки цих типів
  if (($typelink == 'a' or $typelink == 'c') and ($isAdmin === true or $isUsher === true)) {
    $bot->reply("The link must be used by the client!");
    exit('ok');
    }

  // Отже, у нас є ключ за яким ми шукаємо мило в таблиці з диплінками
  // Залежно від типу диплінка - шукаємо у різних таблицях
  $tabledip = false;
  // Цей лінк експрес реєстрації по ссилці з листа
  if ($typelink == 'a') {
    $tabledip = "tg_deeplink";
    $where = "user_key";
    $endz = "";
    }
  // це лінк перевірки квитка для білетера на вході на івенти
// Тут все якесь сЫрэ поки що
  else if ($typelink == 'b') {
    $tabledip = "tickets";
    $where = "key";
    $endz = "";
    // тестове
    $bot->reply('тікет успішно проскановано');
    exit('ok');
    }

  // 
  else if ($typelink == 'c') {
    $tabledip = load::$partners;
    $where = "partner_key";
    $endz = "";
    }

  // Заготовочки типові
// Під помилку
  $ua = "Посилання яким Ви скористалися, ні до чого не призвело";
  $ru = "Ссылка, которой Вы воспользовались, ни к чему не привела";
  $en = "The link you used did not lead to anything";
  $error_link = mova::lg($lg, $ua, $ru, $en);
  $ua = "З поверненням!";
  $ru = "С возвращением!";
  $en = "Welcome back!";
  $welcomeback = mova::lg($lg, $ua, $ru, $en);

  // Перед нами помилковий лінк
  if ($tabledip == false) {
    // Повідомляємо про помилку лінку
    $bot->reply($error_link . ' #0');
    exit('ok');
    }


  $result = core::$db->query("SELECT * FROM `{$tabledip}` WHERE `{$where}`='{$mdkey}'{$endz}");
  $countsrows = $result->num_rows;
  if ($countsrows == 1) {
    // Отримуємо масив який знайшли по ссилці
    $searchdl = $result->fetch_array(MYSQLI_ASSOC);
    $result->close();
    } else if ($countsrows > 1) {
    $result->close();
    // Повідомляємо про помилку лінку
    $bot->reply($error_link . ' #1');
    exit('ok');
    } else {
    $result->close();
    }

  // Якщо це ніби мав бути кліент, а його в базі не знайшли, значить лінк не вірний
  if (empty($searchdl) and $typelink == 'c') {
    // Повідомляємо кліенту, що ми його не знайшли в базі і пропонуємо спочатку зареєструватись на сайті
    $bot->hello($textNeedRegSite);
    exit('hello');
    }





  // Если диплинк не найден, то возможно он уже был использован ранее
  if (empty($searchdl) and $typelink == 'a') {
    $tbs = tg::SearchTgByKey($mdkey, $nameBot);
    // Аккаунт уже создавался ранее
    if (!empty($tbs) and $tbs['kod'] = 'ok') {
      // Возможно клиент удалил привязку к боту, тогда восстанавливаем её
      if ($tbs['mst'] != '') {
        $bot->reply($welcomeback);
        core::$db->query("UPDATE `" . load::$tg_bundle . "` SET `mst` = '',`setmail`=NOW(),`trykod`= '0'`country`='{$lg}' WHERE `from_id`='{$chat}' AND `bot` = '{$nameBot}");
        exit('ok');
        }
      }
    // Можно усложнять и более подробно разбирать причины
    // Давати якісь складні відповіді, але поки що виходимо
    // Повідомляємо про помилку лінку
    $bot->reply($error_link . ' #2');
    exit('ok');

    }

  // ВИдаємо помилку для інших ситуацій ненаходу
  if (empty($searchdl)) {
    // Повідомляємо про помилку лінку
    $bot->reply($error_link . ' #3');
    exit('ok');
    }
  // далі проходять лише ті кого ми знайшли в базі

// А знач далі user_key нормально працює
  array_push($billet, "{user_key}");
  array_push($repl, $user_key);
  // Отже спроба єкспрес реєстрації
  // Тут ніби поки трохи каша
  if ($typelink == 'a') {
    // Получив мыло проверяем соответствует ли оно юзеркею
    // Формируем юзер кей заново?
    $semail = core::buildStandartEmail($searchdl['email']);
    $ukey = md5($semail . load::SECURITY);
    // Если ключ верный, то вносим клиента в базу
    if ($ukey == $user_key) {
      // Поищем есть вообще ли такое мыло в связках с этим ботом
      // Ибо мыло должно быть уникальным для каждого посетителя 
      $tgBundle = tg::SearchTgEmail($semail, $bot);
      if ($tgBundle and $tgBundle['from_id'] != $chat) {
        // Данное мыло уже принадлежит кому-то другому, просим другой маил
        $ua = "Даний email закріплений за іншим Telegram акаунтом.\r\n\r\nДля реєстрації під цим Telegram акаунтом Вам доведеться використовувати інший email.\r\n\r\nВведіть нижче інший email:";
        $ru = "Данный email закреплён за другим Telegram аккаунтом.\r\n\r\nДля регистрации под этим Telegram аккаунтом Вам придётся использовать другой email.\r\n\r\nВведите ниже другой email:";
        $en = "This email is assigned to another Telegram account.\r\n\r\nTo register under this Telegram account you will have to use another email.\r\n\r\nEnter another email below:";
        $texts = mova::lg($lg, $ua, $ru, $en);
        $bot->reply($texts);
        exit('ok');
        }
      $uktb = md5($user_key);
      $ua = "Ви успішно зареєстровані! Приємної роботи!";
      $ru = "Вы успешно зарегистрированы! Приятной работы!";
      $en = "You are successfully registered! Have a nice job!";
      $answerOkReg = mova::lg($lg, $ua, $ru, $en);
      $bot->reply($answerOkReg);
      if (!empty($bundle['from_id'])) {
        // Обновляем запись
        $stz = "UPDATE";
        $endz = "WHERE `from_id`='" . $bundle['from_id'] . "' and `bot`='" . $bot . "'";
        } else {
        // Вставляем новую строку
        $stz = "INSERT INTO";
        $endz = "";
        }
      core::$db->query($stz . " `" . load::$tg_bundle . "` SET  `email`='$semail',`user_key`='$uktb',`kod`='ok',`setmail`=NOW(),`trykod`= '0',`country` = '$lg'" . $endz);
      // Удаляем запись из таблицы с диплинками
      core::$db->query("DELETE FROM `" . load::$tg_deeplink . "` WHERE `email` = '" . $semail . "'");
      exit('ok');
      }
    }

  // В свою чергу кліент не може погасити діплінк класу b
  if ($typelink == 'b' and $isAdmin === false and $isUsher === false) {
    // Загалом тут треба все сильно ускладнити
    // Якщо квиток намагається відсканити сам володар квитка, то 
    // йому можна писати інформацію про подію (дату, час, місце, ціну)
    // Якщо квиток намагається відсканити НЕ володар квитка і виходить (не наші адміни та isUsher)
    // Йому видавати якусь відмову
    // А ТАКОЖ володарю висилати нагадування про те, що не можна передавати квиток третім особам
    }

  // Кліент намагається завершити реєстрацію
  if ($typelink == 'c') {
    // Перевіряємо, чи потрібно нам це 
    // Ну тобто чи не робив він це раніше
    if ($searchdl['TelegaID'] == '') {

      $testsear = false;
      $tomach = false;
      $tsm = mova::lg($lg, "іншого email", "другому email", "another email");
      // Шукаємо, а чи не закріплено цей телеграм аккаунт наразі за кимось іншим?
      $result = core::$db->query("SELECT * FROM `" . load::$partners . "` WHERE `TelegaID`='" . $user . "'");
      $stroki = $result->num_rows;
      if ($stroki == 0) {
        $result->close();
        }
      // Це якась погана ситуація
      if ($stroki > 1) {
        $result->close();
        $tomach = true;
        }

      if ($stroki == 1) {
        $testsear = $result->fetch_array(MYSQLI_ASSOC);
        $result->close();
        $tsm = $testsear['email'];
        }

      // Готуємо текст помилки
      $ua = "Ви намагаєтесь прив'язати цей телеграм до " . $searchdl['email'] . "\r\n\r\nАле це НЕМОЖЛИВО бо цей телеграм вже прив'язано до " . $tsm . "\r\n\r\nЗгідно нашіх політик - ми не можемо дозволити прив'язати декілька облікових записів до одного телеграм акаунту.\r\n\r\nОдна людина може зареєструвати лише один особистий акаунт.";
      $ru = "Этот телеграм привязан к " . $tsm . "\r\n\r\nСогласно нашим политикам – мы не можем позволить привязать несколько учетных записей к одному телеграмм аккаунту.\r\n\r\nОдин человек может зарегистрировать только один личный аккаунт.";
      $en = "This telegram is linked to " . $tsm . "\r\n\r\nDue to our policies, we cannot allow several cloud accounts to be linked to one telegram account.\r\n\r\nOne person can register only one specific account.";
      $response = mova::lg($lg, $ua, $ru, $en);

      // Якщо закріплений, перевіримо, чи не закріплений він за тим кого ми забанили
      if (!empty($testsear) or $tomach == true) {
        // Якщо закріплений за тим кого ми забанили раніше, то 
        // значить банимо й цей новостворений аккаунт
        if (!empty($testsear) and $testsear['status'] == 'black') {

          }
        // Якщо закріплений, но нормальний, то видаємо повідомлення, що цей аккаунт вже прив'язаний до іншого
        $bot->reply($response);
        exit('ok');
        }



      $ua = "Вітаємо!\r\n\r\nТепер Вам необхідно підтвердити номер телефону.\r\n\r\nМи повинні переконатись, що телефон " . $searchdl['phone'] . " вказаний Вами при реєстрації, прив'язано саме до цього телеграм аккаунту.\r\n\r\nВодночас, це простий спосіб верефікації номера телефону без дзвінків та СМС.";
      $ru = "Приветствуем! Теперь вам необходимо подтвердить номер телефона. Мы должны убедиться, что телефон " . $searchdl['phone'] . " указанный Вами при регистрации, связан именно с текущим телеграмм аккаунтом.\r\n\r\Также это простой способ верификации номера телефона без звонков и СМС.";
      $en = "Hi!\r\n\r\nNow you need to confirm the phone number.\r\n\r\nWe need to make sure that phone " . $searchdl['phone'] . " specified by you during registration is connected to the current Telegram account.\r\n\r\nAt the same time, this is a simple way to verify the phone number without calls and SMS.";
      $response = mova::lg($lg, $ua, $ru, $en);
      $timestamp = date('Y-m-d H:i:s');
      // Не дублювати це повідомлення частіше ніж раз на 3 хвилин
      $bot->insertButton([['text' => $sharecontact, 'request_contact' => true]]);
      $bot->setupTypeKeyboard('keyboard');
      $bot->setHoldMinutes(3);
      $bot->giveLogName('authstart');
      $bot->reply($response);

      // Записуємо зв'язок цього мила з цим аккаунтом
      // Та підтверджуємо мило автоматично, бо кліент його підтвердив на минулому кроці
      core::$db->query("UPDATE `" . load::$tg_bundle . "` SET `email`='{$searchdl['email']}',`country`= '{$searchdl['country']}',`kod`='ok' WHERE `from_id`='{$user}' AND `bot`='{$nameBot}'");
      exit('ok');
      } else {
      $bot->reply($welcomeback);
      }

    exit('ok');
    }


  // Якщо ми чомусь НЕ обробили діплінку, то тут вкінці
  // Повідомляємо про помилку лінку і виходимо
  $bot->reply($error_link . ' #99');
  exit('ok');
  }


// ЗАКІНЧИЛИ З КОМАНДАМИ

// !Якщо це повідомлення до каналу або публічного чату, то бот далі НЕ йде
// ! Далі ЛИШЕ ОСОБИСТА ПЕРЕПИСКА З БОТОМ
if ($chattype != 'private') {
  exit('ok');
  }

// Одразу наперед задаємо, що спілкуємося в особистому чаті з юзером
$bot->for($user);
// Якщо це український номер телефона
// Мабуть він намагається надіслати номер телефону вручну, що заборонено
if ((substr($text, 0, 4) == '+380' and strlen($text) == '13') or (substr($text, 0, 3) == '380' and strlen($text) == '12')) {
  // Встановлюємо тип клавіатури
  $bot->setupTypeKeyboard('keyboard');
  // Поки що НЕ передбачено ручне введення номеру телефону
  $bot->insertButton([['text' => $sharecontact, 'request_contact' => true]]);
  // Якщо клієнт ще не вказував раніше особистий номер телефону
  if ($bundle['phone'] == '') {
    $bot->reply("Необхідно поділитись тим номером телефону, що прив'язано до Telegram аккаунту.\r\n---\r\nТаким чином, ми робимо формальну веріфікацію номерів для запобігання абюзивному спаму.\r\n---\r\nПоділитись та веріфікувати номер телефону потрібно за допомогою кнопки нижче");
    exit('ok');
    }
    $bot->reply("Ми НЕ запитували ручне введення номеру телефону. Якщо Ви хочете веріфікувати (підтвердити), номер що прив'язано до поточного Telegram аккаунту, то зробити це необхідно за допомогою кнопки нижче");
  exit('ok');
  }

// Якщо нам надіслали контакт
if (!empty($data['message']['contact']['phone_number'])) {
  // Беремо номер телефону
  $phone = $data['message']['contact']['phone_number'];
  if (substr($phone, 0, 1) != "+") {
    $phone = '+' . $phone;
    }
  $dopanketa = false;

  // Чи працює даний бот з країною телефону користувача?
  // Звіряємо телефон з перелікіком допустимих кодів країни
  $testCountry = core::inar($phone, $setAco['mobileCodes'], true);

  // Розвиток подій можна встановити різний
  // Можна наприклад не пускати далі якщо лєва країна
  if ($testCountry != true) {
    $bot->deleteKeyboard();
    $bot->reply('Цей бот працює лише з ' . strtoupper($botinfo['country']));
    // Також можна банити москалів
    if (strpos($phone, "+7") === 0) {
      $bot->tempban($user);
      }
    exit('ok');
    }


  // Оновлюємо усі записи цього юзера
  core::$db->query("UPDATE `" . load::$tg_bundle . "` SET `date_phone`=NOW(), `phone`='$phone' $dopanketa WHERE `from_id`='{$user}'");

  // Якщо це типова схема боту
  if (empty($partner) or $st == 'bot') {
    $ua = "Дякуємо, Ваш номер телефону збережено в безпечному сховищі";
    $ru = "Спасибо, Ваш номер телефона сохранен в безопасном месте";
    $en = "Thank you, your phone number is saved in a safe place";
    $response = mova::lg($lg, $ua, $ru, $en);
    $bot->reply($response);
    exit('ok');
    }

  // В інших випадках
  // Номер cпівпадає, а значить можемо без проблем завершувати реєстрацію
  $ua = "Аккаунт підтверджено!\r\n\r\nТепер Ви можете завершити реєстрацію на сайті.";
  $ru = "Аккаунт подтвержден!\r\n\r\nТеперь Вы можете завершить регистрацию на сайте.";
  $en = "Your account has been confirmed!\r\n\r\nNow you can complete your registration on the site.";
  $response = mova::lg($lg, $ua, $ru, $en);
  //
  $bot->reply($response);

  exit('ok');
  }


// Якщо це не діплінк, а якесь число
// Вважаємо, що клієнт ввів код прив'язки email до tg
if (ctype_digit($text) and strlen($text) == '5' and $text >= '30000') {

  // Перевіряємо чи це було потрібно
  if ($bundle['kod'] == 'ok' or $bundle['kod'] == '') {
    $ua = "Введення цього коду від Вас НЕ вимагалось";
    $ru = "Введение этого кода от Вас НЕ требовалось";
    $en = "You were NOT required to enter this code.";
    $texts = mova::lg($lg, $ua, $ru, $en);
    $bot->reply($texts);
    exit('ok');
    }
  // Перевіряємо, є валідний код перевірки
  $kp = tg::testKodProverki($bundle);
  if ($kp != false) {
    $kogdanew = date('H:i d.m.Y', strtotime($bundle['kodtime']) + (60 * 60 * 24));
    if ($bundle['trykod'] >= 3) {
      // We inform you that the client has exhausted the number of attempts to request a code for a day
      $ua = "На даний момент Ви вичерпали всі спроби підтвердження email.\r\n\r\nПісля " . $kogdanew . "р. Ви можете запросити новий код АБО змінити " . $bundle['email'] . " на іншій.";
      $ru = "На данный момент Вы исчерпали все попытки подтверждения email.\r\n\r\nПосле " . $kogdanew . "г. Вы сможете запросить новый код ИЛИ изменить " . $bundle['email'] . " на другой.";
      $en = "At this point, you have exhausted all attempts to confirm email.\r\n\r\nAfter " . $kogdanew . "y. you will be able to request a new code OR change " . $bundle['email'] . " another.";
      $texts = mova::lg($lg, $ua, $ru, $en);
      $bot->reply($texts);
      exit('ok');
      }

    // перевіряємо чи співпадає код
    // Код введено вірно
    if ($text == $bundle['kod']) {
      $ua = "Ви успішно зареєстровані! Приємної роботи!";
      $ru = "Вы успешно зарегистрированы! Приятной работы!";
      $en = "You are successfully registered! Have a nice job!";
      $answerOkReg = mova::lg($lg, $ua, $ru, $en);

      $bot->reply($answerOkReg);

      // Встановлюємо країну
      if ($bundle['country'] == '') {
        $coset = "`country`='{$botinfo['country']}',";
        } else {
        $coset = false;
        }

      // Save
      core::$db->query("UPDATE `" . load::$tg_bundle . "` SET {$coset} `kod`='ok', `trykod`= '0',`setmail`=NOW() WHERE `from_id`='" . $bundle['from_id'] . "' and `bot`='" . $bundle['bot'] . "'");
      // Про всяк випадок видаляємо мило з таблиці з диплінками
      core::$db->query("DELETE FROM `" . load::$tg_deeplink . "` WHERE `email` = '" . $bundle['email'] . "'");
      exit('ok');

      } else {
      // Код введён неверно
      $ostatoktry = 3 - $bundle['trykod'] - 1;
      $ua = "спроба спроби спроб";
      $ru = "попытка попытки попыток";
      $en = "attempt attempt attempt";
      $popitki = mova::lg($lg, $ua, $ru, $en);
      $try = core::declension($ostatoktry, $popitki);
      if ($ostatoktry >= '1') {
        $ua = "Змінити email на інший";
        $ru = "Изменить email на другой";
        $en = "Change email to another";
        $klavatext = mova::lg($lg, $ua, $ru, $en);
        // Сообщаем что код не верный
        $ua = "Код НЕВІРНИЙ. У вас залишилося **" . $try . "**.\r\n\r\nВведіть код із листа доставленого на " . $email . " або зміните email на інший.\r\n\r\nПри зміні email у Вас буде всього одна спроба на добу, на введення коду відправленого на новий email";
        $ru = "Код НЕВЕРНЫЙ. У Вас осталось **" . $try . "**.\r\n\r\nВведите код из письма доставленного на " . $email . " или измените email на другой.\r\n\r\nПри изменении email у Вас будет всего одна попытка в сутки, на введение кода отправленного на новый email";
        $en = "The code is INCORRECT. You have **" . $try . "**.\r\n\r\nEnter the code from the letter delivered to " . $email . " or change your email to another.\r\n\r\nWhen you change your email, you will have only one attempt per day to enter the code sent to the new email";
        $koderor = mova::lg($lg, $ua, $ru, $en);
        $bot->insertButton([["text" => $klavatext, "callback_data" => "btn_editemail"]]);
        $bot->setupTypeKeyboard('inline_keyboard');
        $bot->reply($koderor);
        } else {
        // We inform you that the client has exhausted the number of attempts to request a code for a day
        $ua = "На даний момент Ви вичерпали всі спроби підтвердження email.\r\n\r\nПісля " . $kogdanew . "р. Ви можете запросити новий код АБО змінити " . $bundle['email'] . " на іншій.";
        $ru = "На данный момент Вы исчерпали все попытки подтверждения email.\r\n\r\nПосле " . $kogdanew . "г. Вы сможете запросить новый код ИЛИ изменить " . $bundle['email'] . " на другой.";
        $en = "At this point, you have exhausted all attempts to confirm email.\r\n\r\nAfter " . $kogdanew . "y. you will be able to request a new code OR change " . $bundle['email'] . " another.";
        $texts = mova::lg($lg, $ua, $ru, $en);
        $bot->reply($texts);
        }
      // Сохраняем попытку
      core::$db->query("UPDATE `" . load::$tg_bundle . "` SET `trykod`= `trykod`+1 WHERE `from_id`='" . $user . "' and `bot`='" . $botinfo['name'] . "'");
      exit('ok');
      }
    } else {
    // если валидного кода нет
    $ua = "Надіслати новий код";
    $ru = "Выслать новый код";
    $en = "Send new code";
    $klavatextOne = mova::lg($lg, $ua, $ru, $en);
    $ua = "Змінити email на інший";
    $ru = "Изменить email на другой";
    $en = "Change email to another";
    $editmailnew = mova::lg($lg, $ua, $ru, $en);
    // Предлагаем прислать клиенту новый код
    $ua = "Термін дії коду підтвердження закінчився. Надіслати на " . $bundle['email'] . " новий код або змінити email на інший?";
    $ru = "Срок действия кода подтверждения истёк. Выслать на " . $bundle['email'] . " новый код или изменить email на другой?";
    $en = "The verification code has expired. Send to " . $bundle['email'] . " new code or change email to another?";
    $texts = mova::lg($lg, $ua, $ru, $en);
    $bot->insertButton([["text" => $klavatextOne, "callback_data" => "btn_newkod"]]);
    $bot->insertButton([["text" => $editmailnew, "callback_data" => "btn_editemail"]]);
    $bot->setupTypeKeyboard('inline_keyboard');
    $bot->reply($texts);

    if ($bundle['trykod'] >= '1') {
      // Обнуляем попытки
      core::$db->query("UPDATE `" . load::$tg_bundle . "` SET `trykod`= '0' WHERE `from_id`='" . $user . "' and `bot`='" . $botinfo['name'] . "'");
      }
    exit('ok');
    }
  }

if ($isAdmin !== true) {
  // перевіряємо чи не перевищено ліміт відправок специфічних повідомлень за день цим ID
  // ! Видаємо антиспам заглушку якщо користувач надіслав більше 50 таких повідомлень боту за сутки
  // Кнопки опрацюовуються вище і НЕ лімітуються
  // Повідомленя в публічні чати опрацюовуються вище і тому НЕ лімітуються
  // ! Лімітуються тільки надсилання боту команди та текстові повідомлення не описані вище
  $stopspamtext = tg::TelegramSpamProtect($isAdmin, $user, $host, $lg);
  if (!empty($stopspamtext)) {
    $bot->reply($stopspamtext);
    exit('stopspam');
    }
  }

// Якщо перед нами емаил
if (filter_var($text, FILTER_VALIDATE_EMAIL)) { //  and $bundle['kod'] !=
  // Стандартизируємо email
  $email = core::buildStandartEmail($text);

  if ($bundle['email'] == $email) {
    // Этот email уже привязан к Вашему аккаунту
    $ua = "Цей email вже прікріплений до Вашого облікового запису";
    $ru = "Этот email уже привязан к Вашему аккаунту";
    $en = "This email is already linked to your account";
    $ujeetotmail = mova::lg($lg, $ua, $ru, $en);
    $bot->reply($ujeetotmail);
    exit('ok');
    }

  // ! Нижче лише базова логіка
  // ! Цей тестовий бот НЕ додана можливість надситали листи
  // ! Для цього потрібно використовувати бібліотеки для smtp відправки, наприклад phpmailer та не тільки
  // В наступному релізі функціонал відправки буде додано

  // Цей email не належить даному ID?
  if ($bundle['email'] != $email) {
    // Шукаємо чи взагалі цей email є у зв'язках в базі
    // Бо email має бути унікальним для кожного користувача
    $tgBundle = tg::SearchTgEmail($email, $bundle['bot']);

    // Якщо НЕ знайшли email ні в кого І у даного клієнта email ще НЕ було вказано ДОСТОВІРНО
    if ($tgBundle == false and ($bundle['email'] == '' or ($bundle['email'] != '' and $bundle['kod'] != 'ok'))) {

      // Шукаємо чи отримував клієнт код перевірки раніше
      // Тривалість життя коду припустимо ДОБИ
      $kp = tg::testKodProverki($bundle);

      if ($kp['needre'] == true) {
        // Надсилаємо листа з кодом
        // ! для використання цієї функції в моєму репозиторії недостатньо бібліотек !!
        // tg::SendKod($botinfo, $user, $lg, $aco, $host, $isAdmin, $site, $infoemail, $email);
        // ! Надсилаэмо заглушку
        $bot->reply("Here we were supposed to send an email with the code, but we didn’t send it because the necessary libraries were not connected to the bot");
        exit('ok');
        }
      //
      $ua = "Змінити email на інший";
      $ru = "Изменить email на другой";
      $en = "Change email to another";
      $editmailnew = mova::lg($lg, $ua, $ru, $en);
      // Мы отправили код и просим ввести его
      $ua = "р. ми надіслали на " . $text . " лист з кодом.\r\n\r\nНемає листа? Перевірте спам. Щоб почати користуватися ботом, введіть КОД з листа або змініть email на інший";
      $ru = "г. мы отправили на " . $text . " письмо с кодом.\r\n\r\nНет письма? Проверьте спам.\r\n\r\nЧтобы начать пользоваться ботом, введите КОД из письма или измените email на другой";
      $en = "y. we sent to " . $text . " letter with code.\r\n\r\nNo letter? Check spam.\r\n\r\nTo start using the bot, enter the CODE from the letter or change your email to another";
      $texts = mova::lg($lg, "О", "В", "At") . " " . $kp['kodtime'] . mova::lg($lg, $ua, $ru, $en);
      $bot->insertButton([["text" => $editmailnew, "callback_data" => "btn_editemail"]]);
      $bot->reply($texts);
      exit('ok');
      }

    // Если НЕ нашли мыло в связках И у клиента уже есть подтверждённое мыло
    if ($tgBundle == false and $bundle['email'] != '' and $bundle['kod'] == 'ok') {
      $ua = "Змінити email на інший";
      $ru = "Изменить email на другой";
      $en = "Change email to another";
      $editmailnew = mova::lg($lg, $ua, $ru, $en);
      $ua = "Залишити старий email";
      $ru = "Оставить старый email";
      $en = "Leave old email";
      $leaveoldmail = mova::lg($lg, $ua, $ru, $en);

      // need to inform the client
      // Ask if the client really wants to change the current email to a new one  
      $ua = "Ваш поточний email:\r\n\r\n" . $email . "\r\n\r\nЗмінити його на новий:\r\n\r\n" . $text . "\r\n\r\n?";
      $ru = "Ваш текущий email:\r\n\r\n" . $email . "\r\n\r\nИзменить его на новый:\r\n\r\n" . $text . "\r\n\r\n?";
      $en = "Your current email:\r\n\r\n" . $email . "\r\n\r\nLet's change it to a new one:\r\n\r\n" . $text . "\r\n\r\n?";
      $oldmaileditnew = mova::lg($lg, $ua, $ru, $en);
      $bot->insertButton([["text" => $editmailnew, "callback_data" => "btn_editemail"]]);
      $bot->insertButton([["text" => $leaveoldmail, "callback_data" => "btn_oldmail"]]);
      $bot->reply($oldmaileditnew);
      exit('ok');
      }
    // Если у клиента ранее было указано подтверждённое мыло и клиент не в чёрном списке
    //if($bundle['email'] != '' and $bundle['kod'] == 'ok' and (empty($client) or $client['status'] != 'black') ){ /**/ }
    }


  } // Кінець обробки мила


// Адмінський функціонал реалізовується тут
if ($isAdmin === true) {
  // Показати меню адміна якщо в чат адмін ввів слово admin
  if (stristr($text, 'admin') !== false) {
    $bot->insertButton([["text" => "Admin test", "callback_data" => "button_one"]]);
    $bot->reply("Control bot");
    exit('ok');
    }

  } // admin


// Якщо клієнт натиснув старт, що йому писати
if ((!empty($command[0]) and $command[0] === '/start') or (!empty($text) and $text === '/start')) {
  // При старті або рестарті видаляємо всі попередні клавіатури
  // Бо часто кнопка веріфікації номера висить чи якесь меню яке при рестарті стає нелогічним
  // Для цього в телеграм API існує единий нині велосипедний механізм:
  // треба надіслати користувачі повідомлення без клавуатури і видалити попередні
  // Тож для цього можна просто ніби як формально привітатись
  // Не варто в цьому повідомлені надситали важливої інформації
  $bot->deleteKeyboard();
  $hi = mova::lg($lg, "Вітаємо!", "Привет!", "Hi!");
  $bot->reply($hi);
  $bot->setupTypeKeyboard('inline_keyboard');

  // Потім надсилаємо стартове повідомлення з важливою інформацією
  // (можливо спершу беремо з бази, а якщо там немає - використовуємо стандартне)
  // Брати з бази зручно, щоб воно могло бути унікальним і навіть "нестандартним"
  // !Але незручно у випадку багатомовного боту
  if ($botinfo['hello'] != '') {
    $response = $botinfo['hello'];
    } else {
    $ua = "Бот активовано";
    $ru = "Бот активирован";
    $en = "The bot is activated";
    $response = mova::lg($lg, $ua, $ru, $en);
    }
  $bot->reply($response);
  exit('ok');
  }

// ЗАГЛУШКА ВКІНЦІ
// Якщо ми прийшли сюди, значить користувач ввів команду яку бот не вміє опрацьовувати
$ua = "Я не вмію працювати з такими командами.\r\n\r\nЯ бот - який наразі не має штучного інтелекту. Я просто виконую наперед заготовлені конкретні процедурні завдання. Я не здатний підтримувати бесіду або відповідати на запити та повідомлення, тому що це не входить до зони моєї відповідальності.";
$ru = "Я не умею работать с такими командами.\r\n\r\nЯ бот - который не обладает искуственным интелектом. Я просто выполняю заранее заготовленные конкретные процедурные задачи. Я не способен как либо участвовать в беседах или отвечать на запросы и сообщения, так как это не входит в зону моей ответственности.";
$en = "I can't work with such commands.\r\n\r\nI am a bot - which does not yet have artificial intelligence. I simply perform pre-arranged specific procedural tasks. I am unable to participate in conversations or respond to requests and messages, as this is not my responsibility.";
$bot->insertButton([['text' => "GO TO START", 'callback_data' => "/start"]]);
$response = mova::lg($lg, $ua, $ru, $en);
$bot->reply($response);
exit('ok');