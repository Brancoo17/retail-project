<?php

namespace Model;

class DireccionCliente extends ActiveRecord {
    protected static string $tabla = 'direcciones_cliente';
    protected static array $columnasDB = [
        'id', 'usuario_id', 'etiqueta', 'direccion', 'lat', 'lng', 'observaciones'
    ];

    public int $usuario_id;
    public string $etiqueta;
    public string $direccion;
    public ?float $lat = null;
    public ?float $lng = null;
    public ?string $observaciones = null;
    public ?string $creado_en = null;

    public function __construct(array $args = []) {
        $this->id = $args['id'] ?? null;
        $this->usuario_id = isset($args['usuario_id']) ? (int)$args['usuario_id'] : 0;
        $this->etiqueta = $args['etiqueta'] ?? 'Casa';
        $this->direccion = $args['direccion'] ?? '';
        $this->lat = isset($args['lat']) && !empty($args['lat']) ? (float)$args['lat'] : null;
        $this->lng = isset($args['lng']) && !empty($args['lng']) ? (float)$args['lng'] : null;
        $this->observaciones = $args['observaciones'] ?? null;
        $this->creado_en = $args['creado_en'] ?? null;
    }

    public function validar(): array {
        self::$alertas = [];

        if(empty($this->usuario_id)) {
            self::$alertas['error'][] = 'El usuario es obligatorio';
        }
        if(empty(trim($this->direccion))) {
            self::$alertas['error'][] = 'La dirección es obligatoria';
        }

        return self::$alertas;
    }

    public static function porUsuario(int $usuarioId): array {
        $query = "SELECT * FROM " . static::$tabla . " WHERE usuario_id = {$usuarioId} ORDER BY id DESC";
        return static::consultarSQL($query);
    }
}
