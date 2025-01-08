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
            $group->get('', \controller\lobby\LobbyController::class . ':GetLobbys');

            $group->post('', \controller\lobby\LobbyController::class . ':CreateLobby');

            $group->post('/{lobby_ID}/join', \controller\lobby\LobbyController::class . ':JoinLobby');

            $group->delete('/{lobby_ID}/player', \controller\lobby\LobbyController::class . ':RemovePlayer');

            $group->delete('/{lobby_ID}', \controller\lobby\LobbyController::class . ':DeleteLobby');

            $group->patch('/{lobby_ID}', \controller\lobby\LobbyController::class . ':UpdateLobby');

            $group->post('/{lobby_ID}/start', \controller\lobby\LobbyController::class . ':StartLobby');

            $group->post('/{lobby_ID}/start_match', \controller\lobby\LobbyController::class . ':StartMatch');
        })
            ->add(RolesOfMiddleware::class);
    }
}
