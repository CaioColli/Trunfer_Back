<?php

namespace validation;

use Respect\Validation\Validator as v;

class UserValidation
{
    public static function userCadaster()
    {
        return [
            'user_Name' => v::stringType()->notEmpty()->length(3, 50),
            'user_Email' => v::email()->notEmpty(),
            'user_Password' => v::notEmpty()->regex('/^(?=.*\d)(?=.*[a-zA-Z])(?=.*\W)[\d\w\W]{6,}$/')
        ];
    }

    public static function userLogin()
    {
        return [
            'user_Email' => v::email()->notEmpty(),
            'user_Password' => v::notEmpty()->regex('/^(?=.*\d)(?=.*[a-zA-Z])(?=.*\W)[\d\w\W]{6,}$/')
        ];
    }

    public static function userEdit()
    {
        return [
            'user_Name' => v::optional(v::stringType()->notEmpty()->length(3, 50)),
            'user_Email' => v::optional(v::email()->notEmpty()),
            'user_Password' => v::optional(v::notEmpty()),
            'user_New_Password' => v::optional(v::notEmpty()->regex('/^(?=.*\d)(?=.*[a-zA-Z])(?=.*\W)[\d\w\W]{6,}$/'))
        ];
    }
}
