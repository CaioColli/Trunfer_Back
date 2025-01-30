<?php

namespace model\lobby;

use App\Model\Connection;
use Exception;

class LobbyModel
{
    //Cria o lobby e já insere o host no lobby_players
    public static function CreateLobby($lobby_Name, $host_User_ID, $deck_ID)
    {
        $db = Connection::getConnection();

        try {
            $sql = $db->prepare('
                INSERT INTO lobbies
                    (lobby_Name, lobby_Status, lobby_Available, host_user_ID, deck_ID)
                VALUES
                    (:lobby_Name, "Aguardando", 1, :host_user_ID, :deck_ID)
            ');

            $sql->bindParam(':lobby_Name', $lobby_Name);
            $sql->bindParam(':host_user_ID', $host_User_ID);
            $sql->bindParam(':deck_ID', $deck_ID);

            $sql->execute();
            $lobby_ID = $db->lastInsertId();

            // Insere Host no lobby
            $sqlAddHostToLobby = $db->prepare('
                INSERT INTO lobby_players (lobby_ID, user_ID)
                VALUES (:lobby_ID, :user_ID)
            ');
            $sqlAddHostToLobby->bindParam(':lobby_ID', $lobby_ID);
            $sqlAddHostToLobby->bindParam(':user_ID', $host_User_ID);
            $sqlAddHostToLobby->execute();

            return $lobby_ID;
        } catch (Exception) {
            throw new Exception("Erro ao criar lobby");
        }
    }

    public static function GetExistingLobby($lobby_ID)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                SELECT lobby_ID
                FROM lobbies
                WHERE lobby_ID = :lobby_ID
            ');

            $sql->bindParam(':lobby_ID', $lobby_ID);
            $sql->execute();

            return $sql->fetch();
        } catch (Exception) {
            throw new Exception("Erro ao obter lobby");
        }
    }

    public static function GetLobbyStatus($lobby_ID)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                SELECT
                    lobby_Status
                FROM lobbies
                WHERE lobby_ID = :lobby_ID
            ');

            $sql->bindParam(':lobby_ID', $lobby_ID);
            $sql->execute();

