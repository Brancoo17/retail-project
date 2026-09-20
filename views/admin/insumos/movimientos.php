<div class="contenedor seccion">
    <div class="admin-encabezado">
        <a href="/admin/insumos" class="boton boton--secundario boton--pequeno">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Volver a Insumos</span>
        </a>
        <h1 class="seccion__titulo">Historial y Trazabilidad de Stock</h1>
        <p class="seccion__subtitulo">Auditoría completa de compras, ventas, mermas, cancelaciones y ajustes del inventario.</p>

        <div class="admin-encabezado__acciones">
            <a href="/admin/insumos/movimientos/crear" class="boton boton--primario">
                <i class="fa-solid fa-plus"></i>
                <span>Registrar Nuevo Movimiento</span>
            </a>
        </div>
    </div>

    <?php include_once __DIR__ . '/../../templates/alertas.php'; ?>

    <!-- FILTROS DE AUDITORÍA -->
    <form class="formulario-inline u-separador-abajo" method="GET" action="/admin/insumos/movimientos">
        <div class="formulario__campo formulario-inline__campo">
            <select name="insumo_id" class="formulario__input">
                <option value="">-- Todos los Insumos --</option>
                <?php foreach($insumos as $ins): ?>
                    <option 
                        value="<?php echo (int)$ins->id; ?>" 
                        <?php echo ($insumoSeleccionado === (int)$ins->id) ? 'selected' : ''; ?>
                    >
                        <?php echo s($ins->nombre); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="formulario__campo formulario-inline__campo">
            <select name="tipo" class="formulario__input">
                <option value="">-- Todos los Tipos --</option>
                <?php foreach($tipos as $clave => $etiqueta): ?>
                    <option 
                        value="<?php echo s($clave); ?>" 
                        <?php echo ($tipoSeleccionado === $clave) ? 'selected' : ''; ?>
                    >
                        <?php echo s($etiqueta); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="boton boton--primario">
            <i class="fa-solid fa-filter"></i>
            <span>Filtrar</span>
        </button>

        <?php if(!empty($insumoSeleccionado) || !empty($tipoSeleccionado)): ?>
            <a href="/admin/insumos/movimientos" class="boton boton--secundario" title="Limpiar filtros">
                <i class="fa-solid fa-xmark"></i>
                <span>Limpiar</span>
            </a>
        <?php endif; ?>
    </form>

    <!-- TABLA DE MOVIMIENTOS -->
    <?php if(empty($movimientos)): ?>
        <div class="admin-tarjeta card-modulo--vacio">
            <i class="fa-solid fa-clock-rotate-left card-modulo--vacio__icono"></i>
            <h3>No se encontraron movimientos registrados</h3>
            <p>Los movimientos se generan automáticamente con las compras, mermas, ventas o ajustes manuales.</p>
        </div>
    <?php else: ?>
        <div class="tabla-contenedor">
            <table class="tabla">
                <thead class="tabla__thead">
                    <tr>
                        <th class="tabla__th">Fecha / Hora</th>
                        <th class="tabla__th">Insumo</th>
                        <th class="tabla__th">Tipo</th>
                        <th class="tabla__th">Cantidad</th>
                        <th class="tabla__th">Motivo</th>
                        <th class="tabla__th">Responsable</th>
                    </tr>
                </thead>
                <tbody class="tabla__tbody">
                    <?php foreach($movimientos as $mov): ?>
                        <tr class="tabla__tr">
                            <td class="tabla__td">
                                <small class="tabla__unidad"><?php echo s(date('d/m/Y H:i', strtotime($mov->fecha ?? 'now'))); ?></small>
                            </td>
                            <td class="tabla__td">
                                <strong class="u-texto-negrita"><?php echo s($mov->insumo_nombre ?? 'Insumo'); ?></strong>
                            </td>
                            <td class="tabla__td">
                                <span class="badge <?php echo $mov->badgeClase(); ?>">
                                    <?php echo $mov->badgeTexto(); ?>
                                </span>
                            </td>
                            <td class="tabla__td">
                                <span class="precio-destacado">
                                    <?php echo s($mov->signoCantidad()); ?><?php echo s($mov->cantidadFormateada()); ?>
                                </span>
                                <small class="tabla__unidad"><?php echo s($mov->unidad_medida ?? ''); ?></small>
                            </td>
                            <td class="tabla__td">
                                <span><?php echo s($mov->motivo ?? '-'); ?></span>
                            </td>
                            <td class="tabla__td">
                                <small class="tabla__unidad">
                                    <i class="fa-solid fa-user"></i>
                                    <span><?php echo s($mov->usuario_nombre ?? 'Sistema / Admin'); ?></span>
                                </small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
