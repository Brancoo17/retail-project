<div class="contenedor seccion">

    <?php 
        if(isset($_SESSION['alerta_rapida'])): 
            $alerta = $_SESSION['alerta_rapida'];
            unset($_SESSION['alerta_rapida']);
    ?>
        <div class="alerta alerta--<?php echo $alerta['tipo'] === 'exito' ? 'exito' : 'error'; ?> u-separador-abajo">
            <i class="fa-solid fa-<?php echo $alerta['tipo'] === 'exito' ? 'circle-check' : 'circle-exclamation'; ?>"></i>
            <span><?php echo s($alerta['mensaje']); ?></span>
        </div>
    <?php endif; ?>

    <div class="admin-encabezado">
        <div class="admin-encabezado__info">
            <h1 class="admin-encabezado__titulo">Equipo de Repartidores</h1>
            <p class="admin-encabezado__subtitulo">Administra los repartidores de tu comercio, monitorea entregas en calle y gestiona el efectivo pendiente de rendición.</p>
        </div>
        <div class="admin-encabezado__acciones">
            <a href="/admin/repartidores/crear" class="boton boton--primario">
                <i class="fa-solid fa-user-plus"></i>
                <span>Nuevo Repartidor</span>
            </a>
        </div>
    </div>

    <?php if(empty($repartidores)): ?>
        <div class="admin-tarjeta" style="text-align: center; padding: 4.8rem 2rem;">
            <i class="fa-solid fa-motorcycle" style="font-size: 4rem; color: #94a3b8; margin-bottom: 1.6rem;"></i>
            <h3 style="color: #334155; margin-bottom: 0.8rem;">No hay repartidores registrados</h3>
            <p style="color: #64748b; max-width: 45rem; margin: 0 auto 2rem auto;">
                Registra a tu equipo de delivery para que la cocina pueda despacharles pedidos y ellos confirmen las entregas desde su teléfono.
            </p>
            <a href="/admin/repartidores/crear" class="boton boton--primario">
                <i class="fa-solid fa-plus"></i>
                <span>Registrar Primer Repartidor</span>
            </a>
        </div>
    <?php else: ?>
        <div class="admin-tarjeta" style="padding: 0; overflow: hidden;">
            <div class="tabla-contenedor">
                <table class="tabla">
                    <thead class="tabla__encabezado">
                        <tr>
                            <th class="tabla__th">Repartidor</th>
                            <th class="tabla__th">Contacto</th>
                            <th class="tabla__th">Estado</th>
                            <th class="tabla__th" style="text-align: center;">En Calle</th>
                            <th class="tabla__th" style="text-align: center;">Entregas Hoy</th>
                            <th class="tabla__th" style="text-align: center;">Histórico</th>
                            <th class="tabla__th">Efectivo por Rendir</th>
                            <th class="tabla__th" style="text-align: right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="tabla__cuerpo">
                        <?php foreach($repartidores as $rep): ?>
                            <tr class="tabla__tr">
                                <td class="tabla__td">
                                    <div style="display: flex; align-items: center; gap: 1.2rem;">
                                        <div style="width: 3.6rem; height: 3.6rem; border-radius: 50%; background: linear-gradient(135deg, #ea580c, #f59e0b); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.4rem;">
                                            <?php echo strtoupper(mb_substr($rep->nombre, 0, 1)); ?>
                                        </div>
                                        <div>
                                            <strong style="color: #1e293b; font-size: 1.4rem; display: block;"><?php echo s($rep->nombre); ?></strong>
                                            <span style="color: #64748b; font-size: 1.2rem;"><?php echo s($rep->email); ?></span>
                                        </div>
                                    </div>
                                </td>

                                <td class="tabla__td">
                                    <div style="display: flex; align-items: center; gap: 0.8rem;">
                                        <a href="tel:<?php echo s($rep->telefono); ?>" class="boton boton--secundario boton--pequeno" style="padding: 0.4rem 0.8rem;" title="Llamar">
                                            <i class="fa-solid fa-phone" style="color: #16a34a;"></i>
                                            <span><?php echo s($rep->telefono); ?></span>
                                        </a>
                                    </div>
                                </td>

                                <td class="tabla__td">
                                    <?php if((int)$rep->confirmado === 1): ?>
                                        <span class="badge badge--exito">Activo</span>
                                    <?php else: ?>
                                        <span class="badge badge--alerta" style="background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1;">Inactivo</span>
                                    <?php endif; ?>
                                </td>

                                <td class="tabla__td" style="text-align: center;">
                                    <?php if($rep->pedidos_en_curso > 0): ?>
                                        <span class="badge badge--alerta" style="font-weight: 800; font-size: 1.2rem; background: #fff7ed; color: #ea580c; border: 1px solid #fdba74;">
                                            <i class="fa-solid fa-motorcycle"></i> <?php echo $rep->pedidos_en_curso; ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #94a3b8; font-size: 1.3rem;">0</span>
                                    <?php endif; ?>
                                </td>

                                <td class="tabla__td" style="text-align: center;">
                                    <span style="font-weight: 700; color: #059669; font-size: 1.3rem;">
                                        <?php echo $rep->pedidos_entregados_hoy; ?>
                                    </span>
                                </td>

                                <td class="tabla__td" style="text-align: center;">
                                    <span style="color: #64748b; font-size: 1.3rem;">
                                        <?php echo $rep->total_entregados; ?>
                                    </span>
                                </td>

                                <td class="tabla__td">
                                    <?php if($rep->total_por_rendir > 0): ?>
                                        <span class="badge badge--alerta" style="font-size: 1.25rem; font-weight: 800; padding: 0.4rem 0.8rem;">
                                            $ <?php echo number_format($rep->total_por_rendir, 2, ',', '.'); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #10b981; font-size: 1.25rem; font-weight: 600;">Al día ($ 0,00)</span>
                                    <?php endif; ?>
                                </td>

                                <td class="tabla__td" style="text-align: right;">
                                    <div style="display: flex; justify-content: flex-end; align-items: center; gap: 0.8rem;">
                                        <a href="/admin/repartidores/actualizar?id=<?php echo (int)$rep->id; ?>" class="boton boton--secundario boton--pequeno" title="Editar">
                                            <i class="fa-solid fa-pen"></i>
                                            <span>Editar</span>
                                        </a>

                                        <form method="POST" action="/admin/repartidores/eliminar" onsubmit="return confirm('¿Seguro que deseas eliminar o dar de baja a este repartidor?');" style="margin: 0;">
                                            <input type="hidden" name="id" value="<?php echo (int)$rep->id; ?>">
                                            <button type="submit" class="boton boton--secundario boton--pequeno" style="color: #ef4444; border-color: #fca5a5;" title="Eliminar o desactivar">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>
