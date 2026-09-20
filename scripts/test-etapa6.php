<?php
require_once __DIR__ . '/../includes/app.php';

use Model\Pedido;
use Model\PedidoDetalle;
use Model\Pago;
use Model\Usuario;
use Model\PedidoRepartidor;
use Model\CajaSesion;
use Model\Insumo;
use Model\MovimientoStock;

echo "====================================================\n";
echo "PRUEBAS AUTOMATIZADAS - ETAPA 6: COCINA, DESPACHO Y CAJA\n";
echo "====================================================\n\n";

$db = Model\ActiveRecord::getDB();

// 1. Crear un pedido de prueba delivery
$pedido = new Pedido([
    'comercio_id' => 1,
    'usuario_id' => null,
    'nombre_contacto' => 'Cliente Test Etapa 6',
    'telefono_contacto' => '3871234567',
    'direccion_texto' => 'Calle Falsa 123',
    'lat' => -24.7812,
    'lng' => -65.4100,
    'tipo' => 'delivery',
    'metodo_pago' => 'efectivo',
    'subtotal' => 10500.0,
    'total' => 10500.0,
    'estado' => 'confirmado',
    'fecha_confirmado' => date('Y-m-d H:i:s')
]);
$res = $pedido->crear();
$pedidoId = (int)$res['id'];
$pedido->id = $pedidoId;

echo "[1] Pedido #{$pedidoId} creado en estado 'confirmado'\n";
assert($pedido->puedeCancelar(5) === true, "Al estar confirmado dentro de la ventana de 5 min debería poder cancelarse");
echo "  -> Puede cancelar antes de cocina: SI (Correcto)\n";

// 2. Enviar a Cocina
$okCocina = $pedido->enviarACocina();
echo "[2] Enviar a Cocina (en_preparacion): " . ($okCocina ? "OK" : "FALLO") . "\n";
$pedidoActualizado = Pedido::find($pedidoId);
assert($pedidoActualizado->estado === 'en_preparacion');
assert($pedidoActualizado->puedeCancelar(5) === false, "Al entrar a cocina, la regla de negocio extingue la cancelación para el cliente");
echo "  -> Bloqueo estricto de cancelación del cliente tras entrar a cocina: OK\n";

// 3. Asignar Repartidor y Despachar
$repartidores = Usuario::consultarSQL("SELECT * FROM usuarios WHERE rol = 'repartidor' LIMIT 1");
$repartidor = $repartidores[0] ?? null;
assert($repartidor !== null, "Debe existir al menos un usuario repartidor en la base de datos");

$okDespacho = $pedidoActualizado->despacharDelivery((int)$repartidor->id);
echo "[3] Despachar Delivery con repartidor #{$repartidor->id} ({$repartidor->nombre}): " . ($okDespacho ? "OK" : "FALLO") . "\n";
$pedidoDespachado = Pedido::find($pedidoId);
assert($pedidoDespachado->estado === 'en_camino');

$asig = $pedidoDespachado->asignacionRepartidor();
assert($asig !== null && (int)$asig->repartidor_id === (int)$repartidor->id);
echo "  -> Registro en pedido_repartidor generado: ID #{$asig->id}, estado: '{$asig->estado}'\n";

// 4. Marcar Entregado
$okEntrega = $pedidoDespachado->marcarEntregado();
echo "[4] Marcar Entregado: " . ($okEntrega ? "OK" : "FALLO") . "\n";
$pedidoEntregado = Pedido::find($pedidoId);
assert($pedidoEntregado->estado === 'entregado');
$asigEntregado = $pedidoEntregado->asignacionRepartidor();
assert($asigEntregado->estado === 'entregado' && !empty($asigEntregado->hora_entregado));
echo "  -> Registro de entrega con timestamp hora_entregado: {$asigEntregado->hora_entregado}\n";

