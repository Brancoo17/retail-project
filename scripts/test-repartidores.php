<?php

require_once __DIR__ . '/../includes/app.php';

use Model\Usuario;
use Model\Pedido;
use Model\PedidoDetalle;
use Model\PedidoRepartidor;
use Model\Pago;
use Model\CajaSesion;

echo "=== INICIANDO TEST AUTOMATIZADO: ETAPA 7 - MÓDULO DE REPARTIDORES ===\n\n";

$comercioId = 1;

// --------------------------------------------------------------------------
// TEST 1: CRUD DE REPARTIDORES EN ADMINISTRACIÓN
// --------------------------------------------------------------------------
echo "[TEST 1] Verificando CRUD de Repartidores en Administración...\n";

// Alta de repartidor de prueba
$emailTest = 'moto_test_' . time() . '@retail.com';
$repartidorTest = new Usuario([
    'comercio_id' => $comercioId,
    'nombre' => 'Marcos Moto Test',
    'email' => $emailTest,
    'password' => 'clave123456',
    'telefono' => '1133445566',
    'rol' => 'repartidor',
    'confirmado' => 1
]);
$repartidorTest->hashPassword();
$resCrear = $repartidorTest->crear();
assert($resCrear['resultado'] === true, "El repartidor debe crearse con éxito");
$driverId = (int)$resCrear['id'];
echo "  ✔ Repartidor de prueba creado ID #{$driverId} ({$emailTest})\n";

// Validar recuperación y métodos helper
$driverRecargado = Usuario::find($driverId);
assert($driverRecargado !== null, "El repartidor debe existir en la base de datos");
assert($driverRecargado->rol === 'repartidor', "El rol debe ser 'repartidor'");
assert((int)$driverRecargado->confirmado === 1, "Debe estar activo/confirmado");
assert($driverRecargado->comprobarPasswordAndVerificado('clave123456'), "La contraseña debe validar con hash");

// Actualización de datos
$driverRecargado->nombre = 'Marcos Moto Express';
$driverRecargado->telefono = '1199887766';
$resAct = $driverRecargado->guardar();
$okAct = is_array($resAct) ? (bool)($resAct['resultado'] ?? false) : (bool)$resAct;
assert($okAct, "Los datos del repartidor deben actualizarse");

$driverVerif = Usuario::find($driverId);
assert($driverVerif->nombre === 'Marcos Moto Express', "El nombre debe estar actualizado");
assert($driverVerif->telefono === '1199887766', "El teléfono debe estar actualizado");

// Verificar métricas iniciales del repartidor
assert($driverVerif->pedidosEnCurso() === 0, "No debe tener pedidos en curso inicialmente");
assert($driverVerif->pedidosEntregadosHoy() === 0, "No debe tener entregas hoy inicialmente");
assert($driverVerif->totalPorRendir() === 0.0, "No debe tener dinero por rendir inicialmente");
echo "  ✔ Métricas iniciales del repartidor verificadas en cero.\n\n";

// --------------------------------------------------------------------------
// TEST 2: ASIGNACIÓN Y DESPACHO DE DELIVERY DESDE COCINA
// --------------------------------------------------------------------------
echo "[TEST 2] Creando pedido delivery y asignando al repartidor #{$driverId}...\n";

$pedidoTest = new Pedido([
    'comercio_id' => $comercioId,
    'usuario_id' => null,
    'nombre_contacto' => 'Cliente Test Delivery',
    'telefono_contacto' => '1144556677',
    'direccion_texto' => 'Av. San Martín 750, 3B',
    'lat' => -24.7850000,
    'lng' => -65.4120000,
    'distancia_km' => 2.5,
    'costo_envio' => 600.00,
    'tipo' => 'delivery',
    'estado' => 'confirmado',
    'fecha_confirmado' => date('Y-m-d H:i:s'),
    'metodo_pago' => 'efectivo',
    'subtotal' => 5000.00,
    'total' => 5600.00
]);
$pedidoTest->guardar();
$pedidoId = (int)$pedidoTest->id;

// Crear detalle y pago asociado
$detalle = new PedidoDetalle([
    'pedido_id' => $pedidoId,
    'producto_id' => 1,
    'nombre_producto' => 'Hamburguesa Especial',
    'opciones_texto' => 'Salsas: Mayonesa, Mostaza',
    'cantidad' => 1,
    'precio_unitario' => 5000.00,
    'subtotal' => 5000.00
]);
$detalle->guardar();

$pago = new Pago([
    'pedido_id' => $pedidoId,
    'metodo' => 'efectivo',
    'estado' => 'pendiente',
    'monto' => 5600.00,
    'rendido' => 0
]);
$pago->guardar();

echo "  ✔ Pedido #{$pedidoId} creado con total $5.600 en efectivo.\n";

// Despachar delivery con el repartidor
$despachoOk = $pedidoTest->despacharDelivery($driverId);
assert($despachoOk, "El pedido debe despacharse con éxito");

$pedidoRecargado = Pedido::find($pedidoId);
assert($pedidoRecargado->estado === 'en_camino', "El pedido debe pasar a estado 'en_camino'");
assert($driverVerif->pedidosEnCurso() === 1, "El repartidor debe registrar 1 pedido en curso");

