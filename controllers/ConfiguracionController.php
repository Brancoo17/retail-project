<?php

namespace Controllers;

use MVC\Router;
use Model\Comercio;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager as Image;

class ConfiguracionController {

    public static function index(Router $router): void {
        isDueno();

        $comercio = obtenerComercio();
        $resultado = $_GET['resultado'] ?? null;
        $alertas = [];

        if($resultado === '1') {
            Comercio::setAlerta('exito', 'Configuración del comercio y parámetros de delivery actualizados correctamente');
        }

        $alertas = Comercio::getAlertas();

        $router->render('admin/configuracion/index', [
            'titulo' => 'Configuración del Comercio',
            'comercio' => $comercio,
            'alertas' => $alertas,
            'resultado' => $resultado,
            'tieneMapa' => true,
            'tipo' => 'admin'
        ]);
    }

    public static function guardar(Router $router): void {
        isDueno();

        $comercio = obtenerComercio();
        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $comercio->sincronizar($_POST['comercio'] ?? []);

            $imgLogo = null;
            $nombreLogo = '';

            try {
                if(!empty($_FILES['logo']['tmp_name']) && is_uploaded_file($_FILES['logo']['tmp_name'])) {
                    $manager = new Image(new Driver());
                    $contenido = file_get_contents($_FILES['logo']['tmp_name']);
                    $imgLogo = $manager->decode($contenido)->scaleDown(500, 500);
                    $nombreLogo = 'logo_' . md5(uniqid((string)rand(), true)) . '.webp';
                }
            } catch (\Throwable $e) {
                Comercio::setAlerta('error', 'Error al procesar la imagen del logo: ' . $e->getMessage());
            }

            $alertas = array_merge(Comercio::getAlertas(), $comercio->validar());

            if(empty($alertas)) {
                try {
                    if(!is_dir(CARPETA_IMAGENES)) {
                        mkdir(CARPETA_IMAGENES, 0777, true);
                    }

                    if($imgLogo && $nombreLogo) {
                        $imgLogo->save(CARPETA_IMAGENES . $nombreLogo);
                        $comercio->setLogo($nombreLogo);
                    }

                    $resultado = $comercio->guardar();

                    if($resultado) {
                        header('Location: /admin/configuracion?resultado=1');
                        exit;
                    }
                } catch (\Throwable $e) {
                    Comercio::setAlerta('error', 'Error al guardar cambios: ' . $e->getMessage());
                    $alertas = Comercio::getAlertas();
                }
            }
        }

        $router->render('admin/configuracion/index', [
            'titulo' => 'Configuración del Comercio',
            'comercio' => $comercio,
            'alertas' => $alertas,
            'resultado' => null,
            'tieneMapa' => true,
            'tipo' => 'admin'
        ]);
    }
}
