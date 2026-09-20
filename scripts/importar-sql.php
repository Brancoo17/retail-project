<?php

require_once __DIR__ . '/../includes/funciones.php';

$host = '127.0.0.1';
$user = 'root';
$pass = 'root';
$port = 3306;

echo "Conectando a MySQL en {$host}:{$port}...\n";

$db = @mysqli_connect($host, $user, $pass, '', $port);

if (!$db) {
    echo "Error de conexión: " . mysqli_connect_error() . "\n";
    exit(1);
}

$sqlPath = __DIR__ . '/../sql/retail_mvc.sql';
if (!file_exists($sqlPath)) {
    echo "Error: No se encontró el archivo {$sqlPath}\n";
    exit(1);
}

$sql = file_get_contents($sqlPath);

echo "Ejecutando importación de sql/retail_mvc.sql...\n";

if ($db->multi_query($sql)) {
    do {
        if ($resultado = $db->store_result()) {
            $resultado->free();
        }
    } while ($db->more_results() && $db->next_result());

    echo "Base de datos retail_mvc y tablas creadas exitosamente.\n";
} else {
    echo "Error al ejecutar SQL: " . $db->error . "\n";
    exit(1);
}

$db->close();
