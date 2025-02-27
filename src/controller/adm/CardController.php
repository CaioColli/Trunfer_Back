<?php

namespace controller\adm;

use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;

use model\adm\DeckModel;
use model\adm\CardModel;

use response\Messages;
use response\Responses;
use validation\AdmValidation;

class CardController
{
    // Inacabado, falta validação de quantidade máxima de cartas por deck
    public function NewCard(PsrRequest $request, PsrResponse $response)
    {
        $deckID = $request->getAttribute('deck_ID');

        $data = json_decode($request->getBody()->getContents(), true);

        $rules = AdmValidation::CardCreate();

        $errors = [];

        if (!$rules['card_Name']->validate($data['card_Name'])) {
            $errors[] = 'Nome inválido ou ausente.';
        }

        if (!$rules['card_Image']->validate($data['card_Image'])) {
            $errors[] = 'Url inválida ou ausente.';
        }

        if (!$rules['attributes']->validate($data['attributes'])) {
            $errors[] = 'Para criar a carta deve ser enviado exatos 5 atributos';
        }

        if (count($errors) > 0) {
            return Messages::Error400($response, $errors);
        }

        $cardsQuantities = CardModel::GetCards($deckID);

        if (is_array($cardsQuantities) &&  count($cardsQuantities) >= 30) {
            return Messages::Error422($response, ['A quantidade máxima de 30 cartas no deck foi atingida, para inserir uma nova carta, remova uma existente.']);
        }

        CardModel::NewCard(
            $data['card_Name'],
            $data['card_Image'],
            $deckID,
            $data['attributes']
        );

        $response->getBody()->write(json_encode([
            'status' => 201,
            'message' => 'Carta criada com sucesso.',
            'errors' => '',
        ]));

        return $response->withStatus(201);
    }

    public function EditCard(PsrRequest $request, PsrResponse $response)
    {
        $cardID = $request->getAttribute('card_ID');

        $data = json_decode($request->getBody()->getContents(), true);

        $rules = AdmValidation::CardEdit();

        $errors = [];

        if (isset($data['card_Name']) && !$rules['card_Name']->validate($data['card_Name'])) {
            $errors[] = 'Nome inválido ou ausente.';
        }

        if (isset($data['card_Image']) && !$rules['card_Image']->validate($data['card_Image'])) {
            $errors[] = 'Url inválida ou ausente.';
        }

        if (isset($data['attributes']) && !$rules['attributes']->validate($data['attributes'])) {
            $errors[] = 'Para editar o atributo carta deve ser enviado exatos 5 atributos';
        }

        if (isset($data['attributes']) && !is_array($data['attributes'])) {
            $errors[] = 'Atributo passado inválido';
        }

        if (count($errors) > 0) {
            return Messages::Error400($response, $errors);
        }

        // Atualiza nome e imagem 
        if (isset($data['card_Name']) || isset($data['card_Image'])) {
            CardModel::EditCard(
                $cardID,
                $data['card_Name'],
                $data['card_Image'],
                $data['attributes']
            );
        }

        $response->getBody()->write(json_encode([
            'status' => 200,
            'message' => 'Carta editada com sucesso.',
            'errors' => '',
        ]));

        return $response->withStatus(200);
    }

    public function DeleteCard(PsrRequest $request, PsrResponse $response)
    {
        $deckID = $request->getAttribute('deck_ID');
        $cardID = $request->getAttribute('card_ID');

        if (CardModel::DeleteCard($deckID, $cardID)) {
            $response->getBody()->write(json_encode([
                'status' => 200,
                'message' => 'Carta deletada com sucesso.',
                'errors' => '',
            ]));
            return $response->withStatus(200);
        } else {
            $response->getBody()->write(json_encode(Responses::ERR_BAD_REQUEST));
            return $response->withStatus(400);
        }
    }

    public function GetCards(PsrRequest $request, PsrResponse $response)
    {
        $deckID = $request->getAttribute('deck_ID');

        $cards = CardModel::GetCards($deckID);

        if (!$cards) {
            $response->getBody()->write(json_encode([Responses::ERR_NOT_FOUND]));
            return $response->withStatus(404);
        }

        $response->getBody()->write(json_encode([
            'cards' => $cards
        ]));
        return $response->withStatus(200);
    }

    public function GetCard(PsrRequest $request, PsrResponse $response)
    {
        $cardID = $request->getAttribute('card_ID');

        $cardData = CardModel::GetCard($cardID);

        if (!$cardData) {
            $response->getBody()->write(json_encode(Responses::ERR_NOT_FOUND));
            return $response->withStatus(404);
        }

        $response->getBody()->write(json_encode($cardData));
        return $response->withStatus(200);
    }
}
