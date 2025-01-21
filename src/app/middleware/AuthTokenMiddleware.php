<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

use model\user\UserModel;
use response\Messages;

class AuthTokenMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler)
    {
        $token = $request->getHeader('Authorization')[0] ?? null;

        if (!$token) {
            return $this->DenyAcess('Token ausente');
            return Messages::Error401($response, ['Lobby nao encontrado ou ID passado incorreto.']);
        }

        try {
            $user = UserModel::ValidateToken($token);

            // Adiciona os dados do usuário autenticado na requisição
            $request = $request->withAttribute('user', $user);
            

            return $handler->handle($request);
        } catch (\Exception $err) {
            return $this->DenyAcess($err->getMessage());
        }
    }

    public function DenyAcess($message)
    {
        $response = new \Slim\Psr7\Response();
        $response = $response->withStatus(401);

        $response->getBody()->write(json_encode([
            'status' => 401,
            'message' => 'Requisição não autorizada.',
            'errors' => $message,
        ]));

        return $response;
    }
}
