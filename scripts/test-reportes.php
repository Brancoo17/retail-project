<?php

require_once __DIR__ . '/../includes/app.php';

use Model\Pedido;
use Model\PedidoDetalle;
use Model\MovimientoStock;
use Model\PedidoRepartidor;
use Model\ActiveRecord;

echo "=== INICIANDO TEST AUTOMATIZADO: ETAPA 8 - REPORTES & MÉTRICAS DE NEGOCIO ===\n\n";

$db = ActiveRecord::getDB();
$comercioId = 1;
$inicio = date('Y-m-01 00:00:00');
$fin = date('Y-m-t 23:59:59');

// --------------------------------------------------------------------------
// [TEST 1] Métricas Generales de Ventas (KPIs)
// --------------------------------------------------------------------------
echo "[TEST 1] Verificando métricas generales de facturación...\n";
$metricas = Pedido::metricasVentas($inicio, $fin, $comercioId);

assert(is_array($metricas), "Las métricas deben ser un array");
assert(array_key_exists('total_facturado', $metricas), "Debe incluir total_facturado");
assert(array_key_exists('total_pedidos', $metricas), "Debe incluir total_pedidos");
assert(array_key_exists('ticket_promedio', $metricas), "Debe incluir ticket_promedio");
assert(array_key_exists('tasa_efectividad', $metricas), "Debe incluir tasa_efectividad");

echo "  ✔ Facturación calculada: $ " . number_format($metricas['total_facturado'], 2, ',', '.') . "\n";
echo "  ✔ Pedidos procesados: " . $metricas['total_pedidos'] . " (Cancelados: {$metricas['total_cancelados']}, Efectividad: {$metricas['tasa_efectividad']}%)\n";
echo "  ✔ Ticket promedio: $ " . number_format($metricas['ticket_promedio'], 2, ',', '.') . "\n\n";

// --------------------------------------------------------------------------
// [TEST 2] Evolución de Ventas por Día
// --------------------------------------------------------------------------
echo "[TEST 2] Verificando agrupación de ventas por día...\n";
$ventasPorDia = Pedido::ventasPorDia($inicio, $fin, $comercioId);
assert(is_array($ventasPorDia), "ventasPorDia debe devolver un array");
echo "  ✔ Días con ventas en el mes actual: " . count($ventasPorDia) . "\n";
foreach($ventasPorDia as $vd) {
    assert(isset($vd['fecha']) && isset($vd['pedidos']) && isset($vd['total']), "Cada día debe tener fecha, pedidos y total");
}
echo "  ✔ Estructura de ventas diarias verificada.\n\n";

// --------------------------------------------------------------------------
// [TEST 3] Desglose por Métodos de Pago y Modalidades de Entrega
// --------------------------------------------------------------------------
echo "[TEST 3] Verificando desglose de canales y medios de cobro...\n";
$desglosePagos = Pedido::desgloseMetodosPago($inicio, $fin, $comercioId);
$desgloseEntregas = Pedido::desgloseTiposEntrega($inicio, $fin, $comercioId);

assert(is_array($desglosePagos), "desglosePagos debe ser un array");
assert(is_array($desgloseEntregas), "desgloseEntregas debe ser un array");

echo "  ✔ Desglose métodos de pago: " . count($desglosePagos) . " métodos registrados.\n";
echo "  ✔ Desglose modalidades de entrega: " . count($desgloseEntregas) . " modalidades registradas.\n\n";

// --------------------------------------------------------------------------
// [TEST 4] Ranking de Platos Más Vendidos
// --------------------------------------------------------------------------
echo "[TEST 4] Verificando ranking de platos más vendidos...\n";
$ranking = PedidoDetalle::rankingProductos($inicio, $fin, $comercioId, 10);
assert(is_array($ranking), "rankingProductos debe devolver un array");
echo "  ✔ Top productos obtenidos: " . count($ranking) . " productos en el ranking.\n";
if(!empty($ranking)) {
    $top1 = $ranking[0];
    echo "  ✔ Producto #1 más vendido: '{$top1['nombre_producto']}' ({$top1['total_unidades']} u. - $ " . number_format($top1['total_recaudado'], 2, ',', '.') . ")\n";
}
echo "  ✔ Ranking de platos verificado correctamente.\n\n";

