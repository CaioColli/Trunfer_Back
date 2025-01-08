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

    public function GetLobbyPlayers($lobby_ID)
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
}
