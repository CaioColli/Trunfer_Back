<?php

namespace controller\lobby;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use model\lobby\LobbyModel;
use model\adm\DeckModel;
use model\user\UserModel;
use response\Messages;
use Exception;

class LobbyController
{
    public function CreateLobby(Request $request, Response $response)
    {
        try {
            $token = $request->getHeader('Authorization')[0] ?? null;
            $userModel = new UserModel();
            $user = $userModel->ValidateToken($token);

            // Lê o JSON do corpo da requisição
            $data = json_decode($request->getBody()->getContents(), true);

            $lobbyName = $data['lobby_Name'] ?? null;
            $lobbyAvailable = isset($data['lobby_Available']) ? (bool)$data['lobby_Available'] : true;
            $deckID = $data['deck_ID'] ?? null;

            $errors = [];

            if (empty($lobbyName)) {
                $errors[] = 'Nome do lobby é obrigatório.';
            }

            if (empty($deckID)) {
                $errors[] = 'É obrigatório selecionar um deck.';
            }

            if (!empty($errors)) {
                return Messages::Error400($response, $errors);
            }

            if (count($errors) > 0) {
                return Messages::Error400($response, $errors);
            }

            $deckModel = new DeckModel();

            $deck = $deckModel->GetDeck($deckID);
            $deckIsAvailable = $deck['deck_Is_Available'];

            $deckErrors = [];

            if (!$deck) {
                $deckErrors[] = 'Deck nao encontrado.';
            }

            if ($deckIsAvailable === false) {
                $deckErrors[] = 'Deck nao disponivel.';
            }

            if (count($deckErrors) > 0) {
                return Messages::Error400($response, $deckErrors);
            }

            // Cria Lobby e inserir host no lobby_players
            $lobbyModel = new LobbyModel();

            $lobby_ID = $lobbyModel->createLobbyAndAddHost(
                $lobbyName,
                $lobbyAvailable,
                $user['user_ID'],
                $deckID
            );

            // Busca o lobby completo com a lista de players do lobby
            $createdLobby = $lobbyModel->getLobby($lobby_ID);

            $response->getBody()->write(json_encode($createdLobby));
            return $response->withStatus(201);
        } catch (Exception $e) {
            $response = $response->withStatus(400);
            $response->getBody()->write(json_encode([
                'error'  => $e->getMessage(),
                'status' => 400
            ]));
            return $response;
        }
    }

    public function RemovePlayer(Request $request, Response $response)
    {
        try {
            $token = $request->getHeader('Authorization')[0] ?? null;

            $userModel = new UserModel();
            $user = $userModel->ValidateToken($token);

            $lobbyID  = $request->getAttribute('lobby_ID');

            $data = json_decode($request->getBody()->getContents(), true);
            $playerID = $data['user_ID'] ?? null;

            $lobbyModel = new LobbyModel();

            $lobbyData  = $lobbyModel->getLobby($lobbyID);

            if (!$lobbyData) {
                $response->getBody()->write(json_encode(["error" => "Lobby não encontrado."]));
                return $response->withStatus(404);
            }

            // Verifica se é o host ou o próprio player
            $isHost = ($user['user_ID'] == $lobbyData['lobby_Host_User_ID']);
            $isMe = ($user['user_ID'] == $playerID);

            if (!$isHost && !$isMe) {
                $response->getBody()->write(json_encode([
                    "error" => "Você não tem permissão para remover este jogador."
                ]));
                return $response->withStatus(403);
            }

            $ok = $lobbyModel->removePlayerFromLobby($playerID, $lobbyID);

            if ($ok) {
                $response->getBody()->write(json_encode(["message" => "Jogador removido com sucesso."]));
                return $response->withStatus(200);
            }

            $response->getBody()->write(json_encode(["error" => "Falha ao remover o jogador."]));
            return $response->withStatus(400);
        } catch (Exception $err) {
            $response->getBody()->write(json_encode(["error" => $err->getMessage()]));
            return $response->withStatus(400);
        }
    }
}
