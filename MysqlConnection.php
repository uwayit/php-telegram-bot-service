<?php

    class MysqlConnection {
        static $error = array(
            'errorno' => null,
            'errormsg' => null
        );

        private static $local;
        // Edit only this
        private static $username = "username"; 
        private static $pass = "pass"; // 
        private static $dbname = "db"; 
        private static $host = "localhost"; 
        private static $port = "3306";


      
        private static $charset = "utf8mb4"; // For storing emoticons (extended Unicode) in the database

        private function __construct() {
            self::$local = self::mysqlConnect();
        }

        static function getDb()
        {
            return self::$dbname;
        }

        static function getLocal() {
            if ( empty(self::$local) ) {
                new MysqlConnection();  //"local"
            }
            return self::$local;
        }

        private static function mysqlConnect() {
            @$mysqli = new mysqli(self::$host, self::$username, self::$pass, self::$dbname, self::$port);
            if (mysqli_connect_errno()) {
                self::$error = array(
                    'errorno' => mysqli_connect_errno(),
                    'errormsg' => mysqli_connect_error()
                );
                return false;
            }
            $mysqli->set_charset(self::$charset);
            return $mysqli;
        }
    }
