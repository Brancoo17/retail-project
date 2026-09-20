<div class="contenedor seccion">

    <!-- ENCABEZADO Y ACCIONES -->
    <div class="reportes__header u-separador-abajo">
        <div>
            <span class="badge badge--admin" style="font-size: 1.15rem;">Analítica & Finanzas</span>
            <h1 class="seccion__titulo" style="margin-top: 0.4rem;">Reportes & Métricas de Negocio</h1>
            <p class="seccion__subtitulo">
                Período actual: <strong><?php echo s($nombrePeriodo); ?></strong>
            </p>
        </div>

        <div class="reportes__acciones no-print">
            <button type="button" class="boton boton--secundario" onclick="window.print();" title="Imprimir informe en papel o guardar como PDF">
                <i class="fa-solid fa-print"></i>
                <span>Imprimir / PDF</span>
            </button>
        </div>
    </div>

    <!-- SELECTOR DE FILTROS DE PERÍODO -->
    <div class="reportes-filtros no-print u-separador-abajo">
        <div class="reportes-filtros__botones">
            <a href="/admin/reportes?periodo=hoy" class="reportes-filtros__btn <?php echo $periodo === 'hoy' ? 'reportes-filtros__btn--activo' : ''; ?>">
                <i class="fa-solid fa-calendar-day"></i> Hoy
            </a>
            <a href="/admin/reportes?periodo=7dias" class="reportes-filtros__btn <?php echo $periodo === '7dias' ? 'reportes-filtros__btn--activo' : ''; ?>">
                <i class="fa-solid fa-calendar-week"></i> Últimos 7 días
            </a>
            <a href="/admin/reportes?periodo=mes_actual" class="reportes-filtros__btn <?php echo $periodo === 'mes_actual' ? 'reportes-filtros__btn--activo' : ''; ?>">
                <i class="fa-solid fa-calendar-check"></i> Este Mes
            </a>
            <a href="/admin/reportes?periodo=mes_anterior" class="reportes-filtros__btn <?php echo $periodo === 'mes_anterior' ? 'reportes-filtros__btn--activo' : ''; ?>">
                <i class="fa-solid fa-calendar-minus"></i> Mes Anterior
            </a>
        </div>

        <form action="/admin/reportes" method="GET" class="reportes-filtros__form">
            <input type="hidden" name="periodo" value="personalizado">
            <div class="reportes-filtros__campo">
                <label for="fecha_inicio">Desde:</label>
                <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?php echo s($fechaInicio ?: date('Y-m-d', strtotime($inicio))); ?>" required>
            </div>
            <div class="reportes-filtros__campo">
                <label for="fecha_fin">Hasta:</label>
                <input type="date" id="fecha_fin" name="fecha_fin" value="<?php echo s($fechaFin ?: date('Y-m-d', strtotime($fin))); ?>" required>
            </div>
            <button type="submit" class="boton boton--primario boton--pequeno">
                <i class="fa-solid fa-filter"></i> Filtrar
            </button>
        </form>
    </div>

    <!-- 1. TARJETAS DE KPIS PRINCIPALES -->
    <div class="grid grid--4 u-separador-abajo">
        <!-- KPI 1: Facturación -->
        <div class="kpi-card">
            <div class="kpi-card__icono kpi-card__icono--verde">
                <i class="fa-solid fa-sack-dollar"></i>
            </div>
            <div class="kpi-card__datos">
                <span class="kpi-card__etiqueta">Facturación Neta</span>
                <span class="kpi-card__valor">$ <?php echo number_format($metricasVentas['total_facturado'], 2, ',', '.'); ?></span>
                <span class="kpi-card__subtexto">
                    <i class="fa-solid fa-circle-check" style="color: #10b981;"></i> Ventas confirmadas
                </span>
            </div>
        </div>

        <!-- KPI 2: Pedidos -->
        <div class="kpi-card">
            <div class="kpi-card__icono kpi-card__icono--naranja">
                <i class="fa-solid fa-receipt"></i>
            </div>
            <div class="kpi-card__datos">
                <span class="kpi-card__etiqueta">Pedidos Totales</span>
                <span class="kpi-card__valor"><?php echo (int)$metricasVentas['total_pedidos']; ?></span>
                <span class="kpi-card__subtexto">
                    Efectividad: <strong><?php echo $metricasVentas['tasa_efectividad']; ?>%</strong> (<?php echo $metricasVentas['total_cancelados']; ?> cancelados)
                </span>
            </div>
        </div>

        <!-- KPI 3: Ticket Promedio -->
        <div class="kpi-card">
            <div class="kpi-card__icono kpi-card__icono--azul">
                <i class="fa-solid fa-chart-pie"></i>
            </div>
            <div class="kpi-card__datos">
                <span class="kpi-card__etiqueta">Ticket Promedio</span>
                <span class="kpi-card__valor">$ <?php echo number_format($metricasVentas['ticket_promedio'], 2, ',', '.'); ?></span>
                <span class="kpi-card__subtexto">
                    Promedio por comanda
                </span>
            </div>
        </div>

        <!-- KPI 4: Envíos Delivery -->
        <div class="kpi-card">
            <div class="kpi-card__icono kpi-card__icono--purpura">
                <i class="fa-solid fa-motorcycle"></i>
            </div>
            <div class="kpi-card__datos">
                <span class="kpi-card__etiqueta">Fletes & Envíos</span>
                <span class="kpi-card__valor">$ <?php echo number_format($metricasVentas['total_envios'], 2, ',', '.'); ?></span>
                <span class="kpi-card__subtexto">
                    Costo recaudado por delivery
                </span>
            </div>
        </div>
    </div>

    <!-- 2. EVOLUCIÓN DIARIA DE VENTAS (GRÁFICO DE BARRAS CSS) -->
    <div class="card card--grafico u-separador-abajo">
        <div class="card__cabecera">
            <div class="card__titulo-bloque">
                <i class="fa-solid fa-chart-column" style="color: #ea580c;"></i>
                <h3 class="card__titulo">Evolución Diaria de Ventas</h3>
            </div>
            <span style="font-size: 1.3rem; color: #64748b;">
                <?php echo count($ventasPorDia); ?> día(s) con actividad en el período
            </span>
        </div>

        <div class="card__cuerpo">
            <?php if(empty($ventasPorDia)): ?>
                <p class="alerta alerta--info" style="margin: 2rem 0;">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>No se registraron ventas en el período seleccionado.</span>
                </p>
            <?php else: ?>
                <div class="grafico-barras">
                    <div class="grafico-barras__cuadricula">
                        <?php foreach($ventasPorDia as $vd): 
                            $porcentajeAltura = ($maxVentaDia > 0) ? round(($vd['total'] / $maxVentaDia) * 100, 1) : 0;
                            $porcentajeAltura = max(6, $porcentajeAltura); // Mínimo visual
                            $diaTexto = date('d/m', strtotime($vd['fecha']));
                        ?>
                            <div class="grafico-barras__columna">
                                <div class="grafico-barras__barra-contenedor" title="<?php echo $diaTexto; ?>: $ <?php echo number_format($vd['total'], 2, ',', '.'); ?> (<?php echo $vd['pedidos']; ?> pedidos)">
                                    <span class="grafico-barras__tooltip">
                                        <strong><?php echo $diaTexto; ?></strong><br>
                                        $ <?php echo number_format($vd['total'], 0, ',', '.'); ?><br>
                                        <?php echo $vd['pedidos']; ?> pedido(s)
                                    </span>
                                    <div class="grafico-barras__barra" style="height: <?php echo $porcentajeAltura; ?>%;">
                                        <span class="grafico-barras__monto">$ <?php echo number_format($vd['total'], 0, ',', '.'); ?></span>
                                    </div>
                                </div>
                                <span class="grafico-barras__etiqueta"><?php echo $diaTexto; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 3. DESGLOSE DE CANALES Y MEDIOS DE PAGO -->
    <div class="grid grid--2 u-separador-abajo">
        <!-- Métodos de Pago -->
        <div class="card">
            <div class="card__cabecera">
                <div class="card__titulo-bloque">
                    <i class="fa-solid fa-credit-card" style="color: #0284c7;"></i>
                    <h3 class="card__titulo">Métodos de Cobro</h3>
                </div>
            </div>
            <div class="card__cuerpo">
                <?php 
                    $totalFacturado = max(0.01, $metricasVentas['total_facturado']);
                    $pagoEfectivo = $desgloseMetodos['efectivo'] ?? ['pedidos' => 0, 'total' => 0.0];
                    $pagoMP = $desgloseMetodos['mercadopago'] ?? ['pedidos' => 0, 'total' => 0.0];
                    $porcEfectivo = round(($pagoEfectivo['total'] / $totalFacturado) * 100, 1);
                    $porcMP = round(($pagoMP['total'] / $totalFacturado) * 100, 1);
                ?>
                <div class="canal-item">
                    <div class="canal-item__cabecera">
                        <div class="canal-item__nombre">
                            <i class="fa-solid fa-money-bill-wave" style="color: #10b981;"></i>
                            <span>Efectivo (Mostrador / Repartidor)</span>
                        </div>
                        <div class="canal-item__monto">
                            <strong>$ <?php echo number_format($pagoEfectivo['total'], 2, ',', '.'); ?></strong>
                            <span class="canal-item__porc">(<?php echo $porcEfectivo; ?>%)</span>
                        </div>
                    </div>
                    <div class="barra-progreso">
                        <div class="barra-progreso__relleno barra-progreso__relleno--verde" style="width: <?php echo $porcEfectivo; ?>%;"></div>
                    </div>
                    <span class="canal-item__pedidos"><?php echo $pagoEfectivo['pedidos']; ?> pedido(s)</span>
                </div>

                <div class="canal-item" style="margin-top: 1.6rem;">
                    <div class="canal-item__cabecera">
                        <div class="canal-item__nombre">
                            <i class="fa-solid fa-bolt" style="color: #0284c7;"></i>
                            <span>Mercado Pago (Online)</span>
                        </div>
                        <div class="canal-item__monto">
                            <strong>$ <?php echo number_format($pagoMP['total'], 2, ',', '.'); ?></strong>
                            <span class="canal-item__porc">(<?php echo $porcMP; ?>%)</span>
                        </div>
                    </div>
                    <div class="barra-progreso">
                        <div class="barra-progreso__relleno barra-progreso__relleno--azul" style="width: <?php echo $porcMP; ?>%;"></div>
                    </div>
                    <span class="canal-item__pedidos"><?php echo $pagoMP['pedidos']; ?> pedido(s)</span>
                </div>
            </div>
        </div>

        <!-- Modalidades de Entrega -->
        <div class="card">
            <div class="card__cabecera">
                <div class="card__titulo-bloque">
                    <i class="fa-solid fa-truck-ramp-box" style="color: #ea580c;"></i>
                    <h3 class="card__titulo">Modalidad de Entrega</h3>
                </div>
            </div>
            <div class="card__cuerpo">
                <?php 
                    $tipoDelivery = $desgloseTipos['delivery'] ?? ['pedidos' => 0, 'total' => 0.0];
                    $tipoRetiro = $desgloseTipos['retiro'] ?? ['pedidos' => 0, 'total' => 0.0];
                    $porcDelivery = round(($tipoDelivery['total'] / $totalFacturado) * 100, 1);
                    $porcRetiro = round(($tipoRetiro['total'] / $totalFacturado) * 100, 1);
                ?>
                <div class="canal-item">
                    <div class="canal-item__cabecera">
                        <div class="canal-item__nombre">
                            <i class="fa-solid fa-motorcycle" style="color: #ea580c;"></i>
                            <span>Delivery a Domicilio</span>
                        </div>
                        <div class="canal-item__monto">
                            <strong>$ <?php echo number_format($tipoDelivery['total'], 2, ',', '.'); ?></strong>
                            <span class="canal-item__porc">(<?php echo $porcDelivery; ?>%)</span>
                        </div>
                    </div>
                    <div class="barra-progreso">
                        <div class="barra-progreso__relleno barra-progreso__relleno--naranja" style="width: <?php echo $porcDelivery; ?>%;"></div>
                    </div>
                    <span class="canal-item__pedidos"><?php echo $tipoDelivery['pedidos']; ?> pedido(s)</span>
                </div>

                <div class="canal-item" style="margin-top: 1.6rem;">
                    <div class="canal-item__cabecera">
                        <div class="canal-item__nombre">
                            <i class="fa-solid fa-store" style="color: #9333ea;"></i>
                            <span>Retiro en Local (Take Away)</span>
                        </div>
                        <div class="canal-item__monto">
                            <strong>$ <?php echo number_format($tipoRetiro['total'], 2, ',', '.'); ?></strong>
                            <span class="canal-item__porc">(<?php echo $porcRetiro; ?>%)</span>
                        </div>
                    </div>
                    <div class="barra-progreso">
                        <div class="barra-progreso__relleno barra-progreso__relleno--purpura" style="width: <?php echo $porcRetiro; ?>%;"></div>
                    </div>
                    <span class="canal-item__pedidos"><?php echo $tipoRetiro['pedidos']; ?> pedido(s)</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. RANKING DE PLATOS MÁS VENDIDOS -->
    <div class="card u-separador-abajo">
        <div class="card__cabecera">
            <div class="card__titulo-bloque">
                <i class="fa-solid fa-trophy" style="color: #eab308;"></i>
                <h3 class="card__titulo">Top 10 Platos & Productos Más Vendidos</h3>
            </div>
        </div>
        <div class="card__cuerpo card__cuerpo--tabla">
            <?php if(empty($rankingProductos)): ?>
                <p style="padding: 2.4rem; color: #64748b; text-align: center;">No hay registro de productos vendidos en este período.</p>
            <?php else: ?>
                <table class="tabla-reporte">
                    <thead>
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 35%;">Producto</th>
                            <th style="width: 20%;">Categoría</th>
                            <th style="width: 15%; text-align: right;">Unidades</th>
                            <th style="width: 25%; text-align: right;">Total Recaudado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            $posicion = 1;
                            foreach($rankingProductos as $prod): 
                                $participacion = round(($prod['total_recaudado'] / $totalFacturado) * 100, 1);
                        ?>
                            <tr>
                                <td>
                                    <?php if($posicion === 1): ?>
                                        <span class="badge-ranking badge-ranking--oro">1</span>
                                    <?php elseif($posicion === 2): ?>
                                        <span class="badge-ranking badge-ranking--plata">2</span>
                                    <?php elseif($posicion === 3): ?>
                                        <span class="badge-ranking badge-ranking--bronce">3</span>
                                    <?php else: ?>
                                        <span class="badge-ranking"><?php echo $posicion; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo s($prod['nombre_producto']); ?></strong>
                                </td>
                                <td>
                                    <span class="badge badge--categoria"><?php echo s($prod['categoria_nombre']); ?></span>
                                </td>
                                <td style="text-align: right; font-weight: 700; color: #1e293b;">
                                    <?php echo (int)$prod['total_unidades']; ?> u.
                                </td>
                                <td style="text-align: right;">
                                    <strong>$ <?php echo number_format($prod['total_recaudado'], 2, ',', '.'); ?></strong>
                                    <div style="font-size: 1.15rem; color: #64748b;"><?php echo $participacion; ?>% de las ventas</div>
                                </td>
                            </tr>
                        <?php 
                            $posicion++;
                            endforeach; 
                        ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- 5. BALANCE DE CONSUMO DE INSUMOS & MERMAS -->
    <div class="card u-separador-abajo">
        <div class="card__cabecera">
            <div class="card__titulo-bloque">
                <i class="fa-solid fa-boxes-stacked" style="color: #10b981;"></i>
                <h3 class="card__titulo">Balance de Insumos & Materias Primas</h3>
            </div>
            <span style="font-size: 1.3rem; color: #64748b;">Consumo calculado según recetas y mermas del período</span>
        </div>
        <div class="card__cuerpo card__cuerpo--tabla">
            <?php if(empty($balanceInsumos)): ?>
                <p style="padding: 2.4rem; color: #64748b; text-align: center;">No hay insumos registrados en el sistema.</p>
            <?php else: ?>
                <table class="tabla-reporte">
                    <thead>
                        <tr>
                            <th>Insumo</th>
                            <th>Unidad</th>
                            <th style="text-align: right;">Consumo Ventas</th>
                            <th style="text-align: right;">Mermas / Descarte</th>
                            <th style="text-align: right;">Stock Actual</th>
                            <th style="text-align: center;">Estado Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($balanceInsumos as $ins): ?>
                            <tr>
                                <td>
                                    <strong><?php echo s($ins['insumo_nombre']); ?></strong>
                                </td>
                                <td>
                                    <span style="color: #64748b; font-size: 1.25rem;"><?php echo s($ins['unidad_medida']); ?></span>
                                </td>
                                <td style="text-align: right; font-weight: 600; color: #1e293b;">
                                    <?php echo formatearCantidad($ins['consumo_ventas'], $ins['unidad_medida']); ?>
                                </td>
                                <td style="text-align: right; color: #ef4444; font-weight: 600;">
                                    <?php echo $ins['total_mermas'] > 0 ? ('-' . formatearCantidad($ins['total_mermas'], $ins['unidad_medida'])) : '0'; ?>
                                </td>
                                <td style="text-align: right; font-weight: 700;">
                                    <?php echo formatearCantidad($ins['stock_actual'], $ins['unidad_medida']); ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if($ins['stock_actual'] <= 0): ?>
                                        <span class="badge badge--error">Agotado</span>
                                    <?php elseif($ins['stock_bajo']): ?>
                                        <span class="badge badge--alerta">Stock Mínimo</span>
                                    <?php else: ?>
                                        <span class="badge badge--exito">Normal</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- 6. RENDIMIENTO DE REPARTIDORES -->
    <div class="card">
        <div class="card__cabecera">
            <div class="card__titulo-bloque">
                <i class="fa-solid fa-motorcycle" style="color: #ea580c;"></i>
                <h3 class="card__titulo">Rendimiento de Repartidores</h3>
            </div>
            <span style="font-size: 1.3rem; color: #64748b;">Tiempos promedio y efectividad de entrega</span>
        </div>
        <div class="card__cuerpo card__cuerpo--tabla">
            <?php if(empty($metricasRepartidores)): ?>
                <p style="padding: 2.4rem; color: #64748b; text-align: center;">No hay repartidores registrados en el comercio.</p>
            <?php else: ?>
                <table class="tabla-reporte">
                    <thead>
                        <tr>
                            <th>Repartidor</th>
                            <th>Teléfono</th>
                            <th style="text-align: right;">Entregas Realizadas</th>
                            <th style="text-align: right;">Tiempo Promedio</th>
                            <th style="text-align: right;">Efectivo Cobrado</th>
                            <th style="text-align: center;">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($metricasRepartidores as $rep): ?>
                            <tr>
                                <td>
                                    <strong><?php echo s($rep['repartidor_nombre']); ?></strong>
                                </td>
                                <td>
                                    <span style="color: #64748b; font-size: 1.3rem;"><?php echo s($rep['telefono'] ?: 'Sin teléfono'); ?></span>
                                </td>
                                <td style="text-align: right; font-weight: 700; color: #1e293b;">
                                    <?php echo (int)$rep['entregas_completadas']; ?> envíos
                                </td>
                                <td style="text-align: right; font-weight: 600;">
                                    <?php if($rep['entregas_completadas'] > 0): ?>
                                        <span style="color: <?php echo $rep['minutos_promedio'] <= 25 ? '#10b981' : ($rep['minutos_promedio'] <= 40 ? '#f59e0b' : '#ef4444'); ?>;">
                                            <i class="fa-solid fa-stopwatch"></i> <?php echo $rep['minutos_promedio']; ?> min
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #94a3b8;">Sin entregas</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right; font-weight: 700; color: #10b981;">
                                    $ <?php echo number_format($rep['total_efectivo_cobrado'], 2, ',', '.'); ?>
                                </td>
                                <td style="text-align: center;">
                                    <?php if($rep['activo']): ?>
                                        <span class="badge badge--exito">Activo</span>
                                    <?php else: ?>
                                        <span class="badge badge--error">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

</div>
