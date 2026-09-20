<?php

require_once __DIR__ . '/../includes/app.php';

use Model\Comercio;
use Model\Usuario;
use Model\Categoria;
use Model\Producto;
use Model\Insumo;
use Model\ProductoInsumo;
use Model\ProductoOpcion;
use Model\Pedido;
use Model\PedidoDetalle;
use Model\MovimientoStock;
use Model\DireccionCliente;
use Model\ActiveRecord;

echo "======================================================" . PHP_EOL;
echo "   DIAGNÓSTICO INTEGRAL DEL SISTEMA - RETAIL PROJECT   " . PHP_EOL;
echo "======================================================" . PHP_EOL;

$errores = [];

// 1. Conexión a Base de Datos
echo PHP_EOL . "[1/6] Verificando Base de Datos..." . PHP_EOL;
try {
    $db = ActiveRecord::getDB();
    if($db && $db->query("SELECT 1")) {
        echo "  ✔ Conexión exitosa a MySQL ('" . ($_ENV['DB_NAME'] ?? 'retail_mvc') . "')" . PHP_EOL;
    } else {
        $errores[] = "No se pudo hacer ping a la base de datos MySQL.";
        echo "  ❌ Fallo en conexión a MySQL" . PHP_EOL;
    }
} catch(\Throwable $e) {
    $errores[] = "Excepción DB: " . $e->getMessage();
    echo "  ❌ Error conectando a DB: " . $e->getMessage() . PHP_EOL;
}

// 2. Integridad de Tablas y Esquema
echo PHP_EOL . "[2/6] Verificando Tablas del Modelo..." . PHP_EOL;
$tablasRequeridas = [
    'comercios', 'usuarios', 'categorias', 'productos',
    'producto_opciones', 'insumos', 'producto_insumos',
    'movimientos_stock', 'pedidos', 'pedido_detalle',
    'direcciones_cliente', 'pagos', 'comprobantes'
];

foreach($tablasRequeridas as $tabla) {
    $res = $db->query("SHOW TABLES LIKE '{$tabla}'");
    if($res && $res->num_rows > 0) {
        echo "  ✔ Tabla '{$tabla}' presente" . PHP_EOL;
    } else {
        $errores[] = "Tabla requerida '{$tabla}' no existe en la base de datos.";
        echo "  ❌ Tabla '{$tabla}' FALTANTE" . PHP_EOL;
    }
}

// 3. Verificando Datos Clave y Semillas
echo PHP_EOL . "[3/6] Verificando Registros Base..." . PHP_EOL;
$comercio = Comercio::find(1);
if($comercio) {
    echo "  ✔ Comercio ID #1: '{$comercio->nombre}'" . PHP_EOL;
    echo "    - Ubicación: ({$comercio->lat}, {$comercio->lng})" . PHP_EOL;
    echo "    - Envío: {$comercio->km_envio_gratis} km gratis, luego \${$comercio->costo_envio_por_km}/km" . PHP_EOL;
    echo "    - Ventana Cancelación: {$comercio->minutos_cancelacion} minutos" . PHP_EOL;
} else {
    $errores[] = "Comercio ID 1 no encontrado.";
    echo "  ❌ Comercio ID 1 no encontrado" . PHP_EOL;
}

$usuarios = Usuario::all();
$rolesPresentes = [];
foreach($usuarios as $u) {
    $rolesPresentes[$u->rol] = ($rolesPresentes[$u->rol] ?? 0) + 1;
}
echo "  ✔ Usuarios registrados: " . count($usuarios) . " (" . json_encode($rolesPresentes) . ")" . PHP_EOL;

$productos = Producto::all();
$insumos = Insumo::all();
$categorias = Categoria::all();
echo "  ✔ Catálogo: " . count($categorias) . " categorías, " . count($productos) . " productos, " . count($insumos) . " insumos" . PHP_EOL;

// 4. Verificando Lógica de Stock y Recetas
echo PHP_EOL . "[4/6] Verificando Integridad de Recetas e Insumos..." . PHP_EOL;
$recetasTotal = ProductoInsumo::all();
echo "  ✔ Vínculos de receta activos: " . count($recetasTotal) . PHP_EOL;

$insumoHarina = Insumo::find(1);
if($insumoHarina) {
    echo "  ✔ Insumo muestra: '{$insumoHarina->nombre}' | Stock: " . formatearCantidad($insumoHarina->stock_actual, $insumoHarina->unidad_medida) . " {$insumoHarina->unidad_medida}" . PHP_EOL;
}

// 5. Verificando Pedidos Existentes
echo PHP_EOL . "[5/6] Verificando Historial de Pedidos..." . PHP_EOL;
$pedidos = Pedido::all();
echo "  ✔ Pedidos registrados en base: " . count($pedidos) . PHP_EOL;
foreach(array_slice($pedidos, 0, 3) as $ped) {
    echo "    - Pedido #{$ped->id} | {$ped->tipo} | Estado: {$ped->estado} | Total: \${$ped->total}" . PHP_EOL;
}

// 6. Verificando Archivos Compilados y Assets
echo PHP_EOL . "[6/6] Verificando Assets Compilados (Gulp)..." . PHP_EOL;
$cssPath = __DIR__ . '/../public/build/css/app.css';
$jsPath = __DIR__ . '/../public/build/js/app.js';

if(file_exists($cssPath) && filesize($cssPath) > 1000) {
    echo "  ✔ CSS compilado: " . round(filesize($cssPath) / 1024, 1) . " KB (Modificado: " . date('Y-m-d H:i:s', filemtime($cssPath)) . ")" . PHP_EOL;
} else {
    $errores[] = "Archivo public/build/css/app.css ausente o corrupto.";
    echo "  ❌ CSS compilado ausente" . PHP_EOL;
}

if(file_exists($jsPath) && filesize($jsPath) > 1000) {
    echo "  ✔ JS compilado: " . round(filesize($jsPath) / 1024, 1) . " KB (Modificado: " . date('Y-m-d H:i:s', filemtime($jsPath)) . ")" . PHP_EOL;
} else {
    $errores[] = "Archivo public/build/js/app.js ausente o corrupto.";
    echo "  ❌ JS compilado ausente" . PHP_EOL;
}

// Resumen Final
echo PHP_EOL . "======================================================" . PHP_EOL;
if(empty($errores)) {
    echo "  RESULTADO: ✔ TODOS LOS SUBSISTEMAS OPERATIVOS Y EN REGLA" . PHP_EOL;
} else {
    echo "  RESULTADO: ❌ SE ENCONTRARON " . count($errores) . " INCONSISTENCIAS" . PHP_EOL;
    foreach($errores as $err) {
        echo "   - {$err}" . PHP_EOL;
    }
}
echo "======================================================" . PHP_EOL;
