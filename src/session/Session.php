<?php

namespace App\Session;

class Session
{
    private static $userType = null;

    // Método para obter o tipo de usuário
    public static function getUserType()
    {
        return self::$userType;
    }

    // Método para definir o tipo de usuário
    public static function setUserType($userType)
    {
        self::$userType = $userType;
    }
}
