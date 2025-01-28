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

        $errors = [];

        if (!$rules['deck_Name']->validate($data['deck_Name'])) {
            $errors[] = 'Nome inválido ou ausente.';
        }

        if (!$rules['deck_Image']->validate($data['deck_Image'])) {
            $errors[] = 'Url inválida ou ausente.';
        }

        if (!$rules['attributes']->validate($data['attributes'])) {
            $errors[] = 'Para criar o baralho deve ser enviado exatos 5 atributos com no mínimo 3 caracteres.';
        }

        if (count($data['attributes']) !== count(array_unique($data['attributes']))) {
            $errors[] = 'Os atributos devem ter nomes diferentes';
        }

        if (count($errors) > 0) {
            return Messages::Error400($response, $errors);
        }

        DeckModel::NewDeck(
            $data['deck_Name'],
            $data['deck_Image'],
            $data['attributes']
        );

        $response->getBody()->write(json_encode([
            'status' => 201,
            'message' => 'Baralho criado com sucesso.',
            'errors' => '',
        ]));
        return $response->withStatus(201);
    }

    public function DeleteDeck(PsrRequest $request, PsrResponse $response)
    {

        $deck_ID = $request->getAttribute('deck_ID');

        $result = DeckModel::DeleteDeck($deck_ID);

        if ($result) {
            $response->getBody()->write(json_encode(Responses::ACCEPT));
            return $response->withStatus(200);
        } else {
            $response->getBody()->write(json_encode(Responses::ERR_BAD_REQUEST));
            return $response->withStatus(400);
        }

        return $response;
    }

    public function EditDeck(PsrRequest $request, PsrResponse $response)
    {
        $deckModel = new DeckModel();

        $deckID = $request->getAttribute('deck_ID');

        $data = json_decode($request->getBody()->getContents(), true);

        $rules = AdmValidation::DeckEdit();

        $errors = [];

        if (!$rules['deck_Is_Available']->validate($data['deck_Is_Available'])) {
            $errors[] = 'O campo deve ser do tipo booleano.';
        }

        if (!$rules['deck_Image']->validate($data['deck_Image'])) {
            $errors[] = 'Url inválida ou ausente.';
        }

        if (count($errors) > 0) {
            return Messages::Error400($response, $errors);
        }

        $deckData = DeckModel::GetDeck($deckID);

        if (!$deckData) {
            $response->getBody()->write(json_encode(Responses::ERR_NOT_FOUND));
            return $response->withStatus(404);
        }

        $deck_Image = $data['deck_Image'] ?? $deckData['deck_Image'];
        $deck_Is_Available = isset($data['deck_Is_Available']) ? (int) $data['deck_Is_Available'] : (int) $deckData['deck_Is_Available'];

        $updated = $deckModel->EditDeck($deckID, $deck_Is_Available, $deck_Image);

        if (!$updated) {
            $response = $response->withStatus(400);
            $response->getBody()->write(json_encode(['error' => "Falha ao editar o deck."]));
            return $response;
        }

        $response->getBody()->write(json_encode([
            'status' => 200,
            'message' => 'Baralho editado com sucesso.',
            'errors' => '',
        ]));

        return $response->withStatus(200);
    }

    public function GetDeck(PsrRequest $request, PsrResponse $response)
    {

        $deck_ID = $request->getAttribute('deck_ID');

        $deckData = DeckModel::GetFullInfoDeck($deck_ID);

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
