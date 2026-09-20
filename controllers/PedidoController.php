<?php

namespace Controllers;

use MVC\Router;
use Model\Comercio;
use Model\Categoria;
use Model\Producto;
use Model\ProductoOpcion;
use Model\Aderezo;
use Model\ProductoInsumo;
use Model\Insumo;
use Model\Pedido;
use Model\PedidoDetalle;
use Model\DireccionCliente;
use Model\MovimientoStock;

class PedidoController {

    /**
     * Vista pública del menú digital con catálogo categorizado
     */
    public static function menu(Router $router): void {
        $comercio = obtenerComercio(1);
        $categorias = Categoria::porComercio(1, true); // Solo activas
        $productos = Producto::porComercio(1, null, true); // Solo activos

        // Agrupar productos por categoría y verificar disponibilidad de stock por receta
        $catalogo = [];
        foreach($categorias as $cat) {
            $catalogo[$cat->id] = [
                'categoria' => $cat,
                'productos' => []
            ];
        }

        foreach($productos as $prod) {
            if(isset($catalogo[$prod->categoria_id])) {
                $opciones = $prod->opciones(true);
                $receta = ProductoInsumo::porProducto((int)$prod->id);

                // Comprobar si algún insumo de su receta está agotado
                $sinStock = false;
                foreach($receta as $ing) {
                    if((float)($ing->stock_actual ?? 0.0) < (float)$ing->cantidad_consumo) {
                        $sinStock = true;
                        break;
                    }
                }

                $catalogo[$prod->categoria_id]['productos'][] = [
                    'producto' => $prod,
                    'opciones' => $opciones,
                    'receta' => $receta,
                    'disponible' => !$sinStock
                ];
            }
        }

        $aderezos = Aderezo::porComercio(1, true); // Solo activos

        $router->render('paginas/menu', [
            'titulo' => 'Menú Digital & Pedidos Online',
            'comercio' => $comercio,
            'catalogo' => $catalogo,
            'aderezos' => $aderezos,
            'tipo' => 'publico'
        ]);
    }

    /**
     * Vista de checkout (proceso final de compra)
     */
    public static function checkout(Router $router): void {
        $comercio = obtenerComercio(1);
        $usuario = null;
        $direccionesGuardadas = [];

        if(isset($_SESSION['login']) && isset($_SESSION['id'])) {
            $usuarioId = (int)$_SESSION['id'];
            $usuario = \Model\Usuario::find($usuarioId);
            $direccionesGuardadas = DireccionCliente::porUsuario($usuarioId);
        }

        $router->render('pedidos/checkout', [
            'titulo' => 'Confirmar Pedido',
            'comercio' => $comercio,
            'usuario' => $usuario,
            'direcciones' => $direccionesGuardadas,
            'tieneMapa' => true,
            'tipo' => 'publico'
        ]);
    }

