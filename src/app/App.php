<?php

namespace App;

use Slim\Factory\AppFactory;
use App\Route\UserRoutes;
use App\AdmRoutes\AdmRoutes;

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

        // if (class_exists(AdmRoutes::class)) {
        //     echo "A classe App\\Session foi carregada corretamente.";
        //     die();
        // } else {
        //     echo "A classe App\\Session não foi encontrada.";
        //     die();
        // }

        $userRoutes = new UserRoutes($app);
        //$admRoutes = new AdmRoutes($app);

        $app->run();
    }
}
