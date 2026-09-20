<?php

require_once __DIR__ . '/../includes/app.php';

use Model\Pedido;
use Model\PedidoDetalle;
use Model\Pago;
use Model\CajaSesion;
use Model\Comprobante;
use Model\Producto;
use Model\Insumo;
use Model\MovimientoStock;

echo "=== INICIANDO TEST AUTOMATIZADO: PEDIDOS PRESENCIALES (POS MOSTRADOR) ===\n\n";

$comercioId = 1;

// 1. Obtener sesión de caja activa
$sesion = CajaSesion::sesionActiva($comercioId);
if(!$sesion) {
    echo "[1] Abriendo sesión de caja de prueba...\n";
    $sesion = CajaSesion::abrir($comercioId, 2, 5000.00, 'Test POS Presencial');
} else {
    echo "[1] Usando sesión de caja activa ID #{$sesion->id}...\n";
}

$totalesInicio = $sesion->calcularTotalesTurno();
echo "    Total mostrador inicial: \${$totalesInicio['efectivo_mostrador']}\n";
echo "    Total esperado inicial:  \${$totalesInicio['efectivo_esperado']}\n\n";

// 2. Obtener un producto activo del catálogo
$producto = Producto::where('activo', 1);
assert($producto !== null, "Debe existir al menos un producto activo");
$productoId = (int)$producto->id;
$precioProd = (float)$producto->precio;
$cant = 2;
$totalEsperado = $precioProd * $cant;
echo "[2] Producto seleccionado: '{$producto->nombre}' (ID #{$productoId}) - Precio: \${$precioProd} x {$cant} = \${$totalEsperado}\n\n";

// =========================================================================
// ESCENARIO 1: PEDIDO PRESENCIAL CON COBRO EN EL ACTO
// =========================================================================
echo "[3] ESCENARIO 1: Creando pedido presencial 'Carlos Mostrador' con COBRO EN EL ACTO...\n";

// Simulamos la lógica que ejecuta CocinaController::crearPedidoPresencial()
$pedido1 = new Pedido([
    'comercio_id' => $comercioId,
    'usuario_id' => null,
    'nombre_contacto' => 'Carlos Mostrador',
    'telefono_contacto' => 'Mostrador',
    'direccion_texto' => 'Local - Venta Presencial',
    'tipo' => 'retiro',
    'estado' => 'confirmado',
    'fecha_confirmado' => date('Y-m-d H:i:s'),
    'metodo_pago' => 'efectivo',
    'subtotal' => $totalEsperado,
    'costo_envio' => 0.00,
    'descuento' => 0.00,
    'total' => $totalEsperado
]);
$pedido1->guardar();
$ped1Id = (int)$pedido1->id;

// Detalle
$detalle1 = new PedidoDetalle([
    'pedido_id' => $ped1Id,
    'producto_id' => $productoId,
    'nombre_producto' => $producto->nombre,
    'opciones_texto' => '',
    'cantidad' => $cant,
    'precio_unitario' => $precioProd,
    'subtotal' => $totalEsperado
]);
$detalle1->guardar();

// Pago aprobado en el acto
$pago1 = new Pago([
    'comercio_id' => $comercioId,
    'pedido_id' => $ped1Id,
    'metodo' => 'efectivo',
    'estado' => 'aprobado',
    'monto' => $totalEsperado,
    'rendido' => 1,
    'fecha_rendido' => date('Y-m-d H:i:s'),
    'caja_sesion_id' => (int)$sesion->id,
    'mp_payment_id' => 'EFECTIVO-MOSTRADOR-' . date('YmdHis'),
    'fecha' => date('Y-m-d H:i:s')
]);
$pago1->guardar();

// Comprobante
$comp1 = Comprobante::generar($comercioId, $ped1Id, $totalEsperado);

echo "    Validando resultado Pedido 1 (Cobrado en el acto):\n";
echo "    - ID Pedido: #{$ped1Id}\n";
echo "    - Cliente: {$pedido1->nombre_contacto} ({$pedido1->telefono_contacto})\n";
echo "    - Estado Pedido: {$pedido1->estado} (esperado: confirmado)\n";
echo "    - Estado Pago: {$pago1->estado} (esperado: aprobado)\n";
echo "    - Rendido: {$pago1->rendido} (esperado: 1)\n";
echo "    - Caja Sesión vinculada: #{$pago1->caja_sesion_id} (esperado: #{$sesion->id})\n";
echo "    - Comprobante: " . ($comp1 ? $comp1->codigoFormateado() : "NO") . "\n";

assert($pedido1->estado === 'confirmado', "Estado pedido debe ser confirmado");
assert($pago1->estado === 'aprobado', "Pago debe ser aprobado");
assert((int)$pago1->rendido === 1, "Pago debe estar rendido a caja");
assert((int)$pago1->caja_sesion_id === (int)$sesion->id, "Debe estar vinculado a la sesión");
assert($comp1 !== null, "Debe emitir comprobante");

