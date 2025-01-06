<?php

namespace model\lobby;

use App\Model\Connection;
use PDO;
use Exception;

class LobbyModel
{
    //Cria o lobby e já insere o host no lobby_players
    public function createLobbyAndAddHost($lobby_Name, $lobby_Is_Available, $host_User_ID, $deck_ID)
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
            $sql2 = $db->prepare('
                INSERT INTO lobby_players (lobby_ID, user_ID)
                VALUES (:lobby_ID, :user_ID)
            ');
            $sql2->bindParam(':lobby_ID', $lobby_ID);
            $sql2->bindParam(':user_ID', $host_User_ID);
            $sql2->execute();

            return $lobby_ID;
        } catch (Exception $err) {
            throw $err;
        }
    }

    public function getLobby($lobby_ID)
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
                    l.host_user_ID,
                    u.user_Name AS host_user_Name,
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

            if (!$lobbyData) {
                return null;
            }

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

            $response = [
                'lobby_ID' => (int)$lobbyData['lobby_ID'],
                'lobby_Host_User_ID' => (int)$lobbyData['lobby_Host_User_ID'],
                'lobby_Host_Name' => $lobbyData['host_user_Name'],
                'lobby_Name' => $lobbyData['lobby_Name'],
                'lobby_Status' => $lobbyData['lobby_Status'],
                'lobby_Available' => (bool)$lobbyData['lobby_Available'],
                'lobby_Players' => $players,
                'deck_ID' => (int)$lobbyData['deck_ID'],
                'deck_Name' => $lobbyData['deck_Name'],
            ];

            return $response;
        } catch (Exception $err) {
            throw $err;
        }
    }

    // Remove jogador do lobby e, se o lobby ficar vazio, apaga o lobby.
    public function removePlayerFromLobby($user_ID, $lobby_ID)
    {
        try {
            $db = Connection::getConnection();

            // Remove o player do lobby
            $stmt = $db->prepare('
                DELETE FROM lobby_players
                WHERE user_ID = :user_ID AND lobby_ID = :lobby_ID
            ');
            $stmt->bindParam(':user_ID', $user_ID);
            $stmt->bindParam(':lobby_ID', $lobby_ID);
            $stmt->execute();

            // Verifica quantos jogadores restam
            $stmtCount = $db->prepare('
                SELECT COUNT(*) AS total
                FROM lobby_players
                WHERE lobby_ID = :lobby_ID
            ');

            $stmtCount->bindParam(':lobby_ID', $lobby_ID);
            $stmtCount->execute();

            $countResult = $stmtCount->fetch(PDO::FETCH_ASSOC);
            $remainingPlayers = (int)$countResult['total'];

            // Apaga lobby se não tiver jogador
            if ($remainingPlayers === 0) {
                $stmtDelete = $db->prepare('
                    DELETE FROM lobbies
                    WHERE lobby_ID = :lobby_ID
                ');
                $stmtDelete->bindParam(':lobby_ID', $lobby_ID);
                $stmtDelete->execute();
            }

            return true;
        } catch (Exception $err) {
            throw $err;
        }
    }
}
