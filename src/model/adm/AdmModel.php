<?php

namespace model\adm;

use App\Model\Connection;
use Exception;
use PDO;

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

            $result = [];

            foreach ($data as $deck) {
                $result[] = [
                    'deck_ID' => $deck['deck_ID'],
                    'deck_Name' => $deck['deck_Name'],
                    'deck_Is_Available' => (bool)$deck['deck_Is_Available'],
                    'deck_Image' => $deck['deck_Image']
                ];
            }

            return $result;
        } catch (Exception $err) {
            throw $err;
        }
    }

    public function GetDeckAttributes($deck_ID)
    {
        try {
            $db = Connection::getConnection();

            $sqlStatement  = $db->prepare('
                SELECT 
                    da.attribute_ID, 
                    a.attribute_Name
                FROM deck_attributes da
                INNER JOIN attributes a ON da.attribute_ID = a.attribute_ID
                WHERE da.deck_ID = :deck_ID
            ');

            $sqlStatement->bindParam(':deck_ID', $deck_ID);
            $sqlStatement->execute();

            return $sqlStatement->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $err) {
            throw $err;
        }
    }

    //

    public function InsertNewLetter($letter_Name, $letter_Image, $deck_ID)
    {
        try {
            $db = Connection::getConnection();

            $sqlStatement = $db->prepare('
                INSERT INTO letters (letter_Name, letter_Image, deck_ID)
                VALUES (:letter_Name, :letter_Image, :deck_ID)
            ');

            $sqlStatement->bindParam(':letter_Name', $letter_Name);
            $sqlStatement->bindParam(':letter_Image', $letter_Image);
            $sqlStatement->bindParam(':deck_ID', $deck_ID);
            $sqlStatement->execute();

            return $db->lastInsertId();
        } catch (Exception $err) {
            throw $err;
        }
    }

    public function InsertLetterAttributes($letter_ID, $attributes)
    {
        try {
            $db = Connection::getConnection();

            $sqlStatement = $db->prepare('
                INSERT INTO letter_attributes (letter_ID, attribute_ID, attribute_Value)
                VALUES (:letter_ID, :attribute_ID, :attribute_Value)
            ');

            foreach ($attributes as $attribute) {
                $sqlStatement->bindParam(':letter_ID', $letter_ID);
                $sqlStatement->bindParam(':attribute_ID', $attribute['attribute_ID']);
                $sqlStatement->bindParam(':attribute_Value', $attribute['attribute_Value']);
                $sqlStatement->execute();
            }
        } catch (Exception $err) {
            throw $err;
        }
    }

    public function EditLetterAttribute($letter_ID, $attribute_ID, $attribute_Value)
    {
        try {
            $db = Connection::getConnection();

            $sqlStatement = $db->prepare('
                UPDATE letter_attributes
                SET attribute_Value  = :attribute_Value
                WHERE letter_ID = :letter_ID AND attribute_ID = :attribute_ID
            ');

            $sqlStatement->bindParam(':letter_ID', $letter_ID);
            $sqlStatement->bindParam(':attribute_ID', $attribute_ID);
            $sqlStatement->bindParam(':attribute_Value', $attribute_Value);
            $sqlStatement->execute();

            return true;
        } catch (Exception $err) {
            throw $err;
        }
    }

    public function EditLetterDetails($letter_ID, $letter_Name, $letter_Image)
    {
        try {
            $db = Connection::getConnection();

            $sqlCheck = $db->prepare('SELECT letter_Name, letter_Image FROM letters WHERE letter_ID = :letter_ID');
            $sqlCheck->bindParam(':letter_ID', $letter_ID);
            $sqlCheck->execute();
            $letterData = $sqlCheck->fetch(PDO::FETCH_ASSOC);

            if (!$letterData) {
                throw new Exception("Carta não encontrada com o ID: " . $letter_ID);
            }

            // Se não for passado um novo valor para nome e imagem, mantém o valor atual
            $letter_Name = $letter_Name ?? $letterData['letter_Name'];
            $letter_Image = $letter_Image ?? $letterData['letter_Image'];

            $sqlStatement = $db->prepare('
                UPDATE letters
                SET letter_Name = :letter_Name, letter_Image = :letter_Image
                WHERE letter_ID = :letter_ID
            ');

            $sqlStatement->bindParam(':letter_ID', $letter_ID);
            $sqlStatement->bindParam(':letter_Name', $letter_Name);
            $sqlStatement->bindParam(':letter_Image', $letter_Image);

            $sqlStatement->execute();

            return true;
        } catch (Exception $err) {
            throw $err;
        }
    }

    public function GetLetter($letter_ID)
    {
        try {
            $db = Connection::getConnection();

            $sqlStatement = $db->prepare('
                SELECT l.letter_ID, l.letter_Name, l.letter_Image, la.attribute_ID, la.attribute_Value
                FROM letters l
                INNER JOIN letter_attributes la ON l.letter_ID = la.letter_ID
                WHERE l.letter_ID = :letter_ID
            ');

            $sqlStatement->bindParam(':letter_ID', $letter_ID);
            $sqlStatement->execute();

            $letterData = $sqlStatement->fetchAll(PDO::FETCH_ASSOC);

            $letter = [
                'letter_ID' => $letterData[0]['letter_ID'],
                'letter_Name' => $letterData[0]['letter_Name'],
                'letter_Image' => $letterData[0]['letter_Image'],
                'attributes' => []
            ];

            foreach ($letterData as $data) {
                if ($data['attribute_ID']) {
                    $letter['attributes'][] = [
                        'attribute_ID' => $data['attribute_ID'],
                        'attribute_Value' => $data['attribute_Value']
                    ];
                }
            }

            return $letter;
        } catch (Exception $err) {
            throw $err;
        }
    }

    public function GetLetters($deck_ID)
    {
        try {
            $db = Connection::getConnection();

            $sqlStatement = $db->prepare('SELECT * FROM letters WHERE deck_ID = :deck_ID');
            $sqlStatement->bindParam(':deck_ID', $deck_ID);
            $sqlStatement->execute();

            $data = $sqlStatement->fetchAll();

            if (!$data) {
                return null;
            }

            $result = [];

            foreach ($data as $deck) {
                $result[] = [
                    'letter_ID' => $deck['letter_ID'],
                    'letter_Name' => $deck['letter_Name'],
                    'letter_Image' => $deck['letter_Image']
                ];
            }

            return $result;
        } catch (Exception $err) {
            throw $err;
        }
    }

    
}
