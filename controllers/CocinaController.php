<?php

namespace Controllers;

use MVC\Router;
use Model\Pedido;
use Model\Usuario;
use Model\PedidoRepartidor;
use Model\CajaSesion;
use Model\Comprobante;
use Model\Pago;
use Model\Categoria;
use Model\Producto;
use Model\PedidoDetalle;
use Model\ProductoInsumo;
use Model\Insumo;
use Model\MovimientoStock;
use Model\ProductoOpcion;
use Model\Aderezo;
use Classes\KdsHelper;

class CocinaController {

    /**
     * Tablero principal KDS (Kitchen Display System)
     */
    public static function index(Router $router): void {
        isStaff();

        $comercioId = 1;

        // Cargar todos los pedidos activos ordenados cronológicamente
        $queryActivos = "SELECT * FROM pedidos 
                         WHERE comercio_id = {$comercioId} 
                           AND estado IN ('pendiente', 'confirmado', 'en_preparacion', 'en_camino') 
                         ORDER BY id ASC";
        $pedidosActivos = Pedido::consultarSQL($queryActivos);

        $nuevos = [];
        $enCocina = [];
        $despacho = [];

        foreach($pedidosActivos as $ped) {
            if($ped->estado === 'pendiente' || $ped->estado === 'confirmado') {
                $nuevos[] = $ped;
            } elseif($ped->estado === 'en_preparacion') {
                $enCocina[] = $ped;
            } elseif($ped->estado === 'en_camino') {
                $despacho[] = $ped;
            }
        }

        // Cargar pedidos finalizados de hoy para la pestaña de historial
        $hoy = date('Y-m-d');
        $queryHoy = "SELECT * FROM pedidos 
                     WHERE comercio_id = {$comercioId} 
                       AND estado IN ('entregado', 'cancelado') 
                       AND DATE(creado_en) = '{$hoy}' 
                     ORDER BY id DESC LIMIT 50";
        $pedidosFinalizadosHoy = Pedido::consultarSQL($queryHoy);

        // Repartidores disponibles para despacho
        $queryRepartidores = "SELECT * FROM usuarios WHERE comercio_id = {$comercioId} AND rol = 'repartidor' AND confirmado = 1";
        $repartidores = Usuario::consultarSQL($queryRepartidores);

        // Sesión de caja activa
        $sesionCaja = CajaSesion::sesionActiva($comercioId);

        // Catálogo para pedidos presenciales en mostrador
        $categorias = Categoria::porComercio($comercioId, true);
        $productos = Producto::porComercio($comercioId, null, true);
        foreach($productos as $prod) {
            $prod->opciones = $prod->opciones(true);
        }

        $aderezos = Aderezo::porComercio($comercioId, true);

        // Resumen consolidado de producción (All-Day Counter) para comandas en preparación y nuevos
        $allDayData = KdsHelper::calcularAllDay(array_merge($nuevos, $enCocina));

        $router->render('cocina/index', [
            'titulo' => 'Consola de Cocina & Despacho (KDS)',
            'nuevos' => $nuevos,
            'enCocina' => $enCocina,
            'despacho' => $despacho,
            'finalizados' => $pedidosFinalizadosHoy,
            'repartidores' => $repartidores,
            'sesionCaja' => $sesionCaja,
            'categorias' => $categorias,
            'productos' => $productos,
            'aderezos' => $aderezos,
            'allDayItems' => $allDayData['items'],
            'totalPlatosEnMarcha' => $allDayData['total_platos']
        ]);
    }

    /**
     * Avanzar o actualizar estado de un pedido
     */
    public static function cambiarEstado(): void {
        isStaff();

        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /cocina');
            exit;
        }

        $pedidoId = (int)($_POST['pedido_id'] ?? 0);
        $nuevoEstado = $_POST['nuevo_estado'] ?? '';
        $pedido = Pedido::find($pedidoId);

        if(!$pedido || (int)$pedido->comercio_id !== 1) {
            header('Location: /cocina');
            exit;
        }

        $exito = false;

