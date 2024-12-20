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


    public function DeleteDeck($deck_ID)
    {
        try {
            $db = Connection::getConnection();

            $sqlStatement  = $db->prepare('DELETE FROM decks WHERE deck_ID = :deck_ID');

            $sqlStatement->bindParam(':deck_ID', $deck_ID);

            $sqlStatement->execute();

            return $db->lastInsertId();
        } catch (Exception $err) {
            throw $err;
        }
    }
}
