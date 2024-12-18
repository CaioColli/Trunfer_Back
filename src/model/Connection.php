<?php

namespace App\Model;

class Connection
{
    private static $instance = null;

    public static function getConnection()
    {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../model/DatabaseSettings.php';
            $db = $config['db'];

            try {
                self::$instance = new \PDO(
                    "mysql:host={$db['host']};port={$db['port']};dbname={$db['dbname']}",
                    $db['user'],
                    $db['pw']
                );
                self::$instance->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                self::$instance->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            } catch (\Exception $err) {
                die('Erro ao tentar se conectar ao banco de dados: ' . $err->getMessage());
            }
        }

        return self::$instance;
    }
}
