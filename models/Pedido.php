<?php

namespace Model;

class Pedido extends ActiveRecord {
    protected static string $tabla = 'pedidos';
    protected static array $columnasDB = [
        'id', 'comercio_id', 'usuario_id', 'nombre_contacto', 'telefono_contacto',
        'direccion_texto', 'lat', 'lng', 'distancia_km', 'costo_envio', 'tipo',
        'estado', 'fecha_confirmado', 'metodo_pago', 'subtotal', 'total'
    ];

    public int $comercio_id;
    public ?int $usuario_id = null;
    public string $nombre_contacto;
    public string $telefono_contacto;
    public ?string $direccion_texto = null;
    public ?float $lat = null;
    public ?float $lng = null;
    public float $distancia_km;
    public float $costo_envio;
    public string $tipo;
    public string $estado;
    public ?string $fecha_confirmado = null;
    public string $metodo_pago;
    public float $subtotal;
    public float $total;
    public ?string $creado_en = null;
    public ?string $actualizado_en = null;
    public ?array $detalles = null;

    public const ESTADOS_VALIDOS = [
        'pendiente' => 'Pendiente de Confirmación',
        'confirmado' => 'Confirmado por el Local',
        'en_preparacion' => 'En Preparación (Cocina)',
        'en_camino' => 'En Camino / Listo para Retirar',
        'entregado' => 'Entregado',
        'cancelado' => 'Cancelado'
    ];

    public const METODOS_PAGO = [
        'efectivo' => 'Efectivo al recibir / en mostrador',
        'mercadopago' => 'Mercado Pago (Online)'
    ];

    public function __construct(array $args = []) {
        $this->id = $args['id'] ?? null;
        $this->comercio_id = isset($args['comercio_id']) ? (int)$args['comercio_id'] : 1;
        $this->usuario_id = isset($args['usuario_id']) && !empty($args['usuario_id']) ? (int)$args['usuario_id'] : null;
        $this->nombre_contacto = $args['nombre_contacto'] ?? '';
        $this->telefono_contacto = $args['telefono_contacto'] ?? '';
        $this->direccion_texto = $args['direccion_texto'] ?? null;
        $this->lat = isset($args['lat']) && !empty($args['lat']) ? (float)$args['lat'] : null;
        $this->lng = isset($args['lng']) && !empty($args['lng']) ? (float)$args['lng'] : null;
        $this->distancia_km = isset($args['distancia_km']) ? (float)$args['distancia_km'] : 0.0;
        $this->costo_envio = isset($args['costo_envio']) ? (float)$args['costo_envio'] : 0.0;
        $this->tipo = $args['tipo'] ?? 'delivery';
        $this->estado = $args['estado'] ?? 'pendiente';
        $this->fecha_confirmado = $args['fecha_confirmado'] ?? null;
        $this->metodo_pago = $args['metodo_pago'] ?? 'efectivo';
        $this->subtotal = isset($args['subtotal']) ? (float)$args['subtotal'] : 0.0;
        $this->total = isset($args['total']) ? (float)$args['total'] : 0.0;
        $this->creado_en = $args['creado_en'] ?? null;
        $this->actualizado_en = $args['actualizado_en'] ?? null;
    }

    public function validar(): array {
        self::$alertas = [];

        if(empty(trim($this->nombre_contacto))) {
            self::$alertas['error'][] = 'El nombre de contacto es obligatorio';
        }

        if(empty(trim($this->telefono_contacto))) {
            self::$alertas['error'][] = 'El teléfono de contacto es obligatorio para coordinar la entrega';
        }

        if($this->tipo === 'delivery') {
            if(empty(trim($this->direccion_texto ?? ''))) {
                self::$alertas['error'][] = 'Debes ingresar tu dirección de entrega (calle, número y piso/depto)';
            }
            if(empty($this->lat) || empty($this->lng)) {
                self::$alertas['error'][] = 'Debes ubicar tu posición en el mapa para calcular el envío';
            }
        }

        if(!in_array($this->tipo, ['delivery', 'retiro'])) {
            self::$alertas['error'][] = 'El tipo de entrega no es válido';
        }

        if($this->total <= 0) {
            self::$alertas['error'][] = 'El importe total del pedido debe ser mayor a cero';
        }

        return self::$alertas;
    }

