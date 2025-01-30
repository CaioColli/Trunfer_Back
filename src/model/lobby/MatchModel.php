<?php

namespace model\lobby;

use App\Model\Connection;
use Exception;
use PDO;

class MatchModel
{
    public static function CheckDistributedCards($lobby_ID)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                SELECT
                    pc.player_card_ID            
                FROM player_cards pc

                INNER JOIN lobby_players lp ON pc.lobby_player_ID = lp.lobby_player_ID

                WHERE lp.lobby_ID = :lobby_ID 
            ');

            $sql->bindParam(':lobby_ID', $lobby_ID);
            $sql->execute();

            return $sql->fetchAll();
        } catch (Exception) {
            throw new Exception('Erro ao verificar cartas distribuidas.');
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

            if (empty($cards)) {
                throw new Exception('Não há cartas para distribuir.');
            }

            $lobbyPlayers = LobbyModel::GetTotalPlayersLobby($lobby_ID);

            $totalPlayers = count($lobbyPlayers);
            $totalCards = count($cards);
            $cardsPerPlayer = floor($totalCards / $totalPlayers);

            // Embaralhar as cartas
            shuffle($cards);

            $cardIndex = 0;

            // Atribui cartas
            $sqlAssignCardToPlayer = $db->prepare('
                INSERT INTO 
                    player_cards (user_ID, card_ID, lobby_Player_ID, card_Position)
                VALUES 
                    (:user_ID, :card_ID, :lobby_Player_ID, :card_Position)
            ');


            foreach ($lobbyPlayers as $player) {
                $cardPosition = 0;

                for ($i = 0; $i < $cardsPerPlayer; $i++) {
                    $cardPosition++;

                    $sqlAssignCardToPlayer->bindValue(':user_ID', $player['user_ID']);
                    $sqlAssignCardToPlayer->bindValue(':card_ID', $cards[$cardIndex]['card_ID']);
                    $sqlAssignCardToPlayer->bindValue(':lobby_Player_ID', $player['lobby_Player_ID']);
                    $sqlAssignCardToPlayer->bindValue(':card_Position', $cardPosition);

                    $sqlAssignCardToPlayer->execute();

                    $cardIndex++;
                }
            }

            // Atualiza o round e turno
            $sqlGameFlow = $db->prepare('
                INSERT INTO game_flow 
                    (lobby_ID, current_Round, current_Turn)
                VALUE 
                    (:lobby_ID, 1, :current_Turn)
            ');

            $sqlGameFlow->bindParam(':lobby_ID', $lobby_ID);
            $sqlGameFlow->bindValue(':current_Turn', (int)$user_ID);
            $sqlGameFlow->execute();

            return true;
        } catch (Exception $err) {
            throw new Exception('Erro ao distribuir cartas. ' . $err->getMessage());
        }
    }

    public static function GetGameFlow($lobby_ID)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                SELECT 
                    current_Round,
                    current_Turn
                FROM game_flow
                WHERE lobby_ID = :lobby_ID

                ORDER BY game_flow_ID DESC
                LIMIT 1
            ');

            $sql->bindParam(':lobby_ID', $lobby_ID);
            $sql->execute();

            return $sql->fetch(PDO::FETCH_ASSOC);
        } catch (Exception) {
            throw new Exception('Erro ao verificar status do jogo.');
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

                INNER JOIN lobby_players lp ON player_cards.lobby_Player_ID = lp.lobby_Player_ID

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
                INSERT INTO game_state 
                    (lobby_ID, attribute_ID)
                VALUES 
                    (:lobby_ID, :attribute_ID)

                ON DUPLICATE KEY UPDATE 
                    attribute_ID = :attribute_ID
            ');

            $sqlStateGame->bindParam(':lobby_ID', $lobby_ID);
            $sqlStateGame->bindParam(':current_Turn', $user_ID);
            $sqlStateGame->bindParam(':attribute_ID', $attribute_ID);
            $sqlStateGame->execute();

            $gameFlow = MatchModel::GetGameFlow($lobby_ID);
            $currentRound = $gameFlow['current_Round'];

            $card = MatchModel::GetCardWithAttributeChoosed($lobby_ID, $user_ID);

            // Registra a jogada do primeiro jogador
            $sqlFirstPlayed = $db->prepare('
                INSERT INTO player_moves 
                    (lobby_ID, user_ID, player_Card_ID, round)
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
                ORDER BY lobby_player_ID ASC
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

    public static function UpdateGameFlow($lobby_ID, $nextTurn)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                UPDATE game_flow
                SET current_Turn = :current_Turn
                WHERE lobby_ID = :lobby_ID
            ');

            $sql->bindParam(':current_Turn', $nextTurn);
            $sql->bindParam(':lobby_ID', $lobby_ID);
            $sql->execute();

            return true;
        } catch (Exception) {
            throw new Exception('Erro ao atualizar turno.');
        }
    }

    /** **/
    public static function GetChoosedAttribute($lobby_ID)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                SELECT attribute_ID
                FROM game_state
                WHERE lobby_ID = :lobby_ID
                ORDER BY game_state_ID DESC
                LIMIT 1
            ');

            $sql->bindParam(':lobby_ID', $lobby_ID);
            $sql->execute();

            return $sql->fetch();
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

            $gameFlow = MatchModel::GetGameFlow($lobby_ID);
            $currentRound = $gameFlow['current_Round'];

            // Registra a jogada do jogador
            $sqlFirstPlayed = $db->prepare('
                INSERT INTO player_moves 
                    (lobby_ID, user_ID, player_Card_ID, round)
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
    /** **/

    public static function GetRemainingPlayers($lobby_ID, $currentRound)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                SELECT 
                    lp.user_ID
                FROM lobby_players lp
                WHERE lp.lobby_ID = :lobby_ID
                AND user_ID NOT IN (
                    SELECT pm.user_ID
                    FROM player_moves pm
                    WHERE pm.lobby_ID = :lobby_ID
                    AND pm.round = :current_Round
                )
            ');

            $sql->bindParam(':lobby_ID', $lobby_ID);
            $sql->bindParam(':current_Round', $currentRound);
            $sql->execute();

            return $sql->fetchAll();
        } catch (Exception) {
            throw new Exception('Erro ao buscar jogadores restantes.');
        }
    }

    public static function DetermineWinner($lobby_ID)
    {
        try {
            $db = Connection::getConnection();

            // Recupera o atributo escolhido
            $sqlAttribute = $db->prepare('
                SELECT attribute_ID
                FROM game_state
                WHERE lobby_ID = :lobby_ID
                ORDER BY game_state_ID DESC
                LIMIT 1
            ');

            $sqlAttribute->bindParam(':lobby_ID', $lobby_ID);
            $sqlAttribute->execute();

            $attribute = $sqlAttribute->fetch();

            if (!$attribute || !$attribute['attribute_ID']) {
                throw new Exception('O atributo ainda não foi escolhido pelo primeiro jogador.');
            }

            $attribute_ID = $attribute['attribute_ID'];

            // Busca as cartas jogadas no turno atual
            $sqlCompareValues = $db->prepare('
                SELECT 
                    u.user_ID,
                    u.user_Name,
                    l.letter_Name,
                    la.attribute_Value,
                    pl.letter_ID
                FROM player_moves pm

                INNER JOIN player_letters pl ON pm.player_letter_ID = pl.player_letter_ID
                INNER JOIN letters l ON pl.letter_ID = l.letter_ID
                INNER JOIN letter_attributes la ON l.letter_ID = la.letter_ID
                INNER JOIN users u ON pm.user_ID = u.user_ID

                WHERE pm.lobby_ID = :lobby_ID
                AND la.attribute_ID = :attribute_ID
                AND pm.move_ID IN (
                    SELECT MAX(move_ID)
                    FROM player_moves
                    WHERE lobby_ID = :lobby_ID
                    GROUP BY user_ID
                )
            ');

            $sqlCompareValues->bindParam(':lobby_ID', $lobby_ID);
            $sqlCompareValues->bindParam(':attribute_ID', $attribute_ID);
            $sqlCompareValues->execute();

            $results = $sqlCompareValues->fetchAll();

            // A quantidade deve ser igual a quantidade de jogadores na partida.
            if (count($results) < 2) {
                throw new Exception('Ainda não há cartas suficientes para comparar.');
            }

            $winner = null;
            $isDraw = false;

            foreach ($results as $result) {
                echo "Carta: {$result['letter_Name']} - Valor do Atributo: {$result['attribute_Value']}\n";
                if ($winner === null || $result['attribute_Value'] > $winner['attribute_Value']) {
                    $winner = $result;
                    $isDraw = false;
                } elseif ($result['attribute_Value'] === $winner['attribute_Value']) {
                    $isDraw = true;
                }
            }

            if ($isDraw) {
                return [
                    'message' => 'Empate! As cartas permanecem com os jogadores empatados.'
                ];
            }

            return [
                'winner_user_id' => $winner['user_ID'],
                'winner_user_name' => $winner['user_Name'],
                'winner_letter_name' => $winner['letter_Name'],
                'winner_letter_ID' => $winner['letter_ID']
            ];
        } catch (Exception $err) {
            throw $err;
        }
    }

    public static function IncrementRound($lobby_ID)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                UPDATE game_flow
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

    // PAREI AQUI //

    public static function TransferCardsToWinner($lobby_ID, $winner_ID)
    {
        try {
            $db = Connection::getConnection();

            // Obtem todas as cartas jogadas da rodada
            $sqlPlayedCards = $db->prepare('
                SELECT pl.player_letter_ID
                FROM player_moves pm
            
                INNER JOIN player_letters pl ON pm.player_letter_ID = pl.player_letter_ID

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

            if (empty($playedCards)) {
                throw new Exception('Nenhuma carta foi jogada na rodada.');
            }

            // Obtem o ID do jogador vencedor
            $sqlWinner = $db->prepare('
                SELECT lobby_player_ID
                FROM lobby_players
                WHERE user_ID = :user_ID AND lobby_ID = :lobby_ID
            ');

            $sqlWinner->bindParam(':user_ID', $winner_ID);
            $sqlWinner->bindParam(':lobby_ID', $lobby_ID);
            $sqlWinner->execute();

            $winner = $sqlWinner->fetch();

            if (!$winner) {
                throw new Exception('O jogador vencedor não foi encontrado no lobby.');
            }

            $winnerLobbyPlayerID = $winner['lobby_player_ID'];

            // Verfica a ultima posição atual do baralo do vencedor
            $sqlLastPosition = $db->prepare('
                SELECT MAX(position) as last_position
                FROM player_letters
                WHERE lobby_player_ID = :winner_lobby_player_ID
            ');

            $sqlLastPosition->bindParam(':winner_lobby_player_ID', $winnerLobbyPlayerID);
            $sqlLastPosition->execute();

            $lastPosition = $sqlLastPosition->fetch();
            $newPosition = ($lastPosition['last_position'] ?? 0) + 1;

            // Atualiza carta jogada para transferir ao vencedor
            foreach ($playedCards as $card) {

                if (!$card) {
                    throw new Exception('Carta inválida ou já transferida.');
                }

                $sqlTransfer = $db->prepare('
                    UPDATE player_letters
                    SET 
                        user_ID = :winner_user_ID, 
                        lobby_player_ID = :winner_lobby_player_ID,
                        position = :new_position

                    WHERE player_letter_ID = :player_letter_ID
                ');

                $sqlTransfer->bindParam(':winner_user_ID', $winner_ID);
                $sqlTransfer->bindParam(':winner_lobby_player_ID', $winnerLobbyPlayerID);
                $sqlTransfer->bindParam(':new_position', $newPosition);
                $sqlTransfer->bindParam(':player_letter_ID', $card['player_letter_ID']);
                $sqlTransfer->execute();

                $newPosition++;
            }

            return ['message' => 'Cartas transferidas para o vencedor.'];
        } catch (Exception $err) {
            throw $err;
        }
    }
}
