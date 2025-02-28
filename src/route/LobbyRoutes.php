<?php

namespace route;

use App\Middleware\AuthTokenMiddleware;
use App\Middleware\RolesOfMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

class LobbyRoutes
{
    public function __construct(App $app)
    {
        $app->group('/lobby', function (RouteCollectorProxy $group) {
            $group->post('', \controller\lobby\LobbyController::class . ':CreateLobby');
            $group->post('/{lobby_ID}', \controller\lobby\LobbyController::class . ':JoinLobby');

            $group->get('', \controller\lobby\LobbyController::class . ':GetLobbys');
            $group->get('/{lobby_ID}', \controller\lobby\LobbyController::class . ':GetLobby');

            $group->patch('/{lobby_ID}', \controller\lobby\LobbyController::class . ':EditLobby');

            $group->delete('/{lobby_ID}/player/{user_ID}', \controller\lobby\LobbyController::class . ':RemovePlayer');
            $group->delete('/{lobby_ID}', \controller\lobby\LobbyController::class . ':DeleteLobby');

            $group->post('/{lobby_ID}/start_lobby', \controller\lobby\LobbyController::class . ':StartLobby');

            $group->post('/{lobby_ID}/distribute_cards', \controller\lobby\MatchController::class . ':DistributeCards');
            $group->get('/{lobby_ID}/get_card', \controller\lobby\MatchController::class . ':GetAtualDeckCard');
            $group->post('/{lobby_ID}/first_play', \controller\lobby\MatchController::class . ':FirstPlay');
            $group->post('/{lobby_ID}/play_turn', \controller\lobby\MatchController::class . ':PlayTurn');

        })
            ->add(RolesOfMiddleware::class)
            ->add(AuthTokenMiddleware::class);
    }
}