    /**
     * Obtiene las líneas de detalle del pedido
     */
    public function detalles(): array {
        return PedidoDetalle::porPedido((int)$this->id);
    }

    /**
     * Obtiene el registro de pago asociado al pedido
     */
    public function pago(): ?Pago {
        return Pago::porPedido((int)$this->id);
    }

    /**
     * Obtiene el comprobante emitido para este pedido si existe
     */
    public function comprobante(): ?Comprobante {
        return Comprobante::porPedido((int)$this->id);
    }

    public function estadoTexto(): string {
        return self::ESTADOS_VALIDOS[$this->estado] ?? ucfirst($this->estado);
    }

    public function estadoBadgeClase(): string {
        return match($this->estado) {
            'pendiente' => 'badge--alerta',
            'confirmado' => 'badge--info',
            'en_preparacion' => 'badge--destacado',
            'en_camino' => 'badge--ajuste',
            'entregado' => 'badge--exito',
            'cancelado' => 'badge--error',
            default => 'badge--info'
        };
    }

    /**
     * Retorna el número de paso activo para la barra de progreso (1 a 5, o 0 si cancelado)
     */
    public function stepperPaso(): int {
        return match($this->estado) {
            'pendiente' => 1,
            'confirmado' => 2,
            'en_preparacion' => 3,
            'en_camino' => 4,
            'entregado' => 5,
            default => 0
        };
    }

    /**
     * Evalúa si el cliente puede cancelar el pedido según la regla de negocio
     */
    public function puedeCancelar(int $minutosTolerancia = 5): bool {
        // No se puede cancelar si ya está en preparación, en camino, entregado o ya cancelado
        if(in_array($this->estado, ['en_preparacion', 'en_camino', 'entregado', 'cancelado'])) {
            return false;
        }

        // Si está pendiente, siempre puede cancelar
        if($this->estado === 'pendiente') {
            return true;
        }

        // Si está confirmado, validar el tiempo transcurrido desde fecha_confirmado (o creado_en)
        $timestampReferencia = !empty($this->fecha_confirmado) 
            ? strtotime($this->fecha_confirmado) 
            : strtotime($this->creado_en ?? 'now');

        $segundosTranscurridos = time() - $timestampReferencia;
        return ($segundosTranscurridos <= ($minutosTolerancia * 60));
    }

    /**
     * Retorna los segundos restantes disponibles para cancelar
     */
    public function segundosRestantesCancelar(int $minutosTolerancia = 5): int {
        if(!$this->puedeCancelar($minutosTolerancia)) return 0;

        $timestampReferencia = !empty($this->fecha_confirmado) 
            ? strtotime($this->fecha_confirmado) 
            : strtotime($this->creado_en ?? 'now');

        $limite = $timestampReferencia + ($minutosTolerancia * 60);
        return max(0, $limite - time());
    }

    /**
     * Restituye el stock de todos los insumos consumidos por este pedido
     */
    public function revertirStock(): bool {
        $detalles = $this->detalles();
        $ok = true;

        foreach($detalles as $detalle) {
            $receta = ProductoInsumo::porProducto((int)$detalle->producto_id);
            foreach($receta as $ingrediente) {
                $cantidadARestituir = $ingrediente->cantidad_consumo * $detalle->cantidad;
                $res = MovimientoStock::registrar(
                    $this->comercio_id,
                    (int)$ingrediente->insumo_id,
                    $this->usuario_id,
                    'cancelacion',
                    $cantidadARestituir,
                    "Restitución por cancelación de Pedido #{$this->id}"
                );
                if(!$res) $ok = false;
            }
        }
        return $ok;
    }

    /**
     * Busca los pedidos de un usuario cliente
     */
    public static function porUsuario(int $usuarioId): array {
        $query = "SELECT * FROM " . static::$tabla . " WHERE usuario_id = {$usuarioId} ORDER BY id DESC";
        return static::consultarSQL($query);
    }

    /**
     * Obtiene la asignación de repartidor si existe
     */
    public function asignacionRepartidor(): ?PedidoRepartidor {
        return PedidoRepartidor::porPedido((int)$this->id);
    }

