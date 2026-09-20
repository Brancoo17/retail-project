<!-- HERO SECTION GASTRONÓMICO -->
<section class="hero">
    <div class="contenedor hero__contenido">
        <div class="hero__texto">
            <div class="hero__badge-estado">
                <span class="punto-pulso"></span>
                <span>Cocina Abierta • Envíos en Marcha</span>
            </div>

            <h1 class="hero__titulo">
                El verdadero sabor casero, <span>directo a tu mesa</span>
            </h1>

            <p class="hero__descripcion">
                Ingredientes frescos seleccionados a diario, cocción artesanal y envíos veloces con seguimiento en tiempo real. Hacé tu pedido en minutos sin registros complicados.
            </p>

            <div class="hero__acciones">
                <a href="/menu" class="boton boton--primario boton--lg">
                    <i class="fa-solid fa-utensils"></i> Ver Menú y Pedir
                </a>
                <a href="/crear-cuenta" class="boton boton--secundario boton--lg">
                    <i class="fa-solid fa-user-plus"></i> Registrarse
                </a>
            </div>

            <div class="hero__garantias">
                <div class="hero__garantias-item">
                    <i class="fa-solid fa-bolt"></i>
                    <span>Entrega rápida</span>
                </div>
                <div class="hero__garantias-item">
                    <i class="fa-solid fa-seedling"></i>
                    <span>100% Fresco</span>
                </div>
                <div class="hero__garantias-item">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>Empaque térmico</span>
                </div>
            </div>
        </div>

        <div class="hero__visual">
            <div class="tarjeta-hero-gastronomica">
                <div class="tarjeta-hero-gastronomica__cabecera">
                    <div class="tarjeta-hero-gastronomica__icono-comercio">
                        <i class="fa-solid fa-burger"></i>
                    </div>
                    <div>
                        <h3 class="tarjeta-hero-gastronomica__titulo-comercio">
                            <?php echo s($comercio->nombre ?? 'Retail Gastronomía'); ?>
                        </h3>
                        <span class="tarjeta-hero-gastronomica__subtitulo-comercio">
                            <?php echo s($comercio->horario ?? 'Abierto hoy'); ?>
                        </span>
                    </div>
                </div>

                <div class="tarjeta-hero-gastronomica__items">
                    <div class="tarjeta-hero-gastronomica__item">
                        <div class="tarjeta-hero-gastronomica__item-icono tarjeta-hero-gastronomica__item-icono--fuego">
                            <i class="fa-solid fa-fire-burner"></i>
                        </div>
                        <div class="tarjeta-hero-gastronomica__item-texto">
                            <strong>Cocina Artesanal</strong>
                            <small>Elaborado en el momento con recetas caseras</small>
                        </div>
                    </div>

                    <div class="tarjeta-hero-gastronomica__item">
                        <div class="tarjeta-hero-gastronomica__item-icono tarjeta-hero-gastronomica__item-icono--envio">
                            <i class="fa-solid fa-motorcycle"></i>
                        </div>
                        <div class="tarjeta-hero-gastronomica__item-texto">
                            <strong>Envío Gratis</strong>
                            <small>Hasta los primeros <?php echo (float)($comercio->km_envio_gratis ?? 3); ?> km a la redonda</small>
                        </div>
                    </div>

                    <div class="tarjeta-hero-gastronomica__item">
                        <div class="tarjeta-hero-gastronomica__item-icono tarjeta-hero-gastronomica__item-icono--reloj">
                            <i class="fa-solid fa-stopwatch"></i>
                        </div>
                        <div class="tarjeta-hero-gastronomica__item-texto">
                            <strong>Seguimiento en Vivo</strong>
                            <small>Mirá el estado de tu comanda con alerta sonora</small>
                        </div>
                    </div>
                </div>

                <div class="tarjeta-hero-gastronomica__pie">
                    <span>Medios de pago</span>
                    <div class="badge-mp">
                        <i class="fa-solid fa-credit-card"></i> Mercado Pago & Efectivo
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CATEGORÍAS RÁPIDAS -->
<?php if(!empty($categorias)): ?>
<section class="seccion" style="padding-top: 2rem; padding-bottom: 2rem;">
    <div class="contenedor">
        <header class="seccion__encabezado" style="margin-bottom: 2rem;">
            <span class="badge badge--destacado">Nuestra Carta</span>
            <h2 class="seccion__titulo" style="font-size: 2.8rem;">¿Qué te gustaría disfrutar hoy?</h2>
        </header>

        <div class="home-categorias">
            <a href="/menu" class="home-categorias__item">
                <i class="fa-solid fa-border-all"></i> Todos los Platos
            </a>
            <?php foreach($categorias as $cat): ?>
                <a href="/menu#cat-<?php echo (int)$cat->id; ?>" class="home-categorias__item">
                    <i class="fa-solid fa-utensils"></i> <?php echo s($cat->nombre); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- PLATOS DESTACADOS / ESPECIALIDADES -->
