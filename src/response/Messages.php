<?php

namespace response;

class Messages
{
    public static function Error400($response, $errors)
    {
        $response = $response->withStatus(400);
        $response->getBody()->write(json_encode([
            'status' => 400,
            'message' => 'Requisição inválida.',
            'errors' => $errors,
        ]));
        return $response;
    }
}
