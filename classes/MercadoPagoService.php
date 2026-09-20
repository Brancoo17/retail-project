<?php

namespace Classes;

use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Client\Payment\PaymentClient;
use Model\Pedido;

class MercadoPagoService {

    private static bool $configurado = false;
    public static string $ultimoError = '';

    /**
     * Inicializa el SDK de Mercado Pago con el Access Token de entorno
     */
    public static function inicializar(): bool {
        if(self::$configurado) return true;

        $token = trim((string)($_ENV['MP_ACCESS_TOKEN'] ?? ''));
        if(empty($token)) {
            self::$ultimoError = 'El MP_ACCESS_TOKEN no se encuentra configurado en las variables de entorno';
            return false;
        }

        try {
            MercadoPagoConfig::setAccessToken($token);

            // En entornos de desarrollo/local, evitar errores de CA cert en Windows
            if(($_ENV['APP_ENV'] ?? 'local') !== 'production') {
                MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);
            }

            self::$configurado = true;
            return true;
        } catch (\Throwable $e) {
            self::$ultimoError = 'Error al configurar Mercado Pago: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * Crea una preferencia de Checkout Pro para un pedido
     * Retorna array con 'id', 'init_point' y 'sandbox_init_point', o null en caso de error
     */
    public static function crearPreferencia(Pedido $pedido, array $detalles): ?array {
        if(!self::inicializar()) {
            return null;
        }

        try {
            $client = new PreferenceClient();
            $appUrl = rtrim((string)($_ENV['APP_URL'] ?? 'http://localhost:3000'), '/');

            $items = [];
            foreach($detalles as $det) {
                $titulo = $det->nombre_producto;
                if(!empty($det->opciones_texto)) {
                    $titulo .= " (" . mb_substr($det->opciones_texto, 0, 80) . ")";
                }

                $items[] = [
                    'id' => (string)$det->producto_id,
                    'title' => mb_substr($titulo, 0, 100),
                    'quantity' => (int)$det->cantidad,
                    'unit_price' => (float)$det->precio_unitario,
                    'currency_id' => 'ARS'
                ];
            }

            // Si hay costo de envío, agregarlo como ítem adicional en la preferencia
            if((float)$pedido->costo_envio > 0) {
                $items[] = [
                    'id' => 'envio-delivery',
                    'title' => 'Costo de Envío a Domicilio',
                    'quantity' => 1,
                    'unit_price' => (float)$pedido->costo_envio,
                    'currency_id' => 'ARS'
                ];
            }

            $solicitud = [
                'items' => $items,
                'payer' => [
                    'name' => $pedido->nombre_contacto,
                    'phone' => [
                        'number' => preg_replace('/[^0-9]/', '', $pedido->telefono_contacto)
                    ]
                ],
                'back_urls' => [
                    'success' => "{$appUrl}/pago/exitoso?id={$pedido->id}",
                    'failure' => "{$appUrl}/pago/fallido?id={$pedido->id}",
                    'pending' => "{$appUrl}/pago/pendiente?id={$pedido->id}"
                ],
                'auto_return' => 'approved',
                'external_reference' => (string)$pedido->id,
                'statement_descriptor' => 'RETAIL DELIVERY',
                'binary_mode' => true // Solo aprobado o rechazado, sin estados intermedios prolongados
            ];

            // Solo añadir notification_url si es HTTPS (Mercado Pago descarta URLs http en webhooks)
            if(str_starts_with($appUrl, 'https://')) {
                $solicitud['notification_url'] = "{$appUrl}/api/webhook/mercadopago";
            }

            $preferencia = $client->create($solicitud);

            return [
                'id' => $preferencia->id,
                'init_point' => $preferencia->init_point,
                'sandbox_init_point' => $preferencia->sandbox_init_point ?? $preferencia->init_point
            ];
        } catch (\Throwable $e) {
            self::$ultimoError = 'Error al crear preferencia de Mercado Pago: ' . $e->getMessage();
            return null;
        }
    }

    /**
     * Consulta el estado de un pago directamente en la API de Mercado Pago por su ID numérico
     */
    public static function obtenerPago(string|int $paymentId): ?object {
        if(!self::inicializar()) {
            return null;
        }

        try {
            $paymentClient = new PaymentClient();
            return $paymentClient->get((int)$paymentId);
        } catch (\Throwable $e) {
            self::$ultimoError = 'Error consultando pago en Mercado Pago: ' . $e->getMessage();
            return null;
        }
    }
}
