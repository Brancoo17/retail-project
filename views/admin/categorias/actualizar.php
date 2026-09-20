<div class="contenedor seccion">
    <div class="admin-encabezado">
        <a href="/admin/categorias" class="boton boton--secundario boton--pequeno">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Volver a Categorías</span>
        </a>
        <h1 class="seccion__titulo">Editar Categoría</h1>
        <p class="seccion__subtitulo">Modifica el nombre, orden o visibilidad de la categoría.</p>
    </div>

    <?php include_once __DIR__ . '/../../templates/alertas.php'; ?>

    <form class="formulario formulario--admin" method="POST">
        <fieldset class="admin-tarjeta">
            <legend class="admin-tarjeta__titulo">
                <i class="fa-solid fa-pen-to-square"></i>
                <span>Modificar Categoría</span>
            </legend>

            <?php include_once __DIR__ . '/formulario.php'; ?>
        </fieldset>

        <div class="formulario__acciones-admin">
            <button type="submit" class="boton boton--primario">
                <i class="fa-solid fa-floppy-disk"></i>
                <span>Actualizar Categoría</span>
            </button>
        </div>
    </form>
</div>
