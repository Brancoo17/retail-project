<?php

namespace Controllers;

use MVC\Router;
use Model\Categoria;

class CategoriaController {

    public static function index(Router $router): void {
        isDueno();

        $resultado = $_GET['resultado'] ?? null;
        if($resultado === '1') {
            Categoria::setAlerta('exito', 'Categoría creada con éxito');
        } elseif($resultado === '2') {
            Categoria::setAlerta('exito', 'Categoría actualizada con éxito');
        } elseif($resultado === '3') {
            Categoria::setAlerta('exito', 'Categoría eliminada correctamente');
        }

        $alertas = Categoria::getAlertas();
        $categorias = Categoria::porComercio(1);

        $router->render('admin/categorias/index', [
            'titulo' => 'Categorías del Menú',
            'categorias' => $categorias,
            'alertas' => $alertas,
            'tipo' => 'admin'
        ]);
    }

    public static function crear(Router $router): void {
        isDueno();

        $categoria = new Categoria();
        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $categoria->sincronizar($_POST['categoria'] ?? []);
            $categoria->comercio_id = 1;

            $alertas = $categoria->validar();

            if(empty($alertas)) {
                $resultado = $categoria->crear();
                if($resultado['resultado']) {
                    header('Location: /admin/categorias?resultado=1');
                    exit;
                } else {
                    Categoria::setAlerta('error', 'Error al registrar la categoría');
                    $alertas = Categoria::getAlertas();
                }
            }
        }

        $router->render('admin/categorias/crear', [
            'titulo' => 'Nueva Categoría',
            'categoria' => $categoria,
            'alertas' => $alertas,
            'tipo' => 'admin'
        ]);
    }

    public static function actualizar(Router $router): void {
        isDueno();

        $id = (int)($_GET['id'] ?? 0);
        $categoria = Categoria::find($id);

        if(!$categoria || $categoria->comercio_id !== 1) {
            header('Location: /admin/categorias');
            exit;
        }

        $alertas = [];

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $categoria->sincronizar($_POST['categoria'] ?? []);
            $alertas = $categoria->validar();

            if(empty($alertas)) {
                $resultado = $categoria->actualizar();
                if($resultado) {
                    header('Location: /admin/categorias?resultado=2');
                    exit;
                } else {
                    Categoria::setAlerta('error', 'Error al actualizar la categoría');
                    $alertas = Categoria::getAlertas();
                }
            }
        }

        $router->render('admin/categorias/actualizar', [
            'titulo' => 'Editar Categoría',
            'categoria' => $categoria,
            'alertas' => $alertas,
            'tipo' => 'admin'
        ]);
    }

    public static function eliminar(): void {
        isDueno();

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $categoria = Categoria::find($id);

            if($categoria && $categoria->comercio_id === 1) {
                $categoria->eliminar();
            }
        }

        header('Location: /admin/categorias?resultado=3');
        exit;
    }
}
