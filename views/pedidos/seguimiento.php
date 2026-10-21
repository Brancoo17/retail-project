<div class="contenedor seccion">
    <div class="tracker-contenedor">
        <div class="admin-encabezado">
            <a href="/menu" class="boton boton--secundario boton--pequeno">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Volver al Menú</span>
            </a>
            
            <div class="m-flex-entre" style="width: 100%; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.2rem;">
                <div>
                    <h1 class="seccion__titulo" style="margin-bottom: 0.4rem;">Pedido #<?php echo (int)$pedido->id; ?></h1>
                    <p class="seccion__subtitulo" style="margin-bottom: 0;">
                        Realizado el <?php echo s(date('d/m/Y H:i', strtotime($pedido->creado_en ?? 'now'))); ?> hs.
                    </p>
                </div>
                <span class="badge <?php echo $pedido->estadoBadgeClase(); ?>" style="font-size: 1.5rem; padding: 0.8rem 1.6rem;">
                    <?php echo s($pedido->estadoTexto()); ?>
                </span>
            </div>
        </div>

        <?php include_once __DIR__ . '/../templates/alertas.php'; ?>

        <!-- STEPPER DE ESTADOS DEL PEDIDO -->
        <?php if($pedido->estado !== 'cancelado'): ?>
            <?php 
                $pasoActivo = $pedido->stepperPaso();
                $porcentajes = [1 => '0%', 2 => '25%', 3 => '50%', 4 => '75%', 5 => '100%'];
                $anchoBarra = $porcentajes[$pasoActivo] ?? '0%';
            ?>
            <div class="tracker-stepper">
                <div class="tracker-stepper__progreso" style="width: <?php echo $anchoBarra; ?>;"></div>

                <div class="tracker-paso <?php echo ($pasoActivo >= 1) ? ($pasoActivo > 1 ? 'tracker-paso--completado' : 'tracker-paso--actual') : ''; ?>">
                    <div class="tracker-paso__icono">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                    <span class="tracker-paso__texto">1. Pendiente</span>
                </div>

                <div class="tracker-paso <?php echo ($pasoActivo >= 2) ? ($pasoActivo > 2 ? 'tracker-paso--completado' : 'tracker-paso--actual') : ''; ?>">
                    <div class="tracker-paso__icono">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <span class="tracker-paso__texto">2. Confirmado</span>
                </div>

                <div class="tracker-paso <?php echo ($pasoActivo >= 3) ? ($pasoActivo > 3 ? 'tracker-paso--completado' : 'tracker-paso--actual') : ''; ?>">
                    <div class="tracker-paso__icono">
                        <i class="fa-solid fa-fire-burner"></i>
                    </div>
                    <span class="tracker-paso__texto">3. En Cocina</span>
                </div>

                <div class="tracker-paso <?php echo ($pasoActivo >= 4) ? ($pasoActivo > 4 ? 'tracker-paso--completado' : 'tracker-paso--actual') : ''; ?>">
                    <div class="tracker-paso__icono">
                        <i class="fa-solid fa-motorcycle"></i>
                    </div>
                    <span class="tracker-paso__texto"><?php echo ($pedido->tipo === 'delivery') ? '4. En Camino' : '4. Listo para Retirar'; ?></span>
                </div>

                <div class="tracker-paso <?php echo ($pasoActivo >= 5) ? 'tracker-paso--completado' : ''; ?>">
                    <div class="tracker-paso__icono">
                        <i class="fa-solid fa-clipboard-check"></i>
                    </div>
                    <span class="tracker-paso__texto">5. Entregado</span>
                </div>
            </div>
        <?php else: ?>
            <div class="alerta alerta--error u-separador-abajo" style="display: flex; align-items: center; gap: 1.2rem;">
                <i class="fa-solid fa-ban" style="font-size: 2.2rem;"></i>
                <div>
                    <strong>Pedido Cancelado</strong>
                    <p style="margin: 0.4rem 0 0 0;">Este pedido ha sido cancelado y los insumos reservados fueron devueltos al stock del local.</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- TARJETA VENTANA DE CANCELACIÓN (SI APLICA) -->
        <?php if($puedeCancelar): ?>
            <div class="cancelacion-card">
                <div class="cancelacion-card__info">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <div class="cancelacion-card__texto">
                        <strong>Ventana de Cancelación Activa:</strong>
                        <p style="margin: 0.2rem 0 0 0;">
                            Tienes hasta <strong><span id="contador-segundos" data-segundos="<?php echo (int)$segundosRestantes; ?>">--:--</span></strong> para cancelar tu pedido antes de que ingrese a cocina.
                        </p>
                    </div>
                </div>

                <form method="POST" action="/pedido/cancelar" onsubmit="return confirm('¿Seguro que deseas cancelar este pedido? Los artículos serán restituidos al comercio.');">
                    <input type="hidden" name="pedido_id" value="<?php echo (int)$pedido->id; ?>">
                    <button type="submit" class="boton boton--secundario boton--pequeno" style="color: #ef4444; border-color: #fca5a5;">
                        <i class="fa-solid fa-xmark"></i>
                        <span>Cancelar Pedido</span>
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <!-- ESTADO DEL PAGO Y ACCIONES -->
        <?php if($pedido->estado !== 'cancelado'): ?>
            <div class="pago-card">
                <div class="pago-card__info">
                    <?php if($pedido->metodo_pago === 'mercadopago'): ?>
                        <?php if($pago && $pago->esAprobado()): ?>
                            <div class="pago-card__icono pago-card__icono--aprobado">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                            <div class="pago-card__detalles">
                                <strong>Pago Aprobado (Mercado Pago)</strong>
                                <p>Operación confirmada · Total abonado: $ <?php echo number_format((float)$pedido->total, 2, ',', '.'); ?></p>
                            </div>
                        <?php else: ?>
                            <div class="pago-card__icono pago-card__icono--pendiente">
                                <i class="fa-solid fa-qrcode"></i>
                            </div>
                            <div class="pago-card__detalles">
                                <strong>Pago Pendiente (Mercado Pago)</strong>
                                <p>Completa el pago online con tarjeta, débito o dinero en cuenta.</p>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if($pago && $pago->esAprobado()): ?>
                            <div class="pago-card__icono pago-card__icono--aprobado">
                                <i class="fa-solid fa-circle-check"></i>
                            </div>
                            <div class="pago-card__detalles">
                                <strong>Cobro en Efectivo Registrado</strong>
                                <p>Total recibido: $ <?php echo number_format((float)$pedido->total, 2, ',', '.'); ?></p>
                            </div>
                        <?php else: ?>
                            <div class="pago-card__icono pago-card__icono--efectivo">
                                <i class="fa-solid fa-money-bill-wave"></i>
                            </div>
                            <div class="pago-card__detalles">
                                <strong>Pago en Efectivo (Contra Entrega / Retiro)</strong>
                                <p>Abonas $ <?php echo number_format((float)$pedido->total, 2, ',', '.'); ?> al recibir tu pedido o en mostrador.</p>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="pago-card__acciones">
                    <?php if($pago && $pago->esAprobado() && $comprobante): ?>
                        <a href="/comprobante?id=<?php echo (int)$comprobante->id; ?>" class="boton boton--secundario boton--pequeno" target="_blank">
                            <i class="fa-solid fa-receipt"></i>
                            <span>Ver Comprobante (<?php echo s($comprobante->codigoFormateado()); ?>)</span>
                        </a>
                    <?php elseif($pedido->metodo_pago === 'mercadopago' && (!$pago || $pago->esPendiente())): ?>
                        <?php if(!empty($preferenciaUrl)): ?>
                            <a href="<?php echo s($preferenciaUrl); ?>" class="pago-card__boton-mp">
                                <i class="fa-solid fa-credit-card"></i>
                                <span>Pagar con Mercado Pago</span>
                            </a>
                        <?php endif; ?>

                        <!-- Simulador de pago para desarrollo/localhost -->
                        <?php
                        /*
                        <form method="POST" action="/pago/simular" style="margin: 0;">
                            <input type="hidden" name="pedido_id" value="<?php echo (int)$pedido->id; ?>">
                            <button type="submit" class="boton boton--secundario boton--pequeno" title="Simular acreditación para pruebas de desarrollo">
                                <i class="fa-solid fa-vial"></i>
                                <span>Simular Pago Aprobado (Test)</span>
                            </button>
                        </form>
                        */
                        ?>
                    <?php elseif($pedido->metodo_pago === 'efectivo' && (!$pago || $pago->esPendiente())): ?>
                        <!-- El cobro en efectivo solo es gestionado por personal o repartidor -->
                        <span class="badge badge--alerta" style="font-size: 1.3rem; padding: 0.6rem 1.2rem;">
                            <i class="fa-solid fa-clock"></i> Pendiente de cobro al recibir
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- DETALLES DEL PEDIDO -->
        <div class="admin-tarjeta u-separador-abajo">
            <h2 class="admin-tarjeta__titulo">
                <i class="fa-solid fa-receipt"></i>
                <span>Artículos del Pedido</span>
            </h2>

            <div class="tabla-contenedor u-separador-abajo">
                <table class="tabla">
                    <thead class="tabla__thead">
                        <tr>
                            <th class="tabla__th">Producto & Variantes</th>
                            <th class="tabla__th">Cant.</th>
                            <th class="tabla__th">Unitario</th>
                            <th class="tabla__th">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="tabla__tbody">
                        <?php foreach($detalles as $det): ?>
                            <tr class="tabla__tr">
                                <td class="tabla__td">
                                    <strong class="u-texto-negrita"><?php echo s($det->nombre_producto); ?></strong>
                                    <?php if(!empty($det->opciones_texto)): ?>
                                        <div style="font-size: 1.2rem; color: #64748b; margin-top: 0.3rem;">
                                            <?php echo s($det->opciones_texto); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="tabla__td">
                                    <span class="badge badge--orden"><?php echo (int)$det->cantidad; ?></span>
                                </td>
                                <td class="tabla__td">
                                    <span>$ <?php echo number_format((float)$det->precio_unitario, 2, ',', '.'); ?></span>
                                </td>
                                <td class="tabla__td">
                                    <strong class="precio-destacado">$ <?php echo number_format((float)$det->subtotal, 2, ',', '.'); ?></strong>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- DESGLOSE TOTALES -->
            <div class="formulario--admin" style="max-width: 40rem; margin-left: auto;">
                <div class="resumen-pedido__fila">
                    <span>Subtotal Comida:</span>
                    <strong>$ <?php echo number_format((float)$pedido->subtotal, 2, ',', '.'); ?></strong>
                </div>
                <div class="resumen-pedido__fila">
                    <span>Costo de Envío:</span>
                    <strong><?php echo ((float)$pedido->costo_envio > 0) ? '$ ' . number_format((float)$pedido->costo_envio, 2, ',', '.') : 'Gratis ($0)'; ?></strong>
                </div>
                <div class="resumen-pedido__fila resumen-pedido__fila--total">
                    <span>Total Abonado / A Pagar:</span>
                    <span style="color: #ea580c;">$ <?php echo number_format((float)$pedido->total, 2, ',', '.'); ?></span>
                </div>
            </div>
        </div>

        <!-- INFORMACIÓN DE CONTACTO Y ENTREGA -->
        <div class="grid grid--2">
            <div class="admin-tarjeta">
                <h3 class="admin-tarjeta__titulo">
                    <i class="fa-solid fa-location-dot"></i>
                    <span>Modalidad de Entrega</span>
                </h3>
                <p><strong>Tipo:</strong> <?php echo ($pedido->tipo === 'delivery') ? 'Delivery a Domicilio' : 'Retiro por el Local'; ?></p>
                <p><strong>Dirección:</strong> <?php echo s($pedido->direccion_texto); ?></p>
                <?php if($pedido->tipo === 'delivery' && (float)$pedido->distancia_km > 0): ?>
                    <p><strong>Distancia estimada:</strong> <?php echo number_format((float)$pedido->distancia_km, 1, ',', '.'); ?> km</p>
                <?php endif; ?>
            </div>

            <div class="admin-tarjeta">
                <h3 class="admin-tarjeta__titulo">
                    <i class="fa-solid fa-user"></i>
                    <span>Datos de Contacto & Pago</span>
                </h3>
                <p><strong>Cliente:</strong> <?php echo s($pedido->nombre_contacto); ?></p>
                <p><strong>Teléfono:</strong> <?php echo s($pedido->telefono_contacto); ?></p>
                <p><strong>Método de Pago:</strong> <?php echo ($pedido->metodo_pago === 'mercadopago') ? 'Mercado Pago (Online)' : 'Efectivo / Al recibir'; ?></p>
            </div>
        </div>
    </div>
</div>
