<?php

namespace Controllers;

use MVC\Router;
use Model\Pedido;
use Model\PedidoRepartidor;
use Model\Pago;

class RepartidorController {
    /**
     * Consola móvil del repartidor
     */
    public static function index(Router $router): void {
        isRepartidor();

        $repartidorId = (int)($_SESSION['id'] ?? 0);

        // 1. Pedidos en curso asignados a este repartidor
        $pedidosEnCurso = Pedido::asignadosARepartidor($repartidorId, true);

        // 2. Historial de pedidos entregados hoy por este repartidor
        $pedidosEntregadosHoy = Pedido::asignadosARepartidor($repartidorId, false);

        // 3. Efectivo en mano pendiente de rendición
        $pagosPendientes = Pago::porRendirRepartidor($repartidorId);
        $totalPorRendir = 0.0;
        foreach($pagosPendientes as $p) {
            $totalPorRendir += (float)$p->monto;
        }

        $comercio = obtenerComercio();

        $router->render('repartidor/index', [
            'titulo' => 'Consola de Repartos',
            'pedidosEnCurso' => $pedidosEnCurso,
            'pedidosEntregadosHoy' => $pedidosEntregadosHoy,
            'totalPorRendir' => $totalPorRendir,
            'nombreComercio' => $comercio->nombre ?? 'Comercio',
            'tipo' => 'repartidor'
        ]);
    }

    /**
     * Confirmar la entrega de un pedido en mano del cliente
     */
    public static function confirmarEntrega(): void {
        isRepartidor();

        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /repartidor');
            exit;
        }

        $repartidorId = (int)($_SESSION['id'] ?? 0);
        $pedidoId = (int)($_POST['pedido_id'] ?? 0);

        $pedido = Pedido::find($pedidoId);
        if(!$pedido || (int)$pedido->comercio_id !== 1 || $pedido->estado !== 'en_camino') {
            $_SESSION['alerta_rapida'] = ['tipo' => 'error', 'mensaje' => 'El pedido no está disponible para confirmar entrega'];
            header('Location: /repartidor');
            exit;
        }

        $asig = $pedido->asignacionRepartidor();
        if(!$asig || (int)$asig->repartidor_id !== $repartidorId) {
            $_SESSION['alerta_rapida'] = ['tipo' => 'error', 'mensaje' => 'No tienes asignado este pedido'];
            header('Location: /repartidor');
            exit;
        }

        $exito = $pedido->marcarEntregado();

        if($exito) {
            $_SESSION['alerta_rapida'] = [
                'tipo' => 'exito',
                'mensaje' => "¡Pedido #{$pedido->id} entregado con éxito a {$pedido->nombre_contacto}!"
            ];
        } else {
            $_SESSION['alerta_rapida'] = [
                'tipo' => 'error',
                'mensaje' => "Hubo un error al confirmar la entrega del Pedido #{$pedido->id}"
            ];
        }

        header('Location: /repartidor');
        exit;
    }
}
