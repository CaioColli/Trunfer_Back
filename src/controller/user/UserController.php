<?php

namespace controller\user;

use Ramsey\Uuid\Guid\Guid;
use Carbon\Carbon;

use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;

use response\Messages;
use response\Responses;
use model\user\UserModel;
use session\Session;

class UserController
{
    public function Create(PsrRequest $request, PsrResponse $response)
    {
        $bodyContent = $request->getBody();
        $data = json_decode($bodyContent, true);

        $rules = \validation\UserValidation::userCadaster();

        $errors = [];

        if (!isset($data['user_Name']) || !$rules['user_Name']->validate($data['user_Name'])) {
            $errors[] = 'Nome inválido ou ausente.';
        }

        if (!isset($data['user_Email']) || !$rules['user_Email']->validate($data['user_Email'])) {
            $errors[] = 'Email inválido ou ausente.';
        }

        if (!isset($data['user_Password'])) {
            $errors[] = 'Senha inválida ou ausente.';
        } elseif (!$rules['user_Password']->validate($data['user_Password'])) {
            $errors[] = 'A senha deve conter no mínimo 6 caracteres, 1 letra e 1 caractere especial.';
        }

        if (!empty($errors)) {
            $response = $response->withStatus(400);
            $response->getBody()->write(json_encode(['error' => $errors, Responses::ERR_BAD_REQUEST]));
            return $response;
        }

        try {
            $user = new UserModel();

            $userData = $user->NewUser(
                $data['user_Name'],
                $data['user_Email'],
                $data['user_Password'],
                $data['user_Is_Admin'],
                $data['user_Status']
            );

            $response = $response->withStatus(201);
            $response->getBody()->write(json_encode(
                [
                    'id' => $userData,
                    'user_Name' => $data['user_Name'],
                    'user_Email' => $data['user_Email']
                ]
            ));

            return $response;
        } catch (\Exception $err) {
            // $response = $response->withStatus(400);
            // $response->getBody()->write(json_encode(['error' => $err->getMessage()]));
            // return $response;
            return Messages::Error400($response, $err);
        }
    }

    public function Login(PsrRequest $request, PsrResponse $response)
    {
        $bodyContent = $request->getBody();
        $data = json_decode($bodyContent, true);

        $rules = \validation\UserValidation::userLogin();

        $errors = [];

        if (!isset($data['user_Email']) || !$rules['user_Email']->validate($data['user_Email'])) {
            $errors[] = 'Email inválido ou ausente.';
        }

        if (!isset($data['user_Password']) || !$rules['user_Password']->validate($data['user_Password'])) {
            $errors[] = 'Senha inválida ou ausente.';
        }

        if (!empty($errors)) {
            $response = $response->withStatus(400);
            $response->getBody()->write(json_encode(['error' => $errors]));
            return $response;
        }

        try {
            $user = new UserModel();

            $uuid = Guid::uuid4()->toString();

            $expiration = Carbon::now('America/Sao_Paulo')->addHours(2);

            $userData = $user->LoginUser(
                $data['user_Email'],
                $data['user_Password'],
                $uuid,
                $expiration
            );

            if (!$userData) {
                // Se usuário não for encontrado ou senha incorreta
                $response = $response->withStatus(401);
                $response->getBody()->write(json_encode([
                    'message' => 'Usuário ou senha incorretos.'
                ]));
                return $response;
            }

            // Define o tipo de usuário na sessão
            Session::setUserType($userData['user_Is_Admin'] ? 'admin' : 'user');

            //
            //error_log("Tipo de usuário definido: " . Session::getUserType());


            unset($userData['user_Password']);

            $response = $response->withStatus(200);
            $response->getBody()->write(json_encode(
                [
                    'user_ID' => $userData['user_ID'],
                    'user_Is_Admin' => (bool)$userData['user_Is_Admin'],
                    'token' => $uuid,
                    'token_Expiration' => $expiration->toDateTimeString(),
                    'user_Name' => $userData['user_Name'],
                    'user_Email' => $userData['user_Email'],
                    'user_Status' => $userData['user_Status']
                ]
            ));

            return $response;
        } catch (\Exception $err) {
            return Messages::Error400($response, $err);
        }
    }

