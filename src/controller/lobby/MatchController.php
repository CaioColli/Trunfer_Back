<?php

namespace controller\lobby;

use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;

use helpers\Utils;
use model\adm\CardModel;
use model\lobby\LobbyModel;
use model\lobby\MatchModel;
use response\Response;

class MatchController
{
    public function GetGameStateSSE(PsrRequest $request, PsrResponse $response)
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');

        set_time_limit(0);

        $user = $request->getAttribute('user');
        $lobbyID = $request->getAttribute('lobby_ID');

        $lobbyData = LobbyModel::GetLobby($lobbyID);
        $lobbyPlayers = LobbyModel::GetLobbyPlayer($lobbyID, $user['user_ID']);

        $lastDataHash = '';

        while (true) {
            if (!$lobbyData) {
                Response::ReturnSSE(404, 'Not Found', 'Lobby não encontrado.');
                break;
            }

            if (!$lobbyPlayers) {
                Response::ReturnSSE(401, 'Unauthorized', 'Jogador nao encontrado no lobby.');
                break;
            }

            $match = MatchModel::GetGameState($lobbyID);

            $playersName = MatchModel::GetPlayersName($lobbyID);

            foreach ($playersName as $player) {
                $playersName[$player['user_ID']] = $player['user_Name'];
            }

            $allPlayersCards = MatchModel::GetCardsPlayers($lobbyID);

            $currentDataHash = md5(json_encode([$match, $allPlayersCards]));

            $playersCards = [];

            foreach ($allPlayersCards as $player) {
                $userID = $player['user_ID'];
                $playerName = $playersName[$userID];

                $playersCards[] = [
                    'playerName' => $playerName,
                    'numberOfCards' => $player['card_count']
                ];
            }

            if ($currentDataHash !== $lastDataHash) {
                echo "data: " . json_encode(['gameData' => $match, 'playersCards' => $playersCards]) . "\n\n";
                ob_flush();
                flush();
                $lastDataHash = $currentDataHash;
            }

            sleep(2);

            if (connection_aborted()) {
                break;
            }
        }

