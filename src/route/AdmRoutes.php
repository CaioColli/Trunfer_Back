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
                
                $group->post('', \controller\adm\DeckController::class . ':CreateDeck');

                $group->patch('/{deck_ID}', \controller\adm\DeckController::class . ':EditDeck');

                $group->delete('/{deck_ID}', \controller\adm\DeckController::class . ':DeleteDeck');
                
                $group->get('', \controller\adm\DeckController::class . ':GetDecks');

                $group->get('/{deck_ID}', \controller\adm\DeckController::class . ':GetDeck');

                $group->post('/{deck_ID}/letter', \controller\adm\LetterController::class . ':CreateLetter');

                $group->patch('/{deck_ID}/letter/{letter_ID}', \controller\adm\LetterController::class . ':EditLetter');
                
                $group->delete('/{deck_ID}/letter/{letter_ID}', \controller\adm\LetterController::class . ':DeleteLetter');

                $group->get('/{deck_ID}/letter', \controller\adm\LetterController::class . ':GetLetters');

                $group->get('/{deck_ID}/letter/{letter_ID}', \controller\adm\LetterController::class . ':GetLetter');
            });
        })
            ->add(RolesOfMiddleware::class);
    }
}
