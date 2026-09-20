<div class="contenedor seccion">
    <div class="admin-encabezado">
        <a href="/admin" class="boton boton--secundario boton--pequeno">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Volver al Panel</span>
        </a>
        <div class="admin-encabezado__info">
            <h1 class="admin-encabezado__titulo">Catálogo de Productos</h1>
            <p class="admin-encabezado__subtitulo">Gestiona los platos, bebidas y adicionales que verán tus clientes en el menú.</p>
        </div>
        <div class="admin-encabezado__acciones">
            <a href="/admin/productos/crear" class="boton boton--primario">
                <i class="fa-solid fa-plus"></i>
                <span>Nuevo Producto</span>
            </a>
        </div>
    </div>

    <?php include_once __DIR__ . '/../../templates/alertas.php'; ?>

    <?php if(empty($productos)): ?>
        <div class="card-modulo card-modulo--vacio">
            <div class="card-modulo__icono card-modulo__icono--pedidos">
                <i class="fa-solid fa-pizza-slice"></i>
            </div>
            <h3 class="card-modulo__titulo">No hay productos en el catálogo</h3>
            <p class="card-modulo__texto">Comienza agregando platos, postres o bebidas a tus categorías creadas.</p>
            <div style="margin-top: 1.6rem;">
                <a href="/admin/productos/crear" class="boton boton--primario">
                    <i class="fa-solid fa-plus"></i>
                    <span>Cargar Primer Producto</span>
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="tabla-contenedor">
            <table class="tabla">
                <thead class="tabla__thead">
                    <tr>
                        <th class="tabla__th">Foto</th>
                        <th class="tabla__th">Producto</th>
                        <th class="tabla__th">Categoría</th>
                        <th class="tabla__th">Precio Base</th>
                        <th class="tabla__th">Estado</th>
                        <th class="tabla__th tabla__th--acciones">Acciones</th>
                    </tr>
                </thead>
                <tbody class="tabla__tbody">
                    <?php foreach($productos as $prod): ?>
                        <tr class="tabla__tr">
                            <td class="tabla__td tabla__td--foto">
                                <?php if(!empty($prod->imagen) && file_exists(CARPETA_IMAGENES . $prod->imagen)): ?>
                                    <img src="/imagenes/<?php echo s($prod->imagen); ?>" alt="<?php echo s($prod->nombre); ?>" class="producto-thumb">
                                <?php else: ?>
                                    <div class="producto-thumb-placeholder">
                                        <i class="fa-solid fa-utensils"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="tabla__td">
                                <strong><?php echo s($prod->nombre); ?></strong>
                                <?php if(!empty($prod->descripcion)): ?>
                                    <p class="producto-desc-corta"><?php echo s(substr($prod->descripcion, 0, 70)) . (strlen($prod->descripcion) > 70 ? '...' : ''); ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="tabla__td">
                                <span class="badge badge--categoria"><?php echo s($prod->categoria_nombre ?? 'General'); ?></span>
                            </td>
                            <td class="tabla__td">
                                <span class="precio-destacado">$<?php echo number_format($prod->precio, 2, ',', '.'); ?></span>
                            </td>
                            <td class="tabla__td">
                                <?php if((int)$prod->activo === 1): ?>
                                    <span class="badge badge--exito">Disponible</span>
                                <?php else: ?>
                                    <span class="badge badge--error">Pausado</span>
                                <?php endif; ?>
                            </td>
                            <td class="tabla__td tabla__td--acciones">
                                <a href="/admin/productos/actualizar?id=<?php echo s((string)$prod->id); ?>" class="boton-accion boton-accion--editar" title="Editar Producto y Opciones">
                                    <i class="fa-solid fa-pen"></i>
                                </a>

                                <form method="POST" action="/admin/productos/eliminar" onsubmit="return confirm('¿Seguro que deseas eliminar este producto? Se eliminarán también sus opciones y fotos.');">
                                    <input type="hidden" name="id" value="<?php echo s((string)$prod->id); ?>">
                                    <button type="submit" class="boton-accion boton-accion--eliminar" title="Eliminar Producto">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
