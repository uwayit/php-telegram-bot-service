<?php


class core
    {

    const DTZERO = '0000-00-00 00:00:00';
    const DZERO = '0000-00-00';

    static $db;
    static $database;


    static function init()
        {
        self::$db = MysqlConnection::getLocal();
        self::$database = MysqlConnection::getDb();

        // Режим работы
        load::setMode(load::$MODE);
        }

    // ВСЁ ЧТО СВЯЗАНО С ЗАЩИТОЙ ОТ ВЗЛОМА ЧЕГО УГОДНО В СИСТЕМЕ И ЛОГИРОВАНИЕМ ПРОБЛЕМ

    // Функция защищает пост запросы от ....
    static function securePostData()
        {
        foreach ($_POST as $k => $v) {
            if (is_array($v)) {
                // Якщо значення є масивом, очистимо його елементи
                foreach ($v as $key => $value) {
                    $_POST[$k][$key] = trim(self::$db->real_escape_string($value));
                    }
                } else {
                // Якщо значення не є масивом, просто очистимо його
                $_POST[$k] = trim(self::$db->real_escape_string($v));
                }
            }
        }




    // Получаем текущий домен очищенный от www
    static function getCurrentDomainByServerHttpHost()
        {
        // Пытаемся получить домен по SERVER_NAME
        if (isset($_SERVER["SERVER_NAME"]) and $_SERVER["SERVER_NAME"]) {
            // Проверяем есть ли у нас $_SERVER['HTTP_HOST']
            // Если есть, то вычленяем из него домен
            if (substr($_SERVER['HTTP_HOST'], 0, 4) == 'www.') {
                return substr($_SERVER['HTTP_HOST'], 4);
                } else {
                return $_SERVER['HTTP_HOST'];
                }
            }
        // але якщо ініціатор консольний (наприклад крон), 
        // то доведеться $argv[1] переданному из вне
        // Поки не зробив

        return false;
        }


    // Получаем настройки сайта по домену
    static function getSiteByDomain($domain)
        {
        $query = "SELECT * FROM `" . load::$sites . "` WHERE `url` = '$domain' LIMIT 1";
        $site = self::$db->query($query)->fetch_assoc();
        if (empty($site)) {
            return false;
            }
        return $site;
        }


    // Функція дозволяє очистити текст від уразливостей
    // Проганяємо текст через цю функцію в ідеалі завжди
    public static function safety($text, $options = [])
        {
        $defaults = [
            'trim' => true,
            'addslashes' => true,
            'strip_tags' => true,
            'escape' => true,
            'htmlspecialchars' => true
        ];
        $options = array_merge($defaults, $options);

        if ($options['trim']) {
            $text = trim($text);
            }
        if ($options['strip_tags']) {
            $text = strip_tags($text);
            }
        if ($options['addslashes']) {
            $text = addslashes($text);
            }
        if ($options['escape'] && self::$db) {
            $text = self::$db->real_escape_string($text);
            }
        if ($options['htmlspecialchars']) {
            $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
            }
        return $text;
        }

    // Функція чистить массив від небезпечних данних
    public static function safetyCleanArray($data)
        {
        $cleanedData = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $cleanedData[$key] = self::safetyCleanArray($value);
                } else {
                $cleanedData[$key] = self::safety($value);
                }
            }

        return $cleanedData;
        }

    //  Записуємо спробу відправлення форми і перевіряємо, чи не перевищений ліміт відправок форми за день
    // Якщо вказано, параметр test то просто перевіряемо факт - айпи в чорному списку сьогодні чи ні
    static function IPspamProtection($ip, $host, $response = 'not indicated', $count = 'count', $test = false, $iplog = false)
        {
        $firstban = false;
        $blackcount = false;
        $date = date('Y-m-d');
        // Ставимо ліміт
        // У разі великої популярності сервісу, а також у разі ускладнення схеми потрібно буде збільшити
        // Під ускладненням ми розуміємо утримання юзера на сайте
        // Тобто на ресурсі з яким юзер активно взаємодіє постійно, треба збільшити ліміт
        $toblack = '50';
        // Якщо це телеграм хук, то тут можуть бути інші ліміти
        if ($response = 'hook') {
            $toblack = load::adequacyLimit;
            }

        // Формуємо базовий масив
        $arr['black'] = '';
        if (empty($iplog)) {
            $result = self::$db->query("SELECT * FROM `" . load::$ip_log . "` WHERE `IP`='$ip' AND `date`='$date'");
            if ($result->num_rows > 1) {
                // Такого виникати неможе але логіку про всяк варто б продумати якось
                return false;

                } else if ($result->num_rows == 0) { // Если это первый запрос с текущего айпи впринципе
                self::insertRecord(load::$ip_log, ["date" => $date, "ip" => $ip, "count" => '1', "softcount" => '1']);
                $arr['softcount'] = '1';
                $arr['count'] = '1';
                return $arr;
                }
            if ($result->num_rows == 1) {
                $iplog = $result->fetch_array(MYSQLI_ASSOC);
                }
            }
        $arr['softcount'] = $iplog['softcount'];
        $arr['count'] = $iplog['count'];
        $arr['black'] = $iplog['black'];

        // Передаємо інформацію без блокування, тобто просто перевіряємо
        if ($test == 'test') {
            return $arr;
            }

        if ($arr['black'] == 'black') {
            $blackcount = "`blackcount` = `blackcount`+1,";
            } else if ($iplog['black'] == '' and $iplog['count'] >= $toblack) {
            $firstban = "`black` = 'black',  `domain_black` = '$host',";
            $arr['black'] = 'black';
            // Створюємо технічний тікет для контролю
            self::plog(load::EMAIL, 'IP spam with requests and therefore blocked ' . $host . ' on stage: ' . $response, 'uvaga');
            }
        self::$db->query("UPDATE " . load::$ip_log . " SET {$blackcount} {$firstban} `$count` = `$count`+1 WHERE `IP`='$ip' AND `date`='$date'");
        return $arr;

        }



    static function ajaxFormIPspamProtect($ip, $host, $isAdmin, $response = false)
        {
        // Если не админ, то записываем попытку отправки формы
        // И проверяем не превышен ли лимит отправок формы за день
        if ($isAdmin !== true) {
            // Пишем попытку отправить форму
            $iptest = self::IPspamProtection($ip, $host, $response);
            if ($iptest['black'] === 'black') {
                echo json_encode(array("otvet" => "block", "response" => "You exceeded the number of permissible requests for today. There is a suspicion that you are a robot."));
                exit;
                }
            }
        return true;
        }






    // Чи більше друге число за перше  ...
    static function isValidAgeRange($minAge, $maxAge)
        {
        $num1 = intval($minAge);
        $num2 = intval($maxAge);
        return $num2 >= $num1 + 1;
        }


    // Считаем возраст клиента по его дате рождения
    static function calcAge($day, $month, $year)
        {
        $day = (int) $day;
        $month = (int) $month;
        $year = (int) $year;
        if (is_integer($day) && is_integer($month) && is_integer($year)) {
            $month_age = date("m") - $month;
            if ($month_age < 0) {
                $year_age = (date("Y") - $year) - 1;
                } elseif ($month_age == 0) {
                $day_age = date("d") - $day;
                if ($day_age >= 0) {
                    $year_age = date("Y") - $year;
                    } else {
                    $year_age = (date("Y") - $year) - 1;
                    }
                } else {
                $year_age = date("Y") - $year;
                }
            $age = &$year_age;
            return $age;
            } else {
            return false;
            }
        }



    // Определение валюты клиента по стране
    // нужно для заполнения списков сумм, когда мы не знаем телефон клиента и сайт работает с несколькими странами
    // Данная функция не самый идеальный вариант... Вместе со способом хранения данных...
    // Валюту каждой страны приходится запрашивать (запросом к базе данных) отдельно. 
    // Сейчас ~ 10 стран и выходит 10 запросов (они конечно очень простые в маленькой таблице), но если стран будет 100?
    // было бы удобно и правильно получить валюты всех стран одним запросом и отдавать массив...

    static function getValFromCountry($country)
        {
        if (empty($country)) {
            return false;
            }
        $query = "SELECT `ISO_4217` FROM `countries` WHERE `prefix_ISO_3166` = '" . $country . "' LIMIT 1";
        $val = self::$db->query($query)->fetch_array(MYSQLI_ASSOC);
        // подобное возникать не должно и в случае возникновения админ должен уведомляться в идеале
        if (empty($val)) {
            return false;
            }
        // Если всё ок - отдаём страну
        return $val['ISO_4217'];
        }



    // коректно дописує за цифрами 'раз раза разів'
    // Але з мовами є нюанси... 
    // Більше заточена під слов'янські мови поки що
    static function ints($int, $str)
        {
        if (!is_array($str)) {
            $arrStr = explode(" ", $str);
            } else {
            $arrStr = $str;
            }
        $lastInt = $int;


        if ($lastInt >= 5 && $lastInt <= 20) {
            // Для цих чисел явно усюди множина
            return $arrStr[2];
            } else {
            // В англійській мові всі числа - множина (принаймні з tickets)
            $lastInt = substr($int, -1);
            if ($lastInt == 1) {
                return $arrStr[0];
                } else if ($lastInt >= 2 && $lastInt <= 4) {
                return $arrStr[1];
                } else {
                return $arrStr[2];
                }
            }
        }
    // По факту дублікат тої що вище...
    // Функція грамотно підставляє правильно схилену валюту
    // Потрібно використовувати частіше, але я забиваю...
    static function declension($digit, $expr, $onlyword = false)
        {
        if (!is_numeric($digit)) {
            return $digit;
            }
        if (!is_array($expr)) {
            $expr = array_filter(explode(' ', $expr));
            }
        if (empty($expr[2])) {
            $expr[2] = $expr[1];
            }
        $i = preg_replace('/[^0-9]+/s', '', $digit) % 100;
        if ($onlyword) {
            $digit = '';
            }
        if ($i >= 5 && $i <= 20) {
            $res = $digit . ' ' . $expr[2];
            } else {
            $i %= 10;
            if ($i == 1)
                $res = $digit . ' ' . $expr[0];
            elseif ($i >= 2 && $i <= 4)
                $res = $digit . ' ' . $expr[1];
            else
                $res = $digit . ' ' . $expr[2];
            }
        return trim($res);
        }


    // Функция переводит в человекочитаемый формат любую дату из базы данных SQL
    // self::fd('d.m.Yг. в H:i', date('d.m.Y H:i'));
    // self::fd('d.m.Yг.', $client['date']);
    static function fd($format, $raw)
        {
        if (empty($raw)) {
            return '';
            }
        $time = strtotime($raw);
        if (empty($time)) {
            return '';
            }
        $date = date($format, $time);
        return $date;
        }





    // Повертає оновлений рядок 
    // (додає в рядок новий елемент через кому, якщо його там ще не було до цього)
    // Та повідомляє чи був він там чи ні
    static function updateStringIds($ids, $new_id)
        {
        // Перетворюємо рядок в масив
        $ids_array = array_filter(explode(',', $ids));

        // Початковий стан зміненої змінної
        $is_modified = false;

        // Перевіряємо, чи немає в переліку нового id
        if (!in_array($new_id, $ids_array)) {
            // Якщо немає, додаємо новий id
            $ids_array[] = $new_id;
            $is_modified = true;  // Встановлюємо, що рядок був змінений
            }

        // Перетворюємо масив назад у рядок
        $updated_ids = implode(',', $ids_array);

        // Повертаємо масив з оновленим рядком і інформацією про зміну
        return [$updated_ids, $is_modified];
        }



    // Получаем столицу области
    static function getRegionByName($name)
        {
        // получаем из базы все регионы
        $result = self::$db->query("SELECT `id`,`metropolis` from `regions` where `region`='{$name}'");
        $region = $result->fetch_array(MYSQLI_ASSOC);
        $result->close();
        if ($region) {
            return $region;
            } else {
            // Отправляем тикет админу, так как данный ситуации должны проверяться ибо явно имеет место какой-то недочёт
            // отправка письма в разработке и не в приоритете ибо маловероятно исходя из текущей реализации и подхода
            return false;
            }
        }

    static function getRegionById($id)
        {
        // получаем из базы все регионы
        $result = self::$db->query("SELECT `region`,`metropolis` from `regions` where `id`='{$id}'");
        $region = $result->fetch_array(MYSQLI_ASSOC);
        $result->close();
        if ($region) {
            return $region;
            } else {
            return false;
            }
        }

    // Функція транслітерації
