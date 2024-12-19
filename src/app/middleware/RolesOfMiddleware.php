<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

use App\Response\Responses;
use App\Session\Session;

class RolesOfMiddleware
{
    // Define as permissões para cada tipo de usuário
    private const PERMISSION_RULES = [
        "user" => [
            '/^\/user\/edit/' => ['PATCH'],
            '/^\/user\/delete/' => ['DELETE'],
        ],
        "admin" => [
            '/^\/adm\/decks/' => ['POST'],
        ]
    ];

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $userType = Session::getUserType(); // Obtem o tipo de usuário logado

        $uri = $request->getUri()->getPath();
        $method = $request->getMethod();

        if (!$this->isRequestAllowed($userType, $uri, $method)) {
            return $this->denyAccess($request);
        }

        // Continua com a requisição caso permitido
        return $handler->handle($request);
    }

    // Verifica se o tipo de usuário tem permissão para a URI
    private function isRequestAllowed(?string $userType, string $uri, string $method): bool
    {
        if (!isset(self::PERMISSION_RULES[$userType])) {
            return false;
        }

        foreach (self::PERMISSION_RULES[$userType] as $pattern => $methods) {
            if (preg_match($pattern, $uri) && in_array($method, $methods)) {
                return true;
            }
        }

        return false;
    }

    // Responde com erro de acesso negado
    private function denyAccess(Request $request)
    {
        $response = new \Slim\Psr7\Response();
        $response = $response->withStatus(403)
            ->withHeader('Content-Type', 'application/json');

        $response->getBody()->write(json_encode([
            'error' => 'Acesso negado. Você não tem acesso a essa rota.'
        ]));
        return $response;

        // $response = $handler->handle($request);
        // $response = $response->withStatus(400)
        //     ->withHeader('Content-Type', 'application/json');

        // $response->getBody()->write(json_encode(Responses::ERR_BAD_REQUEST));
        // return $response;
    }
}