    public function Edit(PsrRequest $request, PsrResponse $response)
    {
        $token = $request->getHeader('Authorization')[0] ?? null;

        if (!$token) {
            $response = $response->withStatus(401);
            $response->getBody()->write(json_encode(['message' => 'Token ausente.']));
            return $response;
        }

        try {
            $User = new UserModel();
            $user = $User->ValidateToken($token);

            if (!$user) {
                $response = $response->withStatus(401);
                $response->getBody()->write(json_encode(['message' => 'Token inválido.']));
                return $response;
            }

            $bodyContent = $request->getBody();
            $data = json_decode($bodyContent, true);

            $rules = \validation\UserValidation::userEdit();

            $errors = [];

            // A senha atual precisa ser validada antes de qualquer alteração, mas sem precisar passar por regex aqui
            if (!isset($data['user_Password']) || empty($data['user_Password'])) {
                $errors[] = 'Senha atual é obrigatória para fazer qualquer alteração.';
            }

            if (isset($data['user_Name']) && !$rules['user_Name']->validate($data['user_Name'])) {
                $errors[] = 'Nome inválido ou ausente.';
            }

            if (isset($data['user_Email']) && !$rules['user_Email']->validate($data['user_Email'])) {
                $errors[] = 'Email inválido ou ausente.';
            }

            if (isset($data['user_New_Password']) && !$rules['user_New_Password']->validate($data['user_New_Password'])) {
                $errors[] = 'Senha nova inválida.';
            }

            if (!empty($errors)) {
                $response = $response->withStatus(400);
                $response->getBody()->write(json_encode(['error' => $errors]));
                return $response;
            }

            // Extrai os dados ou usa os valores atuais caso não tenham sido passados
            $user_Name = $data['user_Name'] ?? $user['user_Name'];
            $user_Email = $data['user_Email'] ?? $user['user_Email'];
            $user_Password = $data['user_Password']; // Senha atual
            $user_New_Password = $data['user_New_Password'] ?? null;

            $userData = $User->EditUser(
                $user['user_ID'],
                $user_Name,
                $user_Email,
                $user_Password,
                $user_New_Password
            );

            $response = $response->withStatus(200);
            $response->getBody()->write(json_encode([
                'id' => $userData['user_ID'],
                'user_Is_Admin' => (bool) $userData['user_Is_Admin'],
                'user_Name' => $userData['user_Name'],
                'user_Email' => $userData['user_Email'],
                'user_Status' => $userData['user_Status']
            ]));

            return $response;
        } catch (\Exception $err) {
            $response = $response->withStatus(400);
            $response->getBody()->write(json_encode(['error' => $err->getMessage()]));
            return $response;
        }
    }

    public function Delete(PsrRequest $request, PsrResponse $response)
    {
        $token = $request->getHeader('Authorization')[0] ?? null;

        if (!$token) {
            $response = $response->withStatus(401);
            $response->getBody()->write(json_encode(['message' => 'Token ausente.']));
            return $response;
        }

        try {
            $user = new UserModel();
            // Dados vindo do db
            $userData = $user->ValidateToken($token);

            if (!$userData) {
                $response = $response->withStatus(401);
                $response->getBody()->write(json_encode(['message' => 'Token inválido.']));
                return $response;
            }

            $bodyContent = $request->getBody();
            // Dados do request
            $data = json_decode($bodyContent, true);

            $rules = \validation\UserValidation::userDelete();

            $errors = [];

            if (isset($data['user_Email']) && !$rules['user_Email']->validate($data['user_Email'])) {
                $errors[] = 'Email inválido ou ausente.';
            }

            if (!isset($data['user_Password']) || empty($data['user_Password'])) {
                $errors[] = 'Senha atual inválida ou ausente.';
            }

            if (!empty($errors)) {
                $response = $response->withStatus(400);
                $response->getBody()->write(json_encode(['error' => $errors]));
                return $response;
            }

            if ($data['user_Email'] !== $userData['user_Email'] || $data['user_Password'] !== $userData['user_Password']) {
                $response = $response->withStatus(401);
                $response->getBody()->write(json_encode(['message' => 'Senha ou Email inválido.']));
                return $response;
            }

            $user->DeleteUser($userData['user_ID']);

            $response = $response->withStatus(200);
            $response->getBody()->write(json_encode(Responses::ACCEPT));

            return $response;
        } catch (\Exception $err) {
            $response = $response->withStatus(400);
            $response->getBody()->write(json_encode(['error' => $err->getMessage()]));
            return $response;
        }
    }
}
