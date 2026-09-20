<?php

namespace Controllers;

use MVC\Router;
use Model\Aderezo;

class AderezoController {

    public static function index(Router $router): void {
        isDueno();

        $resultado = $_GET['resultado'] ?? null;
        if($resultado === '1') {
            Aderezo::setAlerta('exito', 'Aderezo o salsa registrada con éxito');
        } elseif($resultado === '2') {
            Aderezo::setAlerta('exito', 'Aderezo actualizado correctamente');
        } elseif($resultado === '3') {
            Aderezo::setAlerta('exito', 'Aderezo eliminado correctamente');
        }

        $alertas = Aderezo::getAlertas();
        $aderezos = Aderezo::porComercio(1, false);

        $router->render('admin/aderezos/index', [
            'titulo' => 'Salsas & Aderezos del Menú',
            'aderezos' => $aderezos,
            'alertas' => $alertas,
            'tipo' => 'admin'
        ]);
    }

    public static function crear(): void {
        isDueno();

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = trim($_POST['nombre'] ?? '');
            $orden = (int)($_POST['orden'] ?? 0);

            if(empty($nombre)) {
                Aderezo::setAlerta('error', 'El nombre de la salsa o aderezo es obligatorio');
                $_SESSION['alerta_rapida'] = ['tipo' => 'error', 'mensaje' => 'El nombre del aderezo es obligatorio'];
                header('Location: /admin/aderezos');
                exit;
            }

            $aderezo = new Aderezo([
                'comercio_id' => 1,
                'nombre' => $nombre,
                'activo' => 1,
                'orden' => $orden
            ]);

            $res = $aderezo->guardar();
            if($res) {
                header('Location: /admin/aderezos?resultado=1');
                exit;
            } else {
                header('Location: /admin/aderezos');
                exit;
            }
        }
    }

    public static function actualizar(): void {
        isDueno();

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $aderezo = Aderezo::find($id);

            if(!$aderezo || (int)$aderezo->comercio_id !== 1) {
                header('Location: /admin/aderezos');
                exit;
            }

            if(isset($_POST['toggle_activo'])) {
                // Alternar estado activo / inactivo
                $aderezo->activo = (int)$aderezo->activo === 1 ? 0 : 1;
                $aderezo->guardar();
                header('Location: /admin/aderezos?resultado=2');
                exit;
            }

            // Edición normal
            $nombre = trim($_POST['nombre'] ?? '');
            if(!empty($nombre)) {
                $aderezo->nombre = $nombre;
            }
            if(isset($_POST['orden'])) {
                $aderezo->orden = (int)$_POST['orden'];
            }
            if(isset($_POST['activo'])) {
                $aderezo->activo = (int)$_POST['activo'];
            }

            $aderezo->guardar();
            header('Location: /admin/aderezos?resultado=2');
            exit;
        }
    }

    public static function eliminar(): void {
        isDueno();

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $aderezo = Aderezo::find($id);

            if($aderezo && (int)$aderezo->comercio_id === 1) {
                $aderezo->eliminar();
                header('Location: /admin/aderezos?resultado=3');
                exit;
            }
        }

        header('Location: /admin/aderezos');
        exit;
    }
}
