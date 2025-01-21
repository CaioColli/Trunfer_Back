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
use validation\UserValidation;

class UserController
{
    public function Create(PsrRequest $request, PsrResponse $response)
    {
        $data = json_decode($request->getBody()->getContents(), true);

        $rules = UserValidation::userCadaster();

        $errors = [];

        if (!$rules['user_Name']->validate($data['user_Name'])) {
            $errors[] = 'Campo nome é obrigatório e deve conter no mínimo 3 e no máximo 50 caracteres.';
        }

        if (!$rules['user_Email']->validate($data['user_Email'])) {
            $errors[] = 'Campo email inválido ou ausente.';
        } elseif (UserModel::CheckUsedEmails($data['user_Email'])) {
            $errors[] = 'E-mail ja em uso.';
        }

        if (!$rules['user_Password']->validate($data['user_Password'])) {
            $errors[] = 'Campo senha é obrigatório e deve conter no mínimo 6 caracteres, 1 letra e 1 caractere especial.';
        }

        if (!empty($errors)) {
            return Messages::Error400($response, $errors);
        }

        try {
            UserModel::NewUser(
                $data['user_Name'],
                $data['user_Email'],
                $data['user_Password'],
                $data['user_Is_Admin'],
                $data['user_Status']
            );

            $response->getBody()->write(json_encode(Responses::ACCEPT));
            return $response->withStatus(201);
        } catch (\Exception $err) {
            return Messages::Error400($response, $err);
        }
    }

    public function Login(PsrRequest $request, PsrResponse $response)
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);

            $rules = UserValidation::userLogin();

            $errors = [];

            if (!$rules['user_Email']->validate($data['user_Email'])) {
                $errors[] = 'Campo email inválido ou ausente.';
            }

            if (!$rules['user_Password']->validate($data['user_Password'])) {
                $errors[] = 'Campo senha inválido ou ausente.';
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

            $response->getBody()->write(json_encode($userData));

            return $response->withStatus(200);
        } catch (\Exception $err) {
            return Messages::Error400($response, $err);
        }
    }

    public function Edit(PsrRequest $request, PsrResponse $response)
    {
        try {
            $user = $request->getAttribute('user');

            $userPassword = $user['user_Password'];

            $data = json_decode($request->getBody()->getContents(), true);

            $rules = UserValidation::userEdit();

            $errors = [];

            if (!isset($data['user_Password'])) {
                $errors[] = 'É necessário informar a senha atual para fazer qualquer alteração.';
            } elseif ($userPassword !== $data['user_Password']) {
                $errors[] = 'Senha atual inválida.';
            }

            if (!$rules['user_Name']->validate($data['user_Name'])) {
                $errors[] = 'Campo nome deve conter no mínimo 3 e no máximo 50 caracteres.';
            }

            if (!$rules['user_Email']->validate($data['user_Email'])) {
                $errors[] = 'Campo email inválido ou ausente.';
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
            $user_New_Password = $data['user_New_Password'];

            $userData = UserModel::EditUser(
                $user['user_ID'],
                $user_Name,
                $user_Email,
                $user_Password,
                $user_New_Password
            );

            $response->getBody()->write(json_encode($userData));
            return $response->withStatus(200);
        } catch (\Exception $err) {
            return Messages::Error400($response, [$err->getMessage()]);
        }
    }

    public function Delete(PsrRequest $request, PsrResponse $response)
    {
        try {
            $user = $request->getAttribute('user');

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

            UserModel::DeleteUser($user['user_ID']);

            $response->getBody()->write(json_encode(Responses::ACCEPT));
            return $response->withStatus(200);
        } catch (\Exception $err) {
            return Messages::Error400($response, $err);
        }
    }
}