    /**
     * Obtiene el usuario repartidor asignado
     */
    public function repartidor(): ?Usuario {
        $asig = $this->asignacionRepartidor();
        return $asig ? $asig->repartidor() : null;
    }

    /**
     * Obtiene los pedidos asignados a un repartidor
     */
    public static function asignadosARepartidor(int $repartidorId, bool $soloEnCamino = true): array {
        $query = "SELECT p.* FROM " . static::$tabla . " p 
                  INNER JOIN pedido_repartidor pr ON p.id = pr.pedido_id 
                  WHERE pr.repartidor_id = {$repartidorId}";
        
        if($soloEnCamino) {
            $query .= " AND p.estado = 'en_camino' ORDER BY pr.hora_asignado ASC";
        } else {
            $query .= " AND p.estado = 'entregado' AND DATE(pr.hora_entregado) = CURDATE() ORDER BY pr.hora_entregado DESC";
        }

        return static::consultarSQL($query);
    }

    /**
     * Pasa el pedido de confirmado a en_preparacion (ingreso a cocina)
     */
    public function enviarACocina(): bool {
        if(!in_array($this->estado, ['pendiente', 'confirmado'])) {
            return false;
        }
        $this->estado = 'en_preparacion';
        $res = $this->guardar();
        return is_array($res) ? (bool)($res['resultado'] ?? false) : (bool)$res;
    }

    /**
     * Marca un pedido de retiro como listo en mostrador
     */
    public function despacharRetiro(): bool {
        $this->estado = 'en_camino'; // Para retiro se muestra como 'Listo para Retirar'
        $res = $this->guardar();
        return is_array($res) ? (bool)($res['resultado'] ?? false) : (bool)$res;
    }

    /**
     * Asigna repartidor y despacha delivery
     */
    public function despacharDelivery(int $repartidorId): bool {
        $asig = PedidoRepartidor::asignar((int)$this->id, $repartidorId);
        if(!$asig) {
            return false;
        }
        $this->estado = 'en_camino';
        $res = $this->guardar();
        return is_array($res) ? (bool)($res['resultado'] ?? false) : (bool)$res;
    }

    /**
     * Marca el pedido como entregado
     */
    public function marcarEntregado(): bool {
        $this->estado = 'entregado';
        $asig = $this->asignacionRepartidor();
        if($asig) {
            $asig->registrarEntrega();
        }

        // Si es delivery en efectivo y el repartidor lo entrega, el cobro queda en mano del repartidor
        $pago = $this->pago();
        if($pago && $pago->metodo === 'efectivo' && $pago->estado === 'pendiente') {
            $pago->estado = 'aprobado';
            $pago->mp_payment_id = 'EFECTIVO-DELIVERY-' . date('YmdHis');
            $pago->rendido = 0; // Sigue en custodia del repartidor hasta rendición en caja
            $pago->actualizar();
            // Emitir comprobante al entregar
            $comprobanteExistente = Comprobante::porPedido((int)$this->id);
            if(!$comprobanteExistente) {
                Comprobante::generar((int)$this->comercio_id, (int)$this->id, (float)$this->total);
            }
        }

        $res = $this->guardar();
        return is_array($res) ? (bool)($res['resultado'] ?? false) : (bool)$res;
    }

    /**
     * Cobra en mostrador y entrega el pedido de retiro en un solo paso
     */
    public function cobrarYEntregarRetiro(?int $cajaSesionId = null): bool {
        self::$db->begin_transaction();
        try {
            // 1. Cobrar pago en efectivo de mostrador
            $pago = $this->pago();
            if($pago) {
                $pago->estado = 'aprobado';
                $pago->mp_payment_id = 'EFECTIVO-MOSTRADOR-' . date('YmdHis');
                $pago->rendido = 1; // Entra directo al cajón de caja
                $pago->fecha_rendido = date('Y-m-d H:i:s');
                if($cajaSesionId) {
                    $pago->caja_sesion_id = $cajaSesionId;
                }
                $pago->actualizar();
            }

            // 2. Emitir comprobante interno
            $comprobanteExistente = Comprobante::porPedido((int)$this->id);
            if(!$comprobanteExistente) {
                Comprobante::generar((int)$this->comercio_id, (int)$this->id, (float)$this->total);
            }

            // 3. Pasar pedido a entregado
            $this->estado = 'entregado';
            $res = $this->guardar();

            self::$db->commit();
            return is_array($res) ? (bool)($res['resultado'] ?? false) : (bool)$res;
        } catch (\Throwable $e) {
            self::$db->rollback();
            return false;
        }
    }

