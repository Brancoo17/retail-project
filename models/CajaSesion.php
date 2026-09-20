<?php

namespace Model;

class CajaSesion extends ActiveRecord {
    protected static string $tabla = 'caja_sesiones';
    protected static array $columnasDB = [
        'id', 'comercio_id', 'usuario_id', 'monto_apertura', 'monto_cierre',
        'total_ventas_efectivo', 'total_ventas_mp', 'diferencia',
        'estado', 'fecha_apertura', 'fecha_cierre', 'observaciones'
    ];

    public int $comercio_id;
    public int $usuario_id;
    public float $monto_apertura;
    public ?float $monto_cierre = null;
    public float $total_ventas_efectivo = 0.0;
    public float $total_ventas_mp = 0.0;
    public ?float $diferencia = null;
    public string $estado; // 'abierta', 'cerrada'
    public string $fecha_apertura;
    public ?string $fecha_cierre = null;
    public ?string $observaciones = null;

    public function __construct(array $args = []) {
        $this->id = $args['id'] ?? null;
        $this->comercio_id = (int)($args['comercio_id'] ?? 1);
        $this->usuario_id = (int)($args['usuario_id'] ?? 0);
        $this->monto_apertura = (float)($args['monto_apertura'] ?? 0.0);
        $this->monto_cierre = isset($args['monto_cierre']) && $args['monto_cierre'] !== '' ? (float)$args['monto_cierre'] : null;
        $this->total_ventas_efectivo = (float)($args['total_ventas_efectivo'] ?? 0.0);
        $this->total_ventas_mp = (float)($args['total_ventas_mp'] ?? 0.0);
        $this->diferencia = isset($args['diferencia']) && $args['diferencia'] !== '' ? (float)$args['diferencia'] : null;
        $this->estado = $args['estado'] ?? 'abierta';
        $this->fecha_apertura = $args['fecha_apertura'] ?? date('Y-m-d H:i:s');
        $this->fecha_cierre = $args['fecha_cierre'] ?? null;
        $this->observaciones = $args['observaciones'] ?? null;
    }

    /**
     * Obtiene la sesión de caja actualmente abierta para el comercio
     */
    public static function sesionActiva(int $comercioId = 1): ?self {
        $query = "SELECT * FROM " . static::$tabla . " WHERE comercio_id = {$comercioId} AND estado = 'abierta' ORDER BY id DESC LIMIT 1";
        $resultado = static::consultarSQL($query);
        return array_shift($resultado);
    }

    /**
     * Abre una nueva sesión de caja
     */
    public static function abrir(int $comercioId, int $usuarioId, float $montoApertura, string $obs = ''): ?self {
        // Cerrar sesiones huérfanas si existieran
        $activa = self::sesionActiva($comercioId);
        if($activa) {
            return $activa;
        }

        $nueva = new self([
            'comercio_id' => $comercioId,
            'usuario_id' => $usuarioId,
            'monto_apertura' => $montoApertura,
            'estado' => 'abierta',
            'fecha_apertura' => date('Y-m-d H:i:s'),
            'observaciones' => $obs
        ]);

        $res = $nueva->crear();
        if($res['resultado'] ?? false) {
            $nueva->id = $res['id'];
            return $nueva;
        }

        return null;
    }

    /**
     * Obtiene el usuario que abrió o gestiona la sesión
     */
    public function usuario(): ?Usuario {
        return Usuario::find((int)$this->usuario_id);
    }

