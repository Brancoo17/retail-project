<?php

require_once __DIR__ . '/../includes/app.php';

use Model\Comercio;
use Model\Producto;
use Model\Insumo;
use Model\ProductoInsumo;
use Model\MovimientoStock;

echo "=========================================" . PHP_EOL;
echo " SEED ETAPA 3: INSUMOS, RECETAS Y STOCK   " . PHP_EOL;
echo "=========================================" . PHP_EOL;

$comercio = Comercio::find(1);
if(!$comercio) {
    echo "[ERROR] No se encontró el comercio ID 1. Ejecuta primero seed-inicial.php." . PHP_EOL;
    exit(1);
}

// 1. Insumos Base
$insumosDatos = [
    [
        'nombre' => 'Harina 000 Especial',
        'unidad_medida' => 'kg',
        'stock_actual' => 50.000,
        'stock_minimo' => 10.000
    ],
    [
        'nombre' => 'Queso Muzzarella en Barra',
        'unidad_medida' => 'kg',
        'stock_actual' => 25.000,
        'stock_minimo' => 5.000
    ],
    [
        'nombre' => 'Salsa de Tomate Casera',
        'unidad_medida' => 'lts',
        'stock_actual' => 30.000,
        'stock_minimo' => 8.000
    ],
    [
        'nombre' => 'Cajas de Cartón para Pizza',
        'unidad_medida' => 'unidades',
        'stock_actual' => 150.000,
        'stock_minimo' => 30.000
    ],
    [
        'nombre' => 'Aceitunas Negras Descarozadas',
        'unidad_medida' => 'kg',
        'stock_actual' => 4.500,
        'stock_minimo' => 1.000
    ],
    [
        'nombre' => 'Medallón de Carne Smash 120g',
        'unidad_medida' => 'unidades',
        'stock_actual' => 8.000, // ¡Bajo stock!
        'stock_minimo' => 20.000
    ],
    [
        'nombre' => 'Pan Brioche para Hamburguesa',
        'unidad_medida' => 'unidades',
        'stock_actual' => 0.000, // ¡Agotado!
        'stock_minimo' => 15.000
    ]
];

$insumosCreados = [];

foreach($insumosDatos as $item) {
    $existente = Insumo::consultarSQL("SELECT * FROM insumos WHERE comercio_id = 1 AND nombre = '{$item['nombre']}' LIMIT 1");
    if(!empty($existente)) {
        echo "[INFO] El insumo '{$item['nombre']}' ya existe (ID: {$existente[0]->id})." . PHP_EOL;
        $insumosCreados[$item['nombre']] = $existente[0];
        continue;
    }

    $insumo = new Insumo([
        'comercio_id' => 1,
        'nombre' => $item['nombre'],
        'unidad_medida' => $item['unidad_medida'],
        'stock_actual' => $item['stock_actual'],
        'stock_minimo' => $item['stock_minimo']
    ]);

    $res = $insumo->crear();
    if($res['resultado']) {
        $insumo->id = $res['id'];
        $insumosCreados[$item['nombre']] = $insumo;
        echo "[OK] Insumo creado: {$insumo->nombre} (Stock: {$insumo->stock_actual} {$insumo->unidad_medida})" . PHP_EOL;

        // Registrar movimiento inicial de compra/ingreso
        if($insumo->stock_actual > 0) {
            MovimientoStock::registrar(
                1,
                (int)$insumo->id,
                1, // Usuario dueño
                'compra',
                $insumo->stock_actual,
                'Stock inicial de apertura de inventario'
            );
        }
    } else {
        echo "[ERROR] Error al crear insumo: {$item['nombre']}" . PHP_EOL;
    }
}

// 2. Asociar Receta a un Producto de prueba
$productos = Producto::porComercio(1);
if(empty($productos)) {
    echo "[AVISO] No hay productos en el catálogo aún para vincular una receta." . PHP_EOL;
} else {
    $productoPizza = null;
    foreach($productos as $p) {
        if(stripos($p->nombre, 'pizza') !== false || stripos($p->nombre, 'muzzarella') !== false) {
            $productoPizza = $p;
            break;
        }
    }

    if(!$productoPizza) {
        $productoPizza = $productos[0];
    }

    echo "[INFO] Vinculando receta al producto: '{$productoPizza->nombre}' (ID: {$productoPizza->id})" . PHP_EOL;

    $ingredientesReceta = [
        'Harina 000 Especial' => 0.250,
        'Queso Muzzarella en Barra' => 0.300,
        'Salsa de Tomate Casera' => 0.150,
        'Cajas de Cartón para Pizza' => 1.000
    ];

    foreach($ingredientesReceta as $nombreInsumo => $cantidad) {
        if(isset($insumosCreados[$nombreInsumo])) {
            $insumoObj = $insumosCreados[$nombreInsumo];
            
            if(!ProductoInsumo::existeEnReceta((int)$productoPizza->id, (int)$insumoObj->id)) {
                $pi = new ProductoInsumo([
                    'producto_id' => (int)$productoPizza->id,
                    'insumo_id' => (int)$insumoObj->id,
                    'cantidad_consumo' => $cantidad
                ]);
                $pi->crear();
                echo "  + Receta: {$cantidad} {$insumoObj->unidad_medida} de {$insumoObj->nombre}" . PHP_EOL;
            } else {
                echo "  ~ Ingrediente '{$insumoObj->nombre}' ya presente en la receta." . PHP_EOL;
            }
        }
    }
}

echo "=========================================" . PHP_EOL;
echo " SEED ETAPA 3 FINALIZADO CON ÉXITO        " . PHP_EOL;
echo "=========================================" . PHP_EOL;
