<?php
require_once __DIR__ . '/../includes/app.php';

use Model\ActiveRecord;

$db = ActiveRecord::getDB();

// 1. Agregar permite_aderezos a categorias si no existe
$columnExists = false;
$resCols = $db->query("SHOW COLUMNS FROM categorias LIKE 'permite_aderezos'");
if($resCols && $resCols->num_rows > 0) {
    $columnExists = true;
}

if(!$columnExists) {
    $db->query("ALTER TABLE categorias ADD COLUMN permite_aderezos TINYINT(1) DEFAULT 0 AFTER activo");
    echo "[1] Columna 'permite_aderezos' agregada a tabla 'categorias'.\n";
} else {
    echo "[1] Columna 'permite_aderezos' ya existía en 'categorias'.\n";
}

// 2. Crear tabla aderezos
$sqlTabla = "CREATE TABLE IF NOT EXISTS aderezos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comercio_id INT NOT NULL,
    nombre VARCHAR(60) NOT NULL,
    activo TINYINT(1) DEFAULT 1,
    orden INT DEFAULT 0,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_aderezos_comercio FOREIGN KEY (comercio_id) REFERENCES comercios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$db->query($sqlTabla);
echo "[2] Tabla 'aderezos' verificada/creada con éxito.\n";

// 3. Poblar aderezos iniciales si está vacía
$resCount = $db->query("SELECT COUNT(*) AS total FROM aderezos WHERE comercio_id = 1");
$row = $resCount->fetch_assoc();
$totalAderezos = (int)($row['total'] ?? 0);

if($totalAderezos === 0) {
    $iniciales = [
        'Mayonesa',
        'Mostaza',
        'Ketchup',
        'Salsa Golf',
        'Salsa Barbacoa',
        'Chimichurri',
        'Alioli'
    ];
    $orden = 1;
    foreach($iniciales as $nombre) {
        $nombreEsc = $db->real_escape_string($nombre);
        $db->query("INSERT INTO aderezos (comercio_id, nombre, activo, orden) VALUES (1, '{$nombreEsc}', 1, {$orden})");
        $orden++;
    }
    echo "[3] Se insertaron " . count($iniciales) . " aderezos iniciales para comercio #1.\n";
} else {
    echo "[3] Ya existen {$totalAderezos} aderezos en el comercio #1.\n";
}

// 4. Si existe una categoría tipo 'Sandwiches' o 'Hamburguesas', activar permite_aderezos
$db->query("UPDATE categorias SET permite_aderezos = 1 WHERE comercio_id = 1 AND (LOWER(nombre) LIKE '%sandwich%' OR LOWER(nombre) LIKE '%sándwich%' OR LOWER(nombre) LIKE '%lomito%' OR LOWER(nombre) LIKE '%hamburguesa%')");
echo "[4] Categorías afines actualizadas con permite_aderezos = 1.\n";

echo "\n¡MIGRACIÓN DE ADEREZOS FINALIZADA CON ÉXITO!\n";
