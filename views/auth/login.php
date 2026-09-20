<div class="auth">
    <div class="auth__tarjeta">
        <header class="auth__encabezado">
            <div class="auth__icono">
                <i class="fa-solid fa-lock"></i>
            </div>
            <h1 class="auth__titulo">Iniciar Sesión</h1>
            <p class="auth__descripcion">Ingresa a tu cuenta para gestionar pedidos o administrar el local</p>
        </header>

        <?php include_once __DIR__ . '/../templates/alertas.php'; ?>

        <form class="formulario" method="POST" action="/login">
            <div class="formulario__campo">
                <label class="formulario__label" for="email">Correo Electrónico</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="formulario__input" 
                    placeholder="tu@correo.com"
                    value="<?php echo s($auth->email ?? ''); ?>"
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
                    placeholder="Tu contraseña"
                    required
                >
            </div>

            <button type="submit" class="boton boton--primario boton--bloque">
                <i class="fa-solid fa-arrow-right-to-bracket"></i>
                <span>Ingresar</span>
            </button>

            <nav class="formulario__acciones">
                <a href="/crear-cuenta">¿No tienes cuenta aún? <strong>Crea una aquí</strong></a>
                <a href="/olvide">¿Olvidaste tu contraseña?</a>
            </nav>
        </form>
    </div>
</div>
