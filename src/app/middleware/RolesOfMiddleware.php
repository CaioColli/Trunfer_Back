<?php 

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

use App\Session\Session as Session;
use App\Response\Responses;

class RolesOfMiddleware
{
    const PermissionRules = [
        "user" => [
            '/^\?user\/edit/' => ['PATCH'],
            '/^\?user\/delete/' => ['DELETE'],
        ],
        "admin" => [
            '/^\?adm/' => ['PATCH'],
        ]
    ];

    public function __invoke(Request $request, RequestHandler $handler, $response): Response
    {
        $allowed = false;

        // self:: Serve para acessar uma constante
        foreach (self::PermissionRules[Session::getUserType()] as $rule => $method) {
            if (
                preg_match($rule, $request->getUri()->getPath()) &&
                in_array($request->getMethod(), $method)
            ) {
                $allowed = true;
            }
        }

        if (!$allowed) {
            $response = $response->withStatus(400);
            $response->getBody()->write(json_encode(Responses::ERR_BAD_REQUEST));
            return $response;
        }

        // handle() é um método que lida com requisições
        $response = $handler->handle($request);
        
        return $response;
    }
}