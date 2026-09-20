<?php
    $tieneLogo = (!empty($comercio->logo) && file_exists(CARPETA_IMAGENES . $comercio->logo));
?>
<div class="comprobante-wrapper">
    <!-- BARRA SUPERIOR DE ACCIONES (OCULTA AL IMPRIMIR) -->
    <div class="comprobante-acciones-top">
        <a href="/pedido/seguimiento?id=<?php echo (int)$pedido->id; ?>" class="boton boton--secundario boton--pequeno">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Volver al Pedido</span>
        </a>

        <button type="button" class="boton boton--primario boton--pequeno" onclick="window.print();">
            <i class="fa-solid fa-print"></i>
            <span>Imprimir Comprobante</span>
        </button>
    </div>

    <!-- TICKET DE CONTROL INTERNO -->
    <article class="comprobante-ticket" id="ticket-imprimible">
        <!-- CABECERA CON DATOS DEL COMERCIO -->
        <header class="comprobante-ticket__cabecera">
            <?php if($tieneLogo): ?>
                <img src="/imagenes/<?php echo s($comercio->logo); ?>" alt="<?php echo s($comercio->nombre); ?>" class="comprobante-ticket__logo">
            <?php else: ?>
                <div class="auth__icono" style="margin-bottom: 0.8rem;">
                    <i class="fa-solid fa-utensils"></i>
                </div>
            <?php endif; ?>

            <h1 class="comprobante-ticket__comercio"><?php echo s($comercio->nombre ?? 'Retail Delivery'); ?></h1>
            <p class="comprobante-ticket__datos-comercio">
                <?php echo s($comercio->direccion ?? 'Av. Principal 1234'); ?><br>
                <?php if(!empty($comercio->telefono)): ?>
                    Tel: <?php echo s($comercio->telefono); ?>
                <?php endif; ?>
            </p>

            <div class="comprobante-ticket__numero-badge">
                <?php echo s($comprobante->codigoFormateado()); ?>
            </div>
        </header>

        <!-- DATOS DEL PEDIDO Y CLIENTE -->
        <section class="comprobante-ticket__seccion">
            <div class="comprobante-ticket__fila-info">
                <span>Fecha de Emisión:</span>
                <strong><?php echo s(date('d/m/Y H:i', strtotime($comprobante->fecha_emision ?? 'now'))); ?> hs</strong>
            </div>
            <div class="comprobante-ticket__fila-info">
                <span>Pedido Asociado:</span>
                <strong>#<?php echo (int)$pedido->id; ?></strong>
            </div>
            <div class="comprobante-ticket__fila-info">
                <span>Cliente:</span>
                <strong><?php echo s($pedido->nombre_contacto); ?></strong>
            </div>
            <div class="comprobante-ticket__fila-info">
                <span>Teléfono:</span>
                <strong><?php echo s($pedido->telefono_contacto); ?></strong>
            </div>
            <div class="comprobante-ticket__fila-info">
                <span>Modalidad:</span>
                <strong><?php echo ($pedido->tipo === 'delivery') ? 'Delivery a Domicilio' : 'Retiro en el Local'; ?></strong>
            </div>
            <?php if($pedido->tipo === 'delivery'): ?>
                <div class="comprobante-ticket__fila-info">
                    <span>Dirección de Entrega:</span>
                    <strong style="max-width: 60%; text-align: right;"><?php echo s($pedido->direccion_texto); ?></strong>
                </div>
            <?php endif; ?>
            <div class="comprobante-ticket__fila-info">
                <span>Forma de Pago:</span>
                <strong><?php echo ($pedido->metodo_pago === 'mercadopago') ? 'Mercado Pago (Online)' : 'Efectivo'; ?></strong>
            </div>
        </section>

        <!-- TABLA DE ARTÍCULOS VENDIDOS -->
        <table class="comprobante-ticket__tabla">
            <thead>
                <tr>
                    <th class="td-cant">Cant</th>
                    <th>Descripción</th>
                    <th class="th-precio">Precio</th>
                    <th class="th-precio">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($detalles as $d): ?>
                    <tr>
                        <td class="td-cant"><?php echo (int)$d->cantidad; ?></td>
                        <td>
                            <strong><?php echo s($d->nombre_producto); ?></strong>
                            <?php if(!empty($d->opciones_texto)): ?>
                                <div style="font-size: 1.1rem; color: #64748b; margin-top: 0.2rem;">
                                    <?php echo s($d->opciones_texto); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="td-precio">$ <?php echo number_format((float)$d->precio_unitario, 2, ',', '.'); ?></td>
                        <td class="td-precio"><strong>$ <?php echo number_format((float)$d->subtotal, 2, ',', '.'); ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- DESGLOSE Y TOTALES -->
        <section class="comprobante-ticket__totales">
            <div class="comprobante-ticket__fila-total">
                <span>Subtotal Comida:</span>
                <span>$ <?php echo number_format((float)$pedido->subtotal, 2, ',', '.'); ?></span>
            </div>
            <div class="comprobante-ticket__fila-total">
                <span>Costo de Envío:</span>
                <span><?php echo ((float)$pedido->costo_envio > 0) ? '$ ' . number_format((float)$pedido->costo_envio, 2, ',', '.') : 'Gratis ($0)'; ?></span>
            </div>
            <div class="comprobante-ticket__fila-total comprobante-ticket__fila-total--gran-total">
                <span>TOTAL ABONADO:</span>
                <span>$ <?php echo number_format((float)$comprobante->total, 2, ',', '.'); ?></span>
            </div>

            <div style="text-align: center;">
                <div class="comprobante-ticket__sello-pagado">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>Comprobante Emitido · Pagado</span>
                </div>
            </div>
        </section>

        <!-- PIE DE TICKET -->
        <footer class="comprobante-ticket__pie">
            <p style="margin: 0 0 0.4rem 0;">¡Gracias por tu compra en <?php echo s($comercio->nombre ?? 'Retail Delivery'); ?>!</p>
            <small>Documento no válido como factura fiscal · Comprobante interno numerado</small>
        </footer>
    </article>
</div>
