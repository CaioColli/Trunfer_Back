<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use controller\lobby\MatchServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Http\HttpServer;

$server = IoServer::factory(
    new HttpServer( // Aceita conexões HTTP
        new WsServer( // Lida com WebSockets
            new MatchServer()
        )
    ),
    8080
);

$server->run();
