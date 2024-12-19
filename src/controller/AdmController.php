<?php

namespace App\Controller;

use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;

use App\Model\AdmModel;
use response\Messages;

class AdmController
{
    public function CreateDeck(PsrRequest $request, PsrResponse $response)
    {
        $bodyContent = $request->getBody();
        $data = json_decode($bodyContent, true);

        $rule = \App\Validation\AdmValidation::DeckCreate();

        $erros = [];

        if (!isset($data['deck_Name']) || !$rule['deck_Name']->validate($data['deck_Name'])) {
            $erros[] = 'Nome inválido ou ausente.';
        }

        if (!isset($data['deck_Image']) || !$rule['deck_Image']->validate($data['deck_Image'])) {
            $erros[] = 'Imagem inválida ou ausente.';
        }

        if (count($erros) > 0) {
            return Messages::Error400($response);
        }

        try {
            $deck = new AdmModel();

            $deckData = $deck->InsertNewDeck(
                $data['deck_Name'],
                $data['deck_Image']
            );

            $response = $response->withStatus(201);
            $response->getBody()->write(json_encode(
                [
                    'deck_ID' => $deckData,
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
}
