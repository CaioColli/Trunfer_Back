<?php 

namespace App\Response;

//use Psr\Http\Message\ResponseInterface as PsrResponse;

class Responses
{
    // 200
    const TRUE  = [
        "result" => true,
        "message" => "Operação realizada com sucesso!"
    ];

    // 201
    const CREATED  = [
        "result" => true,
        "message" => "Criado com sucesso!"
    ];

    const FALSE = [
        "result" => false,
        "message" => "Erro ao realizar operação."
    ];

    // 400
    const ERR_BAD_REQUEST = [
        "status" => 400,
        "message" => "Requisição inválida."
    ];  


    // 401
    const ERR_UNAUTHORIZED = [
        "status" => 401,
        "message" => "Não autorizado."
    ];

    // 404
    const ERR_NOT_FOUND = [
        "status" => 404,
        "message" => "Recurso não encontrado."
    ];
}