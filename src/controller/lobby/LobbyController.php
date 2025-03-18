<?php

namespace controller\lobby;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use helpers\Utils;
use model\lobby\LobbyModel;
use model\adm\DeckModel;
use model\lobby\MatchModel;
use response\Messages;
use validation\LobbyValidation;

class LobbyController
{
    public function CreateLobby(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');

        $data = json_decode($request->getBody()->getContents(), true);

        $lobbyName = $data['lobby_Name'];
        $deckID = $data['deck_ID'];

        $deck = DeckModel::GetDeck($deckID);
        $deckIsAvailable = $deck['deck_Is_Available'];

        $rules = LobbyValidation::LobbyCreate();

        $playerInLobby = LobbyModel::VerifyPlayerInLobby($user['user_ID']);

        if ($playerInLobby) {
            return Messages::Return401($response, 401, 'Você já está no lobby, saia do lobby atual para criar outro.');
        }

        if (!$rules['lobby_Name']->validate($data['lobby_Name'])) {
            return Messages::Return400($response, 400, 'Nome do lobby é obrigatório e deve conter no mínimo 3 e no maximo 50 caracteres.');
        }

        if (empty($deckID)) {
            return Messages::Return400($response, 400, 'É obrigatório selecionar um baralho.');
        }

        if (!$deck) {
            return Messages::Return404($response, 404, 'Baralho não encontrado.');
        }

        if ($deckIsAvailable === 0) {
            return Messages::Return403($response, 'Baralho indisponivel.');
        }

        // Cria Lobby e insere o host no lobby
        $lobby = LobbyModel::CreateLobby(
            $lobbyName,
            $user['user_ID'],
            $deckID
        );

        // Exibe o lobby criado
        $lobbyData = LobbyModel::GetLobby($lobby);

        $response->getBody()->write(json_encode($lobbyData));
        return $response->withStatus(201);
    }

    public function GetLobbiesSSE(Request $request, Response $response)
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');

        set_time_limit(0);

        while (true) {
            $lobbies = LobbyModel::GetLobbys();

            if (count($lobbies) < 1) {
                Messages::ReturnSSE(200, 'Ok', 'Nenhum lobby criado ou encontrado.');
            } else {
                echo "data: " . json_encode(['lobbies' => $lobbies]) . "\n\n";
            }

            ob_flush();
            flush();

            if (connection_aborted()) {
                break;
            }

            sleep(10);
        }

