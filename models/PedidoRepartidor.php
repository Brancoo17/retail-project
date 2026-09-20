<?php

namespace Model;

class PedidoRepartidor extends ActiveRecord {
    protected static string $tabla = 'pedido_repartidor';
    protected static array $columnasDB = [
        'id', 'pedido_id', 'repartidor_id', 'hora_asignado', 'hora_entregado', 'estado'
    ];

    public int $pedido_id;
    public int $repartidor_id;
    public string $hora_asignado;
    public ?string $hora_entregado = null;
    public string $estado; // 'asignado', 'en_camino', 'entregado'

    public function __construct(array $args = []) {
        $this->id = $args['id'] ?? null;
        $this->pedido_id = (int)($args['pedido_id'] ?? 0);
        $this->repartidor_id = (int)($args['repartidor_id'] ?? 0);
        $this->hora_asignado = $args['hora_asignado'] ?? date('Y-m-d H:i:s');
        $this->hora_entregado = $args['hora_entregado'] ?? null;
        $this->estado = $args['estado'] ?? 'asignado';
    }

    /**
     * Obtiene la asignación activa de un pedido
     */
    public static function porPedido(int $pedidoId): ?self {
        $query = "SELECT * FROM " . static::$tabla . " WHERE pedido_id = " . (int)$pedidoId . " ORDER BY id DESC LIMIT 1";
        $resultado = static::consultarSQL($query);
        return array_shift($resultado);
    }

    /**
     * Obtiene el modelo de usuario asociado al repartidor
     */
    public function repartidor(): ?Usuario {
        return Usuario::find((int)$this->repartidor_id);
    }

    /**
     * Asigna un repartidor a un pedido, creando o actualizando el registro
     */
    public static function asignar(int $pedidoId, int $repartidorId): ?self {
        $existente = self::porPedido($pedidoId);
        if($existente && $existente->estado !== 'entregado') {
            $existente->repartidor_id = $repartidorId;
            $existente->hora_asignado = date('Y-m-d H:i:s');
            $existente->estado = 'en_camino';
            $existente->guardar();
            return $existente;
        }

        $nuevaAsignacion = new self([
            'pedido_id' => $pedidoId,
            'repartidor_id' => $repartidorId,
            'hora_asignado' => date('Y-m-d H:i:s'),
            'hora_entregado' => null,
            'estado' => 'en_camino'
        ]);

        $res = $nuevaAsignacion->crear();
        if($res['resultado'] ?? false) {
            $nuevaAsignacion->id = $res['id'];
            return $nuevaAsignacion;
        }

        return null;
    }

    /**
     * Marca la entrega completada por el repartidor
     */
    public function registrarEntrega(): bool {
        $this->hora_entregado = date('Y-m-d H:i:s');
        $this->estado = 'entregado';
        $res = $this->guardar();
        return is_array($res) ? (bool)($res['resultado'] ?? false) : (bool)$res;
    }

    /**
     * Métricas de rendimiento de repartidores en un período
     */
    public static function metricasRendimiento(string $inicio, string $fin, int $comercioId = 1): array {
        $inicioEsc = self::$db->escape_string($inicio);
        $finEsc = self::$db->escape_string($fin);

        $sql = "SELECT 
                    u.id as repartidor_id,
                    u.nombre as repartidor_nombre,
                    u.telefono,
                    u.confirmado as activo,
                    COUNT(DISTINCT CASE WHEN pr.estado = 'entregado' AND pr.hora_entregado >= '{$inicioEsc}' AND pr.hora_entregado <= '{$finEsc}' THEN pr.id ELSE NULL END) as entregas_completadas,
                    ROUND(AVG(CASE WHEN pr.estado = 'entregado' AND pr.hora_entregado >= '{$inicioEsc}' AND pr.hora_entregado <= '{$finEsc}' AND pr.hora_entregado IS NOT NULL 
                                   THEN TIMESTAMPDIFF(MINUTE, pr.hora_asignado, pr.hora_entregado) ELSE NULL END), 1) as minutos_promedio,
                    COALESCE(SUM(CASE WHEN pr.estado = 'entregado' AND pr.hora_entregado >= '{$inicioEsc}' AND pr.hora_entregado <= '{$finEsc}' AND pag.metodo = 'efectivo' AND pag.estado = 'aprobado'
                                      THEN pag.monto ELSE 0 END), 0) as total_efectivo_cobrado
                FROM usuarios u
                LEFT JOIN " . static::$tabla . " pr ON u.id = pr.repartidor_id
                LEFT JOIN pagos pag ON pr.pedido_id = pag.pedido_id
                WHERE u.comercio_id = {$comercioId} 
                  AND u.rol = 'repartidor'
                GROUP BY u.id, u.nombre, u.telefono, u.confirmado
                ORDER BY entregas_completadas DESC, minutos_promedio ASC, u.nombre ASC";

        $resultado = self::$db->query($sql);
        $metricas = [];
        if($resultado) {
            while($fila = $resultado->fetch_assoc()) {
                $metricas[] = [
                    'repartidor_id' => (int)$fila['repartidor_id'],
                    'repartidor_nombre' => $fila['repartidor_nombre'],
                    'telefono' => $fila['telefono'],
                    'activo' => (bool)$fila['activo'],
                    'entregas_completadas' => (int)$fila['entregas_completadas'],
                    'minutos_promedio' => $fila['minutos_promedio'] !== null ? (float)$fila['minutos_promedio'] : 0.0,
                    'total_efectivo_cobrado' => (float)$fila['total_efectivo_cobrado']
                ];
            }
        }
        return $metricas;
    }
}
