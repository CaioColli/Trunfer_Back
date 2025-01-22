<?php

namespace model\adm;

use App\Model\Connection;
use Exception;
use PDO;

class CardModel
{
    public static function NewCard($card_Name, $card_Image, $deck_ID, $attributes)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                INSERT INTO cards (card_Name, card_Image, deck_ID)
                VALUES (:card_Name, :card_Image, :deck_ID)
            ');

            $sql->bindParam(':card_Name', $card_Name);
            $sql->bindParam(':card_Image', $card_Image);
            $sql->bindParam(':deck_ID', $deck_ID);
            $sql->execute();

            $cardID = $db->lastInsertId();

            foreach ($attributes as $attribute) {
                $sqlAttributes = $db->prepare('
                    INSERT INTO cards_attributes (card_ID, attribute_ID, attribute_Value)
                    VALUES (:card_ID, :attribute_ID, :attribute_Value)
                ');

                $sqlAttributes->bindParam(':card_ID', $cardID);
                $sqlAttributes->bindParam(':attribute_ID', $attribute['attribute_ID']);
                $sqlAttributes->bindParam(':attribute_Value', $attribute['attribute_Value']);
                $sqlAttributes->execute();
            }

            return $cardID;
        } catch (Exception $err) {
            // return throw new Exception($err->getMessage());
            throw new Exception("Erro ao criar a carta");
        }
    }

    public static function EditCardAttributes($card_ID, $attribute_ID, $attribute_Value)
    {
        try {
            $db = Connection::getConnection();

            $sqlStatement = $db->prepare('
                UPDATE cards_attributes
                    SET attribute_Value  = :attribute_Value
                WHERE card_ID = :card_ID 
                AND attribute_ID = :attribute_ID
            ');

            $sqlStatement->bindParam(':card_ID', $card_ID);
            $sqlStatement->bindParam(':attribute_ID', $attribute_ID);
            $sqlStatement->bindParam(':attribute_Value', $attribute_Value);
            $sqlStatement->execute();

            return true;
        } catch (Exception $err) {
            throw $err;
        }
    }

    public static function EditCard($deck_ID, $card_ID, $card_Name, $card_Image)
    {
        try {
            $db = Connection::getConnection();

            $sqlCheck = $db->prepare('
                SELECT 
                    card_Name, 
                    card_Image 
                FROM cards 
                WHERE card_ID = :card_ID
                AND deck_ID = :deck_ID
            ');

            $sqlCheck->bindParam(':card_ID', $card_ID);
            $sqlCheck->bindParam(':deck_ID', $deck_ID);
            $sqlCheck->execute();

            $cardData = $sqlCheck->fetch();

            // Se não for passado um novo valor para nome e imagem, mantém o valor atual
            $cardNameData = $card_Name ?? $cardData['card_Name'];
            $cardImageData = $card_Image ?? $cardData['card_Image'];

            $sqlStatement = $db->prepare('
                UPDATE cards
                    SET card_Name = :card_Name,
                    card_Image = :card_Image
                WHERE card_ID = :card_ID
                AND deck_ID = :deck_ID
            ');

            $sqlStatement->bindParam(':card_ID', $card_ID);
            $sqlStatement->bindParam(':deck_ID', $deck_ID);
            $sqlStatement->bindParam(':card_Name', $cardNameData);
            $sqlStatement->bindParam(':card_Image', $cardImageData);

            $sqlStatement->execute();

            return true;
        } catch (Exception $err) {
            throw $err;
        }
    }

    public static function DeleteCard($deck_ID,$letter_ID)
    {
        try {
            $db = Connection::getConnection();
            // Exclui a carta
            $sqlStatement = $db->prepare('
                DELETE FROM cards 
                WHERE card_ID = :card_ID
                AND deck_ID = :deck_ID
            ');
            $sqlStatement->bindParam(':card_ID', $letter_ID);
            $sqlStatement->bindParam(':deck_ID', $deck_ID);
            $sqlStatement->execute();

            return true;
        } catch (Exception $err) {
            throw $err;
        }
    }

    // PAREI AQUI //

    public function GetLetter($letter_ID, $deck_ID)
    {
        try {
            $db = Connection::getConnection();

            $sqlStatement = $db->prepare('
                SELECT 
                    l.letter_ID, 
                    l.letter_Name, 
                    l.letter_Image, 
                    a.attribute_Name,
                    la.attribute_ID, 
                    la.attribute_Value
                FROM 
                    letters l
                INNER JOIN 
                    letter_attributes la ON l.letter_ID = la.letter_ID
                INNER JOIN
                    attributes a ON la.attribute_ID = a.attribute_ID
                WHERE 
                    l.letter_ID = :letter_ID 
                AND 
                    l.deck_ID = :deck_ID
            ');

            $sqlStatement->bindParam(':letter_ID', $letter_ID);
            $sqlStatement->bindParam(':deck_ID', $deck_ID);
            $sqlStatement->execute();

            $letterData = $sqlStatement->fetchAll();

            if (empty($letterData)) {
                return null;
            }

            $letter = [
                'letter_ID' => $letterData[0]['letter_ID'],
                'letter_Name' => $letterData[0]['letter_Name'],
                'letter_Image' => $letterData[0]['letter_Image'],
                'attributes' => []
            ];

            foreach ($letterData as $data) {
                if ($data['attribute_ID']) {
                    $letter['attributes'][] = [
                        'attribute_Name' => $data['attribute_Name'],
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

            if (empty($data)) {
                return [];
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
