<?php

namespace Model;

class MovimientoStock extends ActiveRecord {
    protected static string $tabla = 'movimientos_stock';
    protected static array $columnasDB = [
        'id', 'comercio_id', 'insumo_id', 'usuario_id', 'tipo', 'cantidad', 'motivo', 'fecha'
    ];

    public int $comercio_id;
    public int $insumo_id;
    public ?int $usuario_id = null;
    public string $tipo;
    public float $cantidad;
    public ?string $motivo = null;
    public ?string $fecha = null;

    // Propiedades virtuales cargadas desde JOIN
    public ?string $insumo_nombre = null;
    public ?string $unidad_medida = null;
    public ?string $usuario_nombre = null;

    public const TIPOS_VALIDOS = [
        'compra' => 'Compra / Ingreso',
        'merma' => 'Merma / Descarte',
        'ajuste' => 'Ajuste de Inventario',
        'cancelacion' => 'Retorno por Cancelación',
        'venta' => 'Consumo por Venta'
    ];

    public function __construct(array $args = []) {
        $this->id = $args['id'] ?? null;
        $this->comercio_id = isset($args['comercio_id']) ? (int)$args['comercio_id'] : 1;
        $this->insumo_id = isset($args['insumo_id']) ? (int)$args['insumo_id'] : 0;
        $this->usuario_id = isset($args['usuario_id']) && !empty($args['usuario_id']) ? (int)$args['usuario_id'] : null;
        $this->tipo = $args['tipo'] ?? 'compra';
        $this->cantidad = isset($args['cantidad']) ? (float)$args['cantidad'] : 0.0;
        $this->motivo = $args['motivo'] ?? '';
        $this->fecha = $args['fecha'] ?? null;

        $this->insumo_nombre = $args['insumo_nombre'] ?? null;
        $this->unidad_medida = $args['unidad_medida'] ?? null;
        $this->usuario_nombre = $args['usuario_nombre'] ?? null;
    }

    public function validar(): array {
        self::$alertas = [];

        if(empty($this->insumo_id)) {
            self::$alertas['error'][] = 'Debes seleccionar el insumo a modificar';
        }

        if(!array_key_exists($this->tipo, self::TIPOS_VALIDOS)) {
            self::$alertas['error'][] = 'El tipo de movimiento seleccionado no es válido';
        }

        if(abs($this->cantidad) <= 0) {
            self::$alertas['error'][] = 'La cantidad del movimiento debe ser distinta de cero';
        }

        return self::$alertas;
    }

    public function badgeClase(): string {
        return match($this->tipo) {
            'compra' => 'badge--compra',
            'merma' => 'badge--merma',
            'ajuste' => 'badge--ajuste',
            'cancelacion' => 'badge--cancelacion',
            'venta' => 'badge--venta',
            default => 'badge--info'
        };
    }

    public function badgeTexto(): string {
        return self::TIPOS_VALIDOS[$this->tipo] ?? ucfirst($this->tipo);
    }

    public function signoCantidad(): string {
        if(in_array($this->tipo, ['compra', 'cancelacion'])) {
            return '+';
        }
        if(in_array($this->tipo, ['merma', 'venta'])) {
            return '-';
        }
        return ($this->cantidad > 0) ? '+' : '';
    }

    /**
     * Retorna la cantidad del movimiento formateada según el estándar argentino
     */
    public function cantidadFormateada(): string {
        return formatearCantidad($this->cantidad, $this->unidad_medida ?? '');
    }

