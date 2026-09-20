<div class="contenedor seccion">
    <div class="admin-encabezado">
        <a href="/admin/productos" class="boton boton--secundario boton--pequeno">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Volver al Catálogo</span>
        </a>
        <h1 class="seccion__titulo">Editar Producto</h1>
        <p class="seccion__subtitulo">Modifica la información general, administra variantes y configura la receta de insumos.</p>
    </div>

    <?php include_once __DIR__ . '/../../templates/alertas.php'; ?>

    <!-- FORMULARIO PRINCIPAL DE EDICIÓN DEL PRODUCTO -->
    <form class="formulario formulario--admin" method="POST" enctype="multipart/form-data">
        <fieldset class="admin-tarjeta">
            <legend class="admin-tarjeta__titulo">
                <i class="fa-solid fa-pen-to-square"></i>
                <span>Información del Producto</span>
            </legend>

            <?php include_once __DIR__ . '/formulario.php'; ?>
        </fieldset>

        <div class="formulario__acciones-admin">
            <button type="submit" class="boton boton--primario">
                <i class="fa-solid fa-floppy-disk"></i>
                <span>Actualizar Producto</span>
            </button>
        </div>
    </form>

    <!-- SECCIÓN DE MATRIZ DE RECETA / INSUMOS (ETAPA 3) -->
    <div class="formulario--admin u-separador-arriba">
        <fieldset class="admin-tarjeta">
            <legend class="admin-tarjeta__titulo">
                <i class="fa-solid fa-mortar-pestle"></i>
                <span>Receta de Cocina (Consumo de Insumos)</span>
            </legend>
            <p class="admin-tarjeta__descripcion">
                Define la cantidad de materia prima que se consumirá y descontará automáticamente del stock por cada unidad vendida de este plato.
            </p>

            <?php if(!empty($receta)): ?>
                <div class="tabla-contenedor u-separador-abajo">
                    <table class="tabla">
                        <thead class="tabla__thead">
                            <tr>
                                <th class="tabla__th">Ingrediente / Insumo</th>
                                <th class="tabla__th">Consumo por Unidad</th>
                                <th class="tabla__th">Stock en Depósito</th>
                                <th class="tabla__th tabla__th--acciones">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="tabla__tbody">
                            <?php foreach($receta as $item): ?>
                                <tr class="tabla__tr">
                                    <td class="tabla__td">
                                        <strong class="u-texto-negrita"><?php echo s($item->insumo_nombre ?? 'Insumo'); ?></strong>
                                    </td>
                                    <td class="tabla__td">
                                        <span class="precio-destacado"><?php echo s($item->consumoFormateado()); ?></span>
                                        <small class="tabla__unidad"><?php echo s($item->unidad_medida ?? ''); ?></small>
                                    </td>
                                    <td class="tabla__td">
                                        <span><?php echo s($item->stockActualFormateado()); ?></span>
                                        <small class="tabla__unidad"><?php echo s($item->unidad_medida ?? ''); ?></small>
                                    </td>
                                    <td class="tabla__td tabla__td--acciones">
                                        <form method="POST" action="/admin/productos/receta/eliminar" onsubmit="return confirm('¿Quitar este ingrediente de la receta?');">
                                            <input type="hidden" name="id" value="<?php echo (int)$item->id; ?>">
                                            <button type="submit" class="boton-accion boton-accion--eliminar" title="Eliminar de la receta">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="admin-tarjeta card-modulo--vacio u-separador-abajo">
                    <p class="card-modulo__texto">Este producto aún no tiene ingredientes vinculados a su receta de preparación.</p>
                </div>
            <?php endif; ?>

            <!-- FORMULARIO INLINE PARA AGREGAR INSUMO A LA RECETA -->
            <form class="formulario-inline" method="POST" action="/admin/productos/receta/agregar">
                <input type="hidden" name="producto_id" value="<?php echo (int)$producto->id; ?>">

                <div class="formulario__campo formulario__campo--flex-2">
                    <label class="formulario__label" for="receta_insumo_id">Seleccionar Insumo</label>
                    <select id="receta_insumo_id" name="insumo_id" class="formulario__input" required>
                        <option value="">-- Elige un ingrediente --</option>
                        <?php foreach($insumos as $ins): ?>
                            <option value="<?php echo (int)$ins->id; ?>">
                                <?php echo s($ins->nombre); ?> (<?php echo s($ins->unidad_medida); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="formulario__campo formulario__campo--flex-1">
                    <label class="formulario__label" for="receta_cantidad">Cantidad Consumida</label>
                    <input 
                        type="number" 
                        step="any" 
                        min="0.001" 
                        id="receta_cantidad" 
                        name="cantidad_consumo" 
                        class="formulario__input" 
                        placeholder="ej. 0,250 o 1" 
                        required
                    >
                </div>

                <div class="formulario-inline__accion-submit">
                    <button type="submit" class="boton boton--primario">
                        <i class="fa-solid fa-plus"></i>
                        <span>Agregar a Receta</span>
                    </button>
                </div>
            </form>
        </fieldset>
    </div>

    <!-- SECCIÓN DE VARIANTES Y OPCIONES (PASO 2.4) -->
    <div class="formulario--admin u-separador-arriba">
        <fieldset class="admin-tarjeta">
            <legend class="admin-tarjeta__titulo">
                <i class="fa-solid fa-layer-group"></i>
                <span>Variantes, Tamaños y Opciones Adicionales</span>
            </legend>
            <p class="admin-tarjeta__descripcion">
                Permite al cliente personalizar este producto al pedirlo (ej: "Grande +$0", "Familiar +$2500", "Extra Muzzarella +$800", "Sin Cebolla +$0").
            </p>

            <?php if(!empty($opciones)): ?>
                <div class="tabla-contenedor u-separador-abajo">
                    <table class="tabla">
                        <thead class="tabla__thead">
                            <tr>
                                <th class="tabla__th">Opción / Variante</th>
                                <th class="tabla__th">Costo Adicional</th>
                                <th class="tabla__th tabla__th--acciones">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="tabla__tbody">
                            <?php foreach($opciones as $opc): ?>
                                <tr class="tabla__tr">
                                    <td class="tabla__td">
                                        <strong class="u-texto-negrita"><?php echo s($opc->nombre); ?></strong>
                                    </td>
                                    <td class="tabla__td">
                                        <?php if((float)$opc->precio_adicional > 0): ?>
                                            <span class="badge badge--destacado">+ $<?php echo number_format($opc->precio_adicional, 2, ',', '.'); ?></span>
                                        <?php else: ?>
                                            <span class="badge badge--exito">Sin costo extra ($0)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="tabla__td tabla__td--acciones">
                                        <form method="POST" action="/admin/productos/opciones/eliminar" onsubmit="return confirm('¿Eliminar esta opción?');">
                                            <input type="hidden" name="id" value="<?php echo s((string)$opc->id); ?>">
                                            <button type="submit" class="boton-accion boton-accion--eliminar" title="Eliminar Opción">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="admin-tarjeta card-modulo--vacio u-separador-abajo">
                    <p class="card-modulo__texto">Este producto aún no tiene variantes ni adicionales configurados.</p>
                </div>
            <?php endif; ?>

            <!-- FORMULARIO INLINE PARA AGREGAR NUEVA OPCIÓN -->
            <form class="formulario-inline" method="POST" action="/admin/productos/opciones/crear">
                <input type="hidden" name="producto_id" value="<?php echo s((string)$producto->id); ?>">
                
                <div class="formulario__campo formulario__campo--flex-2">
                    <label class="formulario__label" for="opcion_nombre">Nombre de la Variante / Extra</label>
                    <input 
                        type="text" 
                        id="opcion_nombre" 
                        name="opcion[nombre]" 
                        class="formulario__input" 
                        placeholder="Ej: Extra Queso, Tamaño Familiar, Doble Carne..."
                        required
                    >
                </div>

                <div class="formulario__campo formulario__campo--flex-1">
                    <label class="formulario__label" for="opcion_precio">Precio Adicional ($)</label>
                    <input 
                        type="number" 
                        step="0.01" 
                        min="0" 
                        id="opcion_precio" 
                        name="opcion[precio_adicional]" 
                        class="formulario__input" 
                        placeholder="0.00"
                        value="0"
                        required
                    >
                </div>

                <div class="formulario-inline__accion-submit">
                    <button type="submit" class="boton boton--secundario">
                        <i class="fa-solid fa-plus"></i>
                        <span>Agregar Opción</span>
                    </button>
                </div>
            </form>
        </fieldset>
    </div>
</div>
