<?php

namespace App;

use App\Route\UserRoutes;

// use Psr\Http\Message\ResponseInterface as Response;
// use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

class App
{
    /**
     * Inicia a execução do framework, declara os middlewares, handlers, tipo de acesso e chama o registro de rotas.
     */
    public static function run()
    {

        $app = AppFactory::create();
        $app->addErrorMiddleware(true, true, true);

        $userRoutes = new UserRoutes($app);
        
        $app->run();
    }
}
