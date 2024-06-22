<?php

class tool
    {
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
        //core::ec($starttime);
        $result = core::$db->query("SELECT `id` FROM `dialogue` WHERE `$messendger`='$contact' and `essence`='$essence' and `date` >= '" . $starttime . "'");
        if ($result->num_rows > 0) {
            return 'stop';
            }
        return 'needsend';
        }

    // РОбить з одномірного масиву рядок
    // Допомогає з массиву з помилкою сформувати рядок який можна відправити наприклад в телегу адміну
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

    } // class tool

