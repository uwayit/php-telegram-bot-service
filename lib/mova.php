<?php

// Бібліотека з усім щостосується перекладу сайту

class mova {

    // Функція, що допомагає вибрати мову перекладу сайту із закладених
    // Надто просто, але не страшно поки мов мало
    // В першу чергу намагаємось взяти мову партнеру
    // Якщо її немає, то мову з кукісів
    // Якщо її немає, то по ip
    // І у випадку форсмажорів різних беремо мову сайту
    static function alang($partnerlg,$CountryByIP,$cookielg,$sitelg = false) {

        if(!empty($partnerlg)){
            $aco = $partnerlg;
        } else if(!empty($cookielg)){
            $aco = $cookielg;
        } else if(!empty($_COOKIE['lang'])){
            $aco = $_COOKIE['lang'];
        } else if(!empty($CountryByIP)){
            $aco = $CountryByIP;
        } else {
            $aco = $sitelg;
        }
        // У перспективі дефолтним при форсмажорах повинен бути en звичайно ж
        if(empty($aco) or $aco == 'ua' or $aco == 'uk'){
            return 'uk'; // ukraine - вимушений використовувати саме uk, а не ua
        }
        if($aco == 'ru' or $aco == 'ge' or $aco == 'by' or $aco == 'kz'){
            return 'ru';
        }
        if($aco == 'en' or $aco == 'gb'){
            return 'en';
        }
        return $sitelg;

    }

    // Спосіб обрати один з варіантів перекладу
    static function lg($lg,$ua,$ru = false,$en = false) {
        // вважаємо, що Українська мова основна і не порожня за жодних обставин
        if($lg == 'uk' or $lg == 'ua'){
            return $ua;
        }

        if($lg == 'ru' and !empty($ru)){
            return $ru;
        }
        if($lg == 'en' and !empty($en)){
            return $en;
        }
        // У цьому випадку, у будь-якій незрозумілій ситуації
        // видаємо $ua переклад
        return $ua;
    }


} // class lg

