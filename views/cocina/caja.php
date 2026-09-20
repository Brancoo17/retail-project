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
        <a href="/cocina" class="boton boton--secundario boton--pequeno">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Volver a Cocina (KDS)</span>
        </a>

        <div style="display: flex; flex-direction: column; gap: 0.4rem;">
            <h1 class="seccion__titulo" style="margin-bottom: 0;">
                <i class="fa-solid fa-cash-register" style="color: #ea580c;"></i>
                <span>Control y Arqueo de Caja Diario</span>
            </h1>
            <p class="seccion__subtitulo">
                Fecha: <strong><?php echo date('d/m/Y'); ?></strong> · 
                Personal: <strong><?php echo s($_SESSION['nombre'] ?? 'Staff'); ?></strong>
            </p>
        </div>

        <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
            <?php if($sesionActiva): ?>
                <span class="badge badge--exito" style="font-size: 1.4rem; padding: 0.8rem 1.6rem;">
                    <i class="fa-solid fa-lock-open"></i> Caja Abierta (Turno Activo)
                </span>
                <button type="button" class="boton boton--secundario" onclick="abrirModalCerrarCaja()" style="border-color: #f59e0b; color: #b45309;">
                    <i class="fa-solid fa-lock"></i>
                    <span>Cerrar Turno / Arqueo</span>
                </button>
            <?php else: ?>
                <span class="badge badge--alerta" style="font-size: 1.4rem; padding: 0.8rem 1.6rem;">
                    <i class="fa-solid fa-lock"></i> Caja Cerrada
                </span>
                <button type="button" class="boton boton--primario" onclick="abrirModalAbrirCaja()">
                    <i class="fa-solid fa-key"></i>
                    <span>Iniciar Turno (Abrir Caja)</span>
                </button>
            <?php endif; ?>

            <button type="button" onclick="window.print()" class="boton boton--secundario" title="Imprimir reporte de caja">
                <i class="fa-solid fa-print"></i>
                <span>Imprimir Resumen</span>
            </button>
        </div>
    </div>

    <!-- TARJETAS RESUMEN DE CAJA -->
    <div class="caja-resumen">
        <div class="caja-resumen__card">
            <span class="caja-resumen__card-titulo">Fondo Inicial de Cambio</span>
            <span class="caja-resumen__card-monto">
                $ <?php echo number_format((float)($sesionActiva->monto_apertura ?? 0.0), 2, ',', '.'); ?>
            </span>
            <span class="caja-resumen__card-sub">
                <?php echo $sesionActiva ? 'Apertura: ' . date('H:i', strtotime($sesionActiva->fecha_apertura)) . ' hs' : 'Sin turno abierto'; ?>
            </span>
        </div>

        <div class="caja-resumen__card">
            <span class="caja-resumen__card-titulo">Efectivo Mostrador (En Cajón)</span>
            <span class="caja-resumen__card-monto caja-resumen__card-monto--exito">
                $ <?php echo number_format((float)($totalesTurno['efectivo_mostrador'] ?? 0.0), 2, ',', '.'); ?>
            </span>
            <span class="caja-resumen__card-sub">
                <?php echo (int)($totalesTurno['cant_mostrador'] ?? 0); ?> cobros directos en caja
            </span>
        </div>

        <div class="caja-resumen__card">
            <span class="caja-resumen__card-titulo">Efectivo Rendido Delivery (En Cajón)</span>
            <span class="caja-resumen__card-monto caja-resumen__card-monto--exito">
                $ <?php echo number_format((float)($totalesTurno['efectivo_rendido'] ?? 0.0), 2, ',', '.'); ?>
            </span>
            <span class="caja-resumen__card-sub">
                <?php echo (int)($totalesTurno['cant_rendido'] ?? 0); ?> pedidos rendidos por repartidores
            </span>
        </div>

        <div class="caja-resumen__card" style="border-color: #bfdbfe; background-color: #eff6ff;">
            <span class="caja-resumen__card-titulo" style="color: #1e40af;">Efectivo en Calle (A Rendir)</span>
            <span class="caja-resumen__card-monto caja-resumen__card-monto--calle">
                $ <?php echo number_format((float)($totalesTurno['efectivo_en_calle'] ?? 0.0), 2, ',', '.'); ?>
            </span>
            <span class="caja-resumen__card-sub" style="color: #1e3a8a;">
                <?php echo (int)($totalesTurno['cant_en_calle'] ?? 0); ?> pedidos en manos de repartidores (fuera de caja)
            </span>
        </div>

        <div class="caja-resumen__card">
            <span class="caja-resumen__card-titulo">Ventas Mercado Pago (Online)</span>
            <span class="caja-resumen__card-monto caja-resumen__card-monto--mp">
                $ <?php echo number_format((float)($totalesTurno['ventas_mp'] ?? 0.0), 2, ',', '.'); ?>
            </span>
            <span class="caja-resumen__card-sub">
                <?php echo (int)($totalesTurno['cant_mp'] ?? 0); ?> cobros acreditados
            </span>
        </div>

        <div class="caja-resumen__card" style="border-color: #fed7aa; background-color: #fff7ed;">
            <span class="caja-resumen__card-titulo" style="color: #9a3412;">Total Efectivo Esperado en Cajón</span>
            <span class="caja-resumen__card-monto caja-resumen__card-monto--destacado">
                $ <?php echo number_format((float)($totalesTurno['efectivo_esperado'] ?? (float)($sesionActiva->monto_apertura ?? 0.0)), 2, ',', '.'); ?>
            </span>
            <span class="caja-resumen__card-sub" style="color: #9a3412;">
                Fondo + Mostrador + Rendido
            </span>
        </div>
    </div>

    <!-- RENDICIONES PENDIENTES DE REPARTIDORES -->
    <div class="admin-tarjeta u-separador-abajo">
        <div class="admin-tarjeta__encabezado" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.6rem;">
            <h2 class="admin-tarjeta__titulo" style="margin: 0;">
                <i class="fa-solid fa-motorcycle" style="color: #2563eb;"></i>
                <span>Rendiciones de Efectivo de Repartidores (<?php echo count($pendientesRendicion ?? []); ?> con dinero en calle)</span>
            </h2>
        </div>

        <?php if(empty($pendientesRendicion)): ?>
            <div style="text-align: center; color: #64748b; padding: 2rem 0;">
                <i class="fa-solid fa-circle-check" style="font-size: 2.4rem; color: #16a34a; margin-bottom: 0.8rem; display: block;"></i>
                <p style="margin: 0;">No hay repartidores con efectivo pendiente de rendición a caja.</p>
            </div>
        <?php else: ?>
            <div class="tabla-contenedor">
                <table class="tabla">
                    <thead class="tabla__thead">
                        <tr>
                            <th class="tabla__th">Repartidor</th>
                            <th class="tabla__th">Teléfono</th>
                            <th class="tabla__th">Pedidos Cobrados</th>
                            <th class="tabla__th">Total a Rendir</th>
                            <th class="tabla__th">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="tabla__tbody">
                        <?php foreach($pendientesRendicion as $rend): ?>
                            <tr class="tabla__tr">
                                <td class="tabla__td">
                                    <strong class="u-texto-negrita"><?php echo s($rend['repartidor_nombre']); ?></strong>
                                </td>
                                <td class="tabla__td"><?php echo s($rend['repartidor_telefono'] ?? '-'); ?></td>
                                <td class="tabla__td">
                                    <span class="badge badge--info"><?php echo (int)$rend['cantidad_pedidos']; ?> pedidos</span>
                                </td>
                                <td class="tabla__td">
                                    <strong class="precio-destacado" style="color: #2563eb; font-size: 1.6rem;">
                                        $ <?php echo number_format((float)$rend['total_pendiente'], 2, ',', '.'); ?>
                                    </strong>
                                </td>
                                <td class="tabla__td">
                                    <?php if($sesionActiva): ?>
                                        <form method="POST" action="/cocina/rendir-repartidor" style="margin: 0;" onsubmit="return confirm('¿Confirmas que recibiste $ <?php echo number_format((float)$rend['total_pendiente'], 2, ',', '.'); ?> en efectivo de <?php echo addslashes($rend['repartidor_nombre']); ?> para ingresar al cajón?');">
                                            <input type="hidden" name="repartidor_id" value="<?php echo (int)$rend['repartidor_id']; ?>">
                                            <button type="submit" class="boton boton--primario boton--pequeno" style="background-color: #2563eb; border-color: #2563eb;">
                                                <i class="fa-solid fa-hand-holding-dollar"></i>
                                                <span>Recibir Rendición ($ <?php echo number_format((float)$rend['total_pendiente'], 2, ',', '.'); ?>)</span>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge badge--alerta">Abrir caja para recibir</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- TABLA COMPROBANTES EMITIDOS HOY -->
    <div class="admin-tarjeta u-separador-abajo">
        <div class="admin-tarjeta__encabezado" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.6rem;">
            <h2 class="admin-tarjeta__titulo" style="margin: 0;">
                <i class="fa-solid fa-receipt"></i>
                <span>Comprobantes Internos Emitidos Hoy (<?php echo count($comprobantesHoy); ?>)</span>
            </h2>
        </div>

        <?php if(empty($comprobantesHoy)): ?>
            <p style="text-align: center; color: #64748b; padding: 2rem 0;">No se registran comprobantes emitidos en el día de hoy.</p>
        <?php else: ?>
            <div class="tabla-contenedor">
                <table class="tabla">
                    <thead class="tabla__thead">
                        <tr>
                            <th class="tabla__th">Comprobante</th>
                            <th class="tabla__th">Pedido #</th>
                            <th class="tabla__th">Hora</th>
                            <th class="tabla__th">Cliente</th>
                            <th class="tabla__th">Modalidad</th>
                            <th class="tabla__th">Método de Pago</th>
                            <th class="tabla__th">Total</th>
                            <th class="tabla__th">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="tabla__tbody">
                        <?php foreach($comprobantesHoy as $comp): ?>
                            <tr class="tabla__tr">
                                <td class="tabla__td">
                                    <strong class="u-texto-negrita" style="font-family: monospace; font-size: 1.3rem;">
                                        C-<?php echo str_pad((string)$comp->comercio_id, 4, '0', STR_PAD_LEFT); ?>-<?php echo str_pad((string)$comp->numero_correlativo, 8, '0', STR_PAD_LEFT); ?>
                                    </strong>
                                </td>
                                <td class="tabla__td">#<?php echo (int)$comp->pedido_numero; ?></td>
                                <td class="tabla__td"><?php echo date('H:i', strtotime($comp->fecha_emision)); ?> hs</td>
                                <td class="tabla__td"><?php echo s($comp->nombre_contacto); ?></td>
                                <td class="tabla__td">
                                    <span class="badge <?php echo $comp->tipo === 'delivery' ? 'badge--info' : 'badge--destacado'; ?>">
                                        <?php echo ucfirst($comp->tipo); ?>
                                    </span>
                                </td>
                                <td class="tabla__td">
                                    <span class="badge <?php echo $comp->metodo_pago === 'mercadopago' ? 'badge--info' : 'badge--exito'; ?>">
                                        <?php echo $comp->metodo_pago === 'mercadopago' ? 'Mercado Pago' : 'Efectivo'; ?>
                                    </span>
                                </td>
                                <td class="tabla__td">
                                    <strong class="precio-destacado">$ <?php echo number_format((float)$comp->total, 2, ',', '.'); ?></strong>
                                </td>
                                <td class="tabla__td">
                                    <a href="/comprobante?id=<?php echo (int)$comp->id; ?>" target="_blank" class="boton boton--secundario boton--pequeno" title="Ver / Imprimir Ticket">
                                        <i class="fa-solid fa-print"></i>
                                        <span>Imprimir</span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- HISTORIAL DE TURNOS DE CAJA -->
    <div class="admin-tarjeta">
        <h2 class="admin-tarjeta__titulo">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>Historial de Sesiones y Cierres de Turno</span>
        </h2>

        <?php if(empty($ultimasSesiones)): ?>
            <p style="text-align: center; color: #64748b; padding: 2rem 0;">No hay registros históricos de sesiones de caja.</p>
        <?php else: ?>
            <div class="tabla-contenedor">
                <table class="tabla">
                    <thead class="tabla__thead">
                        <tr>
                            <th class="tabla__th">ID</th>
                            <th class="tabla__th">Cajero / Usuario</th>
                            <th class="tabla__th">Apertura</th>
                            <th class="tabla__th">Cierre</th>
                            <th class="tabla__th">Fondo Inicial</th>
                            <th class="tabla__th">Ventas Efectivo</th>
                            <th class="tabla__th">Ventas MP</th>
                            <th class="tabla__th">Total Contado</th>
                            <th class="tabla__th">Diferencia</th>
                            <th class="tabla__th">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="tabla__tbody">
                        <?php foreach($ultimasSesiones as $ses): ?>
                            <tr class="tabla__tr">
                                <td class="tabla__td">#<?php echo (int)$ses->id; ?></td>
                                <td class="tabla__td"><?php echo s($ses->nombre_cajero ?? 'Personal'); ?></td>
                                <td class="tabla__td"><?php echo date('d/m H:i', strtotime($ses->fecha_apertura)); ?> hs</td>
                                <td class="tabla__td"><?php echo $ses->fecha_cierre ? date('d/m H:i', strtotime($ses->fecha_cierre)) . ' hs' : '<span style="color: #16a34a; font-weight: bold;">En curso</span>'; ?></td>
                                <td class="tabla__td">$ <?php echo number_format((float)$ses->monto_apertura, 2, ',', '.'); ?></td>
                                <td class="tabla__td">$ <?php echo number_format((float)$ses->total_ventas_efectivo, 2, ',', '.'); ?></td>
                                <td class="tabla__td">$ <?php echo number_format((float)$ses->total_ventas_mp, 2, ',', '.'); ?></td>
                                <td class="tabla__td">
                                    <?php echo $ses->monto_cierre !== null ? '$ ' . number_format((float)$ses->monto_cierre, 2, ',', '.') : '-'; ?>
                                </td>
                                <td class="tabla__td">
                                    <?php if($ses->diferencia !== null): ?>
                                        <span class="badge <?php echo (float)$ses->diferencia >= 0 ? 'badge--exito' : 'badge--error'; ?>">
                                            <?php echo ((float)$ses->diferencia >= 0 ? '+$ ' : '-$ ') . number_format(abs((float)$ses->diferencia), 2, ',', '.'); ?>
                                        </span>
                                    <?php else: ?>
                                        <span>-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="tabla__td">
                                    <span class="badge <?php echo $ses->estado === 'abierta' ? 'badge--exito' : 'badge--info'; ?>">
                                        <?php echo ucfirst($ses->estado); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- MODAL ABRIR CAJA -->
