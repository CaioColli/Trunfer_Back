<?php

namespace route;

use Slim\App;
use Slim\Routing\RouteCollectorProxy;

class UserRoutes
{
    public function __construct(App $app) 
    {
        $app->group('/user', function (RouteCollectorProxy $group) {
            $group->group('/cadaster', function (RouteCollectorProxy $group) {
                $group->post('', \controller\user\UserController::class . ':Create');
            });

            $group->group('/login', function (RouteCollectorProxy $group) {
                $group->post('', \Controller\user\UserController::class . ':Login');
            });

            $group->group('/edit', function (RouteCollectorProxy $group) {
                $group->patch('', \Controller\user\UserController::class . ':Edit');
            });

            $group->group('/delete', function (RouteCollectorProxy $group) {
                $group->delete('', \Controller\user\UserController::class . ':Delete');
            });
        });
    }
}
