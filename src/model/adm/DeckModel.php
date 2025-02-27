<?php

namespace model\adm;

use App\Model\Connection;
use Exception;

class DeckModel
{
    public static function NewDeck($deck_Name, $deck_Image, $attributes)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                INSERT INTO decks (
                    deck_Name, 
                    deck_Image,
                    first_Attribute,
                    second_Attribute,
                    third_Attribute,
                    fourth_Attribute,
                    fifth_Attribute
                )
                VALUES (
                    :deck_Name, 
                    :deck_Image,
                    :first_Attribute,
                    :second_Attribute,
                    :third_Attribute,
                    :fourth_Attribute,
                    :fifth_Attribute
                )
            ');

            $sql->bindParam(':deck_Name', $deck_Name);
            $sql->bindParam(':deck_Image', $deck_Image);
            $sql->bindParam(':first_Attribute', $attributes[0]);
            $sql->bindParam(':second_Attribute', $attributes[1]);
            $sql->bindParam(':third_Attribute', $attributes[2]);
            $sql->bindParam(':fourth_Attribute', $attributes[3]);
            $sql->bindParam(':fifth_Attribute', $attributes[4]);

            $sql->execute();
            $deckID = $db->lastInsertId();

            return $deckID;
        } catch (Exception $err) {
            throw new Exception("Erro ao criar o baralho" . $err);
        }
    }

    public static function DeleteDeck($deck_ID)
    {
        try {
            $db = Connection::getConnection();
            $sqlStatement  = $db->prepare('
                DELETE FROM 
                decks 
                WHERE deck_ID = :deck_ID
            ');
            $sqlStatement->bindParam(':deck_ID', $deck_ID);
            $sqlStatement->execute();

            return true;
        } catch (Exception) {
            throw new Exception("Erro ao excluir o baralho");
        }
    }

    // Futuramente ser possivel mudar tambem atributos e nome.
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

    public static function GetDecks()
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
            ');

            $sql->execute();

            return $sql->fetchAll();
        } catch (Exception) {
            throw new Exception("Erro ao recuperar dados o baralho");
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
                    deck_Image,
                    first_Attribute,
                    second_Attribute,
                    third_Attribute,
                    fourth_Attribute,
                    fifth_Attribute
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

    // Usado na criação de cartas
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

            $sqlStatement->bindParam(':deck_ID', $deck_ID);
            $sqlStatement->execute();

            return $sqlStatement->fetchAll();
        } catch (Exception $err) {
            throw $err;
        }
    }
}