        return $response;
    }

    public function GetLobbySSE(Request $request, Response $response)
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');

        set_time_limit(0);

        $lobbyID = $request->getAttribute('lobby_ID');

        $lobbyData = LobbyModel::GetLobby($lobbyID);

        while (true) {
            if (!$lobbyData) {
                Messages::ReturnSSE(404, 'Not Found', 'Lobby não encontrado.');
                break;
            }

            $lobbyData = LobbyModel::GetLobby($lobbyID);

            echo "data: " . json_encode($lobbyData) . "\n\n";

            ob_flush();
            flush();

            if (connection_aborted()) {
                break;
            }

            sleep(5);
        }

        return $response;
    }

    public function JoinLobby(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $lobbyID = $request->getAttribute('lobby_ID');

        $lobbyData = LobbyModel::GetLobby($lobbyID);
        $lobbyPlayers = LobbyModel::GetPlayersLobby($lobbyID);
        $playerInLobby = LobbyModel::VerifyPlayerInLobby($user['user_ID']);

        $validateLobby = Utils::ValidateLobby($lobbyID, $user, $response);

        if ($validateLobby) {
            return $validateLobby;
        }

        if ($playerInLobby) {
            if ($playerInLobby['lobby_ID'] == $lobbyID) {
                return Messages::Return200($response, 'Vocé já esta no lobby atual.');
            } else {
                return Messages::Error400($response, 'Vocé já está no lobby, saia do lobby atual para entrar em outro.');
            }
        }

        if (count($lobbyPlayers) >= 30) {
            return Messages::Return400($response, 'Lobby cheio.');
        }

        if ($lobbyData['lobby_Available'] === 0) {
            return Messages::Return200($response, 'Lobby fechado.');
        }

        LobbyModel::JoinLoby($user['user_ID'], $lobbyID);

        $response->getBody()->write(json_encode([
            'players' => LobbyModel::GetLobbyPlayers($lobbyID)
        ]));
        return $response->withStatus(200);
    }

    public function RemovePlayer(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $lobbyID  = $request->getAttribute('lobby_ID');

        $validateLobby = Utils::ValidateLobby($lobbyID, $user, $response);

        $data = json_decode($request->getBody()->getContents(), true);

        $playerID = $data['user_ID'];

        $lobbyHost = LobbyModel::GetLobbyHost($lobbyID);

        $isHost = ($user['user_ID'] == $lobbyHost);
        $isPlayer = ($user['user_ID'] === $playerID);

        if ($validateLobby) {
            return $validateLobby;
        }

        // Verifica se é o host ou o próprio player
        if (!$isHost && !$isPlayer) {
            return Messages::Return401($response, 'Você não tem permissão para remover este jogador.');
        }

        LobbyModel::RemovePlayer($playerID, $lobbyID);

        $response = Messages::Return200($response, 'Jogador removido do lobby com sucesso.');
        return $response->withStatus(200);
    }

    public function EditLobby(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $lobbyID = $request->getAttribute('lobby_ID');

        $validateLobby = Utils::ValidateLobby($lobbyID, $user, $response);

        $data = json_decode($request->getBody()->getContents(), true);

        $lobbyData = LobbyModel::GetLobby($lobbyID);
        $lobbyHost = LobbyModel::GetLobbyHost($lobbyID);

        $lobbyName = $data['lobby_Name'] ?? $lobbyData['lobby_Name'];
        $lobbyAvailable = (int)$data['lobby_Available'] ?? $lobbyData['lobby_Available'];
        $deckID = $data['deck_ID'] ?? $lobbyData['deck_ID'];

        $deckData = DeckModel::GetDecks($deckID);

        $rules = LobbyValidation::EditLobby();

        $isHost = ($user['user_ID'] == $lobbyHost);

        if ($validateLobby) {
            return $validateLobby;
        }

        if ($isHost) {
            if (!$rules['lobby_Name']->validate($data['lobby_Name'])) {
                return Messages::Return400($response, 'Nome inválido ou ausente.');
            }

            if (!$rules['lobby_Available']->validate($data['lobby_Available'])) {
                return Messages::Return400($response, 'O valor deve ser booleano.');
            }

            if (isset($data['deck_ID'])) {
                if (($data['deck_ID']) && !$rules['deck_ID']->validate($data['deck_ID'])) {
                    return Messages::Return400($response, 'Baralho inválido ou ausente.');
                } elseif (!$deckData) {
                    return Messages::Return404($response, 'Baralho inexistente.');
                } elseif ($deckData['deck_Is_Available'] === 0) {
                    return Messages::Return400($response, 'Baralho indisponivel.');
                }
            }

            if ($lobbyData['lobby_Status'] != 'Aguardando') {
                return Messages::Return422($response, 'Confuração do lobby desativada durante o jogo.');
            }
        } else {
            return Messages::Return401($response, 'Você precisa ser o host do lobby para editar o lobby.');
        }

        LobbyModel::EditLobby(
            $lobbyID,
            $lobbyName,
            $lobbyAvailable,
            $deckID
        );

        $response->getBody()->write(json_encode(LobbyModel::GetLobby($lobbyID)));
        return $response->withStatus(200);
    }

    public function DeleteLobby(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $lobbyID = $request->getAttribute('lobby_ID');

        $validateLobby = Utils::ValidateLobby($lobbyID, $user, $response);

        $lobbyHost = LobbyModel::GetLobbyHost($lobbyID);

        $isHost = ($user['user_ID'] == $lobbyHost);

        if ($validateLobby) {
            return $validateLobby;
        }

        if (!$isHost) {
            return Messages::Error401($response, 'Você precisa ser o host do lobby para deletar o lobby.');
        }

        LobbyModel::DeleteLobby($lobbyID);

        $response = Messages::Return200($response, 'Lobby deletado com sucesso.');
        return $response->withStatus(200);
    }

    public function StartLobby(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $userID = $user['user_ID'];

        $lobbyID = $request->getAttribute('lobby_ID');

        $validateLobby = Utils::ValidateLobby($lobbyID, $user, $response);

        $lobbyHost = LobbyModel::GetLobbyHost($lobbyID);

        $isHost = $userID == $lobbyHost;

        if ($validateLobby) {
            return $validateLobby;
        }

        if ($isHost) {
            if (count(LobbyModel::GetPlayersLobby($lobbyID)) < 2) {
                return Messages::Return200($response, 'Lobby precisa ter pelo menos 2 jogadores para iniciar.');
            }

            if (LobbyModel::GetLobbyStatus($lobbyID) != 'Aguardando') {
                return Messages::Return400($response, 'Partida já iniciada.');
            }

            if (MatchModel::CheckDistributedCards($lobbyID) === true) {
                return Messages::Return400($response, 'Cartas já distribuidas');
            }
        } else {
            return Messages::Return401($response, 'Você precisa ser o host do lobby para iniciar o lobby.');
        }

        LobbyModel::StartLobby($lobbyID);
        MatchModel::DistributeCards($lobbyID, $userID);

        $response = Messages::Return200($response, 'Partida iniciada com sucesso.');
        return $response->withStatus(200);
    }
}
