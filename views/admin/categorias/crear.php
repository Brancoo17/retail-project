<div class="contenedor seccion">
    <div class="admin-encabezado">
        <a href="/admin/categorias" class="boton boton--secundario boton--pequeno">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Volver a Categorías</span>
        </a>
        <h1 class="seccion__titulo">Nueva Categoría</h1>
        <p class="seccion__subtitulo">Crea una categoría para organizar los productos de tu menú.</p>
    </div>

    <?php include_once __DIR__ . '/../../templates/alertas.php'; ?>

    <form class="formulario formulario--admin" method="POST" action="/admin/categorias/crear">
        <fieldset class="admin-tarjeta">
            <legend class="admin-tarjeta__titulo">
                <i class="fa-solid fa-tags"></i>
                <span>Datos de la Categoría</span>
            </legend>

            <?php include_once __DIR__ . '/formulario.php'; ?>
        </fieldset>

        <div class="formulario__acciones-admin">
            <button type="submit" class="boton boton--primario">
                <i class="fa-solid fa-plus"></i>
                <span>Guardar Categoría</span>
            </button>
        </div>
    </form>
</div>
