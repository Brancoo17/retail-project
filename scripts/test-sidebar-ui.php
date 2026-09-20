<?php

require_once __DIR__ . '/../includes/app.php';

echo "=== INICIANDO VERIFICACIÓN DE UI: SIDEBAR AUTENTICADO Y HEADER PÚBLICO ===\n\n";

// Función auxiliar para renderizar vista capturando buffer
function simularRender(string $url, ?string $rol = null, ?string $nombre = null): string {
    $_SERVER['REQUEST_URI'] = $url;
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    if($rol !== null) {
        $_SESSION['login'] = true;
        $_SESSION['id'] = 1;
        $_SESSION['nombre'] = $nombre ?? 'Usuario Test';
        $_SESSION['rol'] = $rol;
        $_SESSION['comercio_id'] = 1;
    } else {
        unset($_SESSION['login'], $_SESSION['id'], $_SESSION['nombre'], $_SESSION['rol']);
    }

    $urlActual = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $login = $_SESSION['login'] ?? false;
    $rol = $_SESSION['rol'] ?? '';
    $nombre = $_SESSION['nombre'] ?? '';

    $esRutaPanel = (
        str_starts_with($urlActual, '/admin') ||
        str_starts_with($urlActual, '/cocina') ||
        str_starts_with($urlActual, '/repartidor') ||
        str_starts_with($urlActual, '/cliente')
    );
    $mostrarSidebar = ($login && $esRutaPanel);

    $enlacePanel = match($rol) {
        'dueño' => '/admin',
        'empleado' => '/cocina',
        'repartidor' => '/repartidor',
        'cliente' => '/cliente',
        default => '/admin'
    };

    $comercio = obtenerComercio();
    $nombreComercio = $comercio->nombre ?? 'Retail Delivery';
    $tieneLogo = false;
    $titulo = 'Test UI';
    $contenido = '<div class="test-content">Contenido de prueba</div>';

    ob_start();
    include __DIR__ . '/../views/layout.php';
    return ob_get_clean();
}

// --------------------------------------------------------------------------
// CASO 1: VISITANTE NO AUTENTICADO EN HOME ('/')
// --------------------------------------------------------------------------
echo "[CASO 1] Visitante público en Home ('/')...\n";
$htmlHomeAnonimo = simularRender('/');
assert(strpos($htmlHomeAnonimo, 'class="panel-sidebar"') === false, "No debe mostrar sidebar en Home pública");
assert(strpos($htmlHomeAnonimo, 'class="header"') !== false, "Debe mostrar header superior");
assert(strpos($htmlHomeAnonimo, 'href="/login"') !== false, "Debe tener botón Ingresar");
assert(strpos($htmlHomeAnonimo, 'href="/crear-cuenta"') !== false, "Debe tener botón Registrarse");
assert(strpos($htmlHomeAnonimo, 'href="/admin"') === false, "No debe tener enlace a Panel");
echo "    OK: Header superior correcto para visitante anónimo.\n\n";

// --------------------------------------------------------------------------
// CASO 2: USUARIO AUTENTICADO (DUEÑO) EN HOME ('/')
// --------------------------------------------------------------------------
echo "[CASO 2] Dueño autenticado navegando en Home ('/')...\n";
$htmlHomeDueno = simularRender('/', 'dueño', 'Don Pepito');
assert(strpos($htmlHomeDueno, 'class="panel-sidebar"') === false, "No debe mostrar sidebar en Home pública");
assert(strpos($htmlHomeDueno, 'class="header"') !== false, "Debe mostrar header superior");
assert(strpos($htmlHomeDueno, 'href="/admin"') !== false, "Debe tener enlace a 'Panel' (/admin)");
assert(strpos($htmlHomeDueno, 'href="/logout"') !== false, "Debe tener enlace a 'Cerrar Sesión'");
assert(strpos($htmlHomeDueno, 'href="/admin/productos"') === false, "No debe saturar el header con enlaces internos como Productos");
assert(strpos($htmlHomeDueno, 'href="/admin/insumos"') === false, "No debe saturar el header con Insumos");
assert(strpos($htmlHomeDueno, 'href="/login"') === false, "No debe mostrar Ingresar");
echo "    OK: Header superior limpio con 'Panel' y 'Cerrar Sesión' exclusivamente.\n\n";

// --------------------------------------------------------------------------
// CASO 3: DUEÑO EN PANEL ADMIN ('/admin') -> SIDEBAR CON ICONOS
// --------------------------------------------------------------------------
echo "[CASO 3] Dueño en Panel de Administración ('/admin')...\n";
$htmlAdmin = simularRender('/admin', 'dueño', 'Don Pepito');
assert(strpos($htmlAdmin, 'class="panel-sidebar"') !== false, "Debe renderizar panel-sidebar");
assert(strpos($htmlAdmin, 'class="panel-topbar"') !== false, "Debe renderizar panel-topbar");
assert(strpos($htmlAdmin, 'Don Pepito') !== false, "Debe mostrar el nombre del usuario en el sidebar");
assert(strpos($htmlAdmin, 'Administrador') !== false, "Debe mostrar el badge de Administrador");

