<?php

namespace Model;

class Comprobante extends ActiveRecord {
    protected static string $tabla = 'comprobantes';
    protected static array $columnasDB = [
        'id', 'comercio_id', 'pedido_id', 'numero_correlativo', 'fecha_emision', 'total'
    ];

    public int $comercio_id;
    public int $pedido_id;
    public int $numero_correlativo;
    public ?string $fecha_emision = null;
    public float $total;

    public function __construct(array $args = []) {
        $this->id = $args['id'] ?? null;
        $this->comercio_id = isset($args['comercio_id']) ? (int)$args['comercio_id'] : 1;
        $this->pedido_id = isset($args['pedido_id']) ? (int)$args['pedido_id'] : 0;
        $this->numero_correlativo = isset($args['numero_correlativo']) ? (int)$args['numero_correlativo'] : 0;
        $this->fecha_emision = $args['fecha_emision'] ?? null;
        $this->total = isset($args['total']) ? (float)$args['total'] : 0.0;
    }

    public function validar(): array {
        self::$alertas = [];

        if(empty($this->comercio_id)) {
            self::$alertas['error'][] = 'El comercio es obligatorio';
        }

        if(empty($this->pedido_id)) {
            self::$alertas['error'][] = 'El pedido es obligatorio para emitir el comprobante';
        }

        if($this->numero_correlativo <= 0) {
            self::$alertas['error'][] = 'El número correlativo debe ser mayor a cero';
        }

        if($this->total <= 0) {
            self::$alertas['error'][] = 'El total del comprobante debe ser mayor a cero';
        }

        return self::$alertas;
    }

    /**
     * Busca el comprobante emitido para un pedido
     */
    public static function porPedido(int $pedidoId): ?self {
        $query = "SELECT * FROM " . static::$tabla . " WHERE pedido_id = {$pedidoId} LIMIT 1";
        $resultado = static::consultarSQL($query);
        return array_shift($resultado) ?? null;
    }

    /**
     * Genera un comprobante calculando el siguiente número correlativo atómicamente por comercio
     */
    public static function generar(int $comercioId, int $pedidoId, float $total): self {
        // Consultar el último número correlativo para este comercio
        $queryMax = "SELECT COALESCE(MAX(numero_correlativo), 0) AS max_correlativo 
                     FROM " . static::$tabla . " 
                     WHERE comercio_id = {$comercioId}";
        $resMax = self::$db->query($queryMax);
        $rowMax = $resMax ? $resMax->fetch_assoc() : null;
        $siguienteNumero = ((int)($rowMax['max_correlativo'] ?? 0)) + 1;

        $comprobante = new self([
            'comercio_id' => $comercioId,
            'pedido_id' => $pedidoId,
            'numero_correlativo' => $siguienteNumero,
            'total' => $total
        ]);

        $comprobante->crear();
        return $comprobante;
    }

    /**
     * Formatea el código correlativo de comprobante (ej. C-0001-00000005)
     */
    public function codigoFormateado(): string {
        $sucursal = str_pad((string)$this->comercio_id, 4, '0', STR_PAD_LEFT);
        $numero = str_pad((string)$this->numero_correlativo, 8, '0', STR_PAD_LEFT);
        return "C-{$sucursal}-{$numero}";
    }

    /**
     * Retorna el pedido vinculado
     */
    public function pedido(): ?Pedido {
        return Pedido::find((int)$this->pedido_id);
    }
}
