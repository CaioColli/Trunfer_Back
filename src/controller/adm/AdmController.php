<?php

namespace controller\adm;

use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;

use model\adm\AdmModel;
use response\Messages;

class AdmController
{
    public function CreateDeck(PsrRequest $request, PsrResponse $response)
    {
        $bodyContent = $request->getBody();
        $data = json_decode($bodyContent, true);

        $rule = \validation\AdmValidation::DeckCreate();

        $errors = [];

        if (!isset($data['deck_Name']) || !$rule['deck_Name']->validate($data['deck_Name'])) {
            $errors[] = 'Nome inválido ou ausente.';
        }

        if (!isset($data['deck_Image']) || !$rule['deck_Image']->validate($data['deck_Image'])) {
            $errors[] = 'Imagem inválida ou ausente.';
        }

        if (!isset($data['attributes']) || !is_array($data['attributes']) || count($data['attributes']) !== 5) {
            $errors[] = 'Erro ao enviar os atributos, tente novamente enviando 5 atributos.';
        }

        if (count($errors) > 0) {
            return Messages::Error400($response, $errors);
        }

        try {
            $deck = new AdmModel();

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
            throw $err;
        }
    }

    public function DeleteDeck(PsrRequest $request, PsrResponse $response)
    {
        $bodyContent = $request->getBody();
        $data = json_decode($bodyContent, true);

        try {
            $deck = new AdmModel();

            $result = $deck->DeleteDeck($data['deck_ID']);

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
            throw $err;
        }
    }
}
