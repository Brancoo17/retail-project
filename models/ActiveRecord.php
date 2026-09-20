<?php

namespace Model;

class ActiveRecord {

    protected static \mysqli $db;
    protected static string $tabla = '';
    protected static array $columnasDB = [];

    protected static array $alertas = [];

    public ?int $id = null;

    public static function setDB(\mysqli $database): void {
        self::$db = $database;
    }

    public static function getDB(): \mysqli {
        return self::$db;
    }

    /**
     * Ejecuta una consulta SQL y retorna los registros como arrays asociativos (ideal para reportes y agregaciones)
     */
    public static function queryArray(string $query): array {
        $resultado = self::$db->query($query);

        if(!($resultado instanceof \mysqli_result)) {
            return [];
        }

        $array = [];
        while($registro = $resultado->fetch_assoc()) {
            $array[] = $registro;
        }

        $resultado->free();

        return $array;
    }

    public static function setAlerta(string $tipo, string $mensaje): void {
        static::$alertas[$tipo][] = $mensaje;
    }

    public static function getAlertas(): array {
        return static::$alertas;
    }

    public function validar(): array {
        static::$alertas = [];
        return static::$alertas;
    }

    public static function consultarSQL(string $query): array {
        $resultado = self::$db->query($query);

        if(!($resultado instanceof \mysqli_result)) {
            return [];
        }

        $array = [];
        while($registro = $resultado->fetch_assoc()) {
            $array[] = static::crearObjeto($registro);
        }

        $resultado->free();

        return $array;
    }

    protected static function crearObjeto(array $registro): static {
        $objeto = new static;

        foreach($registro as $key => $value ) {
            if(property_exists( $objeto, $key  )) {
                $objeto->$key = $value;
            }
        }

        return $objeto;
    }

    public function atributos(): array {
        $atributos = [];
        foreach(static::$columnasDB as $columna) {
            if($columna === 'id') continue;
            $atributos[$columna] = $this->$columna ?? null;
        }
        return $atributos;
    }

    public function sanitizarAtributos(): array {
        $atributos = $this->atributos();
        $sanitizado = [];
        foreach($atributos as $key => $value ) {
            if(is_null($value)) {
                $sanitizado[$key] = null;
            } else {
                $sanitizado[$key] = self::$db->escape_string((string)$value);
            }
        }
        return $sanitizado;
    }

    public function sincronizar(array $args = []): void {
        foreach($args as $key => $value) {
            if(property_exists($this, $key) && !is_null($value)) {
                $this->$key = $value;
            }
        }
    }

    public function guardar(): array|bool {
        if(!is_null($this->id)) {
            return $this->actualizar();
        } else {
            return $this->crear();
        }
    }

    public static function all(): array {
        $query = "SELECT * FROM " . static::$tabla;
        return self::consultarSQL($query);
    }

    public static function find(int $id): ?static {
        $idSeguro = (int)$id;
        $query = "SELECT * FROM " . static::$tabla . " WHERE id = {$idSeguro} LIMIT 1";
        $resultado = self::consultarSQL($query);
        return array_shift($resultado);
    }

    public static function get(int $limite): array {
        $limiteSeguro = (int)$limite;
        $query = "SELECT * FROM " . static::$tabla . " LIMIT {$limiteSeguro}";
        return self::consultarSQL($query);
    }

    public static function where(string $columna, mixed $valor): ?static {
        $columnaSegura = preg_replace('/[^a-zA-Z0-9_]/', '', $columna);
        $valorSeguro = self::$db->escape_string((string)$valor);
        $query = "SELECT * FROM " . static::$tabla . " WHERE `{$columnaSegura}` = '{$valorSeguro}' LIMIT 1";
        $resultado = self::consultarSQL($query);
        return array_shift($resultado);
    }

    public static function whereAll(string $columna, mixed $valor): array {
        $columnaSegura = preg_replace('/[^a-zA-Z0-9_]/', '', $columna);
        $valorSeguro = self::$db->escape_string((string)$valor);
        $query = "SELECT * FROM " . static::$tabla . " WHERE `{$columnaSegura}` = '{$valorSeguro}'";
        return self::consultarSQL($query);
    }

    public static function SQL(string $query): array {
        return self::consultarSQL($query);
    }

    public function crear(): array {
        $atributos = $this->sanitizarAtributos();

        $columnas = join(', ', array_keys($atributos));
        $valores = array_map(function($valor) {
            return is_null($valor) ? "NULL" : "'{$valor}'";
        }, array_values($atributos));
        $valoresStr = join(', ', $valores);

        $query = " INSERT INTO " . static::$tabla . " ( {$columnas} ) VALUES ( {$valoresStr} ) ";

        $resultado = self::$db->query($query);
        if($resultado) {
            $this->id = self::$db->insert_id;
        }
        return [
            'resultado' => (bool)$resultado,
            'id' => self::$db->insert_id
        ];
    }

    public function actualizar(): bool {
        $atributos = $this->sanitizarAtributos();

        $valores = [];
        foreach($atributos as $key => $value) {
            if(is_null($value)) {
                $valores[] = "{$key}=NULL";
            } else {
                $valores[] = "{$key}='{$value}'";
            }
        }

        $query = "UPDATE " . static::$tabla . " SET ";
        $query .= join(', ', $valores);
        $query .= " WHERE id = '" . self::$db->escape_string((string)$this->id) . "' ";
        $query .= " LIMIT 1 ";

        return (bool)self::$db->query($query);
    }

    public function eliminar(): bool {
        $query = "DELETE FROM " . static::$tabla . " WHERE id = " . self::$db->escape_string((string)$this->id) . " LIMIT 1";
        return (bool)self::$db->query($query);
    }
}