        switch($nuevoEstado) {
            case 'en_preparacion':
                $exito = $pedido->enviarACocina();
                break;
            case 'despachar_retiro':
                $exito = $pedido->despacharRetiro();
                break;
            case 'entregado':
                $exito = $pedido->marcarEntregado();
                break;
        }

        if($exito) {
            $_SESSION['alerta_rapida'] = ['tipo' => 'exito', 'mensaje' => "Pedido #{$pedidoId} actualizado a " . $pedido->estadoTexto()];
        } else {
            $_SESSION['alerta_rapida'] = ['tipo' => 'error', 'mensaje' => "No se pudo actualizar el estado del Pedido #{$pedidoId}"];
        }

        header('Location: /cocina');
        exit;
    }

    /**
     * Asignar repartidor y pasar a despacho
     */
    public static function asignarRepartidor(): void {
        isStaff();

        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /cocina');
            exit;
        }

        $pedidoId = (int)($_POST['pedido_id'] ?? 0);
        $repartidorId = (int)($_POST['repartidor_id'] ?? 0);

        $pedido = Pedido::find($pedidoId);
        $repartidor = Usuario::find($repartidorId);

        if(!$pedido || !$repartidor || $repartidor->rol !== 'repartidor') {
            $_SESSION['alerta_rapida'] = ['tipo' => 'error', 'mensaje' => 'Repartidor o pedido inválido'];
            header('Location: /cocina');
            exit;
        }

        if($pedido->despacharDelivery($repartidorId)) {
            $_SESSION['alerta_rapida'] = ['tipo' => 'exito', 'mensaje' => "Pedido #{$pedidoId} asignado a {$repartidor->nombre} y despachado"];
        } else {
            $_SESSION['alerta_rapida'] = ['tipo' => 'error', 'mensaje' => "Error al asignar repartidor al pedido #{$pedidoId}"];
        }

        header('Location: /cocina');
        exit;
    }

    /**
     * Cancelar un pedido desde el panel operativo
     */
    public static function cancelarPedido(): void {
        isStaff();

        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /cocina');
            exit;
        }

        $pedidoId = (int)($_POST['pedido_id'] ?? 0);
        $motivo = trim((string)($_POST['motivo'] ?? 'Cancelado por el comercio'));
        $pedido = Pedido::find($pedidoId);

        if(!$pedido) {
            header('Location: /cocina');
            exit;
        }

        if($pedido->cancelarPorStaff($motivo)) {
            $_SESSION['alerta_rapida'] = ['tipo' => 'exito', 'mensaje' => "Pedido #{$pedidoId} cancelado y stock de insumos restituido"];
        } else {
            $_SESSION['alerta_rapida'] = ['tipo' => 'error', 'mensaje' => "No se pudo cancelar el Pedido #{$pedidoId}"];
        }

        header('Location: /cocina');
        exit;
    }

    /**
     * API JSON para el polling del monitor KDS
     */
    public static function apiPedidos(): void {
        isStaff();

        $comercioId = 1;
        $queryActivos = "SELECT id, estado, tipo, metodo_pago, total, fecha_confirmado, creado_en 
                         FROM pedidos 
                         WHERE comercio_id = {$comercioId} 
                           AND estado IN ('pendiente', 'confirmado', 'en_preparacion', 'en_camino') 
                         ORDER BY id ASC";
        $pedidos = Pedido::consultarSQL($queryActivos);

        $resultado = [
            'ok' => true,
            'timestamp' => time(),
            'total_activos' => count($pedidos),
            'pedidos' => array_map(fn($p) => [
                'id' => (int)$p->id,
                'estado' => $p->estado,
                'tipo' => $p->tipo,
                'minutos' => $p->minutosTranscurridos(),
                'alerta_clase' => $p->alertaTiempoClase()
            ], $pedidos)
        ];

        header('Content-Type: application/json');
        echo json_encode($resultado);
        exit;
    }

    /**
     * Vista de Control y Arqueo de Caja Diario
     */
    public static function caja(Router $router): void {
        isStaff();

        $comercioId = 1;
        $sesionActiva = CajaSesion::sesionActiva($comercioId);
        $totalesTurno = $sesionActiva ? $sesionActiva->calcularTotalesTurno() : null;

        // Historial de comprobantes cobrados hoy
        $hoy = date('Y-m-d');
        $queryComprobantes = "SELECT c.*, p.nombre_contacto, p.metodo_pago, p.tipo, p.id as pedido_numero 
                              FROM comprobantes c
                              INNER JOIN pedidos p ON c.pedido_id = p.id
                              WHERE c.comercio_id = {$comercioId}
                                AND DATE(c.fecha_emision) = '{$hoy}'
                              ORDER BY c.numero_correlativo DESC";
        $db = Pedido::getDB();
        $resComp = $db->query($queryComprobantes);
        $comprobantesHoy = [];
        if($resComp) {
            while($c = $resComp->fetch_object()) {
                $comprobantesHoy[] = $c;
            }
        }

        // Resumen histórico de las últimas 10 sesiones de caja
        $querySesiones = "SELECT s.*, u.nombre as nombre_cajero 
                          FROM caja_sesiones s
                          LEFT JOIN usuarios u ON s.usuario_id = u.id
                          WHERE s.comercio_id = {$comercioId}
                          ORDER BY s.id DESC LIMIT 10";
        $resSes = $db->query($querySesiones);
        $ultimasSesiones = [];
        if($resSes) {
            while($s = $resSes->fetch_object()) {
                $ultimasSesiones[] = $s;
            }
        }

        $pendientesRendicion = Pago::repartidoresConRendicionPendiente($comercioId);

        $router->render('cocina/caja', [
            'titulo' => 'Control y Arqueo de Caja Diario',
            'sesionActiva' => $sesionActiva,
            'totalesTurno' => $totalesTurno,
            'pendientesRendicion' => $pendientesRendicion,
            'comprobantesHoy' => $comprobantesHoy,
            'ultimasSesiones' => $ultimasSesiones
        ]);
    }

    /**
     * Cobrar pedido de retiro en mostrador y marcar como entregado
     */
    public static function cobrarRetiro(): void {
        isStaff();

        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /cocina');
            exit;
        }

        $pedidoId = (int)($_POST['pedido_id'] ?? 0);
        $pedido = Pedido::find($pedidoId);

        if(!$pedido || (int)$pedido->comercio_id !== 1) {
            $_SESSION['alerta_rapida'] = ['tipo' => 'error', 'mensaje' => 'Pedido no encontrado'];
            header('Location: /cocina');
            exit;
        }

        $sesionActiva = CajaSesion::sesionActiva(1);
        $cajaSesionId = $sesionActiva ? (int)$sesionActiva->id : null;

        if($pedido->cobrarYEntregarRetiro($cajaSesionId)) {
            $_SESSION['alerta_rapida'] = ['tipo' => 'exito', 'mensaje' => "Pedido #{$pedidoId} cobrado y entregado en mostrador. Comprobante emitido."];
        } else {
            $_SESSION['alerta_rapida'] = ['tipo' => 'error', 'mensaje' => "Error al cobrar y entregar el pedido #{$pedidoId}"];
        }

        header('Location: /cocina');
        exit;
    }

    /**
     * Recibir rendición de efectivo en caja de un repartidor
     */
    public static function rendirRepartidor(): void {
        isStaff();

        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /cocina/caja');
            exit;
        }

        $repartidorId = (int)($_POST['repartidor_id'] ?? 0);
        $repartidor = Usuario::find($repartidorId);

        if(!$repartidor) {
            $_SESSION['alerta_rapida'] = ['tipo' => 'error', 'mensaje' => 'Repartidor no encontrado'];
            header('Location: /cocina/caja');
            exit;
        }

        $sesionActiva = CajaSesion::sesionActiva(1);
        if(!$sesionActiva) {
            $_SESSION['alerta_rapida'] = ['tipo' => 'error', 'mensaje' => 'Debe abrir una sesión de caja antes de recibir rendiciones de dinero'];
            header('Location: /cocina/caja');
            exit;
        }

        $pagos = Pago::porRendirRepartidor($repartidorId);
        if(empty($pagos)) {
            $_SESSION['alerta_rapida'] = ['tipo' => 'info', 'mensaje' => "El repartidor {$repartidor->nombre} no tiene efectivo pendiente de rendir"];
            header('Location: /cocina/caja');
            exit;
        }

        $totalRendido = 0.0;
        $cantidad = 0;
        foreach($pagos as $pago) {
            if($pago->rendirACaja((int)$sesionActiva->id)) {
                $totalRendido += (float)$pago->monto;
                $cantidad++;
            }
        }

        $_SESSION['alerta_rapida'] = [
            'tipo' => 'exito',
            'mensaje' => "Rendición recibida de {$repartidor->nombre}: $ " . number_format($totalRendido, 2, ',', '.') . " ({$cantidad} pedidos ingresados a caja física)"
        ];

        header('Location: /cocina/caja');
        exit;
    }

    /**
     * Abrir turno de caja con fondo inicial
     */
    public static function abrirCaja(): void {
        isStaff();

        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /cocina/caja');
            exit;
        }

        $montoApertura = (float)($_POST['monto_apertura'] ?? 0.0);
        $obs = trim((string)($_POST['observaciones'] ?? ''));
        $usuarioId = (int)($_SESSION['id'] ?? 0);

        $sesion = CajaSesion::abrir(1, $usuarioId, $montoApertura, $obs);

        if($sesion) {
            $_SESSION['alerta_rapida'] = ['tipo' => 'exito', 'mensaje' => 'Caja abierta con éxito. Turno iniciado con fondo de $ ' . number_format($montoApertura, 2, ',', '.')];
        } else {
            $_SESSION['alerta_rapida'] = ['tipo' => 'error', 'mensaje' => 'No se pudo abrir la caja o ya existe una sesión abierta'];
        }

        header('Location: /cocina/caja');
        exit;
    }

    /**
     * Cerrar turno de caja con arqueo de dinero
     */
    public static function cerrarCaja(): void {
        isStaff();

        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /cocina/caja');
            exit;
        }

        $sesionActiva = CajaSesion::sesionActiva(1);
        if(!$sesionActiva) {
            $_SESSION['alerta_rapida'] = ['tipo' => 'error', 'mensaje' => 'No hay ninguna sesión de caja abierta para cerrar'];
            header('Location: /cocina/caja');
            exit;
        }

        $montoCierre = (float)($_POST['monto_cierre'] ?? 0.0);
        $obs = trim((string)($_POST['observaciones'] ?? ''));

        if($sesionActiva->cerrar($montoCierre, $obs)) {
            $difTexto = ($sesionActiva->diferencia >= 0 ? '+$ ' : '-$ ') . number_format(abs($sesionActiva->diferencia), 2, ',', '.');
            $_SESSION['alerta_rapida'] = [
                'tipo' => 'exito', 
                'mensaje' => "Caja cerrada con éxito. Total contado: $ " . number_format($montoCierre, 2, ',', '.') . " (Diferencia: {$difTexto})"
            ];
        } else {
            $_SESSION['alerta_rapida'] = ['tipo' => 'error', 'mensaje' => 'Error al cerrar la sesión de caja'];
        }

        header('Location: /cocina/caja');
        exit;
    }

    /**
     * Crear un pedido presencial de mostrador desde el KDS
     */
    public static function crearPedidoPresencial(): void {
        isStaff();

        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /cocina');
            exit;
        }

        $comercioId = 1;
        $nombreContacto = trim((string)($_POST['nombre_contacto'] ?? ''));
        $accionCobro = (string)($_POST['accion_cobro'] ?? 'cobrar_ahora'); // 'cobrar_ahora' | 'cobrar_entrega'
        $itemsJson = $_POST['items_json'] ?? '[]';
        $items = json_decode($itemsJson, true);

        if(empty($nombreContacto)) {
            $_SESSION['alerta_rapida'] = ['tipo' => 'error', 'mensaje' => 'Debes ingresar el nombre del cliente para el pedido de mostrador'];
            header('Location: /cocina');
            exit;
        }

        if(empty($items) || !is_array($items)) {
            $_SESSION['alerta_rapida'] = ['tipo' => 'error', 'mensaje' => 'Debes agregar al menos un producto a la comanda'];
            header('Location: /cocina');
            exit;
        }

        // Recalcular precios y acumular consumo de insumos
        $subtotal = 0.00;
        $detallesAGuardar = [];
        $consumoInsumosTotal = [];

        foreach($items as $item) {
            $productoId = (int)($item['producto_id'] ?? 0);
            $cantidad = (int)($item['cantidad'] ?? 1);
            $opcionesIds = isset($item['opciones']) && is_array($item['opciones']) ? $item['opciones'] : [];
            $aderezos = isset($item['aderezos']) && is_array($item['aderezos']) ? $item['aderezos'] : [];
            $notas = trim((string)($item['notas'] ?? ''));

            if($cantidad <= 0) continue;

            $prod = Producto::find($productoId);
            if(!$prod || (int)$prod->comercio_id !== $comercioId || (int)$prod->activo !== 1) {
                $_SESSION['alerta_rapida'] = ['tipo' => 'error', 'mensaje' => 'Uno de los productos seleccionados ya no está disponible'];
                header('Location: /cocina');
                exit;
            }

            $precioUnitario = (float)$prod->precio;
            $textoOpciones = [];

            // Aderezos y salsas gratuitas
            if(!empty($aderezos)) {
                $salsasLimpias = [];
                foreach($aderezos as $ad) {
                    $nom = trim(strip_tags((string)$ad));
                    if(!empty($nom)) $salsasLimpias[] = $nom;
                }
                if(!empty($salsasLimpias)) {
                    $textoOpciones[] = "Salsas: " . implode(', ', $salsasLimpias);
                }
            }

            // Sumar precio adicional de variantes válidas
            if(!empty($opcionesIds)) {
                foreach($opcionesIds as $opcId) {
                    $opc = ProductoOpcion::find((int)$opcId);
                    if($opc && (int)$opc->producto_id === $productoId && (int)$opc->activo === 1) {
                        $precioUnitario += (float)$opc->precio_adicional;
                        $textoOpciones[] = $opc->nombre . ((float)$opc->precio_adicional > 0 ? ' (+$' . number_format($opc->precio_adicional, 2, ',', '.') . ')' : '');
                    }
                }
            }

            if(!empty($notas)) {
                $textoOpciones[] = "Notas: " . $notas;
            }

            $subtotalLinea = round($precioUnitario * $cantidad, 2);
            $subtotal += $subtotalLinea;

            $detallesAGuardar[] = [
                'producto_id' => $productoId,
                'nombre_producto' => $prod->nombre,
                'opciones_texto' => implode(' | ', $textoOpciones),
                'cantidad' => $cantidad,
                'precio_unitario' => $precioUnitario,
                'subtotal' => $subtotalLinea
            ];

            // Acumular consumo según receta
            $receta = ProductoInsumo::porProducto($productoId);
            foreach($receta as $ing) {
                $insId = (int)$ing->insumo_id;
                $cantConsumo = (float)$ing->cantidad_consumo * $cantidad;
                if(!isset($consumoInsumosTotal[$insId])) {
                    $consumoInsumosTotal[$insId] = [
                        'insumo_id' => $insId,
                        'nombre' => $ing->insumo_nombre,
                        'unidad' => $ing->unidad_medida,
                        'cantidad' => 0.0
                    ];
                }
                $consumoInsumosTotal[$insId]['cantidad'] += $cantConsumo;
            }
        }

        if(empty($detallesAGuardar)) {
            $_SESSION['alerta_rapida'] = ['tipo' => 'error', 'mensaje' => 'No hay productos válidos en el pedido'];
            header('Location: /cocina');
            exit;
        }

        $total = $subtotal;

        // Verificar stock de insumos antes de confirmar
        foreach($consumoInsumosTotal as $insId => $info) {
            $insumoObj = Insumo::find($insId);
            if($insumoObj && (float)$insumoObj->stock_actual < (float)$info['cantidad']) {
                $_SESSION['alerta_rapida'] = [
                    'tipo' => 'error', 
                    'mensaje' => "Stock insuficiente del insumo '{$info['nombre']}'. Disponible: {$insumoObj->stock_actual} {$info['unidad']}, Requerido: {$info['cantidad']} {$info['unidad']}"
                ];
                header('Location: /cocina');
                exit;
            }
        }

        // Crear pedido
        $pedido = new Pedido([
            'comercio_id' => $comercioId,
            'usuario_id' => null,
            'nombre_contacto' => $nombreContacto,
            'telefono_contacto' => 'Mostrador',
            'direccion_texto' => 'Local - Venta Presencial',
            'tipo' => 'retiro',
            'estado' => 'confirmado',
            'fecha_confirmado' => date('Y-m-d H:i:s'),
            'metodo_pago' => 'efectivo',
            'subtotal' => $subtotal,
            'costo_envio' => 0.00,
            'descuento' => 0.00,
            'total' => $total
        ]);

        $resPedido = $pedido->guardar();
        $pedidoId = is_array($resPedido) ? (int)($resPedido['id'] ?? 0) : (int)$pedido->id;

        if(!$pedidoId) {
            $_SESSION['alerta_rapida'] = ['tipo' => 'error', 'mensaje' => 'Error al registrar el pedido en el sistema'];
            header('Location: /cocina');
            exit;
        }

        // Guardar detalles
        foreach($detallesAGuardar as $det) {
            $det['pedido_id'] = $pedidoId;
            $detalleObj = new PedidoDetalle($det);
            $detalleObj->guardar();
        }

        // Descontar insumos de stock atómicamente mediante MovimientoStock
        $usuarioStaffId = isset($_SESSION['id']) ? (int)$_SESSION['id'] : null;
        foreach($consumoInsumosTotal as $insId => $info) {
            MovimientoStock::registrar(
                $comercioId,
                $insId,
                $usuarioStaffId,
                'venta',
                (float)$info['cantidad'],
                "Venta presencial mostrador Pedido #{$pedidoId} ({$nombreContacto})"
            );
        }

        // Gestionar cobro y caja
        $sesionActiva = CajaSesion::sesionActiva($comercioId);
        $cajaSesionId = $sesionActiva ? (int)$sesionActiva->id : null;

        if($accionCobro === 'cobrar_ahora') {
            // Pago aprobado en el acto
            $pago = new Pago([
                'comercio_id' => $comercioId,
                'pedido_id' => $pedidoId,
                'metodo' => 'efectivo',
                'estado' => 'aprobado',
                'monto' => $total,
                'rendido' => 1,
                'fecha_rendido' => date('Y-m-d H:i:s'),
                'caja_sesion_id' => $cajaSesionId,
                'mp_payment_id' => 'EFECTIVO-MOSTRADOR-' . date('YmdHis'),
                'fecha' => date('Y-m-d H:i:s')
            ]);
            $pago->guardar();

            // Emitir comprobante interno
            $comp = Comprobante::generar($comercioId, $pedidoId, $total);
            $codComp = $comp ? $comp->codigoFormateado() : '';

            $totalFmt = number_format($total, 2, ',', '.');
            $_SESSION['alerta_rapida'] = [
                'tipo' => 'exito',
                'mensaje' => "Pedido mostrador #{$pedidoId} para '{$nombreContacto}' cargado y cobrado en caja ($ {$totalFmt}). Comprobante: {$codComp}"
            ];
        } else {
            // Pago pendiente al entregar
            $pago = new Pago([
                'comercio_id' => $comercioId,
                'pedido_id' => $pedidoId,
                'metodo' => 'efectivo',
                'estado' => 'pendiente',
                'monto' => $total,
                'rendido' => 1,
                'caja_sesion_id' => null,
                'fecha' => date('Y-m-d H:i:s')
            ]);
            $pago->guardar();

            $totalFmt = number_format($total, 2, ',', '.');
            $_SESSION['alerta_rapida'] = [
                'tipo' => 'exito',
                'mensaje' => "Pedido mostrador #{$pedidoId} para '{$nombreContacto}' ingresado a cocina ($ {$totalFmt}). Cobro pendiente al entregar."
            ];
        }

        header('Location: /cocina');
        exit;
    }
}
