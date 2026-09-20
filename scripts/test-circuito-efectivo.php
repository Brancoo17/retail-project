<?php

require_once __DIR__ . '/../includes/app.php';

use Model\Pedido;
use Model\Pago;
use Model\CajaSesion;
use Model\Comprobante;
use Model\Usuario;

echo "=== INICIANDO TEST AUTOMATIZADO: CIRCUITO INTEGRAL DE EFECTIVO Y CAJA ===\n\n";

$comercioId = 1;

// 1. Obtener o abrir una sesión de caja de prueba
$sesion = CajaSesion::sesionActiva($comercioId);
if(!$sesion) {
    echo "[1] Abriendo nueva sesión de caja de prueba con fondo de $10.000...\n";
    $sesion = CajaSesion::abrir($comercioId, 2, 10000.00, 'Test Automatizado Circuito Efectivo');
} else {
    echo "[1] Usando sesión de caja activa ID #{$sesion->id} (Monto apertura: \${$sesion->monto_apertura})...\n";
}

$totalesInicio = $sesion->calcularTotalesTurno();
echo "    Estado inicial caja:\n";
echo "    - Efectivo mostrador: \${$totalesInicio['efectivo_mostrador']}\n";
echo "    - Efectivo rendido:    \${$totalesInicio['efectivo_rendido']}\n";
echo "    - Efectivo en calle:   \${$totalesInicio['efectivo_en_calle']}\n";
echo "    - Efectivo esperado:   \${$totalesInicio['efectivo_esperado']}\n\n";

// 2. Buscar repartidor de prueba
$repartidor = Usuario::where('rol', 'repartidor');
$repartidorId = $repartidor ? (int)$repartidor->id : 1;
echo "[2] Repartidor asignado para pruebas: ID #{$repartidorId} ({$repartidor->nombre})\n\n";

// =========================================================================
// ESCENARIO A: PEDIDO RETIRO / MOSTRADOR CON EFECTIVO
// =========================================================================
echo "[3] ESCENARIO A: Creando pedido RETIRO con pago en EFECTIVO ($3.500)...\n";
$pedRetiro = new Pedido([
    'comercio_id' => $comercioId,
    'usuario_id' => 1,
    'tipo' => 'retiro',
    'metodo_pago' => 'efectivo',
    'subtotal' => 3500.00,
    'costo_envio' => 0.00,
    'descuento' => 0.00,
    'total' => 3500.00,
    'estado' => 'en_camino',
    'nombre_contacto' => 'Cliente Mostrador Test',
    'telefono_contacto' => '1199887766'
]);
$pedRetiro->guardar();
$pedRetiroId = (int)$pedRetiro->id;

// Crear pago asociado
$pagoRetiro = new Pago([
    'comercio_id' => $comercioId,
    'pedido_id' => $pedRetiroId,
    'monto' => 3500.00,
    'metodo' => 'efectivo',
    'estado' => 'pendiente',
    'rendido' => 1 // Retiro/mostrador siempre rendido = 1
]);
$pagoRetiro->guardar();

echo "    Pedido retiro #{$pedRetiroId} creado. Pago ID #{$pagoRetiro->id} en estado 'pendiente'.\n";

// Ejecutar cobro y entrega en mostrador
echo "    Ejecutando cobrarYEntregarRetiro() en mostrador...\n";
$exitoRetiro = $pedRetiro->cobrarYEntregarRetiro((int)$sesion->id);
assert($exitoRetiro === true, "Fallo al cobrar y entregar pedido de retiro");

// Validaciones Escenario A
$pedRetiroActualizado = Pedido::find($pedRetiroId);
$pagoRetiroActualizado = $pedRetiroActualizado->pago();
$compRetiro = $pedRetiroActualizado->comprobante();

echo "    Validando resultado Retiro:\n";
echo "    - Estado pedido: {$pedRetiroActualizado->estado} (esperado: entregado)\n";
echo "    - Estado pago: {$pagoRetiroActualizado->estado} (esperado: aprobado)\n";
echo "    - Rendido: {$pagoRetiroActualizado->rendido} (esperado: 1)\n";
echo "    - Caja Sesión vinculada: #{$pagoRetiroActualizado->caja_sesion_id} (esperado: #{$sesion->id})\n";
echo "    - Comprobante emitido: " . ($compRetiro ? $compRetiro->codigoFormateado() : "NO") . "\n";

