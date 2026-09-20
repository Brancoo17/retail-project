<?php

namespace Controllers;

use MVC\Router;
use Model\Pedido;
use Model\DireccionCliente;

class ClienteController {
    public static function index(Router $router): void {
        isCliente();

        $usuarioId = (int)($_SESSION['id'] ?? 0);
        $pedidos = Pedido::porUsuario($usuarioId);
        $direcciones = DireccionCliente::porUsuario($usuarioId);

        $router->render('cliente/index', [
            'titulo' => 'Mi Cuenta y Pedidos',
            'pedidos' => $pedidos,
            'direcciones' => $direcciones,
            'tipo' => 'cliente'
        ]);
    }
}
