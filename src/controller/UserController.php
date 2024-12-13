<?php

namespace App\Controller;

use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;
use Respect\Validation\Validator as v;

use App\Response\Responses;

class UserController
{
    public function Create(PsrRequest $request, PsrResponse $response)
    {
        $data = $request->getParsedBody() ?? [];

        var_dump($data);

        $rules = \App\Validation\UserValidation::userCadaster();

        $errors = [];

        if (!isset($data['user_Name']) || !$rules['user_Name']->validate($data['user_Name'])) {
            $errors[] = 'Nome inválido ou ausente.';
        }

        if (!isset($data['user_Email']) || !$rules['user_Email']->validate($data['user_Email'])) {
            $errors[] = 'Email inválido ou ausente.';
        }

        if (!isset($data['user_Password']) || !$rules['user_Password']->validate($data['user_Password'])) {
            $errors[] = 'Senha inválida ou ausente.';
        }

        if (!empty($errors)) {
            $response = $response->withStatus(400);
            $response->getBody()->write(json_encode(['error' => $errors, Responses::ERR_BAD_REQUEST]));
            return $response;
        }

        $response = $response->withStatus(200);
        $response->getBody()->write(json_encode(Responses::CREATED));
        return $response;
    }

    public function Login(PsrRequest $request, PsrResponse $response)
    {

        $data = $request->getParsedBody();

        var_dump($data);

        $rules = \App\Validation\UserValidation::userLogin();

        $errors = [];

        if (!isset($data['user_Email']) || !$rules['user_Email']->validate($data['user_Email'])) {
            $errors[] = 'Nome inválido ou ausente.';
        }

        if (!isset($data['user_Password']) || !$rules['user_Password']->validate($data['user_Password'])) {
            $errors[] = 'Senha inválida ou ausente.';
        }

        if (!empty($errors)) {
            $response = $response->withStatus(400);
            $response->getBody()->write(json_encode(['error' => $errors, Responses::ERR_BAD_REQUEST]));
            return $response;
        }

        $response = $response->withStatus(200);
        $response->getBody()->write(json_encode(Responses::CREATED));
        return $response;
    }
}
