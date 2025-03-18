<?php

namespace response;

class Messages
{
    // APAGAR
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


    // APAGAR
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

    public static function Return401($response, $data)
    {
        $response = $response->withStatus(401);
        $response->getBody()->write(json_encode([
            'status' => 401,
            'message' => 'Unauthorized',
            'data' => $data,
        ]));
        return $response;
    }

    public static function Return403($response, $data)
    {
        $response = $response->withStatus(403);
        $response->getBody()->write(json_encode([
            'status' => 403,
            'message' => 'Forbidden',
            'data' => $data,
        ]));
        return $response;
    }

    // APAGAR
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

    public static function Return404($response, $data)
    {
        $response = $response->withStatus(404);
        $response->getBody()->write(json_encode([
            'status' => 404,
            'message' => 'Not Found',
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

    public static function Return200($response, $data)
    {
        $response = $response->withStatus(200);
        $response->getBody()->write(json_encode([
            'status' => 200,
            'message' => 'Ok',
            'data' => $data,
        ]));
        return $response;
    }

    // SSE

    public static function ReturnSSE($status, $message, $data)
    {
        echo "data: " . json_encode([
            'status' => $status,
            'message' => $message,
            'data' => $data
        ]) . "\n\n";
    }
}
