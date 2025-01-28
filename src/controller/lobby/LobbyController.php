<?php

namespace controller\lobby;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use model\lobby\LobbyModel;
use model\lobby\MatchModel;
use model\adm\DeckModel;
use model\user\UserModel;

use response\Messages;

use Exception;
use validation\LobbyValidation;

use function Ramsey\Uuid\v1;

class LobbyController
{
    public function CreateLobby(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');

        $data = json_decode($request->getBody()->getContents(), true);

        $lobbyName = $data['lobby_Name'];
        $deckID = $data['deck_ID'];

        $deck = DeckModel::GetDeck($deckID);
        $deckIsAvailable = $deck['deck_Is_Available'];

        $rules = LobbyValidation::LobbyCreate();

        $playerInLobby = LobbyModel::VerifyPlayerInLobby($user['user_ID']);

        $errors = [];

        if ($playerInLobby) {
            $errors[] = 'Você já está no lobby, saia do lobby atual para criar outro.';
        }

        if (!$rules['lobby_Name']->validate($data['lobby_Name'])) {
            $errors[] = 'Nome do lobby é obrigatório e deve conter no mínimo 3 e no maximo 50 caracteres.';
        }

        if (empty($deckID)) {
            $errors[] = 'É obrigatório selecionar um deck.';
        }

        if (!$deck) {
            $errors[] = 'Deck não encontrado.';
        }

        if ($deckIsAvailable === false) {
            $errors[] = 'Deck não disponivel.';
        }

        if (count($errors) > 0) {
            return Messages::Error400($response, $errors);
        }

        // Cria Lobby e insere o host no lobby
        $lobby = LobbyModel::CreateLobby(
            $lobbyName,
            $user['user_ID'],
            $deckID
        );

        // Busca o lobby completo com a lista de players do lobby
        $createdLobby = LobbyModel::GetLobby($lobby);

        $response->getBody()->write(json_encode($createdLobby));
        return $response->withStatus(201);
    }

    public function GetLobbys(Request $request, Response $response)
    {
        $lobbys = LobbyModel::GetLobbys();

        if (!$lobbys || count($lobbys) == 0) {
            return Messages::Error404($response, ['Nenhum lobby encontrado ou criado.']);
        }

        $response->getBody()->write(json_encode([
            'lobbies' => $lobbys
        ]));

        return $response->withStatus(200);
    }

    public function JoinLobby(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $lobbyID = $request->getAttribute('lobby_ID');

        $lobbyData = LobbyModel::GetLobby($lobbyID);
        $lobbyPlayers = LobbyModel::GetTotalPlayersLobby($lobbyID);
        $playerInLobby = LobbyModel::VerifyPlayerInLobby($user['user_ID']);

        $errors = [];

        if ($playerInLobby) {
            if ($playerInLobby['lobby_ID'] == $lobbyID) {
                $errors[] = 'Vocé já esta no lobby atual.';
            } else {
                $errors[] = 'Vocé já está no lobby, saia do lobby atual para entrar em outro.';
            }
        }

        if (!$lobbyData) {
            $errors[] = 'Lobby não encontrado.';
        }

        if (count($lobbyPlayers) >= 30) {
            $errors[] = 'Lobby cheio.';
        }

        if ($lobbyData['lobby_Available'] === 0) {
            $errors[] = 'Lobby fechado.';
        }

        if (count($errors) > 0) {
            return Messages::Error400($response, $errors);
        }

        LobbyModel::JoinLoby($user['user_ID'], $lobbyID);

        $response->getBody()->write(json_encode([
            'players' => LobbyModel::GetLobbyPlayers($lobbyID)
        ]));
        return $response->withStatus(200);
    }

    public function RemovePlayer(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $lobbyID  = $request->getAttribute('lobby_ID');

        $data = json_decode($request->getBody()->getContents(), true);

        $playerID = $data['user_ID'];

        $lobbyData  = LobbyModel::GetLobby($lobbyID);
        $lobbyHost = LobbyModel::CheckLobbyHost($lobbyID);

        $isHost = ($user['user_ID'] == $lobbyHost);
        $isPlayer = ($user['user_ID'] == $playerID);

        $errors = [];

        if (!$lobbyData) {
            $errors[] = 'Lobby não encontrado.';
        }

        // Verifica se é o host ou o próprio player
        if (!$isHost && !$isPlayer) {
            $errors[] = 'Você não tem permissão para remover este jogador.';
        }

        if (count($errors) > 0) {
            return Messages::Error400($response, $errors);
        }

        LobbyModel::RemovePlayer($playerID, $lobbyID);

        $response->getBody()->write(json_encode([
            'status' => 201,
            'message' => 'Jogador removido com sucesso.',
            'errors' => '',
        ]));
        return $response->withStatus(200);
    }

