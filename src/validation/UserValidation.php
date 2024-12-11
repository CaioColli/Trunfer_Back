<?php

namespace App\Validation;

use Respect\Validation\Validator as v;

class UserValidation
{
    public static function userCadaster()
    {
        return [
            'user_Name' => v::stringType()->notEmpty()->length(3, 50),
            'user_Email' => v::email()->notEmpty(),
            'user_Password' => v::stringType()->notEmpty()->min(8)
        ];
    }

    public static function userLogin()
    {
        return [
            'user_Email' => v::email()->notEmpty(),
            'user_Password' => v::stringType()->notEmpty()->min(8)
        ];
    }
}
