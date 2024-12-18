<?php

namespace App\Model;

use App\Model\Connection;
use Exception;

class UserModel
{
    public function NewUser($user_Name, $user_Email, $user_Password)
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

    public function LoginUser($user_Email, $user_Password, $token, $token_Expiration)
    {
        try {
            $db = Connection::getConnection();

            $sqlStatement  = $db->prepare('SELECT * FROM users WHERE user_Email = :user_Email AND user_Password = :user_Password');

            $sqlStatement->bindParam(':user_Email', $user_Email);
            $sqlStatement->bindParam(':user_Password', $user_Password);
            $sqlStatement->execute();

            if ($sqlStatement->rowCount() > 0) {
                $user = $sqlStatement->fetch();

                // Atualiza o status para "Online"
                $queryUpdate = 'UPDATE users SET user_Status = "Online", token = :token, token_Expiration = :token_Expiration WHERE user_ID = :user_ID';
                $sqlUpdate = $db->prepare($queryUpdate);
                $sqlUpdate->bindParam(':user_ID', $user['user_ID']);
                $sqlUpdate->bindParam(':token', $token);
                $sqlUpdate->bindParam(':token_Expiration', $token_Expiration);
                $sqlUpdate->execute();

                // Após atualizar é feita outra chamada para pegar os dados atualizados
                $query = 'SELECT * FROM users WHERE user_Email = :user_Email AND user_Password = :user_Password';
                $sqlStatement = $db->prepare($query);
                $sqlStatement->bindParam(':user_Email', $user_Email);
                $sqlStatement->bindParam(':user_Password', $user_Password);
                $sqlStatement->execute();

                // Retorna os dados atualizados
                if ($sqlStatement->rowCount() > 0) {
                    return $sqlStatement->fetch();
                }
            }
        } catch (\Exception $err) {
            throw $err;
        }
    }

    public function GetUserByToken($token)
    {
        try {
            $db = Connection::getConnection();

            $sqlStatement  = $db->prepare('SELECT * FROM users WHERE token = :token');
            $sqlStatement->bindParam(':token', $token);
            $sqlStatement->execute();

            if ($sqlStatement->rowCount() > 0) {
                return $sqlStatement->fetch();
            }

            return null; // Retorna null se o token não for encontrado
        } catch (\Exception $err) {
            throw $err;
        }
    }

    public function EditUser($user_ID, $user_Name, $user_Email, $user_Password, $user_New_Password)
    {
        try {
            $db = Connection::getConnection();
            
            $sqlStatement = $db->prepare('SELECT user_Password FROM users WHERE user_ID = :user_ID');
            $sqlStatement->bindParam(':user_ID', $user_ID);
            $sqlStatement->execute();
            $user = $sqlStatement->fetch();

            if (!$user || $user_Password !== $user['user_Password']) {
                throw new Exception('Senha atual inválida.');
            }

            // Se a senha foi alterada, aplica o hash
            if ($user_New_Password) {
                $user_Password = $user_New_Password;
            }

            $queryUpdate = 'UPDATE users SET user_Name = :user_Name, user_Email = :user_Email, user_Password = :user_Password WHERE user_ID = :user_ID';
            $sqlUpdate = $db->prepare($queryUpdate);

            $sqlUpdate->bindParam(':user_ID', $user_ID);
            $sqlUpdate->bindParam(':user_Name', $user_Name);
            $sqlUpdate->bindParam(':user_Email', $user_Email);
            $sqlUpdate->bindParam(':user_Password', $user_Password);

            $sqlUpdate->execute();

            $sqlStatement = $db->prepare('SELECT * FROM users WHERE user_ID = :user_ID');
            $sqlStatement->bindParam(':user_ID', $user_ID);
            $sqlStatement->execute();

            return $sqlStatement->fetch();
        } catch (\Exception $err) {
            throw $err;
        }
    }
}
