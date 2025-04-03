<?php

namespace response;

class Response
{


    public static function Return400($response, $data)
    {
        $response = $response->withStatus(400);
        $response->getBody()->write(json_encode([
            'status' => 400,
            'message' => 'Bad Request',
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

    public static function Return409($response, $data)
    {
        $response = $response->withStatus(409);
        $response->getBody()->write(json_encode([
            'status' => 409,
            'message' => 'Conflict',
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
            'message' => 'Unprocessable Entity',
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

    public static function Return201($response, $data)
    {
        $response = $response->withStatus(201);
        $response->getBody()->write(json_encode([
            'status' => 201,
            'message' => 'Created',
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
