<?php

require_once __DIR__ . '/../includes/app.php';

use Model\Categoria;
use Model\Aderezo;
use Model\Producto;
use Model\ProductoOpcion;
use Model\Pedido;
use Model\PedidoDetalle;
use Model\CajaSesion;

echo "=== INICIANDO SUITE DE TESTS: SISTEMA DE ADEREZOS Y SALSAS ===\n\n";

$comercioId = 1;

// --------------------------------------------------------------------------
// TEST 1: CATEGORÍAS Y PERMITE_ADEREZOS
// --------------------------------------------------------------------------
echo "[TEST 1] Verificando soporte de 'permite_aderezos' en categorías...\n";
$categorias = Categoria::porComercio($comercioId);
assert(!empty($categorias), "Debe haber categorías en la base de datos");

$catHabilitada = null;
foreach($categorias as $cat) {
    if((int)$cat->permite_aderezos === 1) {
        $catHabilitada = $cat;
        break;
    }
}

if(!$catHabilitada) {
    // Si ninguna tiene permite_aderezos, habilitamos la primera para la prueba
    $catHabilitada = $categorias[0];
    $catHabilitada->permite_aderezos = 1;
    $catHabilitada->guardar();
    echo "    Habilitada categoría '{$catHabilitada->nombre}' con permite_aderezos = 1\n";
} else {
    echo "    Categoría '{$catHabilitada->nombre}' encontrada con permite_aderezos = 1\n";
}

$catRecargada = Categoria::find($catHabilitada->id);
assert((int)$catRecargada->permite_aderezos === 1, "La columna permite_aderezos debe persistir en base de datos");
echo "    OK: Persistencia de permite_aderezos verificada.\n\n";

// --------------------------------------------------------------------------
// TEST 2: CATÁLOGO DE ADEREZOS (CRUD)
// --------------------------------------------------------------------------
echo "[TEST 2] Verificando modelo y catálogo Aderezo...\n";
$aderezos = Aderezo::porComercio($comercioId, true);
echo "    Aderezos activos encontrados: " . count($aderezos) . "\n";
foreach($aderezos as $ad) {
    echo "     - {$ad->nombre} (ID: {$ad->id}, Orden: {$ad->orden})\n";
}
assert(count($aderezos) >= 5, "Debe haber al menos 5 aderezos precargados");

// Crear aderezo de prueba
$nuevoAderezo = new Aderezo([
    'comercio_id' => $comercioId,
    'nombre' => 'Salsa Picante Especial Test',
    'activo' => 1,
    'orden' => 99
]);
$resGuardar = $nuevoAderezo->guardar();
assert($resGuardar['resultado'] === true, "El aderezo debe guardarse correctamente");
$aderezoTestId = (int)$nuevoAderezo->id;
echo "    OK: Creado aderezo de prueba ID #{$aderezoTestId}\n";

// Modificar aderezo
$aderezoAEditar = Aderezo::find($aderezoTestId);
assert($aderezoAEditar !== null, "El aderezo recién creado debe existir");
$aderezoAEditar->nombre = 'Salsa Picante Ultra Test';
$aderezoAEditar->activo = 0;
$aderezoAEditar->guardar();

$aderezoDesactivado = Aderezo::find($aderezoTestId);
assert((int)$aderezoDesactivado->activo === 0, "El aderezo debe estar desactivado");
assert($aderezoDesactivado->nombre === 'Salsa Picante Ultra Test', "El nombre debe estar actualizado");

// Eliminar aderezo de prueba
$aderezoDesactivado->eliminar();
$aderezoEliminado = Aderezo::find($aderezoTestId);
assert($aderezoEliminado === null, "El aderezo de prueba debe haber sido eliminado");
echo "    OK: CRUD completo de Aderezo verificado con éxito.\n\n";

