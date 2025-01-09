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

        if (!isset($data['user_Name'])) {
            $errors[] = 'Nome ausente.';
        } elseif (!$rules['user_Name']->validate($data['user_Name'])) {
            $errors[] = 'Nome deve conter no mínimo 3 e no máximo 50 caracteres.';
        }

        if (!isset($data['user_Email'])) {
            $errors[] = 'Email ausente.';
        } elseif (!$rules['user_Email']->validate($data['user_Email'])) {
            $errors[] = 'Digite um email válido.';
        }

        if (!isset($data['user_Password'])) {
            $errors[] = 'Senha inválida ou ausente.';
        } elseif (!$rules['user_Password']->validate($data['user_Password'])) {
            $errors[] = 'A senha deve conter no mínimo 6 caracteres, 1 letra e 1 caractere especial.';
        }

        if (!empty($errors)) {
            return Messages::Error400($response, $errors);
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
                    'user_ID' => $userData,
                    'user_Name' => $data['user_Name'],
                    'user_Email' => $data['user_Email']
                ]
            ));

            return $response->withStatus(201);
        } catch (\Exception $err) {
            return Messages::Error400($response, $err);
        }
    }

    public function Login(PsrRequest $request, PsrResponse $response)
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);

            $rules = \validation\UserValidation::userLogin();

            $errors = [];

            if (!isset($data['user_Email']) || !$rules['user_Email']->validate($data['user_Email'])) {
                $errors[] = 'Email ausente.';
            }

            if (!isset($data['user_Password']) || !$rules['user_Password']->validate($data['user_Password'])) {
                $errors[] = 'Senha ausente.';
            }

            if (!empty($errors)) {
                return Messages::Error400($response, $errors);
            }

            $uuid = Guid::uuid4()->toString();

            $expiration = Carbon::now('America/Sao_Paulo')->addHours(2);

            $userData = UserModel::LoginUser(
                $data['user_Email'],
                $data['user_Password'],
                $uuid,
                $expiration
            );

            if (!$userData) {
                return Messages::Error401($response, ['Email ou senha incorreto.']);
            }

            // Define o tipo de usuário na sessão
            Session::setUserType($userData['user_Is_Admin'] ? 'admin' : 'user');

            unset($userData['user_Password']);

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

            return $response->withStatus(200);
        } catch (\Exception $err) {
            return Messages::Error400($response, $err);
        }
    }

    public function Edit(PsrRequest $request, PsrResponse $response)
    {
        try {
            $token = $request->getHeader('Authorization')[0] ?? null;

            $userModel = new UserModel();

            $user = $userModel->ValidateToken($token);
            $userPassword = $user['user_Password'];

            $data = json_decode($request->getBody()->getContents(), true);

            $rules = \validation\UserValidation::userEdit();

            $errors = [];

            if (!isset($data['user_Password'])) {
                $errors[] = 'É necessário informar a senha atual para fazer qualquer alteração.';
            } elseif ($userPassword !== $data['user_Password']) {
                $errors[] = 'Senha atual inválida.';
            }

            if (!$rules['user_Name']->validate($data['user_Name'])) {
                $errors[] = 'Nome deve conter no mínimo 3 e no máximo 50 caracteres.';
            }

            if (!$rules['user_Email']->validate($data['user_Email'])) {
                $errors[] = 'Digite um email válido.';
            }

            if (!$rules['user_New_Password']->validate($data['user_New_Password'])) {
                $errors[] = 'A senha deve conter no mínimo 6 caracteres, 1 letra e 1 caractere especial.';
            }

            if (!empty($errors)) {
                return Messages::Error400($response, $errors);
            }

            // Extrai os dados ou usa os valores atuais caso não tenham sido passados
            $user_Name = $data['user_Name'] ?? $user['user_Name'];
            $user_Email = $data['user_Email'] ?? $user['user_Email'];
            $user_Password = $data['user_Password']; // Senha atual
            $user_New_Password = $data['user_New_Password'] ?? null;

            $userData = $userModel->EditUser(
                $user['user_ID'],
                $user_Name,
                $user_Email,
                $user_Password,
                $user_New_Password
            );

            $response->getBody()->write(json_encode([
                'user_ID' => $userData['user_ID'],
                'user_Is_Admin' => (bool) $userData['user_Is_Admin'],
                'user_Name' => $userData['user_Name'],
                'user_Email' => $userData['user_Email'],
                'user_Status' => $userData['user_Status']
            ]));

            return $response->withStatus(200);
        } catch (\Exception $err) {
            return Messages::Error400($response, $err);
        }
    }

    public function Delete(PsrRequest $request, PsrResponse $response)
    {
        try {
            $token = $request->getHeader('Authorization')[0] ?? null;

            $userModel = new UserModel();
            
            $user = $userModel->ValidateToken($token);

            $userEmail = $user['user_Email'];
            $userPassword = $user['user_Password'];

            $data = json_decode($request->getBody()->getContents(), true);

            $errors = [];

            if (isset($data['user_Email'])) {
                if ($userEmail !== $data['user_Email']) {
                    $errors[] = 'Email incorreto.';
                }
            } else {
                $errors[] = 'Email ausente.';
            }

            if (isset($data['user_Password'])) {
                if ($userPassword !== $data['user_Password']) {
                    $errors[] = 'Senha incorreta.';
                }
            } else {
                $errors[] = 'Senha ausente.';
            }

            if (!empty($errors)) {
                return Messages::Error400($response, $errors);
            }

            $userModel->DeleteUser($user['user_ID']);

            $response = $response->withStatus(200);
            $response->getBody()->write(json_encode(Responses::ACCEPT));

            return $response;
        } catch (\Exception $err) {
            return Messages::Error400($response, $err);
        }
    }
}
