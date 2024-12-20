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
                $group->post('', \controller\adm\AdmController::class . ':CreateDeck');

                $group->delete('', \controller\adm\AdmController::class . ':DeleteDeck');
            });
        })
            ->add(RolesOfMiddleware::class);
    }
}
