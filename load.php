<?php

mb_internal_encoding('UTF-8');
mb_language('uni');
date_default_timezone_set('Europe/Kiev');

// Підключаємо основні класи та ініціалізуємо доступ до бази
include_once $_SERVER['DOCUMENT_ROOT'] . "/lib/core.php";
// Database connection settings in lib/MysqlConnection
include_once $_SERVER['DOCUMENT_ROOT'] . "/MysqlConnection.php";
core::init();

// ПРИ ДОДАВАННІ НОВОЇ КРАЇНИ В СИСТЕМУ МИ ДОДАЄМО:
// Інфу про країну в SQL таблиці
// sites, regions, currency_konvert
// ТАКОЖ потрібно протестувати чи вивозить обмінні курси нової країни cron/update_kurs


class load {

    // ! need edit
    // super admin email
    // First of all, in order to indicate where to submit tickets for all problems
    const EMAIL = 'gmail@gmail.com';

    // Ключ и соли для доступа админа
    // Встановить свої
    const SERVICE_KEY = '123';
    const SERVICE_KEY_SOLD = 'QQQQ';


    // Нижче, сіль, що бере участь у генерації унікального user_key для кожного клієнта
    // У разі зміни солі, необхідно змінити всі користувачі в основі на нові
    // Без необхідності цього робити не можна, як користувач у всіх висланих раніше листах перестане працювати!
    // Але це також можна до певної міри використовувати на випадок якщо ми хочемо зробити «перезавантаження проекту»,
    // Щоб посилання зі «старого проекту» не працювали у клієнтів у новому проекті, але база збереглася.
    // Також це потрібно зробити у разі витоку констант, солей, бази і т.п.
    const SECURITY = 'LHF&EV*?m$E%';


    // Для відправки повідомлень про помилки та винятки ми можемо використовувати slack або tg

    // Налаштовуємо slack bot
    const SLACK = 'https://hooks.slack.com/services/0000000000000';
    static $SlackSet = [
        'username' => 'ErrorBot', // Ім'я відправника повідомлень про помилки
        'channel' => '#error',    // Вказуємо #канал або @користувача куди будуть йти помилки
        'link_names' => true
    ];

    // chat_id розробника в tg
    const tgdevId = '00000000';

    // ! Бажано нижче не чіпай без необхідності

    // Статические переменные (не путать с константами), содержат названия таблиц
    // Заполняются во время выбора режима работы

    static $ip_log = '';
    static $sites = '';
	static $countries = '';
    static $paid_fiat = '';
	static $paid_crypto = '';
    static $wallets = '';
    static $partners = '';
    static $ref = '';
    static $partners_log = '';
    static $partners_stat = '';
    static $goods = '';
    static $review = '';
    static $seller = '';
    static $tasks = '';
    static $tickets = '';
    static $subscriptions = '';
    static $tg_bundle = '';
    static $tg_service = '';
    static $tg_deeplink = '';
    // Глобальный режим работы
    static $MODE = '';


    // Устанавливаем режим работы
    static public function setMode($mode = 'WORK') {
        self::$MODE = $mode;
	// При добавлении сюда новой таблицы, нужно не забывать её ОБЯЗАТЕЛЬНО "объявлять" выше в списке статичных переменных !!!!
	// !!!!!!!!!!!!!!!!!!!!!!!!!							 
        switch ($mode) {
            // Рабочий
            case 'WORK':
            default:
				self::$ip_log = 'ip_log';
                self::$sites = 'sites';
				self::$countries = 'countries';
                self::$paid_fiat = 'paid_fiat';
                self::$paid_crypto = 'paid_crypto';
                self::$wallets = 'wallets';
                self::$partners = 'partners';
                self::$ref = 'ref';
                self::$partners_log = 'partners_log';
                self::$partners_stat = 'partners_stat';
                self::$goods = 'goods';
                self::$review = 'review';
                self::$seller = 'seller';
                self::$tasks = 'tasks';
                self::$tickets = 'tickets';
                self::$subscriptions = 'subscriptions';
                self::$tg_bundle = 'tg_bundle';
                self::$tg_service = 'tg_service';
                self::$tg_deeplink = 'tg_deeplink';

                

                ini_set('display_errors', 0);

                break;

            // Тестовый
            case 'TEST':
				self::$ip_log = 'ip_log';
                self::$sites = 'sites';
				self::$countries = 'countries';
                self::$paid_fiat = 'paid_fiat';
                self::$paid_crypto = 'paid_crypto';
                self::$wallets = 'wallets_test';
                self::$partners = 'partners_test';
                self::$ref = 'ref';
                self::$partners_log = 'partners_log_test';
                self::$partners_stat = 'partners_stat';
                self::$goods = 'goods_test';
                self::$review = 'review';
                self::$seller = 'seller';
                self::$tasks = 'tasks_test';
                self::$tickets = 'tickets_test';
                self::$subscriptions = 'subscriptions_test';
                self::$tg_bundle = 'tg_bundle_test';
                self::$tg_service = 'tg_service_test';
                self::$tg_deeplink = 'tg_deeplink_test';

                ini_set('display_errors', 1);
				ini_set('error_reporting', E_ALL);

                break;
        }
    }

} // class Constants


// auto load class
function __autoload_libraries($name) {
    $file = $_SERVER['DOCUMENT_ROOT'] . '/lib/' . $name . '.php';
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    return false;
}
spl_autoload_register('__autoload_libraries');


