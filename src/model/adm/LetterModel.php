<?php

namespace model\adm;

use App\Model\Connection;
use Exception;
use PDO;

class LetterModel
{
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

            $sqlInsert = $db->prepare('
                INSERT INTO letter_attributes (letter_ID, attribute_ID, attribute_Value)
                VALUES (:letter_ID, :attribute_ID, :attribute_Value)
            ');

            $sqlAttributeName = $db->prepare('
                SELECT attribute_Name
                FROM attributes
                WHERE attribute_ID = :attribute_ID
            ');

            $result = [];

            foreach ($attributes as $attribute) {
                $sqlInsert->bindParam(':letter_ID', $letter_ID);
                $sqlInsert->bindParam(':attribute_ID', $attribute['attribute_ID']);
                $sqlInsert->bindParam(':attribute_Value', $attribute['attribute_Value']);
                $sqlInsert->execute();

                $sqlAttributeName->bindParam(':attribute_ID', $attribute['attribute_ID']);
                $sqlAttributeName->execute();
                $attributeName = $sqlAttributeName->fetch();

                $result[] = [
                    'attributeName' => $attributeName['attribute_Name'] ?? null,
                    'attributeValue' => $attribute['attribute_Value']
                ];
            }

            return $result;
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

            $letterData = $sqlStatement->fetchAll(PDO::FETCH_ASSOC);

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

    public function DeleteLetter($letter_ID)
    {
        try {
            $db = Connection::getConnection();

            // Exclui as associações da carta com os atributos
            $sqlStatement = $db->prepare('DELETE FROM letter_attributes WHERE letter_ID = :letter_ID');
            $sqlStatement->bindParam(':letter_ID', $letter_ID);
            $sqlStatement->execute();

            // Exclui os atributos que não estão mais associados a nenhuma carta
            $sqlStatement = $db->prepare('DELETE FROM attributes WHERE attribute_ID NOT IN (SELECT attribute_ID FROM letter_attributes)');
            $sqlStatement->execute();

            // Exclui a carta
            $sqlStatement = $db->prepare('DELETE FROM letters WHERE letter_ID = :letter_ID');
            $sqlStatement->bindParam(':letter_ID', $letter_ID);
            $sqlStatement->execute();

            return true;
        } catch (Exception $err) {
            throw $err;
        }
    }
}