    /**
     * Endpoint API JSON para calcular la distancia Haversine y costo de envío en tiempo real
     */
    public static function calcularEnvio(): void {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $latCliente = isset($input['lat']) ? (float)$input['lat'] : null;
        $lngCliente = isset($input['lng']) ? (float)$input['lng'] : null;

        if(!$latCliente || !$lngCliente) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Coordenadas del cliente incompletas'
            ]);
            exit;
        }

        $comercio = obtenerComercio(1);
        if(!$comercio || empty($comercio->lat) || empty($comercio->lng)) {
            echo json_encode([
                'ok' => false,
                'mensaje' => 'El local aún no tiene coordenadas configuradas'
            ]);
            exit;
        }

        $distanciaKm = haversineKm((float)$comercio->lat, (float)$comercio->lng, $latCliente, $lngCliente);
        $kmGratis = (float)$comercio->km_envio_gratis;
        $costoPorKm = (float)$comercio->costo_envio_por_km;

        $costoEnvio = 0.00;
        if($distanciaKm > $kmGratis) {
            $kmExcedente = $distanciaKm - $kmGratis;
            $costoEnvio = round($kmExcedente * $costoPorKm, 2);
        }

        echo json_encode([
            'ok' => true,
            'distancia_km' => $distanciaKm,
            'distancia_formateada' => number_format($distanciaKm, 1, ',', '.') . ' km',
            'costo_envio' => $costoEnvio,
            'costo_formateado' => '$ ' . number_format($costoEnvio, 2, ',', '.'),
            'envio_gratis' => ($costoEnvio === 0.00),
            'km_gratis' => $kmGratis
        ]);
        exit;
    }

    /**
     * Procesa la orden de compra con validación server-side y descuento atómico de stock
     */
    public static function crear(): void {
        header('Content-Type: application/json');

        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $comercio = obtenerComercio(1);

        $nombreContacto = trim((string)($input['nombre_contacto'] ?? ''));
        $telefonoContacto = trim((string)($input['telefono_contacto'] ?? ''));
        $tipoEntrega = (string)($input['tipo'] ?? 'delivery');
        $metodoPago = (string)($input['metodo_pago'] ?? 'efectivo');
        $direccionTexto = trim((string)($input['direccion_texto'] ?? ''));
        $lat = isset($input['lat']) && !empty($input['lat']) ? (float)$input['lat'] : null;
        $lng = isset($input['lng']) && !empty($input['lng']) ? (float)$input['lng'] : null;
        $items = $input['items'] ?? [];

        // 1. Validar campos básicos
        if(empty($nombreContacto) || empty($telefonoContacto)) {
            echo json_encode(['ok' => false, 'mensaje' => 'Por favor ingresa tu nombre y teléfono de contacto']);
            exit;
        }

        if(empty($items) || !is_array($items)) {
            echo json_encode(['ok' => false, 'mensaje' => 'El carrito de compras está vacío']);
            exit;
        }

        // 2. Calcular costo de envío server-side
        $distanciaKm = 0.00;
        $costoEnvio = 0.00;

        if($tipoEntrega === 'delivery') {
            if(empty($direccionTexto) || !$lat || !$lng) {
                echo json_encode(['ok' => false, 'mensaje' => 'Para delivery a domicilio debes ingresar tu dirección y seleccionar el punto en el mapa']);
                exit;
            }

            $distanciaKm = haversineKm((float)$comercio->lat, (float)$comercio->lng, $lat, $lng);
            $kmGratis = (float)$comercio->km_envio_gratis;
            $costoPorKm = (float)$comercio->costo_envio_por_km;

            if($distanciaKm > $kmGratis) {
                $costoEnvio = round(($distanciaKm - $kmGratis) * $costoPorKm, 2);
            }
        }

        // 3. Recalcular precios de productos server-side y acumular consumo de insumos
        $subtotal = 0.00;
        $detallesAGuardar = [];
        $consumoInsumosTotal = []; // [insumo_id => ['nombre' => ..., 'unidad' => ..., 'cantidad' => ...]]

        foreach($items as $item) {
            $productoId = (int)($item['producto_id'] ?? 0);
            $cantidad = (int)($item['cantidad'] ?? 1);
            $opcionesIds = $item['opciones'] ?? [];
            $aderezos = isset($item['aderezos']) && is_array($item['aderezos']) ? $item['aderezos'] : [];
            $notas = trim((string)($item['notas'] ?? ''));

            if($cantidad <= 0) continue;

            $prod = Producto::find($productoId);
            if(!$prod || (int)$prod->comercio_id !== 1 || (int)$prod->activo !== 1) {
                echo json_encode(['ok' => false, 'mensaje' => 'Uno de los productos seleccionados no se encuentra disponible']);
                exit;
            }

            $precioUnitario = (float)$prod->precio;
            $textoOpciones = [];

            // Aderezos o salsas gratuitas seleccionadas
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
            if(!empty($opcionesIds) && is_array($opcionesIds)) {
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

        // 4. Validar disponibilidad estricta de stock para cada insumo
        foreach($consumoInsumosTotal as $insId => $datosConsumo) {
            $insumo = Insumo::find($insId);
            if(!$insumo || (float)$insumo->stock_actual < $datosConsumo['cantidad']) {
                $faltante = $datosConsumo['cantidad'] - (float)($insumo->stock_actual ?? 0.0);
                echo json_encode([
                    'ok' => false,
                    'mensaje' => "Stock insuficiente en cocina para '{$datosConsumo['nombre']}'. Falta " . formatearCantidad($faltante, $datosConsumo['unidad']) . " " . $datosConsumo['unidad'] . " para completar el pedido."
                ]);
                exit;
            }
        }

        $total = $subtotal + $costoEnvio;

        // 5. Persistencia transaccional
        Pedido::getDB()->begin_transaction();

        try {
            $usuarioId = isset($_SESSION['id']) && ($_SESSION['rol'] ?? '') === 'cliente' ? (int)$_SESSION['id'] : null;

            $pedido = new Pedido([
                'comercio_id' => 1,
                'usuario_id' => $usuarioId,
                'nombre_contacto' => $nombreContacto,
                'telefono_contacto' => $telefonoContacto,
                'direccion_texto' => ($tipoEntrega === 'delivery') ? $direccionTexto : 'Retiro en el local (' . $comercio->direccion . ')',
                'lat' => $lat,
                'lng' => $lng,
                'distancia_km' => $distanciaKm,
                'costo_envio' => $costoEnvio,
                'tipo' => $tipoEntrega,
                'estado' => 'pendiente',
                'metodo_pago' => $metodoPago,
                'subtotal' => $subtotal,
                'total' => $total
            ]);

            $resPedido = $pedido->crear();
            if(!$resPedido['resultado']) {
                throw new \Exception('Error al crear la cabecera del pedido');
            }

            $pedidoId = (int)$resPedido['id'];

            // Insertar líneas de detalle
            foreach($detallesAGuardar as $d) {
                $detalle = new PedidoDetalle(array_merge($d, ['pedido_id' => $pedidoId]));
                $detalle->crear();
            }

            // Descontar materia prima mediante MovimientoStock (tipo 'venta')
            foreach($consumoInsumosTotal as $insId => $datosConsumo) {
                MovimientoStock::registrar(
                    1,
                    $insId,
                    $usuarioId,
                    'venta',
                    $datosConsumo['cantidad'],
                    "Venta en Pedido #{$pedidoId}"
                );
            }

            // Si es un cliente registrado que pidió delivery, guardar dirección si no existe
            if($usuarioId && $tipoEntrega === 'delivery' && !empty($direccionTexto)) {
                $dirExistente = DireccionCliente::whereAll('usuario_id', (string)$usuarioId);
                $yaExiste = false;
                foreach($dirExistente as $de) {
                    if(mb_strtolower($de->direccion) === mb_strtolower($direccionTexto)) {
                        $yaExiste = true;
                        break;
                    }
                }
                if(!$yaExiste) {
                    $nuevaDir = new DireccionCliente([
                        'usuario_id' => $usuarioId,
                        'etiqueta' => 'Casa',
                        'direccion' => $direccionTexto,
                        'lat' => $lat,
                        'lng' => $lng
                    ]);
                    $nuevaDir->crear();
                }
            }

            // Crear registro en tabla pagos
            $esDeliveryEfectivo = ($tipoEntrega === 'delivery' && $metodoPago === 'efectivo');

            $pago = new \Model\Pago([
                'pedido_id' => $pedidoId,
                'metodo' => $metodoPago,
                'estado' => 'pendiente',
                'monto' => $total,
                'rendido' => $esDeliveryEfectivo ? 0 : 1
            ]);

            $urlPago = null;
            $prefId = null;

            if($metodoPago === 'mercadopago') {
                $detallesObjetos = [];
                foreach($detallesAGuardar as $d) {
                    $detallesObjetos[] = (object)$d;
                }
                $preferenciaMP = \Classes\MercadoPagoService::crearPreferencia($pedido, $detallesObjetos);
                if($preferenciaMP) {
                    $prefId = $preferenciaMP['id'];
                    $urlPago = $preferenciaMP['init_point'] ?? null;
                    $pago->mp_preference_id = $prefId;
                }
            }

            $pago->crear();

            Pedido::getDB()->commit();

            // Notificación por Email: Enviar resumen y seguimiento al cliente
            \Classes\Email::enviarConfirmacionPedido($pedido, $detallesAGuardar);

            // Alerta de Stock: Notificar al dueño si algún insumo llegó a nivel crítico
            foreach($consumoInsumosTotal as $insId => $datosConsumo) {
                $insumoActualizado = Insumo::find($insId);
                if($insumoActualizado && (float)$insumoActualizado->stock_actual <= (float)$insumoActualizado->stock_minimo) {
                    $dueno = \Model\Usuario::where('rol', 'dueño');
                    if($dueno && !empty($dueno->email)) {
                        \Classes\Email::enviarAlertaStockCritico($insumoActualizado, $dueno->email);
                    }
                }
            }

            echo json_encode([
                'ok' => true,
                'pedido_id' => $pedidoId,
                'preference_id' => $prefId,
                'payment_url' => $urlPago,
                'redirect' => "/pedido/seguimiento?id={$pedidoId}"
            ]);
            exit;
        } catch (\Throwable $e) {
            Pedido::getDB()->rollback();
            echo json_encode([
                'ok' => false,
                'mensaje' => 'Ocurrió un error al procesar el pedido: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    /**
     * Vista de seguimiento en tiempo real del pedido para el cliente
     */
    public static function seguimiento(Router $router): void {
        $id = (int)($_GET['id'] ?? 0);
        $pedido = Pedido::find($id);

        if(!$pedido || (int)$pedido->comercio_id !== 1) {
            header('Location: /');
            exit;
        }

        $comercio = obtenerComercio(1);
        $detalles = $pedido->detalles();
        $pago = $pedido->pago();
        $comprobante = $pedido->comprobante();
        $minutosCancelacion = (int)($comercio->minutos_cancelacion ?? 5);
        $puedeCancelar = $pedido->puedeCancelar($minutosCancelacion);
        $segundosRestantes = $pedido->segundosRestantesCancelar($minutosCancelacion);

        $resultado = $_GET['resultado'] ?? null;
        if($resultado === 'cancelado') {
            Pedido::setAlerta('exito', 'El pedido ha sido cancelado y los ingredientes restituidos al inventario');
        } elseif($resultado === 'error_cancelar') {
            Pedido::setAlerta('error', 'El tiempo de cancelación ha expirado o el pedido ya se encuentra en preparación');
        } elseif($resultado === 'pago_aprobado') {
            Pedido::setAlerta('exito', '¡Pago aprobado con éxito! Tu pedido se encuentra confirmado');
        } elseif($resultado === 'pago_fallido') {
            Pedido::setAlerta('error', 'El pago no pudo ser completado o fue rechazado. Puedes reintentar o pagar en efectivo');
        } elseif($resultado === 'pago_pendiente') {
            Pedido::setAlerta('alerta', 'El pago se encuentra pendiente de acreditación');
        }

        // Generar o refrescar URL de Checkout Pro si corresponde
        $preferenciaUrl = null;
        if($pago && $pago->metodo === 'mercadopago' && $pago->estado === 'pendiente') {
            $preferenciaMP = \Classes\MercadoPagoService::crearPreferencia($pedido, $detalles);
            if($preferenciaMP) {
                $preferenciaUrl = $preferenciaMP['init_point'] ?? null;
                if(empty($pago->mp_preference_id)) {
                    $pago->mp_preference_id = $preferenciaMP['id'];
                    $pago->actualizar();
                }
            }
        }

        $alertas = Pedido::getAlertas();

        $router->render('pedidos/seguimiento', [
            'titulo' => "Seguimiento de Pedido #{$pedido->id}",
            'pedido' => $pedido,
            'detalles' => $detalles,
            'pago' => $pago,
            'comprobante' => $comprobante,
            'preferenciaUrl' => $preferenciaUrl,
            'comercio' => $comercio,
            'puedeCancelar' => $puedeCancelar,
            'segundosRestantes' => $segundosRestantes,
            'minutosCancelacion' => $minutosCancelacion,
            'alertas' => $alertas,
            'tieneMapa' => ($pedido->tipo === 'delivery' && !empty($pedido->lat)),
            'tipo' => 'publico'
        ]);
    }

    /**
     * Endpoint POST para cancelar el pedido por el cliente dentro de la ventana configurada
     */
    public static function cancelar(): void {
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['pedido_id'] ?? 0);
            $pedido = Pedido::find($id);
            $comercio = obtenerComercio(1);

            if($pedido && (int)$pedido->comercio_id === 1) {
                $minutos = (int)($comercio->minutos_cancelacion ?? 5);

                if($pedido->puedeCancelar($minutos)) {
                    Pedido::getDB()->begin_transaction();
                    try {
                        $pedido->estado = 'cancelado';
                        $pedido->actualizar();
                        $pedido->revertirStock();
                        Pedido::getDB()->commit();

                        header("Location: /pedido/seguimiento?id={$pedido->id}&resultado=cancelado");
                        exit;
                    } catch (\Throwable $e) {
                        Pedido::getDB()->rollback();
                    }
                }
            }
            header("Location: /pedido/seguimiento?id={$id}&resultado=error_cancelar");
            exit;
        }

        header('Location: /');
        exit;
    }

    /**
     * Endpoint API JSON público para consultar el estado de un pedido (usado por el banner de invitados)
     */
    public static function estado(): void {
        header('Content-Type: application/json');
        $id = (int)($_GET['id'] ?? 0);
        $pedido = Pedido::find($id);

        if(!$pedido || (int)$pedido->comercio_id !== 1) {
            echo json_encode(['ok' => false, 'mensaje' => 'Pedido no encontrado']);
            exit;
        }

        $activo = !in_array($pedido->estado, ['entregado', 'cancelado']);

        echo json_encode([
            'ok' => true,
            'id' => (int)$pedido->id,
            'estado' => $pedido->estado,
            'estado_texto' => $pedido->estadoTexto(),
            'activo' => $activo
        ]);
        exit;
    }
}
