<?php

namespace validation;

class ArrayLength
{
    private $length;

    public function __construct($length)
    {
        $this->length = $length;
    }

    public function validate($value)
    {
        return is_array($value) && count($value) === $this->length;
    }
}
