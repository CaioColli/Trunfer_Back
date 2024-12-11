<?php

namespace App\Controller;

use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;
use Respect\Validation\Validator as v;

use App\Response\Responses;

class UserController
{
    public function CreateAccount(PsrRequest $request, PsrResponse $response)
    {
        // Se o corpo da requisição estiver vazio retorna uma mensagem
        $data = $request->getParsedBody() ?? [
            "user" => null,
            "message" => "Não há dados inseridos"
        ];

        var_dump($data);

        $rules = \App\Validation\UserValidation::userCadaster();

        $validation = v::key("user_Name", $rules['user_Name'])
            ->key("user_Email", $rules['user_Email'])
            ->key("user_Password", $rules['user_Password']);

        if (!$validation->validate($data)) {
            $response = $response->withStatus(400);
            $response->getBody()->write(json_encode(Responses::ERR_BAD_REQUEST));
            return $response;
        } else {
            $response = $response->withStatus(200);
            $response->getBody()->write(json_encode(Responses::CREATED));
            return $response;
        }
    }
}
