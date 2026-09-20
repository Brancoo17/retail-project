<div class="contenedor seccion">
    <div class="admin-encabezado">
        <a href="/admin" class="boton boton--secundario boton--pequeno">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Volver al Panel</span>
        </a>
        <h1 class="seccion__titulo">Inventario de Insumos & Materia Prima</h1>
        <p class="seccion__subtitulo">Administra los ingredientes, controla niveles de stock en tiempo real y programa reposiciones.</p>

        <div class="admin-encabezado__acciones">
            <a href="/admin/insumos/crear" class="boton boton--primario">
                <i class="fa-solid fa-plus"></i>
                <span>Nuevo Insumo</span>
            </a>
            <a href="/admin/insumos/movimientos/crear" class="boton boton--secundario">
                <i class="fa-solid fa-right-left"></i>
                <span>Registrar Movimiento</span>
            </a>
            <a href="/admin/insumos/movimientos" class="boton boton--secundario">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span>Historial de Movimientos</span>
            </a>
        </div>
    </div>

    <?php include_once __DIR__ . '/../../templates/alertas.php'; ?>

    <!-- TARJETAS KPI RESUMEN DE STOCK -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-card__icono kpi-card__icono--total">
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>
            <div class="kpi-card__info">
                <span class="kpi-card__valor"><?php echo (int)($estadisticas['total'] ?? 0); ?></span>
                <span class="kpi-card__etiqueta">Total de Insumos Registrados</span>
            </div>
        </div>

        <div class="kpi-card kpi-card--alerta">
            <div class="kpi-card__icono kpi-card__icono--alerta">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div class="kpi-card__info">
                <span class="kpi-card__valor"><?php echo (int)($estadisticas['bajos'] ?? 0); ?></span>
                <span class="kpi-card__etiqueta">Insumos con Bajo Stock</span>
            </div>
        </div>

        <div class="kpi-card kpi-card--peligro">
            <div class="kpi-card__icono kpi-card__icono--peligro">
                <i class="fa-solid fa-ban"></i>
            </div>
            <div class="kpi-card__info">
                <span class="kpi-card__valor"><?php echo (int)($estadisticas['agotados'] ?? 0); ?></span>
                <span class="kpi-card__etiqueta">Insumos Agotados</span>
            </div>
        </div>
    </div>

    <!-- BUSCADOR / FILTRO -->
    <form class="formulario-inline u-separador-abajo" method="GET" action="/admin/insumos">
        <div class="formulario__campo formulario-inline__campo">
            <input 
                type="text" 
                name="s" 
                class="formulario__input" 
                placeholder="Buscar insumo por nombre..." 
                value="<?php echo s($busqueda); ?>"
            >
        </div>
        <button type="submit" class="boton boton--primario">
            <i class="fa-solid fa-magnifying-glass"></i>
            <span>Buscar</span>
        </button>
        <?php if(!empty($busqueda)): ?>
            <a href="/admin/insumos" class="boton boton--secundario" title="Limpiar filtro">
                <i class="fa-solid fa-xmark"></i>
                <span>Limpiar</span>
            </a>
        <?php endif; ?>
    </form>

    <!-- LISTADO DE INSUMOS -->
    <?php if(empty($insumos)): ?>
        <div class="admin-tarjeta card-modulo--vacio">
            <i class="fa-solid fa-boxes-stacked card-modulo--vacio__icono"></i>
            <h3>No se encontraron insumos</h3>
            <p>Comienza agregando los ingredientes de tu cocina para poder confeccionar las recetas de tus platos.</p>
            <a href="/admin/insumos/crear" class="boton boton--primario card-modulo--vacio__boton">
                <i class="fa-solid fa-plus"></i>
                <span>Agregar Primer Insumo</span>
            </a>
        </div>
    <?php else: ?>
        <div class="tabla-contenedor">
            <table class="tabla">
                <thead class="tabla__thead">
                    <tr>
                        <th class="tabla__th">Insumo</th>
                        <th class="tabla__th">Unidad</th>
                        <th class="tabla__th">Stock Actual</th>
                        <th class="tabla__th">Stock Mínimo</th>
                        <th class="tabla__th">Estado</th>
                        <th class="tabla__th tabla__th--acciones">Acciones</th>
                    </tr>
                </thead>
                <tbody class="tabla__tbody">
                    <?php foreach($insumos as $insumo): ?>
                        <tr class="tabla__tr">
                            <td class="tabla__td">
                                <strong class="u-texto-negrita"><?php echo s($insumo->nombre); ?></strong>
                            </td>
                            <td class="tabla__td">
                                <span class="badge badge--orden"><?php echo s(strtoupper($insumo->unidad_medida)); ?></span>
                            </td>
                            <td class="tabla__td">
                                <span class="precio-destacado">
                                    <?php echo s($insumo->stockActualFormateado()); ?>
                                </span>
                                <small class="tabla__unidad"><?php echo s($insumo->unidad_medida); ?></small>
                            </td>
                            <td class="tabla__td">
                                <span><?php echo s($insumo->stockMinimoFormateado()); ?></span>
                                <small class="tabla__unidad"><?php echo s($insumo->unidad_medida); ?></small>
                            </td>
                            <td class="tabla__td">
                                <span class="badge <?php echo $insumo->badgeClase(); ?>">
                                    <?php echo $insumo->badgeTexto(); ?>
                                </span>
                            </td>
                            <td class="tabla__td tabla__td--acciones">
                                <a 
                                    href="/admin/insumos/movimientos/crear?insumo_id=<?php echo (int)$insumo->id; ?>" 
                                    class="boton-accion" 
                                    title="Registrar movimiento / ajuste"
                                >
                                    <i class="fa-solid fa-right-left"></i>
                                </a>
                                <a 
                                    href="/admin/insumos/actualizar?id=<?php echo (int)$insumo->id; ?>" 
                                    class="boton-accion boton-accion--editar" 
                                    title="Editar insumo"
                                >
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <form method="POST" action="/admin/insumos/eliminar" onsubmit="return confirm('¿Seguro que deseas eliminar este insumo? Si forma parte de una receta, la acción será rechazada.');">
                                    <input type="hidden" name="id" value="<?php echo (int)$insumo->id; ?>">
                                    <button type="submit" class="boton-accion boton-accion--eliminar" title="Eliminar insumo">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
