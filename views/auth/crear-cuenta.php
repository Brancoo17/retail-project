<div class="auth">
    <div class="auth__tarjeta">
        <header class="auth__encabezado">
            <div class="auth__icono">
                <i class="fa-solid fa-user-plus"></i>
            </div>
            <h1 class="auth__titulo">Crear Cuenta</h1>
            <p class="auth__descripcion">Regístrate para guardar tus direcciones y seguir tus pedidos fácilmente</p>
        </header>

        <?php include_once __DIR__ . '/../templates/alertas.php'; ?>

        <form class="formulario" method="POST" action="/crear-cuenta">
            <div class="formulario__campo">
                <label class="formulario__label" for="nombre">Nombre y Apellido</label>
                <input 
                    type="text" 
                    id="nombre" 
                    name="nombre" 
                    class="formulario__input" 
                    placeholder="Ej: Juan Pérez"
                    value="<?php echo s($usuario->nombre ?? ''); ?>"
                    required
                >
            </div>

            <div class="formulario__campo">
                <label class="formulario__label" for="email">Correo Electrónico</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="formulario__input" 
                    placeholder="tu@correo.com"
                    value="<?php echo s($usuario->email ?? ''); ?>"
                    required
                >
            </div>

            <div class="formulario__campo">
                <label class="formulario__label" for="telefono">Teléfono (WhatsApp)</label>
                <input 
                    type="tel" 
                    id="telefono" 
                    name="telefono" 
                    class="formulario__input" 
                    placeholder="Ej: 11 1234-5678"
                    value="<?php echo s($usuario->telefono ?? ''); ?>"
                    required
                >
            </div>

            <div class="formulario__campo">
                <label class="formulario__label" for="password">Contraseña</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="formulario__input" 
                    placeholder="Mínimo 6 caracteres"
                    required
                >
            </div>

            <button type="submit" class="boton boton--primario boton--bloque">
                <i class="fa-solid fa-user-check"></i>
                <span>Registrarme</span>
            </button>

            <nav class="formulario__acciones">
                <a href="/login">¿Ya tienes cuenta? <strong>Inicia sesión aquí</strong></a>
            </nav>
        </form>
    </div>
</div>
