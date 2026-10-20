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

    <!-- ENCABEZADO KDS -->
    <header class="kds-header">
        <div class="kds-header__info">
            <h1 class="kds-header__titulo">
                <i class="fa-solid fa-fire-burner"></i>
                <span>Consola de Cocina & Despacho</span>
            </h1>
            <p class="kds-header__subtitulo">
                Gestión operativa en tiempo real · 
                <?php if($sesionCaja): ?>
                    <span class="badge badge--exito" style="font-size: 1.1rem;">Caja Abierta</span>
                <?php else: ?>
                    <span class="badge badge--alerta" style="font-size: 1.1rem;">Caja Cerrada</span>
                <?php endif; ?>
            </p>
        </div>

        <div class="kds-header__herramientas">
            <div id="kds-reloj" class="kds-header__reloj">
                <i class="fa-solid fa-clock"></i>
                <span id="reloj-texto">--:--:--</span>
            </div>

            <div class="kds-control">
                <label for="kds-autorefresco"><i class="fa-solid fa-arrows-rotate"></i> Auto-refresco:</label>
                <select id="kds-autorefresco">
                    <option value="15">Cada 15s</option>
                    <option value="30" selected>Cada 30s</option>
                    <option value="0">Desactivado</option>
                </select>
            </div>

            <button type="button" id="kds-btn-audio" class="boton boton--secundario boton--pequeno" title="Activar sonido de nuevas comandas">
                <i class="fa-solid fa-volume-high"></i>
                <span id="audio-estado-texto">Sonido: ON</span>
            </button>

            <button type="button" id="kds-btn-refrescar" class="boton boton--secundario boton--pequeno" title="Actualizar ahora">
                <i class="fa-solid fa-rotate-right"></i>
            </button>

            <button type="button" class="boton boton--primario boton--pequeno" onclick="abrirModalPedidoPresencial()" style="background-color: #059669; border-color: #059669;">
                <i class="fa-solid fa-plus"></i>
                <span>Nuevo Pedido Mostrador</span>
            </button>

            <a href="/cocina/caja" class="boton boton--primario boton--pequeno">
                <i class="fa-solid fa-cash-register"></i>
                <span>Control de Caja</span>
            </a>
        </div>
    </header>

    <!-- NAVEGACIÓN PESTAÑAS -->
    <div class="kds-tabs" role="tablist">
        <div id="tab-btn-activas" class="kds-tabs__tab kds-tabs__tab--activo" role="tab" onclick="cambiarTabKDS('activas')">
            <i class="fa-solid fa-kitchen-set"></i>
            <span>Comandas Activas (<?php echo count($nuevos) + count($enCocina) + count($despacho); ?>)</span>
        </div>
        <div id="tab-btn-historial" class="kds-tabs__tab" role="tab" onclick="cambiarTabKDS('historial')">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>Historial Finalizados Hoy (<?php echo count($finalizados); ?>)</span>
        </div>
    </div>

    <!-- PESTAÑA 1: TABLERO KANBAN EN VIVO -->
    <div id="kds-vista-activas">

        <!-- RESUMEN DE PRODUCCIÓN ALL-DAY (PROPUESTA 3) -->
        <div class="kds-allday">
            <div class="kds-allday__cabecera" onclick="toggleKdsAllDay()" title="Clic para expandir o colapsar el resumen">
                <div class="kds-allday__titulo">
                    <i class="fa-solid fa-fire-flame-curved"></i>
                    <span>Resumen de Producción (All-Day Counter)</span>
                </div>
                <div class="kds-allday__meta">
                    <span class="kds-allday__total-badge" id="kds-allday-total-badge">
                        <?php echo (int)($totalPlatosEnMarcha ?? 0); ?> platos en marcha
                    </span>
                    <i class="fa-solid fa-chevron-down kds-allday__toggle-icon" id="kds-allday-toggle-icon"></i>
                </div>
            </div>
            <div class="kds-allday__cuerpo" id="kds-allday-cuerpo">
                <?php if(empty($allDayItems)): ?>
                    <p class="kds-allday__vacio">
                        <i class="fa-solid fa-circle-check" style="color: #10b981;"></i>
                        <span>No hay platos pendientes en cocina en este momento.</span>
                    </p>
                <?php else: ?>
                    <?php foreach($allDayItems as $item): ?>
                        <div class="kds-allday__pill" title="<?php echo !empty($item['detalles_resumen']) ? htmlspecialchars(implode(' · ', $item['detalles_resumen']), ENT_QUOTES, 'UTF-8') : ''; ?>">
                            <span class="kds-allday__pill-cant"><?php echo (int)$item['cantidad']; ?>x</span>
                            <span class="kds-allday__pill-nombre"><?php echo s($item['nombre']); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php
        $comandasDataStore = [];
        $prepararModalData = function($ped, $detalles, $esPresencial, $comprobante, $pago) use (&$comandasDataStore) {
            $detallesArray = [];
            foreach($detalles as $det) {
                $detallesArray[] = [
                    'id' => (int)$det->id,
                    'nombre' => $det->nombre_producto,
                    'cantidad' => (int)$det->cantidad,
                    'opciones_texto' => $det->opciones_texto,
                    'badges_html' => \Classes\KdsHelper::renderBadgesHTML($det->opciones_texto)
                ];
            }
            $info = [
                'id' => (int)$ped->id,
                'tipo' => $ped->tipo,
                'tipo_texto' => $ped->tipo === 'delivery' ? 'Delivery' : ($esPresencial ? 'Mostrador' : 'Retiro Online'),
                'estado' => $ped->estado,
                'estado_texto' => $ped->estadoTexto(),
                'cliente' => $ped->nombre_contacto,
                'telefono' => $ped->telefono_contacto,
                'direccion' => $ped->direccion_texto,
                'hora' => date('H:i', strtotime($ped->creado_en)),
                'minutos' => $ped->minutosTranscurridos(),
                'metodo_pago' => strtoupper($ped->metodo_pago),
                'pago_aprobado' => ($pago && $pago->esAprobado()),
                'total' => number_format((float)$ped->total, 2, ',', '.'),
                'comprobante_id' => $comprobante ? (int)$comprobante->id : null,
                'items' => $detallesArray
            ];
            $comandasDataStore[(int)$ped->id] = $info;
            return $info;
        };
        ?>

        <div class="kds-tablero">
            
            <!-- COLUMNA 1: POR PREPARAR -->
            <div class="kds-columna kds-columna--nuevos">
                <div class="kds-columna__cabecera">
                    <h2 class="kds-columna__titulo">
                        <i class="fa-solid fa-inbox" style="color: #f59e0b;"></i>
                        <span>1. Por Preparar</span>
                    </h2>
                    <span class="kds-columna__badge kds-columna__badge--nuevos"><?php echo count($nuevos); ?></span>
                </div>

                <div class="kds-columna__cuerpo">
                    <?php if(empty($nuevos)): ?>
                        <div class="kds-columna__vacio">
                            <i class="fa-solid fa-circle-check"></i>
                            <p>No hay pedidos pendientes de ingresar a cocina.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($nuevos as $ped): ?>
                            <?php 
                                $detalles = $ped->detalles();
                                $pago = $ped->pago();
                                $comprobante = $ped->comprobante();
                                $esPresencial = ($ped->telefono_contacto === 'Mostrador');
                                $modalData = $prepararModalData($ped, $detalles, $esPresencial, $comprobante, $pago);
                            ?>
                            <div class="comanda-card" data-id="<?php echo (int)$ped->id; ?>">
                                <div class="comanda-card__encabezado">
                                    <span class="comanda-card__numero">#<?php echo (int)$ped->id; ?></span>
                                    <span class="comanda-card__tipo-badge comanda-card__tipo-badge--<?php echo $ped->tipo; ?><?php echo $esPresencial ? ' comanda-card__tipo-badge--presencial' : ''; ?>">
                                        <i class="fa-solid fa-<?php echo $ped->tipo === 'delivery' ? 'motorcycle' : ($esPresencial ? 'shop' : 'store'); ?>"></i>
                                        <span><?php echo $ped->tipo === 'delivery' ? 'Delivery' : ($esPresencial ? 'Mostrador' : 'Retiro Online'); ?></span>
                                    </span>
                                </div>

                                <div class="comanda-card__meta">
                                    <span><?php echo date('H:i', strtotime($ped->creado_en)); ?> hs</span>
                                    <span class="comanda-card__timer <?php echo $ped->alertaTiempoClase(); ?>">
                                        <i class="fa-regular fa-clock"></i>
                                        <span><?php echo $ped->minutosTranscurridos(); ?> min</span>
                                    </span>
                                </div>

                                <div class="comanda-card__cliente">
                                    <strong><?php echo s($ped->nombre_contacto); ?></strong>
                                    <?php if($esPresencial): ?>
                                        · <span class="badge badge--destacado" style="font-size: 1.1rem; padding: 0.2rem 0.6rem;">Cliente en Local</span>
                                    <?php else: ?>
                                        · <?php echo s($ped->telefono_contacto); ?>
                                    <?php endif; ?>
                                    <?php if($ped->tipo === 'delivery'): ?>
                                        <p><i class="fa-solid fa-location-dot"></i> <?php echo s($ped->direccion_texto); ?></p>
                                    <?php endif; ?>
                                </div>

                                <div class="comanda-card__items comanda-card__items--clickeable" onclick="abrirModalDetalleComanda(<?php echo (int)$ped->id; ?>)" title="Clic para abrir comanda en pantalla completa">
                                    <?php foreach($detalles as $det): ?>
                                        <div class="comanda-card__item">
                                            <span class="comanda-card__item-cant"><?php echo (int)$det->cantidad; ?>x</span>
                                            <div class="comanda-card__item-info">
                                                <div class="comanda-card__item-nombre"><?php echo s($det->nombre_producto); ?></div>
                                                <?php echo \Classes\KdsHelper::renderBadgesHTML($det->opciones_texto); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="comanda-card__pago-info">
                                    <span class="badge <?php echo ($pago && $pago->esAprobado()) ? 'badge--exito' : 'badge--alerta'; ?>">
                                        <?php echo ($pago && $pago->esAprobado()) ? 'Pago Aprobado (' . strtoupper($ped->metodo_pago) . ')' : 'Pendiente (' . strtoupper($ped->metodo_pago) . ')'; ?>
                                    </span>
                                    <strong style="font-size: 1.4rem; color: #ea580c;">$ <?php echo number_format((float)$ped->total, 2, ',', '.'); ?></strong>
                                </div>

                                <div class="comanda-card__acciones">
                                    <form method="POST" action="/cocina/cambiar-estado" style="margin: 0;">
                                        <input type="hidden" name="pedido_id" value="<?php echo (int)$ped->id; ?>">
                                        <input type="hidden" name="nuevo_estado" value="en_preparacion">
                                        <button type="submit" class="boton boton--primario u-ancho-completo">
                                            <i class="fa-solid fa-fire"></i>
                                            <span>Enviar a Cocina</span>
                                        </button>
                                    </form>

                                    <div class="comanda-card__acciones-fila">
                                        <button type="button" class="boton boton--secundario boton--pequeno" onclick="abrirModalDetalleComanda(<?php echo (int)$ped->id; ?>)" title="Ver checklist y detalles">
                                            <i class="fa-solid fa-list-check"></i>
                                            <span>Detalle</span>
                                        </button>

                                        <?php if($comprobante): ?>
                                            <a href="/comprobante?id=<?php echo (int)$comprobante->id; ?>" target="_blank" class="boton boton--secundario boton--pequeno u-ancho-completo" title="Imprimir comanda térmica">
                                                <i class="fa-solid fa-print"></i>
                                                <span>Ticket</span>
                                            </a>
                                        <?php endif; ?>

                                        <button type="button" class="boton boton--secundario boton--pequeno" style="color: #ef4444;" onclick="abrirModalCancelar(<?php echo (int)$ped->id; ?>)" title="Cancelar comanda">
                                            <i class="fa-solid fa-ban"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- COLUMNA 2: EN COCINA / ELABORACIÓN -->
            <div class="kds-columna kds-columna--cocina">
                <div class="kds-columna__cabecera">
                    <h2 class="kds-columna__titulo">
                        <i class="fa-solid fa-utensils" style="color: #3b82f6;"></i>
                        <span>2. En Cocina</span>
                    </h2>
                    <span class="kds-columna__badge kds-columna__badge--cocina"><?php echo count($enCocina); ?></span>
                </div>

                <div class="kds-columna__cuerpo">
                    <?php if(empty($enCocina)): ?>
                        <div class="kds-columna__vacio">
                            <i class="fa-solid fa-kitchen-set"></i>
                            <p>No hay platos en elaboración en este momento.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($enCocina as $ped): ?>
                            <?php 
                                $detalles = $ped->detalles();
                                $pago = $ped->pago();
                                $comprobante = $ped->comprobante();
                                $esPresencial = ($ped->telefono_contacto === 'Mostrador');
                                $modalData = $prepararModalData($ped, $detalles, $esPresencial, $comprobante, $pago);
                            ?>
                            <div class="comanda-card" data-id="<?php echo (int)$ped->id; ?>">
                                <div class="comanda-card__encabezado">
                                    <span class="comanda-card__numero">#<?php echo (int)$ped->id; ?></span>
                                    <span class="comanda-card__tipo-badge comanda-card__tipo-badge--<?php echo $ped->tipo; ?><?php echo $esPresencial ? ' comanda-card__tipo-badge--presencial' : ''; ?>">
                                        <i class="fa-solid fa-<?php echo $ped->tipo === 'delivery' ? 'motorcycle' : ($esPresencial ? 'shop' : 'store'); ?>"></i>
                                        <span><?php echo $ped->tipo === 'delivery' ? 'Delivery' : ($esPresencial ? 'Mostrador' : 'Retiro Online'); ?></span>
                                    </span>
                                </div>

                                <div class="comanda-card__meta">
                                    <span><?php echo date('H:i', strtotime($ped->creado_en)); ?> hs</span>
                                    <span class="comanda-card__timer <?php echo $ped->alertaTiempoClase(); ?>">
                                        <i class="fa-regular fa-clock"></i>
                                        <span><?php echo $ped->minutosTranscurridos(); ?> min</span>
                                    </span>
                                </div>

                                <div class="comanda-card__cliente">
                                    <strong><?php echo s($ped->nombre_contacto); ?></strong>
                                    <?php if($esPresencial): ?>
                                        · <span class="badge badge--destacado" style="font-size: 1.1rem; padding: 0.2rem 0.6rem;">Cliente en Local</span>
                                    <?php else: ?>
                                        · <?php echo s($ped->telefono_contacto); ?>
                                    <?php endif; ?>
                                    <?php if($ped->tipo === 'delivery'): ?>
                                        <p><i class="fa-solid fa-location-dot"></i> <?php echo s($ped->direccion_texto); ?></p>
                                    <?php endif; ?>
                                </div>

                                <div class="comanda-card__items comanda-card__items--clickeable" onclick="abrirModalDetalleComanda(<?php echo (int)$ped->id; ?>)" title="Clic para abrir comanda en pantalla completa">
                                    <?php foreach($detalles as $det): ?>
                                        <div class="comanda-card__item">
                                            <span class="comanda-card__item-cant"><?php echo (int)$det->cantidad; ?>x</span>
                                            <div class="comanda-card__item-info">
                                                <div class="comanda-card__item-nombre"><?php echo s($det->nombre_producto); ?></div>
                                                <?php echo \Classes\KdsHelper::renderBadgesHTML($det->opciones_texto); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="comanda-card__acciones">
                                    <?php if($ped->tipo === 'delivery'): ?>
                                        <button type="button" class="boton boton--primario u-ancho-completo" onclick="abrirModalAsignarRepartidor(<?php echo (int)$ped->id; ?>)">
                                            <i class="fa-solid fa-motorcycle"></i>
                                            <span>Asignar Repartidor</span>
                                        </button>
                                    <?php else: ?>
                                        <form method="POST" action="/cocina/cambiar-estado" style="margin: 0;">
                                            <input type="hidden" name="pedido_id" value="<?php echo (int)$ped->id; ?>">
                                            <input type="hidden" name="nuevo_estado" value="despachar_retiro">
                                            <button type="submit" class="boton boton--primario u-ancho-completo">
                                                <i class="fa-solid fa-bell-concierge"></i>
                                                <span>Listo para Retirar</span>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <div class="comanda-card__acciones-fila">
                                        <button type="button" class="boton boton--secundario boton--pequeno" onclick="abrirModalDetalleComanda(<?php echo (int)$ped->id; ?>)" title="Ver checklist y detalles">
                                            <i class="fa-solid fa-list-check"></i>
                                            <span>Detalle</span>
                                        </button>

                                        <?php if($comprobante): ?>
                                            <a href="/comprobante?id=<?php echo (int)$comprobante->id; ?>" target="_blank" class="boton boton--secundario boton--pequeno u-ancho-completo" title="Imprimir comanda térmica">
                                                <i class="fa-solid fa-print"></i>
                                                <span>Ticket</span>
                                            </a>
                                        <?php endif; ?>

                                        <button type="button" class="boton boton--secundario boton--pequeno" style="color: #ef4444;" onclick="abrirModalCancelar(<?php echo (int)$ped->id; ?>)" title="Cancelar comanda">
                                            <i class="fa-solid fa-ban"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- COLUMNA 3: LISTOS / EN DESPACHO -->
            <div class="kds-columna kds-columna--despacho">
                <div class="kds-columna__cabecera">
                    <h2 class="kds-columna__titulo">
                        <i class="fa-solid fa-route" style="color: #10b981;"></i>
                        <span>3. Despacho / Retiro</span>
                    </h2>
                    <span class="kds-columna__badge kds-columna__badge--despacho"><?php echo count($despacho); ?></span>
                </div>

                <div class="kds-columna__cuerpo">
                    <?php if(empty($despacho)): ?>
                        <div class="kds-columna__vacio">
                            <i class="fa-solid fa-paper-plane"></i>
                            <p>No hay pedidos en viaje o esperando retiro.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($despacho as $ped): ?>
                            <?php 
                                $detalles = $ped->detalles();
                                $asig = $ped->asignacionRepartidor();
                                $repartidor = $ped->repartidor();
                                $comprobante = $ped->comprobante();
                                $pago = $ped->pago();
                                $esPresencial = ($ped->telefono_contacto === 'Mostrador');
                                $requiereCobroRetiro = ($ped->tipo !== 'delivery' && $ped->metodo_pago === 'efectivo' && (!$pago || $pago->esPendiente()));
                                $modalData = $prepararModalData($ped, $detalles, $esPresencial, $comprobante, $pago);
                            ?>
                            <div class="comanda-card" data-id="<?php echo (int)$ped->id; ?>">
                                <div class="comanda-card__encabezado">
                                    <span class="comanda-card__numero">#<?php echo (int)$ped->id; ?></span>
                                    <span class="comanda-card__tipo-badge comanda-card__tipo-badge--<?php echo $ped->tipo; ?><?php echo $esPresencial ? ' comanda-card__tipo-badge--presencial' : ''; ?>">
                                        <i class="fa-solid fa-<?php echo $ped->tipo === 'delivery' ? 'motorcycle' : ($esPresencial ? 'shop' : 'store'); ?>"></i>
                                        <span><?php echo $ped->tipo === 'delivery' ? 'En Viaje' : 'Listo en Local'; ?></span>
                                    </span>
                                </div>

                                <div class="comanda-card__meta">
                                    <span>Salida: <?php echo $asig ? date('H:i', strtotime($asig->hora_asignado)) : date('H:i', strtotime($ped->creado_en)); ?> hs</span>
                                    <span class="comanda-card__timer comanda-timer--normal">
                                        <i class="fa-solid fa-circle-check"></i>
                                        <span>Despachado</span>
                                    </span>
                                </div>

                                <div class="comanda-card__cliente">
                                    <strong><?php echo s($ped->nombre_contacto); ?></strong>
                                    <?php if($esPresencial): ?>
                                        · <span class="badge badge--destacado" style="font-size: 1.1rem; padding: 0.2rem 0.6rem;">Cliente en Local</span>
                                    <?php else: ?>
                                        · <?php echo s($ped->telefono_contacto); ?>
                                    <?php endif; ?>
                                    <?php if($ped->tipo === 'delivery'): ?>
                                        <p><i class="fa-solid fa-location-dot"></i> <?php echo s($ped->direccion_texto); ?></p>
                                        <p style="margin-top: 0.4rem; color: #1e40af;">
                                            <i class="fa-solid fa-helmet-safety"></i> Repartidor: <strong><?php echo s($repartidor->nombre ?? 'Asignado'); ?></strong>
                                        </p>
                                    <?php else: ?>
                                        <p style="color: #9d174d;"><i class="fa-solid fa-box-open"></i> Esperando entrega en mostrador</p>
                                    <?php endif; ?>
                                </div>

                                <div class="comanda-card__items comanda-card__items--clickeable" onclick="abrirModalDetalleComanda(<?php echo (int)$ped->id; ?>)" title="Clic para abrir comanda en pantalla completa">
                                    <?php foreach($detalles as $det): ?>
                                        <div class="comanda-card__item">
                                            <span class="comanda-card__item-cant"><?php echo (int)$det->cantidad; ?>x</span>
                                            <div class="comanda-card__item-info">
                                                <div class="comanda-card__item-nombre"><?php echo s($det->nombre_producto); ?></div>
                                                <?php echo \Classes\KdsHelper::renderBadgesHTML($det->opciones_texto); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="comanda-card__pago-info">
                                    <span class="badge <?php echo ($pago && $pago->esAprobado()) ? 'badge--exito' : 'badge--alerta'; ?>">
                                        <?php echo ($pago && $pago->esAprobado()) ? 'Pago Aprobado (' . strtoupper($ped->metodo_pago) . ')' : 'Pendiente de Cobro (' . strtoupper($ped->metodo_pago) . ')'; ?>
                                    </span>
                                    <strong style="font-size: 1.4rem; color: #ea580c;">$ <?php echo number_format((float)$ped->total, 2, ',', '.'); ?></strong>
                                </div>

                                <div class="comanda-card__acciones">
                                    <?php if($requiereCobroRetiro): ?>
                                        <form method="POST" action="/cocina/cobrar-retiro" style="margin: 0;">
                                            <input type="hidden" name="pedido_id" value="<?php echo (int)$ped->id; ?>">
                                            <button type="submit" class="boton boton--primario u-ancho-completo" style="background-color: #059669; border-color: #059669;">
                                                <i class="fa-solid fa-cash-register"></i>
                                                <span>Cobrar en Mostrador ($ <?php echo number_format((float)$ped->total, 2, ',', '.'); ?>) y Entregar</span>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" action="/cocina/cambiar-estado" style="margin: 0;">
                                            <input type="hidden" name="pedido_id" value="<?php echo (int)$ped->id; ?>">
                                            <input type="hidden" name="nuevo_estado" value="entregado">
                                            <button type="submit" class="boton boton--primario u-ancho-completo" style="background-color: #16a34a; border-color: #16a34a;">
                                                <i class="fa-solid fa-house-chimney-check"></i>
                                                <span>Marcar Entregado</span>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <div class="comanda-card__acciones-fila" style="margin-top: 0.6rem;">
                                        <button type="button" class="boton boton--secundario boton--pequeno" onclick="abrirModalDetalleComanda(<?php echo (int)$ped->id; ?>)" title="Ver detalle completo">
                                            <i class="fa-solid fa-list-check"></i>
                                            <span>Detalle</span>
                                        </button>

                                        <?php if($comprobante): ?>
                                            <a href="/comprobante?id=<?php echo (int)$comprobante->id; ?>" target="_blank" class="boton boton--secundario boton--pequeno u-ancho-completo" title="Ver comprobante">
                                                <i class="fa-solid fa-receipt"></i>
                                                <span>Ticket (<?php echo s($comprobante->codigoFormateado()); ?>)</span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <!-- PESTAÑA 2: HISTORIAL DE PEDIDOS FINALIZADOS HOY -->
    <div id="kds-vista-historial" style="display: none;">
        <div class="admin-tarjeta">
            <h2 class="admin-tarjeta__titulo">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span>Comandas Finalizadas Hoy (<?php echo date('d/m/Y'); ?>)</span>
            </h2>

            <?php if(empty($finalizados)): ?>
                <p style="text-align: center; color: #64748b; padding: 2rem 0;">Aún no hay pedidos entregados o cancelados en el día de hoy.</p>
            <?php else: ?>
                <div class="tabla-contenedor">
                    <table class="tabla">
                        <thead class="tabla__thead">
                            <tr>
                                <th class="tabla__th"># Pedido</th>
                                <th class="tabla__th">Hora</th>
                                <th class="tabla__th">Cliente</th>
                                <th class="tabla__th">Modalidad</th>
                                <th class="tabla__th">Estado</th>
                                <th class="tabla__th">Total</th>
                                <th class="tabla__th">Comprobante</th>
                            </tr>
                        </thead>
                        <tbody class="tabla__tbody">
                            <?php foreach($finalizados as $pf): ?>
                                <?php $comp = $pf->comprobante(); ?>
                                <tr class="tabla__tr">
                                    <td class="tabla__td"><strong class="u-texto-negrita">#<?php echo (int)$pf->id; ?></strong></td>
                                    <td class="tabla__td"><?php echo date('H:i', strtotime($pf->creado_en)); ?> hs</td>
                                    <td class="tabla__td"><?php echo s($pf->nombre_contacto); ?></td>
                                    <td class="tabla__td">
                                        <span class="badge <?php echo $pf->tipo === 'delivery' ? 'badge--info' : 'badge--destacado'; ?>">
                                            <?php echo ucfirst($pf->tipo); ?>
                                        </span>
                                    </td>
                                    <td class="tabla__td">
                                        <span class="badge <?php echo $pf->estadoBadgeClase(); ?>">
                                            <?php echo $pf->estadoTexto(); ?>
                                        </span>
                                    </td>
                                    <td class="tabla__td">
                                        <strong class="precio-destacado">$ <?php echo number_format((float)$pf->total, 2, ',', '.'); ?></strong>
                                    </td>
                                    <td class="tabla__td">
                                        <?php if($comp): ?>
                                            <a href="/comprobante?id=<?php echo (int)$comp->id; ?>" target="_blank" class="boton boton--secundario boton--pequeno">
                                                <i class="fa-solid fa-receipt"></i>
                                                <span><?php echo s($comp->codigoFormateado()); ?></span>
                                            </a>
                                        <?php else: ?>
                                            <span style="color: #94a3b8;">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- MODAL DE ENFOQUE Y CHECKLIST DE COMANDA (PROPUESTA 2) -->
