<div class="contenedor seccion">
    <div class="admin-encabezado">
        <a href="/admin/repartidores" class="boton boton--secundario boton--pequeno">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Volver a Repartidores</span>
        </a>
        <h1 class="seccion__titulo">Editar Repartidor</h1>
        <p class="seccion__subtitulo">Modifica los datos personales, estado de disponibilidad o contraseña del repartidor.</p>
    </div>

    <?php include_once __DIR__ . '/../../templates/alertas.php'; ?>

    <form class="formulario formulario--admin" method="POST" action="/admin/repartidores/actualizar?id=<?php echo (int)$repartidor->id; ?>">
        <fieldset class="admin-tarjeta">
            <legend class="admin-tarjeta__titulo">
                <i class="fa-solid fa-motorcycle"></i>
                <span>Datos de <?php echo s($repartidor->nombre); ?></span>
            </legend>

            <?php include_once __DIR__ . '/formulario.php'; ?>
        </fieldset>

        <div class="formulario__acciones-admin">
            <button type="submit" class="boton boton--primario">
                <i class="fa-solid fa-floppy-disk"></i>
                <span>Actualizar Repartidor</span>
            </button>
        </div>
    </form>
</div>
