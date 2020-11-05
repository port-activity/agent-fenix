<?php

require_once __DIR__ . "/../src/lib/init.php";


spl_autoload_register(
    function ($className) {
        $pathToFind = str_replace("\\", "/", $className);
        $dirs = ["/src/lib/"];
        foreach ($dirs as $dir) {
            $file  = __DIR__ . $dir . $pathToFind . '.php';
            if (file_exists($file)) {
                require_once $file;
                return true;
            }
        }
        return false;
    }
);
