<?php

namespace Model;

class Pago extends ActiveRecord {
    protected static string $tabla = 'pagos';
    protected static array $columnasDB = [
        'id', 'pedido_id', 'metodo', 'estado', 'mp_preference_id',
        'mp_payment_id', 'mp_status', 'monto', 'rendido', 'fecha_rendido', 'caja_sesion_id', 'fecha'
    ];

    public int $pedido_id;
    public string $metodo; // 'mercadopago' | 'efectivo'
    public string $estado; // 'pendiente' | 'aprobado' | 'rechazado'
    public ?string $mp_preference_id = null;
    public ?string $mp_payment_id = null;
    public ?string $mp_status = null;
    public float $monto;
    public int $rendido = 1; // 1 = en caja / rendido; 0 = en calle por delivery
    public ?string $fecha_rendido = null;
    public ?int $caja_sesion_id = null;
    public ?string $fecha = null;

    public const METODOS_VALIDOS = ['mercadopago', 'efectivo'];
    public const ESTADOS_VALIDOS = ['pendiente', 'aprobado', 'rechazado'];

    public function __construct(array $args = []) {
        $this->id = $args['id'] ?? null;
        $this->pedido_id = isset($args['pedido_id']) ? (int)$args['pedido_id'] : 0;
        $this->metodo = $args['metodo'] ?? 'efectivo';
        $this->estado = $args['estado'] ?? 'pendiente';
        $this->mp_preference_id = $args['mp_preference_id'] ?? null;
        $this->mp_payment_id = $args['mp_payment_id'] ?? null;
        $this->mp_status = $args['mp_status'] ?? null;
        $this->monto = isset($args['monto']) ? (float)$args['monto'] : 0.0;
        $this->rendido = isset($args['rendido']) ? (int)$args['rendido'] : 1;
        $this->fecha_rendido = $args['fecha_rendido'] ?? null;
        $this->caja_sesion_id = isset($args['caja_sesion_id']) && $args['caja_sesion_id'] !== '' ? (int)$args['caja_sesion_id'] : null;
        $this->fecha = $args['fecha'] ?? date('Y-m-d H:i:s');
    }

    public function validar(): array {
        self::$alertas = [];

        if(empty($this->pedido_id)) {
            self::$alertas['error'][] = 'El ID de pedido es obligatorio para registrar el pago';
        }

        if(!in_array($this->metodo, self::METODOS_VALIDOS)) {
            self::$alertas['error'][] = 'El método de pago no es válido';
        }

        if(!in_array($this->estado, self::ESTADOS_VALIDOS)) {
            self::$alertas['error'][] = 'El estado del pago no es válido';
        }

        if($this->monto <= 0) {
            self::$alertas['error'][] = 'El monto del pago debe ser mayor a cero';
        }

        return self::$alertas;
    }

    /**
     * Busca el registro de pago asociado a un pedido
     */
    public static function porPedido(int $pedidoId): ?self {
        $query = "SELECT * FROM " . static::$tabla . " WHERE pedido_id = {$pedidoId} LIMIT 1";
        $resultado = static::consultarSQL($query);
        return array_shift($resultado) ?? null;
    }

    /**
     * Marca el pago como aprobado, actualiza el pedido y genera el comprobante si corresponde
     */
    public function aprobar(?string $paymentId = null, ?string $mpStatus = null): bool {
        self::$db->begin_transaction();

        try {
            $this->estado = 'aprobado';
            if($paymentId) $this->mp_payment_id = $paymentId;
            if($mpStatus) $this->mp_status = $mpStatus;
            $this->fecha = date('Y-m-d H:i:s');

            // Obtener pedido asociado
            $pedido = Pedido::find((int)$this->pedido_id);
            if(!$pedido) {
                throw new \Exception('Pedido asociado no encontrado');
            }

            // Si hay sesión de caja abierta en el comercio, vincular el pago
            if(empty($this->caja_sesion_id)) {
                $sesionActiva = CajaSesion::sesionActiva((int)$pedido->comercio_id);
                if($sesionActiva) {
                    $this->caja_sesion_id = (int)$sesionActiva->id;
                }
            }

            $resPago = $this->actualizar();
            if(!$resPago) {
                throw new \Exception('Error al actualizar estado del pago');
            }

            // Si el pedido está pendiente, avanzar a confirmado
            if($pedido->estado === 'pendiente') {
                $pedido->estado = 'confirmado';
                $pedido->fecha_confirmado = date('Y-m-d H:i:s');
                $resPedido = $pedido->actualizar();
                if(!$resPedido) {
                    throw new \Exception('Error al actualizar estado del pedido');
                }
            }

            // Generar Comprobante interno si aún no existe
            $comprobanteExistente = Comprobante::porPedido((int)$pedido->id);
            if(!$comprobanteExistente) {
                Comprobante::generar((int)$pedido->comercio_id, (int)$pedido->id, (float)$pedido->total);
            }

            self::$db->commit();
            return true;
        } catch (\Throwable $e) {
            self::$db->rollback();
            self::$alertas['error'][] = $e->getMessage();
            return false;
        }
    }

