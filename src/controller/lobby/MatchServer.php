<?php

namespace controller\lobby;

use Exception;
use model\lobby\LobbyModel;
use model\lobby\MatchModel;
use model\user\UserModel;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class MatchServer implements MessageComponentInterface
{
    protected $clients;

    public function __construct()
    {
        // Iniciar o objetos que deve armazenar os clientes conectados
        $this->clients = new \SplObjectStorage();
    }

    private function isValidToken($token)
    {
        try {
            return UserModel::ValidateToken($token);
        } catch (Exception $err) {
            return null;
        }
    }

    // Abrir conexão para o novo cliente
    public function onOpen(ConnectionInterface $conn)
    {
        // Validação de token
        $headers = $conn->httpRequest->getHeaders();
        $token = $headers['Authorization'][0] ?? null;

        if (!$token) {
            echo "Token ausente.\n";
            $conn->close();
            return;
        }

        $user = $this->isValidToken($token);

        if (!$user) {
            echo "Token inválido.\n";
            $conn->close();
            return;
        }

        // Validação de lobby ID
        $query = $conn->httpRequest->getUri()->getQuery();
        parse_str($query, $queryArray);
        $lobbyID = $queryArray['lobby_ID'] ?? null;
        $lobbies = LobbyModel::GetExistingLobby($lobbyID);

        if (!$lobbyID) {
            echo "Lobby ID ausente.\n";
            $conn->close();
            return;
        }

        if (!$lobbies) {
            echo "Nenhum lobby encontrado.\n";
            $conn->close();
            return;
        }

        $conn->lobbyID = $lobbyID;

        // Adicionar o cliente na lista
        $this->clients->attach($conn);

        $conn->send('');

        echo "Nova conexão {$conn->resourceId} Usuário ID: {$user['user_ID']} no lobby com ID {$lobbyID} \n\n";
    }

    // Enviar mensagens para os usuário conectados
    public function onMessage(ConnectionInterface $from, $msg)
    {
        $lobbyID = $from->lobbyID;

        // Percorrer a lista de usuários conectados
        foreach ($this->clients as $client) {

            // Não enviar a mensagem para o usuário que enviou a mensagem
            if ($from !== $client && $client->lobbyID == $lobbyID) {
                // Enviar as mensagems para os usuários
                $client->send($msg);
            }
        }

        echo "Usuário {$from->resourceId} enviou uma mensagem. \n\n";
    }

    // Desconectar o cliente do websocket
    public function onClose(ConnectionInterface $conn)
    {
        // Fechar a conexão e retirar o cliente da lista
        $this->clients->detach($conn);

        echo "Usuário {$conn->resourceId} desconectou. \n\n";
    }

    // Função que será chamada caso ocorra algum erro no websocket
    public function onError(ConnectionInterface $conn, Exception $e)
    {
        // Fechar conexão do cliente
        $conn->close();

        echo "Ocorreu um erro: {$e->getMessage()} \n\n";
    }
}


// No fron 

// Exemplo em JavaScript (cliente)
// const lobbyID = 123; // ID do lobby retornado ou definido
// const socket = new WebSocket(`ws://seuservidor:8080/?lobby=${lobbyID}`);