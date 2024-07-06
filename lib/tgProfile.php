<?php
// Формуємо і виводимо профіль користувача по запиту
class tgProfile
    {

    public $bot = '';
    public $client = [];

    public function __construct($bot, $client)
        {
        $this->bot = $bot;
        $this->client = $client;
        }

    public function makeProfile($botinfodop, $service)
        {
        // Формуємо відповідь (виводимо профайл)
        $response = "";
        $response .= $this->getPhoneSection();
        $response .= $this->getLocationSection();
        $response .= $this->getRoleSection($botinfodop, $service);

        $this->addNavigationButton();

        $this->bot->reply($response);
        // Можна вийти тут або в hook
        exit('ok'); 
        }

    private function getPhoneSection()
        {
        if ($this->client['phone'] != '') {
            $phonev = $this->client['phone'];
            $edph = "змінити";
            } else {
            $edph = "вказати";
            $phonev = "_ЩЕ НЕ ПІДТВЕРДЖЕНО_";
            }

        $this->bot->insertButton([['text' => $edph . " номер телефону", 'callback_data' => 'rear_editphone']]);
        return "📱 Номер телефона: " . $phonev . "\r\n---------\r\n";
        }

    private function getLocationSection()
        {
        if ($this->client['region'] != '') {
            $regionv = "\r\n" . $this->client['region'];
            if ($this->client['city'] != '') {
                $regionv .= "\r\n" . $this->client['city'];
                }
            } else {
            $regionv = " _ЩЕ НЕ ВКАЗАНО_";
            }

        return "🏠 Місто знаходження:" . $regionv . "\r\n---------\r\n";
        }

    private function getRoleSection($botinfodop, $service)
        {
        $response = "";
        if ($this->client['role'] == '' || $this->client['role'] == 'candidate') {
            $response .= "Замовлень " . $botinfodop['service'] . ": 0";
            if ($this->client['role'] == 'candidate') {
                $this->bot->insertButton([['text' => "скасувати статус кандидата", 'callback_data' => 'rear_addserviceno']]);
                $response .= "\r\n---------\r\nРеєстрація у якості " . $botinfodop['service'] . ":\r\n_КАНДИДАТ_";
                }
            if ($this->client['role'] != '' and $this->client['role'] != 'candidate') {
                $this->bot->insertButton([['text' => "Зареєструватись у якості ". $botinfodop['service'], 'callback_data' => '/regservice']]);
                }
            $this->bot->insertButton([['text' => "змінити регіон знаходження", 'callback_data' => 'rear_editregion']]);

            } elseif ($this->client['role'] == 'service') {
            $response .= $this->getServiceSection($service);
            }

        return $response;
        }

    private function getServiceSection($service)
        {
        $response = "";
        if (!empty($service['city_id'])) {
            $howcity = substr_count($service['city_id'], ',') + 1;
            } else {
            $howcity = 0;
            }

        if ($howcity >= 1) {
            $response .= "*Міста в яких ви працюєте:*\r\n" . $this->getServiceCities($service['city_id']) . "\r\n---------\r\n";
            $kiko = 7 - $howcity;
            if ($kiko >= 1 && $kiko <= 6) {
                $response .= "Ви можете додати ще *" . $kiko . "* міст\r\n---------\r\n";
                $this->bot->insertButton([['text' => "додати місто надання послуг", 'callback_data' => 'rear_pluscityservice']]);
                }
            if ($howcity >= 7) {
                $response .= "Ви додали максимум міст!\r\n---------\r\n";
                }
            if ($howcity >= 1) {
                $this->bot->insertButton([['text' => "видалити місто надання послуг", 'callback_data' => 'citydel_list']]);
                }
            } else {
            $response .= "Ви ще не вказали жодного міста, яке можете обслуговувати!\r\n---------\r\n";
            }

        $response .= "Успішно опрацьовано замовлень: *0*";
        return $response;
        }

    private function getServiceCities($cityIds)
        {
        $query = "SELECT * FROM `service_city` WHERE id IN (" . $cityIds . ")";
        $result = core::$db->query($query);
        $lcs = "";
        while ($row = $result->fetch_assoc()) {
            $lcs .= $row['city'] . ", " . $row['region'] . "\r\n";
            }
        return $lcs;
        }

    private function addNavigationButton()
        {
        $this->bot->insertButton([["text" => "GO TO START", "callback_data" => "/start"]]);
        }
    }