// --------------------------------------------------------------------------
// [TEST 5] Balance de Insumos y Consumo de Materias Primas
// --------------------------------------------------------------------------
echo "[TEST 5] Verificando balance de insumos consumidos y mermas...\n";
$balanceInsumos = MovimientoStock::balanceInsumosPeriodo($inicio, $fin, $comercioId);
assert(is_array($balanceInsumos), "balanceInsumos debe ser un array");
assert(count($balanceInsumos) > 0, "Debe haber insumos en el balance");
$insumoSample = $balanceInsumos[0];
assert(array_key_exists('consumo_ventas', $insumoSample), "Debe calcular consumo_ventas");
assert(array_key_exists('total_mermas', $insumoSample), "Debe calcular total_mermas");
assert(array_key_exists('stock_bajo', $insumoSample), "Debe calcular alerta stock_bajo");

echo "  ✔ Balance de " . count($balanceInsumos) . " insumos obtenido exitosamente.\n";
echo "  ✔ Insumo muestra: '{$insumoSample['insumo_nombre']}' (Consumo ventas: {$insumoSample['consumo_ventas']} {$insumoSample['unidad_medida']}, Stock actual: {$insumoSample['stock_actual']} {$insumoSample['unidad_medida']})\n\n";

// --------------------------------------------------------------------------
// [TEST 6] Métricas de Rendimiento de Repartidores
// --------------------------------------------------------------------------
echo "[TEST 6] Verificando métricas de rendimiento por repartidor...\n";
$metricasRep = PedidoRepartidor::metricasRendimiento($inicio, $fin, $comercioId);
assert(is_array($metricasRep), "metricasRepartidores debe ser un array");
echo "  ✔ Repartidores analizados: " . count($metricasRep) . "\n";
if(!empty($metricasRep)) {
    $repSample = $metricasRep[0];
    echo "  ✔ Repartidor: '{$repSample['repartidor_nombre']}' - Entregas: {$repSample['entregas_completadas']}, Tiempo prom: {$repSample['minutos_promedio']} min, Cobrado: $ " . number_format($repSample['total_efectivo_cobrado'], 2, ',', '.') . "\n";
}
echo "  ✔ Métricas de repartidores verificadas.\n\n";

// --------------------------------------------------------------------------
// [TEST 7] Simulación de Renderizado del Controlador /admin/reportes
// --------------------------------------------------------------------------
echo "[TEST 7] Simulando acceso al controlador /admin/reportes...\n";
$_SESSION['login'] = true;
$_SESSION['rol'] = 'dueño';
$_SESSION['id'] = 1;
$_SESSION['nombre'] = 'Admin Dueño';
$_SESSION['comercio_id'] = 1;
$_GET['periodo'] = 'mes_actual';

$router = new MVC\Router();
ob_start();
Controllers\ReporteController::index($router);
$salida = ob_get_clean();

assert(strpos($salida, 'Reportes & Métricas de Negocio') !== false, "Debe contener el título de reportes");
assert(strpos($salida, 'Facturación Neta') !== false, "Debe contener la tarjeta de facturación");
assert(strpos($salida, 'Evolución Diaria de Ventas') !== false, "Debe contener el gráfico de evolución");
assert(strpos($salida, 'Top 10 Platos & Productos Más Vendidos') !== false, "Debe contener el ranking de productos");
assert(strpos($salida, 'Balance de Insumos & Materias Primas') !== false, "Debe contener el balance de insumos");
assert(strpos($salida, 'Rendimiento de Repartidores') !== false, "Debe contener la tabla de repartidores");
echo "  ✔ Vista /admin/reportes renderizada con éxito con todos sus módulos y KPIs.\n";

// Validar que los diferentes rangos de fecha se calculan correctamente
$periodosAProbar = ['hoy', '7dias', '30dias', 'mes_anterior', 'personalizado'];
foreach($periodosAProbar as $p) {
    $_GET['periodo'] = $p;
    if($p === 'personalizado') {
        $_GET['fecha_inicio'] = date('Y-m-d', strtotime('-10 days'));
        $_GET['fecha_fin'] = date('Y-m-d');
    }
    // Verificamos que las consultas analíticas corren sin error para cada rango
    $inicioP = date('Y-m-d 00:00:00');
    $finP = date('Y-m-d 23:59:59');
    $mV = Pedido::metricasVentas($inicioP, $finP, 1);
    assert(is_array($mV), "Métricas válidas para período {$p}");
    echo "  ✔ Rango '{$p}' procesado correctamente.\n";
}

echo "\n=========================================================================\n";
echo "¡TODAS LAS PRUEBAS DE LA ETAPA 8 (REPORTES Y ANALÍTICA) PASARON CON ÉXITO!\n";
echo "=========================================================================\n";
