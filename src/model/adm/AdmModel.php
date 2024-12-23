<?php

namespace model\adm;

use App\Model\Connection;
use Exception;

class AdmModel
{
    public function InsertNewDeck($deck_Name, $deck_Image)
    {
        try {
            $db = Connection::getConnection();

            $sqlStatement  = $db->prepare('INSERT INTO decks (deck_Name, deck_Image) VALUES 
            (:deck_Name, :deck_Image)');

            $sqlStatement->bindParam(':deck_Name', $deck_Name);
            $sqlStatement->bindParam(':deck_Image', $deck_Image);

            $sqlStatement->execute();

            return $db->lastInsertId();
        } catch (Exception $err) {
            throw $err;
        }
    }

    // Insere os atributos ao deck criado
    public function InsertDeckAttributes($deck_ID, $attributes)
    {
        try {
            $db = Connection::getConnection();

            $sqlStatement = $db->prepare('
            INSERT INTO deck_attributes (deck_ID, attribute_ID)
            VALUES (:deck_ID, (SELECT attribute_ID FROM attributes WHERE attribute_Name = :attribute_Name))
        ');

            foreach ($attributes as $attribute) {
                $sqlStatement->bindParam(':deck_ID', $deck_ID);
                $sqlStatement->bindParam(':attribute_Name', $attribute);
                $sqlStatement->execute();
            }
        } catch (Exception $err) {
            throw $err;
        }
    }

    // Insere um novo atributo na tabela attributes, ignorando duplicatas.
    public function InsertAttribute($attribute_Name)
    {
        try {
            $db = Connection::getConnection();

            $sqlStatement = $db->prepare('
            INSERT INTO attributes (attribute_Name)
            VALUES (:attribute_Name)
            ON DUPLICATE KEY UPDATE attribute_Name = attribute_Name
        ');

            $sqlStatement->bindParam(':attribute_Name', $attribute_Name);
            $sqlStatement->execute();
        } catch (Exception $err) {
            throw $err;
        }
    }

    public function DeleteDeck($deck_ID)
    {
        try {
            $db = Connection::getConnection();

            // Exclui as associações do deck com os atributos
            $sqlStatement = $db->prepare('DELETE FROM deck_attributes WHERE deck_ID = :deck_ID');
            $sqlStatement->bindParam(':deck_ID', $deck_ID);
            $sqlStatement->execute();

            // Exclui os atributos que não estão mais associados a nenhum outro deck
            $sqlStatement = $db->prepare('DELETE FROM attributes WHERE attribute_ID NOT IN (SELECT attribute_ID FROM deck_attributes)');
            $sqlStatement->execute();

            $sqlStatement  = $db->prepare('DELETE FROM decks WHERE deck_ID = :deck_ID');
            $sqlStatement->bindParam(':deck_ID', $deck_ID);
            $sqlStatement->execute();

            return true;
        } catch (Exception $err) {
            throw $err;
        }
    }

    public function GetDeck($deck_ID)
    {
        try {
            $db = Connection::getConnection();

            $sqlStatement = $db->prepare('
            SELECT 
                d.deck_ID, 
                d.deck_Name, 
                d.deck_Is_Available, 
                d.deck_Image, 
                a.attribute_Name 
            FROM decks d
            JOIN deck_attributes da ON d.deck_ID = da.deck_ID
            JOIN attributes a ON da.attribute_ID = a.attribute_ID
            WHERE d.deck_ID = :deck_ID
            ');

            $sqlStatement->bindParam(':deck_ID', $deck_ID);
            $sqlStatement->execute();

            $deckData = $sqlStatement->fetchAll();

            if (!$deckData) {
                return null;
            }

            $result = [
                'deck_Name' => $deckData[0]['deck_Name'],
                'deck_Is_Available' => (bool)$deckData[0]['deck_Is_Available'],
                'deck_Image' => $deckData[0]['deck_Image'],
                'attributes' => array_column($deckData, 'attribute_Name')
            ];

            return $result;
        } catch (Exception $err) {
            throw $err;
        }
    }

    public function EditDeck($deck_ID, $deck_Is_Available, $deck_Image)
    {
        try {
            $db = Connection::getConnection();

            $sqlStatement  = $db->prepare('UPDATE decks SET deck_Is_Available = :deck_Is_Available, deck_Image = :deck_Image WHERE deck_ID = :deck_ID');

            $sqlStatement->bindParam(':deck_ID', $deck_ID);
            $sqlStatement->bindParam(':deck_Is_Available', $deck_Is_Available);
            $sqlStatement->bindParam(':deck_Image', $deck_Image);
            $sqlStatement->execute();

            return true;
        } catch (Exception $err) {
            throw $err;
        }
    }

    public function GetDecks() 
    {
        try {
            $db = Connection::getConnection();

            $sqlStatement = $db->prepare('SELECT * FROM decks');
            $sqlStatement->execute();

            $data = $sqlStatement->fetchAll();

            if (!$data) {
                return null;
            }

            $result = [
                'deck_ID' => $data[0]['deck_ID'],
                'deck_Name' => $data[0]['deck_Name'],
                'deck_Is_Available' => (bool)$data[0]['deck_Is_Available'],
                'deck_Image' => $data[0]['deck_Image']
            ];

            return $result;
        } catch (Exception $err) {
            throw $err;
        }
    }
}