            return $sql->fetch();
        } catch (Exception) {
            throw new Exception("Erro ao obter os status do lobby");
        }
    }

    public static function GetLobby($lobby_ID)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                SELECT
                    l.lobby_ID,
                    l.lobby_Name,
                    l.lobby_Status,
                    l.lobby_Available,
                    d.deck_ID,
                    d.deck_Name,
                    u.user_Name AS host_user_Name
                FROM lobbies l

                INNER JOIN users u ON l.host_user_ID = u.user_ID
                INNER JOIN decks d ON l.deck_ID = d.deck_ID

                WHERE lobby_ID = :lobby_ID
            ');

            $sql->bindParam(':lobby_ID', $lobby_ID);
            $sql->execute();

            $lobbyData = $sql->fetch();

            $response = [];

            $playersInLobby = LobbyModel::GetLobbyPlayers($lobbyData['lobby_ID']);

            $response = [
                'lobby_ID' => (int)$lobbyData['lobby_ID'],
                'lobby_Name' => $lobbyData['lobby_Name'],
                'lobby_Status' => $lobbyData['lobby_Status'],
                'lobby_Available' => $lobbyData['lobby_Available'],
                'deck_Name' => $lobbyData['deck_Name'],
                'lobby_Host_Name' => $lobbyData['host_user_Name'],
                'lobby_Players' => $playersInLobby
            ];

            return $response;
        } catch (Exception) {
            throw new Exception("Erro ao obter lobby");
        }
    }

    public static function GetLobbys()
    {
        try {
            $db  = Connection::getConnection();

            $sqlLobby = $db->prepare('
                SELECT
                    l.lobby_ID,
                    l.lobby_Name,
                    l.lobby_Status,
                    l.lobby_Available,
                    d.deck_Name,
                    u.user_Name AS host_user_Name
                FROM lobbies l

                INNER JOIN users u ON l.host_user_ID = u.user_ID
                INNER JOIN decks d ON l.deck_ID = d.deck_ID
            ');

            $sqlLobby->execute();
            $lobbies = $sqlLobby->fetchAll();

            $response = [];

            foreach ($lobbies as $lobby) {
                $playersInLobby = LobbyModel::GetLobbyPlayers($lobby['lobby_ID']);

                $lobbyData = [
                    'lobby_ID' => (int)$lobby['lobby_ID'],
                    'lobby_Name' => $lobby['lobby_Name'],
                    'lobby_Status' => $lobby['lobby_Status'],
                    'lobby_Available' => $lobby['lobby_Available'],
                    'deck_Name' => $lobby['deck_Name'],
                    'lobby_Host_Name' => $lobby['host_user_Name'],
                    'lobby_Players' => $playersInLobby
                ];

                $response[] = $lobbyData;
            }
            return $response;
        } catch (Exception) {
            throw new Exception('Erro ao tentar obter lobbies.');
        }
    }

    public static function GetTotalPlayersLobby($lobby_ID)
    {
        try {
            $sql = Connection::getConnection()->prepare('
                SELECT 
                    user_ID,
                    lobby_Player_ID
                FROM lobby_players
                WHERE lobby_ID = :lobby_ID
            ');

            $sql->bindParam(':lobby_ID', $lobby_ID);
            $sql->execute();

            return $sql->fetchAll();
        } catch (Exception) {
            throw new Exception('Erro ao tentar obter todos jogadores do lobby.');
        }
    }

    public static function GetLobbyPlayers($lobby_ID)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                SELECT
                    u.user_Name
                FROM lobby_players lp
                    
                INNER JOIN users u ON lp.user_ID = u.user_ID
                WHERE lp.lobby_ID = :lobby_ID
            ');

            $sql->bindParam(':lobby_ID', $lobby_ID);
            $sql->execute();

            return $sql->fetchAll();
        } catch (Exception) {
            throw new Exception('Erro ao tentar obter jogadores do lobby.');
        }
    }

    public static function VerifyPlayerInLobby($user_ID)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                SELECT lobby_ID
                FROM lobby_players
                WHERE user_ID = :user_ID
            ');

            $sql->bindParam(':user_ID', $user_ID);
            $sql->execute();

            return $sql->fetch();
        } catch (Exception) {
            throw new Exception('Erro ao tentar verificar se jogadores está no lobby.');
        }
    }

    public static function JoinLoby($user_ID, $lobby_ID)
    {
        try {
            $db = Connection::getConnection();

            $sqlAddToLobby = $db->prepare('
                INSERT INTO lobby_players (lobby_ID, user_ID)
                VALUES (:lobby_ID, :user_ID)
            ');

            $sqlAddToLobby->bindParam(':lobby_ID', $lobby_ID);
            $sqlAddToLobby->bindParam(':user_ID', $user_ID);
            $sqlAddToLobby->execute();

            return true;
        } catch (Exception) {
            throw new Exception('Erro ao tentar entrar no lobby.');
        }
    }

    public static function GetLobbyHost($lobby_ID)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                SELECT host_user_ID
                FROM lobbies
                WHERE lobby_ID = :lobby_ID
            ');

            $sql->bindParam(':lobby_ID', $lobby_ID);
            $sql->execute();

            $result = $sql->fetch();

            return $result['host_user_ID']; // Retorna o Host
        } catch (Exception) {
            throw new Exception('Erro ao verificar o host do lobby.');
        }
    }

    // Remove jogador do lobby e se o lobby ficar vazio, lobby é apagado.
    public static function RemovePlayer($user_ID, $lobby_ID)
    {
        try {
            $db = Connection::getConnection();

            // Remove o player do lobby
            $sqlRemove = $db->prepare('
                DELETE FROM lobby_players
                WHERE user_ID = :user_ID AND lobby_ID = :lobby_ID
            ');
            $sqlRemove->bindParam(':user_ID', $user_ID);
            $sqlRemove->bindParam(':lobby_ID', $lobby_ID);
            $sqlRemove->execute();

            // Verifica o proximo jogador do lobby
            $sqlNextPlayer = $db->prepare('
                SELECT user_ID
                FROM lobby_players
                WHERE lobby_ID = :lobby_ID
                    ORDER BY lobby_Player_ID ASC
                LIMIT 1
            ');

            $sqlNextPlayer->bindParam(':lobby_ID', $lobby_ID);
            $sqlNextPlayer->execute();

            $nextPlayer = $sqlNextPlayer->fetch();

            $isHost = LobbyModel::GetLobbyHost($lobby_ID);

            if ($isHost) {
                if (!$nextPlayer) {
                    // Se o lobby ficar vazio, apaga o lobby
                    $sqlDelete = $db->prepare('
                        DELETE FROM lobbies
                        WHERE lobby_ID = :lobby_ID
                    ');

                    $sqlDelete->bindParam(':lobby_ID', $lobby_ID);
                    $sqlDelete->execute();
                } else {
                    $sqlUpdateHost = $db->prepare('
                        UPDATE lobbies
                        SET host_User_ID = :new_Host_User_ID
                        WHERE lobby_ID = :lobby_ID
                    ');

                    $sqlUpdateHost->bindParam(':new_Host_User_ID', $nextPlayer['user_ID']);
                    $sqlUpdateHost->bindParam(':lobby_ID', $lobby_ID);
                    $sqlUpdateHost->execute();
                }
            }

            return true;
        } catch (Exception) {
            throw new Exception('Erro tentar remover jogador ou sair do lobby');
        }
    }

    public static function EditLobby($lobby_ID, $lobby_Name, $lobby_Available, $deck_ID)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                UPDATE lobbies
                SET 
                    lobby_Name = :lobby_Name,
                    lobby_Available = :lobby_Available,
                    deck_ID = :deck_ID
                WHERE lobby_ID = :lobby_ID
            ');

            $sql->bindParam(':lobby_ID', $lobby_ID);
            $sql->bindParam(':lobby_Name', $lobby_Name);
            $sql->bindParam(':lobby_Available', $lobby_Available);
            $sql->bindParam(':deck_ID', $deck_ID);
            $sql->execute();

            $sql->execute();

            return true;
        } catch (Exception) {
            throw new Exception('Erro ao editar o lobby.');
        }
    }

    public static function DeleteLobby($lobby_ID)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                DELETE FROM lobbies
                WHERE lobby_ID = :lobby_ID
            ');

            $sql->bindParam(':lobby_ID', $lobby_ID);
            $sql->execute();

            return true;
        } catch (Exception $err) {
            throw $err;
        }
    }

    public static function StartLobby($lobby_ID)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                UPDATE lobbies
                SET
                    lobby_Status = "Em Jogo",
                    lobby_Available = 0
                WHERE
                    lobby_ID = :lobby_ID
            ');

            $sql->bindParam(':lobby_ID', $lobby_ID);
            $sql->execute();

            return true;
        } catch (Exception) {
            throw new Exception('Erro ao iniciar o lobby.');
        }
    }

    public function FinishLobby($lobby_ID)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                UPDATE lobbies
                SET
                    lobby_Status = "Aguardando",
                    lobby_Available = 1
                WHERE
                    lobby_ID = :lobby_ID
            ');

            $sql->bindParam(':lobby_ID', $lobby_ID);
            $sql->execute();

            return true;
        } catch (Exception) {
            throw new Exception('Erro ao finalizar o lobby.');
        }
    }

    //--//--//--//--//--//--//--//--//--//

    // public function StartMatch($lobby_ID)
    // {
    //     try {
    //         $db = Connection::getConnection();

    //         $sqlPlayers = $db->prepare('
    //             SELECT lp.user_ID, u.user_Name
    //             FROM lobby_players lp
    //             INNER JOIN users u ON lp.user_ID = u.user_ID
    //             WHERE lp.lobby_ID = :lobby_ID
    //         ');

    //         $sqlPlayers->bindParam(':lobby_ID', $lobby_ID);
    //         $sqlPlayers->execute();

    //         return $sqlPlayers->fetchAll();
    //     } catch (Exception $err) {
    //         throw $err;
    //     }
    // }

    // public function DistributeCardsToPlayers($lobby_ID)
    // {
    //     try {
    //         $db = Connection::getConnection();

    //         // Verifica se já foi distribuido cartas no lobby
    //         $sqlCheckLetters = $db->prepare('
    //             SELECT
    //                 pl.player_letter_ID
    //             FROM player_letters pl

    //             INNER JOIN lobby_players lp ON pl.lobby_player_ID = lp.lobby_player_ID

    //             WHERE lobby_ID = :lobby_ID
    //         ');

    //         $sqlCheckLetters->bindParam(':lobby_ID', $lobby_ID);
    //         $sqlCheckLetters->execute();

    //         $checkLettersResult = $sqlCheckLetters->fetchAll();

    //         if (count($checkLettersResult) > 0) {
    //             throw new Exception('As cartas já foram distribuidas.');
    //         }

    //         // Obter o ID do deck associado ao lobby
    //         $sqlLobby = $db->prepare('
    //             SELECT deck_ID
    //             FROM lobbies
    //             WHERE lobby_ID = :lobby_ID
    //         ');

    //         $sqlLobby->bindParam(':lobby_ID', $lobby_ID);
    //         $sqlLobby->execute();

    //         $lobby = $sqlLobby->fetch();

    //         if (!$lobby) {
    //             throw new Exception('Lobby não encontrado.');
    //         }

    //         $deck_ID = $lobby['deck_ID'];

    //         // Obter as cartas do deck
    //         $sqlletters = $db->prepare('
    //             SELECT letter_ID, letter_Name
    //             FROM letters
    //             WHERE deck_ID = :deck_ID
    //         ');

    //         $sqlletters->bindParam(':deck_ID', $deck_ID);
    //         $sqlletters->execute();

    //         $letters = $sqlletters->fetchAll();

    //         if (empty($letters)) {
    //             throw new Exception('Deck sem cartas.');
    //         }

    //         // Obter os jogadores no lobby
    //         $sqlPlayers = $db->prepare('
    //         SELECT lp.lobby_player_ID, lp.user_ID
    //         FROM lobby_players lp
    //         WHERE lp.lobby_ID = :lobby_ID
    //         ');

    //         $sqlPlayers->bindParam(':lobby_ID', $lobby_ID);
    //         $sqlPlayers->execute();

    //         $players = $sqlPlayers->fetchAll();

    //         if (count($players) < 2) {
    //             throw new Exception('Jogadores insuficientes.');
    //         }

    //         $playerCount = count($players);
    //         $cardCount = count($letters);

    //         $cardsPerPlayer = floor($cardCount / $playerCount);

    //         if ($cardsPerPlayer === 0) {
    //             throw new Exception('Não há cartas suficientes para dividir as cartas entre os jogadores.');
    //         }

    //         // Embaralhar as cartas
    //         shuffle($letters);

    //         $cardIndex = 0;

    //         // Consulta para atribuir cartas
    //         $sqlAssignLetters = $db->prepare('
    //             INSERT INTO player_letters (user_ID, letter_ID, lobby_player_ID, position)
    //             VALUES (:user_ID, :letter_ID, :lobby_player_ID, :position)
    //         ');


    //         foreach ($players as $player) {
    //             $position = 0;

    //             for ($i = 0; $i < $cardsPerPlayer; $i++) {
    //                 if (!isset($player['user_ID']) || !isset($letters[$cardIndex]['letter_ID'])) {
    //                     throw new Exception("Dados insuficientes para distribuir as cartas.");
    //                 }

    //                 $position++;

    //                 $sqlAssignLetters->bindValue(':user_ID', $player['user_ID']);
    //                 $sqlAssignLetters->bindValue(':letter_ID', $letters[$cardIndex]['letter_ID']);
    //                 $sqlAssignLetters->bindValue(':lobby_player_ID', $player['lobby_player_ID']);
    //                 $sqlAssignLetters->bindValue(':position', $position);

    //                 $sqlAssignLetters->execute();

    //                 $cardIndex++;
    //             }
    //         }

    //         return true;
    //     } catch (Exception $err) {
    //         throw $err;
    //     }
    // }

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
