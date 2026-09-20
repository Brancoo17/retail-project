<?php

namespace Controllers;

use MVC\Router;
use Model\Insumo;

class AdminController {
    public static function index(Router $router): void {
        isDueno();

        $estadisticasInsumos = Insumo::estadisticas(1);

        $router->render('admin/index', [
            'titulo' => 'Panel de Administración',
            'estadisticasInsumos' => $estadisticasInsumos,
            'tipo' => 'admin'
        ]);
    }
}
