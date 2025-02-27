<?php

namespace model\user;

use App\Model\Connection;
use Exception;

class UserModel
{
    public static function Cadaster($user_Name, $user_Email, $user_Password)
    {
        try {
            // Conexão com o banco
            $db = Connection::getConnection();

            $sqlStatement  = $db->prepare('INSERT INTO users (user_Name, user_Email, user_Password, user_Is_Admin, user_Status) VALUES 
            (:user_Name, :user_Email, :user_Password, false, "Offline")');

            $sqlStatement->bindParam(':user_Name', $user_Name);
            $sqlStatement->bindParam(':user_Email', $user_Email);
            $sqlStatement->bindParam(':user_Password', $user_Password);
            $sqlStatement->execute();

            // Retorna o ID do novo registro
            return $db->lastInsertId();
        } catch (Exception) {
            throw new Exception("Erro ao tentar se cadastrar");
        }
    }

    public static function CheckUsedEmails($user_Email)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
               SELECT 1 FROM users WHERE user_Email = :user_Email
            ');

            $sql->bindParam(':user_Email', $user_Email);
            $sql->execute();

            $result = $sql->fetchColumn() !== false;

            return $result;
        } catch (Exception) {
            throw new Exception("Erro ao tentar verificar emails existentes");
        }
    }

    public static function Login($user_Email, $user_Password, $token, $token_Expiration)
    {
        try {
            $db = Connection::getConnection();

            $sqlStatement  = $db->prepare('
                SELECT user_ID FROM users WHERE user_Email = :user_Email AND user_Password = :user_Password
            ');

            $sqlStatement->bindParam(':user_Email', $user_Email);
            $sqlStatement->bindParam(':user_Password', $user_Password);
            $sqlStatement->execute();

            $userID = $sqlStatement->fetchColumn();

            if ($userID) {
                // Atualiza o status para "Online" e seta o token e expiração
                $sqlUpdate = $db->prepare('
                    UPDATE users SET 
                        user_Status = "Online", 
                        token = :token, 
                        token_Expiration = :token_Expiration 
                    WHERE user_ID = :user_ID
                ');

                $sqlUpdate->bindParam(':user_ID', $userID);
                $sqlUpdate->bindParam(':token', $token);
                $sqlUpdate->bindParam(':token_Expiration', $token_Expiration);
                $sqlUpdate->execute();

                // Após atualizar é feita outra chamada para pegar os dados atualizados
                $sqlStatement = $db->prepare('
                    SELECT 
                        user_ID, 
                        user_Is_Admin, 
                        user_Name, 
                        user_Email, 
                        user_Status,
                        token,
                        token_Expiration
                    FROM users WHERE user_Email = :user_Email 
                    AND user_Password = :user_Password
                ');
                $sqlStatement->bindParam(':user_Email', $user_Email);
                $sqlStatement->bindParam(':user_Password', $user_Password);
                $sqlStatement->execute();

                return $sqlStatement->fetch();
            }
        } catch (Exception) {
            throw new Exception("Erro ao tentar logar");
        }
    }

    public static function GetUser($user_ID)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                SELECT 
                    user_ID, 
                    user_Is_Admin, 
                    user_Name, 
                    user_Email, 
                    user_Status,
                    games_Won,
                    games_Played,
                    token,
                    token_Expiration
                FROM users 
                WHERE user_ID = :user_ID
            ');

            $sql->bindParam(':user_ID', $user_ID);
            $sql->execute();

            $userData = $sql->fetch();

            return $userData;
        } catch (Exception) {
            throw new Exception("Erro ao tentar recuperar os dados do usuário");
        }
    }

    public static function ValidateToken($token)
    {
        try {
            $db = Connection::getConnection();

            $sqlStatement  = $db->prepare('
                SELECT 
                    user_ID,
                    user_Name,
                    user_Email,
                    user_Password,
                    user_Status,
                    token_Expiration
                FROM users 
                WHERE token = :token
            ');
            $sqlStatement->bindParam(':token', $token);
            $sqlStatement->execute();

            if ($sqlStatement->rowCount() === 0) {
                throw new Exception('Token inválido.');
            }

            $user = $sqlStatement->fetch();

            $currentTime = new \DateTime('now', new \DateTimeZone('America/Sao_Paulo'));
            $tokenExpiration = new \DateTime($user['token_Expiration'], new \DateTimeZone('America/Sao_Paulo'));

            if ($currentTime > $tokenExpiration) {
                throw new Exception('Token expirado.');
            }

            return $user;
        } catch (Exception $err) {
            throw new Exception($err->getMessage());
        }
    }

    public static function Edit($user_ID, $user_Name, $user_Email, $user_Password, $user_New_Password)
    {
        try {
            $db = Connection::getConnection();

            $sqlUpdate = $db->prepare('
                UPDATE users 
                    SET user_Name = :user_Name, 
                    user_Email = :user_Email, 
                    user_Password = :user_Password
                WHERE user_ID = :user_ID
            ');

            $sqlUpdate->bindParam(':user_ID', $user_ID);
            $sqlUpdate->bindParam(':user_Name', $user_Name);
            $sqlUpdate->bindParam(':user_Email', $user_Email);
            $sqlUpdate->bindParam(':user_Password', $user_Password);
            $sqlUpdate->bindParam(':user_Password', $user_New_Password);

            $sqlUpdate->execute();

            $sqlStatement = $db->prepare('
                SELECT 
                    user_ID,
                    user_Is_Admin,
                    user_Name,
                    user_Email,
                    user_Status
                FROM users WHERE user_ID = :user_ID
            ');
            $sqlStatement->bindParam(':user_ID', $user_ID);
            $sqlStatement->execute();

            return $sqlStatement->fetch();
        } catch (Exception $err) {
            throw new Exception("Erro ao tentar editar o usuário" . $err->getMessage());
        }
    }

    public static function DeleteUser($user_ID)
    {
        try {
            $db = Connection::getConnection();

            $sqlStatement = $db->prepare('
                SELECT 
                    user_Password, 
                    user_Email 
                FROM users 
                WHERE user_ID = :user_ID
            ');

            $sqlStatement->bindParam(':user_ID', $user_ID);
            $sqlStatement->execute();

            $queryDelete = 'DELETE FROM users WHERE user_ID = :user_ID';

            $sqlStatement = $db->prepare($queryDelete);

            $sqlStatement->bindParam(':user_ID', $user_ID);

            $sqlStatement->execute();
        } catch (Exception) {
            throw new Exception("Erro ao deletar usuário");
        }
    }
}
