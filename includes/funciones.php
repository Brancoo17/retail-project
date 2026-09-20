<?php

// Definir la ruta de la carpeta de imágenes (compatible con localhost, hosting y CLI)
if(!defined('CARPETA_IMAGENES')) {
    if(is_dir(__DIR__ . '/../public/imagenes/')) {
        define('CARPETA_IMAGENES', __DIR__ . '/../public/imagenes/');
    } elseif(isset($_SERVER['DOCUMENT_ROOT']) && is_dir($_SERVER['DOCUMENT_ROOT'] . '/imagenes/')) {
        define('CARPETA_IMAGENES', $_SERVER['DOCUMENT_ROOT'] . '/imagenes/');
    } else {
        define('CARPETA_IMAGENES', __DIR__ . '/../public/imagenes/');
    }
}

/**
 * Imprime formateada una variable para depuración y detiene la ejecución
 */
function debuguear(mixed $variable): void {
    echo "<pre>";
    var_dump($variable);
    echo "</pre>";
    exit;
}

/**
 * Escapa caracteres especiales en un string para evitar XSS
 */
function s(?string $html): string {
    return htmlspecialchars($html ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Verifica si el usuario actual ha iniciado sesión
 */
function isAuth(): void {
    if(!isset($_SESSION['login'])) {
        header('Location: /login');
        exit;
    }
}

/**
 * Verifica si el usuario actual tiene rol de Dueño / Administrador
 */
function isDueno(): void {
    isAuth();
    if(($_SESSION['rol'] ?? '') !== 'dueño') {
        header('Location: /');
        exit;
    }
}

/**
 * Verifica si el usuario actual tiene rol de Empleado (cocina/caja)
 */
function isEmpleado(): void {
    isAuth();
    if(($_SESSION['rol'] ?? '') !== 'empleado') {
        header('Location: /');
        exit;
    }
}

/**
 * Verifica si el usuario actual es Dueño o Empleado (Staff operativo del comercio)
 */
function isStaff(): void {
    isAuth();
    $rol = $_SESSION['rol'] ?? '';
    if($rol !== 'dueño' && $rol !== 'empleado') {
        header('Location: /');
        exit;
    }
}

/**
 * Verifica si el usuario actual tiene rol de Repartidor
 */
function isRepartidor(): void {
    isAuth();
    if(($_SESSION['rol'] ?? '') !== 'repartidor') {
        header('Location: /');
        exit;
    }
}

/**
 * Verifica si el usuario actual tiene rol de Cliente
 */
function isCliente(): void {
    isAuth();
    if(($_SESSION['rol'] ?? '') !== 'cliente') {
        header('Location: /');
        exit;
    }
}

/**
 * Cálculo de distancia en línea recta en kilómetros entre dos pares de coordenadas (Fórmula Haversine)
 */
function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float {
    $radioTierra = 6371.0; // Radio de la Tierra en km
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return round($radioTierra * $c, 2);
}

/**
 * Obtiene la configuración del comercio activo (por sesión o default id=1) con caché en petición
 */
function obtenerComercio(?int $id = null): ?Model\Comercio {
    static $comercioCache = null;
    $idComercio = $id ?? ($_SESSION['comercio_id'] ?? 1);
    
    if ($comercioCache !== null && $comercioCache->id === $idComercio) {
        return $comercioCache;
    }

    $comercioCache = Model\Comercio::find((int)$idComercio);
    return $comercioCache;
}

/**
 * Formatea cantidades de insumos según el estándar argentino:
 * - Coma (,) para la parte decimal en peso/volumen (kg, gr, lts, ml).
 * - Número entero sin decimales para insumos medidos en 'unidades'.
 */
function formatearCantidad(float|int|string|null $cantidad, string $unidad = ''): string {
    $num = (float)($cantidad ?? 0);
    $unidadNorm = strtolower(trim($unidad));

    if($unidadNorm === 'unidades' || $unidadNorm === 'unidad') {
        return number_format(round($num), 0, ',', '.');
    }

    return number_format($num, 3, ',', '.');
}

