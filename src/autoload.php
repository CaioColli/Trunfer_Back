<?php

/**
 * Autoloader que usa os diretórios como namespace
 */
spl_autoload_register(function ($class) {
    
    if (strpos($class, "\\") !== false) {
        $pieces = explode("\\", $class);
        $class = '';
        
        for ($i = 0; $i < count($pieces); $i++) {
            $class .= $i + 1 !== count($pieces) ? strtolower($pieces[$i]).DIRECTORY_SEPARATOR : $pieces[$i];
        }
    }
    
    $class = __DIR__.DIRECTORY_SEPARATOR.$class.'.php';
    
    if(file_exists($class)) {
        require $class;
    }
    
});
