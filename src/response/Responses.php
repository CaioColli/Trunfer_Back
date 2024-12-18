<?php 

namespace App\Response;

//use Psr\Http\Message\ResponseInterface as PsrResponse;

class Responses
{
    // 200
    const ACCEPT  = [
        "status" => 200,
        "message" => "Sucesso ao realizar operação."
    ];

    // 201

    const CREATED = [
        "status" => 201,
        "message" => "Sucesso ao criar recurso."
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

    // 403
    const ERR_FORBIDDEN = [
        "status" => 403,
        "message" => "Acesso negado."
    ];

    // 404
    const ERR_NOT_FOUND = [
        "status" => 404,
        "message" => "Não encontrado."
    ];

    const INTERNAL_ERROR = [
        "status" => 500,
        "message" => "Erro interno do servidor."
    ];
}