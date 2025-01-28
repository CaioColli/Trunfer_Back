<?php

namespace model\adm;

use App\Model\Connection;
use Exception;

class DeckModel
{
    // public function InsertNewDeck($deck_Name, $deck_Image)
    // {
    //     try {
    //         $db = Connection::getConnection();

    //         $sqlStatement  = $db->prepare('INSERT INTO decks (deck_Name, deck_Image) VALUES 
    //         (:deck_Name, :deck_Image)');

    //         $sqlStatement->bindParam(':deck_Name', $deck_Name);
    //         $sqlStatement->bindParam(':deck_Image', $deck_Image);

    //         $sqlStatement->execute();

    //         return $db->lastInsertId();
    //     } catch (Exception $err) {
    //         throw $err;
    //     }
    // }

    public static function NewDeck($deck_Name, $deck_Image, $attributes)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                INSERT INTO decks (deck_Name, deck_Image)
                VALUES (:deck_Name, :deck_Image)
            ');

            $sql->bindParam(':deck_Name', $deck_Name);
            $sql->bindParam(':deck_Image', $deck_Image);

            $sql->execute();

            $deckID = $db->lastInsertId();

            foreach ($attributes as $attribute) {
                $sqlAttributes = $db->prepare('
                    INSERT INTO attributes (deck_ID, attribute_Name)
                    VALUES (:deck_ID, :attribute_Name)
                ');

                $sqlAttributes->bindParam(':deck_ID', $deckID);
                $sqlAttributes->bindParam(':attribute_Name', $attribute);
                $sqlAttributes->execute();
            }

            return $deckID;
        } catch (Exception) {
            throw new Exception("Erro ao criar o baralho");
        }
    }

    // Insere os atributos ao deck criado
    // public function InsertDeckAttributes($deck_ID, $attributes)
    // {
    //     try {
    //         $db = Connection::getConnection();

    //         $sqlStatement = $db->prepare('
    //         INSERT INTO deck_attributes (deck_ID, attribute_ID)
    //         VALUES (:deck_ID, (SELECT attribute_ID FROM attributes WHERE attribute_Name = :attribute_Name))
    //     ');

    //         foreach ($attributes as $attribute) {
    //             $sqlStatement->bindParam(':deck_ID', $deck_ID);
    //             $sqlStatement->bindParam(':attribute_Name', $attribute);
    //             $sqlStatement->execute();
    //         }
    //     } catch (Exception $err) {
    //         throw $err;
    //     }
    // }

    // Insere um novo atributo na tabela attributes, ignorando duplicadas.
    // public function InsertAttribute($attribute_Name)
    // {
    //     try {
    //         $db = Connection::getConnection();

    //         $sqlStatement = $db->prepare('
    //         INSERT INTO attributes (attribute_Name)
    //         VALUES (:attribute_Name)
    //         ON DUPLICATE KEY UPDATE attribute_Name = attribute_Name
    //     ');

    //         $sqlStatement->bindParam(':attribute_Name', $attribute_Name);
    //         $sqlStatement->execute();
    //     } catch (Exception $err) {
    //         throw $err;
    //     }
    // }

    public static function DeleteDeck($deck_ID)
    {
        try {
            $db = Connection::getConnection();
            // Exclui o deck
            $sqlStatement  = $db->prepare('
                DELETE FROM 
                decks 
                WHERE deck_ID = :deck_ID
            ');
            $sqlStatement->bindParam(':deck_ID', $deck_ID);
            $sqlStatement->execute();

            // Exclui as associações de cartas com os atributos
            // $sqlStatement = $db->prepare('DELETE FROM letter_attributes WHERE letter_ID IN (SELECT letter_ID FROM letters WHERE deck_ID = :deck_ID)');
            // $sqlStatement->bindParam(':deck_ID', $deck_ID);
            // $sqlStatement->execute();

            // Exclui as cartas relacionadas ao deck
            // $sqlStatement = $db->prepare('DELETE FROM letters WHERE deck_ID = :deck_ID');
            // $sqlStatement->bindParam(':deck_ID', $deck_ID);
            // $sqlStatement->execute();

            return true;
        } catch (Exception) {
            throw new Exception("Erro ao excluir o baralho");
        }
    }

    public function EditDeck($deck_ID, $deck_Is_Available, $deck_Image)
    {
        try {
            $db = Connection::getConnection();

            $sqlUpdate  = $db->prepare('
            UPDATE decks 
                SET deck_Is_Available = :deck_Is_Available, 
                deck_Image = :deck_Image 
            WHERE deck_ID = :deck_ID');

            $sqlUpdate->bindParam(':deck_ID', $deck_ID);
            $sqlUpdate->bindParam(':deck_Is_Available', $deck_Is_Available);
            $sqlUpdate->bindParam(':deck_Image', $deck_Image);
            $sqlUpdate->execute();

            return true;
        } catch (Exception) {
            throw new Exception("Erro ao editar o baralho");
        }
    }

    public static function GetDeck($deck_ID)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                SELECT 
                    deck_ID, 
                    deck_Name, 
                    deck_Is_Available, 
                    deck_Image
                FROM decks
                WHERE deck_ID = :deck_ID
            ');

            $sql->bindParam(':deck_ID', $deck_ID);
            $sql->execute();

            return $sql->fetch();
        } catch (Exception) {
            throw new Exception("Erro ao recuperar dados o baralho");
        }
    }
    //

    public static function GetFullInfoDeck($deck_ID)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                SELECT 
                    d.deck_ID, 
                    d.deck_Name, 
                    d.deck_Is_Available, 
                    d.deck_Image,
                    GROUP_CONCAT(a.attribute_Name) as attributes
                FROM decks d
                INNER JOIN attributes a ON d.deck_ID = a.deck_ID
                WHERE d.deck_ID = :deck_ID
            ');

            $sql->bindParam(':deck_ID', $deck_ID);
            $sql->execute();

            return $sql->fetch();
        } catch (Exception) {
            throw new Exception("Erro ao recuperar dados o baralho");
        }
    }

    public static function GetDecks()
    {
        try {
            $db = Connection::getConnection();

            $sqlStatement = $db->prepare('SELECT * FROM decks');
            $sqlStatement->execute();

            $data = $sqlStatement->fetchAll();

            if (!$data) {
                return null;
            }

            $result = [];

            foreach ($data as $deck) {
                $result[] = [
                    'deck_ID' => $deck['deck_ID'],
                    'deck_Name' => $deck['deck_Name'],
                    'deck_Is_Available' => $deck['deck_Is_Available'],
                    'deck_Image' => $deck['deck_Image']
                ];
            }

            return $result;
        } catch (Exception $err) {
            throw $err;
        }
    }

    // Usado por enquanto na criação de cartas
    public static function GetDeckAttributes($deck_ID)
    {
        try {
            $db = Connection::getConnection();

            $sqlStatement  = $db->prepare('
                SELECT 
                    attribute_ID,
                    deck_ID,
                    attribute_Name
                FROM attributes
                WHERE deck_ID = :deck_ID
            ');

            //     SELECT 
            //     da.attribute_ID, 
            //     a.attribute_Name
            // FROM deck_attributes da
            // INNER JOIN attributes a ON da.attribute_ID = a.attribute_ID
            // WHERE da.deck_ID = :deck_ID

            $sqlStatement->bindParam(':deck_ID', $deck_ID);
            $sqlStatement->execute();

            return $sqlStatement->fetchAll();
        } catch (Exception $err) {
            throw $err;
        }
    }
}
