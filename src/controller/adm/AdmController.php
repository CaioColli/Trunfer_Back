<?php

namespace controller\adm;

use Exception;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;

use model\adm\AdmModel;
use model\user\UserModel;
use response\Messages;
use response\Responses;

class AdmController
{
    public function CreateDeck(PsrRequest $request, PsrResponse $response)
    {
        $token = $request->getHeader('Authorization')[0] ?? null;

        $bodyContent = $request->getBody();
        $data = json_decode($bodyContent, true);

        $rule = \validation\AdmValidation::DeckCreate();

        if (!isset($data['attributes']) || !is_array($data['attributes']) || count($data['attributes']) !== 5) {
            return Messages::Error400($response, ['Erro ao enviar os atributos, tente novamente enviando 5 atributos.']);
        }

        $errors = [];

        if (!isset($data['deck_Name']) || !$rule['deck_Name']->validate($data['deck_Name'])) {
            $errors[] = 'Nome inválido ou ausente.';
        }

        if (!isset($data['deck_Image']) || !$rule['deck_Image']->validate($data['deck_Image'])) {
            $errors[] = 'Imagem inválida ou ausente.';
        }


        if (count($errors) > 0) {
            return Messages::Error400($response, $errors);
        }

        try {
            $deck = new AdmModel();
            $userModel = new UserModel();

            $user = $userModel->ValidateToken($token);

            $deck_ID = $deck->InsertNewDeck(
                $data['deck_Name'],
                $data['deck_Image']
            );

            // Insere os atributos na tabela attributes e associa ao deck
            foreach ($data['attributes'] as $attribute) {
                $deck->InsertAttribute($attribute);
            }

            $deck->InsertDeckAttributes($deck_ID, $data['attributes']);

            $response = $response->withStatus(201);
            $response->getBody()->write(json_encode(
                [
                    'deck_ID' => $deck_ID,
                    'deck_Name' => $data['deck_Name'],
                    'deck_Is_Available' => false,
                    'deck_Image' => $data['deck_Image']
                ]
            ));

            return $response;
        } catch (\Exception $err) {
            $response = $response->withStatus(400);

            $response->getBody()->write(json_encode(['error' => $err->getMessage()]));

            return $response;
        }
    }

    public function DeleteDeck(PsrRequest $request, PsrResponse $response)
    {
        $token = $request->getHeader('Authorization')[0] ?? null;

        $deck_ID = $request->getAttribute('deck_ID');

        try {
            $deck = new AdmModel();
            $userModel = new UserModel();

            $user = $userModel->ValidateToken($token);

            $result = $deck->DeleteDeck($deck_ID);

            if ($result) {
                $response = $response->withStatus(200);
                $response->getBody()->write(json_encode(
                    [
                        'sucess' => "Excluido com sucesso.",
                        'status' => 200
                    ]
                ));
                return $response;
            } else {
                $response = $response->withStatus(400);
                $response->getBody()->write(json_encode([
                    'error' => "Falha ao excluir o deck.",
                    'status' => 400
                ]));
            }

            return $response;
        } catch (\Exception $err) {
            $response = $response->withStatus(400);

            $response->getBody()->write(json_encode(['error' => $err->getMessage()]));

            return $response;
        }
    }

    public function GetDeck(PsrRequest $request, PsrResponse $response)
    {
        $token = $request->getHeader('Authorization')[0] ?? null;

        $deck_ID = $request->getAttribute('deck_ID');

        try {
            $deck = new AdmModel();
            $userModel = new UserModel();

            $user = $userModel->ValidateToken($token);

            $deckData = $deck->GetDeck($deck_ID);

            if (!$deckData) {
                $response = $response->withStatus(404);
                $response->getBody()->write(json_encode([
                    'error' => "Deck não encontrado.",
                    'status' => 404
                ]));
                return $response;
            }

            $response = $response->withStatus(200);
            $response->getBody()->write(json_encode($deckData));

            return $response;
        } catch (\Exception $err) {
            $response = $response->withStatus(400);

            $response->getBody()->write(json_encode(['error' => $err->getMessage()]));

            return $response;
        }
    }

