<?php 
    $nombreComercio = $nombreComercio ?? (obtenerComercio()->nombre ?? 'Comercio');
?>
<div class="contenedor seccion" style="max-width: 80rem;">

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

    <!-- ENCABEZADO Y RESUMEN DEL REPARTIDOR -->
    <div style="margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.4rem;">
            <div>
                <span class="badge badge--reparto" style="font-size: 1.15rem;">Consola de Reparto</span>
                <h1 style="font-size: 2.4rem; font-weight: 800; color: #1e293b; margin: 0.4rem 0 0 0;">
                    ¡Hola, <?php echo s($_SESSION['nombre'] ?? 'Repartidor'); ?>!
                </h1>
            </div>
            <a href="/repartidor" class="boton boton--secundario boton--pequeno" title="Actualizar pedidos">
                <i class="fa-solid fa-rotate-right"></i>
            </a>
        </div>

        <!-- TARJETAS KPI -->
        <div class="repartidor-kpi">
            <div class="repartidor-kpi__card">
                <div class="repartidor-kpi__card-icono repartidor-kpi__card-icono--efectivo">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                </div>
                <div class="repartidor-kpi__card-info">
                    <span class="repartidor-kpi__card-titulo">Efectivo a Rendir</span>
                    <span class="repartidor-kpi__card-valor repartidor-kpi__card-valor--exito">
                        $ <?php echo number_format($totalPorRendir ?? 0, 2, ',', '.'); ?>
                    </span>
                </div>
            </div>

            <div class="repartidor-kpi__card">
                <div class="repartidor-kpi__card-icono repartidor-kpi__card-icono--entregas">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div class="repartidor-kpi__card-info">
                    <span class="repartidor-kpi__card-titulo">Entregas Hoy</span>
                    <span class="repartidor-kpi__card-valor">
                        <?php echo count($pedidosEntregadosHoy ?? []); ?>
                    </span>
                </div>
            </div>

            <div class="repartidor-kpi__card">
                <div class="repartidor-kpi__card-icono repartidor-kpi__card-icono--activos">
                    <i class="fa-solid fa-motorcycle"></i>
                </div>
                <div class="repartidor-kpi__card-info">
                    <span class="repartidor-kpi__card-titulo">Envíos en Curso</span>
                    <span class="repartidor-kpi__card-valor" style="color: #ea580c;">
                        <?php echo count($pedidosEnCurso ?? []); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- PESTAÑAS -->
    <div class="repartidor-tabs" role="tablist">
        <button type="button" id="tab-btn-activos" class="repartidor-tabs__btn repartidor-tabs__btn--activo" onclick="cambiarTabRepartidor('activos')">
            <i class="fa-solid fa-box"></i>
            <span>Envíos en Curso (<?php echo count($pedidosEnCurso ?? []); ?>)</span>
        </button>
        <button type="button" id="tab-btn-historial" class="repartidor-tabs__btn" onclick="cambiarTabRepartidor('historial')">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>Historial Hoy (<?php echo count($pedidosEntregadosHoy ?? []); ?>)</span>
        </button>
    </div>

    <!-- ================================================================= -->
    <!-- PESTAÑA 1: ENVÍOS EN CURSO (EN CAMINO)                            -->
    <!-- ================================================================= -->
    <div id="vista-activos">
        <?php if(empty($pedidosEnCurso)): ?>
            <div class="admin-tarjeta" style="text-align: center; padding: 4.8rem 2rem;">
                <div style="width: 6.4rem; height: 6.4rem; border-radius: 50%; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 2.8rem; margin: 0 auto 1.6rem auto;">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <h3 style="color: #1e293b; margin-bottom: 0.6rem;">¡No tienes envíos pendientes!</h3>
                <p style="color: #64748b; max-width: 42rem; margin: 0 auto 1.6rem auto; font-size: 1.4rem;">
                    En cuanto la cocina despache un pedido y te lo asigne, aparecerá aquí con todos los datos de entrega y navegación.
                </p>
                <a href="/repartidor" class="boton boton--secundario boton--pequeno">
                    <i class="fa-solid fa-rotate-right"></i>
                    <span>Comprobar Nuevos Pedidos</span>
                </a>
            </div>
        <?php else: ?>
            <?php foreach($pedidosEnCurso as $ped): ?>
                <?php 
                    $detalles = $ped->detalles();
                    $pago = $ped->pago();
                    $esEfectivo = ($ped->metodo_pago === 'efectivo');
                    $numeroWsp = preg_replace('/[^0-9]/', '', $ped->telefono_contacto);
                    $mensajeWsp = "¡Hola {$ped->nombre_contacto}! Voy en camino con tu pedido #{$ped->id} de {$nombreComercio}.";
                ?>
                <div class="reparto-card">
                    <!-- CABECERA -->
                    <div class="reparto-card__cabecera">
                        <div class="reparto-card__id">
                            <i class="fa-solid fa-bag-shopping" style="color: #ea580c;"></i>
                            <span>Pedido #<?php echo (int)$ped->id; ?></span>
                        </div>
                        <div class="reparto-card__meta">
                            <span><i class="fa-solid fa-route"></i> <?php echo number_format($ped->distancia_km, 1, ',', '.'); ?> km</span>
                            <span>·</span>
                            <span><i class="fa-solid fa-clock"></i> Hace <?php echo $ped->minutosTranscurridos(); ?> min</span>
                        </div>
                    </div>

                    <!-- CUERPO -->
                    <div class="reparto-card__cuerpo">

                        <!-- CLIENTE Y CONTACTO -->
                        <div class="reparto-card__seccion">
                            <span class="reparto-card__etiqueta">
                                <i class="fa-solid fa-user"></i> Cliente
                            </span>
                            <div class="reparto-card__cliente-fila">
                                <span class="reparto-card__cliente-nombre"><?php echo s($ped->nombre_contacto); ?></span>
                                <div class="reparto-card__acciones-contacto">
                                    <a href="tel:<?php echo s($ped->telefono_contacto); ?>" class="boton-contacto boton-contacto--tel">
                                        <i class="fa-solid fa-phone"></i>
                                        <span>Llamar</span>
                                    </a>
                                    <?php if(!empty($numeroWsp)): ?>
                                        <a href="https://wa.me/<?php echo $numeroWsp; ?>?text=<?php echo urlencode($mensajeWsp); ?>" target="_blank" class="boton-contacto boton-contacto--wsp">
                                            <i class="fa-brands fa-whatsapp"></i>
                                            <span>WhatsApp</span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- DIRECCIÓN Y NAVEGACIÓN GPS -->
                        <div class="reparto-card__seccion">
                            <span class="reparto-card__etiqueta">
                                <i class="fa-solid fa-location-dot"></i> Dirección de Entrega
                            </span>
                            <div class="reparto-card__direccion-texto">
                                <?php echo s($ped->direccion_texto); ?>
                            </div>
                            <?php if(!empty($ped->lat) && !empty($ped->lng)): ?>
                                <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo $ped->lat; ?>,<?php echo $ped->lng; ?>" target="_blank" class="reparto-card__btn-gps">
                                    <i class="fa-solid fa-diamond-turn-right"></i>
                                    <span>Abrir Navegación GPS (Maps / Waze)</span>
                                </a>
                            <?php endif; ?>
                        </div>

                        <!-- MODALIDAD DE COBRO -->
                        <div class="reparto-card__seccion">
                            <span class="reparto-card__etiqueta">
                                <i class="fa-solid fa-money-bill-wave"></i> Modalidad de Cobro
                            </span>
                            <?php if($esEfectivo): ?>
                                <div class="reparto-card__cobro reparto-card__cobro--efectivo">
                                    <i class="fa-solid fa-hand-holding-dollar reparto-card__cobro-icono"></i>
                                    <div class="reparto-card__cobro-texto">
                                        <span class="reparto-card__cobro-titulo">Cobrar en Mano (Efectivo)</span>
                                        <span class="reparto-card__cobro-monto">$ <?php echo number_format($ped->total, 2, ',', '.'); ?></span>
                                        <span class="reparto-card__cobro-sub">Solicitar el dinero al entregar el pedido</span>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="reparto-card__cobro reparto-card__cobro--online">
                                    <i class="fa-solid fa-circle-check reparto-card__cobro-icono"></i>
                                    <div class="reparto-card__cobro-texto">
                                        <span class="reparto-card__cobro-titulo">Ya Pagado Online (Mercado Pago)</span>
                                        <span class="reparto-card__cobro-monto">$ <?php echo number_format($ped->total, 2, ',', '.'); ?></span>
                                        <span class="reparto-card__cobro-sub">¡NO cobrar nada al cliente! Solo realizar la entrega</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- DETALLE DE PRODUCTOS -->
                        <div class="reparto-card__seccion">
                            <span class="reparto-card__etiqueta">
                                <i class="fa-solid fa-utensils"></i> Detalle de la Comanda
                            </span>
                            <div class="reparto-card__productos">
                                <?php foreach($detalles as $det): ?>
                                    <div class="reparto-card__producto-item">
                                        <div class="reparto-card__producto-item-linea">
                                            <span><strong><?php echo (int)$det->cantidad; ?>x</strong> <?php echo s($det->nombre_producto); ?></span>
                                            <span>$ <?php echo number_format($det->subtotal, 2, ',', '.'); ?></span>
                                        </div>
                                        <?php if(!empty($det->opciones_texto)): ?>
                                            <div class="reparto-card__producto-item-detalles">
                                                <i class="fa-solid fa-plus" style="font-size: 0.9rem;"></i> <?php echo s($det->opciones_texto); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                    </div>

                    <!-- BOTÓN ACCIÓN ENTREGAR -->
                    <div class="reparto-card__footer">
                        <form method="POST" action="/repartidor/confirmar-entrega" onsubmit="return confirm('¿Confirmas que el Pedido #<?php echo $ped->id; ?> fue entregado a <?php echo addslashes(s($ped->nombre_contacto)); ?>?<?php echo $esEfectivo ? '\n\nATENCIÓN: Asegúrate de haber cobrado $' . number_format($ped->total, 2, ',', '.') . ' en efectivo.' : ''; ?>');">
                            <input type="hidden" name="pedido_id" value="<?php echo (int)$ped->id; ?>">
                            <button type="submit" class="reparto-card__btn-entregar">
                                <i class="fa-solid fa-circle-check"></i>
                                <span>Confirmar Entrega</span>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ================================================================= -->
    <!-- PESTAÑA 2: HISTORIAL DE HOY                                       -->
    <!-- ================================================================= -->
    <div id="vista-historial" style="display: none;">
        <?php if(empty($pedidosEntregadosHoy)): ?>
            <div class="admin-tarjeta" style="text-align: center; padding: 4rem 2rem;">
                <i class="fa-solid fa-clock-rotate-left" style="font-size: 3.6rem; color: #cbd5e1; margin-bottom: 1.2rem;"></i>
                <p style="color: #64748b; font-size: 1.4rem; margin: 0;">Aún no has completado entregas en la jornada de hoy.</p>
            </div>
        <?php else: ?>
            <div class="admin-tarjeta" style="padding: 0; overflow: hidden;">
                <div class="tabla-contenedor">
                    <table class="tabla">
                        <thead class="tabla__encabezado">
                            <tr>
                                <th class="tabla__th">Pedido</th>
                                <th class="tabla__th">Cliente</th>
                                <th class="tabla__th">Método</th>
                                <th class="tabla__th">Monto</th>
                                <th class="tabla__th">Rendición</th>
                            </tr>
                        </thead>
                        <tbody class="tabla__cuerpo">
                            <?php foreach($pedidosEntregadosHoy as $pedEnt): ?>
                                <?php 
                                    $pagoEnt = $pedEnt->pago();
                                    $esEfecEnt = ($pedEnt->metodo_pago === 'efectivo');
                                    $estaRendido = ($pagoEnt && (int)$pagoEnt->rendido === 1);
                                ?>
                                <tr class="tabla__tr">
                                    <td class="tabla__td">
                                        <strong style="color: #1e293b;">#<?php echo $pedEnt->id; ?></strong>
                                    </td>
                                    <td class="tabla__td">
                                        <span><?php echo s($pedEnt->nombre_contacto); ?></span>
                                    </td>
                                    <td class="tabla__td">
                                        <?php if($esEfecEnt): ?>
                                            <span class="badge badge--exito">Efectivo</span>
                                        <?php else: ?>
                                            <span class="badge badge--info">Mercado Pago</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="tabla__td">
                                        <strong>$ <?php echo number_format($pedEnt->total, 2, ',', '.'); ?></strong>
                                    </td>
                                    <td class="tabla__td">
                                        <?php if(!$esEfecEnt): ?>
                                            <span style="color: #0284c7; font-size: 1.2rem;">No aplica</span>
                                        <?php elseif($estaRendido): ?>
                                            <span class="badge badge--exito">Rendido en Caja</span>
                                        <?php else: ?>
                                            <span class="badge badge--alerta">En Custodia</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
function cambiarTabRepartidor(tab) {
    const vistaActivos = document.getElementById('vista-activos');
    const vistaHistorial = document.getElementById('vista-historial');
    const btnActivos = document.getElementById('tab-btn-activos');
    const btnHistorial = document.getElementById('tab-btn-historial');

    if(tab === 'activos') {
        vistaActivos.style.display = 'block';
        vistaHistorial.style.display = 'none';
        btnActivos.classList.add('repartidor-tabs__btn--activo');
        btnHistorial.classList.remove('repartidor-tabs__btn--activo');
    } else {
        vistaActivos.style.display = 'none';
        vistaHistorial.style.display = 'block';
        btnActivos.classList.remove('repartidor-tabs__btn--activo');
        btnHistorial.classList.add('repartidor-tabs__btn--activo');
    }
}
</script>
