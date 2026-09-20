<?php

namespace Model;

class Categoria extends ActiveRecord {
    protected static string $tabla = 'categorias';
    protected static array $columnasDB = ['id', 'comercio_id', 'nombre', 'orden', 'activo', 'permite_aderezos'];

    public int $comercio_id;
    public string $nombre;
    public int $orden;
    public int $activo;
    public int $permite_aderezos;

    public function __construct(array $args = []) {
        $this->id = $args['id'] ?? null;
        $this->comercio_id = isset($args['comercio_id']) ? (int)$args['comercio_id'] : 1;
        $this->nombre = $args['nombre'] ?? '';
        $this->orden = isset($args['orden']) ? (int)$args['orden'] : 0;
        $this->activo = isset($args['activo']) ? (int)$args['activo'] : 1;
        $this->permite_aderezos = isset($args['permite_aderezos']) ? (int)$args['permite_aderezos'] : 0;
    }

    public function sincronizar(array $args = []): void {
        parent::sincronizar($args);
        if(isset($args['orden'])) {
            $this->orden = (int)$args['orden'];
        }
        if(isset($args['activo'])) {
            $this->activo = (int)$args['activo'];
        }
        if(isset($args['permite_aderezos'])) {
            $this->permite_aderezos = (int)$args['permite_aderezos'];
        }
    }

    public function validar(): array {
        self::$alertas = [];
        if(empty($this->nombre)) {
            self::$alertas['error'][] = 'El nombre de la categoría es obligatorio';
        }
        if($this->orden < 0) {
            self::$alertas['error'][] = 'El orden debe ser un número igual o mayor a cero';
        }
        return self::$alertas;
    }

    /**
     * Retorna las categorías del comercio ordenadas
     */
    public static function porComercio(int $comercioId = 1, bool $soloActivas = false): array {
        $query = "SELECT * FROM " . static::$tabla . " WHERE comercio_id = {$comercioId}";
        if($soloActivas) {
            $query .= " AND activo = 1";
        }
        $query .= " ORDER BY orden ASC, id ASC";
        return self::consultarSQL($query);
    }
}
