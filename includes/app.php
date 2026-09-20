<?php

date_default_timezone_set('America/Argentina/Buenos_Aires');

// Límite de memoria adecuado para procesamiento de imágenes
@ini_set('memory_limit', '256M');

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// Habilitar reporte de errores si se especifica APP_DEBUG=true en .env
if (isset($_ENV['APP_DEBUG']) && filter_var($_ENV['APP_DEBUG'], FILTER_VALIDATE_BOOLEAN)) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

require_once 'funciones.php';
require_once 'database.php';

use Model\ActiveRecord;
if (isset($db) && $db instanceof \mysqli) {
    ActiveRecord::setDB($db);
}
