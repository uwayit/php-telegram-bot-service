<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/load.php";

// Перевірка доступу до бази даних
if (!core::$db) {
    exit("Error connect to base");
    }

// В перспективі, через цей або якийсь інший візуальний інтерфейс 
// можна буде додавати в систему нові боти або редагувати якісь налаштування
// А тому для створення авторизації треба змінити ці константи
if (load::SERVICE_KEY == '123' or load::SERVICE_KEY_SOLD == 'QQQQQQQ') {
    exit('First change to unique const SERVICE_KEY in load.php');
    }

// Функція для перевірки наявності таблиць
function tablesExist($db)
    {
    $requiredTables = [
        'countries',
        'sites',
        'dialogue',
        'ip_log',
        'regions',
        'service_city',
        'black_data',
        'tasks',
        'tg_bots',
        'tg_bots_dop',
        'tg_bundle',
        'tg_deeplink',
        'tg_group_setup',
        'tg_service'
    ];
    $tablesInDb = $db->query("SHOW TABLES");
    $tablesInDbArray = [];

    while ($row = $tablesInDb->fetch_array()) {
        $tablesInDbArray[] = $row[0];
        }

    foreach ($requiredTables as $table) {
        if (!in_array($table, $tablesInDbArray)) {
            return false;
            }
        }
    return true;
    }

// Функція для перевірки наявності хоча б однієї таблиці
function anyTableExists($db)
    {
    $tablesInDb = $db->query("SHOW TABLES");
    return $tablesInDb->num_rows > 0;
    }
echo '<style>label,button{display:block;}
label,input,button{font-size: 110%;}
label,input{padding:5px;}
input,button,div{margin:0 0 15px 0;width:310px;}
button,div{padding: 20px 0}
body{margin: auto;width: 300px;text-align: center;position: relative;top: 30px;}</style>';
// Логіка установки
if (isset($_GET['install']) && $_GET['install'] == 'true' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Очищення бази даних
    $tablesInDbArray = core::$db->query("SHOW TABLES");
    while ($row = $tablesInDbArray->fetch_array()) {
        core::$db->query("DROP TABLE IF EXISTS {$row[0]}");
        }

    // Встановлення бази з файлу db_bot.sql
    $sql = file_get_contents('db_bot.sql');
    if (core::$db->multi_query($sql)) {
        do {
            if ($result = core::$db->store_result()) {
                $result->free();
                }
            } while (core::$db->next_result());
        }

    echo '<form id="installForm" method="post" action="install.php">
        <label for="admins">Admins:</label><input placeholder="" type="text" name="admins" id="admins" required><br>
        <label for="site">Site:</label><input placeholder="" type="text" name="site" id="site" required><br>
        <label for="bot">Bot:</label><input placeholder="" type="text" name="bot" id="bot" required><br>
        <label for="bot_token">Bot Token:</label><input placeholder="Token" type="text" name="bot_token" id="bot_token" required><br>
        <label for="country">Country:</label><input placeholder="Country" type="text" name="country" id="country" required><br>
        <label for="lg">Language:</label><input placeholder="Language" type="text" name="lg" id="lg" required><br>
        <label for="email">Email:</label><input placeholder="email" type="email" name="email" id="email" required><br>
        <button type="submit">Submit</button>
        <div>Зверніть увагу на те, що форма майже не валідується!</div>
    </form>';

    echo '<script>
        document.getElementById("installForm").addEventListener("submit", function(event) {
            let fields = ["admins", "site", "bot", "bot_token", "country", "lg", "email"];
            let valid = true;

            fields.forEach(function(field) {
                let input = document.getElementById(field);
                if (input.value.length < 2) {
                    input.style.color = "red";
                    valid = false;
                } else {
                    input.style.color = "";
                }
            });

            if (!valid) {
                event.preventDefault();
                alert("Please fill all the fields correctly!");
            }
        });
    </script>';
    } else if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Перевірка наявності таблиць
    if (tablesExist(core::$db)) {
        echo "<div><h2>No installation needed!</h2></div>";
        if (empty($_GET['key']) or $_GET['key'] != load::SERVICE_KEY) {
            echo "<div>Якщо після інсталяції бази, Ви випадково НЕ налаштували свого першого бота, тоді ввійдіть на цю сторінку з ключем ?key=load::SERVICE_KEY<br><br></div>
            <div>Якщо Ви хочете додатково тонко налаштувати або виправити помилки налаштування раніше доданого бота або хочете додати ще одного, то найзручніше це робити через web інтерфейс phpMyAdmin. В таблицях tg_bot та tg_bots_dop - значення кожного стовбця гарно описані. Також, якщо Ви починаєте використовувати новий або інший домен, треба додати новий рядок в таблицю sites.</div>
            <div>Якщо після інсталяції бази, Ви випадково НЕ налаштували (Не додали) свого першого бота, або якщо Ви бажаєте все перевстановити з початку, то видалить з бази хоча б одну таблицю, після чого цей інсталяційний файл дозволить вам розпочати процес встановлення з початку.<br><br></div>
            ";
            }

        exit('no installation needed');
        } elseif (anyTableExists(core::$db)) {
        echo '<div>Цілісність бази порушена. Перевстановити?</div><button onclick="location.href=\'install.php?install=true\'">yes</button>';
        } else {
        echo '<div>Розпочати встановлення?</div><button onclick="location.href=\'install.php?install=true\'">yes</button>';
        }
    }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admins = $_POST['admins'];
    $site = mb_strtolower($_POST['site']);
    $bot = $_POST['bot'];
    $bot_token = $_POST['bot_token'];
    $country = mb_strtolower($_POST['country']);
    $lg = mb_strtolower($_POST['lg']);
    $email = mb_strtolower($_POST['email']);

    core::$db->query("INSERT INTO sites (url, bot, bot_token, country, lg, info_email) VALUES ('$site', '$bot', '$bot_token', '$country', '$lg', '$email')");
    core::$db->query("INSERT INTO tg_bots (admins, site, name, token, country, info_email,reg) VALUES ('$admins', '$site', '$bot', '$bot_token', '$country', '$email',NOW())");
    core::$db->query("INSERT INTO tg_bots_dop (name, manager_mail) VALUES ('$bot', '$email')");

    exit("Installation successful!<br><br><br><br>Тепер обов'язково видаліть файл install.php та sql файл бази данних що лежить поряд!");
    }