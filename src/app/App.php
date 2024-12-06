<?php

namespace App;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

class App
{
    /**
     * Inicia a execução do framework, declara os middlewares, handlers, tipo de acesso e chama o registro de rotas.
     */
    public static function run()
    {

        $app = AppFactory::create();

        // // Rota para a raiz do projeto ("/")
        // $app->get('/', function (Request $request, Response $response) {
        //     $response->getBody()->write("Olá mundo!");
        //     return $response;
        // });

        // $app->get('/hello/{name}', function (Request $request, Response $response, array $args) {
        //     $name = $args['name'];
        //     $response->getBody()->write("Hello, $name");
        //     return $response;
        // });
        
        $app->run();
    }
}