// Verificar consulta de pedidos en curso del repartidor
$enCursoRepartidor = Pedido::asignadosARepartidor($driverId, true);
assert(count($enCursoRepartidor) === 1, "Debe listar 1 pedido asignado en curso");
assert((int)$enCursoRepartidor[0]->id === $pedidoId, "El ID del pedido asignado debe coincidir");
echo "  ✔ Pedido #{$pedidoId} asignado a repartidor #{$driverId} en estado 'en_camino'.\n\n";

// --------------------------------------------------------------------------
// TEST 3: CONFIRMACIÓN DE ENTREGA EN MANO POR EL REPARTIDOR
// --------------------------------------------------------------------------
echo "[TEST 3] Simulando confirmación de entrega por el repartidor...\n";

// El repartidor llega a destino y confirma la entrega
$entregaOk = $pedidoRecargado->marcarEntregado();
assert($entregaOk, "El pedido debe marcarse como entregado correctamente");

$pedidoEntregado = Pedido::find($pedidoId);
assert($pedidoEntregado->estado === 'entregado', "El pedido debe estar en estado 'entregado'");

$asig = $pedidoEntregado->asignacionRepartidor();
assert($asig !== null, "La asignación debe existir");
assert($asig->estado === 'entregado', "La asignación debe estar en 'entregado'");
assert(!empty($asig->hora_entregado), "Debe registrarse la hora de entrega");

// El pago en efectivo pasa a aprobado con rendido = 0 (efectivo en mano del repartidor)
$pagoActualizado = Pago::porPedido($pedidoId);
assert($pagoActualizado !== null, "El registro de pago debe existir");
assert($pagoActualizado->estado === 'aprobado', "El pago debe estar aprobado");
assert((int)$pagoActualizado->rendido === 0, "El pago NO debe estar rendido aún (está en la calle)");

// Validar métricas actualizadas del repartidor
assert($driverVerif->pedidosEnCurso() === 0, "Ya no debe tener pedidos en curso");
assert($driverVerif->pedidosEntregadosHoy() === 1, "Debe registrar 1 entrega completada hoy");
assert($driverVerif->totalPorRendir() === 5600.00, "Debe tener $5.600 en mano pendiente de rendir");
echo "  ✔ Pedido entregado: $5.600 en efectivo en custodia del repartidor.\n\n";

// --------------------------------------------------------------------------
// TEST 4: RENDICIÓN DE EFECTIVO EN CAJA
// --------------------------------------------------------------------------
echo "[TEST 4] Simulando rendición del efectivo en caja...\n";

// Asegurar sesión de caja activa
$sesionCaja = CajaSesion::sesionActiva($comercioId);
if(!$sesionCaja) {
    $sesionCaja = CajaSesion::abrir($comercioId, 2, 10000.00, 'Test Rendición');
}

// Simular la rendición que ejecuta CocinaController::rendirRepartidor()
$pagosARendir = Pago::porRendirRepartidor($driverId);
assert(count($pagosARendir) === 1, "Debe haber exactamente 1 pago por rendir");

foreach($pagosARendir as $p) {
    $p->rendido = 1;
    $p->fecha_rendido = date('Y-m-d H:i:s');
    $p->caja_sesion_id = (int)$sesionCaja->id;
    $p->actualizar();
}

assert($driverVerif->totalPorRendir() === 0.0, "El efectivo por rendir debe volver a $0.00 tras la rendición");
echo "  ✔ Efectivo rendido en caja #{$sesionCaja->id}. Repartidor queda al día ($0,00).\n\n";

// --------------------------------------------------------------------------
// TEST 5: BAJA SEGURA DEL REPARTIDOR
// --------------------------------------------------------------------------
echo "[TEST 5] Probando baja segura del repartidor con historial...\n";

// Como tiene entregas en su historial, no se debe borrar físicamente para no romper integridad
// Se desactiva con confirmado = 0
assert($driverVerif->totalPedidosEntregados() > 0, "Debe tener entregas en el historial");
$driverVerif->confirmado = 0;
$driverVerif->guardar();

$driverDesactivado = Usuario::find($driverId);
assert((int)$driverDesactivado->confirmado === 0, "El repartidor debe figurar inactivo/desactivado");
echo "  ✔ Repartidor desactivado con éxito manteniendo historial relacional íntegro.\n\n";

// --------------------------------------------------------------------------
// LIMPIEZA
// --------------------------------------------------------------------------
echo "[LIMPIEZA] Limpiando registros de prueba...\n";
if($asig) $asig->eliminar();
if($pagoActualizado) $pagoActualizado->eliminar();
$detalle->eliminar();
$pedidoTest->eliminar();
$driverDesactivado->eliminar();
echo "  ✔ Registros de prueba eliminados.\n\n";

echo "=========================================================================\n";
echo "¡TODAS LAS PRUEBAS DE LA ETAPA 7 (REPARTIDORES) PASARON SATISFACTORIAMENTE!\n";
echo "=========================================================================\n";
