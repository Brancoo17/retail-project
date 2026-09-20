<?php

require_once __DIR__ . '/../includes/app.php';

use Model\Comercio;
use Model\Usuario;

echo "Iniciando seed inicial...\n";

// 1. Comercio Default (id = 1)
$comercio = Comercio::find(1);
if (!$comercio) {
    echo "Creando comercio por defecto...\n";
    $comercio = new Comercio([
        'nombre' => 'Pizzería & Rotisería Don Mario',
        'direccion' => 'Av. Corrientes 1234, CABA',
        'lat' => -34.603722,
        'lng' => -58.381592,
        'telefono' => '+54 11 4321-8765',
        'horario' => 'Martes a Domingo de 19:30 a 00:30',
        'logo' => '',
        'color_primario' => '#ea580c',
        'color_secundario' => '#1e293b',
        'km_envio_gratis' => 3.0,
        'costo_envio_por_km' => 500.0,
        'minutos_cancelacion' => 5,
        'estado' => 'activo'
    ]);
    $resultado = $comercio->crear();
    if($resultado['resultado']) {
        echo "Comercio creado con éxito (ID: {$resultado['id']}).\n";
    } else {
        echo "Error al crear comercio.\n";
        exit(1);
    }
} else {
    echo "El comercio con id = 1 ya existe.\n";
}

// 2. Usuarios de Prueba por Rol
$usuariosDemo = [
    [
        'comercio_id' => 1,
        'nombre' => 'Dueño Demo',
        'email' => 'admin@retail.com',
        'password' => 'admin123',
        'telefono' => '1143218765',
        'rol' => 'dueño',
        'confirmado' => 1
    ],
    [
        'comercio_id' => 1,
        'nombre' => 'Cocinero Demo',
        'email' => 'cocina@retail.com',
        'password' => 'cocina123',
        'telefono' => '1199887766',
        'rol' => 'empleado',
        'confirmado' => 1
    ],
    [
        'comercio_id' => 1,
        'nombre' => 'Repartidor Demo',
        'email' => 'delivery@retail.com',
        'password' => 'repartidor123',
        'telefono' => '1155443322',
        'rol' => 'repartidor',
        'confirmado' => 1
    ],
    [
        'comercio_id' => 1,
        'nombre' => 'Cliente Demo',
        'email' => 'cliente@retail.com',
        'password' => 'cliente123',
        'telefono' => '1122334455',
        'rol' => 'cliente',
        'confirmado' => 1
    ]
];

foreach ($usuariosDemo as $datos) {
    $usuarioExistente = Usuario::where('email', $datos['email']);
    if (!$usuarioExistente) {
        $usuario = new Usuario($datos);
        $usuario->hashPassword();
        $usuario->crear();
        echo "Usuario '{$datos['nombre']}' ({$datos['email']}) creado con rol '{$datos['rol']}'.\n";
    } else {
        echo "Usuario '{$datos['email']}' ya existe.\n";
    }
}

echo "Seed inicial completado con éxito.\n";
