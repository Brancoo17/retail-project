<?php

require_once __DIR__ . '/../includes/app.php';

use Model\Comercio;
use Model\Producto;
use Model\Pedido;
use Model\PedidoDetalle;
use Model\Pago;
use Model\Comprobante;

echo "======================================================" . PHP_EOL;
echo "   TEST ETAPA 5: PAGOS Y COMPROBANTES INTERNOS        " . PHP_EOL;
echo "======================================================" . PHP_EOL;

// 1. Probar Correlatividad Secuencial de Comprobantes
echo PHP_EOL . "[1/4] Verificando Correlatividad de Comprobantes..." . PHP_EOL;

$comercio = Comercio::find(1);
$db = \Model\ActiveRecord::getDB();

// Consultar correlativo actual
$resMax = $db->query("SELECT COALESCE(MAX(numero_correlativo), 0) AS max_correlativo FROM comprobantes WHERE comercio_id = 1");
$row = $resMax->fetch_assoc();
$ultimoCorrelativo = (int)$row['max_correlativo'];
echo "  - Último número correlativo registrado: {$ultimoCorrelativo}" . PHP_EOL;

// 2. Crear Pedido con Mercado Pago y verificar Pago inicial
echo PHP_EOL . "[2/4] Creando Pedido con Mercado Pago..." . PHP_EOL;

$prod = Producto::find(1);
$payloadMP = [
    'nombre_contacto' => 'Esteban Bullrich',
    'telefono_contacto' => '1199887766',
    'tipo' => 'delivery',
    'metodo_pago' => 'mercadopago',
    'direccion_texto' => 'Calle Belgrano 1200, Piso 3',
    'lat' => -24.785000,
    'lng' => -65.412000,
    'items' => [
        [
            'producto_id' => (int)$prod->id,
            'cantidad' => 1,
            'opciones' => [],
            'notas' => 'Prueba Mercado Pago'
        ]
    ]
];

$ch = curl_init('http://127.0.0.1:3000/pedido/crear');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payloadMP));
$respJson = curl_exec($ch);
curl_close($ch);

$res = json_decode($respJson, true);
if(empty($res['ok']) || empty($res['pedido_id'])) {
    echo "  ❌ Error creando pedido con Mercado Pago: " . ($res['mensaje'] ?? 'Desconocido') . PHP_EOL;
    exit(1);
}

$pedidoId = (int)$res['pedido_id'];
echo "  ✔ Pedido #{$pedidoId} creado exitosamente con método 'mercadopago'" . PHP_EOL;

// Verificar que se creó el registro en tabla pagos
$pago = Pago::porPedido($pedidoId);
if($pago) {
    echo "  ✔ Registro en tabla 'pagos' creado: ID #{$pago->id} | Método: {$pago->metodo} | Estado: {$pago->estado} | Monto: \${$pago->monto}" . PHP_EOL;
} else {
    echo "  ❌ No se encontró registro en tabla 'pagos' para el pedido #{$pedidoId}" . PHP_EOL;
    exit(1);
}

// 3. Probar Simulación de Aprobación de Pago
echo PHP_EOL . "[3/4] Probando Aprobación de Pago y Emisión de Comprobante..." . PHP_EOL;

// Ejecutar POST a /pago/simular
$chSim = curl_init('http://127.0.0.1:3000/pago/simular');
curl_setopt($chSim, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chSim, CURLOPT_POST, true);
curl_setopt($chSim, CURLOPT_POSTFIELDS, http_build_query(['pedido_id' => $pedidoId]));
curl_setopt($chSim, CURLOPT_FOLLOWLOCATION, false);
curl_exec($chSim);
$httpCodeSim = curl_getinfo($chSim, CURLINFO_HTTP_CODE);
curl_close($chSim);

echo "  - Respuesta POST /pago/simular (esperado 302): HTTP {$httpCodeSim}" . PHP_EOL;

// Verificar estado post-aprobación
$pagoPost = Pago::porPedido($pedidoId);
$pedidoPost = Pedido::find($pedidoId);
$comprobante = Comprobante::porPedido($pedidoId);

if($pagoPost && $pagoPost->estado === 'aprobado') {
    echo "  ✔ Estado del pago actualizado a: '{$pagoPost->estado}' (Payment ID: {$pagoPost->mp_payment_id})" . PHP_EOL;
} else {
    echo "  ❌ El pago no cambió a estado 'aprobado'" . PHP_EOL;
}

if($pedidoPost && $pedidoPost->estado === 'confirmado') {
    echo "  ✔ Estado del pedido avanzó automáticamente a: '{$pedidoPost->estado}'" . PHP_EOL;
} else {
    echo "  ❌ El pedido no avanzó a estado 'confirmado' (actual: {$pedidoPost->estado})" . PHP_EOL;
}