    /**
     * Marca el pago como rechazado
     */
    public function rechazar(?string $paymentId = null, ?string $mpStatus = null): bool {
        $this->estado = 'rechazado';
        if($paymentId) $this->mp_payment_id = $paymentId;
        if($mpStatus) $this->mp_status = $mpStatus;
        return (bool)$this->actualizar();
    }

    public function esAprobado(): bool {
        return $this->estado === 'aprobado';
    }

    public function esPendiente(): bool {
        return $this->estado === 'pendiente';
    }

    public function badgeClase(): string {
        return match($this->estado) {
            'aprobado' => 'badge--exito',
            'rechazado' => 'badge--error',
            default => 'badge--alerta'
        };
    }

    public function badgeTexto(): string {
        return match($this->estado) {
            'aprobado' => 'Pago Aprobado',
            'rechazado' => 'Pago Rechazado',
            default => 'Pago Pendiente'
        };
    }

    /**
     * Rinde el efectivo cobrado en delivery a la sesión de caja activa
     */
    public function rendirACaja(int $cajaSesionId): bool {
        $this->rendido = 1;
        $this->fecha_rendido = date('Y-m-d H:i:s');
        $this->caja_sesion_id = $cajaSesionId;
        $this->estado = 'aprobado';
        $res = $this->actualizar();
        return (bool)$res;
    }

    /**
     * Obtiene los pagos de delivery pendientes de rendición por un repartidor
     */
    public static function porRendirRepartidor(int $repartidorId): array {
        $query = "SELECT p.*, ped.nombre_contacto, ped.total, pr.hora_asignado, pr.hora_entregado
                  FROM pagos p
                  INNER JOIN pedidos ped ON p.pedido_id = ped.id
                  INNER JOIN pedido_repartidor pr ON ped.id = pr.pedido_id
                  WHERE pr.repartidor_id = {$repartidorId}
                    AND p.metodo = 'efectivo'
                    AND p.rendido = 0
                    AND ped.estado != 'cancelado'
                  ORDER BY p.id ASC";
        return static::consultarSQL($query);
    }

    /**
     * Obtiene el listado agrupado de repartidores que tienen efectivo pendiente de rendición
     */
    public static function repartidoresConRendicionPendiente(int $comercioId = 1): array {
        $db = static::getDB();
        $query = "SELECT 
                    u.id as repartidor_id,
                    u.nombre as repartidor_nombre,
                    u.telefono as repartidor_telefono,
                    COUNT(p.id) as cantidad_pedidos,
                    COALESCE(SUM(p.monto), 0) as total_por_rendir
                  FROM pagos p
                  INNER JOIN pedidos ped ON p.pedido_id = ped.id
                  INNER JOIN pedido_repartidor pr ON ped.id = pr.pedido_id
                  INNER JOIN usuarios u ON pr.repartidor_id = u.id
                  WHERE ped.comercio_id = {$comercioId}
                    AND p.metodo = 'efectivo'
                    AND p.rendido = 0
                    AND ped.estado != 'cancelado'
                  GROUP BY u.id, u.nombre, u.telefono";

        $res = $db->query($query);
        $repartidores = [];
        if($res) {
            while($fila = $res->fetch_assoc()) {
                $fila['total_por_rendir'] = (float)$fila['total_por_rendir'];
                $fila['total_pendiente'] = (float)$fila['total_por_rendir'];
                $fila['cantidad_pedidos'] = (int)$fila['cantidad_pedidos'];
                $repartidores[] = $fila;
            }
        }
        return $repartidores;
    }
}
