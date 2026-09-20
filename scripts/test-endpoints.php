<?php

$endpoints = [
    '/' => 'Página Principal',
    '/menu' => 'Carta Digital',
    '/checkout' => 'Checkout de Pedidos',
    '/login' => 'Autenticación',
    '/api/pedido-estado?id=3' => 'API Estado de Pedido'
];

echo "VERIFICACIÓN DE RUTAS HTTP:" . PHP_EOL;

foreach ($endpoints as $ruta => $nombre) {
    $url = "http://127.0.0.1:3000" . $ruta;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $resp = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $icono = ($status >= 200 && $status < 400) ? '✔' : '❌';
    echo "  {$icono} {$nombre} ({$ruta}) -> HTTP {$status}" . PHP_EOL;
}

// Probar endpoint POST /api/calcular-envio
$ch = curl_init('http://127.0.0.1:3000/api/calcular-envio');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['lat' => -24.790000, 'lng' => -65.420000]));
$resEnvio = curl_exec($ch);
$statusEnvio = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$datosEnvio = json_decode($resEnvio, true);
if($statusEnvio === 200 && !empty($datosEnvio['ok'])) {
    echo "  ✔ API Cálculo de Envío (/api/calcular-envio) -> HTTP 200 (Distancia: {$datosEnvio['distancia_formateada']}, Costo: {$datosEnvio['costo_formateado']})" . PHP_EOL;
} else {
    echo "  ❌ API Cálculo de Envío falló -> HTTP {$statusEnvio}" . PHP_EOL;
}
