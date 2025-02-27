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
                INSERT INTO cards (
                    card_Name, 
                    card_Image, 
                    deck_ID,
                    first_Attribute_Value,
                    second_Attribute_Value,
                    third_Attribute_Value,
                    fourth_Attribute_Value,
                    fifth_Attribute_Value
                )
                VALUES (
                    :card_Name, 
                    :card_Image, 
                    :deck_ID,
                    :first_Attribute_Value,
                    :second_Attribute_Value,
                    :third_Attribute_Value,
                    :fourth_Attribute_Value,
                    :fifth_Attribute_Value
                )
            ');

            $sql->bindParam(':card_Name', $card_Name);
            $sql->bindParam(':card_Image', $card_Image);
            $sql->bindParam(':deck_ID', $deck_ID);
            $sql->bindParam(':first_Attribute_Value', $attributes[0]['attribute_Value']);
            $sql->bindParam(':second_Attribute_Value', $attributes[1]['attribute_Value']);
            $sql->bindParam(':third_Attribute_Value', $attributes[2]['attribute_Value']);
            $sql->bindParam(':fourth_Attribute_Value', $attributes[3]['attribute_Value']);
            $sql->bindParam(':fifth_Attribute_Value', $attributes[4]['attribute_Value']);

            $sql->execute();
            $cardID = $db->lastInsertId();

            // foreach ($attributes as $attribute) {
            //     $sqlAttributes = $db->prepare('
            //         INSERT INTO cards_attributes (card_ID, attribute_ID, attribute_Value)
            //         VALUES (:card_ID, :attribute_ID, :attribute_Value)
            //     ');

            //     $sqlAttributes->bindParam(':card_ID', $cardID);
            //     $sqlAttributes->bindParam(':attribute_ID', $attribute['attribute_ID']);
            //     $sqlAttributes->bindParam(':attribute_Value', $attribute['attribute_Value']);
            //     $sqlAttributes->execute();
            // }

            return $cardID;
        } catch (Exception $err) {
            throw new Exception("Erro ao criar a carta" . $err);
        }
    }

    public static function EditCard($card_ID, $card_Name, $card_Image, $attributes)
    {
        try {
            $db = Connection::getConnection();

            $sqlCheck = $db->prepare('
                SELECT 
                    card_Name, 
                    card_Image,
                    first_Attribute_Value,
                    second_Attribute_Value,
                    third_Attribute_Value,
                    fourth_Attribute_Value,
                    fifth_Attribute_Value 
                FROM cards 
                WHERE card_ID = :card_ID
            ');

            $sqlCheck->bindParam(':card_ID', $card_ID);
            $sqlCheck->execute();

            $cardData = $sqlCheck->fetch();

            // Se não for passado um novo valor para nome e imagem, mantém o valor atual
            $cardNameData = $card_Name ?? $cardData['card_Name'];
            $cardImageData = $card_Image ?? $cardData['card_Image'];
            $attributes[0] ?? $cardData['first_Attribute_Value'];
            $attributes[1] ?? $cardData['second_Attribute_Value'];
            $attributes[2] ?? $cardData['third_Attribute_Value'];
            $attributes[3] ?? $cardData['fourth_Attribute_Value'];
            $attributes[4] ?? $cardData['fifth_Attribute_Value'];

            $sqlStatement = $db->prepare('
                UPDATE cards
                    SET card_Name = :card_Name,
                    card_Image = :card_Image,
                    first_Attribute_Value = :first_Attribute_Value,
                    second_Attribute_Value = :second_Attribute_Value,
                    third_Attribute_Value = :third_Attribute_Value,
                    fourth_Attribute_Value = :fourth_Attribute_Value,
                    fifth_Attribute_Value = :fifth_Attribute_Value
                WHERE card_ID = :card_ID
            ');

            $sqlStatement->bindParam(':card_ID', $card_ID);
            $sqlStatement->bindParam(':card_Name', $cardNameData);
            $sqlStatement->bindParam(':card_Image', $cardImageData);
            $sqlStatement->bindParam(':first_Attribute_Value', $attributes[0]['attribute_Value']);
            $sqlStatement->bindParam(':second_Attribute_Value', $attributes[1]['attribute_Value']);
            $sqlStatement->bindParam(':third_Attribute_Value', $attributes[2]['attribute_Value']);
            $sqlStatement->bindParam(':fourth_Attribute_Value', $attributes[3]['attribute_Value']);
            $sqlStatement->bindParam(':fifth_Attribute_Value', $attributes[4]['attribute_Value']);

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

    public static function GetCard($card_ID)
    {
        try {
            $db = Connection::getConnection();

            $sql = $db->prepare('
                SELECT 
                    d.deck_ID,
                    c.card_ID, 
                    c.card_Name, 
                    c.card_Image, 
                    d.first_Attribute,
                    c.first_Attribute_Value,
                    d.second_Attribute,
                    c.second_Attribute_Value,
                    d.third_Attribute,
                    c.third_Attribute_Value,
                    d.fourth_Attribute,
                    c.fourth_Attribute_Value,
                    d.fifth_Attribute,
                    c.fifth_Attribute_Value
                FROM cards c
                    INNER JOIN decks d ON c.deck_ID = d.deck_ID
                WHERE card_ID = :card_ID 
            ');

            $sql->bindParam(':card_ID', $card_ID);
            $sql->execute();

            $cardData = $sql->fetch();

            return $cardData;
        } catch (Exception $err) {
            throw new Exception("Erro ao recuperar a carta" . $err);
        }
    }
}
