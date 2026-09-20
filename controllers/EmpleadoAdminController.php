<?php

namespace Controllers;

use MVC\Router;
use Model\Usuario;

class EmpleadoAdminController {
    /**
     * Listado general del personal de cocina y caja del comercio
     */
    public static function index(Router $router): void {
        isDueno();

        $comercioId = 1;
        $empleados = Usuario::empleadosPorComercio($comercioId);

        $router->render('admin/empleados/index', [
            'titulo' => 'Personal de Cocina & Caja',
            'empleados' => $empleados,
            'tipo' => 'admin'
        ]);
    }

    /**
     * Alta de un nuevo empleado de cocina/caja
     */
    public static function crear(Router $router): void {
        isDueno();

        $comercioId = 1;
        $empleado = new Usuario();
        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $empleado->sincronizar($_POST['empleado'] ?? []);
            $empleado->comercio_id = $comercioId;
            $empleado->rol = 'empleado';
            $empleado->confirmado = isset($_POST['empleado']['confirmado']) ? (int)$_POST['empleado']['confirmado'] : 1;

            $alertas = [];
            if(empty(trim($empleado->nombre))) {
                $alertas['error'][] = 'El nombre del empleado es obligatorio';
            }
            if(empty(trim($empleado->email)) || !filter_var($empleado->email, FILTER_VALIDATE_EMAIL)) {
                $alertas['error'][] = 'El correo electrónico no es válido';
            }
            if(empty(trim($empleado->telefono))) {
                $alertas['error'][] = 'El número de teléfono es obligatorio';
            }
            $passwordPlano = $_POST['empleado']['password'] ?? '';
            if(empty($passwordPlano) || strlen($passwordPlano) < 6) {
                $alertas['error'][] = 'La contraseña debe contener al menos 6 caracteres';
            }

            if(empty($alertas)) {
                if($empleado->existeUsuario($comercioId)) {
                    $alertas = Usuario::getAlertas();
                } else {
                    $empleado->password = $passwordPlano;
                    $empleado->hashPassword();
                    $resultado = $empleado->crear();

                    if($resultado['resultado'] ?? false) {
                        $_SESSION['alerta_rapida'] = [
                            'tipo' => 'exito', 
                            'mensaje' => "Empleado {$empleado->nombre} registrado con éxito con acceso a Cocina y Caja"
                        ];
                        header('Location: /admin/empleados');
                        exit;
                    } else {
                        $alertas['error'][] = 'Hubo un error al guardar el empleado';
                    }
                }
            }
        }

        $router->render('admin/empleados/crear', [
            'titulo' => 'Nuevo Empleado de Cocina / Caja',
            'empleado' => $empleado,
            'alertas' => $alertas,
            'tipo' => 'admin'
        ]);
    }

    /**
     * Edición de un empleado existente
     */
    public static function actualizar(Router $router): void {
        isDueno();

        $id = (int)($_GET['id'] ?? 0);
        $empleado = Usuario::find($id);

        if(!$empleado || $empleado->rol !== 'empleado' || (int)$empleado->comercio_id !== 1) {
            header('Location: /admin/empleados');
            exit;
        }

        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $datos = $_POST['empleado'] ?? [];
            $passwordNuevo = trim($datos['password'] ?? '');

            // Si no desea cambiar la contraseña, conservar la existente
            if(empty($passwordNuevo)) {
                unset($datos['password']);
            }

            $empleadoOriginalEmail = $empleado->email;
            $empleado->sincronizar($datos);

            if(empty(trim($empleado->nombre))) {
                $alertas['error'][] = 'El nombre del empleado es obligatorio';
            }
            if(empty(trim($empleado->email)) || !filter_var($empleado->email, FILTER_VALIDATE_EMAIL)) {
                $alertas['error'][] = 'El correo electrónico no es válido';
            }
            if(empty(trim($empleado->telefono))) {
                $alertas['error'][] = 'El número de teléfono es obligatorio';
            }

            // Si cambió el email, comprobar que no esté en uso
            if($empleado->email !== $empleadoOriginalEmail && $empleado->existeUsuario(1)) {
                $alertas = Usuario::getAlertas();
            }

            // Validar longitud si ingresó nueva contraseña
            if(!empty($passwordNuevo)) {
                if(strlen($passwordNuevo) < 6) {
                    $alertas['error'][] = 'La nueva contraseña debe contener al menos 6 caracteres';
                } else {
                    $empleado->password = $passwordNuevo;
                    $empleado->hashPassword();
                }
            }

            if(empty($alertas)) {
                $resultado = $empleado->actualizar();
                if($resultado) {
                    $_SESSION['alerta_rapida'] = [
                        'tipo' => 'exito', 
                        'mensaje' => "Datos de {$empleado->nombre} actualizados con éxito"
                    ];
                    header('Location: /admin/empleados');
                    exit;
                } else {
                    $alertas['error'][] = 'Hubo un error al actualizar los datos del empleado';
                }
            }
        }

        $router->render('admin/empleados/actualizar', [
            'titulo' => 'Editar Personal de Cocina / Caja',
            'empleado' => $empleado,
            'alertas' => $alertas,
            'tipo' => 'admin'
        ]);
    }

    /**
     * Alternar estado Activo / Inactivo del empleado de cocina
     */
    public static function eliminar(): void {
        isDueno();

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $empleado = Usuario::find($id);

            if($empleado && $empleado->rol === 'empleado' && (int)$empleado->comercio_id === 1) {
                // Alternar estado confirmado
                $empleado->confirmado = ((int)$empleado->confirmado === 1) ? 0 : 1;
                $empleado->actualizar();

                $estadoTexto = ((int)$empleado->confirmado === 1) ? 'activada' : 'desactivada';
                $_SESSION['alerta_rapida'] = [
                    'tipo' => 'exito', 
                    'mensaje' => "La cuenta de {$empleado->nombre} ha sido {$estadoTexto} exitosamente"
                ];
            }
        }

        header('Location: /admin/empleados');
        exit;
    }
}