<div id="modal-comanda-detalle" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(4px); padding: 1.6rem;">
    <div class="kds-modal-detalle">
        <div class="kds-modal-detalle__header">
            <div class="kds-modal-detalle__header-info">
                <span class="kds-modal-detalle__comanda-num" id="modal-detalle-num">#0</span>
                <span class="kds-modal-detalle__tipo-badge" id="modal-detalle-tipo">Delivery</span>
                <span class="badge" id="modal-detalle-estado">En Preparación</span>
            </div>
            <button type="button" onclick="cerrarModalDetalleComanda()" class="kds-modal-detalle__btn-cerrar" title="Cerrar modal (Esc)">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="kds-modal-detalle__cuerpo">
            <!-- Datos del cliente / entrega -->
            <div class="kds-modal-detalle__cliente-card">
                <div class="kds-modal-detalle__meta-bloque">
                    <div><i class="fa-solid fa-user" style="color: #64748b;"></i> Cliente: <strong id="modal-detalle-cliente">-</strong></div>
                    <div id="modal-detalle-tel-fila" style="margin-top: 0.4rem;"><i class="fa-solid fa-phone" style="color: #64748b;"></i> Teléfono: <strong id="modal-detalle-telefono">-</strong></div>
                </div>
                <div class="kds-modal-detalle__meta-bloque">
                    <div><i class="fa-regular fa-clock" style="color: #64748b;"></i> Ingreso: <strong id="modal-detalle-hora">-</strong> (<span id="modal-detalle-minutos">0 min</span>)</div>
                    <div id="modal-detalle-dir-fila" style="margin-top: 0.4rem; display: none;"><i class="fa-solid fa-location-dot" style="color: #ef4444;"></i> Dirección: <strong id="modal-detalle-direccion">-</strong></div>
                </div>
            </div>

            <!-- Checklist interactivo de platos -->
            <div class="kds-modal-detalle__seccion-titulo">
                <span><i class="fa-solid fa-utensils"></i> Ítems de la Comanda</span>
                <span class="kds-modal-detalle__checklist-instruccion">
                    <i class="fa-solid fa-hand-pointer"></i> Toca cada plato al completar su cocción
                </span>
            </div>

            <div class="kds-modal-detalle__checklist" id="modal-detalle-checklist">
                <!-- Ítems inyectados dinámicamente -->
            </div>
        </div>

        <div class="kds-modal-detalle__footer">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <span style="font-size: 1.3rem; color: #64748b;">Progreso de la comanda:</span>
                <strong id="modal-detalle-progreso" style="font-size: 1.4rem; color: #0f172a;">0 / 0 platos</strong>
            </div>

            <div style="display: flex; gap: 1rem; align-items: center;">
                <a id="modal-detalle-btn-ticket" href="#" target="_blank" class="boton boton--secundario boton--pequeno" style="display: none;">
                    <i class="fa-solid fa-print"></i>
                    <span>Ticket Térmico</span>
                </a>
                <button type="button" onclick="cerrarModalDetalleComanda()" class="boton boton--primario">
                    <i class="fa-solid fa-check"></i>
                    <span>Cerrar Detalle</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL ASIGNAR REPARTIDOR -->