    public function EditDeck(PsrRequest $request, PsrResponse $response)
    {
        $token = $request->getHeader('Authorization')[0] ?? null;
        try {
            $userModel = new UserModel();
            $deckModel = new AdmModel();

            $userModel->ValidateToken($token);

            $deck_ID = $request->getAttribute('deck_ID');

            $bodyContent = $request->getBody();
            $data = json_decode($bodyContent, true);

            $rules = \validation\AdmValidation::deckEdit();

            $errors = [];

            if (isset($data['deck_Is_Available']) && !$rules['deck_Is_Available']->validate($data['deck_Is_Available'])) {
                $errors[] = 'O campo "deck_Is_Available" deve ser do tipo booleano.';
            }

            if (isset($data['deck_Image']) && !$rules['deck_Image']->validate($data['deck_Image'])) {
                $errors[] = 'O campo "deck_Image" é inválido.';
            }


            if (!empty($errors)) {
                $response = $response->withStatus(400);
                $response->getBody()->write(json_encode(['error' => $errors, Responses::ERR_BAD_REQUEST]));
                return $response;
            }

            $deck = $deckModel->GetDeck($deck_ID);

            if (!$deck) {
                $response = $response->withStatus(404);
                $response->getBody()->write(json_encode(['error' => "Deck nao encontrado.",]));
                return $response;
            }

            $deck_Image = $data['deck_Image'] ?? $deck['deck_Image'];
            $deck_Is_Available = isset($data['deck_Is_Available']) ? (int) $data['deck_Is_Available'] : (int) $deck['deck_Is_Available'];

            $deck_Name = $data['deck_Name'] ?? $deck['deck_Name'];

            $updated = $deckModel->EditDeck($deck_ID, $deck_Is_Available, $deck_Image);

            if (!$updated) {
                $response = $response->withStatus(400);
                $response->getBody()->write(json_encode(['error' => "Falha ao editar o deck."]));
                return $response;
            }

            $response = $response->withStatus(200);
            $response->getBody()->write(json_encode([
                'deck_ID' => $deck_ID,
                'deck_Name' => $deck_Name,
                'deck_Is_Available' => (bool)$deck_Is_Available,
                'deck_Image' => $deck_Image
            ]));

            return $response;
        } catch (\Exception $err) {
            $response = $response->withStatus(400);

            $response->getBody()->write(json_encode(['error' => $err->getMessage()]));

            return $response;
        }
    }

    public function GetDecks(PsrRequest $request, PsrResponse $response)
    {
        $token = $request->getHeader('Authorization')[0] ?? null;

        try {
            $deck = new AdmModel();
            $userModel = new UserModel();

            $user = $userModel->ValidateToken($token);

            $decks = $deck->GetDecks();

            if (!$decks) {
                $response = $response->withStatus(404);
                $response->getBody()->write(json_encode([
                    'error' => "Decks nao encontrados.",
                    'status' => 404
                ]));
                return $response;
            }

            $response = $response->withStatus(200);
            $response->getBody()->write(json_encode($decks));

            return $response;
        } catch (\Exception $err) {
            $response = $response->withStatus(400);

            $response->getBody()->write(json_encode(['error' => $err->getMessage()]));

            return $response;
        }
    }

    //

