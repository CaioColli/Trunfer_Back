<?php

// user_ID

namespace model\lobby;

use App\Model\Connection;
use Dom\Comment;
use Exception;
use PDO;

class MatchModel
{
    public static function CheckDistributedCards($lobby_ID)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                SELECT EXISTS (
                    SELECT 1 FROM player_cards WHERE lobby_ID = :lobby_ID
                ) AS exists_cards
            ');

            $sql->bindParam(':lobby_ID', $lobby_ID);
            $sql->execute();

            $result = $sql->fetch();

            return (bool) $result['exists_cards'];
        } catch (Exception $err) {
            throw new Exception('Erro ao verificar cartas distribuidas.' . $err);
        }
    }

    public static function DistributeCards($lobby_ID, $user_ID)
    {
        try {
            $db = Connection::getConnection();

            // Obtem o ID do deck associado ao lobby
            $sqlDeckLobby = $db->prepare('
                SELECT deck_ID
                FROM lobbies
                WHERE lobby_ID = :lobby_ID
            ');

            $sqlDeckLobby->bindParam(':lobby_ID', $lobby_ID);
            $sqlDeckLobby->execute();
            $deck = $sqlDeckLobby->fetch();

            $deckID = $deck['deck_ID'];

            // Obter as cartas do deck
            $sqlCardsLobby = $db->prepare('
                SELECT 
                    card_ID
                FROM cards
                WHERE deck_ID = :deck_ID
            ');

            $sqlCardsLobby->bindParam(':deck_ID', $deckID);
            $sqlCardsLobby->execute();
            $cards = $sqlCardsLobby->fetchAll();

            $lobbyPlayers = LobbyModel::GetPlayersLobby($lobby_ID);

            $totalPlayers = count($lobbyPlayers);
            $totalCards = count($cards);
            $cardsPerPlayer = floor($totalCards / $totalPlayers);

            // Embaralhar as cartas
            shuffle($cards);

            $cardIndex = 0;

            // Atribui cartas
            $sqlAssignCardToPlayer = $db->prepare('
                INSERT INTO 
                    player_cards (card_ID, user_ID, card_Position, lobby_ID)
                VALUES 
                    (:card_ID, :user_ID, :card_Position, :lobby_ID)
            ');


            foreach ($lobbyPlayers as $player) {
                $cardPosition = 0;

                for ($i = 0; $i < $cardsPerPlayer; $i++) {
                    $cardPosition++;

                    $sqlAssignCardToPlayer->bindValue(':user_ID', $player['user_ID']);
                    $sqlAssignCardToPlayer->bindValue(':card_ID', $cards[$cardIndex]['card_ID']);
                    $sqlAssignCardToPlayer->bindValue(':card_Position', $cardPosition);
                    $sqlAssignCardToPlayer->bindValue(':lobby_ID', $lobby_ID);

                    $sqlAssignCardToPlayer->execute();

                    $cardIndex++;
                }
            }

            // Atualiza o round e turno
            $sqlGameFlow = $db->prepare('
                INSERT INTO game 
                    (lobby_ID, current_Round, current_Player_Turn)
                VALUE 
                    (:lobby_ID, 1, :current_Player_Turn)
            ');

            $sqlGameFlow->bindParam(':lobby_ID', $lobby_ID);
            $sqlGameFlow->bindValue(':current_Player_Turn', (int)$user_ID);
            $sqlGameFlow->execute();

            return true;
        } catch (Exception $err) {
            throw new Exception('Erro ao distribuir cartas. ' . $err->getMessage());
        }
    }

    public static function GetUserHasAllCards($lobby_ID)
    {
        try {
            $db = Connection::getConnection();

            // Total de cartas do lobby
            $totalLobbyCards = $db->prepare('
                SELECT 
                    COUNT(player_Card_ID) as total
                FROM player_cards
                WHERE user_ID IN (
                    SELECT user_ID
                    FROM lobby_players
                    WHERE lobby_ID = :lobby_ID
                )
            ');

            $totalLobbyCards->bindParam(':lobby_ID', $lobby_ID);
            $totalLobbyCards->execute();
            $totalCards = $totalLobbyCards->fetch();

            // Total de cartas do jogador
            $players = $db->prepare('
                SELECT
                    user_ID,
                    user_Name
                FROM users
                WHERE user_ID IN (
                    SELECT user_ID
                    FROM lobby_players
                    WHERE lobby_ID = :lobby_ID
                )
            ');

            $players->bindParam(':lobby_ID', $lobby_ID);
            $players->execute();
            $players = $players->fetchAll();

            foreach ($players as $player) {
                $userCardsCount = $db->prepare('
                    SELECT COUNT(player_Card_ID) as total
                    FROM player_cards
                    WHERE user_ID = :user_ID
                ');

                $userCardsCount->bindParam(':user_ID', $player['user_ID']);
                $userCardsCount->execute();
                $userCards = $userCardsCount->fetch();

                if ($userCards['total'] === $totalCards['total']) {
                    return [
                        'hasAllCards' => true,
                        'winnerName' => $player['user_Name'],
                        'winnerID' => $player['user_ID']
                    ];
                }
            }

            return ['hasAllCards' => false];
        } catch (Exception $err) {
            throw new Exception('Erro ao verificar as cartas.' . $err);
        }
    }

    public static function GetPlayersPlayed($lobby_ID)
    {
        try {
            $sql = Connection::getConnection();

            $sql = $sql->prepare('
                SELECT 
                    COUNT(DISTINCT user_ID) as moves
                FROM player_moves
                WHERE lobby_ID = :lobby_ID 
                    AND current_Round = :current_Round
            ');

            $gameFlow = MatchModel::GetGameState($lobby_ID);
            $currentRound = $gameFlow['current_Round'];

            $sql->bindParam(':lobby_ID', $lobby_ID);
            $sql->bindParam(':current_Round', $currentRound);
            $sql->execute();

            $moves = $sql->fetch();

            return (int)$moves['moves'];
        } catch (Exception $err) {
            throw new Exception('Erro ao recuperar jogadores que jogaram.' . $err);
        }
    }

    public static function GetGameState($lobby_ID)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                SELECT 
                    g.current_Round,
                    g.current_Player_Turn,
                    u.user_Name AS current_Player_Name
                FROM game g
                JOIN users u ON g.current_Player_Turn = u.user_ID
                WHERE g.lobby_ID = :lobby_ID
                ORDER BY g.game_ID DESC
                LIMIT 1
            ');

            $sql->bindParam(':lobby_ID', $lobby_ID);
            $sql->execute();

            return $sql->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $err) {
            throw new Exception('Erro ao verificar status do jogo.' . $err);
        }
    }

    public static function GetCardWithAttributeChoosed($lobby_ID, $user_ID)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                SELECT 
                    player_Card_ID, 
                    card_ID
                FROM player_cards

                INNER JOIN lobby_players lp ON player_cards.user_ID = lp.user_ID

                WHERE lp.lobby_ID = :lobby_ID 
                    AND lp.user_ID = :user_ID
                ORDER BY card_Position ASC
                LIMIT 1
            ');

            $sql->bindParam(':lobby_ID', $lobby_ID);
            $sql->bindParam(':user_ID', $user_ID);
            $sql->execute();

            return $sql->fetch();
        } catch (Exception) {
            throw new Exception('Erro ao verificar pegar a carta com o atributo escolhido.');
        }
    }

    public static function PlayFirstCard($lobby_ID, $user_ID, $attribute_ID)
    {
        try {
            $db = Connection::getConnection();

            // Registra atributo escolhido na tabela
            $sqlStateGame = $db->prepare('
                UPDATE game 
                    SET round_Attribute_ID = :round_Attribute_ID
                WHERE lobby_ID = :lobby_ID
            ');

            $sqlStateGame->bindParam(':lobby_ID', $lobby_ID);
            $sqlStateGame->bindParam(':round_Attribute_ID', $attribute_ID);
            $sqlStateGame->execute();

            $gameFlow = MatchModel::GetGameState($lobby_ID);
            $currentRound = $gameFlow['current_Round'];

            $card = MatchModel::GetCardWithAttributeChoosed($lobby_ID, $user_ID);

            // Registra a jogada do primeiro jogador
            $sqlFirstPlayed = $db->prepare('
                INSERT INTO player_moves 
                    (lobby_ID, user_ID, player_Card_ID, current_Round)
                VALUES 
                    (:lobby_ID, :user_ID, :player_Card_ID, :current_Round)
            ');

            $sqlFirstPlayed->bindParam(':lobby_ID', $lobby_ID);
            $sqlFirstPlayed->bindParam(':user_ID', $user_ID);
            $sqlFirstPlayed->bindParam(':player_Card_ID', $card['player_Card_ID']);
            $sqlFirstPlayed->bindParam(':current_Round', $currentRound);
            $sqlFirstPlayed->execute();

            return true;
        } catch (Exception $err) {
            throw new Exception('Erro ao jogar a primeira carta.' . $err);
        }
    }

    public static function SetNextPlayer($lobby_ID, $user_ID)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                SELECT 
                    user_ID
                FROM lobby_players
                WHERE lobby_ID = :lobby_ID
                ORDER BY user_ID ASC
            ');

            $sql->bindParam(':lobby_ID', $lobby_ID);
            $sql->execute();

            $players = $sql->fetchAll(PDO::FETCH_COLUMN);

            $currentUserIndex = array_search($user_ID, $players);
            $nextUserIndex = ($currentUserIndex + 1) % count($players);

            return $players[$nextUserIndex];
        } catch (Exception) {
            throw new Exception('Erro ao buscar jogador seguinte.');
        }
    }

    public static function UpdateGameTurn($lobby_ID, $nextTurn)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                UPDATE game
                SET current_Player_Turn = :current_Player_Turn
                WHERE lobby_ID = :lobby_ID
            ');

            $sql->bindParam(':current_Player_Turn', $nextTurn);
            $sql->bindParam(':lobby_ID', $lobby_ID);
            $sql->execute();

            return true;
        } catch (Exception $err) {
            throw new Exception('Erro ao atualizar turno.' . $err);
        }
    }

    public static function GetChoosedAttribute($lobby_ID)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                SELECT round_Attribute_ID
                FROM game
                WHERE lobby_ID = :lobby_ID
                ORDER BY game_ID DESC
                LIMIT 1
            ');

            $sql->bindParam(':lobby_ID', $lobby_ID);
            $sql->execute();

            return $sql->fetch(PDO::FETCH_COLUMN);
        } catch (Exception) {
            throw new Exception('Erro ao buscar atributo escolhido.');
        }
    }

    public static function PlayTurn($lobby_ID, $user_ID)
    {
        try {
            $db = Connection::getConnection();

            $choosedAttribute = MatchModel::GetChoosedAttribute($lobby_ID);
            $card = MatchModel::GetCardWithAttributeChoosed($lobby_ID, $user_ID, $choosedAttribute);

            $gameFlow = MatchModel::GetGameState($lobby_ID);
            $currentRound = $gameFlow['current_Round'];

            // Registra a jogada do jogador
            $sqlFirstPlayed = $db->prepare('    
                INSERT INTO player_moves 
                    (lobby_ID, user_ID, player_Card_ID, current_Round)
                VALUES 
                    (:lobby_ID, :user_ID, :player_Card_ID, :current_Round)
            ');

            $sqlFirstPlayed->bindParam(':lobby_ID', $lobby_ID);
            $sqlFirstPlayed->bindParam(':user_ID', $user_ID);
            $sqlFirstPlayed->bindParam(':player_Card_ID', $card['player_Card_ID']);
            $sqlFirstPlayed->bindParam(':current_Round', $currentRound);
            $sqlFirstPlayed->execute();

            return true;
        } catch (Exception $err) {
            throw new Exception('Erro ao jogar a carta.' . $err);
        }
    }

    public static function GetPlayerCards($user_ID)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                SELECT 
                    player_Card_ID
                FROM player_cards
                WHERE user_ID = :user_ID
            ');

            $sql->bindParam(':user_ID', $user_ID);
            $sql->execute();

            return $sql->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception) {
            throw new Exception('Erro ao buscar cartas do jogador.');
        }
    }

    public static function GetAtualCardID($lobby_ID)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                SELECT 
                    card_ID
                FROM player_cards
                WHERE user_ID = :user_ID
                ORDER BY card_Position ASC
                LIMIT 1
            ');

            $sql->bindParam(':user_ID', $lobby_ID);
            $sql->execute();

            return $sql->fetch(PDO::FETCH_COLUMN);
        } catch (Exception $err) {
            throw new Exception('Erro ao buscar a primeira carta.' . $err);
        }
    }

    public static function IncrementRound($lobby_ID)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                UPDATE game
                SET current_Round = current_Round + 1
                WHERE lobby_ID = :lobby_ID
            ');

            $sql->bindParam(':lobby_ID', $lobby_ID);
            $sql->execute();

            return true;
        } catch (Exception) {
            throw new Exception('Erro ao incrementar rodada.');
        }
    }

    public static function DetermineWinner($lobby_ID, $currentRound)
    {
        try {
            $db = Connection::getConnection();

            $getChoosedAttribute = MatchModel::GetChoosedAttribute($lobby_ID);

            // Busca as cartas jogadas no turno atual
            $sql = $db->prepare('
                SELECT 
                    pm.user_ID,
                    u.user_Name,
                    c.card_Name,
                    CASE 
                        WHEN :attribute_ID = 1 THEN c.first_Attribute_Value
                        WHEN :attribute_ID = 2 THEN c.second_Attribute_Value
                        WHEN :attribute_ID = 3 THEN c.third_Attribute_Value
                        WHEN :attribute_ID = 4 THEN c.fourth_Attribute_Value
                        WHEN :attribute_ID = 5 THEN c.fifth_Attribute_Value
                    END AS attribute_Value,
                    pc.card_ID,
                    pm.current_Round
                FROM player_moves pm

                INNER JOIN player_cards pc ON pm.player_card_ID = pc.player_card_ID
                INNER JOIN cards c ON pc.card_ID = c.card_ID
                INNER JOIN users u ON pm.user_ID = u.user_ID

                WHERE pm.lobby_ID = :lobby_ID
                AND pm.current_Round = :current_Round
            ');

            $sql->bindParam(':lobby_ID', $lobby_ID);
            $sql->bindParam(':current_Round', $currentRound);
            $sql->bindValue(':attribute_ID', $getChoosedAttribute, PDO::PARAM_INT); // Adiciona a variável do atributo escolhido

            $sql->execute();

            $results = $sql->fetchAll();

            $winner = null;
            $isDraw = false;

            foreach ($results as $result) {
                // echo "Carta: {$result['card_Name']} - Valor do Atributo: {$result['attribute_Value']}\n";
                if ($winner === null || $result['attribute_Value'] > $winner['attribute_Value']) {
                    $winner = $result;
                    $isDraw = false;
                } elseif ($result['attribute_Value'] === $winner['attribute_Value']) {
                    $isDraw = true;
                }
            }

            if ($isDraw) {
                return [
                    'status' => 'empate',
                    'message' => 'Empate! As cartas permanecem com os jogadores empatados.',
                    'errors' => ''
                ];
            }

            $sqlUpdateWinner = $db->prepare('
                UPDATE game
                SET round_Winner = :round_Winner
                WHERE lobby_ID = :lobby_ID
                ORDER BY game_ID DESC
                LIMIT 1
            ');

            $sqlUpdateWinner->bindParam(':round_Winner', $winner['user_ID']);
            $sqlUpdateWinner->bindParam(':lobby_ID', $lobby_ID);
            $sqlUpdateWinner->execute();

            return [
                'user_Name' => $winner['user_Name'],
                'card_Name' => $winner['card_Name'],
                'round' => $currentRound
            ];
        } catch (Exception $err) {
            throw new Exception('Erro ao determinar o vencedor.' . $err);
        }
    }

    public static function GetRoundWinner($lobby_ID)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                SELECT 
                    g.round_Winner,
                    u.user_name as round_Winner_User_Name
                FROM game g

                INNER JOIN users u ON g.round_Winner = u.user_ID

                WHERE g.lobby_ID = :lobby_ID
            ');

            $sql->bindParam(':lobby_ID', $lobby_ID);
            $sql->execute();

            return $sql->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $err) {
            throw new Exception('Erro ao buscar o vencedor da rodada.' . $err);
        }
    }

    public static function ResetRoundWinner($lobby_ID)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                UPDATE game
                SET round_Winner = NULL
                WHERE lobby_ID = :lobby_ID
            ');

            $sql->bindParam(':lobby_ID', $lobby_ID);
            $sql->execute();

            return true;
        } catch (Exception $err) {
            throw new Exception('Erro ao resetar o vencedor da rodada.' . $err);
        }
    }

    public static function TransferCardsToWinner($lobby_ID, $winner_ID)
    {
        try {
            $db = Connection::getConnection();

            // Obtem todas as cartas jogadas da rodada
            $sqlPlayedCards = $db->prepare('
                SELECT 
                    pc.player_Card_ID
                FROM player_moves pm

                INNER JOIN player_cards pc ON pm.player_Card_ID = pc.player_Card_ID

                WHERE pm.lobby_ID = :lobby_ID
                    AND pm.move_ID IN (
                        SELECT MAX(move_ID)
                        FROM player_moves
                        WHERE lobby_ID = :lobby_ID
                        GROUP BY user_ID
                    )
            ');

            $sqlPlayedCards->bindParam(':lobby_ID', $lobby_ID);
            $sqlPlayedCards->execute();
            $playedCards = $sqlPlayedCards->fetchAll();

            // Obtem o ID do jogador vencedor
            $sqlWinner = $db->prepare('
                SELECT user_ID
                FROM lobby_players
                WHERE user_ID = :user_ID 
                    AND lobby_ID = :lobby_ID
            ');

            $sqlWinner->bindParam(':user_ID', $winner_ID);
            $sqlWinner->bindParam(':lobby_ID', $lobby_ID);
            $sqlWinner->execute();

            $winner = $sqlWinner->fetch();
            $winnerLobbyPlayerID = $winner['user_ID'];

            // Verfica a ultima posição atual do baralo do vencedor
            $sqlLastPosition = $db->prepare('
                SELECT MAX(card_Position) as last_position
                FROM player_cards
                WHERE user_ID = :winner_user_ID
            ');

            $sqlLastPosition->bindParam(':winner_user_ID', $winnerLobbyPlayerID);
            $sqlLastPosition->execute();

            $lastPosition = $sqlLastPosition->fetch();
            $newPosition = ($lastPosition['last_position'] ?? 0) + 1;

            // Atualiza carta jogada para transferir ao vencedor
            foreach ($playedCards as $card) {
                $sqlTransfer = $db->prepare('
                    UPDATE player_cards
                    SET 
                        user_ID = :winner_user_ID, 
                        user_ID = :winner_user_ID,
                        card_Position = :new_position

                    WHERE player_Card_ID = :player_Card_ID
                ');

                $sqlTransfer->bindParam(':winner_user_ID', $winner_ID);
                $sqlTransfer->bindParam(':winner_user_ID', $winnerLobbyPlayerID);
                $sqlTransfer->bindParam(':new_position', $newPosition);
                $sqlTransfer->bindParam(':player_Card_ID', $card['player_Card_ID']);
                $sqlTransfer->execute();

                $newPosition++;
            }

            return true;
        } catch (Exception $err) {
            throw new Exception('Erro ao transferir a carta ao vencedor.' . $err);
        }
    }
}
