<?php

namespace controller\lobby;

use Exception;

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

    // Abrir conexão para o novo cliente
    public function onOpen(ConnectionInterface $conn)
    {
        // Adicionar o cliente na lista
        $this->clients->attach($conn);

        echo "Nova conexão: {$conn->resourceId}. \n\n";
    }

    // Enviar mensagens para os usuário conectados
    public function onMessage(ConnectionInterface $from, $msg)
    {
        // Percorrer a lista de usuários conectados
        foreach ($this->clients as $client) {

            // Não enviar a mensagem para o usuário que enviou a mensagem
            if ($from !== $client) {
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