assert($pedRetiroActualizado->estado === 'entregado', "Estado debe ser entregado");
assert($pagoRetiroActualizado->estado === 'aprobado', "Pago debe estar aprobado");
assert((int)$pagoRetiroActualizado->rendido === 1, "Rendido debe ser 1");
assert((int)$pagoRetiroActualizado->caja_sesion_id === (int)$sesion->id, "Debe vincularse a la caja");
assert($compRetiro !== null, "Debe emitirse comprobante");

$totalesPostA = $sesion->calcularTotalesTurno();
$deltaMostrador = (float)$totalesPostA['efectivo_mostrador'] - (float)$totalesInicio['efectivo_mostrador'];
echo "    - Delta Efectivo Mostrador en caja: +\${$deltaMostrador} (esperado: +3500.00)\n";
assert(abs($deltaMostrador - 3500.00) < 0.01, "El efectivo de mostrador debe sumar 3500");

echo "    -> Escenario A APROBADO EXITOSAMENTE.\n\n";

// =========================================================================
// ESCENARIO B: PEDIDO DELIVERY CONTRA ENTREGA (CALLE -> RENDICIÓN A CAJA)
// =========================================================================
echo "[4] ESCENARIO B: Creando pedido DELIVERY con pago en EFECTIVO ($4.800)...\n";
$pedDelivery = new Pedido([
    'comercio_id' => $comercioId,
    'usuario_id' => 1,
    'tipo' => 'delivery',
    'metodo_pago' => 'efectivo',
    'subtotal' => 4200.00,
    'costo_envio' => 600.00,
    'descuento' => 0.00,
    'total' => 4800.00,
    'estado' => 'en_camino',
    'nombre_contacto' => 'Cliente Delivery Test',
    'telefono_contacto' => '1144332211',
    'direccion_texto' => 'Av. San Martín 1234'
]);
$pedDelivery->guardar();
$pedDeliveryId = (int)$pedDelivery->id;

// Crear pago asociado (rendido = 0 por ser delivery contra entrega)
$pagoDelivery = new Pago([
    'comercio_id' => $comercioId,
    'pedido_id' => $pedDeliveryId,
    'monto' => 4800.00,
    'metodo' => 'efectivo',
    'estado' => 'pendiente',
    'rendido' => 0 // Dinero aún no en caja
]);
$pagoDelivery->guardar();

// Asignar repartidor
$db = Pedido::getDB();
$db->query("INSERT INTO pedido_repartidor (pedido_id, repartidor_id, hora_asignado, estado) VALUES ({$pedDeliveryId}, {$repartidorId}, NOW(), 'asignado')");

echo "    Pedido delivery #{$pedDeliveryId} en camino con repartidor #{$repartidorId}.\n";

// Sub-paso B1: Repartidor cobra en puerta y marca entregado
echo "    Sub-paso B1: Repartidor entrega pedido y cobra efectivo en mano del cliente...\n";
$exitoDelivery = $pedDelivery->marcarEntregado();
assert($exitoDelivery === true, "Fallo al marcar entregado");

$pagoB1 = Pago::where('pedido_id', $pedDeliveryId);
echo "    Validando estado tras entrega en puerta:\n";
echo "    - Estado pedido: {$pedDelivery->estado} (esperado: entregado)\n";
echo "    - Estado pago: {$pagoB1->estado} (esperado: aprobado)\n";
echo "    - Rendido a caja: {$pagoB1->rendido} (esperado: 0 - dinero en custodia del repartidor en la calle)\n";

assert($pagoB1->estado === 'aprobado', "Pago debe estar aprobado");
assert((int)$pagoB1->rendido === 0, "Rendido DEBE ser 0 (en custodia)");

// Verificar auditoría de caja: debe aparecer en "efectivo_en_calle", NO en "efectivo_rendido", NO en "efectivo_esperado"
$totalesB1 = $sesion->calcularTotalesTurno();
$deltaCalle = (float)$totalesB1['efectivo_en_calle'] - (float)$totalesPostA['efectivo_en_calle'];
$deltaRendido = (float)$totalesB1['efectivo_rendido'] - (float)$totalesPostA['efectivo_rendido'];
echo "    - Delta Efectivo en Calle: +\${$deltaCalle} (esperado: +4800.00)\n";
echo "    - Delta Efectivo Rendido en cajón: +\${$deltaRendido} (esperado: +0.00)\n";
assert(abs($deltaCalle - 4800.00) < 0.01, "Efectivo en calle debe aumentar en 4800");
assert(abs($deltaRendido - 0.00) < 0.01, "Efectivo rendido en cajón debe ser 0 aún");

