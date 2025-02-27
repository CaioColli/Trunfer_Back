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

    public static function Error401($response, $errors)
    {
        $response = $response->withStatus(401);
        $response->getBody()->write(json_encode([
            'status' => 401,
            'message' => 'Requisição não autorizada.',
            'errors' => $errors,
        ]));
        return $response;
    }

    public static function Error404($response, $errors)
    {
        $response = $response->withStatus(404);
        $response->getBody()->write(json_encode([
            'status' => 404,
            'message' => 'Requisição não encontrada.',
            'errors' => $errors,
        ]));
        return $response;
    }

    public static function Error422($response, $errors)
    {
        $response = $response->withStatus(422);
        $response->getBody()->write(json_encode([
            'status' => 422,
            'message' => 'Requisição não processada.',
            'errors' => $errors,
        ]));
        return $response;
    }
}
