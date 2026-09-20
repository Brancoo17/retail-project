<div class="contenedor seccion">
    <header class="seccion__encabezado">
        <span class="badge badge--destacado">Rol: Dueño / Administrador</span>
        <h1 class="seccion__titulo">Panel de Administración</h1>
        <p class="seccion__subtitulo">Bienvenido/a, <?php echo s($_SESSION['nombre'] ?? 'Administrador'); ?>. Aquí gestionarás el comercio, catálogo, insumos y reportes.</p>
    </header>

    <div class="grid grid--3">
        <!-- 1. Cocina (KDS) -->
        <a href="/cocina" class="card-modulo card-modulo--enlace">
            <div class="card-modulo__icono card-modulo__icono--pedidos">
                <i class="fa-solid fa-fire-burner"></i>
            </div>
            <h3 class="card-modulo__titulo">Cocina & Comandas (KDS)</h3>
            <p class="card-modulo__texto">Consola operativa en tiempo real para preparación de pedidos, comandas y despacho.</p>
        </a>

        <!-- 2. Caja Diario -->
        <a href="/cocina/caja" class="card-modulo card-modulo--enlace">
            <div class="card-modulo__icono card-modulo__icono--caja">
                <i class="fa-solid fa-cash-register"></i>
            </div>
            <h3 class="card-modulo__titulo">Control de Caja Diario</h3>
            <p class="card-modulo__texto">Apertura y cierre de turnos, arqueo de efectivo y rendición de repartidores.</p>
        </a>

        <!-- 3. Catálogo de Productos -->
        <a href="/admin/productos" class="card-modulo card-modulo--enlace">
            <div class="card-modulo__icono card-modulo__icono--pedidos">
                <i class="fa-solid fa-burger"></i>
            </div>
            <h3 class="card-modulo__titulo">Catálogo de Productos</h3>
            <p class="card-modulo__texto">Gestiona los platos, precios, fotos WebP y opciones del menú.</p>
        </a>

        <!-- 4. Categorías del Menú -->
        <a href="/admin/categorias" class="card-modulo card-modulo--enlace">
            <div class="card-modulo__icono" style="background-color: #f3e8ff; color: #9333ea;">
                <i class="fa-solid fa-tags"></i>
            </div>
            <h3 class="card-modulo__titulo">Categorías del Menú</h3>
            <p class="card-modulo__texto">Organiza tu carta en secciones (Pizzas, Hamburguesas, Bebidas...).</p>
        </a>

        <!-- 5. Salsas & Aderezos -->
        <a href="/admin/aderezos" class="card-modulo card-modulo--enlace">
            <div class="card-modulo__icono" style="background-color: #ffedd5; color: #ea580c;">
                <i class="fa-solid fa-bottle-droplet"></i>
            </div>
            <h3 class="card-modulo__titulo">Salsas & Aderezos</h3>
            <p class="card-modulo__texto">Gestiona las salsas y aderezos gratuitos para sándwiches y hamburguesas.</p>
        </a>

        <!-- 6. Insumos & Recetas -->
        <a href="/admin/insumos" class="card-modulo card-modulo--enlace">
            <div class="card-modulo__icono card-modulo__icono--stock">
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>
            <h3 class="card-modulo__titulo">Insumos & Recetas</h3>
            <p class="card-modulo__texto">Control de stock de materia prima, matriz de recetas y trazabilidad de movimientos.</p>
            <?php 
                $alertasStock = ((int)($estadisticasInsumos['bajos'] ?? 0)) + ((int)($estadisticasInsumos['agotados'] ?? 0));
                if($alertasStock > 0): 
            ?>
                <span class="badge badge--alerta u-separador-abajo-sm">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span><?php echo $alertasStock; ?> alerta<?php echo ($alertasStock > 1) ? 's' : ''; ?> de stock</span>
                </span>
            <?php endif; ?>
        </a>

        <!-- 7. Movimientos de Stock -->
        <a href="/admin/insumos/movimientos" class="card-modulo card-modulo--enlace">
            <div class="card-modulo__icono" style="background-color: #e0f2fe; color: #0284c7;">
                <i class="fa-solid fa-arrow-right-arrow-left"></i>
            </div>
            <h3 class="card-modulo__titulo">Movimientos de Stock</h3>
            <p class="card-modulo__texto">Historial y trazabilidad de ingresos, egresos, mermas y ajustes de inventario.</p>
        </a>

        <!-- 8. Repartidores -->
        <a href="/admin/repartidores" class="card-modulo card-modulo--enlace">
            <div class="card-modulo__icono" style="background-color: #fef3c7; color: #d97706;">
                <i class="fa-solid fa-motorcycle"></i>
            </div>
            <h3 class="card-modulo__titulo">Equipo de Repartidores</h3>
            <p class="card-modulo__texto">Administra los repartidores, altas de cuenta, pedidos en calle y efectivo pendiente.</p>
        </a>

        <!-- 9. Personal de Cocina & Caja -->
        <a href="/admin/empleados" class="card-modulo card-modulo--enlace">
            <div class="card-modulo__icono" style="background-color: #fdf2f8; color: #db2777;">
                <i class="fa-solid fa-kitchen-set"></i>
            </div>
            <h3 class="card-modulo__titulo">Personal de Cocina & Caja</h3>
            <p class="card-modulo__texto">Gestiona las cuentas de los cocineros y cajeros con acceso al KDS y control de caja.</p>
        </a>

        <!-- 10. Reportes & Métricas -->
        <a href="/admin/reportes" class="card-modulo card-modulo--enlace">
            <div class="card-modulo__icono" style="background-color: #ecfdf5; color: #10b981;">
                <i class="fa-solid fa-chart-line"></i>
            </div>
            <h3 class="card-modulo__titulo">Reportes & Métricas</h3>
            <p class="card-modulo__texto">Ventas por período, platos más pedidos, balance de insumos y rendimiento de repartidores.</p>
        </a>

        <!-- 11. Configuración del Local -->
        <a href="/admin/configuracion" class="card-modulo card-modulo--enlace">
            <div class="card-modulo__icono" style="background-color: #f1f5f9; color: #475569;">
                <i class="fa-solid fa-gear"></i>
            </div>
            <h3 class="card-modulo__titulo">Configuración del Local</h3>
            <p class="card-modulo__texto">Fija la ubicación en el mapa, radio de entrega, costos por km y políticas.</p>
        </a>
    </div>
</div>
