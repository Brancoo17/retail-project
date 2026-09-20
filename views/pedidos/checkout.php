<div class="contenedor seccion">
    <div class="admin-encabezado">
        <a href="/menu" class="boton boton--secundario boton--pequeno">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Volver al Menú</span>
        </a>
        <h1 class="seccion__titulo">Finalizar Pedido</h1>
        <p class="seccion__subtitulo">Completa tus datos de entrega, ubica tu dirección en el mapa y elige cómo pagar.</p>
    </div>

    <form id="formulario-checkout" class="checkout-grid" method="POST">
        <!-- COLUMNA IZQUIERDA: DATOS Y LOGÍSTICA -->
        <div class="checkout-principal">
            
            <!-- SELECTOR TIPO DE ENTREGA -->
            <div class="checkout-tabs" role="tablist">
                <div 
                    id="tab-delivery" 
                    class="checkout-tabs__tab checkout-tabs__tab--activo" 
                    role="tab" 
                    aria-selected="true"
                    data-tipo="delivery"
                >
                    <i class="fa-solid fa-motorcycle"></i>
                    <strong>Delivery a Domicilio</strong>
                    <small>Te lo llevamos a tu puerta</small>
                </div>
                <div 
                    id="tab-retiro" 
                    class="checkout-tabs__tab" 
                    role="tab" 
                    aria-selected="false"
                    data-tipo="retiro"
                >
                    <i class="fa-solid fa-store"></i>
                    <strong>Retiro en el Local</strong>
                    <small>Pasas a buscarlo caliente</small>
                </div>
            </div>
            <input type="hidden" name="tipo" id="input-tipo-entrega" value="delivery">

            <!-- DATOS DE CONTACTO -->
            <div class="admin-tarjeta">
                <h2 class="admin-tarjeta__titulo">
                    <i class="fa-solid fa-user"></i>
                    <span>Tus Datos de Contacto</span>
                </h2>

                <div class="grid grid--2">
                    <div class="formulario__campo">
                        <label for="nombre_contacto" class="formulario__label">Nombre Completo:</label>
                        <input 
                            type="text" 
                            id="nombre_contacto" 
                            name="nombre_contacto" 
                            class="formulario__input" 
                            placeholder="Tu nombre y apellido" 
                            value="<?php echo s($usuario->nombre ?? ''); ?>"
                            required
                        >
                    </div>

                    <div class="formulario__campo">
                        <label for="telefono_contacto" class="formulario__label">Teléfono de Contacto:</label>
                        <input 
                            type="tel" 
                            id="telefono_contacto" 
                            name="telefono_contacto" 
                            class="formulario__input" 
                            placeholder="ej. 11 5544-3322" 
                            value="<?php echo s($usuario->telefono ?? ''); ?>"
                            required
                        >
                        <span class="formulario__ayuda">Te contactaremos ante cualquier consulta sobre tu orden.</span>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN DELIVERY: UBICACIÓN Y MAPA -->
            <div id="seccion-delivery" class="admin-tarjeta">
                <h2 class="admin-tarjeta__titulo">
                    <i class="fa-solid fa-map-location-dot"></i>
                    <span>Ubicación de Entrega (Delivery)</span>
                </h2>

                <?php if(!empty($direcciones)): ?>
                    <div class="formulario__campo u-separador-abajo-sm">
                        <label for="select-direccion-guardada" class="formulario__label">Direcciones Guardadas:</label>
                        <select id="select-direccion-guardada" class="formulario__input">
                            <option value="">-- Seleccionar una dirección guardada --</option>
                            <?php foreach($direcciones as $dir): ?>
                                <option 
                                    value="<?php echo (int)$dir->id; ?>"
                                    data-direccion="<?php echo s($dir->direccion); ?>"
                                    data-lat="<?php echo s((string)$dir->lat); ?>"
                                    data-lng="<?php echo s((string)$dir->lng); ?>"
                                >
                                    <?php echo s($dir->etiqueta); ?>: <?php echo s($dir->direccion); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="formulario__campo u-separador-abajo-sm">
                    <label for="direccion_texto" class="formulario__label">Dirección Completa:</label>
                    <input 
                        type="text" 
                        id="direccion_texto" 
                        name="direccion_texto" 
                        class="formulario__input" 
                        placeholder="Calle, Número, Piso/Depto, Timbre, Entrecalles..." 
                        required
                    >
                </div>

                <div class="formulario__campo">
                    <label class="formulario__label">Fijar Pin en el Mapa:</label>
                    <p class="admin-tarjeta__descripcion">
                        Arrastra el marcador o haz clic en el mapa para ubicar tu casa con precisión y calcular la distancia de envío.
                    </p>

                    <!-- CONTENEDOR DEL MAPA Y DATA ATTRIBUTES -->
                    <div class="checkout-mapa-contenedor">
                        <div 
                            id="mapa-checkout"
                            data-comercio-lat="<?php echo s((string)($comercio->lat ?? -34.603722)); ?>"
                            data-comercio-lng="<?php echo s((string)($comercio->lng ?? -58.381592)); ?>"
                            data-km-gratis="<?php echo s((string)($comercio->km_envio_gratis ?? 3.0)); ?>"
                            data-costo-km="<?php echo s((string)($comercio->costo_envio_por_km ?? 500.0)); ?>"
                        ></div>
                    </div>

                    <input type="hidden" id="cliente_lat" name="lat" value="">
                    <input type="hidden" id="cliente_lng" name="lng" value="">

                    <!-- KPI DE ENVÍO CALCULADO POR HAVERSINE -->
                    <div id="kpi-envio" class="checkout-envio-kpi">
                        <div class="checkout-envio-kpi__item">
                            <span class="checkout-envio-kpi__etiqueta">Distancia al Local:</span>
                            <span id="kpi-distancia-texto" class="checkout-envio-kpi__valor">-- km</span>
                        </div>
                        <div class="checkout-envio-kpi__item">
                            <span class="checkout-envio-kpi__etiqueta">Costo de Envío:</span>
                            <span id="kpi-costo-texto" class="checkout-envio-kpi__valor">$ 0,00</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN RETIRO EN LOCAL (OCULTA POR DEFECTO) -->
            <div id="seccion-retiro" class="admin-tarjeta" style="display: none;">
                <h2 class="admin-tarjeta__titulo">
                    <i class="fa-solid fa-store"></i>
                    <span>Información de Retiro</span>
                </h2>
                <p class="admin-tarjeta__descripcion">
                    Tu pedido será preparado en nuestra cocina para que lo retires en el local.
                </p>
                <div class="checkout-envio-kpi">
                    <div class="checkout-envio-kpi__item">
                        <span class="checkout-envio-kpi__etiqueta">Dirección del Local:</span>
                        <strong class="u-texto-negrita"><?php echo s($comercio->direccion ?? 'Av. Principal 1234'); ?></strong>
                    </div>
                    <div class="checkout-envio-kpi__item">
                        <span class="checkout-envio-kpi__etiqueta">Costo de Envío:</span>
                        <span class="badge badge--exito">¡Gratis ($0)!</span>
                    </div>
                </div>
            </div>

            <!-- MÉTODO DE PAGO -->
            <div class="admin-tarjeta">
                <h2 class="admin-tarjeta__titulo">
                    <i class="fa-solid fa-credit-card"></i>
                    <span>Forma de Pago</span>
                </h2>

                <div class="metodos-pago">
                    <label class="metodos-pago__item metodos-pago__item--activo">
                        <input type="radio" name="metodo_pago" value="efectivo" checked style="display: none;">
                        <i class="fa-solid fa-money-bill-wave"></i>
                        <div class="metodos-pago__info">
                            <strong>Efectivo / Al recibir</strong>
                            <small>Pagas al repartidor o al retirar</small>
                        </div>
                    </label>

                    <label class="metodos-pago__item">
                        <input type="radio" name="metodo_pago" value="mercadopago" style="display: none;">
                        <i class="fa-solid fa-qrcode"></i>
                        <div class="metodos-pago__info">
                            <strong>Mercado Pago</strong>
                            <small>Tarjetas, Débito o Dinero en cuenta</small>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <!-- COLUMNA DERECHA: RESUMEN DE COMPRA -->
        <aside class="checkout-lateral">
            <div class="resumen-pedido">
                <h3 class="resumen-pedido__titulo">
                    <i class="fa-solid fa-receipt"></i>
                    <span>Resumen del Pedido</span>
                </h3>

                <div id="checkout-items-lista" class="resumen-pedido__items">
                    <!-- Ítems inyectados dinámicamente desde el carrito -->
                </div>

                <div class="resumen-pedido__fila">
                    <span>Subtotal Productos:</span>
                    <strong id="checkout-subtotal-texto">$ 0,00</strong>
                </div>

                <div class="resumen-pedido__fila">
                    <span>Costo de Envío:</span>
                    <strong id="checkout-envio-texto">$ 0,00</strong>
                </div>

                <div class="resumen-pedido__fila resumen-pedido__fila--total">
                    <span>Total a Pagar:</span>
                    <span id="checkout-total-texto">$ 0,00</span>
                </div>

                <button 
                    id="btn-confirmar-pedido" 
                    type="submit" 
                    class="boton boton--primario u-separador-arriba" 
                    style="width: 100%; height: 5.2rem; font-size: 1.6rem;"
                >
                    <i class="fa-solid fa-check"></i>
                    <span>Confirmar y Enviar Pedido</span>
                </button>

                <p id="checkout-error-msg" class="alerta alerta--error" style="display: none; margin-top: 1.6rem;"></p>
            </div>
        </aside>
    </form>
</div>
