<?php

class tg
  {

  //  чи відповідає рядок умовам ID телеграм каналів та груп
  static function validateTelegramId($input)
    {
    // Перевірка на дозволені символи (цифри та мінус)
    if (!preg_match('/^[0-9\-]+$/', $input)) {
      return false;
      }

    // Перевірка чи є мінус на початку
    if ($input[0] !== '-') {
      return false;
      }

    // Перевірка довжини рядка
    if (strlen($input) > 7 && strlen($input) <= 16) {
      return true;
      }
    return false;
    }


  // 
  static function testKodProverki($bundle)
    {
      if(empty($bundle['kodtime']) or empty($bundle['kod']) or empty($bundle['from_id']) or empty($bundle['bot']))
      return false;

    $timepayt = strtotime($bundle['kodtime']);
    $timeEnd = time() - (60 * 60 * 24);
    $infokod['needre'] = false;
    // Если прошло меньше допустимого, то считаем старый код активным
    if ($bundle['kod'] != '' and $bundle['kod'] != 'ok' and $timepayt > $timeEnd) {
      $infokod = array();
      $infokod['kod'] = $bundle['kod'];
      $infokod['kodtime'] = date('d.m.Yг. H:i ', $timepayt);
      } else {
      // Если код проверки протух, то...
      // Треба надсилати новий код!
      $infokod['needre'] = true;
      // Cбрасываем счётчик количества попыток ввода кода
      core::$db->query("UPDATE `" . Co::$tg_bundle . "` SET `trykod`= '0' WHERE `from_id`='" . $bundle['from_id'] . "' and `bot`='" . $bundle['bot'] . "'");
      
    }
    return $infokod;
    }

  static function SendKod($bi, $chat, $lg, $aco, $host, $isAdmin, $site, $info_email, $email = false)
    {
    $bot = new tgBot($bi['token'], $bi['name'], $host);
    $insem = false;
    $randkod = mt_rand(30010, 98980);
    // если передано мыло, то вносим его в базу
    if ($email != false) {
      $user_key = md5($email . Co::SECURITY);
      $user_key_to_base = md5($user_key);
      $insem = "`email`='" . $email . "',`user_key`='" . $user_key_to_base . "',";
      } else {
      $bundle = self::SearchTgBundle($chat, $bi['name']);
      if ($bundle['email'] == '') {
        $ua = "Помилка. Не вдалося надіслати код...\r\n\r\nПочніть процедуру з початку. Введіть email:";
        $ru = "Ошибка. Не удалось отправить код...\r\n\r\nНачните процедуру заново. Введите email:";
        $en = "Error. Failed to send code...\r\n\r\nStart the procedure from the beginning. Enter email:";
        $erorsendkod = mova::lg($lg, $ua, $ru, $en) . $host;
        $bot->reply($erorsendkod);
        exit('ok');
        }
      $email = $bundle['email'];

      }
    // Отправляем письмо
    if ($isAdmin === false) {
      // Важно, чтобы письмо с соответствующим переводом было на сервере!!!!
      $templateObject = new templateMail('tg/binding_bot_' . $aco, $site);
      // отключаем шапку и футер
      //$templateObject->useHeaderAndFooter(false);
      // передаем массив с клиентом
      //$templateObject->setClientByRecord();
      $templateObject->setParam('binding_bot', $randkod);
      $templateObject->setParam('nameslogan', $bi['slogan']);
      // получаем итоговое письмо
      $message = $templateObject->buildMessage();
      $ua = "Код перевірки для активації Telegram бота ";
      $ru = "Проверочный код для привязки Telegram бота ";
      $en = "Verification code to activate the Telegram bot ";
      $subj = mova::lg($lg, $ua, $ru, $en) . $host;
      Mail::sendMail($site, $info_email, $email, $subj, $message);
      $ua = "Ми ВЖЕ доставили на " . $email . " лист з кодом.\r\n\r\nЩоб продовжити, введіть КОД з листа:";
      $ru = "Мы УЖЕ доставили на " . $email . " письмо с кодом.\r\n\r\nЧтобы продолжить, введите КОД из письма:";
      $en = "We have ALREADY delivered to " . $email . " letter with a code.\r\n\r\nTo continue, enter the CODE from the letter:";
      $texts = mova::lg($lg, $ua, $ru, $en) . $host;
      } else {
      // Поскольку ты админ, то письмо тебе не отправили, но как клиента в базу внесли
      $texts = "Since you are an admin, the letter was not sent to you, but as a client they entered into the database!";
      }
    $bot->reply($texts);
    // Сохраняем мыло,юзеркей и код
    $qw = "UPDATE `" . Co::$tg_bundle . "` SET {$insem} `kod`='" . $randkod . "',`kodtime`=NOW(), `trykod`= '0', `country` = '" . $aco . "' WHERE `from_id`='" . $chat . "' and `bot`='" . $bi['name'] . "'";
    core::$db->query($qw);
    // Удаляем мыло из таблицы с диплинками
    core::$db->query("DELETE FROM `" . Co::$tg_deeplink . "` WHERE `email` = '" . $email . "'");
    exit('ok');
    }


  // Ищем юзера в таблице с клиентами
  // и связываем две записи clients и bundle между собой
  // ЭТО НУЖНО ТОЛЬКО В КРЕДИТНОЙ СХЕМЕ, ЧТОБЫ можно было ДЕЛАТЬ РАССЫЛКУ в кредитной схеме
  static function updateTgClient($email, $id, $st = false)
    {
    if ($st == 'credit') {
      return false;
      }
    $stem = core::buildStandartEmail($email);
    $client = ident::getClientByEmail($email);
    if ($client and $client['TelegaID'] != '') {
      // Хм, незнаю как быть в данном случае, но вроде как просто устанавливаем новую связь...
      }
    // Сохраняем 
    core::$db->query("UPDATE `" . Co::$clients . "` SET `TelegaID` = '{$id}' WHERE `email`='{$stem}'");
    return true;
    }

  static function getTgIdById($id, $table = false)
    {
    if (empty($table)) {
      $table = Co::$partners;
      }
    $id = intval($id);
    if (empty($id))
      return false;
    $query = 'SELECT `TelegaID` FROM ' . $table . ' WHERE id = ' . $id . ' LIMIT 1';
    $record = core::$db->query($query)->fetch_assoc();
    if (empty($record)) {
      return false;
      }
    return $record['TelegaID'];
    }

  static function getTgIdByEmail($email, $table = false)
    {
    if (empty($table))
      $table = Co::$partners;
    if (empty($id))
      return false;
    $query = 'SELECT `TelegaID` FROM ' . $table . ' WHERE email = ' . $email . ' LIMIT 1';
    $record = core::$db->query($query)->fetch_assoc();
    if (empty($record)) {
      return false;
      }
    return $record['TelegaID'];
    }
  static function getPartnerByTgUser($id, $table = false)
    {
    if (empty($table)) {
      $table = Co::$partners;
      }
    $id = intval($id);
    if (empty($id))
      return false;
    $query = "SELECT * FROM `$table` WHERE `TelegaID` = '$id'  LIMIT 1";
    $record = core::$db->query($query)->fetch_assoc();
    if (empty($record)) 
      return false;
    return $record;
    }

  static function getService($id)
    {
    $id = intval($id);
    if (empty($id))
      return false;
    $query = "SELECT * FROM `".Co::$tg_service."` WHERE `from_id` = '$id'  LIMIT 1";
    $record = core::$db->query($query)->fetch_assoc();
    if (empty($record))
      return false;
    return $record;
    }
    // Якщо це автономний бот без сайту, то в нього явно мають бути додаткові налаштування 
    // які розміщені в окремій таблиці
  static function getBotInfoDop($id)
    {
    $id = intval($id);
    if (empty($id))
      return false;
    $query = "SELECT * FROM `tg_bots_dop` WHERE `from_id` = '$id'  LIMIT 1";
    $record = core::$db->query($query)->fetch_assoc();
    if (empty($record))
      return false;
    return $record;
    }
  // Отримує перелік міст в яких надавач послуг надає послуги
  static function getCities($seci)
    {
    if (empty($seci))
      return false;
    // Підготовка SQL-запиту
    $ids = implode(',', array_map('intval', $seci));
    $query = "SELECT `id`, `city`, `region` FROM `service_city` WHERE `id` IN ($ids)";

    // Виконання запиту
    $result = Core::$db->query($query);

    // Створення багатовимірного масиву
    $listCity = [];
    while ($row = $result->fetch_assoc()) {
      $listCity[$row['id']] = ['city' => $row['city'], 'region' => $row['region']];
      }
    $result->close();
    return $listCity;
    }
  static function getOnlyCities($listCity)
    {
    $onlyCity = [];
    foreach ($listCity as $cityData) {
      $onlyCity[] = $cityData['city'];
      }
    return $onlyCity;
    }
  static function makeDelCityBtnService($listCity, $bot)
    {
    if (count($listCity) <= 7) {
      foreach ($listCity as $id => $cityData) {
        $bot->insertButton([["text" => $cityData['city'] . " (" . $cityData['region'] . ")", "callback_data" => "citydel_" . $id]]);
        }
      } else {
      $buttons = [];
      $i = 0;
      foreach ($listCity as $id => $cityData) {
        if ($i % 2 == 0) {
          $buttons[] = [
            ["text" => $cityData['city'] . " (" . $cityData['region'] . ")", "callback_data" => "citydel_" . $id]
          ];
          } else {
          $buttons[count($buttons) - 1][] = ["text" => $cityData['city'] . " (" . $cityData['region'] . ")", "callback_data" => "citydel_" . $id];
          }
        $i++;
        }
      foreach ($buttons as $button) {
        $bot->insertButton($button);
        }
      }
    }
    // Фактично допомогає перевірити, чи не вказував надавач послуг вже раніше це місто
  static function findCityId($listCity, $region, $city)
    {
    // Перевіряємо, чи $city не пустий
    if (!empty($city)) {
      // Проходимо по всьому масиву $listCity
      foreach ($listCity as $id => $cityData) {
        // Перевіряємо на відповідність і $city, і $region
        if ($cityData['city'] === $city && $cityData['region'] === $region) {
          return $id; // Повертаємо знайдений id
          }
        }
      } else {
      // Якщо $city пустий, перевіряємо лише $region
      foreach ($listCity as $id => $cityData) {
        if ($cityData['region'] === $region) {
          return $id; // Повертаємо знайдений id
          }
        }
      }
    return null; // Якщо нічого не знайдено, повертаємо null
    }

  static function getBotInfo($nameBot, $status = 'work')
    {
    $query = "SELECT * FROM `tg_bots` WHERE `name` = '{$nameBot}' and `status` = '{$status}' LIMIT 1";

    $bi = core::$db->query($query)->fetch_assoc();
    if (empty($bi)) {
      return false;
      }
    return $bi;
    }

    static function insertCity($provider_id, $city, $region, $country){
    // Використання TRANSACTION для забезпечення цілісності даних
    core::$db->begin_transaction();
    $query = "
        INSERT INTO `service_city` (`city`, `region`, `country`, `worker_id`, `date_first`)
        VALUES (?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
            `worker_id` = IF(FIND_IN_SET(VALUES(`worker_id`), `worker_id`) = 0, 
                             IF(`worker_id` = '', VALUES(`worker_id`), CONCAT(`worker_id`, ',', VALUES(`worker_id`))), 
                             `worker_id`),
            `date_first` = IF(`date_first` IS NULL, NOW(), `date_first`)
    ";

    $stmt = core::$db->prepare($query);
    $stmt->bind_param('ssss', $city, $region, $country, $provider_id);
    $stmt->execute();

    // Отримання останнього вставленого або оновленого ID
    $last_id_query = "
        SELECT `id` FROM `service_city`
        WHERE `city` = ? AND `region` = ? AND `country` = ?
    ";

    $stmt = core::$db->prepare($last_id_query);
    $stmt->bind_param('sss', $city, $region, $country);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    // Завершення транзакції
    core::$db->commit();

    $stmt->close();

    return $row['id'];

    }
  // Логуємо це повідомлення як частину діалогу
  static function logDialog($essence, $a, $text, $chat)
    {
    if (!empty($essence) and !empty($a['ok']) and $a['ok'] == '1') {
      $newtext = core::$db->real_escape_string($text);
      //$newtext = addslashes($text);
      $insert = core::$db->query("INSERT INTO `dialogue` SET `tg` = '{$chat}', `essence`= '{$essence}', `date` = NOW(), `message` = '{$newtext}', `message_id` = '{$a['result']['message_id']}'"); // 
      return true;
      }
    return false;
    }


  // Антиспам заглушка для телеграм бота
  static function TelegramSpamProtect($isAdmin, $fromid, $host, $lg)
    {
    if ($isAdmin !== true) {
      // Пишем попытку отправить форму
      $iptest = core::IPspamProtection($fromid, $host);
      if ($iptest['black'] === 'black') {
        $ua = "УПС, сьогодні Ви перевищили ліміт адекватності!\r\n\r\nВаші наступні повідомлення сьогодні будуть ігноруватися!\r\n\r\nЗберіться з думками, потоваришуйте з адекватністю і приходьте завтра!";
        $ru = "УПС, сегодня Вы превысили лимит адекватности!\r\n\r\nВаши последующие сообщения сегодня будут игнорироваться!\r\n\r\nСоберитесь с мыслями, подружитесь с адекватностью и приходите завтра!";
        $en = "Oops, today you exceeded the limit of adequacy!\r\n\r\nYour further messages today will be ignored!\r\n\r\nCollect your thoughts, make friends with adequacy and come back tomorrow!";
        return mova::lg($lg, $ua, $ru, $en);
        }
      }
    // Все добре
    return false;
    }

  // Перевіряємо чи треба мовчати в цьому чаті і якщо треба, то мовчимо
  static function silentInChat($s,$chat){
    if (!empty($s) and !empty($chat)) {
      $some = explode(",", $s);
      array_walk($some, 'core::trva');
      if (in_array($chat, $some)) {
        exit('ok');
        }
      }
    return false;
  }

  static function SearchTgBundle($user, $bot = false)
    {
    if (empty($user))
      return false;
    $searchbot = false;
    if ($bot != false) {
      $searchbot = " and `bot`='" . $bot . "'";
      }
    $result = core::$db->query("SELECT * FROM `" . Co::$tg_bundle . "` WHERE `from_id`='" . $user . "'" . $searchbot);
    $stroki = $result->num_rows;
    if ($stroki == 0) {
      return false;
      }
    if ($stroki == 1) {
      return $result->fetch_array(MYSQLI_ASSOC);
      } else {
      // Если строк больше, значит клиент регался в другом нашем боте ранее и можно подтянуть оттуда информацию
      // Но пока заглушка
      return false;
      }

    }


  // Прив'язуємо раніше відвязаний аккаунт
// Вносимо всі зміни щоб відновити прив'язку
  static function UpdateTgBundle($user, $email, $st, $bot)
    {
    // В кредитной схеме тоже нужно вернуть привязку
    if ($st == 'credit') {
      core::$db->query("UPDATE `" . Co::$clients . "` SET `TelegaID` = '{$user}' WHERE `email`='{$email}'");
      }
    core::$db->query("UPDATE `" . Co::$partners . "` SET `TelegaID` = '{$user}' WHERE `email`='{$email}'");
    // В таблице тг аккаунтов нужно пометить аккаунт доступным для рассылок и привязать к эмаил аккаунту
    core::$db->query("UPDATE `" . Co::$tg_bundle . "` SET `mst` = '',`email`='$email' WHERE `from_id`='{$user}'");

    return true;

    }




  static function SearchTgEmail($email, $bot = false)
    {
    $stem = core::buildStandartEmail($email);
    // Ищем только те мыльники которые подтверждены
    $result = core::$db->query("SELECT * FROM `" . Co::$tg_bundle . "` WHERE `email`='" . $stem . "' and `kod` = 'ok' and `bot`='{$bot}'");
    $stroki = $result->num_rows;
    if ($stroki == 0) {
      return false;
      }
    if ($stroki == 1) {
      return $result->fetch_array(MYSQLI_ASSOC);
      } else {
      // Если строк больше, то явно какой-то сбой...
      return false;
      }

    }

  static function SearchTgByKey($mdkey, $bot)
    {
    // Ищем только те мыльники которые подтверждены
    $result = core::$db->query("SELECT * FROM `" . Co::$tg_bundle . "` WHERE `user_key`='{$mdkey}' and `kod` = 'ok' and `bot`='{$bot}'");
    if ($result->num_rows == 0) {
      $result->close();
      return false;
      }
    if ($result->num_rows == 1) {
      $r = $result->fetch_array(MYSQLI_ASSOC);
        $result->close();
      return $r;
      } else if ($result->num_rows > 1) {
      $result->close();
      // Если строк больше, то явно какой-то сбой...
      return false;
      }

    }

  static function SearchServiceOrder($user, $status = false)
    {
    $result = core::$db->query("SELECT * FROM `service_order` WHERE `user`='{$user}' and `status` != 'cancel' and `status` != 'close'");
    if ($result->num_rows == 0) {
      $result->close();
      return false;
      }
    if ($result->num_rows == 1) {
      $r = $result->fetch_array(MYSQLI_ASSOC);
      $result->close();
      return $r;
      } else if ($result->num_rows > 1) {
      $result->close();
      // Если строк больше, то явно какой-то сбой...
      return false;
      }

    }


  static function SearchTgUsername($username, $full = false)
    {
    if (!empty($full)) {
      $full = '*';
      } else {
      // Якщо шукажмо не повну анкету, то видаємо тільки from_id
      $full = "`from_id`";
      }
    $result = core::$db->query("SELECT $full FROM `" . Co::$tg_bundle . "` WHERE `username`='{$username}' LIMIT 1");
    if ($result->num_rows == 0)
      return false;

    return $result->fetch_array(MYSQLI_ASSOC);
    }

  // Всегда при рассылках и не только, проверяем не забанил ли юзер бота
// если юзер забанил бота, то удаляем его из списка рассылки на следующий раз
  static function testStatus($chatid, $status, $bot, $type = false)
    {

    if (!empty($status) and !empty($status['ok']) and !empty($status['description']) and $status['ok'] == false) {
      if ($status['description'] == 'Forbidden: bot was blocked by the user') {
        // В кредитной схеме нужно удалить привязку
        if ($type == 'credit') {
          core::$db->query("UPDATE `" . Co::$clients . "` SET `TelegaID` = '' WHERE `TelegaID`='{$chatid}'");
          }

        // Видаляємо контакт
        // ПРи вході  в кабінет система запитає в людини її новий контакт
        core::$db->query("UPDATE `" . Co::$partners . "` SET `TelegaID` = '' WHERE `TelegaID`='{$chatid}'");

        // В ботосхеме нужно пометить аккаунт НЕдоступным для рассылок
        core::$db->query("UPDATE `" . Co::$tg_bundle . "` SET `mst` = 'stop' WHERE `from_id`='{$chatid}' and `bot` = '$bot'");
        return true;
        }
      // Якщо група стала супергрупою, або змінила ID
      if (is_array($status['parameters']) and !empty($status['parameters']['migrate_to_chat_id'])) {
        // Зберігаємо новий Chat ID групи
        if ($type == 'event') {
          core::$db->query("UPDATE `" . Co::$club . "` SET `tg_chat` = '{$status['parameters']['migrate_to_chat_id']}' WHERE `tg_chat`='{$chatid}'");
          }
        core::$db->query("UPDATE `tg_group_setup` SET `chat` = '{$status['parameters']['migrate_to_chat_id']}' WHERE `chat`='{$chatid}'");
        // Відаємо новий чат ID аби продублювати повідомлення на нього
        return $status['parameters']['migrate_to_chat_id'];
        }

      }
    // Любую ошибку желательно логировать
    if (!empty($status['description'])) {

      return true;
      }

    return false;
    }

  // Знаходимо повідомлення
  static function searchDialog($user, $essence)
    {
    // Знаходимо найсвіжіший єдиний що відповідає вимогам
    $result = core::$db->query("SELECT * FROM `dialogue` WHERE `tg`='$user' and `essence`='$essence' and `message_id` != 'del' ORDER BY id DESC LIMIT 1");
    if ($result->num_rows == 1) {
      $obje = $result->fetch_assoc();
      $result->close();
      return $obje;
      }
    return false;
    }
  // відмічаємо повідомлення як видалене
  static function delDialog($user, $essence, $mid)
    {
    return core::$db->query("UPDATE `dialogue` SET `message_id` = 'del' WHERE  `tg`='$user' and `essence`='$essence' and  `message_id` = '$mid'");
    }


  static function textNeedRegSite($st, $lg, $host)
    {


    $ua = "Щоб почати користуватися ботом, потрібно спочатку зареєструватися [на сайті](https://" . $host . ")";
    $ru = "Чтобы начать пользоваться ботом, нужно сначала зарегистрироваться [на сайте](https://" . $host . ")";
    $en = "To start use the bot, you must first register on the [website](https://" . $host . ")";
    return mova::lg($lg, $ua, $ru, $en);
    }

  // Отримуємо та Додаємо нову группу (чат) якщо такої ще немає
  public static function getGroup($chat,$chattype = false, $bot = false, $filter = false)
    {
    if ($chattype == "private")
      return false;
    $result = core::$db->query("SELECT * FROM `tg_group_setup` WHERE `chat`='$chat'");
    if ($result->num_rows == 0) {
      core::$db->query("INSERT INTO `tg_group_setup` SET `chat`='$chat', `bots`='$bot',`bot_filter`='$filter'");
      $gr['id'] = mysqli_insert_id(core::$db);
      $gr['chat'] = $chat;
      $gr['bots'] = $bot;
      $gr['bot_filter'] = $filter;
      return $gr;
      } else {
      return $result->fetch_assoc();
      }
    }

  public static function UpdateUserName($username, $id, $old = false)
    {
    if (empty($old) or ($old != $username)) {
      core::$db->query("UPDATE `" . Co::$tg_bundle . "` SET `username`= '$username' WHERE `id`='$id'");
      return true;
      }
    return false;
    }

  // Формуємо 5 кнопкову клавіатуру
  // idReg може бути як id так і назвою регіону
  public static function makeAbcKeyboard($arr,$name,$idReg,$locale)
    {
    // Сортуємо масив за алфавітом з урахуванням української локалі
    $collator = collator_create($locale);
    usort($arr, function ($a, $b) use ($collator) {
      return $collator->compare($a['name'], $b['name']);
      });
      
    $keyboard = [];
      if(!empty($idReg)){
      if (ctype_digit($idReg) !== true) {
          $idRegs = core::getRegionByName($idReg);
          $idReg = $idRegs['id'];
        }
      $idReg = "_" . $idReg;
      }
    // Якщо кількість областей 7 або менше, тоді можна просто вивести повні назви областей
    if (count($arr) <= 7) {
      foreach ($arr as $ar) {
        $keyboard[] = [
          [
            'text' => $ar['name'],
            'callback_data' => 'choose_'.$name.$idReg.'_' . $ar['id']
          ]
        ];
        }
      return $keyboard; // Повертаємо масив
      }

    // Інакше, групувати за першими літерами
    $letters = [];
    foreach ($arr as $ar) {
      $firstLetter = mb_strtoupper(mb_substr($ar['name'], 0, 2));
      if (!isset($letters[$firstLetter])) {
        $letters[$firstLetter] = [];
        }
      $letters[$firstLetter][] = $ar['id'];
      }

    // Створення кнопок для кожної літери
    $buttons = [];
    foreach ($letters as $letter => $ids) {
      $callback_data = 'choose_' . $name . $idReg . '_' . implode('_', $ids);
      $buttons[] = [
        'text' => $letter,
        'callback_data' => $callback_data
      ];
      }

    // Розподіл кнопок по рядках, максимум 5 в рядку
    $row = [];
    foreach ($buttons as $index => $button) {
      $row[] = $button;
      // Додавати рядок в клавіатуру, коли досягли 5 кнопок в рядку або це остання кнопка
      if (count($row) == 5 || $index == count($buttons) - 1) {
        $keyboard[] = $row;
        $row = [];
        }
      }

    return $keyboard; // Повертаємо масив
    }


  } // class tg