    public function EditLobby(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $lobbyID = $request->getAttribute('lobby_ID');

        $data = json_decode($request->getBody()->getContents(), true);

        $lobbyData = LobbyModel::GetLobby($lobbyID);
        $lobbyHost = LobbyModel::CheckLobbyHost($lobbyID);

        $lobbyName = $data['lobby_Name'] ?? $lobbyData['lobby_Name'];
        $lobbyAvailable = (int)$data['lobby_Available'] ?? $lobbyData['lobby_Available'];
        $deckID = $data['deck_ID'] ?? $lobbyData['deck_ID'];

        $deckData = DeckModel::GetDeck($deckID);

        $rules = LobbyValidation::EditLobby();

        $isHost = ($user['user_ID'] == $lobbyHost);

        $errors = [];

        if ($isHost) {
            if (!$lobbyID) {
                $errors[] = 'Lobby não encontrado ou ID incorreto.';
            }

            if (!$rules['lobby_Name']->validate($data['lobby_Name'])) {
                $errors[] = 'Nome inválido ou ausente.';
            }

            if (!$rules['lobby_Available']->validate($data['lobby_Available'])) {
                $errors[] = 'O valor deve ser booleano.';
            }

            if (isset($data['deck_ID'])) {
                if (($data['deck_ID']) && !$rules['deck_ID']->validate($data['deck_ID'])) {
                    $errors[] = 'Deck inválido ou ausente.';
                } elseif (!$deckData) {
                    $errors[] = 'Deck inexistente';
                } elseif ($deckData['deck_Is_Available'] === 0) {
                    $errors[] = 'Deck indisponível';
                }
            }

            if ($lobbyData['lobby_Status'] != 'Aguardando') {
                $errors[] = 'Confuração do lobby desativada durante o jogo.';
            }
        } else {
            $errors[] = 'Você precisa ser o host do lobby para editar o lobby.';
        }

        if (count($errors) > 0) {
            return Messages::Error400($response, $errors);
        }

        LobbyModel::EditLobby(
            $lobbyID,
            $lobbyName,
            $lobbyAvailable,
            $deckID
        );

        $response->getBody()->write(json_encode(LobbyModel::GetLobby($lobbyID)));
        return $response->withStatus(200);
    }

    public function DeleteLobby(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $lobbyID = $request->getAttribute('lobby_ID');
        
        $lobbyData = LobbyModel::GetLobby($lobbyID);
        $lobbyHost = LobbyModel::CheckLobbyHost($lobbyID);

        $isHost = ($user['user_ID'] == $lobbyHost);

        $errors = [];

        if ($isHost) {
            if (!$lobbyData) {
                $errors[] = 'Lobby não encontrado ou ID incorreto.';
            }
        } else {
            $errors[] = 'Você precisa ser o host do lobby para deletar o lobby.';
        }

        if (count($errors) > 0) {
            return Messages::Error400($response, $errors);
        }

        LobbyModel::DeleteLobby($lobbyID);

        $response->getBody()->write(json_encode([
            'status' => 201,
            'message' => 'Lobby deletado com sucesso.',
            'errors' => '',
        ]));
        return $response->withStatus(200);
    }

    // PAREI AQUI //

    //--//--//--//--//--//--//--//--//--//

    public function StartLobby(Request $request, Response $response)
    {
        try {
            $token = $request->getHeader('Authorization')[0] ?? null;

            $userModel = new UserModel();
            $user = $userModel->ValidateToken($token);

            $lobbyID = $request->getAttribute('lobby_ID');

            $lobbyModel = new LobbyModel();

            $lobbyExists = $lobbyModel->GetLobby($lobbyID);

            if (!$lobbyExists) {
                return Messages::Error400($response, ['Lobby não encontrado.']);
            }

            $isHost = ($user['user_ID'] == $lobbyExists['lobby_Host_User_ID']);

            if (!$isHost) {
                return Messages::Error400($response, [' Vocé não é o host deste lobby.']);
            }

            if (count(LobbyModel::GetLobbyPlayers($lobbyID)) < 2) {
                return Messages::Error400($response, ['Lobby precisa ter pelo menos 2 jogadores.']);
            }

            $ok = LobbyModel::StartLobby($lobbyID);

            if ($ok) {
                $response->getBody()->write(json_encode(["message" => "Partida iniciada com sucesso."]));
                return $response->withStatus(200);
            }

            return Messages::Error400($response, ['Falha ao iniciar o lobby.']);
        } catch (Exception $err) {
            return Messages::Error400($response, $err->getMessage());
        }
    }

