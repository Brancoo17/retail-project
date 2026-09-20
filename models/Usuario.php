<?php

namespace Model;

class Usuario extends ActiveRecord {
    protected static string $tabla = 'usuarios';
    protected static array $columnasDB = [
        'id', 'comercio_id', 'nombre', 'email', 'password', 'telefono', 'rol', 'token', 'confirmado'
    ];

    public int $comercio_id;
    public string $nombre;
    public string $email;
    public string $password;
    public string $telefono;
    public string $rol;
    public ?string $token = null;
    public int $confirmado;

    // Métricas y propiedades operativas en tiempo de ejecución
    public int $pedidos_en_curso = 0;
    public int $pedidos_entregados_hoy = 0;
    public int $total_entregados = 0;
    public float $total_por_rendir = 0.0;

    public function __construct(array $args = []) {
        $this->id = $args['id'] ?? null;
        $this->comercio_id = isset($args['comercio_id']) ? (int)$args['comercio_id'] : 1;
        $this->nombre = $args['nombre'] ?? '';
        $this->email = !empty($args['email']) ? strtolower(trim((string)$args['email'])) : '';
        $this->password = $args['password'] ?? '';
        $this->telefono = !empty($args['telefono']) ? trim((string)$args['telefono']) : '';
        $this->rol = $args['rol'] ?? 'cliente';
        $this->token = $args['token'] ?? null;
        $this->confirmado = isset($args['confirmado']) ? (int)$args['confirmado'] : 0;
    }

    public function sincronizar(array $args = []): void {
        parent::sincronizar($args);
        if(isset($args['email'])) {
            $this->email = strtolower(trim((string)$args['email']));
        }
        if(isset($args['telefono'])) {
            $this->telefono = trim((string)$args['telefono']);
        }
    }

    public function validarLogin(): array {
        self::$alertas = [];
        if(empty($this->email)) {
            self::$alertas['error'][] = 'El email es obligatorio';
        } elseif(!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            self::$alertas['error'][] = 'El formato de email no es válido';
        }
        if(empty($this->password)) {
            self::$alertas['error'][] = 'El password es obligatorio';
        }
        return self::$alertas;
    }

    public function validarNuevaCuenta(): array {
        self::$alertas = [];
        if(empty($this->nombre)) {
            self::$alertas['error'][] = 'El nombre es obligatorio';
        }
        if(empty($this->email)) {
            self::$alertas['error'][] = 'El email es obligatorio';
        } elseif(!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            self::$alertas['error'][] = 'El formato de email no es válido';
        }
        if(empty($this->telefono)) {
            self::$alertas['error'][] = 'El teléfono de contacto es obligatorio';
        }
        if(empty($this->password)) {
            self::$alertas['error'][] = 'El password es obligatorio';
        } elseif(strlen($this->password) < 6) {
            self::$alertas['error'][] = 'El password debe tener al menos 6 caracteres';
        }
        return self::$alertas;
    }

    public function validarEmail(): array {
        self::$alertas = [];
        if(empty($this->email)) {
            self::$alertas['error'][] = 'El email es obligatorio';
        } elseif(!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            self::$alertas['error'][] = 'El formato de email no es válido';
        }
        return self::$alertas;
    }

    public function validarPassword(): array {
        self::$alertas = [];
        if(empty($this->password)) {
            self::$alertas['error'][] = 'El password es obligatorio';
        } elseif(strlen($this->password) < 6) {
            self::$alertas['error'][] = 'El password debe contener al menos 6 caracteres';
        }
        return self::$alertas;
    }

    public function existeUsuario(int $comercioId = 1): bool {
        if(empty($this->email)) {
            return false;
        }
        $emailSeguro = self::$db->escape_string($this->email);
        $query = "SELECT id FROM " . self::$tabla . " WHERE email = '{$emailSeguro}' AND comercio_id = {$comercioId}";
        if(!empty($this->id)) {
            $query .= " AND id != " . (int)$this->id;
        }
        $query .= " LIMIT 1";
        $resultado = self::$db->query($query);

        if($resultado && $resultado->num_rows > 0) {
            self::$alertas['error'][] = 'Ya existe un usuario registrado con ese email';
            return true;
        }
        return false;
    }

    public function hashPassword(): void {
        $this->password = password_hash($this->password, PASSWORD_BCRYPT);
    }

    public function crearToken(): void {
        $this->token = bin2hex(random_bytes(32));
    }

    public function comprobarPasswordAndVerificado(string $password): bool {
        $resultado = password_verify($password, $this->password);

        if(!$resultado) {
            self::$alertas['error'][] = 'Contraseña incorrecta';
            return false;
        }

        if(!(int)$this->confirmado) {
            self::$alertas['error'][] = 'Tu cuenta aún no ha sido confirmada. Revisa tu casilla de correo';
            return false;
        }

        return true;
    }

    /**
     * Obtiene empleados (cocina & caja) de un comercio
     */
    public static function empleadosPorComercio(int $comercioId = 1, bool $soloConfirmados = false): array {
        $query = "SELECT * FROM " . static::$tabla . " WHERE comercio_id = {$comercioId} AND rol = 'empleado'";
        if($soloConfirmados) {
            $query .= " AND confirmado = 1";
        }
        $query .= " ORDER BY id DESC";
        return static::consultarSQL($query);
    }

    /**
     * Obtiene repartidores de un comercio
     */
    public static function repartidoresPorComercio(int $comercioId = 1, bool $soloConfirmados = false): array {
        $query = "SELECT * FROM " . static::$tabla . " WHERE comercio_id = {$comercioId} AND rol = 'repartidor'";
        if($soloConfirmados) {
            $query .= " AND confirmado = 1";
        }
        $query .= " ORDER BY id DESC";
        return static::consultarSQL($query);
    }

    /**
     * Cantidad de pedidos entregados hoy por este repartidor
     */
    public function pedidosEntregadosHoy(): int {
        $query = "SELECT COUNT(*) as total FROM pedido_repartidor 
                  WHERE repartidor_id = " . (int)$this->id . " 
                    AND estado = 'entregado' 
                    AND DATE(hora_entregado) = CURDATE()";
        $res = self::$db->query($query);
        if($res && $fila = $res->fetch_assoc()) {
            return (int)$fila['total'];
        }
        return 0;
    }

    /**
     * Cantidad total histórica de pedidos entregados por este repartidor
     */
    public function totalPedidosEntregados(): int {
        $query = "SELECT COUNT(*) as total FROM pedido_repartidor 
                  WHERE repartidor_id = " . (int)$this->id . " 
                    AND estado = 'entregado'";
        $res = self::$db->query($query);
        if($res && $fila = $res->fetch_assoc()) {
            return (int)$fila['total'];
        }
        return 0;
    }

    /**
     * Cantidad de pedidos actualmente en curso asignados a este repartidor
     */
    public function pedidosEnCurso(): int {
        $query = "SELECT COUNT(*) as total FROM pedido_repartidor pr 
                  INNER JOIN pedidos p ON pr.pedido_id = p.id 
                  WHERE pr.repartidor_id = " . (int)$this->id . " 
                    AND p.estado = 'en_camino'";
        $res = self::$db->query($query);
        if($res && $fila = $res->fetch_assoc()) {
            return (int)$fila['total'];
        }
        return 0;
    }

    /**
     * Total de efectivo en mano pendiente de rendición por este repartidor
     */
    public function totalPorRendir(): float {
        $pagos = Pago::porRendirRepartidor((int)$this->id);
        $total = 0.0;
        foreach($pagos as $p) {
            $total += (float)$p->monto;
        }
        return $total;
    }
}
