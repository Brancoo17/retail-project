<?php

namespace Model;

class Insumo extends ActiveRecord {
    protected static string $tabla = 'insumos';
    protected static array $columnasDB = [
        'id', 'comercio_id', 'nombre', 'unidad_medida', 'stock_actual', 'stock_minimo'
    ];

    public int $comercio_id;
    public string $nombre;
    public string $unidad_medida;
    public float $stock_actual;
    public float $stock_minimo;

    public const UNIDADES_VALIDAS = [
        'kg' => 'Kilogramos (kg)',
        'gr' => 'Gramos (gr)',
        'lts' => 'Litros (lts)',
        'ml' => 'Mililitros (ml)',
        'unidades' => 'Unidades'
    ];

    public function __construct(array $args = []) {
        $this->id = $args['id'] ?? null;
        $this->comercio_id = isset($args['comercio_id']) ? (int)$args['comercio_id'] : 1;
        $this->nombre = $args['nombre'] ?? '';
        $this->unidad_medida = $args['unidad_medida'] ?? 'unidades';
        $this->stock_actual = isset($args['stock_actual']) ? (float)$args['stock_actual'] : 0.0;
        $this->stock_minimo = isset($args['stock_minimo']) ? (float)$args['stock_minimo'] : 0.0;
    }

    public function sincronizar(array $args = []): void {
        parent::sincronizar($args);
        if(isset($args['stock_actual'])) {
            $this->stock_actual = (float)str_replace(',', '.', (string)$args['stock_actual']);
        }
        if(isset($args['stock_minimo'])) {
            $this->stock_minimo = (float)str_replace(',', '.', (string)$args['stock_minimo']);
        }
    }

    /**
     * Retorna el stock actual formateado según formato argentino
     */
    public function stockActualFormateado(): string {
        return formatearCantidad($this->stock_actual, $this->unidad_medida);
    }

    /**
     * Retorna el stock mínimo de alerta formateado según formato argentino
     */
    public function stockMinimoFormateado(): string {
        return formatearCantidad($this->stock_minimo, $this->unidad_medida);
    }

    public function validar(): array {
        self::$alertas = [];

        if(empty(trim($this->nombre))) {
            self::$alertas['error'][] = 'El nombre del insumo es obligatorio';
        }

        if(!array_key_exists($this->unidad_medida, self::UNIDADES_VALIDAS)) {
            self::$alertas['error'][] = 'La unidad de medida seleccionada no es válida';
        }

        if($this->stock_actual < 0) {
            self::$alertas['error'][] = 'El stock actual no puede ser un número negativo';
        }

        if($this->stock_minimo < 0) {
            self::$alertas['error'][] = 'El stock mínimo de alerta no puede ser un número negativo';
        }

        return self::$alertas;
    }

    /**
     * Retorna el estado del stock: 'agotado', 'bajo' o 'normal'
     */
    public function estadoStock(): string {
        if($this->stock_actual <= 0) {
            return 'agotado';
        }
        if($this->stock_actual <= $this->stock_minimo) {
            return 'bajo';
        }
        return 'normal';
    }

    /**
     * Retorna la clase CSS del badge según el nivel de stock
     */
    public function badgeClase(): string {
        return match($this->estadoStock()) {
            'agotado' => 'badge--error',
            'bajo' => 'badge--alerta',
            default => 'badge--exito'
        };
    }

    /**
     * Retorna la etiqueta amigable del badge
     */
    public function badgeTexto(): string {
        return match($this->estadoStock()) {
            'agotado' => 'Agotado',
            'bajo' => 'Bajo Stock',
            default => 'Normal'
        };
    }

    /**
     * Obtiene los insumos de un comercio con soporte de búsqueda
     */
    public static function porComercio(int $comercioId = 1, ?string $busqueda = null): array {
        $query = "SELECT * FROM " . static::$tabla . " WHERE comercio_id = {$comercioId}";
        if(!empty($busqueda)) {
            $busquedaSanitizada = self::$db->escape_string(trim($busqueda));
            $query .= " AND nombre LIKE '%{$busquedaSanitizada}%'";
        }
        $query .= " ORDER BY nombre ASC";
        return static::consultarSQL($query);
    }

    /**
     * Obtiene métricas resumidas para el panel de insumos
     */
    public static function estadisticas(int $comercioId = 1): array {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN stock_actual <= 0 THEN 1 ELSE 0 END) as agotados,
                    SUM(CASE WHEN stock_actual > 0 AND stock_actual <= stock_minimo THEN 1 ELSE 0 END) as bajos
                  FROM " . static::$tabla . "
                  WHERE comercio_id = {$comercioId}";

        $resultado = self::$db->query($query);
        $fila = $resultado ? $resultado->fetch_assoc() : null;

        return [
            'total' => (int)($fila['total'] ?? 0),
            'agotados' => (int)($fila['agotados'] ?? 0),
            'bajos' => (int)($fila['bajos'] ?? 0)
        ];
    }

    /**
     * Retorna las recetas donde este insumo está siendo utilizado
     */
    public function recetasEnUso(): array {
        $query = "SELECT pi.*, p.nombre as producto_nombre 
                  FROM producto_insumos pi
                  JOIN productos p ON pi.producto_id = p.id
                  WHERE pi.insumo_id = " . (int)$this->id;
        return static::consultarSQL($query);
    }
}
