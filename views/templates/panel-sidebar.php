<?php
    $comercio = $comercio ?? obtenerComercio();
    $nombreComercio = $comercio->nombre ?? 'Retail Delivery';
    $tieneLogo = (!empty($comercio->logo) && file_exists(CARPETA_IMAGENES . $comercio->logo));
    $nombreUsuario = $_SESSION['nombre'] ?? 'Usuario';
    $rolUsuario = $_SESSION['rol'] ?? 'cliente';
    $urlActual = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

    $esActivo = function(string $ruta) use ($urlActual): string {
        if($ruta === '/' || $ruta === '/admin' || $ruta === '/cocina' || $ruta === '/repartidor' || $ruta === '/cliente') {
            return ($urlActual === $ruta) ? 'panel-nav__link--activo' : '';
        }
        return str_starts_with($urlActual, $ruta) ? 'panel-nav__link--activo' : '';
    };

    $badgeRol = match($rolUsuario) {
        'dueño' => ['texto' => 'Administrador', 'clase' => 'badge--admin'],
        'empleado' => ['texto' => 'Cocina & Caja', 'clase' => 'badge--cocina'],
        'repartidor' => ['texto' => 'Repartidor', 'clase' => 'badge--reparto'],
        default => ['texto' => 'Cliente', 'clase' => 'badge--cliente']
    };
?>

