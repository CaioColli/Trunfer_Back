<?php

namespace controller\lobby;

use model\adm\CardModel;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

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

        $lobbyID = $request->getAttribute('lobby_ID');

        while (true) {
            $lobbies = MatchModel::GetGameState($lobbyID);

            $playersName = MatchModel::GetPlayersName($lobbyID);

            foreach($playersName as $player) {
                $playersName[$player['user_ID']] = $player['user_Name'];
            }

            $allPlayersCards = MatchModel::GetCardsPlayers($lobbyID);

            foreach($allPlayersCards as $player) {
                $userID = $player['user_ID'];
                $playerName = $playersName[$userID];

                $playersCards[] = [
                    'playerName' => $playerName,
                    'numberOfCards' => $player['card_count']
                ];
            }

            echo "data: " . json_encode([
                'lobbies' => $lobbies,
                'playersCards' => $playersCards
            ]) . "\n\n";

            ob_flush();
            flush();

            if (connection_aborted()) {
                break;
            }

            sleep(5);
        }

        return $response;
    }

    public function GetFirtsCard(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $lobbyID = $request->getAttribute('lobby_ID');

        $lobbyData = LobbyModel::GetExistingLobby($lobbyID);
        $playerData = LobbyModel::GetLobbyPlayer($lobbyID, $user['user_ID']);
        $distributedCards = MatchModel::CheckDistributedCards($lobbyID);
        $getAtualCardID = MatchModel::GetAtualCardID($playerData);
        $cardData = CardModel::GetCard($getAtualCardID);

        $errors = [];

        if (!$lobbyData) {
            $errors[] = 'Lobby não encontrado ou ID incorreto.';
        }

        if (!$playerData) {
            $errors[] = 'Jogador não encontrado ou ID incorreto.';
        }

        if ($distributedCards === false) {
            $errors[] = 'Cartas ainda não foram distribuidas.';
        }

        if (!$getAtualCardID) {
            $errors[] = 'ID da carta atual não encontrada.';
        }

        if (!$cardData) {
            $errors[] = 'Carta não encontrada com esse ID.';
        }

        if (count($errors) > 0) {
            return Messages::Error400($response, $errors);
        }

        $response->getBody()->write(json_encode($cardData));
        return $response->withStatus(200);
    }

    // Fazer com que método seja atualizado logo após o ulimo jogador jogar a carta.
    // public function GetDeckCardsSSE(Request $request, Response $response)
    // {
    //     header('Content-Type: text/event-stream');
    //     header('Cache-Control: no-cache');
    //     header('Connection: keep-alive');

    //     set_time_limit(0);

    //     $user = $request->getAttribute('user');
    //     $lobbyID = $request->getAttribute('lobby_ID');

    //     $lobbyData = LobbyModel::GetExistingLobby($lobbyID);
    //     $playerData = LobbyModel::GetLobbyPlayer($lobbyID, $user['user_ID']);
    //     $distributedCards = MatchModel::CheckDistributedCards($lobbyID);

    //     if (!$lobbyData) {
    //         return Messages::Return404($response, 404, 'Lobby não encontrada.');
    //     }

    //     if (!$playerData) {
    //         return Messages::Return401($response, 401, 'Jogador não encontrado no lobby.');
    //     }

    //     if (!$distributedCards) {
    //         return Messages::Return200($response, 200, 'Cartas ainda não foram distribuidas.');
    //     }

    //     while (true) {
    //         if (connection_aborted()) {
    //             break;
    //         }

    //         $cardsInDeck = MatchModel::GetCardsInDeckPlayer($lobbyID, $user['user_ID']);

    //         if (count($cardsInDeck) === 0) {
    //             echo "data: " . json_encode([
    //                 'status' => 200,
    //                 'message' => 'Ok',
    //                 'data' => 'Game Over!',
    //             ]) . "\n\n";

    //             ob_flush();
    //             flush();
    //             break;
    //         }

    //         $responseData = [
    //             'cardsInDeck' => count($cardsInDeck)
    //         ];

    //         echo "data: " . json_encode($responseData) . "\n\n";

    //         ob_flush();
    //         flush();
    //         sleep(10);
    //         continue;
    //     }

    //     return $response->withStatus(200);
    // }

    public function FirstPlay(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $lobbyID = $request->getAttribute('lobby_ID');

        $data = json_decode($request->getBody()->getContents(), true);

        $gameState = MatchModel::GetGameState($lobbyID);
        $currentTurn = $gameState['current_Player_Turn'];
        $currentRound = $gameState['current_Round'];

        $lobbyHost = LobbyModel::GetLobbyHost($lobbyID);
        $isHost = $user['user_ID'] == $lobbyHost;

        $distributedCards = MatchModel::CheckDistributedCards($lobbyID);

        $errors = [];

        if (!isset($data['attribute_ID']) || !in_array($data['attribute_ID'], [1, 2, 3, 4, 5])) {
            $errors[] = 'Atributo inválido. Escolha um entre 1 e 5.';
        }

        if ($currentRound === 1 && !$isHost) {
            $errors[] = 'Somente o host pode jogar na primeira rodada.';
        }

        if ($currentTurn != $user['user_ID']) {
            $errors[] = 'Não é sua vez de jogar.';
        }

        if (!$data['attribute_ID']) {
            $errors[] = 'É necessário passar um atributo.';
        }

        if ($distributedCards === false) {
            $errors[] = 'Cartas ainda não foram distribuidas.';
        }

        if (count($errors) > 0) {
            return Messages::Error400($response, $errors);
        }

        MatchModel::PlayFirstCard($lobbyID, $user['user_ID'], $data['attribute_ID']);

        $nextPlayer = MatchModel::SetNextPlayer($lobbyID, $user['user_ID']);
        MatchModel::UpdateGameTurn($lobbyID, $nextPlayer);

        $response->getBody()->write(json_encode([
            'status' => 200,
            'message' => 'Carta jogada com sucesso.',
            'errors' => '',
        ]));
        return $response->withStatus(200);
    }

    public function PlayTurn(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $lobbyID = $request->getAttribute('lobby_ID');

        $choosedAttribute = MatchModel::GetChoosedAttribute($lobbyID);
        $gameState = MatchModel::GetGameState($lobbyID);
        $currentTurn = $gameState['current_Player_Turn'];
        $currentRound = $gameState['current_Round'];

        $playerData = LobbyModel::GetLobbyPlayer($lobbyID, $user['user_ID']);
        $getPlayerCards = MatchModel::GetPlayerCards($playerData);
        $totalPlayers = LobbyModel::GetTotalPlayersLobby($lobbyID);

        $errors = [];

        if (count($getPlayerCards) === 0) {
            $response->getBody()->write(json_encode([
                'status' => 200,
                'message' => 'Game over.',
                'errors' => '',
            ]));
            return $response->withStatus(200);
        }

        if (!$choosedAttribute) {
            $errors[] = 'O atributo ainda não foi escolhido pelo primeiro jogador.';
        }

        if ($currentTurn != $user['user_ID']) {
            $errors[] = 'Não é sua vez de jogar.';
        }

        if (count($errors) > 0) {
            return Messages::Error400($response, $errors);
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

        $response->getBody()->write(json_encode([
            'status' => 200,
            'message' => 'Carta jogada com sucesso.',
            'errors' => '',
        ]));
        return $response->withStatus(200);
    }

    public static function GetRoundWinnerSSE(Request $request, Response $response)
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');

        set_time_limit(0);

        $lobbyID = $request->getAttribute('lobby_ID');

        while (true) {
            if (connection_aborted()) {
                break;
            }

            $roundWinner = MatchModel::GetRoundWinner($lobbyID);

            if (!$roundWinner) {
                echo "data: " . json_encode([
                    'status' => 200,
                    'message' => 'Ok',
                    'data' => 'Rodada em andamento, esperando um vencedor.',
                ]) . "\n\n";
                ob_flush();
                flush();
                sleep(5);
                continue;
            } else {
                echo "data: " . (json_encode([
                    'status' => 200,
                    'message' => 'Ok',
                    'data' => 'Vencedor da rodada: ' . $roundWinner['round_Winner_User_Name'],
                ])) . "\n\n";

                ob_flush();
                flush();

                sleep(10);
                MatchModel::ResetRoundWinner($lobbyID);
                break;
            }


            ob_flush();
            flush();
            sleep(5);
        }

        return $response;
    }

    public function GetGameWinnerSSE(Request $request, Response $response)
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');

        set_time_limit(0);

        $lobbyID = $request->getAttribute('lobby_ID');

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

            $userHasAllCards = MatchModel::GetUserHasAllCards($lobbyID);

            if (!$userHasAllCards['hasAllCards']) {
                echo "data: " . json_encode([
                    'status' => 200,
                    'message' => 'Ok',
                    'data' => 'Jogo em andamento, esperando um vencedor.'
                ]) . "\n\n";

                ob_flush();
                flush();
                continue;
            } else {
                echo "data: " . json_encode([
                    'status' => 200,
                    'message' => 'Ok',
                    'data' => 'Vencedor do jogo: ' . $userHasAllCards['winnerName']
                ]) . "\n\n";

                ob_flush();
                flush();
                break;
            }

            sleep(5);
        }

        return $response;
    }
}
