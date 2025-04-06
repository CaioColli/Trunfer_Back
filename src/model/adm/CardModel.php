<?php

namespace model\adm;

use App\Model\Connection;
use Exception;
use PDO;

class CardModel
{
    public static function NewCard($deck_ID, $card_Name, $card_Image, $attributes)
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
            $sql->bindParam(':first_Attribute_Value', $attributes[0]);
            $sql->bindParam(':second_Attribute_Value', $attributes[1]);
            $sql->bindParam(':third_Attribute_Value', $attributes[2]);
            $sql->bindParam(':fourth_Attribute_Value', $attributes[3]);
            $sql->bindParam(':fifth_Attribute_Value', $attributes[4]);

            $sql->execute();
            $cardID = $db->lastInsertId();

            return $cardID;
        } catch (Exception $err) {
            throw new Exception("Erro ao criar a carta" . $err);
        }
    }

    public static function EditCard($card_ID, $deck_ID, $card_Name, $card_Image, $first_Attribute_Value, $second_Attribute_Value, $third_Attribute_Value, $fourth_Attribute_Value, $fifth_Attribute_Value)
    {
        try {
            $db = Connection::getConnection();

            $sqlUpdate = $db->prepare('
                UPDATE cards
                    SET
                    card_Name = :card_Name,
                    card_Image = :card_Image,
                    first_Attribute_Value = :first_Attribute_Value,
                    second_Attribute_Value = :second_Attribute_Value,
                    third_Attribute_Value = :third_Attribute_Value,
                    fourth_Attribute_Value = :fourth_Attribute_Value,
                    fifth_Attribute_Value = :fifth_Attribute_Value
                WHERE card_ID = :card_ID
                    AND deck_ID = :deck_ID
            ');

            $sqlUpdate->bindParam(':card_ID', $card_ID);
            $sqlUpdate->bindParam(':deck_ID', $deck_ID);
            $sqlUpdate->bindParam(':card_Name', $card_Name);
            $sqlUpdate->bindParam(':card_Image', $card_Image);
            $sqlUpdate->bindParam(':first_Attribute_Value', $first_Attribute_Value);
            $sqlUpdate->bindParam(':second_Attribute_Value', $second_Attribute_Value);
            $sqlUpdate->bindParam(':third_Attribute_Value', $third_Attribute_Value);
            $sqlUpdate->bindParam(':fourth_Attribute_Value', $fourth_Attribute_Value);
            $sqlUpdate->bindParam(':fifth_Attribute_Value', $fifth_Attribute_Value);
            $sqlUpdate->execute();

            return true;
        } catch (Exception $err) {
            throw new Exception("Erro ao editar a carta" . $err);
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

            $data = [
                "deck_ID" => $cardData['deck_ID'],
                "card_ID" => $cardData['card_ID'],
                "card_Name" => $cardData['card_Name'],
                "card_Image" => $cardData['card_Image'],
                "attributes" => [
                    [
                        "attribute" => $cardData['first_Attribute'],
                        "attribute_Value" => $cardData['first_Attribute_Value'],
                    ],
                    [
                        "attribute" => $cardData['second_Attribute'],
                        "attribute_Value" => $cardData['second_Attribute_Value'],
                    ],
                    [
                        "attribute" => $cardData['third_Attribute'],
                        "attribute_Value" => $cardData['third_Attribute_Value'],
                    ],
                    [
                        "attribute" => $cardData['fourth_Attribute'],
                        "attribute_Value" => $cardData['fourth_Attribute_Value'],
                    ],
                    [
                        "attribute" => $cardData['fifth_Attribute'],
                        "attribute_Value" => $cardData['fifth_Attribute_Value'],
                    ]
                ]
            ];

            return $data;
        } catch (Exception $err) {
            throw new Exception("Erro ao recuperar a carta" . $err);
        }
    }
}