        return $response;
    }

    public function GetFirtsCard(PsrRequest $request, PsrResponse $response)
    {
        $user = $request->getAttribute('user');
        $lobbyID = $request->getAttribute('lobby_ID');

        $lobbyPlayers = LobbyModel::GetLobbyPlayer($lobbyID, $user['user_ID']);

        $distributedCards = MatchModel::CheckDistributedCards($lobbyID);
        $getTopCard = MatchModel::GetAtualCardID($lobbyPlayers);
        $cardData = CardModel::GetCard($getTopCard);

        $validateLobby = Utils::ValidateLobby($lobbyID, $user, $response);

        if ($validateLobby) {
            return $validateLobby;
        }

        if ($distributedCards === false) {
            return Response::Return200($response, 'Cartas ainda não foram distribuidas.');
        }

        if (!$cardData) {
            return Response::Return404($response, 'Carta não encontrada.');
        }

        $response->getBody()->write(json_encode($cardData));
        return $response->withStatus(200);
    }

    public function FirstPlay(PsrRequest $request, PsrResponse $response)
    {
        $user = $request->getAttribute('user');
        $lobbyID = $request->getAttribute('lobby_ID');

        $data = json_decode($request->getBody()->getContents(), true);

        $validateLobby = Utils::ValidateLobby($lobbyID, $user, $response);

        $gameState = MatchModel::GetGameState($lobbyID);
        $currentTurn = $gameState['current_Player_Turn'];
        $currentRound = $gameState['current_Round'];

        $lobbyHost = LobbyModel::GetLobbyHost($lobbyID);
        $isHost = $user['user_ID'] == $lobbyHost;

        $distributedCards = MatchModel::CheckDistributedCards($lobbyID);

        if ($validateLobby) {
            return $validateLobby;
        }

        if (!isset($data['attribute_ID']) || !in_array($data['attribute_ID'], [1, 2, 3, 4, 5])) {
            return Response::Return400($response, 'Atributo inválido. Escolha um entre 1 e 5.');
        }

        if ($currentRound === 1 && !$isHost) {
            return Response::Return401($response, 'Somente o host pode jogar na primeira rodada.');
        }

        if ($currentTurn != $user['user_ID']) {
            return Response::Return200($response, 'Não é sua vez de jogar.');
        }

        if (!$data['attribute_ID']) {
            return Response::Return400($response, 'É necessário passar um atributo.');
        }

        if ($distributedCards === false) {
            return Response::Return200($response, 'Cartas ainda não foram distribuidas.');
        }

        MatchModel::PlayFirstCard($lobbyID, $user['user_ID'], $data['attribute_ID']);

        $nextPlayer = MatchModel::SetNextPlayer($lobbyID, $user['user_ID']);
        MatchModel::UpdateGameTurn($lobbyID, $nextPlayer);

        $response = Response::Return200($response, 'Carta jogada com sucesso.');
        return $response->withStatus(200);
    }

    public function PlayTurn(PsrRequest $request, PsrResponse $response)
    {
        $user = $request->getAttribute('user');
        $lobbyID = $request->getAttribute('lobby_ID');

        $validateLobby = Utils::ValidateLobby($lobbyID, $user, $response);

        $choosedAttribute = MatchModel::GetChoosedAttribute($lobbyID);
        $gameState = MatchModel::GetGameState($lobbyID);
        $currentTurn = $gameState['current_Player_Turn'];
        $currentRound = $gameState['current_Round'];

        $playerData = LobbyModel::GetLobbyPlayer($lobbyID, $user['user_ID']);
        $getPlayerCards = MatchModel::GetPlayerCards($playerData);
        $totalPlayers = LobbyModel::GetTotalPlayersLobby($lobbyID);

        if ($validateLobby) {
            return $validateLobby;
        }

        if (count($getPlayerCards) === 0) {
            return Response::Return200($response, 'Game over.');
        }

        if (!$choosedAttribute) {
            return Response::Return200($response, 'O atributo ainda não foi escolhido pelo primeiro jogador.');
        }

        if ($currentTurn != $user['user_ID']) {
            return Response::Return200($response, 'Não é sua vez de jogar.');
        }

        MatchModel::PlayTurn($lobbyID, $user['user_ID']);

        $movesCount = MatchModel::GetPlayersPlayed($lobbyID);

        if ((int)$movesCount === (int)$totalPlayers) {
            MatchModel::DetermineWinner($lobbyID, $currentRound);

            $roundWinner = MatchModel::GetRoundWinner($lobbyID);
            $winnerID = $roundWinner['round_Winner'];

            MatchModel::TransferCardsToWinner((int)$lobbyID, $winnerID);
            MatchModel::IncrementRound($lobbyID);
            MatchModel::UpdateGameTurn($lobbyID, $winnerID);
            MatchModel::SetNextPlayer($lobbyID, $winnerID);
        } else {
            $nextPlayer = MatchModel::SetNextPlayer($lobbyID, $user['user_ID']);
            MatchModel::UpdateGameTurn($lobbyID, $nextPlayer);
        }

        $response = Response::Return200($response, 'Carta jogada com sucesso.');
        return $response->withStatus(200);
    }

    public static function GetWinnerSSE(PsrRequest $request, PsrResponse $response)
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');

        set_time_limit(0);

        $user = $request->getAttribute('user');
        $lobbyID = $request->getAttribute('lobby_ID');

        $lobbyData = LobbyModel::GetLobby($lobbyID);
        $lobbyPlayers = LobbyModel::GetLobbyPlayer($lobbyID, $user['user_ID']);

        $userHasAllCards = MatchModel::GetUserHasAllCards($lobbyID);

        $lastDataHash = '';

        while (true) {
            if (connection_aborted()) {
                break;
            }

            if (!$lobbyData) {
                Response::ReturnSSE(404, 'Not Found', 'Lobby não encontrado.');
                break;
            }

            if (!$lobbyPlayers) {
                Response::ReturnSSE(401, 'Unauthorized', 'Jogador nao encontrado no lobby.');
                break;
            }

            $userHasAllCards = MatchModel::GetUserHasAllCards($lobbyID);

            $roundWinner = MatchModel::GetRoundWinner($lobbyID);

            $currentDataHash = md5(json_encode([$userHasAllCards, $roundWinner]));

            if ($currentDataHash !== $lastDataHash) {
                $lastDataHash = $currentDataHash;

                if ($userHasAllCards['hasAllCards']) {
                    MatchModel::SetPointsToWinnerPlayer($userHasAllCards['winnerID']);
                    MatchModel::SetPointsToPlayedMatch($lobbyID);

                    Response::ReturnSSE(200, 'Ok', 'Vencedor do jogo: ' . $userHasAllCards['winnerName']);
                    ob_flush();
                    flush();
                    MatchModel::ResetRoundWinner($lobbyID);
                    break;
                }

                if (!$roundWinner) {
                    Response::ReturnSSE(200, 'Ok', 'Rodada em andamento, esperando um vencedor.');
                    ob_flush();
                    flush();
                    continue;
                }

                Response::ReturnSSE(200, 'Ok', 'Vencedor da rodada: ' . $roundWinner['round_Winner_User_Name']);
                ob_flush();
                flush();
                MatchModel::ResetRoundWinner($lobbyID);
            }
            
            sleep(2);
        }

        return $response;
    }
}