<div id="modal-abrir-caja" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="admin-tarjeta" style="width: 100%; max-width: 45rem; margin: 1.6rem; background: #fff; border-radius: 0.8rem;">
        <div class="admin-tarjeta__encabezado" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.6rem;">
            <h3 class="admin-tarjeta__titulo" style="margin: 0;">
                <i class="fa-solid fa-cash-register"></i>
                <span>Iniciar Turno de Caja</span>
            </h3>
            <button type="button" onclick="cerrarModalAbrirCaja()" style="background: none; border: none; font-size: 1.8rem; cursor: pointer;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="/cocina/caja/abrir">
            <div class="formulario__campo u-separador-abajo">
                <label for="monto_apertura" class="formulario__label">Monto de Apertura / Fondo de Cambio ($):</label>
                <input type="number" step="0.01" min="0" name="monto_apertura" id="monto_apertura" class="formulario__input" placeholder="Ej. 10000.00" value="0.00" required>
            </div>

            <div class="formulario__campo u-separador-abajo">
                <label for="obs_apertura" class="formulario__label">Observaciones (Opcional):</label>
                <input type="text" name="observaciones" id="obs_apertura" class="formulario__input" placeholder="Ej. Turno Noche · Cajero: Lucas">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                <button type="button" onclick="cerrarModalAbrirCaja()" class="boton boton--secundario">Cancelar</button>
                <button type="submit" class="boton boton--primario">
                    <i class="fa-solid fa-lock-open"></i>
                    <span>Abrir Turno de Caja</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL CERRAR CAJA / ARQUEO -->
