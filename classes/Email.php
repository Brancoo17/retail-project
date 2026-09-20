<?php

namespace Classes;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Model\Comercio;

class Email {

    public string $email;
    public string $nombre;
    public string $token;
    public string $error = '';

    public function __construct(string $email, string $nombre, string $token = '') {
        $this->email = $email;
        $this->nombre = $nombre;
        $this->token = $token;
    }

    private function configurar(PHPMailer $mail): void {
        $mailHost = $_ENV['MAIL_HOST'] ?? 'sandbox.smtp.mailtrap.io';
        $mailPort = (int)($_ENV['MAIL_PORT'] ?? 2525);
        $mailUser = $_ENV['MAIL_USER'] ?? '';
        $mailPass = $_ENV['MAIL_PASS'] ?? '';
        $mailFrom = $_ENV['MAIL_FROM'] ?? 'pedidos@retail.com';
        $mailEncryption = $_ENV['MAIL_ENCRYPTION'] ?? 'tls';

        $comercio = Comercio::find(1);
        $nombreRemitente = $comercio->nombre ?? 'Retail Delivery';

        $mail->isSMTP();
        $mail->Host = $mailHost;
        $mail->SMTPAuth = !empty($mailUser);
        $mail->Username = $mailUser;
        $mail->Password = $mailPass;
        $mail->SMTPSecure = ($mailPort === 465) ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $mailPort;
        $mail->setFrom($mailFrom, $nombreRemitente);
        $mail->addAddress($this->email, $this->nombre);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
    }

