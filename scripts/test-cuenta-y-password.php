<?php

require_once __DIR__ . '/../includes/app.php';

use Model\Usuario;
use MVC\Router;

echo "=== INICIANDO TEST AUTOMATIZADO: VISTA /CUENTA, SIDEBAR GEAR Y TOGGLE PASSWORD ===\n\n";

// --------------------------------------------------------------------------
// [TEST 1] Verificación de Protección de Ruta /cuenta (Solo Autenticados)
// --------------------------------------------------------------------------
echo "[TEST 1] Verificando protección de acceso a /cuenta...\n";

// Petición anónima (sin sesión) debe redirigir a /login
$ch = curl_init('http://localhost:3000/cuenta');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
$res = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$redirectUrl = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
curl_close($ch);

assert($httpCode === 302, "Usuario no autenticado debe recibir redirección HTTP 302");
assert(strpos($redirectUrl, '/login') !== false, "Debe redirigir a /login");
echo "  ✔ Protección de acceso anónimo verificada (redirige a /login).\n\n";

// --------------------------------------------------------------------------
// [TEST 2] Verificación del Controlador y Actualización de Perfil
// --------------------------------------------------------------------------
echo "[TEST 2] Probando actualización de datos de perfil en CuentaController...\n";

// Crear usuario de prueba
$emailOriginal = 'usuario_cuenta_test_' . time() . '@retail.com';
$passwordOriginal = 'claveOriginal123';
$usuarioPrueba = new Usuario([
    'nombre' => 'Usuario Test Original',
    'email' => $emailOriginal,
    'telefono' => '1155443322',
    'password' => $passwordOriginal,
    'rol' => 'cliente',
    'comercio_id' => 1,
    'confirmado' => 1
]);
$usuarioPrueba->hashPassword();
$resCrear = $usuarioPrueba->crear();
assert($resCrear['resultado'] === true, "Debe crear el usuario de prueba");
$usuarioId = (int)$resCrear['id'];
$usuarioPrueba->id = $usuarioId;
echo "  ✔ Usuario de prueba creado con ID #{$usuarioId}.\n";

// Simular sesión autenticada
$_SESSION['login'] = true;
$_SESSION['id'] = $usuarioId;
$_SESSION['nombre'] = $usuarioPrueba->nombre;
$_SESSION['email'] = $usuarioPrueba->email;
$_SESSION['rol'] = 'cliente';

// A) Actualizar nombre y teléfono conservando el mismo email
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['nombre'] = 'Usuario Test Modificado';
$_POST['email'] = $emailOriginal;
$_POST['telefono'] = '1199008877';
$_POST['password_actual'] = '';
$_POST['password_nuevo'] = '';
$_POST['password_confirmar'] = '';

$router = new Router();
ob_start();
Controllers\CuentaController::index($router);
$salida = ob_get_clean();

$usuarioDb = Usuario::find($usuarioId);
assert($usuarioDb->nombre === 'Usuario Test Modificado', "El nombre debe haberse actualizado");
assert($usuarioDb->telefono === '1199008877', "El teléfono debe haberse actualizado");
assert($_SESSION['nombre'] === 'Usuario Test Modificado', "La sesión debe sincronizar el nuevo nombre");
echo "  ✔ Actualización de datos personales y sincronización de sesión verificada.\n";

// B) Probar cambio de contraseña con clave actual errónea (debe rechazar)
$_POST['password_actual'] = 'claveIncorrecta123';
$_POST['password_nuevo'] = 'nuevaClave456';
$_POST['password_confirmar'] = 'nuevaClave456';

ob_start();
Controllers\CuentaController::index($router);
$salidaError = ob_get_clean();

$usuarioDbNoCambio = Usuario::find($usuarioId);
assert(password_verify($passwordOriginal, $usuarioDbNoCambio->password), "La contraseña NO debe cambiar si la clave actual es incorrecta");
echo "  ✔ Rechazo seguro de cambio de contraseña ante clave actual incorrecta verificado.\n";

