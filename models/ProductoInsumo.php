<?php

namespace Model;

class ProductoInsumo extends ActiveRecord {
    protected static string $tabla = 'producto_insumos';
    protected static array $columnasDB = [
        'id', 'producto_id', 'insumo_id', 'cantidad_consumo'
    ];

    public int $producto_id;
    public int $insumo_id;
    public float $cantidad_consumo;

    // Propiedades virtuales cargadas desde JOIN
    public ?string $insumo_nombre = null;
    public ?string $unidad_medida = null;
    public ?float $stock_actual = null;

    public function __construct(array $args = []) {
        $this->id = $args['id'] ?? null;
        $this->producto_id = isset($args['producto_id']) ? (int)$args['producto_id'] : 0;
        $this->insumo_id = isset($args['insumo_id']) ? (int)$args['insumo_id'] : 0;
        $this->cantidad_consumo = isset($args['cantidad_consumo']) ? (float)$args['cantidad_consumo'] : 0.0;
        $this->insumo_nombre = $args['insumo_nombre'] ?? null;
        $this->unidad_medida = $args['unidad_medida'] ?? null;
        $this->stock_actual = isset($args['stock_actual']) ? (float)$args['stock_actual'] : null;
    }

    public function sincronizar(array $args = []): void {
        parent::sincronizar($args);
        if(isset($args['producto_id'])) {
            $this->producto_id = (int)$args['producto_id'];
        }
        if(isset($args['insumo_id'])) {
            $this->insumo_id = (int)$args['insumo_id'];
        }
        if(isset($args['cantidad_consumo'])) {
            $this->cantidad_consumo = (float)str_replace(',', '.', (string)$args['cantidad_consumo']);
        }
    }

    /**
     * Retorna la cantidad consumida formateada según el estándar argentino
     */
    public function consumoFormateado(): string {
        return formatearCantidad($this->cantidad_consumo, $this->unidad_medida ?? '');
    }

    /**
     * Retorna el stock actual formateado según el estándar argentino
     */
    public function stockActualFormateado(): string {
        return formatearCantidad($this->stock_actual ?? 0.0, $this->unidad_medida ?? '');
    }

    public function validar(): array {
        self::$alertas = [];

        if(empty($this->producto_id)) {
            self::$alertas['error'][] = 'El producto vinculado es obligatorio';
        }

        if(empty($this->insumo_id)) {
            self::$alertas['error'][] = 'Debes seleccionar un insumo de la lista';
        }

        if($this->cantidad_consumo <= 0) {
            self::$alertas['error'][] = 'La cantidad consumida por unidad debe ser mayor a cero';
        }

        return self::$alertas;
    }

    public function insumo(): ?Insumo {
        return Insumo::find($this->insumo_id);
    }

    /**
     * Obtiene todos los ingredientes de la receta de un producto con datos del insumo
     */
    public static function porProducto(int $productoId): array {
        $query = "SELECT pi.*, i.nombre AS insumo_nombre, i.unidad_medida, i.stock_actual 
                  FROM " . static::$tabla . " pi
                  JOIN insumos i ON pi.insumo_id = i.id
                  WHERE pi.producto_id = {$productoId}
                  ORDER BY i.nombre ASC";
        return static::consultarSQL($query);
    }

    /**
     * Verifica si un insumo ya está asignado a la receta del producto
     */
    public static function existeEnReceta(int $productoId, int $insumoId): bool {
        $query = "SELECT id FROM " . static::$tabla . " 
                  WHERE producto_id = {$productoId} AND insumo_id = {$insumoId} 
                  LIMIT 1";
        $resultado = self::$db->query($query);
        return ($resultado && $resultado->num_rows > 0);
    }
}
