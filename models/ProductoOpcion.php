<?php

namespace Model;

class ProductoOpcion extends ActiveRecord {
    protected static string $tabla = 'producto_opciones';
    protected static array $columnasDB = ['id', 'producto_id', 'nombre', 'precio_adicional', 'activo'];

    public int $producto_id;
    public string $nombre;
    public float $precio_adicional;
    public int $activo;

    public function __construct(array $args = []) {
        $this->id = $args['id'] ?? null;
        $this->producto_id = isset($args['producto_id']) ? (int)$args['producto_id'] : 0;
        $this->nombre = $args['nombre'] ?? '';
        $this->precio_adicional = isset($args['precio_adicional']) ? (float)$args['precio_adicional'] : 0.0;
        $this->activo = isset($args['activo']) ? (int)$args['activo'] : 1;
    }

    public function sincronizar(array $args = []): void {
        parent::sincronizar($args);
        if(isset($args['producto_id'])) {
            $this->producto_id = (int)$args['producto_id'];
        }
        if(isset($args['precio_adicional'])) {
            $this->precio_adicional = (float)$args['precio_adicional'];
        }
        if(isset($args['activo'])) {
            $this->activo = (int)$args['activo'];
        }
    }

    public function validar(): array {
        self::$alertas = [];
        if(empty($this->nombre)) {
            self::$alertas['error'][] = 'El nombre de la opción o variante es obligatorio';
        }
        if($this->precio_adicional < 0) {
            self::$alertas['error'][] = 'El precio adicional no puede ser negativo';
        }
        return self::$alertas;
    }

    public static function porProducto(int $productoId, bool $soloActivas = false): array {
        $query = "SELECT * FROM " . static::$tabla . " WHERE producto_id = {$productoId}";
        if($soloActivas) {
            $query .= " AND activo = 1";
        }
        $query .= " ORDER BY id ASC";
        return self::consultarSQL($query);
    }
}