    //--//--//--//--//--//--//--//--//--//

    public function StartMatch(Request $request, Response $response)
    {
        try {
            $token = $request->getHeader('Authorization')[0] ?? null;

            $userModel = new UserModel();
            $userModel->ValidateToken($token);

            $lobbyID = $request->getAttribute('lobby_ID');

            $lobbyModel = new LobbyModel();
            $lobbyData = $lobbyModel->GetLobby($lobbyID);

            $lobbyPlayers = $lobbyModel->GetLobbyPlayers($lobbyID);

            if (!$lobbyData) {
                return Messages::Error400($response, ['Lobby nao encontrado ou ID passado incorreto.']);
            }

            if ($lobbyData['lobby_Status'] === 'Aguardando' || $lobbyData['lobby_Available'] === true) {
                return Messages::Error400($response, ['Não foi possivel dividir as cartas para iniciar o jogo pois o lobby não foi iniciado.']);
            }

            if (count($lobbyPlayers) < 2) {
                return Messages::Error400($response, ['Lobby precisa ter pelo menos 2 jogadores.']);
            }

            MatchModel::DistributeCardsToPlayers($lobbyID);

            $response->getBody()->write(json_encode(["message" => "Cartas distribuidas com sucesso."]));

            return $response->withStatus(200);
        } catch (Exception $err) {
            return Messages::Error400($response, $err->getMessage());
        }
    }

    //

    public function FirstPlayer(Request $request, Response $response)
    {
        try {
            $token = $request->getHeader('Authorization')[0] ?? null;

            $userModel = new UserModel();
            $user = $userModel->ValidateToken($token);

            $lobbyID = $request->getAttribute('lobby_ID');

            $data = json_decode($request->getBody()->getContents(), true);

            $attributeID = $data['attribute_ID'];

            if (!$attributeID) {
                return Messages::Error400($response, ['É necessário passar um atributo.']);
            }

            $lobbyModel = new LobbyModel();
            $result = $lobbyModel->PlayFirstCard($lobbyID, $user['user_ID'], $attributeID);

            $response->getBody()->write(json_encode($result));
            return $response->withStatus(200);
        } catch (Exception $err) {
            return Messages::Error400($response, $err->getMessage());
        }
    }

    public function PlayTurn(Request $request, Response $response)
    {
        try {
            $token = $request->getHeader('Authorization')[0] ?? null;
            $userModel = new UserModel();
            $user = $userModel->ValidateToken($token);

            $lobbyID = $request->getAttribute('lobby_ID');

            $lobbyModel = new LobbyModel();
            $result = $lobbyModel->PlayTurn($lobbyID, $user['user_ID']);

            $response->getBody()->write(json_encode($result));
            return $response->withStatus(200);
        } catch (Exception $err) {
            return Messages::Error400($response, $err->getMessage());
        }
    }

    public function GetWinner(Request $request, Response $response)
    {
        try {
            $token = $request->getHeader('Authorization')[0] ?? null;
            $userModel = new UserModel();
            $user = $userModel->ValidateToken($token);

            $lobbyID = $request->getAttribute('lobby_ID');

            $lobbyModel = new LobbyModel();
            $result = $lobbyModel->DetermineWinner($lobbyID);

            if (isset($result['message'])) {
                $response->getBody()->write(json_encode([
                    'message' => $result['message']
                ]));
                return $response->withStatus(200);
            }

            $transferCards = $lobbyModel->TransferCardsToWinner($lobbyID, $result['winner_user_id']);

            $response->getBody()->write(json_encode([
                'winner' => [
                    'user_name' => $result['winner_user_name'],
                    'letter_name' => $result['winner_letter_name'],
                    'player_letter_ID' => $result['winner_letter_ID'],
                ],
                'transfer_result' => $transferCards['message'],
            ]));

            return $response->withStatus(200);
        } catch (Exception $err) {
            return Messages::Error400($response, $err->getMessage());
        }
    }
}
