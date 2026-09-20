<?php

namespace Controllers;

use MVC\Router;
use Model\Usuario;

class CuentaController {
    /**
     * Vista y actualización del perfil del usuario autenticado (dueño, empleado, repartidor, cliente)
     */
    public static function index(Router $router): void {
        isAuth();

        $usuarioId = (int)($_SESSION['id'] ?? 0);
        $usuario = Usuario::find($usuarioId);

        if(!$usuario) {
            header('Location: /login');
            exit;
        }

        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = trim($_POST['nombre'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $passwordActual = $_POST['password_actual'] ?? '';
            $passwordNuevo = $_POST['password_nuevo'] ?? '';
            $passwordConfirmar = $_POST['password_confirmar'] ?? '';

            if(empty($nombre)) {
                $alertas['error'][] = 'El nombre es obligatorio';
            }
            if(empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $alertas['error'][] = 'El correo electrónico no es válido';
            }

            // Validar unicidad de email si cambió
            $usuario->email = $email;
            if($usuario->existeUsuario(1)) {
                $alertas = array_merge($alertas, Usuario::getAlertas());
            }

            // Cambio opcional de contraseña
            $cambiarPassword = false;
            if(!empty($passwordNuevo) || !empty($passwordConfirmar) || !empty($passwordActual)) {
                if(empty($passwordActual)) {
                    $alertas['error'][] = 'Debes ingresar tu contraseña actual para establecer una nueva';
                } elseif(!password_verify($passwordActual, $usuario->password)) {
                    $alertas['error'][] = 'La contraseña actual ingresada es incorrecta';
                } elseif(strlen($passwordNuevo) < 6) {
                    $alertas['error'][] = 'La nueva contraseña debe tener al menos 6 caracteres';
                } elseif($passwordNuevo !== $passwordConfirmar) {
                    $alertas['error'][] = 'Las nuevas contraseñas no coinciden';
                } else {
                    $cambiarPassword = true;
                }
            }

            if(empty($alertas)) {
                $usuario->nombre = $nombre;
                $usuario->telefono = $telefono;
                if($cambiarPassword) {
                    $usuario->password = $passwordNuevo;
                    $usuario->hashPassword();
                }

                $resultado = $usuario->actualizar();

                if($resultado) {
                    // Actualizar variables de sesión activas
                    $_SESSION['nombre'] = $usuario->nombre;
                    $_SESSION['email'] = $usuario->email;

                    $_SESSION['alerta_rapida'] = [
                        'tipo' => 'exito',
                        'mensaje' => 'Tus datos de perfil han sido actualizados correctamente'
                    ];

                    if(!headers_sent()) {
                        header('Location: /cuenta');
                    }
                    if(php_sapi_name() !== 'cli') {
                        exit;
                    }
                    return;
                } else {
                    $alertas['error'][] = 'Hubo un error al guardar los cambios';
                }
            }
        }

        $router->render('cuenta/index', [
            'titulo' => 'Mi Cuenta - Configuración de Perfil',
            'usuario' => $usuario,
            'alertas' => $alertas,
            'tipo' => 'admin'
        ]);
    }
}
