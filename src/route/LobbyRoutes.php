<?php

namespace route;

use App\Middleware\RolesOfMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

class LobbyRoutes
{
    public function __construct(App $app)
    {
        $app->group('/lobby', function (RouteCollectorProxy $group) {
            $group->post('', \controller\lobby\LobbyController::class . ':CreateLobby');

            $group->delete('/{lobby_ID}', \controller\lobby\LobbyController::class . ':RemovePlayer');
        })
            ->add(RolesOfMiddleware::class);
    }
}