<div id="modal-repartidor" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="admin-tarjeta" style="width: 100%; max-width: 45rem; margin: 1.6rem; background: #fff; border-radius: 0.8rem;">
        <div class="admin-tarjeta__encabezado" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.6rem;">
            <h3 class="admin-tarjeta__titulo" style="margin: 0;">
                <i class="fa-solid fa-motorcycle"></i>
                <span id="modal-repartidor-titulo">Asignar Repartidor</span>
            </h3>
            <button type="button" onclick="cerrarModalRepartidor()" style="background: none; border: none; font-size: 1.8rem; cursor: pointer;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="/cocina/asignar-repartidor">
            <input type="hidden" name="pedido_id" id="modal-repartidor-pedido-id" value="">

            <div class="formulario__campo u-separador-abajo">
                <label for="repartidor_id" class="formulario__label">Seleccionar Repartidor Disponible:</label>
                <select name="repartidor_id" id="repartidor_id" class="formulario__input" required>
                    <option value="">-- Elegir Repartidor --</option>
                    <?php foreach($repartidores as $rep): ?>
                        <option value="<?php echo (int)$rep->id; ?>"><?php echo s($rep->nombre); ?> (<?php echo s($rep->telefono ?? 'Sin tel'); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                <button type="button" onclick="cerrarModalRepartidor()" class="boton boton--secundario">Cancelar</button>
                <button type="submit" class="boton boton--primario">
                    <i class="fa-solid fa-paper-plane"></i>
                    <span>Despachar Pedido</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL CANCELAR PEDIDO -->
<div id="modal-cancelar" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="admin-tarjeta" style="width: 100%; max-width: 45rem; margin: 1.6rem; background: #fff; border-radius: 0.8rem;">
        <div class="admin-tarjeta__encabezado" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.6rem;">
            <h3 class="admin-tarjeta__titulo" style="margin: 0; color: #ef4444;">
                <i class="fa-solid fa-ban"></i>
                <span id="modal-cancelar-titulo">Cancelar Comanda</span>
            </h3>
            <button type="button" onclick="cerrarModalCancelar()" style="background: none; border: none; font-size: 1.8rem; cursor: pointer;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="/cocina/cancelar-pedido">
            <input type="hidden" name="pedido_id" id="modal-cancelar-pedido-id" value="">

            <p style="font-size: 1.3rem; color: #64748b; margin-bottom: 1.2rem;">
                Al cancelar, los insumos reservados según la receta serán restituidos de forma automática al stock del comercio.
            </p>

            <div class="formulario__campo u-separador-abajo">
                <label for="motivo_cancelacion" class="formulario__label">Motivo de la cancelación:</label>
                <input type="text" name="motivo" id="motivo_cancelacion" class="formulario__input" placeholder="Ej. Cliente solicitó anulación telefónica" required>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                <button type="button" onclick="cerrarModalCancelar()" class="boton boton--secundario">Volver</button>
                <button type="submit" class="boton boton--secundario" style="color: #ef4444; border-color: #fca5a5;">
                    <i class="fa-solid fa-trash-can"></i>
                    <span>Confirmar Cancelación</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL NUEVO PEDIDO MOSTRADOR (POS PRESENCIAL) -->
<div id="modal-pedido-presencial" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center; padding: 1.6rem;">
    <div class="pos-modal">
        <div class="admin-tarjeta__encabezado" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.6rem; padding-bottom: 1rem; border-bottom: 1px solid #e2e8f0;">
            <h3 class="admin-tarjeta__titulo" style="margin: 0; color: #059669;">
                <i class="fa-solid fa-cash-register"></i>
                <span>Nuevo Pedido de Mostrador (Presencial)</span>
            </h3>
            <button type="button" onclick="cerrarModalPedidoPresencial()" style="background: none; border: none; font-size: 2rem; cursor: pointer; color: #64748b;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="pos-form" method="POST" action="/cocina/pedido-presencial">
            <input type="hidden" name="items_json" id="pos-items-json" value="[]">

            <div class="pos-modal__grid">
                
                <!-- COLUMNA IZQUIERDA: CATÁLOGO DE PRODUCTOS -->
                <div class="pos-modal__catalogo">
                    <div style="position: relative;">
                        <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 1.2rem; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                        <input type="text" id="pos-buscar" class="formulario__input" placeholder="Buscar por nombre..." style="padding-left: 3.6rem;" oninput="posFiltrarProductos()">
                    </div>

                    <!-- Pestañas de categorías -->
                    <div class="pos-modal__filtros">
                        <button type="button" class="pos-modal__filtro-btn pos-modal__filtro-btn--activo" data-cat="0" onclick="posFiltrarCategoria(0, this)">Todos</button>
                        <?php foreach($categorias ?? [] as $cat): ?>
                            <button type="button" class="pos-modal__filtro-btn" data-cat="<?php echo (int)$cat->id; ?>" onclick="posFiltrarCategoria(<?php echo (int)$cat->id; ?>, this)">
                                <?php echo s($cat->nombre); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <!-- Grilla de productos -->
                    <div class="pos-modal__productos" id="pos-grid-productos">
                        <?php foreach($productos ?? [] as $prod): ?>
                            <?php 
                                $opcionesData = array_map(function($opc) {
                                    return [
                                        'id' => (int)$opc->id,
                                        'nombre' => $opc->nombre,
                                        'precio_adicional' => (float)$opc->precio_adicional
                                    ];
                                }, $prod->opciones ?? []);
                                $jsonOpciones = htmlspecialchars(json_encode($opcionesData), ENT_QUOTES, 'UTF-8');
                                $tieneOpciones = !empty($opcionesData);
                                $permiteAderezos = !empty($prod->permite_aderezos);
                            ?>
                            <div class="pos-modal__producto-card" 
                                 data-id="<?php echo (int)$prod->id; ?>" 
                                 data-cat="<?php echo (int)$prod->categoria_id; ?>"
                                 data-nombre="<?php echo strtolower(s($prod->nombre)); ?>"
                                 data-precio="<?php echo (float)$prod->precio; ?>"
                                 data-opciones='<?php echo $jsonOpciones; ?>'
                                 data-permite-aderezos="<?php echo $permiteAderezos ? '1' : '0'; ?>"
                                 onclick="posClickProducto(<?php echo (int)$prod->id; ?>, '<?php echo addslashes(s($prod->nombre)); ?>', <?php echo (float)$prod->precio; ?>, this)">
                                <div class="pos-modal__producto-card-nombre"><?php echo s($prod->nombre); ?></div>
                                <div class="pos-modal__producto-card-categoria"><?php echo s($prod->categoria_nombre ?? ''); ?></div>
                                <div class="pos-modal__producto-card-badge-wrap" style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
                                    <?php if($tieneOpciones): ?>
                                        <span class="pos-modal__producto-badge-opciones" title="Tiene opciones o adicionales configurables">
                                            <i class="fa-solid fa-layer-group"></i> Opciones
                                        </span>
                                    <?php endif; ?>
                                    <?php if($permiteAderezos): ?>
                                        <span class="pos-modal__producto-badge-opciones" style="background-color: #fef3c7; color: #b45309; border-color: #fde68a;" title="Incluye aderezos y salsas sin cargo">
                                            <i class="fa-solid fa-bottle-droplet"></i> Salsas
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="pos-modal__producto-card-precio">$ <?php echo number_format((float)$prod->precio, 2, ',', '.'); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- COLUMNA DERECHA: TICKET Y COBRO -->
                <div class="pos-modal__comanda">
                    <div class="formulario__campo" style="margin-bottom: 0;">
                        <label for="pos-nombre-cliente" class="formulario__label" style="font-weight: 700; color: #1e293b;">
                            <i class="fa-solid fa-user"></i> Nombre del Cliente / Identificador: *
                        </label>
                        <input type="text" name="nombre_contacto" id="pos-nombre-cliente" class="formulario__input" placeholder="Ej. Juan, Mesa 3..." required autocomplete="off">
                    </div>

                    <!-- Items seleccionados -->
                    <div>
                        <div style="font-size: 1.2rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 0.6rem;">
                            Comanda Actual:
                        </div>
                        <div class="pos-modal__items-contenedor" id="pos-items-lista">
                            <p style="text-align: center; color: #94a3b8; padding: 3rem 1rem; font-size: 1.3rem; margin: 0;">
                                <i class="fa-solid fa-basket-shopping" style="font-size: 2.4rem; display: block; margin-bottom: 0.8rem; opacity: 0.5;"></i>
                                Haz clic en los productos para agregarlos a la comanda.
                            </p>
                        </div>
                    </div>

                    <!-- Caja Total -->
                    <div class="pos-modal__total-caja">
                        <span class="pos-modal__total-label">Total a Pagar:</span>
                        <span class="pos-modal__total-monto" id="pos-total-texto">$ 0,00</span>
                    </div>

                    <!-- Modalidad de Cobro -->
                    <div class="pos-modal__pago-opciones">
                        <div style="font-size: 1.2rem; font-weight: 700; color: #64748b; text-transform: uppercase;">
                            Modalidad de Cobro:
                        </div>

                        <label style="display: flex; align-items: center; gap: 0.8rem; cursor: pointer; font-size: 1.3rem; font-weight: 600; color: #1e293b;">
                            <input type="radio" name="accion_cobro" value="cobrar_ahora" checked onchange="posCambiarModoCobro('cobrar_ahora')">
                            <span><i class="fa-solid fa-money-bill-wave" style="color: #16a34a;"></i> Cobrar en el Acto (Efectivo en Caja)</span>
                        </label>

                        <div id="pos-calculo-vuelto" style="display: flex; flex-direction: column; gap: 0.6rem; padding-left: 2.4rem;">
                            <div style="display: flex; align-items: center; gap: 0.8rem;">
                                <label for="pos-paga-con" style="font-size: 1.2rem; color: #475569;">Paga con ($):</label>
                                <input type="number" step="0.01" min="0" id="pos-paga-con" class="formulario__input" placeholder="Ej. 10000" style="padding: 0.4rem 0.8rem; font-size: 1.3rem; max-width: 14rem;" oninput="posCalcularVuelto()">
                            </div>
                            <div class="pos-modal__vuelto-caja" id="pos-vuelto-contenedor" style="display: none;">
                                <span>Vuelto a entregar:</span>
                                <strong id="pos-vuelto-texto" style="font-size: 1.6rem; color: #059669;">$ 0,00</strong>
                            </div>
                        </div>

                        <label style="display: flex; align-items: center; gap: 0.8rem; cursor: pointer; font-size: 1.3rem; font-weight: 600; color: #1e293b;">
                            <input type="radio" name="accion_cobro" value="cobrar_entrega" onchange="posCambiarModoCobro('cobrar_entrega')">
                            <span><i class="fa-solid fa-clock" style="color: #d97706;"></i> Cobrar al Entregar en Mostrador</span>
                        </label>
                    </div>

                    <!-- Botones de Acción -->
                    <div style="display: flex; gap: 1rem; margin-top: 0.6rem;">
                        <button type="button" onclick="cerrarModalPedidoPresencial()" class="boton boton--secundario" style="flex: 1;">
                            Cancelar
                        </button>
                        <button type="submit" id="pos-btn-submit" class="boton boton--primario" style="flex: 2; background-color: #059669; border-color: #059669;" disabled>
                            <i class="fa-solid fa-circle-check"></i>
                            <span>Confirmar e Ingresar</span>
                        </button>
                    </div>

                </div>

            </div>
        </form>

        <!-- SUBMODAL OPCIONES Y ADICIONALES DEL PRODUCTO -->
        <div id="modal-pos-opciones" class="pos-submodal-overlay" style="display: none;">
            <div class="pos-submodal">
                <div class="pos-submodal__header">
                    <div>
                        <h4 id="pos-submodal-titulo" class="pos-submodal__titulo">Personalizar Producto</h4>
                        <div id="pos-submodal-precio-base" class="pos-submodal__precio-base">Precio base: $ 0,00</div>
                    </div>
                    <button type="button" onclick="posCerrarSubmodalOpciones()" class="pos-submodal__btn-cerrar" title="Cerrar">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="pos-submodal__cuerpo">
                    <div id="pos-submodal-grupo-opciones">
                        <div style="font-size: 1.2rem; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 0.6rem;">
                            <i class="fa-solid fa-sliders"></i> Opciones y Adicionales:
                        </div>
                        <div id="pos-submodal-lista-opciones" class="pos-submodal__opciones-lista">
                            <!-- Opciones inyectadas dinámicamente -->
                        </div>
                    </div>

                    <div id="pos-submodal-grupo-aderezos" style="margin-top: 1.2rem; display: none;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.6rem;">
                            <div style="font-size: 1.2rem; font-weight: 700; color: #475569; text-transform: uppercase;">
                                <i class="fa-solid fa-bottle-droplet" style="color: #f59e0b;"></i> Salsas & Aderezos (Gratis):
                            </div>
                            <span style="font-size: 1.1rem; color: #059669; font-weight: 600; background: #ecfdf5; padding: 0.2rem 0.6rem; border-radius: 9999px;">Incluidos</span>
                        </div>
                        <div id="pos-submodal-lista-aderezos" class="pos-submodal__aderezos-grid">
                            <!-- Checkboxes de aderezos inyectados dinámicamente -->
                        </div>
                    </div>

                    <div class="formulario__campo" style="margin-top: 1.2rem; margin-bottom: 0;">
                        <label for="pos-submodal-notas" class="formulario__label" style="font-weight: 600; font-size: 1.2rem;">
                            <i class="fa-solid fa-pen"></i> Aclaraciones para cocina (Opcional):
                        </label>
                        <input type="text" id="pos-submodal-notas" class="formulario__input" placeholder="Ej: Sin orégano, bien cocido, cortar en 8...">
                    </div>
                </div>

                <div class="pos-submodal__footer">
                    <div class="pos-submodal__cantidad">
                        <button type="button" class="pos-modal__btn-cant" onclick="posCambiarCantidadSubmodal(-1)">-</button>
                        <span id="pos-submodal-cant" style="font-size: 1.5rem; font-weight: 800; min-width: 2.2rem; text-align: center;">1</span>
                        <button type="button" class="pos-modal__btn-cant" onclick="posCambiarCantidadSubmodal(1)">+</button>
                    </div>

                    <button type="button" onclick="posConfirmarSubmodalOpciones()" class="boton boton--primario" style="background-color: #059669; border-color: #059669; flex: 1;">
                        <i class="fa-solid fa-plus"></i>
                        <span id="pos-submodal-btn-texto">Agregar a la Comanda ($ 0,00)</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.posAderezos = <?php 
    $aderezosList = array_map(function($a) {
        return [
            'id' => (int)$a->id,
            'nombre' => $a->nombre
        ];
    }, $aderezos ?? []);
    echo json_encode($aderezosList);
?>;
let posItems = {};
let posCategoriaActual = 0;
let posSubmodalProductoActual = null;
let posSubmodalCantidad = 1;

function abrirModalPedidoPresencial() {
    posItems = {};
    posActualizarVistaItems();
    document.getElementById('pos-nombre-cliente').value = '';
    document.getElementById('pos-paga-con').value = '';
    document.getElementById('pos-vuelto-contenedor').style.display = 'none';
    document.getElementById('pos-buscar').value = '';
    posCerrarSubmodalOpciones();
    posFiltrarCategoria(0, document.querySelector('.pos-modal__filtro-btn[data-cat="0"]'));
    document.getElementById('modal-pedido-presencial').style.display = 'flex';
    document.getElementById('pos-nombre-cliente').focus();
}

function cerrarModalPedidoPresencial() {
    posCerrarSubmodalOpciones();
    document.getElementById('modal-pedido-presencial').style.display = 'none';
}

function posFiltrarCategoria(catId, btn) {
    posCategoriaActual = parseInt(catId);
    document.querySelectorAll('.pos-modal__filtro-btn').forEach(b => b.classList.remove('pos-modal__filtro-btn--activo'));
    if(btn) btn.classList.add('pos-modal__filtro-btn--activo');
    posFiltrarProductos();
}

function posFiltrarProductos() {
    const query = document.getElementById('pos-buscar').value.toLowerCase().trim();
    const cards = document.querySelectorAll('.pos-modal__producto-card');
    cards.forEach(card => {
        const cat = parseInt(card.dataset.cat);
        const nombre = card.dataset.nombre;
        const matchCat = (posCategoriaActual === 0 || cat === posCategoriaActual);
        const matchQuery = (!query || nombre.includes(query));
        card.style.display = (matchCat && matchQuery) ? 'flex' : 'none';
    });
}

function posClickProducto(id, nombre, precio, elementoCard) {
    const opcionesJsonStr = elementoCard.getAttribute('data-opciones') || '[]';
    const permiteAderezos = elementoCard.getAttribute('data-permite-aderezos') === '1';
    let opciones = [];
    try {
        opciones = JSON.parse(opcionesJsonStr);
    } catch(e) {
        opciones = [];
    }

    const tieneOpciones = opciones && opciones.length > 0;

    if(!tieneOpciones && !permiteAderezos) {
        // Producto sin opciones ni aderezos: 1-click directo
        posAgregarItem(id, nombre, precio, [], [], '', 1);
    } else {
        // Producto con opciones o aderezos: abrir submodal de configuración
        posAbrirSubmodalOpciones(id, nombre, precio, opciones, permiteAderezos);
    }
}

function posAbrirSubmodalOpciones(id, nombre, precio, opciones, permiteAderezos) {
    posSubmodalProductoActual = { id, nombre, precio, opciones, permiteAderezos };
    posSubmodalCantidad = 1;

    document.getElementById('pos-submodal-titulo').textContent = nombre;
    document.getElementById('pos-submodal-precio-base').textContent = 'Precio base: $ ' + precio.toLocaleString('es-AR', {minimumFractionDigits: 2});
    document.getElementById('pos-submodal-cant').textContent = '1';
    document.getElementById('pos-submodal-notas').value = '';

    // 1. Opciones y adicionales
    const grupoOpciones = document.getElementById('pos-submodal-grupo-opciones');
    const listaOpciones = document.getElementById('pos-submodal-lista-opciones');
    if(opciones && opciones.length > 0) {
        grupoOpciones.style.display = 'block';
        let htmlOpc = '';
        opciones.forEach(opc => {
            const extraTexto = opc.precio_adicional > 0 ? ` (+ $ ${opc.precio_adicional.toLocaleString('es-AR', {minimumFractionDigits: 2})})` : ' (Sin cargo extra)';
            htmlOpc += `
                <label class="pos-opcion-item">
                    <div style="display: flex; align-items: center; gap: 0.8rem;">
                        <input type="checkbox" class="pos-opcion-check" value="${opc.id}" data-nombre="${opc.nombre}" data-precio="${opc.precio_adicional}" onchange="posActualizarTotalSubmodal()">
                        <span class="pos-opcion-item__nombre">${opc.nombre}</span>
                    </div>
                    <span class="pos-opcion-item__precio">${extraTexto}</span>
                </label>
            `;
        });
        listaOpciones.innerHTML = htmlOpc;
    } else {
        grupoOpciones.style.display = 'none';
        listaOpciones.innerHTML = '';
    }

    // 2. Aderezos y salsas
    const grupoAderezos = document.getElementById('pos-submodal-grupo-aderezos');
    const listaAderezos = document.getElementById('pos-submodal-lista-aderezos');
    if(permiteAderezos && window.posAderezos && window.posAderezos.length > 0) {
        grupoAderezos.style.display = 'block';
        let htmlAde = '';
        window.posAderezos.forEach(ade => {
            htmlAde += `
                <label class="pos-aderezo-chip">
                    <input type="checkbox" class="pos-aderezo-check" value="${ade.nombre}">
                    <span>${ade.nombre}</span>
                </label>
            `;
        });
        listaAderezos.innerHTML = htmlAde;
    } else {
        grupoAderezos.style.display = 'none';
        listaAderezos.innerHTML = '';
    }

    posActualizarTotalSubmodal();
    document.getElementById('modal-pos-opciones').style.display = 'flex';
}

function posCerrarSubmodalOpciones() {
    document.getElementById('modal-pos-opciones').style.display = 'none';
    posSubmodalProductoActual = null;
}

function posCambiarCantidadSubmodal(delta) {
    posSubmodalCantidad = Math.max(1, posSubmodalCantidad + delta);
    document.getElementById('pos-submodal-cant').textContent = posSubmodalCantidad;
    posActualizarTotalSubmodal();
}

function posActualizarTotalSubmodal() {
    if(!posSubmodalProductoActual) return;
    let unitario = posSubmodalProductoActual.precio;
    const checks = document.querySelectorAll('.pos-opcion-check:checked');
    checks.forEach(chk => {
        unitario += parseFloat(chk.dataset.precio || 0);
    });
    const subtotal = unitario * posSubmodalCantidad;
    document.getElementById('pos-submodal-btn-texto').textContent = `Agregar a la Comanda ($ ${subtotal.toLocaleString('es-AR', {minimumFractionDigits: 2})})`;
}

function posConfirmarSubmodalOpciones() {
    if(!posSubmodalProductoActual) return;

    const opcionesSeleccionadas = [];
    const checksOpc = document.querySelectorAll('.pos-opcion-check:checked');
    checksOpc.forEach(chk => {
        opcionesSeleccionadas.push({
            id: parseInt(chk.value),
            nombre: chk.dataset.nombre,
            precio_adicional: parseFloat(chk.dataset.precio || 0)
        });
    });

    const aderezosSeleccionados = [];
    const checksAde = document.querySelectorAll('.pos-aderezo-check:checked');
    checksAde.forEach(chk => {
        aderezosSeleccionados.push(chk.value);
    });

    const notas = document.getElementById('pos-submodal-notas').value.trim();

    posAgregarItem(
        posSubmodalProductoActual.id,
        posSubmodalProductoActual.nombre,
        posSubmodalProductoActual.precio,
        opcionesSeleccionadas,
        aderezosSeleccionados,
        notas,
        posSubmodalCantidad
    );

    posCerrarSubmodalOpciones();
}

function posGenerarItemKey(id, opciones, aderezos, notas) {
    const opcIds = (opciones || []).map(o => o.id).sort((a,b) => a-b).join('-');
    const salsasKey = (aderezos || []).slice().sort().join('-').toLowerCase().replace(/[^a-z0-9]/g, '');
    const notaKey = (notas || '').toLowerCase().replace(/[^a-z0-9]/g, '');
    return `${id}_${opcIds}_${salsasKey}_${notaKey}`;
}

function posAgregarItem(id, nombre, precioBase, opciones, aderezos, notas, cantidad) {
    opciones = opciones || [];
    aderezos = aderezos || [];
    const key = posGenerarItemKey(id, opciones, aderezos, notas);

    let precioUnitario = parseFloat(precioBase);
    opciones.forEach(o => {
        precioUnitario += parseFloat(o.precio_adicional || 0);
    });

    if(posItems[key]) {
        posItems[key].cantidad += cantidad;
    } else {
        posItems[key] = {
            key: key,
            id: parseInt(id),
            nombre: nombre,
            precioBase: parseFloat(precioBase),
            opciones: opciones,
            opcionesIds: opciones.map(o => o.id),
            aderezos: aderezos,
            notas: notas || '',
            precioUnitario: precioUnitario,
            cantidad: cantidad
        };
    }

    posActualizarVistaItems();
}

function posCambiarCantidad(key, delta) {
    if(!posItems[key]) return;
    posItems[key].cantidad += delta;
    if(posItems[key].cantidad <= 0) {
        delete posItems[key];
    }
    posActualizarVistaItems();
}

function posEliminarItem(key) {
    if(posItems[key]) {
        delete posItems[key];
        posActualizarVistaItems();
    }
}

function posActualizarVistaItems() {
    const contenedor = document.getElementById('pos-items-lista');
    const totalTexto = document.getElementById('pos-total-texto');
    const btnSubmit = document.getElementById('pos-btn-submit');
    const itemsJson = document.getElementById('pos-items-json');

    const lista = Object.values(posItems);
    let total = 0;

    if(lista.length === 0) {
        contenedor.innerHTML = `
            <p style="text-align: center; color: #94a3b8; padding: 3rem 1rem; font-size: 1.3rem; margin: 0;">
                <i class="fa-solid fa-basket-shopping" style="font-size: 2.4rem; display: block; margin-bottom: 0.8rem; opacity: 0.5;"></i>
                Haz clic en los productos para agregarlos a la comanda.
            </p>
        `;
        totalTexto.textContent = '$ 0,00';
        btnSubmit.disabled = true;
        itemsJson.value = '[]';
        posCalcularVuelto();
        return;
    }

    let html = '';
    const arrayParaJson = [];

    lista.forEach(item => {
        const subtotal = item.precioUnitario * item.cantidad;
        total += subtotal;

        arrayParaJson.push({
            producto_id: item.id,
            cantidad: item.cantidad,
            opciones: item.opcionesIds,
            aderezos: item.aderezos || [],
            notas: item.notas
        });

        // Construir detalle legible de opciones, aderezos y notas
        let subtextoPartes = [];
        if(item.aderezos && item.aderezos.length > 0) {
            subtextoPartes.push(`<strong>Salsas:</strong> ${item.aderezos.join(', ')}`);
        }
        if(item.opciones && item.opciones.length > 0) {
            item.opciones.forEach(o => {
                subtextoPartes.push(`+ ${o.nombre} (+$ ${o.precio_adicional.toLocaleString('es-AR', {minimumFractionDigits: 2})})`);
            });
        }
        if(item.notas) {
            subtextoPartes.push(`<em>Nota: "${item.notas}"</em>`);
        }
        const subtextoHtml = subtextoPartes.length > 0 
            ? `<div class="pos-modal__item-subtexto">${subtextoPartes.join(' · ')}</div>` 
            : '';

        html += `
            <div class="pos-modal__item-fila">
                <div class="pos-modal__item-info">
                    <div class="pos-modal__item-nombre">${item.nombre}</div>
                    ${subtextoHtml}
                    <div class="pos-modal__item-precio">$ ${item.precioUnitario.toLocaleString('es-AR', {minimumFractionDigits: 2})} c/u · <strong>$ ${subtotal.toLocaleString('es-AR', {minimumFractionDigits: 2})}</strong></div>
                </div>
                <div class="pos-modal__item-controles">
                    <button type="button" class="pos-modal__btn-cant" onclick="posCambiarCantidad('${item.key}', -1)">-</button>
                    <span class="pos-modal__item-cant">${item.cantidad}</span>
                    <button type="button" class="pos-modal__btn-cant" onclick="posCambiarCantidad('${item.key}', 1)">+</button>
                    <button type="button" class="pos-modal__btn-eliminar" onclick="posEliminarItem('${item.key}')" title="Eliminar">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
            </div>
        `;
    });

    contenedor.innerHTML = html;
    totalTexto.textContent = '$ ' + total.toLocaleString('es-AR', {minimumFractionDigits: 2});
    itemsJson.value = JSON.stringify(arrayParaJson);
    btnSubmit.disabled = false;

    posCalcularVuelto();
}

function posCambiarModoCobro(modo) {
    const contenedorVuelto = document.getElementById('pos-calculo-vuelto');
    if(modo === 'cobrar_ahora') {
        contenedorVuelto.style.display = 'flex';
        posCalcularVuelto();
    } else {
        contenedorVuelto.style.display = 'none';
    }
}

function posCalcularVuelto() {
    const pagaConInput = document.getElementById('pos-paga-con');
    const contenedorVuelto = document.getElementById('pos-vuelto-contenedor');
    const textoVuelto = document.getElementById('pos-vuelto-texto');

    const pagaCon = parseFloat(pagaConInput.value) || 0;
    let total = 0;
    Object.values(posItems).forEach(i => total += (i.precioUnitario * i.cantidad));

    if(pagaCon > 0 && total > 0) {
        contenedorVuelto.style.display = 'flex';
        const vuelto = pagaCon - total;
        if(vuelto >= 0) {
            textoVuelto.textContent = '$ ' + vuelto.toLocaleString('es-AR', {minimumFractionDigits: 2});
            textoVuelto.style.color = '#059669';
        } else {
            textoVuelto.textContent = 'Faltan $ ' + Math.abs(vuelto).toLocaleString('es-AR', {minimumFractionDigits: 2});
            textoVuelto.style.color = '#dc2626';
        }
    } else {
        contenedorVuelto.style.display = 'none';
    }
}

function cambiarTabKDS(tab) {
    const vistaActivas = document.getElementById('kds-vista-activas');
    const vistaHistorial = document.getElementById('kds-vista-historial');
    const tabBtnActivas = document.getElementById('tab-btn-activas');
    const tabBtnHistorial = document.getElementById('tab-btn-historial');

    if(tab === 'activas') {
        vistaActivas.style.display = 'block';
        vistaHistorial.style.display = 'none';
        tabBtnActivas.classList.add('kds-tabs__tab--activo');
        tabBtnHistorial.classList.remove('kds-tabs__tab--activo');
    } else {
        vistaActivas.style.display = 'none';
        vistaHistorial.style.display = 'block';
        tabBtnActivas.classList.remove('kds-tabs__tab--activo');
        tabBtnHistorial.classList.add('kds-tabs__tab--activo');
    }
}

function abrirModalAsignarRepartidor(pedidoId) {
    document.getElementById('modal-repartidor-pedido-id').value = pedidoId;
    document.getElementById('modal-repartidor-titulo').textContent = 'Despachar Pedido #' + pedidoId;
    const modal = document.getElementById('modal-repartidor');
    modal.style.display = 'flex';
}

function cerrarModalRepartidor() {
    document.getElementById('modal-repartidor').style.display = 'none';
}

function abrirModalCancelar(pedidoId) {
    document.getElementById('modal-cancelar-pedido-id').value = pedidoId;
    document.getElementById('modal-cancelar-titulo').textContent = 'Cancelar Pedido #' + pedidoId;
    const modal = document.getElementById('modal-cancelar');
    modal.style.display = 'flex';
}

function cerrarModalCancelar() {
    document.getElementById('modal-cancelar').style.display = 'none';
}

/* ====================================================================
   LÓGICA DEL RESUMEN ALL-DAY Y MODAL DE ENFOQUE CHECKLIST
   ==================================================================== */
window.kdsComandasStore = <?php echo json_encode($comandasDataStore ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?> || {};

function toggleKdsAllDay() {
    const cuerpo = document.getElementById('kds-allday-cuerpo');
    const icon = document.getElementById('kds-allday-toggle-icon');
    if (!cuerpo) return;
    const isHidden = cuerpo.style.display === 'none';
    cuerpo.style.display = isHidden ? 'flex' : 'none';
    if (icon) {
        if (isHidden) {
            icon.classList.remove('kds-allday__toggle-icon--rotado');
        } else {
            icon.classList.add('kds-allday__toggle-icon--rotado');
        }
    }
    localStorage.setItem('kds_allday_colapsado', isHidden ? '0' : '1');
}

// Recuperar preferencia de All-Day colapsado al iniciar
document.addEventListener('DOMContentLoaded', () => {
    if (localStorage.getItem('kds_allday_colapsado') === '1') {
        const cuerpo = document.getElementById('kds-allday-cuerpo');
        const icon = document.getElementById('kds-allday-toggle-icon');
        if (cuerpo) cuerpo.style.display = 'none';
        if (icon) icon.classList.add('kds-allday__toggle-icon--rotado');
    }
});

function abrirModalDetalleComanda(pedidoId) {
    const data = window.kdsComandasStore[pedidoId];
    if (!data) return;

    document.getElementById('modal-detalle-num').textContent = '#' + data.id;

    // Badge Tipo (Delivery / Mostrador / Retiro)
    const tipoBadge = document.getElementById('modal-detalle-tipo');
    tipoBadge.textContent = data.tipo_texto;
    tipoBadge.className = 'kds-modal-detalle__tipo-badge comanda-card__tipo-badge--' + data.tipo;

    // Badge Estado
    const estadoBadge = document.getElementById('modal-detalle-estado');
    estadoBadge.textContent = data.estado_texto;
    estadoBadge.className = 'badge ' + (data.estado === 'en_preparacion' ? 'badge--info' : (data.estado === 'en_camino' ? 'badge--exito' : 'badge--alerta'));

    // Datos de Cliente y Tiempos
    document.getElementById('modal-detalle-cliente').textContent = data.cliente || 'Consumidor Final';
    document.getElementById('modal-detalle-telefono').textContent = data.telefono || '-';
    document.getElementById('modal-detalle-hora').textContent = data.hora + ' hs';
    document.getElementById('modal-detalle-minutos').textContent = data.minutos + ' min transcurridos';

    const dirFila = document.getElementById('modal-detalle-dir-fila');
    if (data.tipo === 'delivery' && data.direccion) {
        dirFila.style.display = 'block';
        document.getElementById('modal-detalle-direccion').textContent = data.direccion;
    } else {
        dirFila.style.display = 'none';
    }

    // Botón Ticket Térmico
    const btnTicket = document.getElementById('modal-detalle-btn-ticket');
    if (data.comprobante_id) {
        btnTicket.href = '/comprobante?id=' + data.comprobante_id;
        btnTicket.style.display = 'inline-flex';
    } else {
        btnTicket.style.display = 'none';
    }

    // Renderizar Checklist interactivo
    const checklistCont = document.getElementById('modal-detalle-checklist');
    const checkedKey = 'kds_checklist_' + data.id;
    let checkedItems = {};
    try {
        checkedItems = JSON.parse(sessionStorage.getItem(checkedKey) || '{}');
    } catch (e) {
        checkedItems = {};
    }

    let itemsHtml = '';
    (data.items || []).forEach((item, idx) => {
        const isChecked = !!checkedItems[idx];
        const checkedClass = isChecked ? ' kds-checklist-item--completado' : '';
        const checkIcon = isChecked ? '<i class="fa-solid fa-check"></i>' : '';

        itemsHtml += `
            <div class="kds-checklist-item${checkedClass}" data-item-idx="${idx}" onclick="toggleChecklistItem(this, ${data.id}, ${idx})">
                <div class="kds-checklist-box">${checkIcon}</div>
                <span class="kds-checklist-item__cant">${item.cantidad}x</span>
                <div class="kds-checklist-item__info">
                    <div class="kds-checklist-item__nombre">${escapeHtmlKds(item.nombre)}</div>
                    ${item.badges_html || ''}
                </div>
            </div>
        `;
    });

    checklistCont.innerHTML = itemsHtml;
    actualizarProgresoChecklist(data.id, (data.items || []).length);

    document.getElementById('modal-comanda-detalle').style.display = 'flex';
}

function toggleChecklistItem(el, pedidoId, idx) {
    const checkedKey = 'kds_checklist_' + pedidoId;
    let checkedItems = {};
    try {
        checkedItems = JSON.parse(sessionStorage.getItem(checkedKey) || '{}');
    } catch (e) {
        checkedItems = {};
    }

    const isChecked = el.classList.toggle('kds-checklist-item--completado');
    const box = el.querySelector('.kds-checklist-box');
    if (isChecked) {
        checkedItems[idx] = true;
        if (box) box.innerHTML = '<i class="fa-solid fa-check"></i>';
    } else {
        delete checkedItems[idx];
        if (box) box.innerHTML = '';
    }

    sessionStorage.setItem(checkedKey, JSON.stringify(checkedItems));

    const data = window.kdsComandasStore[pedidoId];
    if (data && data.items) {
        actualizarProgresoChecklist(pedidoId, data.items.length);
    }
}

function actualizarProgresoChecklist(pedidoId, totalItems) {
    const checkedKey = 'kds_checklist_' + pedidoId;
    let checkedItems = {};
    try {
        checkedItems = JSON.parse(sessionStorage.getItem(checkedKey) || '{}');
    } catch (e) {
        checkedItems = {};
    }
    const completados = Object.keys(checkedItems).length;
    const progEl = document.getElementById('modal-detalle-progreso');
    if (progEl) {
        progEl.textContent = `${completados} / ${totalItems} platos listos`;
        if (completados === totalItems && totalItems > 0) {
            progEl.style.color = '#16a34a';
        } else {
            progEl.style.color = '#0f172a';
        }
    }
}

function cerrarModalDetalleComanda() {
    const modal = document.getElementById('modal-comanda-detalle');
    if (modal) modal.style.display = 'none';
}

function escapeHtmlKds(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        cerrarModalDetalleComanda();
    }
});
</script>
