<div class="contenedor seccion">
    <div class="admin-encabezado">
        <a href="/admin/categorias" class="boton boton--secundario boton--pequeno">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Volver a Categorías</span>
        </a>
        <div class="admin-encabezado__info">
            <h1 class="admin-encabezado__titulo">Salsas & Aderezos</h1>
            <p class="admin-encabezado__subtitulo">Catálogo de salsas y aderezos gratuitos para sándwiches, lomitos, hamburguesas y minutas.</p>
        </div>
    </div>

    <div class="admin-alerta-info u-separador-abajo" style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 0.8rem; padding: 1.2rem 1.6rem; display: flex; align-items: center; gap: 1rem; color: #1e40af; font-size: 1.3rem;">
        <i class="fa-solid fa-circle-info" style="font-size: 1.8rem;"></i>
        <span>Los aderezos activos aquí configurados se ofrecerán <strong>sin costo adicional</strong> a los clientes en todas aquellas categorías que tengan marcado el tilde <em>"Habilitar selección de aderezos"</em>.</span>
    </div>

    <?php include_once __DIR__ . '/../../templates/alertas.php'; ?>

    <div class="grid grid--2-1" style="display: grid; grid-template-columns: 1fr; gap: 2rem; align-items: start;">
        
        <!-- LISTADO DE ADEREZOS -->
        <div class="admin-tarjeta">
            <h2 class="admin-tarjeta__titulo" style="font-size: 1.6rem; margin-bottom: 1.4rem;">
                <i class="fa-solid fa-bottle-droplet" style="color: #ea580c;"></i>
                <span>Aderezos Registrados (<?php echo count($aderezos); ?>)</span>
            </h2>

            <?php if(empty($aderezos)): ?>
                <p style="color: #64748b; font-size: 1.3rem;">No hay aderezos registrados aún. Agrega el primero con el formulario de la derecha.</p>
            <?php else: ?>
                <div class="tabla-contenedor">
                    <table class="tabla">
                        <thead class="tabla__thead">
                            <tr>
                                <th class="tabla__th">Orden</th>
                                <th class="tabla__th">Nombre</th>
                                <th class="tabla__th">Estado</th>
                                <th class="tabla__th tabla__th--acciones">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="tabla__tbody">
                            <?php foreach($aderezos as $ad): ?>
                                <tr class="tabla__tr">
                                    <td class="tabla__td tabla__td--orden">
                                        <span class="badge badge--orden"><?php echo (int)$ad->orden; ?></span>
                                    </td>
                                    <td class="tabla__td">
                                        <strong style="font-size: 1.4rem; color: #1e293b;"><?php echo s($ad->nombre); ?></strong>
                                    </td>
                                    <td class="tabla__td">
                                        <form method="POST" action="/admin/aderezos/actualizar" style="display: inline;">
                                            <input type="hidden" name="id" value="<?php echo (int)$ad->id; ?>">
                                            <input type="hidden" name="toggle_activo" value="1">
                                            <?php if((int)$ad->activo === 1): ?>
                                                <button type="submit" class="badge badge--exito" style="cursor: pointer; border: none;" title="Clic para desactivar temporalmente">
                                                    <i class="fa-solid fa-check"></i> Disponible
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" class="badge badge--error" style="cursor: pointer; border: none;" title="Clic para habilitar">
                                                    <i class="fa-solid fa-ban"></i> Agotado / Inactivo
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                    <td class="tabla__td tabla__td--acciones">
                                        <form method="POST" action="/admin/aderezos/eliminar" onsubmit="return confirm('¿Seguro que deseas eliminar este aderezo?');" style="display: inline;">
                                            <input type="hidden" name="id" value="<?php echo (int)$ad->id; ?>">
                                            <button type="submit" class="boton-accion boton-accion--eliminar" title="Eliminar Aderezo">
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

        <!-- FORMULARIO NUEVO ADEREZO -->
        <div class="admin-tarjeta">
            <h2 class="admin-tarjeta__titulo" style="font-size: 1.6rem; margin-bottom: 1.4rem;">
                <i class="fa-solid fa-plus-circle" style="color: #16a34a;"></i>
                <span>Nuevo Aderezo / Salsa</span>
            </h2>

            <form method="POST" action="/admin/aderezos/crear">
                <div class="formulario__campo">
                    <label for="nombre" class="formulario__label">Nombre de la Salsa o Aderezo: *</label>
                    <input 
                        type="text" 
                        name="nombre" 
                        id="nombre" 
                        class="formulario__input" 
                        placeholder="Ej. Salsa Criolla, Tártara, Picante..." 
                        required
                        autocomplete="off"
                    >
                </div>

                <div class="formulario__campo">
                    <label for="orden" class="formulario__label">Orden de aparición:</label>
                    <input 
                        type="number" 
                        name="orden" 
                        id="orden" 
                        class="formulario__input" 
                        value="<?php echo count($aderezos) + 1; ?>"
                        min="0"
                    >
                    <span class="formulario__ayuda">Los aderezos con menor número aparecen primero en la lista.</span>
                </div>

                <button type="submit" class="boton boton--primario u-ancho-completo" style="margin-top: 1.2rem;">
                    <i class="fa-solid fa-check"></i>
                    <span>Registrar Aderezo</span>
                </button>
            </form>
        </div>

    </div>
</div>