// Планується використовувати: 
// 1) для транслітерації перед нечітким пошуком за назвою
// 2) щоб гарно выдображати в урлі
// 3) бо в смехі shop важливо (в першу чергу для протидії шахрайству) щоб назви магазинів відрізнялись
    static function translitIt($str)
        {

        $str = mb_strtolower(trim($str));
        $tr = array(
            " " => "_",
            "'" => "",
            "Ї" => "YI",
            "ї" => "yi",
            "І" => "i",
            "і" => "i",
            "Ґ" => "G",
            "ґ" => "g",
            "Є" => "E",
            "є" => "e", // UA
            "а" => "a",
            "б" => "b",
            "в" => "v",
            "г" => "g",
            "д" => "d",
            "е" => "e",
            "ё" => "e",
            "ж" => "j",
            "з" => "z",
            "и" => "i",
            "й" => "y",
            "к" => "k",
            "л" => "l",
            "м" => "m",
            "н" => "n",
            "о" => "o",
            "п" => "p",
            "р" => "r",
            "с" => "s",
            "т" => "t",
            "у" => "u",
            "ф" => "f",
            "х" => "h",
            "ц" => "ts",
            "ч" => "ch",
            "ш" => "sh",
            "щ" => "sch",
            "ъ" => "y",
            "ы" => "yi",
            "ь" => "'",
            "э" => "e",
            "ю" => "yu",
            "я" => "ya"
        );
        return strtr($str, $tr);
        }


    // получение списка городов области проживания клиента
    // Якщо $filter не пустий і сайт просить нас фільтрувати, значить треба профільтрувати список
    // Регіон може бути навіть ID
    static function getAllSatellite($region, $country, $filter = false, $nf = false)
        {
        if (empty($country))
            return false;



        if (ctype_digit($region) === true) {
            $search = "`id`='{$region}'";
            } else {
            $search = "`country`='{$country}' AND `region`='{$region}'";
            }
        // запашиваем запись в базе по назві регіону і в ідеалі по країні
        // Можна і без країни, але 
        // гіпотетично можемо отримати проблему якщо в різних країнах якийсь регіон буде називаться абсолютно однаково...
        $result = self::$db->query("SELECT * FROM `regions` WHERE $search");
        $info = $result->fetch_assoc();
        $result->close();
        if (empty($info)) {
            return false;
            }

        // если запись найдена добавим
        if (!empty($info["metropolis"])) {
            // по умолчанию список городов пустой
            $cities = [];
            $cities[] = $info["metropolis"];
            } else {
            return false;
            }
        //
        if (!empty($info["satellites"])) {
            $otherMista = explode(",", $info["satellites"]);
            $pakarr = array_merge($cities, $otherMista);
            $ottrim = array_map('trim', $pakarr);
            } else {
            $ottrim = array_map('trim', $cities);
            }
        $locale = self::getLocal($country);
        // Якщо вказано фільтрувати, то фільтруємо
        // Та сортуємо за алфавітом
        $resultDo = self::arrayСleaning($ottrim, $filter, $locale, $nf);



        return $resultDo;
        }

    // Отримуємо локаль (здебільшого на початку використовую для корректного сортування за алфавітом)
    static function getLocal($country)
        {
        if ($country == 'gb' or $country == 'en' or $country == 'us') {
            return 'en_GB';
            } else if ($country == 'ru') {
            return 'ru_RU';
            } else if ($country == 'ua' or $country == 'uk') {
            return 'uk_UA';
            } else {
            return 'uk_UA';
            }

        }

    // Функція, яку додав через напад РФ
    // Є області, регіони та міста
    // у яких так чи інакше неможливо організувати ДОСТАВКУ ТОВАРУ
    // У зв'язку з цим іноді потрібно видаляти зі списку регіонів або міст ті, в яких зараз працювати неможливо
    static function problemRegion($obl, $metropolis, $erReg)
        {

        if ($obl != false) {
            $testOne = stripos($erReg, $obl);
            if ($testOne != false) {
                return true;
                }
            }
        if ($metropolis != false) {
            $testTwo = stripos($erReg, $metropolis);
            if ($testTwo != false) {
                return true;
                }
            }
        // Если запрещённых регионов не обнаружено
        return false;
        }

    // Функція, яка видаляє з масива єлементи які є в другому масиві
    // Але тільки у випадку якщо це треба робити
    // Зазвичай використовую аби чистити список міст або регіонів від мусору
    static function arrayСleaning($goal, $drop, $locale, $need = false)
        {
        if (!empty($drop) and !empty($need)) {
            $resultArray = array_diff($goal, $drop);
            } else {
            $resultArray = $goal;
            }
        // Сортуємо масив за алфавітом
        $collator = collator_create($locale);
        usort($resultArray, function ($a, $b) use ($collator) {
            return $collator->compare($a, $b);
            });
        return $resultArray;
        }


    // Закрываем звёздами часть вывода (Использую для частичного скрытия имён и фио)
    static function stars($name)
        {
        $times = strlen(trim(substr($name, 4, 5)));
        $star = "";
        for ($i = 0; $i < $times; $i++) {
            $star .= "*";
            }
        return $star;
        }
    // Закрываем звёздами часть вывода (Использую для того чтобы укоротить длину кошелька и/или скрыть его часть)
    static function starsPurse($name)
        {
        // Це номер банківської карти, а все інше 
        if (strlen($name) == 16) {
            $name = str_repeat('*', strlen($name) - 4) . substr($name, -4);
            } else
            if (strlen($name) <= 39) {
                for ($i = 4; $i < 5; $i++) {
                    $name = substr_replace($name, "******", $i, 20);
                    }
                } else if (strlen($name) <= 47) {
                for ($i = 5; $i < 6; $i++) {
                    $name = substr_replace($name, "*******", $i, 30);
                    }
                } else {
                for ($i = 6; $i < 7; $i++) {
                    $name = substr_replace($name, "********", $i, 37);
                    }
                }
        return $name;
        }


    // Закрываем звёздами часть телефона
    static function starsPhone($phone)
        {
        for ($i = 5; $i < 11; $i++) {
            $phone = substr_replace($phone, "*", $i, 1);
            }
        return $phone;
        }



    // Функция обновления записей
    static function updateRecord($table, $record, $where)
        {
        if (empty($table))
            return false;
        if (empty($record))
            return false;
        if (empty($where))
            return false;

        // Формируем строки из массива
        $str_fields_value = '';
        foreach ($record as $field => $value) {
            if ($value === null)
                $value = 'NULL';
            else
                $value = "'" . $value . "'";
            $str_fields_value .= ',' . $field . '=' . $value;
            }
        $str_fields_value[0] = ' ';

        $query = 'UPDATE ' . $table . ' SET' . $str_fields_value . ' WHERE ' . $where;
        $result = self::$db->query($query);

        if ($result)
            return true;
        else
            return false;
        }

    // Функция вставки
    static function insertRecord($table, $record, $where = '')
        {
        if (empty($table))
            return false;

        if (empty($record))
            return false;

        // Формируем строки из массива
        $str_fields = '';
        $str_values = '';
        foreach ($record as $field => $value) {
            if ($value === null)
                continue;
            $str_fields .= ',' . $field;
            $str_values .= ',' . "'" . $value . "'";
            }
        $str_fields[0] = '(';
        $str_values[0] = '(';
        $str_fields .= ')';
        $str_values .= ')';
        //echo json_encode(array("otvet" => "error", "response" => $str_fields));exit;
        $query = 'INSERT INTO ' . $table . ' ' . $str_fields . ' VALUES' . $str_values;
        $result = self::$db->query($query);
        $id = mysqli_insert_id(self::$db);
        // Возвращаем id добавленной строки
        if ($result)
            return $id;
        else
            return false;
        }

    // Функция удаления записей
    static function deleteRecord($table, $where)
        {
        // Защищаем базу от неполных (некорректно сформированных) запросов
        if (empty($table))
            return false;

        if (empty($where))
            return false;

        $query = 'DELETE FROM ' . $table . ' WHERE ' . $where;
        //var_dump($query);
        //exit;
        $result = self::$db->query($query);

        if ($result)
            return true;
        else
            return false;
        }




    // Эта используется НЕ только в кредитной схеме
    // Разбирает строку с настройками которые различаются в зависимости от страны
    // И выдаёт настройку соотвествующую целевой стране
    // В строке ОБЯЗАТЕЛЬНО должна быть default настройка
    static function getset($country, $string)
        {

        // разделяем на страны
        $a = explode(';', $string);
        $set_by_countries = array();
        foreach ($a as $e) {
            if ($e <> '') {
                // отделяем префиксы стран от настроек
                $a2 = explode(':', $e, 2);
                $set_by_countries[$a2[0]] = $a2[1];
                }
            }
        if ($country and isset($set_by_countries[$country])) {
            $set = $set_by_countries[$country];
            } else {
            $set = $set_by_countries["default"]; // если нет такого, то берем из default
            }
        return $set;
        }






    // Новая функция добавления комментария/тикета/лога по партнёру
    // В случае успешного добавления строки возвращает её идентификатор
    // При наличии четвёртого параметра - редактирует status і answer (а не добавляет строку)
    static function plog($email, $comment, $status = '', $id = false, $st = false, $partner = false)
        {
        if ($id == false) {
            // В любом случае приводим мыло к типовому варианту
            $stem = self::buildStandartEmail($email);
            $isAdmin = ident::isAdmin();
            // Якщо це кліент, або адмін в будь якій схемі окрім маркетплейса
            if ($isAdmin !== true or ($isAdmin === true and $st != 'shop')) {
                $browser = new Browser();
                if (!empty($browser)) {
                    $ag = $browser->getPlatform() . ", " . $browser->getBrowser() . ", " . $browser->getVersion();
                    } else {
                    $ag = 'Not identified';
                    }
                // ip треба почати передавати в функцію
                // $ip = geo::GetIP();
                $ip = false;
                if (!empty($_COOKIE["fp"])) {
                    $fp = $_COOKIE["fp"];
                    } else {
                    $fp = 'For some reason missing';
                    }
                } else {
                // Якщо перед нами адмін маркетплейсу
                $ag = false;
                $ip = false;
                $fp = false;
                }
            // 
            $record = array(
                "email" => $stem,
                "date" => date('Y-m-d H:i:s'),
                "FingerPrint" => $fp, // Отпечаток браузера сохраняем тоже
                "status" => $status, // Нужен ли ответ?
                "ip" => $ip, // Сохраняем максимально достоверный айпишник КЛИЕНТА
                "Browser" => $ag, // Сохраняем операционку, браузер и его версию
                "comment" => $comment,
                // Якщо ничже не пусто, значить данний тикет лог ми можемо комусь показувати
                'for_partners' => $partner // $partner['email']
            );
            // Створюємо новий запис
            $id = self::insertRecord(load::$partners_log, $record);
            return $id;
            } else {
            // В данном случае выходит фиксируем наш ответ и новый статус тикета
            self::updateRecord(load::$partners_log, ['answer' => $comment, 'status' => $status], "id ='{$id}'");
            return true;
            }

        }


    // Ця функція дуже схожа на попередню, 
    // але минулу я не видаляю і користуюсь ними одночасно поки що
    // лише тому що надто душно перероблювати логіку давно зав'язану на plog
    // plog працює лише з partners_log і credit::clog
    // і загалом більше підлаштований під логування технічних статусів та роботу з нині поки покинутою кредитною схемою
    // allLog - може працювати з будь якою іншою таблицею з такою ж структурою і логікою
    static function allLog($table, $ip, $isAdmin, $st, $email, $comment, $type, $partner = false, $status = '', $id = false)
        {
        if ($id == false) {
            // В любом случае приводим мыло к типовому варианту
            $stem = self::buildStandartEmail($email);
            // Якщо це кліент, або адмін в будь якій схемі окрім маркетплейса
            if ($isAdmin !== true or ($isAdmin === true and $st != 'shop')) {
                $browser = new Browser();
                if (!empty($browser)) {
                    $ag = $browser->getPlatform() . ", " . $browser->getBrowser() . ", " . $browser->getVersion();
                    } else {
                    $ag = 'Not identified';
                    }
                if (!empty($_COOKIE["fp"])) {
                    $fp = $_COOKIE["fp"];
                    } else {
                    $fp = 'For some reason missing';
                    }
                } else {
                // Якщо перед нами адмін маркетплейсу
                $ag = false;
                $ip = false;
                $fp = false;
                }
            // 
            $record = array(
                "email" => $stem,
                "date" => date('Y-m-d H:i:s'),
                "FingerPrint" => $fp, // Отпечаток браузера сохраняем тоже
                "status" => $status, // Нужен ли ответ?
                "ip" => $ip, // Сохраняем максимально достоверный айпишник КЛИЕНТА
                "Browser" => $ag, // Сохраняем операционку, браузер и его версию
                "comment" => $comment,
                "type" => $type,
                // Якщо ничже не пусто, значить данний тикет лог ми можемо комусь показувати
                'for_partners' => $partner // $partner['email']
            );
            // Створюємо новий запис
            $id = self::insertRecord($table, $record);
            return $id;
            } else {
            // В данном случае выходит фиксируем наш ответ и новый статус тикета
            self::updateRecord($table, ['answer' => $comment, 'status' => $status], "id ='{$id}'");
            return true;
            }

        }







    // Загалом просто приймає параметри і віддає хеш
    static function getEventTicketsKey($table, $event, $user, $eventid)
        {
        // Поки не бачу можливості використовувати тут більш складні алгоритми
        // Бо мені потрібен короткий хеш
        // Хеш який не містить ніяких символів крім букв та цифр
        // Повертає 64 символи хешу
        return hash('sha3-256', $table . $event . $eventid . $user . load::SERVICE_KEY_SOLD);

        }


    // Для генерации реферального урла нужно использовать дефолтный домен
    // В идеале его нужно использовать ВСЕГДА, а не только когда site['active'] == 'ref'
    // ДА И ПРИ РАБОТЕ С ЛОКАЛЬНЫМ ДОМЕНОМ ТОЖЕ НУЖНО ЧТОБЫ МЫ ОБРАЩАЛИСЬ ЗА КАРТИНКАМИ К ДЕФОЛТНОМУ, А НЕ ЛОКАЛЬНОМУ
    static function searchDefDomain($site = false)
        {

        $query = "SELECT `url` FROM `" . load::$sites . "` WHERE `active` = 'default' LIMIT 1";
        $record = self::$db->query($query)->fetch_assoc();
        $hostdef = $record['url'];
        return $hostdef;

        }

    // Ми відштовхуємося від логіки, що:
    // 1. email це унікальний ідентифікатор користувача
    // 2. під одним email можна зареєструвати лише один аккаунт
    // В зв'язку з цим ми закриваємо цією функцією всі можливості множинних використань одного email
    static function buildStandartEmail($email)
        {

        $email = trim($email);
        $email = str_replace(' ', '', $email);
        $email = strtolower($email);
        // По умолчанию так и запишем если ничего не поменяем
        $StandartEmail = $email;
        /* Конвертируем все возможные вариации ЯНДЕКС ящиков в эталонный формат mail.mail@yandex.ru */
        if (preg_match('#yandex(\.[a-z]{2,3})+$#is', $email) || preg_match('#@ya.ru$#is', $email) || preg_match('#@narod.ru$#is', $email)) {
            list($box, $domen) = explode('@', $email, 2);
            $StandartEmail = $box . '@yandex.ru';
            $StandartEmail = str_replace('-', '.', $StandartEmail);
            }
        /* Конвертируем все возможные вариации ГУГЛ ящиков в эталонный формат mailmail@gmail.com  */
        if (stristr($email, '@gmail.com') || stristr($email, '@googlemail.com')) {
            list($box, $domen) = explode('@', $email, 2);
            $StandartEmail = str_replace('.', '', $box) . '@gmail.com';
            }
        /* Конвертируем все возможные вариации протонов  */
        if (stristr($email, '@pm.me') || stristr($email, '@proton.me')) { // || stristr($email, '@protonmail.com') // це здається окреме мило
            list($box, $domen) = explode('@', $email, 2);
            $StandartEmail = $box . '@proton.me';
            }
        return $StandartEmail;
        }


    // Получаем детальные нюансы по стране клиента
    static function getSetupByCountry($countries)
        {
        if (is_array($countries)) {
            $escapedCountries = array_map([self::$db, 'real_escape_string'], $countries);
            $countryList = "'" . implode("','", $escapedCountries) . "'";

            $query = "SELECT * FROM " . load::$countries . " WHERE prefix_ISO_3166 IN ($countryList)";
            $result = self::$db->query($query);

            $res = [];
            while ($row = $result->fetch_assoc()) {
                $res[$row['prefix_ISO_3166']] = $row;
                }

            return $res;
            } else {
            $query = "SELECT * FROM " . load::$countries . " WHERE prefix_ISO_3166 = '$countries' LIMIT 1";
            $SetupByCountry = self::$db->query($query)->fetch_assoc();
            if (empty($SetupByCountry)) {
                return false;
                }
            return $SetupByCountry;
            }
        }





    //
    static public function testRegion($region, $mst, $email, $st)
        {
        // Выходим если регион не указан
        if ($region == '') 
            return true;
        // надеюсь не будет возникать ибо нужно будет вручную править регион и mst клиента
        if ($mst != 'region_invalid') {
            $FindRegion = self::$db->query("SELECT * FROM regions WHERE `region`='{$region}'");
            // Если Регион клиента не найден в базе данных
            // АДМИНА уведомляем, клиента НЕ отвлекаем
            if (!$FindRegion) {
                if ($st == 'credit') {
                    $table = load::$clients;
                    } else {
                    $table = load::$partners;
                    }
                // Ставим в базе признак mst = region_invalid чтобы функция не заспамила нас письмами
                self::$db->query("UPDATE " . $table . " SET `mst` = 'region_invalid' WHERE `email` = {$email}");
                // Логируем и создаём тикет
                self::plog($email, "The client's region is not described in the database! You need to fix the region AND manually edit mst", 'need', false, $st);
                return false;
                }
            return true;
            }
        // $mst == region_invalid
        return false;
        }

    // генерує QR
