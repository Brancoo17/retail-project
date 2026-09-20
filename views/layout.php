<?php
    $contenido = $contenido ?? "";
    $titulo = $titulo ?? "Sistema de Gestión";
    $tipo = $tipo ?? 'publico';
    $nombre = $nombre ?? ($_SESSION['nombre'] ?? '');
    $rol = $_SESSION['rol'] ?? '';
    $login = $_SESSION['login'] ?? false;
    $tieneMapa = $tieneMapa ?? false;

    $comercio = obtenerComercio();
    $nombreComercio = $comercio->nombre ?? 'Retail Delivery';
    $tieneLogo = (!empty($comercio->logo) && file_exists(CARPETA_IMAGENES . $comercio->logo));

    $urlActual = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $esActivo = function(string $ruta) use ($urlActual): string {
        if($ruta === '/' || $ruta === '/admin' || $ruta === '/cocina' || $ruta === '/repartidor' || $ruta === '/cliente') {
            return ($urlActual === $ruta) ? 'activo' : '';
        }
        return str_starts_with($urlActual, $ruta) ? 'activo' : '';
    };

    // Determinar si corresponde al layout de Panel con Sidebar
    $esRutaPanel = (
        str_starts_with($urlActual, '/admin') ||
        str_starts_with($urlActual, '/cocina') ||
        str_starts_with($urlActual, '/repartidor') ||
        str_starts_with($urlActual, '/cliente') ||
        str_starts_with($urlActual, '/cuenta')
    );
    $mostrarSidebar = ($login && $esRutaPanel);

    // Enlace de panel según rol para la cabecera pública
    $enlacePanel = match($rol) {
        'dueño' => '/admin',
        'empleado' => '/cocina',
        'repartidor' => '/repartidor',
        'cliente' => '/cliente',
        default => '/admin'
    };
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo s($titulo); ?> | <?php echo s($nombreComercio); ?></title>
    <meta name="description" content="<?php echo s($nombreComercio); ?> - Pedidos online y delivery gastronómico en tiempo real.">
    <meta name="theme-color" content="#ea580c">

    <!-- PWA & Mobile Web App -->
    <link rel="manifest" href="/manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="<?php echo s($nombreComercio); ?>">

    <!-- Open Graph / Redes Sociales -->
    <meta property="og:title" content="<?php echo s($titulo); ?> | <?php echo s($nombreComercio); ?>">
    <meta property="og:description" content="Hacé tu pedido online en <?php echo s($nombreComercio); ?> con delivery en tiempo real.">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?php echo s($nombreComercio); ?>">
    
    <?php if($tieneLogo): ?>
        <meta property="og:image" content="/imagenes/<?php echo s($comercio->logo); ?>">
        <link rel="icon" href="/imagenes/<?php echo s($comercio->logo); ?>" type="image/webp">
        <link rel="apple-touch-icon" href="/imagenes/<?php echo s($comercio->logo); ?>">
    <?php endif; ?>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <?php if($tieneMapa): ?>
        <!-- Leaflet CSS -->
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <?php endif; ?>

    <!-- Hoja de estilos compilada con Sass/Gulp -->
    <link rel="stylesheet" href="/build/css/app.css?v=<?php echo file_exists(__DIR__ . '/../public/build/css/app.css') ? filemtime(__DIR__ . '/../public/build/css/app.css') : '1.0'; ?>">
</head>

<?php if($mostrarSidebar): ?>
    <!-- ============================================================= -->
    <!-- LAYOUT DE PANEL AUTENTICADO CON SIDEBAR LATERAL DESPLEGABLE -->
    <!-- ============================================================= -->
    <body class="tipo-panel">
        <div class="panel-layout" id="panel-layout">
            
            <!-- MENÚ LATERAL -->
            <?php include __DIR__ . '/templates/panel-sidebar.php'; ?>

            <!-- OVERLAY OSCURO PARA DISPOSITIVOS MÓVILES -->
            <div class="panel-sidebar-backdrop" id="panel-sidebar-backdrop"></div>

            <!-- CONTENEDOR PRINCIPAL DEL PANEL -->
            <div class="panel-contenido">
                
                <!-- BARRA SUPERIOR DEL PANEL -->
                <header class="panel-topbar">
                    <div class="panel-topbar__izquierda">
                        <button type="button" class="panel-topbar__btn-toggle" id="panel-topbar-toggle" title="Abrir o cerrar menú">
                            <i class="fa-solid fa-bars"></i>
                        </button>
                        <h2 class="panel-topbar__titulo">
                            <span><?php echo s($titulo); ?></span>
                        </h2>
                    </div>

                    <div class="panel-topbar__derecha">
                        <a href="/menu" class="panel-topbar__btn-tienda" title="Ver Tienda Online" target="_blank">
                            <i class="fa-solid fa-store"></i>
                            <span>Ver Menú</span>
                        </a>

                        <div class="panel-topbar__user">
                            <div class="panel-topbar__user-avatar">
                                <?php echo strtoupper(mb_substr($nombre, 0, 1)); ?>
                            </div>
                            <span class="panel-topbar__user-nombre"><?php echo s($nombre); ?></span>
                            <a href="/logout" class="panel-topbar__btn-logout" title="Cerrar Sesión">
                                <i class="fa-solid fa-arrow-right-from-bracket"></i>
                            </a>
                        </div>
                    </div>
                </header>

                <!-- VISTA ACTIVA -->
                <main class="panel-main">
                    <?php echo $contenido; ?>
                </main>

                <!-- PIE MINIMALISTA DEL PANEL -->
                <footer class="panel-footer">
                    <span>&copy; <?php echo date('Y'); ?> <strong><?php echo s($nombreComercio); ?></strong> — Sistema de Gestión Gastronómica & Delivery</span>
                </footer>
            </div>
        </div>

        <?php if($tieneMapa): ?>
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <?php endif; ?>
        <script src="/build/js/app.js?v=<?php echo file_exists(__DIR__ . '/../public/build/js/app.js') ? filemtime(__DIR__ . '/../public/build/js/app.js') : '1.0'; ?>"></script>
    </body>

