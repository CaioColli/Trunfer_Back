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
    public function GetLobbys(Request $request, Response $response)
    {
        try {
            $token = $request->getHeader('Authorization')[0] ?? null;

            $lobbyModel = new LobbyModel();
            $userModel = new UserModel();

            $userModel->ValidateToken($token);

            $lobbys = $lobbyModel->GetLobbys();

            if (!$lobbys || count($lobbys) == 0) {
                return Messages::Error404($response, ['Lobbies não encontrados.']);
            }

            $response = $response->withStatus(200);
            $response->getBody()->write(json_encode([
                'lobbies' => $lobbys
            ]));

            return $response;
        } catch (Exception $err) {
            return Messages::Error400($response, $err->getMessage());
        }
    }

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

            $lobby_ID = $lobbyModel->CreateLobbyAndAddHost(
                $lobbyName,
                $lobbyAvailable,
                $user['user_ID'],
                $deckID
            );

            // Busca o lobby completo com a lista de players do lobby
            $createdLobby = $lobbyModel->GetLobby($lobby_ID);

            $filteredInfoLobby = [
                'lobby_Host_Name' => $createdLobby['lobby_Host_Name'],
                'lobby_Name' => $createdLobby['lobby_Name'],
                'lobby_Status' => $createdLobby['lobby_Status'],
                'lobby_Available' => (bool)$createdLobby['lobby_Available'],
                'lobby_Players' => $createdLobby['lobby_Players'],
                'deck_Name' => $createdLobby['deck_Name'],
            ];

            $response->getBody()->write(json_encode($filteredInfoLobby));
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

            $lobbyData  = $lobbyModel->GetLobby($lobbyID);

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

            $ok = $lobbyModel->RemovePlayerFromLobby($playerID, $lobbyID);

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

    public function JoinLobby(Request $request, Response $response)
    {
        try {
            $token = $request->getHeader('Authorization')[0] ?? null;

            $userModel = new UserModel();
            $user = $userModel->ValidateToken($token);

            $lobbyID = $request->getAttribute('lobby_ID');

            $lobbyModel = new LobbyModel();
            $lobbyData = $lobbyModel->GetLobby($lobbyID);
            $lobbyPlayers = $lobbyModel->GetLobbyPlayers($lobbyID);

            if (!$lobbyData) {
                return Messages::Error400($response, ['Lobby não encontrado.']);
            }

            if (count($lobbyPlayers) >= 30) {
                return Messages::Error400($response, ['Lobby cheio.']);
            }

            if ($lobbyData['lobby_Available'] === false) {
                return Messages::Error400($response, ['Lobby fechado.']);
            }

            try {
                $lobbyModel->AddPlayerToLobby($user['user_ID'], $lobbyID);
            } catch (Exception $err) {
                return Messages::Error400($response, $err->getMessage());
            }

            $updatedLobby = $lobbyModel->GetLobbyPlayers($lobbyID);

            $hiddeIdPlayers = array_map(function ($player) {
                return [
                    'user_Name' => $player['user_Name'],
                ];
            }, $updatedLobby);

            $response->getBody()->write(json_encode($hiddeIdPlayers));

            return $response->withStatus(200);
        } catch (Exception $err) {
            return Messages::Error400($response, $err->getMessage());
        }
    }

    public function DeleteLobby(Request $request, Response $response)
    {
        try {
            $token = $request->getHeader('Authorization')[0] ?? null;

            $userModel = new UserModel();
            $user = $userModel->ValidateToken($token);

            $lobbyID = $request->getAttribute('lobby_ID');

            if (!$lobbyID) {
                return Messages::Error400($response, ['Lobby nao encontrado ou ID passado incorreto.']);
            }

            $lobbyModel = new LobbyModel();

            $lobbyExists = $lobbyModel->GetLobby($lobbyID);

            if (!$lobbyExists) {
                return Messages::Error400($response, ['Lobby não encontrado.']);
            }

            $isHost = ($user['user_ID'] == $lobbyExists['lobby_Host_User_ID']);

            if (!$isHost) {
                return Messages::Error400($response, [' Vocé não é o host deste lobby.']);
            }

            $ok = $lobbyModel->DeleteLobby($lobbyID);

            if ($ok) {
                $response->getBody()->write(json_encode(["message" => "Lobby deletado com sucesso."]));
                return $response->withStatus(200);
            }

            return Messages::Error400($response, ['Falha ao deletar o lobby.']);
        } catch (Exception $err) {
            return Messages::Error400($response, $err->getMessage());
        }
    }

    public function UpdateLobby(Request $request, Response $response)
    {
        try {
            $token = $request->getHeader('Authorization')[0] ?? null;

            $userModel = new UserModel();
            $user = $userModel->ValidateToken($token);

            $lobbyID = $request->getAttribute('lobby_ID');

            $data = json_decode($request->getBody()->getContents(), true);

            $lobbyName = $data['lobby_Name'] ?? null;
            $lobbyAvailable = $data['lobby_Available'] ?? true;

            $deckID = $data['deck_ID'] ?? null;

            $deckModel = new DeckModel();
            $deck = $deckModel->GetDeck($deckID);

            if (!$deck || $deck['deck_Is_Available'] === false) {
                return Messages::Error400($response, ['Deck inválido ou não disponível.']);
            }

            $lobbyModel = new LobbyModel();
            $lobbyExists = $lobbyModel->GetLobby($lobbyID);

            if (!$lobbyExists) {
                return Messages::Error400($response, ['Lobby não encontrado.']);
            }

            $isHost = ($user['user_ID'] == $lobbyExists['lobby_Host_User_ID']);

            if (!$isHost) {
                return Messages::Error400($response, ['Você não é o host deste lobby.']);
            }

            $updated = $lobbyModel->EditLobby(
                $lobbyID,
                $lobbyName,
                $lobbyAvailable,
                $deckID
            );

            if ($updated) {
                $updatedLobby = $lobbyModel->GetLobby($lobbyID);

                $filteredInfoLobby = [
                    'lobby_Host_Name' => $updatedLobby['lobby_Host_Name'],
                    'lobby_Name' => $updatedLobby['lobby_Name'],
                    'lobby_Status' => $updatedLobby['lobby_Status'],
                    'lobby_Available' => (bool)$updatedLobby['lobby_Available'],
                    'lobby_Players' => $updatedLobby['lobby_Players'],
                    'deck_Name' => $updatedLobby['deck_Name'],
                ];

                $response->getBody()->write(json_encode($filteredInfoLobby));
                return $response->withStatus(200);
            }

            return Messages::Error400($response, ['Falha ao atualizar o lobby.']);
        } catch (Exception $err) {
            return Messages::Error400($response, $err->getMessage());
        }
    }
}
