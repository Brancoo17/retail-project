<?php

namespace Controllers;

use MVC\Router;
use Model\Comercio;
use Model\Categoria;
use Model\Producto;

class PaginasController {
    public static function index(Router $router): void {
        $comercio = Comercio::find(1);
        if(!$comercio) {
            $comercio = new Comercio([
                'nombre' => 'Retail Gastronomía',
                'direccion' => 'Av. Corrientes 1234, CABA',
                'telefono' => '1122334455',
                'horario' => 'Miércoles a Domingos de 19:30 a 00:30',
                'km_envio_gratis' => 3.0,
                'costo_envio_por_km' => 500.0
            ]);
        }

        $categorias = Categoria::porComercio(1);
        $todosProductos = Producto::porComercio(1, null, true);
        $destacados = array_slice($todosProductos, 0, 6);

        $router->render('paginas/index', [
            'titulo' => $comercio->nombre ? "{$comercio->nombre} - Sabores Auténticos & Delivery Rápido" : 'Sabores Auténticos & Delivery Rápido',
            'comercio' => $comercio,
            'categorias' => $categorias,
            'destacados' => $destacados
        ]);
    }
}