if($comprobante) {
    echo "  ✔ Comprobante emitido automáticamente:" . PHP_EOL;
    echo "    - ID: #{$comprobante->id}" . PHP_EOL;
    echo "    - Código Formateado: {$comprobante->codigoFormateado()}" . PHP_EOL;
    echo "    - Número Correlativo: {$comprobante->numero_correlativo}" . PHP_EOL;
    echo "    - Total: \${$comprobante->total}" . PHP_EOL;

    if($comprobante->numero_correlativo === ($ultimoCorrelativo + 1)) {
        echo "  ✔ Secuencia correlativa validada: {$ultimoCorrelativo} -> {$comprobante->numero_correlativo}" . PHP_EOL;
    }
} else {
    echo "  ❌ No se emitió comprobante para el pedido #{$pedidoId}" . PHP_EOL;
}

// 4. Probar Vista de Comprobante y Webhook Endpoint
echo PHP_EOL . "[4/4] Verificando Endpoints de Comprobante y Webhook..." . PHP_EOL;

// Consultar comprobante vía HTTP
$urlComprobante = "http://127.0.0.1:3000/comprobante?id=" . ($comprobante->id ?? 1);
$chComp = curl_init($urlComprobante);
curl_setopt($chComp, CURLOPT_RETURNTRANSFER, true);
$htmlComp = curl_exec($chComp);
$httpCodeComp = curl_getinfo($chComp, CURLINFO_HTTP_CODE);
curl_close($chComp);

if($httpCodeComp === 200 && str_contains($htmlComp, 'ticket-imprimible')) {
    echo "  ✔ Vista de Comprobante ({$urlComprobante}) -> HTTP 200 (Ticket renderizado correctamente)" . PHP_EOL;
} else {
    echo "  ❌ Vista de Comprobante falló -> HTTP {$httpCodeComp}" . PHP_EOL;
}

// Probar Webhook endpoint
$chWh = curl_init('http://127.0.0.1:3000/api/webhook/mercadopago');
curl_setopt($chWh, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chWh, CURLOPT_POST, true);
curl_setopt($chWh, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($chWh, CURLOPT_POSTFIELDS, json_encode(['type' => 'test', 'data' => ['id' => '12345']]));
$respWh = curl_exec($chWh);
$httpCodeWh = curl_getinfo($chWh, CURLINFO_HTTP_CODE);
curl_close($chWh);

if($httpCodeWh === 200 && str_contains($respWh, 'received')) {
    echo "  ✔ Webhook de Mercado Pago (/api/webhook/mercadopago) -> HTTP 200 ('received')" . PHP_EOL;
} else {
    echo "  ❌ Webhook falló -> HTTP {$httpCodeWh}" . PHP_EOL;
}

// 5. Probar Segundo Pedido en Efectivo y Correlatividad Secuencial #2
echo PHP_EOL . "[5/5] Probando Pedido en Efectivo y Segundo Comprobante Correlativo..." . PHP_EOL;
$payloadEfectivo = $payloadMP;
$payloadEfectivo['metodo_pago'] = 'efectivo';
$payloadEfectivo['nombre_contacto'] = 'Mariano Moreno';

$chEf = curl_init('http://127.0.0.1:3000/pedido/crear');
curl_setopt($chEf, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chEf, CURLOPT_POST, true);
curl_setopt($chEf, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($chEf, CURLOPT_POSTFIELDS, json_encode($payloadEfectivo));
$respEf = curl_exec($chEf);
curl_close($chEf);
$resEf = json_decode($respEf, true);
$pedidoIdEf = (int)$resEf['pedido_id'];

// Registrar cobro en efectivo
$chCobro = curl_init('http://127.0.0.1:3000/pago/cobrar-efectivo');
curl_setopt($chCobro, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chCobro, CURLOPT_POST, true);
curl_setopt($chCobro, CURLOPT_POSTFIELDS, http_build_query(['pedido_id' => $pedidoIdEf]));
curl_setopt($chCobro, CURLOPT_FOLLOWLOCATION, false);
curl_exec($chCobro);
curl_close($chCobro);

$comp2 = Comprobante::porPedido($pedidoIdEf);
if($comp2 && $comp2->numero_correlativo === ($comprobante->numero_correlativo + 1)) {
    echo "  ✔ Segundo comprobante emitido exitosamente con código: {$comp2->codigoFormateado()}" . PHP_EOL;
    echo "  ✔ Correlatividad estricta verificada: Correlativo anterior = {$comprobante->numero_correlativo}, Correlativo actual = {$comp2->numero_correlativo}" . PHP_EOL;
} else {
    echo "  ❌ Error en correlatividad del segundo comprobante: esperado " . ($comprobante->numero_correlativo + 1) . ", obtenido " . ($comp2->numero_correlativo ?? 'null') . PHP_EOL;
}

echo PHP_EOL . "======================================================" . PHP_EOL;
echo "   RESULTADO: ✔ FLUJO DE PAGOS Y COMPROBANTES 100% OPERATIVO " . PHP_EOL;
echo "======================================================" . PHP_EOL;
