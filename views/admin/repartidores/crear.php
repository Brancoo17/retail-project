<div class="contenedor seccion">
    <div class="admin-encabezado">
        <a href="/admin/repartidores" class="boton boton--secundario boton--pequeno">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Volver a Repartidores</span>
        </a>
        <h1 class="seccion__titulo">Nuevo Repartidor</h1>
        <p class="seccion__subtitulo">Registra a un integrante de tu equipo de delivery para asignarle pedidos.</p>
    </div>

    <?php include_once __DIR__ . '/../../templates/alertas.php'; ?>

    <form class="formulario formulario--admin" method="POST" action="/admin/repartidores/crear">
        <fieldset class="admin-tarjeta">
            <legend class="admin-tarjeta__titulo">
                <i class="fa-solid fa-motorcycle"></i>
                <span>Datos del Repartidor</span>
            </legend>

            <?php include_once __DIR__ . '/formulario.php'; ?>
        </fieldset>

        <div class="formulario__acciones-admin">
            <button type="submit" class="boton boton--primario">
                <i class="fa-solid fa-user-plus"></i>
                <span>Registrar Repartidor</span>
            </button>
        </div>
    </form>
</div>
