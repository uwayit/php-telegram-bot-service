<?php

// Database connection settings in lib/MysqlConnection


mb_internal_encoding('UTF-8');
mb_language('uni');
date_default_timezone_set('Europe/Kiev');


// Підключаємо основні класи та ініціалізуємо доступ до бази
include_once $_SERVER['DOCUMENT_ROOT'] . "/lib/core.php";
include_once $_SERVER['DOCUMENT_ROOT'] . "/MysqlConnection.php";
core::init();

// ПРИ ДОДАВАННІ НОВОЇ КРАЇНИ В СИСТЕМУ МИ ДОДАЄМО:
// Інфу про країну в SQL таблиці
// sites, regions, currency_konvert
// ТАКОЖ потрібно протестувати чи вивозить обмінні курси нової країни cron/update_kurs


class load {



    // мыло суперадминистратора
    // В первую очередь для того, чтобы указывать куда сыпать тикеты по всем проблемам
    const EMAIL = 'gmail@gmail.com';

    // Стартова ціність щойно опублікованого відгуку
    // Поки озвучую її тут
    const SCORE = '20';

    const MAXWalletADD = '20'; // Максимальна кількість гаманців які можна додати


    // Максимальный период в течении которого в МИНУТАХ мы ждём 
    // нажатия на кнопку "Я ОПЛАТИЛ"
    // Це в ідеалі не більше 20-30 хвилин
    const waytconfirm = 20; // Минуты

    // Максимальный период в течении которого мы ждём 
    // подтвержденних транзакцій ПІСЛЯ натискання кліентом кнопки "я оплатив"
    // Тут вказуємо час для мануальних методів перевірки, де перевірка залежить від людини
    // Дуже бажано звичайно для всіх сторін аби ці люди мали змогу перевіряти не рідше разу на 12 годин
    // аби тут можна було б поставити 700 хвилин
    // Це і продавцю підвищить довіру і кліент раніше зможе розраховувати на можливість поскаржитись
    const lifeTimeOrder = 1438; // НЕ можна ставити більше 1439 (< доби) !!!
    
    const lifeTimeCryptoOrder = 120; // Максимум 180, мінімум 30


    // Допустимое отклонение в процентах от суммы платежа в крипте
    // То есть, если клиент внёс меньший криптоплатёж, то можем ли мы это допустить?
    // В идеале стоит допускать хотя бы ОДИН 1% отклонения в пользу клиента 
    // просто потому что курс в момент платежа и в момент фиксации факта оплаты могут сильно отличаться
    // И нам нужно найти баланс между фиксацией курса и потерей на этом
    // Но в схемах обменников тут НЕльзя допускать никаких отклонений
    const cpd = '1';
																	   

    // Ключ и соли для доступа админа
    // Встановить свої
    const SERVICE_KEY = '123';
    const SERVICE_KEY_SOLD = 'QQQQ';


    // Ниже, соль учавствущая в генерации уникального user_key для каждого клиента
    // В случае изменения соли, нужно сменить все юзеркеи в базе на новые 
    // Без необходимости этого делать нельзя, как как юзеркей во всех высланных ранее письмах перестанет работать!
    // Но это также можно в некотором роде использовать на случай если мы хотим сделать «перезагрузку проекта», 
	// дабы ссылки из «старого проекта» не работали у клиентов в новом проекте, но база сохранилась.
	// Также это НУЖНО сделать в случае утечки констант, солей, базы и т.п.
    const SECURITY = 'LHF&EV*?m$E%';


    // Время ожидания минимального интервала (в МИНУТАХ) перед повторным запуском рассыльщика писем
    const CRON_TG_WAIT_MINUTES = 2;
    // Сколько сообщений (В телеграмм) может отправить за 1 раз каждый из ТАСКОВ
	// У телеги лимит 30 сообщений в секунду, так что в идеале
	// нужно просто с запасом уложиться в этот лимит и желательно не выйти за пределы 20 секунд работы скрипта
	// но если хостинг позволяет больше 20 секунд, то можно и больше. В любом случае стоит уложиться в минуту
	// То есть, если таск всего один, то тут можно смело ставить 200-400
	// гипотетически мы безопасно можем высылать ~ 1000 в минуту, чего более чем достаточно
	// 50 000 в час это очень хорошо и покрывает все мои нужды
    const LIMIT_TG_SENT = 10; 
    // количество клиентов которые могут быть включены в предварительную выборку на Telegram
    const CRON_TG_LIMIT = 400;


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
