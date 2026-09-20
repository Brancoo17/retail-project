<div class="contenedor seccion">
    <div style="margin-bottom: 2.4rem;">
        <a href="/admin/empleados" class="boton boton--secundario">
            <i class="fa-solid fa-arrow-left"></i> Volver al Personal de Cocina
        </a>
    </div>

    <header class="seccion__encabezado u-separador-arriba-sm">
        <span class="badge badge--destacado">Edición de Cuenta</span>
        <h1 class="seccion__titulo">Editar Empleado: <?php echo s($empleado->nombre); ?></h1>
        <p class="seccion__subtitulo">
            Modifica los datos personales, resetea la contraseña de acceso o actualiza el estado de la cuenta.
        </p>
    </header>

    <?php include_once __DIR__ . '/../../templates/alertas.php'; ?>

    <form class="formulario formulario--admin" method="POST">
        <fieldset class="admin-tarjeta" style="padding: 3.2rem;">
            <legend class="admin-tarjeta__titulo">
                <i class="fa-solid fa-user-pen"></i>
                <span>Datos del Empleado</span>
            </legend>

            <?php include_once __DIR__ . '/formulario.php'; ?>
        </fieldset>

        <div class="formulario__acciones-admin" style="display: flex; flex-direction: row; justify-content: flex-end; align-items: center; gap: 1.6rem; margin-top: 2.4rem;">
            <a href="/admin/empleados" class="boton boton--secundario" style="width: auto; min-width: 14rem;">
                Cancelar
            </a>
            <button type="submit" class="boton boton--primario" style="width: auto; min-width: 18rem;">
                <i class="fa-solid fa-floppy-disk"></i> Guardar Cambios
            </button>
        </div>
    </form>
</div>
