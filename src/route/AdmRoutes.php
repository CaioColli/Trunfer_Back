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

                $group->get('', \controller\adm\AdmController::class . ':GetDecks');

                $group->post('/{deck_ID}/letter', \controller\adm\AdmController::class . ':CreateLetter');

                $group->patch('/{deck_ID}/letter/{letter_ID}', \controller\adm\AdmController::class . ':EditLetter');
            });
        })
            ->add(RolesOfMiddleware::class);
    }
}
