<?php

namespace Controllers;

use MVC\Router;
use Model\Usuario;

class RepartidorAdminController {
    /**
     * Listado general de repartidores del comercio con métricas
     */
    public static function index(Router $router): void {
        isDueno();

        $comercioId = 1;
        $repartidores = Usuario::repartidoresPorComercio($comercioId);

        foreach($repartidores as $rep) {
            $rep->pedidos_en_curso = $rep->pedidosEnCurso();
            $rep->pedidos_entregados_hoy = $rep->pedidosEntregadosHoy();
            $rep->total_entregados = $rep->totalPedidosEntregados();
            $rep->total_por_rendir = $rep->totalPorRendir();
        }

        $router->render('admin/repartidores/index', [
            'titulo' => 'Equipo de Repartidores',
            'repartidores' => $repartidores,
            'tipo' => 'admin'
        ]);
    }

    /**
     * Alta de un nuevo repartidor
     */
    public static function crear(Router $router): void {
        isDueno();

        $comercioId = 1;
        $repartidor = new Usuario();
        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $repartidor->sincronizar($_POST['repartidor'] ?? []);
            $repartidor->comercio_id = $comercioId;
            $repartidor->rol = 'repartidor';
            $repartidor->confirmado = isset($_POST['repartidor']['confirmado']) ? (int)$_POST['repartidor']['confirmado'] : 1;

            $alertas = [];
            if(empty(trim($repartidor->nombre))) {
                $alertas['error'][] = 'El nombre del repartidor es obligatorio';
            }
            if(empty(trim($repartidor->email)) || !filter_var($repartidor->email, FILTER_VALIDATE_EMAIL)) {
                $alertas['error'][] = 'El correo electrónico no es válido';
            }
            if(empty(trim($repartidor->telefono))) {
                $alertas['error'][] = 'El número de teléfono es obligatorio';
            }
            $passwordPlano = $_POST['repartidor']['password'] ?? '';
            if(empty($passwordPlano) || strlen($passwordPlano) < 6) {
                $alertas['error'][] = 'La contraseña debe contener al menos 6 caracteres';
            }

            if(empty($alertas)) {
                if($repartidor->existeUsuario($comercioId)) {
                    $alertas = Usuario::getAlertas();
                } else {
                    $repartidor->password = $passwordPlano;
                    $repartidor->hashPassword();
                    $resultado = $repartidor->crear();

                    if($resultado['resultado'] ?? false) {
                        $_SESSION['alerta_rapida'] = ['tipo' => 'exito', 'mensaje' => "Repartidor {$repartidor->nombre} registrado con éxito"];
                        header('Location: /admin/repartidores');
                        exit;
                    } else {
                        $alertas['error'][] = 'Hubo un error al guardar el repartidor';
                    }
                }
            }
        }

