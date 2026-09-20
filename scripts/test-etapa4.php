<?php

require_once __DIR__ . '/../includes/app.php';

use Model\Comercio;
use Model\Producto;
use Model\Insumo;
use Model\Pedido;
use Model\PedidoDetalle;
use Model\MovimientoStock;

echo "=== TEST ETAPA 4: CARRITO Y FLUJO DE PEDIDOS ===" . PHP_EOL;

$comercio = Comercio::find(1);
echo "Comercio: " . $comercio->nombre . PHP_EOL;
echo "Ubicación Local: Lat " . $comercio->lat . ", Lng " . $comercio->lng . PHP_EOL;
echo "Km Envío Gratis: " . $comercio->km_envio_gratis . " km | Costo/Km: $" . $comercio->costo_envio_por_km . PHP_EOL;
echo "Minutos Cancelación: " . $comercio->minutos_cancelacion . " min" . PHP_EOL;

// 1. Probar cálculo de distancia cercana (1 km)
$latCercana = (float)$comercio->lat + 0.008;
$lngCercana = (float)$comercio->lng + 0.008;
$distCercana = haversineKm((float)$comercio->lat, (float)$comercio->lng, $latCercana, $lngCercana);
echo PHP_EOL . "Distancia simulada cliente cercano: " . round($distCercana, 2) . " km" . PHP_EOL;
$costoCercano = ($distCercana > (float)$comercio->km_envio_gratis) ? round(($distCercana - (float)$comercio->km_envio_gratis) * (float)$comercio->costo_envio_por_km, 2) : 0.0;
echo "Costo envío cercano: $" . number_format($costoCercano, 2, ',', '.') . " (" . ($costoCercano == 0 ? "GRATIS" : "CON CARGO") . ")" . PHP_EOL;

// 2. Probar distancia lejana (ej. 8 km)
$latLejana = (float)$comercio->lat + 0.06;
$lngLejana = (float)$comercio->lng + 0.06;
$distLejana = haversineKm((float)$comercio->lat, (float)$comercio->lng, $latLejana, $lngLejana);
echo "Distancia simulada cliente lejano: " . round($distLejana, 2) . " km" . PHP_EOL;
$costoLejano = ($distLejana > (float)$comercio->km_envio_gratis) ? round(($distLejana - (float)$comercio->km_envio_gratis) * (float)$comercio->costo_envio_por_km, 2) : 0.0;
echo "Costo envío lejano: $" . number_format($costoLejano, 2, ',', '.') . PHP_EOL;

// 3. Revisar stock antes de la venta
$insumoHarina = Insumo::find(1);
$insumoQueso = Insumo::find(2);
echo PHP_EOL . "STOCK INICIAL INSUMOS:" . PHP_EOL;
echo "- " . $insumoHarina->nombre . ": " . formatearCantidad($insumoHarina->stock_actual, $insumoHarina->unidad_medida) . " " . $insumoHarina->unidad_medida . PHP_EOL;
echo "- " . $insumoQueso->nombre . ": " . formatearCantidad($insumoQueso->stock_actual, $insumoQueso->unidad_medida) . " " . $insumoQueso->unidad_medida . PHP_EOL;

// 4. Simular creación de un pedido via PedidoController::crear()
echo PHP_EOL . "SIMULANDO CREACIÓN DE PEDIDO..." . PHP_EOL;

// Obtener un producto activo
$producto = Producto::find(1);
$opciones = $producto->opciones(true);
$opcId = !empty($opciones) ? [(int)$opciones[0]->id] : [];

$payload = [
    'nombre_contacto' => 'Juan Pérez Test',
    'telefono_contacto' => '1144332211',
    'tipo' => 'delivery',
    'metodo_pago' => 'efectivo',
    'direccion_texto' => 'Av. San Martín 750, 4to B',
    'lat' => $latCercana,
    'lng' => $lngCercana,
    'items' => [
        [
            'producto_id' => (int)$producto->id,
            'cantidad' => 2,
            'opciones' => $opcId,
            'notas' => 'Bien crocante'
        ]
    ]
];