$totalesPost1 = $sesion->calcularTotalesTurno();
$deltaMostrador1 = (float)$totalesPost1['efectivo_mostrador'] - (float)$totalesInicio['efectivo_mostrador'];
$deltaEsperado1 = (float)$totalesPost1['efectivo_esperado'] - (float)$totalesInicio['efectivo_esperado'];
echo "    - Delta Efectivo Mostrador en caja: +\${$deltaMostrador1} (esperado: +\${$totalEsperado})\n";
echo "    - Delta Efectivo Esperado en cajón: +\${$deltaEsperado1} (esperado: +\${$totalEsperado})\n";

assert(abs($deltaMostrador1 - $totalEsperado) < 0.01, "Efectivo mostrador debe sumar el importe");
assert(abs($deltaEsperado1 - $totalEsperado) < 0.01, "Efectivo esperado debe sumar el importe");

echo "    -> ESCENARIO 1 APROBADO CON ÉXITO.\n\n";

// =========================================================================
// ESCENARIO 2: PEDIDO PRESENCIAL CON COBRO AL ENTREGAR
// =========================================================================
echo "[4] ESCENARIO 2: Creando pedido presencial 'Mesa 4' con COBRO AL ENTREGAR...\n";

$pedido2 = new Pedido([
    'comercio_id' => $comercioId,
    'usuario_id' => null,
    'nombre_contacto' => 'Mesa 4',
    'telefono_contacto' => 'Mostrador',
    'direccion_texto' => 'Local - Venta Presencial',
    'tipo' => 'retiro',
    'estado' => 'confirmado',
    'fecha_confirmado' => date('Y-m-d H:i:s'),
    'metodo_pago' => 'efectivo',
    'subtotal' => $totalEsperado,
    'costo_envio' => 0.00,
    'descuento' => 0.00,
    'total' => $totalEsperado
]);
$pedido2->guardar();
$ped2Id = (int)$pedido2->id;

// Detalle
$detalle2 = new PedidoDetalle([
    'pedido_id' => $ped2Id,
    'producto_id' => $productoId,
    'nombre_producto' => $producto->nombre,
    'opciones_texto' => '',
    'cantidad' => $cant,
    'precio_unitario' => $precioProd,
    'subtotal' => $totalEsperado
]);
$detalle2->guardar();

// Pago pendiente
$pago2 = new Pago([
    'comercio_id' => $comercioId,
    'pedido_id' => $ped2Id,
    'metodo' => 'efectivo',
    'estado' => 'pendiente',
    'monto' => $totalEsperado,
    'rendido' => 1,
    'caja_sesion_id' => null,
    'fecha' => date('Y-m-d H:i:s')
]);
$pago2->guardar();

echo "    Pedido #{$ped2Id} ingresado con pago pendiente.\n";

// Cocina lo pasa a despacho ("Listo en Local")
$pedido2->estado = 'en_camino';
$pedido2->guardar();

// Cliente retira en mostrador: cajero ejecuta cobrarYEntregarRetiro()
echo "    Cliente llega al mostrador: ejecutando cobrarYEntregarRetiro()...\n";
$exitoCobro2 = $pedido2->cobrarYEntregarRetiro((int)$sesion->id);
assert($exitoCobro2 === true, "Debe cobrar y entregar con éxito");

$pago2Actualizado = Pago::where('pedido_id', $ped2Id);
$comp2 = Comprobante::where('pedido_id', $ped2Id);

echo "    Validando resultado Pedido 2 tras cobro en mostrador:\n";
echo "    - Estado Pedido: {$pedido2->estado} (esperado: entregado)\n";
echo "    - Estado Pago: {$pago2Actualizado->estado} (esperado: aprobado)\n";
echo "    - Caja Sesión: #{$pago2Actualizado->caja_sesion_id} (esperado: #{$sesion->id})\n";
echo "    - Comprobante: " . ($comp2 ? $comp2->codigoFormateado() : "NO") . "\n";

assert($pedido2->estado === 'entregado', "Estado pedido debe ser entregado");
assert($pago2Actualizado->estado === 'aprobado', "Pago debe ser aprobado");
assert((int)$pago2Actualizado->caja_sesion_id === (int)$sesion->id, "Debe vincularse a la caja");
assert($comp2 !== null, "Debe emitirse comprobante");

$totalesPost2 = $sesion->calcularTotalesTurno();
$deltaMostradorTotal = (float)$totalesPost2['efectivo_mostrador'] - (float)$totalesInicio['efectivo_mostrador'];
$esperadoTotalAmbos = $totalEsperado * 2;
echo "    - Total acumulado ambos pedidos en mostrador: \${$deltaMostradorTotal} (esperado: \${$esperadoTotalAmbos})\n";
assert(abs($deltaMostradorTotal - $esperadoTotalAmbos) < 0.01, "Ambos pedidos deben estar en efectivo_mostrador");

echo "    -> ESCENARIO 2 APROBADO CON ÉXITO.\n\n";

