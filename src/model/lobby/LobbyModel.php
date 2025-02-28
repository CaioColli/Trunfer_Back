<?php

namespace model\lobby;

use App\Model\Connection;
use Exception;
use PDO;

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

            $result = $sql->fetch();

            return $result['lobby_Status'];
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

            if (!$lobbyData) {
                return null;
            }

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

    public static function GetLobbyPlayer($lobby_ID, $user_ID)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                SELECT 
                    lobby_Player_ID
                FROM lobby_players
                WHERE lobby_ID = :lobby_ID AND user_ID = :user_ID
            ');

            $sql->bindParam(':lobby_ID', $lobby_ID);
            $sql->bindParam(':user_ID', $user_ID);
            $sql->execute();

            return $sql->fetch(PDO::FETCH_COLUMN);
        } catch (Exception) {
            throw new Exception('Erro ao buscar jogador no lobby.');
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
}