// --------------------------------------------------------------------------
// TEST 3: PRODUCTO CON PERMITE_ADEREZOS
// --------------------------------------------------------------------------
echo "[TEST 3] Verificando Producto::porComercio con flag permite_aderezos...\n";
$productos = Producto::porComercio($comercioId, null, true);
$prodConAderezos = null;
foreach($productos as $p) {
    if((int)$p->permite_aderezos === 1) {
        $prodConAderezos = $p;
        break;
    }
}
if(!$prodConAderezos) {
    // Asociar primer producto a la categoría con aderezos
    $prodConAderezos = $productos[0];
    $prodConAderezos->categoria_id = (int)$catHabilitada->id;
    $prodConAderezos->guardar();
    // Reconsultar
    $productos = Producto::porComercio($comercioId, null, true);
    foreach($productos as $p) {
        if((int)$p->permite_aderezos === 1) {
            $prodConAderezos = $p;
            break;
        }
    }
}
assert($prodConAderezos !== null, "Debe existir al menos un producto cuya categoría permita aderezos");
echo "    Producto con salsas habilitadas: '{$prodConAderezos->nombre}' (Cat: {$prodConAderezos->categoria_nombre})\n";
assert((int)$prodConAderezos->permite_aderezos === 1, "permite_aderezos debe ser 1");
echo "    OK: Flag de aderezos transmitido correctamente al producto.\n\n";

// --------------------------------------------------------------------------
// TEST 4: SIMULACIÓN PEDIDO ONLINE CON ADEREZOS
// --------------------------------------------------------------------------
echo "[TEST 4] Simulando creación de Pedido Online con Aderezos...\n";
$salsasCliente = ['Mayonesa', 'Mostaza', 'Salsa Barbacoa'];
$precioBaseProd = (float)$prodConAderezos->precio;

// Simulamos la lógica de formato de PedidoController::crear()
$salsasLimpias = [];
foreach($salsasCliente as $ad) {
    $nom = trim(strip_tags((string)$ad));
    if(!empty($nom)) $salsasLimpias[] = $nom;
}
$textoOpciones = [];
if(!empty($salsasLimpias)) {
    $textoOpciones[] = "Salsas: " . implode(', ', $salsasLimpias);
}
$opcionesTextoFinal = implode(' | ', $textoOpciones);

echo "    Texto opciones generado: '{$opcionesTextoFinal}'\n";
assert(strpos($opcionesTextoFinal, 'Salsas: Mayonesa, Mostaza, Salsa Barbacoa') !== false, "El texto de opciones debe incluir las salsas");

// Crear pedido online en DB
$pedidoOnline = new Pedido([
    'comercio_id' => $comercioId,
    'usuario_id' => null,
    'nombre_contacto' => 'Juan Test Online',
    'telefono_contacto' => '1122334455',
    'direccion_texto' => 'Calle Falsa 123',
    'tipo' => 'delivery',
    'estado' => 'pendiente',
    'metodo_pago' => 'efectivo',
    'subtotal' => $precioBaseProd,
    'costo_envio' => 0.00,
    'descuento' => 0.00,
    'total' => $precioBaseProd
]);
$pedidoOnline->guardar();
$pedidoOnlineId = (int)$pedidoOnline->id;

$detalleOnline = new PedidoDetalle([
    'pedido_id' => $pedidoOnlineId,
    'producto_id' => $prodConAderezos->id,
    'nombre_producto' => $prodConAderezos->nombre,
    'opciones_texto' => $opcionesTextoFinal,
    'cantidad' => 1,
    'precio_unitario' => $precioBaseProd,
    'subtotal' => $precioBaseProd
]);
$detalleOnline->guardar();

$detalleRecargado = PedidoDetalle::where('pedido_id', $pedidoOnlineId);
assert($detalleRecargado !== null, "El detalle del pedido debe existir");
assert($detalleRecargado->opciones_texto === "Salsas: Mayonesa, Mostaza, Salsa Barbacoa", "opciones_texto debe coincidir con las salsas");
assert((float)$detalleRecargado->subtotal === $precioBaseProd, "Las salsas no deben alterar el subtotal ($0.00)");
echo "    OK: Pedido online #{$pedidoOnlineId} persistido con salsas gratuitas en opciones_texto.\n\n";

