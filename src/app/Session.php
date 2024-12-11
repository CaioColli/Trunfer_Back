<?php

namespace App\Session;

class Session
{
    private $userType;


    public function __construct()
    {
        $this->userType = null;
    }

    public static function getUserType()
    {
        return self::$userType;
    }

    public static function setUserType($userType)
    {
        self::$userType = $userType;
    }
}