<?php else: ?>
    <!-- ============================================================= -->
    <!-- LAYOUT PÚBLICO (HOME, MENÚ, LOGIN, CHECKOUT)                  -->
    <!-- ============================================================= -->
    <body class="tipo-<?php echo s($tipo); ?>">

        <!-- BANNER DE PEDIDO ACTIVO PARA CLIENTES INVITADOS -->
        <aside id="banner-pedido-activo" class="banner-pedido-activo banner-pedido-activo--oculto" aria-label="Aviso de pedido en curso">
            <div class="contenedor banner-pedido-activo__contenido">
                <div class="banner-pedido-activo__info">
                    <span class="banner-pedido-activo__pulso"></span>
                    <i class="fa-solid fa-motorcycle"></i>
                    <span>Tenés un pedido en curso: <strong id="banner-pedido-num">#0</strong></span>
                    <span id="banner-pedido-estado" class="badge badge--alerta" style="font-size: 1.1rem; padding: 0.2rem 0.8rem; margin-left: 0.6rem;">En preparación</span>
                </div>
                <div class="banner-pedido-activo__acciones">
                    <a id="banner-pedido-enlace" href="/pedido/seguimiento" class="banner-pedido-activo__boton">
                        <span>Ver Seguimiento en Vivo</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                    <button type="button" id="banner-pedido-cerrar" class="banner-pedido-activo__cerrar" aria-label="Ocultar aviso" title="Ocultar aviso">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>
        </aside>

        <!-- CABECERA PÚBLICA SUPERIOR -->
        <header class="header">
            <div class="contenedor header__contenido">
                <a href="/" class="header__logo" title="<?php echo s($nombreComercio); ?>">
                    <?php if($tieneLogo): ?>
                        <img src="/imagenes/<?php echo s($comercio->logo); ?>" alt="<?php echo s($nombreComercio); ?>" class="header__logo-img">
                    <?php else: ?>
                        <i class="fa-solid fa-utensils header__logo-icono"></i>
                    <?php endif; ?>
                    <span class="header__logo-texto"><?php echo s($nombreComercio); ?></span>
                </a>

                <!-- BOTÓN HAMBURGUESA PARA DISPOSITIVOS MÓVILES -->
                <button type="button" class="header__hamburguesa" id="header-hamburguesa" aria-label="Abrir menú de navegación" aria-expanded="false">
                    <i class="fa-solid fa-bars"></i>
                </button>

                <nav class="navegacion" id="header-navegacion">
                    <a href="/" class="navegacion__enlace <?php echo $esActivo('/'); ?>">Inicio</a>
                    <a href="/menu" class="navegacion__enlace <?php echo $esActivo('/menu'); ?>">Menú</a>
                    
                    <?php if($login): ?>
                        <a href="<?php echo $enlacePanel; ?>" class="navegacion__enlace <?php echo $esActivo($enlacePanel); ?>">
                            <i class="fa-solid fa-gauge" style="margin-right: 0.4rem; color: #ea580c;"></i>
                            <span>Panel</span>
                        </a>
                        <a href="/logout" class="navegacion__enlace navegacion__enlace--logout">
                            <i class="fa-solid fa-arrow-right-from-bracket" style="margin-right: 0.4rem;"></i>
                            <span>Cerrar Sesión</span>
                        </a>
                    <?php else: ?>
                        <a href="/login" class="navegacion__enlace <?php echo $esActivo('/login'); ?>">Ingresar</a>
                        <a href="/crear-cuenta" class="boton boton--primario boton--pequeno navegacion__boton">Registrarse</a>
                    <?php endif; ?>
                </nav>
            </div>
        </header>

        <main class="principal">
            <?php echo $contenido; ?>
        </main>

        <?php if(($tipo ?? '') === 'publico' && ($urlActual ?? '') !== '/checkout'): ?>
            <?php include_once __DIR__ . '/templates/carrito.php'; ?>
        <?php endif; ?>

        <footer class="footer">
            <div class="contenedor footer__contenido">
                <p class="footer__copyright">
                    &copy; <?php echo date('Y'); ?> <span><?php echo s($nombreComercio); ?></span> — Sistema de Gestión y Delivery
                </p>
            </div>
        </footer>

        <?php if($tieneMapa): ?>
            <!-- Leaflet JS -->
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <?php endif; ?>

        <!-- Script centralizado y compilado con Terser/Gulp -->
        <script src="/build/js/app.js?v=<?php echo file_exists(__DIR__ . '/../public/build/js/app.js') ? filemtime(__DIR__ . '/../public/build/js/app.js') : '1.0'; ?>"></script>
    </body>
<?php endif; ?>
</html>
