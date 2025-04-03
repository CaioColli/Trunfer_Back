<?php

namespace controller\adm;

use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;

use model\adm\DeckModel;
use model\adm\CardModel;

use response\Response;
use validation\AdmValidation;

class CardController
{
    // Inacabado, falta validação de quantidade máxima de cartas por deck
    public function NewCard(PsrRequest $request, PsrResponse $response)
    {   
        $deckID = $request->getAttribute('deck_ID');

        $data = json_decode($request->getBody()->getContents(), true);

        $rules = AdmValidation::CardCreate();

        if (!$deckID) {
            return Response::Return404($response, 'Baralho não encontrado.');
        }

        if (!$rules['card_Name']->validate($data['card_Name'])) {
            return Response::Return422($response, 'Nome inválido ou ausente.');
        }

        if (!$rules['card_Image']->validate($data['card_Image'])) {
            return Response::Return422($response, 'Url inválida ou ausente.');
        }

        if (!$rules['attributes']->validate($data['attributes'])) {
            return Response::Return422($response, 'Para criar a carta deve ser enviado exatos 5 atributos.');
        }

        $cardsQuantities = CardModel::GetCards($deckID);

        if (is_array($cardsQuantities) &&  count($cardsQuantities) >= 30) {
            return Response::Return400($response, ['A quantidade máxima de 30 cartas no deck foi atingida, para inserir uma nova carta, remova uma existente.']);
        }

        CardModel::NewCard(
            $data['card_Name'],
            $data['card_Image'],
            $deckID,
            $data['attributes']
        );

        $response = Response::Return201($response, 'Carta criada com sucesso.');
        return $response->withStatus(201);
    }

    public function EditCard(PsrRequest $request, PsrResponse $response)
    {
        $deckID = $request->getAttribute('deck_ID');
        $deckData = DeckModel::GetDeck($deckID);
        $cardID = $request->getAttribute('card_ID');
        $cardData = CardModel::GetCard($cardID);

        if (!$deckData) {
            return Response::Return404($response, 'Baralho não encontrado.');
        }

        if (!$cardData) {
            return Response::Return404($response, 'Carta não encontrada.');
        }

        $data = json_decode($request->getBody()->getContents(), true);

        $rules = AdmValidation::CardEdit();
        
        $card_Name = $data['card_Name'] ?? $cardData['card_Name'];
        $card_Image = $data['card_Image'] ?? $cardData['card_Image'];

        $first_Attribute_Value = $data['attributes']['first_Attribute_Value'] ?? $cardData['first_Attribute_Value'];
        $second_Attribute_Value = $data['attributes']['second_Attribute_Value'] ?? $cardData['second_Attribute_Value'];
        $third_Attribute_Value = $data['attributes']['third_Attribute_Value'] ?? $cardData['third_Attribute_Value'];
        $fourth_Attribute_Value = $data['attributes']['fourth_Attribute_Value'] ?? $cardData['fourth_Attribute_Value'];
        $fifth_Attribute_Value = $data['attributes']['fifth_Attribute_Value'] ?? $cardData['fifth_Attribute_Value'];

        if (isset($data['card_Name']) && !$rules['card_Name']->validate($data['card_Name'])) {
            return Response::Return422($response, 'Nome inválido ou ausente.');
        }

        if (isset($data['card_Image']) && !$rules['card_Image']->validate($data['card_Image'])) {
            return Response::Return422($response, 'Url inválida ou ausente.');
        }

        $updated = CardModel::EditCard($cardID, $deckID, $card_Name, $card_Image, $first_Attribute_Value, $second_Attribute_Value, $third_Attribute_Value, $fourth_Attribute_Value, $fifth_Attribute_Value);

        if (!$updated) {
            return Response::Return400($response, 'Falha ao editar carta.');
        }

        $response = Response::Return200($response, 'Carta editada com sucesso.');
        return $response->withStatus(200);
    }

    public function DeleteCard(PsrRequest $request, PsrResponse $response)
    {
        $deckID = $request->getAttribute('deck_ID');
        $deckData = DeckModel::GetDeck($deckID);
        $cardID = $request->getAttribute('card_ID');

        if (!$deckData) {
            return Response::Return404($response, 'Baralho não encontrado.');
        }

        if (!$cardID) {
            return Response::Return404($response, 'Carta não encontrada.');
        }

        CardModel::DeleteCard($deckID, $cardID);
        
        return Response::Return200($response, 'Carta deletada com sucesso.');
    }

    public function GetCards(PsrRequest $request, PsrResponse $response)
    {
        $deckID = $request->getAttribute('deck_ID');
        $deckData = DeckModel::GetDeck($deckID);

        if (!$deckData) {
            return Response::Return404($response, 'Baralho não encontrado.');
        }

        $cards = CardModel::GetCards($deckID);

        if (!$cards) {
            return Response::Return404($response, 'carta não encontrada.');
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
            return Response::Return404($response, 'Carta não encontrada.');
        }

        $response->getBody()->write(json_encode($cardData));
        return $response->withStatus(200);
    }
}