// Каталог для збереження autodir
    static public function makeQR($hostdef, $autodir, $key, $qrtext, $size = '6', $margin = '1')
        {
        // Включаем библиотеку
        require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/phpqrcode/qrlib.php';
        // Перевірка наявності каталогу
        if (!is_dir($_SERVER['DOCUMENT_ROOT'] . '/files/qr/' . $hostdef . '/' . $autodir)) {
            // Створення каталогу, якщо його немає
            // Останній параметр true робить рекурсивне створення каталогів
            mkdir($_SERVER['DOCUMENT_ROOT'] . '/files/qr/' . $hostdef . '/' . $autodir, 0775, true);
            }

        // Путь и имя файла для сохранения QR-кода
        $filename = $_SERVER['DOCUMENT_ROOT'] . '/files/qr/' . $hostdef . '/' . $autodir . '/' . $key . '.png';

        // Перевіряємо чи немає вже такого файлу
        // Бо нашо його генерувати, якщо він вже є
        if (file_exists($filename)) {
            // Выводимо картинку
            // Але квитки не треба
            if ($autodir != load::$tickets) {
                readfile($filename);
                }
            return $filename;
            }


        // Ще тут якось можна визначати колір заднього фону
        // Граючись з 6-8 параметрами тут QRcode::png
        // але в мене не виходило
        $hexColor = '#404040'; // До прикладу
        list($r, $g, $b) = sscanf($hexColor, "#%02x%02x%02x");
        // Форматуємо RGB-значення в потрібний формат
        $rgbFormatted = sprintf("0x%02x%02x%02x", $r, $g, $b);


        // Якщо не хочемо зберігати файл одразу, а зробити це під час виводу, то просто передаємо null
        $filnam = null; // = $filename
        // Создаем буферизированный вывод
        ob_start(); // Сюди поміщається картинка на випадок, якщо ми хочемо її одразу вивести

        // Создаем QR-код
        QRcode::png($qrtext, $filnam, QR_ECLEVEL_L, $size, $margin); //Image Output


        // Получаем содержимое буфера
        $imageContent = ob_get_contents();

        // Очищаем буфер
        ob_end_clean();

        // Сохраняем изображение в файл
        file_put_contents($filename, $imageContent);

        // Выводимо картинку
        // Але квитки не треба
        if ($autodir != load::$tickets) {
            // Устанавливаем заголовки HTTP для изображения PNG
            header('Content-Type: image/png');
            header('Content-Length: ' . strlen($imageContent));
            // Выводим содержимое изображения
            echo $imageContent;
            }
        return $filename;
        }



    // Считаем сколько файлов по факту лежит в папке и выводим
    static public function dirToArray($dir)
        {
        $result = array();

        // Велосипед, но... 
        $dirr = str_replace('//', '/', $dir);

        $cdir = scandir($dirr);

        foreach ($cdir as $key => $value) {
            if (!in_array($value, array(".", ".."))) {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $value)) {
                    $result[$value] = self::dirToArray($dir . DIRECTORY_SEPARATOR . $value);
                    } else {
                    $result[] = $value;
                    }
                }
            }
        return $result;
        }



    // Функция делает первую букву кирилицы заглавной
    static public function mb_ucfirst($str, $encoding = 'UTF-8')
        {
        if (empty($str)) {
            return false;
            }
        $str = mb_ereg_replace('^[\ ]+', '', $str);
        $str = mb_strtolower($str, $encoding = 'utf-8');
        $str = mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding) .
            mb_substr($str, 1, mb_strlen($str), $encoding);
        return $str;
        }







    // Простая функция вывода echo только лишь админу
    // удобно использовать во время отладки кода без страха забыть убрать на продакшене
    // (хотя натупить и отправить в продакш все же можно указав второй параметр true)
    // core::ec('сообщение которое нужно вывести');
    static function ec($mes, $isAdmin = 'non')
        {
        if ($isAdmin == 'non' and $isAdmin !== true) {
            $isAdmin = ident::isAdmin();
            }
        if ($isAdmin === true) {
            echo $mes . '<br>';
            }
        }
    // Простая функция вывода var_dump только лишь админу
    // удобно использовать во время отладки кода без страха забыть убрать на продакшене
    // (хотя натупить и отправить в продакш все же можно указав второй параметр true)
    // core::vd('сообщение которое нужно вывести');
    static function vd($str, $isAdmin = 'non')
        {
        if ($isAdmin == 'non' and $isAdmin !== true) {
            $isAdmin = ident::isAdmin();
            }
        if ($isAdmin === true) {
            //echo print_r($str, true);
            var_dump($str);
            echo '<br>';
            }
        }




    // Перевірка чи корректно введено номер телефона
