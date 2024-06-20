<?php
ini_set('display_errors',1);
error_reporting(E_ALL);
// Инклудим классы. Кажется каким-то велосипедом делать это тут, но я ж недалёкий


class tgBot{


    private $token = '';
    private $bot = '';
    public $st = '';
    private $essence = '';
    private $host = '';
    private $isAdmin = false;
    public $keyboard = [];
    public $resize_keyboard = true;
    public $type_keyb = false;
    public $parseMode = 'Markdown';   // MarkdownV2 - ще також можна, але там все треба екранувати\.\.\.
    public $needsend = 'needsend';    // По замовчуванню в будь якому випадку відправляємо повідомлення
    public $chat = '';                // Отримувач повідомлення
    public $cqid = '';                // Куди надсилати ok callback після натискання на кнопку
    public function __construct($token,$bot,$host,$isAdmin = false){
        $this->token = $token;
        $this->bot = $bot;
        $this->host = $host;
        $this->isAdmin = $isAdmin;
        //$this->st = $st;
    }
    


    public function request($method, $params = []){ //да-да, request на post-е. в коем-то веке.
        $url = 'https://api.telegram.org/bot' . $this->token .  '/' . $method;
        $curl = curl_init();
          
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_POST, true);
        //if($method == 'sendPhoto'){curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type:multipart/form-data"));curl_setopt($curl, CURLOPT_SAFE_UPLOAD, true); }
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params); 
          
        $out = json_decode(curl_exec($curl), true);
        curl_close($curl);
        // Автологирование в файлы ВХОДЯЩИХ сообщений
        if(empty($params['chat_id'])){
            $chat = false;
        } else {
            $chat = $params['chat_id'];
        }
        // Нужно только для отладки
        // После окончания отладки стоит отключить логирование за ненадобностью
        $this->botLog($this->bot,$chat,$out,$method);
        // Здається максимально логічним очищати клавіатуру після відправки запиту
        // На випадок якщо треба відправити другий запит, щоб попередня клавіатура не заважала
        $this->keyboard = [];
        return $out;
    }

    public function setupParseMode($mode){
        $this->parseMode = $mode;
    }
    public function setupTypeKeyboard($type_keyb)
        {
            $this->type_keyb = $type_keyb;
        }


    public function insertButton($button)
        {
         array_push($this->keyboard, $button);
        }

        public function deleteKeyboard(){
            $this->keyboard = true;
            $this->type_keyb = 'remove_keyboard';
        }

        public function insertKeyboard($keyboard){
            $this->keyboard = $keyboard;
        }
        public function clearkb()
        {
        $this->keyboard = [];
        }
        public function deleteButton($button_id){

        }

        // Формуємо клавіатуру перед відправкою повідомлення
        public function makeKeyboard(){
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
            // Если этот параметр не пустой, значит нам нужно логировать факт отправки этого сообщения 
            // чтобы иметь возможность на него ссылаться
            // и чтобы не дублировать его в будущем
            if(!empty($name) and !is_array($name)){
                $this->essence = $name;
            }
        }
        public function setHoldMinutes($holdtime){
            // Если это не пустое, значит данное сообщение нельзя отправлять часто!
            // То есть можно отправлять если прошло время холда
            if(!empty($this->essence)){
                $holdtime = '- '. $holdtime.' minutes';
                $this->needsend = core::tns($this->chat,'tg',$this->essence, $holdtime);
            }

        }
        // В який саме чат відправляємо повідомлення
        // Якщо це особистий діалог з ботом, то відправляємо в особисті
    public function for($chat,$user = false) {
        if (empty($chat)) return false;
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
    public function reply($text){

        //if(empty($this->chat)) return false;
    
        // Формуємо фінально клавіатуру
        $this->makeKeyboard();
        // ФОрмуємо повідомлення
        $reply_markup = [$this->type_keyb => $this->keyboard,'one_time_keyboard' => true,'resize_keyboard' => $this->resize_keyboard];
        if($this->needsend == 'needsend'){
            // Передавать с сообщением можно только одну клавиатуру ИЛИ только 'remove_keyboard' => true
            // Но keyboard может оставать с прошлых сообщений, то есть её можно передать заранее или отдельным this->request в рамках одного this->reply
            // Хотя возможно её просто лучше не использовать??
            $a = $this->request('sendMessage', ["parse_mode" => $this->parseMode, "chat_id" => $this->chat, "text" => $text,
            'disable_web_page_preview' => true,
            'reply_markup' => json_encode($reply_markup)]);
        } else {
            $a = [];
            $a['error'] = 'not need sent';
        }
        //core::vd($a);

        // Автоматично видаляємо прив'язку телегі у юзера
        $testStatus = tg::testStatus($this->chat,$a,$this->bot);
        // Якщо ми отримали не булеве значення, то це ми отримали новий id чату
        // А значить минуле повідомлення недоставлене і треба його продублювати в новий чат
        if(is_bool($testStatus) === false){
            $a = $this->request('sendMessage', ["parse_mode" => $this->parseMode, "chat_id" => $testStatus, "text" => $text,'disable_web_page_preview' => true,
                'reply_markup' => json_encode($reply_markup)]);
        }

        // Если успешно отправлено и есть признак $essence, то логируем в базу данных в dialog
        $logDialog = tg::logDialog($this->essence,$a,$text,$this->chat);

        return $a;
    }

    // Дозволяє редагувати повідомлення
    // В першу чергу використовую для додавання кнопок до повідомлень каналу
    public function editMessage($message_id,$text = false)
        {
        if (empty($message_id))
            return false;
        $a = $this->request('editMessageReplyMarkup', [
            "chat_id" => $this->chat,
            "text" => $text,
            "message_id" => $message_id,
            'reply_markup' => json_encode([$this->type_keyb => $this->keyboard,])
        ]);
        return $a;
        }


    // предназначено для отправки простых сообщений (вероятно только в личку, не в каналы) без изысков и переусложней которые есть в ->reply
    public function hello($text){
        $type_keyb = 'remove_keyboard';
        $keyboard = true;
        $a = $this->request('sendMessage', ["parse_mode" => $this->parseMode, "chat_id" => $this->chat, "text" => $text,'disable_web_page_preview' => true,
            'reply_markup' => json_encode([$type_keyb => $keyboard,'one_time_keyboard' => true,'resize_keyboard' => $this->resize_keyboard])]);
        return $a;
    }



    // Відповідь
    // Якщо другий параметр true то  показуємо відповідь у вспливашці show_alert
    public function answerCallback($text,$inalert = false){
        if(empty($this->cqid))
        return false; 

        $a = $this->request('answerCallbackQuery', ["callback_query_id" => $this->cqid, 'text' => $text,'show_alert' => $inalert]); 
        return $a;
    }


    public function deleteMessage($message_id){
        $a = $this->request('deleteMessage', ["chat_id" => $this->chat, 'message_id' => $message_id]);
        return $a;
    }

    // Перевіряємо, чи є користувач в чаті
    // Це потрібно, щоб можна було продовжити реєстрацію користувача
    // Або також, аби перевірити, чи є бот в цільовому чаті 
    public function getChatMember($user,$chat = false){
        if(!empty($chat)){
            $this->chat = $chat;
        }
        $a = $this->request('getChatMember', ["chat_id" => $this->chat, 'user_id' => $user]);
        return $a;
    }


    public function createPoll($question,$answers = []){
        $a = $this->request('sendPoll', ["chat_id" => $this->chat, 'question' => $question, "options" => $answers]); return $a;
    }
    // Only
    public function pictureReply($text,$url_of_picture,$textbtn = false,$ticket = false){
        if(!empty($ticket)){
            $type_keyb = 'inline_keyboard';
                $keyboard = [
                    [
                        ["text" => $textbtn, "callback_data" => "retrn_".$ticket]
                    ]
                ];
        } else {
            $type_keyb = 'remove_keyboard';
            $keyboard = true;
        }
            $reply_markup = [$type_keyb => $keyboard,'one_time_keyboard' => true,'resize_keyboard' => $this->resize_keyboard];
            $sendarray = ["parse_mode" => $this->parseMode, "chat_id" => $this->chat,'disable_web_page_preview' => true,
            "caption" => $text,
            'reply_markup' => json_encode($reply_markup), 
            "photo" => new CurlFile($url_of_picture)]; // realpath($url_of_picture) тест
        $a = $this->request('sendPhoto', $sendarray);
        //$this->botLog($this->bot,$chat,$a,'sendPhoto');
        // Автоматично видаляємо прив'язку телегі у юзера
        tg::testStatus($this->chat,$a,$this->bot);
        return $a;
    }
    public function videoReply($text,$url_of_video){
        $a = $this->request('sendVideo', ["parse_mode" => $this->parseMode, "chat_id" => $this->chat, "caption" => $text, "video" => $url_of_video]); 
        // Автоматично видаляємо прив'язку телегі у юзера
        tg::testStatus($this->chat,$a,$this->bot);
        return $a;
    }
    public function gifReply($text,$url_of_gif){
        $a = $this->request('sendAnimation', ["parse_mode" => $this->parseMode, "chat_id" => $this->chat, "caption" => $text, "animation" => $url_of_gif]);
        tg::testStatus($this->chat,$a,$this->bot);
        return $a;
    }
    public function audioReply($text,$url_of_audio){
        $a = $this->request('sendAudio', ["parse_mode" => $this->parseMode, "chat_id" => $this->chat, "caption" => $text, "audio" => $url_of_audio]);
        tg::testStatus($this->chat,$a,$this->bot);
        return $a;
    }
    public function voiceReply($text,$url_of_voice){
        $a = $this->request('sendVoice', ["parse_mode" => $this->parseMode, "chat_id" => $this->chat, "caption" => $text, "voice" => $url_of_voice]);
        // Автоматично видаляємо прив'язку телегі у юзера
        tg::testStatus($this->chat,$a,$this->bot);
        return $a;
    }
    public function videoNoteReply($url_of_vidnote){
        $a = $this->request('sendVideoNote', ["chat_id" => $this->chat, "video" => $url_of_vidnote]);
        // Автоматично видаляємо прив'язку телегі у юзера
        tg::testStatus($this->chat,$a,$this->bot);
        return $a;
    }
    public function setChatTitle($title){
        $a = $this->request('setChatTitle', ["chat_id" => $this->chat, "title" => $title]);return $a;
    }
    // Можуть робити тільки адміни
    // Про всяк випадок збираємо інфу для інформування адміну
    public function chatInviteLink($adminemail,$chat = false){
        if (!empty($chat)) {
            $this->chat = $chat;
            }
        $a = $this->request('exportChatInviteLink', ["chat_id" => $this->chat]);
        if(!empty($a['result'])){
            return $a['result'];
        }
        // Якщо помилка, то треба повідомляти про неї адміна
        if(!empty($a['error_code']) and !empty($adminemail)) {
            $admin = tg::getTgIdByEmail($adminemail);
            $this->for($admin);
            $this->hello('In tg group '.$this->chat.' error: '. core::arrayToKeyValueString($a));

        }
        return false;
    }
    public function pinMessage($message_id){
        $a = $this->request('pinChatMessage', ["chat_id" => $this->chat, "message_id" => $message_id]);return $a;
    }
    public function unpinMessage(){
        $a = $this->request('unpinChatMessage', ["chat_id" => $this->chat]);return $a;
    }
    public function tempban($userid,$time = false){
        // if(is_int($userid) != true) return false;
        // time должен быть в unix (timestamp) формате 
        // Якщо час не вказано в цій функції, то банимо на завжди (до 2048 року)
        if($time == false){ $time = 2473401362; } 
        $a = $this->request('kickChatMember', ["chat_id" => $this->chat, "user_id" => $userid, "until_date" => $time]);return $a;
    }

    public function kick($userid)
        {
        $a = $this->request('kickChatMember', ["chat_id" => $this->chat, 'user_id' => $userid]);
        return $a;
        }
    // Отримуємо адмінів чату
    public function getChatAdmins(){
        $a = $this->request('getChatAdministrators', ["chat_id" => $this->chat]);return $a;
    }


    // Функція збереження файлу який хтось відправив боту

    public function saveFile($file,$pathtosave){
        $file_id = $file['file_id'];
        // Отримання інформації про файл
        $getFileResponse = json_decode(file_get_contents("https://api.telegram.org/bot{$this->token}/getFile?file_id={$file_id}"), true);
        // Завантаження файлу
        $file_path = $getFileResponse['result']['file_path'];
        $file_url = "https://api.telegram.org/file/bot{$this->token}/{$file_path}";
        $rez = file_put_contents($pathtosave, file_get_contents($file_url));
        return true;
    }


    // Текстовый лог ВХОДЯЩИХ сообщений
    // Хотя можно конечно логировать ещё и исходящие от клиента, но в этом нет особой нужды
    // Так как логирование нужно лишь для отладки
    public function botLog($bot,$id,$data,$type) {
        if(empty($data)){
            return false;
        }
        // Перевірка наявності каталогу
        if (!is_dir($_SERVER['DOCUMENT_ROOT'] . "/bots/" . $bot)) {
            // Створення каталогу, якщо його немає
            // Останній параметр true робить рекурсивне створення каталогів
            mkdir($_SERVER['DOCUMENT_ROOT'] . "/bots/" . $bot, 0775, true);
            }
        $fpp = fopen($_SERVER['DOCUMENT_ROOT'] . "/bots/".$bot."/".$type."_".$id.".txt","a");
        fwrite($fpp,mb_convert_encoding(print_r($data, true), 'UTF-8'));
        // fwrite($fpp,iconv('UTF-8', 'Windows-1251', print_r($data, true))); // не работает со смайликами
        fclose($fpp);
    }



}