    /**
     * Calcula los totales recaudados durante el período de esta sesión
     */
    public function calcularTotalesTurno(): array {
        $db = static::getDB();
        $comercioId = (int)$this->comercio_id;
        $sesionId = (int)$this->id;
        $fechaInicio = $db->escape_string($this->fecha_apertura);
        $fechaFin = $this->fecha_cierre ? $db->escape_string($this->fecha_cierre) : date('Y-m-d H:i:s');

        // 1. Ventas Mercado Pago (Online)
        $qMP = "SELECT COUNT(p.id) as cant, COALESCE(SUM(p.monto), 0) as total
                FROM pagos p
                INNER JOIN pedidos ped ON p.pedido_id = ped.id
                WHERE ped.comercio_id = {$comercioId}
                  AND p.metodo = 'mercadopago'
                  AND p.estado = 'aprobado'
                  AND ped.estado != 'cancelado'
                  AND (
                      p.caja_sesion_id = {$sesionId}
                      OR (p.fecha >= '{$fechaInicio}' AND p.fecha <= '{$fechaFin}')
                      OR (ped.fecha_confirmado >= '{$fechaInicio}' AND ped.fecha_confirmado <= '{$fechaFin}')
                  )";
        $resMP = $db->query($qMP)->fetch_assoc();
        $ventasMP = (float)($resMP['total'] ?? 0.0);
        $cantMP = (int)($resMP['cant'] ?? 0);

        // 2. Efectivo cobrado directamente en Mostrador (Retiro en local o pedido presencial)
        $qMostrador = "SELECT COUNT(p.id) as cant, COALESCE(SUM(p.monto), 0) as total
                       FROM pagos p
                       INNER JOIN pedidos ped ON p.pedido_id = ped.id
                       WHERE ped.comercio_id = {$comercioId}
                         AND p.metodo = 'efectivo'
                         AND ped.tipo = 'retiro'
                         AND p.estado = 'aprobado'
                         AND ped.estado != 'cancelado'
                         AND (p.caja_sesion_id = {$sesionId} OR (p.fecha >= '{$fechaInicio}' AND p.fecha <= '{$fechaFin}'))";
        $resMostrador = $db->query($qMostrador)->fetch_assoc();
        $efectivoMostrador = (float)($resMostrador['total'] ?? 0.0);
        $cantMostrador = (int)($resMostrador['cant'] ?? 0);

        // 3. Efectivo de Delivery ya rendido físicamente en caja por repartidores
        $qRendido = "SELECT COUNT(p.id) as cant, COALESCE(SUM(p.monto), 0) as total
                     FROM pagos p
                     INNER JOIN pedidos ped ON p.pedido_id = ped.id
                     WHERE ped.comercio_id = {$comercioId}
                       AND p.metodo = 'efectivo'
                       AND ped.tipo = 'delivery'
                       AND p.estado = 'aprobado'
                       AND p.rendido = 1
                       AND (p.caja_sesion_id = {$sesionId} OR (p.fecha_rendido >= '{$fechaInicio}' AND p.fecha_rendido <= '{$fechaFin}'))
                       AND ped.estado != 'cancelado'";
        $resRendido = $db->query($qRendido)->fetch_assoc();
        $efectivoRendido = (float)($resRendido['total'] ?? 0.0);
        $cantRendido = (int)($resRendido['cant'] ?? 0);

        // 4. Efectivo en la calle (Delivery entregado o en camino con repartidor, aún NO rendido en caja)
        $qCalle = "SELECT COUNT(p.id) as cant, COALESCE(SUM(p.monto), 0) as total
                   FROM pagos p
                   INNER JOIN pedidos ped ON p.pedido_id = ped.id
                   WHERE ped.comercio_id = {$comercioId}
                     AND p.metodo = 'efectivo'
                     AND ped.tipo = 'delivery'
                     AND p.rendido = 0
                     AND ped.estado IN ('en_camino', 'entregado')";
        $resCalle = $db->query($qCalle)->fetch_assoc();
        $efectivoEnCalle = (float)($resCalle['total'] ?? 0.0);
        $cantEnCalle = (int)($resCalle['cant'] ?? 0);

        // Totales de caja física
        $totalEfectivoCaja = $efectivoMostrador + $efectivoRendido;
        $efectivoEsperadoEnCaja = (float)$this->monto_apertura + $totalEfectivoCaja;
        $totalVentas = $totalEfectivoCaja + $efectivoEnCalle + $ventasMP;

        return [
            'ventas_efectivo' => $totalEfectivoCaja,
            'efectivo_mostrador' => $efectivoMostrador,
            'efectivo_rendido' => $efectivoRendido,
            'efectivo_en_calle' => $efectivoEnCalle,
            'ventas_mp' => $ventasMP,
            'total_recaudado' => $totalVentas,
            'efectivo_esperado' => $efectivoEsperadoEnCaja,
            'cant_mostrador' => $cantMostrador,
            'cant_rendido' => $cantRendido,
            'cant_en_calle' => $cantEnCalle,
            'cant_mp' => $cantMP,
            'total_pedidos' => $cantMostrador + $cantRendido + $cantMP
        ];
    }

    /**
     * Cierra el turno de caja, calculando el arqueo final
     */
    public function cerrar(float $montoCierre, string $obs = ''): bool {
        $totales = $this->calcularTotalesTurno();
        $this->total_ventas_efectivo = $totales['ventas_efectivo'];
        $this->total_ventas_mp = $totales['ventas_mp'];
        $this->monto_cierre = $montoCierre;
        $this->diferencia = round($montoCierre - $totales['efectivo_esperado'], 2);
        $this->estado = 'cerrada';
        $this->fecha_cierre = date('Y-m-d H:i:s');
        if(!empty($obs)) {
            $this->observaciones = trim(($this->observaciones ? $this->observaciones . " | " : "") . "Cierre: " . $obs);
        }

        $res = $this->guardar();
        return is_array($res) ? (bool)($res['resultado'] ?? false) : (bool)$res;
    }
}
