<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'u959195816_im2ersao');
define('DB_PASS', 'w6=P4cp6oC');
define('DB_NAME', 'u959195816_im2ersao');
try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) { die('DB Connection failed'); }
