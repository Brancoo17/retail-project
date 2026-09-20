<?php

namespace Model;

class Aderezo extends ActiveRecord {
    protected static string $tabla = 'aderezos';
    protected static array $columnasDB = ['id', 'comercio_id', 'nombre', 'activo', 'orden'];

    public int $comercio_id;
    public string $nombre;
    public int $activo;
    public int $orden;

    public function __construct(array $args = []) {
        $this->id = $args['id'] ?? null;
        $this->comercio_id = isset($args['comercio_id']) ? (int)$args['comercio_id'] : 1;
        $this->nombre = $args['nombre'] ?? '';
        $this->activo = isset($args['activo']) ? (int)$args['activo'] : 1;
        $this->orden = isset($args['orden']) ? (int)$args['orden'] : 0;
    }

    public function sincronizar(array $args = []): void {
        parent::sincronizar($args);
        if(isset($args['activo'])) {
            $this->activo = (int)$args['activo'];
        }
        if(isset($args['orden'])) {
            $this->orden = (int)$args['orden'];
        }
    }

    public function validar(): array {
        self::$alertas = [];
        if(empty(trim($this->nombre))) {
            self::$alertas['error'][] = 'El nombre del aderezo o salsa es obligatorio';
        }
        return self::$alertas;
    }

    /**
     * Retorna los aderezos de un comercio ordenados
     */
    public static function porComercio(int $comercioId = 1, bool $soloActivos = false): array {
        $query = "SELECT * FROM " . static::$tabla . " WHERE comercio_id = {$comercioId}";
        if($soloActivos) {
            $query .= " AND activo = 1";
        }
        $query .= " ORDER BY orden ASC, nombre ASC";
        return self::consultarSQL($query);
    }
}
