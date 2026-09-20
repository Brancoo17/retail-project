<div class="contenedor seccion">
    <div class="admin-encabezado">
        <a href="/admin/insumos" class="boton boton--secundario boton--pequeno">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Volver a Insumos</span>
        </a>
        <h1 class="seccion__titulo">Nuevo Insumo</h1>
        <p class="seccion__subtitulo">Registra una nueva materia prima o insumo para el control de inventario y recetas.</p>
    </div>

    <?php include_once __DIR__ . '/../../templates/alertas.php'; ?>

    <form class="formulario formulario--admin" method="POST">
        <fieldset class="admin-tarjeta">
            <legend class="admin-tarjeta__titulo">
                <i class="fa-solid fa-boxes-stacked"></i>
                <span>Datos del Insumo</span>
            </legend>

            <?php include_once __DIR__ . '/formulario.php'; ?>
        </fieldset>

        <div class="formulario__acciones-admin">
            <button type="submit" class="boton boton--primario">
                <i class="fa-solid fa-plus"></i>
                <span>Crear Insumo</span>
            </button>
        </div>
    </form>
</div>
