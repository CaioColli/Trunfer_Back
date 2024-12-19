<?php

namespace App\Route;

use App\Middleware\RolesOfMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

class AdmRoutes
{
    public function __construct(App $app)
    {
        // Define o grupo /adm
        $app->group('/adm', function (RouteCollectorProxy $group) {
            $group->group('/decks', function (RouteCollectorProxy $group) {
                $group->post('', \App\Controller\AdmController::class . ':CreateDeck');
            });
        })
            ->add(RolesOfMiddleware::class);
    }
}
