<?php

namespace App;

use Slim\Factory\AppFactory;
use route\UserRoutes;
use route\AdmRoutes;
use route\LobbyRoutes;

class App
{
    /**
     * Inicia a execução do framework, declara os middlewares, handlers, tipo de acesso e chama o registro de rotas.
     */
    public static function run()
    {

        $app = AppFactory::create();
        // Habilita middleware de erro embutido, serve para lidar com erros e ajuda a depurar o aplicativo
        $app->addErrorMiddleware(true, true, true);

        $userRoutes = new UserRoutes($app);
        $admRoutes = new AdmRoutes($app);
        $lobbyRoutes = new LobbyRoutes($app);

        $app->run();
    }
}
