<?php

namespace Model;

class PedidoDetalle extends ActiveRecord {
    protected static string $tabla = 'pedido_detalle';
    protected static array $columnasDB = [
        'id', 'pedido_id', 'producto_id', 'nombre_producto',
        'opciones_texto', 'cantidad', 'precio_unitario', 'subtotal'
    ];

    public int $pedido_id;
    public int $producto_id;
    public string $nombre_producto;
    public ?string $opciones_texto = null;
    public int $cantidad;
    public float $precio_unitario;
    public float $subtotal;

    public function __construct(array $args = []) {
        $this->id = $args['id'] ?? null;
        $this->pedido_id = isset($args['pedido_id']) ? (int)$args['pedido_id'] : 0;
        $this->producto_id = isset($args['producto_id']) ? (int)$args['producto_id'] : 0;
        $this->nombre_producto = $args['nombre_producto'] ?? '';
        $this->opciones_texto = $args['opciones_texto'] ?? null;
        $this->cantidad = isset($args['cantidad']) ? (int)$args['cantidad'] : 1;
        $this->precio_unitario = isset($args['precio_unitario']) ? (float)$args['precio_unitario'] : 0.0;
        $this->subtotal = isset($args['subtotal']) ? (float)$args['subtotal'] : 0.0;
    }

    public function validar(): array {
        self::$alertas = [];

        if(empty($this->pedido_id)) {
            self::$alertas['error'][] = 'El pedido asociado es obligatorio';
        }
        if(empty($this->producto_id)) {
            self::$alertas['error'][] = 'El producto es obligatorio';
        }
        if($this->cantidad <= 0) {
            self::$alertas['error'][] = 'La cantidad debe ser mayor a cero';
        }

        return self::$alertas;
    }

    public static function porPedido(int $pedidoId): array {
        $query = "SELECT * FROM " . static::$tabla . " WHERE pedido_id = {$pedidoId} ORDER BY id ASC";
        return static::consultarSQL($query);
    }

    /**
     * Ranking de productos más vendidos en un período
     */
    public static function rankingProductos(string $inicio, string $fin, int $comercioId = 1, int $limite = 10): array {
        $inicioEsc = self::$db->escape_string($inicio);
        $finEsc = self::$db->escape_string($fin);

        $sql = "SELECT 
                    pd.producto_id,
                    pd.nombre_producto,
                    COALESCE(c.nombre, 'Sin categoría') as categoria_nombre,
                    SUM(pd.cantidad) as total_unidades,
                    SUM(pd.subtotal) as total_recaudado
                FROM " . static::$tabla . " pd
                INNER JOIN pedidos p ON pd.pedido_id = p.id
                LEFT JOIN productos pr ON pd.producto_id = pr.id
                LEFT JOIN categorias c ON pr.categoria_id = c.id
                WHERE p.comercio_id = {$comercioId}
                  AND p.estado IN ('confirmado', 'en_preparacion', 'en_camino', 'entregado')
                  AND (
                      (p.fecha_confirmado IS NOT NULL AND p.fecha_confirmado >= '{$inicioEsc}' AND p.fecha_confirmado <= '{$finEsc}')
                      OR (p.fecha_confirmado IS NULL AND p.creado_en >= '{$inicioEsc}' AND p.creado_en <= '{$finEsc}')
                  )
                GROUP BY pd.producto_id, pd.nombre_producto, c.nombre
                ORDER BY total_unidades DESC, total_recaudado DESC
                LIMIT {$limite}";

        $resultado = self::$db->query($sql);
        $ranking = [];
        if($resultado) {
            while($fila = $resultado->fetch_assoc()) {
                $ranking[] = [
                    'producto_id' => (int)$fila['producto_id'],
                    'nombre_producto' => $fila['nombre_producto'],
                    'categoria_nombre' => $fila['categoria_nombre'],
                    'total_unidades' => (int)$fila['total_unidades'],
                    'total_recaudado' => (float)$fila['total_recaudado']
                ];
            }
        }
        return $ranking;
    }
}