    /**
     * Cancelación realizada por el personal del comercio
     */
    public function cancelarPorStaff(string $motivo = 'Cancelado por el comercio'): bool {
        if($this->estado === 'entregado' || $this->estado === 'cancelado') {
            return false;
        }

        $this->estado = 'cancelado';
        $res = $this->guardar();
        $ok = is_array($res) ? (bool)($res['resultado'] ?? false) : (bool)$res;
        if($ok) {
            $this->revertirStock();
            return true;
        }
        return false;
    }

    /**
     * Minutos transcurridos desde que se creó o confirmó el pedido
     */
    public function minutosTranscurridos(): int {
        $timestamp = !empty($this->fecha_confirmado) 
            ? strtotime($this->fecha_confirmado) 
            : strtotime($this->creado_en ?? 'now');
        return max(0, (int)floor((time() - $timestamp) / 60));
    }

    /**
     * Clase CSS de urgencia para el temporizador de la comanda
     */
    public function alertaTiempoClase(): string {
        $mins = $this->minutosTranscurridos();
        if($mins >= 30) return 'comanda-timer--critico';
        if($mins >= 15) return 'comanda-timer--alerta';
        return 'comanda-timer--normal';
    }

    /**
     * Métricas generales de ventas para un rango de fechas
     */
    public static function metricasVentas(string $inicio, string $fin, int $comercioId = 1): array {
        $inicioEsc = self::$db->escape_string($inicio);
        $finEsc = self::$db->escape_string($fin);

        // Ventas confirmadas/entregadas
        $sqlVentas = "SELECT 
                        COUNT(id) as total_pedidos,
                        COALESCE(SUM(total), 0) as total_facturado,
                        COALESCE(SUM(costo_envio), 0) as total_envios,
                        COALESCE(AVG(total), 0) as ticket_promedio
                      FROM " . static::$tabla . "
                      WHERE comercio_id = {$comercioId}
                        AND estado IN ('confirmado', 'en_preparacion', 'en_camino', 'entregado')
                        AND (
                            (fecha_confirmado IS NOT NULL AND fecha_confirmado >= '{$inicioEsc}' AND fecha_confirmado <= '{$finEsc}')
                            OR (fecha_confirmado IS NULL AND creado_en >= '{$inicioEsc}' AND creado_en <= '{$finEsc}')
                        )";
        $resVentas = self::$db->query($sqlVentas);
        $ventas = $resVentas ? $resVentas->fetch_assoc() : [];

        // Conteo de pedidos cancelados
        $sqlCancelados = "SELECT COUNT(id) as total_cancelados
                          FROM " . static::$tabla . "
                          WHERE comercio_id = {$comercioId}
                            AND estado = 'cancelado'
                            AND (
                                (fecha_confirmado IS NOT NULL AND fecha_confirmado >= '{$inicioEsc}' AND fecha_confirmado <= '{$finEsc}')
                                OR (fecha_confirmado IS NULL AND creado_en >= '{$inicioEsc}' AND creado_en <= '{$finEsc}')
                            )";
        $resCancelados = self::$db->query($sqlCancelados);
        $cancelados = $resCancelados ? $resCancelados->fetch_assoc() : [];

        $totalPedidos = (int)($ventas['total_pedidos'] ?? 0);
        $totalCancelados = (int)($cancelados['total_cancelados'] ?? 0);
        $pedidosTotales = $totalPedidos + $totalCancelados;
        $tasaEfectividad = ($pedidosTotales > 0) ? round(($totalPedidos / $pedidosTotales) * 100, 1) : 100.0;

        return [
            'total_facturado' => (float)($ventas['total_facturado'] ?? 0.0),
            'total_pedidos' => $totalPedidos,
            'total_envios' => (float)($ventas['total_envios'] ?? 0.0),
            'ticket_promedio' => (float)($ventas['ticket_promedio'] ?? 0.0),
            'total_cancelados' => $totalCancelados,
            'tasa_efectividad' => $tasaEfectividad
        ];
    }

