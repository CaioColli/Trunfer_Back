<?php

namespace controller\lobby;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use helpers\Utils;
use model\adm\CardModel;
use model\lobby\LobbyModel;
use model\lobby\MatchModel;
use response\Messages;

class MatchController
{
    public function GetGameStateSSE(Request $request, Response $response)
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');

        set_time_limit(0);

        $user = $request->getAttribute('user');
        $lobbyID = $request->getAttribute('lobby_ID');

        $lobbyData = LobbyModel::GetLobby($lobbyID);
        $lobbyPlayers = LobbyModel::GetLobbyPlayer($lobbyID, $user['user_ID']);

        while (true) {
            if (connection_aborted()) {
                break;
            }

            if (!$lobbyData) {
                Messages::ReturnSSE(404, 'Not Found', 'Lobby não encontrado.');
                break;
            }

            if (!$lobbyPlayers) {
                Messages::ReturnSSE(401, 'Unauthorized', 'Jogador nao encontrado no lobby.');
                break;
            }

            $lobbies = MatchModel::GetGameState($lobbyID);

            $playersName = MatchModel::GetPlayersName($lobbyID);

            foreach ($playersName as $player) {
                $playersName[$player['user_ID']] = $player['user_Name'];
            }

            $allPlayersCards = MatchModel::GetCardsPlayers($lobbyID);

            foreach ($allPlayersCards as $player) {
                $userID = $player['user_ID'];
                $playerName = $playersName[$userID];

                $playersCards[] = [
                    'playerName' => $playerName,
                    'numberOfCards' => $player['card_count']
                ];
            }

            Messages::ReturnSSE(200, 'Ok', [$lobbies, $playersCards]);

            ob_flush();
            flush();

            sleep(5);
        }

        return $response;
    }

    public function GetFirtsCard(Request $request, Response $response)
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
            return Messages::Return200($response, 200, 'Cartas ainda não foram distribuidas.');
        }

        if (!$cardData) {
            return Messages::Return404($response, 404, 'Carta não encontrada.');
        }

        $response->getBody()->write(json_encode($cardData));
        return $response->withStatus(200);
    }

    public function FirstPlay(Request $request, Response $response)
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
            return Messages::Return400($response, 'Atributo inválido. Escolha um entre 1 e 5.');
        }

        if ($currentRound === 1 && !$isHost) {
            return Messages::Return401($response, 401, 'Somente o host pode jogar na primeira rodada.');
        }

        if ($currentTurn != $user['user_ID']) {
            return Messages::Return200($response, 200, 'Não é sua vez de jogar.');
        }

        if (!$data['attribute_ID']) {
            return Messages::Return400($response, 'É necessário passar um atributo.');
        }

        if ($distributedCards === false) {
            return Messages::Return200($response, 200, 'Cartas ainda não foram distribuidas.');
        }

        MatchModel::PlayFirstCard($lobbyID, $user['user_ID'], $data['attribute_ID']);

        $nextPlayer = MatchModel::SetNextPlayer($lobbyID, $user['user_ID']);
        MatchModel::UpdateGameTurn($lobbyID, $nextPlayer);

        $response = Messages::Return200($response, 200, 'Carta jogada com sucesso.');
        return $response->withStatus(200);
    }

    public function PlayTurn(Request $request, Response $response)
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
            return Messages::Return200($response, 200, 'Game over.');
        }

        if (!$choosedAttribute) {
            return Messages::Return200($response, 200, 'O atributo ainda não foi escolhido pelo primeiro jogador.');
        }

        if ($currentTurn != $user['user_ID']) {
            return Messages::Return200($response, 200, 'Não é sua vez de jogar.');
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

        $response = Messages::Return200($response, 200, 'Carta jogada com sucesso.');
        return $response->withStatus(200);
    }

    public static function GetWinnerSSE(Request $request, Response $response)
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

        if ($userHasAllCards['hasAllCards']) {
            MatchModel::SetPointsToWinnerPlayer($userHasAllCards['winnerID']);
            $teste = MatchModel::setPointsToPlayedMatch($lobbyID);
            var_dump($teste);
        }

        while (true) {
            if (connection_aborted()) {
                break;
            }

            if (!$lobbyData) {
                Messages::ReturnSSE(404, 'Not Found', 'Lobby não encontrado.');
                break;
            }

            if (!$lobbyPlayers) {
                Messages::ReturnSSE(401, 'Unauthorized', 'Jogador nao encontrado no lobby.');
                break;
            }

            $userHasAllCards = MatchModel::GetUserHasAllCards($lobbyID);

            if ($userHasAllCards['hasAllCards']) {
                Messages::ReturnSSE(200, 'Ok', 'Vencedor do jogo: ' . $userHasAllCards['winnerName']);

                ob_flush();
                flush();
                sleep(10);
                break;
            }

            $roundWinner = MatchModel::GetRoundWinner($lobbyID);

            if (!$roundWinner) {
                Messages::ReturnSSE(200, 'Ok', 'Rodada em andamento, esperando um vencedor.');

                ob_flush();
                flush();
                sleep(5);
                continue;
            }

            if ($roundWinner) {
                Messages::ReturnSSE(200, 'Ok', 'Vencedor da rodada: ' . $roundWinner['round_Winner_User_Name']);

                ob_flush();
                flush();
                sleep(10);
                MatchModel::ResetRoundWinner($lobbyID);
                continue;
            }

            ob_flush();
            flush();
            sleep(5);
        }

        return $response;
    }
}
