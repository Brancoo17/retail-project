<?php

require_once __DIR__ . '/../includes/app.php';

use Model\Producto;

$prod = Producto::find(1);
$payload = [
    'nombre_contacto' => 'Martina Silva',
    'telefono_contacto' => '1155667788',
    'tipo' => 'delivery',
    'metodo_pago' => 'efectivo',
    'direccion_texto' => 'Calle Mitre 450, 1ro A',
    'lat' => -24.785000,
    'lng' => -65.412000,
    'items' => [
        [
            'producto_id' => (int)$prod->id,
            'cantidad' => 1,
            'opciones' => [],
            'notas' => 'Sin cebolla cruda'
        ]
    ]
];

$ch = curl_init('http://127.0.0.1:3000/pedido/crear');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
$res = curl_exec($ch);
curl_close($ch);

echo "Pedido creado: " . $res . PHP_EOL;
