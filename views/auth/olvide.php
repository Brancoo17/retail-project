<div class="auth">
    <div class="auth__tarjeta">
        <header class="auth__encabezado">
            <div class="auth__icono">
                <i class="fa-solid fa-key"></i>
            </div>
            <h1 class="auth__titulo">Recuperar Contraseña</h1>
            <p class="auth__descripcion">Ingresa el correo electrónico asociado a tu cuenta para restablecerla</p>
        </header>

        <?php include_once __DIR__ . '/../templates/alertas.php'; ?>

        <form class="formulario" method="POST" action="/olvide">
            <div class="formulario__campo">
                <label class="formulario__label" for="email">Correo Electrónico</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="formulario__input" 
                    placeholder="tu@correo.com"
                    required
                >
            </div>

            <button type="submit" class="boton boton--primario boton--bloque">
                <i class="fa-solid fa-paper-plane"></i>
                <span>Enviar Instrucciones</span>
            </button>

            <nav class="formulario__acciones">
                <a href="/login">¿Te acordaste de tu clave? <strong>Inicia sesión</strong></a>
                <a href="/crear-cuenta">¿No tienes cuenta? <strong>Regístrate</strong></a>
            </nav>
        </form>
    </div>
</div>