// Головне передавати їй корректні данні на вхід і ніяких проблем не буде
// приклад використання
    static public function validatePhoneNumber($input, $prefix = null)
        {
        // Перевірка чи починається зі знаку "+"
        if ($input[0] !== '+') {
            return false;
            }

        // Перевірка чи після "+" йдуть мінімум 11 та максимум 12 цифр
        $phoneNumber = substr($input, 1);
        $phoneNumberLength = strspn($phoneNumber, '0123456789');
        if ($phoneNumberLength < 11 || $phoneNumberLength > 12) {
            return false;
            }

        // Перевірка чи після "+" не йде "0"
        if ($phoneNumber[0] === '0') {
            return false;
            }

        // Перевірка чи в номері є лише цифри після "+"
        if ($phoneNumber !== str_replace(' ', '', $phoneNumber)) {
            return false;
            }

        // Додаткова перевірка для другого параметру
        if ($prefix !== null) {
            $prefixArray = explode(',', $prefix);
            $normalizedPrefixes = [];

            foreach ($prefixArray as $prefixValue) {
                $prefixValue = trim($prefixValue);

                if (strpos($prefixValue, '+') === 0) {
                    $prefixValue = substr($prefixValue, 1);
                    }

                if (!ctype_digit($prefixValue) || strlen($prefixValue) < 2 || strlen($prefixValue) > 3) {
                    return false;
                    }

                $normalizedPrefixes[] = $prefixValue;
                }

            foreach ($normalizedPrefixes as $prefixValue) {
                $phoneNumberPrefix = substr($phoneNumber, 0, strlen($prefixValue));
                if ($phoneNumberPrefix === $prefixValue) {
                    return true;
                    }
                }

            return false;
            }

        return true;
        }


    // Функція яка допомогає зробити trim елементів масиву
    static public function trva(&$value)
        {
        $value = trim($value);
        }


    // Якщо елемент S е в масиві A повертаємо тру
    // Якщо передано третій параметр, то перевіряємо чи початок $s співпадає з елемнтом з $a
    static public function inar($s, $a, $checkStart = false)
        {
        if (empty($s) or empty($a))
            return false;
        // Якщо передано строку а не масив, то так і задумано
        // Розбиваємо строку
        if (!is_array($a)) {
            $a = explode(",", $a);
            }

        array_walk($a, 'self::trva'); // Трімім елементи масиву
        if ($checkStart) {
            foreach ($a as $prefix) {
                if (strpos($s, $prefix) === 0) {
                    return true;
                    }
                }
            return false;
            } else {
            if (in_array($s, $a))
                return true;
            return false;
            }
        }

    // Перевіряємо, чи не надсилали раніше якесь повідомлення клієнту
    // Check if a certain message has been sent to the client before
    // $holdtime can be equal to any number of minutes, hours, days, you just need to write it in an understandable strtotime format
    static function tns($contact, $messendger, $essence, $holdtime = '-1 days')
        {

        // Выдаём ошибку если не передано
        if (empty($contact) or empty($messendger) or empty($essence)) {
            return 'error';
            }
        // Формируем дату
        $starttime = date('Y-m-d H:i:s', strtotime($holdtime));
        $result = self::$db->query("SELECT `id` FROM `dialogue` WHERE `$messendger`='$contact' and `essence`='$essence' and `date` >= '" . $starttime . "'");
        if ($result->num_rows > 0) {
            return 'stop';
            }
        return 'needsend';
        }

    // РОбить з одномірного масиву рядок
    // Допомогає з массиву з помилкою сформувати рядок який можна відправити наприклад в телегу адміну
    // Э близнюк в tg:: але це нормально
    private static function arrayToKeyValueString($array)
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

    /**
     * Отримуємо курс цільової пари
     * param $from НЕ може бути crypto
     */
    static function getObmenInfo($from, $for)
        {
        $query = "SELECT * FROM `currency_konvert` WHERE `from` = '{$from}' and `for` = '{$for}'";
        $ObmenInfo = self::$db->query($query)->fetch_assoc();
        if (empty($ObmenInfo)) {
            // Про всяк пробуємо навпаки
            // Це тупо, але не критично, бо рідко виникатимо якщо завжди пам'ятати що $from НЕ може бути crypto
            $query = "SELECT * FROM `currency_konvert` WHERE from = '{$for}' and for = '{$from}'";
            $ObmenInfo = self::$db->query($query)->fetch_assoc();
            if (empty($ObmenInfo))
                return false;

            }

        return $ObmenInfo;
        }



    // Приклад використання
    // $directory = '/шлях/до/вашої/папки';
    // $timeThreshold = strtotime('-1 week'); // Наприклад, видаляти файли старше тижня
    // core::cleanDirectory($directory, $timeThreshold);
    static function cleanDirectory($directory, $timeThreshold)
        {
        // Перевірка, чи існує папка
        if (!is_dir($directory)) {
            throw new InvalidArgumentException("Invalid directory: $directory");
            }

        // Перебираємо файли та каталоги у вказаній папці
        $files = glob("$directory/*");
        foreach ($files as $file) {
            // Ігноруємо, якщо це каталог "тека назад" (..)
            if (is_dir($file) && in_array(basename($file), ['.', '..'])) {
                continue;
                }

            // Перевіряємо час останньої модифікації файлу
            $fileModificationTime = filemtime($file);

            // Видаляємо файл, якщо його час створення менший за визначений часовий поріг
            if ($fileModificationTime < $timeThreshold) {
                unlink($file);
                }
            }

        // Видаляємо порожні папки після видалення файлів
        self::removeEmptyDirectories($directory);
        }

    private static function removeEmptyDirectories($directory)
        {
        $emptyDirectories = array_filter(glob("$directory/*"), 'is_dir');
        foreach ($emptyDirectories as $emptyDirectory) {
            // Рекурсивно видаляємо порожні каталоги
            self::removeEmptyDirectories($emptyDirectory);
            // Якщо каталог залишився порожнім, то видаляємо його
            if (count(glob("$emptyDirectory/*")) === 0) {
                rmdir($emptyDirectory);
                }
            }
        }




    // Перевіряє чи проходить людина по віку на івенти
    static function checkAgeRange($arAge, $birthYear)
        {
        $today = date('Y');
        $age = $today - $birthYear;
        if (!is_array($arAge)) {
            $arAge = explode("-", $arAge);
            }
        if ($age >= $arAge[0] && $age <= $arAge[1]) {
            return true;
            } else {
            return false;
            }
        }


    // Повертає найближчу дату до якої діє абонемент приймаючи число тижнів
