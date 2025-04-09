<?php

namespace controller\user;

use Ramsey\Uuid\Guid\Guid;
use Carbon\Carbon;

use Psr\Http\Message\ResponseInterface as PsrResponse;
use Psr\Http\Message\ServerRequestInterface as PsrRequest;

use response\Response;

use model\user\UserModel;
use validation\UserValidation;

class UserController
{
    public function Cadaster(PsrRequest $request, PsrResponse $response)
    {
        $data = json_decode($request->getBody()->getContents(), true);

        $rules = UserValidation::userCadaster();

        if (!$rules['user_Name']->validate($data['user_Name'])) {
            return Response::Return422($response, 'Campo nome é obrigatório e deve conter no mínimo 3 e no máximo 50 caracteres.');
        }

        if (!$rules['user_Email']->validate($data['user_Email'])) {
            return Response::Return422($response, 'Campo email inválido ou ausente.');
        } elseif (UserModel::CheckUsedEmails($data['user_Email'])) {
            return Response::Return409($response, 'E-mail já em uso.');
        }

        if (!$rules['user_Password']->validate($data['user_Password'])) {
            return Response::Return422($response, 'Campo senha é obrigatório e deve conter no mínimo 6 caracteres, 1 letra e 1 caractere especial.');
        }

        UserModel::Cadaster(
            $data['user_Name'],
            $data['user_Email'],
            $data['user_Password'],
            $data['user_Is_Admin'],
            $data['user_Status']
        );

        $response = Response::Return201($response, 'Cadastro realizado com sucesso.');
        return $response->withStatus(201);
    }

    public function Login(PsrRequest $request, PsrResponse $response)
    {
        $data = json_decode($request->getBody()->getContents(), true);

        $rules = UserValidation::userLogin();

        if (!$rules['user_Email']->validate($data['user_Email'])) {
            return Response::Return401($response, 'Email inválido ou ausente.');
        }

        if (!$rules['user_Password']->validate($data['user_Password'])) {
            return Response::Return401($response, 'Senha inválida ou ausente.');
        }

        $uuid = Guid::uuid4()->toString();

        $expiration = Carbon::now('America/Sao_Paulo')->addHours(24);

        $userData = UserModel::Login(
            $data['user_Email'],
            $data['user_Password'],
            $uuid,
            $expiration
        );

        if (!$userData) {
            return Response::Return401($response, ['Email ou senha incorreto.']);
        }

        $response->getBody()->write(json_encode($userData));

        return $response->withStatus(200);
    }

    public function GetUser(PsrRequest $request, PsrResponse $response)
    {
        $user = $request->getAttribute('user');
        $userID = $user['user_ID'];

        $userData = UserModel::GetUser($userID);

        if (!$userData) {
            return Response::Return404($response, 'Usuário não encontrado.');
        }

        $response->getBody()->write(json_encode($userData));
        return $response->withStatus(200);
    }

    public function Edit(PsrRequest $request, PsrResponse $response)
    {
        $user = $request->getAttribute('user');

        $userEmail = $user['user_Email'];
        $userPassword = $user['user_Password'];

        $data = $request->getParsedBody();
        $files = $request->getUploadedFiles();

        $rules = UserValidation::userEdit();

        if (!isset($data['user_Password'])) {
            return Response::Return400($response, 'É necessário informar a senha atual para fazer qualquer alteração.');
        } elseif ($data['user_Password'] !== $userPassword) {
            return Response::Return400($response, 'Senha atual inválida.');
        }

        if (!$rules['user_Name']->validate($data['user_Name'])) {
            return Response::Return422($response, 'Campo nome deve conter no mínimo 3 e no máximo 50 caracteres.');
        }

        if (!$rules['user_Email']->validate($data['user_Email'])) {
            return Response::Return422($response, 'Campo email inválido.');
        } elseif ($data['user_Email'] !== $userEmail && UserModel::CheckUsedEmails($data['user_Email'])) {
            return Response::Return400($response, 'Email já em uso.');
        }

        if (!$rules['user_New_Password']->validate($data['user_New_Password'])) {
            return Response::Return422($response, 'A senha deve conter no mínimo 6 caracteres, 1 letra e 1 caractere especial.');
        }

        // Extrai os dados ou usa os valores atuais caso não tenham sido passados
        $user_Name = $data['user_Name'] ?? $user['user_Name'];
        $user_Email = $data['user_Email'] ?? $user['user_Email'];
        $user_Image = $user['user_Image'];
        $user_New_Password = $data['user_New_Password'];

        if (isset($files['user_Image'])) {
            $media = $files['user_Image']->getClientMediaType();
            $allowedTypes = ['image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png'];

            if (!in_array($media, $allowedTypes)) {
                return Response::Return422($response, 'Formato de imagem inválida, tente novamente no formato PNG ou JPEG.');
            }
            
            $image = $files['user_Image'];

            $basePath = dirname(__DIR__); // Raiz do projeto
            $uploadDir = $basePath . '../../uploads/usersimage/'; // Nome correto

            // Cria a pasta se não existir
            //if (!is_dir($uploadDir)) {
            //    mkdir($uploadDir, 0755, true);
            //}

            $filename = uniqid() . '-' . $image->getClientFilename();
            $absolutePath = $uploadDir . $filename;

            $image->moveTo($absolutePath);

            $user_Image = '/uploads/usersImage/' . $filename; // Mantenha consistente
        }

        UserModel::Edit(
            $user['user_ID'],
            $user_Name,
            $user_Email,
            $user_Image,
            $user_New_Password ?? $userPassword
        );

        $response = Response::Return200($response, 'Conta atualizada com sucesso');
        return $response->withStatus(200);
    }

    public function Delete(PsrRequest $request, PsrResponse $response)
    {
        $user = $request->getAttribute('user');

        $userEmail = $user['user_Email'];
        $userPassword = $user['user_Password'];

        $data = json_decode($request->getBody()->getContents(), true);

        if (isset($data['user_Email'])) {
            if ($userEmail !== $data['user_Email']) {
                return Response::Return409($response, 'Email incorreto.');
            }
        } else {
            return Response::Return400($response, 'Email ausente.');
        }

        if (isset($data['user_Password'])) {
            if ($userPassword !== $data['user_Password']) {
                return Response::Return409($response, 'Senha incorreta.');
            }
        } else {
            return Response::Return400($response, 'Senha ausente.');
        }

        UserModel::DeleteUser($user['user_ID']);

        $response = Response::Return200($response, 'Conta deletada com sucesso');
        return $response->withStatus(200);
    }
}