        $router->render('admin/repartidores/crear', [
            'titulo' => 'Nuevo Repartidor',
            'repartidor' => $repartidor,
            'alertas' => $alertas,
            'tipo' => 'admin'
        ]);
    }

    /**
     * Edición de un repartidor existente
     */
    public static function actualizar(Router $router): void {
        isDueno();

        $id = (int)($_GET['id'] ?? 0);
        $repartidor = Usuario::find($id);

        if(!$repartidor || $repartidor->rol !== 'repartidor' || (int)$repartidor->comercio_id !== 1) {
            header('Location: /admin/repartidores');
            exit;
        }

        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = $_POST['repartidor'] ?? [];
            $passwordAnterior = $repartidor->password;

            $repartidor->nombre = trim((string)($datos['nombre'] ?? ''));
            $repartidor->email = strtolower(trim((string)($datos['email'] ?? '')));
            $repartidor->telefono = trim((string)($datos['telefono'] ?? ''));
            $repartidor->confirmado = isset($datos['confirmado']) ? (int)$datos['confirmado'] : 1;

            if(empty($repartidor->nombre)) {
                $alertas['error'][] = 'El nombre del repartidor es obligatorio';
            }
            if(empty($repartidor->email) || !filter_var($repartidor->email, FILTER_VALIDATE_EMAIL)) {
                $alertas['error'][] = 'El correo electrónico no es válido';
            }
            if(empty($repartidor->telefono)) {
                $alertas['error'][] = 'El número de teléfono es obligatorio';
            }

            // Validar unicidad de email si cambió
            $usuarioExistente = Usuario::where('email', $repartidor->email);
            if($usuarioExistente && (int)$usuarioExistente->id !== (int)$repartidor->id) {
                $alertas['error'][] = 'Ya existe otro usuario registrado con ese correo electrónico';
            }

            // Cambio de contraseña opcional
            $nuevaPassword = $datos['password'] ?? '';
            if(!empty($nuevaPassword)) {
                if(strlen($nuevaPassword) < 6) {
                    $alertas['error'][] = 'La nueva contraseña debe tener al menos 6 caracteres';
                } else {
                    $repartidor->password = $nuevaPassword;
                    $repartidor->hashPassword();
                }
            } else {
                $repartidor->password = $passwordAnterior;
            }

            if(empty($alertas)) {
                $res = $repartidor->guardar();
                $ok = is_array($res) ? (bool)($res['resultado'] ?? false) : (bool)$res;
                if($ok) {
                    $_SESSION['alerta_rapida'] = ['tipo' => 'exito', 'mensaje' => "Datos de {$repartidor->nombre} actualizados con éxito"];
                    header('Location: /admin/repartidores');
                    exit;
                } else {
                    $alertas['error'][] = 'Hubo un error al actualizar el repartidor';
                }
            }
        }

        $router->render('admin/repartidores/actualizar', [
            'titulo' => 'Editar Repartidor',
            'repartidor' => $repartidor,
            'alertas' => $alertas,
            'tipo' => 'admin'
        ]);
    }

    /**
     * Eliminación o baja segura de un repartidor
     */
    public static function eliminar(): void {
        isDueno();

        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/repartidores');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $repartidor = Usuario::find($id);

        if(!$repartidor || $repartidor->rol !== 'repartidor' || (int)$repartidor->comercio_id !== 1) {
            header('Location: /admin/repartidores');
            exit;
        }

        // 1. Validar que no tenga pedidos en camino
        if($repartidor->pedidosEnCurso() > 0) {
            $_SESSION['alerta_rapida'] = [
                'tipo' => 'error',
                'mensaje' => "No se puede dar de baja a {$repartidor->nombre} porque tiene pedidos asignados en camino"
            ];
            header('Location: /admin/repartidores');
            exit;
        }

        // 2. Validar que no tenga efectivo pendiente de rendición
        $pendiente = $repartidor->totalPorRendir();
        if($pendiente > 0) {
            $_SESSION['alerta_rapida'] = [
                'tipo' => 'error',
                'mensaje' => "No se puede dar de baja a {$repartidor->nombre} porque tiene $ " . number_format($pendiente, 2, ',', '.') . " pendiente de rendir en caja"
            ];
            header('Location: /admin/repartidores');
            exit;
        }

        // 3. Si tiene historial de entregas, desactivar en vez de borrar físico para conservar integridad
        if($repartidor->totalPedidosEntregados() > 0) {
            $repartidor->confirmado = 0;
            $repartidor->guardar();
            $_SESSION['alerta_rapida'] = [
                'tipo' => 'exito',
                'mensaje' => "Repartidor {$repartidor->nombre} desactivado (conserva historial de entregas)"
            ];
        } else {
            $repartidor->eliminar();
            $_SESSION['alerta_rapida'] = [
                'tipo' => 'exito',
                'mensaje' => "Repartidor {$repartidor->nombre} eliminado correctamente"
            ];
        }

        header('Location: /admin/repartidores');
        exit;
    }
}