    /**
     * Ventas agrupadas por día para gráficos y evolución temporal
     */
    public static function ventasPorDia(string $inicio, string $fin, int $comercioId = 1): array {
        $inicioEsc = self::$db->escape_string($inicio);
        $finEsc = self::$db->escape_string($fin);

        $sql = "SELECT 
                    DATE(COALESCE(fecha_confirmado, creado_en)) as fecha,
                    COUNT(id) as pedidos,
                    COALESCE(SUM(total), 0) as total
                FROM " . static::$tabla . "
                WHERE comercio_id = {$comercioId}
                  AND estado IN ('confirmado', 'en_preparacion', 'en_camino', 'entregado')
                  AND (
                      (fecha_confirmado IS NOT NULL AND fecha_confirmado >= '{$inicioEsc}' AND fecha_confirmado <= '{$finEsc}')
                      OR (fecha_confirmado IS NULL AND creado_en >= '{$inicioEsc}' AND creado_en <= '{$finEsc}')
                  )
                GROUP BY DATE(COALESCE(fecha_confirmado, creado_en))
                ORDER BY fecha ASC";

        $resultado = self::$db->query($sql);
        $datos = [];
        if($resultado) {
            while($fila = $resultado->fetch_assoc()) {
                $datos[] = [
                    'fecha' => $fila['fecha'],
                    'pedidos' => (int)$fila['pedidos'],
                    'total' => (float)$fila['total']
                ];
            }
        }
        return $datos;
    }

    /**
     * Desglose de ventas según método de pago (Efectivo vs. Mercado Pago)
     */
    public static function desgloseMetodosPago(string $inicio, string $fin, int $comercioId = 1): array {
        $inicioEsc = self::$db->escape_string($inicio);
        $finEsc = self::$db->escape_string($fin);

        $sql = "SELECT 
                    metodo_pago,
                    COUNT(id) as pedidos,
                    COALESCE(SUM(total), 0) as total
                FROM " . static::$tabla . "
                WHERE comercio_id = {$comercioId}
                  AND estado IN ('confirmado', 'en_preparacion', 'en_camino', 'entregado')
                  AND (
                      (fecha_confirmado IS NOT NULL AND fecha_confirmado >= '{$inicioEsc}' AND fecha_confirmado <= '{$finEsc}')
                      OR (fecha_confirmado IS NULL AND creado_en >= '{$inicioEsc}' AND creado_en <= '{$finEsc}')
                  )
                GROUP BY metodo_pago";

        $resultado = self::$db->query($sql);
        $desglose = [];
        if($resultado) {
            while($fila = $resultado->fetch_assoc()) {
                $desglose[$fila['metodo_pago']] = [
                    'pedidos' => (int)$fila['pedidos'],
                    'total' => (float)$fila['total']
                ];
            }
        }
        return $desglose;
    }

    /**
     * Desglose de ventas según modalidad de entrega (Delivery vs. Retiro en mostrador)
     */
    public static function desgloseTiposEntrega(string $inicio, string $fin, int $comercioId = 1): array {
        $inicioEsc = self::$db->escape_string($inicio);
        $finEsc = self::$db->escape_string($fin);

        $sql = "SELECT 
                    tipo,
                    COUNT(id) as pedidos,
                    COALESCE(SUM(total), 0) as total
                FROM " . static::$tabla . "
                WHERE comercio_id = {$comercioId}
                  AND estado IN ('confirmado', 'en_preparacion', 'en_camino', 'entregado')
                  AND (
                      (fecha_confirmado IS NOT NULL AND fecha_confirmado >= '{$inicioEsc}' AND fecha_confirmado <= '{$finEsc}')
                      OR (fecha_confirmado IS NULL AND creado_en >= '{$inicioEsc}' AND creado_en <= '{$finEsc}')
                  )
                GROUP BY tipo";

        $resultado = self::$db->query($sql);
        $desglose = [];
        if($resultado) {
            while($fila = $resultado->fetch_assoc()) {
                $desglose[$fila['tipo']] = [
                    'pedidos' => (int)$fila['pedidos'],
                    'total' => (float)$fila['total']
                ];
            }
        }
        return $desglose;
    }
}
