<?php

namespace route;

use App\Middleware\AuthTokenMiddleware;
use App\Middleware\RolesOfMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

class AdmRoutes
{
    public function __construct(App $app)
    {
        $app->group('/adm', function (RouteCollectorProxy $group) {
            $group->group('/decks', function (RouteCollectorProxy $group) {

                $group->post('', \controller\adm\DeckController::class . ':NewDeck');

                $group->patch('/{deck_ID}', \controller\adm\DeckController::class . ':EditDeck');

                $group->delete('/{deck_ID}', \controller\adm\DeckController::class . ':DeleteDeck');

                $group->get('', \controller\adm\DeckController::class . ':GetDecks');

                $group->get('/{deck_ID}', \controller\adm\DeckController::class . ':GetDeck');

                $group->post('/{deck_ID}/cards', \controller\adm\CardController::class . ':NewCard');

                $group->patch('/{deck_ID}/cards/{card_ID}', \controller\adm\CardController::class . ':EditCard');

                $group->delete('/{deck_ID}/cards/{card_ID}', \controller\adm\CardController::class . ':DeleteCard');

                $group->get('/{deck_ID}/cards', \controller\adm\CardController::class . ':GetCards');

                $group->get('/{deck_ID}/cards/{card_ID}', \controller\adm\CardController::class . ':GetCard');
            });
        })
            ->add(RolesOfMiddleware::class)
            ->add(AuthTokenMiddleware::class);
    }
}
