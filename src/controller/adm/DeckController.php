<?php

namespace controller\adm;

use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;

use model\adm\DeckModel;

use response\Messages;
use response\Responses;
use validation\AdmValidation;

class DeckController
{
    public function NewDeck(PsrRequest $request, PsrResponse $response)
    {
        $data = json_decode($request->getBody()->getContents(), true);

        $rules = AdmValidation::DeckCreate();

        if (!$rules['deck_Name']->validate($data['deck_Name'])) {
            return Messages::Return422($response, 'Nome inválido ou ausente.');
        }

        if (!$rules['deck_Image']->validate($data['deck_Image'])) {
            return Messages::Return422($response, 'Url inválida ou ausente.');
        }

        if (!$rules['attributes']->validate($data['attributes'])) {
            return Messages::Return422($response, 'Para criar o baralho deve ser enviado exatos 5 atributos com no mínimo 3 caracteres.');
        }

        if (count($data['attributes']) !== count(array_unique($data['attributes']))) {
            return Messages::Return400($response, 'Os atributos devem ter nomes diferentes.');
        }

        DeckModel::NewDeck(
            $data['deck_Name'],
            $data['deck_Image'],
            $data['attributes']
        );

        $response = Messages::Return201($response, 'Baralho criado com sucesso.');
        return $response->withStatus(201);
    }

    public function DeleteDeck(PsrRequest $request, PsrResponse $response)
    {

        $deck_ID = $request->getAttribute('deck_ID');

        $deckData = DeckModel::GetDeck($deck_ID);

        if (!$deckData) {
            return Messages::Return404($response, 'Baralho não encontrado.');
        }

        DeckModel::DeleteDeck($deck_ID);

        $response = Messages::Return200($response, 'Baralho deletado com sucesso.');
        return $response;
    }

    public function EditDeck(PsrRequest $request, PsrResponse $response)
    {
        $deckModel = new DeckModel();

        $deckID = $request->getAttribute('deck_ID');
        $deckData = DeckModel::GetDeck($deckID);

        if (!$deckData) {
            return Messages::Return401($response, 'Baralho não encontrado.');
        }

        $data = json_decode($request->getBody()->getContents(), true);

        $rules = AdmValidation::DeckEdit();

        $deck_Name = $data['deck_Name'] ?? $deckData['deck_Name'];
        $deck_Is_Available = isset($data['deck_Is_Available']) ? (int) $data['deck_Is_Available'] : (int) $deckData['deck_Is_Available'];
        $deck_Image = $data['deck_Image'] ?? $deckData['deck_Image'];

        $first_Attribute  = $data['attributes']['first_Attribute']  ?? $deckData['first_Attribute'];
        $second_Attribute = $data['attributes']['second_Attribute'] ?? $deckData['second_Attribute'];
        $third_Attribute  = $data['attributes']['third_Attribute']  ?? $deckData['third_Attribute'];
        $fourth_Attribute = $data['attributes']['fourth_Attribute'] ?? $deckData['fourth_Attribute'];
        $fifth_Attribute  = $data['attributes']['fifth_Attribute']  ?? $deckData['fifth_Attribute'];

        if (!$rules['deck_Is_Available']->validate($data['deck_Is_Available'])) {
            return Messages::Return422($response, 'O campo deve ser do tipo booleano.');
        }

        if (!$rules['deck_Image']->validate($data['deck_Image'])) {
            return Messages::Return400($response, 'Url inválida ou ausente.');
        }

        if (isset($data['attributes']) && is_array($data['attributes'])) {
            if (count($data['attributes']) !== count(array_unique($data['attributes']))) {
                return Messages::Return400($response, 'Os atributos devem ter nomes diferentes.');
            }
        }

        $updated = $deckModel->EditDeck($deckID, $deck_Name, $deck_Is_Available, $deck_Image, $first_Attribute, $second_Attribute, $third_Attribute, $fourth_Attribute, $fifth_Attribute);

        if (!$updated) {
            return Messages::Return400($response, 'Falha ao editar o baralho.');
        }

        $response = Messages::Return200($response, 'Baralho editado com sucesso.');
        return $response->withStatus(200);
    }

    public function GetDeck(PsrRequest $request, PsrResponse $response)
    {

        $deck_ID = $request->getAttribute('deck_ID');

        $deckData = DeckModel::GetDeck($deck_ID);

        if (!$deckData) {
            $response->getBody()->write(json_encode(Responses::ERR_NOT_FOUND));
            return $response->withStatus(404);
        }

        $response->getBody()->write(json_encode($deckData));
        return $response->withStatus(200);
    }

    public function GetDecks(PsrRequest $request, PsrResponse $response)
    {
        $decks = DeckModel::GetDecks();

        if (!$decks) {
            $response->getBody()->write(json_encode(Responses::ERR_NOT_FOUND));
            return $response->withStatus(404);
        }

        $response->getBody()->write(json_encode([
            'decks' => $decks
        ]));
        return $response->withStatus(200);
    }
}