// --------------------------------------------------------------------------
// TEST 5: SIMULACIÓN PEDIDO MOSTRADOR (POS) CON ADEREZOS + OPCIONES + NOTA
// --------------------------------------------------------------------------
echo "[TEST 5] Simulando creación de Pedido Mostrador con Salsas, Opciones y Notas...\n";
$salsasPos = ['Alioli', 'Chimichurri'];
$notaPos = 'Pan bien tostado';

// Buscamos si el producto tiene opciones
$opcionesProd = $prodConAderezos->opciones(true);
$opcionIdElegida = null;
$precioAdicionalOpc = 0.0;
$textoOpcionesPos = [];

// Salsas
if(!empty($salsasPos)) {
    $textoOpcionesPos[] = "Salsas: " . implode(', ', $salsasPos);
}

// Opciones
if(!empty($opcionesProd)) {
    $opc = $opcionesProd[0];
    $opcionIdElegida = (int)$opc->id;
    $precioAdicionalOpc = (float)$opc->precio_adicional;
    $textoOpcionesPos[] = $opc->nombre . ($precioAdicionalOpc > 0 ? ' (+$' . number_format($precioAdicionalOpc, 2, ',', '.') . ')' : '');
}

// Nota
if(!empty($notaPos)) {
    $textoOpcionesPos[] = "Notas: " . $notaPos;
}

$unitarioPos = $precioBaseProd + $precioAdicionalOpc;
$subtotalPos = $unitarioPos * 2; // 2 unidades

$pedidoPos = new Pedido([
    'comercio_id' => $comercioId,
    'usuario_id' => null,
    'nombre_contacto' => 'Pedro Mostrador Test',
    'telefono_contacto' => 'Mostrador',
    'direccion_texto' => 'Local - Venta Presencial',
    'tipo' => 'retiro',
    'estado' => 'confirmado',
    'fecha_confirmado' => date('Y-m-d H:i:s'),
    'metodo_pago' => 'efectivo',
    'subtotal' => $subtotalPos,
    'costo_envio' => 0.00,
    'descuento' => 0.00,
    'total' => $subtotalPos
]);
$pedidoPos->guardar();
$pedidoPosId = (int)$pedidoPos->id;

$detallePos = new PedidoDetalle([
    'pedido_id' => $pedidoPosId,
    'producto_id' => $prodConAderezos->id,
    'nombre_producto' => $prodConAderezos->nombre,
    'opciones_texto' => implode(' | ', $textoOpcionesPos),
    'cantidad' => 2,
    'precio_unitario' => $unitarioPos,
    'subtotal' => $subtotalPos
]);
$detallePos->guardar();

$detallePosRecargado = PedidoDetalle::where('pedido_id', $pedidoPosId);
assert($detallePosRecargado !== null, "El detalle del pedido mostrador debe existir");
assert(strpos($detallePosRecargado->opciones_texto, 'Salsas: Alioli, Chimichurri') !== false, "Debe contener las salsas");
assert(strpos($detallePosRecargado->opciones_texto, 'Notas: Pan bien tostado') !== false, "Debe contener las notas");
assert((float)$detallePosRecargado->subtotal === $subtotalPos, "El subtotal debe reflejar solo precio base + variantes");
echo "    Detalle mostrador opciones_texto: '{$detallePosRecargado->opciones_texto}'\n";
echo "    OK: Pedido mostrador #{$pedidoPosId} persistido con éxito.\n\n";

// --------------------------------------------------------------------------
// LIMPIEZA
// --------------------------------------------------------------------------
echo "[LIMPIEZA] Eliminando registros temporales de prueba...\n";
$detalleOnline->eliminar();
$pedidoOnline->eliminar();
$detallePos->eliminar();
$pedidoPos->eliminar();
echo "    OK: Registros temporales eliminados.\n\n";

echo "=== TODOS LOS TESTS DEL SISTEMA DE ADEREZOS COMPLETADOS CON ÉXITO ===\n";
