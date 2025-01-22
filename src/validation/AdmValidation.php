<?php

namespace validation;

use Respect\Validation\Validator as v;

class AdmValidation
{
    public static function DeckCreate()
    {
        return [
            'deck_Name' => v::stringType()->notEmpty()->length(3, 50),
            'deck_Image' => v::stringType()->notEmpty()->length(1, null),
            'attributes' => v::arrayType()->length(5, 5)->each(
                v::stringType()->notEmpty()->length(3, null)
            )
        ];
    }

    public static function DeckEdit()
    {
        return [
            'deck_Image' => v::optional(v::stringType()->notEmpty()->length(2, null)),
            'deck_Is_Available' => v::optional(v::boolType())
        ];
    }

    public static function CardCreate()
    {
        return [
            'card_Name' => v::stringType()->notEmpty()->length(3, 50),
            'card_Image' => v::stringType()->notEmpty()->length(2, null),
            'attributes' => v::arrayType()->length(5, 5)->each(
                v::keySet(
                    v::key('attribute_ID', v::intType()->positive()),
                    v::key('attribute_Value', v::intType()->positive())
                )
            )
        ];
    }

    public static function CardEdit()
    {
        return [
            'card_Name' => v::optional(v::stringType()->notEmpty()->length(3, 50)),
            'card_Image' => v::optional(v::stringType()->notEmpty()->length(2, null)),
            'attributes' => v::optional(v::arrayType()->each(
                v::keySet(
                    v::key('attribute_ID', v::intType()->notEmpty()->positive()),
                    v::key('attribute_Value', v::intType()->notEmpty()->positive())
                )
            )),
        ];
    }
}
