<?php

namespace route;

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

                $group->patch('/{deck_ID}', \controller\adm\AdmController::class . ':EditDeck');

                $group->delete('/{deck_ID}', \controller\adm\AdmController::class . ':DeleteDeck');

                $group->get('/{deck_ID}', \controller\adm\AdmController::class . ':GetDeck');
            });
        })
            ->add(RolesOfMiddleware::class);
    }
}