// =========================================================================
// ESCENARIO 3: PEDIDO PRESENCIAL CON OPCIONES Y ADICIONALES (Extra Queso, etc.)
// =========================================================================
echo "[5] ESCENARIO 3: Creando pedido presencial con VARIANTES, ADICIONALES Y NOTAS...\n";

// Obtener opciones activas del producto (ej. Extra Queso ID 2, Tamaño Familiar ID 4)
$opcionesActivas = \Model\ProductoOpcion::porProducto($productoId, true);
assert(!empty($opcionesActivas), "El producto debe tener opciones configuradas");

$opcionesIds = array_map(fn($o) => (int)$o->id, $opcionesActivas);
$adicionalTotal = array_sum(array_map(fn($o) => (float)$o->precio_adicional, $opcionesActivas));
$precioUnitarioConOpciones = $precioProd + $adicionalTotal;
$cant3 = 1;
$totalEsperado3 = $precioUnitarioConOpciones * $cant3;
$notas3 = 'Sin orégano, masa bien crocante';

// Simulamos el procesamiento que hace CocinaController::crearPedidoPresencial()
$textoOpcionesParts = [];
foreach($opcionesIds as $opcId) {
    $opc = \Model\ProductoOpcion::find($opcId);
    if($opc) {
        $textoOpcionesParts[] = $opc->nombre . ((float)$opc->precio_adicional > 0 ? ' (+$' . number_format($opc->precio_adicional, 2, ',', '.') . ')' : '');
    }
}
$textoOpcionesParts[] = "Notas: " . $notas3;
$opcionesTextoFinal = implode(' | ', $textoOpcionesParts);

$pedido3 = new Pedido([
    'comercio_id' => $comercioId,
    'usuario_id' => null,
    'nombre_contacto' => 'Laura Variantes',
    'telefono_contacto' => 'Mostrador',
    'direccion_texto' => 'Local - Venta Presencial',
    'tipo' => 'retiro',
    'estado' => 'confirmado',
    'fecha_confirmado' => date('Y-m-d H:i:s'),
    'metodo_pago' => 'efectivo',
    'subtotal' => $totalEsperado3,
    'costo_envio' => 0.00,
    'descuento' => 0.00,
    'total' => $totalEsperado3
]);
$pedido3->guardar();
$ped3Id = (int)$pedido3->id;

$detalle3 = new PedidoDetalle([
    'pedido_id' => $ped3Id,
    'producto_id' => $productoId,
    'nombre_producto' => $producto->nombre,
    'opciones_texto' => $opcionesTextoFinal,
    'cantidad' => $cant3,
    'precio_unitario' => $precioUnitarioConOpciones,
    'subtotal' => $totalEsperado3
]);
$detalle3->guardar();

$pago3 = new Pago([
    'comercio_id' => $comercioId,
    'pedido_id' => $ped3Id,
    'metodo' => 'efectivo',
    'estado' => 'aprobado',
    'monto' => $totalEsperado3,
    'rendido' => 1,
    'fecha_rendido' => date('Y-m-d H:i:s'),
    'caja_sesion_id' => (int)$sesion->id,
    'mp_payment_id' => 'EFECTIVO-MOSTRADOR-' . date('YmdHis'),
    'fecha' => date('Y-m-d H:i:s')
]);
$pago3->guardar();

$comp3 = Comprobante::generar($comercioId, $ped3Id, $totalEsperado3);

echo "    Validando resultado Pedido 3 (Con opciones y notas):\n";
echo "    - ID Pedido: #{$ped3Id}\n";
echo "    - Cliente: {$pedido3->nombre_contacto}\n";
echo "    - Precio base: \${$precioProd} -> Precio final con opciones: \${$precioUnitarioConOpciones}\n";
echo "    - Texto en comanda: '{$detalle3->opciones_texto}'\n";
echo "    - Total pedido: \${$pedido3->total} (esperado: \${$totalEsperado3})\n";
echo "    - Comprobante: " . ($comp3 ? $comp3->codigoFormateado() : "NO") . "\n";

assert((float)$detalle3->precio_unitario === (float)$precioUnitarioConOpciones, "Precio unitario debe incluir adicionales");
assert((float)$pedido3->total === (float)$totalEsperado3, "Total pedido debe coincidir");
assert(str_contains($detalle3->opciones_texto, 'Extra Queso'), "Debe contener Extra Queso");
assert(str_contains($detalle3->opciones_texto, 'Tamaño Familiar'), "Debe contener Tamaño Familiar");
assert(str_contains($detalle3->opciones_texto, 'Notas: Sin orégano'), "Debe contener la nota especial");
assert($comp3 !== null, "Debe generar comprobante");

echo "    -> ESCENARIO 3 APROBADO CON ÉXITO.\n\n";

echo "=========================================================================\n";
echo "¡TODAS LAS PRUEBAS DE PEDIDOS PRESENCIALES PASARON EXITOSAMENTE!\n";
echo "=========================================================================\n";
