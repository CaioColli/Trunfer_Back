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
        } catch (Exception) {
            throw new Exception("Erro ao criar a carta");
        }
    }

    public static function EditCardAttributes($deck_ID, $card_ID, $attribute_ID, $attribute_Value)
    {
        try {
            $db = Connection::getConnection();

            $sqlStatement = $db->prepare('
                UPDATE cards_attributes
                    SET attribute_Value  = :attribute_Value
                WHERE card_ID = :card_ID 
                AND attribute_ID = :attribute_ID
                AND deck_ID = :deck_ID
            ');

            $sqlStatement->bindParam(':card_ID', $card_ID);
            $sqlStatement->bindParam(':attribute_ID', $attribute_ID);
            $sqlStatement->bindParam(':attribute_Value', $attribute_Value);
            $sqlStatement->bindParam(':deck_ID', $deck_ID);
            $sqlStatement->execute();

            return true;
        } catch (Exception) {
            throw new Exception("Erro ao editar atributo");
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
        } catch (Exception) {
            throw new Exception("Erro ao editar a carta");
        }
    }

    public static function DeleteCard($deck_ID, $card_ID)
    {
        try {
            $db = Connection::getConnection();
            // Exclui a carta
            $sqlStatement = $db->prepare('
                DELETE FROM cards 
                WHERE card_ID = :card_ID
                AND deck_ID = :deck_ID
            ');
            $sqlStatement->bindParam(':card_ID', $card_ID);
            $sqlStatement->bindParam(':deck_ID', $deck_ID);
            $sqlStatement->execute();

            return true;
        } catch (Exception) {
            throw new Exception("Erro ao excluir a carta");
        }
    }

    public static function GetCards($deck_ID)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                SELECT 
                    card_ID,
                    card_Name,
                    card_Image 
                FROM cards 
                WHERE deck_ID = :deck_ID
            ');

            $sql->bindParam(':deck_ID', $deck_ID);
            $sql->execute();

            return $sql->fetchAll();
        } catch (Exception) {
            throw new Exception("Erro ao recuperar todas as cartas do baralho");
        }
    }

    public static function GetCard($deck_ID, $card_ID)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                SELECT 
                    c.card_ID, 
                    c.card_Name, 
                    c.card_Image, 
                    a.attribute_Name,
                    ca.attribute_ID, 
                    ca.attribute_Value
                FROM cards c

                INNER JOIN cards_attributes ca ON c.card_ID = ca.card_ID
                INNER JOIN attributes a ON ca.attribute_ID = a.attribute_ID

                WHERE c.card_ID = :card_ID 
                AND c.deck_ID = :deck_ID
            ');

            $sql->bindParam(':card_ID', $card_ID);
            $sql->bindParam(':deck_ID', $deck_ID);
            $sql->execute();

            $cardData = $sql->fetchAll();

            $card = [
                'card_ID' => $cardData[0]['card_ID'],
                'card_Name' => $cardData[0]['card_Name'],
                'card_Image' => $cardData[0]['card_Image'],
                'attributes' => []
            ];

            foreach ($cardData as $data) {
                if ($data['attribute_ID']) {
                    $card['attributes'][] = [
                        'attribute_Name' => $data['attribute_Name'],
                        'attribute_Value' => $data['attribute_Value']
                    ];
                }
            }

            return $card;
        } catch (Exception) {
            throw new Exception("Erro ao recuperar a carta");
        }
    }
}