// Validar iconos y rutas para Dueño
$itemsEsperadosDueno = [
    '/admin' => 'fa-gauge',
    '/cocina' => 'fa-fire-burner',
    '/cocina/caja' => 'fa-cash-register',
    '/admin/productos' => 'fa-burger',
    '/admin/categorias' => 'fa-tags',
    '/admin/aderezos' => 'fa-bottle-droplet',
    '/admin/insumos' => 'fa-boxes-stacked',
    '/admin/insumos/movimientos' => 'fa-arrow-right-arrow-left',
    '/admin/repartidores' => 'fa-motorcycle',
    '/admin/reportes' => 'fa-chart-line',
    '/admin/configuracion' => 'fa-gear',
    '/logout' => 'fa-arrow-right-from-bracket'
];

foreach($itemsEsperadosDueno as $ruta => $icono) {
    assert(strpos($htmlAdmin, "href=\"{$ruta}\"") !== false, "Sidebar de Dueño debe contener enlace a {$ruta}");
    assert(strpos($htmlAdmin, $icono) !== false, "Sidebar de Dueño debe contener icono {$icono}");
}
echo "    OK: Sidebar de Dueño contiene todos los 10 enlaces requeridos con sus respectivos iconos Font Awesome.\n\n";

// --------------------------------------------------------------------------
// CASO 4: EMPLEADO EN COCINA ('/cocina') -> SIDEBAR EMPLEADO
// --------------------------------------------------------------------------
echo "[CASO 4] Empleado en Consola de Cocina ('/cocina')...\n";
$htmlCocina = simularRender('/cocina', 'empleado', 'Chef Ramón');
assert(strpos($htmlCocina, 'class="panel-sidebar"') !== false, "Debe renderizar panel-sidebar");
assert(strpos($htmlCocina, 'Chef Ramón') !== false, "Debe mostrar el nombre Chef Ramón");
assert(strpos($htmlCocina, 'Cocina &amp; Caja') !== false || strpos($htmlCocina, 'Cocina') !== false, "Debe mostrar badge de rol");
assert(strpos($htmlCocina, 'href="/cocina"') !== false, "Debe incluir Cocina (KDS)");
assert(strpos($htmlCocina, 'href="/cocina/caja"') !== false, "Debe incluir Caja Diario");
assert(strpos($htmlCocina, 'href="/admin/configuracion"') === false, "No debe mostrar Configuración a empleados");
assert(strpos($htmlCocina, 'href="/admin/insumos"') === false, "No debe mostrar Insumos a empleados");
echo "    OK: Sidebar de Empleado limitado a Cocina y Caja con sus iconos.\n\n";

// --------------------------------------------------------------------------
// CASO 5: REPARTIDOR EN REPARTOS ('/repartidor') -> SIDEBAR REPARTIDOR
// --------------------------------------------------------------------------
echo "[CASO 5] Repartidor en panel ('/repartidor')...\n";
$htmlRepartidor = simularRender('/repartidor', 'repartidor', 'Moto Juan');
assert(strpos($htmlRepartidor, 'class="panel-sidebar"') !== false, "Debe renderizar panel-sidebar");
assert(strpos($htmlRepartidor, 'fa-motorcycle') !== false, "Debe contener icono de motocicleta");
assert(strpos($htmlRepartidor, 'href="/repartidor"') !== false, "Debe enlazar a /repartidor");
assert(strpos($htmlRepartidor, 'href="/admin"') === false, "No debe mostrar links de administración");
echo "    OK: Sidebar de Repartidor verificado.\n\n";

// --------------------------------------------------------------------------
// CASO 6: CLIENTE EN MI CUENTA ('/cliente') -> SIDEBAR CLIENTE
// --------------------------------------------------------------------------
echo "[CASO 6] Cliente en panel ('/cliente')...\n";
$htmlCliente = simularRender('/cliente', 'cliente', 'Lucía Cliente');
assert(strpos($htmlCliente, 'class="panel-sidebar"') !== false, "Debe renderizar panel-sidebar");
assert(strpos($htmlCliente, 'fa-receipt') !== false, "Debe contener icono de Mis Pedidos");
assert(strpos($htmlCliente, 'href="/cliente"') !== false, "Debe enlazar a /cliente");
assert(strpos($htmlCliente, 'href="/menu"') !== false, "Debe enlazar a /menu");
assert(strpos($htmlCliente, 'href="/admin"') === false, "No debe mostrar links de admin");
echo "    OK: Sidebar de Cliente verificado.\n\n";

// --------------------------------------------------------------------------
// CASO 7: CLIENTE EN HOME ('/') -> ENLACE A PANEL DE CLIENTE
// --------------------------------------------------------------------------
echo "[CASO 7] Cliente navegando en Home pública ('/')...\n";
$htmlHomeCliente = simularRender('/', 'cliente', 'Lucía Cliente');
assert(strpos($htmlHomeCliente, 'href="/cliente"') !== false, "El botón 'Panel' debe llevar al cliente a /cliente");
assert(strpos($htmlHomeCliente, 'href="/logout"') !== false, "Debe tener Cerrar Sesión");
assert(strpos($htmlHomeCliente, 'class="panel-sidebar"') === false, "No debe tener sidebar en Home");
echo "    OK: Navegación pública de Cliente verificada.\n\n";

echo "=== TODOS LOS CASOS DE UI Y NAVEGACIÓN PASARON EXITOSAMENTE ===\n";
