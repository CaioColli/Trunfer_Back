<?php

namespace App\Route;

use Slim\App;
use Slim\Routing\RouteCollectorProxy;

class UserRoutes
{
    public function __construct(App $app) 
    {
        $app->group('/user', function (RouteCollectorProxy $group) {
            $group->group('/cadaster', function (RouteCollectorProxy $group) {
                $group->post('', \App\Controller\UserController::class . ':Create');
            });

            $group->group('/login', function (RouteCollectorProxy $group) {
                $group->post('', \App\Controller\UserController::class . ':Login');
            });

            $group->group('/edit', function (RouteCollectorProxy $group) {
                $group->patch('', \App\Controller\UserController::class . ':Edit');
            });

            $group->group('/delete', function (RouteCollectorProxy $group) {
                $group->delete('', \App\Controller\UserController::class . ':Delete');
            });
        });
    }
}
