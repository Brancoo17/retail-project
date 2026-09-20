<?php

namespace Controllers;

use MVC\Router;
use Model\Pedido;
use Model\Pago;
use Model\Comprobante;
use Classes\MercadoPagoService;

class PagoController {

    /**
     * Webhook oficial de Mercado Pago (recibe notificaciones asíncronas IPN)
     * Endpoint: POST /api/webhook/mercadopago
     */
    public static function webhook(): void {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $query = $_GET;

        $tipo = $input['type'] ?? ($query['topic'] ?? ($input['action'] ?? ''));
        $paymentId = $input['data']['id'] ?? ($query['id'] ?? ($query['data_id'] ?? null));

        if($tipo === 'payment' && $paymentId) {
            $pagoMP = MercadoPagoService::obtenerPago($paymentId);

            if($pagoMP && !empty($pagoMP->external_reference)) {
                $pedidoId = (int)$pagoMP->external_reference;
                $pago = Pago::porPedido($pedidoId);

                if($pago) {
                    $status = $pagoMP->status ?? 'pending';

                    if($status === 'approved') {
                        $pago->aprobar((string)$pagoMP->id, $status);
                    } elseif(in_array($status, ['rejected', 'cancelled'])) {
                        $pago->rechazar((string)$pagoMP->id, $status);
                    }
                }
            }
        }

        // Siempre responder HTTP 200 a Mercado Pago para confirmar recepción
        http_response_code(200);
        echo json_encode(['status' => 'received', 'timestamp' => time()]);
        exit;
    }

    /**
     * Retorno exitoso desde Checkout Pro de Mercado Pago
     * Endpoint: GET /pago/exitoso
     */
    public static function exitoso(): void {
        $pedidoId = (int)($_GET['id'] ?? ($_GET['external_reference'] ?? 0));
        $paymentId = (string)($_GET['payment_id'] ?? ($_GET['collection_id'] ?? ''));
        $status = (string)($_GET['status'] ?? ($_GET['collection_status'] ?? 'approved'));

        if($pedidoId > 0) {
            $pago = Pago::porPedido($pedidoId);
            if($pago) {
                $pago->aprobar($paymentId ?: ('MP-' . time()), $status);
            }
            header("Location: /pedido/seguimiento?id={$pedidoId}&resultado=pago_aprobado");
            exit;
        }

        header('Location: /');
        exit;
    }

    /**
     * Retorno de pago rechazado desde Checkout Pro
     * Endpoint: GET /pago/fallido
     */
    public static function fallido(): void {
        $pedidoId = (int)($_GET['id'] ?? ($_GET['external_reference'] ?? 0));
        $paymentId = (string)($_GET['payment_id'] ?? ($_GET['collection_id'] ?? ''));
        $status = (string)($_GET['status'] ?? 'rejected');

        if($pedidoId > 0) {
            $pago = Pago::porPedido($pedidoId);
            if($pago && $pago->estado !== 'aprobado') {
                $pago->rechazar($paymentId, $status);
            }
            header("Location: /pedido/seguimiento?id={$pedidoId}&resultado=pago_fallido");
            exit;
        }

        header('Location: /');
        exit;
    }

    /**
     * Retorno de pago pendiente (ej. efectivo en Rapipago / Pago Fácil vía MP)
     * Endpoint: GET /pago/pendiente
     */
    public static function pendiente(): void {
        $pedidoId = (int)($_GET['id'] ?? ($_GET['external_reference'] ?? 0));
        if($pedidoId > 0) {
            header("Location: /pedido/seguimiento?id={$pedidoId}&resultado=pago_pendiente");
            exit;
        }

        header('Location: /');
        exit;
    }

    /**
     * Simulador de aprobación de pago para entorno de pruebas / desarrollo
     * Endpoint: POST /pago/simular
     */
    public static function simular(): void {
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $pedidoId = (int)($_POST['pedido_id'] ?? 0);
            $pago = Pago::porPedido($pedidoId);

            if($pago) {
                $paymentIdSimulado = 'SIMULADO-' . date('YmdHis');
                $pago->aprobar($paymentIdSimulado, 'approved');
                header("Location: /pedido/seguimiento?id={$pedidoId}&resultado=pago_aprobado");
                exit;
            }
        }

        header('Location: /');
        exit;
    }

    /**
     * Registro de cobro en efectivo para personal o mostrador
     * Endpoint: POST /pago/cobrar-efectivo
     */
    public static function registrarCobroEfectivo(): void {
        isStaff(); // Solo personal de cocina, caja o dueño

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $pedidoId = (int)($_POST['pedido_id'] ?? 0);
            $origen = $_POST['origen'] ?? 'cocina';
            $pago = Pago::porPedido($pedidoId);

            if($pago) {
                // Si hay sesión de caja activa, asociar el cobro
                $sesion = \Model\CajaSesion::sesionActiva(1);
                if($sesion) {
                    $pago->caja_sesion_id = (int)$sesion->id;
                }
                $pago->rendido = 1;
                $pago->fecha_rendido = date('Y-m-d H:i:s');
                $pago->aprobar('EFECTIVO-MOSTRADOR-' . date('YmdHis'), 'approved');

                if($origen === 'cocina') {
                    $_SESSION['alerta_rapida'] = ['tipo' => 'exito', 'mensaje' => "Cobro de Pedido #{$pedidoId} registrado en mostrador"];
                    header('Location: /cocina');
                    exit;
                } else {
                    header("Location: /pedido/seguimiento?id={$pedidoId}&resultado=pago_aprobado");
                    exit;
                }
            }
        }

        header('Location: /cocina');
        exit;
    }
}
