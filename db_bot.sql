-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Время создания: Июл 06 2024 г., 21:32
-- Версия сервера: 5.7.44-48-log
-- Версия PHP: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


-- --------------------------------------------------------

--
-- Структура таблицы `black_data`
--

CREATE TABLE `black_data` (
  `id` int(11) NOT NULL,
  `date_insert` datetime NOT NULL COMMENT 'Дата останнього використання цього контакту чорнушником',
  `email` varchar(50) DEFAULT NULL COMMENT 'Последний black email пытавшийся использовать этот ip или fp',
  `ip` varchar(50) DEFAULT NULL COMMENT 'ip или диапазон чёрных ip',
  `ip_range` varchar(77) NOT NULL COMMENT 'Якщо хочеться забанити діапазон IP, то вказую його в форматі 000.000.00 тобто фіксую стабільно саме цей діапазон',
  `fp` varchar(100) DEFAULT NULL COMMENT 'Banned fingerprint',
  `name` varchar(55) NOT NULL COMMENT 'If we add an IP to this table, then in this field you need to indicate the name of the governors from this range, such as “Русня”, etc.',
  `attempts` tinyint(11) NOT NULL DEFAULT '0' COMMENT 'Number of attempts to submit an application from this black contact',
  `type` varchar(7) NOT NULL COMMENT 'sitetype'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='грязные данные используемые чернушниками';

-- --------------------------------------------------------

--
-- Структура таблицы `countries`
--

CREATE TABLE `countries` (
  `id` int(13) NOT NULL,
  `country` varchar(35) NOT NULL COMMENT 'Название страны на русском',
  `prefix_ISO_3166` varchar(3) NOT NULL COMMENT 'Сокращённое название страны на латинице согласно ISO 3166-1',
  `ISO_4217` varchar(3) NOT NULL COMMENT 'Валюта страны в формате ISO 4217',
  `val_name_all` varchar(44) NOT NULL COMMENT 'Название валюты на языке страны в формате "гривня гривні гривень"',
  `metropolis` varchar(100) NOT NULL COMMENT 'Столица страны',
  `country_dat` varchar(37) NOT NULL COMMENT 'Страна в дательном падеже кому? где? в ...',
  `country_iz` varchar(38) NOT NULL COMMENT 'страна склоннёная "из ... страны ... из Украины"',
  `first_doc_INN` varchar(22) NOT NULL COMMENT 'Назва документа, який ми використовуємо як основний ідентифікатор для жителів країни',
  `INNDigits` varchar(2) NOT NULL COMMENT 'Длина (кол-во символов) в ИНН',
  `mobileDigits` varchar(2) NOT NULL COMMENT 'Длина телефонного номера (количество цифр в номере)',
  `mobileCodes` varchar(300) NOT NULL COMMENT 'The first digits of telephone numbers in a given country. Can fit up to ~50 detailed codes',
  `population` varchar(5) NOT NULL COMMENT 'Кол-во жителей в миллионах',
  `erReg` varchar(666) NOT NULL COMMENT 'Проблемные регионы страны на которых не работают стандартные законы и правила, из-за чего сайт при работе с этими регионами может иметь некоторые корректировки и изменения',
  `vashegoPasp` varchar(100) NOT NULL DEFAULT 'Вашего паспорта' COMMENT 'Региональные термины зависящие от страны',
  `vashPasp` varchar(40) NOT NULL DEFAULT 'паспорт' COMMENT 'Региональные термины зависящие от страны',
  `termObl` varchar(40) NOT NULL DEFAULT 'область' COMMENT 'Региональные термины зависящие от страны',
  `infoPasp` varchar(150) NOT NULL COMMENT 'Региональные термины зависящие от страны',
  `vashimPasp` varchar(90) NOT NULL DEFAULT 'паспортом' COMMENT 'Региональная терминология',
  `stranicaPasp` varchar(100) NOT NULL DEFAULT ' (разворот с фото)' COMMENT 'Региональная терминология',
  `co_en` varchar(55) NOT NULL COMMENT 'Назва країни англійською. Вирішив використовувати у випадаючих списках',
  `locale` varchar(6) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `countries`
--

INSERT INTO `countries` (`id`, `country`, `prefix_ISO_3166`, `ISO_4217`, `val_name_all`, `metropolis`, `country_dat`, `country_iz`, `first_doc_INN`, `INNDigits`, `mobileDigits`, `mobileCodes`, `population`, `erReg`, `vashegoPasp`, `vashPasp`, `termObl`, `infoPasp`, `vashimPasp`, `stranicaPasp`, `co_en`, `locale`) VALUES
(1, 'Україна', 'ua', 'UAH', 'гривня гривні гривень', 'Київ', 'Україні', 'України', 'ІПН', '10', '12', '+380', '41', 'Токмак,Мелітополь,Маріуполь,Бердянськ,Донецьк,Луганськ,Сєвєродонецьк,Алчевськ,Макіївка,Крим,Вовчанськ', 'Вашего паспорта или ID карты', 'паспорт или ID карту', 'область', ' (первой страницы, страницы с самым свежим фото и страницы с пропиской, если у Вас паспорт или обеих сторон карты если у Вас ID карта)', 'паспортом или ID картой', ' (страница/сторона с самым актуальным фото)', 'Ukraine', 'uk_UA'),
(3, 'Беларусь', 'by', 'BYN', 'рубль рубля рублей', 'Минск', 'Белоруссии', 'Белоруссии', 'личный номер', '14', '12', '+375', '9,2', '', 'Вашего паспорта', 'паспорт', 'область', ' (первой страницы и страницы с пропиской, если у Вас паспорт или обеих сторон карты если у Вас ID карта)', 'паспортом', ' (разворот с фото)', 'Belarus', ''),
(4, 'Грузия', 'ge', 'GEL', 'лари лари лари', 'Тбилиси', 'Грузии', 'Грузии', 'личный номер', '', '12', '+995', '3,7', '', 'Вашей ID карты', 'ID карту', 'регион (область)', ' (обеих сторон ID карты)', 'ID картой', ' (разворот с фото)', 'Georgia', ''),
(5, 'Молдова', 'md', 'MDL', 'леи', 'Chişinău', 'Молдове', 'Молдовы', 'IDNP', '13', '11', '+373', '2,6', '', 'Вашего паспорта', 'паспорт', 'регион (область)', ' (первой страницы и страницы с пропиской, если у Вас паспорт или обеих сторон карты если у Вас ID карта)', 'паспортом', ' (разворот с фото)', 'Moldova', ''),
(6, 'Казахстан', 'kz', 'KZT', 'тенге', 'Нур-Султан', 'Казахстане', 'Казахстана', 'ИИН', '12', '11', '+77,+76,+997', '18,7', '', 'Вашего удостоверения личности', 'удостоверение личности', 'регион (область)', ' (обеих сторон)', 'удостоверением личности', ' (разворот с фото)', 'Kazakhstan', ''),
(7, 'Латвия', 'lv', 'EUR', 'euro euro euro', 'Рига', 'Латвии', 'Латвии', 'персональный код', '12', '11', '+371', '1,9', '', 'Вашего паспорта', 'паспорт', 'регион', ' (первой страницы и страницы с пропиской)', 'паспортом', ' (разворот с фото)', 'Latvia', ''),
(8, 'Армения', 'am', 'AMD', 'драм', 'Ереван', 'Армении', 'Армении', '', '', '11', '+374', '3', '', 'Вашего паспорта', 'паспорт', 'область', ' (первой страницы и страницы с пропиской, если у Вас паспорт или обеих сторон карты если у Вас ID карта)', 'паспортом', ' (разворот с фото)', 'Armenia', ''),
(9, 'Таджикистан', 'tj', 'TJS', 'сомони', 'Душанбе', 'Таджикистану', 'Таджикистана', '', '', '12', '+992', '9,5', '', 'Вашего паспорта', 'паспорт', 'область', ' (первой страницы и страницы с пропиской, если у Вас паспорт или обеих сторон карты если у Вас ID карта)', 'паспортом', ' (разворот с фото)', 'Tajikistan', ''),
(10, 'Узбекистан', 'uz', 'UZS', 'сум', 'Ташкент', 'Узбекистане', 'Узбекистана', 'ПИНФЛ', '14', '12', '+998', '34,2', '', 'Вашего паспорта или ID карты', 'паспорт или ID карту', 'область', ' (первой страницы и страницы с пропиской, если у Вас паспорт или обеих сторон карты если у Вас ID карта)', 'паспортом или ID картой', ' (страница/сторона с самым актуальным фото)', 'Uzbekistan', ''),
(11, 'Кыргызстан', 'kg', 'KGS', 'сом', 'Бишкек', 'Киргизии', 'Киргизии', '', '', '12', '+996', '6,5', '', 'Вашего паспорта', 'паспорт', 'область', ' (первой страницы и страницы с пропиской, если у Вас паспорт или обеих сторон карты если у Вас ID карта)', 'паспортом', ' (разворот с фото)', 'Kyrgyz Republic', ''),
(12, 'United Kingdom', 'gb', 'GBP', 'GBP GBP GBP', 'London', 'United Kingdom', 'United Kingdom', '', '', '12', '+44\r\n', '67', '', '', '', '', '', '', '', 'United Kingdom', 'en_GB');

-- --------------------------------------------------------

--
-- Структура таблицы `dialogue`
--

CREATE TABLE `dialogue` (
  `id` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `email` varchar(55) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Не пустое, если использовалась почта',
  `tg` varchar(22) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'chat id если использовалась телега',
  `essence` varchar(22) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Особый идентификатор сообщения, но не его содержимое',
  `message_id` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Номер повідомлення в чаті з користувачем.  Знаючи його, можна наприклад видалити це повідомлення якщо треба',
  `message` varchar(355) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Что именно было отправлено клиенту',
  `fbclient` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Ответ клиента',
  `auth_key` varchar(7) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'key що необхідний аби запобігти абузінгу видалення та для ідентификації цільового повідомлення',
  `menu` text COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Сюди робимо зліпок меню, на випадок якщо треба буде повернутись назад по меню'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Лог информационных сообщений отправляемых клиентам';


-- --------------------------------------------------------

--
-- Структура таблицы `ip_log`
--

CREATE TABLE `ip_log` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL COMMENT 'Дата активности данного IP',
  `IP` varchar(55) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Айпи с которого было обращение через форму на сайте',
  `count` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Счётчик заполненных ключевых форм на сайте с этого айпи в день date',
  `blackcount` int(11) NOT NULL COMMENT 'Кількість запитів після внесення в блекліст',
  `softcount` int(11) NOT NULL COMMENT 'Альтернативний індікатор. Щоб рахуваи кількість запросів з ip більш детально. Сюди враховується кожна загрузка сторінки (як мінімум)',
  `black` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Превышал ли этот айпи хоть раз лимит',
  `domain_black` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Наш сайт на якому цей айпі перевищив ліміт востаннє'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- --------------------------------------------------------

--
-- Структура таблицы `regions`
--

CREATE TABLE `regions` (
  `id` int(11) NOT NULL,
  `region` varchar(60) NOT NULL COMMENT 'Официальное название области-региона',
  `country` varchar(3) NOT NULL COMMENT 'Страна к которой относится данный регион',
  `iso` varchar(7) NOT NULL COMMENT 'ISO 3166-2',
  `state` varchar(60) NOT NULL COMMENT 'Округ (федеральний в рф), штат тощо',
  `metropolis` varchar(50) NOT NULL COMMENT 'Столица области',
  `satellites` text NOT NULL COMMENT 'Всі інші міста регіону',
  `active` varchar(1) DEFAULT '1' COMMENT 'Тут можна відключити регіона взагалі для всіх, не видаляючи його',
  `timezone` varchar(3) NOT NULL DEFAULT '0' COMMENT 'Данные необходимые для корректировки времени. 0 равен +2 UTC',
  `code_subject` varchar(16) NOT NULL COMMENT 'Код суб''єкта. Якщо у рядку перераховано кілька суб''єктів, то й тут коди повинні стояти через кому без пробілів',
  `code_passport` varchar(10) NOT NULL COMMENT 'Код регіону, який вказується в паспорті',
  `zip` varchar(220) NOT NULL COMMENT 'первые цифры почтового индекса характерные для данной области'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Інфа про регіони';

--
-- Дамп данных таблицы `regions`
--

INSERT INTO `regions` (`id`, `region`, `country`, `iso`, `state`, `metropolis`, `satellites`, `active`, `timezone`, `code_subject`, `code_passport`, `zip`) VALUES
(1, 'Київська обл.', 'ua', 'UA-32', '', 'Київ', 'Біла Церква,Бровари,Бориспіль,Фастів,Ірпінь,Вишневе,Василькíв,Боярка,Обухів,Буча,Переяслав,Вишгород,Славутич,Яготин,Богуслав,Сквира,Березань,Українка,Кагарлик,Тетіїв,Узин,Миронівка,Тараща,Ржищів', '1', '+2', '', '', '01-09'),
(2, 'Вінницька обл.', 'ua', 'UA-05', '', 'Вінниця', 'Ладижин,Могилів-Подільський,Калинівка,Жмеринка,Немирів,Козятин,Стрижавка,Шаргород,Хмільник,Гайсин,Бар,Тульчин,Бершадь,Гнівань,Іллінці,Ямпіль,Погребище,Липовець', '1', '+2', '', '', '21-23,24'),
(3, 'Волинська обл.', 'ua', 'UA-07', '', 'Луцьк', 'Ковель,Нововолинськ,Володимир,Ківерці,Рожище,Камінь-Каширський,Маневичі,Любомль,Ратно,Горохів', '1', '+2', '', '', '43,44,45'),
(4, 'Дніпропетровська обл.', 'ua', 'UA-12', '', 'Дніпро', 'Кривий Ріг,Жовті Води,Нікополь,Павлоград,Кам\'янське,Новомосковськ,Марганець,Першотравенськ,Покров,Синельникове,Тернівка,Вільногірськ,Підгородне,П\'ятихатки,Верхньодніпровськ,Апостолове,Зеленодольськ,Перещепине,Верхівцеве', '1', '+2', '', '', '49-53'),
(5, 'Донецька обл.', 'ua', 'UA-14', '', 'Краматорськ', 'Маріуполь,Покровськ,Донецьк,Макіївка,Горлівка,Слов’янськ,Єнакієве,Костянтинівка,Покровськ,Дружківка,Чистякове,Харцизьк,Шахтарськ,Мирноград,Авдіївка,Сніжне,Торецьк,Ясинувата,Торецьк,Добропілля,Хрестівка,Дебальцеве,Селидове,Докучаєвськ,Волноваха,Лиман,Курахове,Білозерське,Красногорівка,Миколаївка,Вугледар,Новогродівка,Часів Яр,Сіверськ,Українськ', '1', '+2', '', '', '83,84,85,86,87'),
(6, 'Житомирська обл.', 'ua', 'UA-18', '', 'Житомир', 'Овруч,Новоград-Волинський,Бердичів,Коростень,Малин,Коростишів,Радомишль,Баранівка,Олевськ,Андрушівка,Чуднів', '1', '+2', '', '', '10-13'),
(7, 'Закарпатська обл.', 'ua', 'UA-21', '', 'Ужгород', 'Хуст,Рахів,Мукачево,Виноградів,Берегове,Іршава,Свалява,Тячів,Чоп,Перечин', '1', '+2', '', '', '88,89,90'),
(8, 'Запорізька обл.', 'ua', 'UA-23', '', 'Запоріжжя', 'Мелітополь,Бердянськ,Енергодар,Токмак,Пологи,Дніпрорудне,Вільнянськ,Оріхів,Гуляйполе,Василівка,Кам\'янка-Дніпровська,Приморськ,Молочанськ', '1', '+2', '', '', '69,70,71,72'),
(9, 'Івано-Франківська обл.', 'ua', 'UA-26', '', 'Івано-Франківськ', 'Коломия,Калуш,Надвірна,Долина,Бурштин,Болехів,Снятин,Тисмениця,Городенка,Яремче,Косів,Тлумач,Рогатин,Галич', '1', '+2', '', '', '76,77,78'),
(10, 'Кіровоградська обл.', 'ua', 'UA-35', '', 'Кропивницький', 'Олександрія,Гайворон,Світловодськ,Знам’янка,Долинська,Новоукраїнка,Новомиргород,Мала Виска,Бобринець,Помічна,Благовіщенське', '1', '+2', '', '', '25,26,27,28'),
(11, 'Луганська обл.', 'ua', 'UA-09', '', 'Сєвєродонецьк', 'Лисичанськ,Луганськ,Алчевськ,Сорокине,Хрустальний,Кадіївка,Довжанськ', '1', '+2', '', '', '91,92,93,94'),
(12, 'Львівська обл.', 'ua', 'UA-46', '', 'Львів', 'Дрогобич,Червоноград,Стрий,Трускавець,Самбір,Борислав,Новояворівськ,Броди,Новий Розділ,Золочів,Сокаль,Стебник,Винники,Городок,Миколаїв,Жовква,Яворів,Соснівка,Кам\'янка-Бузька,Жидачів,Дубляни,Радехів,Пустомити,Мостиська,Ходорів,Буськ,Рава-Руська', '1', '+2', '', '', '79,80,81,82'),
(13, 'Миколаївська обл.', 'ua', 'UA-48', '', 'Миколаїв', 'Новий Буг,Южноукраїнськ,Нова Одеса,Первомайськ,Вознесенськ,Очаків,Снігурівка,Баштанка', '1', '+2', '', '', '54,55,56,57'),
(14, 'Одеська обл.', 'ua', 'UA-51', '', 'Одеса', 'Ізмаїл,Рені,Чорноморськ,Білгород-Дністровський,Подільськ,Южне,Кілія,Балта,Роздільна,Болград,Арциз,Біляївка,Татарбунари,Теплодар,Березівка,Кодима,Ананьїв,Вилкове', '1', '+2', '', '', '65,66,67,68'),
(15, 'Полтавська обл.', 'ua', 'UA-53', '', 'Полтава', 'Кременчук,Лубни,Гадяч,Горішні Плавні,Миргород,Пирятин,Карлівка,Хорол,Лохвиця,Гребінка,Кобеляки,Глобине,Зіньків,Заводське', '1', '+2', '', '', '36,37,38,39'),
(16, 'Рівненська обл.', 'ua', 'UA-56', '', 'Рівне', 'Сарни,Вараш,Дубно,Костопіль,Здолбунів,Острог,Березне,Радивилів,Дубровиця,Корець', '1', '+2', '', '', '33,34,35'),
(17, 'Сумська обл.', 'ua', 'UA-59', '', 'Суми', 'Шостка,Конотоп,Охтирка,Ромни,Глухів,Лебедин,Кролевець,Тростянець,Білопілля,Путивль,Буринь,Ворожба,Середина-Буда,Дружба', '1', '+2', '', '', '40,41,42'),
(18, 'Тернопільська обл.', 'ua', 'UA-61', '', 'Тернопіль', 'Кременець,Чортків,Бережани,Збараж,Борщів,Бучач,Теребовля,Ланівці,Заліщики,Ланівці,Почаїв,Хоростків', '1', '+2', '', '', '46,47,48'),
(19, 'Харківська обл.', 'ua', 'UA-63', '', 'Харків', 'Лозова,Ізюм,Красноград,Чугуїв,Первомайський,Куп’янськ,Балаклія,Мерефа,Люботин,Вовчанськ,Дергачі,Богодухів,Зміїв,Валки,Барвінкове,Південне', '1', '+2', '', '', '61,62,63,64'),
(20, 'Херсонська обл.', 'ua', 'UA-65', '', 'Херсон', 'Генічеськ,Нова Каховка,Каховка,Олешки,Скадовськ,Гола Пристань,Берислав,Таврійськ', '1', '+2', '', '', '73,74,75'),
(21, 'Хмельницька обл.', 'ua', 'UA-68', '', 'Хмельницький', 'Шепетівка,Кам\'янець-Подільський,Нетішин,Славута,Старокостянтинів,Полонне,Красилів,Волочиськ,Ізяслав,Городок,Дунаївці,Деражня', '1', '+2', '', '', '29,30,31,32'),
(22, 'Черкаська обл.', 'ua', 'UA-71', '', 'Черкаси', 'Умань,Канів,Шпола,Сміла,Золотоноша,Корсунь-Шевченківський,Звенигородка,Ватутіне,Городище,Тальне,Жашків,Кам\'янка,Христинівка,Чигирин,Монастирище', '1', '+2', '', '', '18,19,20'),
(23, 'Чернігівська обл.', 'ua', 'UA-74', '', 'Чернігів', 'Ніжин,Новгород-Сіверський,Прилуки,Бахмач,Носівка,Городня,Корюківка,Мена,Сновськ,Ічня,Бобровиця,Борзна,Семенівка', '1', '+2', '', '', '14,15,16,17'),
(24, 'Чернівецька обл.', 'ua', 'UA-77', '', 'Чернівці', 'Новодністровськ,Сторожинець,Красноїльськ,Хотин,Сокиряни,Заставна,Глибока,Новоселиця,Кіцмань', '1', '+2', '', '', '58,59,60'),
(25, 'Київ', 'ua', 'UA-30', '', '', '', '1', '+2', '', '', '01-09'),
(26, 'Крим', 'ua', 'UA-43', '', 'Сімферополь', 'Керчь,Євпаторія,Ялта,Феодосія,Джанкой,Севастополь,Алушта,Бахчисарай,Яни Капу,Саки,Армянськ,Судак,Білогірськ,Старий Крим,Щолкіне,Алупка', '0', '+2', '', '', ''),
(161, 'Нур-Султан и Акмолинская обл.', 'kz', '', '', 'Нур-Султан', 'Кокшетау,Атбасар,Степногорск,Акколь', '1', '0', '', '', ''),
(162, 'Алматы и Алматинская обл.', 'kz', '', '', 'Алматы', 'Талдыкорган,Сарканд,Чунджа,Текели,Конаев,Карабулак', '1', '0', '', '', ''),
(163, 'Актюбинская обл.', 'kz', '', '', 'Актобе', 'Калдыагаш,Шалкар', '1', '0', '', '', ''),
(164, 'Атырауская обл.', 'kz', '', '', 'Атырау', 'Кульсары,Макат,Индерборский', '1', '0', '', '', ''),
(165, 'Восточно-Казахстанская обл.', 'kz', '', '', 'Усть-Каменогорск', 'Семей,Аягоз,Зайсан', '1', '0', '', '', ''),
(166, 'Жамбылская обл.', 'kz', '', '', 'Тараз', 'Шу,Жанатас,Мерке', '1', '0', '', '', ''),
(167, 'Западно-Казахстанская обл.', 'kz', '', '', 'Уральск', 'Аксай,Жангала,Чапаев', '1', '0', '', '', ''),
(168, 'Карагандинская обл.', 'kz', '', '', 'Караганда', 'Жезказган,Балхаш,Атасу', '1', '0', '', '', ''),
(169, 'Костанайская обл.', 'kz', '', '', 'Костанай', 'Аркалык,Лисаковск,Аулиеколь,Житикара', '1', '0', '', '', ''),
(170, 'Кызылординская обл.', 'kz', '', '', 'Кызылорда', 'Байконур,Жанакорган,Аральск,Айтеке-Би', '1', '0', '', '', ''),
(171, 'Мангистауская обл.', 'kz', '', '', 'Актау', 'Жанаозен,Бейнеу,Шетпе', '1', '0', '', '', ''),
(172, 'Павлодарская обл.', 'kz', '', '', 'Павлодар', 'Экибастуз,Теренколь,Курчатов', '1', '0', '', '', ''),
(173, 'Северо-Казахстанская обл.', 'kz', '', '', 'Петропавловск', 'Новоишимское', '1', '0', '', '', ''),
(197, 'Ташкент и Ташкентская обл.', 'uz', '', '', 'Ташкент', 'Алмалык,Ангрен,Бекабад,Чирчик,Нурафшан,Янгиюль', '1', '0', '', '', ''),
(198, 'Андижанская обл.', 'uz', '', '', 'Андижан', 'Ханабад', '1', '0', '', '', ''),
(199, 'Ферганская обл.', 'uz', '', '', 'Фергана', 'Коканд,Маргилан,Кувасай', '1', '0', '', '', ''),
(200, 'Наманганская обл.', 'uz', '', '', 'Наманган', 'Касансай,Чуст,Учкурган', '1', '0', '', '', ''),
(201, 'Джизакская обл.', 'uz', '', '', 'Джизак', 'Гагарин', '1', '0', '', '', ''),
(202, 'Бухарская обл.', 'uz', '', '', 'Бухара', 'Каракуль,Шафиркан', '1', '0', '', '', ''),
(203, 'Кашкадарьинская обл.', 'uz', '', '', 'Карши', 'Касан,Камаши,Шахрисабз,Китаб', '1', '0', '', '', ''),
(204, 'Навоийская обл.', 'uz', '', '', 'Навои', 'Зарафшан,Нурата,Учкудук', '1', '0', '', '', ''),
(205, 'Самаркандская обл.', 'uz', '', '', 'Самарканд', 'Акташ,Каттакурган,Ургут', '1', '0', '', '', ''),
(206, 'Сурхандарьинская обл.', 'uz', '', '', 'Термез', 'Денау,Кумкурган,Шерабад,Узун,Джаркурган,Шурчи', '1', '0', '', '', ''),
(207, 'Сырдарьинская обл.', 'uz', '', '', 'Сырдарья', 'Гулистан,Ширин,Янгиер', '1', '0', '', '', ''),
(208, 'Республика Каракалпакстан', 'uz', '', '', 'Нукус', 'Мангит,Тахиаташ,Ходжейли,Кегейли', '1', '0', '', '', ''),
(209, 'Хорезмская обл.', 'uz', '', '', 'Ургенч', 'Хива,Хазарасп,Ханка', '1', '0', '', '', ''),
(220, 'Găgăuzia', 'md', 'MD-GA', '', 'Komrat', '', '1', '0', '', '', ''),
(221, 'Transnistria', 'md', '', '', 'Tiraspol', 'Rîbnița', '1', '0', '', '', ''),
(222, 'Bender', 'md', 'MD-BD', '', '', '', '1', '0', '', '', ''),
(223, 'Bălţi', 'md', 'MD-BA', '', '', '', '1', '0', '', '', ''),
(224, 'Chişinău', 'md', 'MD-CU', '', '', '', '1', '0', '', '', ''),
(225, 'Komrat', 'md', '', '', '', '', '1', '0', '', '', ''),
(226, 'Cahul', 'md', '', '', '', '', '1', '0', '', '', ''),
(227, 'Çadır-Lunga', 'md', '', '', '', '', '1', '0', '', '', ''),
(228, 'Edineț', 'md', '', '', '', '', '1', '0', '', '', ''),
(229, 'Hîncești', 'md', '', '', '', '', '1', '0', '', '', ''),
(230, 'Orhei', 'md', '', '', '', '', '1', '0', '', '', ''),
(231, 'Soroca', 'md', '', '', '', '', '1', '0', '', '', ''),
(232, 'Strășeni', 'md', '', '', '', '', '1', '0', '', '', ''),
(233, 'Ungheni', 'md', '', '', '', '', '1', '0', '', '', ''),
(281, 'Екабпилс', 'lv', '', '', 'Екабпилс', '', '1', '0', '', '', ''),
(282, 'Мадона', 'lv', '', '', 'Мадона', '', '1', '0', '', '', ''),
(285, 'Гулбене', 'lv', '', '', 'Гулбене', '', '1', '0', '', '', ''),
(286, 'Алуксне', 'lv', '', '', 'Алуксне', '', '1', '0', '', '', ''),
(287, 'Резекне', 'lv', '', '', 'Резекне', '', '1', '0', '', '', ''),
(288, 'Балви', 'lv', '', '', 'Балви', '', '1', '0', '', '', ''),
(289, 'Прейли', 'lv', '', '', 'Прейли', '', '1', '0', '', '', ''),
(290, 'Лудза', 'lv', '', '', 'Лудза', '', '1', '0', '', '', ''),
(291, 'Даугавпилс', 'lv', '', '', 'Даугавпилс', '', '1', '0', '', '', ''),
(292, 'Краслава', 'lv', '', '', 'Краслава', '', '1', '0', '', '', ''),
(293, 'Вентспилс', 'lv', '', '', 'Вентспилс', '', '1', '0', '', '', ''),
(294, 'Кулдига', 'lv', '', '', 'Кулдига', '', '1', '0', '', '', ''),
(295, 'Талси', 'lv', '', '', 'Талси', '', '1', '0', '', '', ''),
(296, 'Салдус', 'lv', '', '', 'Салдус', '', '1', '0', '', '', ''),
(297, 'Тукумс', 'lv', '', '', 'Тукумс', '', '1', '0', '', '', ''),
(298, 'Лиепая', 'lv', '', '', 'Лиепая', '', '1', '0', '', '', ''),
(299, 'Елгава', 'lv', '', '', 'Елгава', '', '1', '0', '', '', ''),
(300, 'Добеле', 'lv', '', '', 'Добеле', '', '1', '0', '', '', ''),
(301, 'Рига и Юрмала', 'lv', '', '', 'Рига', '', '1', '0', '', '', ''),
(302, 'Бауска', 'lv', '', '', 'Бауска', '', '1', '0', '', '', ''),
(303, 'Огре', 'lv', '', '', 'Огре', '', '1', '0', '', '', ''),
(304, 'Цесис', 'lv', '', '', 'Цесис', '', '1', '0', '', '', ''),
(305, 'Лимбажи', 'lv', '', '', 'Лимбажи', '', '1', '0', '', '', ''),
(306, 'Валмера', 'lv', '', '', 'Валмера', '', '1', '0', '', '', ''),
(307, 'Валка', 'lv', '', '', 'Валка', '', '1', '0', '', '', ''),
(308, 'Айзкраукле', 'lv', '', '', 'Айзкраукле', '', '1', '0', '', '', ''),
(331, 'Южно-Казахстанская обл.', 'kz', '', '', 'Шымкент', 'Туркестан', '1', '0', '', '', ''),
(332, 'Душанбе и РРП', 'tj', '', '', 'Душанбе', '', '1', '0', '', '', ''),
(333, 'Согдийская область', 'tj', '', '', 'Худжанд', '', '1', '0', '', '', ''),
(334, 'Хатлонская область', 'tj', '', '', 'Бохтар', 'Куляб', '1', '0', '', '', ''),
(335, 'Горно-Бадахшанская АО', 'tj', '', '', 'Хорог', '', '1', '0', '', '', ''),
(350, 'Ванадзор', 'am', '', '', 'Ванадзор', '', '1', '0', '', '', ''),
(351, 'Эчмиадзин', 'am', '', '', 'Эчмиадзин', '', '1', '0', '', '', ''),
(352, 'Абовян', 'am', '', '', 'Абовян', '', '1', '0', '', '', ''),
(353, 'Ереван', 'am', '', '', 'Ереван', '', '1', '0', '', '', ''),
(354, 'Гюмри', 'am', '', '', 'Гюмри', '', '1', '0', '', '', ''),
(380, 'Нарынская обл.', 'kg', '', '', 'Нарын', '', '1', '0', '', '', ''),
(381, 'Бишкек и Чуйская обл.', 'kg', '', '', 'Бишкек', '', '1', '0', '', '', ''),
(382, 'Иссык-Кульская обл.', 'kg', '', '', 'Каракол', '', '1', '0', '', '', ''),
(383, 'Баткенская обл.', 'kg', '', '', 'Баткен', '', '1', '0', '', '', ''),
(384, 'Джалал-Абадская обл.', 'kg', '', '', 'Джалал-Абад', '', '1', '0', '', '', ''),
(385, 'Ошская обл.', 'kg', '', '', 'Ош', '', '1', '0', '', '', ''),
(389, 'Таласская обл.', 'kg', '', '', 'Талас', '', '1', '0', '', '', ''),
(410, 'Самегрело и Земо-Сванети', 'ge', 'GE-SZ', '', 'Зугдиди', '', '1', '0', '', '', ''),
(411, 'Аджария АР', 'ge', 'GE-AJ', '', 'Батуми', '', '1', '0', '', '', ''),
(412, 'Абхазская АР', 'ge', 'GE-AB', '', 'Сухум', '', '1', '0', '', '', ''),
(413, 'Тбилиси', 'ge', 'GE-TB', '', '', '', '1', '0', '', '', ''),
(414, 'Кахетия', 'ge', 'GE-KA', '', 'Телави', '', '1', '0', '', '', ''),
(415, 'Квемо-Картли', 'ge', 'GE-KK', '', 'Тбилиси', '', '1', '0', '', '', ''),
(416, 'Мцхета-Мтианети', 'ge', 'GE-MM', '', 'Мцхета', '', '1', '0', '', '', ''),
(417, 'Шида-Картли', 'ge', 'GE-SK', '', 'Гори', '', '1', '0', '', '', ''),
(418, 'Самцхе-Джавахетия', 'ge', 'GE-SJ', '', 'Ахалцихе', '', '1', '0', '', '', ''),
(419, 'Имеретия', 'ge', 'GE-IM', '', 'Кутаиси', '', '1', '0', '', '', ''),
(420, 'Рача-Лечхуми и Квемо-Сванети', 'ge', 'GE-RL', '', 'Амбролаури', '', '1', '0', '', '', ''),
(421, 'Гурия', 'ge', 'GE-GU', '', 'Озургети', '', '1', '0', '', '', ''),
(440, 'Минская область', 'by', '', '', 'Минск', '', '1', '0', '', '', ''),
(441, 'Могилевская область', 'by', '', '', 'Могилёв', '', '1', '0', '', '', ''),
(442, 'Гродненская область', 'by', '', '', 'Гродно', '', '1', '0', '', '', ''),
(443, 'Брестская область', 'by', '', '', 'Брест', '', '1', '0', '', '', ''),
(444, 'Витебская область', 'by', '', '', 'Витебск', '', '1', '0', '', '', ''),
(445, 'Гомельская область', 'by', '', '', 'Гомель', '', '1', '0', '', '', ''),
(470, 'Belfast', 'gb', 'GB-BFS', 'Northern Ireland', '', '', '1', '0', '', '', ''),
(471, 'Ards and North Down', 'gb', 'GB-AND', 'Northern Ireland', 'Bangor', 'Ballygowan,Comber,Crawfordsburn,Donaghadee,Greyabbey,Holywood,Loughries,Millisle,Newtownards,Portaferry,Portavogie', '1', '0', '', '', ''),
(472, 'Antrim and Newtownabbey', 'gb', 'GB-ANN', 'Northern Ireland', 'Antrim', 'Newtownabbey,Aldergrove,Ballyclare,Ballyeaston,Ballynure,Ballyrobert,Caddy,Crumlin,Doagh,Loanends,Moneyglass,Moneynick,Muckamore,Parkgate,Randalstown,Straid,Templepatrick,Tildarg,Toome,Whiteabbey', '1', '0', '', '', ''),
(473, 'Lisburn and Castlereagh', 'gb', 'GB-LBC', 'Northern Ireland', 'Lisburn', 'Annahilt,Ballinderry Lower,Carryduff,Crossnacreevy,Culcavy,Dundonald,Dundrod,Dunmurry,Gelnavy,Hillsborough,Moira', '1', '0', '', '', ''),
(474, 'Newry, Mourne and Down', 'gb', 'GB-NMD', 'Northern Ireland', 'Newry', 'Annalong,Ardglass,Ballynahinch,Castlewellan,Crossmaglen,Downpatrick,Kilkeel,Newcastle,Saintfield,Warrenpoint', '1', '0', '', '', ''),
(475, 'Fermanagh and Omagh', 'gb', 'GB-FMO', 'Northern Ireland', 'Enniskillen', 'Ballinamallard,Belcoo,Fintona,Irvinestown,Kesh,Lisnaskea,Omagh', '1', '0', '', '', ''),
(476, 'Armagh, Banbridge and Craigavon', 'gb', 'GB-ABC', 'Northern Ireland', 'Craigavon', 'Armagh,Banbridge,Lurgan,Portadown', '1', '0', '', '', ''),
(477, 'Mid and East Antrim', 'gb', 'GB-MEA', 'Northern Ireland', 'Ballymena', 'Ballygalley,Broughshane,Carnlough,Carrickfergus,Larne', '1', '0', '', '', ''),
(478, 'Causeway Coast and Glens', 'gb', 'GB-CCG', 'Northern Ireland', 'Coleraine', 'Armoy,Ballintoy,Ballycastle,Ballymoney,Bushmills,Cushendall,Cushendun,Dungiven,Limavady,Portrush,Torr', '1', '0', '', '', ''),
(479, 'Mid-Ulster', 'gb', 'GB-MUL', 'Northern Ireland', 'Dungannon', 'Ballygawley,Coalisland,Cookstown,Draperstown,Magherafelt,Pomeroy', '1', '0', '', '', ''),
(480, 'Derry and Strabane', 'gb', 'GB-DRS', 'Northern Ireland', 'Londonderry', 'Campsey,Castelderg,Claudy,Culmore,Newtownstewart,Sion Mills,Strabane', '1', '0', '', '', ''),
(481, 'Buckinghamshire', 'gb', 'GB-BKM', 'England', 'Aylesbury', 'Milton Keynes,High Wycombe,Buckingham,Great Missenden,Marlow,Stoke Mandeville,Winslow,Wotton Underwood,Haddenham', '1', '0', '', '', ''),
(482, 'Greater London', 'gb', 'GB-LND', 'England', '', '', '1', '0', '', '', ''),
(483, 'West Midlands', 'gb', '', 'England', 'Birmingham', 'Wolverhampton,Dudley,Bromley,Stourbridge,Solihull,Walsall,Willenhall,Bloxwich,Aldridge,Walsall Wood,Oldbury,Smethwick,Blackheath,Wednesbury,Cradley Heath,Rowley Regis,Tipton,West Bromwich', '1', '0', '', '', ''),
(484, 'Greater Manchester', 'gb', '', 'England', 'Manchester', 'Bury,Whitefield,Tottington,Radcliffe,Ramsbottom,Irlam,Pendlebury,Salford,Swinton,Worsley,Eccles,Bolton,Prestwich,Oldham,Royton,Failsworth,Chadderton,Shaw and Crompton,Rochdale,Littleborough,Middleton,Milnrow,Heywood,Stockport,Bredbury,Marple,Ashton-under-Lyne,Dukinfield,Denton,Droylsden,Mossley,Audenshaw,Stalybridge,Hyde,Stretford,Altrincham,Sale,Urmston,Partington,Wigan,Leigh,Atherton,Ashton-in-Makerfield,Golborne,Ince-in-Makerfield\r\n,Standish,Tyldesley,Hindley', '1', '0', '', '', ''),
(485, 'West Yorkshire', 'gb', '', 'England', 'Leeds', 'Batley,Colne Valley,Dewsbury,Heckmondwike,Huddersfield,Meltham,Mirfield,Spenborough,Halifax,Brighouse,Elland,Hebden Royd,Shelf,Sowerby Bridge,Todmorden,Bingley,Bradford,Denholme,Keighley,Silsden,Aireborough,East Ardsley,Garforth,Horsforth,Morley,Otley,Pudsey,Rothwell,Wetherby,Wakefield,Castleford,Hemsworth,Horbury,Knottingley,Normanton,Ossett,Pontefract,South Elmsall,South Kirkby and Moorthorpe,Featherstone,Stanley', '1', '0', '', '', ''),
(486, 'Hampshire', 'gb', 'GB-HAM', 'England', 'Southampton', 'Winchester,Gosport,Fareham,Bishops Waltham,Waterlooville,Bordon,Alton,Petersfield,Blackwater,Yateley,Fleet,Hook,Aldershot,Farnborough,Basingstoke,Tadley,Andover,Romsey,Eastleigh,Hedge End,Lymington,New Milton,Ringwood,Totton and Eling,Fordingbridge,Southampton,Portsmouth', '1', '0', '', '', ''),
(487, 'Essex', 'gb', 'GB-ESS', 'England', 'Chelmsford', 'Harlow,Epping,Chipping Ongar,Chigwell,Waltham Abbey,Loughton,Buckhurst Hill,Brentwood,Basildon,Billericay,Pitsea,Wickford,Rochford,Rayleigh,Maldon,Burnham-on-Crouch,South Woodham Ferrers,Saffron Walden,Great Dunmow,Braintree,Coggeshall,Witham,Halstead,Colchester,Wivenhoe,West Mersea,Brightlingsea,Dovercourt,Clacton-on-Sea,Manningtree,Walton-on-the-Naze\r\n,Frinton-on-Sea,Harwich,Holland-on-Sea,Thurrock,Southend-on-Sea\r\n', '1', '0', '', '', ''),
(488, 'Kent', 'gb', 'GB-KEN', 'England', 'Maidstone', 'Sevenoaks,Swanley,Edenbridge,Dartford,Swanscombe,Gravesend,Northfleet,West Malling,Tonbridge,Snodland,Gillingham,Rochester,Chatham,Paddock Wood,Royal Tunbridge Wells,Southborough,Queenborough,Sittingbourne,Faversham,Ashford,Tenterden,Canterbury,Whitstable,Fordwich,Herne Bay,Folkestone,Lydd,New Romney,Broadstairs,Margate,Ramsgate,Dover,Deal,Sandwich', '1', '0', '', '', ''),
(489, 'Lancashire', 'gb', 'GB-LAN', 'England', 'Preston', 'Blackburn ,Blackpool,Burnley,Nelson,Clitheroe,Rawtenstall,Haslingden,Bacup,Walton-le-Dale,Bamber Bridge,Lostock Hall,Leyland,Lancaster,Fleetwood,Garstang,Poulton-le-Fylde,Thornton-Cleveleys,Ormskirk,Skelmersdale,Lytham St Annes,Accrington,Great Harwood,Clayton-le-Moors,Oswaldtwistle,Rishton,Chorley', '1', '0', '', '', ''),
(490, 'Merseyside', 'gb', '', 'England', 'Liverpool', 'Bootle,Crosby,Maghull,Southport,Formby,Kirkby,Prescot,Huyton,Halewood,St Helens,Newton-le-Willows,Haydock,Bebington,Birkenhead,Heswall,Wallasey,Hoylake', '1', '0', '', '', ''),
(491, 'South Yorkshire', 'gb', '', 'England', 'Sheffield', 'Barnsley,Brierley,Penistone,Wombwell,Hoyland,Doncaster,Askern,Bawtry,Conisbrough,Mexborough,Stainforth,Tickhill,Thorne,Hatfield,Edlington,Rotherham,Dinnington,Maltby,Swinton,Wath upon Dearne', '1', '0', '', '', ''),
(492, 'Devon', 'gb', 'GB-DEV', 'England', 'Exeter', 'Exmouth,Tiverton,Cullompton,Crediton,Barnstaple,Braunton,Fremington,Ilfracombe,Instow,South Molton,Lynton,Lynmouth,Bideford,Holsworthy,Great Torrington,Hartland,Westward Ho!,Tavistock,Chagford,Okehampton,Princetown,Totnes,Dartmouth,Kingsbridge,Ivybridge,Salcombe,Newton Abbot,Teignmouth,Plymouth,Ashburton,Dawlish,Widecombe-in-the-Moor,Torquay', '1', '0', '', '', ''),
(493, 'Surrey', 'gb', 'GB-SRY', 'England', 'Kingston-upon-Thames', 'Guildford,Staines-upon-Thames,Sunbury-on-Thames,Ashford,Shepperton,Addlestone,Chertsey,Camberley,Frimley,Woking,Esher,Walton-on-Thames,Molesey,Cobham,Weybridge,Godalming,Farnham,Haslemere,Dorking,Leatherhead,Epsom,Reigate,Banstead,Redhill,Horley,Oxted,Caterham', '1', '0', '', '', ''),
(494, 'Tyne and Wear', 'gb', '', 'England', 'Newcastle', 'Tynemouth,Whitley Bay,Wallsend,Preston,South Shields,Killingworth,Cullercoats,Sunderland,Gateshead', '1', '0', '', '', ''),
(495, 'North Yorkshire', 'gb', 'GB-NYK', 'England', 'Northallerton', 'Scarborough,Whitby,Filey,Harrogate,Ripon,Knaresborough,Skipton,Middlesbrough,Malton,Redcar,Guisborough,Richmond,Selby,Tadcaster,York,Stockton-on-Tees,Norton,Eaglescliffe,Billingham,Thirsk', '1', '0', '', '', ''),
(496, 'Hertfordshire', 'gb', 'GB-HRT', 'England', 'Hertford', 'Watford,Rickmansworth,Borehamwood,Potters Bar,Bushey,Welwyn Garden City,Cheshunt,Hoddesdon,Waltham Cross,Broxbourne,Bishop\'s Stortford,Sawbridgeworth,Ware,Stevenage,Letchworth Garden City,Hitchin,Royston,Baldock,St Albans,Harpenden,Hemel Hempstead,Tring,Berkhamsted', '1', '0', '', '', ''),
(497, 'Staffordshire', 'gb', 'GB-STS', 'England', 'Stafford', 'Stone,Tamworth,Lichfield,Burntwood,Cannock,Rugeley,Hednesford,Codsall,Newcastle-under-Lyme,Kidsgrove,Leek,Biddulph,Burton upon Trent,Uttoxeter,Stoke-on-Trent', '1', '0', '', '', ''),
(498, 'Nottinghamshire', 'gb', 'GB-NTT', 'England', 'Nottingham', 'Mansfield,Retford,West Bridgford,Bingham,Newark-on-Trent,Arnold,Kirkby-in-Ashfield,Sutton-in-Ashfield,Sutton-in-Ashfield,Beeston,Eastwood,Stapleford,Kelham,Ollerton', '1', '0', '', '', ''),
(499, 'Lincolnshire', 'gb', 'GB-LIN', 'England', 'Lincoln', 'Sleaford,North Hykeham,Grantham,Bourne,Stamford,Spalding,Boston,Manby,Louth,Mablethorpe,Skegness,Gainsborough,Scunthorpe,Barton-upon-Humber,Grimsby,Immingham,Cleethorpes\r\n', '1', '0', '', '', ''),
(500, 'Cheshire', 'gb', 'GB-CHW', 'England', 'Chester', 'Winsford,Neston,Northwich,Frodsham,Ellesmere Port,Sandbach,Crewe,Alsager,Macclesfield,Middlewich,Nantwich,Knutsford,Poynton,Wilmslow,Warrington,Widnes,Runcorn', '1', '0', '', '', ''),
(501, 'Derbyshire', 'gb', 'GB-DBY', 'England', 'Matlock', 'Derby,Buxton,Glossop,New Mills,Chapel-en-le-Frith,Ashbourne,Swadlincote,Newhall,Ilkeston,Long Eaton,Ripley,Heanor,Belper,Alfreton,Wingerworth,Dronfield,Killamarsh,Clay Cross,Eckington,Chesterfield,Clowne,Shirebrook,Bolsover', '1', '0', '', '', ''),
(502, 'Leicestershire', 'gb', 'GB-LEC', 'England', 'Glenfield', 'Leicester,Shepshed,Syston,Loughborough,Melton Mowbray,Market Harborough,Lutterworth,Wigston,Oadby,Narborough,Braunstone Town,Hinckley,Earl Shilton,Coalville,Ashby-de-la-Zouch', '1', '0', '', '', ''),
(503, 'Somerset', 'gb', 'GB-SOM', 'England', 'Bath', 'Glastonbury,Bridgwater,Weston-super-Mare,Wells,Portishead,Clevedon,Nailsea,Yeovil,Somerton,Chard,Taunton,Wellington,Williton,Minehead,Burnham-on-Sea,Shepton Mallet,Frome,Keynsham,Midsomer Norton,Radstock', '1', '0', '', '', ''),
(504, 'County Durham', 'gb', 'GB-DUR', 'England', 'Durham', 'Darlington,Hartlepool,Stockton-on-Tees', '1', '0', '', '', ''),
(505, 'Norfolk', 'gb', 'GB-NFK', 'England', 'Norwich', 'Great Yarmouth,King\'s Lynn,Long Stratton,Diss,Wymondham,Thorpe St Andrew,Aylsham,Cromer,North Walsham,Fakenham,Sheringham,Downham Market,Dereham,Thetford,Swaffham,Watton,Attleborough', '1', '0', '', '', ''),
(506, 'Gloucestershire', 'gb', 'GB-GLS', 'England', 'Gloucester', 'Cheltenham,Stroud,Cirencester,Tewkesbury,Coleford,Lydney,Cinderford,Yate,Thornbury,Filton,Patchway,Bradley Stoke', '1', '0', '', '', ''),
(507, 'West Berkshire', 'gb', 'GB-WBK', 'England', 'Reading', 'Slough,Windsor,Newbury,Sandhurst,Bracknell,Wokingham,Thatcham', '1', '0', '', '', ''),
(508, 'West Sussex', 'gb', 'GB-WSX', 'England', 'Chichester', 'Worthing,Selsey,Littlehampton,Bognor Regis,Horsham,Crawley,Haywards Heath,East Grinstead,Burgess Hill,Shoreham-by-Sea,Southwick', '1', '0', '', '', ''),
(509, 'East Sussex', 'gb', 'GB-ESX', 'England', 'Lewes', 'Brighton,Hove,Seaford,Telscombe,Newhaven,Eastbourne,Hailsham,Polegate,Crowborough,Uckfield,Bexhill-on-Sea,Rye,Hastings', '1', '0', '', '', ''),
(510, 'Cambridgeshire', 'gb', 'GB-CAM', 'England', 'Cambridge', 'Peterborough,Huntingdon,St Neots,March,Wisbech,Whittlesey,Chatteris,Ely,Soham,St Ives,Ramsey,Godmanchester', '1', '0', '', '', ''),
(511, 'Buckinghamshire', 'gb', 'GB-BKM', 'England', 'Aylesbury', 'Milton Keynes,Wycombe,Buckingham,Chesham,Amersham,Denham,Beaconsfield,Gerrards Cross', '1', '0', '', '', ''),
(512, 'Suffolk', 'gb', 'GB-SFK', 'England', 'Ipswich', 'Lowestoft,Bury St Edmunds,Woodbridge,Felixstowe,Kesgrave,Beccles,Stowmarket,Hadleigh,Haverhill,Mildenhall,Brandon,Newmarket', '1', '0', '', '', ''),
(513, 'Dorset', 'gb', 'GB-DOR', 'England', 'Dorchester', 'Bournemouth,Poole,Weymouth,Isle of Portland,Sherborne,Bridport,Shaftesbury,Blandford Forum,Wareham,Swanage,Furzehill,Verwood,Wimborne Minster,Ferndown,Christchurch', '1', '0', '', '', ''),
(514, 'Northamptonshire', 'gb', 'GB-NTH', 'England', 'Northampton', 'Daventry,Towcester,Brackley,Wellingborough,Kettering,Desborough,Burton Latimer,Corby,Thrapston,Raunds,Irthlingborough,Higham Ferrers', '1', '0', '', '', ''),
(515, 'Wiltshire', 'gb', 'GB-WIL', 'England', 'Trowbridge', 'Swindon,Wiltshire,Salisbury,Amesbury,Westbury,Warminster,Royal Wootton Bassett,Melksham,Devizes,Corsham,Chippenham,Calne', '1', '0', '', '', ''),
(516, 'Oxfordshire', 'gb', 'GB-OXF', 'England', 'Oxford', 'Banbury,Bicester,Abingdon,Witney,Carterton,Chipping Norton,Milton,Didcot,Henley-on-Thames,Wallingford', '1', '0', '', '', ''),
(517, 'Bedfordshire', 'gb', 'GB-BDF', 'England', 'Bedford', 'Kempston,Luton,Dunstable,Biggleswade,Ampthill,Houghton Regis,Flitwick,Stotfold,Sandy,Linslade,Leighton Buzzard', '1', '0', '', '', ''),
(518, 'East Riding of Yorkshire', 'gb', 'GB-ERY', 'England', 'Beverley', 'Kingston upon Hull,Bridlington,Driffield,Hessle,Pocklington,Hornsea', '1', '0', '', '', ''),
(519, 'Worcestershire', 'gb', 'GB-WOR', 'England', 'Worcester', 'Malvern,Kidderminster,Stourport-on-Severn,Bewdley,Bromsgrove,Redditch,Pershore,Evesham,Droitwich Spa', '1', '0', '', '', ''),
(520, 'Cornwall', 'gb', 'GB-CON', 'England', 'Truro', 'Bodmin,Bude,Camborne,Fallmouth,Hayle,Helston,Launceston,Liskeard,Newquay,Penryn,Penzance,Redruth,Saltash,St. Ives,St.Austell,Torpoint,Wadebridge', '1', '0', '', '', ''),
(521, 'Warwickshire', 'gb', 'GB-WAR', 'England', 'Royal Leamington Spa', 'Warwick,Kenilworth,Whitnash,Stratford-upon-Avon,Southam,Rugby,Nuneaton,Bedworth,Atherstone', '1', '0', '', '', ''),
(522, 'Cumbria', 'gb', 'GB-CMA', 'England', 'Carlisle', 'Penrith,Workington,Whitehaven,Barrow-in-Furness', '1', '0', '', '', ''),
(523, 'Shropshire', 'gb', 'GB-SHR', 'England', 'Shrewsbury', 'Telford,Oswestry', '1', '0', '', '', ''),
(524, 'Bristol', 'gb', 'GB-BST', 'England', '', '', '1', '0', '', '', ''),
(525, 'Northumberland', 'gb', 'GB-NBL', 'England', 'Morpeth', '', '1', '0', '', '', ''),
(526, 'Herefordshire', 'gb', 'GB-HEF', 'England', 'Hereford', '', '1', '0', '', '', ''),
(527, 'Isle of Wight', 'gb', 'GB-IOW', 'England', 'Newport', '', '1', '0', '', '', ''),
(528, 'Rutland', 'gb', 'GB-RUT', 'England', 'Oakham', '', '1', '0', '', '', ''),
(529, 'Anglesey', 'gb', 'GB-AGY', 'Wales', 'Llangefni', 'Holyhead', '1', '0', '', '', ''),
(530, 'Blaenau Gwent', 'gb', 'GB-BGW', 'Wales', 'Ebbw Vale', 'Brynmawr,Tredegar,Abertillery', '1', '0', '', '', ''),
(531, 'Bridgend', 'gb', 'GB-BGE', 'Wales', 'Bridgend', 'Pyle,Porthcawl,Maesteg', '1', '0', '', '', ''),
(532, 'Gwynedd', 'gb', 'GB-GWN', 'Wales', 'Caernarfon', 'Bangor', '1', '0', '', '', ''),
(533, 'Denbighshire', 'gb', 'GB-DEN', 'Wales', 'Ruthin', 'Rhyl,Prestatyn,Danby', '1', '0', '', '', ''),
(534, 'Vale of Glamorgan', 'gb', 'GB-VGL', 'Wales', 'Barry', 'Penarth,Llantwit Major', '1', '0', '', '', ''),
(535, 'Caerphilly', 'gb', 'GB-CAY', 'Wales', 'Hengoed', 'Caerphilly,Risca,Gelligaer,Blackwood,Bargoed,Oakdale,Abercarn', '1', '0', '', '', ''),
(536, 'Cardiff', 'gb', 'GB-CRF', 'Wales', 'Cardiff', '', '1', '0', '', '', ''),
(537, 'Carmarthenshire', 'gb', 'GB-CMN', 'Wales', 'Carmarthen', 'Llanelli,Ammanford', '1', '0', '', '', ''),
(538, 'Ceredigion', 'gb', 'GB-CGN', 'Wales', 'Aberaeron', 'Aberystwyth', '1', '0', '', '', ''),
(539, 'Conwy', 'gb', 'GB-CWY', 'Wales', 'Conwy', 'Colwyn Bay,Llandudno,Abergele', '1', '0', '', '', ''),
(540, 'Merthyr Tydfil', 'gb', 'GB-MTY', 'Wales', 'Merthyr Tydfil', 'Treharris', '1', '0', '', '', ''),
(541, 'Monmouthshire', 'gb', 'GB-MON', 'Wales', 'Cwmbran', 'Abergavenny,Caldicot,Monmouth,Chepstow', '1', '0', '', '', ''),
(542, 'Neath Port Talbot', 'gb', 'GB-NTL', 'Wales', 'Port Talbot', 'Neath,Pontardeyv', '1', '0', '', '', ''),
(543, 'Newport', 'gb', 'GB-NWP', 'Wales', 'Newport', '', '1', '0', '', '', ''),
(544, 'Pembrokeshire', 'gb', 'GB-PEM', 'Wales', 'Haverfordwest', 'Milford Haven', '1', '0', '', '', ''),
(545, 'Powys', 'gb', 'GB-POW', 'Wales', 'Llandrindod Wells', 'Newtown', '1', '0', '', '', ''),
(546, 'Rhondda Cynon Taf', 'gb', 'GB-RCT', 'Wales', 'Clydach Vale', 'Rhondda,Aberdare,Pontypridd,Mountain Ash,Llantrisant', '1', '0', '', '', ''),
(547, 'Swansea', 'gb', 'GB-SWA', 'Wales', 'Swansea', 'Gorsenon', '1', '0', '', '', ''),
(548, 'Torfaen', 'gb', 'GB-TOF', 'Wales', 'Pontypool', 'Cwmbran', '1', '0', '', '', ''),
(549, 'Flintshire', 'gb', 'GB-FLN', 'Wales', 'Mold', 'Shotton,Buckley,Connah\'s Quay,Flint', '1', '0', '', '', ''),
(550, 'Aberdeenshire', 'gb', 'GB-ABD', 'Scotland', 'Aberdeen ', 'Peterhead,Fraserburgh,Inverurie,Westhill,Stonehaven', '1', '0', '', '', ''),
(551, 'City of Aberdeen', 'gb', 'GB-ABE', 'Scotland', 'Aberdeen', '', '1', '0', '', '', ''),
(552, 'Angus', 'gb', 'GB-ANS', 'Scotland', 'Forfar', 'Arbroath,Montrose,Carnoustie', '1', '0', '', '', ''),
(553, 'Argyll and Bute', 'gb', 'GB-AGB', 'Scotland', 'Lochgilphead', 'Helensburgh,Oban', '1', '0', '', '', ''),
(554, 'City of Glasgow', 'gb', 'GB-GLG', 'Scotland', 'Glasgow', '', '1', '0', '', '', ''),
(555, 'Dumfries and Galloway', 'gb', 'GB-DGY', 'Scotland', 'Dumfries', 'Stranraer', '1', '0', '', '', ''),
(556, 'Dundee-City', 'gb', 'GB-DND', 'Scotland', 'Dundee', '', '1', '0', '', '', ''),
(557, 'City of Edinburgh', 'gb', 'GB-EDH', 'Scotland', 'Edinburgh', 'Queensferry', '1', '0', '', '', ''),
(558, 'West Dunbartonshire', 'gb', 'GB-WDU', 'Scotland', 'Dumbarton', 'Clydebank,Alexandria,Bonhill', '1', '0', '', '', ''),
(559, 'West Lothian', 'gb', 'GB-WWLN', 'Scotland', 'Livingston', 'Bathgate,Broxburn,Linlithgow,Armadale,Whitburn', '1', '0', '', '', ''),
(560, 'The Western Isles', 'gb', 'GB-ELS', 'Scotland', 'Stornoway', '', '1', '0', '', '', ''),
(561, 'Inverclyde', 'gb', 'GB-IVC', 'Scotland', 'Greenock', 'Port Glasgow,Gourock', '1', '0', '', '', ''),
(562, 'Clackmannanshire', 'gb', 'GB-CLK', 'Scotland', 'Alloa', '', '1', '0', '', '', ''),
(563, 'Moray ', 'gb', 'GB-MRY', 'Scotland', 'Elgin', 'Buckie,Forres', '1', '0', '', '', ''),
(564, 'Orkney Islands', 'gb', 'GB-ORK', 'Scotland', 'Kirkwall', '', '1', '0', '', '', ''),
(565, 'Perth and Kinross', 'gb', 'GB-PKN', 'Scotland', 'Perth', '', '1', '0', '', '', ''),
(566, 'South Ayrshire', 'gb', 'GB-SAY', 'Scotland', 'Ayr', 'Prestwick,Troon', '1', '0', '', '', ''),
(567, 'South Lanarkshire', 'gb', 'GB-SLK', 'Scotland', 'Hamilton', 'East Kilbride,Rutherglen,Cambuslang,Blantyre,Larkhall,Carluke', '1', '0', '', '', ''),
(568, 'North Ayrshire', 'gb', 'GB-NAY', 'Scotland', 'Irvine', 'Kilwinning,Saltcoats,Largs,Ardrossan', '1', '0', '', '', ''),
(569, 'North Lanarkshire', 'gb', 'GB-NLK', 'Scotland', 'Motherwell', 'Cumbernauld,Coatbridge,Airdrie,Wishaw,Bellshill,Viewpark', '1', '0', '', '', ''),
(570, 'Renfrewshire', 'gb', 'GB-RFW', 'Scotland', 'Paisley', 'Renfrew,Johnstone,Erskine,Linwood', '1', '0', '', '', ''),
(571, 'Midlothian', 'gb', 'GB-MLN', 'Scotland', 'Dalkeith', 'Penicuik,Bonnyrigg,Mayfield', '1', '0', '', '', ''),
(572, 'Stirling', 'gb', 'GB-STG', 'Scotland', 'Stirling', '', '1', '0', '', '', ''),
(573, 'East Dunbartonshire', 'gb', 'GB-EDU', 'Scotland', 'Kirkintilloch', 'Bearsden,Bishopbriggs,Milngavie,Lenzie', '1', '0', '', '', ''),
(574, 'East Ayrshire', 'gb', 'GB-EAY', 'Scotland', 'Kilmarnock', '', '1', '0', '', '', ''),
(575, 'East Lothian', 'gb', 'GB-ELN', 'Scotland', 'Haddington', 'Musselburgh,Tranent', '1', '0', '', '', ''),
(576, 'East Renfrewshire', 'gb', 'GB-ERW', 'Scotland', 'Giffnock', 'Newton Mearns,Clarkston,Barrhead', '1', '0', '', '', ''),
(577, 'Fife', 'gb', 'GB-FIF', 'Scotland', 'Glenrothes', 'Kirkcaldy,Dunfermline,St Andrews,Buckhaven,Rosyth,Cowdenbeath,Dalgety Bay', '1', '0', '', '', ''),
(578, 'Falkirk', 'gb', 'GB-FAL', 'Scotland', 'Falkirk', 'Polmont,Stenhousemuir,Grangemouth,Borrowstounness,Denny', '1', '0', '', '', ''),
(579, 'Highland', 'gb', 'GB-HLD', 'Scotland', 'Inverness', 'Fort William', '1', '0', '', '', ''),
(580, 'Shetland Islands', 'gb', 'GB-ZET', 'Scotland', 'Lerwick', '', '1', '0', '', '', ''),
(581, 'Scottish Borders', 'gb', 'GB-SCB', 'Scotland', 'Newtown St Boswells', 'Hawick,Galashiels', '1', '0', '', '', '');

-- --------------------------------------------------------

--
-- Структура таблицы `service_city`
--

CREATE TABLE `service_city` (
  `id` int(11) NOT NULL,
  `city` varchar(55) NOT NULL,
  `region` varchar(55) NOT NULL,
  `country` varchar(3) NOT NULL,
  `worker_id` text NOT NULL COMMENT 'Перелік id надавачів послуг',
  `date_first` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Міста в яких надаються послуги та ким';

-- --------------------------------------------------------

--
-- Структура таблицы `service_order`
--

CREATE TABLE `service_order` (
  `id` int(11) NOT NULL,
  `user` varchar(15) NOT NULL,
  `service` varchar(15) NOT NULL,
  `status` varchar(6) NOT NULL COMMENT 'Статус ордера',
  `date` datetime NOT NULL,
  `user_fb` int(1) NOT NULL,
  `service_fb` int(1) NOT NULL,
  `user_pay` int(11) NOT NULL COMMENT 'Скільки по словам користувача він заплатив',
  `service_pay` int(11) NOT NULL COMMENT 'Скільки по словами надавача послуг йому заплатили',
  `country` varchar(3) NOT NULL,
  `region` varchar(33) NOT NULL,
  `city` varchar(44) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Факти замовлень послуги';

-- --------------------------------------------------------

--
-- Структура таблицы `sites`
--

CREATE TABLE `sites` (
  `id` tinyint(4) NOT NULL,
  `url` varchar(20) NOT NULL COMMENT 'domain.com site',
  `version` int(6) NOT NULL DEFAULT '1' COMMENT 'Версія сайту. Ця змінна потрібна, щоб вебдодаток міг перезавантажувати ресурси при оновлені сайту',
  `active` varchar(8) NOT NULL DEFAULT 'yes' COMMENT 'Признак - активен ли сайт, работает ли. Может быть yes, no, а также default - это значит что это основной сайт на который все рефераловоды должны водить своих рефералов! Из-за того что сайт попадает в наклейку рефералам - нельзя менять дефолтный без нужды, а если менять, то нужно в идеале делать рассылку активным партнёрам... Також дефолтний може бути тільки ОДИН',
  `bot` varchar(55) NOT NULL COMMENT 'If it is not empty, then the site is working with a telegram bot and the bot name is in this cell',
  `visual_theme` varchar(20) NOT NULL DEFAULT 'black' COMMENT 'Тема оформления сайта',
  `topColor` varchar(10) NOT NULL DEFAULT '#202020' COMMENT 'the color in which mobile chrome will be painted. It adds uniqueness to the site and helps with cloaking as well. In short, it’s better to use at least a slightly unique color for each site',
  `pageColor` varchar(10) NOT NULL COMMENT 'Колір сторінки по замовчуванню',
  `lg` varchar(2) NOT NULL COMMENT 'language сайта який вмикається по замовчуванню',
  `country` varchar(150) NOT NULL DEFAULT 'ua,gb' COMMENT 'list of countries this site works with (перелік через кому)',
  `brand` varchar(25) NOT NULL COMMENT 'Website or seller name for h1 logo',
  `GoogleAccount` varchar(40) NOT NULL COMMENT 'Google email on which the site is advertised',
  `GoogleTagManager` varchar(14) NOT NULL COMMENT 'Google Tag Manager container code',
  `adwords` varchar(3) NOT NULL COMMENT 'Ознака (yes або no) означає - чи можна цей сайт піарити в гугл адвордс',
  `info_email` varchar(30) NOT NULL DEFAULT '' COMMENT 'Primary email for outgoing emails',
  `info_email_from` varchar(45) NOT NULL DEFAULT 'Support' COMMENT 'Sender''s name that is displayed in the list of letters in the client''s inbox',
  `info_email_pass` varchar(55) NOT NULL,
  `security` varchar(35) NOT NULL COMMENT 'Email of the security service',
  `security_from` varchar(55) NOT NULL DEFAULT 'Security' COMMENT 'The name of the security service',
  `security_pass` varchar(55) NOT NULL,
  `conversia` varchar(44) NOT NULL DEFAULT 'ConvVerifEmailUspeh' COMMENT 'у разі видимості цього блоку на сторінці - google tag зарахує конверсію підтвердження email',
  `https` varchar(3) NOT NULL DEFAULT 's' COMMENT 'Если сайт работает с https то тут ставим просто маленькую букву s, а если не работает, то оставляем пустым',
  `bot_token` varchar(66) NOT NULL COMMENT 'Токен бота. Без него он не сможет работать',
  `smtp_server` varchar(50) NOT NULL COMMENT 'SMTP сервер для отправки почты',
  `smtp_port` varchar(4) NOT NULL COMMENT 'SMTP порт для отправки почты',
  `smtp_active` varchar(3) NOT NULL DEFAULT 'yes' COMMENT 'Should I use SMTP instead of phpmail()',
  `tls` varchar(10) NOT NULL DEFAULT 'ssl' COMMENT 'Enable TLS encryption: ssl (also accepted)',
  `DKIM_active` varchar(3) NOT NULL COMMENT 'Чи потрібно вмикати DKIM для сайту? Ставити yes потрібно тільки після того, як налаштовані всі дані, АЛЕ на ukraine.com.ua НЕ потрібно нічого включати, там працює з коробки саме',
  `DKIM_domain` varchar(200) NOT NULL,
  `NPAPIKEY` varchar(44) NOT NULL COMMENT 'Nova Poshta API key for parcel tracking',
  `type` varchar(8) NOT NULL COMMENT 'Тип сайту',
  `FilterPrblmRegion` varchar(3) NOT NULL DEFAULT 'yes' COMMENT 'Чи використовуємо ми на цьому сайті фільтрацію проблемих регіонів? Тобто, якщо не треба щоб в переліку регіонів чито міст відображались проблемні, то ставимо yes',
  `MODE` varchar(4) NOT NULL DEFAULT 'WORK' COMMENT 'TEST or WORK (замість константи)'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) UNSIGNED NOT NULL,
  `email` varchar(111) NOT NULL,
  `date` datetime DEFAULT NULL,
  `comment` text,
  `type` varchar(13) NOT NULL COMMENT 'Тип таска',
  `answer` varchar(3333) NOT NULL COMMENT 'Яка була дана відповідь на цей тікет',
  `status` varchar(10) NOT NULL COMMENT 'Статус тікета',
  `for_partners` text NOT NULL COMMENT 'Партнери яким можна показувати даний тікет',
  `ip` varchar(40) NOT NULL COMMENT 'IP клиента в момент данного происшествия',
  `phone` varchar(15) NOT NULL COMMENT 'Номер телефона. Заполняется наверное только при обращении в поддержку',
  `FingerPrint` varchar(66) NOT NULL COMMENT 'Отпечаток браузера',
  `Browser` varchar(155) NOT NULL COMMENT 'Браузер клиента'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Задачі які відображаються в кабінетах';

-- --------------------------------------------------------

--
-- Структура таблицы `tg_bots`
--

CREATE TABLE `tg_bots` (
  `id` int(5) NOT NULL,
  `name` varchar(33) NOT NULL COMMENT 'Имя бота',
  `admins` varchar(300) NOT NULL COMMENT 'id админов этого бота',
  `usher` varchar(122) NOT NULL COMMENT 'Білетери, помічники адміна, які мають додаткові права в боті',
  `token` varchar(100) NOT NULL,
  `reg` date NOT NULL COMMENT 'Дата регистрации',
  `status` varchar(33) NOT NULL DEFAULT 'yes' COMMENT 'active (yes) or not',
  `description` varchar(500) NOT NULL,
  `from_id` varchar(22) NOT NULL COMMENT 'id бота',
  `some` varchar(66) NOT NULL COMMENT 'Список чатов в которых просим бота молчать или вести себя как-то иначе',
  `slogan` varchar(300) NOT NULL COMMENT 'Некий КОРОТКИЙ слоган (до 30-50 букв) - презентация бота. Используется например в шапке email. Может содержать html',
  `support` varchar(66) NOT NULL COMMENT 'Telegram customer support account',
  `info_email` varchar(55) NOT NULL,
  `info_email_from` varchar(44) NOT NULL,
  `info_email_pass` varchar(33) NOT NULL,
  `smtp_server` int(33) NOT NULL,
  `smtp_port` varchar(5) NOT NULL,
  `tls` varchar(3) NOT NULL,
  `country` varchar(2) NOT NULL COMMENT 'Одна країна в якій працює цей бот',
  `type` varchar(8) NOT NULL DEFAULT 'bot' COMMENT 'В якій схемі працює бот (shop,bot,credit etc)',
  `site` varchar(44) NOT NULL COMMENT 'Сайт на який посилається бот $host',
  `FilterPrblmRegion` varchar(3) NOT NULL DEFAULT 'yes' COMMENT 'Чи використовуємо ми для цього бота фільтрацію проблемних регіонів (yes,no)',
  `channel` varchar(16) NOT NULL COMMENT 'ID каналу бота',
  `aboutbot` varchar(58) NOT NULL COMMENT 'Посилання на пост про бот в каналі',
  `hello` text NOT NULL COMMENT 'Вітальне повідомлення бота (замість базового)\r\nBot welcome message (instead of the basic one)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Список всех ботов работающих на этой платформе';

-- --------------------------------------------------------

--
-- Структура таблицы `tg_bots_dop`
--

CREATE TABLE `tg_bots_dop` (
  `id` int(5) NOT NULL,
  `name` varchar(33) NOT NULL COMMENT 'Имя бота',
  `from_id` int(11) NOT NULL COMMENT 'id tg_bots',
  `project` varchar(10) NOT NULL DEFAULT 'bot' COMMENT 'Конкретний проєкт. Інколи може знадобитися зміна для більш тонкого налаштування бота',
  `manager_mail` varchar(26) NOT NULL COMMENT 'може відрізнятись від того з якого надсилається пошта',
  `service` varchar(22) NOT NULL DEFAULT 'SERVICE PROVIDER' COMMENT 'Надавач послуг, постачальник',
  `servicu` varchar(35) NOT NULL DEFAULT 'TO THE SERVICE PROVIDER' COMMENT 'постачальнику ! (кому)?',
  `oferservice` varchar(66) NOT NULL COMMENT 'Посилання на окремо викладені умови, які потенційний надавач послуг має прийняти.\r\nReference to the separately set out terms that the potential service provider must accept. ',
  `oferbase` varchar(88) NOT NULL COMMENT 'Посилання на базову угоду користувача. link',
  `FilterPrblmRegion` varchar(3) NOT NULL DEFAULT 'yes' COMMENT 'Чи використовуємо ми для цього бота фільтрацію проблемних регіонів (yes,no)',
  `baza_data` varchar(77) NOT NULL COMMENT 'Посилання на форму збору даних, якщо треба. link'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Список всех ботов работающих на этой платформе';

-- --------------------------------------------------------

--
-- Структура таблицы `tg_bundle`
--

CREATE TABLE `tg_bundle` (
  `id` int(11) NOT NULL,
  `bot` varchar(25) NOT NULL COMMENT 'Имя бота к которому привязан клиент',
  `from_id` varchar(15) NOT NULL COMMENT 'Унікальні ідентифікатори юзера, знаючи які ми можемо відправити йому повідомлення',
  `username` varchar(55) NOT NULL COMMENT 'Як підписаний в телеге',
  `email` varchar(77) NOT NULL COMMENT 'Мыло клиента',
  `phone` varchar(14) NOT NULL,
  `region` varchar(55) NOT NULL,
  `city` varchar(77) NOT NULL,
  `status` varchar(10) NOT NULL COMMENT 'Может быть пусто или admin, work, black. Статусы помогают  присваивать роли или банить клиента',
  `step` varchar(15) NOT NULL COMMENT 'Поле яке дозволяє боту тримати контекст спілкування',
  `country` varchar(2) NOT NULL COMMENT 'Страна клиента',
  `lg` varchar(2) NOT NULL,
  `date_reg` datetime NOT NULL,
  `date_phone` date NOT NULL COMMENT 'Дата останнього оновлення номеру телефону',
  `newemail` varchar(77) NOT NULL COMMENT 'Новый эмаил на который клиент хочет заменить старый',
  `kod` varchar(9) NOT NULL COMMENT 'Код который был выслан на эмаил при регистрации или ok если код проверен',
  `trykod` varchar(1) NOT NULL COMMENT 'Количество попыток введения кода',
  `kodtime` datetime NOT NULL COMMENT 'Время отправки кода',
  `mst` varchar(12) NOT NULL COMMENT 'Различные статусы - в первую очередь касающиеся эмаил или блокировки клиента',
  `user_key` varchar(33) NOT NULL COMMENT 'md5 ключ для простой идентификации юзера',
  `setmail` datetime NOT NULL COMMENT 'Дата и время установки эмаил',
  `role` varchar(14) NOT NULL COMMENT 'В деяких ботах може надаватись можливість обрати роль',
  `dopphone` varchar(14) NOT NULL COMMENT 'Додатковий номер телефону'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Структура таблицы `tg_deeplink`
--

CREATE TABLE `tg_deeplink` (
  `id` int(11) NOT NULL,
  `date` datetime NOT NULL COMMENT 'дата последней отправки',
  `email` varchar(77) NOT NULL,
  `user_key` varchar(33) NOT NULL,
  `counter` int(2) NOT NULL COMMENT 'Количество проигнорированных отправок диплинков на этот адрес'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Структура таблицы `tg_group_setup`
--

CREATE TABLE `tg_group_setup` (
  `id` int(11) NOT NULL,
  `chat` varchar(22) NOT NULL COMMENT 'цільова группа',
  `bot_admin` text NOT NULL COMMENT 'Який бот є адміном цього каналу',
  `date_reg` date NOT NULL COMMENT 'дата початку адмін діяльності бота',
  `delInOut` int(1) NOT NULL DEFAULT '1' COMMENT 'Чи видаляти повідомлення про вступ та видалення з чату?'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Налаштування каналів для ботів';

-- --------------------------------------------------------

--
-- Структура таблицы `tg_service`
--

CREATE TABLE `tg_service` (
  `id` int(11) NOT NULL,
  `bot` varchar(25) NOT NULL COMMENT 'Имя бота до якого прив''язана послуга',
  `from_id` varchar(15) NOT NULL COMMENT 'id з tg_bundle',
  `status` varchar(11) NOT NULL COMMENT 'active,pause,block,delete',
  `date_reg` datetime NOT NULL,
  `price` int(11) NOT NULL COMMENT 'Якщо прайс на послугу фіксований',
  `review` int(11) NOT NULL COMMENT 'Кількість відгуків',
  `rating` int(11) NOT NULL COMMENT 'Сумма всіх оцінок',
  `city_id` text NOT NULL COMMENT 'Перелік id з service_city в яких цей надавач послуг може їх надавати'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Додаткові данні надавача сервісу';

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `black_data`
--
ALTER TABLE `black_data`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `countries`
--
ALTER TABLE `countries`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `dialogue`
--
ALTER TABLE `dialogue`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `ip_log`
--
ALTER TABLE `ip_log`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `regions`
--
ALTER TABLE `regions`
  ADD UNIQUE KEY `id` (`id`);

--
-- Индексы таблицы `service_city`
--
ALTER TABLE `service_city`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_city_region` (`city`,`region`);

--
-- Индексы таблицы `service_order`
--
ALTER TABLE `service_order`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `sites`
--
ALTER TABLE `sites`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `tg_bots`
--
ALTER TABLE `tg_bots`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `tg_bots_dop`
--
ALTER TABLE `tg_bots_dop`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `tg_bundle`
--
ALTER TABLE `tg_bundle`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `tg_deeplink`
--
ALTER TABLE `tg_deeplink`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `tg_group_setup`
--
ALTER TABLE `tg_group_setup`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `tg_service`
--
ALTER TABLE `tg_service`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `black_data`
--
ALTER TABLE `black_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `countries`
--
ALTER TABLE `countries`
  MODIFY `id` int(13) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT для таблицы `dialogue`
--
ALTER TABLE `dialogue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `ip_log`
--
ALTER TABLE `ip_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT для таблицы `regions`
--
ALTER TABLE `regions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=582;

--
-- AUTO_INCREMENT для таблицы `service_city`
--
ALTER TABLE `service_city`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT для таблицы `service_order`
--
ALTER TABLE `service_order`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `sites`
--
ALTER TABLE `sites`
  MODIFY `id` tinyint(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT для таблицы `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT для таблицы `tg_bots`
--
ALTER TABLE `tg_bots`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `tg_bots_dop`
--
ALTER TABLE `tg_bots_dop`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `tg_bundle`
--
ALTER TABLE `tg_bundle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT для таблицы `tg_deeplink`
--
ALTER TABLE `tg_deeplink`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `tg_group_setup`
--
ALTER TABLE `tg_group_setup`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `tg_service`
--
ALTER TABLE `tg_service`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
