<?php

namespace Model;

class Comercio extends ActiveRecord {
    protected static string $tabla = 'comercios';
    protected static array $columnasDB = [
        'id', 'nombre', 'direccion', 'lat', 'lng', 'telefono', 'horario', 
        'logo', 'color_primario', 'color_secundario', 'km_envio_gratis', 
        'costo_envio_por_km', 'minutos_cancelacion', 'estado'
    ];

    public string $nombre;
    public string $direccion;
    public ?float $lat = null;
    public ?float $lng = null;
    public ?string $telefono = null;
    public ?string $horario = null;
    public ?string $logo = null;
    public string $color_primario;
    public string $color_secundario;
    public float $km_envio_gratis;
    public float $costo_envio_por_km;
    public int $minutos_cancelacion;
    public string $estado;

    public function __construct(array $args = []) {
        $this->id = $args['id'] ?? null;
        $this->nombre = $args['nombre'] ?? '';
        $this->direccion = $args['direccion'] ?? '';
        $this->lat = isset($args['lat']) && is_numeric($args['lat']) ? (float)$args['lat'] : null;
        $this->lng = isset($args['lng']) && is_numeric($args['lng']) ? (float)$args['lng'] : null;
        $this->telefono = $args['telefono'] ?? '';
        $this->horario = $args['horario'] ?? '';
        $this->logo = $args['logo'] ?? '';
        $this->color_primario = $args['color_primario'] ?? '#ea580c';
        $this->color_secundario = $args['color_secundario'] ?? '#1e293b';
        $this->km_envio_gratis = isset($args['km_envio_gratis']) ? (float)$args['km_envio_gratis'] : 3.0;
        $this->costo_envio_por_km = isset($args['costo_envio_por_km']) ? (float)$args['costo_envio_por_km'] : 500.0;
        $this->minutos_cancelacion = isset($args['minutos_cancelacion']) ? (int)$args['minutos_cancelacion'] : 5;
        $this->estado = $args['estado'] ?? 'activo';
    }

    public function sincronizar(array $args = []): void {
        parent::sincronizar($args);
        if(isset($args['lat'])) {
            $this->lat = (is_numeric($args['lat']) && $args['lat'] !== '') ? (float)$args['lat'] : null;
        }
        if(isset($args['lng'])) {
            $this->lng = (is_numeric($args['lng']) && $args['lng'] !== '') ? (float)$args['lng'] : null;
        }
        if(isset($args['km_envio_gratis'])) {
            $this->km_envio_gratis = (float)$args['km_envio_gratis'];
        }
        if(isset($args['costo_envio_por_km'])) {
            $this->costo_envio_por_km = (float)$args['costo_envio_por_km'];
        }
        if(isset($args['minutos_cancelacion'])) {
            $this->minutos_cancelacion = (int)$args['minutos_cancelacion'];
        }
    }

    public function setLogo(string $logo): void {
        if(!empty($this->logo) && $this->logo !== $logo) {
            $rutaAnterior = CARPETA_IMAGENES . $this->logo;
            if(file_exists($rutaAnterior) && is_file($rutaAnterior)) {
                @unlink($rutaAnterior);
            }
        }
        $this->logo = $logo;
    }

    public function validar(): array {
        self::$alertas = [];
        if(empty($this->nombre)) {
            self::$alertas['error'][] = 'El nombre del comercio es obligatorio';
        }
        if(empty($this->direccion)) {
            self::$alertas['error'][] = 'La dirección del local es obligatoria';
        }
        if($this->lat !== null && ($this->lat < -90 || $this->lat > 90)) {
            self::$alertas['error'][] = 'La latitud seleccionada no es válida';
        }
        if($this->lng !== null && ($this->lng < -180 || $this->lng > 180)) {
            self::$alertas['error'][] = 'La longitud seleccionada no es válida';
        }
        if($this->km_envio_gratis < 0) {
            self::$alertas['error'][] = 'El radio de envío gratis no puede ser negativo';
        }
        if($this->costo_envio_por_km < 0) {
            self::$alertas['error'][] = 'El costo de envío por km no puede ser negativo';
        }
        if($this->minutos_cancelacion < 0) {
            self::$alertas['error'][] = 'Los minutos de cancelación deben ser iguales o mayores a cero';
        }
        return self::$alertas;
    }
}