    public function CreateLetter(PsrRequest $request, PsrResponse $response)
    {
        $token = $request->getHeader('Authorization')[0] ?? null;

        try {
            $deckModel = new AdmModel();
            $userModel = new UserModel();

            $user = $userModel->ValidateToken($token);

            $deck_ID = $request->getAttribute('deck_ID');

            $bodyContent = $request->getBody();
            $data = json_decode($bodyContent, true);

            $rule = \validation\AdmValidation::letterCreate();

            if (!isset($data['attributes']) || !is_array($data['attributes']) || count($data['attributes']) !== 5) {
                return Messages::Error400($response, ['É obrigatorio o envio de todos os atributos associados do deck selecionado.']);
            }

            $errors = [];

            if (!isset($data['letter_Name']) || !$rule['letter_Name']->validate($data['letter_Name'])) {
                $errors[] = 'Nome inválido ou ausente.';
            }

            if (!isset($data['letter_Image']) || !$rule['letter_Image']->validate($data['letter_Image'])) {
                $errors[] = 'Imagem inválida ou ausente.';
            }

            if (count($errors) > 0) {
                return Messages::Error400($response, $errors);
            }

            $letter_Name = $data['letter_Name'] ?? null;
            $letter_Image = $data['letter_Image'] ?? null;
            $attributes = $data['attributes'] ?? null;

            $deckAttributes = $deckModel->GetDeckAttributes($deck_ID);

            if (empty($deckAttributes)) {
                $response = $response->withStatus(404);
                $response->getBody()->write(json_encode([Responses::ERR_BAD_REQUEST]));
            }

            $deckAttributesIDs = array_column($deckAttributes, 'attribute_ID');

            foreach ($attributes as $attribute) {
                if (!in_array($attribute['attribute_ID'], $deckAttributesIDs)) {
                    return Messages::Error400($response, ['Os atributos enviados não correspondem aos atributos do deck.']);
                }
            }

            $letter_ID = $deckModel->InsertNewLetter($letter_Name, $letter_Image, $deck_ID);

            $deckModel->InsertLetterAttributes($letter_ID, $attributes);

            $response = $response->withStatus(201);
            $response->getBody()->write(json_encode([
                'letter_ID' => $letter_ID,
                'letter_Name' => $letter_Name,
                'letter_Image' => $letter_Image,
                'attributes' => $attributes
            ]));

            return $response;
        } catch (\Exception $err) {
            $response = $response->withStatus(400);
            $response->getBody()->write(json_encode(['error' => $err->getMessage()]));
            return $response;
        }
    }

    public function EditLetter(PsrRequest $request, PsrResponse $response)
    {
        $token = $request->getHeader('Authorization')[0] ?? null;

        try {
            $userModel = new UserModel();
            $deckModel = new AdmModel();

            $userModel->ValidateToken($token);

            $deck_ID = $request->getAttribute('deck_ID');
            $letter_ID = $request->getAttribute('letter_ID');

            $bodyContent = $request->getBody();
            $data = json_decode($bodyContent, true);

            $rules = \validation\AdmValidation::letterEdit();

            $errors = [];

            if (isset($data['letter_Name']) && !$rules['letter_Name']->validate($data['letter_Name'])) {
                $errors[] = 'Nome inválido ou ausente.';
            }

            if (isset($data['letter_Image']) && !$rules['letter_Image']->validate($data['letter_Image'])) {
                $errors[] = 'Imagem inválida ou ausente.';
            }

            if (isset($data['attributes']) && !is_array($data['attributes'])) {
                $errors[] = 'Atributos inválidos ou ausentes.';
            }

            if (count($errors) > 0) {
                return Messages::Error400($response, $errors);
            }

            $attributes = $data['attributes'] ?? null;

            foreach ($attributes as $attribute) {
                if (!isset($attribute['attribute_ID']) || !isset($attribute['attribute_Value'])) {
                    continue;
                }

                $deckModel->EditLetterAttribute(
                    $letter_ID,
                    $attribute['attribute_ID'],
                    $attribute['attribute_Value']
                );
            }

            $updatedLetter = $deckModel->getLetter($letter_ID);

            $response = $response->withStatus(200);
            $response->getBody()->write(json_encode($updatedLetter));

            return $response;
        } catch (\Exception $err) {
            $response = $response->withStatus(400);
            $response->getBody()->write(json_encode(['error' => $err->getMessage()]));
            return $response;
        }
    }

    public function GetLetters(PsrRequest $request, PsrResponse $response)
    {
        $token = $request->getHeader('Authorization')[0] ?? null;

        try {
            $deckModel = new AdmModel();
            $userModel = new UserModel();

            $userModel->ValidateToken($token);

            $deck_ID = $request->getAttribute('deck_ID');

            $letters = $deckModel->GetLetters($deck_ID);

            if (!$letters) {
                $response = $response->withStatus(404);
                $response->getBody()->write(json_encode([Responses::ERR_NOT_FOUND]));
                return $response;
            }

            $response = $response->withStatus(200);
            $response->getBody()->write(json_encode($letters));

            return $response;
        } catch (\Exception $err) {
            $response = $response->withStatus(400);
            $response->getBody()->write(json_encode(['error' => $err->getMessage()]));
            return $response;
        }
    }
}