<div id="modal-cerrar-caja" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="admin-tarjeta" style="width: 100%; max-width: 48rem; margin: 1.6rem; background: #fff; border-radius: 0.8rem;">
        <div class="admin-tarjeta__encabezado" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.6rem;">
            <h3 class="admin-tarjeta__titulo" style="margin: 0; color: #ea580c;">
                <i class="fa-solid fa-scale-balanced"></i>
                <span>Cierre de Turno y Arqueo</span>
            </h3>
            <button type="button" onclick="cerrarModalCerrarCaja()" style="background: none; border: none; font-size: 1.8rem; cursor: pointer;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="/cocina/caja/cerrar">
            <div class="caja-info-cierre alerta--fija u-separador-abajo" style="margin-bottom: 1.6rem; padding: 1.4rem 1.8rem; background-color: #f0f9ff; border: 1px solid #bae6fd; border-radius: 0.8rem; display: flex; align-items: flex-start; gap: 1.2rem;">
                <i class="fa-solid fa-info-circle" style="color: #0284c7; font-size: 2rem; margin-top: 0.2rem;"></i>
                <div>
                    <strong style="color: #0369a1; font-size: 1.4rem;">Efectivo Esperado en Cajón:</strong>
                    <div style="font-size: 2rem; font-weight: 900; margin-top: 0.4rem; color: #ea580c;">
                        $ <?php echo number_format((float)($totalesTurno['efectivo_esperado'] ?? 0.0), 2, ',', '.'); ?>
                    </div>
                    <p style="margin: 0.3rem 0 0 0; font-size: 1.2rem; color: #475569; line-height: 1.4;">
                        (Fondo inicial: $ <?php echo number_format((float)($sesionActiva->monto_apertura ?? 0.0), 2, ',', '.'); ?> + Mostrador: $ <?php echo number_format((float)($totalesTurno['efectivo_mostrador'] ?? 0.0), 2, ',', '.'); ?> + Rendido repartidores: $ <?php echo number_format((float)($totalesTurno['efectivo_rendido'] ?? 0.0), 2, ',', '.'); ?>)
                    </p>
                    <?php if(!empty($totalesTurno['efectivo_en_calle']) && (float)$totalesTurno['efectivo_en_calle'] > 0): ?>
                        <div style="margin-top: 0.6rem; font-size: 1.2rem; color: #1e40af; font-weight: 600;">
                            <i class="fa-solid fa-triangle-exclamation"></i> Hay $ <?php echo number_format((float)$totalesTurno['efectivo_en_calle'], 2, ',', '.'); ?> en custodia de repartidores en la calle (no incluidos en el cajón).
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="formulario__campo u-separador-abajo">
                <label for="monto_cierre" class="formulario__label">Efectivo Físico Contado en Cajón ($):</label>
                <input type="number" step="0.01" min="0" name="monto_cierre" id="monto_cierre" class="formulario__input" placeholder="Monto total recontado" required>
            </div>

            <div class="formulario__campo u-separador-abajo">
                <label for="obs_cierre" class="formulario__label">Observaciones de Cierre (Opcional):</label>
                <input type="text" name="observaciones" id="obs_cierre" class="formulario__input" placeholder="Ej. Rendición sin novedades">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                <button type="button" onclick="cerrarModalCerrarCaja()" class="boton boton--secundario">Volver</button>
                <button type="submit" class="boton boton--primario">
                    <i class="fa-solid fa-lock"></i>
                    <span>Confirmar Cierre de Caja</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalAbrirCaja() {
    document.getElementById('modal-abrir-caja').style.display = 'flex';
}
function cerrarModalAbrirCaja() {
    document.getElementById('modal-abrir-caja').style.display = 'none';
}
function abrirModalCerrarCaja() {
    document.getElementById('modal-cerrar-caja').style.display = 'flex';
}
function cerrarModalCerrarCaja() {
    document.getElementById('modal-cerrar-caja').style.display = 'none';
}
</script>
