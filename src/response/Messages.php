<?php

namespace response;

use response\Responses;

class Messages
{
    public static function Error400($response)
    {
        $response = $response->withStatus(400);
        $response->getBody()->write(json_encode(Responses::ERR_BAD_REQUEST));
        return $response;
    }
}