// Ejecutar POST interno a /pedido/crear via cURL a http://127.0.0.1:3000/pedido/crear
$ch = curl_init('http://127.0.0.1:3000/pedido/crear');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
$respJson = curl_exec($ch);
curl_close($ch);

echo "Respuesta del endpoint /pedido/crear:" . PHP_EOL;
echo $respJson . PHP_EOL;

$res = json_decode($respJson, true);
if(!empty($res['ok']) && !empty($res['pedido_id'])) {
    $pedidoId = (int)$res['pedido_id'];
    echo "¡Pedido creado exitosamente con ID: #{$pedidoId}!" . PHP_EOL;

    // Verificar pedido en DB
    $pedido = Pedido::find($pedidoId);
    echo "Subtotal: $" . number_format($pedido->subtotal, 2, ',', '.') . " | Envío: $" . number_format($pedido->costo_envio, 2, ',', '.') . " | Total: $" . number_format($pedido->total, 2, ',', '.') . PHP_EOL;
    echo "Estado inicial: " . $pedido->estado . PHP_EOL;

    // Verificar stock post-venta
    $insumoHarinaPost = Insumo::find(1);
    $insumoQuesoPost = Insumo::find(2);
    echo PHP_EOL . "STOCK POST-VENTA (DESCONTADO):" . PHP_EOL;
    echo "- " . $insumoHarinaPost->nombre . ": " . formatearCantidad($insumoHarinaPost->stock_actual, $insumoHarinaPost->unidad_medida) . " " . $insumoHarinaPost->unidad_medida . PHP_EOL;
    echo "- " . $insumoQuesoPost->nombre . ": " . formatearCantidad($insumoQuesoPost->stock_actual, $insumoQuesoPost->unidad_medida) . " " . $insumoQuesoPost->unidad_medida . PHP_EOL;

    // 5. Probar Cancelación del pedido y restitución de stock
    echo PHP_EOL . "PROBANDO CANCELACIÓN DE PEDIDO #{$pedidoId}..." . PHP_EOL;
    $chCancel = curl_init('http://127.0.0.1:3000/pedido/cancelar');
    curl_setopt($chCancel, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chCancel, CURLOPT_POST, true);
    curl_setopt($chCancel, CURLOPT_POSTFIELDS, http_build_query(['pedido_id' => $pedidoId]));
    curl_setopt($chCancel, CURLOPT_FOLLOWLOCATION, false);
    $resCancel = curl_exec($chCancel);
    $httpCode = curl_getinfo($chCancel, CURLINFO_HTTP_CODE);
    curl_close($chCancel);

    echo "Código HTTP tras cancelar (esperado 302 redirect): {$httpCode}" . PHP_EOL;

    // Verificar pedido cancelado
    $pedidoCancelado = Pedido::find($pedidoId);
    echo "Estado tras cancelación: " . $pedidoCancelado->estado . PHP_EOL;

    // Verificar stock post-cancelación
    $insumoHarinaRestituido = Insumo::find(1);
    $insumoQuesoRestituido = Insumo::find(2);
    echo PHP_EOL . "STOCK TRAS CANCELACIÓN (RESTITUIDO):" . PHP_EOL;
    echo "- " . $insumoHarinaRestituido->nombre . ": " . formatearCantidad($insumoHarinaRestituido->stock_actual, $insumoHarinaRestituido->unidad_medida) . " " . $insumoHarinaRestituido->unidad_medida . PHP_EOL;
    echo "- " . $insumoQuesoRestituido->nombre . ": " . formatearCantidad($insumoQuesoRestituido->stock_actual, $insumoQuesoRestituido->unidad_medida) . " " . $insumoQuesoRestituido->unidad_medida . PHP_EOL;

    // Verificar movimientos de stock registrados
    $movs = MovimientoStock::porComercio(1, 5, 1);
    echo PHP_EOL . "Últimos movimientos de harina:" . PHP_EOL;
    foreach($movs as $m) {
        echo "- Tipo: {$m->tipo} | Cantidad: " . formatearCantidad($m->cantidad, 'kg') . " kg | Motivo: {$m->motivo}" . PHP_EOL;
    }
} else {
    echo "ERROR en la creación del pedido: " . ($res['mensaje'] ?? 'Desconocido') . PHP_EOL;
}
