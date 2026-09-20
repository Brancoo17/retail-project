<?php

namespace Controllers;

use MVC\Router;
use Model\Comprobante;
use Model\Pedido;
use Model\Pago;
use Model\Comercio;

class ComprobanteController {

    /**
     * Vista de ticket / comprobante interno imprimible
     * Endpoint: GET /comprobante?id=X o GET /comprobante?pedido=X
     */
    public static function ver(Router $router): void {
        $id = (int)($_GET['id'] ?? 0);
        $pedidoId = (int)($_GET['pedido'] ?? 0);

        $comprobante = null;
        if($id > 0) {
            $comprobante = Comprobante::find($id);
        } elseif($pedidoId > 0) {
            $comprobante = Comprobante::porPedido($pedidoId);
        }

        if(!$comprobante) {
            header('Location: /');
            exit;
        }

        $pedido = Pedido::find((int)$comprobante->pedido_id);
        if(!$pedido) {
            header('Location: /');
            exit;
        }

        $detalles = $pedido->detalles();
        $pago = Pago::porPedido((int)$pedido->id);
        $comercio = obtenerComercio((int)$comprobante->comercio_id);

        $router->render('comprobantes/ver', [
            'titulo' => "Comprobante {$comprobante->codigoFormateado()}",
            'comprobante' => $comprobante,
            'pedido' => $pedido,
            'detalles' => $detalles,
            'pago' => $pago,
            'comercio' => $comercio,
            'tipo' => 'publico'
        ]);
    }
}
