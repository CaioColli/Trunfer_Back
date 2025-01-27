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
                
                // $group->post('', \controller\adm\DeckController::class . ':CreateDeck');

                $group->post('', \controller\adm\DeckController::class . ':NewDeck') -> add(AuthTokenMiddleware::class);

                $group->patch('/{deck_ID}', \controller\adm\DeckController::class . ':EditDeck') -> add(AuthTokenMiddleware::class);

                $group->delete('/{deck_ID}', \controller\adm\DeckController::class . ':DeleteDeck') -> add(AuthTokenMiddleware::class);
                
                $group->get('', \controller\adm\DeckController::class . ':GetDecks') -> add(AuthTokenMiddleware::class);

                $group->get('/{deck_ID}', \controller\adm\DeckController::class . ':GetDeck') -> add(AuthTokenMiddleware::class);

                $group->post('/{deck_ID}/cards', \controller\adm\LetterController::class . ':NewCard') -> add(AuthTokenMiddleware::class);

                $group->patch('/{deck_ID}/cards/{card_ID}', \controller\adm\LetterController::class . ':EditCard') -> add(AuthTokenMiddleware::class);
                
                $group->delete('/{deck_ID}/cards/{card_ID}', \controller\adm\LetterController::class . ':DeleteCard') -> add(AuthTokenMiddleware::class);

                $group->get('/{deck_ID}/cards', \controller\adm\LetterController::class . ':GetCards') -> add(AuthTokenMiddleware::class);

                $group->get('/{deck_ID}/cards/{card_ID}', \controller\adm\LetterController::class . ':GetCard') -> add(AuthTokenMiddleware::class);
            });
        })
            ->add(RolesOfMiddleware::class);
    }
}
