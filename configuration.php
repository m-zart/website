<?php

    $environment = "Production";
    
    if ($environment == "Development"){
        DB::$user = "root";
        DB::$password = "";
        DB::$dbName = "faalkaart";
        
        $refreshTimer = 30;
        
        DB::$error_handler = false;
        DB::$throw_exception_on_error = true;
        
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        $addKey = "";
    } else {
        ini_set('display_errors', 0);
        ini_set('display_startup_errors', 0);

        DB::$error_handler = false;
        DB::$throw_exception_on_error = true;
        
        DB::$user = "";
        DB::$password = "";
        DB::$dbName = "";

        $addKey = "";
    }
    
    unset ($environment);


?>
