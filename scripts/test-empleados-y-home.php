<?php

require_once __DIR__ . '/../includes/app.php';

use Model\Usuario;
use Model\Comercio;
use Model\Categoria;
use Model\Producto;

echo "=== INICIANDO TEST AUTOMATIZADO: EMPLEADOS DE COCINA Y REDISEÑO DE HOME PAGE ===\n\n";

// --------------------------------------------------------------------------
// [TEST 1] Verificación del CRUD de Empleados de Cocina & Caja
// --------------------------------------------------------------------------
echo "[TEST 1] Probando gestión de cuentas de cocina (CRUD Empleados)...\n";

$emailTest = 'chef_test_' . time() . '@retail.com';
$empleadoTest = new Usuario([
    'nombre' => 'Chef Test Automatizado',
    'email' => $emailTest,
    'telefono' => '1199887766',
    'password' => 'claveSegura123',
    'rol' => 'empleado',
    'comercio_id' => 1,
    'confirmado' => 1
]);
$empleadoTest->hashPassword();
$resCrear = $empleadoTest->crear();

assert($resCrear['resultado'] === true, "Debe poder registrar un empleado de cocina");
$idEmpleado = (int)$resCrear['id'];
echo "  ✔ Empleado de cocina creado con ID #{$idEmpleado} ({$emailTest}).\n";

// Listar empleados
$empleados = Usuario::empleadosPorComercio(1);
$encontrado = false;
foreach($empleados as $emp) {
    if((int)$emp->id === $idEmpleado) {
        $encontrado = true;
        assert($emp->rol === 'empleado', "El rol debe ser 'empleado'");
        assert((int)$emp->confirmado === 1, "Debe estar activo (confirmado = 1)");
        break;
    }
}
assert($encontrado, "El empleado recién creado debe figurar en el listado");
echo "  ✔ Listado de empleados verificado con el nuevo registro.\n";

// Actualizar empleado
$empleadoGuardado = Usuario::find($idEmpleado);
$empleadoGuardado->nombre = 'Chef Test Modificado';
$empleadoGuardado->telefono = '1122334455';
$resAct = $empleadoGuardado->actualizar();
assert($resAct === true, "Debe poder actualizar datos del empleado");

$empleadoActualizado = Usuario::find($idEmpleado);
assert($empleadoActualizado->nombre === 'Chef Test Modificado', "El nombre debe haberse actualizado");
assert($empleadoActualizado->telefono === '1122334455', "El teléfono debe haberse actualizado");
echo "  ✔ Edición de datos del empleado verificada.\n";

// Desactivar y reactivar empleado (Toggle)
$empleadoActualizado->confirmado = 0;
$empleadoActualizado->actualizar();
$empDesactivado = Usuario::find($idEmpleado);
assert((int)$empDesactivado->confirmado === 0, "Debe poder desactivarse la cuenta de cocina");

$empleadoActualizado->confirmado = 1;
$empleadoActualizado->actualizar();
$empReactivado = Usuario::find($idEmpleado);
assert((int)$empReactivado->confirmado === 1, "Debe poder reactivarse la cuenta de cocina");
echo "  ✔ Alternancia de estado (Activo/Inactivo) verificada.\n\n";

// --------------------------------------------------------------------------
// [TEST 2] Verificación de Enlaces en Sidebar y Dashboard de Admin
// --------------------------------------------------------------------------
echo "[TEST 2] Verificando integración de Personal Cocina en el panel de administración...\n";

$sidebarContent = file_get_contents(__DIR__ . '/../views/templates/panel-sidebar.php');
assert(strpos($sidebarContent, 'href="/admin/empleados"') !== false, "El sidebar debe contener enlace a /admin/empleados");
assert(strpos($sidebarContent, 'fa-kitchen-set') !== false, "El sidebar debe incluir icono fa-kitchen-set");
echo "  ✔ Enlace en el sidebar del Dueño verificado.\n";

$dashboardContent = file_get_contents(__DIR__ . '/../views/admin/index.php');
assert(strpos($dashboardContent, 'href="/admin/empleados"') !== false, "El dashboard admin debe contener tarjeta a /admin/empleados");
assert(strpos($dashboardContent, 'Personal de Cocina & Caja') !== false, "El dashboard admin debe incluir título Personal de Cocina & Caja");
echo "  ✔ Tarjeta en el dashboard admin verificada.\n\n";

// --------------------------------------------------------------------------
// [TEST 3] Verificación del Rediseño de la Home Page (Gastronómica)
// --------------------------------------------------------------------------
echo "[TEST 3] Verificando respuesta HTTP y contenido de la nueva Home Page (/)... \n";

$ch = curl_init('http://localhost:3000/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$htmlHome = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

assert($httpCode === 200, "La Home Page debe responder con HTTP 200");
assert(strpos($htmlHome, 'El verdadero sabor casero') !== false, "Debe contener el nuevo titular de la Home");
assert(strpos($htmlHome, 'Ver Menú y Pedir') !== false, "Debe incluir botón principal al Menú");
assert(strpos($htmlHome, 'Seguir mi Pedido') !== false, "Debe incluir botón de seguimiento");
assert(strpos($htmlHome, 'Especialidades de la Casa') !== false, "Debe contener sección de platos destacados");
assert(strpos($htmlHome, 'Tu pedido en 3 simples pasos') !== false, "Debe contener sección de ¿Cómo Funciona?");
assert(strpos($htmlHome, '¿Por qué elegirnos?') !== false, "Debe contener sección de beneficios");
assert(strpos($htmlHome, 'Cocina Abierta') !== false, "Debe contener badge de estado del local");
echo "  ✔ Home Page verificada con éxito: diseño gastronómico, platos destacados y conversión.\n\n";

// --------------------------------------------------------------------------
// [LIMPIEZA] Eliminar empleado de prueba
// --------------------------------------------------------------------------
$empleadoActualizado->eliminar();
echo "  ✔ Empleado de prueba eliminado limpiamente.\n\n";

echo "=========================================================================\n";
echo "¡TODAS LAS PRUEBAS DE EMPLEADOS Y HOME PAGE PASARON CON ÉXITO!\n";
echo "=========================================================================\n";
