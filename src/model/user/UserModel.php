<?php

namespace model\user;

use App\Model\Connection;
use Exception;

class UserModel
{
    public static function CheckUsedEmails($user_Email)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
               SELECT 1 FROM users WHERE user_Email = :user_Email
            ');

            $sql->bindParam(':user_Email', $user_Email);
            $sql->execute();

            $result = $sql->fetchColumn();

            return $result;
        } catch (\PDOException $err) {
            throw $err;
        }
    }

    public static function NewUser($user_Name, $user_Email, $user_Password)
    {
        try {
            // Conexão com o banco
            $db = Connection::getConnection();

            $sqlStatement  = $db->prepare('SELECT * FROM users WHERE user_Email = :user_Email');

            $sqlStatement->bindParam(':user_Email', $user_Email);
            // Executa a query
            $sqlStatement->execute();

            if ($sqlStatement->rowCount() > 0) {
                throw new Exception('E-mail já cadastrado');
            }

            $sqlStatement  = $db->prepare('INSERT INTO users (user_Name, user_Email, user_Password, user_Is_Admin, user_Status) VALUES 
            (:user_Name, :user_Email, :user_Password, false, "Offline")');

            $sqlStatement->bindParam(':user_Name', $user_Name);
            $sqlStatement->bindParam(':user_Email', $user_Email);
            $sqlStatement->bindParam(':user_Password', $user_Password);
            $sqlStatement->execute();

            // Retorna o ID do novo registro
            return $db->lastInsertId();
        } catch (\PDOException $err) {
            throw $err;
        }
    }

    public static function LoginUser($user_Email, $user_Password, $token, $token_Expiration)
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
                    FROM users WHERE user_Email = :user_Email AND user_Password = :user_Password
                ');
                $sqlStatement->bindParam(':user_Email', $user_Email);
                $sqlStatement->bindParam(':user_Password', $user_Password);
                $sqlStatement->execute();

                return $sqlStatement->fetch();
            }
        } catch (\Exception $err) {
            throw $err;
        }
    }

    public static function EditUser($user_ID, $user_Name, $user_Email, $user_Password, $user_New_Password)
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

            $user = $sqlStatement->fetch();

            if ($user_Password !== $user['user_Password']) {
                throw new Exception('Senha atual inválida.');
            }

            if ($user_New_Password) {
                $user_Password = $user_New_Password;
            }

            if ($user_Email === $user['user_Email']) {
                throw new Exception('E-mail igual ao atual.');
            } elseif (UserModel::CheckUsedEmails($user_Email)) {
                throw new Exception('E-mail ja em uso.');
            }

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
        } catch (\Exception $err) {
            throw $err;
        }
    }

    public function GetUser($user_ID)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('SELECT * FROM users WHERE user_ID = :user_ID');
            $sql->bindParam(':user_ID', $user_ID);
            $sql->execute();

            return $sql->fetch();
        } catch (\Exception $err) {
            throw $err;
        }
    }

    public static function DeleteUser($user_ID)
    {
        try {
            $db = Connection::getConnection();

            $sqlStatement = $db->prepare('SELECT user_Password, user_Email FROM users WHERE user_ID = :user_ID');
            $sqlStatement->bindParam(':user_ID', $user_ID);
            $sqlStatement->execute();

            $queryDelete = 'DELETE FROM users WHERE user_ID = :user_ID';

            $sqlStatement = $db->prepare($queryDelete);

            $sqlStatement->bindParam(':user_ID', $user_ID);

            $sqlStatement->execute();
        } catch (\Exception $err) {
            throw $err;
        }
    }

    public static function ValidateToken($token)
    {
        if (empty($token)) {
            throw new Exception('Token ausente.');
        }

        try {
            $db = Connection::getConnection();

            $sqlStatement  = $db->prepare('SELECT * FROM users WHERE token = :token');
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
        } catch (\Exception $err) {
            throw $err;
        }
    }
}