    /**
     * Registra un movimiento y actualiza atómicamente el stock del insumo
     */
    public static function registrar(
        int $comercioId,
        int $insumoId,
        ?int $usuarioId,
        string $tipo,
        float $cantidad,
        string $motivo = ''
    ): bool {
        $insumo = Insumo::find($insumoId);
        if(!$insumo || (int)$insumo->comercio_id !== $comercioId) {
            self::$alertas['error'][] = 'El insumo no existe o no pertenece al comercio';
            return false;
        }

        // Determinar delta sobre stock_actual
        $delta = match($tipo) {
            'compra', 'cancelacion' => abs($cantidad),
            'merma', 'venta' => -abs($cantidad),
            'ajuste' => $cantidad, // Permite ajustes positivos o negativos
            default => 0.0
        };

        $nuevoStock = $insumo->stock_actual + $delta;
        if($nuevoStock < 0) {
            self::$alertas['error'][] = "Stock insuficiente. El stock de '{$insumo->nombre}' no puede quedar en negativo (actual: {$insumo->stock_actual} {$insumo->unidad_medida})";
            return false;
        }

        self::$db->begin_transaction();

        try {
            // 1. Insertar movimiento de auditoría
            $mov = new self([
                'comercio_id' => $comercioId,
                'insumo_id' => $insumoId,
                'usuario_id' => $usuarioId,
                'tipo' => $tipo,
                'cantidad' => abs($cantidad),
                'motivo' => $motivo
            ]);

            $queryMov = "INSERT INTO " . static::$tabla . " 
                         (comercio_id, insumo_id, usuario_id, tipo, cantidad, motivo) 
                         VALUES (
                             {$comercioId}, 
                             {$insumoId}, 
                             " . ($usuarioId ? $usuarioId : "NULL") . ", 
                             '{$tipo}', 
                             " . abs($cantidad) . ", 
                             '" . self::$db->escape_string($motivo) . "'
                         )";
            $resMov = self::$db->query($queryMov);
            if(!$resMov) {
                throw new \Exception('Error al registrar el movimiento en el historial');
            }

            // 2. Actualizar stock del insumo
            $queryInsumo = "UPDATE insumos 
                            SET stock_actual = {$nuevoStock} 
                            WHERE id = {$insumoId}";
            $resInsumo = self::$db->query($queryInsumo);
            if(!$resInsumo) {
                throw new \Exception('Error al actualizar el stock del insumo');
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
     * Obtiene los movimientos con datos del insumo y usuario responsable
     */
    public static function porComercio(
        int $comercioId = 1,
        int $limite = 100,
        ?int $insumoId = null,
        ?string $tipo = null
    ): array {
        $query = "SELECT m.*, i.nombre AS insumo_nombre, i.unidad_medida, u.nombre AS usuario_nombre 
                  FROM " . static::$tabla . " m
                  JOIN insumos i ON m.insumo_id = i.id
                  LEFT JOIN usuarios u ON m.usuario_id = u.id
                  WHERE m.comercio_id = {$comercioId}";

        if($insumoId) {
            $query .= " AND m.insumo_id = {$insumoId}";
        }
        if(!empty($tipo)) {
            $tipoSanitizado = self::$db->escape_string($tipo);
            $query .= " AND m.tipo = '{$tipoSanitizado}'";
        }

        $query .= " ORDER BY m.id DESC LIMIT {$limite}";
        return static::consultarSQL($query);
    }

    /**
     * Balance de consumo, mermas y compras de insumos en un período
     */
    public static function balanceInsumosPeriodo(string $inicio, string $fin, int $comercioId = 1): array {
        $inicioEsc = self::$db->escape_string($inicio);
        $finEsc = self::$db->escape_string($fin);

        $sql = "SELECT 
                    i.id as insumo_id,
                    i.nombre as insumo_nombre,
                    i.unidad_medida,
                    i.stock_actual,
                    i.stock_minimo,
                    COALESCE(SUM(CASE WHEN m.tipo = 'venta' THEN ABS(m.cantidad) ELSE 0 END), 0) as consumo_ventas,
                    COALESCE(SUM(CASE WHEN m.tipo = 'merma' THEN ABS(m.cantidad) ELSE 0 END), 0) as total_mermas,
                    COALESCE(SUM(CASE WHEN m.tipo = 'compra' THEN m.cantidad ELSE 0 END), 0) as total_compras
                FROM insumos i
                LEFT JOIN " . static::$tabla . " m ON i.id = m.insumo_id 
                     AND m.fecha >= '{$inicioEsc}' 
                     AND m.fecha <= '{$finEsc}'
                WHERE i.comercio_id = {$comercioId}
                GROUP BY i.id, i.nombre, i.unidad_medida, i.stock_actual, i.stock_minimo
                ORDER BY consumo_ventas DESC, total_mermas DESC, i.nombre ASC";

        $resultado = self::$db->query($sql);
        $balance = [];
        if($resultado) {
            while($fila = $resultado->fetch_assoc()) {
                $balance[] = [
                    'insumo_id' => (int)$fila['insumo_id'],
                    'insumo_nombre' => $fila['insumo_nombre'],
                    'unidad_medida' => $fila['unidad_medida'],
                    'stock_actual' => (float)$fila['stock_actual'],
                    'stock_minimo' => (float)$fila['stock_minimo'],
                    'consumo_ventas' => (float)$fila['consumo_ventas'],
                    'total_mermas' => (float)$fila['total_mermas'],
                    'total_compras' => (float)$fila['total_compras'],
                    'stock_bajo' => ((float)$fila['stock_actual'] <= (float)$fila['stock_minimo'])
                ];
            }
        }
        return $balance;
    }
}
