<?php

class tgBot
    {


    private $token = '';
    private $bot = '';
    // If this parameter is not empty, then we need to log the fact that this message was sent
    // to be able to reference it
    // and so as not to duplicate it in the future
    private $essence = '';
    private $host = '';
    private $isAdmin = false;
    public $keyboard = [];
    public $resize_keyboard = true; //
    public $type_keyb = false;      // 
    public $clearKeyboardAfterSend = true;   // Чи очищувати клавіатуру після відправки повідомлення
    public $response = false;       // Відповідь від API telegram
    public $testStatus = true;      // Чи треба 
    public $FullMode = true;        // Якщо false - класс не буде намагатись використовувати інші класи core:: tg:: ident:: тощо
    // MarkdownV2 - також непоганий варіан, але там буквально все треба екранувати\.\.\.
    // Тому я використовую по замовчуванню звичайний Markdown
    public $parseMode = 'Markdown';
    // Чи можна відправити повідомлення
    // Якщо $essence не пустий, то факт відправки логується і тоді можна
    public $needsend = 'needsend'; // По замовчуванню в будь якому випадку відправляємо
    public $chat = '';             // Отримувач повідомлення
    public $cqid = '';             // Куди надсилати ok callback після натискання на кнопку
    public function __construct($token, $bot, $host = false, $isAdmin = false)
        {
        $this->token = $token;
        $this->bot = $bot;
        $this->host = $host;
        $this->isAdmin = $isAdmin;
        }



    public function request($method, $params = [])
        {
        $url = 'https://api.telegram.org/bot' . $this->token . '/' . $method;
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);

        $out = json_decode(curl_exec($curl), true);
        curl_close($curl);

        // Auto-logging to INCOMING message
        // Needed for debugging only
        // After finishing debugging, you should disable logging as unnecessary
        $this->botLog($this->bot, $this->chat, $out, $method);

        if ($this->clearKeyboardAfterSend == true) {
            // Clear the keyboard after sending a notification
            // щоб попередня клавіатура не дублювалась у випадку якщо треба відправити другий запит в рамках одного виклику
            $this->keyboard = [];
            } else {
            // Якщо $this->clearKeyboardAfterSend = false
            // Значить видаленя клавіатури недозволено для одного конкретного НАСТУПНОГО повідомлення
            // Тож клавіатуру зараз не видаляємо, але готуємось видалити її після відправки чергового повідомлення
            $this->clearKeyboardAfterSend = true;
            }
        return $out;
        }

    // Якщо немає доступу до бази данних
    // значить бот запущено в режиме використання лише класу tgBot 
    // В такому жодні додаткові класи не будуть ініціалізовуватись
    public function liteModeOn()
        {
        $this->CancelTestStatus();
        $this->FullMode = false;
        }

    // Запускаємо тестування статусу тільки якщо є підключення до бази
    // Водночас ми ховаємо залежності від class tg
    public function getTestStatus()
        {
        if ($this->FullMode == true) {
            return tg::testStatus($this->chat, $this->response, $this->testStatus, $this->bot);
            }
        return false;
        }

    // Запускаємо логування діалогу в базу тільки якщо є підключення до бази 
    // Водночас ми ховаємо залежності від class tg
    public function getLogDialog($text)
        {
        if ($this->FullMode == true) {
            return tg::logDialog($this->essence, $this->response, $text, $this->chat);
            }
        return false;
        }

    // Якщо треба встановити відмінний від дефолтного parsemode
    public function setupParseMode($mode)
        {
        $this->parseMode = $mode;
        }

    // 
    public function setupTypeKeyboard($type_keyb)
        {
        $this->type_keyb = $type_keyb;
        }


    public function insertButton($button)
        {
        array_push($this->keyboard, $button);
        }

    // Якщо треба видалити клавіатуру
    public function deleteKeyboard()
        {
        $this->keyboard = true;
        $this->type_keyb = 'remove_keyboard';
        }

    public function insertKeyboard($keyboard)
        {
        $this->keyboard = $keyboard;
        }
    public function clearkb()
        {
        $this->keyboard = [];
        }
    // Видаляємо якусь кнопку з масиву клавіатури
    public function deleteButton($button_id)
        {

        }

    // Формуємо клавіатуру перед відправкою повідомлення
    public function makeKeyboard()
        {
        // По замовуванню завжди вважаємо, що це inline_keyboard якщо не вказано інше 
        if ($this->type_keyb == 'inline' or $this->type_keyb == 'inline_keyboard' or ($this->type_keyb == false and !empty($this->keyboard))) {
            //
            $this->type_keyb = 'inline_keyboard';

            } else if ($this->type_keyb == 'BACK') {
            $this->type_keyb = 'keyboard';
            $keyboard = [['BACK']];
            } else if ($this->type_keyb == 'remove_keyboard' or $this->type_keyb == 'remove' or $this->type_keyb == false) {
            $this->type_keyb = 'remove_keyboard';
            $this->keyboard = true;
            } else if ($this->type_keyb == 'menu') {
            //Выводим стандартное меню

            // Пока что заглушка
            $this->type_keyb = 'remove_keyboard';
            $this->keyboard = true;
            }
        // ПРИ ОТПРАВКЕ СООБЩЕНИЯ В КАНАЛ, ВСЕГДА НУЖНО ДОБАВЛЯТЬ inline_keyboard хотя бы с одной кнопкой
        // Это костыль!!!!!
        // Не помню чем он вызван...
        // Та й якось ніби й не треба...
        $chanel = mb_substr($this->chat, 0, 4);
        if ($chanel === '-100') {

            }
        }
    // Якщо хочемо логувати повідомлення - даємо їм імена
    public function giveLogName($name)
        {
        if (!empty($name) and !is_array($name)) {
            $this->essence = $name;
            }
        }

    // Перевіряємо, чи не надсилали раніше цільове повідомлення клієнту
    public function setHoldMinutes($holdtime)
        {
        // Если это не пустое, значит данное сообщение нельзя отправлять часто!
        // То есть можно отправлять если прошло время холда
        if (!empty($this->essence) and $this->FullMode == true) {
            $holdtime = '- ' . $holdtime . ' minutes';
            $this->needsend = core::tns($this->chat, 'tg', $this->essence, $holdtime);
            }
        }

    //
    public function CancelTestStatus()
        {
        $this->testStatus = false;
        }

    // В який саме чат відправляємо повідомлення
    // Якщо це особистий діалог з ботом, то відправляємо в особисті
    public function for($chat, $user = false)
        {
        if (empty($chat))
            return false;
        // Якщо чат вказується примусово  або це явно особистий з ботом діалог
        if ($user == $chat or empty($user)) {
            $this->chat = $chat;
            } else {
            // Якшо чат не співпадає з юзером, тобто діалог публічний
            // то відповідь відправляється в особисті
            // Якщо треба публічно, то просто встанови $bot->for($user);
            $this->chat = $user;
            }
        }

    public function gk()
        {
        return json_encode($this->keyboard);
        }
    public function gg()
        {
        return json_encode($this->type_keyb);
        }
    public function forCallBack($cqid)
        {
        $this->cqid = $cqid;
        }

    // Загальна функція відправки повідомлень
    public function reply($text)
        {

        //if(empty($this->chat)) return false;

        // Формуємо фінально клавіатуру
        $this->makeKeyboard();
        // ФОрмуємо повідомлення
        $reply_markup = [$this->type_keyb => $this->keyboard, 'one_time_keyboard' => true, 'resize_keyboard' => $this->resize_keyboard];
        if ($this->needsend == 'needsend') {
            // Передавать с сообщением можно только одну клавиатуру ИЛИ только 'remove_keyboard' => true
            // Но keyboard может оставать с прошлых сообщений, то есть её можно передать заранее или отдельным this->request в рамках одного this->reply
            // Хотя возможно её просто лучше не использовать??
            $this->response = $this->request('sendMessage', [
                "parse_mode" => $this->parseMode,
                "chat_id" => $this->chat,
                "text" => $text,
                'disable_web_page_preview' => true,
                'reply_markup' => json_encode($reply_markup)
            ]);
            } else {
            $this->response = [];
            $this->response['error'] = 'not need sent';
            }

        // Перевіряємо відповідь для того, щоб виявити:
        // Зміну ID чату отримувача
        // Відписку юзера від повідомлень бота
        $reID = $this->getTestStatus();
        // Якщо ми отримали не булеве значення, то це ми отримали новий id чату
        // А значить минуле повідомлення недоставлене по старому ID
        // Коли таке може відбутись?
        // Коли група/чат/канал стали супергруппою, а значить автоматично змінили ID
        if (is_bool($reID) === false) {
            $this->response = $this->request('sendMessage', [
                "parse_mode" => $this->parseMode,
                "chat_id" => $reID,
                "text" => $text,
                'disable_web_page_preview' => true,
                'reply_markup' => json_encode($reply_markup)
            ]);
            }
        // Если успешно отправлено и есть признак $essence, то логируем в базу данных в dialog
        $this->getLogDialog($text);
        //
        return $this->response;
        }

    // Дозволяє редагувати повідомлення
    // В першу чергу використовую для додавання кнопок до повідомлень каналу
    public function editMessage($message_id, $text = false)
        {
        if (empty($message_id))
            return false;
        $this->response = $this->request('editMessageReplyMarkup', [
            "chat_id" => $this->chat,
            "text" => $text,
            "message_id" => $message_id,
            'reply_markup' => json_encode([$this->type_keyb => $this->keyboard,])
        ]);
        return $this->response;
        }


    // призначено для відправки простих повідомлень без клавіатур, вишукувань та переускладнень які є в -> reply
    public function hello($text)
        {
        $type_keyb = 'remove_keyboard';
        $keyboard = true;
        $this->response = $this->request('sendMessage', [
            "parse_mode" => $this->parseMode,
            "chat_id" => $this->chat,
            "text" => $text,
            'disable_web_page_preview' => true,
            'reply_markup' => json_encode([$type_keyb => $keyboard, 'one_time_keyboard' => true, 'resize_keyboard' => $this->resize_keyboard])
        ]);
        return $this->response;
        }



    // tooltips відповідь на клік по кнопці
    // Якщо другий параметр true то  показуємо відповідь не просто у вспливашці, а show_alert на кшталт js alert
    public function answerCallback($text, $inalert = false)
        {
        if (empty($this->cqid))
            return false;

        $this->response = $this->request('answerCallbackQuery', ["callback_query_id" => $this->cqid, 'text' => $text, 'show_alert' => $inalert]);
        return $this->response;
        }


    public function deleteMessage($message_id)
        {
        $this->response = $this->request('deleteMessage', ["chat_id" => $this->chat, 'message_id' => $message_id]);
        return $this->response;
        }

    // Перевіряємо, чи є користувач в чаті
    // Це потрібно, щоб можна було продовжити реєстрацію користувача
    // Або також, аби перевірити, чи є бот в цільовому чаті 
    public function getChatMember($user, $chat = false)
        {
        if (!empty($chat)) {
            $this->chat = $chat;
            }
        $this->response = $this->request('getChatMember', ["chat_id" => $this->chat, 'user_id' => $user]);
        return $this->response;
        }


    public function createPoll($question, $answers = [])
        {
        $this->response = $this->request('sendPoll', ["chat_id" => $this->chat, 'question' => $question, "options" => $answers]);
        return $this->response;
        }
    // Only
    public function pictureReply($text, $url_of_picture, $textbtn = false, $ticket = false)
        {
        if (!empty($ticket)) {
            $type_keyb = 'inline_keyboard';
            $keyboard = [
                [
                    ["text" => $textbtn, "callback_data" => "retrn_" . $ticket]
                ]
            ];
            } else {
            $type_keyb = 'remove_keyboard';
            $keyboard = true;
            }
        $reply_markup = [$type_keyb => $keyboard, 'one_time_keyboard' => true, 'resize_keyboard' => $this->resize_keyboard];
        $sendarray = [
            "parse_mode" => $this->parseMode,
            "chat_id" => $this->chat,
            'disable_web_page_preview' => true,
            "caption" => $text,
            'reply_markup' => json_encode($reply_markup),
            "photo" => new CurlFile($url_of_picture)
        ];
        $this->response = $this->request('sendPhoto', $sendarray);
        tg::testStatus($this->chat, $this->response, $this->testStatus, $this->bot);
        return $this->response;
        }
    public function videoReply($text, $url_of_video)
        {
        $this->response = $this->request('sendVideo', ["parse_mode" => $this->parseMode, "chat_id" => $this->chat, "caption" => $text, "video" => $url_of_video]);
        tg::testStatus($this->chat, $this->response, $this->testStatus, $this->bot);
        return $this->response;
        }
    public function gifReply($text, $url_of_gif)
        {
        $this->response = $this->request('sendAnimation', ["parse_mode" => $this->parseMode, "chat_id" => $this->chat, "caption" => $text, "animation" => $url_of_gif]);
        tg::testStatus($this->chat, $this->response, $this->testStatus, $this->bot);
        return $this->response;
        }
    public function audioReply($text, $url_of_audio)
        {
        $this->response = $this->request('sendAudio', ["parse_mode" => $this->parseMode, "chat_id" => $this->chat, "caption" => $text, "audio" => $url_of_audio]);
        tg::testStatus($this->chat, $this->response, $this->testStatus, $this->bot);
        return $this->response;
        }
    public function voiceReply($text, $url_of_voice)
        {
        $this->response = $this->request('sendVoice', ["parse_mode" => $this->parseMode, "chat_id" => $this->chat, "caption" => $text, "voice" => $url_of_voice]);
        tg::testStatus($this->chat, $this->response, $this->testStatus, $this->bot);
        return $this->response;
        }
    public function videoNoteReply($url_of_vidnote)
        {
        $this->response = $this->request('sendVideoNote', ["chat_id" => $this->chat, "video" => $url_of_vidnote]);
        tg::testStatus($this->chat, $this->response, $this->testStatus, $this->bot);
        return $this->response;
        }

    public function setChatTitle($title)
        {
        $this->response = $this->request('setChatTitle', ["chat_id" => $this->chat, "title" => $title]);
        return $this->response;
        }

    // Можуть робити тільки адміни
    public function chatInviteLink($admin = false, $chat = false)
        {
        if (!empty($chat)) {
            $this->chat = $chat;
            }
        $this->response = $this->request('exportChatInviteLink', ["chat_id" => $this->chat]);
        if (!empty($this->response['result'])) {
            return $this->response['result'];
            }
        // Якщо помилка, то треба повідомляти про неї адміна
        if (!empty($this->response['error_code']) and !empty($admin)) {
            // Якщо надано ємаіл адміна, то шукаємо по милу його chat_id в базі
            if (filter_var($admin, FILTER_VALIDATE_EMAIL)) {
                $admin = tg::getTgIdByEmail($admin);
                }
            $this->for($admin);
            $this->hello('In tg group ' . $this->chat . ' error: ' . $this->arrayToKeyValueString($this->response));

            }
        return false;
        }

    // По замовчуваню запрошувальні лінки робляться одноразові
    // Одноразові зручні якщо ми хочемо реалізувати доступ в чат по підписці
    // Наприклад:
    // Користувач комунікує з ботом та якимось чином сплачує (заслуговує) доступ в чат
    // Використовуючи цю функцію бот дає користувачу лінк для доступу в чат
    public function createChatInviteLink($member_limit = 1)
        {
        $this->response = $this->request('createChatInviteLink', ["chat_id" => $this->chat, 'member_limit' => $member_limit]);
        if ($this->response['ok']) {
            return $this->response['result']['invite_link'];
            }
        }


    public function pinMessage($message_id)
        {
        $this->response = $this->request('pinChatMessage', ["chat_id" => $this->chat, "message_id" => $message_id]);
        return $this->response;
        }
    public function unpinMessage()
        {
        $this->response = $this->request('unpinChatMessage', ["chat_id" => $this->chat]);
        return $this->response;
        }
    public function tempban($userid, $time = false)
        {
        // if(is_int($userid) != true) return false;
        // time должен быть в unix (timestamp) формате 
        // Якщо час не вказано в цій функції, то банимо на завжди (до 2048 року)
        if ($time == false) {
            $time = 2473401362;
            }
        $this->response = $this->request('kickChatMember', ["chat_id" => $this->chat, "user_id" => $userid, "until_date" => $time]);
        return $this->response;
        }

    public function kick($userid)
        {
        $this->response = $this->request('kickChatMember', ["chat_id" => $this->chat, 'user_id' => $userid]);
        return $this->response;
        }

    // Отримуємо адмінів чату
    public function getChatAdmins()
        {
        $this->response = $this->request('getChatAdministrators', ["chat_id" => $this->chat]);
        return $this->response;
        }


    // Функція збереження на сервері файлу який відправили боту
    public function saveFile($file, $pathtosave)
        {
        $file_id = $file['file_id'];
        // Отримання інформації про файл
        $getFileResponse = json_decode(file_get_contents("https://api.telegram.org/bot{$this->token}/getFile?file_id={$file_id}"), true);
        // Завантаження файлу
        $file_path = $getFileResponse['result']['file_path'];
        $file_url = "https://api.telegram.org/file/bot{$this->token}/{$file_path}";
        $rez = file_put_contents($pathtosave, file_get_contents($file_url));
        return true;
        }


    // Функція що логує повідомлення
    public function botLog($bot, $id, $data, $type)
        {
        if (empty($data)) {
            return false;
            }
        // Перевірка наявності каталогу
        if (!is_dir($_SERVER['DOCUMENT_ROOT'] . "/bots/" . $bot)) {
            // Створення каталогу, якщо його немає
            // Останній параметр true робить рекурсивне створення каталогів
            mkdir($_SERVER['DOCUMENT_ROOT'] . "/bots/" . $bot, 0775, true);
            }
        $fpp = fopen($_SERVER['DOCUMENT_ROOT'] . "/bots/" . $bot . "/" . $type . "_" . $id . ".txt", "a");
        fwrite($fpp, mb_convert_encoding(print_r($data, true), 'UTF-8'));
        // fwrite($fpp,iconv('UTF-8', 'Windows-1251', print_r($data, true))); // не работает со смайликами
        fclose($fpp);
        }

    // РОбить з одномірного масиву рядок
    // Допомогає з массиву з помилкою сформувати рядок який можна відправити наприклад в телегу адміну
    public function arrayToKeyValueString($array)
        {
        // Перевірка, чи масив не пустий і є асоціативним
        if (empty($array) || !is_array($array)) {
            return '';
            }

        $keyValuePairs = [];

        foreach ($array as $key => $value) {
            $keyValuePairs[] = $key . ':' . $value;
            }

        // Об'єднуємо всі пари в один рядок
        return implode(',', $keyValuePairs);
        }

    }