<?php

namespace model\lobby;

use App\Model\Connection;
use PDO;
use Exception;

class LobbyModel
{
    //Cria o lobby e já insere o host no lobby_players
    public function CreateLobbyAndAddHost($lobby_Name, $lobby_Is_Available, $host_User_ID, $deck_ID)
    {
        $db = Connection::getConnection();

        try {
            $sql = $db->prepare('
                INSERT INTO lobbies 
                    (lobby_Name, lobby_Status, lobby_Available, host_user_ID, deck_ID)
                VALUES 
                    (:lobby_Name, "Aguardando", :lobby_Available, :host_user_ID, :deck_ID)
            ');

            $sql->bindParam(':lobby_Name', $lobby_Name);
            $sql->bindParam(':lobby_Available', $lobby_Is_Available, PDO::PARAM_BOOL);
            $sql->bindParam(':host_user_ID', $host_User_ID);
            $sql->bindParam(':deck_ID', $deck_ID);

            $sql->execute();
            $lobby_ID = $db->lastInsertId();

            // Insere HOST EM lobby_players
            $sqlAddHostToLobby = $db->prepare('
                INSERT INTO lobby_players (lobby_ID, user_ID)
                VALUES (:lobby_ID, :user_ID)
            ');
            $sqlAddHostToLobby->bindParam(':lobby_ID', $lobby_ID);
            $sqlAddHostToLobby->bindParam(':user_ID', $host_User_ID);
            $sqlAddHostToLobby->execute();

            return $lobby_ID;
        } catch (Exception $err) {
            throw $err;
        }
    }

    public function GetLobby($lobby_ID)
    {
        try {
            $db = Connection::getConnection();

            // Buscar dados do lobby
            $sqlLobby = $db->prepare('
                SELECT 
                    l.lobby_ID,
                    l.lobby_Name,
                    l.lobby_Status,
                    l.lobby_Available,
                    u.user_Name AS host_user_Name,
                    l.host_User_ID,
                    l.deck_ID,
                    d.deck_Name
                FROM lobbies l
                INNER JOIN users u 
                    ON l.host_user_ID = u.user_ID
                INNER JOIN decks d
                    ON l.deck_ID = d.deck_ID
                WHERE l.lobby_ID = :lobby_ID
            ');

            $sqlLobby->bindParam(':lobby_ID', $lobby_ID);
            $sqlLobby->execute();

            $lobbyData = $sqlLobby->fetch(PDO::FETCH_ASSOC);

            // Busca players do lobby
            $sqlPlayers = $db->prepare('
                SELECT lp.user_ID, u.user_Name
                FROM lobby_players lp
                INNER JOIN users u ON lp.user_ID = u.user_ID
                WHERE lp.lobby_ID = :lobby_ID
            ');

            $sqlPlayers->bindParam(':lobby_ID', $lobby_ID);
            $sqlPlayers->execute();

            $players = $sqlPlayers->fetchAll(PDO::FETCH_ASSOC);

            $playerName = array_column($players, 'user_Name');

            $response = [
                'lobby_ID' => (int)$lobbyData['lobby_ID'],
                'lobby_Host_User_ID' => (int)$lobbyData['host_User_ID'],
                'lobby_Host_Name' => $lobbyData['host_user_Name'],
                'lobby_Name' => $lobbyData['lobby_Name'],
                'lobby_Status' => $lobbyData['lobby_Status'],
                'lobby_Available' => (bool)$lobbyData['lobby_Available'],
                'lobby_Players' => $playerName,
                'deck_ID' => (int)$lobbyData['deck_ID'],
                'deck_Name' => $lobbyData['deck_Name'],
            ];

            return $response;
        } catch (Exception $err) {
            throw $err;
        }
    }

    public function GetLobbys()
    {
        try {
            $db  = Connection::getConnection();

            $sqlLobby = $db->prepare('
                SELECT 
                    l.lobby_ID,
                    l.lobby_Name,
                    l.lobby_Status,
                    l.lobby_Available,
                    u.user_Name AS host_user_Name,
                    l.host_User_ID,
                    l.deck_ID,
                    d.deck_Name
                FROM lobbies l
                INNER JOIN users u 
                    ON l.host_user_ID = u.user_ID
                INNER JOIN decks d
                    ON l.deck_ID = d.deck_ID
            ');

            $sqlLobby->execute();

            $allLobbies = $sqlLobby->fetchAll(PDO::FETCH_ASSOC);

            $response = [];

            foreach ($allLobbies as $lobbyRow) {
                $sqlPlayers = $db->prepare('
                    SELECT lp.user_ID, u.user_Name
                    FROM lobby_players lp
                    INNER JOIN users u ON lp.user_ID = u.user_ID
                    WHERE lp.lobby_ID = :lobby_ID
                ');

                $sqlPlayers->bindParam(':lobby_ID', $lobbyRow['lobby_ID']);

                $sqlPlayers->execute();
                $players = $sqlPlayers->fetchAll(PDO::FETCH_ASSOC);

                // Exibe apenas o nome, escondendo o ID
                $playersName = array_column($players, 'user_Name');

                $lobbyData = [
                    'lobby_Host_Name' => $lobbyRow['host_user_Name'],
                    'lobby_Name' => $lobbyRow['lobby_Name'],
                    'lobby_Status' => $lobbyRow['lobby_Status'],
                    'lobby_Available' => (bool)$lobbyRow['lobby_Available'],
                    'lobby_Players' => $playersName,
                    'deck_Name' => $lobbyRow['deck_Name'],
                ];

                $response[] = $lobbyData;
            }
            return $response;
        } catch (Exception $err) {
            throw $err;
        }
    }

    public static function GetLobbyPlayers($lobby_ID)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                SELECT lp.user_ID, u.user_Name
                FROM lobby_players lp
                INNER JOIN users u ON lp.user_ID = u.user_ID
                WHERE lp.lobby_ID = :lobby_ID
            ');

            $sql->bindParam(':lobby_ID', $lobby_ID);
            $sql->execute();

            return $sql->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $err) {
            throw $err;
        }
    }

    public function AddPlayerToLobby($user_ID, $lobby_ID)
    {
        try {
            $db = Connection::getConnection();

            // Verifica se usuário está em outro lobby
            $sqlCheckExistingLobby = $db->prepare('
                SELECT lp.lobby_ID, l.lobby_Name
                FROM lobby_players lp
                INNER JOIN lobbies l ON lp.lobby_ID = l.lobby_ID
                WHERE lp.user_ID = :user_ID
            ');

            $sqlCheckExistingLobby->bindParam(':user_ID', $user_ID);
            $sqlCheckExistingLobby->execute();

            $existingLobby = $sqlCheckExistingLobby->fetch();

            if ($existingLobby) {
                throw new Exception("Você já está no lobby " . $existingLobby['lobby_Name'] . ", Saia do lobby atual para entrar em outro.");
            }

            $sqlAddToLobby = $db->prepare('
                INSERT INTO lobby_players (lobby_ID, user_ID)
                VALUES (:lobby_ID, :user_ID)
            ');

            $sqlAddToLobby->bindParam(':lobby_ID', $lobby_ID);
            $sqlAddToLobby->bindParam(':user_ID', $user_ID);
            $sqlAddToLobby->execute();

            return true;
        } catch (Exception $err) {
            throw $err;
        }
    }

    // Remove jogador do lobby e se o lobby ficar vazio, apaga o lobby.
    public function RemovePlayerFromLobby($user_ID, $lobby_ID)
    {
        try {
            $db = Connection::getConnection();

            $sqlHostCheck = $db->prepare('
                SELECT host_User_ID
                FROM lobbies
                WHERE lobby_ID = :lobby_ID
            ');

            $sqlHostCheck->bindParam(':lobby_ID', $lobby_ID);
            $sqlHostCheck->execute();

            $host = $sqlHostCheck->fetch(PDO::FETCH_ASSOC);

            $isHost = ($host['host_User_ID'] == $user_ID);

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

            $nextPlayer = $sqlNextPlayer->fetch(PDO::FETCH_ASSOC);

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
        } catch (Exception $err) {
            throw $err;
        }
    }

    public function EditLobby($lobby_ID, $lobby_Name, $lobby_Available, $deck_ID)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                UPDATE lobbies
                SET 
                    lobby_Name = :lobby_Name, 
                    lobby_Available = :lobby_Available, 
                    deck_ID = :deck_ID
                WHERE 
                    lobby_ID = :lobby_ID
            ');

            $sql->bindParam(':lobby_ID', $lobby_ID);
            $sql->bindParam(':lobby_Name', $lobby_Name);
            $sql->bindParam(':lobby_Available', $lobby_Available, PDO::PARAM_INT);
            $sql->bindParam(':deck_ID', $deck_ID);
            $sql->execute();

            $sql->execute();

            return true;
        } catch (Exception $err) {
            throw $err;
        }
    }

    public function DeleteLobby($lobby_ID)
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

    //--//--//--//--//--//--//--//--//--//

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
        } catch (Exception $err) {
            throw $err;
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
        } catch (Exception $err) {
            throw $err;
        }
    }

    //--//--//--//--//--//--//--//--//--//

    public function StartMatch($lobby_ID)
    {
        try {
            $db = Connection::getConnection();

            $sqlPlayers = $db->prepare('
                SELECT lp.user_ID, u.user_Name
                FROM lobby_players lp
                INNER JOIN users u ON lp.user_ID = u.user_ID
                WHERE lp.lobby_ID = :lobby_ID
            ');

            $sqlPlayers->bindParam(':lobby_ID', $lobby_ID);
            $sqlPlayers->execute();

            return $sqlPlayers->fetchAll();
        } catch (Exception $err) {
            throw $err;
        }
    }

    public function DistributeCardsToPlayers($lobby_ID)
    {
        try {
            $db = Connection::getConnection();

            // Verifica se já foi distribuido cartas no lobby
            $sqlCheckLetters = $db->prepare('
                SELECT 
                    pl.player_letter_ID
                FROM player_letters pl
                INNER JOIN lobby_players lp ON pl.lobby_player_ID = lp.lobby_player_ID
                WHERE lobby_ID = :lobby_ID
            ');

            $sqlCheckLetters->bindParam(':lobby_ID', $lobby_ID);
            $sqlCheckLetters->execute();

            $checkLettersResult = $sqlCheckLetters->fetchAll();

            if (count($checkLettersResult) > 0) {
                throw new Exception('As cartas já foram distribuidas.');
            }

            // Obter o ID do deck associado ao lobby
            $sqlLobby = $db->prepare('
                SELECT deck_ID
                FROM lobbies
                WHERE lobby_ID = :lobby_ID
            ');

            $sqlLobby->bindParam(':lobby_ID', $lobby_ID);
            $sqlLobby->execute();

            $lobby = $sqlLobby->fetch();

            if (!$lobby) {
                throw new Exception('Lobby não encontrado.');
            }

            $deck_ID = $lobby['deck_ID'];

            // Obter as cartas do deck
            $sqlletters = $db->prepare('
                SELECT letter_ID, letter_Name
                FROM letters
                WHERE deck_ID = :deck_ID
            ');

            $sqlletters->bindParam(':deck_ID', $deck_ID);
            $sqlletters->execute();

            $letters = $sqlletters->fetchAll();

            if (empty($letters)) {
                throw new Exception('Deck sem cartas.');
            }

            // Obter os jogadores no lobby
            $sqlPlayers = $db->prepare('
            SELECT lp.lobby_player_ID, lp.user_ID
            FROM lobby_players lp
            WHERE lp.lobby_ID = :lobby_ID
            ');

            $sqlPlayers->bindParam(':lobby_ID', $lobby_ID);
            $sqlPlayers->execute();

            $players = $sqlPlayers->fetchAll();

            if (count($players) < 2) {
                throw new Exception('Jogadores insuficientes.');
            }

            $playerCount = count($players);
            $cardCount = count($letters);

            if ($playerCount === 0) {
                throw new Exception('Não há jogadores suficientes para dividir as cartas.');
            }

            $cardsPerPlayer = floor($cardCount / $playerCount);

            // Embaralhar as cartas
            shuffle($letters);

            $cardIndex = 0;

            // Consulta para atribuir cartas
            $sqlAssignLetters = $db->prepare('
                INSERT INTO player_letters (user_ID, letter_ID, lobby_player_ID, card_position)
                VALUES (:user_ID, :letter_ID, :lobby_player_ID, :card_position)
            ');


            foreach ($players as $player) {
                $position = 0;

                for ($i = 0; $i < $cardsPerPlayer; $i++) {
                    if (!isset($player['user_ID']) || !isset($letters[$cardIndex]['letter_ID'])) {
                        throw new Exception("Dados insuficientes para distribuir as cartas.");
                    }

                    $position++;

                    $sqlAssignLetters->bindValue(':user_ID', $player['user_ID']);
                    $sqlAssignLetters->bindValue(':letter_ID', $letters[$cardIndex]['letter_ID']);
                    $sqlAssignLetters->bindValue(':lobby_player_ID', $player['lobby_player_ID']);
                    $sqlAssignLetters->bindValue(':card_position', $position);

                    $sqlAssignLetters->execute();

                    $cardIndex++;
                }
            }

            return true;
        } catch (Exception $err) {
            throw $err;
        }
    }

    //

    public function GetCurrentPlayer($lobby_ID)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                SELECT current_turn
                FROM game_state
                WHERE lobby_ID = :lobby_ID
            ');

            $sql->bindParam(':lobby_ID', $lobby_ID);            
            $sql->execute();

            return $sql->fetch();
        } catch (Exception $err) {
            throw $err;
        }
    }

    public function SetAttributeChoice($lobby_ID, $attribute_ID)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                UPDATE game_state
                SET attribute_ID = :attribute_ID
                WHERE lobby_ID = :lobby_ID
            ');

            $sql->bindParam(':attribute_ID', $attribute_ID);
            $sql->bindParam(':lobby_ID', $lobby_ID);
            $sql->execute();
        } catch (Exception $err) {
            throw $err;
        }
    }

    public function PlayRound($lobby_ID)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                SELECT pl.player_letter_ID, pl lobby_player_ID, pl.letter_ID, la.attribute.Value
                FROM player_letters pl
                INNER JOIN letter_attributes la ON pl,letter_ID = la.letter_ID
                INNER JOIN game_state gs ON gs.attribute_ID = la.attribute_ID
                WHERE pl.lobby_ID = :lobby_ID AND pl.card_position = 1
            ');

            $sql->bindParam(':lobby_ID', $lobby_ID);
            $sql->execute();    

            $results = $sql->fetchAll();
            
            if (count($results) < 2) {
                throw new Exception('Fim de jogo, apenas um jogador de cartas.');
            }

            $highestValue = max(array_column($results, 'attribute_Value'));
            $winningCards = array_filter($results, function ($letter) use ($highestValue) {
                return $letter['attribute_Value'] === $highestValue;
            });

            if (count($winningCards) > 1) {
                return ['tie' => true];
            }

            $winner = reset($winningCards);
            $this->UpdateLetter($lobby_ID, $results, $winner['lobby_player_ID']);

            return ['Vencedor' => $winningCards['player_letter_ID']];
        } catch (Exception $err) {
            throw $err;
        }
    }   
    
    public function UpdateLetter($lobby_ID, $letters, $winner_lobby_player_ID)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                SELECT MAX(card_position)
                FROM player_letters
                WHERE lobby_player_ID = :lobby_player_ID
            ');

            $sql->bindParam(':lobby_player_ID', $winner_lobby_player_ID);
            $sql->execute();    

            $results = $sql->fetch();

            if (!$results) {
                $results = 0;
            }

            $position = $results + 1;

            foreach ($letters as $letter) {
                $sql = $db->prepare('
                    UPDATE player_letters
                    SET lobby_player_ID = :winner_lobby_ID, card_position = :card_position
                    WHERE player_letter_ID = :player_letter_ID
                ');

                $sql->bindParam(':player_letter_ID', $letter['player_letter_ID']);
                $sql->bindParam(':card_position', $position);
                $sql->bindParam(':winner_lobby_ID', $winner_lobby_player_ID);
                $sql->execute();

                $position++;
            }

        } catch (Exception $err) {
            throw $err;
        }
    }
}
