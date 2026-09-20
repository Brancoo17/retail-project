<?php

namespace Controllers;

use MVC\Router;
use Model\Usuario;
use Model\Comercio;
use Classes\Email;

class LoginController {

    public static function login(Router $router): void {
        $alertas = [];

        if(isset($_GET['resultado']) && $_GET['resultado'] === '1') {
            Usuario::setAlerta('exito', 'Contraseña reestablecida correctamente. Ya puedes iniciar sesión.');
        }

        $auth = new Usuario();

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $auth = new Usuario($_POST);
            $alertas = $auth->validarLogin();

            if(empty($alertas)) {
                $emailLimpio = strtolower(trim($_POST['email'] ?? ''));
                $usuario = Usuario::where('email', $emailLimpio);

                if($usuario) {
                    if($usuario->comprobarPasswordAndVerificado($auth->password)) {
                        session_regenerate_id(true);
                        $_SESSION['id'] = $usuario->id;
                        $_SESSION['comercio_id'] = $usuario->comercio_id;
                        $_SESSION['nombre'] = $usuario->nombre;
                        $_SESSION['email'] = $usuario->email;
                        $_SESSION['rol'] = $usuario->rol;
                        $_SESSION['login'] = true;

                        // Redirección inteligente por rol
                        match($usuario->rol) {
                            'dueño' => header('Location: /admin'),
                            'empleado' => header('Location: /cocina'),
                            'repartidor' => header('Location: /repartidor'),
                            default => header('Location: /cliente')
                        };
                        exit;
                    }
                } else {
                    Usuario::setAlerta('error', 'El usuario no existe o las credenciales son incorrectas');
                }
            }
        }

        $alertas = Usuario::getAlertas();

        $router->render('auth/login', [
            'titulo' => 'Iniciar Sesión',
            'auth' => $auth,
            'alertas' => $alertas,
            'tipo' => 'auth'
        ]);
    }

    public static function logout(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }
            session_destroy();
        }
        header('Location: /');
        exit;
    }

    public static function crear(Router $router): void {
        $alertas = [];
        $usuario = new Usuario();

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario->sincronizar($_POST);
            $usuario->rol = 'cliente';
            $usuario->confirmado = 0;
            $usuario->comercio_id = 1; // Comercio activo actual

            $alertas = $usuario->validarNuevaCuenta();

            if(empty($alertas)) {
                if(!$usuario->existeUsuario(1)) {
                    $usuario->hashPassword();
                    $usuario->crearToken();

                    $resultado = $usuario->crear();

                    if($resultado['resultado']) {
                        $email = new Email($usuario->email, $usuario->nombre, $usuario->token);
                        $email->enviarConfirmacion();

                        header('Location: /mensaje');
                        exit;
                    } else {
                        Usuario::setAlerta('error', 'Ocurrió un error al guardar tu cuenta. Intenta de nuevo.');
                    }
                }
            }
        }

        $alertas = Usuario::getAlertas();

        $router->render('auth/crear-cuenta', [
            'titulo' => 'Crear Cuenta',
            'usuario' => $usuario,
            'alertas' => $alertas,
            'tipo' => 'auth'
        ]);
    }

    public static function mensaje(Router $router): void {
        $router->render('auth/mensaje', [
            'titulo' => 'Confirma tu Cuenta',
            'tipo' => 'auth'
        ]);
    }

    public static function confirmar(Router $router): void {
        $alertas = [];
        $token = s($_GET['token'] ?? '');

        if(!$token) {
            Usuario::setAlerta('error', 'Token no válido o inexistente');
        } else {
            $usuario = Usuario::where('token', $token);

            if(!$usuario) {
                Usuario::setAlerta('error', 'Token no válido o vencido');
            } else {
                $usuario->confirmado = 1;
                $usuario->token = null;
                $usuario->actualizar();
                Usuario::setAlerta('exito', '¡Cuenta confirmada correctamente! Ya puedes iniciar sesión.');
            }
        }

        $alertas = Usuario::getAlertas();

        $router->render('auth/confirmar', [
            'titulo' => 'Confirmación de Cuenta',
            'alertas' => $alertas,
            'tipo' => 'auth'
        ]);
    }

    public static function olvide(Router $router): void {
        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $auth = new Usuario($_POST);
            $alertas = $auth->validarEmail();

            if(empty($alertas)) {
                $emailLimpio = strtolower(trim($_POST['email'] ?? ''));
                $usuario = Usuario::where('email', $emailLimpio);

                if($usuario && (int)$usuario->confirmado === 1) {
                    $usuario->crearToken();
                    $usuario->actualizar();

                    $email = new Email($usuario->email, $usuario->nombre, $usuario->token);
                    $email->enviarInstrucciones();

                    Usuario::setAlerta('exito', 'Hemos enviado las instrucciones a tu casilla de correo');
                } else {
                    Usuario::setAlerta('error', 'El usuario no existe o la cuenta aún no está confirmada');
                }
            }
        }

        $alertas = Usuario::getAlertas();

        $router->render('auth/olvide', [
            'titulo' => 'Recuperar Contraseña',
            'alertas' => $alertas,
            'tipo' => 'auth'
        ]);
    }

    public static function recuperar(Router $router): void {
        $alertas = [];
        $token = s($_GET['token'] ?? '');
        $error = false;

        if(!$token) {
            Usuario::setAlerta('error', 'Token no válido');
            $error = true;
        } else {
            $usuario = Usuario::where('token', $token);
            if(!$usuario) {
                Usuario::setAlerta('error', 'Token no válido o expirado');
                $error = true;
            }
        }

        if($_SERVER['REQUEST_METHOD'] === 'POST' && !$error && isset($usuario)) {
            $nuevoPassword = new Usuario($_POST);
            $alertas = $nuevoPassword->validarPassword();

            if(empty($alertas)) {
                $usuario->password = $nuevoPassword->password;
                $usuario->hashPassword();
                $usuario->token = null;

                $resultado = $usuario->actualizar();

                if($resultado) {
                    header('Location: /login?resultado=1');
                    exit;
                }
            }
        }

        $alertas = Usuario::getAlertas();

        $router->render('auth/recuperar', [
            'titulo' => 'Reestablecer Contraseña',
            'alertas' => $alertas,
            'error' => $error,
            'tipo' => 'auth'
        ]);
    }
}
