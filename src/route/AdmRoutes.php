<?php

namespace App\AdmRoutes;

use Slim\App;
use Slim\Routing\RouteCollectorProxy;

class AdmRoutes
{
    public function __construct(App $app)
    {
        $app->group('/adm', function (RouteCollectorProxy $group) {
            $group->group('/decks', function (RouteCollectorProxy $group) {
                $group->post('', \App\Controller\AdmController::class . ':Create');
            });
        });
    }
}
