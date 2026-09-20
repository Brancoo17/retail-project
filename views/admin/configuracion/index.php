<div class="contenedor seccion">
    <div class="admin-encabezado">
        <a href="/admin" class="boton boton--secundario boton--pequeno">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Volver al Panel</span>
        </a>
        <h1 class="seccion__titulo">Configuración del Comercio</h1>
        <p class="seccion__subtitulo">Administra los datos del local, ubicación exacta en el mapa y parámetros de delivery.</p>
    </div>

    <?php include_once __DIR__ . '/../../templates/alertas.php'; ?>

    <form class="formulario formulario--admin" method="POST" action="/admin/configuracion" enctype="multipart/form-data">
        
        <!-- SECCIÓN 1: DATOS GENERALES -->
        <fieldset class="admin-tarjeta">
            <legend class="admin-tarjeta__titulo">
                <i class="fa-solid fa-store"></i>
                <span>Información del Local</span>
            </legend>

            <div class="grid grid--2">
                <div class="formulario__campo">
                    <label class="formulario__label" for="nombre">Nombre del Comercio</label>
                    <input 
                        type="text" 
                        id="nombre" 
                        name="comercio[nombre]" 
                        class="formulario__input" 
                        placeholder="Ej: Pizzería Don Mario"
                        value="<?php echo s($comercio->nombre ?? ''); ?>"
                        required
                    >
                </div>

                <div class="formulario__campo">
                    <label class="formulario__label" for="telefono">Teléfono / WhatsApp</label>
                    <input 
                        type="text" 
                        id="telefono" 
                        name="comercio[telefono]" 
                        class="formulario__input" 
                        placeholder="Ej: +54 11 4321-8765"
                        value="<?php echo s($comercio->telefono ?? ''); ?>"
                    >
                </div>

                <div class="formulario__campo">
                    <label class="formulario__label" for="direccion">Dirección Física (Calle y Número)</label>
                    <input 
                        type="text" 
                        id="direccion" 
                        name="comercio[direccion]" 
                        class="formulario__input" 
                        placeholder="Ej: Av. Corrientes 1234, CABA"
                        value="<?php echo s($comercio->direccion ?? ''); ?>"
                        required
                    >
                </div>

                <div class="formulario__campo">
                    <label class="formulario__label" for="horario">Horario de Atención</label>
                    <input 
                        type="text" 
                        id="horario" 
                        name="comercio[horario]" 
                        class="formulario__input" 
                        placeholder="Ej: Martes a Domingo de 19:30 a 00:30"
                        value="<?php echo s($comercio->horario ?? ''); ?>"
                    >
                </div>
            </div>
        </fieldset>

        <!-- SECCIÓN 2: UBICACIÓN Y MAPA -->
        <fieldset class="admin-tarjeta">
            <legend class="admin-tarjeta__titulo">
                <i class="fa-solid fa-map-location-dot"></i>
                <span>Ubicación en el Mapa (Coordenadas de Origen para Delivery)</span>
            </legend>
            <p class="admin-tarjeta__descripcion">
                Arrastra el pin o haz clic en el mapa para ubicar con exactitud tu local. Con estas coordenadas el sistema calculará en tiempo real la distancia (Haversine) y el costo de envío de cada pedido.
            </p>

            <div id="mapa-comercio" class="mapa-interactivo"></div>

            <div class="grid grid--2 mapa-coordenadas">
                <div class="formulario__campo">
                    <label class="formulario__label" for="lat">Latitud</label>
                    <input 
                        type="text" 
                        id="lat" 
                        name="comercio[lat]" 
                        class="formulario__input" 
                        value="<?php echo s($comercio->lat ?? '-34.603722'); ?>"
                        readonly
                    >
                </div>

                <div class="formulario__campo">
                    <label class="formulario__label" for="lng">Longitud</label>
                    <input 
                        type="text" 
                        id="lng" 
                        name="comercio[lng]" 
                        class="formulario__input" 
                        value="<?php echo s($comercio->lng ?? '-58.381592'); ?>"
                        readonly
                    >
                </div>
            </div>
        </fieldset>

        <!-- SECCIÓN 3: DELIVERY Y CANCELACIÓN -->
        <fieldset class="admin-tarjeta">
            <legend class="admin-tarjeta__titulo">
                <i class="fa-solid fa-truck"></i>
                <span>Parámetros de Envíos y Cancelación</span>
            </legend>

            <div class="grid grid--3">
                <div class="formulario__campo">
                    <label class="formulario__label" for="km_envio_gratis">Radio de Envío Gratis (km)</label>
                    <input 
                        type="number" 
                        step="0.1" 
                        min="0" 
                        id="km_envio_gratis" 
                        name="comercio[km_envio_gratis]" 
                        class="formulario__input" 
                        value="<?php echo s($comercio->km_envio_gratis ?? '3.0'); ?>"
                        required
                    >
                    <span class="formulario__ayuda">Hasta cuántos km a la redonda el delivery no tiene costo.</span>
                </div>

                <div class="formulario__campo">
                    <label class="formulario__label" for="costo_envio_por_km">Costo por km Excedente ($)</label>
                    <input 
                        type="number" 
                        step="10" 
                        min="0" 
                        id="costo_envio_por_km" 
                        name="comercio[costo_envio_por_km]" 
                        class="formulario__input" 
                        value="<?php echo s($comercio->costo_envio_por_km ?? '500.0'); ?>"
                        required
                    >
                    <span class="formulario__ayuda">Se cobra únicamente sobre los kilómetros que superen el radio gratis.</span>
                </div>

                <div class="formulario__campo">
                    <label class="formulario__label" for="minutos_cancelacion">Ventana de Cancelación (minutos)</label>
                    <input 
                        type="number" 
                        min="0" 
                        id="minutos_cancelacion" 
                        name="comercio[minutos_cancelacion]" 
                        class="formulario__input" 
                        value="<?php echo s($comercio->minutos_cancelacion ?? '5'); ?>"
                        required
                    >
                    <span class="formulario__ayuda">Tiempo que tiene el cliente para cancelar antes de que el local lo prepare.</span>
                </div>
            </div>
        </fieldset>

        <!-- SECCIÓN 4: IDENTIDAD VISUAL -->
        <fieldset class="admin-tarjeta">
            <legend class="admin-tarjeta__titulo">
                <i class="fa-solid fa-palette"></i>
                <span>Identidad Visual y Logo</span>
            </legend>

            <div class="grid grid--2">
                <div class="formulario__campo">
                    <label class="formulario__label" for="logo">Logo del Comercio (WebP / PNG / JPG)</label>
                    <?php if(!empty($comercio->logo) && file_exists(CARPETA_IMAGENES . $comercio->logo)): ?>
                        <div class="logo-preview-contenedor">
                            <span class="formulario__ayuda">Logo actual:</span>
                            <img src="/imagenes/<?php echo s($comercio->logo); ?>" alt="Logo Comercio" class="logo-preview">
                        </div>
                    <?php endif; ?>
                    <input 
                        type="file" 
                        id="logo" 
                        name="logo" 
                        class="formulario__input" 
                        accept="image/png, image/jpeg, image/webp"
                    >
                </div>

                <div class="grid grid--2">
                    <div class="formulario__campo">
                        <label class="formulario__label" for="color_primario">Color Primario</label>
                        <div class="color-picker-grupo">
                            <input 
                                type="color" 
                                id="color_primario" 
                                name="comercio[color_primario]" 
                                class="color-picker"
                                value="<?php echo s($comercio->color_primario ?? '#ea580c'); ?>"
                            >
                            <span class="color-picker-codigo"><?php echo s($comercio->color_primario ?? '#ea580c'); ?></span>
                        </div>
                    </div>

                    <div class="formulario__campo">
                        <label class="formulario__label" for="color_secundario">Color Secundario</label>
                        <div class="color-picker-grupo">
                            <input 
                                type="color" 
                                id="color_secundario" 
                                name="comercio[color_secundario]" 
                                class="color-picker"
                                value="<?php echo s($comercio->color_secundario ?? '#1e293b'); ?>"
                            >
                            <span class="color-picker-codigo"><?php echo s($comercio->color_secundario ?? '#1e293b'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </fieldset>

        <div class="formulario__acciones-admin">
            <button type="submit" class="boton boton--primario">
                <i class="fa-solid fa-floppy-disk"></i>
                <span>Guardar Configuración</span>
            </button>
        </div>
    </form>
</div>