// В масштабах одного тижня працює досить грубо, але на більшій кількості тижнів гарно
    static function findNearestDate($weeks)
        {
        // Отримуємо поточну дату
        $currentDate = new DateTime();

        // Знаходимо останній день (неділя) поточного тижня
        $currentDate->modify('this sunday');

        // Додаємо вказану кількість тижнів
        $currentDate->add(new DateInterval('P' . $weeks . 'W'));

        // Отримуємо день місяця
        $dayOfMonth = $currentDate->format('d');

        // Якщо день місяця менший за 15, то вибираємо 15-те число поточного місяця
        if ($dayOfMonth < 15) {
            $currentDate->setDate($currentDate->format('Y'), $currentDate->format('m'), 15);
            } else {
            // Якщо день місяця більший за 15, то вибираємо останнє число поточного місяця
            // Це дуже грубо, але в цілому вважаю що можна стерпіти
            $currentDate->modify('last day of this month');
            }

        // Повертаємо результуючу дату
        return $currentDate->format('Y-m-d');
        }


    // checkTimeDifference - перевіряємо чи впускати на івент та не тільки 
    static function chtd($start, $adjustment = false, $tip = 'start')
        {
        // Перетворюємо час у форматі SQL в об'єкт DateTime
        $checkDateTime = new DateTime($start);

        // Коригуємо вихідний час
        $checkDateTime->modify($adjustment);

        // Поточний час
        $currentDateTime = new DateTime();

        // Порівнюємо часи
        // 
        //self::vd($checkDateTime);
        if ($tip == 'start') {
            if ($checkDateTime < $currentDateTime) {
                return true;
                } else {
                return false;
                }
            } else {
            // 
            if ($checkDateTime > $currentDateTime) {
                return true;
                } else {
                return false;
                }
            }
        }


    static function itsJsonLogErrorExit($ip, $host, $isAdmin, $otvet, $response = false, $log = 'log')
        {
        if ($log == 'log') {
            self::ajaxFormIPspamProtect($ip, $host, $isAdmin, $response);
            }
        echo json_encode(array("otvet" => $otvet, "response" => $response));
        exit;
        }



    } // class core

