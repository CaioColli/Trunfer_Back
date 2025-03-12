<?php

namespace response;

class Messages
{
    public static function Error400($response, $data)
    {
        $response = $response->withStatus(400);
        $response->getBody()->write(json_encode([
            'status' => 400,
            'message' => 'Requisição inválida.',
            'data' => $data,
        ]));
        return $response;
    }

    public static function Return400($response, $data)
    {
        $response = $response->withStatus(400);
        $response->getBody()->write(json_encode([
            'status' => 400,
            'message' => 'Requisição inválida.',
            'data' => $data,
        ]));
        return $response;
    }

    public static function Error401($response, $data)
    {
        $response = $response->withStatus(401);
        $response->getBody()->write(json_encode([
            'status' => 401,
            'message' => 'Requisição não autorizada.',
            'data' => $data,
        ]));
        return $response;
    }

    public static function Error404($response, $data)
    {
        $response = $response->withStatus(404);
        $response->getBody()->write(json_encode([
            'status' => 404,
            'message' => 'Requisição não encontrada.',
            'data' => $data,
        ]));
        return $response;
    }

    public static function Return422($response, $data)
    {
        $response = $response->withStatus(422);
        $response->getBody()->write(json_encode([
            'status' => 422,
            'message' => 'Requisição não processada.',
            'data' => $data,
        ]));
        return $response;
    }

    public static function Return200($response, $status, $data )
    {
        $response = $response->withStatus($status);
        $response->getBody()->write(json_encode([
            'status' => $status,
            'message' => 'Ok',
            'data' => $data,
        ]));
        return $response;
    }
}
