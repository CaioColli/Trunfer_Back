<?php
// Declara o autoloader das bibliotes de terceiros na pasta "/vendor"
require __DIR__ . '/../vendor/autoload.php';

// Declara o autoloader da aplicação na pasta "/src"
require __DIR__ . '/../src/autoload.php';

//var_dump(method_exists(\App\Response\AllResponses::class, 'Responses'));
//die();

//var_dump(class_exists(\App\Response\AllResponses::class));
//die();

//Inicia a execução da aplicação usando a classe App localizada em "/src/app"
App\App::run();