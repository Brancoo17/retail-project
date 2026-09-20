<div class="contenedor seccion">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.8rem; flex-wrap: wrap; gap: 2rem;">
        <a href="/admin" class="boton boton--secundario">
            <i class="fa-solid fa-arrow-left"></i> Volver al Panel
        </a>
        <a href="/admin/empleados/crear" class="boton boton--primario">
            <i class="fa-solid fa-user-plus"></i> Registrar Nuevo Empleado
        </a>
    </div>

    <header class="seccion__encabezado u-separador-arriba-sm">
        <span class="badge badge--destacado">Gestión de Personal</span>
        <h1 class="seccion__titulo">Personal de Cocina & Caja</h1>
        <p class="seccion__subtitulo">
            Gestiona los usuarios autorizados para operar la consola de comandas (KDS en <code>/cocina</code>) y el control de caja diario (<code>/cocina/caja</code>).
        </p>
    </header>

    <?php if(isset($_SESSION['alerta_rapida'])): ?>
        <div class="alerta alerta--<?php echo $_SESSION['alerta_rapida']['tipo']; ?> u-separador-abajo-md">
            <i class="fa-solid fa-circle-check"></i>
            <span><?php echo $_SESSION['alerta_rapida']['mensaje']; ?></span>
        </div>
        <?php unset($_SESSION['alerta_rapida']); ?>
    <?php endif; ?>

    <?php if(empty($empleados)): ?>
        <div class="card-modulo" style="text-align: center; padding: 5rem 2rem;">
            <div class="card-modulo__icono" style="margin: 0 auto 2rem; background-color: #fdf2f8; color: #db2777; width: 6.4rem; height: 6.4rem; font-size: 2.8rem;">
                <i class="fa-solid fa-kitchen-set"></i>
            </div>
            <h3 class="card-modulo__titulo">No hay empleados registrados</h3>
            <p class="card-modulo__texto u-separador-abajo-md">
                Crea cuentas para tus cocineros y cajeros para que puedan ingresar a despachar pedidos y controlar la caja.
            </p>
            <a href="/admin/empleados/crear" class="boton boton--primario">
                <i class="fa-solid fa-plus"></i> Registrar Primer Empleado
            </a>
        </div>
    <?php else: ?>
        <div class="tabla-contenedor" style="padding: 1.6rem 2.4rem;">
            <table class="tabla">
                <thead class="tabla__thead">
                    <tr>
                        <th class="tabla__th" style="padding: 1.6rem 2rem;">Empleado</th>
                        <th class="tabla__th" style="padding: 1.6rem 2rem;">Credencial / Email</th>
                        <th class="tabla__th" style="padding: 1.6rem 2rem;">Teléfono</th>
                        <th class="tabla__th" style="padding: 1.6rem 2rem;">Permisos</th>
                        <th class="tabla__th" style="padding: 1.6rem 2rem;">Estado</th>
                        <th class="tabla__th" style="text-align: right; padding: 1.6rem 2rem;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($empleados as $emp): ?>
                        <tr class="tabla__tr">
                            <td class="tabla__td" style="padding: 1.8rem 2rem;">
                                <div style="display: flex; align-items: center; gap: 1.4rem;">
                                    <div style="width: 4.2rem; height: 4.2rem; border-radius: 50%; background: linear-gradient(135deg, #ea580c, #c2410c); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.6rem; flex-shrink: 0;">
                                        <?php echo strtoupper(mb_substr($emp->nombre, 0, 1)); ?>
                                    </div>
                                    <div>
                                        <strong style="font-size: 1.55rem; color: #1e293b; display: block;">
                                            <?php echo s($emp->nombre); ?>
                                        </strong>
                                        <small style="color: #64748b;">ID #<?php echo (int)$emp->id; ?></small>
                                    </div>
                                </div>
                            </td>
                            <td class="tabla__td" style="padding: 1.8rem 2rem;">
                                <span style="font-family: monospace; font-size: 1.35rem; color: #334155;">
                                    <?php echo s($emp->email); ?>
                                </span>
                            </td>
                            <td class="tabla__td" style="padding: 1.8rem 2rem;">
                                <?php if(!empty($emp->telefono)): ?>
                                    <a href="tel:<?php echo s($emp->telefono); ?>" style="color: #0284c7; text-decoration: none; font-size: 1.35rem;">
                                        <i class="fa-solid fa-phone"></i> <?php echo s($emp->telefono); ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color: #94a3b8;">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="tabla__td" style="padding: 1.8rem 2rem;">
                                <span class="badge" style="background-color: #fdf2f8; color: #db2777; border: 1px solid #fbcfe8;">
                                    <i class="fa-solid fa-kitchen-set"></i> Cocina & Caja
                                </span>
                            </td>
                            <td class="tabla__td" style="padding: 1.8rem 2rem;">
                                <?php if((int)$emp->confirmado === 1): ?>
                                    <span class="badge badge--exito">
                                        <i class="fa-solid fa-circle-check"></i> Activo
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge--error">
                                        <i class="fa-solid fa-circle-xmark"></i> Inactivo
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="tabla__td" style="padding: 1.8rem 2rem; text-align: right;">
                                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 0.8rem;">
                                    <a 
                                        href="/admin/empleados/actualizar?id=<?php echo (int)$emp->id; ?>" 
                                        class="boton boton--secundario boton--sm"
                                        title="Editar empleado"
                                    >
                                        <i class="fa-solid fa-pen-to-square"></i> Editar
                                    </a>

                                    <form method="POST" action="/admin/empleados/eliminar" style="display: inline;" onsubmit="return confirm('¿Estás seguro de <?php echo ((int)$emp->confirmado === 1) ? 'desactivar' : 'activar'; ?> el acceso de este empleado?');">
                                        <input type="hidden" name="id" value="<?php echo (int)$emp->id; ?>">
                                        <?php if((int)$emp->confirmado === 1): ?>
                                            <button type="submit" class="boton boton--peligro boton--sm" title="Suspender acceso">
                                                <i class="fa-solid fa-user-slash"></i> Desactivar
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" class="boton boton--exito boton--sm" title="Reactivar cuenta">
                                                <i class="fa-solid fa-user-check"></i> Activar
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
