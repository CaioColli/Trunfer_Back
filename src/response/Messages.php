<?php

namespace App\Response;

use App\Response\Responses;

class Messages
{
    public static function Error400($response)
    {
        $response = $response->withStatus(400);
        $response->getBody()->write(json_encode(Responses::ERR_BAD_REQUEST));
        return $response;
    }
}
