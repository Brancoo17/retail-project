<div class="contenedor seccion">
    <div class="admin-encabezado">
        <a href="/admin" class="boton boton--secundario boton--pequeno">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Volver al Panel</span>
        </a>
        <div class="admin-encabezado__info">
            <h1 class="admin-encabezado__titulo">Categorías del Menú</h1>
            <p class="admin-encabezado__subtitulo">Organiza tu carta en secciones (Pizzas, Hamburguesas, Bebidas, etc.).</p>
        </div>
        <div class="admin-encabezado__acciones">
            <a href="/admin/aderezos" class="boton boton--secundario" style="margin-right: 0.6rem;">
                <i class="fa-solid fa-bottle-droplet" style="color: #ea580c;"></i>
                <span>Salsas & Aderezos</span>
            </a>
            <a href="/admin/categorias/crear" class="boton boton--primario">
                <i class="fa-solid fa-plus"></i>
                <span>Nueva Categoría</span>
            </a>
        </div>
    </div>

    <?php include_once __DIR__ . '/../../templates/alertas.php'; ?>

    <?php if(empty($categorias)): ?>
        <div class="card-modulo card-modulo--vacio">
            <div class="card-modulo__icono card-modulo__icono--pedidos">
                <i class="fa-solid fa-tags"></i>
            </div>
            <h3 class="card-modulo__titulo">No hay categorías registradas</h3>
            <p class="card-modulo__texto">Crea tu primera categoría para comenzar a clasificar y cargar los productos de tu menú.</p>
            <div style="margin-top: 1.6rem;">
                <a href="/admin/categorias/crear" class="boton boton--primario">
                    <i class="fa-solid fa-plus"></i>
                    <span>Crear Primera Categoría</span>
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="tabla-contenedor">
            <table class="tabla">
                <thead class="tabla__thead">
                    <tr>
                        <th class="tabla__th">Orden</th>
                        <th class="tabla__th">Nombre</th>
                        <th class="tabla__th">Aderezos</th>
                        <th class="tabla__th">Estado</th>
                        <th class="tabla__th tabla__th--acciones">Acciones</th>
                    </tr>
                </thead>
                <tbody class="tabla__tbody">
                    <?php foreach($categorias as $cat): ?>
                        <tr class="tabla__tr">
                            <td class="tabla__td tabla__td--orden">
                                <span class="badge badge--orden"><?php echo s((string)$cat->orden); ?></span>
                            </td>
                            <td class="tabla__td tabla__td--nombre">
                                <strong><?php echo s($cat->nombre); ?></strong>
                            </td>
                            <td class="tabla__td">
                                <?php if(!empty($cat->permite_aderezos)): ?>
                                    <span class="badge badge--exito" style="font-size: 1.1rem; background-color: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;">
                                        <i class="fa-solid fa-check"></i> Habilitados
                                    </span>
                                <?php else: ?>
                                    <span style="color: #94a3b8; font-size: 1.2rem;">No incluye</span>
                                <?php endif; ?>
                            </td>
                            <td class="tabla__td">
                                <?php if((int)$cat->activo === 1): ?>
                                    <span class="badge badge--exito">Activa</span>
                                <?php else: ?>
                                    <span class="badge badge--error">Inactiva</span>
                                <?php endif; ?>
                            </td>
                            <td class="tabla__td tabla__td--acciones">
                                <a href="/admin/categorias/actualizar?id=<?php echo s((string)$cat->id); ?>" class="boton-accion boton-accion--editar" title="Editar Categoría">
                                    <i class="fa-solid fa-pen"></i>
                                </a>

                                <form method="POST" action="/admin/categorias/eliminar" onsubmit="return confirm('¿Seguro que deseas eliminar esta categoría? Los productos asociados podrían quedar sin clasificar.');">
                                    <input type="hidden" name="id" value="<?php echo s((string)$cat->id); ?>">
                                    <button type="submit" class="boton-accion boton-accion--eliminar" title="Eliminar Categoría">
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
