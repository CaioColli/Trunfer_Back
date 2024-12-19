<?php

namespace session;

class Session
{
     // Método para obter o tipo de usuário
    public static function getUserType()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return $_SESSION['user_type'] ?? null;
    }

    // Método para definir o tipo de usuário
    public static function setUserType($userType)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['user_type'] = $userType;
    }
}
