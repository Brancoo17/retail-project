<script>
    window.catalogoAderezos = <?php echo json_encode(array_map(function($a) {
        return [
            'id' => (int)$a->id,
            'nombre' => $a->nombre
        ];
    }, $aderezos ?? [])); ?>;
</script>

<div class="contenedor seccion">
    <header class="seccion__encabezado">
        <span class="badge badge--destacado">Carta Digital</span>
        <h1 class="seccion__titulo">Nuestro Menú</h1>
        <p class="seccion__subtitulo">Elegí lo que más te guste, agregá ingredientes extras y recibilo caliente en tu domicilio o retiralo por nuestro local.</p>
    </header>

    <!-- NAVEGACIÓN RÁPIDA POR CATEGORÍAS -->
    <nav class="menu-categorias" aria-label="Categorías del menú">
        <a href="#todos" class="menu-categorias__item menu-categorias__item--activo" data-categoria="todos">Todos los Platos</a>
        <?php foreach($catalogo as $catId => $datosCat): ?>
            <?php if(!empty($datosCat['productos'])): ?>
                <a href="#cat-<?php echo (int)$catId; ?>" class="menu-categorias__item" data-categoria="<?php echo (int)$catId; ?>">
                    <?php echo s($datosCat['categoria']->nombre); ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <!-- LISTADO POR SECCIÓN -->
    <?php $hayProductos = false; ?>
    <?php foreach($catalogo as $catId => $datosCat): ?>
        <?php if(!empty($datosCat['productos'])): ?>
            <?php $hayProductos = true; ?>
            <section id="cat-<?php echo (int)$catId; ?>" class="menu-seccion" data-seccion-cat="<?php echo (int)$catId; ?>">
                <h2 class="menu-seccion__titulo">
                    <i class="fa-solid fa-utensils"></i>
                    <span><?php echo s($datosCat['categoria']->nombre); ?></span>
                </h2>

                <div class="menu-grid">
                    <?php foreach($datosCat['productos'] as $item): ?>
                        <?php 
                            $prod = $item['producto']; 
                            $opciones = $item['opciones'];
                            $disponible = $item['disponible'];
                            $tieneImg = (!empty($prod->imagen) && file_exists(CARPETA_IMAGENES . $prod->imagen));

                            // Preparar datos JSON sanitizados para el modal
                            $jsonProducto = htmlspecialchars(json_encode([
                                'id' => (int)$prod->id,
                                'nombre' => $prod->nombre,
                                'descripcion' => $prod->descripcion ?? '',
                                'precio' => (float)$prod->precio,
                                'imagen' => $tieneImg ? '/imagenes/' . $prod->imagen : null,
                                'permite_aderezos' => (int)($datosCat['categoria']->permite_aderezos ?? 0) === 1,
                                'opciones' => array_map(function($opc) {
                                    return [
                                        'id' => (int)$opc->id,
                                        'nombre' => $opc->nombre,
                                        'precio_adicional' => (float)$opc->precio_adicional
                                    ];
                                }, $opciones)
                            ]), ENT_QUOTES, 'UTF-8');
                        ?>
                        <article class="card-producto <?php echo !$disponible ? 'card-producto--agotado' : ''; ?>">
                            <div class="card-producto__imagen-contenedor">
                                <?php if($tieneImg): ?>
                                    <img src="/imagenes/<?php echo s($prod->imagen); ?>" alt="<?php echo s($prod->nombre); ?>" class="card-producto__imagen" loading="lazy">
                                <?php else: ?>
                                    <div class="card-producto__placeholder">
                                        <i class="fa-solid fa-pizza-slice"></i>
                                    </div>
                                <?php endif; ?>

                                <?php if(!$disponible): ?>
                                    <div class="card-producto__badge-agotado" style="position: absolute; top: 1rem; right: 1rem;">
                                        <span class="badge badge--error">Sin Stock</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="card-producto__cuerpo">
                                <h3 class="card-producto__nombre"><?php echo s($prod->nombre); ?></h3>
                                <p class="card-producto__descripcion"><?php echo s($prod->descripcion ?? 'Elaborado artesanalmente con ingredientes frescos de primera calidad.'); ?></p>
                                
                                <div class="card-producto__pie">
                                    <span class="card-producto__precio">$ <?php echo number_format((float)$prod->precio, 2, ',', '.'); ?></span>

                                    <?php if($disponible): ?>
                                        <button 
                                            type="button" 
                                            class="boton boton--primario boton--pequeno btn-abrir-producto" 
                                            data-producto="<?php echo $jsonProducto; ?>"
                                        >
                                            <i class="fa-solid fa-plus"></i>
                                            <span>Pedir</span>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="boton boton--secundario boton--pequeno" disabled>
                                            <span>Agotado</span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php if(!$hayProductos): ?>
        <div class="admin-tarjeta card-modulo--vacio">
            <i class="fa-solid fa-utensils card-modulo--vacio__icono"></i>
            <h3>El catálogo se encuentra en preparación</h3>
            <p>Muy pronto publicaremos nuestros deliciosos platos. ¡Vuelve a visitarnos!</p>
        </div>
    <?php endif; ?>
