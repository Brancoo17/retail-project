<div class="contenedor seccion">
    <div class="admin-encabezado">
        <a href="/admin/productos" class="boton boton--secundario boton--pequeno">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Volver al Catálogo</span>
        </a>
        <h1 class="seccion__titulo">Nuevo Producto</h1>
        <p class="seccion__subtitulo">Carga un nuevo ítem en la carta con su precio base y foto.</p>
    </div>

    <?php include_once __DIR__ . '/../../templates/alertas.php'; ?>

    <form class="formulario formulario--admin" method="POST" action="/admin/productos/crear" enctype="multipart/form-data">
        <fieldset class="admin-tarjeta">
            <legend class="admin-tarjeta__titulo">
                <i class="fa-solid fa-utensils"></i>
                <span>Datos del Producto</span>
            </legend>

            <?php include_once __DIR__ . '/formulario.php'; ?>
        </fieldset>

        <div class="formulario__acciones-admin">
            <button type="submit" class="boton boton--primario">
                <i class="fa-solid fa-plus"></i>
                <span>Guardar Producto</span>
            </button>
        </div>
    </form>
</div>