<?php if(!empty($destacados)): ?>
<section class="seccion" style="background-color: #f8fafc;">
    <div class="contenedor">
        <header class="seccion__encabezado">
            <span class="badge badge--destacado">Recomendados</span>
            <h2 class="seccion__titulo">Especialidades de la Casa</h2>
            <p class="seccion__subtitulo">Los platos más pedidos por nuestros clientes, elaborados con materias primas de primera calidad.</p>
        </header>

        <div class="grid grid--3">
            <?php foreach($destacados as $prod): ?>
                <?php 
                    $tieneImg = (!empty($prod->imagen) && file_exists(CARPETA_IMAGENES . $prod->imagen));
                ?>
                <article class="card-producto" style="height: 100%; display: flex; flex-direction: column;">
                    <div class="card-producto__imagen-contenedor">
                        <?php if($tieneImg): ?>
                            <img src="/imagenes/<?php echo s($prod->imagen); ?>" alt="<?php echo s($prod->nombre); ?>" class="card-producto__imagen" loading="lazy">
                        <?php else: ?>
                            <div class="card-producto__placeholder">
                                <i class="fa-solid fa-pizza-slice"></i>
                            </div>
                        <?php endif; ?>

                        <?php if(!empty($prod->categoria_nombre)): ?>
                            <div style="position: absolute; top: 1rem; left: 1rem;">
                                <span class="badge badge--destacado" style="background: rgba(15, 23, 42, 0.75); color: #fff; border: 1px solid rgba(255,255,255,0.2);">
                                    <?php echo s($prod->categoria_nombre); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="card-producto__cuerpo" style="padding: 2.4rem; display: flex; flex-direction: column; flex: 1; justify-content: space-between;">
                        <div>
                            <h3 class="card-producto__nombre" style="font-size: 1.9rem; font-weight: 700; margin-bottom: 0.8rem; color: #1e293b;"><?php echo s($prod->nombre); ?></h3>
                            <?php if(!empty($prod->descripcion)): ?>
                                <p class="card-producto__descripcion" style="font-size: 1.4rem; line-height: 1.6; color: #64748b; margin-bottom: 2rem;"><?php echo s($prod->descripcion); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="card-producto__pie" style="padding-top: 1.6rem; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;">
                            <span class="card-producto__precio" style="font-size: 2.2rem; font-weight: 800; color: #1e293b;">
                                $ <?php echo number_format((float)$prod->precio, 2, ',', '.'); ?>
                            </span>
                            <a href="/menu" class="boton boton--primario boton--sm">
                                <i class="fa-solid fa-plus"></i> Pedir
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div style="text-align: center; margin-top: 4rem;">
            <a href="/menu" class="boton boton--secundario boton--lg">
                <i class="fa-solid fa-arrow-right"></i> Ver Carta Completa y Precios
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- CÓMO FUNCIONA -->
<section class="seccion">
    <div class="contenedor">
        <header class="seccion__encabezado">
            <span class="badge badge--destacado">Proceso Simple</span>
            <h2 class="seccion__titulo">Tu pedido en 3 simples pasos</h2>
            <p class="seccion__subtitulo">Sin aplicaciones pesadas, sin tiempos de espera telefónicos y sin registros obligatorios.</p>
        </header>

        <div class="grid grid--3">
            <article class="home-paso">
                <span class="home-paso__numero">01</span>
                <div class="home-paso__icono">
                    <i class="fa-solid fa-list-check"></i>
                </div>
                <h3 class="home-paso__titulo">1. Elegí tus Platos</h3>
                <p class="home-paso__texto">
                    Navegá nuestra carta digital interactiva, seleccioná tus platos favoritos y personalizá aderezos y opciones extras a tu gusto.
                </p>
            </article>

            <article class="home-paso">
                <span class="home-paso__numero">02</span>
                <div class="home-paso__icono">
                    <i class="fa-solid fa-credit-card"></i>
                </div>
                <h3 class="home-paso__titulo">2. Confirmá y Pagá</h3>
                <p class="home-paso__texto">
                    Ingresá tu dirección o elegí en el mapa. Pagá de forma segura con Mercado Pago o elegí efectivo para abonar contra entrega o al retirar.
                </p>
            </article>

            <article class="home-paso">
                <span class="home-paso__numero">03</span>
                <div class="home-paso__icono">
                    <i class="fa-solid fa-motorcycle"></i>
                </div>
                <h3 class="home-paso__titulo">3. Seguimiento en Vivo</h3>
                <p class="home-paso__texto">
                    Mirá en tiempo real cómo avanza tu orden desde la cocina hasta tu puerta. Te avisaremos con sonido y en pantalla cuando esté en camino.
                </p>
            </article>
        </div>
    </div>
