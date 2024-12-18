<?php

namespace App\Response;

class Messages
{
    public static function Error400($response, $err)
    {
        $response = $response->withStatus(400);
        $response->getBody()->write(json_encode(['error' => $err->getMessage()]));
        return $response;
    }
}
