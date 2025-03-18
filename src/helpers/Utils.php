<?php

namespace helpers;

use model\lobby\LobbyModel;
use response\Messages;

class Utils
{
    public static function ValidateLobby($lobbyID, $user, $response)
    {
        $lobbyData = LobbyModel::GetLobby($lobbyID);
        $lobbyPlayers = LobbyModel::GetLobbyPlayer($lobbyID, $user['user_ID']);

        if (!$lobbyData) {
            return Messages::Return404($response, 404, 'Lobby não encontrado.');
        }

        if (!$lobbyPlayers) {
            return Messages::Return401($response, 401, 'Jogador não encontrado no lobby.');
        }
    }
}
