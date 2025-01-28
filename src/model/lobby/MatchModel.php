<?php

namespace model\lobby;

use App\Model\Connection;
use Exception;


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

    public static function DistributeCardsToPlayers($lobby_ID)
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

            return true;
        } catch (Exception $err) {
            throw new Exception('Erro ao distribuir cartas. ' . $err->getMessage());
        }
    }

    // PAREI AQUI //

    public static function PlayFirstCard($lobby_ID, $user_ID, $attribute_ID)
    {
        try {
            $db = Connection::getConnection();

            // Registra atributo escolhido na tabela
            $sqlStateGame = $db->prepare('
                INSERT INTO game_state (lobby_ID, current_turn, attribute_ID)
                VALUES (:lobby_ID, :current_turn, :attribute_ID)

                ON DUPLICATE KEY UPDATE 
                    current_turn = :current_turn, 
                    attribute_ID = :attribute_ID
            ');

            $sqlStateGame->bindParam(':lobby_ID', $lobby_ID);
            $sqlStateGame->bindParam(':current_turn', $user_ID);
            $sqlStateGame->bindParam(':attribute_ID', $attribute_ID);
            $sqlStateGame->execute();

            // Seleciona a carta do jogador com o atributo correspondente
            $sqlGetCard = $db->prepare('
                SELECT pl.player_letter_ID, l.letter_Name, la.attribute_Value, pl.letter_ID
                FROM player_letters pl

                INNER JOIN letters l ON pl.letter_ID = l.letter_ID
                INNER JOIN lobby_players lp ON pl.lobby_player_ID = lp.lobby_player_ID
                INNER JOIN letter_attributes la ON l.letter_ID = la.letter_ID

                WHERE lp.lobby_ID = :lobby_ID 
                    AND lp.user_ID = :user_ID
                    AND la.attribute_ID = :attribute_ID
                ORDER BY pl.position ASC
                LIMIT 1
            ');

            $sqlGetCard->bindParam(':lobby_ID', $lobby_ID);
            $sqlGetCard->bindParam(':user_ID', $user_ID);
            $sqlGetCard->bindParam(':attribute_ID', $attribute_ID);
            $sqlGetCard->execute();

            $card = $sqlGetCard->fetch();

            if (!$card) {
                throw new Exception('Nenhuma carta disponível para o jogador.');
            }

            // Registra a jogada do primeiro jogador
            $sqlFirstPlayed = $db->prepare('
                INSERT INTO player_moves (lobby_ID, user_ID, player_letter_ID)
                VALUES (:lobby_ID, :user_ID, :player_letter_ID)
            ');

            $sqlFirstPlayed->bindParam(':lobby_ID', $lobby_ID);
            $sqlFirstPlayed->bindParam(':user_ID', $user_ID);
            $sqlFirstPlayed->bindParam(':player_letter_ID', $card['player_letter_ID']);
            $sqlFirstPlayed->execute();

            return [
                'message' => 'Primeira jogada registrada com sucesso.',
                'played_card' => [
                    'player_letter_ID' => $card['player_letter_ID'],
                    'letter_ID' => $card['letter_ID'],
                    'letter_Name' => $card['letter_Name'],
                    'attribute_Value' => $card['attribute_Value']
                ]
            ];
        } catch (Exception $err) {
            throw $err;
        }
    }

    public static function PlayTurn($lobby_ID, $user_ID)
    {
        try {
            $db = Connection::getConnection();

            // Verifica o atributo escolhido
            $sqlStateGame = $db->prepare('
                SELECT attribute_ID
                FROM game_state
                WHERE lobby_ID = :lobby_ID
                ORDER BY game_state_ID DESC
                LIMIT 1
            ');

            $sqlStateGame->bindParam(':lobby_ID', $lobby_ID);
            $sqlStateGame->execute();

            $gameState = $sqlStateGame->fetch();

            if (!$gameState || !$gameState['attribute_ID']) {
                throw new Exception('O atributo ainda não foi escolhido pelo primeiro jogador.');
            }

            // Busca a carta do jogador com o atributo correspondente
            $sqlGetCard = $db->prepare('
                SELECT pl.player_letter_ID, l.letter_Name, la.attribute_Value, pl.letter_ID
                FROM player_letters pl
            
                INNER JOIN letters l ON pl.letter_ID = l.letter_ID
                INNER JOIN lobby_players lp ON pl.lobby_player_ID = lp.lobby_player_ID
                INNER JOIN letter_attributes la ON l.letter_ID = la.letter_ID
            
                WHERE lp.lobby_ID = :lobby_ID 
                AND lp.user_ID = :user_ID
                AND la.attribute_ID = :attribute_ID
                ORDER BY pl.position ASC
                LIMIT 1
            ');

            $sqlGetCard->bindParam(':lobby_ID', $lobby_ID);
            $sqlGetCard->bindParam(':user_ID', $user_ID);
            $sqlGetCard->bindParam(':attribute_ID', $gameState['attribute_ID']);
            $sqlGetCard->execute();

            $card = $sqlGetCard->fetch();

            if (!$card) {
                throw new Exception('Nenhuma carta disponível para jogar.');
            }

            // Registra a jogada do jogador
            $sqlPlayed = $db->prepare('
                INSERT INTO player_moves (lobby_ID, user_ID, player_letter_ID)
                VALUES (:lobby_ID, :user_ID, :player_letter_ID)
            ');

            $sqlPlayed->bindParam(':lobby_ID', $lobby_ID);
            $sqlPlayed->bindParam(':user_ID', $user_ID);
            $sqlPlayed->bindParam(':player_letter_ID', $card['player_letter_ID']);
            $sqlPlayed->execute();

            return [
                'message' => 'Jogada registrada com sucesso.',
                'played_card' => [
                    'player_letter_ID' => $card['player_letter_ID'],
                    'letter_ID' => $card['letter_ID'],
                    'letter_Name' => $card['letter_Name'],
                    'attribute_Value' => $card['attribute_Value']
                ]
            ];
        } catch (Exception $err) {
            throw $err;
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
