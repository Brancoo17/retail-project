<?php

require_once __DIR__ . '/../includes/app.php';

use Model\Pedido;
use Model\Insumo;
use Classes\Email;
use MVC\Router;

echo "=== INICIANDO TEST AUTOMATIZADO: ETAPA 9 - PULIDO UX, NOTIFICACIONES Y DESPLIEGUE ===\n\n";

// --------------------------------------------------------------------------
// [TEST 1] Verificación de la Página 404 y Manejo de Rutas Inexistentes
// --------------------------------------------------------------------------
echo "[TEST 1] Verificando página de error 404 corporativa...\n";

// Intentar petición HTTP real al servidor local
$ch = curl_init('http://localhost:3000/ruta-totalmente-inexistente-' . time());
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$salida404 = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($salida404 !== false && $httpCode > 0) {
    assert($httpCode === 404, "El código HTTP debe ser 404");
    assert(strpos($salida404, 'Error 404') !== false, "Debe contener badge o texto de Error 404");
    assert(strpos($salida404, 'Explorar el Menú') !== false, "Debe incluir enlace de retorno al Menú");
    assert(strpos($salida404, 'Volver al Inicio') !== false, "Debe incluir enlace de retorno al Inicio");
    echo "  ✔ Manejo de rutas 404 verificado vía HTTP con código 404 y vista corporativa.\n\n";
} else {
    // Verificación directa de renderizado de la vista
    $router = new Router();
    ob_start();
    $router->render('paginas/404', [
        'titulo' => 'Página no encontrada - 404'
    ]);
    $salidaVista = ob_get_clean();
    assert(strpos($salidaVista, 'Error 404') !== false, "Debe contener badge o texto de Error 404");
    assert(strpos($salidaVista, 'Explorar el Menú') !== false, "Debe incluir enlace de retorno al Menú");
    echo "  ✔ Vista corporativa 404 verificada por render directo.\n\n";
}

// --------------------------------------------------------------------------
// [TEST 2] Verificación de PWA Web App Manifest
// --------------------------------------------------------------------------
echo "[TEST 2] Verificando manifest.json para instalación móvil PWA...\n";
$manifestPath = __DIR__ . '/../public/manifest.json';
assert(file_exists($manifestPath), "El archivo manifest.json debe existir en public/");

$manifestContent = file_get_contents($manifestPath);
$manifestData = json_decode($manifestContent, true);
assert(is_array($manifestData), "manifest.json debe ser un JSON válido");
assert(!empty($manifestData['name']), "manifest.json debe tener 'name'");
assert(!empty($manifestData['short_name']), "manifest.json debe tener 'short_name'");
assert($manifestData['display'] === 'standalone', "manifest.json debe usar display standalone");
assert($manifestData['theme_color'] === '#ea580c', "manifest.json debe tener theme_color '#ea580c'");
echo "  ✔ PWA manifest.json verificado y válido.\n\n";

// --------------------------------------------------------------------------
// [TEST 3] Metadatos Móviles y Open Graph en Layout
// --------------------------------------------------------------------------
echo "[TEST 3] Verificando meta tags PWA y Open Graph en layout.php...\n";
$layoutPath = __DIR__ . '/../views/layout.php';
$layoutContent = file_get_contents($layoutPath);

assert(strpos($layoutContent, 'rel="manifest"') !== false, "layout.php debe enlazar manifest.json");
assert(strpos($layoutContent, 'name="theme-color"') !== false, "layout.php debe tener meta theme-color");
assert(strpos($layoutContent, 'apple-mobile-web-app-capable') !== false, "layout.php debe tener meta apple-mobile-web-app-capable");
assert(strpos($layoutContent, 'property="og:title"') !== false, "layout.php debe tener Open Graph og:title");
assert(strpos($layoutContent, 'property="og:type"') !== false, "layout.php debe tener Open Graph og:type");
echo "  ✔ Metadatos móviles y Open Graph verificados.\n\n";