// Verificar consulta de repartidores con rendición pendiente
$pendientes = Pago::repartidoresConRendicionPendiente($comercioId);
$encontrado = false;
foreach($pendientes as $pnd) {
    if((int)$pnd['repartidor_id'] === $repartidorId) {
        $encontrado = true;
        echo "    - Repartidor #{$repartidorId} listado en caja con pendiente: \${$pnd['total_pendiente']}\n";
        assert((float)$pnd['total_pendiente'] >= 4800.00, "Debe registrar al menos 4800 pendientes");
    }
}
assert($encontrado === true, "El repartidor debe figurar con rendición pendiente");

// Sub-paso B2: Repartidor regresa al local y entrega el dinero en caja
echo "\n    Sub-paso B2: Repartidor regresa al local y rinde el efectivo en caja física...\n";
$exitoRendir = $pagoB1->rendirACaja((int)$sesion->id);
assert($exitoRendir === true, "Fallo al rendir a caja");

$pagoB2 = Pago::find((int)$pagoB1->id);
echo "    Validando estado tras rendición en mostrador:\n";
echo "    - Rendido: {$pagoB2->rendido} (esperado: 1)\n";
echo "    - Fecha rendido: {$pagoB2->fecha_rendido}\n";
echo "    - Caja vinculada: #{$pagoB2->caja_sesion_id} (esperado: #{$sesion->id})\n";
assert((int)$pagoB2->rendido === 1, "Rendido debe ser 1");
assert(!empty($pagoB2->fecha_rendido), "Fecha rendido debe existir");
assert((int)$pagoB2->caja_sesion_id === (int)$sesion->id, "Caja vinculada debe ser la activa");

// Verificar auditoría final de caja:
$totalesFinal = $sesion->calcularTotalesTurno();
$deltaFinalCalle = (float)$totalesFinal['efectivo_en_calle'] - (float)$totalesPostA['efectivo_en_calle'];
$deltaFinalRendido = (float)$totalesFinal['efectivo_rendido'] - (float)$totalesPostA['efectivo_rendido'];
$deltaEsperadoTotal = (float)$totalesFinal['efectivo_esperado'] - (float)$totalesInicio['efectivo_esperado'];

echo "    Auditoría final tras rendición:\n";
echo "    - Delta Efectivo en Calle: \${$deltaFinalCalle} (esperado: 0.00)\n";
echo "    - Delta Efectivo Rendido en cajón: +\${$deltaFinalRendido} (esperado: +4800.00)\n";
echo "    - Delta Total Efectivo Esperado en cajón: +\${$deltaEsperadoTotal} (esperado: +8300.00 [3500 mostrador + 4800 rendido])\n";

assert(abs($deltaFinalCalle - 0.00) < 0.01, "Efectivo en calle debe haber vuelto a 0 para este pedido");
assert(abs($deltaFinalRendido - 4800.00) < 0.01, "Efectivo rendido debe haber aumentado en 4800");
assert(abs($deltaEsperadoTotal - 8300.00) < 0.01, "El efectivo esperado total debe haber aumentado en 8300");

echo "    -> Escenario B APROBADO EXITOSAMENTE.\n\n";

// =========================================================================
// VALIDACIÓN DE FORMULA DE AUDITORÍA
// =========================================================================
echo "[5] Verificando Fórmula de Auditoría General:\n";
echo "    Fondo Inicial:     \${$sesion->monto_apertura}\n";
echo "    + Mostrador:       \${$totalesFinal['efectivo_mostrador']}\n";
echo "    + Rendido Rep.:    \${$totalesFinal['efectivo_rendido']}\n";
echo "    = Total Esperado:  \${$totalesFinal['efectivo_esperado']}\n";
$calculado = (float)$sesion->monto_apertura + (float)$totalesFinal['efectivo_mostrador'] + (float)$totalesFinal['efectivo_rendido'];
assert(abs($calculado - (float)$totalesFinal['efectivo_esperado']) < 0.01, "Fórmula de arqueo coincide al centavo");

echo "\n=========================================================================\n";
echo "¡TODAS LAS PRUEBAS DEL CIRCUITO DE EFECTIVO PASARON SATISFACTORIAMENTE!\n";
echo "=========================================================================\n";