<aside class="panel-sidebar" id="panel-sidebar">
    <!-- CABECERA DEL SIDEBAR -->
    <div class="panel-sidebar__header">
        <a href="<?php echo ($rolUsuario === 'dueño') ? '/admin' : (($rolUsuario === 'empleado') ? '/cocina' : (($rolUsuario === 'repartidor') ? '/repartidor' : '/cliente')); ?>" class="panel-sidebar__logo" title="<?php echo s($nombreComercio); ?>">
            <?php if($tieneLogo): ?>
                <img src="/imagenes/<?php echo s($comercio->logo); ?>" alt="<?php echo s($nombreComercio); ?>" class="panel-sidebar__logo-img">
            <?php else: ?>
                <div class="panel-sidebar__logo-icono">
                    <i class="fa-solid fa-utensils"></i>
                </div>
            <?php endif; ?>
            <span class="panel-sidebar__logo-texto"><?php echo s($nombreComercio); ?></span>
        </a>

        <!-- Botón para colapsar en Desktop -->
        <button type="button" class="panel-sidebar__btn-colapsar" id="btn-colapsar-sidebar" title="Contraer o expandir menú lateral">
            <i class="fa-solid fa-chevron-left"></i>
        </button>

        <!-- Botón para cerrar en Mobile -->
        <button type="button" class="panel-sidebar__btn-cerrar-movil" id="btn-cerrar-sidebar-movil" title="Cerrar menú">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <!-- FICHA DE USUARIO -->
    <div class="panel-sidebar__usuario">
        <a href="/cuenta" class="panel-sidebar__usuario-link" title="Mi Cuenta / Configuración de Perfil">
            <div class="panel-sidebar__usuario-avatar">
                <?php echo strtoupper(mb_substr($nombreUsuario, 0, 1)); ?>
            </div>
            <div class="panel-sidebar__usuario-info">
                <span class="panel-sidebar__usuario-nombre" title="<?php echo s($nombreUsuario); ?>"><?php echo s($nombreUsuario); ?></span>
                <span class="panel-sidebar__usuario-rol badge <?php echo $badgeRol['clase']; ?>"><?php echo $badgeRol['texto']; ?></span>
            </div>
        </a>
        <a href="/cuenta" class="panel-sidebar__usuario-config <?php echo ($urlActual === '/cuenta') ? 'panel-sidebar__usuario-config--activo' : ''; ?>" title="Configuración de Cuenta">
            <i class="fa-solid fa-gear"></i>
        </a>
    </div>

    <!-- NAVEGACIÓN SEGÚN EL ROL -->
    <nav class="panel-nav">
        <div class="panel-nav__seccion">
            <span class="panel-nav__seccion-titulo">Menú Principal</span>

            <?php if($rolUsuario === 'dueño'): ?>
                <!-- ROLES: DUEÑO / ADMINISTRADOR -->
                <a href="/admin" class="panel-nav__link <?php echo $esActivo('/admin'); ?>" title="Panel General">
                    <i class="fa-solid fa-gauge panel-nav__icono"></i>
                    <span class="panel-nav__texto">Panel General</span>
                </a>
                <a href="/cocina" class="panel-nav__link <?php echo $esActivo('/cocina'); ?>" title="Cocina & Comandas (KDS)">
                    <i class="fa-solid fa-fire-burner panel-nav__icono"></i>
                    <span class="panel-nav__texto">Cocina (KDS)</span>
                </a>
                <a href="/cocina/caja" class="panel-nav__link <?php echo $esActivo('/cocina/caja'); ?>" title="Control de Caja Diario">
                    <i class="fa-solid fa-cash-register panel-nav__icono"></i>
                    <span class="panel-nav__texto">Caja Diario</span>
                </a>
                <a href="/admin/productos" class="panel-nav__link <?php echo $esActivo('/admin/productos'); ?>" title="Catálogo de Productos">
                    <i class="fa-solid fa-burger panel-nav__icono"></i>
                    <span class="panel-nav__texto">Productos</span>
                </a>
                <a href="/admin/categorias" class="panel-nav__link <?php echo $esActivo('/admin/categorias'); ?>" title="Categorías del Menú">
                    <i class="fa-solid fa-tags panel-nav__icono"></i>
                    <span class="panel-nav__texto">Categorías</span>
                </a>
                <a href="/admin/aderezos" class="panel-nav__link <?php echo $esActivo('/admin/aderezos'); ?>" title="Salsas & Aderezos">
                    <i class="fa-solid fa-bottle-droplet panel-nav__icono"></i>
                    <span class="panel-nav__texto">Salsas & Aderezos</span>
                </a>
                <a href="/admin/insumos" class="panel-nav__link <?php echo $esActivo('/admin/insumos'); ?>" title="Insumos y Recetas">
                    <i class="fa-solid fa-boxes-stacked panel-nav__icono"></i>
                    <span class="panel-nav__texto">Insumos & Recetas</span>
                </a>
                <a href="/admin/insumos/movimientos" class="panel-nav__link <?php echo $esActivo('/admin/insumos/movimientos'); ?>" title="Movimientos de Stock">
                    <i class="fa-solid fa-arrow-right-arrow-left panel-nav__icono"></i>
                    <span class="panel-nav__texto">Movimientos Stock</span>
                </a>
                <a href="/admin/repartidores" class="panel-nav__link <?php echo $esActivo('/admin/repartidores'); ?>" title="Equipo de Repartidores">
                    <i class="fa-solid fa-motorcycle panel-nav__icono"></i>
                    <span class="panel-nav__texto">Repartidores</span>
                </a>
                <a href="/admin/empleados" class="panel-nav__link <?php echo $esActivo('/admin/empleados'); ?>" title="Personal de Cocina & Caja">
                    <i class="fa-solid fa-kitchen-set panel-nav__icono"></i>
                    <span class="panel-nav__texto">Personal Cocina</span>
                </a>
                <a href="/admin/reportes" class="panel-nav__link <?php echo $esActivo('/admin/reportes'); ?>" title="Reportes y Estadísticas">
                    <i class="fa-solid fa-chart-line panel-nav__icono"></i>
                    <span class="panel-nav__texto">Reportes & Métricas</span>
                </a>
                <a href="/admin/configuracion" class="panel-nav__link <?php echo $esActivo('/admin/configuracion'); ?>" title="Configuración del Local">
                    <i class="fa-solid fa-gear panel-nav__icono"></i>
                    <span class="panel-nav__texto">Configuración</span>
                </a>

            <?php elseif($rolUsuario === 'empleado'): ?>
                <!-- ROL: EMPLEADO (COCINA / CAJA) -->
                <a href="/cocina" class="panel-nav__link <?php echo $esActivo('/cocina'); ?>" title="Cocina (KDS)">
                    <i class="fa-solid fa-fire-burner panel-nav__icono"></i>
                    <span class="panel-nav__texto">Cocina (KDS)</span>
                </a>
                <a href="/cocina/caja" class="panel-nav__link <?php echo $esActivo('/cocina/caja'); ?>" title="Control de Caja Diario">
                    <i class="fa-solid fa-cash-register panel-nav__icono"></i>
                    <span class="panel-nav__texto">Caja Diario</span>
                </a>

            <?php elseif($rolUsuario === 'repartidor'): ?>
                <!-- ROL: REPARTIDOR -->
                <a href="/repartidor" class="panel-nav__link <?php echo $esActivo('/repartidor'); ?>" title="Mis Repartos">
                    <i class="fa-solid fa-motorcycle panel-nav__icono"></i>
                    <span class="panel-nav__texto">Mis Repartos</span>
                </a>

            <?php elseif($rolUsuario === 'cliente'): ?>
                <!-- ROL: CLIENTE -->
                <a href="/cliente" class="panel-nav__link <?php echo $esActivo('/cliente'); ?>" title="Mis Pedidos">
                    <i class="fa-solid fa-receipt panel-nav__icono"></i>
                    <span class="panel-nav__texto">Mis Pedidos</span>
                </a>
                <a href="/menu" class="panel-nav__link <?php echo $esActivo('/menu'); ?>" title="Hacer Pedido en Línea">
                    <i class="fa-solid fa-utensils panel-nav__icono"></i>
                    <span class="panel-nav__texto">Pedir en Línea</span>
                </a>
            <?php endif; ?>
        </div>

        <!-- ACCESOS RÁPIDOS Y CIERRE -->
        <div class="panel-nav__seccion panel-nav__seccion--pie">
            <span class="panel-nav__seccion-titulo">Enlaces</span>
            <a href="/" class="panel-nav__link" title="Ir a la Tienda Pública">
                <i class="fa-solid fa-store panel-nav__icono"></i>
                <span class="panel-nav__texto">Ver Tienda Online</span>
            </a>
            <a href="/logout" class="panel-nav__link panel-nav__link--logout" title="Cerrar Sesión">
                <i class="fa-solid fa-arrow-right-from-bracket panel-nav__icono"></i>
                <span class="panel-nav__texto">Cerrar Sesión</span>
            </a>
        </div>
    </nav>
</aside>
