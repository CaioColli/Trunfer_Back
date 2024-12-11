<?php

namespace App\Route;

// use Psr\Http\Message\ResponseInterface as Response;
// use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
// use Validation\UserValidation;

class UserRoutes
{
    public function __construct(App $app) 
    {
        $app->group('/conta', function (RouteCollectorProxy $group) {
            $group->group('/cadastro', function (RouteCollectorProxy $group) {
                $group->post('', \App\Controller\UserController::class . ':CreateAccount');
            });
        });
    }
}
