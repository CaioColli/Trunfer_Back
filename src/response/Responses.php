<?php 

namespace response;

class Responses
{
    // 200
    const ACCEPT  = [
        "status" => 200,
        "message" => "Sucesso ao realizar operação.",
        'errors' => "",
    ];

    // 201

    const CREATED = [
        "status" => 201,
        "message" => "Sucesso ao criar recurso.",
        'errors' => "",
    ];

    const UNAUTHORIZED = [
        "status" => 203,
        "message" => "Acesso não autorizado",
        'errors' => "",
    ];

    // 400
    const ERR_BAD_REQUEST = [
        "status" => 400,
        "message" => "Requisição inválida.",
        'errors' => "",
    ];  

    // 401
    const ERR_UNAUTHORIZED = [
        "status" => 401,
        "message" => "Não autorizado.",
        'errors' => "",
    ];

    // 403
    const ERR_FORBIDDEN = [
        "status" => 403,
        "message" => "Acesso negado.",
        'errors' => "",
    ];

    // 404
    const ERR_NOT_FOUND = [
        "status" => 404,
        "message" => "Nada foi encontrado.",
        'errors' => "",
    ];

    const INTERNAL_ERROR = [
        "status" => 500,
        "message" => "Erro interno do servidor.",
        'errors' => "",
    ];
}