</div>

<!-- MODAL DE PERSONALIZACIÓN DE PRODUCTO -->
<div id="modal-producto-overlay" class="modal-overlay" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="modal-producto">
        <button id="modal-cerrar-btn" type="button" class="modal-producto__cerrar" aria-label="Cerrar modal">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div id="modal-img-contenedor">
            <img id="modal-producto-img" src="" alt="" class="modal-producto__imagen">
        </div>

        <div class="modal-producto__contenido">
            <h2 id="modal-producto-titulo" class="modal-producto__titulo"></h2>
            <p id="modal-producto-desc" class="modal-producto__descripcion"></p>

            <!-- SECCIÓN DE ADEREZOS Y SALSAS (SIN CARGO) -->
            <div id="modal-producto-aderezos-grupo" class="modal-producto__grupo-opciones" style="display: none; margin-bottom: 1.6rem;">
                <div class="modal-producto__grupo-titulo">
                    <span><i class="fa-solid fa-bottle-droplet" style="color: #ea580c;"></i> Aderezos y Salsas:</span>
                    <small style="font-weight: 700; color: #16a34a;">(Sin cargo)</small>
                </div>
                <div id="modal-producto-aderezos-lista" class="modal-producto__aderezos-grid">
                    <!-- Checkboxes de salsas inyectados dinámicamente -->
                </div>
            </div>

            <div id="modal-producto-opciones-grupo" class="modal-producto__grupo-opciones">
                <div class="modal-producto__grupo-titulo">
                    <span>Opciones y Adicionales:</span>
                    <small style="font-weight: normal; color: #64748b;">(Opcional)</small>
                </div>
                <div id="modal-producto-opciones-lista">
                    <!-- Opciones inyectadas dinámicamente -->
                </div>
            </div>

            <div class="formulario__campo u-separador-abajo-sm">
                <label for="modal-producto-notas" class="formulario__label">Aclaraciones para la cocina:</label>
                <input 
                    type="text" 
                    id="modal-producto-notas" 
                    class="formulario__input" 
                    placeholder="ej. Sin orégano, bien cocido, cortar en 8 porciones..."
                >
            </div>

            <div class="modal-producto__pie">
                <div class="modal-producto__cantidad">
                    <button type="button" id="modal-btn-menos" aria-label="Restar">-</button>
                    <span id="modal-cantidad-display">1</span>
                    <button type="button" id="modal-btn-mas" aria-label="Sumar">+</button>
                </div>
                <button type="button" id="modal-btn-agregar" class="boton boton--primario modal-producto__boton-agregar">
                    <i class="fa-solid fa-bag-shopping"></i>
                    <span id="modal-btn-agregar-texto">Agregar al Pedido</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- INCLUSIÓN DEL CARRITO FLOTANTE Y DRAWER -->
<?php include_once __DIR__ . '/../templates/carrito.php'; ?>
