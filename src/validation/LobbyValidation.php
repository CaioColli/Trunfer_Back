<?php 

namespace validation;

use Respect\Validation\Validator as v;

class LobbyValidation
{
    public static function LobbyCreate()
    {
        return [
            'lobby_Name' => v::stringType()->notEmpty()->length(3, 50)
        ];
    }

    public static function EditLobby() 
    {
        return [
            'lobby_Name' => v::optional(v::stringType()->length(3, 50)),
            'lobby_Available' => v::optional(v::boolType()),
            'deck_ID' => v::optional(v::intVal()->min(0))
        ];
    }
}