    public function enviarConfirmacion(): bool {
        try {
            $comercio = Comercio::find(1);
            $nombreComercio = $comercio->nombre ?? 'Retail Delivery';
            $appUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost:3000', '/');

            $mail = new PHPMailer(true);
            $this->configurar($mail);
            $mail->Subject = 'Confirma tu cuenta en ' . $nombreComercio;

            $enlace = $appUrl . '/confirmar-cuenta?token=' . urlencode($this->token);

            $contenido = '<div style="font-family: Arial, sans-serif; color: #1e293b; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 12px; background-color: #ffffff;">';
            $contenido .= '<h2 style="color: #ea580c; margin-top: 0;">¡Bienvenido/a, ' . htmlspecialchars($this->nombre) . '!</h2>';
            $contenido .= '<p style="font-size: 15px; line-height: 1.6;">Gracias por crear tu cuenta en <strong>' . htmlspecialchars($nombreComercio) . '</strong>. Para poder realizar pedidos y ver tu historial, confirma tu cuenta haciendo clic en el siguiente botón:</p>';
            $contenido .= '<div style="text-align: center; margin: 30px 0;">';
            $contenido .= '<a href="' . $enlace . '" style="background-color: #ea580c; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">Confirmar Mi Cuenta</a>';
            $contenido .= '</div>';
            $contenido .= '<p style="font-size: 13px; color: #64748b;">Si no puedes hacer clic en el botón, copia y pega este enlace en tu navegador:<br><a href="' . $enlace . '" style="color: #ea580c;">' . $enlace . '</a></p>';
            $contenido .= '<hr style="border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;">';
            $contenido .= '<p style="font-size: 12px; color: #94a3b8; margin-bottom: 0;">Si no te registraste en ' . htmlspecialchars($nombreComercio) . ', puedes desestimar este correo.</p>';
            $contenido .= '</div>';

            $mail->Body = $contenido;
            return (bool)$mail->send();
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function enviarInstrucciones(): bool {
        try {
            $comercio = Comercio::find(1);
            $nombreComercio = $comercio->nombre ?? 'Retail Delivery';
            $appUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost:3000', '/');

            $mail = new PHPMailer(true);
            $this->configurar($mail);
            $mail->Subject = 'Reestablece tu contraseña - ' . $nombreComercio;

            $enlace = $appUrl . '/recuperar?token=' . urlencode($this->token);

            $contenido = '<div style="font-family: Arial, sans-serif; color: #1e293b; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 12px; background-color: #ffffff;">';
            $contenido .= '<h2 style="color: #ea580c; margin-top: 0;">Reestablecer contraseña</h2>';
            $contenido .= '<p style="font-size: 15px; line-height: 1.6;">Hola <strong>' . htmlspecialchars($this->nombre) . '</strong>, has solicitado reestablecer la contraseña de tu cuenta en <strong>' . htmlspecialchars($nombreComercio) . '</strong>.</p>';
            $contenido .= '<div style="text-align: center; margin: 30px 0;">';
            $contenido .= '<a href="' . $enlace . '" style="background-color: #ea580c; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">Crear Nueva Contraseña</a>';
            $contenido .= '</div>';
            $contenido .= '<p style="font-size: 13px; color: #64748b;">Si no puedes hacer clic en el botón, copia y pega este enlace en tu navegador:<br><a href="' . $enlace . '" style="color: #ea580c;">' . $enlace . '</a></p>';
            $contenido .= '<hr style="border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;">';
            $contenido .= '<p style="font-size: 12px; color: #94a3b8; margin-bottom: 0;">Si no solicitaste este cambio, puedes ignorar este mensaje; tu contraseña actual continuará siendo segura.</p>';
            $contenido .= '</div>';

            $mail->Body = $contenido;
            return (bool)$mail->send();
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * Envía email de confirmación y resumen del pedido con enlace de seguimiento en vivo
     */
    public static function enviarConfirmacionPedido(\Model\Pedido $pedido, array $detalles, ?string $emailDestino = null): bool {
        try {
            $comercio = Comercio::find((int)$pedido->comercio_id);
            $nombreComercio = $comercio->nombre ?? 'Retail Delivery';
            $appUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost:3000', '/');

            // Determinar email de destino
            $email = $emailDestino;
            if(empty($email) && $pedido->usuario_id) {
                $usuario = \Model\Usuario::find((int)$pedido->usuario_id);
                $email = $usuario->email ?? null;
            }

            if(empty($email)) {
                return false; // Sin email para notificar
            }

            $mail = new PHPMailer(true);
            $instancia = new self($email, $pedido->nombre_contacto);
            $instancia->configurar($mail);
            $mail->Subject = '¡Pedido #' . $pedido->id . ' Confirmado! - ' . $nombreComercio;

            $enlaceSeguimiento = $appUrl . '/pedido/seguimiento?id=' . (int)$pedido->id;

            $contenido = '<div style="font-family: Arial, sans-serif; color: #1e293b; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 12px; background-color: #ffffff;">';
            $contenido .= '<div style="text-align: center; margin-bottom: 20px;">';
            $contenido .= '<h1 style="color: #ea580c; margin: 0; font-size: 24px;">¡Gracias por tu compra, ' . htmlspecialchars($pedido->nombre_contacto) . '!</h1>';
            $contenido .= '<p style="color: #64748b; font-size: 14px; margin-top: 6px;">Tu pedido <strong>#' . $pedido->id . '</strong> ya fue recibido y está en proceso de confirmación en cocina.</p>';
            $contenido .= '</div>';

            $contenido .= '<div style="background-color: #f8fafc; border-radius: 8px; padding: 16px; margin-bottom: 20px;">';
            $contenido .= '<p style="margin: 0 0 8px 0; font-size: 13px; color: #64748b;"><strong>Modalidad:</strong> ' . ($pedido->tipo === 'delivery' ? 'Delivery a Domicilio' : 'Retiro en el Local') . '</p>';
            if($pedido->tipo === 'delivery' && !empty($pedido->direccion_texto)) {
                $contenido .= '<p style="margin: 0 0 8px 0; font-size: 13px; color: #64748b;"><strong>Dirección:</strong> ' . htmlspecialchars($pedido->direccion_texto) . '</p>';
            }
            $contenido .= '<p style="margin: 0; font-size: 13px; color: #64748b;"><strong>Método de pago:</strong> ' . ($pedido->metodo_pago === 'efectivo' ? 'Efectivo' : 'Mercado Pago (Online)') . '</p>';
            $contenido .= '</div>';

            $contenido .= '<h3 style="color: #1e293b; font-size: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 12px;">Detalle del Pedido</h3>';
            $contenido .= '<table style="width: 100%; border-collapse: collapse; font-size: 14px; margin-bottom: 20px;">';
            foreach($detalles as $item) {
                $nom = is_array($item) ? ($item['nombre_producto'] ?? '') : ($item->nombre_producto ?? '');
                $cant = is_array($item) ? ($item['cantidad'] ?? 1) : ($item->cantidad ?? 1);
                $sub = is_array($item) ? ($item['subtotal'] ?? 0.0) : ($item->subtotal ?? 0.0);
                $opc = is_array($item) ? ($item['opciones_texto'] ?? '') : ($item->opciones_texto ?? '');

                $contenido .= '<tr>';
                $contenido .= '<td style="padding: 8px 0; border-bottom: 1px solid #f1f5f9;"><strong>' . (int)$cant . 'x</strong> ' . htmlspecialchars($nom);
                if(!empty($opc)) {
                    $contenido .= '<br><small style="color: #ea580c;">' . htmlspecialchars($opc) . '</small>';
                }
                $contenido .= '</td>';
                $contenido .= '<td style="padding: 8px 0; border-bottom: 1px solid #f1f5f9; text-align: right;">$ ' . number_format((float)$sub, 2, ',', '.') . '</td>';
                $contenido .= '</tr>';
            }

            if((float)$pedido->costo_envio > 0) {
                $contenido .= '<tr>';
                $contenido .= '<td style="padding: 8px 0; color: #64748b;">Costo de Envío:</td>';
                $contenido .= '<td style="padding: 8px 0; text-align: right; color: #64748b;">$ ' . number_format((float)$pedido->costo_envio, 2, ',', '.') . '</td>';
                $contenido .= '</tr>';
            }

            $contenido .= '<tr>';
            $contenido .= '<td style="padding: 12px 0; font-size: 16px; font-weight: bold; color: #1e293b;">Total:</td>';
            $contenido .= '<td style="padding: 12px 0; font-size: 18px; font-weight: bold; color: #ea580c; text-align: right;">$ ' . number_format((float)$pedido->total, 2, ',', '.') . '</td>';
            $contenido .= '</tr>';
            $contenido .= '</table>';

            $contenido .= '<div style="text-align: center; margin: 28px 0;">';
            $contenido .= '<a href="' . $enlaceSeguimiento . '" style="background-color: #ea580c; color: #ffffff; padding: 14px 28px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 15px; display: inline-block;">Ver Seguimiento en Vivo</a>';
            $contenido .= '</div>';

            $contenido .= '<p style="font-size: 12px; color: #94a3b8; text-align: center; margin-bottom: 0;">' . htmlspecialchars($nombreComercio) . ' · Sistema de Pedidos Online</p>';
            $contenido .= '</div>';

            $mail->Body = $contenido;
            return (bool)$mail->send();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Envía alerta de stock crítico al dueño del comercio
     */
    public static function enviarAlertaStockCritico(\Model\Insumo $insumo, string $emailDueno): bool {
        try {
            $comercio = Comercio::find((int)$insumo->comercio_id);
            $nombreComercio = $comercio->nombre ?? 'Retail Delivery';
            $appUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost:3000', '/');

            $mail = new PHPMailer(true);
            $instancia = new self($emailDueno, 'Administrador');
            $instancia->configurar($mail);
            $mail->Subject = '⚠️ Alerta: Stock Bajo para ' . $insumo->nombre . ' - ' . $nombreComercio;

            $enlaceInsumos = $appUrl . '/admin/insumos';

            $contenido = '<div style="font-family: Arial, sans-serif; color: #1e293b; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #fecaca; border-radius: 12px; background-color: #ffffff;">';
            $contenido .= '<h2 style="color: #ef4444; margin-top: 0;">⚠️ Alerta de Stock Crítico en Cocina</h2>';
            $contenido .= '<p style="font-size: 15px; line-height: 1.6;">El insumo <strong>' . htmlspecialchars($insumo->nombre) . '</strong> ha alcanzado o superado su límite mínimo de reposición en <strong>' . htmlspecialchars($nombreComercio) . '</strong>.</p>';
            $contenido .= '<div style="background-color: #fef2f2; border: 1px solid #fca5a5; border-radius: 8px; padding: 16px; margin: 20px 0;">';
            $contenido .= '<p style="margin: 0 0 6px 0; font-size: 14px;"><strong>Stock Actual:</strong> <span style="color: #ef4444; font-weight: bold;">' . formatearCantidad($insumo->stock_actual, $insumo->unidad_medida) . ' ' . htmlspecialchars($insumo->unidad_medida) . '</span></p>';
            $contenido .= '<p style="margin: 0; font-size: 14px;"><strong>Stock Mínimo Configurado:</strong> ' . formatearCantidad($insumo->stock_minimo, $insumo->unidad_medida) . ' ' . htmlspecialchars($insumo->unidad_medida) . '</p>';
            $contenido .= '</div>';
            $contenido .= '<div style="text-align: center; margin: 24px 0;">';
            $contenido .= '<a href="' . $enlaceInsumos . '" style="background-color: #ef4444; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;">Registrar Compra / Ajustar Stock</a>';
            $contenido .= '</div>';
            $contenido .= '</div>';

            $mail->Body = $contenido;
            return (bool)$mail->send();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
