<?php

namespace controller\lobby;

use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;

use helpers\Utils;
use model\lobby\LobbyModel;
use model\adm\DeckModel;
use model\lobby\MatchModel;
use response\Response;

use validation\LobbyValidation;

class LobbyController
{
    public function CreateLobby(PsrRequest $request, PsrResponse $response)
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
            return Response::Return401($response, 401, 'Você já está no lobby, saia do lobby atual para criar outro.');
        }

        if (!$rules['lobby_Name']->validate($data['lobby_Name'])) {
            return Response::Return400($response, 400, 'Nome do lobby é obrigatório e deve conter no mínimo 3 e no maximo 50 caracteres.');
        }

        if (empty($deckID)) {
            return Response::Return400($response, 400, 'É obrigatório selecionar um baralho.');
        }

        if (!$deck) {
            return Response::Return404($response, 404, 'Baralho não encontrado.');
        }

        if ($deckIsAvailable === 0) {
            return Response::Return403($response, 'Baralho indisponivel.');
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

    public function GetLobbiesSSE(PsrRequest $request, PsrResponse $response)
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');

        set_time_limit(0);

        $lastDataHash = '';

        while (true) {
            $lobbies = LobbyModel::GetLobbys();
            $jsonData = json_encode(['lobbies' => $lobbies]);
            $currentHash = md5($jsonData);

            if (count($lobbies) < 1) {
                return Response::ReturnSSE(200, 'Ok', 'Nenhum lobby criado');
            }

            if ($currentHash !== $lastDataHash) {
                echo "data: " . $jsonData . "\n\n";
                ob_flush();
                flush();
                $lastDataHash = $currentHash;
            }

            sleep(2);

            if (connection_aborted()) {
                break;
            }
        }

        return $response;
    }

    public function GetLobbySSE(PsrRequest $request, PsrResponse $response)
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');

        set_time_limit(0);

        $lobbyID = $request->getAttribute('lobby_ID');

        $lobbyData = LobbyModel::GetLobby($lobbyID);

        $lastDataHash = '';

        while (true) {
            if (!$lobbyData) {
                Response::ReturnSSE(404, 'Not Found', 'Lobby não encontrado.');
                break;
            }

            $lobbyData = LobbyModel::GetLobby($lobbyID);
            $currentHash = md5(json_encode($lobbyData));

            if ($currentHash !== $lastDataHash) {
                echo "data: " . json_encode($lobbyData) . "\n\n";
                ob_flush();
                flush();
                $lastDataHash = $currentHash;
            }

            sleep(2);

            if (connection_aborted()) {
                break;
            }
        }

        return $response;
    }

    public function JoinLobby(PsrRequest $request, PsrResponse $response)
    {
        $user = $request->getAttribute('user');
        $lobbyID = $request->getAttribute('lobby_ID');

        $lobbyData = LobbyModel::GetLobby($lobbyID);
        $lobbyPlayers = LobbyModel::GetPlayersLobby($lobbyID);
        $playerInLobby = LobbyModel::VerifyPlayerInLobby($user['user_ID']);

        if ($playerInLobby) {
            if ($playerInLobby['lobby_ID'] == $lobbyID) {
                return Response::Return200($response, 'Vocé já esta no lobby atual.');
            } else {
                return Response::Return400($response, 'Vocé já está no lobby, saia do lobby atual para entrar em outro.');
            }
        }

        if (count($lobbyPlayers) >= 30) {
            return Response::Return400($response, 'Lobby cheio.');
        }

        if ($lobbyData['lobby_Available'] === 0) {
            return Response::Return200($response, 'Lobby fechado.');
        }

        LobbyModel::JoinLoby($user['user_ID'], $lobbyID);

        $response->getBody()->write(json_encode([
            'players' => LobbyModel::GetLobbyPlayers($lobbyID)
        ]));
        return $response->withStatus(200);
    }

    public function RemovePlayer(PsrRequest $request, PsrResponse $response)
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
            return Response::Return401($response, 'Você não tem permissão para remover este jogador.');
        }

        LobbyModel::RemovePlayer($playerID, $lobbyID);

        $response = Response::Return200($response, 'Jogador removido do lobby com sucesso.');
        return $response->withStatus(200);
    }

    public function EditLobby(PsrRequest $request, PsrResponse $response)
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
                return Response::Return400($response, 'Nome inválido ou ausente.');
            }

            if (!$rules['lobby_Available']->validate($data['lobby_Available'])) {
                return Response::Return400($response, 'O valor deve ser booleano.');
            }

            if (isset($data['deck_ID'])) {
                if (($data['deck_ID']) && !$rules['deck_ID']->validate($data['deck_ID'])) {
                    return Response::Return400($response, 'Baralho inválido ou ausente.');
                } elseif (!$deckData) {
                    return Response::Return404($response, 'Baralho inexistente.');
                } elseif ($deckData['deck_Is_Available'] === 0) {
                    return Response::Return400($response, 'Baralho indisponivel.');
                }
            }

            if ($lobbyData['lobby_Status'] != 'Aguardando') {
                return Response::Return422($response, 'Confuração do lobby desativada durante o jogo.');
            }
        } else {
            return Response::Return401($response, 'Você precisa ser o host do lobby para editar o lobby.');
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

    public function DeleteLobby(PsrRequest $request, PsrResponse $response)
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
            // return Response::Error401($response, 'Você precisa ser o host do lobby para deletar o lobby.');
            return Response::Return401($response, ' Vocé precisa ser o host do lobby para deletar o lobby.');
        }

        LobbyModel::DeleteLobby($lobbyID);

        $response = Response::Return200($response, 'Lobby deletado com sucesso.');
        return $response->withStatus(200);
    }

    public function StartLobby(PsrRequest $request, PsrResponse $response)
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
                return Response::Return200($response, 'Lobby precisa ter pelo menos 2 jogadores para iniciar.');
            }

            if (LobbyModel::GetLobbyStatus($lobbyID) != 'Aguardando') {
                return Response::Return400($response, 'Partida já iniciada.');
            }

            if (MatchModel::CheckDistributedCards($lobbyID) === true) {
                return Response::Return400($response, 'Cartas já distribuidas');
            }
        } else {
            return Response::Return401($response, 'Você precisa ser o host do lobby para iniciar o lobby.');
        }

        LobbyModel::StartLobby($lobbyID);
        MatchModel::DistributeCards($lobbyID, $userID);

        $response = Response::Return200($response, 'Partida iniciada com sucesso.');
        return $response->withStatus(200);
    }
}
