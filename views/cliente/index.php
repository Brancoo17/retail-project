<div class="contenedor seccion">
    <div class="admin-encabezado">
        <span class="badge badge--destacado">Panel de Cliente</span>
        <h1 class="seccion__titulo">Mis Pedidos & Direcciones</h1>
        <p class="seccion__subtitulo">Hola, <?php echo s($_SESSION['nombre'] ?? 'Cliente'); ?>. Consulta el estado de tus compras y administra tus lugares de entrega.</p>
        
        <div class="admin-encabezado__acciones">
            <a href="/menu" class="boton boton--primario">
                <i class="fa-solid fa-utensils"></i>
                <span>Hacer un Nuevo Pedido</span>
            </a>
        </div>
    </div>

    <!-- HISTORIAL DE PEDIDOS -->
    <div class="admin-tarjeta u-separador-abajo">
        <h2 class="admin-tarjeta__titulo">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>Historial de Pedidos</span>
        </h2>

        <?php if(empty($pedidos)): ?>
            <div class="card-modulo--vacio">
                <i class="fa-solid fa-bag-shopping card-modulo--vacio__icono"></i>
                <h3>Aún no has realizado pedidos</h3>
                <p>Navega por nuestra carta, agrega tus platos preferidos al carrito y disfrútalos.</p>
                <a href="/menu" class="boton boton--primario card-modulo--vacio__boton">
                    <i class="fa-solid fa-utensils"></i>
                    <span>Ver Menú Digital</span>
                </a>
            </div>
        <?php else: ?>
            <div class="tabla-contenedor">
                <table class="tabla">
                    <thead class="tabla__thead">
                        <tr>
                            <th class="tabla__th">Pedido #</th>
                            <th class="tabla__th">Fecha</th>
                            <th class="tabla__th">Modalidad</th>
                            <th class="tabla__th">Estado</th>
                            <th class="tabla__th">Total</th>
                            <th class="tabla__th tabla__th--acciones">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="tabla__tbody">
                        <?php foreach($pedidos as $ped): ?>
                            <tr class="tabla__tr">
                                <td class="tabla__td">
                                    <strong class="u-texto-negrita">#<?php echo (int)$ped->id; ?></strong>
                                </td>
                                <td class="tabla__td">
                                    <small class="tabla__unidad"><?php echo s(date('d/m/Y H:i', strtotime($ped->creado_en ?? 'now'))); ?></small>
                                </td>
                                <td class="tabla__td">
                                    <span><?php echo ($ped->tipo === 'delivery') ? 'Delivery' : 'Retiro'; ?></span>
                                </td>
                                <td class="tabla__td">
                                    <span class="badge <?php echo $ped->estadoBadgeClase(); ?>">
                                        <?php echo s($ped->estadoTexto()); ?>
                                    </span>
                                </td>
                                <td class="tabla__td">
                                    <span class="precio-destacado">$ <?php echo number_format((float)$ped->total, 2, ',', '.'); ?></span>
                                </td>
                                <td class="tabla__td tabla__td--acciones">
                                    <a 
                                        href="/pedido/seguimiento?id=<?php echo (int)$ped->id; ?>" 
                                        class="boton boton--secundario boton--pequeno"
                                        title="Seguir pedido"
                                    >
                                        <i class="fa-solid fa-eye"></i>
                                        <span>Seguimiento</span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- LIBRETA DE DIRECCIONES GUARDADAS -->
    <div class="admin-tarjeta">
        <h2 class="admin-tarjeta__titulo">
            <i class="fa-solid fa-map-pin"></i>
            <span>Direcciones Guardadas</span>
        </h2>

        <?php if(empty($direcciones)): ?>
            <p class="admin-tarjeta__descripcion">
                Cuando hagas un pedido con entrega a domicilio, guardaremos automáticamente tu dirección para que tu próxima compra sea en un solo clic.
            </p>
        <?php else: ?>
            <div class="grid grid--3">
                <?php foreach($direcciones as $dir): ?>
                    <div class="admin-tarjeta" style="margin-bottom: 0; background-color: #fafaf9;">
                        <span class="badge badge--orden u-separador-abajo-sm"><?php echo s($dir->etiqueta); ?></span>
                        <p style="font-size: 1.4rem; font-weight: 700; color: #1e293b; margin: 0 0 0.6rem 0;">
                            <?php echo s($dir->direccion); ?>
                        </p>
                        <?php if(!empty($dir->observaciones)): ?>
                            <small style="color: #64748b;"><?php echo s($dir->observaciones); ?></small>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
