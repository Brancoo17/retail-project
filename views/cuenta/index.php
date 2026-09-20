<?php
    $rol = $_SESSION['rol'] ?? 'cliente';
    $enlaceVolver = match($rol) {
        'dueño' => '/admin',
        'empleado' => '/cocina',
        'repartidor' => '/repartidor',
        'cliente' => '/cliente',
        default => '/'
    };

    $badgeRolInfo = match($rol) {
        'dueño' => ['texto' => 'Administrador / Dueño', 'clase' => 'badge--destacado'],
        'empleado' => ['texto' => 'Cocina & Caja', 'clase' => 'badge--cocina'],
        'repartidor' => ['texto' => 'Repartidor de Delivery', 'clase' => 'badge--repartidor'],
        'cliente' => ['texto' => 'Cliente Registrado', 'clase' => 'badge--cliente'],
        default => ['texto' => 'Usuario', 'clase' => '']
    };
?>

<div class="contenedor seccion">
    <div style="margin-bottom: 2.4rem;">
        <a href="<?php echo $enlaceVolver; ?>" class="boton boton--secundario">
            <i class="fa-solid fa-arrow-left"></i> Volver a mi Panel
        </a>
    </div>

    <header class="seccion__encabezado u-separador-arriba-sm">
        <span class="badge <?php echo $badgeRolInfo['clase']; ?>"><?php echo $badgeRolInfo['texto']; ?></span>
        <h1 class="seccion__titulo">Mi Cuenta & Perfil</h1>
        <p class="seccion__subtitulo">
            Gestiona tu información de contacto, credenciales de inicio de sesión y contraseña.
        </p>
    </header>

    <?php if(isset($_SESSION['alerta_rapida'])): ?>
        <div class="alerta alerta--<?php echo $_SESSION['alerta_rapida']['tipo']; ?> u-separador-abajo-md">
            <i class="fa-solid fa-circle-check"></i>
            <span><?php echo $_SESSION['alerta_rapida']['mensaje']; ?></span>
        </div>
        <?php unset($_SESSION['alerta_rapida']); ?>
    <?php endif; ?>

    <?php include_once __DIR__ . '/../templates/alertas.php'; ?>

    <form class="formulario formulario--admin" method="POST" action="/cuenta">
        <!-- BLOQUE 1: DATOS PERSONALES -->
        <fieldset class="admin-tarjeta" style="padding: 3.2rem;">
            <legend class="admin-tarjeta__titulo">
                <i class="fa-solid fa-user-gear"></i>
                <span>Datos Personales y de Contacto</span>
            </legend>

            <div class="formulario__campo">
                <label class="formulario__label" for="nombre">Nombre Completo</label>
                <input 
                    type="text" 
                    id="nombre" 
                    name="nombre" 
                    class="formulario__input" 
                    placeholder="Tu nombre y apellido"
                    value="<?php echo s($usuario->nombre ?? ''); ?>"
                    required
                >
            </div>

            <div class="grid grid--2">
                <div class="formulario__campo">
                    <label class="formulario__label" for="email">Correo Electrónico (Usuario de acceso)</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="formulario__input" 
                        placeholder="tu-correo@ejemplo.com"
                        value="<?php echo s($usuario->email ?? ''); ?>"
                        required
                    >
                </div>

                <div class="formulario__campo">
                    <label class="formulario__label" for="telefono">Teléfono / WhatsApp</label>
                    <input 
                        type="tel" 
                        id="telefono" 
                        name="telefono" 
                        class="formulario__input" 
                        placeholder="Ej: 1122334455"
                        value="<?php echo s($usuario->telefono ?? ''); ?>"
                    >
                </div>
            </div>

            <div class="formulario__campo">
                <label class="formulario__label">Rol Asignado en el Sistema</label>
                <input 
                    type="text" 
                    class="formulario__input" 
                    value="<?php echo ucfirst(s($usuario->rol)); ?> (<?php echo $badgeRolInfo['texto']; ?>)" 
                    disabled 
                    style="background-color: #f8fafc; color: #64748b; cursor: not-allowed;"
                >
                <small style="color: #94a3b8; font-size: 1.25rem; margin-top: 0.4rem;">
                    El rol del usuario está definido por la administración y no puede modificarse desde esta sección.
                </small>
            </div>
        </fieldset>

        <!-- BLOQUE 2: CAMBIO DE CONTRASEÑA -->
        <fieldset class="admin-tarjeta" style="padding: 3.2rem; margin-top: 2.4rem;">
            <legend class="admin-tarjeta__titulo">
                <i class="fa-solid fa-key"></i>
                <span>Seguridad y Contraseña</span>
            </legend>

            <p style="color: #64748b; font-size: 1.4rem; margin-bottom: 2rem;">
                Si deseas cambiar tu contraseña actual, ingresa tu contraseña actual y luego define la nueva contraseña. De lo contrario, deja estos campos en blanco.
            </p>

            <div class="formulario__campo">
                <label class="formulario__label" for="password_actual">Contraseña Actual</label>
                <input 
                    type="password" 
                    id="password_actual" 
                    name="password_actual" 
                    class="formulario__input" 
                    placeholder="Ingresa tu contraseña actual para confirmar el cambio"
                    autocomplete="current-password"
                >
            </div>

            <div class="grid grid--2">
                <div class="formulario__campo">
                    <label class="formulario__label" for="password_nuevo">Nueva Contraseña</label>
                    <input 
                        type="password" 
                        id="password_nuevo" 
                        name="password_nuevo" 
                        class="formulario__input" 
                        placeholder="Mínimo 6 caracteres"
                        autocomplete="new-password"
                        minlength="6"
                    >
                </div>

                <div class="formulario__campo">
                    <label class="formulario__label" for="password_confirmar">Confirmar Nueva Contraseña</label>
                    <input 
                        type="password" 
                        id="password_confirmar" 
                        name="password_confirmar" 
                        class="formulario__input" 
                        placeholder="Repite la nueva contraseña"
                        autocomplete="new-password"
                        minlength="6"
                    >
                </div>
            </div>
        </fieldset>

        <!-- BOTONES DE ACCIÓN -->
        <div class="formulario__acciones-admin" style="display: flex; flex-direction: row; justify-content: flex-end; align-items: center; gap: 1.6rem; margin-top: 2.4rem;">
            <a href="<?php echo $enlaceVolver; ?>" class="boton boton--secundario" style="width: auto; min-width: 14rem;">
                Cancelar
            </a>
            <button type="submit" class="boton boton--primario" style="width: auto; min-width: 18rem;">
                <i class="fa-solid fa-floppy-disk"></i> Guardar Cambios
            </button>
        </div>
    </form>
</div>