// C) Probar cambio de contraseña exitoso con clave actual correcta
$_POST['password_actual'] = $passwordOriginal;
$_POST['password_nuevo'] = 'nuevaClave456';
$_POST['password_confirmar'] = 'nuevaClave456';

ob_start();
Controllers\CuentaController::index($router);
$salidaExito = ob_get_clean();

$usuarioDbCambio = Usuario::find($usuarioId);
assert(password_verify('nuevaClave456', $usuarioDbCambio->password), "La nueva contraseña debe haberse hasheado y guardado correctamente");
echo "  ✔ Cambio de contraseña exitoso verificado.\n\n";

// --------------------------------------------------------------------------
// [TEST 3] Verificación de la Integración en Sidebar y Layout
// --------------------------------------------------------------------------
echo "[TEST 3] Verificando acceso en Sidebar y Layout...\n";

$sidebarHtml = file_get_contents(__DIR__ . '/../views/templates/panel-sidebar.php');
assert(strpos($sidebarHtml, 'href="/cuenta"') !== false, "El sidebar debe enlazar a /cuenta");
assert(strpos($sidebarHtml, 'panel-sidebar__usuario-link') !== false, "El avatar y nombre deben enlazar a /cuenta");
assert(strpos($sidebarHtml, 'panel-sidebar__usuario-config') !== false, "Debe existir el botón con icono gear");
assert(strpos($sidebarHtml, 'fa-gear') !== false, "Debe incluir el icono fa-gear");
echo "  ✔ Sidebar verificado: bloque de usuario interactivo e icono de gear presentes.\n";

$layoutHtml = file_get_contents(__DIR__ . '/../views/layout.php');
assert(strpos($layoutHtml, "str_starts_with(\$urlActual, '/cuenta')") !== false, "layout.php debe incluir /cuenta en esRutaPanel");
echo "  ✔ Layout verificado: /cuenta configurada para mostrar sidebar.\n\n";

// --------------------------------------------------------------------------
// [TEST 4] Verificación de Función Mostrar/Ocultar Contraseña
// --------------------------------------------------------------------------
echo "[TEST 4] Verificando funcionalidad Mostrar/Ocultar Contraseña...\n";

$appJs = file_get_contents(__DIR__ . '/../src/js/app.js');
assert(strpos($appJs, 'initMostrarOcultarPassword') !== false, "app.js debe definir y ejecutar initMostrarOcultarPassword");
assert(strpos($appJs, 'btn-toggle-password') !== false, "app.js debe crear botones con clase btn-toggle-password");
assert(strpos($appJs, 'fa-eye') !== false, "app.js debe incluir iconos de ojo de FontAwesome");

$builtJs = file_get_contents(__DIR__ . '/../public/build/js/app.js');
assert(strpos($builtJs, 'initMostrarOcultarPassword') !== false, "El bundle compilado de JS debe contener la función");

$builtCss = file_get_contents(__DIR__ . '/../public/build/css/app.css');
assert(strpos($builtCss, 'campo-password-wrapper') !== false, "El bundle compilado de CSS debe contener campo-password-wrapper");
assert(strpos($builtCss, 'btn-toggle-password') !== false, "El bundle compilado de CSS debe contener btn-toggle-password");
echo "  ✔ Mostrar/Ocultar contraseña verificado en código fuente y bundles compilados.\n\n";

// --------------------------------------------------------------------------
// [LIMPIEZA]
// --------------------------------------------------------------------------
$usuarioPrueba->eliminar();
echo "  ✔ Registro de prueba eliminado limpiamente.\n\n";

echo "=========================================================================\n";
echo "¡TODAS LAS PRUEBAS DE /CUENTA, SIDEBAR GEAR Y PASSWORD TOGGLE PASARON CON ÉXITO!\n";
echo "=========================================================================\n";
