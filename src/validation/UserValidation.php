<?php

use Respect\Validation\Validator as v;

class UserValidation
{
    public static function rules()
    {
        return [
            'name' => v::stringType()->notEmpty()->length(3, 50),
            'email' => v::email()->notEmpty(),
            'password' => v::stringType()->notEmpty()->min(8)
        ];
    }
}
