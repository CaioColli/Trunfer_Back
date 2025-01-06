<?php

namespace controller\adm;

use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;

use model\adm\DeckModel;
use model\adm\LetterModel;

use model\user\UserModel;
use response\Messages;
use response\Responses;

class LetterController
{
    public function CreateLetter(PsrRequest $request, PsrResponse $response)
    {
        $token = $request->getHeader('Authorization')[0] ?? null;

        try {
            $deckModel = new DeckModel();
            $letterModel = new LetterModel();
            
            $userModel = new UserModel();

            $userModel->ValidateToken($token);

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

            $letter_ID = $letterModel->InsertNewLetter($letter_Name, $letter_Image, $deck_ID);

            $letterModel->InsertLetterAttributes($letter_ID, $attributes);

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
            $deckModel = new LetterModel();

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

            // Atualiza nome e imagem 
            if (isset($data['letter_Name']) || isset($data['letter_Image'])) {
                $deckModel->EditLetterDetails(
                    $letter_ID,
                    $data['letter_Name'] ?? null,
                    $data['letter_Image'] ?? null
                );
            }

            // Atualiza atributos
            $attributes = $data['attributes'] ?? null;

            if ($attributes) {
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
            }

            $updatedLetter = $deckModel->GetLetter($letter_ID, $deck_ID);

            $response = $response->withStatus(200);
            $response->getBody()->write(json_encode($updatedLetter));

            return $response;
        } catch (\Exception $err) {
            $response = $response->withStatus(400);
            $response->getBody()->write(json_encode(['error' => $err->getMessage()]));
            return $response;
        }
    }

    public function DeleteLetter(PsrRequest $request, PsrResponse $response)
    {
        $token = $request->getHeader('Authorization')[0] ?? null;

        try {
            $deckModel = new LetterModel();
            $userModel = new UserModel();

            $userModel->ValidateToken($token);

            $letter_ID = $request->getAttribute('letter_ID');

            $result = $deckModel->DeleteLetter($letter_ID);

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
                    'error' => "Falha ao excluir a carta.",
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

    public function GetLetters(PsrRequest $request, PsrResponse $response)
    {
        $token = $request->getHeader('Authorization')[0] ?? null;

        try {
            $deckModel = new LetterModel();
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

    public function GetLetter(PsrRequest $request, PsrResponse $response)
    {
        $token = $request->getHeader('Authorization')[0] ?? null;

        try {
            $deckModel = new LetterModel();
            $userModel = new UserModel();

            $userModel->ValidateToken($token);

            $deck_ID = $request->getAttribute('deck_ID');
            $letter_ID = $request->getAttribute('letter_ID');

            $deckData = $deckModel->GetLetter($letter_ID, $deck_ID);

            if (!$deckData) {
                $response = $response->withStatus(404);
                $response->getBody()->write(json_encode([
                    'error' => "Carta não encontrada.",
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
}
