<?php
// Declara o autoloader das bibliotes de terceiros na pasta "/vendor"
require __DIR__ . '/../vendor/autoload.php';

// Declara o autoloader da aplicação na pasta "/src"
require __DIR__ . '/../src/autoload.php';

//Inicia a execução da aplicação usando a classe App localizada em "/src/app"
App\App::run();