// --------------------------------------------------------------------------
// [TEST 4] Notificaciones por Email (Confirmación de Pedido y Alerta de Stock)
// --------------------------------------------------------------------------
echo "[TEST 4] Verificando métodos de email en classes/Email.php...\n";
assert(method_exists(Email::class, 'enviarConfirmacionPedido'), "Email debe tener método enviarConfirmacionPedido");
assert(method_exists(Email::class, 'enviarAlertaStockCritico'), "Email debe tener método enviarAlertaStockCritico");

// Probar construcción de email con un pedido de prueba
$pedidoPrueba = Pedido::find(1) ?? new Pedido([
    'id' => 999,
    'comercio_id' => 1,
    'nombre_contacto' => 'Cliente Test',
    'telefono_contacto' => '1122334455',
    'direccion_texto' => 'Av. Siempre Viva 742',
    'tipo' => 'delivery',
    'metodo_pago' => 'efectivo',
    'costo_envio' => 500.00,
    'total' => 4500.00
]);

$detallesPrueba = [
    [
        'nombre_producto' => 'Burger Doble Cheddar',
        'cantidad' => 2,
        'subtotal' => 4000.00,
        'opciones_texto' => 'Papas extras'
    ]
];

// Comprobar que no lanza excepciones (en entorno de test sin SMTP real devolverá false de forma controlada)
$resEmailPedido = Email::enviarConfirmacionPedido($pedidoPrueba, $detallesPrueba, 'test@retail.com');
echo "  ✔ Método enviarConfirmacionPedido ejecutado sin excepciones.\n";

$insumoPrueba = Insumo::find(1) ?? new Insumo([
    'id' => 1,
    'comercio_id' => 1,
    'nombre' => 'Harina 000',
    'unidad_medida' => 'kg',
    'stock_actual' => 2.5,
    'stock_minimo' => 10.0
]);

$resEmailStock = Email::enviarAlertaStockCritico($insumoPrueba, 'admin@retail.com');
echo "  ✔ Método enviarAlertaStockCritico ejecutado sin excepciones.\n\n";

// --------------------------------------------------------------------------
// [TEST 5] Endpoint de Polling en Vivo: /api/pedido-estado
// --------------------------------------------------------------------------
echo "[TEST 5] Verificando endpoint de polling en vivo (/api/pedido-estado)...\n";
$ch = curl_init('http://localhost:3000/api/pedido-estado?id=1');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$salidaEstado = curl_exec($ch);
curl_close($ch);

$datosEstado = json_decode($salidaEstado, true);
assert(is_array($datosEstado), "La respuesta debe ser JSON válido");
assert(isset($datosEstado['ok']), "Debe incluir campo 'ok'");
echo "  ✔ Endpoint /api/pedido-estado verificado para actualización en vivo del tracker.\n\n";

// --------------------------------------------------------------------------
// [TEST 6] Verificación de la Guía de Despliegue (DEPLOY.md)
// --------------------------------------------------------------------------
echo "[TEST 6] Verificando existencia de DEPLOY.md...\n";
$deployPath = __DIR__ . '/../DEPLOY.md';
assert(file_exists($deployPath), "DEPLOY.md debe existir en la raíz del proyecto");
$deployContent = file_get_contents($deployPath);
assert(strpos($deployContent, 'Requisitos del Servidor') !== false, "DEPLOY.md debe incluir requisitos");
assert(strpos($deployContent, 'DocumentRoot') !== false, "DEPLOY.md debe incluir opciones de DocumentRoot");
assert(strpos($deployContent, 'Checklist Pre-Lanzamiento') !== false, "DEPLOY.md debe incluir checklist");
echo "  ✔ Guía técnica de producción DEPLOY.md verificada.\n\n";

echo "=========================================================================\n";
echo "¡TODAS LAS PRUEBAS DE LA ETAPA 9 (PULIDO UX Y DESPLIEGUE) PASARON CON ÉXITO!\n";
echo "=========================================================================\n";