</section>

<!-- BENEFICIOS / PROPUESTA DE VALOR -->
<section class="seccion" style="background-color: #f8fafc;">
    <div class="contenedor">
        <header class="seccion__encabezado">
            <span class="badge badge--destacado">Nuestra Promesa</span>
            <h2 class="seccion__titulo">¿Por qué elegirnos?</h2>
            <p class="seccion__subtitulo">Cuidamos cada detalle de la experiencia gastronómica desde la cocina hasta tu mesa.</p>
        </header>

        <div class="grid grid--4">
            <article class="card-modulo">
                <div class="card-modulo__icono" style="background-color: #ffedd5; color: #ea580c;">
                    <i class="fa-solid fa-seedling"></i>
                </div>
                <h3 class="card-modulo__titulo" style="font-size: 1.9rem;">Ingredientes Frescos</h3>
                <p class="card-modulo__texto">
                    Elaboración diaria con materia prima seleccionada. Control estricto de recetas y trazabilidad de insumos.
                </p>
            </article>

            <article class="card-modulo">
                <div class="card-modulo__icono" style="background-color: #e0f2fe; color: #0284c7;">
                    <i class="fa-solid fa-map-location-dot"></i>
                </div>
                <h3 class="card-modulo__titulo" style="font-size: 1.9rem;">Envío Justo y Preciso</h3>
                <p class="card-modulo__texto">
                    Cálculo de costo de envío inteligente por distancia real en mapa y flete gratuito dentro de la zona de cercanía.
                </p>
            </article>

            <article class="card-modulo">
                <div class="card-modulo__icono" style="background-color: #fef3c7; color: #d97706;">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h3 class="card-modulo__titulo" style="font-size: 1.9rem;">Empaque Térmico</h3>
                <p class="card-modulo__texto">
                    Bolsas y recipientes térmicos especialmente acondicionados para que recibas tu pedido caliente y crujiente.
                </p>
            </article>

            <article class="card-modulo">
                <div class="card-modulo__icono" style="background-color: #ecfdf5; color: #10b981;">
                    <i class="fa-solid fa-headset"></i>
                </div>
                <h3 class="card-modulo__titulo" style="font-size: 1.9rem;">Contacto Directo</h3>
                <p class="card-modulo__texto">
                    Comunicación fluida con el local ante cualquier necesidad, indicación especial o consulta sobre tu entrega.
                </p>
            </article>
        </div>
    </div>
</section>

<!-- INFORMACIÓN DE CONTACTO Y HORARIOS -->
<section class="seccion">
    <div class="contenedor">
        <div class="home-info-local">
            <div class="home-info-local__detalles">
                <span class="badge badge--destacado">Ubicación y Atención</span>
                <h2 style="font-size: 3rem; color: #1e293b; margin: 0.5rem 0 1rem 0;">
                    <?php echo s($comercio->nombre ?? 'Nuestro Local'); ?>
                </h2>

                <div class="home-info-local__item">
                    <i class="fa-solid fa-location-dot"></i>
                    <div>
                        <strong>Dirección</strong>
                        <span><?php echo s($comercio->direccion ?? 'Consultar en el mapa'); ?></span>
                    </div>
                </div>

                <div class="home-info-local__item">
                    <i class="fa-solid fa-clock"></i>
                    <div>
                        <strong>Horario de Atención</strong>
                        <span><?php echo s($comercio->horario ?? 'Miércoles a Domingos'); ?></span>
                    </div>
                </div>

                <?php if(!empty($comercio->telefono)): ?>
                <div class="home-info-local__item">
                    <i class="fa-solid fa-phone"></i>
                    <div>
                        <strong>Teléfono de Contacto</strong>
                        <span><?php echo s($comercio->telefono); ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="home-info-local__acciones">
                <a href="/menu" class="boton boton--primario boton--lg" style="width: 100%; text-align: center;">
                    <i class="fa-solid fa-utensils"></i> Hacer un Pedido Ahora
                </a>

                <?php if(!empty($comercio->telefono)): ?>
                    <?php 
                        $telLimpio = preg_replace('/[^0-9]/', '', $comercio->telefono);
                    ?>
                    <a href="https://wa.me/549<?php echo $telLimpio; ?>?text=Hola!%20Quería%20hacer%20una%20consulta%20sobre%20los%20pedidos" target="_blank" rel="noopener noreferrer" class="boton boton--exito boton--lg" style="width: 100%; text-align: center;">
                        <i class="fa-brands fa-whatsapp"></i> Consultar por WhatsApp
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
