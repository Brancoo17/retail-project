<?php

namespace Model;

class Producto extends ActiveRecord {
    protected static string $tabla = 'productos';
    protected static array $columnasDB = [
        'id', 'comercio_id', 'categoria_id', 'nombre', 'descripcion', 'precio', 'imagen', 'activo'
    ];

    public int $comercio_id;
    public int $categoria_id;
    public string $nombre;
    public ?string $descripcion = null;
    public float $precio;
    public ?string $imagen = null;
    public int $activo;

    public ?string $categoria_nombre = null;
    public ?int $permite_aderezos = 0;
    public array $opciones = [];

    public function __construct(array $args = []) {
        $this->id = $args['id'] ?? null;
        $this->comercio_id = isset($args['comercio_id']) ? (int)$args['comercio_id'] : 1;
        $this->categoria_id = isset($args['categoria_id']) ? (int)$args['categoria_id'] : 0;
        $this->nombre = $args['nombre'] ?? '';
        $this->descripcion = $args['descripcion'] ?? '';
        $this->precio = isset($args['precio']) ? (float)$args['precio'] : 0.0;
        $this->imagen = $args['imagen'] ?? '';
        $this->activo = isset($args['activo']) ? (int)$args['activo'] : 1;
        $this->categoria_nombre = $args['categoria_nombre'] ?? null;
    }

    public function sincronizar(array $args = []): void {
        parent::sincronizar($args);
        if(isset($args['categoria_id'])) {
            $this->categoria_id = (int)$args['categoria_id'];
        }
        if(isset($args['precio'])) {
            $this->precio = (float)$args['precio'];
        }
        if(isset($args['activo'])) {
            $this->activo = (int)$args['activo'];
        }
    }

    public function setImagen(string $imagen): void {
        if(!empty($this->imagen) && $this->imagen !== $imagen) {
            $rutaAnterior = CARPETA_IMAGENES . $this->imagen;
            if(file_exists($rutaAnterior) && is_file($rutaAnterior)) {
                @unlink($rutaAnterior);
            }
        }
        $this->imagen = $imagen;
    }

    public function validar(): array {
        self::$alertas = [];
        if(empty($this->nombre)) {
            self::$alertas['error'][] = 'El nombre del producto es obligatorio';
        }
        if(empty($this->categoria_id)) {
            self::$alertas['error'][] = 'Debes seleccionar una categoría para el producto';
        }
        if($this->precio <= 0) {
            self::$alertas['error'][] = 'El precio base debe ser mayor a cero';
        }
        return self::$alertas;
    }

    public function categoria(): ?Categoria {
        return Categoria::find($this->categoria_id);
    }

    public function opciones(bool $soloActivas = false): array {
        return ProductoOpcion::porProducto((int)$this->id, $soloActivas);
    }

    public function eliminar(): bool {
        if(!empty($this->imagen)) {
            $ruta = CARPETA_IMAGENES . $this->imagen;
            if(file_exists($ruta) && is_file($ruta)) {
                @unlink($ruta);
            }
        }
        return parent::eliminar();
    }

    /**
     * Obtiene productos con nombre de categoría para listados
     */
    public static function porComercio(int $comercioId = 1, ?int $categoriaId = null, bool $soloActivos = false): array {
        $query = "SELECT p.*, c.nombre AS categoria_nombre, c.permite_aderezos 
                  FROM productos p 
                  JOIN categorias c ON p.categoria_id = c.id 
                  WHERE p.comercio_id = {$comercioId}";
        
        if($categoriaId) {
            $query .= " AND p.categoria_id = {$categoriaId}";
        }
        if($soloActivos) {
            $query .= " AND p.activo = 1 AND c.activo = 1";
        }

        $query .= " ORDER BY c.orden ASC, p.id DESC";
        return self::consultarSQL($query);
    }
}