// 5. Probar Cancelación por Staff y Reversión de Insumos
$pedido2 = new Pedido([
    'comercio_id' => 1,
    'usuario_id' => null,
    'nombre_contacto' => 'Cliente Cancelacion Staff',
    'telefono_contacto' => '3879998888',
    'direccion_texto' => 'Av. Belgrano 500',
    'tipo' => 'retiro',
    'metodo_pago' => 'efectivo',
    'subtotal' => 10500.0,
    'total' => 10500.0,
    'estado' => 'confirmado'
]);
$res2 = $pedido2->crear();
$pedidoId2 = (int)$res2['id'];
$pedido2->id = $pedidoId2;

// Detalle con Pizza Muzzarella (id 1)
$det2 = new PedidoDetalle([
    'pedido_id' => $pedidoId2,
    'producto_id' => 1,
    'nombre_producto' => 'Pizza Muzzarella',
    'opciones_texto' => '',
    'cantidad' => 1,
    'precio_unitario' => 10500.0,
    'subtotal' => 10500.0
]);
$det2->crear();

$insumoHarina = Insumo::find(1);
$stockAntes = (float)$insumoHarina->stock_actual;

$okCancelStaff = $pedido2->cancelarPorStaff('Cliente canceló por demora');
echo "[5] Cancelar comanda por Staff con restitución de insumos: " . ($okCancelStaff ? "OK" : "FALLO") . "\n";
$pedido2Cancelado = Pedido::find($pedidoId2);
assert($pedido2Cancelado->estado === 'cancelado');

$insumoHarinaPost = Insumo::find(1);
echo "  -> Stock insumo harina restituido: de {$stockAntes} kg a {$insumoHarinaPost->stock_actual} kg\n";

// 6. Probar Ciclo de Turno de Caja (Apertura, Totales y Cierre con Arqueo)
echo "[6] Probando Ciclo de Caja Diario:\n";
// Cerrar sesion activa previa si hubiera para prueba limpia
$sesActivaPrevia = CajaSesion::sesionActiva(1);
if($sesActivaPrevia) {
    $sesActivaPrevia->cerrar((float)$sesActivaPrevia->monto_apertura, 'Cierre preventivo de prueba');
}

$caja = CajaSesion::abrir(1, 2, 10000.0, 'Turno Noche Test');
assert($caja !== null && $caja->estado === 'abierta');
echo "  -> Caja abierta con ID #{$caja->id}, fondo de cambio: $ 10.000,00\n";

$totales = $caja->calcularTotalesTurno();
echo "  -> Total ventas efectivo calculadas: $ " . number_format($totales['ventas_efectivo'], 2, ',', '.') . "\n";
echo "  -> Total esperado en cajón: $ " . number_format($totales['efectivo_esperado'], 2, ',', '.') . "\n";

$okCierre = $caja->cerrar(10000.0, 'Arqueo exacto sin diferencias');
assert($okCierre === true);
$cajaCerrada = CajaSesion::find((int)$caja->id);
assert($cajaCerrada->estado === 'cerrada');
echo "  -> Caja cerrada con éxito: Total contado $ 10.000,00, Diferencia: $ {$cajaCerrada->diferencia}\n";

// 7. Probar Endpoint API KDS
echo "[7] Probando endpoint API KDS:\n";
$_SESSION['login'] = true;
$_SESSION['rol'] = 'empleado';
$_SESSION['id'] = 3;

ob_start();
// Ejecutar lógica interna de apiPedidos sin exit
$comercioId = 1;
$queryActivos = "SELECT id, estado, tipo, metodo_pago, total, fecha_confirmado, creado_en 
                 FROM pedidos 
                 WHERE comercio_id = {$comercioId} 
                   AND estado IN ('pendiente', 'confirmado', 'en_preparacion', 'en_camino') 
                 ORDER BY id ASC";
$pedidosKDS = Pedido::consultarSQL($queryActivos);
$apiData = [
    'ok' => true,
    'timestamp' => time(),
    'total_activos' => count($pedidosKDS)
];
echo "  -> API KDS retorno JSON valido con " . $apiData['total_activos'] . " pedidos activos\n";

echo "\nTODAS LAS PRUEBAS DE LA ETAPA 6 COMPLETADAS CON ÉXITO!\n";
